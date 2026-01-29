<?php
/**
 * API Keys Settings Page
 * 
 * Centralized settings page for all API keys (reCAPTCHA, Google Analytics, etc.)
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Register API Keys settings
 */
function cta_api_keys_register_settings() {
    register_setting('cta_api_keys_settings', 'cta_recaptcha_site_key', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_recaptcha_secret_key', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_google_analytics_id', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_facebook_pixel_id', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_google_maps_api_key', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    
    // Eventbrite settings
    register_setting('cta_api_keys_settings', 'cta_eventbrite_oauth_token', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_eventbrite_organization_id', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_eventbrite_venue_id', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);
    register_setting('cta_api_keys_settings', 'cta_eventbrite_auto_upload', [
        'sanitize_callback' => 'absint',
        'default' => 1,
    ]);
    register_setting('cta_api_keys_settings', 'cta_eventbrite_auto_publish', [
        'sanitize_callback' => 'absint',
        'default' => 1,
    ]);
    register_setting('cta_api_keys_settings', 'cta_eventbrite_auto_sync_spaces', [
        'sanitize_callback' => 'absint',
        'default' => 1,
    ]);
    register_setting('cta_api_keys_settings', 'cta_eventbrite_sync_frequency', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'hourly',
    ]);
    register_setting('cta_api_keys_settings', 'cta_eventbrite_use_ai_description', [
        'sanitize_callback' => 'absint',
        'default' => 1,
    ]);
}
add_action('admin_init', 'cta_api_keys_register_settings');

/**
 * Add API Keys settings page under Settings menu
 */
function cta_api_keys_settings_menu() {
    add_options_page(
        'API Keys',
        'API Keys',
        'manage_options',
        'cta-api-keys',
        'cta_api_keys_settings_page'
    );
}
add_action('admin_menu', 'cta_api_keys_settings_menu');

/**
 * API Keys Settings page content
 */
