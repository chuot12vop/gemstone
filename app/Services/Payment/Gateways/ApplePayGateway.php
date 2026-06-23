<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use App\Services\Payment\PayPal\PayPalApiClient;
use App\Support\CheckoutCountries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApplePayGateway extends AbstractPaymentGateway
{
    private const REUSABLE_STATUSES = ['CREATED', 'APPROVED', 'PAYER_ACTION_REQUIRED'];

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
            return PaymentInitiationResult::view(viewData: ['configured' => false], notes: 'PayPal API credentials missing');
        }

        $existingId = (string) optional($order->paymentTransactions()->first())->gateway_transaction_id;
        if ($existingId !== '') {
            $summary = $client->getOrderSummary($existingId);
            if ($summary !== null
                && in_array($summary['status'], self::REUSABLE_STATUSES, true)
                && $client->amountMatchesOrder($order, $summary['amount'], $summary['currency'])) {
                return $this->viewResult($client, $order, $existingId);
            }
        }

        $created = $client->createOrder($order);
        if ($created === null || ($created['id'] ?? '') === '') {
            return PaymentInitiationResult::view(
                viewData: ['configured' => true, 'error' => 'Could not start Apple Pay checkout. Please try again or contact support.'],
                notes: 'PayPal Apple Pay order creation failed',
            );
        }

        return $this->viewResult($client, $order, $created['id'], 'PayPal Apple Pay order '.$created['id'].' created');
    }

    public function confirm(Order $order, Request $request): bool
    {
        $client = $this->apiClient();
        if ($client === null) {
            return false;
        }

        $paypalOrderId = trim((string) $request->input('paypal_order_id', ''));
        $storedId = (string) optional($order->paymentTransactions()->first())->gateway_transaction_id;
        if ($paypalOrderId === '' || $storedId === '' || ! hash_equals($storedId, $paypalOrderId)) {
            Log::warning('Apple Pay confirm: PayPal order ID mismatch', ['order_number' => $order->order_number]);

            return false;
        }

        $summary = $client->getOrderSummary($paypalOrderId);
        if ($summary === null || ! $client->amountMatchesOrder($order, $summary['amount'], $summary['currency'])) {
            Log::warning('Apple Pay confirm: PayPal amount mismatch or order unavailable', [
                'order_number' => $order->order_number,
            ]);

            return false;
        }

        if ($summary['status'] === 'COMPLETED' && filled($summary['capture_id'])) {
            $request->merge(['gateway_transaction_id' => $summary['capture_id']]);

            return true;
        }

        $capture = $client->captureOrder($paypalOrderId);
        if ($capture === null || $capture['status'] !== 'COMPLETED') {
            return false;
        }

        $request->merge(['gateway_transaction_id' => $capture['capture_id']]);

        return true;
    }

    private function viewResult(PayPalApiClient $client, Order $order, string $paypalOrderId, ?string $notes = null): PaymentInitiationResult
    {
        return PaymentInitiationResult::view(
            viewData: [
                'configured' => true,
                'paypalOrderId' => $paypalOrderId,
                'clientId' => $client->clientId(),
                'webSdkUrl' => $client->webSdkUrl(),
                'amount' => number_format((float) $order->total_display, 2, '.', ''),
                'currency' => strtoupper((string) $order->currency_code),
                'country' => CheckoutCountries::defaultCode(),
                'sandbox' => $client->isSandbox(),
            ],
            gatewayTransactionId: $paypalOrderId,
            notes: $notes ?? 'Awaiting PayPal Apple Pay payment for order '.$paypalOrderId,
        );
    }

    public function checkoutClient(): ?PayPalApiClient
    {
        return $this->apiClient();
    }

    private function apiClient(): ?PayPalApiClient
    {
        return PayPalApiClient::fromSettings(
            $this->settingFor('paypal', 'client_id'),
            $this->settingFor('paypal', 'client_secret'),
            $this->settingFor('paypal', 'sandbox', '1') === '1',
        );
    }
}
