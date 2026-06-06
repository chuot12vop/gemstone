let pcDrawerOpenEl = null;

(function () {
  initMobileNavDrawer();
  initHeaderSearchDropdown();

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
  initProductQtyStepper();
  initSiteHeaderHeight();
  initHeaderPromoBar();
  initCatalogMega();
  initSiteFooterCollapse();
  initCatalogCategoryFilter();
  initProductUpsellBundle();
  initProductCards();
  initProductCardDrawers();
  initCartPage();
  initCatalogShowMore();
  initCatalogToolbar();
  initProductVariantPicker();
  initHomeSlider();
  initHomeStoriesTextToggle();
  initCheckoutDelivery();
  initCheckoutCountryFilterSelect();
  initCheckoutVoucher();
  initCheckoutMobileSummary();
  initCheckoutGatewayFields();
  initCheckoutExpress();
  initWelcomePopup();
  initFooterNewsletter();
  initContactForm();
  initFlashToasts();
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
    const variantIdInput = row.querySelector('[data-upsell-variant-id]');
    const qtyInput = row.querySelector('[data-upsell-qty]');
    const enabled = check.checked && !check.disabled;
    if (productIdInput instanceof HTMLInputElement) {
      productIdInput.disabled = !enabled;
    }
    if (variantIdInput instanceof HTMLInputElement) {
      variantIdInput.disabled = !enabled;
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

function initMobileNavDrawer() {
  const btn = document.querySelector('[data-nav-toggle]');
  const drawer = document.querySelector('.mobile-nav-drawer[data-nav-drawer]');
  const panel = drawer?.querySelector('[data-nav-panel]');
  const backdrop = drawer?.querySelector('[data-nav-backdrop]');
  const closeBtn = drawer?.querySelector('[data-nav-close]');
  if (!btn || !drawer || !panel) {
    return;
  }

  if (drawer.parentElement !== document.body) {
    document.body.appendChild(drawer);
  }

  const mqDesktop = globalThis.matchMedia('(min-width: 1024px)');

  function isSidebarMode() {
    return !mqDesktop.matches;
  }

  const panelShell = drawer.querySelector('.mobile-nav-drawer__panel');
  let closeTimer = null;
  let onPanelCloseHandler = null;
  let backdropReadyTimer = null;
  const closeDurationMs = 480;
  const backdropGraceMs = 450;

  function cancelCloseAnimation() {
    if (closeTimer) {
      clearTimeout(closeTimer);
      closeTimer = null;
    }
    if (onPanelCloseHandler && panelShell) {
      panelShell.removeEventListener('transitionend', onPanelCloseHandler);
      onPanelCloseHandler = null;
    }
  }

  function cancelBackdropReady() {
    if (backdropReadyTimer) {
      clearTimeout(backdropReadyTimer);
      backdropReadyTimer = null;
    }
    drawer.classList.remove('is-backdrop-ready');
  }

  function releaseFocusToToggle() {
    if (document.activeElement && drawer.contains(document.activeElement)) {
      btn.focus({ preventScroll: true });
    }
  }

  function finishClose() {
    cancelCloseAnimation();
    cancelBackdropReady();
    releaseFocusToToggle();
    drawer.classList.remove('is-open', 'is-backdrop-ready');
    panel.classList.remove('is-open');
    drawer.setAttribute('hidden', '');
    drawer.setAttribute('aria-hidden', 'true');
    drawer.setAttribute('inert', '');
    if (isSidebarMode()) {
      document.body.classList.remove('site-nav-open');
    }
  }

  function setOpen(open) {
    cancelCloseAnimation();
    cancelBackdropReady();

    btn.classList.toggle('is-active', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');

    if (open) {
      document.dispatchEvent(new CustomEvent('shop:close-welcome-popup'));
      document.dispatchEvent(new CustomEvent('shop:close-header-search'));
      drawer.removeAttribute('hidden');
      drawer.removeAttribute('inert');
      drawer.setAttribute('aria-hidden', 'false');
      if (isSidebarMode()) {
        document.body.classList.add('site-nav-open');
      }
      requestAnimationFrame(function () {
        drawer.classList.add('is-open');
        panel.classList.add('is-open');
        backdropReadyTimer = setTimeout(function () {
          drawer.classList.add('is-backdrop-ready');
        }, backdropGraceMs);
        if (closeBtn) {
          closeBtn.focus({ preventScroll: true });
        }
      });
      return;
    }

    drawer.classList.remove('is-open', 'is-backdrop-ready');
    panel.classList.remove('is-open');
    releaseFocusToToggle();

    if (!panelShell) {
      finishClose();
      return;
    }

    onPanelCloseHandler = function (e) {
      if (e.target !== panelShell || e.propertyName !== 'transform') {
        return;
      }
      finishClose();
    };
    panelShell.addEventListener('transitionend', onPanelCloseHandler);
    closeTimer = setTimeout(finishClose, closeDurationMs);
  }

  function close() {
    if (!drawer.classList.contains('is-open')) {
      return;
    }
    setOpen(false);
  }

  btn.addEventListener('click', function () {
    setOpen(!drawer.classList.contains('is-open'));
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', close);
  }

  if (backdrop) {
    backdrop.addEventListener('click', function () {
      if (!drawer.classList.contains('is-backdrop-ready')) {
        return;
      }
      close();
    });
  }

  document.addEventListener('shop:close-mobile-nav', function () {
    if (drawer.classList.contains('is-open') || !drawer.hasAttribute('hidden')) {
      setOpen(false);
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer.classList.contains('is-open') && isSidebarMode()) {
      close();
    }
  });

  mqDesktop.addEventListener('change', function (e) {
    if (e.matches) {
      close();
    }
  });

  panel.querySelectorAll('a[href]').forEach(function (link) {
    link.addEventListener('click', function () {
      if (!isSidebarMode()) {
        return;
      }
      if (link.hasAttribute('data-catalog-trigger')) {
        return;
      }
      if (link.closest('summary')) {
        return;
      }
      close();
    });
  });
}

function initHeaderSearchDropdown() {
  const root = document.querySelector('[data-header-search]');
  const toggle = root?.querySelector('[data-header-search-toggle]');
  const panel = root?.querySelector('[data-header-search-panel]');
  const input = root?.querySelector('[data-header-search-input]');
  if (!root || !toggle || !panel) {
    return;
  }

  function setOpen(open) {
    root.classList.toggle('is-open', open);
    toggle.classList.toggle('is-active', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Close search' : 'Search');

    if (open) {
      panel.removeAttribute('hidden');
      panel.removeAttribute('inert');
      document.dispatchEvent(new CustomEvent('shop:close-mobile-nav'));
      window.requestAnimationFrame(function () {
        if (input instanceof HTMLInputElement) {
          input.focus({ preventScroll: true });
          input.select();
        }
      });
      return;
    }

    panel.setAttribute('hidden', '');
    panel.setAttribute('inert', '');
    if (document.activeElement && panel.contains(document.activeElement)) {
      toggle.focus({ preventScroll: true });
    }
  }

  function close() {
    if (!root.classList.contains('is-open')) {
      return;
    }
    setOpen(false);
  }

  toggle.addEventListener('click', function (e) {
    e.stopPropagation();
    setOpen(!root.classList.contains('is-open'));
  });

  panel.addEventListener('click', function (e) {
    e.stopPropagation();
  });

  document.addEventListener('click', function (e) {
    if (!root.contains(e.target)) {
      close();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      close();
    }
  });

  document.addEventListener('shop:close-header-search', close);
}

function initSiteHeaderHeight() {
  const header = document.querySelector('.site-top')
    || document.querySelector('.site-header-shell')
    || document.querySelector('.site-header');
  const checkoutHeader = document.querySelector('.checkout-header');
  if (!header && !checkoutHeader) {
    return;
  }
  function sync() {
    if (header) {
      document.documentElement.style.setProperty('--site-header-height', header.offsetHeight + 'px');
    }
    if (checkoutHeader) {
      document.documentElement.style.setProperty('--checkout-header-height', checkoutHeader.offsetHeight + 'px');
    }
  }
  sync();
  if (typeof ResizeObserver !== 'undefined') {
    if (header) {
      new ResizeObserver(sync).observe(header);
    }
    if (checkoutHeader) {
      new ResizeObserver(sync).observe(checkoutHeader);
    }
  } else {
    window.addEventListener('resize', sync);
  }
}

function initHeaderPromoBar() {
  const bar = document.querySelector('[data-promo-bar]');
  const trigger = document.querySelector('[data-promo-bar-trigger]');
  const dismiss = document.querySelector('[data-promo-bar-dismiss]');
  const storageKey = 'gemstonePromoBarDismissed';

  if (!bar) {
    return;
  }

  try {
    if (localStorage.getItem(storageKey) === '1') {
      bar.hidden = true;
    }
  } catch (err) {
    /* ignore storage errors */
  }

  if (dismiss) {
    dismiss.addEventListener('click', function (e) {
      e.stopPropagation();
      bar.hidden = true;
      try {
        localStorage.setItem(storageKey, '1');
      } catch (err) {
        /* ignore storage errors */
      }
    });
  }

  if (!trigger) {
    return;
  }

  trigger.addEventListener('click', function () {
    const newsletter = document.getElementById('footer-newsletter');
    if (!newsletter) {
      return;
    }

    newsletter.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const emailInput = newsletter.querySelector('input[type="email"]');
    if (emailInput instanceof HTMLInputElement) {
      window.setTimeout(function () {
        emailInput.focus({ preventScroll: true });
      }, 400);
    }
  });
}

function initCatalogCategoryFilter() {
  // Filters apply only when the user clicks Apply in the drawer form.
}

function initFooterNewsletter() {
  const root = document.querySelector('[data-footer-newsletter]');
  if (!root) {
    return;
  }

  const form = root.querySelector('[data-footer-promo-form]');
  const message = root.querySelector('[data-footer-promo-message]');
  if (!form) {
    return;
  }

  function showMessage(text, isError) {
    if (!message) {
      return;
    }
    message.textContent = text;
    message.hidden = false;
    message.classList.toggle('footer-newsletter__message--err', !!isError);
    message.classList.toggle('footer-newsletter__message--ok', !isError);
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const consent = form.querySelector('input[name="promo_consent"]');
    if (consent && !consent.checked) {
      showMessage('Please agree to our Security and Privacy policies to continue.', true);
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
    }
    if (message) {
      message.hidden = true;
    }

    const body = new FormData(form);
    body.delete('promo_consent');

    fetch(form.action, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: body,
    })
      .then(function (res) {
        return res.json().then(function (data) {
          if (!res.ok) {
            throw new Error(data.message || 'Request failed');
          }
          return data;
        });
      })
      .then(function (data) {
        form.reset();
        showMessage(data.message || 'Thank you — check your inbox for your 10% off code.', false);
      })
      .catch(function (err) {
        showMessage(err.message || 'Please enter a valid email and try again.', true);
      })
      .finally(function () {
        if (submitBtn) {
          submitBtn.disabled = false;
        }
      });
  });
}

function initCheckoutVoucher() {
  const section = document.querySelector('[data-checkout-voucher]');
  if (!section) {
    return;
  }

  const form = document.querySelector('[data-checkout-delivery]');
  const input = section.querySelector('[data-voucher-input]');
  const applyBtn = section.querySelector('[data-voucher-apply]');
  const removeBtn = section.querySelector('[data-voucher-remove]');
  const msg = section.querySelector('[data-voucher-msg]');
  const discountRow = document.querySelector('[data-checkout-discount-row]');
  const discountEl = document.querySelector('[data-checkout-discount]');
  const shippingEl = document.querySelector('[data-checkout-shipping]');
  const shippingRow = document.querySelector('[data-checkout-shipping-row]');
  const taxRow = document.querySelector('[data-checkout-tax-row]');
  const taxEl = document.querySelector('[data-checkout-tax]');
  const totalEl = document.querySelector('[data-checkout-total]');

  function csrfToken() {
    const tokenInput = form && form.querySelector('input[name="_token"]');
    return tokenInput ? tokenInput.value : '';
  }

  function customerEmail() {
    const emailInput = form && form.querySelector('[name="customer_email"]');
    return emailInput ? String(emailInput.value || '').trim() : '';
  }

  function setMsg(text, type) {
    if (!msg) {
      return;
    }
    msg.textContent = text;
    msg.hidden = !text;
    msg.classList.remove('checkout-voucher__msg--ok', 'checkout-voucher__msg--err');
    if (type) {
      msg.classList.add(type === 'ok' ? 'checkout-voucher__msg--ok' : 'checkout-voucher__msg--err');
    }
  }

  function updateTotals(data) {
    if (discountRow && discountEl) {
      const discount = parseFloat(data.discount_usd || '0', 10) || 0;
      if (discount > 0) {
        discountEl.textContent = '−' + (data.discount_formatted || '');
        discountRow.hidden = false;
      } else {
        discountRow.hidden = true;
      }
    }
    if (totalEl && data.total_formatted) {
      totalEl.textContent = data.total_formatted;
    }
    if (shippingEl) {
      const shipping = parseFloat(data.shipping_usd || '0', 10) || 0;
      shippingEl.textContent = shipping <= 0 ? 'FREE' : (data.shipping_formatted || shippingEl.textContent);
      if (shippingRow) {
        shippingRow.hidden = false;
      }
    }
    if (taxRow && taxEl) {
      const tax = parseFloat(data.tax_usd || '0', 10) || 0;
      if (tax > 0) {
        taxEl.textContent = data.tax_formatted || taxEl.textContent;
        taxRow.hidden = false;
      } else {
        taxRow.hidden = true;
      }
    }
    const mobileTotal = document.querySelector('[data-checkout-summary-toggle] [data-checkout-total]');
    if (mobileTotal && data.total_formatted) {
      mobileTotal.textContent = data.total_formatted;
    }
    updateFreeShippingBar(data);
  }

  function swapApplyRemove(applied) {
    if (applyBtn) {
      applyBtn.hidden = applied;
    }
    if (removeBtn) {
      removeBtn.hidden = !applied;
    }
    if (input) {
      input.readOnly = applied;
    }
  }

  if (applyBtn && input) {
    applyBtn.addEventListener('click', function () {
      const code = String(input.value || '').trim();
      const email = customerEmail();
      if (!code) {
        setMsg('Enter a voucher code.', 'err');
        return;
      }
      if (!email) {
        setMsg('Enter your email above first so we can match your voucher.', 'err');
        return;
      }
      applyBtn.disabled = true;
      const body = new FormData();
      body.append('_token', csrfToken());
      body.append('voucher_code', code);
      body.append('customer_email', email);
      fetch(section.getAttribute('data-voucher-apply-url'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body,
      })
        .then(function (res) {
          return res.json().then(function (data) {
            if (!res.ok) {
              throw new Error(data.message || 'Invalid voucher');
            }
            return data;
          });
        })
        .then(function (data) {
          input.value = data.code || code;
          setMsg((data.percent || 10) + '% off applied — you save ' + (data.discount_formatted || '') + '.', 'ok');
          updateTotals(data);
          swapApplyRemove(true);
        })
        .catch(function (err) {
          setMsg(err.message || 'Invalid voucher.', 'err');
        })
        .finally(function () {
          applyBtn.disabled = false;
        });
    });
  }

  if (removeBtn) {
    removeBtn.addEventListener('click', function () {
      removeBtn.disabled = true;
      fetch(section.getAttribute('data-voucher-remove-url'), {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken(),
        },
      })
        .then(function (res) {
          return res.json().then(function (data) {
            if (!res.ok) {
              throw new Error('Could not remove voucher');
            }
            return data;
          });
        })
        .then(function (data) {
          if (input) {
            input.value = '';
            input.readOnly = false;
          }
          setMsg('', null);
          updateTotals({ discount_usd: 0, total_formatted: data.total_formatted });
          swapApplyRemove(false);
        })
        .catch(function () {
          setMsg('Could not remove voucher. Try again.', 'err');
        })
        .finally(function () {
          removeBtn.disabled = false;
        });
    });
  }

  swapApplyRemove(removeBtn && !removeBtn.hidden);
}

function initCheckoutCountryFilterSelect() {
  document.querySelectorAll('[data-filter-select]').forEach(function (root) {
    const select = root.querySelector('select[name]');
    const input = root.querySelector('[data-filter-select-input]');
    const panel = root.querySelector('[data-filter-select-panel]');
    const emptyMsg = root.querySelector('[data-filter-select-empty]');
    const options = Array.from(root.querySelectorAll('[data-filter-select-option]'));
    if (!select || !input || !panel || !options.length) {
      return;
    }

    let activeIndex = -1;

    function visibleOptions() {
      return options.filter(function (opt) {
        return !opt.hidden;
      });
    }

    function selectedOption() {
      return options.find(function (opt) {
        return opt.dataset.value === select.value;
      });
    }

    function setHasValue() {
      root.classList.toggle('has-value', String(input.value || '').trim() !== '');
    }

    function syncInputToSelection() {
      const opt = selectedOption();
      input.value = opt ? (opt.dataset.label || opt.textContent.trim()) : '';
      options.forEach(function (item) {
        item.setAttribute('aria-selected', item === opt ? 'true' : 'false');
      });
      setHasValue();
    }

    function filterOptions(query) {
      const q = String(query || '').trim().toLowerCase();
      let visibleCount = 0;
      options.forEach(function (opt) {
        const label = (opt.dataset.label || opt.textContent).trim();
        const match = !q || label.toLowerCase().includes(q);
        opt.hidden = !match;
        opt.classList.remove('is-active');
        if (match) {
          visibleCount += 1;
        }
      });
      if (emptyMsg) {
        emptyMsg.hidden = visibleCount > 0;
      }
      activeIndex = -1;
    }

    function highlightVisible(visible, index) {
      options.forEach(function (opt) {
        opt.classList.remove('is-active');
      });
      if (index < 0 || index >= visible.length) {
        return;
      }
      const opt = visible[index];
      opt.classList.add('is-active');
      opt.scrollIntoView({ block: 'nearest' });
    }

    function openPanel() {
      root.classList.add('is-open');
      input.setAttribute('aria-expanded', 'true');
      panel.hidden = false;
      filterOptions(input.value);
    }

    function closePanel() {
      root.classList.remove('is-open');
      input.setAttribute('aria-expanded', 'false');
      panel.hidden = true;
      activeIndex = -1;
      options.forEach(function (opt) {
        opt.classList.remove('is-active');
      });
      syncInputToSelection();
    }

    function selectOption(opt) {
      select.value = opt.dataset.value;
      input.value = opt.dataset.label || opt.textContent.trim();
      options.forEach(function (item) {
        item.setAttribute('aria-selected', item === opt ? 'true' : 'false');
      });
      setHasValue();
      select.dispatchEvent(new Event('change', { bubbles: true }));
      closePanel();
    }

    input.addEventListener('focus', openPanel);
    input.addEventListener('input', function () {
      openPanel();
      filterOptions(input.value);
    });

    input.addEventListener('keydown', function (e) {
      const visible = visibleOptions();
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (panel.hidden) {
          openPanel();
        }
        activeIndex = Math.min(activeIndex + 1, visible.length - 1);
        highlightVisible(visible, activeIndex);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex = Math.max(activeIndex - 1, 0);
        highlightVisible(visible, activeIndex);
      } else if (e.key === 'Enter') {
        if (!panel.hidden && activeIndex >= 0 && visible[activeIndex]) {
          e.preventDefault();
          selectOption(visible[activeIndex]);
        }
      } else if (e.key === 'Escape') {
        closePanel();
        input.blur();
      }
    });

    options.forEach(function (opt) {
      opt.addEventListener('click', function () {
        selectOption(opt);
      });
    });

    document.addEventListener('click', function (e) {
      if (!root.contains(e.target)) {
        closePanel();
      }
    });

    syncInputToSelection();
  });
}

function initCheckoutDelivery() {
  const form = document.querySelector('[data-checkout-delivery]');
  if (!form) {
    return;
  }
  const placeholder = form.querySelector('[data-shipping-placeholder]');
  const options = form.querySelector('[data-shipping-options]');
  const requiredNames = [
    'shipping_country',
    'shipping_first_name',
    'shipping_last_name',
    'shipping_address_line1',
    'shipping_city',
    'shipping_postcode',
  ];

  function isAddressComplete() {
    return requiredNames.every(function (name) {
      const el = form.querySelector('[name="' + name + '"]');
      return el && String(el.value || '').trim() !== '';
    });
  }

  function syncShippingMethod() {
    const complete = isAddressComplete();
    if (placeholder) {
      placeholder.hidden = complete;
    }
    if (options) {
      options.hidden = !complete;
    }
  }

  form.addEventListener('input', syncShippingMethod);
  form.addEventListener('change', syncShippingMethod);
  syncShippingMethod();
}

function updateFreeShippingBar(data) {
  if (!data || data.shipping_percent === undefined) {
    return;
  }
  const bar = document.querySelector('[data-free-shipping-bar]');
  if (!bar) {
    return;
  }
  const fill = bar.querySelector('[data-free-shipping-fill]');
  const msg = bar.querySelector('[data-free-shipping-msg]');
  const track = bar.querySelector('.checkout-free-shipping__track');
  const percent = Math.min(100, Math.max(0, parseFloat(data.shipping_percent, 10) || 0));
  if (fill) {
    fill.style.width = percent + '%';
  }
  if (track) {
    track.setAttribute('aria-valuenow', String(Math.round(percent)));
  }
  if (msg) {
    if (data.shipping_qualified) {
      msg.innerHTML = 'Hooray! Your order qualifies for <strong>FREE</strong> delivery.';
    } else if (data.shipping_remaining_formatted) {
      msg.innerHTML =
        'Spend <strong data-free-shipping-remaining>' +
        data.shipping_remaining_formatted +
        '</strong> more for <strong>FREE</strong> delivery';
    }
  }
  bar.dataset.qualified = data.shipping_qualified ? '1' : '0';
}

function initCheckoutMobileSummary() {
  const toggle = document.querySelector('[data-checkout-summary-toggle]');
  const panel = document.querySelector('[data-checkout-aside-panel]');
  const label = document.querySelector('[data-checkout-summary-toggle-label]');
  if (!toggle || !panel) {
    return;
  }
  const mq = window.matchMedia('(min-width: 768px)');

  function syncLayout() {
    if (mq.matches) {
      panel.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      if (label) {
        label.textContent = 'Order summary';
      }
    }
  }

  toggle.addEventListener('click', function () {
    if (mq.matches) {
      return;
    }
    const open = panel.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (label) {
      label.textContent = open ? 'Hide order summary' : 'Show order summary';
    }
  });

  if (typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', syncLayout);
  } else {
    mq.addListener(syncLayout);
  }
  syncLayout();
}

function initCheckoutExpress() {
  const root = document.querySelector('[data-checkout-express]');
  if (!root) {
    return;
  }

  const form = document.querySelector('[data-checkout-delivery]');
  const initUrl = root.getAttribute('data-paypal-init-url');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || document.querySelector('input[name="_token"]')?.value
    || '';

  if (!initUrl) {
    return;
  }

  const sdkUrl = root.getAttribute('data-paypal-sdk');

  function loadPayPalSdk(url) {
    if (typeof paypal !== 'undefined') {
      return Promise.resolve();
    }
    if (!url) {
      return Promise.reject(new Error('PayPal SDK URL missing'));
    }
    const existing = document.querySelector('script[data-paypal-sdk-express]');
    if (existing) {
      return new Promise(function (resolve, reject) {
        if (typeof paypal !== 'undefined') {
          resolve();
          return;
        }
        existing.addEventListener('load', function () { resolve(); }, { once: true });
        existing.addEventListener('error', function () { reject(new Error('PayPal SDK failed to load')); }, { once: true });
      });
    }
    return new Promise(function (resolve, reject) {
      const script = document.createElement('script');
      script.src = url;
      script.async = true;
      script.dataset.paypalSdkExpress = '1';
      script.dataset.sdkIntegrationSource = 'express-checkout';
      script.onload = function () { resolve(); };
      script.onerror = function () { reject(new Error('PayPal SDK failed to load')); };
      document.head.appendChild(script);
    });
  }

  function showPayPalMountError(mountSelector, message) {
    const mount = document.querySelector(mountSelector);
    if (!mount || mount.querySelector('.checkout-express__mount-error')) {
      return;
    }
    mount.innerHTML = '<p class="checkout-express__mount-error">' + message + '</p>';
  }

  function collectPayload() {
    const payload = { customer_email: '' };
    if (!form) {
      return payload;
    }
    const names = [
      'customer_email',
      'shipping_country',
      'shipping_first_name',
      'shipping_last_name',
      'shipping_company',
      'shipping_address_line1',
      'shipping_address_line2',
      'shipping_city',
      'shipping_postcode',
      'shipping_phone',
      'voucher_code',
    ];
    names.forEach(function (name) {
      const el = form.querySelector('[name="' + name + '"]');
      if (el) {
        payload[name] = String(el.value || '').trim();
      }
    });
    const emailOptIn = form.querySelector('[name="marketing_email_opt_in"]');
    if (emailOptIn?.checked) {
      payload.marketing_email_opt_in = '1';
    }
    return payload;
  }

  function postInit() {
    const payload = collectPayload();

    return fetch(initUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(payload),
    }).then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok) {
          throw new Error((data && data.message) || 'Could not start express checkout.');
        }
        return data;
      });
    });
  }

  function postConfirm(confirmUrl, body) {
    return fetch(confirmUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(body),
    }).then(function (res) {
      return res.json().then(function (data) {
        if (res.ok && data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        throw new Error((data && data.message) || 'Payment could not be confirmed.');
      });
    });
  }

  function mountPayPal() {
    if (typeof paypal === 'undefined') {
      showPayPalMountError('#express-paypal-button', 'PayPal could not be loaded. Check your connection or ad blocker.');
      return;
    }

    let lastInit = null;

    function bindExpressButton(mountSelector, options, style) {
      const mount = document.querySelector(mountSelector);
      if (!mount) {
        return;
      }
      const buttonOptions = Object.assign({}, options, {
        style: style,
        createOrder: function () {
          return postInit().then(function (data) {
            lastInit = data;
            return data.paypal_order_id;
          });
        },
        onApprove: function (data) {
          if (!lastInit || !lastInit.confirm_url) {
            return Promise.reject(new Error('Checkout session expired. Please try again.'));
          }
          return postConfirm(lastInit.confirm_url, { paypal_order_id: data.orderID });
        },
        onError: function (err) {
          console.error('PayPal express error', err);
        },
      });
      paypal
        .Buttons(buttonOptions)
        .render(mount)
        .catch(function (err) {
          console.error('PayPal express render failed', mountSelector, err);
          showPayPalMountError(mountSelector, 'PayPal is unavailable in this browser.');
        });
    }

    bindExpressButton('#express-paypal-button', { fundingSource: paypal.FUNDING.PAYPAL }, {
      layout: 'vertical',
      color: 'gold',
      shape: 'rect',
      label: 'paypal',
      height: 48,
      tagline: false,
    });

    const gpayFunding = paypal.FUNDING && paypal.FUNDING.GOOGLEPAY;
    if (gpayFunding) {
      bindExpressButton('#express-googlepay-button', { fundingSource: gpayFunding }, {
        layout: 'vertical',
        color: 'black',
        shape: 'rect',
        height: 48,
        tagline: false,
      });
    }
  }

  loadPayPalSdk(sdkUrl)
    .then(function () {
      mountPayPal();
    })
    .catch(function (err) {
      console.error(err);
      showPayPalMountError('#express-paypal-button', 'PayPal could not be loaded. Refresh the page or use Pay now below.');
    });
}

