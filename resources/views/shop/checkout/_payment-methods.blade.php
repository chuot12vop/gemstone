@php
    $methodCollection = collect($methods);
    $cardMethod = $methodCollection->first(fn ($method) => $method->code() === 'card');
    $methodOrder = ['paypal' => 0, 'apple_pay' => 1, 'venmo' => 2, 'cashapp' => 3, 'zelle' => 4];
    $moreMethods = $methodCollection
        ->reject(fn ($method) => $method->code() === 'card')
        ->sortBy(fn ($method) => $methodOrder[$method->code()] ?? 99)
        ->values();
    $selectedCode = (string) ($selected ?? ($cardMethod ? 'card' : optional($moreMethods->first())->code()));
    $cardSelected = $cardMethod && $selectedCode === 'card';
    $moreSelected = $moreMethods->contains(fn ($method) => $method->code() === $selectedCode);
    $moreExpanded = $moreMethods->isNotEmpty();
    $morePaymentLogos = collect($paymentLogos ?? [])
        ->filter(fn ($logo) => !empty($logo['src']))
        ->values();
    $inlineWalletCheckout = $expressCheckout['paypal'] ?? null;
    $rasterBase = 'assets/img/payments/raster/';
    $rasterLogos = [
        'visa' => ['file' => 'visa.png', 'label' => 'Visa'],
        'mastercard' => ['file' => 'mastercard.png', 'label' => 'Mastercard'],
        'amex' => ['file' => 'amex.png', 'label' => 'American Express'],
        'jcb' => ['file' => 'jcb.png', 'label' => 'JCB'],
        'diners' => ['file' => 'diners-club.png', 'label' => 'Diners Club'],
        'discover' => ['file' => 'discover.png', 'label' => 'Discover'],
    ];
@endphp

