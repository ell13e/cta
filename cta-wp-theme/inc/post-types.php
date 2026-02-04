<?php
/**
 * Custom Post Types
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Preload ACF/meta fields for admin list screens to avoid N+1 queries.
 */
function cta_preload_admin_column_fields($query)
{
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'edit') {
        return;
    }

    add_filter('posts_results', function ($posts) use ($screen) {
        if (empty($posts)) {
            return $posts;
        }

        $post_ids = wp_list_pluck($posts, 'ID');

        if ($screen->post_type === 'course') {
            cta_batch_load_course_fields($post_ids);
        } elseif ($screen->post_type === 'course_event') {
            cta_batch_load_event_fields($post_ids);
        }

        return $posts;
    }, 10, 1);
}
add_action('pre_get_posts', 'cta_preload_admin_column_fields');

/**
 * Batch load common Course meta fields for admin columns.
 *
 * @param int[] $post_ids
 */
function cta_batch_load_course_fields($post_ids)
{
    if (empty($post_ids)) {
        return;
    }

    global $wpdb;
    $ids_string = implode(',', array_map('intval', $post_ids));

    $meta = $wpdb->get_results(
        "SELECT post_id, meta_key, meta_value 
         FROM {$wpdb->postmeta} 
         WHERE post_id IN ($ids_string)
         AND meta_key IN ('course_duration', 'course_price', 'course_level')"
    );

    if (!$meta) {
        return;
    }

    foreach ($meta as $row) {
        // Prime the object cache so get_post_meta / ACF can reuse it.
        wp_cache_set($row->post_id . '_' . $row->meta_key, $row->meta_value, 'cta_admin_meta');
    }
}

/**
 * Batch load common Course Event meta fields for admin columns.
 *
 * @param int[] $post_ids
 */
function cta_batch_load_event_fields($post_ids)
{
    if (empty($post_ids)) {
        return;
    }

    global $wpdb;
    $ids_string = implode(',', array_map('intval', $post_ids));

    $meta = $wpdb->get_results(
        "SELECT post_id, meta_key, meta_value 
         FROM {$wpdb->postmeta} 
         WHERE post_id IN ($ids_string)
         AND meta_key IN ('event_date', 'start_time', 'end_time', 'spaces_available', 'total_spaces', 'linked_course', 'event_image')"
    );

    if (!$meta) {
        return;
    }

    foreach ($meta as $row) {
        wp_cache_set($row->post_id . '_' . $row->meta_key, $row->meta_value, 'cta_admin_meta');
    }
}

/**
 * Register Course post type
 */
function cta_register_course_post_type() {
    $labels = [
        'name' => 'Courses',
        'singular_name' => 'Course',
        'menu_name' => 'Courses',
        'add_new' => 'Add New Course',
        'add_new_item' => 'Add New Course',
        'edit_item' => 'Edit Course',
        'new_item' => 'New Course',
        'view_item' => 'View Course',
        'search_items' => 'Search Courses',
        'not_found' => 'No courses found',
        'not_found_in_trash' => 'No courses found in Trash',
        'all_items' => 'All Courses',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'courses', 'with_front' => false],
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => ['title', 'thumbnail', 'excerpt'],
    ];

    register_post_type('course', $args);
}
add_action('init', 'cta_register_course_post_type');

/**
 * Register Course Category taxonomy
 */
function cta_register_course_category_taxonomy() {
    $labels = [
        'name' => 'Course Categories',
        'singular_name' => 'Course Category',
        'search_items' => 'Search Categories',
        'all_items' => 'All Categories',
        'parent_item' => 'Parent Category',
        'parent_item_colon' => 'Parent Category:',
        'edit_item' => 'Edit Category',
        'update_item' => 'Update Category',
        'add_new_item' => 'Add New Category',
        'new_item_name' => 'New Category Name',
        'menu_name' => 'Categories',
    ];

    $args = [
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'course-category'],
    ];

    register_taxonomy('course_category', ['course'], $args);
}
add_action('init', 'cta_register_course_category_taxonomy');

/**
 * Register Course Event post type (scheduled sessions)
 */
function cta_register_course_event_post_type() {
    $labels = [
        'name' => 'Scheduled Courses',
        'singular_name' => 'Scheduled Course',
        'menu_name' => 'Scheduled Courses',
        'add_new' => 'Add New Session',
        'add_new_item' => 'Add New Session',
        'edit_item' => 'Edit Session',
        'new_item' => 'New Session',
        'view_item' => 'View Session',
        'search_items' => 'Search Sessions',
        'not_found' => 'No sessions found',
        'not_found_in_trash' => 'No sessions found in Trash',
        'all_items' => 'All Sessions',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'upcoming-courses', 'with_front' => false],
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'thumbnail'],
    ];

    register_post_type('course_event', $args);
}
add_action('init', 'cta_register_course_event_post_type');

/**
 * Register Team Member post type
 */
function cta_register_team_member_post_type() {
    $labels = [
        'name' => 'Team Members',
        'singular_name' => 'Team Member',
        'menu_name' => 'Team',
        'add_new' => 'Add Team Member',
        'add_new_item' => 'Add New Team Member',
        'edit_item' => 'Edit Team Member',
        'new_item' => 'New Team Member',
        'view_item' => 'View Team Member',
        'search_items' => 'Search Team',
        'not_found' => 'No team members found',
        'not_found_in_trash' => 'No team members found in Trash',
        'all_items' => 'All Team Members',
    ];

    $args = [
        'labels' => $labels,
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'query_var' => false,
        'rewrite' => false,
        'capability_type' => 'post',
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => 25,
        'menu_icon' => 'dashicons-groups',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'],
    ];

    register_post_type('team_member', $args);
}
add_action('init', 'cta_register_team_member_post_type');

/**
 * Register FAQ post type
 */
function cta_register_faq_post_type() {
    $labels = [
        'name' => 'FAQs',
        'singular_name' => 'FAQ',
        'menu_name' => 'FAQs',
        'add_new' => 'Add New FAQ',
        'add_new_item' => 'Add New FAQ',
        'edit_item' => 'Edit FAQ',
        'new_item' => 'New FAQ',
        'view_item' => 'View FAQ',
        'search_items' => 'Search FAQs',
        'not_found' => 'No FAQs found',
        'not_found_in_trash' => 'No FAQs found in Trash',
        'all_items' => 'All FAQs',
    ];

    $args = [
        'labels' => $labels,
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'query_var' => false,
        'rewrite' => false,
        'capability_type' => 'post',
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => 24,
        'menu_icon' => 'dashicons-editor-help',
        'supports' => ['title', 'editor', 'page-attributes'],
    ];

    register_post_type('faq', $args);
}
add_action('init', 'cta_register_faq_post_type');

/**
 * Register FAQ Category taxonomy
 */
function cta_register_faq_category_taxonomy() {
    $labels = [
        'name' => 'FAQ Categories',
        'singular_name' => 'FAQ Category',
        'search_items' => 'Search Categories',
        'all_items' => 'All Categories',
        'parent_item' => 'Parent Category',
        'parent_item_colon' => 'Parent Category:',
        'edit_item' => 'Edit Category',
        'update_item' => 'Update Category',
        'add_new_item' => 'Add New Category',
        'new_item_name' => 'New Category Name',
        'menu_name' => 'Categories',
    ];

    $args = [
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => false,
    ];

    register_taxonomy('faq_category', ['faq'], $args);
}
add_action('init', 'cta_register_faq_category_taxonomy');

/**
 * Customize admin columns for Courses
 */
function cta_course_admin_columns($columns) {
    $new_columns = [];
    
    // Keep checkbox first
    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
    }
    
    // Title
    if (isset($columns['title'])) {
        $new_columns['title'] = $columns['title'];
    }
    
    // Add custom columns after title
    $new_columns['cta_seo_status'] = 'SEO';
    $new_columns['cta_thumbnail'] = 'Image';
    $new_columns['course_category'] = 'Category';
    $new_columns['course_duration'] = 'Duration';
    $new_columns['course_price'] = 'Price';
    $new_columns['course_discount'] = 'Discount';
    
    // Skip standard columns we've already handled
    $skip = ['cb', 'title', 'date'];
    foreach ($columns as $key => $value) {
        if (!in_array($key, $skip)) {
            $new_columns[$key] = $value;
        }
    }
    
    // Readiness before date
    $new_columns['cta_status'] = 'Status';
    
    // Date last
    if (isset($columns['date'])) {
        $new_columns['date'] = $columns['date'];
    }
    
    return $new_columns;
}
add_filter('manage_course_posts_columns', 'cta_course_admin_columns');

/**
 * Register Form Submission post type
 */
function cta_register_form_submission_post_type() {
    $labels = [
        'name' => 'Form Submissions',
        'singular_name' => 'Form Submission',
        'menu_name' => 'Submissions',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Submission',
        'edit_item' => 'View Submission',
        'new_item' => 'New Submission',
        'view_item' => 'View Submission',
        'search_items' => 'Search Submissions',
        'not_found' => 'No submissions found',
        'not_found_in_trash' => 'No submissions found in Trash',
        'all_items' => 'All Submissions',
    ];

    $args = [
        'labels' => $labels,
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'query_var' => false,
        'rewrite' => false,
        'capability_type' => 'post',
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => 26,
        'menu_icon' => 'dashicons-feedback',
        'supports' => ['title'],
        'capabilities' => [
            'create_posts' => false, // Disable creating manually
            // Allow deleting posts (including test submissions)
            'delete_posts' => 'delete_posts',
            'delete_published_posts' => 'delete_published_posts',
        ],
        'map_meta_cap' => true,
    ];

    register_post_type('form_submission', $args);
}
add_action('init', 'cta_register_form_submission_post_type');

/**
 * Register Form Type taxonomy for submissions
 */
function cta_register_form_type_taxonomy() {
    $labels = [
        'name' => 'Form Types',
        'singular_name' => 'Form Type',
        'search_items' => 'Search Form Types',
        'all_items' => 'All Form Types',
        'edit_item' => 'Edit Form Type',
        'update_item' => 'Update Form Type',
        'add_new_item' => 'Add New Form Type',
        'new_item_name' => 'New Form Type Name',
        'menu_name' => 'Form Types',
    ];

    $args = [
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => false,
        'query_var' => true,
        'rewrite' => false,
    ];

    register_taxonomy('form_type', ['form_submission'], $args);
}
add_action('init', 'cta_register_form_type_taxonomy');

