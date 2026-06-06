@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.posts.index') }}">← Back to list</a>
@endsection

@section('content')
<form class="stack-form" method="post" enctype="multipart/form-data" action="{{ $post ? route('admin.posts.update', $post) : route('admin.posts.store') }}">
    @csrf
    @if($post)
        @method('PUT')
    @endif
    <div class="form-grid">
        <label>
            Title
            <input type="text" name="title" required value="{{ old('title', $post->title ?? '') }}">
        </label>
        <label>
            Slug
            <input type="text" name="slug" value="{{ old('slug', $post->slug ?? '') }}" placeholder="auto from title">
        </label>
        <label>
            Published at
            <input type="datetime-local" name="published_at" value="{{ old('published_at', $post && $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '') }}">
        </label>
        <label>
            Sort order
            <input type="number" name="sort_order" value="{{ old('sort_order', $post ? (string) $post->sort_order : '0') }}">
        </label>
    </div>
    <label>
        Excerpt
        <textarea name="excerpt" rows="2">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
    </label>
    <label>
        Body
        <textarea id="post-body" class="js-rich-text" name="body" rows="8" data-rich-height="360">{{ old('body', $post->body ?? '') }}</textarea>
    </label>
    @include('partials.file-upload', [
        'name' => 'image',
        'label' => 'Cover image',
        'previewUrl' => ($post && $post->image) ? \App\Support\PublicAssetUrl::to($post->image) : null,
        'previewWidth' => 160,
        'previewHeight' => 160,
    ])
    <label class="checkbox">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $post ? $post->is_active : true))>
        Published on storefront
    </label>
    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">{{ $post ? 'Save changes' : 'Create article' }}</button>
    </div>
</form>

@include('admin.partials.tinymce-init')
@endsection
