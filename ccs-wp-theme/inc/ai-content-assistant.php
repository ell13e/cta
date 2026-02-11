<?php
/**
 * AI Content Assistant
 * Integrates AI writing capabilities into WordPress admin
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Register AI Assistant settings
 */
function ccs_ai_register_settings() {
    register_setting('ccs_ai_settings', 'ccs_ai_api_key', [
        'sanitize_callback' => 'sanitize_text_field',
        'type' => 'string',
    ]);
    // Provider-specific keys (optional, used for fallback order)
    register_setting('ccs_ai_settings', 'ccs_ai_groq_api_key', [
        'sanitize_callback' => 'sanitize_text_field',
        'type' => 'string',
    ]);
    register_setting('ccs_ai_settings', 'ccs_ai_anthropic_api_key', [
        'sanitize_callback' => 'sanitize_text_field',
        'type' => 'string',
    ]);
    register_setting('ccs_ai_settings', 'ccs_ai_openai_api_key', [
        'sanitize_callback' => 'sanitize_text_field',
        'type' => 'string',
    ]);
    register_setting('ccs_ai_settings', 'ccs_ai_provider', [
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'groq',
        'type' => 'string',
    ]);
}
add_action('admin_init', 'ccs_ai_register_settings');

/**
 * Add AI Settings page under Settings menu
 */
function ccs_ai_settings_menu() {
    add_options_page(
        'AI Content Assistant',
        'AI Assistant',
        'manage_options',
        'cta-ai-settings',
        'ccs_ai_settings_page'
    );
}
add_action('admin_menu', 'ccs_ai_settings_menu');

/**
 * AI Settings page content
 */
function ccs_ai_settings_page() {
    // Security check - ensure user has permission
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-admin-network" style="font-size: 32px; vertical-align: middle; margin-right: 8px; color: #2271b1;"></span>
            AI Content Assistant Settings
        </h1>
        <hr class="wp-header-end">
        
        <style>
            .cta-ai-settings-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 25px;
                margin-bottom: 25px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .cta-ai-settings-section h2 {
                margin-top: 0;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #f0f0f1;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .cta-ai-settings-section h2 .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                color: #2271b1;
            }
            .cta-ai-settings-field {
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 1px solid #f0f0f1;
            }
            .cta-ai-settings-field:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            .cta-ai-settings-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                font-size: 14px;
            }
            .cta-ai-settings-field select,
            .cta-ai-settings-field input[type="password"] {
                width: 100%;
                max-width: 600px;
                padding: 10px 12px;
                font-size: 14px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                transition: border-color 0.2s;
            }
            .cta-ai-settings-field select:focus,
            .cta-ai-settings-field input[type="password"]:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: none;
            }
            .cta-ai-settings-field .description {
                margin-top: 8px;
                color: #646970;
                font-size: 13px;
            }
            .cta-ai-help-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 25px;
                border-radius: 8px;
                margin-top: 25px;
            }
            .cta-ai-help-card h2 {
                margin-top: 0;
                color: white;
                display: flex;
                align-items: center;
                gap: 10px;
                border-bottom: 2px solid rgba(255,255,255,0.2);
                padding-bottom: 15px;
                margin-bottom: 15px;
            }
            .cta-ai-help-card h2 .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
            }
            .cta-ai-help-card ul {
                margin: 10px 0 0 0;
                padding-left: 20px;
                opacity: 0.95;
            }
            .cta-ai-help-card li {
                margin-bottom: 8px;
            }
        </style>
        
        <form method="post" action="options.php">
            <?php settings_fields('ccs_ai_settings'); ?>
            
            <div class="cta-ai-settings-section">
                <h2>
                    <span class="dashicons dashicons-admin-settings"></span>
                    Configuration
                </h2>
                
                <div class="cta-ai-settings-field">
                    <label for="ccs_ai_provider">AI Provider</label>
                    <select name="ccs_ai_provider" id="ccs_ai_provider">
                            <option value="groq" <?php selected(get_option('ccs_ai_provider', 'groq'), 'groq'); ?>>Groq (Fast)</option>
                            <option value="anthropic" <?php selected(get_option('ccs_ai_provider', 'groq'), 'anthropic'); ?>>Claude (Anthropic) - Best for writing</option>
                            <option value="openai" <?php selected(get_option('ccs_ai_provider'), 'openai'); ?>>GPT-4 (OpenAI)</option>
                        </select>
                        <p class="description">Groq is typically very fast and cost-effective (limits vary by account). Claude is usually best for highest-quality writing. Check your current limits in your provider dashboard.</p>
                </div>
                
                <div class="cta-ai-settings-field">
                    <label for="ccs_ai_api_key">API Key</label>
                    <input 
                        type="password" 
                        name="ccs_ai_api_key" 
                        id="ccs_ai_api_key" 
                        value="<?php echo esc_attr(get_option('ccs_ai_api_key')); ?>" 
                        autocomplete="off"
                        spellcheck="false"
                        class="regular-text"
                    />
                        <p class="description">
                            Optional legacy field. If you add provider-specific keys below, those will be used first.
                        </p>
                </div>

                <div class="cta-ai-settings-field">
                    <label for="ccs_ai_groq_api_key">Groq API Key</label>
                    <input
                        type="password"
                        name="ccs_ai_groq_api_key"
                        id="ccs_ai_groq_api_key"
                        value="<?php echo esc_attr(get_option('ccs_ai_groq_api_key')); ?>"
                        autocomplete="off"
                        spellcheck="false"
                        class="regular-text"
                    />
                    <p class="description">Used first. Get it from <a href="https://console.groq.com" target="_blank">Groq Console</a>.</p>
                </div>

                <div class="cta-ai-settings-field">
                    <label for="ccs_ai_anthropic_api_key">Claude (Anthropic) API Key</label>
                    <input
                        type="password"
                        name="ccs_ai_anthropic_api_key"
                        id="ccs_ai_anthropic_api_key"
                        value="<?php echo esc_attr(get_option('ccs_ai_anthropic_api_key')); ?>"
                        autocomplete="off"
                        spellcheck="false"
                        class="regular-text"
                    />
                    <p class="description">Fallback after Groq. Get it from <a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic Console</a>.</p>
                </div>

                <div class="cta-ai-settings-field">
                    <label for="ccs_ai_openai_api_key">OpenAI API Key</label>
                    <input
                        type="password"
                        name="ccs_ai_openai_api_key"
                        id="ccs_ai_openai_api_key"
                        value="<?php echo esc_attr(get_option('ccs_ai_openai_api_key')); ?>"
                        autocomplete="off"
                        spellcheck="false"
                        class="regular-text"
                    />
                    <p class="description">Fallback after Groq/Claude. Get it from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>.</p>
                </div>
            </div>
            
            <p class="submit">
                <?php submit_button('Save Settings', 'primary button-hero'); ?>
            </p>
        </form>
        
        <div class="cta-ai-help-card">
            <h2>
                <span class="dashicons dashicons-lightbulb"></span>
                Usage Tips
            </h2>
            <ul>
                <li>The AI assistant will appear in the sidebar when editing posts, courses, and scheduled sessions</li>
            <li>Use "Generate Article" for full drafts based on a topic</li>
            <li>Use "Improve Content" to enhance existing text</li>
            <li>All generated content should be reviewed for accuracy</li>
            <li>For care sector content, always verify compliance claims</li>
        </ul>
        </div>
    </div>
    <?php
}

