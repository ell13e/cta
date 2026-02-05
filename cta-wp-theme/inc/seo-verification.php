<?php
/**
 * SEO Verification Tool
 * 
 * Automated testing and validation for SEO implementation
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Run SEO verification checks
 * 
 * @return array Verification results with status, issues, and recommendations
 */
function cta_run_seo_verification() {
    $results = [
        'timestamp' => current_time('mysql'),
        'checks' => [],
        'overall_status' => 'pass',
        'score' => 0,
        'total_checks' => 0,
        'passed_checks' => 0,
        'warnings' => 0,
        'errors' => 0,
    ];
    
    // Check 1: Schema Markup
    $schema_check = cta_verify_schema_markup();
    $results['checks']['schema'] = $schema_check;
    $results['total_checks']++;
    if ($schema_check['status'] === 'pass') $results['passed_checks']++;
    if ($schema_check['status'] === 'warning') $results['warnings']++;
    if ($schema_check['status'] === 'error') $results['errors']++;
    
    // Check 2: Robots.txt
    $robots_check = cta_verify_robots_txt();
    $results['checks']['robots'] = $robots_check;
    $results['total_checks']++;
    if ($robots_check['status'] === 'pass') $results['passed_checks']++;
    if ($robots_check['status'] === 'warning') $results['warnings']++;
    if ($robots_check['status'] === 'error') $results['errors']++;
    
    // Check 3: Sitemap
    $sitemap_check = cta_verify_sitemap();
    $results['checks']['sitemap'] = $sitemap_check;
    $results['total_checks']++;
    if ($sitemap_check['status'] === 'pass') $results['passed_checks']++;
    if ($sitemap_check['status'] === 'warning') $results['warnings']++;
    if ($sitemap_check['status'] === 'error') $results['errors']++;
    
    // Check 4: Meta Tags
    $meta_check = cta_verify_meta_tags();
    $results['checks']['meta_tags'] = $meta_check;
    $results['total_checks']++;
    if ($meta_check['status'] === 'pass') $results['passed_checks']++;
    if ($meta_check['status'] === 'warning') $results['warnings']++;
    if ($meta_check['status'] === 'error') $results['errors']++;
    
    // Check 5: Canonical URLs
    $canonical_check = cta_verify_canonical_urls();
    $results['checks']['canonical'] = $canonical_check;
    $results['total_checks']++;
    if ($canonical_check['status'] === 'pass') $results['passed_checks']++;
    if ($canonical_check['status'] === 'warning') $results['warnings']++;
    if ($canonical_check['status'] === 'error') $results['errors']++;
    
    // Check 6: Search Engine Visibility
    $visibility_check = cta_verify_search_engine_visibility();
    $results['checks']['visibility'] = $visibility_check;
    $results['total_checks']++;
    if ($visibility_check['status'] === 'pass') $results['passed_checks']++;
    if ($visibility_check['status'] === 'warning') $results['warnings']++;
    if ($visibility_check['status'] === 'error') $results['errors']++;
    
    // Check 7: Permalink Structure
    $permalink_check = cta_verify_permalink_structure();
    $results['checks']['permalinks'] = $permalink_check;
    $results['total_checks']++;
    if ($permalink_check['status'] === 'pass') $results['passed_checks']++;
    if ($permalink_check['status'] === 'warning') $results['warnings']++;
    if ($permalink_check['status'] === 'error') $results['errors']++;
    
    // Check 8: Trustpilot Rating Configuration
    $trustpilot_check = cta_verify_trustpilot_config();
    $results['checks']['trustpilot'] = $trustpilot_check;
    $results['total_checks']++;
    if ($trustpilot_check['status'] === 'pass') $results['passed_checks']++;
    if ($trustpilot_check['status'] === 'warning') $results['warnings']++;
    if ($trustpilot_check['status'] === 'error') $results['errors']++;
    
    // Calculate score
    if ($results['total_checks'] > 0) {
        $results['score'] = round(($results['passed_checks'] / $results['total_checks']) * 100);
    }
    
    // Determine overall status
    if ($results['errors'] > 0) {
        $results['overall_status'] = 'error';
    } elseif ($results['warnings'] > 0) {
        $results['overall_status'] = 'warning';
    } else {
        $results['overall_status'] = 'pass';
    }
    
    return $results;
}

/**
 * Verify schema markup implementation
 */
