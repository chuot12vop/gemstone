@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.pages.index') }}">← Back to list</a>
@endsection

@section('content')
<form class="stack-form" method="post" enctype="multipart/form-data" action="{{ $page ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
    @csrf
    @if($page)
        @method('PUT')
    @endif
    <div class="form-grid">
        <label>
            Title
            <input type="text" name="title" required value="{{ old('title', $page->title ?? '') }}">
        </label>
        <label>
            Slug
            <input type="text" name="slug" value="{{ old('slug', $page->slug ?? '') }}" placeholder="auto from title">
        </label>
        <label>
            Sort order
            <input type="number" name="sort_order" value="{{ old('sort_order', $page ? (string) $page->sort_order : '0') }}">
            <span class="form-hint">Lower numbers appear first on the homepage Stories block.</span>
        </label>
    </div>
    <label>
        Description
        <textarea name="description" rows="4">{{ old('description', $page->description ?? '') }}</textarea>
    </label>
    <label>
        Content
        <textarea id="page-content" class="js-rich-text" name="content" rows="8" data-rich-height="360">{{ old('content', $page->content ?? '') }}</textarea>
    </label>
    @include('partials.file-upload', [
        'name' => 'image',
        'label' => 'Image',
        'previewUrl' => ($page && $page->image) ? \App\Support\PublicAssetUrl::to($page->image) : null,
        'previewWidth' => 160,
        'previewHeight' => 160,
    ])
    <label class="checkbox">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $page ? $page->is_active : true))>
        Active on storefront
    </label>
    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">{{ $page ? 'Save changes' : 'Create page' }}</button>
    </div>
</form>

@include('admin.partials.tinymce-init')
@endsection
