<?php
/**
 * Core Web Vitals Optimization Module
 * 
 * Handles LCP (Largest Contentful Paint), INP (Interaction to Next Paint),
 * and CLS (Cumulative Layout Shift) optimizations for event pages.
 *
 * Key features:
 * - Responsive image markup generation (WebP + AVIF support)
 * - Lazy loading implementation
 * - Preload/prefetch resource hints
 * - Image dimension enforcement (prevents CLS)
 * - Performance monitoring
 *
 * Compliant with:
 * - Google Core Web Vitals standards (December 2025 update)
 * - Responsive image best practices (MDN)
 * - Progressive enhancement (fallbacks for older browsers)
 *
 * @package ccs-theme
 * @since 2.1.0
 */

defined('ABSPATH') || exit;

/**
 * Get responsive image HTML with WebP and AVIF support
 * 
 * Generates optimized <picture> element with multiple formats:
 * - AVIF (highest compression, ~50% smaller than WebP)
 * - WebP (modern format, ~75% smaller than JPEG)
 * - JPEG/PNG (fallback for older browsers)
 * 
 * Example output:
 * <picture>
 *   <source srcset="image.avif" type="image/avif">
 *   <source srcset="image.webp" type="image/webp">
 *   <img src="image.jpg" alt="..." width="800" height="600" loading="lazy">
 * </picture>
 * 
 * @param int|string $attachment_id Attachment ID or image URL
 * @param string $size Image size (thumbnail, medium, large, full, or custom)
 * @param array $args Optional arguments:
 *   - 'alt' (string) - Alt text for accessibility
 *   - 'class' (string) - CSS class(es)
 *   - 'lazy' (bool) - Enable lazy loading (default: true)
 *   - 'width' (int) - Explicit width (overrides size metadata)
 *   - 'height' (int) - Explicit height (overrides size metadata)
 *   - 'srcset' (bool) - Generate srcset for responsive images (default: true)
 * @return string HTML picture element with fallback img tag
 */
function ccs_get_responsive_image_html($attachment_id, $size = 'medium', $args = []) {
    $defaults = [
        'alt' => '',
        'class' => '',
        'lazy' => true,
        'width' => null,
        'height' => null,
        'srcset' => true,
    ];
    
    $args = wp_parse_args($args, $defaults);

    // Handle string URLs (convert to attachment ID if possible)
    if (is_string($attachment_id)) {
        $image_url = $attachment_id;
        $attachment_id = attachment_url_to_postid($image_url);
        if (!$attachment_id) {
            // Fallback to simple img tag if not a registered attachment
            return ccs_get_simple_image_html($image_url, $size, $args);
        }
    } else {
        $attachment_id = intval($attachment_id);
    }

    if (!$attachment_id) {
        return '';
    }

    // Get image metadata
    $metadata = wp_get_attachment_metadata($attachment_id);
    if (!$metadata) {
        return '';
    }

    // Get base image URL (full size)
    $base_url = wp_get_attachment_url($attachment_id);
    if (!$base_url) {
        return '';
    }

    // Get attachment alt text
    if (empty($args['alt'])) {
        $args['alt'] = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    }

    // Get width/height from metadata if not explicitly provided
    if (is_null($args['width']) && isset($metadata['width'])) {
        $args['width'] = $metadata['width'];
    }
    if (is_null($args['height']) && isset($metadata['height'])) {
        $args['height'] = $metadata['height'];
    }

    // Build image paths for different formats
    // Assumes your image optimization process generates .avif and .webp versions
    $image_base = pathinfo($base_url, PATHINFO_DIRNAME) . '/' . pathinfo($base_url, PATHINFO_FILENAME);
    $avif_url = $image_base . '.avif';
    $webp_url = $image_base . '.webp';

    // Build picture element
    $html = '<picture>';

    // AVIF source (best compression, modern browsers)
    $html .= sprintf(
        '<source srcset="%s" type="image/avif">',
        esc_attr($avif_url)
    );

    // WebP source (good compression, widely supported modern browsers)
    $html .= sprintf(
        '<source srcset="%s" type="image/webp">',
        esc_attr($webp_url)
    );

    // Fallback img tag (JPEG/PNG for older browsers)
    $img_attrs = [
        'src' => esc_attr($base_url),
        'alt' => esc_attr($args['alt']),
    ];

    // Add width/height (prevents CLS - Cumulative Layout Shift)
    if ($args['width']) {
        $img_attrs['width'] = $args['width'];
    }
    if ($args['height']) {
        $img_attrs['height'] = $args['height'];
    }

    // Add lazy loading (defers below-fold images)
    if ($args['lazy']) {
        $img_attrs['loading'] = 'lazy';
    }

    // Add CSS class
    if ($args['class']) {
        $img_attrs['class'] = esc_attr($args['class']);
    }

    // Build img tag
    $img_html = '<img ';
    foreach ($img_attrs as $key => $value) {
        if ($value !== null && $value !== '') {
            $img_html .= sprintf('%s="%s" ', $key, $value);
        }
    }
    $img_html .= '>';

    $html .= $img_html . '</picture>';

    return $html;
}

