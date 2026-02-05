<?php
/**
 * Custom Robots.txt Configuration
 * 
 * WordPress generates robots.txt dynamically.
 * Add this to functions.php to customize it.
 * 
 * @package CTA_Theme
 */

/**
 * Customize WordPress robots.txt
 */
add_filter('robots_txt', 'cta_custom_robots_txt', 10, 2);
function cta_custom_robots_txt($output, $public) {
    if ('0' === $public) {
        // If site is not public, disallow all
        return $output;
    }
    
    $site_url = get_site_url();
    $sitemap_url = $site_url . '/wp-sitemap.xml';
    
    $custom_rules = "# Continuity Training Academy - Custom Robots.txt\n\n";
    
    $custom_rules .= "User-agent: *\n";
    $custom_rules .= "Disallow: /wp-admin/\n";
    $custom_rules .= "Disallow: /wp-includes/\n";
    $custom_rules .= "Disallow: /wp-content/plugins/\n";
    $custom_rules .= "Disallow: /wp-content/themes/\n";
    $custom_rules .= "Disallow: /wp-content/cache/\n";
    $custom_rules .= "Disallow: /wp-json/\n";
    $custom_rules .= "Disallow: /*?s=\n"; // Block search URLs
    $custom_rules .= "Disallow: /*?p=\n"; // Block old permalink structure
    $custom_rules .= "Disallow: /cart/\n";
    $custom_rules .= "Disallow: /checkout/\n";
    $custom_rules .= "Disallow: /my-account/\n";
    $custom_rules .= "Allow: /wp-admin/admin-ajax.php\n\n";
    
    // Allow important directories
    $custom_rules .= "Allow: /wp-content/uploads/\n\n";
    
    // Sitemap location
    $custom_rules .= "Sitemap: {$sitemap_url}\n";
    
    return $custom_rules;
}

/**
 * Add meta tags for SEO (no plugin needed)
 */
add_action('wp_head', 'cta_add_meta_tags', 1);
function cta_add_meta_tags() {
    if (is_singular()) {
        global $post;
        
        // Get page-specific meta
        $title = get_the_title();
        $description = get_the_excerpt() ?: wp_trim_words(get_the_content(), 30);
        $url = get_permalink();
        $image = get_the_post_thumbnail_url($post->ID, 'large') ?: get_site_icon_url(512);
        
        // Custom descriptions for key pages
        $custom_descriptions = [
            'cqc-compliance-hub' => 'Complete guide to CQC training compliance. 5 Key Questions, mandatory training requirements, inspection preparation, and regulatory updates.',
            'downloadable-resources' => 'Free training resources for care professionals. Templates, checklists, quick reference guides, and tools to support excellent care.',
            'faqs' => 'Frequently asked questions about care training courses, booking, certification, payment, and group training options.',
            'group-training' => 'Group training for care teams in Kent. Flexible scheduling, CPD-accredited certificates, and group rates for quality training.',
        ];
        
        if (is_page()) {
            $page_slug = $post->post_name;
            if (isset($custom_descriptions[$page_slug])) {
                $description = $custom_descriptions[$page_slug];
            }
        }
        
        // Output meta tags
        echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($description)) . '">' . "\n";
        
        // Open Graph tags
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(wp_strip_all_tags($description)) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        
        // Twitter Card tags
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr(wp_strip_all_tags($description)) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
    }
    
    // Canonical is output by inc/seo.php (cta_seo_meta_tags) for all contexts to avoid duplicate or conflicting canonicals.
}
