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
        <legend>Header visibility</legend>
        <label class="checkbox">
            <input type="checkbox" name="show_site_logo" value="1" @checked(old('show_site_logo', ($settings['show_site_logo'] ?? '1') === '1'))>
            Show website logo
        </label>
        <label class="checkbox">
            <input type="checkbox" name="show_site_name" value="1" @checked(old('show_site_name', ($settings['show_site_name'] ?? '1') === '1'))>
            Show site name next to logo
        </label>
        <label class="checkbox">
            <input type="checkbox" name="hide_site_name_mobile" value="1" @checked(old('hide_site_name_mobile', ($settings['hide_site_name_mobile'] ?? '0') === '1'))>
            Hide site name on mobile
        </label>
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Checkout — shipping &amp; tax</legend>
        <div class="form-grid">
            <label>
                Flat shipping fee (USD)
                <input type="number" name="shipping_flat_fee_usd" min="0" step="0.01" value="{{ old('shipping_flat_fee_usd', $settings['shipping_flat_fee_usd'] ?? '5.99') }}">
            </label>
            <label>
                Free shipping threshold (USD)
                <input type="number" name="free_shipping_threshold_usd" min="0.01" step="0.01" value="{{ old('free_shipping_threshold_usd', $settings['free_shipping_threshold_usd'] ?? '100') }}">
            </label>
            <label>
                Free shipping from item count (0 = off)
                <input type="number" name="free_shipping_min_items" min="0" step="1" value="{{ old('free_shipping_min_items', $settings['free_shipping_min_items'] ?? '0') }}">
                <small>Example: 2 = free shipping when cart has 2+ items.</small>
            </label>
            <label>
                Tax percent (%)
                <input type="number" name="checkout_tax_percent" min="0" max="100" step="0.01" value="{{ old('checkout_tax_percent', $settings['checkout_tax_percent'] ?? '8') }}">
                <small>Applied to subtotal after discount plus shipping.</small>
            </label>
        </div>
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Website logo</legend>
        @include('partials.file-upload', [
            'name' => 'site_logo',
            'dropTitle' => 'Drop logo image here',
            'previewUrl' => old('site_logo_preview', \App\Support\PublicAssetUrl::to($settings['site_logo'] ?? '') ?: null),
            'previewWidth' => 160,
            'previewHeight' => 160,
        ])
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
        @include('partials.file-upload', [
            'name' => 'welcome_popup_image',
            'label' => 'Background image (faint behind card)',
            'dropTitle' => 'Drop welcome banner image here',
            'previewUrl' => old('welcome_popup_image_preview', $welcomePopup['image_url'] ?? asset('assets/img/welcome-popup.png')),
            'previewWidth' => 280,
            'previewHeight' => 160,
        ])
        @error('welcome_popup_image')
            <p style="margin:8px 0 0;color:#b33a3a;">{{ $message }}</p>
        @enderror
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Storefront</legend>
        <label>
            WhatsApp contact number
            <input type="text" name="contact_whatsapp_phone" value="{{ old('contact_whatsapp_phone', $settings['contact_whatsapp_phone'] ?? '') }}" placeholder="+849xxxxxxxx">
            <small style="color:#5c6470;">Used for the floating WhatsApp button on the shop.</small>
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
        @include('partials.file-upload', [
            'name' => 'footer_background',
            'label' => 'Footer background image',
            'previewUrl' => !empty($settings['footer_background']) ? \App\Support\PublicAssetUrl::to($settings['footer_background']) : null,
            'previewWidth' => 240,
            'previewHeight' => 120,
        ])
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
                    <div style="flex:0 0 120px;">
                        @include('partials.file-upload', [
                            'name' => "payment_logos[{$i}][image]",
                            'variant' => 'compact',
                            'previewUrl' => $logo['src'] ?? null,
                            'previewFit' => 'contain',
                            'accept' => \App\Support\FileUploadAccept::WITH_SVG,
                            'dropTitle' => 'Drop icon',
                            'dropHint' => 'or click',
                            'previewWidth' => 72,
                            'previewHeight' => 44,
                        ])
                    </div>
                    <div style="flex:1 1 200px;min-width:180px;">
                        <label>
                            Label (accessibility)
                            <input type="text" name="payment_logos[{{ $i }}][label]" value="{{ old('payment_logos.'.$i.'.label', $logo['label'] ?? '') }}" maxlength="120" placeholder="e.g. Visa">
                        </label>
                    </div>
                    <div style="flex:0 0 auto;align-self:center;">
                        <button class="btn-admin" type="button" data-action="remove-payment-logo">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>
        <div style="margin-top:14px;">
            @include('partials.file-upload', [
                'name' => 'payment_logos_new[]',
                'label' => 'Add new icons (multiple files)',
                'multiple' => true,
                'accept' => \App\Support\FileUploadAccept::WITH_SVG,
                'previewFit' => 'contain',
                'inputId' => 'payment-logos-new-input',
            ])
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

