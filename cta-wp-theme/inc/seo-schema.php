<?php
/**
 * Centralized Schema.org Functions
 * 
 * Provides reusable functions for generating consistent schema markup across the site
 *
 * @package CTA_Theme
 */

/**
 * Get organization schema data
 * Used across all pages for consistent organization information
 */
/**
 * Build openingHoursSpecification array from theme mods (e.g. "09:00-17:00").
 *
 * @return array[] Empty or array of schema.org OpeningHoursSpecification items.
 */
function cta_get_opening_hours_schema() {
    $weekdays = get_theme_mod('cta_opening_weekdays', '');
    $saturday = get_theme_mod('cta_opening_saturday', '');
    $sunday = get_theme_mod('cta_opening_sunday', '');
    $specs = [];
    $day_ranges = [
        'weekdays' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        'saturday' => ['Saturday'],
        'sunday' => ['Sunday'],
    ];
    foreach ([ 'weekdays' => $weekdays, 'saturday' => $saturday, 'sunday' => $sunday ] as $key => $hours) {
        if ($hours === '' || !preg_match('/^(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/', trim($hours), $m)) {
            continue;
        }
        $specs[] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => $day_ranges[$key],
            'opens' => $m[1],
            'closes' => $m[2],
        ];
    }
    return $specs;
}

function cta_get_organization_schema() {
    $contact = cta_get_contact_info();
    $site_url = home_url();
    
    // Get rating from theme customizer (default 4.6); optional toggle to avoid self-serving rating in schema
    $show_rating = get_theme_mod('cta_show_trustpilot_rating_schema', true);
    $rating_value = get_theme_mod('cta_trustpilot_rating', '4.6');
    $review_count = get_theme_mod('cta_trustpilot_review_count', '20');
    
    // Get social media URLs from theme customizer
    $facebook_url = get_theme_mod('cta_facebook_url', 'https://www.facebook.com/continuitytraining');
    $linkedin_url = get_theme_mod('cta_linkedin_url', 'https://www.linkedin.com/company/continuity-training-academy-cta');
    $instagram_url = get_theme_mod('cta_instagram_url', 'https://www.instagram.com/continuitytrainingacademy');
    $trustpilot_url = get_theme_mod('cta_trustpilot_url', 'https://www.trustpilot.com/review/continuitytrainingacademy.co.uk');
    
    $same_as = [];
    if (!empty($trustpilot_url)) $same_as[] = $trustpilot_url;
    if (!empty($facebook_url)) $same_as[] = $facebook_url;
    if (!empty($linkedin_url)) $same_as[] = $linkedin_url;
    if (!empty($instagram_url)) $same_as[] = $instagram_url;
    
    $org = [
        '@type' => 'EducationalOrganization',
        '@id' => $site_url . '/#organization',
        'name' => 'Continuity Training Academy',
        'url' => $site_url . '/',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => get_template_directory_uri() . '/assets/img/logo/long_logo-400w.webp',
            'width' => 400,
            'height' => 100,
        ],
        'description' => 'Professional care sector training in Kent. CQC-compliant, CPD-accredited courses since 2020.',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'The Maidstone Studios, New Cut Road',
            'addressLocality' => 'Maidstone',
            'addressRegion' => 'Kent',
            'postalCode' => 'ME14 5NZ',
            'addressCountry' => 'GB',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => '51.264494',
            'longitude' => '0.545844',
        ],
        'telephone' => $contact['phone'],
        'email' => $contact['email'],
        'priceRange' => '££',
        'sameAs' => $same_as,
    ];
    if ($show_rating && $rating_value !== '' && $review_count !== '') {
        $org['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $rating_value,
            'reviewCount' => $review_count,
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }
    $opening = cta_get_opening_hours_schema();
    if (!empty($opening)) {
        $org['openingHoursSpecification'] = $opening;
    }
    return $org;
}

/**
 * Get LocalBusiness schema for the physical location (no aggregateRating; for local/sitelinks).
 */
