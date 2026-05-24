<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CurrencyService;
use App\Support\ProductPricing;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        $currency = app(CurrencyService::class);
        $lines = $this->cart->buildLines();
        $subtotalUsd = (float) array_sum(array_column($lines, 'line_usd'));

        return view('shop.cart', [
            'title' => 'Shopping cart — Gemstone',
            'metaDescription' => 'Review your gemstone selections.',
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
            'currency' => $currency,
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
        $this->cart->add($pid, $qty);

        if ($request->boolean('buy_now')) {
            return redirect()->route('shop.checkout');
        }

        return back()->with('success', 'Added to cart.');
    }

    public function addBundle(Request $request)
    {
        $request->validate([
            'parent_product_id' => 'required|integer|exists:products,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        $parent = Product::query()
            ->where('id', (int) $request->parent_product_id)
            ->where('is_active', true)
            ->with('upsellProducts')
            ->first();

        if (! $parent) {
            return back()->with('error', 'Product not available.');
        }

        $upsellMap = $parent->upsellProducts->keyBy('id');
        $allowedIds = collect([$parent->id])->merge($upsellMap->keys());
        $added = 0;

        foreach ($request->input('items', []) as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            if (! $allowedIds->contains($pid)) {
                continue;
            }

            $p = $pid === $parent->id
                ? $parent
                : $upsellMap->get($pid);

            if (! $p instanceof Product) {
                continue;
            }

            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $qty = min($qty, max(0, $p->stock));
            if ($qty < 1) {
                continue;
            }

            $base = (float) $p->price_usd;
            if ($pid === $parent->id) {
                $unitPrice = $base;
            } else {
                $upsalePct = (float) ($p->pivot->upsale_discount ?? 0);
                $discountPct = (float) ($p->pivot->discount ?? 0);
                $percent = $upsalePct > 0 ? $upsalePct : $discountPct;
                $unitPrice = ProductPricing::afterPercentDiscount($base, $percent > 0 ? $percent : null);
            }

            $this->cart->add($pid, $qty, $unitPrice);
            $added++;
        }

        if ($added < 1) {
            return back()->with('error', 'No products were added to your cart.');
        }

        return redirect()->route('shop.cart')->with('success', 'Bundle added to cart.');
    }

    public function update(Request $request)
    {
        foreach ($request->input('qty', []) as $id => $q) {
            $id = (int) $id;
            $q = max(0, (int) $q);
            $existing = $this->cart->get($id);
            if ($q < 1) {
                $this->cart->remove($id);

                continue;
            }
            $p = Product::query()->where('id', $id)->where('is_active', true)->first();
            if (! $p) {
                $this->cart->remove($id);

                continue;
            }
            $price = $existing['unit_price_usd'] ?? null;
            $this->cart->set($id, min($q, $p->stock), $price);
        }

        return redirect()->route('shop.cart');
    }

    public function remove(Request $request)
    {
        $request->validate(['product_id' => 'required|integer']);
        $this->cart->remove((int) $request->product_id);

        return redirect()->route('shop.cart');
    }
}
