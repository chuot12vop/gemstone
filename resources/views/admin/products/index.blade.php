@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin btn-admin--primary" href="{{ route('admin.products.create') }}">+ New product</a>
@endsection

@section('module-meta')
    {{ $products->count() }} {{ \Illuminate\Support\Str::plural('item', $products->count()) }}
    @if(!empty($q)) — search: <strong>{{ $q }}</strong> @endif
@endsection

@section('content')
<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Price (USD)</th>
            <th>Stock</th>
            <th>Active</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($products as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td>{{ $p->category->name ?? '' }}</td>
                <td>${{ number_format((float) $p->price_usd, 2) }}</td>
                <td>{{ $p->stock }}</td>
                <td>
                    <span class="badge {{ $p->is_active ? 'badge--ok' : 'badge--off' }}">
                        {{ $p->is_active ? 'Active' : 'Hidden' }}
                    </span>
                </td>
                <td class="data-table__actions">
                    <a class="btn-admin btn-admin--small" href="{{ route('admin.products.edit', $p) }}">Edit</a>
                    <form class="inline-form" method="post" action="{{ route('admin.products.destroy', $p) }}" onsubmit="return confirm('Delete this product?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="data-table__empty">No products found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
