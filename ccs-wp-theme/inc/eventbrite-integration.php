<?php
/**
 * Eventbrite Integration
 * 
 * Automatically syncs WordPress course events to Eventbrite, generates AI descriptions,
 * allows importing existing Eventbrite events, and tracks bookings from both sources.
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

// ============================================================================
// EVENTBRITE API HELPER (Best Practices)
// ============================================================================

/**
 * Make Eventbrite API request with retry logic, rate limit handling, and error logging
 * 
 * Follows Eventbrite API best practices:
 * - Retry logic with exponential backoff for 429 and 5xx errors
 * - Rate limit header monitoring
 * - Comprehensive error logging
 * - Response time tracking
 * - HTTPS verification
 * 
 * @param string $url API endpoint URL
 * @param array $args Request arguments (method, headers, body, timeout)
 * @param string $operation_name Name of operation for logging (e.g., 'Create Event')
 * @return array|WP_Error Response data or error
 */
function ccs_eventbrite_api_request($url, $args = [], $operation_name = 'API Request') {
    $default_args = [
        'method' => 'GET',
        'headers' => [],
        'body' => null,
        'timeout' => 30,
        'sslverify' => true, // Ensure HTTPS verification (best practice)
    ];
    
    $args = wp_parse_args($args, $default_args);
    
    // Ensure Authorization header is set
    if (empty($args['headers']['Authorization'])) {
        $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
        if (empty($oauth_token)) {
            return new WP_Error('missing_credentials', 'Eventbrite OAuth token not configured');
        }
        $args['headers']['Authorization'] = 'Bearer ' . $oauth_token;
    }
    
    // Ensure Content-Type header for POST/PUT requests
    if (in_array($args['method'], ['POST', 'PUT']) && empty($args['headers']['Content-Type'])) {
        $args['headers']['Content-Type'] = 'application/json';
    }
    
    // Retry logic with exponential backoff (best practice)
    $max_retries = 3;
    $retry_count = 0;
    $delay = 1;
    
    while ($retry_count < $max_retries) {
        $start_time = microtime(true);
        
        if ($args['method'] === 'GET') {
            $response = wp_remote_get($url, $args);
        } elseif ($args['method'] === 'DELETE') {
            $response = wp_remote_request($url, array_merge($args, ['method' => 'DELETE']));
        } else {
            $response = wp_remote_post($url, $args);
        }
        
        $response_time = microtime(true) - $start_time;
        
        // Network error - retry
        if (is_wp_error($response)) {
            error_log("Eventbrite {$operation_name} Error (attempt " . ($retry_count + 1) . "): " . $response->get_error_message());
            
            if ($retry_count < $max_retries - 1) {
                sleep($delay);
                $delay *= 2; // Exponential backoff
                $retry_count++;
                continue;
            }
            return $response;
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Check rate limit headers (best practice)
        $rate_limit_remaining = wp_remote_retrieve_header($response, 'X-RateLimit-Remaining');
        if ($rate_limit_remaining !== null) {
            $remaining = intval($rate_limit_remaining);
            if ($remaining < 10) {
                error_log("Eventbrite API: Rate limit warning - {$remaining} requests remaining");
            }
        }
        
        if ($http_code >= 200 && $http_code < 300) {
            error_log("Eventbrite {$operation_name} Success (Response time: " . round($response_time, 2) . "s)");
            return $data;
        }
        
        // Rate limit error - retry with backoff (best practice)
        if ($http_code === 429) {
            $retry_after = wp_remote_retrieve_header($response, 'X-RateLimit-Reset');
            $wait_time = $retry_after ? (intval($retry_after) - time()) : $delay;
            
            if ($wait_time > 0 && $retry_count < $max_retries - 1) {
                error_log("Eventbrite API Rate Limited. Waiting {$wait_time} seconds before retry...");
                sleep(min($wait_time, 60)); // Cap at 60 seconds
                $delay *= 2;
                $retry_count++;
                continue;
            }
        }
        
        // 5xx errors - retry (best practice)
        if ($http_code >= 500 && $http_code < 600 && $retry_count < $max_retries - 1) {
            error_log("Eventbrite API Server Error {$http_code}. Retrying in {$delay} seconds...");
            sleep($delay);
            $delay *= 2;
            $retry_count++;
            continue;
        }
        
        // Other errors - don't retry
        $error_message = isset($data['error_description']) ? $data['error_description'] : 'Unknown error';
        $error_code = isset($data['error']) ? $data['error'] : 'UNKNOWN_ERROR';
        
        error_log("Eventbrite {$operation_name} Failed: {$error_code} - {$error_message} (HTTP {$http_code})");
        
        return new WP_Error('api_error', "Eventbrite API error: {$error_message}", [
            'error_code' => $error_code,
            'http_code' => $http_code,
            'response' => $data,
            'operation' => $operation_name
        ]);
    }
    
    return new WP_Error('max_retries_exceeded', "Failed {$operation_name} after {$max_retries} attempts");
}

// ============================================================================
// EVENTBRITE DISCOUNT CODE SYNC
// ============================================================================

/**
 * Create a coded discount on Eventbrite
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 * 
 * @param string $code Discount code
 * @param float $percent_off Discount percentage (1-100)
 * @param string $expiry_date Optional expiry date (YYYY-MM-DD)
 * @param string $event_id Optional specific event ID, or null for all events
 * @return array|WP_Error Eventbrite discount data or error
 */
function ccs_create_eventbrite_discount($code, $percent_off = 20.0, $expiry_date = '', $event_id = null) {
    $org_id = get_option('ccs_eventbrite_organization_id', '');
    
    if (empty($org_id)) {
        return new WP_Error('missing_credentials', 'Eventbrite Organization ID not configured');
    }
    
    if (empty($code)) {
        return new WP_Error('invalid_code', 'Discount code cannot be empty');
    }
    
    $percent_off = floatval($percent_off);
    if ($percent_off < 1.0 || $percent_off > 100.0) {
        return new WP_Error('invalid_percentage', 'Discount percentage must be between 1 and 100');
    }
    
    $discount_payload = [
        'discount' => [
            'type' => 'coded',
            'code' => sanitize_text_field($code),
            'percent_off' => $percent_off,
            'quantity_available' => 0, // 0 = unlimited
        ]
    ];
    
    if ($event_id) {
        $discount_payload['discount']['event_id'] = sanitize_text_field($event_id);
    }
    
    if (!empty($expiry_date)) {
        $date_obj = DateTime::createFromFormat('Y-m-d', $expiry_date);
        if ($date_obj && $date_obj->format('Y-m-d') === $expiry_date) {
            $expiry_datetime = $expiry_date . 'T23:59:59';
            $discount_payload['discount']['end_date'] = $expiry_datetime;
        } else {
            error_log('Eventbrite Discount: Invalid expiry date format: ' . $expiry_date);
        }
    }
    
    $url = "https://www.eventbriteapi.com/v3/organizations/{$org_id}/discounts/";
    
    return ccs_eventbrite_api_request($url, [
        'method' => 'POST',
        'body' => json_encode($discount_payload),
    ], 'Create Discount');
}

/**
 * Update an existing Eventbrite discount
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 * 
 * @param string $discount_id Eventbrite discount ID
 * @param string $code Discount code (optional - can be empty string to keep existing)
 * @param float $percent_off Discount percentage
 * @param string $expiry_date Optional expiry date (YYYY-MM-DD)
 * @return array|WP_Error Updated discount data or error
 */
function ccs_update_eventbrite_discount($discount_id, $code = '', $percent_off = 20.0, $expiry_date = '') {
    if (empty($discount_id)) {
        return new WP_Error('invalid_discount_id', 'Discount ID cannot be empty');
    }
    
    $percent_off = floatval($percent_off);
    if ($percent_off < 1.0 || $percent_off > 100.0) {
        return new WP_Error('invalid_percentage', 'Discount percentage must be between 1 and 100');
    }
    
    $discount_payload = [
        'discount' => [
            'percent_off' => $percent_off,
        ]
    ];
    
    if (!empty($code)) {
        $discount_payload['discount']['code'] = sanitize_text_field($code);
    }
    
    if (!empty($expiry_date)) {
        $date_obj = DateTime::createFromFormat('Y-m-d', $expiry_date);
        if ($date_obj && $date_obj->format('Y-m-d') === $expiry_date) {
            $expiry_datetime = $expiry_date . 'T23:59:59';
            $discount_payload['discount']['end_date'] = $expiry_datetime;
        } else {
            error_log('Eventbrite Discount: Invalid expiry date format: ' . $expiry_date);
        }
    }
    
    $url = "https://www.eventbriteapi.com/v3/discounts/{$discount_id}/";
    
    return ccs_eventbrite_api_request($url, [
        'method' => 'POST',
        'body' => json_encode($discount_payload),
    ], 'Update Discount');
}

/**
 * Delete an Eventbrite discount
 * 
 * Note: Eventbrite discounts that have been used cannot be deleted.
 * If deletion fails, we'll try to disable it by setting an expiry date in the past.
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 * 
 * @param string $discount_id Eventbrite discount ID
 * @return array ['success' => bool, 'method' => 'deleted'|'expired'|'error', 'message' => string]|WP_Error
 */
function ccs_delete_eventbrite_discount($discount_id) {
    if (empty($discount_id)) {
        return new WP_Error('invalid_discount_id', 'Discount ID cannot be empty');
    }
    
    $url = "https://www.eventbriteapi.com/v3/discounts/{$discount_id}/";
    
    $result = ccs_eventbrite_api_request($url, [
        'method' => 'DELETE',
    ], 'Delete Discount');
    
    if (!is_wp_error($result)) {
        return ['success' => true, 'method' => 'deleted', 'message' => 'Discount deleted from Eventbrite'];
    }
    
    $error_data = $result->get_error_data();
    $error_code = isset($error_data['error_code']) ? $error_data['error_code'] : '';
    
    if ($error_code === 'DISCOUNT_CANNOT_BE_DELETED') {
        error_log('Eventbrite Discount Cannot Be Deleted (used): ' . $discount_id . '. Attempting to expire instead...');
        
        $update_result = ccs_update_eventbrite_discount($discount_id, '', 20.0, date('Y-m-d', strtotime('-1 day')));
        
        if (!is_wp_error($update_result)) {
            return [
                'success' => true,
                'method' => 'expired',
                'message' => 'Discount has been used and cannot be deleted. Set expiry date to yesterday to disable it.'
            ];
        } else {
            error_log('Eventbrite Discount Expire Failed: ' . $update_result->get_error_message());
            return [
                'success' => false,
                'method' => 'error',
                'message' => 'Discount has been used and cannot be deleted. Failed to set expiry date: ' . $update_result->get_error_message()
            ];
        }
    }
    
    return $result;
}

/**
 * Sync WordPress discount code to Eventbrite
 * 
 * Follows best practices: graceful degradation, error handling, logging.
 * If Eventbrite sync fails, WordPress code remains functional.
 * 
 * @param array $code_data WordPress discount code data
 * @param int $index Code index in array
 * @return array Updated code data with eventbrite_discount_id
 */
function ccs_sync_discount_code_to_eventbrite($code_data, $index) {
    // Validate required data (graceful degradation - best practice)
    $code = $code_data['code'] ?? '';
    if (empty($code)) {
        error_log('Eventbrite Discount Sync: Code data missing discount code');
        return $code_data;
    }
    
    $expiry_date = $code_data['expiry_date'] ?? '';
    $active = $code_data['active'] ?? true;
    $sync_to_eventbrite = $code_data['sync_to_eventbrite'] ?? false;
    $eventbrite_discount_id = $code_data['eventbrite_discount_id'] ?? '';
    
    $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
    $org_id = get_option('ccs_eventbrite_organization_id', '');
    
    if (empty($oauth_token) || empty($org_id)) {
        error_log('Eventbrite Discount Sync: Credentials not configured for code ' . $code);
        return $code_data;
    }
    
    if (!$sync_to_eventbrite) {
        if (!empty($eventbrite_discount_id)) {
            $delete_result = ccs_delete_eventbrite_discount($eventbrite_discount_id);
            if (!is_wp_error($delete_result) && is_array($delete_result) && $delete_result['success']) {
                if ($delete_result['method'] === 'deleted') {
                    unset($code_data['eventbrite_discount_id']);
                    error_log('Eventbrite Discount Sync: Removed sync for code ' . $code);
                } else {
                    error_log('Eventbrite Discount Sync: Code ' . $code . ' expired on Eventbrite (was used)');
                }
            } elseif (is_wp_error($delete_result)) {
                error_log('Eventbrite Discount Sync: Failed to remove code ' . $code . ' - ' . $delete_result->get_error_message());
            }
        }
        return $code_data;
    }
    
    $percent_off = 20.0;
    
    if (!$active) {
        if (!empty($eventbrite_discount_id)) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $result = ccs_update_eventbrite_discount($eventbrite_discount_id, $code, $percent_off, $yesterday);
            if (!is_wp_error($result)) {
                $code_data['eventbrite_expired'] = true;
                error_log('Eventbrite Discount Sync: Expired inactive code ' . $code);
            } else {
                error_log('Eventbrite Discount Expire Error for code ' . $code . ': ' . $result->get_error_message());
            }
        }
        return $code_data;
    }
    
    if (!empty($eventbrite_discount_id)) {
        $result = ccs_update_eventbrite_discount($eventbrite_discount_id, $code, $percent_off, $expiry_date);
        if (is_wp_error($result)) {
            error_log('Eventbrite Discount Update Error for code ' . $code . ': ' . $result->get_error_message());
        } else {
            unset($code_data['eventbrite_expired']);
            error_log('Eventbrite Discount Sync: Updated code ' . $code);
        }
    } else {
        $result = ccs_create_eventbrite_discount($code, $percent_off, $expiry_date);
        if (!is_wp_error($result) && isset($result['id'])) {
            $code_data['eventbrite_discount_id'] = $result['id'];
            unset($code_data['eventbrite_expired']);
            error_log('Eventbrite Discount Sync: Created code ' . $code . ' (ID: ' . $result['id'] . ')');
        } else {
            $error_msg = is_wp_error($result) ? $result->get_error_message() : 'Unknown error';
            error_log('Eventbrite Discount Create Error for code ' . $code . ': ' . $error_msg);
            // Graceful degradation - WordPress code still works even if Eventbrite sync fails
        }
    }
    
    return $code_data;
}

