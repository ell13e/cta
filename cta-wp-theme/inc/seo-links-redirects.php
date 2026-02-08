<?php
/**
 * SEO Links Configuration
 *
 * - Strip Category Base (remove /category/ from URLs; toggle in SEO → Redirects)
 * - Redirect Attachments (redirect attachment pages to parent posts; toggle in SEO → Redirects)
 * - External link attributes (target="_blank", rel="noopener noreferrer")
 * - Image SEO (auto alt text)
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * =========================================
 * ATTACHMENT REDIRECTS
 * =========================================
 */

/**
 * Redirect attachment pages to parent post (or homepage if orphan).
 * Can be disabled in SEO → Redirects.
 */
function cta_redirect_attachments() {
    if (!(bool) get_option('cta_redirect_attachments_enabled', 1)) {
        return;
    }
    if (!is_attachment()) {
        return;
    }

    global $post;

    $parent_id = $post->post_parent;

    if ($parent_id) {
        $parent_url = get_permalink($parent_id);
        if ($parent_url) {
            wp_redirect($parent_url, 301);
            exit;
        }
    } else {
        wp_redirect(home_url('/'), 301);
        exit;
    }
}
add_action('template_redirect', 'cta_redirect_attachments', 1);

/**
 * =========================================
 * STRIP CATEGORY BASE
 * =========================================
 * Removes /category/ from category URLs (e.g. /category/training/ → /training/).
 * Controlled by SEO → Redirects. When enabled, pages take precedence over
 * categories when a page and a category share the same slug.
 */

/**
 * Use no category base in rewrite rules when strip is enabled.
 * Returning '.' makes WordPress generate rules so category archives live at /slug/.
 */
function cta_strip_category_base_option($value) {
    if (!(bool) get_option('cta_strip_category_base_enabled', 1)) {
        return $value;
    }
    return '.';
}
add_filter('pre_option_category_base', 'cta_strip_category_base_option');

/**
 * Give pages precedence over category archives when both use the same slug.
 */
function cta_page_precedence_over_stripped_category() {
    if (!(bool) get_option('cta_strip_category_base_enabled', 1)) {
        return;
    }
    $GLOBALS['wp_rewrite']->use_verbose_page_rules = true;
}
add_action('init', 'cta_page_precedence_over_stripped_category', 1);

/**
 * Collect page rewrite rules so we can prepend them in rewrite_rules_array.
 */
function cta_collect_page_rules_for_precedence($page_rewrite_rules) {
    if (!(bool) get_option('cta_strip_category_base_enabled', 1)) {
        return $page_rewrite_rules;
    }
    $GLOBALS['cta_page_rewrite_rules'] = $page_rewrite_rules;
    return [];
}
add_filter('page_rewrite_rules', 'cta_collect_page_rules_for_precedence');

/**
 * Prepend page rules so they are matched before category rules.
 */
function cta_prepend_page_rewrite_rules($rewrite_rules) {
    if (!(bool) get_option('cta_strip_category_base_enabled', 1)) {
        return $rewrite_rules;
    }
    if (empty($GLOBALS['cta_page_rewrite_rules'])) {
        return $rewrite_rules;
    }
    return $GLOBALS['cta_page_rewrite_rules'] + $rewrite_rules;
}
add_filter('rewrite_rules_array', 'cta_prepend_page_rewrite_rules');

/**
 * Ensure category term links are generated without /category/ prefix.
 * Builds full path for hierarchical categories (e.g. /training/advanced/).
 */
function cta_fix_category_link($url, $term, $taxonomy) {
    if ($taxonomy !== 'category') {
        return $url;
    }
    if (!(bool) get_option('cta_strip_category_base_enabled', 1)) {
        return $url;
    }
    $slugs = [];
    $current = $term;
    while ($current && !is_wp_error($current)) {
        array_unshift($slugs, $current->slug);
        if (empty($current->parent)) {
            break;
        }
        $current = get_term($current->parent, 'category');
    }
    $path = implode('/', $slugs) . '/';
    return trailingslashit(home_url('/')) . $path;
}
add_filter('term_link', 'cta_fix_category_link', 10, 3);

/**
 * =========================================
 * EXTERNAL LINKS CONFIGURATION
 * =========================================
 */

/**
 * Add target="_blank" to external links (opens in new tab)
 * Note: NoFollow is OFF as requested (external links help SEO)
 */
function cta_add_external_link_attributes($content) {
    if (empty($content)) {
        return $content;
    }
    
    // Don't modify in admin
    if (is_admin()) {
        return $content;
    }
    
    // Get site URL for comparison
    $site_url = home_url();
    $site_domain = parse_url($site_url, PHP_URL_HOST);
    
    // Find all links
    $pattern = '/<a\s+([^>]*href=["\']([^"\']*)["\'][^>]*)>/i';
    
    $content = preg_replace_callback($pattern, function($matches) use ($site_domain) {
        $full_tag = $matches[0];
        $attributes = $matches[1];
        $url = $matches[2];
        
        // Skip if already has target attribute
        if (strpos($attributes, 'target=') !== false) {
            return $full_tag;
        }
        
        // Skip anchor links and mailto/tel
        if (strpos($url, '#') === 0 || strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0) {
            return $full_tag;
        }
        
        // Check if external link
        $url_domain = parse_url($url, PHP_URL_HOST);
        $is_external = $url_domain && $url_domain !== $site_domain;
        
        if ($is_external) {
            // Add target="_blank" and rel="noopener noreferrer" for security
            $new_attributes = rtrim($attributes);
            if (strpos($attributes, 'rel=') === false) {
                $new_attributes .= ' rel="noopener noreferrer"';
            } else {
                // Add noopener if not already present
                if (strpos($attributes, 'noopener') === false) {
                    $new_attributes = preg_replace('/rel=["\']([^"\']*)["\']/', 'rel="$1 noopener noreferrer"', $new_attributes);
                }
            }
            $new_attributes .= ' target="_blank"';
            
            return '<a ' . $new_attributes . '>';
        }
        
        return $full_tag;
    }, $content);
    
    return $content;
}
add_filter('the_content', 'cta_add_external_link_attributes', 99);
add_filter('widget_text', 'cta_add_external_link_attributes', 99);

