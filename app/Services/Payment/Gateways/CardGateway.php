<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use App\Services\Payment\PayPal\PayPalApiClient;
use App\Support\CheckoutCountries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardGateway extends AbstractPaymentGateway
{
    private const BILLING_SESSION_PREFIX = 'checkout.card.billing.';

    private const REUSABLE_STATUSES = ['CREATED', 'APPROVED', 'PAYER_ACTION_REQUIRED'];

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

    /** @return array<string, string|array<int, string>> */
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

    public function checkoutClient(): ?PayPalApiClient
    {
        return $this->apiClient();
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

        $this->storeBillingDetails($order, $request);

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
                    'error' => 'Could not start card checkout. Please try again or contact support.',
                ],
                notes: 'PayPal card order creation failed',
            );
        }

        return $this->viewResult(
            $client,
            $order,
            $created['id'],
            'PayPal card order '.$created['id'].' created',
        );
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
            Log::warning('Card confirm: PayPal order ID mismatch', ['order_number' => $order->order_number]);

            return false;
        }

        $summary = $client->getOrderSummary($paypalOrderId);
        if ($summary === null || ! $client->amountMatchesOrder($order, $summary['amount'], $summary['currency'])) {
            Log::warning('Card confirm: PayPal amount mismatch or order unavailable', [
                'order_number' => $order->order_number,
            ]);

            return false;
        }

        if ($summary['status'] === 'COMPLETED' && filled($summary['capture_id'])) {
            $request->merge(['gateway_transaction_id' => $summary['capture_id']]);
            session()->forget(self::BILLING_SESSION_PREFIX.$order->order_number);

            return true;
        }

        $capture = $client->captureOrder($paypalOrderId);
        if ($capture === null || $capture['status'] !== 'COMPLETED') {
            return false;
        }

        $request->merge(['gateway_transaction_id' => $capture['capture_id']]);
        session()->forget(self::BILLING_SESSION_PREFIX.$order->order_number);

        return true;
    }

    private function viewResult(
        PayPalApiClient $client,
        Order $order,
        string $paypalOrderId,
        ?string $notes = null,
    ): PaymentInitiationResult {
        return PaymentInitiationResult::view(
            viewData: [
                'configured' => true,
                'paypalOrderId' => $paypalOrderId,
                'clientId' => $client->clientId(),
                'webSdkUrl' => $client->webSdkUrl(),
                'currency' => strtoupper((string) $order->currency_code),
                'billingDetails' => session(self::BILLING_SESSION_PREFIX.$order->order_number, [
                    'name' => (string) $order->customer_name,
                    'email' => (string) $order->customer_email,
                ]),
                'sandbox' => $client->isSandbox(),
            ],
            gatewayTransactionId: $paypalOrderId,
            notes: $notes ?? 'Awaiting PayPal card payment for order '.$paypalOrderId,
        );
    }

    private function storeBillingDetails(Order $order, Request $request): void
    {
        if ($request->isMethod('get') || ! $request->has('payment_method')) {
            return;
        }

        $sameAsShipping = $request->boolean('card_billing_same_as_shipping', true);
        $billing = $sameAsShipping ? [
            'name' => trim((string) $request->input('shipping_first_name').' '.(string) $request->input('shipping_last_name')),
            'email' => (string) $request->input('customer_email', $order->customer_email),
            'phone' => (string) $request->input('shipping_phone', ''),
            'address' => [
                'addressLine1' => (string) $request->input('shipping_address_line1', ''),
                'addressLine2' => (string) $request->input('shipping_address_line2', ''),
                'adminArea2' => (string) $request->input('shipping_city', ''),
                'postalCode' => (string) $request->input('shipping_postcode', ''),
                'countryCode' => (string) $request->input('shipping_country', ''),
            ],
        ] : [
            'name' => trim((string) $request->input('card_billing_first_name').' '.(string) $request->input('card_billing_last_name')),
            'email' => (string) $request->input('customer_email', $order->customer_email),
            'phone' => (string) $request->input('shipping_phone', ''),
            'address' => [
                'addressLine1' => (string) $request->input('card_billing_address_line1', ''),
                'addressLine2' => (string) $request->input('card_billing_address_line2', ''),
                'adminArea2' => (string) $request->input('card_billing_city', ''),
                'postalCode' => (string) $request->input('card_billing_postcode', ''),
                'countryCode' => (string) $request->input('card_billing_country', ''),
            ],
        ];

        $billing['address'] = array_filter($billing['address'], static fn ($value) => trim((string) $value) !== '');
        session([self::BILLING_SESSION_PREFIX.$order->order_number => array_filter(
            $billing,
            static fn ($value) => is_array($value) ? $value !== [] : trim((string) $value) !== '',
        )]);
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
