<?php
/**
 * SEO Admin Section
 * 
 * Consolidated admin interface for all SEO features
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Add SEO admin menu
 */
function cta_add_seo_admin_menu() {
    // Main SEO menu
    add_menu_page(
        'SEO',
        'SEO',
        'manage_options',
        'cta-seo',
        'cta_seo_dashboard_page',
        'dashicons-search',
        30
    );
    
    // Dashboard/Overview (first submenu item, replaces main menu)
    add_submenu_page(
        'cta-seo',
        'SEO Dashboard',
        'Dashboard',
        'manage_options',
        'cta-seo',
        'cta_seo_dashboard_page'
    );
    
    // Settings
    add_submenu_page(
        'cta-seo',
        'SEO Settings',
        'Settings',
        'manage_options',
        'cta-seo-settings',
        'cta_seo_settings_page'
    );
    
    // Bulk Optimization
    add_submenu_page(
        'cta-seo',
        'Bulk Optimization',
        'Bulk Optimization',
        'manage_options',
        'cta-seo-bulk',
        'cta_seo_bulk_page'
    );
    
    // Redirects
    add_submenu_page(
        'cta-seo',
        'Redirects',
        'Redirects',
        'manage_options',
        'cta-seo-redirects',
        'cta_seo_redirects_page'
    );
    
    // Sitemap
    add_submenu_page(
        'cta-seo',
        'Sitemap',
        'Sitemap',
        'manage_options',
        'cta-seo-sitemap',
        'cta_seo_sitemap_page'
    );
    
    // Schema
    add_submenu_page(
        'cta-seo',
        'Schema Markup',
        'Schema',
        'manage_options',
        'cta-seo-schema',
        'cta_seo_schema_page'
    );
    
    // Image SEO
    add_submenu_page(
        'cta-seo',
        'Image SEO',
        'Image SEO',
        'manage_options',
        'cta-seo-images',
        'cta_seo_images_page'
    );
    
    // Verification
    add_submenu_page(
        'cta-seo',
        'SEO Verification',
        'Verification',
        'manage_options',
        'cta-seo-verification',
        'cta_seo_admin_verification_page'
    );
}
add_action('admin_menu', 'cta_add_seo_admin_menu', 20);

/**
 * SEO Dashboard Page
 */
