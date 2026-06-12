@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Order history</h1>
    <p class="page-head__summary">All orders linked to your account.</p>
</header>

@if($orders->isEmpty())
    <section class="account-empty-state">
        <h2>No orders yet</h2>
        <p class="muted">When you place an order, its status and details will appear here.</p>
        <button class="btn btn--primary" type="button" data-navigate="{{ route('shop.products.index') }}">Start shopping</button>
    </section>
@else
    <div class="account-orders-table-wrap">
        <table class="account-orders-table">
            <thead>
            <tr>
                <th class="account-orders-table__th-products">Products</th>
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
                    <td class="account-orders-table__products" data-label="Products">
                        <div class="account-order-thumbs">
                            @foreach($order->items->take(3) as $item)
                                @php
                                    $product = $item->product;
                                    $thumb = $product
                                        ? ($product->thumbnail ?: ($product->image ?: asset('assets/img/placeholder.svg')))
                                        : asset('assets/img/placeholder.svg');
                                @endphp
                                <img
                                    class="account-order-thumbs__img"
                                    src="{{ $thumb }}"
                                    alt="{{ $item->product_name }}"
                                    width="44"
                                    height="44"
                                    loading="lazy"
                                >
                            @endforeach
                            @if($order->items->count() > 3)
                                <span class="account-order-thumbs__more">{{ $order->items->count() }} items</span>
                            @endif
                        </div>
                    </td>
                    <td data-label="Order"><span class="account-orders-table__order-no">{{ $order->order_number }}</span></td>
                    <td data-label="Date">{{ $order->created_at->format('M j, Y') }}</td>
                    <td data-label="Status">{{ ucfirst($order->status) }}</td>
                    <td data-label="Total">{{ $currency->formatUsd((float) $order->total_display) }}</td>
                    <td class="account-orders-table__action" data-label="">
                        <button class="btn btn--small btn--ghost" type="button" data-navigate="{{ route('shop.account.orders.show', $order->order_number) }}">View details</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
        <nav class="pagination-wrap" aria-label="Order history pagination">
            {{ $orders->links('shop.partials.pagination') }}
        </nav>
    @endif
@endif

<div class="account-back-link">
    <button class="btn btn--small btn--ghost" type="button" data-navigate="{{ route('shop.account.index') }}">&larr; Back to account</button>
</div>
@endsection
