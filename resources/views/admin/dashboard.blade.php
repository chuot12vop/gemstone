@extends('layouts.admin')

@section('module-meta')
    Welcome back. Quick overview of your store.
@endsection

@section('content')
<div class="stat-grid">
    <a class="stat-card stat-card--link" href="{{ route('admin.products.index') }}">
        <span class="stat-card__label">Products</span>
        <span class="stat-card__value">{{ $productCount }}</span>
        <span class="stat-card__hint">Manage catalog →</span>
    </a>
    <a class="stat-card stat-card--link" href="{{ route('admin.orders.index') }}">
        <span class="stat-card__label">Orders</span>
        <span class="stat-card__value">{{ $orderCount }}</span>
        <span class="stat-card__hint">Review orders →</span>
    </a>
    <a class="stat-card stat-card--link" href="{{ route('admin.currency.index') }}">
        <span class="stat-card__label">Currencies</span>
        <span class="stat-card__value">FX</span>
        <span class="stat-card__hint">Update rates →</span>
    </a>
</div>

<h2 class="admin-h2">Recent orders</h2>
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>Number</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($recentOrders as $o)
            <tr>
                <td><strong>{{ $o->order_number }}</strong></td>
                <td>{{ $o->customer_email }}</td>
                <td>{{ $o->currency_code }} {{ number_format((float) $o->total_display, 2) }}</td>
                <td><span class="badge badge--{{ $o->status }}">{{ $o->status }}</span></td>
                <td class="data-table__actions"><a class="btn-admin btn-admin--small" href="{{ route('admin.orders.show', $o) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="data-table__empty">No orders yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
