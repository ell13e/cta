<?php
/**
 * Resource downloads admin page (simple tracking table).
 *
 * @package ccs-theme
 */
defined('ABSPATH') || exit;

function ccs_resource_downloads_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=ccs_resource',
        'Resource Downloads',
        'Downloads',
        'edit_posts',
        'cta-resource-downloads',
        'ccs_resource_downloads_admin_page'
    );
}
add_action('admin_menu', 'ccs_resource_downloads_admin_menu', 30);

function ccs_resource_downloads_admin_page() {
    if (!current_user_can('edit_posts')) {
        wp_die('You do not have permission to view this page.');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ccs_resource_downloads';

    // Get filter parameters
    $resource_filter = isset($_GET['resource_id']) ? absint($_GET['resource_id']) : 0;
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    $page = max(1, absint($_GET['paged'] ?? 1));
    $per_page = 25;
    $offset = ($page - 1) * $per_page;

    // Build query with filters
    $where = ['1=1'];
    $where_values = [];
    
    if ($resource_filter > 0) {
        $where[] = 'resource_id = %d';
        $where_values[] = $resource_filter;
    }
    
    if (!empty($search)) {
        $where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)';
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $where_values[] = $search_term;
        $where_values[] = $search_term;
        $where_values[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where);

    // Get total count
    if (!empty($where_values)) {
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE $where_clause",
            ...$where_values
        ));
    } else {
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where_clause");
    }

    // Get rows
    if (!empty($where_values)) {
        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE $where_clause ORDER BY downloaded_at DESC LIMIT %d OFFSET %d",
            ...array_merge($where_values, [$per_page, $offset])
        );
    } else {
        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE $where_clause ORDER BY downloaded_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );
    }
    
    $rows = $wpdb->get_results($query);
    $total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 1;

    // Get all resources for filter dropdown
    $resources = get_posts([
        'post_type' => 'ccs_resource',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    ]);

    // Get summary stats
    $total_downloads = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $total_unique_emails = (int) $wpdb->get_var("SELECT COUNT(DISTINCT email) FROM $table");
    $emails_sent = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE email_sent = 1");
    $with_consent = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE consent = 1");

    ?>
    <div class="wrap">
        <h1>Resource Downloads</h1>
        <p class="description">Track resource download requests, lead capture, and email delivery.</p>

        <!-- Summary Stats -->
        <ul class="subsubsub" style="margin: 20px 0;">
            <li><strong><?php echo number_format_i18n($total_downloads); ?></strong> Total Downloads</li> |
            <li><strong><?php echo number_format_i18n($total_unique_emails); ?></strong> Unique Contacts</li> |
            <li><strong><?php echo number_format_i18n($emails_sent); ?></strong> Emails Sent</li> |
            <li><strong><?php echo number_format_i18n($with_consent); ?></strong> Marketing Consent</li>
        </ul>

        <!-- Filters -->
        <div class="tablenav top">
            <form method="get" action="" style="display: inline-block;">
                <input type="hidden" name="post_type" value="ccs_resource">
                <input type="hidden" name="page" value="cta-resource-downloads">
                
                <label for="resource-filter" class="screen-reader-text">Filter by resource</label>
                <select name="resource_id" id="resource-filter">
                    <option value="0">All Resources</option>
                    <?php foreach ($resources as $resource) : ?>
                        <option value="<?php echo esc_attr($resource->ID); ?>" <?php selected($resource_filter, $resource->ID); ?>>
                            <?php echo esc_html($resource->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label for="search-input" class="screen-reader-text">Search contacts</label>
                <input type="search" id="search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search contacts...">
                
                <button type="submit" class="button">Filter</button>
                
                <?php if ($resource_filter > 0 || !empty($search)) : ?>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=ccs_resource&page=cta-resource-downloads')); ?>" class="button">Clear</a>
                <?php endif; ?>
            </form>
            
            <div class="alignright actions">
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=ccs_resource&page=cta-resource-downloads&export=csv')); ?>" class="button">
                    <span class="dashicons dashicons-download" style="vertical-align: middle;"></span> Export CSV
                </a>
            </div>
        </div>

        <?php if ($total_downloads === 0) : ?>
            <!-- Empty state -->
            <div class="notice notice-info inline">
                <p><strong>No downloads yet.</strong></p>
                <p>Once visitors request resources, their information will appear here. <a href="<?php echo esc_url(admin_url('post-new.php?post_type=ccs_resource')); ?>">Add a resource</a> to get started.</p>
            </div>
        <?php else : ?>
            <!-- Downloads table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" style="width: 15%;">Date</th>
                        <th scope="col" style="width: 25%;">Contact</th>
                        <th scope="col" style="width: 30%;">Resource</th>
                        <th scope="col" style="width: 20%;">Status</th>
                        <th scope="col" style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rows) : ?>
                    <tr>
                        <td colspan="5">
                            <p>No downloads match your filters. Try adjusting your search.</p>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($rows as $r) : 
                        $resource_title = $r->resource_id ? get_the_title((int) $r->resource_id) : '(deleted)';
                        $date = mysql2date('j M Y, H:i', (string) $r->downloaded_at);
                        $name = trim($r->first_name . ' ' . $r->last_name);
                        $email = (string) $r->email;
                        $phone = (string) ($r->phone ?? '');
                        $consent = (int) $r->consent === 1;
                        $email_sent = (int) $r->email_sent === 1;
                        $count = (int) $r->download_count;
                    ?>
                    <tr>
                        <td>
                            <?php echo esc_html($date); ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($name); ?></strong>
                            <?php if ($count > 1) : ?>
                                <span class="count" title="<?php echo esc_attr($count); ?> downloads">(<?php echo esc_html($count); ?>)</span>
                            <?php endif; ?>
                            <br>
                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                            <?php if ($phone) : ?>
                                <br><span class="description"><?php echo esc_html($phone); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r->resource_id && get_post_status($r->resource_id)) : ?>
                                <a href="<?php echo esc_url(get_edit_post_link($r->resource_id)); ?>">
                                    <?php echo esc_html($resource_title); ?>
                                </a>
                            <?php else : ?>
                                <span class="description"><?php echo esc_html($resource_title); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($email_sent) : ?>
                                <span class="dashicons dashicons-yes-alt" style="color: #00a32a;" title="Email sent"></span> Email sent
                            <?php else : ?>
                                <span class="dashicons dashicons-dismiss" style="color: #d63638;" title="Email failed"></span> Failed
                            <?php endif; ?>
                            <?php if ($consent) : ?>
                                <br><span class="dashicons dashicons-yes" style="color: #00a32a;" title="Marketing consent given"></span> Consent
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="mailto:<?php echo esc_attr($email); ?>" class="button button-small" title="Send email">
                                <span class="dashicons dashicons-email"></span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php 
                    $start = ($page - 1) * $per_page + 1;
                    $end = min($page * $per_page, $total);
                    ?>
                    <span class="displaying-num"><?php echo number_format_i18n($total); ?> items</span>
                    <?php if ($total_pages > 1) : ?>
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $page,
                        ]);
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Handle CSV export
 */
