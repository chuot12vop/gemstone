<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use Illuminate\Http\Request;

class PayPalGateway extends AbstractPaymentGateway
{
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
    <path fill="#003087" d="M7.4 21h-3a.6.6 0 0 1-.6-.7L5.7 4.6A.7.7 0 0 1 6.4 4h6.7c2.3 0 4 .5 4.9 1.6.8 1 1 2.3.6 3.9-.6 2.4-2.6 3.9-5.4 3.9H10l-.6 4-1 3.6Z"/>
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
        $email = $this->setting('merchant_email');

        return PaymentInitiationResult::view(
            viewData: [
                'merchantEmail' => $email,
                'clientId' => $this->setting('client_id'),
            ],
            notes: $email !== '' ? 'Awaiting PayPal capture for '.$email : 'Awaiting PayPal capture',
        );
    }

    public function confirm(Order $order, Request $request): bool
    {
        // Placeholder for the real PayPal capture call. In production, verify
        // the `payer_id` / `payment_id` returned by the SDK against PayPal's
        // REST API before returning true.
        return true;
    }
}