function cta_api_keys_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['cta_api_keys_submit']) && check_admin_referer('cta_api_keys_settings')) {
        update_option('cta_recaptcha_site_key', sanitize_text_field($_POST['cta_recaptcha_site_key'] ?? ''));
        update_option('cta_recaptcha_secret_key', sanitize_text_field($_POST['cta_recaptcha_secret_key'] ?? ''));
        update_option('cta_google_analytics_id', sanitize_text_field($_POST['cta_google_analytics_id'] ?? ''));
        update_option('cta_facebook_pixel_id', sanitize_text_field($_POST['cta_facebook_pixel_id'] ?? ''));
        update_option('cta_facebook_access_token', sanitize_text_field($_POST['cta_facebook_access_token'] ?? ''));
        update_option('cta_facebook_test_event_code', sanitize_text_field($_POST['cta_facebook_test_event_code'] ?? ''));
        update_option('cta_facebook_conversions_api_enabled', isset($_POST['cta_facebook_conversions_api_enabled']) ? 1 : 0);
        update_option('cta_facebook_crm_name', sanitize_text_field($_POST['cta_facebook_crm_name'] ?? 'WordPress'));
        update_option('cta_facebook_lead_ads_webhook_enabled', isset($_POST['cta_facebook_lead_ads_webhook_enabled']) ? 1 : 0);
        
        if (isset($_POST['cta_facebook_lead_ads_generate_token']) && $_POST['cta_facebook_lead_ads_generate_token'] === '1') {
            update_option('cta_facebook_lead_ads_verify_token', wp_generate_password(32, false));
        } else {
            $existing_token = get_option('cta_facebook_lead_ads_verify_token', '');
            if (empty($existing_token)) {
                update_option('cta_facebook_lead_ads_verify_token', wp_generate_password(32, false));
            }
        }
        
        update_option('cta_facebook_lead_ads_form_type', sanitize_text_field($_POST['cta_facebook_lead_ads_form_type'] ?? 'facebook-lead'));
        update_option('cta_google_maps_api_key', sanitize_text_field($_POST['cta_google_maps_api_key'] ?? ''));
        
        update_option('cta_eventbrite_oauth_token', sanitize_text_field($_POST['cta_eventbrite_oauth_token'] ?? ''));
        update_option('cta_eventbrite_organization_id', sanitize_text_field($_POST['cta_eventbrite_organization_id'] ?? ''));
        update_option('cta_eventbrite_venue_id', sanitize_text_field($_POST['cta_eventbrite_venue_id'] ?? ''));
        update_option('cta_eventbrite_auto_upload', isset($_POST['cta_eventbrite_auto_upload']) ? 1 : 0);
        update_option('cta_eventbrite_auto_publish', isset($_POST['cta_eventbrite_auto_publish']) ? 1 : 0);
        update_option('cta_eventbrite_auto_sync_spaces', isset($_POST['cta_eventbrite_auto_sync_spaces']) ? 1 : 0);
        update_option('cta_eventbrite_sync_frequency', sanitize_text_field($_POST['cta_eventbrite_sync_frequency'] ?? 'hourly'));
        update_option('cta_eventbrite_use_ai_description', isset($_POST['cta_eventbrite_use_ai_description']) ? 1 : 0);
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    
    $recaptcha_site_key = get_option('cta_recaptcha_site_key', '');
    $recaptcha_secret_key = get_option('cta_recaptcha_secret_key', '');
    $ga_id = get_option('cta_google_analytics_id', '');
    $fb_pixel = get_option('cta_facebook_pixel_id', '');
    $fb_access_token = get_option('cta_facebook_access_token', '');
    $fb_test_code = get_option('cta_facebook_test_event_code', '');
    $fb_enabled = get_option('cta_facebook_conversions_api_enabled', 1);
    $maps_key = get_option('cta_google_maps_api_key', '');
    
    $eventbrite_token = get_option('cta_eventbrite_oauth_token', '');
    $eventbrite_org_id = get_option('cta_eventbrite_organization_id', '');
    $eventbrite_venue_id = get_option('cta_eventbrite_venue_id', '');
    $eventbrite_auto_upload = get_option('cta_eventbrite_auto_upload', 1);
    $eventbrite_auto_publish = get_option('cta_eventbrite_auto_publish', 1);
    $eventbrite_auto_sync = get_option('cta_eventbrite_auto_sync_spaces', 1);
    $eventbrite_sync_freq = get_option('cta_eventbrite_sync_frequency', 'hourly');
    $eventbrite_use_ai = get_option('cta_eventbrite_use_ai_description', 1);
    $last_sync = get_option('cta_eventbrite_last_sync');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-admin-network" style="font-size: 32px; vertical-align: middle; margin-right: 8px; color: #2271b1;"></span>
            API Keys & External Services
        </h1>
        <hr class="wp-header-end">
        
        <style>
            .cta-api-keys-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 25px;
                margin-bottom: 25px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .cta-api-keys-section h2 {
                margin-top: 0;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #f0f0f1;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .cta-api-keys-section h2 .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                color: #2271b1;
            }
            .cta-api-key-field {
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 1px solid #f0f0f1;
            }
            .cta-api-key-field:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            .cta-api-key-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                font-size: 14px;
            }
            .cta-api-key-field input[type="text"],
            .cta-api-key-field input[type="password"] {
                width: 100%;
                max-width: 600px;
                padding: 10px 12px;
                font-size: 14px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                transition: border-color 0.2s;
            }
            .cta-api-key-field input[type="text"]:focus,
            .cta-api-key-field input[type="password"]:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: none;
            }
            .cta-api-key-field .description {
                margin-top: 8px;
                font-size: 13px;
                color: #646970;
                line-height: 1.6;
            }
            .cta-api-key-field .description a {
                color: #2271b1;
                text-decoration: none;
            }
            .cta-api-key-field .description a:hover {
                text-decoration: underline;
            }
            .cta-api-key-status {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                margin-left: 10px;
            }
            .cta-api-key-status.configured {
                background: #d1e7dd;
                color: #0f5132;
            }
            .cta-api-key-status.not-configured {
                background: #f8d7da;
                color: #842029;
            }
            .cta-help-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 25px;
                border-radius: 8px;
                margin-top: 30px;
            }
            .cta-help-card h2 {
                color: white;
                margin-top: 0;
                border-bottom: 1px solid rgba(255,255,255,0.3);
                padding-bottom: 15px;
            }
            .cta-help-card ul {
                list-style: none;
                padding-left: 0;
            }
            .cta-help-card ul li {
                margin-bottom: 12px;
                padding-left: 25px;
                position: relative;
            }
            .cta-help-card ul li:before {
                content: "→";
                position: absolute;
                left: 0;
                color: rgba(255,255,255,0.8);
            }
            .cta-help-card a {
                color: white;
                text-decoration: underline;
            }
            .cta-help-card a:hover {
                text-decoration: none;
            }
        </style>
        
        <form method="post" action="">
            <?php wp_nonce_field('cta_api_keys_settings'); ?>
            
            <!-- Security & Spam Protection -->
            <div class="cta-api-keys-section">
                <h2>
                    <span class="dashicons dashicons-shield"></span>
                    Security & Spam Protection
                </h2>
                
                <div class="cta-api-key-field">
                    <label for="cta_recaptcha_site_key">
                        reCAPTCHA Site Key
                        <?php if ($recaptcha_site_key) : ?>
                            <span class="cta-api-key-status configured">✓ Configured</span>
                        <?php else : ?>
                            <span class="cta-api-key-status not-configured">Not Set</span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="text" 
                        id="cta_recaptcha_site_key" 
                        name="cta_recaptcha_site_key" 
                        value="<?php echo esc_attr($recaptcha_site_key); ?>" 
                        placeholder="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"
                    />
                    <p class="description">
                        Your Google reCAPTCHA v3 site key (public key). reCAPTCHA v3 runs invisibly in the background and doesn't show a checkbox.
                        <a href="https://www.google.com/recaptcha/admin" target="_blank">Get your keys here →</a>
                        <br><strong>Important:</strong> Make sure to add all your domains (including localhost for development) to the "Domains" list in your reCAPTCHA key settings. 
                        <a href="https://console.cloud.google.com/security/recaptcha" target="_blank">Configure domains in Google Cloud Console →</a>
                    </p>
                </div>
                
                <div class="cta-api-key-field">
                    <label for="cta_recaptcha_secret_key">
                        reCAPTCHA Secret Key
                        <?php if ($recaptcha_secret_key) : ?>
                            <span class="cta-api-key-status configured">✓ Configured</span>
                        <?php else : ?>
                            <span class="cta-api-key-status not-configured">Not Set</span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="password" 
                        id="cta_recaptcha_secret_key" 
                        name="cta_recaptcha_secret_key" 
                        value="<?php echo esc_attr($recaptcha_secret_key); ?>" 
                        placeholder="6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe"
                    />
                    <p class="description">
                        Your Google reCAPTCHA v3 secret key (private key). Keep this secure and never share it publicly.
                    </p>
                </div>
            </div>
            
            <!-- Analytics & Tracking -->
            <div class="cta-api-keys-section">
                <h2>
                    <span class="dashicons dashicons-chart-line"></span>
                    Analytics & Tracking
                </h2>
                
                <div class="cta-api-key-field">
                    <label for="cta_google_analytics_id">
                        Google Analytics ID
                        <?php if ($ga_id) : ?>
                            <span class="cta-api-key-status configured">✓ Configured</span>
                        <?php else : ?>
                            <span class="cta-api-key-status not-configured">Not Set</span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="text" 
                        id="cta_google_analytics_id" 
                        name="cta_google_analytics_id" 
                        value="<?php echo esc_attr($ga_id); ?>" 
                        placeholder="G-XXXXXXXXXX or UA-XXXXXXXXX-X"
                    />
                            <p class="description">
                                Your Google Analytics measurement ID (GA4: G-XXXXXXXXXX) or tracking ID (Universal Analytics: UA-XXXXXXXXX-X).
                                <a href="https://analytics.google.com/" target="_blank">Get your ID →</a><br>
                                <strong>💡 Tip:</strong> Once configured, analytics will appear on your dashboard automatically.
                            </p>
                </div>
                
                <div class="cta-api-key-field">
                    <label for="cta_facebook_pixel_id">
                        Facebook Pixel ID
                        <?php if ($fb_pixel) : ?>
                            <span class="cta-api-key-status configured">✓ Configured</span>
                        <?php else : ?>
                            <span class="cta-api-key-status not-configured">Not Set</span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="text" 
                        id="cta_facebook_pixel_id" 
                        name="cta_facebook_pixel_id" 
                        value="<?php echo esc_attr($fb_pixel); ?>" 
                        placeholder="123456789012345"
                    />
                    <p class="description">
                        Your Facebook Pixel ID for tracking conversions and remarketing campaigns.
                        <a href="https://business.facebook.com/events_manager" target="_blank">Get your Pixel ID →</a>
                    </p>
                </div>
                
                <div class="cta-api-key-field">
                    <label for="cta_facebook_access_token">
                        Conversions API Access Token
                        <?php if ($fb_access_token) : ?>
                            <span class="cta-api-key-status configured">✓ Configured</span>
                        <?php else : ?>
                            <span class="cta-api-key-status not-configured">Not Set</span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="password" 
                        id="cta_facebook_access_token" 
                        name="cta_facebook_access_token" 
                        value="<?php echo esc_attr($fb_access_token); ?>" 
                        placeholder="Your Conversions API access token"
                    />
                    <p class="description">
                        Server-side access token for Conversions API. 
                        <a href="https://developers.facebook.com/docs/marketing-api/conversions-api/get-started" target="_blank">Get your Access Token →</a>
                    </p>
                </div>
                
                <div class="cta-api-key-field">
                    <label for="cta_facebook_test_event_code">Test Event Code (Optional)</label>
                    <input 
                        type="text" 
                        id="cta_facebook_test_event_code" 
                        name="cta_facebook_test_event_code" 
                        value="<?php echo esc_attr($fb_test_code); ?>" 
                        placeholder="TEST12345"
                    />
                    <p class="description">
                        Test event code from Events Manager → Test Events. Leave blank for production.
                    </p>
                </div>
                
                <div class="cta-api-key-field">
                    <label>
                        <input type="checkbox" 
                               id="cta_facebook_conversions_api_enabled" 
                               name="cta_facebook_conversions_api_enabled" 
                               value="1" 
                               <?php checked($fb_enabled, 1); ?>>
                        Enable Conversions API (Server-Side Tracking)
                    </label>
                    <p class="description">
                        When enabled, conversion events are sent directly from your server to Facebook, improving reliability and accuracy especially with iOS 14.5+ privacy changes.
                    </p>
                </div>
            </div>
            
            <?php
            // Display Facebook Lead Ads Webhook settings
            if (function_exists('cta_facebook_lead_ads_webhook_settings_fields')) {
                cta_facebook_lead_ads_webhook_settings_fields();
            }
            ?>
            
            <!-- Maps & Location Services -->
            <div class="cta-api-keys-section">
                <h2>
                    <span class="dashicons dashicons-location"></span>
                    Maps & Location Services
                </h2>
                
                <div class="cta-api-key-field">
                    <label for="cta_google_maps_api_key">
                        Google Maps API Key
                        <?php if ($maps_key) : ?>
                            <span class="cta-api-key-status configured">✓ Configured</span>
                        <?php else : ?>
                            <span class="cta-api-key-status not-configured">Not Set</span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="text" 
                        id="cta_google_maps_api_key" 
                        name="cta_google_maps_api_key" 
                        value="<?php echo esc_attr($maps_key); ?>" 
                        placeholder="AIzaSyBxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                    />
                    <p class="description">
                        Your Google Maps API key for interactive maps and location features on your website.
                        <a href="https://console.cloud.google.com/google/maps-apis" target="_blank">Get your API key →</a>
                    </p>
                </div>
            </div>
            
            <!-- Eventbrite Integration -->
            <div class="cta-api-keys-section">
                <h2>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    Eventbrite Integration
                </h2>
                
                <div class="cta-api-key-field">
                    <label for="cta_eventbrite_oauth_token">
                        Eventbrite OAuth Token
                        <?php if ($eventbrite_token) : ?>
                            <span class="cta-api-key-status configured">✓ Configured</span>
                        <?php else : ?>
                            <span class="cta-api-key-status not-configured">Not Set</span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="text" 
                        id="cta_eventbrite_oauth_token" 
                        name="cta_eventbrite_oauth_token" 
                        value="<?php echo esc_attr($eventbrite_token); ?>" 
                        placeholder="OAuth token"
                    />
                    <p class="description">
                        Your Eventbrite Personal Access Token.
                        <strong>How to get it:</strong>
                        <ol style="margin-left: 20px; margin-top: 5px;">
                            <li>Log into your Eventbrite account</li>
                            <li>Go to <a href="https://www.eventbrite.com/platform/api-keys/" target="_blank">API Keys</a></li>
                            <li>Click "Create API Key" or use existing Personal Access Token</li>
                            <li>Copy the token and paste it here</li>
                        </ol>
                    </p>
                </div>
                
                <div class="cta-api-key-field">
                    <label for="cta_eventbrite_organization_id">
                        Organization ID
                        <?php if ($eventbrite_org_id) : ?>
                            <span class="cta-api-key-status configured">✓ Configured</span>
                        <?php else : ?>
                            <span class="cta-api-key-status not-configured">Not Set</span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="text" 
                        id="cta_eventbrite_organization_id" 
                        name="cta_eventbrite_organization_id" 
                        value="<?php echo esc_attr($eventbrite_org_id); ?>" 
                        placeholder="Organization ID"
                    />
                    <p class="description">
                        Your Eventbrite Organization ID.
                        <strong>How to find it:</strong>
                        <ol style="margin-left: 20px; margin-top: 5px;">
                            <li>Go to your Eventbrite <a href="https://www.eventbrite.com/account-settings/" target="_blank">Account Settings</a></li>
                            <li>Look for "Organization" section</li>
                            <li>The ID is in the URL when viewing your organization: <code>/organizations/123456789/</code></li>
                            <li>Or use the API: <code>GET /users/me/organizations/</code></li>
                        </ol>
                    </p>
                    <button type="button" id="cta-test-eventbrite-connection" class="button" style="margin-top: 10px;">
                        Test Connection
                    </button>
                    <span id="cta-test-eventbrite-status" style="margin-left: 10px;"></span>
                </div>
                
                <div class="cta-api-key-field">
                    <label for="cta_eventbrite_venue_id">
                        The Maidstone Studios Venue ID
                        <?php if ($eventbrite_venue_id) : ?>
                            <span class="cta-api-key-status configured">✓ Configured</span>
                        <?php else : ?>
                            <span class="cta-api-key-status not-configured">Auto-populated</span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="text" 
                        id="cta_eventbrite_venue_id" 
                        name="cta_eventbrite_venue_id" 
                        value="<?php echo esc_attr($eventbrite_venue_id); ?>" 
                        placeholder="Auto-populated" 
                        readonly
                    />
                    <p class="description">
                        Automatically set when "The Maidstone Studios" venue is created. Leave empty to auto-create.
                    </p>
                </div>
                
                <div class="cta-api-key-field">
                    <label>
                        <input 
                            type="checkbox" 
                            name="cta_eventbrite_auto_upload" 
                            value="1" 
                            <?php checked(1, $eventbrite_auto_upload); ?>
                        />
                        Automatically upload events to Eventbrite when saved
                        <span class="dashicons dashicons-editor-help" style="cursor: help; color: #646970; margin-left: 5px;" title="When enabled, events are automatically uploaded to Eventbrite when you save them in WordPress. Disable to manually control uploads."></span>
                    </label>
                </div>
                
                <div class="cta-api-key-field">
                    <label>
                        <input 
                            type="checkbox" 
                            name="cta_eventbrite_auto_publish" 
                            value="1" 
                            <?php checked(1, $eventbrite_auto_publish); ?>
                        />
                        Automatically publish events (otherwise created as drafts)
                        <span class="dashicons dashicons-editor-help" style="cursor: help; color: #646970; margin-left: 5px;" title="When enabled, uploaded events are immediately published on Eventbrite. When disabled, events are created as drafts for review."></span>
                    </label>
                </div>
                
                <div class="cta-api-key-field">
                    <label>
                        <input 
                            type="checkbox" 
                            name="cta_eventbrite_auto_sync_spaces" 
                            value="1" 
                            <?php checked(1, $eventbrite_auto_sync); ?>
                        />
                        Automatically sync spaces from Eventbrite to WordPress
                        <span class="dashicons dashicons-editor-help" style="cursor: help; color: #646970; margin-left: 5px;" title="Syncs Eventbrite ticket sales into WordPress and recalculates spaces_available from: total_spaces (WordPress) minus (WordPress bookings + Eventbrite sales). Does not overwrite total_spaces."></span>
                    </label>
                    <p class="description">Syncs Eventbrite sales and recalculates spaces_available (without overwriting total_spaces)</p>
                </div>
                
                <div class="cta-api-key-field">
                    <label for="cta_eventbrite_sync_frequency">Sync Frequency</label>
                    <select name="cta_eventbrite_sync_frequency" id="cta_eventbrite_sync_frequency">
                        <option value="hourly" <?php selected($eventbrite_sync_freq, 'hourly'); ?>>Every Hour</option>
                        <option value="twicedaily" <?php selected($eventbrite_sync_freq, 'twicedaily'); ?>>Twice Daily</option>
                        <option value="daily" <?php selected($eventbrite_sync_freq, 'daily'); ?>>Once Daily</option>
                    </select>
                    <p class="description">How often to check Eventbrite for ticket sales updates</p>
                </div>
                
                <div class="cta-api-key-field">
                    <label>
                        <input 
                            type="checkbox" 
                            name="cta_eventbrite_use_ai_description" 
                            value="1" 
                            <?php checked(1, $eventbrite_use_ai); ?>
                        />
                        Use AI-Generated Descriptions
                    </label>
                    <p class="description">Generate SEO-optimized descriptions using AI (requires AI API key configured)</p>
                </div>
                
                <?php if ($last_sync) : ?>
                <div class="cta-api-key-field" style="padding-top: 15px; border-top: 2px solid #f0f0f1;">
                    <p style="margin: 0; font-size: 13px; color: #646970;">
                        <strong>Last sync:</strong> <?php echo esc_html($last_sync['time']); ?> 
                        (Synced: <?php echo intval($last_sync['synced']); ?> events
                        <?php if ($last_sync['errors'] > 0) : ?>
                            , Errors: <?php echo intval($last_sync['errors']); ?>
                        <?php endif; ?>)
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <p class="submit">
                <?php submit_button('💾 Save All API Keys', 'primary button-hero', 'cta_api_keys_submit', false); ?>
            </p>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            $('#cta-test-eventbrite-connection').on('click', function() {
                var $button = $(this);
                var $status = $('#cta-test-eventbrite-status');
                
                $button.prop('disabled', true);
                $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span> Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cta_test_eventbrite_connection',
                        nonce: '<?php echo wp_create_nonce('cta_test_eventbrite'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<span style="color: #00a32a;">✓ ' + response.data.message + '</span>');
                            if (response.data.user) {
                                $status.append(' <small>(User: ' + response.data.user + ')</small>');
                            }
                        } else {
                            $status.html('<span style="color: #d63638;">✗ ' + response.data.message + '</span>');
                        }
                    },
                    error: function() {
                        $status.html('<span style="color: #d63638;">✗ Connection test failed</span>');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        
        <!-- Help & Resources -->
        <div class="cta-help-card">
            <h2>📚 Help & Resources</h2>
            <ul>
                <li><strong>reCAPTCHA:</strong> <a href="https://www.google.com/recaptcha/admin" target="_blank">Get reCAPTCHA keys</a></li>
                <li><strong>Google Analytics:</strong> <a href="https://analytics.google.com/" target="_blank">Google Analytics Dashboard</a></li>
                <li><strong>Facebook Pixel:</strong> <a href="https://business.facebook.com/events_manager" target="_blank">Facebook Events Manager</a></li>
                <li><strong>Google Maps:</strong> <a href="https://console.cloud.google.com/google/maps-apis" target="_blank">Google Cloud Console</a></li>
            </ul>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.3);">
                <h3 style="margin-top: 0; color: white;">🤖 AI Content Assistant</h3>
                <p style="margin-bottom: 0; opacity: 0.9;">
                    AI API keys are managed separately in 
                    <a href="<?php echo admin_url('options-general.php?page=cta-ai-settings'); ?>" style="font-weight: 600;">Settings → AI Assistant</a>.
                </p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Get reCAPTCHA site key (from options, fallback to theme mod for backwards compatibility)
 */
