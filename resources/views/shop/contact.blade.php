@extends('layouts.shop')

@section('mainClass', 'site-main--contact')

@section('content')
<div class="contact-page">
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

            <form
                class="contact-form"
                method="post"
                action="{{ route('shop.contact.store') }}"
                data-contact-form
            >
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
                    Email
                    <input type="email" name="email" maxlength="190" value="{{ old('email') }}" placeholder="you@example.com">
                </label>
                <label>
                    Address
                    <textarea name="address" rows="3" required maxlength="500">{{ old('address') }}</textarea>
                </label>
                <label>
                    Product <span class="muted">(optional)</span>
                    <input type="text" name="product" maxlength="190" value="{{ old('product') }}" placeholder="Bracelet, ring, etc.">
                </label>
                <label>
                    Message <span class="muted">(optional)</span>
                    <textarea name="message" rows="4" maxlength="2000" placeholder="How can we help?">{{ old('message') }}</textarea>
                </label>
                <div class="contact-form__actions">
                    <button class="btn btn--primary" type="submit">Send message</button>
                </div>
                <p class="contact-form__feedback" data-contact-feedback hidden role="status"></p>
            </form>
        </section>
    </div>
</div>
@endsection
