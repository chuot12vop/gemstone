<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'category_id', 'name', 'slug', 'short_description', 'description',
        'price_usd', 'image', 'thumbnail', 'stock', 'is_active', 'meta_title', 'meta_description',
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

    /** @return HasMany<ProductAttribute, Product> */
    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<ProductImage, Product> */
    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<Review> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    /** @return HasMany<Review> */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', Review::STATUS_APPROVED)->latest();
    }
}
