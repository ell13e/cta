<?php
/**
 * SEO Enhancements
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Generate meta description from template based on post type
 * 
 * @param WP_Post $post Post object
 * @return string Generated description
 */
function cta_generate_meta_description($post) {
    if (!$post) {
        return '';
    }
    
    $post_type = $post->post_type;
    
    // Template-based auto-generation
    switch ($post_type) {
        case 'course':
            $level = cta_safe_get_field('course_level', $post->ID, '');
            $title = get_the_title($post->ID);
            $excerpt = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_trim_words(strip_tags($post->post_content), 20);
            
            if ($excerpt) {
                $description = ($level ? $level . ' ' : '') . $title . ' training in Kent. ' . $excerpt . '. CQC-compliant, CPD-accredited.';
            } else {
                $description = ($level ? $level . ' ' : '') . $title . ' training for care workers in Kent. CQC-compliant, CPD-accredited course.';
            }
            break;
            
        case 'course_event':
            $course_title = get_the_title($post->ID);
            $event_date = get_post_meta($post->ID, 'event_date', true);
            $location = cta_safe_get_field('event_location', $post->ID, 'Maidstone');
            
            if ($event_date) {
                $date_formatted = date('j F Y', strtotime($event_date));
                $description = $course_title . ' - ' . $date_formatted . ' in ' . $location . '. Book your place on this essential course. Limited places available.';
            } else {
                $description = $course_title . ' in ' . $location . '. Book your place on this essential course. Limited places available.';
            }
            break;
            
        case 'post':
            $excerpt = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_trim_words(strip_tags($post->post_content), 25);
            if ($excerpt) {
                $description = $excerpt . '. Expert guidance from Continuity Training Academy.';
            } else {
                $description = get_the_title($post->ID) . '. Expert care training guidance from CTA.';
            }
            break;
            
        case 'page':
            $excerpt = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_trim_words(strip_tags($post->post_content), 25);
            if ($excerpt) {
                $description = $excerpt;
            } else {
                $description = get_the_title($post->ID) . '. Professional care training in Kent.';
            }
            break;
            
        default:
            $excerpt = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_trim_words(strip_tags($post->post_content), 25);
            $description = $excerpt ?: get_the_title($post->ID) . '.';
    }
    
    // Ensure length is 120-160 characters
    $description = wp_trim_words($description, 30, '');
    if (strlen($description) < 120) {
        $description .= ' CQC-compliant, CPD-accredited training in Kent.';
    }
    if (strlen($description) > 160) {
        $description = wp_trim_words($description, 25, '');
    }
    
    return $description;
}

/**
 * Get meta description with smart fallback hierarchy
 * Priority 1: ACF custom field
 * Priority 2: Post excerpt
 * Priority 3: Auto-generate from templates
 * Priority 4: Generic fallback (never empty)
 * 
 * @param int|WP_Post $post Post ID or object
 * @return string Meta description
 */
function cta_get_meta_description($post = null) {
    if (!$post) {
        global $post;
    }
    
    if (is_numeric($post)) {
        $post = get_post($post);
    }
    
    if (!$post) {
        return 'Professional care sector training in Maidstone, Kent. CQC-compliant, CPD-accredited courses.';
    }
    
    $post_type = $post->post_type;
    
    // Priority 1: ACF custom field (check existing field names first)
    $custom_description = '';
    if ($post_type === 'course') {
        $custom_description = cta_safe_get_field('course_seo_meta_description', $post->ID, '');
    } elseif ($post_type === 'course_event') {
        $custom_description = cta_safe_get_field('event_seo_meta_description', $post->ID, '');
    } elseif ($post_type === 'post') {
        $custom_description = cta_safe_get_field('news_meta_description', $post->ID, '');
    } else {
        $custom_description = cta_safe_get_field('seo_meta_description', $post->ID, '');
    }
    
    if (!empty($custom_description)) {
        return $custom_description;
    }
    
    // Priority 2: Post excerpt
    if (has_excerpt($post->ID)) {
        $excerpt = get_the_excerpt($post->ID);
        if (strlen($excerpt) >= 120 && strlen($excerpt) <= 160) {
            return $excerpt;
        }
    }
    
    // Priority 3: Auto-generate from templates
    $generated = cta_generate_meta_description($post);
    if (!empty($generated)) {
        return $generated;
    }
    
    // Priority 4: Generic fallback (never empty)
    return 'Professional care sector training in Maidstone, Kent. CQC-compliant, CPD-accredited courses.';
}

/**
 * Safe get_field wrapper - returns default if ACF not active
 * For 'option' post_id, uses Customizer settings instead
 */
function cta_safe_get_field($field, $post_id = false, $default = '') {
    if ($post_id === 'option') {
        return cta_get_option_field($field, $default);
    }
    
    if (!function_exists('get_field')) {
        return $default;
    }
    $value = get_field($field, $post_id);
    return ($value !== null && $value !== '' && $value !== false) ? $value : $default;
}

/**
 * Safely update ACF field or fall back to postmeta
 * 
 * Uses ACF's update_field() when available (proper field mapping),
 * otherwise falls back to update_post_meta() for compatibility
 * 
 * @param string $field Field name
 * @param mixed $value Value to save
 * @param int|string|false $post_id Post ID (defaults to current post)
 * @return bool Success status
 */
function cta_safe_update_field($field, $value, $post_id = false) {
    // Use ACF's update_field() if available (proper field mapping)
    if (function_exists('update_field')) {
        return update_field($field, $value, $post_id);
    }
    
    // Fallback to direct postmeta if ACF not available
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    if (!$post_id) {
        return false;
    }
    
    return update_post_meta($post_id, $field, $value);
}

/**
 * Get option fields from Customizer (replaces ACF Options)
 */
function cta_get_option_field($field, $default = '') {
    $field_map = [
        'seo_site_description' => 'cta_seo_site_description',
        'seo_og_image' => null, // Not in Customizer, use default
        'seo_twitter_handle' => 'cta_seo_twitter_handle',
        'seo_courses_title' => 'cta_seo_courses_title',
        'seo_courses_description' => 'cta_seo_courses_description',
        'seo_events_title' => 'cta_seo_events_title',
        'seo_events_description' => 'cta_seo_events_description',
        'seo_org_name' => 'cta_seo_org_name',
        'seo_org_description' => 'cta_seo_org_description',
        'seo_org_logo' => null,
        'seo_org_founding_date' => 'cta_seo_founding_year',
        'seo_org_price_range' => null,
        'seo_address_street' => 'cta_address_line2',
        'seo_address_locality' => 'cta_address_city',
        'seo_address_region' => null,
        'seo_address_postcode' => 'cta_address_postcode',
        'seo_geo_lat' => 'cta_seo_geo_lat',
        'seo_geo_lng' => 'cta_seo_geo_lng',
        'seo_google_verification' => 'cta_seo_google_verification',
        'seo_bing_verification' => 'cta_seo_bing_verification',
        'seo_default_h1_pattern' => 'cta_seo_default_h1_pattern',
        'seo_default_meta_title_pattern' => 'cta_seo_default_meta_title_pattern',
        'seo_default_meta_description_template' => 'cta_seo_default_meta_description_template',
        'seo_default_section_heading' => 'cta_seo_default_section_heading',
        'contact_phone' => 'cta_contact_phone',
        'contact_email' => 'cta_contact_email',
        'social_facebook' => 'cta_social_facebook',
        'social_instagram' => 'cta_social_instagram',
        'social_linkedin' => 'cta_social_linkedin',
        'social_twitter' => 'cta_social_twitter',
    ];
    
    $defaults = [
        'seo_courses_title' => 'Professional Training Courses',
        'seo_courses_description' => 'Browse our range of CQC-compliant, CPD-accredited care sector training courses in Maidstone, Kent.',
        'seo_events_title' => 'Upcoming Training Courses',
        'seo_events_description' => 'Book your place on our scheduled training sessions in Maidstone, Kent. View dates and availability.',
        'seo_org_price_range' => '££',
        'seo_address_region' => 'Kent',
        'seo_default_section_heading' => 'Course Overview',
    ];
    
    if (isset($field_map[$field]) && $field_map[$field] !== null) {
        return get_theme_mod($field_map[$field], $default);
    }
    
    if (isset($defaults[$field])) {
        return $defaults[$field];
    }
    
    return $default;
}

/**
 * Clean canonical URL
 * - Removes query parameters (except allowed ones)
 * - Ensures consistent trailing slash
 * - Forces HTTPS
 * - Removes www inconsistencies
 */
function cta_clean_canonical_url($url) {
    $parsed = wp_parse_url($url);
    
    if (!$parsed) {
        return $url;
    }
    
    $scheme = 'https';
    
    // Get host (remove www if site doesn't use it, add if it does)
    $host = $parsed['host'] ?? '';
    $site_url = home_url();
    $site_has_www = strpos($site_url, '://www.') !== false;
    
    if ($site_has_www && strpos($host, 'www.') !== 0) {
        $host = 'www.' . $host;
    } elseif (!$site_has_www && strpos($host, 'www.') === 0) {
        $host = substr($host, 4);
    }
    
    $path = $parsed['path'] ?? '/';
    
    if (!preg_match('/\.[a-zA-Z0-9]+$/', $path)) {
        $path = trailingslashit($path);
    }
    
    $clean_url = $scheme . '://' . $host . $path;
    
    return $clean_url;
}

/**
 * =========================================
 * META TAGS & OPEN GRAPH
 * =========================================
 */

/**
 * Helper function to output SEO meta tags for location pages
 * 
 * @param string $title Meta title
 * @param string $description Meta description
 */
function cta_output_seo_meta_tags($title, $description) {
    $url = get_permalink();
    ?>
    <meta name="description" content="<?php echo esc_attr($description); ?>">
    <meta property="og:title" content="<?php echo esc_attr($title); ?>">
    <meta property="og:description" content="<?php echo esc_attr($description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo esc_url($url); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($title); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr($description); ?>">
    <?php
}

/**
 * Output SEO meta tags in <head>
 */
function cta_seo_meta_tags() {
    global $post;
    
    // Get values from Theme Options (with fallbacks)
    $site_name = get_bloginfo('name');
    $site_description = cta_safe_get_field('seo_site_description', 'option', get_bloginfo('description'));
    $default_image = cta_safe_get_field('seo_og_image', 'option', cta_image('logo/long_logo-1200w.webp'));
    $twitter_handle = cta_safe_get_field('seo_twitter_handle', 'option', '');
    
    $courses_title = cta_safe_get_field('seo_courses_title', 'option', 'Professional Training Courses');
    $courses_desc = cta_safe_get_field('seo_courses_description', 'option', 'Browse our range of CQC-compliant, CPD-accredited care sector training courses in Maidstone, Kent.');
    $events_title = cta_safe_get_field('seo_events_title', 'option', 'Upcoming Training Courses');
    $events_desc = cta_safe_get_field('seo_events_description', 'option', 'Book your place on our scheduled training sessions in Maidstone, Kent. View dates and availability.');
    
    if (is_singular()) {
        // Check for custom SEO fields
        $custom_meta_title = '';
        
        if (get_post_type() === 'course') {
            $custom_meta_title = cta_safe_get_field('course_seo_meta_title', get_the_ID(), '');
        } elseif (get_post_type() === 'course_event') {
            $custom_meta_title = cta_safe_get_field('event_seo_meta_title', get_the_ID(), '');
        } elseif (get_post_type() === 'post') {
            $custom_meta_title = cta_safe_get_field('news_meta_title', get_the_ID(), '');
        }
        
        // Use custom meta title if set, otherwise use pattern or default
        if (!empty($custom_meta_title)) {
            $title = $custom_meta_title;
        } else {
            $title_pattern = cta_safe_get_field('seo_default_meta_title_pattern', 'option', '');
            if (!empty($title_pattern)) {
                $title = str_replace('{title}', get_the_title(), $title_pattern);
            } else {
                $title = get_the_title();
            }
        }
        
        // Use smart fallback hierarchy for meta description
        $description = cta_get_meta_description($post);
        
        $custom_canonical = get_post_meta($post->ID, '_cta_canonical', true);
        $url = !empty($custom_canonical) ? $custom_canonical : get_permalink();
        
        $image = get_the_post_thumbnail_url($post->ID, 'large') ?: $default_image;
        $type = 'article';
        $published = get_the_date('c');
        $modified = get_the_modified_date('c');
    } elseif (is_post_type_archive('course')) {
        // Check for category filter
        $category_slug = isset($_GET['category']) ? sanitize_key($_GET['category']) : '';
        $category_names = [
            'communication-workplace-culture' => 'Communication',
            'core-care-skills' => 'Core Care Skills',
            'emergency-first-aid' => 'First Aid',
            'health-conditions-specialist-care' => 'Specialist Care',
            'information-data-management' => 'GDPR & Data',
            'leadership-professional-development' => 'Leadership',
            'medication-management' => 'Medication',
            'nutrition-hygiene' => 'Nutrition & Hygiene',
            'safety-compliance' => 'Safety & Compliance'
        ];
        
        if ($category_slug && isset($category_names[$category_slug])) {
            $title = $category_names[$category_slug] . ' Courses';
            $description = $category_names[$category_slug] . ' training for care workers. CQC-compliant courses in Kent.';
        } else {
            $title = $courses_title;
            $description = $courses_desc;
        }
        
        $url = get_post_type_archive_link('course');
        if ($category_slug) {
            $url = add_query_arg('category', $category_slug, $url);
        }
        $image = $default_image;
        $type = 'website';
    } elseif (is_post_type_archive('course_event')) {
        $title = $events_title;
        $description = $events_desc;
        $url = get_post_type_archive_link('course_event');
        $image = $default_image;
        $type = 'website';
    } elseif (is_home() || is_front_page()) {
        $title = $site_name;
        $description = $site_description ?: 'CQC-compliant care training in Kent since 2020. CPD-accredited courses for care workers, first aid, medication management, safeguarding, and more.';
        $url = home_url('/');
        $image = $default_image;
        $type = 'website';
    } elseif (is_tax('course_category')) {
        $term = get_queried_object();
        $title = $term->name . ' Training Courses';
        $description = $term->description ?: 'Browse our ' . $term->name . ' training courses in Maidstone, Kent.';
        $url = get_term_link($term);
        $image = $default_image;
        $type = 'website';
    } elseif (is_search()) {
        $title = 'Search Results for: ' . get_search_query();
        $description = 'Search results for "' . get_search_query() . '" on ' . $site_name;
        $url = get_search_link();
        $image = $default_image;
        $type = 'website';
    } elseif (is_404()) {
        $title = 'Page Not Found';
        $description = 'The page you are looking for could not be found.';
        $url = home_url($_SERVER['REQUEST_URI']);
        $image = $default_image;
        $type = 'website';
    } else {
        $title = wp_title('', false) ?: $site_name;
        $description = $site_description;
        $url = home_url($_SERVER['REQUEST_URI']);
        $image = $default_image;
        $type = 'website';
    }
    
    // Sanitize
    $title = esc_attr(strip_tags($title));
    
    // Ensure description is 120-160 characters (optimal for Google)
    $description = strip_tags($description);
    if (strlen($description) > 160) {
        $description = wp_trim_words($description, 25, '');
    }
    if (strlen($description) < 120 && strlen($description) > 0) {
        // Only pad if we have some content
        $description .= ' CQC-compliant, CPD-accredited training in Kent.';
        if (strlen($description) > 160) {
            $description = wp_trim_words($description, 22, '');
        }
    }
    $description = esc_attr($description);
    
    // Clean canonical URL - remove query parameters and ensure trailing slash consistency
    $url = cta_clean_canonical_url($url);
    $url = esc_url($url);
    $image = esc_url($image);
    
    // Output meta tags
    ?>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo $description; ?>">
    <link rel="canonical" href="<?php echo $url; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo $type; ?>">
    <meta property="og:url" content="<?php echo $url; ?>">
    <meta property="og:title" content="<?php echo $title; ?>">
    <meta property="og:description" content="<?php echo $description; ?>">
    <meta property="og:image" content="<?php echo $image; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>">
    <meta property="og:locale" content="en_GB">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo $url; ?>">
    <meta name="twitter:title" content="<?php echo $title; ?>">
    <meta name="twitter:description" content="<?php echo $description; ?>">
    <meta name="twitter:image" content="<?php echo $image; ?>">
    <?php if ($twitter_handle) : ?>
    <meta name="twitter:site" content="@<?php echo esc_attr($twitter_handle); ?>">
    <?php endif; ?>
    
    <?php if (is_singular() && isset($published)) : ?>
    <!-- Article Meta -->
    <meta property="article:published_time" content="<?php echo $published; ?>">
    <meta property="article:modified_time" content="<?php echo $modified; ?>">
    <?php endif; ?>
    
    <?php 
    // Verification codes from admin
    $google_verify = cta_safe_get_field('seo_google_verification', 'option', '');
    $bing_verify = cta_safe_get_field('seo_bing_verification', 'option', '');
    
    if ($google_verify) : ?>
    <!-- Google Search Console -->
    <meta name="google-site-verification" content="<?php echo esc_attr($google_verify); ?>">
    <?php endif; ?>
    
    <?php if ($bing_verify) : ?>
    <!-- Bing Webmaster Tools -->
    <meta name="msvalidate.01" content="<?php echo esc_attr($bing_verify); ?>">
    <?php endif; ?>
    
    <?php 
    // Add rel="prev" and rel="next" for paginated archives
    if (is_archive() || is_home()) {
        global $paged, $wp_query;
        $paged = $paged ?: 1;
        $max_page = $wp_query->max_num_pages;
        
        if ($paged > 1) {
            $prev_url = get_pagenum_link($paged - 1);
            echo '<link rel="prev" href="' . esc_url($prev_url) . '">' . "\n";
        }
        
        if ($paged < $max_page) {
            $next_url = get_pagenum_link($paged + 1);
            echo '<link rel="next" href="' . esc_url($next_url) . '">' . "\n";
        }
    }
    ?>
    
    <?php
}
add_action('wp_head', 'cta_seo_meta_tags', 1);

