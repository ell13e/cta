<?php
/**
 * Facebook Lead Ads Webhook Integration
 * 
 * Receives leads from Facebook Lead Ads and creates form submissions in WordPress.
 * 
 * Webhook endpoint: /wp-json/cta/v1/facebook-lead-ads
 * 
 * Setup in Facebook:
 * 1. Go to Meta Business Suite → Lead Ads → Settings → Integrations
 * 2. Add webhook URL: https://yoursite.com/wp-json/cta/v1/facebook-lead-ads
 * 3. Set verify token (configure in WordPress settings)
 * 4. Subscribe to "leadgen" events
 * 
 * References:
 * - Lead Ads Webhooks: https://developers.facebook.com/docs/graph-api/webhooks/reference/leadgen
 * - Lead Ads API: https://developers.facebook.com/docs/marketing-api/leadgen
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Register webhook settings
 */
function cta_facebook_lead_ads_register_settings() {
    register_setting('cta_api_keys_settings', 'cta_facebook_lead_ads_webhook_enabled', [
        'sanitize_callback' => 'absint',
        'default' => 0,
    ]);
    register_setting('cta_api_keys_settings', 'cta_facebook_lead_ads_verify_token', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => wp_generate_password(32, false),
    ]);
    register_setting('cta_api_keys_settings', 'cta_facebook_lead_ads_form_type', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'meta-lead',
    ]);
    register_setting('cta_api_keys_settings', 'cta_facebook_app_secret', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_fb_webhook_trusted_proxies', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
}
add_action('admin_init', 'cta_facebook_lead_ads_register_settings');

/**
 * Admin notice when webhook debug/HTTP bypass constants are defined (dev only, not for production).
 */
function cta_facebook_webhook_admin_notice_dev_constants() {
    if (!defined('CTA_FB_WEBHOOK_DEBUG') && !defined('CTA_FB_WEBHOOK_ALLOW_HTTP')) {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }
    echo '<div class="notice notice-warning"><p><strong>Development mode active:</strong> Facebook Lead Ads webhook security bypasses are enabled (<code>CTA_FB_WEBHOOK_DEBUG</code> and/or <code>CTA_FB_WEBHOOK_ALLOW_HTTP</code>). Do not use in production.</p></div>';
}
add_action('admin_notices', 'cta_facebook_webhook_admin_notice_dev_constants');

/**
 * Schedule daily cleanup of old processed lead IDs (30-day retention).
 */
function cta_facebook_webhook_schedule_cleanup() {
    if (!wp_next_scheduled('cta_cleanup_old_fb_processed_leads')) {
        wp_schedule_event(time(), 'daily', 'cta_cleanup_old_fb_processed_leads');
    }
}
add_action('init', 'cta_facebook_webhook_schedule_cleanup');

/**
 * Daily cron: remove processed lead entries older than 30 days.
 */
function cta_cleanup_old_fb_processed_leads() {
    $processed = get_option('cta_fb_processed_leads', []);
    if (!is_array($processed)) {
        return;
    }
    $processed = cta_facebook_webhook_normalize_processed_leads($processed);
    $cutoff = time() - (30 * DAY_IN_SECONDS);
    $processed = array_filter($processed, function ($entry) use ($cutoff) {
        return isset($entry['time']) && $entry['time'] >= $cutoff;
    });
    $processed = array_values(array_slice($processed, -1000, 1000));
    update_option('cta_fb_processed_leads', $processed, false);
}

/**
 * Normalize processed leads to [['id' => string, 'time' => int], ...]. Handles legacy format (plain ids).
 *
 * @param array $processed
 * @return array
 */
function cta_facebook_webhook_normalize_processed_leads($processed) {
    if (empty($processed)) {
        return [];
    }
    $first = reset($processed);
    if (is_string($first)) {
        $now = time();
        return array_map(function ($id) use ($now) {
            return ['id' => $id, 'time' => $now];
        }, $processed);
    }
    return $processed;
}

/**
 * Register REST API endpoint for Facebook Lead Ads webhook
 */
function cta_register_facebook_lead_ads_webhook() {
    register_rest_route('cta/v1', '/facebook-lead-ads', [
        'methods' => ['GET', 'POST'],
        'callback' => 'cta_handle_facebook_lead_ads_webhook',
        'permission_callback' => '__return_true', // Webhook validation handled internally
    ]);
}
add_action('rest_api_init', 'cta_register_facebook_lead_ads_webhook');

/**
 * Handle Facebook Lead Ads webhook
 *
 * GET: Webhook verification (Facebook sends challenge). Only GET allowed for verification.
 * POST: Lead data from Facebook. Signature verified, rate limited, payload validated.
 * Other methods: 405.
 *
 * @param WP_REST_Request $request REST request object
 * @return WP_REST_Response|WP_Error
 */
function cta_handle_facebook_lead_ads_webhook($request) {
    $method = $request->get_method();

    if ($method === 'GET') {
        return cta_verify_facebook_webhook($request);
    }

    if ($method !== 'POST') {
        return new WP_Error('invalid_method', 'Only POST allowed for webhook data', ['status' => 405]);
    }

    if (!is_ssl() && !defined('CTA_FB_WEBHOOK_ALLOW_HTTP')) {
        return new WP_Error('https_required', 'Webhook must use HTTPS', ['status' => 403]);
    }

    $enabled = get_option('cta_facebook_lead_ads_webhook_enabled', 0);
    if (!$enabled) {
        return new WP_Error('webhook_disabled', 'Facebook Lead Ads webhook is disabled', ['status' => 503]);
    }

    $signature_error = cta_facebook_webhook_verify_signature($request);
    if (is_wp_error($signature_error)) {
        cta_facebook_webhook_log_request($request, false, 'signature_invalid');
        return $signature_error;
    }

    $rate_limit_error = cta_facebook_webhook_check_rate_limit($request);
    if (is_wp_error($rate_limit_error)) {
        cta_facebook_webhook_log_request($request, false, 'rate_limited');
        return $rate_limit_error;
    }

    return cta_process_facebook_lead($request);
}

