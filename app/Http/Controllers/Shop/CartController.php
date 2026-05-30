<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\CurrencyService;
use App\Support\CheckoutShipping;
use App\Support\ProductPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        $currency = app(CurrencyService::class);
        $lines = $this->cart->buildLines();
        $subtotalUsd = (float) array_sum(array_column($lines, 'line_usd'));
        $shippingProgress = CheckoutShipping::progress($subtotalUsd);
        $cartCount = $this->cart->totalQuantity();

        $bestSellersCategory = Category::query()->where('slug', 'Best-Sellers')->first();
        $bestSellers = $bestSellersCategory
            ? Product::query()
                ->where('is_active', true)
                ->where('category_id', $bestSellersCategory->id)
                ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->limit(8)
                ->get()
            : collect();

        return view('shop.cart', [
            'title' => 'Your bag — Gemstone',
            'metaDescription' => 'Review your gemstone selections.',
            'lines' => $lines,
            'subtotalUsd' => $subtotalUsd,
            'shippingProgress' => $shippingProgress,
            'cartCount' => $cartCount,
            'bestSellers' => $bestSellers,
            'currency' => $currency,
        ]);
    }

    public function bagFragment(): JsonResponse
    {
        $currency = app(CurrencyService::class);
        $lines = $this->cart->buildLines();
        $subtotalUsd = (float) array_sum(array_column($lines, 'line_usd'));

        return response()->json([
            'ok' => true,
            'cart_count' => $this->cart->totalQuantity(),
            'subtotal_usd' => $subtotalUsd,
            'html' => view('shop.partials.cart-drawer-bag-body', [
                'lines' => $lines,
                'subtotalUsd' => $subtotalUsd,
                'currency' => $currency,
            ])->render(),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
            'unit_price_usd' => 'nullable|numeric|min:0',
        ]);

        $qty = max(1, (int) $request->input('quantity', 1));
        $variant = null;

        if ($request->filled('variant_id')) {
            $variant = ProductVariant::query()
                ->where('id', (int) $request->variant_id)
                ->where('is_active', true)
                ->with('product')
                ->first();
        } elseif ($request->filled('product_id')) {
            $variant = ProductVariant::query()
                ->where('product_id', (int) $request->product_id)
                ->where('is_active', true)
                ->with('product')
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();
        }

        if (! $variant || ! $variant->product || ! $variant->product->is_active) {
            return $this->addErrorResponse($request, 'Product not available.');
        }

        $qty = min($qty, max(0, $variant->stock));
        if ($qty < 1) {
            return $this->addErrorResponse($request, 'This variant is out of stock.');
        }

        $unitPrice = $request->filled('unit_price_usd') ? (float) $request->unit_price_usd : null;
        $this->cart->add($variant->id, $qty, $unitPrice);

        if ($request->boolean('buy_now')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'redirect' => route('shop.checkout'),
                ]);
            }

            return redirect()->route('shop.checkout');
        }

        return $this->addSuccessResponse($request, 'Added to cart.');
    }

    public function addBundle(Request $request)
    {
        $request->validate([
            'parent_product_id' => 'required|integer|exists:products,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.unit_price_usd' => 'nullable|numeric|min:0',
        ]);

        $parent = Product::query()
            ->where('id', (int) $request->parent_product_id)
            ->where('is_active', true)
            ->with(['upsellProducts', 'variants'])
            ->first();

        if (! $parent) {
            return $this->addErrorResponse($request, 'Product not available.');
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

            $variant = null;
            if (! empty($row['variant_id'])) {
                $variant = ProductVariant::query()
                    ->where('id', (int) $row['variant_id'])
                    ->where('product_id', $pid)
                    ->where('is_active', true)
                    ->first();
            }
            $variant ??= ProductVariant::query()
                ->where('product_id', $pid)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            if (! $variant) {
                continue;
            }

            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $qty = min($qty, max(0, $variant->stock));
            if ($qty < 1) {
                continue;
            }

            if ($request->has('items') && array_key_exists('unit_price_usd', $row) && $row['unit_price_usd'] !== null && $row['unit_price_usd'] !== '') {
                $unitPrice = (float) $row['unit_price_usd'];
            } elseif ($pid === $parent->id) {
                $unitPrice = (float) $variant->price_usd;
            } else {
                $base = (float) $variant->price_usd;
                $upsalePct = (float) ($p->pivot->upsale_discount ?? 0);
                $discountPct = (float) ($p->pivot->discount ?? 0);
                $percent = $upsalePct > 0 ? $upsalePct : $discountPct;
                $unitPrice = ProductPricing::afterPercentDiscount($base, $percent > 0 ? $percent : null);
            }

            $this->cart->add($variant->id, $qty, $unitPrice);
            $added++;
        }

        if ($added < 1) {
            return $this->addErrorResponse($request, 'No products were added to your cart.');
        }

        if ($request->expectsJson()) {
            return $this->jsonCartState('Bundle added to cart.');
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

            $variant = ProductVariant::query()
                ->where('id', $id)
                ->where('is_active', true)
                ->with('product')
                ->first();

            if (! $variant || ! $variant->product || ! $variant->product->is_active) {
                $this->cart->remove($id);

                continue;
            }

            $price = $existing['unit_price_usd'] ?? null;
            $this->cart->set($id, min($q, $variant->stock), $price);
        }

        if ($request->expectsJson()) {
            return $this->jsonCartState('Cart updated.');
        }

        return redirect()->route('shop.cart');
    }

    public function remove(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|integer',
        ]);

        $this->cart->remove((int) $request->variant_id);

        if ($request->expectsJson()) {
            return $this->jsonCartState('Item removed.');
        }

        return redirect()->route('shop.cart');
    }

    private function addSuccessResponse(Request $request, string $message): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson()) {
            return $this->jsonCartState($message);
        }

        return back()->with('success', $message);
    }

    private function addErrorResponse(Request $request, string $message): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return back()->with('error', $message);
    }

    private function jsonCartState(string $message): JsonResponse
    {
        $currency = app(CurrencyService::class);
        $lines = $this->cart->buildLines();
        $subtotalUsd = (float) array_sum(array_column($lines, 'line_usd'));
        $shippingProgress = CheckoutShipping::progress($subtotalUsd);

        return response()->json([
            'ok' => true,
            'message' => $message,
            'cart_count' => $this->cart->totalQuantity(),
            'subtotal_usd' => $subtotalUsd,
            'shipping' => $shippingProgress,
            'html' => view('shop.partials.cart-drawer-bag-body', [
                'lines' => $lines,
                'subtotalUsd' => $subtotalUsd,
                'currency' => $currency,
            ])->render(),
            'lines_html' => view('shop.cart._line-items', [
                'lines' => $lines,
                'currency' => $currency,
            ])->render(),
        ]);
    }
}
