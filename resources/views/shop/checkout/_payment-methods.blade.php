<section class="checkout-block" aria-labelledby="checkout-payment-title">
    <h2 id="checkout-payment-title" class="checkout-block__title">Payment</h2>
    @if(!empty($paymentLogos))
        <p class="checkout-payment-accepted">We accept</p>
        <div class="checkout-payment-accepted__icons">
            @include('shop.partials.payment-icons')
        </div>
    @endif
    <fieldset class="payment-methods">
        <legend class="sr-only">Payment method</legend>
        @foreach($methods as $method)
            <label class="payment-card">
                <input type="radio" name="payment_method" value="{{ $method->code() }}"
                       @checked(($selected ?? '') === $method->code() || (empty($selected) && $loop->first))
                       required
                       data-payment-method-radio>
                <span class="payment-card__icon">{!! $method->iconHtml() ?: '<span class="payment-card__icon-placeholder">'.strtoupper(substr($method->label(),0,1)).'</span>' !!}</span>
                <span class="payment-card__body">
                    <span class="payment-card__label">{{ $method->label() }}</span>
                    @if($method->description() !== '')
                        <span class="payment-card__desc">{{ $method->description() }}</span>
                    @endif
                </span>
                <span class="payment-card__check" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7"/></svg>
                </span>
            </label>
        @endforeach
    </fieldset>
</section>
