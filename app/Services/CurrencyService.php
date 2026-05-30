<?php

namespace App\Services;

use App\Models\CurrencyRate;

class CurrencyService
{
    public const SESSION_KEY = 'currency_code';

    public function currentCode(): string
    {
        $c = session(self::SESSION_KEY, 'USD');
        $rate = CurrencyRate::query()->where('code', $c)->where('is_active', true)->first();

        return $rate ? $c : 'USD';
    }

    public function setCode(string $code): void
    {
        $rate = CurrencyRate::query()->where('code', $code)->where('is_active', true)->first();
        if ($rate) {
            session([self::SESSION_KEY => $code]);
        }
    }

    /**
     * @return list<array{code: string, label: string, symbol: string}>
     */
    public function activeCurrencies(): array
    {
        return CurrencyRate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(static fn (CurrencyRate $r) => [
                'code' => $r->code,
                'label' => $r->label,
                'symbol' => $r->symbol,
            ])
            ->all();
    }

    public function formatUsd(float $amountUsd): string
    {
        $code = $this->currentCode();
        $rate = CurrencyRate::query()->where('code', $code)->where('is_active', true)->first();
        if (! $rate) {
            return '$'.number_format($amountUsd, 2);
        }
        $local = $amountUsd * (float) $rate->rate_per_usd;

        return $rate->symbol.number_format($local, 2).' '.$code;
    }

    public function currentSymbol(): string
    {
        $rate = CurrencyRate::query()->where('code', $this->currentCode())->where('is_active', true)->first();

        return $rate?->symbol ?? '$';
    }

    public function currentRatePerUsd(): float
    {
        $rate = CurrencyRate::query()->where('code', $this->currentCode())->where('is_active', true)->first();

        return $rate ? (float) $rate->rate_per_usd : 1.0;
    }

    public function convertUsdToCurrent(float $amountUsd): float
    {
        $code = $this->currentCode();
        $rate = CurrencyRate::query()->where('code', $code)->where('is_active', true)->first();
        if (! $rate) {
            return $amountUsd;
        }

        return $amountUsd * (float) $rate->rate_per_usd;
    }
}
