<aside class="checkout-aside" aria-labelledby="checkout-aside-title">
    <div class="checkout-aside__header">
        <h2 id="checkout-aside-title" class="checkout-aside__title">Your order</h2>
        <button type="button" class="checkout-aside__toggle" data-checkout-summary-toggle aria-expanded="true" aria-controls="checkout-aside-summary">
            <span data-checkout-summary-toggle-label>Hide</span>
        </button>
    </div>
    <div id="checkout-aside-summary" class="checkout-aside__summary" data-checkout-summary-content>
        <ul class="checkout-aside__list">
            @foreach($lines as $row)
                @php
                    $p = $row['product'];
                    $variant = $row['variant'] ?? null;
                    $name = $p->name ?? 'Item';
                    $image = $variant?->frontImage($p) ?: ($p->image ?? null);
                    $productUrl = $p instanceof \App\Models\Product && $p->getKey()
                        ? route('shop.product', $p)
                        : null;
                @endphp
                <li class="checkout-aside__item">
                    @if($productUrl)
                        <a class="checkout-aside__thumb" href="{{ $productUrl }}">
                            <img src="{{ $image ?: asset('assets/img/placeholder.svg') }}" alt="" width="64" height="64" loading="lazy">
                        </a>
                    @else
                        <span class="checkout-aside__thumb">
                            <img src="{{ $image ?: asset('assets/img/placeholder.svg') }}" alt="" width="64" height="64" loading="lazy">
                        </span>
                    @endif
                    <div class="checkout-aside__body">
                        @if($productUrl)
                            <p class="checkout-aside__name"><a href="{{ $productUrl }}">{{ $name }}</a></p>
                        @else
                            <p class="checkout-aside__name">{{ $name }}</p>
                        @endif
                        @if(!empty($row['variant_label']) && $row['variant_label'] !== 'Default')
                            <p class="checkout-aside__variant">{{ $row['variant_label'] }}</p>
                        @endif
                        <p class="checkout-aside__meta">
                            <span class="checkout-aside__qty">× {{ $row['quantity'] }}</span>
                            <span class="checkout-aside__line">{{ $currency->formatUsd((float) $row['line_usd']) }}</span>
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="checkout-aside__subtotal">
            <span>Subtotal</span>
            <strong data-checkout-subtotal>{{ $currency->formatUsd((float) $subtotalUsd) }}</strong>
        </p>
        @php($discountUsd = (float) ($discountUsd ?? 0))
        @php($shippingUsd = (float) ($shippingUsd ?? 0))
        @php($taxUsd = (float) ($taxUsd ?? 0))
        <p class="checkout-aside__discount" data-checkout-discount-row @if($discountUsd <= 0) hidden @endif>
            <span>Discount</span>
            <strong data-checkout-discount>−{{ $currency->formatUsd($discountUsd) }}</strong>
        </p>
        <p class="checkout-aside__shipping" data-checkout-shipping-row>
            <span>Shipping</span>
            <strong data-checkout-shipping>{{ $shippingUsd <= 0 ? 'FREE' : $currency->formatUsd($shippingUsd) }}</strong>
        </p>
        <p class="checkout-aside__total">
            <span>Total</span>
            <strong data-checkout-total>{{ $currency->formatUsd((float) ($totalUsd ?? $subtotalUsd)) }}</strong>
        </p>
        <p class="checkout-aside__tax" data-checkout-tax-row @if($taxUsd <= 0) hidden @endif>
            Including <strong data-checkout-tax>{{ $currency->formatUsd($taxUsd) }}</strong> in taxes
        </p>
        @if(!empty($asideNote))
            <p class="checkout-aside__note">{{ $asideNote }}</p>
        @endif
    </div>
</aside>
