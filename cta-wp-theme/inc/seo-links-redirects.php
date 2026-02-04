<?php
/**
 * SEO Links & Redirects Configuration
 * 
 * Implements Rank Math-style link and redirect settings:
 * - Strip Category Base (remove /category/ from URLs)
 * - Redirect Attachments (redirect attachment pages to parent posts)
 * - Redirect Orphan Attachments (to homepage)
 * - Open External Links in New Tab
 * - Auto-generate Image Alt Text from filename
 * 
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * =========================================
 * REDIRECTIONS SYSTEM
 * =========================================
 */

/**
 * Store redirects in custom table or post meta
 * Using post meta for simplicity (can be upgraded to custom table later)
 */
function cta_create_redirects_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cta_redirects';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        source_url varchar(255) NOT NULL,
        target_url varchar(255) NOT NULL,
        redirect_type tinyint(3) unsigned NOT NULL DEFAULT 301,
        status varchar(20) NOT NULL DEFAULT 'active',
        hit_count bigint(20) unsigned NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY source_url (source_url),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'cta_create_redirects_table');

/**
 * Auto-redirect on slug change
 */
function cta_auto_redirect_on_slug_change($post_id, $post_after, $post_before) {
    // Only for published posts/pages
    if ($post_after->post_status !== 'publish' || $post_before->post_status !== 'publish') {
        return;
    }
    
    // Only if slug changed
    if ($post_after->post_name === $post_before->post_name) {
        return;
    }
    
    // Build old and new URLs
    $old_url = get_permalink($post_before);
    $new_url = get_permalink($post_after);
    
    // Only create redirect if URLs are different
    if ($old_url !== $new_url) {
        cta_create_redirect($old_url, $new_url, 301);
    }
}
add_action('post_updated', 'cta_auto_redirect_on_slug_change', 10, 3);

/**
 * Create a redirect
 */
function cta_create_redirect($source_url, $target_url, $type = 301) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cta_redirects';
    
    // Normalize URLs (remove trailing slashes, convert to relative)
    $source_url = cta_normalize_redirect_url($source_url);
    $target_url = cta_normalize_redirect_url($target_url);
    
    // Check if redirect already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE source_url = %s",
        $source_url
    ));
    
    if ($existing) {
        // Update existing redirect
        $wpdb->update(
            $table_name,
            [
                'target_url' => $target_url,
                'redirect_type' => $type,
                'status' => 'active',
            ],
            ['id' => $existing],
            ['%s', '%d', '%s'],
            ['%d']
        );
    } else {
        // Create new redirect
        $wpdb->insert(
            $table_name,
            [
                'source_url' => $source_url,
                'target_url' => $target_url,
                'redirect_type' => $type,
                'status' => 'active',
            ],
            ['%s', '%s', '%d', '%s']
        );
    }
}

/**
 * Normalize redirect URL (convert to relative, remove trailing slash)
 */
function cta_normalize_redirect_url($url) {
    $url = str_replace(home_url(), '', $url);
    $url = rtrim($url, '/');
    if (empty($url)) {
        $url = '/';
    }
    return $url;
}

/**
 * Process redirects (check and redirect if match found)
 */
function cta_process_redirects() {
    // Don't redirect in admin
    if (is_admin()) {
        return;
    }
    
    // Don't redirect if already redirected
    if (headers_sent()) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'cta_redirects';
    
    // Get current request URI
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $request_uri = parse_url($request_uri, PHP_URL_PATH);
    $request_uri = rtrim($request_uri, '/');
    if (empty($request_uri)) {
        $request_uri = '/';
    }
    
    // Check for redirect
    $redirect = $wpdb->get_row($wpdb->prepare(
        "SELECT target_url, redirect_type FROM $table_name 
         WHERE source_url = %s AND status = 'active'",
        $request_uri
    ));
    
    if ($redirect) {
        // Increment hit count
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET hit_count = hit_count + 1 WHERE source_url = %s",
            $request_uri
        ));
        
        // Build full target URL
        $target_url = $redirect->target_url;
        if (strpos($target_url, 'http') !== 0) {
            $target_url = home_url($target_url);
        }
        
        // Perform redirect
        wp_redirect($target_url, $redirect->redirect_type);
        exit;
    }
}
add_action('template_redirect', 'cta_process_redirects', 1);

/**
 * =========================================
 * STRIP CATEGORY BASE
 * =========================================
 */

/**
 * Remove /category/ from category URLs
 */
function cta_strip_category_base($rules) {
    $category_base = get_option('category_base');
    if ($category_base && $category_base !== 'category') {
        return $rules; // Custom category base, don't modify
    }
    
    // Remove category base from rewrite rules
    foreach ($rules as $pattern => $rewrite) {
        if (strpos($pattern, 'category/') !== false) {
            $new_pattern = str_replace('category/', '', $pattern);
            unset($rules[$pattern]);
            $rules[$new_pattern] = $rewrite;
        }
    }
    
    return $rules;
}
add_filter('category_rewrite_rules', 'cta_strip_category_base');

