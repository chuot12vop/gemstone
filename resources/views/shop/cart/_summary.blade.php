<aside class="cart-page__summary" aria-labelledby="cart-summary-title">
    <h2 id="cart-summary-title" class="cart-page__summary-title">Order summary</h2>
    <p class="cart-page__subtotal">
        <span>Subtotal</span>
        <strong data-cart-subtotal>{{ $currency->formatUsd($subtotalUsd) }}</strong>
    </p>
    <p class="cart-page__shipping">
        <span>Shipping</span>
        <strong>{{ ($shippingUsd ?? 0) <= 0 ? 'FREE' : $currency->formatUsd((float) $shippingUsd) }}</strong>
    </p>
    @if(($taxUsd ?? 0) > 0)
        <p class="cart-page__tax">
            <span>Taxes</span>
            <strong>{{ $currency->formatUsd((float) $taxUsd) }}</strong>
        </p>
    @endif
    <p class="cart-page__total">
        <span>Total</span>
        <strong>{{ $currency->formatUsd((float) ($totalUsd ?? $subtotalUsd)) }}</strong>
    </p>
    <p class="cart-page__note">Have a discount code? Enter it at checkout.</p>
    <a class="btn btn--primary cart-page__checkout" href="{{ route('shop.checkout') }}">Secure Checkout</a>
    @guest
        <p class="cart-page__auth-note">
            Have an account? <a href="{{ route('login') }}">Log in</a> to check out faster.
            <a href="{{ route('register') }}">Create account</a>
        </p>
    @endguest
    @include('shop.partials.payment-icons')
</aside>