function cta_get_local_business_schema() {
    $contact = cta_get_contact_info();
    $site_url = home_url();
    $opening = cta_get_opening_hours_schema();
    $lb = [
        '@type' => 'LocalBusiness',
        '@id' => $site_url . '/#localbusiness',
        'name' => 'Continuity Training Academy',
        'url' => $site_url . '/',
        'telephone' => $contact['phone'],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'The Maidstone Studios, New Cut Road',
            'addressLocality' => 'Maidstone',
            'addressRegion' => 'Kent',
            'postalCode' => 'ME14 5NZ',
            'addressCountry' => 'GB',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => '51.264494',
            'longitude' => '0.545844',
        ],
        'priceRange' => '££',
    ];
    if (!empty($opening)) {
        $lb['openingHoursSpecification'] = $opening;
    }
    return $lb;
}

/**
 * Get Course schema graph for a single course (rich results).
 * Returns array of schema items: Course (+ optional offers, timeRequired, image), provider references Organization @id.
 *
 * @param int|null $post_id Course post ID; defaults to current post.
 * @return array Schema graph items for this course.
 */
function cta_get_course_schema($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    if (!$post_id || get_post_type($post_id) !== 'course') {
        return [];
    }
    $site_url = home_url();
    $course_url = get_permalink($post_id);
    $name = get_the_title($post_id);
    $description = has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words(strip_tags(get_post_field('post_content', $post_id)), 50);
    if (empty($description)) {
        $description = 'CQC-compliant ' . $name . ' training for care workers. CPD-accredited course.';
    }
    $course = [
        '@type' => 'Course',
        '@id' => $course_url . '#course',
        'name' => $name,
        'description' => $description,
        'url' => $course_url,
        'provider' => ['@id' => $site_url . '/#organization'],
        'inLanguage' => 'en-GB',
        'courseMode' => 'onsite',
        'isAccessibleForFree' => false,
    ];
    $duration = cta_safe_get_field('course_duration', $post_id, '');
    if ($duration && preg_match('/(\d+)\s*day/i', $duration, $m)) {
        $course['timeRequired'] = 'P' . $m[1] . 'D';
    } elseif ($duration && preg_match('/(\d+)\s*hour/i', $duration, $m)) {
        $course['timeRequired'] = 'PT' . $m[1] . 'H';
    }
    $level = cta_safe_get_field('course_level', $post_id, '');
    if ($level) {
        $course['educationalLevel'] = $level;
    }
    $accreditation = cta_safe_get_field('course_accreditation', $post_id, '');
    if ($accreditation) {
        $course['educationalCredentialAwarded'] = $accreditation;
    }
    $price = cta_safe_get_field('course_price', $post_id, '');
    if ($price !== '' && $price !== null) {
        $price_clean = preg_replace('/[^0-9.]/', '', (string) $price);
        if ($price_clean !== '') {
            $course['offers'] = [
                '@type' => 'Offer',
                'price' => $price_clean,
                'priceCurrency' => 'GBP',
                'availability' => 'https://schema.org/InStock',
                'url' => $course_url,
                'seller' => ['@id' => $site_url . '/#organization'],
            ];
        }
    }
    $image_url = cta_get_page_schema_image($post_id);
    if ($image_url) {
        $course['image'] = [
            '@type' => 'ImageObject',
            'url' => $image_url,
        ];
    }
    $terms = get_the_terms($post_id, 'course_category');
    if ($terms && !is_wp_error($terms)) {
        $course['keywords'] = implode(', ', array_map(function ($t) {
            return $t->name;
        }, $terms));
    }
    return [$course];
}

/**
 * Output Course schema on single course pages (graph: Course + Organization + Breadcrumb).
 */
function cta_output_course_schema() {
    if (!is_singular('course')) {
        return;
    }
    $site_url = home_url();
    $page_url = get_permalink();
    $page_title = get_the_title();
    $graph = array_merge(
        cta_get_course_schema(),
        [cta_get_organization_schema()],
        [cta_get_breadcrumb_schema([
            ['name' => 'Home', 'url' => $site_url . '/'],
            ['name' => 'Courses', 'url' => get_post_type_archive_link('course')],
            ['name' => $page_title, 'url' => $page_url],
        ])]
    );
    cta_output_schema_json($graph);
}
add_action('wp_head', 'cta_output_course_schema', 10);

