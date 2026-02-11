<?php
/**
 * Coming Soon template (reuses 404 styling).
 *
 * @package ccs-theme
 */
defined('ABSPATH') || exit;

get_header();
?>

<main id="main-content" class="site-main">
  <section class="error-404-section" aria-labelledby="coming-soon-title">
    <div class="container">
      <div class="error-404-content">
        <div class="error-404-icon" aria-hidden="true">
          <i class="fas fa-tools"></i>
        </div>
        <h1 id="coming-soon-title" class="error-404-title">Oops! We’re still working on this page!</h1>
        <p class="error-404-description">Check back soon.</p>
        <div class="error-404-actions">
          <a class="btn btn-primary" href="<?php echo esc_url(home_url('/')); ?>">Back to Home</a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>

