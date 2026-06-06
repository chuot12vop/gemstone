<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use App\Services\Payment\PayPal\PayPalApiClient;
use App\Support\CheckoutCountries;
use App\Support\MarketingSubscribers;
use App\Support\ShippingAddressFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalGateway extends AbstractPaymentGateway
{
    /** PayPal order IDs start with a known prefix in v2. */
    private const REUSABLE_STATUSES = ['CREATED', 'APPROVED', 'PAYER_ACTION_REQUIRED'];

    public function code(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return 'PayPal';
    }

    public function description(): string
    {
        return 'Pay securely with your PayPal balance, debit card, or credit card.';
    }

    public function iconHtml(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="32" height="32">
    <path fill="#003087" d="M7.4 21h-3a.6.6 0 0 1-.6-.7L5.7 4.6A.7.7 0 0 1 6.4 4h6.7c2.3 0 4 .5 4.9 1.6.8 1 2.3.6 3.9-.6 2.4-2.6 3.9-5.4 3.9H10l-.6 4-1 3.6Z"/>
    <path fill="#009cde" d="M19.7 8.5c-.6 2.4-2.6 3.9-5.4 3.9h-2.1c-.4 0-.7.3-.8.6L10.3 19l-.3 1.6a.6.6 0 0 0 .6.7H13l.3-.2.6-3.5.6-.2c2.5 0 4.4-1.3 5-3.7.2-1 .1-1.8-.4-2.4-.2-.3-.4-.6-.6-.8.5.6.5 1.2.2 2Z"/>
</svg>
SVG;
    }

    public function processingView(): string
    {
        return 'shop.checkout.gateways.paypal-processing';
    }

    public function initiate(Order $order, Request $request): PaymentInitiationResult
    {
        $client = $this->apiClient();

        if ($client === null) {
            return PaymentInitiationResult::view(
                viewData: ['configured' => false],
                notes: 'PayPal API credentials missing',
            );
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
                viewData: [
                    'configured' => true,
                    'error' => 'Could not start PayPal checkout. Please try again or contact support.',
                ],
                notes: 'PayPal create order failed',
            );
        }

        return $this->viewResult(
            $client,
            $order,
            $created['id'],
            $created['id'],
            'PayPal order '.$created['id'].' created',
        );
    }

    public function confirm(Order $order, Request $request): bool
    {
        $client = $this->apiClient();
        if ($client === null) {
            return false;
        }

        $paypalOrderId = trim((string) $request->input('paypal_order_id', ''));
        if ($paypalOrderId === '') {
            $paypalOrderId = trim((string) $request->input('gateway_transaction_id', ''));
        }

        $storedId = (string) optional($order->paymentTransactions()->first())->gateway_transaction_id;
        if ($paypalOrderId === '' || $storedId === '' || ! hash_equals($storedId, $paypalOrderId)) {
            Log::warning('PayPal confirm: order ID mismatch', [
                'order_number' => $order->order_number,
            ]);

            return false;
        }

        $summary = $client->getOrderSummary($paypalOrderId);
        if ($summary !== null && ! $client->amountMatchesOrder($order, $summary['amount'], $summary['currency'])) {
            Log::warning('PayPal confirm: amount mismatch', ['order_number' => $order->order_number]);

            return false;
        }

        if ($summary !== null && $summary['status'] === 'COMPLETED') {
            $captureId = $summary['capture_id'] ?? $paypalOrderId;
            $request->merge(['gateway_transaction_id' => $captureId]);

            return true;
        }

        $capture = $client->captureOrder($paypalOrderId);
        if ($capture === null || $capture['status'] !== 'COMPLETED') {
            return false;
        }

        $request->merge([
            'gateway_transaction_id' => $capture['capture_id'],
        ]);

        return true;
    }

    public function syncExpressPayerDetails(Order $order, string $paypalOrderId): bool
    {
        if (! $this->isExpressOrder($order)) {
            return false;
        }

        $client = $this->apiClient();
        if ($client === null) {
            return false;
        }

        $payer = $client->getPayerDetails($paypalOrderId);
        if ($payer === null) {
            Log::warning('PayPal express: payer details missing', [
                'order_number' => $order->order_number,
                'paypal_order_id' => $paypalOrderId,
            ]);

            return false;
        }

        $givenName = $payer['given_name'];
        $surname = $payer['surname'];
        $fullName = trim($givenName.' '.$surname);
        if ($fullName === '' && ($payer['shipping']['full_name'] ?? '') !== '') {
            $fullName = (string) $payer['shipping']['full_name'];
            [$givenName, $surname] = $this->splitFullName($fullName);
        }

        $phone = $payer['phone'];
        $shipping = $payer['shipping'];
        $hasShipping = ($shipping['address_line1'] ?? '') !== '';

        if ($hasShipping) {
            if ($fullName === '') {
                $fullName = 'PayPal Customer';
            }
            $order->shipping_address = ShippingAddressFormatter::toText([
                'first_name' => $givenName !== '' ? $givenName : $fullName,
                'last_name' => $surname,
                'company' => '',
                'address_line1' => $shipping['address_line1'],
                'address_line2' => $shipping['address_line2'],
                'city' => $shipping['city'],
                'postcode' => $shipping['postcode'],
                'country' => $shipping['country'] !== '' ? $shipping['country'] : CheckoutCountries::defaultCode(),
                'phone' => $phone,
            ]);
        } elseif ($phone !== '') {
            $order->shipping_address = ShippingAddressFormatter::toText([
                'first_name' => $givenName !== '' ? $givenName : ($fullName !== '' ? $fullName : 'PayPal Customer'),
                'last_name' => $surname,
                'company' => '',
                'address_line1' => 'Express checkout — address to confirm',
                'address_line2' => '',
                'city' => '—',
                'postcode' => '—',
                'country' => CheckoutCountries::defaultCode(),
                'phone' => $phone,
            ]);
        }

        $order->customer_email = $payer['email'];
        if ($fullName !== '') {
            $order->customer_name = $fullName;
        }
        if ($phone !== '') {
            $order->shipping_phone = $phone;
        }
        $order->save();

        if ($order->marketing_email_opt_in) {
            MarketingSubscribers::subscribe($payer['email']);
        }

        return true;
    }

    private function isExpressOrder(Order $order): bool
    {
        $notes = (string) optional($order->paymentTransactions()->first())->notes;

        return str_contains($notes, 'Express PayPal checkout');
    }

    /** @return array{0: string, 1: string} */
    private function splitFullName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $fullName, 2) ?: [];

        return [
            (string) ($parts[0] ?? ''),
            (string) ($parts[1] ?? ''),
        ];
    }

    private function viewResult(
        PayPalApiClient $client,
        Order $order,
        string $paypalOrderId,
        ?string $gatewayTransactionId = null,
        ?string $notes = null,
    ): PaymentInitiationResult {
        return PaymentInitiationResult::view(
            viewData: [
                'configured' => true,
                'clientId' => $client->clientId(),
                'paypalOrderId' => $paypalOrderId,
                'sdkUrl' => $client->sdkUrl((string) $order->currency_code),
                'sandbox' => $client->isSandbox(),
            ],
            gatewayTransactionId: $gatewayTransactionId ?? $paypalOrderId,
            notes: $notes ?? 'Awaiting PayPal capture for order '.$paypalOrderId,
        );
    }

    public function checkoutClient(): ?PayPalApiClient
    {
        return $this->apiClient();
    }

    private function apiClient(): ?PayPalApiClient
    {
        return PayPalApiClient::fromSettings(
            $this->setting('client_id'),
            $this->setting('client_secret'),
            $this->setting('sandbox', '1') === '1',
        );
    }
}
