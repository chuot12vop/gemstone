<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'payment_method',
        'gateway_transaction_id',
        'amount',
        'currency_code',
        'status',
        'notes',
        'proof_path',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
    ];

    /** @return BelongsTo<Order, PaymentTransaction> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
