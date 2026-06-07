<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use App\Services\Payment\Stripe\StripeApiClient;
use App\Support\CheckoutCountries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApplePayGateway extends AbstractPaymentGateway
{
    public function code(): string
    {
        return 'apple_pay';
    }

    public function label(): string
    {
        return 'Apple Pay';
    }

    public function description(): string
    {
        return 'One-tap checkout on supported Apple devices.';
    }

    public function iconHtml(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="32" height="32">
    <path fill="#000" d="M16.4 12.3c0-2 1.7-3 1.8-3-1-1.5-2.5-1.7-3-1.7-1.3-.1-2.6.8-3.2.8-.7 0-1.7-.7-2.8-.7-1.4 0-2.7.8-3.5 2.1-1.5 2.6-.4 6.4 1 8.5.7 1 1.6 2.2 2.7 2.1 1.1 0 1.5-.7 2.8-.7s1.7.7 2.8.7c1.2 0 2-1 2.7-2 .8-1.2 1.2-2.4 1.2-2.5-.1 0-2.5-.9-2.5-3.6ZM14.1 6.2c.6-.8 1-1.8.9-2.9-.9 0-1.9.6-2.6 1.4-.6.7-1 1.7-.9 2.7 1 0 2-.5 2.6-1.2Z"/>
</svg>
SVG;
    }

    public function processingView(): string
    {
        return 'shop.checkout.gateways.applepay-processing';
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
                    'error' => 'Could not start Apple Pay checkout. Please try again or contact support.',
                ],
                notes: 'Stripe payment intent creation failed',
            );
        }

        return $this->viewResult(
            $client,
            $order,
            $created['id'],
            $created['client_secret'],
            'Stripe PaymentIntent '.$created['id'].' created',
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
            Log::warning('Apple Pay confirm: payment intent ID mismatch', [
                'order_number' => $order->order_number,
            ]);

            return false;
        }

        $summary = $client->getPaymentIntent($paymentIntentId);
        if ($summary === null) {
            return false;
        }

        if (! $client->amountMatchesOrder($order, $summary['amount'], $summary['currency'])) {
            Log::warning('Apple Pay confirm: amount mismatch', [
                'order_number' => $order->order_number,
            ]);

            return false;
        }

        if ($summary['status'] !== 'succeeded') {
            Log::warning('Apple Pay confirm: payment not succeeded', [
                'order_number' => $order->order_number,
                'status' => $summary['status'],
            ]);

            return false;
        }

        $request->merge(['gateway_transaction_id' => $paymentIntentId]);

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
                    'error' => 'Could not load Apple Pay session. Please refresh and try again.',
                ],
                notes: 'Stripe client secret missing',
            );
        }

        $currency = strtolower((string) $order->currency_code);

        return PaymentInitiationResult::view(
            viewData: [
                'configured' => true,
                'publishableKey' => $client->publishableKey(),
                'clientSecret' => $clientSecret,
                'paymentIntentId' => $paymentIntentId,
                'amount' => $client->amountInSmallestUnit((float) $order->total_display, $currency),
                'currency' => $currency,
                'country' => CheckoutCountries::defaultCode(),
                'testMode' => $client->isTestMode(),
            ],
            gatewayTransactionId: $paymentIntentId,
            notes: $notes ?? 'Awaiting Apple Pay for PaymentIntent '.$paymentIntentId,
        );
    }

    private function apiClient(): ?StripeApiClient
    {
        return StripeApiClient::fromSettings(
            $this->setting('stripe_publishable_key'),
            $this->setting('stripe_secret_key'),
            $this->setting('stripe_test_mode', '1') === '1',
        );
    }
}
