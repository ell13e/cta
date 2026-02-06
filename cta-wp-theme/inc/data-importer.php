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
 * Get post by title (replacement for deprecated get_page_by_title)
 * 
 * @param string $title Post title to search for
 * @param string $post_type Post type to search
 * @return WP_Post|null Post object if found, null otherwise
 */
function cta_get_post_by_title($title, $post_type = 'page') {
    global $wpdb;
    
    $post = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} 
        WHERE post_title = %s 
        AND post_type = %s 
        AND post_status != 'trash'
        LIMIT 1",
        $title,
        $post_type
    ));
    
    if ($post) {
        return get_post($post);
    }
    
    return null;
}

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
        
        // Parse and store reviews
        $reviews_parsed = 0;
        if (function_exists('cta_parse_and_store_reviews')) {
            $reviews_parsed = cta_parse_and_store_reviews();
        }
        
        // Populate new course fields for existing courses
        $courses_populated = 0;
        if (function_exists('cta_populate_course_expanded_fields')) {
            $courses_populated = cta_populate_course_expanded_fields();
        }
        
        // Populate Safeguarding course with SEO-optimized content
        if (function_exists('cta_populate_safeguarding_course_content')) {
            cta_populate_safeguarding_course_content();
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
        update_option('cta_reviews_parsed_count', $reviews_parsed);
        update_option('cta_courses_expanded_populated_count', $courses_populated);
        
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
            
            // Populate expanded content fields if empty
            $intro_paragraph = get_field('course_intro_paragraph', $post_id);
            if (empty($intro_paragraph) && !empty($course['description'])) {
                // Take first 120 words from description
                $words = explode(' ', strip_tags($course['description']));
                $intro = implode(' ', array_slice($words, 0, 120));
                $intro_text = $intro . (count($words) > 120 ? '...' : '');
                update_field('course_intro_paragraph', $intro_text, $post_id);
            }
            
            // Populate benefits if empty
            $benefits = get_field('course_benefits', $post_id);
            if (empty($benefits) || !is_array($benefits) || count($benefits) === 0) {
                $benefits_array = [];
                if ($accreditation && strtolower(trim($accreditation)) !== 'none') {
                    $benefits_array[] = ['benefit' => $accreditation . ' accredited'];
                }
                $benefits_array[] = ['benefit' => 'CPD Certificate upon completion'];
                $benefits_array[] = ['benefit' => 'Digital certificate provided'];
                $benefits_array[] = ['benefit' => 'Training records for CQC evidence'];
                if (!empty($benefits_array)) {
                    update_field('course_benefits', $benefits_array, $post_id);
                }
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
        
        // Set category (limit to 1 category for import - can add secondary manually)
        if (!empty($course['category'])) {
            $category_slug = sanitize_title($course['category']);
            $term = get_term_by('slug', $category_slug, 'course_category');
            
            if (!$term) {
                // Create the category if it doesn't exist
                $term_result = wp_insert_term($course['category'], 'course_category', [
                    'slug' => $category_slug,
                ]);
                if (!is_wp_error($term_result)) {
                    // Remove any existing categories first, then set primary
                    wp_set_object_terms($post_id, [$term_result['term_id']], 'course_category', false);
                }
            } else {
                // Remove any existing categories first, then set primary
                wp_set_object_terms($post_id, [$term->term_id], 'course_category', false);
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
 * Populate multiple courses from JSON data
 * 
 * @param array $course_titles Array of course titles from JSON
 * @return array ['success' => bool, 'message' => string, 'results' => array]
 */
function cta_populate_courses_from_json($course_titles) {
    $courses_data = cta_get_courses_data();
    
    if (empty($courses_data)) {
        return ['success' => false, 'message' => 'No course data found in JSON file.', 'results' => []];
    }
    
    $results = [];
    $success_count = 0;
    $error_count = 0;
    
    foreach ($course_titles as $course_title) {
        $result = cta_populate_single_course_from_json($course_title);
        $results[] = $result;
        
        if ($result['success']) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    $message = sprintf(
        'Processed %d course(s): %d successful, %d failed.',
        count($course_titles),
        $success_count,
        $error_count
    );
    
    return [
        'success' => $success_count > 0,
        'message' => $message,
        'results' => $results
    ];
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
        
        // Populate expanded content fields if empty
        $intro_paragraph = get_field('course_intro_paragraph', $post_id);
        if (empty($intro_paragraph) && !empty($course['description'])) {
            // Take first 120 words from description
            $words = explode(' ', strip_tags($course['description']));
            $intro = implode(' ', array_slice($words, 0, 120));
            $intro_text = $intro . (count($words) > 120 ? '...' : '');
            update_field('course_intro_paragraph', $intro_text, $post_id);
        }
        
        // Populate benefits if empty
        $benefits = get_field('course_benefits', $post_id);
        if (empty($benefits) || !is_array($benefits) || count($benefits) === 0) {
            $benefits_array = [];
            if ($accreditation && strtolower(trim($accreditation)) !== 'none') {
                $benefits_array[] = ['benefit' => $accreditation . ' accredited'];
            }
            $benefits_array[] = ['benefit' => 'CPD Certificate upon completion'];
            $benefits_array[] = ['benefit' => 'Digital certificate provided'];
            $benefits_array[] = ['benefit' => 'Training records for CQC evidence'];
            if (!empty($benefits_array)) {
                update_field('course_benefits', $benefits_array, $post_id);
            }
        }
        
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
    
    // Set category (limit to 1 category for import - can add secondary manually)
    if (!empty($course['category'])) {
        $category_slug = sanitize_title($course['category']);
        $term = get_term_by('slug', $category_slug, 'course_category');
        
        if (!$term) {
            $term_result = wp_insert_term($course['category'], 'course_category', [
                'slug' => $category_slug,
            ]);
            if (!is_wp_error($term_result)) {
                // Get existing categories to preserve secondary if exists
                $existing_terms = wp_get_object_terms($post_id, 'course_category', ['fields' => 'ids']);
                $new_terms = array_slice($existing_terms, 0, 1); // Keep secondary if exists
                array_unshift($new_terms, $term_result['term_id']); // Add primary
                $new_terms = array_slice($new_terms, 0, 2); // Limit to 2
                wp_set_object_terms($post_id, $new_terms, 'course_category', false);
            }
        } else {
            // Get existing categories to preserve secondary if exists
            $existing_terms = wp_get_object_terms($post_id, 'course_category', ['fields' => 'ids']);
            $new_terms = array_slice($existing_terms, 0, 1); // Keep secondary if exists
            array_unshift($new_terms, $term->term_id); // Add primary
            $new_terms = array_slice($new_terms, 0, 2); // Limit to 2
            wp_set_object_terms($post_id, $new_terms, 'course_category', false);
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
    $skipped_no_course = 0;
    $skipped_no_date = 0;
    $skipped_exists = 0;
    $errors = [];
    
    foreach ($events_data as $event) {
        // Find the linked course - try exact match first, then fuzzy match
        $course = cta_get_post_by_title($event['title'], 'course');
        
        // If exact match fails, try fuzzy matching (handles variations like "&" vs "and")
        if (!$course && function_exists('cta_find_course_by_name')) {
            $course_id = cta_find_course_by_name($event['title']);
            if ($course_id) {
                $course = get_post($course_id);
            }
        }
        
        // Last resort: search by title substring
        if (!$course) {
            $courses = get_posts([
                'post_type' => 'course',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                's' => $event['title'],
            ]);
            
            if (!empty($courses)) {
                // Find best match by normalizing titles
                $normalize = function($str) {
                    return strtoupper(preg_replace('/[-_\s&]+/', '', $str));
                };
                
                $normalized_search = $normalize($event['title']);
                $best_match = null;
                $best_score = 0;
                
                foreach ($courses as $c) {
                    $normalized_title = $normalize($c->post_title);
                    if ($normalized_title === $normalized_search) {
                        $course = $c;
                        break;
                    }
                    // Calculate similarity score
                    similar_text($normalized_search, $normalized_title, $score);
                    if ($score > $best_score && $score > 80) { // 80% similarity threshold
                        $best_score = $score;
                        $best_match = $c;
                    }
                }
                
                if (!$course && $best_match) {
                    $course = $best_match;
                }
            }
        }
        
        if (!$course) {
            $skipped_no_course++;
            $errors[] = "Skipped '{$event['title']}' - course not found";
            continue; // Skip if course doesn't exist
        }
        
        // Parse date from format like "Wed, 7 Jan 2026"
        $date = cta_parse_event_date($event['date']);
        
        if (!$date) {
            $skipped_no_date++;
            $errors[] = "Skipped '{$event['title']}' - could not parse date: {$event['date']}";
            continue;
        }
        
        // Create event title
        $event_title = $event['title'] . ' - ' . date('j M Y', strtotime($date));
        
        // SAFETY: Check if event already exists - NEVER delete or overwrite existing data
        $existing = cta_get_post_by_title($event_title, 'course_event');
        
        if ($existing) {
            // SAFETY: Skip if already exists - preserve all existing data
            $skipped_exists++;
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
    
    // Store import results for admin display
    update_option('cta_events_import_last_result', [
        'imported' => $imported,
        'skipped_no_course' => $skipped_no_course,
        'skipped_no_date' => $skipped_no_date,
        'skipped_exists' => $skipped_exists,
        'errors' => $errors,
        'timestamp' => current_time('mysql'),
    ]);
    
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
        $existing = cta_get_post_by_title($article['title'], 'post');
        
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
 * Check if a page with the given slug exists in any status (including trash).
 * Prevents creating duplicates when a page was trashed.
 */
function cta_page_exists_by_slug_any_status($slug) {
    if (!$slug) {
        return null;
    }
    $posts = get_posts([
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => ['publish', 'draft', 'private', 'trash'],
        'posts_per_page' => 1,
        'no_found_rows' => true,
    ]);
    return !empty($posts) ? $posts[0] : null;
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
            'slug' => 'courses-landing',
            'template' => '',
            'content' => '',
        ],
        [
            'title' => 'Upcoming Courses',
            'slug' => 'upcoming-courses-landing',
            'template' => '',
            'content' => '',
        ],
    ];
    
    $created = 0;
    $front_page_id = null;
    $news_page_id = null;
    $parent_pages = []; // Track parent page IDs for hierarchical pages
    
    foreach ($pages as $page_data) {
        // SAFETY: Check if page already exists by slug (any status including trash) - NEVER create duplicates
        $existing = get_page_by_path($page_data['slug']);
        if (!$existing) {
            $existing = cta_page_exists_by_slug_any_status($page_data['slug']);
        }

        // Special case: Check for accessibility page with old slug and update it
        if ($page_data['slug'] === 'accessibility-statement' && !$existing) {
            $old_slug_page = get_page_by_path('accessibility');
            if ($old_slug_page) {
                // SAFETY: Only update slug, never delete - preserve all page content
                wp_update_post([
                    'ID' => $old_slug_page->ID,
                    'post_name' => 'accessibility-statement',
                ]);
                $existing = get_page_by_path('accessibility-statement');
            }
        }

        if ($existing) {
            // SAFETY: Only update template if needed (and page is not trashed), never delete or overwrite content
            if ($existing->post_status !== 'trash' && !empty($page_data['template'])) {
                $current_template = get_post_meta($existing->ID, '_wp_page_template', true);
                if ($current_template !== $page_data['template']) {
                    update_post_meta($existing->ID, '_wp_page_template', $page_data['template']);
                }
            }
            
            // Track existing pages as potential parents for other pages
            if ($existing->post_status !== 'trash') {
                $parent_pages[$page_data['slug']] = $existing->ID;
                if ($page_data['slug'] === 'home') {
                    $front_page_id = $existing->ID;
                }
                if ($page_data['slug'] === 'news') {
                    $news_page_id = $existing->ID;
                }
            }
            // SAFETY: Skip creating duplicate - page exists (or is trashed)
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
 * Option is set first so we only run create once even if it fails or times out.
 */
function cta_maybe_seed_missing_static_pages() {
    if (!is_admin() || !is_user_logged_in()) {
        return;
    }
    if (!current_user_can('edit_pages')) {
        return;
    }
    if (get_option('cta_static_pages_seed_v3') === '1') {
        return;
    }
    // Mark as run immediately so we don't run again on every admin page load
    update_option('cta_static_pages_seed_v3', '1', false);
    if (function_exists('cta_create_static_pages')) {
        cta_create_static_pages();
    }
}
add_action('admin_init', 'cta_maybe_seed_missing_static_pages', 20);

/**
 * On 404 for a known static page slug, create missing static pages and redirect once.
 * Fixes /about/, /group-training/, etc. when those pages were never created.
 */
function cta_create_missing_static_page_on_404() {
    if (!is_404()) {
        return;
    }
    $req_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $home_path = trim((string) parse_url(home_url(), PHP_URL_PATH), '/');
    $path = $home_path !== '' && strpos($req_path, $home_path) === 0
        ? trim(substr($req_path, strlen($home_path)), '/')
        : $req_path;
    // Strip index.php so /index.php/about/ yields slug "about"
    $path = preg_replace('#^index\.php/?#', '', $path);
    $segments = array_values(array_filter(explode('/', $path), function ($s) {
        return $s !== '' && $s !== 'index.php';
    }));
    $slug = (string) ($segments[0] ?? '');
    $allowed_slugs = [
        'home', 'about', 'contact', 'group-training', 'news', 'cqc-compliance-hub',
        'downloadable-resources', 'faqs', 'privacy-policy', 'terms-conditions',
        'cookie-policy', 'accessibility-statement', 'courses', 'upcoming-courses',
    ];
    if (!in_array($slug, $allowed_slugs, true)) {
        return;
    }
    if (get_page_by_path($slug, OBJECT, 'page')) {
        return;
    }
    if (!function_exists('cta_create_static_pages')) {
        return;
    }
    cta_create_static_pages();
    $redirect_url = ($slug === 'home') ? home_url('/') : home_url('/' . $slug . '/');
    wp_safe_redirect($redirect_url, 302, 'CTA Create Missing Page');
    exit;
}
add_action('template_redirect', 'cta_create_missing_static_page_on_404', 0);

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
 * Enqueue styles and scripts only on Import CTA Data admin page
 */
function cta_import_admin_enqueue_scripts($hook) {
    if ($hook !== 'tools_page_cta-import-data') {
        return;
    }
    $version = defined('CTA_THEME_VERSION') ? CTA_THEME_VERSION : '1.0.0';
    wp_enqueue_style(
        'cta-import-admin',
        get_template_directory_uri() . '/assets/css/cta-import-admin.css',
        [],
        $version
    );
    wp_enqueue_script(
        'cta-import-admin',
        get_template_directory_uri() . '/assets/js/cta-import-admin.js',
        [],
        $version,
        true
    );
}
add_action('admin_enqueue_scripts', 'cta_import_admin_enqueue_scripts');

/**
 * Fix single course/event URL conflict: rename Pages that shadow CPT URLs.
 * Callable from admin so single /courses/slug/ and /upcoming-courses/slug/ work.
 *
 * @return array{changed: int, message: string}
 */
function cta_fix_cpt_page_slug_conflict_manual() {
    $changed = 0;
    $courses_page = get_page_by_path('courses', OBJECT, 'page');
    if ($courses_page && $courses_page->post_status === 'publish') {
        wp_update_post(['ID' => $courses_page->ID, 'post_name' => 'courses-landing']);
        $changed++;
    }
    $upcoming_page = get_page_by_path('upcoming-courses', OBJECT, 'page');
    if ($upcoming_page && $upcoming_page->post_status === 'publish') {
        wp_update_post(['ID' => $upcoming_page->ID, 'post_name' => 'upcoming-courses-landing']);
        $changed++;
    }
    if ($changed > 0) {
        flush_rewrite_rules(false);
    }
    $message = $changed === 0
        ? 'No conflicting pages found. Single course and event URLs should already work.'
        : sprintf('Renamed %d page(s). Single course and event URLs will now load correctly.', $changed);
    return ['changed' => $changed, 'message' => $message];
}

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

    // Fix-actions results (shown in-page, no redirect)
    $create_pages_result = null;
    $fix_urls_result = null;
    $cleanup_categories_result = null;

    // Create missing static pages only
    if (isset($_POST['cta_create_missing_pages']) && check_admin_referer('cta_create_missing_pages_nonce')) {
        $create_pages_result = cta_create_static_pages();
    }

    // Fix single course/event URLs (rename shadow pages)
    if (isset($_POST['cta_fix_single_urls']) && check_admin_referer('cta_fix_single_urls_nonce')) {
        $fix_urls_result = cta_fix_cpt_page_slug_conflict_manual();
    }

    // Clean up course categories (enforce 2 max)
    if (isset($_POST['cta_cleanup_categories']) && check_admin_referer('cta_cleanup_categories_nonce')) {
        $cleanup_categories_result = function_exists('cta_cleanup_course_categories') ? cta_cleanup_course_categories() : 0;
    }
    
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
    
    // Handle course events import only
    $events_import_result = null;
    if (isset($_POST['cta_import_events_only']) && check_admin_referer('cta_import_events_nonce')) {
        $events_imported = cta_import_scheduled_events();
        $last_result = get_option('cta_events_import_last_result');
        $events_import_result = [
            'imported' => $events_imported,
            'details' => $last_result,
        ];
    }
    
    // Handle bulk image matching
    if (isset($_POST['cta_bulk_match']) && check_admin_referer('cta_bulk_match_nonce')) {
        $matched = cta_bulk_match_images();
        echo '<div class="notice notice-success"><p><strong>Matched ' . intval($matched) . ' images to courses!</strong></p></div>';
    }
    
    // Handle course population
    $populate_result = null;
    if (isset($_POST['cta_populate_courses']) && check_admin_referer('cta_populate_courses', 'cta_populate_courses_nonce')) {
        $selected_courses = isset($_POST['selected_courses']) ? $_POST['selected_courses'] : [];
        $course_title_fallback = isset($_POST['course_title']) ? sanitize_text_field($_POST['course_title']) : '';
        
        $courses_to_populate = [];
        
        // Get all available courses from JSON
        $courses_data = cta_get_courses_data();
        $all_course_titles = array_keys($courses_data);
        
        // Handle "all" option
        if (in_array('all', $selected_courses)) {
            $courses_to_populate = $all_course_titles;
        } elseif (!empty($selected_courses)) {
            // Use selected courses
            foreach ($selected_courses as $selected) {
                if ($selected !== 'all' && in_array($selected, $all_course_titles)) {
                    $courses_to_populate[] = $selected;
                }
            }
        } elseif (!empty($course_title_fallback)) {
            // Fallback to text input
            $courses_to_populate = [$course_title_fallback];
        }
        
        if (empty($courses_to_populate)) {
            $populate_result = ['success' => false, 'message' => 'Please select at least one course or enter a course title.'];
        } else {
            $populate_result = cta_populate_courses_from_json($courses_to_populate);
        }
    }
    
    // Handle article auto-population
    $articles_result = null;
    if (isset($_POST['cta_create_articles']) && check_admin_referer('cta_create_articles')) {
        if (function_exists('cta_create_articles_from_resources')) {
            $articles_result = cta_create_articles_from_resources();
        }
    }
    
    // Handle bulk session title updates
    $session_titles_result = null;
    if (isset($_POST['cta_update_session_titles']) && check_admin_referer('cta_update_session_titles')) {
        $session_titles_result = cta_bulk_update_session_titles();
    }
    
    ?>
    <div class="wrap cta-import-page">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-database-import" style="font-size: 32px; vertical-align: middle; margin-right: 8px; color: #2271b1;" aria-hidden="true"></span>
            Import CTA Course Data
        </h1>
        <hr class="wp-header-end">

        <nav class="cta-import-jump-nav" aria-label="Page sections">
            <p class="cta-import-jump-nav__title">Jump to section</p>
            <ul class="cta-import-jump-nav__list">
                <li><a href="#cta-fix-issues">Fix common issues</a></li>
                <li><a href="#cta-reference">Data files &amp; pages</a></li>
                <li><a href="#cta-reimport">Re-import &amp; events</a></li>
                <li><a href="#cta-populate">Populate from JSON</a></li>
                <li><a href="#cta-articles">Government articles</a></li>
                <li><a href="#cta-session-titles">Session titles</a></li>
                <li><a href="#cta-manual">Manual entry</a></li>
                <li><a href="#cta-phase1">Phase 1 blog posts</a></li>
                <li><a href="#cta-featured-images">Featured images</a></li>
            </ul>
        </nav>

        <?php
        if ($create_pages_result !== null) {
            $n = (int) $create_pages_result;
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . ($n > 0 ? sprintf('Created %d missing page(s).', $n) : 'No missing pages. All static pages already exist.') . '</strong></p></div>';
        }
        if ($fix_urls_result !== null && is_array($fix_urls_result)) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html($fix_urls_result['message']) . '</strong></p></div>';
        }
        if ($cleanup_categories_result !== null) {
            $n = (int) $cleanup_categories_result;
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . ($n > 0 ? sprintf('Cleaned %d course(s) to a maximum of 2 categories each.', $n) : 'No courses had more than 2 categories.') . '</strong></p></div>';
        }
        ?>

        <?php if ($imported) : ?>
        <div class="notice notice-success cta-import-status" style="padding: 15px;">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-yes-alt" style="vertical-align: middle;" aria-hidden="true"></span>
                Data Import Status
            </h3>
            <ul class="cta-import-status__grid">
                <li>Courses imported: <?php echo intval($courses_count); ?></li>
                <li>Scheduled events imported: <?php echo intval($events_count); ?></li>
                <li>News articles imported: <?php echo intval($news_count); ?></li>
                <li>Pages created: <?php echo intval($pages_count); ?></li>
                <li>Import date: <?php echo esc_html($import_date); ?></li>
            </ul>
            <?php
            $last_result = get_option('cta_events_import_last_result');
            if ($last_result && !empty($last_result['timestamp'])) {
                echo '<p style="margin: 12px 0 0;"><strong>Last event import (' . esc_html($last_result['timestamp']) . '):</strong> ';
                echo intval($last_result['imported']) . ' imported, ' . intval($last_result['skipped_exists']) . ' skipped (already exist).</p>';
            }
            ?>
        </div>
        <?php else : ?>
        <div class="notice notice-warning">
            <p><strong>Course data has not been imported yet.</strong> This usually happens automatically when the theme is activated.</p>
        </div>
        <?php endif; ?>

        <div class="postbox" id="cta-fix-issues">
            <h2 class="hndle">
                <span class="dashicons dashicons-admin-tools" style="vertical-align: middle; margin-right: 5px;" aria-hidden="true"></span>
                Fix common issues
            </h2>
            <div class="inside">
                <p class="description" style="margin-bottom: 16px;">Use these when single course/event pages 404, static pages are missing, or courses have more than 2 categories.</p>
                <div class="cta-import-fix-grid">
                    <div class="cta-import-fix-card">
                        <h3>Create missing static pages</h3>
                        <p>Creates only pages that don’t exist yet (About, Contact, Group Training, etc.). Safe to run multiple times.</p>
                        <form method="post" style="margin: 0;">
                            <?php wp_nonce_field('cta_create_missing_pages_nonce'); ?>
                            <button type="submit" name="cta_create_missing_pages" class="button button-secondary">Create missing pages</button>
                        </form>
                    </div>
                    <div class="cta-import-fix-card">
                        <h3>Fix single course/event URLs</h3>
                        <p>If <code>/courses/course-slug/</code> or <code>/upcoming-courses/event-slug/</code> return 404, run this. It renames the Courses and Upcoming Courses <em>landing</em> pages to <code>courses-landing</code> and <code>upcoming-courses-landing</code> so single URLs resolve correctly.</p>
                        <form method="post" style="margin: 0;">
                            <?php wp_nonce_field('cta_fix_single_urls_nonce'); ?>
                            <button type="submit" name="cta_fix_single_urls" class="button button-secondary">Fix single URLs</button>
                        </form>
                    </div>
                    <div class="cta-import-fix-card">
                        <h3>Clean up course categories</h3>
                        <p>Ensures each course has at most 2 categories. Removes excess; you can also <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=course_category&post_type=course')); ?>">manage categories</a> manually.</p>
                        <form method="post" style="margin: 0;">
                            <?php wp_nonce_field('cta_cleanup_categories_nonce'); ?>
                            <button type="submit" name="cta_cleanup_categories" class="button button-secondary">Clean up categories</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="postbox" id="cta-reference">
            <h2 class="hndle">
                <span class="dashicons dashicons-media-document" style="vertical-align: middle; margin-right: 5px;" aria-hidden="true"></span>
                Data files &amp; pages
            </h2>
            <div class="inside cta-import-reference">
                <details>
                    <summary>Course data file locations</summary>
                    <p style="margin-top: 10px;">The theme looks for course data in the following locations:</p>
                    <ul>
                        <li><code>/wp-content/themes/cta-theme/data/courses-database.json</code> — courses</li>
                        <li><code>/wp-content/themes/cta-theme/data/scheduled-courses.json</code> — scheduled sessions</li>
                        <li><code>/wp-content/themes/cta-theme/data/news-articles.json</code> — blog/news</li>
                    </ul>
                </details>
                <details>
                    <summary>Pages the theme creates</summary>
                    <p style="margin-top: 10px;">The theme automatically creates these pages with their templates:</p>
                    <ul>
                        <li><strong>Home</strong> — front page</li>
                        <li><strong>About Us</strong>, <strong>Contact</strong>, <strong>Group Training</strong>, <strong>News</strong></li>
                        <li><strong>Privacy Policy</strong>, <strong>Terms &amp; Conditions</strong>, <strong>Cookie Policy</strong></li>
                        <li><strong>Courses</strong> — list at <code>/courses/</code>, single at <code>/courses/course-slug/</code></li>
                        <li><strong>Upcoming Courses</strong> — list at <code>/upcoming-courses/</code>, single events at <code>/upcoming-courses/event-slug/</code></li>
                    </ul>
                </details>
            </div>
        </div>

        <div class="postbox" id="cta-reimport">
            <h2 class="hndle">
                <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;" aria-hidden="true"></span>
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
        
        <div class="cta-import-subsection">
        <h3>Import Course Events Only</h3>
        <p>Use this to import only course events from <code>scheduled-courses.json</code> without re-importing all data.</p>
        
        <?php
        if ($events_import_result !== null) {
            echo '<div class="notice notice-' . ($events_import_result['imported'] > 0 ? 'success' : 'warning') . '" style="margin-top: 15px;">';
            echo '<p><strong>Event Import Results:</strong></p>';
            echo '<ul style="margin-left: 20px;">';
            echo '<li>Imported: ' . intval($events_import_result['imported']) . ' events</li>';
            if ($events_import_result['details']) {
                echo '<li>Skipped (course not found): ' . intval($events_import_result['details']['skipped_no_course']) . '</li>';
                echo '<li>Skipped (date parse failed): ' . intval($events_import_result['details']['skipped_no_date']) . '</li>';
                echo '<li>Skipped (already exists): ' . intval($events_import_result['details']['skipped_exists']) . '</li>';
            }
            echo '</ul>';
            
            if ($events_import_result['details'] && !empty($events_import_result['details']['errors'])) {
                echo '<details style="margin-top: 10px;"><summary style="cursor: pointer; font-weight: bold;">View errors (' . count($events_import_result['details']['errors']) . ')</summary>';
                echo '<ul style="margin-top: 10px; margin-left: 20px; max-height: 300px; overflow-y: auto;">';
                foreach (array_slice($events_import_result['details']['errors'], 0, 30) as $error) {
                    echo '<li style="color: #d63638;">' . esc_html($error) . '</li>';
                }
                if (count($events_import_result['details']['errors']) > 30) {
                    echo '<li><em>... and ' . (count($events_import_result['details']['errors']) - 30) . ' more errors</em></li>';
                }
                echo '</ul></details>';
            }
            echo '</div>';
        }
        ?>
        
        <form method="post" style="margin-top: 15px;">
            <?php wp_nonce_field('cta_import_events_nonce'); ?>
            <p>
                <button type="submit" name="cta_import_events_only" class="button button-secondary">
                    <span class="dashicons dashicons-calendar-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                    Import Course Events Only
                </button>
            </p>
        </form>
        </div>
            </div>
        </div>

        <div class="postbox" id="cta-populate">
            <h2 class="hndle">
                <span class="dashicons dashicons-database-import" style="vertical-align: middle; margin-right: 5px;" aria-hidden="true"></span>
                Populate Course Data from JSON
            </h2>
            <div class="inside">
        <p>Select one or more courses to populate from JSON data, or populate all courses at once:</p>
        <form method="post" id="cta-populate-courses-form">
            <?php wp_nonce_field('cta_populate_courses', 'cta_populate_courses_nonce'); ?>
            
            <?php
            $courses_data = cta_get_courses_data();
            $all_course_titles = !empty($courses_data) ? array_keys($courses_data) : [];
            ?>
            <div class="cta-import-course-select-wrap">
                <p>
                    <label for="cta-populate-course-search">Filter courses:</label><br>
                    <input type="text" id="cta-populate-course-search" class="cta-import-course-search" placeholder="Type to filter course list..." autocomplete="off" aria-describedby="cta-populate-course-search-desc">
                </p>
                <p id="cta-populate-course-search-desc" class="screen-reader-text">Filters the course list below as you type.</p>
            <p>
                <label for="cta-populate-selected-courses"><strong>Select Courses:</strong></label><br>
                <select id="cta-populate-selected-courses" name="selected_courses[]" multiple size="10" style="width: 100%; max-width: 600px; margin-top: 5px;">
                    <option value="all" style="font-weight: bold; background-color: #f0f0f0;">-- Select All Courses --</option>
                    <?php if (!empty($all_course_titles)) : ?>
                        <?php foreach ($all_course_titles as $title) : ?>
                            <option value="<?php echo esc_attr($title); ?>"><?php echo esc_html($title); ?></option>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <option disabled>No courses found in JSON data</option>
                    <?php endif; ?>
                </select>
                <p class="description" style="margin-top: 5px;">
                    <strong>Tip:</strong> Hold Ctrl (Windows) or Cmd (Mac) to select multiple courses, or select "Select All Courses" to populate everything.
                </p>
                <script>
                (function() {
                    const select = document.getElementById('cta-populate-selected-courses');
                    if (select) {
                        select.addEventListener('change', function() {
                            const options = Array.from(this.options);
                            const allOption = options.find(opt => opt.value === 'all');
                            const otherOptions = options.filter(opt => opt.value !== 'all');
                            
                            if (allOption && allOption.selected) {
                                // If "all" is selected, select all other options
                                otherOptions.forEach(opt => opt.selected = true);
                            } else if (allOption && !allOption.selected) {
                                // If "all" is deselected, deselect all others
                                const allOthersSelected = otherOptions.every(opt => opt.selected);
                                if (allOthersSelected) {
                                    otherOptions.forEach(opt => opt.selected = false);
                                }
                            } else {
                                // If individual options change, update "all" state
                                const allOthersSelected = otherOptions.every(opt => opt.selected);
                                if (allOthersSelected && otherOptions.length > 0) {
                                    allOption.selected = true;
                                } else if (!allOthersSelected && allOption.selected) {
                                    allOption.selected = false;
                                }
                            }
                        });
                    }
                })();
                </script>
            </p>
            </div>

            <p style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                <label for="cta-populate-course-title"><strong>Or enter course title manually:</strong></label><br>
                <input type="text" id="cta-populate-course-title" name="course_title" class="regular-text" placeholder="Enter exact course title from JSON" style="width: 100%; max-width: 500px; margin-top: 5px;">
                <p class="description" style="margin-top: 5px;">Use this if you can't find the course in the dropdown above</p>
            </p>
            
            <p style="margin-top: 20px;">
                <button type="submit" name="cta_populate_courses" class="button button-primary" onclick="return confirm('This will populate all ACF fields for the selected course(s). Continue?');">
                    <span class="dashicons dashicons-database-import" style="vertical-align: middle; margin-right: 5px;"></span>
                    Populate Selected Courses from JSON
                </button>
            </p>
        </form>
        <?php
        // Display populate result
        if ($populate_result !== null) {
            if ($populate_result['success']) {
                echo '<div class="notice notice-success" style="margin-top: 15px;"><p><strong>' . esc_html($populate_result['message']) . '</strong></p></div>';
                
                // Show individual results if multiple courses
                if (!empty($populate_result['results']) && count($populate_result['results']) > 1) {
                    echo '<details style="margin-top: 10px;"><summary style="cursor: pointer; font-weight: bold;">View individual results</summary><ul style="margin-top: 10px;">';
                    foreach ($populate_result['results'] as $result) {
                        $status = $result['success'] ? '✓' : '✗';
                        $class = $result['success'] ? 'color: green;' : 'color: red;';
                        echo '<li style="' . $class . '">' . esc_html($status . ' ' . $result['message']) . '</li>';
                    }
                    echo '</ul></details>';
                } elseif (!empty($populate_result['results'][0]['course_id'])) {
                    echo '<p><a href="' . admin_url('post.php?post=' . intval($populate_result['results'][0]['course_id']) . '&action=edit') . '" class="button">Edit Course</a></p>';
                }
            } else {
                echo '<div class="notice notice-error" style="margin-top: 15px;"><p><strong>' . esc_html($populate_result['message']) . '</strong></p></div>';
            }
        }
        ?>
            </div>
        </div>
        
        <div class="postbox" id="cta-articles">
            <h2 class="hndle">
                <span class="dashicons dashicons-admin-post" style="vertical-align: middle; margin-right: 5px;" aria-hidden="true"></span>
                Auto-Populate Articles from Government Resources
            </h2>
            <div class="inside">
                <p>Create blog articles automatically from government guidance and resources relevant to care providers and CQC compliance.</p>
                
                <?php
                if (function_exists('cta_get_government_resources_for_articles')) {
                    $resources = cta_get_government_resources_for_articles();
                    $existing_count = 0;
                    foreach ($resources as $resource) {
                        if (get_page_by_title($resource['title'], OBJECT, 'post')) {
                            $existing_count++;
                        }
                    }
                ?>
                <p>Found <strong><?php echo count($resources); ?></strong> government resources ready to be converted to articles.</p>
                <p><?php echo $existing_count; ?> article(s) already exist and will be skipped.</p>
                
                <?php if ($articles_result !== null) : ?>
                    <div class="notice notice-<?php echo ($articles_result['created'] ?? 0) > 0 ? 'success' : 'info'; ?> is-dismissible" style="margin-top: 15px;">
                        <p><strong>
                            <?php 
                            if (isset($articles_result['created']) && isset($articles_result['skipped'])) {
                                echo sprintf(
                                    'Created %d article(s), skipped %d existing article(s).',
                                    $articles_result['created'],
                                    $articles_result['skipped']
                                );
                            } else {
                                echo 'Operation completed.';
                            }
                            ?>
                        </strong></p>
                        
                        <?php if (!empty($articles_result['errors'])) : ?>
                            <details style="margin-top: 10px;">
                                <summary style="cursor: pointer; font-weight: bold;">View errors (<?php echo count($articles_result['errors']); ?>)</summary>
                                <ul style="margin-top: 10px; margin-left: 20px;">
                                    <?php foreach ($articles_result['errors'] as $error) : ?>
                                        <li style="color: #d63638;"><?php echo esc_html($error['title'] ?? ''); ?>: <?php echo esc_html($error['error'] ?? ''); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" style="margin-top: 20px;">
                    <?php wp_nonce_field('cta_create_articles'); ?>
                    <p>
                        <button type="submit" name="cta_create_articles" class="button button-primary" onclick="return confirm('This will create new blog articles from government resources. Articles that already exist will be skipped. Continue?');">
                            <span class="dashicons dashicons-admin-post" style="vertical-align: middle; margin-right: 5px;"></span>
                            Create Articles from Resources
                        </button>
                    </p>
                </form>
                <?php } else { ?>
                    <p class="description">Article auto-population function not available.</p>
                <?php } ?>
            </div>
        </div>
        
        <div class="postbox" id="cta-session-titles">
            <h2 class="hndle">
                <span class="dashicons dashicons-calendar-alt" style="vertical-align: middle; margin-right: 5px;" aria-hidden="true"></span>
                Update Session Titles from Courses
            </h2>
            <div class="inside">
                <p>Bulk update course event titles to match their linked course titles. This will update all course events that have a linked course.</p>
                
                <?php if ($session_titles_result !== null) : ?>
                    <div class="notice notice-<?php echo $session_titles_result['success'] ? 'success' : 'error'; ?> is-dismissible" style="margin-top: 15px;">
                        <p><strong><?php echo esc_html($session_titles_result['message'] ?? 'Operation completed.'); ?></strong></p>
                        <?php if (isset($session_titles_result['updated'])) : ?>
                            <p>Updated <?php echo intval($session_titles_result['updated']); ?> session title(s), skipped <?php echo intval($session_titles_result['skipped'] ?? 0); ?> event(s).</p>
                        <?php endif; ?>
                        
                        <?php if (!empty($session_titles_result['errors'])) : ?>
                            <details style="margin-top: 10px;">
                                <summary style="cursor: pointer; font-weight: bold;">View errors (<?php echo count($session_titles_result['errors']); ?>)</summary>
                                <ul style="margin-top: 10px; margin-left: 20px;">
                                    <?php foreach (array_slice($session_titles_result['errors'], 0, 20) as $error) : ?>
                                        <li style="color: #d63638;"><?php echo esc_html($error['title'] ?? 'Event ID: ' . ($error['event_id'] ?? '')); ?>: <?php echo esc_html($error['error'] ?? ''); ?></li>
                                    <?php endforeach; ?>
                                    <?php if (count($session_titles_result['errors']) > 20) : ?>
                                        <li><em>... and <?php echo count($session_titles_result['errors']) - 20; ?> more errors</em></li>
                                    <?php endif; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php
                $total_events = wp_count_posts('course_event');
                $total_count = ($total_events->publish ?? 0) + ($total_events->draft ?? 0) + ($total_events->private ?? 0);
                ?>
                <p class="description">Total course events: <strong><?php echo intval($total_count); ?></strong></p>
                
                <form method="post" style="margin-top: 20px;">
                    <?php wp_nonce_field('cta_update_session_titles'); ?>
                    <p>
                        <button type="submit" name="cta_update_session_titles" class="button button-primary" onclick="return confirm('This will update all course event titles to match their linked course titles. Continue?');">
                            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
                            Update All Session Titles
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <div class="postbox" id="cta-manual">
            <h2 class="hndle">
                <span class="dashicons dashicons-edit" style="vertical-align: middle; margin-right: 5px;" aria-hidden="true"></span>
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
        
        <div class="postbox" id="cta-featured-images">
            <h2 class="hndle">
                <span class="dashicons dashicons-format-image" style="vertical-align: middle; margin-right: 5px;" aria-hidden="true"></span>
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

/**
 * Parse and store reviews from training_reviews.md
 * Extracts reviews and stores them in ACF options for use in course testimonials
 */
function cta_parse_and_store_reviews() {
    $reviews_file = '/Users/elliesmith/Downloads/training_reviews.md';
    
    // Check if file exists
    if (!file_exists($reviews_file)) {
        // Try relative path from theme directory
        $reviews_file = get_template_directory() . '/../training_reviews.md';
        if (!file_exists($reviews_file)) {
            return 0;
        }
    }
    
    $content = file_get_contents($reviews_file);
    if (empty($content)) {
        return 0;
    }
    
    // Split by horizontal rules (---)
    $sections = preg_split('/^---$/m', $content);
    $reviews = [];
    $review_id = 1;
    
    // Course keywords for matching
    $course_keywords = [
        'first aid' => ['first aid', 'first aid at work', 'faw'],
        'care certificate' => ['care certificate', 'care cert'],
        'medication' => ['medication', 'medication management', 'meds'],
        'moving and handling' => ['moving', 'handling', 'moving and handling', 'manual handling'],
        'health and safety' => ['health', 'safety', 'health and safety', 'h&s'],
        'safeguarding' => ['safeguarding'],
        'train the trainer' => ['train the trainer', 'ttt', 'trainer'],
    ];
    
    foreach ($sections as $section) {
        $section = trim($section);
        if (empty($section)) {
            continue;
        }
        
        // Extract title (## ⭐⭐⭐⭐⭐ [Title])
        if (!preg_match('/^##\s+⭐⭐⭐⭐⭐\s+(.+)$/m', $section, $title_match)) {
            continue;
        }
        $title = trim($title_match[1]);
        
        // Extract quote (paragraph after title, before author)
        $quote_match = preg_match('/^##.*?\n\n(.+?)\n\n\*\*-/s', $section, $quote_m);
        if (!$quote_match) {
            // Try alternative pattern
            $quote_match = preg_match('/^##.*?\n\n(.+?)(?=\n\n\*\*-)/s', $section, $quote_m);
        }
        $quote = $quote_match ? trim($quote_m[1]) : '';
        
        // Extract author (**- [Name]**)
        $author_match = preg_match('/\*\*-\s*(.+?)\s*\*\*/', $section, $author_m);
        $author = $author_match ? trim($author_m[1]) : 'Anonymous';
        
        // Extract date (*[Date]*)
        $date_match = preg_match('/\*\s*([0-9]{2}\.[0-9]{2}\.[0-9]{4})\s*\*/', $section, $date_m);
        $date = $date_match ? trim($date_m[1]) : '';
        
        if (empty($quote)) {
            continue;
        }
        
        // Extract keywords from title and quote
        $text_to_search = strtolower($title . ' ' . $quote);
        $matched_keywords = [];
        $matched_courses = [];
        
        foreach ($course_keywords as $course_name => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($text_to_search, $keyword) !== false) {
                    $matched_keywords[] = $keyword;
                    if (!in_array($course_name, $matched_courses)) {
                        $matched_courses[] = $course_name;
                    }
                    break;
                }
            }
        }
        
        // Create review object
        $review_id_str = 'review_' . str_pad($review_id, 3, '0', STR_PAD_LEFT);
        $reviews[$review_id_str] = [
            'id' => $review_id_str,
            'title' => $title,
            'quote' => $quote,
            'author' => $author,
            'date' => $date,
            'keywords' => array_unique($matched_keywords),
            'course_matches' => $matched_courses,
        ];
        
        $review_id++;
    }
    
    // Store in ACF options
    if (!empty($reviews)) {
        update_option('cta_all_reviews', $reviews);
        
        // Also update ACF field choices for course_selected_reviews
        $choices = [];
        foreach ($reviews as $id => $review) {
            $display = $review['title'] . ' - ' . $review['author'];
            if ($review['date']) {
                $display .= ' (' . $review['date'] . ')';
            }
            $choices[$id] = $display;
        }
        
        // Update the field choices dynamically
        add_filter('acf/load_field/name=course_selected_reviews', function($field) use ($choices) {
            $field['choices'] = $choices;
            return $field;
        });
        
        return count($reviews);
    }
    
    return 0;
}

/**
 * Populate expanded content fields for existing courses on theme activation/update
 * Only populates if fields are empty (doesn't overwrite existing content)
 */
function cta_populate_course_expanded_fields() {
    if (!function_exists('get_field') || !function_exists('update_field')) {
        return 0;
    }
    
    // Get all existing courses
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft', 'private'],
    ]);
    
    if (empty($courses)) {
        return 0;
    }
    
    $populated_count = 0;
    
    foreach ($courses as $course) {
        $post_id = $course->ID;
        $updated = false;
        
        // Populate course_intro_paragraph from course_description if empty
        $intro_paragraph = get_field('course_intro_paragraph', $post_id);
        if (empty($intro_paragraph)) {
            $description = get_field('course_description', $post_id);
            if (!empty($description)) {
                // Take first 120 words from description
                $words = explode(' ', strip_tags($description));
                $intro = implode(' ', array_slice($words, 0, 120));
                $intro_text = $intro . (count($words) > 120 ? '...' : '');
                update_field('course_intro_paragraph', $intro_text, $post_id);
                $updated = true;
            }
        }
        
        // Populate course_benefits from certificate/accreditation if empty
        $benefits = get_field('course_benefits', $post_id);
        if (empty($benefits) || !is_array($benefits) || count($benefits) === 0) {
            $benefits_array = [];
            $certificate = get_field('course_certificate', $post_id);
            $accreditation = get_field('course_accreditation', $post_id);
            
            if ($certificate) {
                $benefits_array[] = ['benefit' => $certificate];
            }
            if ($accreditation && strtolower(trim($accreditation)) !== 'none') {
                $benefits_array[] = ['benefit' => $accreditation . ' accredited'];
            }
            $benefits_array[] = ['benefit' => 'Digital certificate provided'];
            $benefits_array[] = ['benefit' => 'Training records for CQC evidence'];
            
            if (!empty($benefits_array)) {
                update_field('course_benefits', $benefits_array, $post_id);
                $updated = true;
            }
        }
        
        if ($updated) {
            $populated_count++;
        }
    }
    
    return $populated_count;
}
add_action('after_switch_theme', 'cta_populate_course_expanded_fields', 30);

/**
 * Populate Safeguarding course with SEO-optimized content from optimization document
 * This is a one-time population for the specific Safeguarding Level 2 course
 */
function cta_populate_safeguarding_course_content() {
    if (!function_exists('get_field') || !function_exists('update_field')) {
        return false;
    }
    
    // Find Safeguarding course by title or slug
    $safeguarding_course = get_posts([
        'post_type' => 'course',
        'posts_per_page' => 1,
        'post_status' => ['publish', 'draft', 'private'],
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'course_legacy_id',
                'value' => 'safeguarding',
                'compare' => 'LIKE',
            ],
        ],
    ]);
    
    // Try by title
    if (empty($safeguarding_course)) {
        $safeguarding_course = get_posts([
            'post_type' => 'course',
            'posts_per_page' => 1,
            'post_status' => ['publish', 'draft', 'private'],
            'title' => 'Safeguarding',
        ]);
    }
    
    // Try by slug
    if (empty($safeguarding_course)) {
        $safeguarding_course = get_posts([
            'post_type' => 'course',
            'posts_per_page' => 1,
            'post_status' => ['publish', 'draft', 'private'],
            'name' => 'safeguarding-l2',
        ]);
    }
    
    if (empty($safeguarding_course)) {
        return false;
    }
    
    $post_id = $safeguarding_course[0]->ID;
    
    // Only populate if fields are empty (don't overwrite existing content)
    $intro_paragraph = get_field('course_intro_paragraph', $post_id);
    $why_matters = get_field('course_why_matters', $post_id);
    $covered_items = get_field('course_covered_items', $post_id);
    $key_features = get_field('course_key_features', $post_id);
    $faqs = get_field('course_faqs', $post_id);
    
    // A. Opening Paragraph
    if (empty($intro_paragraph)) {
        $intro_text = 'Safeguarding is one of the most critical responsibilities in care work. This Level 2 Safeguarding course provides essential training for all care workers in Kent, covering the recognition, prevention, and reporting of abuse for both vulnerable adults and children. Whether you work in a care home, domiciliary care, or supported living, this course ensures you meet CQC requirements and understand your legal duty to protect those in your care.';
        update_field('course_intro_paragraph', $intro_text, $post_id);
    }
    
    // B. Why Safeguarding Training Matters
    if (empty($why_matters)) {
        $why_text = '<p>Every care worker in the UK has a legal duty to safeguard vulnerable people. The Care Act 2014 and the Care Quality Commission\'s Fundamental Standards make safeguarding training mandatory for all health and social care staff. CQC inspectors specifically check:</p>
<ul>
<li>That all staff have received safeguarding training appropriate to their role</li>
<li>Staff can recognise different types of abuse</li>
<li>Clear reporting procedures are in place and understood</li>
<li>The organisation has a robust safeguarding policy</li>
</ul>
<p>Without proper safeguarding training, care settings risk:</p>
<ul>
<li>CQC compliance failures</li>
<li>Inadequate rating</li>
<li>Legal action</li>
<li>Most importantly: service users being left vulnerable to harm</li>
</ul>
<p>This course ensures you have the knowledge and confidence to identify signs of abuse and take appropriate action to protect the people you support.</p>';
        update_field('course_why_matters', $why_text, $post_id);
    }
    
    // D. What's Covered in Detail (as covered items)
    if (empty($covered_items) || !is_array($covered_items) || count($covered_items) === 0) {
        $covered_items_array = [
            [
                'title' => 'Types of Abuse',
                'description' => 'Understand the six main categories of abuse: physical, emotional, sexual, neglect, financial, and discriminatory. Learn to recognise subtle signs and indicators that might suggest abuse is occurring.',
            ],
            [
                'title' => 'Recognising Signs of Abuse',
                'description' => 'Practical guidance on identifying physical indicators (unexplained injuries, poor hygiene, malnutrition), behavioural changes (withdrawal, fear, aggression), and environmental factors that may suggest abuse.',
            ],
            [
                'title' => 'Safeguarding Procedures',
                'description' => 'Your legal duty to report concerns, who to report to (manager, local authority safeguarding team, CQC), and what happens after you raise a concern. Understanding the difference between safeguarding concerns and alerts.',
            ],
            [
                'title' => 'Protecting Vulnerable Adults',
                'description' => 'Specific considerations when working with adults who lack mental capacity, including the Mental Capacity Act 2005 and how this relates to safeguarding. Understanding consent and when to override it in the person\'s best interests.',
            ],
            [
                'title' => 'Child Safeguarding',
                'description' => 'Recognising abuse in children and young people, understanding different thresholds for concern, and knowing when to involve children\'s social care services.',
            ],
            [
                'title' => 'Whistleblowing',
                'description' => 'Your protected right to report poor practice or abuse, even if it implicates your employer. Understanding the Public Interest Disclosure Act and how whistleblowing policies work.',
            ],
            [
                'title' => 'Reducing Risks',
                'description' => 'How good care practice reduces the likelihood of abuse. Person-centred approaches, maintaining dignity and respect, proper record-keeping, and creating open cultures where concerns can be raised safely.',
            ],
            [
                'title' => 'Legal Framework',
                'description' => 'Overview of the Care Act 2014, Mental Capacity Act 2005, Deprivation of Liberty Safeguards, and how these laws protect vulnerable people.',
            ],
        ];
        update_field('course_covered_items', $covered_items_array, $post_id);
    }
    
    // E. Course Format Details
    $format_details = get_field('course_format_details', $post_id);
    if (empty($format_details)) {
        $format_text = '<p><strong>Duration:</strong> Half day (3 hours)<br>
<strong>Delivery:</strong> Face-to-face training at our Maidstone training centre<br>
<strong>Level:</strong> Level 2 (suitable for all care staff)<br>
<strong>Assessment:</strong> Knowledge check and scenario-based discussions<br>
<strong>Certification:</strong> CPD-accredited certificate valid for employment evidence<br>
<strong>Refresher:</strong> Recommended every 1-2 years</p>
<p>Group bookings available for care homes and agencies wanting to train multiple staff. We can also deliver this course at your premises - contact us for group training options.</p>';
        update_field('course_format_details', $format_text, $post_id);
    }
    
    // F. What Makes Our Training Different (key features)
    if (empty($key_features) || !is_array($key_features) || count($key_features) === 0) {
        $features_array = [
            [
                'icon' => 'fas fa-book',
                'title' => 'Real-World Scenarios',
                'description' => 'We use actual case studies from care settings (anonymised) so you understand how safeguarding situations develop in practice.',
            ],
            [
                'icon' => 'fas fa-user-tie',
                'title' => 'Experienced Trainers',
                'description' => 'Our trainers have worked in care settings and bring first-hand experience of safeguarding investigations and procedures.',
            ],
            [
                'icon' => 'fas fa-shield-alt',
                'title' => 'CQC-Compliant',
                'description' => 'Training mapped to CQC Key Lines of Enquiry and Fundamental Standards, so you can evidence compliance during inspections.',
            ],
            [
                'icon' => 'fas fa-hands-helping',
                'title' => 'Practical Focus',
                'description' => 'Less theory, more practical guidance on what to do when you\'re worried about someone.',
            ],
            [
                'icon' => 'fas fa-users',
                'title' => 'Supportive Environment',
                'description' => 'Small group sizes ensure everyone can ask questions and discuss concerns in confidence.',
            ],
        ];
        update_field('course_key_features', $features_array, $post_id);
    }
    
    // G & H. After the Course (benefits + note)
    $benefits = get_field('course_benefits', $post_id);
    if (empty($benefits) || !is_array($benefits) || count($benefits) === 0) {
        $benefits_array = [
            ['benefit' => 'CPD-accredited Level 2 Safeguarding certificate'],
            ['benefit' => 'Course handout with reporting procedures'],
            ['benefit' => 'Access to our safeguarding resources page'],
            ['benefit' => 'Guidance on refresher training requirements'],
        ];
        update_field('course_benefits', $benefits_array, $post_id);
    }
    
    $after_note = get_field('course_after_note', $post_id);
    if (empty($after_note)) {
        $after_note_text = '<p>Your certificate is recognised by CQC and can be used as evidence of mandatory training completion. Digital copies provided on request.</p>
<p><strong>CQC Inspection Evidence:</strong> During CQC inspections, inspectors will ask: "Have all staff received safeguarding training?", "Can staff describe different types of abuse?", "Do staff know who to report concerns to?" This course ensures you can confidently answer these questions and provide certificate evidence of training. We provide training records suitable for CQC inspection documentation, including date of training, content covered, and certification details.</p>';
        update_field('course_after_note', $after_note_text, $post_id);
    }
    
    // 5. FAQs
    if (empty($faqs) || !is_array($faqs) || count($faqs) === 0) {
        $faqs_array = [
            [
                'question' => 'How often do I need safeguarding training?',
                'answer' => '<p>CQC and most care providers require safeguarding refresher training every 1-2 years. New staff should complete safeguarding training during their induction period, ideally within the first few weeks of employment.</p>',
            ],
            [
                'question' => 'Is this course CQC compliant?',
                'answer' => '<p>Yes. This Level 2 Safeguarding course meets CQC requirements for mandatory safeguarding training. It covers all six types of abuse and reporting procedures as required by the Care Act 2014.</p>',
            ],
            [
                'question' => 'What\'s the difference between Level 1 and Level 2 safeguarding?',
                'answer' => '<p>Level 1 is basic awareness suitable for volunteers or those with minimal contact with vulnerable people. Level 2 is for care workers with direct responsibility for supporting adults or children - this is the level required by most care employers and CQC.</p>',
            ],
            [
                'question' => 'Does this cover children as well as adults?',
                'answer' => '<p>Yes. This course covers safeguarding for both vulnerable adults and children, making it suitable for care workers in settings that support young people or mixed-age service users.</p>',
            ],
            [
                'question' => 'What happens if I report a safeguarding concern?',
                'answer' => '<p>Your manager or safeguarding lead will investigate and may need to report to the local authority safeguarding team. You\'ll be asked to provide details of what you\'ve observed or been told. Your identity is protected throughout the process.</p>',
            ],
            [
                'question' => 'Can I do safeguarding training online?',
                'answer' => '<p>While some basic awareness can be learned online, CQC and most employers require face-to-face safeguarding training for care workers due to the importance of discussion, scenario work, and asking questions. Our half-day course provides this interactive element.</p>',
            ],
            [
                'question' => 'Will I learn about the Mental Capacity Act?',
                'answer' => '<p>Yes, we cover how the Mental Capacity Act relates to safeguarding, particularly when making decisions about reporting concerns for people who lack capacity to consent.</p>',
            ],
            [
                'question' => 'Is this suitable for managers?',
                'answer' => '<p>Yes, though managers with investigation responsibilities may also need Level 3 safeguarding training for their enhanced role. This Level 2 course provides the foundation all care staff need, including managers.</p>',
            ],
        ];
        update_field('course_faqs', $faqs_array, $post_id);
    }
    
    // Set as mandatory training
    $is_mandatory = get_field('course_is_mandatory', $post_id);
    if (empty($is_mandatory)) {
        update_field('course_is_mandatory', true, $post_id);
        update_field('course_mandatory_note', 'This course is required by CQC and must be completed during induction for all care workers. Refreshers needed every 1-2 years.', $post_id);
    }
    
    // Update H1 (post title) if needed
    $current_title = get_the_title($post_id);
    if ($current_title === 'Safeguarding' || strpos(strtolower($current_title), 'safeguarding') === 0) {
        $new_title = 'Safeguarding Adults and Children Training - Level 2';
        if ($current_title !== $new_title) {
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $new_title,
            ]);
        }
    }
    
    // Update SEO meta description if empty
    $meta_desc = get_field('course_seo_meta_description', $post_id);
    if (empty($meta_desc)) {
        update_field('course_seo_meta_description', 'Level 2 Safeguarding training for care workers in Kent. Learn to recognise abuse, reporting procedures, and protecting vulnerable adults and children.', $post_id);
    }
    
    // Update SEO H1 if empty
    $seo_h1 = get_field('course_seo_h1', $post_id);
    if (empty($seo_h1)) {
        update_field('course_seo_h1', 'Safeguarding Adults and Children Training - Level 2', $post_id);
    }
    
    return true;
}
add_action('after_switch_theme', 'cta_populate_safeguarding_course_content', 35);
