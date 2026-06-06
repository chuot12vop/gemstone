@extends('layouts.shop')

@php
    use App\Support\ProductVariantOptions;

    $activeVariants = $product->variants->where('is_active', true)->values();
    $defaultVariant = $activeVariants->firstWhere('is_default', true) ?: $activeVariants->first();
    $pickerVariants = ProductVariantOptions::toPickerJson($product, $activeVariants);
    $swatchColors = ProductVariantOptions::swatchColorsByOption($activeVariants);
    $colors = $activeVariants->pluck('option_color')->filter(fn (?string $c) => $c !== null && trim($c) !== '')->unique()->values();
    $sizes = ProductVariantOptions::sizes($activeVariants);
    $initialColor = $defaultVariant?->option_color ?? '';
    $initialSize = $defaultVariant?->option_size ?? '';

    $galleryImages = $defaultVariant
        ? $defaultVariant->galleryImages($product)
        : collect();
    if ($galleryImages->isEmpty()) {
        $mainImage = $product->image ?: ($product->thumbnail ?: asset('assets/img/placeholder.svg'));
        if ($mainImage) {
            $galleryImages->push($mainImage);
        }
        foreach ($product->productImages as $img) {
            if ($img->path && ! $galleryImages->contains($img->path)) {
                $galleryImages->push($img->path);
            }
        }
    }
    if ($galleryImages->isEmpty()) {
        $galleryImages->push(asset('assets/img/placeholder.svg'));
    }

    $displayPrice = $defaultVariant ? (float) $defaultVariant->price_usd : (float) $product->price_usd;
    $displayStock = $defaultVariant ? (int) $defaultVariant->stock : (int) $product->stock;
@endphp

