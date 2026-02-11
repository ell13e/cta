<?php
/**
 * AI Provider fallback helpers
 *
 * Goal: Always try Groq first, then fall back to other providers if Groq fails.
 * Supports provider-specific keys with backward-compatible global key.
 *
 * @package ccs-theme
 */
defined('ABSPATH') || exit;

/**
 * Providers we support.
 */
function ccs_ai_supported_providers() {
    return ['groq', 'anthropic', 'openai'];
}

/**
 * Always tries Groq first, then preferred (if different), then remaining.
 */
function ccs_ai_provider_fallback_order($preferred = 'groq') {
    $preferred = is_string($preferred) ? strtolower(trim($preferred)) : 'groq';
    if (!in_array($preferred, ccs_ai_supported_providers(), true)) {
        $preferred = 'groq';
    }

    $order = ['groq'];
    if ($preferred !== 'groq') {
        $order[] = $preferred;
    }
    foreach (ccs_ai_supported_providers() as $p) {
        if (!in_array($p, $order, true)) {
            $order[] = $p;
        }
    }
    return $order;
}

/**
 * Provider-specific key option names.
 */
function ccs_ai_provider_key_option($provider) {
    $provider = is_string($provider) ? strtolower(trim($provider)) : '';
    if (!in_array($provider, ccs_ai_supported_providers(), true)) {
        return null;
    }
    return 'ccs_ai_' . $provider . '_api_key';
}

/**
 * Get API key for a provider.
 * - Prefer provider-specific option (ccs_ai_groq_api_key, etc.)
 * - Fall back to legacy global option (ccs_ai_api_key)
 */
function ccs_ai_get_api_key_for_provider($provider) {
    $opt = ccs_ai_provider_key_option($provider);
    $specific = $opt ? trim((string) get_option($opt, '')) : '';
    if (!empty($specific)) {
        return $specific;
    }
    return trim((string) get_option('ccs_ai_api_key', ''));
}

/**
 * Validate that a key "looks like" it belongs to a provider.
 */
function ccs_ai_key_looks_valid($provider, $api_key) {
    $provider = is_string($provider) ? strtolower(trim($provider)) : '';
    $api_key = (string) $api_key;
    if ($api_key === '') {
        return false;
    }

    if ($provider === 'groq') {
        return strpos($api_key, 'gsk_') === 0;
    }
    if ($provider === 'anthropic') {
        return strpos($api_key, 'sk-ant-') === 0;
    }
    if ($provider === 'openai') {
        // OpenAI keys are typically sk- or sk-proj- (both start with sk-).
        return strpos($api_key, 'sk-') === 0;
    }
    return false;
}

/**
 * Return providers we can actually attempt (valid-looking key present).
 */
function ccs_ai_get_attemptable_providers($preferred = 'groq') {
    $providers = ccs_ai_provider_fallback_order($preferred);
    $out = [];
    foreach ($providers as $p) {
        $key = ccs_ai_get_api_key_for_provider($p);
        if (ccs_ai_key_looks_valid($p, $key)) {
            $out[] = $p;
        }
    }
    return $out;
}

/**
 * Try providers in order until one succeeds.
 *
 * The callback is called as: $fn($provider, $api_key)
 * and must return string|array|WP_Error depending on the call-site.
 */
function ccs_ai_try_providers($preferred, callable $fn) {
    $providers = ccs_ai_get_attemptable_providers($preferred);
    if (empty($providers)) {
        return new WP_Error(
            'ai_not_configured',
            'No AI API keys configured. Add your Groq / Claude / OpenAI keys in Settings → AI Assistant.'
        );
    }

    $errors = [];
    foreach ($providers as $provider) {
        $key = ccs_ai_get_api_key_for_provider($provider);
        $result = $fn($provider, $key);

        if (!is_wp_error($result) && $result !== null && $result !== '') {
            return $result;
        }

        $errors[$provider] = is_wp_error($result) ? $result->get_error_message() : 'Empty response';
    }

    $msg = 'AI request failed for all configured providers. ';
    foreach ($errors as $p => $e) {
        $msg .= strtoupper($p) . ': ' . $e . ' ';
    }
    return new WP_Error('ai_all_providers_failed', trim($msg));
}