/**
 * Hide Form Types taxonomy menu - types are managed in code, not by users
 * Form types are hardcoded and tied to functionality, so users shouldn't edit them
 */
function cta_hide_form_types_menu() {
    remove_submenu_page('edit.php?post_type=form_submission', 'edit-tags.php?taxonomy=form_type&amp;post_type=form_submission');
}
add_action('admin_menu', 'cta_hide_form_types_menu', 99);

/**
 * Pre-register form types for submissions
 * Ensures all form types exist in the admin area before submissions are made
 */
function cta_preintegrate_form_types() {
    // Define all form types used across the site
    // These represent submissions from various forms across the website
    $form_types = [
        // Standalone form types
        [
            'slug' => 'callback-request',
            'name' => 'Callback Request',
            'description' => 'Submissions from callback request forms (homepage CTA section, course pages, etc.)',
        ],
        [
            'slug' => 'course-booking',
            'name' => 'Course Booking',
            'description' => 'Submissions from course booking modals and enquiry forms (course detail pages, homepage, etc.)',
        ],
        [
            'slug' => 'meta-lead',
            'name' => 'Meta Lead',
            'description' => 'Leads imported from Facebook Lead Ads (Meta)',
        ],
        [
            'slug' => 'group-booking',
            'name' => 'Group Booking',
            'description' => 'Submissions from group training booking forms (group training page, contact page, etc.)',
        ],
        [
            'slug' => 'newsletter',
            'name' => 'Newsletter Signup',
            'description' => 'Submissions from newsletter signup forms (footer, modals, homepage, etc.)',
        ],
        // Contact form enquiry types (from contact page and other contact forms)
        [
            'slug' => 'general',
            'name' => 'General Enquiry',
            'description' => 'General enquiries submitted via contact forms across the website',
        ],
        [
            'slug' => 'training-consultation',
            'name' => 'Book a Free Training Consultation',
            'description' => 'Requests for free training consultation submitted via contact forms',
        ],
        [
            'slug' => 'group-training',
            'name' => 'Group Training Enquiry',
            'description' => 'Group training enquiries submitted via contact forms',
        ],
        [
            'slug' => 'book-course',
            'name' => 'Book a Course',
            'description' => 'Course booking enquiries submitted via contact forms',
        ],
        [
            'slug' => 'cqc-training',
            'name' => 'CQC Training Enquiry',
            'description' => 'CQC training specific enquiries submitted via contact forms',
        ],
        [
            'slug' => 'support',
            'name' => 'Support/FAQ',
            'description' => 'Support requests and FAQ enquiries submitted via contact forms',
        ],
    ];
    
    // Register each form type if it doesn't already exist
    foreach ($form_types as $form_type) {
        $term = get_term_by('slug', $form_type['slug'], 'form_type');
        
        if (!$term) {
            $term_result = wp_insert_term(
                $form_type['name'],
                'form_type',
                [
                    'slug' => $form_type['slug'],
                    'description' => $form_type['description'],
                ]
            );
            
            if (is_wp_error($term_result)) {
                error_log('CTA Form Types: Failed to create form type ' . $form_type['slug'] . ' - ' . $term_result->get_error_message());
            }
        } else {
            // Update description if term exists but description is empty
            if (empty($term->description) && !empty($form_type['description'])) {
                wp_update_term($term->term_id, 'form_type', [
                    'description' => $form_type['description'],
                ]);
            }
        }
    }
}
add_action('init', 'cta_preintegrate_form_types', 20); // Run after taxonomy registration

/**
 * Ensure form types are created on theme activation
 */
function cta_preintegrate_form_types_on_activation() {
    // Make sure taxonomy is registered first
    if (!taxonomy_exists('form_type')) {
        cta_register_form_type_taxonomy();
    }
    
    // Create form types
    cta_preintegrate_form_types();
}
add_action('after_switch_theme', 'cta_preintegrate_form_types_on_activation');

/**
 * Populate custom admin columns for Courses
 */
function cta_course_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'cta_seo_status':
            $noindex = get_post_meta($post_id, '_cta_noindex', true);
            if ($noindex === '1') {
                echo '<span class="cta-admin-seo-badge cta-admin-seo-hidden" title="Hidden from search engines">Hidden</span>';
            } else {
                echo '<span class="cta-admin-seo-badge cta-admin-seo-visible" title="Visible in search engines">Visible</span>';
            }
            break;
            
        case 'cta_thumbnail':
            if (has_post_thumbnail($post_id)) {
                $thumb_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
                $image_id = get_post_thumbnail_id($post_id);
                echo '<img src="' . esc_url($thumb_url) . '" class="cta-admin-thumbnail cta-change-image" data-post-id="' . esc_attr($post_id) . '" data-post-type="course" data-image-id="' . esc_attr($image_id) . '" title="Click to change image" alt="Course thumbnail" style="cursor: pointer;" />';
            } else {
                echo '<span class="cta-admin-missing-badge cta-change-image" data-post-id="' . esc_attr($post_id) . '" data-post-type="course" title="Click to change image" style="cursor: pointer;">Missing</span>';
            }
            break;
            
        case 'course_category':
            $terms = get_the_terms($post_id, 'course_category');
            if ($terms && !is_wp_error($terms)) {
                // Limit to 2 categories (primary and secondary)
                $limited_terms = array_slice($terms, 0, 2);
                $category_names = wp_list_pluck($limited_terms, 'name');
                
                // Show primary and secondary labels
                if (count($limited_terms) === 2) {
                    echo '<span class="cta-admin-category"><strong>Primary:</strong> ' . esc_html($category_names[0]) . '<br><strong>Secondary:</strong> ' . esc_html($category_names[1]) . '</span>';
                } elseif (count($limited_terms) === 1) {
                    echo '<span class="cta-admin-category"><strong>Primary:</strong> ' . esc_html($category_names[0]) . '</span>';
                } else {
                    echo '<span class="cta-admin-empty">-</span>';
                }
            } else {
                echo '<span class="cta-admin-empty">-</span>';
            }
            break;
            
        case 'course_duration':
            $duration = get_field('course_duration', $post_id);
            $display = $duration ? esc_html($duration) : '-';
            echo '<span class="cta-inline-edit cta-inline-duration cta-admin-duration" data-post-id="' . esc_attr($post_id) . '" data-value="' . esc_attr($duration ?: '') . '" title="Double-click to edit">' . $display . '</span>';
            break;
            
        case 'course_price':
            $price = get_field('course_price', $post_id);
            $display = $price ? '£' . esc_html(number_format($price, 2)) : '-';
            echo '<span class="cta-inline-edit cta-inline-price cta-admin-price" data-post-id="' . esc_attr($post_id) . '" data-value="' . esc_attr($display) . '" title="Double-click to edit">' . $display . '</span>';
            break;
            
        case 'course_discount':
            if (function_exists('cta_is_course_discount_active') && function_exists('cta_get_course_discount') && cta_is_course_discount_active($post_id)) {
                $discount = cta_get_course_discount($post_id);
                echo '<span style="color: #00a32a; font-weight: 600;">' . esc_html($discount['percentage']) . '% off</span>';
                if (!empty($discount['label'])) {
                    echo '<br><small style="color: #646970;">' . esc_html($discount['label']) . '</small>';
                }
                if ($discount['requires_code'] && !empty($discount['discount_code'])) {
                    echo '<br><code style="background: #f0f0f1; padding: 1px 4px; border-radius: 2px; font-size: 11px; color: #856404;">' . esc_html($discount['discount_code']) . '</code>';
                }
                $edit_url = admin_url('edit.php?post_type=course&page=cta-discount-codes&course_id=' . $post_id);
                echo '<br><a href="' . esc_url($edit_url) . '" style="font-size: 11px; margin-top: 4px; display: inline-block;">Edit</a>';
            } else {
                echo '<span style="color: #8c8f94;">-</span>';
            }
            break;
            
        case 'cta_status':
            $post = get_post($post_id);
            $has_image = has_post_thumbnail($post_id);
            $has_excerpt = !empty($post->post_excerpt);
            $has_category = !empty(get_the_terms($post_id, 'course_category'));
            
            $checks = [$has_image, $has_excerpt, $has_category];
            $completed = count(array_filter($checks));
            $total = count($checks);
            
            // Build missing items list
            $missing = [];
            if (!$has_image) $missing[] = 'Image';
            if (!$has_excerpt) $missing[] = 'Excerpt';
            if (!$has_category) $missing[] = 'Category';
            
            $missing_text = !empty($missing) ? ' - Missing: ' . implode(', ', $missing) : '';
            $title_text = $completed === $total ? 'Ready to publish! All essentials complete.' : $completed . '/' . $total . ' complete' . $missing_text;
            
            if ($completed === $total) {
                echo '<span class="status-badge complete" title="' . esc_attr($title_text) . '">Complete</span>';
            } else {
                echo '<span class="status-badge incomplete" title="' . esc_attr($title_text) . '">' . $completed . '/' . $total . '</span>';
            }
            break;
    }
}
add_action('manage_course_posts_custom_column', 'cta_course_admin_column_content', 10, 2);

/**
 * Customize admin columns for Course Events
 */
function cta_course_event_admin_columns($columns) {
    return [
        'cb' => $columns['cb'],
        'title' => 'Session',
        'event_thumbnail' => 'Image',
        'event_course' => 'Course',
        'event_date' => 'Date',
        'event_time' => 'Time',
        'event_spaces' => 'Spaces',
        'event_status' => 'Status',
        'date' => 'Created',
    ];
}
add_filter('manage_course_event_posts_columns', 'cta_course_event_admin_columns');

/**
 * Populate custom admin columns for Course Events
 */
