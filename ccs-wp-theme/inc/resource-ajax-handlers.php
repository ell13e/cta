<?php
/**
 * Resource download request AJAX handler: captures lead + emails secure download link.
 *
 * @package ccs-theme
 */
defined('ABSPATH') || exit;

function ccs_ajax_request_resource_download() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccs_nonce')) {
        wp_send_json_error([
            'message' => 'Security verification failed. Please refresh the page and try again.',
            'code' => 'nonce_failed',
        ], 403);
    }

    $resource_id = absint($_POST['resource_id'] ?? 0);
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $date_of_birth = sanitize_text_field($_POST['date_of_birth'] ?? '');
    $consent = isset($_POST['consent']) && $_POST['consent'] === 'true';

    $errors = [];

    if (!$resource_id || get_post_type($resource_id) !== 'ccs_resource') {
        $errors['resource_id'] = 'Please select a resource to download.';
    }

    if ($first_name === '') {
        $errors['first_name'] = 'First name is required.';
    }
    if ($last_name === '') {
        $errors['last_name'] = 'Last name is required.';
    }
    if ($email === '' || !is_email($email)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (!$consent) {
        $errors['consent'] = 'Please confirm your consent to receive this resource and updates.';
    }

    if ($phone !== '' && function_exists('ccs_validate_uk_phone')) {
        $pv = ccs_validate_uk_phone($phone);
        if (isset($pv['valid']) && !$pv['valid']) {
            $errors['phone'] = $pv['error'] ?? 'Please enter a valid phone number.';
        }
    }

    if ($date_of_birth !== '') {
        $dob_ts = strtotime($date_of_birth);
        if ($dob_ts === false || $dob_ts > time()) {
            $errors['date_of_birth'] = 'Please enter a valid date of birth.';
        }
    }

    if (!empty($errors)) {
        wp_send_json_error([
            'message' => 'Please correct the errors below.',
            'errors' => $errors,
            'code' => 'validation_failed',
        ], 400);
    }

    $file_id = (int) get_post_meta($resource_id, '_ccs_resource_file_id', true);
    if (!$file_id) {
        wp_send_json_error([
            'message' => 'This resource is not available yet.',
            'code' => 'resource_unavailable',
        ], 404);
    }

    $expiry_days = (int) get_post_meta($resource_id, '_ccs_resource_expiry_days', true);
    if ($expiry_days <= 0) $expiry_days = 7;
    if ($expiry_days > 30) $expiry_days = 30;
    $expires_at_ts = time() + ($expiry_days * DAY_IN_SECONDS);

    $token = function_exists('ccs_resource_generate_download_token')
        ? ccs_resource_generate_download_token($resource_id, $email, $expires_at_ts)
        : '';

    if ($token === '') {
        wp_send_json_error([
            'message' => 'Unable to generate a download link. Please try again.',
            'code' => 'token_failed',
        ], 500);
    }

    $download_link = add_query_arg('token', rawurlencode($token), home_url('/resource-download/'));

    // Store lead in DB (best effort)
    global $wpdb;
    $table = $wpdb->prefix . 'ccs_resource_downloads';
    $token_hash = hash('sha256', $token);

    $wpdb->insert($table, [
        'resource_id' => $resource_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone ?: null,
        'date_of_birth' => $date_of_birth ?: null,
        'consent' => $consent ? 1 : 0,
        'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : null,
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field((string) $_SERVER['HTTP_USER_AGENT']) : null,
        'downloaded_at' => current_time('mysql'),
        'email_sent' => 0,
        'token_hash' => $token_hash,
        'token_expires_at' => gmdate('Y-m-d H:i:s', $expires_at_ts),
    ], [
        '%d','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d','%s','%s'
    ]);

    // Build email
    $resource_name = get_the_title($resource_id);
    $subject = (string) get_post_meta($resource_id, '_ccs_resource_email_subject', true);
    $body = (string) get_post_meta($resource_id, '_ccs_resource_email_body', true);
    if ($subject === '') $subject = 'Your {{resource_name}} from {{site_name}}';
    if ($body === '') {
        $body = "Hi {{first_name}},\n\nThanks for requesting {{resource_name}}.\n\nDownload: {{download_link}}\n\nThis link expires in {{expiry_days}} days.";
    }

    $replacements = [
        '{{first_name}}' => $first_name,
        '{{last_name}}' => $last_name,
        '{{email}}' => $email,
        '{{resource_name}}' => $resource_name,
        '{{download_link}}' => $download_link,
        '{{expiry_days}}' => (string) $expiry_days,
        '{{site_name}}' => get_bloginfo('name'),
    ];
    $subject_out = strtr($subject, $replacements);
    $body_out = strtr($body, $replacements);

    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    $sent = wp_mail($email, $subject_out, $body_out, $headers);

    // Add to newsletter if consent given
    if ($consent && function_exists('ccs_newsletter_add_subscriber')) {
        ccs_newsletter_add_subscriber([
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'source' => 'Resource Download: ' . $resource_name,
            'consent' => true,
            'consent_date' => current_time('mysql'),
            'status' => 'active',
        ]);
    }
    
    if ($sent) {
        $wpdb->update($table, [
            'email_sent' => 1,
            'email_sent_at' => current_time('mysql'),
        ], ['token_hash' => $token_hash], ['%d','%s'], ['%s']);
    }

    wp_send_json_success([
        'message' => 'Thanks! We’ve emailed your download link to ' . $email . '.',
        'download_link' => $download_link, // optional: can be used for immediate download
    ]);
}
add_action('wp_ajax_ccs_request_resource_download', 'ccs_ajax_request_resource_download');
add_action('wp_ajax_nopriv_ccs_request_resource_download', 'ccs_ajax_request_resource_download');

