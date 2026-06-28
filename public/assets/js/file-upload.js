(function () {
  'use strict';

  var initialized = new WeakSet();

  function parseAccept(accept) {
    if (!accept) return [];
    return accept.split(',').map(function (part) {
      return part.trim().toLowerCase();
    }).filter(Boolean);
  }

  function fileMatchesAccept(file, acceptList) {
    if (!acceptList.length) return file.type.startsWith('image/');
    var name = (file.name || '').toLowerCase();
    var type = (file.type || '').toLowerCase();
    return acceptList.some(function (token) {
      if (token.startsWith('.')) {
        return name.endsWith(token);
      }
      if (token.endsWith('/*')) {
        var prefix = token.slice(0, -1);
        return type.indexOf(prefix) === 0;
      }
      return type === token;
    });
  }

  function filterFiles(files, acceptList) {
    return Array.prototype.slice.call(files || []).filter(function (file) {
      return fileMatchesAccept(file, acceptList);
    });
  }

  function syncInputFiles(input, files) {
    var dt = new DataTransfer();
    files.forEach(function (file) {
      dt.items.add(file);
    });
    input.files = dt.files;
  }

  function setStatus(upload, message, isError) {
    var status = upload.querySelector('[data-file-upload-status]');
    if (!status) return;
    if (!message) {
      status.hidden = true;
      status.textContent = '';
      status.classList.remove('is-error');
      return;
    }
    status.hidden = false;
    status.textContent = message;
    status.classList.toggle('is-error', !!isError);
  }

  function clearInvalid(upload) {
    var dropzone = upload.querySelector('[data-file-upload-dropzone]');
    if (dropzone) dropzone.classList.remove('is-invalid');
    setStatus(upload, '');
  }

  function clearLinkedInputs(upload) {
    var targets = upload.getAttribute('data-clear-targets');
    if (!targets) return;
    var root = upload.closest('.js-slide-row')
      || upload.closest('.js-product-section-row')
      || upload.closest('.js-section-style-row')
      || upload.closest('form')
      || document;
    targets.split(',').forEach(function (selector) {
      var trimmed = selector.trim();
      if (!trimmed) return;
      root.querySelectorAll(trimmed).forEach(function (el) {
        if (!(el instanceof HTMLInputElement)) return;
        if (el.type === 'checkbox') {
          el.checked = false;
          return;
        }
        el.value = '';
      });
    });
  }

  function setInvalid(upload, message) {
    var dropzone = upload.querySelector('[data-file-upload-dropzone]');
    if (dropzone) {
      dropzone.classList.add('is-invalid');
      dropzone.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    setStatus(upload, message, true);
  }

  function previewObjectUrl(file, img) {
    var url = URL.createObjectURL(file);
    img.src = url;
    img.addEventListener('load', function () {
      URL.revokeObjectURL(url);
    }, { once: true });
  }

  function renderSinglePreview(upload, file) {
    var previewImg = upload.querySelector('[data-file-upload-preview-img]');
    if (!(previewImg instanceof HTMLImageElement)) return;
    if (!file) return;
    previewObjectUrl(file, previewImg);
    if (file.name) {
      setStatus(upload, 'Selected: ' + file.name);
    }
  }

  function createGridItem(upload, file, index, files, acceptList, previewFit) {
    var item = document.createElement('div');
    item.className = 'file-upload__item';

    var img = document.createElement('img');
    img.alt = 'Preview ' + (index + 1);
    img.width = upload.classList.contains('file-upload--compact') ? 60 : 120;
    img.height = upload.classList.contains('file-upload--compact') ? 60 : 120;
    if (previewFit === 'contain') img.classList.add('is-contain');
    previewObjectUrl(file, img);

    var removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'file-upload__remove';
    removeBtn.setAttribute('aria-label', 'Remove image ' + (index + 1));
    removeBtn.textContent = '\u00d7';
    removeBtn.addEventListener('click', function () {
      files.splice(index, 1);
      var input = upload.querySelector('[data-file-upload-input]');
      if (input instanceof HTMLInputElement) syncInputFiles(input, files);
      renderMultiplePreview(upload, files, acceptList);
    });

    item.appendChild(img);
    item.appendChild(removeBtn);
    return item;
  }

  function renderMultiplePreview(upload, files, acceptList) {
    var preview = upload.querySelector('[data-file-upload-preview]');
    var input = upload.querySelector('[data-file-upload-input]');
    if (!(preview instanceof HTMLElement) || !(input instanceof HTMLInputElement)) return;

    var previewFit = upload.getAttribute('data-preview-fit') || 'cover';
    preview.innerHTML = '';
    files.forEach(function (file, index) {
      preview.appendChild(createGridItem(upload, file, index, files, acceptList, previewFit));
    });

    if (files.length > 0) {
      setStatus(upload, files.length + ' file' + (files.length === 1 ? '' : 's') + ' selected');
    } else {
      setStatus(upload, '');
    }
  }

  function initFileUpload(upload) {
    if (!(upload instanceof HTMLElement) || initialized.has(upload)) return;
    initialized.add(upload);

    var input = upload.querySelector('[data-file-upload-input]');
    var dropzone = upload.querySelector('[data-file-upload-dropzone]');
    if (!(input instanceof HTMLInputElement) || !(dropzone instanceof HTMLElement)) return;

    var mode = upload.getAttribute('data-mode') === 'multiple' ? 'multiple' : 'single';
    var maxFiles = parseInt(upload.getAttribute('data-max-files') || '5', 10);
    if (!Number.isFinite(maxFiles) || maxFiles < 1) maxFiles = 5;
    var acceptList = parseAccept(input.getAttribute('accept') || '');
    var files = [];

    function setSingleFile(file) {
      if (!file) return;
      syncInputFiles(input, [file]);
      renderSinglePreview(upload, file);
      clearLinkedInputs(upload);
      clearInvalid(upload);
    }

    function appendFiles(incoming) {
      var valid = filterFiles(incoming, acceptList);
      if (!valid.length) return;

      if (mode === 'single') {
        setSingleFile(valid[0]);
        return;
      }

      valid.forEach(function (file) {
        if (files.length < maxFiles) files.push(file);
      });
      syncInputFiles(input, files);
      renderMultiplePreview(upload, files, acceptList);
      clearLinkedInputs(upload);
      clearInvalid(upload);
    }

    input.addEventListener('change', function () {
      appendFiles(input.files);
      if (mode === 'multiple' && input.files && input.files.length > files.length) {
        setStatus(upload, 'Maximum ' + maxFiles + ' files allowed', true);
      }
    });

    function preventDefaults(event) {
      event.preventDefault();
      event.stopPropagation();
    }

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (eventName) {
      dropzone.addEventListener(eventName, preventDefaults);
    });

    ['dragenter', 'dragover'].forEach(function (eventName) {
      dropzone.addEventListener(eventName, function () {
        dropzone.classList.add('is-dragover');
      });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
      dropzone.addEventListener(eventName, function () {
        dropzone.classList.remove('is-dragover');
      });
    });

    dropzone.addEventListener('drop', function (event) {
      var dt = event.dataTransfer;
      appendFiles(dt ? dt.files : []);
    });

    dropzone.addEventListener('click', function () {
      input.click();
    });

    dropzone.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        input.click();
      }
    });

    var form = upload.closest('form');
    if (form && upload.getAttribute('data-required') === 'true') {
      form.addEventListener('submit', function (event) {
        var hasFile = input.files && input.files.length > 0;
        if (hasFile) return;
        event.preventDefault();
        setInvalid(upload, 'Please choose a file before saving.');
      });
    }
  }

  function initFileUploads(root) {
    var scope = root || document;
    scope.querySelectorAll('[data-file-upload]').forEach(initFileUpload);
  }

  if (!window.__fileUploadBound) {
    window.__fileUploadBound = true;
    document.addEventListener('file-upload:init', function (event) {
      var detail = event.detail || {};
      initFileUploads(detail.root || document);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initFileUploads();
    });
  } else {
    initFileUploads();
  }

  window.initFileUploads = initFileUploads;
})();
