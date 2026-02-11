<?php
/**
 * Resource Downloads: Custom post type + meta + download tracking table
 *
 * @package ccs-theme
 */
defined('ABSPATH') || exit;

/**
 * Register Resource CPT and Category taxonomy.
 */
function ccs_register_resource_post_type() {
    $labels = [
        'name' => 'Resources',
        'singular_name' => 'Resource',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Resource',
        'edit_item' => 'Edit Resource',
        'new_item' => 'New Resource',
        'view_item' => 'View Resource',
        'search_items' => 'Search Resources',
        'not_found' => 'No resources found',
        'not_found_in_trash' => 'No resources found in Trash',
        'menu_name' => 'Resources',
    ];

    register_post_type('ccs_resource', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-download',
        'supports' => ['title', 'editor', 'thumbnail'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'has_archive' => false,
    ]);

    register_taxonomy('ccs_resource_category', ['ccs_resource'], [
        'label' => 'Resource Categories',
        'public' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'hierarchical' => false,
    ]);
}
add_action('init', 'ccs_register_resource_post_type');

/**
 * Create downloads tracking table.
 */
function ccs_create_resource_downloads_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'ccs_resource_downloads';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      resource_id bigint(20) unsigned NOT NULL,
      first_name varchar(100) NOT NULL,
      last_name varchar(100) NOT NULL,
      email varchar(255) NOT NULL,
      phone varchar(50) DEFAULT NULL,
      date_of_birth date DEFAULT NULL,
      consent tinyint(1) NOT NULL DEFAULT 1,
      ip_address varchar(45) DEFAULT NULL,
      user_agent text DEFAULT NULL,
      downloaded_at datetime NOT NULL,
      email_sent tinyint(1) NOT NULL DEFAULT 0,
      email_sent_at datetime DEFAULT NULL,
      download_count int(11) NOT NULL DEFAULT 0,
      last_downloaded_at datetime DEFAULT NULL,
      token_hash varchar(64) DEFAULT NULL,
      token_expires_at datetime DEFAULT NULL,
      PRIMARY KEY  (id),
      KEY resource_id (resource_id),
      KEY email (email),
      KEY downloaded_at (downloaded_at)
    ) $charset_collate;";

    dbDelta($sql);
}
add_action('after_switch_theme', 'ccs_create_resource_downloads_table', 40);

/**
 * Customize editor title label.
 */
function ccs_resource_editor_title($title) {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'ccs_resource') {
        $title = 'Resource Description (displays on your website)';
    }
    return $title;
}
add_filter('enter_title_here', 'ccs_resource_editor_title');

/**
 * Add custom admin CSS for resource pages
 */
function ccs_resource_admin_styles() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'ccs_resource' || $screen->base !== 'post') {
        return;
    }
    ?>
    <style>
        /* Add label before description editor */
        #postdivrich::before {
            content: "Resource Description";
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
            color: #1d2327;
        }
        #postdivrich::after {
            content: "This content displays on your website where visitors can request the resource. Use it to explain what the resource is and why they should download it.";
            display: block;
            margin-bottom: 8px;
            color: #646970;
            font-size: 13px;
            line-height: 1.5;
        }
        
        /* Make the title field more prominent */
        #titlewrap {
            margin-bottom: 20px;
        }
        #title {
            font-size: 1.4em;
            padding: 12px;
        }
        
        /* Add visual separation between sections */
        .postbox {
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .postbox .inside {
            padding: 16px;
        }
        
        /* Style required field indicators */
        .postbox .inside input[required],
        .postbox .inside textarea[required] {
            border-left: 3px solid #2271b1;
        }
        
        /* Make meta box titles more prominent */
        .postbox-header h2 {
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Style the description editor */
        #postdivrich {
            margin-bottom: 20px;
        }
        
        /* Add helpful styling to code examples */
        .description code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
    <?php
}
add_action('admin_head', 'ccs_resource_admin_styles');

/**
 * Add metaboxes.
 */
