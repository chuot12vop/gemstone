<?php

namespace Tests\Feature;

use App\Mail\OrderPaidMail;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayPalWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        foreach ([
            'payment_paypal_client_id' => 'client-id',
            'payment_paypal_client_secret' => 'client-secret',
            'payment_paypal_webhook_id' => 'WEBHOOK-1',
            'payment_paypal_sandbox' => '1',
        ] as $key => $value) {
            Setting::query()->create(['key' => $key, 'value' => $value]);
        }
    }

    public function test_completed_webhook_marks_order_paid_idempotently(): void
    {
        [$order] = $this->pendingTransaction('card', 'PAYPAL-ORDER-1');
        $this->fakeVerification('SUCCESS');
        $event = $this->event('PAYMENT.CAPTURE.COMPLETED', 'CAPTURE-1', 'PAYPAL-ORDER-1');

        $this->postJson(route('webhooks.paypal'), $event, $this->headers())->assertOk();
        $this->postJson(route('webhooks.paypal'), $event, $this->headers())->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $transaction = PaymentTransaction::query()->firstOrFail();
        $this->assertSame('paid', $transaction->status);
        $this->assertSame('CAPTURE-1', $transaction->gateway_transaction_id);

        $this->postJson(route('shop.checkout.confirm', $order->order_number), [
            'paypal_order_id' => 'PAYPAL-ORDER-1',
        ])->assertOk()->assertJsonStructure(['redirect']);
        $this->assertSame('paid', $transaction->fresh()->status);
        Mail::assertSent(OrderPaidMail::class, 1);
    }

    public function test_denied_webhook_only_fails_pending_transaction(): void
    {
        [$order, $transaction] = $this->pendingTransaction('apple_pay', 'PAYPAL-ORDER-2');
        $this->fakeVerification('SUCCESS');

        $this->postJson(
            route('webhooks.paypal'),
            $this->event('PAYMENT.CAPTURE.DENIED', 'CAPTURE-2', 'PAYPAL-ORDER-2'),
            $this->headers(),
        )->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('failed', $transaction->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_invalid_signature_does_not_update_payment(): void
    {
        [$order, $transaction] = $this->pendingTransaction('paypal', 'PAYPAL-ORDER-3');
        $this->fakeVerification('FAILURE');
        $this->postJson(
            route('webhooks.paypal'),
            $this->event('PAYMENT.CAPTURE.COMPLETED', 'CAPTURE-3', 'PAYPAL-ORDER-3'),
            $this->headers(),
        )->assertStatus(400);

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $transaction->fresh()->status);
    }

    public function test_amount_mismatch_does_not_update_payment(): void
    {
        [$order, $transaction] = $this->pendingTransaction('paypal', 'PAYPAL-ORDER-4');
        $event = $this->event('PAYMENT.CAPTURE.COMPLETED', 'CAPTURE-4', 'PAYPAL-ORDER-4');

        $event['resource']['amount']['value'] = '999.00';
        $this->fakeVerification('SUCCESS');
        $this->postJson(route('webhooks.paypal'), $event, $this->headers())->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $transaction->fresh()->status);
    }

    private function fakeVerification(string $status): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => $status,
            ], 200),
        ]);
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            'PAYPAL-CERT-URL' => 'https://api.paypal.com/cert.pem',
            'PAYPAL-TRANSMISSION-ID' => 'transmission-id',
            'PAYPAL-TRANSMISSION-SIG' => 'signature',
            'PAYPAL-TRANSMISSION-TIME' => '2026-06-19T00:00:00Z',
        ];
    }

    /** @return array<string, mixed> */
    private function event(string $type, string $captureId, string $orderId): array
    {
        return [
            'id' => 'WH-'.uniqid(),
            'event_type' => $type,
            'resource' => [
                'id' => $captureId,
                'amount' => ['value' => '120.50', 'currency_code' => 'USD'],
                'supplementary_data' => ['related_ids' => ['order_id' => $orderId]],
            ],
        ];
    }

    /** @return array{Order, PaymentTransaction} */
    private function pendingTransaction(string $method, string $paypalOrderId): array
    {
        $order = Order::query()->create([
            'order_number' => 'ORD-'.uniqid(),
            'customer_email' => 'customer@example.com',
            'customer_name' => 'Jane Customer',
            'shipping_address' => '123 Test St',
            'currency_code' => 'USD',
            'subtotal_usd' => 120.50,
            'discount_usd' => 0,
            'shipping_usd' => 0,
            'tax_usd' => 0,
            'total_display' => 120.50,
            'status' => 'pending',
        ]);
        $transaction = PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'payment_method' => $method,
            'gateway_transaction_id' => $paypalOrderId,
            'amount' => 120.50,
            'currency_code' => 'USD',
            'status' => 'pending',
        ]);

        return [$order, $transaction];
    }
}