/**
 * Upload event to Eventbrite when WordPress event is saved
 */
function ccs_upload_event_to_eventbrite($post_id) {
    $auto_upload = get_option('ccs_eventbrite_auto_upload', 1);
    if (!$auto_upload) {
        return;
    }
    
    if (get_post_type($post_id) !== 'course_event') {
        return;
    }
    
    if (get_post_meta($post_id, '_ccs_eventbrite_syncing', true)) {
        return;
    }
    
    if (!function_exists('get_field')) {
        return;
    }
    
    $course = get_field('linked_course', $post_id);
    $event_date = get_field('event_date', $post_id);
    
    if (!$course || !$event_date) {
        return;
    }
    
    update_post_meta($post_id, '_ccs_eventbrite_syncing', true);
    
    $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
    $org_id = get_option('ccs_eventbrite_organization_id', '');
    
    if (empty($oauth_token) || empty($org_id)) {
        delete_post_meta($post_id, '_ccs_eventbrite_syncing');
        return;
    }
    
    $eventbrite_id = get_post_meta($post_id, 'eventbrite_id', true);
    
    $event_data = ccs_prepare_eventbrite_event_data($post_id);
    
    if (is_wp_error($event_data)) {
        error_log('Eventbrite Upload Error: ' . $event_data->get_error_message());
        delete_post_meta($post_id, '_ccs_eventbrite_syncing');
        return;
    }
    
    $result = null;
    $was_update = false;
    
    if ($eventbrite_id) {
        $was_update = true;
        update_post_meta($post_id, '_ccs_eventbrite_was_update', true);
        
        $result = ccs_update_eventbrite_event($eventbrite_id, $event_data, $oauth_token);
        
        if (is_wp_error($result)) {
            $error_data = $result->get_error_data();
            $error_code = isset($error_data['error_code']) ? $error_data['error_code'] : '';
            $http_code = isset($error_data['http_code']) ? $error_data['http_code'] : 0;
            
            if ($error_code === 'NOT_FOUND' || $http_code === 404) {
                error_log('Eventbrite event not found, creating new event for post ' . $post_id);
                delete_post_meta($post_id, 'eventbrite_id');
                delete_post_meta($post_id, 'eventbrite_url');
                delete_post_meta($post_id, '_ccs_eventbrite_was_update');
                
                $eventbrite_id = null;
                $was_update = false;
            } else {
                error_log('Eventbrite Update Error: ' . $result->get_error_message());
                delete_post_meta($post_id, '_ccs_eventbrite_syncing');
                delete_post_meta($post_id, '_ccs_eventbrite_was_update');
                
                $error_message = ccs_eventbrite_error_message($error_code, $result->get_error_message());
                set_transient('ccs_eventbrite_upload_' . $post_id, [
                    'success' => false,
                    'message' => 'Failed to update Eventbrite event: ' . $error_message,
                    'error_code' => $error_code
                ], 30);
                return;
            }
        } else {
            if (isset($event_data['ticket_class'])) {
                ccs_update_or_create_ticket_class($eventbrite_id, $event_data['ticket_class'], $oauth_token);
            }
            
            // Sync FAQs via Structured Content API
            if (!empty($event_data['faqs']) && is_array($event_data['faqs'])) {
                ccs_sync_eventbrite_faqs_structured_content($eventbrite_id, $event_data['faqs'], $oauth_token);
            }
            
            if (isset($result['url'])) {
                update_post_meta($post_id, 'eventbrite_url', $result['url']);
            }
        }
    }
    
    if (!$eventbrite_id) {
        $result = ccs_create_eventbrite_event($event_data, $oauth_token, $org_id);
        
        if (!is_wp_error($result) && isset($result['id'])) {
            $new_event_id = $result['id'];
            update_post_meta($post_id, 'eventbrite_id', $new_event_id);
            update_post_meta($post_id, 'eventbrite_url', $result['url'] ?? '');
            
            if (isset($event_data['ticket_class'])) {
                ccs_create_eventbrite_ticket_class($new_event_id, $event_data['ticket_class'], $oauth_token);
            }
            
            // Sync FAQs via Structured Content API
            if (!empty($event_data['faqs']) && is_array($event_data['faqs'])) {
                ccs_sync_eventbrite_faqs_structured_content($new_event_id, $event_data['faqs'], $oauth_token);
            }
            
            $auto_publish = get_option('ccs_eventbrite_auto_publish', 1);
            if ($auto_publish) {
                $publish_result = ccs_publish_eventbrite_event($new_event_id, $oauth_token);
                if (!is_wp_error($publish_result)) {
                    update_post_meta($post_id, 'eventbrite_status', 'live');
                }
            }
        }
    }
    
    delete_post_meta($post_id, '_ccs_eventbrite_syncing');
    
    if (isset($result) && is_wp_error($result)) {
        error_log('Eventbrite Upload Error: ' . $result->get_error_message());
        $error_data = $result->get_error_data();
        $error_code = isset($error_data['error_code']) ? $error_data['error_code'] : '';
        $error_message = ccs_eventbrite_error_message($error_code, $result->get_error_message());
        
        set_transient('ccs_eventbrite_upload_' . $post_id, [
            'success' => false,
            'message' => $error_message,
            'error_code' => $error_code
        ], 30);
    } elseif (isset($result) && !is_wp_error($result)) {
        update_post_meta($post_id, '_eventbrite_last_sync', current_time('mysql'));
        
        $eventbrite_url = get_post_meta($post_id, 'eventbrite_url', true);
        $eventbrite_id = get_post_meta($post_id, 'eventbrite_id', true);
        
        $was_update = get_post_meta($post_id, '_ccs_eventbrite_was_update', true);
        delete_post_meta($post_id, '_ccs_eventbrite_was_update');
        
        if ($was_update) {
            $success_message = 'Event updated on Eventbrite successfully!';
        } else {
            $success_message = 'Event uploaded to Eventbrite successfully!';
        }
        
        if ($eventbrite_url) {
            $success_message .= ' <a href="' . esc_url($eventbrite_url) . '" target="_blank">View on Eventbrite →</a>';
        }
        
        set_transient('ccs_eventbrite_upload_' . $post_id, [
            'success' => true,
            'message' => $success_message,
            'url' => $eventbrite_url
        ], 30);
    }
}

/**
 * Prepare event data for Eventbrite API
 */
function ccs_prepare_eventbrite_event_data($post_id) {
    $course = get_field('linked_course', $post_id);
    $event_date = get_field('event_date', $post_id);
    $start_time = get_field('start_time', $post_id);
    $end_time = get_field('end_time', $post_id);
    $location = get_field('event_location', $post_id);
    $total_spaces = get_field('total_spaces', $post_id);
    $event_price = get_field('event_price', $post_id);
    $event_active = get_field('event_active', $post_id);
    
    if (!$course || !$event_date) {
        return new WP_Error('missing_data', 'Course and event date are required');
    }
    
    $eventbrite_description = get_field('eventbrite_description', $post_id);
    $eventbrite_summary = get_field('eventbrite_summary', $post_id);
    $eventbrite_custom_name = get_field('eventbrite_custom_name', $post_id);
    
    if ($eventbrite_custom_name) {
        $event_name = $eventbrite_custom_name;
    } else {
        $event_name = ccs_generate_eventbrite_event_name($post_id);
    }
    
    if (empty($eventbrite_description)) {
        $eventbrite_description = ccs_generate_eventbrite_description($post_id);
        if (empty($eventbrite_description)) {
            $course_description = get_field('course_description', $course->ID);
            $eventbrite_description = '<p>' . nl2br(esc_html($course_description)) . '</p>';
        }
    }
    
    if (empty($eventbrite_summary)) {
        $eventbrite_summary = ccs_generate_eventbrite_summary($post_id);
    }
    
    $start_local = $event_date;
    if ($start_time) {
        $start_local .= 'T' . $start_time . ':00';
    } else {
        $start_local .= 'T09:00:00';
    }
    
    $end_local = $event_date;
    if ($end_time) {
        $end_local .= 'T' . $end_time . ':00';
    } else {
        $start_obj = new DateTime($start_local, new DateTimeZone('Europe/London'));
        $start_obj->modify('+4 hours');
        $end_local = $start_obj->format('Y-m-d\TH:i:s');
    }
    
    $venue_id = ccs_get_or_create_eventbrite_venue($location);
    $tags = ccs_generate_eventbrite_tags($post_id);
    
    // Get FAQs from ACF repeater field
    $eventbrite_faqs = get_field('eventbrite_faqs', $post_id);
    $faqs_array = [];
    if (is_array($eventbrite_faqs) && !empty($eventbrite_faqs)) {
        foreach ($eventbrite_faqs as $faq) {
            if (isset($faq['question']) && isset($faq['answer']) && !empty($faq['question']) && !empty($faq['answer'])) {
                $faqs_array[] = [
                    'question' => sanitize_text_field($faq['question']),
                    'answer' => sanitize_textarea_field($faq['answer']),
                ];
            }
        }
    }
    
    $event_payload = [
        'event' => [
            'name' => [
                'text' => strip_tags($event_name), // Plain text version
                'html' => esc_html($event_name)    // HTML version
            ],
            'description' => [
                'text' => wp_strip_all_tags($eventbrite_description), // Plain text version
                'html' => wp_kses_post($eventbrite_description)       // HTML version (preserves formatting)
            ],
            'summary' => $eventbrite_summary,
            'start' => [
                'timezone' => 'Europe/London',
                'local' => $start_local
            ],
            'end' => [
                'timezone' => 'Europe/London',
                'local' => $end_local
            ],
            'currency' => 'GBP',
            'online_event' => false,
        ]
    ];
    
    if ($venue_id) {
        $event_payload['event']['venue_id'] = $venue_id;
    }
    
    if ($total_spaces && $total_spaces > 0) {
        $event_payload['event']['capacity'] = intval($total_spaces);
    }
    
    $ticket_class = null;
    if ($event_price && $event_price > 0) {
        $capacity = $total_spaces ?: 12;
        $ticket_class = [
            'ticket_class' => [
                'name' => 'Standard Ticket',
                'description' => 'Standard admission ticket',
                'quantity_total' => intval($capacity),
                'free' => false,
                'cost' => number_format($event_price, 2, '.', ''),
                'currency' => 'GBP'
            ]
        ];
    } else {
        $capacity = $total_spaces ?: 12;
        $ticket_class = [
            'ticket_class' => [
                'name' => 'Free Ticket',
                'description' => 'Free admission',
                'quantity_total' => intval($capacity),
                'free' => true
            ]
        ];
    }
    
    return [
        'event' => $event_payload['event'],
        'ticket_class' => $ticket_class,
        'tags' => $tags,
        'faqs' => $faqs_array,
    ];
}

/**
 * Generate SEO-optimized Eventbrite event name
 * Based on Eventbrite SEO best practices: include keywords, location, date
 */
function ccs_generate_eventbrite_event_name($post_id) {
    $course = get_field('linked_course', $post_id);
    if (!$course) {
        return '';
    }
    
    $course_title = $course->post_title;
    $event_date = get_field('event_date', $post_id);
    $location = get_field('event_location', $post_id);
    $duration = get_field('course_duration', $course->ID);
    
    // Build SEO-optimized name with keyword-rich structure
    // Format: [Course Title] - [Duration] Training - [Date] - [Location]
    $name = $course_title;
    
    // Add training/workshop keyword for SEO
    if (stripos($course_title, 'training') === false && 
        stripos($course_title, 'workshop') === false &&
        stripos($course_title, 'course') === false) {
        $name .= ' Training';
    }
    
    if ($duration) {
        $name .= ' - ' . $duration;
    }
    
    if ($event_date) {
        $formatted_date = date('j M Y', strtotime($event_date));
        $name .= ' - ' . $formatted_date;
    }
    
    // Always include location for local SEO (Maidstone, Kent)
    if ($location && $location !== 'The Maidstone Studios') {
        // Extract city if full address
        if (stripos($location, 'Maidstone') !== false) {
            $name .= ' - Maidstone, Kent';
        } else {
            $name .= ' - ' . $location;
        }
    } else {
        $name .= ' - Maidstone, Kent';
    }
    
    // Ensure name is not too long (Eventbrite has limits, but also for SEO)
    if (strlen($name) > 100) {
        // Trim date if too long
        $name = $course_title;
        if ($duration) {
            $name .= ' - ' . $duration;
        }
        $name .= ' - Maidstone, Kent';
    }
    
    return $name;
}

