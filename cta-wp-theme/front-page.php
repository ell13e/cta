<?php
/**
 * Homepage Template
 *
 * @package CTA_Theme
 */

get_header();

$hero_headline = cta_get_field('hero_headline', false, 'CQC-Compliant Care Training in Kent');
$hero_subheadline = cta_get_field('hero_subheadline', false, 'Expert-led accredited training courses designed to keep your team compliant, confident, and care-focused.');
$hero_cta_text = cta_get_field('hero_cta_text', false, 'Find My Course');
$hero_cta_link = cta_get_field('hero_cta_link', false, get_post_type_archive_link('course'));

// If hero CTA links to contact page, add type parameter for auto-fill
if ($hero_cta_link && strpos($hero_cta_link, cta_page_url('contact')) !== false) {
  $separator = strpos($hero_cta_link, '?') !== false ? '&' : '?';
  $hero_cta_link .= $separator . 'type=book-course';
}

$contact = cta_get_contact_info();

// Get Trustpilot settings from Customizer
$trustpilot_url = cta_get_theme_option('trustpilot_url', 'https://uk.trustpilot.com/review/continuitytrainingacademy.co.uk');
$trustpilot_rating = cta_get_theme_option('trustpilot_rating', '4.6/5');
$trustpilot_stars = cta_get_trustpilot_stars($trustpilot_rating);
?>

<!-- Homepage-specific skip links -->
<nav class="skip-links" aria-label="Skip to page sections">
  <a href="#partners-heading" class="skip-link">Skip to trusted partners</a>
  <a href="#why-us-heading" class="skip-link">Skip to why us</a>
  <a href="#courses-heading" class="skip-link">Skip to courses</a>
</nav>

