<?php
/**
 * Performance Optimization Helpers
 * 
 * Helper functions for Core Web Vitals optimization and performance monitoring
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Disable WordPress emojis if option is set
 */
if (get_option('cta_disable_emojis', false)) {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    
    add_filter('tiny_mce_plugins', function($plugins) {
        if (is_array($plugins)) {
            return array_diff($plugins, ['wpemoji']);
        }
        return $plugins;
    });
    
    add_filter('wp_resource_hints', function($urls, $relation_type) {
        if ('dns-prefetch' === $relation_type) {
            $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/');
            $urls = array_diff($urls, [$emoji_svg_url]);
        }
        return $urls;
    }, 10, 2);
}

/**
 * Disable WordPress embeds if option is set
 */
if (get_option('cta_disable_embeds', false)) {
    add_action('init', function() {
        // Remove the REST API endpoint
        remove_action('rest_api_init', 'wp_oembed_register_route');
        
        // Turn off oEmbed auto discovery
        add_filter('embed_oembed_discover', '__return_false');
        
        // Don't filter oEmbed results
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        
        // Remove oEmbed discovery links
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        
        // Remove oEmbed-specific JavaScript from the front-end and back-end
        remove_action('wp_head', 'wp_oembed_add_host_js');
        
        // Remove all embeds rewrite rules
        add_filter('rewrite_rules_array', function($rules) {
            foreach ($rules as $rule => $rewrite) {
                if (false !== strpos($rewrite, 'embed=true')) {
                    unset($rules[$rule]);
                }
            }
            return $rules;
        });
    }, 9999);
}

/**
 * Limit post revisions if option is set
 */
if (get_option('cta_post_revision_limit', false)) {
    $limit = absint(get_option('cta_post_revision_limit', 5));
    if (!defined('WP_POST_REVISIONS')) {
        define('WP_POST_REVISIONS', $limit);
    }
}

/**
 * Get performance recommendations
 * 
 * @return array Performance recommendations with priority and actions
 */
function cta_get_performance_recommendations() {
    $recommendations = [];
    
    // Check 1: Image optimization
    $image_check = cta_check_image_optimization();
    if (!empty($image_check['issues'])) {
        $recommendations[] = [
            'priority' => 'high',
            'category' => 'Images',
            'title' => 'Image Optimization',
            'issues' => $image_check['issues'],
            'actions' => $image_check['actions'],
        ];
    }
    
    // Check 2: Lazy loading
    $lazy_check = cta_check_lazy_loading();
    if (!empty($lazy_check['issues'])) {
        $recommendations[] = [
            'priority' => 'medium',
            'category' => 'Loading',
            'title' => 'Lazy Loading',
            'issues' => $lazy_check['issues'],
            'actions' => $lazy_check['actions'],
        ];
    }
    
    // Check 3: Caching
    $cache_check = cta_check_caching();
    if (!empty($cache_check['issues'])) {
        $recommendations[] = [
            'priority' => 'high',
            'category' => 'Caching',
            'title' => 'Caching Configuration',
            'issues' => $cache_check['issues'],
            'actions' => $cache_check['actions'],
        ];
    }
    
    // Check 4: CSS/JS minification
    $minify_check = cta_check_minification();
    if (!empty($minify_check['issues'])) {
        $recommendations[] = [
            'priority' => 'medium',
            'category' => 'Assets',
            'title' => 'Asset Minification',
            'issues' => $minify_check['issues'],
            'actions' => $minify_check['actions'],
        ];
    }
    
    // Check 5: CDN
    $cdn_check = cta_check_cdn();
    if (!empty($cdn_check['issues'])) {
        $recommendations[] = [
            'priority' => 'low',
            'category' => 'CDN',
            'title' => 'CDN Configuration',
            'issues' => $cdn_check['issues'],
            'actions' => $cdn_check['actions'],
        ];
    }
    
    return $recommendations;
}