/**
 * Get page featured image URL for schema
 * Checks for custom schema image first, then falls back to featured image
 */
function cta_get_page_schema_image($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Check for custom schema image (meta field)
    $schema_image_id = get_post_meta($post_id, '_cta_schema_image', true);
    
    if ($schema_image_id) {
        $image_url = wp_get_attachment_image_url($schema_image_id, 'full');
        if ($image_url) {
            return $image_url;
        }
    }
    
    // Fall back to featured image
    if (has_post_thumbnail($post_id)) {
        return get_the_post_thumbnail_url($post_id, 'full');
    }
    
    // Fall back to default image
    return get_template_directory_uri() . '/assets/img/default-og-image.jpg';
}

/**
 * Get breadcrumb schema
 */
function cta_get_breadcrumb_schema($items = []) {
    $site_url = home_url();
    $page_url = get_permalink();
    
    if (empty($items)) {
        // Default breadcrumb
        $items = [
            ['name' => 'Home', 'url' => $site_url . '/'],
            ['name' => get_the_title(), 'url' => $page_url],
        ];
    }
    
    $list_items = [];
    foreach ($items as $index => $item) {
        $list_items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => $item['url'],
        ];
    }
    
    return [
        '@type' => 'BreadcrumbList',
        '@id' => $page_url . '#breadcrumb',
        'itemListElement' => $list_items,
    ];
}

/**
 * Get WebPage schema
 */
function cta_get_webpage_schema($args = []) {
    $site_url = home_url();
    $page_url = get_permalink();
    
    $defaults = [
        'name' => get_the_title() . ' | Continuity Training Academy',
        'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 30),
    ];
    
    $args = wp_parse_args($args, $defaults);
    
    $schema = [
        '@type' => 'WebPage',
        '@id' => $page_url . '#webpage',
        'url' => $page_url,
        'name' => $args['name'],
        'description' => $args['description'],
        'isPartOf' => ['@id' => $site_url . '/#website'],
        'about' => ['@id' => $site_url . '/#organization'],
        'breadcrumb' => ['@id' => $page_url . '#breadcrumb'],
    ];
    
    // Add primary image if available
    $image_url = cta_get_page_schema_image();
    if ($image_url) {
        $schema['primaryImageOfPage'] = [
            '@type' => 'ImageObject',
            'url' => $image_url,
        ];
    }
    
    return $schema;
}

/**
 * Output schema JSON-LD in head
 */
function cta_output_schema_json($schema_graph) {
    if (empty($schema_graph)) {
        return;
    }
    
    $schema_data = [
        '@context' => 'https://schema.org',
        '@graph' => $schema_graph,
    ];
    
    echo "\n<!-- Schema.org Structured Data -->\n";
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n</script>\n";
}

/**
 * Add schema image meta box to page editor
 */
function cta_add_schema_image_meta_box() {
    add_meta_box(
        'cta_schema_image',
        'Schema.org Featured Image',
        'cta_schema_image_meta_box_callback',
        ['page', 'post', 'course'],
        'side',
        'low'
    );
}
add_action('add_meta_boxes', 'cta_add_schema_image_meta_box');

/**
 * Schema image meta box callback
 */