/**
 * Optimise document title - Fix duplicates and shorten to <60 chars
 */
function cta_document_title_parts($title_parts) {
    global $post;
    
    // Pattern 1: Homepage
    if (is_front_page()) {
        $title_parts['title'] = 'Care Training in Kent | CQC-Compliant | CTA';
        return $title_parts;
    }
    
    // Pattern 2: Course Pages (remove redundant " | Training Course")
    if (is_singular('course')) {
        // Check for custom title override
        $custom_title = cta_safe_get_field('course_seo_meta_title', get_the_ID(), '');
        if (!empty($custom_title)) {
            $title_parts['title'] = $custom_title;
        } else {
            // Just add " | CTA" - remove " | Training Course" redundancy
            $title_parts['title'] = get_the_title() . ' | CTA';
        }
        
        // Ensure max 60 chars
        if (strlen($title_parts['title']) > 60) {
            $title = get_the_title();
            $max_title_length = 60 - 6; // Reserve space for " | CTA"
            if (strlen($title) > $max_title_length) {
                $title = wp_trim_words($title, 6, '');
            }
            $title_parts['title'] = $title . ' | CTA';
        }
        
        return $title_parts;
    }
    
    // Pattern 3: Course Events (auto-append date)
    if (is_singular('course_event')) {
        $event_date = get_post_meta(get_the_ID(), 'event_date', true);
        $base_title = get_the_title();
        
        if ($event_date) {
            $date_formatted = date('j M', strtotime($event_date)); // "13 Feb"
            $title_parts['title'] = $base_title . ' - ' . $date_formatted . ' | Book Now | CTA';
        } else {
            $title_parts['title'] = $base_title . ' | Book Now | CTA';
        }
        
        // Ensure max 60 chars
        if (strlen($title_parts['title']) > 60) {
            $max_base_length = $event_date ? 60 - 20 : 60 - 18; // Reserve space for date + suffix
            if (strlen($base_title) > $max_base_length) {
                $base_title = wp_trim_words($base_title, 4, '');
            }
            if ($event_date) {
                $date_formatted = date('j M', strtotime($event_date));
                $title_parts['title'] = $base_title . ' - ' . $date_formatted . ' | CTA';
            } else {
                $title_parts['title'] = $base_title . ' | CTA';
            }
        }
        
        return $title_parts;
    }
    
    // Pattern 4: Filtered Archives (auto-append category)
    if (is_post_type_archive('course') && isset($_GET['category'])) {
        $category_slug = sanitize_key($_GET['category']);
        $category_names = [
            'communication-workplace-culture' => 'Communication',
            'core-care-skills' => 'Core Care Skills',
            'emergency-first-aid' => 'First Aid',
            'health-conditions-specialist-care' => 'Specialist Care',
            'information-data-management' => 'GDPR & Data',
            'leadership-professional-development' => 'Leadership',
            'medication-management' => 'Medication',
            'nutrition-hygiene' => 'Nutrition & Hygiene',
            'safety-compliance' => 'Safety & Compliance'
        ];
        
        if (isset($category_names[$category_slug])) {
            $title_parts['title'] = $category_names[$category_slug] . ' Courses | CTA';
        } else {
            // Fallback to auto-generated name
            $category_name = ucwords(str_replace('-', ' ', $category_slug));
            $title_parts['title'] = $category_name . ' Courses | CTA';
        }
        
        return $title_parts;
    }
    
    // Pattern 5: Blog Posts
    if (is_singular('post')) {
        $title_parts['title'] = get_the_title() . ' | CTA';
        
        // Ensure max 60 chars
        if (strlen($title_parts['title']) > 60) {
            $title = get_the_title();
            $max_title_length = 60 - 6; // Reserve space for " | CTA"
            if (strlen($title) > $max_title_length) {
                $title = wp_trim_words($title, 6, '');
            }
            $title_parts['title'] = $title . ' | CTA';
        }
        
        return $title_parts;
    }
    
    // Pattern 6: Pages
    if (is_singular('page')) {
        $title_parts['title'] = get_the_title() . ' | CTA';
        
        // Ensure max 60 chars
        if (strlen($title_parts['title']) > 60) {
            $title = get_the_title();
            $max_title_length = 60 - 6;
            if (strlen($title) > $max_title_length) {
                $title = wp_trim_words($title, 6, '');
            }
            $title_parts['title'] = $title . ' | CTA';
        }
        
        return $title_parts;
    }
    
    return $title_parts;
}
add_filter('document_title_parts', 'cta_document_title_parts');

/**
 * Custom title separator
 */
function cta_document_title_separator($sep) {
    return '|';
}
add_filter('document_title_separator', 'cta_document_title_separator');

/**
 * =========================================
 * STRUCTURED DATA / SCHEMA.ORG
 * =========================================
 */

/**
 * Output JSON-LD structured data
 */
