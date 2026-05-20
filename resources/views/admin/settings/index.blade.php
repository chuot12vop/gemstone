@extends('layouts.admin')

@section('module-actions')
    <a class="btn-admin" href="{{ route('admin.interface.index') }}">Interface — banner slides</a>
@endsection

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
        <legend>Welcome popup (homepage)</legend>
        <label class="checkbox">
            <input type="checkbox" name="welcome_popup_enabled" value="1" @checked(old('welcome_popup_enabled', $welcomePopup['enabled'] ?? true))>
            Show welcome popup on the home page
        </label>
        <div class="form-grid">
            <label>
                Delay after first scroll (seconds)
                <input type="number" name="welcome_popup_delay_seconds" min="1" max="120" value="{{ old('welcome_popup_delay_seconds', $welcomePopup['delay_seconds'] ?? 10) }}">
            </label>
        </div>
        <label>
            Title
            <input type="text" name="welcome_popup_title" value="{{ old('welcome_popup_title', $welcomePopup['title'] ?? '') }}" maxlength="300">
        </label>
        <label>
            Email placeholder
            <input type="text" name="welcome_popup_email_placeholder" value="{{ old('welcome_popup_email_placeholder', $welcomePopup['email_placeholder'] ?? '') }}" maxlength="120">
        </label>
        <label>
            Submit button label
            <input type="text" name="welcome_popup_submit_label" value="{{ old('welcome_popup_submit_label', $welcomePopup['submit_label'] ?? '') }}" maxlength="120">
        </label>
        <label>
            Legal / fine print (HTML allowed)
            <textarea name="welcome_popup_legal_html" rows="5">{{ old('welcome_popup_legal_html', $welcomePopup['legal_html'] ?? '') }}</textarea>
        </label>
        <label>
            Success message
            <input type="text" name="welcome_popup_success_message" value="{{ old('welcome_popup_success_message', $welcomePopup['success_message'] ?? '') }}" maxlength="500">
        </label>
        <p style="margin:0 0 6px;font-weight:600;font-size:0.9rem;">Background image (faint behind card)</p>
        <div id="welcome-popup-dropzone" style="margin-top:6px;padding:18px;border:2px dashed #c8d1dc;border-radius:10px;background:#f8fafc;text-align:center;cursor:pointer;">
            <strong>Drop welcome banner image here</strong><br>
            <small>or click to choose 1 image</small>
        </div>
        <div style="margin-top:10px;">
            <img id="welcome-popup-preview"
                 src="{{ old('welcome_popup_image_preview', $welcomePopup['image_url'] ?? asset('assets/img/welcome-popup.png')) }}"
                 alt="Welcome popup preview"
                 width="280"
                 style="max-width:100%;object-fit:cover;border:1px solid #d7dbe2;border-radius:12px;background:#fff;">
        </div>
        <input id="welcome-popup-input" class="display-none" type="file" name="welcome_popup_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        @error('welcome_popup_image')
            <p style="margin:8px 0 0;color:#b33a3a;">{{ $message }}</p>
        @enderror
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Storefront</legend>
        <label>
            WhatsApp contact number
            <input type="text" name="contact_whatsapp_phone" value="{{ old('contact_whatsapp_phone', $settings['contact_whatsapp_phone'] ?? '') }}" placeholder="+849xxxxxxxx">
            <small style="color:#5c6470;">Used for the floating WhatsApp button on the shop. Falls back to payment WhatsApp if empty.</small>
        </label>
        <label>
            Home news ticker (one headline per line)
            <textarea name="home_news_ticker" rows="4" placeholder="New collection launched&#10;Free shipping over $99">{{ old('home_news_ticker', $settings['home_news_ticker'] ?? '') }}</textarea>
        </label>
        <label>
            Footer background image
            <input type="file" name="footer_background" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            @if(!empty($settings['footer_background']))
                <img src="{{ \App\Support\PublicAssetUrl::to($settings['footer_background']) }}" alt="" width="240" style="margin-top:8px;border-radius:8px;display:block;">
            @endif
        </label>
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
            Return policy
            <textarea name="return_policy" rows="8">{{ old('return_policy', $settings['return_policy'] ?? '') }}</textarea>
        </label>
        <label>
            Terms of service
            <textarea name="terms_of_service" rows="8">{{ old('terms_of_service', $settings['terms_of_service'] ?? '') }}</textarea>
        </label>
    </fieldset>

    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">Save system settings</button>
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
    setupSingleImageDropzone('welcome-popup-input', 'welcome-popup-preview', 'welcome-popup-dropzone');
})();
</script>
@endsection
