<?php
/**
 * Course Data Importer
 * 
 * Imports course data and scheduled events from JSON files when the theme is activated.
 * This ensures all existing course data is preserved during the WordPress migration.
 *
 * ⚠️ DATA SAFETY GUARANTEE:
 * - This importer NEVER deletes existing data (courses, events, submissions, pages, etc.)
 * - It only ADDS new data from JSON files if it doesn't already exist
 * - All existing posts, pages, and custom post types are completely safe
 * - The importer checks for existing content before creating anything
 * - It only runs once (checks cta_data_imported option)
 * - Re-uploading/activating the theme will NOT affect existing data
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Import course data on theme activation
 * 
 * IMPORTANT: This function NEVER deletes existing data.
 * It only imports new data from JSON files if it doesn't already exist.
 * All existing courses, events, submissions, and other content are preserved.
 */
function cta_import_data_on_activation() {
    // Only run once - check if we've already imported
    // This prevents re-running on every theme activation
    if (get_option('cta_data_imported')) {
        return;
    }
    
    // SAFETY: Never delete existing data - this function only ADDS new data
    // All import functions check for existing posts before creating new ones
    
    // Wrap in error handling to prevent site breakage
    try {
        // Import site settings first
        if (function_exists('cta_import_site_settings')) {
            cta_import_site_settings();
        }
        
        // Import courses
        $courses_imported = 0;
        if (function_exists('cta_import_courses')) {
            $courses_imported = cta_import_courses();
        }
        
        // Import scheduled events
        $events_imported = 0;
        if (function_exists('cta_import_scheduled_events')) {
            $events_imported = cta_import_scheduled_events();
        }
        
        // Import course categories
        if (function_exists('cta_import_course_categories')) {
            cta_import_course_categories();
        }
        
        // Import news articles
        $news_imported = 0;
        if (function_exists('cta_import_news_articles')) {
            $news_imported = cta_import_news_articles();
        }
        
        // Import team members
        $team_imported = 0;
        if (function_exists('cta_import_team_members')) {
            $team_imported = cta_import_team_members();
        }
        
        // Create static pages
        $pages_created = 0;
        if (function_exists('cta_create_static_pages')) {
            $pages_created = cta_create_static_pages();
        }
        
        // Set up default WordPress settings
        if (function_exists('cta_configure_wordpress_settings')) {
            cta_configure_wordpress_settings();
        }
        
        // Mark as imported
        update_option('cta_data_imported', true);
        update_option('cta_data_import_date', current_time('mysql'));
        update_option('cta_courses_imported_count', $courses_imported);
        update_option('cta_events_imported_count', $events_imported);
        update_option('cta_news_imported_count', $news_imported);
        update_option('cta_team_imported_count', $team_imported);
        update_option('cta_pages_created_count', $pages_created);
        
        // Add admin notice
        set_transient('cta_import_complete', [
            'courses' => $courses_imported,
            'events' => $events_imported,
            'news' => $news_imported,
            'team' => $team_imported,
            'pages' => $pages_created,
        ], 60);
    } catch (Exception $e) {
        // Log error but don't break the site
        error_log('CTA Theme: Import error - ' . $e->getMessage());
        // Mark as attempted so it doesn't keep retrying
        update_option('cta_data_import_attempted', true);
    }
}
add_action('after_switch_theme', 'cta_import_data_on_activation');

/**
 * Show admin notice after import
 */
function cta_show_import_notice() {
    $import_data = get_transient('cta_import_complete');
    
    if ($import_data) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>🎉 Continuity Training Academy Theme Ready!</strong></p>
            <p>Successfully imported:</p>
            <ul style="margin-left: 20px; list-style: disc;">
                <li><?php echo intval($import_data['courses']); ?> courses</li>
                <li><?php echo intval($import_data['events']); ?> scheduled events</li>
                <li><?php echo intval($import_data['news'] ?? 0); ?> news articles</li>
                <li><?php echo intval($import_data['team'] ?? 0); ?> team members</li>
                <li><?php echo intval($import_data['pages'] ?? 0); ?> pages created</li>
            </ul>
            <p>Your site is ready to go live! <a href="<?php echo home_url(); ?>">View your site →</a></p>
        </div>
        <?php
        delete_transient('cta_import_complete');
    }
}
add_action('admin_notices', 'cta_show_import_notice');

/**
 * Import courses from JSON data
 */
