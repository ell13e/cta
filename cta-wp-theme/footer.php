<?php
/**
 * Footer Template
 *
 * @package CTA_Theme
 */

$contact = cta_get_contact_info();
?>

<!-- Clean Modern Footer -->
<footer class="site-footer-modern">
  <div class="footer-modern-container">
    <!-- Top Section: Logo and Description -->
    <div class="footer-modern-top">
      <div class="footer-modern-brand">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="footer-modern-logo-link">
          <img srcset="<?php echo esc_url(cta_image('logo/long_logo-400w.webp')); ?> 400w,
                       <?php echo esc_url(cta_image('logo/long_logo-800w.webp')); ?> 800w,
                       <?php echo esc_url(cta_image('logo/long_logo-1200w.webp')); ?> 1200w,
                       <?php echo esc_url(cta_image('logo/long_logo-1600w.webp')); ?> 1600w"
               src="<?php echo esc_url(cta_image('logo/long_logo-400w.webp')); ?>"
               alt="<?php bloginfo('name'); ?>"
               class="footer-modern-logo"
               width="200"
               height="50"
               sizes="(max-width: 640px) 180px, 200px">
        </a>
        <p class="footer-modern-description">
          Professional care sector training in Kent. CQC-compliant, CPD-accredited courses since 2020.
        </p>
      </div>
    </div>

    <!-- Main Footer Grid -->
    <div class="footer-modern-grid">
      <!-- Company Column -->
      <nav class="footer-modern-col" aria-label="Company navigation">
        <h3 class="footer-modern-heading">Company</h3>
        <ul class="footer-modern-links">
          <?php 
          $about_page = get_page_by_path('about');
          if ($about_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($about_page)); ?>" class="footer-modern-link">About</a></li>
          <?php endif; ?>
          <li><a href="<?php echo esc_url(get_post_type_archive_link('course') ?: home_url('/courses/')); ?>" class="footer-modern-link">Courses</a></li>
          <li><a href="<?php echo esc_url(get_post_type_archive_link('course_event') ?: home_url('/upcoming-courses/')); ?>" class="footer-modern-link">Upcoming Courses</a></li>
          <?php 
          $group_training_page = get_page_by_path('group-training');
          if ($group_training_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($group_training_page)); ?>" class="footer-modern-link">Group Training</a></li>
          <?php endif; ?>
          <?php 
          $locations_page = get_page_by_path('locations');
          if ($locations_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($locations_page)); ?>" class="footer-modern-link">Training Locations</a></li>
          <?php endif; ?>
          <?php 
          $cqc_hub_page = get_page_by_path('cqc-compliance-hub');
          if ($cqc_hub_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($cqc_hub_page)); ?>" class="footer-modern-link">CQC Compliance Hub</a></li>
          <?php endif; ?>
          <?php 
          $training_guides_page = get_page_by_path('training-guides');
          if ($training_guides_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($training_guides_page)); ?>" class="footer-modern-link">Training Guides</a></li>
          <?php endif; ?>
          <?php 
          $resources_page = get_page_by_path('downloadable-resources');
          if ($resources_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($resources_page)); ?>" class="footer-modern-link">Downloadable Resources</a></li>
          <?php endif; ?>
          <?php 
          $news_page = get_option('page_for_posts');
          if ($news_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($news_page)); ?>" class="footer-modern-link">News</a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <!-- Help Column -->
      <nav class="footer-modern-col" aria-label="Help and support navigation">
        <h3 class="footer-modern-heading">Help</h3>
        <ul class="footer-modern-links">
          <?php 
          $contact_page = get_page_by_path('contact');
          if ($contact_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($contact_page)); ?>" class="footer-modern-link">Customer Support</a></li>
          <?php endif; ?>
          <?php 
          $faqs_page = get_page_by_path('faqs');
          if ($faqs_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($faqs_page)); ?>" class="footer-modern-link">FAQs</a></li>
          <?php endif; ?>
          <?php 
          $terms_page = get_page_by_path('terms-conditions');
          if ($terms_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($terms_page)); ?>" class="footer-modern-link">Terms & Conditions</a></li>
          <?php endif; ?>
          <?php 
          $privacy_page = get_page_by_path('privacy-policy');
          if ($privacy_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($privacy_page)); ?>" class="footer-modern-link">Privacy Policy</a></li>
          <?php endif; ?>
          <?php 
          $cookie_page = get_page_by_path('cookie-policy');
          if ($cookie_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($cookie_page)); ?>" class="footer-modern-link">Cookie Policy</a></li>
          <?php endif; ?>
          <?php 
          // Check for accessibility page - prioritize accessibility-statement slug
          $accessibility_page = get_page_by_path('accessibility-statement') ?: get_page_by_path('accessibility') ?: get_page_by_path('accessibility-policy');
          if ($accessibility_page) : ?>
            <li><a href="<?php echo esc_url(get_permalink($accessibility_page)); ?>" class="footer-modern-link">Accessibility</a></li>
          <?php else : ?>
            <li><a href="<?php echo esc_url(home_url('/accessibility-statement/')); ?>" class="footer-modern-link">Accessibility</a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <!-- Newsletter Column -->
      <div class="footer-modern-col footer-modern-newsletter">
        <h3 class="footer-modern-heading">Newsletter</h3>
        <p class="footer-modern-newsletter-description">Stay updated with training insights and CQC updates.</p>
        <button type="button" class="footer-modern-newsletter-btn" onclick="openNewsletterModal()" aria-label="Open newsletter signup form">
          Subscribe to Newsletter
        </button>
      </div>
    </div>

    <!-- Bottom Section: Copyright -->
    <div class="footer-modern-bottom">
      <p class="footer-modern-copyright">
        © Copyright <?php echo date('Y'); ?>. All Rights Reserved by Continuity Training Academy
      </p>
    </div>
  </div>
