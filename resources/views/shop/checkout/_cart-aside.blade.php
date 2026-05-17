<aside class="checkout-aside" aria-labelledby="checkout-aside-title">
    <h2 id="checkout-aside-title" class="checkout-aside__title">Your order</h2>
    <ul class="checkout-aside__list">
        @foreach($lines as $row)
            @php
                $p = $row['product'];
                $name = $p->name ?? 'Item';
                $image = $p->image ?? null;
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
        <strong>{{ $currency->formatUsd((float) $subtotalUsd) }}</strong>
    </p>
    @if(!empty($asideNote))
        <p class="checkout-aside__note">{{ $asideNote }}</p>
    @endif
</aside>
