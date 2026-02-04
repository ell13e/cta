<?php
/**
 * Continuity Training Academy Theme Functions
 *
 * @package CTA_Theme
 * @version 1.0.0
 * 
 * Note: get_field() and other ACF functions are provided by Advanced Custom Fields plugin
 * @phpstan-ignore get_field
 */

defined('ABSPATH') || exit;

/**
 * Hardening: require a WordPress login for wp-admin.
 *
 * WordPress already redirects unauthenticated users to wp-login.php for most wp-admin
 * requests; this is an explicit safety net in case any custom routing/plugins alter that.
 *
 * Note: We intentionally allow unauthenticated access to admin-ajax.php because the
 * theme uses `wp_ajax_nopriv_*` for public forms/search.
 */
function cta_require_login_for_admin_area() {
    if (!is_admin()) {
        return;
    }

    // @phpstan-ignore-next-line - WP_CLI constant defined by WordPress CLI
    if (defined('WP_CLI') && WP_CLI) {
        return;
    }

    // Allow public AJAX endpoints used by the frontend.
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    if (is_user_logged_in()) {
        return;
    }

    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if (stripos($uri, '/wp-admin/') === false) {
        return;
    }

    auth_redirect();
}
add_action('init', 'cta_require_login_for_admin_area', 0);

/**
 * Theme Constants
 */
define('CTA_THEME_VERSION', '1.0.0');
define('CTA_THEME_DIR', get_template_directory());
define('CTA_THEME_URI', get_template_directory_uri());

/**
 * Composer Autoloader
 * 
 * Load PSR-4 autoloader for modern class-based architecture
 */
$autoloader = CTA_THEME_DIR . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

/**
 * Include theme files
 */
require_once CTA_THEME_DIR . '/inc/theme-setup.php';
require_once CTA_THEME_DIR . '/inc/nav-walkers.php';
require_once CTA_THEME_DIR . '/inc/post-types.php';
require_once CTA_THEME_DIR . '/inc/customizer.php';
require_once CTA_THEME_DIR . '/inc/acf-fields.php';
require_once CTA_THEME_DIR . '/inc/theme-options.php';
require_once CTA_THEME_DIR . '/inc/admin.php';
// ARCHIVED: Custom admin styling files moved to `inc.backup/`.
// These files were disabled in favor of vanilla WordPress admin.
require_once CTA_THEME_DIR . '/inc/api-keys-settings.php';
require_once CTA_THEME_DIR . '/inc/ai-provider-fallback.php';
require_once CTA_THEME_DIR . '/inc/ai-content-assistant.php';
require_once CTA_THEME_DIR . '/inc/ai-course-assistant.php';
require_once CTA_THEME_DIR . '/inc/smart-internal-linker.php';
require_once CTA_THEME_DIR . '/inc/ai-chat-widget.php';
require_once CTA_THEME_DIR . '/inc/seo.php';
require_once CTA_THEME_DIR . '/inc/seo-schema.php';
require_once CTA_THEME_DIR . '/inc/seo-image-sitemap.php';
require_once CTA_THEME_DIR . '/inc/seo-verification.php';
require_once CTA_THEME_DIR . '/inc/page-editor-enhancements.php';
require_once CTA_THEME_DIR . '/inc/seo-links-redirects.php';
require_once CTA_THEME_DIR . '/inc/seo-global-settings.php';
require_once CTA_THEME_DIR . '/inc/facebook-conversions-api.php';
require_once CTA_THEME_DIR . '/inc/facebook-lead-ads-webhook.php';
require_once CTA_THEME_DIR . '/inc/performance-helpers.php';
require_once CTA_THEME_DIR . '/inc/content-templates.php';
require_once CTA_THEME_DIR . '/inc/cache-helpers.php';
require_once CTA_THEME_DIR . '/inc/create-phase1-posts.php';
require_once CTA_THEME_DIR . '/inc/block-patterns.php';
require_once CTA_THEME_DIR . '/inc/ajax-handlers.php';
require_once CTA_THEME_DIR . '/inc/form-submissions-admin.php';
require_once CTA_THEME_DIR . '/inc/discount-codes.php';
require_once CTA_THEME_DIR . '/inc/newsletter-subscribers.php';
require_once CTA_THEME_DIR . '/inc/newsletter-automation.php';
require_once CTA_THEME_DIR . '/inc/auto-populate-articles.php';
require_once CTA_THEME_DIR . '/inc/populate-test-events.php';
require_once CTA_THEME_DIR . '/inc/course-category-limits.php';
require_once CTA_THEME_DIR . '/inc/newsletter-automation-builder.php';
require_once CTA_THEME_DIR . '/inc/event-management-ui.php';
require_once CTA_THEME_DIR . '/inc/data-importer.php';
// Removed: backup system (not needed; avoid creating backup archives under uploads).
require_once CTA_THEME_DIR . '/inc/media-library-folders.php';
require_once CTA_THEME_DIR . '/inc/eventbrite-integration.php';
require_once CTA_THEME_DIR . '/inc/resource-downloads.php';
require_once CTA_THEME_DIR . '/inc/resource-email-delivery.php';
require_once CTA_THEME_DIR . '/inc/resource-ajax-handlers.php';
require_once CTA_THEME_DIR . '/inc/resource-admin-page.php';
require_once CTA_THEME_DIR . '/inc/coming-soon.php';

