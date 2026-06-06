<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentAdminController extends Controller
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    private const METHODS = [
        'paypal',
        'whatsapp',
        'apple_pay',
        'venmo',
        'cashapp',
        'zelle',
    ];

    public function index(Request $request)
    {
        $tab = trim((string) $request->get('tab', 'history'));
        if (! in_array($tab, ['history', 'settings'], true)) {
            $tab = 'history';
        }

        $q = trim((string) $request->get('q', ''));
        $method = trim((string) $request->get('method', ''));
        $status = trim((string) $request->get('status', ''));

        $transactions = PaymentTransaction::query()
            ->with('order')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($where) use ($q) {
                    $where->where('gateway_transaction_id', 'like', '%'.$q.'%')
                        ->orWhereHas('order', function ($orderQuery) use ($q) {
                            $orderQuery->where('order_number', 'like', '%'.$q.'%');
                        });
                });
            })
            ->when(in_array($method, self::METHODS, true), fn ($query) => $query->where('payment_method', $method))
            ->when(in_array($status, PaymentTransaction::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->take(200)
            ->get();

        $settings = $this->paymentSettings();
        $hasPaypalSecret = ($settings['payment_paypal_client_secret'] ?? '') !== '';
        unset($settings['payment_paypal_client_secret']);

        return view('admin.payments.index', [
            'title' => 'Payments',
            'breadcrumbs' => [
                ['label' => 'Payments'],
            ],
            'tab' => $tab,
            'settings' => $settings,
            'hasPaypalSecret' => $hasPaypalSecret,
            'transactions' => $transactions,
            'q' => $q,
            'method' => $method,
            'status' => $status,
            'methods' => self::METHODS,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'paypal_enabled' => 'nullable|boolean',
            'paypal_merchant_email' => 'nullable|email|max:190',
            'paypal_client_id' => 'nullable|string|max:255',
            'paypal_client_secret' => 'nullable|string|max:255',
            'paypal_sandbox' => 'nullable|boolean',
            'whatsapp_enabled' => 'nullable|boolean',
            'whatsapp_phone' => 'nullable|string|max:60',
            'whatsapp_message_template' => 'nullable|string|max:500',
            'apple_pay_enabled' => 'nullable|boolean',
            'apple_pay_merchant_id' => 'nullable|string|max:190',
            'apple_pay_domain' => 'nullable|string|max:190',
            'venmo_enabled' => 'nullable|boolean',
            'venmo_username' => 'nullable|string|max:80',
            'cashapp_enabled' => 'nullable|boolean',
            'cashapp_cashtag' => 'nullable|string|max:80',
            'cashapp_qr' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'zelle_enabled' => 'nullable|boolean',
            'zelle_payee_label' => 'nullable|string|max:120',
            'zelle_qr' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $current = $this->paymentSettings();

        $cashappQr = $this->storeQrImage($request->file('cashapp_qr'), 'payments/cashapp');
        if ($cashappQr !== null) {
            $this->deletePublicPath($current['payment_cashapp_qr_image'] ?? null);
        }

        $zelleQr = $this->storeQrImage($request->file('zelle_qr'), 'payments/zelle');
        if ($zelleQr !== null) {
            $this->deletePublicPath($current['payment_zelle_qr_image'] ?? null);
        }

        $settings = [
            'payment_paypal_enabled' => $request->boolean('paypal_enabled') ? '1' : '0',
            'payment_paypal_merchant_email' => trim((string) ($validated['paypal_merchant_email'] ?? '')),
            'payment_paypal_client_id' => trim((string) ($validated['paypal_client_id'] ?? '')),
            'payment_paypal_sandbox' => $request->boolean('paypal_sandbox') ? '1' : '0',
            'payment_whatsapp_enabled' => $request->boolean('whatsapp_enabled') ? '1' : '0',
            'payment_whatsapp_phone' => trim((string) ($validated['whatsapp_phone'] ?? '')),
            'payment_whatsapp_message_template' => trim((string) ($validated['whatsapp_message_template'] ?? '')),
            'payment_apple_pay_enabled' => $request->boolean('apple_pay_enabled') ? '1' : '0',
            'payment_apple_pay_merchant_id' => trim((string) ($validated['apple_pay_merchant_id'] ?? '')),
            'payment_apple_pay_domain' => trim((string) ($validated['apple_pay_domain'] ?? '')),
            'payment_venmo_enabled' => $request->boolean('venmo_enabled') ? '1' : '0',
            'payment_venmo_username' => ltrim(trim((string) ($validated['venmo_username'] ?? '')), '@'),
            'payment_cashapp_enabled' => $request->boolean('cashapp_enabled') ? '1' : '0',
            'payment_cashapp_cashtag' => ltrim(trim((string) ($validated['cashapp_cashtag'] ?? '')), '$'),
            'payment_zelle_enabled' => $request->boolean('zelle_enabled') ? '1' : '0',
            'payment_zelle_payee_label' => trim((string) ($validated['zelle_payee_label'] ?? '')),
        ];

        if ($cashappQr !== null) {
            $settings['payment_cashapp_qr_image'] = $cashappQr;
        }
        if ($zelleQr !== null) {
            $settings['payment_zelle_qr_image'] = $zelleQr;
        }

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $secret = trim((string) $request->input('paypal_client_secret', ''));
        if ($secret !== '') {
            Setting::query()->updateOrCreate(
                ['key' => 'payment_paypal_client_secret'],
                ['value' => $secret],
            );
        }

        return redirect()->route('admin.payments.index', ['tab' => 'settings'])->with('success', 'Payment settings updated.');
    }

    /**
     * @return array<string, string>
     */
    private function paymentSettings(): array
    {
        $defaults = [
            'payment_paypal_enabled' => '0',
            'payment_paypal_merchant_email' => '',
            'payment_paypal_client_id' => '',
            'payment_paypal_client_secret' => '',
            'payment_paypal_sandbox' => '1',
            'payment_whatsapp_enabled' => '0',
            'payment_whatsapp_phone' => '',
            'payment_whatsapp_message_template' => '',
            'payment_apple_pay_enabled' => '0',
            'payment_apple_pay_merchant_id' => '',
            'payment_apple_pay_domain' => '',
            'payment_venmo_enabled' => '0',
            'payment_venmo_username' => '',
            'payment_cashapp_enabled' => '0',
            'payment_cashapp_cashtag' => '',
            'payment_cashapp_qr_image' => '',
            'payment_zelle_enabled' => '0',
            'payment_zelle_payee_label' => '',
            'payment_zelle_qr_image' => '',
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

    private function storeQrImage(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (! in_array($extension, $allowed, true)) {
            $extension = 'png';
        }

        $fileName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs(trim($directory, '/'), $fileName, 'public');

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
