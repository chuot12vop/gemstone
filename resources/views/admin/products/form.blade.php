@extends('layouts.admin')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>
@endpush

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
        </label>
        
    </div>

    <fieldset class="form-fieldset">
        <legend>Thumbnail upload</legend>
        <div id="thumbnail-dropzone" style="margin-top:10px;padding:18px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
            <strong>Drop thumbnail here</strong><br>
            <small>or click to choose 1 image</small>
        </div>
        <div style="margin-top:10px;">
            <img id="thumbnail-preview" src="{{ $product->thumbnail ?? ($product->image ?? asset('assets/img/placeholder.svg')) }}" alt="Thumbnail preview" width="200" height="200" style="object-fit:cover;border:1px solid #d7dbe2;border-radius:8px;background:#fff;">
        </div>
        <input id="thumbnail-input" class="display-none" type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
    </fieldset>

    <label class="display-none">
        Product gallery images (multiple files)
        <input id="images-input" type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        <small>When editing: upload new files to replace existing gallery images.</small>
    </label>
    <div id="images-dropzone" style="padding:18px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
        <strong>Drop gallery images here</strong><br>
        <small>or click to choose multiple files</small>
    </div>
    <div id="gallery-preview" class="product-images-grid"></div>
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
    <label>
        Full description
        <textarea id="product-description" name="description" rows="8">{{ old('description', $product->description ?? '') }}</textarea>
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
    const setupDescriptionEditor = () => {
        const textarea = document.getElementById('product-description');
        if (!(textarea instanceof HTMLTextAreaElement) || typeof tinymce === 'undefined') return;

        tinymce.init({
            selector: '#product-description',
            height: 360,
            menubar: false,
            plugins: 'lists link table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | removeformat code',
            branding: false,
            promotion: false,
            convert_urls: false,
            content_style: 'body { font-family: "Source Sans 3", sans-serif; font-size: 15px; line-height: 1.6; }',
        });

        const form = document.getElementById('product-form');
        form?.addEventListener('submit', () => tinymce.triggerSave());
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

    const setupGalleryPreview = () => {
        const imagesInput = document.getElementById('images-input');
        const imagesDropzone = document.getElementById('images-dropzone');
        const galleryPreview = document.getElementById('gallery-preview');
        if (!(imagesInput instanceof HTMLInputElement) || !(imagesDropzone instanceof HTMLElement) || !galleryPreview) return;
        let productImages = [];

        const syncInputFiles = () => {
            const dt = new DataTransfer();
            productImages.forEach((file) => dt.items.add(file));
            imagesInput.files = dt.files;
        };

        const renderPreview = () => {
            galleryPreview.innerHTML = '';
            productImages.forEach((file, index) => {
                if (!file.type.startsWith('image/')) return;
                const url = URL.createObjectURL(file);
                const item = document.createElement('div');
                item.className = 'product-image-item';

                const img = document.createElement('img');
                img.src = url;
                img.alt = 'New gallery preview';
                img.width = 120;
                img.height = 120;
                img.loading = 'lazy';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '8px';
                img.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'product-image-remove';
                removeButton.textContent = '×';
                removeButton.setAttribute('aria-label', `Remove image ${index + 1}`);
                removeButton.addEventListener('click', () => {
                    productImages.splice(index, 1);
                    syncInputFiles();
                    renderPreview();
                });

                item.appendChild(img);
                item.appendChild(removeButton);
                galleryPreview.appendChild(item);
            });
        };

        const appendFiles = (incomingFiles) => {
            incomingFiles
                .filter((file) => file.type.startsWith('image/'))
                .forEach((file) => productImages.push(file));
            syncInputFiles();
            renderPreview();
        };

        imagesInput.addEventListener('change', () => {
            const files = imagesInput.files ? Array.from(imagesInput.files) : [];
            appendFiles(files);
        });

        const preventDefaults = (event) => {
            event.preventDefault();
            event.stopPropagation();
        };
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
            imagesDropzone.addEventListener(eventName, preventDefaults);
        });
        ['dragenter', 'dragover'].forEach((eventName) => {
            imagesDropzone.addEventListener(eventName, () => imagesDropzone.style.borderColor = '#1f6feb');
        });
        ['dragleave', 'drop'].forEach((eventName) => {
            imagesDropzone.addEventListener(eventName, () => imagesDropzone.style.borderColor = '#c8d1dc');
        });
        imagesDropzone.addEventListener('drop', (event) => {
            const files = event.dataTransfer ? Array.from(event.dataTransfer.files).filter((file) => file.type.startsWith('image/')) : [];
            if (files.length === 0) return;
            appendFiles(files);
        });
        imagesDropzone.addEventListener('click', () => imagesInput.click());
    };

    const setupThumbnailDropzone = () => {
        const fileInput = document.getElementById('thumbnail-input');
        const preview = document.getElementById('thumbnail-preview');
        const dropzone = document.getElementById('thumbnail-dropzone');
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

    setupDescriptionEditor();
    setupAttributes();
    setupGalleryPreview();
    setupThumbnailDropzone();
})();
</script>
@endsection