/**
 * Enqueue styles and scripts
 */
function cta_enqueue_assets() {
    wp_enqueue_style(
        'cta-google-fonts',
        'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;300;400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&family=Lora:wght@400;500;600;700&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
        [],
        '6.5.0'
    );

    wp_enqueue_style(
        'cta-main',
        CTA_THEME_URI . '/assets/css/main.css',
        ['cta-google-fonts', 'font-awesome'],
        CTA_THEME_VERSION
    );

    // Unified accordion system (used across the site)
    wp_enqueue_style(
        'cta-accordion',
        CTA_THEME_URI . '/assets/css/accordion.css',
        ['cta-main'],
        CTA_THEME_VERSION
    );
    
    $recaptcha_site_key = function_exists('cta_get_recaptcha_site_key') ? cta_get_recaptcha_site_key() : get_theme_mod('cta_recaptcha_site_key', '');
    if (!empty($recaptcha_site_key)) {
        wp_add_inline_style('cta-main', '
            .cta-centered-recaptcha-wrapper,
            .contact-form-recaptcha-wrapper,
            .booking-form-recaptcha-wrapper {
                margin: 1.5rem 0;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }
            .cta-centered-recaptcha-wrapper .g-recaptcha,
            .contact-form-recaptcha-wrapper .g-recaptcha,
            .booking-form-recaptcha-wrapper .g-recaptcha {
                margin-bottom: 0.5rem;
            }
            @media (max-width: 640px) {
                .cta-centered-recaptcha-wrapper .g-recaptcha,
                .contact-form-recaptcha-wrapper .g-recaptcha,
                .booking-form-recaptcha-wrapper .g-recaptcha {
                    transform: scale(0.85);
                    transform-origin: 0 0;
                }
            }
        ');
    }

    wp_enqueue_script(
        'cta-main',
        CTA_THEME_URI . '/assets/js/main.js',
        [],
        CTA_THEME_VERSION,
        true
    );

    // Unified accordion system (used across the site)
    wp_enqueue_script(
        'cta-accordion',
        CTA_THEME_URI . '/assets/js/accordion.js',
        [],
        CTA_THEME_VERSION,
        true
    );

    // Real-time discount code validation (frontend forms)
    wp_enqueue_script(
        'cta-discount-validation',
        CTA_THEME_URI . '/assets/js/discount-validation.js',
        ['cta-main'],
        CTA_THEME_VERSION,
        true
    );

    // SECURITY: Google Maps API key removed from frontend
    // We now use iframe embeds which don't require an API key
    // The API key setting is kept for potential future server-side use only
    // See: https://developers.google.com/maps/documentation/embed/get-started
    if (is_page_template('page-templates/locations/locations-index.php') || 
        is_page_template('page-templates/locations/location-maidstone.php') ||
        is_page_template('page-templates/locations/location-medway.php') ||
        is_page_template('page-templates/locations/location-canterbury.php') ||
        is_page_template('page-templates/locations/location-ashford.php') ||
        is_page_template('page-templates/locations/location-tunbridge-wells.php')) {
        
        // Enqueue location maps script (uses iframe embeds, no API key needed)
        wp_enqueue_script(
            'cta-location-maps',
            CTA_THEME_URI . '/assets/js/locations/location-maps.js',
            [],
            CTA_THEME_VERSION,
            true
        );
    }

    $site_wide_discount = cta_get_site_wide_discount();
    $site_wide_active = cta_is_site_wide_discount_active();
    
    wp_localize_script('cta-main', 'ctaData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cta_nonce'),
        'themeUrl' => CTA_THEME_URI,
        'homeUrl' => home_url('/'),
        'siteWideDiscount' => [
            'active' => $site_wide_active,
            'percentage' => $site_wide_active ? $site_wide_discount['percentage'] : 0,
            'label' => $site_wide_discount['label'],
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'cta_enqueue_assets');

/**
 * Add defer attribute to selected non-critical scripts
 */
function cta_add_defer_to_scripts($tag, $handle)
{
    $defer_scripts = [
        'cta-discount-validation',
        'cta-thank-you-modal',
        'cta-resource-download',
        'cta-single-post',
        'cta-group-booking',
    ];

    if (in_array($handle, $defer_scripts, true)) {
        return str_replace(' src', ' defer src', $tag);
    }

    return $tag;
}
add_filter('script_loader_tag', 'cta_add_defer_to_scripts', 10, 2);

/**
 * Add resource hints for external assets (fonts, CDNs)
 */
function cta_add_resource_hints()
{
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}
add_action('wp_head', 'cta_add_resource_hints', 1);

/**
 * Enqueue reCAPTCHA script if site key is configured
 */
function cta_enqueue_recaptcha() {
    $site_key = function_exists('cta_get_recaptcha_site_key') ? cta_get_recaptcha_site_key() : get_theme_mod('cta_recaptcha_site_key', '');
    if (!empty($site_key)) {
        // Load reCAPTCHA v3 API (invisible, runs in background)
        wp_enqueue_script(
            'google-recaptcha',
            'https://www.google.com/recaptcha/api.js?render=' . esc_attr($site_key),
            [],
            null,
            false
        );
    }
}
add_action('wp_enqueue_scripts', 'cta_enqueue_recaptcha');

/**
 * DNS prefetch for common external domains
 */
function cta_add_dns_prefetch()
{
    echo '<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">' . "\n";
    echo '<link rel="dns-prefetch" href="//www.google.com">' . "\n";
}
add_action('wp_head', 'cta_add_dns_prefetch', 0);

/**
 * Enqueue page-specific scripts
 */
function cta_enqueue_page_scripts() {
    if (is_front_page()) {
        // Enqueue course data manager for homepage upcoming courses
        wp_enqueue_script(
            'cta-course-data-manager',
            CTA_THEME_URI . '/assets/js/data/course-data-manager.js',
            ['cta-main'],
            CTA_THEME_VERSION,
            true
        );
        
        // Pass theme URL to JavaScript
        wp_localize_script(
            'cta-course-data-manager',
            'ctaThemeData',
            [
                'themeUri' => CTA_THEME_URI,
            ]
        );
        
        wp_enqueue_script(
            'cta-courses-data',
            CTA_THEME_URI . '/assets/js/data/courses-data.js',
            ['cta-course-data-manager'],
            CTA_THEME_VERSION,
            true
        );
        
        wp_enqueue_script(
            'cta-homepage',
            CTA_THEME_URI . '/assets/js/homepage-upcoming-courses.js',
            ['cta-courses-data'],
            CTA_THEME_VERSION,
            true
        );
        wp_enqueue_script(
            'cta-form-validation',
            CTA_THEME_URI . '/assets/js/form-validation.js',
            ['cta-main'],
            CTA_THEME_VERSION,
            true
        );
    }

    if (is_post_type_archive('course')) {
        // Enqueue course data manager first (required dependency)
        wp_enqueue_script(
            'cta-course-data-manager',
            CTA_THEME_URI . '/assets/js/data/course-data-manager.js',
            ['cta-main'],
            CTA_THEME_VERSION,
            true
        );
        
        // Pass theme URL to JavaScript
        wp_localize_script(
            'cta-course-data-manager',
            'ctaThemeData',
            [
                'themeUri' => CTA_THEME_URI,
            ]
        );
        
        // Enqueue course data
        wp_enqueue_script(
            'cta-courses-data',
            CTA_THEME_URI . '/assets/js/data/courses-data.js',
            ['cta-course-data-manager'],
            CTA_THEME_VERSION,
            true
        );
        
        // Enqueue courses.js (depends on CourseDataManager and CourseData)
        wp_enqueue_script(
            'cta-courses',
            CTA_THEME_URI . '/assets/js/courses.js',
            ['cta-courses-data'],
            CTA_THEME_VERSION,
            true
        );
    }

    if (is_page_template('page-templates/page-contact.php')) {
        wp_enqueue_script(
            'cta-contact',
            CTA_THEME_URI . '/assets/js/contact.js',
            ['cta-main'],
            CTA_THEME_VERSION,
            true
        );

        // Thank you modal for contact form submissions
        wp_enqueue_script(
            'cta-thank-you-modal',
            CTA_THEME_URI . '/assets/js/thank-you-modal.js',
            ['cta-main'],
            CTA_THEME_VERSION,
            true
        );
    }

    if (is_singular('post')) {
        wp_enqueue_script(
            'cta-single-post',
            CTA_THEME_URI . '/assets/js/single-post.js',
            ['cta-main'],
            CTA_THEME_VERSION,
            true
        );
    }

    if (is_page_template('page-templates/page-group-training.php')) {
        wp_enqueue_script(
            'cta-group-booking',
            CTA_THEME_URI . '/assets/js/group-booking.js',
            ['cta-main'],
            CTA_THEME_VERSION,
            true
        );

        // Thank you modal for group training enquiries
        wp_enqueue_script(
            'cta-thank-you-modal',
            CTA_THEME_URI . '/assets/js/thank-you-modal.js',
            ['cta-main'],
            CTA_THEME_VERSION,
            true
        );
    }

    // CQC hub page: load dedicated styles and scripts
    if (is_page_template('page-templates/page-cqc-hub.php')) {
        wp_enqueue_style(
            'cta-cqc-requirements',
            CTA_THEME_URI . '/assets/css/cqc-requirements.css',
            ['cta-main'],
            CTA_THEME_VERSION
        );
        
        wp_enqueue_script(
            'cta-cqc-hub',
            CTA_THEME_URI . '/assets/js/cqc-hub.js',
            ['cta-main'],
            CTA_THEME_VERSION,
            true
        );
    }

    // Location pages: load location styles based on template slug
    if (is_page() && strpos((string) get_page_template_slug(), 'locations/location-') !== false) {
        wp_enqueue_style(
            'cta-locations',
            CTA_THEME_URI . '/assets/css/locations.css',
            ['cta-main'],
            CTA_THEME_VERSION
        );
    }

    // Resource download modal: styles + JS (used on Downloadable Resources, CQC Hub, and Training Guides pages)
    if (is_page_template('page-templates/page-downloadable-resources.php') ||
        is_page_template('page-templates/page-cqc-hub.php') ||
        is_page_template('page-templates/page-training-guides.php')) {
        wp_enqueue_style(
            'cta-resource-download-modal',
            CTA_THEME_URI . '/assets/css/resource-download-modal.css',
            ['cta-main'],
            CTA_THEME_VERSION
        );

        wp_enqueue_script(
            'cta-resource-download',
            CTA_THEME_URI . '/assets/js/resource-download.js',
            ['cta-main'],
            CTA_THEME_VERSION,
            true
        );
    }

    // Note: news-article.js is for static site only, not needed for WordPress
    // WordPress renders posts server-side, so this script is not enqueued
}
add_action('wp_enqueue_scripts', 'cta_enqueue_page_scripts');

/**
 * Helper function to get theme asset URL
 */
function cta_asset($path) {
    return CTA_THEME_URI . '/assets/' . ltrim($path, '/');
}

/**
 * Helper function to get theme image URL
 */
function cta_image($path) {
    return cta_asset('img/' . ltrim($path, '/'));
}

/**
 * Get site contact info (centralized)
 * 
 * Tries ACF Theme Options first, falls back to hardcoded values.
 */
function cta_get_contact_info() {
    if (function_exists('cta_get_contact_info_from_options')) {
        $acf_contact = cta_get_contact_info_from_options();
        if (!empty($acf_contact['phone']) || !empty($acf_contact['email'])) {
            return $acf_contact;
        }
    }
    
    return [
        'phone' => '01622 587343',
        'phone_link' => 'tel:01622587343',
        'email' => 'enquiries@continuitytrainingacademy.co.uk',
        'address' => [
            'line1' => 'Continuity Training Academy',
            'line2' => 'The Maidstone Studios, New Cut Road',
            'city' => 'Maidstone, Kent',
            'postcode' => 'ME14 5NZ',
        ],
        'social' => [
            'facebook' => 'https://facebook.com/continuitytraining',
            'instagram' => 'https://instagram.com/continuitytrainingacademy',
            'linkedin' => 'https://www.linkedin.com/company/continuitytrainingacademy/',
        ],
    ];
}

/**
 * Calculate reading time for content
 */
function cta_reading_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200);
    return max(1, $reading_time);
}