/**
 * Generate SEO-optimized Eventbrite summary (140 characters)
 * Includes keywords and location for search visibility
 */
function ccs_generate_eventbrite_summary($post_id) {
    $course = get_field('linked_course', $post_id);
    if (!$course) {
        return '';
    }
    
    $course_title = $course->post_title;
    $event_date = get_field('event_date', $post_id);
    $location = get_field('event_location', $post_id);
    $duration = get_field('course_duration', $course->ID);
    $accreditation = get_field('course_accreditation', $course->ID);
    
    $formatted_date = $event_date ? date('j M Y', strtotime($event_date)) : '';
    
    $summary_parts = [];
    $summary_parts[] = $course_title;
    
    if (stripos($course_title, 'training') === false && 
        stripos($course_title, 'workshop') === false) {
        $summary_parts[] = 'Training';
    }
    
    if ($location && stripos($location, 'Maidstone') !== false) {
        $summary_parts[] = 'Maidstone, Kent';
    } elseif ($location) {
        $summary_parts[] = $location;
    } else {
        $summary_parts[] = 'Maidstone, Kent';
    }
    
    if ($formatted_date) {
        $summary_parts[] = $formatted_date;
    }
    
    if ($duration) {
        $summary_parts[] = "({$duration})";
    }
    
    $summary = implode(' - ', $summary_parts);
    
    if ($accreditation && strlen($summary) < 100) {
        $summary .= ". {$accreditation}";
    }
    
    if (strlen($summary) > 140) {
        if ($duration) {
            $summary = str_replace(" ({$duration})", '', $summary);
        }
        
        if (strlen($summary) > 140) {
            $base = $course_title . ' Training - Maidstone, Kent';
            if (strlen($base) < 100 && $formatted_date) {
                $summary = $base . ' - ' . $formatted_date;
            } else {
                $summary = $base;
            }
            
            if (strlen($summary) > 140) {
                $summary = substr($summary, 0, 137) . '...';
            }
        }
    }
    
    return $summary;
}

/**
 * Generate Eventbrite tags
 */
function ccs_generate_eventbrite_tags($post_id) {
    $course = get_field('linked_course', $post_id);
    if (!$course) {
        return [];
    }
    
    $tags = [];
    
    // Add location tags
    $tags[] = 'Maidstone';
    $tags[] = 'Kent';
    $tags[] = 'UK Training';
    
    // Add course category
    $terms = get_the_terms($course->ID, 'course_category');
    if ($terms && !is_wp_error($terms)) {
        $tags[] = $terms[0]->name;
    }
    
    // Add accreditation if available
    $accreditation = get_field('course_accreditation', $course->ID);
    if ($accreditation) {
        if (stripos($accreditation, 'CPD') !== false) {
            $tags[] = 'CPD';
        }
        if (stripos($accreditation, 'CQC') !== false) {
            $tags[] = 'CQC';
        }
    }
    
    // Add common care training tags
    $tags[] = 'Care Training';
    $tags[] = 'Professional Development';
    
    return array_unique($tags);
}

/**
 * Enhanced AI description generation with Groq API
 * Generates HTML-formatted description for Eventbrite
 */
function ccs_generate_eventbrite_description($post_id) {
    // AI: try Groq first, then fall back to other configured providers
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    
    // Get course and event data
    $course = get_field('linked_course', $post_id);
    if (!$course) {
        return false;
    }
    
    $event_date = get_field('event_date', $post_id);
    $start_time = get_field('start_time', $post_id);
    $end_time = get_field('end_time', $post_id);
    $location = get_field('event_location', $post_id);
    $duration = get_field('course_duration', $course->ID);
    $price = get_field('event_price', $post_id) ?: get_field('course_price', $course->ID);
    
    // Get course details
    $course_title = $course->post_title;
    $course_description = get_field('course_description', $course->ID);
    $outcomes = function_exists('ccs_get_outcomes') ? ccs_get_outcomes($course->ID) : get_field('course_outcomes', $course->ID);
    $accreditation = get_field('course_accreditation', $course->ID);
    $suitable_for = get_field('course_suitable_for', $course->ID);
    $prerequisites = get_field('course_prerequisites', $course->ID);
    
    // Format date with full context
    $formatted_date = '';
    $day_of_week = '';
    $month_name = '';
    $season = '';
    $time_context = '';
    
    if ($event_date) {
        $date_obj = new DateTime($event_date);
        $formatted_date = $date_obj->format('l, j F Y'); // e.g., "Wednesday, 15 January 2026"
        $day_of_week = $date_obj->format('l');
        $month_name = $date_obj->format('F');
        
        // Determine season
        $month = (int)$date_obj->format('n');
        if ($month >= 3 && $month <= 5) {
            $season = 'spring';
        } elseif ($month >= 6 && $month <= 8) {
            $season = 'summer';
        } elseif ($month >= 9 && $month <= 11) {
            $season = 'autumn';
        } else {
            $season = 'winter';
        }
        
        // Time context
        if ($start_time) {
            $time_parts = explode(':', $start_time);
            $hour = (int)$time_parts[0];
            if ($hour < 12) {
                $time_context = 'morning';
            } elseif ($hour < 17) {
                $time_context = 'afternoon';
            } else {
                $time_context = 'evening';
            }
        }
    }
    
    $outcomes_text = 'N/A';
    if (is_array($outcomes) && !empty($outcomes)) {
        $outcome_list = [];
        foreach ($outcomes as $outcome) {
            if (is_array($outcome) && isset($outcome['outcome_text'])) {
                $outcome_list[] = $outcome['outcome_text'];
            } elseif (is_string($outcome)) {
                $outcome_list[] = $outcome;
            }
        }
        $outcomes_text = !empty($outcome_list) ? implode("\n", $outcome_list) : 'N/A';
    }
    
    $location_parts = [];
    if (stripos($location, 'Maidstone') !== false) {
        $location_parts[] = 'Maidstone';
    }
    if (stripos($location, 'Kent') !== false || stripos($location, 'Maidstone') !== false) {
        $location_parts[] = 'Kent';
    }
    $location_keywords = !empty($location_parts) ? implode(', ', $location_parts) : $location;
    
    // Build keyword-rich title variations for SEO
    $course_keywords = [
        strtolower($course_title),
        'training course',
        'professional development',
        'CPD training',
        'care training',
        'workshop',
        'event',
    ];
    
    // Build comprehensive SEO-optimized prompt based on Eventbrite best practices
    $prompt = "Create a highly SEO-optimized, engaging event description for Eventbrite that will rank well in Google search results. Follow Eventbrite's SEO best practices for event listings.

EVENT DETAILS:
- Course Title: {$course_title}
- Date: {$formatted_date} ({$day_of_week}, {$season})
- Time: " . ($start_time ? date('g:i A', strtotime($start_time)) : 'TBC') . 
        ($end_time ? ' - ' . date('g:i A', strtotime($end_time)) : '') . "
- Location: {$location} ({$location_keywords})
- Duration: {$duration}
- Price: £" . number_format($price, 0) . "
- Event Type: Training Course / Workshop

COURSE INFORMATION:
" . ($course_description ?: 'No description available') . "

LEARNING OUTCOMES:
{$outcomes_text}

" . ($accreditation ? "ACCREDITATION: {$accreditation}\n" : '') . "
" . ($suitable_for ? "SUITABLE FOR: {$suitable_for}\n" : '') . "
" . ($prerequisites ? "PREREQUISITES: {$prerequisites}\n" : '') . "

SEO OPTIMIZATION REQUIREMENTS (Based on Eventbrite's SEO Guidelines):

1. KEYWORD OPTIMIZATION:
   - Naturally include primary keywords: '{$course_title}', 'training course', 'professional development', 'CPD training', 'care training', 'workshop', 'event in {$location_keywords}'
   - Include secondary keywords: 'training {$location_keywords}', '{$course_title} {$location_keywords}', 'CPD {$location_keywords}', 'care training {$location_keywords}'
   - Use location keywords (Maidstone, Kent) at least 5-7 times throughout the description for local SEO
   - Include date-specific keywords: '{$formatted_date}', '{$day_of_week} training', '{$season} training'
   - Use event-related keywords: 'tickets', 'register', 'book now', 'event registration', 'training event'

2. CONTENT STRUCTURE (HTML Format):
   - Start with a compelling opening paragraph (2-3 sentences) that includes:
     * Course title
     * Date and time
     * Location (Maidstone, Kent)
     * Primary keyword naturally integrated
   - Use <h3> tags for these REQUIRED sections:
     * <h3>What You'll Learn</h3> - List learning outcomes
     * <h3>Who Should Attend</h3> - Target audience
     * <h3>Why Choose This Training</h3> - Benefits and value proposition
     * <h3>Event Details</h3> - Date, time, location, duration
     * <h3>About the Location</h3> - Brief mention of venue/location benefits
   - Use <p> tags for paragraphs (2-4 sentences each)
   - Use <ul> and <li> for lists (learning outcomes, benefits, features)
   - Use <strong> to emphasize key points, keywords, and CTAs
   - Include 2-3 clear call-to-action statements using <strong> tags:
     * 'Book your place today'
     * 'Secure your ticket now'
     * 'Register for this essential training'

3. LOCAL SEO OPTIMIZATION:
   - Mention 'Maidstone, Kent' or '{$location_keywords}' in:
     * Opening paragraph
     * Event Details section
     * About the Location section
     * Closing paragraph
   - Include phrases like: 'training in Maidstone', 'Kent training course', 'Maidstone event', 'Kent professional development'
   - Reference local benefits if relevant (accessibility, parking, transport links)

4. CONTENT QUALITY (Google's E-E-A-T Principles):
   - Authority: Mention accreditation, CQC compliance, professional standards
   - Experience: Reference practical, hands-on learning
   - Expertise: Highlight course quality and trainer credentials (if available)
   - Trustworthiness: Include clear pricing, secure booking, professional venue

5. MOBILE OPTIMIZATION (Critical - Most Eventbrite users browse on mobile):
   - Keep paragraphs short (2-4 sentences max)
   - Use short sentences (under 15 words each)
   - Break text into single-line sections where possible
   - Use bullet points for easy scanning
   - Clear section headings for quick navigation
   - Prioritize critical information at the top of the description
   - Scannable format with plenty of white space
   - Test readability on mobile devices

6. ENGAGEMENT & CONVERSION:
   - Create urgency: 'Limited spaces available', 'Book early to secure your place'
   - Highlight value: What attendees will gain, ROI, career benefits
   - Address objections: Why this training is essential, what makes it different
   - Social proof: Professional venue, accredited training, trusted provider

7. SEASONAL & CONTEXTUAL RELEVANCE:
   - {$season} context: " . ($season === 'spring' ? 'New year training goals, fresh starts, spring professional development' : 
                  ($season === 'summer' ? 'Summer training schedules, prepare for busy periods, mid-year development' :
                  ($season === 'autumn' ? 'Autumn training, prepare for winter, Q4 professional development' : 'Winter training, year-end compliance, annual training goals'))) . "
   - {$day_of_week} context: " . (in_array($day_of_week, ['Saturday', 'Sunday']) ? 'Weekend training, convenient scheduling' : 'Mid-week professional development, flexible training') . "
   - Time context: " . ($time_context === 'morning' ? 'Morning session, start your day with professional development' : 
                        ($time_context === 'afternoon' ? 'Afternoon training, convenient timing' : 'Evening session, after-work professional development')) . "

8. WRITING STYLE:
   - British English spelling and terminology
   - Professional yet warm and approachable tone
   - Active voice where possible
   - Clear, concise language (avoid jargon unless necessary)
   - 150-200 words optimal for mobile engagement and search ranking (maximum 2,500 characters allowed)
   - Natural keyword integration (never keyword stuffing)

9. UNIQUE CONTENT REQUIREMENT (Critical for SEO):
   - Generate Eventbrite-specific content that differs from any website course descriptions
   - Do not duplicate content from the course page or other sources (Google penalizes duplicate content)
   - Optimize for Eventbrite's discovery algorithm and Eventbrite user base
   - Write unique copy specifically for Eventbrite's audience and search context

10. STRUCTURED DATA READINESS:
   - Include all event details clearly (date, time, location, price)
   - Use consistent formatting for dates and times
   - Clear venue/location information
   - Pricing information clearly stated

11. FINAL REQUIREMENTS:
    - Return ONLY clean HTML (no markdown, no code blocks)
    - Ensure all HTML tags are properly closed
    - No external links or images
    - Focus on conversion: Every paragraph should move reader toward booking
    - End with a strong CTA encouraging immediate registration

Return the complete HTML description following all these requirements.";

    // Enhanced system prompt with SEO expertise
    $system_prompt = "You are an expert SEO copywriter and event marketing specialist with deep knowledge of:
- Eventbrite's SEO best practices and ranking factors
- Google's search algorithm and E-E-A-T principles
- Local SEO optimization for events
- Conversion-focused event copywriting
- Professional training course marketing in the UK care sector

You specialize in creating HTML-formatted event descriptions that:
1. Rank highly in Google search results for event-related keywords
2. Convert readers into ticket buyers
3. Optimize for local search (Maidstone, Kent area)
4. Follow Eventbrite's content guidelines for maximum visibility
5. Use proper semantic HTML structure for search engine crawlers

Your descriptions are always:
- SEO-optimized with natural keyword integration
- Mobile-friendly and scannable
- Trustworthy and authoritative
- Conversion-focused with clear CTAs
- Properly structured with semantic HTML

Always format responses as clean, semantic HTML ready for Eventbrite's platform.";

    $result = ccs_ai_try_providers($preferred_provider, function($provider, $api_key) use ($system_prompt, $prompt) {
        if ($provider === 'groq' && function_exists('ccs_call_groq_api')) {
            return ccs_call_groq_api($api_key, $system_prompt, $prompt);
        }
        if ($provider === 'anthropic' && function_exists('ccs_call_anthropic_api')) {
            return ccs_call_anthropic_api($api_key, $system_prompt, $prompt);
        }
        if ($provider === 'openai' && function_exists('ccs_call_openai_api')) {
            return ccs_call_openai_api($api_key, $system_prompt, $prompt);
        }
        return new WP_Error('api_error', 'Provider not available');
    });
    
    if (is_wp_error($result)) {
        error_log('Eventbrite AI Description Error: ' . $result->get_error_message());
        return false;
    }
    
    // Clean up the result (remove markdown code blocks if present)
    $description = trim($result);
    $description = preg_replace('/^```html\s*/i', '', $description);
    $description = preg_replace('/^```\s*/i', '', $description);
    $description = preg_replace('/```\s*$/i', '', $description);
    $description = trim($description);
    
    return $description;
}

