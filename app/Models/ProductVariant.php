<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'option_color',
        'swatch_color',
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

    /** @return HasMany<ProductVariantHoverImage> */
    public function hoverImages(): HasMany
    {
        return $this->hasMany(ProductVariantHoverImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return Collection<int, string>
     */
    public function galleryImages(?Product $product = null): Collection
    {
        $images = collect();
        $front = $this->frontImage($product);
        if ($front) {
            $images->push($front);
        }

        if ($this->relationLoaded('hoverImages')) {
            foreach ($this->hoverImages as $hover) {
                if ($hover->path && ! $images->contains($hover->path)) {
                    $images->push($hover->path);
                }
            }
        }

        if ($this->image_hover && ! $images->contains($this->image_hover)) {
            $images->push($this->image_hover);
        }

        return $images->values();
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
        if ($this->relationLoaded('hoverImages')) {
            $firstHover = $this->hoverImages->first()?->path;
            if ($firstHover) {
                return $firstHover;
            }
        }

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

    public function normalizedSwatchColor(): ?string
    {
        $hex = trim((string) ($this->swatch_color ?? ''));

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) ? strtolower($hex) : null;
    }
}
