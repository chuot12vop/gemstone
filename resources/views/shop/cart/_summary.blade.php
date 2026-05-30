<aside class="cart-page__summary" aria-labelledby="cart-summary-title">
    <h2 id="cart-summary-title" class="cart-page__summary-title">Order summary</h2>
    <p class="cart-page__subtotal">
        <span>Subtotal</span>
        <strong data-cart-subtotal>{{ $currency->formatUsd($subtotalUsd) }}</strong>
    </p>
    <p class="cart-page__note">Have a discount code? Enter it at checkout.</p>
    @auth
        <a class="btn btn--primary cart-page__checkout" href="{{ route('shop.checkout') }}">Secure Checkout</a>
    @else
        <a class="btn btn--primary cart-page__checkout" href="{{ route('login') }}">Sign in to checkout</a>
        <p class="cart-page__auth-note">
            Have an account? <a href="{{ route('login') }}">Log in</a> to check out faster.
            <a href="{{ route('register') }}">Create account</a>
        </p>
    @endauth
    @include('shop.partials.payment-icons')
</aside>
