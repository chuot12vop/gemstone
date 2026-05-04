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