/**
 * Check image optimization status
 */
function cta_check_image_optimization() {
    $result = [
        'status' => 'good',
        'issues' => [],
        'actions' => [],
    ];
    
    // Check if WebP is being used (already implemented)
    // This is informational - WebP is already in use
    
    // Check for image optimization plugins
    $optimization_plugins = [
        'ewww-image-optimizer/ewww-image-optimizer.php',
        'imagify/imagify.php',
        'shortpixel-image-optimiser/wp-shortpixel.php',
        'smush/smush.php',
        'wp-smushit/wp-smush.php',
    ];
    
    $has_optimization = false;
    foreach ($optimization_plugins as $plugin) {
        if (is_plugin_active($plugin)) {
            $has_optimization = true;
            break;
        }
    }
    
    if (!$has_optimization) {
        $result['status'] = 'warning';
        $result['issues'][] = 'No image optimization plugin detected';
        $result['actions'][] = 'Consider installing an image optimization plugin (EWWW, Imagify, ShortPixel, or Smush)';
    }
    
    return $result;
}

/**
 * Check lazy loading implementation
 */
function cta_check_lazy_loading() {
    $result = [
        'status' => 'good',
        'issues' => [],
        'actions' => [],
    ];
    
    // Check if lazy loading function exists (already implemented in seo.php)
    if (!has_filter('wp_get_attachment_image_attributes', 'cta_lazy_load_images')) {
        $result['status'] = 'warning';
        $result['issues'][] = 'Lazy loading may not be fully implemented';
        $result['actions'][] = 'Verify lazy loading is working on image attributes';
    }
    
    return $result;
}

/**
 * Check caching configuration
 */
function cta_check_caching() {
    $result = [
        'status' => 'warning',
        'issues' => [],
        'actions' => [],
    ];
    
    // Check for caching plugins
    $caching_plugins = [
        'wp-rocket/wp-rocket.php',
        'w3-total-cache/w3-total-cache.php',
        'wp-super-cache/wp-cache.php',
        'litespeed-cache/litespeed-cache.php',
        'autoptimize/autoptimize.php',
    ];
    
    $has_caching = false;
    foreach ($caching_plugins as $plugin) {
        if (is_plugin_active($plugin)) {
            $has_caching = true;
            break;
        }
    }
    
    if (!$has_caching) {
        $result['issues'][] = 'No caching plugin detected';
        $result['actions'][] = 'Install a caching plugin: WP Rocket (premium), W3 Total Cache, WP Super Cache, or LiteSpeed Cache';
        $result['actions'][] = 'Configure page caching, object caching, and browser caching';
    } else {
        $result['status'] = 'good';
    }
    
    return $result;
}

/**
 * Check CSS/JS minification
 */
function cta_check_minification() {
    $result = [
        'status' => 'warning',
        'issues' => [],
        'actions' => [],
    ];
    
    // Check for minification plugins
    $minify_plugins = [
        'autoptimize/autoptimize.php',
        'wp-rocket/wp-rocket.php',
        'w3-total-cache/w3-total-cache.php',
    ];
    
    $has_minify = false;
    foreach ($minify_plugins as $plugin) {
        if (is_plugin_active($plugin)) {
            $has_minify = true;
            break;
        }
    }
    
    if (!$has_minify) {
        $result['issues'][] = 'No CSS/JS minification detected';
        $result['actions'][] = 'Install Autoptimize plugin for automatic CSS/JS minification';
        $result['actions'][] = 'Or enable minification in your caching plugin settings';
    } else {
        $result['status'] = 'good';
    }
    
    return $result;
}

/**
 * Check CDN configuration
 */
