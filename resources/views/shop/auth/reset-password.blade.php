@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Reset password</h1>
    <p class="page-head__summary">Choose a new password for your account.</p>
</header>

<section class="account-card auth-card">
    <form class="auth-form" method="post" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>
            Email
            <input type="email" name="email" required autocomplete="email" value="{{ old('email', $email) }}">
        </label>
        <label>
            New password
            <input type="password" name="password" required autocomplete="new-password" minlength="8">
        </label>
        <label>
            Confirm password
            <input type="password" name="password_confirmation" required autocomplete="new-password" minlength="8">
        </label>
        @error('email')
            <p class="form-error">{{ $message }}</p>
        @enderror
        <button class="btn btn--primary" type="submit">Reset password</button>
        <p class="auth-form__footer"><a href="{{ route('login') }}">Back to sign in</a></p>
    </form>
</section>
@endsection
