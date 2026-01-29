<?php
/**
 * Form Submissions Admin Interface
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Add admin notice to help users find form submissions
 */
function cta_form_submissions_admin_notice() {
    $screen = get_current_screen();
    
    // Only show on dashboard
    if ($screen && $screen->id === 'dashboard') {
        // Count recent submissions (last 7 days)
        $recent_submissions = get_posts([
            'post_type' => 'form_submission',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'date_query' => [
                [
                    'after' => '7 days ago',
                ],
            ],
            'fields' => 'ids',
        ]);
        
        $count = count($recent_submissions);
        
        if ($count > 0) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>Form Submissions:</strong> You have ' . $count . ' new form submission' . ($count !== 1 ? 's' : '') . ' in the last 7 days. ';
            echo '<a href="' . admin_url('edit.php?post_type=form_submission') . '">View all submissions &rarr;</a></p>';
            echo '</div>';
        }
    }
}
add_action('admin_notices', 'cta_form_submissions_admin_notice');

/**
 * Improve browser tab title for form submissions
 */
function cta_form_submissions_admin_title($admin_title, $title) {
    global $typenow;
    
    if (is_admin() && $typenow === 'form_submission') {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'edit-form_submission') {
            // Get active tab for context
            $selected_tab = isset($_GET['submission_tab']) ? sanitize_text_field($_GET['submission_tab']) : 'all';
            $tab_labels = [
                'all' => 'All Submissions',
                'course-enquiries' => 'Course Enquiries',
                'event-bookings' => 'Event Bookings',
                'group-enquiries' => 'Group Enquiries',
                'newsletter' => 'Newsletter Subscribers',
                'other' => 'Other Enquiries',
            ];
            $tab_label = isset($tab_labels[$selected_tab]) ? $tab_labels[$selected_tab] : 'Form Submissions';
            return str_replace($title, $tab_label, $admin_title);
        }
    }
    
    return $admin_title;
}
add_filter('admin_title', 'cta_form_submissions_admin_title', 10, 2);

/**
 * Admin notice: allow seeding test submissions on demand.
 */
function cta_form_submissions_seed_test_notice() {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->id !== 'edit-form_submission') {
        return;
    }

    // Feedback after running the action
    if (isset($_GET['cta_seed_test_submissions'])) {
        $status = sanitize_text_field((string) $_GET['cta_seed_test_submissions']);
        if ($status === 'seeded') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Test submissions created.</strong> Refresh the list or switch tabs to see them.</p></div>';
        } elseif ($status === 'already') {
            echo '<div class="notice notice-info is-dismissible"><p><strong>Test submissions already exist.</strong> No changes made.</p></div>';
        } elseif ($status === 'failed') {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Could not create test submissions.</strong> Check your PHP error log for details.</p></div>';
        }
    }

    $url = wp_nonce_url(
        admin_url('admin-post.php?action=cta_seed_test_submissions'),
        'cta_seed_test_submissions'
    );

    echo '<div class="notice notice-info"><p>';
    echo '<strong>Need sample data?</strong> ';
    echo '<a class="button button-secondary" href="' . esc_url($url) . '">Generate test submissions</a>';
    echo '</p></div>';
}
add_action('admin_notices', 'cta_form_submissions_seed_test_notice');

/**
 * Handler: seed test submissions.
 */
function cta_handle_seed_test_submissions_admin_post() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to do this.');
    }
    check_admin_referer('cta_seed_test_submissions');

    // If they already exist, don't duplicate.
    $existing = get_posts([
        'post_type' => 'form_submission',
        'post_status' => 'any',
        'meta_query' => [[
            'key' => '_cta_is_test_submission',
            'value' => '1',
            'compare' => '=',
        ]],
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);

    if (!empty($existing)) {
        wp_safe_redirect(admin_url('edit.php?post_type=form_submission&cta_seed_test_submissions=already'));
        exit;
    }

    if (function_exists('cta_populate_test_form_submissions')) {
        cta_populate_test_form_submissions();
        $existing_after = get_posts([
            'post_type' => 'form_submission',
            'post_status' => 'any',
            'meta_query' => [[
                'key' => '_cta_is_test_submission',
                'value' => '1',
                'compare' => '=',
            ]],
            'posts_per_page' => 1,
            'fields' => 'ids',
        ]);

        wp_safe_redirect(admin_url('edit.php?post_type=form_submission&cta_seed_test_submissions=' . (!empty($existing_after) ? 'seeded' : 'failed')));
        exit;
    }

    wp_safe_redirect(admin_url('edit.php?post_type=form_submission&cta_seed_test_submissions=failed'));
    exit;
}
add_action('admin_post_cta_seed_test_submissions', 'cta_handle_seed_test_submissions_admin_post');

/**
 * Verify admin context and permissions
 * Use this at the start of all admin-only functions
 */
function cta_verify_admin_access($capability = 'edit_posts') {
    if (!is_admin()) {
        return false;
    }
    if (!current_user_can($capability)) {
        return false;
    }
    if (!is_user_logged_in()) {
        return false;
    }
    return true;
}

/**
 * Add CSV import submenu
 */
function cta_form_submission_csv_import_menu() {
    add_submenu_page(
        'edit.php?post_type=form_submission',
        'Import CSV',
        'Import CSV',
        'edit_posts',
        'cta-import-csv',
        'cta_form_submission_csv_import_page'
    );
}
add_action('admin_menu', 'cta_form_submission_csv_import_menu', 25);

/**
 * Add single submission submenu
 */
function cta_form_submission_add_single_menu() {
    add_submenu_page(
        'edit.php?post_type=form_submission',
        'Add Submission',
        'Add Submission',
        'edit_posts',
        'cta-add-submission',
        'cta_form_submission_add_single_page'
    );
}
add_action('admin_menu', 'cta_form_submission_add_single_menu', 26);

/**
 * Handler: add single submission
 */
function cta_handle_add_single_submission_admin_post() {
    if (!cta_verify_admin_access('edit_posts')) {
        wp_die('You do not have permission to do this.');
    }
    check_admin_referer('cta_add_single_submission');

    $name = sanitize_text_field($_POST['submission_name'] ?? '');
    $email = sanitize_email($_POST['submission_email'] ?? '');
    $phone = sanitize_text_field($_POST['submission_phone'] ?? '');
    $message = sanitize_textarea_field($_POST['submission_message'] ?? '');
    $notes = sanitize_textarea_field($_POST['submission_followup_notes'] ?? '');
    $page_url = esc_url_raw($_POST['submission_page_url'] ?? '');

    $form_type_slug = sanitize_text_field($_POST['submission_form_type'] ?? 'general');
    $marketing_consent = ($_POST['submission_marketing_consent'] ?? '') === 'yes' ? 'yes' : 'no';
    $assigned_to = sanitize_text_field($_POST['submission_assigned_to'] ?? '');
    $followup_status = sanitize_text_field($_POST['submission_followup_status'] ?? 'new');
    $email_status = sanitize_text_field($_POST['submission_email_status'] ?? '');
    $email_error = sanitize_text_field($_POST['submission_email_error'] ?? '');

    $submitted_at = sanitize_text_field($_POST['submission_submitted_at'] ?? '');
    $submitted_dt = null;
    if ($submitted_at) {
        $submitted_dt = date_create_from_format('Y-m-d\TH:i', $submitted_at, wp_timezone());
    }

    if (empty($name) && empty($email)) {
        wp_safe_redirect(admin_url('edit.php?post_type=form_submission&page=cta-add-submission&cta_add_submission=missing'));
        exit;
    }

    $post_title = $name ?: ($email ?: 'Manual Submission');
    $post_date_local = $submitted_dt ? $submitted_dt->format('Y-m-d H:i:s') : current_time('mysql');

    $post_id = wp_insert_post([
        'post_title' => $post_title,
        'post_type' => 'form_submission',
        'post_status' => 'publish',
        'post_date' => $post_date_local,
        'post_date_gmt' => get_gmt_from_date($post_date_local),
    ]);

    if (is_wp_error($post_id)) {
        wp_safe_redirect(admin_url('edit.php?post_type=form_submission&page=cta-add-submission&cta_add_submission=failed'));
        exit;
    }

    // Save meta data (align with import + admin UI expectations)
    if ($name) update_post_meta($post_id, '_submission_name', $name);
    if ($email) update_post_meta($post_id, '_submission_email', $email);
    if ($phone) update_post_meta($post_id, '_submission_phone', $phone);
    if ($message) update_post_meta($post_id, '_submission_message', $message);
    if ($notes) update_post_meta($post_id, '_submission_followup_notes', $notes);
    if ($page_url) update_post_meta($post_id, '_submission_page_url', $page_url);

    update_post_meta($post_id, '_submission_marketing_consent', $marketing_consent);

    // Optional CRM fields
    $allowed_assignees = ['AS', 'ES', 'VW', 'CR'];
    if ($assigned_to && in_array($assigned_to, $allowed_assignees, true)) {
        update_post_meta($post_id, '_submission_assigned_to', $assigned_to);
    }

    $allowed_followup = ['new', 'needed', 'contacted-email', 'contacted-phone', 'contacted-both', 'in-progress', 'booked', 'paid', 'completed', 'cancelled'];
    if ($followup_status && in_array($followup_status, $allowed_followup, true)) {
        update_post_meta($post_id, '_submission_followup_status', $followup_status);
        if ($followup_status === 'cancelled') {
            update_post_meta($post_id, '_submission_archived_date', current_time('mysql'));
        }
    }

    // Email status (matches list view filters)
    delete_post_meta($post_id, '_submission_email_sent');
    delete_post_meta($post_id, '_submission_email_error');
    if ($email_status === 'sent') {
        update_post_meta($post_id, '_submission_email_sent', 'yes');
    } elseif ($email_status === 'failed') {
        update_post_meta($post_id, '_submission_email_error', $email_error ?: 'Manually marked failed');
    }

    // Track origin for auditing
    update_post_meta($post_id, '_submission_imported', 'manual');
    update_post_meta($post_id, '_submission_imported_date', current_time('mysql'));

    // Set form type taxonomy
    if ($form_type_slug) {
        $term = get_term_by('slug', $form_type_slug, 'form_type');
        if (!$term) {
            $term = get_term_by('name', $form_type_slug, 'form_type');
        }
        if ($term) {
            wp_set_post_terms($post_id, [$term->term_id], 'form_type');
        }
    }

    wp_safe_redirect(admin_url('edit.php?post_type=form_submission&page=cta-add-submission&cta_add_submission=success&post_id=' . absint($post_id)));
    exit;
}
add_action('admin_post_cta_add_single_submission', 'cta_handle_add_single_submission_admin_post');

/**
 * Add submission page
 */
