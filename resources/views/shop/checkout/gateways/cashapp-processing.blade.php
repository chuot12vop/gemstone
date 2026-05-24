<div class="gateway-pane gateway-pane--cashapp">
    <h2 class="gateway-pane__title">Pay with Cash App</h2>

    <ul class="gateway-pane__steps">
        <li>Scan the QR code below in Cash App (or use the pay link).</li>
        <li>Send exactly <strong>{{ strtoupper($data['currency']) }} {{ $data['amount'] }}</strong>@if(! empty($data['cashtag'])) to <strong>{{ '$'.$data['cashtag'] }}</strong>@endif.</li>
        <li>Upload a screenshot of the completed payment below.</li>
    </ul>

    @if(! empty($data['qrImage']))
        <div class="gateway-pane__qr-wrap">
            <img class="gateway-pane__qr-img" src="{{ $data['qrImage'] }}" width="220" height="220" alt="Cash App QR code">
            @if(! empty($data['cashtag']))
                <p class="gateway-pane__qr-meta"><strong>{{ '$'.$data['cashtag'] }}</strong></p>
            @endif
        </div>
    @else
        <p class="banner banner--err">Cash App QR is not configured. Please contact support.</p>
    @endif

    @if(! empty($data['payUrl']))
        <a class="btn btn--primary btn--cashapp" href="{{ $data['payUrl'] }}" target="_blank" rel="noopener">Open Cash App</a>
    @endif

    @include('shop.checkout.gateways._transfer-proof-form', ['order' => $order])
</div>
