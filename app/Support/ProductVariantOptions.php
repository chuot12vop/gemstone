<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class ProductVariantOptions
{
    public static function isOnSale(ProductVariant $variant): bool
    {
        return $variant->compare_at_price_usd !== null
            && (float) $variant->compare_at_price_usd > (float) $variant->price_usd + 0.001;
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @return array<int, array{color: string, variant_id: int, image: ?string, hover_image: ?string, price_usd: float, compare_at_price_usd: ?float, on_sale: bool}>
     */
    public static function colorSwatches(Product $product, Collection $variants): array
    {
        $active = $variants->where('is_active', true)->values();
        $swatches = [];

        $groups = $active->groupBy(static function (ProductVariant $variant): string {
            $color = trim((string) ($variant->option_color ?? ''));

            return $color !== '' ? $color : '__default__';
        });

        foreach ($groups as $key => $group) {
            /** @var ProductVariant $variant */
            $variant = $group->first(static fn (ProductVariant $v) => $v->normalizedSwatchColor() !== null)
                ?? $group->first();
            $color = trim((string) ($variant->option_color ?? ''));

            $swatches[] = [
                'color' => $color !== '' ? $color : 'Default',
                'swatch_color' => $variant->normalizedSwatchColor(),
                'variant_id' => $variant->id,
                'image' => $variant->frontImage($product),
                'hover_image' => $variant->hoverImage($product),
                'price_usd' => (float) $variant->price_usd,
                'compare_at_price_usd' => $variant->compare_at_price_usd !== null
                    ? (float) $variant->compare_at_price_usd
                    : null,
                'on_sale' => self::isOnSale($variant),
            ];
        }

        return $swatches;
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @return array<int, string>
     */
    public static function sizes(Collection $variants, ?string $color = null): array
    {
        $active = $variants->where('is_active', true);

        if ($color !== null && trim($color) !== '') {
            $active = $active->filter(fn (ProductVariant $v) => trim((string) ($v->option_color ?? '')) === $color);
        }

        return $active
            ->pluck('option_size')
            ->filter(fn (?string $size) => $size !== null && trim($size) !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     */
    public static function resolve(Collection $variants, ?string $color, ?string $size): ?ProductVariant
    {
        $active = $variants->where('is_active', true)->values();

        if ($active->isEmpty()) {
            return null;
        }

        $match = $active->first(function (ProductVariant $variant) use ($color, $size) {
            $variantColor = trim((string) ($variant->option_color ?? ''));
            $variantSize = trim((string) ($variant->option_size ?? ''));

            $colorMatch = $color === null || $color === '' || $variantColor === $color;
            $sizeMatch = $size === null || $size === '' || $variantSize === $size;

            return $colorMatch && $sizeMatch;
        });

        return $match ?: $active->firstWhere('is_default', true) ?: $active->first();
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @return array<string, string|null>
     */
    public static function swatchColorsByOption(Collection $variants): array
    {
        $map = [];
        $groups = $variants->where('is_active', true)->groupBy(static function (ProductVariant $variant): string {
            return trim((string) ($variant->option_color ?? ''));
        });

        foreach ($groups as $color => $group) {
            if ($color === '') {
                continue;
            }

            /** @var ProductVariant $variant */
            $variant = $group->first(static fn (ProductVariant $v) => $v->normalizedSwatchColor() !== null)
                ?? $group->first();
            $map[$color] = $variant->normalizedSwatchColor();
        }

        return $map;
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @return array<int, array<string, mixed>>
     */
    public static function toPickerJson(Product $product, Collection $variants): array
    {
        return $variants
            ->where('is_active', true)
            ->sortBy(['sort_order', 'id'])
            ->values()
            ->map(function (ProductVariant $variant) use ($product) {
                $images = $variant->galleryImages($product)->all();

                return [
                    'id' => $variant->id,
                    'color' => $variant->option_color,
                    'size' => $variant->option_size,
                    'label' => $variant->label(),
                    'swatch_color' => $variant->normalizedSwatchColor(),
                    'price_usd' => (float) $variant->price_usd,
                    'compare_at_price_usd' => $variant->compare_at_price_usd !== null
                        ? (float) $variant->compare_at_price_usd
                        : null,
                    'stock' => (int) $variant->stock,
                    'is_default' => (bool) $variant->is_default,
                    'image' => $variant->frontImage($product),
                    'hover_image' => $variant->hoverImage($product),
                    'images' => $images !== [] ? $images : array_filter([$variant->frontImage($product)]),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     */
    public static function syncProductDenormalized(Product $product, Collection $variants): void
    {
        $active = $variants->where('is_active', true);

        if ($active->isEmpty()) {
            return;
        }

        $default = $active->firstWhere('is_default', true) ?: $active->first();

        $product->update([
            'price_usd' => (float) $active->min('price_usd'),
            'stock' => (int) $active->sum('stock'),
            'image' => $default?->frontImage($product) ?: $product->image,
        ]);
    }
}
