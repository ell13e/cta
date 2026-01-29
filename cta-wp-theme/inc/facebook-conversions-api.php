<?php
/**
 * Facebook Conversions API Integration
 * 
 * Server-side event tracking for Facebook/Meta Pixel
 * Sends conversion events directly from server to Facebook, improving reliability
 * and accuracy especially with iOS 14.5+ privacy changes and ad blockers.
 * 
 * Also includes Conversion Leads Integration for CRM:
 * Sends offline conversion events when leads progress through sales funnel.
 * 
 * References:
 * - Conversions API: https://developers.facebook.com/docs/marketing-api/conversions-api
 * - Conversion Leads Integration: https://developers.facebook.com/docs/marketing-api/conversions-api/conversion-leads-integration
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Register Conversions API settings
 */
function cta_facebook_conversions_api_register_settings() {
    register_setting('cta_api_keys_settings', 'cta_facebook_pixel_id', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_facebook_access_token', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_facebook_test_event_code', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_facebook_conversions_api_enabled', [
        'sanitize_callback' => 'absint',
        'default' => 1,
    ]);
    register_setting('cta_api_keys_settings', 'cta_facebook_crm_name', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'WordPress',
    ]);
}
add_action('admin_init', 'cta_facebook_conversions_api_register_settings');

/**
 * Send event to Facebook Conversions API
 * 
 * @param string $event_name Event name (e.g., 'PageView', 'Lead', 'Purchase')
 * @param array $event_data Event data (user_data, custom_data, etc.)
 * @return array|WP_Error Response from Facebook API
 */
