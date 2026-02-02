<?php
/**
 * Template Name: CQC Compliance Hub
 * 
 * Content hub for CQC compliance information, articles, and resources
 *
 * @package CTA_Theme
 */

get_header();

$contact = cta_get_contact_info();

/**
 * Helper function to find course page URL by title/keywords
 */
function cta_find_course_link($keywords) {
  static $courses_cache = null;
  
  if ($courses_cache === null) {
    $courses_cache = get_posts([
      'post_type' => 'course',
      'posts_per_page' => -1,
      'post_status' => 'publish',
    ]);
  }
  
  $keywords_lower = strtolower(trim($keywords));
  
  // Exact title matches first
  foreach ($courses_cache as $course) {
    $title_lower = strtolower($course->post_title);
    if ($title_lower === $keywords_lower || strpos($title_lower, $keywords_lower) !== false) {
      return get_permalink($course->ID);
    }
  }
  
  // Keyword mapping for common variations
  $keyword_map = [
    'safeguarding' => ['safeguarding', 'safeguarding adults', 'safeguarding children'],
    'first aid' => ['first aid', 'efaw', 'emergency first aid', 'efa'],
    'moving' => ['moving', 'handling', 'manual handling', 'moving & handling', 'moving and handling'],
    'medication' => ['medication', 'medicines', 'medication management', 'medication competency'],
    'fire safety' => ['fire safety', 'fire'],
    'health & safety' => ['health', 'safety', 'health & safety', 'health and safety'],
    'infection' => ['infection', 'ipc', 'infection control', 'infection prevention'],
    'care certificate' => ['care certificate'],
    'dementia' => ['dementia', 'dementia care'],
    'mca' => ['mental capacity', 'dols', 'mca', 'mental capacity act'],
    'food hygiene' => ['food hygiene', 'food safety'],
  ];
  
  foreach ($keyword_map as $key => $terms) {
    if (in_array($keywords_lower, $terms) || strpos($keywords_lower, $key) !== false) {
      foreach ($courses_cache as $course) {
        $title_lower = strtolower($course->post_title);
        foreach ($terms as $term) {
          if (strpos($title_lower, $term) !== false) {
            return get_permalink($course->ID);
          }
        }
      }
    }
  }
  
  return null;
}

/**
 * Helper to create a course link or plain text
 */
function cta_course_link($course_name, $display_text = null) {
  $link = cta_find_course_link($course_name);
  $text = $display_text ?: $course_name;
  return $link ? '<a href="' . esc_url($link) . '">' . esc_html($text) . '</a>' : esc_html($text);
}

/**
 * Determine if content should show "NEW" badge
 * Rules: Content updated within last 30 days OR manually flagged
 */
function cta_is_content_new($post_id = 0, $manual_flag = false) {
  if ($manual_flag) {
    return true; // Manual override
  }
  
  if ($post_id > 0) {
    $updated = get_the_modified_date('U', $post_id);
    if (!$updated) {
      $updated = get_the_date('U', $post_id);
    }
    
    if ($updated) {
      $days_old = (current_time('timestamp') - $updated) / DAY_IN_SECONDS;
      return $days_old <= 30; // 30 day rule
    }
  }
  
  return false;
}

/**
 * Get human-readable update date
 */
function cta_get_content_updated_date($post_id) {
  $updated = get_the_modified_date('Y-m-d', $post_id);
  if (!$updated) {
    $updated = get_the_date('Y-m-d', $post_id);
  }
  
  $timestamp = strtotime($updated);
  return human_time_diff($timestamp, current_time('timestamp')) . ' ago';
}

/**
 * Get SVG icon for CQC hub components
 */
function cta_get_cqc_icon($type) {
  $icons = [
    'inspection' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
    'alert' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'guide' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>',
    'tool' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>',
    'checklist' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
    'calendar' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    'grid' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/></svg>',
    'question' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'book' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 003 3h7z"/></svg>',
  ];
  
  return $icons[$type] ?? '';
}

// ACF fields
$hero_title = function_exists('get_field') ? get_field('hero_title') : '';
$hero_subtitle = function_exists('get_field') ? get_field('hero_subtitle') : '';
$intro_text = function_exists('get_field') ? get_field('intro_text') : '';

// Defaults
if (empty($hero_title)) {
    $hero_title = 'Your Complete Guide to CQC Training Compliance';
}
if (empty($hero_subtitle)) {
    $hero_subtitle = 'Everything care providers need to know about training requirements, inspection preparation, and regulatory changes.';
}
if (empty($intro_text)) {
    $intro_text = 'The Care Quality Commission (CQC) sets standards for health and social care services in England. Our CQC-compliant training courses help care providers meet these requirements and prepare for inspections.';
}

// Get CQC-related posts
$cqc_category_ids = function_exists('get_field') ? (get_field('cqc_post_categories') ?: []) : [];
$cqc_category_ids = is_array($cqc_category_ids) ? array_filter(array_map('absint', $cqc_category_ids)) : [];

$cqc_query = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'tax_query' => !empty($cqc_category_ids) ? [
        [
            'taxonomy' => 'category',
            'field' => 'term_id',
            'terms' => $cqc_category_ids,
            'operator' => 'IN',
        ],
    ] : [],
]);

// If no posts found with those categories, get recent posts (fallback)
if (!$cqc_query->have_posts()) {
    $cqc_query = new WP_Query([
        'post_type' => 'post',
        'posts_per_page' => 12,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
}

// Get CQC-related courses
$cqc_courses = new WP_Query([
    'post_type' => 'course',
    'posts_per_page' => 6,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => 'course_accreditation',
            'value' => 'CQC',
            'compare' => 'LIKE',
        ],
        [
            'key' => 'course_description',
            'value' => 'CQC',
            'compare' => 'LIKE',
        ],
    ],
]);

