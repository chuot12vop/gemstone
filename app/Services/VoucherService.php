<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Voucher;
use Illuminate\Support\Str;

class VoucherService
{
    public const FOOTER_PERCENT = 10;

    public function issueFooterVoucher(string $email): Voucher
    {
        $email = strtolower(trim($email));

        $existing = Voucher::query()
            ->where('email', $email)
            ->where('percent', self::FOOTER_PERCENT)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Voucher::query()->create([
            'code' => $this->generateUniqueCode(),
            'email' => $email,
            'percent' => self::FOOTER_PERCENT,
        ]);
    }

    public function findApplicable(string $code, string $checkoutEmail): ?Voucher
    {
        $code = strtoupper(trim($code));
        $checkoutEmail = strtolower(trim($checkoutEmail));

        if ($code === '' || $checkoutEmail === '') {
            return null;
        }

        $voucher = Voucher::query()->where('code', $code)->first();
        if ($voucher === null || $voucher->isUsed()) {
            return null;
        }

        if (strtolower(trim($voucher->email)) !== $checkoutEmail) {
            return null;
        }

        return $voucher;
    }

    public function markUsed(Voucher $voucher, Order $order): void
    {
        $voucher->used_at = now();
        $voucher->order_id = $order->id;
        $voucher->save();
    }

    public function release(Voucher $voucher): void
    {
        $voucher->used_at = null;
        $voucher->order_id = null;
        $voucher->save();
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'GEM10-'.strtoupper(Str::random(8));
        } while (Voucher::query()->where('code', $code)->exists());

        return $code;
    }
}