function cta_schema_image_meta_box_callback($post) {
    wp_nonce_field('cta_schema_image_nonce', 'cta_schema_image_nonce');
    
    $image_id = get_post_meta($post->ID, '_cta_schema_image', true);
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
    
    ?>
    <div class="cta-schema-image-wrapper">
        <p class="description">This image will be used in Schema.org markup for SEO. If not set, the featured image will be used.</p>
        
        <div class="cta-schema-image-preview" style="margin: 10px 0;">
            <?php if ($image_url) : ?>
                <img src="<?php echo esc_url($image_url); ?>" style="max-width: 100%; height: auto; display: block;" />
            <?php else : ?>
                <p style="color: #666; font-style: italic;">No schema image set</p>
            <?php endif; ?>
        </div>
        
        <input type="hidden" id="cta_schema_image_id" name="cta_schema_image_id" value="<?php echo esc_attr($image_id); ?>" />
        
        <p>
            <button type="button" class="button cta-upload-schema-image">
                <?php echo $image_id ? 'Change Image' : 'Set Schema Image'; ?>
            </button>
            <?php if ($image_id) : ?>
                <button type="button" class="button cta-remove-schema-image">Remove Image</button>
            <?php endif; ?>
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var mediaUploader;
        
        $('.cta-upload-schema-image').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Choose Schema Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#cta_schema_image_id').val(attachment.id);
                $('.cta-schema-image-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto; display: block;" />');
                $('.cta-upload-schema-image').text('Change Image');
                if ($('.cta-remove-schema-image').length === 0) {
                    $('.cta-upload-schema-image').after('<button type="button" class="button cta-remove-schema-image">Remove Image</button>');
                }
            });
            
            mediaUploader.open();
        });
        
        $(document).on('click', '.cta-remove-schema-image', function(e) {
            e.preventDefault();
            $('#cta_schema_image_id').val('');
            $('.cta-schema-image-preview').html('<p style="color: #666; font-style: italic;">No schema image set</p>');
            $('.cta-upload-schema-image').text('Set Schema Image');
            $(this).remove();
        });
    });
    </script>
    <?php
}

/**
 * Save schema image meta
 */
