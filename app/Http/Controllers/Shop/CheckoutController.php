<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Voucher;
use App\Services\CartService;
use App\Services\CurrencyService;
use App\Services\VoucherService;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentInitiationResult;
use App\Services\Payment\Gateways\PayPalGateway;
use App\Services\Payment\PaymentMethodRegistry;
use App\Services\Payment\PayPal\PayPalApiClient;
use App\Support\CheckoutCountries;
use App\Support\CheckoutShipping;
use App\Support\MarketingSubscribers;
use App\Support\PhoneValidation;
use App\Support\ProductVariantOptions;
use App\Support\PromoCheckoutSession;
use App\Support\ShippingAddressFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Single-page checkout:
 *   GET  /checkout                  — contact, delivery, payment method, voucher
 *   POST /checkout/place            — create order + delegate to gateway
 *   GET  /checkout/processing/{n}   — gateway-specific UI
 *   POST /checkout/confirm/{n}      — gateway return / customer confirmation
 */
class CheckoutController extends Controller
{
    private const SESSION_VOUCHER_KEY = 'checkout.voucher_id';

    private const SESSION_LAST_ORDER_KEY = 'checkout.last_order';

    private const SESSION_PENDING_ORDER_KEY = 'checkout.pending_order';

    private const ERR_EMPTY_CART = 'Your cart is empty.';

    private const ERR_GATEWAY_UNAVAILABLE = 'Payment gateway is temporarily unavailable. Please try again or choose another payment method.';

    public function __construct(
        private PaymentMethodRegistry $registry,
        private CartService $cart,
        private VoucherService $vouchers,
    ) {}

    /** Unified checkout page. */
    public function index(CurrencyService $currency): RedirectResponse|View
    {
        if ($this->buildLines() === []) {
            return redirect()->route('shop.cart')->with('error', self::ERR_EMPTY_CART);
        }

        $methods = $this->registry->enabled();
        if ($methods === []) {
            return redirect()->route('shop.cart')
                ->with('error', 'No payment methods available right now. Please contact support.');
        }

        $lines = $this->buildLines();
        $subtotalUsd = (float) array_sum(array_column($lines, 'line_usd'));
        $totals = $this->checkoutTotals($subtotalUsd, $lines);

        $user = Auth::user();
        [$firstName, $lastName] = $this->splitName($user?->name ?? '');

        $defaultMethod = $methods[0]->code();
        $selected = (string) old('payment_method', session('checkout.method', $defaultMethod));

        return view('shop.checkout.index', [
            'title' => 'Checkout',
            'metaDescription' => 'Complete your order.',
            'methods' => $methods,
            'selected' => $selected,
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
            'discountUsd' => $totals['discountUsd'],
            'shippingUsd' => $totals['shippingUsd'],
            'taxUsd' => $totals['taxUsd'],
            'totalUsd' => $totals['totalUsd'],
            'appliedVoucher' => $totals['voucher'],
            'shippingProgress' => CheckoutShipping::progress($subtotalUsd, $lines),
            'currency' => $currency,
            'checkoutDefaults' => [
                'customer_email' => $this->defaultCustomerEmail($user?->email),
            ],
            'deliveryDefaults' => [
                'country' => old('shipping_country', CheckoutCountries::defaultCode()),
                'first_name' => old('shipping_first_name', $firstName),
                'last_name' => old('shipping_last_name', $lastName),
                'company' => old('shipping_company', ''),
                'address_line1' => old('shipping_address_line1', ''),
                'address_line2' => old('shipping_address_line2', ''),
                'city' => old('shipping_city', ''),
                'postcode' => old('shipping_postcode', ''),
                'phone' => old('shipping_phone', ''),
            ],
            'expressCheckout' => $this->expressCheckoutConfig($currency),
        ]);
    }