/**
 * Fallback simple image HTML for URLs not in media library
 * 
 * @param string $url Image URL
 * @param string $size Size identifier (not used for external URLs)
 * @param array $args Image arguments (alt, class, lazy, width, height)
 * @return string IMG tag
 */
function ccs_get_simple_image_html($url, $size = 'medium', $args = []) {
    $defaults = [
        'alt' => '',
        'class' => '',
        'lazy' => true,
        'width' => null,
        'height' => null,
    ];
    
    $args = wp_parse_args($args, $defaults);

    $img_attrs = [
        'src' => esc_attr($url),
        'alt' => esc_attr($args['alt']),
    ];

    if ($args['width']) {
        $img_attrs['width'] = $args['width'];
    }
    if ($args['height']) {
        $img_attrs['height'] = $args['height'];
    }

    if ($args['lazy']) {
        $img_attrs['loading'] = 'lazy';
    }

    if ($args['class']) {
        $img_attrs['class'] = esc_attr($args['class']);
    }

    $html = '<img ';
    foreach ($img_attrs as $key => $value) {
        if ($value !== null && $value !== '') {
            $html .= sprintf('%s="%s" ', $key, $value);
        }
    }
    $html .= '>';

    return $html;
}

/**
 * Output resource preload/prefetch hints for critical resources
 * 
 * Improves LCP by telling browser to fetch critical resources early:
 * - preload: Critical resources needed on current page
 * - prefetch: Resources likely needed on next page
 * - dns-prefetch: DNS lookups for external domains
 * - preconnect: Full connection setup for external domains
 * 
 * @param array $resources Resources to preload:
 *   'preload' => [
 *       ['href' => 'critical.js', 'as' => 'script'],
 *       ['href' => 'hero.jpg', 'as' => 'image']
 *   ],
 *   'dns-prefetch' => ['//fonts.googleapis.com'],
 *   'preconnect' => ['https://fonts.gstatic.com']
 * @return void
 */
function ccs_output_resource_hints($resources = []) {
    $defaults = [
        'preload' => [],
        'prefetch' => [],
        'dns-prefetch' => [],
        'preconnect' => [],
    ];
    
    $resources = wp_parse_args($resources, $defaults);

    // Preload critical resources
    foreach ($resources['preload'] as $resource) {
        if (!isset($resource['href']) || !isset($resource['as'])) {
            continue;
        }
        
        printf(
            '<link rel="preload" href="%s" as="%s"%s>',
            esc_attr($resource['href']),
            esc_attr($resource['as']),
            isset($resource['type']) ? ' type="' . esc_attr($resource['type']) . '"' : '',
            "\n"
        );
    }

    // Prefetch resources for next navigation
    foreach ($resources['prefetch'] as $url) {
        printf(
            '<link rel="prefetch" href="%s">' . "\n",
            esc_attr($url)
        );
    }

    // DNS prefetch for external domains
    foreach ($resources['dns-prefetch'] as $domain) {
        printf(
            '<link rel="dns-prefetch" href="%s">' . "\n",
            esc_attr($domain)
        );
    }

    // Preconnect for external resources
    foreach ($resources['preconnect'] as $domain) {
        printf(
            '<link rel="preconnect" href="%s" crossorigin>' . "\n",
            esc_attr($domain)
        );
    }
}

