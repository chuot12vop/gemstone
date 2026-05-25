@if(!empty($policies))
    <section class="pd-policies" aria-label="Store policies">
        <ul class="pd-policies__list">
            @foreach($policies as $row)
                <li class="pd-policies__item">
                    @include('shop.partials.product-policy-icon', ['icon' => $row['icon'] ?? 'shipping'])
                    <span class="pd-policies__text">{{ $row['text'] }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endif