/**
 * Verify Facebook webhook (GET request)
 * Facebook sends a challenge to verify the webhook endpoint.
 * PHP may convert query param dots to underscores (e.g. hub_verify_token).
 *
 * @param WP_REST_Request $request REST request object
 * @return WP_REST_Response|WP_Error
 */
function cta_verify_facebook_webhook($request) {
    $mode = $request->get_param('hub.mode') ?: $request->get_param('hub_mode');
    $token = $request->get_param('hub.verify_token') ?: $request->get_param('hub_verify_token');
    $challenge = $request->get_param('hub.challenge') ?: $request->get_param('hub_challenge');
    $expected_token = get_option('cta_facebook_lead_ads_verify_token', '');

    if ($mode === 'subscribe' && $token !== '' && $expected_token !== '' && hash_equals((string) $expected_token, (string) $token)) {
        return new WP_REST_Response($challenge !== null && $challenge !== '' ? $challenge : 'ok', 200);
    }

    return new WP_Error('verification_failed', 'Webhook verification failed', ['status' => 403]);
}

/**
 * Verify X-Hub-Signature-256 on POST requests (proves request is from Facebook).
 * Uses raw request body and App Secret. Timing-safe comparison.
 *
 * @param WP_REST_Request $request REST request object
 * @return true|WP_Error True if valid or app secret not set (skip verify); WP_Error on invalid signature
 */
function cta_facebook_webhook_verify_signature($request) {
    $app_secret = get_option('cta_facebook_app_secret', '');
    if ($app_secret === '') {
        if (defined('CTA_FB_WEBHOOK_DEBUG')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CTA FB Webhook: App Secret not set; CTA_FB_WEBHOOK_DEBUG allows bypass (dev only).');
            }
            return true;
        }
        error_log('CTA FB Webhook: App Secret not configured - BLOCKING request');
        return new WP_Error(
            'no_app_secret',
            'Webhook requires App Secret to be configured',
            ['status' => 503]
        );
    }

    $signature = $request->get_header('X-Hub-Signature-256');
    if ($signature === null || $signature === '') {
        return new WP_Error('missing_signature', 'X-Hub-Signature-256 header required', ['status' => 401]);
    }

    $raw_body = $request->get_body();
    if (!is_string($raw_body)) {
        return new WP_Error('invalid_body', 'Could not read request body', ['status' => 400]);
    }

    $expected = 'sha256=' . hash_hmac('sha256', $raw_body, $app_secret);
    if (!hash_equals($expected, $signature)) {
        return new WP_Error('invalid_signature', 'Signature verification failed', ['status' => 403]);
    }

    return true;
}

/**
 * Rate limit webhook POSTs by IP to reduce abuse.
 * Limit: 60 requests per IP per minute.
 *
 * @param WP_REST_Request $request REST request object
 * @return true|WP_Error
 */
function cta_facebook_webhook_check_rate_limit($request) {
    $ip = cta_facebook_webhook_client_ip();
    $key = 'cta_fb_webhook_rl_' . md5($ip);
    $window = 60; // seconds
    $max_per_window = 60;

    $data = get_transient($key);
    if ($data === false) {
        $data = ['count' => 0, 'start' => time()];
    }
    if (time() - $data['start'] > $window) {
        $data = ['count' => 0, 'start' => time()];
    }
    $data['count']++;
    set_transient($key, $data, $window + 10);

    if ($data['count'] > $max_per_window) {
        return new WP_Error('rate_limited', 'Too many requests', ['status' => 429]);
    }

    return true;
}

/**
 * Get client IP for rate limiting and logging.
 * Only trusts X-Forwarded-For when REMOTE_ADDR is in the trusted proxies list (prevents spoofing).
 *
 * @return string
 */
function cta_facebook_webhook_client_ip() {
    $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $trusted = get_option('cta_fb_webhook_trusted_proxies', '');
    $trusted_ips = array_filter(array_map('trim', explode(',', $trusted)));
    if (!empty($trusted_ips) && in_array($remote_addr, $trusted_ips, true) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $list = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        return $list[0];
    }
    return $remote_addr !== '' ? $remote_addr : '0.0.0.0';
}

/**
 * Log webhook request for monitoring and abuse detection.
 * Keeps last 100 entries to limit option size. For long-term logging, use a custom DB table.
 *
 * @param WP_REST_Request $request REST request object
 * @param bool            $success Whether the request was processed successfully
 * @param string          $note    Optional note (e.g. 'signature_invalid', 'rate_limited')
 */
function cta_facebook_webhook_log_request($request, $success, $note = '') {
    $log = get_option('cta_fb_webhook_request_log', []);
    if (!is_array($log)) {
        $log = [];
    }
    $entry = [
        'ip' => cta_facebook_webhook_client_ip(),
        'time' => time(),
        'success' => (bool) $success,
        'note' => $note,
    ];
    array_unshift($log, $entry);
    $log = array_slice($log, 0, 100);
    update_option('cta_fb_webhook_request_log', $log, false);
}

