<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use Illuminate\Http\Request;

class VenmoGateway extends AbstractProofTransferGateway
{
    public function code(): string
    {
        return 'venmo';
    }

    public function label(): string
    {
        return 'Venmo';
    }

    public function description(): string
    {
        return 'Scan the QR code or open Venmo to pay, then upload your transfer screenshot.';
    }

    public function iconHtml(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="32" height="32">
    <rect width="24" height="24" rx="5" fill="#008cff"/>
    <path fill="#fff" d="M16.8 4.5c-1.4 3.4-3.6 6.2-5.5 8.1 1.1-2.1 1.8-4.5 2-6.9H8.2C7.4 9.8 5.8 14.6 3.5 19.5h3.4c.8-1.7 1.7-3.4 2.6-4.9.9 1.5 2.1 3.1 3.5 4.6 1.4-2.4 2.4-5 3-7.7H16.8Z"/>
</svg>
SVG;
    }

    public function processingView(): string
    {
        return 'shop.checkout.gateways.venmo-processing';
    }

    public function initiate(Order $order, Request $request): PaymentInitiationResult
    {
        $username = ltrim(trim($this->setting('username')), '@');
        $payUrl = $username !== '' ? $this->venmoPayUrl($order) : '';

        return PaymentInitiationResult::view(
            viewData: [
                'username' => $username,
                'payUrl' => $payUrl,
                'amount' => number_format((float) $order->total_display, 2),
                'currency' => $order->currency_code,
                'note' => 'Order '.$order->order_number,
            ],
            notes: 'Awaiting Venmo payment + proof upload',
        );
    }
}