// Get FAQs
$faqs = [];
if (function_exists('get_field')) {
    $faqs = get_field('faqs');
}
if (empty($faqs)) {
    // Default CQC FAQs
    $faqs = [
        [
            'category' => 'general',
            'question' => 'What training do care homes need for CQC compliance?',
            'answer' => 'Care homes need mandatory training including:' . 
              '<ul class="list-two-column-gold">' .
              '<li>' . (cta_find_course_link('Safeguarding') ? '<a href="' . esc_url(cta_find_course_link('Safeguarding')) . '">Safeguarding</a>' : 'Safeguarding') . '</li>' .
              '<li>' . (cta_find_course_link('Health Safety') ? '<a href="' . esc_url(cta_find_course_link('Health Safety')) . '">Health & Safety</a>' : 'Health & Safety') . '</li>' .
              '<li>' . (cta_find_course_link('Fire Safety') ? '<a href="' . esc_url(cta_find_course_link('Fire Safety')) . '">Fire Safety</a>' : 'Fire Safety') . '</li>' .
              '<li>' . (cta_find_course_link('Moving Handling') ? '<a href="' . esc_url(cta_find_course_link('Moving Handling')) . '">Moving & Handling</a>' : 'Moving & Handling') . '</li>' .
              '<li>' . (cta_find_course_link('First Aid') ? '<a href="' . esc_url(cta_find_course_link('First Aid')) . '">First Aid</a>' : 'First Aid') . '</li>' .
              '<li>' . (cta_find_course_link('Food Hygiene') ? '<a href="' . esc_url(cta_find_course_link('Food Hygiene')) . '">Food Hygiene</a>' : 'Food Hygiene') . '</li>' .
              '<li>' . (cta_find_course_link('Infection Control') ? '<a href="' . esc_url(cta_find_course_link('Infection Control')) . '">Infection Control</a>' : 'Infection Control') . '</li>' .
              '<li>' . (cta_find_course_link('Medication Management') ? '<a href="' . esc_url(cta_find_course_link('Medication Management')) . '">Medication Management</a>' : 'Medication Management') . '</li>' .
              '</ul>' .
              '<p>All staff must complete the ' . (cta_find_course_link('Care Certificate') ? '<a href="' . esc_url(cta_find_course_link('Care Certificate')) . '">Care Certificate</a>' : 'Care Certificate') . ' within 12 weeks of starting.</p>',
        ],
        [
            'category' => 'general',
            'question' => 'How do I prepare staff for a CQC inspection?',
            'answer' => 'Ensure all staff have completed <a href="' . esc_url(get_permalink(get_page_by_path('faqs')) . '?category=general') . '">mandatory training</a>, <a href="' . esc_url(get_permalink(get_page_by_path('downloadable-resources'))) . '">training records</a> are up to date, policies and procedures are current, and staff understand their roles. Our <a href="' . esc_url(get_post_type_archive_link('course')) . '">CQC-compliant training courses</a> cover everything you need to know.',
        ],
        [
            'category' => 'training',
            'question' => 'How often does CQC training need to be refreshed?',
            'answer' => 'Most CQC <a href="' . esc_url(get_permalink(get_page_by_path('faqs')) . '?category=general') . '">mandatory training</a> should be refreshed annually. <a href="' . esc_url(cta_find_course_link('First Aid') ?: get_post_type_archive_link('course')) . '">First Aid</a> certificates typically last 3 years. <a href="' . esc_url(cta_find_course_link('Safeguarding') ?: get_post_type_archive_link('course')) . '">Safeguarding</a> training should be refreshed every 2-3 years. Check specific <a href="' . esc_url(get_post_type_archive_link('course')) . '">course requirements</a> for exact refresh periods.',
        ],
        [
            'category' => 'training',
            'question' => 'Is online training accepted by CQC?',
            'answer' => 'CQC accepts a mix of online and face-to-face training, but some topics (like practical first aid) require hands-on training. Our face-to-face courses ensure all practical elements are covered to CQC standards.',
        ],
    ];
}

// Generate FAQ schema
$faq_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [],
];

foreach ($faqs as $faq) {
    if (is_array($faq) && isset($faq['question']) && isset($faq['answer'])) {
        $faq_schema['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer'],
            ],
        ];
    }
}

// CollectionPage schema
$collection_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $hero_title,
    'description' => $hero_subtitle,
    'url' => get_permalink(),
];
?>

