<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * P2P transfer gateways: customer pays via QR, then uploads a screenshot.
 * Orders stay pending until staff verifies the proof in admin.
 */
abstract class AbstractProofTransferGateway extends AbstractPaymentGateway
{
    public function marksOrderPaidOnConfirm(): bool
    {
        return false;
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    protected function confirmValidationRules(): array
    {
        return [
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png,webp|max:6144',
            'transfer_reference' => 'nullable|string|max:120',
        ];
    }

    public function confirm(Order $order, Request $request): bool
    {
        $validated = $request->validate($this->confirmValidationRules());

        $proofPath = $this->storeProofImage($request->file('payment_proof'));
        if ($proofPath === null) {
            return false;
        }

        $tx = $order->paymentTransactions()->latest()->first();
        if ($tx === null) {
            return false;
        }

        $reference = trim((string) ($validated['transfer_reference'] ?? ''));
        $tx->proof_path = $proofPath;
        if ($reference !== '') {
            $tx->gateway_transaction_id = $reference;
        }
        $tx->notes = 'Payment proof submitted — awaiting verification';
        $tx->save();

        return true;
    }

    protected function storeProofImage(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (! in_array($extension, $allowed, true)) {
            $extension = 'jpg';
        }

        $fileName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs('payment-proofs/'.date('Y/m'), $fileName, 'public');

        if (! is_string($path) || $path === '') {
            return null;
        }

        return '/storage/'.$path;
    }

    protected function venmoPayUrl(Order $order): string
    {
        $username = ltrim(trim($this->setting('username')), '@');
        $amount = number_format((float) $order->total_display, 2, '.', '');
        $note = 'Order '.$order->order_number;

        return 'https://venmo.com/?txn=pay&audience=private&recipients='.rawurlencode($username)
            .'&amount='.rawurlencode($amount)
            .'&note='.rawurlencode($note);
    }
}
