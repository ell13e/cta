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
        'default' => 'facebook-lead',
    ]);
}
add_action('admin_init', 'cta_facebook_lead_ads_register_settings');

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
 * GET: Webhook verification (Facebook sends challenge)
 * POST: Lead data from Facebook
 * 
 * @param WP_REST_Request $request REST request object
 * @return WP_REST_Response|WP_Error
 */
function cta_handle_facebook_lead_ads_webhook($request) {
    $enabled = get_option('cta_facebook_lead_ads_webhook_enabled', 0);
    
    if ($request->get_method() === 'GET') {
        return cta_verify_facebook_webhook($request);
    }
    
    if (!$enabled) {
        return new WP_Error('webhook_disabled', 'Facebook Lead Ads webhook is disabled', ['status' => 503]);
    }
    
    return cta_process_facebook_lead($request);
}

/**
 * Verify Facebook webhook (GET request)
 * Facebook sends a challenge to verify the webhook endpoint
 * 
 * @param WP_REST_Request $request REST request object
 * @return WP_REST_Response
 */
function cta_verify_facebook_webhook($request) {
    $mode = $request->get_param('hub.mode');
    $token = $request->get_param('hub.verify_token');
    $challenge = $request->get_param('hub.challenge');
    $expected_token = get_option('cta_facebook_lead_ads_verify_token', '');
    
    if ($mode === 'subscribe' && $token === $expected_token) {
        return new WP_REST_Response($challenge, 200);
    }
    
    return new WP_Error('verification_failed', 'Webhook verification failed', ['status' => 403]);
}

/**
 * Process Facebook lead data (POST request)
 * 
 * @param WP_REST_Request $request REST request object
 * @return WP_REST_Response|WP_Error
 */
function cta_process_facebook_lead($request) {
    $body = $request->get_json_params();
    
    if (empty($body) || !isset($body['entry'])) {
        return new WP_Error('invalid_payload', 'Invalid webhook payload', ['status' => 400]);
    }
    
    $processed = 0;
    $errors = [];
    
    foreach ($body['entry'] as $entry) {
        if (empty($entry['changes'])) {
            continue;
        }
        
        foreach ($entry['changes'] as $change) {
            if ($change['field'] !== 'leadgen') {
                continue;
            }
            
            $lead_data = $change['value'] ?? [];
            
            if (empty($lead_data['leadgen_id'])) {
                $errors[] = 'Missing leadgen_id in webhook data';
                continue;
            }
            
            $lead_details = cta_fetch_facebook_lead_details($lead_data['leadgen_id']);
            
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
            
            $processed++;
        }
    }
    
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
                if (stripos($field_name, 'how many') !== false || 
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
    
    if (!empty($parsed_data['course_name'])) {
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
    
    if (!empty($parsed_data['date'])) {
        $submission_data['event_date'] = $parsed_data['date'];
    }
    
    $form_type = get_option('cta_facebook_lead_ads_form_type', 'facebook-lead');
    
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
    
    $form_type = get_option('cta_facebook_lead_ads_form_type', 'facebook-lead');
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
                           placeholder="facebook-lead">
                    <p class="description">
                        Form type slug for imported leads (used for categorization in Form Submissions).
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
