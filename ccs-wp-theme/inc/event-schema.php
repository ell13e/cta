<?php
/**
 * Event Schema Markup (Google Event Type)
 * 
 * Generates JSON-LD structured data for event pages per Google's requirements
 * for triggering rich event snippets in search results.
 *
 * Compliant with:
 * - Google Event Schema documentation
 * - schema.org/Event specification
 * - Post-December 2025 SEO best practices
 *
 * @package ccs-theme
 * @since 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Generate Event schema markup (Google's preferred type for events)
 * 
 * Replaces CourseInstance with full Event schema including:
 * - eventStatus (Scheduled, Cancelled, Postponed, Rescheduled)
 * - eventAttendanceMode (Offline, Online, Mixed)
 * - offers with pricing and availability
 * - endDate
 * - performer/organizer
 * - aggregateRating if reviews exist
 * 
 * @param int $post_id Event post ID (course_event)
 * @return array Event schema array ready for JSON-LD output
 */
function ccs_get_event_schema($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    // Core event fields
    $event_title = get_the_title($post_id);
    $event_url = get_permalink($post_id);
    $linked_course = ccs_safe_get_field('linked_course', $post_id, null);
    
    // Date/time fields
    $event_date = ccs_safe_get_field('event_date', $post_id, '');
    $start_time = ccs_safe_get_field('start_time', $post_id, '09:00');
    $end_time = ccs_safe_get_field('end_time', $post_id, '17:00');
    $event_status = ccs_safe_get_field('event_status', $post_id, 'Scheduled');
    $event_attendance_mode = ccs_safe_get_field('event_attendance_mode', $post_id, 'OfflineEventAttendanceMode');
    
    // Location
    $location_name = ccs_safe_get_field('event_location', $post_id, 'Maidstone');
    $location_address = ccs_safe_get_field('event_location_address', $post_id, '');
    
    // Pricing & Availability
    $event_price = ccs_safe_get_field('event_price', $post_id, '');
    $spaces_available = ccs_safe_get_field('spaces_available', $post_id, '');
    $currency = 'GBP';
    
    // Description
    $description = get_the_excerpt($post_id) ?: wp_trim_words(get_the_content(null, null, $post_id), 30);
    
    // Image for schema
    $image_url = ccs_get_page_schema_image($post_id);
    
    // Organization/Performer
    $contact = ccs_get_contact_info();
    $org_name = get_bloginfo('name');
    
    // Build schema
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        '@id' => $event_url . '#event',
        'name' => $event_title,
        'description' => $description,
        'url' => $event_url,
        'image' => $image_url,
        'inLanguage' => 'en-GB',
    ];

    // Add date/time in ISO 8601 format
    if (!empty($event_date)) {
        $start_datetime = ccs_format_event_datetime($event_date, $start_time);
        if ($start_datetime) {
            $schema['startDate'] = $start_datetime;
        }
        
        if (!empty($end_time)) {
            $end_datetime = ccs_format_event_datetime($event_date, $end_time);
            if ($end_datetime) {
                $schema['endDate'] = $end_datetime;
            }
        }
    }

    // Event status (Scheduled, Cancelled, Postponed, Rescheduled)
    // Google supports: EventScheduled, EventCancelled, EventPostponed, EventRescheduled
    $status_map = [
        'Scheduled' => 'https://schema.org/EventScheduled',
        'Cancelled' => 'https://schema.org/EventCancelled',
        'Postponed' => 'https://schema.org/EventPostponed',
        'Rescheduled' => 'https://schema.org/EventRescheduled',
    ];
    
    if (isset($status_map[$event_status])) {
        $schema['eventStatus'] = $status_map[$event_status];
    } else {
        $schema['eventStatus'] = $status_map['Scheduled'];
    }

    // Event attendance mode (Online, Offline, Mixed)
    $attendance_map = [
        'OfflineEventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'OnlineEventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
        'MixedEventAttendanceMode' => 'https://schema.org/MixedEventAttendanceMode',
    ];
    
    if (isset($attendance_map[$event_attendance_mode])) {
        $schema['eventAttendanceMode'] = $attendance_map[$event_attendance_mode];
    } else {
        $schema['eventAttendanceMode'] = $attendance_map['OfflineEventAttendanceMode'];
    }

    // Location (Place type)
    $location_schema = [
        '@type' => 'Place',
        'name' => $location_name,
    ];
    
    if (!empty($location_address)) {
        $location_schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $location_address,
            'addressLocality' => 'Maidstone',
            'addressRegion' => 'Kent',
            'postalCode' => 'ME14 5NZ',
            'addressCountry' => 'GB',
        ];
    } else {
        // Default to organization location if not specified
        $location_schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => 'The Maidstone Studios, New Cut Road',
            'addressLocality' => 'Maidstone',
            'addressRegion' => 'Kent',
            'postalCode' => 'ME14 5NZ',
            'addressCountry' => 'GB',
        ];
    }
    
    // Add geo coordinates if available
    $latitude = get_theme_mod('ccs_seo_geo_lat', '51.264494');
    $longitude = get_theme_mod('ccs_seo_geo_lng', '0.545844');
    if ($latitude && $longitude) {
        $location_schema['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ];
    }
    
    $schema['location'] = $location_schema;

    // Organizer (Educational Organization)
    $schema['organizer'] = [
        '@type' => 'EducationalOrganization',
        '@id' => home_url('/#organization'),
        'name' => $org_name,
        'url' => home_url('/'),
        'email' => $contact['email'] ?? '',
        'telephone' => $contact['phone'] ?? '',
    ];

    // Offers (pricing and ticket availability)
    if (!empty($event_price)) {
        $schema['offers'] = [
            '@type' => 'Offer',
            'url' => $event_url,
            'price' => (string) floatval($event_price),
            'priceCurrency' => $currency,
            'availability' => 'https://schema.org/InStock', // or OutOfStock if spaces_available = 0
        ];
        
        // Update availability based on spaces remaining
        if (!empty($spaces_available) && intval($spaces_available) <= 0) {
            $schema['offers']['availability'] = 'https://schema.org/OutOfStock';
        }
        
        // Add purchase URL
        $schema['offers']['url'] = $event_url;
    }

    // Performer/Instructor (if available)
    $performer_name = ccs_safe_get_field('event_instructor', $post_id, '');
    if (!empty($performer_name)) {
        $schema['performer'] = [
            '@type' => 'Person',
            'name' => $performer_name,
        ];
    }

    // Aggregate rating (if reviews exist in future implementation)
    $avg_rating = get_post_meta($post_id, '_event_avg_rating', true);
    $review_count = get_post_meta($post_id, '_event_review_count', true);
    
    if ($avg_rating && $review_count) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => (float) $avg_rating,
            'reviewCount' => (int) $review_count,
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }

    return $schema;
}