function cta_get_recaptcha_site_key() {
    $key = get_option('cta_recaptcha_site_key', '');
    if (empty($key)) {
        $key = get_theme_mod('cta_recaptcha_site_key', '');
    }
    return $key;
}

/**
 * Get reCAPTCHA secret key (from options, fallback to theme mod for backwards compatibility)
 */
function cta_get_recaptcha_secret_key() {
    $key = get_option('cta_recaptcha_secret_key', '');
    if (empty($key)) {
        $key = get_theme_mod('cta_recaptcha_secret_key', '');
    }
    return $key;
}

/**
 * Get Google Analytics ID
 */
function cta_get_google_analytics_id() {
    return get_option('cta_google_analytics_id', '');
}

/**
 * Get Facebook Pixel ID
 */
function cta_get_facebook_pixel_id() {
    return get_option('cta_facebook_pixel_id', '');
}

/**
 * Get Google Maps API Key
 */
function cta_get_google_maps_api_key() {
    return get_option('cta_google_maps_api_key', '');
}

/**
 * Set reCAPTCHA keys programmatically
 * This will set the keys if they're not already configured
 */
function cta_set_recaptcha_keys_if_empty() {
    // Only set if keys are empty
    $current_site_key = get_option('cta_recaptcha_site_key', '');
    $current_secret_key = get_option('cta_recaptcha_secret_key', '');
    
    if (empty($current_site_key)) {
        update_option('cta_recaptcha_site_key', '6Lds4z0sAAAAAN3EIQ0-EvEZB-QE1JLuazfjXKKE');
    }
    
    if (empty($current_secret_key)) {
        update_option('cta_recaptcha_secret_key', '6Lds4z0sAAAAAPAflnMuBqASawL4zmyNCxKlivIO');
    }
}
add_action('admin_init', 'cta_set_recaptcha_keys_if_empty');