/**
 * Auto-generate Eventbrite description when event is saved (if empty)
 */
function ccs_auto_generate_eventbrite_description($post_id) {
    // Only for course_event
    if (get_post_type($post_id) !== 'course_event') {
        return;
    }
    
    // Check if ACF is available
    if (!function_exists('get_field') || !function_exists('update_field')) {
        return;
    }
    
    // Only generate if field is empty
    $current_description = get_field('eventbrite_description', $post_id);
    if (!empty($current_description)) {
        return; // User has already set a description
    }
    
    // Generate with AI
    $description = ccs_generate_eventbrite_description($post_id);
    if ($description) {
        update_field('eventbrite_description', $description, $post_id);
    }
    
    // Also generate summary if empty
    $current_summary = get_field('eventbrite_summary', $post_id);
    if (empty($current_summary)) {
        $summary = ccs_generate_eventbrite_summary($post_id);
        if ($summary) {
            update_field('eventbrite_summary', $summary, $post_id);
        }
    }
}
add_action('acf/save_post', 'ccs_auto_generate_eventbrite_description', 15);

/**
 * Get or create Eventbrite venue
 * Defaults to "The Maidstone Studios" unless location field specifies otherwise
 */
function ccs_get_or_create_eventbrite_venue($location = '') {
    $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
    $org_id = get_option('ccs_eventbrite_organization_id', '');
    
    if (empty($oauth_token) || empty($org_id)) {
        return '';
    }
    
    // Default venue name
    $default_venue_name = 'The Maidstone Studios';
    
    // Use location field if set and different from default
    $venue_name = $location && $location !== $default_venue_name ? $location : $default_venue_name;
    
    // Check if we have a cached venue ID for this name
    $venue_cache_key = 'ccs_eventbrite_venue_' . md5($venue_name);
    $cached_venue_id = get_transient($venue_cache_key);
    
    if ($cached_venue_id) {
        return $cached_venue_id;
    }
    
    // Check if default venue ID is stored in settings
    $default_venue_id = get_option('ccs_eventbrite_venue_id', '');
    if ($default_venue_id && $venue_name === $default_venue_name) {
        set_transient($venue_cache_key, $default_venue_id, DAY_IN_SECONDS * 30);
        return $default_venue_id;
    }
    
    // Search for existing venue
    $venue_id = ccs_search_eventbrite_venue($venue_name, $org_id, $oauth_token);
    
    if ($venue_id) {
        set_transient($venue_cache_key, $venue_id, DAY_IN_SECONDS * 30);
        return $venue_id;
    }
    
    // Create new venue if not found
    if ($venue_name === $default_venue_name) {
        // Create The Maidstone Studios venue with known address
        $venue_data = [
            'venue' => [
                'name' => 'The Maidstone Studios',
                'address' => [
                    'address_1' => 'The Maidstone Studios, New Cut Road',
                    'city' => 'Maidstone',
                    'region' => 'Kent',
                    'postal_code' => 'ME14 5NZ',
                    'country' => 'GB'
                ]
            ]
        ];
    } else {
        // Create venue with just name (address can be added later in Eventbrite)
        $venue_data = [
            'venue' => [
                'name' => $venue_name,
                'address' => [
                    'city' => 'Maidstone',
                    'region' => 'Kent',
                    'country' => 'GB'
                ]
            ]
        ];
    }
    
    $venue_id = ccs_create_eventbrite_venue($venue_data, $org_id, $oauth_token);
    
    if ($venue_id) {
        set_transient($venue_cache_key, $venue_id, DAY_IN_SECONDS * 30);
        
        // Store default venue ID in settings if it's The Maidstone Studios
        if ($venue_name === $default_venue_name && !$default_venue_id) {
            update_option('ccs_eventbrite_venue_id', $venue_id);
        }
        
        return $venue_id;
    }
    
    return '';
}

/**
 * Search for existing venue by name
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 */
function ccs_search_eventbrite_venue($venue_name, $org_id, $oauth_token) {
    if (empty($org_id) || empty($venue_name)) {
        return '';
    }
    
    $url = "https://www.eventbriteapi.com/v3/organizations/{$org_id}/venues/";
    
    $data = ccs_eventbrite_api_request($url, [
        'method' => 'GET',
        'timeout' => 15,
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
    ], 'Search Venues');
    
    if (is_wp_error($data)) {
        return '';
    }
    
    if (isset($data['venues'])) {
        foreach ($data['venues'] as $venue) {
            if (isset($venue['name']) && strtolower(trim($venue['name'])) === strtolower(trim($venue_name))) {
                return $venue['id'];
            }
        }
    }
    
    return '';
}

/**
 * Create venue on Eventbrite
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 */
function ccs_create_eventbrite_venue($venue_data, $org_id, $oauth_token) {
    if (empty($org_id)) {
        error_log('Eventbrite Venue Creation: Organization ID is required');
        return '';
    }
    
    $url = "https://www.eventbriteapi.com/v3/organizations/{$org_id}/venues/";
    
    $result = ccs_eventbrite_api_request($url, [
        'method' => 'POST',
        'body' => json_encode($venue_data),
        'timeout' => 15,
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
    ], 'Create Venue');
    
    if (is_wp_error($result)) {
        return '';
    }
    
    return isset($result['id']) ? $result['id'] : '';
}

/**
 * Create event on Eventbrite
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 */
function ccs_create_eventbrite_event($event_data, $oauth_token, $org_id) {
    if (empty($org_id)) {
        return new WP_Error('missing_org_id', 'Organization ID is required');
    }
    
    $url = "https://www.eventbriteapi.com/v3/organizations/{$org_id}/events/";
    
    $payload = [
        'event' => $event_data['event']
    ];
    
    return ccs_eventbrite_api_request($url, [
        'method' => 'POST',
        'body' => json_encode($payload),
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
    ], 'Create Event');
}

/**
 * Update event on Eventbrite
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 */
function ccs_update_eventbrite_event($eventbrite_id, $event_data, $oauth_token) {
    if (empty($eventbrite_id)) {
        return new WP_Error('missing_event_id', 'Eventbrite event ID is required');
    }
    
    $url = "https://www.eventbriteapi.com/v3/events/{$eventbrite_id}/";
    
    $payload = [
        'event' => $event_data['event']
    ];
    
    return ccs_eventbrite_api_request($url, [
        'method' => 'POST', // Eventbrite uses POST for updates
        'body' => json_encode($payload),
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
    ], 'Update Event');
}

/**
 * Publish event on Eventbrite
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 */
function ccs_publish_eventbrite_event($event_id, $oauth_token) {
    if (empty($event_id)) {
        return new WP_Error('missing_event_id', 'Eventbrite event ID is required');
    }
    
    $url = "https://www.eventbriteapi.com/v3/events/{$event_id}/publish/";
    
    return ccs_eventbrite_api_request($url, [
        'method' => 'POST',
        'timeout' => 15,
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
    ], 'Publish Event');
}

/**
 * Sync FAQs to Eventbrite via Structured Content API
 * 
 * Structured Content uses versioning and insert-only system, so we need to:
 * 1. Get current structured content version
 * 2. Increment version
 * 3. Include existing modules/widgets + new FAQ widget
 * 4. Publish the updated content
 */
function ccs_sync_eventbrite_faqs_structured_content($eventbrite_id, $faqs, $oauth_token) {
    if (empty($eventbrite_id) || empty($faqs) || !is_array($faqs)) {
        return false;
    }
    
    // Get current working version of structured content
    $current_content = ccs_get_eventbrite_structured_content($eventbrite_id, $oauth_token);
    $current_version = 1;
    $existing_modules = [];
    $existing_widgets = [];
    
    if (!is_wp_error($current_content) && isset($current_content['version'])) {
        $current_version = intval($current_content['version']) + 1;
        $existing_modules = isset($current_content['modules']) ? $current_content['modules'] : [];
        $existing_widgets = isset($current_content['widgets']) ? $current_content['widgets'] : [];
        
        // Remove any existing FAQ widgets (we'll replace with new one)
        if (!empty($existing_widgets)) {
            $existing_widgets = array_filter($existing_widgets, function($widget) {
                return !isset($widget['type']) || $widget['type'] !== 'faqs';
            });
        }
    }
    
    // Format FAQs as Eventbrite Structured Content FAQ widget
    $faq_widget = [
        'type' => 'faqs',
        'data' => [
            'faqs' => array_map(function($faq) {
                return [
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                ];
            }, $faqs),
        ],
    ];
    
    // Add FAQ widget to existing widgets
    $existing_widgets[] = $faq_widget;
    
    // Prepare structured content payload
    $structured_content = [
        'modules' => $existing_modules,
        'widgets' => $existing_widgets,
        'publish' => true, // Publish immediately
    ];
    
    // Set structured content via API
    $url = "https://www.eventbriteapi.com/v3/events/{$eventbrite_id}/structured_content/{$current_version}/";
    
    $result = ccs_eventbrite_api_request($url, [
        'method' => 'POST',
        'body' => json_encode($structured_content),
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
    ], 'Set Structured Content FAQs');
    
    if (is_wp_error($result)) {
        error_log('Eventbrite FAQ Sync Error: ' . $result->get_error_message());
        return false;
    }
    
    return true;
}

/**
 * Get current structured content from Eventbrite
 */
function ccs_get_eventbrite_structured_content($eventbrite_id, $oauth_token) {
    if (empty($eventbrite_id)) {
        return new WP_Error('missing_event_id', 'Eventbrite event ID is required');
    }
    
    // Get latest working version (published or unpublished)
    $url = "https://www.eventbriteapi.com/v3/events/{$eventbrite_id}/structured_content/edit/";
    
    return ccs_eventbrite_api_request($url, [
        'method' => 'GET',
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
        'timeout' => 15,
    ], 'Get Structured Content');
}

/**
 * Create ticket class for event
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 */
function ccs_create_eventbrite_ticket_class($event_id, $ticket_class_data, $oauth_token) {
    if (empty($event_id)) {
        error_log('Eventbrite Ticket Class: Event ID is required');
        return false;
    }
    
    $url = "https://www.eventbriteapi.com/v3/events/{$event_id}/ticket_classes/";
    
    $result = ccs_eventbrite_api_request($url, [
        'method' => 'POST',
        'body' => json_encode($ticket_class_data),
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
    ], 'Create Ticket Class');
    
    if (is_wp_error($result)) {
        return false;
    }
    
    return $result;
}

