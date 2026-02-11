<?php
/**
 * Resource download modal (lead capture) - used by downloadable resources page.
 *
 * @package ccs-theme
 */
defined('ABSPATH') || exit;

$privacy_page = get_page_by_path('privacy-policy');
$privacy_url = $privacy_page ? get_permalink($privacy_page) : home_url('/privacy-policy/');
?>

<div id="resource-download-modal" class="resource-download-modal" role="dialog" aria-modal="true" aria-labelledby="resource-download-title" aria-hidden="true">
  <div class="resource-download-backdrop" data-resource-modal-close aria-hidden="true"></div>

  <div class="resource-download-card" role="document">
    <button type="button" class="resource-download-close" data-resource-modal-close aria-label="Close download form">
      <i class="fas fa-times" aria-hidden="true"></i>
    </button>

    <div class="resource-download-header">
      <div class="resource-download-icon" aria-hidden="true">
        <i class="fas fa-download"></i>
      </div>
      <h2 id="resource-download-title" class="resource-download-title">Get this free resource</h2>
      <p class="resource-download-subtitle" id="resource-download-subtitle">We’ll email you a secure download link.</p>
    </div>

    <form id="resource-download-form" class="resource-download-form" novalidate>
      <input type="hidden" name="resource_id" id="resource-download-resource-id" value="">

      <div class="resource-download-grid">
        <div class="resource-download-field">
          <label for="resource-first-name" class="resource-download-label">First name <span class="required-indicator" aria-label="required">*</span></label>
          <input type="text" id="resource-first-name" name="first_name" class="resource-download-input" autocomplete="given-name" required aria-required="true" aria-describedby="resource-first-name-error" aria-invalid="false">
          <p id="resource-first-name-error" class="resource-download-error" role="alert" aria-live="polite" style="display:none"></p>
        </div>

        <div class="resource-download-field">
          <label for="resource-last-name" class="resource-download-label">Last name <span class="required-indicator" aria-label="required">*</span></label>
          <input type="text" id="resource-last-name" name="last_name" class="resource-download-input" autocomplete="family-name" required aria-required="true" aria-describedby="resource-last-name-error" aria-invalid="false">
          <p id="resource-last-name-error" class="resource-download-error" role="alert" aria-live="polite" style="display:none"></p>
        </div>

        <div class="resource-download-field resource-download-field-full">
          <label for="resource-email" class="resource-download-label">Email <span class="required-indicator" aria-label="required">*</span></label>
          <input type="email" id="resource-email" name="email" class="resource-download-input" autocomplete="email" required aria-required="true" aria-describedby="resource-email-error" aria-invalid="false">
          <p id="resource-email-error" class="resource-download-error" role="alert" aria-live="polite" style="display:none"></p>
        </div>

        <div class="resource-download-field">
          <label for="resource-phone" class="resource-download-label">Phone number <span class="optional-indicator">(optional)</span></label>
          <input type="tel" id="resource-phone" name="phone" class="resource-download-input" autocomplete="tel" aria-describedby="resource-phone-error" aria-invalid="false">
          <p id="resource-phone-error" class="resource-download-error" role="alert" aria-live="polite" style="display:none"></p>
        </div>

        <div class="resource-download-field">
          <label for="resource-dob" class="resource-download-label">Date of birth <span class="optional-indicator">(optional)</span></label>
          <input type="date" id="resource-dob" name="date_of_birth" class="resource-download-input" aria-describedby="resource-dob-error" aria-invalid="false" max="<?php echo esc_attr(date('Y-m-d', strtotime('-13 years'))); ?>">
          <p id="resource-dob-error" class="resource-download-error" role="alert" aria-live="polite" style="display:none"></p>
        </div>
      </div>

      <div class="resource-download-consent">
        <div class="resource-download-consent-row">
          <input type="checkbox" id="resource-consent" name="consent" class="resource-download-checkbox" required aria-required="true" aria-describedby="resource-consent-error" aria-invalid="false">
          <label for="resource-consent" class="resource-download-consent-label">
            I consent to receiving emails, resources, updates and marketing communications from Continuity of Care Services.
            <a class="resource-download-privacy-link" href="<?php echo esc_url($privacy_url); ?>">Privacy Policy</a>
            <span class="required-indicator" aria-label="required">*</span>
          </label>
        </div>
        <p id="resource-consent-error" class="resource-download-error" role="alert" aria-live="polite" style="display:none"></p>
      </div>

      <button type="submit" class="btn btn-primary resource-download-submit">
        Email me the resource
      </button>

      <p class="resource-download-note" id="resource-download-note">You’ll receive a secure download link by email.</p>
    </form>
  </div>
</div>