function cta_form_submission_add_single_page() {
    if (!cta_verify_admin_access('edit_posts')) {
        wp_die('You do not have permission to access this page.');
    }

    $status = isset($_GET['cta_add_submission']) ? sanitize_text_field((string) $_GET['cta_add_submission']) : '';
    $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;

    $action_url = wp_nonce_url(
        admin_url('admin-post.php?action=cta_add_single_submission'),
        'cta_add_single_submission'
    );

    $form_types = get_terms(['taxonomy' => 'form_type', 'hide_empty' => false]);
    $assignees = ['' => '—', 'AS' => 'AS', 'ES' => 'ES', 'VW' => 'VW', 'CR' => 'CR'];
    $followup_statuses = [
        'new' => 'New Enquiry',
        'needed' => 'Requires Follow-up',
        'contacted-email' => 'Contacted (Email)',
        'contacted-phone' => 'Contacted (Phone)',
        'contacted-both' => 'Contacted (Both)',
        'in-progress' => 'Interested / Enquiring',
        'booked' => 'Booked',
        'paid' => 'Booked & Paid',
        'completed' => 'Attended',
        'cancelled' => 'Cancelled',
    ];
    ?>
    
    <style>
        .cta-add-submission-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .cta-add-submission-header {
            margin-bottom: 32px;
        }
        
        .cta-add-submission-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: #1d2327;
        }
        
        .cta-form-row {
            margin-bottom: 20px;
        }
        
        .cta-form-row label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            color: #1d2327;
            font-size: 14px;
        }
        
        .cta-form-row input[type="text"],
        .cta-form-row input[type="email"],
        .cta-form-row input[type="tel"],
        .cta-form-row input[type="url"],
        .cta-form-row input[type="datetime-local"],
        .cta-form-row select,
        .cta-form-row textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.5;
            transition: border-color 0.15s ease;
        }
        
        .cta-form-row input:focus,
        .cta-form-row select:focus,
        .cta-form-row textarea:focus {
            border-color: #2271b1;
            outline: none;
            box-shadow: 0 0 0 1px #2271b1;
        }
        
        .cta-form-row textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .cta-form-section {
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .cta-form-section-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 20px 0;
            color: #1d2327;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .cta-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .cta-form-grid-full {
            grid-column: 1 / -1;
        }
        
        .cta-submit-row {
            display: flex;
            gap: 12px;
            justify-content: flex-start;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e5e5;
        }
        
        .cta-submit-btn {
            padding: 10px 32px;
            background: #4caf50;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .cta-submit-btn:hover {
            background: #45a049;
        }
        
        .cta-cancel-btn {
            padding: 10px 24px;
            background: #f0f0f1;
            color: #2c3338;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s ease;
        }
        
        .cta-cancel-btn:hover {
            background: #e5e5e7;
        }
        
        .cta-success-notice {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        
        .cta-error-notice {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        
        @media (max-width: 768px) {
            .cta-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <div class="wrap">
        <div class="cta-add-submission-container">
            <div class="cta-add-submission-header">
                <h1>New Form Submission</h1>
            </div>
            
            <?php if ($status === 'success' && $post_id) : ?>
            <div class="cta-success-notice">
                <strong>Submission added successfully.</strong>
                <a href="<?php echo esc_url(get_edit_post_link($post_id, '')); ?>">View submission</a> or
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=form_submission')); ?>">back to submissions</a>.
            </div>
            <?php elseif ($status === 'missing') : ?>
            <div class="cta-error-notice">
                <strong>Please enter at least a name or an email.</strong>
            </div>
            <?php elseif ($status === 'failed') : ?>
            <div class="cta-error-notice">
                <strong>Could not create submission.</strong> Check your PHP error log for details.
            </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo esc_url($action_url); ?>">
                
                <!-- Basic Information Section -->
                <div class="cta-form-section">
                    <h2 class="cta-form-section-title">Form Type</h2>
                    <div class="cta-form-row">
                        <label for="submission_form_type">Name</label>
                        <select name="submission_form_type" id="submission_form_type">
                            <?php if (!empty($form_types) && !is_wp_error($form_types)) : ?>
                                <?php foreach ($form_types as $term) : ?>
                                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($term->slug, 'general'); ?>>
                                        <?php echo esc_html($term->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="general" selected>General Enquiry</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Contact Details Section -->
                <div class="cta-form-grid">
                    <div class="cta-form-row">
                        <label for="submission_name">Name</label>
                        <input type="text" name="submission_name" id="submission_name" autocomplete="name">
                    </div>
                    
                    <div class="cta-form-row">
                        <label for="submission_email">Email</label>
                        <input type="email" name="submission_email" id="submission_email" autocomplete="email">
                    </div>
                    
                    <div class="cta-form-row cta-form-grid-full">
                        <label for="submission_phone">Phone</label>
                        <input type="tel" name="submission_phone" id="submission_phone" autocomplete="tel">
                    </div>
                    
                    <div class="cta-form-row cta-form-grid-full">
                        <label for="submission_message">Message</label>
                        <textarea name="submission_message" id="submission_message"></textarea>
                    </div>
                </div>
                
                <!-- Follow-up Section -->
                <div class="cta-form-section">
                    <h2 class="cta-form-section-title">Follow-up Status</h2>
                    <div class="cta-form-grid">
                        <div class="cta-form-row">
                            <label for="submission_followup_status">Status</label>
                            <select name="submission_followup_status" id="submission_followup_status">
                                <?php foreach ($followup_statuses as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($key, 'new'); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="cta-form-row">
                            <label for="submission_assigned_to">Assigned To</label>
                            <select name="submission_assigned_to" id="submission_assigned_to">
                                <?php foreach ($assignees as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Details Section -->
                <div class="cta-form-section">
                    <h2 class="cta-form-section-title">Email Template</h2>
                    <div class="cta-form-grid">
                        <div class="cta-form-row">
                            <label for="submission_email_status">Status</label>
                            <select name="submission_email_status" id="submission_email_status">
                                <option value="">Pending</option>
                                <option value="sent">Sent</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        
                        <div class="cta-form-row">
                            <label for="submission_email_error">Email Error (optional)</label>
                            <input type="text" name="submission_email_error" id="submission_email_error" placeholder="Only used when status = Failed">
                        </div>
                        
                        <div class="cta-form-row cta-form-grid-full">
                            <label for="submission_followup_notes">Follow-up Notes</label>
                            <textarea name="submission_followup_notes" id="submission_followup_notes" style="min-height: 80px;"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Metadata Section -->
                <div class="cta-form-grid">
                    <div class="cta-form-row cta-form-grid-full">
                        <label for="submission_page_url">Page URL</label>
                        <input type="url" name="submission_page_url" id="submission_page_url" placeholder="https://...">
                    </div>
                    
                    <div class="cta-form-row">
                        <label for="submission_submitted_at">Submitted At</label>
                        <input type="datetime-local" name="submission_submitted_at" id="submission_submitted_at">
                    </div>
                </div>
                
                <!-- Submit Row -->
                <div class="cta-submit-row">
                    <button type="submit" class="cta-submit-btn">Add Submission</button>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=form_submission')); ?>" class="cta-cancel-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php
}

/**
 * CSV Import page
 */
function cta_form_submission_csv_import_page() {
    // SECURITY: Verify admin access
    if (!cta_verify_admin_access('edit_posts')) {
        wp_die('You do not have permission to access this page.');
    }
    
    // Handle file upload
    if (isset($_POST['cta_import_csv']) && check_admin_referer('cta_import_csv')) {
        $file_path = isset($_POST['csv_file_path']) ? $_POST['csv_file_path'] : '';
        $mapping = isset($_POST['column_mapping']) ? $_POST['column_mapping'] : [];
        $edited_data = isset($_POST['edited_data']) ? $_POST['edited_data'] : [];
        
        if ($file_path && file_exists($file_path)) {
            $result = cta_process_csv_import($file_path, $mapping, $edited_data);
            
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>File not found. Please upload again.</p></div>';
        }
    }
    
    // Show mapping interface if file uploaded for preview
    $show_mapping = false;
    $csv_headers = [];
    $csv_preview = [];
    $csv_all_rows = [];
    
    // Check if we have stored CSV data (for page refresh)
    $stored_file = get_option('cta_csv_import_temp_file');
    if ($stored_file && file_exists($stored_file)) {
        $preview_data = cta_preview_csv($stored_file);
        if ($preview_data) {
            $show_mapping = true;
            $csv_headers = $preview_data['headers'];
            $csv_preview = $preview_data['preview'];
            $csv_all_rows = $preview_data['all_rows'] ?? [];
            update_option('cta_csv_import_all_data', $csv_all_rows, false);
        }
    }
    
    if (isset($_POST['cta_preview_csv']) && isset($_FILES['csv_file']) && check_admin_referer('cta_preview_csv')) {
        $file = $_FILES['csv_file'];
        
        // SECURITY: Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="notice notice-error"><p>File upload error.</p></div>';
        } else {
            // SECURITY: Validate file type
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($file_ext !== 'csv') {
                echo '<div class="notice notice-error"><p>Only CSV files are allowed.</p></div>';
            } else {
                // SECURITY: Use WordPress upload handler
                $uploaded_file = wp_handle_upload($file, [
                    'test_form' => false,
                    'mimes' => ['csv' => 'text/csv']
                ]);
                
                if (isset($uploaded_file['error'])) {
                    echo '<div class="notice notice-error"><p>' . esc_html($uploaded_file['error']) . '</p></div>';
                } else {
                    // SECURITY: Validate file size (10MB max)
                    if (filesize($uploaded_file['file']) > 10 * 1024 * 1024) {
                        @unlink($uploaded_file['file']);
                        echo '<div class="notice notice-error"><p>File too large. Maximum 10MB.</p></div>';
                    } else {
                        $preview_data = cta_preview_csv($uploaded_file['file']);
            if ($preview_data) {
                $show_mapping = true;
                $csv_headers = $preview_data['headers'];
                $csv_preview = $preview_data['preview'];
                $csv_all_rows = $preview_data['all_rows'] ?? [];
                            // Store validated file path
                            update_option('cta_csv_import_temp_file', $uploaded_file['file'], false);
                            // Store all CSV data for editing
                            update_option('cta_csv_import_all_data', $csv_all_rows, false);
                        }
                    }
                }
            }
        }
    }
    
    ?>
    <style>
        .cta-csv-upload-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            padding: 60px 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .cta-csv-upload-header {
            text-align: center;
            margin-bottom: 16px;
        }
        
        .cta-csv-upload-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: #1d2327;
        }
        
        .cta-csv-upload-subtitle {
            text-align: center;
            color: #646970;
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 8px 0;
        }
        
        .cta-csv-upload-filesize {
            text-align: center;
            color: #646970;
            font-size: 13px;
            margin: 0 0 40px 0;
        }
        
        .cta-csv-dropzone {
            border: 2px dashed #c3c4c7;
            border-radius: 8px;
            padding: 60px 40px;
            text-align: center;
            background: #f9f9f9;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
        }
        
        .cta-csv-dropzone:hover {
            border-color: #2271b1;
            background: #f0f6fc;
        }
        
        .cta-csv-dropzone.dragover {
            border-color: #2271b1;
            background: #e5f2ff;
        }
        
        .cta-csv-upload-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #72a5d8 0%, #5b9dd8 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .cta-csv-upload-icon::before {
            content: '';
            position: absolute;
            width: 60px;
            height: 40px;
            background: #fff;
            border-radius: 50% 50% 0 0;
            top: 15px;
        }
        
        .cta-csv-upload-icon::after {
            content: '↓';
            position: absolute;
            font-size: 32px;
            color: #72a5d8;
            font-weight: bold;
            top: 28px;
            z-index: 1;
        }
        
        .cta-csv-dropzone h2 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 16px 0;
            color: #1d2327;
        }
        
        .cta-csv-browse-btn {
            display: inline-block;
            padding: 10px 24px;
            background: #72a5d8;
            color: #fff;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .cta-csv-browse-btn:hover {
            background: #5b9dd8;
        }
        
        .cta-csv-upload-note {
            text-align: center;
            color: #646970;
            font-size: 13px;
            margin-top: 32px;
            line-height: 1.6;
        }
        
        .cta-csv-upload-note a {
            color: #2271b1;
            text-decoration: none;
        }
        
        .cta-csv-upload-note a:hover {
            text-decoration: underline;
        }
        
        input[type="file"]#csv_file {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            cursor: pointer;
        }
        
        .cta-csv-file-selected {
            margin-top: 16px;
            padding: 12px 16px;
            background: #e5f2ff;
            border: 1px solid #2271b1;
            border-radius: 6px;
            color: #1d2327;
            font-size: 14px;
            display: none;
        }
        
        .cta-csv-file-selected.show {
            display: block;
        }
    </style>
    
    <div class="wrap">
        <?php if (!$show_mapping) : ?>
        <div class="cta-csv-upload-container">
            <div class="cta-csv-upload-header">
                <h1>Import Leads from CSV</h1>
            </div>
            
            <p class="cta-csv-upload-subtitle">
                Upload a CSV file from a CRM, Google, or marketing tool. Supports<br>
                data from Mailchimp, etc.
            </p>
            <p class="cta-csv-upload-filesize">
                Maximum file size <?php echo size_format(wp_max_upload_size()); ?>.
            </p>
            
            <form method="post" enctype="multipart/form-data" action="" id="cta-csv-upload-form">
                <?php wp_nonce_field('cta_preview_csv'); ?>
                
                <div class="cta-csv-dropzone" id="cta-dropzone">
                    <div class="cta-csv-upload-icon"></div>
                    <h2>Upload CSV file</h2>
                    <button type="button" class="cta-csv-browse-btn" onclick="document.getElementById('csv_file').click();">
                        Browse files
                    </button>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                </div>
                
                <div class="cta-csv-file-selected" id="file-selected-msg">
                    <strong>File selected:</strong> <span id="file-name"></span>
                </div>
                
                <p class="cta-csv-upload-note">
                    Use the preview to check columns and help map them. Note: <a href="#" onclick="alert('CSV files should have headers in the first row. Common columns: Name, Email, Phone, Company, etc.'); return false;">CSV format guide</a>
                </p>
                
                <p class="submit" style="text-align: center; margin-top: 32px;">
                    <button type="submit" name="cta_preview_csv" class="button button-primary button-large" style="display: none;" id="preview-btn">
                        Preview & Map Columns
                    </button>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var dropzone = $('#cta-dropzone');
            var fileInput = $('#csv_file');
            var fileSelectedMsg = $('#file-selected-msg');
            var fileName = $('#file-name');
            var previewBtn = $('#preview-btn');
            
            // File input change
            fileInput.on('change', function() {
                if (this.files && this.files[0]) {
                    fileName.text(this.files[0].name);
                    fileSelectedMsg.addClass('show');
                    previewBtn.show();
                }
            });
            
            // Drag and drop
            dropzone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });
            
            dropzone.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });
            
            dropzone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    fileInput[0].files = files;
                    fileName.text(files[0].name);
                    fileSelectedMsg.addClass('show');
                    previewBtn.show();
                }
            });
        });
        </script>
        <?php else : ?>
        <div class="postbox">
            <div class="postbox-header"><h2>Step 2: Map Columns</h2></div>
            <div class="inside">
                <p class="description">Map your CSV columns to the form submission fields. The system has automatically detected likely matches.</p>
                
                <form method="post" enctype="multipart/form-data" action="">
                    <?php wp_nonce_field('cta_import_csv'); ?>
                    <input type="hidden" name="csv_file_path" value="<?php echo esc_attr(get_option('cta_csv_import_temp_file')); ?>">
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>CSV Column</th>
                                <th>Sample Data</th>
                                <th>Map To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $field_mappings = [
                                'name' => 'Name',
                                'email' => 'Email',
                                'phone' => 'Phone',
                                'message' => 'Message',
                                'notes' => 'Notes',
                                'form_type' => 'Form Type',
                                'page_url' => 'Page URL',
                                'marketing_consent' => 'Marketing Consent',
                                'created_date' => 'Created Date',
                            ];
                            
                            foreach ($csv_headers as $index => $header) :
                                $sample = isset($csv_preview[0][$index]) ? $csv_preview[0][$index] : '';
                                $auto_mapped = cta_auto_detect_column($header, $sample);
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($header); ?></strong></td>
                                <td><code><?php echo esc_html(wp_trim_words($sample, 10)); ?></code></td>
                                <td>
                                    <select name="column_mapping[<?php echo esc_attr($index); ?>]" class="postform">
                                        <option value="">Skip This Column</option>
                                        <?php foreach ($field_mappings as $key => $label) : ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($auto_mapped, $key); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                
                <script>
                (function($) {
                    'use strict';
                    
                    $(document).ready(function() {
                        // Apply bulk edit to a single column
                        $('.cta-bulk-apply-btn').on('click', function() {
                            var $btn = $(this);
                            var columnIndex = $btn.data('column-index');
                            var $field = $('#bulk-edit-' + columnIndex);
                            var value = $field.val().trim();
                            
                            if (!value) {
                                alert('Please enter a value to apply.');
                                return;
                            }
                            
                            // Find all cells in this column and update them
                            $('#cta-editable-csv-table tbody tr').each(function() {
                                var $row = $(this);
                                var $cell = $row.find('td').eq(parseInt(columnIndex) + 1); // +1 because first column is row number
                                
                                if ($cell.length) {
                                    var $input = $cell.find('input, textarea');
                                    if ($input.length) {
                                        $input.val(value);
                                        $input.trigger('change');
                                    }
                                }
                            });
                            
                            // Show feedback
                            $btn.text('Applied!').css('background-color', '#00a32a').css('color', '#fff');
                            setTimeout(function() {
                                $btn.text('Apply to All Rows').css('background-color', '').css('color', '');
                            }, 2000);
                        });
                        
                        // Apply all bulk edits at once
                        $('.cta-bulk-apply-all').on('click', function() {
                            var $btn = $(this);
                            var applied = 0;
                            
                            $('.cta-bulk-edit-field').each(function() {
                                var $field = $(this);
                                var value = $field.val().trim();
                                
                                if (value) {
                                    var columnIndex = $field.data('column-index');
                                    
                                    $('#cta-editable-csv-table tbody tr').each(function() {
                                        var $row = $(this);
                                        var $cell = $row.find('td').eq(parseInt(columnIndex) + 1);
                                        
                                        if ($cell.length) {
                                            var $input = $cell.find('input, textarea');
                                            if ($input.length) {
                                                $input.val(value);
                                                $input.trigger('change');
                                            }
                                        }
                                    });
                                    
                                    applied++;
                                }
                            });
                            
                            if (applied === 0) {
                                alert('Please enter at least one value to apply.');
                                return;
                            }
                            
                            // Show feedback
                            $btn.text('Applied ' + applied + ' value(s)!').css('background-color', '#00a32a').css('color', '#fff');
                            setTimeout(function() {
                                $btn.text('Apply All Values to All Rows').css('background-color', '').css('color', '');
                            }, 2000);
                        });
                        
                        // Clear all bulk edit fields
                        $('.cta-bulk-clear-all').on('click', function() {
                            if (confirm('Clear all bulk edit fields?')) {
                                $('.cta-bulk-edit-field').val('');
                            }
                        });
                    });
                })(jQuery);
                </script>
                
                <p class="submit">
                    <button type="submit" name="cta_import_csv" class="button button-primary">Import Leads</button>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=form_submission&page=cta-import-csv')); ?>" class="button button-secondary">Cancel</a>
                </p>
            </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Auto-detect column mapping based on header name and sample data
 */
function cta_auto_detect_column($header, $sample) {
    $header_lower = strtolower($header);
    $sample_lower = strtolower($sample);
    
    // Name detection
    if (preg_match('/name|full.?name|contact.?name|first.?name|last.?name/i', $header)) {
        return 'name';
    }
    
    // Email detection
    if (preg_match('/email|e.?mail|mail/i', $header) || is_email($sample)) {
        return 'email';
    }
    
    // Phone detection
    if (preg_match('/phone|mobile|tel|telephone|number/i', $header) || preg_match('/[\d\s\+\-\(\)]{10,}/', $sample)) {
        return 'phone';
    }
    
    // Notes detection (check before message to prioritize notes)
    if (preg_match('/notes|note|followup|follow.?up/i', $header)) {
        return 'notes';
    }
    
    // Message detection
    if (preg_match('/message|comment|description|enquiry|inquiry/i', $header)) {
        return 'message';
    }
    
    // Form type detection
    if (preg_match('/form.?type|enquiry.?type|source|lead.?type/i', $header)) {
        return 'form_type';
    }
    
    // Page URL detection
    if (preg_match('/url|link|page|source.?url|referrer/i', $header) || filter_var($sample, FILTER_VALIDATE_URL)) {
        return 'page_url';
    }
    
    // Marketing consent detection
    if (preg_match('/consent|marketing|subscribe|newsletter|opt.?in/i', $header)) {
        return 'marketing_consent';
    }
    
    // Date detection
    if (preg_match('/date|time|created|submitted|timestamp/i', $header) || strtotime($sample) !== false) {
        return 'created_date';
    }
    
    return '';
}

/**
 * Preview CSV file
 */
function cta_preview_csv($file_path) {
    // SECURITY: Check user has permission
    if (!current_user_can('edit_posts')) {
        return false;
    }
    
    // SECURITY: Validate file path
    if (!file_exists($file_path)) {
        return false;
    }
    
    // SECURITY: Validate file is within uploads directory
    $upload_dir = wp_upload_dir();
    $allowed_dir = $upload_dir['path'];
    $real_file_path = realpath($file_path);
    $real_allowed_dir = realpath($allowed_dir);
    
    if (!$real_file_path || !$real_allowed_dir || strpos($real_file_path, $real_allowed_dir) !== 0) {
        return false;
    }
    
    // SECURITY: Validate file extension
    $file_ext = strtolower(pathinfo($real_file_path, PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        return false;
    }
    
    // SECURITY: Limit file size for preview (10MB max)
    if (filesize($real_file_path) > 10 * 1024 * 1024) {
        return false;
    }
    
    $handle = fopen($real_file_path, 'r');
    if ($handle === false) {
        return false;
    }
    
    // Detect delimiter
    $first_line = fgets($handle);
    rewind($handle);
    $delimiter = cta_detect_csv_delimiter($first_line);
    
    // Read headers
    $headers = fgetcsv($handle, 0, $delimiter);
    if ($headers === false) {
        fclose($handle);
        return false;
    }
    
    // Read preview rows (first 5)
    $preview = [];
    $all_rows = [];
    $row_count = 0;
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $all_rows[] = $row;
        if ($row_count < 5) {
            $preview[] = $row;
        }
        $row_count++;
    }
    
    fclose($handle);
    
    return [
        'headers' => $headers,
        'preview' => $preview,
        'all_rows' => $all_rows,
    ];
}

/**
 * Detect CSV delimiter
 */
function cta_detect_csv_delimiter($line) {
    $delimiters = [',', ';', "\t", '|'];
    $max_count = 0;
    $detected = ',';
    
    foreach ($delimiters as $delimiter) {
        $count = substr_count($line, $delimiter);
        if ($count > $max_count) {
            $max_count = $count;
            $detected = $delimiter;
        }
    }
    
    return $detected;
}

/**
 * Process CSV import
 */