/**
 * Update or create ticket class (checks existing first)
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 */
function ccs_update_or_create_ticket_class($event_id, $ticket_class_data, $oauth_token) {
    if (empty($event_id)) {
        return false;
    }
    
    // First, get existing ticket classes
    $url = "https://www.eventbriteapi.com/v3/events/{$event_id}/ticket_classes/";
    
    $data = ccs_eventbrite_api_request($url, [
        'method' => 'GET',
        'timeout' => 15,
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
    ], 'Get Ticket Classes');
    
    if (!is_wp_error($data) && isset($data['ticket_classes']) && !empty($data['ticket_classes'])) {
        // Update first ticket class
        $ticket_class_id = $data['ticket_classes'][0]['id'];
        $update_url = "https://www.eventbriteapi.com/v3/events/{$event_id}/ticket_classes/{$ticket_class_id}/";
        
        $update_result = ccs_eventbrite_api_request($update_url, [
            'method' => 'POST',
            'body' => json_encode($ticket_class_data),
            'headers' => [
                'Authorization' => 'Bearer ' . $oauth_token,
            ],
        ], 'Update Ticket Class');
        
        if (!is_wp_error($update_result)) {
            return true;
        }
    }
    
    // If update failed or no ticket class exists, create new one
    $create_result = ccs_create_eventbrite_ticket_class($event_id, $ticket_class_data, $oauth_token);
    return $create_result !== false;
}

/**
 * Hook into event save
 */
function ccs_save_event_to_eventbrite($post_id) {
    // Only run on save_post, not on every ACF field update
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    
    // Use a short delay to ensure all ACF fields are saved
    wp_schedule_single_event(time() + 2, 'ccs_upload_eventbrite_delayed', [$post_id]);
}
add_action('save_post', 'ccs_save_event_to_eventbrite', 20);

/**
 * Delayed upload to ensure ACF fields are saved
 */
function ccs_upload_eventbrite_delayed($post_id) {
    ccs_upload_event_to_eventbrite($post_id);
}
add_action('ccs_upload_eventbrite_delayed', 'ccs_upload_eventbrite_delayed');

/**
 * Also hook into ACF save for immediate updates
 */
function ccs_acf_save_event_to_eventbrite($post_id) {
    if (get_post_type($post_id) === 'course_event') {
        // Small delay to batch multiple field updates
        wp_clear_scheduled_hook('ccs_upload_eventbrite_delayed', [$post_id]);
        wp_schedule_single_event(time() + 1, 'ccs_upload_eventbrite_delayed', [$post_id]);
    }
}
add_action('acf/save_post', 'ccs_acf_save_event_to_eventbrite', 20);

/**
 * Track WordPress booking when form is submitted
 */
function ccs_track_wordpress_booking($post_id, $delegates = 1) {
    // Get current WordPress bookings count
    $current_bookings = intval(get_post_meta($post_id, '_wordpress_bookings', true));
    
    // Add new booking
    $new_total = $current_bookings + intval($delegates);
    
    // Save WordPress bookings count
    update_post_meta($post_id, '_wordpress_bookings', $new_total);
    
    // Recalculate spaces available
    ccs_recalculate_spaces_available($post_id);
}

/**
 * Recalculate spaces_available based on both WordPress and Eventbrite bookings
 */
function ccs_recalculate_spaces_available($post_id) {
    if (!function_exists('get_field') || !function_exists('update_field')) {
        return;
    }
    
    $total_spaces = intval(get_field('total_spaces', $post_id));
    if ($total_spaces <= 0) {
        return;
    }
    
    // Get bookings from both sources
    $wordpress_bookings = intval(get_post_meta($post_id, '_wordpress_bookings', true));
    $eventbrite_bookings = intval(get_post_meta($post_id, '_eventbrite_bookings', true));
    
    // Calculate available spaces
    $total_bookings = $wordpress_bookings + $eventbrite_bookings;
    $spaces_available = max(0, $total_spaces - $total_bookings);
    
    // Update only if different
    $current_available = intval(get_field('spaces_available', $post_id));
    if ($current_available != $spaces_available) {
        update_field('spaces_available', $spaces_available, $post_id);
    }
}

/**
 * Sync spaces from Eventbrite to WordPress for a single event
 */
/**
 * Sync spaces from Eventbrite to WordPress
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 */
function ccs_sync_spaces_from_eventbrite($post_id) {
    $eventbrite_id = get_post_meta($post_id, 'eventbrite_id', true);
    if (!$eventbrite_id) {
        return false;
    }
    
    $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
    if (empty($oauth_token)) {
        return false;
    }
    
    // Skip if currently syncing to Eventbrite
    if (get_post_meta($post_id, '_ccs_eventbrite_syncing', true)) {
        return false;
    }
    
    // Get ticket class info from Eventbrite
    $url = "https://www.eventbriteapi.com/v3/events/{$eventbrite_id}/ticket_classes/";
    
    $data = ccs_eventbrite_api_request($url, [
        'method' => 'GET',
        'timeout' => 15,
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
    ], 'Sync Spaces from Eventbrite');
    
    if (is_wp_error($data)) {
        return false;
    }
    
    if (isset($data['ticket_classes']) && !empty($data['ticket_classes'])) {
        $ticket_class = $data['ticket_classes'][0];
        $quantity_total = isset($ticket_class['quantity_total']) ? intval($ticket_class['quantity_total']) : 0;
        $quantity_sold = isset($ticket_class['quantity_sold']) ? intval($ticket_class['quantity_sold']) : 0;
        
        // Store Eventbrite bookings count (don't overwrite WordPress bookings)
        $current_eventbrite_bookings = intval(get_post_meta($post_id, '_eventbrite_bookings', true));
        
        if ($current_eventbrite_bookings != $quantity_sold) {
            update_post_meta($post_id, '_eventbrite_bookings', $quantity_sold);
        }
        
        // IMPORTANT: Do NOT overwrite WordPress capacity from Eventbrite.
        // WordPress is the source of truth for `total_spaces`. Eventbrite provides sales only.
        // (Optional) Keep a record of Eventbrite capacity for diagnostics.
        if ($quantity_total > 0) {
            update_post_meta($post_id, '_eventbrite_capacity', $quantity_total);
        }
        
        // Recalculate spaces_available based on BOTH sources
        ccs_recalculate_spaces_available($post_id);
        
        // Store last sync time
        update_post_meta($post_id, '_eventbrite_last_sync', current_time('mysql'));
        
        return true;
    }
    
    return false;
}

/**
 * Auto-sync spaces from Eventbrite to WordPress
 * Called automatically on schedule
 */
function ccs_auto_sync_spaces_from_eventbrite() {
    $auto_sync = get_option('ccs_eventbrite_auto_sync_spaces', 1);
    if (!$auto_sync) {
        return;
    }
    
    $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
    if (empty($oauth_token)) {
        return;
    }
    
    // Get all events with Eventbrite IDs
    $events = get_posts([
        'post_type' => 'course_event',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'eventbrite_id',
                'compare' => 'EXISTS',
            ],
        ],
    ]);
    
    $synced = 0;
    $errors = 0;
    
    foreach ($events as $event) {
        $result = ccs_sync_spaces_from_eventbrite($event->ID);
        if ($result) {
            $synced++;
        } else {
            $errors++;
        }
        
        // Small delay to avoid rate limits
        usleep(200000); // 0.2 seconds
    }
    
    // Log sync results
    update_option('ccs_eventbrite_last_sync', [
        'time' => current_time('mysql'),
        'synced' => $synced,
        'errors' => $errors,
    ]);
}

/**
 * Schedule automatic sync
 */
function ccs_schedule_eventbrite_auto_sync() {
    // Clear existing schedule
    wp_clear_scheduled_hook('ccs_eventbrite_auto_sync_spaces');
    
    // Get sync frequency (default: hourly)
    $frequency = get_option('ccs_eventbrite_sync_frequency', 'hourly');
    
    // Schedule if auto-sync is enabled
    $auto_sync = get_option('ccs_eventbrite_auto_sync_spaces', 1);
    if ($auto_sync) {
        if (!wp_next_scheduled('ccs_eventbrite_auto_sync_spaces')) {
            wp_schedule_event(time(), $frequency, 'ccs_eventbrite_auto_sync_spaces');
        }
    }
}
add_action('init', 'ccs_schedule_eventbrite_auto_sync');

/**
 * Hook the scheduled sync
 */
add_action('ccs_eventbrite_auto_sync_spaces', 'ccs_auto_sync_spaces_from_eventbrite');

/**
 * Re-schedule when settings change
 */
function ccs_reschedule_eventbrite_sync() {
    ccs_schedule_eventbrite_auto_sync();
}
add_action('update_option_ccs_eventbrite_auto_sync_spaces', 'ccs_reschedule_eventbrite_sync');
add_action('update_option_ccs_eventbrite_sync_frequency', 'ccs_reschedule_eventbrite_sync');

/**
 * Add Eventbrite import section to course_event editor
 */
