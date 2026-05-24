<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use Illuminate\Http\Request;

class ZelleGateway extends AbstractProofTransferGateway
{
    public function code(): string
    {
        return 'zelle';
    }

    public function label(): string
    {
        return 'Zelle';
    }

    public function description(): string
    {
        return 'Scan our Zelle QR, send the exact total, then upload your transfer screenshot.';
    }

    public function iconHtml(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="32" height="32">
    <rect width="24" height="24" rx="5" fill="#6d1ed4"/>
    <path fill="#fff" d="M6.5 7.5h3.8l2.2 3.4 2.2-3.4h3.8l-4.5 6.5 4.8 7h-3.9l-2.5-3.7-2.5 3.7H6.2l4.8-7-4.5-6.5Z"/>
</svg>
SVG;
    }

    public function processingView(): string
    {
        return 'shop.checkout.gateways.zelle-processing';
    }

    public function initiate(Order $order, Request $request): PaymentInitiationResult
    {
        return PaymentInitiationResult::view(
            viewData: [
                'payeeLabel' => trim($this->setting('payee_label')),
                'qrImage' => trim($this->setting('qr_image')),
                'amount' => number_format((float) $order->total_display, 2),
                'currency' => $order->currency_code,
                'memo' => 'Order '.$order->order_number,
            ],
            notes: 'Awaiting Zelle payment + proof upload',
        );
    }
}