@section('content')
<article class="product-detail product-detail--missoma" itemscope itemtype="https://schema.org/Product" data-product-detail>
    <div class="product-detail__grid">
        <div class="product-detail__media">
            <div class="pd-gallery" data-pd-gallery>
                <div class="pd-gallery__main" data-pd-zoom>
                    <img class="pd-gallery__main-img"
                         data-pd-main
                         itemprop="image"
                         src="{{ $galleryImages->first() }}"
                         alt="{{ $product->name }}"
                         width="800" height="800">
                    <span class="pd-gallery__zoom-lens" data-pd-lens aria-hidden="true"></span>
                    <button type="button" class="pd-gallery__nav pd-gallery__nav--prev" data-pd-prev aria-label="Previous image">&#10094;</button>
                    <button type="button" class="pd-gallery__nav pd-gallery__nav--next" data-pd-next aria-label="Next image">&#10095;</button>
                </div>
                @if($galleryImages->count() > 1)
                    <div class="pd-gallery__thumbs" role="list">
                        @foreach($galleryImages as $idx => $imgPath)
                            <button type="button"
                                    class="pd-gallery__thumb {{ $idx === 0 ? 'is-active' : '' }}"
                                    data-pd-thumb
                                    data-pd-src="{{ $imgPath }}"
                                    aria-label="Show image {{ $idx + 1 }}">
                                <img src="{{ $imgPath }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="product-detail__info">
            <a class="eyebrow product-detail__eyebrow" href="{{ route('shop.catalog.category', $product->category) }}">{{ $product->category->name }}</a>
            <h1 class="product-detail__title" itemprop="name">{{ $product->name }}</h1>

            @if(($reviewStats['count'] ?? 0) > 0)
                <a class="product-detail__rating" href="#reviews">
                    @include('shop.partials.stars', ['rating' => $reviewStats['average']])
                    <span>{{ number_format($reviewStats['average'], 1) }} ({{ $reviewStats['count'] }} {{ Str::plural('review', $reviewStats['count']) }})</span>
                </a>
            @endif

            @if($product->short_description)
                <div class="product-detail__description" data-pd-description>
                    <p class="lede product-detail__description-body is-collapsed" data-pd-description-body>{{ $product->short_description }}</p>
                    <button type="button"
                            class="product-detail__read-more"
                            data-pd-description-toggle
                            data-label-more="Read more"
                            data-label-less="Read less"
                            hidden>
                        Read more
                    </button>
                </div>
            @endif

            <p class="product-detail__price" itemprop="offers" itemscope itemtype="https://schema.org/Offer" data-pd-price>
                <span class="sr-only" itemprop="priceCurrency" content="USD">USD base</span>
                <span class="sr-only" itemprop="price" content="{{ $displayPrice }}"></span>
                {{ $currency->formatUsd($displayPrice) }}
            </p>

            @if($activeVariants->isNotEmpty())
                <div class="pd-variant-picker"
                     data-pd-variant-picker
                     data-variants='@json($pickerVariants)'
                     data-initial-color="{{ $initialColor }}"
                     data-initial-size="{{ $initialSize }}">
                    @if($colors->isNotEmpty())
                        <div class="pd-variant-picker__group">
                            <span class="pd-variant-picker__label">Colour</span>
                            <div class="pd-variant-picker__swatches">
                                @foreach($colors as $i => $color)
                                    @php
                                        $swatchHex = $swatchColors[$color] ?? null;
                                    @endphp
                                    <button type="button"
                                            class="pd-variant-picker__swatch{{ $swatchHex ? ' pd-variant-picker__swatch--has-color' : ' pd-variant-picker__swatch--text' }} {{ ($initialColor === $color) || ($i === 0 && $initialColor === '') ? 'is-active' : '' }}"
                                            data-pd-color="{{ $color }}"
                                            aria-label="{{ $color }}"
                                            title="{{ $color }}">
                                        @if($swatchHex)
                                            <span class="pd-variant-picker__swatch-dot pd-variant-picker__swatch-dot--custom"
                                                  style="background-color: {{ $swatchHex }};"
                                                  aria-hidden="true"></span>
                                        @else
                                            <span class="pd-variant-picker__swatch-label">{{ $color }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($sizes !== [])
                        <div class="pd-variant-picker__group">
                            <span class="pd-variant-picker__label">Size</span>
                            <div class="pd-variant-picker__sizes">
                                @foreach($sizes as $i => $size)
                                    <button type="button"
                                            class="pd-variant-picker__size {{ ($initialSize === $size) || ($i === 0 && $initialSize === '') ? 'is-active' : '' }}"
                                            data-pd-size="{{ $size }}">
                                        {{ $size }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <form class="add-form" method="post" action="{{ route('shop.cart.add') }}" data-pd-form>
                @csrf
                <input type="hidden" name="variant_id" value="{{ $defaultVariant?->id }}" data-pd-variant-id>
                <div class="pd-qty-stepper qty" data-pd-qty-stepper>
                    <span class="sr-only">Quantity</span>
                    <button type="button" class="pd-qty-stepper__btn" data-pd-qty-dec aria-label="Decrease quantity">−</button>
                    <input type="number" name="quantity" value="1" min="1" max="{{ max(1, $displayStock) }}" data-pd-qty readonly>
                    <button type="button" class="pd-qty-stepper__btn" data-pd-qty-inc aria-label="Increase quantity">+</button>
                </div>
                <button class="btn btn--primary"
                        type="submit"
                        data-pd-submit
                        data-label-default="Add to Bag"
                        {{ $displayStock < 1 ? 'disabled' : '' }}>
                    {{ $displayStock < 1 ? 'Out of stock' : 'Add to Bag' }}
                </button>
                <button class="btn btn--buy-now"
                        type="submit"
                        name="buy_now"
                        value="1"
                        data-pd-submit
                        data-label-default="Buy now"
                        {{ $displayStock < 1 ? 'disabled' : '' }}>
                    Buy now
                </button>
            </form>
            <p class="sr-only" data-pd-stock aria-live="polite">{{ $displayStock }} in stock</p>

            <div class="pd-accordion" data-pd-accordion>
                @if($product->description)
                    <article class="pd-accordion__item">
                        <button type="button" class="pd-accordion__btn" data-pd-acc-btn aria-expanded="false">
                            <span>Description</span>
                            <span class="pd-accordion__icon" aria-hidden="true"></span>
                        </button>
                        <div class="pd-accordion__panel" data-pd-acc-panel hidden itemprop="description">
                            <div class="prose">{!! $product->description !!}</div>
                        </div>
                    </article>
                @endif

                @if($product->productAttributes->isNotEmpty())
                    <article class="pd-accordion__item">
                        <button type="button" class="pd-accordion__btn" data-pd-acc-btn aria-expanded="false">
                            <span>Details</span>
                            <span class="pd-accordion__icon" aria-hidden="true"></span>
                        </button>
                        <div class="pd-accordion__panel" data-pd-acc-panel hidden>
                            @foreach($product->productAttributes as $attribute)
                                <p><strong>{{ $attribute->name }}:</strong> {!! nl2br(e($attribute->value)) !!}</p>
                            @endforeach
                        </div>
                    </article>
                @endif

                <article class="pd-accordion__item">
                    <button type="button" class="pd-accordion__btn" data-pd-acc-btn aria-expanded="false">
                        <span>Delivery &amp; Returns</span>
                        <span class="pd-accordion__icon" aria-hidden="true"></span>
                    </button>
                    <div class="pd-accordion__panel" data-pd-acc-panel hidden>
                        @include('shop.partials.product-detail-policies', ['policies' => $productPolicies ?? []])
                    </div>
                </article>
            </div>

            @include('shop.partials.product-upsell-bundle', ['product' => $product, 'currency' => $currency])
        </div>
    </div>

    @if(isset($bestSellerProducts) && $bestSellerProducts->isNotEmpty())
        <section class="product-detail__below product-detail__below--bestsellers" aria-labelledby="product-bestsellers-title">
            <h2 id="product-bestsellers-title" class="section__title section__title--center">Best sellers</h2>
            @include('shop.partials.home-product-slider', [
                'products' => $bestSellerProducts,
                'currency' => $currency,
                'sliderLabel' => 'Best sellers',
            ])
        </section>
    @endif

    @if($relatedProducts->isNotEmpty())
        <section class="product-detail__below product-detail__below--related" aria-labelledby="product-related-title">
            <h2 id="product-related-title" class="section__title section__title--center">You may also like</h2>
            @include('shop.partials.home-product-slider', [
                'products' => $relatedProducts,
                'currency' => $currency,
                'sliderLabel' => 'You may also like',
            ])
        </section>
    @endif

    @include('shop.partials.review-list', ['reviews' => $reviews, 'reviewStats' => $reviewStats])
</article>

<div class="product-cta-bar" data-pd-cta>
    <div class="product-cta-bar__inner">
        <div class="product-cta-bar__product">
            <img class="product-cta-bar__thumb" src="{{ $galleryImages->first() }}" alt="">
            <div class="product-cta-bar__meta">
                <p class="product-cta-bar__name">{{ $product->name }}</p>
                <p class="product-cta-bar__price" data-pd-cta-price>{{ $currency->formatUsd($displayPrice) }}</p>
            </div>
        </div>
        <form class="product-cta-bar__form" method="post" action="{{ route('shop.cart.add') }}">
            @csrf
            <input type="hidden" name="variant_id" value="{{ $defaultVariant?->id }}" data-pd-cta-variant-id>
            <div class="pd-qty-stepper qty product-cta-bar__qty" data-pd-cta-qty-stepper>
                <span class="sr-only">Quantity</span>
                <button type="button" class="pd-qty-stepper__btn" data-pd-cta-qty-dec aria-label="Decrease quantity">−</button>
                <input type="number" name="quantity" value="1" min="1" max="{{ max(1, $displayStock) }}" data-pd-cta-qty readonly>
                <button type="button" class="pd-qty-stepper__btn" data-pd-cta-qty-inc aria-label="Increase quantity">+</button>
            </div>
            <button class="btn btn--primary product-cta-bar__btn"
                    type="submit"
                    data-pd-submit
                    data-label-default="Add to Bag"
                    {{ $displayStock < 1 ? 'disabled' : '' }}>
                {{ $displayStock < 1 ? 'Out of stock' : 'Add to Bag' }}
            </button>
        </form>
    </div>
</div>
@endsection