/**
 * Format date and time into ISO 8601 format with timezone
 * 
 * Converts "2026-03-15" + "14:30" to "2026-03-15T14:30:00+00:00"
 * 
 * @param string $date Date string (YYYY-MM-DD format)
 * @param string $time Time string (HH:MM format)
 * @return string ISO 8601 formatted datetime or empty string
 */
function ccs_format_event_datetime($date, $time = '09:00') {
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return '';
    }
    
    // Validate time format
    if (!preg_match('/^\d{2}:\d{2}/', $time)) {
        $time = '09:00'; // Default to 9 AM
    }
    
    // Combine and format
    $datetime_str = $date . 'T' . $time . ':00';
    
    // Add UK timezone (+00:00 for UTC/GMT)
    $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s', $datetime_str);
    
    if (!$datetime) {
        return '';
    }
    
    // Return in ISO 8601 with timezone
    return $datetime->format('Y-m-d\TH:i:s+00:00');
}

/**
 * Generate schema for event listing pages (archives)
 * 
 * Creates a CollectionPage schema combining:
 * - EventCollection or SearchResultsPage type
 * - Multiple Event items
 * 
 * @param array $event_ids Array of post IDs to include
 * @return array Collection schema
 */
function ccs_get_event_collection_schema($event_ids = []) {
    $site_url = home_url();
    $page_url = get_post_type_archive_link('course_event');
    
    // Get all upcoming events if none specified
    if (empty($event_ids)) {
        $args = [
            'post_type' => 'course_event',
            'posts_per_page' => 12,
            'meta_key' => 'event_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'event_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
            ],
        ];
        
        $query = new WP_Query($args);
        $event_ids = wp_list_pluck($query->posts, 'ID');
    }

    // Build collection schema
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        '@id' => $page_url . '#webpage',
        'url' => $page_url,
        'name' => 'Upcoming Events - ' . get_bloginfo('name'),
        'description' => 'Upcoming training courses and events.',
        'isPartOf' => [
            '@id' => $site_url . '/#website',
        ],
        'mainEntity' => [],
    ];

    // Add individual event schemas
    foreach ($event_ids as $event_id) {
        // Simplified event reference (full schema too large for page output)
        $event_schema = [
            '@type' => 'Event',
            '@id' => get_permalink($event_id) . '#event',
            'name' => get_the_title($event_id),
            'url' => get_permalink($event_id),
            'startDate' => ccs_get_event_start_date_iso($event_id),
        ];
        
        $schema['mainEntity'][] = $event_schema;
    }

    return $schema;
}

