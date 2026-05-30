@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">My account</h1>
    <p class="page-head__summary">Welcome back, {{ $user->name }}.</p>
</header>

<div class="account-grid">
    <section class="account-card">
        <h2 class="account-card__title">Profile</h2>
        <dl class="account-dl account-dl--lined">
            <div class="account-dl__row">
                <dt>Name</dt><dd>{{ $user->name }}</dd>
            </div>
            <div class="account-dl__row">
                <dt>Email</dt><dd>{{ $user->email }}</dd>
            </div>
            <div class="account-dl__row">
                <dt>Phone</dt><dd>{{ $user->phone ?: '—' }}</dd>
            </div>
        </dl>
        <a class="btn btn--small" href="{{ route('shop.account.profile') }}">Edit profile</a>
    </section>

    <section class="account-card">
        <h2 class="account-card__title">Recent orders</h2>
        @if($recentOrders->isEmpty())
            <p class="muted">No orders yet.</p>
        @else
            <ul class="account-orders-list">
                @foreach($recentOrders as $order)
                    <li>
                        <a href="{{ route('shop.account.orders.show', $order->order_number) }}">
                            <strong class="account-order-no">{{ $order->order_number }}</strong>
                            <span>{{ ucfirst($order->status) }}</span>
                            <span>{{ $order->created_at->format('M j, Y') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
        <a class="btn btn--small" href="{{ route('shop.account.orders') }}">View all orders</a>
    </section>
</div>
@endsection
