<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;

class CartService
{
    /**
     * @return array<int, array{qty: int, unit_price_usd: float|null, product_id: int}>
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
     * @return array{qty: int, unit_price_usd: float|null, product_id: int}|null
     */
    public function get(int $variantId): ?array
    {
        return $this->all()[$variantId] ?? null;
    }

    public function add(int $variantId, int $quantity, ?float $unitPriceUsd = null): void
    {
        $cart = $this->all();
        $existing = $cart[$variantId] ?? ['qty' => 0, 'unit_price_usd' => null, 'product_id' => 0];
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
        $cart[$variantId] = $entry;
        $this->persist($cart, $variantId);
    }

    public function addProduct(int $productId, int $quantity, ?float $unitPriceUsd = null): void
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

        $this->add($variant->id, $quantity, $unitPriceUsd);
    }

    public function set(int $variantId, int $quantity, ?float $unitPriceUsd = null): void
    {
        $cart = $this->all();
        if ($quantity < 1) {
            unset($cart[$variantId]);
            session(['cart' => $cart]);

            return;
        }

        $existing = $cart[$variantId] ?? ['qty' => 0, 'unit_price_usd' => null, 'product_id' => 0];
        $variant = ProductVariant::query()->where('id', $variantId)->first();
        $entry = [
            'qty' => $quantity,
            'product_id' => $variant?->product_id ?: ($existing['product_id'] ?? 0),
        ];
        $price = $unitPriceUsd ?? ($existing['unit_price_usd'] ?? null);
        if ($price !== null) {
            $entry['unit_price_usd'] = $price;
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
        $lines = [];
        foreach ($this->all() as $variantId => $entry) {
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

            $unit = $this->unitPriceUsd($variant, $entry['unit_price_usd'] ?? null);
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

    /**
     * @param mixed $value
     * @return array{qty: int, unit_price_usd: float|null, product_id: int}
     */
    private function normalizeEntry(mixed $value): array
    {
        if (is_array($value)) {
            $qty = max(0, (int) ($value['qty'] ?? $value['quantity'] ?? 0));
            $price = isset($value['unit_price_usd']) ? (float) $value['unit_price_usd'] : null;
            $productId = (int) ($value['product_id'] ?? 0);

            return [
                'qty' => $qty,
                'unit_price_usd' => $price !== null && $price >= 0 ? $price : null,
                'product_id' => $productId,
            ];
        }

        return [
            'qty' => max(0, (int) $value),
            'unit_price_usd' => null,
            'product_id' => 0,
        ];
    }

    /**
     * @param array<int, array{qty: int, unit_price_usd?: float|null, product_id?: int}> $cart
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
