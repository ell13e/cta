<?php
/**
 * Image Sitemap Implementation
 * 
 * Adds image sitemap support to WordPress core sitemaps
 * 
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Register image sitemap provider
 */
function ccs_register_image_sitemap_provider() {
    // Only if WordPress sitemaps are enabled
    if (!function_exists('wp_get_sitemap_providers')) {
        return;
    }
    
    // Get sitemap providers
    $providers = wp_get_sitemap_providers();
    
    // Add image sitemap for courses
    add_filter('wp_sitemaps_add_provider', 'ccs_add_image_sitemap_provider', 10, 2);
}
add_action('init', 'ccs_register_image_sitemap_provider');

/**
 * Add image sitemap provider
 */
function ccs_add_image_sitemap_provider($provider, $name) {
    if ($name === 'posts' && class_exists('WP_Sitemaps_Posts')) {
        // Images are added via filters, not a separate provider
        return $provider;
    }
    return $provider;
}

/**
 * Add images to course sitemap entries
 */
function ccs_add_course_images_to_sitemap($url, $post, $post_type) {
    // Only for courses
    if ($post_type !== 'course') {
        return $url;
    }
    
    // Get featured image
    $image_id = $post instanceof WP_Post ? get_post_thumbnail_id($post->ID) : 0;
    if (!$image_id) {
        return $url;
    }
    
    $image_url = wp_get_attachment_image_url($image_id, 'full');
    $image_meta = wp_get_attachment_metadata($image_id);
    
    if (!$image_url) {
        return $url;
    }
    
    // Initialize images array if not exists
    if (!isset($url['images'])) {
        $url['images'] = [];
    }
    
    $image_data = [
        'loc' => $image_url,
    ];
    
    // Add title (alt text or course title)
    $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
    if (!empty($alt_text)) {
        $image_data['title'] = $alt_text;
    } else {
        $image_data['title'] = get_the_title($post->ID) . ' - Training Course';
    }
    
    // Add caption if available
    $caption = wp_get_attachment_caption($image_id);
    if (!empty($caption)) {
        $image_data['caption'] = $caption;
    }
    
    // Add dimensions if available
    if (!empty($image_meta['width']) && !empty($image_meta['height'])) {
        $image_data['width'] = $image_meta['width'];
        $image_data['height'] = $image_meta['height'];
    }
    
    $url['images'][] = $image_data;
    
    return $url;
}
add_filter('wp_sitemaps_posts_entry', 'ccs_add_course_images_to_sitemap', 10, 3);

/**
 * Add images to course event sitemap entries
 */
function ccs_add_event_images_to_sitemap($url, $post, $post_type) {
    // Only for course events
    if ($post_type !== 'course_event') {
        return $url;
    }
    
    if (!($post instanceof WP_Post)) {
        return $url;
    }
    
    // Get featured image or linked course image
    $image_id = get_post_thumbnail_id($post->ID);
    
    // If no featured image, try linked course
    if (!$image_id) {
        $linked_course = ccs_safe_get_field('linked_course', $post->ID, null);
        if ($linked_course) {
            $image_id = get_post_thumbnail_id($linked_course->ID);
        }
    }
    
    if (!$image_id) {
        return $url;
    }
    
    $image_url = wp_get_attachment_image_url($image_id, 'full');
    $image_meta = wp_get_attachment_metadata($image_id);
    
    if (!$image_url) {
        return $url;
    }
    
    // Initialize images array if not exists
    if (!isset($url['images'])) {
        $url['images'] = [];
    }
    
    $image_data = [
        'loc' => $image_url,
    ];
    
    // Add title
    $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
    if (!empty($alt_text)) {
        $image_data['title'] = $alt_text;
    } else {
        $image_data['title'] = get_the_title($post->ID) . ' - Training Event';
    }
    
    // Add dimensions if available
    if (!empty($image_meta['width']) && !empty($image_meta['height'])) {
        $image_data['width'] = $image_meta['width'];
        $image_data['height'] = $image_meta['height'];
    }
    
    $url['images'][] = $image_data;
    
    return $url;
}
add_filter('wp_sitemaps_posts_entry', 'ccs_add_event_images_to_sitemap', 10, 3);