/**
 * Validate and sanitize webhook payload structure. Required: entry (array); each entry
 * must have changes (array); each change must have field (string) and value (array);
 * leadgen changes must have value.leadgen_id (string).
 *
 * @param array $body Parsed JSON body
 * @return array{entries: array, errors: string[]} Sanitized entries and any validation errors
 */
function cta_facebook_webhook_validate_payload($body) {
    $errors = [];
    $entries = [];

    if (!is_array($body)) {
        return ['entries' => [], 'errors' => ['Payload must be an object']];
    }
    if (empty($body['entry']) || !is_array($body['entry'])) {
        return ['entries' => [], 'errors' => ['Missing or invalid entry array']];
    }

    foreach ($body['entry'] as $i => $entry) {
        if (!is_array($entry)) {
            $errors[] = "entry[{$i}] is not an array";
            continue;
        }
        if (empty($entry['changes']) || !is_array($entry['changes'])) {
            continue;
        }
        foreach ($entry['changes'] as $j => $change) {
            if (!is_array($change)) {
                $errors[] = "entry[{$i}].changes[{$j}] is not an array";
                continue;
            }
            $field = isset($change['field']) ? sanitize_text_field((string) $change['field']) : '';
            $value = isset($change['value']) && is_array($change['value']) ? $change['value'] : [];
            if ($field !== 'leadgen') {
                continue;
            }
            $leadgen_id = isset($value['leadgen_id']) ? sanitize_text_field((string) $value['leadgen_id']) : '';
            if ($leadgen_id === '') {
                $errors[] = "entry[{$i}].changes[{$j}]: missing or invalid leadgen_id";
                continue;
            }
            $entries[] = [
                'entry_index' => $i,
                'change_index' => $j,
                'lead_data' => array_merge($value, ['leadgen_id' => $leadgen_id]),
            ];
        }
    }

    return ['entries' => $entries, 'errors' => $errors];
}

/**
 * Process Facebook lead data (POST request)
 * Payload is validated and sanitized before processing.
 *
 * @param WP_REST_Request $request REST request object
 * @return WP_REST_Response|WP_Error
 */
function cta_process_facebook_lead($request) {
    $body = $request->get_json_params();
    $validated = cta_facebook_webhook_validate_payload($body ?: []);

    if (empty($validated['entries']) && !empty($validated['errors'])) {
        cta_facebook_webhook_log_request($request, false, 'invalid_payload');
        return new WP_Error('invalid_payload', 'Invalid webhook payload: ' . implode('; ', $validated['errors']), ['status' => 400]);
    }

    $processed = 0;
    $errors = $validated['errors'];

    $processed_leads = get_option('cta_fb_processed_leads', []);
    if (!is_array($processed_leads)) {
        $processed_leads = [];
    }
    $processed_leads = cta_facebook_webhook_normalize_processed_leads($processed_leads);
    $processed_ids = array_column($processed_leads, 'id');
    $retention_seconds = 30 * DAY_IN_SECONDS;
    $max_entries = 1000;

    foreach ($validated['entries'] as $item) {
        $lead_data = $item['lead_data'];
        $leadgen_id = $lead_data['leadgen_id'];

        if (in_array($leadgen_id, $processed_ids, true)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Facebook Lead Ads: Skipped duplicate lead ' . $leadgen_id);
            }
            continue;
        }

        $lead_details = cta_fetch_facebook_lead_details($leadgen_id);
        if (is_wp_error($lead_details)) {
            $errors[] = 'Failed to fetch lead details: ' . $lead_details->get_error_message();
            continue;
        }

        $adset_info = cta_fetch_facebook_adset_from_lead($lead_data, $lead_details);
        if (is_wp_error($adset_info)) {
            $errors[] = 'Failed to fetch ad set info: ' . $adset_info->get_error_message();
            continue;
        }

        $adset_name = $adset_info['name'] ?? '';
        if (stripos($adset_name, 'cta') === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Facebook Lead Ads: Skipped lead - ad set name does not contain "cta": ' . $adset_name);
            }
            continue;
        }

        $parsed_data = cta_parse_adset_name($adset_name);
        $submission_id = cta_create_submission_from_facebook_lead($lead_data, $lead_details, $parsed_data);
        if (is_wp_error($submission_id)) {
            $errors[] = 'Failed to create submission: ' . $submission_id->get_error_message();
            continue;
        }

        $processed_leads[] = ['id' => $leadgen_id, 'time' => time()];
        $cutoff = time() - $retention_seconds;
        $processed_leads = array_filter($processed_leads, function ($e) use ($cutoff) {
            return isset($e['time']) && $e['time'] >= $cutoff;
        });
        $processed_leads = array_values(array_slice($processed_leads, -$max_entries, $max_entries));
        update_option('cta_fb_processed_leads', $processed_leads, false);
        $processed_ids = array_column($processed_leads, 'id');
        $processed++;
    }

    cta_facebook_webhook_log_request($request, true, $processed > 0 ? 'processed_' . $processed : 'no_leads');

    $response_data = [
        'success' => true,
        'processed' => $processed,
    ];
    if (!empty($errors)) {
        $response_data['errors'] = $errors;
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Facebook Lead Ads Webhook Errors: ' . print_r($errors, true));
        }
    }

    return new WP_REST_Response($response_data, 200);
}

/**
 * Fetch full lead details from Facebook Graph API
 * 
 * @param string $leadgen_id Facebook Lead ID
 * @return array|WP_Error Lead data or error
 */
