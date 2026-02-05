<?php
/**
 * AI-generated image alt text (vision API)
 *
 * Adds "Generate with AI" in attachment details. Uses OpenAI or Anthropic vision
 * to describe the image and produce accessible, SEO-friendly alt text.
 * Requires at least one of: OpenAI API key, Anthropic API key (Groq has no vision).
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * AJAX: Generate alt text from image using vision API
 */
function cta_ajax_generate_ai_alt_text() {
    check_ajax_referer('cta_generate_ai_alt_text', 'nonce');

    if (!current_user_can('upload_files') || !current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }

    $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;
    if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
        wp_send_json_error(['message' => 'Invalid or non-image attachment']);
    }

    $image_url = wp_get_attachment_image_url($attachment_id, 'large');
    if (!$image_url) {
        $image_url = wp_get_attachment_url($attachment_id);
    }
    if (!$image_url) {
        wp_send_json_error(['message' => 'Could not get image URL']);
    }

    // Make URL absolute and available to API (avoid localhost if possible)
    $image_url = set_url_scheme($image_url, is_ssl() ? 'https' : 'http');

    $context = cta_ai_alt_text_context($attachment_id);
    $result = cta_ai_alt_text_call_vision($image_url, $context, $attachment_id);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    $data = is_array($result) ? $result : ['alt' => (string) $result, 'title' => '', 'caption' => '', 'description' => ''];
    wp_send_json_success([
        'title'       => isset($data['title']) ? (string) $data['title'] : '',
        'caption'     => isset($data['caption']) ? (string) $data['caption'] : '',
        'alt'         => isset($data['alt']) ? (string) $data['alt'] : '',
        'description' => isset($data['description']) ? (string) $data['description'] : '',
    ]);
}
add_action('wp_ajax_cta_generate_ai_alt_text', 'cta_ajax_generate_ai_alt_text');

/**
 * Gather context for alt text (parent post, site name, filename)
 */
function cta_ai_alt_text_context($attachment_id) {
    $context = [
        'site_name' => get_bloginfo('name'),
        'filename'  => basename(get_attached_file($attachment_id)),
    ];

    $parent_id = wp_get_post_parent_id($attachment_id);
    if ($parent_id) {
        $parent = get_post($parent_id);
        if ($parent && in_array($parent->post_type, ['post', 'page', 'course', 'course_event'], true)) {
            $context['parent_title'] = $parent->post_title;
            $context['parent_type']  = $parent->post_type;
        }
    }

    return $context;
}

/**
 * Call vision-capable providers (OpenAI, Anthropic). Groq has no vision.
 * Returns array with keys: title, caption, alt, description.
 */
function cta_ai_alt_text_call_vision($image_url, array $context, $attachment_id = 0) {
    $system = cta_ai_alt_text_system_prompt($context);
    $user_text = cta_ai_alt_text_user_prompt();

    $providers = cta_ai_get_attemptable_providers('openai');
    $vision_providers = array_intersect($providers, ['openai', 'anthropic']);
    if (empty($vision_providers)) {
        return new WP_Error(
            'ai_alt_no_vision',
            'No vision-capable API key configured. Add an OpenAI or Anthropic key in Settings → AI Assistant.'
        );
    }

    $image_b64 = null;
    $mime_type = 'image/jpeg';
    if ($attachment_id && file_exists(get_attached_file($attachment_id))) {
        $path = get_attached_file($attachment_id);
        $image_b64 = @base64_encode(file_get_contents($path));
        $mime_type = get_post_mime_type($attachment_id) ?: $mime_type;
    }

    foreach ($vision_providers as $provider) {
        $key = cta_ai_get_api_key_for_provider($provider);
        if ($provider === 'openai') {
            $out = cta_ai_alt_text_openai_vision($key, $system, $user_text, $image_url);
        } else {
            if (empty($image_b64)) {
                continue;
            }
            $out = cta_ai_alt_text_anthropic_vision($key, $system, $user_text, $image_b64, $mime_type);
        }
        if (is_wp_error($out)) {
            continue;
        }
        $parsed = cta_ai_alt_text_parse_response($out);
        if (is_array($parsed) && (trim($parsed['alt'] ?? '') !== '' || trim($parsed['title'] ?? '') !== '')) {
            return $parsed;
        }
    }

    return new WP_Error(
        'ai_alt_failed',
        'Could not generate content. Check your API keys and try again.'
    );
}

/**
 * Parse JSON response from vision API into title, caption, alt, description.
 */
function cta_ai_alt_text_parse_response($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') {
        return [];
    }
    $stripped = preg_replace('/^```\s*json?\s*|\s*```\s*$/i', '', $raw);
    $decoded = json_decode($stripped, true);
    if (is_array($decoded)) {
        return [
            'title'       => isset($decoded['title']) ? (string) $decoded['title'] : '',
            'caption'     => isset($decoded['caption']) ? (string) $decoded['caption'] : '',
            'alt'         => isset($decoded['alt']) ? (string) $decoded['alt'] : '',
            'description' => isset($decoded['description']) ? (string) $decoded['description'] : '',
        ];
    }
    return ['title' => '', 'caption' => '', 'alt' => $raw, 'description' => ''];
}

