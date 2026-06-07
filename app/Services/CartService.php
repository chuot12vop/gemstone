<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ProductPricing;

class CartService
{
    /**
     * @return array<int, array{qty: int, unit_price_usd: float|null, product_id: int, upsell_parent_product_id: int|null}>
     */
    public function all(): array
    {
        $c = session('cart', []);
        if (! is_array($c)) {
            return [];
        }

        $out = [];
        foreach ($c as $k => $v) {
            $variantId = (int) $k;
            if ($variantId < 1) {
                continue;
            }
            $out[$variantId] = $this->normalizeEntry($v);
        }

        return $out;
    }

    public function totalQuantity(): int
    {
        return (int) array_sum(array_column($this->all(), 'qty'));
    }

    /**
     * @return array{qty: int, unit_price_usd: float|null, product_id: int, upsell_parent_product_id: int|null}|null
     */
    public function get(int $variantId): ?array
    {
        return $this->all()[$variantId] ?? null;
    }

    public function add(int $variantId, int $quantity, ?float $unitPriceUsd = null, ?int $upsellParentProductId = null): void
    {
        $cart = $this->all();
        $existing = $cart[$variantId] ?? ['qty' => 0, 'unit_price_usd' => null, 'product_id' => 0, 'upsell_parent_product_id' => null];
        $variant = ProductVariant::query()
            ->where('id', $variantId)
            ->where('is_active', true)
            ->with('product')
            ->first();

        if (! $variant || ! $variant->product || ! $variant->product->is_active) {
            return;
        }

        $entry = [
            'qty' => $existing['qty'] + $quantity,
            'product_id' => $variant->product_id,
        ];
        if ($unitPriceUsd !== null) {
            $entry['unit_price_usd'] = $unitPriceUsd;
        } elseif ($existing['unit_price_usd'] !== null) {
            $entry['unit_price_usd'] = $existing['unit_price_usd'];
        }
        $parentId = $upsellParentProductId ?? ($existing['upsell_parent_product_id'] ?? null);
        if ($parentId !== null && $parentId > 0) {
            $entry['upsell_parent_product_id'] = $parentId;
        }
        $cart[$variantId] = $entry;
        $this->persist($cart, $variantId);
    }

    public function addProduct(int $productId, int $quantity, ?float $unitPriceUsd = null, ?int $upsellParentProductId = null): void
    {
        $variant = ProductVariant::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $variant) {
            return;
        }

