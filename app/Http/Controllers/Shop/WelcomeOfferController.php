<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\PromoVoucherMail;
use App\Models\Setting;
use App\Services\VoucherService;
use App\Support\MarketingSubscribers;
use App\Support\PromoCheckoutSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WelcomeOfferController extends Controller
{
    public function __construct(
        private VoucherService $vouchers,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:190',
        ]);

        $email = strtolower(trim($validated['email']));
        $voucher = $this->vouchers->issueWelcomeVoucher($email);
        $siteName = (string) (Setting::query()->where('key', 'site_name')->value('value')
            ?: config('app.name'));

        try {
            Mail::to($email)->send(new PromoVoucherMail($voucher, $siteName));
        } catch (\Throwable $e) {
            Log::error('Welcome offer email failed', [
                'email' => $email,
                'code' => $voucher->code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'We could not send your email right now. Please try again shortly.',
            ], 503);
        }

        MarketingSubscribers::subscribe($email);
        PromoCheckoutSession::rememberSubscriber($email, $voucher);

        return response()->json([
            'ok' => true,
            'message' => 'Check your inbox for your '.$voucher->percent.'% off code.',
        ]);
    }
}