/**
 * Get page URL by slug
 */
function cta_page_url($slug) {
    $slug_map = [
        'privacy' => 'privacy-policy',
        'terms' => 'terms-conditions',
        'cookies' => 'cookie-policy',
    ];
    
    $actual_slug = isset($slug_map[$slug]) ? $slug_map[$slug] : $slug;
    
    $page = get_page_by_path($actual_slug);
    if ($page) {
        return get_permalink($page->ID);
    }
    return home_url('/' . $actual_slug . '/');
}


/**
 * Get course categories for navigation
 */
function cta_get_course_categories() {
    return cta_cache_get_or_set('cta_course_categories', function () {
        return [
            [
                'slug' => 'core-care-skills',
                'name' => 'Core Care Skills',
                'description' => 'Essential induction and Care Certificate training.',
                'icon' => 'fa-heart',
            ],
            [
                'slug' => 'communication-workplace-culture',
                'name' => 'Communication & Workplace Culture',
                'description' => 'Dignity, equality, communication and care planning.',
                'icon' => 'fa-users',
            ],
            [
                'slug' => 'nutrition-hygiene',
                'name' => 'Nutrition & Hygiene',
                'description' => 'Food safety, nutrition and hygiene practices.',
                'icon' => 'fa-apple-alt',
            ],
            [
                'slug' => 'emergency-first-aid',
                'name' => 'Emergency & First Aid',
                'description' => 'Workplace, paediatric and basic life support.',
                'icon' => 'fa-first-aid',
            ],
            [
                'slug' => 'safety-compliance',
                'name' => 'Safety & Compliance',
                'description' => 'Workplace safety, safeguarding and moving & handling.',
                'icon' => 'fa-shield-alt',
            ],
            [
                'slug' => 'medication-management',
                'name' => 'Medication Management',
                'description' => 'Medicines management, competency and insulin awareness.',
                'icon' => 'fa-pills',
            ],
            [
                'slug' => 'health-conditions-specialist-care',
                'name' => 'Health Conditions & Specialist Care',
                'description' => 'Dementia, diabetes, epilepsy and specialist health conditions.',
                'icon' => 'fa-stethoscope',
            ],
            [
                'slug' => 'leadership-professional-development',
                'name' => 'Leadership & Professional Development',
                'description' => 'Management, supervision and professional skills.',
                'icon' => 'fa-user-tie',
            ],
            [
                'slug' => 'information-data-management',
                'name' => 'Information & Data Management',
                'description' => 'Data protection, record keeping and information governance.',
                'icon' => 'fa-database',
            ],
        ];
    }, HOUR_IN_SECONDS);
}