function cta_process_csv_import($file_path, $mapping, $edited_data = []) {
    // SECURITY: Check user has permission
    if (!current_user_can('edit_posts')) {
        return ['success' => false, 'message' => 'Insufficient permissions'];
    }
    
    // SECURITY: Validate file path is in uploads directory
    $upload_dir = wp_upload_dir();
    $allowed_dir = $upload_dir['path'];
    
    // Resolve file path
    if (!file_exists($file_path)) {
        $file_path = get_option('cta_csv_import_temp_file');
    }
    
    if (!$file_path || !file_exists($file_path)) {
        return ['success' => false, 'message' => 'File not found. Please upload again.'];
    }
    
    // SECURITY: Ensure file is within uploads directory (prevent path traversal)
    $real_file_path = realpath($file_path);
    $real_allowed_dir = realpath($allowed_dir);
    
    if (!$real_file_path || !$real_allowed_dir || strpos($real_file_path, $real_allowed_dir) !== 0) {
        return ['success' => false, 'message' => 'Invalid file path'];
    }
    
    // SECURITY: Validate file extension
    $file_ext = strtolower(pathinfo($real_file_path, PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // SECURITY: Limit file size (10MB max)
    if (filesize($real_file_path) > 10 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File too large. Maximum 10MB.'];
    }
    
    $handle = fopen($real_file_path, 'r');
    if ($handle === false) {
        return ['success' => false, 'message' => 'Could not read file.'];
    }
    
    // Detect delimiter
    $first_line = fgets($handle);
    rewind($handle);
    $delimiter = cta_detect_csv_delimiter($first_line);
    
    // Skip header row
    $headers = fgetcsv($handle, 0, $delimiter);
    
    $imported = 0;
    $skipped = 0;
    $errors = [];
    $row_index = 0;
    
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $data = [];
        
        // Check if this row has edited data
        $has_edited_data = isset($edited_data[$row_index]);
        
        // Map columns - use edited data if available, otherwise use original CSV data
        foreach ($mapping as $index => $field) {
            if ($field) {
                // Check for edited data first
                if ($has_edited_data && isset($edited_data[$row_index][$index])) {
                    $value = trim($edited_data[$row_index][$index]);
                    if ($value !== '') {
                        $data[$field] = $value;
                    } elseif (isset($row[$index])) {
                        // Fall back to original if edited value is empty
                        $data[$field] = trim($row[$index]);
                    }
                } elseif (isset($row[$index])) {
                    // Use original CSV data
                    $data[$field] = trim($row[$index]);
                }
            }
        }
        
        // Skip if no name or email
        if (empty($data['name']) && empty($data['email'])) {
            $skipped++;
            $row_index++;
            continue;
        }
        
        // Create submission
        $result = cta_create_submission_from_import($data);
        
        if ($result['success']) {
            $imported++;
        } else {
            $errors[] = $result['message'];
            $skipped++;
        }
        
        $row_index++;
    }
    
    fclose($handle);
    
    // SECURITY: Clean up temp file using validated path
    if (file_exists($real_file_path)) {
        @unlink($real_file_path);
    }
    delete_option('cta_csv_import_temp_file');
    
    $message = sprintf(
        'Import complete! %d leads imported, %d skipped.',
        $imported,
        $skipped
    );
    
    if (!empty($errors) && count($errors) <= 5) {
        $message .= ' Errors: ' . implode(', ', array_slice($errors, 0, 5));
    }
    
    return ['success' => true, 'message' => $message, 'imported' => $imported, 'skipped' => $skipped];
}

/**
 * Create form submission from imported data
 */
function cta_create_submission_from_import($data) {
    $name = sanitize_text_field($data['name'] ?? '');
    $email = sanitize_email($data['email'] ?? '');
    $phone = sanitize_text_field($data['phone'] ?? '');
    $message = sanitize_textarea_field($data['message'] ?? '');
    $notes = sanitize_textarea_field($data['notes'] ?? '');
    $form_type_slug = sanitize_text_field($data['form_type'] ?? 'general');
    $page_url = esc_url_raw($data['page_url'] ?? '');
    $marketing_consent = isset($data['marketing_consent']) && in_array(strtolower($data['marketing_consent']), ['yes', 'true', '1', 'on']) ? 'yes' : 'no';
    $created_date = !empty($data['created_date']) ? strtotime($data['created_date']) : current_time('timestamp');
    
    if (empty($name) && empty($email)) {
        return ['success' => false, 'message' => 'Missing name and email'];
    }
    
    // Create post
    $post_data = [
        'post_title' => $name ?: $email ?: 'Imported Lead',
        'post_type' => 'form_submission',
        'post_status' => 'publish',
        'post_date' => date('Y-m-d H:i:s', $created_date),
    ];
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        return ['success' => false, 'message' => $post_id->get_error_message()];
    }
    
    // Save meta data
    if ($name) update_post_meta($post_id, '_submission_name', $name);
    if ($email) update_post_meta($post_id, '_submission_email', $email);
    if ($phone) update_post_meta($post_id, '_submission_phone', $phone);
    if ($message) update_post_meta($post_id, '_submission_message', $message);
    if ($notes) update_post_meta($post_id, '_submission_followup_notes', $notes);
    if ($page_url) update_post_meta($post_id, '_submission_page_url', $page_url);
    update_post_meta($post_id, '_submission_marketing_consent', $marketing_consent);
    update_post_meta($post_id, '_submission_imported', 'yes');
    update_post_meta($post_id, '_submission_imported_date', current_time('mysql'));
    
    // Set form type taxonomy
    if ($form_type_slug) {
        $term = get_term_by('slug', $form_type_slug, 'form_type');
        if (!$term) {
            // Try to find by name
            $term = get_term_by('name', $form_type_slug, 'form_type');
        }
        if ($term) {
            wp_set_post_terms($post_id, [$term->term_id], 'form_type');
        } else {
            // Default to general
            $general_term = get_term_by('slug', 'general', 'form_type');
            if ($general_term) {
                wp_set_post_terms($post_id, [$general_term->term_id], 'form_type');
            }
        }
    }
    
    return ['success' => true, 'post_id' => $post_id];
}

/**
 * Clean up admin menu - no extra submenu items
 */
function cta_form_submission_admin_menu_sections() {
    global $submenu;
    
    // Just rename the main menu item
    if (isset($submenu['edit.php?post_type=form_submission'])) {
        $base_slug = 'edit.php?post_type=form_submission';
        
        foreach ($submenu[$base_slug] as $key => $item) {
            if ($item[2] === $base_slug) {
                $submenu[$base_slug][$key][0] = 'All Submissions';
                break;
            }
        }
    }
}
add_action('admin_menu', 'cta_form_submission_admin_menu_sections', 20);

/**
 * Customize admin columns for Form Submissions
 * Show different columns based on active tab
 */
function cta_form_submission_admin_columns($columns) {
    // SECURITY: Only in admin
    if (!is_admin()) {
        return $columns;
    }
    
    // Get active tab
    $selected_tab = isset($_GET['submission_tab']) ? sanitize_text_field($_GET['submission_tab']) : 'all';
    
    // Remove irrelevant columns
    $irrelevant_columns = [
        'seo',
        'image',
        'form_types', // Duplicate of form_type
        'status',
        'author',
        'comments',
    ];
    
    $new_columns = [];
    
    // Add checkbox first
    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
    }
    
    // Add Name (title) - always shown
    $new_columns['title'] = 'Name';
    
    // Tab-specific columns to show relevant data
    switch ($selected_tab) {
        case 'event-bookings':
            $new_columns['contact_info'] = 'Contact';
            $new_columns['course_name'] = 'Course';
            $new_columns['event_date'] = 'Event Date';
            $new_columns['delegates'] = 'Delegates';
            $new_columns['followup_status'] = 'Status';
            $new_columns['assigned_to'] = 'Assigned';
            $new_columns['marketing_consent'] = 'Marketing Consent';
            $new_columns['email_status'] = 'Email Status';
            $new_columns['date'] = 'Submitted';
            break;
            
        case 'course-enquiries':
            $new_columns['contact_info'] = 'Contact';
            $new_columns['course_name'] = 'Course';
            $new_columns['followup_status'] = 'Status';
            $new_columns['assigned_to'] = 'Assigned';
            $new_columns['marketing_consent'] = 'Marketing Consent';
            $new_columns['email_status'] = 'Email Status';
            $new_columns['date'] = 'Submitted';
            break;
            
        case 'group-enquiries':
            $new_columns['contact_info'] = 'Contact';
            $new_columns['course_name'] = 'Training Type';
            $new_columns['delegates'] = 'Staff Count';
            $new_columns['followup_status'] = 'Status';
            $new_columns['assigned_to'] = 'Assigned';
            $new_columns['marketing_consent'] = 'Marketing Consent';
            $new_columns['email_status'] = 'Email Status';
            $new_columns['date'] = 'Submitted';
            break;
            
        case 'newsletter':
            $new_columns['contact_info'] = 'Email';
            $new_columns['newsletter_status'] = 'Subscription';
            $new_columns['marketing_consent'] = 'Marketing Consent';
            $new_columns['email_status'] = 'Email Status';
            $new_columns['date'] = 'Subscribed';
            break;
            
        case 'other':
            $new_columns['form_type'] = 'Type';
            $new_columns['contact_info'] = 'Contact';
            $new_columns['followup_status'] = 'Status';
            $new_columns['assigned_to'] = 'Assigned';
            $new_columns['marketing_consent'] = 'Marketing Consent';
            $new_columns['email_status'] = 'Email Status';
            $new_columns['date'] = 'Submitted';
            break;
            
        default:
            // All types - generic view
            $new_columns['form_type'] = 'Form Type';
            $new_columns['contact_info'] = 'Contact';
            $new_columns['followup_status'] = 'Status';
            $new_columns['assigned_to'] = 'Assigned';
            $new_columns['marketing_consent'] = 'Marketing Consent';
            $new_columns['email_status'] = 'Email Status';
            $new_columns['date'] = 'Submitted';
            break;
    }
    
    return $new_columns;
}
add_filter('manage_form_submission_posts_columns', 'cta_form_submission_admin_columns');

/**
 * Populate custom admin columns for Form Submissions
 */
function cta_form_submission_admin_column_content($column, $post_id) {
    // SECURITY: Check admin context and capability
    if (!is_admin() || !current_user_can('edit_posts')) {
        return;
    }
    
    switch ($column) {
        case 'form_type':
            $terms = get_the_terms($post_id, 'form_type');
            if ($terms && !is_wp_error($terms)) {
                $term_names = array_map(function($term) {
                    return esc_html($term->name);
                }, $terms);
                echo implode(', ', $term_names);
            } else {
                echo '-';
            }
            break;
            
        case 'contact_info':
            // SECURITY: Verify user can edit this specific post before showing sensitive data
            if (!current_user_can('edit_post', $post_id)) {
                echo '<span class="cta-admin-empty">-</span>';
                break;
            }
            
            $email = get_post_meta($post_id, '_submission_email', true);
            $phone = get_post_meta($post_id, '_submission_phone', true);
            
            // Get form type to tailor display
            $form_type_terms = get_the_terms($post_id, 'form_type');
            $form_type_slug = $form_type_terms && !is_wp_error($form_type_terms) ? $form_type_terms[0]->slug : '';
            
            // Check if we're in a tab-specific view (compact mode)
            $selected_tab = isset($_GET['submission_tab']) ? sanitize_text_field($_GET['submission_tab']) : 'all';
            $is_compact = $selected_tab !== 'all';
            
            $info_parts = [];
            
            // Email - show for all except newsletter (which might not have it)
            if ($email && $form_type_slug !== 'newsletter') {
                // Truncate long emails more aggressively in compact mode
                $max_length = $is_compact ? 25 : 30;
                $display_email = strlen($email) > $max_length ? substr($email, 0, $max_length - 3) . '...' : $email;
                $email_html = '<div>';
                $email_html .= '<a href="mailto:' . esc_attr($email) . '" title="' . esc_attr($email) . '">' . esc_html($display_email) . '</a>';
                
                // Only show newsletter badge in full view (not compact)
                if (!$is_compact) {
                    // Check newsletter subscription status
                    $newsletter_status = get_post_meta($post_id, '_submission_newsletter_status', true);
                    $subscriber_id = get_post_meta($post_id, '_submission_newsletter_subscriber_id', true);
                    
                    if ($newsletter_status || $subscriber_id) {
                        $status_labels = [
                            'added' => ['text' => 'New Subscriber', 'class' => 'cta-newsletter-new', 'color' => '#00a32a'],
                            'reactivated' => ['text' => 'Reactivated', 'class' => 'cta-newsletter-reactivated', 'color' => '#2271b1'],
                            'exists' => ['text' => 'Already Subscribed', 'class' => 'cta-newsletter-exists', 'color' => '#646970'],
                            'exists_no_consent' => ['text' => 'In Newsletter', 'class' => 'cta-newsletter-exists', 'color' => '#646970'],
                        ];
                        
                        $status_info = $status_labels[$newsletter_status] ?? ['text' => 'In Newsletter', 'class' => 'cta-newsletter-exists', 'color' => '#646970'];
                        $badge_class = $status_info['class'];
                        $badge_text = $status_info['text'];
                        $badge_color = $status_info['color'];
                        
                        if ($subscriber_id) {
                            $newsletter_url = admin_url('admin.php?page=cta-newsletter');
                            $email_html .= ' <a href="' . esc_url($newsletter_url) . '" title="View in Newsletter Subscribers">' . esc_html($badge_text) . '</a>';
                        } else {
                            $email_html .= ' <span title="Newsletter subscription status">' . esc_html($badge_text) . '</span>';
                        }
                    }
                }
                
                $email_html .= '</div>';
                $info_parts[] = $email_html;
            }
            
            // Phone - show for most forms, but not newsletter
            if ($phone && $form_type_slug !== 'newsletter') {
                $info_parts[] = '<div><a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $phone)) . '" title="' . esc_attr($phone) . '">' . esc_html($phone) . '</a></div>';
            }
            
            // Only show course name in full view (not in tab-specific views where it has its own column)
            if (!$is_compact) {
                // For course bookings, show course name if available
                if ($form_type_slug === 'course-booking') {
                    $course_name = get_post_meta($post_id, '_submission_form_data', true);
                    if (is_array($course_name) && isset($course_name['course_name']) && $course_name['course_name']) {
                        $info_parts[] = '<div style="font-size: 11px; color: #646970; margin-top: 4px;"><span class="cta-admin-meta-label">Course:</span> <span style="font-size: 12px;">' . esc_html($course_name['course_name']) . '</span></div>';
                    }
                }
                
                // For group bookings, show organisation
                if ($form_type_slug === 'group-booking') {
                    $form_data = get_post_meta($post_id, '_submission_form_data', true);
                    if (is_array($form_data) && isset($form_data['organisation']) && $form_data['organisation']) {
                        $info_parts[] = '<div style="font-size: 11px; color: #646970; margin-top: 4px;"><span class="cta-admin-meta-label">Org:</span> <span style="font-size: 12px;">' . esc_html($form_data['organisation']) . '</span></div>';
                    }
                }
            }
            
            echo $info_parts ? '<div class="cta-admin-contact-info" style="line-height: ' . ($is_compact ? '1.4' : '1.5') . ';">' . implode('', $info_parts) . '</div>' : '<span class="cta-admin-empty">-</span>';
            break;
            
        case 'assigned_to':
            $assigned_to = get_post_meta($post_id, '_submission_assigned_to', true);
            $assignees = ['AS', 'ES', 'VW', 'CR'];
            
            echo '<div class="cta-assigned-to-wrapper" data-post-id="' . esc_attr($post_id) . '">';
            echo '<select class="cta-assigned-to-select" data-post-id="' . esc_attr($post_id) . '">';
            echo '<option value="">-</option>';
            foreach ($assignees as $assignee) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($assignee),
                    selected($assigned_to, $assignee, false),
                    esc_html($assignee)
                );
            }
            echo '</select>';
            echo '</div>';
            break;
            
        case 'followup_status':
            $followup_status = get_post_meta($post_id, '_submission_followup_status', true);
            $followup_notes = get_post_meta($post_id, '_submission_followup_notes', true);
            $email_sent = get_post_meta($post_id, '_submission_email_sent', true);
            $email_error = get_post_meta($post_id, '_submission_email_error', true);
            
            // Status labels mapping (training course appropriate)
            $status_labels = [
                'new' => ['label' => 'New Enquiry', 'class' => 'cta-status-new'],
                'needed' => ['label' => 'Requires Follow-up', 'class' => 'cta-status-needed'],
                'contacted-email' => ['label' => 'Contacted (Email)', 'class' => 'cta-status-contacted'],
                'contacted-phone' => ['label' => 'Contacted (Phone)', 'class' => 'cta-status-contacted'],
                'contacted-both' => ['label' => 'Contacted (Both)', 'class' => 'cta-status-contacted'],
                'in-progress' => ['label' => 'Interested / Enquiring', 'class' => 'cta-status-progress'],
                'booked' => ['label' => 'Booked', 'class' => 'cta-status-booked'],
                'paid' => ['label' => 'Booked & Paid', 'class' => 'cta-status-paid'],
                'completed' => ['label' => 'Attended', 'class' => 'cta-status-completed'],
                'cancelled' => ['label' => 'Cancelled', 'class' => 'cta-status-cancelled'],
            ];
            
            $current_status = $status_labels[$followup_status] ?? null;
            
            echo '<div class="cta-followup-status-wrapper" data-post-id="' . esc_attr($post_id) . '" style="display: flex; align-items: center; gap: 6px;">';
            echo '<select class="cta-followup-status-select" data-post-id="' . esc_attr($post_id) . '">';
            echo '<option value="">No Status</option>';
            foreach ($status_labels as $key => $status) {
                printf(
                    '<option value="%s"%s class="%s">%s</option>',
                    esc_attr($key),
                    selected($followup_status, $key, false),
                    esc_attr($status['class']),
                    esc_html($status['label'])
                );
            }
            echo '</select>';
            
            // Add email retry button next to status
            if ($email_error) {
                echo '<button type="button" class="button-link cta-resend-email" data-post-id="' . esc_attr($post_id) . '" title="Retry sending email - ' . esc_attr($email_error) . '" style="color: #d63638; padding: 0; margin: 0; line-height: 1;"><span class="dashicons dashicons-update" style="font-size: 18px; width: 18px; height: 18px;"></span></button>';
            } elseif ($email_sent !== 'yes' && $email_sent !== '1') {
                echo '<button type="button" class="button-link cta-resend-email" data-post-id="' . esc_attr($post_id) . '" title="Send notification now" style="color: #2271b1; padding: 0; margin: 0; line-height: 1;"><span class="dashicons dashicons-update" style="font-size: 18px; width: 18px; height: 18px;"></span></button>';
            }
            
            // Show notes preview/quick edit
            echo '<div class="cta-followup-notes-quick">';
            if ($followup_notes) {
                echo '<div class="cta-notes-preview" title="Click to edit">';
                echo esc_html(wp_trim_words($followup_notes, 15));
                echo '</div>';
            } else {
                echo '<button type="button" class="button-link cta-add-note-btn" data-post-id="' . esc_attr($post_id) . '">+ Add note</button>';
            }
            echo '</div>';
            echo '</div>';
            break;
            
        case 'marketing_consent':
            $consent = get_post_meta($post_id, '_submission_marketing_consent', true);
            if ($consent === 'yes' || $consent === 'on' || $consent === '1') {
                echo '<span class="cta-admin-consent-yes">Yes</span>';
            } else {
                echo '<span class="cta-admin-consent-no">No</span>';
            }
            break;
            
        case 'email_status':
            $email_sent = get_post_meta($post_id, '_submission_email_sent', true);
            $email_error = get_post_meta($post_id, '_submission_email_error', true);
            
            echo '<div class="cta-email-status-wrapper" style="display: flex; align-items: center; gap: 6px;">';
            if ($email_sent === 'yes' || $email_sent === '1') {
                echo '<span class="cta-admin-email-sent">Sent</span>';
            } elseif ($email_error) {
                echo '<span class="cta-admin-email-failed" title="' . esc_attr($email_error) . '">Failed</span>';
                echo '<button type="button" class="button-link cta-resend-email" data-post-id="' . esc_attr($post_id) . '" title="Retry sending email" style="color: #d63638; padding: 0; margin: 0; line-height: 1;"><span class="dashicons dashicons-update" style="font-size: 18px; width: 18px; height: 18px;"></span></button>';
            } else {
                echo '<span class="cta-admin-email-pending">Pending</span>';
                echo '<button type="button" class="button-link cta-resend-email" data-post-id="' . esc_attr($post_id) . '" title="Send notification now" style="color: #2271b1; padding: 0; margin: 0; line-height: 1;"><span class="dashicons dashicons-update" style="font-size: 18px; width: 18px; height: 18px;"></span></button>';
            }
            echo '</div>';
            break;
            
        case 'course_name':
            // Get course name from form data or course_id
            $course_name = get_post_meta($post_id, '_submission_course_name', true);
            if (!$course_name) {
                $form_data = get_post_meta($post_id, '_submission_form_data', true);
                if (is_array($form_data) && isset($form_data['course_name'])) {
                    $course_name = $form_data['course_name'];
                }
            }
            
            if ($course_name) {
                $course_id = get_post_meta($post_id, '_submission_course_id', true);
                if ($course_id) {
                    $course_url = get_permalink($course_id);
                    echo '<a href="' . esc_url($course_url) . '" target="_blank" title="View course">' . esc_html($course_name) . '</a>';
                } else {
                    echo esc_html($course_name);
                }
            } else {
                echo '<span class="cta-admin-empty">-</span>';
            }
            break;
            
        case 'event_date':
            // Get event date from form data or event_date meta
            $event_date = get_post_meta($post_id, '_submission_event_date', true);
            if (!$event_date) {
                $form_data = get_post_meta($post_id, '_submission_form_data', true);
                if (is_array($form_data) && isset($form_data['event_date'])) {
                    $event_date = $form_data['event_date'];
                }
            }
            
            if ($event_date) {
                // Format date nicely
                $date_obj = strtotime($event_date);
                if ($date_obj) {
                    $formatted_date = date('j M Y', $date_obj);
                    $today = strtotime('today');
                    $tomorrow = strtotime('tomorrow');
                    
                    // Highlight upcoming events
                    if ($date_obj >= $today && $date_obj < $tomorrow) {
                        echo '<span style="color: #d63638; font-weight: 600;">Today</span>';
                    } elseif ($date_obj >= $tomorrow && $date_obj <= strtotime('+7 days')) {
                        echo '<span style="color: #2271b1; font-weight: 500;">' . esc_html($formatted_date) . '</span>';
                    } elseif ($date_obj < $today) {
                        echo '<span style="color: #646970;">' . esc_html($formatted_date) . '</span>';
                    } else {
                        echo esc_html($formatted_date);
                    }
                } else {
                    echo esc_html($event_date);
                }
            } else {
                echo '<span class="cta-admin-empty">-</span>';
            }
            break;
            
        case 'delegates':
            // Get delegates count from form data
            $form_data = get_post_meta($post_id, '_submission_form_data', true);
            $delegates = '';
            if (is_array($form_data) && isset($form_data['delegates'])) {
                $delegates = $form_data['delegates'];
            }
            
            if ($delegates) {
                echo '<span style="font-weight: 500;">' . esc_html($delegates) . '</span>';
            } else {
                echo '<span class="cta-admin-empty">-</span>';
            }
            break;
            
        case 'message':
            // Get message from submission
            $message = get_post_meta($post_id, '_submission_message', true);
            
            if ($message) {
                $truncated = wp_trim_words($message, 15);
                $full_message = esc_html($message);
                $display = esc_html($truncated);
                
                if (strlen($message) > strlen($truncated)) {
                    echo '<span title="' . esc_attr($full_message) . '" style="cursor: help;">' . $display . '...</span>';
                } else {
                    echo $display;
                }
            } else {
                echo '<span class="cta-admin-empty">-</span>';
            }
            break;
            
        case 'newsletter_status':
            // Show newsletter subscription status
            $newsletter_status = get_post_meta($post_id, '_submission_newsletter_status', true);
            $subscriber_id = get_post_meta($post_id, '_submission_newsletter_subscriber_id', true);
            
            $status_labels = [
                'added' => ['text' => 'New Subscriber', 'class' => 'cta-newsletter-new', 'color' => '#00a32a'],
                'reactivated' => ['text' => 'Reactivated', 'class' => 'cta-newsletter-reactivated', 'color' => '#2271b1'],
                'exists' => ['text' => 'Already Subscribed', 'class' => 'cta-newsletter-exists', 'color' => '#646970'],
                'exists_no_consent' => ['text' => 'In Newsletter', 'class' => 'cta-newsletter-exists', 'color' => '#646970'],
            ];
            
            if ($newsletter_status || $subscriber_id) {
                $status_info = $status_labels[$newsletter_status] ?? ['text' => 'Subscribed', 'class' => 'cta-newsletter-exists', 'color' => '#646970'];
                $badge_class = $status_info['class'];
                $badge_text = $status_info['text'];
                $badge_color = $status_info['color'];
                
                $badge_style = 'font-size: 11px; padding: 4px 8px; border-radius: 4px; background: ' . ($badge_color === '#00a32a' ? '#e8f5e9' : ($badge_color === '#2271b1' ? '#e8f4f8' : '#f0f0f1')) . '; color: ' . $badge_color . '; font-weight: 500; display: inline-block;';
                
                if ($subscriber_id) {
                    $newsletter_url = admin_url('admin.php?page=cta-newsletter');
                    echo '<a href="' . esc_url($newsletter_url) . '" class="cta-newsletter-badge ' . esc_attr($badge_class) . '" style="' . $badge_style . '" title="View in Newsletter Subscribers">' . esc_html($badge_text) . '</a>';
                } else {
                    echo '<span class="cta-newsletter-badge ' . esc_attr($badge_class) . '" style="' . $badge_style . '">' . esc_html($badge_text) . '</span>';
                }
            } else {
                echo '<span class="cta-admin-empty">-</span>';
            }
            break;
    }
}
add_action('manage_form_submission_posts_custom_column', 'cta_form_submission_admin_column_content', 10, 2);

