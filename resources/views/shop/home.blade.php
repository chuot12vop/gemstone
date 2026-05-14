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

@if($homeMarqueeBrands->isNotEmpty())
<section class="home-section home-section--brands reveal-on-scroll" aria-labelledby="home-brands-title">
    <h2 id="home-brands-title" class="section__title section__title--center home-brands-marquee__heading">Our brands</h2>
    <div class="home-brands-marquee" data-home-brands-marquee>
        <div class="home-brands-marquee__viewport">
            <div class="home-brands-marquee__track">
                @foreach($homeMarqueeBrands as $brand)
                    <a class="home-brands-marquee__link"
                       href="{{ route('shop.products.index', ['brand' => $brand->slug]) }}">
                        <span class="home-brands-marquee__logo-wrap">
                            <img class="home-brands-marquee__logo"
                                 src="{{ \App\Support\PublicAssetUrl::to($brand->image) }}"
                                 alt="{{ $brand->name }}"
                                 width="200"
                                 height="120"
                                 loading="lazy">
                        </span>
                        <span class="home-brands-marquee__name">{{ $brand->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

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
        <h3>About Our Gemstones</h3>
        <p>More than just jewelry—each stone is a piece of a heritage story, a whispering bridge between timeless elegance and the heartbeat of contemporary life.</p>
        <a class="btn btn--primary btn--small" href="{{ route('shop.about.gemstones') }}">View all</a>
    </article>
    <article class="insight-card">
        <h3>The Spiritual Energy of the Five Elements: Find Your Balance</h3>
        <p>The universe thrives on balance, and so do we. The ancient philosophy of the Five Elements (Wu Xing)—Wood, Fire, Earth, Metal, and Water—is a profound map to understanding our inner energy. In this tradition, everything in nature is connected, and by aligning your personal energy with the right gemstone, you can invite harmony, protection, and prosperity into your modern life.</p>
        <a class="btn btn--primary btn--small" href="{{ route('shop.about.spirituality') }}">View all</a>
    </article>
    <article class="insight-card">
        <h3>Pick the Sacred Guardian That Attracts and Protects Wealth Energy</h3>
        <p>Choose pieces crafted to support confidence and prosperity in daily life.</p>
        <a class="btn btn--primary btn--small" href="{{ route('shop.about.wealth') }}">View all</a>
    </article>
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
