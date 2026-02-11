(function () {
  'use strict';

  /**
   * Debounce helper
   */
  function debounce(fn, wait) {
    let t;
    return function debounced(...args) {
      window.clearTimeout(t);
      t = window.setTimeout(() => fn.apply(this, args), wait);
    };
  }

  function getAjaxConfig() {
    if (typeof window.ccsData !== 'object' || !window.ccsData) return null;
    if (!window.ccsData.ajaxUrl || !window.ccsData.nonce) return null;
    return { ajaxUrl: window.ccsData.ajaxUrl, nonce: window.ccsData.nonce };
  }

  function ensureSuccessEl(afterEl, id) {
    const existing = document.getElementById(id);
    if (existing) return existing;

    const el = document.createElement('p');
    el.id = id;
    el.className = 'discount-code-success';
    el.style.display = 'none';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');

    afterEl.insertAdjacentElement('afterend', el);
    return el;
  }

  function setFieldState(input, errorEl, successEl, state) {
    input.classList.remove('discount-code-valid', 'discount-code-invalid');

    if (state === 'clear') {
      if (errorEl) {
        errorEl.textContent = '';
        errorEl.style.display = 'none';
      }
      if (successEl) {
        successEl.textContent = '';
        successEl.style.display = 'none';
      }
      input.setAttribute('aria-invalid', 'false');
      return;
    }

    if (state === 'valid') {
      input.classList.add('discount-code-valid');
      input.setAttribute('aria-invalid', 'false');
      if (errorEl) errorEl.style.display = 'none';
      if (successEl) successEl.style.display = 'block';
      return;
    }

    if (state === 'invalid') {
      input.classList.add('discount-code-invalid');
      input.setAttribute('aria-invalid', 'true');
      if (successEl) successEl.style.display = 'none';
      if (errorEl) errorEl.style.display = 'block';
    }
  }

  async function validateDiscountCode(rawCode, input, errorEl, successEl) {
    const cfg = getAjaxConfig();
    if (!cfg) return;

    const code = (rawCode || '').toUpperCase().trim();
    if (!code) {
      setFieldState(input, errorEl, successEl, 'clear');
      return;
    }

    try {
      const formData = new FormData();
      formData.append('action', 'validate_discount_code');
      formData.append('nonce', cfg.nonce);
      formData.append('discount_code', code);

      const res = await fetch(cfg.ajaxUrl, { method: 'POST', body: formData });
      const json = await res.json();

      if (!json || json.success !== true || !json.data) {
        return;
      }

      const validation = json.data;
      if (validation.valid) {
        if (successEl) {
          successEl.textContent = validation.message || 'Discount applied';
        }
        setFieldState(input, errorEl, successEl, 'valid');
      } else {
        if (errorEl) {
          errorEl.textContent = validation.message || 'This discount code is not valid.';
        }
        setFieldState(input, errorEl, successEl, 'invalid');
      }
    } catch (e) {
      // Silent fail: don’t block the user from submitting; server-side validation still runs.
    }
  }

  function init() {
    const cfg = getAjaxConfig();
    if (!cfg) return;

    const targets = [
      { inputId: 'discount-code', errorId: 'discount-code-error', successId: 'discount-code-success' },
      { inputId: 'group-booking-discount-code', errorId: 'group-booking-discount-code-error', successId: 'group-booking-discount-code-success' },
      { inputId: 'booking-discount-code', errorId: 'booking-discount-code-error', successId: 'booking-discount-code-success' },
    ];

    targets.forEach(({ inputId, errorId, successId }) => {
      const input = document.getElementById(inputId);
      if (!input) return;

      const errorEl = document.getElementById(errorId);
      const successEl = ensureSuccessEl(errorEl || input, successId);

      const run = debounce(() => validateDiscountCode(input.value, input, errorEl, successEl), 500);

      input.addEventListener('input', run);
      input.addEventListener('blur', () => validateDiscountCode(input.value, input, errorEl, successEl));
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