function cta_fetch_facebook_lead_details($leadgen_id) {
    $access_token = get_option('cta_facebook_access_token', '');
    
    if (empty($access_token)) {
        return new WP_Error('no_access_token', 'Facebook Access Token not configured');
    }
    
    $url = 'https://graph.facebook.com/v24.0/' . $leadgen_id . '?fields=id,created_time,field_data,ad_id&access_token=' . urlencode($access_token);
    
    $response = wp_remote_get($url, [
        'timeout' => 10,
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (isset($data['error'])) {
        return new WP_Error('api_error', $data['error']['message'] ?? 'Facebook API error');
    }
    
    return $data;
}

/**
 * Fetch ad set information from lead data
 * 
 * @param array $lead_data Lead data from webhook
 * @param array $lead_details Full lead details from Graph API
 * @return array|WP_Error Ad set data or error
 */
function cta_fetch_facebook_adset_from_lead($lead_data, $lead_details) {
    $access_token = get_option('cta_facebook_access_token', '');
    
    if (empty($access_token)) {
        return new WP_Error('no_access_token', 'Facebook Access Token not configured');
    }
    
    $ad_id = $lead_details['ad_id'] ?? '';
    
    if (empty($ad_id)) {
        return new WP_Error('no_ad_id', 'Ad ID not found in lead data');
    }
    
    $ad_url = 'https://graph.facebook.com/v24.0/' . $ad_id . '?fields=adset_id&access_token=' . urlencode($access_token);
    
    $ad_response = wp_remote_get($ad_url, [
        'timeout' => 10,
    ]);
    
    if (is_wp_error($ad_response)) {
        return $ad_response;
    }
    
    $ad_body = wp_remote_retrieve_body($ad_response);
    $ad_data = json_decode($ad_body, true);
    
    if (isset($ad_data['error'])) {
        return new WP_Error('api_error', $ad_data['error']['message'] ?? 'Facebook API error');
    }
    
    $adset_id = $ad_data['adset_id'] ?? '';
    
    if (empty($adset_id)) {
        return new WP_Error('no_adset_id', 'Ad Set ID not found in ad data');
    }
    
    $adset_url = 'https://graph.facebook.com/v24.0/' . $adset_id . '?fields=name&access_token=' . urlencode($access_token);
    
    $adset_response = wp_remote_get($adset_url, [
        'timeout' => 10,
    ]);
    
    if (is_wp_error($adset_response)) {
        return $adset_response;
    }
    
    $adset_body = wp_remote_retrieve_body($adset_response);
    $adset_data = json_decode($adset_body, true);
    
    if (isset($adset_data['error'])) {
        return new WP_Error('api_error', $adset_data['error']['message'] ?? 'Facebook API error');
    }
    
    return $adset_data;
}

/**
 * Parse ad set name to extract course name and date
 * Format: cta_COURSENAME_DATE (e.g., cta_EPFA_27.01.2026)
 * 
 * Note: For upcoming courses ads (cta_upcoming-courses_DATE), returns empty
 * as the event selection comes from the form field, not the ad set name.
 * 
 * @param string $adset_name Ad set name
 * @return array Parsed data with 'course_name' and 'date'
 */
function cta_parse_adset_name($adset_name) {
    $parsed = [
        'course_name' => '',
        'date' => '',
    ];
    
    if (empty($adset_name)) {
        return $parsed;
    }
    
    // Skip parsing for upcoming courses ads - event comes from form field selection
    if (preg_match('/^cta_upcoming-courses_/i', $adset_name)) {
        return $parsed;
    }
    
    $name = preg_replace('/^cta_/i', '', $adset_name);
    $parts = explode('_', $name);
    
    if (count($parts) >= 2) {
        $parsed['date'] = array_pop($parts);
        $parsed['course_name'] = implode('_', $parts);
    } elseif (count($parts) === 1) {
        $parsed['course_name'] = $parts[0];
    }
    
    return $parsed;
}

/**
 * Parse booking quantity from Facebook form selection
 * Converts text selections like "Just me", "2 people", "3-4 people", "5+ people" to numbers
 * 
 * @param string $value Selection value from form
 * @return int Number of delegates (default: 1)
 */
function cta_parse_booking_quantity($value) {
    if (empty($value)) {
        return 1; // Default to 1 delegate
    }
    
    $value_lower = strtolower(trim($value));
    
    if (stripos($value_lower, 'just me') !== false || stripos($value_lower, '1 person') !== false) {
        return 1;
    }
    
    if (preg_match('/\b2\b/', $value_lower)) {
        return 2;
    }
    
    if (preg_match('/\b3[-\s]4\b/', $value_lower) || preg_match('/\b3\s+to\s+4\b/i', $value_lower)) {
        return 3;
    }
    
    if (preg_match('/\b5\+/i', $value_lower) || preg_match('/\b5\s+or\s+more/i', $value_lower) || preg_match('/\b5\s+people/i', $value_lower)) {
        return 5;
    }
    
    if (preg_match('/\b(\d+)\b/', $value, $matches)) {
        return intval($matches[1]);
    }
    
    return 1;
}

/**
 * Find course_event by course name and date
 * First finds the course, then finds course_events linked to it that match the date
 * 
 * @param string $course_name Course name or acronym (e.g., "EPFA" or "Medication-Competency-Management")
 * @param string $event_date Date in format DD.MM.YYYY (e.g., "27.01.2026")
 * @return int|false Course event post ID or false if not found
 */
function cta_find_course_event_by_course_and_date($course_name, $event_date) {
    if (empty($course_name) || empty($event_date)) {
        return false;
    }
    
    // First find the course
    $course_id = cta_find_course_by_name($course_name);
    if (!$course_id) {
        return false;
    }
    
    // Convert date from DD.MM.YYYY to YYYY-MM-DD (ACF format)
    $date_parts = explode('.', $event_date);
    if (count($date_parts) !== 3) {
        return false;
    }
    
    $acf_date = sprintf('%04d-%02d-%02d', $date_parts[2], $date_parts[1], $date_parts[0]);
    
    // Find course_events linked to this course with matching date
    // Use ACF get_field() to properly handle relationship fields
    $args = [
        'post_type' => 'course_event',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'event_date',
                'value' => $acf_date,
                'compare' => '=',
            ],
        ],
    ];
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        foreach ($query->posts as $event_post) {
            // Check if this event is linked to our course
            $linked_course = function_exists('get_field') ? get_field('linked_course', $event_post->ID) : null;
            
            // Handle ACF relationship field (can be object, array, or ID)
            $linked_course_id = null;
            if (is_object($linked_course)) {
                $linked_course_id = $linked_course->ID ?? null;
            } elseif (is_array($linked_course) && !empty($linked_course)) {
                $linked_course_id = is_object($linked_course[0]) ? $linked_course[0]->ID : $linked_course[0];
            } elseif (is_numeric($linked_course)) {
                $linked_course_id = (int) $linked_course;
            }
            
            if ($linked_course_id === $course_id) {
                wp_reset_postdata();
                return $event_post->ID;
            }
        }
    }
    
    wp_reset_postdata();
    return false;
}

