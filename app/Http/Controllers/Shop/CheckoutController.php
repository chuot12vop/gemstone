<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\CartService;
use App\Services\CurrencyService;
use App\Services\VoucherService;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentInitiationResult;
use App\Services\Payment\PaymentMethodRegistry;
use App\Support\CheckoutCountries;
use App\Support\ShippingAddressFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Three-step checkout flow:
 *   1) GET  /checkout                  — pick a payment method (loaded from DB)
 *   2) GET  /checkout/details          — fill in customer / shipping info
 *   3) POST /checkout/place            — create the order + delegate to gateway
 *      GET  /checkout/processing/{n}   — gateway-specific UI (button, link, ...)
 *      POST /checkout/confirm/{n}      — gateway return / customer confirmation
 *
 * Payment gateways are resolved through {@see PaymentMethodRegistry} which uses
 * the Strategy pattern, so the controller never needs to know about specific
 * methods. Adding a new method requires zero changes here.
 */
class CheckoutController extends Controller
{
    private const SESSION_METHOD_KEY = 'checkout.method';

    private const SESSION_VOUCHER_KEY = 'checkout.voucher_id';

    private const ERR_EMPTY_CART = 'Your cart is empty.';

    public function __construct(
        private PaymentMethodRegistry $registry,
        private CartService $cart,
        private VoucherService $vouchers,
    ) {}

    /** Step 1 — choose payment method. */
    public function index(): RedirectResponse|View
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
        $totals = $this->checkoutTotals($subtotalUsd);

