<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use Illuminate\Http\Request;

class CashAppGateway extends AbstractProofTransferGateway
{
    public function code(): string
    {
        return 'cashapp';
    }

    public function label(): string
    {
        return 'Cash App';
    }

    public function description(): string
    {
        return 'Scan our Cash App QR, send the exact total, then upload your receipt screenshot.';
    }

    public function iconHtml(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="32" height="32">
    <rect width="24" height="24" rx="5" fill="#00d632"/>
    <path fill="#fff" d="M13.2 4.5h-2.4v3.2H8.5v2.4h2.3v1.8c0 2.8 1.6 4.4 4.4 4.4.9 0 1.7-.1 2.4-.4v-2.6c-.5.2-1 .3-1.6.3-1.4 0-2.1-.7-2.1-2.2V10h3.5V7.7h-3.3V4.5Z"/>
</svg>
SVG;
    }

    public function processingView(): string
    {
        return 'shop.checkout.gateways.cashapp-processing';
    }

    public function initiate(Order $order, Request $request): PaymentInitiationResult
    {
        $cashtag = ltrim(trim($this->setting('cashtag')), '$');
        $qrImage = trim($this->setting('qr_image'));
        $payUrl = $cashtag !== ''
            ? 'https://cash.app/$'.rawurlencode($cashtag).'/'.$this->amountForCashApp($order)
            : '';

        return PaymentInitiationResult::view(
            viewData: [
                'cashtag' => $cashtag,
                'qrImage' => $qrImage,
                'payUrl' => $payUrl,
                'amount' => number_format((float) $order->total_display, 2),
                'currency' => $order->currency_code,
            ],
            notes: 'Awaiting Cash App payment + proof upload',
        );
    }

    private function amountForCashApp(Order $order): string
    {
        $amount = (float) $order->total_display;

        return number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2, '.', '');
    }
}
