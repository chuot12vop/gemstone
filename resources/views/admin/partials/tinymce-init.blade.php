@props([
    'formSelector' => '.stack-form',
])

@once('admin-tinymce-cdn')
@push('head')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>
@endpush
@endonce

@push('scripts')
<script>
(function () {
    const formSelector = @json($formSelector);

    const baseConfig = {
        menubar: false,
        plugins: 'lists link table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | removeformat code',
        branding: false,
        promotion: false,
        convert_urls: false,
        content_style: 'body { font-family: "Source Sans 3", sans-serif; font-size: 15px; line-height: 1.6; }',
    };

    function ensureId(textarea) {
        if (textarea.id) {
            return textarea.id;
        }
        const id = 'rich-text-' + Math.random().toString(36).slice(2, 10);
        textarea.id = id;
        return id;
    }

    window.adminInitRichText = function (root) {
        if (typeof tinymce === 'undefined') {
            return;
        }
        const scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('textarea.js-rich-text').forEach(function (textarea) {
            if (!(textarea instanceof HTMLTextAreaElement)) {
                return;
            }
            const id = ensureId(textarea);
            if (tinymce.get(id)) {
                return;
            }
            const height = parseInt(String(textarea.dataset.richHeight || '280'), 10) || 280;
            tinymce.init({
                ...baseConfig,
                target: textarea,
                height: height,
            });
        });
    };

    window.adminRemoveRichText = function (root) {
        if (typeof tinymce === 'undefined' || !root) {
            return;
        }
        root.querySelectorAll('textarea.js-rich-text').forEach(function (textarea) {
            const id = textarea.id;
            if (id && tinymce.get(id)) {
                tinymce.remove('#' + id);
            }
        });
    };

    function bindFormSave() {
        document.querySelectorAll(formSelector).forEach(function (form) {
            form.addEventListener('submit', function () {
                tinymce.triggerSave();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            window.adminInitRichText();
            bindFormSave();
        });
    } else {
        window.adminInitRichText();
        bindFormSave();
    }
})();
</script>
@endpush
