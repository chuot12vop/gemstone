@extends('layouts.admin')

@section('module-meta')
    {{ $orders->count() }} most recent
    @if(!empty($q)) — search: <strong>{{ $q }}</strong> @endif
@endsection

@section('content')
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>Number</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($orders as $o)
            <tr>
                <td><strong>{{ $o->order_number }}</strong></td>
                <td>{{ $o->created_at?->format('Y-m-d H:i') }}</td>
                <td>
                    {{ $o->customer_name }}<br>
                    <span class="muted">{{ $o->customer_email }}</span>
                </td>
                <td>{{ $o->currency_code }} {{ number_format((float) $o->total_display, 2) }}</td>
                <td><span class="badge badge--{{ $o->status }}">{{ $o->status }}</span></td>
                <td class="data-table__actions">
                    <a class="btn-admin btn-admin--small" href="{{ route('admin.orders.show', $o) }}">View</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="data-table__empty">No orders yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
