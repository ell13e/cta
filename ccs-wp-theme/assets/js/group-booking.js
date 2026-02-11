// Group Booking Form Handler
(function () {
  'use strict';

  // Development mode flag
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  const form = document.getElementById('group-booking-form-element');
  const submitButton = document.getElementById('group-booking-submit');
  const successMessage = document.getElementById('group-booking-success');
  const staffNumberInput = document.getElementById('group-booking-numberOfStaff');
  const staffNumberHint = document.getElementById('staff-number-hint');
  let isSubmitting = false;

  if (!form) return;

  // Track form load time for anti-bot protection
  const formLoadTime = Date.now() / 1000; // Unix timestamp in seconds
  const formLoadTimeInput = document.getElementById('group-booking-form-load-time');
  if (formLoadTimeInput) {
    formLoadTimeInput.value = formLoadTime.toString();
  }

  // Staff number validation with helpful hint
  if (staffNumberInput && staffNumberHint) {
    staffNumberInput.addEventListener('input', function() {
      const value = parseInt(this.value, 10);
      if (isNaN(value) || value < 1) {
        staffNumberHint.style.display = 'none';
        this.setCustomValidity('Please enter a valid number of staff (minimum 1).');
      } else if (value > 100) {
        staffNumberHint.style.display = 'none';
        this.setCustomValidity('For groups larger than 100, please contact us directly to discuss your training needs.');
        this.reportValidity();
      } else if (value > 12) {
        staffNumberHint.style.display = 'flex';
        this.setCustomValidity('');
      } else {
        staffNumberHint.style.display = 'none';
        this.setCustomValidity('');
      }
    });

    staffNumberInput.addEventListener('blur', function() {
      const value = parseInt(this.value, 10);
      if (isNaN(value) || value < 1) {
        staffNumberHint.style.display = 'none';
        this.setCustomValidity('Please enter a valid number of staff (minimum 1).');
      } else if (value > 100) {
        staffNumberHint.style.display = 'none';
        this.setCustomValidity('For groups larger than 100, please contact us directly to discuss your training needs.');
        this.reportValidity();
      } else if (value > 12) {
        staffNumberHint.style.display = 'flex';
        this.setCustomValidity('');
      } else {
        staffNumberHint.style.display = 'none';
        this.setCustomValidity('');
      }
    });
  }

  // Handle form submission via WordPress AJAX
  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    if (isSubmitting) return;

    // Honeypot spam protection - check all honeypot fields
    const honeypotFieldIds = ['group-booking-website', 'group-booking-url', 'group-booking-homepage'];
    for (let i = 0; i < honeypotFieldIds.length; i++) {
      const field = document.getElementById(honeypotFieldIds[i]);
      if (field && field.value !== '') {
        // Bot detected - silently reject
        return;
      }
    }

    // Validate form
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

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
      clearError('group-booking-name', 'group-booking-name-error');
      clearError('group-booking-email', 'group-booking-email-error');
      clearError('group-booking-phone', 'group-booking-phone-error');
      clearError('group-booking-organisation', 'group-booking-organisation-error');
      clearError('group-booking-numberOfStaff', 'group-booking-numberOfStaff-error');
      clearError('group-booking-trainingType', 'group-booking-trainingType-error');
      clearError('group-booking-consent', 'group-booking-consent-error');
      const errorSummary = document.getElementById('group-booking-error-summary');
      if (errorSummary) {
        errorSummary.style.display = 'none';
      }
    }
    
    // Clear previous errors
    clearAllErrors();
    
    // Check if WordPress AJAX is available
    if (typeof ccsData === 'undefined' || !ccsData.ajaxUrl) {
      if (isDevelopment) console.error('WordPress AJAX not available');
      showError('group-booking-name', 'group-booking-name-error', 'Configuration error. Please contact us directly.');
      return;
    }

    isSubmitting = true;
    submitButton.disabled = true;
    const originalButtonText = submitButton.textContent || 'Get My Free Quote';
    submitButton.textContent = 'Sending Request...';

    // Prepare form data for WordPress AJAX
    const formData = new FormData();
    formData.append('action', 'cta_group_booking');
    formData.append('nonce', ccsData.nonce);
    formData.append('name', document.getElementById('group-booking-name').value);
    formData.append('email', document.getElementById('group-booking-email').value);
    formData.append('phone', document.getElementById('group-booking-phone').value);
    formData.append('organisation', document.getElementById('group-booking-organisation').value);
    formData.append('numberOfStaff', document.getElementById('group-booking-numberOfStaff').value);
    formData.append('trainingType', document.getElementById('group-booking-trainingType').value);
    formData.append('details', document.getElementById('group-booking-details')?.value || '');
    
    // Add discount code if provided
    const discountCodeInput = document.getElementById('group-booking-discount-code');
    if (discountCodeInput && discountCodeInput.value.trim()) {
      formData.append('discount_code', discountCodeInput.value.toUpperCase().trim());
    }
    
    // Include honeypot fields (reuse the same array declared above)
    honeypotFieldIds.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field) {
        formData.append(field.name || fieldId.replace('group-booking-', ''), field.value);
      }
    });
    
    // Add submission timestamp for anti-bot protection
    const submissionTime = Date.now() / 1000; // Unix timestamp in seconds
    formData.append('submission_time', submissionTime.toString());
    
    // Ensure form load time is included
    if (formLoadTimeInput && formLoadTimeInput.value) {
      formData.append('form_load_time', formLoadTimeInput.value);
    }
    
    // Include consent
    const consentCheckbox = document.getElementById('group-booking-consent');
    formData.append('consent', consentCheckbox && consentCheckbox.checked ? 'true' : 'false');
    
    // Marketing consent (optional, pre-checked)
    const marketingConsentCheckbox = document.getElementById('group-marketing-consent');
    formData.append('marketingConsent', marketingConsentCheckbox && marketingConsentCheckbox.checked ? 'true' : 'false');
    
    // Include page URL for tracking
    formData.append('page_url', window.location.href);

    // Get reCAPTCHA v3 token before submission
    const recaptchaResponseInput = document.getElementById('group-booking-recaptcha-response');
    const submitForm = async function() {
      // Ensure reCAPTCHA response is included
      if (recaptchaResponseInput && recaptchaResponseInput.value) {
        formData.append('g-recaptcha-response', recaptchaResponseInput.value);
      }

      try {
        const response = await fetch(ccsData.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();

      // Log response for debugging
      if (isDevelopment) {
        console.log('Group booking response:', data);
      }

      if (data.success) {
        // Generate custom thank you message with course details
        const trainingType = document.getElementById('group-booking-trainingType').value || '';
        const courseNameMap = {
          'core-care-skills': 'Core Care Skills',
          'emergency-first-aid': 'Emergency & First Aid',
          'health-conditions-specialist-care': 'Health Conditions & Specialist Care',
          'medication-management': 'Medication Management',
          'safety-compliance': 'Safety & Compliance',
          'communication-workplace-culture': 'Communication & Workplace Culture',
          'information-data-management': 'Information & Data Management',
          'leadership-professional-development': 'Leadership & Professional Development',
          'nutrition-hygiene': 'Nutrition & Hygiene'
        };
        
        const courseName = data.data?.training_type || courseNameMap[trainingType] || trainingType || 'training course';
        
        const thankYouMessage = data.data?.message || "Thank you! Your enquiry has been passed on to a member of our team.";
        const nextStepsMessage = `We will be in touch regarding your ${courseName} course enquiry. Please keep an eye out for an email from us.`;

        // Try to show thank you modal first, fallback to inline success message
        let feedbackShown = false;
        if (window.showThankYouModal && typeof window.showThankYouModal === 'function') {
          try {
            window.showThankYouModal(thankYouMessage, {
              nextSteps: nextStepsMessage
            });
            feedbackShown = true;
          } catch (error) {
            if (isDevelopment) {
              console.warn('Failed to show thank you modal:', error);
            }
          }
        }

        // Fallback: Show inline success message if modal didn't work
        if (!feedbackShown && successMessage) {
          successMessage.style.display = 'flex';
          successMessage.setAttribute('aria-live', 'assertive');
          successMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          
          // Hide success message after 8 seconds
          setTimeout(() => {
            if (successMessage) {
              successMessage.style.display = 'none';
            }
          }, 8000);
        }

        // Reset form
        form.reset();

        // Reset consent checkbox
        if (consentCheckbox) {
          consentCheckbox.checked = false;
        }
        
        // Reset button state
        isSubmitting = false;
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText || 'Get My Free Quote';
      } else {
        // Handle validation errors from server - show inline
        if (data.data && data.data.errors) {
          const errors = data.data.errors;
          if (errors.name) showError('group-booking-name', 'group-booking-name-error', errors.name);
          if (errors.email) showError('group-booking-email', 'group-booking-email-error', errors.email);
          if (errors.phone) showError('group-booking-phone', 'group-booking-phone-error', errors.phone);
          if (errors.organisation) showError('group-booking-organisation', 'group-booking-organisation-error', errors.organisation);
          if (errors.numberOfStaff) showError('group-booking-numberOfStaff', 'group-booking-numberOfStaff-error', errors.numberOfStaff);
          if (errors.trainingType) showError('group-booking-trainingType', 'group-booking-trainingType-error', errors.trainingType);
          if (errors.discount_code) showError('group-booking-discount-code', 'group-booking-discount-code-error', errors.discount_code);
          if (errors.consent) showError('group-booking-consent', 'group-booking-consent-error', errors.consent);
        } else {
        const errorMessage = data.data?.message || 'There was an error submitting your request. Please try again.';
        if (isDevelopment) {
          console.error('Group booking error:', data);
        }
          showError('group-booking-name', 'group-booking-name-error', errorMessage);
        }
        
        // Reset button state on error
        isSubmitting = false;
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
      }
    } catch (error) {
      if (isDevelopment) {
        console.error('Group booking submission error:', error);
      }
      showError('group-booking-name', 'group-booking-name-error', 'An error occurred while submitting your request. Please try again or call us directly.');
      
      // Reset button state on error
      isSubmitting = false;
      submitButton.disabled = false;
      submitButton.textContent = originalButtonText;
    }
    };

    // Get reCAPTCHA v3 token before submitting
    if (recaptchaResponseInput && typeof ctaGetRecaptchaToken === 'function') {
      ctaGetRecaptchaToken('group-booking', 'submit', function(token) {
        if (token) {
          submitForm();
        } else {
          // Token generation failed, but continue anyway (graceful degradation)
          submitForm();
        }
      });
    } else {
      // No reCAPTCHA configured, submit directly
      submitForm();
    }
  });

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#group-booking-form"]').forEach((anchor) => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Update URL without jumping
        history.pushState(null, null, this.getAttribute('href'));
      }
    });
  });

  // FAQ Category filtering (accordion functionality handled by unified accordion.js)
  function initFAQCategoryFilter() {
    // Accordion functionality now handled by unified accordion.js
    // Only handling category filtering here
    const sidebarBtns = document.querySelectorAll('.faq-sidebar-btn');
    const categoryDropdown = document.getElementById('faq-category-select');
    
    // Category filter function
    function filterByCategory(category) {
      const faqItems = document.querySelectorAll('.accordion[data-accordion-group="group-training-faq"]');
      
      // Close any open accordions first
      const accordionTriggers = document.querySelectorAll('.accordion[data-accordion-group="group-training-faq"] .accordion-trigger');
      accordionTriggers.forEach(trigger => {
        const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
        if (isExpanded) {
          const contentId = trigger.getAttribute('aria-controls');
          const content = document.getElementById(contentId);
          if (content && window.CCSAccordion) {
            window.CCSAccordion.close(trigger, content, 'height');
          }
        }
      });
      
      // Filter items with smooth transition
      faqItems.forEach(item => {
        const itemCategory = item.dataset.category;
        if (category === 'all' || itemCategory === category) {
          item.classList.remove('faq-item-hidden');
          item.classList.add('faq-item-visible');
        } else {
          item.classList.remove('faq-item-visible');
          item.classList.add('faq-item-hidden');
        }
      });
    }
    
    // Sidebar button clicks
    sidebarBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        // Update active state
        sidebarBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Filter by category
        const category = this.dataset.category || 'all';
        filterByCategory(category);
      });
    });
    
    // Accordion click handling now handled by unified accordion.js
    // Removed old accordion code - using unified system
    /*
    const oldFaqQuestions = document.querySelectorAll('.group-faq-question');
    oldFaqQuestions.forEach(question => {
      question.addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        const answerId = this.getAttribute('aria-controls');
        const answer = document.getElementById(answerId);
        
        // Close all other accordions
        faqQuestions.forEach(q => {
          if (q !== question) {
            q.setAttribute('aria-expanded', 'false');
            const otherAnswerId = q.getAttribute('aria-controls');
            const otherAnswer = document.getElementById(otherAnswerId);
            if (otherAnswer) {
              otherAnswer.setAttribute('aria-hidden', 'true');
              otherAnswer.style.maxHeight = '';
            }
          }
        });
        
        // Toggle current accordion
        if (isExpanded) {
          this.setAttribute('aria-expanded', 'false');
          if (answer) {
            answer.setAttribute('aria-hidden', 'true');
            // Clear inline maxHeight to let CSS handle the transition
            answer.style.maxHeight = '';
          }
        } else {
          this.setAttribute('aria-expanded', 'true');
          if (answer) {
            // Temporarily remove max-height constraint to measure actual height
            const currentMaxHeight = answer.style.maxHeight;
            answer.style.maxHeight = 'none';
            const height = answer.scrollHeight;
            // Reset to collapsed state
            answer.style.maxHeight = '0';
            // Set aria-hidden to false
            answer.setAttribute('aria-hidden', 'false');
            // Force a reflow
            void answer.offsetHeight;
            // Now set the actual height for smooth animation
            answer.style.maxHeight = height + 'px';
          }
        }
      });
      
      // Handle keyboard navigation
      question.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.click();
        }
      });
    });
    */
  }

  // Initialize FAQ category filtering when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFAQCategoryFilter);
  } else {
    initFAQCategoryFilter();
  }
})();

