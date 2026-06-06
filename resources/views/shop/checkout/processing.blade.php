@extends('layouts.checkout')

@section('content')
<div class="checkout-shell">
    <header class="checkout-processing-head">
        <h1 class="checkout-processing-head__title">Complete your payment</h1>
        <p class="checkout-processing-head__summary">Order {{ $order->order_number }} · {{ $gateway->label() }}</p>
        <form method="post" action="{{ route('shop.checkout.cancel', ['order_number' => $order->order_number]) }}" class="checkout-processing-head__back">
            @csrf
            <button type="submit" class="btn btn--ghost">&larr; Back to checkout</button>
        </form>
    </header>

    <div class="checkout-layout">
        <div class="checkout-layout__main">
            <section class="checkout-processing">
                <div class="checkout-processing__order">
                    <p class="checkout-processing__order-no">Order <strong>{{ $order->order_number }}</strong></p>
                    <p class="checkout-processing__total">
                        Total: <strong>{{ strtoupper($order->currency_code) }} {{ number_format((float) $order->total_display, 2) }}</strong>
                    </p>
                </div>

                <div class="checkout-processing__gateway">
                    @include($gateway->processingView(), [
                        'order' => $order,
                        'gateway' => $gateway,
                        'data' => $gatewayData,
                    ])
                </div>
            </section>
        </div>

        <div class="checkout-layout__aside checkout-layout__aside--visible">
            @include('shop.checkout._cart-aside', [
                'lines' => $lines,
                'subtotalUsd' => $subtotalUsd,
                'discountUsd' => $discountUsd ?? 0,
                'shippingUsd' => $shippingUsd ?? 0,
                'taxUsd' => $taxUsd ?? 0,
                'totalUsd' => $totalUsd ?? $subtotalUsd,
                'currency' => $currency,
                'asideNote' => 'Total at checkout: '.strtoupper($order->currency_code).' '.number_format((float) $order->total_display, 2),
            ])
        </div>
    </div>
</div>
@endsection
