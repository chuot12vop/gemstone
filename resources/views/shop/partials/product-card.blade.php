@php
    use App\Support\ProductPricing;
    use App\Support\ProductVariantOptions;

    $variants = $product->relationLoaded('variants')
        ? $product->variants->where('is_active', true)
        : collect();
    $defaultVariant = $variants->firstWhere('is_default', true) ?: $variants->first();
    $swatches = ProductVariantOptions::colorSwatches($product, $variants);
    $activeVariant = $defaultVariant;
    $frontImage = $activeVariant?->frontImage($product) ?: ($product->image ?: asset('assets/img/placeholder.svg'));
    $hoverImage = $activeVariant?->hoverImage($product) ?: $frontImage;
    $basePrice = $activeVariant ? (float) $activeVariant->price_usd : (float) $product->price_usd;
    $variantOnSale = $activeVariant && ProductVariantOptions::isOnSale($activeVariant);
    $productDiscountPct = (float) ($product->discount ?? 0);
    $hasProductDiscount = $productDiscountPct > 0;
    $stickerUrl = trim((string) ($product->sticker ?? ''));
    $cardBadgeLabel = trim((string) ($product->card_badge_label ?? ''));
    if ($variantOnSale) {
        $displayPrice = $basePrice;
        $comparePrice = (float) $activeVariant->compare_at_price_usd;
        $onSale = true;
    } elseif ($hasProductDiscount) {
        $displayPrice = ProductPricing::afterPercentDiscount($basePrice, $productDiscountPct);
        $comparePrice = $basePrice;
        $onSale = true;
    } else {
        $displayPrice = $basePrice;
        $comparePrice = null;
        $onSale = false;
    }
    $inStock = ($activeVariant?->stock ?? $product->stock) > 0;
    $cardVariants = ProductVariantOptions::toPickerJson($product, $variants);
@endphp
<article class="shop-product-card shop-product-card--missoma"
         data-product-card
         data-product-id="{{ $product->id }}"
         data-product-url="{{ route('shop.product', $product) }}"
         data-product-discount="{{ $hasProductDiscount ? $productDiscountPct : 0 }}"
         data-variants='@json($cardVariants)'>
    <div class="shop-product-card__media-wrap">
        <a href="{{ route('shop.product', $product) }}" class="shop-product-card__media" data-product-card-media>
            <img class="shop-product-card__img shop-product-card__img--front"
                 src="{{ $frontImage }}"
                 alt="{{ $product->name }}"
                 width="400"
                 height="400"
                 loading="lazy"
                 data-product-card-front>
            @if($hoverImage && $hoverImage !== $frontImage)
                <img class="shop-product-card__img shop-product-card__img--back"
                     src="{{ $hoverImage }}"
                     alt=""
                     width="400"
                     height="400"
                     loading="lazy"
                     aria-hidden="true"
                     data-product-card-back>
            @endif
        </a>

        @if($cardBadgeLabel !== '')
            <span class="shop-product-card__badge shop-product-card__badge--label">{{ $cardBadgeLabel }}</span>
        @endif

        @if($stickerUrl !== '')
            <span class="shop-product-card__sticker" aria-hidden="true">
                <img class="shop-product-card__sticker-img"
                     src="{{ $stickerUrl }}"
                     alt=""
                     width="72"
                     height="72"
                     loading="lazy">
            </span>
        @endif

        <button class="shop-product-card__cart-btn"
                type="button"
                aria-label="Thêm giỏ hàng"
                title="Thêm giỏ hàng"
                {{ ! $inStock ? 'disabled' : '' }}
                data-pc-drawer-open>
            <svg class="shop-product-card__cart-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M6 6h15l-1.5 9h-12z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                <circle cx="9" cy="20" r="1.25" fill="currentColor"/>
                <circle cx="18" cy="20" r="1.25" fill="currentColor"/>
                <path d="M6 6L5 3H2" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <div class="shop-product-card__body">
        @if(count($swatches) > 1)
            <div class="shop-product-card__swatches" role="list" aria-label="Color options">
                @foreach($swatches as $i => $swatch)
                    @php
                        $swatchBase = (float) $swatch['price_usd'];
                        $swatchVariantSale = ! empty($swatch['on_sale']);
                        if ($swatchVariantSale) {
                            $swatchDisplay = $swatchBase;
                            $swatchCompare = (float) $swatch['compare_at_price_usd'];
                            $swatchOnSale = true;
                        } elseif ($hasProductDiscount) {
                            $swatchDisplay = ProductPricing::afterPercentDiscount($swatchBase, $productDiscountPct);
                            $swatchCompare = $swatchBase;
                            $swatchOnSale = true;
                        } else {
                            $swatchDisplay = $swatchBase;
                            $swatchCompare = null;
                            $swatchOnSale = false;
                        }
                    @endphp
                    <button type="button"
                            class="shop-product-card__swatch {{ $i === 0 ? 'is-active' : '' }}"
                            role="listitem"
                            aria-label="{{ $swatch['color'] }}"
                            data-product-card-swatch
                            data-variant-id="{{ $swatch['variant_id'] }}"
                            data-image="{{ $swatch['image'] }}"
                            data-hover-image="{{ $swatch['hover_image'] }}"
                            data-price-usd="{{ $swatchDisplay }}"
                            data-price-formatted="{{ $currency->formatUsd($swatchDisplay) }}"
                            data-compare-price-usd="{{ $swatchCompare ?? '' }}"
                            data-compare-price-formatted="{{ $swatchOnSale && $swatchCompare ? $currency->formatUsd($swatchCompare) : '' }}"
                            data-on-sale="{{ $swatchOnSale ? '1' : '0' }}"
                            data-variant-on-sale="{{ $swatchVariantSale ? '1' : '0' }}"
                            title="{{ $swatch['color'] }}">
                        <span class="shop-product-card__swatch-dot" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
        @endif

        <h3 class="shop-product-card__title">
            <a href="{{ route('shop.product', $product) }}">{{ $product->name }}</a>
        </h3>

        <div class="shop-product-card__prices{{ $onSale ? ' shop-product-card__prices--sale' : '' }}">
            <span class="shop-product-card__price" data-product-card-price>{{ $currency->formatUsd($displayPrice) }}</span>
            @if($comparePrice !== null)
                <span class="shop-product-card__compare" data-product-card-compare>{{ $currency->formatUsd($comparePrice) }}</span>
            @else
                <span class="shop-product-card__compare" data-product-card-compare hidden></span>
            @endif
        </div>
    </div>

    @include('shop.partials.product-card-drawer', ['product' => $product, 'currency' => $currency])
</article>
