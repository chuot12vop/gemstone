@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.certificates.index') }}">← Back to list</a>
@endsection

@section('content')
@if($errors->any())
    <div class="admin-banner admin-banner--err" role="alert" style="margin-bottom:1rem;">
        <ul style="margin:0;padding-left:1.2rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form class="stack-form" method="post" enctype="multipart/form-data" action="{{ $certificate ? route('admin.certificates.update', $certificate) : route('admin.certificates.store') }}" id="certificate-form">
    @csrf
    @if($certificate)
        @method('PUT')
    @endif

    <div class="form-grid">
        <label>
            Name
            <input type="text" name="name" required value="{{ old('name', $certificate->name ?? '') }}">
        </label>
        <label>
            Sort order
            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $certificate ? (string) $certificate->sort_order : '0') }}">
        </label>
    </div>

    <fieldset class="form-fieldset">
        <legend>Certificate image</legend>
        <div id="certificate-image-dropzone" style="margin-top:10px;padding:18px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
            <strong>Drop image here</strong><br>
            <small>or click to choose 1 image</small>
        </div>
        <div style="margin-top:10px;">
            <img id="certificate-image-preview"
                 src="{{ !empty($certificate?->image) ? \App\Support\PublicAssetUrl::to($certificate->image) : asset('assets/img/placeholder.svg') }}"
                 alt="Certificate preview"
                 width="200"
                 height="200"
                 style="object-fit:contain;border:1px solid #d7dbe2;border-radius:8px;background:#fff;">
        </div>
        <input id="certificate-image-input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="certificate-image-input">
        <p id="certificate-image-filename" class="certificate-image-filename" aria-live="polite">
            @if($certificate?->image)
                Current image on file. Choose a new file to replace it.
            @else
                No image selected yet.
            @endif
        </p>
        @error('image')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </fieldset>

    <label>
        Description
        <textarea name="description" rows="4">{{ old('description', $certificate->description ?? '') }}</textarea>
    </label>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">{{ $certificate ? 'Save changes' : 'Create certificate' }}</button>
        <a class="btn-admin" href="{{ route('admin.certificates.index') }}">Cancel</a>
    </div>
</form>
<script>
(() => {
    const fileInput = document.getElementById('certificate-image-input');
    const preview = document.getElementById('certificate-image-preview');
    const dropzone = document.getElementById('certificate-image-dropzone');
    const filenameEl = document.getElementById('certificate-image-filename');
    const isCreate = {{ $certificate ? 'false' : 'true' }};
    if (!(fileInput instanceof HTMLInputElement) || !(preview instanceof HTMLImageElement) || !(dropzone instanceof HTMLElement)) {
        return;
    }

    const setFile = (file) => {
        if (!file || !file.type.startsWith('image/')) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        if (filenameEl) {
            filenameEl.textContent = `Selected: ${file.name}`;
        }
        const url = URL.createObjectURL(file);
        preview.src = url;
        preview.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
    };

    fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (file) {
            setFile(file);
        }
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

    const form = document.getElementById('certificate-form');
    form?.addEventListener('submit', (event) => {
        if (!isCreate) {
            return;
        }
        const hasFile = fileInput.files && fileInput.files.length > 0;
        if (hasFile) {
            return;
        }
        event.preventDefault();
        if (filenameEl) {
            filenameEl.textContent = 'Please choose an image before saving.';
            filenameEl.style.color = '#b33a3a';
        }
        dropzone.style.borderColor = '#b33a3a';
        dropzone.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
})();
</script>
@endsection