/**
 * Ensure permalink structure is set and flush rewrite rules if needed
 * This fixes 404 errors on posts when permalinks aren't configured
 */
function cta_ensure_permalinks() {
    $permalink_structure = get_option('permalink_structure');
    if (empty($permalink_structure)) {
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules(false);
    }
}
add_action('init', 'cta_ensure_permalinks', 1);

/**
 * Fix accessibility page slug if it exists with old slug
 * One-time migration from 'accessibility' to 'accessibility-statement'
 */
function cta_fix_accessibility_page_slug() {
    if (!is_admin()) {
        return;
    }
    
    $old_page = get_page_by_path('accessibility');
    if ($old_page) {
        $new_page = get_page_by_path('accessibility-statement');
        if (!$new_page) {
            wp_update_post([
                'ID' => $old_page->ID,
                'post_name' => 'accessibility-statement',
            ]);
            // Flush rewrite rules
            flush_rewrite_rules(false);
        }
    }
}
add_action('admin_init', 'cta_fix_accessibility_page_slug');

/**
 * Fix 404s for single posts by serving them directly
 * Handles URLs with /news.html appended or .html extensions
 */
function cta_fix_single_post_404s() {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }
    
    if (is_404()) {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        $path = strtok($request_uri, '?');
        $post_slug = null;
        
        if (preg_match('#^/([^/]+)/news\.html$#', $path, $matches)) {
            $post_slug = $matches[1];
        }
        elseif (preg_match('#^/([^/]+)\.html$#', $path, $matches)) {
            $post_slug = $matches[1];
        }
        
        if ($post_slug) {
            $post = get_page_by_path($post_slug, OBJECT, 'post');
            
            if ($post && $post->post_status === 'publish') {
                global $wp_query;
                
                $wp_query->is_404 = false;
                $wp_query->is_single = true;
                $wp_query->is_singular = true;
                
                $wp_query->queried_object = $post;
                $wp_query->queried_object_id = $post->ID;
                $wp_query->posts = [$post];
                $wp_query->post_count = 1;
                $wp_query->found_posts = 1;
                $wp_query->max_num_pages = 1;
                
                $wp_query->post = $post;
                $GLOBALS['post'] = $post;
                
                $wp_query->is_post_type_archive = false;
                $wp_query->is_archive = false;
                $wp_query->is_home = false;
                
                // Status code should be 200, not 404
                status_header(200);
            }
        }
    }
}
add_action('template_redirect', 'cta_fix_single_post_404s', 1);

