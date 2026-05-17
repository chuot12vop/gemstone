<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'customer_email', 'customer_name', 'shipping_address',
        'currency_code', 'subtotal_usd', 'total_display', 'status',
    ];

    protected $casts = [
        'subtotal_usd' => 'float',
        'total_display' => 'float',
    ];

    /** @return BelongsTo<User, Order> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<PaymentTransaction> */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->latest();
    }

    /** @return HasMany<Review> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
