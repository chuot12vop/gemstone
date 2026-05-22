@php
    $count = $posts->count();
    $slidesMobile = (int) ($slidesMobile ?? 2);
    $slidesTablet = (int) ($slidesTablet ?? 4);
    $slidesDesktop = (int) ($slidesDesktop ?? 6);
    $breakpointTablet = (int) ($breakpointTablet ?? 820);
    $breakpointDesktop = (int) ($breakpointDesktop ?? 960);
    $slideBasisMobile = 100 / max(1, $slidesMobile);
    $slideBasisTablet = 100 / max(1, $slidesTablet);
    $slideBasisDesktop = 100 / max(1, $slidesDesktop);
@endphp
@if($count > 0)
    <div class="home-review-slider"
         data-home-slider
         data-slide-interval="5000"
         data-slides-mobile="{{ $slidesMobile }}"
         data-slides-tablet="{{ $slidesTablet }}"
         data-slides-desktop="{{ $slidesDesktop }}"
         data-slide-breakpoint-tablet="{{ $breakpointTablet }}"
         data-slide-breakpoint="{{ $breakpointDesktop }}"
         style="--slide-basis-mobile: {{ $slideBasisMobile }}%; --slide-basis-tablet: {{ $slideBasisTablet }}%; --slide-basis-desktop: {{ $slideBasisDesktop }}%;"
         aria-roledescription="carousel"
         aria-label="Customer reviews">
        <div class="home-review-slider__viewport" data-slider-viewport>
            <div class="home-review-slider__track" data-home-slider-track>
                @foreach($posts as $i => $post)
                    <div class="home-review-slider__slide {{ $i === 0 ? 'is-active' : '' }}"
                         data-slide
                         data-slide-index="{{ $i }}"
                         aria-roledescription="slide"
                         aria-label="Review {{ $i + 1 }} of {{ $count }}"
                         @if($count > 1) aria-hidden="{{ $i === 0 ? 'false' : 'true' }}" @endif>
                        @if($type === 'journal')
                            @include('shop.partials.post-card', ['post' => $post])
                        @else
                            @include('shop.partials.home-feedback-card', ['review' => $post])
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @if($count > 1)
            <button type="button" class="home-review-slider__nav home-review-slider__nav--prev" data-slider-prev aria-label="Previous review">&#10094;</button>
            <button type="button" class="home-review-slider__nav home-review-slider__nav--next" data-slider-next aria-label="Next review">&#10095;</button>
            <div class="home-review-slider__dots" role="tablist" aria-label="Review slides">
                @foreach($posts as $i => $post)
                    <button type="button"
                            class="home-review-slider__dot {{ $i === 0 ? 'is-active' : '' }}"
                            data-dot
                            data-slide-to="{{ $i }}"
                            role="tab"
                            aria-label="Show review {{ $i + 1 }}"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"></button>
                @endforeach
            </div>
        @endif
    </div>
@endif
