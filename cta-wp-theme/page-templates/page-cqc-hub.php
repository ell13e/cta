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
    $intro_text = 'The Care Quality Commission (CQC) sets standards for health and social care services in England. Our <a href="' . esc_url(get_post_type_archive_link('course')) . '">CQC-compliant training courses</a> help care providers meet these requirements and prepare for inspections.';
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
              '<li>' . (cta_find_course_link('First Aid') ? '<a href="' . esc_url(cta_find_course_link('First Aid')) . '">First Aid at Work</a>' : 'First Aid at Work') . '</li>' .
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
            'answer' => 'Most CQC <a href="' . esc_url(get_permalink(get_page_by_path('faqs')) . '?category=general') . '">mandatory training</a> should be refreshed annually. <a href="' . esc_url(cta_find_course_link('First Aid') ?: get_post_type_archive_link('course')) . '">First Aid at Work</a> certificates typically last 3 years. <a href="' . esc_url(cta_find_course_link('Safeguarding') ?: get_post_type_archive_link('course')) . '">Safeguarding</a> training should be refreshed every 2-3 years. Check specific <a href="' . esc_url(get_post_type_archive_link('course')) . '">course requirements</a> for exact refresh periods.',
        ],
        [
            'category' => 'training',
            'question' => 'Is online training accepted by CQC?',
            'answer' => 'CQC accepts a mix of online and face-to-face training, but some topics (like <a href="' . esc_url(cta_find_course_link('First Aid') ?: get_post_type_archive_link('course')) . '">practical first aid</a>) require hands-on training. Our <a href="' . esc_url(get_permalink(get_page_by_path('group-training'))) . '">face-to-face courses</a> ensure all practical elements are covered to CQC standards.',
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
  <!-- Hero Section -->
  <section class="group-hero-section" aria-labelledby="cqc-heading">
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
      <div class="group-hero-cta">
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="btn btn-primary group-hero-btn-primary">Download CQC Training Checklist</a>
      </div>
    </div>
  </section>

  <!-- CQC Training Requirements Section -->
  <?php get_template_part('template-parts/cqc-requirements-section'); ?>

  <!-- Mandatory Training by Setting Section -->
  <?php
  $mandatory_training_title = function_exists('get_field') ? get_field('mandatory_training_title') : '';
  $mandatory_training_description = function_exists('get_field') ? get_field('mandatory_training_description') : '';
  $mandatory_training_settings = function_exists('get_field') ? get_field('mandatory_training_settings') : [];
  
  if (empty($mandatory_training_title)) {
    $mandatory_training_title = 'Mandatory Training by Setting';
  }
  if (empty($mandatory_training_description)) {
    $mandatory_training_description = 'Training requirements vary by care setting. Find what\'s required for your service type.';
  }
  ?>
  <section class="content-section bg-light-cream" aria-labelledby="mandatory-training-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="mandatory-training-heading" class="section-title"><?php echo esc_html($mandatory_training_title); ?></h2>
        <p class="section-description"><?php echo esc_html($mandatory_training_description); ?></p>
      </div>
      
      <div class="cqc-accordion-wrapper">
        <?php if (!empty($mandatory_training_settings) && is_array($mandatory_training_settings)) : 
          // Use ACF fields if available
          foreach ($mandatory_training_settings as $setting) :
            $setting_name = $setting['setting_name'] ?? '';
            $setting_id = $setting['setting_id'] ?? '';
            $setting_intro = $setting['setting_intro'] ?? '';
            $setting_courses = $setting['setting_courses'] ?? '';
            
            if (empty($setting_name) || empty($setting_id) || empty($setting_courses)) continue;
        ?>
        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-setting-<?php echo esc_attr($setting_id); ?>">
            <span><?php echo esc_html($setting_name); ?></span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="cqc-setting-<?php echo esc_attr($setting_id); ?>" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <?php if (!empty($setting_intro)) : ?>
            <p><?php echo wp_kses_post($setting_intro); ?></p>
            <?php endif; ?>
            <ul class="list-two-column-gold">
              <?php 
              $courses = explode("\n", $setting_courses);
              foreach ($courses as $course) {
                $course = trim($course);
                if (empty($course)) continue;
                // Check if it has a note in parentheses
                $note = '';
                if (preg_match('/^(.+?)\s*\((.+?)\)$/', $course, $matches)) {
                  $course = trim($matches[1]);
                  $note = ' (' . esc_html($matches[2]) . ')';
                }
                echo '<li>' . cta_course_link($course) . $note . '</li>';
              }
              ?>
            </ul>
          </div>
        </div>
        <?php 
          endforeach;
        else :
          // Default hardcoded content
        ?>
        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-setting-domiciliary">
            <span>Domiciliary Care</span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="cqc-setting-domiciliary" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <ul class="list-two-column-gold">
              <li><?php echo cta_course_link('Care Certificate'); ?></li>
              <li><?php echo cta_course_link('Safeguarding Adults'); ?></li>
              <li><?php echo cta_course_link('Moving & Handling'); ?></li>
              <li><?php echo cta_course_link('First Aid', 'First Aid at Work'); ?></li>
              <li><?php echo cta_course_link('Medication Awareness'); ?></li>
              <li><?php echo cta_course_link('Infection Control'); ?></li>
              <li><?php echo cta_course_link('Health & Safety'); ?></li>
              <li><?php echo cta_course_link('Fire Safety'); ?></li>
              <li><?php echo cta_course_link('Food Hygiene'); ?> (if applicable)</li>
              <li><?php echo cta_course_link('Lone Working Safety') ?: 'Lone Working Safety'; ?></li>
            </ul>
          </div>
        </div>

        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-setting-residential">
            <span>Residential Care Home</span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="cqc-setting-residential" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <ul class="list-two-column-gold">
              <li><?php echo cta_course_link('Care Certificate'); ?></li>
              <li><?php echo cta_course_link('Safeguarding Adults'); ?> & Children</li>
              <li><?php echo cta_course_link('Moving & Handling'); ?></li>
              <li><?php echo cta_course_link('First Aid', 'First Aid at Work'); ?></li>
              <li><?php echo cta_course_link('Medication Management'); ?></li>
              <li><?php echo cta_course_link('Infection Control'); ?></li>
              <li><?php echo cta_course_link('Health & Safety'); ?></li>
              <li><?php echo cta_course_link('Fire Safety'); ?></li>
              <li><?php echo cta_course_link('Food Hygiene'); ?></li>
              <li><?php echo cta_course_link('Dementia Care'); ?></li>
              <li><?php echo cta_course_link('End of Life Care') ?: 'End of Life Care'; ?></li>
            </ul>
          </div>
        </div>

        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-setting-nursing">
            <span>Nursing Home</span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="cqc-setting-nursing" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <p>All <a href="#cqc-setting-residential">residential care requirements</a> plus:</p>
            <ul>
              <li><?php echo cta_course_link('Clinical Skills') ?: 'Clinical Skills'; ?></li>
              <li><?php echo cta_course_link('Wound Care') ?: 'Wound Care'; ?></li>
              <li><?php echo cta_course_link('Catheter Care') ?: 'Catheter Care'; ?></li>
              <li><?php echo cta_course_link('PEG Feeding') ?: 'PEG Feeding'; ?></li>
              <li><?php echo cta_course_link('Diabetes Management') ?: 'Diabetes Management'; ?></li>
              <li><?php echo cta_course_link('Pressure Ulcer Prevention') ?: 'Pressure Ulcer Prevention'; ?></li>
              <li><?php echo cta_course_link('Clinical Governance') ?: 'Clinical Governance'; ?></li>
            </ul>
          </div>
        </div>

        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-setting-supported-living">
            <span>Supported Living</span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="cqc-setting-supported-living" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <ul class="list-two-column-gold">
              <li><?php echo cta_course_link('Care Certificate'); ?></li>
              <li><?php echo cta_course_link('Safeguarding Adults'); ?></li>
              <li><?php echo cta_course_link('Moving & Handling'); ?></li>
              <li><?php echo cta_course_link('First Aid', 'First Aid at Work'); ?></li>
              <li><?php echo cta_course_link('Medication Management'); ?></li>
              <li><?php echo cta_course_link('Learning Disabilities') ?: 'Learning Disabilities Awareness'; ?></li>
              <li><?php echo cta_course_link('Mental Capacity Act'); ?> & DoLS</li>
              <li><?php echo cta_course_link('Positive Behaviour Support') ?: 'Positive Behaviour Support'; ?></li>
              <li><?php echo cta_course_link('Health & Safety'); ?></li>
              <li><?php echo cta_course_link('Fire Safety'); ?></li>
            </ul>
          </div>
        </div>

        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-setting-complex-care">
            <span>Complex Care</span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="cqc-setting-complex-care" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <p>All <a href="<?php echo esc_url(get_permalink(get_page_by_path('faqs')) . '?category=general'); ?>">core care training</a> plus:</p>
            <ul>
              <li><?php echo cta_course_link('Clinical Skills') ?: 'Clinical Skills'; ?></li>
              <li><?php echo cta_course_link('Ventilator Care') ?: 'Ventilator Care'; ?></li>
              <li><?php echo cta_course_link('Tracheostomy Care') ?: 'Tracheostomy Care'; ?></li>
              <li><?php echo cta_course_link('Enteral Feeding') ?: 'Enteral Feeding'; ?></li>
              <li><?php echo cta_course_link('Seizure Management') ?: 'Seizure Management'; ?></li>
              <li><?php echo cta_course_link('Diabetes Management') ?: 'Diabetes Management'; ?></li>
              <li><?php echo cta_course_link('Epilepsy Awareness') ?: 'Epilepsy Awareness'; ?></li>
              <li><?php echo cta_course_link('Specialist Health Conditions') ?: 'Specialist Health Conditions'; ?></li>
            </ul>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- CQC Inspection Preparation Section -->
  <?php
  $inspection_title = function_exists('get_field') ? get_field('inspection_title') : '';
  $inspection_description = function_exists('get_field') ? get_field('inspection_description') : '';
  $inspection_highlight_title = function_exists('get_field') ? get_field('inspection_highlight_title') : '';
  $inspection_highlight_text = function_exists('get_field') ? get_field('inspection_highlight_text') : '';
  $inspection_accordions = function_exists('get_field') ? get_field('inspection_accordions') : [];
  $inspection_cta_title = function_exists('get_field') ? get_field('inspection_cta_title') : '';
  $inspection_cta_text = function_exists('get_field') ? get_field('inspection_cta_text') : '';
  $inspection_cta_button_text = function_exists('get_field') ? get_field('inspection_cta_button_text') : '';
  $inspection_cta_link = function_exists('get_field') ? get_field('inspection_cta_link') : '';
  
  if (empty($inspection_title)) {
    $inspection_title = 'CQC Inspection Preparation';
  }
  if (empty($inspection_description)) {
    $inspection_description = 'Be inspection-ready with organized <a href="' . esc_url(get_permalink(get_page_by_path('downloadable-resources'))) . '">training records</a> and documentation';
  }
  if (empty($inspection_highlight_title)) {
    $inspection_highlight_title = 'What Inspectors Check';
  }
  if (empty($inspection_highlight_text)) {
    $inspection_highlight_text = 'CQC inspectors verify all staff have completed <a href="' . esc_url(get_permalink(get_page_by_path('faqs')) . '?category=general') . '">mandatory training</a>, certificates are current, and competency is documented.';
  }
  if (empty($inspection_cta_title)) {
    $inspection_cta_title = 'Get Inspection Ready';
  }
  if (empty($inspection_cta_text)) {
    $inspection_cta_text = 'Download our comprehensive checklist to ensure your training records meet CQC standards';
  }
  if (empty($inspection_cta_button_text)) {
    $inspection_cta_button_text = 'Download Inspection Readiness Checklist';
  }
  if (empty($inspection_cta_link)) {
    $inspection_cta_link = get_permalink(get_page_by_path('downloadable-resources'));
  }
  ?>
  <section class="content-section" aria-labelledby="inspection-prep-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="inspection-prep-heading" class="section-title"><?php echo esc_html($inspection_title); ?></h2>
        <p class="section-description"><?php echo wp_kses_post($inspection_description); ?></p>
      </div>
      
      <!-- Key Focus Areas -->
      <div class="cqc-inspection-intro">
        <div class="cqc-inspection-highlight">
          <div class="cqc-inspection-highlight-icon">
            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
          </div>
          <div class="cqc-inspection-highlight-content">
            <h3><?php echo esc_html($inspection_highlight_title); ?></h3>
            <p><?php echo wp_kses_post($inspection_highlight_text); ?></p>
          </div>
        </div>
      </div>

      <!-- Accordion Sections -->
      <div class="cqc-inspection-accordions">
        <?php if (!empty($inspection_accordions) && is_array($inspection_accordions)) : 
          // Use ACF fields if available
          foreach ($inspection_accordions as $index => $accordion) :
            $accordion_title = $accordion['title'] ?? '';
            $accordion_icon = $accordion['icon'] ?? 'fas fa-check-circle';
            $accordion_icon_color = $accordion['icon_color'] ?? '#35938d';
            $accordion_expanded = !empty($accordion['expanded']);
            $accordion_warning = !empty($accordion['warning']);
            $accordion_items = $accordion['items'] ?? '';
            
            if (empty($accordion_title) || empty($accordion_items)) continue;
            
            $accordion_id = 'inspection-accordion-' . $index;
            $accordion_class = 'accordion';
            if ($accordion_warning) {
              $accordion_class .= ' cqc-warning-item';
            }
        ?>
        <div class="<?php echo esc_attr($accordion_class); ?>" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="<?php echo $accordion_expanded ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr($accordion_id); ?>">
            <span>
              <?php if (!empty($accordion_icon)) : ?>
              <i class="<?php echo esc_attr($accordion_icon); ?>" aria-hidden="true" style="margin-right: 12px; color: <?php echo esc_attr($accordion_icon_color); ?>;"></i>
              <?php endif; ?>
              <?php echo esc_html($accordion_title); ?>
            </span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="<?php echo esc_attr($accordion_id); ?>" class="accordion-content" role="region" aria-hidden="<?php echo $accordion_expanded ? 'false' : 'true'; ?>">
            <div class="cqc-checklist-grid">
              <?php 
              $items = array_filter(array_map('trim', explode("\n", $accordion_items)));
              foreach ($items as $item) :
                $item_class = 'cqc-checklist-item';
                if ($accordion_warning) {
                  $item_class .= ' cqc-warning';
                }
                $icon_class = $accordion_warning ? 'fas fa-times-circle' : 'fas fa-check-circle';
              ?>
              <div class="<?php echo esc_attr($item_class); ?>">
                <i class="<?php echo esc_attr($icon_class); ?>" aria-hidden="true"></i>
                <span><?php echo wp_kses_post($item); ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php 
          endforeach;
        else :
          // Default hardcoded content
        ?>
        <!-- Default hardcoded accordions -->
        <div class="accordion" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="true" aria-controls="inspection-look-for">
            <span>
              <i class="fas fa-search" aria-hidden="true" style="margin-right: 12px; color: #35938d;"></i>
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

        <div class="accordion" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="inspection-organize">
            <span>
              <i class="fas fa-folder-open" aria-hidden="true" style="margin-right: 12px; color: #35938d;"></i>
              How to Organize Your Training Evidence
            </span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="inspection-organize" class="accordion-content" role="region" aria-hidden="true">
            <div class="cqc-checklist-grid">
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Maintain a central <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>">training matrix</a> for all staff</span>
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
                <span>Ensure managers can quickly access <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>">training records</a> during inspections</span>
              </div>
            </div>
          </div>
        </div>

        <div class="accordion cqc-warning-item" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="inspection-inadequate">
            <span>
              <i class="fas fa-exclamation-triangle" aria-hidden="true" style="margin-right: 12px; color: #d97706;"></i>
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

        <div class="accordion cqc-featured-item" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="inspection-best-practice">
            <span>
              <i class="fas fa-star" aria-hidden="true" style="margin-right: 12px; color: #9b8560;"></i>
              Best Practice Documentation
            </span>
            <span class="accordion-icon" aria-hidden="true"></span>
          </button>
          <div id="inspection-best-practice" class="accordion-content" role="region" aria-hidden="true">
            <div class="cqc-checklist-grid">
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Individual <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>">training records</a> for each staff member</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Organizational <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>">training matrix</a> showing all staff and courses</span>
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
        <?php endif; ?>
      </div>
      
      <!-- CTA -->
      <div class="cqc-inspection-cta">
        <div class="cqc-inspection-cta-content">
          <h3><?php echo esc_html($inspection_cta_title); ?></h3>
          <p><?php echo esc_html($inspection_cta_text); ?></p>
          <a href="<?php echo esc_url($inspection_cta_link); ?>" class="btn btn-primary btn-large">
            <i class="fas fa-download" aria-hidden="true"></i>
            <?php echo esc_html($inspection_cta_button_text); ?>
          </a>
        </div>
      </div>
        <?php endif; ?>
    </div>
  </section>

  <!-- 2026 Regulatory Changes Section -->
  <?php
  $regulatory_title = function_exists('get_field') ? get_field('regulatory_title') : '';
  $regulatory_description = function_exists('get_field') ? get_field('regulatory_description') : '';
  $regulatory_cards = function_exists('get_field') ? get_field('regulatory_cards') : [];
  
  if (empty($regulatory_title)) {
    $regulatory_title = '2026 Regulatory Changes';
  }
  if (empty($regulatory_description)) {
    $regulatory_description = 'Stay ahead of upcoming CQC framework updates and new training requirements';
  }
  ?>
  <section class="content-section bg-light-cream" aria-labelledby="regulatory-changes-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="regulatory-changes-heading" class="section-title"><?php echo esc_html($regulatory_title); ?></h2>
        <p class="section-description"><?php echo esc_html($regulatory_description); ?></p>
      </div>
      
      <?php
      // Get regulatory cards from ACF or use defaults
      if (empty($regulatory_cards)) {
      if (empty($regulatory_cards)) {
        // Default cards with links
        $regulatory_cards = [
          [
            'label' => 'New Framework',
            'title' => 'Single Assessment Framework Updates',
            'content' => 'CQC is implementing a new Single Assessment Framework that streamlines inspections and focuses on outcomes. <a href="' . esc_url(get_permalink(get_page_by_path('downloadable-resources'))) . '">Training evidence</a> will continue to be a key component, with emphasis on demonstrating <a href="' . esc_url(get_permalink(get_page_by_path('downloadable-resources'))) . '">competency</a> and continuous improvement.',
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
          <a href="<?php echo esc_url($card['link_url']); ?>" class="cqc-regulatory-card-link" aria-label="<?php echo esc_attr($card['title'] . ' - ' . (!empty($card['link_text']) ? $card['link_text'] : 'Learn more')); ?>">
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
          
          <?php if (!empty($card['link_url']) && !empty($card['link_text'])) : ?>
          <div class="cqc-regulatory-card-footer">
            <span class="cqc-regulatory-link-text"><?php echo esc_html($card['link_text']); ?></span>
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

  <!-- CQC Articles Section -->
  <?php if ($cqc_query->have_posts()) : ?>
  <section class="news-articles-section" aria-labelledby="cqc-articles-heading">
    <div class="container">
      <div class="news-articles-header">
        <h2 id="cqc-articles-heading" class="section-title">Latest Compliance Articles</h2>
        <p class="section-subtitle">Stay updated with the latest CQC insights, inspection guidance, and compliance best practices</p>
      </div>
      
      <div class="news-articles-grid">
        <?php while ($cqc_query->have_posts()) : $cqc_query->the_post();
          $categories = get_the_category();
          $category = $categories && !is_wp_error($categories) ? $categories[0] : null;
          $read_time = get_field('read_time') ?: cta_reading_time(get_the_content());
        ?>
        <article class="news-article-card">
          <div class="news-article-image-wrapper">
            <?php if (has_post_thumbnail()) : ?>
            <a href="<?php the_permalink(); ?>">
              <?php the_post_thumbnail('medium_large', ['class' => 'news-article-image', 'loading' => 'lazy']); ?>
            </a>
            <?php else : ?>
            <div class="news-article-image-placeholder" aria-hidden="true">
              <i class="fas fa-file-alt" aria-hidden="true"></i>
            </div>
            <?php endif; ?>
          </div>
          
          <div class="news-article-content">
            <div class="news-article-meta">
              <?php if ($category) : ?>
              <span class="news-category-badge"><?php echo esc_html($category->name); ?></span>
              <?php endif; ?>
              <span class="news-article-date"><?php echo get_the_date('j M Y'); ?></span>
              <?php if ($read_time) : ?>
              <span class="news-read-time"><?php echo esc_html($read_time); ?> min read</span>
              <?php endif; ?>
            </div>
            
            <h3 class="news-article-title">
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            
            <p class="news-article-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
            
            <a href="<?php the_permalink(); ?>" class="news-article-link">
              Read More
              <svg class="news-arrow-icon-small" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </a>
          </div>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
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

  <!-- Downloadable CQC Resources Section -->
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

  <!-- CTA Section -->
  <section class="cqc-cta-section">
    <div class="container">
      <div class="cqc-cta-content">
        <h2 class="cqc-cta-title">Not Sure What Training You Need for Your Next CQC Inspection?</h2>
        <p class="cqc-cta-description">Our team can help you understand CQC requirements and ensure your staff have the right training to achieve a positive inspection outcome.</p>
        <div class="cqc-cta-buttons">
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" class="btn btn-primary btn-large">Book a Free Training Consultation</a>
          <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-secondary btn-large">View All Courses</a>
        </div>
      </div>
    </div>
  </section>
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
          "text": "Required courses include: Care Certificate, Safeguarding Adults, Moving & Handling, First Aid at Work, Medication Awareness, Infection Control, Health & Safety, Fire Safety, Food Hygiene (if applicable), and Lone Working Safety."
        }
      },
      {
        "@type": "Question",
        "name": "What training is required for residential care homes?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Required courses include: Care Certificate, Safeguarding Adults & Children, Moving & Handling, First Aid at Work, Medication Management, Infection Control, Health & Safety, Fire Safety, Food Hygiene, Dementia Care, and End of Life Care."
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
