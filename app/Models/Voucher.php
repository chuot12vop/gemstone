<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'email',
        'percent',
        'used_at',
        'order_id',
    ];

    protected $casts = [
        'percent' => 'integer',
        'used_at' => 'datetime',
    ];

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function discountUsd(float $subtotalUsd): float
    {
        $pct = max(0, min(100, (int) $this->percent));

        return round($subtotalUsd * ($pct / 100), 2);
    }

    /** @return BelongsTo<Order, Voucher> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
