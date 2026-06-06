@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.brands.index') }}">← Back to list</a>
@endsection

@section('content')
<form class="stack-form" method="post" enctype="multipart/form-data" action="{{ $brand ? route('admin.brands.update', $brand) : route('admin.brands.store') }}">
    @csrf
    @if($brand)
        @method('PUT')
    @endif
    <div class="form-grid">
        <label>
            Name
            <input type="text" name="name" required value="{{ old('name', $brand->name ?? '') }}">
        </label>
        <label>
            Slug
            <input type="text" name="slug" value="{{ old('slug', $brand->slug ?? '') }}" placeholder="auto from name">
        </label>
        <label>
            Sort order
            <input type="number" name="sort_order" value="{{ old('sort_order', $brand ? (string) $brand->sort_order : '0') }}">
        </label>
    </div>

    <fieldset class="form-fieldset">
        <legend>Logo / image</legend>
        @include('partials.file-upload', [
            'name' => 'image',
            'previewUrl' => !empty($brand?->image) ? \App\Support\PublicAssetUrl::to($brand->image) : null,
        ])
    </fieldset>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">{{ $brand ? 'Save changes' : 'Create brand' }}</button>
        <a class="btn-admin" href="{{ route('admin.brands.index') }}">Cancel</a>
    </div>
</form>
@endsection
