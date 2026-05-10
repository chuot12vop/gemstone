@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Checkout</h1>
    <p class="page-head__summary">Step 1 of 3 — choose how you would like to pay.</p>
</header>

@include('shop.checkout._stepper', ['step' => $step])

@if($errors->any())
    <p class="banner banner--err">{{ $errors->first() }}</p>
@endif

<form class="checkout-form" method="post" action="{{ route('shop.checkout.method') }}">
    @csrf
    <fieldset class="payment-methods">
        <legend class="sr-only">Payment method</legend>
        @foreach($methods as $method)
            <label class="payment-card">
                <input type="radio" name="payment_method" value="{{ $method->code() }}"
                       @checked($selected === $method->code() || (! $selected && $loop->first))
                       required>
                <span class="payment-card__icon">{!! $method->iconHtml() ?: '<span class="payment-card__icon-placeholder">'.strtoupper(substr($method->label(),0,1)).'</span>' !!}</span>
                <span class="payment-card__body">
                    <span class="payment-card__label">{{ $method->label() }}</span>
                    @if($method->description() !== '')
                        <span class="payment-card__desc">{{ $method->description() }}</span>
                    @endif
                </span>
                <span class="payment-card__check" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7"/></svg>
                </span>
            </label>
        @endforeach
    </fieldset>

    <div class="checkout-actions">
        <a class="btn btn--ghost" href="{{ route('shop.cart') }}">&larr; Back to cart</a>
        <button class="btn btn--primary" type="submit">Continue</button>
    </div>
</form>
@endsection
