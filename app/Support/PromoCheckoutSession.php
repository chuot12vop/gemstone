<?php

namespace App\Support;

use App\Models\Voucher;

/**
 * Session keys used to prefill checkout after a 10% promo signup.
 */
final class PromoCheckoutSession
{
    public const EMAIL_KEY = 'promo.subscriber_email';

    public static function rememberSubscriber(string $email, ?Voucher $voucher = null): void
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return;
        }

        session([self::EMAIL_KEY => $email]);

        if ($voucher !== null && ! $voucher->isUsed()) {
            session(['checkout.voucher_id' => $voucher->id]);
        }
    }

    public static function subscriberEmail(): string
    {
        $email = session(self::EMAIL_KEY);

        return is_string($email) ? strtolower(trim($email)) : '';
    }
}
