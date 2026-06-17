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

                @include('shop.checkout._express-checkout', ['expressCheckout' => $expressCheckout ?? []])

                <section class="checkout-block" aria-labelledby="checkout-contact-title">
                    <h2 id="checkout-contact-title" class="checkout-block__title">Contact</h2>
                    @guest
                        <p class="checkout-contact-auth">
                            Have an account?
                            <a href="{{ route('login', ['redirect' => route('shop.checkout')]) }}">Log in</a>
                            or <a href="{{ route('register') }}">create an account</a> for faster checkout.
                        </p>
                    @endguest
                    <div class="checkout-field checkout-field--floating full">
                        <input type="email" id="customer_email" name="customer_email" required autocomplete="email"
                               value="{{ $checkoutDefaults['customer_email'] ?? old('customer_email') }}" placeholder=" ">
                        <label for="customer_email">Email</label>
                    </div>
                    <label class="checkout-checkbox checkout-checkbox--contact full">
                        <input type="checkbox" name="marketing_email_opt_in" value="1" @checked(old('marketing_email_opt_in', true))>
                        <span>Don't Miss Out. Sign up for VIP access to sales, promos and new collections - straight to your inbox.</span>
                    </label>
                </section>

                @include('shop.checkout._delivery-fields', ['deliveryDefaults' => $deliveryDefaults ?? []])

                @include('shop.checkout._payment-methods', [
                    'methods' => $methods,
                    'selected' => $selected,
                ])

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
                'shippingUsd' => $shippingUsd ?? 0,
                'taxUsd' => $taxUsd ?? 0,
                'totalUsd' => $totalUsd ?? $subtotalUsd,
                'currency' => $currency,
            ])
        </div>
    </div>
</div>
@endsection
