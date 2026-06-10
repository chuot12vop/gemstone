@php
    use App\Support\ProductPricing;
    use App\Support\ProductVariantOptions;

    $variants = $product->relationLoaded('variants')
        ? $product->variants->where('is_active', true)->values()
        : collect();
    $defaultVariant = $variants->firstWhere('is_default', true) ?: $variants->first();
    $pickerVariants = ProductVariantOptions::toPickerJson($product, $variants);
    $colors = $variants->pluck('option_color')->filter(fn (?string $c) => $c !== null && trim($c) !== '')->unique()->values();
    $sizes = ProductVariantOptions::sizes($variants);
    $initialColor = $defaultVariant?->option_color ?? '';
    $initialSize = $defaultVariant?->option_size ?? '';
    $mainImage = $defaultVariant?->frontImage($product) ?: ($product->image ?: asset('assets/img/placeholder.svg'));
    $displayPrice = $defaultVariant ? (float) $defaultVariant->price_usd : (float) $product->price_usd;
    $inStock = ($defaultVariant?->stock ?? $product->stock) > 0;
    $hasUpsells = $product->relationLoaded('upsellProducts') && $product->upsellProducts->isNotEmpty();
@endphp
<div class="pc-drawer"
     data-pc-drawer
     data-product-id="{{ $product->id }}"
     hidden
     aria-hidden="true">
    <div class="pc-drawer__backdrop" data-pc-drawer-close tabindex="-1"></div>
    <div class="pc-drawer__shell {{ $hasUpsells ? '' : 'pc-drawer__shell--bag-only' }}" role="dialog" aria-modal="true" aria-label="Quick add {{ $product->name }}">
        @if($hasUpsells)
            <aside class="pc-drawer__upsells" aria-label="Why not add">
                <h2 class="pc-drawer__upsells-title">Why Not Add</h2>
                <ul class="pc-drawer__upsells-list">
                    @foreach($product->upsellProducts as $upsell)
                        @php
                            $upsellVariant = $upsell->defaultVariant();
                            $upsellImage = $upsellVariant?->frontImage($upsell) ?: ($upsell->thumbnail ?: ($upsell->image ?: asset('assets/img/placeholder.svg')));
                            $base = $upsellVariant ? (float) $upsellVariant->price_usd : (float) $upsell->price_usd;
                            $discountPct = (float) ($upsell->pivot->discount ?? 0);
                            $upsalePct = (float) ($upsell->pivot->upsale_discount ?? 0);
                            $cartUsd = ProductPricing::afterPercentDiscount($base, $upsalePct > 0 ? $upsalePct : ($discountPct > 0 ? $discountPct : null));
                            $displayUsd = $cartUsd;
                            $upsellInStock = ($upsellVariant?->stock ?? $upsell->stock) > 0;
                        @endphp
                        <li class="pc-drawer__upsell-item">
                            <a class="pc-drawer__upsell-media" href="{{ route('shop.product', $upsell) }}">
                                <img src="{{ $upsellImage }}" alt="{{ $upsell->name }}" width="120" height="120" loading="lazy">
                            </a>
                            <div class="pc-drawer__upsell-body">
                                <a class="pc-drawer__upsell-name" href="{{ route('shop.product', $upsell) }}">{{ $upsell->name }}</a>
                                <p class="pc-drawer__upsell-price">{{ $currency->formatUsd($displayUsd) }}</p>
                                <button type="button"
                                        class="pc-drawer__upsell-add"
                                        data-pc-upsell-add
                                        data-variant-id="{{ $upsellVariant?->id }}"
                                        data-upsell-parent-product-id="{{ $product->id }}"
                                        data-unit-price-usd="{{ $cartUsd }}"
                                        {{ ! $upsellInStock || ! $upsellVariant ? 'disabled' : '' }}>
                                    Add to Bag
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif

        <aside class="pc-drawer__bag" aria-label="Your bag">
            <header class="pc-drawer__bag-head">
                <h2 class="pc-drawer__bag-title">Your Bag (<span data-pc-drawer-count>{{ app(\App\Services\CartService::class)->totalQuantity() }}</span>)</h2>
                <button type="button" class="pc-drawer__close" data-pc-drawer-close aria-label="Close">×</button>
            </header>

            <section class="pc-drawer__add-product" data-pc-drawer-add>
                <div class="pc-drawer__add-row">
                    <img class="pc-drawer__add-thumb" src="{{ $mainImage }}" alt="" width="72" height="72" data-pc-drawer-thumb>
                    <div class="pc-drawer__add-info">
                        <p class="pc-drawer__add-name">{{ $product->name }}</p>
                        <p class="pc-drawer__add-price" data-pc-drawer-price>{{ $currency->formatUsd($displayPrice) }}</p>
                    </div>
                </div>

                @if($variants->isNotEmpty())
                    <div class="pc-drawer__variants"
                         data-pc-drawer-variants
                         data-variants='@json($pickerVariants)'
                         data-initial-color="{{ $initialColor }}"
                         data-initial-size="{{ $initialSize }}">
                        @if($colors->isNotEmpty())
                            <div class="pc-drawer__variant-group">
                                <span class="pc-drawer__variant-label">Colour</span>
                                <div class="pc-drawer__variant-options">
                                    @foreach($colors as $i => $color)
                                        <button type="button"
                                                class="pc-drawer__variant-btn {{ ($initialColor === $color) || ($i === 0 && $initialColor === '') ? 'is-active' : '' }}"
                                                data-pc-color="{{ $color }}">{{ $color }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if($sizes !== [])
                            <div class="pc-drawer__variant-group">
                                <span class="pc-drawer__variant-label">Size</span>
                                <div class="pc-drawer__variant-options">
                                    @foreach($sizes as $i => $size)
                                        <button type="button"
                                                class="pc-drawer__variant-btn {{ ($initialSize === $size) || ($i === 0 && $initialSize === '') ? 'is-active' : '' }}"
                                                data-pc-size="{{ $size }}">{{ $size }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <button type="button"
                        class="btn btn--primary pc-drawer__add-btn"
                        data-pc-drawer-add-btn
                        data-variant-id="{{ $defaultVariant?->id }}"
                        {{ ! $inStock ? 'disabled' : '' }}>
                    Add to Bag
                </button>
            </section>

            <div class="pc-drawer__bag-body" data-pc-drawer-bag-body>
                {{-- Filled via AJAX on open --}}
            </div>
        </aside>
    </div>
</div>
