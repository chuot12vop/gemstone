@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.orders.index') }}">← Back to list</a>
@endsection

@section('content')
<div class="grid-2col">
    <div>
        <h2 class="admin-h2">Customer</h2>
        <p>{{ $order->customer_name }}<br><span class="muted">{{ $order->customer_email }}</span></p>
        <h2 class="admin-h2">Ship to</h2>
        <p>{!! nl2br(e($order->shipping_address)) !!}</p>
    </div>
    <div>
        <h2 class="admin-h2">Status</h2>
        <p><span class="badge badge--{{ $order->status }}">{{ $order->status }}</span></p>
        <h2 class="admin-h2">Totals</h2>
        <p>USD subtotal <strong>${{ number_format((float) $order->subtotal_usd, 2) }}</strong></p>
        <p>Display <strong>{{ $order->currency_code }} {{ number_format((float) $order->total_display, 2) }}</strong></p>
    </div>
</div>

<h2 class="admin-h2">Items</h2>
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Unit USD</th>
            <th>Line USD</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>${{ number_format((float) $item->unit_price_usd, 2) }}</td>
                <td>${{ number_format((float) $item->line_total_usd, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<form class="stack-form form-inline" method="post" action="{{ route('admin.orders.status', $order) }}">
    @csrf
    <label class="form-inline__field">
        Update status
        <select name="status">
            @foreach(['pending', 'paid', 'shipped', 'cancelled'] as $s)
                <option value="{{ $s }}" @selected($order->status === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </label>
    <button class="btn-admin btn-admin--primary" type="submit">Save status</button>
</form>
@endsection
