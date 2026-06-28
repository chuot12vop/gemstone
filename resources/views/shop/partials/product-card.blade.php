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
    $productDiscountPct = (float) ($product->discount ?? 0);
    $hasProductDiscount = $productDiscountPct > 0;
    $stickerUrl = trim((string) ($product->sticker ?? ''));
    $cardBadgeLabel = trim((string) ($product->card_badge_label ?? ''));
    $price = ProductPricing::display(
        $activeVariant ? (float) $activeVariant->price_usd : (float) $product->price_usd,
        $activeVariant?->compare_at_price_usd !== null ? (float) $activeVariant->compare_at_price_usd : null,
        $productDiscountPct
    );
    $displayPrice = $price['display'];
    $comparePrice = $price['compare'];
    $onSale = $price['on_sale'];
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
                <path d="M7.25 8.75h9.5a2 2 0 0 1 1.99 1.82l.58 6.4a2.75 2.75 0 0 1-2.74 3H7.42a2.75 2.75 0 0 1-2.74-3l.58-6.4a2 2 0 0 1 1.99-1.82Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M8.75 11.5V7.75a3.25 3.25 0 0 1 6.5 0v3.75" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <div class="shop-product-card__body">
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

        @if(count($swatches) > 1)
            <div class="shop-product-card__swatches" role="list" aria-label="Color options">
                @foreach($swatches as $i => $swatch)
                    @php
                        $swatchVariantSale = ! empty($swatch['on_sale']);
                        $swatchPrice = ProductPricing::display(
                            (float) $swatch['price_usd'],
                            $swatch['compare_at_price_usd'] !== null ? (float) $swatch['compare_at_price_usd'] : null,
                            $productDiscountPct
                        );
                        $swatchDisplay = $swatchPrice['display'];
                        $swatchCompare = $swatchPrice['compare'];
                        $swatchOnSale = $swatchPrice['on_sale'];
                    @endphp
                    <button type="button"
                            class="shop-product-card__swatch {{ $i === 0 ? 'is-active' : '' }}"
                            role="listitem"
                            aria-label="{{ $swatch['color'] }}"
                            data-product-card-swatch
                            data-variant-id="{{ $swatch['variant_id'] }}"
                            data-swatch-color="{{ $swatch['swatch_color'] ?? '' }}"
                            data-image="{{ $swatch['image'] }}"
                            data-hover-image="{{ $swatch['hover_image'] }}"
                            data-price-usd="{{ $swatchDisplay }}"
                            data-price-formatted="{{ $currency->formatUsd($swatchDisplay) }}"
                            data-compare-price-usd="{{ $swatchCompare ?? '' }}"
                            data-compare-price-formatted="{{ $swatchOnSale && $swatchCompare ? $currency->formatUsd($swatchCompare) : '' }}"
                            data-on-sale="{{ $swatchOnSale ? '1' : '0' }}"
                            data-variant-on-sale="{{ $swatchVariantSale ? '1' : '0' }}"
                            title="{{ $swatch['color'] }}">
                        <span class="shop-product-card__swatch-dot{{ !empty($swatch['swatch_color']) ? ' shop-product-card__swatch-dot--custom' : '' }}"
                              aria-hidden="true"
                              @if(!empty($swatch['swatch_color'])) style="background-color: {{ $swatch['swatch_color'] }};" @endif></span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @include('shop.partials.product-card-drawer', ['product' => $product, 'currency' => $currency])
</article>
