@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Order <span class="account-order-no">{{ $order->order_number }}</span></h1>
    <p class="page-head__summary">Placed {{ $order->created_at->format('F j, Y g:i A') }} · {{ ucfirst($order->status) }}</p>
</header>

<div class="account-grid">
    <section class="account-card">
        <h2 class="account-card__title">Items</h2>
        <ul class="account-order-items">
            @foreach($order->items as $item)
                @php
                    $product = $item->product;
                    $thumb = $product
                        ? ($product->thumbnail ?: ($product->image ?: asset('assets/img/placeholder.svg')))
                        : asset('assets/img/placeholder.svg');
                @endphp
                <li class="account-order-items__row">
                    <img
                        class="account-order-items__thumb"
                        src="{{ $thumb }}"
                        alt="{{ $item->product_name }}"
                        width="52"
                        height="52"
                        loading="lazy"
                    >
                    <div class="account-order-items__info">
                        <strong>{{ $item->product_name }}</strong>
                        <span class="muted">× {{ $item->quantity }} — {{ $currency->formatUsd((float) $item->line_total_usd) }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
        <dl class="account-order-totals">
            <div class="account-order-totals__row">
                <dt>Subtotal</dt>
                <dd>{{ $currency->formatUsd((float) $order->subtotal_usd) }}</dd>
            </div>
            @if((float) ($order->discount_usd ?? 0) > 0)
                <div class="account-order-totals__row">
                    <dt>Discount @if($order->voucher_code)({{ $order->voucher_code }})@endif</dt>
                    <dd>−{{ $currency->formatUsd((float) $order->discount_usd) }}</dd>
                </div>
                <div class="account-order-totals__row account-order-totals__row--total">
                    <dt>Total</dt>
                    <dd>{{ $currency->formatUsd(max(0, (float) $order->subtotal_usd - (float) $order->discount_usd)) }}</dd>
                </div>
            @endif
        </dl>
    </section>

    <section class="account-card">
        <h2 class="account-card__title">Shipping to</h2>
        <dl class="account-dl account-dl--lined">
            <div class="account-dl__row">
                <dt>Address</dt>
                <dd>{!! nl2br(e($order->shipping_address)) !!}</dd>
            </div>
            @if($order->shipping_phone)
                <div class="account-dl__row">
                    <dt>Phone</dt>
                    <dd>{{ $order->shipping_phone }}</dd>
                </div>
            @endif
        </dl>
    </section>
</div>

<p class="account-back-link"><a href="{{ route('shop.account.orders') }}">← All orders</a></p>
@endsection
