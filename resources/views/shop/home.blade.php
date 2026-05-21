@extends('layouts.shop')

@section('mainClass', 'site-main--home')

@section('content')
@php($slideCount = count($bannerSlides))
<section class="home-hero home-hero--slider reveal-on-scroll"
         data-home-slider
         data-slide-interval="4000"
         aria-roledescription="carousel"
         aria-label="Home highlights">
    <div class="home-hero__viewport" data-slider-viewport>
        <div class="home-hero__slides" data-home-slider-track>
            @foreach($bannerSlides as $i => $slide)
                <div class="home-hero__slide {{ $i === 0 ? 'is-active' : '' }}"
                     data-slide
                     data-slide-index="{{ $i }}"
                     aria-roledescription="slide"
                     aria-label="Slide {{ $i + 1 }} of {{ $slideCount }}"
                     @if($slideCount > 1) aria-hidden="{{ $i === 0 ? 'false' : 'true' }}" @endif>
                    <a class="home-hero__slide-hit" href="{{ $slide['cta_url'] }}">
                        <img class="home-hero__bg" src="{{ $slide['image'] }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" width="1400" height="788" draggable="false">
                        <div class="home-hero__overlay">
                            @if(($slide['title'] ?? '') !== '')
                                <h1 class="home-hero__title">{{ $slide['title'] }}</h1>
                            @endif
                            @if(($slide['content'] ?? '') !== '')
                                <p class="home-hero__lede">{{ $slide['content'] }}</p>
                            @endif
                            <span class="btn btn--primary home-hero__cta" style="max-width:200px;">
                                @if(!empty($slide['category_id']))
                                    Shop this category
                                @else
                                    Shop the collection
                                @endif
                            </span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
    @if($slideCount > 1)
        <button type="button" class="home-hero__nav home-hero__nav--prev" data-slider-prev aria-label="Previous slide">&#10094;</button>
        <button type="button" class="home-hero__nav home-hero__nav--next" data-slider-next aria-label="Next slide">&#10095;</button>
        <div class="home-hero__dots" role="tablist" aria-label="Slides">
            @foreach($bannerSlides as $i => $_slide)
                <button type="button"
                        class="home-hero__dot {{ $i === 0 ? 'is-active' : '' }}"
                        data-dot
                        data-slide-to="{{ $i }}"
                        role="tab"
                        aria-label="Show slide {{ $i + 1 }}"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"></button>
            @endforeach
        </div>
    @endif
</section>

@if($homeCertificates->isNotEmpty())
<section class="home-section home-section--certificates reveal-on-scroll" aria-labelledby="home-certificates-title">
    <h2 id="home-certificates-title" class="section__title section__title--center">As seen in</h2>
    <div class="home-certificates-marquee" role="region" aria-label="Certificate logos">
        <div class="home-certificates-marquee__track">
            @foreach([false, true] as $duplicate)
                <div class="home-certificates-marquee__group" @if($duplicate) aria-hidden="true" @endif>
                    @foreach($homeCertificates as $certificate)
                        <article class="home-certificates-marquee__item">
                            <img class="home-certificates-marquee__img"
                                 src="{{ \App\Support\PublicAssetUrl::to($certificate->image) }}"
                                 alt="{{ $duplicate ? '' : $certificate->name }}"
                                 width="200"
                                 height="120"
                                 loading="lazy"
                                 draggable="false">
                        </article>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="home-section home-section--collections reveal-on-scroll" aria-labelledby="home-collections-title">
    <h2 id="home-collections-title" class="section__title section__title--center">Collections</h2>
    @if($homeCollections->isEmpty())
        <p class="home-section__empty home-section__empty--center">Browse the full catalog on the <a href="{{ route('shop.catalog') }}">shop page</a>.</p>
    @else
        <div class="category-card-grid category-card-grid--collections">
            @foreach($homeCollections as $cat)
                <a class="category-card" href="{{ route('shop.catalog.category', $cat) }}">
                    <span class="category-card__media">
                        <img src="{{ \App\Support\PublicAssetUrl::to($cat->image) }}" alt="{{ $cat->name }}" loading="lazy" width="280" height="280">
                    </span>
                    <span class="category-card__body">
                        <span class="category-card__title">{{ $cat->name }}</span>
                        @if($cat->description)
                            <span class="category-card__desc">{{ \Illuminate\Support\Str::limit(strip_tags($cat->description), 80) }}</span>
                        @endif
                        <span class="category-card__cta">Shop this collection →</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</section>

