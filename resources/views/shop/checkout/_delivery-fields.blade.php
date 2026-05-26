@php
    use App\Support\CheckoutCountries;

    $countries = CheckoutCountries::options();
    $defaults = $deliveryDefaults ?? [];
    $oldCountry = old('shipping_country', $defaults['country'] ?? CheckoutCountries::defaultCode());
@endphp

<section class="checkout-block" aria-labelledby="checkout-delivery-title">
    <h2 id="checkout-delivery-title" class="checkout-block__title">Delivery</h2>

    <div class="checkout-field checkout-field--floating full">
        <select id="shipping_country" name="shipping_country" required autocomplete="country">
            @foreach($countries as $code => $label)
                <option value="{{ $code }}" @selected($oldCountry === $code)>{{ $label }}</option>
            @endforeach
        </select>
        <label for="shipping_country">Country/Region</label>
    </div>

    <div class="checkout-field-row">
        <div class="checkout-field checkout-field--floating">
            <input type="text" id="shipping_first_name" name="shipping_first_name" required autocomplete="given-name"
                   value="{{ old('shipping_first_name', $defaults['first_name'] ?? '') }}" placeholder=" ">
            <label for="shipping_first_name">First name</label>
        </div>
        <div class="checkout-field checkout-field--floating">
            <input type="text" id="shipping_last_name" name="shipping_last_name" required autocomplete="family-name"
                   value="{{ old('shipping_last_name', $defaults['last_name'] ?? '') }}" placeholder=" ">
            <label for="shipping_last_name">Last name</label>
        </div>
    </div>

    <div class="checkout-field checkout-field--floating full">
        <input type="text" id="shipping_company" name="shipping_company" autocomplete="organization"
               value="{{ old('shipping_company', $defaults['company'] ?? '') }}" placeholder=" ">
        <label for="shipping_company">Company (optional)</label>
    </div>

    <div class="checkout-field checkout-field--floating checkout-field--with-icon full">
        <input type="text" id="shipping_address_line1" name="shipping_address_line1" required autocomplete="address-line1"
               value="{{ old('shipping_address_line1', $defaults['address_line1'] ?? '') }}" placeholder=" ">
        <label for="shipping_address_line1">Address</label>
        <span class="checkout-field__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>
            </svg>
        </span>
    </div>

    <div class="checkout-field checkout-field--floating full">
        <input type="text" id="shipping_address_line2" name="shipping_address_line2" autocomplete="address-line2"
               value="{{ old('shipping_address_line2', $defaults['address_line2'] ?? '') }}" placeholder=" ">
        <label for="shipping_address_line2">Apartment, suite, etc. (optional)</label>
    </div>

    <div class="checkout-field-row">
        <div class="checkout-field checkout-field--floating">
            <input type="text" id="shipping_city" name="shipping_city" required autocomplete="address-level2"
                   value="{{ old('shipping_city', $defaults['city'] ?? '') }}" placeholder=" ">
            <label for="shipping_city">City</label>
        </div>
        <div class="checkout-field checkout-field--floating">
            <input type="text" id="shipping_postcode" name="shipping_postcode" required autocomplete="postal-code"
                   value="{{ old('shipping_postcode', $defaults['postcode'] ?? '') }}" placeholder=" ">
            <label for="shipping_postcode">Postcode</label>
        </div>
    </div>

    <div class="checkout-field checkout-field--floating checkout-field--with-icon full">
        <input type="tel" id="shipping_phone" name="shipping_phone" required autocomplete="tel"
               value="{{ old('shipping_phone', $defaults['phone'] ?? '') }}" placeholder=" ">
        <label for="shipping_phone">Phone</label>
        <span class="checkout-field__icon checkout-field__icon--help" title="Used for delivery updates" aria-hidden="true">?</span>
    </div>

    <label class="checkout-checkbox full">
        <input type="checkbox" name="marketing_sms_opt_in" value="1" @checked(old('marketing_sms_opt_in'))>
        <span>Text me with news and offers</span>
    </label>
</section>

<section class="checkout-block" aria-labelledby="checkout-shipping-method-title">
    <h2 id="checkout-shipping-method-title" class="checkout-block__title">Shipping method</h2>
    <p class="checkout-shipping-placeholder" data-shipping-placeholder>
        Enter your shipping address to view available shipping methods.
    </p>
    <div class="checkout-shipping-options" data-shipping-options hidden>
        <label class="checkout-shipping-option">
            <input type="radio" name="shipping_method" value="standard" checked>
            <span class="checkout-shipping-option__body">
                <span class="checkout-shipping-option__label">Standard Shipping</span>
                <span class="checkout-shipping-option__meta">$5.99 · FREE on orders over $100 (UPS/USPS)</span>
            </span>
        </label>
    </div>
</section>
