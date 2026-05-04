@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Checkout</h1>
    <p class="page-head__summary">Enter shipping details. Demo records the order only — add payment in production.</p>
</header>

<form class="checkout-form" method="post" action="{{ route('shop.checkout.place') }}">
    @csrf
    <div class="form-grid">
        <label>
            Full name
            <input type="text" name="customer_name" required value="{{ old('customer_name') }}">
        </label>
        <label>
            Email
            <input type="email" name="customer_email" required value="{{ old('customer_email') }}">
        </label>
        <label class="full">
            Shipping address
            <textarea name="shipping_address" rows="4" required>{{ old('shipping_address') }}</textarea>
        </label>
    </div>
    @if($errors->any())
        <p class="banner banner--err">{{ $errors->first() }}</p>
    @endif

    <section class="checkout-summary">
        <h2>Order summary</h2>
        <ul>
            @foreach($lines as $row)
                @php($p = $row['product'])
                <li>{{ $p->name }} × {{ $row['quantity'] }} — {{ $currency->formatUsd((float) $row['line_usd']) }}</li>
            @endforeach
        </ul>
        <p class="checkout-total">Total: {{ $currency->formatUsd((float) $subtotalUsd) }}</p>
    </section>

    <button class="btn btn--primary" type="submit">Place order</button>
</form>
@endsection
