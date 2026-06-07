@php
    $configured = ($data['configured'] ?? false) === true;
    $publishableKey = $data['publishableKey'] ?? '';
    $clientSecret = $data['clientSecret'] ?? '';
    $paymentIntentId = $data['paymentIntentId'] ?? '';
    $amount = (int) ($data['amount'] ?? 0);
    $currency = $data['currency'] ?? 'usd';
    $country = $data['country'] ?? 'US';
    $testMode = ($data['testMode'] ?? false) === true;
    $error = $data['error'] ?? null;
@endphp

<div class="gateway-pane gateway-pane--applepay">
    <h2 class="gateway-pane__title">Pay with Apple Pay</h2>
    <p>Authorize payment for order <strong>{{ $order->order_number }}</strong> — <strong>{{ strtoupper($order->currency_code) }} {{ number_format((float) $order->total_display, 2) }}</strong>.</p>

    @if(! $configured)
        <p class="gateway-pane__hint gateway-pane__hint--warn">Apple Pay is not fully configured. Add your <strong>Stripe Publishable Key</strong> and <strong>Secret Key</strong> in Admin → Payments → Payment settings, then save.</p>
    @elseif($error)
        <p class="gateway-pane__hint gateway-pane__hint--warn">{{ $error }}</p>
    @elseif($publishableKey === '' || $clientSecret === '' || $paymentIntentId === '' || $amount <= 0)
        <p class="gateway-pane__hint gateway-pane__hint--warn">Unable to load Apple Pay checkout. Refresh this page or contact support.</p>
    @else
        <ul class="gateway-pane__steps">
            <li>Use the Apple Pay button below on a supported device (Safari on Mac, iPhone, or iPad).</li>
            <li>Authenticate with Touch&nbsp;ID, Face&nbsp;ID, or your device passcode.</li>
            <li>Stay on this page until we confirm your payment.</li>
        </ul>

        <div id="apple-pay-button-container" class="gateway-pane__apple-pay"></div>
        <p id="apple-pay-unavailable" class="gateway-pane__hint gateway-pane__hint--warn" hidden>Apple Pay is not available on this browser or device. Try Safari on an Apple device, or choose another payment method.</p>

        @if($testMode)
            <p class="gateway-pane__hint">Test mode — use <a href="https://dashboard.stripe.com/test/dashboard" target="_blank" rel="noopener">Stripe test keys</a> and register your domain under Stripe → Settings → Payment methods → Apple Pay.</p>
        @endif

        @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
        (function () {
            var publishableKey = @json($publishableKey);
            var clientSecret = @json($clientSecret);
            var paymentIntentId = @json($paymentIntentId);
            var amount = @json($amount);
            var currency = @json($currency);
            var country = @json($country);
            var confirmUrl = @json(route('shop.checkout.confirm', ['order_number' => $order->order_number]));
            var csrf = @json(csrf_token());
            var orderLabel = @json('Order '.$order->order_number);

            var buttonContainer = document.getElementById('apple-pay-button-container');
            var unavailableEl = document.getElementById('apple-pay-unavailable');

            function postConfirm(body) {
                return fetch(confirmUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                }).then(function (res) {
                    return res.json().then(function (data) {
                        if (res.ok && data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }
                        var msg = (data && data.message) ? data.message : 'Payment could not be confirmed.';
                        throw new Error(msg);
                    });
                });
            }

            if (typeof Stripe === 'undefined') {
                buttonContainer.innerHTML = '<p class="gateway-pane__hint gateway-pane__hint--warn">Stripe could not be loaded. Check your connection or ad blocker.</p>';
                return;
            }

            var stripe = Stripe(publishableKey);
            var paymentRequest = stripe.paymentRequest({
                country: country,
                currency: currency,
                total: {
                    label: orderLabel,
                    amount: amount,
                },
                requestPayerEmail: true,
            });

            var elements = stripe.elements();
            var prButton = elements.create('paymentRequestButton', {
                paymentRequest: paymentRequest,
                style: {
                    paymentRequestButton: {
                        type: 'buy',
                        theme: 'black',
                        height: '48px',
                    },
                },
            });

            paymentRequest.canMakePayment().then(function (result) {
                if (result && result.applePay) {
                    prButton.mount('#apple-pay-button-container');
                } else {
                    buttonContainer.hidden = true;
                    unavailableEl.hidden = false;
                }
            });

            paymentRequest.on('paymentmethod', function (ev) {
                stripe.confirmCardPayment(clientSecret, {
                    payment_method: ev.paymentMethod.id,
                }, {
                    handleActions: true,
                }).then(function (result) {
                    if (result.error) {
                        ev.complete('fail');
                        alert(result.error.message || 'Apple Pay could not be completed.');
                        return;
                    }

                    ev.complete('success');

                    var intent = result.paymentIntent;
                    if (intent && intent.status === 'succeeded') {
                        postConfirm({ payment_intent_id: intent.id || paymentIntentId }).catch(function (err) {
                            alert(err.message || 'Payment could not be confirmed.');
                        });
                    }
                });
            });
        })();
        </script>
        @endpush
    @endif
</div>
