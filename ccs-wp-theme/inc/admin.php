<?php
/**
 * Admin Customizations
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Add dashboard widgets
 */
function ccs_add_dashboard_widgets() {
    wp_add_dashboard_widget(
        'ccs_upcoming_courses_widget',
        'Upcoming Courses',
        'ccs_dashboard_widget_content'
    );
    
    // Add Google Analytics widget if GA ID is configured
    $ga_id = get_option('ccs_google_analytics_id', '');
    if (!empty($ga_id)) {
        wp_add_dashboard_widget(
            'ccs_google_analytics_widget',
            'Website Analytics',
            'ccs_google_analytics_widget_content'
        );
    }
}
add_action('wp_dashboard_setup', 'ccs_add_dashboard_widgets');

/**
 * Dashboard widget content
 */
function ccs_dashboard_widget_content() {
    // Check if ACF is available
    if (!function_exists('get_field')) {
        echo '<p>Advanced Custom Fields plugin is required for this widget.</p>';
        return;
    }
    
    $today = date('Y-m-d');
    
    $args = [
        'post_type' => 'course_event',
        'posts_per_page' => 5,
        'meta_key' => 'event_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => [
            [
                'key' => 'event_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE',
            ],
        ],
    ];
    
    $events = new WP_Query($args);
    
    if ($events->have_posts()) :
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Course</th><th>Date</th><th>Spaces</th></tr></thead>';
        echo '<tbody>';
        
        while ($events->have_posts()) : $events->the_post();
            $course = get_field('linked_course');
            $date = get_field('event_date');
            $spaces = get_field('spaces_available');
            $course_name = $course ? $course->post_title : get_the_title();
            
            $spaces_class = '';
            if ($spaces !== '' && $spaces <= 3) {
                $spaces_class = ' style="color: #d63638; font-weight: bold;"';
            }
            
            echo '<tr>';
            echo '<td><a href="' . get_edit_post_link() . '">' . esc_html($course_name) . '</a></td>';
            echo '<td>' . esc_html(date('j M Y', strtotime($date))) . '</td>';
            echo '<td' . $spaces_class . '>' . ($spaces !== '' ? esc_html($spaces) : '-') . '</td>';
            echo '</tr>';
        endwhile;
        
        echo '</tbody></table>';
        wp_reset_postdata();
        
        echo '<p style="text-align: right; margin-top: 12px;"><a href="' . admin_url('edit.php?post_type=course_event') . '" class="button">View All Sessions</a></p>';
    else :
        echo '<p>No upcoming courses scheduled.</p>';
        echo '<p><a href="' . admin_url('post-new.php?post_type=course_event') . '" class="button button-primary">Schedule a Course</a></p>';
    endif;
}

/**
 * Google Analytics Dashboard Widget
 * Lightweight widget showing key metrics without plugin bloat
 */
function ccs_google_analytics_widget_content() {
    $ga_id = get_option('ccs_google_analytics_id', '');
    
    if (empty($ga_id)) {
        echo '<div class="notice notice-warning inline"><p>';
        echo '<strong>Google Analytics not configured.</strong><br>';
        echo 'Add your GA4 Measurement ID in <a href="' . admin_url('options-general.php?page=cta-api-keys') . '">Settings → API Keys</a>.';
        echo '</p></div>';
        return;
    }
    
    // Get cached stats (cache for 1 hour)
    $cache_key = 'ccs_ga_stats_' . md5($ga_id);
    $stats = get_transient($cache_key);
    
    // If no cache, show basic info with link to GA
    if ($stats === false) {
        // Try to get basic stats (this is lightweight - just shows link and basic info)
        $stats = [
            'configured' => true,
            'ga_id' => $ga_id,
            'last_updated' => current_time('mysql'),
        ];
        
        // Cache for 1 hour
        set_transient($cache_key, $stats, HOUR_IN_SECONDS);
    }
    
    ?>
    <div>
        <p class="description" style="margin-bottom: 20px;">
            <strong>Google Analytics:</strong> <?php echo esc_html($ga_id); ?> (Active)
        </p>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
            <a href="https://analytics.google.com/" target="_blank" class="button button-primary">
                Open Google Analytics
            </a>
            <a href="<?php echo admin_url('options-general.php?page=cta-api-keys'); ?>" class="button">
                Settings
            </a>
        </div>
        
        <?php if (isset($stats['last_updated'])) : ?>
        <p class="description" style="margin-top: 10px; text-align: right; font-size: 11px;">
            Last checked: <?php echo human_time_diff(strtotime($stats['last_updated']), current_time('timestamp')); ?> ago
        </p>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Optional: Add refresh button functionality
        $('#ccs_google_analytics_widget').on('click', '.cta-ga-refresh', function(e) {
            e.preventDefault();
            var $widget = $(this).closest('.postbox');
            $widget.find('.inside').html('<p style="text-align: center; padding: 20px;"><span class="spinner is-active" style="float: none; margin: 0 auto;"></span> Refreshing...</p>');
            
            // Reload page after a moment to refresh cache
            setTimeout(function() {
                location.reload();
            }, 1000);
        });
    });
    </script>
    <?php
}

