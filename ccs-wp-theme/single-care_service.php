<?php
/**
 * Single Care Service – CCS
 *
 * @package ccs-theme
 */

get_header();

$contact_page = ccs_page_url('contact');
?>

<main id="main-content">
  <?php
  while (have_posts()) :
      the_post();
      $title = get_the_title();
      $content = get_the_content();
      $thumb = get_the_post_thumbnail(null, 'large');
  ?>
    <article class="ccs-single-service">
      <header class="ccs-single-service-header">
        <div class="container">
          <?php if ($thumb) : ?>
            <div class="ccs-single-service-image"><?php echo $thumb; ?></div>
          <?php endif; ?>
          <h1 class="ccs-single-service-title"><?php echo esc_html($title); ?></h1>
        </div>
      </header>
      <div class="container ccs-single-service-content">
        <div class="ccs-single-service-body">
          <?php echo apply_filters('the_content', $content); ?>
        </div>
        <?php if ($contact_page) : ?>
          <p class="ccs-single-service-cta">
            <a href="<?php echo esc_url($contact_page); ?>?type=care-assessment" class="btn btn-primary">Request a care assessment</a>
          </p>
        <?php endif; ?>
      </div>
    </article>
  <?php endwhile; ?>
</main>

<?php get_footer(); ?>