/**
 * Get preload hints for hero image (improves LCP)
 * 
 * Hero images are typically the largest contentful paint element.
 * Preloading them significantly improves LCP metric.
 * 
 * @param int $attachment_id Hero image attachment ID
 * @param bool $include_webp Whether to preload WebP version too
 * @return array Preload resource hints for ccs_output_resource_hints()
 */
function ccs_get_hero_image_preload_hints($attachment_id, $include_webp = true) {
    if (!$attachment_id) {
        return [];
    }

    $image_url = wp_get_attachment_url($attachment_id);
    if (!$image_url) {
        return [];
    }

    $hints = [];

    // Preload main image
    $hints[] = [
        'href' => $image_url,
        'as' => 'image',
    ];

    // Preload WebP if available
    if ($include_webp) {
        $image_base = pathinfo($image_url, PATHINFO_DIRNAME) . '/' . pathinfo($image_url, PATHINFO_FILENAME);
        $webp_url = $image_base . '.webp';
        
        $hints[] = [
            'href' => $webp_url,
            'as' => 'image',
            'type' => 'image/webp',
        ];
    }

    return ['preload' => $hints];
}

/**
 * Add width/height attributes to image tags to prevent CLS
 * 
 * CLS (Cumulative Layout Shift) occurs when images load without explicit
 * dimensions, causing layout shift as the image pushes surrounding content.
 * 
 * This function processes image markup and ensures width/height are always set.
 * 
 * @param string $html HTML content with images
 * @param array $images_with_dims Array mapping image URLs to [width, height]
 * @return string HTML with width/height attributes added
 */
function ccs_ensure_image_dimensions($html, $images_with_dims = []) {
    if (empty($html) || empty($images_with_dims)) {
        return $html;
    }

    foreach ($images_with_dims as $url => $dims) {
        if (!isset($dims['width']) || !isset($dims['height'])) {
            continue;
        }

        // Pattern to find img tags for this URL
        $pattern = sprintf(
            '/(<img\s+[^>]*src="%s"[^>]*)>/i',
            preg_quote(esc_url($url), '/')
        );

        // Check if width/height already present
        if (preg_match($pattern, $html)) {
            // Add width/height if not present
            $replacement = sprintf(
                '$1 width="%d" height="%d">',
                intval($dims['width']),
                intval($dims['height'])
            );

            // Only add if not already present
            if (!preg_match('/width=|height=/', $html)) {
                $html = preg_replace($pattern, $replacement, $html);
            }
        }
    }

    return $html;
}

/**
 * Generate CSS for lazy-loaded image placeholders
 * 
 * Creates low-quality placeholder effect while images load.
 * Improves perceived performance (LCP perception).
 * 
 * @return string CSS rules
 */
function ccs_get_lazy_image_placeholder_css() {
    return '
    <style>
    /* Lazy-loaded images */
    img[loading="lazy"] {
        background-color: #f0f0f0;
        background-image: linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%, #f0f0f0);
        background-size: 20px 20px;
        background-position: 0 0;
        animation: cta-placeholder-loading 1.5s linear infinite;
    }
    
    @keyframes cta-placeholder-loading {
        0% { background-position: 0 0; }
        100% { background-position: 20px 20px; }
    }
    
    /* Stop animation when image loads */
    img[loading="lazy"][src] {
        animation: none;
        background-image: none;
    }
    </style>
    ';
}

/**
 * Output performance monitoring script
 * 
 * Logs Web Vitals metrics to server for monitoring CWV scores.
 * Uses Navigation Timing API and Largest Contentful Paint API.
 * 
 * @return void
 */
