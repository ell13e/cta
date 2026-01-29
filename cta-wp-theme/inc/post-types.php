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
                echo '<span class="cta-admin-category">' . esc_html(implode(', ', wp_list_pluck($terms, 'name'))) . '</span>';
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
    
    // Generate base slug from title
    $base_slug = sanitize_title($post->post_title);
    $suffix_parts = [];
    
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
    
    // Add level shorthand (e.g., "Level 2" → "l2")
    if ($level && preg_match('/(\d+)/i', $level, $matches)) {
        $suffix_parts[] = 'l' . $matches[1];
    }
    
    // Build final slug
    $new_slug = $base_slug;
    if (!empty($suffix_parts)) {
        $new_slug .= '-' . implode('-', $suffix_parts);
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

