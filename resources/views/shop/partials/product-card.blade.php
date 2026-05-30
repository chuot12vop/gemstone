@php
    use App\Support\ProductVariantOptions;

    $variants = $product->relationLoaded('variants')
        ? $product->variants->where('is_active', true)
        : collect();
    $defaultVariant = $variants->firstWhere('is_default', true) ?: $variants->first();
    $swatches = ProductVariantOptions::colorSwatches($product, $variants);
    $activeVariant = $defaultVariant;
    $frontImage = $activeVariant?->frontImage($product) ?: ($product->image ?: asset('assets/img/placeholder.svg'));
    $hoverImage = $activeVariant?->hoverImage($product) ?: $frontImage;
    $displayPrice = $activeVariant ? (float) $activeVariant->price_usd : (float) $product->price_usd;
    $comparePrice = $activeVariant && ProductVariantOptions::isOnSale($activeVariant)
        ? (float) $activeVariant->compare_at_price_usd
        : null;
    $onSale = $comparePrice !== null;
    $cardBadgeLabel = trim((string) ($product->card_badge_label ?? ''));
    $inStock = ($activeVariant?->stock ?? $product->stock) > 0;
    $cardVariants = ProductVariantOptions::toPickerJson($product, $variants);
@endphp
<article class="shop-product-card shop-product-card--missoma"
         data-product-card
         data-product-id="{{ $product->id }}"
         data-product-url="{{ route('shop.product', $product) }}"
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

        @if($onSale)
            <span class="shop-product-card__badge shop-product-card__badge--sale" data-product-card-sale-badge>SALE</span>
        @else
            <span class="shop-product-card__badge shop-product-card__badge--sale" data-product-card-sale-badge hidden>SALE</span>
        @endif

        @if($cardBadgeLabel !== '')
            <span class="shop-product-card__badge shop-product-card__badge--promo">{{ $cardBadgeLabel }}</span>
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
                    <button type="button"
                            class="shop-product-card__swatch {{ $i === 0 ? 'is-active' : '' }}"
                            role="listitem"
                            aria-label="{{ $swatch['color'] }}"
                            data-product-card-swatch
                            data-variant-id="{{ $swatch['variant_id'] }}"
                            data-image="{{ $swatch['image'] }}"
                            data-hover-image="{{ $swatch['hover_image'] }}"
                            data-price-usd="{{ $swatch['price_usd'] }}"
                            data-price-formatted="{{ $currency->formatUsd($swatch['price_usd']) }}"
                            data-compare-price-usd="{{ $swatch['compare_at_price_usd'] ?? '' }}"
                            data-compare-price-formatted="{{ ! empty($swatch['on_sale']) && $swatch['compare_at_price_usd'] ? $currency->formatUsd($swatch['compare_at_price_usd']) : '' }}"
                            data-on-sale="{{ ! empty($swatch['on_sale']) ? '1' : '0' }}"
                            title="{{ $swatch['color'] }}">
                        <span class="shop-product-card__swatch-dot" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
        @endif

        <h3 class="shop-product-card__title">
            <a href="{{ route('shop.product', $product) }}">{{ $product->name }}</a>
        </h3>

        <div class="shop-product-card__prices">
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
