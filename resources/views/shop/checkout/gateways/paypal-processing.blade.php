@php
    $configured = ($data['configured'] ?? false) === true;
    $paypalOrderId = $data['paypalOrderId'] ?? '';
    $clientId = $data['clientId'] ?? '';
    $webSdkUrl = $data['webSdkUrl'] ?? '';
    $currency = $data['currency'] ?? strtoupper((string) $order->currency_code);
    $sandbox = ($data['sandbox'] ?? false) === true;
    $error = $data['error'] ?? null;
@endphp

<div class="gateway-pane gateway-pane--paypal">
    <h2 class="gateway-pane__title">Pay with PayPal</h2>
    <p>Authorize payment for order <strong>{{ $order->order_number }}</strong> — <strong>{{ strtoupper($order->currency_code) }} {{ number_format((float) $order->total_display, 2) }}</strong>.</p>

    @if(! $configured)
        <p class="gateway-pane__hint gateway-pane__hint--warn">PayPal is not fully configured. Add <strong>Client ID</strong> and <strong>Client Secret</strong> in Admin → Payments → Payment settings, then save.</p>
    @elseif($error)
        <p class="gateway-pane__hint gateway-pane__hint--warn">{{ $error }}</p>
    @elseif($paypalOrderId === '' || $clientId === '' || $webSdkUrl === '')
        <p class="gateway-pane__hint gateway-pane__hint--warn">Unable to load PayPal checkout. Refresh this page or contact support.</p>
    @else
        <ul class="gateway-pane__steps">
            <li>Use the PayPal button below (balance, card, or guest checkout).</li>
            <li>Confirm the amount, then you will return here automatically.</li>
        </ul>

        <div id="paypal-button-container" class="gateway-pane__paypal-buttons"></div>

        @if($sandbox)
            <p class="gateway-pane__hint">Sandbox mode — use a <a href="https://developer.paypal.com/dashboard/accounts" target="_blank" rel="noopener">PayPal sandbox buyer account</a> for testing.</p>
        @endif

        @push('scripts')
        <script src="{{ $webSdkUrl }}" data-sdk-integration-source="paypal-web-sdk-v6"></script>
        <script>
        (async function () {
            var paypalOrderId = @json($paypalOrderId);
            var clientId = @json($clientId);
            var currency = @json($currency);
            var confirmUrl = @json(route('shop.checkout.confirm', ['order_number' => $order->order_number]));
            var csrf = @json(csrf_token());
            var container = document.getElementById('paypal-button-container');

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

            if (typeof paypal === 'undefined' || typeof paypal.createInstance !== 'function') {
                container.innerHTML =
                    '<p class="gateway-pane__hint gateway-pane__hint--warn">PayPal could not be loaded. Check your connection or ad blocker.</p>';
                return;
            }

            try {
                var sdk = await paypal.createInstance({
                    clientId: clientId,
                    components: ['paypal-payments'],
                    pageType: 'checkout'
                });
                var paymentMethods = await sdk.findEligibleMethods({ currencyCode: currency });
                if (!paymentMethods.isEligible('paypal')) {
                    container.innerHTML = '<p class="gateway-pane__hint gateway-pane__hint--warn">PayPal is not available for this checkout. Please choose another payment method.</p>';
                    return;
                }

                var session = sdk.createPayPalOneTimePaymentSession({
                    onApprove: function (data) {
                        document.dispatchEvent(new CustomEvent('checkout:loading', {
                            detail: { message: 'Confirming your PayPal payment...' }
                        }));
                        return postConfirm({ paypal_order_id: data.orderId || paypalOrderId }).catch(function (err) {
                            document.dispatchEvent(new CustomEvent('checkout:loading-end'));
                            throw err;
                        });
                    },
                    onCancel: function () {
                        document.dispatchEvent(new CustomEvent('checkout:loading-end'));
                    },
                    onError: function (err) {
                        document.dispatchEvent(new CustomEvent('checkout:loading-end'));
                        console.error('PayPal error', err);
                        alert('PayPal reported an error. Please try again or choose another payment method.');
                    }
                });
                var button = document.createElement('paypal-button');
                container.appendChild(button);
                button.addEventListener('click', function () {
                    document.dispatchEvent(new CustomEvent('checkout:loading', {
                        detail: { message: 'Opening PayPal checkout...' }
                    }));
                    session.start({ presentationMode: 'auto' }, Promise.resolve({ orderId: paypalOrderId })).catch(function (err) {
                        document.dispatchEvent(new CustomEvent('checkout:loading-end'));
                        console.error('PayPal start error', err);
                        alert('PayPal could not be started. Please try again or choose another payment method.');
                    });
                });
            } catch (err) {
                console.error('PayPal SDK v6 initialization failed', err);
                container.innerHTML = '<p class="gateway-pane__hint gateway-pane__hint--warn">PayPal could not be loaded. Please choose another payment method.</p>';
            }
        })();
        </script>
        @endpush
    @endif
</div>
