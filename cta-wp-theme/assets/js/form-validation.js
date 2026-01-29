// CTA Form Validation
(function() {
  'use strict';
  
  const form = document.querySelector('.cta-centered-form');
  if (!form) return;

  const nameInput = document.getElementById('cta-centered-name');
  const emailInput = document.getElementById('cta-centered-email');
  const phoneInput = document.getElementById('cta-centered-phone');
  const consentCheckbox = document.getElementById('cta-centered-consent');
  const submitButton = form.querySelector('.cta-centered-submit-button');
  const successMessage = form.querySelector('.cta-centered-success-message');
  
  const nameError = document.getElementById('cta-name-error');
  const emailError = document.getElementById('cta-email-error');
  const phoneError = document.getElementById('cta-phone-error');
  const consentError = document.getElementById('cta-consent-error');
  const consentContainer = document.querySelector('.cta-centered-consent');
  const robotError = document.getElementById('cta-robot-error');
  const recaptchaResponseInput = document.getElementById('cta-recaptcha-response');

  // Track form load time for anti-bot protection
  const formLoadTime = Date.now() / 1000; // Unix timestamp in seconds
  const formLoadTimeInput = document.getElementById('cta-form-load-time');
  if (formLoadTimeInput) {
    formLoadTimeInput.value = formLoadTime.toString();
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phoneRegex = /^[\d\s\-\+\(\)]+$/;

  function validateName() {
    const value = nameInput.value.trim();
    if (!value) {
      showError(nameInput, nameError, 'Name is required');
      return false;
    }
    if (value.length < 2) {
      showError(nameInput, nameError, 'Please enter your full name');
      return false;
    }
    clearError(nameInput, nameError);
    return true;
  }

  function validateEmail() {
    const value = emailInput.value.trim();
    // Email is optional for callback requests
    if (value && !emailRegex.test(value)) {
      showError(emailInput, emailError, 'Please enter a valid email address');
      return false;
    }
    clearError(emailInput, emailError);
    return true;
  }

  function validatePhone() {
    const value = phoneInput.value.trim();
    if (!value) {
      showError(phoneInput, phoneError, 'Phone number is required for callback');
      return false;
    }
    
    // Remove all whitespace and common formatting characters
    let cleaned = value.replace(/[\s\-\(\)\.]/g, '');
    
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
    
    // Must have 10-11 digits
    const digitCount = digitsOnly.length;
    if (digitCount < 10 || digitCount > 11) {
      showError(phoneInput, phoneError, 'Phone number must be 10-11 digits (e.g., 01622 587343 or 07123 456789)');
      return false;
    }
    
    // Must start with 0 and be followed by a non-zero digit (UK format)
    if (!digitsOnly.match(/^0[1-9]/)) {
      if (digitsOnly.match(/^[1-9]\d{9,10}$/)) {
        showError(phoneInput, phoneError, 'UK phone numbers should start with 0 (e.g., 01622 587343)');
      } else {
        showError(phoneInput, phoneError, 'Please enter a valid UK phone number (e.g., 01622 587343 or 07123 456789)');
      }
      return false;
    }
    
    // More specific pattern matching for UK numbers (using digitsOnly to ensure clean validation)
    const ukPhonePattern = /^0[1-9]\d{8,9}$/;
    if (!ukPhonePattern.test(digitsOnly)) {
      showError(phoneInput, phoneError, 'Please enter a valid UK phone number format');
      return false;
    }
    
    // Check for suspicious patterns (repeating digits)
    if (digitsOnly.match(/^0(\d)\1{8,9}$/)) {
      showError(phoneInput, phoneError, 'Please enter a valid phone number');
      return false;
    }
    
    clearError(phoneInput, phoneError);
    return true;
  }

  function validateConsent() {
    if (!consentCheckbox || !consentCheckbox.checked) {
      if (consentCheckbox) {
        consentCheckbox.setAttribute('aria-invalid', 'true');
        consentCheckbox.classList.add('error');
      }
      if (consentError) {
        consentError.textContent = 'You must consent to being contacted';
        consentError.setAttribute('aria-live', 'assertive');
        consentError.style.display = 'block';
      }
      return false;
    }
    if (consentCheckbox) {
      consentCheckbox.setAttribute('aria-invalid', 'false');
      consentCheckbox.classList.remove('error');
    }
    if (consentError) {
      consentError.textContent = '';
      consentError.setAttribute('aria-live', 'polite');
      consentError.style.display = 'none';
    }
    return true;
  }

  function showError(input, errorElement, message) {
    if (input) {
      input.classList.add('error');
      input.setAttribute('aria-invalid', 'true');
      // Find error icon - it's in the parent input-box, not direct parent
      const inputBox = input.closest('.cta-centered-input-box');
      const errorIcon = inputBox?.querySelector('.cta-centered-error-icon');
      if (errorIcon) {
        errorIcon.classList.remove('hidden');
        errorIcon.style.display = 'flex';
      }
    }
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.setAttribute('aria-live', 'assertive');
      errorElement.style.display = 'block';
    }
  }

  function clearError(input, errorElement) {
    if (input) {
      input.classList.remove('error');
      input.setAttribute('aria-invalid', 'false');
      const errorIcon = input.parentElement?.parentElement?.querySelector('.cta-centered-error-icon');
      if (errorIcon) {
        errorIcon.classList.add('hidden');
        errorIcon.style.display = 'none';
      }
    }
    if (errorElement) {
      errorElement.textContent = '';
      errorElement.setAttribute('aria-live', 'polite');
      errorElement.style.display = 'none';
    }
  }

  function validateRobot() {
    // reCAPTCHA v3 is invisible - token will be generated on form submission
    // Just check if reCAPTCHA is configured (input exists)
    if (recaptchaResponseInput) {
      // Clear any error messages
      if (robotError) {
        robotError.textContent = '';
        robotError.setAttribute('aria-live', 'polite');
        robotError.style.display = 'none';
      }
      return true; // Token will be generated on submit
    }
    // No reCAPTCHA configured, skip validation
    return true;
  }

  function validateForm() {
    const nameValid = validateName();
    const emailValid = validateEmail();
    const phoneValid = validatePhone();
    const consentValid = validateConsent();
    const robotValid = validateRobot();
    return nameValid && emailValid && phoneValid && consentValid && robotValid;
  }

  nameInput.addEventListener('blur', validateName);
  emailInput.addEventListener('blur', validateEmail);
  phoneInput.addEventListener('blur', validatePhone);
  consentCheckbox.addEventListener('change', validateConsent);

  nameInput.addEventListener('input', function() {
    if (this.classList.contains('error')) {
      clearError(this, nameError);
    }
  });

  emailInput.addEventListener('input', function() {
    if (this.classList.contains('error')) {
      clearError(this, emailError);
    }
  });

  phoneInput.addEventListener('input', function() {
    if (this.classList.contains('error')) {
      clearError(this, phoneError);
    }
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Honeypot spam protection - check all honeypot fields
    const honeypotFields = ['cta-website', 'cta-url', 'cta-homepage'];
    for (let i = 0; i < honeypotFields.length; i++) {
      const field = document.getElementById(honeypotFields[i]);
      if (field && field.value !== '') {
        // Bot detected - silently reject
        return;
      }
    }
    
    if (!validateForm()) {
      if (nameInput.classList.contains('error')) {
        nameInput.focus();
      } else if (emailInput.classList.contains('error')) {
        emailInput.focus();
      } else if (phoneInput.classList.contains('error')) {
        phoneInput.focus();
      } else if (consentCheckbox.classList.contains('error')) {
        consentCheckbox.focus();
      }
      return;
    }

    // Enhanced loading state
    submitButton.disabled = true;
    submitButton.classList.add('loading');
    form.classList.add('form-submitting');
    form.setAttribute('aria-busy', 'true');
    
    // Show spinner, hide button text
    const buttonText = submitButton.querySelector('.cta-button-text');
    const buttonSpinner = submitButton.querySelector('.cta-button-spinner');
    const buttonIcon = submitButton.querySelector('.cta-button-icon');
    
    if (buttonText) buttonText.style.display = 'none';
    if (buttonSpinner) {
      buttonSpinner.classList.remove('hidden');
      buttonSpinner.style.display = 'inline-block';
    }
    if (buttonIcon) buttonIcon.style.display = 'none';
    
    // Update button aria-label for screen readers
    submitButton.setAttribute('aria-label', 'Submitting your request, please wait...');

    // Get form data
    const formData = new FormData(form);
    formData.append('action', 'cta_callback_request');
    
    // Ensure nonce is included (from wp_nonce_field)
    const nonceInput = form.querySelector('input[name="nonce"]');
    if (nonceInput && !formData.has('nonce')) {
      formData.append('nonce', nonceInput.value);
    }
    
    // If ctaData has a nonce, use it (more reliable)
    if (typeof ctaData !== 'undefined' && ctaData.nonce) {
      formData.set('nonce', ctaData.nonce);
    }
    
    // Add submission timestamp for anti-bot protection
    const submissionTime = Date.now() / 1000; // Unix timestamp in seconds
    formData.set('submission_time', submissionTime.toString());
    
    // Ensure form load time is set
    if (!formData.has('form_load_time') && formLoadTimeInput) {
      formData.set('form_load_time', formLoadTimeInput.value);
    }
    
    formData.append('page_url', window.location.href);

    // Check if WordPress AJAX is available
    if (typeof ctaData === 'undefined' || !ctaData.ajaxUrl) {
      alert('Configuration error. Please contact us directly.');
      submitButton.disabled = false;
      submitButton.classList.remove('loading');
      form.classList.remove('form-submitting');
      form.setAttribute('aria-busy', 'false');
      if (buttonText) buttonText.style.display = 'inline';
      if (buttonSpinner) {
        buttonSpinner.classList.add('hidden');
        buttonSpinner.style.display = 'none';
      }
      if (buttonIcon) buttonIcon.style.display = 'inline-block';
      return;
    }

    // Get reCAPTCHA v3 token before submission
    const submitForm = function() {
      // Ensure reCAPTCHA response is included
      if (recaptchaResponseInput && recaptchaResponseInput.value) {
        formData.append('g-recaptcha-response', recaptchaResponseInput.value);
      }

      // Submit via WordPress AJAX
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
      // Reset loading state
      submitButton.disabled = false;
      submitButton.classList.remove('loading');
      form.classList.remove('form-submitting');
      form.setAttribute('aria-busy', 'false');
      
      // Show spinner, hide button text
      if (buttonText) buttonText.style.display = 'inline';
      if (buttonSpinner) {
        buttonSpinner.classList.add('hidden');
        buttonSpinner.style.display = 'none';
      }
      if (buttonIcon) buttonIcon.style.display = 'inline-block';
      
      // Reset button aria-label
      submitButton.setAttribute('aria-label', 'Submit callback request');
      
      if (data.success) {
        // Show success message inline
        if (successMessage) {
          successMessage.classList.remove('hidden');
          successMessage.style.display = 'flex';
          successMessage.setAttribute('aria-live', 'assertive');
          
          // Scroll to success message if needed
          successMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        // Reset form
        form.reset();
        if (consentCheckbox) {
          consentCheckbox.checked = false;
        }
        clearError(nameInput, nameError);
        clearError(emailInput, emailError);
        clearError(phoneInput, phoneError);
        clearError(consentCheckbox, consentError);
        
        // Hide success message after 5 seconds
        if (successMessage) {
          setTimeout(function() {
            successMessage.classList.add('hidden');
            successMessage.style.display = 'none';
          }, 5000);
        }
      } else {
        // Handle errors
        if (data.data && data.data.errors) {
          const errors = data.data.errors;
          if (errors.name) showError(nameInput, nameError, errors.name);
          if (errors.email) showError(emailInput, emailError, errors.email);
          if (errors.phone) showError(phoneInput, phoneError, errors.phone);
          if (errors.consent) showError(consentCheckbox, consentError, errors.consent);
        } else {
          alert(data.data && data.data.message ? data.data.message : 'An error occurred. Please try again.');
        }
      }
    })
    .catch(error => {
      console.error('Form submission error:', error);
      submitButton.disabled = false;
      submitButton.classList.remove('loading');
      form.classList.remove('form-submitting');
      form.setAttribute('aria-busy', 'false');
      
      if (buttonText) buttonText.style.display = 'inline';
      if (buttonSpinner) {
        buttonSpinner.classList.add('hidden');
        buttonSpinner.style.display = 'none';
      }
      if (buttonIcon) buttonIcon.style.display = 'inline-block';
      
      alert('An error occurred while submitting your request. Please try again or call us directly.');
    });
    };

    // Get reCAPTCHA v3 token before submitting
    if (recaptchaResponseInput && typeof ctaGetRecaptchaToken === 'function') {
      ctaGetRecaptchaToken('cta', 'submit', function(token) {
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
})();

