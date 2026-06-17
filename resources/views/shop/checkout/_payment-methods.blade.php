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
    $moreExpanded = $moreSelected;
    $rasterBase = 'assets/img/payments/raster/';
    $rasterLogos = [
        'visa' => ['file' => 'visa.png', 'label' => 'Visa'],
        'mastercard' => ['file' => 'mastercard.png', 'label' => 'Mastercard'],
        'amex' => ['file' => 'amex.png', 'label' => 'American Express'],
        'jcb' => ['file' => 'jcb.png', 'label' => 'JCB'],
        'diners' => ['file' => 'diners-club.png', 'label' => 'Diners Club'],
        'discover' => ['file' => 'discover.png', 'label' => 'Discover'],
    ];
    $gatewayRasterLogos = [
        'paypal' => ['file' => 'paypal.png', 'label' => 'PayPal'],
        'apple_pay' => ['file' => 'apple-pay.png', 'label' => 'Apple Pay'],
        'venmo' => ['file' => 'venmo.png', 'label' => 'Venmo'],
        'cashapp' => ['file' => 'cashapp.png', 'label' => 'Cash App'],
        'zelle' => ['file' => 'zelle.png', 'label' => 'Zelle'],
    ];
@endphp

<section class="checkout-block checkout-block--missoma-payment" aria-labelledby="checkout-payment-title">
    <h2 id="checkout-payment-title" class="checkout-block__title">Payment</h2>
    <p class="checkout-payment-secure">All transactions are secure and encrypted.</p>

    <fieldset class="payment-methods payment-methods--missoma">
        <legend class="sr-only">Payment method</legend>

        @if($cardMethod)
            <div class="payment-method-item payment-method-item--stripe-card {{ $cardSelected ? 'is-selected' : '' }}" data-payment-method-item>
                <label class="payment-card payment-card--stripe-card">
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
            </div>
        @endif

        @if($moreMethods->isNotEmpty())
            <div class="payment-method-item payment-method-item--more {{ $moreSelected ? 'is-selected' : '' }} {{ $moreExpanded ? 'is-open' : '' }}" data-payment-more-item>
                <button class="payment-more-toggle"
                        type="button"
                        data-payment-more-toggle
                        aria-expanded="{{ $moreExpanded ? 'true' : 'false' }}"
                        aria-controls="payment-more-panel">
                    <span class="payment-card__check" aria-hidden="true"></span>
                    <span class="payment-card__body">
                        <span class="payment-card__label">More Payment Options</span>
                    </span>
                    <span class="payment-more-toggle__dots" aria-hidden="true">...</span>
                </button>
                <div id="payment-more-panel" class="payment-more-panel" data-payment-more-panel @if(! $moreExpanded) hidden @endif>
                    @foreach($moreMethods as $method)
                        @php($adminLogo = \App\Support\PaymentMethodLogos::forGateway($method->code(), $method->label()))
                        @php($fallbackLogo = $gatewayRasterLogos[$method->code()] ?? null)
                        <label class="payment-more-option" data-payment-method-item>
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
                            @elseif($fallbackLogo)
                                <img class="payment-more-option__logo"
                                     src="{{ asset($rasterBase.$fallbackLogo['file']) }}"
                                     alt="{{ $fallbackLogo['label'] }}"
                                     width="70"
                                     height="34"
                                     loading="lazy"
                                     decoding="async">
                            @else
                                <span class="payment-more-option__text-logo">{{ $method->label() }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    </fieldset>

    @if($cardMethod && $cardMethod->customerFieldsView())
        @include($cardMethod->customerFieldsView(), ['gateway' => $cardMethod])
    @endif
</section>
