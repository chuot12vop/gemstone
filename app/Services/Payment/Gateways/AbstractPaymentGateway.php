<?php

namespace App\Services\Payment\Gateways;

use App\Models\Setting;
use App\Services\Payment\Contracts\PaymentGateway;

/**
 * Convenience base class for all gateways.
 *
 * - Caches the `payment_<code>_*` settings rows so each gateway only hits the
 *   `settings` table once per request.
 * - Provides sensible no-op defaults for the optional hooks.
 */
abstract class AbstractPaymentGateway implements PaymentGateway
{
    /** @var array<string, string>|null */
    private ?array $cachedSettings = null;

    public function description(): string
    {
        return '';
    }

    public function iconHtml(): string
    {
        return '';
    }

    public function isEnabled(): bool
    {
        return $this->setting('enabled') === '1';
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    public function validationRules(): array
    {
        return [];
    }

    public function customerFieldsView(): ?string
    {
        return null;
    }

    public function marksOrderPaidOnConfirm(): bool
    {
        return true;
    }

    /**
     * Read a single setting value for this gateway.
     *
     * Internally settings are stored as `payment_<code>_<key>` (e.g.
     * `payment_paypal_merchant_email`). This wrapper hides that convention so
     * concrete gateways stay tidy.
     */
    protected function setting(string $key, string $default = ''): string
    {
        if ($this->cachedSettings === null) {
            $prefix = 'payment_'.$this->code().'_';
            $this->cachedSettings = Setting::query()
                ->where('key', 'like', $prefix.'%')
                ->pluck('value', 'key')
                ->mapWithKeys(static fn ($value, $key) => [
                    substr((string) $key, strlen($prefix)) => (string) $value,
                ])
                ->all();
        }

        return $this->cachedSettings[$key] ?? $default;
    }
}
