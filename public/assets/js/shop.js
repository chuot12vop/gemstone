(function () {
  const btn = document.querySelector('[data-nav-toggle]');
  const panel = document.querySelector('[data-nav-panel]');
  if (btn && panel) {
    btn.addEventListener('click', function () {
      const open = panel.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  const revealItems = document.querySelectorAll('.reveal-on-scroll');
  if (revealItems.length) {
    if ('IntersectionObserver' in globalThis) {
      const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        });
      }, { threshold: 0.2 });
      revealItems.forEach(function (item) { observer.observe(item); });
    } else {
      revealItems.forEach(function (item) { item.classList.add('is-visible'); });
    }
  }

  initProductGallery();
  initProductDescriptionToggle();
  initProductAttributesAccordion();
  initProductCtaBar();
  initProductCtaQtySync();
  initSiteHeaderHeight();
  initCatalogMega();
  initCatalogFiltersCollapse();
  initCatalogCategoryFilter();
  initProductUpsellBundle();
  initHomeSlider();
  initWelcomePopup();
  initContactForm();
})();

function initProductUpsellBundle() {
  const root = document.querySelector('[data-product-upsell]');
  if (!root) {
    return;
  }

  const form = root.querySelector('[data-product-upsell-form]');
  const totalSale = root.querySelector('[data-upsell-total-sale]');
  const totalWas = root.querySelector('[data-upsell-total-was]');
  const symbol = root.dataset.currencySymbol || '$';
  const rate = parseFloat(root.dataset.currencyRate || '1', 10) || 1;
  const code = root.dataset.currencyCode || 'USD';

  const formatMoney = (usd) => {
    const local = usd * rate;
    return symbol + local.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (code !== 'USD' ? ' ' + code : '');
  };

  const syncRowInputs = (check) => {
    const row = check.closest('.product-upsell__item');
    if (!row) {
      return;
    }
    const productIdInput = row.querySelector('[data-upsell-product-id]');
    const qtyInput = row.querySelector('[data-upsell-qty]');
    const enabled = check.checked && !check.disabled;
    if (productIdInput instanceof HTMLInputElement) {
      productIdInput.disabled = !enabled;
    }
    if (qtyInput instanceof HTMLInputElement) {
      qtyInput.disabled = !enabled;
    }
  };

  const updateTotals = () => {
    let saleUsd = 0;
    let baseUsd = 0;
    root.querySelectorAll('[data-upsell-check]').forEach((check) => {
      if (!(check instanceof HTMLInputElement) || !check.checked) {
        return;
      }
      const base = parseFloat(check.dataset.baseUsd || '0', 10) || 0;
      const cart = parseFloat(check.dataset.cartUsd || check.dataset.displayUsd || '0', 10) || 0;
      saleUsd += cart;
      baseUsd += base;
    });
    if (totalSale) {
      totalSale.textContent = formatMoney(saleUsd);
    }
    if (totalWas) {
      if (baseUsd > saleUsd + 0.001) {
        totalWas.textContent = formatMoney(baseUsd);
        totalWas.hidden = false;
      } else {
        totalWas.hidden = true;
      }
    }
  };

  root.querySelectorAll('[data-upsell-check]').forEach((check) => {
    if (!(check instanceof HTMLInputElement)) {
      return;
    }
    syncRowInputs(check);
    check.addEventListener('change', () => {
      syncRowInputs(check);
      updateTotals();
    });
  });

  if (form instanceof HTMLFormElement) {
    form.addEventListener('submit', () => {
      root.querySelectorAll('[data-upsell-check]').forEach((check) => {
        if (check instanceof HTMLInputElement) {
          syncRowInputs(check);
        }
      });
    });
  }

  updateTotals();
}

function initSiteHeaderHeight() {
  const header = document.querySelector('.site-header');
  if (!header) {
    return;
  }
  function sync() {
    document.documentElement.style.setProperty('--site-header-height', header.offsetHeight + 'px');
  }
  sync();
  if (typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(sync).observe(header);
  } else {
    window.addEventListener('resize', sync);
  }
}