</footer>

<!-- Back to Top Button -->
<button
  id="back-to-top"
  class="back-to-top"
  aria-label="Back to top"
  aria-hidden="true"
  title="Back to top"
>
  <i class="fas fa-arrow-up" aria-hidden="true"></i>
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const backToTop = document.getElementById('back-to-top');
  if (backToTop) {
    window.addEventListener('scroll', function() {
      if (window.scrollY > 300) {
        backToTop.setAttribute('aria-hidden', 'false');
        backToTop.style.opacity = '1';
        backToTop.style.pointerEvents = 'auto';
      } else {
        backToTop.setAttribute('aria-hidden', 'true');
        backToTop.style.opacity = '0';
        backToTop.style.pointerEvents = 'none';
      }
    });
    
    backToTop.addEventListener('click', function() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
});
</script>

<!-- Newsletter Modal -->
<div id="newsletter-modal" class="newsletter-modal" role="dialog" aria-modal="true" aria-labelledby="newsletter-title" aria-hidden="true">
  <div class="newsletter-modal-backdrop" onclick="closeNewsletterModal()" aria-hidden="true"></div>
  <div class="newsletter-signup-card">
    <button class="newsletter-signup-close" onclick="closeNewsletterModal()" aria-label="Close newsletter signup">
      <i class="fas fa-times" aria-hidden="true"></i>
    </button>
    <div class="newsletter-signup-header">
      <div class="newsletter-signup-icon">
        <i class="fas fa-envelope" aria-hidden="true"></i>
      </div>
      <h2 id="newsletter-title" class="newsletter-signup-title">Stay Updated</h2>
      <p class="newsletter-signup-description">Get the latest training insights, CQC updates, and exclusive offers delivered to your inbox.</p>
    </div>
    <form id="newsletter-signup-form" class="newsletter-signup-form">
      <!-- Error Summary -->
      <div id="newsletter-error-summary" class="newsletter-signup-error-summary" role="alert" aria-live="assertive" style="display: none">
        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
        <div>
          <strong>Please correct the following errors:</strong>
          <ul id="newsletter-error-list"></ul>
        </div>
      </div>

      <div class="newsletter-signup-input-wrapper">
        <label for="newsletter-signup-first-name" class="newsletter-signup-label">
          First name <span class="required-indicator" aria-label="required">*</span>
        </label>
        <input
          type="text"
          id="newsletter-signup-first-name"
          name="first_name"
          placeholder="First name"
          class="newsletter-signup-input"
          required
          aria-label="First name"
          autocomplete="given-name"
          aria-describedby="newsletter-first-name-error"
          aria-invalid="false"
        />
        <p id="newsletter-first-name-error" class="newsletter-signup-error" role="alert" aria-live="polite" style="display: none"></p>
      </div>
      <div class="newsletter-signup-input-wrapper">
        <label for="newsletter-signup-last-name" class="newsletter-signup-label">
          Last name <span class="required-indicator" aria-label="required">*</span>
        </label>
        <input
          type="text"
          id="newsletter-signup-last-name"
          name="last_name"
          placeholder="Last name"
          class="newsletter-signup-input"
          required
          aria-label="Last name"
          autocomplete="family-name"
          aria-describedby="newsletter-last-name-error"
          aria-invalid="false"
        />
        <p id="newsletter-last-name-error" class="newsletter-signup-error" role="alert" aria-live="polite" style="display: none"></p>
      </div>
      <div class="newsletter-signup-input-wrapper">
        <label for="newsletter-signup-email" class="newsletter-signup-label">
          Email address <span class="required-indicator" aria-label="required">*</span>
        </label>
        <input
          type="email"
          id="newsletter-signup-email"
          name="email"
          placeholder="Enter your email address"
          class="newsletter-signup-input"
          required
          aria-label="Email address"
          autocomplete="email"
          aria-describedby="newsletter-email-error"
          aria-invalid="false"
        />
        <p id="newsletter-email-error" class="newsletter-signup-error" role="alert" aria-live="polite" style="display: none"></p>
      </div>

      <div class="newsletter-signup-input-wrapper">
        <label for="newsletter-signup-phone" class="newsletter-signup-label">
          Phone number <span class="optional-indicator">(optional)</span>
        </label>
        <input
          type="tel"
          id="newsletter-signup-phone"
          name="phone"
          placeholder="e.g., 07123 456789"
          class="newsletter-signup-input"
          aria-label="Phone number (optional)"
          autocomplete="tel"
          aria-describedby="newsletter-phone-error"
          aria-invalid="false"
        />
        <p id="newsletter-phone-error" class="newsletter-signup-error" role="alert" aria-live="polite" style="display: none"></p>
      </div>
      <div class="newsletter-signup-input-wrapper">
        <label for="newsletter-signup-dob" class="newsletter-signup-label">
          Date of birth <span class="optional-indicator">(optional)</span>
        </label>
        <input
          type="date"
          id="newsletter-signup-dob"
          name="date_of_birth"
          placeholder="Date of birth"
          class="newsletter-signup-input"
          aria-label="Date of birth (optional)"
          max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>"
          aria-describedby="newsletter-dob-error"
          aria-invalid="false"
        />
        <small style="display: block; margin-top: 4px; color: #646970; font-size: 12px;">We use this to send you personalised birthday offers</small>
        <p id="newsletter-dob-error" class="newsletter-signup-error" role="alert" aria-live="polite" style="display: none"></p>
      </div>
      <div class="newsletter-signup-consent">
        <input
          type="checkbox"
          id="newsletter-signup-consent"
          name="consent"
          class="newsletter-signup-consent-checkbox"
          required
          aria-required="true"
          aria-describedby="newsletter-consent-error"
          aria-invalid="false"
        />
        <label for="newsletter-signup-consent" class="newsletter-signup-consent-label">
          I consent to receiving updates from <?php bloginfo('name'); ?>. <span class="required-indicator" aria-label="required">*</span> 
          <?php 
          $privacy_page_modal = get_page_by_path('privacy-policy');
          if ($privacy_page_modal) : ?>
            <a href="<?php echo esc_url(get_permalink($privacy_page_modal)); ?>" class="newsletter-signup-privacy-link">Privacy Policy</a>
          <?php endif; ?>
        </label>
        <p id="newsletter-consent-error" class="newsletter-signup-error" role="alert" aria-live="polite" style="display: none"></p>
      </div>
      <button type="submit" class="newsletter-signup-btn">
        Subscribe Now
        <i class="fas fa-arrow-right" aria-hidden="true"></i>
      </button>
    </form>
    <button class="newsletter-signup-dismiss" onclick="closeNewsletterModal()" aria-label="Close newsletter signup">
      No thanks, I'll pass
    </button>
  </div>
