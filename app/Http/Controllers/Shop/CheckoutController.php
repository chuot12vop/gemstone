<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function index()
    {
        $lines = [];
        $subtotalUsd = 0.0;
        foreach ($this->cartItems() as $pid => $qty) {
            $p = Product::query()->where('id', $pid)->where('is_active', true)->first();
            if (! $p) {
                continue;
            }
            $q = min((int) $qty, max(0, $p->stock));
            if ($q < 1) {
                continue;
            }
            $line = (float) $p->price_usd * $q;
            $subtotalUsd += $line;
            $lines[] = ['product' => $p, 'quantity' => $q, 'line_usd' => $line];
        }

        if ($lines === []) {
            return redirect()->route('shop.cart')->with('error', 'Your cart is empty.');
        }

        return view('shop.checkout', [
            'title' => 'Checkout — Gemstone',
            'metaDescription' => 'Complete your order securely.',
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
        ]);
    }

    public function place(Request $request, CurrencyService $currency)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:160',
            'customer_email' => 'required|email',
            'shipping_address' => 'required|string|max:2000',
        ]);

        $lines = [];
        $subtotalUsd = 0.0;
        foreach ($this->cartItems() as $pid => $qty) {
            $p = Product::query()->where('id', $pid)->where('is_active', true)->first();
            if (! $p) {
                continue;
            }
            $q = min((int) $qty, max(0, $p->stock));
            if ($q < 1) {
                continue;
            }
            $line = (float) $p->price_usd * $q;
            $subtotalUsd += $line;
            $lines[] = ['product' => $p, 'quantity' => $q, 'line_usd' => $line];
        }

        if ($lines === []) {
            return redirect()->route('shop.cart')->with('error', 'Your cart is empty.');
        }

        $code = $currency->currentCode();
        $totalDisplay = $currency->convertUsdToCurrent($subtotalUsd);

        $order = Order::query()->create([
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

        session()->forget('cart');

        return redirect()
            ->route('shop.order.show', ['order_number' => $order->order_number])
            ->with('success', 'Thank you! Order #'.$order->order_number.' received.');
    }

    public function confirmation(string $order_number)
    {
        $order = Order::query()->where('order_number', $order_number)->firstOrFail();
        $order->load('items');

        return view('shop.order_confirmation', [
            'title' => 'Order '.$order->order_number,
            'metaDescription' => 'Your order confirmation.',
            'order' => $order,
        ]);
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
