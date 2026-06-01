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
            Legal / fine print
            <textarea id="welcome-popup-legal" class="js-rich-text" name="welcome_popup_legal_html" rows="6" data-rich-height="220">{{ old('welcome_popup_legal_html', $welcomePopup['legal_html'] ?? '') }}</textarea>
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
            Contact form Google Script URL
            <input type="url" name="contact_google_script_url" value="{{ old('contact_google_script_url', $settings['contact_google_script_url'] ?? '') }}" placeholder="https://script.google.com/macros/s/.../exec">
            <small style="color:#5c6470;">Web app URL from Google Apps Script (deploy as &ldquo;Anyone&rdquo;). Sends JSON: <code>name</code>, <code>phone</code>, <code>email</code>, <code>address</code>, <code>product</code>, <code>message</code>. Overrides <code>CONTACT_GOOGLE_SCRIPT_URL</code> in .env when set.</small>
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
        <legend>Footer payment icons</legend>
        <p style="margin:0 0 12px;color:#5c6470;font-size:0.95rem;">
            Shown in the site footer, cart, and checkout. Drag rows to reorder. SVG, PNG, JPG, or WebP (max 2&nbsp;MB each).
        </p>
        <div id="payment-logos-list">
            @foreach($paymentLogos as $i => $logo)
                <div class="js-payment-logo-row form-fieldset" draggable="true" style="margin-top:12px;padding:12px;border:1px solid #e2e6ec;border-radius:10px;background:#fff;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-start;">
                    <input type="hidden" class="js-payment-logo-path" name="payment_logos[{{ $i }}][path]" value="{{ old('payment_logos.'.$i.'.path', $logo['path'] ?? '') }}">
                    <div style="flex:0 0 auto;">
                        <img class="js-payment-logo-preview" src="{{ $logo['src'] ?? asset('assets/img/placeholder.svg') }}" alt="" width="72" height="44" style="object-fit:contain;border:1px solid #d7dbe2;border-radius:6px;background:#fff;padding:4px;">
                    </div>
                    <div style="flex:1 1 200px;min-width:180px;">
                        <label>
                            Label (accessibility)
                            <input type="text" name="payment_logos[{{ $i }}][label]" value="{{ old('payment_logos.'.$i.'.label', $logo['label'] ?? '') }}" maxlength="120" placeholder="e.g. Visa">
                        </label>
                        <label style="margin-top:8px;display:block;">
                            Replace image
                            <input class="js-payment-logo-file" type="file" name="payment_logos[{{ $i }}][image]" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml">
                        </label>
                    </div>
                    <div style="flex:0 0 auto;align-self:center;">
                        <button class="btn-admin" type="button" data-action="remove-payment-logo">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>
        <div style="margin-top:14px;">
            <label>
                Add new icons (multiple files)
                <input id="payment-logos-new-input" type="file" name="payment_logos_new[]" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" multiple>
            </label>
            <div id="payment-logos-new-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;"></div>
        </div>
        <button class="btn-admin" type="button" id="add-payment-logo" style="margin-top:12px;">+ Add icon row</button>
        @error('payment_logos.*.image')
            <p style="margin:8px 0 0;color:#b33a3a;">{{ $message }}</p>
        @enderror
        @error('payment_logos_new.*')
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
            Return policy
            <textarea name="return_policy" rows="8">{{ old('return_policy', $settings['return_policy'] ?? '') }}</textarea>
        </label>
        <label>
            Terms of service
            <textarea name="terms_of_service" rows="8">{{ old('terms_of_service', $settings['terms_of_service'] ?? '') }}</textarea>
        </label>
        <label>
            Story of us
            <textarea name="story_of_us" rows="8">{{ old('story_of_us', $settings['story_of_us'] ?? '') }}</textarea>
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

    const paymentLogosList = document.getElementById('payment-logos-list');
    const addPaymentLogoBtn = document.getElementById('add-payment-logo');
    const paymentLogosNewInput = document.getElementById('payment-logos-new-input');
    const paymentLogosNewPreview = document.getElementById('payment-logos-new-preview');
    const placeholderSrc = @json(asset('assets/img/placeholder.svg'));

    const reindexPaymentLogoRows = () => {
        if (!paymentLogosList) return;
        paymentLogosList.querySelectorAll('.js-payment-logo-row').forEach((row, index) => {
            row.querySelectorAll('[name^="payment_logos["]').forEach((input) => {
                if (!(input instanceof HTMLInputElement)) return;
                input.name = input.name.replace(/payment_logos\[\d+]/, `payment_logos[${index}]`);
            });
        });
    };

    const bindPaymentLogoRow = (row) => {
        const fileInput = row.querySelector('.js-payment-logo-file');
        const preview = row.querySelector('.js-payment-logo-preview');
        const removeBtn = row.querySelector('[data-action="remove-payment-logo"]');

        if (fileInput instanceof HTMLInputElement && preview instanceof HTMLImageElement) {
            fileInput.addEventListener('change', () => {
                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                if (!file || !file.type.startsWith('image/')) return;
                const url = URL.createObjectURL(file);
                preview.src = url;
                preview.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
            });
        }

        if (removeBtn instanceof HTMLButtonElement) {
            removeBtn.addEventListener('click', () => {
                row.remove();
                reindexPaymentLogoRows();
            });
        }

        row.addEventListener('dragstart', (event) => {
            row.classList.add('is-dragging');
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
            }
        });
        row.addEventListener('dragend', () => {
            row.classList.remove('is-dragging');
            reindexPaymentLogoRows();
        });
        row.addEventListener('dragover', (event) => {
            event.preventDefault();
            const dragging = paymentLogosList?.querySelector('.is-dragging');
            if (!(dragging instanceof HTMLElement) || dragging === row || !paymentLogosList) return;
            const rows = [...paymentLogosList.querySelectorAll('.js-payment-logo-row')];
            const dragIndex = rows.indexOf(dragging);
            const hoverIndex = rows.indexOf(row);
            if (dragIndex < 0 || hoverIndex < 0) return;
            if (dragIndex < hoverIndex) {
                row.after(dragging);
            } else {
                row.before(dragging);
            }
        });
    };

    if (paymentLogosList) {
        paymentLogosList.querySelectorAll('.js-payment-logo-row').forEach(bindPaymentLogoRow);
    }

    if (addPaymentLogoBtn && paymentLogosList) {
        addPaymentLogoBtn.addEventListener('click', () => {
            const index = paymentLogosList.querySelectorAll('.js-payment-logo-row').length;
            const row = document.createElement('div');
            row.className = 'js-payment-logo-row form-fieldset';
            row.draggable = true;
            row.style.cssText = 'margin-top:12px;padding:12px;border:1px solid #e2e6ec;border-radius:10px;background:#fff;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-start;';
            row.innerHTML = `
                <input type="hidden" class="js-payment-logo-path" name="payment_logos[${index}][path]" value="">
                <div style="flex:0 0 auto;">
                    <img class="js-payment-logo-preview" src="${placeholderSrc}" alt="" width="72" height="44" style="object-fit:contain;border:1px solid #d7dbe2;border-radius:6px;background:#fff;padding:4px;">
                </div>
                <div style="flex:1 1 200px;min-width:180px;">
                    <label>
                        Label (accessibility)
                        <input type="text" name="payment_logos[${index}][label]" maxlength="120" placeholder="e.g. Visa">
                    </label>
                    <label style="margin-top:8px;display:block;">
                        Image
                        <input class="js-payment-logo-file" type="file" name="payment_logos[${index}][image]" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" required>
                    </label>
                </div>
                <div style="flex:0 0 auto;align-self:center;">
                    <button class="btn-admin" type="button" data-action="remove-payment-logo">Remove</button>
                </div>
            `;
            paymentLogosList.appendChild(row);
            bindPaymentLogoRow(row);
        });
    }

    if (paymentLogosNewInput instanceof HTMLInputElement && paymentLogosNewPreview) {
        paymentLogosNewInput.addEventListener('change', () => {
            paymentLogosNewPreview.replaceChildren();
            const files = paymentLogosNewInput.files ? Array.from(paymentLogosNewInput.files) : [];
            files.forEach((file) => {
                if (!file.type.startsWith('image/')) return;
                const img = document.createElement('img');
                img.alt = file.name;
                img.width = 72;
                img.height = 44;
                img.style.cssText = 'object-fit:contain;border:1px solid #d7dbe2;border-radius:6px;background:#fff;padding:4px;';
                const url = URL.createObjectURL(file);
                img.src = url;
                img.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
                paymentLogosNewPreview.appendChild(img);
            });
        });
    }
})();
</script>

@include('admin.partials.tinymce-init')
@endsection