function ccs_resource_add_meta_boxes() {
    add_meta_box(
        'ccs_resource_file',
        'Resource File',
        'ccs_resource_file_metabox',
        'ccs_resource',
        'normal',
        'high'
    );

    add_meta_box(
        'ccs_resource_email_template',
        'Email Template',
        'ccs_resource_email_template_metabox',
        'ccs_resource',
        'normal',
        'default'
    );

    add_meta_box(
        'ccs_resource_settings',
        'Resource Settings',
        'ccs_resource_settings_metabox',
        'ccs_resource',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'ccs_resource_add_meta_boxes');

function ccs_resource_file_metabox($post) {
    wp_nonce_field('ccs_resource_meta', 'ccs_resource_meta_nonce');
    $file_id = (int) get_post_meta($post->ID, '_ccs_resource_file_id', true);
    $file_url = $file_id ? wp_get_attachment_url($file_id) : '';
    $file_name = $file_id ? basename(get_attached_file($file_id)) : '';
    ?>
    <div style="margin-bottom: 20px;">
        <p>
            <label for="ccs_resource_file_id"><strong>Attachment ID</strong></label>
        </p>
        <p>
            <input type="number" class="regular-text" id="ccs_resource_file_id" name="ccs_resource_file_id" value="<?php echo esc_attr($file_id); ?>" placeholder="0" required>
        </p>
        <?php if ($file_url) : ?>
            <div style="padding: 12px; background: #f0f0f1; border-left: 4px solid #00a32a; margin: 12px 0;">
                <p style="margin: 0 0 8px;"><strong>Current File:</strong></p>
                <p style="margin: 0 0 8px;"><code><?php echo esc_html($file_name); ?></code></p>
                <p style="margin: 0;">
                    <a href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener" class="button button-small">
                        <span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span> View File
                    </a>
                    <a href="<?php echo esc_url(admin_url('upload.php')); ?>" class="button button-small">
                        <span class="dashicons dashicons-admin-media" style="margin-top: 3px;"></span> Media Library
                    </a>
                </p>
            </div>
        <?php else : ?>
            <div style="padding: 12px; background: #fff3cd; border-left: 4px solid #dba617; margin: 12px 0;">
                <p style="margin: 0;"><strong>⚠️ No file attached</strong></p>
            </div>
        <?php endif; ?>
        <p class="description">
            <strong>How to attach a file:</strong><br>
            1. Upload your file to the <a href="<?php echo esc_url(admin_url('upload.php')); ?>" target="_blank">Media Library</a><br>
            2. Click on the uploaded file to view its details<br>
            3. Copy the Attachment ID (shown in the URL or file details)<br>
            4. Paste the ID in the field above
        </p>
    </div>
    <?php
}

function ccs_resource_email_template_metabox($post) {
    $subject = (string) get_post_meta($post->ID, '_ccs_resource_email_subject', true);
    $body = (string) get_post_meta($post->ID, '_ccs_resource_email_body', true);

    if ($subject === '') {
        $subject = 'Your {{resource_name}} from Continuity of Care Services';
    }
    if ($body === '') {
        $body = "Hi {{first_name}},\n\nThank you for requesting {{resource_name}}.\n\nDownload your resource here:\n{{download_link}}\n\nThis link expires in {{expiry_days}} days.\n\nBest regards,\nContinuity of Care Services"; 
    }
    ?>
    <div style="margin-bottom: 20px;">
        <p>
            <label for="ccs_resource_email_subject"><strong>Subject</strong></label>
        </p>
        <p>
            <input type="text" class="widefat" id="ccs_resource_email_subject" name="ccs_resource_email_subject" value="<?php echo esc_attr($subject); ?>" required placeholder="Your {{resource_name}} from Continuity of Care Services">
        </p>
    </div>
    <div style="margin-bottom: 20px;">
        <p>
            <label for="ccs_resource_email_body"><strong>Body</strong></label>
        </p>
        <p>
            <textarea class="widefat" rows="12" id="ccs_resource_email_body" name="ccs_resource_email_body" required placeholder="Hi {{first_name}},&#10;&#10;Thank you for requesting {{resource_name}}..."><?php echo esc_textarea($body); ?></textarea>
        </p>
    </div>
    <div style="padding: 12px; background: #f0f6fc; border-left: 4px solid #2271b1; margin-bottom: 12px;">
        <p style="margin: 0 0 8px;"><strong>Available Placeholders:</strong></p>
        <table style="width: 100%; font-size: 12px;">
            <tr>
                <td style="padding: 4px 8px 4px 0;"><code>{{first_name}}</code></td>
                <td style="padding: 4px 0;">User's first name</td>
            </tr>
            <tr>
                <td style="padding: 4px 8px 4px 0;"><code>{{last_name}}</code></td>
                <td style="padding: 4px 0;">User's last name</td>
            </tr>
            <tr>
                <td style="padding: 4px 8px 4px 0;"><code>{{email}}</code></td>
                <td style="padding: 4px 0;">User's email address</td>
            </tr>
            <tr>
                <td style="padding: 4px 8px 4px 0;"><code>{{resource_name}}</code></td>
                <td style="padding: 4px 0;">Resource title</td>
            </tr>
            <tr>
                <td style="padding: 4px 8px 4px 0;"><code>{{download_link}}</code></td>
                <td style="padding: 4px 0;"><strong>Download URL (required)</strong></td>
            </tr>
            <tr>
                <td style="padding: 4px 8px 4px 0;"><code>{{expiry_days}}</code></td>
                <td style="padding: 4px 0;">Number of days until link expires</td>
            </tr>
            <tr>
                <td style="padding: 4px 8px 4px 0;"><code>{{site_name}}</code></td>
                <td style="padding: 4px 0;">Website name</td>
            </tr>
        </table>
    </div>
    <?php
}

function ccs_resource_settings_metabox($post) {
    $icon = (string) get_post_meta($post->ID, '_ccs_resource_icon', true);
    $expiry_days = (int) get_post_meta($post->ID, '_ccs_resource_expiry_days', true);
    if ($expiry_days <= 0) $expiry_days = 7;
    if ($icon === '') $icon = 'fas fa-file';
    ?>
    <div style="margin-bottom: 16px;">
        <label for="ccs_resource_icon"><strong>Icon Class</strong></label>
        <input type="text" class="widefat" id="ccs_resource_icon" name="ccs_resource_icon" value="<?php echo esc_attr($icon); ?>" placeholder="fas fa-file-pdf">
        <p class="description" style="margin-top: 6px;">
            Font Awesome icon class<br>
            Common: <code>fas fa-file-pdf</code>, <code>fas fa-file-word</code>, <code>fas fa-file-excel</code>
        </p>
    </div>
    <div style="margin-bottom: 16px;">
        <label for="ccs_resource_expiry_days"><strong>Link Expiry (days)</strong></label>
        <input type="number" class="small-text" id="ccs_resource_expiry_days" name="ccs_resource_expiry_days" value="<?php echo esc_attr($expiry_days); ?>" min="1" max="30" required>
        <p class="description" style="margin-top: 6px;">
            How long download links remain valid (1-30 days)
        </p>
    </div>
    <?php
}

/**
 * Customize the title placeholder
 */
function ccs_resource_title_placeholder($title) {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'ccs_resource') {
        $title = 'Enter resource title (e.g., "CQC Inspection Checklist")';
    }
    return $title;
}
add_filter('enter_title_here', 'ccs_resource_title_placeholder');