/**
 * Custom login logo
 */
function ccs_login_logo() {
    $logo_url = CCS_THEME_URI . '/assets/img/logo/long_logo-400w.webp';
    ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo esc_url($logo_url); ?>);
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            width: 320px;
            height: 80px;
        }
        .login form {
            border-radius: 8px;
        }
        .wp-core-ui .button-primary {
            background: #3ba59b;
            border-color: #2d8b82;
        }
        .wp-core-ui .button-primary:hover {
            background: #2d8b82;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'ccs_login_logo');

/**
 * Custom login logo URL
 */
function ccs_login_logo_url() {
    return home_url('/');
}
add_filter('login_headerurl', 'ccs_login_logo_url');

/**
 * Custom login logo title
 */
function ccs_login_logo_title() {
    return get_bloginfo('name');
}
add_filter('login_headertext', 'ccs_login_logo_title');

/**
 * Add admin bar menu for quick access
 */
function ccs_admin_bar_menu($wp_admin_bar) {
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    // Add Courses menu
    $wp_admin_bar->add_node([
        'id' => 'cta-courses',
        'title' => '<span class="ab-icon dashicons dashicons-welcome-learn-more"></span> Courses',
        'href' => admin_url('edit.php?post_type=course'),
    ]);
    
    $wp_admin_bar->add_node([
        'parent' => 'cta-courses',
        'id' => 'cta-courses-all',
        'title' => 'All Courses',
        'href' => admin_url('edit.php?post_type=course'),
    ]);
    
    $wp_admin_bar->add_node([
        'parent' => 'cta-courses',
        'id' => 'cta-courses-new',
        'title' => 'Add New Course',
        'href' => admin_url('post-new.php?post_type=course'),
    ]);
    
    $wp_admin_bar->add_node([
        'parent' => 'cta-courses',
        'id' => 'cta-courses-categories',
        'title' => 'Categories',
        'href' => admin_url('edit-tags.php?taxonomy=course_category&post_type=course'),
    ]);
    
    // Add Sessions menu
    $wp_admin_bar->add_node([
        'parent' => 'cta-courses',
        'id' => 'cta-sessions',
        'title' => 'Scheduled Sessions',
        'href' => admin_url('edit.php?post_type=course_event'),
    ]);
    
    $wp_admin_bar->add_node([
        'parent' => 'cta-courses',
        'id' => 'cta-sessions-new',
        'title' => 'Add New Session',
        'href' => admin_url('post-new.php?post_type=course_event'),
    ]);
    
}
add_action('admin_bar_menu', 'ccs_admin_bar_menu', 100);

/**
 * Admin footer text
 */
function ccs_admin_footer_text($text) {
    return 'Continuity of Care Services Theme &bull; <a href="https://continuitytrainingacademy.co.uk" target="_blank">Visit Site</a>';
}
add_filter('admin_footer_text', 'ccs_admin_footer_text');

/**
 * Remove unnecessary dashboard widgets
 */
function ccs_remove_dashboard_widgets() {
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
}
add_action('wp_dashboard_setup', 'ccs_remove_dashboard_widgets');

/**
 * Remove tags meta box from post editor
 */
function ccs_remove_tags_meta_box() {
    remove_meta_box('tagsdiv-post_tag', 'post', 'side');
}
add_action('admin_menu', 'ccs_remove_tags_meta_box');

/**
 * Auto-generate session title from course and date
 */
function ccs_auto_generate_session_title($post_id, $post, $update) {
    // Only for course_event post type
    if ($post->post_type !== 'course_event') {
        return;
    }
    
    // Check if ACF is available
    if (!function_exists('get_field')) {
        return;
    }
    
    // Prevent infinite loop
    remove_action('save_post', 'ccs_auto_generate_session_title', 20, 3);
    
    $course = get_field('linked_course', $post_id);
    $date = get_field('event_date', $post_id);
    
    if ($course) {
        // Only use course title, don't add date since it's shown separately
        $title = $course->post_title;
        
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $title,
        ]);
    }
    
    add_action('save_post', 'ccs_auto_generate_session_title', 20, 3);
}
add_action('save_post', 'ccs_auto_generate_session_title', 20, 3);

/**
 * Add quick links to course row actions
 */
function ccs_course_row_actions($actions, $post) {
    if ($post->post_type === 'course') {
        $actions['schedule'] = sprintf(
            '<a href="%s">Schedule Session</a>',
            admin_url('post-new.php?post_type=course_event&course_id=' . $post->ID)
        );
    }
    return $actions;
}
add_filter('post_row_actions', 'ccs_course_row_actions', 10, 2);

