<?php

namespace App\Support;

final class ProductDetailPolicies
{
    /**
     * @return list<array{icon: string, text: string}>
     */
    public static function rows(): array
    {
        return [
            ['icon' => 'shipping', 'text' => 'Free US Shipping over $120 & 30-Day Easy Returns'],
            ['icon' => 'locker', 'text' => '18K Gold Vermeil / 925 Sterling Silver'],
            ['icon' => 'warranty', 'text' => '100% Waterproof & Tarnish-Free Guarantee'],
            // ['icon' => 'returns', 'text' => '60-Day Easy Returns'],
            ['icon' => 'gift', 'text' => 'Gift-wrap available at checkout'],
        ];
    }
}
