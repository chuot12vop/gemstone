@extends('layouts.shop')

@section('mainClass', 'site-main--home')

@section('content')
@php($slideCount = count($bannerSlides))
<section class="home-hero home-hero--slider reveal-on-scroll"
         data-home-slider
         data-slider-loop="true"
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
                        @php($mobileImage = $slide['image_mobile'] ?? $slide['image'])
                        <picture>
                            <source media="(max-width: 767px)" srcset="{{ $mobileImage }}">
                            <img class="home-hero__bg" src="{{ $slide['image'] }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" width="1400" height="788" draggable="false">
                        </picture>
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
<section class="home-section home-section--certificates reveal-on-scroll{{ !empty($homeSectionStyles['certificates']['background_image_url']) ? ' home-section--has-bg-image' : '' }}" aria-labelledby="home-certificates-title" style="{{ \App\Support\HomeSectionSettings::inlineStyle($homeSectionStyles['certificates'] ?? []) }}">
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

@if($homeBestSellers)
    <section class="home-section home-section--bestsellers reveal-on-scroll{{ !empty($homeSectionStyles['bestsellers']['background_image_url']) ? ' home-section--has-bg-image' : '' }}" aria-labelledby="home-bestsellers-title" style="{{ \App\Support\HomeSectionSettings::inlineStyle($homeSectionStyles['bestsellers'] ?? []) }}">
        @php($bestSellersUrl = isset($homeBestSellersCategory) && $homeBestSellersCategory ? route('shop.catalog.category', $homeBestSellersCategory) : route('shop.products.index'))
        <h2 id="home-bestsellers-title" class="section__title section__title--center">
            <a class="section__title-link" href="{{ $bestSellersUrl }}">Best sellers</a>
        </h2>
        @if(isset($homeBestSellersCategory) && $homeBestSellersCategory && !empty($homeBestSellersCategory->image))
            <a class="home-category-feature" href="{{ route('shop.catalog.category', $homeBestSellersCategory) }}" aria-label="Shop {{ $homeBestSellersCategory->name }}">
                <span class="home-category-feature__media">
                    <img src="{{ \App\Support\PublicAssetUrl::to($homeBestSellersCategory->image) }}" alt="{{ $homeBestSellersCategory->name }}" loading="lazy">
                </span>
                <span class="home-category-feature__body">
                    <span class="home-category-feature__title">{{ $homeBestSellersCategory->name }}</span>
                    @if(trim((string) $homeBestSellersCategory->description) !== '')
                        <span class="home-category-feature__desc">{{ $homeBestSellersCategory->description }}</span>
                    @endif
                </span>
            </a>
        @endif
        @if($homeBestSellers->isEmpty())
            <p class="home-section__empty home-section__empty--center">No best sellers yet — check back soon or <a href="{{ route('shop.products.index') }}">browse the shop</a>.</p>
        @else
            @include('shop.partials.home-product-slider', [
                'products' => $homeBestSellers,
                'currency' => $currency,
                'sliderLabel' => 'Best sellers',
            ])
        @endif
    </section>
@endif