/**
 * Pre-fill course when creating session from course
 */
function ccs_prefill_session_course() {
    global $pagenow;
    
    if ($pagenow !== 'post-new.php' || !isset($_GET['post_type']) || $_GET['post_type'] !== 'course_event') {
        return;
    }
    
    if (!isset($_GET['course_id'])) {
        return;
    }
    
    $course_id = intval($_GET['course_id']);
    $course = get_post($course_id);
    $course_title = $course && $course->post_type === 'course' ? $course->post_title : '';
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Wait for ACF to load
        setTimeout(function() {
            var $field = $('[data-key="field_linked_course"]');
            if ($field.length) {
                // For ACF select2 field
                var courseId = <?php echo $course_id; ?>;
                $field.find('select').val(courseId).trigger('change');
                
                // Also set title immediately if we have it
                <?php if ($course_title) : ?>
                var $titleField = $('#title');
                if ($titleField.length && (!$titleField.val() || $titleField.val() === 'Auto Draft')) {
                    $titleField.val('<?php echo esc_js($course_title); ?>');
                    $titleField.data('has-value', true);
                    if ($titleField.attr('placeholder')) {
                        $titleField.attr('placeholder', '');
                    }
                }
                <?php endif; ?>
            }
        }, 500);
    });
    </script>
    <?php
}
add_action('admin_footer-post-new.php', 'ccs_prefill_session_course');

/**
 * Auto-populate session title from selected course
 * NOTE: Automatic behavior removed - use manual trigger in Import CTA Data page
 * This function is kept for manual bulk operations
 */
function ccs_auto_populate_session_title() {
    // Automatic behavior disabled - function kept for manual use only
    return;
}

/**
 * Bulk update session titles from linked courses
 * Manual trigger function for Import CTA Data page
 * 
 * @return array Result with 'updated', 'skipped', and 'errors' counts
 */
function ccs_bulk_update_session_titles() {
    if (!current_user_can('edit_posts')) {
        return ['success' => false, 'message' => 'You do not have permission to perform this action.'];
    }
    
    $updated = 0;
    $skipped = 0;
    $errors = [];
    
    // Get all course events
    $events = get_posts([
        'post_type' => 'course_event',
        'posts_per_page' => -1,
        'post_status' => 'any',
    ]);
    
    foreach ($events as $event) {
        // Get linked course
        $course_id = function_exists('get_field') ? get_field('linked_course', $event->ID) : null;
        
        if (!$course_id) {
            $skipped++;
            continue;
        }
        
        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'course') {
            $skipped++;
            continue;
        }
        
        // Update title to match course title
        $new_title = $course->post_title;
        $result = wp_update_post([
            'ID' => $event->ID,
            'post_title' => $new_title,
        ]);
        
        if (is_wp_error($result)) {
            $errors[] = [
                'event_id' => $event->ID,
                'title' => $event->post_title,
                'error' => $result->get_error_message()
            ];
        } else {
            $updated++;
        }
    }
    
    return [
        'success' => true,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $errors,
        'message' => sprintf('Updated %d session title(s), skipped %d event(s).', $updated, $skipped)
    ];
}

/**
 * AJAX handler to get course title
 * NOTE: Kept for manual bulk operations in Import CTA Data page
 */
function ccs_get_course_title_ajax() {
    check_ajax_referer('ccs_get_course_title', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    
    if (!$course_id) {
        wp_send_json_error(['message' => 'Invalid course ID']);
    }
    
    $course = get_post($course_id);
    
    if (!$course || $course->post_type !== 'course') {
        wp_send_json_error(['message' => 'Course not found']);
    }
    
    wp_send_json_success(['title' => $course->post_title]);
}
add_action('wp_ajax_ccs_get_course_title', 'ccs_get_course_title_ajax');

/**
 * Ensure only one event can be featured at a time
 */
function ccs_handle_featured_event($post_id) {
    // Check if ACF is available
    if (!function_exists('get_field') || !function_exists('update_field')) {
        return;
    }
    
    // Get post object (acf/save_post only passes post_id)
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'course_event') {
        return;
    }
    
    $is_featured = get_field('event_featured', $post_id);
    
    if ($is_featured) {
        // Unfeature all other events
        $other_events = get_posts([
            'post_type' => 'course_event',
            'posts_per_page' => -1,
            'post__not_in' => [$post_id],
            'meta_query' => [
                [
                    'key' => 'event_featured',
                    'value' => '1',
                    'compare' => '=',
                ],
            ],
        ]);
        
        foreach ($other_events as $event) {
            update_field('event_featured', 0, $event->ID);
        }
    }
}
add_action('acf/save_post', 'ccs_handle_featured_event', 20);

/**
 * Sync featured image to ACF event_image field when featured image is set
 * This ensures both the WordPress featured image and ACF field stay in sync
 */
