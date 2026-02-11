<?php
/**
 * Template Name: Contact Us
 *
 * @package ccs-theme
 */

get_header();

$contact = ccs_get_contact_info();

$hero_title = get_field('hero_title') ?: 'Contact Us for Care Training in Kent';
$hero_subtitle = get_field('hero_subtitle') ?: "Book a course, arrange group training, or ask about compliance. Call, email, or use the form below.";
?>

<main id="main-content">
  <section class="group-hero-section" aria-labelledby="contact-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
          </li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <span class="breadcrumb-current" aria-current="page">Contact Us</span>
          </li>
        </ol>
      </nav>
      <h1 id="contact-heading" class="hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
    </div>
  </section>

  <section class="contact-main-section" aria-labelledby="contact-details-heading">
    <div class="container">
      <h2 id="contact-details-heading" class="sr-only">Contact details</h2>
      <div class="contact-main-layout">
        <div class="contact-info-panel">
          <div class="contact-quick-cards-stacked">
            <a href="<?php echo esc_url($contact['phone_link']); ?>" class="contact-quick-card">
              <div class="contact-quick-icon-wrapper">
                <i class="fas fa-phone contact-quick-icon" aria-hidden="true"></i>
              </div>
              <div class="contact-quick-content">
                <h3 class="contact-quick-title">Call Us</h3>
                <p class="contact-quick-value"><?php echo esc_html($contact['phone']); ?></p>
              </div>
            </a>

            <a href="mailto:<?php echo esc_attr($contact['email']); ?>" class="contact-quick-card">
              <div class="contact-quick-icon-wrapper">
                <i class="fas fa-envelope contact-quick-icon" aria-hidden="true"></i>
              </div>
              <div class="contact-quick-content">
                <h3 class="contact-quick-title">Email Us</h3>
                <p class="contact-quick-value"><?php echo esc_html($contact['email']); ?></p>
              </div>
            </a>
          </div>

          <div class="contact-map-card">
            <div class="contact-map-wrapper">
              <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2495.717049057254!2d0.546680377251131!3d51.27952727176235!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47df3394790a6a17%3A0x6bb94452df2da3f5!2sContinuity%20Training%20Academy!5e0!3m2!1sen!2suk!4v1766494532400!5m2!1sen!2suk"
                width="100%"
                height="100%"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Continuity of Care Services Location"
                aria-label="Map showing Continuity of Care Services location at The Maidstone Studios, New Cut Road, Maidstone, Kent ME14 5NZ"
              ></iframe>
            </div>
          </div>
        </div>

        <div class="contact-form-panel">
          <div class="contact-form-card-new">
            <div class="contact-form-header">
              <h2 class="contact-form-heading">Send Us a Message</h2>
            </div>

            <?php 
            if (shortcode_exists('contact-form-7')) {
              echo do_shortcode('[contact-form-7 id="contact-form" title="Contact Form"]');
            } else {
            ?>
            <form id="contact-form" class="contact-form-new" novalidate>
              <input type="text" name="website" id="website" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
              <input type="text" name="url" id="contact-url" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
              <input type="text" name="homepage" id="contact-homepage" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
              <input type="hidden" name="form_load_time" id="contact-form-load-time" value="">
              <input type="hidden" name="submission_time" id="contact-submission-time" value="">

              <div id="contact-form-error-summary" class="contact-form-error-summary" role="alert" aria-live="assertive" style="display: none">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <div>
                  <strong>Please correct the following errors:</strong>
                  <ul id="contact-form-error-list"></ul>
                </div>
              </div>

              <div class="contact-form-fields-grid">
                <div class="contact-form-field">
                  <label for="name" class="contact-form-label">
                    Your Name <span class="contact-form-required">*</span>
                  </label>
                  <div class="contact-form-input-wrapper-new">
                    <i class="fas fa-user contact-form-input-icon" aria-hidden="true"></i>
                    <input
                      type="text"
                      id="name"
                      name="name"
                      class="contact-form-input"
                      placeholder="Enter your full name"
                      autocomplete="name"
                      required
                      aria-required="true"
                      aria-describedby="name-error"
                      aria-invalid="false"
                    />
                    <i class="fas fa-check-circle contact-form-success-icon" aria-hidden="true"></i>
                    <i class="fas fa-exclamation-circle contact-form-error-icon" aria-hidden="true"></i>
                  </div>
                  <p
                    id="name-error"
                    class="contact-form-error"
                    role="alert"
                    aria-live="polite"
                    style="display: none"
                  ></p>
                </div>

                <div class="contact-form-field">
                  <label for="email" class="contact-form-label">
                    Email <span class="contact-form-required">*</span>
                  </label>
                  <div class="contact-form-input-wrapper-new">
                    <i class="fas fa-envelope contact-form-input-icon" aria-hidden="true"></i>
                    <input
                      type="email"
                      id="email"
                      name="email"
                      class="contact-form-input"
                      placeholder="your.email@example.com"
                      autocomplete="email"
                      required
                      aria-required="true"
                      aria-describedby="email-error"
                      aria-invalid="false"
                    />
                    <i class="fas fa-check-circle contact-form-success-icon" aria-hidden="true"></i>
                    <i class="fas fa-exclamation-circle contact-form-error-icon" aria-hidden="true"></i>
                  </div>
                  <p
                    id="email-error"
                    class="contact-form-error"
                    role="alert"
                    aria-live="polite"
                    style="display: none"
                  ></p>
                </div>

                <div class="contact-form-field">
                  <label for="phone" class="contact-form-label">
                    Phone Number <span class="contact-form-required">*</span>
                  </label>
                  <div class="contact-form-input-wrapper-new">
                    <i class="fas fa-phone contact-form-input-icon" aria-hidden="true"></i>
                    <input
                      type="tel"
                      id="phone"
                      name="phone"
                      class="contact-form-input"
                      placeholder="01622 587343"
                      autocomplete="tel"
                      required
                      aria-required="true"
                      aria-describedby="phone-error"
                      aria-invalid="false"
                    />
                    <i class="fas fa-check-circle contact-form-success-icon" aria-hidden="true"></i>
                    <i class="fas fa-exclamation-circle contact-form-error-icon" aria-hidden="true"></i>
                  </div>
                  <p
                    id="phone-error"
                    class="contact-form-error"
                    role="alert"
                    aria-live="polite"
                    style="display: none"
                  ></p>
                </div>
              </div>

              <div class="contact-form-field">
                <label for="enquiryType" class="contact-form-label">
                  Enquiry Type <span class="contact-form-required">*</span>
                </label>
                <div class="contact-form-select-wrapper">
                  <select
                    id="enquiryType"
                    name="enquiryType"
                    class="contact-form-select"
                    required
                    aria-required="true"
                    aria-describedby="enquiryType-error"
                    aria-invalid="false"
                  >
                    <option value="">Please select an enquiry type...</option>
                    <option value="training-consultation">Book a Free Training Consultation</option>
                    <option value="group-training">Group Training</option>
                    <option value="book-course">Book a Course</option>
                    <option value="cqc-training">CQC Training Enquiry</option>
                    <option value="support">Support/FAQ</option>
                    <option value="general">General Enquiry</option>
                  </select>
                  <i class="fas fa-list contact-form-select-icon" aria-hidden="true"></i>
                  <i class="fas fa-check-circle contact-form-success-icon" aria-hidden="true"></i>
                  <i class="fas fa-exclamation-circle contact-form-error-icon" aria-hidden="true"></i>
                </div>
                <p
                  id="enquiryType-error"
                  class="contact-form-error"
                  role="alert"
                  aria-live="polite"
                  style="display: none"
                ></p>
              </div>

              <div id="course-selection-field" class="contact-form-field" style="display: none;">
                <label class="contact-form-label">
                  Select Course(s) <span class="contact-form-required">*</span>
                </label>
                <div id="course-selection-container" class="course-selection-container">
                  <div id="course-selection-loading" class="course-selection-loading">
                    <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                    <span>Loading courses...</span>
                  </div>
                  <div id="course-selection-list" class="course-selection-list" role="group" aria-labelledby="course-selection-label" style="display: none;"></div>
                </div>
                <p id="course-selection-error" class="contact-form-error" role="alert" aria-live="polite" style="display: none"></p>
              </div>

              <div id="discount-code-field" class="contact-form-field" style="display: none;">
                <label for="discount-code" class="contact-form-label">
                  Discount Code (Optional)
                </label>
                <div class="contact-form-input-wrapper">
                  <input
                    type="text"
                    id="discount-code"
                    name="discount_code"
                    class="contact-form-input"
                    placeholder="Enter code"
                    autocomplete="off"
                    aria-describedby="discount-code-error"
                    aria-invalid="false"
                    style="text-transform: uppercase;"
                  />
                  <i class="fas fa-check-circle contact-form-success-icon" aria-hidden="true"></i>
                  <i class="fas fa-exclamation-circle contact-form-error-icon" aria-hidden="true"></i>
                </div>
                <p
                  id="discount-code-error"
                  class="contact-form-error"
                  role="alert"
                  aria-live="polite"
                  style="display: none"
                ></p>
              </div>

              <div class="contact-form-field">
                <label for="message" class="contact-form-label">
                  Message <span class="contact-form-required">*</span>
                </label>
                <div class="contact-form-textarea-wrapper-new">
                  <textarea
                    id="message"
                    name="message"
                    class="contact-form-textarea"
                    rows="6"
                    placeholder="Tell us about your training needs..."
                    maxlength="1000"
                    required
                    aria-required="true"
                    aria-describedby="message-error message-counter"
                    aria-invalid="false"
                  ></textarea>
                  <i class="fas fa-comment-dots contact-form-textarea-icon" aria-hidden="true"></i>
                  <i class="fas fa-check-circle contact-form-success-icon" aria-hidden="true"></i>
                  <i class="fas fa-exclamation-circle contact-form-error-icon" aria-hidden="true"></i>
                </div>
                <div class="contact-form-message-footer">
                  <p
                    id="message-error"
                    class="contact-form-error"
                    role="alert"
                    aria-live="polite"
                    style="display: none"
                  ></p>
                  <span id="message-counter" class="contact-form-char-counter" aria-live="polite">0/1000</span>
                </div>
              </div>

              <div class="contact-form-consent">
                <div class="contact-form-consent-checkbox-wrapper">
                  <input
                    type="checkbox"
                    id="contact-consent"
                    name="consent"
                    required
                    class="contact-form-consent-checkbox"
                    aria-required="true"
                    aria-describedby="consent-error"
                    aria-invalid="false"
                  />
                  <label for="contact-consent" class="contact-form-consent-label">
                    I would like to be contacted about training services. <span class="contact-form-required">*</span>
                  </label>
                </div>
                <div class="contact-form-consent-checkbox-wrapper" style="margin-top: 12px;">
                  <input
                    type="checkbox"
                    id="contact-marketing-consent"
                    name="marketingConsent"
                    checked
                    class="contact-form-consent-checkbox"
                    aria-describedby="marketing-consent-info"
                  />
                  <label for="contact-marketing-consent" class="contact-form-consent-label">
                    I would like to receive updates, offers, and training news from Continuity of Care Services.
                  </label>
                </div>
                <p
                  id="consent-error"
                  class="contact-form-error"
                  role="alert"
                  aria-live="polite"
                  style="display: none"
                ></p>
              </div>
              <p class="contact-form-privacy">
                By submitting this form, you agree to our <a href="<?php echo esc_url(ccs_page_url('privacy')); ?>" class="contact-form-privacy-link">Privacy Policy</a>
              </p>

              <div id="contact-form-success" class="contact-form-success" style="display: none">
                <i class="fas fa-check-circle contact-form-success-icon" aria-hidden="true"></i>
                <h3 class="contact-form-success-title">Message Sent!</h3>
                <p class="contact-form-success-text">We'll get back to you.</p>
              </div>

              <input type="hidden" name="g-recaptcha-response" id="contact-recaptcha-response" value="">
              <span id="contact-robot-error" class="contact-form-error-message" role="alert" aria-live="polite" style="display: none;"></span>

              <div class="contact-form-actions">
                <button type="submit" id="contact-form-submit" class="btn btn-primary contact-form-submit">
                  <span id="contact-form-submit-text">Send</span>
                  <span id="contact-form-submit-loading" class="contact-form-submit-loading" style="display: none" aria-hidden="true">
                    <span class="contact-form-spinner" aria-hidden="true"></span>
                    <span>Sending...</span>
                  </span>
                </button>
                <button type="button" id="contact-form-clear" class="btn btn-secondary contact-form-clear">
                  Clear Form
                </button>
              </div>
            </form>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </section>

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
      <p class="thank-you-modal-message" id="thank-you-message">We'll be in touch soon.</p>
      
      <div class="thank-you-modal-next-steps">
        <h3 class="thank-you-modal-next-steps-title">What happens next?</h3>
        <p class="thank-you-modal-next-steps-text">
          We'll review your message and get back to you.
        </p>
        <div class="thank-you-modal-actions">
          <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-secondary">Browse Our Courses</a>
        </div>
      </div>
    </div>
  </div>
</main>

<?php get_footer(); ?>

