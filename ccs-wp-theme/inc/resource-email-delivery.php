<?php
/**
 * Resource download link generation + secure download endpoint.
 *
 * @package ccs-theme
 */
defined('ABSPATH') || exit;

function ccs_resource_add_query_vars($vars) {
    $vars[] = 'ccs_resource_download';
    return $vars;
}
add_filter('query_vars', 'ccs_resource_add_query_vars');

function ccs_resource_add_rewrite_rule() {
    add_rewrite_rule('^resource-download/?$', 'index.php?ccs_resource_download=1', 'top');
}
add_action('init', 'ccs_resource_add_rewrite_rule');

function ccs_resource_flush_rewrite_rules() {
    ccs_resource_add_rewrite_rule();
    flush_rewrite_rules(false);
}
add_action('after_switch_theme', 'ccs_resource_flush_rewrite_rules', 50);

/**
 * Generate a signed token for a resource download.
 */
function ccs_resource_generate_download_token($resource_id, $email, $expires_at_ts) {
    $payload = [
        'rid' => (int) $resource_id,
        'email' => (string) $email,
        'exp' => (int) $expires_at_ts,
        'iat' => (int) time(),
        'v' => 1,
    ];

    $payload_json = wp_json_encode($payload);
    $payload_b64 = rtrim(strtr(base64_encode($payload_json), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $payload_b64, wp_salt('ccs_resource_download'));
    return $payload_b64 . '.' . $sig;
}

/**
 * Verify token and return payload array or false.
 */
function ccs_resource_verify_download_token($token) {
    if (!is_string($token) || strpos($token, '.') === false) {
        return false;
    }
    [$payload_b64, $sig] = explode('.', $token, 2);
    if ($payload_b64 === '' || $sig === '') {
        return false;
    }

    $expected = hash_hmac('sha256', $payload_b64, wp_salt('ccs_resource_download'));
    if (!hash_equals($expected, $sig)) {
        return false;
    }

    $payload_json = base64_decode(strtr($payload_b64, '-_', '+/'));
    if (!$payload_json) {
        return false;
    }
    $payload = json_decode($payload_json, true);
    if (!is_array($payload) || empty($payload['rid']) || empty($payload['email']) || empty($payload['exp'])) {
        return false;
    }
    if ((int) $payload['exp'] < time()) {
        return false;
    }
    return $payload;
}

/**
 * Serve resource file if token is valid.
 */
function ccs_resource_download_template_redirect() {
    if ((int) get_query_var('ccs_resource_download') !== 1) {
        return;
    }

    $token = isset($_GET['token']) ? (string) $_GET['token'] : '';
    $payload = ccs_resource_verify_download_token($token);
    if (!$payload) {
        status_header(403);
        nocache_headers();
        wp_die('This download link is invalid or has expired.', 'Download expired', ['response' => 403]);
    }

    $resource_id = (int) $payload['rid'];
    $email = (string) $payload['email'];

    $file_id = (int) get_post_meta($resource_id, '_ccs_resource_file_id', true);
    if (!$file_id) {
        status_header(404);
        wp_die('Resource file not found.', 'Not found', ['response' => 404]);
    }

    $file_path = get_attached_file($file_id);
    if (!$file_path || !file_exists($file_path) || !is_readable($file_path)) {
        status_header(404);
        wp_die('Resource file not available.', 'Not found', ['response' => 404]);
    }

    // Track download (best effort)
    global $wpdb;
    $table = $wpdb->prefix . 'ccs_resource_downloads';
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE $table SET download_count = download_count + 1, last_downloaded_at = %s WHERE resource_id = %d AND email = %s ORDER BY id DESC LIMIT 1",
            current_time('mysql'),
            $resource_id,
            $email
        )
    );

    nocache_headers();
    header('Content-Description: File Transfer');
    header('Content-Type: ' . (function_exists('mime_content_type') ? mime_content_type($file_path) : 'application/octet-stream'));
    header('Content-Disposition: attachment; filename=\"' . basename($file_path) . '\"');
    header('Content-Length: ' . filesize($file_path));

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    readfile($file_path);
    exit;
}
add_action('template_redirect', 'ccs_resource_download_template_redirect', 0);

