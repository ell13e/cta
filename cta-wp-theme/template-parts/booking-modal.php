<?php
/**
 * Template Part: Booking Modal
 *
 * @package CTA_Theme
 * 
 * Shared booking/enquiry modal used across the site.
 */

$contact = cta_get_contact_info();
?>

<!-- Booking Modal -->
<div id="booking-modal" class="booking-modal" role="dialog" aria-modal="true" aria-labelledby="booking-modal-title" aria-hidden="true">
  <div class="booking-modal-backdrop" aria-hidden="true"></div>
  <div class="booking-modal-container">
    <button class="booking-modal-close" aria-label="Close booking form">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M18 6L6 18M6 6l12 12"></path>
      </svg>
    </button>
    
    <div class="booking-modal-content">
      <h2 id="booking-modal-title" class="booking-modal-title">Book This Course</h2>
      <p class="booking-modal-subtitle" id="booking-modal-course-name"></p>
      <p class="booking-modal-course-date" id="booking-modal-course-date" style="display: none;"></p>
      
      <form id="booking-modal-form" class="booking-modal-form" novalidate>
        <!-- Honeypot spam protection - multiple fields -->
        <input type="text" name="website" id="booking-website" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="text" name="url" id="booking-url" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="text" name="homepage" id="booking-homepage" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="hidden" name="form_load_time" id="booking-form-load-time" value="">
        <input type="hidden" name="submission_time" id="booking-submission-time" value="">
        
        <!-- Hidden course info -->
        <input type="hidden" name="course_name" id="booking-course-name">
        <input type="hidden" name="course_id" id="booking-course-id">
        <input type="hidden" name="event_date" id="booking-event-date">
        
        <!-- Error Summary -->
        <div id="booking-error-summary" class="booking-form-error-summary" role="alert" aria-live="assertive" style="display: none">
          <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
          <div>
            <strong>Please correct the following errors:</strong>
            <ul id="booking-error-list"></ul>
          </div>
        </div>

        <div class="booking-form-grid">
          <div class="booking-form-field">
            <label for="booking-name" class="booking-form-label">
              Your Name <span class="required">*</span>
            </label>
            <input type="text" id="booking-name" name="name" required class="booking-form-input" placeholder="Full name" autocomplete="name" aria-describedby="booking-name-error" aria-invalid="false">
            <p id="booking-name-error" class="booking-form-error" role="alert" aria-live="polite" style="display: none"></p>
          </div>
          
          <div class="booking-form-field">
            <label for="booking-email" class="booking-form-label">
              Email <span class="required">*</span>
            </label>
            <input type="email" id="booking-email" name="email" required class="booking-form-input" placeholder="your@email.com" autocomplete="email" aria-describedby="booking-email-error" aria-invalid="false">
            <p id="booking-email-error" class="booking-form-error" role="alert" aria-live="polite" style="display: none"></p>
          </div>
          
          <div class="booking-form-field">
            <label for="booking-phone" class="booking-form-label">
              Phone <span class="required">*</span>
            </label>
            <input type="tel" id="booking-phone" name="phone" required class="booking-form-input" placeholder="01234 567890" autocomplete="tel" aria-describedby="booking-phone-error" aria-invalid="false">
            <p id="booking-phone-error" class="booking-form-error" role="alert" aria-live="polite" style="display: none"></p>
          </div>
          
          <div class="booking-form-field">
            <label for="booking-delegates" class="booking-form-label">
              Number of Delegates
            </label>
            <input type="number" id="booking-delegates" name="delegates" min="1" max="20" class="booking-form-input" placeholder="1" value="1">
          </div>
          
          <div class="booking-form-field">
            <label for="booking-discount-code" class="booking-form-label">
              Discount Code (Optional)
            </label>
            <div class="contact-form-input-wrapper">
              <input type="text" id="booking-discount-code" name="discount_code" class="booking-form-input contact-form-input" placeholder="Enter code" autocomplete="off" aria-describedby="booking-discount-code-error" aria-invalid="false" style="text-transform: uppercase;">
              <i class="fas fa-check-circle contact-form-success-icon" aria-hidden="true"></i>
              <i class="fas fa-exclamation-circle contact-form-error-icon" aria-hidden="true"></i>
            </div>
            <p id="booking-discount-code-error" class="booking-form-error" role="alert" aria-live="polite" style="display: none"></p>
          </div>
        </div>
        
        <div class="booking-form-field">
          <label for="booking-message" class="booking-form-label">
            Additional Information
          </label>
          <textarea id="booking-message" name="message" rows="3" class="booking-form-textarea" placeholder="Any specific requirements or questions..."></textarea>
        </div>
        
        <div class="booking-form-consent">
          <input type="checkbox" id="booking-consent" name="consent" required class="booking-form-checkbox" aria-describedby="booking-consent-error" aria-invalid="false">
          <label for="booking-consent" class="booking-form-consent-label">
            I consent to being contacted about this booking. <span class="required">*</span>
          </label>
          <div style="margin-top: 12px;">
            <input type="checkbox" id="booking-marketing-consent" name="marketingConsent" checked class="booking-form-checkbox">
            <label for="booking-marketing-consent" class="booking-form-consent-label">
              I would like to receive updates, offers, and training news from Continuity Training Academy.
            </label>
          </div>
          <p id="booking-consent-error" class="booking-form-error" role="alert" aria-live="polite" style="display: none"></p>
        </div>
        
        <!-- reCAPTCHA v3 (invisible, no widget needed) -->
        <input type="hidden" name="g-recaptcha-response" id="booking-recaptcha-response" value="">
        <p id="booking-recaptcha-error" class="booking-form-error" role="alert" aria-live="polite" style="display: none"></p>
        
        <div class="booking-form-actions">
          <button type="submit" class="btn btn-primary booking-form-submit">
            Submit Enquiry
          </button>
        </div>
        
        <p class="booking-form-privacy">
          By submitting, you agree to our <a href="<?php echo esc_url(cta_page_url('privacy')); ?>">Privacy Policy</a>
        </p>
      </form>
      
      <div class="booking-modal-alternative">
        <p>Or call us directly:</p>
        <a href="<?php echo esc_url($contact['phone_link']); ?>" class="booking-modal-phone">
          <i class="fas fa-phone" aria-hidden="true"></i>
          <?php echo esc_html($contact['phone']); ?>
        </a>
      </div>
    </div>
  </div>