function cta_course_event_admin_column_content($column, $post_id) {
    // Status column is handled in admin.php, skip it here
    if ($column === 'event_status') {
        return;
    }
    
    switch ($column) {
        case 'event_thumbnail':
            $thumb_url = '';
            $image_id = '';
            
            // PRIORITY 1: Check WordPress featured image first (what user sets in meta box)
            if (has_post_thumbnail($post_id)) {
                $image_id = get_post_thumbnail_id($post_id);
                $thumb_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
            }
            
            // PRIORITY 2: Fallback to ACF event_image field if no featured image
            if (empty($thumb_url)) {
                $event_image = get_field('event_image', $post_id);
            if ($event_image) {
                // Handle both array and ID formats from ACF
                if (is_array($event_image)) {
                    if (isset($event_image['sizes']['thumbnail'])) {
                        $thumb_url = $event_image['sizes']['thumbnail'];
                        $image_id = isset($event_image['ID']) ? $event_image['ID'] : (isset($event_image['id']) ? $event_image['id'] : '');
                    } elseif (isset($event_image['url'])) {
                        $thumb_url = $event_image['url'];
                        $image_id = isset($event_image['ID']) ? $event_image['ID'] : (isset($event_image['id']) ? $event_image['id'] : '');
                    }
                } elseif (is_numeric($event_image)) {
                    $image_id = intval($event_image);
                    $thumb_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                }
                }
            }
            
            // PRIORITY 3: Fallback to course featured image if event image not set
            if (empty($thumb_url)) {
                $linked_course = get_field('linked_course', $post_id);
                if ($linked_course && has_post_thumbnail($linked_course->ID)) {
                $thumb_url = get_the_post_thumbnail_url($linked_course->ID, 'thumbnail');
                $image_id = get_post_thumbnail_id($linked_course->ID);
                }
            }
            
            // Display thumbnail or missing badge
            if (!empty($thumb_url) && !empty($image_id)) {
                echo '<img src="' . esc_url($thumb_url) . '" class="cta-admin-thumbnail cta-change-image" data-post-id="' . esc_attr($post_id) . '" data-post-type="course_event" data-image-id="' . esc_attr($image_id) . '" title="Click to change image" alt="Event thumbnail" style="cursor: pointer;" />';
            } else {
                echo '<span class="cta-admin-missing-badge cta-change-image" data-post-id="' . esc_attr($post_id) . '" data-post-type="course_event" title="Click to change image" style="cursor: pointer;">Missing</span>';
            }
            break;
            
        case 'event_course':
            $course = get_field('linked_course', $post_id);
            if ($course) {
                echo '<a href="' . get_edit_post_link($course->ID) . '" class="cta-admin-course-link">' . esc_html($course->post_title) . '</a>';
            } else {
                echo '<span class="cta-admin-empty">-</span>';
            }
            break;
            
        case 'event_date':
            $date = get_field('event_date', $post_id);
            if ($date) {
                $formatted_date = date('j M Y', strtotime($date));
                $is_past = strtotime($date) < strtotime('today');
                $class = $is_past ? 'cta-admin-date cta-admin-date-past' : 'cta-admin-date';
                echo '<span class="' . esc_attr($class) . '">' . esc_html($formatted_date) . '</span>';
            } else {
                echo '<span class="cta-admin-empty">-</span>';
            }
            break;
            
        case 'event_time':
            $start = get_field('start_time', $post_id);
            $end = get_field('end_time', $post_id);
            $time_value = '';
            if ($start && $end) {
                $time_value = $start . ' – ' . $end;
                $display = esc_html($time_value);
            } elseif ($start) {
                $time_value = $start;
                $display = esc_html($start);
            } else {
                $display = '-';
            }
            echo '<span class="cta-inline-edit cta-inline-time cta-admin-time" data-post-id="' . esc_attr($post_id) . '" data-value="' . esc_attr($time_value) . '" title="Double-click to edit">' . $display . '</span>';
            break;
            
        case 'event_spaces':
            $spaces_available = get_field('spaces_available', $post_id);
            $total_spaces = get_field('total_spaces', $post_id);
            
            // If total_spaces is not set, set it to match spaces_available (assumes no bookings yet)
            // This handles backward compatibility for existing events
            if (empty($total_spaces) || $total_spaces <= 0) {
                if ($spaces_available !== '' && intval($spaces_available) > 0) {
                    $total_spaces = intval($spaces_available);
                    // Auto-set total_spaces for backward compatibility
                    update_field('total_spaces', $total_spaces, $post_id);
                } else {
                    $total_spaces = 12; // Default fallback
                }
            } else {
                $total_spaces = intval($total_spaces);
            }
            
            if ($spaces_available !== '') {
                $spaces_available_int = intval($spaces_available);
                $spaces_booked = max(0, $total_spaces - $spaces_available_int);
                $percentage = $total_spaces > 0 ? round(($spaces_booked / $total_spaces) * 100) : 0;
                
                $class = 'cta-admin-spaces';
                if ($spaces_available_int <= 3) {
                    $class .= ' cta-admin-spaces-low';
                } elseif ($spaces_available_int <= 6) {
                    $class .= ' cta-admin-spaces-medium';
                } else {
                    $class .= ' cta-admin-spaces-high';
                }
                
                // Show: "Available / Total (Booked %)"
                $display = esc_html($spaces_available) . ' / ' . esc_html($total_spaces);
                if ($spaces_booked > 0) {
                    $display .= ' (' . esc_html($percentage) . '% booked)';
                } else {
                    $display .= ' (0% booked)';
                }
                
                echo '<span class="cta-inline-edit cta-inline-spaces ' . esc_attr($class) . '" data-post-id="' . esc_attr($post_id) . '" data-value="' . esc_attr($spaces_available) . '" title="Double-click to edit available spaces">' . $display . '</span>';
            } else {
                echo '<span class="cta-admin-empty">-</span>';
            }
            break;
    }
}
add_action('manage_course_event_posts_custom_column', 'cta_course_event_admin_column_content', 10, 2);

/**
 * Make Course Event columns sortable
 */
function cta_course_event_sortable_columns($columns) {
    $columns['event_date'] = 'event_date';
    return $columns;
}
add_filter('manage_edit-course_event_sortable_columns', 'cta_course_event_sortable_columns');

/**
 * Handle sorting for Course Event date column
 * Defaults to sorting by event_date ascending (earliest dates first)
 */
function cta_course_event_orderby($query) {
    if (!is_admin()) {
        return;
    }
    
    global $pagenow, $typenow;
    
    // Only on edit.php for course_event
    if ($pagenow !== 'edit.php' || $typenow !== 'course_event') {
        return;
    }
    
    // Only modify main query
    if (!$query->is_main_query()) {
        return;
    }
    
        $orderby = $query->get('orderby');
        
        // If explicitly sorting by event_date, use meta_value
        if ($orderby === 'event_date') {
            $query->set('meta_key', 'event_date');
            $query->set('orderby', 'meta_value');
            $query->set('meta_type', 'DATE');
        } 
        // If no orderby is set (default view), sort by event_date ascending
        // Don't override if user clicked on 'date' (Created) column
    elseif (empty($orderby) || $orderby === 'menu_order title') {
            $query->set('meta_key', 'event_date');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'ASC');
            $query->set('meta_type', 'DATE');
        }
    }
add_action('pre_get_posts', 'cta_course_event_orderby', 20);

/**
 * Flush rewrite rules on theme activation
 */
function cta_flush_rewrite_rules() {
    cta_register_course_post_type();
    cta_register_course_category_taxonomy();
    cta_register_course_event_post_type();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'cta_flush_rewrite_rules');

/**
 * Auto-generate short slug from title + duration + level for Courses
 * Examples:
 *   "Basic Life Support" (1 Day, Level 2) → "basic-life-support-1d-l2"
 *   "Basic Life Support" (3 Day, Level 2) → "basic-life-support-3d-l2"
 *   "Fire Safety" (3 Hours, Level 1) → "fire-safety-3h-l1"
 */
function cta_generate_course_slug($post_id) {
    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'course') {
        return;
    }
    
    // Get fields
    $level = get_field('course_level', $post_id);
    $duration = get_field('course_duration', $post_id);
    
    // Generate shorter base slug from title (extract key words, limit length)
    $title = $post->post_title;
    $stop_words = ['a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the', 'to', 'was', 'will', 'with', 'training', 'course'];
    
    // Extract key words from title
    $words = preg_split('/[\s\-]+/', strtolower($title));
    $key_words = array_filter($words, function($word) use ($stop_words) {
        $clean_word = preg_replace('/[^a-z0-9]/', '', $word);
        return strlen($clean_word) >= 3 && !in_array($clean_word, $stop_words);
    });
    
    // Take first 2-3 key words for shorter slug
    $key_words = array_slice($key_words, 0, 3);
    $base_slug = !empty($key_words) ? implode('-', $key_words) : sanitize_title($title);
    
    // Limit base slug length to leave room for suffixes (max 40 chars)
    if (strlen($base_slug) > 40) {
        $base_slug = substr($base_slug, 0, 40);
        $base_slug = rtrim($base_slug, '-');
    }
    
    $suffix_parts = [];
    
    // Add level shorthand FIRST (most important) - e.g., "Level 2" → "l2"
    // Handle various formats: "Level 2", "L2", "2", "Level2", etc.
    if ($level) {
        $level_clean = trim($level);
        if (preg_match('/(?:level\s*)?(\d+)/i', $level_clean, $matches)) {
            $suffix_parts[] = 'l' . $matches[1];
        }
    }
    
    // Add duration shorthand (e.g., "1 Day" → "1d", "3 Hours" → "3h", "Half Day" → "hd")
    if ($duration) {
        $duration_lower = strtolower($duration);
        if (preg_match('/(\d+)\s*day/i', $duration_lower, $matches)) {
            $suffix_parts[] = $matches[1] . 'd';
        } elseif (preg_match('/(\d+)\s*hour/i', $duration_lower, $matches)) {
            $suffix_parts[] = $matches[1] . 'h';
        } elseif (strpos($duration_lower, 'half') !== false) {
            $suffix_parts[] = 'hd';
        }
    }
    
    // Build final slug
    $new_slug = $base_slug;
    if (!empty($suffix_parts)) {
        $new_slug .= '-' . implode('-', $suffix_parts);
    }
    
    // Enforce WordPress slug length limit (200 chars max, but keep it shorter for best practice)
    // If too long, truncate base slug but keep suffixes
    $max_length = 80; // Reasonable limit for URLs
    if (strlen($new_slug) > $max_length) {
        $suffix_length = strlen('-' . implode('-', $suffix_parts));
        $base_max = $max_length - $suffix_length;
        if ($base_max > 10) {
            $base_slug = substr($base_slug, 0, $base_max);
            $base_slug = rtrim($base_slug, '-');
            $new_slug = $base_slug . '-' . implode('-', $suffix_parts);
        } else {
            // If even with minimal base it's too long, just use base + level
            $new_slug = $base_slug . '-' . ($suffix_parts[0] ?? '');
        }
    }
    
    // Only update if slug is different
    if ($post->post_name !== $new_slug) {
        remove_action('acf/save_post', 'cta_auto_generate_course_slug_on_acf_save', 20);
        $result = wp_update_post([
            'ID' => $post_id,
            'post_name' => $new_slug,
        ], true);
        add_action('acf/save_post', 'cta_auto_generate_course_slug_on_acf_save', 20);
        
        // If update failed, log error but don't prevent save
        if (is_wp_error($result)) {
            error_log('Failed to update course slug: ' . $result->get_error_message());
        }
    }
}

