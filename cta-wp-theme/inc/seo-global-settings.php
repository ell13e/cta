<?php
/**
 * Global SEO Settings & Title Templates
 * 
 * Implements Rank Math-style global SEO configuration:
 * - Title templates with variables (%title%, %sep%, %sitename%)
 * - Global meta settings (robots, separator)
 * - Multiple keywords strategy (primary vs secondary)
 * - Schema templates
 * 
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * =========================================
 * TITLE TEMPLATES
 * =========================================
 */

/**
 * Get title template for post type
 */
function cta_get_title_template($post_type = 'post') {
    $templates = [
        'post' => '%title%', // Posts: title only (usually has enough characters)
        'page' => '%title% %sep% %sitename%', // Pages: title + separator + sitename
        'course' => '%title% %sep% %sitename%',
        'course_event' => '%title% %sep% %sitename%',
    ];
    
    // Allow override from theme options
    $custom_template = cta_safe_get_field('seo_title_template_' . $post_type, 'option', '');
    if (!empty($custom_template)) {
        return $custom_template;
    }
    
    return $templates[$post_type] ?? '%title% %sep% %sitename%';
}

/**
 * Process title template with variables
 */
function cta_process_title_template($template, $post = null) {
    if (!$post) {
        global $post;
    }
    
    $title = get_the_title($post->ID);
    $sitename = get_bloginfo('name');
    $sep = '–'; // Default separator (dash)
    
    // Allow separator override
    $custom_sep = cta_safe_get_field('seo_title_separator', 'option', '');
    if (!empty($custom_sep)) {
        $sep = $custom_sep;
    }
    
    // Replace variables
    $processed = str_replace('%title%', $title, $template);
    $processed = str_replace('%sitename%', $sitename, $processed);
    $processed = str_replace('%sep%', $sep, $processed);
    
    return $processed;
}

/**
 * Apply title template to document title
 */
function cta_apply_title_template($title_parts) {
    global $post;
    
    if (!is_singular()) {
        return $title_parts;
    }
    
    // Skip if custom meta title is set (takes precedence)
    $custom_title = '';
    if (get_post_type() === 'course') {
        $custom_title = cta_safe_get_field('course_seo_meta_title', get_the_ID(), '');
    } elseif (get_post_type() === 'course_event') {
        $custom_title = cta_safe_get_field('event_seo_meta_title', get_the_ID(), '');
    } elseif (get_post_type() === 'post') {
        $custom_title = cta_safe_get_field('news_meta_title', get_the_ID(), '');
    } elseif (get_post_type() === 'page') {
        $custom_title = cta_safe_get_field('page_seo_meta_title', get_the_ID(), '');
    }
    
    if (!empty($custom_title)) {
        $title_parts['title'] = $custom_title;
        return $title_parts;
    }
    
    // Apply template
    $template = cta_get_title_template(get_post_type());
    $title_parts['title'] = cta_process_title_template($template, $post);
    
    // Ensure max 60 chars
    if (strlen($title_parts['title']) > 60) {
        $title = get_the_title();
        $sitename = get_bloginfo('name');
        $sep = cta_safe_get_field('seo_title_separator', 'option', '–');
        
        // Calculate available space
        $sep_len = strlen($sep) + 2; // separator + spaces
        $sitename_len = strlen($sitename);
        $max_title_len = 60 - $sep_len - $sitename_len;
        
        if ($max_title_len > 0 && strlen($title) > $max_title_len) {
            $title = wp_trim_words($title, 6, '');
        }
        
        $title_parts['title'] = $title . ' ' . $sep . ' ' . $sitename;
    }
    
    return $title_parts;
}
add_filter('document_title_parts', 'cta_apply_title_template', 5);

/**
 * =========================================
 * GLOBAL META SETTINGS
 * =========================================
 */

/**
 * Get global robots meta settings
 */
function cta_get_global_robots_meta() {
    return [
        'index' => true, // Default: Index all content
        'follow' => true,
        'max-snippet' => -1, // Unlimited snippet characters
        'max-image-preview' => 'large', // Large preview images
        'max-video-preview' => -1, // Unlimited video preview seconds
    ];
}

/**
 * Apply global robots meta to all pages
 */
function cta_apply_global_robots_meta($robots) {
    $global = cta_get_global_robots_meta();
    
    // Merge with existing robots meta
    $robots = array_merge($robots, $global);
    
    return $robots;
}
add_filter('wp_robots', 'cta_apply_global_robots_meta', 5);

/**
 * =========================================
 * MULTIPLE KEYWORDS STRATEGY
 * =========================================
 */

/**
 * Test keyword in specific locations (primary keyword only)
 * - SEO title
 * - Meta description
 * - URL slug
 * - First 10% of content
 * - Image alt text
 */