/**
 * Customize the editor label
 */
function ccs_resource_editor_settings($settings) {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'ccs_resource') {
        $settings['tinymce'] = [
            'content_css' => get_template_directory_uri() . '/assets/css/editor-style.css',
        ];
    }
    return $settings;
}
add_filter('wp_editor_settings', 'ccs_resource_editor_settings');

/**
 * Add helpful admin notices for resource creation
 */
function ccs_resource_admin_notices() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'ccs_resource') {
        return;
    }
    
    // Only show on add/edit screens
    if ($screen->base !== 'post') {
        return;
    }
    
    global $post;
    
    // Check if this is a new resource (no ID yet or auto-draft)
    $is_new = !$post || $post->post_status === 'auto-draft';
    
    if ($is_new) {
        ?>
        <div class="notice notice-info" style="position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                <div style="flex: 1;">
                    <h3 style="margin-top: 12px;">📋 Resource Upload Checklist</h3>
                    <p><strong>Before publishing, make sure you have:</strong></p>
                    <ol style="margin-left: 20px; line-height: 1.8;">
                        <li><strong>Resource Title</strong> - Enter a clear, descriptive title at the top of this page</li>
                        <li><strong>Resource Description</strong> - Add a description that displays on your website (optional but recommended)</li>
                        <li><strong>File Upload</strong> - Upload your file to the Media Library and enter its Attachment ID</li>
                        <li><strong>Email Template</strong> - Customize the email users receive with their download link</li>
                        <li><strong>Icon & Expiry</strong> - Set the icon class and how long download links remain valid</li>
                        <li><strong>Category</strong> - Add at least one category (right sidebar)</li>
                        <li><strong>Featured Image</strong> - Optional thumbnail for the resource</li>
                    </ol>
                    <p><strong>💡 Tip:</strong> Upload your file to the <a href="<?php echo esc_url(admin_url('upload.php')); ?>" target="_blank">Media Library</a> first, then come back here to complete the resource setup.</p>
                </div>
                <div style="flex-shrink: 0; padding-top: 12px;">
                    <button type="button" id="cta-resource-ai-assistant" class="button button-primary button-large" style="height: auto; padding: 12px 20px; display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                        <span class="dashicons dashicons-admin-network" style="font-size: 20px; width: 20px; height: 20px;"></span>
                        <span>AI Assistant</span>
                    </button>
                    <p style="margin: 8px 0 0; font-size: 12px; color: #646970; text-align: center;">
                        Generate title,<br>description & email
                    </p>
                </div>
            </div>
        </div>
        <?php
    } else {
        // Check for missing required fields
        $file_id = get_post_meta($post->ID, '_ccs_resource_file_id', true);
        $warnings = [];
        
        if (empty($file_id)) {
            $warnings[] = 'No file attached - users won\'t be able to download anything';
        }
        
        $categories = wp_get_post_terms($post->ID, 'ccs_resource_category');
        if (empty($categories) || is_wp_error($categories)) {
            $warnings[] = 'No category assigned - helps organize resources on your website';
        }
        
        if (!empty($warnings)) {
            ?>
            <div class="notice notice-warning">
                <h3 style="margin-top: 12px;">⚠️ Missing Information</h3>
                <ul style="margin-left: 20px;">
                    <?php foreach ($warnings as $warning) : ?>
                        <li><?php echo esc_html($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }
    }
}
add_action('admin_notices', 'ccs_resource_admin_notices');

