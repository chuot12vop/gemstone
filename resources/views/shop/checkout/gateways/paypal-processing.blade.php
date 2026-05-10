<div class="gateway-pane gateway-pane--paypal">
    <h2 class="gateway-pane__title">Pay with PayPal</h2>
    <p>Click the button below to open PayPal and authorize the payment for order <strong>{{ $order->order_number }}</strong>.</p>

    <ul class="gateway-pane__steps">
        <li>Sign in to your PayPal account (or pay as guest).</li>
        <li>Confirm the amount of <strong>{{ strtoupper($order->currency_code) }} {{ number_format((float) $order->total_display, 2) }}</strong>.</li>
        <li>Return here automatically to receive your confirmation.</li>
    </ul>

    <form method="post" action="{{ route('shop.checkout.confirm', ['order_number' => $order->order_number]) }}" class="gateway-pane__cta">
        @csrf
        <input type="hidden" name="gateway_transaction_id" value="PP-DEMO-{{ strtoupper(Str::random(8)) }}">
        <button class="btn btn--primary btn--paypal" type="submit">
            Pay with PayPal
        </button>
    </form>

    <p class="gateway-pane__hint">Demo build — clicking the button will simulate a successful PayPal capture.</p>
</div>
