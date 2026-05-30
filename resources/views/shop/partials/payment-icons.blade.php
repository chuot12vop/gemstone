@if(!empty($paymentLogos))
<div class="payment-icons" aria-label="Accepted payment methods">
    @foreach($paymentLogos as $logo)
        <span class="payment-icons__item" title="{{ $logo['label'] }}">
            <img class="payment-icons__img"
                 src="{{ $logo['src'] }}"
                 alt="{{ $logo['label'] }}"
                 width="48"
                 height="30"
                 loading="lazy"
                 decoding="async">
        </span>
    @endforeach
</div>
@endif