/**
 * Save resource meta.
 */
function ccs_resource_save_meta($post_id) {
    if (!isset($_POST['ccs_resource_meta_nonce']) || !wp_verify_nonce($_POST['ccs_resource_meta_nonce'], 'ccs_resource_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (get_post_type($post_id) !== 'ccs_resource') {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $file_id = absint($_POST['ccs_resource_file_id'] ?? 0);
    update_post_meta($post_id, '_ccs_resource_file_id', $file_id);

    $subject = sanitize_text_field($_POST['ccs_resource_email_subject'] ?? '');
    $body = wp_kses_post($_POST['ccs_resource_email_body'] ?? '');
    update_post_meta($post_id, '_ccs_resource_email_subject', $subject);
    update_post_meta($post_id, '_ccs_resource_email_body', $body);

    $icon = sanitize_text_field($_POST['ccs_resource_icon'] ?? '');
    $expiry_days = absint($_POST['ccs_resource_expiry_days'] ?? 7);
    if ($expiry_days < 1) $expiry_days = 1;
    if ($expiry_days > 30) $expiry_days = 30;

    update_post_meta($post_id, '_ccs_resource_icon', $icon);
    update_post_meta($post_id, '_ccs_resource_expiry_days', $expiry_days);
}
add_action('save_post', 'ccs_resource_save_meta');

/**
 * Enqueue AI assistant script for resource edit screen
 */
function ccs_resource_ai_assistant_scripts($hook) {
    global $post;
    
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    
    if (!$post || get_post_type($post) !== 'ccs_resource') {
        return;
    }
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#cta-resource-ai-assistant').on('click', function() {
            const $button = $(this);
            const originalHtml = $button.html();
            
            // Disable button and show loading
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span> Generating...');
            
            // Get file name if available
            const fileId = $('#ccs_resource_file_id').val();
            let fileName = '';
            
            if (fileId) {
                // Try to get file name from the displayed current file
                const fileNameEl = $('[style*="Current File"]').next('p').find('code');
                if (fileNameEl.length) {
                    fileName = fileNameEl.text();
                }
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_resource_content',
                    nonce: '<?php echo wp_create_nonce('ccs_resource_ai_nonce'); ?>',
                    file_name: fileName
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Fill in the title
                        if (response.data.title && !$('#title').val()) {
                            $('#title').val(response.data.title);
                            $('#title-prompt-text').hide();
                        }
                        
                        // Fill in the description (main editor)
                        if (response.data.description && typeof tinymce !== 'undefined') {
                            const editor = tinymce.get('content');
                            if (editor) {
                                editor.setContent(response.data.description);
                            } else {
                                $('#content').val(response.data.description);
                            }
                        }
                        
                        // Fill in the excerpt
                        if (response.data.excerpt) {
                            $('#excerpt').val(response.data.excerpt);
                        }
                        
                        // Fill in email subject
                        if (response.data.email_subject) {
                            $('#ccs_resource_email_subject').val(response.data.email_subject);
                        }
                        
                        // Fill in email body
                        if (response.data.email_body) {
                            $('#ccs_resource_email_body').val(response.data.email_body);
                        }
                        
                        // Fill in icon suggestion
                        if (response.data.icon) {
                            $('#ccs_resource_icon').val(response.data.icon);
                        }
                        
                        // Show success message
                        $('<div class="notice notice-success is-dismissible"><p><strong>✨ AI Assistant:</strong> Content generated successfully! Review and edit as needed.</p></div>')
                            .insertAfter('.wp-header-end')
                            .delay(5000)
                            .fadeOut();
                    } else {
                        alert('Failed to generate content: ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AI Assistant error:', error);
                    alert('Failed to generate content. Please check your AI settings.');
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        });
    });
    </script>
    <style>
    @keyframes rotation {
        from { transform: rotate(0deg); }
        to { transform: rotate(359deg); }
    }
    </style>
    <?php
}
add_action('admin_footer', 'ccs_resource_ai_assistant_scripts');

