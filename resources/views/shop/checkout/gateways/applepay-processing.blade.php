@php
    $configured = ($data['configured'] ?? false) === true;
    $paypalOrderId = $data['paypalOrderId'] ?? '';
    $clientId = $data['clientId'] ?? '';
    $clientToken = $data['clientToken'] ?? '';
    $webSdkUrl = $data['webSdkUrl'] ?? '';
    $amount = $data['amount'] ?? '';
    $currency = $data['currency'] ?? 'USD';
    $country = $data['country'] ?? 'US';
    $sandbox = ($data['sandbox'] ?? false) === true;
    $error = $data['error'] ?? null;
@endphp

<div class="gateway-pane gateway-pane--applepay">
    <h2 class="gateway-pane__title">Pay with Apple Pay</h2>
    <p>Authorize payment for order <strong>{{ $order->order_number }}</strong> - <strong>{{ strtoupper($order->currency_code) }} {{ number_format((float) $order->total_display, 2) }}</strong>.</p>

    @if(! $configured)
        <p class="gateway-pane__hint gateway-pane__hint--warn">Apple Pay is not fully configured. Add your PayPal REST API credentials in Admin - Payments - Payment settings.</p>
    @elseif($error)
        <p class="gateway-pane__hint gateway-pane__hint--warn">{{ $error }}</p>
    @elseif($paypalOrderId === '' || $clientId === '' || $clientToken === '' || $webSdkUrl === '' || $amount === '')
        <p class="gateway-pane__hint gateway-pane__hint--warn">Unable to load Apple Pay checkout. Refresh this page or contact support.</p>
    @else
        <ul class="gateway-pane__steps">
            <li>Use the Apple Pay button below on a supported Apple device and browser.</li>
            <li>Authenticate with Touch ID, Face ID, or your device passcode.</li>
            <li>Stay on this page until we confirm your payment.</li>
        </ul>
        <div id="apple-pay-button-container" class="gateway-pane__apple-pay"></div>
        <p id="apple-pay-error" class="gateway-pane__hint gateway-pane__hint--warn" hidden></p>

        @if($sandbox)
            <p class="gateway-pane__hint">Sandbox mode - use a PayPal sandbox buyer and an Apple Pay sandbox wallet.</p>
        @endif

        @push('scripts')
        <script src="{{ $webSdkUrl }}" data-sdk-integration-source="paypal-web-sdk-v6-applepay"></script>
        <script src="https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js"></script>
        <script>
        (async function () {
            var paypalOrderId = @json($paypalOrderId);
            var clientId = @json($clientId);
            var clientToken = @json($clientToken);
            var amount = @json($amount);
            var currency = @json($currency);
            var country = @json($country);
            var confirmUrl = @json(route('shop.checkout.confirm', ['order_number' => $order->order_number]));
            var csrf = @json(csrf_token());
            var displayName = @json((string) config('app.name', 'Store'));
            var container = document.getElementById('apple-pay-button-container');
            var errorEl = document.getElementById('apple-pay-error');

            function showError(message) {
                errorEl.textContent = message;
                errorEl.hidden = false;
            }

            function postConfirm() {
                return fetch(confirmUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ paypal_order_id: paypalOrderId })
                }).then(function (response) {
                    return response.json().then(function (data) {
                        if (response.ok && data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }
                        throw new Error(data.message || 'Payment could not be confirmed.');
                    });
                });
            }

            if (typeof paypal === 'undefined' || typeof paypal.createInstance !== 'function' || typeof ApplePaySession === 'undefined') {
                showError('Apple Pay is not available on this browser or device. Please choose another payment method.');
                return;
            }
            if (typeof ApplePaySession.canMakePayments === 'function' && !ApplePaySession.canMakePayments()) {
                showError('Apple Pay is not set up on this device. Please choose another payment method.');
                return;
            }

            try {
                var sdk = await PayPalCheckout.createInstance({
                    webSdkUrl: @json($webSdkUrl),
                    clientToken: clientToken,
                    components: ['applepay-payments'],
                });
                var paymentMethods = await sdk.findEligibleMethods({ currencyCode: currency, amount: amount });
                if (!paymentMethods.isEligible('applepay')) {
                    console.warn('Apple Pay is not eligible for this PayPal merchant or buyer context.');
                    showError('Apple Pay is not available for this PayPal account, browser, or device.');
                    return;
                }
                var applepay = await sdk.createApplePayOneTimePaymentSession();
                var config = await applepay.config();

                var button = document.createElement('apple-pay-button');
                button.setAttribute('buttonstyle', 'black');
                button.setAttribute('type', 'buy');
                button.setAttribute('locale', 'en-US');
                container.appendChild(button);

                button.addEventListener('click', function () {
                    var session = new ApplePaySession(4, {
                        countryCode: config.countryCode || country,
                        currencyCode: currency,
                        merchantCapabilities: config.merchantCapabilities,
                        supportedNetworks: config.supportedNetworks,
                        requiredBillingContactFields: ['name', 'postalAddress'],
                        total: { label: displayName, amount: amount, type: 'final' }
                    });

                    session.onvalidatemerchant = function (event) {
                        applepay.validateMerchant({ validationUrl: event.validationURL, displayName: displayName })
                            .then(function (merchantSession) { session.completeMerchantValidation(merchantSession.merchantSession); })
                            .catch(function () {
                                session.abort();
                                showError('Apple Pay merchant validation failed. Please try another payment method.');
                            });
                    };

                    session.onpaymentauthorized = function (event) {
                        document.dispatchEvent(new CustomEvent('checkout:loading', {
                            detail: { message: 'Confirming your Apple Pay payment...' }
                        }));
                        applepay.confirmOrder({
                            orderId: paypalOrderId,
                            token: event.payment.token,
                            billingContact: event.payment.billingContact
                        }).then(function () {
                            session.completePayment(ApplePaySession.STATUS_SUCCESS);
                            return postConfirm();
                        }).catch(function (error) {
                            document.dispatchEvent(new CustomEvent('checkout:loading-end'));
                            session.completePayment(ApplePaySession.STATUS_FAILURE);
                            showError(PayPalCheckout.errorMessage(error, 'Apple Pay could not be completed.'));
                        });
                    };

                    session.begin();
                });
            } catch (error) {
                console.error('PayPal Apple Pay setup failed', error);
                showError(PayPalCheckout.errorMessage(error, 'PayPal Apple Pay configuration could not be loaded. Please choose another payment method.'));
            }
        })();
        </script>
        @endpush
    @endif
</div>
