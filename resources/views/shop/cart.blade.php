@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Your cart</h1>
</header>

@if(count($lines) === 0)
    <p class="empty-state">Your cart is empty. <a href="{{ route('shop.products.index') }}">Continue shopping</a></p>
@else
    <ul class="cart-list">
        @foreach($lines as $row)
            @php $p = $row['product']; @endphp
            <li class="cart-line">
                <a class="cart-line__thumb" href="{{ route('shop.product', $p) }}">
                    <img src="{{ $p->image ?: asset('assets/img/placeholder.svg') }}" alt="" width="96" height="96">
                </a>
                <div class="cart-line__body">
                    <h2 class="cart-line__title"><a href="{{ route('shop.product', $p) }}">{{ $p->name }}</a></h2>
                    <p class="cart-line__price">{{ $currency->formatUsd((float) $p->price_usd) }} each</p>
                    <label>
                        Qty
                        <input form="cart-update" type="number" name="qty[{{ $p->id }}]" value="{{ $row['quantity'] }}" min="0" max="{{ $p->stock }}">
                    </label>
                </div>
                <div class="cart-line__total">{{ $currency->formatUsd((float) $row['line_usd']) }}</div>
                <div class="cart-line__remove">
                    <form method="post" action="{{ route('shop.cart.remove') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $p->id }}">
                        <button type="submit" class="link-btn">Remove</button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>

    <form id="cart-update" method="post" action="{{ route('shop.cart.update') }}">
        @csrf
    </form>
    <div class="cart-actions">
        <button class="btn btn--ghost" type="submit" form="cart-update">Update cart</button>
        <a class="btn btn--primary" href="{{ route('shop.checkout') }}">Checkout</a>
    </div>

    <p class="cart-subtotal">Subtotal (USD basis): <strong>${{ number_format($subtotalUsd, 2) }}</strong> · Display: <strong>{{ $currency->formatUsd((float) $subtotalUsd) }}</strong></p>
@endif
@endsection
