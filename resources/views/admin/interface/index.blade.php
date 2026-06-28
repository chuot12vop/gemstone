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
        <legend>Home product sections</legend>
        <p style="margin:0 0 12px;color:#5c6470;font-size:0.95rem;">Choose a banner image and up to 6 products for Best Sellers and New Arrivals. When no products are selected, the homepage uses the default product list.</p>
        <div class="form-grid">
            @foreach($productSections as $sectionKey => $section)
                @php($selectedProductIds = collect(old('product_sections.'.$sectionKey.'.product_ids', $section['product_ids'] ?? []))->map(fn ($id) => (int) $id)->all())
                @php($productsById = $products->keyBy('id'))
                <div class="js-product-section-row form-fieldset interface-section-row">
                    <h3 class="interface-section-row__title">{{ $productSectionLabels[$sectionKey] ?? $sectionKey }}</h3>
                    <input type="hidden" class="js-product-section-existing-image" name="product_sections[{{ $sectionKey }}][existing_banner_image]" value="{{ old('product_sections.'.$sectionKey.'.existing_banner_image', $section['banner_image'] ?? '') }}">
                    <div class="form-grid interface-section-controls">
                        <div class="home-product-picker upsell-picker" data-home-product-picker data-section-key="{{ $sectionKey }}" data-search-url="{{ route('admin.products.search') }}" style="grid-column:1 / -1;">
                            <label class="upsell-picker__search-label">
                                Products shown
                                <input type="search" class="upsell-picker__search" placeholder="Type to search products..." autocomplete="off" data-home-product-search>
                            </label>
                            <div class="upsell-picker__results" data-home-product-results hidden></div>
                            <p class="upsell-picker__empty" data-home-product-status @if(count($selectedProductIds) < 6) hidden @endif>Maximum 6 products selected.</p>
                            <div class="upsell-picker__selected" data-home-product-selected>
                                @foreach($selectedProductIds as $selectedProductId)
                                    @php($selectedProduct = $productsById->get($selectedProductId))
                                    @if($selectedProduct)
                                        @php($selectedThumb = $selectedProduct->thumbnail ?: $selectedProduct->image)
                                        <div class="upsell-picker__row home-product-picker__row" data-home-product-row data-product-id="{{ $selectedProduct->id }}">
                                            <input type="hidden" name="product_sections[{{ $sectionKey }}][product_ids][]" value="{{ $selectedProduct->id }}">
                                            <div class="upsell-picker__product">
                                                @if($selectedThumb)
                                                    <img src="{{ \App\Support\PublicAssetUrl::to($selectedThumb) }}" alt="" width="40" height="40">
                                                @endif
                                                <span>{{ $selectedProduct->name }}</span>
                                            </div>
                                            <button type="button" class="btn-admin btn-admin--small" data-home-product-up>Move up</button>
                                            <button type="button" class="btn-admin btn-admin--small" data-home-product-down>Move down</button>
                                            <button type="button" class="btn-admin btn-admin--small btn-admin--danger" data-home-product-remove>Remove</button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <label class="interface-checkbox interface-checkbox--remove-bg">
                            <input type="checkbox" class="js-product-section-remove-image" name="product_sections[{{ $sectionKey }}][remove_banner_image]" value="1" @checked(old('product_sections.'.$sectionKey.'.remove_banner_image'))>
                            Remove banner image
                        </label>
                    </div>
                    <div class="interface-section-upload">
                        @include('partials.file-upload', [
                            'name' => "product_sections[{$sectionKey}][banner_image]",
                            'label' => 'Banner image',
                            'hint' => 'Wide banner displayed above this product section.',
                            'dropTitle' => 'Drop banner image',
                            'dropHint' => 'or click to choose',
                            'previewUrl' => !empty($section['banner_image']) ? \App\Support\PublicAssetUrl::to($section['banner_image']) : null,
                            'previewWidth' => 280,
                            'previewHeight' => 120,
                            'clearTargets' => '.js-product-section-existing-image,.js-product-section-remove-image',
                        ])
                        @error('product_sections.'.$sectionKey.'.banner_image')
                            <p style="margin:8px 0 0;color:#b33a3a;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>
    </fieldset>

    <fieldset class="form-fieldset" style="margin-top:24px;">
        <legend>Home section backgrounds</legend>
        <p style="margin:0 0 12px;color:#5c6470;font-size:0.95rem;">Set a background color and optional background image for each homepage section. Hero slider is configured separately above.</p>
        <div class="form-grid">
            @foreach($sectionKeys as $sectionKey)
                @php($style = $sectionStyles[$sectionKey] ?? ['background_color' => '#ffffff', 'background_image' => ''])
                <div class="js-section-style-row form-fieldset interface-section-row">
                    <h3 class="interface-section-row__title">{{ $sectionLabels[$sectionKey] ?? $sectionKey }}</h3>
                    <input type="hidden" class="js-section-existing-image" name="sections[{{ $sectionKey }}][existing_background_image]" value="{{ old('sections.'.$sectionKey.'.existing_background_image', $style['background_image'] ?? '') }}">
                    <div class="form-grid interface-section-controls">
                        <label>
                            Background color
                            <input type="color" class="js-section-color" name="sections[{{ $sectionKey }}][background_color]" value="{{ old('sections.'.$sectionKey.'.background_color', $style['background_color'] ?? '#ffffff') }}">
                        </label>
                        <label class="interface-checkbox interface-checkbox--remove-bg">
                            <input type="checkbox" class="js-section-remove-image" name="sections[{{ $sectionKey }}][remove_background_image]" value="1" @checked(old('sections.'.$sectionKey.'.remove_background_image'))>
                            Remove background image
                        </label>
                    </div>
                    <div class="interface-section-upload">
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

    document.querySelectorAll('[data-home-product-picker]').forEach((root) => {
        const searchUrl = root.getAttribute('data-search-url') || '';
        const sectionKey = root.getAttribute('data-section-key') || '';
        const searchInput = root.querySelector('[data-home-product-search]');
        const results = root.querySelector('[data-home-product-results]');
        const selected = root.querySelector('[data-home-product-selected]');
        const status = root.querySelector('[data-home-product-status]');
        const maxProducts = 6;
        if (!(searchInput instanceof HTMLInputElement) || !results || !selected || sectionKey === '') return;

        let debounceTimer = null;

        const selectedIds = () => new Set(
            Array.from(selected.querySelectorAll('[data-home-product-row]'))
                .map((row) => parseInt(row.getAttribute('data-product-id') || '0', 10))
                .filter((id) => id > 0)
        );

        const selectedCount = () => selected.querySelectorAll('[data-home-product-row]').length;

        const updateStatus = () => {
            if (!status) return;
            const isFull = selectedCount() >= maxProducts;
            status.hidden = !isFull;
            status.textContent = isFull ? 'Maximum 6 products selected.' : '';
        };

        const hideResults = () => {
            results.hidden = true;
            results.innerHTML = '';
        };

        const renderEmpty = (message) => {
            const empty = document.createElement('p');
            empty.className = 'upsell-picker__empty';
            empty.textContent = message;
            results.replaceChildren(empty);
            results.hidden = false;
        };

        const renderProductRow = (product) => {
            const row = document.createElement('div');
            row.className = 'upsell-picker__row home-product-picker__row';
            row.setAttribute('data-home-product-row', '');
            row.setAttribute('data-product-id', String(product.id));

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = `product_sections[${sectionKey}][product_ids][]`;
            hidden.value = String(product.id);

            const productWrap = document.createElement('div');
            productWrap.className = 'upsell-picker__product';
            if (product.thumbnail) {
                const img = document.createElement('img');
                img.src = product.thumbnail;
                img.alt = '';
                img.width = 40;
                img.height = 40;
                productWrap.appendChild(img);
            }
            const name = document.createElement('span');
            name.textContent = product.name || `#${product.id}`;
            productWrap.appendChild(name);

            const up = document.createElement('button');
            up.type = 'button';
            up.className = 'btn-admin btn-admin--small';
            up.setAttribute('data-home-product-up', '');
            up.textContent = 'Move up';

            const down = document.createElement('button');
            down.type = 'button';
            down.className = 'btn-admin btn-admin--small';
            down.setAttribute('data-home-product-down', '');
            down.textContent = 'Move down';

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn-admin btn-admin--small btn-admin--danger';
            remove.setAttribute('data-home-product-remove', '');
            remove.textContent = 'Remove';

            row.append(hidden, productWrap, up, down, remove);
            return row;
        };

        const addProduct = (product) => {
            const id = parseInt(String(product.id || '0'), 10);
            if (id <= 0 || selectedIds().has(id)) return;
            if (selectedCount() >= maxProducts) {
                renderEmpty('Maximum 6 products selected.');
                updateStatus();
                return;
            }
            selected.appendChild(renderProductRow({ ...product, id }));
            searchInput.value = '';
            hideResults();
            updateStatus();
            searchInput.focus();
        };

        const runSearch = async () => {
            const q = searchInput.value.trim();
            if (q === '') {
                hideResults();
                return;
            }
            if (selectedCount() >= maxProducts) {
                renderEmpty('Maximum 6 products selected.');
                updateStatus();
                return;
            }

            const response = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!response.ok) {
                renderEmpty('Could not search products.');
                return;
            }

            const ids = selectedIds();
            const matches = (await response.json()).filter((item) => !ids.has(parseInt(String(item.id || '0'), 10)));
            if (matches.length === 0) {
                renderEmpty('No products found.');
                return;
            }

            results.innerHTML = '';
            matches.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'upsell-picker__result';
                if (item.thumbnail) {
                    const img = document.createElement('img');
                    img.src = item.thumbnail;
                    img.alt = '';
                    img.width = 32;
                    img.height = 32;
                    btn.appendChild(img);
                }
                const name = document.createElement('span');
                name.textContent = item.name || `#${item.id}`;
                btn.appendChild(name);
                btn.addEventListener('click', () => addProduct(item));
                results.appendChild(btn);
            });
            results.hidden = false;
        };

        searchInput.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(runSearch, 200);
        });

        selected.addEventListener('click', (event) => {
            const target = event.target instanceof HTMLElement ? event.target : null;
            if (!target) return;
            const row = target.closest('[data-home-product-row]');
            if (!row) return;

            if (target.closest('[data-home-product-remove]')) {
                row.remove();
                updateStatus();
                return;
            }
            if (target.closest('[data-home-product-up]')) {
                const previous = row.previousElementSibling;
                if (previous) selected.insertBefore(row, previous);
                return;
            }
            if (target.closest('[data-home-product-down]')) {
                const next = row.nextElementSibling;
                if (next) selected.insertBefore(next, row);
            }
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Node) || root.contains(event.target)) return;
            hideResults();
        });

        updateStatus();
    });

    document.querySelectorAll('.js-product-section-row').forEach((row) => {
        const removeCheckbox = row.querySelector('.js-product-section-remove-image');
        if (!(removeCheckbox instanceof HTMLInputElement)) return;

        removeCheckbox.addEventListener('change', () => {
            if (!removeCheckbox.checked) return;
            const upload = row.querySelector('[data-file-upload]');
            const fileInput = upload?.querySelector('[data-file-upload-input]');
            const previewImg = upload?.querySelector('[data-file-upload-preview-img]');
            const existing = row.querySelector('.js-product-section-existing-image');
            if (fileInput instanceof HTMLInputElement) fileInput.value = '';
            if (existing instanceof HTMLInputElement) existing.value = '';
            if (previewImg instanceof HTMLImageElement) previewImg.src = placeholderSrc;
        });
    });

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
