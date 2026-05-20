<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AboutPageSettings;

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
            'return_policy' => '',
            'terms_of_service' => '',
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
        $about = AboutPageSettings::resolve();
        $siteName = $settings['site_name'] ?: config('app.name');

        return view('shop.about', [
            'title' => 'About '.$siteName.' — Heritage & intention',
            'metaDescription' => $about['page_summary'] ?: 'Learn how we bridge ancient feng shui wisdom with modern craftsmanship.',
            'about' => $about,
            'siteName' => $siteName,
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

    public function returnPolicy()
    {
        return $this->renderPolicyPage(
            'Return policy',
            'return_policy',
            'Conditions for returns, exchanges, and refunds.'
        );
    }

    public function termsOfService()
    {
        return $this->renderPolicyPage(
            'Terms of service',
            'terms_of_service',
            'Terms that apply when you use our store.'
        );
    }

    public function retailPolicy()
    {
        return redirect()->route('shop.policy.return', [], 301);
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
