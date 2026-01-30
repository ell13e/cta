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

while (have_posts()) : the_post();
  
  // Auto-populate SEO Meta Tags
  $course_title = get_the_title();
  $course_excerpt = get_the_excerpt();
  $course_description = $course_excerpt ? wp_trim_words(strip_tags($course_excerpt), 25) : wp_trim_words(strip_tags(get_the_content()), 25);
  $meta_title = $course_title . ' | CQC-Compliant Care Training | CTA';
  $meta_description = $course_description ? $course_description : 'CQC-compliant ' . $course_title . ' training for care providers. CPD-accredited course with instant certification.';
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
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          <span><?php echo esc_html($duration); ?><?php if ($hours) : ?> (<?php echo esc_html($hours); ?> hours)<?php endif; ?></span>
        </div>
        <?php endif; ?>
        <?php if ($level) : ?>
        <div class="event-detail-hero-meta-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
          </svg>
          <span><?php echo esc_html($level); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($accreditation && strtolower(trim($accreditation)) !== 'none') : ?>
        <div class="event-detail-hero-meta-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="8" r="7"></circle>
            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
          </svg>
          <span><?php echo esc_html($accreditation); ?></span>
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
          $section_heading = get_field('course_seo_section_heading');
          if (!$section_heading) {
            $section_heading = cta_safe_get_field('seo_default_section_heading', 'option', 'Course Overview');
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

            <div class="course-detail-price-wrapper">
              <?php if ($has_discount) : ?>
                <div class="course-detail-price-line">
                  <span class="course-detail-price-original" aria-label="Original price">£<?php echo esc_html(number_format($original_price, 0)); ?></span>
                  <div class="course-detail-price is-discounted">From £<?php echo esc_html(number_format($price, 0)); ?></div>
                </div>
              <?php else : ?>
                <div class="course-detail-price">From £<?php echo esc_html(number_format($price, 0)); ?></div>
              <?php endif; ?>
              <div class="course-detail-price-note">per person</div>
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

            <!-- Book Now Button -->
            <button 
              type="button"
              onclick="openBookingModal('<?php echo esc_js(get_the_title()); ?>', '<?php echo esc_js(get_the_ID()); ?>')"
              class="primary-cta-button"
              aria-label="Enquire about this course"
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
  <section class="course-detail-related-section">
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
})();
</script>

<?php get_footer(); ?>