function ccs_add_eventbrite_import_section() {
    global $post;
    
    if ($post && $post->post_type === 'course_event') {
        add_meta_box(
            'ccs_eventbrite_import',
            'Import from Eventbrite',
            'ccs_eventbrite_import_meta_box',
            'course_event',
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'ccs_add_eventbrite_import_section');

/**
 * Display admin notices for Eventbrite operations
 */
function ccs_eventbrite_admin_notices() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'course_event') {
        return;
    }
    
    $post_id = get_the_ID();
    if (!$post_id) {
        return;
    }
    
    $upload_status = get_transient('ccs_eventbrite_upload_' . $post_id);
    if ($upload_status) {
        if ($upload_status['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post($upload_status['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Eventbrite Upload Failed:</strong> ' . wp_kses_post($upload_status['message']) . '</p></div>';
        }
        delete_transient('ccs_eventbrite_upload_' . $post_id);
    }
}
add_action('admin_notices', 'ccs_eventbrite_admin_notices');

/**
 * Enhanced error messages with actionable tips
 */
function ccs_eventbrite_error_message($error_code, $error_message) {
    $help = '';
    
    switch ($error_code) {
        case 'missing_credentials':
            $help = 'Go to <a href="' . admin_url('admin.php?page=cta-api-keys') . '">API Keys settings</a> to configure Eventbrite credentials.';
            break;
        case 'INVALID_AUTH':
        case 'INVALID_AUTH_HEADER':
            $help = 'Your OAuth token may be expired. <a href="https://www.eventbrite.com/platform/api-keys/" target="_blank">Generate a new token</a> and update it in settings.';
            break;
        case 'NOT_FOUND':
            $help = 'The event may have been deleted on Eventbrite. Try unlinking and re-uploading.';
            break;
        case 'RATE_LIMIT_EXCEEDED':
        case 'HIT_RATE_LIMIT':
            $help = 'Too many requests. Please wait a few minutes and try again.';
            break;
        case 'missing_data':
            $help = 'Ensure the event has a linked course and event date set.';
            break;
    }
    
    return $error_message . ($help ? '<br><strong>Tip:</strong> ' . $help : '');
}

/**
 * Visual status indicator for Eventbrite integration
 */
function ccs_eventbrite_status_indicator($post_id) {
    $auto_upload = get_option('ccs_eventbrite_auto_upload', 1);
    $eventbrite_id = get_post_meta($post_id, 'eventbrite_id', true);
    
    if ($eventbrite_id) {
        $eventbrite_url = get_post_meta($post_id, 'eventbrite_url', true);
        echo '<div style="padding: 12px; background: #d1e7dd; border-left: 4px solid #00a32a; margin-bottom: 15px;">';
        echo '<strong>✓ Linked to Eventbrite</strong>';
        if ($eventbrite_url) {
            echo ' <a href="' . esc_url($eventbrite_url) . '" target="_blank" style="margin-left: 8px;">View on Eventbrite →</a>';
        }
        echo '</div>';
    } elseif ($auto_upload) {
        echo '<div style="padding: 12px; background: #e7f5ff; border-left: 4px solid #2271b1; margin-bottom: 15px;">';
        echo '<strong>📤 Will upload to Eventbrite when saved</strong><br>';
        echo '<small>Auto-upload is enabled. This event will be created on Eventbrite when you save.</small>';
        echo '</div>';
    } else {
        echo '<div style="padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 15px;">';
        echo '<strong>⚠️ Not on Eventbrite</strong><br>';
        echo '<small>Auto-upload is disabled. Use "Fetch My Eventbrite Events" below to import, or enable auto-upload in <a href="' . admin_url('admin.php?page=cta-api-keys') . '">settings</a>.</small>';
        echo '</div>';
    }
}

/**
 * Quick Start guide for new events
 */
function ccs_eventbrite_quick_start_guide($post_id) {
    $eventbrite_id = get_post_meta($post_id, 'eventbrite_id', true);
    
    if (!$eventbrite_id) {
        echo '<div style="padding: 15px; background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px; margin-bottom: 20px;">';
        echo '<h3 style="margin-top: 0;">📋 Quick Start Guide</h3>';
        echo '<ol style="margin-left: 20px;">';
        echo '<li><strong>Fill in event details</strong> (date, time, location, price, spaces)</li>';
        echo '<li><strong>Link a course</strong> (required for Eventbrite upload)</li>';
        echo '<li><strong>Save the event</strong> - it will automatically upload to Eventbrite</li>';
        echo '<li><strong>Or import existing</strong> - click "Fetch My Eventbrite Events" below</li>';
        echo '</ol>';
        echo '</div>';
    }
}

/**
 * Validation warnings for required fields
 */
function ccs_eventbrite_validation_warnings($post_id) {
    $warnings = [];
    
    if (!get_field('linked_course', $post_id)) {
        $warnings[] = 'No course linked - Eventbrite upload requires a linked course';
    }
    
    if (!get_field('event_date', $post_id)) {
        $warnings[] = 'No event date set';
    }
    
    $total_spaces = get_field('total_spaces', $post_id);
    if (empty($total_spaces) || $total_spaces <= 0) {
        $warnings[] = 'No capacity set - will default to 12 spaces';
    }
    
    if (!empty($warnings)) {
        echo '<div style="padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 15px;">';
        echo '<strong>⚠️ Before uploading to Eventbrite:</strong><ul style="margin: 5px 0 0 20px;">';
        foreach ($warnings as $warning) {
            echo '<li>' . esc_html($warning) . '</li>';
        }
        echo '</ul></div>';
    }
}

/**
 * Test Eventbrite API connection
 */
function ccs_eventbrite_test_connection() {
    check_ajax_referer('ccs_test_eventbrite', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
    $org_id = get_option('ccs_eventbrite_organization_id', '');
    
    if (empty($oauth_token) || empty($org_id)) {
        wp_send_json_error(['message' => 'Please configure OAuth Token and Organization ID first']);
    }
    
    // Test API connection
    $url = "https://www.eventbriteapi.com/v3/users/me/";
    $data = ccs_eventbrite_api_request($url, [
        'method' => 'GET',
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
    ], 'Test Connection');
    
    if (is_wp_error($data)) {
        $error_data = $data->get_error_data();
        $error_code = isset($error_data['error_code']) ? $error_data['error_code'] : '';
        $error_message = ccs_eventbrite_error_message($error_code, $data->get_error_message());
        wp_send_json_error(['message' => $error_message]);
    }
    
    $user_name = isset($data['name']) ? $data['name'] : 'Connected';
    wp_send_json_success([
        'message' => '✓ Connection successful!',
        'user' => $user_name
    ]);
}
add_action('wp_ajax_ccs_test_eventbrite_connection', 'ccs_eventbrite_test_connection');

/**
 * Eventbrite import meta box content
 */
function ccs_eventbrite_import_meta_box($post) {
    $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
    $org_id = get_option('ccs_eventbrite_organization_id', '');
    $eventbrite_id = get_post_meta($post->ID, 'eventbrite_id', true);
    
    if (empty($oauth_token) || empty($org_id)) {
        echo '<p style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">';
        echo 'Eventbrite API credentials not configured. <a href="' . admin_url('admin.php?page=cta-api-keys') . '">Configure settings</a>';
        echo '</p>';
        return;
    }
    
    ?>
    <div id="cta-eventbrite-import-wrapper" style="padding: 15px;">
        <?php
        // Quick Start Guide (only for new events)
        ccs_eventbrite_quick_start_guide($post->ID);
        
        // Validation Warnings
        ccs_eventbrite_validation_warnings($post->ID);
        
        // Status Indicator
        ccs_eventbrite_status_indicator($post->ID);
        ?>
        
        <div style="margin-bottom: 15px;">
            <button type="button" id="cta-fetch-eventbrite-events" class="button button-primary">
                <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                Fetch My Eventbrite Events
            </button>
            <span id="cta-fetch-status" style="margin-left: 10px;"></span>
        </div>
        
        <div id="cta-eventbrite-events-list" style="display: none; margin-top: 15px;">
            <h4 style="margin: 0 0 10px 0;">Select an Event to Import:</h4>
            <div id="cta-events-container" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                <!-- Events will be loaded here -->
            </div>
        </div>
        
        <div id="cta-import-preview" style="display: none; margin-top: 15px; padding: 15px; background: #f0f6fc; border: 1px solid #2271b1;">
            <h4 style="margin: 0 0 10px 0;">Preview - Fields to be filled:</h4>
            <div id="cta-preview-content"></div>
            <div style="margin-top: 15px;">
                <button type="button" id="cta-confirm-import" class="button button-primary">
                    Import & Fill Fields
                </button>
                <button type="button" id="cta-cancel-import" class="button">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var selectedEvent = null;
        
        // Fetch Eventbrite events
        $('#cta-fetch-eventbrite-events').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-fetch-status');
            var $container = $('#cta-eventbrite-events-list');
            var $eventsList = $('#cta-events-container');
            
            $button.prop('disabled', true);
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span> Loading...');
            $eventsList.html('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_fetch_eventbrite_events',
                    nonce: '<?php echo wp_create_nonce('ccs_fetch_events'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data.events) {
                        var events = response.data.events;
                        
                        if (events.length === 0) {
                            $eventsList.html('<p>No events found. Create events in Eventbrite first.</p>');
                        } else {
                            var html = '<div style="display: grid; gap: 10px;">';
                            events.forEach(function(event) {
                                var startDate = event.start ? new Date(event.start.local).toLocaleDateString('en-GB', {
                                    weekday: 'short',
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }) : 'Date TBD';
                                
                                html += '<div class="cta-eventbrite-event-card" style="padding: 12px; border: 2px solid #ddd; border-radius: 4px; background: #fff; cursor: pointer;" data-event-id="' + event.id + '" data-event-data=\'' + JSON.stringify(event) + '\'>';
                                html += '<div style="font-weight: 600; margin-bottom: 6px;">' + (event.name ? event.name.text : 'Untitled Event') + '</div>';
                                html += '<div style="font-size: 13px; color: #646970;">';
                                html += '<span>📅 ' + startDate + '</span>';
                                if (event.venue && event.venue.name) {
                                    html += '<span style="margin-left: 15px;">📍 ' + event.venue.name + '</span>';
                                }
                                if (event.capacity) {
                                    html += '<span style="margin-left: 15px;">👥 Capacity: ' + event.capacity + '</span>';
                                }
                                html += '</div>';
                                html += '</div>';
                            });
                            html += '</div>';
                            $eventsList.html(html);
                            
                            // Add click handlers
                            $('.cta-eventbrite-event-card').on('click', function() {
                                $('.cta-eventbrite-event-card').css('border-color', '#ddd');
                                $(this).css('border-color', '#2271b1');
                                selectedEvent = JSON.parse($(this).attr('data-event-data'));
                                showImportPreview(selectedEvent);
                            });
                        }
                        
                        $container.show();
                    } else {
                        $status.html('<span style="color: #d63638;">✗ Error: ' + (response.data.message || 'Failed to fetch events') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Failed to fetch events</span>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
        
        // Show import preview
        function showImportPreview(event) {
            var $preview = $('#cta-import-preview');
            var $content = $('#cta-preview-content');
            
            var html = '<ul style="list-style: none; padding: 0; margin: 0;">';
            
            // Parse event data
            var eventName = event.name ? event.name.text : '';
            var startDate = event.start ? event.start.local : '';
            var endDate = event.end ? event.end.local : '';
            var venue = event.venue ? event.venue.name : '';
            var capacity = event.capacity || '';
            var description = event.description ? event.description.text : '';
            
            html += '<li style="padding: 6px 0; border-bottom: 1px solid #ddd;"><strong>Event Name:</strong> ' + escapeHtml(eventName) + '</li>';
            html += '<li style="padding: 6px 0; border-bottom: 1px solid #ddd;"><strong>Start:</strong> ' + formatDateTime(startDate) + '</li>';
            html += '<li style="padding: 6px 0; border-bottom: 1px solid #ddd;"><strong>End:</strong> ' + formatDateTime(endDate) + '</li>';
            html += '<li style="padding: 6px 0; border-bottom: 1px solid #ddd;"><strong>Venue:</strong> ' + escapeHtml(venue) + '</li>';
            if (capacity) {
                html += '<li style="padding: 6px 0; border-bottom: 1px solid #ddd;"><strong>Capacity:</strong> ' + capacity + '</li>';
            }
            html += '<li style="padding: 6px 0; border-bottom: 1px solid #ddd;"><strong>Course:</strong> <span style="color: #d63638;">Will attempt to match by title</span></li>';
            
            html += '</ul>';
            
            $content.html(html);
            $preview.show();
        }
        
        // Confirm import
        $('#cta-confirm-import').on('click', function() {
            if (!selectedEvent) return;
            
            var $button = $(this);
            $button.prop('disabled', true).text('Importing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_import_eventbrite_event',
                    post_id: <?php echo $post->ID; ?>,
                    eventbrite_event: JSON.stringify(selectedEvent),
                    nonce: '<?php echo wp_create_nonce('ccs_import_event'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Event imported successfully! Fields have been filled.');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.data.message || 'Import failed'));
                        $button.prop('disabled', false).text('Import & Fill Fields');
                    }
                },
                error: function() {
                    alert('Import failed. Please try again.');
                    $button.prop('disabled', false).text('Import & Fill Fields');
                }
            });
        });
        
        // Cancel import
        $('#cta-cancel-import').on('click', function() {
            $('#cta-import-preview').hide();
            $('.cta-eventbrite-event-card').css('border-color', '#ddd');
            selectedEvent = null;
        });
        
        // Unlink Eventbrite
        $('#cta-unlink-eventbrite').on('click', function() {
            if (!confirm('Unlink this event from Eventbrite? This will not delete the Eventbrite event.')) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_unlink_eventbrite',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_unlink_event'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        });
        
        // Helper functions
        function formatDateTime(isoString) {
            if (!isoString) return 'Not set';
            var date = new Date(isoString);
            return date.toLocaleString('en-GB', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
    </script>
    <?php
}

/**
 * AJAX: Fetch Eventbrite events
 */
function ccs_ajax_fetch_eventbrite_events() {
    check_ajax_referer('ccs_fetch_events', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
    $org_id = get_option('ccs_eventbrite_organization_id', '');
    
    if (empty($oauth_token) || empty($org_id)) {
        wp_send_json_error(['message' => 'Eventbrite API not configured']);
    }
    
    $url = "https://www.eventbriteapi.com/v3/organizations/{$org_id}/events/?status=live,draft&order_by=start_asc&expand=venue";
    
    $data = ccs_eventbrite_api_request($url, [
        'method' => 'GET',
        'timeout' => 15,
        'headers' => [
            'Authorization' => 'Bearer ' . $oauth_token,
        ],
    ], 'Fetch Events');
    
    if (is_wp_error($data)) {
        wp_send_json_error(['message' => $data->get_error_message()]);
    }
    
    $events = isset($data['events']) ? $data['events'] : [];
    
    wp_send_json_success(['events' => $events]);
}
add_action('wp_ajax_ccs_fetch_eventbrite_events', 'ccs_ajax_fetch_eventbrite_events');

/**
 * AJAX: Import Eventbrite event and fill fields
 */
function ccs_ajax_import_eventbrite_event() {
    check_ajax_referer('ccs_import_event', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $event_data_json = isset($_POST['eventbrite_event']) ? $_POST['eventbrite_event'] : '';
    
    if (!$post_id || empty($event_data_json)) {
        wp_send_json_error(['message' => 'Invalid data']);
    }
    
    $event_data = json_decode(stripslashes($event_data_json), true);
    
    if (!$event_data) {
        wp_send_json_error(['message' => 'Invalid event data']);
    }
    
    // Parse Eventbrite event data
    $event_name = isset($event_data['name']['text']) ? $event_data['name']['text'] : '';
    $start_local = isset($event_data['start']['local']) ? $event_data['start']['local'] : '';
    $end_local = isset($event_data['end']['local']) ? $event_data['end']['local'] : '';
    $venue_name = isset($event_data['venue']['name']) ? $event_data['venue']['name'] : 'The Maidstone Studios';
    $capacity = isset($event_data['capacity']) ? intval($event_data['capacity']) : 0;
    $eventbrite_id = isset($event_data['id']) ? $event_data['id'] : '';
    $eventbrite_url = isset($event_data['url']) ? $event_data['url'] : '';
    
    // Parse date/time
    $start_datetime = new DateTime($start_local, new DateTimeZone('Europe/London'));
    $end_datetime = new DateTime($end_local, new DateTimeZone('Europe/London'));
    
    $event_date = $start_datetime->format('Y-m-d');
    $start_time = $start_datetime->format('H:i');
    $end_time = $end_datetime->format('H:i');
    
    // Try to find matching course
    $course_id = ccs_find_matching_course($event_name);
    
    // Get ticket class for price
    $price = 0;
    if ($eventbrite_id) {
        $price = ccs_get_eventbrite_ticket_price($eventbrite_id);
    }
    
    // Fill ACF fields
    if (function_exists('update_field')) {
        if ($course_id) {
            update_field('linked_course', $course_id, $post_id);
        }
        update_field('event_date', $event_date, $post_id);
        update_field('start_time', $start_time, $post_id);
        update_field('end_time', $end_time, $post_id);
        update_field('event_location', $venue_name, $post_id);
        
        if ($capacity > 0) {
            update_field('total_spaces', $capacity, $post_id);
            update_field('spaces_available', $capacity, $post_id);
        }
        
        if ($price > 0) {
            update_field('event_price', $price, $post_id);
        }
        
        update_field('event_active', 1, $post_id);
    }
    
    // Store Eventbrite ID and URL
    if ($eventbrite_id) {
        update_post_meta($post_id, 'eventbrite_id', $eventbrite_id);
        update_post_meta($post_id, 'eventbrite_url', $eventbrite_url);
    }
    
    // Update post title
    if ($course_id) {
        $course = get_post($course_id);
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $course->post_title,
        ]);
    }
    
    wp_send_json_success(['message' => 'Event imported successfully']);
}
add_action('wp_ajax_ccs_import_eventbrite_event', 'ccs_ajax_import_eventbrite_event');

/**
 * Find matching course by title
 */
function ccs_find_matching_course($event_name) {
    // Clean event name (remove duration, dates, etc.)
    $clean_name = preg_replace('/\s*-\s*(1 Day|2 Days|3 Hours|.*Duration.*)/i', '', $event_name);
    $clean_name = preg_replace('/\s*\(.*?\)\s*$/', '', $clean_name); // Remove date in parentheses
    $clean_name = trim($clean_name);
    
    // Search for course
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => 10,
        's' => $clean_name,
    ]);
    
    if (!empty($courses)) {
        // Try exact match first
        foreach ($courses as $course) {
            if (stripos($course->post_title, $clean_name) !== false || 
                stripos($clean_name, $course->post_title) !== false) {
                return $course->ID;
            }
        }
        // Fallback to first result
        return $courses[0]->ID;
    }
    
    return 0;
}

