@php
    $placeholder = $placeholder ?? 'Value';
    $valueColor = $valueColor ?? '';
    $valueSwatch = $valueSwatch ?? '';
    $nameColor = $nameColor ?? null;
    $nameSwatch = $nameSwatch ?? null;
    $dataNameColor = $dataNameColor ?? null;
    $dataNameSwatch = $dataNameSwatch ?? null;
    $defaultSwatch = '#E3E3E3';
    $presetColors = [
        '#E3E3E3', '#F8C8C8', '#F5D6C6', '#FFF4CC', '#D4EDDA', '#D6E9FF', '#E8DAEF', '#EBD4F5',
        '#6B6B6B', '#E74C3C', '#8B4513', '#6B7F3B', '#228B22', '#2563EB', '#008080', '#6B21A8',
    ];
    $normalizedSwatch = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $valueSwatch) ? strtolower($valueSwatch) : '';
@endphp
<div class="admin-color-picker{{ $normalizedSwatch === '' ? ' is-empty' : '' }}" data-color-picker>
    <div class="admin-color-picker__bar">
        <button type="button"
                class="admin-color-picker__trigger"
                data-color-picker-trigger
                aria-expanded="false"
                aria-haspopup="listbox"
                aria-label="Choose color">
            <span class="admin-color-picker__preview"
                  data-color-picker-preview
                  style="background-color: {{ $normalizedSwatch !== '' ? $normalizedSwatch : $defaultSwatch }};"></span>
            <svg class="admin-color-picker__chevron" width="10" height="6" viewBox="0 0 10 6" aria-hidden="true">
                <path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <input type="text"
               class="admin-color-picker__value"
               value="{{ $valueColor }}"
               placeholder="{{ $placeholder }}"
               data-color-picker-value
               @if($nameColor) name="{{ $nameColor }}" @endif
               @if($dataNameColor) data-name="{{ $dataNameColor }}" @endif>
        <button type="button"
                class="admin-color-picker__clear"
                data-color-picker-clear
                aria-label="Clear color">
            <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M2 4h12M5.5 4V3a1 1 0 011-1h3a1 1 0 011 1v1M6 7v5M10 7v5M3.5 4l.7 9.2a1 1 0 001 .8h5.6a1 1 0 001-.8L12.5 4" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
    <div class="admin-color-picker__menu" data-color-picker-menu hidden>
        <span class="admin-color-picker__label">Color</span>
        <div class="admin-color-picker__grid" role="listbox" aria-label="Preset colors">
            @foreach($presetColors as $hex)
                <button type="button"
                        class="admin-color-picker__swatch{{ $normalizedSwatch === strtolower($hex) ? ' is-selected' : '' }}"
                        data-color-picker-swatch="{{ $hex }}"
                        style="background-color: {{ $hex }};"
                        role="option"
                        aria-selected="{{ $normalizedSwatch === strtolower($hex) ? 'true' : 'false' }}"
                        aria-label="{{ $hex }}"></button>
            @endforeach
        </div>
        <button type="button" class="admin-color-picker__custom" data-color-picker-custom>Custom</button>
        <input type="color"
               class="admin-color-picker__native"
               data-color-picker-native
               value="{{ $normalizedSwatch !== '' ? $normalizedSwatch : $defaultSwatch }}"
               tabindex="-1"
               aria-hidden="true">
        <button type="button" class="admin-color-picker__reset" data-color-picker-reset>
            <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M3 8a5 5 0 019.3-1.5M13 8a5 5 0 01-9.3 1.5M3 3v3h3M13 13v-3h-3" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Reset
        </button>
        <div class="admin-color-picker__footer">
            <span class="admin-color-picker__footer-label">Preview —</span>
            <span class="admin-color-picker__footer-name" data-color-picker-footer-name>{{ $valueColor !== '' ? $valueColor : '—' }}</span>
            <span class="admin-color-picker__footer-dot"
                  data-color-picker-footer-dot
                  style="background-color: {{ $normalizedSwatch !== '' ? $normalizedSwatch : $defaultSwatch }};"></span>
            <span class="admin-color-picker__footer-text" data-color-picker-footer-text>{{ $normalizedSwatch !== '' ? strtoupper($normalizedSwatch) : '—' }}</span>
        </div>
    </div>
    <input type="hidden"
           data-color-picker-hex
           value="{{ $normalizedSwatch }}"
           @if($nameSwatch) name="{{ $nameSwatch }}" @endif
           @if($dataNameSwatch) data-name="{{ $dataNameSwatch }}" @endif>
</div>
