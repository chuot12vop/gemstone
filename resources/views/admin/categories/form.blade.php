@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.categories.index') }}">← Back to list</a>
@endsection

@section('content')
<form class="stack-form" method="post" enctype="multipart/form-data" action="{{ $category ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
    @csrf
    @if($category)
        @method('PUT')
    @endif
    <div class="form-grid">
        <label>
            Name
            <input type="text" name="name" required value="{{ old('name', $category->name ?? '') }}">
        </label>
        <label>
            Slug
            <input type="text" name="slug" value="{{ old('slug', $category->slug ?? '') }}">
        </label>
        <label>
            Sort order
            <input type="number" name="sort_order" value="{{ old('sort_order', $category ? (string) $category->sort_order : '0') }}">
        </label>
    </div>

    <fieldset class="form-fieldset">
        <legend>Category image</legend>
        @include('partials.file-upload', [
            'name' => 'image',
            'previewUrl' => !empty($category?->image) ? \App\Support\PublicAssetUrl::to($category->image) : null,
        ])
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Catalog banner</legend>
        <p class="form-help">Wide banner displayed on this category's catalog page. When empty, the category image is used.</p>
        @include('partials.file-upload', [
            'name' => 'catalog_banner',
            'previewUrl' => !empty($category?->catalog_banner) ? \App\Support\PublicAssetUrl::to($category->catalog_banner) : null,
        ])
    </fieldset>

    <label>
        Description
        <textarea name="description" rows="3">{{ old('description', $category->description ?? '') }}</textarea>
    </label>

    <fieldset class="form-fieldset">
        <legend>SEO</legend>
        <label>
            Meta title
            <input type="text" name="meta_title" value="{{ old('meta_title', $category->meta_title ?? '') }}">
        </label>
        <label>
            Meta description
            <textarea name="meta_description" rows="2">{{ old('meta_description', $category->meta_description ?? '') }}</textarea>
        </label>
    </fieldset>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">{{ $category ? 'Save changes' : 'Create category' }}</button>
        <a class="btn-admin" href="{{ route('admin.categories.index') }}">Cancel</a>
    </div>
</form>
@endsection
