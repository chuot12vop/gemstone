@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.products.index') }}">← Back to list</a>
@endsection

@section('content')
@php
    $attributeRows = old('attributes');
    if (!is_array($attributeRows)) {
        $attributeRows = $product ? $product->productAttributes->map(fn ($item) => [
            'name' => $item->name,
            'value' => $item->value,
        ])->all() : [];
    }
    if (count($attributeRows) === 0) {
        $attributeRows = [['name' => '', 'value' => '']];
    }
@endphp
<form id="product-form" class="stack-form" method="post" enctype="multipart/form-data" action="{{ $product ? route('admin.products.update', $product) : route('admin.products.store') }}">
    @csrf
    @if($product)
        @method('PUT')
    @endif
    @if($brands->isEmpty())
        <p class="admin-banner admin-banner--err" role="alert">Add at least one brand before creating products.</p>
    @endif
    <div class="form-grid">
        <label>
            Name
            <input type="text" name="name" required value="{{ old('name', $product->name ?? '') }}">
        </label>
        <label>
            URL slug
            <input type="text" name="slug" placeholder="auto from name" value="{{ old('slug', $product->slug ?? '') }}">
        </label>
        <label>
            Brand
            <select name="brand_id" required>
                @foreach($brands as $b)
                    <option value="{{ $b->id }}" @selected((int) old('brand_id', $product->brand_id ?? 0) === $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Category
            <select name="category_id" required>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected((int) old('category_id', $product->category_id ?? 0) === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Price (USD)
            <input type="text" name="price_usd" required value="{{ old('price_usd', $product ? (string) $product->price_usd : '') }}">
        </label>
        <label>
            Stock
            <input type="number" name="stock" min="0" value="{{ old('stock', $product ? (string) $product->stock : '0') }}">
            <small>Synced from variants on save.</small>
        </label>

    </div>

    @php
        $variantRows = old('variants');
        if (! is_array($variantRows) && $product) {
            $variantRows = $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'option_color' => $v->option_color,
                'swatch_color' => $v->swatch_color,
                'option_size' => $v->option_size,
                'price_usd' => (string) $v->price_usd,
                'compare_at_price_usd' => $v->compare_at_price_usd !== null ? (string) $v->compare_at_price_usd : '',
                'stock' => (string) $v->stock,
                'sku' => $v->sku,
                'is_default' => $v->is_default,
                'is_active' => $v->is_active,
                'image' => $v->image,
                'image_hover' => $v->image_hover,
                'hover_images' => $v->hoverImages->map(fn ($img) => ['id' => $img->id, 'path' => $img->path])->all(),
            ])->all();
        }
        if (! is_array($variantRows) || count($variantRows) === 0) {
            $variantRows = [[
                'option_color' => '',
                'option_size' => '',
                'price_usd' => old('price_usd', $product ? (string) $product->price_usd : '0'),
                'compare_at_price_usd' => '',
                'stock' => old('stock', $product ? (string) $product->stock : '0'),
                'sku' => '',
                'is_default' => true,
                'is_active' => true,
            ]];
        }
    @endphp
    <fieldset class="form-fieldset">
        <legend>Variants</legend>
        <p class="admin-hint">Each row is a purchasable variant (color, size, price, stock). Exactly one must be default.</p>
        <div id="variant-list">
            @foreach($variantRows as $i => $row)
                <div class="form-grid form-grid--variants js-variant-row" style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #e5e7eb;">
                    @if(!empty($row['id']))
                        <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $row['id'] }}">
                    @endif
                    <label>
                        Color
                        @include('admin.partials.color-picker', [
                            'nameColor' => "variants[{$i}][option_color]",
                            'nameSwatch' => "variants[{$i}][swatch_color]",
                            'valueColor' => $row['option_color'] ?? '',
                            'valueSwatch' => $row['swatch_color'] ?? '',
                        ])
                        <small>Color name and swatch shown on storefront.</small>
                    </label>
                    <label>
                        Size
                        <input type="text" name="variants[{{ $i }}][option_size]" value="{{ $row['option_size'] ?? '' }}" placeholder="One Size">
                    </label>
                    <label>
                        Price (USD)
                        <input type="text" name="variants[{{ $i }}][price_usd]" required value="{{ $row['price_usd'] ?? '0' }}">
                    </label>
                    <label>
                        Compare at price
                        <input type="text" name="variants[{{ $i }}][compare_at_price_usd]" value="{{ $row['compare_at_price_usd'] ?? '' }}">
                    </label>
                    <label>
                        Stock
                        <input type="number" name="variants[{{ $i }}][stock]" min="0" required value="{{ $row['stock'] ?? '0' }}">
                    </label>
                    <label>
                        SKU
                        <input type="text" name="variants[{{ $i }}][sku]" value="{{ $row['sku'] ?? '' }}">
                    </label>
                    @include('partials.file-upload', [
                        'name' => "variants[{$i}][image]",
                        'label' => 'Front image',
                        'variant' => 'compact',
                        'previewUrl' => $row['image'] ?? null,
                    ])
                    <div>
                        @include('partials.file-upload', [
                            'name' => "variants[{$i}][hover_images][]",
                            'label' => 'Hover images (3–5)',
                            'hint' => 'Upload up to 5 hover/gallery images for this variant.',
                            'variant' => 'compact',
                            'multiple' => true,
                            'maxFiles' => 5,
                        ])
                        @if(!empty($row['hover_images']) && is_array($row['hover_images']))
                            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-top:0.5rem;">
                                @foreach($row['hover_images'] as $hoverImage)
                                    <label style="display:flex;flex-direction:column;align-items:center;gap:0.25rem;font-size:0.75rem;">
                                        <img src="{{ $hoverImage['path'] ?? '' }}" alt="" width="60" height="60" style="object-fit:cover;border-radius:6px;">
                                        <span>
                                            <input type="checkbox" name="variants[{{ $i }}][remove_hover_image_ids][]" value="{{ $hoverImage['id'] ?? '' }}">
                                            Remove
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @include('partials.file-upload', [
                        'name' => "variants[{$i}][image_hover]",
                        'label' => 'Legacy hover image',
                        'variant' => 'compact',
                        'previewUrl' => $row['image_hover'] ?? null,
                    ])
                    <label class="checkbox">
                        <input type="checkbox" name="variants[{{ $i }}][is_default]" value="1" @checked(!empty($row['is_default']))>
                        Default variant
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" name="variants[{{ $i }}][is_active]" value="1" @checked(!array_key_exists('is_active', $row) || !empty($row['is_active']))>
                        Active
                    </label>
                    <div class="form-actions">
                        <button class="btn-admin" type="button" data-action="remove-variant">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button class="btn-admin" type="button" id="add-variant">+ Add variant</button>
    </fieldset>

    <div class="form-grid">

    <fieldset class="form-fieldset">
        <legend>Thumbnail upload</legend>
        @include('partials.file-upload', [
            'name' => 'thumbnail',
            'dropTitle' => 'Drop thumbnail here',
            'previewUrl' => $product->thumbnail ?? ($product->image ?? null),
        ])
    </fieldset>

    @include('partials.file-upload', [
        'name' => 'images[]',
        'label' => 'Product gallery images',
        'hint' => 'When editing: upload new files to replace existing gallery images.',
        'dropTitle' => 'Drop gallery images here',
        'dropHint' => 'or click to choose multiple files',
        'multiple' => true,
        'inputId' => 'images-input',
    ])
    @if($product && $product->productImages->isNotEmpty())
        <div class="product-images-grid">
            @foreach($product->productImages as $galleryImage)
                <div class="product-image-item">
                    <img src="{{ $galleryImage->path }}" alt="Gallery" width="120" height="120" style="object-fit:scale-down;border-radius:8px;">
                </div>
            @endforeach
        </div>
    @endif

    <label>
        Short description
        <textarea name="short_description" rows="2">{{ old('short_description', $product->short_description ?? '') }}</textarea>
    </label>
    <div class="form-grid form-grid--2">
        <fieldset class="form-fieldset">
            <legend>Card sticker image</legend>
            @if($product && $product->sticker)
                <label class="checkbox">
                    <input type="checkbox" name="remove_sticker" value="1" @checked(old('remove_sticker'))>
                    Remove current sticker
                </label>
            @endif
            @include('partials.file-upload', [
                'name' => 'sticker',
                'label' => 'Upload sticker',
                'hint' => 'PNG/WebP with transparency recommended. Shown on the top-right of the product card (45°). Max 2MB.',
                'previewUrl' => ($product && $product->sticker) ? $product->sticker : null,
                'previewFit' => 'contain',
                'previewWidth' => 96,
                'previewHeight' => 96,
            ])
        </fieldset>
        <label>
            Discount %
            <input type="number" name="discount" min="0" max="100" step="0.01" placeholder="e.g. 20" value="{{ old('discount', $product->discount ?? '') }}">
            <small>Strikethrough original price + sale price on the card (unless a variant compare-at price is set).</small>
        </label>
    </div>
    <label>
        Card badge label
        <input type="text" name="card_badge_label" maxlength="50" placeholder="e.g. HOT, LIMITED" value="{{ old('card_badge_label', $product->card_badge_label ?? '') }}">
        <small>Black badge on the top-left of the product card image (e.g. SALE, HOT, LIMITED). Leave empty to hide.</small>
    </label>
    <label>
        Full description
        <textarea id="product-description" class="js-rich-text" name="description" rows="8" data-rich-height="360">{{ old('description', $product->description ?? '') }}</textarea>
    </label>

    <fieldset class="form-fieldset">
        <legend>Attributes</legend>
        <div id="attribute-list">
            @foreach($attributeRows as $i => $row)
                <div class="form-grid form-grid--attributes js-attribute-row">
                    <label>
                        Name
                        <textarea name="attributes[{{ $i }}][name]" rows="2" placeholder="e.g. Color">{{ $row['name'] ?? '' }}</textarea>
                    </label>
                    <label>
                        Value
                        <textarea name="attributes[{{ $i }}][value]" rows="3" placeholder="e.g. Black">{{ $row['value'] ?? '' }}</textarea>
                    </label>
                    <div class="form-actions">
                        <button class="btn-admin" type="button" data-action="remove-attribute">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button class="btn-admin" type="button" id="add-attribute">+ Add attribute</button>
    </fieldset>

    @php
        $upsellRows = old('upsells');
        if (! is_array($upsellRows) && $product) {
            $upsellRows = $product->upsellProducts->map(fn ($p) => [
                'product_id' => $p->id,
                'name' => $p->name,
                'thumbnail' => $p->thumbnail ?: $p->image,
                'price_usd' => (float) $p->price_usd,
                'discount' => (float) ($p->pivot->discount ?? 0),
                'upsale_discount' => (float) ($p->pivot->upsale_discount ?? 0),
            ])->all();
        }
        $upsellRows = is_array($upsellRows) ? $upsellRows : [];
        $upsellSearchUrl = route('admin.products.search', $product ? ['exclude' => $product->id] : []);
    @endphp
    <fieldset class="form-fieldset">
        <legend>Upsell products</legend>
        <p class="admin-hint">Products shown as “Frequently bought together” on the storefront. Set display discount and upsale cart discount (% off base price).</p>
        <div class="upsell-picker" data-upsell-picker data-search-url="{{ $upsellSearchUrl }}">
            <label class="upsell-picker__search-label">
                Search products
                <input type="search" class="upsell-picker__search" placeholder="Type to search…" autocomplete="off" data-upsell-search>
            </label>
            <div class="upsell-picker__results" data-upsell-results hidden></div>
            <div class="upsell-picker__selected" data-upsell-selected>
                @foreach($upsellRows as $row)
                    @if((int) ($row['product_id'] ?? 0) > 0)
                        <div class="upsell-picker__row" data-upsell-row data-product-id="{{ (int) $row['product_id'] }}">
                            <input type="hidden" name="upsells[{{ (int) $row['product_id'] }}][product_id]" value="{{ (int) $row['product_id'] }}">
                            <div class="upsell-picker__product">
                                @if(!empty($row['thumbnail']))
                                    <img src="{{ $row['thumbnail'] }}" alt="" width="40" height="40">
                                @endif
                                <span>{{ $row['name'] ?? ('#' . $row['product_id']) }}</span>
                            </div>
                            <label>
                                Discount %
                                <input type="number" name="upsells[{{ (int) $row['product_id'] }}][discount]" min="0" max="100" step="0.01" value="{{ $row['discount'] ?? 0 }}">
                            </label>
                            <label>
                                Upsale discount %
                                <input type="number" name="upsells[{{ (int) $row['product_id'] }}][upsale_discount]" min="0" max="100" step="0.01" value="{{ $row['upsale_discount'] ?? 0 }}">
                            </label>
                            <button type="button" class="btn-admin" data-upsell-remove aria-label="Remove">×</button>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>SEO</legend>
        <label>
            Meta title
            <input type="text" name="meta_title" value="{{ old('meta_title', $product->meta_title ?? '') }}">
        </label>
        <label>
            Meta description
            <textarea name="meta_description" rows="2">{{ old('meta_description', $product->meta_description ?? '') }}</textarea>
        </label>
    </fieldset>

    <label class="checkbox">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product ? $product->is_active : true))>
        Active on storefront
    </label>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">{{ $product ? 'Save changes' : 'Create product' }}</button>
        <a class="btn-admin" href="{{ route('admin.products.index') }}">Cancel</a>
    </div>
