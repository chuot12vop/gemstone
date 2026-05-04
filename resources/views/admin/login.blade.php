@extends('layouts.admin_login')

@section('content')
<form class="login-form" method="post" action="{{ route('admin.login.post') }}">
    @csrf
    <label>
        Email
        <input type="email" name="email" required value="{{ old('email') }}" autocomplete="username">
    </label>
    <label>
        Password
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button type="submit" class="btn-admin btn-admin--primary">Sign in</button>
</form>
@endsection
