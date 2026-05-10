@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin btn-admin--primary" href="{{ route('admin.reviews.create') }}">+ New review</a>
@endsection

@section('module-meta')
    {{ $reviews->count() }} review{{ $reviews->count() === 1 ? '' : 's' }}
@endsection

@section('content')
<form class="stack-form form-inline" method="get" action="{{ route('admin.reviews.index') }}" style="margin-bottom:14px;">
    <label class="form-inline__field">
        Search
        <input type="text" name="q" value="{{ $q }}" placeholder="Title, content, customer">
    </label>
    <label class="form-inline__field">
        Product
        <select name="product_id">
            <option value="0">All products</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected($productId === (int) $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
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
        Rating
        <select name="rating">
            <option value="0">All</option>
            @foreach([5,4,3,2,1] as $r)
                <option value="{{ $r }}" @selected($rating === $r)>{{ $r }} ★</option>
            @endforeach
        </select>
    </label>
    <button class="btn-admin" type="submit">Filter</button>
</form>

<div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>Created</th>
            <th>Product</th>
            <th>Customer</th>
            <th>Rating</th>
            <th>Title / Content</th>
            <th>Photos</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($reviews as $review)
            <tr>
                <td>{{ $review->created_at?->format('Y-m-d H:i') }}</td>
                <td>
                    @if($review->product)
                        <a href="{{ route('shop.product', $review->product) }}" target="_blank" rel="noopener">{{ $review->product->name }}</a>
                    @else
                        <em>Deleted</em>
                    @endif
                </td>
                <td>
                    {{ $review->customer_name }}<br>
                    <span class="muted">{{ $review->customer_email }}</span>
                </td>
                <td><strong>{{ $review->rating }}</strong> ★</td>
                <td>
                    @if($review->title)<strong>{{ $review->title }}</strong><br>@endif
                    <span class="admin-review-snippet">{{ Str::limit($review->content, 140) }}</span>
                </td>
                <td>
                    @if($review->images->isNotEmpty())
                        <span class="admin-review-thumbs">
                            @foreach($review->images->take(3) as $img)
                                <img src="{{ $img->path }}" alt="" loading="lazy">
                            @endforeach
                            @if($review->images->count() > 3)
                                <span class="admin-review-thumbs__more">+{{ $review->images->count() - 3 }}</span>
                            @endif
                        </span>
                    @else
                        -
                    @endif
                </td>
                <td><span class="badge badge--{{ $review->status }}">{{ $review->status }}</span></td>
                <td class="data-table__actions">
                    <a class="btn-admin btn-admin--small" href="{{ route('admin.reviews.edit', $review) }}">Edit</a>
                    <form method="post" action="{{ route('admin.reviews.destroy', $review) }}"
                          onsubmit="return confirm('Delete this review?');" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn-admin btn-admin--small btn-admin--danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="data-table__empty">No reviews match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
