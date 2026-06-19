@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Forgot password</h1>
    <p class="page-head__summary">Enter your email and we will send you a link to reset your password.</p>
</header>

<section class="account-card auth-card">
    <form class="auth-form" method="post" action="{{ route('password.email') }}">
        @csrf
        <label>
            Email
            <input type="email" name="email" required autocomplete="email" value="{{ old('email') }}">
        </label>
        @error('email')
            <p class="form-error">{{ $message }}</p>
        @enderror
        <button class="btn btn--primary" type="submit">Send reset link</button>
        <p class="auth-form__footer"><a class="btn btn--ghost btn--small" href="{{ route('login') }}">Back to sign in</a></p>
    </form>
</section>
@endsection
