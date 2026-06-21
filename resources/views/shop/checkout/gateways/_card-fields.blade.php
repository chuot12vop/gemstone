@php
    $billingSame = old('card_billing_same_as_shipping', '1') !== '0';
    $countries = \App\Support\CheckoutCountries::options();
@endphp

<section class="card-billing" data-card-checkout-fields aria-labelledby="card-billing-title">
    <h3 id="card-billing-title" class="sr-only">Billing address</h3>

    <label class="card-billing__same">
        <input type="checkbox" value="1" @checked($billingSame) data-card-billing-same>
        <span>Use shipping address as billing address</span>
    </label>

    <input type="hidden"
           name="card_billing_same_as_shipping"
           value="{{ $billingSame ? '1' : '0' }}"
           data-card-billing-value>

    <div class="card-billing__fields" data-card-billing-fields @if($billingSame) hidden @endif>
        <div class="checkout-field-row">
            <div class="checkout-field checkout-field--floating">
                <input type="text" id="card_billing_first_name" name="card_billing_first_name" value="{{ old('card_billing_first_name') }}" placeholder=" ">
                <label for="card_billing_first_name">First name</label>
            </div>
            <div class="checkout-field checkout-field--floating">
                <input type="text" id="card_billing_last_name" name="card_billing_last_name" value="{{ old('card_billing_last_name') }}" placeholder=" ">
                <label for="card_billing_last_name">Last name</label>
            </div>
        </div>
        <div class="checkout-field checkout-field--floating full">
            <input type="text" id="card_billing_address_line1" name="card_billing_address_line1" value="{{ old('card_billing_address_line1') }}" placeholder=" ">
            <label for="card_billing_address_line1">Address</label>
        </div>
        <div class="checkout-field checkout-field--floating full">
            <input type="text" id="card_billing_address_line2" name="card_billing_address_line2" value="{{ old('card_billing_address_line2') }}" placeholder=" ">
            <label for="card_billing_address_line2">Apartment, suite, etc.</label>
        </div>
        <div class="checkout-field-row">
            <div class="checkout-field checkout-field--floating">
                <input type="text" id="card_billing_city" name="card_billing_city" value="{{ old('card_billing_city') }}" placeholder=" ">
                <label for="card_billing_city">City</label>
            </div>
            <div class="checkout-field checkout-field--floating">
                <input type="text" id="card_billing_postcode" name="card_billing_postcode" value="{{ old('card_billing_postcode') }}" placeholder=" ">
                <label for="card_billing_postcode">Postcode</label>
            </div>
        </div>
        <div class="checkout-field checkout-field--floating full">
            <select id="card_billing_country" name="card_billing_country">
                <option value="" @selected(old('card_billing_country') === null || old('card_billing_country') === '')></option>
                @foreach($countries as $code => $label)
                    <option value="{{ $code }}" @selected(old('card_billing_country') === $code)>{{ $label }}</option>
                @endforeach
            </select>
            <label for="card_billing_country">Country/Region</label>
        </div>
    </div>
</section>
