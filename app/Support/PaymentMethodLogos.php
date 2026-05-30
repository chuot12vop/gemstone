<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentMethodLogos
{
    private const DIRECTORY = 'settings/logo';

    /** @var list<string> */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    /**
     * @return list<array{src: string, label: string}>
     */
    public static function all(?string $excludePublicPath = null): array
    {
        $disk = Storage::disk('public');

        if (! $disk->exists(self::DIRECTORY)) {
            return [];
        }

        $exclude = self::normalizePublicPath($excludePublicPath);

        return collect($disk->files(self::DIRECTORY))
            ->filter(function (string $path) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                return in_array($ext, self::IMAGE_EXTENSIONS, true);
            })
            ->sort()
            ->map(function (string $path) {
                $publicPath = '/storage/'.$path;
                $filename = pathinfo($path, PATHINFO_FILENAME);

                return [
                    'src' => PublicAssetUrl::to($publicPath),
                    'label' => self::labelFromFilename($filename),
                    'public_path' => $publicPath,
                ];
            })
            ->filter(function (array $logo) use ($exclude) {
                if ($exclude === '') {
                    return true;
                }

                return self::normalizePublicPath($logo['public_path']) !== $exclude;
            })
            ->map(fn (array $logo) => [
                'src' => $logo['src'],
                'label' => $logo['label'],
            ])
            ->values()
            ->all();
    }

    private static function normalizePublicPath(?string $path): string
    {
        if ($path === null) {
            return '';
        }

        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            $parsed = parse_url($path, PHP_URL_PATH);

            return is_string($parsed) ? self::normalizePublicPath($parsed) : '';
        }

        if (! Str::startsWith($path, '/storage/')) {
            $path = '/storage/'.ltrim($path, '/');
        }

        return rtrim($path, '/');
    }

    private static function labelFromFilename(string $filename): string
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $filename)) {
            return 'Payment method';
        }

        return Str::title(str_replace(['-', '_'], ' ', $filename));
    }
}