/**
 * Fix category links to not include /category/
 */
function cta_fix_category_link($termlink, $term, $taxonomy) {
    if ($taxonomy === 'category') {
        $category_base = get_option('category_base');
        if ($category_base === 'category' || empty($category_base)) {
            // Remove /category/ from link
            $termlink = str_replace('/category/', '/', $termlink);
        }
    }
    return $termlink;
}
add_filter('term_link', 'cta_fix_category_link', 10, 3);

/**
 * =========================================
 * ATTACHMENT REDIRECTS
 * =========================================
 */

/**
 * Redirect attachment pages to parent post
 */
function cta_redirect_attachments() {
    if (!is_attachment()) {
        return;
    }
    
    global $post;
    
    // Get parent post
    $parent_id = $post->post_parent;
    
    if ($parent_id) {
        // Redirect to parent post
        $parent_url = get_permalink($parent_id);
        if ($parent_url) {
            wp_redirect($parent_url, 301);
            exit;
        }
    } else {
        // Orphan attachment - redirect to homepage
        wp_redirect(home_url('/'), 301);
        exit;
    }
}
add_action('template_redirect', 'cta_redirect_attachments', 1);

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

/**
 * =========================================
 * REDIRECTS ADMIN INTERFACE
 * =========================================
 */

/**
 * Add redirects admin page
 * NOTE: Now in SEO admin section, but keeping function for backward compatibility
 */
function cta_add_redirects_admin_page() {
    // Removed from Tools menu - now in SEO section
    // Function still available for use in SEO admin section
}
// Removed action - menu now in SEO section
// add_action('admin_menu', 'cta_add_redirects_admin_page');

/**
 * Redirects admin page
 */
function cta_redirects_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cta_redirects';
    
    // Handle form submissions
    if (isset($_POST['cta_add_redirect']) && check_admin_referer('cta_redirects')) {
        $source = sanitize_text_field($_POST['source_url']);
        $target = sanitize_text_field($_POST['target_url']);
        $type = intval($_POST['redirect_type']);
        
        if (!empty($source) && !empty($target)) {
            cta_create_redirect($source, $target, $type);
            echo '<div class="notice notice-success"><p>Redirect added successfully!</p></div>';
        }
    }
    
    if (isset($_GET['delete']) && check_admin_referer('cta_delete_redirect')) {
        $id = intval($_GET['delete']);
        $wpdb->delete($table_name, ['id' => $id], ['%d']);
        echo '<div class="notice notice-success"><p>Redirect deleted!</p></div>';
    }
    
    // Get all redirects
    $redirects = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    
    ?>
    <div class="wrap">
        <h1>Redirects Management</h1>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <!-- Add Redirect Form -->
            <div>
                <h2>Add New Redirect</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('cta_redirects'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="source_url">Source URL</label></th>
                            <td>
                                <input type="text" id="source_url" name="source_url" class="regular-text" 
                                       placeholder="/old-page/" required>
                                <p class="description">Old URL (relative path, e.g., /old-page/)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="target_url">Target URL</label></th>
                            <td>
                                <input type="text" id="target_url" name="target_url" class="regular-text" 
                                       placeholder="/new-page/" required>
                                <p class="description">New URL (relative path or full URL)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="redirect_type">Redirect Type</label></th>
                            <td>
                                <select id="redirect_type" name="redirect_type">
                                    <option value="301">301 Permanent</option>
                                    <option value="302">302 Temporary</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="cta_add_redirect" class="button button-primary" value="Add Redirect">
                    </p>
                </form>
            </div>
            
            <!-- Redirects List -->
            <div>
                <h2>Existing Redirects (<?php echo count($redirects); ?>)</h2>
                <?php if (empty($redirects)) : ?>
                    <p>No redirects configured yet.</p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Source URL</th>
                                <th>Target URL</th>
                                <th>Type</th>
                                <th>Hits</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($redirects as $redirect) : ?>
                                <tr>
                                    <td><code><?php echo esc_html($redirect->source_url); ?></code></td>
                                    <td><code><?php echo esc_html($redirect->target_url); ?></code></td>
                                    <td><?php echo esc_html($redirect->redirect_type); ?></td>
                                    <td><?php echo number_format($redirect->hit_count); ?></td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url(admin_url('tools.php?page=cta-redirects&delete=' . $redirect->id), 'cta_delete_redirect'); ?>" 
                                           class="button button-small" 
                                           onclick="return confirm('Delete this redirect?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">
            <h3>ℹ️ How It Works</h3>
            <ul>
                <li><strong>Auto-redirects:</strong> When you change a page/post slug, a redirect is automatically created.</li>
                <li><strong>Manual redirects:</strong> Use the form above to create custom redirects.</li>
                <li><strong>Attachment redirects:</strong> Attachment pages automatically redirect to their parent post (or homepage if orphaned).</li>
                <li><strong>Category base:</strong> Category URLs don't include /category/ (cleaner URLs).</li>
            </ul>
        </div>
    </div>
    <?php
}