<section class="home-section home-section--collections reveal-on-scroll{{ !empty($homeSectionStyles['collections']['background_image_url']) ? ' home-section--has-bg-image' : '' }}" aria-labelledby="home-collections-title" style="{{ \App\Support\HomeSectionSettings::inlineStyle($homeSectionStyles['collections'] ?? []) }}">
    <h2 id="home-collections-title" class="section__title section__title--center">Collections</h2>
    @if($homeCollections->isEmpty())
        <p class="home-section__empty home-section__empty--center">Browse the full catalog on the <a href="{{ route('shop.catalog') }}">shop page</a>.</p>
    @else
        <div class="category-card-grid category-card-grid--collections">
            @foreach($homeCollections as $cat)
                <a class="category-card" href="{{ route('shop.catalog.category', $cat) }}">
                    <span class="category-card__media">
                        <img src="{{ \App\Support\PublicAssetUrl::to($cat->image) }}" alt="{{ $cat->name }}" loading="lazy">
                    </span>
                    <span class="category-card__body">
                        <span class="category-card__title">{{ $cat->name }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</section>

<section class="home-section home-section--new reveal-on-scroll{{ !empty($homeSectionStyles['new']['background_image_url']) ? ' home-section--has-bg-image' : '' }}" aria-labelledby="home-new-title" style="{{ \App\Support\HomeSectionSettings::inlineStyle($homeSectionStyles['new'] ?? []) }}">
    <h2 id="home-new-title" class="section__title section__title--center">
        <a class="section__title-link" href="{{ route('shop.products.index', ['sort' => 'newest']) }}">New arrivals</a>
    </h2>
    @if(isset($homeNewCategory) && $homeNewCategory && !empty($homeNewCategory->image))
        <a class="home-category-feature" href="{{ route('shop.catalog.category', $homeNewCategory) }}" aria-label="Shop {{ $homeNewCategory->name }}">
            <span class="home-category-feature__media">
                <img src="{{ \App\Support\PublicAssetUrl::to($homeNewCategory->image) }}" alt="{{ $homeNewCategory->name }}" loading="lazy">
            </span>
            <span class="home-category-feature__body">
                <span class="home-category-feature__title">{{ $homeNewCategory->name }}</span>
                @if(trim((string) $homeNewCategory->description) !== '')
                    <span class="home-category-feature__desc">{{ $homeNewCategory->description }}</span>
                @endif
            </span>
        </a>
    @endif
    @if($homeNewProducts->isEmpty())
        <p class="home-section__empty home-section__empty--center">New pieces are on the way — check back soon or <a href="{{ route('shop.products.index') }}">browse the shop</a>.</p>
    @else
        @include('shop.partials.home-product-slider', [
            'products' => $homeNewProducts,
            'currency' => $currency,
            'sliderLabel' => 'New arrivals',
        ])
    @endif
</section>

<section class="home-section home-section--reviews reveal-on-scroll{{ !empty($homeSectionStyles['reviews']['background_image_url']) ? ' home-section--has-bg-image' : '' }}" aria-labelledby="home-reviews-title" style="{{ \App\Support\HomeSectionSettings::inlineStyle($homeSectionStyles['reviews'] ?? []) }}">
    <h2 id="home-reviews-title" class="section__title section__title--center">Feedback</h2>
    @if($homeReviews->isEmpty())
        <p class="reviews__empty">No photo reviews yet — share your experience with images after your next order.</p>
    @else
        @include('shop.partials.home-review-slider', ['posts' => $homeReviews, 'type' => 'reviews'])
    @endif
</section>

<section class="home-service-band" aria-label="Store benefits">
    <div class="home-service-band__item">
        <span class="home-service-band__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M24 4.5l3.5 3 4.6-1 2 4.2 4.6.8.1 4.7 3.6 3-1.8 4.3 1.8 4.3-3.6 3-.1 4.7-4.6.8-2 4.2-4.6-1-3.5 3-3.5-3-4.6 1-2-4.2-4.6-.8-.1-4.7-3.6-3 1.8-4.3-1.8-4.3 3.6-3 .1-4.7 4.6-.8 2-4.2 4.6 1 3.5-3z"/>
                <circle cx="24" cy="23.5" r="9.5"/>
                <path d="M18.8 23.8l3.7 3.7 6.9-7.4"/>
            </svg>
        </span>
        <span class="home-service-band__label">2 YEAR WARRANTY</span>
    </div>
    <div class="home-service-band__item">
        <span class="home-service-band__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 30h26.5V16H12"/>
                <path d="M31.5 22.5h6.7l4.8 5.2V30h-11.5"/>
                <path d="M10 34.5a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9z"/>
                <path d="M35.5 34.5a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9z"/>
                <path d="M4 18h11M2.5 22h9.5M6 26h9"/>
            </svg>
        </span>
        <span class="home-service-band__label">FREE DELIVERY ${{ number_format(\App\Support\CheckoutShipping::freeShippingThresholdUsd(), 0) }}+</span>
    </div>
    <div class="home-service-band__item">
        <span class="home-service-band__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 10h21v26H16z"/>
                <path d="M16 10l4-5h21l-4 5"/>
                <path d="M37 10l4-5v26l-4 5"/>
                <path d="M20 18h13"/>
                <path d="M15 30H6.5"/>
                <path d="M10 24l-6 6 6 6"/>
            </svg>
        </span>
        <span class="home-service-band__label">60-DAY RETURNS</span>
    </div>
</section>

@if($homeStoryPage)
    @include('shop.partials.home-stories', ['storyPage' => $homeStoryPage, 'homeSectionStyles' => $homeSectionStyles])
@endif

@if($homeJournalPosts->isNotEmpty())
@php($journalCount = $homeJournalPosts->count())
@php($journalSlidesDesktop = min(3, max(1, $journalCount - 1)))
@php($journalBasisDesktop = 100 / $journalSlidesDesktop)
<section class="home-section home-section--journal reveal-on-scroll{{ !empty($homeSectionStyles['journal']['background_image_url']) ? ' home-section--has-bg-image' : '' }}" aria-labelledby="home-journal-title" style="{{ \App\Support\HomeSectionSettings::inlineStyle($homeSectionStyles['journal'] ?? []) }}">
    <div class="home-section__head-row">
        <h2 id="home-journal-title" class="section__title">Journal</h2>
        <a class="btn btn--ghost btn--small" href="{{ route('shop.news.index') }}">View more</a>
    </div>
    <div class="home-journal-slider"
         data-home-slider
         data-slider-loop="true"
         data-autoplay="false"
         data-slide-interval="3000"
         data-slides-mobile="1"
         data-slides-tablet="2"
         data-slides-desktop="{{ $journalSlidesDesktop }}"
         data-slide-breakpoint-tablet="480"
         data-slide-breakpoint="961"
         style="--slide-basis-mobile: 100%; --slide-basis-tablet: 50%; --slide-basis-desktop: {{ $journalBasisDesktop }}%;"
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

@if(!empty($welcomePopup['enabled']))
    @include('shop.partials.welcome-popup', ['welcomePopup' => $welcomePopup])
@endif
@endsection
