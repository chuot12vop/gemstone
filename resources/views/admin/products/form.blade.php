@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.products.index') }}">← Back to list</a>
@endsection

@section('content')
<form class="stack-form" method="post" action="{{ $product ? route('admin.products.update', $product) : route('admin.products.store') }}">
    @csrf
    @if($product)
        @method('PUT')
    @endif
    <div class="form-grid">
        <label>
            Name
            <input type="text" name="name" required value="{{ old('name', $product->name ?? '') }}">
        </label>
        <label>
            URL slug
            <input type="text" name="slug" placeholder="auto from name" value="{{ old('slug', $product->slug ?? '') }}">
        </label>
        <label>
            Category
            <select name="category_id" required>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected((int) old('category_id', $product->category_id ?? 0) === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Price (USD)
            <input type="text" name="price_usd" required value="{{ old('price_usd', $product ? (string) $product->price_usd : '') }}">
        </label>
        <label>
            Stock
            <input type="number" name="stock" min="0" value="{{ old('stock', $product ? (string) $product->stock : '0') }}">
        </label>
        <label>
            Image URL or path
            <input type="text" name="image" placeholder="{{ asset('assets/img/placeholder.svg') }}" value="{{ old('image', $product->image ?? '') }}">
        </label>
    </div>

    <label>
        Short description
        <textarea name="short_description" rows="2">{{ old('short_description', $product->short_description ?? '') }}</textarea>
    </label>
    <label>
        Full description
        <textarea name="description" rows="6">{{ old('description', $product->description ?? '') }}</textarea>
    </label>

    <fieldset class="form-fieldset">
        <legend>SEO</legend>
        <label>
            Meta title
            <input type="text" name="meta_title" value="{{ old('meta_title', $product->meta_title ?? '') }}">
        </label>
        <label>
            Meta description
            <textarea name="meta_description" rows="2">{{ old('meta_description', $product->meta_description ?? '') }}</textarea>
        </label>
    </fieldset>

    <label class="checkbox">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product ? $product->is_active : true))>
        Active on storefront
    </label>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">{{ $product ? 'Save changes' : 'Create product' }}</button>
        <a class="btn-admin" href="{{ route('admin.products.index') }}">Cancel</a>
    </div>
</form>
@endsection