/**
 * Helper: Get event start date in ISO 8601 format
 * 
 * @param int $post_id Event post ID
 * @return string ISO 8601 datetime
 */
function ccs_get_event_start_date_iso($post_id) {
    $event_date = ccs_safe_get_field('event_date', $post_id, '');
    $start_time = ccs_safe_get_field('start_time', $post_id, '09:00');
    
    return ccs_format_event_datetime($event_date, $start_time);
}

/**
 * Output Event schema JSON-LD to page head
 * 
 * Should be called in wp_head on event pages
 * 
 * @param int $post_id Post ID to generate schema for
 * @param bool $include_organization Whether to include org schema
 * @return void
 */
function ccs_output_event_schema($post_id = null, $include_organization = true) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    if (!$post_id || get_post_type($post_id) !== 'course_event') {
        return;
    }

    $schema_graph = [];

    // Add organization schema
    if ($include_organization) {
        $schema_graph[] = ccs_get_organization_schema();
    }

    // Add website schema
    $schema_graph[] = [
        '@type' => 'WebSite',
        '@id' => home_url('/#website'),
        'url' => home_url('/'),
        'name' => get_bloginfo('name'),
        'inLanguage' => 'en-GB',
    ];

    // Add breadcrumb schema
    $breadcrumb_items = [
        ['name' => 'Home', 'url' => home_url('/'), 'position' => 1],
        ['name' => 'Upcoming Events', 'url' => get_post_type_archive_link('course_event'), 'position' => 2],
        ['name' => get_the_title($post_id), 'url' => get_permalink($post_id), 'position' => 3],
    ];
    $schema_graph[] = ccs_get_breadcrumb_schema($breadcrumb_items);

    // Add event schema
    $schema_graph[] = ccs_get_event_schema($post_id);

    // Output
    ccs_output_schema_json($schema_graph);
}

/**
 * Output Event collection schema on archive pages
 * 
 * @param array $event_ids Array of event post IDs (optional)
 * @return void
 */
function ccs_output_event_collection_schema($event_ids = []) {
    $schema_graph = [];

    // Add organization
    $schema_graph[] = ccs_get_organization_schema();

    // Add website
    $schema_graph[] = [
        '@type' => 'WebSite',
        '@id' => home_url('/#website'),
        'url' => home_url('/'),
        'name' => get_bloginfo('name'),
        'inLanguage' => 'en-GB',
    ];

    // Add collection
    $schema_graph[] = ccs_get_event_collection_schema($event_ids);

    // Output
    ccs_output_schema_json($schema_graph);
}

/**
 * Hook: Output event schema on single event pages
 */
function ccs_hook_output_event_schema() {
    if (is_singular('course_event')) {
        ccs_output_event_schema(null, true);
    }
}
add_action('wp_head', 'ccs_hook_output_event_schema', 15); // After open tag, before other meta

/**
 * Hook: Output event collection schema on event archive
 */
function ccs_hook_output_event_archive_schema() {
    if (is_post_type_archive('course_event')) {
        ccs_output_event_collection_schema();
    }
}
add_action('wp_head', 'ccs_hook_output_event_archive_schema', 15);
