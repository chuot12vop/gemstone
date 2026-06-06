<?php

namespace App\Support;

use App\Models\Setting;

class CheckoutShipping
{
    public static function freeShippingThresholdUsd(): float
    {
        $val = Setting::query()->where('key', 'free_shipping_threshold_usd')->value('value');
        $n = is_numeric($val) ? (float) $val : 100.0;

        return max(0.01, $n);
    }

    public static function freeShippingMinItems(): int
    {
        $val = Setting::query()->where('key', 'free_shipping_min_items')->value('value');

        return max(0, (int) ($val ?? 0));
    }

    public static function flatFeeUsd(): float
    {
        $val = Setting::query()->where('key', 'shipping_flat_fee_usd')->value('value');
        $n = is_numeric($val) ? (float) $val : 5.99;

        return max(0, $n);
    }

    public static function taxPercent(): float
    {
        $val = Setting::query()->where('key', 'checkout_tax_percent')->value('value');
        $n = is_numeric($val) ? (float) $val : 8.0;

        return max(0, min(100, $n));
    }

    /**
     * @param  array<int, array{quantity?: int}>  $lines
     */
    public static function itemCount(array $lines): int
    {
        return (int) array_sum(array_map(
            static fn (array $line): int => (int) ($line['quantity'] ?? 0),
            $lines,
        ));
    }

    /**
     * @param  array<int, array{quantity?: int}>  $lines
     */
    public static function qualifiesForFreeShipping(float $orderSubtotalUsd, array $lines): bool
    {
        if ($orderSubtotalUsd >= self::freeShippingThresholdUsd()) {
            return true;
        }

        $minItems = self::freeShippingMinItems();
        if ($minItems > 0 && self::itemCount($lines) >= $minItems) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, array{quantity?: int}>  $lines
     */
    public static function shippingFeeUsd(float $orderSubtotalUsd, array $lines): float
    {
        return self::qualifiesForFreeShipping($orderSubtotalUsd, $lines) ? 0.0 : self::flatFeeUsd();
    }

    public static function taxUsd(float $taxableUsd): float
    {
        $percent = self::taxPercent();
        if ($percent <= 0 || $taxableUsd <= 0) {
            return 0.0;
        }

        return round($taxableUsd * ($percent / 100), 2);
    }

    /**
     * @param  array<int, array{quantity?: int}>  $lines
     * @return array{
     *     qualified: bool,
     *     remaining: float,
     *     percent: float,
     *     threshold: float,
     *     min_items: int,
     *     item_count: int
     * }
     */
    public static function progress(float $orderSubtotalUsd, array $lines = []): array
    {
        $threshold = self::freeShippingThresholdUsd();
        $minItems = self::freeShippingMinItems();
        $itemCount = self::itemCount($lines);
        $qualified = self::qualifiesForFreeShipping($orderSubtotalUsd, $lines);
        $remaining = $qualified ? 0.0 : max(0.0, $threshold - $orderSubtotalUsd);
        $percent = $qualified ? 100.0 : min(100.0, ($orderSubtotalUsd / $threshold) * 100.0);

        return [
            'qualified' => $qualified,
            'remaining' => $remaining,
            'percent' => $percent,
            'threshold' => $threshold,
            'min_items' => $minItems,
            'item_count' => $itemCount,
        ];
    }

    /**
     * @param  array<int, array{quantity?: int}>  $lines
     * @return array{
     *     shippingUsd: float,
     *     taxUsd: float,
     *     totalUsd: float,
     *     taxableUsd: float
     * }
     */
    public static function orderAmounts(float $subtotalUsd, float $discountUsd, array $lines): array
    {
        $netSubtotal = max(0, $subtotalUsd - $discountUsd);
        $shippingUsd = self::shippingFeeUsd($subtotalUsd, $lines);
        $taxableUsd = $netSubtotal + $shippingUsd;
        $taxUsd = self::taxUsd($taxableUsd);
        $totalUsd = round($netSubtotal + $shippingUsd + $taxUsd, 2);

        return [
            'shippingUsd' => $shippingUsd,
            'taxUsd' => $taxUsd,
            'totalUsd' => $totalUsd,
            'taxableUsd' => $taxableUsd,
        ];
    }
}
