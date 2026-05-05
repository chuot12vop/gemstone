@extends('layouts.shop')

@section('mainClass', 'site-main--home')

@section('content')
<section class="home-hero reveal-on-scroll">
    <img class="home-hero__bg" src="{{ !empty($siteSettings['home_banner']) ? $siteSettings['home_banner'] : 'https://taichigemstone.com/cdn/shop/files/Gemini_Generated_Image_7ja7m27ja7m27ja7.png?v=1773133793&width=1400' }}" alt="Gemstone jewelry in natural setting" loading="eager">
    <div class="home-hero__overlay">
        <p class="eyebrow">Revitalize your being</p>
        <h1 class="home-hero__title">Vitality &amp; Balance</h1>
        <p class="home-hero__lede">Elevate your energy with naturally selected gemstone bracelets and handcrafted feng shui pieces.</p>
        <a class="btn btn--primary" style="max-width:200px;" href="{{ route('shop.catalog') }}">Shop the collection</a>
    </div>
</section>

<section class="home-section home-section--curated reveal-on-scroll">
    <h2 class="section__title section__title--center">Curated for Your Intention</h2>
    <ul class="curated-grid">
        @foreach($categories as $cat)
            <li>
                <a class="curated-card" href="{{ route('shop.catalog.category', $cat) }}">
                    <div class="curated-card__media">
                        <img src="{{ $cat->products->first()->image ?? asset('assets/img/placeholder.svg') }}" alt="{{ $cat->name }}" loading="lazy">
                    </div>
                    <span class="curated-card__name">{{ $cat->name }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</section>

<section class="home-section features-strip reveal-on-scroll">
    <article class="feature-item">
        <h3>Ethically Sourced</h3>
        <p>Every stone is selected from trusted partners focused on transparent sourcing.</p>
    </article>
    <article class="feature-item">
        <h3>Infused with Energy</h3>
        <p>Designed with feng shui principles to bring calm and positive intention.</p>
    </article>
    <article class="feature-item">
        <h3>Handcrafted</h3>
        <p>Each piece is hand-finished for comfort, durability and elegant daily wear.</p>
    </article>
</section>

<section class="home-section home-section--featured reveal-on-scroll">
    <h2 class="section__title">Featured Collection</h2>
    <div class="featured-grid">
        @foreach($featured as $p)
            <article class="featured-card">
                <a href="{{ route('shop.product', $p) }}" class="featured-card__link">
                    <div class="featured-card__img-wrap">
                        <img src="{{ $p->image ?: asset('assets/img/placeholder.svg') }}" alt="{{ $p->name }}" width="400" height="400" loading="lazy">
                    </div>
                    <h3 class="featured-card__title">{{ $p->name }}</h3>
                </a>
            </article>
        @endforeach
    </div>
</section>

<section class="home-section insights reveal-on-scroll">
    <article class="insight-card">
        <h3>What is Feng Shui?</h3>
        <p>Discover how mindful gemstone placement helps invite balance and harmony.</p>
        <a class="btn btn--primary btn--small" href="{{ route('shop.about') }}">View all</a>
    </article>
    <article class="insight-card">
        <h3>The Spiritual Energy of the Five Elements</h3>
        <p>Learn how wood, fire, earth, metal and water align with gemstone choices.</p>
        <a class="btn btn--primary btn--small" href="{{ route('shop.about') }}">View all</a>
    </article>
    <article class="insight-card">
        <h3>Pick the Sacred Guardian That Attracts and Protects Wealth Energy</h3>
        <p>Choose pieces crafted to support confidence and prosperity in daily life.</p>
        <a class="btn btn--primary btn--small" href="{{ route('shop.about') }}">View all</a>
    </article>
</section>
@endsection
