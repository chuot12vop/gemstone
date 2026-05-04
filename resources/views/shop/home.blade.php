@extends('layouts.shop')

@section('content')
<section class="hero">
    <p class="eyebrow">Revitalize your being</p>
    <h1 class="hero__title">Vitality &amp; balance</h1>
    <p class="hero__lede">Premium gemstone jewelry for the US market — hand-selected materials, calm palette, and intention in every piece.</p>
    <a class="btn btn--primary" href="{{ route('shop.catalog') }}">Shop the collection</a>
</section>

<section class="section">
    <h2 class="section__title">Curated for your intention</h2>
    <ul class="cat-grid">
        @foreach($categories as $cat)
            <li>
                <a class="cat-card" href="{{ route('shop.catalog.category', $cat) }}">
                    <span class="cat-card__name">{{ $cat->name }}</span>
                    <span class="cat-card__meta">Explore</span>
                </a>
            </li>
        @endforeach
    </ul>
</section>

<section class="section section--soft">
    <h2 class="section__title">Featured</h2>
    <div class="product-grid">
        @foreach($featured as $p)
            <article class="product-card">
                <a href="{{ route('shop.product', $p) }}" class="product-card__link">
                    <div class="product-card__img-wrap">
                        <img src="{{ $p->image ?: asset('assets/img/placeholder.svg') }}" alt="" width="400" height="400" loading="lazy">
                    </div>
                    <h3 class="product-card__title">{{ $p->name }}</h3>
                    {{-- <p class="product-card__price">{{ $currency->formatUsd((float) $p->price_usd) }}</p> --}}
                    <p class="product-card__price">{{ $currency }}</p>
                </a>
            </article>
        @endforeach
    </div>
</section>

<section class="section three-cols">
    <div>
        <h3>Ethically sourced</h3>
        <p>Stones from suppliers who value fair practices and care for the craft.</p>
    </div>
    <div>
        <h3>Infused with intention</h3>
        <p>Classical feng shui inspiration — designed for modern, mindful living.</p>
    </div>
    <div>
        <h3>Hand-finished</h3>
        <p>Artisan detail with comfortable, daily wear in mind.</p>
    </div>
</section>
@endsection
