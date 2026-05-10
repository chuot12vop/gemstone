@php
    $rating = (float) ($rating ?? 0);
    $rounded = (int) round($rating);
    $size = $size ?? 16;
@endphp
<span class="stars" role="img" aria-label="Rated {{ number_format($rating, 1) }} out of 5">
    @for($i = 1; $i <= 5; $i++)
        <svg class="stars__icon {{ $i <= $rounded ? 'is-on' : '' }}" viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" aria-hidden="true">
            <path d="M12 2l2.95 6.6 7.05.7-5.3 4.92 1.55 7.18L12 17.85 5.75 21.4 7.3 14.22 2 9.3l7.05-.7L12 2z"
                  fill="{{ $i <= $rounded ? 'currentColor' : 'none' }}"
                  stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        </svg>
    @endfor
</span>