</div>

<script>
// Store original scroll position for restoration
let originalScrollPosition = 0;
let originalBodyStyle = '';

function openNewsletterModal() {
  const modal = document.getElementById('newsletter-modal');
  if (!modal) return;
  
  // Save current scroll position
  originalScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
  
  // Save original body styles
  originalBodyStyle = document.body.style.cssText;
  
  // Lock body scroll - works on desktop and mobile
  document.body.style.position = 'fixed';
  document.body.style.top = `-${originalScrollPosition}px`;
  document.body.style.left = '0';
  document.body.style.right = '0';
  document.body.style.overflow = 'hidden';
  document.body.style.width = '100%';
  
  // Also lock html scroll (some browsers need this)
  document.documentElement.style.overflow = 'hidden';
  
  modal.setAttribute('aria-hidden', 'false');
  
  setTimeout(() => {
    const emailInput = document.getElementById('newsletter-signup-email');
    if (emailInput) emailInput.focus();
  }, 100);
  
  const handleEscape = function(e) {
    if (e.key === 'Escape') {
      closeNewsletterModal();
      document.removeEventListener('keydown', handleEscape);
    }
  };
  document.addEventListener('keydown', handleEscape);
}

function closeNewsletterModal() {
  const modal = document.getElementById('newsletter-modal');
  if (!modal) return;
  
  // Remove focus from any focused element inside the modal before hiding
  const focusedElement = modal.querySelector(':focus');
  if (focusedElement) {
    focusedElement.blur();
  }
  
  modal.setAttribute('aria-hidden', 'true');
  
  // Restore body styles
  document.body.style.cssText = originalBodyStyle;
  document.documentElement.style.overflow = '';
  
  // Restore scroll position
  window.scrollTo(0, originalScrollPosition);
}

