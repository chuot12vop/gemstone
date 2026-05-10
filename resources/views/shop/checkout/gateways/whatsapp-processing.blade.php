<div class="gateway-pane gateway-pane--whatsapp">
    <h2 class="gateway-pane__title">Confirm via WhatsApp</h2>
    <p>Tap the button below to open WhatsApp with a pre-filled message. Our team will send you payment instructions and confirm your order shortly.</p>

    <ul class="gateway-pane__steps">
        <li>Open WhatsApp by tapping the green button below.</li>
        <li>Send the pre-filled message — it includes your order number.</li>
        <li>You will receive payment instructions and a confirmation receipt.</li>
    </ul>

    @if(! empty($data['phone']))
        <a class="btn btn--primary btn--whatsapp" target="_blank" rel="noopener" href="{{ $data['whatsappUrl'] }}">
            Open WhatsApp
        </a>
    @else
        <p class="banner banner--err">WhatsApp number is not configured. Please contact support.</p>
    @endif

    @if(! empty($data['message']))
        <details class="gateway-pane__details">
            <summary>Preview message</summary>
            <pre class="gateway-pane__pre">{{ $data['message'] }}</pre>
        </details>
    @endif

    <form method="post" action="{{ route('shop.checkout.confirm', ['order_number' => $order->order_number]) }}" class="gateway-pane__cta">
        @csrf
        <button class="btn btn--ghost" type="submit">I've sent the message</button>
    </form>
</div>
