@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Thank you</h1>
    <p class="page-head__summary">
        Order <strong>{{ $order->order_number }}</strong>
        @if($order->status === 'paid')
            is confirmed.
        @else
            has been placed.
        @endif
    </p>
</header>

@if($order->status === 'pending')
    <p class="banner banner--info">Your payment proof is being reviewed. We will email you at <strong>{{ $order->customer_email }}</strong> once confirmed.</p>
@endif

<div class="prose">
    @php($isPlaceholderEmail = str_ends_with(strtolower((string) $order->customer_email), '@checkout.pending'))

    @if($order->status === 'paid')
        @if($isPlaceholderEmail)
            <p>Payment confirmed.</p>
        @else
            <p>Payment confirmed — check your inbox at <strong>{{ $order->customer_email }}</strong>.</p>
        @endif
    @else
        @if($isPlaceholderEmail)
            <p>Your order has been received.</p>
        @else
            <p>We emailed your order details to <strong>{{ $order->customer_email }}</strong>.</p>
        @endif
    @endif

    <p>Total (display currency at checkout): {{ $order->currency_code }} {{ number_format((float) $order->total_display, 2) }}</p>
</div>

<section class="order-items">
    <h2 class="product-detail__section-title">Your items</h2>
    <ul class="order-items__list">
        @foreach($order->items as $item)
            <li class="order-items__row">
                <div class="order-items__info">
                    <strong>{{ $item->product_name }}</strong>
                    <span class="muted">× {{ $item->quantity }} — ${{ number_format((float) $item->line_total_usd, 2) }} USD</span>
                </div>
                <div class="order-items__action">
                    @if($item->review)
                        <span class="order-items__reviewed" title="Status: {{ $item->review->status }}">
                            ★ Reviewed
                        </span>
                    @elseif($item->product_id)
                        <a class="btn btn--ghost btn--small"
                           href="{{ route('shop.review.create', ['order_number' => $order->order_number, 'orderItem' => $item->id]) }}">
                            Write a review
                        </a>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</section>

<p class="order-items__continue"><a href="{{ route('shop.products.index') }}">Continue shopping →</a></p>
@endsection
