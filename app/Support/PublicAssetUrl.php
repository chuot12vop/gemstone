<?php

namespace App\Support;

use Illuminate\Support\Str;

class PublicAssetUrl
{
    /**
     * Build an absolute URL for a path stored under the public disk (e.g. /storage/settings/logo/…).
     * Uses filesystems.disks.public.url (APP_URL + /storage).
     */
    public static function to(?string $path): string
    {
        if ($path === null) {
            return '';
        }

        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        if (Str::startsWith($path, '/storage/')) {
            $relative = ltrim(Str::after($path, '/storage'), '/');
            if ($relative === '') {
                return '';
            }
            $base = rtrim((string) config('filesystems.disks.public.url'), '/');

            return $base !== '' ? $base.'/'.$relative : url('/storage/'.$relative);
        }

        return url($path);
    }
}
