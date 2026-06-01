@extends('layouts.admin')

@section('module-meta')
    {{ $orders->total() }} {{ \Illuminate\Support\Str::plural('order', $orders->total()) }}
    @if($orders->hasPages())
        · page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}
    @endif
    @if(!empty($q)) — search: <strong>{{ $q }}</strong> @endif
    @if($status !== '') — status: <strong>{{ $status }}</strong> @endif
    @if($method !== '') — payment: <strong>{{ ucfirst(str_replace('_', ' ', $method)) }}</strong> @endif
    @if($dateFrom !== '' || $dateTo !== '')
        — dates:
        <strong>
            @if($dateFrom !== '' && $dateTo !== '')
                {{ $dateFrom }} – {{ $dateTo }}
            @elseif($dateFrom !== '')
                from {{ $dateFrom }}
            @else
                until {{ $dateTo }}
            @endif
        </strong>
    @endif
@endsection

@section('content')
<form class="stack-form form-inline" method="get" action="{{ route('admin.orders.index') }}" style="margin-bottom:14px;">
    <label class="form-inline__field">
        Search
        <input type="text" name="q" value="{{ $q }}" placeholder="Order #, name, email, phone">
    </label>
    <label class="form-inline__field">
        Status
        <select name="status">
            <option value="">All</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </label>
    <label class="form-inline__field">
        Payment method
        <select name="method">
            <option value="">All</option>
            @foreach($paymentMethods as $option)
                <option value="{{ $option }}" @selected($method === $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
            @endforeach
        </select>
    </label>
    <label class="form-inline__field">
        From
        <input type="date" name="date_from" value="{{ $dateFrom }}">
    </label>
    <label class="form-inline__field">
        To
        <input type="date" name="date_to" value="{{ $dateTo }}">
    </label>
    <button class="btn-admin" type="submit">Filter</button>
</form>

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
            <tr>
                <td colspan="6" class="data-table__empty">
                    {{ ($hasFilters ?? false) ? 'No orders match these filters.' : 'No orders yet.' }}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($orders->hasPages())
    @include('admin.partials.pagination', ['paginator' => $orders])
@endif
@endsection