function cta_verify_schema_markup() {
    $check = [
        'name' => 'Schema Markup',
        'status' => 'pass',
        'issues' => [],
        'recommendations' => [],
    ];
    
    // Check if schema function exists
    if (!function_exists('cta_schema_markup')) {
        $check['status'] = 'error';
        $check['issues'][] = 'Schema markup function not found';
        return $check;
    }
    
    // Test homepage schema
    $homepage_id = get_option('page_on_front');
    if ($homepage_id) {
        $post = get_post($homepage_id);
        if ($post) {
            global $wp_query;
            $wp_query->is_front_page = true;
            $wp_query->is_home = true;
            
            ob_start();
            cta_schema_markup();
            $schema_output = ob_get_clean();
            
            if (empty($schema_output)) {
                $check['status'] = 'error';
                $check['issues'][] = 'No schema markup output on homepage';
            } elseif (!strpos($schema_output, 'application/ld+json')) {
                $check['status'] = 'error';
                $check['issues'][] = 'Schema markup format incorrect';
            } else {
                // Check for required schemas
                $required_schemas = ['EducationalOrganization', 'LocalBusiness', 'WebSite'];
                $found_schemas = [];
                foreach ($required_schemas as $schema_type) {
                    if (strpos($schema_output, $schema_type) !== false) {
                        $found_schemas[] = $schema_type;
                    }
                }
                
                if (count($found_schemas) < count($required_schemas)) {
                    $check['status'] = 'warning';
                    $missing = array_diff($required_schemas, $found_schemas);
                    $check['issues'][] = 'Missing schema types: ' . implode(', ', $missing);
                }
            }
            
            // Reset query
            wp_reset_query();
        }
    }
    
    return $check;
}

/**
 * Verify robots.txt
 */
function cta_verify_robots_txt() {
    $check = [
        'name' => 'Robots.txt',
        'status' => 'pass',
        'issues' => [],
        'recommendations' => [],
    ];
    
    // Check if robots.txt filter exists
    if (!has_filter('robots_txt', 'cta_robots_txt')) {
        $check['status'] = 'error';
        $check['issues'][] = 'Robots.txt customization not found';
        return $check;
    }
    
    // Test robots.txt output
    $robots_output = apply_filters('robots_txt', '', get_option('blog_public'));
    
    if (empty($robots_output)) {
        $check['status'] = 'warning';
        $check['issues'][] = 'Robots.txt output is empty';
    } else {
        // Check for sitemap reference
        if (strpos($robots_output, 'Sitemap:') === false) {
            $check['status'] = 'warning';
            $check['issues'][] = 'Sitemap not referenced in robots.txt';
        }
        
        // Check for proper disallows
        if (strpos($robots_output, 'Disallow: /wp-admin/') === false) {
            $check['status'] = 'warning';
            $check['recommendations'][] = 'Consider adding Disallow: /wp-admin/ to robots.txt';
        }
    }
    
    return $check;
}

/**
 * Verify sitemap
 */
function cta_verify_sitemap() {
    $check = [
        'name' => 'XML Sitemap',
        'status' => 'pass',
        'issues' => [],
        'recommendations' => [],
    ];
    
    // Check WordPress version (sitemaps require 5.5+)
    global $wp_version;
    if (version_compare($wp_version, '5.5', '<')) {
        $check['status'] = 'error';
        $check['issues'][] = 'WordPress version ' . $wp_version . ' is below 5.5 (sitemaps require 5.5+)';
        return $check;
    }
    
    // Check search engine visibility
    if (get_option('blog_public') == 0) {
        $check['status'] = 'error';
        $check['issues'][] = 'Search engine visibility is disabled (sitemaps require this to be enabled)';
        return $check;
    }
    
    // Check permalink structure
    $permalink_structure = get_option('permalink_structure');
    if (empty($permalink_structure)) {
        $check['status'] = 'error';
        $check['issues'][] = 'Permalink structure is set to "Plain" (sitemaps require pretty permalinks)';
        return $check;
    }
    
    // Test sitemap URL
    $sitemap_url = home_url('/wp-sitemap.xml');
    $response = wp_remote_head($sitemap_url, ['timeout' => 5, 'sslverify' => false]);
    
    if (is_wp_error($response)) {
        $check['status'] = 'warning';
        $check['issues'][] = 'Could not verify sitemap accessibility: ' . $response->get_error_message();
        $check['recommendations'][] = 'Test sitemap URL manually: ' . $sitemap_url;
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code == 200) {
            // Sitemap is accessible
        } elseif ($status_code == 404) {
            $check['status'] = 'error';
            $check['issues'][] = 'Sitemap returns 404 (not found)';
            $check['recommendations'][] = 'Flush rewrite rules: Settings → Permalinks → Save Changes';
        } else {
            $check['status'] = 'warning';
            $check['issues'][] = 'Sitemap returned HTTP ' . $status_code;
        }
    }
    
    return $check;
}

/**
 * Verify meta tags
 */
