<?php

namespace App\Support;

final class ShippingAddressFormatter
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function toText(array $data): string
    {
        $lines = [];

        $name = trim(((string) ($data['first_name'] ?? '')).' '.((string) ($data['last_name'] ?? '')));
        if ($name !== '') {
            $lines[] = $name;
        }

        $company = trim((string) ($data['company'] ?? ''));
        if ($company !== '') {
            $lines[] = $company;
        }

        $line1 = trim((string) ($data['address_line1'] ?? ''));
        if ($line1 !== '') {
            $lines[] = $line1;
        }

        $line2 = trim((string) ($data['address_line2'] ?? ''));
        if ($line2 !== '') {
            $lines[] = $line2;
        }

        $city = trim((string) ($data['city'] ?? ''));
        $postcode = trim((string) ($data['postcode'] ?? ''));
        $cityLine = $city;
        if ($postcode !== '') {
            $cityLine = $cityLine !== '' ? $cityLine.' '.$postcode : $postcode;
        }
        if ($cityLine !== '') {
            $lines[] = $cityLine;
        }

        $country = trim((string) ($data['country'] ?? ''));
        if ($country !== '') {
            $lines[] = CheckoutCountries::label($country);
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '') {
            $lines[] = 'Phone: '.$phone;
        }

        return implode("\n", $lines);
    }
}
