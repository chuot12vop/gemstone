@if(count($lines) === 0)
@else
<ul class="cart-page__lines" data-cart-page-lines>
    @foreach($lines as $row)
        @php
            $p = $row['product'];
            $variant = $row['variant'];
            $image = $variant->frontImage($p) ?: asset('assets/img/placeholder.svg');
        @endphp
        <li class="cart-page__line" data-cart-line data-variant-id="{{ $variant->id }}">
            <a class="cart-page__line-thumb" href="{{ route('shop.product', $p) }}">
                <img src="{{ $image }}" alt="" width="96" height="96" loading="lazy">
            </a>
            <div class="cart-page__line-body">
                <a class="cart-page__line-name" href="{{ route('shop.product', $p) }}">{{ $p->name }}</a>
                @if($row['variant_label'] !== 'Default')
                    <p class="cart-page__line-variant">{{ $row['variant_label'] }}</p>
                @endif
                <p class="cart-page__line-price">{{ $currency->formatUsd((float) $row['unit_price_usd']) }}</p>
                <div class="cart-page__line-qty">
                    <button type="button" class="cart-page__qty-btn" data-cart-qty-dec aria-label="Decrease quantity">−</button>
                    <span class="cart-page__qty-val" data-cart-qty-val>{{ $row['quantity'] }}</span>
                    <button type="button" class="cart-page__qty-btn" data-cart-qty-inc aria-label="Increase quantity">+</button>
                </div>
            </div>
            <button type="button" class="cart-page__line-remove" data-cart-remove aria-label="Remove item">×</button>
        </li>
    @endforeach
</ul>
@endif
