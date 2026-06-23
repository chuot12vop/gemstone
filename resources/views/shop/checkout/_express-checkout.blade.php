@php
    $express = $expressCheckout ?? ['show' => false, 'slots' => [], 'paypal' => null];
    $paypal = $express['paypal'] ?? null;
    $slots = $express['slots'] ?? [];
    $slotCount = count($slots);

    $initialSlotCount = $slotCount - (in_array('apple_pay', $slots, true) ? 1 : 0);
@endphp
@if(($express['show'] ?? false) === true && $paypal && $slotCount > 0)
<section class="checkout-express" aria-labelledby="checkout-express-title" data-checkout-express

    @if($initialSlotCount === 0) hidden @endif
    data-paypal-init-url="{{ $paypal['initUrl'] }}"
    data-paypal-web-sdk="{{ $paypal['webSdkUrl'] }}"

    data-paypal-client-id="{{ $paypal['clientId'] }}"

    data-apple-pay-amount="{{ $paypal['amount'] }}"

    data-apple-pay-currency="{{ $paypal['currency'] }}"

    data-apple-pay-country="{{ $paypal['country'] }}"
    data-paypal-sandbox="{{ ($paypal['sandbox'] ?? false) ? '1' : '0' }}">
    <h2 id="checkout-express-title" class="checkout-express__title">Express checkout</h2>
    <div class="checkout-express__buttons checkout-express__buttons--{{ $initialSlotCount }}" data-express-buttons>
        @if(in_array('paypal', $slots, true))
            <div class="checkout-express__slot checkout-express__slot--paypal">
                <div id="express-paypal-button" class="checkout-express__paypal-mount" aria-label="PayPal"></div>
            </div>
        @endif
        @if(in_array('google_pay', $slots, true))
            <div class="checkout-express__slot checkout-express__slot--gpay">
                <div id="express-googlepay-button" class="checkout-express__gpay-mount" aria-label="Google Pay"></div>
            </div>
        @endif

        @if(in_array('apple_pay', $slots, true))

            <div class="checkout-express__slot checkout-express__slot--applepay" hidden>

                <div id="express-applepay-button" class="checkout-express__applepay-mount" aria-label="Apple Pay"></div>

            </div>

        @endif
    </div>
    <div class="checkout-express__divider" role="separator" aria-label="or">
        <span class="checkout-express__divider-line"></span>
        <span class="checkout-express__divider-text">OR</span>
        <span class="checkout-express__divider-line"></span>
    </div>
</section>
@endif