/**
 * Find course by name/acronym
 * Handles variations with hyphens, underscores, and spaces
 * 
 * @param string $course_name Course name or acronym (e.g., "EPFA" or "Medication-Competency-Management")
 * @return int|false Course post ID or false if not found
 */
function cta_find_course_by_name($course_name) {
    if (empty($course_name)) {
        return false;
    }
    
    $normalize = function($str) {
        return strtoupper(preg_replace('/[-_\s]+/', '', $str));
    };
    
    $normalized_search = $normalize($course_name);
    
    $args = [
        'post_type' => 'course',
        'posts_per_page' => 10,
        'post_status' => 'publish',
        's' => $course_name,
    ];
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        $best_match = null;
        $best_score = 0;
        
        while ($query->have_posts()) {
            $query->the_post();
            $course_id = get_the_ID();
            $title = get_the_title($course_id);
            $normalized_title = $normalize($title);
            
            if ($normalized_title === $normalized_search) {
                wp_reset_postdata();
                return $course_id;
            }
            
            if (strpos($normalized_title, $normalized_search) === 0) {
                $score = strlen($normalized_search) / strlen($normalized_title);
                if ($score > $best_score) {
                    $best_score = $score;
                    $best_match = $course_id;
                }
            }
            
            if (strpos($normalized_title, $normalized_search) !== false) {
                $score = strlen($normalized_search) / strlen($normalized_title);
                if ($score > $best_score) {
                    $best_score = $score;
                    $best_match = $course_id;
                }
            }
        }
        wp_reset_postdata();
        
        if ($best_match) {
            return $best_match;
        }
    }
    
    $args = [
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ];
    
    $all_courses = get_posts($args);
    
    foreach ($all_courses as $course) {
        $title = $course->post_title;
        $normalized_title = $normalize($title);
        
        if ($normalized_title === $normalized_search) {
            return $course->ID;
        }
        
        if (strpos($normalized_title, $normalized_search) === 0) {
            return $course->ID;
        }
        
        if (strpos($normalized_title, $normalized_search) !== false) {
            return $course->ID;
        }
    }
    
    return false;
}

/**
 * Find course_event by title/text match
 * Used when leads select an event from a multiple choice question
 * 
 * @param string $event_text Event text from form (e.g., "Emergency Paediatric First Aid - 27 Jan 2026")
 * @return int|false Course event post ID or false if not found
 */
function cta_find_course_event_by_text($event_text) {
    if (empty($event_text)) {
        return false;
    }
    
    // Normalize the search text (remove extra spaces, convert to lowercase)
    $normalize = function($str) {
        return strtolower(preg_replace('/\s+/', ' ', trim($str)));
    };
    
    $normalized_search = $normalize($event_text);
    
    // Check if normalized search is empty (whitespace-only input)
    if (empty($normalized_search)) {
        return false;
    }
    
    // Try to extract date from the text (common formats)
    $date_patterns = [
        '/(\d{1,2})[.\s]+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*[.\s]+(\d{4})/i',
        '/(\d{1,2})[.\s]+(\d{1,2})[.\s]+(\d{4})/',
        '/(\d{1,2})\/(\d{1,2})\/(\d{4})/',
    ];
    
    $extracted_date = null;
    foreach ($date_patterns as $pattern) {
        if (preg_match($pattern, $event_text, $matches)) {
            // Try to parse the date
            $date_str = $matches[0];
            $date_obj = strtotime($date_str);
            if ($date_obj) {
                $extracted_date = date('Y-m-d', $date_obj);
                break;
            }
        }
    }
    
    // Search course_events by title
    $args = [
        'post_type' => 'course_event',
        'posts_per_page' => 50,
        'post_status' => 'publish',
        's' => $event_text,
    ];
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        $best_match = null;
        $best_score = 0;
        
        while ($query->have_posts()) {
            $query->the_post();
            $event_id = get_the_ID();
            $title = get_the_title($event_id);
            $normalized_title = $normalize($title);
            
            // If we extracted a date, verify it matches
            if ($extracted_date) {
                $event_date = function_exists('get_field') ? get_field('event_date', $event_id) : null;
                if (!$event_date) {
                    $event_date = get_post_meta($event_id, 'event_date', true);
                }
                
                if ($event_date) {
                    $event_date_formatted = is_string($event_date) ? $event_date : date('Y-m-d', $event_date);
                    if ($event_date_formatted !== $extracted_date) {
                        continue; // Date doesn't match, skip
                    }
                }
            }
            
            // Calculate match score
            if ($normalized_title === $normalized_search) {
                wp_reset_postdata();
                return $event_id; // Exact match
            }
            
            // Check if search text is contained in title
            if (strpos($normalized_title, $normalized_search) !== false && !empty($normalized_title)) {
                $score = strlen($normalized_search) / strlen($normalized_title);
                if ($score > $best_score) {
                    $best_score = $score;
                    $best_match = $event_id;
                }
            }
            
            // Check if title is contained in search text
            if (strpos($normalized_search, $normalized_title) !== false && !empty($normalized_search) && !empty($normalized_title)) {
                $score = strlen($normalized_title) / strlen($normalized_search);
                if ($score > $best_score) {
                    $best_score = $score;
                    $best_match = $event_id;
                }
            }
        }
        wp_reset_postdata();
        
        if ($best_match) {
            return $best_match;
        }
    }
    
    wp_reset_postdata();
    return false;
}

