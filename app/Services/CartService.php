<?php

namespace App\Services;

use App\Models\Product;

class CartService
{
    /**
     * @return array<int, array{qty: int, unit_price_usd: float|null}>
     */
    public function all(): array
    {
        $c = session('cart', []);
        if (! is_array($c)) {
            return [];
        }

        $out = [];
        foreach ($c as $k => $v) {
            $pid = (int) $k;
            if ($pid < 1) {
                continue;
            }
            $out[$pid] = $this->normalizeEntry($v);
        }

        return $out;
    }

    public function totalQuantity(): int
    {
        return (int) array_sum(array_column($this->all(), 'qty'));
    }

    /**
     * @return array{qty: int, unit_price_usd: float|null}|null
     */
    public function get(int $productId): ?array
    {
        return $this->all()[$productId] ?? null;
    }

    public function add(int $productId, int $quantity, ?float $unitPriceUsd = null): void
    {
        $cart = $this->all();
        $existing = $cart[$productId] ?? ['qty' => 0, 'unit_price_usd' => null];
        $entry = ['qty' => $existing['qty'] + $quantity];
        if ($unitPriceUsd !== null) {
            $entry['unit_price_usd'] = $unitPriceUsd;
        }
        $cart[$productId] = $entry;
        $this->persist($cart, $productId);
    }

    public function set(int $productId, int $quantity, ?float $unitPriceUsd = null): void
    {
        $cart = $this->all();
        if ($quantity < 1) {
            unset($cart[$productId]);
            session(['cart' => $cart]);

            return;
        }

        $existing = $cart[$productId] ?? ['qty' => 0, 'unit_price_usd' => null];
        $entry = ['qty' => $quantity];
        $price = $unitPriceUsd ?? ($existing['unit_price_usd'] ?? null);
        if ($price !== null) {
            $entry['unit_price_usd'] = $price;
        }
        $cart[$productId] = $entry;
        session(['cart' => $cart]);
    }

    public function remove(int $productId): void
    {
        $cart = $this->all();
        unset($cart[$productId]);
        session(['cart' => $cart]);
    }

    public function clear(): void
    {
        session()->forget('cart');
    }

    public function unitPriceUsd(Product $product, ?float $stored = null): float
    {
        if ($stored !== null && $stored >= 0) {
            return (float) $stored;
        }

        return (float) $product->price_usd;
    }

    /**
     * @return array<int, array{product: Product, quantity: int, unit_price_usd: float, line_usd: float}>
     */
    public function buildLines(): array
    {
        $lines = [];
        foreach ($this->all() as $pid => $entry) {
            $p = Product::query()->where('id', $pid)->where('is_active', true)->first();
            if (! $p) {
                continue;
            }
            $q = min((int) $entry['qty'], max(0, $p->stock));
            if ($q < 1) {
                continue;
            }
            $unit = $this->unitPriceUsd($p, $entry['unit_price_usd'] ?? null);
            $lines[] = [
                'product' => $p,
                'quantity' => $q,
                'unit_price_usd' => $unit,
                'line_usd' => $unit * $q,
            ];
        }

        return $lines;
    }

    /**
     * @param mixed $value
     * @return array{qty: int, unit_price_usd: float|null}
     */
    private function normalizeEntry(mixed $value): array
    {
        if (is_array($value)) {
            $qty = max(0, (int) ($value['qty'] ?? $value['quantity'] ?? 0));
            $price = isset($value['unit_price_usd']) ? (float) $value['unit_price_usd'] : null;

            return [
                'qty' => $qty,
                'unit_price_usd' => $price !== null && $price >= 0 ? $price : null,
            ];
        }

        return [
            'qty' => max(0, (int) $value),
            'unit_price_usd' => null,
        ];
    }

    /**
     * @param array<int, array{qty: int, unit_price_usd?: float|null}> $cart
     */
    private function persist(array $cart, int $productId): void
    {
        $p = Product::query()->where('id', $productId)->where('is_active', true)->first();
        if ($p) {
            $cart[$productId]['qty'] = min((int) $cart[$productId]['qty'], max(0, $p->stock));
        }
        if ($cart[$productId]['qty'] < 1) {
            unset($cart[$productId]);
        }
        session(['cart' => $cart]);
    }
}
