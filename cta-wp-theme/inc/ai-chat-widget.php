<?php
/**
 * AI Chat Widget
 * Floating chatbot for content assistance in WordPress admin
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Add the AI Chat Widget to admin footer
 */
function cta_ai_chat_widget() {
    // Show on post and page editing screens
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post') {
        return;
    }
    
    // Allow on pages, posts, courses, and events
    $allowed_types = ['page', 'post', 'course', 'course_event'];
    if (!in_array($screen->post_type, $allowed_types)) {
        return;
    }
    
    $api_key = get_option('cta_ai_api_key');
    if (empty($api_key)) {
        return;
    }
    
    wp_nonce_field('cta_ai_chat_nonce', 'cta_ai_chat_nonce_field');
    ?>
    <style>
        #cta-ai-chat-widget {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        #cta-ai-chat-toggle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            color: white;
            font-size: 24px;
        }
        
        #cta-ai-chat-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 30px rgba(102, 126, 234, 0.5);
        }
        
        #cta-ai-chat-toggle.open {
            transform: rotate(45deg);
        }
        
        #cta-ai-chat-panel {
            position: absolute;
            bottom: 70px;
            right: 0;
            width: 380px;
            height: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
        }
        
        #cta-ai-chat-panel.open {
            display: flex;
        }
        
        .cta-chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .cta-chat-header-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .cta-chat-header-info h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .cta-chat-header-info p {
            margin: 4px 0 0 0;
            font-size: 12px;
            opacity: 0.8;
        }
        
        .cta-chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .cta-chat-message {
            max-width: 85%;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .cta-chat-message.assistant {
            background: #f0f0f1;
            color: #1d2327;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }
        
        .cta-chat-message.user {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        
        .cta-chat-message.typing {
            display: flex;
            gap: 4px;
            padding: 16px;
        }
        
        .cta-chat-message.typing span {
            width: 8px;
            height: 8px;
            background: #8c8f94;
            border-radius: 50%;
            animation: cta-typing 1.4s ease-in-out infinite;
        }
        
        .cta-chat-message.typing span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .cta-chat-message.typing span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes cta-typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-8px); }
        }
        
        .cta-chat-suggestions {
            padding: 12px 16px;
            background: #f9f9f9;
            border-top: 1px solid #f0f0f1;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .cta-chat-suggestion {
            background: white;
            border: 1px solid #c3c4c7;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .cta-chat-suggestion:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .cta-chat-input-area {
            padding: 12px 16px;
            border-top: 1px solid #f0f0f1;
            display: flex;
            gap: 8px;
        }
        
        .cta-chat-input {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid #c3c4c7;
            border-radius: 24px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .cta-chat-input:focus {
            border-color: #667eea;
        }
        
        .cta-chat-send {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .cta-chat-send:hover {
            transform: scale(1.1);
        }
        
        .cta-chat-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .cta-chat-copy-btn {
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            font-size: 11px;
            margin-top: 8px;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .cta-chat-copy-btn:hover {
            background: rgba(102, 126, 234, 0.1);
        }
    </style>
    
    <div id="cta-ai-chat-widget">
        <div id="cta-ai-chat-panel">
            <div class="cta-chat-header">
                <div class="cta-chat-header-icon">🤖</div>
                <div class="cta-chat-header-info">
                    <h3>SEO & Content Assistant</h3>
                    <p>Ask me anything about your <?php echo esc_html($screen->post_type); ?></p>
                </div>
            </div>
            
            <div class="cta-chat-messages" id="cta-chat-messages">
                <div class="cta-chat-message assistant">
                    👋 Hi! I'm your SEO & content assistant. I can help you:
                    <br><br>
                    📊 <strong>SEO Analysis</strong> - Entities, keywords & semantic topics<br>
                    ✨ <strong>SEO Titles</strong> - Optimised 50-60 character titles<br>
                    📋 <strong>Meta Descriptions</strong> - Perfect 120-160 character descriptions<br>
                    🔍 <strong>Keywords</strong> - Primary + secondary keyword suggestions<br>
                    🔗 <strong>Internal Linking</strong> - Suggest relevant pages to link to<br>
                    🎨 <strong>Formatting</strong> - Headings, lists, structure improvements
                    <br><br>
                    Try "SEO Analysis" to get started!
                </div>
            </div>
            
            <div class="cta-chat-suggestions" id="cta-chat-suggestions">
                <button class="cta-chat-suggestion" data-prompt="Analyse my content for SEO - what entities, keywords, and semantic topics should I include? Give me specific suggestions for improving my SEO score.">📊 SEO Analysis</button>
                <button class="cta-chat-suggestion" data-prompt="Write a catchy SEO title (50-60 chars exactly) for my <?php echo esc_js($screen->post_type); ?>">✨ SEO Title</button>
                <button class="cta-chat-suggestion" data-prompt="Write an SEO meta description (120-160 chars exactly) for this <?php echo esc_js($screen->post_type); ?>">📋 Meta Description</button>
                <button class="cta-chat-suggestion" data-prompt="What keywords should I include? Give me 1 primary keyword + 5 secondary keywords for SEO optimization">🔍 Keywords</button>
                <button class="cta-chat-suggestion" data-prompt="What internal pages should I link to from this content? Suggest 3-5 relevant pages with anchor text suggestions.">🔗 Internal Links</button>
                <button class="cta-chat-suggestion" data-prompt="Analyse my content structure and suggest formatting improvements - what should be headings, lists, quotes, etc.">🎨 Fix Formatting</button>
            </div>
            
            <div class="cta-chat-input-area">
                <input type="text" class="cta-chat-input" id="cta-chat-input" placeholder="Ask me anything..." />
                <button class="cta-chat-send" id="cta-chat-send">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </div>
        
        <button id="cta-ai-chat-toggle">
            <span class="dashicons dashicons-format-chat"></span>
        </button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var isOpen = false;
        var conversationHistory = [];
        
        // Toggle chat
        $('#cta-ai-chat-toggle').on('click', function() {
            isOpen = !isOpen;
            $(this).toggleClass('open', isOpen);
            $('#cta-ai-chat-panel').toggleClass('open', isOpen);
            
            if (isOpen) {
                $('#cta-chat-input').focus();
            }
        });
        
        // Send message
        function sendMessage(message) {
            if (!message.trim()) return;
            
            // Add user message
            addMessage(message, 'user');
            $('#cta-chat-input').val('');
            
            // Add to history
            conversationHistory.push({role: 'user', content: message});
            
            // Show typing indicator
            var typingId = addTypingIndicator();
            
            // Get current content for context
            var currentContent = '';
            var currentTitle = $('#title').val() || '';
            
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
                currentContent = tinymce.activeEditor.getContent({format: 'text'});
            } else {
                currentContent = $('#content').val();
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cta_ai_chat',
                    nonce: $('#cta_ai_chat_nonce_field').val(),
                    message: message,
                    history: JSON.stringify(conversationHistory.slice(-6)), // Last 6 messages for context
                    current_title: currentTitle,
                    current_content: currentContent.substring(0, 1000) // First 1000 chars for context
                },
                success: function(response) {
                    removeTypingIndicator(typingId);
                    
                    if (response.success) {
                        var reply = response.data.reply;
                        conversationHistory.push({role: 'assistant', content: reply});
                        addMessage(reply, 'assistant', true);
                    } else {
                        addMessage('Sorry, I encountered an error: ' + response.data, 'assistant');
                    }
                },
                error: function() {
                    removeTypingIndicator(typingId);
                    addMessage('Sorry, I couldn\'t connect. Please try again.', 'assistant');
                }
            });
        }
        
        function addMessage(text, type, showCopy) {
            var messageHtml = '<div class="cta-chat-message ' + type + '">' + 
                text.replace(/\n/g, '<br>');
            
            if (showCopy && type === 'assistant') {
                messageHtml += '<button class="cta-chat-copy-btn" data-text="' + escapeHtml(text) + '">📋 Copy</button>';
                
                // Detect SEO title suggestions (50-60 chars)
                var titleMatch = text.match(/(?:title|headline)[:\s]*["']?([^"'\n]{40,70})["']?/i);
                if (titleMatch && titleMatch[1].length >= 40 && titleMatch[1].length <= 70) {
                    var suggestedTitle = titleMatch[1].trim();
                    messageHtml += '<button class="cta-chat-apply-btn" data-field="meta_title" data-value="' + escapeHtml(suggestedTitle) + '" style="margin-left: 5px; background: #00a32a; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 11px;">✨ Apply as SEO Title</button>';
                }
                
                // Detect meta description suggestions (120-160 chars)
                var descMatch = text.match(/(?:description|excerpt|meta)[:\s]*["']?([^"'\n]{110,170})["']?/i);
                if (descMatch && descMatch[1].length >= 110 && descMatch[1].length <= 170) {
                    var suggestedDesc = descMatch[1].trim();
                    messageHtml += '<button class="cta-chat-apply-btn" data-field="meta_description" data-value="' + escapeHtml(suggestedDesc) + '" style="margin-left: 5px; background: #00a32a; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 11px;">📋 Apply as Meta Description</button>';
                }
                
                // Detect primary keyword suggestions
                var keywordMatch = text.match(/(?:primary|main|focus)\s+keyword[:\s]*["']?([^"'\n]{1,50})["']?/i);
                if (keywordMatch) {
                    var suggestedKeyword = keywordMatch[1].trim();
                    messageHtml += '<button class="cta-chat-apply-btn" data-field="primary_keyword" data-value="' + escapeHtml(suggestedKeyword) + '" style="margin-left: 5px; background: #2271b1; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 11px;">🔍 Apply as Primary Keyword</button>';
                }
            }
            
            messageHtml += '</div>';
            
            $('#cta-chat-messages').append(messageHtml);
            scrollToBottom();
        }
        
        function addTypingIndicator() {
            var id = 'typing-' + Date.now();
            $('#cta-chat-messages').append(
                '<div class="cta-chat-message assistant typing" id="' + id + '">' +
                '<span></span><span></span><span></span>' +
                '</div>'
            );
            scrollToBottom();
            return id;
        }
        
        function removeTypingIndicator(id) {
            $('#' + id).remove();
        }
        
        function scrollToBottom() {
            var container = $('#cta-chat-messages');
            container.scrollTop(container[0].scrollHeight);
        }
        
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/"/g, '&quot;');
        }
        
        // Send on button click
        $('#cta-chat-send').on('click', function() {
            sendMessage($('#cta-chat-input').val());
        });
        
        // Send on Enter
        $('#cta-chat-input').on('keypress', function(e) {
            if (e.which === 13) {
                sendMessage($(this).val());
            }
        });
        
        // Quick suggestions
        $('.cta-chat-suggestion').on('click', function() {
            var prompt = $(this).data('prompt');
            sendMessage(prompt);
        });
        
        // Copy to clipboard
        $(document).on('click', '.cta-chat-copy-btn', function() {
            var text = $(this).data('text');
            navigator.clipboard.writeText(text).then(function() {
                alert('Copied to clipboard!');
            });
        });
        
        // Apply SEO suggestions to meta box
        $(document).on('click', '.cta-chat-apply-btn', function() {
            var field = $(this).data('field');
            var value = $(this).data('value');
            
            switch(field) {
                case 'meta_title':
                    if ($('#page_seo_meta_title').length) {
                        $('#page_seo_meta_title').val(value).trigger('input');
                        $(this).text('✅ Applied!').css('background', '#00a32a').prop('disabled', true);
                    } else {
                        alert('SEO meta box not found. Please ensure the page is saved first.');
                    }
                    break;
                    
                case 'meta_description':
                    if ($('#page_seo_meta_description').length) {
                        $('#page_seo_meta_description').val(value).trigger('input');
                        $(this).text('✅ Applied!').css('background', '#00a32a').prop('disabled', true);
                    } else {
                        alert('SEO meta box not found. Please ensure the page is saved first.');
                    }
                    break;
                    
                case 'primary_keyword':
                    if ($('#page_seo_primary_keyword').length) {
                        $('#page_seo_primary_keyword').val(value).trigger('input');
                        $(this).text('✅ Applied!').css('background', '#00a32a').prop('disabled', true);
                    } else {
                        alert('SEO meta box not found. Please ensure the page is saved first.');
                    }
                    break;
            }
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'cta_ai_chat_widget');

/**
 * AJAX handler for chat messages
 */
function cta_ai_chat_ajax() {
    check_ajax_referer('cta_ai_chat_nonce', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error('Invalid request', 403);
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }
    
    $preferred_provider = get_option('cta_ai_provider', 'groq');
    
    $message = sanitize_textarea_field($_POST['message']);
    $history = json_decode(stripslashes($_POST['history']), true) ?: [];
    $current_title = sanitize_text_field($_POST['current_title']);
    $current_content = sanitize_textarea_field($_POST['current_content']);
    
    // Get current screen for post type context
    $screen = get_current_screen();
    $post_type = $screen ? $screen->post_type : 'post';
    
    // Get company knowledge for context
    $company_knowledge = cta_get_ai_company_context();
    
    // Build context-aware system prompt
    $system_prompt = "ROLE: Helpful content assistant for Continuity Training Academy (care training, CQC compliance, healthcare topics)

COMPANY CONTEXT:
{$company_knowledge}

OUTPUT LANGUAGE: British English ONLY
- Use: favour, colour, organisation, centre, realise, optimise
- Never use: favor, color, organization, center, realize, optimize
- Check: Every word must be British English spelling

SOURCE POLICY (STRICT):
APPROVED UK SOURCES ONLY:
1. NHS (National Health Service) - nhs.uk
2. CQC (Care Quality Commission) - cqc.org.uk
3. Skills for Care - skillsforcare.org.uk
4. GOV.UK (government guidance) - gov.uk
5. NICE (National Institute for Health and Care Excellence) - nice.org.uk
6. HSE (Health and Safety Executive) - hse.gov.uk
7. SCIE (Social Care Institute for Excellence) - scie.org.uk

SOURCE RULES:
- If source not in list → DO NOT suggest it
- If unsure → Say so, do NOT make up alternatives
- Only exception: User explicitly asks for different source

LINK FORMATTING:
- Format: [anchor text](url)
- Anchor text: MAXIMUM 3 words
- Good: [CQC guidance](url) ✓
- Bad: [The Care Quality Commission published guidance](url) ✗

FORMATTING SUGGESTIONS (when asked):
- Use format: 📌 \"[exact text]\" → Should be an H2 heading
- Identify: H2 headings (main sections), H3 headings (subsections)
- Spot: Lists hiding in paragraphs, quotes for blockquotes
- Suggest: Bold for key terms, break up walls of text

SEO GUIDELINES:
- Meta descriptions: exactly 120-160 characters
- Title tags: 50-60 characters with primary keyword
- One H1 per page, logical H2→H3 hierarchy
- Primary keyword in first 10% of content (or first 300 words)
- Keyword density: 1-1.5% optimal, >2.5% penalty
- Content length: 2500+ words = 100% score, 2000-2500 = 70%, 1500-2000 = 60%
- No keyword stuffing - write for humans first
- E-E-A-T: Experience, Expertise, Authoritativeness, Trust
- Semantic entities: Include related concepts, not just keywords
- Internal linking: Link to related pages with descriptive anchor text

CURRENT CONTENT CONTEXT:
- Post Type: " . ($screen->post_type ?? 'post') . "
- Title: " . ($current_title ?: '(not set yet)') . "
- Content preview: " . ($current_content ? substr(strip_tags($current_content), 0, 2000) . '...' : '(empty)') . "
- Word count: " . ($current_content ? str_word_count(strip_tags($current_content)) : 0) . " words

SEO ANALYSIS MODE:
When asked for SEO analysis, provide:
1. Primary keyword suggestion (1-4 words)
2. 5 secondary keywords
3. Semantic entities to include (related concepts, not just keywords)
4. Content gaps (what competitors cover that this doesn't)
5. Internal linking opportunities (suggest 3-5 relevant pages with anchor text)
6. Specific improvements to reach 100/100 SEO score

RESPONSE STYLE:
- Brief but actionable
- Practical, helpful advice
- Use 📌 format for specific suggestions
- Reference real CTA courses when relevant
- Mention team members by name when it adds credibility

REMEMBER: British English, UK sources only, 3-word link anchors - no exceptions unless explicitly requested.";

    // Build messages array
    $messages = [];
    foreach ($history as $msg) {
        if (isset($msg['role']) && isset($msg['content'])) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }
    }
    
    // Add current message if not already in history
    if (empty($messages) || end($messages)['content'] !== $message) {
        $messages[] = ['role' => 'user', 'content' => $message];
    }
    
    // Call AI API with fallback: Groq → preferred → remaining
    $result = cta_ai_try_providers($preferred_provider, function($provider, $api_key) use ($system_prompt, $messages) {
        if ($provider === 'groq') return cta_call_groq_chat($api_key, $system_prompt, $messages);
        if ($provider === 'anthropic') return cta_call_anthropic_chat($api_key, $system_prompt, $messages);
        return cta_call_openai_chat($api_key, $system_prompt, $messages);
    });
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success(['reply' => $result]);
}
add_action('wp_ajax_cta_ai_chat', 'cta_ai_chat_ajax');

/**
 * Call Anthropic API for chat
 */
function cta_call_anthropic_chat($api_key, $system, $messages) {
    $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01'
        ],
        'body' => json_encode([
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 1024,
            'system' => $system,
            'messages' => $messages
        ]),
        'timeout' => 30
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
 * Call OpenAI API for chat
 */
function cta_call_openai_chat($api_key, $system, $messages) {
    // Prepend system message
    array_unshift($messages, ['role' => 'system', 'content' => $system]);
    
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode([
            'model' => 'gpt-4o',
            'messages' => $messages,
            'max_tokens' => 1024,
            'temperature' => 0.7
        ]),
        'timeout' => 30
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
 * Call Groq API for chat
 */
function cta_call_groq_chat($api_key, $system, $messages) {
    // Prepend system message
    array_unshift($messages, ['role' => 'system', 'content' => $system]);
    
    $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode([
            'model' => 'llama-3.3-70b-versatile',
            'messages' => $messages,
            'max_tokens' => 1024,
            'temperature' => 0.5,  // Lower for more consistency
            'top_p' => 0.9,        // Focus responses
            'frequency_penalty' => 0.1,  // Reduce repetition
            'presence_penalty' => 0.1    // Encourage topic coverage
        ]),
        'timeout' => 30
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

/**
 * Get company context for AI
 */
function cta_get_ai_company_context() {
    $context = [];
    
    // Company info
    $context[] = "COMPANY: Continuity Training Academy (CTA)";
    $context[] = "LOCATION: The Maidstone Studios, New Cut Road, Maidstone, Kent ME14 5NZ";
    $context[] = "PHONE: 01622 587343";
    $context[] = "EMAIL: enquiries@continuitytrainingacademy.co.uk";
    $context[] = "FOUNDED: 2020";
    $context[] = "FOCUS: CQC-compliant, CPD-accredited care sector training in Kent and Southeast England";
    
    // Get team members
    $team_members = get_posts([
        'post_type' => 'team_member',
        'posts_per_page' => -1,
    ]);
    
    if (!empty($team_members)) {
        $context[] = "\nTEAM MEMBERS:";
        foreach ($team_members as $member) {
            $role = get_post_meta($member->ID, '_team_role', true);
            if (function_exists('get_field')) {
                $role = get_field('team_role', $member->ID) ?: $role;
            }
            $context[] = "- {$member->post_title} ({$role})";
        }
    }
    
    // Get course categories
    $categories = get_terms([
        'taxonomy' => 'course_category',
        'hide_empty' => false,
    ]);
    
    if (!empty($categories) && !is_wp_error($categories)) {
        $context[] = "\nCOURSE CATEGORIES:";
        foreach ($categories as $cat) {
            $context[] = "- {$cat->name}";
        }
    }
    
    // Get some courses
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => 15,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    
    if (!empty($courses)) {
        $context[] = "\nPOPULAR COURSES (can link to these):";
        foreach ($courses as $course) {
            $context[] = "- {$course->post_title} (/courses/{$course->post_name}/)";
        }
    }
    
    return implode("\n", $context);
}

