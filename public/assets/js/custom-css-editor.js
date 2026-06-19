(function () {
  'use strict';

  const workspace = document.querySelector('[data-custom-css-workspace]');
  if (!workspace) return;

  const forms = Array.from(workspace.querySelectorAll('[data-custom-css-form]'));
  const tabs = Array.from(workspace.querySelectorAll('[data-viewport-tab]'));
  const editorStatus = workspace.querySelector('[data-editor-status]');
  const editorPath = workspace.querySelector('[data-editor-path]');
  const previewForm = workspace.querySelector('[data-preview-form]');
  const previewUrl = workspace.querySelector('[data-preview-url]');
  const previewFrame = workspace.querySelector('[data-preview-frame]');
  const previewStage = workspace.querySelector('[data-preview-stage]');
  const previewFrameWrap = workspace.querySelector('[data-preview-frame-wrap]');
  const previewSize = workspace.querySelector('[data-preview-size]');
  const refreshButton = workspace.querySelector('[data-refresh-preview]');
  const previewStatus = workspace.querySelector('[data-preview-status]');
  const previewError = workspace.querySelector('[data-preview-error]');
  const savedData = document.querySelector('[data-saved-custom-css]');
  const maxBytes = Number(workspace.dataset.maxBytes || 524288);
  const previewBase = new URL(workspace.dataset.previewOrigin || window.location.origin);
  const encoder = new TextEncoder();
  const editors = {};
  let savedCss = {};
  let activeViewport = forms.length ? forms[0].dataset.customCssForm : 'desktop';
  let updateTimer = null;

  if (!forms.length || !previewFrame || !previewStage || !previewFrameWrap) return;

  forms.forEach(function (form) {
    const viewport = form.dataset.customCssForm;
    editors[viewport] = form.querySelector('[data-custom-css-editor]');
  });

  try {
    savedCss = JSON.parse(savedData ? savedData.textContent : '{}');
  } catch (_) {
    savedCss = {};
  }

  function byteLength(value) {
    return encoder.encode(value).length;
  }

  function formFor(viewport) {
    return forms.find(function (form) { return form.dataset.customCssForm === viewport; });
  }

  function isDirty(viewport) {
    return editors[viewport].value !== String(savedCss[viewport] || '');
  }

  function hasDirtyEditor() {
    return Object.keys(editors).some(isDirty);
  }

  function updateFormState(viewport) {
    const form = formFor(viewport);
    const editor = editors[viewport];
    if (!form || !editor) return;

    const bytes = byteLength(editor.value);
    const tooLarge = bytes > maxBytes;
    const dirty = isDirty(viewport);
    const characterCount = form.querySelector('[data-character-count]');
    const byteCount = form.querySelector('[data-byte-count]');
    const saveButton = form.querySelector('[data-save-css]');
    const tab = tabs.find(function (item) { return item.dataset.viewportTab === viewport; });

    if (characterCount) characterCount.textContent = editor.value.length.toLocaleString() + ' characters';
    if (byteCount) byteCount.textContent = (bytes / 1024).toFixed(1) + ' / 512 KB';
    if (saveButton) saveButton.disabled = tooLarge;
    if (tab) tab.classList.toggle('is-dirty', dirty);

    if (viewport === activeViewport && editorStatus) {
      editorStatus.classList.toggle('is-dirty', dirty && !tooLarge);
      editorStatus.classList.toggle('is-invalid', tooLarge);
      editorStatus.textContent = tooLarge ? 'Over 512 KB' : (dirty ? 'Unsaved changes' : 'Saved');
    }
  }

  function showEditorError(form, message) {
    const error = form.querySelector('[data-editor-error]');
    if (!error) return;
    error.textContent = message || '';
    error.hidden = !message;
  }

  function showPreviewError(message) {
    if (!previewError) return;
    previewError.textContent = message || '';
    previewError.hidden = !message;
  }

  function injectPreviewCss() {
    try {
      const doc = previewFrame.contentDocument;
      if (!doc || !doc.head) throw new Error('Preview document is unavailable.');

      doc.querySelectorAll('[data-custom-theme-stylesheet]').forEach(function (link) {
        link.disabled = true;
      });

      forms.forEach(function (form) {
        const viewport = form.dataset.customCssForm;
        let style = doc.querySelector('[data-custom-theme-preview="' + viewport + '"]');
        if (!style) {
          style = doc.createElement('style');
          style.setAttribute('data-custom-theme-preview', viewport);
          doc.head.appendChild(style);
        }
        if (form.dataset.media) style.media = form.dataset.media;
        else style.removeAttribute('media');
        style.textContent = editors[viewport].value;
      });

      if (previewStatus) previewStatus.textContent = 'Previewing unsaved CSS';
      showPreviewError('');
    } catch (_) {
      if (previewStatus) previewStatus.textContent = 'Preview unavailable';
      showPreviewError('The page left this website or cannot be accessed by the preview.');
    }
  }

  function schedulePreview() {
    window.clearTimeout(updateTimer);
    updateTimer = window.setTimeout(injectPreviewCss, 120);
  }

  function applyPreviewViewport() {
    const form = formFor(activeViewport);
    if (!form) return;

    const width = Number(form.dataset.width);
    const height = Number(form.dataset.height);
    const availableWidth = Math.max(1, previewStage.clientWidth - 16);
    const scale = Math.min(1, availableWidth / width);

    previewFrame.style.width = width + 'px';
    previewFrame.style.height = height + 'px';
    previewFrame.style.transform = 'scale(' + scale + ')';
    previewFrameWrap.style.width = Math.round(width * scale) + 'px';
    previewFrameWrap.style.height = Math.round(height * scale) + 'px';
    if (previewSize) previewSize.textContent = width + ' × ' + height + 'px';
  }

  function activateViewport(viewport, focusTab) {
    if (!editors[viewport]) return;
    activeViewport = viewport;

    forms.forEach(function (form) {
      form.hidden = form.dataset.customCssForm !== viewport;
    });
    tabs.forEach(function (tab) {
      const active = tab.dataset.viewportTab === viewport;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.tabIndex = active ? 0 : -1;
      if (active && focusTab) tab.focus();
    });

    const form = formFor(viewport);
    if (editorPath) editorPath.innerHTML = '<code>' + form.dataset.path + '</code>';
    updateFormState(viewport);
    applyPreviewViewport();
  }

  function resolvePreviewUrl(value) {
    const raw = String(value || '').trim();
    if (!raw) throw new Error('Enter an internal path or URL.');

    const target = new URL(raw, previewBase);
    if (target.origin !== previewBase.origin || !['http:', 'https:'].includes(target.protocol)) {
      throw new Error('Only URLs on this website can be previewed.');
    }
    return target.href;
  }

  function loadPreview() {
    try {
      const target = resolvePreviewUrl(previewUrl ? previewUrl.value : '/');
      showPreviewError('');
      if (previewStatus) previewStatus.textContent = 'Loading preview...';
      previewFrame.src = target;
    } catch (error) {
      if (previewStatus) previewStatus.textContent = 'Invalid preview URL';
      showPreviewError(error.message);
    }
  }

  async function saveForm(form) {
    const viewport = form.dataset.customCssForm;
    const editor = editors[viewport];
    const saveButton = form.querySelector('[data-save-css]');
    if (byteLength(editor.value) > maxBytes) {
      updateFormState(viewport);
      editor.focus();
      return;
    }

    showEditorError(form, '');
    if (saveButton) {
      saveButton.disabled = true;
      saveButton.dataset.label = saveButton.textContent;
      saveButton.textContent = 'Saving...';
    }

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: new FormData(form),
        credentials: 'same-origin'
      });
      const payload = await response.json().catch(function () { return {}; });
      if (!response.ok) {
        const validationError = payload.errors && payload.errors.custom_css && payload.errors.custom_css[0];
        throw new Error(validationError || payload.message || 'Unable to save this stylesheet.');
      }

      savedCss[viewport] = editor.value;
      updateFormState(viewport);
      if (previewStatus) previewStatus.textContent = payload.message || 'CSS saved.';
    } catch (error) {
      showEditorError(form, error.message || 'Unable to save this stylesheet.');
    } finally {
      if (saveButton) {
        saveButton.textContent = saveButton.dataset.label || 'Save CSS';
        saveButton.disabled = byteLength(editor.value) > maxBytes;
      }
    }
  }

  forms.forEach(function (form) {
    const viewport = form.dataset.customCssForm;
    const editor = editors[viewport];

    editor.addEventListener('input', function () {
      updateFormState(viewport);
      schedulePreview();
    });

    editor.addEventListener('keydown', function (event) {
      if (event.key !== 'Tab') return;
      event.preventDefault();
      const start = editor.selectionStart;
      const end = editor.selectionEnd;
      editor.setRangeText('  ', start, end, 'end');
      editor.dispatchEvent(new Event('input', { bubbles: true }));
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      saveForm(form);
    });

    const resetButton = form.querySelector('[data-reset-css]');
    if (resetButton) {
      resetButton.addEventListener('click', function () {
        editor.value = String(savedCss[viewport] || '');
        showEditorError(form, '');
        updateFormState(viewport);
        injectPreviewCss();
        editor.focus();
      });
    }

    updateFormState(viewport);
  });

  tabs.forEach(function (tab, index) {
    tab.addEventListener('click', function () {
      activateViewport(tab.dataset.viewportTab, false);
    });
    tab.addEventListener('keydown', function (event) {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      let nextIndex = index;
      if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
      if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
      if (event.key === 'Home') nextIndex = 0;
      if (event.key === 'End') nextIndex = tabs.length - 1;
      activateViewport(tabs[nextIndex].dataset.viewportTab, true);
    });
  });

  if (previewForm) {
    previewForm.addEventListener('submit', function (event) {
      event.preventDefault();
      loadPreview();
    });
  }

  if (refreshButton) {
    refreshButton.addEventListener('click', function () {
      if (previewStatus) previewStatus.textContent = 'Refreshing preview...';
      try {
        previewFrame.contentWindow.location.reload();
      } catch (_) {
        loadPreview();
      }
    });
  }

  previewFrame.addEventListener('load', injectPreviewCss);
  window.addEventListener('resize', applyPreviewViewport);
  if ('ResizeObserver' in window) new ResizeObserver(applyPreviewViewport).observe(previewStage);

  window.addEventListener('beforeunload', function (event) {
    if (!hasDirtyEditor()) return;
    event.preventDefault();
    event.returnValue = '';
  });

  activateViewport(activeViewport, false);
})();
