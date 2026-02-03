<?php
/**
 * Template Name: Location Page
 * 
 * Location-specific landing page template for SEO-optimized local pages
 *
 * @package CTA_Theme
 */

get_header();

// Get location data
$location_data = cta_get_current_location_data();

// Fallback to page slug if no location data found
if (!$location_data && $post) {
    $page_slug = $post->post_name;
    $location_data = cta_get_location_data($page_slug);
}

// If still no location data, use default (Maidstone)
if (!$location_data) {
    $location_data = cta_get_location_data('maidstone');
}

$contact = cta_get_contact_info();

// Get ACF fields with fallbacks (all stored per-page)
$post_id = $post ? $post->ID : 0;
$hero_title = function_exists('get_field') ? get_field('hero_title', $post_id) : '';
$hero_subtitle = function_exists('get_field') ? get_field('hero_subtitle', $post_id) : '';
$page_sections = function_exists('get_field') ? (get_field('page_content_sections', $post_id) ?: []) : [];
$testimonials = function_exists('get_field') ? (get_field('testimonials', $post_id) ?: []) : [];
$faqs = function_exists('get_field') ? (get_field('faqs', $post_id) ?: []) : [];
$section_testimonials_title = function_exists('get_field') ? get_field('section_testimonials_title', $post_id) : '';
$section_faq_title = function_exists('get_field') ? get_field('section_faq_title', $post_id) : '';

// Default content if ACF fields not set
if (empty($hero_title)) {
    $hero_title = 'Care Training Courses in ' . $location_data['display_name'];
}
if (empty($hero_subtitle)) {
    $hero_subtitle = $location_data['description'];
}
if (empty($section_testimonials_title)) {
    $section_testimonials_title = 'What People Say';
}
if (empty($section_faq_title)) {
    $section_faq_title = 'Frequently Asked Questions';
}

// Get courses for this location
$location_courses = cta_get_location_courses($location_data['slug'], 6);
$location_events = cta_get_location_events($location_data['slug'], 6);

// Generate location-specific schema
$location_schema = cta_get_location_schema($location_data);
?>