function cta_schema_markup() {
    $contact = function_exists('cta_get_contact_info') ? cta_get_contact_info() : [];
    
    // Get SEO options from admin
    $org_name = cta_safe_get_field('seo_org_name', 'option', get_bloginfo('name'));
    $org_description = cta_safe_get_field('seo_org_description', 'option', 'Professional care sector training provider in Maidstone, Kent. CQC-compliant, CPD-accredited courses.');
    $org_logo = cta_safe_get_field('seo_org_logo', 'option', cta_image('logo/long_logo-400w.webp'));
    $founding_date = cta_safe_get_field('seo_org_founding_date', 'option', '2020');
    $price_range = cta_safe_get_field('seo_org_price_range', 'option', '££');
    
    // Address from SEO options
    $street = cta_safe_get_field('seo_address_street', 'option', 'The Maidstone Studios, New Cut Road');
    $locality = cta_safe_get_field('seo_address_locality', 'option', 'Maidstone');
    $region = cta_safe_get_field('seo_address_region', 'option', 'Kent');
    $postcode = cta_safe_get_field('seo_address_postcode', 'option', 'ME14 5NZ');
    $lat = cta_safe_get_field('seo_geo_lat', 'option', '51.2795');
    $lng = cta_safe_get_field('seo_geo_lng', 'option', '0.5467');
    
    // Get Trustpilot rating for aggregate rating schema
    $trustpilot_rating = cta_get_theme_option('trustpilot_rating', '4.6/5');
    $trustpilot_url = cta_get_theme_option('trustpilot_url', 'https://uk.trustpilot.com/review/continuitytrainingacademy.co.uk');
    $review_count = absint(cta_get_theme_option('trustpilot_review_count', 20));
    
    // Parse rating (e.g., "4.6/5" -> 4.6)
    $rating_value = floatval(preg_replace('/[^0-9.]/', '', $trustpilot_rating));
    
    // Organisation schema (all pages)
    $org_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'EducationalOrganization',
        'name' => $org_name,
        'url' => home_url('/'),
        'logo' => $org_logo,
        'description' => $org_description,
        'foundingDate' => $founding_date,
        'priceRange' => $price_range,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $street,
            'addressLocality' => $locality,
            'addressRegion' => $region,
            'postalCode' => $postcode,
            'addressCountry' => 'GB',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => floatval($lat),
            'longitude' => floatval($lng),
        ],
        'telephone' => $contact['phone'] ?? '01622 587343',
        'email' => $contact['email'] ?? 'enquiries@continuitytrainingacademy.co.uk',
        'sameAs' => array_filter([
            $contact['social']['facebook'] ?? '',
            $contact['social']['instagram'] ?? '',
            $contact['social']['linkedin'] ?? '',
            $contact['social']['twitter'] ?? '',
        ]),
        'areaServed' => [
            '@type' => 'GeoCircle',
            'geoMidpoint' => [
                '@type' => 'GeoCoordinates',
                'latitude' => floatval($lat),
                'longitude' => floatval($lng),
            ],
            'geoRadius' => '50000', // 50km radius
        ],
    ];
    
    // Add aggregate rating if Trustpilot rating exists
    if ($rating_value > 0 && $review_count > 0) {
        $org_schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $rating_value,
            'bestRating' => '5',
            'worstRating' => '5',
            'ratingCount' => $review_count,
            'reviewCount' => $review_count,
        ];
    }
    
    echo '<script type="application/ld+json">' . json_encode($org_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    
    // LocalBusiness schema (critical for local SEO - separate from EducationalOrganization)
    $local_business_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        '@id' => home_url('/#organization'),
        'name' => $org_name,
        'url' => home_url('/'),
        'logo' => $org_logo,
        'description' => $org_description,
        'image' => $org_logo,
        'priceRange' => $price_range,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $street,
            'addressLocality' => $locality,
            'addressRegion' => $region,
            'postalCode' => $postcode,
            'addressCountry' => 'GB',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => floatval($lat),
            'longitude' => floatval($lng),
        ],
        'telephone' => $contact['phone'] ?? '01622 587343',
        'email' => $contact['email'] ?? 'enquiries@continuitytrainingacademy.co.uk',
        'areaServed' => [
            [
                '@type' => 'City',
                'name' => 'Maidstone',
            ],
            [
                '@type' => 'City',
                'name' => 'Medway',
            ],
            [
                '@type' => 'City',
                'name' => 'Canterbury',
            ],
            [
                '@type' => 'City',
                'name' => 'Ashford',
            ],
            [
                '@type' => 'State',
                'name' => 'Kent',
            ],
        ],
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'Care Training Courses',
            'itemListElement' => [
                [
                    '@type' => 'OfferCatalog',
                    'name' => 'Core Care Skills',
                ],
                [
                    '@type' => 'OfferCatalog',
                    'name' => 'Emergency & First Aid',
                ],
                [
                    '@type' => 'OfferCatalog',
                    'name' => 'Safety & Compliance',
                ],
                [
                    '@type' => 'OfferCatalog',
                    'name' => 'Medication Management',
                ],
            ],
        ],
    ];
    
    // Add aggregate rating to LocalBusiness
    if ($rating_value > 0 && $review_count > 0) {
        $local_business_schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $rating_value,
            'bestRating' => '5',
            'worstRating' => '5',
            'ratingCount' => $review_count,
            'reviewCount' => $review_count,
        ];
    }
    
    echo '<script type="application/ld+json">' . json_encode($local_business_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    
    // WebSite schema with SearchAction (for on-site search)
    $website_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $org_name,
        'url' => home_url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => home_url('/?s={search_term_string}'),
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
    
    echo '<script type="application/ld+json">' . json_encode($website_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    
    // Course schema
    if (is_singular('course')) {
        global $post;
        $price = cta_safe_get_field('course_price', $post->ID, '');
        $duration = cta_safe_get_field('course_duration', $post->ID, '');
        $level = cta_safe_get_field('course_level', $post->ID, '');
        $accreditation = cta_safe_get_field('course_accreditation', $post->ID, '');
        
        $course_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => get_the_title(),
            'description' => has_excerpt() ? get_the_excerpt() : wp_trim_words(strip_tags($post->post_content), 50),
            'url' => get_permalink(),
            'provider' => [
                '@type' => 'EducationalOrganization',
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $street,
                    'addressLocality' => $locality,
                    'addressRegion' => $region,
                    'postalCode' => $postcode,
                    'addressCountry' => 'GB',
                ],
            ],
            'courseMode' => 'onsite',
            'inLanguage' => 'en-GB',
            'isAccessibleForFree' => false,
            'teaches' => get_the_title(), // What the course teaches
        ];
        
        // Add course category as keywords
        $terms = get_the_terms($post->ID, 'course_category');
        if ($terms && !is_wp_error($terms)) {
            $keywords = array_map(function($term) {
                return $term->name;
            }, $terms);
            $course_schema['keywords'] = implode(', ', $keywords);
        }
        
        if ($price) {
            // Clean price (remove currency symbols, extract numeric value)
            $price_clean = preg_replace('/[^0-9.]/', '', $price);
            $course_schema['offers'] = [
                '@type' => 'Offer',
                'price' => $price_clean,
                'priceCurrency' => 'GBP',
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink(),
                'seller' => [
                    '@type' => 'EducationalOrganization',
                    'name' => get_bloginfo('name'),
                ],
            ];
        }
        
        if ($duration) {
            // Convert duration to ISO 8601 format
            if (preg_match('/(\d+)\s*day/i', $duration, $matches)) {
                $course_schema['timeRequired'] = 'P' . $matches[1] . 'D';
            } elseif (preg_match('/(\d+)\s*hour/i', $duration, $matches)) {
                $course_schema['timeRequired'] = 'PT' . $matches[1] . 'H';
            }
        }
        
        if ($level) {
            $course_schema['educationalLevel'] = $level;
        }
        
        if ($accreditation) {
            $course_schema['educationalCredentialAwarded'] = $accreditation;
        }
        
        // Add location (Maidstone, Kent)
        $course_schema['location'] = [
            '@type' => 'Place',
            'name' => 'Maidstone Studios',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $street,
                'addressLocality' => $locality,
                'addressRegion' => $region,
                'postalCode' => $postcode,
                'addressCountry' => 'GB',
            ],
        ];
        
        if (has_post_thumbnail()) {
            $course_schema['image'] = get_the_post_thumbnail_url($post->ID, 'large');
        }
        
        echo '<script type="application/ld+json">' . json_encode($course_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
    
    // Course Event schema
    if (is_singular('course_event')) {
        global $post;
        $linked_course = cta_safe_get_field('linked_course', $post->ID, null);
        $event_date = cta_safe_get_field('event_date', $post->ID, '');
        $start_time = cta_safe_get_field('start_time', $post->ID, '');
        $location = cta_safe_get_field('event_location', $post->ID, '');
        $spaces = cta_safe_get_field('spaces_available', $post->ID, '');
        $price = cta_safe_get_field('event_price', $post->ID, '');
        
        if (!$price && $linked_course) {
            $price = cta_safe_get_field('course_price', $linked_course->ID, '');
        }
        
        $event_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CourseInstance',
            'name' => get_the_title(),
            'description' => $linked_course ? get_the_excerpt($linked_course->ID) : get_the_excerpt(),
            'url' => get_permalink(),
            'courseMode' => 'onsite',
            'inLanguage' => 'en-GB',
            'organizer' => [
                '@type' => 'EducationalOrganization',
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
            ],
        ];
        
        if ($event_date) {
            $datetime = $event_date;
            if ($start_time) {
                $datetime .= 'T' . $start_time . ':00';
            }
            $event_schema['startDate'] = $datetime;
        }
        
        if ($location) {
            $event_schema['location'] = [
                '@type' => 'Place',
                'name' => $location,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Maidstone',
                    'addressRegion' => 'Kent',
                    'addressCountry' => 'GB',
                ],
            ];
        }
        
        if ($price) {
            $event_schema['offers'] = [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'GBP',
                'availability' => ($spaces > 0) ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'url' => get_permalink(),
                'validFrom' => get_the_date('c'),
            ];
        }
        
        echo '<script type="application/ld+json">' . json_encode($event_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
    
    // Blog post schema
    if (is_singular('post')) {
        global $post;
        
        $article_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'description' => has_excerpt() ? get_the_excerpt() : wp_trim_words(strip_tags($post->post_content), 30),
            'url' => get_permalink(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => cta_image('logo/long_logo-400w.webp'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => get_permalink(),
            ],
        ];
        
        if (has_post_thumbnail()) {
            $article_schema['image'] = get_the_post_thumbnail_url($post->ID, 'large');
        }
        
        echo '<script type="application/ld+json">' . json_encode($article_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
    
    // FAQPage schema (for pages with FAQs)
    if (is_page()) {
        global $post;
        $faqs = [];
        
        // Check if page has FAQ ACF field
        if (function_exists('get_field')) {
            $faqs = get_field('faqs', $post->ID);
        }
        
        // If no ACF FAQs, check for default FAQs (e.g., group-training page)
        if (empty($faqs) && function_exists('cta_get_page_faqs')) {
            $faqs = cta_get_page_faqs($post->ID);
        }
        
        if (!empty($faqs) && is_array($faqs)) {
            $faq_schema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => [],
            ];
            
            foreach ($faqs as $faq) {
                $question = is_array($faq) ? ($faq['question'] ?? '') : '';
                $answer = is_array($faq) ? ($faq['answer'] ?? '') : '';
                
                if (!empty($question) && !empty($answer)) {
                    $faq_schema['mainEntity'][] = [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $answer,
                        ],
                    ];
                }
            }
            
            if (!empty($faq_schema['mainEntity'])) {
                echo '<script type="application/ld+json">' . json_encode($faq_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
            }
        }
    }
    
    // Breadcrumb schema
    if (!is_front_page()) {
        $breadcrumbs = cta_get_breadcrumb_items();
        if (!empty($breadcrumbs)) {
            $breadcrumb_schema = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [],
            ];
            
            foreach ($breadcrumbs as $index => $crumb) {
                $breadcrumb_schema['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['title'],
                    'item' => $crumb['url'],
                ];
            }
            
            echo '<script type="application/ld+json">' . json_encode($breadcrumb_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
        }
    }
}
add_action('wp_head', 'cta_schema_markup', 5);

/**
 * Helper function to get breadcrumb items for schema
 */
function cta_get_breadcrumb_items() {
    $items = [];
    
    // Home
    $items[] = [
        'title' => 'Home',
        'url' => home_url('/'),
    ];
    
    if (is_singular('course')) {
        $items[] = [
            'title' => 'Courses',
            'url' => get_post_type_archive_link('course'),
        ];
        
        $terms = get_the_terms(get_the_ID(), 'course_category');
        if ($terms && !is_wp_error($terms)) {
            $items[] = [
                'title' => $terms[0]->name,
                'url' => get_term_link($terms[0]),
            ];
        }
        
        $items[] = [
            'title' => get_the_title(),
            'url' => get_permalink(),
        ];
    } elseif (is_singular('course_event')) {
        $items[] = [
            'title' => 'Upcoming Courses',
            'url' => get_post_type_archive_link('course_event'),
        ];
        $items[] = [
            'title' => get_the_title(),
            'url' => get_permalink(),
        ];
    } elseif (is_singular('post')) {
        $items[] = [
            'title' => 'News',
            'url' => get_permalink(get_option('page_for_posts')),
        ];
        $items[] = [
            'title' => get_the_title(),
            'url' => get_permalink(),
        ];
    } elseif (is_post_type_archive('course')) {
        $items[] = [
            'title' => 'Courses',
            'url' => get_post_type_archive_link('course'),
        ];
    } elseif (is_post_type_archive('course_event')) {
        $items[] = [
            'title' => 'Upcoming Courses',
            'url' => get_post_type_archive_link('course_event'),
        ];
    } elseif (is_tax('course_category')) {
        $items[] = [
            'title' => 'Courses',
            'url' => get_post_type_archive_link('course'),
        ];
        $term = get_queried_object();
        $items[] = [
            'title' => $term->name,
            'url' => get_term_link($term),
        ];
    } elseif (is_page()) {
        $items[] = [
            'title' => get_the_title(),
            'url' => get_permalink(),
        ];
    }
    
    return $items;
}

/**
 * =========================================
 * SITEMAP ENHANCEMENTS
 * =========================================
 */

/**
 * Add custom post types to WordPress sitemap
 */
function cta_sitemap_post_types($post_types) {
    // WordPress already includes 'post' and 'page' by default
    // We just need to add our custom post types
    
    // Ensure courses are included
    if (post_type_exists('course')) {
        $post_types['course'] = get_post_type_object('course');
    }
    
    // Ensure events are included
    if (post_type_exists('course_event')) {
        $post_types['course_event'] = get_post_type_object('course_event');
    }
    
    return $post_types;
}
add_filter('wp_sitemaps_post_types', 'cta_sitemap_post_types');

/**
 * Remove taxonomies from sitemap
 * Only include individual posts/pages, not taxonomy archives
 */
function cta_sitemap_taxonomies($taxonomies) {
    // Remove all taxonomies - we only want individual course pages, not category archives
    unset($taxonomies['category']); // Blog categories
    unset($taxonomies['post_tag']); // Tags are noindexed
    unset($taxonomies['course_category']); // Course categories - we want courses, not categories
    unset($taxonomies['form_type']); // Form types - these are functional, not content
    
    // Return empty or remaining taxonomies
    return $taxonomies;
}
add_filter('wp_sitemaps_taxonomies', 'cta_sitemap_taxonomies');

/**
 * Exclude author archives from sitemap (noindexed per SEO docs)
 */
function cta_sitemap_exclude_users($args, $user) {
    // Exclude all users from sitemap since author archives are noindexed
    return false;
}
add_filter('wp_sitemaps_users_query_args', 'cta_sitemap_exclude_users', 10, 2);

/**
 * Remove users provider from sitemap entirely
 */
function cta_sitemap_remove_users($provider, $name) {
    if ($name === 'users') {
        return false;
    }
    return $provider;
}
add_filter('wp_sitemaps_add_provider', 'cta_sitemap_remove_users', 10, 2);

/**
 * Filter sitemap entries to exclude noindexed content
 * This runs after the query, so we can check each post individually
 */
function cta_filter_sitemap_entry($entry, $post, $post_type) {
    // Exclude posts explicitly marked as noindex
    $noindex = get_post_meta($post->ID, '_cta_noindex', true);
    if ($noindex === '1' || $noindex === 1) {
        return false; // Exclude from sitemap
    }
    
    // Exclude specific utility/functional pages by slug
    $excluded_slugs = [
        'sample-page',           // WordPress default demo page
        'unsubscribe',           // Email unsubscribe page (functional, not content)
        'privacy-policy',        // Legal pages - not SEO content
        'cookie-policy',
        'terms-conditions',
        'terms-and-conditions',
        'legal-notice',
        'disclaimer',
        // Remove all resource/location/compliance/training/faqs/downloadable pages
        'cqc-compliance-hub',
        'training-guides-tools',
        'training-guides',
        'downloadable-resources',
        'faqs',
        // All location pages (add more as needed)
        'location-maidstone',
        'location-london',
        'location-lancashire',
        'location-tunbridge-wells',
        'location-wales',
        'locations-index',
        'locations',
        'group-training',
    ];
    if ($post_type === 'page' && in_array($post->post_name, $excluded_slugs)) {
        return false; // Exclude utility and set-aside pages
    }
    
    // Exclude posts with missing critical data
    if ($post_type === 'course' || $post_type === 'course_event') {
        // Must have content
        if (empty($post->post_content) && empty($post->post_excerpt)) {
            return false;
        }
        
        // Must have title
        if (empty($post->post_title)) {
            return false;
        }
        
        // Must be published (not draft/pending)
        if ($post->post_status !== 'publish') {
            return false;
        }
        
        // Events must have future date
        if ($post_type === 'course_event') {
            $active = get_post_meta($post->ID, 'event_active', true);
            if ($active === '0' || $active === 0) {
                return false; // Exclude inactive events
            }
            
            $event_date = get_post_meta($post->ID, 'event_date', true);
            if (empty($event_date)) {
                return false; // No date = exclude
            }
            
            // Exclude past events (events with dates in the past)
            // Use WordPress timezone for consistent date comparison
            $wp_timezone = wp_timezone();
            try {
                $event_datetime = new DateTime($event_date, $wp_timezone);
                $today_datetime = new DateTime('today', $wp_timezone);
                
                // If event date is in the past, exclude from sitemap
                if ($event_datetime < $today_datetime) {
                    return false;
                }
            } catch (Exception $e) {
                // Invalid date format - exclude from sitemap
                return false;
            }
        }
    }
    
    // Exclude pages marked as noindex
    $robots_meta = get_post_meta($post->ID, '_seo_robots_noindex', true);
    if ($robots_meta === '1') {
        return false;
    }
    
    return $entry;
}
add_filter('wp_sitemaps_posts_entry', 'cta_filter_sitemap_entry', 5, 3);

/**
 * Set priority and change frequency for sitemap entries
 */
function cta_sitemap_entry($entry, $post, $post_type) {
    // First check if entry was already filtered out
    if ($entry === false) {
        return false;
    }
    
    // High priority pages based on slug
    $high_priority_slugs = [
        'cqc-compliance-hub',
        'training-guides',
        'downloadable-resources',
        'faqs',
        'group-training',
        'contact',
        'about',
        'safeguarding-training',
        'manual-handling-training',
    ];
    
    // Set priorities and change frequencies based on content type
    if ($post_type === 'page') {
        $slug = $post->post_name;
        if (in_array($slug, $high_priority_slugs)) {
            $entry['priority'] = 0.9;
            $entry['changefreq'] = 'weekly';
        } else {
            $entry['priority'] = 0.7;
            $entry['changefreq'] = 'monthly';
        }
    } elseif ($post_type === 'course') {
        // Courses are high priority (core business content)
        $entry['priority'] = 0.9;
        $entry['changefreq'] = 'monthly'; // Course content relatively stable
    } elseif ($post_type === 'course_event') {
        // Upcoming events - priority based on date proximity
        $event_date = get_post_meta($post->ID, 'event_date', true);
        if ($event_date) {
            $days_until = (strtotime($event_date) - time()) / DAY_IN_SECONDS;
            
            // Higher priority for sooner events
            if ($days_until <= 7) {
                $entry['priority'] = 0.9; // This week
            } elseif ($days_until <= 30) {
                $entry['priority'] = 0.8; // This month
            } else {
                $entry['priority'] = 0.7; // Future
            }
        } else {
            $entry['priority'] = 0.7; // Default if no date
        }
        $entry['changefreq'] = 'weekly'; // Booking availability changes
    } elseif ($post_type === 'post') {
        // Blog posts - priority based on age
        $post_age_days = (time() - strtotime($post->post_date)) / DAY_IN_SECONDS;
        
        if ($post_age_days <= 30) {
            $entry['priority'] = 0.7; // Recent posts
            $entry['changefreq'] = 'weekly';
        } else {
            $entry['priority'] = 0.6; // Older posts
            $entry['changefreq'] = 'monthly';
        }
    }
    
    // Set lastmod to post modified date for better crawling
    if (isset($post->post_modified_gmt)) {
        $entry['lastmod'] = $post->post_modified_gmt;
    }
    
    return $entry;
}
add_filter('wp_sitemaps_posts_entry', 'cta_sitemap_entry', 10, 3);

/**
 * Increase max URLs per sitemap
 */
function cta_sitemap_max_urls($max_urls) {
    return 2000; // Increased from default
}
add_filter('wp_sitemaps_max_urls', 'cta_sitemap_max_urls');

/**
 * Flush sitemap cache when posts are added/updated
 */
function cta_flush_sitemap_cache($post_id, $post) {
    // Only for public post types in the sitemap
    $sitemap_post_types = ['post', 'page', 'course', 'course_event'];
    
    if (in_array($post->post_type, $sitemap_post_types) && $post->post_status === 'publish') {
        // Delete sitemap cache
        wp_cache_delete('sitemap_posts_' . $post->post_type, 'sitemaps');
        wp_cache_delete('sitemap_index', 'sitemaps');
        
        // Delete the transient that caches the sitemap
        delete_transient('wp_sitemap_posts_' . $post->post_type);
        delete_transient('wp_sitemap_index');
        
        // Ping search engines (throttled to once per hour)
        cta_ping_search_engines();
    }
}
add_action('save_post', 'cta_flush_sitemap_cache', 10, 2);
add_action('delete_post', 'cta_flush_sitemap_cache', 10, 2);

/**
 * Flush sitemap cache when terms are added/updated
 */
function cta_flush_sitemap_taxonomy_cache($term_id, $tt_id, $taxonomy) {
    if ($taxonomy === 'course_category') {
        wp_cache_delete('sitemap_taxonomies_' . $taxonomy, 'sitemaps');
        delete_transient('wp_sitemap_taxonomies_' . $taxonomy);
        delete_transient('wp_sitemap_index');
        
        // Ping search engines
        cta_ping_search_engines();
    }
}
add_action('create_term', 'cta_flush_sitemap_taxonomy_cache', 10, 3);
add_action('edit_term', 'cta_flush_sitemap_taxonomy_cache', 10, 3);
add_action('delete_term', 'cta_flush_sitemap_taxonomy_cache', 10, 3);

/**
 * Ping search engines when sitemap updates
 */
function cta_ping_search_engines() {
    // Only ping once per hour max
    $last_ping = get_transient('cta_sitemap_last_ping');
    if ($last_ping) {
        return;
    }
    
    $sitemap_url = home_url('/wp-sitemap.xml');
    
    // Ping Google
    wp_remote_get('https://www.google.com/ping?sitemap=' . urlencode($sitemap_url), [
        'timeout' => 5,
        'blocking' => false, // Don't wait for response
    ]);
    
    // Ping Bing
    wp_remote_get('https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url), [
        'timeout' => 5,
        'blocking' => false,
    ]);
    
    // Set transient to prevent too frequent pinging
    set_transient('cta_sitemap_last_ping', time(), HOUR_IN_SECONDS);
}

