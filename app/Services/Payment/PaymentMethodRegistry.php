<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGateway;

/**
 * Lookup service that hides the details of how gateways are registered.
 *
 * Inject the registry anywhere you need the list of methods or to resolve a
 * single gateway by its code. Adding a new gateway only requires tagging it in
 * {@see \App\Providers\PaymentServiceProvider} — callers stay untouched.
 */
class PaymentMethodRegistry
{
    /** @var array<string, PaymentGateway> */
    private array $gateways = [];

    /**
     * @param  iterable<PaymentGateway>  $gateways
     */
    public function __construct(iterable $gateways)
    {
        foreach ($gateways as $gateway) {
            $this->gateways[$gateway->code()] = $gateway;
        }
    }

    /**
     * @return array<string, PaymentGateway>
     */
    public function all(): array
    {
        return $this->gateways;
    }

    /**
     * Methods enabled in the admin payment settings, in registration order.
     *
     * @return array<int, PaymentGateway>
     */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->gateways,
            static fn (PaymentGateway $g) => $g->isEnabled(),
        ));
    }

    public function find(string $code): ?PaymentGateway
    {
        return $this->gateways[$code] ?? null;
    }

    public function findEnabled(string $code): ?PaymentGateway
    {
        $gateway = $this->find($code);

        return $gateway && $gateway->isEnabled() ? $gateway : null;
    }
}