    /** Create a PayPal order for express checkout (JSON). */
    public function expressPaypal(Request $request, CurrencyService $currency): JsonResponse
    {
        $gateway = $this->registry->findEnabled('paypal');
        if (! $gateway instanceof PayPalGateway) {
            return response()->json(['message' => 'PayPal is not available.'], 422);
        }

        $client = $gateway->checkoutClient();
        if ($client === null) {
            return response()->json(['message' => 'PayPal is not configured.'], 422);
        }

        $lines = $this->buildLines();
        if ($lines === []) {
            return response()->json(['message' => self::ERR_EMPTY_CART], 422);
        }

        $countryCodes = array_keys(CheckoutCountries::options());
        $validated = $request->validate([
            'customer_email' => 'nullable|email|max:190',
            'shipping_country' => 'nullable|string|in:'.implode(',', $countryCodes),
            'shipping_first_name' => 'nullable|string|max:80',
            'shipping_last_name' => 'nullable|string|max:80',
            'shipping_company' => 'nullable|string|max:120',
            'shipping_address_line1' => 'nullable|string|max:200',
            'shipping_address_line2' => 'nullable|string|max:200',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_postcode' => 'nullable|string|max:32',
            'shipping_phone' => [
                'nullable',
                'string',
                'max:40',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || trim((string) $value) === '') {
                        return;
                    }
                    if (! PhoneValidation::hasMinDigits((string) $value)) {
                        $fail(__('validation.min_digits', [
                            'attribute' => str_replace('_', ' ', $attribute),
                            'min' => 9,
                        ]));
                    }
                },
            ],
            'voucher_code' => 'nullable|string|max:32',
            'marketing_email_opt_in' => 'nullable|boolean',
        ]);

        $validated = $this->expressCheckoutDefaults($validated);

        $customerName = trim($validated['shipping_first_name'].' '.$validated['shipping_last_name']);
        $shippingAddress = ShippingAddressFormatter::toText([
            'first_name' => $validated['shipping_first_name'],
            'last_name' => $validated['shipping_last_name'],
            'company' => $validated['shipping_company'] ?? '',
            'address_line1' => $validated['shipping_address_line1'],
            'address_line2' => $validated['shipping_address_line2'] ?? '',
            'city' => $validated['shipping_city'],
            'postcode' => $validated['shipping_postcode'],
            'country' => $validated['shipping_country'],
            'phone' => $validated['shipping_phone'],
        ]);

        $subtotalUsd = (float) array_sum(array_column($lines, 'line_usd'));
        $currencyCode = $currency->currentCode();

        $voucher = $this->resolveVoucherForCheckout(
            (string) ($validated['voucher_code'] ?? ''),
            (string) $validated['customer_email'],
        );
        if ($voucher === false) {
            return response()->json(['message' => 'Voucher code is invalid or does not match this email.'], 422);
        }

        $discountUsd = $voucher instanceof Voucher ? $voucher->discountUsd($subtotalUsd) : 0.0;
        $amounts = CheckoutShipping::orderAmounts($subtotalUsd, $discountUsd, $lines);
        $totalUsd = $amounts['totalUsd'];
        $totalDisplay = $currency->convertUsdToCurrent($totalUsd);
        $userId = Auth::id();

        $this->cancelPendingOrderBySession();

        $order = DB::transaction(function () use ($validated, $customerName, $shippingAddress, $lines, $subtotalUsd, $discountUsd, $amounts, $totalDisplay, $currencyCode, $userId, $voucher, $request) {
            $order = Order::query()->create([
                'user_id' => $userId,
                'order_number' => $this->makeOrderNumber(),
                'customer_email' => $validated['customer_email'],
                'customer_name' => $customerName,
                'shipping_address' => $shippingAddress,
                'shipping_phone' => ($validated['shipping_phone'] ?? '') !== ''
                    ? $validated['shipping_phone']
                    : null,
                'marketing_sms_opt_in' => false,
                'marketing_email_opt_in' => $request->boolean('marketing_email_opt_in'),
                'currency_code' => $currencyCode,
                'subtotal_usd' => $subtotalUsd,
                'voucher_code' => $voucher?->code,
                'discount_usd' => $discountUsd,
                'shipping_usd' => $amounts['shippingUsd'],
                'tax_usd' => $amounts['taxUsd'],
                'total_display' => $totalDisplay,
                'status' => 'pending',
            ]);

            if ($voucher instanceof Voucher) {
                $this->vouchers->markUsed($voucher, $order);
            }

            foreach ($lines as $row) {
                /** @var Product $p */
                $p = $row['product'];
                /** @var \App\Models\ProductVariant $variant */
                $variant = $row['variant'];
                $q = (int) $row['quantity'];
                $unit = (float) $row['unit_price_usd'];
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_variant_id' => $variant->id,
                    'variant_label' => $row['variant_label'],
                    'product_name' => $p->name,
                    'quantity' => $q,
                    'unit_price_usd' => $unit,
                    'line_total_usd' => $unit * $q,
                ]);

                $variant->stock = max(0, $variant->stock - $q);
                $variant->save();

                $p->load('variants');
                \App\Support\ProductVariantOptions::syncProductDenormalized($p, $p->variants);
            }

            PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'payment_method' => 'paypal',
                'amount' => $totalDisplay,
                'currency_code' => $currencyCode,
                'status' => 'pending',
                'notes' => 'Express PayPal checkout',
            ]);

            return $order;
        });

        try {
            $result = $gateway->initiate($order, $request);
        } catch (\Throwable $e) {
            Log::error('Express PayPal initiation failed', [
                'order_number' => $order->order_number,
                'message' => $e->getMessage(),
            ]);
            $this->rollbackFailedCheckout($order, $voucher instanceof Voucher ? $voucher : null);

            return response()->json(['message' => self::ERR_GATEWAY_UNAVAILABLE], 422);
        }

        if (! $this->isInitiationSuccessful($result)) {
            $message = (string) ($result->viewData['error'] ?? self::ERR_GATEWAY_UNAVAILABLE);
            $this->rollbackFailedCheckout($order, $voucher instanceof Voucher ? $voucher : null);

            return response()->json(['message' => $message], 422);
        }

        $this->updateLatestTransaction($order, [
            'gateway_transaction_id' => $result->gatewayTransactionId,
            'notes' => $result->notes,
        ]);

        $paypalOrderId = $result->viewData['paypalOrderId'] ?? '';
        if ($paypalOrderId === '') {
            $this->rollbackFailedCheckout($order, $voucher instanceof Voucher ? $voucher : null);

            return response()->json([
                'message' => $result->viewData['error'] ?? 'Could not start PayPal checkout.',
            ], 422);
        }

        session([
            self::SESSION_LAST_ORDER_KEY => $order->order_number,
            self::SESSION_PENDING_ORDER_KEY => $order->order_number,
            'checkout.method' => 'paypal',
        ]);

        return response()->json([
            'paypal_order_id' => $paypalOrderId,
            'order_number' => $order->order_number,
            'confirm_url' => route('shop.checkout.confirm', ['order_number' => $order->order_number]),
        ]);
    }

    /** @deprecated Redirect to unified checkout. */
    public function chooseMethod(): RedirectResponse
    {
        return redirect()->route('shop.checkout');
    }

    /** @deprecated Redirect to unified checkout. */
    public function details(): RedirectResponse
    {
        return redirect()->route('shop.checkout');
    }

    /** Validate, create order + transaction, hand off to gateway. */
    public function place(Request $request, CurrencyService $currency): RedirectResponse
    {
        $code = (string) $request->input('payment_method', '');
        $gateway = $this->registry->findEnabled($code);
        if ($gateway === null) {
            return redirect()->route('shop.checkout')
                ->withInput()
                ->withErrors(['payment_method' => 'Please choose a valid payment method.']);
        }

        $countryCodes = implode(',', array_keys(CheckoutCountries::options()));

        $rules = array_merge([
            'payment_method' => 'required|string',
            'customer_email' => 'required|email|max:190',
            'shipping_country' => 'required|string|in:'.$countryCodes,
            'shipping_first_name' => 'required|string|max:80',
            'shipping_last_name' => 'required|string|max:80',
            'shipping_company' => 'nullable|string|max:120',
            'shipping_address_line1' => 'required|string|max:200',
            'shipping_address_line2' => 'nullable|string|max:200',
            'shipping_city' => 'required|string|max:100',
            'shipping_postcode' => 'required|string|max:32',
            'shipping_phone' => PhoneValidation::rules(),
            'shipping_method' => 'nullable|string|in:standard',
            'voucher_code' => 'nullable|string|max:32',
        ], $gateway->validationRules());
        $validated = $request->validate($rules);

        $customerName = trim($validated['shipping_first_name'].' '.$validated['shipping_last_name']);
        $shippingAddress = ShippingAddressFormatter::toText([
            'first_name' => $validated['shipping_first_name'],
            'last_name' => $validated['shipping_last_name'],
            'company' => $validated['shipping_company'] ?? '',
            'address_line1' => $validated['shipping_address_line1'],
            'address_line2' => $validated['shipping_address_line2'] ?? '',
            'city' => $validated['shipping_city'],
            'postcode' => $validated['shipping_postcode'],
            'country' => $validated['shipping_country'],
            'phone' => $validated['shipping_phone'],
        ]);

        $lines = $this->buildLines();
        if ($lines === []) {
            return redirect()->route('shop.cart')->with('error', self::ERR_EMPTY_CART);
        }

        $subtotalUsd = (float) array_sum(array_column($lines, 'line_usd'));
        $currencyCode = $currency->currentCode();

        $voucher = $this->resolveVoucherForCheckout(
            (string) ($validated['voucher_code'] ?? ''),
            (string) $validated['customer_email'],
        );
        if ($voucher === false) {
            return redirect()->route('shop.checkout')
                ->withInput()
                ->withErrors(['voucher_code' => 'This voucher code is invalid, already used, or does not match your email.']);
        }

        $discountUsd = $voucher instanceof Voucher ? $voucher->discountUsd($subtotalUsd) : 0.0;
        $amounts = CheckoutShipping::orderAmounts($subtotalUsd, $discountUsd, $lines);
        $totalUsd = $amounts['totalUsd'];
        $totalDisplay = $currency->convertUsdToCurrent($totalUsd);

        $userId = Auth::id();

        $this->cancelPendingOrderBySession();

        $order = DB::transaction(function () use ($validated, $customerName, $shippingAddress, $lines, $subtotalUsd, $discountUsd, $amounts, $totalDisplay, $currencyCode, $gateway, $userId, $request, $voucher) {
            $order = Order::query()->create([
                'user_id' => $userId,
                'order_number' => $this->makeOrderNumber(),
                'customer_email' => $validated['customer_email'],
                'customer_name' => $customerName,
                'shipping_address' => $shippingAddress,
                'shipping_phone' => $validated['shipping_phone'],
                'marketing_sms_opt_in' => $request->boolean('marketing_sms_opt_in'),
                'marketing_email_opt_in' => $request->boolean('marketing_email_opt_in'),
                'currency_code' => $currencyCode,
                'subtotal_usd' => $subtotalUsd,
                'voucher_code' => $voucher?->code,
                'discount_usd' => $discountUsd,
                'shipping_usd' => $amounts['shippingUsd'],
                'tax_usd' => $amounts['taxUsd'],
                'total_display' => $totalDisplay,
                'status' => 'pending',
            ]);

            if ($voucher instanceof Voucher) {
                $this->vouchers->markUsed($voucher, $order);
            }

            foreach ($lines as $row) {
                /** @var Product $p */
                $p = $row['product'];
                /** @var \App\Models\ProductVariant $variant */
                $variant = $row['variant'];
                $q = (int) $row['quantity'];
                $unit = (float) $row['unit_price_usd'];
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_variant_id' => $variant->id,
                    'variant_label' => $row['variant_label'],
                    'product_name' => $p->name,
                    'quantity' => $q,
                    'unit_price_usd' => $unit,
                    'line_total_usd' => $unit * $q,
                ]);

                $variant->stock = max(0, $variant->stock - $q);
                $variant->save();

                $p->load('variants');
                \App\Support\ProductVariantOptions::syncProductDenormalized($p, $p->variants);
            }

            PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'payment_method' => $gateway->code(),
                'amount' => $totalDisplay,
                'currency_code' => $currencyCode,
                'status' => 'pending',
                'notes' => 'Awaiting gateway initiation',
            ]);

            return $order;
        });

        if ($request->boolean('marketing_email_opt_in')) {
            MarketingSubscribers::subscribe((string) $validated['customer_email']);
        }

        try {
            $result = $gateway->initiate($order, $request);
        } catch (\Throwable $e) {
            Log::error('Payment gateway initiation failed', [
                'order_number' => $order->order_number,
                'gateway' => $gateway->code(),
                'message' => $e->getMessage(),
            ]);
            $this->rollbackFailedCheckout($order, $voucher instanceof Voucher ? $voucher : null);

            return redirect()->route('shop.checkout')
                ->withInput()
                ->with('error', self::ERR_GATEWAY_UNAVAILABLE);
        }

        if (! $this->isInitiationSuccessful($result)) {
            $message = (string) ($result->viewData['error'] ?? self::ERR_GATEWAY_UNAVAILABLE);
            $this->rollbackFailedCheckout($order, $voucher instanceof Voucher ? $voucher : null);

            return redirect()->route('shop.checkout')
                ->withInput()
                ->with('error', $message);
        }

        $this->updateLatestTransaction($order, [
            'gateway_transaction_id' => $result->gatewayTransactionId,
            'notes' => $result->notes,
        ]);

        session([
            self::SESSION_PENDING_ORDER_KEY => $order->order_number,
            'checkout.method' => $code,
        ]);
        session()->forget(self::SESSION_VOUCHER_KEY);
        session([self::SESSION_LAST_ORDER_KEY => $order->order_number]);

        return match ($result->type) {
            PaymentInitiationResult::TYPE_REDIRECT => redirect()->away((string) $result->redirectUrl),
            PaymentInitiationResult::TYPE_COMPLETED => $this->markOrderPaid($order, $gateway, $result->gatewayTransactionId),
            default => redirect()->route('shop.checkout.processing', ['order_number' => $order->order_number]),
        };
    }

    /** Gateway-specific UI page (performing the payment). */
    public function processing(Request $request, string $order_number): View|RedirectResponse
    {
        $order = Order::query()->where('order_number', $order_number)->firstOrFail();
        $gateway = $this->registry->find((string) optional($order->paymentTransactions()->first())->payment_method);

        if (! $gateway) {
            return redirect()->route('shop.order.show', ['order_number' => $order->order_number]);
        }

        if ($order->status === 'paid') {
            return redirect()->route('shop.order.show', ['order_number' => $order->order_number]);
        }

        try {
            $initiation = $gateway->initiate($order, $request);
        } catch (\Throwable $e) {
            Log::error('Payment gateway initiation failed on processing page', [
                'order_number' => $order->order_number,
                'gateway' => $gateway->code(),
                'message' => $e->getMessage(),
            ]);
            $voucher = $this->voucherForOrder($order);
            $this->rollbackFailedCheckout($order, $voucher);

            return redirect()->route('shop.checkout')
                ->with('error', self::ERR_GATEWAY_UNAVAILABLE);
        }

        if (! $this->isInitiationSuccessful($initiation)) {
            $message = (string) ($initiation->viewData['error'] ?? self::ERR_GATEWAY_UNAVAILABLE);
            $voucher = $this->voucherForOrder($order);
            $this->rollbackFailedCheckout($order, $voucher);

            return redirect()->route('shop.checkout')
                ->with('error', $message);
        }

        $order->load(['items.product']);
        $lines = [];
        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product === null) {
                $product = new Product([
                    'name' => $item->product_name,
                    'image' => null,
                    'price_usd' => $item->unit_price_usd,
                ]);
            }
            $lines[] = [
                'product' => $product,
                'quantity' => $item->quantity,
                'line_usd' => (float) $item->line_total_usd,
            ];
        }

        $subtotalUsd = (float) $order->subtotal_usd;
        $discountUsd = (float) ($order->discount_usd ?? 0);
        $shippingUsd = (float) ($order->shipping_usd ?? 0);
        $taxUsd = (float) ($order->tax_usd ?? 0);
        $totalUsd = max(0, $subtotalUsd - $discountUsd + $shippingUsd + $taxUsd);

        return view('shop.checkout.processing', [
            'title' => 'Complete payment — '.$order->order_number,
            'metaDescription' => 'Finish your payment.',
            'order' => $order,
            'gateway' => $gateway,
            'gatewayData' => $initiation->viewData,
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
            'discountUsd' => $discountUsd,
            'shippingUsd' => $shippingUsd,
            'taxUsd' => $taxUsd,
            'totalUsd' => $totalUsd,
            'currency' => app(CurrencyService::class),
        ]);
    }

    public function cancelPending(string $order_number): RedirectResponse
    {
        $order = Order::query()->where('order_number', $order_number)->firstOrFail();

        if ($order->status !== 'pending') {
            return redirect()->route('shop.checkout')
                ->with('error', 'This order can no longer be changed.');
        }

        $this->restoreOrderStock($order);
        $order->status = 'cancelled';
        $order->save();

        $this->updateLatestTransaction($order, [
            'status' => 'cancelled',
            'notes' => 'Cancelled by customer — returned to checkout',
        ]);

        if (session(self::SESSION_PENDING_ORDER_KEY) === $order->order_number) {
            session()->forget(self::SESSION_PENDING_ORDER_KEY);
        }

        return redirect()->route('shop.checkout')
            ->with('success', 'Your order was cancelled. You can review your details and choose a payment method again.');
    }

    public function applyVoucher(Request $request, CurrencyService $currency): JsonResponse
    {
        if ($this->buildLines() === []) {
            return response()->json(['ok' => false, 'message' => self::ERR_EMPTY_CART], 422);
        }

        $validated = $request->validate([
            'voucher_code' => 'required|string|max:32',
            'customer_email' => 'required|email|max:190',
        ]);

        $voucher = $this->vouchers->findApplicable(
            $validated['voucher_code'],
            $validated['customer_email'],
        );

        if ($voucher === null) {
            session()->forget(self::SESSION_VOUCHER_KEY);

            return response()->json([
                'ok' => false,
                'message' => 'Invalid voucher, already used, or email does not match.',
            ], 422);
        }

        session([self::SESSION_VOUCHER_KEY => $voucher->id]);

        $subtotalUsd = (float) array_sum(array_column($this->buildLines(), 'line_usd'));
        $lines = $this->buildLines();
        $discountUsd = $voucher->discountUsd($subtotalUsd);
        $amounts = CheckoutShipping::orderAmounts($subtotalUsd, $discountUsd, $lines);

        return response()->json(array_merge(
            $this->shippingProgressPayload($subtotalUsd, $currency, $lines),
            [
                'ok' => true,
                'code' => $voucher->code,
                'percent' => $voucher->percent,
                'discount_usd' => $discountUsd,
                'shipping_usd' => $amounts['shippingUsd'],
                'tax_usd' => $amounts['taxUsd'],
                'total_usd' => $amounts['totalUsd'],
                'subtotal_usd' => $subtotalUsd,
                'discount_formatted' => $currency->formatUsd($discountUsd),
                'shipping_formatted' => $currency->formatUsd($amounts['shippingUsd']),
                'tax_formatted' => $currency->formatUsd($amounts['taxUsd']),
                'total_formatted' => $currency->formatUsd($amounts['totalUsd']),
            ],
        ));
    }

    public function removeVoucher(CurrencyService $currency): JsonResponse
    {
        session()->forget(self::SESSION_VOUCHER_KEY);
        $lines = $this->buildLines();
        $subtotalUsd = (float) array_sum(array_column($lines, 'line_usd'));
        $amounts = CheckoutShipping::orderAmounts($subtotalUsd, 0.0, $lines);

        return response()->json(array_merge(
            $this->shippingProgressPayload($subtotalUsd, $currency, $lines),
            [
                'ok' => true,
                'shipping_usd' => $amounts['shippingUsd'],
                'tax_usd' => $amounts['taxUsd'],
                'total_usd' => $amounts['totalUsd'],
                'subtotal_usd' => $subtotalUsd,
                'shipping_formatted' => $currency->formatUsd($amounts['shippingUsd']),
                'tax_formatted' => $currency->formatUsd($amounts['taxUsd']),
                'total_formatted' => $currency->formatUsd($amounts['totalUsd']),
            ],
        ));
    }

    /** Gateway return / "I have paid" confirmation. */
    public function confirm(Request $request, string $order_number): RedirectResponse|JsonResponse
    {
        $order = Order::query()->where('order_number', $order_number)->firstOrFail();
        $tx = $order->paymentTransactions()->first();
        $gateway = $this->registry->find((string) optional($tx)->payment_method);

        if (! $gateway) {
            return redirect()->route('shop.order.show', ['order_number' => $order->order_number]);
        }

        if ($gateway->confirm($order, $request)) {
            if ($gateway instanceof PayPalGateway) {
                $paypalOrderId = trim((string) $request->input('paypal_order_id', ''));
                if ($paypalOrderId !== '') {
                    $gateway->syncExpressPayerDetails($order, $paypalOrderId);
                    $order->refresh();
                }
            }

            if (! $gateway->marksOrderPaidOnConfirm()) {
                $redirect = redirect()
                    ->route('shop.order.show', ['order_number' => $order->order_number])
                    ->with('success', 'Thank you! We received your payment proof for order #'.$order->order_number.'. Our team will verify and confirm shortly.');

                return $request->expectsJson()
                    ? response()->json(['redirect' => $redirect->getTargetUrl()])
                    : $redirect;
            }

            $redirect = $this->markOrderPaid($order, $gateway, $request->input('gateway_transaction_id'));

            return $request->expectsJson()
                ? response()->json(['redirect' => $redirect->getTargetUrl()])
                : $redirect;
        }

        $this->updateLatestTransaction($order, [
            'status' => 'failed',
            'notes' => 'Gateway confirmation failed',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Payment could not be confirmed. Please try again.',
            ], 422);
        }

        return redirect()->route('shop.checkout.processing', ['order_number' => $order->order_number])
            ->with('error', 'Payment could not be confirmed. Please try again.');
    }

    public function confirmation(string $order_number): View
    {
        $order = Order::query()->where('order_number', $order_number)->firstOrFail();
        $order->load(['items.review', 'paymentTransactions']);

        return view('shop.order_confirmation', [
            'title' => 'Order '.$order->order_number,
            'metaDescription' => 'Your order confirmation.',
            'order' => $order,
        ]);
    }

    private function markOrderPaid(Order $order, PaymentGateway $gateway, ?string $txId): RedirectResponse
    {
        $this->cart->clear();
        session()->forget(self::SESSION_VOUCHER_KEY);
        session()->forget(self::SESSION_PENDING_ORDER_KEY);

        DB::transaction(function () use ($order, $txId) {
            $order->status = 'paid';
            $order->save();
            $this->updateLatestTransaction($order, [
                'status' => 'paid',
                'paid_at' => now(),
                'gateway_transaction_id' => $txId,
                'notes' => 'Payment captured',
            ]);
        });

        return redirect()
            ->route('shop.order.show', ['order_number' => $order->order_number])
            ->with('success', 'Thanks! Order #'.$order->order_number.' is paid via '.$gateway->label().'.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function updateLatestTransaction(Order $order, array $attributes): void
    {
        $tx = $order->paymentTransactions()->first();
        if (! $tx) {
            return;
        }
        foreach ($attributes as $key => $value) {
            if ($value === null) {
                continue;
            }
            $tx->{$key} = $value;
        }
        $tx->save();
    }

    /**
     * @return array<int, array{product: Product, quantity: int, unit_price_usd: float, line_usd: float}>
     */
    private function buildLines(): array
    {
        return $this->cart->buildLines();
    }

    private function makeOrderNumber(): string
    {
        return 'GS-'.strtoupper(bin2hex(random_bytes(4))).'-'.date('ymd');
    }

    /**
     * @return array{voucher: ?Voucher, discountUsd: float, shippingUsd: float, taxUsd: float, totalUsd: float}
     */
    private function checkoutTotals(float $subtotalUsd, array $lines): array
    {
        $voucher = $this->sessionVoucher();
        $discountUsd = $voucher ? $voucher->discountUsd($subtotalUsd) : 0.0;
        $amounts = CheckoutShipping::orderAmounts($subtotalUsd, $discountUsd, $lines);

        return [
            'voucher' => $voucher,
            'discountUsd' => $discountUsd,
            'shippingUsd' => $amounts['shippingUsd'],
            'taxUsd' => $amounts['taxUsd'],
            'totalUsd' => $amounts['totalUsd'],
        ];
    }

    private function sessionVoucher(): ?Voucher
    {
        $id = session(self::SESSION_VOUCHER_KEY);
        if (! $id) {
            return null;
        }

        $voucher = Voucher::query()->find($id);
        if ($voucher === null || $voucher->isUsed()) {
            session()->forget(self::SESSION_VOUCHER_KEY);

            return null;
        }

        return $voucher;
    }

    /**
     * @return Voucher|null|false null = no voucher; false = invalid
     */
    private function resolveVoucherForCheckout(string $code, string $email)
    {
        $code = trim($code);
        if ($code === '') {
            $session = $this->sessionVoucher();
            if ($session === null) {
                return null;
            }
            $code = $session->code;
        }

        $voucher = $this->vouchers->findApplicable($code, $email);
        if ($voucher === null) {
            return false;
        }

        session([self::SESSION_VOUCHER_KEY => $voucher->id]);

        return $voucher;
    }

    /**
     * @return array{show: bool, slots: list<string>, paypal: ?array<string, mixed>}
     */
    private function expressCheckoutConfig(CurrencyService $currency): array
    {
        $paypalGateway = $this->registry->findEnabled('paypal');
        if (! $paypalGateway instanceof PayPalGateway) {
            return ['show' => false, 'slots' => [], 'paypal' => null];
        }

        $client = $paypalGateway->checkoutClient();
        if ($client === null) {
            return ['show' => false, 'slots' => [], 'paypal' => null];
        }

        $code = strtoupper($currency->currentCode());
        $paypal = [
            'clientId' => $client->clientId(),
            'sdkUrl' => $client->sdkUrl($code, 'buttons'),
            'sandbox' => $client->isSandbox(),
            'initUrl' => route('shop.checkout.express.paypal'),
            'currency' => $code,
        ];

        return [
            'show' => true,
            'slots' => ['paypal', 'google_pay'],
            'paypal' => $paypal,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function expressCheckoutDefaults(array $validated): array
    {
        $email = trim((string) ($validated['customer_email'] ?? ''));
        if ($email === '') {
            $validated['customer_email'] = $this->expressPlaceholderEmail();
        }

        if (trim((string) ($validated['shipping_phone'] ?? '')) === '') {
            $validated['shipping_phone'] = '';
        }

        return $this->expressShippingDefaults($validated);
    }

    private function expressPlaceholderEmail(): string
    {
        $accountEmail = Auth::user()?->email;
        if (is_string($accountEmail) && trim($accountEmail) !== '') {
            return strtolower(trim($accountEmail));
        }

        return 'express.'.bin2hex(random_bytes(8)).'@checkout.pending';
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function expressShippingDefaults(array $validated): array
    {
        $country = trim((string) ($validated['shipping_country'] ?? ''));
        if ($country === '') {
            $validated['shipping_country'] = CheckoutCountries::defaultCode();
        }

        foreach ([
            'shipping_first_name' => 'Guest',
            'shipping_last_name' => 'Customer',
            'shipping_address_line1' => 'Express checkout — address to confirm',
            'shipping_city' => '—',
            'shipping_postcode' => '—',
        ] as $key => $fallback) {
            if (trim((string) ($validated[$key] ?? '')) === '') {
                $validated[$key] = $fallback;
            }
        }

        return $validated;
    }

    private function defaultCustomerEmail(?string $accountEmail): string
    {
        $old = old('customer_email');
        if (is_string($old) && trim($old) !== '') {
            return $old;
        }

        if ($accountEmail !== null && trim($accountEmail) !== '') {
            return $accountEmail;
        }

        return PromoCheckoutSession::subscriberEmail();
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return ['', ''];
        }
        $parts = preg_split('/\s+/', $fullName, 2) ?: [];

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    /**
     * @param  array<int, array{quantity?: int}>  $lines
     * @return array<string, mixed>
     */
    private function shippingProgressPayload(float $subtotalUsd, CurrencyService $currency, array $lines = []): array
    {
        $progress = CheckoutShipping::progress($subtotalUsd, $lines);

        return [
            'shipping_qualified' => $progress['qualified'],
            'shipping_percent' => $progress['percent'],
            'shipping_remaining_usd' => $progress['remaining'],
            'shipping_remaining_formatted' => $currency->formatUsd($progress['remaining']),
            'shipping_threshold_usd' => $progress['threshold'],
            'shipping_min_items' => $progress['min_items'],
            'shipping_item_count' => $progress['item_count'],
        ];
    }

    private function cancelPendingOrderBySession(): void
    {
        $orderNumber = session(self::SESSION_PENDING_ORDER_KEY);
        if (! is_string($orderNumber) || $orderNumber === '') {
            return;
        }

        $order = Order::query()->where('order_number', $orderNumber)->where('status', 'pending')->first();
        if ($order === null) {
            session()->forget(self::SESSION_PENDING_ORDER_KEY);

            return;
        }

        $this->restoreOrderStock($order);
        $order->status = 'cancelled';
        $order->save();
        $this->updateLatestTransaction($order, [
            'status' => 'cancelled',
            'notes' => 'Replaced by a new checkout attempt',
        ]);
        session()->forget(self::SESSION_PENDING_ORDER_KEY);
    }

    private function restoreOrderStock(Order $order): void
    {
        $order->loadMissing(['items.product']);

        foreach ($order->items as $item) {
            if ($item->product_variant_id) {
                $variant = ProductVariant::query()->find($item->product_variant_id);
                if ($variant) {
                    $variant->stock = (int) $variant->stock + (int) $item->quantity;
                    $variant->save();

                    $product = $item->product;
                    if ($product) {
                        $product->load('variants');
                        ProductVariantOptions::syncProductDenormalized($product, $product->variants);
                    }
                }
            }
        }
    }

    private function isInitiationSuccessful(PaymentInitiationResult $result): bool
    {
        if (isset($result->viewData['error']) && $result->viewData['error'] !== '') {
            return false;
        }

        if (($result->viewData['configured'] ?? true) === false) {
            return false;
        }

        return match ($result->type) {
            PaymentInitiationResult::TYPE_REDIRECT => filled($result->redirectUrl),
            PaymentInitiationResult::TYPE_COMPLETED => true,
            PaymentInitiationResult::TYPE_VIEW => true,
            default => false,
        };
    }

    private function rollbackFailedCheckout(Order $order, ?Voucher $voucher = null): void
    {
        if ($order->status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($order, $voucher): void {
            $this->restoreOrderStock($order);
            $order->status = 'cancelled';
            $order->save();

            $this->updateLatestTransaction($order, [
                'status' => 'cancelled',
                'notes' => 'Payment gateway failed — rolled back',
            ]);

            if ($voucher instanceof Voucher && $voucher->order_id === $order->id) {
                $this->vouchers->release($voucher);
            }
        });

        if (session(self::SESSION_PENDING_ORDER_KEY) === $order->order_number) {
            session()->forget(self::SESSION_PENDING_ORDER_KEY);
        }
        session()->forget(self::SESSION_LAST_ORDER_KEY);
    }

    private function voucherForOrder(Order $order): ?Voucher
    {
        if ($order->voucher_code === null || $order->voucher_code === '') {
            return null;
        }

        return Voucher::query()
            ->where('code', $order->voucher_code)
            ->where('order_id', $order->id)
            ->first();
    }
}