function initCatalogCategoryFilter() {
  const form = document.querySelector('[data-catalog-filter-form]');
  const categorySelect = document.querySelector('[data-catalog-category-filter]');
  if (!form || !categorySelect) {
    return;
  }

  categorySelect.addEventListener('change', function () {
    const brandSelect = form.querySelector('[name="brand"]');
    if (brandSelect instanceof HTMLSelectElement) {
      brandSelect.value = '';
    }
    form.requestSubmit();
  });
}

function initCatalogFiltersCollapse() {
  const details = document.querySelector('[data-catalog-filters]');
  if (!details) {
    return;
  }
  const mq = window.matchMedia('(min-width: 820px)');
  function sync() {
    if (mq.matches) {
      details.open = true;
      return;
    }
    if (!details.hasAttribute('data-filters-active')) {
      details.open = false;
    }
  }
  if (typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', sync);
  } else {
    mq.addListener(sync);
  }
  sync();
}

function initWelcomePopup() {
  const root = document.querySelector('[data-welcome-popup]');
  if (!root) {
    return;
  }

  let openTimer = null;
  let dismissed = false;
  let opened = false;

  function openPopup() {
    if (dismissed || opened) {
      return;
    }
    opened = true;
    root.hidden = false;
    root.setAttribute('aria-hidden', 'false');
    document.body.classList.add('welcome-popup-open');
    const email = root.querySelector('#welcome-popup-email');
    if (email) {
      email.focus();
    }
  }

  function closePopup() {
    dismissed = true;
    root.hidden = true;
    root.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('welcome-popup-open');
    if (openTimer !== null) {
      clearTimeout(openTimer);
      openTimer = null;
    }
  }

  function scheduleOpen() {
    if (openTimer !== null || dismissed || opened) {
      return;
    }
    const delayMs = (parseInt(String(root.getAttribute('data-welcome-delay') || '10'), 10) || 10) * 1000;
    openTimer = setTimeout(openPopup, delayMs);
  }

  window.addEventListener('scroll', scheduleOpen, { passive: true, once: true });
  window.addEventListener('wheel', scheduleOpen, { passive: true, once: true });
  window.addEventListener('touchmove', scheduleOpen, { passive: true, once: true });

  root.querySelectorAll('[data-welcome-close]').forEach(function (el) {
    el.addEventListener('click', closePopup);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !root.hidden) {
      closePopup();
    }
  });

  const form = root.querySelector('[data-welcome-form]');
  const success = root.querySelector('[data-welcome-success]');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
      }
      fetch(form.action, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new FormData(form),
      })
        .then(function (res) {
          if (!res.ok) {
            throw new Error('Request failed');
          }
          return res.json();
        })
        .then(function () {
          form.hidden = true;
          if (success) {
            success.hidden = false;
          }
          setTimeout(closePopup, 2200);
        })
        .catch(function () {
          if (submitBtn) {
            submitBtn.disabled = false;
          }
          window.alert('Please enter a valid email and try again.');
        });
    });
  }
}

function initContactForm() {
  const form = document.querySelector('[data-contact-form]');
  if (!form) {
    return;
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    const submitBtn = form.querySelector('button[type="submit"]');
    const feedback = form.querySelector('[data-contact-feedback]');
    const csrfInput = form.querySelector('[name="_token"]');

    if (submitBtn) {
      submitBtn.disabled = true;
    }
    if (feedback) {
      feedback.hidden = true;
      feedback.classList.remove('is-ok', 'is-err');
    }

    const headers = {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-Contact-Form': '1',
    };
    if (csrfInput && csrfInput.value) {
      headers['X-CSRF-TOKEN'] = csrfInput.value;
    }

    const requests = [];

    requests.push(
      fetch(form.action, {
        method: 'POST',
        headers: headers,
        credentials: 'same-origin',
        body: new FormData(form),
      }).then(function (res) {
        return res.text().then(function (text) {
          var data = {};
          if (text) {
            try {
              data = JSON.parse(text);
            } catch (parseErr) {
              throw new Error('Unexpected server response. Please reload the page and try again.');
            }
          }
          if (!res.ok) {
            var message = data.message
              || (data.errors && Object.values(data.errors).flat()[0])
              || 'Request failed';
            throw new Error(message);
          }
          return data;
        });
      })
    );

    Promise.all(requests)
      .then(function (results) {
        const data = results[results.length - 1] || {};
        form.reset();
        if (feedback) {
          feedback.textContent = data.message || 'Thanks! Our team will reach out shortly.';
          feedback.classList.add('is-ok');
          feedback.hidden = false;
        }
      })
      .catch(function (err) {
        if (feedback) {
          feedback.textContent = err && err.message
            ? err.message
            : 'Something went wrong. Please check your details and try again.';
          feedback.classList.add('is-err');
          feedback.hidden = false;
        }
      })
      .finally(function () {
        if (submitBtn) {
          submitBtn.disabled = false;
        }
      });
  });
}

