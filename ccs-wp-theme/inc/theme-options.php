<?php
/**
 * Theme Options Helper Functions
 * 
 * Now uses Customizer settings instead of ACF PRO Options Pages.
 * ACF PRO is no longer required.
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Get contact info - uses Customizer settings
 * 
 * This function is called throughout the theme to get contact details.
 * It now pulls from the WordPress Customizer instead of ACF Options.
 */
function ccs_get_contact_info_from_options() {
    // Use Customizer-based function if available
    if (function_exists('ccs_get_contact_info_from_customizer')) {
        return ccs_get_contact_info_from_customizer();
    }
    
    // Fallback to hardcoded values
    return [
        'phone' => '01622 587343',
        'phone_link' => 'tel:01622587343',
        'email' => 'enquiries@continuitytrainingacademy.co.uk',
        'address' => [
            'line1' => 'Continuity of Care Services',
            'line2' => 'The Maidstone Studios, New Cut Road',
            'city' => 'Maidstone, Kent',
            'postcode' => 'ME14 5NZ',
        ],
        'social' => [
            'facebook' => 'https://facebook.com/continuitytraining',
            'instagram' => 'https://instagram.com/continuitytrainingacademy',
            'linkedin' => 'https://www.linkedin.com/company/continuitytrainingacademy/',
            'twitter' => '',
        ],
    ];
}

/**
 * Get SEO option value - uses Customizer settings
 * 
 * @param string $key The setting key
 * @param mixed $default Default value if not set
 * @return mixed
 */
function ccs_get_seo_option($key, $default = '') {
    // Use Customizer-based function if available
    if (function_exists('ccs_get_seo_setting')) {
        return ccs_get_seo_setting($key, $default);
    }
    
    // Fallback defaults
    $defaults = [
        'site_description' => 'Professional care sector training in Maidstone, Kent. CQC-compliant, CPD-accredited courses for healthcare professionals.',
        'org_name' => 'Continuity of Care Services',
        'org_description' => 'Professional care sector training provider in Maidstone, Kent. CQC-compliant, CPD-accredited courses since 2020.',
        'founding_date' => '2020',
        'geo_lat' => '51.2795',
        'geo_lng' => '0.5467',
        'google_verification' => '',
        'bing_verification' => '',
        'twitter_handle' => '',
    ];
    
    return $defaults[$key] ?? $default;
}

/**
 * Get general theme option - uses Customizer settings
 * 
 * @param string $key The setting key
 * @param mixed $default Default value if not set
 * @return mixed
 */
function ccs_get_theme_option($key, $default = '') {
    $customizer_keys = [
        'trustpilot_url' => 'ccs_trustpilot_url',
        'trustpilot_rating' => 'ccs_trustpilot_rating',
        'trustpilot_review_count' => 'ccs_trustpilot_review_count',
        'default_course_location' => 'ccs_default_course_location',
        'booking_email' => 'ccs_booking_email',
    ];
    
    if (isset($customizer_keys[$key])) {
        return get_theme_mod($customizer_keys[$key], $default);
    }
    
    return $default;
}

/**
 * Generate Trustpilot stars HTML based on rating
 * 
 * @param string $rating Rating in format "4.6/5" or just "4.6"
 * @return string HTML for stars
 */
function ccs_get_trustpilot_stars($rating) {
    // Parse rating (e.g., "4.6/5" or "4.6" or "5")
    $rating_value = floatval(preg_replace('/[^0-9.]/', '', $rating));
    
    // Clamp between 0 and 5
    $rating_value = max(0, min(5, $rating_value));
    
    $full_stars = floor($rating_value);
    $has_half_star = ($rating_value - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
    
    $stars_html = '';
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        $stars_html .= '<i class="fas fa-star star-icon star-filled trustpilot-star" aria-hidden="true"></i>';
    }
    
    // Half star
    if ($has_half_star) {
        $stars_html .= '<i class="fas fa-star-half-alt star-icon star-partial trustpilot-star" aria-hidden="true"></i>';
    }
    
    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        $stars_html .= '<i class="far fa-star star-icon star-empty trustpilot-star" aria-hidden="true"></i>';
    }
    
    return $stars_html;
}
