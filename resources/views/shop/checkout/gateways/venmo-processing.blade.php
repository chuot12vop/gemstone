<div class="gateway-pane gateway-pane--venmo">
    <h2 class="gateway-pane__title">Pay with Venmo</h2>

    <ul class="gateway-pane__steps">
        <li>Scan the QR code or tap <strong>Open Venmo</strong> — amount and order note are pre-filled.</li>
        <li>Complete the payment in the Venmo app.</li>
        <li>Upload a screenshot of the completed transfer below.</li>
    </ul>

    @if(! empty($data['username']) && ! empty($data['payUrl']))
        <div class="gateway-pane__qr-wrap">
            <canvas id="venmo-qr-canvas" class="gateway-pane__qr" width="220" height="220" aria-label="Venmo payment QR code" data-pay-url="{{ $data['payUrl'] }}"></canvas>
            <p class="gateway-pane__qr-meta">
                <strong>{{ '@'.$data['username'] }}</strong><br>
                {{ strtoupper($data['currency']) }} {{ $data['amount'] }} · {{ $data['note'] }}
            </p>
        </div>

        <a class="btn btn--primary btn--venmo" href="{{ $data['payUrl'] }}" target="_blank" rel="noopener">Open Venmo</a>
    @else
        <p class="banner banner--err">Venmo is not configured. Please contact support.</p>
    @endif

    @include('shop.checkout.gateways._transfer-proof-form', ['order' => $order])
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
(function () {
    const canvas = document.getElementById('venmo-qr-canvas');
    if (!canvas || typeof QRCode === 'undefined') return;
    const url = canvas.getAttribute('data-pay-url');
    if (!url) return;
    QRCode.toCanvas(canvas, url, { width: 220, margin: 2, color: { dark: '#008cff' } });
})();
</script>
@endpush
