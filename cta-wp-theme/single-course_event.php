<?php
/**
 * Single Course Event (Scheduled Session) Template
 * 
 * Matches the event-detail.html layout exactly
 *
 * @package CTA_Theme
 */

// Redirect very old events (1+ year past) to the upcoming courses archive
$event_date = get_field('event_date');
if ($event_date) {
    $event_timestamp = strtotime($event_date);
    $one_year_ago = strtotime('-1 year');
    
    if ($event_timestamp < $one_year_ago) {
        // Redirect to upcoming courses with 301 (permanent)
        wp_redirect(get_post_type_archive_link('course_event'), 301);
        exit;
    }
}

get_header();

$contact = cta_get_contact_info();

while (have_posts()) : the_post();
  
  // Auto-populate SEO Meta Tags
  $event_title = get_the_title();
  $event_date_raw = get_field('event_date');
  $event_date_formatted = $event_date_raw ? date('j F Y', strtotime($event_date_raw)) : '';
  $linked_course = get_field('linked_course');
  $course_title = $linked_course ? get_the_title($linked_course->ID) : $event_title;
  
  $meta_title = $course_title . ($event_date_formatted ? ' - ' . $event_date_formatted : '') . ' | Book Now | CTA';
  $meta_description = 'Book ' . $course_title . ' training' . ($event_date_formatted ? ' on ' . $event_date_formatted : '') . ' at our Maidstone centre. CQC-compliant, CPD-accredited. Instant certificates.';
  ?>
  <meta name="description" content="<?php echo esc_attr($meta_description); ?>">
  <meta property="og:title" content="<?php echo esc_attr($meta_title); ?>">
  <meta property="og:description" content="<?php echo esc_attr($meta_description); ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo esc_attr($meta_title); ?>">
  <meta name="twitter:description" content="<?php echo esc_attr($meta_description); ?>">
  <?php
  // Get event fields
  $course = get_field('linked_course');
  $event_date = get_field('event_date');
  $start_time = get_field('start_time');
  $end_time = get_field('end_time');
  $location = get_field('event_location');
  $spaces = get_field('spaces_available');
  $event_price = get_field('event_price');
  
  // Get course fields (from linked course)
  $course_title = $course ? $course->post_title : get_the_title();
  $description = $course ? get_field('course_description', $course->ID) : '';
  $duration = $course ? get_field('course_duration', $course->ID) : '';
  
  // Price logic: Use custom event price if set, otherwise use course price
  // If custom price is lower than original, show discount
  $original_price = $course ? get_field('course_price', $course->ID) : '';
  $price = $event_price ?: $original_price;
  $has_discount = false;
  $discount_percent = 0;
  $discount_type = '';
  $discount_label = '';
  $discount_code = '';
  $requires_code = false;
  
  if ($event_price && $original_price && floatval($event_price) < floatval($original_price)) {
    $has_discount = true;
    $discount_percent = round((1 - (floatval($event_price) / floatval($original_price))) * 100);
    $discount_type = 'event';
  }
  
  // Check for course-specific discount
  if ($course) {
    $course_discount = cta_get_course_discount($course->ID);
    $course_discount_active = cta_is_course_discount_active($course->ID);
    $course_discount_percent = cta_get_course_discount_percentage($course->ID);
    $course_requires_code = $course_discount['requires_code'] && !empty($course_discount['discount_code']);
    
    if ($course_discount_active && $course_discount_percent > 0) {
      if (!$course_requires_code) {
        // Automatic course discount
        $course_discounted_price = cta_apply_course_discount($course->ID, floatval($original_price));
        if (!$has_discount || $course_discounted_price < floatval($price)) {
          $has_discount = true;
          $discount_percent = $course_discount_percent;
          $price = $course_discounted_price;
          $discount_type = 'course';
          $discount_label = $course_discount['label'] ?: 'Special Offer';
        }
      } else {
        // Code required - show info
        $discount_type = 'course-code';
        $discount_label = $course_discount['label'] ?: 'Special Offer';
        $discount_code = $course_discount['discount_code'];
        $requires_code = true;
        $discount_percent = $course_discount_percent;
      }
    }
  }
  
  // Apply site-wide discount if active
  $site_wide_discount_percent = cta_get_site_wide_discount_percentage();
  $site_wide_active = cta_is_site_wide_discount_active();
  if ($site_wide_active && $site_wide_discount_percent > 0) {
    // Calculate discounted price
    $discounted_price = cta_apply_site_wide_discount(floatval($original_price));
    // Only show site-wide discount if it's better than existing discount
    if (!$has_discount || $discounted_price < floatval($price)) {
      $has_discount = true;
      $discount_percent = $site_wide_discount_percent;
      $price = $discounted_price;
      $discount_type = 'site-wide';
      $site_wide_discount = cta_get_site_wide_discount();
      $discount_label = $site_wide_discount['label'] ?: 'Site-Wide Sale';
    }
  }
  $max_delegates = $course ? get_field('course_max_delegates', $course->ID) : '';
  $accreditation = $course ? get_field('course_accreditation', $course->ID) : '';
  $certificate = $course ? get_field('course_certificate', $course->ID) : '';
  $prerequisites = $course ? get_field('course_prerequisites', $course->ID) : '';
  $suitable_for = $course ? get_field('course_suitable_for', $course->ID) : '';
  $outcomes = $course ? cta_get_outcomes($course->ID) : []; // Uses helper to parse textarea
  
  // Get category from linked course
  $category_name = '';
  $category_slug = '';
  if ($course) {
    $terms = get_the_terms($course->ID, 'course_category');
    if ($terms && !is_wp_error($terms)) {
      $category_name = $terms[0]->name;
      $category_slug = $terms[0]->slug;
    }
  }
  
  // Format date - "Wednesday, 7 January 2026"
  $formatted_date = $event_date ? date('l, j F Y', strtotime($event_date)) : '';