/**
 * Trigger slug generation after ACF fields are saved
 */
function cta_auto_generate_course_slug_on_acf_save($post_id) {
    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (get_post_type($post_id) === 'course') {
        cta_generate_course_slug($post_id);
    }
}
add_action('acf/save_post', 'cta_auto_generate_course_slug_on_acf_save', 20);

/**
 * Force regenerate slugs for all courses
 * Run this once via WP-CLI or admin action to fix existing slugs
 * Usage: wp eval 'cta_regenerate_all_course_slugs();'
 */
function cta_regenerate_all_course_slugs() {
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft', 'private'],
    ]);
    
    $updated = 0;
    foreach ($courses as $course) {
        $old_slug = $course->post_name;
        cta_generate_course_slug($course->ID);
        $new_slug = get_post($course->ID)->post_name;
        
        if ($old_slug !== $new_slug) {
            $updated++;
            error_log("Updated course slug: {$course->post_title} - {$old_slug} → {$new_slug}");
        }
    }
    
    return $updated;
}

/**
 * Auto-generate short slug for Course Events
 * Format: course-slug-jan15 (e.g., "basic-life-support-1d-l2-jan15")
 */
function cta_generate_event_slug($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'course_event') {
        return;
    }
    
    $linked_course = get_field('linked_course', $post_id);
    $event_date = get_field('event_date', $post_id);
    
    if ($linked_course && $event_date) {
        // Use course slug + short date (jan15, feb20, etc.)
        $course_slug = $linked_course->post_name;
        $date_short = strtolower(date('Mj', strtotime($event_date))); // e.g., "jan15"
        $new_slug = $course_slug . '-' . $date_short;
        
        // Check for duplicates (same course, same date)
        $existing = get_posts([
            'post_type' => 'course_event',
            'name' => $new_slug,
            'post__not_in' => [$post_id],
            'posts_per_page' => 1,
        ]);
        
        if (!empty($existing)) {
            // Add year if duplicate
            $new_slug = $course_slug . '-' . strtolower(date('Mj-Y', strtotime($event_date)));
        }
        
        // Only update if different
        if ($post->post_name !== $new_slug) {
            remove_action('acf/save_post', 'cta_auto_generate_event_slug_on_acf_save', 20);
            wp_update_post([
                'ID' => $post_id,
                'post_name' => $new_slug,
            ]);
            add_action('acf/save_post', 'cta_auto_generate_event_slug_on_acf_save', 20);
        }
    }
}

/**
 * Trigger event slug generation after ACF fields are saved
 */
function cta_auto_generate_event_slug_on_acf_save($post_id) {
    if (get_post_type($post_id) === 'course_event') {
        cta_generate_event_slug($post_id);
    }
}
add_action('acf/save_post', 'cta_auto_generate_event_slug_on_acf_save', 20);

/**
 * Auto-generate short, logical slug for News articles (blog posts)
 * Creates concise slugs by extracting key words and limiting length
 * Format: key-words-monYY (e.g., "maidstone-award-dec21" instead of full title)
 */
function cta_generate_post_slug($post_id, $post, $update) {
    if ($post->post_type !== 'post') {
        return;
    }
    
    // Only auto-generate for new posts or if slug is auto-draft
    if ($update && strpos($post->post_name, 'auto-draft') === false && !empty($post->post_name)) {
        return;
    }
    
    // Extract key words from title (remove common words, keep meaningful terms)
    $title = $post->post_title;
    $stop_words = ['a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the', 'to', 'was', 'will', 'with', 'the', 'this', 'what', 'when', 'where', 'who', 'why', 'how', 'wins', 'wins', 'provider', 'training'];
    
    // Convert to lowercase and split into words
    $words = preg_split('/[\s\-]+/', strtolower($title));
    
    // Filter out stop words and keep only meaningful words (3+ characters)
    $key_words = array_filter($words, function($word) use ($stop_words) {
        $clean_word = preg_replace('/[^a-z0-9]/', '', $word);
        return strlen($clean_word) >= 3 && !in_array($clean_word, $stop_words);
    });
    
    // Take first 3-4 key words for slug
    $key_words = array_slice($key_words, 0, 4);
    
    // Build base slug from key words
    $base_slug = implode('-', $key_words);
    
    // Limit total length to 50 characters (before date suffix)
    if (strlen($base_slug) > 50) {
        $base_slug = substr($base_slug, 0, 50);
        // Remove trailing hyphen if present
        $base_slug = rtrim($base_slug, '-');
    }
    
    // If no key words extracted, fall back to first few words of sanitized title
    if (empty($base_slug)) {
        $fallback = sanitize_title($title);
        $fallback_words = explode('-', $fallback);
        $base_slug = implode('-', array_slice($fallback_words, 0, 3));
    }
    
    // Add date suffix (monYY format)
    $date_short = strtolower(date('My', strtotime($post->post_date))); // e.g., "dec21"
    $new_slug = $base_slug . '-' . $date_short;
    
    // Limit total slug length to 60 characters (WordPress default)
    if (strlen($new_slug) > 60) {
        $base_length = 60 - strlen($date_short) - 1; // -1 for hyphen
        $base_slug = substr($base_slug, 0, $base_length);
        $base_slug = rtrim($base_slug, '-');
        $new_slug = $base_slug . '-' . $date_short;
    }
    
    // Only update if different
    if ($post->post_name !== $new_slug) {
        remove_action('save_post', 'cta_generate_post_slug', 20);
        wp_update_post([
            'ID' => $post_id,
            'post_name' => $new_slug,
        ]);
        add_action('save_post', 'cta_generate_post_slug', 20, 3);
    }
}
add_action('save_post', 'cta_generate_post_slug', 20, 3);

/**
 * =========================================
 * AUTO-GENERATE SEO-FRIENDLY EXCERPTS
 * Target: 150-160 characters (Google meta description limit)
 * =========================================
 */

/**
 * Auto-generate excerpt for Courses
 * Format: "[Title] - [Duration] [Level] training in Maidstone, Kent. [First sentence of description]"
 */
function cta_auto_generate_course_excerpt($post_id) {
    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'course') {
        return;
    }
    
    // Skip if excerpt already set manually
    if (!empty($post->post_excerpt)) {
        return;
    }
    
    $title = $post->post_title;
    $duration = get_field('course_duration', $post_id);
    $level = get_field('course_level', $post_id);
    $description = get_field('course_description', $post_id);
    
    // Build excerpt parts
    $parts = [$title];
    
    // Add duration and level
    $meta_parts = [];
    if ($duration) {
        $meta_parts[] = $duration;
    }
    if ($level) {
        $meta_parts[] = $level;
    }
    if (!empty($meta_parts)) {
        $parts[] = implode(' ', $meta_parts) . ' training in Maidstone, Kent.';
    } else {
        $parts[] = 'Professional training in Maidstone, Kent.';
    }
    
    $excerpt = implode(' - ', $parts);
    
    // If we have room, add first sentence from description
    if ($description && strlen($excerpt) < 120) {
        $clean_desc = wp_strip_all_tags($description);
        // Get first sentence
        if (preg_match('/^([^.!?]+[.!?])/', $clean_desc, $matches)) {
            $first_sentence = trim($matches[1]);
            if (strlen($excerpt . ' ' . $first_sentence) <= 160) {
                $excerpt .= ' ' . $first_sentence;
            }
        }
    }
    
    // Trim to 160 chars max, ending at word boundary
    if (strlen($excerpt) > 160) {
        $excerpt = substr($excerpt, 0, 157);
        $excerpt = substr($excerpt, 0, strrpos($excerpt, ' ')) . '...';
    }
    
    // Update excerpt
    remove_action('acf/save_post', 'cta_auto_generate_course_excerpt_on_save', 25);
    $result = wp_update_post([
        'ID' => $post_id,
        'post_excerpt' => $excerpt,
    ], true);
    add_action('acf/save_post', 'cta_auto_generate_course_excerpt_on_save', 25);
    
    // If update failed, log error but don't prevent save
    if (is_wp_error($result)) {
        error_log('Failed to update course excerpt: ' . $result->get_error_message());
    }
}

function cta_auto_generate_course_excerpt_on_save($post_id) {
    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (get_post_type($post_id) === 'course') {
        cta_auto_generate_course_excerpt($post_id);
    }
}
add_action('acf/save_post', 'cta_auto_generate_course_excerpt_on_save', 25);

/**
 * Auto-generate excerpt for News posts
 * Format: First 150-160 chars of content, ending at sentence or word boundary
 */