        return view('shop.checkout.method', [
            'title' => 'Checkout — Choose payment',
            'metaDescription' => 'Choose how you want to pay.',
            'methods' => $methods,
            'selected' => session(self::SESSION_METHOD_KEY),
            'step' => 1,
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
            'discountUsd' => $totals['discountUsd'],
            'totalUsd' => $totals['totalUsd'],
            'appliedVoucher' => $totals['voucher'],
            'currency' => app(CurrencyService::class),
        ]);
    }

    /** Step 1 → 2 transition: persist the selected method in session. */
    public function chooseMethod(Request $request): RedirectResponse
    {
        $code = (string) $request->input('payment_method', '');
        if ($this->registry->findEnabled($code) === null) {
            return redirect()->route('shop.checkout')
                ->withErrors(['payment_method' => 'Please choose a valid payment method.']);
        }
        session([self::SESSION_METHOD_KEY => $code]);

        return redirect()->route('shop.checkout.details');
    }

    /** Step 2 — collect customer info + render any gateway-specific extras. */
    public function details(CurrencyService $currency): RedirectResponse|View
    {
        [$gateway, $redirect] = $this->resolveSelectedGateway();
        if ($redirect !== null) {
            return $redirect;
        }

        $lines = $this->buildLines();
        if ($lines === []) {
            return redirect()->route('shop.cart')->with('error', self::ERR_EMPTY_CART);
        }

        $subtotalUsd = (float) array_sum(array_column($lines, 'line_usd'));
        $totals = $this->checkoutTotals($subtotalUsd);

        $user = Auth::user();
        [$firstName, $lastName] = $this->splitName($user?->name ?? '');

        return view('shop.checkout.details', [
            'title' => 'Checkout — Your details',
            'metaDescription' => 'Enter shipping and contact details.',
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
            'discountUsd' => $totals['discountUsd'],
            'totalUsd' => $totals['totalUsd'],
            'appliedVoucher' => $totals['voucher'],
            'gateway' => $gateway,
            'currency' => $currency,
            'step' => 2,
            'checkoutDefaults' => [
                'customer_email' => old('customer_email', $user?->email ?? ''),
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
        ]);
    }

    /** Step 2 → 3: validate, create order + transaction, hand off to gateway. */
    public function place(Request $request, CurrencyService $currency): RedirectResponse
    {
        [$gateway, $redirect] = $this->resolveSelectedGateway();
        if ($redirect !== null) {
            return $redirect;
        }

        $countryCodes = implode(',', array_keys(CheckoutCountries::options()));

        $rules = array_merge([
            'customer_email' => 'required|email|max:190',
            'shipping_country' => 'required|string|in:'.$countryCodes,
            'shipping_first_name' => 'required|string|max:80',
            'shipping_last_name' => 'required|string|max:80',
            'shipping_company' => 'nullable|string|max:120',
            'shipping_address_line1' => 'required|string|max:200',
            'shipping_address_line2' => 'nullable|string|max:200',
            'shipping_city' => 'required|string|max:100',
            'shipping_postcode' => 'required|string|max:32',
            'shipping_phone' => 'required|string|max:40',
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
        $code = $currency->currentCode();

        $voucher = $this->resolveVoucherForCheckout(
            (string) ($validated['voucher_code'] ?? ''),
            (string) $validated['customer_email'],
        );
        if ($voucher === false) {
            return redirect()->route('shop.checkout.details')
                ->withInput()
                ->withErrors(['voucher_code' => 'This voucher code is invalid, already used, or does not match your email.']);
        }

        $discountUsd = $voucher instanceof Voucher ? $voucher->discountUsd($subtotalUsd) : 0.0;
        $totalUsd = max(0, $subtotalUsd - $discountUsd);
        $totalDisplay = $currency->convertUsdToCurrent($totalUsd);

        $userId = Auth::id();

        $order = DB::transaction(function () use ($validated, $customerName, $shippingAddress, $lines, $subtotalUsd, $discountUsd, $totalDisplay, $code, $gateway, $userId, $request, $voucher) {
            $order = Order::query()->create([
                'user_id' => $userId,
                'order_number' => $this->makeOrderNumber(),
                'customer_email' => $validated['customer_email'],
                'customer_name' => $customerName,
                'shipping_address' => $shippingAddress,
                'shipping_phone' => $validated['shipping_phone'],
                'marketing_sms_opt_in' => $request->boolean('marketing_sms_opt_in'),
                'currency_code' => $code,
                'subtotal_usd' => $subtotalUsd,
                'voucher_code' => $voucher?->code,
                'discount_usd' => $discountUsd,
                'total_display' => $totalDisplay,
                'status' => 'pending',
            ]);

            if ($voucher instanceof Voucher) {
                $this->vouchers->markUsed($voucher, $order);
            }

            foreach ($lines as $row) {
                /** @var Product $p */
                $p = $row['product'];
                $q = (int) $row['quantity'];
                $unit = (float) $row['unit_price_usd'];
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_name' => $p->name,
                    'quantity' => $q,
                    'unit_price_usd' => $unit,
                    'line_total_usd' => $unit * $q,
                ]);
                $p->stock = max(0, $p->stock - $q);
                $p->save();
            }

            PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'payment_method' => $gateway->code(),
                'amount' => $totalDisplay,
                'currency_code' => $code,
                'status' => 'pending',
                'notes' => 'Awaiting gateway initiation',
            ]);

            return $order;
        });

        $result = $gateway->initiate($order, $request);
        $this->updateLatestTransaction($order, [
            'gateway_transaction_id' => $result->gatewayTransactionId,
            'notes' => $result->notes,
        ]);

        $this->cart->clear();
        session()->forget([self::SESSION_METHOD_KEY, self::SESSION_VOUCHER_KEY]);

        return match ($result->type) {
            PaymentInitiationResult::TYPE_REDIRECT => redirect()->away((string) $result->redirectUrl),
            PaymentInitiationResult::TYPE_COMPLETED => $this->markOrderPaid($order, $gateway, $result->gatewayTransactionId),
            default => redirect()->route('shop.checkout.processing', ['order_number' => $order->order_number]),
        };
    }

    /** Gateway-specific UI page (Step 3 — performing the payment). */
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

        $initiation = $gateway->initiate($order, $request);

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
        $totalUsd = max(0, $subtotalUsd - $discountUsd);

        return view('shop.checkout.processing', [
            'title' => 'Complete payment — '.$order->order_number,
            'metaDescription' => 'Finish your payment.',
            'order' => $order,
            'gateway' => $gateway,
            'gatewayData' => $initiation->viewData,
            'step' => 3,
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
            'discountUsd' => $discountUsd,
            'totalUsd' => $totalUsd,
            'currency' => app(CurrencyService::class),
        ]);
    }

    public function applyVoucher(Request $request): JsonResponse
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
        $discountUsd = $voucher->discountUsd($subtotalUsd);

        return response()->json([
            'ok' => true,
            'code' => $voucher->code,
            'percent' => $voucher->percent,
            'discount_usd' => $discountUsd,
            'total_usd' => max(0, $subtotalUsd - $discountUsd),
            'discount_formatted' => app(CurrencyService::class)->formatUsd($discountUsd),
            'total_formatted' => app(CurrencyService::class)->formatUsd(max(0, $subtotalUsd - $discountUsd)),
        ]);
    }

    public function removeVoucher(): JsonResponse
    {
        session()->forget(self::SESSION_VOUCHER_KEY);
        $subtotalUsd = (float) array_sum(array_column($this->buildLines(), 'line_usd'));

        return response()->json([
            'ok' => true,
            'total_usd' => $subtotalUsd,
            'total_formatted' => app(CurrencyService::class)->formatUsd($subtotalUsd),
        ]);
    }

    /** Gateway return / "I have paid" confirmation. */
    public function confirm(Request $request, string $order_number): RedirectResponse
    {
        $order = Order::query()->where('order_number', $order_number)->firstOrFail();
        $tx = $order->paymentTransactions()->first();
        $gateway = $this->registry->find((string) optional($tx)->payment_method);

        if (! $gateway) {
            return redirect()->route('shop.order.show', ['order_number' => $order->order_number]);
        }

        if ($gateway->confirm($order, $request)) {
            if (! $gateway->marksOrderPaidOnConfirm()) {
                return redirect()
                    ->route('shop.order.show', ['order_number' => $order->order_number])
                    ->with('success', 'Thank you! We received your payment proof for order #'.$order->order_number.'. Our team will verify and confirm shortly.');
            }

            return $this->markOrderPaid($order, $gateway, $request->input('gateway_transaction_id'));
        }

        $this->updateLatestTransaction($order, [
            'status' => 'failed',
            'notes' => 'Gateway confirmation failed',
        ]);

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

    /**
     * Resolve the gateway saved in session and bail out to step 1 if missing.
     *
     * @return array{0: ?PaymentGateway, 1: ?RedirectResponse}
     */
    private function resolveSelectedGateway(): array
    {
        $code = (string) session(self::SESSION_METHOD_KEY, '');
        $gateway = $this->registry->findEnabled($code);
        if ($gateway === null) {
            return [null, redirect()->route('shop.checkout')
                ->with('error', 'Please choose a payment method first.')];
        }

        return [$gateway, null];
    }

    private function markOrderPaid(Order $order, PaymentGateway $gateway, ?string $txId): RedirectResponse
    {
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
     * @return array{voucher: ?Voucher, discountUsd: float, totalUsd: float}
     */
    private function checkoutTotals(float $subtotalUsd): array
    {
        $voucher = $this->sessionVoucher();
        $discountUsd = $voucher ? $voucher->discountUsd($subtotalUsd) : 0.0;

        return [
            'voucher' => $voucher,
            'discountUsd' => $discountUsd,
            'totalUsd' => max(0, $subtotalUsd - $discountUsd),
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
    /** @return Voucher|null|false */
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
}