/**
 * Populate test form submissions for each submission type on theme activation
 * 
 * Creates one test submission for each form type to help with testing and development
 */
function cta_populate_test_form_submissions() {
    // Safety: only seed when the post type exists (it's registered on `init`)
    if (!post_type_exists('form_submission')) {
        return;
    }

    // Check if test submissions already exist
    $existing_test = get_posts([
        'post_type' => 'form_submission',
        'post_status' => 'any',
        'meta_query' => [
            [
                'key' => '_cta_is_test_submission',
                'value' => '1',
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);
    
    // If test data already exists, skip
    if (!empty($existing_test)) {
        return;
    }
    
    // Ensure form types are created first
    if (function_exists('cta_preintegrate_form_types')) {
        cta_preintegrate_form_types();
    }
    
    // Test data for each form type
    $test_submissions = [
        [
            'form_type' => 'callback-request',
            'name' => 'Test User - Callback',
            'email' => 'test.callback@example.com',
            'phone' => '01622 123456',
            'message' => 'I would like to request a callback to discuss training options for my care home.',
            'consent' => 'yes',
            'marketing_consent' => 'no',
        ],
        [
            'form_type' => 'course-booking',
            'name' => 'Test User - Course Booking',
            'email' => 'test.course@example.com',
            'phone' => '01622 234567',
            'message' => 'I am interested in booking the Adult Social Care Certificate course. Please let me know about available dates.',
            'consent' => 'yes',
            'marketing_consent' => 'no',
            'course_name' => 'Adult Social Care Certificate',
        ],
        [
            'form_type' => 'group-booking',
            'name' => 'Test User - Group Booking',
            'email' => 'test.group@example.com',
            'phone' => '01622 345678',
            'message' => 'We need group training for 15 staff members on Health & Safety. Can you provide on-site training?',
            'consent' => 'yes',
            'marketing_consent' => 'no',
        ],
        [
            'form_type' => 'newsletter',
            'name' => 'Test User - Newsletter',
            'email' => 'test.newsletter@example.com',
            'phone' => '',
            'message' => '',
            'consent' => 'yes',
            'marketing_consent' => 'no',
        ],
        [
            'form_type' => 'general',
            'name' => 'Test User - General',
            'email' => 'test.general@example.com',
            'phone' => '01622 456789',
            'message' => 'I have a general question about your training services and would like more information.',
            'consent' => 'yes',
            'marketing_consent' => 'no',
        ],
        [
            'form_type' => 'schedule-call',
            'name' => 'Test User - Schedule Call',
            'email' => 'test.call@example.com',
            'phone' => '01622 567890',
            'message' => 'I would like to schedule a call to discuss our training requirements in more detail.',
            'consent' => 'yes',
            'marketing_consent' => 'no',
        ],
        [
            'form_type' => 'group-training',
            'name' => 'Test User - Group Training',
            'email' => 'test.grouptraining@example.com',
            'phone' => '01622 678901',
            'message' => 'We are looking for group training solutions for our care facility. Please provide information on available courses and pricing.',
            'consent' => 'yes',
            'marketing_consent' => 'no',
        ],
        [
            'form_type' => 'book-course',
            'name' => 'Test User - Book Course',
            'email' => 'test.bookcourse@example.com',
            'phone' => '01622 789012',
            'message' => 'I would like to book a place on the Emergency First Aid course. What dates are available?',
            'consent' => 'yes',
            'marketing_consent' => 'no',
            'course_name' => 'Emergency First Aid',
        ],
        [
            'form_type' => 'cqc-training',
            'name' => 'Test User - CQC Training',
            'email' => 'test.cqc@example.com',
            'phone' => '01622 890123',
            'message' => 'We need CQC-compliant training for our staff. Can you provide information on your CQC training courses?',
            'consent' => 'yes',
            'marketing_consent' => 'no',
        ],
        [
            'form_type' => 'support',
            'name' => 'Test User - Support',
            'email' => 'test.support@example.com',
            'phone' => '01622 901234',
            'message' => 'I have a question about my course booking and need support with accessing my training materials.',
            'consent' => 'yes',
            'marketing_consent' => 'no',
        ],
    ];
    
    // Create test submissions
    foreach ($test_submissions as $submission_data) {
        $form_type = $submission_data['form_type'];
        unset($submission_data['form_type']);
        
        // Add metadata
        $submission_data['ip'] = '127.0.0.1';
        $submission_data['user_agent'] = 'Test Data Generator';
        $submission_data['page_url'] = home_url('/');
        
        // Save submission (mark email as sent for test data)
        if (function_exists('cta_save_form_submission')) {
            $post_id = cta_save_form_submission($submission_data, $form_type, true, '');
            if (!is_wp_error($post_id) && !empty($post_id)) {
                update_post_meta($post_id, '_cta_is_test_submission', '1');
                update_post_meta($post_id, '_cta_test_form_type', $form_type);
            }
        }
    }
}

/**
 * Mark test submissions to be seeded after the theme is activated.
 * `after_switch_theme` runs before `init` (so CPT/taxonomy may not exist yet).
 */
function cta_mark_test_form_submissions_for_seeding() {
    update_option('cta_seed_test_form_submissions', '1', false);
}
add_action('after_switch_theme', 'cta_mark_test_form_submissions_for_seeding', 30);

/**
 * Seed test submissions once, after CPT/taxonomy registration.
 */
function cta_maybe_seed_test_form_submissions() {
    if (!is_admin()) {
        return;
    }
    if (get_option('cta_seed_test_form_submissions') !== '1') {
        return;
    }
    // Ensure CPT exists (registered on init)
    if (!post_type_exists('form_submission')) {
        return;
    }

    cta_populate_test_form_submissions();
    delete_option('cta_seed_test_form_submissions');
}
add_action('init', 'cta_maybe_seed_test_form_submissions', 99);

/**
 * ============================================
 * AUTOMATIC SCHEMA.ORG STRUCTURED DATA
 * ============================================
 * Automatically adds JSON-LD schema to blog posts and course pages
 * No template changes needed - injected via wp_footer hook
 */

/**
 * Add Article schema to blog posts
 */
function cta_add_article_schema() {
    // Only on single blog posts
    if (!is_singular('post')) {
        return;
    }
    
    global $post;
    
    // Get post data
    $title = get_the_title();
    $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 30);
    $url = get_permalink();
    $date_published = get_the_date('c');
    $date_modified = get_the_modified_date('c');
    $author_name = get_the_author();
    $author_url = get_author_posts_url(get_the_author_meta('ID'));
    
    // Get featured image
    $image = get_the_post_thumbnail_url($post->ID, 'large');
    if (!$image) {
        $image = get_site_icon_url(512);
    }
    
    // Get categories
    $categories = get_the_category();
    $category_names = array();
    if (!empty($categories) && !is_wp_error($categories)) {
        foreach ($categories as $category) {
            $category_names[] = $category->name;
        }
    }
    
    // Build schema
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => esc_html($title),
        'description' => esc_html(wp_strip_all_tags($excerpt)),
        'url' => esc_url($url),
        'datePublished' => $date_published,
        'dateModified' => $date_modified,
        'author' => array(
            '@type' => 'Person',
            'name' => esc_html($author_name),
            'url' => esc_url($author_url)
        ),
        'publisher' => array(
            '@type' => 'EducationalOrganization',
            'name' => 'Continuity Training Academy',
            'url' => esc_url(home_url('/')),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => get_site_icon_url(512)
            )
        ),
        'image' => array(
            '@type' => 'ImageObject',
            'url' => esc_url($image)
        ),
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id' => esc_url($url)
        )
    );
    
    // Add keywords if categories exist
    if (!empty($category_names)) {
        $schema['keywords'] = implode(', ', $category_names);
    }
    
    // Add word count
    $word_count = str_word_count(strip_tags(get_the_content()));
    if ($word_count > 0) {
        $schema['wordCount'] = $word_count;
    }
    
    // Validate required fields before output
    if (empty($schema['headline']) || empty($schema['datePublished'])) {
        return; // Don't output incomplete schema
    }
    
    // Validate date format (ISO 8601)
    $timestamp = strtotime($schema['datePublished']);
    if ($timestamp !== false) {
        $schema['datePublished'] = date('c', $timestamp);
    } else {
        return; // Invalid date
    }
    
    if (isset($schema['dateModified'])) {
        $timestamp = strtotime($schema['dateModified']);
        if ($timestamp !== false) {
            $schema['dateModified'] = date('c', $timestamp);
        }
    }
    
    // Output schema
    echo "\n<!-- Article Schema.org Structured Data -->\n";
    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo "\n" . '</script>' . "\n";
}
add_action('wp_footer', 'cta_add_article_schema', 5);

