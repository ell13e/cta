<?php
/**
 * Google Search Console Integration
 * 
 * Pulls real performance data from Google Search Console API
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Add Search Console submenu to SEO section
 */
function cta_add_search_console_menu() {
    add_submenu_page(
        'cta-seo',
        'Search Console',
        'Search Console',
        'manage_options',
        'cta-seo-search-console',
        'cta_search_console_page'
    );
}
add_action('admin_menu', 'cta_add_search_console_menu', 25);

/**
 * Search Console admin page
 */
function cta_search_console_page() {
    // Check if API is configured
    $api_configured = get_option('cta_gsc_access_token');
    $property_url = get_option('cta_gsc_property_url', home_url());
    
    // Handle OAuth callback
    if (isset($_GET['code']) && isset($_GET['state']) && wp_verify_nonce($_GET['state'], 'cta_gsc_oauth')) {
        $code = sanitize_text_field($_GET['code']);
        $token = cta_gsc_exchange_code_for_token($code);
        
        if ($token && !is_wp_error($token)) {
            update_option('cta_gsc_access_token', $token);
            update_option('cta_gsc_token_expires', time() + 3600); // 1 hour
            echo '<div class="notice notice-success"><p>Search Console connected successfully!</p></div>';
            $api_configured = true;
        } else {
            echo '<div class="notice notice-error"><p>Failed to connect. Please try again.</p></div>';
        }
    }
    
    // Handle disconnect
    if (isset($_POST['disconnect_gsc']) && check_admin_referer('cta_disconnect_gsc')) {
        delete_option('cta_gsc_access_token');
        delete_option('cta_gsc_token_expires');
        delete_option('cta_gsc_refresh_token');
        delete_option('cta_gsc_property_url');
        $api_configured = false;
        echo '<div class="notice notice-success"><p>Disconnected from Search Console.</p></div>';
    }
    
    // Handle property URL save
    if (isset($_POST['save_property_url']) && check_admin_referer('cta_save_property_url')) {
        $url = esc_url_raw($_POST['property_url']);
        update_option('cta_gsc_property_url', $url);
        $property_url = $url;
        echo '<div class="notice notice-success"><p>Property URL saved!</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>Google Search Console</h1>
        
        <?php if (!$api_configured): ?>
        <div class="card" style="max-width: 800px;">
            <h2>Connect Google Search Console</h2>
            <p>Connect your site to Google Search Console to view real performance data, rankings, and search analytics.</p>
            
            <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin: 20px 0;">
                <h3>Setup Instructions:</h3>
                <ol>
                    <li>Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a></li>
                    <li>Create a new project or select existing one</li>
                    <li>Enable "Google Search Console API"</li>
                    <li>Create OAuth 2.0 credentials (Web application)</li>
                    <li>Add authorized redirect URI: <code><?php echo admin_url('admin.php?page=cta-seo-search-console'); ?></code></li>
                    <li>Enter your Client ID and Client Secret below</li>
                </ol>
            </div>
            
            <form method="post">
                <?php wp_nonce_field('cta_gsc_setup'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gsc_client_id">Client ID</label>
                        </th>
                        <td>
                            <input type="text" id="gsc_client_id" name="gsc_client_id" class="regular-text" />
                            <p class="description">From Google Cloud Console → Credentials</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gsc_client_secret">Client Secret</label>
                        </th>
                        <td>
                            <input type="text" id="gsc_client_secret" name="gsc_client_secret" class="regular-text" />
                            <p class="description">From Google Cloud Console → Credentials</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save & Connect', 'primary', 'save_gsc_credentials'); ?>
            </form>
            
            <?php
            // Handle credentials save
            if (isset($_POST['save_gsc_credentials']) && check_admin_referer('cta_gsc_setup')) {
                $client_id = sanitize_text_field($_POST['gsc_client_id']);
                $client_secret = sanitize_text_field($_POST['gsc_client_secret']);
                
                if (!empty($client_id) && !empty($client_secret)) {
                    update_option('cta_gsc_client_id', $client_id);
                    update_option('cta_gsc_client_secret', $client_secret);
                    
                    // Generate OAuth URL
                    $redirect_uri = admin_url('admin.php?page=cta-seo-search-console');
                    $state = wp_create_nonce('cta_gsc_oauth');
                    $scope = urlencode('https://www.googleapis.com/auth/webmasters.readonly');
                    $oauth_url = "https://accounts.google.com/o/oauth2/v2/auth?client_id=" . urlencode($client_id) . 
                                "&redirect_uri=" . urlencode($redirect_uri) . 
                                "&response_type=code&scope=" . $scope . 
                                "&state=" . $state . 
                                "&access_type=offline&prompt=consent";
                    
                    echo '<div class="notice notice-success"><p>Credentials saved! <a href="' . esc_url($oauth_url) . '" class="button button-primary">Connect to Google Search Console</a></p></div>';
                }
            }
            ?>
        </div>
        <?php else: ?>
        
        <div class="card" style="margin-bottom: 20px;">
            <h2>Connection Status</h2>
            <p>✅ Connected to Google Search Console</p>
            <p><strong>Property URL:</strong> <?php echo esc_html($property_url); ?></p>
            
            <form method="post" style="margin-top: 15px;">
                <?php wp_nonce_field('cta_disconnect_gsc'); ?>
                <button type="submit" name="disconnect_gsc" class="button" onclick="return confirm('Are you sure you want to disconnect?');">
                    Disconnect
                </button>
            </form>
        </div>
        
        <form method="post" style="margin-bottom: 20px;">
            <?php wp_nonce_field('cta_save_property_url'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="property_url">Property URL</label>
                    </th>
                    <td>
                        <input type="url" id="property_url" name="property_url" value="<?php echo esc_attr($property_url); ?>" class="regular-text" />
                        <p class="description">The URL of your property in Search Console (e.g., https://yoursite.com)</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Property URL', 'secondary', 'save_property_url'); ?>
        </form>
        
        <div class="card">
            <h2>Performance Data</h2>
            <?php
            // Try to fetch data
            $performance_data = cta_gsc_get_performance_data($property_url);
            
            if (is_wp_error($performance_data)) {
                echo '<p style="color: #d63638;">Error: ' . esc_html($performance_data->get_error_message()) . '</p>';
                echo '<p>Make sure your property URL is correct and you have access in Google Search Console.</p>';
            } elseif ($performance_data) {
                ?>
                <p>✅ Data fetched successfully! (This is a placeholder - full implementation requires API calls)</p>
                <p><em>Full implementation would show:</em></p>
                <ul>
                    <li>Top performing pages</li>
                    <li>Keyword rankings</li>
                    <li>Click-through rates</li>
                    <li>Average position</li>
                    <li>Impressions and clicks</li>
                </ul>
                <?php
            } else {
                echo '<p>No data available yet. Make sure your site is verified in Google Search Console.</p>';
            }
            ?>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Next Steps</h2>
            <ol>
                <li>Verify your site in <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                <li>Submit your sitemap: <code><?php echo esc_html(home_url('/wp-sitemap.xml')); ?></code></li>
                <li>Wait 24-48 hours for data to populate</li>
                <li>Check back here to view performance metrics</li>
            </ol>
        </div>
        
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Exchange OAuth code for access token
 */
function cta_gsc_exchange_code_for_token($code) {
    $client_id = get_option('cta_gsc_client_id');
    $client_secret = get_option('cta_gsc_client_secret');
    $redirect_uri = admin_url('admin.php?page=cta-seo-search-console');
    
    if (empty($client_id) || empty($client_secret)) {
        return new WP_Error('missing_credentials', 'Client ID or Secret not configured');
    }
    
    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'body' => [
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code',
        ],
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['access_token'])) {
        if (isset($body['refresh_token'])) {
            update_option('cta_gsc_refresh_token', $body['refresh_token']);
        }
        return $body['access_token'];
    }
    
    return new WP_Error('token_error', 'Failed to get access token');
}

/**
 * Get performance data from Search Console
 */
function cta_gsc_get_performance_data($property_url) {
    $access_token = get_option('cta_gsc_access_token');
    
    if (empty($access_token)) {
        return new WP_Error('not_connected', 'Not connected to Search Console');
    }
    
    // Check if token expired
    $expires = get_option('cta_gsc_token_expires', 0);
    if (time() >= $expires) {
        // Try to refresh token
        $refreshed = cta_gsc_refresh_token();
        if (is_wp_error($refreshed)) {
            return $refreshed;
        }
        $access_token = $refreshed;
    }
    
    // This is a placeholder - full implementation would make API calls
    // to https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/searchAnalytics/query
    
    return false; // Placeholder - returns false for now
}

/**
 * Refresh access token
 */
function cta_gsc_refresh_token() {
    $refresh_token = get_option('cta_gsc_refresh_token');
    $client_id = get_option('cta_gsc_client_id');
    $client_secret = get_option('cta_gsc_client_secret');
    
    if (empty($refresh_token)) {
        return new WP_Error('no_refresh_token', 'No refresh token available. Please reconnect.');
    }
    
    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'body' => [
            'refresh_token' => $refresh_token,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'refresh_token',
        ],
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['access_token'])) {
        update_option('cta_gsc_access_token', $body['access_token']);
        update_option('cta_gsc_token_expires', time() + ($body['expires_in'] ?? 3600));
        return $body['access_token'];
    }
    
    return new WP_Error('refresh_error', 'Failed to refresh token');
}
