<?php
/**
 * Upcoming Courses (Course Events) Archive Template
 *
 * @package ccs-theme
 */

get_header();

$contact = ccs_get_contact_info();

$today = date('Y-m-d');

$args = [
  'post_type' => 'course_event',
  'posts_per_page' => -1,
  'meta_key' => 'event_date',
  'orderby' => 'meta_value',
  'order' => 'ASC',
  'meta_query' => [
    'relation' => 'AND',
    [
      'key' => 'event_date',
      'value' => $today,
      'compare' => '>=',
      'type' => 'DATE',
    ],
    [
      'relation' => 'OR',
      [
        'key' => 'event_active',
        'value' => '1',
        'compare' => '=',
      ],
      [
        'key' => 'event_active',
        'compare' => 'NOT EXISTS',
      ],
    ],
  ],
];

$events = new WP_Query($args);
$all_events = $events->posts;
$featured_event = !empty($all_events) ? $all_events[0] : null;
$remaining_events = array_slice($all_events, 1);

$site_wide_active = function_exists('ccs_is_site_wide_discount_active') ? ccs_is_site_wide_discount_active() : false;
$site_wide_percent = $site_wide_active && function_exists('ccs_get_site_wide_discount_percentage') ? floatval(ccs_get_site_wide_discount_percentage()) : 0;
$site_wide_label = '';
if ($site_wide_active && function_exists('ccs_get_site_wide_discount')) {
  $sw = ccs_get_site_wide_discount();
  $site_wide_label = !empty($sw['label']) ? (string) $sw['label'] : 'Site-Wide Sale';
}
?>