<section class="home-section home-section--about reveal-on-scroll" aria-labelledby="home-about-title">
    <h2 id="home-about-title" class="section__title section__title--center">About us</h2>
    <div class="home-about">
        @if(!empty($about['home_lede']))
            <p class="home-about__lede">{{ $about['home_lede'] }}</p>
        @endif

        @foreach($about['panels'] as $panel)
            @if(($panel['title'] ?? '') !== '' || ($panel['body'] ?? '') !== '')
                <details class="home-about__panel">
                    @if(($panel['title'] ?? '') !== '')
                        <summary class="home-about__summary">{{ $panel['title'] }}</summary>
                    @endif
                    @if(($panel['body'] ?? '') !== '')
                        <div class="home-about__body">{!! $panel['body'] !!}</div>
                    @endif
                </details>
            @endif
        @endforeach

        <a class="btn btn--primary btn--small" href="{{ route('shop.about') }}">{{ $about['home_button_label'] ?: 'Learn more about us' }}</a>
    </div>
</section>

@if($homeJournalPosts->isNotEmpty())
@php($journalCount = $homeJournalPosts->count())
<section class="home-section home-section--journal reveal-on-scroll" aria-labelledby="home-journal-title">
    <div class="home-section__head-row">
        <h2 id="home-journal-title" class="section__title">Journal</h2>
        <a class="btn btn--ghost btn--small" href="{{ route('shop.news.index') }}">View more</a>
    </div>
    <div class="home-journal-slider"
         data-home-slider
         data-slide-interval="3000"
         data-slides-mobile="1"
         data-slides-desktop="3"
         data-slide-breakpoint="768"
         aria-roledescription="carousel"
         aria-label="Journal articles">
        <div class="home-journal-slider__viewport" data-slider-viewport>
            <div class="home-journal-slider__track" data-home-slider-track>
                @foreach($homeJournalPosts as $i => $post)
                    <div class="home-journal-slider__slide {{ $i === 0 ? 'is-active' : '' }}"
                         data-slide
                         data-slide-index="{{ $i }}"
                         aria-roledescription="slide"
                         aria-label="Article {{ $i + 1 }} of {{ $journalCount }}"
                         @if($journalCount > 1) aria-hidden="{{ $i === 0 ? 'false' : 'true' }}" @endif>
                        @include('shop.partials.post-card', ['post' => $post])
                    </div>
                @endforeach
            </div>
        </div>
        @if($journalCount > 1)
            <button type="button" class="home-journal-slider__nav home-journal-slider__nav--prev" data-slider-prev aria-label="Previous article">&#10094;</button>
            <button type="button" class="home-journal-slider__nav home-journal-slider__nav--next" data-slider-next aria-label="Next article">&#10095;</button>
            <div class="home-journal-slider__dots" role="tablist" aria-label="Journal slides">
                @foreach($homeJournalPosts as $i => $_post)
                    <button type="button"
                            class="home-journal-slider__dot {{ $i === 0 ? 'is-active' : '' }}"
                            data-dot
                            data-slide-to="{{ $i }}"
                            role="tab"
                            aria-label="Show article {{ $i + 1 }}"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"></button>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif

@if($homeBestSellers)
    <section class="home-section home-section--new reveal-on-scroll" aria-labelledby="home-new-title">
        @if($homeBestSellers->isEmpty())
        <h2 id="home-new-title" class="section__title section__title--center">Best sellers</h2>
            <p class="home-section__empty home-section__empty--center">No best sellers yet — check back soon or <a href="{{ route('shop.products.index') }}">browse the shop</a>.</p>
        @else
            <div class="shop-product-grid">
                @foreach($homeBestSellers as $product)
                    @include('shop.partials.product-card', ['product' => $product, 'currency' => $currency])
                @endforeach
            </div>
        @endif
    </section>
@endif

<section class="home-section home-section--new reveal-on-scroll" aria-labelledby="home-new-title">
    <h2 id="home-new-title" class="section__title section__title--center">New arrivals</h2>
    @if($homeNewProducts->isEmpty())
        <p class="home-section__empty home-section__empty--center">New pieces are on the way — check back soon or <a href="{{ route('shop.products.index') }}">browse the shop</a>.</p>
    @else
        <div class="shop-product-grid">
            @foreach($homeNewProducts as $product)
                @include('shop.partials.product-card', ['product' => $product, 'currency' => $currency])
            @endforeach
        </div>
    @endif
</section>

<section class="home-section home-section--reviews reveal-on-scroll" aria-labelledby="home-reviews-title">
    <h2 id="home-reviews-title" class="section__title section__title--center">Customer reviews</h2>
    @if($homeReviews->isEmpty())
        <p class="reviews__empty">No reviews yet — be the first to share your thoughts after your next order.</p>
    @else
        <ul class="home-reviews-grid">
            @foreach($homeReviews as $review)
                <li class="home-review-card">
                    <p class="home-review-card__name">{{ $review->customer_name }}</p>
                    @include('shop.partials.stars', ['rating' => $review->rating])
                    <p class="home-review-card__content">{{ $review->content }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</section>

@if(!empty($welcomePopup['enabled']))
    @include('shop.partials.welcome-popup', ['welcomePopup' => $welcomePopup])
@endif
@endsection
