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

    public function webSdkUrl(): string
    {
        return $this->sandbox
            ? 'https://www.sandbox.paypal.com/web-sdk/v6/core'
            : 'https://www.paypal.com/web-sdk/v6/core';
    }

    public function sdkUrl(string $currencyCode, string $components = 'buttons'): string
    {
        $host = $this->sandbox
            ? 'https://www.sandbox.paypal.com'
            : 'https://www.paypal.com';

        $query = [
            'client-id' => $this->clientId,
            'currency' => strtoupper($currencyCode),
            'intent' => 'capture',
            'components' => $components,
        ];

        if ($this->sandbox) {
            $query['buyer-country'] = 'US';
        }

        return $host.'/sdk/js?'.http_build_query($query);
    }

    public function generateClientToken(): ?string
    {
        $token = $this->accessToken();
        if ($token === null) {
            return null;
        }

        $response = Http::baseUrl($this->apiBaseUrl())
            ->acceptJson()
            ->withToken($token)
            ->withBody('{}', 'application/json')
            ->post('/v1/identity/generate-token');

        if (! $response->successful()) {
            Log::warning('PayPal client token generation failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }

        $clientToken = trim((string) $response->json('client_token'));

        return $clientToken !== '' ? $clientToken : null;
    }

    public function generateBrowserSafeClientToken(?string $domain = null): ?string
    {
        $payload = [
            'grant_type' => 'client_credentials',
            'response_type' => 'client_token',
        ];

        $domain = trim((string) ($domain ?? url('/')));
        if ($domain !== '') {
            $payload['domains[]'] = $domain;
        }

        $response = Http::baseUrl($this->apiBaseUrl())
            ->asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post('/v1/oauth2/token', $payload);

        if (! $response->successful()) {
            Log::warning('PayPal browser-safe client token generation failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }

        $clientToken = trim((string) $response->json('access_token'));

        return $clientToken !== '' ? $clientToken : null;
    }

    /**
     * @param  array<string, string|null>  $headers
     * @param  array<string, mixed>  $event
     */
    public function verifyWebhookSignature(array $headers, array $event, string $webhookId): bool
    {
        $api = $this->api();
        if ($api === null || trim($webhookId) === '') {
            return false;
        }

        $response = $api->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo' => (string) ($headers['auth_algo'] ?? ''),
            'cert_url' => (string) ($headers['cert_url'] ?? ''),
            'transmission_id' => (string) ($headers['transmission_id'] ?? ''),
            'transmission_sig' => (string) ($headers['transmission_sig'] ?? ''),
            'transmission_time' => (string) ($headers['transmission_time'] ?? ''),
            'webhook_id' => trim($webhookId),
            'webhook_event' => $event,
        ]);

        if (! $response->successful()) {
            Log::warning('PayPal webhook signature verification failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return false;
        }

        return strtoupper((string) $response->json('verification_status')) === 'SUCCESS';
    }

    /**
     * @return array{id: string, status: string}|null
     */
    public function createOrder(Order $order): ?array
    {
        $api = $this->api();
        if ($api === null) {
            return null;
        }

        $amount = $this->formatAmount((float) $order->total_display);
        $currency = strtoupper((string) $order->currency_code);

        $response = $api->post('/v2/checkout/orders', [
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
        $token = $this->accessToken();
        if ($token === null) {
            return null;
        }

        // PayPal requires a JSON object body, not `[]` (Laravel encodes [] as an array).
        $response = Http::baseUrl($this->apiBaseUrl())
            ->acceptJson()
            ->withToken($token)
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
        $api = $this->api();
        if ($api === null) {
            return null;
        }

        $response = $api->get('/v2/checkout/orders/'.$paypalOrderId);

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

    /**
     * @return array{
     *   email: string,
     *   given_name: string,
     *   surname: string,
     *   phone: string,
     *   shipping: array<string, string>
     * }|null
     */
    public function getPayerDetails(string $paypalOrderId): ?array
    {
        $api = $this->api();
        if ($api === null) {
            return null;
        }

        $response = $api->get('/v2/checkout/orders/'.$paypalOrderId);
        if (! $response->successful()) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();
        $payer = $data['payer'] ?? null;
        if (! is_array($payer)) {
            return null;
        }

        $email = strtolower(trim((string) ($payer['email_address'] ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $name = is_array($payer['name'] ?? null) ? $payer['name'] : [];
        $givenName = trim((string) ($name['given_name'] ?? ''));
        $surname = trim((string) ($name['surname'] ?? ''));

        $phone = '';
        $phoneObj = $payer['phone'] ?? null;
        if (is_array($phoneObj)) {
            $phoneNumber = $phoneObj['phone_number'] ?? null;
            if (is_array($phoneNumber)) {
                $national = preg_replace('/\D/', '', (string) ($phoneNumber['national_number'] ?? ''));
                $countryCode = preg_replace('/\D/', '', (string) ($phoneNumber['country_code'] ?? ''));
                if ($national !== '') {
                    $phone = $countryCode !== '' ? '+'.$countryCode.$national : $national;
                }
            }
        }

        $shipping = [];
        $unit = $data['purchase_units'][0] ?? null;
        if (is_array($unit) && is_array($unit['shipping'] ?? null)) {
            $ship = $unit['shipping'];
            $shipName = is_array($ship['name'] ?? null)
                ? trim((string) ($ship['name']['full_name'] ?? ''))
                : '';
            $address = is_array($ship['address'] ?? null) ? $ship['address'] : [];
            $shipping = [
                'full_name' => $shipName,
                'address_line1' => trim((string) ($address['address_line_1'] ?? '')),
                'address_line2' => trim((string) ($address['address_line_2'] ?? '')),
                'city' => trim((string) ($address['admin_area_2'] ?? '')),
                'postcode' => trim((string) ($address['postal_code'] ?? '')),
                'country' => strtoupper(trim((string) ($address['country_code'] ?? ''))),
            ];
        }

        return [
            'email' => $email,
            'given_name' => $givenName,
            'surname' => $surname,
            'phone' => $phone,
            'shipping' => $shipping,
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

    private function api(): ?\Illuminate\Http\Client\PendingRequest
    {
        $token = $this->accessToken();
        if ($token === null) {
            return null;
        }

        return Http::baseUrl($this->apiBaseUrl())
            ->acceptJson()
            ->asJson()
            ->withToken($token);
    }

    private function apiBaseUrl(): string
    {
        return $this->sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function accessToken(): ?string
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

            return null;
        }

        $this->accessToken = (string) ($response->json('access_token') ?? '');

        if ($this->accessToken === '') {
            Log::error('PayPal access token missing');

            return null;
        }

        return $this->accessToken;
    }
}