<main id="main-content">
  <!-- Hero Section -->
  <section class="hero" aria-labelledby="hero-heading">
    <div class="container">
      <div class="hero-content">
        <a href="<?php echo esc_url($trustpilot_url); ?>" target="_blank" rel="noopener noreferrer" class="trustpilot-link">
          <div class="trustpilot-stars" role="img" aria-label="<?php echo esc_attr($trustpilot_rating); ?> rating">
            <?php echo $trustpilot_stars; ?>
          </div>
          <span class="trustpilot-rating"><?php echo esc_html($trustpilot_rating); ?></span>
          <span class="trustpilot-text">Excellent on Trustpilot</span>
        </a>

        <div class="hero-heading-wrapper">
          <h1 id="hero-heading" class="hero-title"><?php echo esc_html($hero_headline); ?></h1>
          <p class="hero-subtitle"><?php echo esc_html($hero_subheadline); ?></p>
        </div>
        <div class="hero-cta">
          <a href="<?php echo esc_url($hero_cta_link); ?>" class="btn btn-primary"><?php echo esc_html($hero_cta_text); ?></a>
          <a href="<?php echo esc_url($contact['phone_link']); ?>" class="btn btn-secondary">
            <i class="fas fa-phone icon-phone" aria-hidden="true"></i>
            <span><?php echo esc_html($contact['phone']); ?></span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="partners-enhanced" aria-labelledby="partners-heading">
    <div class="container">
      <?php
      $partners_title = cta_get_field('partners_title', false, 'Trusted by Care Providers Across the United Kingdom');
      // Parse {focus} tags for highlighting
      $partners_title_parsed = preg_replace('/\{focus\}(.*?)\{\/focus\}/', '<span class="partners-enhanced-focus">$1</span>', $partners_title);
      if ($partners_title_parsed === $partners_title) {
        // No tags found, highlight "United Kingdom" by default
        $partners_title_parsed = str_replace('United Kingdom', '<span class="partners-enhanced-focus">United Kingdom</span>', $partners_title);
      }
      ?>
      <h2 id="partners-heading" class="partners-enhanced-title">
        <?php echo wp_kses_post($partners_title_parsed); ?>
      </h2>
      <div class="partners-marquee">
        <div class="partners-marquee-fade-left"></div>
        <div class="partners-marquee-fade-right"></div>
        <div class="partners-marquee-inner animate-marquee-slow md:hover:pause" aria-label="Partner care provider logos">
          <?php 
          $partners = [
            ['slug' => 'rusko-care-ltd', 'name' => 'Rusko Care Ltd', 'url' => 'https://ruskocare.co.uk'],
            ['slug' => 'hummingbird-homecare-services', 'name' => 'Hummingbird Homecare Services', 'url' => 'https://www.hummingbird-homecare.co.uk'],
            ['slug' => 'healthy-care-limited', 'name' => 'Healthy Care Limited', 'url' => 'https://healthy-care.co.uk'],
            ['slug' => 'no-place-like-home', 'name' => 'No Place Like Home', 'url' => 'https://www.nplh.uk'],
            ['slug' => 'home-instead-senior-care', 'name' => 'Home Instead Senior Care', 'url' => 'https://www.homeinstead.co.uk'],
            ['slug' => 'royal-care', 'name' => 'Royal Care', 'url' => 'https://www.royalcare.co.uk'],
            ['slug' => 'ideal-care-services', 'name' => 'Ideal Care Services', 'url' => 'https://idealcareservices.com'],
            ['slug' => 'pineapple-care-services', 'name' => 'Pineapple Care Services', 'url' => 'https://www.pineapple.care'],
            ['slug' => '4life-healthcare-services', 'name' => '4Life Healthcare Services', 'url' => 'https://www.4lifehealthcare.co.uk'],
            ['slug' => 'almond-care', 'name' => 'Almond Care', 'url' => 'https://www.almondcare.co.uk'],
            ['slug' => 'enabling-care-for-you', 'name' => 'Enabling Care For You', 'url' => 'https://www.ecfy.co.uk'],
            ['slug' => 'care-at-home-services', 'name' => 'Care at Home Services', 'url' => 'https://careathomeservices.co.uk'],
            ['slug' => 'cera-care', 'name' => 'Cera Care', 'url' => 'https://ceracare.co.uk'],
            ['slug' => 'continuity-of-care-services', 'name' => 'Continuity of Care Services', 'url' => 'https://www.continuitycareservices.co.uk'],
            ['slug' => 'courage-healthcare', 'name' => 'Courage Healthcare', 'url' => 'https://couragehealthcare.co.uk'],
            ['slug' => 'caring-logo-flat-70', 'name' => 'Caring UK', 'url' => 'https://www.caringuk.com'],
          ];
          
          for ($set = 0; $set < 2; $set++) :
            $aria_hidden = $set > 0 ? ' aria-hidden="true"' : '';
          ?>
          <div class="partners-marquee-set"<?php echo $aria_hidden; ?>>
            <?php foreach ($partners as $partner) : ?>
            <div class="partner-logo-wrapper">
              <a href="<?php echo esc_url($partner['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Visit <?php echo esc_attr($partner['name']); ?> website">
                <img src="<?php echo esc_url(cta_image('partners/' . $partner['slug'] . '-logo.webp')); ?>" alt="<?php echo esc_attr($partner['name']); ?>" class="partner-logo" width="150" height="60" loading="lazy">
              </a>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Why Us Section -->
  <section class="why-us-blended" aria-labelledby="why-us-heading">
    <div class="container">
      <div class="why-us-blended-header">
        <p class="section-eyebrow"><?php echo esc_html(cta_get_field('why_us_eyebrow', false, 'Why us?')); ?></p>
        <h2 id="why-us-heading" class="section-title"><?php echo esc_html(cta_get_field('why_us_title', false, 'Why Care Providers Book Us')); ?></h2>
        <p class="section-subtitle"><?php echo esc_html(cta_get_field('why_us_subtitle', false, 'CQC-compliant training that actually suits the way your teams actually work.')); ?></p>
      </div>
      <div class="why-us-blended-grid">
        <div class="why-us-blended-card">
          <div class="why-us-blended-card-header">
            <div class="why-us-blended-icon-wrapper why-us-blended-icon-1">
              <i class="fas fa-user-shield why-us-blended-icon" aria-hidden="true"></i>
            </div>
            <span class="why-us-blended-badge badge-verified">Verified</span>
          </div>
          <h3 class="why-us-blended-title"><?php echo esc_html(cta_get_field('why_us_card1_title', false, 'DBS-checked trainers')); ?></h3>
          <p class="why-us-blended-description"><?php echo esc_html(cta_get_field('why_us_card1_description', false, 'Trusted professionals you can safely invite into your setting with complete peace of mind')); ?></p>
          <ul class="why-us-blended-list">
            <?php
            $card1_list = cta_get_field('why_us_card1_list', false, "Enhanced DBS clearance\nProfessional indemnity insurance\nRegular background updates");
            $card1_items = array_filter(array_map('trim', explode("\n", $card1_list)));
            foreach ($card1_items as $item) :
            ?>
            <li>
              <i class="fas fa-check icon-check" aria-hidden="true"></i>
              <?php echo esc_html($item); ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="why-us-blended-card">
          <div class="why-us-blended-card-header">
            <div class="why-us-blended-icon-wrapper why-us-blended-icon-2">
              <i class="fas fa-certificate why-us-blended-icon" aria-hidden="true"></i>
            </div>
            <span class="why-us-blended-badge badge-instant">Instant</span>
          </div>
          <h3 class="why-us-blended-title"><?php echo esc_html(cta_get_field('why_us_card2_title', false, 'Accredited certificates')); ?></h3>
          <p class="why-us-blended-description"><?php echo esc_html(cta_get_field('why_us_card2_description', false, 'No waiting for paperwork. Stay audit-ready instantly with immediate proof of competency')); ?></p>
          <ul class="why-us-blended-list">
            <?php
            $card2_list = cta_get_field('why_us_card2_list', false, "Digital & physical certificates\nEmail certificate delivery\nAutomatic renewal reminders");
            $card2_items = array_filter(array_map('trim', explode("\n", $card2_list)));
            foreach ($card2_items as $item) :
            ?>
            <li>
              <i class="fas fa-check icon-check" aria-hidden="true"></i>
              <?php echo esc_html($item); ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="why-us-blended-card">
          <div class="why-us-blended-card-header">
            <div class="why-us-blended-icon-wrapper why-us-blended-icon-3">
              <i class="fas fa-calendar-alt why-us-blended-icon" aria-hidden="true"></i>
            </div>
            <span class="why-us-blended-badge badge-choice">Your Choice</span>
          </div>
          <h3 class="why-us-blended-title"><?php echo esc_html(cta_get_field('why_us_card3_title', false, 'Flexible delivery')); ?></h3>
          <p class="why-us-blended-description"><?php echo esc_html(cta_get_field('why_us_card3_description', false, 'Train at your location or ours, whatever works for your team\'s schedule without disrupting care')); ?></p>
          <ul class="why-us-blended-list">
            <?php
            $card3_list = cta_get_field('why_us_card3_list', false, "On-site training available\nModern classroom facilities\nFlexible scheduling options");
            $card3_items = array_filter(array_map('trim', explode("\n", $card3_list)));
            foreach ($card3_items as $item) :
            ?>
            <li>
              <i class="fas fa-check icon-check" aria-hidden="true"></i>
              <?php echo esc_html($item); ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- Course Categories Section -->
  <section class="course-categories" aria-labelledby="courses-heading">
    <div class="container">
      <div class="section-header">
        <h2 id="courses-heading" class="section-title"><?php echo esc_html(cta_get_field('courses_title', false, 'Care Sector Training')); ?></h2>
        <p class="section-subtitle"><?php echo esc_html(cta_get_field('courses_subtitle', false, 'Essential to specialist care skills')); ?></p>
      </div>
      <div class="categories-grid">
        <?php
        // Use same badge color mapping as archive-course.php
        $category_badge_colors = [
          'core-care-skills' => 'category-badge-blue',
          'emergency-first-aid' => 'category-badge-red',
          'health-conditions-specialist-care' => 'category-badge-green',
          'medication-management' => 'category-badge-purple',
          'safety-compliance' => 'category-badge-amber',
          'communication-workplace-culture' => 'category-badge-teal',
          'information-data-management' => 'category-badge-indigo',
          'nutrition-hygiene' => 'category-badge-orange',
          'leadership-professional-development' => 'category-badge-pink',
        ];
        
        $categories = [
          [
            'slug' => 'core-care-skills',
            'name' => 'Core Care',
            'badge' => 'Essential',
            'badge_class' => $category_badge_colors['core-care-skills'],
            'icon' => 'fa-heart',
            'description' => 'Essential skills for providing quality care',
            'highlights' => ['Person-centred care principles', 'Professional communication skills', 'Legal frameworks & duty of care'],
          ],
          [
            'slug' => 'emergency-first-aid',
            'name' => 'First Aid',
            'badge' => 'Life-Saving',
            'badge_class' => $category_badge_colors['emergency-first-aid'],
            'icon' => 'fa-first-aid',
            'description' => 'Respond confidently in critical moments',
            'highlights' => ['CPR & defibrillator use', 'Emergency scenario management', 'Workplace first aid protocols'],
          ],
          [
            'slug' => 'health-conditions-specialist-care',
            'name' => 'Specialist Health',
            'badge' => 'Advanced',
            'badge_class' => $category_badge_colors['health-conditions-specialist-care'],
            'icon' => 'fa-stethoscope',
            'description' => 'Expert knowledge for complex conditions',
            'highlights' => ['Dementia & mental health support', 'Learning disabilities awareness', 'End of life care with dignity'],
          ],
          [
            'slug' => 'medication-management',
            'name' => 'Medication',
            'badge' => 'Clinical',
            'badge_class' => $category_badge_colors['medication-management'],
            'icon' => 'fa-pills',
            'description' => 'Safe medication administration practices',
            'highlights' => ['Medication safety protocols', 'Drug interactions & side effects', 'Documentation & record keeping'],
          ],
          [
            'slug' => 'safety-compliance',
            'name' => 'Safety',
            'badge' => 'Practical',
            'badge_class' => $category_badge_colors['safety-compliance'],
            'icon' => 'fa-shield-alt',
            'description' => 'Protect yourself and those in your care',
            'highlights' => ['Hoist operation & positioning', 'Risk assessment techniques', 'Fire safety & evacuation'],
          ],
          [
            'slug' => 'communication-workplace-culture',
            'name' => 'Communication',
            'badge' => 'Essential',
            'badge_class' => $category_badge_colors['communication-workplace-culture'],
            'icon' => 'fa-users',
            'description' => 'Effective and inclusive communication',
            'highlights' => ['Professional communication skills', 'Diversity & inclusion training', 'Team collaboration & culture'],
          ],
        ];
        
        foreach ($categories as $category) :
          $category_link = add_query_arg('category', $category['slug'], get_post_type_archive_link('course'));
        ?>
        <a href="<?php echo esc_url($category_link); ?>" class="category-card-link group" aria-label="View courses in <?php echo esc_attr($category['name']); ?>">
          <div class="category-card-bg-top"></div>
          <div class="category-card-bg-bottom"></div>
          
              <div class="category-card-header-new">
            <div class="category-header-left">
              <div class="category-icon-title-row">
                <span class="category-badge-new course-card-badge <?php echo esc_attr($category['badge_class']); ?>"><?php echo esc_html($category['badge']); ?></span>
                <div class="category-icon-wrapper">
                  <i class="fas <?php echo esc_attr($category['icon']); ?> category-icon-svg" aria-hidden="true"></i>
                </div>
                <div class="category-name-wrapper">
                  <h3 class="category-name-new"><?php echo esc_html($category['name']); ?></h3>
                </div>
              </div>
            </div>
          </div>

          <div class="category-content">
            <p class="category-description-new"><?php echo esc_html($category['description']); ?></p>
            <ul class="category-highlights-new">
              <?php foreach ($category['highlights'] as $index => $highlight) : ?>
              <li<?php echo $index === 2 ? ' class="category-highlight-mobile-hidden"' : ''; ?>>
                <i class="fas fa-check icon-check" aria-hidden="true"></i>
                <span><?php echo esc_html($highlight); ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <div class="category-footer-new">
            <div class="category-count-wrapper-new">
              <i class="fas fa-graduation-cap icon-graduation" aria-hidden="true"></i>
              <span class="category-count-new" data-category="<?php echo esc_attr($category['slug']); ?>">Courses</span>
            </div>
            <span class="category-explore-link">
              Explore <span class="explore-arrow" aria-hidden="true">→</span>
            </span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      
      <div class="categories-cta">
        <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-primary btn-large">
          Browse All Courses
          <span class="btn-arrow" aria-hidden="true">→</span>
          <span class="sr-only">to discover all training options</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Upcoming Courses Section -->
  <section class="upcoming-courses" aria-labelledby="upcoming-heading">
    <div class="container">
      <div class="section-header">
        <h2 id="upcoming-heading" class="section-title">Upcoming Courses</h2>
        <p class="section-subtitle">Upcoming open course dates in Maidstone & surrounding areas. Book your place on our CQC-compliant training sessions.</p>
      </div>
      
      <div class="courses-grid" id="upcoming-courses-grid">
        <?php
        $site_wide_active = function_exists('cta_is_site_wide_discount_active') ? cta_is_site_wide_discount_active() : false;
        $site_wide_percent = $site_wide_active && function_exists('cta_get_site_wide_discount_percentage') ? floatval(cta_get_site_wide_discount_percentage()) : 0;
        $site_wide_label = '';
        if ($site_wide_active && function_exists('cta_get_site_wide_discount')) {
          $sw = cta_get_site_wide_discount();
          $site_wide_label = !empty($sw['label']) ? (string) $sw['label'] : 'Site-Wide Sale';
        }

        // Query upcoming course events - limit to 3 coming soonest
        $upcoming_events = new WP_Query([
          'post_type' => 'course_event',
          'posts_per_page' => 3,
          'meta_key' => 'event_date',
          'orderby' => 'meta_value',
          'order' => 'ASC',
          'meta_query' => [
            'relation' => 'AND',
            [
              'key' => 'event_date',
              'value' => date('Y-m-d'),
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
        ]);
        
        if ($upcoming_events->have_posts()) :
          while ($upcoming_events->have_posts()) : $upcoming_events->the_post();
            $course = get_field('linked_course');
            $event_date = get_field('event_date');
            $start_time = get_field('start_time');
            $end_time = get_field('end_time');
            $location = get_field('event_location');
            $spaces = get_field('spaces_available');
            $price = get_field('event_price') ?: ($course ? get_field('course_price', $course->ID) : '');
            // Ensure price is numeric (remove any currency symbols)
            if ($price) {
              $price = preg_replace('/[£$,\s]/', '', $price);
              $price = is_numeric($price) ? floatval($price) : '';
            }

            $original_price = $price;
            $discounted_price = $price;
            $has_site_wide_discount = false;
            if ($site_wide_active && $site_wide_percent > 0 && $original_price && function_exists('cta_apply_site_wide_discount')) {
              $discounted_price = floatval(cta_apply_site_wide_discount(floatval($original_price)));
              if ($discounted_price > 0 && $discounted_price < floatval($original_price)) {
                $has_site_wide_discount = true;
              }
            }
            $duration = $course ? get_field('course_duration', $course->ID) : '';
            
            if (!$course) continue;
            
            // Get category for styling
            $category_class = '';
            $category_name = '';
            $terms = get_the_terms($course->ID, 'course_category');
            if ($terms && !is_wp_error($terms)) {
              $category_name = $terms[0]->name;
              $category_class = 'event-card-' . sanitize_title($terms[0]->slug);
            }
        ?>
        <a href="<?php echo esc_url(get_permalink()); ?>" class="event-card <?php echo esc_attr($category_class); ?>">
          <div class="event-card-image-wrapper">
            <?php if (has_post_thumbnail($course->ID)) : 
              $thumbnail_id = get_post_thumbnail_id($course->ID);
              $image_src = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
              $image_srcset = wp_get_attachment_image_srcset($thumbnail_id, 'medium_large');
              $image_sizes = wp_get_attachment_image_sizes($thumbnail_id, 'medium_large');
            ?>
            <img srcset="<?php echo esc_attr($image_srcset); ?>"
                 src="<?php echo esc_url($image_src[0]); ?>"
                 sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                 alt="<?php echo esc_attr($course->post_title); ?>"
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
            <h3 class="event-card-title"><?php echo esc_html($course->post_title); ?></h3>
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
                <span aria-hidden="true">→</span>
              </div>
            </div>
          </div>
        </a>
        <?php
          endwhile;
          wp_reset_postdata();
        else :
        ?>
        <p class="no-courses-message">No upcoming courses scheduled. Check back soon or <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>">contact us</a> to arrange group training.</p>
        <?php endif; ?>
      </div>
      
      <div class="upcoming-courses-footer">
        <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="btn btn-secondary courses-link-footer">
          View Full Course Calendar
          <span class="btn-arrow" aria-hidden="true">→</span>
          <span class="sr-only">to see all available training dates</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section class="testimonials-section" aria-labelledby="testimonials-heading">
    <div class="container">
      <div class="testimonials-header">
        <h2 id="testimonials-heading" class="section-title"><?php echo esc_html(cta_get_field('testimonials_title', false, 'What Our Learners Say')); ?></h2>
        <p class="section-subtitle"><?php echo esc_html(cta_get_field('testimonials_subtitle', false, 'Real feedback from care professionals who\'ve trained with us.')); ?></p>
        <div class="testimonials-trustpilot-link">
          <a href="<?php echo esc_url($trustpilot_url); ?>" target="_blank" rel="noopener noreferrer" class="trustpilot-link-inline" aria-label="Read all reviews on Trustpilot">
            <div class="trustpilot-stars-inline" role="img" aria-label="<?php echo esc_attr($trustpilot_rating); ?> rating">
              <?php echo $trustpilot_stars; ?>
            </div>
            <span class="trustpilot-rating-inline"><?php echo esc_html($trustpilot_rating); ?></span>
            <span class="trustpilot-text-inline">Excellent on Trustpilot</span>
            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
          </a>
        </div>
      </div>
      <div class="testimonials-grid">
        <article class="testimonial-card">
          <div class="testimonial-quote-wrapper">
            <p class="testimonial-quote">"Solely the best training academy!!! I had my training with a few different specialists in Continuity training academy and I am very pleased. It's much easier to learn when a trainer is passionate and excited about the topics they teach."</p>
          </div>
          <div class="testimonial-author">
            <div class="testimonial-avatar">
              <i class="fas fa-user" aria-hidden="true"></i>
            </div>
            <div class="testimonial-info">
              <div class="testimonial-name">Inga</div>
              <div class="testimonial-company">Trustpilot Review</div>
            </div>
          </div>
        </article>
        
        <article class="testimonial-card">
          <div class="testimonial-quote-wrapper">
            <p class="testimonial-quote">"We had Jen Boorman deliver our team TTT training. Medication, people's moving and handling and first aid at work! Jen is a fantastic trainer and leaves you feeling confident in your new learnt abilities!"</p>
          </div>
          <div class="testimonial-author">
            <div class="testimonial-avatar">
              <i class="fas fa-user" aria-hidden="true"></i>
            </div>
            <div class="testimonial-info">
              <div class="testimonial-name">Expertise Homecare</div>
              <div class="testimonial-company">Trustpilot Review</div>
            </div>
          </div>
        </article>
        
        <article class="testimonial-card">
          <div class="testimonial-quote-wrapper">
            <p class="testimonial-quote">"I had the pleasure of being trained by Jen, whom I have known for 10+ years. She has a wealth of knowledge to pass on. Jen's training style is very much centred around each individual and is delivered in a very personable manner."</p>
          </div>
          <div class="testimonial-author">
            <div class="testimonial-avatar">
              <i class="fas fa-user" aria-hidden="true"></i>
            </div>
            <div class="testimonial-info">
              <div class="testimonial-name">Melvyn</div>
              <div class="testimonial-company">Trustpilot Review</div>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- Latest News Section -->
  <section class="latest-news-section" aria-labelledby="latest-news-heading">
    <div class="container">
      <div class="latest-news-header">
        <h2 id="latest-news-heading" class="latest-news-title-main">
          Latest <span class="latest-news-title-highlight">News</span>
        </h2>
        <p class="latest-news-intro">Stay updated with the latest insights, training tips, and industry news.</p>
      </div>
      
      <?php
      // Query 3 most recent blog posts
      $recent_posts = new WP_Query([
        'post_type' => 'post',
        'posts_per_page' => 3,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
      ]);
      ?>
      
      <div class="latest-news-layout">
        <?php if ($recent_posts->have_posts()) : 
          $post_count = 0;
          $posts_array = [];
          while ($recent_posts->have_posts()) : $recent_posts->the_post();
            $posts_array[] = [
              'id' => get_the_ID(),
              'title' => get_the_title(),
              'permalink' => get_permalink(),
              'excerpt' => get_the_excerpt(),
              'thumbnail_id' => get_post_thumbnail_id(),
              'categories' => get_the_category(),
              'date' => get_the_date('M j, Y'),
            ];
          endwhile;
          wp_reset_postdata();
          
          // Display first post as featured
          if (!empty($posts_array)) :
            $featured = $posts_array[0];
            $featured_categories = $featured['categories'];
            $featured_category_name = $featured_categories ? strtoupper($featured_categories[0]->name) : 'NEWS';
        ?>
        <article class="latest-news-featured">
          <div class="latest-news-featured-badge">Latest News</div>
          <div class="latest-news-featured-image-wrapper">
            <?php if ($featured['thumbnail_id']) : 
              $image_src = wp_get_attachment_image_src($featured['thumbnail_id'], 'large');
              $image_srcset = wp_get_attachment_image_srcset($featured['thumbnail_id'], 'large');
              $image_sizes = wp_get_attachment_image_sizes($featured['thumbnail_id'], 'large');
            ?>
            <img srcset="<?php echo esc_attr($image_srcset); ?>"
                 src="<?php echo esc_url($image_src[0]); ?>"
                 sizes="<?php echo esc_attr($image_sizes); ?>"
                 alt="<?php echo esc_attr($featured['title']); ?>"
                 class="latest-news-featured-image"
                 loading="lazy"
                 width="600"
                 height="400">
            <?php else : ?>
            <div class="latest-news-featured-image-placeholder" aria-hidden="true">
              <i class="fas fa-newspaper" aria-hidden="true"></i>
            </div>
            <?php endif; ?>
          </div>
          <div class="latest-news-featured-content">
            <p class="latest-news-featured-meta"><?php echo esc_html($featured_category_name); ?> / <?php echo esc_html(strtoupper($featured['date'])); ?></p>
            <h3 class="latest-news-featured-title"><?php echo esc_html($featured['title']); ?></h3>
            <p class="latest-news-featured-excerpt"><?php echo wp_trim_words($featured['excerpt'], 20, '...'); ?></p>
            <a href="<?php echo esc_url($featured['permalink']); ?>" class="latest-news-featured-link" aria-label="Read full article - <?php echo esc_attr($featured['title']); ?>">
              Read Full Article
              <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </article>
        <?php endif; ?>
        
        <div class="latest-news-categories">
          <?php 
          // Display remaining 2 posts as category cards
          for ($i = 1; $i < min(3, count($posts_array)); $i++) :
            $post = $posts_array[$i];
            $post_categories = $post['categories'];
            $category_name = $post_categories ? $post_categories[0]->name : 'News';
          ?>
          <article class="latest-news-category-card">
            <div class="latest-news-category-image-wrapper">
              <?php if ($post['thumbnail_id']) : 
                $image_src = wp_get_attachment_image_src($post['thumbnail_id'], 'medium');
                $image_srcset = wp_get_attachment_image_srcset($post['thumbnail_id'], 'medium');
                $image_sizes = wp_get_attachment_image_sizes($post['thumbnail_id'], 'medium');
              ?>
              <img srcset="<?php echo esc_attr($image_srcset); ?>"
                   src="<?php echo esc_url($image_src[0]); ?>"
                   sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                   alt="<?php echo esc_attr($post['title']); ?>"
                   class="latest-news-category-image"
                   loading="lazy"
                   width="200"
                   height="150">
              <?php else : ?>
              <div class="latest-news-category-image-placeholder" aria-hidden="true">
                <i class="fas fa-file-alt" aria-hidden="true"></i>
              </div>
              <?php endif; ?>
            </div>
            <div class="latest-news-category-content">
              <h3 class="latest-news-category-title"><?php echo esc_html($category_name); ?></h3>
              <p class="latest-news-category-excerpt"><?php echo wp_trim_words($post['excerpt'], 15, '...'); ?></p>
              <a href="<?php echo esc_url($post['permalink']); ?>" class="latest-news-category-link" aria-label="Read more about <?php echo esc_attr($category_name); ?>">
                Read More
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
              </a>
            </div>
          </article>
          <?php endfor; ?>
        </div>
        <?php else : ?>
        <p class="no-articles-message">No articles yet. Check back soon for updates!</p>
        <?php endif; ?>
      </div>

      <div class="latest-news-cta">
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('news'))); ?>" class="latest-news-more-button">
          View all articles
        </a>
      </div>
    </div>
  </section>

  <!-- CTA Section with Form -->
  <section id="cta-centered-section" class="cta-centered-section" aria-labelledby="cta-centered-heading">
    <div class="cta-centered-decorative-top-left"></div>
    <div class="cta-centered-decorative-top-right"></div>
    <div class="cta-centered-decorative-bottom"></div>
    <div class="cta-centered-container">
      <div class="cta-centered-icon-wrapper" aria-hidden="true">
        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
      </div>
      <h2 id="cta-centered-heading" class="cta-centered-headline">Ready to Invest In <span class="cta-success-gradient">Success<span class="no-wrap">?</span></span></h2>
      <p class="cta-centered-description">CQC-compliant training with CPD-accredited certificates. Join <strong aria-label="Over 2000 professionals trained">2000+</strong> UK care professionals who trust us to keep their teams trained and compliant.</p>

      <div class="cta-centered-benefits">
        <div class="cta-centered-benefit">
          <i class="fas fa-check-circle" aria-hidden="true"></i>
          <span>Expert training guidance</span>
        </div>
        <div class="cta-centered-benefit">
          <i class="fas fa-check-circle" aria-hidden="true"></i>
          <span>Accredited training</span>
        </div>
        <div class="cta-centered-benefit">
          <i class="fas fa-check-circle" aria-hidden="true"></i>
          <span>CQC-compliant</span>
        </div>
      </div>

      <form class="cta-centered-form" method="post" action="" novalidate>
        <?php wp_nonce_field('cta_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="cta_callback_request">
        <input type="hidden" name="form-type" value="callback-request">
        <!-- Honeypot spam protection - multiple fields -->
        <input type="text" name="website" id="cta-website" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="text" name="url" id="cta-url" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="text" name="homepage" id="cta-homepage" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="hidden" name="form_load_time" id="cta-form-load-time" value="">
        <input type="hidden" name="submission_time" id="cta-submission-time" value="">
        <div class="cta-centered-success-message hidden" role="alert" aria-live="assertive">
          <i class="fas fa-check-circle" aria-hidden="true"></i>
          <span>Thank you! We'll call you back.</span>
        </div>
        <div class="cta-centered-form-card">
          <div class="cta-centered-input-group">
            <div class="cta-centered-input-wrapper">
              <label for="cta-centered-name" class="cta-centered-label">Name <span class="required-indicator" aria-label="required">*</span></label>
              <div class="cta-centered-input-box">
                <i class="fas fa-user cta-centered-input-icon" aria-hidden="true"></i>
                <input 
                  type="text" 
                  id="cta-centered-name" 
                  name="name" 
                  class="cta-centered-name-input" 
                  placeholder="Enter your name"
                  required
                  aria-required="true"
                  aria-describedby="cta-name-error"
                  autocomplete="name"
                >
                <i class="fas fa-exclamation-circle cta-centered-error-icon hidden" aria-hidden="true"></i>
              </div>
              <span id="cta-name-error" class="cta-centered-error-message" role="alert" aria-live="polite"></span>
            </div>
            <div class="cta-centered-input-wrapper">
              <label for="cta-centered-phone" class="cta-centered-label">Phone Number <span class="required-indicator" aria-label="required">*</span></label>
              <div class="cta-centered-input-box">
                <i class="fas fa-phone cta-centered-input-icon" aria-hidden="true"></i>
                <input 
                  type="tel" 
                  id="cta-centered-phone" 
                  name="phone" 
                  class="cta-centered-phone-input" 
                  placeholder="Enter your phone number"
                  required
                  aria-required="true"
                  aria-describedby="cta-phone-error"
                  autocomplete="tel"
                  inputmode="tel"
                >
                <i class="fas fa-exclamation-circle cta-centered-error-icon hidden" aria-hidden="true"></i>
              </div>
              <span id="cta-phone-error" class="cta-centered-error-message" role="alert" aria-live="polite"></span>
            </div>
            <div class="cta-centered-input-wrapper">
              <label for="cta-centered-email" class="cta-centered-label">Email Address <span class="optional-indicator">(optional)</span></label>
              <div class="cta-centered-input-box">
                <i class="fas fa-envelope cta-centered-input-icon" aria-hidden="true"></i>
                <input 
                  type="email" 
                  id="cta-centered-email" 
                  name="email" 
                  class="cta-centered-email-input" 
                  placeholder="Enter your email (optional)"
                  aria-describedby="cta-email-error"
                  autocomplete="email"
                  inputmode="email"
                >
                <i class="fas fa-exclamation-circle cta-centered-error-icon hidden" aria-hidden="true"></i>
              </div>
              <span id="cta-email-error" class="cta-centered-error-message" role="alert" aria-live="polite"></span>
            </div>
            <div class="cta-centered-consent">
              <div class="cta-centered-consent-checkbox-wrapper">
                <input
                  type="checkbox"
                  id="cta-centered-consent"
                  name="consent"
                  class="cta-centered-consent-checkbox"
                  aria-describedby="cta-consent-error"
                  aria-invalid="false"
                />
                <label for="cta-centered-consent" class="cta-centered-consent-label">
                  I would like to be contacted about training services.
                </label>
              </div>
              <div class="cta-centered-consent-checkbox-wrapper" style="margin-top: 12px;">
                <input
                  type="checkbox"
                  id="cta-marketing-consent"
                  name="marketingConsent"
                  checked
                  class="cta-centered-consent-checkbox"
                />
                <label for="cta-marketing-consent" class="cta-centered-consent-label">
                  I would like to receive updates, offers, and training news from Continuity Training Academy.
                </label>
              </div>
              <span id="cta-consent-error" class="cta-centered-error-message" role="alert" aria-live="polite" style="display: none;"></span>
            </div>
            <!-- reCAPTCHA v3 (invisible, no widget needed) -->
            <input type="hidden" name="g-recaptcha-response" id="cta-recaptcha-response" value="">
            <span id="cta-robot-error" class="cta-centered-error-message" role="alert" aria-live="polite" style="display: none;"></span>
            <p class="cta-centered-privacy">
              By submitting this form, you agree to our <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" class="cta-centered-privacy-link">Privacy Policy</a>
            </p>
            <button type="submit" class="cta-centered-submit-button" aria-label="Submit callback request">
              <span class="cta-button-text">Request callback</span>
              <i class="fas fa-arrow-right cta-button-icon" aria-hidden="true"></i>
              <i class="fas fa-spinner fa-spin cta-button-spinner hidden" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      </form>
    </div>
  </section>
</main>

<?php get_footer(); ?>

