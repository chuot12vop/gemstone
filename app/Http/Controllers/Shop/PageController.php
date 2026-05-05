<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class PageController extends Controller
{
    /**
     * @return array<string, string>
     */
    private function settingsMap(): array
    {
        $defaults = [
            'site_name' => config('app.name'),
            'security_policy' => '',
            'privacy_policy' => '',
            'retail_policy' => '',
        ];

        $stored = Setting::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->toArray();

        foreach ($stored as $key => $value) {
            if (array_key_exists($key, $defaults) && $value !== null) {
                $defaults[$key] = (string) $value;
            }
        }

        return $defaults;
    }

    public function about()
    {
        $settings = $this->settingsMap();

        return view('shop.about', [
            'title' => 'About '.$settings['site_name'].' — Heritage & intention',
            'metaDescription' => 'Learn how we bridge ancient feng shui wisdom with modern craftsmanship for US customers.',
        ]);
    }

    public function contact()
    {
        $settings = $this->settingsMap();

        return view('shop.contact', [
            'title' => 'Contact — '.$settings['site_name'].' support',
            'metaDescription' => 'Reach our team for orders and gemstone questions.',
        ]);
    }

    public function securityPolicy()
    {
        return $this->renderPolicyPage(
            'Security policy',
            'security_policy',
            'How we keep your information and transactions secure.'
        );
    }

    public function privacyPolicy()
    {
        return $this->renderPolicyPage(
            'Privacy policy',
            'privacy_policy',
            'How we collect, use and protect your personal data.'
        );
    }

    public function retailPolicy()
    {
        return $this->renderPolicyPage(
            'Retail policy',
            'retail_policy',
            'Order, shipping, return and retail conditions.'
        );
    }

    private function renderPolicyPage(string $heading, string $settingKey, string $fallback): \Illuminate\Contracts\View\View
    {
        $settings = $this->settingsMap();
        $siteName = $settings['site_name'] ?: config('app.name');
        $content = trim($settings[$settingKey] ?? '');

        if ($content === '') {
            $content = $fallback;
        }

        return view('shop.policy', [
            'title' => $heading.' — '.$siteName,
            'metaDescription' => $heading.' for '.$siteName.'.',
            'heading' => $heading,
            'content' => $content,
        ]);
    }
}
