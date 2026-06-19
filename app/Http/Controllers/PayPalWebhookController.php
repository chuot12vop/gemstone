<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Services\Payment\PaymentCompletionService;
use App\Services\Payment\PayPal\PayPalApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    private const ONLINE_METHODS = ['card', 'paypal', 'apple_pay'];

    public function __construct(private PaymentCompletionService $completion)
    {
    }

    public function __invoke(Request $request): Response
    {
        $event = $request->json()->all();
        $client = $this->apiClient();
        $webhookId = $this->setting('payment_paypal_webhook_id');

        if ($client === null || ! $client->verifyWebhookSignature($this->signatureHeaders($request), $event, $webhookId)) {
            Log::warning('Rejected PayPal webhook with invalid signature', [
                'event_id' => $event['id'] ?? null,
            ]);

            return response('Invalid PayPal webhook signature.', 400);
        }

        $eventType = strtoupper((string) ($event['event_type'] ?? ''));
        if (! in_array($eventType, ['PAYMENT.CAPTURE.COMPLETED', 'PAYMENT.CAPTURE.DENIED'], true)) {
            return response('', 200);
        }

        $resource = is_array($event['resource'] ?? null) ? $event['resource'] : [];
        $captureId = trim((string) ($resource['id'] ?? ''));
        $orderId = trim((string) data_get($resource, 'supplementary_data.related_ids.order_id', ''));
        $transaction = $this->findTransaction($orderId, $captureId);

        if (! $transaction || ! $transaction->order) {
            Log::warning('PayPal webhook transaction not found', [
                'event_id' => $event['id'] ?? null,
                'paypal_order_id' => $orderId,
                'capture_id' => $captureId,
            ]);

            return response('', 200);
        }

        if (! $this->amountMatches($transaction, $resource)) {
            Log::warning('PayPal webhook amount mismatch', [
                'event_id' => $event['id'] ?? null,
                'order_number' => $transaction->order->order_number,
            ]);

            return response('', 200);
        }

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED' && $captureId !== '') {
            $this->completion->complete($transaction->order, $captureId);
        } elseif ($eventType === 'PAYMENT.CAPTURE.DENIED') {
            $this->completion->failPending($transaction, 'PayPal capture denied');
        }

        return response('', 200);
    }

    private function findTransaction(string $orderId, string $captureId): ?PaymentTransaction
    {
        $ids = array_values(array_filter([$orderId, $captureId]));
        if ($ids === []) {
            return null;
        }

        return PaymentTransaction::query()
            ->with('order')
            ->whereIn('payment_method', self::ONLINE_METHODS)
            ->whereIn('gateway_transaction_id', $ids)
            ->latest()
            ->first();
    }

    /** @param  array<string, mixed>  $resource */
    private function amountMatches(PaymentTransaction $transaction, array $resource): bool
    {
        $value = trim((string) data_get($resource, 'amount.value', ''));
        $currency = strtoupper(trim((string) data_get($resource, 'amount.currency_code', '')));
        if ($value === '' || $currency === '') {
            return false;
        }

        return $currency === strtoupper((string) $transaction->currency_code)
            && bccomp(number_format((float) $transaction->amount, 2, '.', ''), $value, 2) === 0;
    }

    /** @return array<string, string|null> */
    private function signatureHeaders(Request $request): array
    {
        return [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
        ];
    }

    private function apiClient(): ?PayPalApiClient
    {
        return PayPalApiClient::fromSettings(
            $this->setting('payment_paypal_client_id'),
            $this->setting('payment_paypal_client_secret'),
            $this->setting('payment_paypal_sandbox', '1') === '1',
        );
    }

    private function setting(string $key, string $default = ''): string
    {
        $value = Setting::query()->where('key', $key)->value('value');

        return $value === null ? $default : (string) $value;
    }
}
