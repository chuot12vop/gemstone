<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class CustomThemeStylesheet
{
    public const PATH = 'custom-theme.css';

    public const MAX_BYTES = 524288;

    public const LOAD_ORDER = ['desktop', 'tablet', 'mobile'];

    public const VIEWPORTS = [
        'desktop' => [
            'label' => 'Desktop',
            'path' => self::PATH,
            'media' => null,
            'width' => 1440,
            'height' => 900,
        ],
        'mobile' => [
            'label' => 'Mobile',
            'path' => 'custom-theme-mobile.css',
            'media' => '(max-width: 767px)',
            'width' => 430,
            'height' => 932,
        ],
        'tablet' => [
            'label' => 'Tablet',
            'path' => 'custom-theme-tablet.css',
            'media' => '(min-width: 768px) and (max-width: 1024px)',
            'width' => 1024,
            'height' => 1366,
        ],
    ];

    public static function config(string $viewport): ?array
    {
        return self::VIEWPORTS[$viewport] ?? null;
    }

    public static function contents(string $viewport = 'desktop'): string
    {
        $config = self::config($viewport);
        if ($config === null || ! Storage::disk('public')->exists($config['path'])) {
            return '';
        }

        return (string) Storage::disk('public')->get($config['path']);
    }

    public static function versionedUrl(string $viewport = 'desktop'): ?string
    {
        $config = self::config($viewport);
        if ($config === null) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($config['path'])) {
            return null;
        }

        return asset('storage/'.$config['path']).'?v='.$disk->lastModified($config['path']);
    }

    public static function editorData(): array
    {
        $viewports = [];
        foreach (self::VIEWPORTS as $key => $config) {
            $viewports[$key] = array_merge($config, [
                'key' => $key,
                'contents' => self::contents($key),
            ]);
        }

        return $viewports;
    }
}