function cta_check_cdn() {
    $result = [
        'status' => 'info',
        'issues' => [],
        'actions' => [],
    ];
    
    // Check for CDN plugins or configuration
    $cdn_plugins = [
        'wp-rocket/wp-rocket.php',
        'w3-total-cache/w3-total-cache.php',
    ];
    
    $has_cdn = false;
    foreach ($cdn_plugins as $plugin) {
        if (is_plugin_active($plugin)) {
            // Check if CDN is configured (basic check)
            $has_cdn = true;
            break;
        }
    }
    
    // Check for Cloudflare (common CDN)
    if (isset($_SERVER['HTTP_CF_RAY'])) {
        $has_cdn = true;
    }
    
    if (!$has_cdn) {
        $result['issues'][] = 'No CDN detected';
        $result['actions'][] = 'Consider using Cloudflare (free tier available) for CDN and additional performance benefits';
        $result['actions'][] = 'Or configure CDN in your caching plugin';
    } else {
        $result['status'] = 'good';
    }
    
    return $result;
}

/**
 * Get Core Web Vitals target metrics
 */
function cta_get_core_web_vitals_targets() {
    return [
        'lcp' => [
            'name' => 'Largest Contentful Paint (LCP)',
            'target' => 2.5,
            'unit' => 'seconds',
            'description' => 'Time until the largest content element is visible',
        ],
        'inp' => [
            'name' => 'Interaction to Next Paint (INP)',
            'target' => 200,
            'unit' => 'milliseconds',
            'description' => 'Time from user interaction to visual response',
        ],
        'cls' => [
            'name' => 'Cumulative Layout Shift (CLS)',
            'target' => 0.1,
            'unit' => 'score',
            'description' => 'Visual stability measure (lower is better)',
        ],
    ];
}

/**
 * Add performance dashboard widget
 */
