<?php
/**
 * Resource unavailable modal - shown when a resource is not yet available for download.
 *
 * @package ccs-theme
 */
defined('ABSPATH') || exit;
?>

<div id="resource-unavailable-modal" class="resource-unavailable-modal" role="dialog" aria-modal="true" aria-labelledby="resource-unavailable-title" aria-hidden="true">
  <div class="resource-unavailable-backdrop" data-resource-unavailable-modal-close aria-hidden="true"></div>

  <div class="resource-unavailable-card" role="document">
    <button type="button" class="resource-unavailable-close" data-resource-unavailable-modal-close aria-label="Close modal">
      <i class="fas fa-times" aria-hidden="true"></i>
    </button>

    <div class="resource-unavailable-header">
      <div class="resource-unavailable-icon" aria-hidden="true">
        <i class="fas fa-clock"></i>
      </div>
      <h2 id="resource-unavailable-title" class="resource-unavailable-title">Resource Coming Soon</h2>
      <p class="resource-unavailable-subtitle" id="resource-unavailable-subtitle">This resource is not available yet.</p>
    </div>

    <div class="resource-unavailable-content">
      <p>We're currently preparing this resource and it will be available for download soon. Please check back later.</p>
      <p>If you'd like to be notified when this resource becomes available, please <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>">contact us</a>.</p>
    </div>

    <div class="resource-unavailable-actions">
      <button type="button" class="btn btn-primary resource-unavailable-close-btn" data-resource-unavailable-modal-close>
        Got it
      </button>
    </div>
  </div>
</div>