<?php if (!empty($location_schema)) : ?>
<script type="application/ld+json">
<?php echo json_encode($location_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>
<?php endif; ?>

<main id="main-content" class="site-main">
  <!-- Hero Section -->
  <section class="group-hero-section" aria-labelledby="location-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><span class="breadcrumb-current" aria-current="page"><?php echo esc_html($location_data['display_name']); ?></span></li>
        </ol>
      </nav>
      <h1 id="location-heading" class="hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
      <div class="group-hero-cta">
        <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="btn btn-primary group-hero-btn-primary">View Upcoming Courses</a>
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" class="btn btn-secondary group-hero-btn-secondary">Contact Us</a>
      </div>
    </div>
  </section>

  <!-- Introduction / Custom Content -->
  <section class="group-how-it-works-section" aria-labelledby="location-intro-heading">
    <div class="container">
      <div class="group-how-it-works-header">
        <h2 id="location-intro-heading" class="section-title">Professional Care Training in <?php echo esc_html($location_data['name']); ?></h2>
      </div>
      <div>
        <?php if (!empty($page_sections) && is_array($page_sections)) : ?>
          <?php foreach ($page_sections as $section) :
            $heading = is_array($section) ? ($section['section_heading'] ?? '') : '';
            $content = is_array($section) ? ($section['section_content'] ?? '') : '';
            if (empty($heading) && empty($content)) continue;
          ?>
            <section class="content-section" aria-label="<?php echo esc_attr(wp_strip_all_tags((string) $heading)); ?>" style="margin: 0 0 28px 0;">
              <?php if (!empty($heading)) : ?>
                <h3 class="section-title" style="font-size: 1.5rem;"><?php echo esc_html($heading); ?></h3>
              <?php endif; ?>
              <?php if (!empty($content)) : 
                $list_style = is_array($section) ? ($section['section_list_style'] ?? 'default') : 'default';
                if ($list_style === 'two-column-gold') {
                  // Apply class to all ul elements in content
                  $content = preg_replace('/<ul\b([^>]*)>/i', '<ul class="list-two-column-gold"$1>', $content);
                }
              ?>
                <div class="section-content"><?php echo wp_kses_post($content); ?></div>
              <?php endif; ?>
            </section>
          <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($post && !empty($post->post_content)) : ?>
          <div>
            <?php echo wp_kses_post(wpautop($post->post_content)); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Testimonials (optional, per page) -->
  <?php if (!empty($testimonials) && is_array($testimonials)) : ?>
  <section class="group-testimonials-section" aria-labelledby="location-testimonials-heading">
    <div class="container">
      <div class="group-testimonials-header">
        <p class="group-testimonials-eyebrow">What People Say</p>
        <h2 id="location-testimonials-heading" class="section-title"><?php echo esc_html($section_testimonials_title); ?></h2>
      </div>
      <div class="group-testimonials-grid">
        <?php foreach ($testimonials as $testimonial) :
          $quote = is_array($testimonial) ? ($testimonial['quote'] ?? '') : '';
          $author = is_array($testimonial) ? ($testimonial['author'] ?? '') : '';
          $icon = is_array($testimonial) ? ($testimonial['icon'] ?? 'fas fa-user') : 'fas fa-user';
          if (empty($quote) || empty($author)) continue;
        ?>
        <blockquote class="group-testimonial-card">
          <span class="group-testimonial-quote-mark" aria-hidden="true">"</span>
          <p class="group-testimonial-quote"><?php echo esc_html($quote); ?></p>
          <footer class="group-testimonial-footer">
            <div class="group-testimonial-avatar">
              <i class="<?php echo esc_attr($icon ?: 'fas fa-user'); ?>" aria-hidden="true"></i>
            </div>
            <cite class="group-testimonial-author"><?php echo esc_html($author); ?></cite>
          </footer>
        </blockquote>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- FAQs (optional, per page) -->
  <?php if (!empty($faqs) && is_array($faqs)) : ?>
  <section class="group-how-it-works-section" aria-labelledby="location-faq-heading">
    <div class="container">
      <div class="group-how-it-works-header">
        <h2 id="location-faq-heading" class="section-title"><?php echo esc_html($section_faq_title); ?></h2>
      </div>
      <div style="max-width: 900px; margin: 0 auto;">
        <?php foreach ($faqs as $faq) :
          $q = is_array($faq) ? ($faq['question'] ?? '') : '';
          $a = is_array($faq) ? ($faq['answer'] ?? '') : '';
          if (empty($q) || empty($a)) continue;
        ?>
          <details class="faq-item" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #e0e0e0; border-radius: 4px;">
            <summary style="font-weight: 600; cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center;">
              <span><?php echo esc_html($q); ?></span>
              <span aria-hidden="true">▾</span>
            </summary>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
              <p><?php echo esc_html($a); ?></p>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Featured Courses Section -->
  <?php if ($location_courses->have_posts()) : ?>
  <section class="courses-listing-section" aria-labelledby="location-courses-heading">
    <div class="container">
      <div class="courses-listing-header">
        <h2 id="location-courses-heading" class="section-title">Popular Training Courses in <?php echo esc_html($location_data['name']); ?></h2>
        <p class="section-subtitle">CQC-compliant, CPD-accredited courses available in <?php echo esc_html($location_data['display_name']); ?></p>
      </div>
      
      <div class="courses-grid">
        <?php while ($location_courses->have_posts()) : $location_courses->the_post(); 
          $duration = function_exists('get_field') ? get_field('course_duration') : '';
          $price = function_exists('get_field') ? get_field('course_price') : '';
          $accreditation = function_exists('get_field') ? get_field('course_accreditation') : '';
          $level = function_exists('get_field') ? get_field('course_level') : '';
          // Use limiting function to get max 2 categories
          $terms = function_exists('cta_get_course_category_terms') ? cta_get_course_category_terms(get_the_ID()) : get_the_terms(get_the_ID(), 'course_category');
          $primary_term = $terms && !is_wp_error($terms) && !empty($terms) ? $terms[0] : null;
          $secondary_term = $terms && !is_wp_error($terms) && count($terms) >= 2 ? $terms[1] : null;
          $category_slug = $primary_term ? $primary_term->slug : '';
          
          // Badge colors, icons, and short names (matching archive-course.php)
          $category_badge_colors = [
            'core-care-skills' => 'course-badge-blue',
            'communication-workplace-culture' => 'course-badge-teal',
            'nutrition-hygiene' => 'course-badge-orange',
            'emergency-first-aid' => 'course-badge-red',
            'safety-compliance' => 'course-badge-amber',
            'medication-management' => 'course-badge-purple',
            'health-conditions-specialist-care' => 'course-badge-pink',
            'leadership-professional-development' => 'course-badge-indigo',
            'information-data-management' => 'course-badge-indigo',
          ];
          
          $category_icons = [
            'core-care-skills' => 'fa-heart',
            'emergency-first-aid' => 'fa-first-aid',
            'health-conditions-specialist-care' => 'fa-stethoscope',
            'medication-management' => 'fa-pills',
            'safety-compliance' => 'fa-shield-alt',
            'communication-workplace-culture' => 'fa-users',
            'information-data-management' => 'fa-database',
            'nutrition-hygiene' => 'fa-apple-alt',
            'leadership-professional-development' => 'fa-user-tie',
          ];
          
          $category_short_names = [
            'core-care-skills' => 'Core Care',
            'communication-workplace-culture' => 'Communication',
            'nutrition-hygiene' => 'Nutrition',
            'emergency-first-aid' => 'First Aid',
            'safety-compliance' => 'Safety',
            'medication-management' => 'Medication',
            'health-conditions-specialist-care' => 'Specialist',
            'leadership-professional-development' => 'Leadership',
            'information-data-management' => 'Data',
          ];
          
          $badge_color = isset($category_badge_colors[$category_slug]) ? $category_badge_colors[$category_slug] : 'course-badge-blue';
          $short_name = isset($category_short_names[$category_slug]) ? $category_short_names[$category_slug] : ($primary_term ? $primary_term->name : '');
          $primary_icon = isset($category_icons[$category_slug]) ? $category_icons[$category_slug] : 'fa-book';
          $secondary_icon = $secondary_term && isset($category_icons[$secondary_term->slug]) ? $category_icons[$secondary_term->slug] : 'fa-book';
          $secondary_badge_color = $secondary_term && isset($category_badge_colors[$secondary_term->slug]) ? $category_badge_colors[$secondary_term->slug] : 'course-badge-blue';
          $secondary_short_name = $secondary_term && isset($category_short_names[$secondary_term->slug]) ? $category_short_names[$secondary_term->slug] : ($secondary_term ? $secondary_term->name : '');
        ?>
        <article class="course-card" data-category="<?php echo esc_attr($category_slug); ?>" data-title="<?php echo esc_attr(strtolower(get_the_title())); ?>" data-course-id="<?php echo get_the_ID(); ?>" data-course-url="<?php echo esc_url(get_permalink()); ?>">
          <?php if (has_post_thumbnail()) : 
            $thumbnail_id = get_post_thumbnail_id();
            $image_src = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
            $image_srcset = wp_get_attachment_image_srcset($thumbnail_id, 'medium_large');
          ?>
          <div class="course-image-wrapper">
            <img srcset="<?php echo esc_attr($image_srcset); ?>"
                 src="<?php echo esc_url($image_src[0]); ?>"
                 alt="<?php echo esc_attr(get_the_title()); ?>"
                 class="course-image"
                 loading="lazy"
                 width="400"
                 height="225"
                 sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw">
          </div>
          <?php endif; ?>
          
          <div class="course-card-header">
            <?php if ($primary_term || $secondary_term) : ?>
            <div class="course-card-badge-wrapper">
              <?php if ($primary_term) : ?>
              <span class="course-card-badge <?php echo esc_attr($badge_color); ?>">
                <i class="fas <?php echo esc_attr($primary_icon); ?> course-card-badge-icon" aria-hidden="true"></i>
                <?php echo esc_html($short_name); ?>
              </span>
              <?php endif; ?>
              <?php if ($secondary_term) : ?>
              <span class="course-card-badge <?php echo esc_attr($secondary_badge_color); ?>">
                <i class="fas <?php echo esc_attr($secondary_icon); ?> course-card-badge-icon" aria-hidden="true"></i>
                <?php echo esc_html($secondary_short_name); ?>
              </span>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <h3 class="course-card-title"><?php the_title(); ?></h3>
            
            <?php if (has_excerpt()) : ?>
            <p class="course-card-description"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>
          </div>
          
          <div class="course-card-content">
            <div class="course-card-meta">
              <?php if ($duration) : ?>
              <div class="course-card-meta-item">
                <i class="fas fa-clock course-card-meta-icon" aria-hidden="true"></i>
                <span><?php echo esc_html($duration); ?></span>
              </div>
              <?php endif; ?>
              
              <?php if ($accreditation) : ?>
              <div class="course-card-meta-item">
                <i class="fas fa-trophy course-card-meta-icon" aria-hidden="true"></i>
                <span><?php echo esc_html($accreditation); ?></span>
              </div>
              <?php endif; ?>
              
              <?php if ($level) : ?>
              <div class="course-card-meta-item">
                <i class="fas fa-chart-line course-card-meta-icon" aria-hidden="true"></i>
                <span><?php echo esc_html($level); ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="course-card-footer">
            <?php if ($price) : ?>
            <div class="course-card-price">
              <p class="course-card-price-amount">From £<?php echo esc_html(number_format($price, 0)); ?></p>
              <p class="course-card-price-label">per person</p>
            </div>
            <?php endif; ?>
            <a href="<?php the_permalink(); ?>" class="course-card-link" aria-label="View <?php echo esc_attr(get_the_title()); ?>">
              View Course
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M12 5l7 7-7 7"></path>
              </svg>
            </a>
          </div>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
      
      <div class="courses-listing-footer" style="text-align: center; margin-top: 2rem;">
        <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-secondary">View All Courses</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Upcoming Events Section -->
  <?php if ($location_events->have_posts()) : ?>
  <section class="news-articles-section" aria-labelledby="location-events-heading">
    <div class="container">
      <div class="news-articles-header">
        <h2 id="location-events-heading" class="section-title">Upcoming Training Sessions in <?php echo esc_html($location_data['name']); ?></h2>
        <p class="section-subtitle">Book your place on scheduled training courses</p>
      </div>
      
      <div class="news-articles-grid">
        <?php 
        while ($location_events->have_posts()) : $location_events->the_post();
          $event_id = get_the_ID();
          $event_date = function_exists('get_field') ? get_field('event_date', $event_id) : '';
          $start_time = function_exists('get_field') ? get_field('start_time', $event_id) : '';
          $end_time = function_exists('get_field') ? get_field('end_time', $event_id) : '';
          $location = function_exists('get_field') ? get_field('event_location', $event_id) : '';
          $spaces = function_exists('get_field') ? get_field('spaces_available', $event_id) : '';
          $event_price = function_exists('get_field') ? get_field('event_price', $event_id) : '';
          $linked_course = function_exists('get_field') ? get_field('linked_course', $event_id) : null;
          
          $course_title = $linked_course ? $linked_course->post_title : get_the_title();
          $price = $event_price ?: ($linked_course ? (function_exists('get_field') ? get_field('course_price', $linked_course->ID) : '') : '');
          $duration = $linked_course ? (function_exists('get_field') ? get_field('course_duration', $linked_course->ID) : '') : '';
          
          $category_class = '';
          $category_name = '';
          if ($linked_course) {
            $terms = get_the_terms($linked_course->ID, 'course_category');
            if ($terms && !is_wp_error($terms)) {
              $category_name = $terms[0]->name;
              $category_class = 'event-card-' . sanitize_title($terms[0]->slug);
            }
          }
        ?>
        <a href="<?php the_permalink(); ?>" class="event-card <?php echo esc_attr($category_class); ?>">
          <div class="event-card-image-wrapper">
            <?php if ($linked_course && has_post_thumbnail($linked_course->ID)) : 
              $thumbnail_id = get_post_thumbnail_id($linked_course->ID);
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
            <h3 class="event-card-title"><?php echo esc_html($course_title); ?></h3>
            
            <div class="event-card-meta">
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
              
              <?php if ($location) : ?>
              <div class="event-card-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                  <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <span><?php echo esc_html($location); ?></span>
              </div>
              <?php endif; ?>
              
              <?php if ($spaces !== '' && $spaces > 0) : ?>
              <div class="event-card-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span><?php echo esc_html($spaces); ?> spaces</span>
              </div>
              <?php elseif ($spaces === '0') : ?>
              <div class="event-card-meta-item event-card-full">
                <span>Fully booked</span>
              </div>
              <?php endif; ?>
            </div>
            
            <?php if (has_excerpt()) : ?>
            <p class="event-card-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>
            <?php endif; ?>
          </div>
          
          <div class="event-card-footer">
            <?php if ($price) : ?>
            <div class="event-card-price">
              <span class="event-card-price-amount">£<?php echo esc_html(number_format($price, 0)); ?></span>
            </div>
            <?php endif; ?>
            <span class="event-card-cta">
              <span>Book Now</span>
              <span aria-hidden="true">→</span>
            </span>
          </div>
        </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
      
      <div style="text-align: center; margin-top: 2rem;">
        <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="btn btn-secondary">View All Upcoming Courses</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Why Choose Us Section -->
  <section class="why-choose-us-section" aria-labelledby="location-why-heading">
    <div class="container">
      <div class="why-choose-us-header">
        <h2 id="location-why-heading" class="why-choose-us-title">Why Choose Continuity Training Academy in <?php echo esc_html($location_data['name']); ?>?</h2>
        <p class="why-choose-us-subtitle">Professional, accredited care training tailored to your needs</p>
      </div>
      <div class="why-choose-us-grid">
        <div class="why-choose-us-card">
          <div class="why-choose-us-icon-wrapper">
            <i class="fas fa-check-circle why-choose-us-icon" aria-hidden="true"></i>
          </div>
          <h3 class="why-choose-us-card-title">CQC-Compliant Training</h3>
          <p class="why-choose-us-card-description">All our courses meet Care Quality Commission requirements and standards.</p>
        </div>
        
        <div class="why-choose-us-card">
          <div class="why-choose-us-icon-wrapper">
            <i class="fas fa-certificate why-choose-us-icon" aria-hidden="true"></i>
          </div>
          <h3 class="why-choose-us-card-title">CPD-Accredited</h3>
          <p class="why-choose-us-card-description">Professional development certificates recognized across the care sector.</p>
        </div>
        
        <div class="why-choose-us-card">
          <div class="why-choose-us-icon-wrapper">
            <i class="fas fa-user-tie why-choose-us-icon" aria-hidden="true"></i>
          </div>
          <h3 class="why-choose-us-card-title">Experienced Trainers</h3>
          <p class="why-choose-us-card-description">Learn from qualified professionals with real-world care sector experience.</p>
        </div>
        
        <div class="why-choose-us-card">
          <div class="why-choose-us-icon-wrapper">
            <i class="fas fa-map-marker-alt why-choose-us-icon" aria-hidden="true"></i>
          </div>
          <h3 class="why-choose-us-card-title">Flexible Locations</h3>
          <p class="why-choose-us-card-description">Training at our Maidstone Studios or on-site at your location in <?php echo esc_html($location_data['name']); ?>.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cant-find-section">
    <div class="container">
      <div class="cant-find-content">
        <h2 class="cant-find-title">Ready to Book Your Training in <?php echo esc_html($location_data['name']); ?>?</h2>
        <p class="cant-find-description">Contact us today to discuss your training needs or book your place on an upcoming course.</p>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; margin-top: 1.5rem;">
          <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="btn btn-primary">View Upcoming Courses</a>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" class="btn btn-secondary">Get in Touch</a>
          <a href="<?php echo esc_url($contact['phone_link']); ?>" class="btn btn-secondary">Call <?php echo esc_html($contact['phone']); ?></a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php
get_footer();