<?php if (!empty($faq_schema['mainEntity'])) : ?>
<script type="application/ld+json">
<?php echo json_encode($faq_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>
<?php endif; ?>

<script type="application/ld+json">
<?php echo json_encode($collection_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>

<main id="main-content" class="site-main">
  <!-- Hero Section with Training Selector -->
  <section class="cqc-hero-section" aria-labelledby="cqc-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>#resources" class="breadcrumb-link">Resources</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><span class="breadcrumb-current" aria-current="page">CQC Compliance Hub</span></li>
        </ol>
      </nav>
      <h1 id="cqc-heading" class="hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
      
      <!-- Training Requirements Selector -->
      <div class="cqc-training-selector-hero">
        <h2 class="cqc-selector-title">What Training Do You Need?</h2>
        <p class="cqc-selector-description">Select your care setting to see mandatory training requirements</p>
        
        <div class="cqc-setting-buttons">
          <button type="button" class="cqc-setting-btn" data-setting="domiciliary" aria-label="Show training requirements for Domiciliary Care">
            Domiciliary Care
          </button>
          <button type="button" class="cqc-setting-btn" data-setting="residential" aria-label="Show training requirements for Residential Care">
            Residential Care
          </button>
          <button type="button" class="cqc-setting-btn" data-setting="nursing" aria-label="Show training requirements for Nursing Home">
            Nursing Home
          </button>
          <button type="button" class="cqc-setting-btn" data-setting="supported-living" aria-label="Show training requirements for Supported Living">
            Supported Living
          </button>
          <button type="button" class="cqc-setting-btn" data-setting="complex-care" aria-label="Show training requirements for Complex Care">
            Complex Care
          </button>
        </div>
        
        <!-- Results display area -->
        <div class="cqc-training-results" id="training-results" aria-live="polite" style="display: none;">
          <div class="cqc-results-header">
            <h3 id="results-title" class="cqc-results-title"></h3>
            <button type="button" class="cqc-results-collapse" aria-label="Collapse results">×</button>
          </div>
          <div class="cqc-results-content">
            <ul class="cqc-results-list list-two-column-gold" id="results-list">
              <!-- Dynamically populated -->
            </ul>
            <div class="cqc-results-footer" id="results-footer" style="display: none;">
              <p class="cqc-results-count">Showing 10 of <span id="total-count"></span> courses</p>
              <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-secondary">
                View All Courses →
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Quick Start Paths -->
  <section class="cqc-quick-paths" aria-label="Quick start options">
    <div class="container">
      <h2 class="sr-only">Quick Start</h2>
      <div class="cqc-path-grid">
        <a href="#inspection-prep" class="cqc-path-card">
          <?php echo cta_get_cqc_icon('inspection'); ?>
          <strong>Facing Inspection?</strong>
          <span>Get inspection-ready checklist</span>
        </a>
        
        <a href="#regulatory-updates" class="cqc-path-card cqc-path-urgent">
          <?php echo cta_get_cqc_icon('alert'); ?>
          <strong>2026 Regulatory Changes</strong>
          <span>New requirements coming</span>
          <span class="cqc-path-badge">NEW</span>
        </a>
        
        <a href="#essential-guidance" class="cqc-path-card">
          <?php echo cta_get_cqc_icon('guide'); ?>
          <strong>New Provider?</strong>
          <span>Essential compliance guidance</span>
        </a>
      </div>
    </div>
  </section>
  
  <!-- Critical Alert Banner -->
  <section class="cqc-alert-banner" aria-label="Critical regulatory updates">
    <div class="container">
      <div class="cqc-alert-content">
        <span class="cqc-alert-badge">NEW 2026</span>
        <div class="cqc-alert-text">
          <strong>Major Regulatory Changes:</strong>
          Oliver McGowan Training becomes statutory • New Single Assessment Framework • Enhanced safeguarding requirements
      </div>
        <a href="#regulatory-updates" class="btn btn-alert">View All Changes →</a>
      </div>
    </div>
  </section>
  
  <!-- Search Section -->
  <section class="cqc-search-section" aria-label="Search CQC compliance resources">
    <div class="container">
      <form class="cqc-hub-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <label for="cqc-search" class="sr-only">Search CQC compliance resources</label>
        <div class="cqc-search-wrapper">
          <input 
            type="search" 
            id="cqc-search" 
            name="s" 
            value="<?php echo esc_attr(get_query_var('s')); ?>"
            placeholder="Search training requirements, regulations, inspection guidance..."
            class="cqc-search-input"
          >
          <input type="hidden" name="post_type" value="post">
          <button type="submit" class="cqc-search-btn" aria-label="Search">
            <?php echo cta_get_cqc_icon('inspection'); ?>
          </button>
        </div>
      </form>
      
      <!-- Quick Filters -->
      <div class="cqc-quick-filters">
        <span class="cqc-filter-label">Quick filters:</span>
        <a href="?filter=training" class="cqc-filter-tag <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'training') ? 'is-active' : ''; ?>">Training</a>
        <a href="?filter=inspection" class="cqc-filter-tag <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'inspection') ? 'is-active' : ''; ?>">Inspection</a>
        <a href="?filter=regulations" class="cqc-filter-tag <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'regulations') ? 'is-active' : ''; ?>">Regulations</a>
        <a href="?filter=tools" class="cqc-filter-tag <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'tools') ? 'is-active' : ''; ?>">Tools</a>
      </div>
    </div>
  </section>

  <!-- Essential Guidance Section -->
  <section class="cqc-section cqc-section-essential" id="essential-guidance">
    <div class="container">
      <div class="cqc-section-header">
        <span class="cqc-section-badge">Essential</span>
        <h2 class="cqc-section-title">Essential Guidance</h2>
        <p class="cqc-section-description">Must-read resources for all care providers</p>
      </div>
      
      <?php
      // Manually curated essential items (3-5 max)
      $essential_items = [
        [
          'title' => 'CQC Inspection Preparation Guide',
          'type' => 'guide',
          'url' => '#inspection-prep',
          'excerpt' => 'Complete checklist for inspection readiness',
          'updated' => '2024-01-15',
          'manual_new' => false,
        ],
        [
          'title' => 'Mandatory Training Requirements',
          'type' => 'reference',
          'url' => '#training-requirements',
          'excerpt' => 'What training is legally required by care setting',
          'updated' => '2024-01-10',
          'manual_new' => false,
        ],
        [
          'title' => '2026 Regulatory Changes: What You Need to Know',
          'type' => 'update',
          'url' => '#regulatory-updates',
          'excerpt' => 'New requirements and framework updates',
          'updated' => '2024-01-20',
          'manual_new' => true,
        ],
      ];
      ?>
      
      <div class="cqc-essential-grid">
        <?php foreach ($essential_items as $item) : 
          $is_new = cta_is_content_new(0, $item['manual_new']) || (strtotime($item['updated']) > strtotime('-30 days'));
        ?>
        <article class="cqc-essential-card">
          <span class="cqc-content-type cqc-content-type--<?php echo esc_attr($item['type']); ?>">
            <?php echo esc_html(ucfirst($item['type'])); ?>
            <?php if ($is_new) : ?><span class="cqc-new-badge">NEW</span><?php endif; ?>
          </span>
          <h3 class="cqc-essential-title"><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a></h3>
          <p class="cqc-essential-excerpt"><?php echo esc_html($item['excerpt']); ?></p>
          <time datetime="<?php echo esc_attr($item['updated']); ?>" class="cqc-updated-date">
            Updated <?php echo esc_html(human_time_diff(strtotime($item['updated']), current_time('timestamp'))); ?> ago
          </time>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  
  <!-- Most Viewed Resources Section -->
  <section class="cqc-section cqc-section-popular" id="most-viewed">
    <div class="container">
      <div class="cqc-section-header">
        <span class="cqc-section-badge">Popular</span>
        <h2 class="cqc-section-title">Most Viewed Resources</h2>
        <p class="cqc-section-description">Resources other providers find most helpful</p>
      </div>
      
      <?php
      // Manually curated popular items (4 items)
      $popular_items = [
        [
          'title' => 'CQC Inspection Preparation Guide',
          'type' => 'guide',
          'url' => '#inspection-prep',
          'excerpt' => 'Complete checklist for inspection readiness',
          'updated' => '2024-01-15',
          'manual_new' => false,
        ],
        [
          'title' => 'Mandatory Training Requirements Explained',
          'type' => 'reference',
          'url' => '#training-requirements',
          'excerpt' => 'What training is legally required by care setting',
          'updated' => '2024-01-10',
          'manual_new' => false,
        ],
        [
          'title' => '2026 Regulatory Changes: What You Need to Know',
          'type' => 'update',
          'url' => '#regulatory-updates',
          'excerpt' => 'New requirements and framework updates',
          'updated' => '2024-01-20',
          'manual_new' => true,
        ],
        [
          'title' => 'Training Record Templates',
          'type' => 'tool',
          'url' => get_permalink(get_page_by_path('downloadable-resources')),
          'excerpt' => 'Downloadable templates for tracking training',
          'updated' => '2024-01-05',
          'manual_new' => false,
        ],
      ];
      ?>
      
      <div class="cqc-popular-grid">
        <?php foreach ($popular_items as $item) : 
          $is_new = cta_is_content_new(0, $item['manual_new']) || (strtotime($item['updated']) > strtotime('-30 days'));
        ?>
        <article class="cqc-popular-card">
          <span class="cqc-content-type cqc-content-type--<?php echo esc_attr($item['type']); ?>">
            <?php echo esc_html(ucfirst($item['type'])); ?>
            <?php if ($is_new) : ?><span class="cqc-new-badge">NEW</span><?php endif; ?>
          </span>
          <h3 class="cqc-popular-title"><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a></h3>
          <p class="cqc-popular-excerpt"><?php echo esc_html($item['excerpt']); ?></p>
          <time datetime="<?php echo esc_attr($item['updated']); ?>" class="cqc-updated-date">
            Updated <?php echo esc_html(human_time_diff(strtotime($item['updated']), current_time('timestamp'))); ?> ago
          </time>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  
  <!-- CQC Training Requirements Section -->
  <?php get_template_part('template-parts/cqc-requirements-section'); ?>

  <!-- Mandatory Training by Setting Section (Hidden - data source for hero selector) -->
  <div style="display: none;" id="training-data-source">
    <div data-setting="domiciliary" data-title="Domiciliary Care">
      <ul>
        <li><?php echo cta_course_link('Care Certificate'); ?></li>
        <li><?php echo cta_course_link('Safeguarding Adults'); ?></li>
        <li><?php echo cta_course_link('Moving & Handling'); ?></li>
        <li><?php echo cta_course_link('First Aid'); ?></li>
        <li><?php echo cta_course_link('Medication Awareness'); ?></li>
        <li><?php echo cta_course_link('Infection Control'); ?></li>
        <li><?php echo cta_course_link('Health & Safety'); ?></li>
        <li><?php echo cta_course_link('Fire Safety'); ?></li>
        <li><?php echo cta_course_link('Food Hygiene'); ?> (if applicable)</li>
              <li>Lone Working Safety</li>
            </ul>
          </div>
    <div data-setting="residential" data-title="Residential Care Home">
      <ul>
        <li><?php echo cta_course_link('Care Certificate'); ?></li>
        <li><?php echo cta_course_link('Safeguarding Adults'); ?> & Children</li>
        <li><?php echo cta_course_link('Moving & Handling'); ?></li>
        <li><?php echo cta_course_link('First Aid'); ?></li>
        <li><?php echo cta_course_link('Medication Management'); ?></li>
        <li><?php echo cta_course_link('Infection Control'); ?></li>
        <li><?php echo cta_course_link('Health & Safety'); ?></li>
        <li><?php echo cta_course_link('Fire Safety'); ?></li>
        <li><?php echo cta_course_link('Food Hygiene'); ?></li>
        <li><?php echo cta_course_link('Dementia Care'); ?></li>
              <li>End of Life Care</li>
            </ul>
          </div>
    <div data-setting="nursing" data-title="Nursing Home">
      <ul>
        <li>All residential care requirements plus:</li>
              <li>Clinical Skills</li>
              <li>Wound Care</li>
              <li>Catheter Care</li>
              <li>PEG Feeding</li>
              <li>Diabetes Management</li>
              <li>Pressure Ulcer Prevention</li>
              <li>Clinical Governance</li>
            </ul>
          </div>
    <div data-setting="supported-living" data-title="Supported Living">
      <ul>
        <li><?php echo cta_course_link('Care Certificate'); ?></li>
        <li><?php echo cta_course_link('Safeguarding Adults'); ?></li>
        <li><?php echo cta_course_link('Moving & Handling'); ?></li>
        <li><?php echo cta_course_link('First Aid'); ?></li>
        <li><?php echo cta_course_link('Medication Management'); ?></li>
              <li>Learning Disabilities Awareness</li>
        <li><?php echo cta_course_link('Mental Capacity Act'); ?> & DoLS</li>
              <li>Positive Behaviour Support</li>
        <li><?php echo cta_course_link('Health & Safety'); ?></li>
        <li><?php echo cta_course_link('Fire Safety'); ?></li>
            </ul>
          </div>
    <div data-setting="complex-care" data-title="Complex Care">
      <ul>
        <li>All core care training plus:</li>
              <li>Clinical Skills</li>
              <li>Ventilator Care</li>
              <li>Tracheostomy Care</li>
              <li>Enteral Feeding</li>
              <li>Seizure Management</li>
              <li>Diabetes Management</li>
              <li>Epilepsy Awareness</li>
              <li>Specialist Health Conditions</li>
            </ul>
          </div>
        </div>

  <!-- CQC Inspection Preparation Section -->
  <section class="cqc-section cqc-section-inspection" id="inspection-prep" aria-labelledby="inspection-prep-heading">
    <div class="container">
      <div class="cqc-section-header">
        <span class="cqc-section-badge">Inspection</span>
        <h2 id="inspection-prep-heading" class="cqc-section-title">Inspection Preparation</h2>
        <p class="cqc-section-description">Be inspection-ready with organized training records and documentation</p>
      </div>
      
      <!-- Key Focus Areas -->
      <div class="cqc-inspection-intro">
        <div class="cqc-inspection-highlight">
          <div class="cqc-inspection-highlight-icon">
            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
          </div>
          <div class="cqc-inspection-highlight-content">
            <h3>What Inspectors Check</h3>
            <p>CQC inspectors verify all staff have completed <a href="<?php echo esc_url(get_permalink(get_page_by_path('faqs')) . '?category=general'); ?>">mandatory training</a>, certificates are current, and competency is documented.</p>
          </div>
        </div>
      </div>

      <!-- Accordion Sections -->
      <div class="cqc-inspection-accordions">
        <!-- What Inspectors Look For -->
        <div class="accordion" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="true" aria-controls="inspection-look-for">
            <span>
              <span class="accordion-icon-inline"><?php echo cta_get_cqc_icon('inspection'); ?></span>
              What Inspectors Look For in Training Records
            </span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="inspection-look-for" class="accordion-content" role="region" aria-hidden="false">
            <div class="cqc-checklist-grid">
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Completed training certificates with expiry dates</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span><a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>">Training matrices</a> showing who has completed what training</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Evidence of <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>">competency assessments</a></span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Training needs analysis and annual training plans</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Records of refresher training and updates</span>
              </div>
            </div>
          </div>
        </div>

        <!-- How to Organize -->
        <div class="accordion" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="inspection-organize">
            <span>
              <span class="accordion-icon-inline"><?php echo cta_get_cqc_icon('tool'); ?></span>
              How to Organize Your Training Evidence
            </span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="inspection-organize" class="accordion-content" role="region" aria-hidden="true">
            <div class="cqc-checklist-grid">
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Maintain a central training matrix for all staff</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Store <a href="<?php echo esc_url(get_permalink(get_page_by_path('faqs')) . '?category=certification'); ?>">certificates</a> digitally with expiry date tracking</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Document <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>">competency assessments</a> alongside certificates</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Keep training plans and needs analysis documents current</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Ensure managers can quickly access training records during inspections</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Common Inadequate Ratings -->
        <div class="accordion cqc-warning-item" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="inspection-inadequate">
            <span>
              <span class="accordion-icon-inline"><?php echo cta_get_cqc_icon('alert'); ?></span>
              Common Training-Related Inadequate Ratings
            </span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="inspection-inadequate" class="accordion-content" role="region" aria-hidden="true">
            <div class="cqc-checklist-grid">
              <div class="cqc-checklist-item cqc-warning">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                <span>Staff have not completed <a href="<?php echo esc_url(get_permalink(get_page_by_path('faqs')) . '?category=general'); ?>">mandatory training</a> within required timeframes</span>
              </div>
              <div class="cqc-checklist-item cqc-warning">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                <span><a href="<?php echo esc_url(get_permalink(get_page_by_path('faqs')) . '?category=certification'); ?>">Training certificates</a> have expired and not been renewed</span>
              </div>
              <div class="cqc-checklist-item cqc-warning">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                <span><a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>">Competency assessments</a> are missing or incomplete</span>
              </div>
              <div class="cqc-checklist-item cqc-warning">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                <span><a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>">Training records</a> are disorganized or inaccessible</span>
              </div>
              <div class="cqc-checklist-item cqc-warning">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                <span>New staff have not completed <a href="<?php echo esc_url(cta_find_course_link('Care Certificate') ?: get_post_type_archive_link('course')); ?>">induction training</a></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Best Practice -->
        <div class="accordion cqc-featured-item" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="inspection-best-practice">
            <span>
              <span class="accordion-icon-inline">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
              </span>
              Best Practice Documentation
            </span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="inspection-best-practice" class="accordion-content" role="region" aria-hidden="true">
            <div class="cqc-checklist-grid">
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Individual training records for each staff member</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Organizational training matrix showing all staff and courses</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Annual training plans aligned with service needs</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Training needs analysis documents</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span><a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>">Competency assessment records</a></span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Evidence of training delivery and attendance</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- CTA -->
      <div class="cqc-inspection-cta">
        <div class="cqc-inspection-cta-content">
          <h3>Get Inspection Ready</h3>
          <p>Download our comprehensive checklist to ensure your training records meet CQC standards</p>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="btn btn-primary btn-large">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
              <polyline points="7 10 12 15 17 10"/>
              <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Download Inspection Readiness Checklist
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- 2026 Regulatory Changes Section -->
  <section class="cqc-section cqc-section-regulatory" id="regulatory-updates" aria-labelledby="regulatory-changes-heading">
    <div class="container">
      <div class="cqc-section-header">
        <span class="cqc-section-badge cqc-badge-urgent">2026 Changes</span>
        <h2 id="regulatory-changes-heading" class="cqc-section-title">Regulatory Updates</h2>
        <p class="cqc-section-description">New requirements and framework changes</p>
      </div>
      
      <?php
      // Get regulatory cards from ACF or use defaults
      $regulatory_cards = get_field('regulatory_cards');
      if (empty($regulatory_cards)) {
        // Default cards with links
        $regulatory_cards = [
          [
            'label' => 'New Framework',
            'title' => 'Single Assessment Framework Updates',
            'content' => 'CQC is implementing a new Single Assessment Framework that streamlines inspections and focuses on outcomes. <a href="' . esc_url(get_permalink(get_page_by_path('faqs')) . '?category=general') . '">Training evidence</a> will continue to be a key component, with emphasis on demonstrating <a href="' . esc_url(get_permalink(get_page_by_path('downloadable-resources'))) . '">competency</a> and continuous improvement.',
            'link_url' => 'https://www.cqc.org.uk/guidance-providers/assessment-framework',
            'link_text' => 'Read CQC framework guidance',
            'highlight' => false,
          ],
          [
            'label' => 'Mandatory Training',
            'title' => 'New Mandatory Training Requirements',
            'content' => 'Several new mandatory training requirements are being introduced in 2026:',
            'list_items' => [
              'Oliver McGowan Mandatory Training on Learning Disability and Autism (becoming statutory)',
              'Enhanced <a href="' . esc_url(cta_find_course_link('Safeguarding') ?: get_post_type_archive_link('course')) . '">safeguarding training</a> requirements',
              'Updated <a href="' . esc_url(cta_find_course_link('Medication Management') ?: get_post_type_archive_link('course')) . '">medication management</a> competencies',
              'Refreshed <a href="' . esc_url(cta_find_course_link('Infection Control') ?: get_post_type_archive_link('course')) . '">infection prevention and control</a> standards',
            ],
            'link_url' => get_permalink(get_page_by_path('faqs')) . '?category=general',
            'link_text' => 'View mandatory training FAQs',
            'highlight' => false,
          ],
          [
            'label' => 'Statutory Requirement',
            'title' => 'Oliver McGowan Training Becoming Statutory',
            'content' => 'The Oliver McGowan Mandatory Training on Learning Disability and Autism will become a legal requirement for all health and social care staff in 2026. This training is essential for services supporting people with learning disabilities and autism.',
            'link_url' => 'https://www.gov.uk/government/publications/the-oliver-mcgowan-mandatory-training-on-learning-disability-and-autism',
            'link_text' => 'Read official guidance on Oliver McGowan training',
            'highlight' => true,
          ],
          [
            'label' => 'Timeline',
            'title' => 'Timeline of Upcoming Changes',
            'list_items' => [
              '<strong>Q1 2026:</strong> Single Assessment Framework fully implemented',
              '<strong>Q2 2026:</strong> <a href="https://www.gov.uk/government/publications/the-oliver-mcgowan-mandatory-training-on-learning-disability-and-autism">Oliver McGowan training</a> becomes statutory',
              '<strong>Q3 2026:</strong> Updated <a href="' . esc_url(cta_find_course_link('Safeguarding') ?: get_post_type_archive_link('course')) . '">safeguarding</a> requirements in effect',
              '<strong>Q4 2026:</strong> Enhanced <a href="' . esc_url(cta_find_course_link('Medication Management') ?: get_post_type_archive_link('course')) . '">medication competency</a> standards',
            ],
            'link_url' => get_permalink(get_page_by_path('faqs')) . '?category=general',
            'link_text' => 'View training renewal FAQs',
            'highlight' => false,
            'timeline' => true,
          ],
          [
            'label' => 'Our Commitment',
            'title' => 'How CTA Courses Meet New Standards',
            'content' => 'All <a href="' . esc_url(get_post_type_archive_link('course')) . '">Continuity Training Academy courses</a> are designed to meet current and upcoming <a href="' . esc_url(get_permalink(get_page_by_path('faqs')) . '?category=general') . '">CQC requirements</a>. We regularly update our course content to align with regulatory changes and ensure your training remains compliant.',
            'link_url' => get_permalink(get_page_by_path('about')),
            'link_text' => 'Learn about our training approach',
            'highlight' => false,
            'cta' => true,
          ],
        ];
      }
      ?>
      
      <div class="cqc-regulatory-grid">
        <?php foreach ($regulatory_cards as $card) : 
          $card_class = 'cqc-regulatory-card';
          if (!empty($card['highlight'])) $card_class .= ' cqc-regulatory-card-highlight';
          if (!empty($card['cta'])) $card_class .= ' cqc-regulatory-card-cta';
          
          $label_class = 'cqc-regulatory-label';
          if (!empty($card['highlight'])) $label_class .= ' cqc-regulatory-label-important';
          
          $list_class = !empty($card['timeline']) ? 'cqc-timeline-list' : '';
        ?>
        <div class="<?php echo esc_attr($card_class); ?>">
          <?php if (!empty($card['link_url'])) : ?>
          <a href="<?php echo esc_url($card['link_url']); ?>" class="cqc-regulatory-card-link cqc-card-linkable" aria-label="<?php echo esc_attr($card['title'] . ' - ' . (!empty($card['link_text']) ? $card['link_text'] : 'Learn more')); ?>">
          <?php endif; ?>
          
          <div class="<?php echo esc_attr($label_class); ?>">
            <span><?php echo esc_html($card['label']); ?></span>
          </div>
          <h3 class="cqc-regulatory-title"><?php echo esc_html($card['title']); ?></h3>
          
          <?php if (!empty($card['content'])) : ?>
          <p><?php echo wp_kses_post($card['content']); ?></p>
          <?php endif; ?>
          
          <?php if (!empty($card['list_items'])) : ?>
          <ul class="<?php echo esc_attr($list_class); ?>">
            <?php foreach ($card['list_items'] as $item) : ?>
            <li><?php echo wp_kses_post($item); ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          
          <?php if (!empty($card['link_url'])) : ?>
          <div class="cqc-regulatory-card-footer">
            <span class="cqc-regulatory-link-text"><?php echo esc_html($card['link_text'] ?: 'Learn more'); ?></span>
            <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M7 17L17 7M7 7h10v10"></path>
            </svg>
        </div>
          <?php endif; ?>
          
          <?php if (!empty($card['link_url'])) : ?>
          </a>
          <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CQC Compliance Updates Section -->
  <?php if ($cqc_query->have_posts()) : ?>
  <section class="cqc-section cqc-section-updates" aria-labelledby="cqc-articles-heading">
    <div class="container">
      <div class="cqc-section-header">
        <span class="cqc-section-badge">Updates</span>
        <h2 id="cqc-articles-heading" class="cqc-section-title">CQC Compliance Updates</h2>
        <p class="cqc-section-description">Recent guidance and regulatory changes</p>
      </div>
      
      <div class="news-articles-grid">
        <div class="cqc-updates-grid">
        <?php while ($cqc_query->have_posts()) : $cqc_query->the_post();
          $categories = get_the_category();
          $category = $categories && !is_wp_error($categories) ? $categories[0] : null;
          $read_time = get_field('read_time') ?: cta_reading_time(get_the_content());
          $is_new = cta_is_content_new(get_the_ID(), get_field('content_new_flag'));
        ?>
        <article class="cqc-update-card">
          <span class="cqc-content-type cqc-content-type--article">
            Article
            <?php if ($is_new) : ?><span class="cqc-new-badge">NEW</span><?php endif; ?>
          </span>
          
          <div class="cqc-update-image-wrapper">
            <?php if (has_post_thumbnail()) : ?>
            <a href="<?php the_permalink(); ?>">
              <?php the_post_thumbnail('medium_large', ['class' => 'cqc-update-image', 'loading' => 'lazy']); ?>
            </a>
            <?php else : ?>
            <div class="cqc-update-image-placeholder" aria-hidden="true">
              <?php echo cta_get_cqc_icon('book'); ?>
            </div>
            <?php endif; ?>
          </div>
          
          <div class="cqc-update-content">
            <div class="cqc-update-meta">
              <?php if ($category) : ?>
              <span class="cqc-update-category"><?php echo esc_html($category->name); ?></span>
              <?php endif; ?>
              <time datetime="<?php echo esc_attr(get_the_date('c')); ?>" class="cqc-update-date">
                <?php echo get_the_date('j M Y'); ?>
              </time>
              <?php if ($read_time) : ?>
              <span class="cqc-update-read-time"><?php echo esc_html($read_time); ?> min read</span>
              <?php endif; ?>
            </div>
            
            <h3 class="cqc-update-title">
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            
            <p class="cqc-update-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
            
            <time datetime="<?php echo esc_attr(get_the_modified_date('c')); ?>" class="cqc-updated-date">
              Updated <?php echo esc_html(cta_get_content_updated_date(get_the_ID())); ?>
            </time>
            
            <a href="<?php the_permalink(); ?>" class="cqc-update-link">
              Read More
              <svg class="cqc-update-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </a>
          </div>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
        </div>
      </div>
      
      <div class="cta-center">
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('news'))); ?>" class="btn btn-secondary resource-card-btn">View All Articles</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- CQC Training Courses Section -->
  <?php if ($cqc_courses->have_posts()) : ?>
  <section class="courses-listing-section" aria-labelledby="cqc-courses-heading">
    <div class="container">
      <div class="courses-listing-header">
        <h2 id="cqc-courses-heading" class="section-title">CQC-Compliant Training Courses</h2>
        <p class="section-subtitle">Essential training courses to meet CQC requirements</p>
      </div>
      
      <div class="courses-grid">
        <?php while ($cqc_courses->have_posts()) : $cqc_courses->the_post();
          $duration = function_exists('get_field') ? get_field('course_duration') : '';
          $price = function_exists('get_field') ? get_field('course_price') : '';
          $accreditation = function_exists('get_field') ? get_field('course_accreditation') : '';
          $terms = get_the_terms(get_the_ID(), 'course_category');
          $category_name = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
        ?>
        <article class="course-card">
          <?php if (has_post_thumbnail()) : ?>
          <div class="course-image-wrapper">
            <a href="<?php the_permalink(); ?>">
              <?php the_post_thumbnail('medium_large', ['class' => 'course-image', 'loading' => 'lazy']); ?>
            </a>
          </div>
          <?php endif; ?>
          
          <div class="course-card-header">
            <?php if ($category_name) : ?>
            <span class="course-badge"><?php echo esc_html($category_name); ?></span>
            <?php endif; ?>
            <h3 class="course-card-title">
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
          </div>
          
          <div class="course-card-body">
            <?php if (has_excerpt()) : ?>
            <p class="course-card-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>
            <?php endif; ?>
            
            <div class="course-card-meta">
              <?php if ($duration) : ?>
              <span class="course-meta-item">
                <i class="fas fa-clock" aria-hidden="true"></i>
                <?php echo esc_html($duration); ?>
              </span>
              <?php endif; ?>
              
              <?php if ($accreditation) : ?>
              <span class="course-meta-item">
                <i class="fas fa-certificate" aria-hidden="true"></i>
                <?php echo esc_html($accreditation); ?>
              </span>
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
            <a href="<?php the_permalink(); ?>" class="course-read-more-btn">Read More</a>
          </div>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>

      
      <div class="cta-center large-spacing">
        <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-secondary">View All Courses</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- FAQ Section -->
  <?php if (!empty($faqs)) : ?>
  <section class="content-section bg-light-cream" aria-labelledby="cqc-faq-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="cqc-faq-heading" class="section-title">FAQs About CQC Compliance</h2>
        <p class="section-description">Common questions about CQC requirements and training</p>
      </div>
      
      <div class="cqc-faq-wrapper">
        <?php foreach ($faqs as $index => $faq) :
          if (!is_array($faq) || !isset($faq['question']) || !isset($faq['answer'])) {
            continue;
          }
        ?>
        <div class="accordion" data-accordion-group="cqc-faq">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-faq-<?php echo (int) $index; ?>">
            <span><?php echo esc_html($faq['question']); ?></span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="cqc-faq-<?php echo (int) $index; ?>" class="accordion-content" role="region" aria-hidden="true">
            <?php echo wpautop(wp_kses_post($faq['answer'])); ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Tools & Resources Section -->
  <section class="cqc-section cqc-section-tools" id="tools-resources" aria-labelledby="cqc-tools-heading">
    <div class="container">
      <div class="cqc-section-header">
        <span class="cqc-section-badge">Tools</span>
        <h2 id="cqc-tools-heading" class="cqc-section-title">Tools & Resources</h2>
        <p class="cqc-section-description">Checklists, templates, and downloadable resources</p>
      </div>
      
      <div class="cqc-toolkit-grid">
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="cqc-toolkit-item">
          <div class="cqc-toolkit-icon">
            <?php echo cta_get_cqc_icon('checklist'); ?>
          </div>
          <strong class="cqc-toolkit-title">Inspection Checklist</strong>
          <span class="cqc-toolkit-desc">Download PDF</span>
        </a>
        
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="cqc-toolkit-item">
          <div class="cqc-toolkit-icon">
            <?php echo cta_get_cqc_icon('grid'); ?>
          </div>
          <strong class="cqc-toolkit-title">Training Matrix Template</strong>
          <span class="cqc-toolkit-desc">Download Excel</span>
        </a>
        
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="cqc-toolkit-item">
          <div class="cqc-toolkit-icon">
            <?php echo cta_get_cqc_icon('calendar'); ?>
          </div>
          <strong class="cqc-toolkit-title">Renewal Calendar</strong>
          <span class="cqc-toolkit-desc">Interactive tool</span>
        </a>
        
        <a href="#essential-guidance" class="cqc-toolkit-item">
          <div class="cqc-toolkit-icon">
            <?php echo cta_get_cqc_icon('book'); ?>
          </div>
          <strong class="cqc-toolkit-title">Essential Reading</strong>
          <span class="cqc-toolkit-desc">View curated list</span>
        </a>
        
        <a href="#cqc-faq-heading" class="cqc-toolkit-item">
          <div class="cqc-toolkit-icon">
            <?php echo cta_get_cqc_icon('question'); ?>
          </div>
          <strong class="cqc-toolkit-title">FAQs</strong>
          <span class="cqc-toolkit-desc">Common questions answered</span>
        </a>
        
        <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="cqc-toolkit-item">
          <div class="cqc-toolkit-icon">
            <?php echo cta_get_cqc_icon('book'); ?>
          </div>
          <strong class="cqc-toolkit-title">All Courses</strong>
          <span class="cqc-toolkit-desc">Browse training courses</span>
        </a>
      </div>
    </div>
  </section>
  
  <!-- Downloadable CQC Resources Section (Legacy - keep for backward compatibility) -->
  <?php 
  $downloadable_resources = get_field('downloadable_resources');
  if ($downloadable_resources && is_array($downloadable_resources) && !empty($downloadable_resources)) : 
  ?>
  <section class="content-section" aria-labelledby="cqc-resources-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="cqc-resources-heading" class="section-title">Downloadable CQC Resources</h2>
        <p class="section-description">Free templates, checklists, and tools to support your CQC compliance</p>
      </div>
      
      <div class="cqc-resources-grid">
        <?php foreach ($downloadable_resources as $item) : 
          $resource = $item['resource'] ?? null;
          if (!$resource || !is_object($resource)) continue;
          
          $resource_id = (int) $resource->ID;
          $resource_title = get_the_title($resource_id);
          $resource_excerpt = has_excerpt($resource_id) ? get_the_excerpt($resource_id) : '';
          
          // Get icon from ACF field or use resource's default icon
          $custom_icon = $item['custom_icon'] ?? '';
          if (empty($custom_icon)) {
            $custom_icon = get_post_meta($resource_id, '_cta_resource_icon', true);
          }
          if (empty($custom_icon)) {
            $custom_icon = 'fas fa-file';
          }
        ?>
        <div class="cqc-resource-card">
          <div class="cqc-resource-icon">
            <i class="<?php echo esc_attr($custom_icon); ?>" aria-hidden="true"></i>
          </div>
          <h3 class="cqc-resource-title"><?php echo esc_html($resource_title); ?></h3>
          <p class="cqc-resource-description"><?php echo esc_html($resource_excerpt); ?></p>
          <button 
            type="button"
            class="cqc-resource-link resource-download-btn" 
            data-resource-id="<?php echo esc_attr($resource_id); ?>"
            data-resource-name="<?php echo esc_attr($resource_title); ?>"
            style="border: none; background: none; cursor: pointer; padding: 0; font: inherit; color: inherit; text-align: left; width: 100%; display: inline-flex; align-items: center; gap: 8px;"
          >
            Get via Email
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  
  <!-- Subscription Bar (Sticky Footer) -->
  <aside class="cqc-subscription-bar" id="cqc-subscription-bar" aria-label="Subscribe for updates">
    <div class="container">
      <button type="button" class="cqc-subscription-close" aria-label="Close subscription bar">×</button>
      <div class="cqc-subscription-content">
        <strong class="cqc-subscription-title">Stay Updated on CQC Changes</strong>
        <p class="cqc-subscription-description">Get notified when new regulations or guidance are published</p>
        <form class="cqc-subscription-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="cqc_subscribe">
          <?php wp_nonce_field('cqc_subscribe', 'cqc_subscribe_nonce'); ?>
          <label for="cqc-email" class="sr-only">Email address</label>
          <input 
            type="email" 
            id="cqc-email" 
            name="email" 
            placeholder="your@email.com"
            required
            class="cqc-subscription-input"
          >
          <button type="submit" class="btn btn-primary">Subscribe</button>
        </form>
      </div>
    </div>
  </aside>