/**
 * Get ticket price from Eventbrite event
 * 
 * Follows Eventbrite API best practices via ccs_eventbrite_api_request helper.
 */
function ccs_get_eventbrite_ticket_price($event_id) {
    if (empty($event_id)) {
        return 0;
    }
    
    $url = "https://www.eventbriteapi.com/v3/events/{$event_id}/ticket_classes/";
    
    $data = ccs_eventbrite_api_request($url, [
        'method' => 'GET',
        'timeout' => 10,
    ], 'Get Ticket Price');
    
    if (is_wp_error($data)) {
        return 0;
    }
    
    if (isset($data['ticket_classes']) && !empty($data['ticket_classes'])) {
        $ticket = $data['ticket_classes'][0];
        if (isset($ticket['cost']) && isset($ticket['cost']['major_value'])) {
            return floatval($ticket['cost']['major_value']);
        }
    }
    
    return 0;
}

/**
 * AJAX: Unlink Eventbrite event
 */
function ccs_ajax_unlink_eventbrite() {
    check_ajax_referer('ccs_unlink_event', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if ($post_id) {
        delete_post_meta($post_id, 'eventbrite_id');
        delete_post_meta($post_id, 'eventbrite_url');
        wp_send_json_success();
    }
    
    wp_send_json_error(['message' => 'Invalid post ID']);
}
add_action('wp_ajax_ccs_unlink_eventbrite', 'ccs_ajax_unlink_eventbrite');

/**
 * AJAX handler for regenerate button
 */
function ccs_ajax_regenerate_eventbrite_description() {
    check_ajax_referer('ccs_regenerate_desc', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    // Generate new description
    $description = ccs_generate_eventbrite_description($post_id);
    
    if ($description) {
        // Update the field
        if (function_exists('update_field')) {
            update_field('eventbrite_description', $description, $post_id);
        }
        
        wp_send_json_success([
            'message' => 'Description regenerated successfully',
            'description' => $description
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to generate description. Check AI API settings.']);
    }
}
add_action('wp_ajax_ccs_regenerate_eventbrite_description', 'ccs_ajax_regenerate_eventbrite_description');

/**
 * AJAX handler for generating Eventbrite Summary (140 chars)
 */
function ccs_generate_eventbrite_summary_ajax() {
    check_ajax_referer('ccs_regenerate_desc', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'course_event') {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    
    // Get course and event data for context
    $course = get_field('linked_course', $post_id);
    if (!$course) {
        wp_send_json_error(['message' => 'No linked course found']);
    }
    
    $event_date = get_field('event_date', $post_id);
    $location = get_field('event_location', $post_id);
    $duration = get_field('course_duration', $course->ID);
    $accreditation = get_field('course_accreditation', $course->ID);
    
    $course_title = $course->post_title;
    $formatted_date = $event_date ? date('j M Y', strtotime($event_date)) : '';
    $location_text = $location ?: 'Maidstone, Kent';
    
    // Build context-aware prompt
    $system_prompt = "You are an expert copywriter for Continuity of Care Services, a care sector training provider in Kent, UK. Write concise, compelling summaries optimized for Eventbrite search and discovery. Use British English only.";
    
    $prompt = "Write a 140-character summary for an Eventbrite event listing.

Course: {$course_title}
" . ($duration ? "Duration: {$duration}\n" : "") . 
($formatted_date ? "Date: {$formatted_date}\n" : "") . 
"Location: {$location_text}
" . ($accreditation && strtolower(trim($accreditation)) !== 'none' ? "Accreditation: {$accreditation}\n" : "") . "

Requirements:
- Exactly 140 characters or less
- Include course title, location (Maidstone, Kent), and date
- Highlight key benefit or accreditation if relevant
- Target: UK care sector professionals searching for training
- Format: Compelling, keyword-rich, action-oriented
- Use British English only

Write only the summary text, nothing else. No quotes, no prefixes, just the 140-character summary.";
    
    $result = ccs_ai_try_providers($preferred_provider, function($provider, $api_key) use ($system_prompt, $prompt) {
        if ($provider === 'groq' && function_exists('ccs_call_groq_api')) return ccs_call_groq_api($api_key, $system_prompt, $prompt);
        if ($provider === 'anthropic' && function_exists('ccs_call_anthropic_api')) return ccs_call_anthropic_api($api_key, $system_prompt, $prompt);
        if ($provider === 'openai' && function_exists('ccs_call_openai_api')) return ccs_call_openai_api($api_key, $system_prompt, $prompt);
        return new WP_Error('api_error', 'Provider not available');
    });
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $result->get_error_message()]);
    }
    
    if (!$result) {
        wp_send_json_error(['message' => 'Failed to generate summary. Check AI API settings.']);
    }
    
    // Clean and trim to exactly 140 characters
    $summary = trim(strip_tags($result));
    $summary = preg_replace('/^["\']|["\']$/', '', $summary); // Remove quotes if AI added them
    if (strlen($summary) > 140) {
        $summary = substr($summary, 0, 137) . '...';
    }
    
    wp_send_json_success(['summary' => $summary]);
}
add_action('wp_ajax_ccs_generate_eventbrite_summary', 'ccs_generate_eventbrite_summary_ajax');

/**
 * AJAX handler for generating Eventbrite Custom Name (75 chars)
 */
function ccs_generate_eventbrite_custom_name_ajax() {
    check_ajax_referer('ccs_regenerate_desc', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'course_event') {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    
    // Get course and event data for context
    $course = get_field('linked_course', $post_id);
    if (!$course) {
        wp_send_json_error(['message' => 'No linked course found']);
    }
    
    $event_date = get_field('event_date', $post_id);
    $location = get_field('event_location', $post_id);
    $duration = get_field('course_duration', $course->ID);
    
    $course_title = $course->post_title;
    $formatted_date = $event_date ? date('j M Y', strtotime($event_date)) : '';
    $location_text = $location ?: 'Maidstone, Kent';
    
    // Build context-aware prompt
    $system_prompt = "You are an expert SEO copywriter for Continuity of Care Services, a care sector training provider in Kent, UK. Write SEO-optimized event names for Eventbrite that maximize search visibility. Use British English only.";
    
    $prompt = "Write an SEO-optimized event name (maximum 75 characters) for Eventbrite following 2026 best practices.

Course: {$course_title}
" . ($duration ? "Duration: {$duration}\n" : "") . 
($formatted_date ? "Date: {$formatted_date}\n" : "") . 
"Location: {$location_text}

Requirements (Eventbrite 2026 Best Practices):
- Exactly 75 characters or less (optimal for Eventbrite search)
- TITLE STRUCTURE: [Event Type] + [Unique Descriptor] + [Location if space allows]
- Include specific event type keywords (training, course, workshop, conference)
- Include location (Maidstone, Kent) if space allows for local SEO
- Target: UK care sector professionals searching for training
- Use title case (no all-caps, appears spammy)
- Use British English only

EXAMPLES OF GOOD FORMAT:
- 'Moving & Handling Training - Maidstone, Kent'
- 'CQC Safeguarding Workshop for Care Staff'
- 'Dementia Care Training Course - Professional Development'

AVOID:
- Vague descriptors ('Fun Night Out', 'Amazing Event')
- All-caps or excessive punctuation
- Generic claims ('Best', 'Can't Miss')
- Unnecessary dates (already captured in date field)

Write only the event name, nothing else. No quotes, no prefixes, just the event name (max 75 chars).";
    
    $result = ccs_ai_try_providers($preferred_provider, function($provider, $api_key) use ($system_prompt, $prompt) {
        if ($provider === 'groq' && function_exists('ccs_call_groq_api')) return ccs_call_groq_api($api_key, $system_prompt, $prompt);
        if ($provider === 'anthropic' && function_exists('ccs_call_anthropic_api')) return ccs_call_anthropic_api($api_key, $system_prompt, $prompt);
        if ($provider === 'openai' && function_exists('ccs_call_openai_api')) return ccs_call_openai_api($api_key, $system_prompt, $prompt);
        return new WP_Error('api_error', 'Provider not available');
    });
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $result->get_error_message()]);
    }
    
    if (!$result) {
        wp_send_json_error(['message' => 'Failed to generate name. Check AI API settings.']);
    }
    
    // Clean and trim to exactly 75 characters
    $name = trim(strip_tags($result));
    $name = preg_replace('/^["\']|["\']$/', '', $name); // Remove quotes if AI added them
    if (strlen($name) > 75) {
        $name = substr($name, 0, 72) . '...';
    }
    
    wp_send_json_success(['name' => $name]);
}
add_action('wp_ajax_ccs_generate_eventbrite_custom_name', 'ccs_generate_eventbrite_custom_name_ajax');

/**
 * AJAX handler for generating Eventbrite FAQs
 */
function ccs_generate_eventbrite_faqs_ajax() {
    check_ajax_referer('ccs_regenerate_desc', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'course_event') {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    
    // Get course and event data for context
    $course = get_field('linked_course', $post_id);
    if (!$course) {
        wp_send_json_error(['message' => 'No linked course found']);
    }
    
    $event_date = get_field('event_date', $post_id);
    $location = get_field('event_location', $post_id);
    $duration = get_field('course_duration', $course->ID);
    $accreditation = get_field('course_accreditation', $course->ID);
    $suitable_for = get_field('course_suitable_for', $course->ID);
    $prerequisites = get_field('course_prerequisites', $course->ID);
    $course_description = get_field('course_description', $course->ID);
    $price = get_field('event_price', $post_id) ?: get_field('course_price', $course->ID);
    
    $course_title = $course->post_title;
    $formatted_date = $event_date ? date('j F Y', strtotime($event_date)) : '';
    $location_text = $location ?: 'Maidstone, Kent';
    
    // Build context-aware prompt
    $system_prompt = "You are an expert copywriter for Continuity of Care Services, a care sector training provider in Kent, UK. Generate FAQ questions and answers optimized for Eventbrite listings to improve search visibility (+8% boost). Use British English only.";
    
    $prompt = "Generate 5-8 FAQ questions and answers for an Eventbrite event listing.

Course: {$course_title}
" . ($duration ? "Duration: {$duration}\n" : "") . 
($formatted_date ? "Date: {$formatted_date}\n" : "") . 
"Location: {$location_text}
" . ($price ? "Price: £" . number_format($price, 0) . "\n" : "") . 
($accreditation && strtolower(trim($accreditation)) !== 'none' ? "Accreditation: {$accreditation}\n" : "") . 
($suitable_for ? "Suitable for: {$suitable_for}\n" : "") . 
($prerequisites ? "Prerequisites: {$prerequisites}\n" : "") . "

Requirements:
- Generate 5-8 FAQs covering common attendee questions
- Topics should include: parking/accessibility, what to bring, refund/cancellation policy, prerequisites/requirements, certification details
- Each FAQ should be specific to this course and event
- Answers should be concise (2-4 sentences) and helpful
- Format as JSON array with 'question' and 'answer' keys
- Target: UK care sector professionals booking training
- Use British English only

Example format:
[
  {\"question\": \"Is parking available at the venue?\", \"answer\": \"Yes, free parking is available at The Maidstone Studios.\"},
  {\"question\": \"What should I bring to the training?\", \"answer\": \"Please bring a notepad and pen. All course materials will be provided.\"}
]

Return ONLY valid JSON, nothing else.";
    
    $result = ccs_ai_try_providers($preferred_provider, function($provider, $api_key) use ($system_prompt, $prompt) {
        if ($provider === 'groq' && function_exists('ccs_call_groq_api')) return ccs_call_groq_api($api_key, $system_prompt, $prompt);
        if ($provider === 'anthropic' && function_exists('ccs_call_anthropic_api')) return ccs_call_anthropic_api($api_key, $system_prompt, $prompt);
        if ($provider === 'openai' && function_exists('ccs_call_openai_api')) return ccs_call_openai_api($api_key, $system_prompt, $prompt);
        return new WP_Error('api_error', 'Provider not available');
    });
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $result->get_error_message()]);
    }
    
    if (!$result) {
        wp_send_json_error(['message' => 'Failed to generate FAQs. Check AI API settings.']);
    }
    
    // Clean and parse JSON
    $clean_result = trim(strip_tags($result));
    // Remove markdown code blocks if present
    $clean_result = preg_replace('/^```json\s*/i', '', $clean_result);
    $clean_result = preg_replace('/^```\s*/i', '', $clean_result);
    $clean_result = preg_replace('/```\s*$/i', '', $clean_result);
    $clean_result = trim($clean_result);
    
    $faqs = json_decode($clean_result, true);
    
    if (!is_array($faqs) || empty($faqs)) {
        wp_send_json_error(['message' => 'Failed to parse FAQ data. Please try again.']);
    }
    
    wp_send_json_success(['faqs' => $faqs]);
}
add_action('wp_ajax_ccs_generate_eventbrite_faqs', 'ccs_generate_eventbrite_faqs_ajax');

/**
 * AJAX handler for generating Eventbrite Tag Suggestions (AI-powered)
 */
function ccs_generate_eventbrite_tags_ai_ajax() {
    check_ajax_referer('ccs_regenerate_desc', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'course_event') {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    
    // Get course and event data for context
    $course = get_field('linked_course', $post_id);
    if (!$course) {
        wp_send_json_error(['message' => 'No linked course found']);
    }
    
    $event_date = get_field('event_date', $post_id);
    $location = get_field('event_location', $post_id);
    $duration = get_field('course_duration', $course->ID);
    $accreditation = get_field('course_accreditation', $course->ID);
    
    $course_title = $course->post_title;
    $formatted_date = $event_date ? date('j M Y', strtotime($event_date)) : '';
    $location_text = $location ?: 'Maidstone, Kent';
    
    // Extract location keywords
    $location_parts = [];
    if (stripos($location, 'Maidstone') !== false) {
        $location_parts[] = 'Maidstone';
    }
    if (stripos($location, 'Kent') !== false || stripos($location, 'Maidstone') !== false) {
        $location_parts[] = 'Kent';
    }
    $location_keywords = !empty($location_parts) ? implode(', ', $location_parts) : $location;
    
    // Get course category
    $category = '';
    $terms = get_the_terms($course->ID, 'course_category');
    if ($terms && !is_wp_error($terms)) {
        $category = $terms[0]->name;
    }
    
    // Build context-aware prompt
    $system_prompt = "You are an expert SEO copywriter for Continuity of Care Services, a care sector training provider in Kent, UK. Generate Eventbrite tag suggestions optimized for search visibility and discovery. Use British English only.";
    
    $prompt = "Generate 5-7 tag suggestions for an Eventbrite event listing.

Course: {$course_title}
" . ($duration ? "Duration: {$duration}\n" : "") . 
($formatted_date ? "Date: {$formatted_date}\n" : "") . 
"Location: {$location_text}
" . ($category ? "Category: {$category}\n" : "") . 
($accreditation && strtolower(trim($accreditation)) !== 'none' ? "Accreditation: {$accreditation}\n" : "") . "

Requirements:
- Generate 5-7 tags optimized for Eventbrite search
- Include course-specific keywords (extract key terms from course title)
- Include location-specific tags (e.g., 'Maidstone training', 'Kent care courses', 'Southeast UK')
- Include niche identifiers (e.g., 'care sector', 'CQC compliant', 'professional development')
- Include geographic qualifiers ('Kent-based', 'Maidstone events')
- Use terms that attendees actually search for (not generic category names)
- Format as comma-separated list
- Target: UK care sector professionals searching for training
- Use British English only

Example format:
Maidstone training, Kent care courses, CQC compliant, professional development, care sector, Southeast UK

Return ONLY the comma-separated tags, nothing else. No quotes, no prefixes, just the tags.";
    
    $result = ccs_ai_try_providers($preferred_provider, function($provider, $api_key) use ($system_prompt, $prompt) {
        if ($provider === 'groq' && function_exists('ccs_call_groq_api')) return ccs_call_groq_api($api_key, $system_prompt, $prompt);
        if ($provider === 'anthropic' && function_exists('ccs_call_anthropic_api')) return ccs_call_anthropic_api($api_key, $system_prompt, $prompt);
        if ($provider === 'openai' && function_exists('ccs_call_openai_api')) return ccs_call_openai_api($api_key, $system_prompt, $prompt);
        return new WP_Error('api_error', 'Provider not available');
    });
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $result->get_error_message()]);
    }
    
    if (!$result) {
        wp_send_json_error(['message' => 'Failed to generate tags. Check AI API settings.']);
    }
    
    // Clean and format tags
    $tags = trim(strip_tags($result));
    $tags = preg_replace('/^["\']|["\']$/', '', $tags); // Remove quotes if AI added them
    
    wp_send_json_success(['tags' => $tags]);
}
add_action('wp_ajax_ccs_generate_eventbrite_tags_ai', 'ccs_generate_eventbrite_tags_ai_ajax');

