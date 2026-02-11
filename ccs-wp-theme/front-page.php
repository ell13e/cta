<?php
/**
 * Homepage Template – CCS
 *
 * @package ccs-theme
 */

get_header();

$contact = ccs_get_contact_info();
$contact_page = ccs_page_url('contact');
$services_url = get_post_type_archive_link('care_service');
if (!$services_url) {
    $services_url = home_url('/services/');
}
?>

<nav class="skip-links" aria-label="Skip to page sections">
  <a href="#hero-heading" class="skip-link">Skip to main content</a>
  <a href="#trust-signals" class="skip-link">Skip to trust signals</a>
  <a href="#why-ccs" class="skip-link">Skip to why CCS</a>
</nav>

<main id="main-content">
  <section class="hero" aria-labelledby="hero-heading">
    <div class="container">
      <div class="hero-content">
        <?php if (defined('CCS_CQC_REPORT_URL')) : ?>
        <a href="<?php echo esc_url(CCS_CQC_REPORT_URL); ?>" target="_blank" rel="noopener noreferrer" class="ccs-hero-cqc-badge">
          <span class="ccs-cqc-rating">CQC Rating: <?php echo esc_html(defined('CCS_CQC_RATING') ? CCS_CQC_RATING : 'Good'); ?></span>
          <span class="ccs-cqc-text">View our report</span>
        </a>
        <?php endif; ?>

        <div class="hero-heading-wrapper">
          <h1 id="hero-heading" class="hero-title"><?php echo esc_html(ccs_get_field('hero_headline', false, 'Your Team, Your Time, Your Life')); ?></h1>
          <p class="hero-subtitle"><?php echo esc_html(ccs_get_field('hero_subheadline', false, "It's not just what we do, it's how we do it. Maidstone-based, Kent-wide home care.")); ?></p>
        </div>
        <div class="hero-cta">
          <a href="<?php echo esc_url($contact_page ? $contact_page : '#'); ?>?type=care-assessment" class="btn btn-primary">Request a care assessment</a>
          <a href="<?php echo esc_url($contact['phone_link']); ?>" class="btn btn-secondary">
            <i class="fas fa-phone icon-phone" aria-hidden="true"></i>
            <span><?php echo esc_html($contact['phone']); ?></span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section id="trust-signals" class="ccs-trust-signals" aria-labelledby="trust-heading">
    <div class="container">
      <h2 id="trust-heading" class="screen-reader-text">Why choose us</h2>
      <div class="ccs-trust-grid">
        <?php if (defined('CCS_CQC_RATING')) : ?>
        <div class="ccs-trust-item">
          <span class="ccs-trust-label">CQC Rating</span>
          <span class="ccs-trust-value"><?php echo esc_html(CCS_CQC_RATING); ?></span>
        </div>
        <?php endif; ?>
        <div class="ccs-trust-item">
          <span class="ccs-trust-label">Phone</span>
          <a href="<?php echo esc_url(defined('CCS_PHONE_LINK') ? CCS_PHONE_LINK : 'tel:01622809881'); ?>"><?php echo esc_html(defined('CCS_PHONE') ? CCS_PHONE : '01622 809881'); ?></a>
        </div>
        <?php if (defined('CCS_OFFICE_HOURS_DAYS')) : ?>
        <div class="ccs-trust-item">
          <span class="ccs-trust-label">Office hours</span>
          <span class="ccs-trust-value"><?php echo esc_html(CCS_OFFICE_HOURS_DAYS . ' ' . (defined('CCS_OFFICE_HOURS_START') ? CCS_OFFICE_HOURS_START : '9am') . '–' . (defined('CCS_OFFICE_HOURS_END') ? CCS_OFFICE_HOURS_END : '5pm')); ?></span>
        </div>
        <?php endif; ?>
        <?php if (defined('CCS_ONCALL_STATUS')) : ?>
        <div class="ccs-trust-item">
          <span class="ccs-trust-label">Support</span>
          <span class="ccs-trust-value"><?php echo esc_html(CCS_ONCALL_STATUS); ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section id="why-ccs" class="ccs-why-section" aria-labelledby="why-ccs-heading">
    <div class="container">
      <h2 id="why-ccs-heading" class="section-title"><?php echo esc_html(ccs_get_field('why_ccs_title', false, 'Why CCS')); ?></h2>
      <p class="ccs-why-lead"><?php echo esc_html(ccs_get_field('why_ccs_lead', false, "We don't rush or rotate staff. We take time to get to know each person, not just their care plan.")); ?></p>
      <div class="ccs-why-ctas">
        <a href="<?php echo esc_url($services_url); ?>" class="btn btn-primary">Our services</a>
        <a href="<?php echo esc_url($contact_page ? $contact_page : '#'); ?>" class="btn btn-secondary">Contact us</a>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>