function cta_auto_generate_post_excerpt($post_id, $post, $update) {
    if ($post->post_type !== 'post') {
        return;
    }
    
    // Skip if excerpt already set
    if (!empty($post->post_excerpt)) {
        return;
    }
    
    // Skip revisions and autosaves
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    $content = wp_strip_all_tags($post->post_content);
    
    if (empty($content)) {
        return;
    }
    
    // Try to get first 1-2 sentences within 160 chars
    $excerpt = '';
    $sentences = preg_split('/(?<=[.!?])\s+/', $content, 3);
    
    foreach ($sentences as $sentence) {
        if (strlen($excerpt . ' ' . $sentence) <= 160) {
            $excerpt .= ($excerpt ? ' ' : '') . $sentence;
        } else {
            break;
        }
    }
    
    // If no complete sentence fits, truncate at word boundary
    if (empty($excerpt) || strlen($excerpt) < 50) {
        $excerpt = substr($content, 0, 157);
        $excerpt = substr($excerpt, 0, strrpos($excerpt, ' ')) . '...';
    }
    
    // Trim to 160 max
    if (strlen($excerpt) > 160) {
        $excerpt = substr($excerpt, 0, 157);
        $excerpt = substr($excerpt, 0, strrpos($excerpt, ' ')) . '...';
    }
    
    remove_action('save_post', 'cta_auto_generate_post_excerpt', 25);
    wp_update_post([
        'ID' => $post_id,
        'post_excerpt' => $excerpt,
    ]);
    add_action('save_post', 'cta_auto_generate_post_excerpt', 25, 3);
}
add_action('save_post', 'cta_auto_generate_post_excerpt', 25, 3);

/**
 * Show excerpt character count in admin
 */
function cta_excerpt_character_count() {
    global $post_type;
    
    if (!in_array($post_type, ['course', 'post'])) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        var $excerpt = $('#excerpt');
        if ($excerpt.length) {
            var $counter = $('<p class="description excerpt-counter"></p>');
            $excerpt.after($counter);
            
            function updateCounter() {
                var len = $excerpt.val().length;
                var color = len > 160 ? '#d63638' : (len > 140 ? '#dba617' : '#00a32a');
                $counter.html('Character count: <strong style="color:' + color + '">' + len + '/160</strong> (ideal: 150-160 for SEO)');
            }
            
            $excerpt.on('input', updateCounter);
            updateCounter();
        }
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'cta_excerpt_character_count');
add_action('admin_footer-post-new.php', 'cta_excerpt_character_count');

/**
 * Add Course ID field for reference/migration
 */
function cta_add_course_id_field() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    acf_add_local_field_group([
        'key' => 'group_course_reference',
        'title' => 'Reference',
        'fields' => [
            [
                'key' => 'field_course_legacy_id',
                'label' => 'Legacy ID',
                'name' => 'course_legacy_id',
                'type' => 'number',
                'instructions' => 'Original course ID from static site (for migration reference)',
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_course_code',
                'label' => 'Course Code',
                'name' => 'course_code',
                'type' => 'text',
                'instructions' => 'Internal course code (e.g., BLS-001)',
                'wrapper' => ['width' => '50%'],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course',
                ],
            ],
        ],
        'menu_order' => 99,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
    ]);
}
add_action('acf/init', 'cta_add_course_id_field');

/**
 * Add Slug column to Course admin list
 */
function cta_add_slug_column($columns) {
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['course_slug'] = 'Slug';
        }
    }
    return $new_columns;
}
add_filter('manage_course_posts_columns', 'cta_add_slug_column', 20);

/**
 * Populate Slug column
 */
function cta_slug_column_content($column, $post_id) {
    if ($column === 'course_slug') {
        $post = get_post($post_id);
        echo '<code>' . esc_html($post->post_name) . '</code>';
    }
}
add_action('manage_course_posts_custom_column', 'cta_slug_column_content', 10, 2);

/**
 * Quick edit support for slug
 */
function cta_quick_edit_slug_field($column_name, $post_type) {
    if ($post_type !== 'course' || $column_name !== 'course_slug') {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label>
                <span class="title">Slug</span>
                <span class="input-text-wrap">
                    <input type="text" name="post_name" class="ptitle" value="">
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action('quick_edit_custom_box', 'cta_quick_edit_slug_field', 10, 2);

/**
 * Add filter dropdowns for course events
 */
function cta_course_event_add_filters() {
    global $typenow;
    
    if ($typenow !== 'course_event') {
        return;
    }
    
    // Filter by Course
    $selected_course = isset($_GET['event_course']) ? intval($_GET['event_course']) : 0;
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    
    if (!empty($courses)) {
        echo '<select name="event_course" id="event_course">';
        echo '<option value="0">' . esc_html__('All Courses', 'cta-theme') . '</option>';
        
        foreach ($courses as $course) {
            printf(
                '<option value="%d"%s>%s</option>',
                $course->ID,
                selected($selected_course, $course->ID, false),
                esc_html($course->post_title)
            );
        }
        
        echo '</select>';
    }
    
    // Filter by Status (Active/Past)
    $selected_status = isset($_GET['event_status']) ? sanitize_text_field($_GET['event_status']) : '';
    echo '<select name="event_status" id="event_status">';
    echo '<option value="">' . esc_html__('All Events', 'cta-theme') . '</option>';
    echo '<option value="upcoming"' . selected($selected_status, 'upcoming', false) . '>' . esc_html__('Upcoming', 'cta-theme') . '</option>';
    echo '<option value="past"' . selected($selected_status, 'past', false) . '>' . esc_html__('Past', 'cta-theme') . '</option>';
    echo '</select>';
}
add_action('restrict_manage_posts', 'cta_course_event_add_filters');

/**
 * Filter course events by course and status
 */
function cta_course_event_filter_query($query) {
    global $pagenow, $typenow;
    
    if ($pagenow !== 'edit.php' || $typenow !== 'course_event') {
        return;
    }
    
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $meta_query = [];
    
    // Filter by course
    if (isset($_GET['event_course']) && intval($_GET['event_course']) > 0) {
        $meta_query[] = [
            'key' => 'linked_course',
            'value' => intval($_GET['event_course']),
            'compare' => '=',
        ];
    }
    
    // Filter by status
    if (isset($_GET['event_status']) && $_GET['event_status'] !== '') {
        $today = date('Y-m-d');
        if ($_GET['event_status'] === 'upcoming') {
            $meta_query[] = [
                'key' => 'event_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE',
            ];
        } elseif ($_GET['event_status'] === 'past') {
            $meta_query[] = [
                'key' => 'event_date',
                'value' => $today,
                'compare' => '<',
                'type' => 'DATE',
            ];
        }
    }
    
    if (!empty($meta_query)) {
        if (count($meta_query) > 1) {
            $meta_query['relation'] = 'AND';
        }
        $query->set('meta_query', $meta_query);
    }
}
add_action('parse_query', 'cta_course_event_filter_query');

/**
 * Customize admin columns for FAQs
 */
function cta_faq_admin_columns($columns) {
    return [
        'cb' => $columns['cb'],
        'title' => 'Question',
        'faq_category' => 'Category',
        'faq_answer_preview' => 'Answer Preview',
        'date' => 'Date',
    ];
}
add_filter('manage_faq_posts_columns', 'cta_faq_admin_columns');

/**
 * Populate custom admin columns for FAQs
 */
function cta_faq_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'faq_category':
            $terms = get_the_terms($post_id, 'faq_category');
            if ($terms && !is_wp_error($terms)) {
                $category_names = wp_list_pluck($terms, 'name');
                echo esc_html(implode(', ', $category_names));
            } else {
                echo '<span class="cta-admin-empty">-</span>';
            }
            break;
            
        case 'faq_answer_preview':
            $answer = get_field('faq_answer', $post_id);
            if ($answer) {
                $preview = wp_strip_all_tags($answer);
                $preview = wp_trim_words($preview, 20);
                echo '<span title="' . esc_attr(wp_strip_all_tags($answer)) . '">' . esc_html($preview) . '</span>';
            } else {
                // Fallback to post content if ACF field not set
                $content = get_post_field('post_content', $post_id);
                if ($content) {
                    $preview = wp_strip_all_tags($content);
                    $preview = wp_trim_words($preview, 20);
                    echo '<span title="' . esc_attr(wp_strip_all_tags($content)) . '">' . esc_html($preview) . '</span>';
                } else {
                    echo '<span class="cta-admin-empty">-</span>';
                }
            }
            break;
    }
}
add_action('manage_faq_posts_custom_column', 'cta_faq_admin_column_content', 10, 2);

/**
 * Get default FAQs data
 * Returns the hardcoded FAQs from the template
 */
