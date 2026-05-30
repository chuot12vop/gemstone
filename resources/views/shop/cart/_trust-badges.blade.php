@php
    use App\Support\ProductDetailPolicies;

    $trustIcons = ['warranty', 'shipping', 'returns'];
    $badges = collect(ProductDetailPolicies::rows())
        ->filter(fn ($row) => in_array($row['icon'], $trustIcons, true))
        ->values();
@endphp
@if($badges->isNotEmpty())
<section class="cart-page__trust" aria-label="Shopping reassurance">
    <ul class="cart-page__trust-list">
        @foreach($badges as $badge)
            <li class="cart-page__trust-item">
                @include('shop.partials.product-policy-icon', ['icon' => $badge['icon']])
                <span class="cart-page__trust-text">{{ $badge['text'] }}</span>
            </li>
        @endforeach
    </ul>
</section>
@endif