function initCheckoutGatewayFields() {
  const form = document.querySelector('[data-checkout-delivery]');
  if (!form) {
    return;
  }
  const blocks = form.querySelectorAll('[data-gateway-fields]');
  const radios = form.querySelectorAll('[data-payment-method-radio]');
  if (!radios.length) {
    return;
  }

  function sync() {
    const checked = form.querySelector('[data-payment-method-radio]:checked');
    const code = checked ? checked.value : '';
    blocks.forEach(function (block) {
      block.hidden = block.getAttribute('data-gateway-fields') !== code;
    });
  }

  radios.forEach(function (radio) {
    radio.addEventListener('change', sync);
  });
  sync();
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
    details.open = false;
  }
  if (typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', sync);
  } else {
    mq.addListener(sync);
  }
  sync();
}

function initSiteFooterCollapse() {
  const groups = document.querySelectorAll('[data-footer-collapse]');
  if (!groups.length) {
    return;
  }
  const mq = window.matchMedia('(min-width: 768px)');

  function sync() {
    groups.forEach(function (details) {
      details.open = mq.matches;
    });
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
  let lastFocused = null;

  function openPopup() {
    if (dismissed || opened) {
      return;
    }
    opened = true;
    lastFocused = document.activeElement;
    document.dispatchEvent(new CustomEvent('shop:close-mobile-nav'));
    root.hidden = false;
    root.removeAttribute('inert');
    root.setAttribute('aria-hidden', 'false');
    document.body.classList.add('welcome-popup-open');
    const email = root.querySelector('#welcome-popup-email');
    if (email) {
      email.focus();
    }
  }

  function closePopup() {
    if (root.hidden) {
      return;
    }
    dismissed = true;
    opened = false;
    const restoreTarget = lastFocused;
    lastFocused = null;
    root.hidden = true;
    root.setAttribute('inert', '');
    root.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('welcome-popup-open');
    if (openTimer !== null) {
      clearTimeout(openTimer);
      openTimer = null;
    }
    if (
      restoreTarget
      && typeof restoreTarget.focus === 'function'
      && restoreTarget !== document.body
      && !root.contains(restoreTarget)
    ) {
      restoreTarget.focus({ preventScroll: true });
    }
  }

  document.addEventListener('shop:close-welcome-popup', closePopup);

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
  const reduceMotion = globalThis.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.querySelectorAll('[data-home-slider]').forEach(function (root) {
    const viewport = root.querySelector('[data-slider-viewport]') || root.querySelector('.home-hero__viewport');
    const track = root.querySelector('[data-home-slider-track]');
    const realSlides = Array.from(root.querySelectorAll('[data-slide]:not([data-slide-clone])'));
    const dots = Array.from(root.querySelectorAll('[data-dot]'));
    if (!viewport || !track || realSlides.length === 0) {
      return;
    }

    const loopRequested = root.getAttribute('data-slider-loop') === 'true';
    const ms = parseInt(String(root.getAttribute('data-slide-interval') || '4000'), 10) || 4000;
    const autoplayEnabled = root.getAttribute('data-autoplay') !== 'false' && !reduceMotion;

    let slides = realSlides.slice();
    let active = 0;
    let cloneLead = 0;
    let loopActive = false;
    let timer = null;
    let touchStartX = 0;
    let touchStartY = 0;
    let dragOffset = 0;
    let dragging = false;
    let lockAxis = null;
    let cachedSlideWidth = 0;
    let lastInnerWidth = window.innerWidth;
    let rafId = null;
    let inView = false;
    let hoverPaused = false;

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

    function canAdvance() {
      return realSlides.length > slidesPerView();
    }

    function maxActiveIndex() {
      if (loopActive) {
        return cloneLead + realSlides.length - 1;
      }
      return Math.max(0, slides.length - slidesPerView());
    }

    function normalizeIndex(idx) {
      const max = maxActiveIndex();
      if (max === 0 && !loopActive) {
        return 0;
      }
      if (loopActive) {
        return idx;
      }
      if (idx < 0) {
        return max;
      }
      if (idx > max) {
        return 0;
      }
      return idx;
    }

    function realIndexFromActive() {
      if (!loopActive) {
        return active;
      }
      let ri = active - cloneLead;
      const n = realSlides.length;
      ri = ((ri % n) + n) % n;
      return ri;
    }

    function clearLoopClones() {
      track.querySelectorAll('[data-slide-clone]').forEach(function (el) {
        el.remove();
      });
      cloneLead = 0;
      loopActive = false;
      slides = realSlides.slice();
    }

    function setupLoopClones() {
      clearLoopClones();
      if (!loopRequested || !canAdvance()) {
        return;
      }
      const per = Math.min(slidesPerView(), realSlides.length);
      for (let i = realSlides.length - per; i < realSlides.length; i++) {
        const clone = realSlides[i].cloneNode(true);
        clone.setAttribute('data-slide-clone', 'lead');
        clone.setAttribute('aria-hidden', 'true');
        clone.classList.remove('is-active');
        track.insertBefore(clone, realSlides[0]);
      }
      for (let i = 0; i < per; i++) {
        const clone = realSlides[i].cloneNode(true);
        clone.setAttribute('data-slide-clone', 'tail');
        clone.setAttribute('aria-hidden', 'true');
        clone.classList.remove('is-active');
        track.appendChild(clone);
      }
      cloneLead = per;
      loopActive = true;
      slides = Array.from(track.querySelectorAll('[data-slide]'));
    }

    function fixLoopPosition() {
      if (!loopActive) {
        return;
      }
      const firstReal = cloneLead;
      const lastReal = cloneLead + realSlides.length - 1;
      if (active > lastReal) {
        active = firstReal;
        applyPosition(false);
      } else if (active < firstReal) {
        active = lastReal;
        applyPosition(false);
      }
    }

    function slideWidth() {
      const first = slides[0];
      if (first) {
        const measured = first.getBoundingClientRect().width;
        if (measured > 0) {
          return measured;
        }
      }
      return (viewport.clientWidth || 1) / slidesPerView();
    }

    function refreshSlideWidth() {
      cachedSlideWidth = slideWidth();
    }

    function rebuildSliderState(resetRealIndex) {
      const isStatic = !canAdvance();
      root.classList.toggle('is-static', isStatic);
      if (isStatic) {
        stop();
        clearLoopClones();
        active = 0;
        track.style.transform = 'translate3d(0, 0, 0)';
        return;
      }
      let ri = 0;
      if (resetRealIndex !== undefined && resetRealIndex !== null) {
        ri = resetRealIndex;
      } else if (loopActive) {
        ri = realIndexFromActive();
      } else {
        ri = active;
      }
      if (loopRequested) {
        setupLoopClones();
        active = loopActive ? cloneLead + ri : ri;
      } else {
        clearLoopClones();
        active = ri;
      }
    }

    function applyPosition(animate) {
      if (animate === undefined) {
        animate = true;
      }
      const w = dragging ? cachedSlideWidth : slideWidth();
      const x = Math.round(-(active * w) + dragOffset);
      track.style.transition = animate && !dragging ? '' : 'none';
      track.style.transform = 'translate3d(' + x + 'px, 0, 0)';
    }

    function queueApplyPosition() {
      if (rafId !== null) {
        return;
      }
      rafId = requestAnimationFrame(function () {
        rafId = null;
        if (dragging) {
          applyPosition(false);
        }
      });
    }

    function cancelQueuedPosition() {
      if (rafId !== null) {
        cancelAnimationFrame(rafId);
        rafId = null;
      }
    }

    function stop() {
      if (timer !== null) {
        clearInterval(timer);
        timer = null;
      }
    }

    function start() {
      stop();
      if (!autoplayEnabled) {
        return;
      }
      if (canAdvance() && inView && !hoverPaused) {
        timer = setInterval(function () {
          setActive(active + 1, true);
        }, ms);
      }
    }

    function updateSlideAria() {
      const per = slidesPerView();
      const ri = realIndexFromActive();
      realSlides.forEach(function (el, i) {
        const visible = i >= ri && i < ri + per;
        el.classList.toggle('is-active', i === ri);
        if (realSlides.length > 1) {
          el.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }
      });
    }

    function updateDots() {
      const ri = realIndexFromActive();
      dots.forEach(function (d, i) {
        d.classList.toggle('is-active', i === ri);
        d.setAttribute('aria-selected', i === ri ? 'true' : 'false');
      });
    }

    function setActive(idx, animate) {
      if (animate === undefined) {
        animate = true;
      }
      if (loopActive) {
        active = idx;
      } else {
        active = normalizeIndex(idx);
      }
      dragOffset = 0;
      dragging = false;
      updateSlideAria();
      updateDots();
      applyPosition(animate);
      if (!loopActive) {
        return;
      }
      if (!animate) {
        fixLoopPosition();
        return;
      }
      const onEnd = function () {
        track.removeEventListener('transitionend', onEnd);
        fixLoopPosition();
        updateSlideAria();
        updateDots();
      };
      track.addEventListener('transitionend', onEnd);
    }

    function finishTouchDrag() {
      if (!dragging) {
        return;
      }
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

    dots.forEach(function (d) {
      d.addEventListener('click', function () {
        const to = parseInt(String(d.getAttribute('data-slide-to') || '0'), 10);
        if (!Number.isNaN(to)) {
          setActive(loopActive ? cloneLead + to : to, true);
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
      if (!canAdvance() || e.touches.length !== 1) {
        return;
      }
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
      if (!dragging || e.touches.length !== 1) {
        return;
      }
      const x = e.touches[0].clientX;
      const y = e.touches[0].clientY;
      const dx = x - touchStartX;
      const dy = y - touchStartY;
      if (lockAxis === null) {
        if (Math.abs(dx) < 6 && Math.abs(dy) < 6) {
          return;
        }
        lockAxis = Math.abs(dx) >= Math.abs(dy);
      }
      if (!lockAxis) {
        return;
      }
      e.preventDefault();
      dragOffset = dx;
      queueApplyPosition();
    }, { passive: false });

    viewport.addEventListener('touchend', finishTouchDrag, { passive: true });
    viewport.addEventListener('touchcancel', finishTouchDrag, { passive: true });

    if (autoplayEnabled) {
      root.addEventListener('pointerenter', function (e) {
        if (e.pointerType === 'mouse') {
          hoverPaused = true;
          stop();
        }
      });
      root.addEventListener('pointerleave', function (e) {
        if (e.pointerType === 'mouse') {
          hoverPaused = false;
          start();
        }
      });
      if ('IntersectionObserver' in globalThis) {
        const autoplayObserver = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            inView = entry.isIntersecting;
            if (inView) {
              start();
            } else {
              stop();
            }
          });
        }, { threshold: 0.2 });
        autoplayObserver.observe(root);
      } else {
        inView = true;
      }
    }

    window.addEventListener('resize', function () {
      if (dragging) {
        return;
      }
      const w = window.innerWidth;
      if (Math.abs(w - lastInnerWidth) < 2) {
        return;
      }
      lastInnerWidth = w;
      const ri = realIndexFromActive();
      rebuildSliderState(ri);
      refreshSlideWidth();
      applyPosition(false);
      updateSlideAria();
      updateDots();
      start();
    });

    rebuildSliderState(0);
    refreshSlideWidth();
    applyPosition(false);
    updateSlideAria();
    updateDots();
    if (autoplayEnabled && !('IntersectionObserver' in globalThis) && canAdvance()) {
      start();
    }
  });
}

function initCatalogMega() {
  const mqDesktop = globalThis.matchMedia('(min-width: 1024px)');

  function isMobileNav() {
    return !mqDesktop.matches;
  }

  function closeAllMega() {
    document.querySelectorAll('[data-nav-mega]').forEach(function (li) {
      li.classList.remove('is-mega-open');
      const trigger = li.querySelector('[data-catalog-trigger]');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
    });
  }

  document.querySelectorAll('[data-nav-mega]').forEach(function (li) {
    const trigger = li.querySelector('[data-catalog-trigger]');
    if (!trigger) return;

    trigger.addEventListener('click', function (e) {
      if (!isMobileNav()) return;
      if (!li.classList.contains('is-mega-open')) {
        e.preventDefault();
        li.classList.add('is-mega-open');
        trigger.setAttribute('aria-expanded', 'true');
      }
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeAllMega();
    }
  });

  mqDesktop.addEventListener('change', function (e) {
    if (e.matches) closeAllMega();
  });

  const navToggle = document.querySelector('[data-nav-toggle]');
  const navDrawer = document.querySelector('.mobile-nav-drawer[data-nav-drawer]');
  if (navToggle && navDrawer) {
    navToggle.addEventListener('click', function () {
      window.setTimeout(function () {
        if (!navDrawer.classList.contains('is-open')) {
          closeAllMega();
        }
      }, 0);
    });
  }
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

  gallery._pdSetImages = function (urls) {
    const nextUrls = (urls || []).filter(Boolean);
    if (!nextUrls.length) {
      return;
    }

    images.length = 0;
    nextUrls.forEach(function (url) { images.push(url); });

    const thumbsWrap = gallery.querySelector('.pd-gallery__thumbs');
    if (thumbsWrap) {
      if (nextUrls.length <= 1) {
        thumbsWrap.hidden = true;
        thumbsWrap.innerHTML = '';
      } else {
        thumbsWrap.hidden = false;
        thumbsWrap.innerHTML = nextUrls.map(function (url, idx) {
          return '<button type="button" class="pd-gallery__thumb' + (idx === 0 ? ' is-active' : '') + '" data-pd-thumb data-pd-src="' + url + '" aria-label="Show image ' + (idx + 1) + '"><img src="' + url + '" alt="" loading="lazy"></button>';
        }).join('');
        thumbsWrap.querySelectorAll('[data-pd-thumb]').forEach(function (thumb, idx) {
          thumb.addEventListener('click', function () { setActive(idx, false); });
        });
      }
    }

    if (prevBtn) prevBtn.classList.toggle('is-hidden', nextUrls.length <= 1);
    if (nextBtn) nextBtn.classList.toggle('is-hidden', nextUrls.length <= 1);

    setActive(0, false);
  };
}

function initHomeStoriesTextToggle() {
  const root = document.querySelector('[data-home-stories-text]');
  if (!root) {
    return;
  }

  const body = root.querySelector('[data-home-stories-text-body]');
  const toggle = root.querySelector('[data-home-stories-text-toggle]');
  if (!body || !toggle) {
    return;
  }

  const mq = window.matchMedia('(max-width: 1023px)');
  const labelMore = toggle.dataset.labelMore || 'Show more';
  const labelLess = toggle.dataset.labelLess || 'Show less';
  let expanded = false;

  function isCompactViewport() {
    return mq.matches;
  }

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

  function disableToggle() {
    expanded = false;
    body.classList.remove('is-collapsed', 'is-expanded');
    toggle.hidden = true;
  }

  function measure() {
    if (!isCompactViewport()) {
      disableToggle();
      return;
    }
    if (expanded) {
      toggle.hidden = false;
      return;
    }
    setCollapsed();
    const canToggle = body.scrollHeight > body.clientHeight + 1;
    toggle.hidden = !canToggle;
    if (!canToggle) {
      body.classList.remove('is-collapsed');
    }
  }

  measure();
  if (typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', function () {
      expanded = false;
      measure();
    });
  } else {
    mq.addListener(function () {
      expanded = false;
      measure();
    });
  }
  window.addEventListener('resize', measure);

  toggle.addEventListener('click', function () {
    if (!isCompactViewport()) {
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
  const accordions = document.querySelectorAll('[data-pd-attributes], [data-pd-accordion]');
  accordions.forEach(function (wrap) {
    const buttons = Array.from(wrap.querySelectorAll('[data-pd-attr-btn], [data-pd-acc-btn]'));
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
  });
}

function initProductCtaQtySync() {
  const main = document.querySelector('[data-pd-qty]');
  const cta = document.querySelector('[data-pd-cta-qty]');
  if (!main || !cta) return;
  main.addEventListener('input', function () { cta.value = main.value; });
  cta.addEventListener('input', function () { main.value = cta.value; });
}

function initProductQtyStepper() {
  const pairs = [
    {
      dec: document.querySelector('[data-pd-qty-dec]'),
      inc: document.querySelector('[data-pd-qty-inc]'),
      input: document.querySelector('[data-pd-qty]'),
    },
    {
      dec: document.querySelector('[data-pd-cta-qty-dec]'),
      inc: document.querySelector('[data-pd-cta-qty-inc]'),
      input: document.querySelector('[data-pd-cta-qty]'),
    },
  ];

  const clampQty = (input, delta) => {
    if (!(input instanceof HTMLInputElement)) return;
    const min = parseInt(input.min || '1', 10) || 1;
    const max = parseInt(input.max || '9999', 10) || 9999;
    const next = Math.min(max, Math.max(min, (parseInt(input.value || '1', 10) || 1) + delta));
    input.value = String(next);
    input.dispatchEvent(new Event('input', { bubbles: true }));
  };

  pairs.forEach(function (pair) {
    if (!(pair.input instanceof HTMLInputElement)) return;
    if (pair.dec) {
      pair.dec.addEventListener('click', function () { clampQty(pair.input, -1); });
    }
    if (pair.inc) {
      pair.inc.addEventListener('click', function () { clampQty(pair.input, 1); });
    }
  });
}

function initFlashToasts() {
  const stack = document.querySelector('[data-toast-stack]');
  if (!stack) {
    return;
  }

  const toasts = Array.from(stack.querySelectorAll('[data-toast]'));
  if (!toasts.length) {
    return;
  }

  const dismissToast = (toast) => {
    if (toast.dataset.dismissed === 'true') {
      return;
    }
    toast.dataset.dismissed = 'true';
    toast.classList.remove('is-visible');
    toast.classList.add('is-leaving');
    window.setTimeout(function () {
      toast.remove();
      if (!stack.querySelector('[data-toast]')) {
        stack.remove();
      }
    }, 350);
  };

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

function initProductCards() {
  document.querySelectorAll('[data-product-card]').forEach(function (card) {
    const front = card.querySelector('[data-product-card-front]');
    const back = card.querySelector('[data-product-card-back]');
    const priceEl = card.querySelector('[data-product-card-price]');
    const compareEl = card.querySelector('[data-product-card-compare]');
    const variantInput = card.querySelector('[data-product-card-variant]');
    const swatches = card.querySelectorAll('[data-product-card-swatch]');
    const currencySymbol = document.documentElement.dataset.currencySymbol || '$';

    const formatUsd = (usd) => {
      const value = Number(usd || 0);
      return currencySymbol + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    const syncPricing = (swatch) => {
      if (priceEl) {
        priceEl.textContent = swatch.dataset.priceFormatted || formatUsd(swatch.dataset.priceUsd);
      }
      const onSale = swatch.dataset.onSale === '1';
      if (compareEl) {
        if (onSale && swatch.dataset.comparePriceFormatted) {
          compareEl.textContent = swatch.dataset.comparePriceFormatted;
          compareEl.hidden = false;
        } else {
          compareEl.textContent = '';
          compareEl.hidden = true;
        }
      }
    };

    swatches.forEach(function (swatch) {
      swatch.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        swatches.forEach(function (item) { item.classList.remove('is-active'); });
        swatch.classList.add('is-active');

        const image = swatch.dataset.image || '';
        const hoverImage = swatch.dataset.hoverImage || image;
        if (front instanceof HTMLImageElement && image) {
          front.src = image;
        }
        if (back instanceof HTMLImageElement) {
          if (hoverImage && hoverImage !== image) {
            back.src = hoverImage;
            back.hidden = false;
          } else {
            back.hidden = true;
          }
        }
        if (variantInput instanceof HTMLInputElement) {
          variantInput.value = swatch.dataset.variantId || '';
        }
        syncPricing(swatch);
      });
    });
  });
}

function initCatalogShowMore() {
  const summary = document.querySelector('[data-catalog-desc]');
  const toggle = document.querySelector('[data-catalog-desc-toggle]');
  if (!summary || !toggle) {
    return;
  }

  const collapsedClass = 'is-collapsed';
  const checkOverflow = () => {
    summary.classList.add(collapsedClass);
    const needsToggle = summary.scrollHeight > summary.clientHeight + 2;
    toggle.hidden = !needsToggle;
    if (!needsToggle) {
      summary.classList.remove(collapsedClass);
    }
  };

  checkOverflow();
  toggle.addEventListener('click', function () {
    const collapsed = summary.classList.toggle(collapsedClass);
    toggle.textContent = collapsed ? 'Show more' : 'Show less';
  });
}

function initCatalogToolbar() {
  const panel = document.querySelector('[data-catalog-filters-panel]');
  const openBtn = document.querySelector('[data-catalog-open-filters]');
  if (!panel || !openBtn) {
    return;
  }

  const closeBtns = panel.querySelectorAll('[data-catalog-close-filters]');

  function setFiltersOpen(open) {
    if (open) {
      panel.hidden = false;
      requestAnimationFrame(function () {
        panel.classList.add('is-open');
      });
      document.body.classList.add('is-catalog-filters-open');
      openBtn.setAttribute('aria-expanded', 'true');
      return;
    }
    panel.classList.remove('is-open');
    document.body.classList.remove('is-catalog-filters-open');
    openBtn.setAttribute('aria-expanded', 'false');
    window.setTimeout(function () {
      if (!panel.classList.contains('is-open')) {
        panel.hidden = true;
      }
    }, 360);
  }

  openBtn.addEventListener('click', function () {
    const isOpen = panel.classList.contains('is-open');
    setFiltersOpen(!isOpen);
  });

  closeBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      setFiltersOpen(false);
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && panel.classList.contains('is-open')) {
      setFiltersOpen(false);
    }
  });

  const showMoreBtn = document.querySelector('[data-catalog-show-more]');
  const grid = document.querySelector('[data-catalog-grid]');
  if (!showMoreBtn || !grid) {
    return;
  }

  showMoreBtn.addEventListener('click', function () {
    const nextUrl = showMoreBtn.dataset.nextUrl;
    if (!nextUrl) {
      return;
    }
    showMoreBtn.disabled = true;
    fetch(nextUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) { return response.text(); })
      .then(function (html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const newCards = doc.querySelectorAll('[data-catalog-grid] .shop-product-card');
        newCards.forEach(function (card) { grid.appendChild(card); });
        initProductCards();
        initProductCardDrawers();

        const newBtn = doc.querySelector('[data-catalog-show-more]');
        if (newBtn && newBtn.dataset.nextUrl) {
          showMoreBtn.dataset.nextUrl = newBtn.dataset.nextUrl;
          showMoreBtn.disabled = false;
        } else {
          showMoreBtn.remove();
        }
      })
      .catch(function () {
        window.location.href = nextUrl;
      });
  });
}

