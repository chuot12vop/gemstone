<aside class="cart-page__summary" aria-labelledby="cart-summary-title">
    <h2 id="cart-summary-title" class="cart-page__summary-title">Order summary</h2>
    <p class="cart-page__subtotal">
        <span>Subtotal</span>
        <strong data-cart-subtotal>{{ $currency->formatUsd($subtotalUsd) }}</strong>
    </p>
    <p class="cart-page__shipping" data-cart-shipping-row>
        <span>Shipping</span>
        <strong data-cart-shipping>{{ ($shippingUsd ?? 0) <= 0 ? 'FREE' : $currency->formatUsd((float) $shippingUsd) }}</strong>
    </p>
    <p class="cart-page__tax" data-cart-tax-row @if(($taxUsd ?? 0) <= 0) hidden @endif>
        <span>Taxes</span>
        <strong data-cart-tax>{{ $currency->formatUsd((float) ($taxUsd ?? 0)) }}</strong>
    </p>
    <p class="cart-page__total">
        <span>Total</span>
        <strong data-cart-total>{{ $currency->formatUsd((float) ($totalUsd ?? $subtotalUsd)) }}</strong>
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
