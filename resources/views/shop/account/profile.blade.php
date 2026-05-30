@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Account profile</h1>
    <p class="page-head__summary">Update your name and phone number for checkout and order updates.</p>
</header>

<section class="account-card account-profile-card">
    <form class="account-profile-form" method="post" action="{{ route('shop.account.profile.update') }}">
        @csrf
        <div class="account-profile-form__row">
            <label class="account-profile-form__label" for="profile_name">Full name</label>
            <div class="account-profile-form__field">
                <input id="profile_name" type="text" name="name" required value="{{ old('name', $user->name) }}">
            </div>
        </div>
        <div class="account-profile-form__row">
            <label class="account-profile-form__label" for="profile_email">Email</label>
            <div class="account-profile-form__field">
                <input id="profile_email" type="email" value="{{ $user->email }}" disabled>
                <small class="muted">Email is managed through Google sign-in.</small>
            </div>
        </div>
        <div class="account-profile-form__row">
            <label class="account-profile-form__label" for="profile_phone">Phone</label>
            <div class="account-profile-form__field">
                <input
                    id="profile_phone"
                    type="tel"
                    name="phone"
                    inputmode="tel"
                    minlength="9"
                    value="{{ old('phone', $user->phone) }}"
                    placeholder="+1 555 000 0000"
                    title="Enter at least 9 digits"
                >
                <small class="muted">Minimum 9 digits.</small>
            </div>
        </div>
        <div class="account-profile-form__actions">
            <button class="btn btn--primary" type="submit">Save profile</button>
            <a class="btn" href="{{ route('shop.account.index') }}">Back</a>
        </div>
    </form>
</section>
@endsection