/**
 * Daily cleanup - flush sitemap cache to remove past events
 */
function cta_cleanup_sitemap() {
    // Flush sitemap cache to force regeneration (removes past events)
    wp_cache_delete('wp_sitemap', 'sitemaps');
    wp_cache_delete('sitemap_index', 'sitemaps');
    delete_transient('wp_sitemap_index');
    delete_transient('wp_sitemap_posts_course_event');
}
add_action('cta_daily_cleanup', 'cta_cleanup_sitemap');

/**
 * Schedule daily cleanup on theme activation
 * 
 * Calculates next 3 AM in WordPress local timezone, then converts to GMT for cron scheduling
 */
function cta_schedule_daily_cleanup() {
    if (!wp_next_scheduled('cta_daily_cleanup')) {
        // Get WordPress timezone
        $wp_timezone = wp_timezone();
        
        // Calculate next 3 AM in WordPress local timezone
        $now_local = new DateTime('now', $wp_timezone);
        $next_3am_local = new DateTime('today 03:00:00', $wp_timezone);
        
        // If 3 AM today has already passed, schedule for tomorrow
        if ($next_3am_local < $now_local) {
            $next_3am_local = new DateTime('tomorrow 03:00:00', $wp_timezone);
        }
        
        // Convert to GMT timestamp for wp_schedule_event (which expects GMT)
        // getTimestamp() already returns UTC/GMT timestamp, so no conversion needed
        $next_3am_gmt = $next_3am_local->getTimestamp();
        
        wp_schedule_event($next_3am_gmt, 'daily', 'cta_daily_cleanup');
    }
}
add_action('after_switch_theme', 'cta_schedule_daily_cleanup');

/**
 * Fix duplicate course URLs on theme activation
 * Updates course slugs to correct format (with duration and level)
 * Runs automatically when theme is activated/uploaded
 */
function cta_fix_duplicate_urls_on_activation() {
    $fixes = [
        'adult-social-care-certificate' => 'adult-social-care-certificate-3d-l2',
        'emergency-first-aid-at-work' => 'emergency-first-aid-at-work-1d-l3',
        'medication-competency-management' => 'medication-competency-management-1d-l3',
        'moving-positioning-inc-hoist' => 'moving-positioning-inc-hoist-1d-l3',
    ];
    
    foreach ($fixes as $old_slug => $new_slug) {
        // Find course with old slug
        $course = get_page_by_path($old_slug, OBJECT, 'course');
        
        if (!$course) {
            continue; // Course not found or already correct
        }
        
        // Check if new slug already exists (different course)
        $existing = get_page_by_path($new_slug, OBJECT, 'course');
        if ($existing && $existing->ID !== $course->ID) {
            continue; // Conflict - skip
        }
        
        // Update the slug
        remove_action('acf/save_post', 'cta_auto_generate_course_slug_on_acf_save', 20);
        wp_update_post([
            'ID' => $course->ID,
            'post_name' => $new_slug,
        ], true);
        add_action('acf/save_post', 'cta_auto_generate_course_slug_on_acf_save', 20);
    }
    
    // Flush rewrite rules to ensure new URLs work
    flush_rewrite_rules(false);
}
add_action('after_switch_theme', 'cta_fix_duplicate_urls_on_activation', 5);

/**
 * One-time fix for duplicate URLs on init (if not already fixed)
 * This ensures existing sites get the fix even if theme wasn't just activated
 */
add_action('init', function() {
    // Only run once (check option)
    $fix_run = get_option('cta_duplicate_urls_fixed', false);
    if ($fix_run) {
        return; // Already fixed
    }
    
    // Run the fix
    cta_fix_duplicate_urls_on_activation();
    
    // Mark as done
    update_option('cta_duplicate_urls_fixed', true);
}, 1);

add_action('init', function() {
    if (!wp_next_scheduled('cta_daily_cleanup')) {
        // Get WordPress timezone
        $wp_timezone = wp_timezone();
        
        // Calculate next 3 AM in WordPress local timezone
        $now_local = new DateTime('now', $wp_timezone);
        $next_3am_local = new DateTime('today 03:00:00', $wp_timezone);
        
        // If 3 AM today has already passed, schedule for tomorrow
        if ($next_3am_local < $now_local) {
            $next_3am_local = new DateTime('tomorrow 03:00:00', $wp_timezone);
        }
        
        // Convert to GMT timestamp for wp_schedule_event (which expects GMT)
        // getTimestamp() already returns UTC/GMT timestamp, so no conversion needed
        $next_3am_gmt = $next_3am_local->getTimestamp();
        
        wp_schedule_event($next_3am_gmt, 'daily', 'cta_daily_cleanup');
    }
});

/**
 * Add admin menu for sitemap viewer
 */
function cta_add_sitemap_admin_menu() {
    add_submenu_page(
        'tools.php',
        'Sitemap Monitor',
        'Sitemap Monitor',
        'manage_options',
        'cta-sitemap',
        'cta_sitemap_admin_page'
    );
    
    add_submenu_page(
        'tools.php',
        'SEO Tools',
        'SEO Tools',
        'manage_options',
        'cta-seo-tools',
        'cta_seo_tools_admin_page'
    );
}
add_action('admin_menu', 'cta_add_sitemap_admin_menu', 20);

/**
 * Sitemap admin page
 */
function cta_sitemap_admin_page() {
    // Handle manual sitemap refresh
    if (isset($_POST['refresh_sitemap']) && check_admin_referer('cta_refresh_sitemap')) {
        // Clear all sitemap caches
        wp_cache_flush();
        delete_transient('wp_sitemap_index');
        delete_transient('cta_sitemap_last_ping');
        
        // Ping search engines
        cta_ping_search_engines();
        
        echo '<div class="notice notice-success"><p>Sitemap cache cleared and search engines pinged!</p></div>';
    }
    
    // Get sitemap stats
    $sitemap_url = home_url('/wp-sitemap.xml');
    
    // Count URLs in sitemap
    $pages_count = wp_count_posts('page')->publish;
    $posts_count = wp_count_posts('post')->publish;
    $courses_count = wp_count_posts('course')->publish ?? 0;
    $events_count = wp_count_posts('course_event')->publish ?? 0;
    $total_urls = $pages_count + $posts_count + $courses_count + $events_count;
    
    // Check if post types exist but have no content
    $warnings = [];
    if (post_type_exists('course_event') && $events_count == 0) {
        $warnings[] = 'No published scheduled courses/events yet. WordPress will not create a sitemap sub-file until at least 1 event is published.';
    }
    if (post_type_exists('course') && $courses_count == 0) {
        $warnings[] = 'No published courses yet. WordPress will not create a sitemap sub-file until at least 1 course is published.';
    }
    
    ?>
    <div class="wrap">
        <h1>📍 Sitemap Monitor</h1>
        
        <?php if (!empty($warnings)): ?>
        <div class="notice notice-warning">
            <p><strong>⚠️ Important Notes:</strong></p>
            <ul style="margin-left: 20px;">
                <?php foreach ($warnings as $warning): ?>
                <li><?php echo esc_html($warning); ?></li>
                <?php endforeach; ?>
            </ul>
            <p><em>WordPress sitemaps only include post types that have published content. This is normal behavior.</em></p>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Sitemap Overview</h2>
            <p><strong>Sitemap URL:</strong> <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank"><?php echo esc_html($sitemap_url); ?></a></p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Content Type</th>
                        <th>Published Count</th>
                        <th>In Sitemap?</th>
                        <th>Priority</th>
                        <th>Change Frequency</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Pages</strong></td>
                        <td><?php echo number_format($pages_count); ?></td>
                        <td><?php echo $pages_count > 0 ? '✅ Yes' : '❌ No (none published)'; ?></td>
                        <td>0.7 - 0.9</td>
                        <td>Weekly - Monthly</td>
                    </tr>
                    <tr>
                        <td><strong>Blog Posts</strong></td>
                        <td><?php echo number_format($posts_count); ?></td>
                        <td><?php echo $posts_count > 0 ? '✅ Yes' : '❌ No (none published)'; ?></td>
                        <td>0.6</td>
                        <td>Monthly</td>
                    </tr>
                    <tr>
                        <td><strong>Courses</strong></td>
                        <td><?php echo number_format($courses_count); ?></td>
                        <td><?php echo $courses_count > 0 ? '✅ Yes' : '❌ No (none published)'; ?></td>
                        <td>0.8</td>
                        <td>Weekly</td>
                    </tr>
                    <tr>
                        <td><strong>Upcoming Events</strong></td>
                        <td><?php echo number_format($events_count); ?></td>
                        <td><?php echo $events_count > 0 ? '✅ Yes' : '❌ No (none published)'; ?></td>
                        <td>0.85</td>
                        <td>Daily</td>
                    </tr>
                    <tr>
                        <td colspan="5" style="background: #f0f0f1; font-weight: bold;">
                            Total URLs Currently in Sitemap: <?php echo number_format($total_urls); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php if ($total_urls == 0): ?>
            <div class="notice notice-info inline">
                <p><strong>Getting Started:</strong> Your sitemap is ready but empty. Start by publishing some content:</p>
                <ul style="margin-left: 20px;">
                    <li><a href="<?php echo admin_url('post-new.php?post_type=page'); ?>">Add a Page</a></li>
                    <li><a href="<?php echo admin_url('post-new.php?post_type=course'); ?>">Add a Course</a></li>
                    <li><a href="<?php echo admin_url('post-new.php?post_type=course_event'); ?>">Add a Scheduled Course</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Recent Sitemap Activity</h2>
            <?php
            $last_ping = get_transient('cta_sitemap_last_ping');
            if ($last_ping) {
                echo '<p>✅ Last pinged search engines: <strong>' . human_time_diff($last_ping) . ' ago</strong></p>';
            } else {
                echo '<p>⏳ Search engines have not been pinged yet (or ping data expired).</p>';
            }
            ?>
            
            <form method="post">
                <?php wp_nonce_field('cta_refresh_sitemap'); ?>
                <button type="submit" name="refresh_sitemap" class="button button-primary">
                    🔄 Refresh Sitemap & Ping Search Engines
                </button>
            </form>
            <p class="description">This will clear the sitemap cache and notify Google and Bing of the update.</p>
        </div>
        
        <div class="card">
            <h2>What's Included in Your Sitemap</h2>
            <p>Your sitemap includes <strong>individual pages and posts</strong> only:</p>
            <ul>
                <li>✅ Individual Pages (About, Contact, etc.)</li>
                <li>✅ Individual Blog Posts</li>
                <li>✅ Individual Course Pages</li>
                <li>✅ Individual Event/Session Pages</li>
            </ul>
            <p><strong>Excluded from sitemap:</strong></p>
            <ul>
                <li>❌ Category archives (not indexed)</li>
                <li>❌ Tag archives (not indexed)</li>
                <li>❌ Author archives (not indexed)</li>
            </ul>
            <p class="description">This focuses search engines on your actual content pages, not archive/listing pages.</p>
        </div>
        
        <div class="card">
            <h2>High-Priority Pages</h2>
            <ul>
                <li>CQC Compliance Hub (0.9 priority)</li>
                <li>Training Guides (0.9 priority)</li>
                <li>Downloadable Resources (0.9 priority)</li>
                <li>FAQs (0.9 priority)</li>
                <li>Group Training (0.9 priority)</li>
                <li>Contact (0.9 priority)</li>
                <li>All Individual Courses (0.8 priority)</li>
                <li>All Upcoming Events (0.85 priority)</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Submit Sitemap to Search Engines</h2>
            <p>To improve indexing, submit your sitemap directly to search engines:</p>
            <ul>
                <li>
                    <strong>Google Search Console:</strong>
                    <a href="https://search.google.com/search-console" target="_blank">Add sitemap here</a>
                    <br><code><?php echo esc_html($sitemap_url); ?></code>
                </li>
                <li>
                    <strong>Bing Webmaster Tools:</strong>
                    <a href="https://www.bing.com/webmasters" target="_blank">Add sitemap here</a>
                    <br><code><?php echo esc_html($sitemap_url); ?></code>
                </li>
            </ul>
        </div>
        
        <div class="card">
            <h2>How Automatic Updates Work</h2>
            <p>Your sitemap now updates automatically when:</p>
            <ul>
                <li>✅ A new page is published or updated</li>
                <li>✅ A new course is added or modified</li>
                <li>✅ A new event is scheduled or changed</li>
                <li>✅ A blog post is published or updated</li>
                <li>✅ Course categories are added or changed</li>
            </ul>
            <p>Search engines are automatically notified (max once per hour to avoid spam).</p>
        </div>
    </div>
    <?php
}