</form>
<template id="variant-row-template">
    <div class="form-grid form-grid--variants js-variant-row" style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #e5e7eb;">
        <label>
            Color
            @include('admin.partials.color-picker', [
                'dataNameColor' => 'variant-color',
                'dataNameSwatch' => 'variant-swatch-color',
                'valueColor' => '',
                'valueSwatch' => '',
            ])
            <small>Color name and swatch shown on storefront.</small>
        </label>
        <label>
            Size
            <input type="text" data-name="variant-size" placeholder="One Size">
        </label>
        <label>
            Price (USD)
            <input type="text" data-name="variant-price" required value="0">
        </label>
        <label>
            Compare at price
            <input type="text" data-name="variant-compare-price">
        </label>
        <label>
            Stock
            <input type="number" data-name="variant-stock" min="0" required value="0">
        </label>
        <label>
            SKU
            <input type="text" data-name="variant-sku">
        </label>
        @include('partials.file-upload', [
            'dataName' => 'variant-image',
            'label' => 'Front image',
            'variant' => 'compact',
        ])
        @include('partials.file-upload', [
            'dataName' => 'variant-hover-images',
            'label' => 'Hover images (3–5)',
            'variant' => 'compact',
            'multiple' => true,
            'maxFiles' => 5,
        ])
        @include('partials.file-upload', [
            'dataName' => 'variant-image-hover',
            'label' => 'Legacy hover image',
            'variant' => 'compact',
        ])
        <label class="checkbox">
            <input type="checkbox" data-name="variant-default" value="1">
            Default variant
        </label>
        <label class="checkbox">
            <input type="checkbox" data-name="variant-active" value="1" checked>
            Active
        </label>
        <div class="form-actions">
            <button class="btn-admin" type="button" data-action="remove-variant">Remove</button>
        </div>
    </div>