function initProductVariantPicker() {
  const root = document.querySelector('[data-pd-variant-picker]');
  if (!root) {
    return;
  }

  let variants = [];
  try {
    variants = JSON.parse(root.dataset.variants || '[]');
  } catch (error) {
    variants = [];
  }

  const colorButtons = root.querySelectorAll('[data-pd-color]');
  const sizeButtons = root.querySelectorAll('[data-pd-size]');
  const variantInput = document.querySelector('[data-pd-variant-id]');
  const ctaVariantInputs = document.querySelectorAll('[data-pd-cta-variant-id]');
  const priceEl = document.querySelector('[data-pd-price]');
  const ctaPriceEl = document.querySelector('[data-pd-cta-price]');
  const stockEl = document.querySelector('[data-pd-stock]');
  const submitButtons = document.querySelectorAll('[data-pd-submit]');
  const mainImg = document.querySelector('[data-pd-main]');
  const currencySymbol = document.documentElement.dataset.currencySymbol || '$';
  const currencyRate = parseFloat(document.documentElement.dataset.currencyRate || '1', 10) || 1;
  const currencyCode = document.documentElement.dataset.currencyCode || 'USD';

  let selectedColor = root.dataset.initialColor || '';
  let selectedSize = root.dataset.initialSize || '';

  const formatUsd = (usd) => {
    const value = Number(usd || 0) * currencyRate;
    const formatted = currencySymbol + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return currencyCode !== 'USD' ? formatted + ' ' + currencyCode : formatted;
  };

  const resolveVariant = () => {
    return variants.find(function (variant) {
      const colorMatch = !selectedColor || (variant.color || '') === selectedColor;
      const sizeMatch = !selectedSize || (variant.size || '') === selectedSize;
      return colorMatch && sizeMatch;
    }) || variants.find(function (variant) { return variant.is_default; }) || variants[0] || null;
  };

  const syncGallery = (variant) => {
    if (!variant) {
      return;
    }

    const gallery = document.querySelector('[data-pd-gallery]');
    const urls = Array.isArray(variant.images) && variant.images.length
      ? variant.images.filter(Boolean)
      : [variant.image].filter(Boolean);

    if (gallery && typeof gallery._pdSetImages === 'function') {
      gallery._pdSetImages(urls);
      return;
    }

    if (mainImg instanceof HTMLImageElement && variant.image) {
      mainImg.src = variant.image;
    }
  };

  const updateQtyLimits = (variant) => {
    if (!variant) return;
    const max = Math.max(1, Number(variant.stock || 1));
    document.querySelectorAll('[data-pd-qty], [data-pd-cta-qty]').forEach(function (input) {
      if (!(input instanceof HTMLInputElement)) return;
      input.max = String(max);
      if ((parseInt(input.value || '1', 10) || 1) > max) {
        input.value = String(max);
      }
    });
  };

  const applyVariant = () => {
    const variant = resolveVariant();
    if (!variant) {
      return;
    }

    if (variantInput instanceof HTMLInputElement) {
      variantInput.value = String(variant.id);
    }
    ctaVariantInputs.forEach(function (input) {
      if (input instanceof HTMLInputElement) {
        input.value = String(variant.id);
      }
    });

    if (priceEl) {
      priceEl.textContent = formatUsd(variant.price_usd);
    }
    if (ctaPriceEl) {
      ctaPriceEl.textContent = formatUsd(variant.price_usd);
    }
    if (stockEl) {
      stockEl.textContent = variant.stock > 0 ? variant.stock + ' in stock' : 'Out of stock';
    }

    submitButtons.forEach(function (button) {
      button.disabled = variant.stock < 1;
      if (variant.stock < 1) {
        button.textContent = 'Out of stock';
      } else if (button.dataset.labelDefault) {
        button.textContent = button.dataset.labelDefault;
      }
    });

    updateQtyLimits(variant);
    syncGallery(variant);
  };

  colorButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      selectedColor = button.dataset.pdColor || '';
      colorButtons.forEach(function (item) { item.classList.remove('is-active'); });
      button.classList.add('is-active');
      applyVariant();
    });
  });

  sizeButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      selectedSize = button.dataset.pdSize || '';
      sizeButtons.forEach(function (item) { item.classList.remove('is-active'); });
      button.classList.add('is-active');
      applyVariant();
    });
  });

  applyVariant();
}