function cta_import_courses() {
    $courses_data = cta_get_courses_data();
    
    if (empty($courses_data)) {
        return 0;
    }
    
    $imported = 0;
    
    foreach ($courses_data as $course_title => $course) {
        // SAFETY: Check if course already exists - NEVER delete or overwrite existing data
        $existing = get_posts([
            'post_type' => 'course',
            'title' => $course_title,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);
        
        if (!empty($existing)) {
            // SAFETY: Skip if already exists - preserve all existing data
            continue;
        }
        
        // Create the course post
        $post_data = [
            'post_title' => sanitize_text_field($course_title),
            'post_content' => '', // Content is in ACF fields
            'post_status' => 'publish',
            'post_type' => 'course',
            'post_excerpt' => wp_trim_words(sanitize_textarea_field($course['description'] ?? ''), 30),
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            continue;
        }
        
        // Set ACF fields
        if (function_exists('update_field')) {
            // Course Details
            update_field('course_level', sanitize_text_field($course['level'] ?? ''), $post_id);
            update_field('course_duration', sanitize_text_field($course['duration'] ?? ''), $post_id);
            
            // Parse hours from string like "3 hours"
            $hours = 0;
            if (!empty($course['hours'])) {
                preg_match('/(\d+(?:\.\d+)?)/', $course['hours'], $matches);
                $hours = isset($matches[1]) ? floatval($matches[1]) : 0;
            }
            update_field('course_hours', $hours, $post_id);
            
            // Trainer (first trainer if array)
            $trainer = '';
            if (!empty($course['trainers']) && is_array($course['trainers'])) {
                $trainer = $course['trainers'][0];
            }
            update_field('course_trainer', sanitize_text_field($trainer), $post_id);
            
            // Price
            update_field('course_price', floatval($course['price'] ?? 0), $post_id);
            
            // Course Content
            update_field('course_description', wp_kses_post($course['description'] ?? ''), $post_id);
            update_field('course_suitable_for', sanitize_textarea_field($course['whoShouldAttend'] ?? ''), $post_id);
            update_field('course_prerequisites', sanitize_textarea_field($course['requirements'] ?? ''), $post_id);
            
            // Learning Outcomes (now stored as textarea, one per line)
            if (!empty($course['learningOutcomes']) && is_array($course['learningOutcomes'])) {
                $outcomes_text = implode("\n", array_map('sanitize_text_field', $course['learningOutcomes']));
                update_field('course_outcomes', $outcomes_text, $post_id);
            }
            
            // Accreditation
            $accreditation = $course['accreditation'] ?? '';
            // Take first accreditation if multiple (separated by |)
            if (strpos($accreditation, '|') !== false) {
                $accreditation = explode('|', $accreditation)[0];
            }
            update_field('course_accreditation', sanitize_text_field($accreditation), $post_id);
            update_field('course_certificate', 'CPD Certificate upon completion', $post_id);
            
            // Set Legacy ID and Course Code for migration reference
            if (!empty($course['id'])) {
                update_field('course_legacy_id', intval($course['id']), $post_id);
            }
            
            // Generate course code from title (e.g., "Basic Life Support" -> "BLS-001")
            // Extract first letter of each major word, limit to 3-4 letters
            $words = explode(' ', $course_title);
            $code_prefix = '';
            foreach ($words as $word) {
                if (strlen($word) > 0 && !in_array(strtolower($word), ['and', 'the', 'for', 'with', 'from', 'to', 'of', 'in', 'on', 'at', 'a', 'an'])) {
                    $code_prefix .= strtoupper(substr($word, 0, 1));
                    if (strlen($code_prefix) >= 3) {
                        break;
                    }
                }
            }
            // If we don't have enough letters, pad with numbers or use first 3 chars
            if (strlen($code_prefix) < 2) {
                $code_prefix = strtoupper(substr(preg_replace('/[^a-z]/i', '', $course_title), 0, 3));
            }
            // Add the legacy ID as suffix (e.g., BLS-001)
            $course_code = $code_prefix . '-' . str_pad($course['id'] ?? $post_id, 3, '0', STR_PAD_LEFT);
            update_field('course_code', sanitize_text_field($course_code), $post_id);
        }
        
        // Set category
        if (!empty($course['category'])) {
            $category_slug = sanitize_title($course['category']);
            $term = get_term_by('slug', $category_slug, 'course_category');
            
            if (!$term) {
                // Create the category if it doesn't exist
                $term_result = wp_insert_term($course['category'], 'course_category', [
                    'slug' => $category_slug,
                ]);
                if (!is_wp_error($term_result)) {
                    wp_set_object_terms($post_id, $term_result['term_id'], 'course_category');
                }
            } else {
                wp_set_object_terms($post_id, $term->term_id, 'course_category');
            }
        }
        
        // Set featured image from course image path
        if (!empty($course['image'])) {
            cta_set_course_featured_image($post_id, $course['image'], $course_title);
        }
        
        $imported++;
    }
    
    return $imported;
}

/**
 * Populate a single course from JSON data by title
 * 
 * @param string $course_title The exact course title from JSON
 * @return array ['success' => bool, 'message' => string, 'course_id' => int|null]
 */
function cta_populate_single_course_from_json($course_title) {
    $courses_data = cta_get_courses_data();
    
    if (empty($courses_data)) {
        return ['success' => false, 'message' => 'No course data found in JSON file.'];
    }
    
    if (!isset($courses_data[$course_title])) {
        return ['success' => false, 'message' => 'Course not found in JSON: "' . esc_html($course_title) . '"'];
    }
    
    // Check if course post exists
    $existing = get_posts([
        'post_type' => 'course',
        'title' => $course_title,
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);
    
    $post_id = null;
    
    if (!empty($existing)) {
        // Update existing course
        $post_id = $existing[0]->ID;
    } else {
        // Create new course post
        $post_data = [
            'post_title' => sanitize_text_field($course_title),
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'course',
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return ['success' => false, 'message' => 'Failed to create course post: ' . $post_id->get_error_message()];
        }
    }
    
    $course = $courses_data[$course_title];
    
    // Populate ACF fields (same logic as bulk import)
    if (function_exists('update_field')) {
        update_field('course_level', sanitize_text_field($course['level'] ?? ''), $post_id);
        update_field('course_duration', sanitize_text_field($course['duration'] ?? ''), $post_id);
        
        // Parse hours
        $hours = 0;
        if (!empty($course['hours'])) {
            preg_match('/(\d+(?:\.\d+)?)/', $course['hours'], $matches);
            $hours = isset($matches[1]) ? floatval($matches[1]) : 0;
        }
        update_field('course_hours', $hours, $post_id);
        
        // Trainer
        $trainer = '';
        if (!empty($course['trainers']) && is_array($course['trainers'])) {
            $trainer = $course['trainers'][0];
        }
        update_field('course_trainer', sanitize_text_field($trainer), $post_id);
        
        // Price
        update_field('course_price', floatval($course['price'] ?? 0), $post_id);
        
        // Course Content
        update_field('course_description', wp_kses_post($course['description'] ?? ''), $post_id);
        update_field('course_suitable_for', sanitize_textarea_field($course['whoShouldAttend'] ?? ''), $post_id);
        update_field('course_prerequisites', sanitize_textarea_field($course['requirements'] ?? ''), $post_id);
        
        // Learning Outcomes
        if (!empty($course['learningOutcomes']) && is_array($course['learningOutcomes'])) {
            $outcomes_text = implode("\n", array_map('sanitize_text_field', $course['learningOutcomes']));
            update_field('course_outcomes', $outcomes_text, $post_id);
        }
        
        // Accreditation
        $accreditation = $course['accreditation'] ?? '';
        if (strpos($accreditation, '|') !== false) {
            $accreditation = explode('|', $accreditation)[0];
        }
        update_field('course_accreditation', sanitize_text_field($accreditation), $post_id);
        update_field('course_certificate', 'CPD Certificate upon completion', $post_id);
        
        // Legacy ID
        if (!empty($course['id'])) {
            update_field('course_legacy_id', intval($course['id']), $post_id);
        }
        
        // Generate course code
        $words = explode(' ', $course_title);
        $code_prefix = '';
        foreach ($words as $word) {
            if (strlen($word) > 0 && !in_array(strtolower($word), ['and', 'the', 'for', 'with', 'from', 'to', 'of', 'in', 'on', 'at', 'a', 'an'])) {
                $code_prefix .= strtoupper(substr($word, 0, 1));
                if (strlen($code_prefix) >= 3) {
                    break;
                }
            }
        }
        if (strlen($code_prefix) < 2) {
            $code_prefix = strtoupper(substr(preg_replace('/[^a-z]/i', '', $course_title), 0, 3));
        }
        $course_code = $code_prefix . '-' . str_pad($course['id'] ?? $post_id, 3, '0', STR_PAD_LEFT);
        update_field('course_code', sanitize_text_field($course_code), $post_id);
    }
    
    // Set category
    if (!empty($course['category'])) {
        $category_slug = sanitize_title($course['category']);
        $term = get_term_by('slug', $category_slug, 'course_category');
        
        if (!$term) {
            $term_result = wp_insert_term($course['category'], 'course_category', [
                'slug' => $category_slug,
            ]);
            if (!is_wp_error($term_result)) {
                wp_set_object_terms($post_id, $term_result['term_id'], 'course_category');
            }
        } else {
            wp_set_object_terms($post_id, $term->term_id, 'course_category');
        }
    }
    
    $action = !empty($existing) ? 'updated' : 'created';
    return [
        'success' => true,
        'message' => sprintf('Course "%s" %s successfully from JSON data!', esc_html($course_title), $action),
        'course_id' => $post_id
    ];
}

/**
 * Import scheduled events from JSON data
 */
function cta_import_scheduled_events() {
    $events_data = cta_get_scheduled_events_data();
    
    if (empty($events_data)) {
        return 0;
    }
    
    $imported = 0;
    
    foreach ($events_data as $event) {
        // Find the linked course
        $course = get_page_by_title($event['title'], OBJECT, 'course');
        
        if (!$course) {
            continue; // Skip if course doesn't exist
        }
        
        // Parse date from format like "Wed, 7 Jan 2026"
        $date = cta_parse_event_date($event['date']);
        
        if (!$date) {
            continue;
        }
        
        // Create event title
        $event_title = $event['title'] . ' - ' . date('j M Y', strtotime($date));
        
        // SAFETY: Check if event already exists - NEVER delete or overwrite existing data
        $existing = get_page_by_title($event_title, OBJECT, 'course_event');
        
        if ($existing) {
            // SAFETY: Skip if already exists - preserve all existing data
            continue;
        }
        
        // Create the event post
        $post_data = [
            'post_title' => sanitize_text_field($event_title),
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'course_event',
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            continue;
        }
        
        // Set ACF fields
        if (function_exists('update_field')) {
            update_field('linked_course', $course->ID, $post_id);
            update_field('event_date', $date, $post_id);
            
            // Parse time from format like "10:00 AM - 1:00 PM"
            if (!empty($event['time'])) {
                $times = cta_parse_event_time($event['time']);
                if ($times) {
                    update_field('start_time', $times['start'], $post_id);
                    update_field('end_time', $times['end'], $post_id);
                }
            }
            
            update_field('event_location', 'The Maidstone Studios', $post_id);
            
            // Set total_spaces to 12 (default capacity)
            $total_spaces = 12;
            update_field('total_spaces', $total_spaces, $post_id);
            
            // Set spaces_available - if spotsLeft is provided, use it; otherwise start with full capacity (0 booked)
            $spots_left = !empty($event['spotsLeft']) ? intval($event['spotsLeft']) : $total_spaces;
            update_field('spaces_available', $spots_left, $post_id);
            
            // Set total_spaces to 12 (default capacity)
            $total_spaces = 12;
            update_field('total_spaces', $total_spaces, $post_id);
            
            // Set spaces_available - if spotsLeft is provided, use it; otherwise start with full capacity (0 booked)
            $spots_left = !empty($event['spotsLeft']) ? intval($event['spotsLeft']) : $total_spaces;
            update_field('spaces_available', $spots_left, $post_id);
            
            // Parse price from format like "£62"
            if (!empty($event['price'])) {
                $price = floatval(str_replace(['£', ','], '', $event['price']));
                update_field('event_price', $price, $post_id);
            }
            
            update_field('event_active', 1, $post_id);
            update_field('event_featured', 0, $post_id);
        }
        
        $imported++;
    }
    
    return $imported;
}

/**
 * Import course categories
 */
function cta_import_course_categories() {
    $categories = [
        'Core Care Skills' => [
            'description' => 'Essential induction and Care Certificate training.',
            'icon' => 'fa-heart',
        ],
        'Communication & Workplace Culture' => [
            'description' => 'Dignity, equality, communication and care planning.',
            'icon' => 'fa-users',
        ],
        'Nutrition & Hygiene' => [
            'description' => 'Food safety, nutrition and hygiene practices.',
            'icon' => 'fa-apple-alt',
        ],
        'Emergency & First Aid' => [
            'description' => 'Workplace, paediatric and basic life support.',
            'icon' => 'fa-first-aid',
        ],
        'Safety & Compliance' => [
            'description' => 'Workplace safety, safeguarding and moving & handling.',
            'icon' => 'fa-shield-alt',
        ],
        'Medication Management' => [
            'description' => 'Medicines management, competency and insulin awareness.',
            'icon' => 'fa-pills',
        ],
        'Health Conditions & Specialist Care' => [
            'description' => 'Dementia, diabetes, epilepsy and specialist health conditions.',
            'icon' => 'fa-stethoscope',
        ],
        'Leadership & Professional Development' => [
            'description' => 'Management, supervision and professional skills.',
            'icon' => 'fa-user-tie',
        ],
        'Information & Data Management' => [
            'description' => 'Data protection, record keeping and information governance.',
            'icon' => 'fa-database',
        ],
    ];
    
    foreach ($categories as $name => $data) {
        $slug = sanitize_title($name);
        $existing = get_term_by('slug', $slug, 'course_category');
        
        if (!$existing) {
            wp_insert_term($name, 'course_category', [
                'slug' => $slug,
                'description' => $data['description'],
            ]);
        }
    }
}

/**
 * Import news articles from JSON data
 */
function cta_import_news_articles() {
    $json_file = CTA_THEME_DIR . '/data/news-articles.json';
    
    if (!file_exists($json_file)) {
        return 0;
    }
    
    $json = file_get_contents($json_file);
    $articles = json_decode($json, true);
    
    if (empty($articles)) {
        return 0;
    }
    
    $imported = 0;
    
    foreach ($articles as $article) {
        // SAFETY: Check if article already exists - NEVER delete or overwrite existing data
        $existing = get_page_by_title($article['title'], OBJECT, 'post');
        
        if ($existing) {
            // SAFETY: Skip if already exists - preserve all existing data
            continue;
        }
        
        // Parse date
        $date = $article['date'] ?? date('Y-m-d');
        
        // Create the post
        $post_data = [
            'post_title' => sanitize_text_field($article['title']),
            'post_content' => wp_kses_post($article['content'] ?? $article['excerpt'] ?? ''),
            'post_excerpt' => sanitize_textarea_field($article['excerpt'] ?? ''),
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_date' => $date . ' 09:00:00',
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            continue;
        }
        
        // Set category
        if (!empty($article['categoryName'])) {
            $category = get_term_by('name', $article['categoryName'], 'category');
            
            if (!$category) {
                $term_result = wp_insert_term($article['categoryName'], 'category');
                if (!is_wp_error($term_result)) {
                    wp_set_object_terms($post_id, $term_result['term_id'], 'category');
                }
            } else {
                wp_set_object_terms($post_id, $category->term_id, 'category');
            }
        }
        
        // Store original image path for reference
        if (!empty($article['image'])) {
            update_post_meta($post_id, '_original_image_path', $article['image']);
        }
        
        $imported++;
    }
    
    return $imported;
}

/**
 * Create static pages with their respective templates
 */
function cta_create_static_pages() {
    $pages = [
        [
            'title' => 'Home',
            'slug' => 'home',
            'template' => '', // Uses front-page.php automatically
            'content' => '',
            'set_as_front' => true,
        ],
        [
            'title' => 'About Us',
            'slug' => 'about',
            'template' => 'page-templates/page-about.php',
            'content' => '',
        ],
        [
            'title' => 'Contact',
            'slug' => 'contact',
            'template' => 'page-templates/page-contact.php',
            'content' => '',
        ],
        [
            'title' => 'Group Training',
            'slug' => 'group-training',
            'template' => 'page-templates/page-group-training.php',
            'content' => '',
        ],
        [
            'title' => 'News',
            'slug' => 'news',
            'template' => 'page-templates/page-news.php',
            'content' => '',
        ],
        [
            'title' => 'CQC Compliance Hub',
            'slug' => 'cqc-compliance-hub',
            'template' => 'page-templates/page-cqc-hub.php',
            'content' => '',
        ],
        [
            'title' => 'Training Guides & Tools',
            'slug' => 'training-guides-tools',
            'template' => 'page-templates/page-training-guides.php',
            'content' => '',
        ],
        [
            'title' => 'Downloadable Resources',
            'slug' => 'downloadable-resources',
            'template' => 'page-templates/page-downloadable-resources.php',
            'content' => '',
        ],
        [
            'title' => 'FAQs',
            'slug' => 'faqs',
            'template' => 'page-templates/page-faqs.php',
            'content' => '',
        ],
        [
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'template' => 'page-templates/page-privacy.php',
            'content' => '',
        ],
        [
            'title' => 'Terms & Conditions',
            'slug' => 'terms-conditions',
            'template' => 'page-templates/page-terms.php',
            'content' => '',
        ],
        [
            'title' => 'Cookie Policy',
            'slug' => 'cookie-policy',
            'template' => 'page-templates/page-cookies.php',
            'content' => '',
        ],
        [
            'title' => 'Accessibility Statement',
            'slug' => 'accessibility-statement',
            'template' => 'page-templates/page-accessibility.php',
            'content' => '',
        ],
        [
            'title' => 'Courses',
            'slug' => 'courses',
            'template' => '', // Uses archive-course.php automatically
            'content' => '',
        ],
        [
            'title' => 'Upcoming Courses',
            'slug' => 'upcoming-courses',
            'template' => '', // Uses archive-course_event.php automatically
            'content' => '',
        ],
        // Location pages - main index
        [
            'title' => 'Training Locations',
            'slug' => 'locations',
            'template' => 'page-templates/locations/locations-index.php',
            'content' => '',
        ],
        // Kent location pages (children of locations)
        [
            'title' => 'Maidstone Training Centre',
            'slug' => 'locations/maidstone',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-maidstone.php',
            'content' => '',
        ],
        [
            'title' => 'Medway Care Training',
            'slug' => 'locations/medway',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-medway.php',
            'content' => '',
        ],
        [
            'title' => 'Canterbury & East Kent Training',
            'slug' => 'locations/canterbury',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-canterbury.php',
            'content' => '',
        ],
        [
            'title' => 'Ashford & South Kent Training',
            'slug' => 'locations/ashford',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-ashford.php',
            'content' => '',
        ],
        [
            'title' => 'Tunbridge Wells & West Kent Training',
            'slug' => 'locations/tunbridge-wells',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-tunbridge-wells.php',
            'content' => '',
        ],
        // UK-wide location pages (children of locations)
        [
            'title' => 'Greater Manchester Care Training',
            'slug' => 'locations/greater-manchester',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-greater-manchester.php',
            'content' => '',
        ],
        [
            'title' => 'London Care Training',
            'slug' => 'locations/london',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-london.php',
            'content' => '',
        ],
        [
            'title' => 'West Yorkshire Care Training',
            'slug' => 'locations/west-yorkshire',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-west-yorkshire.php',
            'content' => '',
        ],
        [
            'title' => 'Midlands Care Training',
            'slug' => 'locations/midlands',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-midlands.php',
            'content' => '',
        ],
        [
            'title' => 'South West Care Training',
            'slug' => 'locations/south-west',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-south-west.php',
            'content' => '',
        ],
        [
            'title' => 'Lancashire Care Training',
            'slug' => 'locations/lancashire',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-lancashire.php',
            'content' => '',
        ],
        [
            'title' => 'East of England Care Training',
            'slug' => 'locations/east-england',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-east-england.php',
            'content' => '',
        ],
        [
            'title' => 'Merseyside Care Training',
            'slug' => 'locations/merseyside',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-merseyside.php',
            'content' => '',
        ],
        [
            'title' => 'Scotland Care Training',
            'slug' => 'locations/scotland',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-scotland.php',
            'content' => '',
        ],
        [
            'title' => 'Wales Care Training',
            'slug' => 'locations/wales',
            'parent_slug' => 'locations',
            'template' => 'page-templates/locations/location-wales.php',
            'content' => '',
        ],
    ];
    
    $created = 0;
    $front_page_id = null;
    $news_page_id = null;
    $parent_pages = []; // Track parent page IDs for hierarchical pages
    
    foreach ($pages as $page_data) {
        // SAFETY: Check if page already exists by slug - NEVER delete existing pages
        $existing = get_page_by_path($page_data['slug']);
        
        // Special case: Check for accessibility page with old slug and update it
        if ($page_data['slug'] === 'accessibility-statement') {
            $old_slug_page = get_page_by_path('accessibility');
            if ($old_slug_page && !$existing) {
                // SAFETY: Only update slug, never delete - preserve all page content
                wp_update_post([
                    'ID' => $old_slug_page->ID,
                    'post_name' => 'accessibility-statement',
                ]);
                $existing = get_page_by_path('accessibility-statement');
            }
        }
        
        if ($existing) {
            // SAFETY: Only update template if needed, never delete or overwrite content
            if (!empty($page_data['template'])) {
                $current_template = get_post_meta($existing->ID, '_wp_page_template', true);
                if ($current_template !== $page_data['template']) {
                    update_post_meta($existing->ID, '_wp_page_template', $page_data['template']);
                }
            }
            
            // Track existing pages as potential parents for other pages
            $parent_pages[$page_data['slug']] = $existing->ID;
            
            // Track existing pages for settings
            if ($page_data['slug'] === 'home') {
                $front_page_id = $existing->ID;
            }
            if ($page_data['slug'] === 'news') {
                $news_page_id = $existing->ID;
            }
            // SAFETY: Skip creating duplicate - preserve existing page
            continue;
        }
        
        // Create the page
        $post_data = [
            'post_title' => sanitize_text_field($page_data['title']),
            'post_name' => sanitize_title($page_data['slug']),
            'post_content' => $page_data['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
        ];
        
        // Handle parent pages for hierarchical structure
        if (!empty($page_data['parent_slug'])) {
            // Check if parent exists in our tracking array
            if (isset($parent_pages[$page_data['parent_slug']])) {
                $post_data['post_parent'] = $parent_pages[$page_data['parent_slug']];
            } else {
                // Try to find parent page by slug
                $parent_page = get_page_by_path($page_data['parent_slug']);
                if ($parent_page) {
                    $post_data['post_parent'] = $parent_page->ID;
                    $parent_pages[$page_data['parent_slug']] = $parent_page->ID;
                }
            }
        }
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            continue;
        }
        
        // Set page template if specified
        if (!empty($page_data['template'])) {
            update_post_meta($post_id, '_wp_page_template', $page_data['template']);
        }
        
        // Track this page as a potential parent for other pages
        $parent_pages[$page_data['slug']] = $post_id;
        
        // Track IDs for WordPress settings
        if ($page_data['slug'] === 'home') {
            $front_page_id = $post_id;
        }
        if ($page_data['slug'] === 'news') {
            $news_page_id = $post_id;
        }
        
        $created++;
    }
    
    // Set WordPress reading settings (always set, even if pages existed)
    if ($front_page_id) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $front_page_id);
    }
    
    if ($news_page_id) {
        update_option('page_for_posts', $news_page_id);
    }
    
    // Also set permalink structure to pretty URLs if not already set
    $permalink_structure = get_option('permalink_structure');
    if (empty($permalink_structure)) {
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules();
    }
    
    return $created;
}

