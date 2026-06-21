@php
    $appliedCode = $appliedVoucher->code ?? old('voucher_code', '');
    $hasDiscount = ($discountUsd ?? 0) > 0;
@endphp
<section class="checkout-block checkout-voucher" aria-labelledby="checkout-voucher-title" data-checkout-voucher
         data-voucher-apply-url="{{ route('shop.checkout.voucher.apply') }}"
         data-voucher-remove-url="{{ route('shop.checkout.voucher.remove') }}">
    <h2 id="checkout-voucher-title" class="checkout-block__title">Voucher</h2>
    <div class="checkout-voucher__row">
        <div class="checkout-field checkout-field--floating checkout-voucher__field">
            <input type="text" id="voucher_code" name="voucher_code" autocomplete="off" maxlength="32"
                   value="{{ $appliedCode }}" placeholder=" " data-voucher-input
                   @if($hasDiscount) readonly @endif>
            <label for="voucher_code">Voucher code</label>
        </div>
        <button type="button" class="btn btn--ghost checkout-voucher__btn" data-voucher-apply @if($hasDiscount) hidden @endif>Apply</button>
        <button type="button" class="btn btn--ghost checkout-voucher__btn" data-voucher-remove @if(!$hasDiscount) hidden @endif>Remove</button>
    </div>
    <p class="checkout-voucher__msg@if($errors->has('voucher_code')) checkout-voucher__msg--err@elseif($hasDiscount) checkout-voucher__msg--ok@endif"
       data-voucher-msg @if(!$errors->has('voucher_code') && !$hasDiscount) hidden @endif role="status">
        @if($errors->has('voucher_code'))
            {{ $errors->first('voucher_code') }}
        @elseif($hasDiscount)
            {{ ($appliedVoucher->percent ?? 10) }}% off applied — you save {{ $currency->formatUsd((float) $discountUsd) }}.
        @endif
    </p>
</section>