function ccs_resource_downloads_export_csv() {
    if (!isset($_GET['export']) || $_GET['export'] !== 'csv') {
        return;
    }
    
    if (!isset($_GET['page']) || $_GET['page'] !== 'cta-resource-downloads') {
        return;
    }
    
    if (!current_user_can('edit_posts')) {
        wp_die('You do not have permission to export this data.');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ccs_resource_downloads';
    
    // Get all downloads
    $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY downloaded_at DESC");
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=resource-downloads-' . date('Y-m-d') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, [
        'Date/Time',
        'Resource',
        'First Name',
        'Last Name',
        'Email',
        'Phone',
        'Marketing Consent',
        'Email Sent',
        'Download Count',
        'IP Address',
    ]);
    
    // Add data rows
    foreach ($rows as $row) {
        $resource_title = $row->resource_id ? get_the_title((int) $row->resource_id) : '(deleted)';
        
        fputcsv($output, [
            $row->downloaded_at,
            $resource_title,
            $row->first_name,
            $row->last_name,
            $row->email,
            $row->phone ?? '',
            $row->consent == 1 ? 'Yes' : 'No',
            $row->email_sent == 1 ? 'Yes' : 'No',
            $row->download_count,
            $row->ip_address ?? '',
        ]);
    }
    
    fclose($output);
    exit;
}
add_action('admin_init', 'ccs_resource_downloads_export_csv');

