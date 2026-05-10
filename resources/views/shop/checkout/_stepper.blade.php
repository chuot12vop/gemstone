@php
    $steps = [
        1 => 'Payment method',
        2 => 'Your details',
        3 => 'Complete payment',
    ];
@endphp
<ol class="checkout-stepper" aria-label="Checkout progress">
    @foreach($steps as $idx => $label)
        @php
            $state = $idx < $step ? 'done' : ($idx === $step ? 'current' : 'todo');
        @endphp
        <li class="checkout-stepper__item checkout-stepper__item--{{ $state }}" @if($state === 'current') aria-current="step" @endif>
            <span class="checkout-stepper__bullet" aria-hidden="true">{{ $idx }}</span>
            <span class="checkout-stepper__label">{{ $label }}</span>
        </li>
    @endforeach
</ol>
