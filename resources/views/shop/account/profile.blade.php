@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Account profile</h1>
    <p class="page-head__summary">Update your name and phone number for checkout and order updates.</p>
</header>

<form class="contact-form" method="post" action="{{ route('shop.account.profile.update') }}" style="max-width:520px;margin:0 auto;">
    @csrf
    <label>
        Full name
        <input type="text" name="name" required value="{{ old('name', $user->name) }}">
    </label>
    <label>
        Email
        <input type="email" value="{{ $user->email }}" disabled>
        <small class="muted">Email is managed through Google sign-in.</small>
    </label>
    <label>
        Phone
        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+1 555 000 0000">
    </label>
    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Save profile</button>
        <a class="btn" href="{{ route('shop.account.index') }}">Back</a>
    </div>
</form>
@endsection
