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

/**
 * Shorten accreditation text for display
 */
function cta_shorten_accreditation($accreditation) {
  if (empty($accreditation)) {
    return $accreditation;
  }
  // Shorten "Skills for Health UK Core Skills Training Framework" to "Skills For Health UK"
  $accreditation = str_ireplace('Skills for Health UK Core Skills Training Framework', 'Skills For Health UK', $accreditation);
  return $accreditation;
}

/**
 * Shorten accreditation text for "What's Included" section
 */
function cta_shorten_accreditation_for_includes($accreditation) {
  if (empty($accreditation)) {
    return $accreditation;
  }
  // Shorten "Skills for Health UK Core Skills Training Framework" to "Skills for Health UK Core Framework Accreditation"
  $accreditation = str_ireplace('Skills for Health UK Core Skills Training Framework', 'Skills for Health UK Core Framework', $accreditation);
  return $accreditation;
}

while (have_posts()) : the_post();
  
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
  <section class="course-detail-content content-section">
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
          <h2 class="course-detail-section-heading" id="course-overview"><?php echo esc_html($section_heading); ?></h2>
          
          <?php if ($outcomes && is_array($outcomes) && count($outcomes) > 0) : ?>
          <!-- Course Content - EXPANDED (no accordion) -->
          <div class="course-detail-learn-section">
            <h3 class="course-detail-subheading">Course Content</h3>
            <div class="course-detail-learn-grid" id="course-content-list">
              <?php 
              // Show first 6-8 items, then progressive disclosure
              $initial_count = min(8, count($outcomes));
              $display_items = array_slice($outcomes, 0, $initial_count);
              $has_more = count($outcomes) > $initial_count;
              
              foreach ($display_items as $outcome) : 
              ?>
              <div class="course-detail-learn-item"><?php echo esc_html($outcome['outcome_text']); ?></div>
              <?php endforeach; ?>
              
              <?php if ($has_more) : ?>
              <div class="course-detail-learn-more-wrapper" id="course-content-more" style="display: none;">
                <?php 
                $remaining_items = array_slice($outcomes, $initial_count);
                foreach ($remaining_items as $outcome) : 
                ?>
                <div class="course-detail-learn-item"><?php echo esc_html($outcome['outcome_text']); ?></div>
                <?php endforeach; ?>
              </div>
              <button type="button" class="course-content-show-more" id="course-content-toggle" onclick="toggleCourseContent()">
                <span class="show-more-text">Show All <?php echo count($outcomes); ?> Topics</span>
                <span class="show-less-text" style="display: none;">Show Less</span>
                <i class="fas fa-chevron-down toggle-icon" aria-hidden="true"></i>
              </button>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($description) : ?>
          <!-- Description - EXPANDED (no accordion) -->
          <div class="course-detail-description-section">
            <h3 class="course-detail-subheading">Course Description</h3>
            <div class="course-detail-description">
              <?php echo wpautop(wp_kses_post($description)); ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($suitable_for) : ?>
          <!-- Who Should Attend - EXPANDED (no accordion) -->
          <div class="course-detail-audience-section">
            <h3 class="course-detail-subheading">Who Should Attend</h3>
            <?php 
            // Check if content already has list markup
            $has_list_markup = strpos($suitable_for, '<ul>') !== false || strpos($suitable_for, '<li>') !== false;
            
            if ($has_list_markup) {
              // Display as-is if already formatted as a list
              echo '<div class="course-detail-text">' . wp_kses_post($suitable_for) . '</div>';
            } else {
              // Wrap in div with styling for paragraph format
              echo '<div class="course-detail-text course-audience-text">' . wpautop(wp_kses_post($suitable_for)) . '</div>';
            }
            ?>
          </div>
          <?php endif; ?>

          <?php if ($prerequisites) : ?>
          <!-- Requirements - EXPANDED (no accordion) -->
          <div class="course-detail-requirements-section">
            <h3 class="course-detail-subheading">Requirements</h3>
            <div class="course-detail-text">
              <?php 
              // Check if prerequisites are minimal (like "No prerequisites required")
              $prereq_text = strip_tags($prerequisites);
              $is_minimal = strlen($prereq_text) < 100 || stripos($prereq_text, 'no prerequisite') !== false || stripos($prereq_text, 'none required') !== false;
              
              if ($is_minimal) {
                // For minimal requirements, show as a highlighted summary
                echo '<div class="course-requirements-summary">' . wp_kses_post($prerequisites) . '</div>';
              } else {
                echo wpautop(wp_kses_post($prerequisites));
              }
              ?>
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
              <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
            </button>
            <div class="accordion-content" id="certification-content" aria-hidden="true">
              <div class="course-detail-certification">
                <?php if ($certificate) : ?>
                <div class="course-detail-certification-item">
                  <div class="course-detail-certification-label">Certificate</div>
                  <div class="course-detail-certification-value"><?php echo esc_html($certificate); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($accreditation && strtolower(trim($accreditation)) !== 'none') : ?>
                <div class="course-detail-certification-item">
                  <div class="course-detail-certification-label">Accreditation</div>
                  <div class="course-detail-certification-badge"><?php echo esc_html($accreditation); ?></div>
                </div>
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
              <i class="fas fa-chevron-down accordion-icon" aria-hidden="true"></i>
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
            <?php
              $discount_percent = ($course_discount_active ? $course_discount_percent : $site_wide_discount_percent);
              $show_discount_ui = ($has_discount || $discount_type === 'course-code');
            ?>

            <?php if ($show_discount_ui) : ?>
              <div class="course-detail-discount-badges" aria-label="Offer details">
                <span class="badge badge-discount">Save <?php echo esc_html($discount_percent); ?>%</span>
                <span class="badge badge-critical"><?php echo esc_html($discount_label); ?></span>
              </div>
            <?php endif; ?>

            <?php if ($discount_type === 'course-code') : ?>
              <div class="course-detail-discount-callout" role="note" aria-label="Discount code required">
                <p class="course-detail-discount-callout-title">Discount code required</p>
                <p class="course-detail-discount-callout-body">
                  Use code <code class="course-detail-discount-code"><?php echo esc_html($discount_code); ?></code> at checkout to get <?php echo esc_html($discount_percent); ?>% off.
                </p>
              </div>
            <?php endif; ?>

            <div class="course-detail-price-wrapper course-detail-price-prominent">
              <?php if ($has_discount) : ?>
                <div class="course-detail-price-line">
                  <span class="course-detail-price-original" aria-label="Original price">£<?php echo esc_html(number_format($original_price, 0)); ?></span>
                  <div class="course-detail-price-container">
                    <span class="course-detail-price is-discounted">From £<?php echo esc_html(number_format($price, 0)); ?></span>
                    <span class="course-detail-price-unit">per person</span>
                  </div>
                </div>
              <?php else : ?>
                <div class="course-detail-price-container">
                  <span class="course-detail-price">From £<?php echo esc_html(number_format($price, 0)); ?></span>
                  <span class="course-detail-price-unit">per person</span>
                </div>
              <?php endif; ?>
              <div class="course-detail-price-includes">
                <span class="course-detail-price-includes-label">Includes:</span>
                <ul class="course-detail-price-includes-list">
                  <?php
                  // Build includes array dynamically based on available fields
                  $includes = [];
                  
                  if ($certificate) {
                    $includes[] = 'Certificate';
                  }
                  
                  if ($accreditation && strtolower(trim($accreditation)) !== 'none') {
                    $includes[] = esc_html(cta_shorten_accreditation_for_includes($accreditation)) . ' Accreditation';
                  }
                  
                  // Always include materials
                  $includes[] = 'Course materials';
                  
                  foreach ($includes as $include) {
                    echo '<li>' . esc_html($include) . '</li>';
                  }
                  ?>
                </ul>
              </div>
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
              <?php if ($start_time && $end_time) : ?>
              <li class="course-detail-meta-item">
                <span class="course-detail-meta-label">Times</span>
                <span class="course-detail-meta-value"><?php echo esc_html($start_time); ?> - <?php echo esc_html($end_time); ?></span>
              </li>
              <?php elseif ($start_time) : ?>
              <li class="course-detail-meta-item">
                <span class="course-detail-meta-label">Start Time</span>
                <span class="course-detail-meta-value"><?php echo esc_html($start_time); ?></span>
              </li>
              <?php endif; ?>
              <?php if ($spaces !== '' && $spaces > 0) : ?>
              <li class="course-detail-meta-item">
                <span class="course-detail-meta-label">Spaces</span>
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
              class="primary-cta-button primary-cta-button-large"
              aria-label="Book this course"
            >
              Book Now
            </button>

            <!-- Phone Number -->
            <div class="course-detail-phone">
              <a href="<?php echo esc_url($contact['phone_link']); ?>" class="course-detail-phone-link">
                <i class="fas fa-phone" aria-hidden="true"></i>
                <span><?php echo esc_html($contact['phone']); ?></span>
              </a>
              <p class="course-detail-phone-hours">Phone lines open: Mon-Fri 9am-5pm</p>
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
                <span aria-hidden="true">→</span>
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
function toggleCourseContent() {
  const moreWrapper = document.getElementById('course-content-more');
  const toggle = document.getElementById('course-content-toggle');
  const showMore = toggle.querySelector('.show-more-text');
  const showLess = toggle.querySelector('.show-less-text');
  const icon = toggle.querySelector('.toggle-icon');
  
  if (moreWrapper.style.display === 'none') {
    moreWrapper.style.display = 'grid';
    showMore.style.display = 'none';
    showLess.style.display = 'inline';
    icon.style.transform = 'rotate(180deg)';
  } else {
    moreWrapper.style.display = 'none';
    showMore.style.display = 'inline';
    showLess.style.display = 'none';
    icon.style.transform = 'rotate(0deg)';
  }
}
</script>

<?php get_footer(); ?>