        $this->add($variant->id, $quantity, $unitPriceUsd, $upsellParentProductId);
    }

    public function set(int $variantId, int $quantity, ?float $unitPriceUsd = null): void
    {
        $cart = $this->all();
        if ($quantity < 1) {
            unset($cart[$variantId]);
            session(['cart' => $cart]);

            return;
        }

        $existing = $cart[$variantId] ?? ['qty' => 0, 'unit_price_usd' => null, 'product_id' => 0, 'upsell_parent_product_id' => null];
        $variant = ProductVariant::query()->where('id', $variantId)->first();
        $entry = [
            'qty' => $quantity,
            'product_id' => $variant?->product_id ?: ($existing['product_id'] ?? 0),
        ];
        $price = $unitPriceUsd ?? ($existing['unit_price_usd'] ?? null);
        if ($price !== null) {
            $entry['unit_price_usd'] = $price;
        }
        if (! empty($existing['upsell_parent_product_id'])) {
            $entry['upsell_parent_product_id'] = (int) $existing['upsell_parent_product_id'];
        }
        $cart[$variantId] = $entry;
        session(['cart' => $cart]);
    }

    public function remove(int $variantId): void
    {
        $cart = $this->all();
        unset($cart[$variantId]);
        session(['cart' => $cart]);
    }

    public function clear(): void
    {
        session()->forget('cart');
    }

    public function unitPriceUsd(ProductVariant $variant, ?float $stored = null): float
    {
        if ($stored !== null && $stored >= 0) {
            return (float) $stored;
        }

        return (float) $variant->price_usd;
    }

    /**
     * @return array<int, array{
     *     product: Product,
     *     variant: ProductVariant,
     *     quantity: int,
     *     unit_price_usd: float,
     *     line_usd: float,
     *     variant_label: string
     * }>
     */
    public function buildLines(): array
    {
        $cart = $this->all();
        $productIdsInCart = array_values(array_unique(array_filter(array_column($cart, 'product_id'))));
        $lines = [];

        foreach ($cart as $variantId => $entry) {
            $variant = ProductVariant::query()
                ->where('id', $variantId)
                ->where('is_active', true)
                ->with(['product.category'])
                ->first();

            if (! $variant || ! $variant->product || ! $variant->product->is_active) {
                continue;
            }

            $q = min((int) $entry['qty'], max(0, $variant->stock));
            if ($q < 1) {
                continue;
            }

            $parentProductId = (int) ($entry['upsell_parent_product_id'] ?? 0);
            if ($parentProductId > 0) {
                $unit = in_array($parentProductId, $productIdsInCart, true)
                    ? $this->upsellDiscountedPrice($parentProductId, $variant->product_id, $variant)
                    : (float) $variant->price_usd;
            } else {
                $unit = $this->unitPriceUsd($variant, $entry['unit_price_usd'] ?? null);
            }

            $lines[] = [
                'product' => $variant->product,
                'variant' => $variant,
                'quantity' => $q,
                'unit_price_usd' => $unit,
                'line_usd' => $unit * $q,
                'variant_label' => $variant->label(),
            ];
        }

        return $lines;
    }

    private function upsellDiscountedPrice(int $parentProductId, int $upsellProductId, ProductVariant $variant): float
    {
        $base = (float) $variant->price_usd;
        $parent = Product::query()
            ->where('id', $parentProductId)
            ->with(['upsellProducts' => fn ($q) => $q->where('products.id', $upsellProductId)])
            ->first();

        if (! $parent || $parent->upsellProducts->isEmpty()) {
            return $base;
        }

        $upsell = $parent->upsellProducts->first();
        $upsalePct = (float) ($upsell->pivot->upsale_discount ?? 0);
        $discountPct = (float) ($upsell->pivot->discount ?? 0);
        $percent = $upsalePct > 0 ? $upsalePct : $discountPct;

        return ProductPricing::afterPercentDiscount($base, $percent > 0 ? $percent : null);
    }

    /**
     * @param mixed $value
     * @return array{qty: int, unit_price_usd: float|null, product_id: int, upsell_parent_product_id: int|null}
     */
    private function normalizeEntry(mixed $value): array
    {
        if (is_array($value)) {
            $qty = max(0, (int) ($value['qty'] ?? $value['quantity'] ?? 0));
            $price = isset($value['unit_price_usd']) ? (float) $value['unit_price_usd'] : null;
            $productId = (int) ($value['product_id'] ?? 0);
            $parentId = (int) ($value['upsell_parent_product_id'] ?? 0);

            $entry = [
                'qty' => $qty,
                'unit_price_usd' => $price !== null && $price >= 0 ? $price : null,
                'product_id' => $productId,
            ];
            if ($parentId > 0) {
                $entry['upsell_parent_product_id'] = $parentId;
            }

            return $entry;
        }

        return [
            'qty' => max(0, (int) $value),
            'unit_price_usd' => null,
            'product_id' => 0,
            'upsell_parent_product_id' => null,
        ];
    }

    /**
     * @param array<int, array{qty: int, unit_price_usd?: float|null, product_id?: int, upsell_parent_product_id?: int|null}> $cart
     */
    private function persist(array $cart, int $variantId): void
    {
        $variant = ProductVariant::query()
            ->where('id', $variantId)
            ->where('is_active', true)
            ->first();

        if ($variant) {
            $cart[$variantId]['product_id'] = $variant->product_id;
            $cart[$variantId]['qty'] = min((int) $cart[$variantId]['qty'], max(0, $variant->stock));
        }

        if (($cart[$variantId]['qty'] ?? 0) < 1) {
            unset($cart[$variantId]);
        }

        session(['cart' => $cart]);
    }
}
