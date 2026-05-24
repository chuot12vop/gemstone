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
}
