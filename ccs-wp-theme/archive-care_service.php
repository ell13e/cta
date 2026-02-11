<?php
/**
 * Care Services Archive – CCS
 *
 * @package ccs-theme
 */

get_header();

$services_url = get_post_type_archive_link('care_service');
$contact_page = ccs_page_url('contact');
?>

<main id="main-content">
  <header class="ccs-archive-header">
    <div class="container">
      <h1 class="ccs-archive-title">Our Services</h1>
      <p class="ccs-archive-description">Maidstone-based, Kent-wide. We don't rush or rotate staff—we take time to get to know each person.</p>
    </div>
  </header>

  <div class="container">
    <?php if (have_posts()) : ?>
      <ul class="ccs-services-grid">
        <?php
        while (have_posts()) :
            the_post();
            $link = get_permalink();
            $title = get_the_title();
            $excerpt = get_the_excerpt();
            $thumb = get_the_post_thumbnail(null, 'medium');
        ?>
          <li class="ccs-service-card">
            <a href="<?php echo esc_url($link); ?>" class="ccs-service-card-link">
              <?php if ($thumb) : ?>
                <div class="ccs-service-card-image"><?php echo $thumb; ?></div>
              <?php endif; ?>
              <div class="ccs-service-card-body">
                <h2 class="ccs-service-card-title"><?php echo esc_html($title); ?></h2>
                <?php if ($excerpt) : ?>
                  <p class="ccs-service-card-excerpt"><?php echo esc_html($excerpt); ?></p>
                <?php endif; ?>
                <span class="ccs-service-card-cta">Learn more</span>
              </div>
            </a>
          </li>
        <?php endwhile; ?>
      </ul>
      <?php
      the_posts_pagination([
          'mid_size'  => 2,
          'prev_text' => 'Previous',
          'next_text' => 'Next',
      ]);
      ?>
    <?php else : ?>
      <p class="ccs-no-services">No care services have been added yet. Check back soon.</p>
    <?php endif; ?>

    <?php if ($contact_page) : ?>
      <p class="ccs-archive-cta">
        <a href="<?php echo esc_url($contact_page); ?>?type=care-assessment" class="btn btn-primary">Request a care assessment</a>
      </p>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