/**
 * Validate schema data before output
 * 
 * @param string $type Schema type (Course, CourseInstance, Event)
 * @param array $data Schema data array
 * @return array|false Validated data or false if invalid
 */
function cta_validate_schema_data($type, $data) {
    $required = [
        'Course' => ['name', 'description', 'provider'],
        'CourseInstance' => ['name', 'startDate', 'location'],
        'Event' => ['name', 'startDate', 'location', 'offers']
    ];
    
    // Check required fields
    if (isset($required[$type])) {
        foreach ($required[$type] as $field) {
            if (empty($data[$field])) {
                return false; // Don't output incomplete schema
            }
        }
    }
    
    // Validate price format (must be numeric string, no £ symbol)
    if (isset($data['offers']['price'])) {
        $data['offers']['price'] = preg_replace('/[^0-9.]/', '', $data['offers']['price']);
        if (empty($data['offers']['price'])) {
            unset($data['offers']['price']); // Remove if invalid
        }
    }
    
    // Validate date format (ISO 8601)
    if (isset($data['startDate'])) {
        $timestamp = strtotime($data['startDate']);
        if ($timestamp !== false) {
            $data['startDate'] = date('c', $timestamp); // ISO 8601 format
        } else {
            return false; // Invalid date
        }
    }
    
    return $data;
}