/**
 * =========================================
 * SITEMAP DIAGNOSTIC TOOL
 * =========================================
 */

/**
 * Add sitemap diagnostic admin notice
 */
function cta_sitemap_diagnostic_notice() {
    // Only show on dashboard and settings pages
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, ['dashboard', 'options-reading', 'options-permalink'])) {
        return;
    }
    
    $issues = [];
    $warnings = [];
    $fixes = [];
    
    // Check 1: Search engine visibility
    if (get_option('blog_public') == 0) {
        $issues[] = 'Search engine visibility is disabled';
        $fixes[] = [
            'label' => 'Enable search engine visibility',
            'url' => admin_url('options-reading.php'),
            'action' => 'enable_visibility',
        ];
    }
    
    // Check 2: Permalink structure
    $permalink_structure = get_option('permalink_structure');
    if (empty($permalink_structure)) {
        $issues[] = 'Permalink structure is set to "Plain" (sitemaps require pretty permalinks)';
        $fixes[] = [
            'label' => 'Fix permalink structure',
            'url' => admin_url('options-permalink.php'),
            'action' => 'fix_permalinks',
        ];
    }
    
    // Check 3: WordPress version (sitemaps require 5.5+)
    global $wp_version;
    if (version_compare($wp_version, '5.5', '<')) {
        $warnings[] = 'WordPress version ' . $wp_version . ' is below 5.5 (sitemaps require 5.5+)';
    }
    
    // Check 4: Test sitemap URL
    $sitemap_url = home_url('/wp-sitemap.xml');
    $sitemap_accessible = false;
    
    // Try to check if sitemap is accessible (non-blocking check)
    $response = wp_remote_head($sitemap_url, [
        'timeout' => 5,
        'sslverify' => false,
    ]);
    
    if (!is_wp_error($response)) {
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code == 200) {
            $sitemap_accessible = true;
        } elseif ($status_code == 404) {
            $issues[] = 'Sitemap returns 404 (not found)';
            if (empty($permalink_structure)) {
                $fixes[] = [
                    'label' => 'Flush rewrite rules',
                    'url' => admin_url('options-permalink.php'),
                    'action' => 'flush_rewrite',
                ];
            }
        } else {
            $warnings[] = 'Sitemap returned HTTP ' . $status_code;
        }
    } else {
        // Can't check remotely, but that's okay - might be localhost or firewall
        $warnings[] = 'Could not verify sitemap accessibility (this is normal on localhost)';
    }
    
    // Only show notice if there are issues or warnings
    if (empty($issues) && empty($warnings)) {
        return;
    }
    
    $notice_class = !empty($issues) ? 'notice-error' : 'notice-warning';
    ?>
    <div class="notice <?php echo esc_attr($notice_class); ?> is-dismissible">
        <h3 style="margin-top: 0;">🔍 Sitemap Diagnostic</h3>
        
        <?php if (!empty($issues)) : ?>
        <p><strong>Issues Found:</strong></p>
        <ul style="margin-left: 20px;">
            <?php foreach ($issues as $issue) : ?>
            <li><?php echo esc_html($issue); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        
        <?php if (!empty($warnings)) : ?>
        <p><strong>Warnings:</strong></p>
        <ul style="margin-left: 20px;">
            <?php foreach ($warnings as $warning) : ?>
            <li><?php echo esc_html($warning); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        
        <?php if ($sitemap_accessible) : ?>
        <p style="color: #00a32a; font-weight: 600;">✅ Sitemap is accessible at: <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank"><?php echo esc_html($sitemap_url); ?></a></p>
        <?php else : ?>
        <p><strong>Sitemap URL:</strong> <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank"><?php echo esc_html($sitemap_url); ?></a></p>
        <?php endif; ?>
        
        <?php if (!empty($fixes)) : ?>
        <p style="margin-top: 15px;"><strong>Quick Fixes:</strong></p>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
            <?php foreach ($fixes as $fix) : ?>
                <?php if ($fix['action'] === 'enable_visibility') : ?>
                    <a href="<?php echo esc_url($fix['url']); ?>" class="button button-primary"><?php echo esc_html($fix['label']); ?></a>
                <?php elseif ($fix['action'] === 'fix_permalinks') : ?>
                    <a href="<?php echo esc_url($fix['url']); ?>" class="button button-primary"><?php echo esc_html($fix['label']); ?></a>
                <?php elseif ($fix['action'] === 'flush_rewrite') : ?>
                    <a href="<?php echo esc_url($fix['url']); ?>" class="button"><?php echo esc_html($fix['label']); ?></a>
                <?php else : ?>
                    <a href="<?php echo esc_url($fix['url']); ?>" class="button"><?php echo esc_html($fix['label']); ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <p style="margin-top: 15px; font-size: 12px; color: #646970;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=cta-sitemap-diagnostic')); ?>">View detailed diagnostic &rarr;</a> | 
            <a href="https://search.google.com/search-console" target="_blank">Open Google Search Console &rarr;</a>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'cta_sitemap_diagnostic_notice');

/**
 * Handle one-click fixes via AJAX
 */
function cta_sitemap_fix_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cta_sitemap_fix')) {
        wp_send_json_error(['message' => 'Invalid security token']);
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    
    switch ($action) {
        case 'enable_visibility':
            update_option('blog_public', 1);
            wp_send_json_success(['message' => 'Search engine visibility enabled']);
            break;
            
        case 'fix_permalinks':
            if (empty(get_option('permalink_structure'))) {
                update_option('permalink_structure', '/%postname%/');
            }
            flush_rewrite_rules(false);
            wp_send_json_success(['message' => 'Permalink structure fixed and rewrite rules flushed']);
            break;
            
        case 'flush_rewrite':
            flush_rewrite_rules(false);
            wp_send_json_success(['message' => 'Rewrite rules flushed']);
            break;
            
        default:
            wp_send_json_error(['message' => 'Unknown action']);
    }
}
add_action('wp_ajax_cta_sitemap_fix', 'cta_sitemap_fix_handler');

/**
 * Add sitemap diagnostic admin page
 */
function cta_add_sitemap_diagnostic_page() {
    add_submenu_page(
        'tools.php',
        'Sitemap Diagnostic',
        'Sitemap Diagnostic',
        'manage_options',
        'cta-sitemap-diagnostic',
        'cta_sitemap_diagnostic_page'
    );
}
add_action('admin_menu', 'cta_add_sitemap_diagnostic_page');

/**
 * Sitemap diagnostic page content
 */
function cta_sitemap_diagnostic_page() {
    $sitemap_url = home_url('/wp-sitemap.xml');
    $sitemap_index_url = home_url('/wp-sitemap.xml');
    
    // Run diagnostics
    $checks = [
        'search_engine_visibility' => get_option('blog_public') == 1,
        'permalink_structure' => !empty(get_option('permalink_structure')),
        'wp_version' => version_compare($GLOBALS['wp_version'], '5.5', '>='),
    ];
    
    // Test sitemap accessibility
    $response = wp_remote_get($sitemap_url, [
        'timeout' => 10,
        'sslverify' => false,
    ]);
    
    $sitemap_status = 'unknown';
    $sitemap_content = '';
    
    if (!is_wp_error($response)) {
        $status_code = wp_remote_retrieve_response_code($response);
        $sitemap_status = $status_code == 200 ? 'accessible' : 'error';
        $sitemap_content = wp_remote_retrieve_body($response);
    } else {
        $sitemap_status = 'error';
        $sitemap_content = $response->get_error_message();
    }
    
    ?>
    <div class="wrap">
        <h1>Sitemap Diagnostic Tool</h1>
        
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2>Quick Status Check</h2>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Check</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Search Engine Visibility</strong></td>
                        <td>
                            <?php if ($checks['search_engine_visibility']) : ?>
                                <span style="color: #00a32a;">✅ Enabled</span>
                            <?php else : ?>
                                <span style="color: #d63638;">❌ Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$checks['search_engine_visibility']) : ?>
                                <a href="<?php echo admin_url('options-reading.php'); ?>" class="button button-primary">Enable</a>
                            <?php else : ?>
                                <span style="color: #646970;">No action needed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Permalink Structure</strong></td>
                        <td>
                            <?php if ($checks['permalink_structure']) : ?>
                                <span style="color: #00a32a;">✅ Configured</span>
                            <?php else : ?>
                                <span style="color: #d63638;">❌ Set to "Plain"</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$checks['permalink_structure']) : ?>
                                <a href="<?php echo admin_url('options-permalink.php'); ?>" class="button button-primary">Configure</a>
                            <?php else : ?>
                                <a href="<?php echo admin_url('options-permalink.php'); ?>" class="button">Re-flush Rules</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>WordPress Version</strong></td>
                        <td>
                            <?php if ($checks['wp_version']) : ?>
                                <span style="color: #00a32a;">✅ <?php echo esc_html($GLOBALS['wp_version']); ?> (5.5+)</span>
                            <?php else : ?>
                                <span style="color: #d63638;">❌ <?php echo esc_html($GLOBALS['wp_version']); ?> (requires 5.5+)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$checks['wp_version']) : ?>
                                <a href="<?php echo admin_url('update-core.php'); ?>" class="button">Update WordPress</a>
                            <?php else : ?>
                                <span style="color: #646970;">No action needed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Sitemap Accessibility</strong></td>
                        <td>
                            <?php if ($sitemap_status === 'accessible') : ?>
                                <span style="color: #00a32a;">✅ Accessible</span>
                            <?php elseif ($sitemap_status === 'error') : ?>
                                <span style="color: #d63638;">❌ Error</span>
                            <?php else : ?>
                                <span style="color: #d63638;">❌ Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank" class="button">Test URL</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2>Sitemap URLs</h2>
            <p>Submit these URLs to Google Search Console:</p>
            <ul>
                <li><strong>Main Sitemap Index:</strong> <a href="<?php echo esc_url($sitemap_index_url); ?>" target="_blank"><?php echo esc_html($sitemap_index_url); ?></a></li>
            </ul>
            <p>
                <a href="https://search.google.com/search-console" target="_blank" class="button button-primary">Open Google Search Console &rarr;</a>
            </p>
        </div>
        
        <?php if ($sitemap_status === 'accessible' && !empty($sitemap_content)) : ?>
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2>Sitemap Preview</h2>
            <textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px;"><?php echo esc_textarea(substr($sitemap_content, 0, 2000)); ?><?php echo strlen($sitemap_content) > 2000 ? '...' : ''; ?></textarea>
        </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2>Next Steps</h2>
            <ol>
                <li>Fix any issues shown above</li>
                <li>Verify sitemap is accessible by clicking "Test URL"</li>
                <li>Submit sitemap to <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                <li>Wait 24-48 hours for Google to process</li>
            </ol>
        </div>
    </div>
    <?php
}

