@extends('layouts.shop')

@section('content')
<article class="product-detail" itemscope itemtype="https://schema.org/Product">
    <div class="product-detail__grid">
        <div class="product-detail__media">
            <img itemprop="image" src="{{ $product->image ?: asset('assets/img/placeholder.svg') }}"
                 alt="{{ $product->name }}" width="600" height="600">
        </div>
        <div class="product-detail__info">
            <p class="eyebrow"><a href="{{ route('shop.catalog.category', $product->category) }}">{{ $product->category->name }}</a></p>
            <h1 class="product-detail__title" itemprop="name">{{ $product->name }}</h1>
            <p class="product-detail__price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                <span itemprop="priceCurrency" content="USD">USD base</span>
                <span itemprop="price" content="{{ $product->price_usd }}"></span>
                {{ $currency->formatUsd((float) $product->price_usd) }}
            </p>
            @if($product->short_description)
                <p class="lede" itemprop="description">{{ $product->short_description }}</p>
            @endif
            @if($product->description)
                <div class="prose">{!! nl2br(e($product->description)) !!}</div>
            @endif

            <form class="add-form" method="post" action="{{ route('shop.cart.add') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <label class="qty">
                    <span class="sr-only">Quantity</span>
                    <input type="number" name="quantity" value="1" min="1" max="{{ max(1, $product->stock) }}">
                </label>
                <button class="btn btn--primary" type="submit" {{ $product->stock < 1 ? 'disabled' : '' }}>
                    {{ $product->stock < 1 ? 'Out of stock' : 'Add to cart' }}
                </button>
            </form>
            <p class="stock-note">{{ $product->stock }} in stock</p>
        </div>
    </div>
</article>
@endsection
