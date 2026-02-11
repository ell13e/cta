(function () {
  'use strict';

  let currentResourceName = '';
  /** @type {HTMLElement | null} Element that had focus before a modal opened; used to return focus on close. */
  let downloadModalPreviousFocus = null;
  let unavailableModalPreviousFocus = null;

  function getCfg() {
    if (!window.ccsData || !ccsData.ajaxUrl || !ccsData.nonce) return null;
    return { ajaxUrl: ccsData.ajaxUrl, nonce: ccsData.nonce };
  }

  function openModal(resourceId, resourceName) {
    const modal = document.getElementById('resource-download-modal');
    if (!modal) return;

    downloadModalPreviousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    currentResourceName = resourceName || '';

    const idInput = document.getElementById('resource-download-resource-id');
    if (idInput) idInput.value = String(resourceId || '');

    const subtitle = document.getElementById('resource-download-subtitle');
    if (subtitle && resourceName) {
      subtitle.textContent = `We'll email you a secure download link for "${resourceName}".`;
    }

    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
      const first = document.getElementById('resource-first-name');
      first?.focus();
    }, 50);
  }

  function closeModal() {
    const modal = document.getElementById('resource-download-modal');
    if (!modal) return;

    const canReturnTo = downloadModalPreviousFocus &&
      document.contains(downloadModalPreviousFocus) &&
      !downloadModalPreviousFocus.closest('[aria-hidden="true"]');
    const returnTo = canReturnTo ? downloadModalPreviousFocus : document.body;
    returnTo.focus();
    downloadModalPreviousFocus = null;

    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function openUnavailableModal(resourceName) {
    const modal = document.getElementById('resource-unavailable-modal');
    if (!modal) return;

    unavailableModalPreviousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

    const subtitle = document.getElementById('resource-unavailable-subtitle');
    if (subtitle && resourceName) {
      subtitle.textContent = `"${resourceName}" is not available yet.`;
    }

    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
      const closeBtn = modal.querySelector('[data-resource-unavailable-modal-close]');
      closeBtn?.focus();
    }, 50);
  }

  function closeUnavailableModal() {
    const modal = document.getElementById('resource-unavailable-modal');
    if (!modal) return;

    // Move focus off the close button before hiding the modal. Otherwise aria-hidden on the modal
    // would hide the focused element from assistive tech (spec violation).
    const canReturnTo = unavailableModalPreviousFocus &&
      document.contains(unavailableModalPreviousFocus) &&
      !unavailableModalPreviousFocus.closest('[aria-hidden="true"]');
    const returnTo = canReturnTo ? unavailableModalPreviousFocus : document.body;
    returnTo.focus();
    unavailableModalPreviousFocus = null;

    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function showFieldError(fieldId, errorId, msg) {
    const field = document.getElementById(fieldId);
    const err = document.getElementById(errorId);
    if (field) field.setAttribute('aria-invalid', 'true');
    if (err) {
      err.textContent = msg;
      err.style.display = 'block';
    }
  }

  function clearFieldError(fieldId, errorId) {
    const field = document.getElementById(fieldId);
    const err = document.getElementById(errorId);
    if (field) field.setAttribute('aria-invalid', 'false');
    if (err) {
      err.textContent = '';
      err.style.display = 'none';
    }
  }

  function clearAllErrors() {
    clearFieldError('resource-first-name', 'resource-first-name-error');
    clearFieldError('resource-last-name', 'resource-last-name-error');
    clearFieldError('resource-email', 'resource-email-error');
    clearFieldError('resource-phone', 'resource-phone-error');
    clearFieldError('resource-dob', 'resource-dob-error');
    clearFieldError('resource-consent', 'resource-consent-error');
  }

  async function submitForm(form) {
    const cfg = getCfg();
    if (!cfg) return;

    clearAllErrors();

    const resourceId = document.getElementById('resource-download-resource-id')?.value || '';
    const firstName = document.getElementById('resource-first-name')?.value.trim() || '';
    const lastName = document.getElementById('resource-last-name')?.value.trim() || '';
    const email = document.getElementById('resource-email')?.value.trim() || '';
    const phone = document.getElementById('resource-phone')?.value.trim() || '';
    const dob = document.getElementById('resource-dob')?.value || '';
    const consent = document.getElementById('resource-consent')?.checked || false;

    let hasErrors = false;
    if (!resourceId) {
      hasErrors = true;
    }
    if (!firstName) {
      showFieldError('resource-first-name', 'resource-first-name-error', 'First name is required.');
      hasErrors = true;
    }
    if (!lastName) {
      showFieldError('resource-last-name', 'resource-last-name-error', 'Last name is required.');
      hasErrors = true;
    }
    if (!email || !/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(email)) {
      showFieldError('resource-email', 'resource-email-error', 'Please enter a valid email address.');
      hasErrors = true;
    }
    // Validate UK phone number if provided
    if (phone) {
      const original = phone.trim();
      // Remove all whitespace and common formatting characters
      let cleaned = original.replace(/[\s\-\(\)\.]/g, '');
      
      // Handle international format: +44 or 0044
      if (cleaned.match(/^(\+44|0044)/)) {
        // Extract digits after country code
        let digitsAfterCode = cleaned.substring(cleaned.match(/^\+44/) ? 3 : 4).replace(/\D/g, '');
        // Convert to UK format (remove leading 0 if present, then add 0)
        digitsAfterCode = digitsAfterCode.replace(/^0+/, '');
        cleaned = '0' + digitsAfterCode;
      }
      
      // Extract only digits for validation
      const digitsOnly = cleaned.replace(/\D/g, '');
      
      // Must have 10-11 digits and start with 0[1-9]
      const digitCount = digitsOnly.length;
      if (digitCount < 10 || digitCount > 11 || !digitsOnly.match(/^0[1-9]\d{8,9}$/)) {
        showFieldError('resource-phone', 'resource-phone-error', 'Please enter a valid UK phone number (e.g., 01622 587343 or 07123 456789)');
        hasErrors = true;
      }
    }
    if (dob) {
      const t = Date.parse(dob);
      if (Number.isNaN(t) || t > Date.now()) {
        showFieldError('resource-dob', 'resource-dob-error', 'Please enter a valid date of birth.');
        hasErrors = true;
      }
    }
    if (!consent) {
      showFieldError('resource-consent', 'resource-consent-error', 'Consent is required.');
      hasErrors = true;
    }
    if (hasErrors) return;

    const btn = form.querySelector('button[type=\"submit\"]');
    const original = btn ? btn.textContent : '';
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Sending…';
    }

    const fd = new FormData();
    fd.append('action', 'cta_request_resource_download');
    fd.append('nonce', cfg.nonce);
    fd.append('resource_id', resourceId);
    fd.append('first_name', firstName);
    fd.append('last_name', lastName);
    fd.append('email', email);
    fd.append('phone', phone);
    fd.append('date_of_birth', dob);
    fd.append('consent', consent ? 'true' : 'false');

    try {
      const res = await fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
      const json = await res.json();

      if (btn) {
        btn.disabled = false;
        btn.textContent = original || 'Email me the resource';
      }

      if (json?.success) {
        closeModal();
        const msg = json.data?.message || 'Thanks! Check your email for the download link.';
        if (window.showThankYouModal) {
          window.showThankYouModal(msg, { hideNextSteps: true });
        } else {
          alert(msg);
        }
        form.reset();
        return;
      }

      // Check if resource is unavailable
      if (json?.data?.code === 'resource_unavailable') {
        closeModal();
        openUnavailableModal(currentResourceName || 'This resource');
        form.reset();
        return;
      }

      const errors = json?.data?.errors;
      if (errors) {
        if (errors.first_name) showFieldError('resource-first-name', 'resource-first-name-error', errors.first_name);
        if (errors.last_name) showFieldError('resource-last-name', 'resource-last-name-error', errors.last_name);
        if (errors.email) showFieldError('resource-email', 'resource-email-error', errors.email);
        if (errors.phone) showFieldError('resource-phone', 'resource-phone-error', errors.phone);
        if (errors.date_of_birth) showFieldError('resource-dob', 'resource-dob-error', errors.date_of_birth);
        if (errors.consent) showFieldError('resource-consent', 'resource-consent-error', errors.consent);
      }
    } catch (e) {
      if (btn) {
        btn.disabled = false;
        btn.textContent = original || 'Email me the resource';
      }
    }
  }

  function init() {
    const modal = document.getElementById('resource-download-modal');
    if (!modal) return;

    document.addEventListener('click', (e) => {
      const t = e.target;
      if (!(t instanceof Element)) return;

      const closeEl = t.closest('[data-resource-modal-close]');
      if (closeEl) {
        e.preventDefault();
        closeModal();
        return;
      }

      const unavailableCloseEl = t.closest('[data-resource-unavailable-modal-close]');
      if (unavailableCloseEl) {
        e.preventDefault();
        closeUnavailableModal();
        return;
      }

      const btn = t.closest('.resource-download-btn');
      if (btn) {
        e.preventDefault();
        const rid = btn.getAttribute('data-resource-id');
        const name = btn.getAttribute('data-resource-name') || '';
        openModal(rid, name);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const downloadModal = document.getElementById('resource-download-modal');
        const unavailableModal = document.getElementById('resource-unavailable-modal');
        
        if (downloadModal && downloadModal.getAttribute('aria-hidden') === 'false') {
          closeModal();
        }
        if (unavailableModal && unavailableModal.getAttribute('aria-hidden') === 'false') {
          closeUnavailableModal();
        }
      }
    });

    const form = document.getElementById('resource-download-form');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        submitForm(form);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