document.addEventListener('DOMContentLoaded', function() {
  const newsletterForm = document.getElementById('newsletter-signup-form');
  if (newsletterForm) {
    if (newsletterForm.dataset?.ctaBound === 'true') return;
    newsletterForm.dataset.ctaBound = 'true';
    
    newsletterForm.addEventListener('submit', function(e) {
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
        clearError('newsletter-signup-first-name', 'newsletter-first-name-error');
        clearError('newsletter-signup-last-name', 'newsletter-last-name-error');
        clearError('newsletter-signup-email', 'newsletter-email-error');
        clearError('newsletter-signup-phone', 'newsletter-phone-error');
        clearError('newsletter-signup-dob', 'newsletter-dob-error');
        clearError('newsletter-signup-consent', 'newsletter-consent-error');
        const errorSummary = document.getElementById('newsletter-error-summary');
        if (errorSummary) {
          errorSummary.style.display = 'none';
        }
      }
      
      // Clear previous errors
      clearAllErrors();
      
      const firstNameInput = this.querySelector('#newsletter-signup-first-name');
      const lastNameInput = this.querySelector('#newsletter-signup-last-name');
      const emailInput = this.querySelector('input[type="email"]');
      const phoneInput = this.querySelector('#newsletter-signup-phone');
      const dobInput = this.querySelector('#newsletter-signup-dob');
      const consentInput = this.querySelector('input[name="consent"]');
      const submitBtn = this.querySelector('button[type="submit"]');
      const errorSummary = document.getElementById('newsletter-error-summary');
      const errorList = document.getElementById('newsletter-error-list');
      const errors = [];
      
      const firstName = firstNameInput ? firstNameInput.value.trim() : '';
      const lastName = lastNameInput ? lastNameInput.value.trim() : '';
      const email = emailInput ? emailInput.value.trim() : '';
      const phone = phoneInput ? phoneInput.value.trim() : '';
      const dateOfBirth = dobInput ? dobInput.value : '';
      const consent = consentInput ? consentInput.checked : false;
      
      // Validate fields
      if (!firstName) {
        showError('newsletter-signup-first-name', 'newsletter-first-name-error', 'First name is required');
        errors.push('First name is required');
      }
      
      if (!lastName) {
        showError('newsletter-signup-last-name', 'newsletter-last-name-error', 'Last name is required');
        errors.push('Last name is required');
      }
      
      if (!email) {
        showError('newsletter-signup-email', 'newsletter-email-error', 'Email address is required');
        errors.push('Email address is required');
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('newsletter-signup-email', 'newsletter-email-error', 'Please enter a valid email address');
        errors.push('Please enter a valid email address');
      }
      
      // Optional phone validation (only validate if provided)
      if (phone) {
        // Very light UK-format check (server-side does the authoritative validation)
        const phoneOk = /^\\+?\\d[\\d\\s()\\-]{7,}$/.test(phone);
        if (!phoneOk) {
          showError('newsletter-signup-phone', 'newsletter-phone-error', 'Please enter a valid phone number');
          errors.push('Please enter a valid phone number');
        }
      }

      // Optional DOB validation (only validate if provided)
      if (dateOfBirth) {
        const dobTime = Date.parse(dateOfBirth);
        if (Number.isNaN(dobTime) || dobTime > Date.now()) {
          showError('newsletter-signup-dob', 'newsletter-dob-error', 'Please enter a valid date of birth');
          errors.push('Please enter a valid date of birth');
        }
      }
      
      if (!consent) {
        showError('newsletter-signup-consent', 'newsletter-consent-error', 'Please confirm your consent to receiving updates');
        errors.push('Please confirm your consent');
      }
      
      // Show error summary if there are errors
      if (errors.length > 0) {
        if (errorSummary && errorList) {
          errorList.innerHTML = errors.map(err => '<li>' + err + '</li>').join('');
          errorSummary.style.display = 'flex';
          errorSummary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        return;
      }
      
      if (typeof ctaData === 'undefined' || !ctaData.ajaxUrl) {
        showError('newsletter-signup-first-name', 'newsletter-first-name-error', 'Unable to process subscription. Please try again later.');
        return;
      }
      
      const originalText = submitBtn ? submitBtn.innerHTML : '';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="contact-form-spinner" aria-hidden="true"></span> Subscribing...';
      }
      
      const formData = new FormData();
      formData.append('action', 'cta_newsletter_signup');
      formData.append('nonce', ctaData.nonce);
      formData.append('first_name', firstName);
      formData.append('last_name', lastName);
      formData.append('email', email);
      formData.append('phone', phone);
      formData.append('date_of_birth', dateOfBirth);
      formData.append('consent', consent ? 'true' : 'false');
      
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
          submitBtn.innerHTML = originalText;
        }
        
        if (data.success) {
          clearAllErrors();
          
          const status = data.data?.status;
          const message = data.data?.message || 'Thank you for subscribing!';
          
          // Close newsletter modal first
          closeNewsletterModal();
          
          // Small delay to ensure modal closes before showing thank you
          setTimeout(() => {
            // If already subscribed, show thank you modal without next steps
            if (status === 'exists') {
              if (window.showThankYouModal) {
                window.showThankYouModal(message, { hideNextSteps: true });
              } else {
                alert(message);
              }
            } else if (window.showThankYouModal) {
              // For new subscriptions or reactivations, show the full modal
              window.showThankYouModal(
                message,
                {
                  nextSteps: 'Check your email for a confirmation message. We\'ll send you updates about new courses, CQC changes, and training opportunities.'
                }
              );
            } else {
              // Fallback if modal function not available
              alert(message + '\n\nCheck your email for a confirmation message. We\'ll send you updates about new courses, CQC changes, and training opportunities.');
            }
          }, 300);
          
          newsletterForm.reset();
        } else {
          // Show server-side validation errors inline
          if (data.data && data.data.errors) {
            const errors = data.data.errors;
            if (errors.first_name) showError('newsletter-signup-first-name', 'newsletter-first-name-error', errors.first_name);
            if (errors.last_name) showError('newsletter-signup-last-name', 'newsletter-last-name-error', errors.last_name);
            if (errors.email) showError('newsletter-signup-email', 'newsletter-email-error', errors.email);
            if (errors.phone) showError('newsletter-signup-phone', 'newsletter-phone-error', errors.phone);
            if (errors.date_of_birth) showError('newsletter-signup-dob', 'newsletter-dob-error', errors.date_of_birth);
            if (errors.consent) showError('newsletter-signup-consent', 'newsletter-consent-error', errors.consent);
          } else {
            showError('newsletter-signup-first-name', 'newsletter-first-name-error', data.data?.message || 'Unable to process subscription. Please try again.');
          }
        }
      })
      .catch(error => {
        console.error('Newsletter signup error:', error);
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
        showError('newsletter-signup-first-name', 'newsletter-first-name-error', 'Unable to process subscription. Please try again.');
      });
    });
  }
});