<section class="checkout-block checkout-block--missoma-payment" aria-labelledby="checkout-payment-title">
    <h2 id="checkout-payment-title" class="checkout-block__title">Payment</h2>
    <p class="checkout-payment-secure">All transactions are secure and encrypted.</p>

    <fieldset class="payment-methods payment-methods--missoma">
        <legend class="sr-only">Payment method</legend>

        @if($cardMethod)
            <div class="payment-method-item payment-method-item--paypal-card {{ $cardSelected ? 'is-selected' : '' }}" data-payment-method-item>
                <label class="payment-card payment-card--paypal-card"
                       data-card-payment-toggle
                       aria-expanded="{{ $cardSelected ? 'true' : 'false' }}">
                    <input type="radio"
                           name="payment_method"
                           value="{{ $cardMethod->code() }}"
                           @checked($cardSelected)
                           required
                           data-payment-method-radio>
                    <span class="payment-card__check" aria-hidden="true"></span>
                    <span class="payment-card__body">
                        <span class="payment-card__label">{{ $cardMethod->label() }}</span>
                    </span>
                    <span class="payment-card__brand-icons" aria-label="Accepted cards">
                        <img src="{{ asset($rasterBase.$rasterLogos['visa']['file']) }}" alt="{{ $rasterLogos['visa']['label'] }}" width="38" height="24" loading="lazy" decoding="async">
                        <img src="{{ asset($rasterBase.$rasterLogos['mastercard']['file']) }}" alt="{{ $rasterLogos['mastercard']['label'] }}" width="38" height="24" loading="lazy" decoding="async">
                        <img src="{{ asset($rasterBase.$rasterLogos['amex']['file']) }}" alt="{{ $rasterLogos['amex']['label'] }}" width="38" height="24" loading="lazy" decoding="async">
                        <span class="payment-card__more-wrap">
                            <button class="payment-card__more" type="button" aria-label="Show more accepted cards" aria-describedby="payment-card-more-popover">+3</button>
                            <span id="payment-card-more-popover" class="payment-card__more-popover" role="tooltip">
                                <img src="{{ asset($rasterBase.$rasterLogos['jcb']['file']) }}" alt="{{ $rasterLogos['jcb']['label'] }}" width="38" height="24" loading="lazy" decoding="async">
                                <img src="{{ asset($rasterBase.$rasterLogos['diners']['file']) }}" alt="{{ $rasterLogos['diners']['label'] }}" width="38" height="24" loading="lazy" decoding="async">
                                <img src="{{ asset($rasterBase.$rasterLogos['discover']['file']) }}" alt="{{ $rasterLogos['discover']['label'] }}" width="38" height="24" loading="lazy" decoding="async">
                            </span>
                        </span>
                    </span>
                </label>
                <div class="payment-method-item__panel checkout-card-panel"
                     data-payment-method-panel
                     @if(! $cardSelected) hidden @endif>
                    @if(!empty($cardCheckout))
                        <div class="checkout-card-fields"
                             data-checkout-card-fields
                             data-card-place-url="{{ $cardCheckout['placeUrl'] }}"
                             data-paypal-web-sdk="{{ $cardCheckout['webSdkUrl'] }}"
                             data-paypal-client-id="{{ $cardCheckout['clientId'] }}"
                             data-paypal-client-token="{{ $cardCheckout['clientToken'] }}"
                             data-paypal-currency="{{ $cardCheckout['currency'] }}"
                             data-paypal-sandbox="{{ ($cardCheckout['sandbox'] ?? false) ? '1' : '0' }}">
                            <div id="checkout-card-number" class="checkout-card-fields__field checkout-card-fields__field--number"></div>
                            <div class="checkout-card-fields__row">
                                <div id="checkout-card-expiry" class="checkout-card-fields__field"></div>
                                <div id="checkout-card-cvv" class="checkout-card-fields__field"></div>
                            </div>
                            <input id="checkout-card-name"
                                   class="checkout-card-fields__field checkout-card-fields__input"
                                   type="text"
                                   name="cardholder_name"
                                   autocomplete="cc-name"
                                   placeholder="Name on card">
                            <p class="checkout-card-fields__message checkout-card-fields__message--error"
                               data-checkout-card-error role="alert" hidden></p>
                        </div>
                    @else
                        <p class="checkout-card-fields__message">
                            Secure card fields will open after you continue.
                        </p>
                    @endif

                    @if($cardMethod->customerFieldsView())
                        @include($cardMethod->customerFieldsView(), ['gateway' => $cardMethod])
                    @endif
                </div>
            </div>
        @endif

        @if($moreMethods->isNotEmpty())
            <div class="payment-method-item payment-method-item--more {{ $moreSelected ? 'is-selected' : '' }} {{ $moreExpanded ? 'is-open' : '' }}" data-payment-more-item>
                <label class="payment-more-toggle"
                        data-payment-more-toggle
                        aria-expanded="{{ $moreExpanded ? 'true' : 'false' }}"
                        aria-controls="payment-more-panel">
                    <input type="radio"
                           data-payment-more-radio
                           @checked($moreExpanded)
                           aria-label="More Payment Options">
                    <span class="payment-card__body">
                        <span class="payment-card__label">More Payment Options</span>
                    </span>
                    @if($morePaymentLogos->isNotEmpty())
                        <span class="payment-more-toggle__logos" aria-label="Accepted payment methods">
                            @foreach($morePaymentLogos as $logo)
                                <img class="payment-more-toggle__logo"
                                     src="{{ $logo['src'] }}"
                                     alt="{{ $logo['label'] ?? 'Payment method' }}"
                                     width="38"
                                     height="24"
                                     loading="lazy"
                                     decoding="async">
                            @endforeach
                        </span>
                    @else
                        <span class="payment-more-toggle__dots" aria-hidden="true">...</span>
                    @endif
                </label>
                <div id="payment-more-panel" class="payment-more-panel" data-payment-more-panel @if(! $moreExpanded) hidden @endif>
                    @foreach($moreMethods as $method)
                        @php($adminLogo = \App\Support\PaymentMethodLogos::forGateway($method->code(), $method->label()))
                        @php($inlineWallet = in_array($method->code(), ['paypal', 'apple_pay'], true) && !empty($inlineWalletCheckout))
                        <div class="payment-more-option-wrap" data-payment-method-item>
                            <label class="payment-more-option">
                                <input type="radio"
                                       name="payment_method"
                                       value="{{ $method->code() }}"
                                       @checked($selectedCode === $method->code())
                                       required
                                       data-payment-method-radio>
                                <span class="payment-card__check" aria-hidden="true"></span>
                                <span class="payment-more-option__label">{{ $method->label() }}</span>
                                @if($adminLogo)
                                    <img class="payment-more-option__logo"
                                         src="{{ $adminLogo['src'] }}"
                                         alt="{{ $adminLogo['label'] }}"
                                         width="70"
                                         height="34"
                                         loading="lazy"
                                         decoding="async">
                                @else
                                    <span class="payment-more-option__text-logo">{{ $method->label() }}</span>
                                @endif
                            </label>
                            @if($inlineWallet)
                                <div class="payment-method-item__panel checkout-wallet-panel"
                                     data-payment-method-panel
                                     data-checkout-wallet-panel="{{ $method->code() }}"
                                     data-wallet-place-url="{{ route('shop.checkout.place') }}"
                                     data-paypal-web-sdk="{{ $inlineWalletCheckout['webSdkUrl'] }}"
                                     data-paypal-client-id="{{ $inlineWalletCheckout['clientId'] }}"
                                     data-paypal-client-token="{{ $inlineWalletCheckout['clientToken'] }}"
                                     data-apple-pay-amount="{{ $inlineWalletCheckout['amount'] }}"
                                     data-apple-pay-currency="{{ $inlineWalletCheckout['currency'] }}"
                                     data-apple-pay-country="{{ $inlineWalletCheckout['country'] }}"
                                     data-paypal-sandbox="{{ ($inlineWalletCheckout['sandbox'] ?? false) ? '1' : '0' }}"
                                    data-wallet-preload="1">
                                    @if($method->code() === 'paypal')
                                        <div id="checkout-paypal-button" class="checkout-wallet-panel__mount checkout-wallet-panel__mount--paypal" aria-label="PayPal"></div>
                                    @elseif($method->code() === 'apple_pay')
                                        <div id="checkout-applepay-button" class="checkout-wallet-panel__mount checkout-wallet-panel__mount--applepay" aria-label="Apple Pay"></div>
                                        <p class="checkout-wallet-panel__message checkout-wallet-panel__message--error" data-checkout-wallet-error hidden></p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </fieldset>

</section>
