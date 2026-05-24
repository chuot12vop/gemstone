<?php

namespace App\Support;

use App\Models\Setting;

final class ContactFormSettings
{
    public static function googleScriptUrl(): string
    {
        $stored = trim((string) Setting::query()->where('key', 'contact_google_script_url')->value('value'));
        if ($stored !== '') {
            return $stored;
        }

        return trim((string) config('services.contact.google_script_url', ''));
    }
}