window.openNewsletterModal = openNewsletterModal;
window.closeNewsletterModal = closeNewsletterModal;
</script>

<!-- Thank You Popup Modal -->
<div id="thank-you-modal" class="thank-you-modal" role="dialog" aria-labelledby="thank-you-title" aria-hidden="true">
  <div class="thank-you-modal-overlay"></div>
  <div class="thank-you-modal-content">
    <button class="thank-you-modal-close" aria-label="Close thank you message">
      <i class="fas fa-times" aria-hidden="true"></i>
    </button>
    <div class="thank-you-modal-icon">
      <i class="fas fa-check-circle" aria-hidden="true"></i>
    </div>
    <h2 id="thank-you-title" class="thank-you-modal-title">Thank You!</h2>
    <p class="thank-you-modal-message" id="thank-you-message">Thank you for subscribing!</p>
    
    <div class="thank-you-modal-next-steps">
      <h3 class="thank-you-modal-next-steps-title">What happens next?</h3>
      <p class="thank-you-modal-next-steps-text" id="thank-you-next-steps-text">
        Check your email for a confirmation message. We'll send you updates about new courses, CQC changes, and training opportunities.
      </p>
      <div class="thank-you-modal-actions">
        <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-secondary">Browse Our Courses</a>
      </div>
    </div>
  </div>
