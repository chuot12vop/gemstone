@extends('layouts.admin')

@section('module-meta', 'Edit responsive storefront styles and preview changes before publishing.')

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/css/custom-css-editor.css') }}?v={{ filemtime(public_path('assets/css/custom-css-editor.css')) }}">
@endpush

@section('content')
<div class="custom-css-workspace"
     data-custom-css-workspace
     data-max-bytes="{{ $maxBytes }}"
     data-preview-origin="{{ url('/') }}">
    <section class="custom-css-panel" aria-labelledby="custom-css-editor-title">
        <div class="custom-css-panel__head">
            <div>
                <h2 class="admin-h2" id="custom-css-editor-title">Stylesheet editor</h2>
                <p class="custom-css-panel__path" data-editor-path></p>
            </div>
            <span class="custom-css-status" data-editor-status aria-live="polite">Saved</span>
        </div>

        <div class="custom-css-tabs" role="tablist" aria-label="Responsive stylesheets">
            @foreach($viewports as $key => $viewport)
                <button type="button"
                        class="custom-css-tab {{ $loop->first ? 'is-active' : '' }}"
                        id="custom-css-tab-{{ $key }}"
                        role="tab"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        aria-controls="custom-css-panel-{{ $key }}"
                        tabindex="{{ $loop->first ? '0' : '-1' }}"
                        data-viewport-tab="{{ $key }}">
                    {{ $viewport['label'] }}
                    <span class="custom-css-tab__dirty" aria-hidden="true"></span>
                </button>
            @endforeach
        </div>

        @foreach($viewports as $key => $viewport)
            <form method="post"
                  action="{{ route('admin.custom-css.update', $key) }}"
                  id="custom-css-panel-{{ $key }}"
                  class="custom-css-editor-panel"
                  role="tabpanel"
                  aria-labelledby="custom-css-tab-{{ $key }}"
                  data-custom-css-form="{{ $key }}"
                  data-path="storage/app/public/{{ $viewport['path'] }}"
                  data-width="{{ $viewport['width'] }}"
                  data-height="{{ $viewport['height'] }}"
                  data-media="{{ $viewport['media'] }}"
                  @if(!$loop->first) hidden @endif>
                @csrf
                @method('PUT')

                <label class="sr-only" for="custom-css-editor-{{ $key }}">{{ $viewport['label'] }} CSS</label>
                <textarea id="custom-css-editor-{{ $key }}"
                          class="custom-css-editor"
                          name="custom_css"
                          spellcheck="false"
                          autocapitalize="off"
                          autocomplete="off"
                          data-custom-css-editor="{{ $key }}">{{ $viewport['contents'] }}</textarea>

                <p class="custom-css-error" data-editor-error role="alert" hidden></p>

                <div class="custom-css-editor__meta">
                    <span data-character-count>0 characters</span>
                    <span data-byte-count>0 / 512 KB</span>
                </div>

                <p class="admin-hint">
                    @if($viewport['media'])
                        Loaded only for <code>{{ $viewport['media'] }}</code>. Use this file for responsive overrides.
                    @else
                        Base stylesheet loaded on every screen. Mobile and tablet files override it at their breakpoints.
                    @endif
                </p>

                <div class="form-actions">
                    <button class="btn-admin btn-admin--primary" type="submit" data-save-css>Save {{ $viewport['label'] }} CSS</button>
                    <button class="btn-admin" type="button" data-reset-css>Reset</button>
                </div>
            </form>
        @endforeach

        <p class="admin-hint custom-css-asset-hint">
            Use absolute asset paths such as <code>/storage/products/example.webp</code> so URLs behave the same in preview and after saving.
        </p>
    </section>

    <section class="custom-css-panel custom-css-preview" aria-labelledby="custom-css-preview-title">
        <div class="custom-css-panel__head custom-css-preview__head">
            <div>
                <h2 class="admin-h2" id="custom-css-preview-title">Live preview</h2>
                <p class="custom-css-preview__status" data-preview-status aria-live="polite">Loading preview...</p>
            </div>
            <span class="custom-css-preview__size" data-preview-size>1440 × 900px</span>
        </div>

        <form class="custom-css-preview__controls" data-preview-form>
            <label class="sr-only" for="custom-css-preview-url">Internal preview URL</label>
            <input id="custom-css-preview-url" type="text" value="/" placeholder="/catalog" data-preview-url>
            <button class="btn-admin" type="submit">Load</button>
            <button class="btn-admin" type="button" data-refresh-preview>Refresh</button>
        </form>
        <p class="custom-css-error" data-preview-error role="alert" hidden></p>

        <div class="custom-css-preview__stage" data-preview-stage>
            <div class="custom-css-preview__frame-wrap" data-preview-frame-wrap>
                <iframe class="custom-css-preview__frame"
                        title="Storefront custom CSS preview"
                        src="{{ url('/') }}"
                        data-preview-frame></iframe>
            </div>
        </div>
    </section>
</div>

<script type="application/json" data-saved-custom-css>@json(collect($viewports)->mapWithKeys(fn ($viewport, $key) => [$key => $viewport['contents']]))</script>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/custom-css-editor.js') }}?v={{ filemtime(public_path('assets/js/custom-css-editor.js')) }}" defer></script>
@endpush
