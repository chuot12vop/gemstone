<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PaymentLogoSettings
{
    public const SETTING_KEY = 'payment_logos';

    private const STORAGE_DIR = 'settings/payment-logos';

    /** @var list<string> */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    /**
     * @return list<array{path: string, label: string, src: string}>
     */
    public static function resolveForForm(): array
    {
        self::importLegacyIfEmpty();

        return collect(self::decodedItems())
            ->map(function (array $item) {
                $path = trim((string) ($item['path'] ?? ''));
                if ($path === '') {
                    return null;
                }

                return [
                    'path' => $path,
                    'label' => trim((string) ($item['label'] ?? '')),
                    'src' => PublicAssetUrl::to($path) ?: $path,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Find an admin-configured logo for a checkout payment gateway.
     *
     * @return array{src: string, label: string}|null
     */
    public static function forGateway(string $code, string $label = ''): ?array
    {
        self::importLegacyIfEmpty();

        $needles = self::gatewayMatchNeedles($code, $label);

        foreach (self::decodedItems() as $item) {
            if (! self::matchesGateway($item, $needles)) {
                continue;
            }

            $path = trim((string) ($item['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $logoLabel = trim((string) ($item['label'] ?? ''));
            if ($logoLabel === '') {
                $logoLabel = self::labelFromPath($path);
            }

            return [
                'src' => PublicAssetUrl::to($path) ?: $path,
                'label' => $logoLabel,
            ];
        }

        return null;
    }

    /**
     * @return list<array{src: string, label: string}>
     */
    public static function forDisplay(?string $excludePublicPath = null): array
    {
        self::importLegacyIfEmpty();

        $exclude = self::normalizePublicPath($excludePublicPath);

        return collect(self::decodedItems())
            ->map(function (array $item) {
                $path = trim((string) ($item['path'] ?? ''));
                if ($path === '') {
                    return null;
                }

                $label = trim((string) ($item['label'] ?? ''));
                if ($label === '') {
                    $label = self::labelFromPath($path);
                }

                return [
                    'path' => $path,
                    'src' => PublicAssetUrl::to($path) ?: $path,
                    'label' => $label,
                ];
            })
            ->filter()
            ->filter(function (array $logo) use ($exclude) {
                if ($exclude === '') {
                    return true;
                }

                return self::normalizePublicPath($logo['path']) !== $exclude;
            })
            ->map(fn (array $logo) => [
                'src' => $logo['src'],
                'label' => $logo['label'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{path?: string, label?: string, image?: UploadedFile|null}>  $rows
     * @param  list<UploadedFile>  $newFiles
     */
    public static function saveFromAdmin(array $rows, array $newFiles = []): void
    {
        $oldPaths = collect(self::decodedItems())
            ->map(fn (array $item) => trim((string) ($item['path'] ?? '')))
            ->filter()
            ->values()
            ->all();

        $saved = [];

        foreach ($rows as $row) {
            $path = trim((string) ($row['path'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            /** @var UploadedFile|null $file */
            $file = $row['image'] ?? null;

            if ($file instanceof UploadedFile) {
                $stored = self::storeImage($file);
                if ($stored !== null) {
                    if ($path !== '') {
                        self::deletePublicPath($path);
                    }
                    $path = $stored;
                }
            }

            if ($path === '') {
                continue;
            }

            if ($label === '') {
                $label = self::labelFromPath($path);
            }

            $saved[] = [
                'path' => $path,
                'label' => $label,
            ];
        }

        foreach ($newFiles as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = self::storeImage($file);
            if ($path === null) {
                continue;
            }

            $saved[] = [
                'path' => $path,
                'label' => self::labelFromFilename($file->getClientOriginalName()),
            ];
        }

        $newPaths = collect($saved)->pluck('path')->all();
        foreach (array_diff($oldPaths, $newPaths) as $removed) {
            self::deletePublicPath($removed);
        }

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => json_encode($saved, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
    }

    private static function importLegacyIfEmpty(): void
    {
        if (self::decodedItems() !== []) {
            return;
        }

        $legacy = PaymentMethodLogos::legacyFromDirectory();
        if ($legacy === []) {
            return;
        }

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => json_encode($legacy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
    }

    /**
     * @return list<array{path: string, label: string}>
     */
    private static function decodedItems(): array
    {
        $raw = Setting::query()->where('key', self::SETTING_KEY)->value('value');
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $path = trim((string) ($item['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            if (! str_starts_with($path, '/storage/')) {
                $path = '/storage/'.ltrim($path, '/');
            }

            $items[] = [
                'path' => $path,
                'label' => trim((string) ($item['label'] ?? '')),
            ];
        }

        return $items;
    }

    private static function storeImage(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            $extension = 'jpg';
        }

        $fileName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs(self::STORAGE_DIR, $fileName, 'public');

        if (! is_string($path) || $path === '') {
            return null;
        }

        return '/storage/'.$path;
    }

    private static function deletePublicPath(string $path): void
    {
        $path = trim($path);
        if ($path === '') {
            return;
        }

        $relativePath = str_starts_with($path, '/storage/')
            ? Str::after($path, '/storage/')
            : ltrim($path, '/');

        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }

    private static function labelFromPath(string $path): string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);

        return self::labelFromFilename($filename);
    }

    /**
     * @return list<string>
     */
    private static function gatewayMatchNeedles(string $code, string $label): array
    {
        $code = strtolower(trim($code));
        $needles = [];

        if ($code !== '') {
            $needles[] = $code;
            $needles[] = str_replace('_', '', $code);
            $needles[] = str_replace('_', ' ', $code);
        }

        $aliases = [
            'paypal' => ['paypal'],
            'apple_pay' => ['apple pay', 'applepay', 'apple'],
            'venmo' => ['venmo'],
            'cashapp' => ['cash app', 'cashapp', 'cash-app'],
            'zelle' => ['zelle'],
        ];

        if (isset($aliases[$code])) {
            $needles = array_merge($needles, $aliases[$code]);
        }

        $label = strtolower(trim($label));
        if ($label !== '') {
            $needles[] = $label;
        }

        return array_values(array_unique(array_filter($needles)));
    }

    /**
     * @param  array{path?: string, label?: string}  $item
     * @param  list<string>  $needles
     */
    private static function matchesGateway(array $item, array $needles): bool
    {
        $path = strtolower((string) ($item['path'] ?? ''));
        $logoLabel = strtolower((string) ($item['label'] ?? ''));
        $filename = strtolower(pathinfo($path, PATHINFO_FILENAME));

        foreach ($needles as $needle) {
            if ($needle === '') {
                continue;
            }

            $compactNeedle = str_replace([' ', '_', '-', '.'], '', $needle);
            $haystacks = [
                $path,
                $logoLabel,
                $filename,
                str_replace([' ', '_', '-', '.'], '', $logoLabel),
                str_replace([' ', '_', '-', '.'], '', $filename),
            ];

            foreach ($haystacks as $haystack) {
                if ($haystack === '') {
                    continue;
                }

                if (str_contains($haystack, $needle)) {
                    return true;
                }

                if ($compactNeedle !== '' && str_contains($haystack, $compactNeedle)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function labelFromFilename(string $filename): string
    {
        $filename = pathinfo($filename, PATHINFO_FILENAME);
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $filename)) {
            return 'Payment method';
        }

        return Str::title(str_replace(['-', '_', '.'], ' ', $filename));
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

        if (! str_starts_with($path, '/storage/')) {
            $path = '/storage/'.ltrim($path, '/');
        }

        return rtrim($path, '/');
    }
}