</template>
<template id="attribute-row-template">
    <div class="form-grid form-grid--attributes js-attribute-row">
        <label>
            Name
            <textarea rows="2" data-name="attribute-name" placeholder="e.g. Color"></textarea>
        </label>
        <label>
            Value
            <textarea rows="3" data-name="attribute-value" placeholder="e.g. Black"></textarea>
        </label>
        <div class="form-actions">
            <button class="btn-admin" type="button" data-action="remove-attribute">Remove</button>
        </div>
    </div>
</template>
<script>
(() => {
    const setupVariants = () => {
        const list = document.getElementById('variant-list');
        const addButton = document.getElementById('add-variant');
        const template = document.getElementById('variant-row-template');
        if (!list || !addButton || !template) return;

        const updateNames = () => {
            const rows = list.querySelectorAll('.js-variant-row');
            rows.forEach((row, index) => {
                const map = [
                    ['variant-color', `variants[${index}][option_color]`],
                    ['variant-swatch-color', `variants[${index}][swatch_color]`],
                    ['variant-size', `variants[${index}][option_size]`],
                    ['variant-price', `variants[${index}][price_usd]`],
                    ['variant-compare-price', `variants[${index}][compare_at_price_usd]`],
                    ['variant-stock', `variants[${index}][stock]`],
                    ['variant-sku', `variants[${index}][sku]`],
                    ['variant-image', `variants[${index}][image]`],
                    ['variant-hover-images', `variants[${index}][hover_images][]`],
                    ['variant-image-hover', `variants[${index}][image_hover]`],
                    ['variant-default', `variants[${index}][is_default]`],
                    ['variant-active', `variants[${index}][is_active]`],
                ];
                map.forEach(([dataName, fieldName]) => {
                    const input = row.querySelector(`[data-name="${dataName}"], [name*="[${dataName.replace('variant-', '')}]"]`);
                    if (input) input.setAttribute('name', fieldName);
                });
                row.querySelectorAll('input[type="hidden"]').forEach((hidden) => {
                    if (hidden.name && hidden.name.includes('[id]')) {
                        hidden.setAttribute('name', `variants[${index}][id]`);
                    }
                });
            });
        };

        addButton.addEventListener('click', () => {
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('.js-variant-row');
            list.appendChild(fragment);
            updateNames();
            if (row) {
                document.dispatchEvent(new CustomEvent('admin:color-picker-init', { detail: { root: row } }));
                document.dispatchEvent(new CustomEvent('file-upload:init', { detail: { root: row } }));
            }
        });

        list.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            if (target.dataset.action !== 'remove-variant') return;
            const row = target.closest('.js-variant-row');
            if (!row) return;
            if (list.querySelectorAll('.js-variant-row').length <= 1) return;
            row.remove();
            updateNames();
        });

        updateNames();
    };

    const setupAttributes = () => {
        const list = document.getElementById('attribute-list');
        const addButton = document.getElementById('add-attribute');
        const template = document.getElementById('attribute-row-template');
        if (!list || !addButton || !template) return;

        const updateNames = () => {
            const rows = list.querySelectorAll('.js-attribute-row');
            rows.forEach((row, index) => {
                const nameInput = row.querySelector('[data-name="attribute-name"], textarea[name*="[name]"]');
                const valueInput = row.querySelector('[data-name="attribute-value"], textarea[name*="[value]"]');
                if (nameInput) nameInput.setAttribute('name', `attributes[${index}][name]`);
                if (valueInput) valueInput.setAttribute('name', `attributes[${index}][value]`);
            });
        };

        const ensureOneRow = () => {
            if (list.querySelectorAll('.js-attribute-row').length > 0) return;
            addButton.click();
        };

        addButton.addEventListener('click', () => {
            const fragment = template.content.cloneNode(true);
            list.appendChild(fragment);
            updateNames();
        });

        list.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            if (target.dataset.action !== 'remove-attribute') return;
            const row = target.closest('.js-attribute-row');
            if (!row) return;
            row.remove();
            updateNames();
            ensureOneRow();
        });

        updateNames();
    };

    const setupUpsellPicker = () => {
        const root = document.querySelector('[data-upsell-picker]');
        if (!root) return;
        const searchUrl = root.dataset.searchUrl || '';
        const searchInput = root.querySelector('[data-upsell-search]');
        const results = root.querySelector('[data-upsell-results]');
        const selected = root.querySelector('[data-upsell-selected]');
        if (!(searchInput instanceof HTMLInputElement) || !results || !selected) return;

        let debounceTimer = null;

        const selectedIds = () => new Set(
            Array.from(selected.querySelectorAll('[data-upsell-row]'))
                .map((row) => parseInt(row.getAttribute('data-product-id') || '0', 10))
                .filter((id) => id > 0)
        );

        const renderRow = (product) => {
            const id = product.id;
            const row = document.createElement('div');
            row.className = 'upsell-picker__row';
            row.setAttribute('data-upsell-row', '');
            row.setAttribute('data-product-id', String(id));
            const thumb = product.thumbnail
                ? `<img src="${product.thumbnail}" alt="" width="40" height="40">`
                : '';
            row.innerHTML = `
                <input type="hidden" name="upsells[${id}][product_id]" value="${id}">
                <div class="upsell-picker__product">${thumb}<span>${product.name}</span></div>
                <label>Discount %
                    <input type="number" name="upsells[${id}][discount]" min="0" max="100" step="0.01" value="0">
                </label>
                <label>Upsale discount %
                    <input type="number" name="upsells[${id}][upsale_discount]" min="0" max="100" step="0.01" value="0">
                </label>
                <button type="button" class="btn-admin" data-upsell-remove aria-label="Remove">×</button>
            `;
            return row;
        };

        const hideResults = () => {
            results.hidden = true;
            results.innerHTML = '';
        };

        const runSearch = async () => {
            const q = searchInput.value.trim();
            if (q.length < 1) {
                hideResults();
                return;
            }
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', q);
            const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const items = await response.json();
            const ids = selectedIds();
            const matches = items.filter((item) => !ids.has(item.id));
            if (matches.length === 0) {
                results.innerHTML = '<p class="upsell-picker__empty">No products found.</p>';
                results.hidden = false;
                return;
            }
            results.innerHTML = '';
            matches.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'upsell-picker__result';
                btn.setAttribute('data-upsell-add', '');
                if (item.thumbnail) {
                    const img = document.createElement('img');
                    img.src = item.thumbnail;
                    img.alt = '';
                    img.width = 32;
                    img.height = 32;
                    btn.appendChild(img);
                }
                const name = document.createElement('span');
                name.textContent = item.name;
                btn.appendChild(name);
                const price = document.createElement('small');
                price.textContent = '$' + Number(item.price_usd).toFixed(2);
                btn.appendChild(price);
                btn.addEventListener('click', () => {
                    selected.appendChild(renderRow(item));
                    searchInput.value = '';
                    hideResults();
                });
                results.appendChild(btn);
            });
            results.hidden = false;
        };

        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(runSearch, 250);
        });

        selected.addEventListener('click', (event) => {
            const btn = event.target instanceof HTMLElement
                ? event.target.closest('[data-upsell-remove]')
                : null;
            if (!btn) return;
            const row = btn.closest('[data-upsell-row]');
            if (row) row.remove();
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Node) || root.contains(event.target)) return;
            hideResults();
        });
    };

    setupVariants();
    setupAttributes();
    setupUpsellPicker();
})();
</script>

@include('admin.partials.tinymce-init', ['formSelector' => '#product-form'])
@endsection
