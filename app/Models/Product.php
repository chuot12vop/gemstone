<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'category_id', 'name', 'slug', 'short_description', 'description',
        'price_usd', 'image', 'stock', 'is_active', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'price_usd' => 'float',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Category, Product> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
