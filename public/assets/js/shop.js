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
  initProductCtaBar();
  initProductCtaQtySync();
})();

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

  const images = thumbs.length
    ? thumbs.map(function (t) { return t.getAttribute('data-pd-src'); })
    : [mainImg.getAttribute('src')];
  let currentIndex = 0;

  function setActive(index) {
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
  }

  if (images[0]) {
    mainWrap.style.setProperty('--pd-zoom-image', 'url("' + images[0] + '")');
  }

  thumbs.forEach(function (t, i) {
    t.addEventListener('click', function () { setActive(i); });
  });

  if (prevBtn) prevBtn.addEventListener('click', function () { setActive(currentIndex - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { setActive(currentIndex + 1); });

  if (images.length <= 1) {
    if (prevBtn) prevBtn.classList.add('is-hidden');
    if (nextBtn) nextBtn.classList.add('is-hidden');
  }

  mainWrap.addEventListener('mouseenter', function () {
    if (matchMedia('(hover: hover) and (pointer: fine)').matches) {
      mainWrap.classList.add('is-zooming');
    }
  });
  mainWrap.addEventListener('mouseleave', function () {
    mainWrap.classList.remove('is-zooming');
  });
  mainWrap.addEventListener('mousemove', function (e) {
    const rect = mainWrap.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    mainWrap.style.setProperty('--pd-zoom-pos', x + '% ' + y + '%');
  });

  let touchStartX = null;
  mainWrap.addEventListener('touchstart', function (e) {
    if (e.touches.length === 1) touchStartX = e.touches[0].clientX;
  }, { passive: true });
  mainWrap.addEventListener('touchend', function (e) {
    if (touchStartX === null) return;
    const dx = (e.changedTouches[0] || {}).clientX - touchStartX;
    if (Math.abs(dx) > 40) {
      setActive(dx < 0 ? currentIndex + 1 : currentIndex - 1);
    }
    touchStartX = null;
  });
}

function initProductCtaBar() {
  const bar = document.querySelector('[data-pd-cta]');
  if (!bar) return;

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

function initProductCtaQtySync() {
  const main = document.querySelector('[data-pd-qty]');
  const cta = document.querySelector('[data-pd-cta-qty]');
  if (!main || !cta) return;
  main.addEventListener('input', function () { cta.value = main.value; });
  cta.addEventListener('input', function () { main.value = cta.value; });
}