/**
 * Add AI Assistant meta box to post editor
 */
function ccs_ai_add_metabox() {
    add_meta_box(
        'ccs_ai_assistant',
        '🤖 AI Content Assistant',
        'ccs_ai_metabox_callback',
        'post',
        'side',
        'high'
    );
    
    // Add AI Assistant for Courses
    add_meta_box(
        'ccs_ai_course_assistant',
        '🤖 AI Course Assistant',
        'ccs_ai_course_metabox_callback',
        'course',
        'side',
        'high'
    );
    
    // Add AI Assistant for Course Events
    add_meta_box(
        'ccs_ai_event_assistant',
        '🤖 AI Event Assistant',
        'ccs_ai_event_metabox_callback',
        'course_event',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'ccs_ai_add_metabox');

/**
 * AI Assistant meta box content
 */
function ccs_ai_metabox_callback($post) {
    $api_key = get_option('ccs_ai_api_key');
    
    if (empty($api_key)) {
        ?>
        <div class="cta-ai-setup-notice">
            <p><strong>Setup Required</strong></p>
            <p>
                <a href="<?php echo admin_url('options-general.php?page=cta-ai-settings'); ?>">Add your API key</a> to enable AI features.
            </p>
        </div>
        <?php
        return;
    }
    
    wp_nonce_field('ccs_ai_nonce', 'ccs_ai_nonce_field');
    ?>
    <style>
        .cta-ai-section {
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0f1;
        }
        .cta-ai-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .cta-ai-label {
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: #1d2327;
            margin-bottom: 8px;
            display: block;
        }
        .cta-ai-input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .cta-ai-input:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
            outline: none;
        }
        .cta-ai-btn {
            width: 100%;
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .cta-ai-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .cta-ai-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .cta-ai-btn-secondary {
            background: #f0f0f1;
            color: #1d2327;
            border: 1px solid #c3c4c7;
        }
        .cta-ai-btn-secondary:hover {
            background: #e0e0e0;
        }
        .cta-ai-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        .cta-ai-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: cta-spin 0.8s linear infinite;
        }
        @keyframes cta-spin {
            to { transform: rotate(360deg); }
        }
        .cta-ai-quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .cta-ai-quick-btn {
            padding: 8px;
            font-size: 11px;
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .cta-ai-quick-btn:hover {
            background: #f6f7f7;
            border-color: #2271b1;
        }
        .cta-ai-output {
            background: #f6f7f7;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 12px;
            font-size: 13px;
            line-height: 1.5;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
        .cta-ai-output.visible {
            display: block;
        }
        .cta-ai-insert-btn {
            margin-top: 8px;
            background: #00a32a;
            color: white;
        }
        .cta-ai-insert-btn:hover {
            background: #008a20;
        }
    </style>
    
    <!-- Generate Full Article -->
    <div class="cta-ai-section">
        <label class="cta-ai-label">✨ Generate Article</label>
        <input type="text" id="cta-ai-topic" class="cta-ai-input" placeholder="Enter topic, e.g., 'CQC inspection tips'" />
        <select id="cta-ai-tone" class="cta-ai-input">
            <option value="professional">Professional & Informative</option>
            <option value="friendly">Friendly & Approachable</option>
            <option value="authoritative">Authoritative & Expert</option>
        </select>
        <button type="button" id="cta-ai-generate" class="cta-ai-btn cta-ai-btn-primary">
            <span class="btn-text">🚀 Generate Article</span>
            <span class="btn-loading" style="display:none;"><span class="cta-ai-spinner"></span> Writing...</span>
        </button>
    </div>
    
    <!-- Quick Actions -->
    <div class="cta-ai-section">
        <label class="cta-ai-label">⚡ Quick Actions</label>
        <div class="cta-ai-quick-actions">
            <button type="button" class="cta-ai-quick-btn" data-action="improve">✏️ Improve</button>
            <button type="button" class="cta-ai-quick-btn" data-action="shorten">📝 Shorten</button>
            <button type="button" class="cta-ai-quick-btn" data-action="expand">📖 Expand</button>
            <button type="button" class="cta-ai-quick-btn" data-action="seo">🔍 SEO Optimise</button>
            <button type="button" class="cta-ai-quick-btn" data-action="excerpt" style="grid-column: span 2; background: #e8f4ea;">📋 Generate SEO Excerpt</button>
        </div>
        <p style="font-size: 11px; color: #646970; margin: 8px 0 0 0;">
            💬 Use the chat bubble (bottom-right) for formatting help & questions
        </p>
    </div>
    
    <!-- Generate Brief -->
    <div class="cta-ai-section">
        <label class="cta-ai-label">📋 Research Brief</label>
        <input type="text" id="cta-ai-brief-topic" class="cta-ai-input" placeholder="Topic for UK-sourced research brief" />
        <button type="button" id="cta-ai-brief" class="cta-ai-btn cta-ai-btn-secondary">
            <span class="btn-text">📊 Generate Brief (UK Sources)</span>
            <span class="btn-loading" style="display:none;"><span class="cta-ai-spinner" style="border-color: rgba(0,0,0,0.2); border-top-color: #1d2327;"></span></span>
        </button>
    </div>
    
    <!-- Output Area -->
    <div id="cta-ai-output" class="cta-ai-output"></div>
    <button type="button" id="cta-ai-insert" class="cta-ai-btn cta-ai-insert-btn" style="display:none;">
        Insert into Editor
    </button>
    
    <script>
    jQuery(document).ready(function($) {
        var generatedContent = '';
        
        // Generate Article
        $('#cta-ai-generate').on('click', function() {
            var btn = $(this);
            var topic = $('#cta-ai-topic').val();
            var tone = $('#cta-ai-tone').val();
            
            if (!topic) {
                alert('Please enter a topic');
                return;
            }
            
            btn.prop('disabled', true);
            btn.find('.btn-text').hide();
            btn.find('.btn-loading').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_ai_generate',
                    nonce: $('#ccs_ai_nonce_field').val(),
                    topic: topic,
                    tone: tone,
                    type: 'article'
                },
                success: function(response) {
                    if (response.success) {
                        generatedContent = response.data.content;
                        $('#cta-ai-output').html(response.data.content.replace(/\n/g, '<br>')).addClass('visible');
                        $('#cta-ai-insert').show();
                        
                        // Auto-fill title if empty
                        if (!$('#title').val() && response.data.title) {
                            $('#title').val(response.data.title);
                        }
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.find('.btn-text').show();
                    btn.find('.btn-loading').hide();
                }
            });
        });
        
        // Quick Actions
        $('.cta-ai-quick-btn').on('click', function() {
            var action = $(this).data('action');
            var content = '';
            
            // Try to get selected text from editor
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                content = tinymce.activeEditor.selection.getContent({format: 'text'});
            }
            
            if (!content) {
                // Get all content
                if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                    content = tinymce.activeEditor.getContent({format: 'text'});
                } else {
                    content = $('#content').val();
                }
            }
            
            if (!content || content.length < 20) {
                alert('Please add some content first, or select text to improve.');
                return;
            }
            
            var btn = $(this);
            btn.prop('disabled', true).text('...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_ai_generate',
                    nonce: $('#ccs_ai_nonce_field').val(),
                    content: content,
                    type: action
                },
                success: function(response) {
                    if (response.success) {
                        generatedContent = response.data.content;
                        
                        // Format the output nicely
                        var formattedContent = response.data.content
                            .replace(/\n/g, '<br>')
                            .replace(/📌/g, '<br><strong style="color: #667eea;">📌</strong>')
                            .replace(/H2:/g, '<span style="color: #00a32a; font-weight: 600;">H2:</span>')
                            .replace(/H3:/g, '<span style="color: #2271b1; font-weight: 600;">H3:</span>')
                            .replace(/LIST:/g, '<span style="color: #dba617; font-weight: 600;">LIST:</span>')
                            .replace(/QUOTE:/g, '<span style="color: #9b59b6; font-weight: 600;">QUOTE:</span>')
                            .replace(/BOLD:/g, '<span style="color: #e74c3c; font-weight: 600;">BOLD:</span>')
                            .replace(/BREAK:/g, '<span style="color: #3498db; font-weight: 600;">BREAK:</span>');
                        
                        $('#cta-ai-output').html(formattedContent).addClass('visible');
                        
                        // Special handling for different actions
                        if (action === 'excerpt') {
                            $('#excerpt').val(response.data.content);
                            $('#cta-ai-insert').hide();
                            alert('✅ Excerpt added! Check the Excerpt field below.');
                        } else if (action === 'links' || action === 'structure') {
                            // Don't show insert for suggestions/analysis
                            $('#cta-ai-insert').hide();
                        } else {
                            $('#cta-ai-insert').show();
                        }
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    btn.prop('disabled', false);
                    var labels = {
                        improve: '✏️ Improve', 
                        shorten: '📝 Shorten', 
                        expand: '📖 Expand', 
                        seo: '🔍 SEO Optimise',
                        excerpt: '📋 Generate SEO Excerpt',
                        links: '🔗 Suggest External Links',
                        structure: '🔍 Analyse Structure & Suggest Formatting'
                    };
                    btn.text(labels[action]);
                }
            });
        });
        
        // Generate Brief
        $('#cta-ai-brief').on('click', function() {
            var btn = $(this);
            var topic = $('#cta-ai-brief-topic').val();
            
            if (!topic) {
                alert('Please enter a topic');
                return;
            }
            
            btn.prop('disabled', true);
            btn.find('.btn-text').hide();
            btn.find('.btn-loading').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_ai_generate',
                    nonce: $('#ccs_ai_nonce_field').val(),
                    topic: topic,
                    type: 'brief'
                },
                success: function(response) {
                    if (response.success) {
                        generatedContent = response.data.content;
                        $('#cta-ai-output').html(response.data.content.replace(/\n/g, '<br>')).addClass('visible');
                        $('#cta-ai-insert').hide(); // Don't show insert for briefs
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.find('.btn-text').show();
                    btn.find('.btn-loading').hide();
                }
            });
        });
        
        // Insert Content
        $('#cta-ai-insert').on('click', function() {
            if (generatedContent) {
                if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
                    tinymce.activeEditor.setContent(generatedContent);
                } else {
                    $('#content').val(generatedContent);
                }
                $('#cta-ai-output').removeClass('visible');
                $(this).hide();
            }
        });
    });
    </script>
    <?php
}