</div>

<?php 
// Include booking modal on pages that need it
if (is_singular('course') || is_singular('course_event') || is_post_type_archive('course') || is_post_type_archive('course_event') || is_front_page()) {
  get_template_part('template-parts/booking-modal');
}

// Resource download lead-capture modal (load on any page that has resource download buttons)
// Buttons exist on: Downloadable Resources, CQC Hub, and Training Guides pages
if (is_page_template('page-templates/page-downloadable-resources.php') ||
    is_page_template('page-templates/page-cqc-hub.php') ||
    is_page_template('page-templates/page-training-guides.php')) {
    get_template_part('template-parts/resource-download-modal');
}
?>

<?php 
// Add reCAPTCHA callback functions if reCAPTCHA is enabled
$recaptcha_site_key = function_exists('cta_get_recaptcha_site_key') ? cta_get_recaptcha_site_key() : get_theme_mod('cta_recaptcha_site_key', '');
if (!empty($recaptcha_site_key)) :
?>
<script>
// reCAPTCHA v3 implementation (invisible, runs in background)
var ctaRecaptchaSiteKey = '<?php echo esc_js($recaptcha_site_key); ?>';

// Execute reCAPTCHA v3 and get token
function ctaExecuteRecaptcha(action, callback) {
  // Early return if no site key
  if (!ctaRecaptchaSiteKey) {
    if (callback) callback(null);
    return;
  }
  
  // Check if reCAPTCHA is loaded
  if (typeof grecaptcha === 'undefined') {
    // Try to wait for it to load
    if (typeof window.grecaptcha !== 'undefined') {
      // Use window.grecaptcha if available
      grecaptcha = window.grecaptcha;
    } else {
      // Not loaded yet, callback with null
      if (callback) callback(null);
      return;
    }
  }
  
  // Check if execute function exists
  if (typeof grecaptcha.execute !== 'function') {
    // Wait for reCAPTCHA to be ready
    if (typeof grecaptcha.ready === 'function') {
      grecaptcha.ready(function() {
        if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.execute === 'function') {
          try {
  grecaptcha.execute(ctaRecaptchaSiteKey, {action: action || 'submit'})
    .then(function(token) {
      if (callback) callback(token);
    })
    .catch(function(error) {
      // Handle specific reCAPTCHA errors
      var errorMsg = error && error.message ? error.message : String(error);
      if (errorMsg.indexOf('Invalid site key') !== -1 || errorMsg.indexOf('localhost') !== -1) {
        console.warn('reCAPTCHA: Domain not in allowed list. Add "' + window.location.hostname + '" to your reCAPTCHA site key domains in Google Cloud Console: https://console.cloud.google.com/security/recaptcha');
      } else if (errorMsg.indexOf('BROWSER_ERROR') !== -1) {
        console.warn('reCAPTCHA: Network error. Form will still work without reCAPTCHA protection.');
      } else {
        console.warn('reCAPTCHA v3 error:', error);
      }
      if (callback) callback(null);
    });
          } catch (error) {
            console.warn('reCAPTCHA v3 execution error:', error);
            if (callback) callback(null);
          }
        } else {
          if (callback) callback(null);
        }
      });
    } else {
      // No ready function, callback with null
      if (callback) callback(null);
    }
    return;
  }
  
  // Execute reCAPTCHA
  try {
    grecaptcha.execute(ctaRecaptchaSiteKey, {action: action || 'submit'})
      .then(function(token) {
        if (callback) callback(token);
      })
      .catch(function(error) {
        // Handle specific reCAPTCHA errors
        var errorMsg = error && error.message ? error.message : String(error);
        if (errorMsg.indexOf('Invalid site key') !== -1 || errorMsg.indexOf('localhost') !== -1) {
          console.warn('reCAPTCHA: Domain "' + window.location.hostname + '" not in allowed list. Add it to your reCAPTCHA site key domains: https://console.cloud.google.com/security/recaptcha');
        } else if (errorMsg.indexOf('BROWSER_ERROR') !== -1) {
          console.warn('reCAPTCHA: Network/timeout error. Form submissions will still work.');
        } else {
          console.warn('reCAPTCHA v3 error:', error);
        }
        if (callback) callback(null);
      });
  } catch (error) {
    console.warn('reCAPTCHA v3 execution error:', error);
    if (callback) callback(null);
  }
}

