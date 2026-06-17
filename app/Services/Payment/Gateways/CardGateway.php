<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Setting;
use App\Services\Payment\Data\PaymentInitiationResult;
use App\Services\Payment\Stripe\StripeApiClient;
use App\Support\CheckoutCountries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardGateway extends AbstractPaymentGateway
{
    private const BILLING_SESSION_PREFIX = 'checkout.card.billing.';

    public function code(): string
    {
        return 'card';
    }

    public function label(): string
    {
        return 'Credit or Debit Card';
    }

    public function description(): string
    {
        return 'Pay securely with Visa, Mastercard, American Express, and more.';
    }

    public function customerFieldsView(): ?string
    {
        return 'shop.checkout.gateways._card-fields';
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    public function validationRules(): array
    {
        $countryCodes = implode(',', array_keys(CheckoutCountries::options()));

        return [
            'card_billing_same_as_shipping' => 'nullable|boolean',
            'card_billing_first_name' => 'required_if:card_billing_same_as_shipping,0|nullable|string|max:80',
            'card_billing_last_name' => 'required_if:card_billing_same_as_shipping,0|nullable|string|max:80',
            'card_billing_address_line1' => 'required_if:card_billing_same_as_shipping,0|nullable|string|max:200',
            'card_billing_address_line2' => 'nullable|string|max:200',
            'card_billing_city' => 'required_if:card_billing_same_as_shipping,0|nullable|string|max:100',
            'card_billing_postcode' => 'required_if:card_billing_same_as_shipping,0|nullable|string|max:20',
            'card_billing_country' => 'required_if:card_billing_same_as_shipping,0|nullable|string|in:'.$countryCodes,
        ];
    }

    public function processingView(): string
    {
        return 'shop.checkout.gateways.card-processing';
    }

    public function initiate(Order $order, Request $request): PaymentInitiationResult
    {
        $client = $this->apiClient();

        if ($client === null) {
            return PaymentInitiationResult::view(
                viewData: ['configured' => false],
                notes: 'Stripe API credentials missing',
            );
        }

        $this->storeBillingDetails($order, $request);

        $existingId = (string) optional($order->paymentTransactions()->first())->gateway_transaction_id;
        if ($existingId !== '') {
            $summary = $client->getPaymentIntent($existingId);
            if ($summary !== null
                && $client->isReusableStatus($summary['status'])
                && $client->amountMatchesOrder($order, $summary['amount'], $summary['currency'])) {
                return $this->viewResult($client, $order, $existingId, $summary['client_secret'] ?? null);
            }
        }

        $created = $client->createPaymentIntent($order);
        if ($created === null || ($created['id'] ?? '') === '' || ($created['client_secret'] ?? '') === '') {
            return PaymentInitiationResult::view(
                viewData: [
                    'configured' => true,
                    'error' => 'Could not start card checkout. Please try again or contact support.',
                ],
                notes: 'Stripe payment intent creation failed',
            );
        }

        return $this->viewResult(
            $client,
            $order,
            $created['id'],
            $created['client_secret'],
            'Stripe card PaymentIntent '.$created['id'].' created',
        );
    }

    public function confirm(Order $order, Request $request): bool
    {
        $client = $this->apiClient();
        if ($client === null) {
            return false;
        }

        $paymentIntentId = trim((string) $request->input('payment_intent_id', ''));
        if ($paymentIntentId === '') {
            $paymentIntentId = trim((string) $request->input('gateway_transaction_id', ''));
        }

        $storedId = (string) optional($order->paymentTransactions()->first())->gateway_transaction_id;
        if ($paymentIntentId === '' || $storedId === '' || ! hash_equals($storedId, $paymentIntentId)) {
            Log::warning('Card confirm: payment intent ID mismatch', [
                'order_number' => $order->order_number,
            ]);

            return false;
        }

        $summary = $client->getPaymentIntent($paymentIntentId);
        if ($summary === null) {
            return false;
        }

        if (! $client->amountMatchesOrder($order, $summary['amount'], $summary['currency'])) {
            Log::warning('Card confirm: amount mismatch', [
                'order_number' => $order->order_number,
            ]);

            return false;
        }

        if ($summary['status'] !== 'succeeded') {
            Log::warning('Card confirm: payment not succeeded', [
                'order_number' => $order->order_number,
                'status' => $summary['status'],
            ]);

            return false;
        }

        $request->merge(['gateway_transaction_id' => $paymentIntentId]);
        session()->forget(self::BILLING_SESSION_PREFIX.$order->order_number);

        return true;
    }

    private function viewResult(
        StripeApiClient $client,
        Order $order,
        string $paymentIntentId,
        ?string $clientSecret = null,
        ?string $notes = null,
    ): PaymentInitiationResult {
        if ($clientSecret === null || $clientSecret === '') {
            $summary = $client->getPaymentIntent($paymentIntentId);
            $clientSecret = (string) ($summary['client_secret'] ?? '');
        }

        if ($clientSecret === '') {
            return PaymentInitiationResult::view(
                viewData: [
                    'configured' => true,
                    'error' => 'Could not load card checkout. Please refresh and try again.',
                ],
                notes: 'Stripe client secret missing',
            );
        }

        return PaymentInitiationResult::view(
            viewData: [
                'configured' => true,
                'publishableKey' => $client->publishableKey(),
                'clientSecret' => $clientSecret,
                'paymentIntentId' => $paymentIntentId,
                'billingDetails' => session(self::BILLING_SESSION_PREFIX.$order->order_number, [
                    'name' => (string) $order->customer_name,
                    'email' => (string) $order->customer_email,
                ]),
                'testMode' => $client->isTestMode(),
            ],
            gatewayTransactionId: $paymentIntentId,
            notes: $notes ?? 'Awaiting card payment for PaymentIntent '.$paymentIntentId,
        );
    }

    private function storeBillingDetails(Order $order, Request $request): void
    {
        if ($request->isMethod('get') || ! $request->has('payment_method')) {
            return;
        }

        $sameAsShipping = $request->boolean('card_billing_same_as_shipping', true);

        if ($sameAsShipping) {
            $billing = [
                'name' => trim((string) $request->input('shipping_first_name').' '.(string) $request->input('shipping_last_name')),
                'email' => (string) $request->input('customer_email', $order->customer_email),
                'phone' => (string) $request->input('shipping_phone', ''),
                'address' => [
                    'line1' => (string) $request->input('shipping_address_line1', ''),
                    'line2' => (string) $request->input('shipping_address_line2', ''),
                    'city' => (string) $request->input('shipping_city', ''),
                    'postal_code' => (string) $request->input('shipping_postcode', ''),
                    'country' => (string) $request->input('shipping_country', ''),
                ],
            ];
        } else {
            $billing = [
                'name' => trim((string) $request->input('card_billing_first_name').' '.(string) $request->input('card_billing_last_name')),
                'email' => (string) $request->input('customer_email', $order->customer_email),
                'phone' => (string) $request->input('shipping_phone', ''),
                'address' => [
                    'line1' => (string) $request->input('card_billing_address_line1', ''),
                    'line2' => (string) $request->input('card_billing_address_line2', ''),
                    'city' => (string) $request->input('card_billing_city', ''),
                    'postal_code' => (string) $request->input('card_billing_postcode', ''),
                    'country' => (string) $request->input('card_billing_country', ''),
                ],
            ];
        }

        session([self::BILLING_SESSION_PREFIX.$order->order_number => $this->compactBillingDetails($billing)]);
    }

    /**
     * @param  array<string, mixed>  $billing
     * @return array<string, mixed>
     */
    private function compactBillingDetails(array $billing): array
    {
        $billing['address'] = array_filter($billing['address'] ?? [], static fn ($value) => trim((string) $value) !== '');

        return array_filter($billing, static function ($value) {
            if (is_array($value)) {
                return $value !== [];
            }

            return trim((string) $value) !== '';
        });
    }

    private function apiClient(): ?StripeApiClient
    {
        $settings = Setting::query()
            ->whereIn('key', [
                'payment_apple_pay_stripe_publishable_key',
                'payment_apple_pay_stripe_secret_key',
                'payment_apple_pay_stripe_test_mode',
            ])
            ->pluck('value', 'key')
            ->map(fn ($value) => (string) $value)
            ->all();

        return StripeApiClient::fromSettings(
            $settings['payment_apple_pay_stripe_publishable_key'] ?? '',
            $settings['payment_apple_pay_stripe_secret_key'] ?? '',
            ($settings['payment_apple_pay_stripe_test_mode'] ?? '1') === '1',
        );
    }
}
