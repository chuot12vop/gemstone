@php
    $paymentLogos = [
        ['file' => 'visa.svg', 'label' => 'Visa'],
        ['file' => 'mastercard.svg', 'label' => 'Mastercard'],
        ['file' => 'amex.svg', 'label' => 'American Express'],
        ['file' => 'paypal.svg', 'label' => 'PayPal'],
        ['file' => 'apple-pay.svg', 'label' => 'Apple Pay'],
    ];
@endphp
<div class="payment-icons" aria-label="Accepted payment methods">
    @foreach($paymentLogos as $logo)
        <span class="payment-icons__item" title="{{ $logo['label'] }}">
            <img class="payment-icons__img"
                 src="{{ asset('assets/img/payments/'.$logo['file']) }}"
                 alt="{{ $logo['label'] }}"
                 width="48"
                 height="30"
                 loading="lazy"
                 decoding="async">
        </span>
    @endforeach
</div>