// Set reCAPTCHA token in hidden input
function ctaSetRecaptchaToken(token, formId) {
  var inputId = formId ? formId + '-recaptcha-response' : 'g-recaptcha-response';
  var input = document.getElementById(inputId) || document.querySelector('input[name="g-recaptcha-response"]');
  if (input) {
    input.value = token || '';
  }
  
  // Clear error messages
  var errorElements = document.querySelectorAll('[id$="-robot-error"], [id$="-recaptcha-error"]');
  errorElements.forEach(function(el) {
    el.style.display = 'none';
    el.textContent = '';
  });
}

// Get reCAPTCHA token before form submission
function ctaGetRecaptchaToken(formId, action, callback) {
  if (!ctaRecaptchaSiteKey) {
    if (callback) callback(null);
    return;
  }
  
  ctaExecuteRecaptcha(action || 'submit', function(token) {
    if (token) {
      ctaSetRecaptchaToken(token, formId);
    }
    if (callback) callback(token);
  });
}

// Initialize reCAPTCHA v3 on page load
function ctaInitializeRecaptcha() {
  if (!ctaRecaptchaSiteKey) {
    return;
  }
  
  // Check if reCAPTCHA is loaded
  if (typeof grecaptcha !== 'undefined') {
    // Wait for reCAPTCHA to be ready
    if (typeof grecaptcha.ready === 'function') {
      grecaptcha.ready(function() {
        ctaExecuteRecaptcha('pageview', function(token) {
          // Token obtained, ready for form submissions
        });
      });
    } else if (typeof grecaptcha.execute === 'function') {
      // Already loaded and ready
      ctaExecuteRecaptcha('pageview', function(token) {
        // Token obtained, ready for form submissions
      });
    } else {
      // reCAPTCHA object exists but not ready yet, wait a bit and retry
      setTimeout(ctaInitializeRecaptcha, 100);
    }
  } else {
    // reCAPTCHA script not loaded yet, wait for it
    // Check if script is in the DOM
    var recaptchaScript = document.querySelector('script[src*="recaptcha/api.js"]');
    if (recaptchaScript) {
      // Script is loading, wait for it
      recaptchaScript.addEventListener('load', function() {
        setTimeout(ctaInitializeRecaptcha, 100);
      });
    } else {
      // Script might not be enqueued, wait a bit and retry (max 5 seconds)
      var retries = 0;
      var maxRetries = 50; // 50 * 100ms = 5 seconds
      var checkInterval = setInterval(function() {
        retries++;
        if (typeof grecaptcha !== 'undefined') {
          clearInterval(checkInterval);
          ctaInitializeRecaptcha();
        } else if (retries >= maxRetries) {
          clearInterval(checkInterval);
          // Give up, reCAPTCHA not available
        }
      }, 100);
    }
  }
}

// Try to initialize when DOM is ready
document.addEventListener('DOMContentLoaded', ctaInitializeRecaptcha);

// Also try on window load (in case DOMContentLoaded fires too early)
window.addEventListener('load', function() {
  if (ctaRecaptchaSiteKey && typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function') {
    grecaptcha.ready(function() {
    ctaExecuteRecaptcha('pageview', function(token) {
      // Token obtained, ready for form submissions
      });
    });
  }
});
</script>
<?php endif; ?>

<?php
// Facebook Pixel (client-side)
$fb_pixel_id = get_option('cta_facebook_pixel_id', '');
if (!empty($fb_pixel_id)) :
?>
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo esc_js($fb_pixel_id); ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo esc_attr($fb_pixel_id); ?>&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>

