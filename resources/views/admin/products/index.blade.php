@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin btn-admin--primary" href="{{ route('admin.products.create') }}">+ New product</a>
@endsection

@section('module-meta')
    {{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
    @if($products->hasPages())
        · page {{ $products->currentPage() }} of {{ $products->lastPage() }}
    @endif
    @if(!empty($q)) — search: <strong>{{ $q }}</strong> @endif
    @if($brandId > 0)
        · brand: <strong>{{ $brands->firstWhere('id', $brandId)?->name ?? '—' }}</strong>
    @endif
    @if($categoryId > 0)
        · category: <strong>{{ $categories->firstWhere('id', $categoryId)?->name ?? '—' }}</strong>
    @endif
@endsection

@section('content')
<form class="stack-form form-inline" method="get" action="{{ route('admin.products.index') }}" style="margin-bottom:14px;">
    <label class="form-inline__field">
        Search
        <input type="text" name="q" value="{{ $q }}" placeholder="Name or slug">
    </label>
    <label class="form-inline__field">
        Brand
        <select name="brand_id">
            <option value="0">All brands</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected($brandId === (int) $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="form-inline__field">
        Category
        <select name="category_id">
            <option value="0">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected($categoryId === (int) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </label>
    <button class="btn-admin" type="submit">Filter</button>
    @if($q !== '' || $brandId > 0 || $categoryId > 0)
        <a class="btn-admin" href="{{ route('admin.products.index') }}">Clear</a>
    @endif
</form>

<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Brand</th>
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
                <td>{{ $p->brand->name ?? '—' }}</td>
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
            <tr><td colspan="7" class="data-table__empty">No products match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($products->hasPages())
    @include('admin.partials.pagination', ['paginator' => $products])
@endif
@endsection