/**
 * =========================================
 * ROBOTS.TXT CUSTOMIZATION
 * =========================================
 */

/**
 * Customize robots.txt output
 */
function cta_robots_txt($output, $public) {
    if (!$public) {
        return $output;
    }
    
    $output = "# robots.txt for Continuity Training Academy\n";
    $output .= "# Generated by WordPress\n\n";
    
    // Default rules for all bots
    $output .= "User-agent: *\n";
    $output .= "Allow: /\n\n";
    $output .= "# Disallow admin and private areas\n";
    $output .= "Disallow: /wp-admin/\n";
    $output .= "Disallow: /wp-includes/\n";
    $output .= "Disallow: /wp-content/plugins/\n";
    $output .= "Disallow: /wp-content/themes/\n";
    $output .= "Disallow: /wp-content/uploads/\n";
    $output .= "Disallow: /wp-json/\n";
    $output .= "Disallow: /?s=\n";
    $output .= "Disallow: /search/\n\n";
    
    // Block AI training crawlers (protect proprietary content)
    $output .= "# AI Training Bots - Blocked\n";
    $training_bots = [
        'GPTBot',           // OpenAI training
        'CCBot',            // Common Crawl (trains many AI models)
        'Google-Extended',  // Google Bard training
        'anthropic-ai',     // Claude training
        'ClaudeBot',        // Anthropic crawler
        'Omgilibot',        // Omgili training
        'FacebookBot',      // Meta AI training
        'Diffbot',          // AI training dataset
    ];
    
    foreach ($training_bots as $bot) {
        $output .= "User-agent: {$bot}\n";
        $output .= "Disallow: /\n\n";
    }
    
    // Allow AI search engines (good for discovery)
    $output .= "# AI Search Bots - Allowed\n";
    $search_bots = [
        'PerplexityBot',    // Perplexity AI search
        'YouBot',           // You.com search
        'ChatGPT-User',     // OpenAI search (not training)
    ];
    
    foreach ($search_bots as $bot) {
        $output .= "User-agent: {$bot}\n";
        $output .= "Allow: /\n\n";
    }
    
    // Allow sitemap
    $output .= "# Allow sitemap\n";
    $output .= "Allow: /wp-sitemap.xml\n";
    $output .= "Allow: /sitemap.xml\n\n";
    $output .= "# Sitemap location\n";
    $output .= "Sitemap: " . home_url('/wp-sitemap.xml') . "\n";
    
    return $output;
}
add_filter('robots_txt', 'cta_robots_txt', 10, 2);

/**
 * SEO Tools admin page
 */
function cta_seo_tools_admin_page() {
    // Handle CSV import
    $import_result = null;
    if (isset($_POST['import_csv']) && check_admin_referer('cta_import_seo_csv')) {
        $import_result = cta_import_meta_descriptions_from_csv();
    }
    
    // Get stats
    $pages_with_meta = 0;
    $pages_without_meta = 0;
    
    $post_types = ['course', 'course_event', 'post', 'page'];
    foreach ($post_types as $post_type) {
        $posts = get_posts([
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ]);
        
        foreach ($posts as $post_id) {
            $has_meta = false;
            if ($post_type === 'course') {
                $has_meta = !empty(cta_safe_get_field('course_seo_meta_description', $post_id, ''));
            } elseif ($post_type === 'course_event') {
                $has_meta = !empty(cta_safe_get_field('event_seo_meta_description', $post_id, ''));
            } elseif ($post_type === 'post') {
                $has_meta = !empty(cta_safe_get_field('news_meta_description', $post_id, ''));
            } else {
                $has_meta = !empty(cta_safe_get_field('seo_meta_description', $post_id, ''));
            }
            
            if ($has_meta) {
                $pages_with_meta++;
            } else {
                $pages_without_meta++;
            }
        }
    }
    
    $csv_path = get_template_directory() . '/data/seo_meta_descriptions.csv';
    $csv_exists = file_exists($csv_path);
    
    ?>
    <div class="wrap">
        <h1>SEO Tools</h1>
        
        <?php if ($import_result) : ?>
            <div class="notice notice-<?php echo $import_result['success'] ? 'success' : 'error'; ?> is-dismissible">
                <p><strong><?php echo esc_html($import_result['message']); ?></strong></p>
                <?php if (!empty($import_result['errors']) && count($import_result['errors']) <= 10) : ?>
                    <ul>
                        <?php foreach ($import_result['errors'] as $error) : ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2>Meta Descriptions</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <h3 style="margin: 0 0 10px 0;">Pages with Meta Descriptions</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0; color: #00a32a;"><?php echo $pages_with_meta; ?></p>
                </div>
                <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <h3 style="margin: 0 0 10px 0;">Pages Missing Meta Descriptions</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 0; color: #d63638;"><?php echo $pages_without_meta; ?></p>
                </div>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                <h3>Import Meta Descriptions from CSV</h3>
                
                <?php if ($csv_exists) : ?>
                    <p>CSV file found at: <code><?php echo esc_html($csv_path); ?></code></p>
                    <p class="description">
                        This will import meta descriptions from the CSV file. Only pages without existing descriptions will be updated (merge mode).
                    </p>
                    <form method="post" style="margin-top: 15px;">
                        <?php wp_nonce_field('cta_import_seo_csv'); ?>
                        <button type="submit" name="import_csv" class="button button-primary" onclick="return confirm('This will import meta descriptions for all pages in the CSV. Continue?');">
                            Import Meta Descriptions from CSV
                        </button>
                    </form>
                <?php else : ?>
                    <div class="notice notice-warning">
                        <p><strong>CSV file not found.</strong></p>
                        <p>Expected location: <code><?php echo esc_html($csv_path); ?></code></p>
                        <p>Please ensure the CSV file is placed in the theme's <code>data/</code> directory.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                <h3>Auto-Generation</h3>
                <p>Meta descriptions are automatically generated for new posts when saved if no custom description is set.</p>
                <p class="description">
                    <strong>Fallback hierarchy:</strong><br>
                    1. ACF custom field (if set)<br>
                    2. Post excerpt (if exists and 120-160 chars)<br>
                    3. Auto-generated from template (based on post type)<br>
                    4. Generic fallback (never empty)
                </p>
            </div>
        </div>
        
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2>Broken Link Detection</h2>
            
            <?php
            // Check for known broken links
            $broken_links = [];
            
            // Check award post
            $award_post = get_page_by_path('maidstone-national-award-first-dec21', OBJECT, 'post');
            if ($award_post) {
                $content = $award_post->post_content;
                // Look for links
                preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $url) {
                        $parsed = wp_parse_url($url);
                        if ($parsed && isset($parsed['host'])) {
                            // External link - would need to check if it's broken
                            $broken_links[] = [
                                'post' => $award_post->post_title,
                                'url' => $url,
                                'type' => 'external',
                                'edit_link' => get_edit_post_link($award_post->ID)
                            ];
                        } else {
                            // Internal link
                            $post_id = url_to_postid($url);
                            if (!$post_id) {
                                $broken_links[] = [
                                    'post' => $award_post->post_title,
                                    'url' => $url,
                                    'type' => 'internal',
                                    'edit_link' => get_edit_post_link($award_post->ID)
                                ];
                            }
                        }
                    }
                }
            }
            
            // Check moving & handling post
            $moving_post = get_page_by_path('moving-handling-reducing-workplace-jan25', OBJECT, 'post');
            if ($moving_post) {
                $content = $moving_post->post_content;
                preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $url) {
                        $parsed = wp_parse_url($url);
                        if ($parsed && isset($parsed['host']) && $parsed['host'] !== $_SERVER['HTTP_HOST']) {
                            // External link - mark as potentially broken (needs manual verification)
                            $broken_links[] = [
                                'post' => $moving_post->post_title,
                                'url' => $url,
                                'type' => 'external',
                                'edit_link' => get_edit_post_link($moving_post->ID)
                            ];
                        }
                    }
                }
            }
            ?>
            
            <?php if (!empty($broken_links)) : ?>
                <div class="notice notice-warning" style="margin-top: 15px;">
                    <p><strong>Potential broken links found:</strong></p>
                    <ul>
                        <?php foreach ($broken_links as $link) : ?>
                            <li>
                                <strong><?php echo esc_html($link['post']); ?></strong>: 
                                <a href="<?php echo esc_url($link['url']); ?>" target="_blank"><?php echo esc_html($link['url']); ?></a>
                                (<?php echo esc_html($link['type']); ?>)
                                <a href="<?php echo esc_url($link['edit_link']); ?>" class="button button-small" style="margin-left: 10px;">Edit Post</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="description">Please verify these links manually and update or remove them if they are broken.</p>
                </div>
            <?php else : ?>
                <p>No broken links detected in the specified posts. Note: This is a basic check. External links should be verified manually.</p>
            <?php endif; ?>
            
            <p class="description" style="margin-top: 15px;">
                <strong>Known issues to check:</strong><br>
                - <code>maidstone-national-award-first-dec21</code>: Check for broken internal links<br>
                - <code>moving-handling-reducing-workplace-jan25</code>: Check for broken external links
            </p>
        </div>
        
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2>Orphan Pages Detection</h2>
            
            <?php
            // Find orphan pages (pages with only 1 internal link)
            $orphan_pages = [];
            $all_pages = get_posts([
                'post_type' => ['page', 'course', 'course_event', 'post'],
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'fields' => 'ids'
            ]);
            
            foreach ($all_pages as $page_id) {
                // Count internal links to this page
                $link_count = 0;
                
                // Search for links to this page in post content
                $posts = get_posts([
                    'post_type' => ['page', 'course', 'course_event', 'post'],
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'exclude' => [$page_id]
                ]);
                
                $page_url = get_permalink($page_id);
                $page_slug = get_post_field('post_name', $page_id);
                
                foreach ($posts as $post) {
                    $content = $post->post_content;
                    // Count occurrences of this page's URL or slug
                    if (strpos($content, $page_url) !== false || strpos($content, $page_slug) !== false) {
                        $link_count++;
                    }
                }
                
                // Check menu items
                $menu_items = wp_get_nav_menu_items('primary');
                if ($menu_items) {
                    foreach ($menu_items as $item) {
                        if ($item->object_id == $page_id) {
                            $link_count++;
                        }
                    }
                }
                
                if ($link_count <= 1) {
                    $orphan_pages[] = [
                        'id' => $page_id,
                        'title' => get_the_title($page_id),
                        'url' => $page_url,
                        'type' => get_post_type($page_id),
                        'link_count' => $link_count,
                        'edit_link' => get_edit_post_link($page_id)
                    ];
                }
            }
            ?>
            
            <?php if (!empty($orphan_pages)) : ?>
                <div class="notice notice-warning" style="margin-top: 15px;">
                    <p><strong>Found <?php echo count($orphan_pages); ?> orphan pages (with 1 or fewer internal links):</strong></p>
                    <ul style="margin-top: 10px;">
                        <?php foreach (array_slice($orphan_pages, 0, 20) as $orphan) : ?>
                            <li>
                                <strong><?php echo esc_html($orphan['title']); ?></strong> 
                                (<?php echo esc_html($orphan['type']); ?>) - 
                                <?php echo $orphan['link_count']; ?> link(s)
                                <a href="<?php echo esc_url($orphan['edit_link']); ?>" class="button button-small" style="margin-left: 10px;">Edit</a>
                                <a href="<?php echo esc_url($orphan['url']); ?>" target="_blank" class="button button-small">View</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($orphan_pages) > 20) : ?>
                        <p class="description">... and <?php echo count($orphan_pages) - 20; ?> more. Add internal links to these pages from relevant content.</p>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <p>No orphan pages detected. All pages have sufficient internal linking.</p>
            <?php endif; ?>
        </div>
        
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2>Content Audit - Thin Content Detection</h2>
            
            <?php
            // Find pages with low text-to-HTML ratio
            $thin_pages = [];
            $all_content = get_posts([
                'post_type' => ['page', 'course', 'post'],
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ]);
            
            foreach ($all_content as $post) {
                $content = $post->post_content;
                $text_content = wp_strip_all_tags($content);
                $text_length = strlen($text_content);
                $html_length = strlen($content);
                
                if ($html_length > 0) {
                    $text_ratio = ($text_length / $html_length) * 100;
                    
                    // Flag pages with less than 20% text content or less than 300 words
                    if ($text_ratio < 20 || str_word_count($text_content) < 300) {
                        $thin_pages[] = [
                            'id' => $post->ID,
                            'title' => get_the_title($post->ID),
                            'url' => get_permalink($post->ID),
                            'type' => $post->post_type,
                            'text_ratio' => round($text_ratio, 1),
                            'word_count' => str_word_count($text_content),
                            'edit_link' => get_edit_post_link($post->ID)
                        ];
                    }
                }
            }
            
            // Sort by word count (lowest first)
            usort($thin_pages, function($a, $b) {
                return $a['word_count'] - $b['word_count'];
            });
            ?>
            
            <?php if (!empty($thin_pages)) : ?>
                <div class="notice notice-warning" style="margin-top: 15px;">
                    <p><strong>Found <?php echo count($thin_pages); ?> pages with thin content (low text-to-HTML ratio or <300 words):</strong></p>
                    <table class="widefat" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th>Type</th>
                                <th>Word Count</th>
                                <th>Text Ratio</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($thin_pages, 0, 30) as $thin) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($thin['title']); ?></strong></td>
                                    <td><?php echo esc_html($thin['type']); ?></td>
                                    <td><?php echo $thin['word_count']; ?> words</td>
                                    <td><?php echo $thin['text_ratio']; ?>%</td>
                                    <td>
                                        <a href="<?php echo esc_url($thin['edit_link']); ?>" class="button button-small">Edit</a>
                                        <a href="<?php echo esc_url($thin['url']); ?>" target="_blank" class="button button-small">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($thin_pages) > 30) : ?>
                        <p class="description" style="margin-top: 10px;">... and <?php echo count($thin_pages) - 30; ?> more pages. Consider adding more content to improve SEO.</p>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <p>No thin content pages detected. All pages have sufficient text content.</p>
            <?php endif; ?>
            
            <p class="description" style="margin-top: 15px;">
                <strong>Recommendations:</strong><br>
                - Add 300+ words of unique content per page<br>
                - Aim for text-to-HTML ratio above 20%<br>
                - Add course descriptions, FAQs, or relevant information
            </p>
        </div>
        
        <div class="card" style="max-width: 1200px; margin-top: 20px;">
            <h2>Fix Duplicate Course URLs</h2>
            
            <?php
            // Handle URL fix action
            $fix_result = null;
            if (isset($_POST['fix_duplicate_urls']) && check_admin_referer('cta_fix_duplicate_urls')) {
                $fix_result = cta_fix_duplicate_course_urls();
            }
            
            // Check current status
            $duplicate_urls = [
                'adult-social-care-certificate' => 'adult-social-care-certificate-3d-l2',
                'emergency-first-aid-at-work' => 'emergency-first-aid-at-work-1d-l3',
                'medication-competency-management' => 'medication-competency-management-1d-l3',
                'moving-positioning-inc-hoist' => 'moving-positioning-inc-hoist-1d-l3',
            ];
            
            $status = [];
            foreach ($duplicate_urls as $old_slug => $new_slug) {
                $old_course = get_page_by_path($old_slug, OBJECT, 'course');
                $new_course = get_page_by_path($new_slug, OBJECT, 'course');
                
                if ($old_course && $new_course && $old_course->ID === $new_course->ID) {
                    $status[$old_slug] = ['status' => 'fixed', 'message' => 'Already correct'];
                } elseif ($old_course && !$new_course) {
                    $status[$old_slug] = ['status' => 'needs_fix', 'course_id' => $old_course->ID, 'current' => $old_slug, 'target' => $new_slug];
                } elseif (!$old_course && $new_course) {
                    $status[$old_slug] = ['status' => 'fixed', 'message' => 'Already correct'];
                } else {
                    $status[$old_slug] = ['status' => 'not_found', 'message' => 'Course not found'];
                }
            }
            ?>
            
            <?php if ($fix_result) : ?>
                <div class="notice notice-<?php echo !empty($fix_result['errors']) ? 'warning' : 'success'; ?> is-dismissible" style="margin-top: 15px;">
                    <?php if (!empty($fix_result['updated'])) : ?>
                        <p><strong>Updated:</strong></p>
                        <ul>
                            <?php foreach ($fix_result['updated'] as $msg) : ?>
                                <li><?php echo esc_html($msg); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($fix_result['errors'])) : ?>
                        <p><strong>Errors:</strong></p>
                        <ul>
                            <?php foreach ($fix_result['errors'] as $msg) : ?>
                                <li><?php echo esc_html($msg); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <p>Some courses have duplicate URLs without duration/level suffixes. This tool updates them to the correct format.</p>
            
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Current Slug</th>
                        <th>Target Slug</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($status as $old_slug => $info) : ?>
                        <tr>
                            <td><code><?php echo esc_html($old_slug); ?></code></td>
                            <td><code><?php echo esc_html($duplicate_urls[$old_slug]); ?></code></td>
                            <td>
                                <?php if ($info['status'] === 'fixed') : ?>
                                    <span style="color: #00a32a;">✓ <?php echo esc_html($info['message']); ?></span>
                                <?php elseif ($info['status'] === 'needs_fix') : ?>
                                    <span style="color: #d63638;">⚠ Needs Fix</span>
                                <?php else : ?>
                                    <span style="color: #d63638;">✗ <?php echo esc_html($info['message']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($info['status'] === 'needs_fix') : ?>
                                    <a href="<?php echo esc_url(get_edit_post_link($info['course_id'])); ?>" class="button button-small">Edit Course</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (array_filter($status, function($s) { return $s['status'] === 'needs_fix'; })) : ?>
                <form method="post" style="margin-top: 20px;">
                    <?php wp_nonce_field('cta_fix_duplicate_urls'); ?>
                    <button type="submit" name="fix_duplicate_urls" class="button button-primary" onclick="return confirm('This will update course slugs to the correct format. Continue?');">
                        Fix Duplicate URLs
                    </button>
                </form>
            <?php else : ?>
                <p class="description" style="margin-top: 15px; color: #00a32a;">
                    <strong>✓ All course URLs are correct!</strong>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Fix duplicate course URLs
 * Updates course slugs to match the correct format (with duration and level)
 */
function cta_fix_duplicate_course_urls() {
    $fixes = [
        'adult-social-care-certificate' => 'adult-social-care-certificate-3d-l2',
        'emergency-first-aid-at-work' => 'emergency-first-aid-at-work-1d-l3',
        'medication-competency-management' => 'medication-competency-management-1d-l3',
        'moving-positioning-inc-hoist' => 'moving-positioning-inc-hoist-1d-l3',
    ];
    
    $updated = [];
    $errors = [];
    
    foreach ($fixes as $old_slug => $new_slug) {
        // Find course with old slug
        $course = get_page_by_path($old_slug, OBJECT, 'course');
        
        if (!$course) {
            // Check if it's already correct
            $check_course = get_page_by_path($new_slug, OBJECT, 'course');
            if ($check_course) {
                $updated[] = "✓ {$old_slug} → Already correct ({$new_slug})";
                continue;
            }
            $errors[] = "✗ {$old_slug} → Course not found";
            continue;
        }
        
        // Check if new slug already exists (different course)
        $existing = get_page_by_path($new_slug, OBJECT, 'course');
        if ($existing && $existing->ID !== $course->ID) {
            $errors[] = "✗ {$old_slug} → New slug {$new_slug} already exists (ID: {$existing->ID})";
            continue;
        }
        
        // Update the slug
        remove_action('acf/save_post', 'cta_auto_generate_course_slug_on_acf_save', 20);
        $result = wp_update_post([
            'ID' => $course->ID,
            'post_name' => $new_slug,
        ], true);
        add_action('acf/save_post', 'cta_auto_generate_course_slug_on_acf_save', 20);
        
        if (is_wp_error($result)) {
            $errors[] = "✗ {$old_slug} → Error: " . $result->get_error_message();
        } else {
            $updated[] = "✓ {$old_slug} → {$new_slug} (ID: {$course->ID})";
            // Flush rewrite rules
            flush_rewrite_rules(false);
        }
    }
    
    return [
        'updated' => $updated,
        'errors' => $errors,
    ];
}

/**
 * =========================================
 * PERFORMANCE & TECHNICAL SEO
 * =========================================
 */

/**
 * Add preconnect hints for external resources
 */
function cta_resource_hints($urls, $relation_type) {
    if ($relation_type === 'preconnect') {
        $urls[] = [
            'href' => 'https://fonts.googleapis.com',
            'crossorigin' => true,
        ];
        $urls[] = [
            'href' => 'https://fonts.gstatic.com',
            'crossorigin' => true,
        ];
        $urls[] = [
            'href' => 'https://cdnjs.cloudflare.com',
            'crossorigin' => true,
        ];
    }
    return $urls;
}
add_filter('wp_resource_hints', 'cta_resource_hints', 10, 2);

/**
 * Remove unnecessary meta tags and prevent duplicate canonicals
 */
function cta_cleanup_head() {
    // Remove WordPress version
    remove_action('wp_head', 'wp_generator');
    
    // Remove RSD link
    remove_action('wp_head', 'rsd_link');
    
    // Remove wlwmanifest link
    remove_action('wp_head', 'wlwmanifest_link');
    
    // Remove shortlink
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Remove REST API link
    remove_action('wp_head', 'rest_output_link_wp_head');
    
    // Remove oEmbed discovery links
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    
    // IMPORTANT: Remove WordPress default canonical to prevent duplicates
    // We output our own canonical in cta_seo_meta_tags()
    remove_action('wp_head', 'rel_canonical');
    
    // Also remove Yoast/RankMath canonical if they're active (we handle it ourselves)
    // This prevents conflicts with SEO plugins
    add_filter('wpseo_canonical', '__return_false'); // Yoast
    add_filter('rank_math/frontend/canonical', '__return_false'); // RankMath
}
add_action('after_setup_theme', 'cta_cleanup_head');

/**
 * Add robots meta for specific pages
 */
function cta_robots_meta($robots) {
    // Check per-page settings first (from SEO Controls meta box)
    if (is_singular()) {
        $post_id = get_queried_object_id();
        $noindex = get_post_meta($post_id, '_cta_noindex', true);
        $nofollow = get_post_meta($post_id, '_cta_nofollow', true);
        
        if ($noindex === '1') {
            $robots['noindex'] = true;
        }
        if ($nofollow === '1') {
            $robots['nofollow'] = true;
        }
    }
    
    // No-index search results
    if (is_search()) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    
    // No-index 404 pages
    if (is_404()) {
        $robots['noindex'] = true;
    }
    
    // No-index paginated archives after page 1
    if (is_paged()) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }
    
    // No-index tag, author, and date archives (low value, per SEO docs)
    if (is_tag() || is_author() || is_date()) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }
    
    return $robots;
}
add_filter('wp_robots', 'cta_robots_meta');