/**
 * Create form submission from Facebook lead data
 * 
 * @param array $lead_data Lead data from webhook
 * @param array $lead_details Full lead details from Graph API
 * @param array $parsed_data Parsed course name and date from ad set name
 * @return int|WP_Error Submission post ID or error
 */
function cta_create_submission_from_facebook_lead($lead_data, $lead_details, $parsed_data = []) {
    $lead_id = $lead_data['leadgen_id'] ?? '';
    $page_id = $lead_data['page_id'] ?? '';
    $form_id = $lead_data['form_id'] ?? '';
    $created_time = $lead_data['created_time'] ?? time();
    
    $field_data = $lead_details['field_data'] ?? [];
    
    $submission_data = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'message' => '',
        'consent' => 'yes', // Facebook leads have already consented
        'marketing_consent' => 'yes', // Facebook leads have marketing consent
        'meta_lead_id' => $lead_id,
        'page_url' => !empty($page_id) ? 'https://facebook.com/' . $page_id : '',
    ];
    
    foreach ($field_data as $field) {
        $field_name = strtolower($field['name'] ?? '');
        $field_value = $field['values'][0] ?? '';
        
        if (empty($field_value)) {
            continue;
        }
        
        switch ($field_name) {
            case 'first_name':
            case 'full_name':
            case 'name':
                if (empty($submission_data['name'])) {
                    $submission_data['name'] = $field_value;
                } else {
                    $submission_data['name'] = $field_value . ' ' . $submission_data['name'];
                }
                break;
                
            case 'last_name':
                if (!empty($submission_data['name'])) {
                    $submission_data['name'] = $submission_data['name'] . ' ' . $field_value;
                } else {
                    $submission_data['name'] = $field_value;
                }
                break;
                
            case 'email':
            case 'email_address':
                $submission_data['email'] = $field_value;
                break;
                
            case 'phone_number':
            case 'phone':
            case 'mobile_phone':
            case 'phone_number_1': // Facebook sometimes adds _1 suffix
                $submission_data['phone'] = $field_value;
                break;
                
            case 'message':
            case 'comments':
            case 'additional_comments':
                if (!empty($submission_data['message'])) {
                    $submission_data['message'] .= "\n\n" . $field_value;
                } else {
                    $submission_data['message'] = $field_value;
                }
                break;
                
            default:
                // Check if this is an event selection field (multiple choice question about which event)
                if (stripos($field_name, 'event') !== false || 
                    stripos($field_name, 'course') !== false ||
                    stripos($field_name, 'which') !== false ||
                    stripos($field_name, 'select') !== false) {
                    
                    // Try to find course_event by the selected text
                    $course_event_id = cta_find_course_event_by_text($field_value);
                    
                    if ($course_event_id && empty($submission_data['course_id'])) {
                        // Found a matching course_event - use it
                        $submission_data['course_id'] = $course_event_id;
                        $submission_data['course_name'] = get_the_title($course_event_id);
                        
                        // Get event date from the course_event
                        $event_date = function_exists('get_field') ? get_field('event_date', $course_event_id) : null;
                        if (!$event_date) {
                            $event_date = get_post_meta($course_event_id, 'event_date', true);
                        }
                        if ($event_date) {
                            // Convert from Y-m-d to DD.MM.YYYY format for consistency
                            $date_obj = is_string($event_date) ? strtotime($event_date) : $event_date;
                            if ($date_obj !== false && $date_obj > 0) {
                                $submission_data['event_date'] = date('d.m.Y', $date_obj);
                            } else {
                                // Invalid date - log and use original format if it's already a string
                                if (defined('WP_DEBUG') && WP_DEBUG) {
                                    error_log('Facebook Lead Ads: Invalid event_date format: ' . print_r($event_date, true));
                                }
                                if (is_string($event_date)) {
                                    $submission_data['event_date'] = $event_date;
                                }
                            }
                        }
                        
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('Facebook Lead Ads: Found course_event ' . $course_event_id . ' from event selection field: ' . $field_value);
                        }
                    }
                    
                    // Store in form_data
                    if (!isset($submission_data['form_data'])) {
                        $submission_data['form_data'] = [];
                    }
                    $submission_data['form_data'][$field_name] = $field_value;
                } elseif (stripos($field_name, 'how many') !== false || 
                    stripos($field_name, 'booking') !== false || 
                    stripos($field_name, 'people') !== false ||
                    stripos($field_name, 'delegates') !== false) {
                    
                    $delegates = cta_parse_booking_quantity($field_value);
                    if ($delegates > 0) {
                        $submission_data['delegates'] = $delegates;
                    }
                    
                    if (!isset($submission_data['form_data'])) {
                        $submission_data['form_data'] = [];
                    }
                    $submission_data['form_data'][$field_name] = $field_value;
                } else {
                    if (!isset($submission_data['form_data'])) {
                        $submission_data['form_data'] = [];
                    }
                    $submission_data['form_data'][$field_name] = $field_value;
                }
                break;
        }
    }
    
    // Try to find course_event first (if we have both course name and date)
    if (!empty($parsed_data['course_name']) && !empty($parsed_data['date'])) {
        $course_event_id = cta_find_course_event_by_course_and_date($parsed_data['course_name'], $parsed_data['date']);
        
        if ($course_event_id) {
            // Found a matching course_event - use it
            $submission_data['course_id'] = $course_event_id;
            $submission_data['course_name'] = get_the_title($course_event_id);
            
            // Get event date from the course_event
            $event_date = function_exists('get_field') ? get_field('event_date', $course_event_id) : null;
            if (!$event_date) {
                $event_date = get_post_meta($course_event_id, 'event_date', true);
            }
            if ($event_date) {
                // Convert from Y-m-d to DD.MM.YYYY format for consistency
                $date_obj = is_string($event_date) ? strtotime($event_date) : $event_date;
                if ($date_obj !== false && $date_obj > 0) {
                    $submission_data['event_date'] = date('d.m.Y', $date_obj);
                } else {
                    // Invalid date - log and use parsed date as fallback
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Facebook Lead Ads: Invalid event_date format: ' . print_r($event_date, true));
                    }
                    $submission_data['event_date'] = $parsed_data['date'];
                }
            } else {
                $submission_data['event_date'] = $parsed_data['date'];
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Facebook Lead Ads: Found course_event ' . $course_event_id . ' for course "' . $parsed_data['course_name'] . '" on date ' . $parsed_data['date']);
            }
        } else {
            // No matching course_event found - fall back to course only
            $course_id = cta_find_course_by_name($parsed_data['course_name']);
            
            if ($course_id) {
                $submission_data['course_id'] = $course_id;
                $submission_data['course_name'] = get_the_title($course_id);
                $submission_data['event_date'] = $parsed_data['date'];
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Facebook Lead Ads: Found course ' . $course_id . ' but no matching course_event for date ' . $parsed_data['date']);
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Facebook Lead Ads: Course not found for: ' . $parsed_data['course_name']);
                }
            }
        }
    } elseif (!empty($parsed_data['course_name'])) {
        // Only course name provided, no date
        $course_id = cta_find_course_by_name($parsed_data['course_name']);
        
        if ($course_id) {
            $submission_data['course_id'] = $course_id;
            $submission_data['course_name'] = get_the_title($course_id);
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Facebook Lead Ads: Course not found for: ' . $parsed_data['course_name']);
            }
        }
    }
    
    // Set event_date if we have it but haven't set it yet
    if (!empty($parsed_data['date']) && empty($submission_data['event_date'])) {
        $submission_data['event_date'] = $parsed_data['date'];
    }
    
    $form_type = get_option('cta_facebook_lead_ads_form_type', 'meta-lead');
    
    if (class_exists('\\CTA\\Repositories\\FormSubmissionRepository')) {
        $repository = new \CTA\Repositories\FormSubmissionRepository();
        $submission_id = $repository->create($submission_data, $form_type, false, '');
    } else {
        $submission_id = cta_save_form_submission($submission_data, $form_type, false, '');
    }
    
    if (is_wp_error($submission_id)) {
        return $submission_id;
    }
    
    if (!empty($submission_data['delegates'])) {
        update_post_meta($submission_id, '_submission_delegates', absint($submission_data['delegates']));
    }
    
    if (!empty($page_id)) {
        update_post_meta($submission_id, '_submission_facebook_page_id', $page_id);
    }
    if (!empty($form_id)) {
        update_post_meta($submission_id, '_submission_facebook_form_id', $form_id);
    }
    if (!empty($created_time)) {
        update_post_meta($submission_id, '_submission_facebook_created_time', $created_time);
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Facebook Lead Ads: Created submission ' . $submission_id . ' from lead ' . $lead_id);
    }
    
    return $submission_id;
}

