(function () {
  'use strict';

  const STORAGE_KEY = 'admin.sidebarCollapsed';
  const shell = document.querySelector('[data-admin-shell]');
  if (!shell) return;

  const collapseBtn = document.querySelector('[data-admin-collapse]');
  const mobileBtn = document.querySelector('[data-admin-mobile-toggle]');
  const backdrop = document.querySelector('[data-admin-backdrop]');

  function isDesktop() {
    return window.matchMedia('(min-width: 900px)').matches;
  }

  function applyStored() {
    if (!isDesktop()) return;
    try {
      if (localStorage.getItem(STORAGE_KEY) === '1') {
        shell.classList.add('is-collapsed');
      }
    } catch (_) {
      /* localStorage unavailable */
    }
  }

  function toggleCollapse() {
    const next = !shell.classList.contains('is-collapsed');
    shell.classList.toggle('is-collapsed', next);
    try {
      localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
    } catch (_) {
      /* localStorage unavailable */
    }
  }

  function openMobile() {
    shell.classList.add('is-mobile-open');
  }

  function closeMobile() {
    shell.classList.remove('is-mobile-open');
  }

  if (collapseBtn) collapseBtn.addEventListener('click', toggleCollapse);
  if (mobileBtn) mobileBtn.addEventListener('click', openMobile);
  if (backdrop) backdrop.addEventListener('click', closeMobile);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeMobile();
      closeAllPops();
    }
  });

  window.addEventListener('resize', function () {
    if (isDesktop()) closeMobile();
  });

  document.querySelectorAll('.admin-sidebar a').forEach(function (a) {
    a.addEventListener('click', function () {
      if (!isDesktop()) closeMobile();
    });
  });

  const pops = Array.prototype.slice.call(document.querySelectorAll('[data-admin-pop]'));

  function closeAllPops(except) {
    pops.forEach(function (p) {
      if (p !== except) {
        p.classList.remove('is-open');
        const btn = p.querySelector('[data-admin-pop-btn]');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  pops.forEach(function (pop) {
    const btn = pop.querySelector('[data-admin-pop-btn]');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const open = !pop.classList.contains('is-open');
      closeAllPops(open ? pop : null);
      pop.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  document.addEventListener('click', function (e) {
    const inside = pops.some(function (p) { return p.contains(e.target); });
    if (!inside) closeAllPops();
  });

  applyStored();
})();

(function () {
  'use strict';

  const DEFAULT_SWATCH = '#E3E3E3';
  const HEX_RE = /^#[0-9A-Fa-f]{6}$/;

  function normalizeHex(value) {
    const hex = String(value || '').trim();
    return HEX_RE.test(hex) ? hex.toLowerCase() : '';
  }

  function closeAllColorPickers(except) {
    document.querySelectorAll('[data-color-picker].is-open').forEach(function (picker) {
      if (picker === except) return;
      picker.classList.remove('is-open');
      const trigger = picker.querySelector('[data-color-picker-trigger]');
      const menu = picker.querySelector('[data-color-picker-menu]');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
      if (menu) menu.hidden = true;
    });
  }

  function updatePreviewName(picker) {
    const valueInput = picker.querySelector('[data-color-picker-value]');
    const footerName = picker.querySelector('[data-color-picker-footer-name]');
    if (!footerName) return;
    const name = valueInput ? String(valueInput.value || '').trim() : '';
    footerName.textContent = name !== '' ? name : '—';
  }

  function setSwatch(picker, hex) {
    const normalized = normalizeHex(hex);
    const hidden = picker.querySelector('[data-color-picker-hex]');
    const preview = picker.querySelector('[data-color-picker-preview]');
    const footerDot = picker.querySelector('[data-color-picker-footer-dot]');
    const footerText = picker.querySelector('[data-color-picker-footer-text]');
    const native = picker.querySelector('[data-color-picker-native]');
    const displayColor = normalized || DEFAULT_SWATCH;

    if (hidden) hidden.value = normalized;
    if (hidden && normalized === '') {
      hidden.removeAttribute('value');
    } else if (hidden) {
      hidden.setAttribute('value', normalized);
    }
    if (preview) preview.style.backgroundColor = displayColor;
    if (footerDot) footerDot.style.backgroundColor = displayColor;
    if (footerText) footerText.textContent = normalized ? normalized.toUpperCase() : '—';
    if (native && normalized) native.value = normalized;
    picker.classList.toggle('is-empty', normalized === '');
    updatePreviewName(picker);

    picker.querySelectorAll('[data-color-picker-swatch]').forEach(function (btn) {
      const swatchHex = (btn.getAttribute('data-color-picker-swatch') || '').toLowerCase();
      const selected = swatchHex === normalized;
      btn.classList.toggle('is-selected', selected);
      btn.setAttribute('aria-selected', selected ? 'true' : 'false');
    });
  }

  function initColorPickerState(picker) {
    const hidden = picker.querySelector('[data-color-picker-hex]');
    setSwatch(picker, hidden ? hidden.value : '');
    updatePreviewName(picker);
  }

  function initColorPickers(root) {
    const scope = root || document;
    scope.querySelectorAll('[data-color-picker]').forEach(initColorPickerState);
  }

  if (!window.__adminColorPickerBound) {
    window.__adminColorPickerBound = true;

    document.addEventListener('click', function (e) {
      const target = e.target;
      if (!(target instanceof Element)) return;

      const picker = target.closest('[data-color-picker]');
      if (!picker) {
        closeAllColorPickers(null);
        return;
      }

      if (target.closest('[data-color-picker-trigger]')) {
        e.preventDefault();
        e.stopPropagation();
        const menu = picker.querySelector('[data-color-picker-menu]');
        const trigger = picker.querySelector('[data-color-picker-trigger]');
        const willOpen = !picker.classList.contains('is-open');
        closeAllColorPickers(willOpen ? picker : null);
        picker.classList.toggle('is-open', willOpen);
        if (menu) menu.hidden = !willOpen;
        if (trigger) trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        return;
      }

      if (target.closest('[data-color-picker-swatch]')) {
        e.preventDefault();
        setSwatch(picker, target.closest('[data-color-picker-swatch]').getAttribute('data-color-picker-swatch'));
        return;
      }

      if (target.closest('[data-color-picker-custom]')) {
        e.preventDefault();
        const native = picker.querySelector('[data-color-picker-native]');
        if (native) native.click();
        return;
      }

      if (target.closest('[data-color-picker-reset]')) {
        e.preventDefault();
        setSwatch(picker, '');
        return;
      }

      if (target.closest('[data-color-picker-clear]')) {
        e.preventDefault();
        const valueInput = picker.querySelector('[data-color-picker-value]');
        if (valueInput) valueInput.value = '';
        setSwatch(picker, '');
        return;
      }

      e.stopPropagation();
    });

    document.addEventListener('input', function (e) {
      const target = e.target;
      if (!(target instanceof Element)) return;
      const picker = target.closest('[data-color-picker]');
      if (!picker) return;
      if (target.matches('[data-color-picker-native]')) {
        setSwatch(picker, target.value);
        return;
      }
      if (target.matches('[data-color-picker-value]')) {
        updatePreviewName(picker);
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeAllColorPickers(null);
    });

    document.addEventListener('admin:color-picker-init', function (e) {
      initColorPickers(e.detail && e.detail.root);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initColorPickers(); });
  } else {
    initColorPickers();
  }
})();

(function () {
  'use strict';

  function dismissToast(toast) {
    if (!toast || toast.dataset.dismissed === 'true') {
      return;
    }
    toast.dataset.dismissed = 'true';
    toast.classList.remove('is-visible');
    toast.classList.add('is-leaving');
    window.setTimeout(function () {
      const stack = toast.parentElement;
      toast.remove();
      if (stack && stack.matches('[data-toast-stack]') && !stack.querySelector('[data-toast]')) {
        stack.remove();
      }
    }, 350);
  }

  function initFlashToasts() {
    const stack = document.querySelector('[data-toast-stack]');
    if (!stack) {
      return;
    }

    const toasts = Array.prototype.slice.call(stack.querySelectorAll('[data-toast]'));
    if (!toasts.length) {
      return;
    }

    toasts.forEach(function (toast, index) {
      window.setTimeout(function () {
        toast.classList.add('is-visible');
      }, 80 + index * 120);

      window.setTimeout(function () {
        dismissToast(toast);
      }, 5200 + index * 120);

      const closeBtn = toast.querySelector('[data-toast-close]');
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          dismissToast(toast);
        });
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFlashToasts);
  } else {
    initFlashToasts();
  }
})();
