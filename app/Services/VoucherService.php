<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Voucher;
use Illuminate\Support\Str;

class VoucherService
{
    public const FOOTER_PERCENT = 10;
    public const WELCOME_PERCENT = 15;

    public function issueFooterVoucher(string $email): Voucher
    {
        return $this->issueVoucher($email, self::FOOTER_PERCENT);
    }

    public function issueWelcomeVoucher(string $email): Voucher
    {
        return $this->issueVoucher($email, self::WELCOME_PERCENT);
    }

    private function issueVoucher(string $email, int $percent): Voucher
    {
        $email = strtolower(trim($email));

        $existing = Voucher::query()
            ->where('email', $email)
            ->where('percent', $percent)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Voucher::query()->create([
            'code' => $this->generateUniqueCode($percent),
            'email' => $email,
            'percent' => $percent,
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

    public function markOrderVoucherUsed(Order $order): void
    {
        $code = trim((string) $order->voucher_code);
        if ($code === '') {
            return;
        }

        $voucher = Voucher::query()->where('code', strtoupper($code))->first();
        if ($voucher === null) {
            return;
        }

        if ($voucher->isUsed() && (int) $voucher->order_id !== (int) $order->id) {
            return;
        }

        $this->markUsed($voucher, $order);
    }

    public function release(Voucher $voucher): void
    {
        $voucher->used_at = null;
        $voucher->order_id = null;
        $voucher->save();
    }

    private function generateUniqueCode(int $percent = self::FOOTER_PERCENT): string
    {
        do {
            $code = 'GEM'.max(1, min(99, $percent)).'-'.strtoupper(Str::random(8));
        } while (Voucher::query()->where('code', $code)->exists());

        return $code;
    }
}
