@extends('layouts.shop')

@section('mainClass', 'site-main--review')

@section('content')
<header class="page-head review-page-head">
    <p class="eyebrow">Verified purchase</p>
    <h1 class="page-head__title">Write a review</h1>
    <p class="page-head__summary">Help other customers by sharing an honest review of your purchase.</p>
</header>

<article class="review-form-wrap">
    <aside class="review-form-product">
        <a class="review-form-product__media" href="{{ route('shop.product', $product) }}">
            <img src="{{ $product->thumbnail ?: ($product->image ?: asset('assets/img/placeholder.svg')) }}"
                 alt="{{ $product->name }}" width="320" height="320">
        </a>
        <div class="review-form-product__body">
            <p class="eyebrow">{{ $product->category?->name }}</p>
            <h2 class="review-form-product__title">
                <a href="{{ route('shop.product', $product) }}">{{ $product->name }}</a>
            </h2>
            <dl class="review-order-meta">
                <div>
                    <dt>Order</dt>
                    <dd>{{ $order->order_number }}</dd>
                </div>
                <div>
                    <dt>Purchased</dt>
                    <dd>{{ $order->created_at->format('M j, Y') }}</dd>
                </div>
            </dl>
        </div>
    </aside>

    <form class="review-form"
          method="post"
          action="{{ route('shop.review.store', ['order_number' => $order->order_number, 'orderItem' => $orderItem->id]) }}"
          enctype="multipart/form-data">
        @csrf

        @if($errors->any())
            <p class="banner banner--err">{{ $errors->first() }}</p>
        @endif

        <section class="review-form__section review-form__section--rating">
            <fieldset class="review-form__rating" data-rating-input>
                <legend>How would you rate this product?</legend>
                <p class="review-form__hint">Select a star rating.</p>
                <div class="review-rating-row">
                    <div class="rating-stars">
                        @for($i = 5; $i >= 1; $i--)
                            <input type="radio" id="rating-{{ $i }}" name="rating" value="{{ $i }}" @checked((int) old('rating', 5) === $i) required>
                            <label for="rating-{{ $i }}" aria-label="{{ $i }} stars">
                                <svg viewBox="0 0 24 24" width="36" height="36" aria-hidden="true">
                                    <path d="M12 2l2.95 6.6 7.05.7-5.3 4.92 1.55 7.18L12 17.85 5.75 21.4 7.3 14.22 2 9.3l7.05-.7L12 2z"
                                          fill="currentColor" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
                                </svg>
                            </label>
                        @endfor
                    </div>
                    <strong class="review-rating-label" data-rating-label aria-live="polite">Excellent</strong>
                </div>
            </fieldset>
        </section>

        <section class="review-form__section">
            <label for="review-title">
                <span>Review title <small>(optional)</small></span>
                <input id="review-title" type="text" name="title" maxlength="200" value="{{ old('title') }}" placeholder="What stood out most?">
            </label>

            <label for="review-content">
                <span>Your review</span>
                <textarea id="review-content" name="content" rows="7" maxlength="5000" required placeholder="Tell us about the quality, look, fit, or anything else that may help another customer.">{{ old('content') }}</textarea>
            </label>
            <div class="review-form__field-meta">
                <span>Be specific and respectful.</span>
                <span><output data-review-count>{{ mb_strlen(old('content', '')) }}</output>/5000</span>
            </div>
        </section>

        <section class="review-form__section">
            @include('partials.file-upload', [
                'name' => 'images[]',
                'label' => 'Add photos (optional)',
                'hint' => 'Up to 5 JPG, PNG or WebP images, 4 MB each.',
                'dropTitle' => 'Add photos to your review',
                'dropHint' => 'Drag and drop or click to browse',
                'multiple' => true,
                'maxFiles' => 5,
            ])
        </section>

        <div class="review-form__actions">
            <button class="btn btn--ghost" type="button" data-navigate="{{ route('shop.order.show', ['order_number' => $order->order_number]) }}">Cancel</button>
            <button class="btn btn--primary" type="submit">Submit review</button>
        </div>
    </form>
</article>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var labels = {1: 'Poor', 2: 'Fair', 3: 'Good', 4: 'Very good', 5: 'Excellent'};
    var ratingLabel = document.querySelector('[data-rating-label]');
    var ratingInputs = document.querySelectorAll('[data-rating-input] input[name="rating"]');
    var reviewContent = document.querySelector('#review-content');
    var reviewCount = document.querySelector('[data-review-count]');

    function updateRatingLabel() {
        var checked = document.querySelector('[data-rating-input] input[name="rating"]:checked');
        if (checked && ratingLabel) {
            ratingLabel.textContent = labels[checked.value] || '';
        }
    }

    ratingInputs.forEach(function (input) {
        input.addEventListener('change', updateRatingLabel);
    });

    if (reviewContent && reviewCount) {
        reviewContent.addEventListener('input', function () {
            reviewCount.textContent = reviewContent.value.length;
        });
    }

    updateRatingLabel();
});
</script>
@endpush
