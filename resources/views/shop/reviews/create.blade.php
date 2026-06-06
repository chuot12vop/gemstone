@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Write a review</h1>
    <p class="page-head__summary">Order <strong>{{ $order->order_number }}</strong> — share your thoughts on <strong>{{ $orderItem->product_name }}</strong>.</p>
</header>

<article class="review-form-wrap">
    <aside class="review-form-product">
        <a href="{{ route('shop.product', $product) }}">
            <img src="{{ $product->thumbnail ?: ($product->image ?: asset('assets/img/placeholder.svg')) }}"
                 alt="{{ $product->name }}" width="120" height="120">
        </a>
        <div>
            <p class="eyebrow">{{ $product->category?->name }}</p>
            <h2 class="review-form-product__title">
                <a href="{{ route('shop.product', $product) }}">{{ $product->name }}</a>
            </h2>
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

        <fieldset class="review-form__rating" data-rating-input>
            <legend>Your rating</legend>
            <div class="rating-stars">
                @for($i = 5; $i >= 1; $i--)
                    <input type="radio" id="rating-{{ $i }}" name="rating" value="{{ $i }}" @checked((int) old('rating', 5) === $i) required>
                    <label for="rating-{{ $i }}" aria-label="{{ $i }} stars">
                        <svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">
                            <path d="M12 2l2.95 6.6 7.05.7-5.3 4.92 1.55 7.18L12 17.85 5.75 21.4 7.3 14.22 2 9.3l7.05-.7L12 2z"
                                  fill="currentColor" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
                        </svg>
                    </label>
                @endfor
            </div>
        </fieldset>

        <label>
            Title (optional)
            <input type="text" name="title" maxlength="200" value="{{ old('title') }}" placeholder="Sum it up in a few words">
        </label>

        <label>
            Your review
            <textarea name="content" rows="6" maxlength="5000" required>{{ old('content') }}</textarea>
        </label>

        @include('partials.file-upload', [
            'name' => 'images[]',
            'label' => 'Photos (up to 5)',
            'hint' => 'JPG, PNG or WebP. Max 4 MB each.',
            'multiple' => true,
            'maxFiles' => 5,
        ])

        <div class="review-form__actions">
            <a class="btn btn--ghost" href="{{ route('shop.order.show', ['order_number' => $order->order_number]) }}">Cancel</a>
            <button class="btn btn--primary" type="submit">Submit review</button>
        </div>
    </form>
</article>
@endsection
