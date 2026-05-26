@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Checkout</h1>
    <p class="page-head__summary">Step 2 of 3 — delivery and contact details.</p>
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

        <form class="checkout-form" method="post" action="{{ route('shop.checkout.place') }}" data-checkout-delivery>
            @csrf

            <section class="checkout-block" aria-labelledby="checkout-contact-title">
                <h2 id="checkout-contact-title" class="checkout-block__title">Contact</h2>
                <div class="checkout-field checkout-field--floating full">
                    <input type="email" id="customer_email" name="customer_email" required autocomplete="email"
                           value="{{ $checkoutDefaults['customer_email'] ?? old('customer_email') }}" placeholder=" ">
                    <label for="customer_email">Email</label>
                </div>
            </section>

            @include('shop.checkout._delivery-fields', ['deliveryDefaults' => $deliveryDefaults ?? []])

            @include('shop.checkout._voucher-fields', [
                'appliedVoucher' => $appliedVoucher ?? null,
                'discountUsd' => $discountUsd ?? 0,
                'currency' => $currency,
            ])

            @if($gateway->customerFieldsView())
                <section class="checkout-block">
                    @include($gateway->customerFieldsView(), ['gateway' => $gateway])
                </section>
            @endif

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
        'discountUsd' => $discountUsd ?? 0,
        'totalUsd' => $totalUsd ?? $subtotalUsd,
        'currency' => $currency,
    ])
</div>
@endsection