/**
 * Format date column with relative dates
 */
function cta_form_submission_date_column($date, $post_id, $column_name, $post_type) {
    if ($post_type !== 'form_submission' || $column_name !== 'date') {
        return $date;
    }
    
    $post_date = get_post_time('U', true, $post_id);
    $time_diff = time() - $post_date;
    
    // Show relative time for recent posts
    if ($time_diff < DAY_IN_SECONDS) {
        // Less than a day ago
        if ($time_diff < HOUR_IN_SECONDS) {
            $minutes = round($time_diff / MINUTE_IN_SECONDS);
            if ($minutes < 1) {
                $relative = 'Just now';
            } else {
                $relative = sprintf(_n('%d minute ago', '%d minutes ago', $minutes), $minutes);
            }
        } else {
            $hours = round($time_diff / HOUR_IN_SECONDS);
            $relative = sprintf(_n('%d hour ago', '%d hours ago', $hours), $hours);
        }
        return '<span title="' . esc_attr($date) . '">' . esc_html($relative) . '</span>';
    } elseif ($time_diff < WEEK_IN_SECONDS) {
        // Less than a week ago
        $days = round($time_diff / DAY_IN_SECONDS);
        if ($days === 1) {
            $relative = 'Yesterday';
        } else {
            $relative = sprintf(_n('%d day ago', '%d days ago', $days), $days);
        }
        return '<span title="' . esc_attr($date) . '">' . esc_html($relative) . '</span>';
    }
    
    // For older posts, show abbreviated date but keep full date on hover
    $abbrev_date = get_post_time('j M Y', false, $post_id);
    return '<span title="' . esc_attr($date) . '">' . esc_html($abbrev_date) . '</span>';
}
add_filter('post_date_column_time', 'cta_form_submission_date_column', 10, 4);

/**
 * Add bulk actions for form submissions
 * NOTE: Disabled because we use custom HTML bulk actions integrated with tabs
 */
// function cta_form_submission_bulk_actions($actions) {
//     $actions['bulk_assign'] = 'Assign To';
//     $actions['bulk_status'] = 'Change Follow-up Status';
//     return $actions;
// }
// add_filter('bulk_actions-edit-form_submission', 'cta_form_submission_bulk_actions');

/**
 * Handle bulk actions for form submissions
 */
function cta_form_submission_handle_bulk_actions($redirect_to, $action, $post_ids) {
    if (!in_array($action, ['bulk_assign', 'bulk_status', 'delete_permanently', 'trash'])) {
        return $redirect_to;
    }
    
    check_admin_referer('bulk-posts');
    
    if (!current_user_can('edit_posts')) {
        wp_die('You do not have permission to perform this action.');
    }
    
    $updated = 0;
    
    // Handle trash action
    if ($action === 'trash') {
        foreach ($post_ids as $post_id) {
            wp_trash_post($post_id);
            $updated++;
        }
        $redirect_to = add_query_arg('bulk_trashed', $updated, $redirect_to);
        return $redirect_to;
    }
    
    if ($action === 'bulk_assign') {
        $assigned_to = isset($_REQUEST['bulk_assigned_to']) ? sanitize_text_field($_REQUEST['bulk_assigned_to']) : '';
        
        if (!empty($assigned_to)) {
            foreach ($post_ids as $post_id) {
                update_post_meta($post_id, '_submission_assigned_to', $assigned_to);
                $updated++;
            }
            $redirect_to = add_query_arg('bulk_assigned', $updated, $redirect_to);
        }
    } elseif ($action === 'bulk_status') {
        $status = isset($_REQUEST['bulk_followup_status']) ? sanitize_text_field($_REQUEST['bulk_followup_status']) : '';
        
        if (!empty($status)) {
            foreach ($post_ids as $post_id) {
                update_post_meta($post_id, '_submission_followup_status', $status);
                $updated++;
            }
            $redirect_to = add_query_arg('bulk_status_updated', $updated, $redirect_to);
        }
    } elseif ($action === 'delete_permanently') {
        // Permanently delete submissions (useful for test data)
        if (!current_user_can('delete_posts')) {
            wp_die('You do not have permission to delete posts.');
        }
        
        foreach ($post_ids as $post_id) {
            // Verify this is actually a form submission before deleting
            if (get_post_type($post_id) === 'form_submission') {
                wp_delete_post($post_id, true); // true = bypass trash, delete permanently
                $updated++;
            }
        }
        $redirect_to = add_query_arg('bulk_deleted', $updated, $redirect_to);
    }
    
    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-form_submission', 'cta_form_submission_handle_bulk_actions', 10, 3);

/**
 * Add bulk action selectors
 */
function cta_form_submission_bulk_action_selectors() {
    global $typenow;
    
    if ($typenow !== 'form_submission') {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Add bulk assign selector
        $('<select name="bulk_assigned_to" id="bulk_assigned_to" style="display:none;"><option value="">— Select —</option><option value="AS">AS</option><option value="ES">ES</option><option value="VW">VW</option><option value="CR">CR</option></select>')
            .insertAfter('select[name="action"]');
        
        // Add bulk status selector
        $('<select name="bulk_followup_status" id="bulk_followup_status" style="display:none;"><option value="">— Select —</option><option value="new">New Enquiry</option><option value="needed">Requires Follow-up</option><option value="contacted-email">Contacted (Email)</option><option value="contacted-phone">Contacted (Phone)</option><option value="contacted-both">Contacted</option><option value="in-progress">Interested / Enquiring</option><option value="booked">Booked</option><option value="paid">Booked & Paid</option><option value="completed">Attended</option><option value="cancelled">Cancelled</option></select>')
            .insertAfter('select[name="bulk_assigned_to"]');
        
        // Show/hide selectors based on action selection
        $('select[name="action"], select[name="action2"]').on('change', function() {
            var action = $(this).val();
            $('#bulk_assigned_to, #bulk_followup_status').hide();
            
            if (action === 'bulk_assign') {
                $('#bulk_assigned_to').show();
            } else if (action === 'bulk_status') {
                $('#bulk_followup_status').show();
            }
        });
    });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'cta_form_submission_bulk_action_selectors');

/**
 * Show bulk action admin notices
 */
function cta_form_submission_bulk_action_notices() {
    global $post_type, $pagenow;
    
    if ($pagenow !== 'edit.php' || $post_type !== 'form_submission') {
        return;
    }
    
    if (isset($_REQUEST['bulk_assigned'])) {
        $count = intval($_REQUEST['bulk_assigned']);
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             sprintf(_n('%d submission assigned.', '%d submissions assigned.', $count), $count) . 
             '</p></div>';
    }
    
    if (isset($_REQUEST['bulk_status_updated'])) {
        $count = intval($_REQUEST['bulk_status_updated']);
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             sprintf(_n('%d submission status updated.', '%d submissions status updated.', $count), $count) . 
             '</p></div>';
    }
    
    if (isset($_REQUEST['bulk_deleted'])) {
        $count = intval($_REQUEST['bulk_deleted']);
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             sprintf(_n('%d submission permanently deleted.', '%d submissions permanently deleted.', $count), $count) . 
             '</p></div>';
    }
    
    if (isset($_REQUEST['bulk_trashed'])) {
        $count = intval($_REQUEST['bulk_trashed']);
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             sprintf(_n('%d submission moved to trash.', '%d submissions moved to trash.', $count), $count) . 
             ' <a href="' . esc_url(admin_url('edit.php?post_type=form_submission&post_status=trash')) . '">View Trash</a>' .
             '</p></div>';
    }
}
add_action('admin_notices', 'cta_form_submission_bulk_action_notices');

/**
 * Make columns sortable
 */
function cta_form_submission_sortable_columns($columns) {
    $columns['form_type'] = 'form_type';
    $columns['followup_status'] = 'followup_status';
    $columns['marketing_consent'] = 'marketing_consent';
    $columns['email_status'] = 'email_status';
    $columns['assigned_to'] = 'assigned_to';
    $columns['course_name'] = 'course_name';
    $columns['event_date'] = 'event_date';
    $columns['delegates'] = 'delegates';
    $columns['newsletter_status'] = 'newsletter_status';
    return $columns;
}
add_filter('manage_edit-form_submission_sortable_columns', 'cta_form_submission_sortable_columns');

/**
 * Handle custom column sorting
 */
function cta_form_submission_custom_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    switch ($orderby) {
        case 'followup_status':
        case 'marketing_consent':
        case 'email_status':
        case 'assigned_to':
        case 'course_name':
        case 'event_date':
        case 'delegates':
        case 'newsletter_status':
            $query->set('meta_key', '_submission_' . $orderby);
            $query->set('orderby', 'meta_value');
            break;
    }
}
add_action('pre_get_posts', 'cta_form_submission_custom_orderby');

/**
 * Get count of submissions by status (for quick filter badges)
 * Cached to improve performance
 */
function cta_get_submission_count_by_status($status) {
    $cache_key = 'cta_submission_count_' . $status;
    $count = get_transient($cache_key);
    
    if ($count === false) {
        $args = [
            'post_type' => 'form_submission',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_submission_followup_status',
                    'value' => $status,
                    'compare' => '=',
                ],
            ],
        ];
        
        // Exclude cancelled from "All Active"
        if ($status === 'active') {
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => '_submission_followup_status',
                    'value' => 'cancelled',
                    'compare' => '!=',
                ],
                [
                    'key' => '_submission_followup_status',
                    'compare' => 'NOT EXISTS',
                ],
            ];
        }
        
        $query = new WP_Query($args);
        $count = $query->found_posts;
        
        // Cache for 5 minutes to improve performance
        set_transient($cache_key, $count, 5 * MINUTE_IN_SECONDS);
    }
    
    return $count;
}

/**
 * Get submission count by tab category
 */
