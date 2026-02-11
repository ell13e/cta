<?php
/**
 * Modern Event Management UI for Scheduled Courses
 * Card-based layout inspired by modern event management platforms
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Enqueue styles and scripts for event management UI
 */
function ccs_event_management_enqueue_assets($hook) {
    global $typenow;
    
    if ($typenow === 'course_event' && $hook === 'edit.php') {
        wp_add_inline_style('wp-admin', '
            /* Event Management UI - Uses WordPress standard classes where possible */
            /* Custom styles only for unique card grid layout */
            
            /* Search input with icon - minimal custom styling */
            .cta-events-search input.regular-text {
                padding-left: 40px;
            }
            
            /* Events Grid */
            .cta-events-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 24px;
                margin-bottom: 32px;
            }
            
            /* Event Card */
            .cta-event-card {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                overflow: hidden;
                transition: all 0.2s;
                cursor: pointer;
            }
            
            .cta-event-card:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                transform: translateY(-2px);
            }
            
            .cta-event-card-image {
                width: 100%;
                height: 180px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                position: relative;
                overflow: hidden;
            }
            
            .cta-event-card-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .cta-event-card-category {
                position: absolute;
                top: 12px;
                left: 12px;
                padding: 4px 12px;
                background: rgba(255, 255, 255, 0.9);
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                color: #2271b1;
            }
            
            .cta-event-card-content {
                padding: 20px;
            }
            
            .cta-event-card-title {
                font-size: 18px;
                font-weight: 600;
                color: #1d2327;
                margin: 0 0 4px 0;
                line-height: 1.4;
            }
            
            .cta-event-card-date {
                font-size: 14px;
                color: #646970;
                margin: 0 0 8px 0;
                font-weight: 500;
            }
            
            .cta-event-card-description {
                font-size: 14px;
                color: #6b7280;
                line-height: 1.5;
                margin-bottom: 16px;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            
            .cta-event-card-meta {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
                color: #6b7280;
                margin-bottom: 16px;
            }
            
            .cta-event-card-meta-icon {
                color: #9ca3af;
                font-size: 16px;
            }
            
            .cta-event-card-progress {
                margin-bottom: 16px;
            }
            
            .cta-event-card-progress-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                font-size: 12px;
            }
            
            .cta-event-card-progress-label {
                color: #6b7280;
                font-weight: 500;
            }
            
            .cta-event-card-progress-percent {
                color: #2271b1;
                font-weight: 600;
            }
            
            .cta-event-card-progress-bar {
                width: 100%;
                height: 8px;
                background: #e5e7eb;
                border-radius: 4px;
                overflow: hidden;
            }
            
            .cta-event-card-progress-fill {
                height: 100%;
                background: #2271b1;
                border-radius: 4px;
                transition: width 0.3s;
            }
            
            .cta-event-card-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-top: 16px;
                border-top: 1px solid #f3f4f6;
            }
            
            .cta-event-card-spaces {
                font-size: 13px;
                color: #6b7280;
            }
            
            .cta-event-card-spaces.low {
                color: #dc2626;
                font-weight: 600;
            }
            
            .cta-event-card-price {
                font-size: 16px;
                font-weight: 700;
                color: #1d2327;
            }
            
            /* Empty State */
            .cta-events-empty {
                text-align: center;
                padding: 60px 20px;
                background: #fff;
                border-radius: 12px;
                border: 1px solid #e5e7eb;
            }
            
            .cta-events-empty-icon {
                font-size: 48px;
                color: #d1d5db;
                margin-bottom: 16px;
            }
            
            .cta-events-empty-title {
                font-size: 18px;
                font-weight: 600;
                color: #1d2327;
                margin: 0 0 8px 0;
            }
            
            .cta-events-empty-text {
                font-size: 14px;
                color: #6b7280;
                margin: 0 0 24px 0;
            }
            
            /* Pagination uses WordPress standard .tablenav-pages class */
            
            @media (max-width: 1024px) {
                .cta-events-grid {
                    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                }
            }
            
            @media (max-width: 768px) {
                .cta-events-grid {
                    grid-template-columns: 1fr;
                }
                
                .cta-events-search {
                    width: 100%;
                    max-width: 100%;
                }
            }
        ');
    }
}
add_action('admin_enqueue_scripts', 'ccs_event_management_enqueue_assets');