function cta_get_default_faqs() {
    return [
        // General Training Questions (8 FAQs)
        ['category' => 'general', 'question' => 'What training is mandatory for care workers?', 'answer' => 'Mandatory training is the essential foundation every care worker needs. At CTA, we ensure care workers complete: Health and Safety Awareness : Understanding workplace hazards and your responsibilities; Infection Prevention and Control : Current practices for safe, hygienic care; Fire Safety : Evacuating safely and protecting people in your care; Moving and Handling : Safe techniques to protect yourself and those you support; Safeguarding Adults : Recognising and reporting abuse or neglect; Learning Disability and Autism Awareness (Oliver McGowan training) : Required by law since July 2022; Safeguarding Children : Essential for roles involving contact with under:18s. For roles involving medication, Medication Competency is non:negotiable. All of these are CQC expectations, and gaps in mandatory training are red flags during inspection. CTA Reality Check: Just because you\'ve done the online module doesn\'t mean you\'re competent. Our hands:on approach ensures your team can actually do these things under pressure:not just pass a quiz.'],
        ['category' => 'general', 'question' => 'How often does training need to be renewed?', 'answer' => 'The short answer: Every three years minimum for most mandatory training, unless the content is included in a recognised adult social care qualification within that three:year window. However, some topics need refreshing sooner if: new risks are introduced (new equipment, new care tasks); legislation changes (happens regularly in care); an incident or near:miss highlights gaps; individual competency assessments show decline. Medication training is more frequent:usually annual refreshers are expected because this is high:risk, high:compliance work. CTA Advice: Don\'t wait until certificates expire. Build a renewal calendar now and book courses 2:3 months in advance:our courses fill up fast, and compliance deadlines sneak up.'],
        ['category' => 'general', 'question' => 'What\'s the difference between CPD and accredited training?', 'answer' => 'CPD (Continuing Professional Development) is ongoing learning:it can be formal or informal, and it\'s flexible. Reading a safeguarding update article, shadowing a colleague on medication rounds, attending a webinar:that\'s all CPD. Accredited training is formal, assessed learning leading to a recognised qualification or certificate. It\'s governed by awarding bodies like TQUK, NCFE CACHE, or HSE (for First Aid). Accredited courses have standards, quality checks, and carry more weight. Why it matters for you: CQC expects some accredited training, but they also recognise CPD. A good training strategy mixes both:accredited courses for core mandatory training, CPD for specialisms and ongoing development. CTA Mix: We deliver HSE:approved First Aid (accredited), CQC:compliant courses (accredited), and we support your CPD through coaching, on:site training tailored to your policies, and scenario:based learning.'],
        ['category' => 'general', 'question' => 'Do online courses meet CQC requirements?', 'answer' => 'Short answer: It depends, but CQC is increasingly sceptical about online:only training. The CQC Inspection Framework values competence. For practical, high:stakes care skills (First Aid, medication administration, moving and handling), online modules don\'t build muscle memory or real:world confidence. Staff can\'t practice CPR on a dummy via Zoom. For theory:heavy topics (e.g., GDPR, understanding dementia), online can work as part of a blended approach. But even then, interaction and assessment matter. CQC Reality: Inspectors ask, "Can your staff actually do this if a crisis happened?" Online certificates often can\'t answer that confidently. CTA Stance: We\'re in:person, hands:on, and practical. No "click and certificate" shortcuts. Real people, real scenarios, real confidence.'],
        ['category' => 'general', 'question' => 'What is the Care Certificate and who needs it?', 'answer' => 'The Care Certificate is a nationally recognised qualification covering 15 standards for adult social care workers. It\'s not legally mandatory, but it\'s a gold standard:hugely respected by employers, CQC, and Skills for Care. Who benefits most: New care workers entering the industry; Care assistants progressing toward team leader roles; Anyone without formal social care qualifications; Roles involving domiciliary or residential care. What it covers: Communication, person:centred care, duty of care, safeguarding, equality, health and safety, infection control, medication awareness, mental health, dementia, nutrition, hydration, privacy, and dignity. Reality: The Care Certificate isn\'t mandatory, but if a CQC inspector sees staff without it and without equivalent qualifications, they ask why. It\'s the industry\'s signal of competence. CTA Offer: We deliver Care Certificate:aligned training and can support your team\'s progression pathway.'],
        ['category' => 'general', 'question' => 'How long are training certificates valid?', 'answer' => 'Most certificates are valid for three years, after which refresher training is required. This applies to: Safeguarding (all levels); Health and Safety; Fire Safety; Infection Control; Learning Disability and Autism Awareness. First Aid certificates (EFAW/Paediatric): Valid for three years. After that, you need a refresher course (not a full retraining). Medication and moving/handling: Often require annual refreshers or competency reassessment depending on your policy and the risks in your setting. Specialist certificates (e.g., dementia, end:of:life care) vary by awarding body:typically 3:5 years. CQC Inspection Tip: Inspectors will check your training evidence. Expired certificates are a compliance gap. Use our Training Renewal Tracker template to stay ahead.'],
        ['category' => 'general', 'question' => 'What happens if training expires?', 'answer' => 'Short answer: Your staff are no longer deemed competent for those duties, and you\'re in breach of CQC regulation. Practically, this means: That team member can\'t be rostered for duties requiring that training; You have a compliance gap in your inspection file; If an incident occurs and training is expired, liability falls on the organisation; Insurance may not cover incidents involving untrained staff. It\'s not a small issue. CQC explicitly checks training records for expiry dates. If you have expired Fire Safety training but an evacuation was needed, that\'s a serious finding. CTA Prevention: We help you build a training calendar and send renewal reminders. Spaces fill up:booking 2:3 months early keeps compliance on track.'],
        ['category' => 'general', 'question' => 'Can training be completed during probation?', 'answer' => 'Yes:and it should be. In fact, mandatory training is part of a proper induction. Best practice: Probation periods should include: Weeks 1:2: Emergency First Aid, Fire Safety, Health & Safety basics, safeguarding intro; Weeks 2:4: Moving and handling, infection control, role:specific training; Weeks 4:8: Deeper competency building, shadow rounds, assessments. CQC Expectation: Providers should evidence that staff are trained before they work unsupervised. Waiting until probation ends to train them is risky. CTA Approach: We offer fast:track, intensive courses that fit probation timelines. New starters can complete core training within their first two weeks, building confidence and competence from day one.'],
        
        // Booking & Scheduling (8 FAQs)
        ['category' => 'booking', 'question' => 'How do I book training for my team?', 'answer' => 'Three easy ways to book with CTA: 1. Eventbrite (Individual or small groups) : Visit our Eventbrite page, browse upcoming courses, select date, number of delegates, and book online. Payment and confirmation immediate. Perfect for quick bookings. 2. Direct Phone : Call 01622 587 343. Speak to our team about your specific needs. Discuss group discounts, dates, on:site options. Fast:track booking for employers. 3. Bespoke Group Training (Best for care providers) : Email enquiries@continuitytrainingacademy.co.uk. Discuss your team\'s training plan for the year. We tailor dates, venues, and course content. Often the most cost:effective for larger teams. Pro tip: Larger bookings (8+ delegates) get group discounts and flexible scheduling.'],
        ['category' => 'booking', 'question' => 'What\'s your cancellation policy?', 'answer' => 'For individual bookings (Eventbrite): More than 14 days before the course: Full refund; 7:14 days before: 75% refund; Less than 7 days: No refund (we can sometimes offer place transfer). For group/bespoke training: Cancellations made 30+ days in advance: Full refund (minus admin); 14:30 days: 50% refund; Less than 14 days: No refund. Delegate swaps (much easier): Can\'t make the date? Swap your spot with another team member anytime, free of charge. Just let us know in advance. CTA Philosophy: We build relationships, not rigid policies. If something\'s genuinely difficult, talk to us:we\'ll usually find a solution.'],
        ['category' => 'booking', 'question' => 'Can you deliver training at our location?', 'answer' => 'Absolutely:this is one of our strengths. We deliver on:site training at: Care homes; Supported living services; Domiciliary care provider offices; Health services; Nurseries and childcare. Why on:site training works: No travel time for your team; Training tailored to your policies and environment; Scenarios using your equipment, your settings, your processes; More cost:effective for large teams (often cheaper than public courses); Flexible scheduling (evenings/weekends available). What we need: Appropriate room (tables, chairs, privacy); Access to your equipment (mannequins for First Aid, moving equipment for M&H, etc.); 2:3 weeks\' notice for booking. CTA Mobile Reach: We serve Maidstone, Kent, and the wider South East. No travel is too far:we come to you.'],
        ['category' => 'booking', 'question' => 'What are your group booking discounts?', 'answer' => 'The more you book, the more you save. We offer tiered discounts based on group size, with even better rates for annual contracts. If you commit to regular training (e.g., quarterly refreshers, new starter inductions), we offer bespoke packages with deeper discounts. Contact us to discuss your specific needs and we\'ll provide a tailored quote. CTA Reality: Bulk training is our sweet spot. You get better pricing, we build a long:term relationship, and your compliance is sorted.'],
        ['category' => 'booking', 'question' => 'How far in advance should I book?', 'answer' => 'Ideal timeline: 8:12 weeks before you need training. Here\'s why: 8:10 weeks: Guarantees your preferred date and trainer; 4:8 weeks: Still good availability, but less flexibility; 2:4 weeks: Possible, but dates may be limited; Less than 2 weeks: Only book if you\'re flexible on dates. Seasonal peaks: January, April, September, and November are busy (new year resolutions, inspection prep, team changes). Book early if you target these months. Emergency training: Sometimes you need urgent refreshers (inspection notice, staff absence, incident). Call us:we\'ll do our best to squeeze you in, but can\'t promise preferred dates. CTA Tip: Plan your year\'s training calendar now. Block out dates in January, April, September, and November. This keeps compliance on track and can offer better rates through advance planning:contact us to discuss.'],
        ['category' => 'booking', 'question' => 'Do you offer weekend or evening training?', 'answer' => 'Yes:we\'re flexible. Evening courses (after 16:30): Available by request for groups of 8+; Perfect for teams with shift patterns; Usually 1:2 nights depending on the course. Weekend courses: Saturday courses available (9 AM : 4 PM); Ideal for care homes with limited weekday staff availability; Popular for roles requiring EFAW/Paeds before employment starts. Book ahead: Weekend and evening slots fill quickly. Give us 4:6 weeks\' notice for these. Not every course suits evening/weekend: Some hands:on courses (e.g., advanced moving and handling) work better during regular hours. We\'ll advise what\'s possible when you call.'],
        ['category' => 'booking', 'question' => 'What happens if staff can\'t attend on the day?', 'answer' => 'If a delegate can\'t make it: 1. More than 7 days before: Free transfer to another date (no charge, no refund); 2. Less than 7 days: Same policy applies (we operate on goodwill, not penalties); 3. Last:minute emergency: Contact us ASAP. We\'ll try to reschedule or find a replacement from your team. Why we\'re flexible: We know care is unpredictable. Unplanned absences happen. Swapping delegates is often the easiest solution. What we ask: Just give us notice so we can update the register and ensure the right people are trained.'],
        ['category' => 'booking', 'question' => 'Can I change training dates after booking?', 'answer' => 'Yes, with flexibility depending on timing: 8+ weeks before: Free date change, no questions; 4:8 weeks before: Free change if we have availability; 2:4 weeks before: Possible, but limited slots:ask first; Less than 2 weeks: Difficult, but we\'ll try. Delegate swaps: Super easy. If Person A can\'t make 15 March but Person B can, just tell us. No charge. Group courses: If you\'ve booked a bespoke on:site course and need to reschedule, we\'ll work with your calendar. Usually 2:3 weeks\' notice keeps things smooth. Bottom line: We work around your needs. Life in care is busy:we get it.'],
        
        // Certification & Accreditation (8 FAQs)
        ['category' => 'certification', 'question' => 'Are your courses CQC:compliant?', 'answer' => 'Yes:100%. Every course we deliver aligns with: CQC Regulation 18 (Training requirements); CQC Inspection Framework (Key Lines of Enquiry for training and competence); Skills for Care standards (statutory and mandatory training guide); HSE requirements (for First Aid and Health & Safety courses). What this means practically: Our content covers what CQC inspectors expect to see; We provide evidence (certificates, attendance records, competency sign:offs); Our courses bridge the gap between "completed training" and "can actually do the job"; If inspectors ask, "Can you evidence competence?":we help you answer confidently. CTA Commitment: We don\'t just deliver courses. We help you build a training portfolio that stands up to CQC scrutiny.'],
        ['category' => 'certification', 'question' => 'What accreditations do you have?', 'answer' => 'CTA holds: Advantage Accreditation : Centre of the Year 2021; HSE Approval : For Emergency First Aid at Work (Level 3); CPD Accreditation : All our courses are CPD:registered; Skills for Care alignment : Our content matches their statutory and mandatory training standards; Ofsted compliance : For childcare:related courses (Paediatric First Aid). Quality Assurance: Trainers are industry:experienced (not just certified); Annual quality reviews and updates; Feedback:driven course design; Scenario:based, practical assessment. CTA Transparency: We\'re happy to share accreditation documents. Ask when you enquire.'],
        ['category' => 'certification', 'question' => 'Who accredits your certificates?', 'answer' => 'Depends on the course: First Aid (EFAW/Paediatric): HSE:approved via our accreditation body; Medication Competency: CQC:compliant, Skills for Care:aligned assessment; Safeguarding, Moving & Handling, etc.: CPD:accredited and Skills for Care:referenced; Care Certificate: Aligned with Skills for Care standards (if relevant to your pathway). What this means: Your certificates carry weight nationally. Employers, CQC, and other providers recognise them. Not a franchise course list? No. We deliver tailored, CQC:compliant training. Certificates are evidence of your competence, assessed by our expert trainers in real:world scenarios.'],
        ['category' => 'certification', 'question' => 'Are your certificates accepted nationally?', 'answer' => 'Yes. Our certificates are: Recognised by CQC; Accepted by employers across the UK; Valid for roles in care homes, domiciliary care, nursing, supported living, and specialist services; Transferable if staff move between employers. The only exception: Some roles (e.g., registered nurse, specific clinical roles) may require additional qualifications or registration. We\'ll advise on this during booking. Pro tip: Your training records (ours + any others) build a portfolio showing ongoing competence development. This is gold for CQC and for staff morale.'],
        ['category' => 'certification', 'question' => 'Do you provide digital certificates?', 'answer' => 'Yes:instant digital delivery after course completion. After your course ends: Digital certificate sent to your email same day (or next business day); PDF format:easy to print, share, or store; Includes attendee name, course name, date, trainer name, and validity period; Registrar\'s signature and CTA accreditation details. Physical copies: Available on request:contact us for details. Storing certificates: We recommend: Digital backup (secure shared drive); Staff personnel files; Training management system (if you use one). CQC Inspection: Have these ready. Inspectors will ask to see evidence. Digital + physical copies = fully prepared.'],
        ['category' => 'certification', 'question' => 'How quickly do we receive certificates?', 'answer' => 'Typically within 24 hours of course completion. For courses ending in the afternoon, digital certificates are sent by end of business. For courses ending mid:day, you usually have them by email within 2 hours. Urgent timescales? If you need evidence before a specific date (e.g., CQC inspection notice), let us know when booking. We can often expedite. No waiting games: This is one advantage of in:person training:you know immediately if staff are competent, and you get proof fast.'],
        ['category' => 'certification', 'question' => 'What if a certificate is lost?', 'answer' => 'No problem:we hold records. Email us with attendee name and course date; We\'ll provide a replacement digital certificate (free); Physical copy available if needed:contact us for details; Process usually takes 2:3 working days. Backup strategy: Keep digital copies of all certificates in a secure shared drive (Google Drive, OneDrive, etc.). This prevents loss and makes CQC inspections stress:free.'],
        ['category' => 'certification', 'question' => 'Do your courses meet Skills for Care standards?', 'answer' => 'Completely. All our content aligns with: Skills for Care Statutory and Mandatory Training Guide (August 2024 update); Care Certificate standards (15 standards for adult social care workers); Oliver McGowan Training on Learning Disability and Autism; Leadership and management frameworks (for manager:level courses). Why this matters: If you\'re applying for Workforce Development Fund (LDSS) grants, our courses are eligible; Staff trained with us have a recognised, national qualification; CQC sees Skills for Care alignment as best practice. CTA + Skills for Care: We stay updated on changes and refresh our content annually. You\'re always current.'],
        
        // Course-Specific Questions (6 FAQs)
        ['category' => 'course:specific', 'question' => 'What\'s included in the Care Certificate?', 'answer' => 'The Care Certificate covers 15 standards: 1. Understanding your role : Knowing your responsibilities and accountabilities; 2. Your health, safety and wellbeing : Protecting yourself while at work; 3. Duty of care : Understanding safeguarding and your legal obligations; 4. Equality and inclusion : Treating people fairly and respecting diversity; 5. Working in a person:centred way : Putting the individual at the centre; 6. Communication : Listening, speaking, and understanding diverse needs; 7. Privacy and dignity : Respecting confidentiality and personal space; 8. Fluids and nutrition : Supporting healthy eating and drinking; 9. Awareness of mental health, dementia and learning disabilities; 10. Safeguarding adults; 11. Safeguarding children; 12. Basic life support and First Aid; 13. Health and safety in care settings; 14. Handling information and keeping it confidential; 15. Infection prevention and control. Format: Mix of taught sessions, practice scenarios, and practical assessment. Time: Usually 8:10 days (depending on delivery method). CTA Approach: We deliver Care Certificate content in real:world scenarios using your setting. Staff leave not just "trained" but confident.'],
        ['category' => 'course:specific', 'question' => 'Which first aid course do childcare staff need?', 'answer' => 'Childcare staff require: Emergency Paediatric First Aid (Level 3), OFSTED:approved. This covers: CPR on infants and children; Choking (different techniques for kids); Common paediatric emergencies (febrile convulsions, allergic reactions, etc.); Recovery position for children; Assessment and reassurance in a crisis. Why separate from adult EFAW? Anatomy differs (tiny airways, different compression depths), and early childhood scenarios are unique. Both courses matter: Some roles (e.g., managers in nurseries) benefit from both Adult EFAW and Paediatric EFAW:for comprehensive coverage. CTA Delivery: One:day, practical course. Small groups, lots of mannequin practice. Staff leave confident they could handle a real paediatric emergency. Regulatory note: OFSTED expects evidence of Paediatric First Aid. It\'s not optional in childcare.'],
        ['category' => 'course:specific', 'question' => 'What\'s the difference between medication awareness and competency?', 'answer' => 'Medication Awareness: Understand what medications are, why people take them, side effects; Know how to store and handle medications safely; Understand why accurate records matter; Can explain but not administer. Medication Competency: Can administer medications correctly (oral, topical, injected:depending on role); Understands the "5 Rights" (right person, drug, dose, route, time); Can assess when to withhold medication; Assessed as competent by a trainer/assessor. When Awareness enough? Roles where staff handle meds but don\'t administer (e.g., care assistants, domiciliary support). When Competency needed? Direct administration:care workers giving tablets, nurses giving injections, anyone signing off medication administration. CQC Reality: Inspectors ask, "Who can administer medications?" Your answer must be specific and evidenced. Awareness ≠ Competency. CTA Approach: We assess actual competence. No guessing on the day:we verify you can do it safely.'],
        ['category' => 'course:specific', 'question' => 'Do I need moving & handling theory AND practical?', 'answer' => 'Yes:both are essential. They\'re not separate. Theory (classroom): Understanding biomechanics, spine health, loads; Legislation (Health & Safety at Work Act, MHOR); Risk assessment approach; Communication and consent. Practical (hands:on): Transferring using equipment (slide sheets, hoists, turntables); Manual handling techniques (where absolutely necessary); Adaptive methods for different conditions (stroke, arthritis, dementia); Real equipment your service uses. Why both? You can\'t safely move someone without understanding why you\'re doing it that way. Theory informs practice. Duration: Usually 1:2 days depending on role complexity. CTA Difference: We bring your actual equipment. Training happens in your care environment (if on:site), not a sterile classroom.'],
        ['category' => 'course:specific', 'question' => 'Is safeguarding training different for managers?', 'answer' => 'Yes:significantly. Safeguarding Level 1 (for all care workers): Recognising signs of abuse/neglect; Knowing who to report to; Understanding your role; Basic case scenarios. Safeguarding Level 2 (for supervisory/team roles): More detailed abuse types (including institutional, self:neglect); Recording and evidence gathering; Supporting victims and witnesses; Creating a safeguarding culture; Policy and procedure implementation. Safeguarding Level 3 (for managers/registered managers): Safeguarding strategy and policy development; Managing allegations; Multi:agency working (police, social care investigations); Creating systems and oversight; Legal responsibilities and accountability. CQC Inspection: Inspectors specifically check that managers have Level 2 or 3 evidence. If you don\'t, that\'s a compliance gap. CTA Delivery: Role:specific, scenario:based. Managers leave with confidence in handling real safeguarding issues.'],
        ['category' => 'course:specific', 'question' => 'What level of dementia training do we need?', 'answer' => 'Depends on your role and service type: Level 1 (Awareness:for all staff): What dementia is, types (Alzheimer\'s, vascular, etc.); Progression and symptoms; Communicating with someone with dementia; Reducing triggers for distress; Basic person:centred approaches. Who needs it? Everyone in care. Level 2 (Principles:for care and supervisory roles): Deeper understanding of dementia care; Understanding behaviour as communication; Environmental design for dementia support; Working with families; Managing complex behaviours. Who needs it? Care workers, team leaders, activity coordinators. Level 3 (Advanced:for managers, specialists): Dementia care strategy; Staff training and supervision; Complex presentations (advanced dementia, co:morbidities); End:of:life care in dementia. Who needs it? Registered managers, clinical leads. CQC Expectation: All staff should have at least Level 1. If your service specialises in dementia, Level 2+ is standard. CTA Reality: Dementia care isn\'t a checkbox. It\'s a way of thinking. We train for genuine understanding, not just certificate collection.'],
        
        // Payment & Funding (6 FAQs)
        ['category' => 'payment', 'question' => 'What payment methods do you accept?', 'answer' => 'We accept: Debit and credit cards (Visa, Mastercard, American Express); Bank transfer (BACS); Cheque (with advance notice). Eventbrite bookings: Payment taken online (card only) at booking. Large group/invoice:based bookings: Bank transfer often preferred. We\'ll invoice after confirming course details. No payment issues: CTA is transparent on pricing. No hidden fees, no surprise charges. Payment timing: Invoiced courses typically due within 30 days. Eventbrite bookings are immediate.'],
        ['category' => 'payment', 'question' => 'Do you offer payment plans?', 'answer' => 'For larger group training commitments, yes. If you\'re investing in an annual training plan (e.g., quarterly mandatory updates for a care home), we can discuss: Staged payments across the year; Deposit + final payment structure; Monthly training packages with set costs. Small individual courses: Fixed pricing, payment upfront (Eventbrite) or via invoice. How to arrange: Email enquiries@continuitytrainingacademy.co.uk with your training needs. We\'ll discuss options. CTA Philosophy: We partner with you for the long term. If payment structure is the barrier, let\'s solve it.'],
        ['category' => 'payment', 'question' => 'Can we use Workforce Development Fund?', 'answer' => 'Short answer: Yes, but it\'s now called LDSS (Learning and Development Support Scheme). What changed: The Workforce Development Fund (WDF) was replaced by the Adult Social Care Learning and Development Support Scheme (LDSS) from April 2025. How it works: 1. Check the eligible course list on gov.uk (our courses are listed); 2. Book and pay for training upfront; 3. Claim reimbursement from LDSS (up to the stated maximum per course). Eligible courses include: The Oliver McGowan Training (Tier 1 & 2); Leadership and management programmes; Specialist qualifications (dementia, autism, end:of:life care); Some diploma:level adult care qualifications. What\'s NOT covered: General awareness training, First Aid, moving & handling (unless part of a larger qualification). CTA Support: When you book, tell us you\'re using LDSS. We\'ll provide invoices and documentation to support your claim. Important: Check the eligible course list and reimbursement rates annually. LDSS changes quarterly.'],
        ['category' => 'payment', 'question' => 'Do you provide training invoices for our records?', 'answer' => 'Absolutely. We provide: Itemised invoices (course name, date, attendee list, cost); Attendance certificates for all participants; Training records showing competency sign:off; Digital copies of all documents. Invoice timing: Sent within 2 working days of course completion. For LDSS claims: We provide all documentation needed to submit your reimbursement claim. Compliance ready: Everything is formatted for audit and CQC inspection.'],
        ['category' => 'payment', 'question' => 'Are there discounts for multiple courses?', 'answer' => 'Yes:our group discount structure includes volume savings based on the number of delegates and courses booked. Multiple courses over a year? Even better. Annual packages offer significant savings compared to ad:hoc bookings. Contact us with your annual training plan and we\'ll provide a bespoke quote tailored to your needs. How to arrange: Email enquiries@continuitytrainingacademy.co.uk with your annual training plan. We\'ll quote a bespoke package.'],
        ['category' => 'payment', 'question' => 'Can we pay per delegate or per course?', 'answer' => 'Both options available, depending on structure: Per delegate (most common for groups): You pay for each person attending; Useful if team numbers fluctuate. Per course (block booking): You book a course for a specific date; One price, regardless of final headcount (within limits); Useful for annual planning. Flexible approach: If some staff might not attend, per:delegate pricing reduces financial risk. If you\'re certain of headcount, per:course is often cheaper. When booking: We\'ll ask about your preference and recommend the most cost:effective option. Contact us to discuss pricing for your specific needs.'],
        
        // Group Training & Employers (6 FAQs)
        ['category' => 'group:training', 'question' => 'What\'s the minimum group size for on:site training?', 'answer' => 'Minimum: 6 people for on:site courses (to make travel worthwhile for our trainer). Smaller groups (1:5 people): Can usually attend a public course instead, often at similar or better cost. Larger groups (8:25 people): Often more cost:effective on:site with group discounts. Contact us to discuss pricing for your group size and we\'ll recommend the most cost:effective option. Talk to us: If you have 4:5 people, email enquiries@continuitytrainingacademy.co.uk. We might combine them with another organisation\'s group or suggest public course dates.'],
        ['category' => 'group:training', 'question' => 'Can you tailor training to our policies?', 'answer' => 'Completely:that\'s what we do. When we deliver on:site training, we: Review your policies and procedures beforehand; Tailor scenarios to your care environment; Use your equipment (hoists, moving aids, medication charts); Address your specific risks and challenges; Train your staff in your context, not generic care theory. Examples: A domiciliary care agency: We focus on home safety, lone working, medication in non:clinical settings; A care home: We include your facilities, your resident needs, your escalation procedures; A nursing home: We address clinical protocols, medication administration, delegation. CQC Reality: Inspectors often ask, "Is your training tailored to your service?" Bespoke training shows yes. CTA Commitment: No off:the:shelf courses. Your training, your setting, your standards.'],
        ['category' => 'group:training', 'question' => 'Do you provide training matrices for your staff?', 'answer' => 'Yes:in multiple formats. What we provide: Role:based training matrices (care worker, team leader, manager, clinical staff, specialists); Mapped against CQC Key Lines of Enquiry; Showing mandatory, recommended, and specialist training; Frequency and refresher timelines; Eligibility criteria for each role. Formats: Excel (editable:you can adapt to your specific structure); PDF (for sharing with staff, managers); Printable or digital storage. How it works: You use the matrix to plan training for each team member, identify gaps, and schedule refreshers. It becomes your annual training plan. CTA Support: We help populate the matrix during your first year, then you own it.'],
        ['category' => 'group:training', 'question' => 'Can you track training for multiple sites?', 'answer' => 'For group training contracts, yes. If you operate multiple care homes, domiciliary services, or nursing facilities, we: Maintain separate attendance and competency records per site; Provide aggregate reports showing compliance across all locations; Help you identify organisation:wide training gaps; Support your quality assurance and CQC preparation. How it works: Provide us with a list of sites and roles; We schedule training across all locations; We send site:specific and consolidated reports; You have a complete training audit trail. Size: Works well for organisations with 2:5 locations. Larger networks? Discuss your reporting needs when you call. We may recommend a training management system.'],
        ['category' => 'group:training', 'question' => 'Do you offer annual training contracts?', 'answer' => 'Yes:our preferred model for care providers. An annual training contract typically includes: Quarterly mandatory refreshers for all staff; New starter induction training; Role:specific development (team leader, manager, clinical); Specialist courses (dementia, end:of:life, safeguarding advanced); On:site delivery where possible; Priority booking and reserved dates; Discounted rates compared to ad:hoc bookings; Annual training calendar and planning support. Cost varies depending on staff size and course mix:contact us for a tailored quote. Benefit: Compliance is sorted. You\'re not scrambling to book last:minute courses or facing expired certificates. How to arrange: 1. Email enquiries@continuitytrainingacademy.co.uk; 2. Describe your team size, roles, and compliance needs; 3. We\'ll build a bespoke annual plan and quote. CTA Reality: This is where we excel:long:term partnerships, strategic training planning, embedded quality.'],
        ['category' => 'group:training', 'question' => 'Can trainers visit multiple locations?', 'answer' => 'Absolutely:we\'re mobile. If you operate multiple sites across Kent and the South East: Our trainer can visit Site A, Site B, Site C on consecutive days; Cost:effective (one trainer deployment vs. three separate bookings); Consistent messaging across locations; Easier compliance tracking. What we need: 2:3 weeks\' notice for a multi:site tour; Clear list of dates, locations, and participant numbers; Appropriate training space at each location; Same course (easier to coordinate) or closely related courses. Example: A domiciliary care provider with 3 office locations books Emergency First Aid at all three on Mon/Tues/Weds. One trainer, one week, significant savings. Logistics: We handle travel. You just confirm locations and dates. CTA Advantage: Mobile, flexible, organised. Your training fits your geography, not the other way around.'],
    ];
}

