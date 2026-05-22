@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('shop.about') }}" target="_blank" rel="noopener">View About page</a>
    <a class="btn-admin" href="{{ route('shop.home') }}" target="_blank" rel="noopener">View home section</a>
@endsection

@section('module-meta')
    Edit the full <strong>/about</strong> page and the About us teaser on the homepage.
@endsection

@section('content')
<form id="about-form" class="stack-form" method="post" action="{{ route('admin.about.save') }}">
    @csrf
    @if($errors->any())
        <div class="admin-banner admin-banner--err" role="alert" style="margin-bottom:12px;">
            {{ $errors->first() }}
        </div>
    @endif

    <fieldset class="form-fieldset">
        <legend>About page (/about)</legend>
        <label>
            Page summary (subtitle under title)
            <input type="text" name="page_summary" value="{{ old('page_summary', $about['page_summary']) }}" maxlength="500">
        </label>
        <label>
            Page body
            <textarea id="about-page-body" class="js-rich-text" name="page_body" rows="8" data-rich-height="360">{{ old('page_body', $about['page_body']) }}</textarea>
        </label>
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Homepage — About us section</legend>
        <label>
            Intro paragraph
            <textarea name="home_lede" rows="4">{{ old('home_lede', $about['home_lede']) }}</textarea>
        </label>
        <label>
            Button label
            <input type="text" name="home_button_label" value="{{ old('home_button_label', $about['home_button_label']) }}" maxlength="120">
        </label>
        <p style="margin:0 0 8px;font-weight:600;font-size:0.9rem;">Accordion panels</p>
        <div id="about-panels-list">
            @foreach(old('panels', $about['panels']) as $i => $panel)
                <div class="js-about-panel form-fieldset" style="margin-top:10px;padding:12px;border:1px solid #e2e6ec;border-radius:10px;background:#fff;">
                    <label>
                        Panel title
                        <input type="text" name="panels[{{ $i }}][title]" value="{{ $panel['title'] ?? '' }}" maxlength="200">
                    </label>
                    <label>
                        Panel body
                        <textarea class="js-rich-text" name="panels[{{ $i }}][body]" rows="6" data-rich-height="220">{{ $panel['body'] ?? '' }}</textarea>
                    </label>
                    <button type="button" class="btn-admin btn-admin--small btn-admin--danger js-about-panel-remove">Remove panel</button>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn-admin" id="about-panel-add" style="margin-top:10px;">+ Add panel</button>
    </fieldset>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">Save About us</button>
    </div>
</form>

<template id="about-panel-template">
    <div class="js-about-panel form-fieldset" style="margin-top:10px;padding:12px;border:1px solid #e2e6ec;border-radius:10px;background:#fff;">
        <label>
            Panel title
            <input type="text" data-name="title" maxlength="200">
        </label>
        <label>
            Panel body
            <textarea class="js-rich-text" data-name="body" rows="6" data-rich-height="220"></textarea>
        </label>
        <button type="button" class="btn-admin btn-admin--small btn-admin--danger js-about-panel-remove">Remove panel</button>
    </div>
</template>

@push('scripts')
<script>
(function () {
    const list = document.getElementById('about-panels-list');
    const template = document.getElementById('about-panel-template');
    const addBtn = document.getElementById('about-panel-add');
    if (!list || !template || !addBtn) return;

    function reindexPanels() {
        list.querySelectorAll('.js-about-panel').forEach(function (row, index) {
            row.querySelectorAll('[data-name]').forEach(function (el) {
                const field = el.getAttribute('data-name');
                el.setAttribute('name', 'panels[' + index + '][' + field + ']');
            });
            row.querySelectorAll('[name^="panels["]').forEach(function (el) {
                if (el.hasAttribute('data-name')) return;
                const match = el.getAttribute('name').match(/\[(\w+)\]$/);
                if (match) {
                    el.setAttribute('name', 'panels[' + index + '][' + match[1] + ']');
                }
            });
        });
    }

    function bindRemove(row) {
        const btn = row.querySelector('.js-about-panel-remove');
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (list.querySelectorAll('.js-about-panel').length <= 1) {
                if (typeof window.adminRemoveRichText === 'function') {
                    window.adminRemoveRichText(row);
                }
                row.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
                return;
            }
            if (typeof window.adminRemoveRichText === 'function') {
                window.adminRemoveRichText(row);
            }
            row.remove();
            reindexPanels();
        });
    }

    list.querySelectorAll('.js-about-panel').forEach(bindRemove);

    addBtn.addEventListener('click', function () {
        const index = list.querySelectorAll('.js-about-panel').length;
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.js-about-panel');
        row.querySelector('[data-name="title"]').setAttribute('name', 'panels[' + index + '][title]');
        row.querySelector('[data-name="body"]').setAttribute('name', 'panels[' + index + '][body]');
        list.appendChild(fragment);
        const newRow = list.lastElementChild;
        bindRemove(newRow);
        if (typeof window.adminInitRichText === 'function') {
            window.adminInitRichText(newRow);
        }
    });
})();
</script>
@endpush

@include('admin.partials.tinymce-init', ['formSelector' => '#about-form'])
@endsection
