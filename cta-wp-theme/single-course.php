<?php
/**
 * Single Course Template
 * 
 * Matches the event-detail.html layout but without event-specific details
 *
 * @package CTA_Theme
 */

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
  
  $level = get_field('course_level');
  $duration = get_field('course_duration');
  $hours = get_field('course_hours');
  $trainer = get_field('course_trainer');
  $original_price = get_field('course_price');
  $price = $original_price;
  
  // Check for course-specific discount first
  $course_discount = cta_get_course_discount(get_the_ID());
  $course_discount_active = cta_is_course_discount_active(get_the_ID());
  $course_discount_percent = cta_get_course_discount_percentage(get_the_ID());
  $requires_code = $course_discount['requires_code'] && !empty($course_discount['discount_code']);
  
  // Apply course-specific discount if active and automatic (no code required)
  $has_discount = false;
  $discount_type = ''; // 'course', 'site-wide', or ''
  $discount_label = '';
  $discount_code = '';
  
  if ($course_discount_active && $course_discount_percent > 0 && $price) {
    if (!$requires_code) {
      // Automatic discount - apply it
      $has_discount = true;
      $discount_type = 'course';
      $price = cta_apply_course_discount(get_the_ID(), floatval($price));
      $discount_label = $course_discount['label'] ?: 'Special Offer';
    } else {
      // Code required - show discount info but don't apply price yet
      $discount_type = 'course-code';
      $discount_label = $course_discount['label'] ?: 'Special Offer';
      $discount_code = $course_discount['discount_code'];
    }
  }
  
  // Apply site-wide discount if active and no course discount, or if site-wide is better
  $site_wide_discount_percent = cta_get_site_wide_discount_percentage();
  $site_wide_active = cta_is_site_wide_discount_active();
  if ($site_wide_active && $site_wide_discount_percent > 0 && $price) {
    $site_wide_price = cta_apply_site_wide_discount(floatval($original_price));
    // Use site-wide if no course discount, or if site-wide is better
    if (!$has_discount || $site_wide_price < floatval($price)) {
      $has_discount = true;
      $discount_type = 'site-wide';
      $price = $site_wide_price;
      $site_wide_discount = cta_get_site_wide_discount();
      $discount_label = $site_wide_discount['label'] ?: 'Site-Wide Sale';
    }
  }
  
  $description = get_field('course_description');
  $suitable_for = get_field('course_suitable_for');
  $prerequisites = get_field('course_prerequisites');
  $outcomes = cta_get_outcomes(get_the_ID());
  
  $accreditation = get_field('course_accreditation');
  $certificate = get_field('course_certificate');
  
  $terms = get_the_terms(get_the_ID(), 'course_category');
  $category_name = $terms && !is_wp_error($terms) ? $terms[0]->name : '';
  $category_slug = $terms && !is_wp_error($terms) ? $terms[0]->slug : '';
?>

