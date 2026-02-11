<?php
/**
 * 404 Page Template
 *
 * @package ccs-theme
 */

get_header();
?>

<main id="main-content">
  <section class="error-404-section">
    <div class="container">
      <div class="error-404-content">
        <h1 class="error-404-title">404</h1>
        <h2 class="error-404-subtitle">Page Not Found</h2>
        <p class="error-404-description">
          Sorry, the page you're looking for doesn't exist or has been moved. 
          Let's get you back on track.
        </p>
        <div class="error-404-actions">
          <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">Back to Home</a>
          <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-secondary">Browse Courses</a>
        </div>
        
        <div class="error-404-help">
          <p>Need help? Call us on <a href="tel:01622587343">01622 587343</a></p>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>

