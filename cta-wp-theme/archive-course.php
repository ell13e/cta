<?php
/**
 * Course Archive Template
 *
 * @package CTA_Theme
 */

get_header();

$categories = cta_get_course_categories();
$current_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

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

$category_badge_colors = [
  'core-care-skills' => 'course-badge-blue',
  'emergency-first-aid' => 'course-badge-red',
  'health-conditions-specialist-care' => 'course-badge-green',
  'medication-management' => 'course-badge-purple',
  'safety-compliance' => 'course-badge-amber',
  'communication-workplace-culture' => 'course-badge-teal',
  'information-data-management' => 'course-badge-indigo',
  'nutrition-hygiene' => 'course-badge-orange',
  'leadership-professional-development' => 'course-badge-pink',
];

$category_short_names = [
  'core-care-skills' => 'Core Care',
  'emergency-first-aid' => 'First Aid',
  'health-conditions-specialist-care' => 'Specialist',
  'medication-management' => 'Medication',
  'safety-compliance' => 'Safety',
  'communication-workplace-culture' => 'Communication',
  'information-data-management' => 'Data',
  'nutrition-hygiene' => 'Nutrition',
  'leadership-professional-development' => 'Leadership',
];

$category_names = [
  'communication-workplace-culture' => 'Communication',
  'core-care-skills' => 'Core Care Skills',
  'emergency-first-aid' => 'First Aid',
  'health-conditions-specialist-care' => 'Specialist Care',
  'information-data-management' => 'GDPR & Data',
  'leadership-professional-development' => 'Leadership',
  'medication-management' => 'Medication',
  'nutrition-hygiene' => 'Nutrition & Hygiene',
  'safety-compliance' => 'Safety & Compliance'
];
?>