function cta_get_submission_count_by_tab($tab) {
    $cache_key = 'cta_submission_tab_count_' . $tab;
    $count = get_transient($cache_key);
    
    if ($count === false) {
        $args = [
            'post_type' => 'form_submission',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];
        
        switch ($tab) {
            case 'course-enquiries':
                // Course Enquiries: course-booking, book-course (individual course bookings)
                // Exclude event bookings (course-booking where course_id is a course_event)
                // Exclude group bookings (group-booking, group-training)
                $course_events = get_posts([
                    'post_type' => 'course_event',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                ]);
                
                // Get all course-booking and book-course submissions
                $course_enquiry_ids = get_posts([
                    'post_type' => 'form_submission',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'tax_query' => [
                        [
                            'taxonomy' => 'form_type',
                            'field' => 'slug',
                            'terms' => ['course-booking', 'book-course'],
                            'operator' => 'IN',
                        ],
                    ],
                ]);
                
                // Exclude event bookings (course-booking where course_id is a course_event)
                if (!empty($course_events) && !empty($course_enquiry_ids)) {
                    $event_booking_ids = get_posts([
                        'post_type' => 'form_submission',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                        'post__in' => $course_enquiry_ids,
                        'tax_query' => [
                            [
                                'taxonomy' => 'form_type',
                                'field' => 'slug',
                                'terms' => 'course-booking',
                            ],
                        ],
                        'meta_query' => [
                            [
                                'key' => '_submission_course_id',
                                'value' => $course_events,
                                'compare' => 'IN',
                            ],
                        ],
                    ]);
                    $course_enquiry_ids = array_diff($course_enquiry_ids, $event_booking_ids);
                }
                
                if (!empty($course_enquiry_ids)) {
                    $args['post__in'] = array_values($course_enquiry_ids);
                } else {
                    return 0;
                }
                break;
                
            case 'event-bookings':
                // Event bookings: course-booking where course_id is a course_event
                $course_events = get_posts([
                    'post_type' => 'course_event',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                ]);
                
                if (!empty($course_events)) {
                    $args['tax_query'] = [
                        [
                            'taxonomy' => 'form_type',
                            'field' => 'slug',
                            'terms' => 'course-booking',
                        ],
                    ];
                    $args['meta_query'] = [
                        [
                            'key' => '_submission_course_id',
                            'value' => $course_events,
                            'compare' => 'IN',
                        ],
                    ];
                } else {
                    return 0;
                }
                break;
                
            case 'group-enquiries':
                // Group Enquiries: group-booking, group-training
                $args['tax_query'] = [
                    [
                        'taxonomy' => 'form_type',
                        'field' => 'slug',
                        'terms' => ['group-booking', 'group-training'],
                        'operator' => 'IN',
                    ],
                ];
                break;
                
            case 'newsletter':
                // Newsletter Subscribers: newsletter only
                $args['tax_query'] = [
                    [
                        'taxonomy' => 'form_type',
                        'field' => 'slug',
                        'terms' => 'newsletter',
                    ],
                ];
                break;
                
            case 'other':
                // Other Enquiries: callback-request, schedule-call, general, cqc-training, support
                $args['tax_query'] = [
                    [
                        'taxonomy' => 'form_type',
                        'field' => 'slug',
                        'terms' => ['callback-request', 'schedule-call', 'general', 'cqc-training', 'support'],
                        'operator' => 'IN',
                    ],
                ];
                break;
                
            default:
                return 0;
        }
        
        $query = new WP_Query($args);
        $count = $query->found_posts;
        
        // Cache for 5 minutes
        set_transient($cache_key, $count, 5 * MINUTE_IN_SECONDS);
    }
    
    return $count;
}

/**
 * Clear submission count cache when submissions are updated
 */
function cta_clear_submission_count_cache($post_id) {
    if (get_post_type($post_id) === 'form_submission') {
        $statuses = ['new', 'needed', 'booked', 'active'];
        foreach ($statuses as $status) {
            delete_transient('cta_submission_count_' . $status);
        }
        
        // Clear tab counts too
        $tabs = ['course-enquiries', 'event-bookings', 'newsletter', 'other'];
        foreach ($tabs as $tab) {
            delete_transient('cta_submission_tab_count_' . $tab);
        }
    }
}
add_action('save_post', 'cta_clear_submission_count_cache');
add_action('delete_post', 'cta_clear_submission_count_cache');

/**
 * Add custom styles for form submissions admin
 */
function cta_form_submission_admin_styles() {
    global $typenow;
    
    if ($typenow !== 'form_submission') {
        return;
    }
    ?>
    <style>
        /* Hide default WordPress elements */
        .wrap .wp-heading-inline + .page-title-action,
        .subsubsub {
            display: none !important;
        }
        
        /* Custom filter pills container */
        .cta-filter-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 20px 0;
            align-items: center;
        }
        
        .cta-filter-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            background: #f0f0f1;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            color: #2c3338;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        
        .cta-filter-pill:hover {
            background: #e5e5e7;
            border-color: #c3c4c7;
            color: #1d2327;
        }
        
        .cta-filter-pill.active {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
        }
        
        .cta-filter-pill.active:hover {
            background: #135e96;
            border-color: #135e96;
        }
        
        /* Action buttons row */
        .cta-actions-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 16px 0;
            align-items: center;
        }
        
        .cta-actions-row .button {
            height: 32px;
            line-height: 30px;
        }
        
        .cta-actions-row select {
            height: 32px;
        }
        
        /* Clean up spacing */
        .tablenav.top {
            margin: 0 0 8px 0;
        }
        
        .tablenav .actions {
            padding: 0;
        }
    </style>
    <?php
}
add_action('admin_head', 'cta_form_submission_admin_styles');

/**
 * Add clean filter interface with pills
 */
function cta_form_submission_add_filters() {
    global $typenow;
    
    if (!is_admin() || !current_user_can('edit_posts') || $typenow !== 'form_submission') {
        return;
    }
    
    $selected_followup = isset($_GET['followup_filter']) ? $_GET['followup_filter'] : '';
    $selected_date = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
    $selected_form_type = isset($_GET['form_type_filter']) ? $_GET['form_type_filter'] : '';
    $selected_consent = isset($_GET['marketing_consent_filter']) ? $_GET['marketing_consent_filter'] : '';
    $selected_email_status = isset($_GET['email_status_filter']) ? $_GET['email_status_filter'] : '';
    $selected_tab = isset($_GET['submission_tab']) ? sanitize_text_field($_GET['submission_tab']) : 'all';
    
    $has_filters = !empty($selected_form_type) || !empty($selected_followup) || 
                   !empty($selected_consent) || !empty($selected_email_status) ||
                   !empty($selected_date);
    
    // Get counts for each tab
    $all_count = wp_count_posts('form_submission')->publish;
    $course_enquiries_count = cta_get_submission_count_by_tab('course-enquiries');
    $event_bookings_count = cta_get_submission_count_by_tab('event-bookings');
    $group_enquiries_count = cta_get_submission_count_by_tab('group-enquiries');
    $newsletter_count = cta_get_submission_count_by_tab('newsletter');
    $other_count = cta_get_submission_count_by_tab('other');
    
    // Filter pills for quick access
    echo '<div class="cta-filter-pills">';
    
    $quick_filters = [
        ['label' => 'All types', 'param' => 'submission_tab', 'value' => 'all'],
        ['label' => 'Course Enquiries', 'param' => 'submission_tab', 'value' => 'course-enquiries', 'count' => $course_enquiries_count],
        ['label' => 'Event type', 'param' => 'submission_tab', 'value' => 'event-bookings', 'count' => $event_bookings_count],
        ['label' => 'Newsletter', 'param' => 'submission_tab', 'value' => 'newsletter', 'count' => $newsletter_count],
        ['label' => 'Group', 'param' => 'submission_tab', 'value' => 'group-enquiries', 'count' => $group_enquiries_count],
        ['label' => 'Other Listings (' . $other_count . ')', 'param' => 'submission_tab', 'value' => 'other'],
    ];
    
    foreach ($quick_filters as $filter) {
        $base_url = admin_url('edit.php?post_type=form_submission');
        
        // Build URL with this filter
        if ($filter['param'] === 'submission_tab') {
            if ($filter['value'] !== 'all') {
                $url = add_query_arg('submission_tab', $filter['value'], $base_url);
            } else {
                $url = $base_url;
            }
        } else {
            $url = add_query_arg($filter['param'], $filter['value'], $base_url);
            // Preserve tab
            if ($selected_tab !== 'all') {
                $url = add_query_arg('submission_tab', $selected_tab, $url);
            }
        }
        
        // Check if active
        $is_active = false;
        if ($filter['param'] === 'submission_tab') {
            $is_active = $selected_tab === $filter['value'];
        } elseif ($filter['param'] === 'date_filter') {
            $is_active = $selected_date === $filter['value'];
        }
        
        $active_class = $is_active ? ' active' : '';
        $display_label = $filter['label'];
        
        // Add count if available and not already in label
        if (isset($filter['count']) && strpos($filter['label'], '(') === false) {
            $display_label .= ' (' . $filter['count'] . ')';
        }
        
        printf(
            '<a href="%s" class="cta-filter-pill%s">%s</a>',
            esc_url($url),
            $active_class,
            esc_html($display_label)
        );
    }
    
    echo '</div>';
    
    // Add hidden input to preserve tab selection when filtering
    echo '<input type="hidden" name="submission_tab" value="' . esc_attr($selected_tab) . '">';
    
    // Actions row with bulk actions and filters
    echo '<div class="cta-actions-row">';
    
    // Bulk actions dropdown
    echo '<select name="action" id="bulk-action-selector-top">';
    echo '<option value="-1">Bulk actions</option>';
    echo '<option value="trash">Move to Trash</option>';
    echo '<option value="delete_permanently">Delete Permanently</option>';
    echo '</select>';
    submit_button(__('Apply'), 'action', '', false, ['id' => 'doaction']);
    
    // Show "All" button if on a specific tab
    if ($selected_tab !== 'all') {
        $all_url = admin_url('edit.php?post_type=form_submission');
        printf(
            '<a href="%s" class="button">All</a>',
            esc_url($all_url)
        );
    }
    
    // Filter button to show dropdown filters
    echo '<button type="button" class="button" id="cta-toggle-filters">Advanced filters</button>';
    
    // Export button
    echo '<button type="button" class="button">Export</button>';
    
    echo '</div>';
    
    // Collapsible advanced filters
    $show_filters = $has_filters ? ' style="display: block;"' : ' style="display: none;"';
    echo '<div id="cta-advanced-filters" class="tablenav top"' . $show_filters . '>';
    echo '<div class="alignleft actions">';
    
    // Date filter
    echo '<select name="date_filter" id="date_filter" class="postform">';
    echo '<option value="">All dates</option>';
    echo '<option value="today"' . selected($selected_date, 'today', false) . '>Today</option>';
    echo '<option value="yesterday"' . selected($selected_date, 'yesterday', false) . '>Yesterday</option>';
    echo '<option value="week"' . selected($selected_date, 'week', false) . '>Last 7 days</option>';
    echo '<option value="month"' . selected($selected_date, 'month', false) . '>Last 30 days</option>';
    echo '<option value="3months"' . selected($selected_date, '3months', false) . '>Last 3 months</option>';
    echo '</select>';
    
    // Form Type filter (only show if on "all" tab)
    if ($selected_tab === 'all') {
        $form_types = get_terms(['taxonomy' => 'form_type', 'hide_empty' => false]);
        
        if (!empty($form_types) && !is_wp_error($form_types)) {
            echo '<select name="form_type_filter" id="form_type_filter" class="postform">';
            echo '<option value="">All Form Types</option>';
            foreach ($form_types as $form_type) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($form_type->slug),
                    selected($selected_form_type, $form_type->slug, false),
                    esc_html($form_type->name)
                );
            }
            echo '</select>';
        }
    }
    
    // Follow-up Status filter
    echo '<select name="followup_filter" id="followup_filter" class="postform">';
    echo '<option value="">All Status</option>';
    echo '<option value="new"' . selected($selected_followup, 'new', false) . '>New</option>';
    echo '<option value="needed"' . selected($selected_followup, 'needed', false) . '>Follow-up Needed</option>';
    echo '<option value="contacted-email"' . selected($selected_followup, 'contacted-email', false) . '>Contacted (Email)</option>';
    echo '<option value="contacted-phone"' . selected($selected_followup, 'contacted-phone', false) . '>Contacted (Phone)</option>';
    echo '<option value="contacted-both"' . selected($selected_followup, 'contacted-both', false) . '>Contacted (Both)</option>';
    echo '<option value="in-progress"' . selected($selected_followup, 'in-progress', false) . '>In Progress</option>';
    echo '<option value="booked"' . selected($selected_followup, 'booked', false) . '>Booked</option>';
    echo '<option value="paid"' . selected($selected_followup, 'paid', false) . '>Paid</option>';
    echo '<option value="completed"' . selected($selected_followup, 'completed', false) . '>Completed</option>';
    echo '<option value="cancelled"' . selected($selected_followup, 'cancelled', false) . '>Cancelled</option>';
    echo '</select>';
    
    // Marketing Consent filter
    echo '<select name="marketing_consent_filter" id="marketing_consent_filter" class="postform">';
    echo '<option value="">All Marketing Consent</option>';
    echo '<option value="yes"' . selected($selected_consent, 'yes', false) . '>Opted In</option>';
    echo '<option value="no"' . selected($selected_consent, 'no', false) . '>Opted Out</option>';
    echo '</select>';
    
    // Email Status filter
    echo '<select name="email_status_filter" id="email_status_filter" class="postform">';
    echo '<option value="">All Email Status</option>';
    echo '<option value="sent"' . selected($selected_email_status, 'sent', false) . '>Sent</option>';
    echo '<option value="failed"' . selected($selected_email_status, 'failed', false) . '>Failed</option>';
    echo '</select>';
    
    // Apply filters button
    submit_button('Apply Filters', 'button', 'filter_action', false, ['id' => 'post-query-submit']);
    
    // Clear filters link
    if ($has_filters) {
        $clear_url = admin_url('edit.php?post_type=form_submission');
        if ($selected_tab !== 'all') {
            $clear_url = add_query_arg('submission_tab', $selected_tab, $clear_url);
        }
        printf(
            ' <a href="%s" class="button">Clear Filters</a>',
            esc_url($clear_url)
        );
    }
    
    echo '</div>';
    echo '</div>';
    
    // JavaScript to toggle filters
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#cta-toggle-filters').on('click', function() {
            $('#cta-advanced-filters').slideToggle(200);
        });
    });
    </script>
    <?php
}
add_action('restrict_manage_posts', 'cta_form_submission_add_filters');

/**
 * Add custom bulk actions for form submissions
 */
function cta_form_submission_bulk_actions($actions) {
    global $typenow;
    
    if ($typenow === 'form_submission') {
        // Remove default edit action
        unset($actions['edit']);
        
        // Add our custom actions
        $actions = [
            'bulk_assign' => 'Assign To',
            'bulk_status' => 'Change Follow-up Status',
            'trash' => 'Move to Trash',
        ];
    }
    
    return $actions;
}
add_filter('bulk_actions-edit-form_submission', 'cta_form_submission_bulk_actions', 999);

/**
 * Customize WordPress post status views to show All, Published, and Trash
 */
function cta_customize_post_status_views($views) {
    global $typenow, $wp_query;
    
    if ($typenow === 'form_submission') {
        $post_type_object = get_post_type_object($typenow);
        $num_posts = wp_count_posts($typenow, 'readable');
        $current_status = isset($_GET['post_status']) ? $_GET['post_status'] : 'publish';
        
        $views = [];
        
        // All link
        $all_count = $num_posts->publish;
        $class = ($current_status === 'publish' || empty($_GET['post_status'])) ? 'current' : '';
        $views['all'] = sprintf(
            '<a href="%s" class="%s">All <span class="count">(%s)</span></a>',
            esc_url(admin_url('edit.php?post_type=form_submission')),
            $class,
            number_format_i18n($all_count)
        );
        
        // Trash link
        if (!empty($num_posts->trash)) {
            $class = ($current_status === 'trash') ? 'current' : '';
            $views['trash'] = sprintf(
                '<a href="%s" class="%s">Trash <span class="count">(%s)</span></a>',
                esc_url(admin_url('edit.php?post_type=form_submission&post_status=trash')),
                $class,
                number_format_i18n($num_posts->trash)
            );
        }
    }
    
    return $views;
}
add_filter('views_edit-form_submission', 'cta_customize_post_status_views', 999);

/**
 * Remove duplicate "Filter" button and "All dates" dropdown
 * WordPress adds these by default, but we already have them
 */
function cta_remove_duplicate_filters() {
    global $typenow;
    
    if ($typenow === 'form_submission') {
        ?>
        <style>
            /* Hide WordPress's default month filter dropdown (duplicate "All dates") */
            #filter-by-date {
                display: none !important;
            }
            
            /* Hide WordPress's default filter button (we have our own "Advanced filters" button) */
            .tablenav .actions #post-query-submit {
                display: none !important;
            }
            
            /* Hide WordPress's default bulk actions dropdown (we have our own) */
            .tablenav.top .bulkactions:not(#doaction):not(#doaction2) {
                display: none !important;
            }
            
            /* Hide the default bulk action selector */
            .tablenav select[name="action"]:not(#bulk-action-selector-top),
            .tablenav select[name="action2"]:not(#bulk-action-selector-top) {
                display: none !important;
            }
            
            /* Use WordPress standard dropdown widths */
            #cta-advanced-filters select.postform,
            #bulk-action-selector-top {
                width: auto !important;
                max-width: 200px !important;
                min-width: 150px !important;
            }
        </style>
        <?php
    }
}
add_action('admin_head', 'cta_remove_duplicate_filters');

/**
 * Set default posts per page for form submissions
 */
function cta_form_submission_screen_options($screen_options, $screen) {
    if ($screen->id === 'edit-form_submission') {
        $screen_options['per_page'] = 50;
    }
    return $screen_options;
}
add_filter('set-screen-option', 'cta_form_submission_screen_options', 10, 2);

/**
 * Set default posts per page on first load
 */
function cta_form_submission_default_posts_per_page($per_page, $post_type) {
    if ($post_type === 'form_submission') {
        $user_per_page = get_user_option('edit_form_submission_per_page');
        if (!$user_per_page) {
            return 50;
        }
    }
    return $per_page;
}
add_filter('edit_posts_per_page', 'cta_form_submission_default_posts_per_page', 10, 2);