/**
 * =========================================
 * IMAGE SEO - AUTO ALT TEXT
 * =========================================
 */

/**
 * Auto-generate alt text from filename if missing
 * Format: %filename% (cleaned and formatted)
 * Runs with lower priority (20) so course-specific alt text takes precedence
 */
function cta_auto_generate_alt_text($attr, $attachment, $size) {
    // Only if alt text is missing (after other filters have run)
    if (!empty($attr['alt'])) {
        return $attr;
    }
    
    // Get attachment filename
    $filename = get_post_meta($attachment->ID, '_wp_attached_file', true);
    if (empty($filename)) {
        $file_path = get_attached_file($attachment->ID);
        if ($file_path) {
            $filename = basename($file_path);
        }
    }
    
    if (empty($filename)) {
        return $attr;
    }
    
    // Remove extension and clean up
    $filename = pathinfo($filename, PATHINFO_FILENAME);
    $filename = str_replace(['-', '_'], ' ', $filename);
    $filename = ucwords(strtolower($filename));
    
    // Set alt text
    $attr['alt'] = $filename;
    
    // Also save to attachment meta for future use
    if (empty(get_post_meta($attachment->ID, '_wp_attachment_image_alt', true))) {
        update_post_meta($attachment->ID, '_wp_attachment_image_alt', $filename);
    }
    
    return $attr;
}
// Priority 20 so course-specific alt text (priority 10) takes precedence
add_filter('wp_get_attachment_image_attributes', 'cta_auto_generate_alt_text', 20, 3);

/**
 * Auto-generate alt text when image is uploaded
 */
function cta_auto_alt_on_upload($post_id) {
    // Only for images
    if (!wp_attachment_is_image($post_id)) {
        return;
    }
    
    // Only if alt text is missing
    $existing_alt = get_post_meta($post_id, '_wp_attachment_image_alt', true);
    if (!empty($existing_alt)) {
        return;
    }
    
    // Get filename
    $file = get_attached_file($post_id);
    if (!$file) {
        return;
    }
    
    $filename = basename($file);
    $filename = pathinfo($filename, PATHINFO_FILENAME);
    $filename = str_replace(['-', '_'], ' ', $filename);
    $filename = ucwords(strtolower($filename));
    
    // Save alt text
    update_post_meta($post_id, '_wp_attachment_image_alt', $filename);
}
add_action('add_attachment', 'cta_auto_alt_on_upload');
add_action('edit_attachment', 'cta_auto_alt_on_upload');

/**
 * Bulk add alt text to existing images without alt text
 */
function cta_bulk_add_alt_text() {
    $images = get_posts([
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_wp_attachment_image_alt',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ]);
    
    $count = 0;
    foreach ($images as $image) {
        $file = get_attached_file($image->ID);
        if (!$file) {
            continue;
        }
        
        $filename = basename($file);
        $filename = pathinfo($filename, PATHINFO_FILENAME);
        $filename = str_replace(['-', '_'], ' ', $filename);
        $filename = ucwords(strtolower($filename));
        
        update_post_meta($image->ID, '_wp_attachment_image_alt', $filename);
        $count++;
    }
    
    return $count;
}

/**
 * Admin notice for bulk alt text tool
 */
function cta_bulk_alt_text_admin_notice() {
    if (isset($_GET['cta_bulk_alt_done'])) {
        $count = intval($_GET['cta_bulk_alt_done']);
        ?>
        <div class="notice notice-success is-dismissible">
            <p>✅ Successfully added alt text to <?php echo $count; ?> images.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'cta_bulk_alt_text_admin_notice');

/**
 * Add bulk alt text tool to admin
 */
function cta_add_bulk_alt_text_tool() {
    if (isset($_GET['cta_bulk_alt_text']) && current_user_can('manage_options')) {
        check_admin_referer('cta_bulk_alt_text');
        $count = cta_bulk_add_alt_text();
        wp_redirect(admin_url('upload.php?cta_bulk_alt_done=' . $count));
        exit;
    }
}
add_action('admin_init', 'cta_add_bulk_alt_text_tool');

/**
 * Add bulk alt text button to media library
 */
function cta_add_bulk_alt_text_button($views) {
    if (current_user_can('manage_options')) {
        $url = wp_nonce_url(admin_url('upload.php?cta_bulk_alt_text=1'), 'cta_bulk_alt_text');
        $views['cta_bulk_alt'] = '<a href="' . esc_url($url) . '" class="button">Add Alt Text to All Images</a>';
    }
    return $views;
}
add_filter('views_upload', 'cta_add_bulk_alt_text_button');