function cta_save_schema_image_meta($post_id) {
    // Check nonce
    if (!isset($_POST['cta_schema_image_nonce']) || !wp_verify_nonce($_POST['cta_schema_image_nonce'], 'cta_schema_image_nonce')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save or delete meta
    if (isset($_POST['cta_schema_image_id']) && !empty($_POST['cta_schema_image_id'])) {
        update_post_meta($post_id, '_cta_schema_image', intval($_POST['cta_schema_image_id']));
    } else {
        delete_post_meta($post_id, '_cta_schema_image');
    }
}
add_action('save_post', 'cta_save_schema_image_meta');

/**
 * Add theme customizer settings for schema
 */
function cta_schema_customizer_settings($wp_customize) {
    // Add Schema Section
    $wp_customize->add_section('cta_schema_settings', [
        'title' => 'Schema & SEO Settings',
        'priority' => 30,
    ]);
    
    // Trustpilot Rating
    $wp_customize->add_setting('cta_trustpilot_rating', [
        'default' => '4.6',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    $wp_customize->add_control('cta_trustpilot_rating', [
        'label' => 'Trustpilot Rating',
        'description' => 'Your current Trustpilot rating (e.g., 4.6)',
        'section' => 'cta_schema_settings',
        'type' => 'text',
    ]);
    
    // Review Count
    $wp_customize->add_setting('cta_trustpilot_review_count', [
        'default' => '20',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    $wp_customize->add_control('cta_trustpilot_review_count', [
        'label' => 'Trustpilot Review Count',
        'description' => 'Number of Trustpilot reviews',
        'section' => 'cta_schema_settings',
        'type' => 'text',
    ]);
    
    // Social Media URLs
    $wp_customize->add_setting('cta_facebook_url', [
        'default' => 'https://www.facebook.com/continuitytraining',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    
    $wp_customize->add_control('cta_facebook_url', [
        'label' => 'Facebook URL',
        'section' => 'cta_schema_settings',
        'type' => 'url',
    ]);
    
    $wp_customize->add_setting('cta_linkedin_url', [
        'default' => 'https://www.linkedin.com/company/continuity-training-academy-cta',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    
    $wp_customize->add_control('cta_linkedin_url', [
        'label' => 'LinkedIn URL',
        'section' => 'cta_schema_settings',
        'type' => 'url',
    ]);
    
    $wp_customize->add_setting('cta_instagram_url', [
        'default' => 'https://www.instagram.com/continuitytrainingacademy',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    
    $wp_customize->add_control('cta_instagram_url', [
        'label' => 'Instagram URL',
        'section' => 'cta_schema_settings',
        'type' => 'url',
    ]);
    
    $wp_customize->add_setting('cta_trustpilot_url', [
        'default' => 'https://www.trustpilot.com/review/continuitytrainingacademy.co.uk',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    
    $wp_customize->add_control('cta_trustpilot_url', [
        'label' => 'Trustpilot URL',
        'section' => 'cta_schema_settings',
        'type' => 'url',
    ]);
    
    // Show Trustpilot rating in Organization schema (Google: avoid self-serving ratings; disable if unsure)
    $wp_customize->add_setting('cta_show_trustpilot_rating_schema', [
        'default' => true,
        'sanitize_callback' => function ($v) { return (bool) $v; },
    ]);
    $wp_customize->add_control('cta_show_trustpilot_rating_schema', [
        'label' => 'Show Trustpilot rating in Organization schema',
        'description' => 'Uncheck if you prefer not to show star rating in schema (e.g. to avoid self-serving interpretation).',
        'section' => 'cta_schema_settings',
        'type' => 'checkbox',
    ]);
    
    // Opening hours (format HH:MM-HH:MM; used in Organization and LocalBusiness schema)
    $wp_customize->add_setting('cta_opening_weekdays', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('cta_opening_weekdays', [
        'label' => 'Opening hours (Mon–Fri)',
        'description' => 'e.g. 09:00-17:00. Leave blank to omit.',
        'section' => 'cta_schema_settings',
        'type' => 'text',
    ]);
    $wp_customize->add_setting('cta_opening_saturday', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('cta_opening_saturday', [
        'label' => 'Opening hours (Saturday)',
        'description' => 'e.g. 09:00-12:00',
        'section' => 'cta_schema_settings',
        'type' => 'text',
    ]);
    $wp_customize->add_setting('cta_opening_sunday', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('cta_opening_sunday', [
        'label' => 'Opening hours (Sunday)',
        'description' => 'e.g. 10:00-14:00',
        'section' => 'cta_schema_settings',
        'type' => 'text',
    ]);
}
add_action('customize_register', 'cta_schema_customizer_settings');

/**
 * =========================================
 * PERMANENT PAGE SCHEMA FUNCTIONS
 * =========================================
 */

/**
 * Get Homepage schema (WebSite + Organization + BreadcrumbList)
 */
function cta_get_homepage_schema() {
    $site_url = home_url();
    $site_name = get_bloginfo('name');
    $site_description = get_bloginfo('description') ?: 'CQC-compliant care training in Kent since 2020. CPD-accredited courses for care workers, first aid, medication management, safeguarding, and more.';
    
    $schema_graph = [];
    
    // WebSite schema
    $schema_graph[] = [
        '@type' => 'WebSite',
        '@id' => $site_url . '/#website',
        'url' => $site_url . '/',
        'name' => $site_name,
        'description' => $site_description,
        'inLanguage' => 'en-GB',
        'publisher' => ['@id' => $site_url . '/#organization'],
    ];
    
    // Organization schema
    $schema_graph[] = cta_get_organization_schema();
    
    // LocalBusiness for physical location (local/sitelinks)
    $schema_graph[] = cta_get_local_business_schema();
    
    // BreadcrumbList for homepage
    $schema_graph[] = cta_get_breadcrumb_schema([
        ['name' => 'Home', 'url' => $site_url . '/'],
    ]);
    
    return $schema_graph;
}

/**
 * Get AboutPage schema
 */
function cta_get_about_page_schema() {
    $site_url = home_url();
    $page_url = get_permalink();
    $page_title = get_the_title();
    $page_description = cta_get_meta_description();
    
    $schema_graph = [];
    
    // AboutPage schema
    $schema_graph[] = [
        '@type' => 'AboutPage',
        '@id' => $page_url . '#webpage',
        'url' => $page_url,
        'name' => $page_title,
        'description' => $page_description,
        'isPartOf' => ['@id' => $site_url . '/#website'],
        'about' => ['@id' => $site_url . '/#organization'],
        'breadcrumb' => ['@id' => $page_url . '#breadcrumb'],
    ];
    
    // Organization schema
    $schema_graph[] = cta_get_organization_schema();
    
    // LocalBusiness for physical location
    $schema_graph[] = cta_get_local_business_schema();
    
    // BreadcrumbList
    $schema_graph[] = cta_get_breadcrumb_schema([
        ['name' => 'Home', 'url' => $site_url . '/'],
        ['name' => $page_title, 'url' => $page_url],
    ]);
    
    // Add primary image if available
    $image_url = cta_get_page_schema_image();
    if ($image_url) {
        $schema_graph[0]['primaryImageOfPage'] = [
            '@type' => 'ImageObject',
            'url' => $image_url,
        ];
    }
    
    return $schema_graph;
}

/**
 * Get ContactPage schema
 */
function cta_get_contact_page_schema() {
    $site_url = home_url();
    $page_url = get_permalink();
    $page_title = get_the_title();
    $page_description = cta_get_meta_description();
    $contact = cta_get_contact_info();
    
    $schema_graph = [];
    
    // ContactPage schema
    $schema_graph[] = [
        '@type' => 'ContactPage',
        '@id' => $page_url . '#webpage',
        'url' => $page_url,
        'name' => $page_title,
        'description' => $page_description,
        'isPartOf' => ['@id' => $site_url . '/#website'],
        'about' => ['@id' => $site_url . '/#organization'],
        'breadcrumb' => ['@id' => $page_url . '#breadcrumb'],
    ];
    
    // Organization schema (includes contact info)
    $schema_graph[] = cta_get_organization_schema();
    
    // LocalBusiness for physical location
    $schema_graph[] = cta_get_local_business_schema();
    
    // BreadcrumbList
    $schema_graph[] = cta_get_breadcrumb_schema([
        ['name' => 'Home', 'url' => $site_url . '/'],
        ['name' => $page_title, 'url' => $page_url],
    ]);
    
    // Add primary image if available
    $image_url = cta_get_page_schema_image();
    if ($image_url) {
        $schema_graph[0]['primaryImageOfPage'] = [
            '@type' => 'ImageObject',
            'url' => $image_url,
        ];
    }
    
    return $schema_graph;
}

/**
 * Get CollectionPage schema for resource pages
 */
function cta_get_collection_page_schema($page_slug = '') {
    $site_url = home_url();
    $page_url = get_permalink();
    $page_title = get_the_title();
    $page_description = cta_get_meta_description();
    
    $schema_graph = [];
    
    // CollectionPage schema
    $schema_graph[] = [
        '@type' => 'CollectionPage',
        '@id' => $page_url . '#webpage',
        'url' => $page_url,
        'name' => $page_title,
        'description' => $page_description,
        'isPartOf' => ['@id' => $site_url . '/#website'],
        'about' => ['@id' => $site_url . '/#organization'],
        'breadcrumb' => ['@id' => $page_url . '#breadcrumb'],
    ];
    
    // Organization schema
    $schema_graph[] = cta_get_organization_schema();
    
    // BreadcrumbList
    $schema_graph[] = cta_get_breadcrumb_schema([
        ['name' => 'Home', 'url' => $site_url . '/'],
        ['name' => $page_title, 'url' => $page_url],
    ]);
    
    // Add primary image if available
    $image_url = cta_get_page_schema_image();
    if ($image_url) {
        $schema_graph[0]['primaryImageOfPage'] = [
            '@type' => 'ImageObject',
            'url' => $image_url,
        ];
    }
    
    return $schema_graph;
}

/**
 * Get FAQPage schema
 */
function cta_get_faq_page_schema() {
    $site_url = home_url();
    $page_url = get_permalink();
    $page_title = get_the_title();
    $page_description = cta_get_meta_description();
    
    $schema_graph = [];
    
    // FAQPage schema
    $faq_schema = [
        '@type' => 'FAQPage',
        '@id' => $page_url . '#webpage',
        'url' => $page_url,
        'name' => $page_title,
        'description' => $page_description,
        'isPartOf' => ['@id' => $site_url . '/#website'],
        'about' => ['@id' => $site_url . '/#organization'],
        'breadcrumb' => ['@id' => $page_url . '#breadcrumb'],
    ];
    
    // Extract FAQ items from multiple sources
    $mainEntity = [];
    
    // Priority 1: Check ACF repeater field 'faqs'
    if (function_exists('get_field')) {
        $acf_faqs = get_field('faqs', get_the_ID());
        if (!empty($acf_faqs) && is_array($acf_faqs)) {
            foreach ($acf_faqs as $faq) {
                if (is_array($faq) && isset($faq['question']) && isset($faq['answer'])) {
                    $question = trim($faq['question']);
                    $answer = is_array($faq['answer']) ? '' : trim(strip_tags($faq['answer']));
                    
                    // If answer is empty, try to get from WYSIWYG field
                    if (empty($answer) && isset($faq['answer'])) {
                        $answer = wp_strip_all_tags($faq['answer']);
                    }
                    
                    if (!empty($question) && !empty($answer)) {
                        $mainEntity[] = [
                            '@type' => 'Question',
                            'name' => $question,
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => $answer,
                            ],
                        ];
                    }
                }
            }
        }
    }
    
    // Priority 2: Check for FAQ custom post type (for FAQs page)
    if (empty($mainEntity) && get_post_type() === 'page') {
        $page_slug = get_post()->post_name;
        if ($page_slug === 'faqs') {
            $faq_posts = get_posts([
                'post_type' => 'faq',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'menu_order',
                'order' => 'ASC',
            ]);
            
            foreach ($faq_posts as $faq_post) {
                $question = trim($faq_post->post_title);
                // Prefer block editor content (post content), fallback to ACF
                $answer = $faq_post->post_content;
                if (empty(trim(strip_tags($answer))) && function_exists('get_field')) {
                    $answer = get_field('faq_answer', $faq_post->ID) ?: '';
                }
                $answer = wp_strip_all_tags($answer);
                
                if (!empty($question) && !empty($answer)) {
                    $mainEntity[] = [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $answer,
                        ],
                    ];
                }
            }
        }
    }
    
    // Priority 3: Parse HTML content for FAQ patterns (accordion structure)
    if (empty($mainEntity)) {
        global $post;
        $content = get_the_content();
        
        // Look for accordion FAQ patterns
        // Pattern: <button>Question</button> followed by <div>Answer</div>
        // Or: <h3>Question</h3> followed by <p>Answer</p>
        if (!empty($content)) {
            // Try to extract from accordion structure
            preg_match_all(
                '/<button[^>]*class="[^"]*accordion-trigger[^"]*"[^>]*>\s*<span[^>]*>(.*?)<\/span>/is',
                $content,
                $questions
            );
            
            preg_match_all(
                '/<div[^>]*class="[^"]*accordion-content[^"]*"[^>]*>(.*?)<\/div>/is',
                $content,
                $answers
            );
            
            if (!empty($questions[1]) && !empty($answers[1]) && count($questions[1]) === count($answers[1])) {
                for ($i = 0; $i < count($questions[1]); $i++) {
                    $question = wp_strip_all_tags($questions[1][$i]);
                    $answer = wp_strip_all_tags($answers[1][$i]);
                    
                    if (!empty($question) && !empty($answer) && strlen($answer) > 20) {
                        $mainEntity[] = [
                            '@type' => 'Question',
                            'name' => $question,
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => $answer,
                            ],
                        ];
                    }
                }
            }
        }
    }
    
    // Add mainEntity if we found FAQs
    if (!empty($mainEntity)) {
        $faq_schema['mainEntity'] = $mainEntity;
    }
    
    $schema_graph[] = $faq_schema;
    
    // Organization schema
    $schema_graph[] = cta_get_organization_schema();
    
    // BreadcrumbList
    $schema_graph[] = cta_get_breadcrumb_schema([
        ['name' => 'Home', 'url' => $site_url . '/'],
        ['name' => $page_title, 'url' => $page_url],
    ]);
    
    // Add primary image if available
    $image_url = cta_get_page_schema_image();
    if ($image_url) {
        $schema_graph[0]['primaryImageOfPage'] = [
            '@type' => 'ImageObject',
            'url' => $image_url,
        ];
    }
    
    return $schema_graph;
}

/**
 * Get Group Training page schema (WebPage + Service with OfferCatalog)
 */
function cta_get_group_training_page_schema() {
    $site_url = home_url();
    $page_url = get_permalink();
    $page_title = get_the_title();
    $page_description = cta_get_meta_description();
    
    $schema_graph = [];
    
    // WebPage schema
    $schema_graph[] = [
        '@type' => 'WebPage',
        '@id' => $page_url . '#webpage',
        'url' => $page_url,
        'name' => $page_title,
        'description' => $page_description,
        'isPartOf' => ['@id' => $site_url . '/#website'],
        'about' => ['@id' => $site_url . '/#organization'],
        'breadcrumb' => ['@id' => $page_url . '#breadcrumb'],
    ];
    
    // Service schema with OfferCatalog
    $schema_graph[] = [
        '@type' => 'Service',
        '@id' => $page_url . '#service',
        'name' => 'Group Training Services',
        'description' => 'On-site group training for care teams across the UK. Flexible scheduling, CPD-accredited certificates, and group rates.',
        'provider' => ['@id' => $site_url . '/#organization'],
        'areaServed' => [
            '@type' => 'Country',
            'name' => 'United Kingdom',
        ],
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'Group Training Courses',
            'itemListElement' => [
                [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => 'Group Training',
                    ],
                ],
            ],
        ],
    ];
    
    // Organization schema
    $schema_graph[] = cta_get_organization_schema();
    
    // BreadcrumbList
    $schema_graph[] = cta_get_breadcrumb_schema([
        ['name' => 'Home', 'url' => $site_url . '/'],
        ['name' => $page_title, 'url' => $page_url],
    ]);
    
    // Add primary image if available
    $image_url = cta_get_page_schema_image();
    if ($image_url) {
        $schema_graph[0]['primaryImageOfPage'] = [
            '@type' => 'ImageObject',
            'url' => $image_url,
        ];
    }
    
    return $schema_graph;
}

/**
 * Output permanent page schema based on page type
 */
function cta_output_permanent_page_schema() {
    // Only on pages
    if (!is_page()) {
        return;
    }
    
    // Check if it's a permanent page
    if (!function_exists('cta_is_permanent_page')) {
        return;
    }
    
    if (!cta_is_permanent_page()) {
        return;
    }
    
    $page_slug = get_post()->post_name;
    $schema_type = cta_safe_get_field('page_schema_type', get_the_ID(), '');
    
    $schema_graph = [];
    
    // Determine schema type based on page slug or ACF field
    if (is_front_page() || $page_slug === 'home') {
        $schema_graph = cta_get_homepage_schema();
    } elseif ($page_slug === 'about' || $page_slug === 'about-us') {
        $schema_graph = cta_get_about_page_schema();
    } elseif ($page_slug === 'contact') {
        $schema_graph = cta_get_contact_page_schema();
    } elseif ($page_slug === 'group-training') {
        $schema_graph = cta_get_group_training_page_schema();
    } elseif ($page_slug === 'faqs') {
        $schema_graph = cta_get_faq_page_schema();
    } elseif (in_array($page_slug, ['cqc-compliance-hub', 'downloadable-resources', 'news'], true)) {
        $schema_graph = cta_get_collection_page_schema($page_slug);
    } else {
        // Fallback to WebPage schema
        $schema_graph = [
            cta_get_webpage_schema([
                'name' => get_the_title(),
                'description' => cta_get_meta_description(),
            ]),
            cta_get_organization_schema(),
            cta_get_breadcrumb_schema(),
        ];
    }
    
    // Override with ACF schema type if set
    if (!empty($schema_type) && $schema_type !== 'WebPage') {
        // If custom schema type is set, use generic WebPage but with custom type
        // This allows flexibility for custom pages
        $page_url = get_permalink();
        $site_url = home_url();
        
        $schema_graph = [
            [
                '@type' => $schema_type,
                '@id' => $page_url . '#webpage',
                'url' => $page_url,
                'name' => get_the_title(),
                'description' => cta_get_meta_description(),
                'isPartOf' => ['@id' => $site_url . '/#website'],
                'about' => ['@id' => $site_url . '/#organization'],
                'breadcrumb' => ['@id' => $page_url . '#breadcrumb'],
            ],
            cta_get_organization_schema(),
            cta_get_breadcrumb_schema(),
        ];
        
        $image_url = cta_get_page_schema_image();
        if ($image_url) {
            $schema_graph[0]['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url' => $image_url,
            ];
        }
    }
    
    // Output schema
    if (!empty($schema_graph)) {
        cta_output_schema_json($schema_graph);
    }
}
add_action('wp_head', 'cta_output_permanent_page_schema', 10);
