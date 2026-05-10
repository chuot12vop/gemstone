<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'quantity', 'unit_price_usd', 'line_total_usd',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_usd' => 'float',
        'line_total_usd' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasOne<Review> */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