/**
 * AI Course Assistant meta box content
 */
function ccs_ai_course_metabox_callback($post) {
    $api_key = get_option('ccs_ai_api_key');
    
    if (empty($api_key)) {
        ?>
        <div class="cta-ai-setup-notice">
            <p><strong>Setup Required</strong></p>
            <p>
                <a href="<?php echo admin_url('options-general.php?page=cta-ai-settings'); ?>">Add your API key</a> to enable AI features.
            </p>
        </div>
        <?php
        return;
    }
    
    wp_nonce_field('ccs_ai_nonce', 'ccs_ai_nonce_field');
    
    // Get current course data for context
    $course_title = $post->post_title;
    $course_level = function_exists('get_field') ? get_field('course_level', $post->ID) : '';
    $course_duration = function_exists('get_field') ? get_field('course_duration', $post->ID) : '';
    $course_category_terms = get_the_terms($post->ID, 'course_category');
    $course_category = $course_category_terms && !is_wp_error($course_category_terms) ? $course_category_terms[0]->name : '';
    ?>
    <style>
        .cta-ai-course-section {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f1;
        }
        .cta-ai-course-section:last-child {
            border-bottom: none;
        }
        .cta-ai-course-btn {
            width: 100%;
            padding: 8px 12px;
            margin-bottom: 6px;
            font-size: 12px;
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }
        .cta-ai-course-btn:hover {
            background: #f6f7f7;
            border-color: #2271b1;
        }
        .cta-ai-course-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .cta-ai-course-output {
            background: #f6f7f7;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 10px;
            font-size: 12px;
            max-height: 150px;
            overflow-y: auto;
            display: none;
            margin-top: 8px;
        }
        .cta-ai-course-output.visible {
            display: block;
        }
        .cta-ai-course-insert {
            margin-top: 6px;
            padding: 6px 10px;
            font-size: 11px;
            background: #00a32a;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            display: none;
        }
        .cta-ai-course-insert:hover {
            background: #008a20;
        }
        .cta-ai-suggestions {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #c3c4c7;
        }
        .cta-ai-suggestions h4 {
            font-size: 12px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: #1d2327;
        }
        .cta-ai-suggestion-item,
        .cta-ai-followup-item {
            display: inline-block;
            margin: 4px 8px 4px 0;
            padding: 4px 8px;
            background: #f0f0f1;
            border: 1px solid #c3c4c7;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
            color: #2271b1;
            transition: all 0.2s;
        }
        .cta-ai-suggestion-item:hover,
        .cta-ai-followup-item:hover {
            background: #2271b1;
            color: white;
            border-color: #2271b1;
        }
        .cta-ai-suggestions-section {
            margin-bottom: 12px;
        }
    </style>
    
    <div class="cta-ai-course-section">
        <p style="font-size: 11px; color: #646970; margin: 0 0 8px 0;">
            <strong>Course:</strong> <?php echo esc_html($course_title); ?><br>
            <?php if ($course_category) : ?><strong>Category:</strong> <?php echo esc_html($course_category); ?><br><?php endif; ?>
            <?php if ($course_level) : ?><strong>Level:</strong> <?php echo esc_html($course_level); ?><br><?php endif; ?>
            <?php if ($course_duration) : ?><strong>Duration:</strong> <?php echo esc_html($course_duration); ?><?php endif; ?>
        </p>
    </div>
    
    <div class="cta-ai-course-section">
        <button type="button" class="cta-ai-course-btn" data-action="description" data-field="course_description">
            📝 Generate Course Description
        </button>
        <button type="button" class="cta-ai-course-btn" data-action="suitable_for" data-field="course_suitable_for">
            👥 Generate "Who Should Attend"
        </button>
        <button type="button" class="cta-ai-course-btn" data-action="prerequisites" data-field="course_prerequisites">
            ✅ Generate Prerequisites
        </button>
        <button type="button" class="cta-ai-course-btn" data-action="outcomes" data-field="course_outcomes">
            🎯 Generate Learning Outcomes
        </button>
    </div>
    
    <div id="cta-ai-course-output" class="cta-ai-course-output"></div>
    <div id="cta-ai-course-suggestions" class="cta-ai-suggestions" style="display: none;"></div>
    <button type="button" id="cta-ai-course-insert" class="cta-ai-course-insert">Insert into Field</button>
    
    <script>
    jQuery(document).ready(function($) {
        var generatedContent = '';
        var targetField = '';
        
        $('.cta-ai-course-btn').on('click', function() {
            var btn = $(this);
            var action = btn.data('action');
            targetField = btn.data('field');
            
            btn.prop('disabled', true).text('Generating...');
            
            var courseData = {
                title: '<?php echo esc_js($course_title); ?>',
                category: '<?php echo esc_js($course_category); ?>',
                level: '<?php echo esc_js($course_level); ?>',
                duration: '<?php echo esc_js($course_duration); ?>'
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_ai_generate_course',
                    nonce: $('#ccs_ai_nonce_field').val(),
                    type: action,
                    course_data: courseData
                },
                success: function(response) {
                    if (response.success) {
                        generatedContent = response.data.content;
                        $('#cta-ai-course-output').html(response.data.content.replace(/\n/g, '<br>')).addClass('visible');
                        $('#cta-ai-course-insert').show();
                        
                        // Display suggestions and follow-ups
                        var suggestionsHtml = '';
                        if (response.data.suggestions && response.data.suggestions.length > 0) {
                            suggestionsHtml += '<div class="cta-ai-suggestions-section">';
                            suggestionsHtml += '<h4>💡 Suggested Next Steps:</h4>';
                            response.data.suggestions.forEach(function(suggestion) {
                                suggestionsHtml += '<span class="cta-ai-suggestion-item" data-suggestion="' + 
                                    suggestion.replace(/"/g, '&quot;') + '">' + 
                                    suggestion + '</span>';
                            });
                            suggestionsHtml += '</div>';
                        }
                        if (response.data.followups && response.data.followups.length > 0) {
                            suggestionsHtml += '<div class="cta-ai-suggestions-section">';
                            suggestionsHtml += '<h4>🔄 Follow-up Prompts:</h4>';
                            response.data.followups.forEach(function(followup) {
                                suggestionsHtml += '<span class="cta-ai-followup-item" data-followup="' + 
                                    followup.replace(/"/g, '&quot;') + '">' + 
                                    followup + '</span>';
                            });
                            suggestionsHtml += '</div>';
                        }
                        
                        if (suggestionsHtml) {
                            $('#cta-ai-course-suggestions').html(suggestionsHtml).show();
                        }
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    btn.prop('disabled', false);
                    var labels = {
                        description: '📝 Generate Course Description',
                        suitable_for: '👥 Generate "Who Should Attend"',
                        prerequisites: '✅ Generate Prerequisites',
                        outcomes: '🎯 Generate Learning Outcomes'
                    };
                    btn.text(labels[action]);
                }
            });
        });
        
        $('#cta-ai-course-insert').on('click', function() {
            if (generatedContent && targetField) {
                // Find ACF field and insert content
                var $field = $('[name="acf[' + targetField.replace('course_', 'field_course_') + ']"], [name="' + targetField + '"], textarea[name*="' + targetField + '"]');
                
                if ($field.length) {
                    if ($field.is('textarea')) {
                        $field.val(generatedContent);
                    } else if ($field.closest('.acf-field').find('textarea').length) {
                        $field.closest('.acf-field').find('textarea').val(generatedContent);
                    } else if ($field.closest('.acf-field').find('.wp-editor-area').length) {
                        if (typeof tinymce !== 'undefined' && tinymce.get($field.closest('.acf-field').find('.wp-editor-area').attr('id'))) {
                            tinymce.get($field.closest('.acf-field').find('.wp-editor-area').attr('id')).setContent(generatedContent);
                        } else {
                            $field.closest('.acf-field').find('.wp-editor-area').val(generatedContent);
                        }
                    }
                    
                    // Trigger ACF change event
                    $field.trigger('change');
                    
                    $('#cta-ai-course-output').removeClass('visible');
                    $(this).hide();
                    alert('✅ Content inserted!');
                } else {
                    alert('Could not find the target field. Please insert manually.');
                }
            }
        });
    });
    </script>
    <?php
}

/**
 * AI Event Assistant meta box content
 */
function ccs_ai_event_metabox_callback($post) {
    $api_key = get_option('ccs_ai_api_key');
    
    if (empty($api_key)) {
        ?>
        <div class="cta-ai-setup-notice">
            <p><strong>Setup Required</strong></p>
            <p>
                <a href="<?php echo admin_url('options-general.php?page=cta-ai-settings'); ?>">Add your API key</a> to enable AI features.
            </p>
        </div>
        <?php
        return;
    }
    
    wp_nonce_field('ccs_ai_nonce', 'ccs_ai_nonce_field');
    
    // Get current event data
    $event_title = $post->post_title;
    $linked_course = function_exists('get_field') ? get_field('linked_course', $post->ID) : null;
    $course_title = $linked_course ? (is_object($linked_course) ? $linked_course->post_title : get_the_title($linked_course)) : '';
    $event_date = function_exists('get_field') ? get_field('event_date', $post->ID) : '';
    $event_location = function_exists('get_field') ? get_field('event_location', $post->ID) : '';
    ?>
    <style>
        .cta-ai-event-section {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f1;
        }
        .cta-ai-event-section:last-child {
            border-bottom: none;
        }
        .cta-ai-event-btn {
            width: 100%;
            padding: 8px 12px;
            margin-bottom: 6px;
            font-size: 12px;
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }
        .cta-ai-event-btn:hover {
            background: #f6f7f7;
            border-color: #2271b1;
        }
        .cta-ai-event-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .cta-ai-event-output {
            background: #f6f7f7;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 10px;
            font-size: 12px;
            max-height: 150px;
            overflow-y: auto;
            display: none;
            margin-top: 8px;
        }
        .cta-ai-event-output.visible {
            display: block;
        }
        .cta-ai-event-insert {
            margin-top: 6px;
            padding: 6px 10px;
            font-size: 11px;
            background: #00a32a;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            display: none;
        }
        .cta-ai-event-insert:hover {
            background: #008a20;
        }
        .cta-ai-event-suggestions {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #c3c4c7;
        }
        .cta-ai-event-suggestions h4 {
            font-size: 12px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: #1d2327;
        }
        .cta-ai-event-suggestions-section {
            margin-bottom: 12px;
        }
    </style>
    
    <div class="cta-ai-event-section">
        <p style="font-size: 11px; color: #646970; margin: 0 0 8px 0;">
            <strong>Event:</strong> <?php echo esc_html($event_title); ?><br>
            <?php if ($course_title) : ?><strong>Course:</strong> <?php echo esc_html($course_title); ?><br><?php endif; ?>
            <?php if ($event_date) : ?><strong>Date:</strong> <?php echo esc_html($event_date); ?><br><?php endif; ?>
            <?php if ($event_location) : ?><strong>Location:</strong> <?php echo esc_html($event_location); ?><?php endif; ?>
        </p>
    </div>
    
    <div class="cta-ai-event-section">
        <button type="button" class="cta-ai-event-btn" data-action="description" data-field="event_description">
            📝 Generate Event Description
        </button>
        <button type="button" class="cta-ai-event-btn" data-action="instructions" data-field="event_instructions">
            📋 Generate Attendee Instructions
        </button>
    </div>
    
    <div id="cta-ai-event-output" class="cta-ai-event-output"></div>
    <div id="cta-ai-event-suggestions" class="cta-ai-event-suggestions" style="display: none;"></div>
    <button type="button" id="cta-ai-event-insert" class="cta-ai-event-insert">Insert into Field</button>
    
    <script>
    jQuery(document).ready(function($) {
        var generatedContent = '';
        var targetField = '';
        
        $('.cta-ai-event-btn').on('click', function() {
            var btn = $(this);
            var action = btn.data('action');
            targetField = btn.data('field');
            
            btn.prop('disabled', true).text('Generating...');
            
            var eventData = {
                title: '<?php echo esc_js($event_title); ?>',
                course: '<?php echo esc_js($course_title); ?>',
                date: '<?php echo esc_js($event_date); ?>',
                location: '<?php echo esc_js($event_location); ?>'
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_ai_generate_event',
                    nonce: $('#ccs_ai_nonce_field').val(),
                    type: action,
                    event_data: eventData
                },
                success: function(response) {
                    if (response.success) {
                        generatedContent = response.data.content;
                        $('#cta-ai-event-output').html(response.data.content.replace(/\n/g, '<br>')).addClass('visible');
                        $('#cta-ai-event-insert').show();
                        
                        // Display suggestions and follow-ups
                        var suggestionsHtml = '';
                        if (response.data.suggestions && response.data.suggestions.length > 0) {
                            suggestionsHtml += '<div class="cta-ai-event-suggestions-section">';
                            suggestionsHtml += '<h4>💡 Suggested Next Steps:</h4>';
                            response.data.suggestions.forEach(function(suggestion) {
                                suggestionsHtml += '<span class="cta-ai-suggestion-item" data-suggestion="' + 
                                    suggestion.replace(/"/g, '&quot;') + '">' + 
                                    suggestion + '</span>';
                            });
                            suggestionsHtml += '</div>';
                        }
                        if (response.data.followups && response.data.followups.length > 0) {
                            suggestionsHtml += '<div class="cta-ai-event-suggestions-section">';
                            suggestionsHtml += '<h4>🔄 Follow-up Prompts:</h4>';
                            response.data.followups.forEach(function(followup) {
                                suggestionsHtml += '<span class="cta-ai-followup-item" data-followup="' + 
                                    followup.replace(/"/g, '&quot;') + '">' + 
                                    followup + '</span>';
                            });
                            suggestionsHtml += '</div>';
                        }
                        
                        if (suggestionsHtml) {
                            $('#cta-ai-event-suggestions').html(suggestionsHtml).show();
                        }
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    btn.prop('disabled', false);
                    var labels = {
                        description: '📝 Generate Event Description',
                        instructions: '📋 Generate Attendee Instructions'
                    };
                    btn.text(labels[action]);
                }
            });
        });
        
        // Handle event suggestion clicks
        $(document).on('click', '.cta-ai-suggestion-item', function() {
            var suggestion = $(this).data('suggestion');
            var actionMap = {
                'description': 'description',
                'instructions': 'instructions',
                'attendee instructions': 'instructions'
            };
            
            var matchedAction = null;
            for (var key in actionMap) {
                if (suggestion.toLowerCase().indexOf(key) !== -1) {
                    matchedAction = actionMap[key];
                    break;
                }
            }
            
            if (matchedAction) {
                $('.cta-ai-event-btn[data-action="' + matchedAction + '"]').click();
            } else {
                alert('Suggestion: ' + suggestion + '\n\nClick the appropriate button to generate this content.');
            }
        });
        
        // Handle event follow-up clicks
        $(document).on('click', '.cta-ai-followup-item', function() {
            var followup = $(this).data('followup');
            alert('Follow-up Prompt:\n\n' + followup + '\n\nYou can use this prompt in the AI chat widget or manually refine the content.');
        });
        
        $('#cta-ai-event-insert').on('click', function() {
            if (generatedContent && targetField) {
                var $field = $('[name="acf[' + targetField.replace('event_', 'field_event_') + ']"], [name="' + targetField + '"], textarea[name*="' + targetField + '"]');
                
                if ($field.length) {
                    if ($field.is('textarea')) {
                        $field.val(generatedContent);
                    } else if ($field.closest('.acf-field').find('textarea').length) {
                        $field.closest('.acf-field').find('textarea').val(generatedContent);
                    }
                    $field.trigger('change');
                    $('#cta-ai-event-output').removeClass('visible');
                    $(this).hide();
                    alert('✅ Content inserted!');
                } else {
                    alert('Could not find the target field. Please insert manually.');
                }
            }
        });
    });
    </script>
    <?php
}

/**
 * AJAX handler for AI generation
 */
function ccs_ai_generate_ajax() {
    check_ajax_referer('ccs_ai_nonce', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error('Invalid request', 403);
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }
    
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    
    $type = sanitize_text_field($_POST['type']);
    $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
    $tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'professional';
    
    // Get company knowledge for context
    $company_knowledge = function_exists('ccs_get_ai_company_context') ? ccs_get_ai_company_context() : '';
    
    // Build prompt based on type
    $system_prompt = "ROLE: Expert content writer for Continuity of Care Services (care sector training provider, Kent, UK)

COMPANY CONTEXT:
{$company_knowledge}

OUTPUT LANGUAGE: British English ONLY
- Use: favour, colour, organisation, centre, realise, optimise
- Never use: favor, color, organization, center, realize, optimize
- Check: Every word must be British English spelling

SOURCE POLICY (STRICT - NO EXCEPTIONS):
APPROVED SOURCES ONLY:
1. NHS (National Health Service) - nhs.uk
2. CQC (Care Quality Commission) - cqc.org.uk
3. Skills for Care - skillsforcare.org.uk
4. GOV.UK (government guidance) - gov.uk
5. NICE (National Institute for Health and Care Excellence) - nice.org.uk
6. HSE (Health and Safety Executive) - hse.gov.uk
7. SCIE (Social Care Institute for Excellence) - scie.org.uk

SOURCE RULES:
- If source not in list above → DO NOT reference it
- If unsure → Skip the reference entirely
- Only exception: User explicitly requests different source
- Never invent or make up sources

LINK FORMATTING (CRITICAL):
- Format: [anchor text](url)
- Anchor text: MAXIMUM 3 words
- Good: [CQC guidance](url) ✓
- Bad: [The Care Quality Commission published guidance](url) ✗
- Examples: [NHS recommends](url), [Skills for Care states](url), [GOV.UK guidance](url)

QUALITY STANDARDS:
- E-E-A-T: Demonstrate experience, expertise, authority, trust
- Human-first: Write for readers, not search engines
- Accurate: Verify all claims before writing
- Engaging: Use practical examples and actionable insights
- Professional: Maintain appropriate tone for care sector audience";
    
    switch ($type) {
        case 'article':
            $tone_desc = [
                'professional' => 'professional and informative',
                'friendly' => 'friendly and approachable',
                'authoritative' => 'authoritative and expert'
            ];
            $prompt = "Write a comprehensive blog article about: {$topic}

Tone: {$tone_desc[$tone]}

STEP-BY-STEP PROCESS:
1. Create title (50-60 characters) that includes primary keyword naturally
2. Write opening paragraph (3-4 sentences) that:
   - Includes primary keyword in first or second sentence
   - Hooks the reader with relevance to UK care sector
   - Sets context for the article
3. Write 3-4 main sections (use ## for H2 headings):
   - Each section: 150-200 words
   - Include practical tips or insights
   - Add 1 UK source link per section (MAX 3-word anchor)
   - Use ### for subsections where logical
4. Write conclusion (100-150 words) with:
   - Summary of key points
   - Call-to-action mentioning Continuity of Care Services courses
5. Add 'Sources' section listing all UK source URLs referenced

REQUIRED STRUCTURE:
# [Title - 50-60 chars with primary keyword]

[Opening paragraph with primary keyword in first/second sentence]

## [First Main Section Heading]

[Content with practical insights - 150-200 words]
[UK source link: [3-word anchor](url)]

### [Subsection if needed]

[More detail]

## [Second Main Section]

[Content - 150-200 words]
[UK source link: [3-word anchor](url)]

## [Third Main Section]

[Content - 150-200 words]
[UK source link: [3-word anchor](url)]

## Conclusion

[Summary and CTA to CTA courses]

## Sources

- [List all UK source URLs referenced]

VALIDATION CHECKLIST (verify before outputting):
✓ Word count: 600-800 words total
✓ British English throughout (favour, colour, organisation)
✓ Primary keyword in first paragraph
✓ 2-3 UK source links (MAX 3-word anchors each)
✓ H2/H3 heading hierarchy logical
✓ CTA to CTA courses at end
✓ No non-UK sources referenced
✓ All links formatted as [text](url)
✓ Sources section included

OUTPUT NOW following this exact structure:";
            break;
            
        case 'brief':
            $prompt = "Create a detailed content brief for an article about: {$topic}

OUTPUT FORMAT (follow exactly):
---
1. SUGGESTED TITLE
[50-60 characters, includes primary keyword]

2. SEO META DESCRIPTION
[150-160 characters exactly, compelling, includes keyword]

3. TARGET AUDIENCE
[Who should read this - be specific about UK care sector roles]

4. SEARCH INTENT
[Informational / Commercial / Transactional]

5. KEYWORDS
Primary: [keyword]
Secondary: [5-7 keywords]

6. KEY POINTS TO COVER
- [Point 1]
- [Point 2]
- [Point 3]
- [Point 4]
- [Point 5]

7. SUGGESTED STRUCTURE
## [H2 Heading 1]
### [H3 Subsection if needed]
## [H2 Heading 2]
## [H2 Heading 3]

8. UK SOURCES TO REFERENCE
- NHS: [specific topic page if relevant]
- CQC: [specific guidance if relevant]
- Skills for Care: [relevant resource]
- GOV.UK: [relevant guidance]

9. INTERNAL LINK OPPORTUNITIES
- [Course type 1] - link to relevant CTA course
- [Course type 2] - link to relevant CTA course

10. E-E-A-T SIGNALS
[How to demonstrate expertise and authority]

11. ESTIMATED WORD COUNT
[600-800 words]
---

OUTPUT NOW in this exact format:";
            break;
            
        case 'excerpt':
            $prompt = "Write an SEO-optimised excerpt/meta description for this content.

REQUIREMENTS:
- Exactly 150-160 characters (critical for SERP display)
- Include primary keyword naturally
- Be compelling - appears in Google search results
- Include subtle call-to-action or benefit
- British English

Content to summarise:
{$content}

OUTPUT FORMAT:
[Your excerpt here - 150-160 characters exactly]

VALIDATION:
✓ Character count: 150-160
✓ Includes primary keyword
✓ British English
✓ Compelling and actionable

Return ONLY the excerpt, nothing else:";
            break;
            
        case 'improve':
            $prompt = "Improve this content for clarity, engagement, and professionalism. Keep the same length and meaning, but make it better. Ensure British English spelling throughout:\n\n{$content}";
            break;
            
        case 'shorten':
            $prompt = "Condense this content to about half its length while keeping the key points. Maintain British English:\n\n{$content}";
            break;
            
        case 'expand':
            $prompt = "Expand this content with more detail, examples, and insights. Double the length. Add 1-2 external links to authoritative UK sources (NHS, CQC, Skills for Care) with MAX 3-word anchor text. Use British English:\n\n{$content}";
            break;
            
        case 'seo':
            $prompt = "Optimise this content for SEO while keeping it natural and readable.

Requirements:
- Ensure primary keyword appears in first paragraph
- Add relevant secondary keywords naturally
- Improve headings (H2, H3) for clarity and keywords
- Enhance readability with shorter paragraphs
- Add 1-2 external links to UK authoritative sources (MAX 3-word anchor text)
- Suggest internal linking opportunities (mention as comments)
- Keep British English spelling
- Do NOT keyword stuff - write for humans first

Content:
{$content}";
            break;
            
        case 'links':
            $prompt = "Analyse this content and suggest external links to authoritative UK sources.

For each suggestion, provide:
1. The phrase to link (MAX 3 words)
2. The recommended URL (NHS, CQC, Skills for Care, GOV.UK, NICE, HSE only)
3. Why this source adds credibility

Content:
{$content}

Format as a numbered list.";
            break;
            
        case 'structure':
            $prompt = "Analyse this article content and suggest formatting improvements. Look for:

1. **Headings needed** - Text that should be H2 (main sections) or H3 (subsections)
2. **Lists hiding in paragraphs** - Comma-separated items, numbered sequences, or 'firstly/secondly' patterns that should be bullet or numbered lists
3. **Quotes** - Statements from sources, statistics, or key insights that should be blockquotes
4. **Walls of text** - Long paragraphs that should be broken up
5. **Emphasis needed** - Key terms or important phrases that should be bold

For each suggestion, use this EXACT format:
📌 H2: \"[exact text that should be a main heading]\"
📌 H3: \"[exact text that should be a subheading]\"
📌 LIST: \"[paragraph]\" → Convert to bullet points because [reason]
📌 QUOTE: \"[text]\" → Make this a blockquote
📌 BOLD: \"[term]\" → Emphasise this key term
📌 BREAK: \"[paragraph start]\" → Split this into smaller paragraphs

Be specific - quote the EXACT text so the user can find it easily.
Only suggest changes that would genuinely improve readability.

Content to analyse:
{$content}";
            break;
            
        default:
            wp_send_json_error('Invalid request type');
    }
    
    // Call AI API with fallback: Groq → preferred → remaining
    $result = ccs_ai_try_providers($preferred_provider, function($provider, $api_key) use ($system_prompt, $prompt) {
        if ($provider === 'groq') {
            return function_exists('ccs_call_groq_api') ? ccs_call_groq_api($api_key, $system_prompt, $prompt) : new WP_Error('missing_fn', 'Groq function missing');
        }
        if ($provider === 'anthropic') {
            return function_exists('ccs_call_anthropic_api') ? ccs_call_anthropic_api($api_key, $system_prompt, $prompt) : new WP_Error('missing_fn', 'Anthropic function missing');
        }
        return function_exists('ccs_call_openai_api') ? ccs_call_openai_api($api_key, $system_prompt, $prompt) : new WP_Error('missing_fn', 'OpenAI function missing');
    });
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    // Extract title if it's an article
    $title = '';
    if ($type === 'article') {
        // Try to extract title from first line
        $lines = explode("\n", $result);
        if (!empty($lines[0])) {
            $first_line = trim($lines[0]);
            if (strpos($first_line, '#') === 0) {
                $title = trim(str_replace('#', '', $first_line));
                $result = trim(implode("\n", array_slice($lines, 1)));
            }
        }
    }
    
    wp_send_json_success([
        'content' => $result,
        'title' => $title
    ]);
}
add_action('wp_ajax_ccs_ai_generate', 'ccs_ai_generate_ajax');

/**
 * AJAX handler for course content generation
 */
function ccs_ai_generate_course_ajax() {
    check_ajax_referer('ccs_ai_nonce', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error('Invalid request', 403);
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }
    
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    
    $type = sanitize_text_field($_POST['type']);
    $course_data = isset($_POST['course_data']) ? $_POST['course_data'] : [];
    
    $company_knowledge = function_exists('ccs_get_ai_company_context') ? ccs_get_ai_company_context() : '';
    
    $system_prompt = "ROLE: Expert course content writer for Continuity of Care Services (care sector training provider, Kent, UK)

COMPANY CONTEXT:
{$company_knowledge}

OUTPUT LANGUAGE: British English ONLY
- Use: favour, colour, organisation, centre, realise, optimise
- Never use: favor, color, organization, center, realize, optimize

SOURCE POLICY (STRICT):
APPROVED UK SOURCES ONLY:
1. CQC (Care Quality Commission) - cqc.org.uk
2. Skills for Care - skillsforcare.org.uk
3. NHS (National Health Service) - nhs.uk
4. GOV.UK (government guidance) - gov.uk
5. NICE (National Institute for Health and Care Excellence) - nice.org.uk
6. HSE (Health and Safety Executive) - hse.gov.uk
7. SCIE (Social Care Institute for Excellence) - scie.org.uk

SOURCE RULES:
- If source not in list → DO NOT reference it
- If unsure → Skip reference entirely
- Only exception: User explicitly requests different source

QUALITY STANDARDS:
- Focus on CQC compliance and UK care sector standards
- Professional, clear, and engaging
- Practical, actionable language
- UK-specific context and terminology
- Accurate and current information

OUTPUT FORMAT:
After your main content, provide:
1. SUGGESTION PROMPTS: 2-3 related content pieces that could be generated next (e.g., \"Generate learning outcomes\", \"Generate prerequisites\", \"Generate who should attend\")
2. FOLLOW-UP PROMPTS: 2-3 prompts to refine or expand the content (e.g., \"Make this more specific to residential care settings\", \"Add more detail about CQC compliance\", \"Expand on the practical applications\")

Format these sections as:
---SUGGESTIONS---
[list of suggestion prompts, one per line]

---FOLLOW-UPS---
[list of follow-up prompts, one per line]";
    
    $course_title = sanitize_text_field($course_data['title'] ?? '');
    $course_category = sanitize_text_field($course_data['category'] ?? '');
    $course_level = sanitize_text_field($course_data['level'] ?? '');
    $course_duration = sanitize_text_field($course_data['duration'] ?? '');
    
    switch ($type) {
        case 'description':
            $prompt = "Write a comprehensive course description for: {$course_title}
" . ($course_category ? "Category: {$course_category}\n" : "") . 
($course_level ? "Level: {$course_level}\n" : "") . 
($course_duration ? "Duration: {$course_duration}\n" : "") . "
Requirements:
- 200-300 words
- Engaging introduction explaining what the course covers
- Highlight key benefits and learning outcomes
- Mention CQC compliance and CPD accreditation where relevant
- Professional but approachable tone
- Focus on practical application in UK care settings
- Ensure all information aligns with UK care sector best practices
- End with value proposition for attendees
- If referencing sources, ONLY use UK authoritative sources (CQC, Skills for Care, NHS, GOV.UK, NICE, HSE, SCIE)
- Do NOT reference sources outside this approved list unless explicitly asked";
            break;
            
        case 'suitable_for':
            $prompt = "Write a 'Who Should Attend' section for this course: {$course_title}
" . ($course_category ? "Category: {$course_category}\n" : "") . 
($course_level ? "Level: {$course_level}\n" : "") . "
Requirements:
- 100-150 words
- List specific UK care sector job roles and positions (e.g., Care Workers, Senior Care Assistants, Registered Managers, Domiciliary Care Workers, etc.)
- Mention experience levels (e.g., 'suitable for both new and experienced staff')
- Include UK care settings where applicable (residential care homes, domiciliary care, supported living, day services, etc.)
- Reference UK care sector terminology and job titles
- Be specific about who would benefit most in UK care context
- Use bullet points or short paragraphs
- If referencing sources, ONLY use UK authoritative sources (CQC, Skills for Care, NHS, GOV.UK, NICE, HSE, SCIE)
- Do NOT reference sources outside this approved list unless explicitly asked";
            break;
            
        case 'prerequisites':
            $prompt = "Write prerequisites/requirements for this course: {$course_title}
" . ($course_level ? "Level: {$course_level}\n" : "") . "
Requirements:
- 50-100 words
- List any required UK qualifications, experience, or prior training (e.g., NVQ levels, care certificates, etc.)
- Mention if no prerequisites are needed
- Include any practical requirements (e.g., ability to participate in practical activities)
- Be clear and concise
- Focus on UK care sector context
- If referencing sources, ONLY use UK authoritative sources (CQC, Skills for Care, NHS, GOV.UK, NICE, HSE, SCIE)
- Do NOT reference sources outside this approved list unless explicitly asked";
            break;
            
        case 'outcomes':
            $prompt = "Generate learning outcomes for this course: {$course_title}
" . ($course_category ? "Category: {$course_category}\n" : "") . 
($course_level ? "Level: {$course_level}\n" : "") . "
Requirements:
- Generate 5-8 specific, measurable learning outcomes
- Each outcome should start with an action verb (Understand, Recognise, Apply, Demonstrate, etc.)
- Focus on practical skills and knowledge relevant to UK care sector
- Make them specific to this course topic and UK care context
- Format as one outcome per line (no bullets or numbers)
- Each outcome should be clear and achievable in UK care settings
- Ensure outcomes reflect UK care sector best practices
- If referencing sources, ONLY use UK authoritative sources (CQC, Skills for Care, NHS, GOV.UK, NICE, HSE, SCIE)
- Do NOT reference sources outside this approved list unless explicitly asked";
            break;
            
        default:
            wp_send_json_error('Invalid request type');
    }
    
    $result = ccs_ai_try_providers($preferred_provider, function($provider, $api_key) use ($system_prompt, $prompt) {
        if ($provider === 'groq') return ccs_call_groq_api($api_key, $system_prompt, $prompt);
        if ($provider === 'anthropic') return ccs_call_anthropic_api($api_key, $system_prompt, $prompt);
        return ccs_call_openai_api($api_key, $system_prompt, $prompt);
    });
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    // Parse suggestions and follow-ups from response
    $parsed = ccs_parse_ai_response_with_suggestions($result);
    
    wp_send_json_success([
        'content' => $parsed['content'],
        'suggestions' => $parsed['suggestions'],
        'followups' => $parsed['followups']
    ]);
}
add_action('wp_ajax_ccs_ai_generate_course', 'ccs_ai_generate_course_ajax');

/**
 * AJAX handler for event content generation
 */
function ccs_ai_generate_event_ajax() {
    check_ajax_referer('ccs_ai_nonce', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error('Invalid request', 403);
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }
    
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    
    $type = sanitize_text_field($_POST['type']);
    $event_data = isset($_POST['event_data']) ? $_POST['event_data'] : [];
    
    $company_knowledge = function_exists('ccs_get_ai_company_context') ? ccs_get_ai_company_context() : '';
    
    $system_prompt = "You are an expert event content writer for Continuity of Care Services, a care sector training provider in Kent, UK.

COMPANY KNOWLEDGE:
{$company_knowledge}

CRITICAL RULES:
1. Write in British English (favour, colour, organisation, etc.)
2. Be professional, clear, and helpful
3. Include practical information attendees need
4. Focus on UK care sector context and standards
5. SOURCE RESTRICTION - ABSOLUTELY CRITICAL:
   - When referencing sources, ONLY use these UK authoritative sources:
     * Care Quality Commission (CQC) - cqc.org.uk
     * Skills for Care - skillsforcare.org.uk
     * NHS (National Health Service) - nhs.uk
     * GOV.UK (government guidance) - gov.uk
     * NICE (National Institute for Health and Care Excellence) - nice.org.uk
     * Health and Safety Executive (HSE) - hse.gov.uk
     * Social Care Institute for Excellence (SCIE) - scie.org.uk
   - DO NOT reference sources outside this list unless explicitly asked to do so
   - If you cannot find a relevant source from this list, DO NOT make up alternatives
6. Use UK terminology and care sector language
7. Ensure all information is accurate for UK care settings

OUTPUT FORMAT:
After your main content, provide:
1. SUGGESTION PROMPTS: 2-3 related content pieces that could be generated next (e.g., \"Generate event description\", \"Generate attendee instructions\")
2. FOLLOW-UP PROMPTS: 2-3 prompts to refine or expand the content (e.g., \"Add more detail about parking\", \"Make the tone more welcoming\", \"Include information about refreshments\")

Format these sections as:
---SUGGESTIONS---
[list of suggestion prompts, one per line]

---FOLLOW-UPS---
[list of follow-up prompts, one per line]";
    
    $event_title = sanitize_text_field($event_data['title'] ?? '');
    $course_title = sanitize_text_field($event_data['course'] ?? '');
    $event_date = sanitize_text_field($event_data['date'] ?? '');
    $event_location = sanitize_text_field($event_data['location'] ?? '');
    
    switch ($type) {
        case 'description':
            $prompt = "Write an event description for: {$event_title}
" . ($course_title ? "Course: {$course_title}\n" : "") . 
($event_date ? "Date: {$event_date}\n" : "") . 
($event_location ? "Location: {$event_location}\n" : "") . "
Requirements:
- 150-200 words
- Engaging introduction
- What attendees will learn/experience in UK care sector context
- Practical information about the event
- Professional but welcoming tone
- Focus on UK care sector relevance and application
- If referencing sources, ONLY use UK authoritative sources (CQC, Skills for Care, NHS, GOV.UK, NICE, HSE, SCIE)
- Do NOT reference sources outside this approved list unless explicitly asked";
            break;
            
        case 'instructions':
            $prompt = "Write attendee instructions for this training event: {$event_title}
" . ($event_date ? "Date: {$event_date}\n" : "") . 
($event_location ? "Location: {$event_location}\n" : "") . "
Requirements:
- 100-150 words
- What to bring (if anything)
- Arrival time and registration details
- Parking/transport information if relevant (UK locations)
- Dress code if applicable
- What to expect on the day
- Contact information for queries
- Clear, friendly, and practical
- Use UK date/time formats and terminology
- If referencing sources, ONLY use UK authoritative sources (CQC, Skills for Care, NHS, GOV.UK, NICE, HSE, SCIE)
- Do NOT reference sources outside this approved list unless explicitly asked";
            break;
            
        default:
            wp_send_json_error('Invalid request type');
    }
    
    $result = ccs_ai_try_providers($preferred_provider, function($provider, $api_key) use ($system_prompt, $prompt) {
        if ($provider === 'groq') return ccs_call_groq_api($api_key, $system_prompt, $prompt);
        if ($provider === 'anthropic') return ccs_call_anthropic_api($api_key, $system_prompt, $prompt);
        return ccs_call_openai_api($api_key, $system_prompt, $prompt);
    });
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    // Parse suggestions and follow-ups from response
    $parsed = ccs_parse_ai_response_with_suggestions($result);
    
    wp_send_json_success([
        'content' => $parsed['content'],
        'suggestions' => $parsed['suggestions'],
        'followups' => $parsed['followups']
    ]);
}
add_action('wp_ajax_ccs_ai_generate_event', 'ccs_ai_generate_event_ajax');

/**
 * Parse AI response to extract content, suggestions, and follow-ups
 */
function ccs_parse_ai_response_with_suggestions($response) {
    $content = $response;
    $suggestions = [];
    $followups = [];
    
    // Check for suggestions section
    if (preg_match('/---SUGGESTIONS---\s*\n(.*?)(?:\n---FOLLOW-UPS---|$)/is', $response, $suggestions_match)) {
        $suggestions_text = trim($suggestions_match[1]);
        $suggestions = array_filter(array_map('trim', explode("\n", $suggestions_text)));
        // Remove the suggestions section from content
        $content = preg_replace('/---SUGGESTIONS---.*?---FOLLOW-UPS---.*?$/is', '', $content);
    }
    
    // Check for follow-ups section
    if (preg_match('/---FOLLOW-UPS---\s*\n(.*?)$/is', $response, $followups_match)) {
        $followups_text = trim($followups_match[1]);
        $followups = array_filter(array_map('trim', explode("\n", $followups_text)));
        // Remove the follow-ups section from content
        $content = preg_replace('/---FOLLOW-UPS---.*?$/is', '', $content);
    }
    
    // Clean up content (remove any remaining markers)
    $content = trim(preg_replace('/---(SUGGESTIONS|FOLLOW-UPS)---.*?$/is', '', $content));
    
    return [
        'content' => trim($content),
        'suggestions' => array_values($suggestions),
        'followups' => array_values($followups)
    ];
}

/**
 * Call Anthropic (Claude) API
 */
function ccs_call_anthropic_api($api_key, $system, $prompt) {
    $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01'
        ],
        'body' => json_encode([
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 4096,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]),
        'timeout' => 60
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['error'])) {
        return new WP_Error('api_error', $body['error']['message']);
    }
    
    if (isset($body['content'][0]['text'])) {
        return $body['content'][0]['text'];
    }
    
    return new WP_Error('api_error', 'Unexpected API response');
}

/**
 * Call OpenAI (GPT-4) API
 */
function ccs_call_openai_api($api_key, $system, $prompt) {
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 4096,
            'temperature' => 0.7
        ]),
        'timeout' => 60
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['error'])) {
        return new WP_Error('api_error', $body['error']['message']);
    }
    
    if (isset($body['choices'][0]['message']['content'])) {
        return $body['choices'][0]['message']['content'];
    }
    
    return new WP_Error('api_error', 'Unexpected API response');
}

/**
 * Call Groq API (Free & Fast)
 */
function ccs_call_groq_api($api_key, $system, $prompt) {
    $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode([
            'model' => 'llama-3.3-70b-versatile', // Groq recommended Llama 3.3 70B versatile model
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 4096,
            'temperature' => 0.5,  // Lower for more consistency
            'top_p' => 0.9,        // Focus responses
            'frequency_penalty' => 0.1,  // Reduce repetition
            'presence_penalty' => 0.1    // Encourage topic coverage
        ]),
        'timeout' => 60
    ]);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['error'])) {
        return new WP_Error('api_error', $body['error']['message'] ?? 'Groq API error');
    }
    
    if (isset($body['choices'][0]['message']['content'])) {
        return $body['choices'][0]['message']['content'];
    }
    
    return new WP_Error('api_error', 'Unexpected Groq API response');
}