/**
 * Add Facebook Lead Ads webhook settings to API Keys page
 */
function cta_facebook_lead_ads_webhook_settings_fields() {
    $enabled = get_option('cta_facebook_lead_ads_webhook_enabled', 0);
    $verify_token = get_option('cta_facebook_lead_ads_verify_token', '');
    
    if (empty($verify_token)) {
        $verify_token = wp_generate_password(32, false);
        update_option('cta_facebook_lead_ads_verify_token', $verify_token);
    }
    
    $form_type = get_option('cta_facebook_lead_ads_form_type', 'meta-lead');
    $webhook_url = rest_url('cta/v1/facebook-lead-ads');
    ?>
    <div class="cta-api-keys-section">
        <h2>
            <span class="dashicons dashicons-facebook-alt" style="color: #1877F2;"></span>
            Facebook Lead Ads Webhook
        </h2>
        <p class="description">
            Receive leads from Facebook Lead Ads automatically. Leads are imported as form submissions in WordPress.
            <a href="https://developers.facebook.com/docs/graph-api/webhooks/reference/leadgen" target="_blank">Learn more about Lead Ads Webhooks</a>
            <br><strong>Note:</strong> You need to configure the <strong>Conversions API Access Token</strong> above (in the Facebook Pixel & Conversions API section) for the webhook to fetch lead details.
        </p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="cta_facebook_lead_ads_webhook_enabled">Enable Webhook</label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               id="cta_facebook_lead_ads_webhook_enabled" 
                               name="cta_facebook_lead_ads_webhook_enabled" 
                               value="1" 
                               <?php checked($enabled, 1); ?>>
                        Receive leads from Facebook Lead Ads
                    </label>
                    <p class="description">
                        When enabled, leads from Facebook Lead Ads will be automatically imported as form submissions.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cta_facebook_app_secret">App Secret</label>
                </th>
                <td>
                    <input type="password" 
                           id="cta_facebook_app_secret" 
                           name="cta_facebook_app_secret" 
                           value="" 
                           class="regular-text"
                           autocomplete="off">
                    <p class="description">
                        Required in production: verifies POST requests via X-Hub-Signature-256. Find it in Meta for Developers → Your App → Settings → Basic → App Secret. Leave blank to keep current. If not set, webhook POSTs are blocked (use <code>CTA_FB_WEBHOOK_DEBUG</code> in wp-config.php only for local dev to bypass).
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cta_fb_webhook_trusted_proxies">Trusted proxy IPs</label>
                </th>
                <td>
                    <input type="text" 
                           id="cta_fb_webhook_trusted_proxies" 
                           name="cta_fb_webhook_trusted_proxies" 
                           value="<?php echo esc_attr(get_option('cta_fb_webhook_trusted_proxies', '')); ?>" 
                           class="large-text"
                           placeholder="e.g. 10.0.0.1, 172.16.0.1">
                    <p class="description">
                        Comma-separated IPs of reverse proxies (e.g. Cloudflare, nginx). Only then is X-Forwarded-For used for rate limiting and logging; otherwise REMOTE_ADDR is used (prevents spoofing).
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cta_facebook_lead_ads_verify_token">Verify Token</label>
                </th>
                <td>
                    <input type="text" 
                           id="cta_facebook_lead_ads_verify_token" 
                           name="cta_facebook_lead_ads_verify_token" 
                           value="<?php echo esc_attr($verify_token); ?>" 
                           class="regular-text"
                           readonly>
                    <input type="hidden" name="cta_facebook_lead_ads_generate_token" id="cta_facebook_lead_ads_generate_token" value="0">
                    <button type="button" class="button" onclick="document.getElementById('cta_facebook_lead_ads_verify_token').select(); document.execCommand('copy'); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 2000);">Copy</button>
                    <button type="button" class="button" onclick="if(confirm('Generate a new verify token? The old token will no longer work with Facebook. You will need to update the webhook in Facebook with the new token.')) { document.getElementById('cta_facebook_lead_ads_generate_token').value = '1'; var form = this.closest('form'); if(form) { form.submit(); } }">Generate New Token</button>
                    <p class="description">
                        Use this token when setting up the webhook in Facebook. 
                        <strong>Webhook URL:</strong> <code><?php echo esc_html($webhook_url); ?></code>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cta_facebook_lead_ads_form_type">Form Type</label>
                </th>
                <td>
                    <input type="text" 
                           id="cta_facebook_lead_ads_form_type" 
                           name="cta_facebook_lead_ads_form_type" 
                           value="<?php echo esc_attr($form_type); ?>" 
                           class="regular-text"
                           placeholder="meta-lead">
                    <p class="description">
                        Form type slug for imported leads (used for categorization in Form Submissions). Default: meta-lead
                    </p>
                </td>
            </tr>
        </table>
        
        <?php if ($enabled) : ?>
            <div class="notice notice-info inline">
                <p>
                    <strong>Webhook Active:</strong> Leads from Facebook Lead Ads will be automatically imported.
                    <br>
                    <strong>Webhook URL:</strong> <code><?php echo esc_html($webhook_url); ?></code>
                    <br>
                    <strong>Verify Token:</strong> <code><?php echo esc_html($verify_token); ?></code>
                </p>
                <p>
                    <strong>Setup Instructions:</strong>
                </p>
                <ol style="margin-left: 20px;">
                    <li><strong>Get your Access Token:</strong> Go to <a href="https://business.facebook.com/events_manager" target="_blank">Events Manager</a> → Data Sources → Your Pixel → Settings → Conversions API → Generate Access Token. Enter it in the <strong>Conversions API Access Token</strong> field above.</li>
                    <li><strong>Configure Webhook in Facebook:</strong> Go to <a href="https://business.facebook.com" target="_blank">Meta Business Suite</a> → Lead Ads → Settings → Integrations</li>
                    <li>Click "Add Integration" → "Webhook"</li>
                    <li>Enter the <strong>Webhook URL</strong> above</li>
                    <li>Enter the <strong>Verify Token</strong> above</li>
                    <li>Subscribe to <strong>"leadgen"</strong> events</li>
                    <li>Save and verify the webhook</li>
                </ol>
            </div>
        <?php else : ?>
            <div class="notice notice-warning inline">
                <p>
                    <strong>Webhook Disabled:</strong> Enable the webhook above to start receiving leads from Facebook Lead Ads.
                </p>
                <p>
                    <strong>Before enabling:</strong> Make sure you have configured the <strong>Conversions API Access Token</strong> in the Facebook Pixel & Conversions API section above. 
                    <a href="https://developers.facebook.com/docs/marketing-api/conversions-api/get-started" target="_blank">Get your Access Token →</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