/**
 * Add Course schema to course pages
 */
function cta_add_course_schema() {
    // Only on single course pages
    if (!is_singular('course')) {
        return;
    }
    
    global $post;
    
    // Get course data
    $title = get_the_title();
    if (empty($title)) {
        return; // Must have title
    }
    
    $description = has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 50);
    if (empty($description)) {
        $description = 'CQC-compliant ' . $title . ' training for care workers. CPD-accredited course.';
    }
    $url = get_permalink();
    
    // Get ACF fields (if available)
    $duration = function_exists('get_field') ? get_field('course_duration') : '';
    $price = function_exists('get_field') ? get_field('course_price') : '';
    $course_level = function_exists('get_field') ? get_field('course_level') : '';
    $certification = function_exists('get_field') ? get_field('certification_type') : '';
    $delivery_method = function_exists('get_field') ? get_field('delivery_method') : '';
    
    // Get featured image
    $image = get_the_post_thumbnail_url($post->ID, 'large');
    if (!$image) {
        $image = get_site_icon_url(512);
    }
    if (!$image) {
        $image = get_template_directory_uri() . '/assets/img/logo/long_logo-400w.webp'; // Fallback
    }
    
    // Build base schema
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Course',
        'name' => esc_html($title),
        'description' => esc_html(wp_strip_all_tags($description)),
        'url' => esc_url($url),
        'provider' => array(
            '@type' => 'EducationalOrganization',
            'name' => 'Continuity Training Academy',
            'url' => esc_url(home_url('/')),
            'address' => array(
                '@type' => 'PostalAddress',
                'addressLocality' => 'Maidstone',
                'addressRegion' => 'Kent',
                'addressCountry' => 'GB'
            )
        ),
        'image' => esc_url($image)
    );
    
    // Add course instance if we have duration or delivery method
    if ($duration || $delivery_method) {
        $course_instance = array(
            '@type' => 'CourseInstance'
        );
        
        // Add course mode based on delivery method
        if ($delivery_method) {
            $mode_map = array(
                'classroom' => 'onsite',
                'online' => 'online',
                'blended' => 'blended',
                'on-site' => 'onsite'
            );
            $mode = isset($mode_map[strtolower($delivery_method)]) ? $mode_map[strtolower($delivery_method)] : 'onsite';
            $course_instance['courseMode'] = $mode;
        }
        
        // Add duration (convert to ISO 8601 format if possible)
        if ($duration) {
            // Try to parse duration (e.g., "3 hours", "1 day", "2 days")
            $duration_lower = strtolower($duration);
            if (preg_match('/(\d+)\s*(hour|hr|h)/i', $duration_lower, $matches)) {
                $course_instance['duration'] = 'PT' . $matches[1] . 'H';
            } elseif (preg_match('/(\d+)\s*(day|d)/i', $duration_lower, $matches)) {
                $course_instance['duration'] = 'P' . $matches[1] . 'D';
            } else {
                // Just use the raw value
                $course_instance['duration'] = esc_html($duration);
            }
        }
        
        $schema['hasCourseInstance'] = $course_instance;
    }
    
    // Add price if available (validate format)
    if ($price) {
        // Extract numeric price (remove £ symbol and other non-numeric chars except decimal point)
        $price_numeric = preg_replace('/[^0-9.]/', '', $price);
        if ($price_numeric && is_numeric($price_numeric)) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $price_numeric, // Numeric string, no £ symbol
                'priceCurrency' => 'GBP',
                'availability' => 'https://schema.org/InStock',
                'url' => esc_url($url)
            );
        } else {
            // Fallback: POA (Price on Application)
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => '0', // Use 0 for POA
                'priceCurrency' => 'GBP',
                'availability' => 'https://schema.org/InStock',
                'url' => esc_url($url)
            );
        }
    } else {
        // Default: POA if no price set
        $schema['offers'] = array(
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'GBP',
            'availability' => 'https://schema.org/InStock',
            'url' => esc_url($url)
        );
    }
    
    // Add educational level if available
    if ($course_level) {
        $schema['educationalLevel'] = esc_html($course_level);
    }
    
    // Add educational credential if certification is mentioned
    if ($certification) {
        $schema['educationalCredentialAwarded'] = array(
            '@type' => 'EducationalOccupationalCredential',
            'name' => esc_html($certification),
            'credentialCategory' => 'certificate'
        );
    } else {
        // Default CPD certificate
        $schema['educationalCredentialAwarded'] = array(
            '@type' => 'EducationalOccupationalCredential',
            'name' => 'CPD Accredited Certificate',
            'credentialCategory' => 'certificate'
        );
    }
    
    // Add course prerequisites (common for care training)
    $schema['coursePrerequisites'] = 'None - suitable for all care workers';
    
    // Add audience
    $schema['audience'] = array(
        '@type' => 'EducationalAudience',
        'educationalRole' => 'Care Worker'
    );
    
    // Add in language
    $schema['inLanguage'] = 'en-GB';
    
    // Validate schema before output
    $validated = cta_validate_schema_data('Course', $schema);
    if ($validated === false) {
        return; // Don't output invalid schema
    }
    $schema = $validated;
    
    // Output schema
    echo "\n<!-- Course Schema.org Structured Data -->\n";
    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo "\n" . '</script>' . "\n";
}
add_action('wp_footer', 'cta_add_course_schema', 5);

