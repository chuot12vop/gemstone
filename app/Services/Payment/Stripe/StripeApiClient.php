<?php

namespace App\Services\Payment\Stripe;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Payment Intents API — used for Apple Pay via Payment Request Button.
 */
class StripeApiClient
{
    private const API_BASE = 'https://api.stripe.com/v1';

    /** @var list<string> */
    private const REUSABLE_STATUSES = [
        'requires_payment_method',
        'requires_confirmation',
        'requires_action',
        'requires_capture',
    ];

    /** @var list<string> */
    private const ZERO_DECIMAL_CURRENCIES = [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    public function __construct(
        private string $publishableKey,
        private string $secretKey,
        private bool $testMode,
    ) {}

    public static function fromSettings(
        string $publishableKey,
        string $secretKey,
        bool $testMode,
    ): ?self {
        if ($publishableKey === '' || $secretKey === '') {
            return null;
        }

        return new self($publishableKey, $secretKey, $testMode);
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function publishableKey(): string
    {
        return $this->publishableKey;
    }

    /**
     * @return array{id: string, status: string, client_secret: string}|null
     */
    public function createPaymentIntent(Order $order): ?array
    {
        $response = $this->api()->post(self::API_BASE.'/payment_intents', [
            'amount' => $this->amountInSmallestUnit((float) $order->total_display, (string) $order->currency_code),
            'currency' => strtolower((string) $order->currency_code),
            'automatic_payment_methods[enabled]' => 'true',
            'metadata[order_number]' => $order->order_number,
            'metadata[order_id]' => (string) $order->id,
            'description' => 'Order '.$order->order_number,
        ]);

        if (! $response->successful()) {
            Log::warning('Stripe create payment intent failed', [
                'order_number' => $order->order_number,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        return [
            'id' => (string) ($data['id'] ?? ''),
            'status' => (string) ($data['status'] ?? ''),
            'client_secret' => (string) ($data['client_secret'] ?? ''),
        ];
    }

    /**
     * @return array{id: string, status: string, amount: int, currency: string, client_secret: string}|null
     */
    public function getPaymentIntent(string $paymentIntentId): ?array
    {
        if ($paymentIntentId === '') {
            return null;
        }

        $response = $this->api()->get(self::API_BASE.'/payment_intents/'.$paymentIntentId);
        if (! $response->successful()) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        return [
            'id' => (string) ($data['id'] ?? ''),
            'status' => (string) ($data['status'] ?? ''),
            'amount' => (int) ($data['amount'] ?? 0),
            'currency' => strtolower((string) ($data['currency'] ?? '')),
            'client_secret' => (string) ($data['client_secret'] ?? ''),
        ];
    }

    public function isReusableStatus(string $status): bool
    {
        return in_array($status, self::REUSABLE_STATUSES, true);
    }

    public function amountMatchesOrder(Order $order, int $stripeAmount, string $stripeCurrency): bool
    {
        $currency = strtolower((string) $order->currency_code);
        $expected = $this->amountInSmallestUnit((float) $order->total_display, $currency);

        return strtolower($stripeCurrency) === $currency && $stripeAmount === $expected;
    }

    public function amountInSmallestUnit(float $amount, string $currencyCode): int
    {
        $currency = strtoupper($currencyCode);
        $amount = max(0, $amount);

        if (in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true)) {
            return (int) round($amount);
        }

        return (int) round($amount * 100);
    }

    private function api(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->secretKey)
            ->asForm()
            ->acceptJson()
            ->timeout(30);
    }
}
