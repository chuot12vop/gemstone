<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\Data\PaymentInitiationResult;
use Illuminate\Http\Request;

class WhatsAppGateway extends AbstractPaymentGateway
{
    public function code(): string
    {
        return 'whatsapp';
    }

    public function label(): string
    {
        return 'WhatsApp';
    }

    public function description(): string
    {
        return 'Confirm your order over WhatsApp and pay via bank transfer or COD.';
    }

    public function iconHtml(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="32" height="32">
    <path fill="#25d366" d="M20.5 3.5A11 11 0 0 0 3.7 17.4L2 22l4.7-1.6A11 11 0 1 0 20.5 3.5Z"/>
    <path fill="#fff" d="M16.5 14.4c-.3-.1-1.6-.8-1.8-.9-.3-.1-.5-.1-.7.1l-.9 1c-.2.2-.3.3-.6.1-1.6-.8-2.6-1.4-3.7-3.2-.3-.5.3-.5.8-1.5.1-.2 0-.4 0-.5l-.9-2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1 2.9 1.2 3.1c.1.2 2.1 3.2 5.2 4.5 1.9.7 2.6.8 3.5.7.6-.1 1.6-.6 1.8-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.7-.3Z"/>
</svg>
SVG;
    }

    public function processingView(): string
    {
        return 'shop.checkout.gateways.whatsapp-processing';
    }

    public function initiate(Order $order, Request $request): PaymentInitiationResult
    {
        $phone = preg_replace('/\D+/', '', $this->setting('phone')) ?? '';
        $template = $this->setting(
            'message_template',
            'Hello, I would like to pay for order #{order_number}'
        );
        $message = strtr($template, [
            '{order_number}' => $order->order_number,
            '{customer_name}' => $order->customer_name,
            '{total}' => number_format((float) $order->total_display, 2).' '.$order->currency_code,
        ]);

        $url = 'https://wa.me/'.$phone.'?text='.rawurlencode($message);

        return PaymentInitiationResult::view(
            viewData: [
                'whatsappUrl' => $url,
                'phone' => $phone,
                'message' => $message,
            ],
            notes: 'Awaiting customer WhatsApp confirmation',
        );
    }

    public function confirm(Order $order, Request $request): bool
    {
        // For WhatsApp the customer self-confirms after sending the message;
        // staff still verifies the payment on their side before shipping.
        return true;
    }
}