function initProductCardDrawers() {
  const bagUrl = document.documentElement.dataset.cartBagUrl || '';
  const addUrl = document.documentElement.dataset.cartAddUrl || '';
  const updateUrl = document.documentElement.dataset.cartUpdateUrl || '';
  const removeUrl = document.documentElement.dataset.cartRemoveUrl || '';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const currencySymbol = document.documentElement.dataset.currencySymbol || '$';
  const currencyRate = parseFloat(document.documentElement.dataset.currencyRate || '1', 10) || 1;
  const currencyCode = document.documentElement.dataset.currencyCode || 'USD';

  let openDrawer = pcDrawerOpenEl;

  const formatUsd = (usd) => {
    const value = Number(usd || 0) * currencyRate;
    const formatted = currencySymbol + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return currencyCode !== 'USD' ? formatted + ' ' + currencyCode : formatted;
  };

  const updateHeaderCartCount = (count) => {
    document.querySelectorAll('[data-header-cart-count]').forEach(function (el) {
      el.textContent = String(count);
    });
    document.querySelectorAll('.cart-link').forEach(function (link) {
      link.setAttribute('aria-label', 'Cart (' + count + ' items)');
    });
  };

  const fetchBag = async (drawer) => {
    if (!bagUrl || !drawer) return;
    const bagBody = drawer.querySelector('[data-pc-drawer-bag-body]');
    if (!bagBody) return;
    bagBody.innerHTML = '<p class="pc-drawer__empty">Loading…</p>';
    try {
      const res = await fetch(bagUrl, { headers: { Accept: 'application/json' } });
      const data = await res.json();
      if (!res.ok || !data.ok) return;
      bagBody.innerHTML = data.html || '';
      const countEl = drawer.querySelector('[data-pc-drawer-count]');
      if (countEl) countEl.textContent = String(data.cart_count ?? 0);
      updateHeaderCartCount(data.cart_count ?? 0);
      bindBagActions(drawer, bagBody);
    } catch (e) {
      bagBody.innerHTML = '<p class="pc-drawer__empty">Could not load bag.</p>';
    }
  };

  const postCart = async (url, body) => {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(body),
    });
    return res.json();
  };

  const refreshFromResponse = async (drawer, data) => {
    if (!data || !data.ok) return false;
    const bagBody = drawer.querySelector('[data-pc-drawer-bag-body]');
    if (bagBody && data.html) {
      bagBody.innerHTML = data.html;
      bindBagActions(drawer, bagBody);
    }
    const countEl = drawer.querySelector('[data-pc-drawer-count]');
    if (countEl && data.cart_count != null) {
      countEl.textContent = String(data.cart_count);
    }
    if (data.cart_count != null) {
      updateHeaderCartCount(data.cart_count);
    }
    return true;
  };

  const bindBagActions = (drawer, bagBody) => {
    bagBody.querySelectorAll('[data-cart-line]').forEach(function (line) {
      const variantId = parseInt(line.getAttribute('data-variant-id') || '0', 10);
      const qtyVal = line.querySelector('[data-cart-qty-val]');
      const dec = line.querySelector('[data-cart-qty-dec]');
      const inc = line.querySelector('[data-cart-qty-inc]');
      const removeBtn = line.querySelector('[data-cart-remove]');

      const setQty = async (next) => {
        const data = await postCart(updateUrl, { qty: { [variantId]: next } });
        await refreshFromResponse(drawer, data);
      };

      if (dec) {
        dec.addEventListener('click', function () {
          const current = parseInt(qtyVal?.textContent || '1', 10);
          setQty(Math.max(0, current - 1));
        });
      }
      if (inc) {
        inc.addEventListener('click', function () {
          const current = parseInt(qtyVal?.textContent || '1', 10);
          setQty(current + 1);
        });
      }
      if (removeBtn) {
        removeBtn.addEventListener('click', async function () {
          const data = await postCart(removeUrl, { variant_id: variantId });
          await refreshFromResponse(drawer, data);
        });
      }
    });
  };

  const closeDrawer = (drawer) => {
    if (!drawer) return;
    drawer.classList.remove('is-open');
    drawer.setAttribute('hidden', '');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('pc-drawer-open');
    if (openDrawer === drawer) {
      openDrawer = null;
      pcDrawerOpenEl = null;
    }
  };

  const openDrawerEl = async (drawer) => {
    if (!drawer) return;
    if (openDrawer && openDrawer !== drawer) {
      closeDrawer(openDrawer);
    }
    openDrawer = drawer;
    pcDrawerOpenEl = drawer;
    drawer.removeAttribute('hidden');
    drawer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('pc-drawer-open');
    requestAnimationFrame(function () {
      drawer.classList.add('is-open');
    });
    await fetchBag(drawer);
  };

  const initDrawerVariants = (drawer) => {
    const root = drawer.querySelector('[data-pc-drawer-variants]');
    const addBtn = drawer.querySelector('[data-pc-drawer-add-btn]');
    const priceEl = drawer.querySelector('[data-pc-drawer-price]');
    const thumbEl = drawer.querySelector('[data-pc-drawer-thumb]');
    if (!addBtn) return;

    let variants = [];
    if (root) {
      try {
        variants = JSON.parse(root.dataset.variants || '[]');
      } catch (e) {
        variants = [];
      }
    }

    let selectedColor = root?.dataset.initialColor || '';
    let selectedSize = root?.dataset.initialSize || '';

    const resolveVariant = () => {
      if (variants.length === 0) {
        const id = parseInt(addBtn.dataset.variantId || '0', 10);
        return id > 0 ? { id: id, stock: addBtn.disabled ? 0 : 1, price_usd: 0, image: null } : null;
      }
      return variants.find(function (v) {
        const colorMatch = !selectedColor || (v.color || '') === selectedColor;
        const sizeMatch = !selectedSize || (v.size || '') === selectedSize;
        return colorMatch && sizeMatch;
      }) || variants.find(function (v) { return v.is_default; }) || variants[0] || null;
    };

    const applyVariant = () => {
      const variant = resolveVariant();
      if (!variant) return;
      addBtn.dataset.variantId = String(variant.id);
      addBtn.disabled = variant.stock < 1;
      if (priceEl && variant.price_usd) priceEl.textContent = formatUsd(variant.price_usd);
      if (thumbEl instanceof HTMLImageElement && variant.image) {
        thumbEl.src = variant.image;
      }
    };

    if (root) {
      root.querySelectorAll('[data-pc-color]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          selectedColor = btn.dataset.pcColor || '';
          root.querySelectorAll('[data-pc-color]').forEach(function (b) { b.classList.remove('is-active'); });
          btn.classList.add('is-active');
          applyVariant();
        });
      });

      root.querySelectorAll('[data-pc-size]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          selectedSize = btn.dataset.pcSize || '';
          root.querySelectorAll('[data-pc-size]').forEach(function (b) { b.classList.remove('is-active'); });
          btn.classList.add('is-active');
          applyVariant();
        });
      });

      applyVariant();
    }

    addBtn.addEventListener('click', async function () {
      const variantId = parseInt(addBtn.dataset.variantId || '0', 10);
      if (variantId < 1) return;
      addBtn.disabled = true;
      try {
        const data = await postCart(addUrl, { variant_id: variantId, quantity: 1 });
        await refreshFromResponse(drawer, data);
        if (root) applyVariant();
      } finally {
        if (root) applyVariant();
      }
    });
  };

  document.querySelectorAll('[data-pc-drawer]:not([data-pc-drawer-bound])').forEach(function (drawer) {
    drawer.setAttribute('data-pc-drawer-bound', '1');
    if (drawer.parentElement !== document.body) {
      document.body.appendChild(drawer);
    }
    initDrawerVariants(drawer);

    drawer.querySelectorAll('[data-pc-drawer-close]').forEach(function (btn) {
      btn.addEventListener('click', function () { closeDrawer(drawer); });
    });

    drawer.querySelectorAll('[data-pc-upsell-add]').forEach(function (btn) {
      btn.addEventListener('click', async function () {
        const variantId = parseInt(btn.dataset.variantId || '0', 10);
        if (variantId < 1) return;
        btn.disabled = true;
        const payload = { variant_id: variantId, quantity: 1 };
        if (btn.dataset.unitPriceUsd) {
          payload.unit_price_usd = parseFloat(btn.dataset.unitPriceUsd);
        }
        try {
          const data = await postCart(addUrl, payload);
          await refreshFromResponse(drawer, data);
        } finally {
          btn.disabled = false;
        }
      });
    });
  });

  document.querySelectorAll('[data-pc-drawer-open]:not([data-pc-drawer-open-bound])').forEach(function (btn) {
    btn.setAttribute('data-pc-drawer-open-bound', '1');
    btn.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      const card = btn.closest('[data-product-card]');
      const productId = card?.dataset.productId;
      const drawer = productId
        ? document.querySelector('[data-pc-drawer][data-product-id="' + productId + '"]')
        : card?.querySelector('[data-pc-drawer]');
      if (drawer) openDrawerEl(drawer);
    });
  });

  if (!document.documentElement.dataset.pcDrawerEscapeBound) {
    document.documentElement.dataset.pcDrawerEscapeBound = '1';
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && pcDrawerOpenEl) {
        pcDrawerOpenEl.classList.remove('is-open');
        pcDrawerOpenEl.setAttribute('hidden', '');
        pcDrawerOpenEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('pc-drawer-open');
        pcDrawerOpenEl = null;
      }
    });
  }
}

