<?php

namespace App\Providers;

use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Gateways\ApplePayGateway;
use App\Services\Payment\Gateways\CashAppGateway;
use App\Services\Payment\Gateways\PayPalGateway;
use App\Services\Payment\Gateways\VenmoGateway;
use App\Services\Payment\Gateways\ZelleGateway;
use App\Services\Payment\PaymentMethodRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Wires every payment gateway into the container under a single tag.
 *
 * To plug in a new gateway:
 *   1. Implement {@see PaymentGateway} (extend AbstractPaymentGateway).
 *   2. Add the FQCN to the {@see self::GATEWAYS} array below.
 *   3. (Optional) add settings in admin → Payments.
 *
 * No other file in the codebase needs to change.
 */
class PaymentServiceProvider extends ServiceProvider
{
    /** @var array<int, class-string<PaymentGateway>> */
    private const GATEWAYS = [
        PayPalGateway::class,
        ApplePayGateway::class,
        VenmoGateway::class,
        CashAppGateway::class,
        ZelleGateway::class,
    ];

    public function register(): void
    {
        foreach (self::GATEWAYS as $class) {
            $this->app->singleton($class);
        }

        $this->app->tag(self::GATEWAYS, 'payment.gateway');

        $this->app->singleton(PaymentMethodRegistry::class, function ($app) {
            return new PaymentMethodRegistry($app->tagged('payment.gateway'));
        });
    }
}
