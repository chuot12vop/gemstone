@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.reviews.index') }}">← Back to list</a>
@endsection

@section('content')
@if($errors->any())
    <div class="admin-banner admin-banner--err" role="alert" style="margin-bottom:12px;">
        {{ $errors->first() }}
    </div>
@endif

<form class="stack-form"
      method="post"
      enctype="multipart/form-data"
      action="{{ $review ? route('admin.reviews.update', $review) : route('admin.reviews.store') }}">
    @csrf
    @if($review)
        @method('PUT')
    @endif

    <div class="form-grid">
        <label>
            Product
            <select name="product_id" required>
                <option value="">Select a product…</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected((int) old('product_id', $review?->product_id) === (int) $product->id)>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </label>
        <label>
            Status
            <select name="status" required>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" @selected(old('status', $review?->status ?? \App\Models\Review::STATUS_PENDING) === $s)>
                        {{ ucfirst($s) }}
                    </option>
                @endforeach
            </select>
        </label>
        <label>
            Customer name
            <input type="text" name="customer_name" required maxlength="160"
                   value="{{ old('customer_name', $review?->customer_name) }}">
        </label>
        <label>
            Customer email
            <input type="email" name="customer_email" required maxlength="190"
                   value="{{ old('customer_email', $review?->customer_email) }}">
        </label>
        <label>
            Rating
            <select name="rating" required>
                @foreach([5,4,3,2,1] as $r)
                    <option value="{{ $r }}" @selected((int) old('rating', $review?->rating ?? 5) === $r)>{{ $r }} ★</option>
                @endforeach
            </select>
        </label>
        <label>
            Title
            <input type="text" name="title" maxlength="200"
                   value="{{ old('title', $review?->title) }}">
        </label>
    </div>

    <label>
        Content
        <textarea name="content" rows="5" maxlength="5000" required>{{ old('content', $review?->content) }}</textarea>
    </label>

    @if($review && $review->images->isNotEmpty())
        <fieldset class="form-fieldset">
            <legend>Existing photos</legend>
            <div class="admin-review-existing">
                @foreach($review->images as $img)
                    <label class="admin-review-existing__item">
                        <img src="{{ $img->path }}" alt="" loading="lazy">
                        <span><input type="checkbox" name="delete_image_ids[]" value="{{ $img->id }}"> Delete</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    <label>
        Add photos
        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
        <small class="muted">JPG, PNG or WebP, max 4 MB each, up to 5 files.</small>
    </label>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">{{ $review ? 'Save changes' : 'Create review' }}</button>
        <a class="btn-admin" href="{{ route('admin.reviews.index') }}">Cancel</a>
    </div>
</form>
@endsection
