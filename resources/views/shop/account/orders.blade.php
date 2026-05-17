@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Order history</h1>
    <p class="page-head__summary">All orders linked to your account.</p>
</header>

@if($orders->isEmpty())
    <p class="muted">You have not placed any orders yet. <a href="{{ route('shop.products.index') }}">Start shopping</a>.</p>
@else
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($orders as $order)
                <tr>
                    <td><code>{{ $order->order_number }}</code></td>
                    <td>{{ $order->created_at->format('M j, Y') }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td>{{ $currency->formatUsd((float) $order->subtotal_usd) }}</td>
                    <td><a href="{{ route('shop.account.orders.show', $order->order_number) }}">Details</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
@endif

<p><a href="{{ route('shop.account.index') }}">← Back to account</a></p>
@endsection
