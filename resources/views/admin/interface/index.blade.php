@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.settings.index') }}">← System settings</a>
    <a class="btn-admin" href="{{ route('admin.dashboard') }}">Dashboard</a>
@endsection

@section('module-meta')
    Configure the storefront home hero slideshow: desktop and mobile images, title, content, and optional category link for each slide.
@endsection

@section('content')
<form id="interface-banner-form" class="stack-form" method="post" enctype="multipart/form-data" action="{{ route('admin.interface.save') }}">
    @csrf
    @if($errors->any())
        <div class="admin-banner admin-banner--err" role="alert" style="margin-bottom:12px;">
            {{ $errors->first() }}
        </div>
    @endif

    <fieldset class="form-fieldset">
        <legend>Home banner slides</legend>
        <p style="margin:0 0 12px;color:#5c6470;font-size:0.95rem;">Each slide needs a desktop image. Upload a separate mobile image for phones (optional — desktop image is used when blank). Choose a category so the whole banner links to that catalog view (or leave blank for the full product list).</p>
        <div id="slides-list">
            @foreach($slides as $i => $slide)
                <div class="js-slide-row form-fieldset" style="margin-top:14px;padding:14px;border:1px solid #e2e6ec;border-radius:10px;background:#fff;">
                    <input type="hidden" class="js-existing-image" name="slides[{{ $i }}][existing_image]" value="{{ old('slides.'.$i.'.existing_image', $slide['image'] ?? '') }}">
                    <input type="hidden" class="js-existing-image-mobile" name="slides[{{ $i }}][existing_image_mobile]" value="{{ old('slides.'.$i.'.existing_image_mobile', $slide['image_mobile'] ?? '') }}">
                    <div class="form-grid">
                        <label style="grid-column: 1 / -1;">
                            Title
                            <input type="text" class="js-slide-title" name="slides[{{ $i }}][title]" value="{{ old('slides.'.$i.'.title', $slide['title'] ?? '') }}" placeholder="e.g. Vitality & Balance">
                        </label>
                        <label style="grid-column: 1 / -1;">
                            Content
                            <textarea class="js-slide-content" name="slides[{{ $i }}][content]" rows="3" placeholder="Short supporting text">{{ old('slides.'.$i.'.content', $slide['content'] ?? '') }}</textarea>
                        </label>
                        <label style="grid-column: 1 / -1;">
                            Link to category
                            <select class="js-slide-category" name="slides[{{ $i }}][category_id]">
                                <option value="" @selected((int) old('slides.'.$i.'.category_id', $slide['category_id'] ?? 0) === 0)>— Full catalog (no category filter) —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected((int) old('slides.'.$i.'.category_id', $slide['category_id'] ?? 0) === $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="form-grid" style="margin-top:12px;gap:16px;">
                        <div>
                            <p style="margin:0 0 6px;font-weight:600;font-size:0.9rem;">Desktop image <span style="color:#b33a3a;">*</span></p>
                            <p style="margin:0 0 8px;color:#5c6470;font-size:0.85rem;">Wide banner for tablets and desktop (recommended ~1400×788).</p>
                            <div class="js-slide-dropzone" data-image-role="desktop" style="padding:14px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
                                <strong>Drop desktop image</strong><br>
                                <small>or click to choose</small>
                            </div>
                            <div style="margin-top:10px;">
                                <img class="js-slide-preview" data-image-role="desktop" src="{{ !empty($slide['image']) ? \App\Support\PublicAssetUrl::to($slide['image']) : asset('assets/img/placeholder.svg') }}" alt="Desktop preview" width="280" height="158" style="object-fit:cover;border:1px solid #d7dbe2;border-radius:8px;background:#fff;">
                            </div>
                            <input class="display-none js-slide-file" data-image-role="desktop" type="file" name="slides[{{ $i }}][image]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            @error('slides.'.$i.'.image')
                                <p style="margin:8px 0 0;color:#b33a3a;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <p style="margin:0 0 6px;font-weight:600;font-size:0.9rem;">Mobile image</p>
                            <p style="margin:0 0 8px;color:#5c6470;font-size:0.85rem;">Portrait banner for phones (optional; uses desktop image if empty).</p>
                            <div class="js-slide-dropzone" data-image-role="mobile" style="padding:14px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
                                <strong>Drop mobile image</strong><br>
                                <small>or click to choose</small>
                            </div>
                            <div style="margin-top:10px;">
                                <img class="js-slide-preview" data-image-role="mobile" src="{{ !empty($slide['image_mobile']) ? \App\Support\PublicAssetUrl::to($slide['image_mobile']) : asset('assets/img/placeholder.svg') }}" alt="Mobile preview" width="280" height="160" style="object-fit:cover;border:1px solid #d7dbe2;border-radius:8px;background:#fff;">
                            </div>
                            <input class="display-none js-slide-file" data-image-role="mobile" type="file" name="slides[{{ $i }}][image_mobile]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            @error('slides.'.$i.'.image_mobile')
                                <p style="margin:8px 0 0;color:#b33a3a;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="form-actions" style="margin-top:12px;">
                        <button class="btn-admin" type="button" data-action="remove-slide">Remove slide</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button class="btn-admin" type="button" id="add-slide" style="margin-top:12px;">+ Add slide</button>
    </fieldset>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">Save interface</button>
    </div>
</form>

<template id="slide-row-template">
    <div class="js-slide-row form-fieldset" style="margin-top:14px;padding:14px;border:1px solid #e2e6ec;border-radius:10px;background:#fff;">
        <input type="hidden" class="js-existing-image" name="" value="">
        <input type="hidden" class="js-existing-image-mobile" name="" value="">
        <div class="form-grid">
            <label style="grid-column: 1 / -1;">
                Title
                <input type="text" class="js-slide-title" name="" value="" placeholder="e.g. Vitality & Balance">
            </label>
            <label style="grid-column: 1 / -1;">
                Content
                <textarea class="js-slide-content" name="" rows="3" placeholder="Short supporting text"></textarea>
            </label>
            <label style="grid-column: 1 / -1;">
                Link to category
                <select class="js-slide-category" name="">
                    <option value="">— Full catalog (no category filter) —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="form-grid" style="margin-top:12px;gap:16px;">
            <div>
                <p style="margin:0 0 6px;font-weight:600;font-size:0.9rem;">Desktop image <span style="color:#b33a3a;">*</span></p>
                <p style="margin:0 0 8px;color:#5c6470;font-size:0.85rem;">Wide banner for tablets and desktop (recommended ~1400×788).</p>
                <div class="js-slide-dropzone" data-image-role="desktop" style="padding:14px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
                    <strong>Drop desktop image</strong><br>
                    <small>or click to choose</small>
                </div>
                <div style="margin-top:10px;">
                    <img class="js-slide-preview" data-image-role="desktop" src="{{ asset('assets/img/placeholder.svg') }}" alt="Desktop preview" width="280" height="158" style="object-fit:cover;border:1px solid #d7dbe2;border-radius:8px;background:#fff;">
                </div>
                <input class="display-none js-slide-file" data-image-role="desktop" type="file" name="" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>
            <div>
                <p style="margin:0 0 6px;font-weight:600;font-size:0.9rem;">Mobile image</p>
                <p style="margin:0 0 8px;color:#5c6470;font-size:0.85rem;">Portrait banner for phones (optional; uses desktop image if empty).</p>
                <div class="js-slide-dropzone" data-image-role="mobile" style="padding:14px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
                    <strong>Drop mobile image</strong><br>
                    <small>or click to choose</small>
                </div>
                <div style="margin-top:10px;">
                    <img class="js-slide-preview" data-image-role="mobile" src="{{ asset('assets/img/placeholder.svg') }}" alt="Mobile preview" width="160" height="280" style="object-fit:cover;border:1px solid #d7dbe2;border-radius:8px;background:#fff;">
                </div>
                <input class="display-none js-slide-file" data-image-role="mobile" type="file" name="" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>
        </div>
        <div class="form-actions" style="margin-top:12px;">
            <button class="btn-admin" type="button" data-action="remove-slide">Remove slide</button>
        </div>
    </div>
</template>

<script>
(() => {
    const list = document.getElementById('slides-list');
    const addButton = document.getElementById('add-slide');
    const template = document.getElementById('slide-row-template');
    const form = document.getElementById('interface-banner-form');
    if (!list || !addButton || !template || !form) return;

    const placeholderSrc = @json(asset('assets/img/placeholder.svg'));

    const updateSlideNames = () => {
        const rows = list.querySelectorAll('.js-slide-row');
        rows.forEach((row, index) => {
            const existing = row.querySelector('.js-existing-image');
            const existingMobile = row.querySelector('.js-existing-image-mobile');
            const title = row.querySelector('.js-slide-title');
            const content = row.querySelector('.js-slide-content');
            const category = row.querySelector('.js-slide-category');
            const fileDesktop = row.querySelector('.js-slide-file[data-image-role="desktop"]');
            const fileMobile = row.querySelector('.js-slide-file[data-image-role="mobile"]');
            if (existing instanceof HTMLInputElement) existing.setAttribute('name', `slides[${index}][existing_image]`);
            if (existingMobile instanceof HTMLInputElement) existingMobile.setAttribute('name', `slides[${index}][existing_image_mobile]`);
            if (title instanceof HTMLInputElement) title.setAttribute('name', `slides[${index}][title]`);
            if (content instanceof HTMLTextAreaElement) content.setAttribute('name', `slides[${index}][content]`);
            if (category instanceof HTMLSelectElement) category.setAttribute('name', `slides[${index}][category_id]`);
            if (fileDesktop instanceof HTMLInputElement) fileDesktop.setAttribute('name', `slides[${index}][image]`);
            if (fileMobile instanceof HTMLInputElement) fileMobile.setAttribute('name', `slides[${index}][image_mobile]`);
        });
    };

    const ensureOneRow = () => {
        if (list.querySelectorAll('.js-slide-row').length > 0) return;
        addButton.click();
    };

    const bindImageUpload = (row, role) => {
        const fileInput = row.querySelector(`.js-slide-file[data-image-role="${role}"]`);
        const preview = row.querySelector(`.js-slide-preview[data-image-role="${role}"]`);
        const dropzone = row.querySelector(`.js-slide-dropzone[data-image-role="${role}"]`);
        const existing = row.querySelector(role === 'desktop' ? '.js-existing-image' : '.js-existing-image-mobile');
        if (!(fileInput instanceof HTMLInputElement) || !(preview instanceof HTMLImageElement) || !(dropzone instanceof HTMLElement)) return;

        const setFile = (file) => {
            if (!file || !file.type.startsWith('image/')) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            if (existing instanceof HTMLInputElement) existing.value = '';
            const url = URL.createObjectURL(file);
            preview.src = url;
            preview.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
        };

        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
            if (file) setFile(file);
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
            const files = event.dataTransfer ? Array.from(event.dataTransfer.files).filter((f) => f.type.startsWith('image/')) : [];
            if (files.length === 0) return;
            setFile(files[0]);
        });
        dropzone.addEventListener('click', () => fileInput.click());
    };

    const bindRow = (row) => {
        bindImageUpload(row, 'desktop');
        bindImageUpload(row, 'mobile');
    };

    addButton.addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.js-slide-row');
        if (!row) return;
        list.appendChild(row);
        bindRow(row);
        updateSlideNames();
    });

    list.querySelectorAll('.js-slide-row').forEach((row) => bindRow(row));

    list.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.dataset.action !== 'remove-slide') return;
        const row = target.closest('.js-slide-row');
        if (!row) return;
        row.remove();
        updateSlideNames();
        ensureOneRow();
    });

    form.addEventListener('submit', () => {
        updateSlideNames();
    });

    updateSlideNames();
})();
</script>
@endsection
