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
            ['icon' => 'shipping', 'text' => 'Standard Shipping $5.99, FREE on orders over $100'],
            ['icon' => 'locker', 'text' => 'Fast & Secure Shipping via UPS/USPS'],
            ['icon' => 'warranty', 'text' => '2-Year Quality Warranty'],
            ['icon' => 'returns', 'text' => '60-Day Easy Returns'],
            ['icon' => 'gift', 'text' => 'Gift-wrap available at checkout'],
        ];
    }
}
