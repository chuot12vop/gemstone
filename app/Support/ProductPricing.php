<?php

namespace App\Support;

final class ProductPricing
{
    public static function afterPercentDiscount(float $baseUsd, ?float $percent): float
    {
        $percent = $percent === null ? 0.0 : (float) $percent;
        if ($percent <= 0) {
            return round($baseUsd, 2);
        }

        return round($baseUsd * (1 - min(100, $percent) / 100), 2);
    }

    /**
     * @return array{base: float, display: float, compare: ?float, on_sale: bool}
     */
    public static function display(float $baseUsd, ?float $compareAtUsd, ?float $productDiscountPercent): array
    {
        $baseUsd = round($baseUsd, 2);

        if ($compareAtUsd !== null && $compareAtUsd > $baseUsd + 0.001) {
            return [
                'base' => $baseUsd,
                'display' => $baseUsd,
                'compare' => round($compareAtUsd, 2),
                'on_sale' => true,
            ];
        }

        $discountPercent = $productDiscountPercent === null ? 0.0 : (float) $productDiscountPercent;
        if ($discountPercent > 0) {
            return [
                'base' => $baseUsd,
                'display' => self::afterPercentDiscount($baseUsd, $discountPercent),
                'compare' => $baseUsd,
                'on_sale' => true,
            ];
        }

        return [
            'base' => $baseUsd,
            'display' => $baseUsd,
            'compare' => null,
            'on_sale' => false,
        ];
    }
}
