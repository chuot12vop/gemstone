<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'option_color',
        'option_size',
        'price_usd',
        'compare_at_price_usd',
        'stock',
        'image',
        'image_hover',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_usd' => 'float',
        'compare_at_price_usd' => 'float',
        'stock' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** @return BelongsTo<Product, ProductVariant> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function label(): string
    {
        $parts = array_filter([
            $this->option_color,
            $this->option_size,
        ], fn (?string $v) => $v !== null && trim($v) !== '');

        return $parts !== [] ? implode(' / ', $parts) : 'Default';
    }

    public function frontImage(?Product $product = null): ?string
    {
        if ($this->image) {
            return $this->image;
        }

        $product ??= $this->relationLoaded('product') ? $this->product : null;

        return $product?->image ?: $product?->thumbnail;
    }

    public function hoverImage(?Product $product = null): ?string
    {
        if ($this->image_hover) {
            return $this->image_hover;
        }

        $product ??= $this->relationLoaded('product') ? $this->product : null;
        if (! $product) {
            return null;
        }

        $secondGallery = $product->relationLoaded('productImages')
            ? $product->productImages->skip(1)->first()?->path
            : null;

        return $secondGallery ?: $this->frontImage($product);
    }
}
