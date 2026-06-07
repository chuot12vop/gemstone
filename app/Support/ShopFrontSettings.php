<?php

namespace App\Support;

use App\Models\Setting;

class ShopFrontSettings
{
    /**
     * @return array{
     *     whatsapp_phone: string,
     *     whatsapp_url: string|null,
     *     footer_background: string,
     *     home_news_ticker: list<string>
     * }
     */
    public static function resolve(): array
    {
        $keys = [
            'contact_whatsapp_phone',
            'footer_background',
            'home_news_ticker',
        ];

        $stored = Setting::query()
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->toArray();

        $phone = trim((string) ($stored['contact_whatsapp_phone'] ?? ''));

        $tickerRaw = trim((string) ($stored['home_news_ticker'] ?? ''));
        $tickerLines = $tickerRaw === ''
            ? []
            : array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $tickerRaw) ?: [])));

        return [
            'whatsapp_phone' => $phone,
            'whatsapp_url' => self::whatsappUrl($phone),
            'footer_background' => PublicAssetUrl::to((string) ($stored['footer_background'] ?? '')),
            'home_news_ticker' => $tickerLines,
        ];
    }

    public static function whatsappUrl(string $phone, string $message = ''): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        $url = 'https://wa.me/'.$digits;
        if ($message !== '') {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }
}
