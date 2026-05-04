@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Sign in</h1>
    <p class="page-head__summary">Use your Google account to sign in to {{ config('app.name') }}.</p>
</header>

<p class="login-google-wrap">
    <a class="btn btn--google" href="{{ route('auth.google.redirect') }}">Continue with Google</a>
</p>
<p class="login-note">New customers are registered automatically on first sign-in.</p>
@endsection
