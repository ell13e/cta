<?php
/**
 * Template Name: CQC Compliance Hub
 * 
 * Content hub for CQC compliance information, articles, and resources
 *
 * @package ccs-theme
 */

get_header();

$contact = ccs_get_contact_info();

/**
 * Format FAQ answer text, converting semicolon-separated lists to HTML lists
 * (Same function as in page-faqs.php)
 * 
 * @param string $text The FAQ answer text
 * @return string Formatted HTML
 */
function ccs_format_faq_answer($text) {
    if (empty($text)) {
        return '';
    }
    
    // Look for patterns like: "intro text: Item : Description; Item : Description; closing text"
    // Improved pattern to catch more variations
    // Match: "Title : Description;" where Title starts with capital letter
    $pattern = '/([A-Z][A-Za-z\s&\-\'()]{3,}?)\s*:\s*([^;]{10,}?)(?:;\s*(?=[A-Z])|;\s*$|$)/';
    
    $matches = [];
    if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        // Need at least 2 consecutive items to format as list
        if (count($matches) >= 2) {
            $first_pos = $matches[0][0][1];
            $last_end = $matches[count($matches) - 1][0][1] + strlen($matches[count($matches) - 1][0][0]);
            $list_span = $last_end - $first_pos;
            $text_length = strlen($text);
            
            // If list spans at least 20% of text, format as list
            if ($list_span >= $text_length * 0.2) {
                $before_text = trim(substr($text, 0, $first_pos));
                $after_text = trim(substr($text, $last_end));
                
                $list_items = [];
                foreach ($matches as $match) {
                    $title = trim($match[1][0]);
                    $description = trim($match[2][0]);
                    $description = rtrim($description, '; ');
                    
                    // Validate item - more lenient
                    if (!empty($title) && !empty($description) && 
                        strlen($title) >= 3 && strlen($description) >= 10) {
                        $list_items[] = ['title' => $title, 'description' => $description];
                    }
                }
                
                // Format as list if we have 2+ valid items
                if (count($list_items) >= 2) {
                    $output = '';
                    
                    if (!empty($before_text)) {
                        // Remove trailing colon/space
                        $before_text = preg_replace('/:\s*$/', '', trim($before_text));
                        $output .= '<p class="faq-intro">' . wp_kses_post($before_text) . '</p>';
                    }
                    
                    $output .= '<ul class="faq-answer-list">';
                    foreach ($list_items as $item) {
                        $output .= '<li><strong class="faq-list-title">' . esc_html($item['title']) . ':</strong> <span class="faq-list-description">' . esc_html($item['description']) . '</span></li>';
                    }
                    $output .= '</ul>';
                    
                    if (!empty($after_text)) {
                        $output .= '<p class="faq-outro">' . wp_kses_post($after_text) . '</p>';
                    }
                    
                    return $output;
                }
            }
        }
    }
    
    // Default: standard formatting with improved paragraph spacing
    $formatted = wpautop(wp_kses_post($text));
    
    // Add classes to paragraphs for better styling
    $formatted = preg_replace('/<p>/', '<p class="faq-paragraph">', $formatted);
    
    return $formatted;
}

/**
 * Helper function to safely get page URL with fallback
 */
function ccs_safe_page_url($slug, $fallback = null) {
  $page = get_page_by_path($slug);
  if ($page) {
    return get_permalink($page->ID);
  }
  // Fallback to home URL if page doesn't exist
  return $fallback ?: home_url('/');
}

/**
 * Helper function to find course page URL by title/keywords
 */