/**
 * Redirect to canonical URL if current URL has query params or wrong format
 * This enforces the canonical URL and prevents duplicate content
 */
function cta_enforce_canonical_redirect() {
    // Don't redirect in admin, AJAX, or for logged-in users editing
    if (is_admin() || wp_doing_ajax() || is_preview()) {
        return;
    }
    
    // Don't redirect search or 404
    if (is_search() || is_404()) {
        return;
    }
    
    // Don't redirect POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }
    
    // Get current URL
    $current_url = (is_ssl() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // Get the canonical URL for this page
    $canonical_url = '';
    
    if (is_singular()) {
        $canonical_url = get_permalink();
    } elseif (is_post_type_archive()) {
        $canonical_url = get_post_type_archive_link(get_post_type());
    } elseif (is_tax()) {
        $term = get_queried_object();
        $canonical_url = get_term_link($term);
    } elseif (is_home() || is_front_page()) {
        $canonical_url = home_url('/');
    }
    
    if (empty($canonical_url) || is_wp_error($canonical_url)) {
        return;
    }
    
    // Parse URLs to compare paths
    $current_parsed = wp_parse_url($current_url);
    $canonical_parsed = wp_parse_url($canonical_url);
    
    if (!$current_parsed || !$canonical_parsed) {
        return;
    }
    
    $current_path = untrailingslashit($current_parsed['path'] ?? '');
    $canonical_path = untrailingslashit($canonical_parsed['path'] ?? '');
    
    // Only redirect if:
    // 1. The paths are different AND
    // 2. The current URL has query params (common case for tracking params) OR
    // 3. The current path contains the canonical path but has extra segments (like /news.html appended)
    $has_query = !empty($current_parsed['query']);
    $path_mismatch = $current_path !== $canonical_path;
    
    // Check if current path has extra segments that shouldn't be there
    // e.g., if canonical is /post-slug and current is /post-slug/news.html
    $has_extra_segments = $path_mismatch && strpos($current_path, $canonical_path) === 0 && 
                          strlen($current_path) > strlen($canonical_path);
    
    // Only redirect if we have query params (safe redirect) or if there are clearly wrong extra segments
    if ($path_mismatch && ($has_query || $has_extra_segments)) {
        // For extra segments, be more careful - only redirect if it's clearly wrong
        if ($has_extra_segments && !$has_query) {
            // Check if the extra segment looks like a mistake (ends with .html when it shouldn't)
            $extra = substr($current_path, strlen($canonical_path));
            if (preg_match('#^/.*\.html$#', $extra)) {
                // This looks like a mistaken append (e.g., /post-slug/news.html)
                wp_redirect($canonical_url, 301);
                exit;
            }
        } elseif ($has_query) {
            // Safe redirect for query params only
            wp_redirect($canonical_url, 301);
            exit;
        }
    }
}
add_action('template_redirect', 'cta_enforce_canonical_redirect', 5);

/**
 * Handle specific URL redirects (404 fixes, old URLs, etc.)
 */
function cta_handle_specific_redirects() {
    // Don't redirect in admin, AJAX, or for logged-in users editing
    if (is_admin() || wp_doing_ajax() || is_preview()) {
        return;
    }
    
    // Don't redirect POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }
    
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = untrailingslashit(strtok($request_uri, '?'));
    
    // Specific redirects
    $redirects = [
        '/contact-us' => '/contact',
        '/contact-us/' => '/contact/',
    ];
    
    if (isset($redirects[$path])) {
        $redirect_url = home_url($redirects[$path]);
        // Preserve query string if present
        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            // Parse query string into array for add_query_arg()
            parse_str($_SERVER['QUERY_STRING'], $query_params);
            if (!empty($query_params)) {
                $redirect_url = add_query_arg($query_params, $redirect_url);
            }
        }
        wp_redirect($redirect_url, 301);
        exit;
    }
}
add_action('template_redirect', 'cta_handle_specific_redirects', 1);

/**
 * =========================================
 * IMAGE SEO
 * =========================================
 */

/**
 * Auto-generate alt text for course images
 */
function cta_auto_alt_text($attr, $attachment, $size) {
    if (empty($attr['alt'])) {
        $parent_id = get_post_field('post_parent', $attachment->ID);
        
        if ($parent_id && get_post_type($parent_id) === 'course') {
            $attr['alt'] = get_the_title($parent_id) . ' - Training Course';
        } elseif ($parent_id && get_post_type($parent_id) === 'course_event') {
            $linked_course = cta_safe_get_field('linked_course', $parent_id, null);
            if ($linked_course) {
                $attr['alt'] = $linked_course->post_title . ' - Training Session';
            }
        }
    }
    
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'cta_auto_alt_text', 10, 3);

/**
 * Add loading="lazy" and decoding="async" to images
 */
function cta_lazy_load_images($attr) {
    if (!isset($attr['loading'])) {
        $attr['loading'] = 'lazy';
    }
    if (!isset($attr['decoding'])) {
        $attr['decoding'] = 'async';
    }
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'cta_lazy_load_images');

