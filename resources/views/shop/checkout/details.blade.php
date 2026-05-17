@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Checkout</h1>
    <p class="page-head__summary">Step 2 of 3 — please tell us where to ship your order.</p>
</header>

@include('shop.checkout._stepper', ['step' => $step])

<div class="checkout-layout">
    <div class="checkout-layout__main">
        <div class="checkout-summary-banner">
            <span class="checkout-summary-banner__label">Selected method</span>
            <span class="checkout-summary-banner__method">
                <span class="checkout-summary-banner__icon">{!! $gateway->iconHtml() !!}</span>
                <strong>{{ $gateway->label() }}</strong>
            </span>
            <a class="checkout-summary-banner__change" href="{{ route('shop.checkout') }}">Change</a>
        </div>

        <form class="checkout-form" method="post" action="{{ route('shop.checkout.place') }}">
            @csrf
            <div class="form-grid">
                <label>
                    Full name
                    <input type="text" name="customer_name" required value="{{ $checkoutDefaults['customer_name'] ?? old('customer_name') }}">
                </label>
                <label>
                    Email
                    <input type="email" name="customer_email" required value="{{ $checkoutDefaults['customer_email'] ?? old('customer_email') }}">
                </label>
                <label class="full">
                    Shipping address
                    <textarea name="shipping_address" rows="4" required>{{ old('shipping_address') }}</textarea>
                </label>
                @if($gateway->customerFieldsView())
                    @include($gateway->customerFieldsView(), ['gateway' => $gateway])
                @endif
            </div>

            @if($errors->any())
                <p class="banner banner--err">{{ $errors->first() }}</p>
            @endif

            <div class="checkout-actions">
                <a class="btn btn--ghost" href="{{ route('shop.checkout') }}">&larr; Back</a>
                <button class="btn btn--primary" type="submit">Continue to payment</button>
            </div>
        </form>
    </div>

    @include('shop.checkout._cart-aside', [
        'lines' => $lines,
        'subtotalUsd' => $subtotalUsd,
        'currency' => $currency,
    ])
</div>
@endsection
