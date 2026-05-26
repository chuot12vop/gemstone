<?php

namespace App\Support;

final class CheckoutCountries
{
    /** @return array<string, string> ISO-ish code => English label */
    public static function options(): array
    {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'IE' => 'Ireland',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'SG' => 'Singapore',
            'HK' => 'Hong Kong',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'MX' => 'Mexico',
            'VN' => 'Vietnam',
        ];
    }

    public static function label(string $code): string
    {
        return self::options()[$code] ?? $code;
    }

    public static function defaultCode(): string
    {
        return 'US';
    }
}
