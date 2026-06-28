@php
    $stats = $reviewStats ?? ['count' => 0, 'average' => 0, 'distribution' => [5=>0,4=>0,3=>0,2=>0,1=>0]];
@endphp
<section class="reviews" id="reviews" data-reviews>
    <header class="reviews__head">
        <h2 class="product-detail__section-title">Customer Reviews</h2>
        @if($stats['count'] > 0)
            <div class="reviews__summary">
                <div class="reviews__avg">
                    <span class="reviews__avg-num">{{ number_format($stats['average'], 1) }}</span>
                    @include('shop.partials.stars', ['rating' => $stats['average'], 'size' => 18])
                </div>
                <p class="reviews__count">Based on {{ $stats['count'] }} {{ Str::plural('review', $stats['count']) }}</p>

                <ul class="reviews__bars">
                    @foreach([5,4,3,2,1] as $star)
                        @php
                            $count = $stats['distribution'][$star] ?? 0;
                            $pct = $stats['count'] > 0 ? round($count * 100 / $stats['count']) : 0;
                        @endphp
                        <li class="reviews__bar">
                            <span class="reviews__bar-label">{{ $star }} ★</span>
                            <span class="reviews__bar-track" aria-hidden="true">
                                <span class="reviews__bar-fill" style="width: {{ $pct }}%"></span>
                            </span>
                            <span class="reviews__bar-count">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </header>

    @if($reviews->isEmpty())
        <p class="reviews__empty">No reviews yet — be the first to share your thoughts after your next order.</p>
    @else
        <ul class="reviews__list">
            @foreach($reviews as $review)
                <li class="review-card">
                    <header class="review-card__head">
                        <div>
                            @include('shop.partials.stars', ['rating' => $review->rating])
                            @if($review->title)
                                <h3 class="review-card__title">{{ $review->title }}</h3>
                            @endif
                        </div>
                        <p class="review-card__meta">
                            <strong>{{ $review->customer_name }}</strong>
                            <span aria-hidden="true">·</span>
                            <time datetime="{{ $review->created_at?->toIso8601String() }}">{{ $review->created_at?->format('M j, Y') }}</time>
                        </p>
                    </header>
                    <p class="review-card__content">{!! nl2br(e($review->content)) !!}</p>
                    @if($review->images->isNotEmpty())
                        <ul class="review-card__images">
                            @foreach($review->images as $img)
                                <li><a href="{{ $img->path }}" target="_blank" rel="noopener"><img src="{{ $img->path }}" alt="" loading="lazy"></a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>

        @if($reviews->hasPages())
            <nav class="pagination-wrap" aria-label="Customer reviews pagination">
                {{ $reviews->links('shop.partials.pagination') }}
            </nav>
        @endif
    @endif
</section>
