@php
    $configured = ($data['configured'] ?? false) === true;
    $paypalOrderId = $data['paypalOrderId'] ?? '';
    $clientId = $data['clientId'] ?? '';
    $clientToken = $data['clientToken'] ?? '';
    $webSdkUrl = $data['webSdkUrl'] ?? '';
    $currency = $data['currency'] ?? strtoupper((string) $order->currency_code);
    $billingDetails = $data['billingDetails'] ?? [];
    $sandbox = ($data['sandbox'] ?? false) === true;
    $error = $data['error'] ?? null;
@endphp

<div class="gateway-pane gateway-pane--card">
    <h2 class="gateway-pane__title">Pay by card</h2>
    <p>Authorize payment for order <strong>{{ $order->order_number }}</strong> - <strong>{{ strtoupper($order->currency_code) }} {{ number_format((float) $order->total_display, 2) }}</strong>.</p>

    @if(! $configured)
        <p class="gateway-pane__hint gateway-pane__hint--warn">Card payments are not fully configured. Add your PayPal REST API credentials in Admin - Payments - Payment settings.</p>
    @elseif($error)
        <p class="gateway-pane__hint gateway-pane__hint--warn">{{ $error }}</p>
    @elseif($paypalOrderId === '' || $clientId === '' || $clientToken === '' || $webSdkUrl === '')
        <p class="gateway-pane__hint gateway-pane__hint--warn">Unable to load secure card checkout. Refresh this page or contact support.</p>
    @else
        <form id="paypal-card-payment-form" class="paypal-card-form" data-paypal-card-form>
            <label class="paypal-card-form__label" for="paypal-card-name">Name on card</label>
            <div id="paypal-card-name" class="paypal-card-form__element"></div>
            <label class="paypal-card-form__label" for="paypal-card-number">Card number</label>
            <div id="paypal-card-number" class="paypal-card-form__element"></div>
            <label class="paypal-card-form__label" for="paypal-card-expiry">Expiry</label>
            <div id="paypal-card-expiry" class="paypal-card-form__element"></div>
            <label class="paypal-card-form__label" for="paypal-card-cvv">Security code</label>
            <div id="paypal-card-cvv" class="paypal-card-form__element"></div>
            <p id="paypal-card-error" class="paypal-card-form__message paypal-card-form__message--err" role="alert" hidden></p>
            <button id="paypal-card-submit" class="btn btn--primary paypal-card-form__submit" type="submit">
                Pay {{ strtoupper($order->currency_code) }} {{ number_format((float) $order->total_display, 2) }}
            </button>
        </form>

        @if($sandbox)
            <p class="gateway-pane__hint">Sandbox mode - use a PayPal sandbox test card.</p>
        @endif

        @push('scripts')
        <script src="{{ $webSdkUrl }}" data-sdk-integration-source="paypal-web-sdk-v6-card-fields"></script>
        <script>
        (async function () {
            var paypalOrderId = @json($paypalOrderId);
            var clientId = @json($clientId);
            var clientToken = @json($clientToken);
            var currency = @json($currency);
            var billingDetails = @json($billingDetails);
            var confirmUrl = @json(route('shop.checkout.confirm', ['order_number' => $order->order_number]));
            var csrf = @json(csrf_token());
            var form = document.getElementById('paypal-card-payment-form');
            var errorEl = document.getElementById('paypal-card-error');
            var submitBtn = document.getElementById('paypal-card-submit');
            var buttonText = @json('Pay '.strtoupper($order->currency_code).' '.number_format((float) $order->total_display, 2));

            function setError(message) {
                errorEl.textContent = message || '';
                errorEl.hidden = !message;
            }

            function paypalErrorMessage(error, fallback) {
                var messages = [];
                var visited = new Set();

                function add(value) {
                    var message = String(value || '').trim();
                    if (message && messages.indexOf(message) === -1) {
                        messages.push(message);
                    }
                }

                function inspect(value, depth) {
                    if (value === null || value === undefined || depth > 4) return;
                    if (typeof value === 'string') {
                        var text = value.trim();
                        if (!text) return;
                        if (text[0] === '{' || text[0] === '[') {
                            try {
                                inspect(JSON.parse(text), depth + 1);
                                return;
                            } catch (parseError) {}
                        }
                        add(text);
                        return;
                    }
                    if (typeof value !== 'object' || visited.has(value)) return;

                    visited.add(value);
                    ['issue', 'description', 'message'].forEach(function (key) {
                        if (typeof value[key] === 'string') add(value[key]);
                    });
                    ['details', 'data', 'body', 'cause', 'error', 'errors', 'response'].forEach(function (key) {
                        if (value[key] !== undefined) inspect(value[key], depth + 1);
                    });
                    if (Array.isArray(value)) {
                        value.forEach(function (item) { inspect(item, depth + 1); });
                    }
                }

                inspect(error, 0);
                if (!messages.length) return fallback;

                var code = String(error && (error.code || error.name) || '').trim();
                return 'PayPal' + (code ? ' (' + code + ')' : '') + ': ' + messages.join(' — ');
            }

            function setBusy(busy) {
                submitBtn.disabled = busy;
                submitBtn.textContent = busy ? 'Processing...' : buttonText;
                document.dispatchEvent(new CustomEvent(busy ? 'checkout:loading' : 'checkout:loading-end', {
                    detail: busy ? { message: 'Processing your card payment...' } : null
                }));
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

            if (!form || typeof paypal === 'undefined' || typeof paypal.createInstance !== 'function') {
                setError('PayPal secure card fields could not be loaded. Check your connection or account eligibility.');
                return;
            }

            var cardSession;
            try {
                var sdk = await paypal.createInstance({
                    clientToken: clientToken,
                    components: ['card-fields'],
                    pageType: 'checkout'
                });
                var paymentMethods = await sdk.findEligibleMethods({ currencyCode: currency });
                if (!paymentMethods.isEligible('advanced_cards')) {
                    setError('PayPal: Advanced Card Payments is not eligible for this merchant, buyer, currency, or browser context.');
                    submitBtn.hidden = true;
                    return;
                }
                cardSession = sdk.createCardFieldsOneTimePaymentSession();
                document.getElementById('paypal-card-name').appendChild(cardSession.createCardFieldsComponent({ type: 'name', placeholder: 'Name on card' }));
                document.getElementById('paypal-card-number').appendChild(cardSession.createCardFieldsComponent({ type: 'number', placeholder: 'Card number' }));
                document.getElementById('paypal-card-expiry').appendChild(cardSession.createCardFieldsComponent({ type: 'expiry', placeholder: 'MM / YY' }));
                document.getElementById('paypal-card-cvv').appendChild(cardSession.createCardFieldsComponent({ type: 'cvv', placeholder: 'CVV' }));
            } catch (error) {
                console.error('PayPal card fields initialization failed', error);
                setError(paypalErrorMessage(error, 'PayPal secure card fields could not be loaded. Check your connection or account eligibility.'));
                submitBtn.hidden = true;
                return;
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                setError('');
                setBusy(true);
                cardSession.submit(paypalOrderId, { billingAddress: billingDetails.address || {} })
                    .then(function (result) {
                        if (result && result.state === 'succeeded') {
                            return postConfirm();
                        }
                        if (result && result.state === 'canceled') {
                            throw new Error('Authentication was cancelled. Please try again.');
                        }
                        throw new Error((result && result.data && result.data.message) || 'Card payment could not be completed.');
                    })
                    .catch(function (error) {
                        setError(paypalErrorMessage(error, 'Check your card details and try again.'));
                        setBusy(false);
                    });
            });
        })();
        </script>
        @endpush
    @endif
</div>
