@extends('layouts.shop')

@section('mainClass', 'site-main--home')

@section('content')
@php($slideCount = count($bannerSlides))
<section class="home-hero home-hero--slider reveal-on-scroll"
         data-home-slider
         data-slide-interval="4000"
         aria-roledescription="carousel"
         aria-label="Home highlights">
    <div class="home-hero__slides">
        @foreach($bannerSlides as $i => $slide)
            <div class="home-hero__slide {{ $i === 0 ? 'is-active' : '' }}"
                 data-slide
                 data-slide-index="{{ $i }}"
                 aria-roledescription="slide"
                 aria-label="Slide {{ $i + 1 }} of {{ $slideCount }}"
                 @if($slideCount > 1) aria-hidden="{{ $i === 0 ? 'false' : 'true' }}" @endif>
                <a class="home-hero__slide-hit" href="{{ $slide['cta_url'] }}">
                    <img class="home-hero__bg" src="{{ $slide['image'] }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" width="1400" height="788">
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
    <h2 id="home-certificates-title" class="section__title section__title--center">Certificates</h2>
    <div class="home-certificates-scroll" tabindex="0" role="region" aria-label="Certificate logos — scroll horizontally">
        @foreach($homeCertificates as $certificate)
            <article class="home-certificates-scroll__item">
                <img class="home-certificates-scroll__img"
                     src="{{ \App\Support\PublicAssetUrl::to($certificate->image) }}"
                     alt="{{ $certificate->name }}"
                     width="200"
                     height="120"
                     loading="lazy"
                     draggable="false">
            </article>
        @endforeach
    </div>
</section>
@endif

<section class="home-section home-section--products reveal-on-scroll" aria-labelledby="home-top-products-title">
    <h2 id="home-top-products-title" class="section__title section__title--center">Top products</h2>
    @if($homeTopProducts->isEmpty())
        <p class="home-section__empty home-section__empty--center">Browse the full catalog on the <a href="{{ route('shop.products.index') }}">shop page</a>.</p>
    @else
        <div class="shop-product-grid">
            @foreach($homeTopProducts as $product)
                @include('shop.partials.product-card', ['product' => $product, 'currency' => $currency])
            @endforeach
        </div>
    @endif
</section>

<section class="home-section home-section--collections reveal-on-scroll" aria-labelledby="home-collections-title">
    <h2 id="home-collections-title" class="section__title section__title--center">Top categories</h2>
    @if($homeCollections->isEmpty())
        <p class="home-section__empty home-section__empty--center">Browse the full catalog on the <a href="{{ route('shop.catalog') }}">shop page</a>.</p>
    @else
        <div class="category-card-grid category-card-grid--collections">
            @foreach($homeCollections as $cat)
                <a class="category-card" href="{{ route('shop.catalog.category', $cat) }}">
                    <span class="category-card__media">
                        <img src="{{ \App\Support\PublicAssetUrl::to($cat->image) }}" alt="{{ $cat->name }}" loading="lazy" width="600" height="600">
                    </span>
                    <span class="category-card__body">
                        <span class="category-card__title">{{ $cat->name }}</span>
                        @if($cat->description)
                            <span class="category-card__desc">{{ \Illuminate\Support\Str::limit(strip_tags($cat->description), 120) }}</span>
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
        <p class="home-about__lede">More than jewelry — a bridge between mindful tradition and contemporary life. We take the time to understand each stone before it is chosen, crafting pieces with reverence for energetic balance and everyday wear.</p>

        <details class="home-about__panel">
            <summary class="home-about__summary">Our philosophy</summary>
            <div class="home-about__body">
                <p>We believe meaningful spiritual tools shouldn't be rushed; they must be nurtured. Every design is crafted with reverence to achieve energetic balance, effortless wearability, and a pure, authentic beauty suited for customers who seek deep, genuine connections.</p>
            </div>
        </details>

        <details class="home-about__panel">
            <summary class="home-about__summary">Materials &amp; craftsmanship</summary>
            <div class="home-about__body">
                <p>Each stone is selected for clarity, color, and intention before it enters our workshop. Hand-finishing and careful stringing ensure pieces that feel as good as they look — made to be worn daily, not kept in a drawer.</p>
            </div>
        </details>

        <details class="home-about__panel">
            <summary class="home-about__summary">Color &amp; aesthetic</summary>
            <div class="home-about__body">
                <p>Our palette is an ode to daylight: warm cream, champagne gold, and soft neutrals — as elegant and quiet as nature itself.</p>
            </div>
        </details>

        <a class="btn btn--primary btn--small" href="{{ route('shop.about') }}">Learn more about us</a>
    </div>
</section>

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

@push('scripts')
<script>
(() => {
    document.querySelectorAll('[data-home-slider]').forEach((root) => {
        const slides = Array.from(root.querySelectorAll('[data-slide]'));
        const dots = Array.from(root.querySelectorAll('[data-dot]'));
        if (slides.length === 0) {
            return;
        }

        let active = 0;
        const ms = parseInt(String(root.getAttribute('data-slide-interval') || '4000'), 10) || 4000;

        let timer = null;
        const stop = () => {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        };
        const start = () => {
            stop();
            if (slides.length > 1) {
                timer = window.setInterval(() => setActive(active + 1), ms);
            }
        };

        const setActive = (idx) => {
            const next = (idx + slides.length) % slides.length;
            slides.forEach((el, i) => {
                el.classList.toggle('is-active', i === next);
                if (slides.length > 1) {
                    el.setAttribute('aria-hidden', i === next ? 'false' : 'true');
                }
            });
            dots.forEach((d, i) => {
                d.classList.toggle('is-active', i === next);
                d.setAttribute('aria-selected', i === next ? 'true' : 'false');
            });
            active = next;
        };

        dots.forEach((d) => {
            d.addEventListener('click', () => {
                const to = parseInt(String(d.getAttribute('data-slide-to') || '0'), 10);
                if (!Number.isNaN(to)) {
                    setActive(to);
                    start();
                }
            });
        });

        root.querySelector('[data-slider-prev]')?.addEventListener('click', () => {
            setActive(active - 1);
            start();
        });
        root.querySelector('[data-slider-next]')?.addEventListener('click', () => {
            setActive(active + 1);
            start();
        });

        let touchStartX = 0;
        root.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0]?.clientX ?? 0;
        }, { passive: true });
        root.addEventListener('touchend', (e) => {
            const dx = (e.changedTouches[0]?.clientX ?? 0) - touchStartX;
            if (Math.abs(dx) < 40) return;
            setActive(dx < 0 ? active + 1 : active - 1);
            start();
        }, { passive: true });

        if (slides.length <= 1) {
            return;
        }

        start();
        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
    });
})();
</script>
@endpush
@endsection
