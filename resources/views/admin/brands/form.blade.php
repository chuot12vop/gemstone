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
        <div id="brand-image-dropzone" style="margin-top:10px;padding:18px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
            <strong>Drop image here</strong><br>
            <small>or click to choose 1 image</small>
        </div>
        <div style="margin-top:10px;">
            <img id="brand-image-preview" src="{{ !empty($brand?->image) ? \App\Support\PublicAssetUrl::to($brand->image) : asset('assets/img/placeholder.svg') }}" alt="Brand image preview" width="200" height="200" style="object-fit:cover;border:1px solid #d7dbe2;border-radius:8px;background:#fff;">
        </div>
        <input id="brand-image-input" class="display-none" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
    </fieldset>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">{{ $brand ? 'Save changes' : 'Create brand' }}</button>
        <a class="btn-admin" href="{{ route('admin.brands.index') }}">Cancel</a>
    </div>
</form>
<script>
(() => {
    const fileInput = document.getElementById('brand-image-input');
    const preview = document.getElementById('brand-image-preview');
    const dropzone = document.getElementById('brand-image-dropzone');
    if (!(fileInput instanceof HTMLInputElement) || !(preview instanceof HTMLImageElement) || !(dropzone instanceof HTMLElement)) {
        return;
    }

    const setFile = (file) => {
        if (!file || !file.type.startsWith('image/')) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        const url = URL.createObjectURL(file);
        preview.src = url;
        preview.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
    };

    fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        setFile(file);
    });

    const preventDefaults = (event) => {
        event.preventDefault();
        event.stopPropagation();
    };
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, preventDefaults);
    });
    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, () => { dropzone.style.borderColor = '#1f6feb'; });
    });
    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, () => { dropzone.style.borderColor = '#c8d1dc'; });
    });
    dropzone.addEventListener('drop', (event) => {
        const files = event.dataTransfer ? Array.from(event.dataTransfer.files).filter((file) => file.type.startsWith('image/')) : [];
        if (files.length === 0) return;
        setFile(files[0]);
    });
    dropzone.addEventListener('click', () => fileInput.click());
})();
</script>
@endsection
