<div class="gateway-pane gateway-pane--zelle">
    <h2 class="gateway-pane__title">Pay with Zelle</h2>

    <ul class="gateway-pane__steps">
        <li>Open your bank app and scan the Zelle QR below.</li>
        <li>Send exactly <strong>{{ strtoupper($data['currency']) }} {{ $data['amount'] }}</strong>@if(! empty($data['payeeLabel'])) to <strong>{{ $data['payeeLabel'] }}</strong>@endif.</li>
        <li>Use memo: <strong>{{ $data['memo'] }}</strong> if your bank allows it.</li>
        <li>Upload a screenshot of the completed transfer below.</li>
    </ul>

    @if(! empty($data['qrImage']))
        <div class="gateway-pane__qr-wrap">
            <img class="gateway-pane__qr-img" src="{{ $data['qrImage'] }}" width="220" height="220" alt="Zelle QR code">
            @if(! empty($data['payeeLabel']))
                <p class="gateway-pane__qr-meta"><strong>{{ $data['payeeLabel'] }}</strong></p>
            @endif
        </div>
    @else
        <p class="banner banner--err">Zelle QR is not configured. Please contact support.</p>
    @endif

    @include('shop.checkout.gateways._transfer-proof-form', ['order' => $order])
</div>
