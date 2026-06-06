<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @var list<string> */
    public const STATUSES = ['pending', 'paid', 'shipped', 'cancelled'];

    protected $fillable = [
        'user_id', 'order_number', 'customer_email', 'customer_name', 'shipping_address',
        'shipping_phone', 'marketing_sms_opt_in', 'marketing_email_opt_in',
        'currency_code', 'subtotal_usd', 'voucher_code', 'discount_usd', 'shipping_usd', 'tax_usd', 'total_display', 'status',
    ];

    protected $casts = [
        'subtotal_usd' => 'float',
        'discount_usd' => 'float',
        'shipping_usd' => 'float',
        'tax_usd' => 'float',
        'total_display' => 'float',
        'marketing_sms_opt_in' => 'boolean',
        'marketing_email_opt_in' => 'boolean',
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
