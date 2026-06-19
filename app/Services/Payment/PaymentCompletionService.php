<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\OrderMailService;
use Illuminate\Support\Facades\DB;

class PaymentCompletionService
{
    public function __construct(private OrderMailService $orderMail)
    {
    }

    public function complete(Order $order, string $captureId): bool
    {
        $transitioned = DB::transaction(function () use ($order, $captureId) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $transaction = $lockedOrder->paymentTransactions()->first();

            if ($lockedOrder->status === 'paid') {
                return false;
            }

            if ($lockedOrder->status !== 'pending' || ! $transaction) {
                return false;
            }

            $lockedOrder->status = 'paid';
            $lockedOrder->save();

            $transaction->status = 'paid';
            $transaction->paid_at = now();
            $transaction->gateway_transaction_id = $captureId;
            $transaction->notes = 'Payment captured';
            $transaction->save();

            return true;
        });

        if ($transitioned) {
            $this->orderMail->sendPaid($order->fresh(['items']));
        }

        return $transitioned;
    }

    public function failPending(PaymentTransaction $transaction, string $notes): bool
    {
        return DB::transaction(function () use ($transaction, $notes) {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($locked->status !== 'pending') {
                return false;
            }

            $locked->status = 'failed';
            $locked->notes = $notes;
            $locked->save();

            return true;
        });
    }
}
