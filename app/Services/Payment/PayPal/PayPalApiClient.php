<?php

namespace App\Services\Payment\PayPal;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Checkout v2 (Orders API) — server-side create + capture.
 */
class PayPalApiClient
{
    private ?string $accessToken = null;

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private bool $sandbox,
    ) {}

    public static function fromSettings(
        string $clientId,
        string $clientSecret,
        bool $sandbox,
    ): ?self {
        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        return new self($clientId, $clientSecret, $sandbox);
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function sdkUrl(string $currencyCode): string
    {
        $host = $this->sandbox
            ? 'https://www.sandbox.paypal.com'
            : 'https://www.paypal.com';

        return $host.'/sdk/js?'.http_build_query([
            'client-id' => $this->clientId,
            'currency' => strtoupper($currencyCode),
            'intent' => 'capture',
        ]);
    }

    /**
     * @return array{id: string, status: string}|null
     */
    public function createOrder(Order $order): ?array
    {
        $amount = $this->formatAmount((float) $order->total_display);
        $currency = strtoupper((string) $order->currency_code);

        $response = $this->api()->post('/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $order->order_number,
                'custom_id' => (string) $order->id,
                'description' => 'Order '.$order->order_number,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => $amount,
                ],
            ]],
            'application_context' => [
                'brand_name' => (string) config('app.name', 'Store'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
            ],
        ]);

        if (! $response->successful()) {
            Log::warning('PayPal create order failed', [
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
        ];
    }

    /**
     * @return array{capture_id: string, status: string}|null
     */
    public function captureOrder(string $paypalOrderId): ?array
    {
        // PayPal requires a JSON object body, not `[]` (Laravel encodes [] as an array).
        $response = Http::baseUrl($this->apiBaseUrl())
            ->acceptJson()
            ->withToken($this->accessToken())
            ->withBody('{}', 'application/json')
            ->post('/v2/checkout/orders/'.$paypalOrderId.'/capture');

        if (! $response->successful()) {
            Log::warning('PayPal capture failed', [
                'paypal_order_id' => $paypalOrderId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();
        $capture = $this->extractCapture($data);

        if ($capture === null) {
            return null;
        }

        return $capture;
    }

    /**
     * @return array{status: string, amount: string, currency: string, capture_id: ?string}|null
     */
    public function getOrderSummary(string $paypalOrderId): ?array
    {
        $response = $this->api()->get('/v2/checkout/orders/'.$paypalOrderId);

        if (! $response->successful()) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        $unit = $data['purchase_units'][0] ?? null;
        if (! is_array($unit)) {
            return null;
        }

        $amount = $unit['amount'] ?? null;
        if (! is_array($amount)) {
            return null;
        }

        $capture = $this->extractCapture($data);

        return [
            'status' => (string) ($data['status'] ?? ''),
            'amount' => (string) ($amount['value'] ?? ''),
            'currency' => strtoupper((string) ($amount['currency_code'] ?? '')),
            'capture_id' => $capture['capture_id'] ?? null,
        ];
    }

    public function amountMatchesOrder(Order $order, string $paypalAmount, string $paypalCurrency): bool
    {
        $expected = $this->formatAmount((float) $order->total_display);
        $currency = strtoupper((string) $order->currency_code);

        return strtoupper($paypalCurrency) === $currency
            && bccomp($expected, $paypalAmount, 2) === 0;
    }

    private function formatAmount(float $amount): string
    {
        return number_format(max(0, $amount), 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $captureResponse
     * @return array{capture_id: string, status: string}|null
     */
    private function extractCapture(array $captureResponse): ?array
    {
        $units = $captureResponse['purchase_units'] ?? [];
        if (! is_array($units) || $units === []) {
            return null;
        }

        $unit = $units[0];
        if (! is_array($unit)) {
            return null;
        }

        $payments = $unit['payments'] ?? null;
        if (! is_array($payments)) {
            return null;
        }

        $captures = $payments['captures'] ?? [];
        if (! is_array($captures) || $captures === []) {
            return null;
        }

        $capture = $captures[0];
        if (! is_array($capture)) {
            return null;
        }

        $id = (string) ($capture['id'] ?? '');
        $status = (string) ($capture['status'] ?? '');

        if ($id === '' || $status === '') {
            return null;
        }

        return ['capture_id' => $id, 'status' => $status];
    }

    private function api(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->apiBaseUrl())
            ->acceptJson()
            ->asJson()
            ->withToken($this->accessToken());
    }

    private function apiBaseUrl(): string
    {
        return $this->sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function accessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $response = Http::baseUrl($this->apiBaseUrl())
            ->asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post('/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (! $response->successful()) {
            Log::error('PayPal OAuth failed', ['status' => $response->status()]);

            throw new \RuntimeException('PayPal authentication failed.');
        }

        $this->accessToken = (string) ($response->json('access_token') ?? '');

        if ($this->accessToken === '') {
            throw new \RuntimeException('PayPal access token missing.');
        }

        return $this->accessToken;
    }
}