function ccs_sync_featured_image_to_acf($post_id) {
    // Only for course and course_event post types
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, ['course', 'course_event'])) {
        return;
    }
    
    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // Check if featured image was set
    $thumbnail_id = get_post_thumbnail_id($post_id);
    
    if ($thumbnail_id) {
        // Sync to ACF event_image field for course events
        if ($post->post_type === 'course_event' && function_exists('update_field')) {
            // Only update if ACF field doesn't already match (avoid infinite loops)
            $current_event_image = get_field('event_image', $post_id);
            $current_image_id = null;
            
            if (is_array($current_event_image) && isset($current_event_image['ID'])) {
                $current_image_id = intval($current_event_image['ID']);
            } elseif (is_numeric($current_event_image)) {
                $current_image_id = intval($current_event_image);
            }
            
            // Only update if different to avoid unnecessary updates
            if ($current_image_id !== $thumbnail_id) {
                update_field('event_image', $thumbnail_id, $post_id);
            }
        }
    } else {
        // If featured image was removed, also clear ACF field for course events
        if ($post->post_type === 'course_event' && function_exists('update_field')) {
            $current_event_image = get_field('event_image', $post_id);
            if (!empty($current_event_image)) {
                // Only clear if it was set (don't clear if it was already empty)
                update_field('event_image', '', $post_id);
            }
        }
    }
}
add_action('save_post', 'ccs_sync_featured_image_to_acf', 25);

/**
 * Preserve manually set featured images and sync to ACF
 * Runs after ACF saves to ensure featured image takes precedence
 */
function ccs_preserve_manual_featured_image($post_id) {
    // Only for course and course_event post types
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, ['course', 'course_event'])) {
        return;
    }
    
    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // Check if featured image was manually set via meta box
    if (isset($_POST['_thumbnail_id'])) {
        $thumbnail_id = intval($_POST['_thumbnail_id']);
        
        // If a valid image ID was set
        if ($thumbnail_id > 0) {
            // Ensure the featured image is set (WordPress should have done this, but ensure it)
            $current_thumbnail = get_post_thumbnail_id($post_id);
            if ($current_thumbnail != $thumbnail_id) {
                set_post_thumbnail($post_id, $thumbnail_id);
            }
            
            // Sync to ACF event_image field for course events
            if ($post->post_type === 'course_event' && function_exists('update_field')) {
                update_field('event_image', $thumbnail_id, $post_id);
            }
        } elseif ($thumbnail_id === -1) {
            // -1 means "remove featured image"
            delete_post_thumbnail($post_id);
            
            // Also clear ACF field for course events
            if ($post->post_type === 'course_event' && function_exists('update_field')) {
                update_field('event_image', '', $post_id);
            }
        }
    } else {
        // If _thumbnail_id wasn't in POST, check if featured image exists and sync to ACF
        // This handles cases where featured image was set but ACF field is empty
        if ($post->post_type === 'course_event' && function_exists('update_field')) {
            $thumbnail_id = get_post_thumbnail_id($post_id);
            if ($thumbnail_id) {
                $event_image = get_field('event_image', $post_id);
                $event_image_id = null;
                
                if (is_array($event_image) && isset($event_image['ID'])) {
                    $event_image_id = intval($event_image['ID']);
                } elseif (is_numeric($event_image)) {
                    $event_image_id = intval($event_image);
                }
                
                // If ACF field doesn't match featured image, sync it
                if ($event_image_id !== $thumbnail_id) {
                    update_field('event_image', $thumbnail_id, $post_id);
                }
            }
        }
    }
}
// Run after ACF saves (priority 20, ACF runs at 10)
add_action('save_post', 'ccs_preserve_manual_featured_image', 20);
add_action('acf/save_post', 'ccs_preserve_manual_featured_image', 20);

/**
 * Auto-set spaces_available to match total_spaces when creating new events
 * Ensures new events start with 0 bookings unless explicitly set
 */
function ccs_auto_set_event_spaces($post_id) {
    // Check if ACF is available
    if (!function_exists('get_field') || !function_exists('update_field')) {
        return;
    }
    
    // Only for course_event post type
    if (get_post_type($post_id) !== 'course_event') {
        return;
    }
    
    // Only on new posts (not updates)
    if (get_post_meta($post_id, '_ccs_spaces_initialized', true)) {
        return;
    }
    
    $total_spaces = get_field('total_spaces', $post_id);
    $spaces_available = get_field('spaces_available', $post_id);
    
    // If total_spaces is set but spaces_available is empty or 0, set it to match total_spaces
    if (!empty($total_spaces) && $total_spaces > 0) {
        if (empty($spaces_available) || $spaces_available === '') {
            update_field('spaces_available', $total_spaces, $post_id);
        }
    } elseif (empty($total_spaces) || $total_spaces <= 0) {
        // If total_spaces not set, set both to default 12
        update_field('total_spaces', 12, $post_id);
        if (empty($spaces_available) || $spaces_available === '') {
            update_field('spaces_available', 12, $post_id);
        }
    }
    
    // Mark as initialized to prevent re-running on updates
    update_post_meta($post_id, '_ccs_spaces_initialized', true);
}
add_action('acf/save_post', 'ccs_auto_set_event_spaces', 10);

