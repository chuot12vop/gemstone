@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Sign in</h1>
    <p class="page-head__summary">
        @if($checkoutRequired ?? false)
            Sign in to prefill your details and track this order in your account.
        @else
            Sign in to track orders and manage your profile. You can also checkout as a guest.
        @endif
    </p>
</header>

@if($errors->any())
    <ul class="form-errors" role="alert">
        @foreach($errors->all() as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif

<form class="auth-form" method="post" action="{{ route('shop.login') }}">
    @csrf
    <label>
        Email
        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
    </label>
    <label>
        Password
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <p class="auth-form__switch"><a class="btn btn--ghost btn--small" href="{{ route('password.request') }}">Forgot your password?</a></p>
    <label class="auth-form__remember">
        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
        Remember me
    </label>
    <button type="submit" class="btn btn--primary">Sign in</button>
</form>

<p class="auth-form__switch">No account yet? <a class="btn btn--ghost btn--small" href="{{ route('register') }}">Create one</a></p>

<p class="auth-form__divider" role="presentation"><span>or</span></p>

<p class="login-google-wrap">
    <a class="btn btn--google" href="{{ route('auth.google.redirect') }}">Continue with Google</a>
</p>

<p class="login-note">Google accounts are linked automatically on first sign-in. Manage your profile and orders in <a href="{{ route('shop.account.index') }}">My account</a>.</p>
@endsection