function ccs_find_course_link($keywords) {
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
    'first aid' => ['first aid', 'first aid at work', 'faw'],
    'emergency first aid' => ['emergency first aid', 'efaw', 'efa', 'emergency first aid at work'],
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
function ccs_course_link($course_name, $display_text = null) {
  $link = ccs_find_course_link($course_name);
  $text = $display_text ?: $course_name;
  return $link ? '<a href="' . esc_url($link) . '">' . esc_html($text) . '</a>' : esc_html($text);
}

/**
 * Helper to get logical color class for regulatory labels
 * Color coding system:
 * 1. Statutory/Legal → Important (pink) - highest priority
 * 2. Training/Educational → Teal - training content
 * 3. Timeline/Updates → Purple - time-based info
 * 4. Compliance/Standards → Amber - compliance info
 * 5. Framework/New → Blue - new systems/frameworks
 * 6. Resources/Information → Default (cream) - general resources
 * 7. Commitment/CTA → Gold - company commitments
 */
function ccs_get_label_color_class($label, $is_highlight = false) {
  if (empty($label)) {
    return '';
  }
  
  $label_lower = strtolower(trim($label));
  
  // Force highlight to important (pink)
  if ($is_highlight) {
    return 'cqc-regulatory-label-important';
  }
  
  // Priority order matters - check most specific first
  // 1. Statutory/Legal/Requirement → Important (pink) - highest priority
  if (strpos($label_lower, 'statutory') !== false || strpos($label_lower, 'legal') !== false || strpos($label_lower, 'requirement') !== false) {
    return 'cqc-regulatory-label-important';
  } 
  // 2. Framework/New/System → Blue - check framework first (more specific than "new")
  elseif (strpos($label_lower, 'framework') !== false || strpos($label_lower, 'new') !== false || strpos($label_lower, 'system') !== false) {
    return 'cqc-regulatory-label-blue';
  }
  // 3. Training/Educational/Course/Structure → Teal - training content
  elseif (strpos($label_lower, 'training') !== false || strpos($label_lower, 'educational') !== false || strpos($label_lower, 'course') !== false || strpos($label_lower, 'structure') !== false) {
    return 'cqc-regulatory-label-teal';
  }
  // 4. Timeline/Updates/Change/News/Regulatory/Guidance → Purple - time-based info
  elseif (strpos($label_lower, 'timeline') !== false || strpos($label_lower, 'update') !== false || strpos($label_lower, 'change') !== false || strpos($label_lower, 'news') !== false || strpos($label_lower, 'regulatory') !== false || strpos($label_lower, 'guidance') !== false) {
    return 'cqc-regulatory-label-purple';
  }
  // 5. Compliance/Standards/Fundamental/Code of Practice/Practice → Amber - compliance info
  elseif (strpos($label_lower, 'compliance') !== false || strpos($label_lower, 'standard') !== false || strpos($label_lower, 'fundamental') !== false || strpos($label_lower, 'code of practice') !== false || strpos($label_lower, 'practice') !== false) {
    return 'cqc-regulatory-label-amber';
  }
  // 6. Commitment/CTA/Our → Gold - company commitments
  elseif (strpos($label_lower, 'commitment') !== false || strpos($label_lower, 'cta') !== false || strpos($label_lower, 'our') !== false) {
    return 'cqc-regulatory-label-gold';
  }
  // 7. Reports/About/Funding → Purple (information/resource category)
  elseif (strpos($label_lower, 'report') !== false || strpos($label_lower, 'about') !== false || strpos($label_lower, 'funding') !== false) {
    return 'cqc-regulatory-label-purple';
  }
  // 8. Who Needs It → Teal (training/educational context)
  elseif (strpos($label_lower, 'who needs') !== false || strpos($label_lower, 'needs it') !== false) {
    return 'cqc-regulatory-label-teal';
  }
  
  // Default (cream) - no additional class for unrecognized labels
  return '';
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
    $intro_text = 'The Care Quality Commission (CQC) sets standards for health and social care services in England. Our <a href="' . esc_url(get_post_type_archive_link('course') ?: home_url('/courses/')) . '">CQC-compliant training courses</a> help care providers meet these requirements and prepare for inspections.';
  }

// Get CQC-related posts
$cqc_category_ids = function_exists('get_field') ? (get_field('cqc_post_categories') ?: []) : [];
$cqc_category_ids = is_array($cqc_category_ids) ? array_filter(array_map('absint', $cqc_category_ids)) : [];

// Get CQC Compliance category specifically
$cqc_compliance_category = get_term_by('slug', 'cqc-compliance', 'category');
$cqc_compliance_category_id = $cqc_compliance_category ? $cqc_compliance_category->term_id : 0;

$cqc_query = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => 3,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'tax_query' => [
        [
            'taxonomy' => 'category',
            'field' => 'term_id',
            'terms' => $cqc_compliance_category_id ? [$cqc_compliance_category_id] : ($cqc_category_ids ?: []),
            'operator' => 'IN',
        ],
    ],
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
              '<li>' . (ccs_find_course_link('Safeguarding') ? '<a href="' . esc_url(ccs_find_course_link('Safeguarding')) . '">Safeguarding</a>' : 'Safeguarding') . '</li>' .
              '<li>' . (ccs_find_course_link('Health Safety') ? '<a href="' . esc_url(ccs_find_course_link('Health Safety')) . '">Health & Safety</a>' : 'Health & Safety') . '</li>' .
              '<li>' . (ccs_find_course_link('Fire Safety') ? '<a href="' . esc_url(ccs_find_course_link('Fire Safety')) . '">Fire Safety</a>' : 'Fire Safety') . '</li>' .
              '<li>' . (ccs_find_course_link('Moving Handling') ? '<a href="' . esc_url(ccs_find_course_link('Moving Handling')) . '">Moving & Handling</a>' : 'Moving & Handling') . '</li>' .
              '<li>' . (ccs_find_course_link('First Aid') ? '<a href="' . esc_url(ccs_find_course_link('First Aid')) . '">First Aid at Work</a>' : 'First Aid at Work') . '</li>' .
              '<li>' . (ccs_find_course_link('Food Hygiene') ? '<a href="' . esc_url(ccs_find_course_link('Food Hygiene')) . '">Food Hygiene</a>' : 'Food Hygiene') . '</li>' .
              '<li>' . (ccs_find_course_link('Infection Control') ? '<a href="' . esc_url(ccs_find_course_link('Infection Control')) . '">Infection Control</a>' : 'Infection Control') . '</li>' .
              '<li>' . (ccs_find_course_link('Medication Management') ? '<a href="' . esc_url(ccs_find_course_link('Medication Management')) . '">Medication Management</a>' : 'Medication Management') . '</li>' .
              '</ul>' .
              '<p>All staff must complete the ' . (ccs_find_course_link('Care Certificate') ? '<a href="' . esc_url(ccs_find_course_link('Care Certificate')) . '">Care Certificate</a>' : 'Care Certificate') . ' within 12 weeks of starting.</p>',
        ],
        [
            'category' => 'general',
            'question' => 'How do I prepare staff for a CQC inspection?',
            'answer' => 'Ensure all staff have completed <a href="' . esc_url(ccs_safe_page_url('faqs') . '?category=general') . '">mandatory training</a>, <a href="' . esc_url(ccs_safe_page_url('downloadable-resources')) . '">training records</a> are up to date, policies and procedures are current, and staff understand their roles. Our <a href="' . esc_url(get_post_type_archive_link('course') ?: home_url('/courses/')) . '">CQC-compliant training courses</a> cover everything you need to know.',
        ],
        [
            'category' => 'training',
            'question' => 'How often does CQC training need to be refreshed?',
            'answer' => 'Most CQC <a href="' . esc_url(ccs_safe_page_url('faqs') . '?category=general') . '">mandatory training</a> should be refreshed annually. <a href="' . esc_url(ccs_find_course_link('First Aid') ?: (get_post_type_archive_link('course') ?: home_url('/courses/'))) . '">First Aid at Work</a> certificates typically last 3 years. <a href="' . esc_url(ccs_find_course_link('Safeguarding') ?: (get_post_type_archive_link('course') ?: home_url('/courses/'))) . '">Safeguarding</a> training should be refreshed every 2-3 years. Check specific <a href="' . esc_url(get_post_type_archive_link('course') ?: home_url('/courses/')) . '">course requirements</a> for exact refresh periods.',
        ],
        [
            'category' => 'training',
            'question' => 'Is online training accepted by CQC?',
            'answer' => 'CQC accepts a mix of online and face-to-face training, but some topics (like <a href="' . esc_url(ccs_find_course_link('First Aid') ?: (get_post_type_archive_link('course') ?: home_url('/courses/'))) . '">practical first aid</a>) require hands-on training. Our <a href="' . esc_url(ccs_safe_page_url('group-training')) . '">face-to-face courses</a> ensure all practical elements are covered to CQC standards.',
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
  <section class="group-hero-section" aria-labelledby="cqc-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><span class="breadcrumb-current" aria-current="page">CQC Compliance Hub</span></li>
        </ol>
      </nav>
      <h1 id="cqc-heading" class="hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
      <div class="group-hero-cta">
        <a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>" class="btn btn-primary group-hero-btn-primary">Download CQC Training Checklist</a>
      </div>
    </div>
  </section>

  <nav class="cqc-jump-nav" aria-label="Page sections">
    <div class="container">
      <span class="cqc-jump-nav-label">Jump to:</span>
      <div class="cqc-jump-nav-wrapper">
        <ul class="cqc-jump-nav-list">
          <li><a href="#cqc-requirements-heading" class="cqc-jump-nav-link">Training Requirements</a></li>
          <li><a href="#mandatory-training-heading" class="cqc-jump-nav-link">Mandatory Training</a></li>
          <li><a href="#inspection-prep-heading" class="cqc-jump-nav-link">Inspection Prep</a></li>
          <li><a href="#regulatory-changes-heading" class="cqc-jump-nav-link">Regulatory Changes</a></li>
          <li><a href="#oliver-mcgowan-heading" class="cqc-jump-nav-link">Oliver McGowan</a></li>
          <li><a href="#cqc-resources-heading" class="cqc-jump-nav-link">CQC Resources</a></li>
          <li><a href="#government-guidance-heading" class="cqc-jump-nav-link">Government Guidance</a></li>
          <li><a href="#cqc-articles-heading" class="cqc-jump-nav-link">Articles</a></li>
          <li><a href="#cqc-courses-heading" class="cqc-jump-nav-link">Courses</a></li>
          <li><a href="#cqc-faq-heading" class="cqc-jump-nav-link">FAQs</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <?php get_template_part('template-parts/cqc-requirements-section'); ?>

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
          $first_item = true;
          foreach ($mandatory_training_settings as $setting) :
            $setting_name = $setting['setting_name'] ?? '';
            $setting_id = $setting['setting_id'] ?? '';
            $setting_intro = $setting['setting_intro'] ?? '';
            $setting_courses = $setting['setting_courses'] ?? '';
            
            if (empty($setting_name) || empty($setting_id) || empty($setting_courses)) continue;
            
            // First accordion is open by default
            $is_first = $first_item;
            $first_item = false;
        ?>
        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>" aria-controls="cqc-setting-<?php echo esc_attr($setting_id); ?>">
            <span><?php echo esc_html($setting_name); ?></span>
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="cqc-setting-<?php echo esc_attr($setting_id); ?>" class="accordion-content" role="region" aria-hidden="<?php echo $is_first ? 'false' : 'true'; ?>">
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
                echo '<li>' . ccs_course_link($course) . $note . '</li>';
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
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="cqc-setting-domiciliary" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <ul class="list-two-column-gold">
              <li><?php echo ccs_course_link('Care Certificate'); ?></li>
              <li><?php echo ccs_course_link('Safeguarding Adults'); ?></li>
              <li><?php echo ccs_course_link('Moving & Handling'); ?></li>
              <li><?php echo ccs_course_link('First Aid', 'First Aid at Work'); ?></li>
              <li><?php echo ccs_course_link('Medication Awareness'); ?></li>
              <li><?php echo ccs_course_link('Infection Control'); ?></li>
              <li><?php echo ccs_course_link('Health & Safety'); ?></li>
              <li><?php echo ccs_course_link('Fire Safety'); ?></li>
              <li><?php echo ccs_course_link('Food Hygiene'); ?> (if applicable)</li>
              <li><?php echo ccs_course_link('Lone Working Safety') ?: 'Lone Working Safety'; ?></li>
            </ul>
          </div>
        </div>

        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-setting-residential">
            <span>Residential Care Home</span>
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="cqc-setting-residential" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <ul class="list-two-column-gold">
              <li><?php echo ccs_course_link('Care Certificate'); ?></li>
              <li><?php echo ccs_course_link('Safeguarding Adults'); ?> & Children</li>
              <li><?php echo ccs_course_link('Moving & Handling'); ?></li>
              <li><?php echo ccs_course_link('First Aid', 'First Aid at Work'); ?></li>
              <li><?php echo ccs_course_link('Medication Management'); ?></li>
              <li><?php echo ccs_course_link('Infection Control'); ?></li>
              <li><?php echo ccs_course_link('Health & Safety'); ?></li>
              <li><?php echo ccs_course_link('Fire Safety'); ?></li>
              <li><?php echo ccs_course_link('Food Hygiene'); ?></li>
              <li><?php echo ccs_course_link('Dementia Care'); ?></li>
              <li><?php echo ccs_course_link('End of Life Care') ?: 'End of Life Care'; ?></li>
            </ul>
          </div>
        </div>

        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-setting-nursing">
            <span>Nursing Home</span>
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="cqc-setting-nursing" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <p>All <a href="#cqc-setting-residential">residential care requirements</a> plus:</p>
            <ul>
              <li><?php echo ccs_course_link('Clinical Skills') ?: 'Clinical Skills'; ?></li>
              <li><?php echo ccs_course_link('Wound Care') ?: 'Wound Care'; ?></li>
              <li><?php echo ccs_course_link('Catheter Care') ?: 'Catheter Care'; ?></li>
              <li><?php echo ccs_course_link('PEG Feeding') ?: 'PEG Feeding'; ?></li>
              <li><?php echo ccs_course_link('Diabetes Management') ?: 'Diabetes Management'; ?></li>
              <li><?php echo ccs_course_link('Pressure Ulcer Prevention') ?: 'Pressure Ulcer Prevention'; ?></li>
              <li><?php echo ccs_course_link('Clinical Governance') ?: 'Clinical Governance'; ?></li>
            </ul>
          </div>
        </div>

        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-setting-supported-living">
            <span>Supported Living</span>
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="cqc-setting-supported-living" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <ul class="list-two-column-gold">
              <li><?php echo ccs_course_link('Care Certificate'); ?></li>
              <li><?php echo ccs_course_link('Safeguarding Adults'); ?></li>
              <li><?php echo ccs_course_link('Moving & Handling'); ?></li>
              <li><?php echo ccs_course_link('First Aid', 'First Aid at Work'); ?></li>
              <li><?php echo ccs_course_link('Medication Management'); ?></li>
              <li><?php echo ccs_course_link('Learning Disabilities') ?: 'Learning Disabilities Awareness'; ?></li>
              <li><?php echo ccs_course_link('Mental Capacity Act'); ?> & DoLS</li>
              <li><?php echo ccs_course_link('Positive Behaviour Support') ?: 'Positive Behaviour Support'; ?></li>
              <li><?php echo ccs_course_link('Health & Safety'); ?></li>
              <li><?php echo ccs_course_link('Fire Safety'); ?></li>
            </ul>
          </div>
        </div>

        <div class="accordion" data-accordion-group="cqc-settings">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="cqc-setting-complex-care">
            <span>Complex Care</span>
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="cqc-setting-complex-care" class="accordion-content" role="region" aria-hidden="true">
            <p><strong>Required courses:</strong></p>
            <p>All <a href="<?php echo esc_url(ccs_safe_page_url('faqs') . '?category=general'); ?>">core care training</a> plus:</p>
            <ul>
              <li><?php echo ccs_course_link('Clinical Skills') ?: 'Clinical Skills'; ?></li>
              <li><?php echo ccs_course_link('Ventilator Care') ?: 'Ventilator Care'; ?></li>
              <li><?php echo ccs_course_link('Tracheostomy Care') ?: 'Tracheostomy Care'; ?></li>
              <li><?php echo ccs_course_link('Enteral Feeding') ?: 'Enteral Feeding'; ?></li>
              <li><?php echo ccs_course_link('Seizure Management') ?: 'Seizure Management'; ?></li>
              <li><?php echo ccs_course_link('Diabetes Management') ?: 'Diabetes Management'; ?></li>
              <li><?php echo ccs_course_link('Epilepsy Awareness') ?: 'Epilepsy Awareness'; ?></li>
              <li><?php echo ccs_course_link('Specialist Health Conditions') ?: 'Specialist Health Conditions'; ?></li>
            </ul>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php
  $inspection_title = function_exists('get_field') ? get_field('inspection_title') : '';
  $inspection_description = function_exists('get_field') ? get_field('inspection_description') : '';
  $inspection_highlight_title = function_exists('get_field') ? get_field('inspection_highlight_title') : '';
  $inspection_highlight_text = function_exists('get_field') ? get_field('inspection_highlight_text') : '';
  $inspection_accordions = function_exists('get_field') ? get_field('inspection_accordions') : [];
  $inspection_ccs_title = function_exists('get_field') ? get_field('inspection_ccs_title') : '';
  $inspection_ccs_text = function_exists('get_field') ? get_field('inspection_ccs_text') : '';
  $inspection_ccs_button_text = function_exists('get_field') ? get_field('inspection_ccs_button_text') : '';
  $inspection_ccs_link = function_exists('get_field') ? get_field('inspection_ccs_link') : '';
  
  if (empty($inspection_title)) {
    $inspection_title = 'CQC Inspection Preparation';
  }
  if (empty($inspection_description)) {
    $inspection_description = 'Be inspection-ready with organized <a href="' . esc_url(ccs_safe_page_url('downloadable-resources')) . '">training records</a> and documentation';
  }
  if (empty($inspection_highlight_title)) {
    $inspection_highlight_title = 'What Inspectors Check';
  }
  if (empty($inspection_highlight_text)) {
    $inspection_highlight_text = 'CQC inspectors verify all staff have completed <a href="' . esc_url(ccs_safe_page_url('faqs') . '?category=general') . '">mandatory training</a>, certificates are current, and competency is documented.';
  }
  if (empty($inspection_ccs_title)) {
    $inspection_ccs_title = 'Get Inspection Ready';
  }
  if (empty($inspection_ccs_text)) {
    $inspection_ccs_text = 'Download our comprehensive checklist to ensure your training records meet CQC standards';
  }
  if (empty($inspection_ccs_button_text)) {
    $inspection_ccs_button_text = 'Download Inspection Readiness Checklist';
  }
  if (empty($inspection_ccs_link)) {
    $inspection_ccs_link = ccs_safe_page_url('downloadable-resources');
  }
  ?>
  <section class="content-section" aria-labelledby="inspection-prep-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="inspection-prep-heading" class="section-title"><?php echo esc_html($inspection_title); ?></h2>
        <p class="section-description"><?php echo wp_kses_post($inspection_description); ?></p>
      </div>
      
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

      <div class="cqc-inspection-accordions">
        <?php if (!empty($inspection_accordions) && is_array($inspection_accordions)) : 
          // Default colors for each accordion (using CQC requirements colors)
          $default_colors = ['#3182ce', '#805ad5', '#d53f8c', '#d69e2e', '#38a169', '#35938d'];
          
          // Use ACF fields if available
          $first_accordion_index = null;
          foreach ($inspection_accordions as $index => $accordion) :
            $accordion_title = $accordion['title'] ?? '';
            $accordion_items = $accordion['items'] ?? '';
            if (empty($accordion_title) || empty($accordion_items)) continue;
            
            // Set first valid accordion index
            if ($first_accordion_index === null) {
              $first_accordion_index = $index;
            }
            
            $accordion_icon = $accordion['icon'] ?? 'fas fa-check-circle';
            // Use custom color if set, otherwise use default color based on index
            $accordion_icon_color = !empty($accordion['icon_color']) ? $accordion['icon_color'] : $default_colors[$index % count($default_colors)];
            // First accordion is always expanded, or use ACF field if set
            $accordion_expanded = ($index === $first_accordion_index) || !empty($accordion['expanded']);
            $accordion_warning = !empty($accordion['warning']);
            
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
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
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
        <div class="accordion" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="true" aria-controls="inspection-look-for">
            <span>
              <i class="fas fa-search" aria-hidden="true" style="margin-right: 12px; color: #3182ce;"></i>
              What Inspectors Look For in Training Records
            </span>
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="inspection-look-for" class="accordion-content" role="region" aria-hidden="false">
            <div class="cqc-checklist-grid">
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Completed training certificates with expiry dates</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span><a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>">Training matrix</a> recording who has completed what training</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Evidence of <a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>">competency assessments</a></span>
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
              <i class="fas fa-folder-open" aria-hidden="true" style="margin-right: 12px; color: #805ad5;"></i>
              How to Organize Your Training Evidence
            </span>
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="inspection-organize" class="accordion-content" role="region" aria-hidden="true">
            <div class="cqc-checklist-grid">
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Maintain a central <a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>">training matrix</a> for all staff</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Store <a href="<?php echo esc_url(ccs_safe_page_url('faqs') . '?category=certification'); ?>">certificates</a> digitally with expiry date tracking</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Document <a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>">competency assessments</a> alongside certificates</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Keep training plans and needs analysis documents current</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Ensure managers can quickly access <a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>">training records</a> during inspections</span>
              </div>
            </div>
          </div>
        </div>

        <div class="accordion cqc-warning-item" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="inspection-inadequate">
            <span>
              <i class="fas fa-exclamation-triangle" aria-hidden="true" style="margin-right: 12px; color: #d53f8c;"></i>
              Common Training-Related Inadequate Ratings
            </span>
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="inspection-inadequate" class="accordion-content" role="region" aria-hidden="true">
            <div class="cqc-checklist-grid">
              <div class="cqc-checklist-item cqc-warning">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                <span>Staff have not completed <a href="<?php echo esc_url(ccs_safe_page_url('faqs') . '?category=general'); ?>">mandatory training</a> within required timeframes</span>
              </div>
              <div class="cqc-checklist-item cqc-warning">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                <span><a href="<?php echo esc_url(ccs_safe_page_url('faqs') . '?category=certification'); ?>">Training certificates</a> have expired and not been renewed</span>
              </div>
              <div class="cqc-checklist-item cqc-warning">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                <span><a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>">Competency assessments</a> are missing or incomplete</span>
              </div>
              <div class="cqc-checklist-item cqc-warning">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                <span><a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>">Training records</a> are disorganized or inaccessible</span>
              </div>
              <div class="cqc-checklist-item cqc-warning">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                <span>New staff have not completed <a href="<?php echo esc_url(ccs_find_course_link('Care Certificate') ?: (get_post_type_archive_link('course') ?: home_url('/courses/'))); ?>">induction training</a></span>
              </div>
            </div>
          </div>
        </div>

        <div class="accordion cqc-featured-item" data-accordion-group="cqc-inspection">
          <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="inspection-best-practice">
            <span>
              <i class="fas fa-star" aria-hidden="true" style="margin-right: 12px; color: #d69e2e;"></i>
              Best Practice Documentation
            </span>
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="inspection-best-practice" class="accordion-content" role="region" aria-hidden="true">
            <div class="cqc-checklist-grid">
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Individual <a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>">training records</a> for each staff member</span>
              </div>
              <div class="cqc-checklist-item">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>Organizational <a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>">training matrix</a> showing all staff and courses</span>
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
                <span><a href="<?php echo esc_url(ccs_safe_page_url('downloadable-resources')); ?>">Competency assessment records</a></span>
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
      
      <div class="cqc-inspection-cta">
        <div class="cqc-inspection-cta-content">
          <h3><?php echo esc_html($inspection_ccs_title); ?></h3>
          <p><?php echo esc_html($inspection_ccs_text); ?></p>
          <a href="<?php echo esc_url($inspection_ccs_link); ?>" class="btn btn-primary btn-large">
            <i class="fas fa-download" aria-hidden="true"></i>
            <?php echo esc_html($inspection_ccs_button_text); ?>
          </a>
        </div>
      </div>
    </div>
  </section>

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
      // Use ACF regulatory cards if available, otherwise use defaults
      if (empty($regulatory_cards) || !is_array($regulatory_cards)) {
        // Default cards with links
        $regulatory_cards = [
          [
            'label' => 'New Framework',
            'title' => 'Single Assessment Framework Updates',
            'content' => 'CQC has introduced a new Single Assessment Framework that simplifies the inspection process and focuses on care outcomes. The framework is being fully implemented throughout 2026. Training evidence remains essential, with greater emphasis on demonstrating staff competency and ongoing professional development.',
            'link_url' => 'https://www.cqc.org.uk/guidance-regulation/providers/assessment/assessment-framework',
            'link_text' => 'Read CQC framework guidance',
            'highlight' => false,
          ],
          [
            'label' => 'Mandatory Training',
            'title' => 'New Mandatory Training Requirements',
            'content' => 'Several mandatory training requirements are being updated throughout 2026:',
            'list_items' => [
              'Oliver McGowan Mandatory Training on Learning Disability and Autism (becoming statutory in Q2 2026)',
              '<a href="' . esc_url(ccs_find_course_link('Safeguarding') ?: (get_post_type_archive_link('course') ?: home_url('/courses/'))) . '">Enhanced safeguarding training</a> requirements (coming in Q3 2026)',
              'Updated <a href="' . esc_url(ccs_find_course_link('Medication Management') ?: (get_post_type_archive_link('course') ?: home_url('/courses/'))) . '">medication management</a> competencies (coming in Q4 2026)',
              'Refreshed infection prevention and control standards',
            ],
            'link_url' => ccs_safe_page_url('faqs') . '?category=general',
            'link_text' => 'View mandatory training FAQs',
            'highlight' => false,
          ],
          [
            'label' => 'Statutory Requirement',
            'title' => 'Oliver McGowan Training Becoming Statutory',
            'content' => 'The Oliver McGowan Mandatory Training on Learning Disability and Autism will become a legal requirement for all health and social care staff in Q2 2026 (April-June). The code of practice became final on 6 September 2025 under the Health and Care Act 2022. This training is essential for services supporting people with learning disabilities and autism. The training consists of two tiers: Tier 1 (general awareness) includes elearning plus a 1-hour online interactive session, while Tier 2 (direct care roles) includes elearning plus a 1-day face-to-face session.',
            'link_url' => 'https://www.gov.uk/government/publications/oliver-mcgowan-code-of-practice/the-oliver-mcgowan-draft-code-of-practice-on-statutory-learning-disability-and-autism-training',
            'link_text' => 'Read the code of practice',
            'highlight' => true,
          ],
          [
            'label' => 'Timeline',
            'title' => 'Timeline of Regulatory Changes',
            'list_items' => [
              '<strong>Q1 2026 (Jan-Mar):</strong> Single Assessment Framework is being fully implemented',
              '<strong>Q2 2026 (Apr-Jun):</strong> Oliver McGowan training will become statutory (code of practice finalised 6 September 2025)',
              '<strong>Q3 2026 (Jul-Sep):</strong> Updated <a href="' . esc_url(ccs_find_course_link('Safeguarding') ?: (get_post_type_archive_link('course') ?: home_url('/courses/'))) . '">safeguarding</a> requirements will come into effect',
              '<strong>Q4 2026 (Oct-Dec):</strong> Enhanced <a href="' . esc_url(ccs_find_course_link('Medication Management') ?: (get_post_type_archive_link('course') ?: home_url('/courses/'))) . '">medication competency</a> standards will be introduced',
            ],
            'link_url' => get_post_type_archive_link('course') ?: home_url('/courses/'),
            'link_text' => 'Browse all courses',
            'highlight' => false,
            'timeline' => true,
          ],
          [
            'label' => 'Fundamental Standard',
            'title' => 'Reducing Restraint and Restrictive Intervention',
            'content' => 'The fundamental standards require that people must not suffer unnecessary or disproportionate restraint. The Department of Health guidance on reducing the need for restraint and restrictive intervention provides best practice for care providers. This is a key safeguarding requirement that CQC inspectors assess.',
            'link_url' => 'https://assets.publishing.service.gov.uk/media/5d1387e240f0b6350e1ab567/reducing-the-need-for-restraint-and-restrictive-intervention.pdf',
            'link_text' => 'Read restraint reduction guidance',
            'highlight' => false,
          ],
          [
            'label' => 'Our Commitment',
            'title' => 'How CTA Courses Meet New Standards',
            'content' => 'All Continuity of Care Services courses are designed to meet current and upcoming CQC requirements. We regularly update our course content to align with regulatory changes and ensure your training remains compliant and up-to-date.',
            'link_url' => get_post_type_archive_link('course') ?: home_url('/courses/'),
            'link_text' => 'Browse all courses',
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
          $color_class = ccs_get_label_color_class($card['label'] ?? '', !empty($card['highlight']));
          if (!empty($color_class)) {
            $label_class .= ' ' . $color_class;
          }
          
          $list_class = !empty($card['timeline']) ? 'cqc-timeline-list' : '';
        ?>
        <div class="<?php echo esc_attr($card_class); ?>">
          <?php 
          // Only wrap entire card in link if there's no footer link text
          $wrap_card = !empty($card['link_url']) && empty($card['link_text']);
          if ($wrap_card) : ?>
          <a href="<?php echo esc_url($card['link_url']); ?>" class="cqc-regulatory-card-link" aria-label="<?php echo esc_attr($card['title'] . ' - Learn more'); ?>">
          <?php endif; ?>
          
          <?php if (!empty($card['label'])) : ?>
          <div class="<?php echo esc_attr($label_class); ?>">
            <span><?php echo esc_html($card['label']); ?></span>
          </div>
          <?php endif; ?>
          <h3 class="cqc-regulatory-title"><?php echo esc_html($card['title']); ?></h3>
          
          <?php if (!empty($card['content'])) : ?>
          <div><?php echo wp_kses_post($card['content']); ?></div>
          <?php endif; ?>
          
          <?php if (!empty($card['list_items'])) : 
            // Handle both array and newline-separated string
            $list_items = is_array($card['list_items']) ? $card['list_items'] : array_filter(array_map('trim', explode("\n", $card['list_items'])));
          ?>
          <ul class="<?php echo esc_attr($list_class); ?>">
            <?php foreach ($list_items as $item) : ?>
            <li><?php echo wp_kses_post($item); ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          
          <?php if (!empty($card['link_url']) && !empty($card['link_text'])) : ?>
          <div class="cqc-regulatory-card-footer">
            <a href="<?php echo esc_url($card['link_url']); ?>" class="cqc-regulatory-link-text" <?php echo (strpos($card['link_url'], 'http') === 0) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
              <?php echo esc_html($card['link_text']); ?>
            </a>
            <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M7 17L17 7M7 7h10v10"></path>
            </svg>
          </div>
          <?php endif; ?>
          
          <?php if ($wrap_card) : ?>
          </a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="content-section bg-light-cream" aria-labelledby="oliver-mcgowan-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="oliver-mcgowan-heading" class="section-title">Oliver McGowan Mandatory Training</h2>
        <p class="section-description">Understanding the new statutory requirement for learning disability and autism training</p>
      </div>
      
      <div class="cqc-regulatory-grid">
        <div class="cqc-regulatory-card cqc-regulatory-card-highlight">
          <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Statutory Requirement', true)); ?>">
            <span>Statutory Requirement</span>
          </div>
          <h3 class="cqc-regulatory-title">What is Oliver McGowan Training?</h3>
          <div>
            <p>The Oliver McGowan Mandatory Training on Learning Disability and Autism is the government's preferred and recommended training for health and social care staff. Named after Oliver McGowan, a young autistic teenager with a mild learning disability who died in 2016, this training ensures staff have the right skills and knowledge to provide safe, compassionate care.</p>
            <p><strong>The code of practice became final on 6 September 2025</strong> and the training will become a legal requirement for all health and social care staff in Q2 2026 (April-June) under the Health and Care Act 2022.</p>
          </div>
          <div class="cqc-regulatory-card-footer">
            <a href="https://www.gov.uk/government/publications/oliver-mcgowan-code-of-practice/the-oliver-mcgowan-draft-code-of-practice-on-statutory-learning-disability-and-autism-training" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-link-text">
              Read the code of practice
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </a>
          </div>
        </div>

        <div class="cqc-regulatory-card">
          <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Training Structure')); ?>">
            <span>Training Structure</span>
          </div>
          <h3 class="cqc-regulatory-title">Two Tiers of Training</h3>
          <div>
            <p>The training has two tiers:</p>
            <ul class="cqc-timeline-list">
              <li><strong>Tier 1 (General Awareness):</strong> For staff who need general awareness. Includes elearning (1.5 hours) plus a 1-hour online session.</li>
              <li><strong>Tier 2 (Direct Care):</strong> For staff who provide care. Includes the same elearning plus a 1-day face-to-face session.</li>
            </ul>
            <p><strong>Note:</strong> Everyone must complete the elearning. Tier 2 includes Tier 1 content, so staff only complete one tier based on their role.</p>
          </div>
          <div class="cqc-regulatory-card-footer">
            <a href="https://www.e-lfh.org.uk/programmes/the-oliver-mcgowan-mandatory-training-on-learning-disability-and-autism/" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-link-text">
              Access the training
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </a>
          </div>
        </div>

        <div class="cqc-regulatory-card">
          <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Who Needs It')); ?>">
            <span>Who Needs It</span>
          </div>
          <h3 class="cqc-regulatory-title">Who Needs This Training?</h3>
          <div>
            <p>All registered health and social care providers must ensure their staff receive appropriate training:</p>
            <ul class="cqc-timeline-list">
              <li><strong>Tier 1:</strong> Staff who need general awareness (e.g., finance staff, administrators without patient contact)</li>
              <li><strong>Tier 2:</strong> Staff who provide direct care or support (e.g., care workers, nurses, GPs, reception staff with patient contact, managers making service decisions)</li>
            </ul>
            <p>Employers must assess which tier each staff member needs based on their role and level of contact with people with learning disabilities or autistic people.</p>
          </div>
          <div class="cqc-regulatory-card-footer">
            <a href="https://www.gov.uk/government/publications/explanatory-memorandum-on-the-oliver-mcgowan-code-of-practice/explanatory-memorandum-to-the-oliver-mcgowan-draft-code-of-practice-on-statutory-learning-disability-and-autism-training" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-link-text">
              Read explanatory memorandum
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </a>
          </div>
        </div>

        <div class="cqc-regulatory-card">
          <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Compliance')); ?>">
            <span>Compliance</span>
          </div>
          <h3 class="cqc-regulatory-title">Meeting the Requirement</h3>
          <div>
            <p>The code of practice sets out standards for training and guidance for meeting those standards. CQC will assess whether registered providers are meeting this requirement during inspections.</p>
            <p style="font-weight: 600;">Key points:</p>
            <ul class="cqc-timeline-list">
              <li>Training must be standardised and meet the core capabilities frameworks</li>
              <li>Training must be co-delivered by experts by experience (people with learning disabilities or autistic people)</li>
              <li>Providers must maintain records of staff completion</li>
              <li>CQC can take enforcement action if providers fail to meet the requirement</li>
            </ul>
          </div>
          <div class="cqc-regulatory-card-footer">
            <a href="<?php echo esc_url(get_post_type_archive_link('course') ?: home_url('/courses/')); ?>" class="cqc-regulatory-link-text">
              Browse our courses
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="content-section bg-light-cream" aria-labelledby="cqc-resources-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="cqc-resources-heading" class="section-title">Official CQC Resources</h2>
        <p class="section-description">Essential links to CQC guidance, publications, and updates to help you stay compliant</p>
      </div>
      
      <div class="cqc-regulatory-grid">
        <div class="cqc-regulatory-card">
          <a href="https://www.cqc.org.uk/guidance-regulation" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-card-link">
            <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Guidance')); ?>">
              <span>Guidance</span>
            </div>
            <h3 class="cqc-regulatory-title">CQC Guidance & Regulation</h3>
            <div>
              <p>Comprehensive guidance for health and social care providers, including registration requirements, assessment framework, regulations and fundamental standards, enforcement actions, notification requirements, and registration fees.</p>
            </div>
            <div class="cqc-regulatory-card-footer">
              <span class="cqc-regulatory-link-text">Visit CQC Guidance & Regulation</span>
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </div>
          </a>
        </div>

        <div class="cqc-regulatory-card">
          <a href="https://www.cqc.org.uk/publications" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-card-link">
            <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Reports')); ?>">
              <span>Reports</span>
            </div>
            <h3 class="cqc-regulatory-title">CQC Publications</h3>
            <div>
              <p>Access CQC's official reports and publications:</p>
              <ul class="cqc-timeline-list">
                <li><strong>State of Care</strong> - Annual assessment of health and social care in England</li>
                <li><strong>Major reports</strong> - In-depth analysis of care quality and trends</li>
                <li><strong>Surveys</strong> - Feedback from people using NHS services</li>
                <li><strong>Themed inspections</strong> - Focused reports on specific care areas</li>
                <li><strong>Annual reports</strong> - CQC's corporate activities and performance</li>
              </ul>
            </div>
            <div class="cqc-regulatory-card-footer">
              <span class="cqc-regulatory-link-text">Browse CQC Publications</span>
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </div>
          </a>
        </div>

        <div class="cqc-regulatory-card">
          <a href="https://www.cqc.org.uk/news" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-card-link">
            <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Updates')); ?>">
              <span>Updates</span>
            </div>
            <h3 class="cqc-regulatory-title">CQC News & Updates</h3>
            <div>
              <p>Stay informed with the latest CQC news and regulatory updates:</p>
              <ul class="cqc-timeline-list">
                <li>Latest news and announcements</li>
                <li>Press releases on inspection findings</li>
                <li>Regulatory changes and framework updates</li>
                <li>Service rating announcements</li>
                <li>Improvement plans and progress updates</li>
              </ul>
            </div>
            <div class="cqc-regulatory-card-footer">
              <span class="cqc-regulatory-link-text">Read CQC News</span>
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </div>
          </a>
        </div>

        <div class="cqc-regulatory-card">
          <a href="https://www.cqc.org.uk/about-us" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-card-link">
            <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('About')); ?>">
              <span>About</span>
            </div>
            <h3 class="cqc-regulatory-title">About the Care Quality Commission</h3>
            <div>
              <p>Learn about CQC's role and how they regulate care:</p>
              <ul class="cqc-timeline-list">
                <li><strong>Purpose:</strong> Regulate health and adult social care to protect people and improve quality</li>
                <li><strong>Vision:</strong> Everyone receives safe, effective and compassionate care</li>
                <li>Fundamental standards of care</li>
                <li>How CQC monitors, inspects and rates services</li>
                <li>Who CQC regulates and works with</li>
              </ul>
            </div>
            <div class="cqc-regulatory-card-footer">
              <span class="cqc-regulatory-link-text">Learn About CQC</span>
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="content-section" aria-labelledby="government-guidance-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="government-guidance-heading" class="section-title">Additional Government Guidance</h2>
        <p class="section-description">Essential codes of practice, guidance documents, and regulations from the Department of Health and Social Care</p>
      </div>
      
      <div class="cqc-regulatory-grid">
        <div class="cqc-regulatory-card">
          <a href="https://www.gov.uk/government/publications/the-health-and-social-care-act-2008-code-of-practice-on-the-prevention-and-control-of-infections-and-related-guidance/health-and-social-care-act-2008-code-of-practice-on-the-prevention-and-control-of-infections-and-related-guidance" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-card-link">
            <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Code of Practice')); ?>">
              <span>Code of Practice</span>
            </div>
            <h3 class="cqc-regulatory-title">Infection Prevention and Control</h3>
            <div>
              <p>The Health and Social Care Act 2008 code of practice on the prevention and control of infections sets out requirements for registered providers. This is a fundamental standard that CQC inspectors check during inspections.</p>
            </div>
            <div class="cqc-regulatory-card-footer">
              <span class="cqc-regulatory-link-text">Read code of practice</span>
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </div>
          </a>
        </div>

        <div class="cqc-regulatory-card">
          <a href="https://assets.publishing.service.gov.uk/media/5d1387e240f0b6350e1ab567/reducing-the-need-for-restraint-and-restrictive-intervention.pdf" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-card-link">
            <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Guidance')); ?>">
              <span>Guidance</span>
            </div>
            <h3 class="cqc-regulatory-title">Reducing Restraint and Restrictive Intervention</h3>
            <div>
              <p>Department of Health guidance on reducing the need for restraint and restrictive intervention. This relates to the fundamental standard that people must not suffer unnecessary or disproportionate restraint.</p>
            </div>
            <div class="cqc-regulatory-card-footer">
              <span class="cqc-regulatory-link-text">Read the guidance</span>
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </div>
          </a>
        </div>

        <div class="cqc-regulatory-card">
          <a href="https://www.gov.uk/government/publications/healthcare-education-and-training-tariff-2025-to-2026/education-and-training-tariffs-2025-to-2026" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-card-link">
            <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Funding')); ?>">
              <span>Funding</span>
            </div>
            <h3 class="cqc-regulatory-title">Healthcare Education and Training Tariff</h3>
            <div>
              <p>Information about education and training tariff payments for 2025 to 2026, including clinical placements, undergraduate medical and dental training, and postgraduate training arrangements.</p>
            </div>
            <div class="cqc-regulatory-card-footer">
              <span class="cqc-regulatory-link-text">View training tariff guidance</span>
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </div>
          </a>
        </div>

        <div class="cqc-regulatory-card">
          <a href="https://www.gov.uk/guidance/supplying-take-home-naloxone-without-a-prescription" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-card-link">
            <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Guidance')); ?>">
              <span>Guidance</span>
            </div>
            <h3 class="cqc-regulatory-title">Supplying Take-Home Naloxone</h3>
            <div>
              <p>Guidance for health and social care providers on supplying take-home naloxone without a prescription. Relevant for services supporting people at risk of opioid overdose.</p>
            </div>
            <div class="cqc-regulatory-card-footer">
              <span class="cqc-regulatory-link-text">Read the guidance</span>
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </div>
          </a>
        </div>

        <div class="cqc-regulatory-card">
          <a href="https://www.gov.uk/government/consultations/down-syndrome-act-2022-draft-statutory-guidance-easy-read" target="_blank" rel="noopener noreferrer" class="cqc-regulatory-card-link">
            <div class="cqc-regulatory-label <?php echo esc_attr(ccs_get_label_color_class('Statutory Guidance', true)); ?>">
              <span>Statutory Guidance</span>
            </div>
            <h3 class="cqc-regulatory-title">Down Syndrome Act 2022</h3>
            <div>
              <p>Draft statutory guidance for the Down Syndrome Act 2022. This Act requires local authorities and NHS bodies to have regard to guidance when providing services to people with Down syndrome. Relevant for care providers supporting people with Down syndrome.</p>
            </div>
            <div class="cqc-regulatory-card-footer">
              <span class="cqc-regulatory-link-text">Read the draft guidance</span>
              <svg class="cqc-regulatory-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M7 17L17 7M7 7h10v10"></path>
              </svg>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>

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
          $read_time = get_field('read_time') ?: ccs_reading_time(get_the_content());
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

  <?php if (!empty($faqs)) : ?>
  <section class="content-section bg-light-cream" aria-labelledby="cqc-faq-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="cqc-faq-heading" class="section-title">FAQs About CQC Compliance</h2>
        <p class="section-description">Common questions about CQC requirements and training</p>
      </div>
      
      <div class="cqc-faq-wrapper">
        <?php 
        $first_faq = true;
        foreach ($faqs as $index => $faq) :
          if (!is_array($faq) || !isset($faq['question']) || !isset($faq['answer'])) {
            continue;
          }
          $is_expanded = $first_faq;
          $first_faq = false;
        ?>
        <div class="accordion" data-accordion-group="cqc-faq">
          <button type="button" class="accordion-trigger" aria-expanded="<?php echo $is_expanded ? 'true' : 'false'; ?>" aria-controls="cqc-faq-<?php echo (int) $index; ?>">
            <span><?php echo esc_html($faq['question']); ?></span>
            <span class="accordion-icon" aria-hidden="true">
              <i class="fas fa-plus" aria-hidden="true"></i>
              <i class="fas fa-minus" aria-hidden="true"></i>
            </span>
          </button>
          <div id="cqc-faq-<?php echo (int) $index; ?>" class="accordion-content" role="region" aria-hidden="<?php echo $is_expanded ? 'false' : 'true'; ?>">
            <?php 
            // Use FAQ formatting function if available, otherwise use wpautop
            if (function_exists('ccs_format_faq_answer')) {
              echo ccs_format_faq_answer($faq['answer']);
            } else {
              echo wpautop(wp_kses_post($faq['answer']));
            }
            ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

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
            $custom_icon = get_post_meta($resource_id, '_ccs_resource_icon', true);
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

  <section class="cqc-cta-section" aria-labelledby="cqc-cta-heading">
    <div class="container">
      <div class="cqc-cta-content">
        <h2 id="cqc-cta-heading" class="cqc-cta-title">Need Help With CQC Training?</h2>
        <p class="cqc-cta-description">Our team can help you understand CQC requirements and ensure your staff have the right training to achieve a positive inspection outcome.</p>
        <div class="cqc-cta-buttons">
          <a href="<?php echo esc_url(ccs_safe_page_url('contact')); ?>" class="btn btn-primary btn-large">Book a Free Training Consultation</a>
          <a href="<?php echo esc_url(get_post_type_archive_link('course') ?: home_url('/courses/')); ?>" class="btn btn-secondary btn-large">View All Courses</a>
        </div>
      </div>
    </div>
  </section>
</main>

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
    "name": "Continuity of Care Services",
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
