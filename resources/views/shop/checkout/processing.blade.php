@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Complete your payment</h1>
    <p class="page-head__summary">Step 3 of 3 — finish your payment with {{ $gateway->label() }}.</p>
</header>

@include('shop.checkout._stepper', ['step' => $step])

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

    @include('shop.checkout._cart-aside', [
        'lines' => $lines,
        'subtotalUsd' => $subtotalUsd,
        'currency' => $currency,
        'asideNote' => 'Total at checkout: '.strtoupper($order->currency_code).' '.number_format((float) $order->total_display, 2),
    ])
</div>
@endsection