/**
 * Filter submissions by form type and marketing consent
 */
function cta_form_submission_filter_query($query) {
    global $pagenow, $typenow;
    
    // SECURITY: Check admin context and capability
    if (!is_admin() || !current_user_can('edit_posts')) {
        return;
    }
    
    if ($pagenow === 'edit.php' && $typenow === 'form_submission' && $query->is_main_query()) {
        // Default sort based on tab if no orderby is set
        if (!isset($_GET['orderby'])) {
            $selected_tab = isset($_GET['submission_tab']) ? sanitize_text_field($_GET['submission_tab']) : 'all';
            
            switch ($selected_tab) {
                case 'event-bookings':
                    // Sort event bookings by event date (soonest first)
                    $query->set('meta_key', '_submission_event_date');
                    $query->set('orderby', 'meta_value');
                    $query->set('order', 'ASC');
                    break;
                case 'group-enquiries':
                    // Sort group enquiries by number of delegates (descending)
                    $query->set('meta_key', '_submission_delegates');
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'DESC');
                    break;
                case 'newsletter':
                    // Sort newsletter subscriptions by date (newest first)
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                    break;
                default:
                    // All other tabs: sort by date (newest first)
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                    break;
            }
        }
        
        // Tab filter - categorize submissions
        $selected_tab = isset($_GET['submission_tab']) ? sanitize_text_field($_GET['submission_tab']) : 'all';
        
        if ($selected_tab !== 'all') {
            $meta_query = $query->get('meta_query') ?: [];
            $tax_query = $query->get('tax_query') ?: [];
            
            switch ($selected_tab) {
                case 'course-enquiries':
                    // Course Enquiries: course-booking, book-course (individual course bookings)
                    // Exclude event bookings (course-booking where course_id is a course_event)
                    // Exclude group bookings (group-booking, group-training)
                    $course_events = get_posts([
                        'post_type' => 'course_event',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                    ]);
                    
                    // Get all course-booking and book-course submissions
                    $course_enquiry_ids = get_posts([
                        'post_type' => 'form_submission',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                        'tax_query' => [
                            [
                                'taxonomy' => 'form_type',
                                'field' => 'slug',
                                'terms' => ['course-booking', 'book-course'],
                                'operator' => 'IN',
                            ],
                        ],
                    ]);
                    
                    // Exclude event bookings (course-booking where course_id is a course_event)
                    if (!empty($course_events) && !empty($course_enquiry_ids)) {
                        $event_booking_ids = get_posts([
                            'post_type' => 'form_submission',
                            'posts_per_page' => -1,
                            'fields' => 'ids',
                            'post__in' => $course_enquiry_ids,
                            'tax_query' => [
                                [
                                    'taxonomy' => 'form_type',
                                    'field' => 'slug',
                                    'terms' => 'course-booking',
                                ],
                            ],
                            'meta_query' => [
                                [
                                    'key' => '_submission_course_id',
                                    'value' => $course_events,
                                    'compare' => 'IN',
                                ],
                            ],
                        ]);
                        $course_enquiry_ids = array_diff($course_enquiry_ids, $event_booking_ids);
                    }
                    
                    if (!empty($course_enquiry_ids)) {
                        $query->set('post__in', array_values($course_enquiry_ids));
                        // Clear other queries since we're using post__in
                        $query->set('meta_query', []);
                        $query->set('tax_query', []);
                    } else {
                        $query->set('post__in', [0]);
                    }
                    break;
                    
                case 'event-bookings':
                    // Event bookings: course-booking where course_id is a course_event
                    // First filter by form type
                    $tax_query[] = [
                        'taxonomy' => 'form_type',
                        'field' => 'slug',
                        'terms' => 'course-booking',
                    ];
                    
                    // Then filter by course_id that is a course_event
                    $course_events = get_posts([
                        'post_type' => 'course_event',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                    ]);
                    
                    if (!empty($course_events)) {
                        $meta_query[] = [
                            'key' => '_submission_course_id',
                            'value' => $course_events,
                            'compare' => 'IN',
                        ];
                    } else {
                        // No course events exist, return empty result
                        $query->set('post__in', [0]);
                    }
                    break;
                    
                case 'group-enquiries':
                    // Group Enquiries: group-booking, group-training
                    $tax_query[] = [
                        'taxonomy' => 'form_type',
                        'field' => 'slug',
                        'terms' => ['group-booking', 'group-training'],
                        'operator' => 'IN',
                    ];
                    break;
                    
                case 'newsletter':
                    // Newsletter Subscribers: newsletter only
                    $tax_query[] = [
                        'taxonomy' => 'form_type',
                        'field' => 'slug',
                        'terms' => 'newsletter',
                    ];
                    break;
                    
                case 'other':
                    // Other Enquiries: callback-request, schedule-call, general, cqc-training, support
                    $tax_query[] = [
                        'taxonomy' => 'form_type',
                        'field' => 'slug',
                        'terms' => ['callback-request', 'schedule-call', 'general', 'cqc-training', 'support'],
                        'operator' => 'IN',
                    ];
                    break;
            }
            
            if (!empty($meta_query)) {
                $query->set('meta_query', $meta_query);
            }
            if (!empty($tax_query)) {
                $query->set('tax_query', $tax_query);
            }
        }
        
        // Form Type filter (works alongside tab filter)
        if (isset($_GET['form_type_filter']) && $_GET['form_type_filter'] !== '') {
            $existing_tax_query = $query->get('tax_query') ?: [];
            
            // If we already have a tax_query from tab filter, combine them
            if (!empty($existing_tax_query)) {
                $existing_tax_query[] = [
                    'taxonomy' => 'form_type',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_GET['form_type_filter']),
                ];
                $query->set('tax_query', $existing_tax_query);
            } else {
                $query->set('tax_query', [
                    [
                        'taxonomy' => 'form_type',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['form_type_filter']),
                    ],
                ]);
            }
        }
        
        // Date filter
        if (isset($_GET['date_filter']) && $_GET['date_filter'] !== '') {
            $date_filter = sanitize_text_field($_GET['date_filter']);
            $date_query = [];
            
            switch ($date_filter) {
                case 'today':
                    $date_query = [
                        'year' => date('Y'),
                        'month' => date('n'),
                        'day' => date('j'),
                    ];
                    break;
                case 'yesterday':
                    $yesterday = date('Y-m-d', strtotime('yesterday'));
                    $date_query = [
                        'year' => date('Y', strtotime($yesterday)),
                        'month' => date('n', strtotime($yesterday)),
                        'day' => date('j', strtotime($yesterday)),
                    ];
                    break;
                case 'week':
                    $date_query = [
                        'after' => '7 days ago',
                        'inclusive' => true,
                    ];
                    break;
                case 'month':
                    $date_query = [
                        'after' => '30 days ago',
                        'inclusive' => true,
                    ];
                    break;
                case '3months':
                    $date_query = [
                        'after' => '3 months ago',
                        'inclusive' => true,
                    ];
                    break;
            }
            
            if (!empty($date_query)) {
                $query->set('date_query', [$date_query]);
            }
        }
        
        // Marketing Consent filter
        if (isset($_GET['marketing_consent_filter']) && $_GET['marketing_consent_filter'] !== '') {
            $meta_query = $query->get('meta_query') ?: [];
            $meta_query[] = [
                'key' => '_submission_marketing_consent',
                'value' => $_GET['marketing_consent_filter'] === 'yes' ? ['yes', 'on', '1'] : ['no', 'off', '0', ''],
                'compare' => $_GET['marketing_consent_filter'] === 'yes' ? 'IN' : 'NOT IN',
            ];
            $query->set('meta_query', $meta_query);
        }
        
        // Email Status filter
        if (isset($_GET['email_status_filter']) && $_GET['email_status_filter'] !== '') {
            $meta_query = $query->get('meta_query') ?: [];
            if ($_GET['email_status_filter'] === 'sent') {
                $meta_query[] = [
                    'key' => '_submission_email_sent',
                    'value' => ['yes', '1'],
                    'compare' => 'IN',
                ];
            } elseif ($_GET['email_status_filter'] === 'failed') {
                $meta_query[] = [
                    'key' => '_submission_email_error',
                    'compare' => 'EXISTS',
                ];
            } else { // pending
                $meta_query[] = [
                    'relation' => 'OR',
                    [
                        'key' => '_submission_email_sent',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_submission_email_sent',
                        'value' => ['yes', '1'],
                        'compare' => 'NOT IN',
                    ],
                ];
            }
            $query->set('meta_query', $meta_query);
        }
        
        // Follow-up Status filter
        if (isset($_GET['followup_filter']) && $_GET['followup_filter'] !== '') {
            $meta_query = $query->get('meta_query') ?: [];
            if ($_GET['followup_filter'] === 'none') {
                $meta_query[] = [
                    'relation' => 'OR',
                    [
                        'key' => '_submission_followup_status',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_submission_followup_status',
                        'value' => '',
                        'compare' => '=',
                    ],
                ];
            } else {
                $meta_query[] = [
                    'key' => '_submission_followup_status',
                    'value' => sanitize_text_field($_GET['followup_filter']),
                    'compare' => '=',
                ];
            }
            $query->set('meta_query', $meta_query);
        }
        
        // Archive filter - show only archived (cancelled) items
        if (isset($_GET['archive_filter']) && $_GET['archive_filter'] === 'archived') {
            $meta_query = $query->get('meta_query') ?: [];
            // Remove any existing followup_status filters
            $meta_query = array_filter($meta_query, function($query) {
                return isset($query['key']) && $query['key'] !== '_submission_followup_status';
            });
            $meta_query = array_values($meta_query); // Re-index array
            $meta_query[] = [
                'key' => '_submission_followup_status',
                'value' => 'cancelled',
                'compare' => '=',
            ];
            $query->set('meta_query', $meta_query);
        } elseif (!isset($_GET['followup_filter']) || $_GET['followup_filter'] !== 'cancelled') {
            // Exclude archived (cancelled) items from main views unless specifically viewing cancelled
            $meta_query = $query->get('meta_query') ?: [];
            // Check if we already have a followup_status filter
            $has_followup_filter = false;
            foreach ($meta_query as $mq) {
                if (isset($mq['key']) && $mq['key'] === '_submission_followup_status') {
                    $has_followup_filter = true;
                    break;
                }
            }
            // Only add exclusion if we don't already have a followup filter
            if (!$has_followup_filter) {
                $meta_query[] = [
                    'relation' => 'OR',
                    [
                        'key' => '_submission_followup_status',
                        'value' => 'cancelled',
                        'compare' => '!=',
                    ],
                    [
                        'key' => '_submission_followup_status',
                        'compare' => 'NOT EXISTS',
                    ],
                ];
                $query->set('meta_query', $meta_query);
            }
        }
        
        // Contact forms filter (all contact form enquiry types)
        if (isset($_GET['contact_forms']) && $_GET['contact_forms'] === '1') {
            $tax_query = $query->get('tax_query') ?: [];
            $tax_query[] = [
                'taxonomy' => 'form_type',
                'field' => 'slug',
                'terms' => ['general', 'schedule-call', 'group-training', 'book-course', 'cqc-training', 'support'],
            ];
            $query->set('tax_query', $tax_query);
        }
    }
}
add_action('parse_query', 'cta_form_submission_filter_query');

/**
 * Add meta boxes for submission details
 */