<template id="payment-logo-row-template">
    <div class="js-payment-logo-row form-fieldset" draggable="true" style="margin-top:12px;padding:12px;border:1px solid #e2e6ec;border-radius:10px;background:#fff;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-start;">
        <input type="hidden" class="js-payment-logo-path" name="" value="">
        <div style="flex:0 0 120px;">
            @include('partials.file-upload', [
                'name' => '',
                'dataName' => 'payment-logo-image',
                'variant' => 'compact',
                'previewFit' => 'contain',
                'accept' => \App\Support\FileUploadAccept::WITH_SVG,
                'dropTitle' => 'Drop icon',
                'dropHint' => 'or click',
                'previewWidth' => 72,
                'previewHeight' => 44,
                'required' => true,
            ])
        </div>
        <div style="flex:1 1 200px;min-width:180px;">
            <label>
                Label (accessibility)
                <input type="text" data-name="payment-logo-label" maxlength="120" placeholder="e.g. Visa">
            </label>
        </div>
        <div style="flex:0 0 auto;align-self:center;">
            <button class="btn-admin" type="button" data-action="remove-payment-logo">Remove</button>
        </div>
    </div>
</template>

<script>
(() => {
    const paymentLogosList = document.getElementById('payment-logos-list');
    const addPaymentLogoBtn = document.getElementById('add-payment-logo');
    const paymentLogoTemplate = document.getElementById('payment-logo-row-template');

    const reindexPaymentLogoRows = () => {
        if (!paymentLogosList) return;
        paymentLogosList.querySelectorAll('.js-payment-logo-row').forEach((row, index) => {
            const pathInput = row.querySelector('.js-payment-logo-path');
            const labelInput = row.querySelector('[data-name="payment-logo-label"]');
            const fileInput = row.querySelector('[data-file-upload-input]');
            if (pathInput instanceof HTMLInputElement) pathInput.name = `payment_logos[${index}][path]`;
            if (labelInput instanceof HTMLInputElement) labelInput.name = `payment_logos[${index}][label]`;
            if (fileInput instanceof HTMLInputElement) fileInput.name = `payment_logos[${index}][image]`;
        });
    };

    const bindPaymentLogoRow = (row) => {
        const removeBtn = row.querySelector('[data-action="remove-payment-logo"]');
        if (removeBtn instanceof HTMLButtonElement) {
            removeBtn.addEventListener('click', () => {
                row.remove();
                reindexPaymentLogoRows();
            });
        }

        row.addEventListener('dragstart', (event) => {
            row.classList.add('is-dragging');
            if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
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
            if (dragIndex < hoverIndex) row.after(dragging);
            else row.before(dragging);
        });
    };

    if (paymentLogosList) {
        paymentLogosList.querySelectorAll('.js-payment-logo-row').forEach(bindPaymentLogoRow);
    }

    if (addPaymentLogoBtn && paymentLogosList && paymentLogoTemplate) {
        addPaymentLogoBtn.addEventListener('click', () => {
            const fragment = paymentLogoTemplate.content.cloneNode(true);
            const row = fragment.querySelector('.js-payment-logo-row');
            if (!row) return;
            paymentLogosList.appendChild(row);
            bindPaymentLogoRow(row);
            reindexPaymentLogoRows();
            document.dispatchEvent(new CustomEvent('file-upload:init', { detail: { root: row } }));
        });
    }
})();
</script>

@include('admin.partials.tinymce-init')
@endsection
