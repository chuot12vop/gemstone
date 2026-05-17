<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Services\CurrencyService;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentInitiationResult;
use App\Services\Payment\PaymentMethodRegistry;
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

    private const ERR_EMPTY_CART = 'Your cart is empty.';

    public function __construct(private PaymentMethodRegistry $registry) {}

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

        return view('shop.checkout.method', [
            'title' => 'Checkout — Choose payment',
            'metaDescription' => 'Choose how you want to pay.',
            'methods' => $methods,
            'selected' => session(self::SESSION_METHOD_KEY),
            'step' => 1,
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
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

        $subtotalUsd = array_sum(array_column($lines, 'line_usd'));

        $user = Auth::user();

        return view('shop.checkout.details', [
            'title' => 'Checkout — Your details',
            'metaDescription' => 'Enter shipping and contact details.',
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
            'gateway' => $gateway,
            'currency' => $currency,
            'step' => 2,
            'checkoutDefaults' => [
                'customer_name' => old('customer_name', $user?->name ?? ''),
                'customer_email' => old('customer_email', $user?->email ?? ''),
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

        $rules = array_merge([
            'customer_name' => 'required|string|max:160',
            'customer_email' => 'required|email',
            'shipping_address' => 'required|string|max:2000',
        ], $gateway->validationRules());
        $validated = $request->validate($rules);

        $lines = $this->buildLines();
        if ($lines === []) {
            return redirect()->route('shop.cart')->with('error', self::ERR_EMPTY_CART);
        }

        $subtotalUsd = (float) array_sum(array_column($lines, 'line_usd'));
        $code = $currency->currentCode();
        $totalDisplay = $currency->convertUsdToCurrent($subtotalUsd);

        $userId = Auth::id();

        $order = DB::transaction(function () use ($validated, $lines, $subtotalUsd, $totalDisplay, $code, $gateway, $userId) {
            $order = Order::query()->create([
                'user_id' => $userId,
                'order_number' => $this->makeOrderNumber(),
                'customer_email' => $validated['customer_email'],
                'customer_name' => $validated['customer_name'],
                'shipping_address' => $validated['shipping_address'],
                'currency_code' => $code,
                'subtotal_usd' => $subtotalUsd,
                'total_display' => $totalDisplay,
                'status' => 'pending',
            ]);

            foreach ($lines as $row) {
                /** @var Product $p */
                $p = $row['product'];
                $q = (int) $row['quantity'];
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_name' => $p->name,
                    'quantity' => $q,
                    'unit_price_usd' => (float) $p->price_usd,
                    'line_total_usd' => (float) $p->price_usd * $q,
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

        session()->forget('cart');
        session()->forget(self::SESSION_METHOD_KEY);

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

        return view('shop.checkout.processing', [
            'title' => 'Complete payment — '.$order->order_number,
            'metaDescription' => 'Finish your payment.',
            'order' => $order,
            'gateway' => $gateway,
            'gatewayData' => $initiation->viewData,
            'step' => 3,
            'lines' => $lines,
            'subtotalUsd' => (float) $order->subtotal_usd,
            'currency' => app(CurrencyService::class),
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
     * @return array<int, array{product: Product, quantity: int, line_usd: float}>
     */
    private function buildLines(): array
    {
        $lines = [];
        foreach ($this->cartItems() as $pid => $qty) {
            $p = Product::query()->where('id', $pid)->where('is_active', true)->first();
            if (! $p) {
                continue;
            }
            $q = min((int) $qty, max(0, $p->stock));
            if ($q < 1) {
                continue;
            }
            $lines[] = [
                'product' => $p,
                'quantity' => $q,
                'line_usd' => (float) $p->price_usd * $q,
            ];
        }

        return $lines;
    }

    private function makeOrderNumber(): string
    {
        return 'GS-'.strtoupper(bin2hex(random_bytes(4))).'-'.date('ymd');
    }

    /**
     * @return array<int, int>
     */
    private function cartItems(): array
    {
        $c = session('cart', []);
        if (! is_array($c)) {
            return [];
        }
        $out = [];
        foreach ($c as $k => $v) {
            $out[(int) $k] = (int) $v;
        }

        return $out;
    }
}