<main id="main-content" class="site-main">
  <!-- Hero Section -->
  <section class="group-hero-section" aria-labelledby="events-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><span class="breadcrumb-current" aria-current="page">Upcoming Courses</span></li>
        </ol>
      </nav>
      <h1 id="events-heading" class="hero-title">Upcoming Care Training Courses in Kent</h1>
      <p class="hero-subtitle">
        See upcoming dates in Maidstone, Kent and book your place on First Aid and mandatory care training courses.
      </p>
    </div>
  </section>

  <!-- Events Listing Section -->
  <section class="events-listing-section">
    <div class="container">
      <?php if ($featured_event) : 
        $f_course = get_field('linked_course', $featured_event->ID);
        $f_event_date = get_field('event_date', $featured_event->ID);
        $f_start_time = get_field('start_time', $featured_event->ID);
        $f_end_time = get_field('end_time', $featured_event->ID);
        $f_location = get_field('event_location', $featured_event->ID);
        $f_event_price = get_field('event_price', $featured_event->ID);
        
        $f_course_title = $f_course ? $f_course->post_title : $featured_event->post_title;
        $f_price = $f_event_price ?: ($f_course ? get_field('course_price', $f_course->ID) : '');
        $f_duration = $f_course ? get_field('course_duration', $f_course->ID) : '';
        
        $f_category_name = '';
        if ($f_course) {
          $f_terms = get_the_terms($f_course->ID, 'course_category');
          if ($f_terms && !is_wp_error($f_terms)) {
            $f_category_name = $f_terms[0]->name;
          }
        }
        
        $f_image_url = '';
        if ($f_course && has_post_thumbnail($f_course->ID)) {
          $f_image_url = get_the_post_thumbnail_url($f_course->ID, 'large');
        }
      ?>
      <!-- Featured Event Hero Card -->
      <a href="<?php echo esc_url(get_permalink($featured_event->ID)); ?>" class="events-featured-hero" tabindex="0" aria-label="View details for <?php echo esc_attr($f_course_title); ?>">
        <div class="events-featured-hero-bg"<?php if ($f_image_url) : ?> style="background-image: url('<?php echo esc_url($f_image_url); ?>');"<?php endif; ?>></div>
        <div class="events-featured-hero-overlay"></div>
        <div class="events-featured-hero-content">
          <span class="events-featured-hero-badge">Upcoming Event</span>
          <h2 class="events-featured-hero-title"><?php echo esc_html($f_course_title); ?></h2>
          <div class="events-featured-hero-meta">
            <?php if ($f_event_date) : ?>
            <div class="events-featured-hero-meta-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
              <span><?php echo esc_html(date('D, j M Y', strtotime($f_event_date))); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($f_start_time) : ?>
            <div class="events-featured-hero-meta-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              <span><?php echo esc_html($f_start_time); ?><?php echo $f_end_time ? ' - ' . esc_html($f_end_time) : ''; ?></span>
            </div>
            <?php endif; ?>
          </div>
          <?php if ($f_location) : ?>
          <div class="events-featured-hero-location">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <span><?php echo esc_html($f_location); ?></span>
          </div>
          <?php endif; ?>
        </div>
      </a>
      <?php endif; ?>

      <!-- Events Grid -->
      <div class="events-grid-section">
        <h2 class="sr-only">Upcoming Training Events</h2>
        <?php if (!empty($remaining_events) || (!$featured_event && $events->have_posts())) : ?>
        <div class="events-grid">
          <?php 
          $events_to_display = $featured_event ? $remaining_events : $all_events;
          foreach ($events_to_display as $event_post) : 
            $event_id = $event_post->ID;
            $course = get_field('linked_course', $event_id);
            $event_date = get_field('event_date', $event_id);
            $start_time = get_field('start_time', $event_id);
            $end_time = get_field('end_time', $event_id);
            $location = get_field('event_location', $event_id);
            $spaces = get_field('spaces_available', $event_id);
            $event_price = get_field('event_price', $event_id);
            
            $course_title = $course ? $course->post_title : $event_post->post_title;
            $price = $event_price ?: ($course ? get_field('course_price', $course->ID) : '');
            if ($price) {
              $price = preg_replace('/[£$,\s]/', '', $price);
              $price = is_numeric($price) ? floatval($price) : '';
            }
            $duration = $course ? get_field('course_duration', $course->ID) : '';

            $original_price = $price;
            $discounted_price = $price;
            $has_site_wide_discount = false;
            if ($site_wide_active && $site_wide_percent > 0 && $original_price && function_exists('ccs_apply_site_wide_discount')) {
              $discounted_price = floatval(ccs_apply_site_wide_discount(floatval($original_price)));
              if ($discounted_price > 0 && $discounted_price < floatval($original_price)) {
                $has_site_wide_discount = true;
              }
            }
            
            $category_class = '';
            $category_name = '';
            if ($course) {
              $terms = get_the_terms($course->ID, 'course_category');
              if ($terms && !is_wp_error($terms)) {
                $category_name = $terms[0]->name;
                $category_class = 'event-card-' . sanitize_title($terms[0]->slug);
              }
            }
        ?>
        <a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="event-card <?php echo esc_attr($category_class); ?>">
          <div class="event-card-image-wrapper">
            <?php if ($course && has_post_thumbnail($course->ID)) : 
              $thumbnail_id = get_post_thumbnail_id($course->ID);
              $image_src = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
              $image_srcset = wp_get_attachment_image_srcset($thumbnail_id, 'medium_large');
            ?>
            <img srcset="<?php echo esc_attr($image_srcset); ?>"
                 src="<?php echo esc_url($image_src[0]); ?>"
                 sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                 alt="<?php echo esc_attr($course_title); ?>"
                 class="event-card-image"
                 loading="lazy"
                 width="400"
                 height="225">
            <?php endif; ?>
            <div class="event-card-pills">
              <?php if ($category_name) : ?>
              <span class="event-card-pill event-card-pill-category"><?php echo esc_html($category_name); ?></span>
              <?php endif; ?>
              <?php if ($duration) : ?>
              <span class="event-card-pill event-card-pill-duration">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <?php echo esc_html($duration); ?>
              </span>
              <?php endif; ?>
            </div>
          </div>
          <div class="event-card-content">
            <div class="event-card-meta-info">
              <?php if ($event_date) : ?>
              <div class="event-card-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span><?php echo esc_html(date('D, j M Y', strtotime($event_date))); ?></span>
              </div>
              <?php endif; ?>
              <?php if ($start_time) : ?>
              <div class="event-card-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span><?php echo esc_html($start_time); ?><?php echo $end_time ? ' - ' . esc_html($end_time) : ''; ?></span>
              </div>
              <?php endif; ?>
            </div>
            <h3 class="event-card-title"><?php echo esc_html($course_title); ?></h3>
            <?php if ($location) : ?>
            <div class="event-card-location">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <span><?php echo esc_html($location); ?></span>
            </div>
            <?php endif; ?>
            <div class="event-card-footer">
              <?php if ($discounted_price) : ?>
              <div class="event-card-price">
                <?php if ($has_site_wide_discount) : ?>
                  <div class="event-card-discount-badges" aria-label="Offer details">
                    <span class="badge badge-discount">Save <?php echo esc_html($site_wide_percent); ?>%</span>
                    <span class="badge badge-critical"><?php echo esc_html($site_wide_label); ?></span>
                  </div>
                  <span class="event-card-price-original" aria-label="Original price">£<?php echo esc_html(number_format($original_price, 0)); ?></span>
                  <span class="event-card-price-amount is-discounted">From £<?php echo esc_html(number_format($discounted_price, 0)); ?></span>
                <?php else : ?>
                  <span class="event-card-price-amount">From £<?php echo esc_html(number_format($discounted_price, 0)); ?></span>
                <?php endif; ?>
                <span class="event-card-price-label">per person</span>
              </div>
              <?php endif; ?>
              <div class="event-card-cta">
                <span>Book Now</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>
        </a>
        <?php endforeach; wp_reset_postdata(); ?>
        </div>
        <?php else : ?>
        <div class="no-events-message">
          <p>No upcoming courses scheduled at the moment.</p>
          <p>Check back soon or <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>">contact us</a> to arrange group training for your team.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-content">
        <h2 class="cta-title">Can't Find a Suitable Date?</h2>
        <p class="cta-description">We can arrange training at your premises for groups of 6 or more. Flexible scheduling to fit around your team's needs.</p>
        <div class="cta-buttons">
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('group-training'))); ?>" class="btn btn-primary">Learn About Group Training</a>
          <a href="<?php echo esc_url($contact['phone_link']); ?>" class="btn btn-secondary">
            <i class="fas fa-phone" aria-hidden="true"></i>
            <?php echo esc_html($contact['phone']); ?>
          </a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>