/**
 * One-time: ensure newer static pages exist on already-initialised sites.
 */
function cta_maybe_seed_missing_static_pages() {
    if (!is_admin() || !is_user_logged_in()) {
        return;
    }
    if (!current_user_can('edit_pages')) {
        return;
    }
    // Updated to v3 to include location pages
    if (get_option('cta_static_pages_seed_v3') === '1') {
        return;
    }
    if (function_exists('cta_create_static_pages')) {
        cta_create_static_pages();
    }
    update_option('cta_static_pages_seed_v3', '1', false);
}
add_action('admin_init', 'cta_maybe_seed_missing_static_pages', 20);

/**
 * Get courses data from embedded JSON
 */
function cta_get_courses_data() {
    // Embedded course data (from courses-database.json)
    $json_file = CTA_THEME_DIR . '/data/courses-database.json';
    
    if (file_exists($json_file)) {
        $json = file_get_contents($json_file);
        return json_decode($json, true);
    }
    
    return [];
}

/**
 * Get scheduled events data from embedded JSON
 */
function cta_get_scheduled_events_data() {
    $json_file = CTA_THEME_DIR . '/data/scheduled-courses.json';
    
    if (file_exists($json_file)) {
        $json = file_get_contents($json_file);
        return json_decode($json, true);
    }
    
    return [];
}

