@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Thank you</h1>
    <p class="page-head__summary">Order <strong>{{ $order->order_number }}</strong> is confirmed.</p>
</header>

<div class="prose">
    <p>We sent a confirmation to <strong>{{ $order->customer_email }}</strong>.</p>
    <h2>Items</h2>
    <ul>
        @foreach($order->items as $item)
            <li>{{ $item->product_name }} × {{ $item->quantity }} — ${{ number_format((float) $item->line_total_usd, 2) }} USD</li>
        @endforeach
    </ul>
    <p>Total (display currency at checkout): {{ $order->currency_code }} {{ number_format((float) $order->total_display, 2) }}</p>
    <p><a href="{{ route('shop.products.index') }}">Continue shopping</a></p>
</div>
@endsection