</main>

<!-- Schema.org Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "CQC Compliance Hub - Complete Training Guide",
  "description": "Complete guide to CQC training compliance. 5 Key Questions, mandatory training requirements, inspection preparation, and regulatory updates.",
  "url": "<?php echo esc_url(get_permalink()); ?>",
  "mainEntity": {
    "@type": "FAQPage",
    "mainEntity": [
      {
        "@type": "Question",
        "name": "What training is required for domiciliary care?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Required courses include: Care Certificate, Safeguarding Adults, Moving & Handling, First Aid, Medication Awareness, Infection Control, Health & Safety, Fire Safety, Food Hygiene (if applicable), and Lone Working Safety."
        }
      },
      {
        "@type": "Question",
        "name": "What training is required for residential care homes?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Required courses include: Care Certificate, Safeguarding Adults & Children, Moving & Handling, First Aid, Medication Management, Infection Control, Health & Safety, Fire Safety, Food Hygiene, Dementia Care, and End of Life Care."
        }
      },
      {
        "@type": "Question",
        "name": "What training is required for nursing homes?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "All residential care requirements plus: Clinical Skills, Wound Care, Catheter Care, PEG Feeding, Diabetes Management, Pressure Ulcer Prevention, and Clinical Governance."
        }
      }
    ]
  },
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "<?php echo esc_url(home_url('/')); ?>"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "CQC Compliance Hub",
        "item": "<?php echo esc_url(get_permalink()); ?>"
      }
    ]
  },
  "about": {
    "@type": "Thing",
    "name": "CQC Compliance",
    "description": "Care Quality Commission compliance training and regulatory requirements for care providers"
  },
  "publisher": {
    "@type": "EducationalOrganization",
    "name": "Continuity Training Academy",
    "url": "<?php echo esc_url(home_url('/')); ?>"
  }
}
</script>

<script>
(function() {
  'use strict';
  
  // FAQ/Accordion functionality
  const faqButtons = document.querySelectorAll('.group-faq-question');
  
  faqButtons.forEach(button => {
    button.addEventListener('click', function() {
      const isExpanded = this.getAttribute('aria-expanded') === 'true';
      const answerId = this.getAttribute('aria-controls');
      const answer = document.getElementById(answerId);
      
      // Toggle this accordion
      this.setAttribute('aria-expanded', !isExpanded);
      answer.setAttribute('aria-hidden', isExpanded);
    });
  });
})();
</script>

<?php
get_footer();