?>

<main id="main-content">
  <!-- Event Hero Section -->
  <section class="group-hero-section" aria-labelledby="event-detail-heading">
    <div class="container">
      <!-- Breadcrumbs -->
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
          </li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="breadcrumb-link">Upcoming Courses</a>
          </li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <span class="breadcrumb-current" aria-current="page"><?php echo esc_html($course_title); ?></span>
          </li>
        </ol>
      </nav>

      <!-- Title -->
      <?php 
      $custom_h1 = get_field('event_seo_h1');
      if ($custom_h1) {
        $h1_text = $custom_h1;
      } else {
        $default_h1 = $course_title;
        $h1_pattern = cta_safe_get_field('seo_default_h1_pattern', 'option', '');
        if (!empty($h1_pattern)) {
          $h1_text = str_replace('{title}', $default_h1, $h1_pattern);
        } else {
          $h1_text = $default_h1;
        }
      }
      ?>
      <h1 id="event-detail-heading" class="hero-title"><?php echo esc_html($h1_text); ?></h1>
      
      <!-- Meta Info -->
      <div class="event-detail-hero-meta">
        <?php if ($formatted_date) : ?>
        <div class="event-detail-hero-meta-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          <span><?php echo esc_html($formatted_date); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($duration) : ?>
        <div class="event-detail-hero-meta-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          <span><?php echo esc_html($duration); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($location) : ?>
        <div class="event-detail-hero-meta-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
          <span><?php echo esc_html($location); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($spaces !== '' && $spaces > 0) : ?>
        <div class="event-detail-hero-meta-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
          <span><?php echo esc_html($spaces); ?> spots left</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Course Detail Content Section -->
  <section class="course-detail-content">
    <div class="container">
      <div class="course-detail-grid">
        
        <!-- Main Content Column -->
        <div class="course-detail-main">
          
          <?php 
          $section_heading = get_field('event_seo_section_heading');
          if (!$section_heading) {
            $section_heading = cta_safe_get_field('seo_default_section_heading', 'option', 'Event Overview');
          }
          ?>
          <h2 class="course-detail-section-heading"><?php echo esc_html($section_heading); ?></h2>
          
          <?php if ($outcomes && is_array($outcomes) && count($outcomes) > 0) : ?>
          <!-- What You'll Learn Accordion (opens by default) -->
          <div class="accordion" data-accordion-group="course-details">
            <button 
              type="button"
              class="accordion-trigger" 
              aria-expanded="true"
              aria-controls="learn-content"
              data-accordion="learn"
            >
              <h3 class="course-detail-accordion-title">What You'll Learn</h3>
              <svg class="accordion-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <div class="accordion-content" id="learn-content" aria-hidden="false">
              <div class="course-detail-learn-grid">
                <?php foreach ($outcomes as $outcome) : ?>
                <div class="course-detail-learn-item"><?php echo esc_html($outcome['outcome_text']); ?></div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($description) : ?>
          <!-- Description Accordion -->
          <div class="accordion" data-accordion-group="course-details">
            <button 
              type="button"
              class="accordion-trigger" 
              aria-expanded="<?php echo (!$outcomes || count($outcomes) === 0) ? 'true' : 'false'; ?>"
              aria-controls="description-content"
              data-accordion="description"
            >
              <h3 class="course-detail-accordion-title">Description</h3>
              <svg class="accordion-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <div class="accordion-content" id="description-content" aria-hidden="<?php echo (!$outcomes || count($outcomes) === 0) ? 'false' : 'true'; ?>">
              <div class="course-detail-description">
                <?php echo nl2br(esc_html($description)); ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($prerequisites) : ?>
          <!-- Requirements Accordion -->
          <div class="accordion" data-accordion-group="course-details">
            <button 
              type="button"
              class="accordion-trigger" 
              aria-expanded="false"
              aria-controls="requirements-content"
              data-accordion="requirements"
            >
              <h3 class="course-detail-accordion-title">Requirements</h3>
              <svg class="accordion-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <div class="accordion-content" id="requirements-content" aria-hidden="true">
              <div class="course-detail-text">
                <?php echo nl2br(esc_html($prerequisites)); ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($suitable_for) : ?>
          <!-- Who Should Attend Accordion -->
          <div class="accordion" data-accordion-group="course-details">
            <button 
              type="button"
              class="accordion-trigger" 
              aria-expanded="false"
              aria-controls="audience-content"
              data-accordion="audience"
            >
              <h3 class="course-detail-accordion-title">Who Should Attend</h3>
              <svg class="accordion-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <div class="accordion-content" id="audience-content" aria-hidden="true">
              <div class="course-detail-text">
                <?php echo nl2br(esc_html($suitable_for)); ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($certificate || $accreditation) : ?>
          <!-- Certification Accordion -->
          <div class="accordion" data-accordion-group="course-details">
            <button 
              type="button"
              class="accordion-trigger" 
              aria-expanded="false"
              aria-controls="certification-content"
              data-accordion="certification"
            >
              <h3 class="course-detail-accordion-title">Certification</h3>
              <svg class="accordion-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <div class="accordion-content" id="certification-content" aria-hidden="true">
              <div class="course-detail-certification">
                <?php if ($certificate) : ?>
                <p><?php echo esc_html($certificate); ?></p>
                <?php endif; ?>
                <?php if ($accreditation && strtolower(trim($accreditation)) !== 'none') : ?>
                <span class="course-detail-certification-badge"><?php echo esc_html($accreditation); ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Accessibility Support Accordion -->
          <div class="accordion" data-accordion-group="course-details">
            <button 
              type="button"
              class="accordion-trigger" 
              aria-expanded="false"
              aria-controls="accessibility-content"
              data-accordion="accessibility"
            >
              <h3 class="course-detail-accordion-title">Accessibility Support</h3>
              <svg class="accordion-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <div class="accordion-content" id="accessibility-content" aria-hidden="true">
              <p class="course-detail-text">
                Our training facilities are fully accessible. Please contact us if you have specific accessibility requirements.
              </p>
            </div>
          </div>

        </div>

        <!-- Sidebar -->
        <aside class="course-detail-sidebar">
          <div class="course-detail-booking-card">
            
            <!-- Price Display -->
            <?php if ($price) : ?>
            <div class="course-detail-price-wrapper">
              <?php if ($has_discount || $requires_code) : ?>
                <div style="margin-bottom: 8px;">
                  <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span class="badge badge-discount">Save <?php echo esc_html($discount_percent); ?>%</span>
                    <?php if ($discount_label) : ?>
                      <span class="badge badge-discount-subtle"><?php echo esc_html($discount_label); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <?php if ($requires_code && !empty($discount_code)) : ?>
                  <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 16px; border-radius: 4px; margin-bottom: 12px;">
                    <p style="margin: 0 0 8px 0; font-weight: 600; color: #856404; font-size: 14px;">
                      💳 Discount Code Required
                    </p>
                    <p style="margin: 0 0 12px 0; color: #856404; font-size: 13px; line-height: 1.5;">
                      Use code <strong style="font-size: 16px; letter-spacing: 1px; background: #ffe69c; padding: 4px 8px; border-radius: 3px;"><?php echo esc_html($discount_code); ?></strong> at checkout to get <?php echo esc_html($discount_percent); ?>% off.
                    </p>
                    <p style="margin: 0; color: #856404; font-size: 12px;">
                      Original price: <span style="text-decoration: line-through;">£<?php echo esc_html(number_format($original_price, 0)); ?></span> 
                      → Discounted price: <strong style="color: #dc3232;">£<?php echo esc_html(number_format(cta_apply_course_discount($course->ID, floatval($original_price)), 0)); ?></strong>
                    </p>
                  </div>
                <?php endif; ?>
                <div style="display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap; margin-bottom: 4px;">
                  <div style="text-decoration: line-through; color: #8c8f94; font-size: 18px; font-weight: 400;">
                    £<?php echo esc_html(number_format($original_price, 0)); ?>
                  </div>
                  <div class="course-detail-price" style="color: #dc3232; font-weight: 700;">
                    From £<?php echo esc_html(number_format($price, 0)); ?>
                  </div>
                </div>
              <?php else : ?>
                <div class="course-detail-price">From £<?php echo esc_html(number_format($price, 0)); ?></div>
              <?php endif; ?>
              <div class="course-detail-price-note">per person</div>
            </div>
            <?php endif; ?>

            <!-- Course Meta Information -->
            <ul class="course-detail-meta-list">
              <?php if ($formatted_date) : ?>
              <li class="course-detail-meta-item">
                <span class="course-detail-meta-label">Start Date</span>
                <span class="course-detail-meta-value"><?php echo esc_html($formatted_date); ?></span>
              </li>
              <?php endif; ?>
              <?php if ($spaces !== '' && $spaces > 0) : ?>
              <li class="course-detail-meta-item">
                <span class="course-detail-meta-label">Enrolled</span>
                <span class="course-detail-meta-value"><?php echo esc_html($spaces); ?> spots left</span>
              </li>
              <?php endif; ?>
              <?php if ($duration) : ?>
              <li class="course-detail-meta-item">
                <span class="course-detail-meta-label">Duration</span>
                <span class="course-detail-meta-value"><?php echo esc_html($duration); ?></span>
              </li>
              <?php endif; ?>
              <?php 
              // Get course level for qualification badge
              $level = $course ? get_field('course_level', $course->ID) : '';
              if ($level) : ?>
              <li class="course-detail-meta-item">
                <span class="course-detail-meta-label">Qualification</span>
                <span class="course-detail-meta-value"><span class="course-detail-badge"><?php echo esc_html($level); ?></span></span>
              </li>
              <?php endif; ?>
              <?php if ($category_name) : ?>
              <li class="course-detail-meta-item">
                <span class="course-detail-meta-label">Category</span>
                <span class="course-detail-meta-value"><?php echo esc_html($category_name); ?></span>
              </li>
              <?php endif; ?>
              <?php if ($location) : ?>
              <li class="course-detail-meta-item">
                <span class="course-detail-meta-label">Location</span>
                <span class="course-detail-meta-value"><?php echo esc_html($location); ?></span>
              </li>
              <?php endif; ?>
            </ul>

            <!-- Book Now Button -->
            <button 
              type="button"
              onclick="openBookingModal('<?php echo esc_js($course_title); ?>', '<?php echo esc_js(get_the_ID()); ?>', '<?php echo esc_js($formatted_date); ?>')"
              class="primary-cta-button"
              aria-label="Book this course"
            >
              Book Now
            </button>

            <!-- Phone Number -->
            <div class="course-detail-phone">
              <a href="<?php echo esc_url($contact['phone_link']); ?>" class="course-detail-phone-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                <span><?php echo esc_html($contact['phone']); ?></span>
              </a>
            </div>

          </div>
        </aside>

      </div>
    </div>
  </section>

  <!-- Related Courses Section -->
  <?php
  // Query related upcoming events in the same category
  $today = date('Y-m-d');
  $related_events = new WP_Query([
    'post_type' => 'course_event',
    'posts_per_page' => 3,
    'post__not_in' => [get_the_ID()],
    'meta_key' => 'event_date',
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'meta_query' => [
      [
        'key' => 'event_date',
        'value' => $today,
        'compare' => '>=',
        'type' => 'DATE',
      ],
    ],
  ]);
    
  if ($related_events->have_posts()) :
  ?>
  <section class="course-detail-related-section">
    <div class="container">
      <div class="course-detail-related-header">
        <h2 class="course-detail-related-title">Related Courses</h2>
      </div>
      <div id="related-courses">
        <?php while ($related_events->have_posts()) : $related_events->the_post(); 
          $rel_course = get_field('linked_course');
          $rel_event_date = get_field('event_date');
          $rel_location = get_field('event_location');
          $rel_event_price = get_field('event_price');
          $rel_duration = $rel_course ? get_field('course_duration', $rel_course->ID) : '';
          $rel_price = $rel_event_price ?: ($rel_course ? get_field('course_price', $rel_course->ID) : '');
          $rel_title = $rel_course ? $rel_course->post_title : get_the_title();
          
          // Get category
          $rel_category_name = '';
          $rel_category_class = '';
          if ($rel_course) {
            $rel_terms = get_the_terms($rel_course->ID, 'course_category');
            if ($rel_terms && !is_wp_error($rel_terms)) {
              $rel_category_name = $rel_terms[0]->name;
              $rel_category_class = 'event-card-' . sanitize_title($rel_terms[0]->slug);
            }
          }
          
          // Get image
          $rel_thumbnail_id = $rel_course ? get_post_thumbnail_id($rel_course->ID) : null;
        ?>
        <a href="<?php echo esc_url(get_permalink()); ?>" class="event-card <?php echo esc_attr($rel_category_class); ?>">
          <div class="event-card-image-wrapper">
            <?php if ($rel_thumbnail_id) : 
              $image_src = wp_get_attachment_image_src($rel_thumbnail_id, 'medium_large');
            ?>
            <img src="<?php echo esc_url($image_src[0]); ?>"
                 alt="<?php echo esc_attr($rel_title); ?>"
                 class="event-card-image"
                 loading="lazy"
                 width="400"
                 height="225">
            <?php endif; ?>
            <div class="event-card-pills">
              <?php if ($rel_category_name) : ?>
              <span class="event-card-pill event-card-pill-category"><?php echo esc_html($rel_category_name); ?></span>
              <?php endif; ?>
              <?php if ($rel_duration) : ?>
              <span class="event-card-pill event-card-pill-duration">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <?php echo esc_html($rel_duration); ?>
              </span>
              <?php endif; ?>
            </div>
          </div>
          <div class="event-card-content">
            <div class="event-card-meta-info">
              <?php if ($rel_event_date) : ?>
              <div class="event-card-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span><?php echo esc_html(date('l, j F Y', strtotime($rel_event_date))); ?></span>
              </div>
              <?php endif; ?>
            </div>
            <?php if ($rel_location) : ?>
            <div class="event-card-location">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <span><?php echo esc_html($rel_location); ?></span>
            </div>
            <?php endif; ?>
            <h3 class="event-card-title"><?php echo esc_html($rel_title); ?></h3>
            <div class="event-card-footer">
              <?php if ($rel_price) : ?>
              <div class="event-card-price">
                <span class="event-card-price-amount">From £<?php echo esc_html(number_format($rel_price, 0)); ?></span>
                <span class="event-card-price-label">per person</span>
              </div>
              <?php endif; ?>
              <div class="event-card-cta">
                <span>Book Now</span>
              </div>
            </div>
          </div>
        </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
      
      <!-- Looking for a Different Course CTA -->
      <div class="course-detail-related-cta">
        <h3>Looking for a Different Course?</h3>
        <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="btn btn-primary">
          View All Upcoming Courses
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14M12 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>
    </div>
  </section>
  <?php endif; ?>
</main>

<?php endwhile; ?>

<script>
(function() {
  'use strict';
  
  // Course detail accordion functionality
  const accordionTriggers = document.querySelectorAll('.course-detail-accordion-trigger');
  
  accordionTriggers.forEach(trigger => {
    trigger.addEventListener('click', function(e) {
      e.preventDefault();
      const isExpanded = this.getAttribute('aria-expanded') === 'true';
      const contentId = this.getAttribute('aria-controls');
      const content = document.getElementById(contentId);
      
      if (content) {
        // Toggle this accordion
        this.setAttribute('aria-expanded', !isExpanded);
        content.setAttribute('aria-hidden', isExpanded);
      }
    });
  });
})();
</script>

<?php get_footer(); ?>

