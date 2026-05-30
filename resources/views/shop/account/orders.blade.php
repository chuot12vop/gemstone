@extends('layouts.shop')

@section('content')
<header class="page-head">
    <h1 class="page-head__title">Order history</h1>
    <p class="page-head__summary">All orders linked to your account.</p>
</header>

@if($orders->isEmpty())
    <p class="muted">You have not placed any orders yet. <a href="{{ route('shop.products.index') }}">Start shopping</a>.</p>
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
                    <td class="account-orders-table__products">
                        <div class="account-order-thumbs">
                            @foreach($order->items->take(4) as $item)
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
                            @if($order->items->count() > 4)
                                <span class="account-order-thumbs__more">+{{ $order->items->count() - 4 }}</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->created_at->format('M j, Y') }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td>{{ $currency->formatUsd((float) $order->subtotal_usd) }}</td>
                    <td class="account-orders-table__action"><a href="{{ route('shop.account.orders.show', $order->order_number) }}">Details</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
@endif

<p class="account-back-link"><a href="{{ route('shop.account.index') }}">← Back to account</a></p>
@endsection