function cta_verify_meta_tags() {
    $check = [
        'name' => 'Meta Tags',
        'status' => 'pass',
        'issues' => [],
        'recommendations' => [],
    ];
    
    // Check if meta tags function exists
    if (!function_exists('cta_seo_meta_tags')) {
        $check['status'] = 'error';
        $check['issues'][] = 'Meta tags function not found';
        return $check;
    }
    
    // Check if function is hooked
    if (!has_action('wp_head', 'cta_seo_meta_tags')) {
        $check['status'] = 'error';
        $check['issues'][] = 'Meta tags function not hooked to wp_head';
        return $check;
    }
    
    return $check;
}

/**
 * Fetch a URL and return canonical link count and href values (for manual review).
 *
 * @param string $url Full URL to fetch.
 * @return array{count: int, hrefs: string[], error: string|null}
 */
function cta_fetch_canonical_from_url($url) {
    $out = ['count' => 0, 'hrefs' => [], 'error' => null];
    $response = wp_remote_get($url, [
        'timeout' => 10,
        'sslverify' => true,
        'user-agent' => 'CTA-SEO-Verification/1.0',
    ]);
    if (is_wp_error($response)) {
        $out['error'] = $response->get_error_message();
        return $out;
    }
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        $out['error'] = 'HTTP ' . $code;
        return $out;
    }
    $body = wp_remote_retrieve_body($response);
    if (!is_string($body) || $body === '') {
        $out['error'] = 'Empty response';
        return $out;
    }
    // Match <link rel="canonical" href="..."> (allow single or double quotes, optional whitespace)
    if (preg_match_all('#<link\s+[^>]*rel\s*=\s*["\']canonical["\'][^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>#i', $body, $m) ||
        preg_match_all('#<link\s+[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*rel\s*=\s*["\']canonical["\'][^>]*>#i', $body, $m)) {
        $out['count'] = count($m[1]);
        $out['hrefs'] = array_values(array_unique(array_map('trim', $m[1])));
    }
    return $out;
}

/**
 * Verify canonical URLs and sample coverage (homepage, post, course, archive).
 */
function cta_verify_canonical_urls() {
    $check = [
        'name' => 'Canonical URLs',
        'status' => 'pass',
        'issues' => [],
        'recommendations' => [],
        'coverage' => [],
    ];
    
    // Check if canonical function exists
    if (!function_exists('cta_clean_canonical_url')) {
        $check['status'] = 'warning';
        $check['issues'][] = 'Canonical URL cleaning function not found';
        return $check;
    }
    
    // Test canonical URL cleaning
    $test_url = 'https://example.com/page/?utm_source=test';
    $cleaned = cta_clean_canonical_url($test_url);
    
    if (strpos($cleaned, 'utm_source') !== false) {
        $check['status'] = 'warning';
        $check['issues'][] = 'Canonical URL cleaning may not be removing query parameters';
    }
    
    // Canonical coverage: sample homepage, a post, a course, and an archive
    $base = home_url('/');
    $samples = [];
    
    // Homepage
    $samples[] = ['label' => 'Homepage', 'url' => $base];
    
    // First published post
    $post = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'orderby' => 'date']);
    if (!empty($post)) {
        $samples[] = ['label' => 'Post', 'url' => get_permalink($post[0])];
    }
    
    // First published course
    $course = get_posts(['post_type' => 'course', 'post_status' => 'publish', 'posts_per_page' => 1, 'orderby' => 'date']);
    if (!empty($course)) {
        $samples[] = ['label' => 'Course', 'url' => get_permalink($course[0])];
    }
    
    // Course archive
    $samples[] = ['label' => 'Course archive', 'url' => get_post_type_archive_link('course') ?: $base . 'course/'];
    
    foreach ($samples as $sample) {
        $url = $sample['url'];
        if (empty($url) || is_wp_error($url)) {
            $check['coverage'][] = ['label' => $sample['label'], 'url' => '', 'count' => 0, 'href' => null, 'error' => 'Invalid URL'];
            continue;
        }
        $result = cta_fetch_canonical_from_url($url);
        $href = isset($result['hrefs'][0]) ? $result['hrefs'][0] : null;
        $check['coverage'][] = [
            'label' => $sample['label'],
            'url'   => $url,
            'count' => $result['count'],
            'href'  => $href,
            'error' => $result['error'],
        ];
        if ($result['error']) {
            if ($check['status'] === 'pass') {
                $check['status'] = 'warning';
            }
            $check['issues'][] = $sample['label'] . ': could not fetch (' . $result['error'] . ')';
        } elseif ($result['count'] === 0) {
            if ($check['status'] === 'pass') {
                $check['status'] = 'warning';
            }
            $check['issues'][] = $sample['label'] . ': no canonical link found';
        } elseif ($result['count'] > 1) {
            if ($check['status'] === 'pass') {
                $check['status'] = 'warning';
            }
            $check['issues'][] = $sample['label'] . ': multiple canonicals (' . $result['count'] . ')';
        }
    }
    
    return $check;
}