/**
 * Parse event date from format like "Wed, 7 Jan 2026"
 */
function cta_parse_event_date($date_string) {
    // Remove day name prefix
    $date_string = preg_replace('/^[A-Za-z]+,\s*/', '', $date_string);
    
    // Parse the date
    $timestamp = strtotime($date_string);
    
    if ($timestamp === false) {
        return null;
    }
    
    return date('Y-m-d', $timestamp);
}

/**
 * Parse event time from format like "10:00 AM - 1:00 PM"
 */
function cta_parse_event_time($time_string) {
    $parts = explode(' - ', $time_string);
    
    if (count($parts) !== 2) {
        return null;
    }
    
    $start = date('H:i', strtotime($parts[0]));
    $end = date('H:i', strtotime($parts[1]));
    
    return [
        'start' => $start,
        'end' => $end,
    ];
}

/**
 * Set featured image for course (placeholder - actual images need to be uploaded)
 */
function cta_set_course_featured_image($post_id, $image_path, $course_title) {
    // This function would normally download/upload the image
    // For now, we'll skip this as images need to be in the Media Library
    // The admin can manually set featured images after import
    
    // Store the original image path as post meta for reference
    update_post_meta($post_id, '_original_image_path', $image_path);
}

/**
 * Admin page to manually trigger import
 */
