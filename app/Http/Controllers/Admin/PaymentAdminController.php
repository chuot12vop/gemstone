<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use Illuminate\Http\Request;

class PaymentAdminController extends Controller
{
    private const METHODS = ['paypal', 'whatsapp', 'apple_pay'];

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
            ->when(in_array($status, ['pending', 'paid', 'failed', 'refunded'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->take(200)
            ->get();

        return view('admin.payments.index', [
            'title' => 'Payments',
            'breadcrumbs' => [
                ['label' => 'Payments'],
            ],
            'tab' => $tab,
            'settings' => $this->paymentSettings(),
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
            'whatsapp_enabled' => 'nullable|boolean',
            'whatsapp_phone' => 'nullable|string|max:60',
            'whatsapp_message_template' => 'nullable|string|max:500',
            'apple_pay_enabled' => 'nullable|boolean',
            'apple_pay_merchant_id' => 'nullable|string|max:190',
            'apple_pay_domain' => 'nullable|string|max:190',
        ]);

        $settings = [
            'payment_paypal_enabled' => $request->boolean('paypal_enabled') ? '1' : '0',
            'payment_paypal_merchant_email' => trim((string) ($validated['paypal_merchant_email'] ?? '')),
            'payment_paypal_client_id' => trim((string) ($validated['paypal_client_id'] ?? '')),
            'payment_whatsapp_enabled' => $request->boolean('whatsapp_enabled') ? '1' : '0',
            'payment_whatsapp_phone' => trim((string) ($validated['whatsapp_phone'] ?? '')),
            'payment_whatsapp_message_template' => trim((string) ($validated['whatsapp_message_template'] ?? '')),
            'payment_apple_pay_enabled' => $request->boolean('apple_pay_enabled') ? '1' : '0',
            'payment_apple_pay_merchant_id' => trim((string) ($validated['apple_pay_merchant_id'] ?? '')),
            'payment_apple_pay_domain' => trim((string) ($validated['apple_pay_domain'] ?? '')),
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->route('admin.payments.index')->with('success', 'Payment settings updated.');
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
            'payment_whatsapp_enabled' => '0',
            'payment_whatsapp_phone' => '',
            'payment_whatsapp_message_template' => '',
            'payment_apple_pay_enabled' => '0',
            'payment_apple_pay_merchant_id' => '',
            'payment_apple_pay_domain' => '',
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
}