/**
 * Verify search engine visibility
 */
function cta_verify_search_engine_visibility() {
    $check = [
        'name' => 'Search Engine Visibility',
        'status' => 'pass',
        'issues' => [],
        'recommendations' => [],
    ];
    
    $blog_public = get_option('blog_public');
    
    if ($blog_public == 0) {
        $check['status'] = 'error';
        $check['issues'][] = 'Search engine visibility is disabled';
        $check['recommendations'][] = 'Enable in Settings → Reading → uncheck "Discourage search engines from indexing this site"';
    }
    
    return $check;
}

/**
 * Verify permalink structure
 */
function cta_verify_permalink_structure() {
    $check = [
        'name' => 'Permalink Structure',
        'status' => 'pass',
        'issues' => [],
        'recommendations' => [],
    ];
    
    $permalink_structure = get_option('permalink_structure');
    
    if (empty($permalink_structure)) {
        $check['status'] = 'error';
        $check['issues'][] = 'Permalink structure is set to "Plain"';
        $check['recommendations'][] = 'Configure in Settings → Permalinks (any option except "Plain")';
    }
    
    return $check;
}

/**
 * Verify Trustpilot configuration
 */
function cta_verify_trustpilot_config() {
    $check = [
        'name' => 'Trustpilot Configuration',
        'status' => 'pass',
        'issues' => [],
        'recommendations' => [],
    ];
    
    $rating = cta_get_theme_option('trustpilot_rating', '');
    $review_count = cta_get_theme_option('trustpilot_review_count', '');
    
    if (empty($rating)) {
        $check['status'] = 'warning';
        $check['issues'][] = 'Trustpilot rating not configured';
        $check['recommendations'][] = 'Set in Appearance → Customize → Theme Settings → Trustpilot Rating';
    }
    
    if (empty($review_count) || $review_count == 0) {
        $check['status'] = 'warning';
        $check['issues'][] = 'Trustpilot review count not configured';
        $check['recommendations'][] = 'Set in Appearance → Customize → Theme Settings → Trustpilot Review Count';
    }
    
    return $check;
}

/**
 * Handle SEO fix actions
 */
function cta_handle_seo_fix_actions() {
    if (!isset($_POST['cta_seo_fix_action']) || !current_user_can('manage_options')) {
        return;
    }
    
    check_admin_referer('cta_seo_fix_action');
    
    $action = sanitize_text_field($_POST['cta_seo_fix_action']);
    $success = false;
    $message = '';
    
    switch ($action) {
        case 'fix_search_visibility':
            update_option('blog_public', 1);
            $success = true;
            $message = 'Search engine visibility enabled!';
            break;
            
        case 'fix_permalinks':
            // Set to post name structure
            update_option('permalink_structure', '/%postname%/');
            flush_rewrite_rules();
            $success = true;
            $message = 'Permalinks set to SEO-friendly structure!';
            break;
            
        case 'generate_robots':
            $robots_content = "User-agent: *\n";
            $robots_content .= "Allow: /\n\n";
            $robots_content .= "Sitemap: " . home_url('/sitemap.xml') . "\n";
            
            $robots_file = ABSPATH . 'robots.txt';
            if (file_put_contents($robots_file, $robots_content) !== false) {
                $success = true;
                $message = 'robots.txt file generated!';
            } else {
                $message = 'Failed to create robots.txt. Check file permissions.';
            }
            break;
            
        case 'fix_trustpilot':
            // Get values from POST (theme reads via cta_trustpilot_rating / cta_trustpilot_review_count)
            $rating = isset($_POST['trustpilot_rating']) ? floatval($_POST['trustpilot_rating']) : 0;
            $count = isset($_POST['trustpilot_count']) ? absint($_POST['trustpilot_count']) : 0;
            
            if ($rating > 0 && $count > 0) {
                set_theme_mod('cta_trustpilot_rating', $rating . '/5');
                set_theme_mod('cta_trustpilot_review_count', (string) $count);
                $success = true;
                $message = 'Trustpilot configuration updated!';
            } else {
                $message = 'Please provide valid rating and review count.';
            }
            break;
            
        case 'regenerate_sitemap':
            // Trigger sitemap regeneration
            delete_transient('cta_sitemap_cache');
            do_action('cta_regenerate_sitemap');
            $success = true;
            $message = 'Sitemap regenerated successfully!';
            break;
            
        case 'enable_schema_markup':
            update_option('cta_enable_schema_markup', true);
            $success = true;
            $message = 'Schema markup enabled for all applicable pages!';
            break;
            
        case 'disable_schema_markup':
            update_option('cta_enable_schema_markup', false);
            $success = true;
            $message = 'Schema markup disabled.';
            break;
            
        case 'configure_default_meta':
            // Get values from POST
            $default_title_suffix = isset($_POST['default_title_suffix']) ? sanitize_text_field($_POST['default_title_suffix']) : '';
            $default_description = isset($_POST['default_description']) ? sanitize_textarea_field($_POST['default_description']) : '';
            
            if (!empty($default_title_suffix) && !empty($default_description)) {
                update_option('cta_default_title_suffix', $default_title_suffix);
                update_option('cta_default_meta_description', $default_description);
                $success = true;
                $message = 'Default meta tags configured!';
            } else {
                $message = 'Please provide both title suffix and description.';
            }
            break;
            
        case 'enable_canonical_urls':
            update_option('cta_enable_canonical_urls', true);
            $success = true;
            $message = 'Canonical URLs enabled!';
            break;
            
        case 'disable_canonical_urls':
            update_option('cta_enable_canonical_urls', false);
            $success = true;
            $message = 'Canonical URLs disabled.';
            break;
            
        case 'configure_social_meta':
            $og_image = isset($_POST['og_default_image']) ? esc_url_raw($_POST['og_default_image']) : '';
            $fb_app_id = isset($_POST['fb_app_id']) ? sanitize_text_field($_POST['fb_app_id']) : '';
            
            if (!empty($og_image)) {
                update_option('cta_default_og_image', $og_image);
            }
            if (!empty($fb_app_id)) {
                update_option('cta_facebook_app_id', $fb_app_id);
            }
            
            $success = true;
            $message = 'Social media meta tags configured!';
            break;
    }
    
    // Clear verification cache so results refresh
    delete_transient('cta_seo_verification_results');
    
    if ($success) {
        add_settings_error('cta_seo_verification', 'success', $message, 'success');
    } else {
        add_settings_error('cta_seo_verification', 'error', $message, 'error');
    }
}
add_action('admin_init', 'cta_handle_seo_fix_actions');

