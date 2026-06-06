@php
    $shippingProgress = \App\Support\CheckoutShipping::progress($subtotalUsd, $lines);
@endphp
@if($shippingProgress['qualified'])
    <p class="pc-drawer__shipping-msg">Hooray! Your order will be delivered for FREE</p>
@else
    <p class="pc-drawer__shipping-msg">Spend {{ $currency->formatUsd($shippingProgress['remaining']) }} more for FREE delivery</p>
@endif
<div class="pc-drawer__shipping-bar" role="progressbar" aria-valuenow="{{ (int) $shippingProgress['percent'] }}" aria-valuemin="0" aria-valuemax="100">
    <span class="pc-drawer__shipping-bar-fill" style="width: {{ $shippingProgress['percent'] }}%;"></span>
</div>

@if(count($lines) === 0)
    <p class="pc-drawer__empty">Your bag is empty.</p>
@else
    <ul class="pc-drawer__lines">
        @foreach($lines as $row)
            @php
                $p = $row['product'];
                $variant = $row['variant'];
                $image = $variant->frontImage($p) ?: asset('assets/img/placeholder.svg');
            @endphp
            <li class="pc-drawer__line" data-cart-line data-variant-id="{{ $variant->id }}">
                <a class="pc-drawer__line-thumb" href="{{ route('shop.product', $p) }}">
                    <img src="{{ $image }}" alt="" width="80" height="80" loading="lazy">
                </a>
                <div class="pc-drawer__line-body">
                    <a class="pc-drawer__line-name" href="{{ route('shop.product', $p) }}">{{ $p->name }}</a>
                    @if($row['variant_label'] !== 'Default')
                        <p class="pc-drawer__line-variant">{{ $row['variant_label'] }}</p>
                    @endif
                    <p class="pc-drawer__line-price">{{ $currency->formatUsd((float) $row['unit_price_usd']) }}</p>
                    <div class="pc-drawer__line-qty">
                        <button type="button" class="pc-drawer__qty-btn" data-cart-qty-dec aria-label="Decrease quantity">−</button>
                        <span class="pc-drawer__qty-val" data-cart-qty-val>{{ $row['quantity'] }}</span>
                        <button type="button" class="pc-drawer__qty-btn" data-cart-qty-inc aria-label="Increase quantity">+</button>
                    </div>
                </div>
                <button type="button" class="pc-drawer__line-remove" data-cart-remove aria-label="Remove item">×</button>
            </li>
        @endforeach
    </ul>
@endif

<div class="pc-drawer__footer">
    <p class="pc-drawer__subtotal">
        <span>Subtotal</span>
        <strong data-cart-subtotal>{{ $currency->formatUsd($subtotalUsd) }}</strong>
    </p>
    <p class="pc-drawer__note">Have a discount code? Enter it at checkout.</p>
    <a class="btn btn--primary pc-drawer__checkout" href="{{ route('shop.checkout') }}">Secure Checkout</a>
    @include('shop.partials.payment-icons')
</div>
