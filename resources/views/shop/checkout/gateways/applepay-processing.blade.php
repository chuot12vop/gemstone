<div class="gateway-pane gateway-pane--applepay">
    <h2 class="gateway-pane__title">Pay with Apple Pay</h2>
    <p>Use Touch&nbsp;ID, Face&nbsp;ID, or your device passcode to authorize the payment for order <strong>{{ $order->order_number }}</strong>.</p>

    <ul class="gateway-pane__steps">
        <li>Tap the Apple Pay button below.</li>
        <li>Authenticate on your Apple device.</li>
        <li>Stay on this page until we confirm the payment.</li>
    </ul>

    <form method="post" action="{{ route('shop.checkout.confirm', ['order_number' => $order->order_number]) }}" class="gateway-pane__cta">
        @csrf
        <input type="hidden" name="gateway_transaction_id" value="AP-DEMO-{{ strtoupper(Str::random(8)) }}">
        <button class="btn btn--applepay" type="submit" aria-label="Buy with Apple Pay">
            <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
                <path fill="currentColor" d="M16.4 12.3c0-2 1.7-3 1.8-3-1-1.5-2.5-1.7-3-1.7-1.3-.1-2.6.8-3.2.8-.7 0-1.7-.7-2.8-.7-1.4 0-2.7.8-3.5 2.1-1.5 2.6-.4 6.4 1 8.5.7 1 1.6 2.2 2.7 2.1 1.1 0 1.5-.7 2.8-.7s1.7.7 2.8.7c1.2 0 2-1 2.7-2 .8-1.2 1.2-2.4 1.2-2.5-.1 0-2.5-.9-2.5-3.6ZM14.1 6.2c.6-.8 1-1.8.9-2.9-.9 0-1.9.6-2.6 1.4-.6.7-1 1.7-.9 2.7 1 0 2-.5 2.6-1.2Z"/>
            </svg>
            <span>Pay</span>
        </button>
    </form>

    <p class="gateway-pane__hint">Demo build — clicking the button will simulate a successful Apple Pay session.</p>
</div>
