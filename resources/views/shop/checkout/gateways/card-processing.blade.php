@php
    $configured = ($data['configured'] ?? false) === true;
    $publishableKey = $data['publishableKey'] ?? '';
    $clientSecret = $data['clientSecret'] ?? '';
    $paymentIntentId = $data['paymentIntentId'] ?? '';
    $billingDetails = $data['billingDetails'] ?? [];
    $testMode = ($data['testMode'] ?? false) === true;
    $error = $data['error'] ?? null;
@endphp

<div class="gateway-pane gateway-pane--card">
    <h2 class="gateway-pane__title">Pay by card</h2>
    <p>Authorize payment for order <strong>{{ $order->order_number }}</strong> - <strong>{{ strtoupper($order->currency_code) }} {{ number_format((float) $order->total_display, 2) }}</strong>.</p>

    @if(! $configured)
        <p class="gateway-pane__hint gateway-pane__hint--warn">Card payments are not fully configured. Add your <strong>Stripe Publishable Key</strong> and <strong>Secret Key</strong> in Admin - Payments - Payment settings, then save.</p>
    @elseif($error)
        <p class="gateway-pane__hint gateway-pane__hint--warn">{{ $error }}</p>
    @elseif($publishableKey === '' || $clientSecret === '' || $paymentIntentId === '')
        <p class="gateway-pane__hint gateway-pane__hint--warn">Unable to load card checkout. Refresh this page or contact support.</p>
    @else
        <form id="stripe-card-payment-form" class="stripe-card-form" data-stripe-card-form>
            <label class="stripe-card-form__label" for="stripe-card-element">Card details</label>
            <div id="stripe-card-element" class="stripe-card-form__element"></div>
            <p id="stripe-card-error" class="stripe-card-form__message stripe-card-form__message--err" role="alert" hidden></p>
            <button id="stripe-card-submit" class="btn btn--primary stripe-card-form__submit" type="submit">
                Pay {{ strtoupper($order->currency_code) }} {{ number_format((float) $order->total_display, 2) }}
            </button>
        </form>

        @if($testMode)
            <p class="gateway-pane__hint">Test mode - use Stripe test cards while verifying checkout.</p>
        @endif

        @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
        (function () {
            var publishableKey = @json($publishableKey);
            var clientSecret = @json($clientSecret);
            var paymentIntentId = @json($paymentIntentId);
            var billingDetails = @json($billingDetails);
            var confirmUrl = @json(route('shop.checkout.confirm', ['order_number' => $order->order_number]));
            var csrf = @json(csrf_token());

            var form = document.getElementById('stripe-card-payment-form');
            var errorEl = document.getElementById('stripe-card-error');
            var submitBtn = document.getElementById('stripe-card-submit');

            function setError(message) {
                if (!errorEl) {
                    return;
                }
                errorEl.textContent = message || '';
                errorEl.hidden = !message;
            }

            function setBusy(isBusy) {
                if (!submitBtn) {
                    return;
                }
                submitBtn.disabled = isBusy;
                submitBtn.textContent = isBusy ? 'Processing...' : @json('Pay '.strtoupper($order->currency_code).' '.number_format((float) $order->total_display, 2));
            }

            function postConfirm(intentId) {
                return fetch(confirmUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ payment_intent_id: intentId || paymentIntentId }),
                }).then(function (res) {
                    return res.json().then(function (data) {
                        if (res.ok && data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }
                        throw new Error((data && data.message) ? data.message : 'Payment could not be confirmed.');
                    });
                });
            }

            if (!form || typeof Stripe === 'undefined') {
                setError('Stripe could not be loaded. Check your connection or ad blocker.');
                return;
            }

            var stripe = Stripe(publishableKey);
            var elements = stripe.elements();
            var card = elements.create('card', {
                hidePostalCode: true,
                style: {
                    base: {
                        color: '#231f20',
                        fontFamily: 'Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                        fontSize: '16px',
                        '::placeholder': {
                            color: '#77716a',
                        },
                    },
                    invalid: {
                        color: '#8b1e16',
                    },
                },
            });

            card.mount('#stripe-card-element');
            card.on('change', function (event) {
                setError(event.error ? event.error.message : '');
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                setError('');
                setBusy(true);

                stripe.confirmCardPayment(clientSecret, {
                    payment_method: {
                        card: card,
                        billing_details: billingDetails || {},
                    },
                }).then(function (result) {
                    if (result.error) {
                        setError(result.error.message || 'Card payment could not be completed.');
                        setBusy(false);
                        return;
                    }

                    var intent = result.paymentIntent;
                    if (!intent || intent.status !== 'succeeded') {
                        setError('Payment has not completed yet. Please try again.');
                        setBusy(false);
                        return;
                    }

                    postConfirm(intent.id || paymentIntentId).catch(function (err) {
                        setError(err.message || 'Payment could not be confirmed.');
                        setBusy(false);
                    });
                }).catch(function (err) {
                    setError(err.message || 'Card payment could not be completed.');
                    setBusy(false);
                });
            });
        })();
        </script>
        @endpush
    @endif
</div>
