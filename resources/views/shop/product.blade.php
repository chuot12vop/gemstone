@extends('layouts.shop')

@php
    $mainImage = $product->image ?: ($product->thumbnail ?: asset('assets/img/placeholder.svg'));
    $galleryImages = collect();
    if ($mainImage) {
        $galleryImages->push($mainImage);
    }
    foreach ($product->productImages as $img) {
        if ($img->path && ! $galleryImages->contains($img->path)) {
            $galleryImages->push($img->path);
        }
    }
    if ($galleryImages->isEmpty()) {
        $galleryImages->push(asset('assets/img/placeholder.svg'));
    }
@endphp

@section('content')
<article class="product-detail" itemscope itemtype="https://schema.org/Product" data-product-detail>
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
            <p class="eyebrow">
                <a href="{{ route('shop.catalog.category', $product->category) }}">{{ $product->category->name }}</a>
            </p>
            <h1 class="product-detail__title" itemprop="name">{{ $product->name }}</h1>

            <p class="product-detail__price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                <span class="sr-only" itemprop="priceCurrency" content="USD">USD base</span>
                <span class="sr-only" itemprop="price" content="{{ $product->price_usd }}"></span>
                {{ $currency->formatUsd((float) $product->price_usd) }}
            </p>

            @if($product->short_description)
                <p class="lede" itemprop="description">{{ $product->short_description }}</p>
            @endif

            <form class="add-form" method="post" action="{{ route('shop.cart.add') }}" data-pd-form>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <label class="qty">
                    <span class="sr-only">Quantity</span>
                    <input type="number" name="quantity" value="1" min="1" max="{{ max(1, $product->stock) }}" data-pd-qty>
                </label>
                <button class="btn btn--primary" type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}>
                    {{ $product->stock < 1 ? 'Out of stock' : 'Add to cart' }}
                </button>
            </form>
            <p class="stock-note">{{ $product->stock }} in stock</p>

            @if($product->description)
                <div class="prose product-detail__description">
                    <h2 class="product-detail__section-title">Description</h2>
                    {!! nl2br(e($product->description)) !!}
                </div>
            @endif
        </div>
    </div>

    @if($relatedProducts->isNotEmpty())
        <section class="product-related">
            <h2 class="section__title section__title--center">You may also like</h2>
            <ul class="spotlight-grid">
                @foreach($relatedProducts as $rp)
                    <li>
                        <article class="spotlight-card">
                            <a href="{{ route('shop.product', $rp) }}" class="spotlight-card__media">
                                <img src="{{ $rp->image ?: asset('assets/img/placeholder.svg') }}" alt="{{ $rp->name }}" width="400" height="400" loading="lazy">
                            </a>
                            <div class="spotlight-card__body">
                                <h3 class="spotlight-card__title">
                                    <a href="{{ route('shop.product', $rp) }}">{{ $rp->name }}</a>
                                </h3>
                                <p class="spotlight-card__price">{{ $currency->formatUsd((float) $rp->price_usd) }}</p>
                                <form class="spotlight-card__cart" method="post" action="{{ route('shop.cart.add') }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $rp->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button class="btn btn--primary btn--small spotlight-card__add" type="submit" {{ $rp->stock < 1 ? 'disabled' : '' }}>
                                        {{ $rp->stock < 1 ? 'Out of stock' : 'Add to cart' }}
                                    </button>
                                </form>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</article>

<div class="product-cta-bar" data-pd-cta>
    <div class="product-cta-bar__inner">
        <div class="product-cta-bar__product">
            <img class="product-cta-bar__thumb" src="{{ $galleryImages->first() }}" alt="">
            <div class="product-cta-bar__meta">
                <p class="product-cta-bar__name">{{ $product->name }}</p>
                <p class="product-cta-bar__price">{{ $currency->formatUsd((float) $product->price_usd) }}</p>
            </div>
        </div>
        <form class="product-cta-bar__form" method="post" action="{{ route('shop.cart.add') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <label class="qty product-cta-bar__qty">
                <span class="sr-only">Quantity</span>
                <input type="number" name="quantity" value="1" min="1" max="{{ max(1, $product->stock) }}" data-pd-cta-qty>
            </label>
            <button class="btn btn--primary product-cta-bar__btn" type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}>
                {{ $product->stock < 1 ? 'Out of stock' : 'Add to cart' }}
            </button>
        </form>
    </div>
</div>
@endsection
