<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Login' }} · {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
</head>
<body class="admin-login-body">
    <div class="admin-login">
        <p class="admin-login__brand">{{ config('app.name') }} Admin</p>
        @if($errors->any())
            <p class="admin-banner admin-banner--err">{{ $errors->first() }}</p>
        @endif
        @yield('content')
    </div>
</body>
</html>