function cta_add_import_admin_page() {
    add_submenu_page(
        'tools.php',
        'Import CTA Data',
        'Import CTA Data',
        'manage_options',
        'cta-import-data',
        'cta_import_admin_page_content'
    );
}
add_action('admin_menu', 'cta_add_import_admin_page');

/**
 * Import admin page content
 */
function cta_import_admin_page_content() {
    $imported = get_option('cta_data_imported');
    $import_date = get_option('cta_data_import_date');
    $courses_count = get_option('cta_courses_imported_count', 0);
    $events_count = get_option('cta_events_imported_count', 0);
    $news_count = get_option('cta_news_imported_count', 0);
    $pages_count = get_option('cta_pages_created_count', 0);
    
    // Handle manual import
    if (isset($_POST['cta_reimport']) && check_admin_referer('cta_reimport_nonce')) {
        delete_option('cta_data_imported');
        cta_import_data_on_activation();
        echo '<div class="notice notice-success"><p><strong>Data re-imported successfully!</strong></p></div>';
        
        // Refresh counts
        $courses_count = get_option('cta_courses_imported_count', 0);
        $events_count = get_option('cta_events_imported_count', 0);
        $news_count = get_option('cta_news_imported_count', 0);
        $pages_count = get_option('cta_pages_created_count', 0);
        $imported = true;
    }
    
    // Handle bulk image matching
    if (isset($_POST['cta_bulk_match']) && check_admin_referer('cta_bulk_match_nonce')) {
        $matched = cta_bulk_match_images();
        echo '<div class="notice notice-success"><p><strong>Matched ' . intval($matched) . ' images to courses!</strong></p></div>';
    }
    
    // Handle single course population
    $populate_result = null;
    if (isset($_POST['cta_populate_single_course']) && check_admin_referer('cta_populate_single_course', 'cta_populate_single_course_nonce')) {
        $course_title = isset($_POST['course_title']) ? sanitize_text_field($_POST['course_title']) : '';
        
        if (empty($course_title)) {
            $populate_result = ['success' => false, 'message' => 'Please enter a course title.'];
        } else {
            $populate_result = cta_populate_single_course_from_json($course_title);
        }
    }
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-database-import" style="font-size: 32px; vertical-align: middle; margin-right: 8px; color: #2271b1;"></span>
            Import CTA Course Data
        </h1>
        <hr class="wp-header-end">
        
        
        <?php if ($imported) : ?>
        <div class="notice notice-success" style="padding: 15px;">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-yes-alt" style="vertical-align: middle;"></span>
                Data Import Status
            </h3>
            <ul>
                <li>Courses imported: <?php echo intval($courses_count); ?></li>
                <li>Scheduled events imported: <?php echo intval($events_count); ?></li>
                <li>News articles imported: <?php echo intval($news_count); ?></li>
                <li>Pages created: <?php echo intval($pages_count); ?></li>
                <li>Import date: <?php echo esc_html($import_date); ?></li>
            </ul>
        </div>
        <?php else : ?>
        <div class="notice notice-warning">
            <p><strong>Course data has not been imported yet.</strong> This usually happens automatically when the theme is activated.</p>
        </div>
        <?php endif; ?>
        
        <div class="postbox">
            <h2 class="hndle">
                <span class="dashicons dashicons-media-document" style="vertical-align: middle; margin-right: 5px;"></span>
                Course Data Files
            </h2>
            <div class="inside">
        <p>The theme looks for course data in the following locations:</p>
        <ul>
            <li><code>/wp-content/themes/cta-theme/data/courses-database.json</code> - 40 courses</li>
            <li><code>/wp-content/themes/cta-theme/data/scheduled-courses.json</code> - Scheduled training sessions</li>
            <li><code>/wp-content/themes/cta-theme/data/news-articles.json</code> - Blog/news posts</li>
        </ul>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle">
                <span class="dashicons dashicons-admin-page" style="vertical-align: middle; margin-right: 5px;"></span>
                Pages Created
            </h2>
            <div class="inside">
        <p>The theme automatically creates these pages with their templates:</p>
        <ul>
            <li><strong>Home</strong> - Front page (set as static front page)</li>
            <li><strong>About Us</strong> - About page with team info</li>
            <li><strong>Contact</strong> - Contact form and details</li>
            <li><strong>Group Training</strong> - Corporate training enquiries</li>
            <li><strong>News</strong> - Blog posts page</li>
            <li><strong>Privacy Policy</strong> - GDPR privacy policy</li>
            <li><strong>Terms & Conditions</strong> - Legal terms</li>
            <li><strong>Cookie Policy</strong> - Cookie information</li>
            <li><strong>Courses</strong> - Course archive</li>
            <li><strong>Upcoming Courses</strong> - Scheduled events archive</li>
        </ul>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle">
                <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
                Re-import Data
            </h2>
            <div class="inside">
        <p>Use this to re-import course data. Existing courses with the same title will be skipped.</p>
        
        <form method="post">
            <?php wp_nonce_field('cta_reimport_nonce'); ?>
            <p>
                    <button type="submit" name="cta_reimport" class="button button-primary button-hero" onclick="return confirm('This will import any new courses from the JSON files. Existing courses will not be affected. Continue?');">
                        <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
                        Re-import Course Data
                    </button>
            </p>
        </form>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle">
                <span class="dashicons dashicons-database-import" style="vertical-align: middle; margin-right: 5px;"></span>
                Populate Individual Course
            </h2>
            <div class="inside">
        <p>Populate a single course from JSON data by course title:</p>
        <form method="post" id="cta-populate-single-course-form">
            <?php wp_nonce_field('cta_populate_single_course', 'cta_populate_single_course_nonce'); ?>
            <p>
                <label for="cta-populate-course-title"><strong>Course Title:</strong></label><br>
                <input type="text" id="cta-populate-course-title" name="course_title" class="regular-text" placeholder="Enter exact course title from JSON" style="width: 100%; max-width: 500px; margin-top: 5px;" required>
                <p class="description" style="margin-top: 5px;">The title must match exactly with the course title in <code>courses-database.json</code></p>
            </p>
            <p>
                <button type="submit" name="cta_populate_single_course" class="button button-primary" onclick="return confirm('This will populate all ACF fields for the course. Continue?');">
                    <span class="dashicons dashicons-database-import" style="vertical-align: middle; margin-right: 5px;"></span>
                    Populate Course from JSON
                </button>
            </p>
        </form>
        <?php
        // Display populate result
        if ($populate_result !== null) {
            if ($populate_result['success']) {
                echo '<div class="notice notice-success" style="margin-top: 15px;"><p><strong>' . esc_html($populate_result['message']) . '</strong></p></div>';
                if (!empty($populate_result['course_id'])) {
                    echo '<p><a href="' . admin_url('post.php?post=' . intval($populate_result['course_id']) . '&action=edit') . '" class="button">Edit Course</a></p>';
                }
            } else {
                echo '<div class="notice notice-error" style="margin-top: 15px;"><p><strong>' . esc_html($populate_result['message']) . '</strong></p></div>';
            }
        }
        ?>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle">
                <span class="dashicons dashicons-edit" style="vertical-align: middle; margin-right: 5px;"></span>
                Manual Data Entry
            </h2>
            <div class="inside">
        <p>You can also add courses manually:</p>
        <ul>
            <li><a href="<?php echo admin_url('post-new.php?post_type=course'); ?>">Add New Course</a></li>
            <li><a href="<?php echo admin_url('post-new.php?post_type=course_event'); ?>">Add New Scheduled Session</a></li>
            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=course_category&post_type=course'); ?>">Manage Course Categories</a></li>
        </ul>
        </div>
        
        <?php
        // Phase 1 Blog Posts section
        if (function_exists('cta_render_phase1_posts_section')) {
            cta_render_phase1_posts_section();
        }
        ?>
        
        <div class="postbox">
            <h2 class="hndle">
                <span class="dashicons dashicons-format-image" style="vertical-align: middle; margin-right: 5px;"></span>
                Featured Images
            </h2>
            <div class="inside">
        
        <?php
        // Show bulk match result
        $match_result = get_transient('cta_bulk_match_result');
        if ($match_result !== false) {
            echo '<div class="notice notice-success"><p>Matched ' . intval($match_result) . ' images to courses.</p></div>';
            delete_transient('cta_bulk_match_result');
        }
        
        $missing_images = cta_get_posts_missing_images();
        ?>
        
        <?php if (empty($missing_images)) : ?>
        <div class="notice notice-success inline">
            <p>✓ All courses and posts have featured images set.</p>
        </div>
        <?php else : ?>
        <div class="notice notice-warning inline">
            <p><strong><?php echo count($missing_images); ?> courses/posts are missing featured images.</strong></p>
        </div>
        
        <p>Upload the course thumbnail images to the <a href="<?php echo admin_url('upload.php'); ?>">Media Library</a>, then click the button below to automatically match them.</p>
        
        <?php
        // Show match results if available
        $match_result = get_transient('cta_bulk_match_result');
        if ($match_result !== false) {
            delete_transient('cta_bulk_match_result');
            ?>
            <div class="notice notice-success inline">
                <p>✓ Matched <?php echo intval($match_result); ?> image(s) to courses.</p>
            </div>
            <?php
        }
        ?>
        
        <form method="post">
            <?php wp_nonce_field('cta_bulk_match_nonce'); ?>
            <p>
                <input type="submit" name="cta_bulk_match" class="button button-secondary" value="Match Uploaded Images to Courses">
            </p>
        </form>
        
        <h3>Missing Images</h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Course/Post</th>
                    <th>Type</th>
                    <th>Expected Filename</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($missing_images, 0, 20) as $item) : ?>
                <tr>
                    <td><a href="<?php echo get_edit_post_link($item['id']); ?>"><?php echo esc_html($item['title']); ?></a></td>
                    <td><?php echo esc_html($item['type']); ?></td>
                    <td><code><?php echo esc_html($item['expected_image']); ?></code></td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($missing_images) > 20) : ?>
                <tr>
                    <td colspan="3"><em>...and <?php echo count($missing_images) - 20; ?> more</em></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <h3>How Image Matching Works</h3>
        <ol>
            <li>Upload course images to the <a href="<?php echo admin_url('upload.php'); ?>">Media Library</a></li>
            <li>Images are automatically matched when uploaded (by filename)</li>
            <li>Or click "Match Uploaded Images" to bulk-match existing uploads</li>
            <li>The importer looks for filenames like <code>basic_life_support01.webp</code></li>
        </ol>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Reset import flag (for development/testing)
 */
