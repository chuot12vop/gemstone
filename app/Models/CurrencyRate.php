<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    protected $fillable = ['code', 'label', 'symbol', 'rate_per_usd', 'sort_order', 'is_active'];

    protected $casts = [
        'rate_per_usd' => 'float',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
