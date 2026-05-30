@php
    use App\Models\CurrencyRate;
    use App\Support\ProductPricing;

    $parentVariant = $product->defaultVariant();
    $mainImage = $parentVariant?->frontImage($product) ?: ($product->thumbnail ?: ($product->image ?: asset('assets/img/placeholder.svg')));
    $mainBase = $parentVariant ? (float) $parentVariant->price_usd : (float) $product->price_usd;
    $mainDisplay = $mainBase;
    $mainCart = $mainBase;
    $mainStock = $parentVariant ? (int) $parentVariant->stock : (int) $product->stock;
    $currencyCode = $currency->currentCode();
    $currencyRate = CurrencyRate::query()->where('code', $currencyCode)->where('is_active', true)->first();
    $ratePerUsd = $currencyRate ? (float) $currencyRate->rate_per_usd : 1.0;
    $currencySymbol = $currencyRate ? $currencyRate->symbol : '$';
@endphp
@if($product->upsellProducts->isNotEmpty())
<section class="product-upsell"
         data-product-upsell
         data-currency-symbol="{{ $currencySymbol }}"
         data-currency-rate="{{ $ratePerUsd }}"
         data-currency-code="{{ $currencyCode }}"
         aria-labelledby="product-upsell-title">
    <h2 id="product-upsell-title" class="product-upsell__title">Frequently bought together</h2>

    <form class="product-upsell__form" method="post" action="{{ route('shop.cart.add-bundle') }}" data-product-upsell-form>
        @csrf
        <input type="hidden" name="parent_product_id" value="{{ $product->id }}">

        <ul class="product-upsell__list">
            <li class="product-upsell__item product-upsell__item--locked"
                data-base-usd="{{ $mainBase }}"
                data-display-usd="{{ $mainDisplay }}"
                data-cart-usd="{{ $mainCart }}">
                <label class="product-upsell__row">
                    <input type="checkbox"
                           class="product-upsell__check"
                           name="items[{{ $product->id }}][selected]"
                           value="1"
                           checked
                           disabled
                           data-upsell-check
                           data-upsell-locked
                           data-base-usd="{{ $mainBase }}"
                           data-display-usd="{{ $mainDisplay }}"
                           data-cart-usd="{{ $mainCart }}">
                    <input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                    @if($parentVariant)
                        <input type="hidden" name="items[{{ $product->id }}][variant_id]" value="{{ $parentVariant->id }}">
                    @endif
                    <input type="hidden" name="items[{{ $product->id }}][quantity]" value="1">
                    <span class="product-upsell__thumb">
                        <img src="{{ $mainImage }}" alt="" width="72" height="72" loading="lazy">
                    </span>
                    <span class="product-upsell__body">
                        <span class="product-upsell__name">{{ $product->name }}</span>
                        <span class="product-upsell__prices">
                            <span class="product-upsell__price product-upsell__price--sale"
                                  data-upsell-sale>{{ $currency->formatUsd($mainDisplay) }}</span>
                        </span>
                    </span>
                </label>
            </li>

            @foreach($product->upsellProducts as $upsell)
                @php
                    $upsellVariant = $upsell->defaultVariant();
                    $upsellImage = $upsellVariant?->frontImage($upsell) ?: ($upsell->thumbnail ?: ($upsell->image ?: asset('assets/img/placeholder.svg')));
                    $base = $upsellVariant ? (float) $upsellVariant->price_usd : (float) $upsell->price_usd;
                    $discountPct = (float) ($upsell->pivot->discount ?? 0);
                    $upsalePct = (float) ($upsell->pivot->upsale_discount ?? 0);
                    $displayUsd = ProductPricing::afterPercentDiscount($base, $discountPct > 0 ? $discountPct : null);
                    $cartUsd = ProductPricing::afterPercentDiscount($base, $upsalePct > 0 ? $upsalePct : ($discountPct > 0 ? $discountPct : null));
                    $inStock = ($upsellVariant?->stock ?? $upsell->stock) > 0;
                @endphp
                <li class="product-upsell__item">
                    <label class="product-upsell__row">
                        <input type="checkbox"
                               class="product-upsell__check"
                               name="items[{{ $upsell->id }}][selected]"
                               value="1"
                               checked
                               {{ $inStock ? '' : 'disabled' }}
                               data-upsell-check
                               data-base-usd="{{ $base }}"
                               data-display-usd="{{ $displayUsd }}"
                               data-cart-usd="{{ $cartUsd }}">
                        <input type="hidden" name="items[{{ $upsell->id }}][product_id]" value="{{ $upsell->id }}" data-upsell-product-id disabled>
                        @if($upsellVariant)
                            <input type="hidden" name="items[{{ $upsell->id }}][variant_id]" value="{{ $upsellVariant->id }}" data-upsell-variant-id disabled>
                        @endif
                        <input type="hidden" name="items[{{ $upsell->id }}][quantity]" value="1" data-upsell-qty disabled>
                        <span class="product-upsell__thumb">
                            <img src="{{ $upsellImage }}" alt="" width="72" height="72" loading="lazy">
                        </span>
                        <span class="product-upsell__body">
                            <span class="product-upsell__name">{{ $upsell->name }}</span>
                            <span class="product-upsell__prices">
                                <span class="product-upsell__price product-upsell__price--sale">{{ $currency->formatUsd($displayUsd) }}</span>
                                @if($displayUsd < $base - 0.001)
                                    <span class="product-upsell__price product-upsell__price--was">{{ $currency->formatUsd($base) }}</span>
                                @endif
                            </span>
                            @if(! $inStock)
                                <span class="product-upsell__stock">Out of stock</span>
                            @endif
                        </span>
                    </label>
                </li>
            @endforeach
        </ul>

        @php
            $bundleSaleUsd = $mainCart;
            $bundleBaseUsd = $mainBase;
            foreach ($product->upsellProducts as $upsell) {
                $upsellVariant = $upsell->defaultVariant();
                if (($upsellVariant?->stock ?? $upsell->stock) < 1) {
                    continue;
                }
                $base = $upsellVariant ? (float) $upsellVariant->price_usd : (float) $upsell->price_usd;
                $discountPct = (float) ($upsell->pivot->discount ?? 0);
                $upsalePct = (float) ($upsell->pivot->upsale_discount ?? 0);
                $pct = $upsalePct > 0 ? $upsalePct : $discountPct;
                $bundleSaleUsd += ProductPricing::afterPercentDiscount($base, $pct > 0 ? $pct : null);
                $bundleBaseUsd += $base;
            }
        @endphp
        <button type="submit" class="product-upsell__claim" data-upsell-submit {{ $mainStock < 1 ? 'disabled' : '' }}>
            <span class="product-upsell__claim-label">Claim Offer</span>
            <span class="product-upsell__claim-prices">
                <span class="product-upsell__claim-total" data-upsell-total-sale>{{ $currency->formatUsd($bundleSaleUsd) }}</span>
                @if($bundleBaseUsd > $bundleSaleUsd + 0.001)
                    <span class="product-upsell__claim-was" data-upsell-total-was>{{ $currency->formatUsd($bundleBaseUsd) }}</span>
                @else
                    <span class="product-upsell__claim-was" data-upsell-total-was hidden></span>
                @endif
            </span>
        </button>
    </form>
</section>
@endif
