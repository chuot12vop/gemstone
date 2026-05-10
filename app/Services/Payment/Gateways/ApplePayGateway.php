<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use Illuminate\Http\Request;

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
        return PaymentInitiationResult::view(
            viewData: [
                'merchantId' => $this->setting('merchant_id'),
                'domain' => $this->setting('domain'),
            ],
            notes: 'Awaiting Apple Pay session',
        );
    }

    public function confirm(Order $order, Request $request): bool
    {
        // Placeholder for Apple Pay merchant validation. Real implementation
        // should validate the `paymentToken` against your processor.
        return true;
    }
}