/**
 * =========================================
 * INTERNAL LINKING
 * =========================================
 */

/**
 * Auto-link course names in content to their course pages
 */
function cta_auto_link_courses($content) {
    if (!is_singular('post')) {
        return $content;
    }
    
    // Get all published courses
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);
    
    foreach ($courses as $course) {
        $title = preg_quote($course->post_title, '/');
        $url = get_permalink($course->ID);
        
        // Only link first occurrence, and only if not already linked
        // Use a simpler pattern that avoids complex lookbehind (PHP PCRE limitation)
        // Check if title is not already inside an <a> tag by using a negative lookahead
        // Split content into parts that are not inside tags
        $parts = preg_split('/(<[^>]+>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $new_content = '';
        $in_tag = false;
        $linked = false;
        
        foreach ($parts as $part) {
            if (preg_match('/^<[^>]+>$/', $part)) {
                // This is an HTML tag
                $new_content .= $part;
                if (preg_match('/^<a\b/i', $part)) {
                    $in_tag = true;
                }
                if (preg_match('/<\/a>$/i', $part)) {
                    $in_tag = false;
                }
            } else {
                // This is text content
                if (!$in_tag && !$linked) {
                    // Try to replace the course title in this text part
                    $replaced = preg_replace(
                        '/\b(' . $title . ')\b/i',
                        '<a href="' . esc_url($url) . '">$1</a>',
                        $part,
                        1
                    );
                    if ($replaced !== $part) {
                        $linked = true;
                    }
                    $new_content .= $replaced;
                } else {
                    $new_content .= $part;
                }
            }
        }
        
        if ($linked) {
            $content = $new_content;
        }
    }
    
    return $content;
}
add_filter('the_content', 'cta_auto_link_courses', 20);

/**
 * =========================================
 * SEO DASHBOARD WIDGET
 * =========================================
 */

/**
 * Add SEO dashboard widget
 */
function cta_add_seo_dashboard_widget() {
    wp_add_dashboard_widget(
        'cta_seo_widget',
        'SEO Status & Tools',
        'cta_seo_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'cta_add_seo_dashboard_widget');

/**
 * SEO dashboard widget content
 */
function cta_seo_dashboard_widget_content() {
    // Get verification results (cached)
    $verification_results = get_transient('cta_seo_verification_results');
    
    if ($verification_results === false && function_exists('cta_run_seo_verification')) {
        $verification_results = cta_run_seo_verification();
        set_transient('cta_seo_verification_results', $verification_results, HOUR_IN_SECONDS);
    }
    
    $status_colors = [
        'pass' => '#00a32a',
        'warning' => '#dba617',
        'error' => '#d63638',
    ];
    
    $status_icons = [
        'pass' => '✓',
        'warning' => '⚠',
        'error' => '✗',
    ];
    
    ?>
    <div>
        <?php if ($verification_results) : ?>
        <div style="margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <span style="font-size: 20px; color: <?php echo esc_attr($status_colors[$verification_results['overall_status']] ?? '#646970'); ?>;">
                    <?php echo esc_html($status_icons[$verification_results['overall_status']] ?? '○'); ?>
                </span>
                <div>
                    <strong>SEO Status: <?php echo esc_html(ucfirst($verification_results['overall_status'])); ?></strong>
                    <p style="margin: 0; font-size: 12px; color: #646970;">
                        Score: <?php echo esc_html($verification_results['score']); ?>% 
                        (<?php echo esc_html($verification_results['passed_checks']); ?>/<?php echo esc_html($verification_results['total_checks']); ?> checks passed)
                    </p>
                </div>
            </div>
            
            <?php if ($verification_results['overall_status'] !== 'pass') : ?>
            <div style="background: <?php echo esc_attr($status_colors[$verification_results['overall_status']]); ?>15; border-left: 3px solid <?php echo esc_attr($status_colors[$verification_results['overall_status']]); ?>; padding: 10px; margin-top: 10px;">
                <p style="margin: 0; font-size: 12px;">
                    <?php if ($verification_results['errors'] > 0) : ?>
                        <strong><?php echo esc_html($verification_results['errors']); ?> error(s)</strong> need attention
                    <?php elseif ($verification_results['warnings'] > 0) : ?>
                        <strong><?php echo esc_html($verification_results['warnings']); ?> warning(s)</strong> detected
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 15px;">
            <h3 style="margin-top: 0; font-size: 14px;">Quick Status</h3>
            <ul style="margin: 10px 0; padding-left: 20px; font-size: 12px;">
                <li>
                    <strong>Schema Markup:</strong> 
                    <?php if (function_exists('cta_schema_markup')) : ?>
                        <span style="color: #00a32a;">✓ Implemented</span>
                    <?php else : ?>
                        <span style="color: #d63638;">✗ Missing</span>
                    <?php endif; ?>
                </li>
                <li>
                    <strong>Sitemap:</strong> 
                    <?php 
                    $sitemap_url = home_url('/wp-sitemap.xml');
                    $sitemap_response = wp_remote_head($sitemap_url, ['timeout' => 3, 'sslverify' => false]);
                    if (!is_wp_error($sitemap_response) && wp_remote_retrieve_response_code($sitemap_response) == 200) :
                    ?>
                        <span style="color: #00a32a;">✓ Accessible</span>
                    <?php else : ?>
                        <span style="color: #dba617;">⚠ Check needed</span>
                    <?php endif; ?>
                </li>
                <li>
                    <strong>Search Visibility:</strong> 
                    <?php if (get_option('blog_public') == 1) : ?>
                        <span style="color: #00a32a;">✓ Enabled</span>
                    <?php else : ?>
                        <span style="color: #d63638;">✗ Disabled</span>
                    <?php endif; ?>
                </li>
                <li>
                    <strong>Trustpilot Rating:</strong> 
                    <?php 
                    $rating = cta_get_theme_option('trustpilot_rating', '');
                    if (!empty($rating)) :
                    ?>
                        <span style="color: #00a32a;">✓ Configured (<?php echo esc_html($rating); ?>)</span>
                    <?php else : ?>
                        <span style="color: #dba617;">⚠ Not configured</span>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
        
        <div style="margin-bottom: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
            <h3 style="margin-top: 0; font-size: 14px;">Action Items</h3>
            <ul style="margin: 10px 0; padding-left: 20px; font-size: 12px;">
                <?php if (get_option('blog_public') == 0) : ?>
                <li style="color: #d63638;">Enable search engine visibility</li>
                <?php endif; ?>
                <?php if (empty(get_option('permalink_structure'))) : ?>
                <li style="color: #d63638;">Configure permalink structure</li>
                <?php endif; ?>
                <?php if (empty(cta_get_theme_option('trustpilot_rating', ''))) : ?>
                <li style="color: #dba617;">Configure Trustpilot rating</li>
                <?php endif; ?>
                <?php if (get_option('blog_public') == 1 && !empty(get_option('permalink_structure')) && !empty(cta_get_theme_option('trustpilot_rating', ''))) : ?>
                <li style="color: #00a32a;">✓ All critical items configured</li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
            <a href="<?php echo admin_url('tools.php?page=cta-seo-verification'); ?>" class="button button-primary">Run SEO Verification</a>
            <a href="<?php echo admin_url('tools.php?page=cta-sitemap-diagnostic'); ?>" class="button">Sitemap Diagnostic</a>
            <a href="<?php echo admin_url('tools.php?page=cta-performance'); ?>" class="button">Performance</a>
        </div>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
            <a href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer" class="button button-small">Google Search Console</a>
            <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener noreferrer" class="button button-small">Rich Results Test</a>
            <a href="https://pagespeed.web.dev/" target="_blank" rel="noopener noreferrer" class="button button-small">PageSpeed Insights</a>
        </div>
        
        <?php if ($verification_results && isset($verification_results['timestamp'])) : ?>
        <p class="description" style="margin-top: 15px; text-align: right; font-size: 11px;">
            Last verified: <?php echo human_time_diff(strtotime($verification_results['timestamp']), current_time('timestamp')); ?> ago
        </p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * =========================================
 * EMPTY ANCHOR TEXT DETECTION
 * =========================================
 */

/**
 * Check for empty anchor text in content and show admin warning
 */
function cta_check_empty_anchors($content) {
    // Only check in admin when editing
    if (!is_admin() || !current_user_can('edit_posts')) {
        return $content;
    }
    
    // Detect empty links: <a href="..."></a> or <a href="..."> </a>
    if (preg_match('/<a[^>]+href=["\'][^"\']+["\'][^>]*>\s*<\/a>/i', $content)) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>Warning:</strong> This content contains links with no anchor text. Please add descriptive text to all links for accessibility and SEO.</p></div>';
        });
    }
    
    return $content;
}
add_filter('content_save_pre', 'cta_check_empty_anchors');

/**
 * =========================================
 * META DESCRIPTION AUTO-GENERATION & CSV IMPORT
 * =========================================
 */

/**
 * Auto-generate meta description on post save if empty
 */
function cta_auto_generate_seo_meta($post_id, $post, $update) {
    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    // Only for relevant post types
    $allowed_types = ['course', 'course_event', 'post', 'page'];
    if (!in_array($post->post_type, $allowed_types)) {
        return;
    }
    
    // Skip if custom meta already set (don't overwrite manual edits)
    $existing = '';
    if ($post->post_type === 'course') {
        $existing = cta_safe_get_field('course_seo_meta_description', $post_id, '');
    } elseif ($post->post_type === 'course_event') {
        $existing = cta_safe_get_field('event_seo_meta_description', $post_id, '');
    } elseif ($post->post_type === 'post') {
        $existing = cta_safe_get_field('news_meta_description', $post_id, '');
    } else {
        $existing = cta_safe_get_field('seo_meta_description', $post_id, '');
    }
    
    if (!empty($existing)) {
        return; // Don't overwrite manually set descriptions
    }
    
    // Auto-generate based on post type
    $description = cta_generate_meta_description($post);
    
    if (!empty($description)) {
        // Save to appropriate field using ACF-aware update function
        if ($post->post_type === 'course') {
            cta_safe_update_field('course_seo_meta_description', $description, $post_id);
        } elseif ($post->post_type === 'course_event') {
            cta_safe_update_field('event_seo_meta_description', $description, $post_id);
        } elseif ($post->post_type === 'post') {
            cta_safe_update_field('news_meta_description', $description, $post_id);
        } else {
            cta_safe_update_field('seo_meta_description', $description, $post_id);
        }
    }
}
add_action('save_post', 'cta_auto_generate_seo_meta', 10, 3);

/**
 * Import meta descriptions from CSV (merge mode - only fills empty fields)
 * 
 * @return array Results with imported/skipped/failed counts
 */
function cta_import_meta_descriptions_from_csv() {
    $csv_path = get_template_directory() . '/data/seo_meta_descriptions.csv';
    
    if (!file_exists($csv_path)) {
        return [
            'success' => false,
            'message' => 'CSV file not found at: ' . $csv_path,
            'imported' => 0,
            'skipped' => 0,
            'failed' => 0
        ];
    }
    
    $rows = array_map('str_getcsv', file($csv_path));
    if (empty($rows)) {
        return [
            'success' => false,
            'message' => 'CSV file is empty',
            'imported' => 0,
            'skipped' => 0,
            'failed' => 0
        ];
    }
    
    $header = array_shift($rows); // Remove header row
    
    $imported = 0;
    $skipped = 0;
    $failed = 0;
    $errors = [];
    
    foreach ($rows as $row_num => $row) {
        if (count($row) < 4) {
            $failed++;
            continue;
        }
        
        list($url, $page_title, $description, $char_count) = $row;
        
        // Clean URL - remove domain, get path
        $url = trim($url);
        $parsed = wp_parse_url($url);
        if (!$parsed || !isset($parsed['path'])) {
            $failed++;
            continue;
        }
        
        $path = rtrim($parsed['path'], '/');
        if (empty($path)) {
            $path = '/';
        }
        
        // Find post by URL
        $post_id = url_to_postid(home_url($path));
        
        // Handle homepage variants
        if (!$post_id && ($path === '/' || $path === '')) {
            $post_id = get_option('page_on_front') ?: 0;
            if (!$post_id) {
                // Homepage is posts page
                $post_id = 0;
            }
        }
        
        if (!$post_id) {
            $failed++;
            $errors[] = "Row " . ($row_num + 2) . ": Could not find post for URL: " . $url;
            continue;
        }
        
        // Skip homepage (handled separately)
        if ($post_id === 0) {
            $skipped++;
            continue;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            $failed++;
            continue;
        }
        
        // Check if description already exists (merge mode)
        $existing = '';
        if ($post->post_type === 'course') {
            $existing = cta_safe_get_field('course_seo_meta_description', $post_id, '');
        } elseif ($post->post_type === 'course_event') {
            $existing = cta_safe_get_field('event_seo_meta_description', $post_id, '');
        } elseif ($post->post_type === 'post') {
            $existing = cta_safe_get_field('news_meta_description', $post_id, '');
        } else {
            $existing = cta_safe_get_field('seo_meta_description', $post_id, '');
        }
        
        if (!empty($existing)) {
            $skipped++;
            continue; // Don't overwrite existing descriptions
        }
        
        // Import description
        $description = trim($description);
        if (empty($description)) {
            $skipped++;
            continue;
        }
        
        // Save to appropriate field using ACF-aware update function
        if ($post->post_type === 'course') {
            cta_safe_update_field('course_seo_meta_description', $description, $post_id);
        } elseif ($post->post_type === 'course_event') {
            cta_safe_update_field('event_seo_meta_description', $description, $post_id);
        } elseif ($post->post_type === 'post') {
            cta_safe_update_field('news_meta_description', $description, $post_id);
        } else {
            cta_safe_update_field('seo_meta_description', $description, $post_id);
        }
        
        $imported++;
    }
    
    return [
        'success' => $imported > 0,
        'message' => sprintf('Imported %d descriptions, skipped %d (already set), failed %d', $imported, $skipped, $failed),
        'imported' => $imported,
        'skipped' => $skipped,
        'failed' => $failed,
        'errors' => $errors
    ];
}
