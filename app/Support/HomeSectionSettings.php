<?php

namespace App\Support;

use App\Models\Setting;

final class HomeSectionSettings
{
    public const SETTING_KEY = 'home_section_styles';

    /** @var list<string> */
    public const SECTION_KEYS = [
        'certificates',
        'collections',
        'bestsellers',
        'new',
        'journal',
        'stories',
        'reviews',
    ];

    /** @var array<string, string> */
    public const SECTION_LABELS = [
        'certificates' => 'As Seen In (Certificates)',
        'collections' => 'Collections',
        'bestsellers' => 'Best Sellers',
        'new' => 'New Arrivals',
        'journal' => 'Journal',
        'stories' => 'Stories',
        'reviews' => 'Feedback',
    ];

    /**
     * @return array<string, array{background_color: string, background_image: string}>
     */
    public static function defaults(): array
    {
        $out = [];
        foreach (self::SECTION_KEYS as $index => $key) {
            $out[$key] = [
                'background_color' => $index % 2 === 0 ? '#ffffff' : '#f6f0e6',
                'background_image' => '',
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array{background_color: string, background_image: string, background_image_url: string}>
     */
    public static function resolve(): array
    {
        $data = self::defaults();
        $raw = Setting::query()->where('key', self::SETTING_KEY)->value('value');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach (self::SECTION_KEYS as $key) {
                    if (! isset($decoded[$key]) || ! is_array($decoded[$key])) {
                        continue;
                    }
                    $row = $decoded[$key];
                    if (isset($row['background_color'])) {
                        $color = self::normalizeColor((string) $row['background_color']);
                        if ($color !== null) {
                            $data[$key]['background_color'] = $color;
                        }
                    }
                    if (isset($row['background_image'])) {
                        $data[$key]['background_image'] = trim((string) $row['background_image']);
                    }
                }
            }
        }

        return self::withResolvedImages($data);
    }

    /**
     * @return array<string, array{background_color: string, background_image: string}>
     */
    public static function resolveForForm(): array
    {
        $resolved = self::resolve();
        $out = [];
        foreach (self::SECTION_KEYS as $key) {
            $out[$key] = [
                'background_color' => $resolved[$key]['background_color'],
                'background_image' => $resolved[$key]['background_image'],
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, array{background_color?: string, background_image?: string}>  $input
     */
    public static function store(array $input): void
    {
        $defaults = self::defaults();
        $payload = [];

        foreach (self::SECTION_KEYS as $key) {
            $row = $input[$key] ?? [];
            $color = self::normalizeColor((string) ($row['background_color'] ?? ''))
                ?? $defaults[$key]['background_color'];
            $image = trim((string) ($row['background_image'] ?? ''));

            $payload[$key] = [
                'background_color' => $color,
                'background_image' => $image,
            ];
        }

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
    }

    /**
     * @param  array{background_color?: string, background_image?: string, background_image_url?: string}  $style
     */
    public static function inlineStyle(array $style): string
    {
        $color = self::normalizeColor((string) ($style['background_color'] ?? '')) ?? '#ffffff';
        $imagePath = trim((string) ($style['background_image'] ?? ''));
        $imageUrl = trim((string) ($style['background_image_url'] ?? ''));

        if ($imageUrl === '' && $imagePath !== '') {
            $imageUrl = PublicAssetUrl::to($imagePath) ?: '';
        }

        $parts = [
            '--home-section-bg: '.e($color),
            'background-color: '.e($color),
        ];

        if ($imageUrl !== '') {
            $parts[] = '--home-section-bg-image: url('.e($imageUrl).')';
            $parts[] = 'background-image: linear-gradient(rgba(255, 253, 249, 0.64), rgba(255, 253, 249, 0.64)), url('.e($imageUrl).')';
        }

        return implode('; ', $parts);
    }

    public static function normalizeColor(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1) {
            if (strlen($value) === 4) {
                return '#'.strtoupper($value[1].$value[1].$value[2].$value[2].$value[3].$value[3]);
            }

            return strtoupper($value);
        }

        return null;
    }

    /**
     * @param  array<string, array{background_color: string, background_image: string}>  $data
     * @return array<string, array{background_color: string, background_image: string, background_image_url: string}>
     */
    private static function withResolvedImages(array $data): array
    {
        foreach ($data as $key => $row) {
            $path = trim((string) ($row['background_image'] ?? ''));
            $data[$key]['background_image_url'] = $path !== ''
                ? (PublicAssetUrl::to($path) ?: '')
                : '';
        }

        return $data;
    }
}
