@php
    $progress = $shippingProgress ?? [];
    $qualified = (bool) ($progress['qualified'] ?? false);
    $percent = (float) ($progress['percent'] ?? 0);
    $remaining = (float) ($progress['remaining'] ?? 0);
    $threshold = (float) ($progress['threshold'] ?? 100);
@endphp
<div class="checkout-free-shipping"
     data-free-shipping-bar
     data-threshold-usd="{{ $threshold }}"
     data-qualified="{{ $qualified ? '1' : '0' }}">
    <div class="checkout-free-shipping__track" role="progressbar"
         aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) round($percent) }}"
         aria-label="Free shipping progress">
        <span class="checkout-free-shipping__fill" data-free-shipping-fill style="width: {{ $percent }}%"></span>
    </div>
    <p class="checkout-free-shipping__msg" data-free-shipping-msg>
        @if($qualified)
            Hooray! Your order qualifies for <strong>FREE</strong> delivery.
        @else
            Spend <strong data-free-shipping-remaining>{{ $currency->formatUsd($remaining) }}</strong> more for <strong>FREE</strong> delivery
        @endif
    </p>
</div>
