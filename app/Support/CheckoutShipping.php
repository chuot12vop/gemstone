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

    /**
     * @return array{
     *     qualified: bool,
     *     remaining: float,
     *     percent: float,
     *     threshold: float
     * }
     */
    public static function progress(float $orderSubtotalUsd): array
    {
        $threshold = self::freeShippingThresholdUsd();
        $qualified = $orderSubtotalUsd >= $threshold;
        $remaining = $qualified ? 0.0 : max(0.0, $threshold - $orderSubtotalUsd);
        $percent = $qualified ? 100.0 : min(100.0, ($orderSubtotalUsd / $threshold) * 100.0);

        return [
            'qualified' => $qualified,
            'remaining' => $remaining,
            'percent' => $percent,
            'threshold' => $threshold,
        ];
    }
}
