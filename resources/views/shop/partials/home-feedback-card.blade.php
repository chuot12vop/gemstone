@php
    $cover = $review->images->first();
    $name = trim((string) $review->customer_name);
    $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($parts) >= 2) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1));
    } else {
        $initials = mb_strtoupper(mb_substr($name, 0, 2));
    }
@endphp
<article class="home-feedback-card">
    @if($cover)
        <div class="home-feedback-card__hero">
            <img class="home-feedback-card__photo"
                 src="{{ \App\Support\PublicAssetUrl::to($cover->path) }}"
                 alt=""
                 loading="lazy"
                 width="200"
                 height="200">
            <div class="home-feedback-card__avatar-wrap">
                <span class="home-feedback-card__avatar" aria-hidden="true">{{ $initials }}</span>
                <span class="home-feedback-card__verified" aria-label="Verified customer">
                    <svg viewBox="0 0 20 20" width="8" height="8" aria-hidden="true">
                        <circle cx="10" cy="10" r="10" fill="currentColor"/>
                        <path d="M6 10.2 8.4 12.6 14 7" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </div>
        </div>
    @endif
    <div class="home-feedback-card__body">
        <p class="home-feedback-card__name">{{ $name }}</p>
        <p class="home-feedback-card__date">
            <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('j/n/Y') }}</time>
        </p>
        <blockquote class="home-feedback-card__quote">
            <span class="home-feedback-card__quote-mark home-feedback-card__quote-mark--open" aria-hidden="true">&ldquo;</span>
            <p>{{ $review->content }}</p>
            <span class="home-feedback-card__quote-mark home-feedback-card__quote-mark--close" aria-hidden="true">&rdquo;</span>
        </blockquote>
        <div class="home-feedback-card__stars">
            @include('shop.partials.stars', ['rating' => $review->rating, 'size' => 12])
        </div>
    </div>
</article>
