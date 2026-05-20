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
  initProductAttributesAccordion();
  initProductCtaBar();
  initProductCtaQtySync();
  initCatalogMega();
  initHomeSlider();
  initWelcomePopup();
})();

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
    let dragOffset = 0;
    let dragging = false;

    function slidesPerView() {
      const bp = parseInt(String(root.getAttribute('data-slide-breakpoint') || '768'), 10) || 768;
      const mobile = parseInt(String(root.getAttribute('data-slides-mobile') || '1'), 10) || 1;
      const desktop = parseInt(String(root.getAttribute('data-slides-desktop') || String(mobile)), 10) || mobile;
      return globalThis.matchMedia('(min-width: ' + bp + 'px)').matches ? desktop : mobile;
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
      const base = -(active * slideWidth());
      track.style.transition = animate && !dragging ? '' : 'none';
      track.style.transform = 'translate3d(' + (base + dragOffset) + 'px, 0, 0)';
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

    root.addEventListener('touchstart', function (e) {
      if (!canAdvance() || e.touches.length !== 1) return;
      stop();
      dragging = true;
      touchStartX = e.touches[0].clientX;
      dragOffset = 0;
      track.style.transition = 'none';
    }, { passive: true });

    root.addEventListener('touchmove', function (e) {
      if (!dragging || e.touches.length !== 1) return;
      dragOffset = e.touches[0].clientX - touchStartX;
      applyPosition(false);
    }, { passive: true });

    root.addEventListener('touchend', function () {
      if (!dragging) return;
      dragging = false;
      const threshold = slideWidth() * 0.16;
      if (dragOffset < -threshold) {
        setActive(active + 1, true);
      } else if (dragOffset > threshold) {
        setActive(active - 1, true);
      } else {
        setActive(active, true);
      }
      start();
    }, { passive: true });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    window.addEventListener('resize', function () {
      active = normalizeIndex(active);
      setActive(active, false);
      start();
    });

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
    return globalThis.matchMedia('(max-width: 767px)').matches;
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

  globalThis.matchMedia('(min-width: 768px)').addEventListener('change', function (e) {
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
