<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\WelcomePopupSettings;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingAdminController extends Controller
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    public function index()
    {
        return view('admin.settings.index', [
            'title' => 'System',
            'breadcrumbs' => [
                ['label' => 'System'],
            ],
            'settings' => $this->getSettingsMap(),
            'welcomePopup' => WelcomePopupSettings::resolve(),
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:190',
            'security_policy' => 'nullable|string',
            'privacy_policy' => 'nullable|string',
            'return_policy' => 'nullable|string',
            'terms_of_service' => 'nullable|string',
            'site_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'footer_background' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
            'contact_whatsapp_phone' => 'nullable|string|max:60',
            'contact_google_script_url' => 'nullable|url|max:500',
            'home_news_ticker' => 'nullable|string|max:8000',
            'welcome_popup_enabled' => 'nullable|boolean',
            'welcome_popup_delay_seconds' => 'nullable|integer|min:1|max:120',
            'welcome_popup_title' => 'nullable|string|max:300',
            'welcome_popup_email_placeholder' => 'nullable|string|max:120',
            'welcome_popup_submit_label' => 'nullable|string|max:120',
            'welcome_popup_legal_html' => 'nullable|string|max:8000',
            'welcome_popup_success_message' => 'nullable|string|max:500',
            'welcome_popup_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
        ]);

        $settings = $this->getSettingsMap();

        $logoPath = $this->storeImage($request->file('site_logo'), 'settings/logo');
        if ($logoPath !== null) {
            $this->deletePublicPath($settings['site_logo'] ?? null);
            $settings['site_logo'] = $logoPath;
        }

        $footerBgPath = $this->storeImage($request->file('footer_background'), 'settings/footer');
        if ($footerBgPath !== null) {
            $this->deletePublicPath($settings['footer_background'] ?? null);
            $settings['footer_background'] = $footerBgPath;
        }

        $settings['site_name'] = $validated['site_name'];
        $settings['contact_whatsapp_phone'] = trim((string) ($validated['contact_whatsapp_phone'] ?? ''));
        $settings['contact_google_script_url'] = trim((string) ($validated['contact_google_script_url'] ?? ''));
        $settings['home_news_ticker'] = trim((string) ($validated['home_news_ticker'] ?? ''));
        $settings['security_policy'] = trim((string) ($validated['security_policy'] ?? ''));
        $settings['privacy_policy'] = trim((string) ($validated['privacy_policy'] ?? ''));
        $settings['return_policy'] = trim((string) ($validated['return_policy'] ?? ''));
        $settings['terms_of_service'] = trim((string) ($validated['terms_of_service'] ?? ''));

        $welcome = WelcomePopupSettings::resolve();
        $welcomeImagePath = $this->storeImage($request->file('welcome_popup_image'), 'settings/welcome-popup');
        if ($welcomeImagePath !== null) {
            $oldImage = (string) ($welcome['image'] ?? '');
            if (str_starts_with($oldImage, self::PUBLIC_STORAGE_PREFIX)) {
                $this->deletePublicPath($oldImage);
            }
            $welcome['image'] = $welcomeImagePath;
        }

        WelcomePopupSettings::store([
            'enabled' => $request->boolean('welcome_popup_enabled'),
            'delay_seconds' => (int) ($validated['welcome_popup_delay_seconds'] ?? $welcome['delay_seconds']),
            'title' => $validated['welcome_popup_title'] ?? $welcome['title'],
            'email_placeholder' => $validated['welcome_popup_email_placeholder'] ?? $welcome['email_placeholder'],
            'submit_label' => $validated['welcome_popup_submit_label'] ?? $welcome['submit_label'],
            'legal_html' => $validated['welcome_popup_legal_html'] ?? $welcome['legal_html'],
            'success_message' => $validated['welcome_popup_success_message'] ?? $welcome['success_message'],
            'image' => $welcome['image'],
        ]);

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->route('admin.settings.index')->with('success', 'System settings updated.');
    }

    /**
     * @return array<string, string>
     */
    private function getSettingsMap(): array
    {
        $defaults = [
            'site_name' => config('app.name'),
            'site_logo' => '',
            'footer_background' => '',
            'contact_whatsapp_phone' => '',
            'contact_google_script_url' => '',
            'home_news_ticker' => '',
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

    private function storeImage(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (! in_array($extension, $allowed, true)) {
            $extension = 'jpg';
        }
        $relativeDirectory = trim($directory, '/');
        $fileName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs($relativeDirectory, $fileName, 'public');

        if (! is_string($path) || $path === '') {
            return null;
        }

        return self::PUBLIC_STORAGE_PREFIX.$path;
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $relativePath = Str::startsWith($path, self::PUBLIC_STORAGE_PREFIX)
            ? Str::after($path, self::PUBLIC_STORAGE_PREFIX)
            : ltrim($path, '/');

        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