function initHomeSlider() {
  document.querySelectorAll('[data-home-slider]').forEach(function (root) {
    const viewport = root.querySelector('[data-slider-viewport]') || root.querySelector('.home-hero__viewport');
    const track = root.querySelector('[data-home-slider-track]');
    const slides = Array.from(root.querySelectorAll('[data-slide]'));
    const dots = Array.from(root.querySelectorAll('[data-dot]'));
    if (!viewport || !track || slides.length === 0) {
      return;
    }

    let active = 0;
    const ms = parseInt(String(root.getAttribute('data-slide-interval') || '4000'), 10) || 4000;
    let timer = null;
    let touchStartX = 0;
    let touchStartY = 0;
    let dragOffset = 0;
    let dragging = false;
    let lockAxis = null;
    let cachedSlideWidth = 0;
    let lastInnerWidth = window.innerWidth;
    let rafId = null;

    function slidesPerView() {
      const desktopBp = parseInt(String(root.getAttribute('data-slide-breakpoint') || '768'), 10) || 768;
      const mobile = parseInt(String(root.getAttribute('data-slides-mobile') || '1'), 10) || 1;
      const desktop = parseInt(String(root.getAttribute('data-slides-desktop') || String(mobile)), 10) || mobile;
      const tabletAttr = root.getAttribute('data-slides-tablet');
      if (tabletAttr === null || tabletAttr === '') {
        return globalThis.matchMedia('(min-width: ' + desktopBp + 'px)').matches ? desktop : mobile;
      }
      const tabletBp = parseInt(String(root.getAttribute('data-slide-breakpoint-tablet') || '640'), 10) || 640;
      const tablet = parseInt(String(tabletAttr), 10) || mobile;
      if (globalThis.matchMedia('(min-width: ' + desktopBp + 'px)').matches) {
        return desktop;
      }
      if (globalThis.matchMedia('(min-width: ' + tabletBp + 'px)').matches) {
        return tablet;
      }
      return mobile;
    }

    function maxActiveIndex() {
      return Math.max(0, slides.length - slidesPerView());
    }

    function canAdvance() {
      return slides.length > slidesPerView();
    }

    function normalizeIndex(idx) {
      const max = maxActiveIndex();
      if (max === 0) {
        return 0;
      }
      if (idx < 0) {
        return max;
      }
      if (idx > max) {
        return 0;
      }
      return idx;
    }

    function slideWidth() {
      return (viewport.clientWidth || 1) / slidesPerView();
    }

    function refreshSlideWidth() {
      cachedSlideWidth = slideWidth();
    }

    function syncStaticState() {
      const isStatic = !canAdvance();
      root.classList.toggle('is-static', isStatic);
      if (isStatic) {
        stop();
        active = 0;
        track.style.transform = 'translate3d(0, 0, 0)';
      }
    }

    function applyPosition(animate) {
      if (animate === undefined) animate = true;
      const w = dragging ? cachedSlideWidth : slideWidth();
      const x = Math.round(-(active * w) + dragOffset);
      track.style.transition = animate && !dragging ? '' : 'none';
      track.style.transform = 'translate3d(' + x + 'px, 0, 0)';
    }

    function queueApplyPosition() {
      if (rafId !== null) return;
      rafId = requestAnimationFrame(function () {
        rafId = null;
        if (dragging) applyPosition(false);
      });
    }

    function cancelQueuedPosition() {
      if (rafId !== null) {
        cancelAnimationFrame(rafId);
        rafId = null;
      }
    }

    function finishTouchDrag() {
      if (!dragging) return;
      cancelQueuedPosition();
      const wasHorizontal = lockAxis === true;
      dragging = false;
      lockAxis = null;
      if (!wasHorizontal) {
        dragOffset = 0;
        setActive(active, true);
        start();
        return;
      }
      const threshold = cachedSlideWidth * 0.16;
      if (dragOffset < -threshold) {
        setActive(active + 1, true);
      } else if (dragOffset > threshold) {
        setActive(active - 1, true);
      } else {
        setActive(active, true);
      }
      start();
    }

    function stop() {
      if (timer !== null) {
        clearInterval(timer);
        timer = null;
      }
    }

    function start() {
      stop();
      if (canAdvance()) {
        timer = setInterval(function () {
          setActive(active + 1, true);
        }, ms);
      }
    }

    function updateSlideAria() {
      const per = slidesPerView();
      slides.forEach(function (el, i) {
        const visible = i >= active && i < active + per;
        el.classList.toggle('is-active', i === active);
        if (slides.length > 1) {
          el.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }
      });
    }

    function setActive(idx, animate) {
      if (animate === undefined) animate = true;
      active = normalizeIndex(idx);
      dragOffset = 0;
      dragging = false;
      updateSlideAria();
      dots.forEach(function (d, i) {
        d.classList.toggle('is-active', i === active);
        d.setAttribute('aria-selected', i === active ? 'true' : 'false');
      });
      applyPosition(animate);
      syncStaticState();
    }

    dots.forEach(function (d) {
      d.addEventListener('click', function () {
        const to = parseInt(String(d.getAttribute('data-slide-to') || '0'), 10);
        if (!Number.isNaN(to)) {
          setActive(to, true);
          start();
        }
      });
    });

    root.querySelector('[data-slider-prev]')?.addEventListener('click', function () {
      setActive(active - 1, true);
      start();
    });
    root.querySelector('[data-slider-next]')?.addEventListener('click', function () {
      setActive(active + 1, true);
      start();
    });

    viewport.addEventListener('touchstart', function (e) {
      if (!canAdvance() || e.touches.length !== 1) return;
      stop();
      refreshSlideWidth();
      dragging = true;
      lockAxis = null;
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;
      dragOffset = 0;
      track.style.transition = 'none';
    }, { passive: true });

    viewport.addEventListener('touchmove', function (e) {
      if (!dragging || e.touches.length !== 1) return;
      const x = e.touches[0].clientX;
      const y = e.touches[0].clientY;
      const dx = x - touchStartX;
      const dy = y - touchStartY;
      if (lockAxis === null) {
        if (Math.abs(dx) < 6 && Math.abs(dy) < 6) return;
        lockAxis = Math.abs(dx) >= Math.abs(dy);
      }
      if (!lockAxis) return;
      e.preventDefault();
      dragOffset = dx;
      queueApplyPosition();
    }, { passive: false });

    viewport.addEventListener('touchend', finishTouchDrag, { passive: true });
    viewport.addEventListener('touchcancel', finishTouchDrag, { passive: true });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    window.addEventListener('resize', function () {
      if (dragging) return;
      const w = window.innerWidth;
      if (Math.abs(w - lastInnerWidth) < 2) return;
      lastInnerWidth = w;
      refreshSlideWidth();
      active = normalizeIndex(active);
      setActive(active, false);
      start();
    });

    refreshSlideWidth();
    setActive(0, false);
    if (canAdvance()) {
      start();
    }
  });
}