// Event Status column is now handled in post-types.php
// This filter is kept for backward compatibility but shouldn't be needed

/**
 * Populate Event Status column
 */
function ccs_event_status_column_content($column, $post_id) {
    if ($column === 'event_status') {
        // Check if ACF is available
        if (!function_exists('get_field')) {
            echo '<span class="status-badge">N/A</span>';
            return;
        }
        
        $is_active = get_field('event_active', $post_id);
        $is_featured = get_field('event_featured', $post_id);
        
        if ($is_featured) {
            echo '<span class="status-badge status-badge-featured">Featured</span>';
        } else {
            $status_value = ($is_active || $is_active === null) ? 'active' : 'hidden';
            $status_text = $status_value === 'active' ? 'Active' : 'Hidden';
            $status_class = $status_value === 'active' ? 'status-badge-active' : 'status-badge-hidden';
            echo '<span class="status-badge cta-inline-edit cta-inline-status ' . esc_attr($status_class) . '" data-post-id="' . esc_attr($post_id) . '" data-value="' . esc_attr($status_value) . '" title="Double-click to edit">' . esc_html($status_text) . '</span>';
        }
    }
}
add_action('manage_course_event_posts_custom_column', 'ccs_event_status_column_content', 10, 2);

/**
 * Add Eventbrite sync status column to event list
 */
function ccs_add_eventbrite_status_column($columns) {
    $columns['eventbrite_status'] = 'Eventbrite';
    return $columns;
}
add_filter('manage_course_event_posts_columns', 'ccs_add_eventbrite_status_column');

function ccs_eventbrite_status_column_content($column, $post_id) {
    if ($column === 'eventbrite_status') {
        $eventbrite_id = get_post_meta($post_id, 'eventbrite_id', true);
        $eventbrite_url = get_post_meta($post_id, 'eventbrite_url', true);
        $last_sync = get_post_meta($post_id, '_eventbrite_last_sync', true);
        
        if ($eventbrite_id) {
            echo '<div style="line-height: 1.8;">';
            echo '<span style="color: #00a32a;">✓ Synced</span>';
            
            if ($eventbrite_url) {
                echo '<br><a href="' . esc_url($eventbrite_url) . '" target="_blank" class="button button-small" style="margin-top: 5px;">View on Eventbrite</a>';
            }
            
            if ($last_sync) {
                echo '<br><small style="color: #646970;">Last synced: ' . esc_html(human_time_diff(strtotime($last_sync), current_time('timestamp'))) . ' ago</small>';
            }
            echo '</div>';
        } else {
            echo '<span style="color: #8c8f94;">Not synced</span>';
        }
    }
}
add_action('manage_course_event_posts_custom_column', 'ccs_eventbrite_status_column_content', 10, 2);

/**
 * Eventbrite integration status notice (shown on the Session editor).
 *
 * Previously this was printed in `admin_footer`, which can appear visually clipped / "cut off"
 * at the bottom of the screen depending on admin layout and viewport.
 */
function ccs_eventbrite_integration_status_notice($post) {
    if (!$post || !isset($post->post_type) || $post->post_type !== 'course_event') {
        return;
    }

    $total_events = wp_count_posts('course_event');
    $total_count = intval($total_events->publish) + intval($total_events->draft) + intval($total_events->future);

    $synced_events = get_posts([
        'post_type' => 'course_event',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'meta_query' => [
            [
                'key' => 'eventbrite_id',
                'compare' => 'EXISTS',
            ],
        ],
        'fields' => 'ids',
    ]);

    $synced_count = count($synced_events);
    $not_synced = $total_count - $synced_count;

    echo '<div class="notice notice-info cta-eventbrite-status-notice">';
    echo '<p><strong>Eventbrite Integration Status</strong></p>';
    echo '<p><strong>Total Events:</strong> ' . esc_html($total_count) . '</p>';
    echo '<p><strong style="color: #00a32a;">Synced to Eventbrite:</strong> ' . esc_html($synced_count) . '</p>';
    echo '<p><strong style="color: #8c8f94;">Not Synced:</strong> ' . esc_html($not_synced) . '</p>';
    echo '</div>';
}
add_action('edit_form_after_title', 'ccs_eventbrite_integration_status_notice');

/**
 * Add manual booking tracking section in event editor
 */
