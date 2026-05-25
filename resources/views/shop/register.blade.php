@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Create account</h1>
    <p class="page-head__summary">Register to checkout, save your details, and view order history.</p>
</header>

@if($errors->any())
    <ul class="form-errors" role="alert">
        @foreach($errors->all() as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif

<form class="auth-form" method="post" action="{{ route('shop.register') }}">
    @csrf
    <label>
        Full name
        <input type="text" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
    </label>
    <label>
        Email
        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
    </label>
    <label>
        Password
        <input type="password" name="password" required autocomplete="new-password" minlength="8">
    </label>
    <label>
        Confirm password
        <input type="password" name="password_confirmation" required autocomplete="new-password" minlength="8">
    </label>
    <button type="submit" class="btn btn--primary">Create account</button>
</form>

<p class="auth-form__switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>

<p class="auth-form__divider" role="presentation"><span>or</span></p>

<p class="login-google-wrap">
    <a class="btn btn--google" href="{{ route('auth.google.redirect') }}">Continue with Google</a>
</p>
@endsection
