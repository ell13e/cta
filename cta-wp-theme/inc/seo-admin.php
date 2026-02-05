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
    
    // SEO Tools (moved from Tools menu)
    // Note: Function is defined in seo.php, so we reference it directly
    if (function_exists('cta_seo_tools_admin_page')) {
        add_submenu_page(
            'cta-seo',
            'SEO Tools',
            'Tools',
            'manage_options',
            'cta-seo-tools',
            'cta_seo_tools_admin_page'
        );
    }
    
    // Sitemap Diagnostic (moved from Tools menu)
    // Note: Function is defined in seo.php, so we reference it directly
    if (function_exists('cta_sitemap_diagnostic_page')) {
        add_submenu_page(
            'cta-seo',
            'Sitemap Diagnostic',
            'Sitemap Diagnostic',
            'manage_options',
            'cta-seo-sitemap-diagnostic',
            'cta_sitemap_diagnostic_page'
        );
    }
    
    // Performance Optimization (moved from Tools menu)
    // Note: Function is defined in performance-helpers.php, so we reference it directly
    if (function_exists('cta_performance_admin_page')) {
        add_submenu_page(
            'cta-seo',
            'Performance Optimization',
            'Performance',
            'manage_options',
            'cta-seo-performance',
            'cta_performance_admin_page'
        );
    }
}
add_action('admin_menu', 'cta_add_seo_admin_menu', 20);

/**
 * Enqueue SEO admin styles on all SEO sub-pages (not just Dashboard).
 */