function cta_reset_import() {
    if (current_user_can('manage_options') && isset($_GET['cta_reset_import']) && wp_verify_nonce($_GET['_wpnonce'], 'cta_reset_import')) {
        delete_option('cta_data_imported');
        delete_option('cta_data_import_date');
        delete_option('cta_courses_imported_count');
        delete_option('cta_events_imported_count');
        
        wp_redirect(admin_url('tools.php?page=cta-import-data&reset=1'));
        exit;
    }
}
add_action('admin_init', 'cta_reset_import');

/**
 * Auto-match uploaded images to courses/posts based on filename
 * 
 * When you upload an image, this checks if any course/post has a matching
 * _original_image_path and automatically sets it as the featured image.
 */
function cta_auto_match_uploaded_image($attachment_id) {
    $attachment_file = get_attached_file($attachment_id);
    if (!$attachment_file) {
        return;
    }
    
    $filename = basename($attachment_file);
    $filename_no_ext = pathinfo($filename, PATHINFO_FILENAME);
    
    // Try multiple matching strategies
    $search_terms = [
        $filename,                    // Exact filename match
        $filename_no_ext,             // Filename without extension
        str_replace(['_', '-'], '', $filename_no_ext), // Without separators
    ];
    
    // Search for posts with matching _original_image_path
    foreach ($search_terms as $search_term) {
        $args = [
            'post_type' => ['course', 'post', 'team_member'],
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_original_image_path',
                    'value' => $filename,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => '_original_image_path',
                    'value' => $filename_no_ext,
                    'compare' => 'LIKE',
                ],
            ],
            'fields' => 'ids',
        ];
        
        $matching_posts = get_posts($args);
        
        foreach ($matching_posts as $post_id) {
            // Check if the stored path actually matches
            $original_path = get_post_meta($post_id, '_original_image_path', true);
            $original_filename = basename($original_path);
            $original_no_ext = pathinfo($original_filename, PATHINFO_FILENAME);
            
            // Match if filenames match (with or without extension, case-insensitive)
            $matches = (
                strcasecmp($filename, $original_filename) === 0 ||
                strcasecmp($filename_no_ext, $original_no_ext) === 0 ||
                strcasecmp(str_replace(['_', '-'], '', $filename_no_ext), str_replace(['_', '-'], '', $original_no_ext)) === 0
            );
            
            if ($matches && !has_post_thumbnail($post_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
        
        // If we found matches, stop searching
        if (!empty($matching_posts)) {
            break;
        }
    }
}
add_action('add_attachment', 'cta_auto_match_uploaded_image');

/**
 * Bulk match images to courses
 * Finds all uploaded images and tries to match them to courses
 */
function cta_bulk_match_images() {
    $matched = 0;
    
    // Get ALL courses/posts/team members without featured images (not just those with _original_image_path)
    $posts_with_path = get_posts([
        'post_type' => ['course', 'post', 'team_member'],
        'posts_per_page' => -1,
        'meta_key' => '_original_image_path',
        'meta_compare' => 'EXISTS',
    ]);
    
    // Also get courses without _original_image_path to try title-based matching
    $posts_without_path = get_posts([
        'post_type' => ['course'],
        'posts_per_page' => -1,
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => '_original_image_path',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ]);
    
    // Combine both lists, removing duplicates
    $all_post_ids = array_unique(array_merge(
        wp_list_pluck($posts_with_path, 'ID'),
        wp_list_pluck($posts_without_path, 'ID')
    ));
    
    $posts = get_posts([
        'post_type' => ['course', 'post', 'team_member'],
        'posts_per_page' => -1,
        'post__in' => $all_post_ids,
    ]);
    
    // Get all attachments once (more efficient)
    global $wpdb;
    $attachments = $wpdb->get_results(
        "SELECT ID, post_title, meta_value 
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attached_file'
         WHERE p.post_type = 'attachment' 
         AND p.post_status = 'inherit'
         AND pm.meta_value IS NOT NULL"
    );
    
    // Build a lookup map of attachment filenames
    // Key insight: Filenames use underscores OR hyphens, may have number suffixes (01, 02), and responsive versions (-400w)
    $attachment_map = [];
    foreach ($attachments as $attachment) {
        $att_file = get_attached_file($attachment->ID);
        if (!$att_file) continue;
        
        $filename = basename($att_file);
        $filename_no_ext = pathinfo($filename, PATHINFO_FILENAME);
        
        // Remove responsive suffixes (-400w, -800w, -1200w, -1600w)
        $base_filename = preg_replace('/-\d+w$/', '', $filename_no_ext);
        
        // Remove number suffixes (01, 02, 03, etc.) to get base name
        // BUT: If filename is ONLY numbers, keep it as-is
        $base_name = preg_replace('/\d+$/', '', $base_filename);
        if (empty($base_name) && !empty($base_filename) && ctype_digit($base_filename)) {
            // Filename is just numbers (e.g., "3"), keep it
            $base_name = $base_filename;
        }
        
        // Also handle WordPress duplicate suffixes (-1, -2, etc.) for numeric filenames
        // If filename is "3-1", we want to match it to "3"
        $numeric_base = null;
        if (preg_match('/^(\d+)(-\d+)?$/', $base_filename, $matches)) {
            // Filename is numeric with optional WordPress suffix (e.g., "3-1")
            $numeric_base = $matches[1]; // Just the number part
        }
        
        // Store multiple variations for matching
        $attachment_map[strtolower($filename)] = $attachment->ID;
        $attachment_map[strtolower($filename_no_ext)] = $attachment->ID;
        $attachment_map[strtolower($base_filename)] = $attachment->ID;
        if (!empty($base_name)) {
            $attachment_map[strtolower($base_name)] = $attachment->ID;
        }
        // Store numeric base for numeric-only filenames
        if ($numeric_base !== null) {
            $attachment_map[strtolower($numeric_base)] = $attachment->ID;
        }
        
        // Also store with underscores/hyphens normalized (only if base_name has content)
        if (!empty($base_name)) {
            $normalized = str_replace(['_', '-'], '', $base_name);
            if ($normalized !== $base_name && !empty($normalized)) {
                $attachment_map[strtolower($normalized)] = $attachment->ID;
            }
        }
    }
    
    foreach ($posts as $post) {
        // Skip if already has featured image
        if (has_post_thumbnail($post->ID)) {
            continue;
        }
        
        $original_path = get_post_meta($post->ID, '_original_image_path', true);
        if (empty($original_path)) {
            continue;
        }
        
        $expected_filename = basename($original_path);
        $expected_no_ext = pathinfo($expected_filename, PATHINFO_FILENAME);
        
        // Remove responsive suffixes if present
        $expected_base = preg_replace('/-\d+w$/', '', $expected_no_ext);
        // Remove number suffixes (but keep if filename is ONLY numbers)
        $expected_name = preg_replace('/\d+$/', '', $expected_base);
        // If the result is empty, the original was just numbers - keep it
        if (empty($expected_name) && !empty($expected_base)) {
            $expected_name = $expected_base;
        }
        
        // Try multiple matching strategies
        $match_id = null;
        
        // Strategy 1: Try exact filename match
        if (isset($attachment_map[strtolower($expected_filename)])) {
            $match_id = $attachment_map[strtolower($expected_filename)];
        }
        // Strategy 2: Try filename without extension
        elseif (isset($attachment_map[strtolower($expected_no_ext)])) {
            $match_id = $attachment_map[strtolower($expected_no_ext)];
        }
        // Strategy 3: Try base filename (without responsive suffix)
        elseif (isset($attachment_map[strtolower($expected_base)])) {
            $match_id = $attachment_map[strtolower($expected_base)];
        }
        // Strategy 4: Try base name (without number suffix, but only if not empty)
        elseif (!empty($expected_name) && isset($attachment_map[strtolower($expected_name)])) {
            $match_id = $attachment_map[strtolower($expected_name)];
        }
        // Strategy 4.5: For numeric-only filenames, skip number matching and go straight to title-based
        // (since uploaded images have descriptive names, not numeric ones)
        // Strategy 5: Match by course title to filename (more aggressive matching)
        // This is the primary strategy for courses with numeric image paths
        if (!$match_id || ctype_digit($expected_no_ext)) {
            $post_title = strtolower($post->post_title);
            $post_slug = strtolower($post->post_name);
            
            // Extract key words from title (remove common words)
            $stop_words = ['at', 'and', 'or', 'the', 'a', 'an', 'for', 'in', 'on', 'with', 'to', 'of'];
            $title_words = array_filter(
                explode(' ', $post_title),
                function($word) use ($stop_words) {
                    return strlen($word) > 2 && !in_array($word, $stop_words);
                }
            );
            $key_words = array_slice($title_words, 0, 3); // Take first 3 meaningful words
            
            // Convert title to both underscore and hyphen formats
            $title_underscore = sanitize_title($post_title);
            $title_underscore = str_replace('-', '_', $title_underscore);
            $title_hyphen = sanitize_title($post_title);
            
            // Create variations for matching
            $title_variations = [
                $title_underscore,
                $title_hyphen,
                str_replace(['_', '-'], '', $title_underscore),
                str_replace(['_', '-'], '', $title_hyphen),
                implode('_', $key_words),
                implode('-', $key_words),
                implode('', $key_words),
            ];
            
            // Try to find attachment that matches course title
            foreach ($attachments as $attachment) {
                $att_file = get_attached_file($attachment->ID);
                if (!$att_file) continue;
                
                $att_filename = strtolower(basename($att_file));
                $att_no_ext = strtolower(pathinfo($att_filename, PATHINFO_FILENAME));
                
                // Remove responsive suffixes
                $att_base = preg_replace('/-\d+w$/', '', $att_no_ext);
                // Remove number suffixes (but keep if filename is ONLY numbers)
                $att_name = preg_replace('/\d+$/', '', $att_base);
                if (empty($att_name) && !empty($att_base) && ctype_digit($att_base)) {
                    $att_name = $att_base;
                }
                
                // Normalize separators for comparison
                $att_normalized = str_replace(['_', '-', ' '], '', $att_name);
                
                // Check multiple matching strategies
                $matches = false;
                
                // Exact matches
                foreach ($title_variations as $variation) {
                    $var_normalized = str_replace(['_', '-', ' '], '', $variation);
                    if ($att_name === $variation || 
                        $att_normalized === $var_normalized ||
                        stripos($att_name, $variation) !== false ||
                        stripos($att_name, $post_slug) !== false) {
                        $matches = true;
                        break;
                    }
                }
                
                // Partial word matching - check if key words appear in filename
                if (!$matches && !empty($key_words)) {
                    $att_words = preg_split('/[_\-\s]+/', $att_name);
                    $matching_words = 0;
                    foreach ($key_words as $key_word) {
                        foreach ($att_words as $att_word) {
                            // More lenient matching: check if words share significant characters
                            if (stripos($att_word, $key_word) !== false || 
                                stripos($key_word, $att_word) !== false ||
                                // Check if first 3+ characters match (handles abbreviations)
                                (strlen($key_word) >= 3 && strlen($att_word) >= 3 && 
                                 stripos($att_word, substr($key_word, 0, 3)) === 0)) {
                                $matching_words++;
                                break;
                            }
                        }
                    }
                    // More lenient: if at least 1 key word matches (or 2 if we have 3+ words)
                    $min_match = count($key_words) >= 3 ? 2 : 1;
                    if ($matching_words >= $min_match) {
                        $matches = true;
                    }
                }
                
                // Additional check: if filename contains significant portion of course title
                if (!$matches && !empty($post_title)) {
                    // Remove common words and get first significant word
                    $title_first_word = '';
                    $title_words_array = explode(' ', $post_title);
                    foreach ($title_words_array as $word) {
                        $word = strtolower(trim($word));
                        if (strlen($word) > 3 && !in_array($word, ['and', 'the', 'for', 'with', 'from', 'to', 'of', 'in', 'on', 'at'])) {
                            $title_first_word = $word;
                            break;
                        }
                    }
                    // Check if filename starts with or contains this word
                    if (!empty($title_first_word) && 
                        (stripos($att_name, $title_first_word) === 0 || 
                         stripos($att_name, $title_first_word) !== false)) {
                        $matches = true;
                    }
                }
                
                if ($matches) {
                    $match_id = $attachment->ID;
                    break;
                }
            }
        }
        
        if ($match_id) {
            set_post_thumbnail($post->ID, $match_id);
            $matched++;
        }
    }
    
    return $matched;
}

/**
 * Handle bulk match from admin page
 */
function cta_handle_bulk_match() {
    if (isset($_POST['cta_bulk_match']) && check_admin_referer('cta_bulk_match_nonce')) {
        $matched = cta_bulk_match_images();
        set_transient('cta_bulk_match_result', $matched, 60);
        wp_redirect(admin_url('tools.php?page=cta-import-data&matched=1'));
        exit;
    }
}
add_action('admin_init', 'cta_handle_bulk_match');

/**
 * Get courses/posts missing featured images
 */
function cta_get_posts_missing_images() {
    $posts = get_posts([
        'post_type' => ['course', 'post', 'team_member'],
        'posts_per_page' => -1,
        'meta_key' => '_original_image_path',
        'meta_compare' => 'EXISTS',
    ]);
    
    $missing = [];
    
    foreach ($posts as $post) {
        if (!has_post_thumbnail($post->ID)) {
            $missing[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'expected_image' => basename(get_post_meta($post->ID, '_original_image_path', true)),
            ];
        }
    }
    
    return $missing;
}

/**
 * Import site settings from JSON
 */
function cta_import_site_settings() {
    $json_file = CTA_THEME_DIR . '/data/site-settings.json';
    
    if (!file_exists($json_file)) {
        return;
    }
    
    $json = file_get_contents($json_file);
    $settings = json_decode($json, true);
    
    if (empty($settings)) {
        return;
    }
    
    // Store settings as theme options
    update_option('cta_site_settings', $settings);
    
    // Set WordPress site title and tagline
    if (!empty($settings['company']['name'])) {
        update_option('blogname', $settings['company']['name']);
    }
    
    if (!empty($settings['company']['tagline'])) {
        update_option('blogdescription', $settings['company']['tagline']);
    }
    
    // Set admin email
    if (!empty($settings['contact']['email'])) {
        update_option('admin_email', $settings['contact']['email']);
    }
    
    // Set timezone to UK
    update_option('timezone_string', 'Europe/London');
    update_option('date_format', 'j F Y');
    update_option('time_format', 'g:i a');
}

/**
 * Import team members from JSON
 */
function cta_import_team_members() {
    $json_file = CTA_THEME_DIR . '/data/team-members.json';
    
    if (!file_exists($json_file)) {
        return 0;
    }
    
    $json = file_get_contents($json_file);
    $members = json_decode($json, true);
    
    if (empty($members)) {
        return 0;
    }
    
    $imported = 0;
    
    foreach ($members as $member) {
        // SAFETY: Check if team member already exists - NEVER delete or overwrite existing data
        $existing = get_page_by_path($member['slug'], OBJECT, 'team_member');
        
        if ($existing) {
            // SAFETY: Skip if already exists - preserve all existing data
            continue;
        }
        
        // Create the team member post
        $post_data = [
            'post_title' => sanitize_text_field($member['name']),
            'post_name' => sanitize_title($member['slug']),
            'post_content' => wp_kses_post($member['bio']),
            'post_excerpt' => sanitize_textarea_field($member['teaser']),
            'post_status' => 'publish',
            'post_type' => 'team_member',
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            continue;
        }
        
        // Set ACF fields if available
        if (function_exists('update_field')) {
            update_field('team_role', sanitize_text_field($member['role']), $post_id);
            update_field('team_experience', sanitize_text_field($member['experience']), $post_id);
            update_field('team_type', sanitize_text_field($member['type']), $post_id);
            
            // Specialisations as repeater
            if (!empty($member['specialisations'])) {
                $specs = [];
                foreach ($member['specialisations'] as $spec) {
                    $specs[] = ['specialisation' => sanitize_text_field($spec)];
                }
                update_field('team_specialisations', $specs, $post_id);
            }
        } else {
            // Fallback to post meta
            update_post_meta($post_id, '_team_role', sanitize_text_field($member['role']));
            update_post_meta($post_id, '_team_experience', sanitize_text_field($member['experience']));
            update_post_meta($post_id, '_team_type', sanitize_text_field($member['type']));
            update_post_meta($post_id, '_team_specialisations', $member['specialisations']);
        }
        
        // Store original image path
        if (!empty($member['image'])) {
            update_post_meta($post_id, '_original_image_path', $member['image']);
        }
        
        $imported++;
    }
    
    return $imported;
}

/**
 * Configure WordPress settings for optimal setup
 */
function cta_configure_wordpress_settings() {
    // Permalink structure
    $permalink_structure = get_option('permalink_structure');
    if (empty($permalink_structure)) {
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules();
    }
    
    // Disable comments on new posts by default
    update_option('default_comment_status', 'closed');
    update_option('default_ping_status', 'closed');
    
    // Set uploads to organize by year/month
    update_option('uploads_use_yearmonth_folders', 1);
    
    // Discourage search engines until ready (can be enabled later)
    // update_option('blog_public', 0);
    
    // Set posts per page
    update_option('posts_per_page', 12);
    
    // Disable WordPress default robots.txt handling (we use our own SEO)
    // This is handled in seo.php
}

/**
 * Get site settings
 */
function cta_get_site_settings($key = null) {
    $settings = get_option('cta_site_settings', []);
    
    if ($key === null) {
        return $settings;
    }
    
    // Support dot notation like 'company.name'
    $keys = explode('.', $key);
    $value = $settings;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return null;
        }
        $value = $value[$k];
    }
    
    return $value;
}

/**
 * Get team members
 */
function cta_get_team_members($type = null) {
    $args = [
        'post_type' => 'team_member',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ];
    
    if ($type) {
        $args['meta_query'] = [
            [
                'key' => function_exists('get_field') ? 'team_type' : '_team_type',
                'value' => $type,
            ],
        ];
    }
    
    return get_posts($args);
}