function ccs_add_manual_bookings_field() {
    global $post;
    
    if ($post && $post->post_type === 'course_event') {
        $wordpress_bookings = intval(get_post_meta($post->ID, '_wordpress_bookings', true));
        $eventbrite_bookings = intval(get_post_meta($post->ID, '_eventbrite_bookings', true));
        $total_spaces = function_exists('get_field') ? intval(get_field('total_spaces', $post->ID)) : 0;
        $spaces_available = function_exists('get_field') ? intval(get_field('spaces_available', $post->ID)) : 0;
        
        ?>
        <div class="misc-pub-section" style="border-top: 1px solid #ddd; padding-top: 10px;">
            <h4 style="margin: 0 0 8px 0;">Booking Tracking</h4>
            <p style="margin: 4px 0;">
                <strong>WordPress Bookings:</strong> 
                <input type="number" 
                       id="wordpress-bookings-input" 
                       value="<?php echo esc_attr($wordpress_bookings); ?>" 
                       min="0" 
                       class="small-text"
                       style="width: 60px; margin-left: 5px;"
                       data-post-id="<?php echo esc_attr($post->ID); ?>">
                <button type="button" 
                        id="update-wordpress-bookings" 
                        class="button button-small" 
                        style="margin-left: 5px;">
                    Update
                </button>
            </p>
            <p style="margin: 4px 0;">
                <strong>Eventbrite Bookings:</strong> <?php echo esc_html($eventbrite_bookings); ?> (auto-synced)
            </p>
            <p style="margin: 4px 0;">
                <strong>Total Booked:</strong> <?php echo esc_html($wordpress_bookings + $eventbrite_bookings); ?> / <?php echo esc_html($total_spaces); ?>
            </p>
            <p style="margin: 4px 0;">
                <strong>Spaces Available:</strong> <?php echo esc_html($spaces_available); ?>
            </p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#update-wordpress-bookings').on('click', function() {
                var postId = $('#wordpress-bookings-input').data('post-id');
                var bookings = parseInt($('#wordpress-bookings-input').val()) || 0;
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ccs_update_wordpress_bookings',
                        post_id: postId,
                        bookings: bookings,
                        nonce: '<?php echo wp_create_nonce('ccs_update_bookings'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('post_submitbox_misc_actions', 'ccs_add_manual_bookings_field');

/**
 * AJAX handler for manual booking adjustment
 */
function ccs_ajax_update_wordpress_bookings() {
    check_ajax_referer('ccs_update_bookings', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $bookings = isset($_POST['bookings']) ? intval($_POST['bookings']) : 0;
    
    if ($post_id <= 0) {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    if ($bookings < 0) {
        $bookings = 0;
    }
    
    update_post_meta($post_id, '_wordpress_bookings', $bookings);
    if (function_exists('ccs_recalculate_spaces_available')) {
        ccs_recalculate_spaces_available($post_id);
    }
    
    wp_send_json_success(['message' => 'Bookings updated']);
}
add_action('wp_ajax_ccs_update_wordpress_bookings', 'ccs_ajax_update_wordpress_bookings');


/**
 * AJAX handler for inline field editing
 */
function ccs_save_inline_field() {
    check_ajax_referer('ccs_inline_edit', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
    $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
    
    if (!$post_id || !$field) {
        wp_send_json_error(['message' => 'Invalid request']);
    }
    
    // Verify post exists and user can edit it
    $post = get_post($post_id);
    if (!$post || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => 'Cannot edit this post']);
    }
    
    $success = false;
    
    switch ($field) {
        case 'course_price':
            $price = floatval($value);
            if ($price >= 0) {
                update_field('course_price', $price, $post_id);
                $success = true;
            }
            break;
            
        case 'course_duration':
            update_field('course_duration', $value, $post_id);
            $success = true;
            break;
            
        case 'event_time':
            $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';
            $end_time = isset($_POST['end_time']) ? sanitize_text_field($_POST['end_time']) : '';
            if ($start_time) {
                update_field('start_time', $start_time, $post_id);
            }
            if ($end_time) {
                update_field('end_time', $end_time, $post_id);
            }
            $success = true;
            break;
            
        case 'spaces_available':
            $spaces = intval($value);
            if ($spaces >= 0) {
                update_field('spaces_available', $spaces, $post_id);
                $success = true;
            }
            break;
            
        case 'event_active':
            $is_active = $value === '1' || $value === 'true';
            update_field('event_active', $is_active ? 1 : 0, $post_id);
            $success = true;
            break;
    }
    
    if ($success) {
        wp_send_json_success(['message' => 'Field updated successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to update field']);
    }
}
add_action('wp_ajax_ccs_save_inline_field', 'ccs_save_inline_field');

/**
 * Improve post editor UX with helpful styling and guidance
 */
function ccs_improve_post_editor_ux() {
    global $post_type;
    
    if ($post_type !== 'post' && $post_type !== 'faq') {
        return;
    }
    ?>
    <style>
        /* Make editor help text more visible */
        .cta-editor-help {
            background: #f0f6fc;
            border-left: 4px solid #2271b1;
            padding: 12px 16px;
            margin: 10px 0 15px 0;
            border-radius: 4px;
        }
        
        /* Improve ACF field descriptions */
        .acf-field .acf-label label {
            font-weight: 600;
            color: #1d2327;
        }
        
        .acf-field .acf-label .description {
            font-size: 13px;
            color: #646970;
            margin-top: 4px;
            line-height: 1.5;
        }
        
        /* Make "Article Content" section more prominent */
        .acf-field-group[data-key="group_news_article_content"] {
            border-top: 2px solid #2271b1;
        }
        
        .acf-field-group[data-key="group_news_article_content"] .acf-label {
            font-size: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 16px;
        }
        
        /* Improve section repeater UI */
        .acf-repeater .acf-row {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            margin-bottom: 16px;
            background: #fff;
        }
        
        .acf-repeater .acf-row-handle {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        /* Better spacing in editor */
        #post-body-content {
            padding-top: 0;
        }
        
        /* Hide main content editor if using ACF sections (optional - can be removed if needed) */
        /* .post-type-post #content_ifr { display: none; } */
    </style>
    <?php
}
add_action('admin_head-post.php', 'ccs_improve_post_editor_ux');
add_action('admin_head-post-new.php', 'ccs_improve_post_editor_ux');

/**
 * Improve FAQ page editor UX with similar styling to blog posts
 */
function ccs_improve_faq_editor_ux() {
    global $post, $post_type;
    
    if (!$post) {
        return;
    }
    
    // Apply to FAQ post type OR pages using FAQ template
    $template = get_page_template_slug($post->ID);
    $is_faq_page = ($template === 'page-templates/page-faqs.php' || 
                    $template === 'page-templates/page-cqc-hub.php' ||
                    strpos($template, 'faq') !== false);
    
    // Check if this is FAQ post type or FAQ page template
    if ($post_type !== 'faq' && (!$is_faq_page || get_post_type() !== 'page')) {
        return;
    }
    
    // Only apply if ACF is available
    if (!function_exists('get_field')) {
        return;
    }
    
    ?>
    <style>
        /* Improve ACF field descriptions */
        .acf-field .acf-label label {
            font-weight: 600;
            color: #1d2327;
        }
        
        .acf-field .acf-label .description {
            font-size: 13px;
            color: #646970;
            margin-top: 4px;
            line-height: 1.5;
        }
        
        /* Make FAQ sections more prominent */
        .acf-field-group[data-key="group_resources_faqs_page"],
        .acf-field-group[data-key="group_resources_cqc_hub"],
        .acf-field-group[data-key="group_faq_content"] {
            border-top: 2px solid #2271b1;
        }
        
        .acf-field-group[data-key="group_resources_faqs_page"] .acf-label,
        .acf-field-group[data-key="group_resources_cqc_hub"] .acf-label,
        .acf-field-group[data-key="group_faq_content"] .acf-label {
            font-size: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 16px;
        }
        
        /* Style FAQ Answer field for FAQ post type */
        .acf-field[data-name="faq_answer"] {
            margin-top: 16px;
        }
        
        .acf-field[data-name="faq_answer"] .acf-label {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .acf-field[data-name="faq_answer"] .wp-editor-container {
            border: 1px solid #d1d5db;
            border-radius: 4px;
        }
        
        /* Improve FAQ repeater UI - similar to blog sections */
        .acf-repeater[data-name="faqs"] .acf-row,
        .acf-repeater[data-name="faqs"] .acf-row {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            margin-bottom: 16px;
            background: #fff;
            padding: 16px;
        }
        
        .acf-repeater[data-name="faqs"] .acf-row-handle {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 16px;
        }
        
        /* Better styling for FAQ question field */
        .acf-repeater[data-name="faqs"] .acf-field[data-name="question"] {
            margin-bottom: 16px;
        }
        
        .acf-repeater[data-name="faqs"] .acf-field[data-name="question"] .acf-label {
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Better styling for FAQ answer WYSIWYG */
        .acf-repeater[data-name="faqs"] .acf-field[data-name="answer"] {
            margin-top: 16px;
        }
        
        .acf-repeater[data-name="faqs"] .acf-field[data-name="answer"] .acf-label {
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Improve WYSIWYG editor appearance */
        .acf-repeater[data-name="faqs"] .acf-field[data-name="answer"] .wp-editor-container {
            border: 1px solid #d1d5db;
            border-radius: 4px;
        }
        
        /* Better spacing in editor */
        #post-body-content {
            padding-top: 0;
        }
        
        /* Category field styling */
        .acf-repeater[data-name="faqs"] .acf-field[data-name="category"] {
            margin-bottom: 12px;
        }
    </style>
    <?php
}
add_action('admin_head-post.php', 'ccs_improve_faq_editor_ux');
add_action('admin_head-post-new.php', 'ccs_improve_faq_editor_ux');

/**
 * Enqueue admin media library scripts
 */
function ccs_enqueue_admin_media_library() {
    global $pagenow, $typenow;
    
    // On course and course_event list pages AND edit pages
    if (!in_array($typenow, ['course', 'course_event'])) {
        return;
    }
    
    if (!in_array($pagenow, ['edit.php', 'post.php', 'post-new.php'])) {
        return;
    }
    
    // Enqueue WordPress media scripts
    wp_enqueue_media();
    
    // Enqueue our custom script
    wp_enqueue_script(
        'cta-admin-media-library',
        CCS_THEME_URI . '/assets/js/admin-media-library.js',
        ['jquery'],
        CCS_THEME_VERSION,
        true
    );
    
    // Localize script with nonce
    wp_localize_script('cta-admin-media-library', 'ctaAdminMediaLibrary', [
        'nonce' => wp_create_nonce('ccs_save_image'),
    ]);
    
    // Add inline styles for clickable images
    wp_add_inline_style('wp-admin', '
        .cta-change-image {
            transition: opacity 0.2s ease;
        }
        .cta-change-image:hover {
            opacity: 0.8;
            box-shadow: 0 0 0 2px #2271b1;
        }
        .cta-admin-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #dcdcde;
            display: block;
        }
        .cta-admin-thumbnail.cta-change-image {
            border: 2px solid transparent;
            border-radius: 2px;
        }
        .cta-admin-thumbnail.cta-change-image:hover {
            border-color: #2271b1;
        }
        .cta-admin-missing-badge.cta-change-image {
            color: #2271b1;
            text-decoration: underline;
        }
        .cta-admin-missing-badge.cta-change-image:hover {
            color: #135e96;
        }
        .wp-list-table .column-ccs_thumbnail,
        .wp-list-table .column-event_thumbnail {
            width: 70px;
            text-align: center;
        }
    ');
}
add_action('admin_enqueue_scripts', 'ccs_enqueue_admin_media_library');

/**
 * Enqueue review picker scripts and styles for course editor
 */
function ccs_enqueue_review_picker($hook) {
    // Only load on course edit pages
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    
    global $post;
    if (!$post || $post->post_type !== 'course') {
        return;
    }
    
    // Enqueue CSS
    wp_enqueue_style(
        'cta-admin-review-picker',
        get_template_directory_uri() . '/assets/css/admin-review-picker.css',
        [],
        CCS_THEME_VERSION
    );
    
    // Enqueue JavaScript
    wp_enqueue_script(
        'cta-admin-review-picker',
        get_template_directory_uri() . '/assets/js/admin-review-picker.js',
        ['jquery'],
        CCS_THEME_VERSION,
        true
    );
    
    // Localize script with reviews data
    $all_reviews = get_option('ccs_all_reviews', []);
    wp_localize_script(
        'cta-admin-review-picker',
        'ctaAllReviews',
        $all_reviews
    );
}
add_action('admin_enqueue_scripts', 'ccs_enqueue_review_picker');

/**
 * Enqueue admin CSS for improved admin interface styling
 */
function ccs_enqueue_admin_styles($hook) {
    wp_enqueue_style(
        'cta-admin-styles',
        get_template_directory_uri() . '/assets/css/admin.css',
        [],
        CCS_THEME_VERSION
    );
}
add_action('admin_enqueue_scripts', 'ccs_enqueue_admin_styles');

/**
 * AJAX handler to save image for course or course event
 */
function ccs_save_image_ajax() {
    check_ajax_referer('ccs_save_image', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
    
    if (!$post_id || !$post_type || !$image_id) {
        wp_send_json_error(['message' => 'Invalid request']);
    }
    
    // Verify post exists and user can edit it
    $post = get_post($post_id);
    if (!$post || $post->post_type !== $post_type || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => 'Cannot edit this post']);
    }
    
    // Verify image exists
    $image = get_post($image_id);
    if (!$image || $image->post_type !== 'attachment' || !wp_attachment_is_image($image_id)) {
        wp_send_json_error(['message' => 'Invalid image']);
    }
    
    $success = false;
    
    if ($post_type === 'course') {
        // Set as featured image (thumbnail)
        $result = set_post_thumbnail($post_id, $image_id);
        $success = $result !== false;
    } elseif ($post_type === 'course_event') {
        // Set as featured image (thumbnail) - now supported for course events
        $result = set_post_thumbnail($post_id, $image_id);
        $success = $result !== false;
        
        // Also sync to ACF event_image field for backward compatibility
        if ($success && function_exists('update_field')) {
            update_field('event_image', $image_id, $post_id);
        }
    }
    
    if ($success) {
        wp_send_json_success(['message' => 'Image updated successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to update image']);
    }
}
add_action('wp_ajax_ccs_save_image', 'ccs_save_image_ajax');
