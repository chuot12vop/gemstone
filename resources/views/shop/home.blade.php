@extends('layouts.shop')

@section('mainClass', 'site-main--home')

@section('content')
<section class="home-hero reveal-on-scroll">
    <img class="home-hero__bg" src="{{ !empty($siteSettings['home_banner']) ? $siteSettings['home_banner'] : 'https://taichigemstone.com/cdn/shop/files/Gemini_Generated_Image_7ja7m27ja7m27ja7.png?v=1773133793&width=1400' }}" alt="Gemstone jewelry in natural setting" loading="eager">
    <div class="home-hero__overlay">
        <p class="eyebrow">Revitalize your being</p>
        <h1 class="home-hero__title">Vitality &amp; Balance</h1>
        <p class="home-hero__lede">Elevate your energy with naturally selected gemstone bracelets and handcrafted feng shui pieces.</p>
        <a class="btn btn--primary" style="max-width:200px;" href="{{ route('shop.products.index') }}">Shop the collection</a>
    </div>
</section>

<section class="home-section home-section--spotlight reveal-on-scroll">
    <h2 class="section__title section__title--center">Featured products</h2>
    <div class="shop-product-grid">
        @foreach($spotlightProducts as $p)
            @include('shop.partials.product-card', ['product' => $p, 'currency' => $currency])
        @endforeach
    </div>
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

<section class="home-section home-section--categories reveal-on-scroll">
    <h2 class="section__title">Categories</h2>
    @if($homeCategories->isEmpty())
        <p class="home-section__empty">Browse the full catalog on the <a href="{{ route('shop.catalog') }}">shop page</a>.</p>
    @else
        <div class="category-card-grid">
            @foreach($homeCategories as $cat)
                <a class="category-card" href="{{ route('shop.catalog.category', $cat) }}">
                    <span class="category-card__media">
                        <img src="{{ \App\Support\PublicAssetUrl::to($cat->image) }}" alt="{{ $cat->name }}" loading="lazy" width="600" height="600">
                    </span>
                    <span class="category-card__body">
                        <span class="category-card__title">{{ $cat->name }}</span>
                        @if($cat->description)
                            <span class="category-card__desc">{{ \Illuminate\Support\Str::limit(strip_tags($cat->description), 120) }}</span>
                        @endif
                        <span class="category-card__cta">Shop this category →</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif
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