</div>

<script>
// Define functions and make them globally available immediately
(function() {
  'use strict';
  
  // Store original scroll position for restoration
  let bookingScrollPosition = 0;
  let bookingBodyStyle = '';

  // Define openBookingModal function
  function openBookingModal(courseName, courseId, eventDate) {
  const modal = document.getElementById('booking-modal');
  if (!modal) {
    // Fallback: redirect to contact page if modal doesn't exist
    const contactUrl = '<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>';
    window.location.href = contactUrl + '?course=' + encodeURIComponent(courseName);
    return;
  }
  const courseNameEl = document.getElementById('booking-modal-course-name');
  const courseDateEl = document.getElementById('booking-modal-course-date');
  const courseNameInput = document.getElementById('booking-course-name');
  const courseIdInput = document.getElementById('booking-course-id');
  const eventDateInput = document.getElementById('booking-event-date');
  
  if (courseName) {
    courseNameEl.textContent = courseName;
    courseNameInput.value = courseName;
  }
  if (courseId) {
    courseIdInput.value = courseId;
  }
  
  // Show event date if provided and store in hidden field
  if (eventDate) {
    courseDateEl.textContent = 'Event Date: ' + eventDate;
    courseDateEl.style.display = 'block';
    if (eventDateInput) {
      eventDateInput.value = eventDate;
    }
  } else {
    courseDateEl.style.display = 'none';
    if (eventDateInput) {
      eventDateInput.value = '';
    }
  }
  
  // Track form load time for anti-bot protection
  const formLoadTime = Date.now() / 1000; // Unix timestamp in seconds
  const formLoadTimeInput = document.getElementById('booking-form-load-time');
  if (formLoadTimeInput) {
    formLoadTimeInput.value = formLoadTime.toString();
  }
  
  // Save current scroll position
  bookingScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
  
  // Save original body styles
  bookingBodyStyle = document.body.style.cssText;
  
  // Lock body scroll - works on desktop and mobile
  document.body.style.position = 'fixed';
  document.body.style.top = `-${bookingScrollPosition}px`;
  document.body.style.left = '0';
  document.body.style.right = '0';
  document.body.style.overflow = 'hidden';
  document.body.style.width = '100%';
  
  // Also lock html scroll (some browsers need this)
  document.documentElement.style.overflow = 'hidden';
  
  modal.setAttribute('aria-hidden', 'false');
  
  setTimeout(() => {
    const nameInput = document.getElementById('booking-name');
    if (nameInput) nameInput.focus();
  }, 100);
}

  // Define closeBookingModal function
function closeBookingModal() {
  const modal = document.getElementById('booking-modal');
    if (modal) {
  modal.setAttribute('aria-hidden', 'true');
  
  // Restore body styles
  document.body.style.cssText = bookingBodyStyle;
  document.documentElement.style.overflow = '';
  
  // Restore scroll position
  window.scrollTo(0, bookingScrollPosition);
}
  }

  // Make functions globally accessible immediately
  window.openBookingModal = openBookingModal;
  window.closeBookingModal = closeBookingModal;

  // Also ensure it's available on DOMContentLoaded in case script loads late
document.addEventListener('DOMContentLoaded', function() {
    // Ensure functions are still available
    if (!window.openBookingModal) {
      window.openBookingModal = openBookingModal;
    }
    if (!window.closeBookingModal) {
      window.closeBookingModal = closeBookingModal;
    }
  const modal = document.getElementById('booking-modal');
  if (!modal) return;
  
  modal.querySelector('.booking-modal-backdrop').addEventListener('click', closeBookingModal);
  modal.querySelector('.booking-modal-close').addEventListener('click', closeBookingModal);
  
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
      closeBookingModal();
    }
  });
  
  const bookingForm = document.getElementById('booking-modal-form');
  if (bookingForm) {
      // Declare honeypot field IDs once outside the event listener
      const honeypotFieldIds = ['booking-website', 'booking-url', 'booking-homepage'];
      
    bookingForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Helper functions for inline error display
      function showError(fieldId, errorId, message) {
        const field = document.getElementById(fieldId);
        const errorEl = document.getElementById(errorId);
        if (field) {
          field.setAttribute('aria-invalid', 'true');
          field.classList.add('error');
        }
        if (errorEl) {
          errorEl.textContent = message;
          errorEl.style.display = 'block';
        }
      }
      
      function clearError(fieldId, errorId) {
        const field = document.getElementById(fieldId);
        const errorEl = document.getElementById(errorId);
        if (field) {
          field.setAttribute('aria-invalid', 'false');
          field.classList.remove('error');
        }
        if (errorEl) {
          errorEl.textContent = '';
          errorEl.style.display = 'none';
        }
      }
      
      function clearAllErrors() {
        clearError('booking-name', 'booking-name-error');
        clearError('booking-email', 'booking-email-error');
        clearError('booking-phone', 'booking-phone-error');
        clearError('booking-discount-code', 'booking-discount-code-error');
        clearError('booking-consent', 'booking-consent-error');
        clearError('booking-recaptcha', 'booking-recaptcha-error');
        const errorSummary = document.getElementById('booking-error-summary');
        if (errorSummary) {
          errorSummary.style.display = 'none';
        }
      }
      
      // Clear previous errors
      clearAllErrors();
      
      if (typeof ctaData === 'undefined' || !ctaData.ajaxUrl) {
        showError('booking-name', 'booking-name-error', 'Configuration error. Please contact us directly.');
        return;
      }
      
      // Honeypot spam protection - check all honeypot fields
      for (let i = 0; i < honeypotFieldIds.length; i++) {
        const field = document.getElementById(honeypotFieldIds[i]);
        if (field && field.value !== '') {
          // Bot detected - silently reject
          return;
        }
      }
      
      const nameInput = document.getElementById('booking-name');
      const emailInput = document.getElementById('booking-email');
      const phoneInput = document.getElementById('booking-phone');
      const consentInput = document.getElementById('booking-consent');
      const errorSummary = document.getElementById('booking-error-summary');
      const errorList = document.getElementById('booking-error-list');
      const errors = [];
      
      // Validate name
      if (!nameInput.value.trim()) {
        showError('booking-name', 'booking-name-error', 'Name is required');
        errors.push('Name is required');
      }
      
      // Validate email
      if (!emailInput.value.trim()) {
        showError('booking-email', 'booking-email-error', 'Email is required');
        errors.push('Email is required');
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
        showError('booking-email', 'booking-email-error', 'Please enter a valid email address');
        errors.push('Please enter a valid email address');
      }
      
      // Validate phone
      if (!phoneInput.value.trim()) {
        showError('booking-phone', 'booking-phone-error', 'Phone number is required');
        errors.push('Phone number is required');
      }
      
      // Validate consent
      if (!consentInput.checked) {
        showError('booking-consent', 'booking-consent-error', 'Please consent to being contacted about this booking');
        errors.push('Please consent to being contacted');
      }
      
      // reCAPTCHA v3 token will be generated on submit (invisible, no validation needed here)
      
      // Show error summary if there are errors
      if (errors.length > 0) {
        if (errorSummary && errorList) {
          errorList.innerHTML = errors.map(err => '<li>' + err + '</li>').join('');
          errorSummary.style.display = 'flex';
          errorSummary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        return;
      }
      
      const submitBtn = this.querySelector('.booking-form-submit');
      const originalText = submitBtn ? submitBtn.textContent : '';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
      }
      
      const formData = new FormData();
      formData.append('action', 'cta_course_booking');
      formData.append('nonce', ctaData.nonce);
      formData.append('name', nameInput.value.trim());
      formData.append('email', emailInput.value.trim());
      formData.append('phone', phoneInput.value.trim());
      formData.append('delegates', document.getElementById('booking-delegates')?.value || '1');
      formData.append('message', document.getElementById('booking-message')?.value || '');
      formData.append('discount_code', document.getElementById('booking-discount-code')?.value?.toUpperCase().trim() || '');
      formData.append('course_name', document.getElementById('booking-course-name')?.value || '');
      formData.append('course_id', document.getElementById('booking-course-id')?.value || '');
      formData.append('event_date', document.getElementById('booking-event-date')?.value || '');
      formData.append('consent', 'true');
      
      // Marketing consent (optional, pre-checked)
      const marketingConsentCheckbox = document.getElementById('booking-marketing-consent');
      formData.append('marketingConsent', marketingConsentCheckbox && marketingConsentCheckbox.checked ? 'true' : 'false');
      
      formData.append('page_url', window.location.href);
      
      // Include honeypot fields (reuse the same array declared above)
      honeypotFieldIds.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
          formData.append(field.name || fieldId.replace('booking-', ''), field.value);
        }
      });
      
      // Add submission timestamp for anti-bot protection
      const submissionTime = Date.now() / 1000; // Unix timestamp in seconds
      formData.append('submission_time', submissionTime.toString());
      
      // Ensure form load time is included
      const formLoadTimeInput = document.getElementById('booking-form-load-time');
      if (formLoadTimeInput && formLoadTimeInput.value) {
        formData.append('form_load_time', formLoadTimeInput.value);
      }
      
      // Get reCAPTCHA input element
      const recaptchaResponseInput = document.getElementById('booking-recaptcha-response');
      
      // Submit form function
      const submitForm = function() {
        // Ensure reCAPTCHA response is included if available
        if (recaptchaResponseInput && recaptchaResponseInput.value) {
          formData.append('g-recaptcha-response', recaptchaResponseInput.value);
        }

        fetch(ctaData.ajaxUrl, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
          }
          
          if (data.success) {
            clearAllErrors();
            closeBookingModal();
            
            const courseName = data.data?.course_name || document.getElementById('booking-course-name')?.value || 'this course';
            if (window.showThankYouModal) {
              window.showThankYouModal(
                data.data?.message || 'Thank you for your booking enquiry!',
                {
                  nextSteps: `We will review your enquiry for ${courseName} and get back to you.`
                }
              );
            }
            
            bookingForm.reset();
          } else {
            // Show server-side validation errors inline
            if (data.data && data.data.errors) {
              const errors = data.data.errors;
              if (errors.name) showError('booking-name', 'booking-name-error', errors.name);
              if (errors.email) showError('booking-email', 'booking-email-error', errors.email);
              if (errors.phone) showError('booking-phone', 'booking-phone-error', errors.phone);
              if (errors.discount_code) showError('booking-discount-code', 'booking-discount-code-error', errors.discount_code);
              if (errors.consent) showError('booking-consent', 'booking-consent-error', errors.consent);
              if (errors.recaptcha) showError('booking-recaptcha', 'booking-recaptcha-error', errors.recaptcha);
            } else {
              showError('booking-name', 'booking-name-error', data.data?.message || 'Unable to send your enquiry. Please try again.');
            }
          }
        })
        .catch(error => {
          console.error('Booking form error:', error);
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
          }
          showError('booking-name', 'booking-name-error', 'Unable to send your enquiry. Please try again or call us directly.');
        });
      };

      // Get reCAPTCHA v3 token before submitting
      if (recaptchaResponseInput && typeof ctaGetRecaptchaToken === 'function') {
        // Set a timeout fallback in case reCAPTCHA callback never fires
        let tokenReceived = false;
        const timeout = setTimeout(function() {
          if (!tokenReceived) {
            console.warn('reCAPTCHA token timeout, submitting without token');
            tokenReceived = true;
            submitForm();
          }
        }, 5000); // 5 second timeout
        
        ctaGetRecaptchaToken('booking', 'submit', function(token) {
          tokenReceived = true;
          clearTimeout(timeout);
          // Submit regardless of whether token was received (graceful degradation)
          submitForm();
        });
      } else {
        // No reCAPTCHA configured, submit directly
        submitForm();
      }
    });
  }
  }); // End DOMContentLoaded
})(); // End IIFE - functions are now available globally
</script>

