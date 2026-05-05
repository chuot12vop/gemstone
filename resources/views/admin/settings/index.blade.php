@extends('layouts.admin')

@section('content')
<form class="stack-form" method="post" enctype="multipart/form-data" action="{{ route('admin.settings.save') }}">
    @csrf
    @if($errors->any())
        <div class="admin-banner admin-banner--err" role="alert" style="margin-bottom:12px;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="form-grid">
        <label>
            Site name
            <input type="text" name="site_name" required value="{{ old('site_name', $settings['site_name'] ?? config('app.name')) }}">
        </label>
    </div>

    <fieldset class="form-fieldset">
        <legend>Website logo</legend>
        <div id="site-logo-dropzone" style="margin-top:10px;padding:18px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
            <strong>Drop logo image here</strong><br>
            <small>or click to choose 1 image</small>
        </div>
        <div style="margin-top:10px;">
            <img id="site-logo-preview" src="{{ old('site_logo_preview', \App\Support\PublicAssetUrl::to($settings['site_logo']) ?: asset('assets/img/placeholder.svg')) }}" alt="Site logo preview" width="160" height="160" style="object-fit:cover;border:1px solid #d7dbe2;border-radius:8px;background:#fff;">
        </div>
        <input id="site-logo-input" class="display-none" type="file" name="site_logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        @error('site_logo')
            <p style="margin:8px 0 0;color:#b33a3a;">{{ $message }}</p>
        @enderror
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Homepage banner</legend>
        <div id="home-banner-dropzone" style="margin-top:10px;padding:18px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
            <strong>Drop banner image here</strong><br>
            <small>or click to choose 1 image</small>
        </div>
        <div style="margin-top:10px;">
            <img id="home-banner-preview" src="{{ old('home_banner_preview', \App\Support\PublicAssetUrl::to($settings['home_banner']) ?: asset('assets/img/placeholder.svg')) }}" alt="Home banner preview" width="320" height="180" style="object-fit:cover;border:1px solid #d7dbe2;border-radius:8px;background:#fff;">
        </div>
        <input id="home-banner-input" class="display-none" type="file" name="home_banner" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        @error('home_banner')
            <p style="margin:8px 0 0;color:#b33a3a;">{{ $message }}</p>
        @enderror
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Policies</legend>
        <label>
            Security policy
            <textarea name="security_policy" rows="8">{{ old('security_policy', $settings['security_policy'] ?? '') }}</textarea>
        </label>
        <label>
            Privacy policy
            <textarea name="privacy_policy" rows="8">{{ old('privacy_policy', $settings['privacy_policy'] ?? '') }}</textarea>
        </label>
        <label>
            Retail policy
            <textarea name="retail_policy" rows="8">{{ old('retail_policy', $settings['retail_policy'] ?? '') }}</textarea>
        </label>
    </fieldset>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">Save settings</button>
    </div>
</form>

<script>
(() => {
    const setupSingleImageDropzone = (inputId, previewId, zoneId) => {
        const fileInput = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const dropzone = document.getElementById(zoneId);
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
            dropzone.addEventListener(eventName, () => dropzone.style.borderColor = '#1f6feb');
        });
        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, () => dropzone.style.borderColor = '#c8d1dc');
        });
        dropzone.addEventListener('drop', (event) => {
            const files = event.dataTransfer ? Array.from(event.dataTransfer.files).filter((file) => file.type.startsWith('image/')) : [];
            if (files.length === 0) return;
            setFile(files[0]);
        });
        dropzone.addEventListener('click', () => fileInput.click());
    };

    setupSingleImageDropzone('site-logo-input', 'site-logo-preview', 'site-logo-dropzone');
    setupSingleImageDropzone('home-banner-input', 'home-banner-preview', 'home-banner-dropzone');
})();
</script>
@endsection