/**
 * Add JavaScript for regenerate button
 */
function ccs_eventbrite_description_admin_script($hook) {
    global $post;
    
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    
    if (!$post || $post->post_type !== 'course_event') {
        return;
    }
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Regenerate description button
        $('#cta-regenerate-eventbrite-desc').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-regenerate-status');
            var $descriptionField = $('#acf-field_eventbrite_description');
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_regenerate_eventbrite_description',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_regenerate_desc'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Update the WYSIWYG field
                        if ($descriptionField.length) {
                            // For WYSIWYG, we need to set the content and trigger editor update
                            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('acf-field_eventbrite_description')) {
                                tinyMCE.get('acf-field_eventbrite_description').setContent(response.data.description);
                            } else {
                                $descriptionField.val(response.data.description);
                            }
                            $descriptionField.trigger('change');
                        }
                        $status.html('<span style="color: #00a32a;">✓ Regenerated</span>');
                        // Update description counter after regeneration
                        if (typeof updateDescriptionCounter === 'function') {
                            updateDescriptionCounter();
                        }
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).html('🔄 Regenerate AI Description');
                }
            });
        });
        
        // Character counter for Eventbrite Summary (140 chars)
        var $summaryField = $('#acf-field_eventbrite_summary');
        if ($summaryField.length) {
            var $summaryCounter = $('<div class="description" style="margin-top: 6px; font-size: 12px;"></div>');
            $summaryField.after($summaryCounter);
            
            function updateSummaryCounter() {
                var len = $summaryField.val().length;
                var maxLen = 140;
                var color = len > maxLen ? '#d63638' : (len >= 130 ? '#dba617' : '#00a32a');
                var status = len > maxLen ? 'over limit' : (len >= 130 ? 'close to limit' : 'good');
                $summaryCounter.html('<span style="color: ' + color + '; font-weight: 600;">' + len + '/' + maxLen + ' characters</span> <span style="color: #646970;">(' + status + ')</span>');
            }
            
            $summaryField.on('input', updateSummaryCounter);
            updateSummaryCounter();
        }
        
        // Character counter for Eventbrite Custom Name (75 chars)
        var $nameField = $('#acf-field_eventbrite_custom_name');
        if ($nameField.length) {
            var $nameCounter = $('<div class="description" style="margin-top: 6px; font-size: 12px;"></div>');
            $nameField.after($nameCounter);
            
            function updateNameCounter() {
                var len = $nameField.val().length;
                var maxLen = 75;
                var color = len > maxLen ? '#d63638' : (len >= 68 ? '#dba617' : '#00a32a');
                var status = len > maxLen ? 'over limit' : (len >= 68 ? 'close to limit' : 'good');
                $nameCounter.html('<span style="color: ' + color + '; font-weight: 600;">' + len + '/' + maxLen + ' characters</span> <span style="color: #646970;">(' + status + ')</span>');
            }
            
            $nameField.on('input', updateNameCounter);
            updateNameCounter();
        }
        
        // Character counter for Eventbrite Description (WYSIWYG - 50,000 char limit per Eventbrite)
        function updateDescriptionCounter() {
            var $descriptionField = $('#acf-field_eventbrite_description');
            if (!$descriptionField.length) return;
            
            var content = '';
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('acf-field_eventbrite_description')) {
                content = tinyMCE.get('acf-field_eventbrite_description').getContent({ format: 'text' });
            } else {
                content = $descriptionField.val();
            }
            
            var len = content.length;
            var maxLen = 50000; // Eventbrite's limit
            var recommendedMax = 5000; // Recommended for readability
            var color = len > recommendedMax ? '#dba617' : '#00a32a';
            var status = len > recommendedMax ? 'long (consider shortening)' : 'good length';
            
            // Remove existing counter if present
            $('#eventbrite-description-counter').remove();
            
            var $counter = $('<div id="eventbrite-description-counter" class="description" style="margin-top: 6px; font-size: 12px;"></div>');
            $descriptionField.closest('.acf-field').find('.acf-input').append($counter);
            
            $counter.html('<span style="color: ' + color + '; font-weight: 600;">' + len.toLocaleString() + ' characters</span> <span style="color: #646970;">(' + status + ', max: ' + maxLen.toLocaleString() + ')</span>');
        }
        
        // Update description counter on WYSIWYG changes
        if (typeof tinyMCE !== 'undefined') {
            var editor = tinyMCE.get('acf-field_eventbrite_description');
            if (editor) {
                editor.on('keyup', updateDescriptionCounter);
                editor.on('change', updateDescriptionCounter);
            }
        }
        
        // Initial counter update
        setTimeout(updateDescriptionCounter, 500);
        
        // Update on ACF field changes
        $(document).on('acf/sync', updateDescriptionCounter);
        
        // Generate Eventbrite Summary with AI
        $('#cta-generate-eventbrite-summary').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-summary-status');
            var $summaryField = $('#acf-field_eventbrite_summary');
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_eventbrite_summary',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_regenerate_desc'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.summary) {
                        $summaryField.val(response.data.summary);
                        $summaryField.trigger('input'); // Trigger counter update
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
        
        // Generate Eventbrite Custom Name with AI
        $('#cta-generate-eventbrite-name').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-name-status');
            var $nameField = $('#acf-field_eventbrite_custom_name');
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_eventbrite_custom_name',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_regenerate_desc'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.name) {
                        $nameField.val(response.data.name);
                        $nameField.trigger('input'); // Trigger counter update
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
        
        // Generate Eventbrite FAQs with AI
        $('#cta-generate-eventbrite-faqs').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-faqs-status');
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_eventbrite_faqs',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_regenerate_desc'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.faqs && Array.isArray(response.data.faqs)) {
                        // Clear existing FAQs
                        var $repeater = $('[data-name="eventbrite_faqs"]');
                        if ($repeater.length) {
                            // Remove existing rows
                            $repeater.find('.acf-row').not('.acf-clone').remove();
                            
                            // Add new FAQ rows
                            response.data.faqs.forEach(function(faq) {
                                if (faq.question && faq.answer) {
                                    var $addButton = $repeater.find('.acf-button[data-event="add-row"]');
                                    if ($addButton.length) {
                                        $addButton.trigger('click');
                                        
                                        // Wait for row to be added, then fill it
                                        setTimeout(function() {
                                            var $newRow = $repeater.find('.acf-row').not('.acf-clone').last();
                                            $newRow.find('[data-name="question"] input').val(faq.question);
                                            $newRow.find('[data-name="answer"] textarea').val(faq.answer);
                                            // Trigger ACF update
                                            $newRow.find('input, textarea').trigger('change');
                                        }, 100);
                                    }
                                }
                            });
                            
                            // Wait for all rows to be added
                            setTimeout(function() {
                                $status.html('<span style="color: #00a32a;">✓ Generated ' + response.data.faqs.length + ' FAQs</span>');
                            }, 500);
                        } else {
                            $status.html('<span style="color: #d63638;">✗ FAQ field not found</span>');
                        }
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + (response.data && response.data.message ? response.data.message : 'Generation failed') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('✨ Generate FAQs with AI');
                }
            });
        });
        
        // Generate Eventbrite Tags with AI
        $('#cta-generate-eventbrite-tags').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-tags-status');
            var $tagsField = $('#acf-field_eventbrite_tag_suggestions');
            
            if (!$tagsField.length) {
                $status.html('<span style="color: #d63638;">✗ Field not found</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_eventbrite_tags_ai',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_regenerate_desc'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.tags) {
                        $tagsField.val(response.data.tags);
                        $tagsField.trigger('input');
                        $status.html('<span style="color: #00a32a;">✓ Generated</span>');
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + (response.data && response.data.message ? response.data.message : 'Generation failed') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('✨ Generate Tags with AI');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'ccs_eventbrite_description_admin_script');