function cta_test_primary_keyword_only($keyword, $post) {
    $tests = [];
    
    if (empty($keyword)) {
        return $tests;
    }
    
    $keyword_lower = strtolower($keyword);
    $title = get_the_title($post->ID);
    $meta_title = cta_safe_get_field('page_seo_meta_title', $post->ID, '') ?: $title;
    $meta_desc = cta_safe_get_field('page_seo_meta_description', $post->ID, '');
    $url_slug = $post->post_name;
    $content = strip_tags($post->post_content);
    $first_10_percent = max(300, floor(strlen($content) * 0.1));
    $first_content = substr($content, 0, $first_10_percent);
    
    // Test 1: SEO title
    $tests['title'] = [
        'label' => 'Primary keyword in SEO title',
        'passed' => stripos($meta_title, $keyword_lower) !== false,
        'location' => 'title',
    ];
    
    // Test 2: Meta description
    if (!empty($meta_desc)) {
        $tests['description'] = [
            'label' => 'Primary keyword in meta description',
            'passed' => stripos($meta_desc, $keyword_lower) !== false,
            'location' => 'description',
        ];
    }
    
    // Test 3: URL slug
    $keyword_slug = str_replace(' ', '-', $keyword_lower);
    $tests['url'] = [
        'label' => 'Primary keyword in URL slug',
        'passed' => stripos($url_slug, $keyword_slug) !== false,
        'location' => 'url',
    ];
    
    // Test 4: First 10% of content
    $tests['content_beginning'] = [
        'label' => 'Primary keyword in first 10% of content',
        'passed' => stripos(strtolower($first_content), $keyword_lower) !== false,
        'location' => 'content',
    ];
    
    // Test 5: Image alt text
    $image_id = get_post_thumbnail_id($post->ID);
    $alt_text = '';
    if ($image_id) {
        $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
    }
    $tests['image_alt'] = [
        'label' => 'Primary keyword in image alt text',
        'passed' => !empty($alt_text) && stripos(strtolower($alt_text), $keyword_lower) !== false,
        'location' => 'image',
    ];
    
    return $tests;
}

/**
 * Test keyword in content body and subheadings (all keywords)
 * - Keyword presence in content
 * - Keyword in subheadings (H2/H3)
 */
function cta_test_all_keywords($keywords, $post) {
    $tests = [];
    
    if (empty($keywords)) {
        return $tests;
    }
    
    $content = strip_tags($post->post_content);
    $content_lower = strtolower($content);
    
    // Extract subheadings
    preg_match_all('/<h[2-3][^>]*>([^<]+)<\/h[2-3]>/i', $post->post_content, $headings);
    $headings_text = implode(' ', $headings[1]);
    $headings_lower = strtolower($headings_text);
    
    foreach ($keywords as $keyword) {
        $keyword_lower = strtolower(trim($keyword));
        if (empty($keyword_lower)) {
            continue;
        }
        
        // Test: Keyword in content
        $in_content = stripos($content_lower, $keyword_lower) !== false;
        
        // Test: Keyword in subheadings
        $in_headings = stripos($headings_lower, $keyword_lower) !== false;
        
        $tests[$keyword_lower] = [
            'keyword' => $keyword,
            'in_content' => $in_content,
            'in_headings' => $in_headings,
            'label' => 'Keyword "' . $keyword . '" in content and subheadings',
            'passed' => $in_content && $in_headings,
        ];
    }
    
    return $tests;
}

/**
 * =========================================
 * SCHEMA TEMPLATES
 * =========================================
 */

/**
 * Get schema template for post type
 */
function cta_get_schema_template($post_type = 'post') {
    $templates = [
        'post' => 'Article', // Blog posts use Article schema
        'page' => 'None', // Pages: None (unless manually set)
        'course' => 'Course', // Courses use Course schema
        'course_event' => 'Event', // Events use Event schema
    ];
    
    return $templates[$post_type] ?? 'WebPage';
}

/**
 * Apply schema template
 */
function cta_apply_schema_template($post_id) {
    $post_type = get_post_type($post_id);
    
    // Check if schema type is already set
    $existing_schema = cta_safe_get_field('page_schema_type', $post_id, '');
    if (!empty($existing_schema)) {
        return; // Don't override manual settings
    }
    
    // Apply template
    $template = cta_get_schema_template($post_type);
    if ($template !== 'None') {
        cta_safe_update_field('page_schema_type', $post_id, $template);
    }
}
add_action('save_post', 'cta_apply_schema_template', 10, 1);

/**
 * =========================================
 * SITEMAP CONFIGURATION
 * =========================================
 */

/**
 * Configure sitemap settings
 */
function cta_configure_sitemap() {
    // Links per sitemap: 200 (WordPress default is 500, but 200 is recommended)
    add_filter('wp_sitemaps_max_urls', function($max_urls) {
        return 200; // Rank Math recommendation
    });
    
    // Images in sitemap: ON (already implemented in seo-image-sitemap.php)
    // Featured images: ON (already implemented)
    // Ping search engines: ON (already implemented in seo.php)
    // Include pages/posts: ON (default)
    // Include attachments: OFF (we redirect attachments, so no need)
    add_filter('wp_sitemaps_add_provider', function($provider, $name) {
        if ($name === 'attachments') {
            return false; // Disable attachment sitemap
        }
        return $provider;
    }, 10, 2);
    
    // Include categories: ON (default)
    // Include tags: OFF (tags are noindexed)
    add_filter('wp_sitemaps_add_provider', function($provider, $name) {
        if ($name === 'taxonomies') {
            // Filter out tags
            add_filter('wp_sitemaps_taxonomies_query_args', function($args) {
                if (isset($args['taxonomy'])) {
                    $args['taxonomy'] = array_diff($args['taxonomy'], ['post_tag']);
                }
                return $args;
            });
        }
        return $provider;
    }, 10, 2);
}
add_action('init', 'cta_configure_sitemap');

/**
 * =========================================
 * NOINDEX EMPTY CATEGORIES/TAGS
 * =========================================
 */

/**
 * Noindex empty category and tag archives
 * Note: Rank Math recommends OFF (delete empty categories instead)
 * But we'll implement it as an option
 */
function cta_noindex_empty_archives($robots) {
    // Check if setting is enabled
    $noindex_empty = cta_safe_get_field('seo_noindex_empty_archives', 'option', false);
    
    if (!$noindex_empty) {
        return $robots; // Setting is OFF (as recommended)
    }
    
    if (is_category() || is_tag()) {
        $term = get_queried_object();
        $count = $term->count ?? 0;
        
        if ($count === 0) {
            $robots['noindex'] = true;
        }
    }
    
    return $robots;
}
add_filter('wp_robots', 'cta_noindex_empty_archives', 10);