/**
 * AJAX handler to generate resource content with AI
 */
function ccs_ajax_generate_resource_content() {
    check_ajax_referer('ccs_resource_ai_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $file_name = sanitize_text_field($_POST['file_name'] ?? '');
    
    // Build the prompt
    $prompt = "You are helping create a downloadable resource for a care training website. ";
    
    if (!empty($file_name)) {
        $prompt .= "The file is named: {$file_name}. ";
    }
    
    $prompt .= "Generate the following content:\n\n";
    $prompt .= "1. A clear, professional title (max 60 characters)\n";
    $prompt .= "2. A detailed description (2-3 paragraphs) explaining what the resource is and how it helps care workers/managers\n";
    $prompt .= "3. A short excerpt (max 150 characters) for preview cards\n";
    $prompt .= "4. An email subject line for delivering the resource\n";
    $prompt .= "5. An email body that thanks the user and includes placeholders: {{first_name}}, {{resource_name}}, {{download_link}}, {{expiry_days}}\n";
    $prompt .= "6. A Font Awesome icon class that matches the resource type (e.g., 'fas fa-file-pdf', 'fas fa-file-excel', 'fas fa-clipboard-check')\n\n";
    $prompt .= "Format your response as JSON with keys: title, description, excerpt, email_subject, email_body, icon\n";
    $prompt .= "Make it professional, helpful, and specific to care sector training.";
    
    // Call AI provider
    if (!function_exists('ccs_ai_generate_content')) {
        wp_send_json_error(['message' => 'AI functionality not available. Please check your AI settings.']);
    }
    
    $response = ccs_ai_generate_content($prompt, [
        'temperature' => 0.7,
        'max_tokens' => 1000,
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }
    
    // Try to parse JSON response
    $content = json_decode($response, true);
    
    if (!$content) {
        // If not JSON, try to extract content manually
        $content = [
            'title' => 'Resource Title',
            'description' => $response,
            'excerpt' => substr($response, 0, 150),
            'email_subject' => 'Your {{resource_name}} from Continuity of Care Services',
            'email_body' => "Hi {{first_name}},\n\nThank you for requesting {{resource_name}}.\n\nDownload your resource here:\n{{download_link}}\n\nThis link expires in {{expiry_days}} days.\n\nBest regards,\nContinuity of Care Services",
            'icon' => 'fas fa-file'
        ];
    }
    
    wp_send_json_success($content);
}
add_action('wp_ajax_ccs_generate_resource_content', 'ccs_ajax_generate_resource_content');
