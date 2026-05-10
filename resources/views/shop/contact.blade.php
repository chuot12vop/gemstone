@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Contact</h1>
    <p class="page-head__summary">Questions about sizing, materials, or your order? Reach our US-facing support team.</p>
</header>

<div class="contact-grid">
    
    <section class="contact-form-section">
        <h2 class="product-detail__section-title">Leave us a message</h2>
        <p class="muted">Share your details below and our team will follow up by phone.</p>

        @if($errors->any())
            <p class="banner banner--err">{{ $errors->first() }}</p>
        @endif

        <form class="contact-form" method="post" action="{{ route('shop.contact.store') }}">
            @csrf
            <label>
                Full name
                <input type="text" name="name" required maxlength="160" value="{{ old('name') }}">
            </label>
            <label>
                Phone number
                <input type="tel" name="phone" required maxlength="40" value="{{ old('phone') }}" placeholder="+1 (___) ___-____">
            </label>
            <label>
                Address
                <textarea name="address" rows="3" required maxlength="500">{{ old('address') }}</textarea>
            </label>
            <div class="contact-form__actions">
                <button class="btn btn--primary" type="submit">Send message</button>
            </div>
        </form>
    </section>
</div>
@endsection
