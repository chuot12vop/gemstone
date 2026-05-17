@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Sign in</h1>
    <p class="page-head__summary">Use Google to sign in, then complete your profile and view order history.</p>
</header>

<p class="login-google-wrap">
    <a class="btn btn--google" href="{{ route('auth.google.redirect') }}">Continue with Google</a>
</p>
<p class="login-note">New customers are registered automatically on first sign-in. After signing in you can add your phone number and see past orders in <a href="{{ route('shop.account.index') }}">My account</a>.</p>
@endsection
