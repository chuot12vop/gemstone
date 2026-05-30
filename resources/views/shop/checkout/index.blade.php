@extends('layouts.checkout')

@section('content')
<div class="checkout-shell">
    @include('shop.checkout._free-shipping-bar', [
        'shippingProgress' => $shippingProgress,
        'currency' => $currency,
    ])

    <button type="button" class="checkout-mobile-summary" data-checkout-summary-toggle aria-expanded="false">
        <span class="checkout-mobile-summary__label" data-checkout-summary-toggle-label>Show order summary</span>
        <span class="checkout-mobile-summary__total" data-checkout-total>{{ $currency->formatUsd((float) ($totalUsd ?? $subtotalUsd)) }}</span>
    </button>

    <div class="checkout-layout">
        <div class="checkout-layout__main">
            @if($errors->any())
                <p class="banner banner--err">{{ $errors->first() }}</p>
            @endif

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

                @include('shop.checkout._payment-methods', [
                    'methods' => $methods,
                    'selected' => $selected,
                ])

                @foreach($methods as $method)
                    @if($method->customerFieldsView())
                        <section class="checkout-block checkout-gateway-fields"
                                 data-gateway-fields="{{ $method->code() }}"
                                 @if(($selected ?? '') !== $method->code() && !(empty($selected) && $loop->first)) hidden @endif>
                            @include($method->customerFieldsView(), ['gateway' => $method])
                        </section>
                    @endif
                @endforeach

                @include('shop.checkout._voucher-fields', [
                    'appliedVoucher' => $appliedVoucher ?? null,
                    'discountUsd' => $discountUsd ?? 0,
                    'currency' => $currency,
                ])

                <div class="checkout-actions">
                    <button class="btn btn--primary btn--checkout-pay" type="submit">Pay now</button>
                </div>
            </form>
        </div>

        <div class="checkout-layout__aside" data-checkout-aside-panel>
            @include('shop.checkout._cart-aside', [
                'lines' => $lines,
                'subtotalUsd' => $subtotalUsd,
                'discountUsd' => $discountUsd ?? 0,
                'totalUsd' => $totalUsd ?? $subtotalUsd,
                'currency' => $currency,
            ])
        </div>
    </div>
</div>
@endsection