/**
 * Override the default list table with modern card view
 */
function ccs_event_management_override_list_table() {
    global $typenow;
    
    if ($typenow === 'course_event') {
        // Get filter status
        $status_filter = isset($_GET['event_status']) ? sanitize_text_field($_GET['event_status']) : 'active';
        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $category_filter = isset($_GET['event_category']) ? sanitize_text_field($_GET['event_category']) : '';
        
        // Count events by status
        $active_count = ccs_count_events_by_status('active');
        $draft_count = ccs_count_events_by_status('draft');
        $past_count = ccs_count_events_by_status('past');
        
        // Query events
        $args = [
            'post_type' => 'course_event',
            'posts_per_page' => 20,
            'paged' => isset($_GET['paged']) ? intval($_GET['paged']) : 1,
        ];
        
        if ($search_query) {
            $args['s'] = $search_query;
        }
        
        // Filter by category
        if ($category_filter) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'course_category',
                    'field' => 'slug',
                    'terms' => $category_filter,
                ],
            ];
        }
        
        // Filter by status
        if ($status_filter === 'active') {
            $args['meta_query'] = [
                [
                    'key' => 'event_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
            ];
            $args['post_status'] = 'publish';
            $args['meta_key'] = 'event_date';
            $args['orderby'] = 'meta_value';
            $args['meta_type'] = 'DATE';
            $args['order'] = 'ASC';
        } elseif ($status_filter === 'past') {
            $args['meta_query'] = [
                [
                    'key' => 'event_date',
                    'value' => date('Y-m-d'),
                    'compare' => '<',
                    'type' => 'DATE',
                ],
            ];
            $args['meta_key'] = 'event_date';
            $args['orderby'] = 'meta_value';
            $args['meta_type'] = 'DATE';
            $args['order'] = 'DESC';
        } elseif ($status_filter === 'draft') {
            $args['post_status'] = 'draft';
            $args['meta_key'] = 'event_date';
            $args['orderby'] = 'meta_value';
            $args['meta_type'] = 'DATE';
            $args['order'] = 'ASC';
        } else {
            // Default: sort by date ascending
            $args['meta_key'] = 'event_date';
            $args['orderby'] = 'meta_value';
            $args['meta_type'] = 'DATE';
            $args['order'] = 'ASC';
        }
        
        $events_query = new WP_Query($args);
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Scheduled Courses</h1>
            <a href="<?php echo admin_url('post-new.php?post_type=course_event'); ?>" class="page-title-action">
                <span class="dashicons dashicons-plus-alt" style="vertical-align: middle; margin-right: 4px;"></span>
                Add New Session
            </a>
            <p class="description" style="margin-top: 8px;">Manage your training sessions and events</p>
            
            <div class="cta-events-header-right" style="margin: 20px 0;">
                <div class="cta-events-search" style="position: relative; max-width: 400px;">
                    <span class="dashicons dashicons-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #646970; pointer-events: none;"></span>
                    <form method="get" style="display: inline; width: 100%;">
                        <input type="hidden" name="post_type" value="course_event">
                        <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="Search event, location, etc" class="regular-text" style="padding-left: 40px;">
                    </form>
                </div>
            </div>
            
            <!-- Status Filter Pills -->
            <ul class="subsubsub" style="margin: 20px 0;">
                <li>
                    <a href="<?php echo esc_url(add_query_arg(['event_status' => 'active', 's' => $search_query])); ?>" 
                       class="<?php echo $status_filter === 'active' ? 'current' : ''; ?>">
                        Active <span class="count">(<?php echo $active_count; ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg(['event_status' => 'draft', 's' => $search_query])); ?>" 
                       class="<?php echo $status_filter === 'draft' ? 'current' : ''; ?>">
                        Draft <span class="count">(<?php echo $draft_count; ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg(['event_status' => 'past', 's' => $search_query])); ?>" 
                       class="<?php echo $status_filter === 'past' ? 'current' : ''; ?>">
                        Past <span class="count">(<?php echo $past_count; ?>)</span>
                    </a>
                </li>
            </ul>
            
            <!-- Filter Bar -->
            <div style="margin: 20px 0;">
                <form method="get" style="display: inline;">
                    <input type="hidden" name="post_type" value="course_event">
                    <input type="hidden" name="event_status" value="<?php echo esc_attr($status_filter); ?>">
                    <?php if ($search_query) : ?>
                        <input type="hidden" name="s" value="<?php echo esc_attr($search_query); ?>">
                    <?php endif; ?>
                    <select name="event_category" class="postform" onchange="this.form.submit();">
                        <option value="">All Categories</option>
                        <?php
                        $categories = get_terms(['taxonomy' => 'course_category', 'hide_empty' => false]);
                        foreach ($categories as $category) {
                            $selected = $category_filter === $category->slug ? 'selected' : '';
                            echo '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                </form>
            </div>
            
            <?php if ($events_query->have_posts()) : ?>
                <div class="cta-events-grid">
                    <?php while ($events_query->have_posts()) : $events_query->the_post();
                        $post_id = get_the_ID();
                        $course = get_field('linked_course', $post_id);
                        $event_date = get_field('event_date', $post_id);
                        $start_time = get_field('start_time', $post_id);
                        $end_time = get_field('end_time', $post_id);
                        $spaces = get_field('spaces_available', $post_id);
                        $venue = get_field('event_location', $post_id);
                        $price = get_field('event_price', $post_id);
                        
                        // Get event image - check featured image first, then ACF field, then course image
                        $image_url = '';
                        $image_id = get_post_thumbnail_id($post_id);
                        
                        // If event has featured image, use it
                        if ($image_id) {
                            $image_url = wp_get_attachment_image_url($image_id, 'medium');
                        } else {
                            // Check ACF event_image field
                            $event_image = get_field('event_image', $post_id);
                            if ($event_image) {
                                if (is_array($event_image) && isset($event_image['sizes']['medium'])) {
                                    $image_url = $event_image['sizes']['medium'];
                                } elseif (is_array($event_image) && isset($event_image['url'])) {
                                    $image_url = $event_image['url'];
                                } elseif (is_numeric($event_image)) {
                                    $image_url = wp_get_attachment_image_url(intval($event_image), 'medium');
                                }
                            }
                            
                            // Fallback to course image
                            if (empty($image_url) && $course) {
                                $course_image_id = get_post_thumbnail_id($course->ID);
                                if ($course_image_id) {
                                    $image_url = wp_get_attachment_image_url($course_image_id, 'medium');
                                }
                            }
                        }
                        
                        // Get category from linked course, not event post
                        $category_name = 'Training'; // Default fallback
                        if ($course) {
                            $course_categories = get_the_terms($course->ID, 'course_category');
                            if ($course_categories && !is_wp_error($course_categories)) {
                                $category_name = $course_categories[0]->name;
                            }
                        } else {
                            // Fallback to event post categories if no linked course
                            $categories = get_the_terms($post_id, 'course_category');
                            if ($categories && !is_wp_error($categories)) {
                                $category_name = $categories[0]->name;
                            }
                        }
                        
                        // Calculate spaces booked accurately
                        // If total_spaces is set, use it; otherwise use spaces_available as max (backward compatibility)
                        $total_spaces = get_field('total_spaces', $post_id);
                        if (empty($total_spaces) || $total_spaces <= 0) {
                            // If total_spaces not set, assume spaces_available represents the full capacity (no bookings yet)
                            $total_spaces = $spaces !== '' && intval($spaces) > 0 ? intval($spaces) : 12;
                        } else {
                            $total_spaces = intval($total_spaces);
                        }
                        
                        $spaces_available_int = intval($spaces);
                        // Spaces booked = total - available
                        $spaces_booked = max(0, $total_spaces - $spaces_available_int);
                        $spaces_left = $spaces_available_int;
                        
                        // Calculate progress percentage
                        $progress = $total_spaces > 0 ? ($spaces_booked / $total_spaces) * 100 : 0;
                        
                        // Format date
                        $formatted_date = $event_date ? date('j M Y', strtotime($event_date)) : '';
                        $formatted_time = '';
                        if ($start_time && $end_time) {
                            $formatted_time = date('g:i A', strtotime($start_time)) . ' – ' . date('g:i A', strtotime($end_time));
                        } elseif ($start_time) {
                            $formatted_time = date('g:i A', strtotime($start_time));
                        }
                        
                        // Extract title and remove date if it matches the formatted_date shown below
                        $title = get_the_title();
                        $clean_title = $title;
                        
                        // If we have a formatted date, remove it from the title (since it's shown separately on the line below)
                        if ($formatted_date && $event_date) {
                            // Generate all possible date format variations that might appear in title
                            $date_variations = [
                                $formatted_date, // "7 Jan 2026" (exact match - this is what's shown below)
                                date('d M Y', strtotime($event_date)), // "07 Jan 2026"
                                date('j/n/Y', strtotime($event_date)), // "7/1/2026"
                                date('d/m/Y', strtotime($event_date)), // "07/01/2026"
                                date('j-m-Y', strtotime($event_date)), // "7-01-2026"
                                date('d-m-Y', strtotime($event_date)), // "07-01-2026"
                            ];
                            
                            foreach ($date_variations as $date_var) {
                                // Remove date if it appears at the end with dash separator: "Title - 7 Jan 2026"
                                $clean_title = preg_replace('/\s*-\s*' . preg_quote($date_var, '/') . '\s*$/i', '', $clean_title);
                                // Remove date if it appears at the end with other separators: "Title | 7 Jan 2026" or "Title : 7 Jan 2026"
                                $clean_title = preg_replace('/\s*[|\-:]\s*' . preg_quote($date_var, '/') . '\s*$/i', '', $clean_title);
                                // Remove date if it appears at the end with just spaces: "Title 7 Jan 2026"
                                $clean_title = preg_replace('/\s+' . preg_quote($date_var, '/') . '\s*$/i', '', $clean_title);
                            }
                            $clean_title = trim($clean_title);
                            
                            // Fallback: If title still contains the formatted date anywhere, try to remove it
                            if (stripos($clean_title, $formatted_date) !== false) {
                                $clean_title = str_ireplace($formatted_date, '', $clean_title);
                                $clean_title = preg_replace('/\s*[|\-:\s]+\s*$/', '', $clean_title); // Remove trailing separators
                                $clean_title = trim($clean_title);
                            }
                        }
                        
                        // Get location - default to "The Maidstone Studios" instead of TBA
                        $display_location = $venue ?: 'The Maidstone Studios';
                    ?>
                        <div class="cta-event-card" onclick="window.location.href='<?php echo get_edit_post_link($post_id); ?>'">
                            <div class="cta-event-card-image" style="background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);">
                                <?php if ($image_url) : ?>
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($clean_title); ?>">
                                <?php endif; ?>
                                <span class="cta-event-card-category"><?php echo esc_html($category_name); ?></span>
                            </div>
                            <div class="cta-event-card-content">
                                <h3 class="cta-event-card-title"><?php echo esc_html($clean_title); ?></h3>
                                <?php if ($formatted_date) : ?>
                                <div class="cta-event-card-date"><?php echo esc_html($formatted_date); ?></div>
                                <?php endif; ?>
                                <?php if ($course) : ?>
                                <p class="cta-event-card-description"><?php echo esc_html(wp_trim_words($course->post_excerpt ?: $course->post_content, 20)); ?></p>
                                <?php endif; ?>
                                <div class="cta-event-card-meta">
                                    <span class="dashicons dashicons-location cta-event-card-meta-icon"></span>
                                    <span><?php echo esc_html($display_location); ?></span>
                                    <?php if ($formatted_time) : ?>
                                        <span style="margin: 0 4px;">–</span>
                                        <span><?php echo esc_html($formatted_time); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($spaces !== '' && $total_spaces > 0) : ?>
                                <div class="cta-event-card-progress">
                                    <div class="cta-event-card-progress-header">
                                        <span class="cta-event-card-progress-label">Spaces Booked: <?php echo esc_html($spaces_booked); ?> / <?php echo esc_html($total_spaces); ?></span>
                                        <span class="cta-event-card-progress-percent"><?php echo round($progress); ?>%</span>
                                    </div>
                                    <div class="cta-event-card-progress-bar">
                                        <div class="cta-event-card-progress-fill" style="width: <?php echo esc_attr($progress); ?>%"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="cta-event-card-footer">
                                    <span class="cta-event-card-spaces <?php echo $spaces_left <= 3 ? 'low' : ''; ?>">
                                        <?php echo $spaces !== '' ? esc_html($spaces_left) . ' Spaces Left' : 'Unlimited'; ?>
                                    </span>
                                    <?php if ($price) : ?>
                                    <span class="cta-event-card-price">£<?php echo esc_html($price); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination - WordPress Standard -->
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
                        $total_pages = $events_query->max_num_pages;
                        $total_items = $events_query->found_posts;
                        $per_page = $events_query->get('posts_per_page');
                        ?>
                        <span class="displaying-num"><?php echo sprintf(_n('%s item', '%s items', $total_items), number_format_i18n($total_items)); ?></span>
                        <?php if ($total_pages > 1) : ?>
                        <span class="pagination-links">
                            <?php if ($paged > 1) : ?>
                            <a class="prev-page button" href="<?php echo esc_url(add_query_arg(['paged' => $paged - 1])); ?>">
                                <span class="screen-reader-text">Previous page</span>
                                <span aria-hidden="true">‹</span>
                            </a>
                            <?php else : ?>
                            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                            <?php endif; ?>
                            
                            <span class="paging-input">
                                <label for="current-page-selector" class="screen-reader-text">Current page</label>
                                <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo esc_attr($paged); ?>" size="2" aria-describedby="table-paging">
                                <span class="tablenav-paging-text"> of <span class="total-pages"><?php echo number_format_i18n($total_pages); ?></span></span>
                            </span>
                            
                            <?php if ($paged < $total_pages) : ?>
                            <a class="next-page button" href="<?php echo esc_url(add_query_arg(['paged' => $paged + 1])); ?>">
                                <span class="screen-reader-text">Next page</span>
                                <span aria-hidden="true">›</span>
                            </a>
                            <?php else : ?>
                            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="notice notice-info" style="padding: 20px; text-align: center;">
                    <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: 600;">No events found</p>
                    <p style="margin: 0 0 15px 0;">Get started by creating your first scheduled course session.</p>
                    <a href="<?php echo admin_url('post-new.php?post_type=course_event'); ?>" class="button button-primary">
                        Add New Session
                    </a>
                </div>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Auto-submit search on enter
            $('.cta-events-search input').on('keypress', function(e) {
                if (e.which === 13) {
                    $(this).closest('form').submit();
                }
            });
        });
        </script>
        <?php
        
        // Prevent default WordPress list table from showing
        add_filter('views_edit-course_event', '__return_empty_array');
        return true;
    }
    
    return false;
}