function initCartPage() {
  const root = document.querySelector('[data-cart-page]');
  if (!root) return;

  const updateUrl = document.documentElement.dataset.cartUpdateUrl || '';
  const removeUrl = document.documentElement.dataset.cartRemoveUrl || '';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const currencySymbol = document.documentElement.dataset.currencySymbol || '$';
  const currencyRate = parseFloat(document.documentElement.dataset.currencyRate || '1', 10) || 1;
  const currencyCode = document.documentElement.dataset.currencyCode || 'USD';

  const formatUsd = (usd) => {
    const value = Number(usd || 0) * currencyRate;
    const formatted = currencySymbol + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return currencyCode !== 'USD' ? formatted + ' ' + currencyCode : formatted;
  };

  const updateHeaderCartCount = (count) => {
    document.querySelectorAll('[data-header-cart-count]').forEach(function (el) {
      el.textContent = String(count);
    });
    document.querySelectorAll('.cart-link').forEach(function (link) {
      link.setAttribute('aria-label', 'Cart (' + count + ' items)');
    });
  };

  const updateTitleCount = (count) => {
    const title = root.querySelector('[data-cart-page-title]');
    if (!title) return;
    title.textContent = count > 0 ? 'Your bag (' + count + ')' : 'Your bag';
  };

  const updateSubtotal = (subtotalUsd) => {
    root.querySelectorAll('[data-cart-subtotal]').forEach(function (el) {
      el.textContent = formatUsd(subtotalUsd);
    });
  };

  const updateFreeShippingBar = (shipping) => {
    const bar = document.querySelector('[data-free-shipping-bar]');
    if (!bar || !shipping) return;
    const fill = bar.querySelector('[data-free-shipping-fill]');
    const msg = bar.querySelector('[data-free-shipping-msg]');
    const track = bar.querySelector('.checkout-free-shipping__track');
    const percent = Math.min(100, Number(shipping.percent || 0));

    if (fill) fill.style.width = percent + '%';
    if (track) track.setAttribute('aria-valuenow', String(Math.round(percent)));
    bar.dataset.qualified = shipping.qualified ? '1' : '0';

    if (msg) {
      if (shipping.qualified) {
        msg.innerHTML = 'Hooray! Your order qualifies for <strong>FREE</strong> delivery.';
      } else {
        msg.innerHTML = 'Spend <strong data-free-shipping-remaining>' + formatUsd(shipping.remaining) + '</strong> more for <strong>FREE</strong> delivery';
      }
    }
  };

  const postCart = async (url, body) => {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(body),
    });
    return res.json();
  };

  const bindCartLineActions = (container) => {
    if (!container) return;
    container.querySelectorAll('[data-cart-line]').forEach(function (line) {
      const variantId = parseInt(line.getAttribute('data-variant-id') || '0', 10);
      const qtyVal = line.querySelector('[data-cart-qty-val]');
      const dec = line.querySelector('[data-cart-qty-dec]');
      const inc = line.querySelector('[data-cart-qty-inc]');
      const removeBtn = line.querySelector('[data-cart-remove]');

      const refreshFromResponse = async (data) => {
        if (!data || !data.ok) return;
        if (data.cart_count === 0) {
          globalThis.location.reload();
          return;
        }
        const linesWrap = root.querySelector('[data-cart-page-lines-wrap]');
        if (linesWrap && data.lines_html != null) {
          linesWrap.innerHTML = data.lines_html;
          bindCartLineActions(linesWrap);
        }
        if (data.subtotal_usd != null) {
          updateSubtotal(data.subtotal_usd);
        }
        if (data.shipping) {
          updateFreeShippingBar(data.shipping);
        }
        if (data.cart_count != null) {
          updateHeaderCartCount(data.cart_count);
          updateTitleCount(data.cart_count);
        }
      };

      const setQty = async (next) => {
        const data = await postCart(updateUrl, { qty: { [variantId]: next } });
        await refreshFromResponse(data);
      };

      if (dec) {
        dec.addEventListener('click', function () {
          const current = parseInt(qtyVal?.textContent || '1', 10);
          setQty(Math.max(0, current - 1));
        });
      }
      if (inc) {
        inc.addEventListener('click', function () {
          const current = parseInt(qtyVal?.textContent || '1', 10);
          setQty(current + 1);
        });
      }
      if (removeBtn) {
        removeBtn.addEventListener('click', async function () {
          const data = await postCart(removeUrl, { variant_id: variantId });
          await refreshFromResponse(data);
        });
      }
    });
  };

  bindCartLineActions(root);
}
