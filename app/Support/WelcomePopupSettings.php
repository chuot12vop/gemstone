<?php

namespace App\Support;

use App\Models\Setting;

final class WelcomePopupSettings
{
    public const SETTING_KEY = 'welcome_popup_settings';

    /**
     * @return array{
     *     enabled: bool,
     *     delay_seconds: int,
     *     title: string,
     *     email_placeholder: string,
     *     submit_label: string,
     *     legal_html: string,
     *     success_message: string,
     *     image: string
     * }
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'delay_seconds' => 10,
            'title' => 'Your 15% Welcome Gift Awaits',
            'email_placeholder' => 'Enter your email',
            'submit_label' => 'Reveal My Offer',
            'legal_html' => '<p>By subscribing you agree to receive marketing emails from our store. New subscribers only. Offer applied at checkout when eligible. <a href="/terms-of-service">View Terms</a> &amp; <a href="/privacy-policy">Privacy</a>. You may unsubscribe at any time.</p>',
            'success_message' => 'Thank you — check your inbox for your welcome offer.',
            'image' => '/assets/img/welcome-popup.png',
        ];
    }

    /**
     * @return array{
     *     enabled: bool,
     *     delay_seconds: int,
     *     title: string,
     *     email_placeholder: string,
     *     submit_label: string,
     *     legal_html: string,
     *     success_message: string,
     *     image: string
     * }
     */
    public static function resolve(): array
    {
        $data = self::defaults();
        $raw = Setting::query()->where('key', self::SETTING_KEY)->value('value');

        if (! is_string($raw) || $raw === '') {
            return self::withResolvedImage($data);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return self::withResolvedImage($data);
        }

        if (array_key_exists('enabled', $decoded)) {
            $data['enabled'] = filter_var($decoded['enabled'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($decoded['delay_seconds'])) {
            $data['delay_seconds'] = max(1, min(120, (int) $decoded['delay_seconds']));
        }
        foreach (['title', 'email_placeholder', 'submit_label', 'legal_html', 'success_message'] as $key) {
            if (isset($decoded[$key])) {
                $data[$key] = trim((string) $decoded[$key]);
            }
        }
        if (isset($decoded['image'])) {
            $img = trim((string) $decoded['image']);
            if ($img !== '') {
                $data['image'] = $img;
            }
        }

        return self::withResolvedImage($data);
    }

    /**
     * @param  array{
     *     enabled?: bool,
     *     delay_seconds?: int,
     *     title?: string,
     *     email_placeholder?: string,
     *     submit_label?: string,
     *     legal_html?: string,
     *     success_message?: string,
     *     image?: string
     * }  $data
     */
    public static function store(array $data): void
    {
        $defaults = self::defaults();
        $payload = [
            'enabled' => filter_var($data['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'delay_seconds' => max(1, min(120, (int) ($data['delay_seconds'] ?? $defaults['delay_seconds']))),
            'title' => self::filledOrDefault($data['title'] ?? null, $defaults['title']),
            'email_placeholder' => self::filledOrDefault($data['email_placeholder'] ?? null, $defaults['email_placeholder']),
            'submit_label' => self::filledOrDefault($data['submit_label'] ?? null, $defaults['submit_label']),
            'legal_html' => self::filledOrDefault($data['legal_html'] ?? null, $defaults['legal_html']),
            'success_message' => self::filledOrDefault($data['success_message'] ?? null, $defaults['success_message']),
            'image' => self::filledOrDefault($data['image'] ?? null, $defaults['image']),
        ];

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
    }

    private static function filledOrDefault(?string $value, string $default): string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function withResolvedImage(array $data): array
    {
        $path = (string) ($data['image'] ?? '');
        if ($path !== '' && ! str_starts_with($path, 'http')) {
            $data['image_url'] = PublicAssetUrl::to($path) ?: asset(ltrim($path, '/'));
        } else {
            $data['image_url'] = $path !== '' ? $path : asset('assets/img/welcome-popup.png');
        }

        return $data;
    }
}