/**
 * Count events by status
 */
function ccs_count_events_by_status($status) {
    $args = [
        'post_type' => 'course_event',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];
    
    if ($status === 'active') {
        $args['meta_query'] = [
            [
                'key' => 'event_date',
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE',
            ],
        ];
        $args['post_status'] = 'publish';
    } elseif ($status === 'past') {
        $args['meta_query'] = [
            [
                'key' => 'event_date',
                'value' => date('Y-m-d'),
                'compare' => '<',
                'type' => 'DATE',
            ],
        ];
    } elseif ($status === 'draft') {
        $args['post_status'] = 'draft';
    }
    
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Hide default table and show card view
 */
function ccs_event_management_show_card_view() {
    global $typenow, $pagenow;
    
    if ($typenow === 'course_event' && $pagenow === 'edit.php') {
        // Hide default WordPress list table with CSS
        add_action('admin_head', function() {
            ?>
            <style>
                body.edit-php.post-type-course_event .wp-list-table, 
                body.edit-php.post-type-course_event .tablenav.top, 
                body.edit-php.post-type-course_event .tablenav.bottom,
                body.edit-php.post-type-course_event .subsubsub,
                body.edit-php.post-type-course_event .wrap > h1.wp-heading-inline:first-of-type,
                body.edit-php.post-type-course_event .wrap > .page-title-action:first-of-type { 
                    display: none !important; 
                }
            </style>
            <?php
        }, 999);
        
        // Render card view output
        add_action('admin_notices', function() {
            ccs_event_management_override_list_table();
        }, 1);
    }
}
add_action('load-edit.php', 'ccs_event_management_show_card_view');

