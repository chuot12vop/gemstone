<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
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

        return view('shop.cart', [
            'title' => 'Shopping cart — Gemstone',
            'metaDescription' => 'Review your gemstone selections.',
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);
        $pid = (int) $request->product_id;
        $qty = max(1, (int) $request->input('quantity', 1));
        $p = Product::query()->where('id', $pid)->where('is_active', true)->first();
        if (! $p) {
            return back()->with('error', 'Product not available.');
        }
        $qty = min($qty, max(0, $p->stock));
        $cart = $this->cartItems();
        $cart[$pid] = ($cart[$pid] ?? 0) + $qty;
        $cart[$pid] = min((int) $cart[$pid], $p->stock);
        session(['cart' => $cart]);

        return back()->with('success', 'Added to cart.');
    }

    public function update(Request $request)
    {
        $cart = $this->cartItems();
        foreach ($request->input('qty', []) as $id => $q) {
            $id = (int) $id;
            $q = max(0, (int) $q);
            if ($q < 1) {
                unset($cart[$id]);
            } else {
                $p = Product::query()->where('id', $id)->where('is_active', true)->first();
                if ($p) {
                    $cart[$id] = min($q, $p->stock);
                }
            }
        }
        session(['cart' => $cart]);

        return redirect()->route('shop.cart');
    }

    public function remove(Request $request)
    {
        $request->validate(['product_id' => 'required|integer']);
        $pid = (int) $request->product_id;
        $cart = $this->cartItems();
        unset($cart[$pid]);
        session(['cart' => $cart]);

        return redirect()->route('shop.cart');
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