/**
 * Create FAQ categories if they don't exist
 */
function cta_create_faq_categories() {
    $categories = [
        'general' => 'General Training',
        'booking' => 'Booking & Scheduling',
        'certification' => 'Certification & Accreditation',
        'course-specific' => 'Course-Specific',
        'payment' => 'Payment & Funding',
        'group-training' => 'Group Training & Employers',
    ];
    
    foreach ($categories as $slug => $name) {
        $term = get_term_by('slug', $slug, 'faq_category');
        if (!$term) {
            wp_insert_term($name, 'faq_category', ['slug' => $slug]);
        }
    }
}

/**
 * Auto-populate FAQs from default data
 */
function cta_populate_faqs_from_defaults() {
    // Check if FAQs already exist
    $existing_faqs = get_posts([
        'post_type' => 'faq',
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);
    
    // Only populate if no FAQs exist
    if (!empty($existing_faqs)) {
        return 0;
    }
    
    // Create categories first
    cta_create_faq_categories();
    
    // Get default FAQs
    $default_faqs = cta_get_default_faqs();
    $created = 0;
    $menu_order = 0;
    
    foreach ($default_faqs as $faq) {
        // Check if FAQ already exists by title
        $existing = get_page_by_title($faq['question'], OBJECT, 'faq');
        if ($existing) {
            continue;
        }
        
        // Map category slug (template uses 'course:specific' and 'group:training', taxonomy uses 'course-specific' and 'group-training')
        $category_slug = str_replace(':', '-', $faq['category']);
        
        // Create FAQ post
        $post_data = [
            'post_title' => $faq['question'],
            'post_content' => $faq['answer'], // Fallback content
            'post_status' => 'publish',
            'post_type' => 'faq',
            'menu_order' => $menu_order++,
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (!is_wp_error($post_id) && $post_id > 0) {
            // Set ACF answer field
            if (function_exists('update_field')) {
                update_field('faq_answer', $faq['answer'], $post_id);
            }
            
            // Assign category
            $term = get_term_by('slug', $category_slug, 'faq_category');
            if ($term) {
                wp_set_object_terms($post_id, [$term->term_id], 'faq_category');
            }
            
            $created++;
        }
    }
    
    return $created;
}
add_action('after_switch_theme', 'cta_populate_faqs_from_defaults', 30);
