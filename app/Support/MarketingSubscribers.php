<?php

namespace App\Support;

use App\Models\Setting;

class MarketingSubscribers
{
    public const SETTING_KEY = 'welcome_popup_subscribers';

    public static function subscribe(string $email): void
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $list = self::emails();
        if (in_array($email, $list, true)) {
            return;
        }

        $list[] = $email;
        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => json_encode(array_values($list), JSON_UNESCAPED_UNICODE)]
        );
    }

    /**
     * @return list<string>
     */
    public static function emails(): array
    {
        $raw = Setting::query()->where('key', self::SETTING_KEY)->value('value');
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $item) {
            $e = strtolower(trim((string) $item));
            if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $out[] = $e;
            }
        }

        return array_values(array_unique($out));
    }
}