function cta_seo_admin_enqueue_styles($hook) {
    if (strpos($hook, 'cta-seo') === false) {
        return;
    }
    wp_enqueue_style(
        'cta-seo-admin',
        get_template_directory_uri() . '/assets/css/seo-admin.css',
        [],
        defined('CTA_THEME_VERSION') ? CTA_THEME_VERSION : '1.0.0'
    );
}
add_action('admin_enqueue_scripts', 'cta_seo_admin_enqueue_styles', 11);

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
    
    // Count pages with meta descriptions
    $pages_with_meta = 0;
    $pages_without_meta = 0;
    $post_types = ['course', 'course_event', 'post', 'page'];
    foreach ($post_types as $post_type) {
        $posts = get_posts([
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ]);
        
        foreach ($posts as $post_id) {
            $has_meta = false;
            if ($post_type === 'course') {
                $has_meta = !empty(cta_safe_get_field('course_seo_meta_description', $post_id, ''));
            } elseif ($post_type === 'course_event') {
                $has_meta = !empty(cta_safe_get_field('event_seo_meta_description', $post_id, ''));
            } elseif ($post_type === 'post') {
                $has_meta = !empty(cta_safe_get_field('news_meta_description', $post_id, ''));
            } else {
                $has_meta = !empty(cta_safe_get_field('seo_meta_description', $post_id, ''));
            }
            
            if ($has_meta) {
                $pages_with_meta++;
            } else {
                $pages_without_meta++;
            }
        }
    }
    
    $total_content = $pages_with_meta + $pages_without_meta;
    $meta_coverage = $total_content > 0 ? round(($pages_with_meta / $total_content) * 100, 1) : 0;
    
    // Get sitemap URL
    $sitemap_url = home_url('/wp-sitemap.xml');
    
    ?>
    <div class="wrap">
        <h1>SEO Dashboard</h1>
        
        <div class="cta-seo-dashboard">
            <div class="cta-seo-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                
                <!-- Meta Descriptions Coverage -->
                <div class="card">
                    <h2>Meta Descriptions</h2>
                    <p style="font-size: 32px; margin: 10px 0; font-weight: bold; color: <?php echo $meta_coverage >= 80 ? '#00a32a' : ($meta_coverage >= 50 ? '#dba617' : '#d63638'); ?>;">
                        <?php echo $meta_coverage; ?>%
                    </p>
                    <p style="margin: 0; font-size: 13px; color: #666;">
                        <?php echo number_format($pages_with_meta); ?> of <?php echo number_format($total_content); ?> pages
                    </p>
                    <?php if ($pages_without_meta > 0): ?>
                        <p style="margin: 10px 0 0;">
                            <a href="<?php echo admin_url('admin.php?page=cta-seo-tools'); ?>" class="button button-primary">
                                Fix Missing Meta
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
                
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
                    <p style="margin: 5px 0 0; font-size: 12px; color: #666;">
                        <a href="<?php echo admin_url('admin.php?page=cta-seo-sitemap'); ?>">Manage →</a>
                        <span style="margin: 0 5px;">|</span>
                        <a href="<?php echo admin_url('admin.php?page=cta-seo-sitemap-diagnostic'); ?>">Diagnostic →</a>
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
                    <a href="<?php echo admin_url('admin.php?page=cta-seo-tools'); ?>" class="button">
                        SEO Tools
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=cta-seo-schema'); ?>" class="button">
                        Schema Markup
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=cta-seo-verification'); ?>" class="button">
                        Verification
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=cta-seo-sitemap-diagnostic'); ?>" class="button">
                        Sitemap Diagnostic
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=cta-seo-performance'); ?>" class="button">
                        Performance
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

            <!-- SEO runbook: monitoring, security, redirects -->
            <div class="card" style="margin-top: 20px;">
                <h2>SEO runbook</h2>
                <p style="margin-top: 0;">Ongoing practice tied to theme improvements. No code changes here—process only.</p>
                <ul>
                    <li><strong>After schema changes:</strong> Validate key URLs (homepage, a post, a course, an event) with <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener">Google Rich Results Test</a>; fix errors and preferably warnings; re-check Search Console Enhancements after deployment.</li>
                    <li><strong>Traffic drops:</strong> Use Search Console Performance (16 months), compare date ranges, segment by query/page/device; use “Clicks difference” to find affected URLs; cross-check with Search Status Dashboard (core/spam/review updates).</li>
                    <li><strong>Security:</strong> Treat Security Issues and Safe Browsing as P0; keep WordPress, plugins, theme and server patched; use <code>site:yourdomain</code> periodically to spot odd URLs.</li>
                    <li><strong>Redirects:</strong> Prefer 301 for permanent moves; keep chains to one hop; use real 404/410 and a helpful 404 page for removed content.</li>
                </ul>
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
    <div class="wrap cta-seo-page">
        <header class="cta-seo-header">
            <h1>SEO Settings</h1>
            <p class="cta-seo-header-desc">Global defaults for titles and indexing. Per-page overrides are in each post’s SEO meta box.</p>
        </header>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Title &amp; indexing</h2>
            <div class="cta-seo-section__body">
            <form method="post">
                <?php wp_nonce_field('cta_seo_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="title_separator">Title separator</label>
                        </th>
                        <td>
                            <input type="text" id="title_separator" name="title_separator" value="<?php echo esc_attr($title_separator); ?>" class="regular-text" />
                            <p class="description">Character between page title and site name (default: –)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default robots</th>
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
                <?php submit_button('Save settings', 'primary', 'cta_save_seo_settings'); ?>
            </form>
            </div>
        </div>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Title templates</h2>
            <div class="cta-seo-section__body">
            <p><strong>Posts:</strong> <code>%title%</code></p>
            <p><strong>Pages:</strong> <code>%title% %sep% %sitename%</code></p>
            <p><strong>Courses:</strong> <code>%title% %sep% %sitename%</code></p>
            <p><strong>Events:</strong> <code>%title% %sep% %sitename%</code></p>
            <p class="description">Applied automatically. Override per page in the SEO meta box.</p>
            </div>
        </div>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Link policy</h2>
            <div class="cta-seo-section__body">
            <p>For outbound links, use the correct <code>rel</code> attributes so search engines understand the relationship:</p>
            <ul>
                <li><strong>Paid or partnership links</strong> (e.g. ads, sponsor logos, partner sections): use <code>rel="sponsored"</code>. This theme adds <code>rel="sponsored"</code> to the homepage partner logos section.</li>
                <li><strong>User-generated content</strong> (e.g. comments, forum posts): use <code>rel="ugc"</code> or <code>rel="nofollow"</code> on links within that content. If you enable comments, ensure your theme or plugin outputs these attributes on comment author/URL links.</li>
            </ul>
            <p class="description">Applying these in templates keeps markup consistent with Google’s link guidelines.</p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Meta description field name by post type (for bulk SEO).
 */
function cta_seo_bulk_meta_field($post_type) {
    $fields = [
        'page'          => 'page_seo_meta_description',
        'post'          => 'news_meta_description',
        'course'        => 'course_seo_meta_description',
        'course_event'  => 'event_seo_meta_description',
    ];
    return $fields[$post_type] ?? 'page_seo_meta_description';
}

/**
 * Bulk SEO Optimization Page
 */
function cta_seo_bulk_page() {
    // Single-item quick action (GET)
    if (isset($_GET['cta_bulk_single']) && isset($_GET['id']) && isset($_GET['action']) && check_admin_referer('cta_bulk_seo_single_' . (int) $_GET['id'])) {
        $post_id = (int) $_GET['id'];
        $action = sanitize_text_field($_GET['action']);
        $post = get_post($post_id);
        if ($post && current_user_can('edit_post', $post_id)) {
            if ($action === 'generate_descriptions') {
                $description = cta_get_meta_description($post);
                $field = cta_seo_bulk_meta_field($post->post_type);
                if (!empty($description)) {
                    cta_safe_update_field($field, $post_id, $description);
                }
            } elseif ($action === 'apply_schema') {
                $schema_type = cta_get_schema_template($post->post_type);
                if ($schema_type && $schema_type !== 'None') {
                    cta_safe_update_field('page_schema_type', $post_id, $schema_type);
                }
            }
            $redirect = add_query_arg([
                'page'          => 'cta-seo-bulk',
                'post_type'     => $post->post_type,
                'status'        => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
                's'             => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
                'paged'         => isset($_GET['paged']) ? (int) $_GET['paged'] : 1,
                'cta_bulk_done' => 1,
            ], admin_url('admin.php'));
            wp_safe_redirect($redirect);
            exit;
        }
    }

    // Handle bulk actions (POST)
    if (isset($_POST['cta_bulk_seo_action']) && check_admin_referer('cta_bulk_seo')) {
        $action = sanitize_text_field($_POST['bulk_action']);
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', array_unique($_POST['post_ids'])) : [];

        if (empty($post_ids)) {
            echo '<div class="notice notice-error"><p>No posts selected.</p></div>';
        } else {
            $updated = 0;
            $skipped = 0;

            foreach ($post_ids as $post_id) {
                $post = get_post($post_id);
                if (!$post || !current_user_can('edit_post', $post_id)) {
                    continue;
                }
                $meta_field = cta_seo_bulk_meta_field($post->post_type);

                switch ($action) {
                    case 'generate_descriptions':
                        $description = cta_get_meta_description($post);
                        if (!empty($description)) {
                            cta_safe_update_field($meta_field, $post_id, $description);
                            $updated++;
                        } else {
                            $skipped++;
                        }
                        break;

                    case 'apply_schema':
                        $schema_type = cta_get_schema_template($post->post_type);
                        if ($schema_type && $schema_type !== 'None') {
                            cta_safe_update_field('page_schema_type', $post_id, $schema_type);
                            $updated++;
                        }
                        break;
                }
            }

            $msg = 'Updated ' . number_format($updated) . ' item(s).';
            if ($skipped > 0 && $action === 'generate_descriptions') {
                $msg .= ' ' . number_format($skipped) . ' had no generated description.';
            }
            echo '<div class="notice notice-success"><p>' . esc_html($msg) . '</p></div>';
        }
    }

    if (isset($_GET['cta_bulk_done'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Item updated.</p></div>';
    }

    // Post types
    $post_types = ['page', 'post'];
    if (post_type_exists('course')) {
        $post_types[] = 'course';
    }
    if (post_type_exists('course_event')) {
        $post_types[] = 'course_event';
    }

    $filter_post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'page';
    if (!in_array($filter_post_type, $post_types, true)) {
        $filter_post_type = 'page';
    }
    $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $paged = max(1, (int) (isset($_GET['paged']) ? $_GET['paged'] : 1));

    $args = [
        'post_type'      => $filter_post_type,
        'posts_per_page' => 20,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
        'paged'          => $paged,
    ];

    if ($filter_status === 'missing_meta') {
        $meta_key = cta_seo_bulk_meta_field($filter_post_type);
        $args['meta_query'] = [
            'relation' => 'OR',
            [
                'key'     => $meta_key,
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => $meta_key,
                'value'   => '',
                'compare' => '=',
            ],
        ];
    } elseif ($filter_status === 'has_meta') {
        $meta_key = cta_seo_bulk_meta_field($filter_post_type);
        $args['meta_query'] = [
            [
                'key'     => $meta_key,
                'value'   => '',
                'compare' => '!=',
            ],
        ];
    }

    if (!empty($search)) {
        $args['s'] = $search;
    }

    $query = new WP_Query($args);
    
    ?>
    <div class="wrap cta-seo-page">
        <header class="cta-seo-header">
            <h1>Bulk SEO Optimization</h1>
            <p class="cta-seo-header-desc">Apply meta descriptions or schema types in bulk. Filter and select items below, then choose an action.</p>
        </header>

        <div class="cta-seo-toolbar">
            <form method="get" class="cta-seo-toolbar-group">
                <input type="hidden" name="page" value="cta-seo-bulk" />
                <input type="hidden" name="paged" value="1" />
                <label for="cta-bulk-post-type">Post type</label>
                <select name="post_type" id="cta-bulk-post-type">
                    <?php foreach ($post_types as $pt) : ?>
                        <option value="<?php echo esc_attr($pt); ?>" <?php selected($filter_post_type, $pt); ?>>
                            <?php echo esc_html(ucfirst($pt === 'course_event' ? 'Events' : $pt)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="cta-bulk-status">Filter</label>
                <select name="status" id="cta-bulk-status">
                    <option value="all" <?php selected($filter_status, 'all'); ?>>All</option>
                    <option value="missing_meta" <?php selected($filter_status, 'missing_meta'); ?>>Missing meta</option>
                    <option value="has_meta" <?php selected($filter_status, 'has_meta'); ?>>Has meta</option>
                </select>
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search…" class="regular-text" />
                <button type="submit" class="button">Filter</button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cta-seo-bulk&post_type=' . $filter_post_type)); ?>" class="button">Reset</a>
            </form>
            <form method="post" id="cta-bulk-seo-form" class="cta-seo-toolbar-actions">
                <?php wp_nonce_field('cta_bulk_seo'); ?>
                <input type="hidden" name="cta_bulk_seo_action" value="1" />
                <select name="bulk_action" id="bulk_action" required>
                    <option value="">Apply to selected…</option>
                    <option value="generate_descriptions">Generate meta descriptions</option>
                    <option value="apply_schema">Apply default schema</option>
                </select>
                <button type="submit" class="button button-primary" id="bulk-submit-btn" disabled>Apply</button>
                <span id="selected-count"></span>
            </form>
        </div>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title"><?php echo esc_html(ucfirst($filter_post_type === 'course_event' ? 'Events' : $filter_post_type)); ?> <span class="cta-seo-section__count">(<?php echo number_format($query->found_posts); ?> found)</span></h2>
            
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
                            <?php
                            while ($query->have_posts()) :
                                $query->the_post();
                                $post_id = get_the_ID();
                                $pt = get_post_type($post_id);
                                $meta_field = cta_seo_bulk_meta_field($pt);
                                $meta_title = $pt === 'course' ? cta_safe_get_field('course_seo_meta_title', $post_id, '') : ($pt === 'course_event' ? cta_safe_get_field('event_seo_meta_title', $post_id, '') : cta_safe_get_field('page_seo_meta_title', $post_id, ''));
                                $meta_description = cta_safe_get_field($meta_field, $post_id, '');
                                $schema_type = cta_safe_get_field('page_schema_type', $post_id, '');
                                $single_nonce = wp_create_nonce('cta_bulk_seo_single_' . $post_id);
                                $base_url = admin_url('admin.php');
                                $gen_url = add_query_arg(['page' => 'cta-seo-bulk', 'cta_bulk_single' => 1, 'id' => $post_id, 'action' => 'generate_descriptions', '_wpnonce' => $single_nonce], $base_url);
                                $schema_url = add_query_arg(['page' => 'cta-seo-bulk', 'cta_bulk_single' => 1, 'id' => $post_id, 'action' => 'apply_schema', '_wpnonce' => $single_nonce], $base_url);
                            ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="post_ids[]" value="<?php echo (int) $post_id; ?>" class="post-checkbox" />
                                    </td>
                                    <td>
                                        <strong>
                                            <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>">
                                                <?php echo esc_html(get_the_title()); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($meta_title) : ?>
                                            <span class="cta-seo-badge cta-seo-badge--set">Set</span>
                                            <span class="cta-seo-meta-preview" title="<?php echo esc_attr($meta_title); ?>"><?php echo esc_html(mb_substr($meta_title, 0, 40)); ?><?php echo mb_strlen($meta_title) > 40 ? '…' : ''; ?></span>
                                        <?php else : ?>
                                            <span class="cta-seo-badge cta-seo-badge--missing">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($meta_description) : ?>
                                            <span class="cta-seo-badge cta-seo-badge--set">Set</span>
                                            <span class="cta-seo-meta-preview" title="<?php echo esc_attr($meta_description); ?>"><?php echo esc_html(mb_substr($meta_description, 0, 50)); ?><?php echo mb_strlen($meta_description) > 50 ? '…' : ''; ?></span>
                                        <?php else : ?>
                                            <span class="cta-seo-badge cta-seo-badge--missing">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($schema_type) : ?>
                                            <span class="cta-seo-badge cta-seo-badge--set"><?php echo esc_html($schema_type); ?></span>
                                        <?php else : ?>
                                            <span class="cta-seo-badge cta-seo-badge--default">Default</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="cta-seo-table-actions">
                                            <a href="<?php echo esc_url($gen_url); ?>" title="Generate meta description">Generate</a>
                                            <span class="sep">|</span>
                                            <a href="<?php echo esc_url($schema_url); ?>" title="Apply default schema">Schema</a>
                                            <span class="sep">|</span>
                                            <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile;
                            wp_reset_postdata(); ?>
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

                <?php
                $total_pages = $query->max_num_pages;
                if ($total_pages > 1) {
                    $base = add_query_arg(['page' => 'cta-seo-bulk', 'post_type' => $filter_post_type, 'status' => $filter_status, 's' => $search], admin_url('admin.php'));
                    echo '<div class="tablenav bottom" style="margin-top: 16px;">';
                    echo '<div class="tablenav-pages">';
                    echo '<span class="displaying-num">' . number_format($query->found_posts) . ' items</span>';
                    echo ' <span class="pagination-links">';
                    if ($paged > 1) {
                        echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $paged - 1, $base)) . '">‹</a> ';
                    }
                    echo '<span class="paging-input">' . $paged . ' of <span class="total-pages">' . $total_pages . '</span></span>';
                    if ($paged < $total_pages) {
                        echo ' <a class="next-page button" href="' . esc_url(add_query_arg('paged', $paged + 1, $base)) . '">›</a>';
                    }
                    echo '</span></div></div>';
                }
                ?>
            <?php else : ?>
                <p>No posts found. Try changing the filter or search.</p>
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
 * Aligned with Google Search structured data and Schema.org.
 * @see https://developers.google.com/search/docs/appearance/structured-data/search-gallery
 * @see https://schema.org/docs/full.html
 */
function cta_seo_schema_page() {
    global $wpdb;

    $pages_with_custom_schema = (int) $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->postmeta}
        WHERE meta_key = '_cta_schema_type' AND meta_value != ''
    ");
    $courses_count = (int) (wp_count_posts('course')->publish ?? 0);
    $events_count = (int) (wp_count_posts('course_event')->publish ?? 0);

    $google_gallery = 'https://developers.google.com/search/docs/appearance/structured-data/search-gallery';
    $schema_docs = 'https://schema.org/docs/full.html';
    $rich_results_test = 'https://search.google.com/test/rich-results';
    ?>
    <div class="wrap cta-seo-page">
        <header class="cta-seo-header">
            <h1>Schema Markup</h1>
            <p class="cta-seo-header-desc">Structured data types this theme outputs and where they’re used. Validate URLs with <a href="<?php echo esc_url($rich_results_test); ?>" target="_blank" rel="noopener">Rich Results Test</a> after changes.</p>
        </header>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Overview</h2>
            <div class="cta-seo-section__body">
            <p>Structured data helps Google understand your content and can make it eligible for <strong>rich results</strong> (e.g. course carousels, event snippets, FAQ expandables, breadcrumbs). This theme outputs JSON-LD based on <a href="<?php echo esc_url($schema_docs); ?>" target="_blank" rel="noopener">Schema.org</a> types that <a href="<?php echo esc_url($google_gallery); ?>" target="_blank" rel="noopener">Google Search supports</a>.</p>
            <p><strong>Byline dates:</strong> For posts, keep visible &ldquo;Published&rdquo; and &ldquo;Last updated&rdquo; dates in sync with Article schema (<code>datePublished</code> / <code>dateModified</code>). The single post template shows these labels automatically.</p>
            </div>
        </div>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Snippet control</h2>
            <div class="cta-seo-section__body">
            <p>You can control what Google uses in search snippets and AI Overviews:</p>
            <ul>
                <li><strong>Page-level:</strong> In the page SEO meta box (Advanced SEO Settings), use <strong>No snippet</strong> to exclude the whole page from snippets, or <strong>Max snippet length</strong> to cap the snippet length (e.g. 160 characters).</li>
                <li><strong>Section-level:</strong> Wrap any block of content in <code>&lt;div data-nosnippet&gt;…&lt;/div&gt;</code> so that section is omitted from snippets while the rest of the page can still be used. No theme setting required.</li>
            </ul>
            <p>Use <code>nosnippet</code> (page-level) when the entire page shouldn’t appear in snippets. Use <code>data-nosnippet</code> when only specific parts (e.g. disclaimers, internal notes) should be excluded.</p>
            </div>
        </div>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Rich results eligibility</h2>
            <div class="cta-seo-section__body">
            <p>Types we output and their Google Search feature guides:</p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Schema.org type</th>
                        <th>Where we use it</th>
                        <th>Google guide</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Article</strong></td>
                        <td><code>Article</code></td>
                        <td>Blog posts</td>
                        <td><a href="https://developers.google.com/search/docs/appearance/structured-data/article" target="_blank" rel="noopener">Article</a></td>
                    </tr>
                    <tr>
                        <td><strong>Breadcrumb</strong></td>
                        <td><code>BreadcrumbList</code></td>
                        <td>All pages (site hierarchy)</td>
                        <td><a href="https://developers.google.com/search/docs/appearance/structured-data/breadcrumb" target="_blank" rel="noopener">Breadcrumb</a></td>
                    </tr>
                    <tr>
                        <td><strong>Course list</strong></td>
                        <td><code>Course</code></td>
                        <td>Single course pages</td>
                        <td><a href="https://developers.google.com/search/docs/appearance/structured-data/course" target="_blank" rel="noopener">Course list</a></td>
                    </tr>
                    <tr>
                        <td><strong>Event</strong></td>
                        <td><code>Event</code></td>
                        <td>Single scheduled course events; event archives</td>
                        <td><a href="https://developers.google.com/search/docs/appearance/structured-data/event" target="_blank" rel="noopener">Event</a></td>
                    </tr>
                    <tr>
                        <td><strong>FAQ</strong></td>
                        <td><code>FAQPage</code></td>
                        <td>FAQ page (questions from content/CPT)</td>
                        <td><a href="https://developers.google.com/search/docs/appearance/structured-data/faqpage" target="_blank" rel="noopener">FAQ</a></td>
                    </tr>
                    <tr>
                        <td><strong>Organization</strong></td>
                        <td><code>EducationalOrganization</code></td>
                        <td>All pages (org + rating, contact, sameAs)</td>
                        <td><a href="https://developers.google.com/search/docs/appearance/structured-data/organization" target="_blank" rel="noopener">Organization</a></td>
                    </tr>
                    <tr>
                        <td><strong>Review snippet</strong></td>
                        <td><code>AggregateRating</code> (on Organization)</td>
                        <td>Site-wide (Trustpilot rating)</td>
                        <td><a href="https://developers.google.com/search/docs/appearance/structured-data/review-snippet" target="_blank" rel="noopener">Review snippet</a></td>
                    </tr>
                    <tr>
                        <td><strong>WebPage</strong></td>
                        <td><code>WebPage</code>, <code>AboutPage</code>, <code>ContactPage</code>, etc.</td>
                        <td>Pages (with optional override per page)</td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Schema coverage by content type</h2>
            <div class="cta-seo-section__body">
            <table class="cta-seo-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Content type</th>
                        <th>Schema type(s)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Posts</strong></td>
                        <td>Article</td>
                        <td>Automatic</td>
                    </tr>
                    <tr>
                        <td><strong>Pages</strong></td>
                        <td>WebPage (or AboutPage, ContactPage, FAQPage, etc. by template)</td>
                        <td>Automatic; override in page SEO meta box</td>
                    </tr>
                    <tr>
                        <td><strong>Courses</strong></td>
                        <td>Course</td>
                        <td>Automatic</td>
                    </tr>
                    <tr>
                        <td><strong>Scheduled courses (events)</strong></td>
                        <td>Event</td>
                        <td>Automatic</td>
                    </tr>
                    <tr>
                        <td><strong>All pages</strong></td>
                        <td>EducationalOrganization, BreadcrumbList</td>
                        <td>Site-wide</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Statistics</h2>
            <div class="cta-seo-section__body">
            <ul>
                <li><strong>Pages with custom schema override:</strong> <?php echo number_format($pages_with_custom_schema); ?></li>
                <li><strong>Courses (with Course schema):</strong> <?php echo number_format($courses_count); ?></li>
                <li><strong>Events (with Event schema):</strong> <?php echo number_format($events_count); ?></li>
            </ul>
            </div>
        </div>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Override per page</h2>
            <div class="cta-seo-section__body">
            <p>When editing a page, use the SEO meta box to set a custom <code>schema:WebPage</code> subtype (e.g. <code>AboutPage</code>, <code>ContactPage</code>) or leave default. Schema.org type hierarchy: <a href="<?php echo esc_url($schema_docs); ?>" target="_blank" rel="noopener">Schema.org full hierarchy</a>.</p>
            </div>
        </div>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Validate</h2>
            <div class="cta-seo-section__body">
            <p>Check how Google interprets your markup and whether rich results are eligible:</p>
            <p>
                <a href="<?php echo esc_url($rich_results_test); ?>" target="_blank" rel="noopener" class="button button-primary">Open Rich Results Test</a>
            </p>
            <p class="description">Enter a live URL from your site. Actual appearance in search may still vary.</p>
            </div>
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
    
    // Handle bulk remove alt text action
    if (isset($_POST['cta_bulk_remove_alt_text']) && check_admin_referer('cta_bulk_remove_alt_text_action')) {
        $images = $wpdb->get_results("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type LIKE 'image/%'
        ");
        $removed = 0;
        foreach ($images as $image) {
            $current_alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
            if (!empty($current_alt)) {
                delete_post_meta($image->ID, '_wp_attachment_image_alt');
                $removed++;
            }
        }
        echo '<div class="notice notice-success"><p>Alt text removed from ' . number_format($removed) . ' images.</p></div>';
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
    <div class="wrap cta-seo-page">
        <header class="cta-seo-header">
            <h1>Image SEO</h1>
            <p class="cta-seo-header-desc">Alt text coverage and bulk actions. Use the Media Library or AI generation for individual images.</p>
        </header>

        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Image alt text statistics</h2>
            <div class="cta-seo-section__body">
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
                    <tr class="cta-seo-table-total">
                        <td><strong>Total Images</strong></td>
                        <td><?php echo number_format($total_images); ?></td>
                        <td>100%</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
        
        <?php if ($images_without_alt > 0): ?>
        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Bulk Add Alt Text</h2>
            <div class="cta-seo-section__body">
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
        </div>
        <?php else: ?>
        <div class="notice notice-success inline">
            <p>✅ All images have alt text!</p>
        </div>
        <?php endif; ?>
        
        <?php if ($images_with_alt > 0): ?>
        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Remove All Alt Text</h2>
            <div class="cta-seo-section__body">
            <p>Remove alt text from all images. Use this if you want to clear auto-generated alt and start over, or manage alt text manually. This cannot be undone.</p>
            <form method="post" onsubmit="return confirm('Remove alt text from all <?php echo number_format($images_with_alt); ?> images? This cannot be undone.');">
                <?php wp_nonce_field('cta_bulk_remove_alt_text_action'); ?>
                <p>
                    <button type="submit" name="cta_bulk_remove_alt_text" class="button cta-seo-btn-danger">
                        Remove Alt Text from <?php echo number_format($images_with_alt); ?> Images
                    </button>
                </p>
            </form>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">How It Works</h2>
            <div class="cta-seo-section__body">
            <ul>
                <li><strong>Automatic:</strong> When you upload an image, alt text is automatically generated from the filename</li>
                <li><strong>Format:</strong> Filename is cleaned and formatted (e.g., "care-training-course.jpg" → "Care Training Course")</li>
                <li><strong>Generate with AI:</strong> In the Media Library, open any image’s attachment details and use <strong>Generate with AI</strong> to fill Title, Caption, Alt text, and Description in one go. Alt text follows <a href="https://www.w3.org/WAI/tutorials/images/decision-tree/" target="_blank" rel="noopener">W3C guidance</a> (e.g. empty for decorative images). Requires OpenAI or Anthropic key in Settings → AI Assistant.</li>
                <li><strong>Manual Override:</strong> You can edit alt text for any image in the Media Library</li>
                <li><strong>Bulk Tool:</strong> Use the button above to add alt text to all images missing it</li>
            </ul>
            </div>
        </div>
        
        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Manage Images</h2>
            <div class="cta-seo-section__body">
            <p>
                <a href="<?php echo admin_url('upload.php'); ?>" class="button">
                    Go to Media Library
                </a>
            </p>
            </div>
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

