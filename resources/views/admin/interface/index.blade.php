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
                            @include('partials.file-upload', [
                                'name' => "slides[{$i}][image]",
                                'dataName' => 'slide-image-desktop',
                                'label' => 'Desktop image *',
                                'hint' => 'Wide banner for tablets and desktop (recommended ~1400×788).',
                                'dropTitle' => 'Drop desktop image',
                                'dropHint' => 'or click to choose',
                                'previewUrl' => !empty($slide['image']) ? \App\Support\PublicAssetUrl::to($slide['image']) : null,
                                'previewWidth' => 280,
                                'previewHeight' => 158,
                                'clearTargets' => '.js-existing-image',
                            ])
                            @error('slides.'.$i.'.image')
                                <p style="margin:8px 0 0;color:#b33a3a;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            @include('partials.file-upload', [
                                'name' => "slides[{$i}][image_mobile]",
                                'dataName' => 'slide-image-mobile',
                                'label' => 'Mobile image',
                                'hint' => 'Portrait banner for phones (optional; uses desktop image if empty).',
                                'dropTitle' => 'Drop mobile image',
                                'dropHint' => 'or click to choose',
                                'previewUrl' => !empty($slide['image_mobile']) ? \App\Support\PublicAssetUrl::to($slide['image_mobile']) : null,
                                'previewWidth' => 280,
                                'previewHeight' => 160,
                                'clearTargets' => '.js-existing-image-mobile',
                            ])
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

    <fieldset class="form-fieldset" style="margin-top:24px;">
        <legend>Home section backgrounds</legend>
        <p style="margin:0 0 12px;color:#5c6470;font-size:0.95rem;">Set a background color and optional background image for each homepage section. Hero slider is configured separately above.</p>
        <div class="form-grid">
            @foreach($sectionKeys as $sectionKey)
                @php($style = $sectionStyles[$sectionKey] ?? ['background_color' => '#ffffff', 'background_image' => ''])
                <div class="js-section-style-row form-fieldset" style="grid-column:1 / -1;margin-top:14px;padding:14px;border:1px solid #e2e6ec;border-radius:10px;background:#fff;">
                    <h3 style="margin:0 0 12px;font-size:1rem;">{{ $sectionLabels[$sectionKey] ?? $sectionKey }}</h3>
                    <input type="hidden" class="js-section-existing-image" name="sections[{{ $sectionKey }}][existing_background_image]" value="{{ old('sections.'.$sectionKey.'.existing_background_image', $style['background_image'] ?? '') }}">
                    <div class="form-grid">
                        <label>
                            Background color
                            <input type="color" class="js-section-color" name="sections[{{ $sectionKey }}][background_color]" value="{{ old('sections.'.$sectionKey.'.background_color', $style['background_color'] ?? '#ffffff') }}">
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;margin-top:1.6rem;">
                            <input type="checkbox" class="js-section-remove-image" name="sections[{{ $sectionKey }}][remove_background_image]" value="1" @checked(old('sections.'.$sectionKey.'.remove_background_image'))>
                            Remove background image
                        </label>
                    </div>
                    <div style="margin-top:12px;">
                        @include('partials.file-upload', [
                            'name' => "sections[{$sectionKey}][background_image]",
                            'label' => 'Background image',
                            'hint' => 'Optional decorative background for this section.',
                            'dropTitle' => 'Drop background image',
                            'dropHint' => 'or click to choose',
                            'previewUrl' => !empty($style['background_image']) ? \App\Support\PublicAssetUrl::to($style['background_image']) : null,
                            'previewWidth' => 280,
                            'previewHeight' => 120,
                            'clearTargets' => '.js-section-existing-image,.js-section-remove-image',
                        ])
                        @error('sections.'.$sectionKey.'.background_image')
                            <p style="margin:8px 0 0;color:#b33a3a;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>
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
                @include('partials.file-upload', [
                    'dataName' => 'slide-image-desktop',
                    'label' => 'Desktop image *',
                    'hint' => 'Wide banner for tablets and desktop (recommended ~1400×788).',
                    'dropTitle' => 'Drop desktop image',
                    'dropHint' => 'or click to choose',
                    'previewWidth' => 280,
                    'previewHeight' => 158,
                    'clearTargets' => '.js-existing-image',
                ])
            </div>
            <div>
                @include('partials.file-upload', [
                    'dataName' => 'slide-image-mobile',
                    'label' => 'Mobile image',
                    'hint' => 'Portrait banner for phones (optional; uses desktop image if empty).',
                    'dropTitle' => 'Drop mobile image',
                    'dropHint' => 'or click to choose',
                    'previewWidth' => 280,
                    'previewHeight' => 160,
                    'clearTargets' => '.js-existing-image-mobile',
                ])
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
            const fileDesktop = row.querySelector('[data-name="slide-image-desktop"]');
            const fileMobile = row.querySelector('[data-name="slide-image-mobile"]');
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

    addButton.addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.js-slide-row');
        if (!row) return;
        list.appendChild(row);
        updateSlideNames();
        document.dispatchEvent(new CustomEvent('file-upload:init', { detail: { root: row } }));
    });

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
<script>
(() => {
    const placeholderSrc = @json(asset('assets/img/placeholder.svg'));

    document.querySelectorAll('.js-section-style-row').forEach((row) => {
        const removeCheckbox = row.querySelector('.js-section-remove-image');
        if (!(removeCheckbox instanceof HTMLInputElement)) return;

        removeCheckbox.addEventListener('change', () => {
            if (!removeCheckbox.checked) return;
            const upload = row.querySelector('[data-file-upload]');
            const fileInput = upload?.querySelector('[data-file-upload-input]');
            const previewImg = upload?.querySelector('[data-file-upload-preview-img]');
            const existing = row.querySelector('.js-section-existing-image');
            if (fileInput instanceof HTMLInputElement) fileInput.value = '';
            if (existing instanceof HTMLInputElement) existing.value = '';
            if (previewImg instanceof HTMLImageElement) previewImg.src = placeholderSrc;
        });
    });
})();
</script>
@endsection
