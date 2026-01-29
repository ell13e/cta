// Contact Form Handler
// Handles form validation, submission, and user feedback

(function() {
  'use strict';

  // Development mode flag
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  // DOM elements - will be set in init()
  let form;
  let submitButton;
  let submitText;
  let submitLoading;
  let successMessage;
  let clearButton;
  
  // Form fields - will be set in init()
  let nameInput;
  let emailInput;
  let phoneInput;
  let organisationInput;
  let enquiryTypeSelect;
  let messageTextarea;
  let consentCheckbox;
  let courseSelectionField;
  let courseSelect;
  let courseSelectionList;
  let courseSelectionContainer;
  
  // Error elements - will be set in init()
  let nameError;
  let emailError;
  let messageError;
  let enquiryTypeError;

  // State
  let isSubmitting = false;
  let errors = {};
  
  // Error message constants - more helpful and contextual
  const ERROR_MESSAGES = {
    name: 'Please enter your name',
    email: 'Please enter your email address',
    emailInvalid: 'Please enter a valid email address (e.g., name@example.com)',
    phone: 'Please enter your phone number',
    phoneInvalid: 'Please enter a valid UK phone number (e.g., 01622 587343 or 07123 456789)',
    enquiryType: 'Please select how we can help you',
    message: 'Please tell us about your enquiry',
    messageMaxLength: 'Your message is too long. Please keep it under 1000 characters.',
    consent: 'Please confirm you agree to be contacted',
    selectedCourses: 'Please select at least one course you\'re interested in'
  };

  // Format UK phone number as user types
  function formatPhoneNumber(value) {
    // Remove all non-digit characters except +
    let cleaned = value.replace(/[^\d+]/g, '');
    
    // If starts with +44, keep it
    if (cleaned.startsWith('+44')) {
      cleaned = cleaned.replace('+44', '0');
    }
    
    // Remove leading 0 if it's followed by another 0 (0044 -> 44)
    if (cleaned.startsWith('0044')) {
      cleaned = '0' + cleaned.substring(4);
    }
    
    // Remove all non-digits
    cleaned = cleaned.replace(/\D/g, '');
    
    // Format based on length
    if (cleaned.length === 0) {
      return '';
    } else if (cleaned.length <= 3) {
      return cleaned;
    } else if (cleaned.length <= 6) {
      return cleaned.substring(0, 3) + ' ' + cleaned.substring(3);
    } else if (cleaned.length <= 10) {
      return cleaned.substring(0, 3) + ' ' + cleaned.substring(3, 6) + ' ' + cleaned.substring(6);
    } else {
      // For longer numbers (international format)
      return cleaned.substring(0, 3) + ' ' + cleaned.substring(3, 6) + ' ' + cleaned.substring(6, 10) + ' ' + cleaned.substring(10);
    }
  }

  // Validate UK phone number format
  function validatePhone(phoneValue) {
    if (!phoneValue || !phoneValue.trim()) {
      return { valid: false, error: ERROR_MESSAGES.phone };
    }
    
    const original = phoneValue.trim();
    
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
    
    // Must have 10-11 digits
    const digitCount = digitsOnly.length;
    if (digitCount < 10 || digitCount > 11) {
      return { valid: false, error: 'Phone number must be 10-11 digits (e.g., 01622 587343 or 07123 456789)' };
    }
    
    // Must start with 0 and be followed by a non-zero digit (UK format)
    if (!digitsOnly.match(/^0[1-9]/)) {
      // Check if it's all digits but missing leading 0
      if (digitsOnly.match(/^[1-9]\d{9,10}$/)) {
        return { valid: false, error: 'UK phone numbers should start with 0 (e.g., 01622 587343)' };
      }
      return { valid: false, error: ERROR_MESSAGES.phoneInvalid };
    }
    
    // More specific pattern matching for UK numbers (using digitsOnly to ensure clean validation)
    // Mobile: 07xxx xxxxxx (11 digits starting with 07)
    // Landline: 01xxx xxxxxx (10 digits) or 02x xxxx xxxx (10-11 digits)
    // Non-geographic: 03xx, 05xx, 08xx, 09xx (10-11 digits)
    const ukPhonePattern = /^0[1-9]\d{8,9}$/;
    
    if (!ukPhonePattern.test(digitsOnly)) {
      return { valid: false, error: 'Please enter a valid UK phone number format' };
    }
    
    // Additional validation: Check for suspicious patterns
    // Repeating digits (e.g., 0000000000, 1111111111)
    if (digitsOnly.match(/^0(\d)\1{8,9}$/)) {
      return { valid: false, error: 'Please enter a valid phone number' };
    }
    
    return { valid: true, error: null };
  }

  // Initialize form
  function init() {
    // Get DOM elements after DOM is ready
    form = document.getElementById('contact-form');
    if (!form) return; // Exit if form doesn't exist
    
    submitButton = document.getElementById('contact-form-submit');
    submitText = document.getElementById('contact-form-submit-text');
    submitLoading = document.getElementById('contact-form-submit-loading');
    successMessage = document.getElementById('contact-form-success');
    clearButton = document.getElementById('contact-form-clear');

    // Track form load time for anti-bot protection
    const formLoadTime = Date.now() / 1000; // Unix timestamp in seconds
    const formLoadTimeInput = document.getElementById('contact-form-load-time');
    if (formLoadTimeInput) {
      formLoadTimeInput.value = formLoadTime.toString();
    }
    
    // Form fields
    nameInput = document.getElementById('name');
    emailInput = document.getElementById('email');
    phoneInput = document.getElementById('phone');
    organisationInput = document.getElementById('organisation');
    enquiryTypeSelect = document.getElementById('enquiryType');
    messageTextarea = document.getElementById('message');
    consentCheckbox = document.getElementById('contact-consent');
    courseSelectionField = document.getElementById('course-selection-field');
    courseSelect = document.getElementById('selectedCourses');
    courseSelectionList = document.getElementById('course-selection-list');
    courseSelectionContainer = document.getElementById('course-selection-container');
    
    // Error elements
    nameError = document.getElementById('name-error');
    emailError = document.getElementById('email-error');
    messageError = document.getElementById('message-error');
    enquiryTypeError = document.getElementById('enquiryType-error');

    // Auto-fill enquiry type from URL params or referrer
    autoFillEnquiryType();

    // Add event listeners
    form.addEventListener('submit', handleSubmit);
    
    // Clear form button
    if (clearButton) {
      clearButton.addEventListener('click', (e) => {
        e.preventDefault();
        // Confirm before clearing if form has content
        const hasContent = nameInput.value.trim() || 
                          emailInput.value.trim() || 
                          phoneInput.value.trim() || 
                          (messageTextarea && messageTextarea.value.trim());
        
        if (hasContent) {
          const confirmed = window.confirm('Are you sure you want to clear the form? All entered information will be lost.');
          if (!confirmed) {
            return;
          }
        }
        
        // Clear the form
        resetForm();
        
        // Scroll to top of form smoothly
        const formElement = document.getElementById('contact-form');
        if (formElement) {
          formElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Focus first field on desktop only
        if (window.innerWidth >= 768 && nameInput) {
          setTimeout(() => {
            nameInput.focus();
          }, 300);
        }
      });
    }
    
    // Debounce function for input validation
    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }

    // Real-time validation on input (debounced)
    const debouncedNameValidation = debounce(() => {
      clearError('name');
      updateProgress();
      updateFieldValidation('name');
    }, 300);

    const debouncedEmailValidation = debounce(() => {
      clearError('email');
      updateProgress();
      updateFieldValidation('email');
    }, 300);

    const debouncedPhoneValidation = debounce(() => {
      clearError('phone');
      updateProgress();
      updateFieldValidation('phone');
    }, 300);

    nameInput.addEventListener('input', debouncedNameValidation);
    emailInput.addEventListener('input', debouncedEmailValidation);
    
    // Phone input with auto-formatting
    phoneInput.addEventListener('input', (e) => {
      const cursorPosition = e.target.selectionStart;
      const formatted = formatPhoneNumber(e.target.value);
      e.target.value = formatted;
      
      // Restore cursor position (adjust for added spaces)
      const spacesBefore = (e.target.value.substring(0, cursorPosition).match(/\s/g) || []).length;
      const spacesAfter = (formatted.substring(0, cursorPosition).match(/\s/g) || []).length;
      const newPosition = cursorPosition + (spacesAfter - spacesBefore);
      e.target.setSelectionRange(newPosition, newPosition);
      
      debouncedPhoneValidation();
      saveFormToLocalStorage();
    });
    if (organisationInput) {
      organisationInput.addEventListener('input', () => updateProgress());
    }
    if (enquiryTypeSelect) {
      enquiryTypeSelect.addEventListener('change', () => {
        clearError('enquiryType');
        updateProgress();
        handleEnquiryTypeChange();
        trackEnquiryTypeSelection();
      });
    }
    
    // Character counter for message (debounced)
    const debouncedMessageValidation = debounce(() => {
      clearError('message');
      updateProgress();
      updateFieldValidation('message');
    }, 300);

    messageTextarea.addEventListener('input', () => {
      updateCharacterCounter(); // Update counter immediately for better UX
      debouncedMessageValidation();
      saveFormToLocalStorage();
    });
    
    // Auto-save form data to localStorage
    [nameInput, emailInput].forEach(input => {
      if (input) {
        input.addEventListener('input', saveFormToLocalStorage);
      }
    });
    
    if (enquiryTypeSelect) {
      enquiryTypeSelect.addEventListener('change', saveFormToLocalStorage);
    }
    
    if (consentCheckbox) {
      consentCheckbox.addEventListener('change', saveFormToLocalStorage);
    }
    
    // Initial character counter update
    if (messageTextarea) {
      updateCharacterCounter();
      // Also update on any existing content
      messageTextarea.addEventListener('paste', () => {
        setTimeout(updateCharacterCounter, 10);
      });
    }
    
    // Initial enquiry type check
    handleEnquiryTypeChange();
    
    // Initial progress update
    updateProgress();

    // Validation on blur - but don't refocus on mobile to prevent keyboard jumping
    const handleBlurValidation = (fieldName) => {
      // Only validate, don't refocus on mobile/tablet
      validateField(fieldName);
    };
    
    nameInput.addEventListener('blur', () => handleBlurValidation('name'));
    emailInput.addEventListener('blur', () => handleBlurValidation('email'));
    phoneInput.addEventListener('blur', () => handleBlurValidation('phone'));
    messageTextarea.addEventListener('blur', () => handleBlurValidation('message'));
    if (enquiryTypeSelect) {
      enquiryTypeSelect.addEventListener('blur', () => handleBlurValidation('enquiryType'));
    }
    if (consentCheckbox) {
      consentCheckbox.addEventListener('change', () => {
        if (consentCheckbox.checked) {
          clearError('consent');
        }
      });
    }
    
    // Initial progress update
    updateProgress();
    
    // Restore form from localStorage on page load
    restoreFormFromLocalStorage();

    // Collect and populate metadata tracking
    collectMetadata();

    // Scroll to form handler for CTA button
    const ctaButton = document.querySelector('.contact-providers-cta-button');
    if (ctaButton) {
      ctaButton.addEventListener('click', (e) => {
        e.preventDefault();
        scrollToForm();
      });
    }

    // Jump to form link handler
    const jumpLink = document.getElementById('contact-hero-jump-link');
    if (jumpLink) {
      jumpLink.addEventListener('click', (e) => {
        e.preventDefault();
        scrollToForm();
      });
    }

    // Floating CTA handler
    const floatingCTA = document.getElementById('contact-floating-cta');
    if (floatingCTA) {
      const floatingLink = floatingCTA.querySelector('a');
      if (floatingLink) {
        floatingLink.addEventListener('click', (e) => {
          e.preventDefault();
          scrollToForm();
        });
      }

      // Show/hide floating CTA based on scroll position
      let lastScrollTop = 0;
      window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const formElement = document.getElementById('contact-form');
        
        if (formElement && window.innerWidth < 768) {
          const formTop = formElement.getBoundingClientRect().top + window.pageYOffset;
          const formVisible = formTop > scrollTop + window.innerHeight;
          
          if (formVisible && scrollTop > 200) {
            floatingCTA.classList.add('visible');
          } else {
            floatingCTA.classList.remove('visible');
        }
        }
        
        lastScrollTop = scrollTop;
      });
    }
  }

  // Auto-fill enquiry type from URL params or referrer
  function autoFillEnquiryType() {
    if (!enquiryTypeSelect) return;

    // Method 1: URL parameter (primary method)
    const urlParams = new URLSearchParams(window.location.search);
    const typeParam = urlParams.get('type');
    
    if (typeParam) {
      // Map URL param values to dropdown values
      const typeMapping = {
        'training-consultation': 'training-consultation',
        'group-training': 'group-training',
        'book-course': 'book-course',
        'cqc-training': 'cqc-training',
        'support': 'support',
        'general': 'general'
      };
      
      const mappedValue = typeMapping[typeParam] || typeParam;
      if (enquiryTypeSelect.querySelector(`option[value="${mappedValue}"]`)) {
        enquiryTypeSelect.value = mappedValue;
        // Track auto-fill from URL param
        trackEnquiryTypeSelection('url_param', mappedValue);
        return;
      }
    }

    // Method 2: Referrer detection (fallback)
    const referrer = document.referrer;
    if (referrer) {
      const referrerUrl = new URL(referrer);
      const referrerPath = referrerUrl.pathname.toLowerCase();
      const referrerHost = referrerUrl.hostname.toLowerCase();
      
      // Only process if referrer is from same domain
      const currentHost = window.location.hostname.toLowerCase();
      if (referrerHost === currentHost || referrerHost.includes('continuitytrainingacademy')) {
        let detectedType = null;
        
        // Check referrer path
        if (referrerPath.includes('group-training')) {
          detectedType = 'group-training';
        } else if (referrerPath.includes('calendar') || referrerPath.includes('courses')) {
          detectedType = 'book-course';
        } else if (referrerPath.includes('cqc-changes') || referrerPath.includes('news') || referrerPath.includes('article')) {
          detectedType = 'cqc-training';
        } else if (referrerPath.includes('faq')) {
          detectedType = 'support';
        } else if (referrerPath.includes('index') || referrerPath === '/' || referrerPath === '') {
          // Check for callback CTAs from homepage
          const referrerHash = referrerUrl.hash;
          if (referrerHash && referrerHash.includes('callback')) {
            detectedType = 'schedule-call';
          }
        }
        
        if (detectedType && enquiryTypeSelect.querySelector(`option[value="${detectedType}"]`)) {
          enquiryTypeSelect.value = detectedType;
          // Track auto-fill from referrer
          trackEnquiryTypeSelection('referrer', detectedType);
        }
      }
    }
  }

  // Track enquiry type selection for analytics
  function trackEnquiryTypeSelection(source, value) {
    if (!enquiryTypeSelect) return;
    
    const selectedValue = value || enquiryTypeSelect.value;
    if (!selectedValue) return;

    // Google Analytics 4 event
    if (typeof gtag !== 'undefined') {
      gtag('event', 'enquiry_type_selected', {
        'enquiry_type': selectedValue,
        'source': source || 'manual',
        'page_location': window.location.href
      });
    }

    // Universal Analytics fallback
    if (typeof ga !== 'undefined') {
      ga('send', 'event', 'Contact Form', 'Enquiry Type Selected', selectedValue);
    }

    // Custom analytics event
    if (typeof window.trackEvent === 'function') {
      window.trackEvent('enquiry_type_selected', {
        type: selectedValue,
        source: source || 'manual'
      });
    }

    // Console log for debugging (remove in production if needed)
    if (isDevelopment) {
      // Log in development only
    }
  }

  // Scroll to form helper function
  function scrollToForm() {
    const formElement = document.getElementById('contact-form');
    if (formElement) {
      formElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
      // Focus first input
      setTimeout(() => {
        if (nameInput) {
          nameInput.focus();
        }
      }, 500);
    }
  }

  // Update progress indicator (only counts required fields)
  function updateProgress() {
    // Progress indicator elements (may not exist on all pages)
    const progressFill = document.getElementById('contact-form-progress-fill');
    const progressCurrent = document.getElementById('contact-form-progress-current');
    const progressTotal = document.getElementById('contact-form-progress-total');
    
    if (!progressFill || !progressCurrent) return;
    
    let completed = 0;
    const totalRequiredFields = 4; // name, email, enquiryType, message
    // Count only required fields
    if (nameInput && nameInput.value.trim()) completed++;
    if (emailInput && emailInput.value.trim()) completed++;
    if (enquiryTypeSelect && enquiryTypeSelect.value) completed++;
    if (messageTextarea && messageTextarea.value.trim()) completed++;
    
    const percentage = (completed / totalRequiredFields) * 100;
    if (progressFill) {
      progressFill.style.width = percentage + '%';
    }
    if (progressCurrent) {
      progressCurrent.textContent = completed;
    }
    if (progressTotal) {
      progressTotal.textContent = totalRequiredFields;
    }
  }

  // Update character counter
  function updateCharacterCounter() {
    const counter = document.getElementById('message-counter');
    if (counter && messageTextarea) {
      const length = messageTextarea.value.length;
      const maxLength = parseInt(messageTextarea.getAttribute('maxlength')) || 1000;
      counter.textContent = `${length}/${maxLength}`;
      
      // Update counter styling based on remaining characters
      const remaining = maxLength - length;
      counter.classList.remove('contact-form-char-counter-warning', 'contact-form-char-counter-error');
      if (remaining < 50 && remaining >= 0) {
        counter.classList.add('contact-form-char-counter-warning');
      } else if (remaining < 0) {
        counter.classList.add('contact-form-char-counter-error');
      }
    }
  }

  // Load courses for checkbox list
  let coursesLoaded = false;
  async function loadCourses() {
    if (coursesLoaded || !courseSelectionList) return;
    
    const loadingEl = document.getElementById('course-selection-loading');
    
    try {
      const response = await fetch(ctaData.ajaxUrl + '?action=cta_get_courses');
      const data = await response.json();
      
      if (data.success && data.data.courses) {
        if (loadingEl) {
          loadingEl.style.display = 'none';
        }
        
        if (data.data.courses.length === 0) {
          courseSelectionList.innerHTML = '<p class="course-selection-empty">No courses available</p>';
          courseSelectionList.style.display = 'block';
        } else {
          courseSelectionList.innerHTML = '';
          
          // Create a scrollable container with max height
          const coursesWrapper = document.createElement('div');
          coursesWrapper.className = 'course-selection-checkboxes';
          
          data.data.courses.forEach(course => {
            const checkboxWrapper = document.createElement('div');
            checkboxWrapper.className = 'course-selection-checkbox-item';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = `course-${course.id}`;
            checkbox.name = 'selectedCourses[]';
            checkbox.value = course.id;
            checkbox.className = 'course-selection-checkbox';
            
            const label = document.createElement('label');
            label.htmlFor = `course-${course.id}`;
            label.className = 'course-selection-label';
            label.textContent = course.title;
            
            checkboxWrapper.appendChild(checkbox);
            checkboxWrapper.appendChild(label);
            coursesWrapper.appendChild(checkboxWrapper);
          });
          
          courseSelectionList.appendChild(coursesWrapper);
          courseSelectionList.style.display = 'block';
        }
        
        coursesLoaded = true;
      }
    } catch (error) {
      if (isDevelopment) {
        console.error('Error loading courses:', error);
      }
      if (loadingEl) {
        loadingEl.style.display = 'none';
      }
      if (courseSelectionList) {
        courseSelectionList.innerHTML = '<p class="course-selection-error">Error loading courses. Please refresh the page.</p>';
        courseSelectionList.style.display = 'block';
      }
    }
  }

  // Handle enquiry type change for conditional fields
  function handleEnquiryTypeChange() {
    const orgWrapper = document.getElementById('organisation-field-wrapper');
    if (!enquiryTypeSelect) return;
    
    const value = enquiryTypeSelect.value;
    const showOrg = value === 'booking' || value === 'group';
    const showCourses = value === 'book-course' || value === 'group-training';
    const showDiscountCode = value === 'book-course' || value === 'group-training';
    
    // Handle organisation field
    if (orgWrapper) {
    if (showOrg) {
      orgWrapper.style.display = 'block';
      // Update grid to show both fields
      const grid = orgWrapper.closest('.contact-form-grid');
      if (grid) {
        grid.style.gridTemplateColumns = 'repeat(2, 1fr)';
      }
    } else {
      orgWrapper.style.display = 'none';
      // Reset grid if needed
      const grid = orgWrapper.closest('.contact-form-grid');
      if (grid && !showOrg) {
        // Keep grid layout but hide the field
        }
      }
    }
    
    // Handle course selection field
    if (courseSelectionField) {
      if (showCourses) {
        courseSelectionField.style.display = 'block';
        // Load courses if not already loaded
        if (!coursesLoaded && courseSelectionList) {
          loadCourses();
        }
      } else {
        courseSelectionField.style.display = 'none';
        // Clear selection (uncheck all checkboxes)
        if (courseSelectionList) {
          const checkboxes = courseSelectionList.querySelectorAll('input[type="checkbox"]');
          checkboxes.forEach(cb => cb.checked = false);
        }
      }
    }
  }

  // Collect metadata for form submission tracking
  function collectMetadata() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // UTM parameters
    const utmSource = document.getElementById('utm_source');
    const utmMedium = document.getElementById('utm_medium');
    const utmCampaign = document.getElementById('utm_campaign');
    
    if (utmSource) utmSource.value = urlParams.get('utm_source') || '';
    if (utmMedium) utmMedium.value = urlParams.get('utm_medium') || '';
    if (utmCampaign) utmCampaign.value = urlParams.get('utm_campaign') || '';
    
    // Referrer (external source)
    const referrerField = document.getElementById('referrer');
    if (referrerField) {
      const referrer = document.referrer;
      if (referrer) {
        try {
          const referrerUrl = new URL(referrer);
          const currentHost = window.location.hostname.toLowerCase();
          const referrerHost = referrerUrl.hostname.toLowerCase();
          
          // Only store external referrers (not same domain)
          if (referrerHost !== currentHost && !referrerHost.includes('continuitytrainingacademy')) {
            referrerField.value = referrer;
          }
        } catch (e) {
          // Invalid URL, store as-is
          referrerField.value = referrer;
        }
      }
    }
    
    // Internal source (which page on our site they came from)
    const internalSourceField = document.getElementById('internal_source');
    if (internalSourceField) {
      const sourceParam = urlParams.get('source');
      if (sourceParam) {
        internalSourceField.value = sourceParam;
      } else {
        // Try to detect from referrer if same domain
        const referrer = document.referrer;
        if (referrer) {
          try {
            const referrerUrl = new URL(referrer);
            const currentHost = window.location.hostname.toLowerCase();
            const referrerHost = referrerUrl.hostname.toLowerCase();
            
            if (referrerHost === currentHost || referrerHost.includes('continuitytrainingacademy')) {
              internalSourceField.value = referrerUrl.pathname;
            }
          } catch (e) {
            // Invalid URL, ignore
          }
        }
      }
    }
  }

  // Handle form submission
  function handleSubmit(e) {
    e.preventDefault();

    if (isSubmitting) return;

    // Honeypot spam protection - check all honeypot fields
    const honeypotFields = ['website', 'contact-url', 'contact-homepage'];
    for (let i = 0; i < honeypotFields.length; i++) {
      const field = document.getElementById(honeypotFields[i]);
      if (field && field.value !== '') {
        // Bot detected - silently reject
        return;
      }
    }

    // Validate form
    const validationErrors = validateForm();
    
    if (Object.keys(validationErrors).length > 0) {
      errors = validationErrors;
      displayErrors();
      
      // Don't trap focus on mobile - allow user to scroll/exit
      // Only focus first error field on desktop (larger screens)
      if (window.innerWidth >= 768) {
        const firstErrorField = Object.keys(validationErrors)[0];
        const firstErrorElement = document.getElementById(firstErrorField);
        if (firstErrorElement) {
          // Small delay to allow error messages to render
          setTimeout(() => {
            firstErrorElement.focus();
          }, 100);
        }
      }
      // On mobile, don't focus - let user scroll naturally
      return;
    }

    // Update timestamp before submission
    const timestampField = document.getElementById('submission_timestamp');
    if (timestampField) {
      timestampField.value = new Date().toISOString();
    }

    // Clear previous errors
    errors = {};
    clearAllErrors();

    // Submit form
    submitForm();
  }

  // Validate form
  function validateForm() {
    const newErrors = {};

    // Name validation
    const nameValue = nameInput.value.trim();
    if (!nameValue) {
      newErrors.name = ERROR_MESSAGES.name;
    }

    // Email validation
    const emailValue = emailInput.value.trim();
    if (!emailValue) {
      newErrors.email = ERROR_MESSAGES.email;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
      newErrors.email = ERROR_MESSAGES.emailInvalid;
    }

    // Phone validation
    const phoneValue = phoneInput.value.trim();
    const phoneValidation = validatePhone(phoneValue);
    if (!phoneValidation.valid) {
      newErrors.phone = phoneValidation.error;
    }

    // Enquiry type validation
    if (enquiryTypeSelect) {
      const enquiryTypeValue = enquiryTypeSelect.value;
      if (!enquiryTypeValue) {
        newErrors.enquiryType = ERROR_MESSAGES.enquiryType;
      }
    }

    // Message validation
    const messageValue = messageTextarea.value.trim();
    if (!messageValue) {
      newErrors.message = ERROR_MESSAGES.message;
    } else {
      const maxLength = parseInt(messageTextarea.getAttribute('maxlength')) || 1000;
      if (messageValue.length > maxLength) {
        newErrors.message = ERROR_MESSAGES.messageMaxLength;
      }
    }

    // Consent validation
    if (!consentCheckbox || !consentCheckbox.checked) {
      newErrors.consent = ERROR_MESSAGES.consent;
    }

    // Course selection validation (for book-course and group-training)
    // Only validate if the course selection field is visible
    const enquiryValue = enquiryTypeSelect ? enquiryTypeSelect.value : '';
    if ((enquiryValue === 'book-course' || enquiryValue === 'group-training') && courseSelectionField && courseSelectionList) {
      // Check if field is actually visible
      const isVisible = courseSelectionField.style.display !== 'none' && 
                       courseSelectionField.offsetParent !== null;
      
      if (isVisible) {
        const selectedCheckboxes = courseSelectionList.querySelectorAll('input[type="checkbox"]:checked');
        if (selectedCheckboxes.length === 0) {
          newErrors.selectedCourses = 'Please select at least one course';
        }
      }
    }

    return newErrors;
  }

  // Display errors
  function displayErrors() {
    const errorSummary = document.getElementById('contact-form-error-summary');
    const errorList = document.getElementById('contact-form-error-list');
    const errorCount = Object.keys(errors).length;
    const isMobile = window.innerWidth < 768;

    // Show/hide error summary
    if (errorSummary && errorList) {
      if (errorCount > 0) {
        errorList.innerHTML = '';
        const fieldOrder = ['name', 'email', 'phone', 'enquiryType', 'selectedCourses', 'message', 'consent'];
        const orderedFields = fieldOrder.filter(field => errors[field]);
        
        orderedFields.forEach((field, index) => {
          const li = document.createElement('li');
          const link = document.createElement('a');
          // Map field names to actual element IDs
          const fieldIdMap = {
            'name': 'name',
            'email': 'email',
            'phone': 'phone',
            'enquiryType': 'enquiryType',
            'selectedCourses': 'course-selection-field',
            'discount_code': 'discount-code',
            'message': 'message',
            'consent': 'contact-consent'
          };
          link.href = `#${fieldIdMap[field] || field}`;
          link.textContent = errors[field];
          link.className = 'contact-form-error-link';
          link.addEventListener('click', (e) => {
            e.preventDefault();
            const fieldElement = document.getElementById(fieldIdMap[field] || field);
            if (fieldElement) {
              // Only focus on desktop - on mobile, just scroll
              if (!isMobile && field !== 'selectedCourses') {
                fieldElement.focus();
              }
              fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          });
          li.appendChild(link);
          errorList.appendChild(li);
        });
        
        errorSummary.style.display = 'flex';
        
        // On mobile, just scroll to error summary without focusing (prevents keyboard jump)
        // On desktop, focus for better keyboard navigation
        setTimeout(() => {
          errorSummary.scrollIntoView({ behavior: 'smooth', block: 'center' });
          
          // Only focus on desktop - prevents keyboard jumping on mobile/tablet
          if (!isMobile) {
            errorSummary.focus();
            errorSummary.setAttribute('tabindex', '-1');
            
            // Focus first error field after a brief delay (desktop only)
            const firstErrorField = orderedFields[0];
            if (firstErrorField) {
              setTimeout(() => {
                const firstField = document.getElementById(firstErrorField);
                if (firstField) {
                  firstField.focus();
                }
              }, 300);
            }
          } else {
            // On mobile, just make it focusable for screen readers but don't auto-focus
            errorSummary.setAttribute('tabindex', '-1');
          }
        }, 100);
      } else {
        errorSummary.style.display = 'none';
        errorSummary.removeAttribute('tabindex');
      }
    }

    // Name error
    if (errors.name) {
      nameError.textContent = errors.name;
      nameError.style.display = 'block';
      nameInput.setAttribute('aria-invalid', 'true');
      nameInput.classList.add('contact-form-input-error');
      updateFieldValidation('name');
    } else {
      clearError('name');
      updateFieldValidation('name');
    }

    // Email error
    if (errors.email) {
      emailError.textContent = errors.email;
      emailError.style.display = 'block';
      emailInput.setAttribute('aria-invalid', 'true');
      emailInput.classList.add('contact-form-input-error');
      updateFieldValidation('email');
    } else {
      clearError('email');
      updateFieldValidation('email');
    }

    // Phone error
    if (errors.phone) {
      const phoneError = document.getElementById('phone-error');
      if (phoneError) {
        phoneError.textContent = errors.phone;
        phoneError.style.display = 'block';
      }
      if (phoneInput) {
        phoneInput.setAttribute('aria-invalid', 'true');
        phoneInput.classList.add('contact-form-input-error');
        updateFieldValidation('phone');
      }
    } else {
      clearError('phone');
      updateFieldValidation('phone');
    }

    // Message error
    if (errors.message) {
      messageError.textContent = errors.message;
      messageError.style.display = 'block';
      messageTextarea.setAttribute('aria-invalid', 'true');
      messageTextarea.classList.add('contact-form-input-error');
      updateFieldValidation('message');
    } else {
      clearError('message');
      updateFieldValidation('message');
    }

    // Enquiry type error
    if (errors.enquiryType) {
      if (enquiryTypeError) {
        enquiryTypeError.textContent = errors.enquiryType;
        enquiryTypeError.style.display = 'block';
      }
      if (enquiryTypeSelect) {
        enquiryTypeSelect.setAttribute('aria-invalid', 'true');
        enquiryTypeSelect.classList.add('contact-form-select-error');
        updateFieldValidation('enquiryType');
      }
    } else {
      clearError('enquiryType');
      updateFieldValidation('enquiryType');
    }

    // Course selection error
    if (errors.selectedCourses) {
      const courseSelectionError = document.getElementById('course-selection-error');
      if (courseSelectionError) {
        courseSelectionError.textContent = errors.selectedCourses;
        courseSelectionError.style.display = 'block';
      }
      if (courseSelectionField) {
        courseSelectionField.classList.add('contact-form-field-error');
      }
    } else {
      const courseSelectionError = document.getElementById('course-selection-error');
      if (courseSelectionError) {
        courseSelectionError.textContent = '';
        courseSelectionError.style.display = 'none';
      }
      if (courseSelectionField) {
        courseSelectionField.classList.remove('contact-form-field-error');
      }
    }

    // Consent error
    if (errors.consent) {
      const consentError = document.getElementById('consent-error');
      if (consentError) {
        consentError.textContent = errors.consent;
        consentError.style.display = 'block';
      }
      if (consentCheckbox) {
        consentCheckbox.setAttribute('aria-invalid', 'true');
        consentCheckbox.classList.add('contact-form-input-error');
      }
    } else {
      const consentError = document.getElementById('consent-error');
      if (consentError) {
        consentError.style.display = 'none';
        consentError.textContent = '';
      }
      if (consentCheckbox) {
        consentCheckbox.setAttribute('aria-invalid', 'false');
        consentCheckbox.classList.remove('contact-form-input-error');
      }
    }
  }

  // Validate individual field
  function validateField(fieldName, skipFocus = false) {
    const fieldErrors = {};
    
    if (fieldName === 'name') {
      const nameValue = nameInput.value.trim();
      if (!nameValue) {
        fieldErrors.name = ERROR_MESSAGES.name;
      }
    } else if (fieldName === 'email') {
      const emailValue = emailInput.value.trim();
      if (!emailValue) {
        fieldErrors.email = ERROR_MESSAGES.email;
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
        fieldErrors.email = ERROR_MESSAGES.emailInvalid;
      }
    } else if (fieldName === 'phone') {
      const phoneValue = phoneInput.value.trim();
      const phoneValidation = validatePhone(phoneValue);
      if (!phoneValidation.valid) {
        fieldErrors.phone = phoneValidation.error;
      }
    } else if (fieldName === 'message') {
      const messageValue = messageTextarea.value.trim();
      if (!messageValue) {
        fieldErrors.message = ERROR_MESSAGES.message;
      } else {
        const maxLength = parseInt(messageTextarea.getAttribute('maxlength')) || 1000;
        if (messageValue.length > maxLength) {
          fieldErrors.message = ERROR_MESSAGES.messageMaxLength;
        }
      }
    } else if (fieldName === 'enquiryType') {
      if (enquiryTypeSelect) {
        const enquiryTypeValue = enquiryTypeSelect.value;
        if (!enquiryTypeValue) {
          fieldErrors.enquiryType = ERROR_MESSAGES.enquiryType;
        }
      }
    }

    // Update errors object
    if (fieldErrors[fieldName]) {
      errors[fieldName] = fieldErrors[fieldName];
    } else {
      delete errors[fieldName];
    }

    // Display errors for this field (but don't auto-focus on mobile)
    // Pass skipFocus flag to prevent focus on blur validation
    if (skipFocus || window.innerWidth < 768) {
      // Just update the visual error state without focusing
      displayErrorsWithoutFocus();
    } else {
      displayErrors();
    }
  }

  // Display errors without auto-focusing (for blur validation on mobile)
  function displayErrorsWithoutFocus() {
    const errorSummary = document.getElementById('contact-form-error-summary');
    const errorList = document.getElementById('contact-form-error-list');
    const errorCount = Object.keys(errors).length;

    // Show/hide error summary (but don't focus it)
    if (errorSummary && errorList) {
      if (errorCount > 0) {
        errorList.innerHTML = '';
        const fieldOrder = ['name', 'email', 'phone', 'enquiryType', 'message', 'consent'];
        const orderedFields = fieldOrder.filter(field => errors[field]);
        
        orderedFields.forEach((field, index) => {
          const li = document.createElement('li');
          const link = document.createElement('a');
          link.href = `#${field}`;
          link.textContent = errors[field];
          link.className = 'contact-form-error-link';
          link.addEventListener('click', (e) => {
            e.preventDefault();
            const fieldElement = document.getElementById(field);
            if (fieldElement) {
              // Only focus on desktop
              if (window.innerWidth >= 768) {
                fieldElement.focus();
              }
              fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          });
          li.appendChild(link);
          errorList.appendChild(li);
        });
        
        errorSummary.style.display = 'flex';
        // Don't scroll or focus - just show the errors
      } else {
        errorSummary.style.display = 'none';
        errorSummary.removeAttribute('tabindex');
      }
    }

    // Update individual field error states (same as displayErrors)
    // Name error
    if (errors.name) {
      nameError.textContent = errors.name;
      nameError.style.display = 'block';
      nameInput.setAttribute('aria-invalid', 'true');
      nameInput.classList.add('contact-form-input-error');
      updateFieldValidation('name');
    } else {
      clearError('name');
      updateFieldValidation('name');
    }

    // Email error
    if (errors.email) {
      emailError.textContent = errors.email;
      emailError.style.display = 'block';
      emailInput.setAttribute('aria-invalid', 'true');
      emailInput.classList.add('contact-form-input-error');
      updateFieldValidation('email');
    } else {
      clearError('email');
      updateFieldValidation('email');
    }

    // Phone error
    if (errors.phone) {
      const phoneError = document.getElementById('phone-error');
      if (phoneError) {
        phoneError.textContent = errors.phone;
        phoneError.style.display = 'block';
      }
      if (phoneInput) {
        phoneInput.setAttribute('aria-invalid', 'true');
        phoneInput.classList.add('contact-form-input-error');
        updateFieldValidation('phone');
      }
    } else {
      clearError('phone');
      updateFieldValidation('phone');
    }

    // Discount code error (second location)
    const discountCodeInput2 = document.getElementById('discount-code');
    const discountCodeError2 = document.getElementById('discount-code-error');
    if (errors.discount_code) {
      if (discountCodeError2) {
        discountCodeError2.textContent = errors.discount_code;
        discountCodeError2.style.display = 'block';
      }
      if (discountCodeInput2) {
        discountCodeInput2.setAttribute('aria-invalid', 'true');
        discountCodeInput2.classList.add('contact-form-input-error');
      }
    } else {
      if (discountCodeError2) {
        discountCodeError2.textContent = '';
        discountCodeError2.style.display = 'none';
      }
      if (discountCodeInput2) {
        discountCodeInput2.setAttribute('aria-invalid', 'false');
        discountCodeInput2.classList.remove('contact-form-input-error');
      }
    }

    // Message error
    if (errors.message) {
      messageError.textContent = errors.message;
      messageError.style.display = 'block';
      messageTextarea.setAttribute('aria-invalid', 'true');
      messageTextarea.classList.add('contact-form-input-error');
      updateFieldValidation('message');
    } else {
      clearError('message');
      updateFieldValidation('message');
    }

    // Enquiry type error
    if (errors.enquiryType) {
      if (enquiryTypeError) {
        enquiryTypeError.textContent = errors.enquiryType;
        enquiryTypeError.style.display = 'block';
      }
      if (enquiryTypeSelect) {
        enquiryTypeSelect.setAttribute('aria-invalid', 'true');
        enquiryTypeSelect.classList.add('contact-form-select-error');
        updateFieldValidation('enquiryType');
      }
    } else {
      clearError('enquiryType');
      updateFieldValidation('enquiryType');
    }

    // Consent error
    if (errors.consent) {
      const consentError = document.getElementById('consent-error');
      if (consentError) {
        consentError.textContent = errors.consent;
        consentError.style.display = 'block';
      }
      if (consentCheckbox) {
        consentCheckbox.setAttribute('aria-invalid', 'true');
        consentCheckbox.classList.add('contact-form-input-error');
      }
    } else {
      const consentError = document.getElementById('consent-error');
      if (consentError) {
        consentError.style.display = 'none';
        consentError.textContent = '';
      }
      if (consentCheckbox) {
        consentCheckbox.setAttribute('aria-invalid', 'false');
        consentCheckbox.classList.remove('contact-form-input-error');
      }
    }
  }

  // Update field validation icons
  function updateFieldValidation(fieldName) {
    let input, errorElement;
    
    if (fieldName === 'name') {
      input = nameInput;
      errorElement = nameError;
    } else if (fieldName === 'email') {
      input = emailInput;
      errorElement = emailError;
    } else if (fieldName === 'phone') {
      input = phoneInput;
      errorElement = document.getElementById('phone-error');
    } else if (fieldName === 'message') {
      input = messageTextarea;
      errorElement = messageError;
    } else if (fieldName === 'enquiryType') {
      input = enquiryTypeSelect;
      errorElement = enquiryTypeError;
    } else {
      return;
    }

    if (!input) return;

    const wrapper = input.closest('.contact-form-input-wrapper') || 
                    input.closest('.contact-form-textarea-wrapper') ||
                    input.closest('.contact-form-input-wrapper-new') ||
                    input.closest('.contact-form-textarea-wrapper-new') ||
                    input.closest('.contact-form-select-wrapper');
    if (!wrapper) return;

    const successIcon = wrapper.querySelector('.contact-form-success-icon');
    const errorIcon = wrapper.querySelector('.contact-form-error-icon');

    // Check if field is valid
    let isValid = false;
    if (input.tagName === 'SELECT') {
      isValid = input.value !== '';
    } else {
      isValid = input.checkValidity() && input.value.trim() !== '';
    }
    
    const hasError = input.classList.contains('contact-form-input-error') || 
                     input.classList.contains('contact-form-select-error') ||
                     (errorElement && errorElement.style.display !== 'none' && errorElement.textContent);

    if (hasError) {
      if (successIcon) successIcon.style.display = 'none';
      if (errorIcon) errorIcon.style.display = 'block';
    } else if (isValid) {
      if (successIcon) successIcon.style.display = 'block';
      if (errorIcon) errorIcon.style.display = 'none';
    } else {
      if (successIcon) successIcon.style.display = 'none';
      if (errorIcon) errorIcon.style.display = 'none';
    }
  }

  // Clear error for specific field
  function clearError(fieldName) {
    const errorElement = document.getElementById(fieldName + '-error');
    const inputElement = document.getElementById(fieldName);
    
    if (errorElement) {
      errorElement.style.display = 'none';
      errorElement.textContent = '';
    }
    
    if (inputElement) {
      inputElement.setAttribute('aria-invalid', 'false');
      // Remove both input and select error classes
      inputElement.classList.remove('contact-form-input-error');
      inputElement.classList.remove('contact-form-select-error');
    }
  }

  // Clear all errors
  function clearAllErrors() {
    const errorSummary = document.getElementById('contact-form-error-summary');
    if (errorSummary) {
      errorSummary.style.display = 'none';
    }

    [nameError, emailError, messageError, enquiryTypeError].forEach(error => {
      if (error) {
        error.style.display = 'none';
        error.textContent = '';
      }
    });

    const phoneError = document.getElementById('phone-error');
    if (phoneError) {
      phoneError.style.display = 'none';
      phoneError.textContent = '';
    }

    [nameInput, emailInput, phoneInput, messageTextarea, enquiryTypeSelect].forEach(input => {
      if (input) {
        input.setAttribute('aria-invalid', 'false');
        input.classList.remove('contact-form-input-error');
        if (input.id) {
          updateFieldValidation(input.id);
        }
      }
    });
  }

  // Submit form via WordPress AJAX
  function submitForm() {
    if (!form || !submitButton || !nameInput || !emailInput || !phoneInput || !messageTextarea) {
      if (isDevelopment) console.error('Contact form elements not found');
      return;
    }
    
    isSubmitting = true;
    
    // Update button state with better visual feedback
    submitButton.disabled = true;
    submitButton.setAttribute('aria-busy', 'true');
    submitButton.classList.add('is-submitting');
    if (submitText) submitText.style.display = 'none';
    if (submitLoading) submitLoading.style.display = 'inline-flex';
    submitButton.setAttribute('aria-label', 'Sending message, please wait...');
    
    // Disable all form inputs during submission
    const formInputs = form.querySelectorAll('input, textarea, select, button');
    formInputs.forEach(input => {
      if (input !== submitButton && input !== clearButton) {
        input.disabled = true;
        input.setAttribute('aria-disabled', 'true');
      }
    });
    
    // Add visual feedback to form
    form.classList.add('is-submitting');

    // Hide success message if visible
    if (successMessage) successMessage.style.display = 'none';

    // Check if WordPress AJAX is available
    if (typeof ctaData === 'undefined' || !ctaData.ajaxUrl) {
      if (isDevelopment) console.error('WordPress AJAX not available');
      handleSubmitError('Configuration error. Please contact us directly.');
      return;
    }

    // Prepare form data for WordPress AJAX
    const formData = new FormData();
    formData.append('action', 'cta_contact_form');
    formData.append('nonce', ctaData.nonce);
    formData.append('name', nameInput.value.trim());
    formData.append('email', emailInput.value.trim());
    formData.append('phone', phoneInput.value.trim());
    formData.append('enquiryType', enquiryTypeSelect ? enquiryTypeSelect.value : 'general');
    formData.append('message', messageTextarea.value.trim());
    formData.append('consent', consentCheckbox && consentCheckbox.checked ? 'true' : 'false');
    
    // Marketing consent (optional, pre-checked)
    const marketingConsentCheckbox = document.getElementById('contact-marketing-consent');
    formData.append('marketingConsent', marketingConsentCheckbox && marketingConsentCheckbox.checked ? 'true' : 'false');
    
    formData.append('page_url', window.location.href);
    
    // Add selected courses if applicable
    if (courseSelect) {
      // Get selected courses from checkboxes
      if (courseSelectionList) {
        const selectedCheckboxes = courseSelectionList.querySelectorAll('input[type="checkbox"]:checked');
        selectedCheckboxes.forEach(checkbox => {
          if (checkbox.value) {
            formData.append('selectedCourses[]', checkbox.value);
        }
      });
      }
    }
    
    // Add discount code if provided
    const discountCodeInput = document.getElementById('discount-code');
    if (discountCodeInput && discountCodeInput.value.trim()) {
      formData.append('discount_code', discountCodeInput.value.toUpperCase().trim());
    }
    
    // Include honeypot fields
    const honeypotFields = ['website', 'contact-url', 'contact-homepage'];
    honeypotFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field) {
        formData.append(field.name || fieldId, field.value);
      }
    });
    
    // Add submission timestamp for anti-bot protection
    const submissionTime = Date.now() / 1000; // Unix timestamp in seconds
    formData.append('submission_time', submissionTime.toString());
    
    // Ensure form load time is included
    const formLoadTimeInput = document.getElementById('contact-form-load-time');
    if (formLoadTimeInput && formLoadTimeInput.value) {
      formData.append('form_load_time', formLoadTimeInput.value);
    }
    
    // Get reCAPTCHA v3 token before submission
    const recaptchaResponseInput = document.getElementById('contact-recaptcha-response');
    const submitForm = function() {
      // Ensure reCAPTCHA response is included
      if (recaptchaResponseInput && recaptchaResponseInput.value) {
        formData.append('g-recaptcha-response', recaptchaResponseInput.value);
      }

      // Send AJAX request
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
      isSubmitting = false;
      
      // Re-enable all form inputs
      const formInputs = form.querySelectorAll('input, textarea, select, button');
      formInputs.forEach(input => {
        input.disabled = false;
        input.removeAttribute('aria-disabled');
      });
      
      // Remove visual feedback from form
      form.classList.remove('is-submitting');
      
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.setAttribute('aria-busy', 'false');
        submitButton.classList.remove('is-submitting');
      }
      submitButton.removeAttribute('aria-label');
      if (submitText) submitText.style.display = 'inline';
      if (submitLoading) submitLoading.style.display = 'none';

      if (data.success) {
        // Track form submission performance
        if (typeof window.trackFormSubmission === 'function') {
          window.trackFormSubmission();
        }

        // Generate custom thank you message based on enquiry type
        const enquiryType = enquiryTypeSelect?.value || '';
        let thankYouMessage = data.data?.message || "Thank you! Your enquiry has been passed on to a member of our team.";
        let nextStepsMessage = "";
        
        // Map enquiry types to contact methods
        const enquiryTypeMap = {
          'schedule-call': { method: 'phone' },
          'group-training': { method: 'email' },
          'book-course': { method: 'email' },
          'cqc-training': { method: 'email' },
          'support': { method: 'email' },
          'general': { method: 'email' }
        };
        
        if (enquiryType && enquiryTypeMap[enquiryType]) {
          const { method } = enquiryTypeMap[enquiryType];
          if (method === 'phone') {
            nextStepsMessage = "We'll call you back soon.";
          } else {
            nextStepsMessage = "We'll review your enquiry and get back to you via email.";
          }
        } else {
          nextStepsMessage = "We'll review your enquiry and get back to you.";
        }

        // Clear saved form data on successful submission
        try {
          localStorage.removeItem('cta_contact_form_draft');
        } catch (e) {
          // localStorage might be disabled - silently fail
        }
        
        // Reset form first (before showing modal)
        resetForm();
        
        // Reset consent checkbox
        const consentCheckbox = document.getElementById('contact-consent');
        if (consentCheckbox) {
          consentCheckbox.checked = false;
        }

        // Show thank you popup with custom message (only once)
        // The modal function itself now has a guard, but we also ensure we only call it once
        if (typeof window.showThankYouModal === 'function') {
          // Small delay to ensure form reset completes
          setTimeout(() => {
            window.showThankYouModal(thankYouMessage, {
              nextSteps: nextStepsMessage
            });
          }, 100);
        }

        // Focus success message for screen readers
        if (successMessage) {
          successMessage.setAttribute('tabindex', '-1');
          setTimeout(() => {
            successMessage.focus();
          }, 100);
        }
      } else {
        // Handle validation errors from server
        if (data.data?.errors) {
          errors = data.data.errors;
          displayErrors();
        } else {
          handleSubmitError(data.data?.message || 'Unable to send message. Please try again.');
        }
      }
    })
    .catch(error => {
      if (isDevelopment) console.error('Form submission error:', error);
      handleSubmitError('Unable to send message. Please try again or contact us directly.');
    });
    };

    // Get reCAPTCHA v3 token before submitting
    if (recaptchaResponseInput && typeof ctaGetRecaptchaToken === 'function') {
      ctaGetRecaptchaToken('contact', 'submit', function(token) {
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
  }

  // Handle submission errors
  function handleSubmitError(message) {
    isSubmitting = false;
    
    // Re-enable all form inputs
    const formInputs = form.querySelectorAll('input, textarea, select, button');
    formInputs.forEach(input => {
      input.disabled = false;
      input.removeAttribute('aria-disabled');
    });
    
    // Remove visual feedback from form
    form.classList.remove('is-submitting');
    
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.setAttribute('aria-busy', 'false');
      submitButton.classList.remove('is-submitting');
      submitButton.removeAttribute('aria-label');
    }
    if (submitText) submitText.style.display = 'inline';
    if (submitLoading) submitLoading.style.display = 'none';
    
    // Show error message to user
    const errorSummary = document.getElementById('contact-form-error-summary');
    const errorList = document.getElementById('contact-form-error-list');
    if (errorSummary && errorList) {
      errorList.innerHTML = '<li>' + message + '</li>';
      errorSummary.style.display = 'flex';
      errorSummary.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  // Save form data to localStorage
  function saveFormToLocalStorage() {
    if (!form) return;
    
    try {
      const formData = {
        name: nameInput?.value || '',
        email: emailInput?.value || '',
        phone: phoneInput?.value || '',
        enquiryType: enquiryTypeSelect?.value || '',
        message: messageTextarea?.value || '',
        consent: consentCheckbox?.checked || false,
        timestamp: Date.now()
      };
      
      localStorage.setItem('cta_contact_form_draft', JSON.stringify(formData));
    } catch (e) {
      // localStorage might be disabled or full - silently fail
      if (isDevelopment) console.warn('Could not save form to localStorage:', e);
    }
  }
  
  // Restore form data from localStorage
  function restoreFormFromLocalStorage() {
    if (!form) return;
    
    try {
      const saved = localStorage.getItem('cta_contact_form_draft');
      if (!saved) return;
      
      const formData = JSON.parse(saved);
      
      // Only restore if data is less than 24 hours old
      const age = Date.now() - (formData.timestamp || 0);
      const maxAge = 24 * 60 * 60 * 1000; // 24 hours
      
      if (age > maxAge) {
        localStorage.removeItem('cta_contact_form_draft');
        return;
      }
      
      // Check if form already has content (don't overwrite user input)
      const hasContent = nameInput?.value.trim() || 
                        emailInput?.value.trim() || 
                        phoneInput?.value.trim() || 
                        messageTextarea?.value.trim();
      
      if (hasContent) {
        // Ask user if they want to restore
        const shouldRestore = window.confirm('We found a saved draft of your form. Would you like to restore it?');
        if (!shouldRestore) {
          localStorage.removeItem('cta_contact_form_draft');
          return;
        }
      }
      
      // Restore form fields
      if (formData.name && nameInput) nameInput.value = formData.name;
      if (formData.email && emailInput) emailInput.value = formData.email;
      if (formData.phone && phoneInput) phoneInput.value = formData.phone;
      if (formData.message && messageTextarea) messageTextarea.value = formData.message;
      if (formData.enquiryType && enquiryTypeSelect) {
        enquiryTypeSelect.value = formData.enquiryType;
        handleEnquiryTypeChange();
      }
      if (formData.consent && consentCheckbox) consentCheckbox.checked = formData.consent;
      
      // Update UI
      updateCharacterCounter();
      updateProgress();
      
      // Show a subtle notification
      const notification = document.createElement('div');
      notification.className = 'contact-form-restored-notification';
      notification.setAttribute('role', 'status');
      notification.setAttribute('aria-live', 'polite');
      notification.textContent = 'Form restored from saved draft';
      form.insertBefore(notification, form.firstChild);
      
      setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
      }, 3000);
      
    } catch (e) {
      // Invalid data or localStorage disabled - silently fail
      if (isDevelopment) console.warn('Could not restore form from localStorage:', e);
      localStorage.removeItem('cta_contact_form_draft');
    }
  }

  // Reset form
  function resetForm() {
    // Preserve enquiry type if it was set via URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const typeParam = urlParams.get('type');
    const preservedEnquiryType = enquiryTypeSelect ? enquiryTypeSelect.value : null;
    
    form.reset();
    clearAllErrors();
    errors = {};
    
    // Restore enquiry type if it was set via URL or preserve current if valid
    if (enquiryTypeSelect) {
      if (typeParam) {
        const typeMapping = {
          'schedule-call': 'schedule-call',
          'group-training': 'group-training',
          'book-course': 'book-course',
          'support': 'support',
          'general': 'general'
        };
        const mappedValue = typeMapping[typeParam] || typeParam;
        if (enquiryTypeSelect.querySelector(`option[value="${mappedValue}"]`)) {
          enquiryTypeSelect.value = mappedValue;
        }
      } else if (preservedEnquiryType && preservedEnquiryType !== '') {
        enquiryTypeSelect.value = preservedEnquiryType;
      }
    }
    
    // Reset character counter
    updateCharacterCounter();
    
    // Reset progress
    updateProgress();
    
    // Clear saved form data
    try {
      localStorage.removeItem('cta_contact_form_draft');
    } catch (e) {
      // localStorage might be disabled - silently fail
    }
  }

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

// FAQ Accordion Handler
(function() {
  'use strict';

  function initFAQ() {
    const faqQuestions = document.querySelectorAll('.contact-faq-question');
    
    if (faqQuestions.length === 0) {
      return;
    }
    
    faqQuestions.forEach(question => {
      question.addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        const answerId = this.getAttribute('aria-controls');
        const answer = document.getElementById(answerId);
        
        if (!answer) {
          return;
        }
        
        // Close all other accordions
        faqQuestions.forEach(q => {
          if (q !== question) {
            q.setAttribute('aria-expanded', 'false');
            const otherAnswerId = q.getAttribute('aria-controls');
            const otherAnswer = document.getElementById(otherAnswerId);
            if (otherAnswer) {
              otherAnswer.setAttribute('aria-hidden', 'true');
              otherAnswer.style.maxHeight = '0';
            }
          }
        });
        
        // Toggle current accordion
        if (isExpanded) {
          this.setAttribute('aria-expanded', 'false');
          if (answer) {
            answer.setAttribute('aria-hidden', 'true');
            answer.style.maxHeight = '0';
          }
        } else {
          this.setAttribute('aria-expanded', 'true');
          if (answer) {
            answer.setAttribute('aria-hidden', 'false');
            answer.style.maxHeight = answer.scrollHeight + 'px';
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
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFAQ);
  } else {
    initFAQ();
  }
})();