function ccs_output_performance_monitoring() {
    ?>
    <script>
    (function() {
        // Monitor Core Web Vitals
        if ('PerformanceObserver' in window) {
            // LCP (Largest Contentful Paint)
            const lcpObserver = new PerformanceObserver((list) => {
                const lcpValue = list.getEntries().pop().renderTime || list.getEntries().pop().loadTime;
                console.log('LCP:', lcpValue);
                ccs_sendMetric('lcp', lcpValue);
            });
            lcpObserver.observe({entryTypes: ['largest-contentful-paint']});
            
            // CLS (Cumulative Layout Shift)
            let clsValue = 0;
            const clsObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                }
                console.log('CLS:', clsValue);
            });
            clsObserver.observe({entryTypes: ['layout-shift']});
            
            // Log CLS on page unload
            window.addEventListener('beforeunload', () => {
                ccs_sendMetric('cls', clsValue);
            });
        }
        
        // INP (Interaction to Next Paint) - if available
        if ('PerformanceObserver' in window) {
            try {
                const inpObserver = new PerformanceObserver((list) => {
                    const inpValue = list.getEntries().pop().processingDuration;
                    console.log('INP:', inpValue);
                    ccs_sendMetric('inp', inpValue);
                });
                inpObserver.observe({entryTypes: ['event']});
            } catch (e) {
                // INP observer not supported
            }
        }
    })();
    
    // Send metric to server
    function ccs_sendMetric(metric, value) {
        if (navigator.sendBeacon) {
            navigator.sendBeacon('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'ccs_log_performance_metric',
                metric: metric,
                value: value,
                url: window.location.href,
                nonce: '<?php echo wp_create_nonce('ccs_performance_nonce'); ?>'
            });
        }
    }
    </script>
    <?php
}

/**
 * AJAX handler: Log performance metrics
 * 
 * Called by client-side performance monitoring script.
 * Stores metrics in log file or database for analysis.
 */
function ccs_handle_log_performance_metric() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccs_performance_nonce')) {
        wp_die('Nonce verification failed');
    }

    $metric = sanitize_text_field($_POST['metric'] ?? '');
    $value = floatval($_POST['value'] ?? 0);
    $url = esc_url_raw($_POST['url'] ?? '');

    if (!$metric || !$value) {
        wp_die('Invalid metric data');
    }

    // Log to file (or database in production)
    $log_file = WP_CONTENT_DIR . '/debug-cwv.log';
    $log_entry = sprintf(
        "[%s] %s=%f (from %s)\n",
        current_time('mysql'),
        $metric,
        $value,
        $url
    );

    error_log($log_entry, 3, $log_file);

    wp_die('ok');
}
add_action('wp_ajax_nopriv_ccs_log_performance_metric', 'ccs_handle_log_performance_metric');
add_action('wp_ajax_ccs_log_performance_metric', 'ccs_handle_log_performance_metric');

/**
 * Output critical CSS inline (above-the-fold optimization)
 * 
 * Inlines critical CSS for above-the-fold content to eliminate
 * render-blocking CSS, improving FCP (First Contentful Paint).
 * 
 * @param string $critical_css Minified critical CSS
 * @return void
 */
function ccs_output_critical_css($critical_css = '') {
    if (empty($critical_css)) {
        return;
    }
    
    echo '<style>' . $critical_css . '</style>';
}

/**
 * Defer non-critical CSS
 * 
 * Moves non-critical stylesheets to load asynchronously,
 * preventing them from blocking page render.
 * 
 * @param string $handle WordPress handle for stylesheet
 * @return void
 */
function ccs_defer_non_critical_css($handle) {
    add_filter('style_loader_tag', function($tag, $style_handle) use ($handle) {
        if ($style_handle === $handle) {
            return str_replace(
                'rel="stylesheet"',
                'rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"',
                $tag
            );
        }
        return $tag;
    }, 10, 2);
}
