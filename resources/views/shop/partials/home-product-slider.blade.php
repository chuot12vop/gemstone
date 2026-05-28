@php
    $count = $products->count();
    $sliderLabel = $sliderLabel ?? 'Products';
@endphp
@if($count > 0)
    <div class="home-product-slider"
         data-home-slider
         data-slider-loop="true"
         data-slide-interval="4000"
         data-slides-mobile="1"
         data-slides-tablet="2"
         data-slides-desktop="{{ $count }}"
         data-slide-breakpoint-tablet="700"
         data-slide-breakpoint="960"
         aria-roledescription="carousel"
         aria-label="{{ $sliderLabel }}">
        <div class="home-product-slider__viewport" data-slider-viewport>
            <div class="home-product-slider__track" data-home-slider-track>
                @foreach($products as $i => $product)
                    <div class="home-product-slider__slide {{ $i === 0 ? 'is-active' : '' }}"
                         data-slide
                         data-slide-index="{{ $i }}"
                         aria-roledescription="slide"
                         aria-label="Product {{ $i + 1 }} of {{ $count }}"
                         @if($count > 1) aria-hidden="{{ $i === 0 ? 'false' : 'true' }}" @endif>
                        @include('shop.partials.product-card', ['product' => $product, 'currency' => $currency])
                    </div>
                @endforeach
            </div>
        </div>
        @if($count > 1)
            <button type="button" class="home-product-slider__nav home-product-slider__nav--prev" data-slider-prev aria-label="Previous product">&#10094;</button>
            <button type="button" class="home-product-slider__nav home-product-slider__nav--next" data-slider-next aria-label="Next product">&#10095;</button>
            <div class="home-product-slider__dots" role="tablist" aria-label="{{ $sliderLabel }} slides">
                @foreach($products as $i => $_product)
                    <button type="button"
                            class="home-product-slider__dot {{ $i === 0 ? 'is-active' : '' }}"
                            data-dot
                            data-slide-to="{{ $i }}"
                            role="tab"
                            aria-label="Show product {{ $i + 1 }}"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"></button>
                @endforeach
            </div>
        @endif
    </div>
@endif
