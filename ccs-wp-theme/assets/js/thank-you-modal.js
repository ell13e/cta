// Thank You Modal - Reusable popup for all form submissions
(function() {
  'use strict';

  const modal = document.getElementById('thank-you-modal');
  const overlay = modal?.querySelector('.thank-you-modal-overlay');
  const closeBtn = modal?.querySelector('.thank-you-modal-close');
  const messageEl = document.getElementById('thank-you-message');
  const nextStepsEl = modal?.querySelector('.thank-you-modal-next-steps');
  const nextStepsTextEl = modal?.querySelector('.thank-you-modal-next-steps-text');

  if (!modal) return;

  // Store reference to element that triggered the modal for focus return
  let previousActiveElement = null;

  // Show modal with custom message and optional next steps
  function showThankYouModal(message = "Thank you! We'll be in touch soon.", options = {}) {
    // Prevent double modal - if modal is already active, don't show again
    if (modal.classList.contains('active')) {
      return;
    }

    // Store the currently focused element to return focus later
    previousActiveElement = document.activeElement;

    // Set main message
    if (messageEl) {
      messageEl.textContent = message;
    }

    // Handle next steps section
    if (nextStepsEl && nextStepsTextEl) {
      if (options.hideNextSteps) {
        // Hide next steps section (e.g., for "already subscribed" message)
        nextStepsEl.style.display = 'none';
      } else if (options.nextSteps) {
        nextStepsTextEl.innerHTML = options.nextSteps;
        nextStepsEl.style.display = 'block';
      } else {
        // Default next steps for newsletter subscriptions
        nextStepsTextEl.innerHTML = "Check your email for a confirmation message. We'll send you updates about new courses, CQC changes, and training opportunities.";
        nextStepsEl.style.display = 'block';
      }
    }

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    // Focus management for accessibility
    const closeButton = modal.querySelector('.thank-you-modal-close');
    if (closeButton) {
      setTimeout(() => closeButton.focus(), 100);
    }
  }

  // Hide modal
  function hideThankYouModal() {
    // Remove focus from any element inside the modal before hiding
    // This prevents the aria-hidden violation
    const focusedElement = modal.querySelector(':focus');
    if (focusedElement) {
      focusedElement.blur();
    }

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = ''; // Restore scrolling

    // Return focus to the element that triggered the modal
    if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
      // Use setTimeout to ensure the modal is fully hidden before refocusing
      setTimeout(() => {
        previousActiveElement.focus();
        previousActiveElement = null;
      }, 100);
    }
  }

  // Close on overlay click
  if (overlay) {
    overlay.addEventListener('click', hideThankYouModal);
  }

  // Close on close button click
  if (closeBtn) {
    closeBtn.addEventListener('click', hideThankYouModal);
  }

  // Close on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.classList.contains('active')) {
      hideThankYouModal();
    }
  });

  // Make function globally available
  window.showThankYouModal = showThankYouModal;
  window.hideThankYouModal = hideThankYouModal;
})();