/**
 * Add SEO verification admin page
 * NOTE: Now in SEO admin section, but keeping function for backward compatibility
 */
function cta_add_seo_verification_page() {
    // Removed from Tools menu - now in SEO section
    // Function still available for use in SEO admin section
}
// Removed action - menu now in SEO section
// add_action('admin_menu', 'cta_add_seo_verification_page');

/**
 * SEO verification page content
 */
function cta_seo_verification_page() {
    // Run verification
    $results = cta_run_seo_verification();
    
    // Handle AJAX refresh
    if (isset($_POST['cta_refresh_verification']) && check_admin_referer('cta_verification_refresh')) {
        $results = cta_run_seo_verification();
        // Cache results for 1 hour
        set_transient('cta_seo_verification_results', $results, HOUR_IN_SECONDS);
    } else {
        // Try to get cached results
        $cached = get_transient('cta_seo_verification_results');
        if ($cached !== false) {
            $results = $cached;
        }
    }
    
    $status_colors = [
        'pass' => '#00a32a',
        'warning' => '#dba617',
        'error' => '#d63638',
    ];
    
    $status_icons = [
        'pass' => '✓',
        'warning' => '⚠',
        'error' => '✗',
    ];
    
    settings_errors('cta_seo_verification');
    ?>
    <div class="wrap cta-seo-page">
        <header class="cta-seo-header">
            <h1>SEO Verification</h1>
            <p class="cta-seo-header-desc">Identify and fix SEO issues with one-click actions. Re-run after changes to confirm.</p>
        </header>
        
        <div class="cta-seo-section">
            <div class="cta-seo-actions" style="justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <h2 class="cta-seo-section__title">Verification Results</h2>
                    <p class="description">Last checked: <?php echo esc_html($results['timestamp']); ?></p>
                </div>
                <form method="post" style="margin: 0;">
                    <?php wp_nonce_field('cta_verification_refresh'); ?>
                    <input type="hidden" name="cta_refresh_verification" value="1">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                        Refresh
                    </button>
                </form>
            </div>
            
            <div class="cta-seo-status-banner cta-seo-status-banner--<?php echo esc_attr($results['overall_status']); ?>">
                <p class="cta-seo-status-banner__title"><?php echo esc_html($status_icons[$results['overall_status']); ?> Overall: <?php echo esc_html(ucfirst($results['overall_status'])); ?></p>
                <p class="cta-seo-status-banner__detail">Score: <?php echo esc_html($results['score']); ?>% (<?php echo esc_html($results['passed_checks']); ?>/<?php echo esc_html($results['total_checks']); ?> checks passed)</p>
            </div>
            
            <table class="cta-seo-table widefat">
                <thead>
                    <tr>
                        <th>Check</th>
                        <th>Status</th>
                        <th>Details</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['checks'] as $check_key => $check) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($check['name']); ?></strong></td>
                        <td>
                            <span class="cta-seo-status cta-seo-status--<?php echo esc_attr($check['status']); ?>">
                                <?php echo esc_html($status_icons[$check['status']]); ?> <?php echo esc_html(ucfirst($check['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($check['issues'])) : ?>
                            <ul>
                                <?php foreach ($check['issues'] as $issue) : ?>
                                <li><?php echo esc_html($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            
                            <?php if (!empty($check['recommendations'])) : ?>
                            <ul style="margin: 5px 0; font-size: 12px;">
                                <?php foreach ($check['recommendations'] as $rec) : ?>
                                <li><?php echo esc_html($rec); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            
                            <?php if (!empty($check['coverage']) && $check_key === 'canonical') : ?>
                            <div style="margin-top: 8px; font-size: 12px;">
                                <strong style="color: #1d2327;">Canonical coverage (for manual review):</strong>
                                <table class="widefat striped" style="margin-top: 6px; max-width: 100%; font-size: 12px;">
                                    <thead>
                                        <tr>
                                            <th>Page</th>
                                            <th>Canonicals</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($check['coverage'] as $row) : ?>
                                        <tr>
                                            <td><?php echo esc_html($row['label']); ?></td>
                                            <td><?php echo $row['error'] ? esc_html($row['error']) : ( (int) $row['count'] ); ?></td>
                                            <td style="word-break: break-all;"><?php echo $row['href'] ? esc_html($row['href']) : '—'; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (empty($check['issues']) && empty($check['recommendations'])) : ?>
                            <span style="color: #00a32a;">All checks passed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($check['status'] !== 'pass') : ?>
                                <?php
                                // Add fix buttons for specific checks
                                if ($check_key === 'search_visibility' && $check['status'] !== 'pass') :
                                ?>
                                <form method="post" style="margin: 0;">
                                    <?php wp_nonce_field('cta_seo_fix_action'); ?>
                                    <input type="hidden" name="cta_seo_fix_action" value="fix_search_visibility">
                                    <button type="submit" class="button button-small button-primary">
                                        <span class="dashicons dashicons-yes" style="vertical-align: middle; font-size: 14px;"></span>
                                        Enable Indexing
                                    </button>
                                </form>
                                <?php elseif ($check_key === 'permalink_structure' && $check['status'] !== 'pass') : ?>
                                <form method="post" style="margin: 0;">
                                    <?php wp_nonce_field('cta_seo_fix_action'); ?>
                                    <input type="hidden" name="cta_seo_fix_action" value="fix_permalinks">
                                    <button type="submit" class="button button-small button-primary">
                                        <span class="dashicons dashicons-admin-links" style="vertical-align: middle; font-size: 14px;"></span>
                                        Fix Permalinks
                                    </button>
                                </form>
                                <?php elseif ($check_key === 'robots_txt' && $check['status'] !== 'pass') : ?>
                                <form method="post" style="margin: 0;">
                                    <?php wp_nonce_field('cta_seo_fix_action'); ?>
                                    <input type="hidden" name="cta_seo_fix_action" value="generate_robots">
                                    <button type="submit" class="button button-small button-primary">
                                        <span class="dashicons dashicons-media-code" style="vertical-align: middle; font-size: 14px;"></span>
                                        Generate robots.txt
                                    </button>
                                </form>
                                <?php elseif ($check_key === 'trustpilot' && $check['status'] !== 'pass') : ?>
                                <button type="button" class="button button-small" onclick="document.getElementById('trustpilot-form').style.display='block'; this.style.display='none';">
                                    <span class="dashicons dashicons-star-filled" style="vertical-align: middle; font-size: 14px;"></span>
                                    Configure
                                </button>
                                <?php elseif ($check_key === 'xml_sitemap' && $check['status'] !== 'pass') : ?>
                                <form method="post" style="margin: 0;">
                                    <?php wp_nonce_field('cta_seo_fix_action'); ?>
                                    <input type="hidden" name="cta_seo_fix_action" value="regenerate_sitemap">
                                    <button type="submit" class="button button-small button-primary">
                                        <span class="dashicons dashicons-update" style="vertical-align: middle; font-size: 14px;"></span>
                                        Regenerate
                                    </button>
                                </form>
                                <?php else : ?>
                                <a href="<?php echo admin_url('customize.php'); ?>" class="button button-small">
                                    Configure
                                </a>
                                <?php endif; ?>
                            <?php else : ?>
                            <span class="cta-seo-status cta-seo-status--pass">✓ OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Trustpilot Quick Config Form (hidden by default) -->
        <div id="trustpilot-form" class="cta-seo-section" style="display: none;">
            <h2><span class="dashicons dashicons-star-filled" style="color: #00b67a;"></span> Configure Trustpilot</h2>
            <p>Enter your Trustpilot rating and review count to display trust signals on your site.</p>
            <form method="post">
                <?php wp_nonce_field('cta_seo_fix_action'); ?>
                <input type="hidden" name="cta_seo_fix_action" value="fix_trustpilot">
                <table class="form-table">
                    <tr>
                        <th><label for="trustpilot_rating">Rating (out of 5)</label></th>
                        <td>
                            <input type="number" name="trustpilot_rating" id="trustpilot_rating" 
                                   min="0" max="5" step="0.1" value="4.8" 
                                   style="width: 100px;" required>
                            <p class="description">Example: 4.8</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="trustpilot_count">Number of Reviews</label></th>
                        <td>
                            <input type="number" name="trustpilot_count" id="trustpilot_count" 
                                   min="1" value="100" 
                                   style="width: 100px;" required>
                            <p class="description">Example: 250</p>
                        </td>
                    </tr>
                </table>
                <button type="submit" class="button button-primary">Save Trustpilot Settings</button>
                <button type="button" class="button" onclick="document.getElementById('trustpilot-form').style.display='none';">Cancel</button>
            </form>
        </div>
        
        <!-- SEO Configuration Tools -->
        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">SEO Configuration</h2>
            <div class="cta-seo-section__body">
            <p>Configure advanced SEO settings for your site.</p>
            
            <?php
            $schema_enabled = get_option('cta_enable_schema_markup', true);
            $canonical_enabled = get_option('cta_enable_canonical_urls', true);
            $default_title_suffix = get_option('cta_default_title_suffix', '| Continuity Training Academy');
            $default_description = get_option('cta_default_meta_description', '');
            $og_image = get_option('cta_default_og_image', '');
            ?>
            
            <table class="cta-seo-table widefat">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Status</th>
                        <th>Action</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Schema Markup</strong></td>
                        <td>
                            <?php if ($schema_enabled) : ?>
                                <span style="color: #00a32a;">✓ Enabled</span>
                            <?php else : ?>
                                <span style="color: #d63638;">● Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('cta_seo_fix_action'); ?>
                                <input type="hidden" name="cta_seo_fix_action" value="<?php echo $schema_enabled ? 'disable_schema_markup' : 'enable_schema_markup'; ?>">
                                <button type="submit" class="button button-small">
                                    <?php echo $schema_enabled ? 'Disable' : 'Enable'; ?>
                                </button>
                            </form>
                        </td>
                        <td>Adds structured data for courses, organization, and breadcrumbs</td>
                    </tr>
                    <tr>
                        <td><strong>Canonical URLs</strong></td>
                        <td>
                            <?php if ($canonical_enabled) : ?>
                                <span style="color: #00a32a;">✓ Enabled</span>
                            <?php else : ?>
                                <span style="color: #d63638;">● Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('cta_seo_fix_action'); ?>
                                <input type="hidden" name="cta_seo_fix_action" value="<?php echo $canonical_enabled ? 'disable_canonical_urls' : 'enable_canonical_urls'; ?>">
                                <button type="submit" class="button button-small">
                                    <?php echo $canonical_enabled ? 'Disable' : 'Enable'; ?>
                                </button>
                            </form>
                        </td>
                        <td>Prevents duplicate content issues with clean canonical URLs</td>
                    </tr>
                    <tr>
                        <td><strong>Default Meta Tags</strong></td>
                        <td>
                            <?php if (!empty($default_title_suffix) && !empty($default_description)) : ?>
                                <span style="color: #00a32a;">✓ Configured</span>
                            <?php else : ?>
                                <span style="color: #dba617;">⚠ Not Set</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small" onclick="document.getElementById('meta-config-form').style.display='block'; this.style.display='none';">
                                <span class="dashicons dashicons-edit" style="vertical-align: middle; font-size: 14px;"></span>
                                Configure
                            </button>
                        </td>
                        <td>Fallback title suffix and description for pages without custom meta</td>
                    </tr>
                    <tr>
                        <td><strong>Social Media Tags</strong></td>
                        <td>
                            <?php if (!empty($og_image)) : ?>
                                <span style="color: #00a32a;">✓ Configured</span>
                            <?php else : ?>
                                <span style="color: #00a32a;">✓ Auto-populated</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small" onclick="document.getElementById('social-config-form').style.display='block'; this.style.display='none';">
                                <span class="dashicons dashicons-share" style="vertical-align: middle; font-size: 14px;"></span>
                                Configure
                            </button>
                        </td>
                        <td><?php echo !empty($og_image) ? 'Default share image set for Facebook, LinkedIn.' : 'Auto-populated from page title, description, and featured image; set a default image below to override.'; ?></td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
        
        <div id="meta-config-form" class="cta-seo-section" style="display: none;">
            <h2 class="cta-seo-section__title">Configure Default Meta Tags</h2>
            <div class="cta-seo-section__body">
            <p>These will be used as fallbacks when pages don't have custom meta tags.</p>
            <form method="post">
                <?php wp_nonce_field('cta_seo_fix_action'); ?>
                <input type="hidden" name="cta_seo_fix_action" value="configure_default_meta">
                <table class="form-table">
                    <tr>
                        <th><label for="default_title_suffix">Title Suffix</label></th>
                        <td>
                            <input type="text" name="default_title_suffix" id="default_title_suffix" 
                                   value="<?php echo esc_attr($default_title_suffix); ?>" 
                                   style="width: 400px;" required>
                            <p class="description">Added to the end of page titles. Example: "| Continuity Training Academy"</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="default_description">Default Description</label></th>
                        <td>
                            <textarea name="default_description" id="default_description" 
                                      rows="3" style="width: 400px;" required><?php echo esc_textarea($default_description); ?></textarea>
                            <p class="description">Used when a page doesn't have a custom description (max 160 characters recommended)</p>
                        </td>
                    </tr>
                </table>
                <button type="submit" class="button button-primary">Save Default Meta Tags</button>
                <button type="button" class="button" onclick="document.getElementById('meta-config-form').style.display='none';">Cancel</button>
            </form>
            </div>
        </div>
        
        <div id="social-config-form" class="cta-seo-section" style="display: none;">
            <h2 class="cta-seo-section__title">Configure Social Media Tags</h2>
            <div class="cta-seo-section__body">
            <p>Default share image for Facebook, LinkedIn. Your site links to Trustpilot, Facebook, LinkedIn and Instagram from the theme.</p>
            <form method="post">
                <?php wp_nonce_field('cta_seo_fix_action'); ?>
                <input type="hidden" name="cta_seo_fix_action" value="configure_social_meta">
                <table class="form-table">
                    <tr>
                        <th><label for="og_default_image">Default OG Image URL</label></th>
                        <td>
                            <input type="url" name="og_default_image" id="og_default_image" 
                                   value="<?php echo esc_attr($og_image); ?>" 
                                   style="width: 400px;" placeholder="https://example.com/image.jpg">
                            <p class="description">Default image when a page has no featured image. Recommended: 1200×630px.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fb_app_id">Facebook App ID</label></th>
                        <td>
                            <input type="text" name="fb_app_id" id="fb_app_id" 
                                   value="<?php echo esc_attr(get_option('cta_facebook_app_id', '')); ?>" 
                                   style="width: 200px;" placeholder="123456789">
                            <p class="description">Optional: For Facebook Insights</p>
                        </td>
                    </tr>
                </table>
                <button type="submit" class="button button-primary">Save Social Media Settings</button>
                <button type="button" class="button" onclick="document.getElementById('social-config-form').style.display='none';">Cancel</button>
            </form>
            </div>
        </div>
        
        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Quick Links</h2>
            <div class="cta-seo-section__body">
            <div class="cta-seo-actions">
                <a href="<?php echo admin_url('admin.php?page=cta-seo-sitemap-diagnostic'); ?>" class="button">
                    <span class="dashicons dashicons-admin-site" style="vertical-align: middle;"></span>
                    Sitemap Diagnostic
                </a>
                <a href="<?php echo admin_url('admin.php?page=cta-seo-performance'); ?>" class="button">
                    <span class="dashicons dashicons-performance" style="vertical-align: middle;"></span>
                    Performance
                </a>
                <a href="https://search.google.com/test/rich-results?url=<?php echo urlencode(home_url()); ?>" target="_blank" class="button">
                    <span class="dashicons dashicons-external" style="vertical-align: middle;"></span>
                    Test Rich Results
                </a>
                <a href="https://search.google.com/search-console" target="_blank" class="button">
                    <span class="dashicons dashicons-external" style="vertical-align: middle;"></span>
                    Search Console
                </a>
            </div>
            </div>
        </div>
        
        <div class="cta-seo-section">
            <h2 class="cta-seo-section__title">Export Verification Report</h2>
            <div class="cta-seo-section__body">
            <p>Download a detailed verification report for your records.</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('cta_export_verification'); ?>
                <input type="hidden" name="action" value="cta_export_seo_verification">
                <button type="submit" class="button">
                    <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                    Export as JSON
                </button>
            </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Handle verification report export
 */
function cta_export_seo_verification() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    check_admin_referer('cta_export_verification');
    
    $results = cta_run_seo_verification();
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="seo-verification-' . date('Y-m-d') . '.json"');
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}
add_action('admin_post_cta_export_seo_verification', 'cta_export_seo_verification');