function cta_seo_dashboard_page() {
    // Get stats
    $pages_count = wp_count_posts('page')->publish;
    $posts_count = wp_count_posts('post')->publish;
    $courses_count = wp_count_posts('course')->publish ?? 0;
    $events_count = wp_count_posts('course_event')->publish ?? 0;
    
    // Count redirects
    global $wpdb;
    $redirects_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cta_redirects");
    
    // Count images without alt text
    $images_without_alt = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
        WHERE p.post_type = 'attachment' 
        AND p.post_mime_type LIKE 'image/%'
        AND (pm.meta_value IS NULL OR pm.meta_value = '')
    ");
    
    // Get sitemap URL
    $sitemap_url = home_url('/wp-sitemap.xml');
    
    ?>
    <div class="wrap">
        <h1>SEO Dashboard</h1>
        
        <div class="cta-seo-dashboard">
            <div class="cta-seo-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                
                <!-- Content Stats -->
                <div class="card">
                    <h2>Content Overview</h2>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                            <strong>Pages:</strong> <?php echo number_format($pages_count); ?>
                        </li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                            <strong>Posts:</strong> <?php echo number_format($posts_count); ?>
                        </li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                            <strong>Courses:</strong> <?php echo number_format($courses_count); ?>
                        </li>
                        <li style="padding: 8px 0;">
                            <strong>Events:</strong> <?php echo number_format($events_count); ?>
                        </li>
                    </ul>
                </div>
                
                <!-- Redirects Stats -->
                <div class="card">
                    <h2>Redirects</h2>
                    <p style="font-size: 32px; margin: 10px 0; font-weight: bold;">
                        <?php echo number_format($redirects_count); ?>
                    </p>
                    <p style="margin: 0;">
                        <a href="<?php echo admin_url('admin.php?page=cta-seo-redirects'); ?>" class="button">
                            Manage Redirects
                        </a>
                    </p>
                </div>
                
                <!-- Image SEO Stats -->
                <div class="card">
                    <h2>Image SEO</h2>
                    <?php if ($images_without_alt > 0): ?>
                        <p style="color: #d63638; font-size: 32px; margin: 10px 0; font-weight: bold;">
                            <?php echo number_format($images_without_alt); ?>
                        </p>
                        <p style="margin: 0;">Images missing alt text</p>
                        <p style="margin: 10px 0 0;">
                            <a href="<?php echo admin_url('admin.php?page=cta-seo-images'); ?>" class="button button-primary">
                                Fix Images
                            </a>
                        </p>
                    <?php else: ?>
                        <p style="color: #00a32a; font-size: 32px; margin: 10px 0; font-weight: bold;">✓</p>
                        <p style="margin: 0;">All images have alt text</p>
                    <?php endif; ?>
                </div>
                
                <!-- Sitemap Stats -->
                <div class="card">
                    <h2>Sitemap</h2>
                    <p style="margin: 10px 0;">
                        <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank" class="button">
                            View Sitemap
                        </a>
                    </p>
                    <p style="margin: 0; font-size: 12px; color: #666;">
                        <a href="<?php echo admin_url('admin.php?page=cta-seo-sitemap'); ?>">Manage Sitemap →</a>
                    </p>
                </div>
                
            </div>
            
            <!-- Quick Actions -->
            <div class="card" style="margin-top: 20px;">
                <h2>Quick Actions</h2>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="<?php echo admin_url('admin.php?page=cta-seo-settings'); ?>" class="button">
                        SEO Settings
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=cta-seo-bulk'); ?>" class="button button-primary">
                        Bulk Optimization
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=cta-seo-schema'); ?>" class="button">
                        Schema Markup
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=cta-seo-verification'); ?>" class="button">
                        Verification Tools
                    </a>
                    <a href="<?php echo admin_url('upload.php'); ?>" class="button">
                        Media Library
                    </a>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card" style="margin-top: 20px;">
                <h2>Recent Activity</h2>
                <?php
                $last_ping = get_transient('cta_sitemap_last_ping');
                if ($last_ping) {
                    echo '<p>✅ Last sitemap ping: <strong>' . human_time_diff($last_ping) . ' ago</strong></p>';
                } else {
                    echo '<p>⏳ Sitemap has not been pinged yet.</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * SEO Settings Page
 */
function cta_seo_settings_page() {
    // Handle form submission
    if (isset($_POST['cta_save_seo_settings']) && check_admin_referer('cta_seo_settings')) {
        $title_separator = sanitize_text_field($_POST['title_separator']);
        $default_robots_index = isset($_POST['default_robots_index']) ? 1 : 0;
        $default_robots_follow = isset($_POST['default_robots_follow']) ? 1 : 0;
        
        update_option('cta_seo_title_separator', $title_separator);
        update_option('cta_seo_default_robots_index', $default_robots_index);
        update_option('cta_seo_default_robots_follow', $default_robots_follow);
        
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    
    $title_separator = get_option('cta_seo_title_separator', '–');
    $default_robots_index = get_option('cta_seo_default_robots_index', 1);
    $default_robots_follow = get_option('cta_seo_default_robots_follow', 1);
    
    ?>
    <div class="wrap">
        <h1>SEO Settings</h1>
        
        <form method="post">
            <?php wp_nonce_field('cta_seo_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="title_separator">Title Separator</label>
                    </th>
                    <td>
                        <input type="text" id="title_separator" name="title_separator" value="<?php echo esc_attr($title_separator); ?>" class="regular-text" />
                        <p class="description">Character used to separate page title and site name (default: –)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Default Robots Meta</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="default_robots_index" value="1" <?php checked($default_robots_index, 1); ?> />
                                Index (allow search engines to index)
                            </label><br>
                            <label>
                                <input type="checkbox" name="default_robots_follow" value="1" <?php checked($default_robots_follow, 1); ?> />
                                Follow (allow search engines to follow links)
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Settings', 'primary', 'cta_save_seo_settings'); ?>
        </form>
        
        <hr>
        
        <h2>Title Templates</h2>
        <div class="card">
            <p><strong>Posts:</strong> <code>%title%</code></p>
            <p><strong>Pages:</strong> <code>%title% %sep% %sitename%</code></p>
            <p><strong>Courses:</strong> <code>%title% %sep% %sitename%</code></p>
            <p><strong>Events:</strong> <code>%title% %sep% %sitename%</code></p>
            <p class="description">These templates are automatically applied. You can override them per page in the SEO meta box.</p>
        </div>
    </div>
    <?php
}

/**
 * Bulk SEO Optimization Page
 */
function cta_seo_bulk_page() {
    // Handle bulk actions
    if (isset($_POST['cta_bulk_seo_action']) && check_admin_referer('cta_bulk_seo')) {
        $action = sanitize_text_field($_POST['bulk_action']);
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        
        if (empty($post_ids)) {
            echo '<div class="notice notice-error"><p>No posts selected.</p></div>';
        } else {
            $updated = 0;
            
            foreach ($post_ids as $post_id) {
                switch ($action) {
                    case 'generate_descriptions':
                        // Auto-generate meta descriptions
                        $post = get_post($post_id);
                        if ($post) {
                            $description = cta_get_meta_description($post);
                            if (!empty($description)) {
                                cta_safe_update_field('page_seo_meta_description', $post_id, $description);
                                $updated++;
                            }
                        }
                        break;
                    
                    case 'apply_schema':
                        // Apply default schema based on post type
                        $post = get_post($post_id);
                        if ($post) {
                            $schema_type = cta_get_schema_template($post->post_type);
                            if ($schema_type) {
                                cta_safe_update_field('page_schema_type', $post_id, $schema_type);
                                $updated++;
                            }
                        }
                        break;
                }
            }
            
            echo '<div class="notice notice-success"><p>Updated ' . number_format($updated) . ' posts!</p></div>';
        }
    }
    
    // Get post types to show
    $post_types = ['page', 'post'];
    if (post_type_exists('course')) {
        $post_types[] = 'course';
    }
    
    // Get filter
    $filter_post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'page';
    $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // Build query
    $args = [
        'post_type' => $filter_post_type,
        'posts_per_page' => 50,
        'orderby' => 'title',
        'order' => 'ASC',
    ];
    
    if ($filter_status === 'missing_meta') {
        // Only show posts missing meta description
        $args['meta_query'] = [
            [
                'key' => 'page_seo_meta_description',
                'compare' => 'NOT EXISTS',
            ],
        ];
    }
    
    if (!empty($search)) {
        $args['s'] = $search;
    }
    
    $query = new WP_Query($args);
    
    ?>
    <div class="wrap">
        <h1>Bulk SEO Optimization</h1>
        
        <div class="card" style="margin-bottom: 20px;">
            <h2>Bulk Actions</h2>
            <form method="post" id="cta-bulk-seo-form">
                <?php wp_nonce_field('cta_bulk_seo'); ?>
                <input type="hidden" name="cta_bulk_seo_action" value="1" />
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Action</th>
                        <td>
                            <select name="bulk_action" id="bulk_action" required>
                                <option value="">-- Select Action --</option>
                                <option value="generate_descriptions">Generate Meta Descriptions</option>
                                <option value="apply_schema">Apply Default Schema Types</option>
                            </select>
                            <p class="description">Select an action to apply to selected posts below.</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="bulk-submit-btn" disabled>
                        Apply to Selected Posts
                    </button>
                    <span id="selected-count" style="margin-left: 10px; color: #666;"></span>
                </p>
            </form>
        </div>
        
        <!-- Filters -->
        <div class="card" style="margin-bottom: 20px;">
            <form method="get">
                <input type="hidden" name="page" value="cta-seo-bulk" />
                <table class="form-table">
                    <tr>
                        <th scope="row">Post Type</th>
                        <td>
                            <select name="post_type">
                                <?php foreach ($post_types as $pt) : ?>
                                    <option value="<?php echo esc_attr($pt); ?>" <?php selected($filter_post_type, $pt); ?>>
                                        <?php echo esc_html(ucfirst($pt)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Filter</th>
                        <td>
                            <select name="status">
                                <option value="all" <?php selected($filter_status, 'all'); ?>>All Posts</option>
                                <option value="missing_meta" <?php selected($filter_status, 'missing_meta'); ?>>Missing Meta Descriptions</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Search</th>
                        <td>
                            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" class="regular-text" placeholder="Search posts..." />
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button">Filter</button>
                    <a href="<?php echo admin_url('admin.php?page=cta-seo-bulk'); ?>" class="button">Reset</a>
                </p>
            </form>
        </div>
        
        <!-- Posts List -->
        <div class="card">
            <h2>Posts (<?php echo number_format($query->found_posts); ?> found)</h2>
            
            <?php if ($query->have_posts()) : ?>
                <form id="posts-form">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 30px;">
                                    <input type="checkbox" id="select-all" />
                                </th>
                                <th>Title</th>
                                <th>Meta Title</th>
                                <th>Meta Description</th>
                                <th>Schema</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($query->have_posts()) : $query->the_post(); 
                                $post_id = get_the_ID();
                                $meta_title = cta_safe_get_field('page_seo_meta_title', $post_id, '');
                                $meta_description = cta_safe_get_field('page_seo_meta_description', $post_id, '');
                                $schema_type = cta_safe_get_field('page_schema_type', $post_id, '');
                            ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="post_ids[]" value="<?php echo $post_id; ?>" class="post-checkbox" />
                                    </td>
                                    <td>
                                        <strong>
                                            <a href="<?php echo get_edit_post_link($post_id); ?>">
                                                <?php echo esc_html(get_the_title()); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($meta_title) : ?>
                                            <span style="color: #00a32a;">✓</span> <?php echo esc_html(mb_substr($meta_title, 0, 50)); ?>
                                        <?php else : ?>
                                            <span style="color: #d63638;">✗</span> <em>Not set</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($meta_description) : ?>
                                            <span style="color: #00a32a;">✓</span> <?php echo esc_html(mb_substr($meta_description, 0, 80)); ?>...
                                        <?php else : ?>
                                            <span style="color: #d63638;">✗</span> <em>Not set</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $schema_type ? esc_html($schema_type) : '<em>Default</em>'; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($post_id); ?>" class="button button-small">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </tbody>
                    </table>
                </form>
                
                <script>
                jQuery(document).ready(function($) {
                    // Select all checkbox
                    $('#select-all').on('change', function() {
                        $('.post-checkbox').prop('checked', $(this).prop('checked'));
                        updateSelectedCount();
                    });
                    
                    // Individual checkboxes
                    $('.post-checkbox').on('change', function() {
                        updateSelectedCount();
                    });
                    
                    function updateSelectedCount() {
                        var count = $('.post-checkbox:checked').length;
                        $('#selected-count').text(count > 0 ? count + ' selected' : '');
                        $('#bulk-submit-btn').prop('disabled', count === 0);
                        
                        // Copy checked IDs to form
                        var ids = [];
                        $('.post-checkbox:checked').each(function() {
                            ids.push($(this).val());
                        });
                        $('#cta-bulk-seo-form').find('input[name="post_ids[]"]').remove();
                        ids.forEach(function(id) {
                            $('#cta-bulk-seo-form').append('<input type="hidden" name="post_ids[]" value="' + id + '" />');
                        });
                    }
                    
                    // Form submission
                    $('#cta-bulk-seo-form').on('submit', function(e) {
                        if ($('.post-checkbox:checked').length === 0) {
                            e.preventDefault();
                            alert('Please select at least one post.');
                            return false;
                        }
                        
                        if (!$('#bulk_action').val()) {
                            e.preventDefault();
                            alert('Please select an action.');
                            return false;
                        }
                        
                        if (!confirm('This will update ' + $('.post-checkbox:checked').length + ' post(s). Continue?')) {
                            e.preventDefault();
                            return false;
                        }
                    });
                });
                </script>
            <?php else : ?>
                <p>No posts found.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * SEO Redirects Page (redirects to existing function)
 */
function cta_seo_redirects_page() {
    // Use existing redirects function
    if (function_exists('cta_redirects_admin_page')) {
        cta_redirects_admin_page();
    } else {
        echo '<div class="wrap"><h1>Redirects</h1><p>Redirects functionality not available.</p></div>';
    }
}

/**
 * SEO Sitemap Page (redirects to existing function)
 */
function cta_seo_sitemap_page() {
    // Use existing sitemap function
    if (function_exists('cta_sitemap_admin_page')) {
        cta_sitemap_admin_page();
    } else {
        echo '<div class="wrap"><h1>Sitemap</h1><p>Sitemap functionality not available.</p></div>';
    }
}

/**
 * SEO Schema Page
 */
function cta_seo_schema_page() {
    // Get schema stats
    $pages_with_schema = 0;
    $posts_with_schema = 0;
    $courses_with_schema = wp_count_posts('course')->publish ?? 0;
    $events_with_schema = wp_count_posts('course_event')->publish ?? 0;
    
    // Count pages with custom schema
    global $wpdb;
    $pages_with_schema = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_cta_schema_type' 
        AND meta_value != ''
    ");
    
    ?>
    <div class="wrap">
        <h1>Schema Markup</h1>
        
        <div class="card">
            <h2>Schema Coverage</h2>
            <p>Schema markup is automatically applied to content based on post type:</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Content Type</th>
                        <th>Schema Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Posts</strong></td>
                        <td>Article</td>
                        <td>✅ Automatic</td>
                    </tr>
                    <tr>
                        <td><strong>Pages</strong></td>
                        <td>WebPage (or custom)</td>
                        <td>✅ Automatic (can override)</td>
                    </tr>
                    <tr>
                        <td><strong>Courses</strong></td>
                        <td>Course</td>
                        <td>✅ Automatic</td>
                    </tr>
                    <tr>
                        <td><strong>Events</strong></td>
                        <td>Event</td>
                        <td>✅ Automatic</td>
                    </tr>
                    <tr>
                        <td><strong>All Pages</strong></td>
                        <td>Organization, LocalBusiness</td>
                        <td>✅ Site-wide</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Schema Statistics</h2>
            <ul>
                <li><strong>Pages with custom schema:</strong> <?php echo number_format($pages_with_schema); ?></li>
                <li><strong>Courses with schema:</strong> <?php echo number_format($courses_with_schema); ?></li>
                <li><strong>Events with schema:</strong> <?php echo number_format($events_with_schema); ?></li>
            </ul>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Schema Types Available</h2>
            <ul>
                <li><strong>Article</strong> - Blog posts and articles</li>
                <li><strong>WebPage</strong> - Regular pages</li>
                <li><strong>Course</strong> - Training courses</li>
                <li><strong>Event</strong> - Scheduled course events</li>
                <li><strong>FAQPage</strong> - Pages with FAQs</li>
                <li><strong>Organization</strong> - Site-wide organization info</li>
                <li><strong>LocalBusiness</strong> - Site-wide business info</li>
            </ul>
            <p class="description">You can override the default schema type for any page in the SEO meta box when editing that page.</p>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Validate Schema</h2>
            <p>Use Google's Rich Results Test to validate your schema markup:</p>
            <p>
                <a href="https://search.google.com/test/rich-results" target="_blank" class="button button-primary">
                    Open Rich Results Test
                </a>
            </p>
        </div>
    </div>
    <?php
}

/**
 * SEO Images Page
 */
function cta_seo_images_page() {
    global $wpdb;
    
    // Handle bulk alt text action
    if (isset($_POST['cta_bulk_add_alt_text']) && check_admin_referer('cta_bulk_alt_text_action')) {
        $images = $wpdb->get_results("
            SELECT ID, post_title 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
        ");
        
        $updated = 0;
        foreach ($images as $image) {
            $current_alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
            if (empty($current_alt)) {
                $filename = pathinfo(get_attached_file($image->ID), PATHINFO_FILENAME);
                $alt_text = ucwords(str_replace(['-', '_'], ' ', $filename));
                update_post_meta($image->ID, '_wp_attachment_image_alt', $alt_text);
                $updated++;
            }
        }
        
        echo '<div class="notice notice-success"><p>Alt text added to ' . number_format($updated) . ' images!</p></div>';
    }
    
    // Count images
    $total_images = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->posts} 
        WHERE post_type = 'attachment' 
        AND post_mime_type LIKE 'image/%'
    ");
    
    $images_without_alt = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
        WHERE p.post_type = 'attachment' 
        AND p.post_mime_type LIKE 'image/%'
        AND (pm.meta_value IS NULL OR pm.meta_value = '')
    ");
    
    $images_with_alt = $total_images - $images_without_alt;
    
    ?>
    <div class="wrap">
        <h1>Image SEO</h1>
        
        <div class="card">
            <h2>Image Alt Text Statistics</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Images with alt text</strong></td>
                        <td><?php echo number_format($images_with_alt); ?></td>
                        <td><?php echo $total_images > 0 ? number_format(($images_with_alt / $total_images) * 100, 1) : 0; ?>%</td>
                    </tr>
                    <tr>
                        <td><strong>Images missing alt text</strong></td>
                        <td><?php echo number_format($images_without_alt); ?></td>
                        <td><?php echo $total_images > 0 ? number_format(($images_without_alt / $total_images) * 100, 1) : 0; ?>%</td>
                    </tr>
                    <tr style="background: #f0f0f1; font-weight: bold;">
                        <td><strong>Total Images</strong></td>
                        <td><?php echo number_format($total_images); ?></td>
                        <td>100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php if ($images_without_alt > 0): ?>
        <div class="card" style="margin-top: 20px;">
            <h2>Bulk Add Alt Text</h2>
            <p>Automatically add alt text to all images that are missing it. Alt text will be generated from the image filename.</p>
            <form method="post">
                <?php wp_nonce_field('cta_bulk_alt_text_action'); ?>
                <p>
                    <button type="submit" name="cta_bulk_add_alt_text" class="button button-primary">
                        Add Alt Text to <?php echo number_format($images_without_alt); ?> Images
                    </button>
                </p>
            </form>
        </div>
        <?php else: ?>
        <div class="notice notice-success inline">
            <p>✅ All images have alt text!</p>
        </div>
        <?php endif; ?>
        
        <div class="card" style="margin-top: 20px;">
            <h2>How It Works</h2>
            <ul>
                <li><strong>Automatic:</strong> When you upload an image, alt text is automatically generated from the filename</li>
                <li><strong>Format:</strong> Filename is cleaned and formatted (e.g., "care-training-course.jpg" → "Care Training Course")</li>
                <li><strong>Manual Override:</strong> You can edit alt text for any image in the Media Library</li>
                <li><strong>Bulk Tool:</strong> Use the button above to add alt text to all images missing it</li>
            </ul>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Manage Images</h2>
            <p>
                <a href="<?php echo admin_url('upload.php'); ?>" class="button">
                    Go to Media Library
                </a>
            </p>
        </div>
    </div>
    <?php
}

/**
 * SEO Verification Page (uses existing function from seo-verification.php)
 * 
 * This is a wrapper to avoid function name conflicts.
 * The actual verification page is in seo-verification.php
 */
function cta_seo_admin_verification_page() {
    // The function cta_seo_verification_page() is defined in seo-verification.php
    // Since seo-verification.php is loaded before this file, we can call it directly
    if (function_exists('cta_seo_verification_page')) {
        // Call the original function from seo-verification.php
        call_user_func('cta_seo_verification_page');
    } else {
        // Fallback if function doesn't exist
        echo '<div class="wrap"><h1>SEO Verification</h1><p>Verification functionality not available.</p></div>';
    }
}