/**
 * User prompt for all four WordPress media fields.
 * References W3C alt decision tree and WordPress roles: Title (search), Caption (visible), Alt (accessibility), Description (optional).
 */
function cta_ai_alt_text_user_prompt() {
    return 'Look at this image and output a single JSON object with exactly these keys (use empty string "" when a field should be left blank): '
        . '"title", "caption", "alt", "description". '
        . 'Rules: '
        . '**title**: Short, searchable label for the Media Library (e.g. "Trainer at whiteboard in workshop"). '
        . '**caption**: Optional visible text that can appear under the image on the page; can be one short sentence or empty. '
        . '**alt**: Alternative text for accessibility (screen readers and when image does not load). Follow W3C guidance: use "" (empty string) if the image is purely decorative; for informative images use a brief description of what the image conveys; for images of text, use that text; for functional images (e.g. in a link/button) describe the function. Keep under ~125 characters. No "Image of..." prefix. '
        . '**description**: Optional longer description; leave "" for simple images. '
        . 'Output only valid JSON, no markdown or explanation.';
}

function cta_ai_alt_text_system_prompt(array $context) {
    $parts = [
        'You generate WordPress media metadata (title, caption, alt text, description) for a UK care sector training website.',
        'Site: ' . (isset($context['site_name']) ? $context['site_name'] : 'Continuity Training Academy') . '.',
        'Use natural language; weave in relevant terms (e.g. training, workshop, presentation, care, CQC) only when they fit. No keyword stuffing.',
    ];
    if (!empty($context['parent_title'])) {
        $parts[] = 'This image may be used in: ' . $context['parent_title'] . '.';
    }
    return implode(' ', $parts);
}

/**
 * OpenAI vision (gpt-4o): image_url in user content
 */
function cta_ai_alt_text_openai_vision($api_key, $system, $user_text, $image_url) {
    $body = [
        'model'       => 'gpt-4o',
        'max_tokens'  => 400,
        'temperature' => 0.4,
        'messages'    => [
            ['role' => 'system', 'content' => $system],
            [
                'role'    => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $user_text],
                    ['type' => 'image_url', 'image_url' => ['url' => $image_url]],
                ],
            ],
        ],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body'    => json_encode($body),
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code >= 400 && isset($body['error']['message'])) {
        return new WP_Error('openai_vision', $body['error']['message']);
    }

    if (isset($body['choices'][0]['message']['content'])) {
        return trim($body['choices'][0]['message']['content']);
    }

    return new WP_Error('openai_vision', 'Unexpected OpenAI response');
}

/**
 * Anthropic vision (Claude): image as base64 content block
 */
function cta_ai_alt_text_anthropic_vision($api_key, $system, $user_text, $image_data, $mime_type = 'image/jpeg') {
    $content = [['type' => 'text', 'text' => $user_text]];

    if (is_string($image_data) && strpos($image_data, 'data:') === 0) {
        $image_data = preg_replace('#^data:image/\w+;base64,#', '', $image_data);
    }
    if (is_string($image_data) && strlen($image_data) > 100 && base64_decode($image_data, true) !== false) {
        $content[] = [
            'type'   => 'image',
            'source' => [
                'type'       => 'base64',
                'media_type' => in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true) ? $mime_type : 'image/jpeg',
                'data'       => $image_data,
            ],
        ];
    }

    $body = [
        'model'      => 'claude-3-5-sonnet-20241022',
        'max_tokens' => 400,
        'system'     => $system,
        'messages'   => [
            ['role' => 'user', 'content' => $content],
        ],
    ];

    $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'headers' => [
            'Content-Type'        => 'application/json',
            'x-api-key'           => $api_key,
            'anthropic-version'   => '2023-06-01',
        ],
        'body'    => json_encode($body),
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $res_body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code >= 400 && isset($res_body['error']['message'])) {
        return new WP_Error('anthropic_vision', $res_body['error']['message']);
    }

    if (isset($res_body['content'][0]['text'])) {
        return trim($res_body['content'][0]['text']);
    }

    return new WP_Error('anthropic_vision', 'Unexpected Anthropic response');
}

/**
 * Enqueue script for "Generate with AI" in attachment details (Media Library + media modal)
 */
function cta_ai_alt_text_enqueue_scripts($hook) {
    $load = ($hook === 'upload.php')
        || ($hook === 'post.php')
        || ($hook === 'post-new.php');

    if (!$load) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'cta-ai-alt-text',
        get_template_directory_uri() . '/assets/js/ai-alt-text.js',
        ['jquery'],
        defined('CTA_THEME_VERSION') ? CTA_THEME_VERSION : '1.0',
        true
    );
    wp_localize_script('cta-ai-alt-text', 'ctaAiAltText', [
        'nonce'   => wp_create_nonce('cta_generate_ai_alt_text'),
        'ajaxurl' => admin_url('admin-ajax.php'),
        'label'   => __('Generate with AI', 'cta-theme'),
        'loading' => __('Generating…', 'cta-theme'),
        'error'   => __('Could not generate. Check Settings → AI Assistant for API keys.', 'cta-theme'),
    ]);
}
add_action('admin_enqueue_scripts', 'cta_ai_alt_text_enqueue_scripts', 20);