<main id="main-content" class="site-main">
  <section class="group-hero-section" aria-labelledby="courses-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="breadcrumb-link">Courses</a>
          </li>
          <?php if ($current_category) : 
            // Use full category name for breadcrumb (not short name)
            $category_name = isset($category_names[$current_category]) 
              ? $category_names[$current_category] 
              : ucwords(str_replace('-', ' ', $current_category));
          ?>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <span class="breadcrumb-current" aria-current="page"><?php echo esc_html($category_name); ?></span>
          </li>
          <?php endif; ?>
        </ol>
      </nav>
      <h1 id="courses-heading" class="hero-title">Accredited Care Training Courses</h1>
      <p class="hero-subtitle">
        Browse our comprehensive range of care sector training courses. All delivered by experienced instructors
        with CPD-accredited certificates.
      </p>
    </div>
  </section>

  <div class="courses-filter-section" id="courses-filter-section">
    <div class="container">
      <!-- Search Input -->
      <div class="courses-search-wrapper">
        <div class="courses-search-input-wrapper">
          <i class="fas fa-search courses-search-icon" aria-hidden="true"></i>
          <input
            id="course-search"
            type="search"
            placeholder="Search courses..."
            class="courses-search-input"
            aria-label="Search courses by title or description"
          />
          <button
            id="clear-search"
            class="courses-search-clear"
            aria-label="Clear search"
            style="display: none;"
          >
            <i class="fas fa-times" aria-hidden="true"></i>
          </button>
          <kbd class="courses-search-shortcut">/</kbd>
        </div>
      </div>

      <!-- Category Filter Buttons -->
      <div class="courses-filter-buttons-wrapper">
        <button id="mobile-filter-toggle" class="mobile-filter-toggle" aria-expanded="false" aria-controls="courses-filter-buttons">
          <i class="fas fa-filter" aria-hidden="true"></i>
          <span>Filter Categories</span>
          <i class="fas fa-chevron-down mobile-filter-chevron" aria-hidden="true"></i>
        </button>
        <div class="courses-filter-buttons" id="courses-filter-buttons" role="group" aria-label="Filter courses by category">
          <button id="filter-all" class="courses-filter-btn<?php echo empty($current_category) ? ' courses-filter-btn-active' : ''; ?>" data-category="all" aria-pressed="<?php echo empty($current_category) ? 'true' : 'false'; ?>" aria-label="Show all courses">
            <i class="fas fa-th-large courses-filter-icon" aria-hidden="true"></i>
            <span class="courses-filter-btn-text">All Courses</span>
          </button>
          <?php foreach ($categories as $category) : 
            $icon = isset($category_icons[$category['slug']]) ? $category_icons[$category['slug']] : 'fa-book';
            $is_active = $current_category === $category['slug'];
          ?>
          <button id="filter-<?php echo esc_attr($category['slug']); ?>" class="courses-filter-btn<?php echo $is_active ? ' courses-filter-btn-active' : ''; ?>" data-category="<?php echo esc_attr($category['slug']); ?>" aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>" aria-label="Filter by <?php echo esc_attr($category['name']); ?>">
            <i class="fas <?php echo esc_attr($icon); ?> courses-filter-icon" aria-hidden="true"></i>
            <span class="courses-filter-btn-text"><?php echo esc_html($category['name']); ?></span>
          </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <section class="courses-listing-section" aria-labelledby="courses-catalog-heading">
    <div class="container">
      <h2 id="courses-catalog-heading" class="sr-only">Course Catalog</h2>
      
      <div class="courses-results-count" tabindex="-1" id="results-count-container">
        <p role="status" aria-live="polite">
          Showing <span class="courses-results-number" id="results-count">0</span>
          <span id="results-text"> courses</span>
        </p>
      </div>

      <?php
      $args = [
        'post_type' => 'course',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
      ];
      
      if ($current_category) {
        $args['tax_query'] = [
          [
            'taxonomy' => 'course_category',
            'field' => 'slug',
            'terms' => $current_category,
          ],
        ];
      }
      
      $courses = new WP_Query($args);
      $course_count = $courses->found_posts;
      
      if ($courses->have_posts()) :
      ?>
      <div id="courses-grid" class="courses-grid">
        <?php while ($courses->have_posts()) : $courses->the_post(); 
          $duration = get_field('course_duration');
          $price = get_field('course_price');
          $accreditation = get_field('course_accreditation');
          $level = get_field('course_level');
          // Use limiting function to get max 2 categories
          $terms = function_exists('cta_get_course_category_terms') ? cta_get_course_category_terms(get_the_ID()) : get_the_terms(get_the_ID(), 'course_category');
          $primary_term = $terms && !is_wp_error($terms) && !empty($terms) ? $terms[0] : null;
          $secondary_term = $terms && !is_wp_error($terms) && count($terms) >= 2 ? $terms[1] : null;
          $category_slug = $primary_term ? $primary_term->slug : '';
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
            <a href="<?php the_permalink(); ?>" class="course-read-more-btn" aria-label="Read more about <?php echo esc_attr(get_the_title()); ?>">
              Read More
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M12 5l7 7-7 7"></path>
              </svg>
            </a>
          </div>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
      
      <div id="no-results" class="no-results" style="display: none;">
        <p>No courses found matching your criteria. Try adjusting your filters or <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>">contact us</a> for help.</p>
      </div>
      
      <?php else : ?>
      <p class="no-courses">No courses available at the moment. Please check back soon.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="why-choose-us-section" aria-labelledby="why-choose-us-heading">
    <div class="container">
      <div class="why-choose-us-header">
        <h2 id="why-choose-us-heading" class="why-choose-us-title">Why Choose Us for Your Accredited Care Training?</h2>
        <p class="why-choose-us-subtitle">We provide high-quality accredited care training courses to individuals and organisations across the UK.</p>
      </div>
      <div class="why-choose-us-grid">
        <div class="why-choose-us-card">
          <div class="why-choose-us-icon-wrapper">
            <i class="fas fa-comments why-choose-us-icon" aria-hidden="true"></i>
          </div>
          <h3 class="why-choose-us-card-title">Ongoing Support</h3>
          <p class="why-choose-us-card-description">Get continuous support and guidance throughout your learning journey.</p>
        </div>
        <div class="why-choose-us-card">
          <div class="why-choose-us-icon-wrapper">
            <i class="fas fa-users why-choose-us-icon" aria-hidden="true"></i>
          </div>
          <h3 class="why-choose-us-card-title">Join Our Community</h3>
          <p class="why-choose-us-card-description">Connect with fellow care professionals and expand your network.</p>
        </div>
        <div class="why-choose-us-card">
          <div class="why-choose-us-icon-wrapper">
            <i class="fas fa-compass why-choose-us-icon" aria-hidden="true"></i>
          </div>
          <h3 class="why-choose-us-card-title">Career Guidance</h3>
          <p class="why-choose-us-card-description">Receive expert advice to advance your career in the care sector.</p>
        </div>
        <div class="why-choose-us-card">
          <div class="why-choose-us-icon-wrapper">
            <i class="fas fa-wallet why-choose-us-icon" aria-hidden="true"></i>
          </div>
          <h3 class="why-choose-us-card-title">Flexible Payment Options</h3>
          <p class="why-choose-us-card-description">Choose from various payment plans that suit your budget.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="cant-find-section" aria-labelledby="cant-find-heading">
    <div class="container">
      <div class="cant-find-content">
        <h2 id="cant-find-heading" class="cant-find-title">Can't find what you're looking for?</h2>
        <p class="cant-find-description">If you're unable to find the course you're looking for, please contact us and we'll be happy to help.</p>
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" class="btn btn-primary">Contact Us</a>
      </div>
    </div>
  </section>
