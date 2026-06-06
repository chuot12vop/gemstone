@php
    use App\Support\FileUploadAccept;

    $name = $name ?? '';
    $previewUrl = $previewUrl ?? null;
    $multiple = !empty($multiple);
    $accept = $accept ?? FileUploadAccept::RASTER;
    $label = $label ?? null;
    $hint = $hint ?? null;
    $dropTitle = $dropTitle ?? ($multiple ? 'Drop images here' : 'Drop image here');
    $dropHint = $dropHint ?? ($multiple ? 'or click to choose files' : 'or click to choose 1 image');
    $variant = in_array($variant ?? 'default', ['default', 'compact'], true) ? ($variant ?? 'default') : 'default';
    $previewFit = in_array($previewFit ?? 'cover', ['cover', 'contain'], true) ? ($previewFit ?? 'cover') : 'cover';
    $maxFiles = max(1, (int) ($maxFiles ?? 5));
    $required = !empty($required);
    $capture = $capture ?? null;
    $dataName = $dataName ?? null;
    $statusText = $statusText ?? null;
    $inputId = $inputId ?? null;
    $previewWidth = (int) ($previewWidth ?? ($variant === 'compact' ? 60 : 200));
    $previewHeight = (int) ($previewHeight ?? ($variant === 'compact' ? 60 : 200));
    $placeholderUrl = $placeholderUrl ?? asset('assets/img/placeholder.svg');
    $showPreview = ($showPreview ?? true) && ($variant !== 'compact' || $multiple);
    $mode = $multiple ? 'multiple' : 'single';
    $resolvedPreview = $previewUrl ?: ($showPreview ? $placeholderUrl : null);
    $clearTargets = $clearTargets ?? null;
@endphp
<div class="file-upload file-upload--{{ $variant }}"
     data-file-upload
     data-mode="{{ $mode }}"
     data-max-files="{{ $maxFiles }}"
     data-preview-fit="{{ $previewFit }}"
     @if($required) data-required="true" @endif
     @if($clearTargets) data-clear-targets="{{ $clearTargets }}" @endif>
    @if($label)
        <span class="file-upload__label">{{ $label }}</span>
    @endif
    @if($hint)
        <small class="file-upload__hint">{{ $hint }}</small>
    @endif
    <div class="file-upload__dropzone" data-file-upload-dropzone tabindex="0" role="button" aria-label="{{ $dropTitle }}">
        <strong class="file-upload__drop-title">{{ $dropTitle }}</strong>
        <small class="file-upload__drop-hint">{{ $dropHint }}</small>
    </div>
    @if($showPreview)
        <div class="file-upload__preview{{ $multiple ? ' file-upload__preview--grid' : '' }}" data-file-upload-preview>
            @if(!$multiple && $resolvedPreview)
                <img class="file-upload__preview-img"
                     data-file-upload-preview-img
                     src="{{ $resolvedPreview }}"
                     alt=""
                     width="{{ $previewWidth }}"
                     height="{{ $previewHeight }}"
                     style="object-fit: {{ $previewFit }};">
            @endif
        </div>
    @elseif($variant === 'compact' && $resolvedPreview)
        <img class="file-upload__preview-img file-upload__preview-img--compact"
             data-file-upload-preview-img
             src="{{ $resolvedPreview }}"
             alt=""
             width="{{ $previewWidth }}"
             height="{{ $previewHeight }}"
             style="object-fit: {{ $previewFit }};">
    @endif
    @if($statusText !== null)
        <p class="file-upload__status" data-file-upload-status aria-live="polite">{{ $statusText }}</p>
    @else
        <p class="file-upload__status" data-file-upload-status aria-live="polite" hidden></p>
    @endif
    <input type="file"
           class="file-upload__input"
           data-file-upload-input
           @if($inputId) id="{{ $inputId }}" @endif
           @if($name) name="{{ $name }}" @endif
           @if($dataName) data-name="{{ $dataName }}" @endif
           accept="{{ $accept }}"
           @if($multiple) multiple @endif
           @if($required) required @endif
           @if($capture) capture="{{ $capture }}" @endif
           hidden>
</div>
