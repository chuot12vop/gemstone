<?php

namespace App\Support;

use App\Models\Setting;

final class AboutPageSettings
{
    public const SETTING_KEY = 'about_page_content';

    /**
     * @return array{
     *     page_title: string,
     *     page_summary: string,
     *     page_body: string,
     *     home_lede: string,
     *     home_button_label: string,
     *     panels: list<array{title: string, body: string}>
     * }
     */
    public static function defaults(): array
    {
        return [
            'page_title' => '',
            'page_summary' => 'More than jewelry — a bridge between mindful tradition and contemporary life.',
            'page_body' => '<p>We believe meaningful spiritual tools shouldn\'t be rushed; they must be nurtured. We take the time to understand each stone before it is chosen. Every design is crafted with reverence to achieve energetic balance, effortless wearability, and a pure, authentic beauty suited for customers who seek deep, genuine connections.</p>'
                .'<p>Our palette is an ode to daylight: warm cream, champagne gold, and soft neutrals—as elegant and quiet as nature itself.</p>',
            'home_lede' => 'More than jewelry — a bridge between mindful tradition and contemporary life. We take the time to understand each stone before it is chosen, crafting pieces with reverence for energetic balance and everyday wear.',
            'home_button_label' => 'Learn more about us',
            'panels' => [
                [
                    'title' => 'Our philosophy',
                    'body' => '<p>We believe meaningful spiritual tools shouldn\'t be rushed; they must be nurtured. Every design is crafted with reverence to achieve energetic balance, effortless wearability, and a pure, authentic beauty suited for customers who seek deep, genuine connections.</p>',
                ],
                [
                    'title' => 'Materials & craftsmanship',
                    'body' => '<p>Each stone is selected for clarity, color, and intention before it enters our workshop. Hand-finishing and careful stringing ensure pieces that feel as good as they look — made to be worn daily, not kept in a drawer.</p>',
                ],
                [
                    'title' => 'Color & aesthetic',
                    'body' => '<p>Our palette is an ode to daylight: warm cream, champagne gold, and soft neutrals — as elegant and quiet as nature itself.</p>',
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     page_title: string,
     *     page_summary: string,
     *     page_body: string,
     *     home_lede: string,
     *     home_button_label: string,
     *     panels: list<array{title: string, body: string}>
     * }
     */
    public static function resolve(): array
    {
        $data = self::defaults();
        $raw = Setting::query()->where('key', self::SETTING_KEY)->value('value');

        if (! is_string($raw) || $raw === '') {
            return $data;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $data;
        }

        if (isset($decoded['page_title'])) {
            $data['page_title'] = trim((string) $decoded['page_title']);
        }
        if (isset($decoded['page_summary'])) {
            $data['page_summary'] = trim((string) $decoded['page_summary']);
        }
        if (isset($decoded['page_body'])) {
            $data['page_body'] = trim((string) $decoded['page_body']);
        }
        if (isset($decoded['home_lede'])) {
            $data['home_lede'] = trim((string) $decoded['home_lede']);
        }
        if (isset($decoded['home_button_label'])) {
            $data['home_button_label'] = trim((string) $decoded['home_button_label']);
        }
        if (isset($decoded['panels']) && is_array($decoded['panels'])) {
            $data['panels'] = self::normalizePanels($decoded['panels']);
        }

        if ($data['home_button_label'] === '') {
            $data['home_button_label'] = self::defaults()['home_button_label'];
        }

        return $data;
    }

    /**
     * @param  array<int, mixed>  $panels
     * @return list<array{title: string, body: string}>
     */
    public static function normalizePanels(array $panels): array
    {
        $out = [];
        foreach ($panels as $panel) {
            if (! is_array($panel)) {
                continue;
            }
            $title = trim((string) ($panel['title'] ?? ''));
            $body = trim((string) ($panel['body'] ?? ''));
            if ($title === '' && $body === '') {
                continue;
            }
            $out[] = ['title' => $title, 'body' => $body];
        }

        return $out;
    }

    /**
     * @param  array{
     *     page_title?: string,
     *     page_summary?: string,
     *     page_body?: string,
     *     home_lede?: string,
     *     home_button_label?: string,
     *     panels?: list<array{title: string, body: string}>
     * }  $data
     */
    public static function store(array $data): void
    {
        $payload = [
            'page_title' => trim((string) ($data['page_title'] ?? '')),
            'page_summary' => trim((string) ($data['page_summary'] ?? '')),
            'page_body' => trim((string) ($data['page_body'] ?? '')),
            'home_lede' => trim((string) ($data['home_lede'] ?? '')),
            'home_button_label' => trim((string) ($data['home_button_label'] ?? '')),
            'panels' => self::normalizePanels($data['panels'] ?? []),
        ];

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
    }
}