</main>

<script>
(function() {
  const resultsCount = document.getElementById('results-count');
  const coursesGrid = document.getElementById('courses-grid');
  if (resultsCount && coursesGrid) {
    const cards = coursesGrid.querySelectorAll('.course-card');
    resultsCount.textContent = cards.length;
  }

  const filterButtons = document.querySelectorAll('.courses-filter-btn');
  filterButtons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      const category = this.getAttribute('data-category');
      const url = new URL(window.location);
      
      if (category && category !== 'all') {
        url.searchParams.set('category', category);
      } else {
        url.searchParams.delete('category');
      }
      window.location = url;
    });
  });

  const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
  const filterButtonsContainer = document.getElementById('courses-filter-buttons');
  if (mobileFilterToggle && filterButtonsContainer) {
    mobileFilterToggle.addEventListener('click', function() {
      const isExpanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', !isExpanded);
      filterButtonsContainer.classList.toggle('is-open');
    });
  }

  const searchInput = document.getElementById('course-search');
  const clearSearchBtn = document.getElementById('clear-search');
  const noResults = document.getElementById('no-results');
  
  if (searchInput && coursesGrid) {
    searchInput.addEventListener('input', function() {
      const query = this.value.toLowerCase().trim();
      const cards = coursesGrid.querySelectorAll('.course-card');
      let visibleCount = 0;
      
      cards.forEach(function(card) {
        const title = card.getAttribute('data-title') || '';
        const description = card.querySelector('.course-card-description')?.textContent.toLowerCase() || '';
        const matches = title.includes(query) || description.includes(query);
        card.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
      });
      
      if (resultsCount) {
        resultsCount.textContent = visibleCount;
      }
      
      if (noResults) {
        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
      }
      
      if (clearSearchBtn) {
        clearSearchBtn.style.display = query ? 'flex' : 'none';
      }
    });
    
    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        searchInput.focus();
      });
    }
    
    document.addEventListener('keydown', function(e) {
      if (e.key === '/' && document.activeElement !== searchInput) {
        e.preventDefault();
        searchInput.focus();
      }
    });
  }
})();
</script>

<?php get_footer(); ?>

