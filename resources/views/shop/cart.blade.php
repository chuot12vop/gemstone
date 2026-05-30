@extends('layouts.shop')

@section('mainClass', 'site-main--cart')

@section('content')
<div class="cart-page" data-cart-page>
    <header class="cart-page__head">
        <h1 class="cart-page__title" data-cart-page-title>
            @if($cartCount > 0)
                Your bag ({{ $cartCount }})
            @else
                Your bag
            @endif
        </h1>
    </header>

    @include('shop.checkout._free-shipping-bar', [
        'shippingProgress' => $shippingProgress,
        'currency' => $currency,
    ])

    @if(count($lines) === 0)
        <div class="cart-page__empty">
            <p class="cart-page__empty-msg">Your bag is empty.</p>
            @guest
                <p class="cart-page__empty-auth">
                    Have an account? <a href="{{ route('login') }}">Log in</a> to check out faster.
                </p>
            @endguest
            <a class="btn btn--primary cart-page__empty-cta" href="{{ route('shop.products.index') }}">Continue shopping</a>
        </div>
        @include('shop.cart._trust-badges')
    @else
        <div class="cart-page__layout" data-cart-page-layout>
            <div class="cart-page__main">
                <div data-cart-page-lines-wrap>
                    @include('shop.cart._line-items', ['lines' => $lines, 'currency' => $currency])
                </div>
                <a class="cart-page__continue" href="{{ route('shop.products.index') }}">Continue shopping</a>
            </div>
            <div data-cart-page-summary-wrap>
                @include('shop.cart._summary', [
                    'subtotalUsd' => $subtotalUsd,
                    'currency' => $currency,
                ])
            </div>
        </div>
        @include('shop.cart._trust-badges')
    @endif

    @if(isset($bestSellers) && $bestSellers->isNotEmpty())
        <section class="cart-page__bestsellers home-section home-section--bestsellers reveal-on-scroll" aria-labelledby="cart-bestsellers-title">
            <h2 id="cart-bestsellers-title" class="section__title section__title--center">Best sellers</h2>
            @include('shop.partials.home-product-slider', [
                'products' => $bestSellers,
                'currency' => $currency,
                'sliderLabel' => 'Best sellers',
            ])
        </section>
    @endif
</div>
@endsection
