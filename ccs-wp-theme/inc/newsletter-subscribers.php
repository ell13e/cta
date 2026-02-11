<?php
/**
 * Newsletter Subscriber Management
 *
 * Stores subscribers in a custom database table and provides
 * admin interface for viewing and emailing them.
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Capability required to manage the Newsletter area.
 *
 * Editors should have access; `manage_options` is too restrictive.
 */
function ccs_newsletter_required_capability() {
    return 'edit_others_posts';
}

/**
 * Centralised permission check for Newsletter admin actions/AJAX.
 */
function ccs_newsletter_user_can_manage() {
    return current_user_can(ccs_newsletter_required_capability());
}

/**
 * Create newsletter tag tables (tags + subscriber ↔ tag relationships).
 */
function ccs_create_newsletter_tag_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tags_table = $wpdb->prefix . 'ccs_newsletter_tags';
    $rel_table = $wpdb->prefix . 'ccs_newsletter_subscriber_tags';

    $tags_sql = "CREATE TABLE IF NOT EXISTS $tags_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(120) NOT NULL,
        slug varchar(140) NOT NULL,
        color varchar(20) DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY name (name)
    ) $charset_collate;";

    $rel_sql = "CREATE TABLE IF NOT EXISTS $rel_table (
        subscriber_id bigint(20) unsigned NOT NULL,
        tag_id bigint(20) unsigned NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (subscriber_id, tag_id),
        KEY tag_id (tag_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($tags_sql);
    dbDelta($rel_sql);
}

/**
 * Return all tags.
 *
 * @return array<int, object> rows with ->id, ->name, ->slug, ->color
 */
function ccs_newsletter_get_tags() {
    global $wpdb;
    $tags_table = $wpdb->prefix . 'ccs_newsletter_tags';
    return $wpdb->get_results("SELECT id, name, slug, color FROM $tags_table ORDER BY name ASC");
}

/**
 * Get tag ids for subscribers on the current page.
 *
 * @param int[] $subscriber_ids
 * @return array<int, int[]> map of subscriber_id => [tag_id, ...]
 */
function ccs_newsletter_get_tag_ids_for_subscribers($subscriber_ids) {
    $subscriber_ids = array_values(array_filter(array_map('absint', (array) $subscriber_ids)));
    if (empty($subscriber_ids)) return [];

    global $wpdb;
    $rel_table = $wpdb->prefix . 'ccs_newsletter_subscriber_tags';
    $placeholders = implode(',', array_fill(0, count($subscriber_ids), '%d'));
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT subscriber_id, tag_id FROM $rel_table WHERE subscriber_id IN ($placeholders)",
        $subscriber_ids
    ));

    $map = [];
    foreach ($rows as $r) {
        $sid = (int) $r->subscriber_id;
        $tid = (int) $r->tag_id;
        if (!isset($map[$sid])) $map[$sid] = [];
        $map[$sid][] = $tid;
    }
    return $map;
}

/**
 * Assign tag(s) to subscriber(s).
 *
 * @param int[] $subscriber_ids
 * @param int[] $tag_ids
 * @return int number of inserts attempted
 */
function ccs_newsletter_assign_tags($subscriber_ids, $tag_ids) {
    $subscriber_ids = array_values(array_filter(array_map('absint', (array) $subscriber_ids)));
    $tag_ids = array_values(array_filter(array_map('absint', (array) $tag_ids)));
    if (empty($subscriber_ids) || empty($tag_ids)) return 0;

    global $wpdb;
    $rel_table = $wpdb->prefix . 'ccs_newsletter_subscriber_tags';

    $inserted = 0;
    foreach ($subscriber_ids as $sid) {
        foreach ($tag_ids as $tid) {
            // Avoid duplicates
            $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO $rel_table (subscriber_id, tag_id) VALUES (%d, %d)",
                $sid,
                $tid
            ));
            $inserted++;
        }
    }
    return $inserted;
}

/**
 * Remove tag(s) from subscriber(s).
 *
 * @param int[] $subscriber_ids
 * @param int[] $tag_ids
 * @return int number of rows deleted
 */
function ccs_newsletter_remove_tags($subscriber_ids, $tag_ids) {
    $subscriber_ids = array_values(array_filter(array_map('absint', (array) $subscriber_ids)));
    $tag_ids = array_values(array_filter(array_map('absint', (array) $tag_ids)));
    if (empty($subscriber_ids) || empty($tag_ids)) return 0;

    global $wpdb;
    $rel_table = $wpdb->prefix . 'ccs_newsletter_subscriber_tags';

    $sid_placeholders = implode(',', array_fill(0, count($subscriber_ids), '%d'));
    $tid_placeholders = implode(',', array_fill(0, count($tag_ids), '%d'));
    return (int) $wpdb->query($wpdb->prepare(
        "DELETE FROM $rel_table WHERE subscriber_id IN ($sid_placeholders) AND tag_id IN ($tid_placeholders)",
        array_merge($subscriber_ids, $tag_ids)
    ));
}

/**
 * Create a tag.
 *
 * @param string $name
 * @param string $color
 * @return int|WP_Error tag id
 */
function ccs_newsletter_create_tag($name, $color = '') {
    global $wpdb;
    $tags_table = $wpdb->prefix . 'ccs_newsletter_tags';

    $name = sanitize_text_field($name);
    if ($name === '') {
        return new WP_Error('empty_name', 'Tag name is required.');
    }

    $slug = sanitize_title($name);
    if ($slug === '') {
        $slug = 'tag-' . wp_generate_uuid4();
    }

    // Normalise colour (optional)
    $color = sanitize_text_field($color);
    if ($color !== '' && !preg_match('/^#?[0-9a-fA-F]{6}$/', $color)) {
        return new WP_Error('invalid_color', 'Invalid colour value.');
    }
    if ($color !== '' && $color[0] !== '#') {
        $color = '#' . $color;
    }

    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $tags_table WHERE slug = %s", $slug));
    if ($exists) {
        return new WP_Error('duplicate', 'A tag with that name already exists.');
    }

    $wpdb->insert($tags_table, [
        'name' => $name,
        'slug' => $slug,
        'color' => $color ?: null,
    ], ['%s', '%s', '%s']);

    return (int) $wpdb->insert_id;
}

/**
 * Create the subscribers table on theme activation
 */
function ccs_create_subscribers_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        first_name varchar(100) DEFAULT NULL,
        last_name varchar(100) DEFAULT NULL,
        date_of_birth date DEFAULT NULL,
        subscribed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_address varchar(45) DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        unsubscribed_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY email (email),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add new columns if they don't exist (for existing installs)
    $columns = $wpdb->get_col("DESC $table_name", 0);
    if (!in_array('first_name', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN first_name varchar(100) DEFAULT NULL AFTER email");
    }
    if (!in_array('last_name', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN last_name varchar(100) DEFAULT NULL AFTER first_name");
    }
    if (!in_array('date_of_birth', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN date_of_birth date DEFAULT NULL AFTER last_name");
    }
    if (!in_array('last_birthday_email_year', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN last_birthday_email_year int(4) DEFAULT NULL AFTER date_of_birth");
    }
    
    // Verify all existing subscribers have IDs (safety check)
    ccs_verify_subscriber_ids();
    
    // Create tracking tables
    ccs_create_email_tracking_tables();

    // Create tags tables
    ccs_create_newsletter_tag_tables();
    
    // Create unsubscribe page if it doesn't exist
    ccs_create_unsubscribe_page();
}
add_action('after_switch_theme', 'ccs_create_subscribers_table');
add_action('after_switch_theme', 'ccs_migrate_existing_data_to_newsletter', 20);

/**
 * Create unsubscribe page if it doesn't exist
 */
function ccs_create_unsubscribe_page() {
    $page = get_page_by_path('unsubscribe');
    
    if (!$page) {
        $page_data = [
            'post_title' => 'Unsubscribe',
            'post_name' => 'unsubscribe',
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'page_template' => 'page-templates/page-unsubscribe.php'
        ];
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'page-templates/page-unsubscribe.php');
        }
    }
}

/**
 * Create email tracking tables
 */
function ccs_create_email_tracking_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Campaigns table
    $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
    $campaigns_sql = "CREATE TABLE IF NOT EXISTS $campaigns_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        subject varchar(255) NOT NULL,
        sent_at datetime NOT NULL,
        total_sent int(11) NOT NULL DEFAULT 0,
        total_opened int(11) NOT NULL DEFAULT 0,
        total_clicked int(11) NOT NULL DEFAULT 0,
        unique_opens int(11) NOT NULL DEFAULT 0,
        unique_clicks int(11) NOT NULL DEFAULT 0,
        status varchar(20) DEFAULT 'completed',
        PRIMARY KEY (id),
        KEY sent_at (sent_at),
        KEY status (status)
    ) $charset_collate;";
    
    // Add status column if it doesn't exist (for existing installs)
    $columns = $wpdb->get_col("DESC $campaigns_table", 0);
    if (!in_array('status', $columns)) {
        $wpdb->query("ALTER TABLE $campaigns_table ADD COLUMN status varchar(20) DEFAULT 'completed' AFTER unique_clicks");
        $wpdb->query("ALTER TABLE $campaigns_table ADD KEY status (status)");
    }
    
    // Opens tracking table
    $opens_table = $wpdb->prefix . 'ccs_email_opens';
    $opens_sql = "CREATE TABLE IF NOT EXISTS $opens_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        campaign_id bigint(20) unsigned NOT NULL,
        subscriber_id bigint(20) unsigned NOT NULL,
        opened_at datetime NOT NULL,
        ip_address varchar(45) DEFAULT NULL,
        user_agent text,
        PRIMARY KEY (id),
        KEY campaign_id (campaign_id),
        KEY subscriber_id (subscriber_id),
        KEY opened_at (opened_at),
        UNIQUE KEY unique_open (campaign_id, subscriber_id)
    ) $charset_collate;";
    
    // Clicks tracking table
    $clicks_table = $wpdb->prefix . 'ccs_email_clicks';
    $clicks_sql = "CREATE TABLE IF NOT EXISTS $clicks_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        campaign_id bigint(20) unsigned NOT NULL,
        subscriber_id bigint(20) unsigned NOT NULL,
        url text NOT NULL,
        clicked_at datetime NOT NULL,
        ip_address varchar(45) DEFAULT NULL,
        user_agent text,
        PRIMARY KEY (id),
        KEY campaign_id (campaign_id),
        KEY subscriber_id (subscriber_id),
        KEY clicked_at (clicked_at)
    ) $charset_collate;";
    
    // Email queue table for large lists
    $queue_table = $wpdb->prefix . 'ccs_email_queue';
    $queue_sql = "CREATE TABLE IF NOT EXISTS $queue_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        campaign_id bigint(20) unsigned NOT NULL,
        subscriber_id bigint(20) unsigned NOT NULL,
        email varchar(255) NOT NULL,
        subject varchar(500) NOT NULL,
        content longtext NOT NULL,
        headers longtext,
        status varchar(20) NOT NULL DEFAULT 'pending',
        attempts int(11) NOT NULL DEFAULT 0,
        last_attempt_at datetime DEFAULT NULL,
        error_message text,
        scheduled_for datetime DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        sent_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY campaign_id (campaign_id),
        KEY subscriber_id (subscriber_id),
        KEY status (status),
        KEY scheduled_for (scheduled_for),
        KEY campaign_status (campaign_id, status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($campaigns_sql);
    dbDelta($opens_sql);
    dbDelta($clicks_sql);
    dbDelta($queue_sql);
}

// Create tracking tables on init if they don't exist
add_action('admin_init', function() {
    global $wpdb;
    $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
    if ($wpdb->get_var("SHOW TABLES LIKE '$campaigns_table'") !== $campaigns_table) {
        ccs_create_email_tracking_tables();
    }
});

// Also run on init if table doesn't exist (for existing installs)
function ccs_maybe_create_subscribers_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        ccs_create_subscribers_table();
    }
}
add_action('admin_init', 'ccs_maybe_create_subscribers_table');

// Ensure tag tables exist on existing installs
add_action('admin_init', function() {
    global $wpdb;
    $tags_table = $wpdb->prefix . 'ccs_newsletter_tags';
    if ($wpdb->get_var("SHOW TABLES LIKE '$tags_table'") !== $tags_table) {
        ccs_create_newsletter_tag_tables();
    }
});

/**
 * Add subscriber to database
 */
function ccs_add_newsletter_subscriber($email, $ip = '', $first_name = '', $last_name = '', $date_of_birth = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    
    // Check if already exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE email = %s",
        $email
    ));
    
    if ($existing) {
        // Reactivate if previously unsubscribed
        if ($existing->status === 'unsubscribed') {
            $update_data = [
                'status' => 'active',
                'subscribed_at' => current_time('mysql'),
                'unsubscribed_at' => null
            ];
            $update_format = ['%s', '%s', '%s'];
            
            // Update name/DOB if provided
            if (!empty($first_name)) {
                $update_data['first_name'] = sanitize_text_field($first_name);
                $update_format[] = '%s';
            }
            if (!empty($last_name)) {
                $update_data['last_name'] = sanitize_text_field($last_name);
                $update_format[] = '%s';
            }
            if (!empty($date_of_birth)) {
                $update_data['date_of_birth'] = sanitize_text_field($date_of_birth);
                $update_format[] = '%s';
            }
            
            $wpdb->update(
                $table_name,
                $update_data,
                ['id' => $existing->id],
                $update_format,
                ['%d']
            );
            return 'reactivated';
        }
        
        // Update name/DOB if provided and subscriber already exists
        if (!empty($first_name) || !empty($last_name) || !empty($date_of_birth)) {
            $update_data = [];
            $update_format = [];
            
            if (!empty($first_name)) {
                $update_data['first_name'] = sanitize_text_field($first_name);
                $update_format[] = '%s';
            }
            if (!empty($last_name)) {
                $update_data['last_name'] = sanitize_text_field($last_name);
                $update_format[] = '%s';
            }
            if (!empty($date_of_birth)) {
                $update_data['date_of_birth'] = sanitize_text_field($date_of_birth);
                $update_format[] = '%s';
            }
            
            if (!empty($update_data)) {
                $wpdb->update(
                    $table_name,
                    $update_data,
                    ['id' => $existing->id],
                    $update_format,
                    ['%d']
                );
            }
        }
        
        return 'exists';
    }
    
    // Insert new subscriber
    $insert_data = [
        'email' => $email,
        'ip_address' => $ip,
        'subscribed_at' => current_time('mysql'),
        'status' => 'active'
    ];
    $insert_format = ['%s', '%s', '%s', '%s'];
    
    if (!empty($first_name)) {
        $insert_data['first_name'] = sanitize_text_field($first_name);
        $insert_format[] = '%s';
    }
    if (!empty($last_name)) {
        $insert_data['last_name'] = sanitize_text_field($last_name);
        $insert_format[] = '%s';
    }
    if (!empty($date_of_birth)) {
        $insert_data['date_of_birth'] = sanitize_text_field($date_of_birth);
        $insert_format[] = '%s';
    }
    
    $insert_result = $wpdb->insert(
        $table_name,
        $insert_data,
        $insert_format
    );
    
    // Verify insert was successful and ID was assigned
    if ($insert_result === false) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CCS Newsletter: Failed to insert subscriber - ' . $wpdb->last_error);
        }
        return false; // Return false on failure
    }
    
    // Ensure ID was assigned (should be automatic with AUTO_INCREMENT)
    $subscriber_id = $wpdb->insert_id;
    if (empty($subscriber_id)) {
        // Fallback: retrieve the ID we just inserted
        $subscriber_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s ORDER BY subscribed_at DESC LIMIT 1",
            $email
        ));
        
        if (empty($subscriber_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CCS Newsletter: Subscriber inserted but ID not found');
            }
            return false;
        }
    }
    
    // Log successful addition with ID for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('CCS Newsletter: Subscriber added with ID ' . $subscriber_id);
    }

    // If an active automation flow with trigger "subscribes" exists, it will send the welcome (or first step).
    $welcome_handled_by_automation = function_exists('ccs_automation_has_active_subscribes_flow') && ccs_automation_has_active_subscribes_flow();
    if (!$welcome_handled_by_automation) {
        ccs_send_welcome_email($subscriber_id, $email, $first_name);
    }

    do_action('ccs_newsletter_subscriber_added', $subscriber_id, $email, $first_name);

    return 'added';
}

/**
 * Get all active subscribers
 */
function ccs_get_newsletter_subscribers($status = 'active') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    
    // Ensure all subscribers have IDs (safety check)
    ccs_verify_subscriber_ids();
    
    if ($status === 'all') {
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY subscribed_at DESC");
    }
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE status = %s ORDER BY subscribed_at DESC",
        $status
    ));
}

/**
 * Verify all subscribers have IDs and fix any that don't
 * This is a safety function to ensure data integrity
 */
function ccs_verify_subscriber_ids() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        return;
    }
    
    // Check for subscribers without IDs (shouldn't happen with AUTO_INCREMENT, but safety check)
    $subscribers_without_id = $wpdb->get_results(
        "SELECT email FROM $table_name WHERE id IS NULL OR id = 0"
    );
    
    if (!empty($subscribers_without_id)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CCS Newsletter: Found ' . count($subscribers_without_id) . ' subscribers without IDs. This should not happen with AUTO_INCREMENT.');
        }
        
        // This shouldn't happen, but if it does, we need to fix the table structure
        // The table should have AUTO_INCREMENT, so this is a fallback
        foreach ($subscribers_without_id as $sub) {
            // Get the next available ID
            $next_id = $wpdb->get_var("SELECT COALESCE(MAX(id), 0) + 1 FROM $table_name");
            
            // Update the subscriber with the new ID
            $wpdb->update(
                $table_name,
                ['id' => $next_id],
                ['email' => $sub->email],
                ['%d'],
                ['%s']
            );
        }
    }
    
    // Verify the table has AUTO_INCREMENT on the id column
    $column_info = $wpdb->get_row("SHOW COLUMNS FROM $table_name WHERE Field = 'id'");
    if ($column_info && strpos($column_info->Extra, 'auto_increment') === false) {
        // Fix the table structure if AUTO_INCREMENT is missing
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CCS Newsletter: id column missing AUTO_INCREMENT. Attempting to fix...');
        }
        
        // Get current max ID to set AUTO_INCREMENT correctly
        $max_id = $wpdb->get_var("SELECT COALESCE(MAX(id), 0) FROM $table_name");
        $next_id = max(1, $max_id + 1);
        
        // Alter table to add AUTO_INCREMENT
        $wpdb->query("ALTER TABLE $table_name MODIFY id bigint(20) unsigned NOT NULL AUTO_INCREMENT");
        $wpdb->query("ALTER TABLE $table_name AUTO_INCREMENT = $next_id");
    }
}

/**
 * Get subscriber count
 */
function ccs_get_subscriber_count($status = 'active') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE status = %s",
        $status
    ));
}

/**
 * Import newsletter subscribers from CSV
 */
function ccs_import_newsletter_csv($file) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    
    // Security check
    if (!ccs_newsletter_user_can_manage()) {
        return ['success' => false, 'message' => 'Insufficient permissions'];
    }
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        return ['success' => false, 'message' => 'Invalid file type. Please upload a CSV file.'];
    }
    
    // Read CSV
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        return ['success' => false, 'message' => 'Could not read file'];
    }
    
    // Get headers
    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        return ['success' => false, 'message' => 'Could not read CSV headers'];
    }
    
    // Normalize headers (lowercase, trim)
    $headers = array_map(function($h) {
        return strtolower(trim($h));
    }, $headers);
    
    // Find column indices
    $email_idx = array_search('email', $headers);
    if ($email_idx === false) {
        fclose($handle);
        return ['success' => false, 'message' => 'CSV must contain an "email" column'];
    }
    
    $first_name_idx = array_search('first name', $headers);
    if ($first_name_idx === false) {
        $first_name_idx = array_search('firstname', $headers);
    }
    
    $last_name_idx = array_search('last name', $headers);
    if ($last_name_idx === false) {
        $last_name_idx = array_search('lastname', $headers);
    }
    
    $dob_idx = array_search('date of birth', $headers);
    if ($dob_idx === false) {
        $dob_idx = array_search('dob', $headers);
        if ($dob_idx === false) {
            $dob_idx = array_search('birthdate', $headers);
        }
    }
    
    $imported = 0;
    $skipped = 0;
    $errors = [];
    
    // Process rows
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== count($headers)) {
            $skipped++;
            continue;
        }
        
        $email = sanitize_email(trim($row[$email_idx] ?? ''));
        if (empty($email) || !is_email($email)) {
            $skipped++;
            continue;
        }
        
        $first_name = $first_name_idx !== false ? sanitize_text_field(trim($row[$first_name_idx] ?? '')) : '';
        $last_name = $last_name_idx !== false ? sanitize_text_field(trim($row[$last_name_idx] ?? '')) : '';
        $date_of_birth = $dob_idx !== false ? sanitize_text_field(trim($row[$dob_idx] ?? '')) : '';
        
        // Validate date of birth if provided
        if (!empty($date_of_birth)) {
            $dob_timestamp = strtotime($date_of_birth);
            if ($dob_timestamp === false || $dob_timestamp > time()) {
                $date_of_birth = '';
            }
        }
        
        // Check if email already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        // Add subscriber
        $result = ccs_add_newsletter_subscriber($email, ccs_get_client_ip(), $first_name, $last_name, $date_of_birth);
        if ($result) {
            $imported++;
        } else {
            $skipped++;
        }
    }
    
    fclose($handle);
    
    $message = sprintf(
        'Import complete! %d subscriber%s imported, %d skipped.',
        $imported,
        $imported !== 1 ? 's' : '',
        $skipped
    );
    
    return ['success' => true, 'message' => $message, 'imported' => $imported, 'skipped' => $skipped];
}

/**
 * Enqueue newsletter admin styles
 */
function ccs_newsletter_admin_enqueue_styles($hook) {
    // Only load on Newsletter pages we own (keep Email Templates/Automation screens on core styling).
    $page = isset($_GET['page']) ? (string) $_GET['page'] : '';
    if ($page === '' || strpos($page, 'cta-newsletter') !== 0) {
        return;
    }
    
    // Add inline CSS for newsletter admin styling
    wp_add_inline_style('wp-admin', '
        :root {
            --cta-admin-spacing-sm: 8px;
            --cta-admin-spacing-md: 16px;
            --cta-admin-spacing-lg: 24px;
            --cta-admin-spacing-xl: 32px;
        }
        
        /* Global Newsletter Admin Styles */
        .wrap.cta-newsletter-admin,
        .wrap[class*="cta-newsletter"] {
            padding: var(--cta-admin-spacing-lg) 0;
        }
        
        /* Postbox and container spacing */
        .postbox,
        .cta-newsletter-card {
            margin-bottom: var(--cta-admin-spacing-lg);
            padding: 0;
        }
        
        .postbox .inside,
        .cta-newsletter-card .inside {
            padding: var(--cta-admin-spacing-lg);
        }
        
        .postbox-header {
            padding: var(--cta-admin-spacing-md) var(--cta-admin-spacing-lg);
            border-bottom: 1px solid #dcdcde;
        }
        
        .postbox-header h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #1d2327;
        }
        
        /* Typography hierarchy */
        .cta-newsletter-admin h1,
        .wrap.cta-newsletter-admin h1 {
            font-size: 23px;
            font-weight: 400;
            margin: 0 0 var(--cta-admin-spacing-md) 0;
            padding: 9px 0 4px 0;
            line-height: 1.3;
        }
        
        .cta-newsletter-admin h2,
        .wrap.cta-newsletter-admin h2 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 var(--cta-admin-spacing-md) 0;
            color: #1d2327;
        }
        
        .cta-newsletter-admin h3,
        .wrap.cta-newsletter-admin h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 var(--cta-admin-spacing-sm) 0;
            color: #1d2327;
        }
        
        .cta-newsletter-admin p,
        .wrap.cta-newsletter-admin p {
            margin: 0 0 var(--cta-admin-spacing-md) 0;
            line-height: 1.6;
        }
        
        .cta-newsletter-admin .description {
            margin-top: var(--cta-admin-spacing-sm);
            margin-bottom: var(--cta-admin-spacing-md);
            color: #646970;
            font-size: 13px;
        }
        
        /* Form field spacing */
        .cta-newsletter-admin .form-table th,
        .cta-newsletter-admin .form-table td {
            padding: var(--cta-admin-spacing-md) 0;
            vertical-align: top;
        }
        
        .cta-newsletter-admin .form-table th {
            padding-right: var(--cta-admin-spacing-lg);
            font-weight: 600;
            color: #1d2327;
        }
        
        .cta-newsletter-admin input[type="text"],
        .cta-newsletter-admin input[type="email"],
        .cta-newsletter-admin input[type="number"],
        .cta-newsletter-admin input[type="date"],
        .cta-newsletter-admin input[type="time"],
        .cta-newsletter-admin select,
        .cta-newsletter-admin textarea {
            margin-bottom: var(--cta-admin-spacing-md);
            padding: 6px 8px;
        }
        
        .cta-newsletter-admin textarea {
            width: 100%;
            min-height: 120px;
        }
        
        .cta-newsletter-admin label {
            display: block;
            margin-bottom: var(--cta-admin-spacing-sm);
            font-weight: 500;
            color: #1d2327;
        }
        
        /* Card-based statistics display */
        .cta-newsletter-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--cta-admin-spacing-md);
            margin-bottom: var(--cta-admin-spacing-lg);
        }
        
        .cta-newsletter-stat-card {
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            padding: var(--cta-admin-spacing-lg);
        }
        
        .cta-newsletter-stat-card h3 {
            margin: 0 0 var(--cta-admin-spacing-sm) 0;
            font-size: 13px;
            font-weight: 600;
            color: #646970;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .cta-newsletter-stat-card .stat-value {
            font-size: 32px;
            font-weight: 600;
            color: #1d2327;
            line-height: 1;
            margin: 0;
        }
        
        /* Button spacing */
        .cta-newsletter-admin .button,
        .cta-newsletter-admin .button-primary {
            margin-right: var(--cta-admin-spacing-sm);
            margin-bottom: var(--cta-admin-spacing-sm);
        }
        
        /* Table improvements */
        .cta-newsletter-admin .wp-list-table th,
        .cta-newsletter-admin .wp-list-table td {
            padding: var(--cta-admin-spacing-md) var(--cta-admin-spacing-sm);
        }
        
        .cta-newsletter-admin .wp-list-table thead th {
            font-weight: 600;
            color: #1d2327;
        }
        
        /* Filter/search UI */
        .cta-newsletter-filters {
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            padding: var(--cta-admin-spacing-md);
            margin-bottom: var(--cta-admin-spacing-lg);
        }
        
        .cta-newsletter-filters .form-table {
            margin: 0;
        }
        
        .cta-newsletter-filters .form-table th,
        .cta-newsletter-filters .form-table td {
            padding: var(--cta-admin-spacing-sm) var(--cta-admin-spacing-md);
        }
        
        /* Calendar Grid Layout */
        .cta-calendar-container {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            padding: var(--cta-admin-spacing-lg);
            margin-top: var(--cta-admin-spacing-lg);
        }
        
        .cta-calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--cta-admin-spacing-lg);
            padding-bottom: var(--cta-admin-spacing-md);
            border-bottom: 2px solid #dcdcde;
        }
        
        .cta-calendar-title {
            font-size: 20px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .cta-calendar-nav {
            display: flex;
            gap: var(--cta-admin-spacing-sm);
        }
        
        .cta-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            background: #dcdcde;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .cta-calendar-day-header {
            background: #f6f7f7;
            padding: var(--cta-admin-spacing-md);
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            color: #646970;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .cta-calendar-day {
            background: #fff;
            min-height: 100px;
            padding: var(--cta-admin-spacing-sm);
            display: flex;
            flex-direction: column;
            transition: background-color 0.2s;
        }
        
        .cta-calendar-day:hover {
            background: #f6f7f7;
        }
        
        .cta-calendar-day.today {
            background: #f0f6fc;
            border: 2px solid #2271b1;
        }
        
        .cta-calendar-day.other-month {
            background: #fafafa;
            opacity: 0.5;
        }
        
        .cta-calendar-day-number {
            font-weight: 600;
            font-size: 14px;
            color: #1d2327;
            margin-bottom: var(--cta-admin-spacing-sm);
        }
        
        .cta-calendar-day.today .cta-calendar-day-number {
            color: #2271b1;
        }
        
        .cta-calendar-event {
            background: #dba617;
            color: #fff;
            padding: 4px 6px;
            margin-bottom: 4px;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        
        .cta-calendar-event:hover {
            opacity: 0.9;
        }
        
        .cta-calendar-event strong {
            font-weight: 600;
        }
        
        .cta-calendar-day .button-small {
            width: 100%;
            margin: 0;
        }
        
        .cta-calendar-today-badge {
            font-size: 10px;
            padding: 2px 6px;
            background: #2271b1;
            color: #fff;
            border-radius: 3px;
            font-weight: 600;
        }
        
        .cta-calendar-more {
            padding: 4px 8px;
            font-size: 11px;
            color: #646970;
        }
        
        .cta-calendar-schedule-wrapper {
            padding: 8px;
            text-align: center;
        }
        
        .cta-calendar-schedule-btn {
            font-size: 11px;
        }
        
        .cta-calendar-legend {
            margin-top: var(--cta-admin-spacing-lg);
            padding: var(--cta-admin-spacing-md);
            background: #f6f7f7;
            border-radius: 8px;
            display: flex;
            gap: var(--cta-admin-spacing-lg);
            align-items: center;
            font-size: 13px;
        }
        
        .cta-calendar-legend strong {
            color: #1d2327;
        }
        
        .cta-calendar-legend-item {
            display: flex;
            align-items: center;
            gap: var(--cta-admin-spacing-sm);
        }
        
        .cta-calendar-legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }
        
        .cta-calendar-legend-color.scheduled {
            background: #dba617;
        }
        
        .cta-calendar-legend-color.today {
            background: #f0f6fc;
            border: 2px solid #2271b1;
        }
        
        .cta-calendar-legend-note {
            margin-left: auto;
        }
        
        .cta-calendar-legend-note span {
            color: #646970;
        }
    ');
    
    // Enqueue JavaScript for enhanced interactions
    wp_enqueue_script(
        'cta-newsletter-admin',
        get_template_directory_uri() . '/assets/js/newsletter-admin.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/newsletter-admin.js'),
        true
    );
}
add_action('admin_enqueue_scripts', 'ccs_newsletter_admin_enqueue_styles');

/**
 * Add admin menu page
 */
function ccs_newsletter_admin_menu() {
    $cap = ccs_newsletter_required_capability();
    add_menu_page(
        'Newsletter Subscribers',
        'Newsletter',
        $cap,
        'cta-newsletter',
        'ccs_newsletter_admin_page',
        'dashicons-email-alt',
        30
    );

    // Explicit sub-menu structure (replaces the old in-page tab system).
    add_submenu_page(
        'cta-newsletter',
        'Newsletter Overview',
        'Overview',
        $cap,
        'cta-newsletter',
        'ccs_newsletter_admin_page'
    );
    add_submenu_page(
        'cta-newsletter',
        'Compose Newsletter',
        'Compose',
        $cap,
        'cta-newsletter-compose',
        'ccs_newsletter_compose_page'
    );
    add_submenu_page(
        'cta-newsletter',
        'Newsletter Campaigns',
        'Campaigns',
        $cap,
        'cta-newsletter-campaigns',
        'ccs_newsletter_campaigns_page'
    );
    add_submenu_page(
        'cta-newsletter',
        'Newsletter Subscribers',
        'Subscribers',
        $cap,
        'cta-newsletter-subscribers',
        'ccs_newsletter_subscribers_page'
    );
    add_submenu_page(
        'cta-newsletter',
        'Newsletter Calendar',
        'Calendar',
        $cap,
        'cta-newsletter-calendar',
        'ccs_newsletter_calendar_page'
    );
    add_submenu_page(
        'cta-newsletter',
        'Newsletter Tags',
        'Tags',
        $cap,
        'cta-newsletter-tags',
        'ccs_newsletter_tags_page'
    );
}
add_action('admin_menu', 'ccs_newsletter_admin_menu');

/**
 * Newsletter submenu page callbacks.
 *
 * These are split out so each screen can be focused (Compose, Campaigns, etc).
 * The actual UI will be refactored into dedicated renderers in subsequent changes.
 */
function ccs_newsletter_compose_page() {
    if (!ccs_newsletter_user_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    // Handle send
    if (isset($_POST['ccs_send_newsletter']) && check_admin_referer('ccs_send_newsletter')) {
        ccs_send_newsletter_email();
    }

    global $wpdb;
    $subscribers_table = $wpdb->prefix . 'ccs_newsletter_subscribers';
    $templates_table = $wpdb->prefix . 'ccs_email_templates';

    $active_count = ccs_get_subscriber_count('active');
    $all_tags = ccs_newsletter_get_tags();

    // Load template (optional) for prefill
    $loaded_template = null;
    if (isset($_GET['use_template'])) {
        $template_id = absint($_GET['use_template']);
        if ($template_id > 0) {
            $loaded_template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $templates_table WHERE id = %d", $template_id));
        }
        if (!$loaded_template && $template_id > 0) {
            echo '<div class="notice notice-error"><p>Template not found. Please select a valid template.</p></div>';
        }
    }

    $templates = $wpdb->get_results("SELECT id, name, subject FROM $templates_table ORDER BY is_system DESC, name ASC");

    // Content link pickers
    $courses = get_posts([
        'post_type' => 'course',
        'post_status' => 'publish',
        'posts_per_page' => 100,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    $articles = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 100,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    $prefill_schedule_date = isset($_GET['schedule_date']) ? sanitize_text_field($_GET['schedule_date']) : '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $prefill_schedule_date)) {
        $prefill_schedule_date = '';
    }
    $prefill_schedule_time = isset($_GET['schedule_time']) ? sanitize_text_field($_GET['schedule_time']) : '';
    if (!preg_match('/^\d{2}:\d{2}$/', $prefill_schedule_time)) {
        $prefill_schedule_time = '';
    }

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Compose newsletter</h1>
        <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=cta-email-templates')); ?>">Manage templates</a>
        <p class="description">Write, preview, and send a campaign. Keep it simple: pick recipients, write the subject, write the message, send.</p>
        <hr class="wp-header-end" />
        
        <?php ccs_render_newsletter_navigation('cta-newsletter-compose'); ?>

        <div class="postbox">
            <div class="postbox-header"><h2>Template (optional)</h2></div>
            <div class="inside">
                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                    <input type="hidden" name="page" value="cta-newsletter-compose" />
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="use_template">Start from</label></th>
                            <td>
                                <select id="use_template" name="use_template" class="postform" onchange="this.form.submit();">
                                <option value="">Blank message</option>
                                <?php foreach ($templates as $t) : ?>
                                    <option value="<?php echo esc_attr($t->id); ?>" <?php selected($loaded_template && (int) $loaded_template->id === (int) $t->id); ?>>
                                        <?php echo esc_html($t->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($loaded_template) : ?>
                                <p class="description">Loaded: <strong><?php echo esc_html($loaded_template->name); ?></strong> (subject and content pre-filled)</p>
                            <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>

        <form method="post" id="newsletter-form">
            <?php wp_nonce_field('ccs_send_newsletter'); ?>

            <div class="postbox">
                <div class="postbox-header"><h2>Recipients</h2></div>
                <div class="inside">
                    <?php if ((int) $active_count === 0) : ?>
                        <div class="notice notice-warning"><p>No active subscribers yet.</p></div>
                        <p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-subscribers')); ?>">Go to Subscribers</a></p>
                    <?php else : ?>
                        <fieldset>
                            <legend>Recipient selection</legend>
                            <p><label><input type="radio" name="recipient_mode" value="all" checked> Send to all active subscribers (<?php echo esc_html((string) $active_count); ?>)</label></p>
                            <p><label><input type="radio" name="recipient_mode" value="tags"> Send to subscribers with selected tag(s)</label></p>
                        </fieldset>

                        <div id="cta-recipient-tags" style="display:none;">
                            <?php if (empty($all_tags)) : ?>
                                <div class="notice notice-info"><p>No tags yet. Create one in Newsletter → Tags.</p></div>
                            <?php else : ?>
                                <p>
                                    <?php foreach ($all_tags as $t) : ?>
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="recipient_tag_ids[]" value="<?php echo esc_attr($t->id); ?>">
                                            <?php echo esc_html($t->name); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-header"><h2>Subject</h2></div>
                <div class="inside">
                    <label for="email_subject">Email subject</label>
                    <input type="text" id="email_subject" name="email_subject" class="widefat" required
                        value="<?php echo esc_attr($loaded_template ? (string) $loaded_template->subject : ''); ?>"
                        placeholder="e.g. New course dates available" />
                    <p class="description">Aim for under 60 characters.</p>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-header"><h2>Message</h2></div>
                <div class="inside">
                    <?php
                    $initial_content = $loaded_template ? (string) $loaded_template->content : '';
                    wp_editor(
                        $initial_content,
                        'email_content',
                        [
                            'textarea_name' => 'email_content',
                            'textarea_rows' => 14,
                            'media_buttons' => false,
                            'teeny' => true,
                        ]
                    );
                    ?>
                    <p class="description">Available placeholders: <code>{site_name}</code>, <code>{unsubscribe_link}</code>, <code>{first_name}</code></p>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-header"><h2>Link to content (optional)</h2></div>
                <div class="inside">
                    <fieldset>
                        <legend>Content link</legend>
                        <p>
                            <label><input type="radio" name="link_type" value="none" checked> No link</label><br>
                            <label><input type="radio" name="link_type" value="course"> Link to course</label><br>
                            <label><input type="radio" name="link_type" value="article"> Link to article</label>
                        </p>
                    </fieldset>

                    <div id="cta-link-course" style="display:none;">
                        <p>
                            <label for="course_id">Course</label>
                            <select id="course_id" name="course_id" class="postform">
                                <option value="0">Select a course…</option>
                                <?php foreach ($courses as $c) : ?>
                                    <option value="<?php echo esc_attr($c->ID); ?>"><?php echo esc_html($c->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    </div>

                    <div id="cta-link-article" style="display:none;">
                        <p>
                            <label for="article_id">Article</label>
                            <select id="article_id" name="article_id" class="postform">
                                <option value="0">Select an article…</option>
                                <?php foreach ($articles as $a) : ?>
                                    <option value="<?php echo esc_attr($a->ID); ?>"><?php echo esc_html($a->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-header"><h2>Schedule (optional)</h2></div>
                <div class="inside">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="schedule_date">Date</label></th>
                            <td><input type="date" id="schedule_date" name="schedule_date" value="<?php echo esc_attr($prefill_schedule_date); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="schedule_time">Time</label></th>
                            <td>
                                <input type="time" id="schedule_time" name="schedule_time" value="<?php echo esc_attr($prefill_schedule_time); ?>" />
                                <p class="description">Leave date/time empty to send immediately.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-header"><h2>Send</h2></div>
                <div class="inside">
                    <p>
                        <button type="submit" class="button button-primary" name="ccs_send_newsletter" value="1">Send / Schedule</button>
                        <button type="button" class="button" id="cta-preview-btn">Preview</button>
                    </p>
                    <p>
                        <label for="test_email_address">Send test email:</label>
                        <input type="email" id="test_email_address" placeholder="you@example.com" class="regular-text" />
                        <button type="button" class="button" id="cta-send-test-btn">Send test</button>
                    </p>
                </div>
            </div>
        </form>
    </div>

    <script>
    jQuery(function($){
        function toggleTagBox() {
            var mode = $('input[name="recipient_mode"]:checked').val();
            $('#cta-recipient-tags').toggle(mode === 'tags');
        }
        $('input[name="recipient_mode"]').on('change', toggleTagBox);
        toggleTagBox();

        function toggleLinkPickers() {
            var t = $('input[name="link_type"]:checked').val();
            $('#cta-link-course').toggle(t === 'course');
            $('#cta-link-article').toggle(t === 'article');
        }
        $('input[name="link_type"]').on('change', toggleLinkPickers);
        toggleLinkPickers();

        $('#cta-send-test-btn').on('click', function(){
            var email = $('#test_email_address').val() || '';
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                alert('Please enter a valid test email address.');
                return;
            }
            var $form = $('#newsletter-form');
            $form.find('input[name="test_email"]').remove();
            $form.find('input[name="test_email_address"]').remove();
            $form.append('<input type="hidden" name="test_email" value="1">');
            $form.append('<input type="hidden" name="test_email_address" value="' + $('<div>').text(email).html() + '">');
            $form.submit();
        });

        $('#cta-preview-btn').on('click', function(){
            var subject = $('#email_subject').val() || '(No subject)';
            var content = '';
            if (typeof tinymce !== 'undefined' && tinymce.get('email_content')) {
                content = tinymce.get('email_content').getContent() || '';
            } else {
                content = $('#email_content').val() || '';
            }

            var w = window.open('', 'ctaNewsletterPreview', 'width=980,height=720,scrollbars=yes');
            if (!w) return;
            w.document.open();
            w.document.write('<!doctype html><html><head><meta charset=\"utf-8\"><title>Preview</title>');
            w.document.write('<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">');
            w.document.write('<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:20px;} .box{max-width:700px;margin:0 auto;border:1px solid #dcdcde;padding:18px;border-radius:8px;} h1{font-size:18px;margin:0 0 10px;} .meta{color:#646970;font-size:13px;margin-bottom:12px;} </style>');
            w.document.write('</head><body><div class=\"box\">');
            w.document.write('<h1>' + subject.replace(/</g,'&lt;') + '</h1>');
            w.document.write('<div class=\"meta\">Preview only. Links/placeholders may be replaced during sending.</div>');
            w.document.write('<div>' + content + '</div>');
            w.document.write('</div></body></html>');
            w.document.close();
        });
    });
    </script>
    <?php
}

function ccs_newsletter_campaigns_page() {
    if (!ccs_newsletter_user_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    global $wpdb;
    $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
    $queue_table = $wpdb->prefix . 'ccs_email_queue';

    // Actions
    if (isset($_GET['action'], $_GET['campaign'])) {
        $campaign_id = absint($_GET['campaign']);
        $action = sanitize_text_field($_GET['action']);

        if ($campaign_id > 0 && $action === 'cancel_scheduled' && check_admin_referer('cancel_scheduled_' . $campaign_id)) {
            $cancelled = ccs_cancel_scheduled_campaign($campaign_id);
            if ($cancelled) {
                echo '<div class="notice notice-success is-dismissible"><p>Scheduled campaign cancelled. Pending emails removed from the queue.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to cancel campaign. It may have already been sent.</p></div>';
            }
        }

        if ($campaign_id > 0 && $action === 'retry' && check_admin_referer('retry_failed_' . $campaign_id)) {
            $retried = ccs_retry_failed_emails($campaign_id);
            if ($retried > 0) {
                echo '<div class="notice notice-success is-dismissible"><p>Re-queued ' . esc_html((string) $retried) . ' failed email(s).</p></div>';
            } else {
                echo '<div class="notice notice-info is-dismissible"><p>No failed emails to retry.</p></div>';
            }
        }
    }

    $view_queue = isset($_GET['view']) && $_GET['view'] === 'queue' && isset($_GET['campaign']);
    $queue_campaign_id = $view_queue ? absint($_GET['campaign']) : 0;

    // Queue view
    if ($view_queue && $queue_campaign_id > 0) {
        $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $campaigns_table WHERE id = %d", $queue_campaign_id));
        if (!$campaign) {
            echo '<div class="wrap"><h1>Campaign queue</h1><div class="notice notice-error"><p>Campaign not found.</p></div></div>';
            return;
        }

        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $where = "campaign_id = %d";
        $params = [$queue_campaign_id];
        if ($status_filter !== 'all') {
            $where .= " AND status = %s";
            $params[] = $status_filter;
        }

        $counts = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) AS cnt FROM $queue_table WHERE campaign_id = %d GROUP BY status",
            $queue_campaign_id
        ));
        $counts_by_status = [];
        foreach ($counts as $c) $counts_by_status[(string) $c->status] = (int) $c->cnt;

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT id, email, status, attempts, last_attempt_at, error_message, scheduled_for, sent_at
             FROM $queue_table
             WHERE $where
             ORDER BY id DESC
             LIMIT 200",
            $params
        ));

        $retry_url = wp_nonce_url(admin_url('admin.php?page=cta-newsletter-campaigns&view=queue&campaign=' . $queue_campaign_id . '&action=retry'), 'retry_failed_' . $queue_campaign_id);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Campaign queue</h1>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-campaigns')); ?>">Back to campaigns</a>
            <p class="description"><?php echo esc_html((string) $campaign->subject); ?></p>
            <hr class="wp-header-end" />

            <div class="notice notice-info">
                <p>
                    Pending: <strong><?php echo esc_html((string) ($counts_by_status['pending'] ?? 0)); ?></strong>,
                    Processing: <strong><?php echo esc_html((string) ($counts_by_status['processing'] ?? 0)); ?></strong>,
                    Sent: <strong><?php echo esc_html((string) ($counts_by_status['sent'] ?? 0)); ?></strong>,
                    Failed: <strong><?php echo esc_html((string) ($counts_by_status['failed'] ?? 0)); ?></strong>
                </p>
            </div>

            <p>
                <a class="button" href="<?php echo esc_url($retry_url); ?>">Retry failed emails</a>
            </p>

            <form method="get">
                <input type="hidden" name="page" value="cta-newsletter-campaigns" />
                <input type="hidden" name="view" value="queue" />
                <input type="hidden" name="campaign" value="<?php echo esc_attr((string) $queue_campaign_id); ?>" />
                <label for="status">Status</label>
                <select id="status" name="status" class="postform">
                    <option value="all" <?php selected($status_filter, 'all'); ?>>All</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                    <option value="processing" <?php selected($status_filter, 'processing'); ?>>Processing</option>
                    <option value="sent" <?php selected($status_filter, 'sent'); ?>>Sent</option>
                    <option value="failed" <?php selected($status_filter, 'failed'); ?>>Failed</option>
                </select>
                <button type="submit" class="button">Filter</button>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-primary">Email</th>
                        <th scope="col" class="manage-column">Status</th>
                        <th scope="col" class="manage-column">Attempts</th>
                        <th scope="col" class="manage-column">Scheduled for</th>
                        <th scope="col" class="manage-column">Last attempt</th>
                        <th scope="col" class="manage-column">Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)) : ?>
                        <tr><td colspan="6">No queue items found.</td></tr>
                    <?php else : ?>
                        <?php foreach ($items as $it) : ?>
                            <tr>
                                <td class="column-primary">
                                    <strong><?php echo esc_html((string) $it->email); ?></strong>
                                    <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                                </td>
                                <td><?php echo esc_html((string) $it->status); ?></td>
                                <td><?php echo esc_html((string) ((int) $it->attempts)); ?></td>
                                <td><?php echo $it->scheduled_for ? esc_html(wp_date('j M Y, g:i a', strtotime($it->scheduled_for))) : '—'; ?></td>
                                <td><?php echo $it->last_attempt_at ? esc_html(wp_date('j M Y, g:i a', strtotime($it->last_attempt_at))) : '—'; ?></td>
                                <td><?php echo !empty($it->error_message) ? esc_html(wp_trim_words((string) $it->error_message, 12)) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return;
    }

    // Campaign list view
    $campaigns = $wpdb->get_results("SELECT * FROM $campaigns_table ORDER BY sent_at DESC LIMIT 100");
    $campaign_ids = array_map(static function($c){ return (int) $c->id; }, $campaigns ?: []);

    // Queue counts for campaigns (failed + pending)
    $queue_counts = [];
    if (!empty($campaign_ids)) {
        $placeholders = implode(',', array_fill(0, count($campaign_ids), '%d'));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT campaign_id,
                    SUM(status = 'failed') AS failed_count,
                    SUM(status = 'pending') AS pending_count
             FROM $queue_table
             WHERE campaign_id IN ($placeholders)
             GROUP BY campaign_id",
            $campaign_ids
        ));
        foreach ($rows as $r) {
            $queue_counts[(int) $r->campaign_id] = [
                'failed' => (int) $r->failed_count,
                'pending' => (int) $r->pending_count,
            ];
        }
    }

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Campaigns</h1>
        <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-compose')); ?>">Compose new</a>
        <p class="description">Sent and scheduled campaigns. View queue status for scheduled/queued sends.</p>
        <hr class="wp-header-end" />
        
        <?php ccs_render_newsletter_navigation('cta-newsletter-campaigns'); ?>

        <?php if (empty($campaigns)) : ?>
            <div class="notice notice-info"><p>No campaigns yet.</p></div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-primary">Subject</th>
                        <th scope="col" class="manage-column">Date</th>
                        <th scope="col" class="manage-column">Sent</th>
                        <th scope="col" class="manage-column">Opened</th>
                        <th scope="col" class="manage-column">Clicked</th>
                        <th scope="col" class="manage-column">Open rate</th>
                        <th scope="col" class="manage-column">Click rate</th>
                        <th scope="col" class="manage-column">Status</th>
                        <th scope="col" class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $c) : ?>
                        <?php
                        $total_sent = (int) ($c->total_sent ?? 0);
                        $opens = (int) ($c->unique_opens ?? 0);
                        $clicks = (int) ($c->unique_clicks ?? 0);
                        $open_rate = $total_sent > 0 ? round(($opens / $total_sent) * 100) : 0;
                        $click_rate = $total_sent > 0 ? round(($clicks / $total_sent) * 100) : 0;
                        $status = (string) ($c->status ?? 'completed');
                        $queue_url = admin_url('admin.php?page=cta-newsletter-campaigns&view=queue&campaign=' . (int) $c->id);
                        $cancel_url = wp_nonce_url(admin_url('admin.php?page=cta-newsletter-campaigns&action=cancel_scheduled&campaign=' . (int) $c->id), 'cancel_scheduled_' . (int) $c->id);
                        $retry_url = wp_nonce_url(admin_url('admin.php?page=cta-newsletter-campaigns&view=queue&campaign=' . (int) $c->id . '&action=retry'), 'retry_failed_' . (int) $c->id);
                        $qc = $queue_counts[(int) $c->id] ?? ['failed' => 0, 'pending' => 0];
                        ?>
                        <tr>
                            <td class="column-primary">
                                <strong><?php echo esc_html((string) $c->subject); ?></strong>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                            </td>
                            <td><?php echo !empty($c->sent_at) ? esc_html(wp_date('j M Y, g:i a', strtotime($c->sent_at))) : '—'; ?></td>
                            <td><?php echo esc_html((string) $total_sent); ?></td>
                            <td><?php echo esc_html((string) $opens); ?></td>
                            <td><?php echo esc_html((string) $clicks); ?></td>
                            <td><?php echo esc_html((string) $open_rate); ?>%</td>
                            <td><?php echo esc_html((string) $click_rate); ?>%</td>
                            <td><?php echo esc_html($status); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url($queue_url); ?>">Queue</a>
                                <?php if ($qc['failed'] > 0) : ?>
                                    <a class="button button-small" href="<?php echo esc_url($retry_url); ?>">Retry failed (<?php echo esc_html((string) $qc['failed']); ?>)</a>
                                <?php endif; ?>
                                <?php if ($status === 'scheduled') : ?>
                                    <a class="button button-small" href="<?php echo esc_url($cancel_url); ?>" onclick="return confirm('Cancel this scheduled campaign? Pending emails will be removed.');">Cancel</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

function ccs_newsletter_subscribers_page() {
    if (!ccs_newsletter_user_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    $tags_table = $wpdb->prefix . 'ccs_newsletter_tags';
    $rel_table = $wpdb->prefix . 'ccs_newsletter_subscriber_tags';

    // Handle remove all unsubscribed
    if (isset($_POST['remove_all_unsubscribed']) && check_admin_referer('ccs_remove_all_unsubscribed')) {
        $deleted = $wpdb->query("DELETE FROM $table_name WHERE status = 'unsubscribed'");
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(_n('%d unsubscribed subscriber removed.', '%d unsubscribed subscribers removed.', $deleted, 'cta'), $deleted) . '</p></div>';
    }

    // Handle bulk actions
    if (isset($_POST['bulk_action']) && isset($_POST['subscriber_ids']) && is_array($_POST['subscriber_ids']) && check_admin_referer('ccs_newsletter_bulk_action')) {
        $action = sanitize_text_field($_POST['bulk_action']);
        $ids = array_values(array_filter(array_map('absint', (array) $_POST['subscriber_ids'])));

        if ($action === 'unsubscribe' && !empty($ids)) {
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET status = 'unsubscribed', unsubscribed_at = %s WHERE id IN (" . implode(',', array_fill(0, count($ids), '%d')) . ")",
                array_merge([current_time('mysql')], $ids)
            ));
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(_n('%d subscriber unsubscribed.', '%d subscribers unsubscribed.', $updated, 'cta'), $updated) . '</p></div>';
        } elseif ($action === 'reactivate' && !empty($ids)) {
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET status = 'active', unsubscribed_at = NULL WHERE id IN (" . implode(',', array_fill(0, count($ids), '%d')) . ")",
                $ids
            ));
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(_n('%d subscriber reactivated.', '%d subscribers reactivated.', $updated, 'cta'), $updated) . '</p></div>';
        } elseif ($action === 'delete' && !empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_name WHERE id IN ($placeholders)",
                $ids
            ));
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(_n('%d subscriber deleted.', '%d subscribers deleted.', $deleted, 'cta'), $deleted) . '</p></div>';
        } elseif (($action === 'add_tag' || $action === 'remove_tag') && !empty($ids)) {
            $tag_id = absint($_POST['bulk_tag_id'] ?? 0);
            if ($tag_id <= 0) {
                echo '<div class="notice notice-error"><p>Please choose a tag.</p></div>';
            } else {
                if ($action === 'add_tag') {
                    ccs_newsletter_assign_tags($ids, [$tag_id]);
                    echo '<div class="notice notice-success is-dismissible"><p>Tag applied to selected subscribers.</p></div>';
                } else {
                    ccs_newsletter_remove_tags($ids, [$tag_id]);
                    echo '<div class="notice notice-success is-dismissible"><p>Tag removed from selected subscribers.</p></div>';
                }
            }
        }
    }

    // Handle CSV import
    if (isset($_POST['ccs_import_newsletter_csv']) && check_admin_referer('ccs_import_newsletter_csv')) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $result = ccs_import_newsletter_csv($_FILES['csv_file']);
            if (!empty($result['success'])) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html((string) ($result['message'] ?? 'Import complete.')) . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html((string) ($result['message'] ?? 'Import failed.')) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Please select a CSV file to import.</p></div>';
        }
    }

    // Row actions
    if (isset($_GET['action'], $_GET['id'])) {
        $id = absint($_GET['id']);
        $action = sanitize_text_field($_GET['action']);

        if ($id > 0 && $action === 'delete' && check_admin_referer('delete_subscriber_' . $id)) {
            $wpdb->delete($table_name, ['id' => $id], ['%d']);
            echo '<div class="notice notice-success is-dismissible"><p>Subscriber deleted.</p></div>';
        }

        if ($id > 0 && $action === 'unsubscribe' && check_admin_referer('unsubscribe_' . $id)) {
            $wpdb->update(
                $table_name,
                ['status' => 'unsubscribed', 'unsubscribed_at' => current_time('mysql')],
                ['id' => $id],
                ['%s', '%s'],
                ['%d']
            );
            echo '<div class="notice notice-success is-dismissible"><p>Subscriber marked as unsubscribed.</p></div>';
        }
    }

    // Filters
    $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'all';
    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $tag_filter = isset($_GET['tag_filter']) ? absint($_GET['tag_filter']) : 0;
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

    $where = [];
    $params = [];
    if ($status_filter !== 'all') {
        $where[] = "status = %s";
        $params[] = $status_filter;
    }
    if ($search_query !== '') {
        $like = '%' . $wpdb->esc_like($search_query) . '%';
        $where[] = "(email LIKE %s OR first_name LIKE %s OR last_name LIKE %s)";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($tag_filter > 0) {
        $where[] = "EXISTS (SELECT 1 FROM $rel_table st WHERE st.subscriber_id = $table_name.id AND st.tag_id = %d)";
        $params[] = $tag_filter;
    }
    if ($date_from !== '') {
        $where[] = "DATE(subscribed_at) >= %s";
        $params[] = $date_from;
    }
    if ($date_to !== '') {
        $where[] = "DATE(subscribed_at) <= %s";
        $params[] = $date_to;
    }
    $where_sql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    $per_page = isset($_GET['per_page']) ? max(1, absint($_GET['per_page'])) : 50;
    $current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    $count_query = "SELECT COUNT(*) FROM $table_name $where_sql";
    $total_items = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_query, $params)) : (int) $wpdb->get_var($count_query);
    $total_pages = $total_items > 0 ? (int) ceil($total_items / $per_page) : 1;

    $query = "SELECT * FROM $table_name $where_sql ORDER BY subscribed_at DESC LIMIT %d OFFSET %d";
    $query_params = array_merge($params, [$per_page, $offset]);
    $subscribers = $wpdb->get_results($wpdb->prepare($query, $query_params));

    // Tags map for display + bulk actions
    $all_tags = ccs_newsletter_get_tags();
    $tags_by_id = [];
    foreach ($all_tags as $t) $tags_by_id[(int) $t->id] = $t;
    $subscribers_on_page_ids = array_map(static function($s) { return (int) $s->id; }, $subscribers ?: []);
    $subscriber_tag_ids_map = ccs_newsletter_get_tag_ids_for_subscribers($subscribers_on_page_ids);

    $base_url = admin_url('admin.php?page=cta-newsletter-subscribers');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Subscribers</h1>
        <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-tags')); ?>">Manage tags</a>
        <p class="description">Search, filter, import/export, and manage your subscriber list.</p>
        <hr class="wp-header-end" />
        
        <?php ccs_render_newsletter_navigation('cta-newsletter-subscribers'); ?>

        <!-- Modern Import UI with Drag & Drop -->
        <div class="postbox" style="margin-bottom: var(--admin-spacing-lg);">
            <div class="postbox-header">
                <h2>
                    <span class="dashicons dashicons-upload" style="vertical-align: middle;" aria-hidden="true"></span>
                    Import Subscribers
                </h2>
            </div>
            <div class="inside">
                <form method="post" enctype="multipart/form-data" id="cta-import-form">
                    <?php wp_nonce_field('ccs_import_newsletter_csv'); ?>
                    
                    <div class="cta-import-dropzone" id="cta-import-dropzone" role="button" tabindex="0" aria-label="Upload CSV file">
                        <div class="cta-import-icon" aria-hidden="true">
                            <span class="dashicons dashicons-upload"></span>
                        </div>
                        <div class="cta-import-text">Drag & drop your CSV file here</div>
                        <div class="cta-import-hint">or click to browse</div>
                        <input type="file" name="csv_file" accept=".csv" required class="cta-import-file-input" id="cta-import-file-input" aria-label="Select CSV file" />
                    </div>
                    
                    <div class="cta-import-progress" id="cta-import-progress" role="status" aria-live="polite" aria-label="Import progress">
                        <div class="cta-progress-bar">
                            <div class="cta-progress-fill" style="width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p style="text-align: center; color: #646970; font-size: 13px;">Processing...</p>
                    </div>
                    
                    <button type="submit" name="ccs_import_newsletter_csv" class="button button-primary" style="display: none;" id="cta-import-submit">
                        <span class="dashicons dashicons-upload" style="vertical-align: middle;" aria-hidden="true"></span> Import CSV
                    </button>
                </form>
                
                <div style="margin-top: var(--admin-spacing-lg); padding-top: var(--admin-spacing-lg); border-top: 1px solid #f0f0f1;">
                    <p style="margin: 0 0 var(--admin-spacing-sm); font-weight: 500; color: #1d2327; display: flex; align-items: center; gap: var(--admin-spacing-xs);">
                        <span class="dashicons dashicons-info" style="color: #2271b1; font-size: 18px; width: 18px; height: 18px;" aria-hidden="true"></span>
                        <span>CSV Format Requirements</span>
                    </p>
                    <p class="description" style="margin: 0 0 var(--admin-spacing-sm);">
                        Your CSV file should include these columns:
                    </p>
                    <ul style="margin: 0 0 var(--admin-spacing-md) var(--admin-spacing-lg); color: #646970; font-size: 13px; line-height: 1.8;">
                        <li><code>email</code> (required) - Email address</li>
                        <li><code>first name</code> (optional) - First name</li>
                        <li><code>last name</code> (optional) - Last name</li>
                        <li><code>date of birth</code> (optional) - Format: YYYY-MM-DD</li>
                    </ul>
                    <div style="display: flex; gap: var(--admin-spacing-sm); flex-wrap: wrap;">
                        <a href="#" class="button button-small" id="cta-download-sample-csv">
                            <span class="dashicons dashicons-download" style="vertical-align: middle; font-size: 14px;" aria-hidden="true"></span> Download Sample CSV
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-subscribers&action=export')); ?>" class="button button-small" id="cta-export-subscribers">
                            <span class="dashicons dashicons-download" style="vertical-align: middle; font-size: 14px;" aria-hidden="true"></span> Export Current List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get" class="cta-filter-form">
                    <input type="hidden" name="page" value="cta-newsletter-subscribers" />
                    <label for="status_filter" class="screen-reader-text">Filter by status</label>
                    <select id="status_filter" name="status_filter" class="postform">
                        <option value="all" <?php selected($status_filter, 'all'); ?>>All Status</option>
                        <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                        <option value="unsubscribed" <?php selected($status_filter, 'unsubscribed'); ?>>Unsubscribed</option>
                    </select>
                    <label for="tag_filter" class="screen-reader-text">Filter by tag</label>
                    <select id="tag_filter" name="tag_filter" class="postform">
                        <option value="0">All Tags</option>
                        <?php foreach ($all_tags as $t) : ?>
                            <option value="<?php echo esc_attr($t->id); ?>" <?php selected($tag_filter, (int) $t->id); ?>><?php echo esc_html($t->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="date_from" class="screen-reader-text">From date</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="From date" />
                    <label for="date_to" class="screen-reader-text">To date</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="To date" />
                    <button type="submit" class="button button-primary">Apply Filters</button>
                    <a class="button" href="<?php echo esc_url($base_url); ?>">Clear</a>
                </form>
            </div>
        </div>

        <form method="post">
            <?php wp_nonce_field('ccs_newsletter_bulk_action'); ?>

            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label class="screen-reader-text" for="bulk_action">Bulk actions</label>
                    <select name="bulk_action" id="bulk_action" aria-label="Select bulk action">
                        <option value="">Bulk Actions</option>
                        <option value="unsubscribe">Unsubscribe</option>
                        <option value="reactivate">Reactivate</option>
                        <option value="delete">Delete</option>
                        <option value="add_tag">Add tag…</option>
                        <option value="remove_tag">Remove tag…</option>
                    </select>
                    <label for="bulk_tag_id" class="screen-reader-text">Select tag</label>
                    <select name="bulk_tag_id" id="bulk_tag_id" class="postform" style="display:none;" aria-label="Select tag">
                        <option value="">Choose tag…</option>
                        <?php foreach ($all_tags as $t) : ?>
                            <option value="<?php echo esc_attr($t->id); ?>"><?php echo esc_html($t->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button action" value="Apply" />
                </div>
                <div class="tablenav-pages">
                    <?php
                    if ($total_pages > 1) {
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '‹',
                            'next_text' => '›',
                            'total' => $total_pages,
                            'current' => $current_page,
                        ]);
                    }
                    ?>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="select_all" /></td>
                        <th scope="col" class="manage-column column-primary">Email</th>
                        <th scope="col" class="manage-column">Name</th>
                        <th scope="col" class="manage-column">Date of birth</th>
                        <th scope="col" class="manage-column">Subscribed</th>
                        <th scope="col" class="manage-column">Status</th>
                        <th scope="col" class="manage-column">Tags</th>
                        <th scope="col" class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)) : ?>
                        <tr><td colspan="8">No subscribers found.</td></tr>
                    <?php else : ?>
                        <?php foreach ($subscribers as $sub) : ?>
                            <?php
                            $tag_ids = $subscriber_tag_ids_map[(int) $sub->id] ?? [];
                            $name = trim((string) ($sub->first_name ?? '') . ' ' . (string) ($sub->last_name ?? ''));
                            $delete_url = wp_nonce_url(admin_url('admin.php?page=cta-newsletter-subscribers&action=delete&id=' . (int) $sub->id), 'delete_subscriber_' . (int) $sub->id);
                            $unsubscribe_url = wp_nonce_url(admin_url('admin.php?page=cta-newsletter-subscribers&action=unsubscribe&id=' . (int) $sub->id), 'unsubscribe_' . (int) $sub->id);
                            ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="subscriber_ids[]" value="<?php echo esc_attr((string) $sub->id); ?>" /></th>
                                <td class="column-primary">
                                    <strong><?php echo esc_html((string) $sub->email); ?></strong>
                                    <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                                </td>
                                <td><?php echo $name !== '' ? esc_html($name) : '—'; ?></td>
                                <td><?php echo !empty($sub->date_of_birth) ? esc_html(wp_date('j M Y', strtotime($sub->date_of_birth))) : '—'; ?></td>
                                <td><?php echo !empty($sub->subscribed_at) ? esc_html(wp_date('j M Y, g:i a', strtotime($sub->subscribed_at))) : '—'; ?></td>
                                <td>
                                    <?php
                                    $status = (string) $sub->status;
                                    $badge_class = $status === 'active' ? 'cta-badge success' : 'cta-badge';
                                    ?>
                                    <span class="<?php echo esc_attr($badge_class); ?>"><?php echo esc_html(ucfirst($status)); ?></span>
                                </td>
                                <td>
                                    <?php if (empty($tag_ids)) : ?>
                                        —
                                    <?php else : ?>
                                        <?php foreach ($tag_ids as $tid) : ?>
                                            <?php $t = $tags_by_id[(int) $tid] ?? null; if (!$t) continue; ?>
                                            <span class="tag" style="display:inline-flex; align-items:center; gap:6px; margin: 0 6px 6px 0; padding: 2px 8px; border: 1px solid #dcdcde; border-radius: 999px; background: #fff; font-size: 12px;">
                                                <span style="display:inline-block; width:8px; height:8px; border-radius:999px; background: <?php echo esc_attr($t->color ?: '#2271b1'); ?>;"></span>
                                                <span><?php echo esc_html($t->name); ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: var(--admin-spacing-xs); flex-wrap: wrap;">
                                        <?php if ((string) $sub->status === 'active') : ?>
                                            <a class="button button-small" href="<?php echo esc_url($unsubscribe_url); ?>" aria-label="Unsubscribe <?php echo esc_attr($sub->email); ?>">Unsubscribe</a>
                                        <?php endif; ?>
                                        <a class="button button-small button-link-delete" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Delete this subscriber? This cannot be undone.');" aria-label="Delete <?php echo esc_attr($sub->email); ?>">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    if ($total_pages > 1) {
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '‹',
                            'next_text' => '›',
                            'total' => $total_pages,
                            'current' => $current_page,
                        ]);
                    }
                    ?>
                </div>
            </div>
        </form>
    </div>

    <script>
    jQuery(function($){
        function toggleBulkTag() {
            var v = $('#bulk_action').val();
            $('#bulk_tag_id').toggle(v === 'add_tag' || v === 'remove_tag');
        }
        $('#bulk_action').on('change', toggleBulkTag);
        toggleBulkTag();

        $('#select_all').on('change', function(){
            $('input[name="subscriber_ids[]"]').prop('checked', this.checked);
        });
    });
    </script>
    <?php
}

function ccs_newsletter_calendar_page() {
    if (!ccs_newsletter_user_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    global $wpdb;
    $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
    $queue_table = $wpdb->prefix . 'ccs_email_queue';

    $month = isset($_GET['month']) ? absint($_GET['month']) : (int) wp_date('n');
    $year = isset($_GET['year']) ? absint($_GET['year']) : (int) wp_date('Y');
    $month = min(12, max(1, $month));
    $year = max(2000, min(2100, $year));

    $first_day_ts = strtotime(sprintf('%04d-%02d-01', $year, $month));
    $days_in_month = (int) date('t', $first_day_ts);
    $first_weekday = (int) date('N', $first_day_ts); // 1=Mon..7=Sun

    // Scheduled campaigns by day (from queue table)
    $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $days_in_month);
    $scheduled_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT q.campaign_id, DATE(q.scheduled_for) AS day, MIN(q.scheduled_for) AS first_time, c.subject
         FROM $queue_table q
         INNER JOIN $campaigns_table c ON c.id = q.campaign_id
         WHERE q.scheduled_for IS NOT NULL
           AND q.scheduled_for BETWEEN %s AND %s
         GROUP BY q.campaign_id, DATE(q.scheduled_for), c.subject
         ORDER BY first_time ASC",
        $start,
        $end
    ));

    $by_day = [];
    foreach ($scheduled_rows as $r) {
        $d = (string) $r->day;
        if (!isset($by_day[$d])) $by_day[$d] = [];
        $by_day[$d][] = $r;
    }

    $prev_ts = strtotime('-1 month', $first_day_ts);
    $next_ts = strtotime('+1 month', $first_day_ts);
    $today = wp_date('Y-m-d');

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Newsletter Calendar</h1>
        <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-compose')); ?>">
            <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span> Schedule Campaign
        </a>
        <p class="description">View and manage scheduled campaigns. Click any date to schedule a new campaign.</p>
        <hr class="wp-header-end" />
        
        <?php ccs_render_newsletter_navigation('cta-newsletter-calendar'); ?>

        <div class="cta-calendar-container">
            <div class="cta-calendar-header">
                <div class="cta-calendar-title"><?php echo esc_html(wp_date('F Y', $first_day_ts)); ?></div>
                <div class="cta-calendar-nav">
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-calendar&month=' . (int) date('n', $prev_ts) . '&year=' . (int) date('Y', $prev_ts))); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span> Previous
                    </a>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-calendar')); ?>">Today</a>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-calendar&month=' . (int) date('n', $next_ts) . '&year=' . (int) date('Y', $next_ts))); ?>">
                        Next <span class="dashicons dashicons-arrow-right-alt2" style="vertical-align: middle;"></span>
                    </a>
                </div>
            </div>

            <div class="cta-calendar-grid">
                <!-- Day headers -->
                <div class="cta-calendar-day-header">Mon</div>
                <div class="cta-calendar-day-header">Tue</div>
                <div class="cta-calendar-day-header">Wed</div>
                <div class="cta-calendar-day-header">Thu</div>
                <div class="cta-calendar-day-header">Fri</div>
                <div class="cta-calendar-day-header">Sat</div>
                <div class="cta-calendar-day-header">Sun</div>

                <?php
                $day = 1;
                $cell = 1;
                $total_cells = (int) ceil(($first_weekday - 1 + $days_in_month) / 7) * 7;
                while ($cell <= $total_cells) :
                    for ($col = 1; $col <= 7; $col++, $cell++) {
                        if ($cell < $first_weekday || $day > $days_in_month) {
                            echo '<div class="cta-calendar-day other-month"></div>';
                            continue;
                        }

                        $date_key = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $items = $by_day[$date_key] ?? [];
                        $is_today = ($date_key === $today);
                        $day_classes = 'cta-calendar-day';
                        if ($is_today) $day_classes .= ' today';

                        echo '<div class="' . esc_attr($day_classes) . '" data-date="' . esc_attr($date_key) . '">';
                        echo '<div class="cta-calendar-day-number">' . esc_html((string) $day);
                        if ($is_today) echo ' <span class="cta-calendar-today-badge">Today</span>';
                        echo '</div>';

                        if (!empty($items)) {
                            $shown = 0;
                            foreach ($items as $it) {
                                if ($shown >= 3) break;
                                $queue_url = admin_url('admin.php?page=cta-newsletter-campaigns&view=queue&campaign=' . (int) $it->campaign_id);
                                $time = $it->first_time ? wp_date('g:ia', strtotime($it->first_time)) : '';
                                
                                echo '<div class="cta-calendar-event scheduled" data-campaign-id="' . esc_attr((string) $it->campaign_id) . '" title="' . esc_attr((string) $it->subject) . '">';
                                echo '<strong>' . esc_html($time) . '</strong> ';
                                echo esc_html(wp_trim_words((string) $it->subject, 4));
                                echo '</div>';
                                $shown++;
                            }
                            if (count($items) > 3) {
                                echo '<div class="cta-calendar-more">+' . esc_html((string) (count($items) - 3)) . ' more</div>';
                            }
                        } else {
                            echo '<div class="cta-calendar-schedule-wrapper">';
                            echo '<a href="' . esc_url(admin_url('admin.php?page=cta-newsletter-compose&schedule_date=' . $date_key)) . '" class="button button-small cta-calendar-schedule-btn">+ Schedule</a>';
                            echo '</div>';
                        }

                        echo '</div>';
                        $day++;
                    }
                endwhile;
                ?>
            </div>
            
            <!-- Calendar Legend -->
            <div class="cta-calendar-legend">
                <strong>Legend:</strong>
                <div class="cta-calendar-legend-item">
                    <div class="cta-calendar-legend-color scheduled"></div>
                    <span>Scheduled</span>
                </div>
                <div class="cta-calendar-legend-item">
                    <div class="cta-calendar-legend-color today"></div>
                    <span>Today</span>
                </div>
                <div class="cta-calendar-legend-note">
                    <span>Click any date to schedule a campaign</span>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function ccs_newsletter_tags_page() {
    if (!ccs_newsletter_user_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    global $wpdb;
    $tags_table = $wpdb->prefix . 'ccs_newsletter_tags';
    $rel_table = $wpdb->prefix . 'ccs_newsletter_subscriber_tags';

    // Create tag
    if (isset($_POST['ccs_create_tag']) && check_admin_referer('ccs_create_tag')) {
        $name = sanitize_text_field($_POST['tag_name'] ?? '');
        $color = sanitize_text_field($_POST['tag_color'] ?? '');
        $res = ccs_newsletter_create_tag($name, $color);
        if (is_wp_error($res)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($res->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>Tag created.</p></div>';
        }
    }

    // Delete tag
    if (isset($_GET['action'], $_GET['tag_id']) && $_GET['action'] === 'delete') {
        $tag_id = absint($_GET['tag_id']);
        if ($tag_id > 0 && check_admin_referer('ccs_delete_tag_' . $tag_id)) {
            // Remove relationships then delete the tag
            $wpdb->delete($rel_table, ['tag_id' => $tag_id], ['%d']);
            $wpdb->delete($tags_table, ['id' => $tag_id], ['%d']);
            echo '<div class="notice notice-success is-dismissible"><p>Tag deleted.</p></div>';
        }
    }

    $tags = ccs_newsletter_get_tags();

    // Tag usage counts
    $counts = $wpdb->get_results("SELECT tag_id, COUNT(*) AS cnt FROM $rel_table GROUP BY tag_id");
    $counts_by_id = [];
    foreach ($counts as $r) $counts_by_id[(int) $r->tag_id] = (int) $r->cnt;

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Tags</h1>
        <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-subscribers')); ?>">Back to subscribers</a>
        <p class="description">Create and manage tags used for segmentation.</p>
        <hr class="wp-header-end" />
        
        <?php ccs_render_newsletter_navigation('cta-newsletter-tags'); ?>

        <div class="postbox" style="margin-top: 16px;">
            <h2 class="hndle">Create tag</h2>
            <div class="inside">
                <form method="post" style="display:flex; gap: 12px; align-items: end; flex-wrap: wrap;">
                    <?php wp_nonce_field('ccs_create_tag'); ?>
                    <div>
                        <label for="tag_name" style="display:block; font-weight:600; margin-bottom: 6px;">Name</label>
                        <input type="text" id="tag_name" name="tag_name" class="regular-text" required />
                    </div>
                    <div>
                        <label for="tag_color" style="display:block; font-weight:600; margin-bottom: 6px;">Colour (optional)</label>
                        <input type="color" id="tag_color" name="tag_color" value="#2271b1" />
                    </div>
                    <div>
                        <button type="submit" class="button button-primary" name="ccs_create_tag" value="1">Add tag</button>
                    </div>
                </form>
            </div>
        </div>

        <h2 style="margin-top: 18px;">All tags</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-primary">Name</th>
                    <th scope="col" class="manage-column">Colour</th>
                    <th scope="col" class="manage-column">Subscribers</th>
                    <th scope="col" class="manage-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tags)) : ?>
                    <tr><td colspan="4">No tags yet.</td></tr>
                <?php else : ?>
                    <?php foreach ($tags as $t) : ?>
                        <?php
                        $tag_id = (int) $t->id;
                        $color = (string) ($t->color ?: '#2271b1');
                        $cnt = (int) ($counts_by_id[$tag_id] ?? 0);
                        $delete_url = wp_nonce_url(admin_url('admin.php?page=cta-newsletter-tags&action=delete&tag_id=' . $tag_id), 'ccs_delete_tag_' . $tag_id);
                        ?>
                        <tr>
                            <td class="column-primary">
                                <strong><?php echo esc_html((string) $t->name); ?></strong>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                            </td>
                            <td>
                                <span style="display:inline-flex; align-items:center; gap:8px;">
                                    <span style="display:inline-block; width:12px; height:12px; border-radius:999px; background: <?php echo esc_attr($color); ?>;"></span>
                                    <code><?php echo esc_html($color); ?></code>
                                </span>
                            </td>
                            <td><?php echo esc_html((string) $cnt); ?></td>
                            <td>
                                <a class="button button-small button-link-delete" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Delete this tag? It will be removed from all subscribers.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Render newsletter navigation tabs
 */
function ccs_render_newsletter_navigation($active_page = 'cta-newsletter') {
    $tabs = [
        ['page' => 'cta-newsletter', 'label' => 'Overview'],
        ['page' => 'cta-newsletter-compose', 'label' => 'Compose'],
        ['page' => 'cta-newsletter-campaigns', 'label' => 'Campaigns'],
        ['page' => 'cta-newsletter-subscribers', 'label' => 'Subscribers'],
        ['page' => 'cta-newsletter-calendar', 'label' => 'Calendar'],
        ['page' => 'cta-newsletter-tags', 'label' => 'Tags'],
        ['page' => 'cta-automation', 'label' => 'Automation'],
        ['page' => 'cta-email-templates', 'label' => 'Templates'],
    ];

    echo '<h2 class="nav-tab-wrapper wp-clearfix" style="margin: 12px 0 0 0;">';
    foreach ($tabs as $tab) {
        $is_active = ($active_page === $tab['page']);
        printf(
            '<a href="%s" class="nav-tab%s">%s</a>',
            esc_url(admin_url('admin.php?page=' . $tab['page'])),
            $is_active ? ' nav-tab-active' : '',
            esc_html($tab['label'])
        );
    }
    echo '</h2>';
}

/**
 * Primary navigation for Newsletter admin area.
 * Uses WordPress core `nav-tab` styles (no custom button styling).
 * @deprecated Use ccs_render_newsletter_navigation() instead
 */
function ccs_render_newsletter_admin_primary_nav($active_page = 'cta-newsletter') {
    ccs_render_newsletter_navigation($active_page);
}

/**
 * Newsletter Overview screen (clean WP admin UI).
 */
function ccs_newsletter_render_overview_screen($active_count, $total_count, $unsubscribed_count, $total_sent, $recent_campaigns) {
    global $wpdb;
    $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
    
    // Calculate growth metrics
    $last_30_days_count = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->prefix}ccs_newsletter_subscribers 
        WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND status = 'active'
    ");
    
    $previous_30_days_count = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->prefix}ccs_newsletter_subscribers 
        WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
        AND subscribed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND status = 'active'
    ");
    
    $growth_percent = 0;
    if ($previous_30_days_count > 0) {
        $growth_percent = round((($last_30_days_count - $previous_30_days_count) / $previous_30_days_count) * 100, 1);
    }
    
    $avg_open_rate = $wpdb->get_var("
        SELECT AVG(CASE WHEN total_sent > 0 THEN (unique_opens / total_sent) * 100 ELSE 0 END)
        FROM $campaigns_table
        WHERE status = 'completed'
    ");
    $avg_open_rate = $avg_open_rate ? round($avg_open_rate, 1) : 0;
    
    $campaigns_this_month = $wpdb->get_var("
        SELECT COUNT(*)
        FROM $campaigns_table
        WHERE YEAR(sent_at) = YEAR(NOW())
        AND MONTH(sent_at) = MONTH(NOW())
    ");
    ?>
    <div class="wrap cta-newsletter-dashboard">
        <h1 class="wp-heading-inline">Newsletter Overview</h1>
        <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-compose')); ?>">
            <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span> Compose New
        </a>
        <hr class="wp-header-end" />

        <?php ccs_render_newsletter_navigation('cta-newsletter'); ?>

        <!-- Modern Statistics Cards -->
        <div class="cta-stats-grid">
            <div class="cta-stat-card card-success">
                <div class="cta-stat-card-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="cta-stat-label">Active Subscribers</div>
                <div class="cta-stat-value"><?php echo esc_html(number_format_i18n((int) $active_count)); ?></div>
                <?php if ($growth_percent != 0) : ?>
                    <div class="cta-stat-change <?php echo $growth_percent > 0 ? 'positive' : 'negative'; ?>">
                        <span class="dashicons dashicons-arrow-<?php echo $growth_percent > 0 ? 'up' : 'down'; ?>-alt"></span>
                        <?php echo abs($growth_percent); ?>% vs last month
                    </div>
                <?php endif; ?>
                <div class="cta-stat-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-subscribers')); ?>">View all →</a>
                </div>
            </div>
            
            <div class="cta-stat-card card-info">
                <div class="cta-stat-card-icon">
                    <span class="dashicons dashicons-email-alt"></span>
                </div>
                <div class="cta-stat-label">Emails Sent</div>
                <div class="cta-stat-value"><?php echo esc_html(number_format_i18n((int) $total_sent)); ?></div>
                <div class="cta-stat-change">
                    <?php echo esc_html(number_format_i18n((int) $campaigns_this_month)); ?> campaigns this month
                </div>
                <div class="cta-stat-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-campaigns')); ?>">View campaigns →</a>
                </div>
            </div>
            
            <div class="cta-stat-card card-purple">
                <div class="cta-stat-card-icon">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="cta-stat-label">Avg Open Rate</div>
                <div class="cta-stat-value"><?php echo esc_html($avg_open_rate); ?>%</div>
                <div class="cta-stat-change">
                    Industry avg: 21%
                </div>
                <div class="cta-stat-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-campaigns')); ?>">View stats →</a>
                </div>
            </div>
            
            <div class="cta-stat-card card-warning">
                <div class="cta-stat-card-icon">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="cta-stat-label">Unsubscribed</div>
                <div class="cta-stat-value"><?php echo esc_html(number_format_i18n((int) $unsubscribed_count)); ?></div>
                <div class="cta-stat-change">
                    <?php 
                    $unsubscribe_rate = $total_count > 0 ? round(($unsubscribed_count / $total_count) * 100, 1) : 0;
                    echo esc_html($unsubscribe_rate); 
                    ?>% of total
                </div>
                <div class="cta-stat-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-subscribers&status_filter=unsubscribed')); ?>">View list →</a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="cta-quick-actions">
            <h2>Quick Actions</h2>
            <div class="cta-action-buttons">
                <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-compose')); ?>" class="cta-action-btn">
                    <span class="dashicons dashicons-edit"></span>
                    <span>Compose Newsletter</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-subscribers')); ?>" class="cta-action-btn">
                    <span class="dashicons dashicons-groups"></span>
                    <span>Manage Subscribers</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cta-email-templates')); ?>" class="cta-action-btn">
                    <span class="dashicons dashicons-admin-page"></span>
                    <span>Email Templates</span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-calendar')); ?>" class="cta-action-btn">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span>View Calendar</span>
                </a>
            </div>
        </div>

        <!-- Recent Campaigns -->
        <div class="cta-recent-campaigns">
            <h2>Recent Campaigns</h2>
            
            <?php if (empty($recent_campaigns)) : ?>
                <div class="cta-empty-state">
                    <div class="cta-empty-icon" aria-hidden="true">
                        <span class="dashicons dashicons-email-alt"></span>
                    </div>
                    <h3 class="cta-empty-title">No campaigns yet</h3>
                    <p class="cta-empty-text">
                        Create your first newsletter to get started!
                    </p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-compose')); ?>" class="button button-primary button-large">
                        <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span> Create First Campaign
                    </a>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-primary" style="width: 40%;">Subject</th>
                            <th scope="col" class="manage-column">Date</th>
                            <th scope="col" class="manage-column">Recipients</th>
                            <th scope="col" class="manage-column">Opens</th>
                            <th scope="col" class="manage-column">Clicks</th>
                            <th scope="col" class="manage-column">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_campaigns as $c) : 
                            $open_rate = ($c->total_sent > 0) ? round(($c->unique_opens / $c->total_sent) * 100, 1) : 0;
                            $click_rate = ($c->total_sent > 0) ? round(($c->unique_clicks / $c->total_sent) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td class="column-primary">
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-campaigns&view=detail&campaign=' . $c->id)); ?>">
                                            <?php echo esc_html((string) ($c->subject ?? '(No subject)')); ?>
                                        </a>
                                    </strong>
                                    <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                                </td>
                                <td>
                                    <?php echo esc_html($c->sent_at ? wp_date('M j, Y', strtotime($c->sent_at)) : '—'); ?>
                                    <br><small style="color: #646970;"><?php echo esc_html($c->sent_at ? wp_date('g:i a', strtotime($c->sent_at)) : ''); ?></small>
                                </td>
                                <td><strong><?php echo esc_html(number_format_i18n((int) ($c->total_sent ?? 0))); ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html(number_format_i18n((int) ($c->unique_opens ?? 0))); ?></strong>
                                    <br><small style="color: #646970;"><?php echo esc_html($open_rate); ?>%</small>
                                </td>
                                <td>
                                    <strong><?php echo esc_html(number_format_i18n((int) ($c->unique_clicks ?? 0))); ?></strong>
                                    <br><small style="color: #646970;"><?php echo esc_html($click_rate); ?>%</small>
                                </td>
                                <td>
                                    <?php
                                    $status = $c->status ?? 'completed';
                                    $badge_class = match($status) {
                                        'completed' => 'cta-badge success',
                                        'scheduled' => 'cta-badge warning',
                                        'draft' => 'cta-badge',
                                        default => 'cta-badge primary'
                                    };
                                    ?>
                                    <span class="<?php echo esc_attr($badge_class); ?>"><?php echo esc_html(ucfirst($status)); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (count($recent_campaigns) >= 5) : ?>
                    <div style="text-align: center; margin-top: var(--admin-spacing-lg); padding-top: var(--admin-spacing-lg); border-top: 1px solid #f0f0f1;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=cta-newsletter-campaigns')); ?>" class="button">
                            View All Campaigns
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Admin page content
 */
function ccs_newsletter_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    $tags_table = $wpdb->prefix . 'ccs_newsletter_tags';
    $rel_table = $wpdb->prefix . 'ccs_newsletter_subscriber_tags';
    
    // Handle remove all unsubscribed
    if (isset($_POST['remove_all_unsubscribed']) && check_admin_referer('ccs_remove_all_unsubscribed')) {
        $deleted = $wpdb->query("DELETE FROM $table_name WHERE status = 'unsubscribed'");
        echo '<div class="notice notice-success is-dismissible"><p>✅ ' . sprintf(_n('%d unsubscribed subscriber removed.', '%d unsubscribed subscribers removed.', $deleted, 'cta'), $deleted) . '</p></div>';
    }
    
    // Handle bulk actions
    if (isset($_POST['bulk_action']) && isset($_POST['subscriber_ids']) && is_array($_POST['subscriber_ids']) && check_admin_referer('ccs_newsletter_bulk_action')) {
        $action = sanitize_text_field($_POST['bulk_action']);
        $ids = array_map('absint', $_POST['subscriber_ids']);
        
        if ($action === 'unsubscribe' && !empty($ids)) {
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET status = 'unsubscribed', unsubscribed_at = %s WHERE id IN (" . implode(',', array_fill(0, count($ids), '%d')) . ")",
                array_merge([current_time('mysql')], $ids)
            ));
            echo '<div class="notice notice-success"><p>' . sprintf(_n('%d subscriber unsubscribed.', '%d subscribers unsubscribed.', $updated, 'cta'), $updated) . '</p></div>';
        } elseif ($action === 'delete' && !empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_name WHERE id IN ($placeholders)",
                $ids
            ));
            echo '<div class="notice notice-success"><p>' . sprintf(_n('%d subscriber deleted.', '%d subscribers deleted.', $deleted, 'cta'), $deleted) . '</p></div>';
        } elseif ($action === 'reactivate' && !empty($ids)) {
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET status = 'active', unsubscribed_at = NULL WHERE id IN (" . implode(',', array_fill(0, count($ids), '%d')) . ")",
                $ids
            ));
            echo '<div class="notice notice-success"><p>' . sprintf(_n('%d subscriber reactivated.', '%d subscribers reactivated.', $updated, 'cta'), $updated) . '</p></div>';
        } elseif (($action === 'add_tag' || $action === 'remove_tag') && !empty($ids)) {
            $tag_id = absint($_POST['bulk_tag_id'] ?? 0);
            if ($tag_id <= 0) {
                echo '<div class="notice notice-error"><p>Please choose a tag.</p></div>';
            } else {
                if ($action === 'add_tag') {
                    ccs_newsletter_assign_tags($ids, [$tag_id]);
                    echo '<div class="notice notice-success"><p>Tag applied to selected subscribers.</p></div>';
                } else {
                    $deleted = ccs_newsletter_remove_tags($ids, [$tag_id]);
                    echo '<div class="notice notice-success"><p>Tag removed from selected subscribers.</p></div>';
                }
            }
        }
    }
    
    // Handle CSV import
    if (isset($_POST['ccs_import_newsletter_csv']) && check_admin_referer('ccs_import_newsletter_csv')) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $result = ccs_import_newsletter_csv($_FILES['csv_file']);
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Please select a CSV file to import.</p></div>';
        }
    }
    
    // Handle actions
    if (isset($_POST['ccs_send_newsletter']) && check_admin_referer('ccs_send_newsletter')) {
        ccs_send_newsletter_email();
    }
    
    // Handle cancel scheduled campaign
    if (isset($_GET['action']) && $_GET['action'] === 'cancel_scheduled' && isset($_GET['campaign'])) {
        $campaign_id = absint($_GET['campaign']);
        if (check_admin_referer('cancel_scheduled_' . $campaign_id)) {
            $cancelled = ccs_cancel_scheduled_campaign($campaign_id);
            if ($cancelled) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Scheduled campaign cancelled successfully. All pending emails have been removed from the queue.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>❌ Failed to cancel scheduled campaign. It may have already been sent or cancelled.</p></div>';
            }
        }
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        if (check_admin_referer('delete_subscriber_' . $_GET['id'])) {
            $wpdb->delete($table_name, ['id' => absint($_GET['id'])], ['%d']);
            echo '<div class="notice notice-success"><p>Subscriber removed.</p></div>';
        }
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'unsubscribe' && isset($_GET['id'])) {
        if (check_admin_referer('unsubscribe_' . $_GET['id'])) {
            $wpdb->update(
                $table_name,
                ['status' => 'unsubscribed', 'unsubscribed_at' => current_time('mysql')],
                ['id' => absint($_GET['id'])],
                ['%s', '%s'],
                ['%d']
            );
            echo '<div class="notice notice-success"><p>Subscriber marked as unsubscribed.</p></div>';
        }
    }
    
    // Get filter/search parameters
    $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'all';
    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $tag_filter = isset($_GET['tag_filter']) ? absint($_GET['tag_filter']) : 0;
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    
    // Build query with filters
    $where_clauses = [];
    $query_params = [];
    
    if ($status_filter !== 'all') {
        $where_clauses[] = "status = %s";
        $query_params[] = $status_filter;
    }
    
    if (!empty($search_query)) {
        $where_clauses[] = "(email LIKE %s OR first_name LIKE %s OR last_name LIKE %s)";
        $search_like = '%' . $wpdb->esc_like($search_query) . '%';
        $query_params[] = $search_like;
        $query_params[] = $search_like;
        $query_params[] = $search_like;
    }

    if ($tag_filter > 0) {
        $where_clauses[] = "EXISTS (SELECT 1 FROM $rel_table st WHERE st.subscriber_id = $table_name.id AND st.tag_id = %d)";
        $query_params[] = $tag_filter;
    }
    
    if (!empty($date_from)) {
        $where_clauses[] = "DATE(subscribed_at) >= %s";
        $query_params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_clauses[] = "DATE(subscribed_at) <= %s";
        $query_params[] = $date_to;
    }
    
    $order_by = isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby'] . ' ' . (isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC')) : 'subscribed_at DESC';
    
    // Pagination parameters
    $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 50;
    $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    if ($current_page < 1) $current_page = 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM $table_name";
    if (!empty($where_clauses)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        $count_query .= ' ' . $where_sql;
        if (!empty($query_params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }
    } else {
        $total_items = $wpdb->get_var($count_query);
    }
    $total_pages = $total_items > 0 ? ceil($total_items / $per_page) : 1;
    
    // Get filtered subscribers with pagination
    if (!empty($where_clauses)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        $query = "SELECT * FROM $table_name $where_sql ORDER BY $order_by LIMIT %d OFFSET %d";
        if (!empty($query_params)) {
            $subscribers = $wpdb->get_results($wpdb->prepare($query, array_merge($query_params, [$per_page, $offset])));
        } else {
            $subscribers = $wpdb->get_results($wpdb->prepare($query, [$per_page, $offset]));
        }
    } else {
    $subscribers = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $order_by LIMIT %d OFFSET %d", [$per_page, $offset]));
    }

    // Tags data for UI
    $all_tags = ccs_newsletter_get_tags();
    $subscribers_on_page_ids = array_map(static function($s) { return (int) $s->id; }, $subscribers ?: []);
    $subscriber_tag_ids_map = ccs_newsletter_get_tag_ids_for_subscribers($subscribers_on_page_ids);
    $tags_by_id = [];
    foreach ($all_tags as $t) {
        $tags_by_id[(int) $t->id] = $t;
    }
    
    // Handle template loading
    $loaded_template = null;
    if (isset($_GET['use_template'])) {
        $template_id = absint($_GET['use_template']);
        $templates_table = $wpdb->prefix . 'ccs_email_templates';
        $loaded_template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $templates_table WHERE id = %d", $template_id));
        
        if (!$loaded_template) {
            echo '<div class="notice notice-error"><p>Template not found. Please select a valid template.</p></div>';
        }
    }
    
    $active_count = ccs_get_subscriber_count('active');
    $unsubscribed_count = ccs_get_subscriber_count('unsubscribed');
    $total_count = count($wpdb->get_results("SELECT id FROM $table_name"));

    // Overview-only: keep this screen calm; the old “tabs + stepper + everything” UI has been split
    // into dedicated submenu pages (Compose/Campaigns/Subscribers/Calendar/Tags).
    $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
    $total_sent = (int) ($wpdb->get_var("SELECT SUM(total_sent) FROM $campaigns_table") ?: 0);
    $recent_campaigns = $wpdb->get_results("SELECT * FROM $campaigns_table ORDER BY sent_at DESC LIMIT 5");
    ccs_newsletter_render_overview_screen($active_count, $total_count, $unsubscribed_count, $total_sent, $recent_campaigns);
    return;
    
    ?>
    
    <div class="wrap cta-newsletter-wrap">
        <!-- Error/Success Messages Container -->
        <div id="cta-newsletter-messages" class="cta-newsletter-messages"></div>
        
        <!-- Header -->
        <h1 class="wp-heading-inline">Newsletter</h1>
        <p class="description">Manage subscribers, send campaigns, and track performance.</p>
        <?php ccs_render_newsletter_admin_primary_nav('cta-newsletter'); ?>
        
        <!-- Quick Stats Dashboard -->
        <?php
        // Get recent campaign stats
        $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
        $total_sent = $wpdb->get_var("SELECT SUM(total_sent) FROM $campaigns_table");
        $total_sent = $total_sent ? $total_sent : 0;
        ?>
        <div class="cta-stats-grid">
            <div class="cta-stat-card">
                <div class="cta-stat-card-header">
                    <div class="cta-stat-card-icon green">
                        <span class="dashicons dashicons-yes-alt"></span>
                </div>
                    <div class="cta-stat-card-label">Active</div>
                </div>
                <div class="cta-stat-card-value"><?php echo esc_html($active_count); ?></div>
                <div class="cta-stat-card-desc">Subscribers</div>
            </div>
            
            <div class="cta-stat-card">
                <div class="cta-stat-card-header">
                    <div class="cta-stat-card-icon blue">
                        <span class="dashicons dashicons-groups"></span>
                </div>
                    <div class="cta-stat-card-label">Total</div>
                </div>
                <div class="cta-stat-card-value"><?php echo esc_html($total_count); ?></div>
                <div class="cta-stat-card-desc">All Time</div>
            </div>
            
            <div class="cta-stat-card">
                <div class="cta-stat-card-header">
                    <div class="cta-stat-card-icon red">
                        <span class="dashicons dashicons-dismiss"></span>
                </div>
                    <div class="cta-stat-card-label">Unsubscribed</div>
                </div>
                <div class="cta-stat-card-value"><?php echo esc_html($unsubscribed_count); ?></div>
                <div class="cta-stat-card-desc">Opted Out</div>
            </div>
            
            <div class="cta-stat-card">
                <div class="cta-stat-card-header">
                    <div class="cta-stat-card-icon orange">
                        <span class="dashicons dashicons-email-alt"></span>
                </div>
                    <div class="cta-stat-card-label">Emails Sent</div>
                </div>
                <div class="cta-stat-card-value"><?php echo number_format($total_sent); ?></div>
                <div class="cta-stat-card-desc">All Campaigns</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="cta-quick-actions-grid">
            <a href="#send-email-form" class="cta-quick-action-card">
                <div class="cta-quick-action-card-content">
                    <span class="dashicons dashicons-edit cta-quick-action-card-icon"></span>
                    <div class="cta-quick-action-card-text">
                        <h3 class="cta-quick-action-card-title">Send Newsletter</h3>
                        <p class="cta-quick-action-card-desc">Compose and send email campaign</p>
                    </div>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=cta-email-templates'); ?>" class="cta-quick-action-card">
                <div class="cta-quick-action-card-content">
                    <span class="dashicons dashicons-admin-page cta-quick-action-card-icon"></span>
                    <div class="cta-quick-action-card-text">
                        <h3 class="cta-quick-action-card-title">Email Templates</h3>
                        <p class="cta-quick-action-card-desc">Browse and manage templates</p>
                    </div>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=cta-automation'); ?>" class="cta-quick-action-card">
                <div class="cta-quick-action-card-content">
                    <span class="dashicons dashicons-controls-repeat cta-quick-action-card-icon"></span>
                    <div class="cta-quick-action-card-text">
                        <h3 class="cta-quick-action-card-title">Automation Flows</h3>
                        <p class="cta-quick-action-card-desc">Set up automated sequences</p>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Tabs for different views (WordPress core nav-tab styles) -->
        <h2 class="nav-tab-wrapper wp-clearfix cta-tabs-nav" style="margin-top: 12px;">
            <a href="#tab-send" class="nav-tab cta-tab-btn nav-tab-active" data-tab="send">
                <span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
                Send Email
            </a>
            <a href="#tab-subscribers" class="nav-tab cta-tab-btn" data-tab="subscribers">
                <span class="dashicons dashicons-groups" aria-hidden="true"></span>
                Subscribers (<?php echo esc_html(count($subscribers)); ?>)
            </a>
            <a href="#tab-campaigns" class="nav-tab cta-tab-btn" data-tab="campaigns">
                <span class="dashicons dashicons-chart-line" aria-hidden="true"></span>
                Campaigns
            </a>
            <a href="#tab-calendar" class="nav-tab cta-tab-btn" data-tab="calendar">
                <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                Calendar
            </a>
        </h2>
            
            <!-- Tab Content: Send Email -->
            <div id="tab-send" class="cta-tab-content active">
        
        <!-- Step-by-Step Workflow -->
        <div class="cta-workflow-steps">
            <div class="cta-workflow-step active" data-step="1">
                <div class="cta-workflow-step-number">1</div>
                <div class="cta-workflow-step-label">Details</div>
            </div>
            <div class="cta-workflow-step" data-step="2">
                <div class="cta-workflow-step-number">2</div>
                <div class="cta-workflow-step-label">Templates</div>
            </div>
            <div class="cta-workflow-step" data-step="3">
                <div class="cta-workflow-step-number">3</div>
                <div class="cta-workflow-step-label">Design</div>
            </div>
            <div class="cta-workflow-step" data-step="4">
                <div class="cta-workflow-step-number">4</div>
                <div class="cta-workflow-step-label">Review</div>
            </div>
        </div>
        
        <div id="ai-email-custom-input" class="cta-ai-input-container">
            <input type="text" id="ai-email-custom-topic" class="cta-ai-input" placeholder="Enter your email topic...">
        </div>
        
        <div id="ai-email-loading" class="cta-ai-loading">
            <span class="spinner is-active"></span>
            <p>Generating your email...</p>
        </div>
        
        <!-- Send Email Form -->
        <div class="postbox cta-send-email-form" id="send-email-form">
            <div class="cta-send-email-form-header">
                <h2 class="hndle">
                    <span class="dashicons dashicons-email-alt"></span>
                    Compose Newsletter Email
                </h2>
                <?php if ($active_count > 0) : ?>
                    <div class="subscriber-count">
                        <?php echo $active_count; ?> active subscriber<?php echo $active_count !== 1 ? 's' : ''; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($active_count === 0) : ?>
                <div class="inside">
                    <div class="notice notice-warning">
                        <p>
                            <span class="dashicons dashicons-warning"></span>
                            No active subscribers yet. Emails will be sent once people subscribe to your newsletter.
                        </p>
                    </div>
                </div>
            <?php else : ?>
                <form method="post" id="newsletter-form">
                    <?php wp_nonce_field('ccs_send_newsletter'); ?>
                    
                    <div class="inside">
                        <!-- Recipients / Subject (Details) -->
                        <div class="cta-form-section" id="cta-step-details">
                            <h3 class="cta-form-section-title">
                                <span class="dashicons dashicons-groups"></span>
                                Recipients
                            </h3>
                            <p class="cta-text-muted" style="margin-top: 0;">
                                Choose who should receive this campaign. You can target by tag, then optionally deselect specific people.
                            </p>

                            <fieldset style="border: 0; padding: 0; margin: 0;">
                                <legend class="screen-reader-text">Recipient selection</legend>
                                <label style="display:flex; align-items:center; gap:10px; margin: 8px 0;">
                                    <input type="radio" name="recipient_mode" value="all" checked>
                                    <span>All active subscribers</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:10px; margin: 8px 0;">
                                    <input type="radio" name="recipient_mode" value="tags">
                                    <span>Only subscribers with selected tag(s)</span>
                                </label>
                            </fieldset>

                            <div id="cta-recipient-tags" style="display:none; margin-top: 12px; padding: 14px; background: #f9f9f9; border: 1px solid #dcdcde; border-radius: 8px;">
                                <?php if (empty($all_tags)) : ?>
                                    <p style="margin:0; color:#646970;">No tags yet. Create one in Subscribers → Tags.</p>
                                <?php else : ?>
                                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                                        <?php foreach ($all_tags as $t) : ?>
                                            <label style="display:flex; align-items:center; gap:8px; padding: 6px 10px; background:#fff; border:1px solid #dcdcde; border-radius: 999px;">
                                                <input type="checkbox" class="cta-recipient-tag" value="<?php echo esc_attr($t->id); ?>">
                                                <span style="display:inline-block; width:10px; height:10px; border-radius: 999px; background: <?php echo esc_attr($t->color ?: '#2271b1'); ?>;"></span>
                                                <span><?php echo esc_html($t->name); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="display:flex; gap: 10px; align-items:center; margin-top: 12px; flex-wrap:wrap;">
                                        <span id="cta-recipient-count" style="color:#646970; font-size: 13px;">Recipients: —</span>
                                        <button type="button" class="button" id="cta-review-recipients-btn">Review recipients</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Subject Line Section -->
                        <div class="cta-form-section" id="cta-subject-section">
                            <h3 class="cta-form-section-title">
                                <span class="dashicons dashicons-edit"></span>
                                Subject Line
                            </h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="email_subject">Email Subject</label></th>
                                    <td>
                                        <input type="text" id="email_subject" name="email_subject" required
                                               value="<?php echo esc_attr($loaded_template ? (string) $loaded_template->subject : ''); ?>"
                                               placeholder="e.g. New Course Available: Manual Handling Training">
                                        <p class="description">
                                            <button type="button" id="ai-regenerate-subject" class="button button-small cta-mt-1">
                                                <span class="dashicons dashicons-update"></span>
                                                Regenerate with AI
                                            </button>
                                            <button type="button" id="cta-generate-email-subject" class="button button-small cta-mt-1" style="margin-left: 6px;">
                                                ✨ Generate with AI
                                            </button>
                                            <span id="cta-generate-subject-status" style="margin-left: 10px; font-size: 12px;"></span>
                                            <span class="cta-text-muted" style="margin-left: 12px;">Keep it under 60 characters for best results</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Template Section -->
                        <div class="cta-form-section" id="cta-step-templates">
                            <h3 class="cta-form-section-title">
                                <span class="dashicons dashicons-admin-page"></span>
                                Choose a Template
                            </h3>
                            <p class="cta-text-muted cta-mb-3" style="font-size: 14px;">
                                Select a template to start with, or choose "Custom Message" to write from scratch.
                            </p>
                            
                                        <?php
                                        $templates_table = $wpdb->prefix . 'ccs_email_templates';
                                        $saved_templates = $wpdb->get_results("SELECT id, name, subject, content FROM $templates_table ORDER BY is_system DESC, name ASC");
                                        ?>
                            
                            <!-- Template Gallery -->
                            <div class="cta-template-gallery">
                                <!-- Custom Message Option -->
                                <div class="cta-template-card <?php echo !$loaded_template ? 'selected' : ''; ?>" data-template-id="" data-template-type="custom">
                                    <div class="cta-template-card-preview" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        <div class="cta-template-card-preview-inner">
                                            <div style="font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Custom Message</div>
                                            <div style="font-size: 12px; color: #64748b; line-height: 1.5;">Start from scratch and create your own email content</div>
                                        </div>
                                    </div>
                                    <div class="cta-template-card-info">
                                        <div class="cta-template-card-name">Custom Message</div>
                                        <div class="cta-template-card-subject">Write your own email content</div>
                                    </div>
                                </div>
                                
                                <!-- Quick Templates -->
                                <?php
                                $quick_templates = [
                                    'new_course' => ['name' => 'New Course', 'icon' => '📚', 'desc' => 'Announce a new training course'],
                                    'new_article' => ['name' => 'New Article', 'icon' => '📰', 'desc' => 'Share a new blog post'],
                                    'upcoming_courses' => ['name' => 'Upcoming Courses', 'icon' => '📅', 'desc' => 'Remind about upcoming training'],
                                    'quarterly' => ['name' => 'Quarterly Update', 'icon' => '📊', 'desc' => 'Quarterly newsletter update'],
                                    'welcome' => ['name' => 'Welcome Email', 'icon' => '👋', 'desc' => 'Welcome new subscribers'],
                                ];
                                foreach ($quick_templates as $type => $info) : ?>
                                    <div class="cta-template-card" data-template-id="" data-template-type="<?php echo esc_attr($type); ?>">
                                        <div class="cta-template-card-preview">
                                            <div class="cta-template-card-preview-inner">
                                                <div style="font-size: 32px; text-align: center; margin-bottom: 12px;"><?php echo $info['icon']; ?></div>
                                                <div style="font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;"><?php echo esc_html($info['name']); ?></div>
                                                <div style="font-size: 11px; color: #64748b; line-height: 1.4;"><?php echo esc_html($info['desc']); ?></div>
                                            </div>
                                        </div>
                                        <div class="cta-template-card-info">
                                            <div class="cta-template-card-name"><?php echo esc_html($info['name']); ?></div>
                                            <div class="cta-template-card-subject"><?php echo esc_html($info['desc']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- Saved Templates -->
                                            <?php foreach ($saved_templates as $template) : ?>
                                    <div class="cta-template-card <?php echo ($loaded_template && $loaded_template->id == $template->id) ? 'selected' : ''; ?>" data-template-id="<?php echo esc_attr($template->id); ?>" data-template-type="saved">
                                        <div class="cta-template-card-preview">
                                            <div class="cta-template-card-preview-inner">
                                                <div style="font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 8px; line-height: 1.3;"><?php echo esc_html(wp_trim_words($template->subject, 8)); ?></div>
                                                <div style="font-size: 11px; color: #64748b; line-height: 1.4;"><?php echo esc_html(wp_trim_words(strip_tags($template->content), 12)); ?></div>
                                            </div>
                                        </div>
                                        <div class="cta-template-card-info">
                                            <div class="cta-template-card-name"><?php echo esc_html($template->name); ?></div>
                                            <div class="cta-template-card-subject"><?php echo esc_html(wp_trim_words($template->subject, 10)); ?></div>
                                        </div>
                                    </div>
                                            <?php endforeach; ?>
                            </div>
                            
                            <!-- Hidden input for selected template -->
                            <input type="hidden" id="saved_template_id" name="saved_template_id" value="<?php echo $loaded_template ? esc_attr($loaded_template->id) : ''; ?>">
                            <input type="hidden" id="email_type" name="email_type" value="custom">
                            
                            <p class="description" style="margin-top: 20px;">
                                <a href="<?php echo admin_url('admin.php?page=cta-email-templates'); ?>" target="_blank" style="color: var(--cta-blue); text-decoration: none; font-weight: 500;">
                                    <span class="dashicons dashicons-admin-page" style="font-size: 16px; vertical-align: middle;"></span>
                                    Manage templates
                                </a>
                            </p>
                        </div>
                        
                        <?php if ($loaded_template) : ?>
                            <div style="background: #d1ecf1; border-left: 4px solid #2271b1; padding: 12px 16px; margin: 0 24px 24px 24px; border-radius: 4px;">
                                <p style="margin: 0; color: #0c5460; font-size: 14px;">
                                    <span class="dashicons dashicons-yes" style="color: #00a32a; vertical-align: middle;"></span>
                                    <strong>Template loaded:</strong> <?php echo esc_html($loaded_template->name); ?>
                                    <span style="color: #646970; margin-left: 8px;">(Subject and content pre-filled)</span>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Message Content Section -->
                        <div class="cta-form-section" id="cta-step-design">
                            <h3 class="cta-form-section-title">
                                <span class="dashicons dashicons-edit-large"></span>
                                Email Content
                            </h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="email_content">Message</label></th>
                                    <td>
                                        <button type="button" id="cta-generate-email-content" class="button button-small" style="margin-bottom: 10px;">✨ Generate with AI</button>
                                        <span id="cta-generate-content-status" style="margin-left: 10px; font-size: 12px;"></span>
                                        <?php 
                                        $initial_content = $loaded_template ? $loaded_template->content : '';
                                        wp_editor($initial_content, 'email_content', [
                                            'textarea_rows' => 12,
                                            'media_buttons' => false,
                                            'teeny' => false,
                                            'quicktags' => true,
                                            'tinymce' => [
                                                'toolbar1' => 'bold,italic,underline,|,bullist,numlist,|,link,unlink,|,alignleft,aligncenter,alignright,|,forecolor,|,undo,redo',
                                                'toolbar2' => '',
                                            ],
                                        ]);
                                        ?>
                                        <p class="description">
                                            <strong>Available placeholders:</strong> 
                                            <code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;">{site_name}</code>, 
                                            <code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;">{unsubscribe_link}</code>, 
                                            <code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;">{first_name}</code>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Link to Content Section -->
                        <div class="cta-form-section">
                            <h3 class="cta-form-section-title">
                                <span class="dashicons dashicons-admin-links"></span>
                                Link to Content
                            </h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Content Link</th>
                                    <td>
                                        <div class="cta-radio-group">
                                            <label class="cta-radio-option">
                                                <input type="radio" name="link_type" value="none" checked>
                                                <span>No link</span>
                                            </label>
                                            <label class="cta-radio-option">
                                                <input type="radio" name="link_type" value="course">
                                                <span>Link to Course</span>
                                            </label>
                                            <label class="cta-radio-option">
                                                <input type="radio" name="link_type" value="article">
                                                <span>Link to Article</span>
                                            </label>
                                        </div>
                                        
                                        <div id="course_select_wrapper" class="cta-select-wrapper" style="display: none;">
                                            <label for="course_id">Select Course</label>
                                            <select name="course_id" id="course_id">
                                                <option value="">Select a course...</option>
                                                <?php
                                                $courses = get_posts(['post_type' => 'course', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                                                foreach ($courses as $course) {
                                                    $course_url = get_permalink($course->ID);
                                                    echo '<option value="' . $course->ID . '" data-url="' . esc_attr($course_url) . '">' . esc_html($course->post_title) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <div id="article_select_wrapper" class="cta-select-wrapper" style="display: none;">
                                            <label for="article_id">Select Article</label>
                                            <select name="article_id" id="article_id">
                                                <option value="">Select an article...</option>
                                                <?php
                                                $articles = get_posts(['post_type' => 'post', 'posts_per_page' => 20, 'orderby' => 'date', 'order' => 'DESC']);
                                                foreach ($articles as $article) {
                                                    $article_url = get_permalink($article->ID);
                                                    echo '<option value="' . $article->ID . '" data-url="' . esc_attr($article_url) . '">' . esc_html($article->post_title) . ' (' . get_the_date('j M Y', $article) . ')</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Scheduling Section -->
                    <div class="cta-form-section">
                        <h3 class="cta-form-section-title">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            Schedule (Optional)
                        </h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="schedule_enable">Schedule Send</label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="schedule_enable" name="schedule_enable" value="1">
                                        Schedule this email for later
                                    </label>
                                    <div id="schedule-fields" style="display: none; margin-top: 12px; padding: 16px; background: #f9f9f9; border-radius: 8px;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                            <div>
                                                <label for="schedule_date" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Date</label>
                                                <input type="date" id="schedule_date" name="schedule_date" min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px;">
                                            </div>
                                            <div>
                                                <label for="schedule_time" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Time</label>
                                                <input type="time" id="schedule_time" name="schedule_time" style="width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px;">
                                            </div>
                                        </div>
                                        <p class="description" style="margin-top: 8px; font-size: 12px; color: #646970;">
                                            Emails will be sent automatically at the scheduled time. You can cancel scheduled campaigns from the Campaign Stats tab.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="submit" id="cta-step-review">
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                        <button type="button" id="cta-send-email-btn" class="button button-primary">
                            <span class="dashicons dashicons-email-alt" style="font-size: 18px; vertical-align: middle; margin-right: 6px;"></span>
                            Send to <span id="cta-send-count"><?php echo (int) $active_count; ?></span> <span id="cta-send-count-label">recipients</span>
                        </button>
                            <button type="button" id="cta-send-test-btn" class="button">
                                <span class="dashicons dashicons-admin-users" style="font-size: 18px; vertical-align: middle; margin-right: 6px;"></span>
                                Send Test Email
                            </button>
                        <button type="button" id="preview_email" class="button button-secondary">
                            <span class="dashicons dashicons-visibility" style="font-size: 18px; vertical-align: middle; margin-right: 6px;"></span>
                                Preview
                        </button>
                        </div>
                        <div class="cta-form-info" style="margin-top: 12px;">
                            <span class="dashicons dashicons-info"></span>
                            <span>
                                <?php if ($active_count > 500) : ?>
                                    Large lists (>500) are automatically queued and processed in the background.
                                <?php else : ?>
                                    Emails are sent immediately to all active subscribers.
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Test Email Modal -->
                    <div id="test-email-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; align-items: center; justify-content: center;">
                        <div style="background: #fff; border-radius: 8px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                            <h2 style="margin: 0 0 20px 0; font-size: 24px;">Send Test Email</h2>
                            <p style="margin: 0 0 20px 0; color: #646970; font-size: 14px;">
                                Send a test email to yourself to preview how it will look to subscribers.
                            </p>
                            <div style="margin-bottom: 20px;">
                                <label for="test_email_address" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Email Address</label>
                                <input type="email" id="test_email_address" name="test_email_address" 
                                       value="<?php echo esc_attr(get_option('admin_email')); ?>"
                                       placeholder="your@email.com" 
                                       required
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #dcdcde; border-radius: 4px; font-size: 14px;">
                            </div>
                            <div style="display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid #dcdcde; padding-top: 20px;">
                                <button type="button" id="test-email-cancel" class="button">Cancel</button>
                                <button type="button" id="test-email-send" class="button button-primary">
                                    <span class="dashicons dashicons-email-alt" style="font-size: 16px; vertical-align: middle; margin-right: 6px;"></span>
                                    Send Test Email
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Pre-Send Confirmation Modal -->
                <div id="cta-send-confirmation-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; align-items: center; justify-content: center;">
                    <div style="background: #fff; border-radius: 8px; padding: 30px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <h2 style="margin: 0 0 20px 0; font-size: 24px; color: #1d2327;">You're About to Send This Email</h2>
                        <p style="margin: 0 0 24px 0; color: #646970; font-size: 14px; line-height: 1.6;">
                            Please confirm the following before sending to <strong><span id="cta-confirm-recipient-count"><?php echo (int) $active_count; ?></span> <span id="cta-confirm-recipient-label">recipients</span></strong>:
                        </p>
                        
                        <div style="margin-bottom: 24px;">
                            <label style="display: flex; align-items: flex-start; padding: 12px; background: #f6f7f7; border-radius: 4px; margin-bottom: 12px; cursor: pointer;">
                                <input type="checkbox" id="confirm-subject" required style="margin-right: 12px; margin-top: 2px; cursor: pointer;">
                                <span style="flex: 1; color: #1d2327; font-size: 14px; line-height: 1.5;">
                                    <strong>Subject line is correct and compelling</strong><br>
                                    <span style="color: #646970; font-size: 13px;" id="subject-preview"></span>
                                </span>
                            </label>
                            
                            <label style="display: flex; align-items: flex-start; padding: 12px; background: #f6f7f7; border-radius: 4px; margin-bottom: 12px; cursor: pointer;">
                                <input type="checkbox" id="confirm-content" required style="margin-right: 12px; margin-top: 2px; cursor: pointer;">
                                <span style="flex: 1; color: #1d2327; font-size: 14px; line-height: 1.5;">
                                    <strong>Email content has been reviewed and is error-free</strong>
                                </span>
                            </label>
                            
                            <label style="display: flex; align-items: flex-start; padding: 12px; background: #f6f7f7; border-radius: 4px; margin-bottom: 12px; cursor: pointer;">
                                <input type="checkbox" id="confirm-links" required style="margin-right: 12px; margin-top: 2px; cursor: pointer;">
                                <span style="flex: 1; color: #1d2327; font-size: 14px; line-height: 1.5;">
                                    <strong>All links are working and point to the correct content</strong>
                                </span>
                            </label>
                            
                            <label style="display: flex; align-items: flex-start; padding: 12px; background: #f6f7f7; border-radius: 4px; margin-bottom: 12px; cursor: pointer;">
                                <input type="checkbox" id="confirm-recipients" required style="margin-right: 12px; margin-top: 2px; cursor: pointer;">
                                <span style="flex: 1; color: #1d2327; font-size: 14px; line-height: 1.5;">
                                    <strong>Recipient count is correct</strong> (<?php echo $active_count; ?> active subscriber<?php echo $active_count !== 1 ? 's' : ''; ?>)
                                </span>
                            </label>
                            
                            <label style="display: flex; align-items: flex-start; padding: 12px; background: #f6f7f7; border-radius: 4px; cursor: pointer;">
                                <input type="checkbox" id="confirm-final" required style="margin-right: 12px; margin-top: 2px; cursor: pointer;">
                                <span style="flex: 1; color: #1d2327; font-size: 14px; line-height: 1.5;">
                                    <strong>I'm ready to send this email now</strong>
                                </span>
                            </label>
                        </div>
                        
                        <div style="display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid #dcdcde; padding-top: 20px;">
                            <button type="button" id="cta-cancel-send" class="button" style="margin: 0;">
                                Cancel
                            </button>
                            <button type="button" id="cta-confirm-send" class="button button-primary" style="margin: 0;" disabled>
                                <span class="dashicons dashicons-email-alt" style="font-size: 16px; vertical-align: middle; margin-right: 6px;"></span>
                                Send Email Now
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recipient Review Modal (for tag-targeted sends) -->
                <div id="cta-recipient-review-modal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 100000; align-items: center; justify-content: center;">
                    <div role="dialog" aria-modal="true" aria-labelledby="cta-recipient-review-title" style="background:#fff; border-radius: 8px; padding: 20px; width: 92%; max-width: 760px; max-height: 80vh; overflow: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <div style="display:flex; justify-content: space-between; align-items: center; gap: 12px;">
                            <h2 id="cta-recipient-review-title" style="margin:0; font-size: 18px;">Review recipients</h2>
                            <button type="button" class="button" id="cta-recipient-review-close">Close</button>
                        </div>
                        <p style="margin: 12px 0; color:#646970;">Uncheck anyone you don’t want to email for this campaign.</p>
                        <div style="display:flex; gap: 10px; align-items:center; margin-bottom: 10px; flex-wrap: wrap;">
                            <button type="button" class="button" id="cta-recipient-select-all">Select all</button>
                            <button type="button" class="button" id="cta-recipient-select-none">Select none</button>
                            <span id="cta-recipient-review-count" style="color:#646970; margin-left: auto;"></span>
                        </div>
                        <div id="cta-recipient-review-list" style="border: 1px solid #dcdcde; border-radius: 8px; padding: 10px; max-height: 46vh; overflow: auto;">
                            <p style="margin:0; color:#646970;">Choose tag(s) first.</p>
                        </div>
                        <div style="display:flex; justify-content:flex-end; gap: 10px; margin-top: 14px; border-top: 1px solid #dcdcde; padding-top: 14px;">
                            <button type="button" class="button" id="cta-recipient-review-cancel">Cancel</button>
                            <button type="button" class="button button-primary" id="cta-recipient-review-save">Use selected recipients</button>
                        </div>
                    </div>
                </div>
                
                <!-- Deliverability Notice -->
                <details class="cta-email-deliverability-tips" style="margin-top: 20px;">
                    <summary style="padding: 12px 16px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px; cursor: pointer; font-weight: 600; color: #1d2327; font-size: 14px; list-style: none;">
                        <span style="user-select: none;">📧 Email Deliverability Tips</span>
                        <span style="float: right; font-weight: normal; color: #646970; font-size: 12px;">(click to expand)</span>
                    </summary>
                    <div style="padding: 16px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px; margin-top: 4px;">
                        <ul style="margin: 0; padding-left: 20px; color: #646970; font-size: 13px; line-height: 1.6;">
                            <li><strong>Use an SMTP plugin</strong> (WP Mail SMTP, Easy WP SMTP) for better inbox placement</li>
                            <li><strong>Configure SPF, DKIM, and DMARC</strong> DNS records for your email domain</li>
                            <li><strong>Avoid spam trigger words</strong> in subject lines (FREE, CLICK HERE, URGENT in caps)</li>
                            <li><strong>Maintain list hygiene</strong> - remove inactive subscribers regularly</li>
                            <li><strong>Monitor bounce rates</strong> - high bounces hurt sender reputation</li>
                        </ul>
                    </div>
                </details>
            <?php endif; ?>
                </div>
        </div>
        
            <!-- Tab Content: Campaign Stats -->
            <div id="tab-campaigns" class="cta-tab-content" style="display: none; padding: 24px;">
                <?php
                // Check if viewing queue for specific campaign
                $view_queue = isset($_GET['view']) && $_GET['view'] === 'queue' && isset($_GET['campaign']);
                $queue_campaign_id = $view_queue ? absint($_GET['campaign']) : 0;
                
                if ($view_queue && $queue_campaign_id) {
                    // Show queue progress
                    $queue_table = $wpdb->prefix . 'ccs_email_queue';
                    $total = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d",
                        $queue_campaign_id
                    ));
                    $sent = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d AND status = 'sent'",
                        $queue_campaign_id
                    ));
                    $pending = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d AND status = 'pending'",
                        $queue_campaign_id
                    ));
                    $failed = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d AND status = 'failed'",
                        $queue_campaign_id
                    ));
                    $processing = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d AND status = 'processing'",
                        $queue_campaign_id
                    ));
                    
                    $progress = $total > 0 ? round(($sent / $total) * 100, 1) : 0;
                    
                    // Handle retry action
                    if (isset($_GET['action']) && $_GET['action'] === 'retry' && check_admin_referer('retry_failed_' . $queue_campaign_id)) {
                        $retried = ccs_retry_failed_emails($queue_campaign_id);
                        if ($retried > 0) {
                            echo '<div class="notice notice-success"><p>✅ ' . $retried . ' failed email' . ($retried !== 1 ? 's' : '') . ' queued for retry.</p></div>';
                            // Refresh counts
                            $failed = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d AND status = 'failed'",
                                $queue_campaign_id
                            ));
                            $pending = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d AND status = 'pending'",
                                $queue_campaign_id
                            ));
                        }
                    }
                    ?>
                    <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h2 style="margin: 0; font-size: 20px;">Campaign Progress</h2>
                            <a href="<?php echo admin_url('admin.php?page=cta-newsletter&tab=campaigns'); ?>" class="button">Back to Campaigns</a>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-weight: 600; color: #1d2327;">Progress</span>
                                <span style="color: #646970; font-size: 14px;"><?php echo $sent; ?> / <?php echo $total; ?> sent (<?php echo $progress; ?>%)</span>
                            </div>
                            <div style="background: #f0f0f1; border-radius: 4px; height: 24px; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, #00a32a 0%, #00d084 100%); height: 100%; width: <?php echo $progress; ?>%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">
                                    <?php if ($progress > 10) : ?><?php echo $progress; ?>%<?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 20px;">
                            <div style="text-align: center; padding: 16px; background: #f9f9f9; border-radius: 8px;">
                                <div style="font-size: 32px; font-weight: 700; color: #00a32a; margin-bottom: 4px;"><?php echo $sent; ?></div>
                                <div style="font-size: 13px; color: #646970;">Sent</div>
                            </div>
                            <div style="text-align: center; padding: 16px; background: #f9f9f9; border-radius: 8px;">
                                <div style="font-size: 32px; font-weight: 700; color: #2271b1; margin-bottom: 4px;"><?php echo $pending; ?></div>
                                <div style="font-size: 13px; color: #646970;">Pending</div>
                            </div>
                            <div style="text-align: center; padding: 16px; background: #f9f9f9; border-radius: 8px;">
                                <div style="font-size: 32px; font-weight: 700; color: #dba617; margin-bottom: 4px;"><?php echo $processing; ?></div>
                                <div style="font-size: 13px; color: #646970;">Processing</div>
                            </div>
                            <div style="text-align: center; padding: 16px; background: #f9f9f9; border-radius: 8px;">
                                <div style="font-size: 32px; font-weight: 700; color: #d63638; margin-bottom: 4px;"><?php echo $failed; ?></div>
                                <div style="font-size: 13px; color: #646970;">Failed</div>
                            </div>
                        </div>
                        
                        <?php if ($failed > 0) : ?>
                            <div class="notice notice-warning" style="margin-top: 20px;">
                                <p style="margin: 0 0 12px 0; font-weight: 600;">
                                    <span class="dashicons dashicons-warning" style="vertical-align: middle;"></span>
                                    <?php echo $failed; ?> email<?php echo $failed !== 1 ? 's' : ''; ?> failed to send
                                </p>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cta-newsletter&view=queue&campaign=' . $queue_campaign_id . '&action=retry'), 'retry_failed_' . $queue_campaign_id); ?>" class="button button-small">
                                    <span class="dashicons dashicons-update" style="font-size: 16px; vertical-align: middle;"></span>
                                    Retry Failed Emails
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <script>
                        // Auto-refresh progress every 5 seconds if still processing
                        <?php if ($pending > 0 || $processing > 0) : ?>
                        setTimeout(function() {
                            window.location.reload();
                        }, 5000);
                        <?php endif; ?>
                        </script>
                    </div>
                    <?php
                }
                
                $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
                // Check if status column exists
                $columns = $wpdb->get_col("DESC $campaigns_table", 0);
                $has_status = in_array('status', $columns);
                
                // Get recent campaigns, excluding cancelled ones
                $recent_campaigns = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $campaigns_table 
                         WHERE " . ($has_status ? "status != 'cancelled' OR status IS NULL" : "1=1") . "
                         ORDER BY sent_at DESC, id DESC LIMIT 10"
                    )
                );
                
                // Add status if column doesn't exist (for compatibility)
                if (!$has_status && !empty($recent_campaigns)) {
                    foreach ($recent_campaigns as $campaign) {
                        $campaign->status = 'completed';
                    }
                }
                if (!empty($recent_campaigns)) : ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h2 style="margin: 0; font-size: 24px; font-weight: 700; color: var(--cta-text);">Recent Campaigns</h2>
                        <div style="display: flex; gap: 8px;">
                            <select id="campaign-sort" style="padding: 8px 12px; border: 2px solid var(--cta-border); border-radius: 8px; font-size: 13px;">
                                <option value="date_desc">Date (Newest)</option>
                                <option value="date_asc">Date (Oldest)</option>
                                <option value="opens_desc">Most Opens</option>
                                <option value="clicks_desc">Most Clicks</option>
                            </select>
                        </div>
                    </div>
                    <div style="background: var(--cta-white); border-radius: 16px; border: 1px solid var(--cta-border); overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--cta-gray-light); border-bottom: 2px solid var(--cta-border);">
                                    <th style="padding: 16px; text-align: left; font-weight: 600; color: var(--cta-text); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Campaign</th>
                                    <th style="padding: 16px; text-align: left; font-weight: 600; color: var(--cta-text); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Date</th>
                                    <th style="padding: 16px; text-align: center; font-weight: 600; color: var(--cta-text); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Sent</th>
                                    <th style="padding: 16px; text-align: center; font-weight: 600; color: var(--cta-text); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Opened</th>
                                    <th style="padding: 16px; text-align: center; font-weight: 600; color: var(--cta-text); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Clicked</th>
                                    <th style="padding: 16px; text-align: center; font-weight: 600; color: var(--cta-text); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Open Rate</th>
                                    <th style="padding: 16px; text-align: center; font-weight: 600; color: var(--cta-text); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Click Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_campaigns as $campaign) : 
                                    $open_rate = $campaign->total_sent > 0 ? round(($campaign->unique_opens / $campaign->total_sent) * 100, 1) : 0;
                                    $click_rate = $campaign->total_sent > 0 ? round(($campaign->unique_clicks / $campaign->total_sent) * 100, 1) : 0;
                                    $ctr = $campaign->unique_opens > 0 ? round(($campaign->unique_clicks / $campaign->unique_opens) * 100, 1) : 0;
                                    $campaign_status = isset($campaign->status) ? $campaign->status : 'completed';
                                ?>
                                    <tr style="border-bottom: 1px solid var(--cta-border); transition: background 0.2s;" onmouseover="this.style.background='var(--cta-gray-light)';" onmouseout="this.style.background='var(--cta-white)';">
                                        <td style="padding: 16px;">
                                            <div style="font-weight: 600; color: var(--cta-text); margin-bottom: 4px;"><?php echo esc_html($campaign->subject); ?></div>
                                            <?php if ($campaign_status === 'scheduled') : ?>
                                                <div style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 6px; font-size: 11px; font-weight: 500; margin-top: 6px;">
                                                    <span class="dashicons dashicons-calendar-alt" style="font-size: 12px;"></span>
                                                    Scheduled
                                                </div>
                                                <div style="margin-top: 6px;">
                                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cta-newsletter&tab=campaigns&action=cancel_scheduled&campaign=' . $campaign->id), 'cancel_scheduled_' . $campaign->id); ?>" 
                                                       style="font-size: 12px; color: #d63638; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" 
                                                       onclick="return confirm('Cancel this scheduled campaign?');">
                                                        <span class="dashicons dashicons-dismiss" style="font-size: 14px;"></span>
                                                        Cancel
                                                    </a>
                                                </div>
                                            <?php elseif ($campaign_status === 'queued') : ?>
                                                <a href="<?php echo admin_url('admin.php?page=cta-newsletter&view=queue&campaign=' . $campaign->id); ?>" 
                                                   style="font-size: 12px; color: var(--cta-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-top: 6px;">
                                                    <span class="dashicons dashicons-chart-line" style="font-size: 14px;"></span>
                                                    View Progress
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 16px; color: var(--cta-gray-dark);">
                                            <?php 
                                            if (empty($campaign->sent_at) && $campaign_status === 'scheduled') {
                                                echo '<span style="color: var(--cta-gray-dark);">Scheduled</span>';
                                            } elseif (!empty($campaign->sent_at)) {
                                                echo esc_html(date('j M Y', strtotime($campaign->sent_at)));
                                                echo '<div style="font-size: 12px; color: var(--cta-gray);">' . esc_html(date('g:i a', strtotime($campaign->sent_at))) . '</div>';
                                            } else {
                                                echo '<span style="color: var(--cta-gray);">—</span>';
                                            }
                                            ?>
                                        </td>
                                        <td style="padding: 16px; text-align: center;">
                                            <div style="font-weight: 600; color: var(--cta-text); font-size: 15px;"><?php echo number_format($campaign->total_sent); ?></div>
                                        </td>
                                        <td style="padding: 16px; text-align: center;">
                                            <div style="font-weight: 600; color: var(--cta-text); font-size: 15px;"><?php echo number_format($campaign->unique_opens); ?></div>
                                            <?php if ($campaign->total_opened > $campaign->unique_opens) : ?>
                                                <div style="font-size: 11px; color: var(--cta-gray);"><?php echo number_format($campaign->total_opened); ?> total</div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 16px; text-align: center;">
                                            <div style="font-weight: 600; color: var(--cta-text); font-size: 15px;"><?php echo number_format($campaign->unique_clicks); ?></div>
                                            <?php if ($campaign->total_clicked > $campaign->unique_clicks) : ?>
                                                <div style="font-size: 11px; color: var(--cta-gray);"><?php echo number_format($campaign->total_clicked); ?> total</div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 16px; text-align: center;">
                                            <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: <?php echo $open_rate >= 20 ? 'rgba(0, 201, 141, 0.1)' : ($open_rate >= 10 ? 'rgba(245, 158, 11, 0.1)' : 'rgba(239, 68, 68, 0.1)'); ?>; border-radius: 8px;">
                                                <strong style="color: <?php echo $open_rate >= 20 ? '#00c98d' : ($open_rate >= 10 ? '#f59e0b' : '#ef4444'); ?>; font-size: 15px;">
                                                    <?php echo $open_rate; ?>%
                                                </strong>
                                            </div>
                                        </td>
                                        <td style="padding: 16px; text-align: center;">
                                            <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: <?php echo $click_rate >= 3 ? 'rgba(0, 201, 141, 0.1)' : ($click_rate >= 1 ? 'rgba(245, 158, 11, 0.1)' : 'rgba(239, 68, 68, 0.1)'); ?>; border-radius: 8px;">
                                                <strong style="color: <?php echo $click_rate >= 3 ? '#00c98d' : ($click_rate >= 1 ? '#f59e0b' : '#ef4444'); ?>; font-size: 15px;">
                                                    <?php echo $click_rate; ?>%
                                                </strong>
                                            </div>
                                            <?php if ($campaign->unique_opens > 0) : ?>
                                                <div style="font-size: 11px; color: var(--cta-gray); margin-top: 4px;">CTR: <?php echo $ctr; ?>%</div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p style="margin-top: 16px; color: var(--cta-gray-dark); font-size: 13px; line-height: 1.6;">
                        <strong>Note:</strong> Open rates are approximate. Some email clients block images by default, which prevents accurate tracking. 
                        Click rates are more reliable as they require user interaction.
                    </p>
                <?php else : ?>
                    <div style="padding: 40px; text-align: center; background: #f9f9f9; border-radius: 8px; border: 2px dashed #dcdcde;">
                        <p style="margin: 0; color: #646970; font-size: 16px;">No campaigns sent yet. Send your first email to see statistics here.</p>
        </div>
        <?php endif; ?>
            </div>
            
            <!-- Tab Content: Calendar -->
            <div id="tab-calendar" class="cta-tab-content" style="display: none; padding: 24px;">
                <?php
                // Get scheduled campaigns
                $queue_table = $wpdb->prefix . 'ccs_email_queue';
                $scheduled_campaigns = $wpdb->get_results(
                    "SELECT c.*, 
                     (SELECT scheduled_for FROM $queue_table WHERE campaign_id = c.id LIMIT 1) as scheduled_time
                     FROM $campaigns_table c
                     WHERE c.status = 'scheduled'
                     ORDER BY scheduled_time ASC"
                );
                
                // Get all sent campaigns for calendar
                $all_campaigns = $wpdb->get_results(
                    "SELECT * FROM $campaigns_table 
                     WHERE sent_at IS NOT NULL AND sent_at != ''
                     ORDER BY sent_at DESC"
                );
                
                // Build campaign dates array for JavaScript
                $campaign_dates_js = [];
                foreach ($all_campaigns as $camp) {
                    if (!empty($camp->sent_at)) {
                        $date = date('Y-m-d', strtotime($camp->sent_at));
                        if (!isset($campaign_dates_js[$date])) {
                            $campaign_dates_js[$date] = [];
                        }
                        $campaign_dates_js[$date][] = $camp->subject;
                    }
                }
                ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2 style="margin: 0; font-size: 24px; font-weight: 700; color: var(--cta-text);">Campaign Calendar</h2>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <button type="button" id="calendar-prev-month" class="button" style="padding: 8px 16px;">
                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                        </button>
                        <span id="calendar-month-year" style="font-weight: 600; color: var(--cta-text); min-width: 150px; text-align: center;">
                            <?php echo date('F Y'); ?>
                        </span>
                        <button type="button" id="calendar-next-month" class="button" style="padding: 8px 16px;">
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </button>
                    </div>
                </div>
                
                <!-- Scheduled Campaigns -->
                <?php if (!empty($scheduled_campaigns)) : ?>
                    <div style="background: var(--cta-white); border-radius: 16px; border: 1px solid var(--cta-border); padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: var(--cta-text); display: flex; align-items: center; gap: 8px;">
                            <span class="dashicons dashicons-calendar-alt" style="color: var(--cta-blue);"></span>
                            Scheduled Campaigns
                        </h3>
                        <div style="display: grid; gap: 12px;">
                            <?php foreach ($scheduled_campaigns as $scheduled) : 
                                $scheduled_date = !empty($scheduled->scheduled_time) ? $scheduled->scheduled_time : '';
                                if ($scheduled_date) :
                                    $date_obj = new DateTime($scheduled_date);
                                    $formatted_date = $date_obj->format('l, F j, Y');
                                    $formatted_time = $date_obj->format('g:i A');
                            ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; background: var(--cta-gray-light); border-radius: 10px; border: 2px solid var(--cta-border);">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: var(--cta-text); margin-bottom: 4px;"><?php echo esc_html($scheduled->subject); ?></div>
                                        <div style="font-size: 13px; color: var(--cta-gray-dark); display: flex; align-items: center; gap: 8px;">
                                            <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; color: var(--cta-blue);"></span>
                                            <?php echo esc_html($formatted_date); ?> at <?php echo esc_html($formatted_time); ?>
                                        </div>
                                        <div style="font-size: 12px; color: var(--cta-gray); margin-top: 4px;">
                                            <?php echo number_format($scheduled->total_sent); ?> recipients
                                        </div>
                                    </div>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cta-newsletter&tab=campaigns&action=cancel_scheduled&campaign=' . $scheduled->id), 'cancel_scheduled_' . $scheduled->id); ?>"
                                       class="button-link-delete"
                                       onclick="return confirm('Cancel this scheduled campaign?');">
                                        Cancel
                                    </a>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Calendar Grid -->
                <div style="background: var(--cta-white); border-radius: 16px; border: 1px solid var(--cta-border); padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    <div id="cta-calendar-details" class="notice notice-info" style="margin: 0 0 16px 0;">
                        <p style="margin: 0;">
                            Click a date to see what was sent that day, or schedule a new campaign.
                        </p>
                    </div>
                    <div id="campaign-calendar" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px;">
                        <!-- Calendar will be generated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
        // Tab switching
        function setActiveTab(tab) {
            if (!tab) return;

            var $buttons = $('.cta-tab-btn');
            var $panels = $('.cta-tab-content');
            var $activeBtn = $buttons.filter('[data-tab="' + tab + '"]');
            var $activePanel = $('#tab-' + tab);

            if (!$activeBtn.length || !$activePanel.length) {
                return;
            }

            $buttons.removeClass('nav-tab-active').attr({
                'aria-selected': 'false',
                'tabindex': '-1'
            });
            $panels.removeClass('active').hide().attr('hidden', true);

            $activeBtn.addClass('nav-tab-active').attr({
                'aria-selected': 'true',
                'tabindex': '0'
            });
            $activePanel.addClass('active').show().removeAttr('hidden');
        }

        // Initialize ARIA roles for tabs
        $('.cta-tabs-nav').attr('role', 'tablist');
        $('.cta-tab-btn').each(function() {
            var tab = $(this).data('tab');
            if (!tab) return;
            $(this).attr({
                'role': 'tab',
                'aria-controls': 'tab-' + tab,
                'aria-selected': $(this).hasClass('nav-tab-active') ? 'true' : 'false',
                'tabindex': $(this).hasClass('nav-tab-active') ? '0' : '-1'
            });
        });
        $('.cta-tab-content').each(function() {
            var id = $(this).attr('id') || '';
            $(this).attr({
                'role': 'tabpanel',
                'tabindex': '0'
            });
            if (!$(this).hasClass('active')) {
                $(this).attr('hidden', true).hide();
            }
        });

        $(document).on('click', '.cta-tab-btn', function(e) {
            e.preventDefault();
            setActiveTab($(this).data('tab'));
        });

        // Keyboard: left/right to move tabs, enter/space to activate
        $(document).on('keydown', '.cta-tab-btn', function(e) {
            var key = e.key;
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End', 'Enter', ' '].includes(key)) return;

            var $tabs = $('.cta-tab-btn');
            var idx = $tabs.index(this);
            if (idx < 0) return;

            if (key === 'Enter' || key === ' ') {
                e.preventDefault();
                setActiveTab($(this).data('tab'));
                return;
            }

            e.preventDefault();
            var nextIdx = idx;
            if (key === 'ArrowLeft') nextIdx = Math.max(0, idx - 1);
            if (key === 'ArrowRight') nextIdx = Math.min($tabs.length - 1, idx + 1);
            if (key === 'Home') nextIdx = 0;
            if (key === 'End') nextIdx = $tabs.length - 1;
            $tabs.eq(nextIdx).focus();
        });
        
        // Calendar functionality
        var currentMonth = new Date().getMonth();
        var currentYear = new Date().getFullYear();
        var campaignDates = <?php echo wp_json_encode($campaign_dates_js); ?>;
        
        function renderCalendar(month, year) {
            var firstDay = new Date(year, month, 1).getDay();
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            
            $('#calendar-month-year').text(monthNames[month] + ' ' + year);
            
            var $calendar = $('#campaign-calendar');
            $calendar.empty();
            
            // Day headers
            var dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayHeaders.forEach(function(day) {
                $calendar.append('<div style="padding: 12px; text-align: center; font-weight: 600; color: var(--cta-gray-dark); font-size: 12px; text-transform: uppercase;">' + day + '</div>');
            });
            
            // Empty cells for days before month starts
            for (var i = 0; i < firstDay; i++) {
                $calendar.append('<div style="padding: 8px; min-height: 80px; background: var(--cta-gray-light); border-radius: 8px;"></div>');
            }
            
            // Days of month
            for (var day = 1; day <= daysInMonth; day++) {
                var dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                var hasCampaign = campaignDates.hasOwnProperty(dateStr);
                var isToday = new Date().toDateString() === new Date(year, month, day).toDateString();
                
                var dayHtml = '<button type="button" class="cta-calendar-day" data-date="' + dateStr + '" style="padding: 8px; min-height: 80px; text-align: left; width: 100%; background: ' + (isToday ? 'rgba(102, 126, 234, 0.1)' : 'var(--cta-white)') + '; border: 2px solid ' + (isToday ? 'var(--cta-blue)' : 'var(--cta-border)') + '; border-radius: 8px; position: relative; cursor: pointer;">';
                dayHtml += '<div style="font-weight: 600; color: var(--cta-text); margin-bottom: 4px;">' + day + '</div>';
                
                if (hasCampaign) {
                    var campaigns = campaignDates[dateStr];
                    campaigns.slice(0, 2).forEach(function(campaign) {
                        dayHtml += '<div style="font-size: 11px; color: var(--cta-white); background: var(--cta-blue); padding: 2px 6px; border-radius: 4px; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' + campaign + '">' + (campaign.length > 15 ? campaign.substring(0, 15) + '...' : campaign) + '</div>';
                    });
                    if (campaigns.length > 2) {
                        dayHtml += '<div style="font-size: 10px; color: var(--cta-gray-dark); margin-top: 2px;">+' + (campaigns.length - 2) + ' more</div>';
                    }
                }
                
                dayHtml += '</button>';
                $calendar.append(dayHtml);
            }
        }
        
        renderCalendar(currentMonth, currentYear);
        
        $('#calendar-prev-month').on('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar(currentMonth, currentYear);
        });
        
        $('#calendar-next-month').on('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentMonth, currentYear);
        });

        // Calendar: click a day to see details / schedule new
        $(document).on('click', '.cta-calendar-day', function() {
            var dateStr = $(this).data('date');
            if (!dateStr) return;

            var campaigns = campaignDates[dateStr] || [];
            var $details = $('#cta-calendar-details');

            var html = '<p style="margin: 0 0 8px 0;"><strong>' + dateStr + '</strong></p>';
            if (campaigns.length) {
                html += '<p style="margin: 0 0 8px 0;">Sent campaigns:</p><ul style="margin: 0 0 8px 20px;">';
                campaigns.slice(0, 8).forEach(function(subject) {
                    html += '<li>' + String(subject) + '</li>';
                });
                if (campaigns.length > 8) {
                    html += '<li>…and ' + (campaigns.length - 8) + ' more</li>';
                }
                html += '</ul>';
            } else {
                html += '<p style="margin: 0 0 8px 0;">No campaigns sent on this date.</p>';
            }

            html += '<div style="display:flex; gap: 10px; flex-wrap: wrap;">' +
                '<button type="button" class="button button-primary cta-calendar-schedule" data-date="' + dateStr + '">Schedule a campaign for this date</button>' +
                '<button type="button" class="button cta-calendar-jump-send">Go to Send Email</button>' +
            '</div>';

            $details
                .removeClass('notice-error notice-warning notice-success')
                .addClass('notice-info')
                .html(html);
        });

        $(document).on('click', '.cta-calendar-jump-send', function() {
            setActiveTab('send');
            setTimeout(function() {
                if ($('#send-email-form').length) {
                    $('html, body').animate({ scrollTop: $('#send-email-form').offset().top - 100 }, 250);
                }
            }, 50);
        });

        $(document).on('click', '.cta-calendar-schedule', function() {
            var dateStr = $(this).data('date');
            if (!dateStr) return;

            setActiveTab('send');
            setTimeout(function() {
                // Enable scheduling + set date (time left for user)
                if ($('#schedule_enable').length) {
                    $('#schedule_enable').prop('checked', true).trigger('change');
                }
                if ($('#schedule_date').length) {
                    $('#schedule_date').val(dateStr);
                }
                if ($('#schedule_time').length) {
                    $('#schedule_time').focus();
                }
                if ($('#send-email-form').length) {
                    $('html, body').animate({ scrollTop: $('#send-email-form').offset().top - 100 }, 250);
                }
            }, 100);
        });
        
        function loadSavedTemplate(templateId) {
            if (!templateId) return;

            // Fetch template via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_get_saved_template',
                    template_id: templateId,
                    nonce: '<?php echo wp_create_nonce('ccs_newsletter_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        if (response.data.subject) {
                            $('#email_subject').val(response.data.subject);
                        }
                        if (response.data.content) {
                            if (typeof tinymce !== 'undefined' && tinymce.get('email_content')) {
                                tinymce.get('email_content').setContent(response.data.content);
                            } else {
                                $('#email_content').val(response.data.content);
                            }
                        }
                    } else {
                        showNewsletterMessage('Failed to load template. Please try again.', 'error');
                    }
                },
                error: function() {
                    showNewsletterMessage('Error loading template. Please try again.', 'error');
                }
            });
        }

        // Template selection (card gallery)
        $('.cta-template-card').on('click', function() {
            var $card = $(this);
            var templateId = $card.data('template-id') || '';
            var templateType = $card.data('template-type') || 'custom';
            
            // Update visual selection
            $('.cta-template-card').removeClass('selected');
            $card.addClass('selected');
            
            // Update hidden inputs
            $('#saved_template_id').val(templateId);
            $('#email_type').val(templateType);
            
            // Update workflow step
            $('.cta-workflow-step[data-step="1"]').addClass('complete').removeClass('active');
            $('.cta-workflow-step[data-step="2"]').addClass('complete').removeClass('active');
            $('.cta-workflow-step[data-step="3"]').addClass('active');
            
            // If it's a saved template, load it into subject/editor
            if (templateId && templateType === 'saved') {
                loadSavedTemplate(templateId);
            }
        });
        
        // Workflow step navigation
        $('.cta-workflow-step').on('click', function() {
            var step = $(this).data('step');
            var targets = {
                1: '#cta-step-details',
                2: '#cta-step-templates',
                3: '#cta-step-design',
                4: '#cta-step-review'
            };
            var target = targets[step] || '';
            if (target && $(target).length) {
                $('html, body').animate({ scrollTop: $(target).offset().top - 110 }, 250);
            }
        });

        // Recipients by tag(s)
        var recipientMode = 'all';
        var recipientList = []; // [{id,email,name}]
        var selectedRecipientIds = null; // array<int> or null meaning "not set yet"

        function getSelectedTagIds() {
            var ids = [];
            $('.cta-recipient-tag:checked').each(function() {
                ids.push(parseInt($(this).val(), 10));
            });
            return ids.filter(Boolean);
        }

        function renderRecipientReviewList() {
            var $list = $('#cta-recipient-review-list');
            if (!recipientList.length) {
                $list.html('<p style="margin:0; color:#646970;">No recipients found for these tag(s).</p>');
                $('#cta-recipient-review-count').text('');
                return;
            }

            var selectedSet = new Set((selectedRecipientIds && selectedRecipientIds.length) ? selectedRecipientIds : recipientList.map(function(r){ return r.id; }));
            var html = '<div style="display:grid; gap: 8px;">';
            recipientList.forEach(function(r) {
                var checked = selectedSet.has(r.id) ? ' checked' : '';
                html += '<label style="display:flex; align-items:center; gap:10px; padding: 8px 10px; border: 1px solid #dcdcde; border-radius: 6px;">' +
                    '<input type="checkbox" class="cta-recipient-checkbox" value="' + r.id + '"' + checked + '>' +
                    '<span style="font-weight:600;">' + (r.email || '') + '</span>' +
                    (r.name ? '<span style="color:#646970;">(' + r.name + ')</span>' : '') +
                '</label>';
            });
            html += '</div>';
            $list.html(html);

            updateRecipientReviewCount();
        }

        function updateRecipientReviewCount() {
            var total = recipientList.length;
            var selected = $('#cta-recipient-review-list .cta-recipient-checkbox:checked').length;
            $('#cta-recipient-review-count').text(selected + ' selected of ' + total);
        }

        function setRecipientCounts(n) {
            $('#cta-recipient-count').text('Recipients: ' + (typeof n === 'number' ? n : '—'));
            if (typeof n === 'number') {
                $('#cta-send-count').text(String(n));
                $('#cta-confirm-recipient-count').text(String(n));
                var label = (n === 1) ? 'recipient' : 'recipients';
                $('#cta-send-count-label').text(label);
                $('#cta-confirm-recipient-label').text(label);
            }
        }

        function fetchRecipientsForTags(tagIds, cb) {
            if (!tagIds || !tagIds.length) {
                recipientList = [];
                selectedRecipientIds = null;
                setRecipientCounts(null);
                if (cb) cb();
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_newsletter_get_recipients_for_tags',
                    nonce: '<?php echo wp_create_nonce('ccs_newsletter_nonce'); ?>',
                    tag_ids: tagIds
                },
                success: function(resp) {
                    if (!resp || !resp.success || !resp.data) {
                        showNewsletterMessage('Failed to load recipients for selected tags.', 'error');
                        recipientList = [];
                        selectedRecipientIds = null;
                        setRecipientCounts(null);
                        if (cb) cb();
                        return;
                    }
                    recipientList = resp.data.recipients || [];
                    selectedRecipientIds = recipientList.map(function(r){ return r.id; });
                    setRecipientCounts(recipientList.length);
                    if (cb) cb();
                },
                error: function() {
                    showNewsletterMessage('Error loading recipients for tags.', 'error');
                    recipientList = [];
                    selectedRecipientIds = null;
                    setRecipientCounts(null);
                    if (cb) cb();
                }
            });
        }

        $('input[name="recipient_mode"]').on('change', function() {
            recipientMode = $(this).val() || 'all';
            if (recipientMode === 'tags') {
                $('#cta-recipient-tags').show();
                fetchRecipientsForTags(getSelectedTagIds());
            } else {
                $('#cta-recipient-tags').hide();
                recipientList = [];
                selectedRecipientIds = null;
                // Default to all active (PHP count)
                setRecipientCounts(<?php echo (int) $active_count; ?>);
            }
        });

        $(document).on('change', '.cta-recipient-tag', function() {
            if (recipientMode !== 'tags') return;
            fetchRecipientsForTags(getSelectedTagIds());
        });

        // Review recipients modal
        var $recModal = $('#cta-recipient-review-modal');
        $('#cta-review-recipients-btn').on('click', function() {
            if (recipientMode !== 'tags') return;
            fetchRecipientsForTags(getSelectedTagIds(), function() {
                renderRecipientReviewList();
                $recModal.css('display', 'flex');
            });
        });
        $('#cta-recipient-review-close, #cta-recipient-review-cancel').on('click', function() { $recModal.hide(); });
        $recModal.on('click', function(e) { if (e.target === $recModal[0]) $recModal.hide(); });
        $(document).on('change', '.cta-recipient-checkbox', updateRecipientReviewCount);
        $('#cta-recipient-select-all').on('click', function() {
            $('#cta-recipient-review-list .cta-recipient-checkbox').prop('checked', true);
            updateRecipientReviewCount();
        });
        $('#cta-recipient-select-none').on('click', function() {
            $('#cta-recipient-review-list .cta-recipient-checkbox').prop('checked', false);
            updateRecipientReviewCount();
        });
        $('#cta-recipient-review-save').on('click', function() {
            var ids = [];
            $('#cta-recipient-review-list .cta-recipient-checkbox:checked').each(function() {
                ids.push(parseInt($(this).val(), 10));
            });
            selectedRecipientIds = ids.filter(Boolean);
            setRecipientCounts(selectedRecipientIds.length);
            $recModal.hide();
        });
        
        // Respect URL tab (and ensure "Use template" lands somewhere obvious)
        try {
            var params = new URLSearchParams(window.location.search);
            var tabParam = params.get('tab');
            if (tabParam) {
                setActiveTab(tabParam);
            }
            if (params.has('use_template')) {
                setActiveTab('send');
                setTimeout(function() {
                    if ($('#send-email-form').length) {
                        $('html, body').animate({ scrollTop: $('#send-email-form').offset().top - 100 }, 250);
                    }
                }, 50);
            }
        } catch (e) {}

        // If viewing queue, switch to campaigns tab (unless explicitly overridden)
        <?php if ($view_queue) : ?>
        setActiveTab('campaigns');
        <?php endif; ?>
        
        // Smooth scroll to send email form (quick action card)
        $('a[href="#send-email-form"]').on('click', function(e) {
            e.preventDefault();
            setActiveTab('send');

            var activeSubscriberCount = <?php echo (int) $active_count; ?>;
            setTimeout(function() {
                if ($('#send-email-form').length) {
                    $('html, body').animate({ scrollTop: $('#send-email-form').offset().top - 100 }, 300);
                }

                // If there are no subscribers, make it explicit what to do next.
                if (activeSubscriberCount === 0) {
                    showNewsletterMessage('No active subscribers yet. Import a CSV or wait for signups before sending.', 'warning');
                    // Move the user to the Subscribers tab (where Import CSV lives)
                    setTimeout(function() {
                        setActiveTab('subscribers');
                        if ($('#show-import-form').length) {
                            $('#show-import-form').focus();
                        }
                    }, 350);
                } else if ($('#email_subject').length) {
                    $('#email_subject').trigger('focus');
                }
            }, 100);
        });
    });
    </script>
            
            <!-- Tab Content: Subscribers -->
            <div id="tab-subscribers" class="cta-tab-content" style="display: none; padding: 24px;">
        <div class="postbox cta-subscribers-list">
            <h2 class="hndle">
                <span>👥 Subscriber List</span>
            </h2>
            <div class="inside">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="button" id="show-import-form" class="button">
                        <span class="dashicons dashicons-upload" style="font-size: 16px; vertical-align: middle; margin-right: 5px;"></span>
                        Import CSV
                    </button>
                    <button type="button" id="export_csv" class="button">
                        <span class="dashicons dashicons-download" style="font-size: 16px; vertical-align: middle; margin-right: 5px;"></span>
                        Export CSV
                    </button>
                </div>
            </div>
            
            <!-- CSV Import Form (hidden by default) -->
            <div id="csv-import-form" style="display: none; background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <h3 style="margin-top: 0;">Import Subscribers from CSV</h3>
                <p style="color: #646970; font-size: 13px; margin-bottom: 16px;">
                    CSV should include columns: <code>email</code> (required), <code>first name</code>, <code>last name</code>, <code>date of birth</code>
                </p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('ccs_import_newsletter_csv'); ?>
                    <input type="file" name="csv_file" accept=".csv" required style="margin-bottom: 12px;">
                    <div>
                        <button type="submit" name="ccs_import_newsletter_csv" class="button button-primary">Import</button>
                        <button type="button" id="hide-import-form" class="button">Cancel</button>
                    </div>
                </form>
            </div>
            
            <!-- Search and Filter Controls -->
            <form method="get" style="background: #f9f9f9; padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <input type="hidden" name="page" value="cta-newsletter">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: end;">
                    <div>
                        <label for="s" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Search</label>
                        <input type="text" id="s" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="Email, name..." style="width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px;">
                    </div>
                    <div>
                        <label for="status_filter" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Status</label>
                        <select id="status_filter" name="status_filter" style="width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px;">
                            <option value="all" <?php selected($status_filter, 'all'); ?>>All</option>
                            <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                            <option value="unsubscribed" <?php selected($status_filter, 'unsubscribed'); ?>>Unsubscribed</option>
                        </select>
                    </div>
                    <div>
                        <label for="tag_filter" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Tag</label>
                        <select id="tag_filter" name="tag_filter" style="width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px;">
                            <option value="0">All tags</option>
                            <?php foreach ($all_tags as $t) : ?>
                                <option value="<?php echo esc_attr($t->id); ?>" <?php selected($tag_filter, (int) $t->id); ?>>
                                    <?php echo esc_html($t->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="date_from" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">From Date</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px;">
                    </div>
                    <div>
                        <label for="date_to" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">To Date</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px;">
                    </div>
                    <div>
                        <button type="submit" class="button button-primary" style="width: 100%;">Apply Filters</button>
                    </div>
                    <?php if ($search_query || $status_filter !== 'all' || $tag_filter > 0 || $date_from || $date_to) : ?>
                    <div>
                        <a href="<?php echo admin_url('admin.php?page=cta-newsletter'); ?>" class="button" style="width: 100%;">Clear Filters</a>
                    </div>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Tags (simple, Outlook-style categories) -->
            <div class="postbox" style="margin: 0 0 16px 0;">
                <h3 style="margin: 0; padding: 12px 16px; border-bottom: 1px solid #dcdcde;">🏷️ Tags</h3>
                <div class="inside" style="padding: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 160px auto; gap: 12px; align-items: end; max-width: 700px;">
                        <div>
                            <label for="cta-new-tag-name" style="display:block; font-weight:600; font-size:13px; margin-bottom: 6px;">New tag name</label>
                            <input type="text" id="cta-new-tag-name" placeholder="e.g. Nursery Worker" style="width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px;">
                        </div>
                        <div>
                            <label for="cta-new-tag-color" style="display:block; font-weight:600; font-size:13px; margin-bottom: 6px;">Colour (optional)</label>
                            <input type="color" id="cta-new-tag-color" value="#2271b1" style="width: 100%; height: 38px; padding: 2px; border: 1px solid #dcdcde; border-radius: 4px;">
                        </div>
                        <div>
                            <button type="button" class="button button-primary" id="cta-create-tag-btn">Add tag</button>
                        </div>
                    </div>
                    <div id="cta-tags-list" style="margin-top: 14px; display:flex; gap: 8px; flex-wrap: wrap;">
                        <?php if (empty($all_tags)) : ?>
                            <span style="color:#646970;">No tags yet.</span>
                        <?php else : ?>
                            <?php foreach ($all_tags as $t) : ?>
                                <span class="cta-tag-chip" data-tag-id="<?php echo esc_attr($t->id); ?>" style="display:inline-flex; align-items:center; gap:6px; padding: 4px 10px; border-radius: 999px; border: 1px solid #dcdcde; background: #fff;">
                                    <span style="display:inline-block; width:10px; height:10px; border-radius: 999px; background: <?php echo esc_attr($t->color ?: '#2271b1'); ?>;"></span>
                                    <span><?php echo esc_html($t->name); ?></span>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <p class="description" style="margin: 12px 0 0 0;">Use tags like Outlook categories: assign them to subscribers, then send to everyone in a tag (with the option to deselect individual recipients before sending).</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <?php if ($status_filter === 'unsubscribed' || $unsubscribed_count > 0) : ?>
                <div class="notice notice-warning" style="margin: 16px 0;">
                    <p style="margin: 0;">
                        <span class="dashicons dashicons-warning" style="vertical-align: middle;"></span>
                        <strong><?php echo number_format($unsubscribed_count); ?></strong> unsubscribed subscriber<?php echo $unsubscribed_count !== 1 ? 's' : ''; ?>.
                        <span style="color:#646970;">Remove unsubscribed subscribers to keep your list clean.</span>
                    </p>
                    <div style="margin-top: 10px;">
                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete all unsubscribed subscribers? This cannot be undone.');">
                            <?php wp_nonce_field('ccs_remove_all_unsubscribed'); ?>
                            <input type="hidden" name="remove_all_unsubscribed" value="1">
                            <button type="submit" class="button button-secondary">
                                <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                Remove All Unsubscribed
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Bulk Actions -->
            <form method="post" id="bulk-actions-form" style="margin-bottom: 16px; display: none;">
                <?php wp_nonce_field('ccs_newsletter_bulk_action'); ?>
                <div style="display: flex; gap: 12px; align-items: center; padding: 12px; background: var(--cta-gray-light); border: 2px solid var(--cta-border); border-radius: 10px;">
                    <select name="bulk_action" id="bulk-action-select" style="padding: 8px 16px; border: 2px solid var(--cta-border); border-radius: 8px; font-size: 14px;">
                        <option value="">Bulk Actions</option>
                        <option value="unsubscribe">Unsubscribe</option>
                        <option value="reactivate">Reactivate</option>
                        <option value="delete">Delete</option>
                        <option value="add_tag">Add tag…</option>
                        <option value="remove_tag">Remove tag…</option>
                    </select>
                    <select name="bulk_tag_id" id="bulk-tag-select" style="display:none; padding: 8px 16px; border: 2px solid var(--cta-border); border-radius: 8px; font-size: 14px;">
                        <option value="">Choose a tag…</option>
                        <?php foreach ($all_tags as $t) : ?>
                            <option value="<?php echo esc_attr($t->id); ?>"><?php echo esc_html($t->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button" id="bulk-action-apply" disabled style="padding: 8px 20px; border-radius: 8px;">Apply</button>
                    <span id="bulk-selected-count" style="color: var(--cta-gray-dark); font-size: 13px; margin-left: auto; font-weight: 500;"></span>
                </div>
            </form>
            
            <?php if (empty($subscribers)) : ?>
                <div style="padding: 40px; text-align: center; background: #f9f9f9; border-radius: 8px; border: 2px dashed #dcdcde;">
                    <p style="margin: 0; color: #646970; font-size: 16px;">
                        <?php if ($search_query || $status_filter !== 'all' || $date_from || $date_to) : ?>
                            No subscribers found matching your filters. <a href="<?php echo admin_url('admin.php?page=cta-newsletter'); ?>">Clear filters</a> to see all subscribers.
                        <?php else : ?>
                            No subscribers yet. They'll appear here once people sign up.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select_all"></th>
                            <th><a href="<?php echo esc_url(add_query_arg(['orderby' => 'email', 'order' => (isset($_GET['orderby']) && $_GET['orderby'] === 'email' && isset($_GET['order']) && $_GET['order'] === 'ASC') ? 'DESC' : 'ASC'], $_SERVER['REQUEST_URI'])); ?>">Email</a></th>
                            <th>Name</th>
                            <th style="width: 100px;">Date of Birth</th>
                            <th style="width: 180px;"><a href="<?php echo esc_url(add_query_arg(['orderby' => 'subscribed_at', 'order' => (isset($_GET['orderby']) && $_GET['orderby'] === 'subscribed_at' && isset($_GET['order']) && $_GET['order'] === 'ASC') ? 'DESC' : 'ASC'], $_SERVER['REQUEST_URI'])); ?>">Subscribed</a></th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 220px;">Tags</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $sub) : ?>
                            <tr>
                                <td><input type="checkbox" name="subscriber_ids[]" value="<?php echo $sub->id; ?>" class="subscriber-checkbox"></td>
                                <td>
                                    <strong><?php echo esc_html($sub->email); ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    $full_name = trim(($sub->first_name ?? '') . ' ' . ($sub->last_name ?? ''));
                                    echo !empty($full_name) ? esc_html($full_name) : '<em>Not provided</em>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($sub->date_of_birth)) {
                                        echo date('j M Y', strtotime($sub->date_of_birth));
                                    } else {
                                        echo '<em>Not provided</em>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('j M Y, g:ia', strtotime($sub->subscribed_at)); ?></td>
                                <td>
                                    <?php if ($sub->status === 'active') : ?>
                                        <span style="display: inline-flex; align-items: center; gap: 6px; color: #00a32a; font-weight: 500;">
                                            <span style="display: inline-block; width: 8px; height: 8px; background: #00a32a; border-radius: 50%;"></span>
                                            Active
                                        </span>
                                    <?php else : ?>
                                        <span style="display: inline-flex; align-items: center; gap: 6px; color: #d63638;">
                                            <span style="display: inline-block; width: 8px; height: 8px; background: #d63638; border-radius: 50%;"></span>
                                            Unsubscribed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $tag_ids = $subscriber_tag_ids_map[(int) $sub->id] ?? [];
                                    $tag_ids_csv = implode(',', array_map('absint', $tag_ids));
                                    ?>
                                    <div class="cta-subscriber-tags" data-subscriber-id="<?php echo esc_attr($sub->id); ?>" data-tag-ids="<?php echo esc_attr($tag_ids_csv); ?>" style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <?php if (empty($tag_ids)) : ?>
                                            <span style="color:#646970; font-size:12px;">—</span>
                                        <?php else : ?>
                                            <?php foreach ($tag_ids as $tid) :
                                                $t = $tags_by_id[(int) $tid] ?? null;
                                                if (!$t) continue;
                                            ?>
                                                <span class="cta-tag-chip" style="display:inline-flex; align-items:center; gap:6px; padding: 2px 8px; border-radius: 999px; border: 1px solid #dcdcde; background: #fff; font-size: 12px;">
                                                    <span style="display:inline-block; width:8px; height:8px; border-radius: 999px; background: <?php echo esc_attr($t->color ?: '#2271b1'); ?>;"></span>
                                                    <span><?php echo esc_html($t->name); ?></span>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <button type="button" class="button button-small cta-edit-tags-btn" data-subscriber-id="<?php echo esc_attr($sub->id); ?>">Tags</button>
                                        <?php if ($sub->status === 'active') : ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cta-newsletter&action=unsubscribe&id=' . $sub->id), 'unsubscribe_' . $sub->id); ?>" 
                                               class="button button-small"
                                               onclick="return confirm('Unsubscribe this email?');">
                                                Unsubscribe
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cta-newsletter&action=delete&id=' . $sub->id), 'delete_subscriber_' . $sub->id); ?>" 
                                           class="button button-small button-link-delete"
                                           onclick="return confirm('Permanently delete this subscriber?');">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Edit tags modal -->
                <div id="cta-tags-modal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 100000; align-items: center; justify-content: center;">
                    <div role="dialog" aria-modal="true" aria-labelledby="cta-tags-modal-title" style="background:#fff; border-radius: 8px; padding: 20px; width: 92%; max-width: 560px; max-height: 80vh; overflow: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <div style="display:flex; justify-content: space-between; align-items: center; gap: 12px;">
                            <h2 id="cta-tags-modal-title" style="margin:0; font-size: 18px;">Edit subscriber tags</h2>
                            <button type="button" class="button" id="cta-tags-modal-close">Close</button>
                        </div>
                        <p style="margin: 12px 0; color:#646970;">Pick tags to assign to this subscriber.</p>
                        <div id="cta-tags-modal-body" style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <?php foreach ($all_tags as $t) : ?>
                                <label style="display:flex; align-items:center; gap:10px; padding: 10px; border: 1px solid #dcdcde; border-radius: 6px;">
                                    <input type="checkbox" class="cta-tag-checkbox" value="<?php echo esc_attr($t->id); ?>">
                                    <span style="display:inline-block; width:10px; height:10px; border-radius: 999px; background: <?php echo esc_attr($t->color ?: '#2271b1'); ?>;"></span>
                                    <span><?php echo esc_html($t->name); ?></span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($all_tags)) : ?>
                                <p style="margin:0; color:#646970;">Create a tag above first.</p>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex; justify-content:flex-end; gap: 10px; margin-top: 16px; border-top: 1px solid #dcdcde; padding-top: 14px;">
                            <button type="button" class="button" id="cta-tags-modal-cancel">Cancel</button>
                            <button type="button" class="button button-primary" id="cta-tags-modal-save">Save tags</button>
                        </div>
                        <input type="hidden" id="cta-tags-modal-subscriber-id" value="">
                    </div>
                </div>
                
                <?php if ($total_pages > 1) : ?>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <span style="color: #646970; font-size: 13px;">
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_items); ?> of <?php echo number_format($total_items); ?> subscriber<?php echo $total_items !== 1 ? 's' : ''; ?>
                        </span>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <?php
                            $base_url = admin_url('admin.php?page=cta-newsletter');
                            $query_args = [];
                            if ($search_query) $query_args['s'] = $search_query;
                            if ($status_filter !== 'all') $query_args['status_filter'] = $status_filter;
                            if (!empty($tag_filter)) $query_args['tag_filter'] = $tag_filter;
                            if ($date_from) $query_args['date_from'] = $date_from;
                            if ($date_to) $query_args['date_to'] = $date_to;
                            if (isset($_GET['orderby'])) $query_args['orderby'] = sanitize_text_field($_GET['orderby']);
                            if (isset($_GET['order'])) $query_args['order'] = sanitize_text_field($_GET['order']);
                            
                            // Previous page
                            if ($current_page > 1) {
                                $query_args['paged'] = $current_page - 1;
                                echo '<a href="' . esc_url(add_query_arg($query_args, $base_url)) . '" class="button">‹ Previous</a>';
                            }
                            
                            // Page numbers
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            if ($start_page > 1) {
                                $query_args['paged'] = 1;
                                echo '<a href="' . esc_url(add_query_arg($query_args, $base_url)) . '" class="button">1</a>';
                                if ($start_page > 2) echo '<span style="padding: 0 8px; color: #646970;">...</span>';
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $query_args['paged'] = $i;
                                $class = $i === $current_page ? 'button button-primary' : 'button';
                                echo '<a href="' . esc_url(add_query_arg($query_args, $base_url)) . '" class="' . $class . '">' . $i . '</a>';
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) echo '<span style="padding: 0 8px; color: #646970;">...</span>';
                                $query_args['paged'] = $total_pages;
                                echo '<a href="' . esc_url(add_query_arg($query_args, $base_url)) . '" class="button">' . $total_pages . '</a>';
                            }
                            
                            // Next page
                            if ($current_page < $total_pages) {
                                $query_args['paged'] = $current_page + 1;
                                echo '<a href="' . esc_url(add_query_arg($query_args, $base_url)) . '" class="button">Next ›</a>';
                            }
                            ?>
                        </div>
                    </div>
                <?php else : ?>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #646970; font-size: 13px;">
                            Showing <?php echo count($subscribers); ?> subscriber<?php echo count($subscribers) !== 1 ? 's' : ''; ?>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            </div>
        </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Helper function to show messages
        function showNewsletterMessage(message, type) {
            type = type || 'info';
            var $container = $('#cta-newsletter-messages');
            var noticeClass = type === 'error' ? 'notice-error' : (type === 'success' ? 'notice-success' : 'notice-info');
            
            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            $container.html($notice);
            
            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $container.offset().top - 100
            }, 300);
        }

        // Ensure the TinyMCE editor doesn't end up vertically centered / offset (seen when CSS leaks in)
        function normalizeEmailEditor() {
            if (typeof tinymce === 'undefined') return;
            var editor = tinymce.get('email_content');
            if (!editor) {
                // TinyMCE may not be registered yet; retry briefly.
                var attempts = 0;
                var timer = setInterval(function() {
                    attempts++;
                    var ed = tinymce.get('email_content');
                    if (ed) {
                        clearInterval(timer);
                        editor = ed;
                        // Re-run now that we have the editor instance.
                        normalizeEmailEditor();
                    }
                    if (attempts >= 10) {
                        clearInterval(timer);
                    }
                }, 250);
                return;
            }

            var applyFix = function() {
                try {
                    var body = editor.getBody();
                    if (!body) return;
                    body.style.display = 'block';
                    body.style.alignItems = '';
                    body.style.justifyContent = '';
                    body.style.margin = '0';
                    body.style.padding = '8px';
                    body.style.boxSizing = 'border-box';
                } catch (e) {
                    // no-op: editor not ready yet
                }
            };

            editor.on('init', applyFix);
            editor.on('SetContent', applyFix);
            editor.on('NodeChange', applyFix);
            applyFix();
        }

        normalizeEmailEditor();
        
        // Global error handler for AJAX requests
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            if (settings.url && settings.url.includes('ccs_')) {
                var errorMessage = 'An error occurred. Please try again.';
                
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your connection.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Permission denied. Please refresh the page.';
                }
                
                showNewsletterMessage(errorMessage, 'error');
            }
        });
        
        // Generate Email Subject with AI (new button)
        $('#cta-generate-email-subject').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-subject-status');
            var $subjectField = $('#email_subject');
            var $contentField = $('#email_content');
            
            // Get email content for context if available
            var emailContent = '';
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('email_content')) {
                emailContent = tinyMCE.get('email_content').getContent({ format: 'text' });
            } else if ($contentField.length) {
                emailContent = $contentField.val();
            }
            
            // Get email type and topic for context
            var emailType = $('#email_type').val() || 'custom';
            var customTopic = '';
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_email_subject',
                    content: emailContent || emailType,
                    nonce: '<?php echo wp_create_nonce('ccs_ai_email'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.subject) {
                        $subjectField.val(response.data.subject);
                        $status.html('<span style="color: #00a32a;">✓ Generated</span>');
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + (response.data && response.data.message ? response.data.message : 'Generation failed') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('✨ Generate with AI');
                }
            });
        });
        
        // Generate Email Content with AI
        $('#cta-generate-email-content').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-content-status');
            var $contentField = $('#email_content');
            var $subjectField = $('#email_subject');
            
            // Get subject line for context
            var subjectLine = $subjectField.val() || '';
            var emailType = $('#email_type').val() || 'custom';
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_newsletter_email',
                    email_type: emailType,
                    subject_line: subjectLine,
                    nonce: '<?php echo wp_create_nonce('ccs_ai_email'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Update subject if provided
                        if (response.data.subject && !$subjectField.val()) {
                            $subjectField.val(response.data.subject);
                        }
                        
                        // Update content
                        if (response.data.content) {
                            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('email_content')) {
                                tinyMCE.get('email_content').setContent(response.data.content);
                            } else if ($contentField.length) {
                                $contentField.val(response.data.content);
                            }
                        }
                        
                        $status.html('<span style="color: #00a32a;">✓ Generated</span>');
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + (response.data && response.data.message ? response.data.message : 'Generation failed') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('✨ Generate with AI');
                }
            });
        });
        
        $('#ai-regenerate-subject').on('click', function() {
            var content = '';
            if (typeof tinymce !== 'undefined' && tinymce.get('email_content')) {
                content = tinymce.get('email_content').getContent({format: 'text'});
            } else {
                content = $('#email_content').val();
            }
            
            if (!content) {
                showNewsletterMessage('Please generate or write email content first.', 'error');
                return;
            }
            
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0 4px 0 0;"></span>Generating...');
            
            $.post(ajaxurl, {
                action: 'ccs_generate_email_subject',
                content: content.substring(0, 500),
                nonce: '<?php echo wp_create_nonce('ccs_ai_email'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $('#email_subject').val(response.data.subject);
                    showNewsletterMessage('Subject line generated successfully!', 'success');
                } else {
                    showNewsletterMessage(response.data && response.data.message ? response.data.message : 'Failed to generate subject line.', 'error');
                }
            })
            .fail(function() {
                showNewsletterMessage('Network error. Please try again.', 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).html(originalText);
            });
        });
        
        function generateAIEmail(type) {
            var customTopic = type === 'custom' ? $('#ai-email-custom-topic').val() : '';
            var courseId = $('#course_id').val();
            var articleId = $('#article_id').val();
            
            // For new_course and new_article types, check if course/article is selected
            if (type === 'new_course' && !courseId) {
                // If no course selected, use the most recent course
                var firstCourse = $('#course_id option:not(:first)').first();
                if (firstCourse.length && firstCourse.val()) {
                    courseId = firstCourse.val();
                    // Auto-select it in the dropdown
                    $('#course_id').val(courseId);
                    // Show the course selector if hidden
                    if ($('input[name="link_type"][value="course"]').length) {
                        $('input[name="link_type"][value="course"]').prop('checked', true).trigger('change');
                    }
                } else {
                    showNewsletterMessage('Please select a course from "Link to Content" section first, or the AI will generate a generic course email.', 'info');
                }
            }
            
            if (type === 'new_article' && !articleId) {
                // If no article selected, use the most recent article
                var firstArticle = $('#article_id option:not(:first)').first();
                if (firstArticle.length && firstArticle.val()) {
                    articleId = firstArticle.val();
                    // Auto-select it in the dropdown
                    $('#article_id').val(articleId);
                    // Show the article selector if hidden
                    if ($('input[name="link_type"][value="article"]').length) {
                        $('input[name="link_type"][value="article"]').prop('checked', true).trigger('change');
                    }
                } else {
                    showNewsletterMessage('Please select an article from "Link to Content" section first, or the AI will generate a generic article email.', 'info');
                }
            }
            
            $('#ai-email-loading').show();
            
            $.post(ajaxurl, {
                action: 'ccs_generate_newsletter_email',
                email_type: type,
                custom_topic: customTopic,
                course_id: courseId,
                article_id: articleId,
                nonce: '<?php echo wp_create_nonce('ccs_ai_email'); ?>'
            }, function(response) {
                $('#ai-email-loading').hide();
                
                if (response.success) {
                    // Set subject
                    $('#email_subject').val(response.data.subject);
                    
                    // Set content in editor
                    if (typeof tinymce !== 'undefined' && tinymce.get('email_content')) {
                        tinymce.get('email_content').setContent(response.data.content);
                    } else {
                        $('#email_content').val(response.data.content);
                    }
                    
                    // Scroll to form
                    $('html, body').animate({
                        scrollTop: $('.cta-send-email-form').offset().top - 50
                    }, 500);
                } else {
                    showNewsletterMessage(response.data && response.data.message ? response.data.message : 'Failed to generate email. Please try again.', 'error');
                }
            }).fail(function() {
                $('#ai-email-loading').hide();
                showNewsletterMessage('Failed to connect to AI service. Please check your API settings.', 'error');
            });
        }
        
        // Function to update button links in editor
        function updateButtonLinks() {
            var linkType = $('input[name="link_type"]:checked').val();
            var editor = typeof tinymce !== 'undefined' && tinymce.get('email_content');
            var content = editor ? editor.getContent() : $('#email_content').val();
            
            if (!content) return;
            
            var updated = false;
            var newContent = content;
            
            if (linkType === 'course') {
                var courseOption = $('#course_id option:selected');
                var courseUrl = courseOption.data('url');
                if (courseUrl) {
                    // Update "Book Your Place Now" button - preserve existing styles
                    newContent = newContent.replace(/<a\s+([^>]*href=["'])[^"']*(["'][^>]*>Book Your Place Now →<\/a>)/gi, 
                        '<a $1' + courseUrl + '$2');
                    updated = true;
                }
            } else if (linkType === 'article') {
                var articleOption = $('#article_id option:selected');
                var articleUrl = articleOption.data('url');
                if (articleUrl) {
                    // Update "Read the Full Article" button - preserve existing styles
                    newContent = newContent.replace(/<a\s+([^>]*href=["'])[^"']*(["'][^>]*>Read the Full Article →<\/a>)/gi,
                        '<a $1' + articleUrl + '$2');
                    updated = true;
                }
            } else {
                // No link - set to fallback URLs
                var coursesUrl = '<?php echo esc_js(get_post_type_archive_link("course") ?: home_url("/courses/")); ?>';
                var newsUrl = '<?php echo esc_js(get_permalink(get_option("page_for_posts")) ?: home_url("/news/")); ?>';
                newContent = newContent.replace(/<a\s+([^>]*href=["'])[^"']*(["'][^>]*>Book Your Place Now →<\/a>)/gi,
                    '<a $1' + coursesUrl + '$2');
                newContent = newContent.replace(/<a\s+([^>]*href=["'])[^"']*(["'][^>]*>Read the Full Article →<\/a>)/gi,
                    '<a $1' + newsUrl + '$2');
                updated = true;
            }
            
            if (updated && newContent !== content) {
                if (editor) {
                    editor.setContent(newContent);
                } else {
                    $('#email_content').val(newContent);
                }
            }
        }
        
        // Toggle link selectors and update button links
        $('input[name="link_type"]').on('change', function() {
            $('#course_select_wrapper, #article_select_wrapper').hide();
            if ($(this).val() === 'course') {
                $('#course_select_wrapper').show();
            } else if ($(this).val() === 'article') {
                $('#article_select_wrapper').show();
            }
            updateButtonLinks();
        });
        
        // Update button links when course/article is selected
        $('#course_id, #article_id').on('change', function() {
            updateButtonLinks();
        });
        
        // Note: legacy dropdown-based template selection used to listen for
        // `#saved_template_id` change events. The UI is now a card gallery, so
        // templates are loaded via `loadSavedTemplate()` above.
        
        // Quick template selection with auto-population
        $('#email_type').on('change', function() {
            var selected = $(this).val();
            var $select = $(this);
            
            $('#saved_template_id').val('');
            
            if (selected === 'custom') {
                return;
            }
            
            // Show loading state
            $select.prop('disabled', true);
            
            // Templates that need auto-population via AJAX
            var smartTemplates = ['upcoming_courses', 'quarterly', 'birthday', 'welcome', 'special_offer'];
            
            if (smartTemplates.indexOf(selected) !== -1) {
                // Fetch populated template via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ccs_get_populated_template',
                        template_type: selected,
                        nonce: '<?php echo wp_create_nonce('ccs_newsletter_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            $('#email_subject').val(response.data.subject);
                            if (typeof tinymce !== 'undefined' && tinymce.get('email_content')) {
                                tinymce.get('email_content').setContent(response.data.content);
                            } else {
                                $('#email_content').val(response.data.content);
                            }
                        } else {
                            showNewsletterMessage('Failed to load template. Please try again.', 'error');
                        }
                        $select.prop('disabled', false);
                    },
                    error: function() {
                        showNewsletterMessage('Error loading template. Please try again.', 'error');
                        $select.prop('disabled', false);
                    }
                });
            } else {
                // Use static templates for new_course and new_article
                var templates = {
                    'new_course': {
                        subject: 'New Course: [Course Name] - Pass Your Next CQC Inspection',
                        content: '<p>Hi {first_name},</p>\n<p><strong>Your next CQC inspection is coming. Will your team be ready?</strong></p>\n<p>We\'ve just launched a new course that gives care professionals exactly what they need to pass inspections with confidence, not anxiety.</p>\n<div style="background: #f9f9f9; border-left: 4px solid #3ba59b; padding: 20px; margin: 24px 0; border-radius: 4px;">\n<p style="margin: 0 0 12px 0;"><strong style="font-size: 18px; color: #2b1b0e;">[Course details will be inserted here]</strong></p>\n<p style="margin: 0 0 8px 0; color: #646970; line-height: 1.6;">This course will help you:</p>\n<ul style="margin: 8px 0 0 0; padding-left: 20px; color: #646970; line-height: 1.8;">\n<li><strong>Meet CQC requirements</strong> with confidence, with no more last-minute panic</li>\n<li><strong>Build team competence</strong> so everyone knows exactly what inspectors look for</li>\n<li><strong>Deliver better care outcomes</strong> that show in your inspection reports</li>\n</ul>\n</div>\n<p><strong>Imagine walking into your next inspection knowing your team is prepared.</strong> No scrambling. No stress. Just confidence.</p>\n<p>This CPD-accredited course is trusted by care providers across Kent and the UK. Many book 2-3 months before their inspection dates to ensure their team is ready.</p>\n<p style="margin: 32px 0 24px 0; text-align: center;"><a href="#" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Reserve Your Team\'s Place →</a></p>\n<p><strong>Spaces fill up quickly</strong>, especially in the months leading up to inspection periods. Secure your spot now to avoid disappointment.</p>\n<p>Questions? Just reply to this email. We\'re here to help.</p>\n<p>Best regards,<br><strong>The CCS Team</strong></p>\n<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Early booking means you get the dates that work for your team\'s schedule. Don\'t wait until the last minute.</p>'
                    },
                    'new_article': {
                        subject: 'How to [Solve Problem]: 5-Minute Guide for Care Professionals',
                        content: '<p>Hi {first_name},</p>\n<p><strong>[Common challenge] is costing you time and stress. Here\'s how to fix it.</strong></p>\n<p>We\'ve just published a practical guide that shows you exactly how to solve this problem, with steps you can implement this week.</p>\n<div style="background: #f9f9f9; border-left: 4px solid #2271b1; padding: 20px; margin: 24px 0; border-radius: 4px;">\n<p style="margin: 0 0 12px 0;"><strong style="font-size: 18px; color: #2b1b0e;">[Article title and summary will be inserted here]</strong></p>\n<p style="margin: 0; color: #646970; line-height: 1.6;">You\'ll discover:</p>\n<ul style="margin: 8px 0 0 0; padding-left: 20px; color: #646970; line-height: 1.8;">\n<li>Practical strategies you can use immediately</li>\n<li>Common mistakes to avoid</li>\n<li>How this improves your CQC compliance</li>\n</ul>\n</div>\n<p><strong>This isn\'t theory. It\'s what works.</strong> Based on latest CQC guidance and used by care providers who consistently pass their inspections.</p>\n<p style="margin: 32px 0 24px 0; text-align: center;"><a href="#" style="display: inline-block; background: linear-gradient(135deg, #2271b1 0%, #1e5a8f 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3);">Read the Full Guide →</a></p>\n<p><strong>This 5-minute read could save you hours of work</strong> and help you avoid common compliance pitfalls.</p>\n<p>Best regards,<br><strong>The CCS Team</strong></p>\n<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Bookmark this for your next inspection prep. It\'s the kind of resource that makes the difference between a good inspection and a great one.</p>'
                    }
                };
                
                if (templates[selected]) {
                    $('#email_subject').val(templates[selected].subject);
                    if (typeof tinymce !== 'undefined' && tinymce.get('email_content')) {
                        tinymce.get('email_content').setContent(templates[selected].content);
                    } else {
                        $('#email_content').val(templates[selected].content);
                    }
                }
                $select.prop('disabled', false);
            }
        });
        
        // Select all checkbox
        $('#select_all').on('change', function() {
            $('.subscriber-checkbox').prop('checked', $(this).is(':checked'));
            updateBulkActions();
        });
        
        // Update bulk actions when checkboxes change
        $('.subscriber-checkbox').on('change', function() {
            updateBulkActions();
        });
        
        function updateBulkActions() {
            var checked = $('.subscriber-checkbox:checked');
            var count = checked.length;
            var $bulkForm = $('#bulk-actions-form');
            var $bulkSelect = $('#bulk-action-select');
            var $bulkApply = $('#bulk-action-apply');
            var $bulkCount = $('#bulk-selected-count');
            
            if (count > 0) {
                $bulkForm.show();
                $bulkCount.text(count + ' selected');
                var action = $bulkSelect.val() || '';
                var needsTag = (action === 'add_tag' || action === 'remove_tag');
                var tagOk = !needsTag || ($('#bulk-tag-select').val() || '').length > 0;
                $bulkApply.prop('disabled', !action || !tagOk);
            } else {
                $bulkForm.hide();
            }
        }
        
        // Enable/disable bulk apply button based on selection
        $('#bulk-action-select').on('change', function() {
            var count = $('.subscriber-checkbox:checked').length;
            var action = $(this).val() || '';
            var needsTag = (action === 'add_tag' || action === 'remove_tag');
            $('#bulk-tag-select').toggle(needsTag);
            var tagOk = !needsTag || ($('#bulk-tag-select').val() || '').length > 0;
            $('#bulk-action-apply').prop('disabled', !action || count === 0 || !tagOk);
        });

        $('#bulk-tag-select').on('change', function() {
            var count = $('.subscriber-checkbox:checked').length;
            var action = $('#bulk-action-select').val() || '';
            var needsTag = (action === 'add_tag' || action === 'remove_tag');
            var tagOk = !needsTag || ($(this).val() || '').length > 0;
            $('#bulk-action-apply').prop('disabled', !action || count === 0 || !tagOk);
        });
        
        // Handle bulk action form submission
        $('#bulk-actions-form').on('submit', function(e) {
            var action = $('#bulk-action-select').val();
            var count = $('.subscriber-checkbox:checked').length;
            
            if (!action || count === 0) {
                e.preventDefault();
                return false;
            }
            
            var actionText = action === 'delete'
                ? 'delete'
                : (action === 'unsubscribe'
                    ? 'unsubscribe'
                    : (action === 'reactivate'
                        ? 'reactivate'
                        : (action === 'add_tag'
                            ? 'add a tag to'
                            : 'remove a tag from')));

            if (action === 'add_tag' || action === 'remove_tag') {
                var tagId = $('#bulk-tag-select').val();
                if (!tagId) {
                    e.preventDefault();
                    showNewsletterMessage('Please choose a tag for this bulk action.', 'error');
                    return false;
                }
            }
            if (!confirm('Are you sure you want to ' + actionText + ' ' + count + ' subscriber(s)?')) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            var $submitBtn = $('#bulk-action-apply');
            var originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true)
                .html('<span class="spinner is-active" style="float: none; margin: 0 8px 0 0;"></span>Processing...')
                .attr('aria-busy', 'true');
            
            // Collect checked IDs and add to form
            $('.subscriber-checkbox:checked').each(function() {
                var id = $(this).val();
                if ($('#bulk-actions-form').find('input[name="subscriber_ids[]"][value="' + id + '"]').length === 0) {
                    $('#bulk-actions-form').append('<input type="hidden" name="subscriber_ids[]" value="' + id + '">');
                }
            });
        });
        
        // CSV Import/Export toggle
        $('#show-import-form').on('click', function() {
            $('#csv-import-form').slideDown();
        });
        
        $('#hide-import-form').on('click', function() {
            $('#csv-import-form').slideUp();
        });

        // Tags: create a new tag
        $('#cta-create-tag-btn').on('click', function() {
            var name = ($('#cta-new-tag-name').val() || '').trim();
            var color = ($('#cta-new-tag-color').val() || '').trim();
            if (!name) {
                showNewsletterMessage('Enter a tag name.', 'error');
                $('#cta-new-tag-name').focus();
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).attr('aria-busy', 'true');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_newsletter_create_tag',
                    nonce: '<?php echo wp_create_nonce('ccs_newsletter_nonce'); ?>',
                    name: name,
                    color: color
                },
                success: function(resp) {
                    if (!resp || !resp.success || !resp.data) {
                        showNewsletterMessage((resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to create tag.', 'error');
                        return;
                    }

                    var t = resp.data;
                    var chip = '<span class="cta-tag-chip" data-tag-id="' + t.id + '" style="display:inline-flex; align-items:center; gap:6px; padding: 4px 10px; border-radius: 999px; border: 1px solid #dcdcde; background: #fff;">' +
                        '<span style="display:inline-block; width:10px; height:10px; border-radius: 999px; background: ' + (t.color || '#2271b1') + ';"></span>' +
                        '<span>' + t.name + '</span>' +
                    '</span>';
                    if ($('#cta-tags-list').find('.cta-tag-chip').length === 0) {
                        $('#cta-tags-list').empty();
                    }
                    $('#cta-tags-list').append(chip);

                    // Add to tag filter + bulk tag select
                    $('#tag_filter').append('<option value="' + t.id + '">' + t.name + '</option>');
                    $('#bulk-tag-select').append('<option value="' + t.id + '">' + t.name + '</option>');

                    // Add to modal checkbox list
                    var modalLabel = '<label style="display:flex; align-items:center; gap:10px; padding: 10px; border: 1px solid #dcdcde; border-radius: 6px;">' +
                        '<input type="checkbox" class="cta-tag-checkbox" value="' + t.id + '">' +
                        '<span style="display:inline-block; width:10px; height:10px; border-radius: 999px; background: ' + (t.color || '#2271b1') + ';"></span>' +
                        '<span>' + t.name + '</span>' +
                    '</label>';
                    $('#cta-tags-modal-body').append(modalLabel);

                    $('#cta-new-tag-name').val('');
                    showNewsletterMessage('Tag created.', 'success');
                },
                error: function() {
                    showNewsletterMessage('Error creating tag. Please try again.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeAttr('aria-busy');
                }
            });
        });

        // Tags: edit subscriber tags modal
        var $tagsModal = $('#cta-tags-modal');
        function openTagsModal(subscriberId) {
            if (!subscriberId) return;
            $('#cta-tags-modal-subscriber-id').val(subscriberId);

            // Reset checks
            $tagsModal.find('.cta-tag-checkbox').prop('checked', false);

            // Read current tag ids from row
            var $container = $('.cta-subscriber-tags[data-subscriber-id="' + subscriberId + '"]');
            var csv = ($container.data('tag-ids') || '').toString();
            var ids = csv ? csv.split(',').map(function(x) { return parseInt(x, 10); }).filter(Boolean) : [];
            ids.forEach(function(id) {
                $tagsModal.find('.cta-tag-checkbox[value="' + id + '"]').prop('checked', true);
            });

            $tagsModal.css('display', 'flex');
            setTimeout(function() {
                $tagsModal.find('.cta-tag-checkbox').first().focus();
            }, 50);
        }

        function closeTagsModal() {
            $tagsModal.hide();
            $('#cta-tags-modal-subscriber-id').val('');
        }

        $(document).on('click', '.cta-edit-tags-btn', function() {
            openTagsModal($(this).data('subscriber-id'));
        });
        $('#cta-tags-modal-close, #cta-tags-modal-cancel').on('click', closeTagsModal);
        $tagsModal.on('click', function(e) {
            if (e.target === $tagsModal[0]) {
                closeTagsModal();
            }
        });

        $('#cta-tags-modal-save').on('click', function() {
            var subscriberId = parseInt($('#cta-tags-modal-subscriber-id').val(), 10);
            if (!subscriberId) return;

            var tagIds = [];
            $tagsModal.find('.cta-tag-checkbox:checked').each(function() {
                tagIds.push(parseInt($(this).val(), 10));
            });

            var $btn = $(this);
            $btn.prop('disabled', true).attr('aria-busy', 'true');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_newsletter_update_subscriber_tags',
                    nonce: '<?php echo wp_create_nonce('ccs_newsletter_nonce'); ?>',
                    subscriber_id: subscriberId,
                    tag_ids: tagIds
                },
                success: function(resp) {
                    if (!resp || !resp.success || !resp.data) {
                        showNewsletterMessage((resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to save tags.', 'error');
                        return;
                    }

                    var tags = resp.data.tags || [];
                    var $container = $('.cta-subscriber-tags[data-subscriber-id="' + subscriberId + '"]');
                    var csv = tags.map(function(t){ return t.id; }).join(',');
                    $container.attr('data-tag-ids', csv).data('tag-ids', csv);

                    if (!tags.length) {
                        $container.html('<span style="color:#646970; font-size:12px;">—</span>');
                    } else {
                        var html = '';
                        tags.forEach(function(t) {
                            html += '<span class="cta-tag-chip" style="display:inline-flex; align-items:center; gap:6px; padding: 2px 8px; border-radius: 999px; border: 1px solid #dcdcde; background: #fff; font-size: 12px;">' +
                                '<span style="display:inline-block; width:8px; height:8px; border-radius: 999px; background: ' + (t.color || '#2271b1') + ';"></span>' +
                                '<span>' + t.name + '</span>' +
                            '</span>';
                        });
                        $container.html(html);
                    }

                    showNewsletterMessage('Tags saved.', 'success');
                    closeTagsModal();
                },
                error: function() {
                    showNewsletterMessage('Error saving tags. Please try again.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeAttr('aria-busy');
                }
            });
        });
        
        // Pre-send confirmation modal
        var $modal = $('#cta-send-confirmation-modal');
        var $sendBtn = $('#cta-send-email-btn');
        var $confirmBtn = $('#cta-confirm-send');
        var $cancelBtn = $('#cta-cancel-send');
        var $form = $('#newsletter-form');
        var $checkboxes = $modal.find('input[type="checkbox"]');
        
        // Show modal when send button is clicked
        $sendBtn.on('click', function(e) {
            e.preventDefault();

            // Guard: recipients by tag must be resolved
            if (recipientMode === 'tags') {
                if (!selectedRecipientIds || selectedRecipientIds.length === 0) {
                    showNewsletterMessage('No recipients selected. Choose tag(s) and review recipients.', 'error');
                    return;
                }
            }
            
            // Get subject for preview
            var subject = $('#email_subject').val() || '(No subject)';
            $('#subject-preview').text(subject);
            
            // Reset all checkboxes
            $checkboxes.prop('checked', false);
            $confirmBtn.prop('disabled', true);
            
            // Show modal
            $modal.css('display', 'flex');
        });
        
        // Close modal on cancel
        $cancelBtn.on('click', function() {
            $modal.hide();
        });
        
        // Close modal on background click
        $modal.on('click', function(e) {
            if (e.target === $modal[0]) {
                $modal.hide();
            }
        });
        
        // Enable/disable confirm button based on checkboxes
        $checkboxes.on('change', function() {
            var allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
            $confirmBtn.prop('disabled', !allChecked);
        });
        
        // Handle final confirmation
        $confirmBtn.on('click', function() {
            if ($checkboxes.filter(':checked').length === $checkboxes.length) {
                // Show loading state
                var originalText = $confirmBtn.html();
                $confirmBtn.prop('disabled', true)
                    .html('<span class="spinner is-active" style="float: none; margin: 0 8px 0 0;"></span>Sending email...')
                    .attr('aria-busy', 'true');
                
                // Disable all form inputs
                $form.find('input, textarea, select, button').prop('disabled', true);
                
                // Prevent modal from closing
                $modal.off('click');
                $cancelBtn.prop('disabled', true);
                
                // Add schedule fields if enabled
                if ($('#schedule_enable').is(':checked')) {
                    var scheduleDate = $('#schedule_date').val();
                    var scheduleTime = $('#schedule_time').val();
                    if (scheduleDate && scheduleTime) {
                        $form.append('<input type="hidden" name="schedule_date" value="' + scheduleDate + '">');
                        $form.append('<input type="hidden" name="schedule_time" value="' + scheduleTime + '">');
                    }
                }

                // Target recipients (optional)
                if (recipientMode === 'tags') {
                    // Ensure we only submit current selection
                    $form.find('input[name="recipient_ids[]"]').remove();
                    (selectedRecipientIds || []).forEach(function(id) {
                        $form.append('<input type="hidden" name="recipient_ids[]" value="' + id + '">');
                    });
                }
                // All checkboxes are checked, submit the form
                $form.append('<input type="hidden" name="ccs_send_newsletter" value="1">');
                $form.submit();
            }
        });
        
        // Schedule enable/disable
        $('#schedule_enable').on('change', function() {
            if ($(this).is(':checked')) {
                $('#schedule-fields').slideDown();
                // Set default time to 1 hour from now
                var now = new Date();
                now.setHours(now.getHours() + 1);
                var dateStr = now.toISOString().split('T')[0];
                var timeStr = now.toTimeString().split(':').slice(0, 2).join(':');
                $('#schedule_date').val(dateStr);
                $('#schedule_time').val(timeStr);
            } else {
                $('#schedule-fields').slideUp();
            }
        });
        
        // Test email modal
        $('#cta-send-test-btn').on('click', function() {
            $('#test-email-modal').css('display', 'flex');
        });
        
        $('#test-email-cancel, #test-email-modal').on('click', function(e) {
            if (e.target === this || $(e.target).attr('id') === 'test-email-cancel') {
                $('#test-email-modal').hide();
            }
        });
        
        $('#test-email-send').on('click', function() {
            var testEmail = $('#test_email_address').val();
            if (!testEmail || !testEmail.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                showNewsletterMessage('Please enter a valid email address.', 'error');
                return;
            }
            
            // Add test email fields and submit
            $form.append('<input type="hidden" name="test_email" value="1">');
            $form.append('<input type="hidden" name="test_email_address" value="' + testEmail + '">');
            $form.append('<input type="hidden" name="ccs_send_newsletter" value="1">');
            $form.submit();
        });
        
        // Export CSV - export all visible/filtered subscribers
        $('#export_csv').on('click', function() {
            // Helper function to properly escape CSV fields
            function escapeCSV(field) {
                if (field === null || field === undefined) return '""';
                var str = String(field);
                // Escape double quotes by doubling them
                str = str.replace(/"/g, '""');
                // Wrap in quotes if contains comma, newline, or quote
                if (str.indexOf(',') !== -1 || str.indexOf('\n') !== -1 || str.indexOf('"') !== -1) {
                    return '"' + str + '"';
                }
                return str;
            }
            
            var csv = 'Email,First Name,Last Name,Date of Birth,Subscribed Date,Status\n';
            $('.subscriber-checkbox').each(function() {
                var $row = $(this).closest('tr');
                var email = $row.find('td').eq(1).text().trim();
                var name = $row.find('td').eq(2).text().trim();
                var nameParts = name.split(' ');
                var firstName = nameParts[0] || '';
                var lastName = nameParts.slice(1).join(' ') || '';
                var dob = $row.find('td').eq(3).text().trim();
                if (dob === 'Not provided' || dob === '<em>Not provided</em>') dob = '';
                var subscribed = $row.find('td').eq(4).text().trim();
                var status = $row.find('td').eq(5).text().trim();
                
                csv += escapeCSV(email) + ',' + escapeCSV(firstName) + ',' + escapeCSV(lastName) + ',' + escapeCSV(dob) + ',' + escapeCSV(subscribed) + ',' + escapeCSV(status) + '\n';
            });
            
            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'newsletter-subscribers-<?php echo date("Y-m-d"); ?>.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        });
        
        // Preview email with mobile and desktop views
        $('#preview_email').on('click', function() {
            var subject = $('#email_subject').val();
            if (!subject) {
                showNewsletterMessage('Please enter a subject line first.', 'error');
                return;
            }
            
            var content = '';
            if (typeof tinymce !== 'undefined' && tinymce.get('email_content')) {
                content = tinymce.get('email_content').getContent();
            } else {
                content = $('#email_content').val();
            }
            
            if (!content) {
                showNewsletterMessage('Please enter email content first.', 'error');
                return;
            }
            
            // Replace placeholders for preview
            var siteName = '<?php echo esc_js(get_bloginfo("name")); ?>';
            content = content.replace(/{site_name}/g, siteName);
            content = content.replace(/{unsubscribe_link}/g, '<a href="#" style="color: #2271b1; text-decoration: underline;">Unsubscribe</a>');
            content = content.replace(/{first_name}/g, 'John'); // Example name for preview
            
            var preview = window.open('', 'Email Preview', 'width=1400,height=900,scrollbars=yes');
            preview.document.write('<!DOCTYPE html><html><head><title>Email Preview: ' + subject + '</title>');
            preview.document.write('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
            preview.document.write('<style>');
            preview.document.write('* { box-sizing: border-box; }');
            preview.document.write('body { margin: 0; padding: 20px; background-color: #e5e5e5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }');
            preview.document.write('.preview-wrapper { display: flex; gap: 30px; max-width: 1400px; margin: 0 auto; }');
            preview.document.write('.preview-view { flex: 1; }');
            preview.document.write('.preview-view-title { background: #2b1b0e; color: #fff; padding: 12px 20px; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px 6px 0 0; }');
            preview.document.write('.preview-view-title.mobile { background: #2271b1; }');
            preview.document.write('.preview-container { background: #fff; border-radius: 0 0 8px 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); overflow: hidden; }');
            preview.document.write('.preview-container.desktop { max-width: 600px; margin: 0 auto; }');
            preview.document.write('.preview-container.mobile { max-width: 375px; margin: 0 auto; }');
            preview.document.write('.preview-header { padding: 32px 40px 24px; border-bottom: 3px solid #3ba59b; background: linear-gradient(135deg, #fefdfb 0%, #ffffff 100%); }');
            preview.document.write('.preview-container.mobile .preview-header { padding: 24px 20px 20px; }');
            preview.document.write('.preview-header h1 { margin: 0; font-size: 26px; font-weight: 700; color: #2b1b0e; line-height: 1.2; letter-spacing: -0.5px; }');
            preview.document.write('.preview-container.mobile .preview-header h1 { font-size: 22px; }');
            preview.document.write('.preview-header p { margin: 8px 0 0 0; font-size: 14px; color: #646970; font-weight: 500; }');
            preview.document.write('.preview-container.mobile .preview-header p { font-size: 12px; }');
            preview.document.write('.preview-subject { padding: 20px 40px; background: #f9f9f9; border-bottom: 1px solid #e0e0e0; }');
            preview.document.write('.preview-container.mobile .preview-subject { padding: 16px 20px; }');
            preview.document.write('.preview-subject strong { display: block; font-size: 11px; text-transform: uppercase; color: #646970; margin-bottom: 6px; letter-spacing: 0.5px; }');
            preview.document.write('.preview-subject span { font-size: 15px; color: #2b1b0e; font-weight: 500; }');
            preview.document.write('.preview-container.mobile .preview-subject span { font-size: 13px; }');
            preview.document.write('.preview-content { padding: 36px 40px; color: #2b1b0e; font-size: 16px; line-height: 1.75; }');
            preview.document.write('.preview-container.mobile .preview-content { padding: 24px 20px; font-size: 14px; line-height: 1.6; }');
            preview.document.write('.preview-content h3 { color: #2b1b0e; font-size: 20px; margin: 32px 0 16px 0; }');
            preview.document.write('.preview-container.mobile .preview-content h3 { font-size: 18px; margin: 24px 0 12px 0; }');
            preview.document.write('.preview-content p { margin: 0 0 16px 0; }');
            preview.document.write('.preview-content div { margin: 24px 0; }');
            preview.document.write('.preview-container.mobile .preview-content div { margin: 16px 0; }');
            preview.document.write('.preview-content a { color: #2271b1; text-decoration: none; }');
            preview.document.write('.preview-content a:hover { text-decoration: underline; }');
            preview.document.write('.preview-content .button-link { display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3); margin: 24px 0; }');
            preview.document.write('.preview-container.mobile .preview-content .button-link { padding: 14px 24px; font-size: 15px; display: block; text-align: center; margin: 20px 0; }');
            preview.document.write('.preview-unsubscribe { padding: 20px 40px; background-color: #fafafa; border-top: 1px solid #e0e0e0; }');
            preview.document.write('.preview-container.mobile .preview-unsubscribe { padding: 16px 20px; }');
            preview.document.write('.preview-unsubscribe p { margin: 0; font-size: 12px; color: #8c8f94; text-align: center; line-height: 1.5; }');
            preview.document.write('.preview-footer { padding: 24px 40px; background-color: #f9f9f9; border-top: 1px solid #e0e0e0; }');
            preview.document.write('.preview-container.mobile .preview-footer { padding: 20px; }');
            preview.document.write('.preview-footer p { margin: 0 0 12px 0; font-size: 13px; color: #646970; text-align: center; line-height: 1.6; }');
            preview.document.write('.preview-container.mobile .preview-footer p { font-size: 11px; }');
            preview.document.write('.preview-footer a { color: #2271b1; text-decoration: none; margin: 0 8px; }');
            preview.document.write('.preview-container.mobile .preview-footer a { margin: 0 4px; font-size: 11px; }');
            preview.document.write('@media (max-width: 1200px) { .preview-wrapper { flex-direction: column; } }');
            preview.document.write('</style>');
            preview.document.write('</head><body>');
            preview.document.write('<div class="preview-wrapper">');
            
            // Desktop view
            preview.document.write('<div class="preview-view">');
            preview.document.write('<div class="preview-view-title">🖥️ Desktop View (600px)</div>');
            preview.document.write('<div class="preview-container desktop">');
            preview.document.write('<div class="preview-header">');
            preview.document.write('<h1>' + siteName + '</h1>');
            preview.document.write('<p>Professional Care Sector Training</p>');
            preview.document.write('</div>');
            preview.document.write('<div class="preview-subject"><strong>Subject:</strong><span> ' + subject + '</span></div>');
            preview.document.write('<div class="preview-content">' + content + '</div>');
            preview.document.write('<div class="preview-unsubscribe"><p>You\'re receiving this because you subscribed to our newsletter. <a href="#">Unsubscribe</a></p></div>');
            preview.document.write('<div class="preview-footer">');
            preview.document.write('<p><strong>Continuity of Care Services</strong><br>The Maidstone Studios, New Cut Road<br>Maidstone, Kent, ME14 5NZ</p>');
            preview.document.write('<p><a href="#">Contact Us</a> | <a href="#">Privacy Policy</a> | <a href="#">Terms</a></p>');
            preview.document.write('</div>');
            preview.document.write('</div>');
            preview.document.write('</div>');
            
            // Mobile view
            preview.document.write('<div class="preview-view">');
            preview.document.write('<div class="preview-view-title mobile">📱 Mobile View (375px)</div>');
            preview.document.write('<div class="preview-container mobile">');
            preview.document.write('<div class="preview-header">');
            preview.document.write('<h1>' + siteName + '</h1>');
            preview.document.write('<p>Professional Care Sector Training</p>');
            preview.document.write('</div>');
            preview.document.write('<div class="preview-subject"><strong>Subject:</strong><span> ' + subject + '</span></div>');
            preview.document.write('<div class="preview-content">' + content + '</div>');
            preview.document.write('<div class="preview-unsubscribe"><p>You\'re receiving this because you subscribed to our newsletter. <a href="#">Unsubscribe</a></p></div>');
            preview.document.write('<div class="preview-footer">');
            preview.document.write('<p><strong>Continuity of Care Services</strong><br>The Maidstone Studios, New Cut Road<br>Maidstone, Kent, ME14 5NZ</p>');
            preview.document.write('<p><a href="#">Contact Us</a> | <a href="#">Privacy Policy</a> | <a href="#">Terms</a></p>');
            preview.document.write('</div>');
            preview.document.write('</div>');
            preview.document.write('</div>');
            
            preview.document.write('</div>');
            preview.document.write('</body></html>');
            preview.document.close();
        });
    });
    </script>
<?php
} // End ccs_newsletter_admin_page()

/**
 * Send newsletter email to all active subscribers
 */
function ccs_send_newsletter_email() {
    if (!ccs_newsletter_user_can_manage()) {
        return;
    }
    
    $subject = sanitize_text_field($_POST['email_subject'] ?? '');
    $content = wp_kses_post($_POST['email_content'] ?? '');
    $link_type = sanitize_text_field($_POST['link_type'] ?? 'none');
    $course_id = absint($_POST['course_id'] ?? 0);
    $article_id = absint($_POST['article_id'] ?? 0);
    $recipient_mode = sanitize_text_field($_POST['recipient_mode'] ?? 'all');
    
    if (empty($subject) || empty($content)) {
        echo '<div class="notice notice-error"><p>Subject and content are required.</p></div>';
        return;
    }
    
    // Add link if selected
    if ($link_type === 'course' && $course_id) {
        $course = get_post($course_id);
        if ($course) {
            $link_html = '<p><a href="' . get_permalink($course_id) . '" style="display: inline-block; background: #2271b1; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px;">View Course: ' . esc_html($course->post_title) . ' →</a></p>';
            $content = str_replace('[Course details will be inserted here]', '<strong>' . esc_html($course->post_title) . '</strong><br>' . wp_trim_words($course->post_content, 30), $content);
            $content = str_replace('<a href="#">Book Your Place Now →</a>', '<a href="' . get_permalink($course_id) . '">Book Your Place Now →</a>', $content);
        }
    } elseif ($link_type === 'article' && $article_id) {
        $article = get_post($article_id);
        if ($article) {
            $content = str_replace('[Article title and summary will be inserted here]', '<strong>' . esc_html($article->post_title) . '</strong><br>' . wp_trim_words($article->post_content, 30), $content);
            $content = str_replace('<a href="#">Read the Full Article →</a>', '<a href="' . get_permalink($article_id) . '">Read the Full Article →</a>', $content);
        }
    } elseif ($link_type === 'none') {
        // If no link selected, replace button with link to courses page as fallback
        $courses_url = get_post_type_archive_link('course') ?: home_url('/courses/');
        $content = str_replace('<a href="#">Book Your Place Now →</a>', '<a href="' . esc_url($courses_url) . '">Book Your Place Now →</a>', $content);
        $content = str_replace('<a href="#">Read the Full Article →</a>', '<a href="' . esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/news/')) . '">Read the Full Article →</a>', $content);
    }
    
    // Check if this is a test email
    $is_test_email = isset($_POST['test_email']) && $_POST['test_email'] === '1';
    $test_email_address = isset($_POST['test_email_address']) ? sanitize_email($_POST['test_email_address']) : '';
    
    if ($is_test_email) {
        if (empty($test_email_address) || !is_email($test_email_address)) {
            echo '<div class="notice notice-error"><p>Please enter a valid test email address.</p></div>';
            return;
        }
        // Send test email to single address
        ccs_send_test_email($subject, $content, $link_type, $course_id, $article_id, $test_email_address);
        return;
    }
    
    // Check for scheduled send
    $schedule_date = isset($_POST['schedule_date']) ? sanitize_text_field($_POST['schedule_date']) : '';
    $schedule_time = isset($_POST['schedule_time']) ? sanitize_text_field($_POST['schedule_time']) : '';
    $is_scheduled = !empty($schedule_date) && !empty($schedule_time);
    
    // Validate scheduled time if provided
    $scheduled_datetime = null;
    if ($is_scheduled) {
        $scheduled_datetime = $schedule_date . ' ' . $schedule_time . ':00';
        $scheduled_timestamp = strtotime($scheduled_datetime);
        $current_timestamp = current_time('timestamp');
        
        if ($scheduled_timestamp === false || $scheduled_timestamp <= $current_timestamp) {
            echo '<div class="notice notice-error"><p>❌ Scheduled time must be in the future. Please select a valid future date and time.</p></div>';
            return;
        }
        $scheduled_datetime = date('Y-m-d H:i:s', $scheduled_timestamp);
    }
    
    // Optional: target specific recipients (e.g. tag-based sends)
    $recipient_ids = isset($_POST['recipient_ids']) ? (array) $_POST['recipient_ids'] : [];
    $recipient_ids = array_values(array_filter(array_map('absint', $recipient_ids)));
    $recipient_tag_ids = isset($_POST['recipient_tag_ids']) ? (array) $_POST['recipient_tag_ids'] : [];
    $recipient_tag_ids = array_values(array_filter(array_map('absint', $recipient_tag_ids)));

    // Get subscribers
    if (!empty($recipient_ids)) {
        global $wpdb;
        $subscribers_table = $wpdb->prefix . 'ccs_newsletter_subscribers';
        $placeholders = implode(',', array_fill(0, count($recipient_ids), '%d'));
        $subscribers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $subscribers_table WHERE status = 'active' AND id IN ($placeholders) ORDER BY subscribed_at DESC",
            $recipient_ids
        ));
    } elseif ($recipient_mode === 'tags') {
        if (empty($recipient_tag_ids)) {
            echo '<div class="notice notice-error"><p>Please select at least one tag.</p></div>';
            return;
        }

        global $wpdb;
        $subscribers_table = $wpdb->prefix . 'ccs_newsletter_subscribers';
        $rel_table = $wpdb->prefix . 'ccs_newsletter_subscriber_tags';
        $placeholders = implode(',', array_fill(0, count($recipient_tag_ids), '%d'));

        // Select active subscribers with any of the selected tags.
        $subscribers = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT s.*
             FROM $subscribers_table s
             INNER JOIN $rel_table st ON st.subscriber_id = s.id
             WHERE s.status = 'active' AND st.tag_id IN ($placeholders)
             ORDER BY s.subscribed_at DESC",
            $recipient_tag_ids
        ));
    } else {
        $subscribers = ccs_get_newsletter_subscribers('active');
    }
    
    if (empty($subscribers)) {
        echo '<div class="notice notice-warning"><p>No active subscribers to send to.</p></div>';
        return;
    }
    
    $subscriber_count = count($subscribers);
    // Always use queue for scheduled emails, or for large lists
    $use_queue = $is_scheduled || $subscriber_count > 500;
    
    // Replace placeholders
    $site_name = get_bloginfo('name');
    
    // Get email configuration
    $from_email = defined('CCS_EMAIL_OFFICE') && !empty(CCS_EMAIL_OFFICE) 
        ? CCS_EMAIL_OFFICE 
        : get_option('admin_email');
    
    $from_name = $site_name;
    
    // Check if email is configured
    if (empty($from_email)) {
        echo '<div class="notice notice-error"><p>❌ Email not configured. Please set CCS_EMAIL_OFFICE in wp-config.php or configure WordPress admin email.</p></div>';
        return;
    }
    
    // Validate email domain matches site domain (helps with deliverability)
    $site_domain = parse_url(home_url(), PHP_URL_HOST);
    $email_domain = substr(strrchr($from_email, "@"), 1);
    
    if ($email_domain !== $site_domain && !defined('CTA_EMAIL_DOMAIN_VERIFIED')) {
        echo '<div class="notice notice-warning is-dismissible"><p>⚠️ <strong>Deliverability Warning:</strong> Your email address domain (' . esc_html($email_domain) . ') doesn\'t match your website domain (' . esc_html($site_domain) . '). This may affect deliverability. Ensure SPF, DKIM, and DMARC records are properly configured for ' . esc_html($email_domain) . '.</p></div>';
    }
    
    $sent_count = 0;
    $failed_count = 0;
    
    // Create campaign record
    global $wpdb;
    $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
    // Check if status column exists
    $columns = $wpdb->get_col("DESC $campaigns_table", 0);
    $has_status = in_array('status', $columns);
    
    $campaign_data = [
        'subject' => $subject,
        'total_sent' => count($subscribers)
    ];
    $campaign_format = ['%s', '%d'];
    
    // Only set sent_at for immediate sends
    if (!$is_scheduled) {
        $campaign_data['sent_at'] = current_time('mysql');
        $campaign_format[] = '%s';
    }
    
    if ($has_status) {
        $campaign_data['status'] = $is_scheduled ? 'scheduled' : 'sending';
        $campaign_format[] = '%s';
    }
    
    $campaign_id = $wpdb->insert(
        $campaigns_table,
        $campaign_data,
        $campaign_format
    );
    
    if ($campaign_id === false) {
        echo '<div class="notice notice-error"><p>❌ Failed to create campaign record. Tracking may not work.</p></div>';
        $campaign_id = 0;
    } else {
        $campaign_id = $wpdb->insert_id;
    }
    
    // Prepare email content once (shared for all subscribers)
    $base_email_content = $content;
    
    foreach ($subscribers as $subscriber) {
        // Create unsubscribe link with token for security
        $unsubscribe_token = wp_hash($subscriber->email . $subscriber->id);
        $unsubscribe_url = add_query_arg([
            'ccs_unsubscribe' => 1,
            'email' => urlencode($subscriber->email),
            'token' => $unsubscribe_token
        ], home_url('/unsubscribe/'));
        $unsubscribe_link = '<a href="' . esc_url($unsubscribe_url) . '" style="color: #2271b1; text-decoration: underline;">Unsubscribe</a>';
        
        // Build comprehensive email headers for better deliverability (per subscriber)
        // These headers are critical for inbox placement and spam avoidance
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'X-Mailer: WordPress/' . get_bloginfo('version'),
            'Message-ID: <' . time() . '.' . md5($subscriber->email . time() . $campaign_id) . '@' . parse_url(home_url(), PHP_URL_HOST) . '>',
        ];
        
        // List-Unsubscribe header (RFC 2369) - CRITICAL for deliverability
        // Gmail, Outlook, and other providers use this to show unsubscribe buttons
        // This significantly improves inbox placement and reduces spam filtering
        $headers[] = 'List-Unsubscribe: <' . esc_url($unsubscribe_url) . '>';
        $headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
        
        // Precedence header to prevent auto-replies (vacation messages, out-of-office, etc.)
        $headers[] = 'Precedence: bulk';
        
        // Auto-Submitted header (RFC 3834) - indicates automated email
        $headers[] = 'Auto-Submitted: auto-generated';
        
        // Get subscriber name for personalization
        $subscriber_name = '';
        if (!empty($subscriber->first_name)) {
            $subscriber_name = $subscriber->first_name;
        }
        
        // Create tracking pixel URL
        $tracking_pixel_url = add_query_arg([
            'ccs_track' => 'open',
            'campaign' => $campaign_id,
            'subscriber' => $subscriber->id,
            'token' => wp_hash($campaign_id . $subscriber->id . $subscriber->email)
        ], home_url('/'));
        
        // Track all links in the email
        $email_content = $base_email_content;
        
        // Replace placeholders first - properly escape text values to prevent quote/apostrophe issues
        // Content is already HTML (from wp_kses_post), but text placeholders need escaping
        $email_content = str_replace(
            ['{site_name}', '{unsubscribe_link}', '{first_name}'],
            [esc_html($site_name), $unsubscribe_link, esc_html($subscriber_name)],
            $email_content
        );
        
        // Track all links (except unsubscribe and tracking links)
        // This is the standard method used by MailChimp, Constant Contact, SendGrid, etc.
        $email_content = preg_replace_callback(
            '/<a\s+([^>]*href=["\'])([^"\']+)(["\'][^>]*)>/i',
            function($matches) use ($campaign_id, $subscriber, $site_name) {
                $url = $matches[2];
                
                // Skip tracking and unsubscribe links (avoid double-tracking)
                if (strpos($url, 'ccs_track') !== false || strpos($url, 'ccs_unsubscribe') !== false) {
                    return $matches[0];
                }
                
                // Skip mailto: links (email links shouldn't be tracked)
                if (strpos($url, 'mailto:') === 0) {
                    return $matches[0];
                }
                
                // Skip anchor-only links (#section) - these don't leave the page
                if (strpos($url, '#') === 0 && strpos($url, '://') === false) {
                    return $matches[0];
                }
                
                // Convert relative URLs to absolute
                if (strpos($url, '://') === false && strpos($url, '//') !== 0) {
                    // Relative URL - make it absolute
                    if (strpos($url, '/') === 0) {
                        // Absolute path
                        $url = home_url($url);
                    } else {
                        // Relative path
                        $url = home_url('/' . ltrim($url, '/'));
                    }
                }
                
                // Create tracked URL (standard link wrapping method)
                // Pattern: Original URL → Tracking URL → Record Click → Redirect to Original
                $tracked_url = add_query_arg([
                    'ccs_track' => 'click',
                    'campaign' => $campaign_id,
                    'subscriber' => $subscriber->id,
                    'url' => urlencode($url),
                    'token' => wp_hash($campaign_id . $subscriber->id . $url)
                ], home_url('/'));
                
                return '<a ' . $matches[1] . esc_url($tracked_url) . $matches[3] . '>';
            },
            $email_content
        );
        
        // Personalize greeting if name is available - handle multiple variations
        if (!empty($subscriber_name)) {
            $email_content = preg_replace('/<p>Hi there,?<\/p>/i', '<p>Hi ' . esc_html($subscriber_name) . ',</p>', $email_content);
            $email_content = preg_replace('/Hi there,?/i', 'Hi ' . esc_html($subscriber_name) . ',', $email_content);
            // Also replace {first_name} placeholder if it wasn't already replaced
            $email_content = str_replace('{first_name}', esc_html($subscriber_name), $email_content);
        } else {
            // If no name, replace placeholder with generic greeting
            $email_content = str_replace('{first_name}', 'there', $email_content);
        }
        
        // If using queue, add to queue instead of sending immediately
        if ($use_queue) {
            $queued = ccs_add_email_to_queue(
                $campaign_id,
                $subscriber->id,
                $subscriber->email,
                $subject,
                $email_content,
                $headers,
                $scheduled_datetime
            );
            
            if ($queued) {
                $sent_count++; // Count as "queued" for now
            } else {
                $failed_count++;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('CCS Newsletter: Failed to add email to queue for ' . $subscriber->email);
                }
            }
            continue; // Skip direct sending
        }
        
        // Wrap in professional HTML email template with improved mobile support
        $html_email = '<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>' . esc_html($subject) . '</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style type="text/css">
        table {border-collapse: collapse; border-spacing: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt;}
        .outlook-group-fix {width:100% !important;}
    </style>
    <![endif]-->
    <style type="text/css">
        /* Reset styles */
        body, table, td, p, a, li, blockquote {-webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;}
        table, td {mso-table-lspace: 0pt; mso-table-rspace: 0pt;}
        img {-ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none;}
        /* Mobile styles */
        @media only screen and (max-width: 600px) {
            .email-container {width: 100% !important; max-width: 100% !important;}
            .email-content {padding: 24px 20px !important;}
            .email-header {padding: 24px 20px !important;}
            .email-footer {padding: 20px !important;}
            h1 {font-size: 22px !important;}
            .button {padding: 14px 24px !important; font-size: 15px !important;}
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
    <!--[if mso | IE]>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td>
    <![endif]-->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5; margin: 0; padding: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!--[if mso | IE]>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
                    <tr>
                        <td>
                <![endif]-->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 600px; width: 100%; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                    <!-- Header -->
                    <tr>
                        <td class="email-header" style="padding: 32px 40px 24px; border-bottom: 3px solid #3ba59b; background: linear-gradient(135deg, #fefdfb 0%, #ffffff 100%);">
                            <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #2b1b0e; line-height: 1.2; letter-spacing: -0.5px;">
                                ' . esc_html($site_name) . '
                            </h1>
                            <p style="margin: 8px 0 0 0; font-size: 14px; color: #646970; font-weight: 500;">
                                Professional Care Sector Training
                            </p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td class="email-content" style="padding: 36px 40px;">
                            <div style="color: #2b1b0e; font-size: 16px; line-height: 1.75;">
                                ' . $email_content . '
                            </div>
                        </td>
                    </tr>
                    <!-- Tracking Pixel -->
                    <tr>
                        <td style="height: 1px; line-height: 1px; font-size: 1px;">
                            <img src="' . esc_url($tracking_pixel_url) . '" width="1" height="1" alt="" style="display: block; width: 1px; height: 1px; border: 0;" />
                        </td>
                    </tr>
                    <!-- Unsubscribe Footer -->
                    <tr>
                        <td style="padding: 20px 40px; background-color: #fafafa; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0; font-size: 12px; color: #8c8f94; text-align: center; line-height: 1.5;">
                                You\'re receiving this because you subscribed to our newsletter. 
                                ' . $unsubscribe_link . '
                            </p>
                            <p style="margin: 8px 0 0 0; font-size: 11px; color: #a7aaad; text-align: center; line-height: 1.4;">
                                We track email opens and clicks to improve our communications. 
                                <a href="' . esc_url(home_url('/privacy-policy/')) . '" style="color: #8c8f94; text-decoration: underline;">Learn more in our Privacy Policy</a>.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td class="email-footer" style="padding: 24px 40px; background-color: #f9f9f9; border-top: 1px solid #e0e0e0; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0 0 12px 0; font-size: 13px; color: #646970; text-align: center; line-height: 1.6;">
                                <strong>Continuity of Care Services</strong><br>
                                The Maidstone Studios, New Cut Road<br>
                                Maidstone, Kent, ME14 5NZ
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #8c8f94; text-align: center; line-height: 1.5;">
                                <a href="' . esc_url(home_url('/contact/')) . '" style="color: #2271b1; text-decoration: none; margin: 0 8px;">Contact Us</a> | 
                                <a href="' . esc_url(home_url('/privacy-policy/')) . '" style="color: #2271b1; text-decoration: none; margin: 0 8px;">Privacy Policy</a> | 
                                <a href="' . esc_url(home_url('/terms-conditions/')) . '" style="color: #2271b1; text-decoration: none; margin: 0 8px;">Terms</a>
                            </p>
                        </td>
                    </tr>
                </table>
                <!--[if mso | IE]>
                        </td>
                    </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
    <!--[if mso | IE]>
            </td>
        </tr>
    </table>
    <![endif]-->
</body>
</html>';
        
        // Send email
        $sent = wp_mail($subscriber->email, $subject, $html_email, $headers);
        
        if ($sent) {
            $sent_count++;
        } else {
            $failed_count++;
            $error_message = 'CCS Newsletter: Failed to send to ' . $subscriber->email;
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $last_error = error_get_last();
                $error_message .= ' - Last error: ' . (is_array($last_error) && isset($last_error['message']) ? $last_error['message'] : 'Unknown error');
            }
            error_log($error_message);
        }
        
        // Rate limiting: Small delay to avoid overwhelming mail server
        // For large lists, increase delay to prevent timeouts and server overload
        $subscriber_count = count($subscribers);
        if ($subscriber_count > 100) {
            usleep(500000); // 0.5 second delay for very large lists
        } elseif ($subscriber_count > 50) {
            usleep(250000); // 0.25 second delay for medium lists
        } elseif ($subscriber_count > 10) {
            usleep(100000); // 0.1 second delay for small lists
        }
        
        // Check for PHP execution time limit (prevent timeouts)
        if ($sent_count % 50 === 0 && $subscriber_count > 50) {
            // Reset execution time every 50 emails for large lists
            @set_time_limit(300); // 5 minutes
        }
    }
    
    // If using queue, trigger queue processing
    if ($use_queue && !$is_scheduled) {
        // For immediate queue processing (large lists), trigger now
        if (!wp_next_scheduled('ccs_process_email_queue')) {
            wp_schedule_single_event(time() + 10, 'ccs_process_email_queue');
        }
    } elseif ($is_scheduled) {
        // For scheduled emails, queue processor will handle them at scheduled time
        // No need to trigger immediately
    }
    
    // Update campaign status
    if ($campaign_id > 0 && $has_status) {
        if ($is_scheduled) {
            // Keep status as 'scheduled' - will be updated to 'sending' when queue starts processing
        } elseif ($use_queue && $sent_count > 0) {
            // Large list queued - status already set to 'sending'
        } else {
            // Direct send completed
            $wpdb->update(
                $campaigns_table,
                ['status' => 'completed'],
                ['id' => $campaign_id],
                ['%s'],
                ['%d']
            );
        }
    }
    
    // Display results with deliverability tips
    if ($sent_count > 0) {
        $stats_link = '';
        if ($campaign_id > 0) {
            $stats_link = ' <a href="' . admin_url('admin.php?page=cta-newsletter-campaigns') . '" style="text-decoration: underline;">View campaign stats</a>.';
        }
        
        if ($is_scheduled) {
            $scheduled_display = date('F j, Y \a\t g:i A', strtotime($scheduled_datetime));
            echo '<div class="notice notice-success is-dismissible"><p>✅ Newsletter scheduled successfully for ' . $sent_count . ' subscriber(s). Emails will be sent on ' . esc_html($scheduled_display) . '.' . $stats_link . '</p></div>';
        } elseif ($use_queue) {
            $deliverability_tip = '';
            // Check if using default wp_mail (may have deliverability issues)
            if (!has_filter('wp_mail')) {
                $deliverability_tip = ' <strong>💡 Tip:</strong> For better deliverability, consider installing an SMTP plugin (WP Mail SMTP, Easy WP SMTP) to send emails through a proper mail server with SPF/DKIM authentication.';
            }
            echo '<div class="notice notice-success is-dismissible"><p>✅ Newsletter queued successfully for ' . $sent_count . ' subscriber(s). Emails will be sent in batches.' . $stats_link . $deliverability_tip . '</p></div>';
        } else {
            $deliverability_tip = '';
            // Check if using default wp_mail (may have deliverability issues)
            if (!has_filter('wp_mail')) {
                $deliverability_tip = ' <strong>💡 Tip:</strong> For better deliverability, consider installing an SMTP plugin (WP Mail SMTP, Easy WP SMTP) to send emails through a proper mail server with SPF/DKIM authentication.';
            }
            echo '<div class="notice notice-success is-dismissible"><p>✅ Newsletter sent successfully to ' . $sent_count . ' subscriber(s).' . $stats_link . $deliverability_tip . '</p></div>';
        }
    }
    
    if ($failed_count > 0) {
        $error_msg = '⚠️ Failed to send to ' . $failed_count . ' subscriber(s).';
        if ($failed_count === count($subscribers)) {
            $error_msg .= ' <strong>This usually means your server\'s mail function is not configured.</strong> Consider installing an SMTP plugin like "WP Mail SMTP" or "Easy WP SMTP" to send emails reliably.';
        } else {
            $error_msg .= ' Check error logs for details.';
        }
        echo '<div class="notice notice-warning is-dismissible"><p>' . $error_msg . '</p></div>';
    }
    
    if ($sent_count === 0 && $failed_count === 0) {
        echo '<div class="notice notice-info"><p>ℹ️ No emails were sent. Please check your configuration.</p></div>';
    }
}

/**
 * Handle email tracking (opens and clicks)
 */
function ccs_handle_email_tracking() {
    if (!isset($_GET['ccs_track']) || !isset($_GET['campaign']) || !isset($_GET['subscriber']) || !isset($_GET['token'])) {
        return;
    }
    
    global $wpdb;
    $track_type = sanitize_text_field($_GET['ccs_track']);
    $campaign_id = absint($_GET['campaign']);
    $subscriber_id = absint($_GET['subscriber']);
    
    // Verify token
    $subscriber = $wpdb->get_row($wpdb->prepare(
        "SELECT email FROM {$wpdb->prefix}ccs_newsletter_subscribers WHERE id = %d",
        $subscriber_id
    ));
    
    if (!$subscriber) {
        return;
    }
    
    $expected_token = '';
    if ($track_type === 'open') {
        $expected_token = wp_hash($campaign_id . $subscriber_id . $subscriber->email);
    } elseif ($track_type === 'click') {
        $url = isset($_GET['url']) ? urldecode($_GET['url']) : '';
        $expected_token = wp_hash($campaign_id . $subscriber_id . $url);
    }
    
    if (!hash_equals($expected_token, sanitize_text_field($_GET['token']))) {
        return;
    }
    
    // Collect minimal data for GDPR compliance
    // IP addresses are anonymised (last octet removed) for privacy
    $ip = ccs_get_client_ip();
    if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // Anonymise IPv4: remove last octet (e.g., 192.168.1.123 -> 192.168.1.0)
        $ip_parts = explode('.', $ip);
        if (count($ip_parts) === 4) {
            $ip_parts[3] = '0';
            $ip = implode('.', $ip_parts);
        }
    } elseif (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // Anonymise IPv6: remove last 64 bits (e.g., 2001:db8::1 -> 2001:db8::)
        $ip_parts = explode(':', $ip);
        if (count($ip_parts) > 4) {
            $ip = implode(':', array_slice($ip_parts, 0, 4)) . '::';
        }
    }
    
    // User agent is collected but truncated for privacy
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(substr($_SERVER['HTTP_USER_AGENT'], 0, 200)) : '';
    
    if ($track_type === 'open') {
        $opens_table = $wpdb->prefix . 'ccs_email_opens';
        // Check if already opened (unique opens)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $opens_table WHERE campaign_id = %d AND subscriber_id = %d",
            $campaign_id, $subscriber_id
        ));
        
        if (!$existing) {
            $wpdb->insert(
                $opens_table,
                [
                    'campaign_id' => $campaign_id,
                    'subscriber_id' => $subscriber_id,
                    'opened_at' => current_time('mysql'),
                    'ip_address' => $ip,
                    'user_agent' => $user_agent
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
            
            // Update campaign stats
            $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
            $wpdb->query($wpdb->prepare(
                "UPDATE $campaigns_table SET 
                    total_opened = total_opened + 1,
                    unique_opens = unique_opens + 1
                WHERE id = %d",
                $campaign_id
            ));
        } else {
            // Still count as total open (multiple opens)
            $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
            $wpdb->query($wpdb->prepare(
                "UPDATE $campaigns_table SET total_opened = total_opened + 1 WHERE id = %d",
                $campaign_id
            ));
        }
        
        // Return 1x1 transparent pixel
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
        
    } elseif ($track_type === 'click') {
        $url = isset($_GET['url']) ? urldecode($_GET['url']) : '';
        if (empty($url)) {
            return;
        }
        
        $clicks_table = $wpdb->prefix . 'ccs_email_clicks';
        
        // Check if already clicked (for unique clicks)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $clicks_table WHERE campaign_id = %d AND subscriber_id = %d AND url = %s",
            $campaign_id, $subscriber_id, $url
        ));
        
        $wpdb->insert(
            $clicks_table,
            [
                'campaign_id' => $campaign_id,
                'subscriber_id' => $subscriber_id,
                'url' => $url,
                'clicked_at' => current_time('mysql'),
                'ip_address' => $ip,
                'user_agent' => $user_agent
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );
        
        // Update campaign stats
        $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
        if (!$existing) {
            // Unique click
            $wpdb->query($wpdb->prepare(
                "UPDATE $campaigns_table SET 
                    total_clicked = total_clicked + 1,
                    unique_clicks = unique_clicks + 1
                WHERE id = %d",
                $campaign_id
            ));
        } else {
            // Total click (multiple clicks)
            $wpdb->query($wpdb->prepare(
                "UPDATE $campaigns_table SET total_clicked = total_clicked + 1 WHERE id = %d",
                $campaign_id
            ));
        }
        
        // Redirect to actual URL
        wp_redirect(esc_url_raw($url));
        exit;
    }
}
add_action('template_redirect', 'ccs_handle_email_tracking');

/**
 * Handle unsubscribe requests from email links
 */
function ccs_handle_unsubscribe() {
    if (!isset($_GET['ccs_unsubscribe']) || !isset($_GET['email']) || !isset($_GET['token'])) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    
    $email = sanitize_email($_GET['email']);
    $token = sanitize_text_field($_GET['token']);
    $ip = ccs_get_client_ip();
    
    // Rate limiting: Check for too many unsubscribe attempts from this IP
    $transient_key = 'ccs_unsubscribe_attempts_' . md5($ip);
    $attempts = get_transient($transient_key);
    if ($attempts === false) {
        $attempts = 0;
    }
    
    if ($attempts >= 10) {
        error_log('CTA Unsubscribe: Rate limit exceeded for IP ' . $ip);
        wp_die('Too many unsubscribe attempts. Please try again later.', 'Rate Limit Exceeded', ['response' => 429]);
    }
    
    set_transient($transient_key, $attempts + 1, 3600); // 1 hour
    
    // Find subscriber
    $subscriber = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE email = %s",
        $email
    ));
    
    if (!$subscriber) {
        error_log('CTA Unsubscribe: Subscriber not found');
        wp_die('Subscriber not found.', 'Unsubscribe', ['response' => 404]);
    }
    
    // Verify token - this is the critical security check
    $expected_token = wp_hash($subscriber->email . $subscriber->id);
    if (!hash_equals($expected_token, $token)) {
        error_log('CTA Unsubscribe: Invalid token attempt');
        wp_die('Invalid unsubscribe link. This link may have expired or been tampered with.', 'Invalid Link', ['response' => 403]);
    }
    
    // Additional security: Check if already unsubscribed (prevent replay attacks)
    if ($subscriber->status === 'unsubscribed') {
        // Already unsubscribed, just show confirmation
        wp_redirect(add_query_arg([
            'unsubscribed' => '1',
            'email' => urlencode($email)
        ], home_url('/unsubscribe/')));
        exit;
    }
    
    // Unsubscribe
    $result = $wpdb->update(
        $table_name,
        ['status' => 'unsubscribed', 'unsubscribed_at' => current_time('mysql')],
        ['id' => $subscriber->id],
        ['%s', '%s'],
        ['%d']
    );
    
    if ($result !== false) {
        error_log('CTA Unsubscribe: Successfully unsubscribed');
    } else {
        error_log('CTA Unsubscribe: Database update failed');
        wp_die('Unable to process unsubscribe request. Please contact us directly.', 'Error', ['response' => 500]);
    }
    
    // Redirect to unsubscribe confirmation page
    wp_redirect(add_query_arg([
        'unsubscribed' => '1',
        'email' => urlencode($email)
    ], home_url('/unsubscribe/')));
    exit;
}
add_action('template_redirect', 'ccs_handle_unsubscribe');

/**
 * Parse AI output that is expected to be JSON.
 * Handles common wrapping like code fences and extra leading/trailing text.
 */
function ccs_parse_ai_json_string($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') {
        return null;
    }

    // Strip common code fences.
    if (preg_match('/^```(?:json)?\s*(.*)\s*```$/is', $raw, $m)) {
        $raw = trim($m[1]);
    }

    // Fast path: valid JSON as-is.
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    // Try to extract the first JSON object substring.
    $start = strpos($raw, '{');
    $end = strrpos($raw, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $candidate = substr($raw, $start, $end - $start + 1);
        $decoded = json_decode($candidate, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

/**
 * AJAX: Generate newsletter email with AI
 */
function ccs_generate_newsletter_email_ajax() {
    check_ajax_referer('ccs_ai_email', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error('Invalid request', 403);
    }
    
    if (!ccs_newsletter_user_can_manage()) {
        wp_send_json_error('Permission denied');
    }
    
    $email_type = sanitize_text_field($_POST['email_type'] ?? 'custom');
    $custom_topic = sanitize_text_field($_POST['custom_topic'] ?? '');
    $course_id = absint($_POST['course_id'] ?? 0);
    $article_id = absint($_POST['article_id'] ?? 0);
    $subject_line = sanitize_text_field($_POST['subject_line'] ?? ''); // Auto-parse subject line if provided
    
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    
    // Build context based on email type
    $context = ccs_build_email_context($email_type, $course_id, $article_id, $custom_topic);
    
    // Auto-parse subject line for context if provided
    if (!empty($subject_line)) {
        $context['subject_line'] = $subject_line;
    }
    
    // Build the prompt
    $prompt = ccs_build_email_prompt($email_type, $context);
    
    // Call AI with email copywriting best practices from Email-copy.md
    $system_prompt = "You are an expert email copywriter for Continuity of Care Services, a care sector training provider in Kent, UK. Your goal is to generate a response: to move readers to take action.

AUDIENCE:
- Care sector professionals (care home managers, support workers, nurses, social workers)
- Based in the UK, primarily England
- Seeking CQC compliance, professional development, and better care delivery
- Busy professionals who value clarity and practical benefits
- Language: Conversational British English, professional but approachable

EMAIL STRUCTURE - THE 4 P's FORMULA (MUST USE):
1. PROMISE: Open with a compelling benefit or outcome in the first 1-2 sentences
   - Acknowledge subscriber personally with {first_name}
   - Lead with primary benefit or reason for writing
   - Create curiosity about content that follows
   Example: 'Hi {first_name}, Want to pass your next CQC inspection with confidence?'

2. PICTURE: Help readers visualize the outcome or solution
   - Paint the picture of success (better inspections, confident team, compliant care)
   - Use short paragraphs (2-3 sentences max) for scannability
   - Include bullet points for key benefits

3. PROOF: Provide evidence that your solution works
   - Social proof: CQC compliance, CPD-accredited, trusted by care providers
   - Use specific numbers when possible (e.g., 'Over 500 care professionals trained')
   - Include relevant examples or case studies

4. PUSH: Include a clear call-to-action
   - Single, focused action per email
   - Compelling reason to act now
   - Prominent button with action-oriented language ('Book Now', 'Read More', 'Reserve Your Spot')

EMAIL HIERARCHY (STRUCTURE):
- Opening Hook: Personal acknowledgment + primary benefit (1-2 sentences)
- Value Section: Deliver on subject line promise, actionable insights, short paragraphs
- Social Proof: When appropriate - testimonials, statistics, certifications
- Call-to-Action: Clear, prominent button with value proposition
- Closing: Personal sign-off, contact info, unsubscribe link

COPYWRITING PRINCIPLES:
1. LEAD WITH BENEFITS, NOT FEATURES: Transform features into benefits
   - Duration → 'Complete in just X hours, minimal time away from your team'
   - CQC compliance → 'Pass your next inspection with confidence'
   - Price → 'Invest in your team's development for less than...'

2. CLARITY OVER CLEVERNESS: Simple, conversational language. Immediately understandable.

3. PERSONAL & CONVERSATIONAL: Second person ('you', 'your'). Helpful colleague tone, not corporate.

4. PSYCHOLOGICAL TRIGGERS:
   - Curiosity: Subject lines that create intrigue
   - FOMO: Limited availability or time-sensitive offers
   - Social proof: Evidence of others' success
   - Authority: CQC compliance, expertise signals
   - Scarcity: Limited spaces, early booking benefits

5. SCANNABLE DESIGN:
   - Short paragraphs (2-3 sentences maximum)
   - Bullet points for lists and key benefits
   - Bold important phrases and keywords
   - Plenty of white space for visual breathing room

SUBJECT LINE REQUIREMENTS:
- 45-60 characters for optimal open rates
- Benefit-focused: Lead with what's in it for them
- Creates curiosity, urgency, or direct benefit
- Mobile-optimized: Consider how it appears on mobile
- Use specific numbers when possible
- Question-based, benefit-driven, or urgency/scarcity formats work best

TECHNICAL REQUIREMENTS:
- Write in British English (favour, colour, organisation)
- Keep emails concise (150-250 words for most, 200-300 for quarterly)
- Use {first_name} placeholder for personalization
- End with 'Best regards, The CCS Team'
- Include {unsubscribe_link} at bottom in small text
- Use HTML formatting: <p>, <strong>, <a>, <div> with inline styles
- Make buttons prominent with clear action text and gradient styling

UK SOURCE REQUIREMENTS - STRICTLY ENFORCED:
- When referencing external sources, ONLY use: NHS, CQC, Skills for Care, GOV.UK, NICE, HSE, SCIE
- DO NOT reference any other sources unless explicitly requested
- If mentioning regulations or standards, only reference UK sources
- Keep all references UK-focused and relevant to the care sector

OUTPUT FORMAT:
Return a JSON object with exactly these fields:
{
  \"subject\": \"Compelling subject line (45-60 chars, benefit-focused, creates curiosity or urgency)\",
  \"content\": \"Full HTML email content following 4 P's formula and all principles above\"
}";
    
    $result = ccs_ai_try_providers($preferred_provider, function($provider, $api_key) use ($system_prompt, $prompt) {
        if ($provider === 'groq') return ccs_call_groq_api_newsletter($api_key, $system_prompt, $prompt);
        if ($provider === 'anthropic') return ccs_call_anthropic_api_newsletter($api_key, $system_prompt, $prompt);
        return ccs_call_openai_api_newsletter($api_key, $system_prompt, $prompt);
    });
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    // Parse JSON response (robust against code fences / extra text)
    $parsed = ccs_parse_ai_json_string($result);
    if (!$parsed || !isset($parsed['subject']) || !isset($parsed['content'])) {
        wp_send_json_error('Failed to parse AI response. Please try again.');
    }
    
    wp_send_json_success([
        'subject' => $parsed['subject'],
        'content' => $parsed['content']
    ]);
}
add_action('wp_ajax_ccs_generate_newsletter_email', 'ccs_generate_newsletter_email_ajax');

/**
 * AJAX: Generate just the subject line
 */
function ccs_generate_email_subject_ajax() {
    check_ajax_referer('ccs_ai_email', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error('Invalid request', 403);
    }
    
    if (!ccs_newsletter_user_can_manage()) {
        wp_send_json_error('Permission denied');
    }
    
    $content = sanitize_textarea_field($_POST['content'] ?? '');
    
    if (empty($content)) {
        wp_send_json_error('No content provided');
    }
    
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    
    $prompt = "Based on this email content, write 1 compelling email subject line following email copywriting best practices.

SUBJECT LINE MASTERY PRINCIPLES:
1. CLARITY OVER CLEVERNESS: Ensure recipients understand the email's purpose immediately
2. BENEFIT-FOCUSED: Lead with what's in it for them (better CQC ratings, confidence, compliance)
3. APPROPRIATE URGENCY: Use time sensitivity when genuine (limited spaces, deadlines)
4. MOBILE OPTIMIZATION: 45-60 characters for best open rates on mobile devices
5. HIGH-PERFORMING TYPES:
   - Question-Based: 'Ready to pass your next CQC inspection?'
   - Benefit-Driven: 'Save 3 hours on compliance prep with this system'
   - Urgency/Scarcity: 'Only 5 spaces left: Book your training today'
   - Personal/Conversational: 'I made a mistake (and how you can avoid it)'
   - News/Update: 'New course: CQC compliance in 4 hours'

AVOID:
- Spam trigger words (FREE, CLICK HERE, URGENT in caps)
- Misleading subject lines
- Generic phrases
- Excessive capitalization

Email content:\n" . $content . "\n\nWrite only the subject line, nothing else. Make it compelling and benefit-focused.";
    
    $subject_system = "You are an expert email subject line writer specializing in care sector training. Your goal is to maximize open rates by creating compelling, benefit-focused subject lines that create curiosity or urgency. Write in British English for UK care sector professionals. Apply subject line mastery principles: clarity over cleverness, benefit-focused, appropriate urgency, mobile optimization (45-60 chars), and use high-performing formats (question-based, benefit-driven, urgency/scarcity, personal/conversational).";
    
    $result = ccs_ai_try_providers($preferred_provider, function($provider, $api_key) use ($subject_system, $prompt) {
        if ($provider === 'groq') return ccs_call_groq_api_newsletter($api_key, $subject_system, $prompt);
        if ($provider === 'anthropic') return ccs_call_anthropic_api_newsletter($api_key, $subject_system, $prompt);
        return ccs_call_openai_api_newsletter($api_key, $subject_system, $prompt);
    });
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success(['subject' => trim($result, '"\' ')]);
}
add_action('wp_ajax_ccs_generate_email_subject', 'ccs_generate_email_subject_ajax');

/**
 * AJAX handler to get populated template content
 */
function ccs_get_populated_template_ajax() {
    check_ajax_referer('ccs_newsletter_nonce', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    if (!ccs_newsletter_user_can_manage()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    $template_type = sanitize_text_field($_POST['template_type'] ?? '');
    
    if (empty($template_type)) {
        wp_send_json_error(['message' => 'Template type required']);
    }
    
    $template_data = ccs_get_populated_template($template_type);
    
    if ($template_data) {
        wp_send_json_success($template_data);
    } else {
        wp_send_json_error(['message' => 'Failed to load template']);
    }
}
add_action('wp_ajax_ccs_get_populated_template', 'ccs_get_populated_template_ajax');

/**
 * AJAX handler to get saved template content
 */
function ccs_get_saved_template_ajax() {
    check_ajax_referer('ccs_newsletter_nonce', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    if (!ccs_newsletter_user_can_manage()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    $template_id = absint($_POST['template_id'] ?? 0);
    
    if ($template_id <= 0) {
        wp_send_json_error(['message' => 'Invalid template ID']);
    }
    
    global $wpdb;
    $templates_table = $wpdb->prefix . 'ccs_email_templates';
    $template = $wpdb->get_row($wpdb->prepare("SELECT subject, content FROM $templates_table WHERE id = %d", $template_id));
    
    if ($template) {
        wp_send_json_success([
            'subject' => $template->subject,
            'content' => $template->content
        ]);
    } else {
        wp_send_json_error(['message' => 'Template not found']);
    }
}
add_action('wp_ajax_ccs_get_saved_template', 'ccs_get_saved_template_ajax');

/**
 * AJAX: Create newsletter tag
 */
function ccs_newsletter_create_tag_ajax() {
    check_ajax_referer('ccs_newsletter_nonce', 'nonce');

    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    if (!ccs_newsletter_user_can_manage()) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $color = sanitize_text_field(wp_unslash($_POST['color'] ?? ''));

    $result = ccs_newsletter_create_tag($name, $color);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    global $wpdb;
    $tags_table = $wpdb->prefix . 'ccs_newsletter_tags';
    $tag = $wpdb->get_row($wpdb->prepare("SELECT id, name, color FROM $tags_table WHERE id = %d", (int) $result));
    if (!$tag) {
        wp_send_json_error(['message' => 'Tag created but could not be loaded.']);
    }

    wp_send_json_success([
        'id' => (int) $tag->id,
        'name' => (string) $tag->name,
        'color' => (string) ($tag->color ?: ''),
    ]);
}
add_action('wp_ajax_ccs_newsletter_create_tag', 'ccs_newsletter_create_tag_ajax');

/**
 * AJAX: Update subscriber tags
 */
function ccs_newsletter_update_subscriber_tags_ajax() {
    check_ajax_referer('ccs_newsletter_nonce', 'nonce');

    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    if (!ccs_newsletter_user_can_manage()) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    $subscriber_id = absint($_POST['subscriber_id'] ?? 0);
    $tag_ids = isset($_POST['tag_ids']) ? (array) $_POST['tag_ids'] : [];
    $tag_ids = array_values(array_filter(array_map('absint', $tag_ids)));

    if ($subscriber_id <= 0) {
        wp_send_json_error(['message' => 'Invalid subscriber.']);
    }

    global $wpdb;
    $rel_table = $wpdb->prefix . 'ccs_newsletter_subscriber_tags';
    $tags_table = $wpdb->prefix . 'ccs_newsletter_tags';

    // Replace tags (simple + deterministic)
    $wpdb->delete($rel_table, ['subscriber_id' => $subscriber_id], ['%d']);
    if (!empty($tag_ids)) {
        ccs_newsletter_assign_tags([$subscriber_id], $tag_ids);
    }

    if (empty($tag_ids)) {
        wp_send_json_success(['tags' => []]);
    }

    $placeholders = implode(',', array_fill(0, count($tag_ids), '%d'));
    $tags = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, color FROM $tags_table WHERE id IN ($placeholders) ORDER BY name ASC",
        $tag_ids
    ));
    $out = array_map(static function($t) {
        return [
            'id' => (int) $t->id,
            'name' => (string) $t->name,
            'color' => (string) ($t->color ?: ''),
        ];
    }, $tags ?: []);

    wp_send_json_success(['tags' => $out]);
}
add_action('wp_ajax_ccs_newsletter_update_subscriber_tags', 'ccs_newsletter_update_subscriber_tags_ajax');

/**
 * AJAX: Get recipients for tag ids (active subscribers only)
 */
function ccs_newsletter_get_recipients_for_tags_ajax() {
    check_ajax_referer('ccs_newsletter_nonce', 'nonce');

    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    if (!ccs_newsletter_user_can_manage()) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    $tag_ids = isset($_POST['tag_ids']) ? (array) $_POST['tag_ids'] : [];
    $tag_ids = array_values(array_filter(array_map('absint', $tag_ids)));
    if (empty($tag_ids)) {
        wp_send_json_success(['recipients' => []]);
    }

    global $wpdb;
    $subscribers_table = $wpdb->prefix . 'ccs_newsletter_subscribers';
    $rel_table = $wpdb->prefix . 'ccs_newsletter_subscriber_tags';

    $placeholders = implode(',', array_fill(0, count($tag_ids), '%d'));
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT s.id, s.email, s.first_name, s.last_name
         FROM $subscribers_table s
         INNER JOIN $rel_table st ON st.subscriber_id = s.id
         WHERE s.status = 'active' AND st.tag_id IN ($placeholders)
         ORDER BY s.email ASC",
        $tag_ids
    ));

    $recipients = array_map(static function($r) {
        $name = trim((string) ($r->first_name ?? '') . ' ' . (string) ($r->last_name ?? ''));
        return [
            'id' => (int) $r->id,
            'email' => (string) $r->email,
            'name' => $name ?: '',
        ];
    }, $rows ?: []);

    wp_send_json_success(['recipients' => $recipients]);
}
add_action('wp_ajax_ccs_newsletter_get_recipients_for_tags', 'ccs_newsletter_get_recipients_for_tags_ajax');

/**
 * Get populated template content with real data
 */
function ccs_get_populated_template($template_type) {
    $base_templates = [
        'new_course' => [
            'subject' => 'New Course: [Course Name] - Pass Your Next CQC Inspection',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Your next CQC inspection is coming. Will your team be ready?</strong></p>
<p>We\'ve just launched a new course that gives care professionals exactly what they need to pass inspections with confidence, not anxiety.</p>
<div style="background: #f9f9f9; border-left: 4px solid #3ba59b; padding: 20px; margin: 24px 0; border-radius: 4px;">
<p style="margin: 0 0 12px 0;"><strong style="font-size: 18px; color: #2b1b0e;">[Course details will be inserted here]</strong></p>
<p style="margin: 0 0 8px 0; color: #646970; line-height: 1.6;">This course will help you:</p>
<ul style="margin: 8px 0 0 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>Meet CQC requirements</strong> with confidence, with no more last-minute panic</li>
<li><strong>Build team competence</strong> so everyone knows exactly what inspectors look for</li>
<li><strong>Deliver better care outcomes</strong> that show in your inspection reports</li>
</ul>
</div>
<p><strong>Imagine walking into your next inspection knowing your team is prepared.</strong> No scrambling. No stress. Just confidence.</p>
<p>This CPD-accredited course is trusted by care providers across Kent and the UK. Many book 2-3 months before their inspection dates to ensure their team is ready.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="#" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Reserve Your Team\'s Place →</a></p>
<p><strong>Spaces fill up quickly</strong>, especially in the months leading up to inspection periods. Secure your spot now to avoid disappointment.</p>
<p>Questions? Just reply to this email. We\'re here to help.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Early booking means you get the dates that work for your team\'s schedule. Don\'t wait until the last minute.</p>'
        ],
        'new_article' => [
            'subject' => 'How to [Solve Problem]: 5-Minute Guide for Care Professionals',
            'content' => '<p>Hi {first_name},</p>
<p><strong>[Common challenge] is costing you time and stress. Here\'s how to fix it.</strong></p>
<p>We\'ve just published a practical guide that shows you exactly how to solve this problem, with steps you can implement this week.</p>
<div style="background: #f9f9f9; border-left: 4px solid #2271b1; padding: 20px; margin: 24px 0; border-radius: 4px;">
<p style="margin: 0 0 12px 0;"><strong style="font-size: 18px; color: #2b1b0e;">[Article title and summary will be inserted here]</strong></p>
<p style="margin: 0; color: #646970; line-height: 1.6;">You\'ll discover:</p>
<ul style="margin: 8px 0 0 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li>Practical strategies you can use immediately</li>
<li>Common mistakes to avoid</li>
<li>How this improves your CQC compliance</li>
</ul>
</div>
<p><strong>This isn\'t theory. It\'s what works.</strong> Based on latest CQC guidance and used by care providers who consistently pass their inspections.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="#" style="display: inline-block; background: linear-gradient(135deg, #2271b1 0%, #1e5a8f 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3);">Read the Full Guide →</a></p>
<p><strong>This 5-minute read could save you hours of work</strong> and help you avoid common compliance pitfalls.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Bookmark this for your next inspection prep. It\'s the kind of resource that makes the difference between a good inspection and a great one.</p>'
        ],
        'upcoming_courses' => [
            'subject' => 'Limited Spaces: Upcoming Training Courses This Month',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Your next CQC inspection is coming. Is your team ready?</strong></p>
<p>Here are our upcoming courses with spaces still available:</p>
<div style="background: #f9f9f9; padding: 20px; margin: 24px 0; border-radius: 4px; border: 1px solid #e0e0e0;">
{upcoming_courses_list}
</div>
<p><strong>Why book now instead of waiting?</strong></p>
<ul style="margin: 16px 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>You choose the dates</strong> that work for your team, not whatever\'s left</li>
<li><strong>Your staff are prepared</strong> well before inspection dates, reducing stress</li>
<li><strong>You avoid last-minute panic</strong> when courses are fully booked</li>
</ul>
<p>Many care providers book 2-3 months in advance. The courses that fill up fastest? The ones closest to common inspection periods.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/upcoming-courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">View All Upcoming Courses →</a></p>
<p>Secure your team\'s training dates now. Spaces are limited.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Need help choosing the right course for your team? Reply to this email and we\'ll recommend the best options for your care setting.</p>'
        ],
        'quarterly' => [
            'subject' => 'Your Quarterly Update: What We\'ve Accomplished Together',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Thank you for being part of our community.</strong> Your commitment to professional development helps deliver better care across Kent and the UK.</p>
<h3 style="color: #2b1b0e; font-size: 20px; margin: 32px 0 16px 0;">What We\'ve Accomplished This Quarter</h3>
<div style="background: #f9f9f9; padding: 20px; margin: 24px 0; border-radius: 4px;">
{quarterly_summary}
</div>
<p>Together, we\'re raising standards in care. <strong>Your dedication to continuous learning makes a real difference for your team, your residents, and the sector.</strong></p>
<p><strong>What\'s coming next quarter:</strong></p>
<ul style="margin: 16px 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>New courses</strong> designed around latest CQC guidance</li>
<li><strong>Practical resources</strong> to help you pass inspections with confidence</li>
<li><strong>Expert insights</strong> from industry leaders</li>
</ul>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Browse Our Courses →</a></p>
<p>We look forward to supporting your professional development in the months ahead.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Is there a specific topic or challenge you\'d like us to cover? Reply to this email. We read every response and use your feedback to shape what we create next.</p>'
        ],
        'birthday' => [
            'subject' => '🎂 Happy Birthday, {first_name}!',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Happy Birthday! 🎉</strong></p>
<p>We wanted to take a moment to celebrate you today. Your dedication to professional development and continuous learning in the care sector makes a real difference—not just for your career, but for the people you care for every day.</p>
<div style="background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%); border-left: 4px solid #3ba59b; padding: 24px; margin: 24px 0; border-radius: 4px; text-align: center;">
<p style="margin: 0; font-size: 48px; line-height: 1;">🎂</p>
<p style="margin: 16px 0 0 0; font-size: 20px; color: #2b1b0e; font-weight: 600;">Wishing you a wonderful year ahead!</p>
</div>
<p>As a special birthday gift, we\'d like to offer you <strong>20% off any course</strong> booked in the next 30 days. Use code <strong>BIRTHDAY20</strong> at checkout.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Browse Our Courses →</a></p>
<p>Thank you for being part of our community. Here\'s to another year of growth, learning, and making a positive impact in care.</p>
<p>Best wishes,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> This offer is valid for 30 days from your birthday. Don\'t miss out!</p>'
        ],
        'welcome' => [
            'subject' => 'Welcome to Continuity of Care Services!',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Welcome to Continuity of Care Services!</strong></p>
<p>Thank you for subscribing to our newsletter. We\'re thrilled to have you join our community of care professionals who are committed to continuous learning and excellence in care delivery.</p>
<div style="background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%); border-left: 4px solid #3ba59b; padding: 24px; margin: 24px 0; border-radius: 4px;">
<p style="margin: 0 0 16px 0; font-weight: 600; color: #2b1b0e; font-size: 18px;">What you can expect from us:</p>
<ul style="margin: 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>Latest CQC guidance</strong> and compliance updates</li>
<li><strong>Practical training courses</strong> to help you and your team excel</li>
<li><strong>Expert insights</strong> from industry leaders</li>
<li><strong>Resources and tools</strong> to make your job easier</li>
</ul>
</div>
<p>We know how busy you are, so we promise to only send you content that\'s genuinely valuable—no fluff, just practical insights that help you deliver better care and pass inspections with confidence.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Browse Our Courses →</a></p>
<p>If you have any questions or topics you\'d like us to cover, just reply to this email. We read every message and use your feedback to shape what we create.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Follow us on social media for daily tips and updates. We\'re here to support your professional development journey.</p>'
        ],
        'special_offer' => [
            'subject' => '🎁 Special Offer: [Discount]% Off Training Courses',
            'content' => '<p>Hi {first_name},</p>
<p><strong>We have a special offer just for you!</strong></p>
<p>As a valued member of our community, we\'re offering you <strong>[Discount]% off</strong> all training courses for a limited time.</p>
<div style="background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%); border: 2px solid #ffc107; padding: 24px; margin: 24px 0; border-radius: 4px; text-align: center;">
<p style="margin: 0 0 8px 0; font-size: 32px; font-weight: 700; color: #856404;">[Discount]% OFF</p>
<p style="margin: 0; font-size: 16px; color: #856404; font-weight: 600;">Use code: <strong style="font-size: 18px; letter-spacing: 1px;">[DISCOUNT_CODE]</strong></p>
<p style="margin: 16px 0 0 0; font-size: 14px; color: #856404;">Valid until [End Date]</p>
</div>
<p><strong>Why book now?</strong></p>
<ul style="margin: 16px 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>Save money</strong> on essential CQC compliance training</li>
<li><strong>Secure your dates</strong> before spaces fill up</li>
<li><strong>Invest in your team</strong> at a reduced rate</li>
<li><strong>Limited time offer</strong>—don\'t miss out</li>
</ul>
<p>This offer applies to all our training courses, including our most popular CQC inspection preparation courses. Perfect timing to get your team ready for their next inspection.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Browse Courses & Claim Offer →</a></p>
<p><strong>How to use your discount:</strong></p>
<ol style="margin: 16px 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li>Browse our courses and select the training you need</li>
<li>Enter code <strong>[DISCOUNT_CODE]</strong> at checkout</li>
<li>Your discount will be applied automatically</li>
</ol>
<p>Questions? Just reply to this email or call us. We\'re here to help.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> This offer is exclusive to our newsletter subscribers. Thank you for being part of our community!</p>'
        ]
    ];
    
    if (!isset($base_templates[$template_type])) {
        return null;
    }
    
    $template = $base_templates[$template_type];
    
    // Populate with real data based on template type
    switch ($template_type) {
        case 'upcoming_courses':
            $courses_html = ccs_build_upcoming_courses_html();
            $template['content'] = str_replace('{upcoming_courses_list}', $courses_html, $template['content']);
            break;
            
        case 'quarterly':
            $summary_html = ccs_build_quarterly_summary_html();
            $template['content'] = str_replace('{quarterly_summary}', $summary_html, $template['content']);
            break;
    }
    
    return $template;
}

/**
 * Build HTML for upcoming courses list (next 30 days)
 */
function ccs_build_upcoming_courses_html() {
    $thirty_days_from_now = date('Y-m-d', strtotime('+30 days'));
    $today = date('Y-m-d');
    
    $upcoming = get_posts([
        'post_type' => 'course_event',
        'posts_per_page' => 10,
        'meta_key' => 'event_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => [
            [
                'key' => 'event_date',
                'value' => [$today, $thirty_days_from_now],
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            ]
        ]
    ]);
    
    if (empty($upcoming)) {
        return '<p style="margin: 0; color: #646970;"><em>No upcoming courses scheduled in the next 30 days. Check back soon!</em></p>';
    }
    
    $html = '<ul style="margin: 0; padding: 0; list-style: none;">';
    
    foreach ($upcoming as $event) {
        $date = get_field('event_date', $event->ID) ?: get_post_meta($event->ID, 'event_date', true);
        $price = get_field('event_price', $event->ID) ?: get_post_meta($event->ID, 'event_price', true);
        $spaces = get_field('spaces_available', $event->ID) ?: get_post_meta($event->ID, 'spaces_available', true);
        $url = get_permalink($event->ID);
        
        $date_formatted = $date ? date('j F Y', strtotime($date)) : 'TBC';
        $price_formatted = $price ? '£' . number_format($price, 2) : '';
        $spaces_text = $spaces ? $spaces . ' spaces left' : '';
        
        $html .= '<li style="margin: 0 0 16px 0; padding: 0 0 16px 0; border-bottom: 1px solid #e0e0e0;">';
        $html .= '<p style="margin: 0 0 4px 0;"><strong style="font-size: 16px; color: #2b1b0e;"><a href="' . esc_url($url) . '" style="color: #3ba59b; text-decoration: none;">' . esc_html($event->post_title) . '</a></strong></p>';
        $html .= '<p style="margin: 0; color: #646970; font-size: 14px; line-height: 1.6;">';
        $html .= '<span style="margin-right: 16px;">📅 ' . esc_html($date_formatted) . '</span>';
        if ($price_formatted) {
            $html .= '<span style="margin-right: 16px;">💰 ' . esc_html($price_formatted) . '</span>';
        }
        if ($spaces_text) {
            $html .= '<span>👥 ' . esc_html($spaces_text) . '</span>';
        }
        $html .= '</p>';
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    
    return $html;
}

/**
 * Build HTML for quarterly summary
 */
function ccs_build_quarterly_summary_html() {
    $three_months_ago = date('Y-m-d', strtotime('-3 months'));
    
    // Get articles from last quarter
    $recent_articles = get_posts([
        'post_type' => 'post',
        'posts_per_page' => 5,
        'date_query' => [['after' => $three_months_ago]],
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    
    // Get new courses from last quarter
    $new_courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => 5,
        'date_query' => [['after' => $three_months_ago]],
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    
    $html = '<div style="margin: 0;">';
    
    if (!empty($new_courses)) {
        $html .= '<p style="margin: 0 0 16px 0; font-weight: 600; color: #2b1b0e;">📚 New Courses This Quarter:</p>';
        $html .= '<ul style="margin: 0 0 24px 0; padding-left: 20px; color: #646970; line-height: 1.8;">';
        foreach ($new_courses as $course) {
            $html .= '<li><a href="' . esc_url(get_permalink($course->ID)) . '" style="color: #3ba59b; text-decoration: none;">' . esc_html($course->post_title) . '</a></li>';
        }
        $html .= '</ul>';
    }
    
    if (!empty($recent_articles)) {
        $html .= '<p style="margin: 0 0 16px 0; font-weight: 600; color: #2b1b0e;">📰 Articles Published:</p>';
        $html .= '<ul style="margin: 0; padding-left: 20px; color: #646970; line-height: 1.8;">';
        foreach ($recent_articles as $article) {
            $html .= '<li><a href="' . esc_url(get_permalink($article->ID)) . '" style="color: #3ba59b; text-decoration: none;">' . esc_html($article->post_title) . '</a></li>';
        }
        $html .= '</ul>';
    }
    
    if (empty($new_courses) && empty($recent_articles)) {
        $html .= '<p style="margin: 0; color: #646970;"><em>This quarter has been focused on preparing new resources for you. Stay tuned for exciting updates!</em></p>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Build context for email generation
 */
function ccs_build_email_context($type, $course_id, $article_id, $custom_topic) {
    $context = [];
    
    switch ($type) {
        case 'new_course':
            if ($course_id) {
                $course = get_post($course_id);
                if ($course) {
                    $context['course_name'] = $course->post_title;
                    $context['course_url'] = get_permalink($course_id);
                    $context['course_excerpt'] = wp_trim_words($course->post_content, 50);
                    
                    if (function_exists('get_field')) {
                        $context['duration'] = get_field('course_duration', $course_id);
                        $context['price'] = get_field('course_price', $course_id);
                        $context['level'] = get_field('course_level', $course_id);
                        $context['category'] = '';
                        $categories = wp_get_post_terms($course_id, 'course_category');
                        if ($categories && !is_wp_error($categories)) {
                            $context['category'] = $categories[0]->name;
                        }
                    }
                }
            } else {
                // If no course selected, use the most recent course
                $recent_course = get_posts(['post_type' => 'course', 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC']);
                if (!empty($recent_course)) {
                    $course = $recent_course[0];
                    $context['course_name'] = $course->post_title;
                    $context['course_url'] = get_permalink($course->ID);
                    $context['course_excerpt'] = wp_trim_words($course->post_content, 50);
                    
                    if (function_exists('get_field')) {
                        $context['duration'] = get_field('course_duration', $course->ID);
                        $context['price'] = get_field('course_price', $course->ID);
                        $context['level'] = get_field('course_level', $course->ID);
                        $context['category'] = '';
                        $categories = wp_get_post_terms($course->ID, 'course_category');
                        if ($categories && !is_wp_error($categories)) {
                            $context['category'] = $categories[0]->name;
                        }
                    }
                }
            }
            // Get list of recent courses for selection
            $recent_courses = get_posts(['post_type' => 'course', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);
            $context['recent_courses'] = array_map(function($c) {
                return $c->post_title;
            }, $recent_courses);
            break;
            
        case 'new_article':
            if ($article_id) {
                $article = get_post($article_id);
                if ($article) {
                    $context['article_title'] = $article->post_title;
                    $context['article_url'] = get_permalink($article_id);
                    $context['article_excerpt'] = wp_trim_words($article->post_content, 50);
                    
                    // Get article category
                    $categories = get_the_category($article_id);
                    if ($categories && !is_wp_error($categories)) {
                        $context['article_category'] = $categories[0]->name;
                    }
                    
                    // Get read time if available
                    if (function_exists('get_field')) {
                        $read_time = get_field('read_time', $article_id);
                        if ($read_time) {
                            $context['read_time'] = $read_time;
                        }
                    }
                }
            } else {
                // If no article selected, use the most recent article
                $recent_article = get_posts(['post_type' => 'post', 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC']);
                if (!empty($recent_article)) {
                    $article = $recent_article[0];
                    $context['article_title'] = $article->post_title;
                    $context['article_url'] = get_permalink($article->ID);
                    $context['article_excerpt'] = wp_trim_words($article->post_content, 50);
                    
                    // Get article category
                    $categories = get_the_category($article->ID);
                    if ($categories && !is_wp_error($categories)) {
                        $context['article_category'] = $categories[0]->name;
                    }
                    
                    // Get read time if available
                    if (function_exists('get_field')) {
                        $read_time = get_field('read_time', $article->ID);
                        if ($read_time) {
                            $context['read_time'] = $read_time;
                        }
                    }
                }
            }
            // Get recent articles
            $recent_articles = get_posts(['post_type' => 'post', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);
            $context['recent_articles'] = array_map(function($a) {
                return ['title' => $a->post_title, 'excerpt' => wp_trim_words($a->post_content, 20)];
            }, $recent_articles);
            break;
            
        case 'quarterly':
            // Get content from last 3 months
            $three_months_ago = date('Y-m-d', strtotime('-3 months'));
            
            $recent_articles = get_posts([
                'post_type' => 'post',
                'posts_per_page' => 10,
                'date_query' => [['after' => $three_months_ago]]
            ]);
            $context['articles_this_quarter'] = array_map(function($a) {
                return ['title' => $a->post_title, 'url' => get_permalink($a->ID)];
            }, $recent_articles);
            
            $new_courses = get_posts([
                'post_type' => 'course',
                'posts_per_page' => 10,
                'date_query' => [['after' => $three_months_ago]]
            ]);
            $context['new_courses_this_quarter'] = array_map(function($c) {
                return $c->post_title;
            }, $new_courses);
            
            // Upcoming events next quarter
            $upcoming = get_posts([
                'post_type' => 'course_event',
                'posts_per_page' => 10,
                'meta_key' => 'event_date',
                'orderby' => 'meta_value',
                'order' => 'ASC',
                'meta_query' => [
                    ['key' => 'event_date', 'value' => date('Y-m-d'), 'compare' => '>=']
                ]
            ]);
            $context['upcoming_next_quarter'] = array_map(function($e) {
                $date = get_field('event_date', $e->ID) ?: get_post_meta($e->ID, 'event_date', true);
                return ['title' => $e->post_title, 'date' => $date];
            }, $upcoming);
            
            $context['current_quarter'] = 'Q' . ceil(date('n') / 3) . ' ' . date('Y');
            $context['next_quarter'] = 'Q' . (ceil(date('n') / 3) % 4 + 1) . ' ' . (ceil(date('n') / 3) == 4 ? date('Y') + 1 : date('Y'));
            break;
            
        case 'upcoming':
            // Get upcoming events
            $upcoming = get_posts([
                'post_type' => 'course_event',
                'posts_per_page' => 15,
                'meta_key' => 'event_date',
                'orderby' => 'meta_value',
                'order' => 'ASC',
                'meta_query' => [
                    ['key' => 'event_date', 'value' => date('Y-m-d'), 'compare' => '>=']
                ]
            ]);
            
            $context['upcoming_courses'] = [];
            foreach ($upcoming as $event) {
                $date = get_field('event_date', $event->ID) ?: get_post_meta($event->ID, 'event_date', true);
                $price = get_field('event_price', $event->ID) ?: get_post_meta($event->ID, 'event_price', true);
                $spaces = get_field('spaces_available', $event->ID) ?: get_post_meta($event->ID, 'spaces_available', true);
                
                $context['upcoming_courses'][] = [
                    'title' => $event->post_title,
                    'date' => $date ? date('j F Y', strtotime($date)) : 'TBC',
                    'price' => $price ? '£' . $price : '',
                    'spaces' => $spaces,
                    'url' => get_permalink($event->ID)
                ];
            }
            break;
            
        case 'custom':
            $context['topic'] = $custom_topic;
            break;
    }
    
    // Always include site info
    $context['site_url'] = home_url();
    $context['courses_url'] = home_url('/courses/');
    $context['upcoming_url'] = home_url('/upcoming-courses/');
    $context['contact_url'] = home_url('/contact/');
    
    return $context;
}

/**
 * Build the AI prompt for email generation
 */
function ccs_build_email_prompt($type, $context) {
    // Reference guides: Email Marketing Copywriting Guide & Copywriting Fundamentals
    $subject_context = '';
    if (!empty($context['subject_line'])) {
        $subject_context = "\n\nSUBJECT LINE PROVIDED: " . $context['subject_line'] . "\nUse this subject line as context for the email content. The email should align with and deliver on the promise made in the subject line.";
    }
    
    $prompts = [
        'new_course' => "You are an expert email copywriter specializing in care sector training. Follow the principles from 'Email Marketing Copywriting: The Complete Guide' and 'Copywriting Fundamentals' to write a newsletter email announcing a new course." . $subject_context . "

CORE COPYWRITING PRINCIPLES TO APPLY:
- Lead with benefits, not features (from Copywriting Fundamentals)
- Clarity over cleverness (from Copywriting Fundamentals)
- Use personal and conversational language (from Copywriting Fundamentals)
- Structure strategically using the 4 P's Email Formula (from Email Marketing Copywriting Guide)
- Create compelling subject lines (from Email Marketing Copywriting Guide)
- Use social proof and credibility (from Copywriting Fundamentals)

Write a newsletter email announcing a new course using the 4 P's Email Formula and email copywriting best practices.

COURSE INFORMATION:
Course Name: " . ($context['course_name'] ?? 'a new training course') . "
URL: " . ($context['course_url'] ?? '{course_url}') . "
Details: " . ($context['course_excerpt'] ?? '') . "
Duration: " . ($context['duration'] ?? '') . "
Price: " . ($context['price'] ? '£' . $context['price'] : '') . "
Level: " . ($context['level'] ?? '') . "
Category: " . ($context['category'] ?? '') . "

EMAIL STRUCTURE - USE THE 4 P's FORMULA:

1. PROMISE (Opening Hook - First 1-2 sentences):
   - Acknowledge subscriber personally: 'Hi {first_name},'
   - Lead with primary benefit: What they gain (better CQC ratings, confidence, compliance)
   - Create curiosity: 'Want to pass your next CQC inspection with confidence?'
   - Address their pain point: Inspection anxiety, compliance challenges

2. PICTURE (Value Section - Main body):
   - Paint the picture of success: Better inspections, confident team, compliant care
   - Show how this course solves their specific problem
   - Use short paragraphs (2-3 sentences max) for scannability
   - Include bullet points for key benefits
   - Transform features into benefits:
     * Duration → 'Complete in just X hours, minimal time away from your team'
     * CQC compliance → 'Pass your next inspection with confidence'
     * Price → 'Invest in your team's development for less than...'

3. PROOF (Social Proof - When appropriate):
   - Mention if it's popular, CPD-accredited, or trusted by other care providers
   - Use specific numbers: 'Over 500 care professionals trained'
   - Include relevant examples or case studies
   - Authority signals: CQC compliance, industry recognition

4. PUSH (Call-to-Action):
   - Single, focused action: 'Book Your Place Now' or 'Reserve Your Spot'
   - Compelling reason to act now: Limited spaces, early booking benefits
   - Prominent button with action-oriented language
   - Create urgency: Limited spaces, upcoming inspection dates

ADDITIONAL REQUIREMENTS:
- Conversational tone: Write like a helpful colleague, not corporate marketing
- Scannable design: Short paragraphs, bullet points, bold important phrases
- Mobile-friendly: Concise, clear hierarchy
- Length: 150-200 words
- Use {first_name} for personalization throughout",

        'new_article' => "You are an expert email copywriter specializing in care sector training. Follow the principles from 'Email Marketing Copywriting: The Complete Guide' and 'Copywriting Fundamentals' to write a newsletter email promoting a new blog article.

CORE COPYWRITING PRINCIPLES TO APPLY:
- Lead with benefits, not features (from Copywriting Fundamentals)
- Clarity over cleverness (from Copywriting Fundamentals)
- Use personal and conversational language (from Copywriting Fundamentals)
- Structure strategically using the 4 P's Email Formula (from Email Marketing Copywriting Guide)
- Create compelling subject lines (from Email Marketing Copywriting Guide)
- Use social proof and credibility (from Copywriting Fundamentals)

Write a newsletter email promoting a new blog article using the 4 P's Email Formula and email copywriting best practices.

ARTICLE INFORMATION:
Title: " . ($context['article_title'] ?? 'a new article') . "
Category: " . ($context['article_category'] ?? '') . "
Read Time: " . ($context['read_time'] ?? '') . "
URL: " . ($context['article_url'] ?? '{article_url}') . "
Summary: " . ($context['article_excerpt'] ?? '') . "

EMAIL STRUCTURE - USE THE 4 P's FORMULA:

1. PROMISE (Opening Hook - First 1-2 sentences):
   - Acknowledge subscriber: 'Hi {first_name},'
   - Lead with benefit: What they'll learn or gain (solve a problem, save time, improve care)
   - Create curiosity: 'Struggling with [common challenge] in your care setting?'
   - Address their pain point: Connect to common challenges care professionals face

2. PICTURE (Value Section - Main body):
   - Tease the key insight without giving everything away
   - Paint the picture: How this solves their problem
   - Use short paragraphs (2-3 sentences max) for scannability
   - Include bullet points if listing key takeaways

3. PROOF (Social Proof - When appropriate):
   - Mention if it's based on CQC guidance, industry best practice, or expert insights
   - Use authority signals: 'Based on latest CQC guidance' or 'Industry best practice'
   - Include relevant examples or case studies

4. PUSH (Call-to-Action):
   - Single, focused action: 'Read the Full Article' or 'Discover How to...'
   - Compelling reason: 'This 5-minute read could save you hours of work'
   - Prominent button with action-oriented language

ADDITIONAL REQUIREMENTS:
- Clear value proposition: Why should they read this now? What problem does it solve?
- Conversational tone: Write like sharing a helpful resource with a colleague
- Scannable design: Short paragraphs, clear hierarchy
- Length: 100-150 words
- Use {first_name} for personalization",

        'quarterly' => "You are an expert email copywriter specializing in care sector training. Follow the principles from 'Email Marketing Copywriting: The Complete Guide' and 'Copywriting Fundamentals' to write a quarterly newsletter update email.

CORE COPYWRITING PRINCIPLES TO APPLY:
- Lead with benefits, not features (from Copywriting Fundamentals)
- Clarity over cleverness (from Copywriting Fundamentals)
- Use personal and conversational language (from Copywriting Fundamentals)
- Structure strategically using the 4 P's Email Formula (from Email Marketing Copywriting Guide)
- Use reciprocity principle (from Email Marketing Copywriting Guide)
- Create scannable content with short paragraphs (from Email Marketing Copywriting Guide)

Write a quarterly newsletter update email using the 4 P's Email Formula and email copywriting best practices.

QUARTERLY INFORMATION:
Current quarter: " . ($context['current_quarter'] ?? 'Q4 2024') . "

Articles published this quarter:
" . (isset($context['articles_this_quarter']) ? implode("\n", array_map(function($a) { return "- " . $a['title']; }, $context['articles_this_quarter'])) : 'None') . "

New courses added:
" . (isset($context['new_courses_this_quarter']) ? implode("\n", array_map(function($c) { return "- " . $c; }, $context['new_courses_this_quarter'])) : 'None') . "

Coming up next quarter:
" . (isset($context['upcoming_next_quarter']) ? implode("\n", array_map(function($e) { return "- " . $e['title'] . " (" . $e['date'] . ")"; }, array_slice($context['upcoming_next_quarter'], 0, 5))) : 'TBC') . "

EMAIL STRUCTURE - USE THE 4 P's FORMULA:

1. PROMISE (Opening Hook - First 1-2 sentences):
   - Acknowledge subscriber: 'Hi {first_name},'
   - Lead with value: Acknowledge their commitment to professional development
   - Use reciprocity: 'Thank you for being part of our community'
   - Create connection: Make them feel valued and part of something meaningful

2. PICTURE (Value Section - Main body):
   - Highlight TOP 2-3 ITEMS only: Don't list everything - focus on most valuable/beneficial content
   - For each item, explain what they gain (better compliance, time-saving tips, career advancement)
   - Paint the picture: 'Together, we're raising standards in care across the UK'
   - Use short paragraphs (2-3 sentences max) for scannability
   - Include subheadings to break up sections
   - Preview what's coming next quarter with benefit-focused language

3. PROOF (Social Proof - When appropriate):
   - Mention impact: 'Over 500 care professionals trained this quarter'
   - Use specific numbers and statistics
   - Include relevant examples or case studies
   - Authority signals: Industry recognition, CQC compliance

4. PUSH (Call-to-Action):
   - Multiple CTAs: Offer clear next steps (browse courses, read articles, book training)
   - Compelling reason: 'Your dedication to continuous learning makes a real difference'
   - Prominent buttons with action-oriented language
   - Create anticipation: 'We look forward to supporting your professional development'

ADDITIONAL REQUIREMENTS:
- Warm, personal tone: Like a quarterly catch-up with a trusted advisor
- Scannable design: Short paragraphs, subheadings, bullet points, clear hierarchy
- Use reciprocity: Thank them, acknowledge their value
- Length: 200-300 words
- Use {first_name} for personalization",

        'upcoming' => "You are an expert email copywriter specializing in care sector training. Follow the principles from 'Email Marketing Copywriting: The Complete Guide' and 'Copywriting Fundamentals' to write a newsletter email about upcoming training courses.

CORE COPYWRITING PRINCIPLES TO APPLY:
- Lead with benefits, not features (from Copywriting Fundamentals)
- Clarity over cleverness (from Copywriting Fundamentals)
- Use personal and conversational language (from Copywriting Fundamentals)
- Structure strategically using the 4 P's Email Formula (from Email Marketing Copywriting Guide)
- Create urgency when appropriate (from Email Marketing Copywriting Guide)
- Use scarcity principle (from Copywriting Fundamentals)

Write a newsletter email about upcoming training courses using the 4 P's Email Formula and email copywriting best practices.

UPCOMING COURSES:
" . (isset($context['upcoming_courses']) ? implode("\n", array_map(function($c) { 
    return "- " . $c['title'] . " | " . $c['date'] . " | " . $c['price'] . " | " . ($c['spaces'] ? $c['spaces'] . ' spaces left' : '');
}, array_slice($context['upcoming_courses'], 0, 8))) : 'None scheduled') . "

Booking URL: " . ($context['upcoming_url'] ?? home_url('/upcoming-courses/')) . "

EMAIL STRUCTURE - USE THE 4 P's FORMULA:

1. PROMISE (Opening Hook - First 1-2 sentences):
   - Acknowledge subscriber: 'Hi {first_name},'
   - Lead with urgency/benefit: 'Your next CQC inspection is coming. Are you ready?'
   - Create curiosity: Scarcity (limited spaces) or benefit (advance your career, pass inspections)
   - Address pain point: Inspection preparation, compliance deadlines

2. PICTURE (Value Section - Main body):
   - List courses with benefit-focused descriptions (what they gain, not just course name)
   - Paint the picture: Better inspections, confident team, compliant care
   - Use short paragraphs (2-3 sentences max) for scannability
   - Include bullet points for course listings
   - Show ROI: What they get for the investment

3. PROOF (Social Proof - When appropriate):
   - Scarcity: 'Spaces fill up quickly' or 'Only X spots remaining'
   - Social proof: Mention popularity ('Our most booked course') or testimonials
   - Authority: CQC compliance, CPD-accredited
   - Use specific numbers: 'Many care providers book 2-3 months in advance'

4. PUSH (Call-to-Action):
   - Single, focused action: 'Secure Your Place' or 'Book Before Spaces Fill'
   - Compelling reason to act now: Limited spaces, early booking benefits
   - Create FOMO: 'Don't wait until the last minute. Book now to avoid disappointment'
   - Prominent button with action-oriented language

ADDITIONAL REQUIREMENTS:
- Use scarcity principle: Highlight limited spaces, early booking benefits, deadline approaching
- Conversational tone: Helpful tone, like a colleague sharing opportunities
- Scannable design: Short paragraphs, clear hierarchy, bullet points
- Length: 150-200 words
- Use {first_name} for personalization",

        'custom' => "Write a newsletter email about: " . ($context['topic'] ?? 'training updates') . " using the 4 P's Email Formula and email copywriting best practices.

EMAIL STRUCTURE - USE THE 4 P's FORMULA:

1. PROMISE (Opening Hook - First 1-2 sentences):
   - Acknowledge subscriber: 'Hi {first_name},'
   - Lead with benefit: What do they gain from this topic?
   - Create curiosity: Address their pain point or challenge
   - Connect to common challenges: Inspections, staffing, compliance, time constraints

2. PICTURE (Value Section - Main body):
   - Paint the picture: How this topic helps them succeed
   - Show the outcome: Better compliance, time savings, career advancement
   - Use short paragraphs (2-3 sentences max) for scannability
   - Include bullet points for key benefits or takeaways

3. PROOF (Social Proof - When appropriate):
   - Include relevant statistics, examples, or authority signals
   - Use specific numbers when possible
   - Mention industry best practices or CQC guidance if relevant

4. PUSH (Call-to-Action):
   - Single, focused action: One clear action they should take
   - Compelling reason to act now
   - Prominent button with action-oriented language

ADDITIONAL REQUIREMENTS:
- Understand audience: UK care sector professionals seeking CQC compliance and professional development
- Clear value proposition: Why should they care about this topic now?
- Conversational tone: Professional but approachable, like a helpful colleague
- Scannable design: Short paragraphs, bullet points, clear hierarchy
- Length: 150-200 words
- Use {first_name} for personalization
- Write in British English"
    ];
    
    return $prompts[$type] ?? $prompts['custom'];
}

/**
 * Call Anthropic API for newsletter
 */
function ccs_call_anthropic_api_newsletter($api_key, $system, $prompt) {
    $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01'
        ],
        'body' => json_encode([
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => 1024,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['error'])) {
        return new WP_Error('api_error', $body['error']['message']);
    }
    
    if (isset($body['content'][0]['text'])) {
        return $body['content'][0]['text'];
    }
    
    return new WP_Error('api_error', 'Unexpected API response');
}

/**
 * Call OpenAI API for newsletter
 */
function ccs_call_openai_api_newsletter($api_key, $system, $prompt) {
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1024,
            'temperature' => 0.7
        ]),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['error'])) {
        return new WP_Error('api_error', $body['error']['message']);
    }
    
    if (isset($body['choices'][0]['message']['content'])) {
        return $body['choices'][0]['message']['content'];
    }
    
    return new WP_Error('api_error', 'Unexpected API response');
}

/**
 * Call Groq API for newsletter
 */
function ccs_call_groq_api_newsletter($api_key, $system, $prompt) {
    $payload = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ],
        'max_tokens' => 2048,
        'temperature' => 0.5,  // Lower for more consistency
        'top_p' => 0.9,        // Focus responses
        'frequency_penalty' => 0.1,  // Reduce repetition
        'presence_penalty' => 0.1,    // Encourage topic coverage
        // Prefer JSON responses when supported by the model/provider.
        'response_format' => ['type' => 'json_object'],
    ];

    $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => wp_json_encode($payload),
        'timeout' => 60
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['error'])) {
        // Some Groq models / accounts may not support `response_format`.
        // Retry once without it so the feature keeps working.
        $message = (string) ($body['error']['message'] ?? 'Groq API error');
        if (stripos($message, 'response_format') !== false) {
            unset($payload['response_format']);
            $retry = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ],
                'body' => wp_json_encode($payload),
                'timeout' => 60,
            ]);

            if (is_wp_error($retry)) {
                return $retry;
            }

            $retry_body = json_decode(wp_remote_retrieve_body($retry), true);
            if (isset($retry_body['error'])) {
                return new WP_Error('api_error', $retry_body['error']['message'] ?? 'Groq API error');
            }
            if (isset($retry_body['choices'][0]['message']['content'])) {
                return $retry_body['choices'][0]['message']['content'];
            }
        }

        return new WP_Error('api_error', $message);
    }
    
    if (isset($body['choices'][0]['message']['content'])) {
        return $body['choices'][0]['message']['content'];
    }
    
    return new WP_Error('api_error', 'Unexpected Groq API response');
}

/**
 * Send test email to a single address
 */
function ccs_send_test_email($subject, $content, $link_type, $course_id, $article_id, $test_email) {
    // Process content same as regular email
    if ($link_type === 'course' && $course_id) {
        $course = get_post($course_id);
        if ($course) {
            $content = str_replace('[Course details will be inserted here]', '<strong>' . esc_html($course->post_title) . '</strong><br>' . wp_trim_words($course->post_content, 30), $content);
            $content = str_replace('<a href="#">Book Your Place Now →</a>', '<a href="' . get_permalink($course_id) . '">Book Your Place Now →</a>', $content);
        }
    } elseif ($link_type === 'article' && $article_id) {
        $article = get_post($article_id);
        if ($article) {
            $content = str_replace('[Article title and summary will be inserted here]', '<strong>' . esc_html($article->post_title) . '</strong><br>' . wp_trim_words($article->post_content, 30), $content);
            $content = str_replace('<a href="#">Read the Full Article →</a>', '<a href="' . get_permalink($article_id) . '">Read the Full Article →</a>', $content);
        }
    }
    
    // Replace placeholders
    $site_name = get_bloginfo('name');
    $content = str_replace(['{site_name}', '{first_name}', '{unsubscribe_link}'], [$site_name, 'Test', '<a href="#">Unsubscribe</a>'], $content);
    
    // Get email configuration
    $from_email = defined('CCS_EMAIL_OFFICE') && !empty(CCS_EMAIL_OFFICE) 
        ? CCS_EMAIL_OFFICE 
        : get_option('admin_email');
    $from_name = $site_name;
    
    // Build email headers
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
    ];
    
    // Wrap in HTML template (simplified version)
    $html_email = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="margin: 0; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f5f5f5;"><div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px;">' . $content . '<p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; font-size: 12px; color: #8c8f94; text-align: center;">This is a test email sent from ' . esc_html($site_name) . '</p></div></body></html>';
    
    $sent = wp_mail($test_email, '[TEST] ' . $subject, $html_email, $headers);
    
    if ($sent) {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Test email sent successfully to ' . esc_html($test_email) . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Failed to send test email. Check your email configuration.</p></div>';
    }
}

/**
 * Add email to queue for later sending
 */
function ccs_add_email_to_queue($campaign_id, $subscriber_id, $email, $subject, $content, $headers, $scheduled_for = null) {
    global $wpdb;
    $queue_table = $wpdb->prefix . 'ccs_email_queue';
    
    $queue_data = [
        'campaign_id' => $campaign_id,
        'subscriber_id' => $subscriber_id,
        'email' => $email,
        'subject' => $subject,
        'content' => $content,
        'headers' => is_array($headers) ? implode("\n", $headers) : $headers,
        'status' => 'pending',
        'scheduled_for' => $scheduled_for
    ];
    
    $queue_format = ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s'];
    
    return $wpdb->insert($queue_table, $queue_data, $queue_format);
}

/**
 * Process email queue
 * Called by WordPress cron
 */
function ccs_process_email_queue() {
    global $wpdb;
    $queue_table = $wpdb->prefix . 'ccs_email_queue';
    $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
    $subscribers_table = $wpdb->prefix . 'ccs_newsletter_subscribers';
    
    // Get pending emails (max 50 per batch to avoid timeouts)
    $pending = $wpdb->get_results($wpdb->prepare(
        "SELECT q.*, s.first_name, s.last_name 
         FROM $queue_table q
         LEFT JOIN $subscribers_table s ON q.subscriber_id = s.id
         WHERE q.status = 'pending' 
         AND (q.scheduled_for IS NULL OR q.scheduled_for <= %s)
         ORDER BY q.id ASC
         LIMIT 50",
        current_time('mysql')
    ));
    
    if (empty($pending)) {
        return; // No emails to process
    }
    
    // Update campaign status from 'scheduled' to 'sending' when processing starts
    $campaign_ids = array_unique(array_column($pending, 'campaign_id'));
    foreach ($campaign_ids as $camp_id) {
        $columns = $wpdb->get_col("DESC $campaigns_table", 0);
        $has_status = in_array('status', $columns);
        if ($has_status) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $campaigns_table SET status = 'sending', sent_at = %s WHERE id = %d AND status = 'scheduled'",
                current_time('mysql'),
                $camp_id
            ));
        }
    }
    
    $site_name = get_bloginfo('name');
    $from_email = defined('CCS_EMAIL_OFFICE') && !empty(CCS_EMAIL_OFFICE) 
        ? CCS_EMAIL_OFFICE 
        : get_option('admin_email');
    $from_name = $site_name;
    
    $sent_count = 0;
    $failed_count = 0;
    
    foreach ($pending as $queue_item) {
        // Update status to processing
        $wpdb->update(
            $queue_table,
            ['status' => 'processing', 'last_attempt_at' => current_time('mysql'), 'attempts' => $queue_item->attempts + 1],
            ['id' => $queue_item->id],
            ['%s', '%s', '%d'],
            ['%d']
        );
        
        // Get subscriber info
        $subscriber_name = !empty($queue_item->first_name) ? $queue_item->first_name : '';
        
        // Personalize content
        $email_content = $queue_item->content;
        $email_content = str_replace(
            ['{site_name}', '{unsubscribe_link}', '{first_name}'],
            [$site_name, '<a href="' . esc_url(home_url('/unsubscribe/')) . '">Unsubscribe</a>', $subscriber_name],
            $email_content
        );
        
        // Create unsubscribe link
        $unsubscribe_token = wp_hash($queue_item->email . $queue_item->subscriber_id);
        $unsubscribe_url = add_query_arg([
            'ccs_unsubscribe' => 1,
            'email' => urlencode($queue_item->email),
            'token' => $unsubscribe_token
        ], home_url('/unsubscribe/'));
        
        // Build headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'List-Unsubscribe: <' . esc_url($unsubscribe_url) . '>',
            'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
            'Precedence: bulk',
            'Auto-Submitted: auto-generated',
        ];
        
        // Create tracking pixel
        $tracking_pixel_url = add_query_arg([
            'ccs_track' => 'open',
            'campaign' => $queue_item->campaign_id,
            'subscriber' => $queue_item->subscriber_id,
            'token' => wp_hash($queue_item->campaign_id . $queue_item->subscriber_id . $queue_item->email)
        ], home_url('/'));
        
        // Wrap in HTML template (use same template as regular send)
        $html_email = ccs_build_email_template($queue_item->subject, $email_content, $site_name, $unsubscribe_url, $tracking_pixel_url);
        
        // Send email
        $sent = wp_mail($queue_item->email, $queue_item->subject, $html_email, $headers);
        
        if ($sent) {
            $wpdb->update(
                $queue_table,
                ['status' => 'sent', 'sent_at' => current_time('mysql')],
                ['id' => $queue_item->id],
                ['%s', '%s'],
                ['%d']
            );
            $sent_count++;
        } else {
            // Mark as failed (will retry up to 3 times)
            if ($queue_item->attempts >= 3) {
                $wpdb->update(
                    $queue_table,
                    ['status' => 'failed', 'error_message' => 'Max attempts reached'],
                    ['id' => $queue_item->id],
                    ['%s', '%s'],
                    ['%d']
                );
            } else {
                $wpdb->update(
                    $queue_table,
                    ['status' => 'pending', 'error_message' => 'Send failed, will retry'],
                    ['id' => $queue_item->id],
                    ['%s', '%s'],
                    ['%d']
                );
            }
            $failed_count++;
        }
        
        // Small delay
        usleep(100000); // 0.1 second
    }
    
    // Update campaign status
    if (!empty($pending)) {
        $campaign_id = $pending[0]->campaign_id;
        $remaining = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d AND status = 'pending'",
            $campaign_id
        ));
        
        if ($remaining == 0) {
            $wpdb->update(
                $campaigns_table,
                ['status' => 'completed'],
                ['id' => $campaign_id],
                ['%s'],
                ['%d']
            );
        }
    }
    
    // Schedule next batch if there are more pending
    $more_pending = $wpdb->get_var("SELECT COUNT(*) FROM $queue_table WHERE status = 'pending'");
    if ($more_pending > 0) {
        if (!wp_next_scheduled('ccs_process_email_queue')) {
            wp_schedule_single_event(time() + 30, 'ccs_process_email_queue');
        }
    }
}
add_action('ccs_process_email_queue', 'ccs_process_email_queue');

/**
 * Send welcome email to new subscriber
 */
function ccs_send_welcome_email($subscriber_id, $email, $first_name = '') {
    // Check if welcome emails are enabled (can be disabled via option)
    $welcome_emails_enabled = get_option('ccs_welcome_emails_enabled', true);
    if (!$welcome_emails_enabled) {
        return;
    }
    
    global $wpdb;
    $templates_table = $wpdb->prefix . 'ccs_email_templates';
    
    // Try to get welcome email template from database
    $welcome_template = $wpdb->get_row(
        "SELECT * FROM $templates_table WHERE template_type = 'welcome' AND is_system = 1 ORDER BY id ASC LIMIT 1"
    );
    
    // If no template found, use default welcome email
    if (!$welcome_template) {
        $subject = 'Welcome to Continuity of Care Services!';
        $content = ccs_get_default_welcome_email_content();
    } else {
        $subject = $welcome_template->subject;
        $content = $welcome_template->content;
    }
    
    // Replace placeholders
    $site_name = get_bloginfo('name');
    $subscriber_name = !empty($first_name) ? esc_html($first_name) : 'there';
    
    // Create unsubscribe link
    $unsubscribe_token = wp_hash($email . $subscriber_id);
    $unsubscribe_url = add_query_arg([
        'ccs_unsubscribe' => 1,
        'email' => urlencode($email),
        'token' => $unsubscribe_token
    ], home_url('/unsubscribe/'));
    $unsubscribe_link = '<a href="' . esc_url($unsubscribe_url) . '" style="color: #2271b1; text-decoration: underline;">Unsubscribe</a>';
    
    // Replace placeholders in content
    $content = str_replace(
        ['{site_name}', '{unsubscribe_link}', '{first_name}'],
        [esc_html($site_name), $unsubscribe_link, $subscriber_name],
        $content
    );
    
    // Build email headers
    $from_email = defined('CCS_EMAIL_OFFICE') && !empty(CCS_EMAIL_OFFICE) 
        ? CCS_EMAIL_OFFICE 
        : get_option('admin_email');
    $from_name = $site_name;
    
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
        'List-Unsubscribe: <' . esc_url($unsubscribe_url) . '>',
        'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
        'Precedence: bulk',
        'Auto-Submitted: auto-generated',
    ];
    
    // Build HTML email (no tracking pixel for welcome emails)
    $html_email = ccs_build_email_template($subject, $content, $site_name, $unsubscribe_url, '');
    
    // Send email
    $sent = wp_mail($email, $subject, $html_email, $headers);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        if ($sent) {
            error_log('CCS Newsletter: Welcome email sent to ' . $email);
        } else {
            error_log('CCS Newsletter: Failed to send welcome email to ' . $email);
        }
    }
}

/**
 * Get default welcome email content
 */
function ccs_get_default_welcome_email_content() {
    return '<p>Hi {first_name},</p>
<p><strong>Welcome to Continuity of Care Services!</strong></p>
<p>Thank you for subscribing to our newsletter. We\'re thrilled to have you join our community of care professionals who are committed to continuous learning and excellence in care delivery.</p>
<div style="background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%); border-left: 4px solid #3ba59b; padding: 24px; margin: 24px 0; border-radius: 4px;">
<p style="margin: 0 0 16px 0; font-weight: 600; color: #2b1b0e; font-size: 18px;">What you can expect from us:</p>
<ul style="margin: 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>Latest CQC guidance</strong> and compliance updates</li>
<li><strong>Practical training courses</strong> to help you and your team excel</li>
<li><strong>Expert insights</strong> from industry leaders</li>
<li><strong>Resources and tools</strong> to make your job easier</li>
</ul>
</div>
<p>We know how busy you are, so we promise to only send you content that\'s genuinely valuable—no fluff, just practical insights that help you deliver better care and pass inspections with confidence.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Browse Our Courses →</a></p>
<p>If you have any questions or topics you\'d like us to cover, just reply to this email. We read every message and use your feedback to shape what we create.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Follow us on social media for daily tips and updates. We\'re here to support your professional development journey.</p>';
}

/**
 * Build email HTML template
 * Ensures visually appealing emails with proper HTML structure and no code leaks
 */
function ccs_build_email_template($subject, $content, $site_name, $unsubscribe_url, $tracking_pixel_url) {
    // Sanitize content to prevent code leaks - content should already be HTML from wp_kses_post
    // But we ensure it's properly formatted and no raw code appears
    $sanitized_content = wp_kses_post($content);
    
    // Ensure content is not empty - if it is, provide a fallback
    if (empty(trim(strip_tags($sanitized_content)))) {
        $sanitized_content = '<p>This email was sent from ' . esc_html($site_name) . '.</p>';
    }
    
    $unsubscribe_link = '<a href="' . esc_url($unsubscribe_url) . '" style="color: #2271b1; text-decoration: underline;">Unsubscribe</a>';
    
    // Use robust HTML email template with Outlook/MSO compatibility
    return '<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>' . esc_html($subject) . '</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style type="text/css">
        table {border-collapse: collapse; border-spacing: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt;}
        .outlook-group-fix {width:100% !important;}
    </style>
    <![endif]-->
    <style type="text/css">
        /* Reset styles */
        body, table, td, p, a, li, blockquote {-webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;}
        table, td {mso-table-lspace: 0pt; mso-table-rspace: 0pt;}
        img {-ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none;}
        /* Mobile styles */
        @media only screen and (max-width: 600px) {
            .email-container {width: 100% !important; max-width: 100% !important;}
            .email-content {padding: 24px 20px !important;}
            .email-header {padding: 24px 20px !important;}
            .email-footer {padding: 20px !important;}
            h1 {font-size: 22px !important;}
            .button {padding: 14px 24px !important; font-size: 15px !important;}
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
    <!--[if mso | IE]>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td>
    <![endif]-->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5; margin: 0; padding: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!--[if mso | IE]>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
                    <tr>
                        <td>
                <![endif]-->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 600px; width: 100%; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                    <!-- Header -->
                    <tr>
                        <td class="email-header" style="padding: 32px 40px 24px; border-bottom: 3px solid #3ba59b; background: linear-gradient(135deg, #fefdfb 0%, #ffffff 100%);">
                            <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #2b1b0e; line-height: 1.2; letter-spacing: -0.5px;">
                                ' . esc_html($site_name) . '
                            </h1>
                            <p style="margin: 8px 0 0 0; font-size: 14px; color: #646970; font-weight: 500;">
                                Professional Care Sector Training
                            </p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td class="email-content" style="padding: 36px 40px;">
                            <div style="color: #2b1b0e; font-size: 16px; line-height: 1.75;">
                                ' . $sanitized_content . '
                            </div>
                        </td>
                    </tr>
                    <!-- Tracking Pixel (only if URL provided) -->
                    ' . (!empty($tracking_pixel_url) ? '<tr>
                        <td style="height: 1px; line-height: 1px; font-size: 1px;">
                            <img src="' . esc_url($tracking_pixel_url) . '" width="1" height="1" alt="" style="display: block; width: 1px; height: 1px; border: 0;" />
                        </td>
                    </tr>' : '') . '
                    <!-- Unsubscribe Footer -->
                    <tr>
                        <td style="padding: 20px 40px; background-color: #fafafa; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0; font-size: 12px; color: #8c8f94; text-align: center; line-height: 1.5;">
                                You\'re receiving this because you subscribed to our newsletter. 
                                ' . $unsubscribe_link . '
                            </p>
                            <p style="margin: 8px 0 0 0; font-size: 11px; color: #a7aaad; text-align: center; line-height: 1.4;">
                                We track email opens and clicks to improve our communications. 
                                <a href="' . esc_url(home_url('/privacy-policy/')) . '" style="color: #8c8f94; text-decoration: underline;">Learn more in our Privacy Policy</a>.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td class="email-footer" style="padding: 24px 40px; background-color: #f9f9f9; border-top: 1px solid #e0e0e0; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0 0 12px 0; font-size: 13px; color: #646970; text-align: center; line-height: 1.6;">
                                <strong>Continuity of Care Services</strong><br>
                                The Maidstone Studios, New Cut Road<br>
                                Maidstone, Kent, ME14 5NZ
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #8c8f94; text-align: center; line-height: 1.5;">
                                <a href="' . esc_url(home_url('/contact/')) . '" style="color: #2271b1; text-decoration: none; margin: 0 8px;">Contact Us</a> | 
                                <a href="' . esc_url(home_url('/privacy-policy/')) . '" style="color: #2271b1; text-decoration: none; margin: 0 8px;">Privacy Policy</a> | 
                                <a href="' . esc_url(home_url('/terms-conditions/')) . '" style="color: #2271b1; text-decoration: none; margin: 0 8px;">Terms</a>
                            </p>
                        </td>
                    </tr>
                </table>
                <!--[if mso | IE]>
                        </td>
                    </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
    <!--[if mso | IE]>
            </td>
        </tr>
    </table>
    <![endif]-->
</body>
</html>';
}

/**
 * Retry failed emails in queue
 */
function ccs_retry_failed_emails($campaign_id = null) {
    global $wpdb;
    $queue_table = $wpdb->prefix . 'ccs_email_queue';
    
    $where = "status = 'failed'";
    $params = [];
    
    if ($campaign_id) {
        $where .= " AND campaign_id = %d";
        $params[] = $campaign_id;
    }
    
    if (!empty($params)) {
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $queue_table SET status = 'pending', attempts = 0, error_message = NULL WHERE $where",
            $params
        ));
    } else {
        $result = $wpdb->query("UPDATE $queue_table SET status = 'pending', attempts = 0, error_message = NULL WHERE $where");
    }
    
    if ($result > 0) {
        // Schedule queue processing
        if (!wp_next_scheduled('ccs_process_email_queue')) {
            wp_schedule_single_event(time() + 10, 'ccs_process_email_queue');
        }
        return $result;
    }
    
    return 0;
}

/**
 * Cancel a scheduled campaign
 * Deletes pending queue items and updates campaign status
 */
function ccs_cancel_scheduled_campaign($campaign_id) {
    global $wpdb;
    $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
    $queue_table = $wpdb->prefix . 'ccs_email_queue';
    
    // Verify campaign exists and is scheduled
    $columns = $wpdb->get_col("DESC $campaigns_table", 0);
    $has_status = in_array('status', $columns);
    
    $campaign = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $campaigns_table WHERE id = %d",
        $campaign_id
    ));
    
    if (!$campaign) {
        return false;
    }
    
    // Check if campaign is scheduled
    if ($has_status && isset($campaign->status) && $campaign->status !== 'scheduled') {
        return false; // Not a scheduled campaign
    }
    
    // Delete all pending queue items for this campaign
    $deleted_queue = $wpdb->query($wpdb->prepare(
        "DELETE FROM $queue_table WHERE campaign_id = %d AND status IN ('pending', 'processing')",
        $campaign_id
    ));
    
    // Update campaign status to cancelled or delete it
    if ($has_status) {
        // Check if status column supports 'cancelled'
        $wpdb->update(
            $campaigns_table,
            ['status' => 'cancelled'],
            ['id' => $campaign_id],
            ['%s'],
            ['%d']
        );
    } else {
        // If no status column, delete the campaign record
        $wpdb->delete($campaigns_table, ['id' => $campaign_id], ['%d']);
    }
    
    return true;
}

/**
 * Send automated birthday emails to subscribers
 * Runs daily via WordPress cron
 */
function ccs_send_birthday_emails() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccs_newsletter_subscribers';
    
    // Get current date (month and day only)
    $today = current_time('Y-m-d');
    $current_year = (int)date('Y', strtotime($today));
    $current_month = (int)date('m', strtotime($today));
    $current_day = (int)date('d', strtotime($today));
    
    // Find subscribers with birthdays today who haven't received a birthday email this year
    // Match month and day, but not year (so it works every year)
    $subscribers = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE status = 'active' 
         AND date_of_birth IS NOT NULL 
         AND date_of_birth != ''
         AND MONTH(date_of_birth) = %d 
         AND DAY(date_of_birth) = %d
         AND (last_birthday_email_year IS NULL OR last_birthday_email_year < %d)",
        $current_month,
        $current_day,
        $current_year
    ));
    
    if (empty($subscribers)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CTA Birthday Emails: No subscribers with birthdays today.');
        }
        return;
    }
    
    // Get birthday email template
    $template_data = ccs_get_populated_template('birthday');
    if (!$template_data || !isset($template_data['subject']) || !isset($template_data['content'])) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CTA Birthday Emails: Birthday template not found.');
        }
        return;
    }
    
    $subject_template = $template_data['subject'];
    $content_template = $template_data['content'];
    
    // Get email configuration
    $site_name = get_bloginfo('name');
    $from_email = defined('CCS_EMAIL_OFFICE') && !empty(CCS_EMAIL_OFFICE) 
        ? CCS_EMAIL_OFFICE 
        : get_option('admin_email');
    $from_name = $site_name;
    
    if (empty($from_email)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CTA Birthday Emails: Email not configured.');
        }
        return;
    }
    
    $sent_count = 0;
    $failed_count = 0;
    
    // Create campaign record for birthday emails
    $campaigns_table = $wpdb->prefix . 'ccs_email_campaigns';
    $columns = $wpdb->get_col("DESC $campaigns_table", 0);
    $has_status = in_array('status', $columns);
    
    $campaign_data = [
        'subject' => '🎂 Birthday Emails - ' . $today,
        'sent_at' => current_time('mysql'),
        'total_sent' => count($subscribers)
    ];
    $campaign_format = ['%s', '%s', '%d'];
    
    if ($has_status) {
        $campaign_data['status'] = 'sending';
        $campaign_format[] = '%s';
    }
    
    $campaign_id = $wpdb->insert(
        $campaigns_table,
        $campaign_data,
        $campaign_format
    );
    
    if ($campaign_id === false) {
        $campaign_id = 0;
    } else {
        $campaign_id = $wpdb->insert_id;
    }
    
    foreach ($subscribers as $subscriber) {
        // Personalize subject and content
        $first_name = !empty($subscriber->first_name) ? $subscriber->first_name : 'there';
        $subject = str_replace('{first_name}', $first_name, $subject_template);
        $content = str_replace('{first_name}', $first_name, $content_template);
        $content = str_replace('{site_name}', $site_name, $content);
        
        // Create unsubscribe link
        $unsubscribe_token = wp_hash($subscriber->email . $subscriber->id);
        $unsubscribe_url = add_query_arg([
            'ccs_unsubscribe' => 1,
            'email' => urlencode($subscriber->email),
            'token' => $unsubscribe_token
        ], home_url('/unsubscribe/'));
        $content = str_replace('{unsubscribe_link}', '<a href="' . esc_url($unsubscribe_url) . '" style="color: #2271b1; text-decoration: underline;">Unsubscribe</a>', $content);
        
        // Build email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'X-Mailer: WordPress/' . get_bloginfo('version'),
            'Message-ID: <' . time() . '.' . md5($subscriber->email . time() . $campaign_id) . '@' . parse_url(home_url(), PHP_URL_HOST) . '>',
            'List-Unsubscribe: <' . esc_url($unsubscribe_url) . '>',
            'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
            'Precedence: bulk',
            'Auto-Submitted: auto-generated',
        ];
        
        // Create tracking pixel
        $tracking_pixel_url = add_query_arg([
            'ccs_track' => 'open',
            'campaign' => $campaign_id,
            'subscriber' => $subscriber->id,
            'token' => wp_hash($campaign_id . $subscriber->id . $subscriber->email)
        ], home_url('/'));
        
        // Build HTML email template
        $html_email = ccs_build_email_template($subject, $content, $site_name, $unsubscribe_url, $tracking_pixel_url);
        
        // Send email
        $sent = wp_mail($subscriber->email, $subject, $html_email, $headers);
        
        if ($sent) {
            // Update tracking - mark that birthday email was sent this year
            $wpdb->update(
                $table_name,
                ['last_birthday_email_year' => $current_year],
                ['id' => $subscriber->id],
                ['%d'],
                ['%d']
            );
            
            $sent_count++;
        } else {
            $failed_count++;
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CTA Birthday Emails: Failed to send to ' . $subscriber->email);
            }
        }
        
        // Small delay to avoid overwhelming mail server
        usleep(100000); // 0.1 second delay
    }
    
    // Update campaign status
    if ($campaign_id > 0 && $has_status) {
        $wpdb->update(
            $campaigns_table,
            ['status' => 'completed'],
            ['id' => $campaign_id],
            ['%s'],
            ['%d']
        );
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf('CTA Birthday Emails: Sent %d birthday emails, %d failed.', $sent_count, $failed_count));
    }
}
add_action('ccs_send_birthday_emails', 'ccs_send_birthday_emails');

/**
 * Schedule daily birthday email check
 */
function ccs_schedule_birthday_emails() {
    if (!wp_next_scheduled('ccs_send_birthday_emails')) {
        // Schedule to run daily at 9 AM
        $schedule_time = strtotime('today 9:00 AM');
        if ($schedule_time < time()) {
            $schedule_time = strtotime('tomorrow 9:00 AM');
        }
        wp_schedule_event($schedule_time, 'daily', 'ccs_send_birthday_emails');
    }
}
add_action('wp', 'ccs_schedule_birthday_emails');
add_action('after_switch_theme', 'ccs_schedule_birthday_emails');

/**
 * Migrate existing form submissions and leads to newsletter subscribers
 * Runs on theme activation to combine all existing data
 */
function ccs_migrate_existing_data_to_newsletter() {
    global $wpdb;
    
    // Check if migration has already run
    $migration_key = 'ccs_newsletter_migration_completed';
    if (get_option($migration_key)) {
        return; // Migration already completed
    }
    
    $subscribers_table = $wpdb->prefix . 'ccs_newsletter_subscribers';
    $migrated_count = 0;
    $skipped_count = 0;
    
    // 1. Migrate form submissions with marketing consent
    $form_submissions = get_posts([
        'post_type' => 'form_submission',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_submission_marketing_consent',
                'value' => ['yes', 'on', '1'],
                'compare' => 'IN'
            ],
            [
                'key' => '_submission_email',
                'compare' => 'EXISTS'
            ]
        ]
    ]);
    
    foreach ($form_submissions as $submission) {
        $email = get_post_meta($submission->ID, '_submission_email', true);
        $name = get_post_meta($submission->ID, '_submission_name', true);
        $phone = get_post_meta($submission->ID, '_submission_phone', true);
        $ip = get_post_meta($submission->ID, '_submission_ip', true);
        $submitted_date = $submission->post_date;
        
        if (empty($email) || !is_email($email)) {
            $skipped_count++;
            continue;
        }
        
        // Check if subscriber already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status FROM $subscribers_table WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            // Update existing subscriber if they were unsubscribed (reactivate them)
            if ($existing->status === 'unsubscribed') {
                $wpdb->update(
                    $subscribers_table,
                    [
                        'status' => 'active',
                        'unsubscribed_at' => null
                    ],
                    ['id' => $existing->id],
                    ['%s', '%s'],
                    ['%d']
                );
            }
            $skipped_count++;
            continue;
        }
        
        // Parse name into first/last
        $name_parts = explode(' ', trim($name), 2);
        $first_name = !empty($name_parts[0]) ? $name_parts[0] : null;
        $last_name = !empty($name_parts[1]) ? $name_parts[1] : null;
        
        // Insert new subscriber
        $wpdb->insert(
            $subscribers_table,
            [
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'subscribed_at' => $submitted_date,
                'ip_address' => $ip,
                'status' => 'active'
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($wpdb->last_error) {
            error_log('CCS Newsletter Migration: Error migrating form submission ' . $submission->ID . ' - ' . $wpdb->last_error);
            $skipped_count++;
        } else {
            $migrated_count++;
            // Link submission to subscriber
            update_post_meta($submission->ID, '_submission_newsletter_subscriber_id', $wpdb->insert_id);
            update_post_meta($submission->ID, '_submission_newsletter_status', 'migrated');
        }
    }
    
    // 2. Check for any other email sources (custom post types, user emails, etc.)
    // Migrate WordPress users who have opted in (if you have a meta field for this)
    $users = get_users([
        'meta_query' => [
            [
                'key' => 'newsletter_subscription',
                'value' => 'yes',
                'compare' => '='
            ]
        ]
    ]);
    
    foreach ($users as $user) {
        if (empty($user->user_email) || !is_email($user->user_email)) {
            continue;
        }
        
        // Check if already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $subscribers_table WHERE email = %s",
            $user->user_email
        ));
        
        if ($existing) {
            continue;
        }
        
        // Insert subscriber
        $wpdb->insert(
            $subscribers_table,
            [
                'email' => $user->user_email,
                'first_name' => $user->first_name ?: null,
                'last_name' => $user->last_name ?: null,
                'subscribed_at' => $user->user_registered,
                'status' => 'active'
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
        
        if (!$wpdb->last_error) {
            $migrated_count++;
        }
    }
    
    // Mark migration as completed
    update_option($migration_key, time());
    
    // Log results
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'CCS Newsletter Migration: Migrated %d subscribers, skipped %d duplicates/existing',
            $migrated_count,
            $skipped_count
        ));
    }
    
    // Show admin notice if in admin
    if (is_admin() && $migrated_count > 0) {
        add_action('admin_notices', function() use ($migrated_count) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Newsletter Migration Complete:</strong> ' . $migrated_count . ' existing contacts have been added to your newsletter subscribers.</p>';
            echo '</div>';
        });
    }
}
/**
 * Download sample CSV file
 */
function ccs_download_sample_csv() {
    // Generate sample CSV data
    $csv_data = [
        ['email', 'first name', 'last name', 'date of birth'],
        ['john.doe@example.com', 'John', 'Doe', '1985-03-15'],
        ['jane.smith@example.com', 'Jane', 'Smith', '1990-07-22'],
        ['bob.wilson@example.com', 'Bob', 'Wilson', '']
    ];
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=newsletter-subscribers-sample.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output CSV
    $output = fopen('php://output', 'w');
    foreach ($csv_data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Handle sample CSV download
add_action('admin_init', function() {
    if (isset($_GET['action']) && $_GET['action'] === 'download_sample_csv' && isset($_GET['page']) && $_GET['page'] === 'cta-newsletter-subscribers') {
        if (current_user_can('edit_others_posts')) {
            ccs_download_sample_csv();
        }
    }
});