function initCatalogMega() {
  const li = document.querySelector('[data-nav-mega]');
  const trigger = document.querySelector('[data-catalog-trigger]');
  const navToggle = document.querySelector('[data-nav-toggle]');
  const navPanel = document.querySelector('[data-nav-panel]');
  if (!li || !trigger) return;

  function closeMega() {
    li.classList.remove('is-mega-open');
    trigger.setAttribute('aria-expanded', 'false');
  }

  function isMobileNav() {
    return globalThis.matchMedia('(max-width: 1023px)').matches;
  }

  trigger.addEventListener('click', function (e) {
    if (!isMobileNav()) return;
    if (!li.classList.contains('is-mega-open')) {
      e.preventDefault();
      li.classList.add('is-mega-open');
      trigger.setAttribute('aria-expanded', 'true');
    }
  });

  if (navToggle && navPanel) {
    navToggle.addEventListener('click', function () {
      window.setTimeout(function () {
        if (!navPanel.classList.contains('is-open')) {
          closeMega();
        }
      }, 0);
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMega();
  });

  globalThis.matchMedia('(min-width: 1024px)').addEventListener('change', function (e) {
    if (e.matches) closeMega();
  });
}

function initProductGallery() {
  const gallery = document.querySelector('[data-pd-gallery]');
  if (!gallery) return;

  const mainWrap = gallery.querySelector('[data-pd-zoom]');
  const mainImg = gallery.querySelector('[data-pd-main]');
  const lens = gallery.querySelector('[data-pd-lens]');
  const thumbs = Array.from(gallery.querySelectorAll('[data-pd-thumb]'));
  const prevBtn = gallery.querySelector('[data-pd-prev]');
  const nextBtn = gallery.querySelector('[data-pd-next]');

  if (!mainImg || !mainWrap) return;

  const LENS_RADIUS = 10;
  const LENS_ZOOM = 2;

  function canLensZoom() {
    return matchMedia('(hover: hover) and (pointer: fine)').matches;
  }

  function updateLensPosition(e) {
    if (!lens || !mainWrap.classList.contains('is-zooming')) return;
    const rect = mainWrap.getBoundingClientRect();
    const x = Math.min(rect.width, Math.max(0, e.clientX - rect.left));
    const y = Math.min(rect.height, Math.max(0, e.clientY - rect.top));
    lens.style.left = (x - LENS_RADIUS) + 'px';
    lens.style.top = (y - LENS_RADIUS) + 'px';
    lens.style.backgroundSize = (rect.width * LENS_ZOOM) + 'px ' + (rect.height * LENS_ZOOM) + 'px';
    lens.style.backgroundPosition = (-(x * LENS_ZOOM - LENS_RADIUS)) + 'px ' + (-(y * LENS_ZOOM - LENS_RADIUS)) + 'px';
  }

  const images = thumbs.length
    ? thumbs.map(function (t) { return t.dataset.pdSrc; })
    : [mainImg.getAttribute('src')];
  let currentIndex = 0;
  let autoplayId = null;
  const AUTOPLAY_DELAY = 5000;

  function setActive(index, fromAutoplay) {
    if (fromAutoplay === undefined) fromAutoplay = false;
    if (index < 0) index = images.length - 1;
    if (index >= images.length) index = 0;
    currentIndex = index;
    const src = images[index];
    if (!src) return;

    mainWrap.classList.add('is-fading');
    const next = new Image();
    next.onload = function () {
      mainImg.src = src;
      mainWrap.classList.remove('is-fading');
      mainWrap.style.setProperty('--pd-zoom-image', 'url("' + src + '")');
    };
    next.onerror = function () {
      mainImg.src = src;
      mainWrap.classList.remove('is-fading');
    };
    next.src = src;

    thumbs.forEach(function (t, i) {
      t.classList.toggle('is-active', i === index);
    });

    if (!fromAutoplay) restartAutoplay();
  }

  function stopAutoplay() {
    if (autoplayId) {
      clearInterval(autoplayId);
      autoplayId = null;
    }
  }

  function startAutoplay() {
    if (images.length <= 1) return;
    stopAutoplay();
    autoplayId = setInterval(function () {
      setActive(currentIndex + 1, true);
    }, AUTOPLAY_DELAY);
  }

  function restartAutoplay() {
    startAutoplay();
  }

  if (images[0]) {
    mainWrap.style.setProperty('--pd-zoom-image', 'url("' + images[0] + '")');
  }

  thumbs.forEach(function (t, i) {
    t.addEventListener('click', function () { setActive(i, false); });
  });

  if (prevBtn) prevBtn.addEventListener('click', function () { setActive(currentIndex - 1, false); });
  if (nextBtn) nextBtn.addEventListener('click', function () { setActive(currentIndex + 1, false); });

  if (images.length <= 1) {
    if (prevBtn) prevBtn.classList.add('is-hidden');
    if (nextBtn) nextBtn.classList.add('is-hidden');
  }

  mainWrap.addEventListener('mouseenter', function (e) {
    if (!canLensZoom()) return;
    mainWrap.classList.add('is-zooming');
    stopAutoplay();
    updateLensPosition(e);
  });
  mainWrap.addEventListener('mouseleave', function () {
    mainWrap.classList.remove('is-zooming');
    restartAutoplay();
  });
  mainWrap.addEventListener('mousemove', function (e) {
    if (!canLensZoom()) return;
    updateLensPosition(e);
  });

  let touchStartX = null;
  mainWrap.addEventListener('touchstart', function (e) {
    if (e.touches.length === 1) touchStartX = e.touches[0].clientX;
  }, { passive: true });
  mainWrap.addEventListener('touchend', function (e) {
    if (touchStartX === null) return;
    const dx = (e.changedTouches[0]?.clientX ?? touchStartX) - touchStartX;
    if (Math.abs(dx) > 40) {
      setActive(dx < 0 ? currentIndex + 1 : currentIndex - 1, false);
    }
    touchStartX = null;
  });

  gallery.addEventListener('focusin', stopAutoplay);
  gallery.addEventListener('focusout', function () {
    if (!gallery.contains(document.activeElement)) {
      restartAutoplay();
    }
  });

  startAutoplay();
}

function initProductDescriptionToggle() {
  const root = document.querySelector('[data-pd-description]');
  if (!root) return;

  const body = root.querySelector('[data-pd-description-body]');
  const toggle = root.querySelector('[data-pd-description-toggle]');
  if (!body || !toggle) return;

  const labelMore = toggle.dataset.labelMore || 'Read more';
  const labelLess = toggle.dataset.labelLess || 'Read less';
  let expanded = false;
  let canToggle = false;

  function setCollapsed() {
    expanded = false;
    body.classList.add('is-collapsed');
    body.classList.remove('is-expanded');
    toggle.textContent = labelMore;
  }

  function setExpanded() {
    expanded = true;
    body.classList.remove('is-collapsed');
    body.classList.add('is-expanded');
    toggle.textContent = labelLess;
  }

  function measure() {
    if (expanded) {
      return;
    }
    setCollapsed();
    canToggle = body.scrollHeight > body.clientHeight + 1;
    toggle.hidden = !canToggle;
    if (!canToggle) {
      body.classList.remove('is-collapsed');
    }
  }

  measure();
  window.addEventListener('resize', measure);

  toggle.addEventListener('click', function () {
    if (!canToggle && !expanded) {
      return;
    }
    if (expanded) {
      setCollapsed();
      measure();
    } else {
      setExpanded();
    }
  });
}

function initProductCtaBar() {
  const bar = document.querySelector('[data-pd-cta]');
  if (!bar) return;

  document.body.classList.add('has-product-cta-bar');

  const trigger = document.querySelector('[data-pd-form]') || document.querySelector('[data-product-detail]');

  function update() {
    if (!trigger) {
      bar.classList.add('is-visible');
      return;
    }
    const rect = trigger.getBoundingClientRect();
    const passed = rect.bottom < 80 || rect.top > window.innerHeight;
    bar.classList.toggle('is-visible', passed);
  }

  update();
  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
}

function initProductAttributesAccordion() {
  const wrap = document.querySelector('[data-pd-attributes]');
  if (!wrap) return;

  const buttons = Array.from(wrap.querySelectorAll('[data-pd-attr-btn]'));
  if (!buttons.length) return;

  function closeAll() {
    buttons.forEach(function (button) {
      button.setAttribute('aria-expanded', 'false');
      const panel = button.nextElementSibling;
      if (panel) panel.hidden = true;
    });
  }

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      const panel = button.nextElementSibling;
      const expanded = button.getAttribute('aria-expanded') === 'true';
      closeAll();
      if (!expanded) {
        button.setAttribute('aria-expanded', 'true');
        if (panel) panel.hidden = false;
      }
    });
  });
}

function initProductCtaQtySync() {
  const main = document.querySelector('[data-pd-qty]');
  const cta = document.querySelector('[data-pd-cta-qty]');
  if (!main || !cta) return;
  main.addEventListener('input', function () { cta.value = main.value; });
  cta.addEventListener('input', function () { main.value = cta.value; });
}