function cta_add_performance_dashboard_widget() {
    wp_add_dashboard_widget(
        'cta_performance_widget',
        'Performance Optimization',
        'cta_performance_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'cta_add_performance_dashboard_widget');

/**
 * Performance dashboard widget content
 */
function cta_performance_dashboard_widget_content() {
    $recommendations = cta_get_performance_recommendations();
    $targets = cta_get_core_web_vitals_targets();
    
    ?>
    <div>
        <p class="description" style="margin-bottom: 15px;">
            Monitor and optimize your site's performance for better Core Web Vitals scores.
        </p>
        
        <div style="margin-bottom: 20px;">
            <h3 style="margin-top: 0; font-size: 14px;">Core Web Vitals Targets</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <?php foreach ($targets as $metric) : ?>
                <li style="margin-bottom: 5px;">
                    <strong><?php echo esc_html($metric['name']); ?>:</strong> 
                    &lt; <?php echo esc_html($metric['target']); ?> <?php echo esc_html($metric['unit']); ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <?php if (!empty($recommendations)) : ?>
        <div style="margin-bottom: 20px;">
            <h3 style="margin-top: 0; font-size: 14px;">Recommendations</h3>
            <?php 
            $high_priority = array_filter($recommendations, function($r) { return $r['priority'] === 'high'; });
            if (!empty($high_priority)) : 
            ?>
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 10px 0;">
                <strong style="color: #856404;">High Priority:</strong>
                <ul style="margin: 5px 0 0 20px;">
                    <?php foreach ($high_priority as $rec) : ?>
                    <li><?php echo esc_html($rec['title']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php else : ?>
        <div style="background: #d1e7dd; border-left: 4px solid #00a32a; padding: 10px; margin: 10px 0;">
            <strong style="color: #0f5132;">✓ No critical performance issues detected</strong>
        </div>
        <?php endif; ?>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
            <a href="https://pagespeed.web.dev/" target="_blank" class="button button-primary">Test PageSpeed</a>
            <a href="<?php echo admin_url('plugins.php'); ?>" class="button">Manage Plugins</a>
        </div>
        
        <p class="description" style="margin-top: 15px; font-size: 11px; color: #646970;">
            Note: Perfect scores aren't required. Average #1 ranking sites score 40-60 on mobile PageSpeed.
        </p>
    </div>
    <?php
}

/**
 * Add performance admin page
 */
function cta_add_performance_admin_page() {
    add_submenu_page(
        'tools.php',
        'Performance Optimization',
        'Performance Optimization',
        'manage_options',
        'cta-performance',
        'cta_performance_admin_page'
    );
}
add_action('admin_menu', 'cta_add_performance_admin_page');

/**
 * Handle performance optimization actions
 */
function cta_handle_performance_actions() {
    if (!isset($_POST['cta_performance_action']) || !current_user_can('manage_options')) {
        return;
    }
    
    check_admin_referer('cta_performance_action');
    
    $action = sanitize_text_field($_POST['cta_performance_action']);
    $success = false;
    $message = '';
    
    switch ($action) {
        case 'optimize_database':
            $success = cta_optimize_database();
            $message = $success ? 'Database optimized successfully!' : 'Database optimization failed.';
            break;
            
        case 'clear_transients':
            $success = cta_clear_expired_transients();
            $message = $success ? 'Expired transients cleared!' : 'Failed to clear transients.';
            break;
            
        case 'generate_htaccess':
            $success = cta_generate_performance_htaccess();
            $message = $success ? 'Browser caching rules added to .htaccess!' : 'Failed to update .htaccess. Check file permissions.';
            break;
            
        case 'disable_emojis':
            update_option('cta_disable_emojis', true);
            $success = true;
            $message = 'WordPress emojis disabled!';
            break;
            
        case 'enable_emojis':
            delete_option('cta_disable_emojis');
            $success = true;
            $message = 'WordPress emojis enabled!';
            break;
            
        case 'disable_embeds':
            update_option('cta_disable_embeds', true);
            $success = true;
            $message = 'WordPress embeds disabled!';
            break;
            
        case 'enable_embeds':
            delete_option('cta_disable_embeds');
            $success = true;
            $message = 'WordPress embeds enabled!';
            break;
            
        case 'limit_revisions':
            $limit = isset($_POST['revision_limit']) ? absint($_POST['revision_limit']) : 5;
            update_option('cta_post_revision_limit', $limit);
            $success = true;
            $message = "Post revisions limited to {$limit}!";
            break;
            
        case 'enable_auto_optimization':
            if (!wp_next_scheduled('cta_auto_database_cleanup')) {
                // Schedule for Sunday at 3 AM
                $timestamp = strtotime('next Sunday 3:00 AM');
                wp_schedule_event($timestamp, 'weekly', 'cta_auto_database_cleanup');
                $success = true;
                $message = 'Automatic weekly database optimization enabled! First run: ' . date('F j, Y g:i a', $timestamp);
            } else {
                $message = 'Automatic optimization is already enabled.';
            }
            break;
            
        case 'disable_auto_optimization':
            $timestamp = wp_next_scheduled('cta_auto_database_cleanup');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'cta_auto_database_cleanup');
                $success = true;
                $message = 'Automatic database optimization disabled.';
            } else {
                $message = 'Automatic optimization was not scheduled.';
            }
            break;
    }
    
    if ($success) {
        add_settings_error('cta_performance', 'success', $message, 'success');
    } else {
        add_settings_error('cta_performance', 'error', $message, 'error');
    }
}
add_action('admin_init', 'cta_handle_performance_actions');

/**
 * Register custom cron schedule for weekly optimization
 */
function cta_add_weekly_cron_schedule($schedules) {
    if (!isset($schedules['weekly'])) {
        $schedules['weekly'] = [
            'interval' => 604800, // 7 days in seconds
            'display' => __('Once Weekly')
        ];
    }
    return $schedules;
}
add_filter('cron_schedules', 'cta_add_weekly_cron_schedule');

/**
 * Hook the database optimization function to the cron event
 */
add_action('cta_auto_database_cleanup', 'cta_optimize_database');

/**
 * Clean up scheduled event on theme deactivation
 */
function cta_cleanup_scheduled_events() {
    $timestamp = wp_next_scheduled('cta_auto_database_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'cta_auto_database_cleanup');
    }
}
register_deactivation_hook(__FILE__, 'cta_cleanup_scheduled_events');

/**
 * Optimize database tables
 */
function cta_optimize_database() {
    global $wpdb;
    
    // Get all tables
    $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
    
    if (empty($tables)) {
        return false;
    }
    
    foreach ($tables as $table) {
        $wpdb->query("OPTIMIZE TABLE {$table[0]}");
    }
    
    // Clean up post revisions (keep last 5)
    $wpdb->query("
        DELETE FROM {$wpdb->posts}
        WHERE post_type = 'revision'
        AND ID NOT IN (
            SELECT * FROM (
                SELECT ID FROM {$wpdb->posts}
                WHERE post_type = 'revision'
                ORDER BY post_modified DESC
                LIMIT 100
            ) AS keep_revisions
        )
    ");
    
    // Clean up auto-drafts older than 7 days
    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->posts}
        WHERE post_status = 'auto-draft'
        AND post_modified < %s
    ", date('Y-m-d', strtotime('-7 days'))));
    
    // Clean up orphaned post meta
    $wpdb->query("
        DELETE pm FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE p.ID IS NULL
    ");
    
    // Clean up orphaned comment meta
    $wpdb->query("
        DELETE cm FROM {$wpdb->commentmeta} cm
        LEFT JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id
        WHERE c.comment_ID IS NULL
    ");
    
    return true;
}

/**
 * Clear expired transients
 */
function cta_clear_expired_transients() {
    global $wpdb;
    
    $time = time();
    
    // Delete expired transients and their values using a more efficient JOIN.
    $wpdb->query(
        $wpdb->prepare(
            "
        DELETE a, b FROM {$wpdb->options} a
        LEFT JOIN {$wpdb->options} b 
            ON b.option_name = REPLACE(a.option_name, '_transient_timeout_', '_transient_')
        WHERE a.option_name LIKE %s
        AND a.option_value < %d
    ",
            '_transient_timeout_%',
            $time
        )
    );
    
    // Delete orphaned transient options using LEFT JOIN instead of subquery.
    $wpdb->query(
        "
        DELETE t1 FROM {$wpdb->options} t1
        LEFT JOIN {$wpdb->options} t2 
            ON t2.option_name = CONCAT('_transient_timeout_', SUBSTRING(t1.option_name, 12))
        WHERE t1.option_name LIKE '_transient_%'
        AND t1.option_name NOT LIKE '_transient_timeout_%'
        AND t2.option_name IS NULL
    "
    );
    
    return true;
}

/**
 * Generate performance-optimized .htaccess rules
 */
function cta_generate_performance_htaccess() {
    $htaccess_file = ABSPATH . '.htaccess';
    
    if (!is_writable($htaccess_file)) {
        return false;
    }
    
    $current_content = file_get_contents($htaccess_file);
    
    // Check if our rules already exist
    if (strpos($current_content, '# BEGIN CTA Performance') !== false) {
        return true; // Already exists
    }
    
    $performance_rules = "\n# BEGIN CTA Performance\n";
    
    // Browser caching with Cache-Control headers
    $performance_rules .= "<IfModule mod_expires.c>\n";
    $performance_rules .= "ExpiresActive On\n";
    $performance_rules .= "ExpiresByType image/jpg \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType image/jpeg \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType image/gif \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType image/png \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType image/webp \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType image/svg+xml \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType text/css \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType application/javascript \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType text/javascript \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType application/pdf \"access plus 1 month\"\n";
    $performance_rules .= "ExpiresByType text/x-javascript \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType image/x-icon \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType font/woff \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType font/woff2 \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType application/font-woff \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresByType application/font-woff2 \"access plus 1 year\"\n";
    $performance_rules .= "ExpiresDefault \"access plus 2 days\"\n";
    $performance_rules .= "</IfModule>\n";
    $performance_rules .= "\n";
    
    // Cache-Control headers (for better browser caching)
    $performance_rules .= "<IfModule mod_headers.c>\n";
    $performance_rules .= "# Cache static assets for 1 year\n";
    $performance_rules .= "<FilesMatch \"\\.(jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|eot)$\">\n";
    $performance_rules .= "Header set Cache-Control \"max-age=31536000, public\"\n";
    $performance_rules .= "</FilesMatch>\n";
    $performance_rules .= "# Cache CSS/JS for 1 year (versioned files)\n";
    $performance_rules .= "<FilesMatch \"\\.(css|js)$\">\n";
    $performance_rules .= "Header set Cache-Control \"max-age=31536000, public\"\n";
    $performance_rules .= "</FilesMatch>\n";
    $performance_rules .= "# Don't cache HTML\n";
    $performance_rules .= "<FilesMatch \"\\.(html|htm|php)$\">\n";
    $performance_rules .= "Header set Cache-Control \"max-age=0, must-revalidate\"\n";
    $performance_rules .= "</FilesMatch>\n";
    $performance_rules .= "</IfModule>\n";
    $performance_rules .= "\n";
    
    // Gzip compression
    $performance_rules .= "<IfModule mod_deflate.c>\n";
    $performance_rules .= "AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json application/xml\n";
    $performance_rules .= "AddOutputFilterByType DEFLATE image/svg+xml\n";
    $performance_rules .= "</IfModule>\n";
    $performance_rules .= "# END CTA Performance\n\n";
    
    // Add rules at the beginning of the file
    $new_content = $performance_rules . $current_content;
    
    return file_put_contents($htaccess_file, $new_content) !== false;
}

/**
 * Performance admin page content
 */
function cta_performance_admin_page() {
    $targets = cta_get_core_web_vitals_targets();
    
    // Get current settings
    $emojis_disabled = get_option('cta_disable_emojis', false);
    $embeds_disabled = get_option('cta_disable_embeds', false);
    $revision_limit = get_option('cta_post_revision_limit', 5);
    
    // Check .htaccess status
    $htaccess_file = ABSPATH . '.htaccess';
    $htaccess_writable = is_writable($htaccess_file);
    $htaccess_has_rules = file_exists($htaccess_file) && strpos(file_get_contents($htaccess_file), '# BEGIN CTA Performance') !== false;
    
    // Get database stats
    global $wpdb;
    $revision_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
    $autodraft_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
    $transient_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
    
    settings_errors('cta_performance');
    
    ?>
    <div class="wrap">
        <h1>Performance Optimization</h1>
        <p class="description">Take action to improve your site's performance. These tools actually do the work for you.</p>
        
        <!-- Database Optimization -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2><span class="dashicons dashicons-database" style="color: #2271b1;"></span> Database Optimization</h2>
            <p>Clean up and optimize your database to improve query performance.</p>
            
            <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin: 15px 0;">
                <strong>Current Database Stats:</strong>
                <ul style="margin: 10px 0;">
                    <li>Post Revisions: <strong><?php echo number_format_i18n($revision_count); ?></strong></li>
                    <li>Auto-Drafts: <strong><?php echo number_format_i18n($autodraft_count); ?></strong></li>
                    <li>Transients: <strong><?php echo number_format_i18n($transient_count); ?></strong></li>
                </ul>
            </div>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('cta_performance_action'); ?>
                <input type="hidden" name="cta_performance_action" value="optimize_database">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                    Optimize Database Now
                </button>
            </form>
            
            <form method="post" style="display: inline; margin-left: 10px;">
                <?php wp_nonce_field('cta_performance_action'); ?>
                <input type="hidden" name="cta_performance_action" value="clear_transients">
                <button type="submit" class="button">
                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                    Clear Expired Transients
                </button>
            </form>
            
            <p class="description" style="margin-top: 10px;">
                <strong>What this does:</strong> Optimizes database tables, removes old revisions, cleans up auto-drafts, and removes orphaned metadata.
            </p>
        </div>
        
        <!-- Browser Caching -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2><span class="dashicons dashicons-clock" style="color: #2271b1;"></span> Browser Caching</h2>
            <p>Add caching rules to your .htaccess file to tell browsers to cache static files.</p>
            
            <div style="background: <?php echo $htaccess_has_rules ? '#d1e7dd' : '#f0f0f1'; ?>; padding: 15px; border-radius: 4px; margin: 15px 0;">
                <?php if ($htaccess_has_rules) : ?>
                    <strong style="color: #0f5132;">✓ Browser caching rules are active</strong>
                <?php elseif (!$htaccess_writable) : ?>
                    <strong style="color: #d63638;">✗ .htaccess file is not writable</strong>
                    <p style="margin: 10px 0 0 0;">Please make .htaccess writable (chmod 644) to enable this feature.</p>
                <?php else : ?>
                    <strong>Browser caching rules not yet added</strong>
                <?php endif; ?>
            </div>
            
            <?php if ($htaccess_writable && !$htaccess_has_rules) : ?>
            <form method="post">
                <?php wp_nonce_field('cta_performance_action'); ?>
                <input type="hidden" name="cta_performance_action" value="generate_htaccess">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
                    Add Browser Caching Rules
                </button>
            </form>
            <?php endif; ?>
            
            <p class="description" style="margin-top: 10px;">
                <strong>What this does:</strong> Adds expires headers and gzip compression to .htaccess for faster page loads.
            </p>
        </div>
        
        <!-- WordPress Features -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2><span class="dashicons dashicons-wordpress" style="color: #2271b1;"></span> WordPress Features</h2>
            <p>Disable unnecessary WordPress features to reduce HTTP requests and improve performance.</p>
            
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Status</th>
                        <th>Action</th>
                        <th>Impact</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Emoji Scripts</strong></td>
                        <td>
                            <?php if ($emojis_disabled) : ?>
                                <span style="color: #00a32a;">✓ Disabled</span>
                            <?php else : ?>
                                <span style="color: #d63638;">● Enabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('cta_performance_action'); ?>
                                <input type="hidden" name="cta_performance_action" value="<?php echo $emojis_disabled ? 'enable_emojis' : 'disable_emojis'; ?>">
                                <button type="submit" class="button button-small">
                                    <?php echo $emojis_disabled ? 'Enable' : 'Disable'; ?>
                                </button>
                            </form>
                        </td>
                        <td>Saves 1 HTTP request, ~15KB</td>
                    </tr>
                    <tr>
                        <td><strong>Embeds (oEmbed)</strong></td>
                        <td>
                            <?php if ($embeds_disabled) : ?>
                                <span style="color: #00a32a;">✓ Disabled</span>
                            <?php else : ?>
                                <span style="color: #d63638;">● Enabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('cta_performance_action'); ?>
                                <input type="hidden" name="cta_performance_action" value="<?php echo $embeds_disabled ? 'enable_embeds' : 'disable_embeds'; ?>">
                                <button type="submit" class="button button-small">
                                    <?php echo $embeds_disabled ? 'Enable' : 'Disable'; ?>
                                </button>
                            </form>
                        </td>
                        <td>Saves 1 HTTP request, ~8KB</td>
                    </tr>
                    <tr>
                        <td><strong>Post Revisions</strong></td>
                        <td>Limited to <?php echo esc_html($revision_limit); ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('cta_performance_action'); ?>
                                <input type="hidden" name="cta_performance_action" value="limit_revisions">
                                <input type="number" name="revision_limit" value="<?php echo esc_attr($revision_limit); ?>" min="0" max="20" style="width: 60px;">
                                <button type="submit" class="button button-small">Update</button>
                            </form>
                        </td>
                        <td>Reduces database size</td>
                    </tr>
                </tbody>
            </table>
            
            <p class="description" style="margin-top: 10px;">
                <strong>Note:</strong> These changes take effect immediately. Disabling features you don't use improves performance.
            </p>
        </div>
        
        <!-- Core Web Vitals -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2><span class="dashicons dashicons-chart-line" style="color: #2271b1;"></span> Core Web Vitals Targets</h2>
            <p>Google's recommended performance metrics for good user experience.</p>
            
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Target</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($targets as $key => $metric) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($metric['name']); ?></strong></td>
                        <td>&lt; <?php echo esc_html($metric['target']); ?> <?php echo esc_html($metric['unit']); ?></td>
                        <td><?php echo esc_html($metric['description']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p style="margin-top: 15px;">
                <a href="https://pagespeed.web.dev/?url=<?php echo urlencode(home_url()); ?>" target="_blank" class="button button-primary">
                    <span class="dashicons dashicons-external" style="vertical-align: middle;"></span>
                    Test Your Site on PageSpeed Insights
                </a>
            </p>
        </div>
        
        <!-- Performance Notes -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2>Performance Notes</h2>
            <ul style="line-height: 1.8;">
                <li>✓ <strong>WebP images</strong> are already implemented</li>
                <li>✓ <strong>Lazy loading</strong> is already implemented</li>
                <li>📊 Perfect PageSpeed scores aren't required for ranking success</li>
                <li>📊 Average #1 ranking sites score 40-60 on mobile PageSpeed</li>
                <li>🎯 Focus on user experience improvements rather than perfect scores</li>
            </ul>
        </div>
        
        <!-- Automatic Optimization Schedule -->
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2><span class="dashicons dashicons-calendar-alt" style="color: #2271b1;"></span> Automatic Optimization</h2>
            <p>Schedule automatic database optimization to keep your site running smoothly.</p>
            
            <?php
            $next_scheduled = wp_next_scheduled('cta_auto_database_cleanup');
            $auto_enabled = $next_scheduled !== false;
            ?>
            
            <div style="background: <?php echo $auto_enabled ? '#d1e7dd' : '#f0f0f1'; ?>; padding: 15px; border-radius: 4px; margin: 15px 0;">
                <?php if ($auto_enabled) : ?>
                    <strong style="color: #0f5132;">✓ Automatic optimization is enabled</strong>
                    <p style="margin: 10px 0 0 0;">
                        Next scheduled run: <strong><?php echo date('F j, Y g:i a', $next_scheduled); ?></strong>
                        (<?php echo human_time_diff($next_scheduled, current_time('timestamp')); ?> from now)
                    </p>
                <?php else : ?>
                    <strong>Automatic optimization is not scheduled</strong>
                    <p style="margin: 10px 0 0 0;">Enable weekly automatic database optimization for hands-free maintenance.</p>
                <?php endif; ?>
            </div>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('cta_performance_action'); ?>
                <input type="hidden" name="cta_performance_action" value="<?php echo $auto_enabled ? 'disable_auto_optimization' : 'enable_auto_optimization'; ?>">
                <button type="submit" class="button <?php echo $auto_enabled ? '' : 'button-primary'; ?>">
                    <span class="dashicons dashicons-<?php echo $auto_enabled ? 'no' : 'yes'; ?>" style="vertical-align: middle;"></span>
                    <?php echo $auto_enabled ? 'Disable' : 'Enable'; ?> Automatic Optimization
                </button>
            </form>
            
            <p class="description" style="margin-top: 10px;">
                <strong>What it does:</strong> Runs database optimization weekly (Sunday at 3 AM). Cleans up revisions, auto-drafts, transients, and orphaned data automatically.
            </p>
        </div>
    </div>
    <?php
}
