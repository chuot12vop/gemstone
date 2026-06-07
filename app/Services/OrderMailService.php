<?php

namespace App\Services;

use App\Mail\OrderPaidMail;
use App\Mail\OrderPlacedMail;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderMailService
{
    public function sendPlaced(Order $order): void
    {
        if ($order->placed_email_sent_at !== null) {
            return;
        }

        if (! $this->canSendTo($order->customer_email)) {
            return;
        }

        $order->loadMissing('items');
        $siteName = $this->siteName();

        try {
            Mail::to($order->customer_email)->send(new OrderPlacedMail($order, $siteName));
            $order->placed_email_sent_at = now();
            $order->save();
        } catch (\Throwable $e) {
            Log::error('Order placed email failed', [
                'order_number' => $order->order_number,
                'email' => $order->customer_email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendPaid(Order $order): void
    {
        if ($order->paid_email_sent_at !== null) {
            return;
        }

        if (! $this->canSendTo($order->customer_email)) {
            return;
        }

        $order->loadMissing('items');
        $siteName = $this->siteName();

        try {
            Mail::to($order->customer_email)->send(new OrderPaidMail($order, $siteName));
            $order->paid_email_sent_at = now();
            $order->save();
        } catch (\Throwable $e) {
            Log::error('Order paid email failed', [
                'order_number' => $order->order_number,
                'email' => $order->customer_email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function canSendTo(?string $email): bool
    {
        $email = strtolower(trim((string) $email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return ! str_ends_with($email, '@checkout.pending');
    }

    private function siteName(): string
    {
        return (string) (Setting::query()->where('key', 'site_name')->value('value')
            ?: config('app.name'));
    }
}
