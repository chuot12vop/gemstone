@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Order {{ $order->order_number }}</h1>
    <p class="page-head__summary">Placed {{ $order->created_at->format('F j, Y g:i A') }} · {{ ucfirst($order->status) }}</p>
</header>

<section class="account-order-detail">
    <h2>Items</h2>
    <ul>
        @foreach($order->items as $item)
            <li>{{ $item->product_name }} × {{ $item->quantity }} — {{ $currency->formatUsd((float) $item->line_total_usd) }}</li>
        @endforeach
    </ul>
    <p><strong>Subtotal:</strong> {{ $currency->formatUsd((float) $order->subtotal_usd) }}</p>
    @if((float) ($order->discount_usd ?? 0) > 0)
        <p><strong>Discount</strong> @if($order->voucher_code)({{ $order->voucher_code }})@endif: −{{ $currency->formatUsd((float) $order->discount_usd) }}</p>
        <p><strong>Total:</strong> {{ $currency->formatUsd(max(0, (float) $order->subtotal_usd - (float) $order->discount_usd)) }}</p>
    @endif
    <p><strong>Shipping to:</strong><br>{!! nl2br(e($order->shipping_address)) !!}</p>
    @if($order->shipping_phone)
        <p><strong>Phone:</strong> {{ $order->shipping_phone }}</p>
    @endif
</section>

<p><a href="{{ route('shop.account.orders') }}">← All orders</a></p>
@endsection