function cta_send_facebook_event($event_name, $event_data = []) {
    $pixel_id = get_option('cta_facebook_pixel_id', '');
    $access_token = get_option('cta_facebook_access_token', '');
    $enabled = get_option('cta_facebook_conversions_api_enabled', 1);
    $test_event_code = get_option('cta_facebook_test_event_code', '');
    
    // Skip if not configured or disabled
    if (empty($pixel_id) || empty($access_token) || !$enabled) {
        return new WP_Error('not_configured', 'Facebook Conversions API not configured');
    }
    
    // Build event payload
    $event = [
        'event_name' => $event_name,
        'event_time' => time(),
        'event_source_url' => $event_data['event_source_url'] ?? (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']) ? 
            (is_ssl() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : ''),
        'action_source' => 'website',
    ];
    
    // Add user data (hashed for privacy)
    if (!empty($event_data['user_data'])) {
        $event['user_data'] = cta_hash_facebook_user_data($event_data['user_data']);
    } else {
        // Auto-detect user data from current request
        $event['user_data'] = cta_get_facebook_user_data();
    }
    
    // Add custom data
    if (!empty($event_data['custom_data'])) {
        $event['custom_data'] = $event_data['custom_data'];
    }
    
    // Add test event code if in test mode
    if (!empty($test_event_code)) {
        $event['test_event_code'] = $test_event_code;
    }
    
    // Build request payload
    $payload = [
        'data' => [$event],
    ];
    
    // Send to Facebook Conversions API (using latest API version v24.0)
    $url = 'https://graph.facebook.com/v24.0/' . $pixel_id . '/events';
    $args = [
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($payload),
        'timeout' => 5,
        'blocking' => false, // Non-blocking for performance
    ];
    
    // Add access token to URL
    $url = add_query_arg('access_token', $access_token, $url);
    
    $response = wp_remote_request($url, $args);
    
    // Log errors in development
    if (is_wp_error($response)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Facebook Conversions API Error: ' . $response->get_error_message());
        }
        return $response;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    return $data;
}

/**
 * Hash user data for Facebook Conversions API
 * Facebook requires certain user data fields to be hashed (SHA-256)
 * 
 * @param array $user_data Raw user data
 * @return array Hashed user data
 */
function cta_hash_facebook_user_data($user_data) {
    $hashed = [];
    $fields_to_hash = ['email', 'phone', 'first_name', 'last_name', 'city', 'state', 'zip', 'country'];
    
    foreach ($user_data as $key => $value) {
        if (empty($value)) {
            continue;
        }
        
        $key_lower = strtolower($key);
        
        // Hash sensitive fields
        if (in_array($key_lower, $fields_to_hash)) {
            $hashed[$key] = hash('sha256', strtolower(trim($value)));
        } else {
            $hashed[$key] = $value;
        }
    }
    
    // Add client IP and user agent (required for deduplication)
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $hashed['client_ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $hashed['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    return $hashed;
}

/**
 * Get user data from current request
 * Extracts available user data for Conversions API
 * 
 * @return array User data (hashed)
 */
function cta_get_facebook_user_data() {
    $user_data = [];
    
    // Get from current user if logged in
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if ($user->user_email) {
            $user_data['email'] = $user->user_email;
        }
        if ($user->first_name) {
            $user_data['first_name'] = $user->first_name;
        }
        if ($user->last_name) {
            $user_data['last_name'] = $user->last_name;
        }
    }
    
    // Get from form submissions (if available in session/cookies)
    // This would be set when user submits a form
    
    // Add IP and user agent
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $user_data['client_ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $user_data['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    return cta_hash_facebook_user_data($user_data);
}

/**
 * Track PageView event
 * Called on page load
 */
function cta_track_facebook_pageview() {
    $pixel_id = get_option('cta_facebook_pixel_id', '');
    if (empty($pixel_id)) {
        return;
    }
    
    cta_send_facebook_event('PageView', [
        'event_source_url' => (is_ssl() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    ]);
}
add_action('wp', 'cta_track_facebook_pageview');

/**
 * Track Lead event when form submission is saved
 */
function cta_track_facebook_lead_on_save($post_id, $post, $update) {
    // Only track new submissions, not updates
    if ($update || $post->post_type !== 'form_submission') {
        return;
    }
    
    // Get form data
    $email = get_post_meta($post_id, '_submission_email', true);
    $name = get_post_meta($post_id, '_submission_name', true);
    $phone = get_post_meta($post_id, '_submission_phone', true);
    $form_type = wp_get_post_terms($post_id, 'form_type', ['fields' => 'slugs']);
    $form_type = !empty($form_type) ? $form_type[0] : 'general';
    
    // Skip newsletter subscriptions (not leads)
    if (in_array($form_type, ['newsletter', 'newsletter-subscription'])) {
        return;
    }
    
    $user_data = [];
    if ($email) $user_data['email'] = $email;
    if ($phone) $user_data['phone'] = $phone;
    if ($name) {
        $name_parts = explode(' ', $name, 2);
        if (!empty($name_parts[0])) $user_data['first_name'] = $name_parts[0];
        if (!empty($name_parts[1])) $user_data['last_name'] = $name_parts[1];
    }
    
    cta_track_facebook_lead($post_id, $user_data);
}
add_action('save_post', 'cta_track_facebook_lead_on_save', 20, 3);

/**
 * Track Purchase event when course booking is completed
 * Hook into course booking controller
 */
function cta_track_facebook_purchase_on_booking($submission_id, $submission_data) {
    // Only track course bookings
    if (empty($submission_data['course_id']) || empty($submission_data['course_name'])) {
        return;
    }
    
    // Get course/event price
    $course_id = $submission_data['course_id'];
    $post_type = get_post_type($course_id);
    
    // Try to get price from course_event first, then fall back to course
    if ($post_type === 'course_event') {
        $price = get_field('event_price', $course_id);
        if (empty($price)) {
            $linked_course = get_field('linked_course', $course_id);
            if ($linked_course) {
                $price = get_field('course_price', $linked_course->ID);
            }
        }
    } else {
        $price = get_field('course_price', $course_id);
    }
    
    if (empty($price) || !is_numeric($price)) {
        $price = 0;
    }
    
    // Calculate total (price per delegate * number of delegates)
    $delegates = !empty($submission_data['delegates']) ? intval($submission_data['delegates']) : 1;
    $total = floatval($price) * $delegates;
    
    // Apply discount if present
    if (!empty($submission_data['discount_percent']) && is_numeric($submission_data['discount_percent'])) {
        $total = $total * (1 - ($submission_data['discount_percent'] / 100));
    }
    
    // Get user data
    $user_data = [];
    if (!empty($submission_data['email'])) $user_data['email'] = $submission_data['email'];
    if (!empty($submission_data['phone'])) $user_data['phone'] = $submission_data['phone'];
    if (!empty($submission_data['name'])) {
        $name_parts = explode(' ', $submission_data['name'], 2);
        if (!empty($name_parts[0])) $user_data['first_name'] = $name_parts[0];
        if (!empty($name_parts[1])) $user_data['last_name'] = $name_parts[1];
    }
    
    cta_track_facebook_purchase($submission_id, $total, 'GBP', $user_data);
}
add_action('cta_course_booking_saved', 'cta_track_facebook_purchase_on_booking', 10, 2);

/**
 * =========================================
 * CONVERSION LEADS INTEGRATION (CRM)
 * For offline conversion tracking from CRM
 * =========================================
 */

/**
 * Hash user data for Conversion Leads Integration (CRM)
 * Formats email and phone as arrays and hashes them for Meta's Conversion Leads Integration
 * 
 * @param array $user_data Raw user data
 * @return array Formatted and hashed user data for CRM events
 */
function cta_hash_facebook_crm_user_data($user_data) {
    $hashed = [];
    
    // Format email as array (em[]) - required format for Conversion Leads Integration
    if (!empty($user_data['email'])) {
        $hashed['em'] = [hash('sha256', strtolower(trim($user_data['email'])))];
    }
    
    // Format phone as array (ph[]) - required format for Conversion Leads Integration
    if (!empty($user_data['phone'])) {
        // Remove all non-numeric characters for phone hashing
        $phone_clean = preg_replace('/[^0-9]/', '', $user_data['phone']);
        if (!empty($phone_clean)) {
            $hashed['ph'] = [hash('sha256', $phone_clean)];
        }
    }
    
    // Hash other contact information if provided
    $other_fields = ['first_name', 'last_name', 'city', 'state', 'zip', 'country', 'gender', 'date_of_birth'];
    foreach ($other_fields as $field) {
        if (!empty($user_data[$field])) {
            $hashed[$field] = hash('sha256', strtolower(trim($user_data[$field])));
        }
    }
    
    return $hashed;
}

/**
 * Send offline conversion event for Conversion Leads Integration
 * Used when leads progress through sales funnel (e.g., Lead Qualified, Appointment Set, Sale Completed)
 * 
 * Follows Meta's Conversion Leads Integration specification:
 * - action_source: "system_generated"
 * - custom_data.event_source: "crm"
 * - custom_data.lead_event_source: CRM name (e.g., "WordPress")
 * - user_data.lead_id: Meta Lead ID (15-17 digits)
 * - user_data.em: Array of hashed emails
 * - user_data.ph: Array of hashed phone numbers
 * 
 * @param string $lead_id Meta Lead ID (15-17 digits from Lead Ads)
 * @param string $event_name Conversion event name (e.g., 'Lead', 'Appointment Set', 'Sale Completed')
 * @param array $event_data Additional event data (value, currency, user_data, etc.)
 * @param string $crm_name CRM name (default: 'WordPress')
 * @return array|WP_Error Response from Facebook API
 */
function cta_send_facebook_offline_conversion($lead_id, $event_name, $event_data = [], $crm_name = 'WordPress') {
    $pixel_id = get_option('cta_facebook_pixel_id', '');
    $access_token = get_option('cta_facebook_access_token', '');
    $enabled = get_option('cta_facebook_conversions_api_enabled', 1);
    $test_event_code = get_option('cta_facebook_test_event_code', '');
    
    // Skip if not configured or disabled
    if (empty($pixel_id) || empty($access_token) || !$enabled) {
        return new WP_Error('not_configured', 'Facebook Conversions API not configured');
    }
    
    // Validate Lead ID (15-17 digits)
    if (empty($lead_id) || !preg_match('/^\d{15,17}$/', $lead_id)) {
        return new WP_Error('invalid_lead_id', 'Meta Lead ID must be 15-17 digits');
    }
    
    // Build event payload for Conversion Leads Integration
    $event = [
        'event_name' => $event_name,
        'event_time' => isset($event_data['event_time']) ? intval($event_data['event_time']) : time(),
        'action_source' => 'system_generated', // Required for Conversion Leads Integration
    ];
    
    // Add custom_data with CRM information (required for Conversion Leads Integration)
    $event['custom_data'] = [
        'event_source' => 'crm', // Required: must be "crm"
        'lead_event_source' => $crm_name, // Required: CRM name (e.g., "WordPress", "HubSpot", "Salesforce")
    ];
    
    // Merge any additional custom data (value, currency, etc.)
    if (!empty($event_data['custom_data'])) {
        $event['custom_data'] = array_merge($event['custom_data'], $event_data['custom_data']);
    }
    
    // Build user_data with Lead ID (required for Conversion Leads Integration)
    $event['user_data'] = [
        'lead_id' => $lead_id, // Required: 15-17 digit Meta Lead ID
    ];
    
    // Add hashed user data (email, phone) in required format for CRM events
    if (!empty($event_data['user_data'])) {
        $crm_user_data = cta_hash_facebook_crm_user_data($event_data['user_data']);
        $event['user_data'] = array_merge($event['user_data'], $crm_user_data);
    }
    
    // Add test event code if in test mode
    if (!empty($test_event_code)) {
        $event['test_event_code'] = $test_event_code;
    }
    
    // Build request payload
    $payload = [
        'data' => [$event],
    ];
    
    // Send to Facebook Conversions API (using latest API version v24.0)
    $url = 'https://graph.facebook.com/v24.0/' . $pixel_id . '/events';
    $args = [
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($payload),
        'timeout' => 5,
        'blocking' => false, // Non-blocking for performance
    ];
    
    // Add access token to URL
    $url = add_query_arg('access_token', $access_token, $url);
    
    $response = wp_remote_request($url, $args);
    
    // Log errors in development
    if (is_wp_error($response)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Facebook Conversion Leads Integration Error: ' . $response->get_error_message());
        }
        return $response;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    return $data;
}

/**
 * Track offline conversion when form submission status changes
 * Maps followup statuses to conversion events
 * 
 * @param int $post_id Form submission ID
 * @param string $old_status Old followup status
 * @param string $new_status New followup status
 */
function cta_track_facebook_offline_conversion_on_status_change($post_id, $old_status, $new_status) {
    // Get Meta Lead ID from submission
    $lead_id = get_post_meta($post_id, '_submission_meta_lead_id', true);
    if (empty($lead_id)) {
        return; // No Lead ID, skip
    }
    
    // Map status to conversion event
    $status_to_event = [
        'in-progress' => 'Lead', // Lead is qualified/interested
        'booked' => 'Appointment Set', // Appointment/booking made
        'paid' => 'Sale Completed', // Payment received
        'completed' => 'Sale Completed', // Course attended (completed sale)
    ];
    
    // Check if new status maps to a conversion event
    if (!isset($status_to_event[$new_status])) {
        return; // Status doesn't map to conversion event
    }
    
    $event_name = $status_to_event[$new_status];
    
    // Get submission data for value calculation
    $course_id = get_post_meta($post_id, '_submission_course_id', true);
    $custom_data = [];
    
    if ($course_id) {
        $price = get_field('course_price', $course_id);
        if ($price && is_numeric($price)) {
            $custom_data['value'] = floatval($price);
            $custom_data['currency'] = 'GBP';
        }
    }
    
    // Get CRM name from settings
    $crm_name = get_option('cta_facebook_crm_name', 'WordPress');
    
    // Send offline conversion event
    cta_send_facebook_offline_conversion($lead_id, $event_name, [
        'custom_data' => $custom_data,
        'event_id' => 'status_' . $post_id . '_' . $new_status . '_' . time(), // Unique event ID
    ], $crm_name);
}
add_action('cta_form_submission_status_changed', 'cta_track_facebook_offline_conversion_on_status_change', 10, 3);

/**
 * Manually send offline conversion event
 * Called from admin interface
 * 
 * @param int $submission_id Form submission ID
 * @param string $event_name Conversion event name
 * @param array $event_data Additional event data
 * @return array|WP_Error Response from Facebook API
 */
function cta_send_manual_offline_conversion($submission_id, $event_name, $event_data = []) {
    $lead_id = get_post_meta($submission_id, '_submission_meta_lead_id', true);
    
    if (empty($lead_id)) {
        return new WP_Error('no_lead_id', 'Meta Lead ID not found for this submission');
    }
    
    // Get user data from submission
    $user_data = [];
    $email = get_post_meta($submission_id, '_submission_email', true);
    $phone = get_post_meta($submission_id, '_submission_phone', true);
    $name = get_post_meta($submission_id, '_submission_name', true);
    
    if ($email) $user_data['email'] = $email;
    if ($phone) $user_data['phone'] = $phone;
    if ($name) {
        $name_parts = explode(' ', $name, 2);
        if (!empty($name_parts[0])) $user_data['first_name'] = $name_parts[0];
        if (!empty($name_parts[1])) $user_data['last_name'] = $name_parts[1];
    }
    
    // Get course price if available
    $course_id = get_post_meta($submission_id, '_submission_course_id', true);
    if ($course_id && empty($event_data['custom_data']['value'])) {
        $price = get_field('course_price', $course_id);
        if ($price && is_numeric($price)) {
            if (!isset($event_data['custom_data'])) {
                $event_data['custom_data'] = [];
            }
            $event_data['custom_data']['value'] = floatval($price);
            $event_data['custom_data']['currency'] = 'GBP';
        }
    }
    
    $event_data['user_data'] = $user_data;
    $event_data['event_id'] = 'manual_' . $submission_id . '_' . $event_name . '_' . time();
    
    // Get CRM name from settings
    $crm_name = get_option('cta_facebook_crm_name', 'WordPress');
    
    return cta_send_facebook_offline_conversion($lead_id, $event_name, $event_data, $crm_name);
}

/**
 * Track Lead event (form submission)
 * 
 * @param int $form_id Form submission ID
 * @param array $form_data Form data
 */
function cta_track_facebook_lead($form_id, $form_data) {
    $user_data = [];
    
    // Extract user data from form
    if (!empty($form_data['email'])) {
        $user_data['email'] = $form_data['email'];
    }
    if (!empty($form_data['phone'])) {
        $user_data['phone'] = $form_data['phone'];
    }
    if (!empty($form_data['first_name'])) {
        $user_data['first_name'] = $form_data['first_name'];
    }
    if (!empty($form_data['last_name'])) {
        $user_data['last_name'] = $form_data['last_name'];
    }
    
    cta_send_facebook_event('Lead', [
        'user_data' => $user_data,
        'custom_data' => [
            'content_name' => 'Contact Form Submission',
            'content_category' => 'Lead Generation',
        ],
    ]);
}

/**
 * Track Purchase event (course booking)
 * 
 * @param int $booking_id Booking/order ID
 * @param float $value Purchase value
 * @param string $currency Currency code (default: GBP)
 * @param array $user_data User data
 */
function cta_track_facebook_purchase($booking_id, $value, $currency = 'GBP', $user_data = []) {
    cta_send_facebook_event('Purchase', [
        'user_data' => $user_data,
        'custom_data' => [
            'value' => $value,
            'currency' => $currency,
            'content_ids' => [(string)$booking_id],
            'content_type' => 'course_booking',
        ],
    ]);
}

/**
 * Track ViewContent event (course page view)
 * 
 * @param int $course_id Course ID
 * @param string $course_title Course title
 */
function cta_track_facebook_view_content($course_id, $course_title) {
    cta_send_facebook_event('ViewContent', [
        'custom_data' => [
            'content_ids' => [(string)$course_id],
            'content_name' => $course_title,
            'content_type' => 'course',
            'content_category' => 'Training Course',
        ],
    ]);
}

/**
 * Track InitiateCheckout event (booking form started)
 * 
 * @param int $course_id Course ID
 * @param float $value Course price
 */
function cta_track_facebook_initiate_checkout($course_id, $value) {
    cta_send_facebook_event('InitiateCheckout', [
        'custom_data' => [
            'content_ids' => [(string)$course_id],
            'value' => $value,
            'currency' => 'GBP',
            'content_type' => 'course',
        ],
    ]);
}

/**
 * Add Conversions API settings to API Keys page
 */
function cta_facebook_conversions_api_settings_fields() {
    $pixel_id = get_option('cta_facebook_pixel_id', '');
    $access_token = get_option('cta_facebook_access_token', '');
    $test_event_code = get_option('cta_facebook_test_event_code', '');
    $enabled = get_option('cta_facebook_conversions_api_enabled', 1);
    $crm_name = get_option('cta_facebook_crm_name', 'WordPress');
    ?>
    <div class="cta-api-keys-section">
        <h2>
            <span class="dashicons dashicons-facebook-alt" style="color: #1877F2;"></span>
            Facebook Pixel & Conversions API
        </h2>
        <p class="description">
            Track conversions server-side for better accuracy and reliability. 
            <a href="https://developers.facebook.com/docs/marketing-api/conversions-api" target="_blank">Learn more about Conversions API</a>
        </p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="cta_facebook_pixel_id">Pixel ID</label>
                </th>
                <td>
                    <input type="text" 
                           id="cta_facebook_pixel_id" 
                           name="cta_facebook_pixel_id" 
                           value="<?php echo esc_attr($pixel_id); ?>" 
                           class="regular-text"
                           placeholder="123456789012345">
                    <p class="description">
                        Your Facebook Pixel ID (found in Events Manager → Data Sources → Your Pixel)
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cta_facebook_access_token">Access Token</label>
                </th>
                <td>
                    <input type="password" 
                           id="cta_facebook_access_token" 
                           name="cta_facebook_access_token" 
                           value="<?php echo esc_attr($access_token); ?>" 
                           class="regular-text"
                           placeholder="Your Conversions API access token">
                    <p class="description">
                        <a href="https://developers.facebook.com/docs/marketing-api/conversions-api/get-started" target="_blank">Get your Access Token</a> from Events Manager → Data Sources → Settings → Conversions API
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cta_facebook_test_event_code">Test Event Code</label>
                </th>
                <td>
                    <input type="text" 
                           id="cta_facebook_test_event_code" 
                           name="cta_facebook_test_event_code" 
                           value="<?php echo esc_attr($test_event_code); ?>" 
                           class="regular-text"
                           placeholder="TEST12345 (optional)">
                    <p class="description">
                        Optional: Test event code from Events Manager → Test Events. Leave blank for production.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cta_facebook_conversions_api_enabled">Enable Conversions API</label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               id="cta_facebook_conversions_api_enabled" 
                               name="cta_facebook_conversions_api_enabled" 
                               value="1" 
                               <?php checked($enabled, 1); ?>>
                        Send server-side events to Facebook Conversions API
                    </label>
                    <p class="description">
                        When enabled, conversion events are sent directly from your server to Facebook, improving reliability and accuracy.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cta_facebook_crm_name">CRM Name (Conversion Leads)</label>
                </th>
                <td>
                    <input type="text" 
                           id="cta_facebook_crm_name" 
                           name="cta_facebook_crm_name" 
                           value="<?php echo esc_attr($crm_name); ?>" 
                           class="regular-text"
                           placeholder="WordPress">
                    <p class="description">
                        Name of your CRM system for Conversion Leads Integration (e.g., "WordPress", "HubSpot", "Salesforce"). 
                        This appears in Events Manager as the <code>lead_event_source</code>.
                    </p>
                </td>
            </tr>
        </table>
        
        <?php if (!empty($pixel_id) && !empty($access_token)) : ?>
            <div class="notice notice-info inline">
                <p>
                    <strong>Status:</strong> Conversions API is configured. 
                    Events are being sent to Pixel ID: <code><?php echo esc_html($pixel_id); ?></code>
                </p>
            </div>
        <?php else : ?>
            <div class="notice notice-warning inline">
                <p>
                    <strong>Setup Required:</strong> Enter your Pixel ID and Access Token to enable Conversions API tracking.
                </p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
// Note: Settings fields are integrated directly into api-keys-settings.php