/**
 * Fallback menu for Resources dropdown
 * Displays default links if menu is not assigned
 */
function cta_resources_fallback_menu() {
    $items = [
        ['title' => 'CQC Compliance Hub', 'slug' => 'cqc-compliance-hub'],
        ['title' => 'Downloadable Resources', 'slug' => 'downloadable-resources'],
        ['title' => 'News & Articles', 'slug' => 'news'],
        ['title' => 'FAQs', 'slug' => 'faqs'],
    ];
    
    echo '<ul class="dropdown-menu-list">';
    foreach ($items as $item) {
        $page = get_page_by_path($item['slug']);
        if ($page) {
            echo '<li><a href="' . esc_url(get_permalink($page)) . '" class="dropdown-menu-item" role="menuitem">' . esc_html($item['title']) . '</a></li>';
        }
    }
    echo '</ul>';
}

/**
 * Fallback menu for Footer Company column
 * Displays default links if menu is not assigned
 */
function cta_footer_company_fallback_menu() {
    $items = [
        ['title' => 'About', 'slug' => 'about'],
        ['title' => 'Courses', 'url' => get_post_type_archive_link('course') ?: home_url('/courses/')],
        ['title' => 'Upcoming Courses', 'url' => get_post_type_archive_link('course_event') ?: home_url('/upcoming-courses/')],
        ['title' => 'Group Training', 'slug' => 'group-training'],
        ['title' => 'CQC Compliance Hub', 'slug' => 'cqc-compliance-hub'],
        ['title' => 'Training Guides', 'slug' => 'training-guides'],
        ['title' => 'Downloadable Resources', 'slug' => 'downloadable-resources'],
        ['title' => 'News', 'page_id' => get_option('page_for_posts')],
    ];
    
    echo '<ul class="footer-modern-links">';
    foreach ($items as $item) {
        $url = '';
        if (isset($item['url'])) {
            $url = $item['url'];
        } elseif (isset($item['slug'])) {
            $page = get_page_by_path($item['slug']);
            $url = $page ? get_permalink($page) : '';
        } elseif (isset($item['page_id']) && $item['page_id']) {
            $url = get_permalink($item['page_id']);
        }
        
        if ($url) {
            echo '<li><a href="' . esc_url($url) . '" class="footer-modern-link">' . esc_html($item['title']) . '</a></li>';
        }
    }
    echo '</ul>';
}

/**
 * Fallback menu for Footer Help column
 * Displays default links if menu is not assigned
 */
function cta_footer_help_fallback_menu() {
    $items = [
        ['title' => 'Customer Support', 'slug' => 'contact'],
        ['title' => 'FAQs', 'slug' => 'faqs'],
        ['title' => 'Terms & Conditions', 'slug' => 'terms-conditions'],
        ['title' => 'Privacy Policy', 'slug' => 'privacy-policy'],
        ['title' => 'Cookie Policy', 'slug' => 'cookie-policy'],
        ['title' => 'Accessibility', 'slug' => 'accessibility-statement', 'fallback' => home_url('/accessibility-statement/')],
    ];
    
    echo '<ul class="footer-modern-links">';
    foreach ($items as $item) {
        $url = '';
        if (isset($item['slug'])) {
            $page = get_page_by_path($item['slug']);
            if (!$page && isset($item['fallback'])) {
                $url = $item['fallback'];
            } else {
                $url = $page ? get_permalink($page) : '';
            }
        }
        
        if ($url) {
            echo '<li><a href="' . esc_url($url) . '" class="footer-modern-link">' . esc_html($item['title']) . '</a></li>';
        }
    }
    echo '</ul>';
}