<main id="main-content">
  <!-- Course Hero Section -->
  <section class="group-hero-section" aria-labelledby="course-detail-heading">
    <div class="container">
      <!-- Breadcrumbs -->
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
          </li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="breadcrumb-link">Courses</a>
          </li>
          <?php if ($category_name) : ?>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(add_query_arg('category', $category_slug, get_post_type_archive_link('course'))); ?>" class="breadcrumb-link"><?php echo esc_html($category_name); ?></a>
          </li>
          <?php endif; ?>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <span class="breadcrumb-current" aria-current="page"><?php the_title(); ?></span>
          </li>
        </ol>
      </nav>

      <!-- Title -->
      <?php 
      $custom_h1 = get_field('course_seo_h1');
      if ($custom_h1) {
        $h1_text = $custom_h1;
      } else {
        $h1_pattern = cta_safe_get_field('seo_default_h1_pattern', 'option', '');
        if (!empty($h1_pattern)) {
          $h1_text = str_replace('{title}', get_the_title(), $h1_pattern);
        } else {
          $h1_text = get_the_title();
        }
      }
      ?>
      <h1 id="course-detail-heading" class="hero-title"><?php echo esc_html($h1_text); ?></h1>
      
      <!-- Meta Info -->
      <div class="event-detail-hero-meta">
        <?php if ($duration) : ?>
        <div class="event-detail-hero-meta-item">
          <i class="fas fa-clock" aria-hidden="true"></i>
          <span><?php echo esc_html($duration); ?><?php if ($hours) : ?> (<?php echo esc_html($hours); ?> hours)<?php endif; ?></span>
        </div>
        <?php endif; ?>
        <?php if ($level) : ?>
        <div class="event-detail-hero-meta-item">
          <i class="fas fa-layer-group" aria-hidden="true"></i>
          <span><?php echo esc_html($level); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($accreditation && strtolower(trim($accreditation)) !== 'none') : ?>
        <div class="event-detail-hero-meta-item">
          <i class="fas fa-award" aria-hidden="true"></i>
          <span><?php echo esc_html(cta_shorten_accreditation($accreditation)); ?></span>
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
          // Expanded Content Section (before accordions)
          $why_matters = get_field('course_why_matters');
          $covered_items = get_field('course_covered_items');
          $format_details = get_field('course_format_details');
          $key_features = get_field('course_key_features');
          $benefits = get_field('course_benefits');
          $after_note = get_field('course_after_note');
          
          if ($why_matters || $covered_items || $format_details || $key_features || $benefits) :
          ?>
          <div class="course-expanded-content">
            <?php if ($why_matters) : ?>
            <div class="course-why-matters-callout">
              <div class="course-callout-icon">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
              </div>
              <div class="course-callout-content">
                <h3>Why This Matters</h3>
                <?php echo wp_kses_post($why_matters); ?>
              </div>
            </div>
            <?php endif; ?>
            
            <?php if ($covered_items && is_array($covered_items)) : ?>
            <div class="course-covered-section">
              <h3>Key Topics</h3>
              <div class="course-covered-grid">
                <?php 
                // Limit to 6 items max to reduce density
                $display_items = array_slice($covered_items, 0, 6);
                foreach ($display_items as $item) : 
                ?>
                <div class="course-covered-item">
                  <h4><?php echo esc_html($item['title']); ?></h4>
                  <p><?php echo esc_html(wp_trim_words($item['description'], 20)); ?></p>
                </div>
                <?php endforeach; ?>
              </div>
              <?php if (count($covered_items) > 6) : ?>
              <p class="course-covered-more">
                <a href="#course-overview">View full curriculum below</a>
              </p>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($format_details) : ?>
            <div class="course-format-details">
              <?php echo wp_kses_post(wp_trim_words($format_details, 100)); ?>
              <?php if (str_word_count(strip_tags($format_details)) > 100) : ?>
              <p><a href="#course-overview">Read more about course format</a></p>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($key_features && is_array($key_features)) : ?>
            <div class="course-different-section">
              <h3>Training Highlights</h3>
              <div class="course-features-list">
                <?php 
                // Limit to 3 features max
                $display_features = array_slice($key_features, 0, 3);
                foreach ($display_features as $feature) : 
                ?>
                <div class="course-feature-item">
                  <?php if (!empty($feature['icon'])) : ?>
                  <div class="course-feature-icon">
                    <i class="<?php echo esc_attr($feature['icon']); ?>" aria-hidden="true"></i>
                  </div>
                  <?php endif; ?>
                  <div class="course-feature-content">
                    <h4><?php echo esc_html($feature['title']); ?></h4>
                    <p><?php echo esc_html(wp_trim_words($feature['description'], 25)); ?></p>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          
          <?php 
          $section_heading = get_field('course_seo_section_heading');
          if (!$section_heading) {
            $section_heading = cta_safe_get_field('seo_default_section_heading', 'option', 'Course Overview');
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
              // TODO: Manually update ACF content to use bullet format for better scanability
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
                  <div class="course-detail-certification-badge"><?php echo esc_html(cta_shorten_accreditation($accreditation)); ?></div>
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
          
          <!-- Mandatory Badge (separate card, outside booking card) -->
          <?php 
          $is_mandatory = get_field('course_is_mandatory');
          $mandatory_note = get_field('course_mandatory_note');
          if ($is_mandatory) : 
          ?>
          <div class="course-mandatory-card">
            <div class="course-mandatory-icon">
              <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            </div>
            <div class="course-mandatory-content">
              <h3>Mandatory Training</h3>
              <p><?php echo $mandatory_note ? esc_html($mandatory_note) : 'This course is mandatory for CQC compliance. Ensure all staff complete this training.'; ?></p>
            </div>
          </div>
          <?php endif; ?>
          
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
              <?php if ($duration) : ?>
              <li class="course-detail-meta-item">
                <span class="course-detail-meta-label">Duration</span>
                <span class="course-detail-meta-value"><?php echo esc_html($duration); ?></span>
              </li>
              <?php endif; ?>
              <?php if ($level) : ?>
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
            </ul>

            <!-- Next Available Dates -->
            <?php
            $today = date('Y-m-d');
            $upcoming_events = new WP_Query([
                'post_type' => 'course_event',
                'posts_per_page' => 3,
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
                    [
                        'key' => 'linked_course',
                        'value' => get_the_ID(),
                        'compare' => '=',
                    ],
                ],
            ]);
            
            if ($upcoming_events->have_posts()) :
            ?>
            <div class="course-next-dates">
              <h4>Next Available Dates</h4>
              <ul class="course-dates-list">
                <?php while ($upcoming_events->have_posts()) : $upcoming_events->the_post(); 
                  $event_date = get_field('event_date');
                  $event_location = get_field('event_location');
                  $spaces_available = get_field('spaces_available');
                  $linked_course = get_field('linked_course');
                  $event_id = get_the_ID();
                  $course_title = $linked_course ? $linked_course->post_title : get_the_title();
                  $course_id = $linked_course ? $linked_course->ID : get_the_ID();
                  $formatted_event_date = date('j M Y', strtotime($event_date));
                ?>
                <li class="course-date-item">
                  <button 
                    type="button"
                    onclick="openBookingModal('<?php echo esc_js($course_title); ?>', '<?php echo esc_js($event_id); ?>', '<?php echo esc_js($formatted_event_date); ?>')"
                    class="course-date-item-button"
                    aria-label="Book <?php echo esc_attr($course_title); ?> on <?php echo esc_attr($formatted_event_date); ?>"
                  >
                    <div class="course-date-info">
                      <div class="course-date-date"><?php echo esc_html($formatted_event_date); ?></div>
                      <?php if ($event_location) : ?>
                      <div class="course-date-location"><?php echo esc_html($event_location); ?></div>
                      <?php endif; ?>
                      <?php if ($spaces_available !== '' && $spaces_available <= 5) : ?>
                      <div class="course-date-spaces"><?php echo esc_html($spaces_available); ?> spaces left</div>
                      <?php endif; ?>
                    </div>
                    <span class="course-date-book">Book</span>
                  </button>
                </li>
                <?php endwhile; ?>
              </ul>
              <p class="course-dates-fallback">
                Can't see a date that works for you? <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" style="font-weight: 600; text-decoration: underline;">Get in touch</a> to discuss alternative dates.
              </p>
            </div>
            <?php 
            wp_reset_postdata();
            else :
            ?>
            <div class="course-next-dates">
              <h4>Next Available Dates</h4>
              <p class="course-dates-no-dates">
                No upcoming dates scheduled at the moment. Please get in touch to discuss your training needs.
              </p>
              <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" class="btn btn-secondary">
                Enquire About This Course
              </a>
            </div>
            <?php endif; ?>

            <!-- Enquire Button -->
            <button 
              type="button"
              onclick="openBookingModal('<?php echo esc_js(get_the_title()); ?>', '<?php echo esc_js(get_the_ID()); ?>')"
              class="primary-cta-button primary-cta-button-large"
              aria-label="Request more information about this course"
            >
              Request More Information
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

  <!-- Course FAQs Section -->
  <?php
  $course_faqs = get_field('course_faqs');
  if (!empty($course_faqs) && is_array($course_faqs)) :
  ?>
  <section id="faq" class="content-section" aria-labelledby="course-faq-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="course-faq-heading" class="section-title">Frequently Asked Questions</h2>
      </div>
      
      <div class="faqs-content-wrapper">
        <div class="group-faq-list">
          <?php foreach ($course_faqs as $index => $faq) : 
            if (!is_array($faq) || !isset($faq['question']) || !isset($faq['answer'])) {
              continue;
            }
          ?>
          <div class="accordion faq-item" data-accordion-group="course-faqs">
            <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="course-faq-<?php echo (int) $index; ?>">
              <span><?php echo esc_html($faq['question']); ?></span>
              <span class="accordion-icon" aria-hidden="true">
                <i class="fas fa-plus" aria-hidden="true"></i>
                <i class="fas fa-minus" aria-hidden="true"></i>
              </span>
            </button>
            <div id="course-faq-<?php echo (int) $index; ?>" class="accordion-content" role="region" aria-hidden="true">
              <?php echo wpautop(wp_kses_post($faq['answer'])); ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Testimonials Section -->
  <?php 
  $selected_review_ids = get_field('course_selected_reviews');
  if (!empty($selected_review_ids)) : 
    $all_reviews = get_option('cta_all_reviews', []);
    $reviews_to_show = [];
    foreach ($selected_review_ids as $review_id) {
      if (isset($all_reviews[$review_id])) {
        $reviews_to_show[] = $all_reviews[$review_id];
      }
    }
    
    if (!empty($reviews_to_show)) :
  ?>
  <section class="course-testimonials-section content-section" aria-labelledby="course-testimonials-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="course-testimonials-heading" class="section-title">What Our Students Say</h2>
      </div>
      <div class="testimonials-grid">
        <?php foreach ($reviews_to_show as $review) : ?>
        <article class="testimonial-card">
          <div class="testimonial-quote-wrapper">
            <p class="testimonial-quote"><?php echo esc_html($review['quote']); ?></p>
          </div>
          <div class="testimonial-author">
            <div class="testimonial-avatar">
              <i class="fas fa-user" aria-hidden="true"></i>
            </div>
            <div class="testimonial-info">
              <div class="testimonial-name"><?php echo esc_html($review['author']); ?></div>
              <div class="testimonial-company"><?php echo esc_html($review['date']); ?></div>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php 
    endif;
  endif; 
  ?>

  <!-- CTA Section Before Related Courses -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-content">
        <h2 class="cta-title">Ready to Get Started?</h2>
        <p class="cta-description">Join hundreds of care professionals advancing their skills with this course. CPD-accredited training, expert-led sessions, and certificate upon completion.</p>
        <div class="cta-buttons">
          <button 
            type="button"
            onclick="openBookingModal('<?php echo esc_js(get_the_title()); ?>', '<?php echo esc_js(get_the_ID()); ?>')"
            class="btn btn-primary"
            aria-label="Enquire about this course"
          >
            Request More Information
          </button>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('group-training')) ?: get_permalink(get_page_by_path('contact')) . '?type=group-training'); ?>" class="btn btn-secondary">
            Book for Your Team
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Related Courses Section -->
  <?php
  wp_reset_postdata();
  rewind_posts();
  the_post();
  
  if ($category_slug) :
    $related = new WP_Query([
      'post_type' => 'course',
      'posts_per_page' => 3,
      'post__not_in' => [get_the_ID()],
      'tax_query' => [
        [
          'taxonomy' => 'course_category',
          'field' => 'slug',
          'terms' => $category_slug,
        ],
      ],
    ]);
    
    if ($related->have_posts()) :
  ?>
  <section class="course-detail-related-section content-section">
    <div class="container">
      <div class="course-detail-related-header">
        <h2 class="course-detail-related-title">Related Courses</h2>
      </div>
      <div id="related-courses">
        <?php while ($related->have_posts()) : $related->the_post(); 
          $rel_price = get_field('course_price');
          $rel_duration = get_field('course_duration');
          $rel_terms = get_the_terms(get_the_ID(), 'course_category');
          $rel_category_name = $rel_terms && !is_wp_error($rel_terms) ? $rel_terms[0]->name : '';
          $rel_category_class = $rel_terms && !is_wp_error($rel_terms) ? 'event-card-' . sanitize_title($rel_terms[0]->slug) : '';
          $rel_thumbnail_id = get_post_thumbnail_id();
        ?>
        <a href="<?php echo esc_url(get_permalink()); ?>" class="event-card <?php echo esc_attr($rel_category_class); ?>">
          <div class="event-card-image-wrapper">
            <?php if ($rel_thumbnail_id) : 
              $image_src = wp_get_attachment_image_src($rel_thumbnail_id, 'medium_large');
              $image_srcset = wp_get_attachment_image_srcset($rel_thumbnail_id, 'medium_large');
            ?>
            <img srcset="<?php echo esc_attr($image_srcset); ?>"
                 src="<?php echo esc_url($image_src[0]); ?>"
                 sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                 alt="<?php echo esc_attr(get_the_title()); ?>"
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
            <h3 class="event-card-title"><?php the_title(); ?></h3>
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
      
      <!-- Looking for a Different Course? CTA -->
      <div class="course-detail-related-cta">
        <h3>Looking for a Different Course?</h3>
        <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-primary">
          View All Courses
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14M12 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>
    </div>
  </section>
  <?php endif; endif; ?>
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
  
  // Toggle course content (Show more/less) - make globally accessible
  window.toggleCourseContent = function() {
    const moreWrapper = document.getElementById('course-content-more');
    const toggleButton = document.getElementById('course-content-toggle');
    
    if (!moreWrapper || !toggleButton) {
      console.error('Course content toggle: Required elements not found');
      return;
    }
    
    const showMoreText = toggleButton.querySelector('.show-more-text');
    const showLessText = toggleButton.querySelector('.show-less-text');
    const toggleIcon = toggleButton.querySelector('.fa-chevron-down');
    
    if (moreWrapper && toggleButton) {
      const isHidden = moreWrapper.style.display === 'none' || !moreWrapper.style.display;
      
      if (isHidden) {
        // Check content height before animating
        // Temporarily show to measure
        moreWrapper.style.display = 'block';
        moreWrapper.style.visibility = 'hidden';
        const contentHeight = moreWrapper.offsetHeight;
        moreWrapper.style.display = 'none';
        moreWrapper.style.visibility = 'visible';
        
        // Skip animation for very long content (>1000px) to prevent jankiness
        if (contentHeight > 1000) {
          moreWrapper.style.display = 'block';
          if (showMoreText) showMoreText.style.display = 'none';
          if (showLessText) showLessText.style.display = 'inline';
          if (toggleIcon) toggleIcon.style.transform = 'rotate(180deg)';
          // Smooth scroll to toggle button
          toggleButton.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
          // Animate for shorter content
          moreWrapper.style.display = 'block';
          moreWrapper.style.maxHeight = '0';
          moreWrapper.style.overflow = 'hidden';
          moreWrapper.style.transition = 'max-height 0.3s ease-out';
          
          // Force reflow
          void moreWrapper.offsetHeight;
          
          moreWrapper.style.maxHeight = contentHeight + 'px';
          if (showMoreText) showMoreText.style.display = 'none';
          if (showLessText) showLessText.style.display = 'inline';
          if (toggleIcon) toggleIcon.style.transform = 'rotate(180deg)';
          
          // Clean up after animation
          setTimeout(() => {
            moreWrapper.style.maxHeight = '';
            moreWrapper.style.overflow = '';
            moreWrapper.style.transition = '';
          }, 300);
          
          // Smooth scroll to toggle button
          setTimeout(() => {
            toggleButton.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }, 150);
        }
      } else {
        // Collapse
        const currentHeight = moreWrapper.offsetHeight;
        moreWrapper.style.maxHeight = currentHeight + 'px';
        moreWrapper.style.overflow = 'hidden';
        moreWrapper.style.transition = 'max-height 0.3s ease-out';
        
        // Force reflow
        void moreWrapper.offsetHeight;
        
        moreWrapper.style.maxHeight = '0';
          if (showMoreText) showMoreText.style.display = 'inline';
          if (showLessText) showLessText.style.display = 'none';
          if (toggleIcon) toggleIcon.style.transform = 'rotate(0deg)';
        
        // Clean up after animation
        setTimeout(() => {
          moreWrapper.style.display = 'none';
          moreWrapper.style.maxHeight = '';
          moreWrapper.style.overflow = '';
          moreWrapper.style.transition = '';
        }, 300);
      }
    }
  }
})();
</script>

<?php get_footer(); ?>