function cta_form_submission_add_meta_boxes() {
    // Remove Form Types taxonomy box (it's registered as non-hierarchical/tags)
    remove_meta_box('tagsdiv-form_type', 'form_submission', 'side');
    remove_meta_box('form_typediv', 'form_submission', 'side');

    // Submission details (use core WP metabox UI)
    add_meta_box(
        'cta_form_submission_details',
        'Submission Details',
        'cta_form_submission_details_wp_callback',
        'form_submission',
        'normal',
        'high'
    );

    // Activity log
    add_meta_box(
        'cta_form_submission_activity_log',
        'Activity Log',
        'cta_form_submission_activity_log_callback',
        'form_submission',
        'normal',
        'default'
    );

    // Follow-up (side)
    add_meta_box(
        'cta_form_submission_followup',
        'Follow-up',
        'cta_form_submission_followup_wp_callback',
        'form_submission',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'cta_form_submission_add_meta_boxes');

/**
 * Metabox: Submission Details (WordPress-native UI)
 */
function cta_form_submission_details_wp_callback($post) {
    if (!current_user_can('edit_posts')) {
        echo '<p>You do not have permission to view this content.</p>';
        return;
    }

    $name = (string) get_post_meta($post->ID, '_submission_name', true);
    $email = (string) get_post_meta($post->ID, '_submission_email', true);
    $phone = (string) get_post_meta($post->ID, '_submission_phone', true);
    $message = (string) get_post_meta($post->ID, '_submission_message', true);
    $page_url = (string) get_post_meta($post->ID, '_submission_page_url', true);
    $marketing_consent = (string) get_post_meta($post->ID, '_submission_marketing_consent', true);

    $ip_address = (string) get_post_meta($post->ID, '_submission_ip', true);
    $user_agent = (string) get_post_meta($post->ID, '_submission_user_agent', true);
    $lead_source = (string) get_post_meta($post->ID, '_submission_lead_source', true);

    $form_data = get_post_meta($post->ID, '_submission_form_data', true);
    if (!is_array($form_data)) {
        $form_data = [];
    }

    $form_type_terms = get_the_terms($post->ID, 'form_type');
    $form_type_name = ($form_type_terms && !is_wp_error($form_type_terms)) ? (string) $form_type_terms[0]->name : '';

    // Course / booking context (from meta + form_data + linked posts)
    $submission_course_id = (int) get_post_meta($post->ID, '_submission_course_id', true);
    if (!$submission_course_id && isset($form_data['course_id'])) {
        $submission_course_id = (int) $form_data['course_id'];
    }
    $submission_course_name = (string) get_post_meta($post->ID, '_submission_course_name', true);
    if ($submission_course_name === '' && isset($form_data['course_name'])) {
        $submission_course_name = (string) $form_data['course_name'];
    }

    $submission_event_date = (string) get_post_meta($post->ID, '_submission_event_date', true);
    if ($submission_event_date === '' && isset($form_data['event_date'])) {
        $submission_event_date = (string) $form_data['event_date'];
    }

    $course_post = null;
    $course_event_post = null;
    $course_level = '';
    $is_upcoming_session = false;

    if ($submission_course_id > 0) {
        $linked = get_post($submission_course_id);
        if ($linked && $linked instanceof WP_Post) {
            if ($linked->post_type === 'course_event') {
                $course_event_post = $linked;
                $is_upcoming_session = true;

                // Prefer event_date from the course_event itself if missing on submission.
                if ($submission_event_date === '') {
                    $submission_event_date = function_exists('get_field')
                        ? (string) (get_field('event_date', $course_event_post->ID) ?: '')
                        : (string) get_post_meta($course_event_post->ID, 'event_date', true);
                }

                // Resolve related course for the session.
                $linked_course = function_exists('get_field')
                    ? get_field('linked_course', $course_event_post->ID)
                    : null;
                $linked_course_id = 0;
                if (is_object($linked_course) && $linked_course instanceof WP_Post) {
                    $linked_course_id = (int) $linked_course->ID;
                } elseif (is_numeric($linked_course)) {
                    $linked_course_id = (int) $linked_course;
                } else {
                    // Fallback: ACF stores ID in post meta.
                    $linked_course_id = (int) get_post_meta($course_event_post->ID, 'linked_course', true);
                }
                if ($linked_course_id > 0) {
                    $maybe_course = get_post($linked_course_id);
                    if ($maybe_course && $maybe_course instanceof WP_Post && $maybe_course->post_type === 'course') {
                        $course_post = $maybe_course;
                    }
                }
            } elseif ($linked->post_type === 'course') {
                $course_post = $linked;
                $is_upcoming_session = ($submission_event_date !== '');
            }
        }
    }

    if ($course_post) {
        $course_level = function_exists('get_field')
            ? (string) (get_field('course_level', $course_post->ID) ?: '')
            : (string) get_post_meta($course_post->ID, 'course_level', true);
        if ($submission_course_name === '') {
            $submission_course_name = (string) $course_post->post_title;
        }
    }

    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row">Submitted</th><td>' . esc_html(get_the_date('d M Y H:i', $post->ID)) . '</td></tr>';
    if ($form_type_name) {
        echo '<tr><th scope="row">Form Type</th><td>' . esc_html($form_type_name) . '</td></tr>';
    }
    if ($name !== '') {
        echo '<tr><th scope="row">Name</th><td>' . esc_html($name) . '</td></tr>';
    }
    if ($email !== '') {
        echo '<tr><th scope="row">Email</th><td><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></td></tr>';
    }
    if ($phone !== '') {
        $tel = preg_replace('/[^0-9+]/', '', $phone);
        echo '<tr><th scope="row">Phone</th><td><a href="tel:' . esc_attr($tel) . '">' . esc_html($phone) . '</a></td></tr>';
    }
    if ($marketing_consent !== '') {
        echo '<tr><th scope="row">Marketing Consent</th><td>' . esc_html($marketing_consent === 'yes' ? 'Yes' : 'No') . '</td></tr>';
    }
    if ($page_url !== '') {
        echo '<tr><th scope="row">Submitted From</th><td><a href="' . esc_url($page_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($page_url) . '</a></td></tr>';
    }
    echo '</tbody></table>';

    // Course section (if applicable)
    if ($submission_course_name !== '' || $submission_course_id > 0 || $submission_event_date !== '') {
        echo '<h3>Course</h3>';
        echo '<table class="widefat striped"><tbody>';

        if ($course_post) {
            $edit_url = get_edit_post_link($course_post->ID);
            echo '<tr><th>Course</th><td>';
            if ($edit_url) {
                echo '<a href="' . esc_url($edit_url) . '">' . esc_html($course_post->post_title) . '</a>';
            } else {
                echo esc_html($course_post->post_title);
            }
            echo '</td></tr>';
        } elseif ($submission_course_name !== '') {
            echo '<tr><th>Course</th><td>' . esc_html($submission_course_name) . '</td></tr>';
        }

        if ($course_level !== '') {
            echo '<tr><th>Level</th><td>' . esc_html($course_level) . '</td></tr>';
        } elseif (isset($form_data['course_level']) && $form_data['course_level'] !== '') {
            echo '<tr><th>Level</th><td>' . esc_html((string) $form_data['course_level']) . '</td></tr>';
        }

        if ($course_event_post) {
            $edit_url = get_edit_post_link($course_event_post->ID);
            echo '<tr><th>Scheduled Session</th><td>';
            if ($edit_url) {
                echo '<a href="' . esc_url($edit_url) . '">View scheduled course</a>';
            } else {
                echo 'Scheduled course';
            }
            echo '</td></tr>';
        }

        if ($submission_event_date !== '') {
            $ts = strtotime($submission_event_date);
            $pretty = $ts ? date_i18n('j M Y', $ts) : $submission_event_date;
            echo '<tr><th>Date</th><td>' . esc_html($pretty) . '</td></tr>';
            echo '<tr><th>Type</th><td>' . esc_html($is_upcoming_session ? 'Upcoming course' : 'Course enquiry') . '</td></tr>';
        }

        // Delegates (common keys)
        $delegates = '';
        foreach (['delegates', 'number_of_staff', 'attendees'] as $k) {
            if (isset($form_data[$k]) && $form_data[$k] !== '') {
                $delegates = (string) $form_data[$k];
                break;
            }
        }
        if ($delegates !== '') {
            echo '<tr><th>Delegates</th><td>' . esc_html($delegates) . '</td></tr>';
        }

        echo '</tbody></table>';
    }

    if ($message !== '') {
        echo '<h3>Message</h3>';
        echo '<div class="notice inline"><p>' . nl2br(esc_html($message)) . '</p></div>';
    }

    if (!empty($form_data)) {
        echo '<h3>Submitted Fields</h3>';
        echo '<table class="widefat striped"><tbody>';
        foreach ($form_data as $key => $value) {
            if ($value === '' || $value === null || $value === []) {
                continue;
            }
            if (is_array($value)) {
                $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } else {
                $value = (string) $value;
            }
            echo '<tr>';
            echo '<th>' . esc_html((string) $key) . '</th>';
            echo '<td><code>' . esc_html($value) . '</code></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    if ($ip_address !== '' || $user_agent !== '' || $lead_source !== '') {
        echo '<h3>Technical</h3>';
        echo '<table class="widefat striped"><tbody>';
        if ($lead_source !== '') {
            echo '<tr><th>Lead Source</th><td>' . esc_html($lead_source) . '</td></tr>';
        }
        if ($ip_address !== '') {
            echo '<tr><th>IP Address</th><td><code>' . esc_html($ip_address) . '</code></td></tr>';
        }
        if ($user_agent !== '') {
            echo '<tr><th>User Agent</th><td><code>' . esc_html($user_agent) . '</code></td></tr>';
        }
        echo '</tbody></table>';
    }
}

/**
 * Metabox: Follow-up (WordPress-native UI)
 */
function cta_form_submission_followup_wp_callback($post) {
    if (!current_user_can('edit_posts')) {
        echo '<p>You do not have permission to view this content.</p>';
        return;
    }

    wp_nonce_field('cta_form_submission_followup', 'cta_form_submission_followup_nonce');

    $followup_status = (string) get_post_meta($post->ID, '_submission_followup_status', true);
    $assigned_to = (string) get_post_meta($post->ID, '_submission_assigned_to', true);
    $followup_notes = (string) get_post_meta($post->ID, '_submission_followup_notes', true);

    echo '<p><label for="followup_status"><strong>Status</strong></label></p>';
    echo '<select name="followup_status" id="followup_status" class="widefat">';
    echo '<option value="">New Enquiry</option>';
    echo '<option value="new"' . selected($followup_status, 'new', false) . '>New Enquiry</option>';
    echo '<option value="needed"' . selected($followup_status, 'needed', false) . '>Requires Follow-up</option>';
    echo '<option value="contacted-email"' . selected($followup_status, 'contacted-email', false) . '>Contacted (Email)</option>';
    echo '<option value="contacted-phone"' . selected($followup_status, 'contacted-phone', false) . '>Contacted (Phone)</option>';
    echo '<option value="contacted-both"' . selected($followup_status, 'contacted-both', false) . '>Contacted</option>';
    echo '<option value="in-progress"' . selected($followup_status, 'in-progress', false) . '>Interested / Enquiring</option>';
    echo '<option value="booked"' . selected($followup_status, 'booked', false) . '>Booked</option>';
    echo '<option value="paid"' . selected($followup_status, 'paid', false) . '>Booked & Paid</option>';
    echo '<option value="completed"' . selected($followup_status, 'completed', false) . '>Attended</option>';
    echo '<option value="cancelled"' . selected($followup_status, 'cancelled', false) . '>Cancelled</option>';
    echo '</select>';

    echo '<p style="margin-top: 12px;"><label for="assigned_to"><strong>Assigned To</strong></label></p>';
    echo '<select name="assigned_to" id="assigned_to" class="widefat">';
    echo '<option value="">Not assigned</option>';
    echo '<option value="AS"' . selected($assigned_to, 'AS', false) . '>AS</option>';
    echo '<option value="ES"' . selected($assigned_to, 'ES', false) . '>ES</option>';
    echo '<option value="VW"' . selected($assigned_to, 'VW', false) . '>VW</option>';
    echo '<option value="CR"' . selected($assigned_to, 'CR', false) . '>CR</option>';
    echo '</select>';

    echo '<p style="margin-top: 12px;"><label for="followup_notes"><strong>Notes</strong></label></p>';
    echo '<textarea name="followup_notes" id="followup_notes" class="widefat" rows="5">' . esc_textarea($followup_notes) . '</textarea>';
    echo '<p class="description">Use the "Update" button to save changes.</p>';
    
    // Facebook Conversion Leads Integration
    $meta_lead_id = (string) get_post_meta($post->ID, '_submission_meta_lead_id', true);
    $fb_pixel_id = get_option('cta_facebook_pixel_id', '');
    $fb_enabled = get_option('cta_facebook_conversions_api_enabled', 1);
    
    if (!empty($fb_pixel_id) && $fb_enabled) :
        echo '<hr style="margin: 20px 0;">';
        echo '<h3 style="margin: 0 0 12px 0; font-size: 14px;">Facebook Conversion Leads</h3>';
        
        echo '<p><label for="meta_lead_id"><strong>Meta Lead ID</strong></label></p>';
        echo '<input type="text" name="meta_lead_id" id="meta_lead_id" class="widefat" value="' . esc_attr($meta_lead_id) . '" placeholder="12345678901234567" pattern="[0-9]{15,17}">';
        echo '<p class="description">15-17 digit Lead ID from Facebook Lead Ads. Required for offline conversion tracking.</p>';
        
        if (!empty($meta_lead_id)) :
            echo '<div style="margin-top: 15px; padding: 12px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">';
            echo '<p style="margin: 0 0 10px 0;"><strong>Send Conversion Event</strong></p>';
            echo '<form method="post" style="margin: 0;">';
            wp_nonce_field('cta_send_facebook_conversion_' . $post->ID, 'cta_send_facebook_conversion_nonce');
            echo '<select name="conversion_event_name" style="width: 100%; margin-bottom: 8px;">';
            echo '<option value="Lead">Lead (Qualified)</option>';
            echo '<option value="Appointment Set">Appointment Set</option>';
            echo '<option value="Sale Completed">Sale Completed</option>';
            echo '<option value="Purchase">Purchase</option>';
            echo '</select>';
            echo '<button type="submit" name="send_facebook_conversion" class="button button-secondary" style="width: 100%;" onclick="return confirm(\'Send this conversion event to Facebook?\');">Send Conversion Event</button>';
            echo '</form>';
            echo '<p class="description" style="margin: 8px 0 0 0; font-size: 11px;">Conversion events are automatically sent when status changes to: Interested → Lead, Booked → Appointment Set, Paid/Completed → Sale Completed</p>';
            echo '</div>';
        else :
            echo '<p class="description" style="margin-top: 8px; color: #d63638;">Enter Meta Lead ID to enable offline conversion tracking.</p>';
        endif;
    endif;
}

/**
 * Metabox: Activity Log
 */
function cta_form_submission_activity_log_callback($post) {
    if (!current_user_can('edit_posts')) {
        echo '<p>You do not have permission to view this content.</p>';
        return;
    }

    $activity_log = get_post_meta($post->ID, '_submission_activity_log', true);
    if (!is_array($activity_log)) {
        $activity_log = [];
    }

    // Sort by timestamp (newest first)
    usort($activity_log, function($a, $b) {
        return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
    });

    if (empty($activity_log)) {
        echo '<p style="color: #787c82; font-style: italic;">No activity recorded yet.</p>';
        return;
    }

    echo '<style>
        .cta-activity-log { list-style: none; margin: 0; padding: 0; }
        .cta-activity-log li { padding: 12px 0; border-bottom: 1px solid #dcdcde; position: relative; padding-left: 30px; }
        .cta-activity-log li:last-child { border-bottom: none; }
        .cta-activity-log-icon { position: absolute; left: 0; top: 12px; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; }
        .cta-activity-log-icon.assigned { background: #2271b1; color: white; }
        .cta-activity-log-icon.status { background: #00a32a; color: white; }
        .cta-activity-log-icon.note { background: #f0b849; color: white; }
        .cta-activity-log-icon.created { background: #72aee6; color: white; }
        .cta-activity-log-time { font-size: 12px; color: #787c82; }
        .cta-activity-log-text { margin: 4px 0 0 0; }
        .cta-activity-log-detail { font-size: 12px; color: #646970; margin: 4px 0 0 0; background: #f6f7f7; padding: 8px; border-radius: 4px; }
    </style>';

    echo '<ul class="cta-activity-log">';
    foreach ($activity_log as $entry) {
        $type = $entry['type'] ?? 'other';
        $user_name = $entry['user_name'] ?? 'System';
        $timestamp = $entry['timestamp'] ?? time();
        $message = $entry['message'] ?? '';
        $details = $entry['details'] ?? '';

        $icon_class = 'created';
        $icon_char = '●';
        
        switch ($type) {
            case 'assigned':
                $icon_class = 'assigned';
                $icon_char = '👤';
                break;
            case 'status_changed':
                $icon_class = 'status';
                $icon_char = '◆';
                break;
            case 'note_added':
            case 'note_updated':
                $icon_class = 'note';
                $icon_char = '📝';
                break;
        }

        echo '<li>';
        echo '<div class="cta-activity-log-icon ' . esc_attr($icon_class) . '">' . $icon_char . '</div>';
        echo '<div class="cta-activity-log-time">' . esc_html(human_time_diff($timestamp, current_time('timestamp'))) . ' ago</div>';
        echo '<div class="cta-activity-log-text"><strong>' . esc_html($user_name) . '</strong> ' . esc_html($message) . '</div>';
        if ($details) {
            echo '<div class="cta-activity-log-detail">' . esc_html($details) . '</div>';
        }
        echo '</li>';
    }
    echo '</ul>';
}

// Removed dead code: unused "Modern Lead Detail UI" callbacks.

/**
 * Add activity log entry
 */
function cta_add_activity_log($post_id, $type, $message, $details = '') {
    $activity_log = get_post_meta($post_id, '_submission_activity_log', true);
    if (!is_array($activity_log)) {
        $activity_log = [];
    }

    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name ?: $current_user->user_login ?: 'System';

    $activity_log[] = [
        'type' => $type,
        'message' => $message,
        'details' => $details,
        'user_name' => $user_name,
        'user_id' => $current_user->ID,
        'timestamp' => current_time('timestamp'),
    ];

    update_post_meta($post_id, '_submission_activity_log', $activity_log);
}

/**
 * Save follow-up status
 */
function cta_form_submission_save_followup($post_id) {
    // Check nonce
    if (!isset($_POST['cta_form_submission_followup_nonce']) || 
        !wp_verify_nonce($_POST['cta_form_submission_followup_nonce'], 'cta_form_submission_followup')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Check post type
    if (get_post_type($post_id) !== 'form_submission') {
        return;
    }
    
    // Save follow-up status and log change
    if (isset($_POST['followup_status'])) {
        $old_status = get_post_meta($post_id, '_submission_followup_status', true);
        $new_status = sanitize_text_field($_POST['followup_status']);
        
        if ($old_status !== $new_status) {
            if ($new_status) {
                update_post_meta($post_id, '_submission_followup_status', $new_status);
                
                // Log status change
                $status_labels = [
                    'new' => 'New Enquiry',
                    'needed' => 'Requires Follow-up',
                    'contacted-email' => 'Contacted (Email)',
                    'contacted-phone' => 'Contacted (Phone)',
                    'contacted-both' => 'Contacted',
                    'in-progress' => 'Interested / Enquiring',
                    'booked' => 'Booked',
                    'paid' => 'Booked & Paid',
                    'completed' => 'Attended',
                    'cancelled' => 'Cancelled',
                ];
                $old_label = $old_status ? ($status_labels[$old_status] ?? $old_status) : 'None';
                $new_label = $status_labels[$new_status] ?? $new_status;
                cta_add_activity_log($post_id, 'status_changed', 'changed status', "From: {$old_label} → To: {$new_label}");
                
                // Trigger Facebook offline conversion tracking
                do_action('cta_form_submission_status_changed', $post_id, $old_status, $new_status);
            } else {
                delete_post_meta($post_id, '_submission_followup_status');
            }
        }
    }
    
    // Save Meta Lead ID
    if (isset($_POST['meta_lead_id'])) {
        $lead_id = sanitize_text_field($_POST['meta_lead_id']);
        // Validate Lead ID format (15-17 digits)
        if (empty($lead_id) || preg_match('/^\d{15,17}$/', $lead_id)) {
            if ($lead_id) {
                update_post_meta($post_id, '_submission_meta_lead_id', $lead_id);
            } else {
                delete_post_meta($post_id, '_submission_meta_lead_id');
            }
        }
    }
    
    // Handle manual offline conversion event
    if (isset($_POST['send_facebook_conversion']) && isset($_POST['cta_send_facebook_conversion_nonce']) && check_admin_referer('cta_send_facebook_conversion_' . $post_id, 'cta_send_facebook_conversion_nonce')) {
        $lead_id = get_post_meta($post_id, '_submission_meta_lead_id', true);
        $event_name = sanitize_text_field($_POST['conversion_event_name'] ?? 'Lead');
        
        if (empty($lead_id)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Meta Lead ID is required to send conversion events. Please enter the Lead ID first.</p></div>';
            });
        } else {
            $result = cta_send_manual_offline_conversion($post_id, $event_name);
            
            if (is_wp_error($result)) {
                add_action('admin_notices', function() use ($result) {
                    echo '<div class="notice notice-error"><p>Failed to send conversion event: ' . esc_html($result->get_error_message()) . '</p></div>';
                });
            } else {
                // Log the conversion event
                cta_add_activity_log($post_id, 'facebook_conversion_sent', 'sent Facebook conversion', "Event: {$event_name}");
                add_action('admin_notices', function() use ($event_name) {
                    echo '<div class="notice notice-success is-dismissible"><p>Conversion event "' . esc_html($event_name) . '" sent successfully to Facebook.</p></div>';
                });
            }
        }
    }
    
    // Save follow-up notes and log change
    if (isset($_POST['followup_notes'])) {
        $old_notes = get_post_meta($post_id, '_submission_followup_notes', true);
        $new_notes = sanitize_textarea_field($_POST['followup_notes']);
        
        if ($old_notes !== $new_notes) {
            if ($new_notes) {
                update_post_meta($post_id, '_submission_followup_notes', $new_notes);
                
                // Log note change
                if (!$old_notes) {
                    cta_add_activity_log($post_id, 'note_added', 'added a note', wp_trim_words($new_notes, 20));
                } else {
                    cta_add_activity_log($post_id, 'note_updated', 'updated notes', wp_trim_words($new_notes, 20));
                }
            } else {
                delete_post_meta($post_id, '_submission_followup_notes');
            }
        }
    }
    
    // Save follow-up date
    if (isset($_POST['followup_date'])) {
        $date = sanitize_text_field($_POST['followup_date']);
        if ($date) {
            update_post_meta($post_id, '_submission_followup_date', $date);
        } else {
            delete_post_meta($post_id, '_submission_followup_date');
        }
    }
    
    // Save lead source
    if (isset($_POST['lead_source'])) {
        $lead_source = sanitize_text_field($_POST['lead_source']);
        if ($lead_source) {
            update_post_meta($post_id, '_submission_lead_source', $lead_source);
        } else {
            delete_post_meta($post_id, '_submission_lead_source');
        }
    }
    
    // Save assigned to and log change
    if (isset($_POST['assigned_to'])) {
        $old_assigned = get_post_meta($post_id, '_submission_assigned_to', true);
        $new_assigned = sanitize_text_field($_POST['assigned_to']);
        
        if ($old_assigned !== $new_assigned) {
            if ($new_assigned) {
                update_post_meta($post_id, '_submission_assigned_to', $new_assigned);
                
                // Log assignment
                $assignee_names = [
                    'AS' => 'Adele',
                    'ES' => 'Ellie',
                    'VW' => 'Victoria',
                    'CR' => 'Chloe',
                ];
                $assignee_name = $assignee_names[$new_assigned] ?? $new_assigned;
                cta_add_activity_log($post_id, 'assigned', 'assigned to ' . $assignee_name);
            } else {
                delete_post_meta($post_id, '_submission_assigned_to');
                cta_add_activity_log($post_id, 'assigned', 'unassigned from lead');
            }
        }
    }
}
add_action('save_post', 'cta_form_submission_save_followup');

/**
 * Remove default publish box actions (submissions shouldn't be editable)
 */
function cta_form_submission_remove_publish_actions() {
    // Intentionally left blank: keep the core Publish/Update box so admins can save notes/status.
}
add_action('admin_menu', 'cta_form_submission_remove_publish_actions');


/**
 * Enqueue CRM inline editing script with localized data
 */
function cta_form_submission_enqueue_scripts($hook) {
    global $typenow;
    
    // Add spacing improvements for form submission pages
    $page = isset($_GET['page']) ? (string) $_GET['page'] : '';
    if ($typenow === 'form_submission' || strpos($page, 'cta-form-submission') === 0) {
        wp_add_inline_style('wp-admin', '
            /* Give postbox content proper breathing room */
            .postbox .inside {
                padding: 20px;
            }
            
            /* Consistent postbox spacing */
            .postbox {
                margin-bottom: 20px;
            }
            
            /* Form elements need space */
            .postbox .inside label {
                display: block;
                margin-bottom: 6px;
                font-weight: 500;
            }
            
            .postbox .inside input[type="text"],
            .postbox .inside input[type="email"],
            .postbox .inside input[type="file"],
            .postbox .inside select,
            .postbox .inside textarea {
                margin-bottom: 12px;
            }
            
            .postbox .inside .description {
                margin-top: 6px;
                margin-bottom: 12px;
            }
            
            /* Table spacing */
            .wp-list-table th,
            .wp-list-table td {
                padding: 12px 10px;
            }
        ');
    }
    
    if ($typenow === 'form_submission' && $hook === 'edit.php') {
        // SECURITY: Only load for authenticated admin users
        if (!cta_verify_admin_access('edit_posts')) {
                    return;
        }
        
        wp_enqueue_script(
            'cta-crm-inline-editing',
            get_template_directory_uri() . '/assets/js/crm-inline-editing.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        // SECURITY: Localize script instead of inline nonces
        wp_localize_script('cta-crm-inline-editing', 'ctaCRM', [
            'nonces' => [
                'assignment' => wp_create_nonce('cta_update_submission_assigned'),
                'status' => wp_create_nonce('cta_update_submission_status'),
                'notes' => wp_create_nonce('cta_update_submission_notes'),
                'lead_source' => wp_create_nonce('cta_update_submission_lead_source'),
                'resend_email' => wp_create_nonce('cta_resend_submission_email'),
            ],
            'ajaxurl' => admin_url('admin-ajax.php'),
        ]);
        
        // Add resend email inline script
        wp_add_inline_script('cta-crm-inline-editing', "
            jQuery(document).ready(function($) {
                $(document).on('click', '.cta-resend-email', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var postId = button.data('post-id');
                    var icon = button.find('.dashicons');
                    
                    if (button.prop('disabled')) return;
                    
                    if (!confirm('Resend confirmation email for this submission?')) {
                        return;
                    }
                    
                    button.prop('disabled', true);
                    icon.addClass('spin-animation');
                    
                    // Add CSS animation for spinning icon
                    if (!$('#cta-spin-animation').length) {
                        $('<style id=\"cta-spin-animation\">@keyframes cta-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } } .spin-animation { animation: cta-spin 1s linear infinite; }</style>').appendTo('head');
                    }
                    
                    $.ajax({
                        url: ctaCRM.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cta_resend_submission_email',
                            post_id: postId,
                            nonce: ctaCRM.nonces.resend_email
                        },
                        success: function(response) {
                            icon.removeClass('spin-animation');
                            if (response.success) {
                                // Remove the button after successful send
                                button.fadeOut(300, function() {
                                    button.remove();
                                });
                                // Update email status column if visible
                                var emailStatusWrapper = button.closest('tr').find('.cta-email-status-wrapper');
                                if (emailStatusWrapper.length) {
                                    emailStatusWrapper.html('<span class=\"cta-admin-email-sent\">Sent</span>');
                                }
                            } else {
                                alert('Error: ' + (response.data.message || 'Failed to send email'));
                                button.prop('disabled', false);
                            }
                        },
                        error: function() {
                            icon.removeClass('spin-animation');
                            alert('Error: Failed to send email');
                            button.prop('disabled', false);
                        }
                    });
                });
            });
        ");
    }
}
add_action('admin_enqueue_scripts', 'cta_form_submission_enqueue_scripts');

/**
 * AJAX handler for updating submission status
 */
function cta_update_submission_status_ajax() {
    check_ajax_referer('cta_update_submission_status', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    // SECURITY: Check capability
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    $status = sanitize_text_field($_POST['status'] ?? '');
    
    // SECURITY: Validate post exists and is correct type
    if (!$post_id || get_post_type($post_id) !== 'form_submission') {
        wp_send_json_error(['message' => 'Invalid submission'], 400);
    }
    
    // SECURITY: Verify user can edit this specific post
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => 'Cannot edit this submission'], 403);
    }
    
    $old_status = get_post_meta($post_id, '_submission_followup_status', true);
    
    if ($status) {
        update_post_meta($post_id, '_submission_followup_status', $status);
        
        // Log status change
        if ($old_status !== $status) {
            $status_labels = [
                'new' => 'New Enquiry',
                'needed' => 'Requires Follow-up',
                'contacted-email' => 'Contacted (Email)',
                'contacted-phone' => 'Contacted (Phone)',
                'contacted-both' => 'Contacted',
                'in-progress' => 'Interested / Enquiring',
                'booked' => 'Booked',
                'paid' => 'Booked & Paid',
                'completed' => 'Attended',
                'cancelled' => 'Cancelled',
            ];
            $old_label = $old_status ? ($status_labels[$old_status] ?? $old_status) : 'None';
            $new_label = $status_labels[$status] ?? $status;
            cta_add_activity_log($post_id, 'status_changed', 'changed status', "From: {$old_label} → To: {$new_label}");
        }
        
        // If cancelled, also set archived date
        if ($status === 'cancelled') {
            update_post_meta($post_id, '_submission_archived_date', current_time('mysql'));
        } else {
            // If restoring from cancelled, remove archived date
            $current_status = get_post_meta($post_id, '_submission_followup_status', true);
            if ($current_status !== 'cancelled') {
                delete_post_meta($post_id, '_submission_archived_date');
            }
        }
    } else {
        delete_post_meta($post_id, '_submission_followup_status');
        delete_post_meta($post_id, '_submission_archived_date');
    }
    
    wp_send_json_success(['message' => 'Status updated', 'status' => $status]);
}
add_action('wp_ajax_cta_update_submission_status', 'cta_update_submission_status_ajax');

/**
 * AJAX handler for updating submission notes
 */
function cta_update_submission_notes_ajax() {
    check_ajax_referer('cta_update_submission_notes', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    // SECURITY: Check capability
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');
    
    // SECURITY: Validate post exists and is correct type
    if (!$post_id || get_post_type($post_id) !== 'form_submission') {
        wp_send_json_error(['message' => 'Invalid submission'], 400);
    }
    
    // SECURITY: Verify user can edit this specific post
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => 'Cannot edit this submission'], 403);
    }
    
    $old_notes = get_post_meta($post_id, '_submission_followup_notes', true);
    
    if ($notes) {
        update_post_meta($post_id, '_submission_followup_notes', $notes);
        
        // Log note change
        if ($old_notes !== $notes) {
            if (!$old_notes) {
                cta_add_activity_log($post_id, 'note_added', 'added a note', wp_trim_words($notes, 20));
            } else {
                cta_add_activity_log($post_id, 'note_updated', 'updated notes', wp_trim_words($notes, 20));
            }
        }
    } else {
        delete_post_meta($post_id, '_submission_followup_notes');
    }
    
    wp_send_json_success(['message' => 'Notes updated']);
}
add_action('wp_ajax_cta_update_submission_notes', 'cta_update_submission_notes_ajax');

/**
 * AJAX handler for updating submission assigned to
 */
function cta_update_submission_assigned_ajax() {
    check_ajax_referer('cta_update_submission_assigned', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    // SECURITY: Check capability
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    $assigned_to = sanitize_text_field($_POST['assigned_to'] ?? '');
    $send_email = isset($_POST['send_email']) && $_POST['send_email'] === '1';
    
    // SECURITY: Validate post exists and is correct type
    if (!$post_id || get_post_type($post_id) !== 'form_submission') {
        wp_send_json_error(['message' => 'Invalid submission'], 400);
    }
    
    // SECURITY: Verify user can edit this specific post
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => 'Cannot edit this submission'], 403);
    }
    
    // Validate assignee is in allowed list
    $allowed_assignees = ['AS', 'ES', 'VW', 'CR'];
    if ($assigned_to && !in_array($assigned_to, $allowed_assignees)) {
        wp_send_json_error(['message' => 'Invalid assignee']);
    }
    
    $email_sent = false;
    
    // Update assignment
    if ($assigned_to) {
        $old_assigned = get_post_meta($post_id, '_submission_assigned_to', true);
        update_post_meta($post_id, '_submission_assigned_to', $assigned_to);
        
        // Log assignment if changed
        if ($old_assigned !== $assigned_to) {
            $assignee_names = [
                'AS' => 'Adele',
                'ES' => 'Ellie',
                'VW' => 'Victoria',
                'CR' => 'Chloe',
            ];
            $assignee_name = $assignee_names[$assigned_to] ?? $assigned_to;
            cta_add_activity_log($post_id, 'assigned', 'assigned to ' . $assignee_name);
        }
        
        // Send email notification if requested
        if ($send_email) {
            $email_sent = cta_send_assignment_notification_email($post_id, $assigned_to);
        }
    } else {
        delete_post_meta($post_id, '_submission_assigned_to');
    }
    
    wp_send_json_success([
        'message' => 'Assignment updated',
        'assigned_to' => $assigned_to,
        'email_sent' => $email_sent
    ]);
}
add_action('wp_ajax_cta_update_submission_assigned', 'cta_update_submission_assigned_ajax');

/**
 * AJAX handler for updating submission lead source
 */
function cta_update_submission_lead_source_ajax() {
    check_ajax_referer('cta_update_submission_lead_source', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    // SECURITY: Check capability
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    $lead_source = sanitize_text_field($_POST['lead_source'] ?? '');
    
    // SECURITY: Validate post exists and is correct type
    if (!$post_id || get_post_type($post_id) !== 'form_submission') {
        wp_send_json_error(['message' => 'Invalid submission'], 400);
    }
    
    // SECURITY: Verify user can edit this specific post
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => 'Cannot edit this submission'], 403);
    }
    
    // Validate lead source is in allowed list
    $allowed_sources = ['website', 'google-ads', 'facebook', 'referral', 'direct', 'other'];
    if ($lead_source && !in_array($lead_source, $allowed_sources)) {
        wp_send_json_error(['message' => 'Invalid lead source']);
    }
    
    // Update lead source
    if ($lead_source) {
        update_post_meta($post_id, '_submission_lead_source', $lead_source);
    } else {
        delete_post_meta($post_id, '_submission_lead_source');
    }
    
    wp_send_json_success([
        'message' => 'Lead source updated',
        'lead_source' => $lead_source
    ]);
}
add_action('wp_ajax_cta_update_submission_lead_source', 'cta_update_submission_lead_source_ajax');

/**
 * Send email notification when a lead is assigned
 */
function cta_send_assignment_notification_email($post_id, $assignee) {
    // Get submission details
    $name = get_post_meta($post_id, '_submission_name', true);
    $email = get_post_meta($post_id, '_submission_email', true);
    $phone = get_post_meta($post_id, '_submission_phone', true);
    $message = get_post_meta($post_id, '_submission_message', true);
    $page_url = get_post_meta($post_id, '_submission_page_url', true);
    $form_type_terms = get_the_terms($post_id, 'form_type');
    $form_type = $form_type_terms && !is_wp_error($form_type_terms) ? $form_type_terms[0]->name : 'Form Submission';
    
    // Assignee email mapping
    $assignee_emails = [
        'AS' => 'adele@continuitytrainingacademy.co.uk',
        'ES' => 'ellie@continuitytrainingacademy.co.uk',
        'VW' => 'victoria@continuitytrainingacademy.co.uk',
        'CR' => 'chloe@continuitytrainingacademy.co.uk',
    ];
    
    // Get assignee email, fallback to enquiries if not found
    $to = $assignee_emails[$assignee] ?? (defined('CTA_ENQUIRIES_EMAIL') ? CTA_ENQUIRIES_EMAIL : get_option('admin_email'));
    
    // Extract name from email (part before @) and capitalize first letter
    $assignee_name = '';
    if ($to && strpos($to, '@') !== false) {
        $email_parts = explode('@', $to);
        $assignee_name = ucfirst($email_parts[0]);
    } else {
        $assignee_name = $assignee;
    }
    
    // Get submission URL
    $submission_url = admin_url('post.php?post=' . $post_id . '&action=edit');
    
    // Build email
    $subject = sprintf('[CTA Lead Assigned] New lead assigned to %s', $assignee);
    
    $body = "Hello {$assignee_name},\n\n";
    $body .= "A new lead has been assigned to you:\n\n";
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $body .= "LEAD DETAILS\n";
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $body .= "Name: {$name}\n";
    if ($email) {
        $body .= "Email: {$email}\n";
    }
    if ($phone) {
        $body .= "Phone: {$phone}\n";
    }
    $body .= "Form Type: {$form_type}\n";
    if ($message) {
        $body .= "\nMessage:\n{$message}\n";
    }
    if ($page_url) {
        $body .= "\nSubmitted from: {$page_url}\n";
    }
    $body .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $body .= "View full submission: {$submission_url}\n";
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $body .= "Please follow up with this lead as soon as possible.\n\n";
    $body .= "Best regards,\n";
    $body .= "Continuity Training Academy";
    
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
    ];
    
    // Add Reply-To if we have the lead's email
    if ($email) {
        $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
    }
    
    $sent = wp_mail($to, $subject, $body, $headers);
    
    return $sent;
}

/**
 * AJAX handler for resending submission confirmation email
 */
function cta_resend_submission_email_ajax() {
    check_ajax_referer('cta_resend_submission_email', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    // SECURITY: Check capability
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    
    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid submission ID']);
    }
    
    // Get submission data
    $email = get_post_meta($post_id, '_submission_email', true);
    $name = get_post_meta($post_id, '_submission_name', true);
    $form_type = get_post_meta($post_id, '_submission_form_type', true);
    
    if (!$email) {
        wp_send_json_error(['message' => 'No email address found for this submission']);
    }
    
    // Get all submission data
    $phone = get_post_meta($post_id, '_submission_phone', true);
    $message = get_post_meta($post_id, '_submission_message', true);
    $form_data = get_post_meta($post_id, '_submission_form_data', true);
    $submission_date = get_the_date('j F Y \a\t g:i a', $post_id);
    
    // Send admin notification email
    $contact = cta_get_contact_info();
    $admin_email = $contact['email'];
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    
    // Form type label
    $form_type_label = ucwords(str_replace('-', ' ', $form_type));
    
    $subject = 'New Form Submission: ' . $form_type_label;
    
    $body = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
    $body .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';
    $body .= '<h2 style="color: #2c3e50;">New Form Submission</h2>';
    $body .= '<p><strong>Form Type:</strong> ' . esc_html($form_type_label) . '</p>';
    $body .= '<p><strong>Submitted:</strong> ' . esc_html($submission_date) . '</p>';
    $body .= '<hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">';
    $body .= '<h3 style="color: #2c3e50;">Contact Details</h3>';
    $body .= '<p><strong>Name:</strong> ' . esc_html($name) . '</p>';
    $body .= '<p><strong>Email:</strong> <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></p>';
    if ($phone) {
        $body .= '<p><strong>Phone:</strong> <a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></p>';
    }
    if ($message) {
        $body .= '<hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">';
        $body .= '<h3 style="color: #2c3e50;">Message</h3>';
        $body .= '<p>' . nl2br(esc_html($message)) . '</p>';
    }
    if ($form_data && is_array($form_data)) {
        $body .= '<hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">';
        $body .= '<h3 style="color: #2c3e50;">Additional Details</h3>';
        foreach ($form_data as $key => $value) {
            if (!in_array($key, ['name', 'email', 'phone', 'message']) && $value) {
                $label = ucwords(str_replace(['_', '-'], ' ', $key));
                $body .= '<p><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</p>';
            }
        }
    }
    $body .= '<hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">';
    $body .= '<p><a href="' . esc_url(admin_url('post.php?post=' . $post_id . '&action=edit')) . '" style="background: #2271b1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">View in WordPress</a></p>';
    $body .= '</div></body></html>';
    
    $sent = wp_mail($admin_email, $subject, $body, $headers);
    
    if ($sent) {
        // Update meta to mark as sent
        update_post_meta($post_id, '_submission_email_sent', 'yes');
        delete_post_meta($post_id, '_submission_email_error');
        
        wp_send_json_success(['message' => 'Email sent successfully']);
    } else {
        // Mark as failed
        update_post_meta($post_id, '_submission_email_sent', 'no');
        update_post_meta($post_id, '_submission_email_error', 'Failed to send via wp_mail');
        
        wp_send_json_error(['message' => 'Failed to send email']);
    }
}
add_action('wp_ajax_cta_resend_submission_email', 'cta_resend_submission_email_ajax');
