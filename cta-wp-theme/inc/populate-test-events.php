<?php
/**
 * Auto-populate test course events on theme activation/update
 * 
 * Creates sample upcoming course events if none exist
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Check if there are any upcoming course events
 * 
 * @return bool True if upcoming events exist, false otherwise
 */
function cta_has_upcoming_events() {
    $today = date('Y-m-d');
    
    $upcoming = new WP_Query([
        'post_type' => 'course_event',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'meta_key' => 'event_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => [
            [
                'key' => 'event_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE',
            ],
        ],
    ]);
    
    $has_upcoming = $upcoming->have_posts();
    wp_reset_postdata();
    
    return $has_upcoming;
}

/**
 * Populate test discounts
 * Creates sample site-wide discount, course discounts, and discount codes
 */
function cta_populate_test_discounts() {
    // Check if site-wide discount already exists
    $existing_site_wide = get_option('cta_site_wide_discount_active', false);
    
    if (!$existing_site_wide) {
        // Create a site-wide discount (15% off, expires in 2 months)
        $expiry_date = date('Y-m-d', strtotime('+2 months'));
        update_option('cta_site_wide_discount_active', true);
        update_option('cta_site_wide_discount_percentage', 15);
        update_option('cta_site_wide_discount_label', 'Spring Sale');
        update_option('cta_site_wide_discount_expiry', $expiry_date);
    }
    
    // Check if discount codes exist
    $existing_codes = cta_get_discount_codes();
    
    if (empty($existing_codes)) {
        // Create some test discount codes
        $test_codes = [
            [
                'code' => 'WELCOME20',
                'discount' => 20,
                'active' => true,
                'expiry_date' => date('Y-m-d', strtotime('+3 months')),
                'sync_to_eventbrite' => false,
            ],
            [
                'code' => 'EARLYBIRD15',
                'discount' => 15,
                'active' => true,
                'expiry_date' => date('Y-m-d', strtotime('+1 month')),
                'sync_to_eventbrite' => false,
            ],
            [
                'code' => 'STUDENT10',
                'discount' => 10,
                'active' => true,
                'expiry_date' => date('Y-m-d', strtotime('+6 months')),
                'sync_to_eventbrite' => false,
            ],
        ];
        
        cta_save_discount_codes($test_codes);
    }
    
    // Get courses to add discounts to
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => 5,
        'post_status' => 'publish',
        'orderby' => 'rand',
    ]);
    
    if (empty($courses)) {
        return;
    }
    
    // Add course-specific discounts to some courses
    $discount_configs = [
        ['percentage' => 25, 'label' => 'Limited Time Offer', 'requires_code' => false, 'code' => ''],
        ['percentage' => 20, 'label' => 'Early Bird Special', 'requires_code' => true, 'code' => 'EARLYBIRD'],
        ['percentage' => 15, 'label' => 'Group Booking Discount', 'requires_code' => false, 'code' => ''],
    ];
    
    $discount_index = 0;
    foreach ($courses as $course) {
        // Skip if course already has a discount
        $existing_discount = get_post_meta($course->ID, '_course_discount_active', true);
        if ($existing_discount === '1') {
            continue;
        }
        
        // Apply discount to first 3 courses
        if ($discount_index < 3) {
            $config = $discount_configs[$discount_index];
            $expiry_date = date('Y-m-d', strtotime('+2 months'));
            
            update_post_meta($course->ID, '_course_discount_active', true);
            update_post_meta($course->ID, '_course_discount_percentage', $config['percentage']);
            update_post_meta($course->ID, '_course_discount_label', $config['label']);
            update_post_meta($course->ID, '_course_discount_requires_code', $config['requires_code'] ? 1 : 0);
            update_post_meta($course->ID, '_course_discount_code', $config['code']);
            update_post_meta($course->ID, '_course_discount_expiry', $expiry_date);
            
            $discount_index++;
        }
    }
}

/**
 * Populate test course events
 * Creates 6-8 sample events with dates spread over the next 3 months
 */
function cta_populate_test_course_events() {
    // Check if we already have upcoming events
    if (cta_has_upcoming_events()) {
        return;
    }
    
    // Get some existing courses to link to
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => 5,
        'post_status' => 'publish',
        'orderby' => 'rand',
    ]);
    
    if (empty($courses)) {
        // No courses exist, can't create events
        return;
    }
    
    // Create events spread over the next 3 months
    $locations = [
        'Maidstone Training Centre',
        'London Training Centre',
        'Canterbury Training Centre',
        'Online',
        'Kent Training Centre',
    ];
    
    $times = [
        ['09:00', '17:00'],
        ['09:30', '16:30'],
        ['10:00', '16:00'],
    ];
    
    $event_count = 0;
    $max_events = min(8, count($courses) * 2);
    
    // Start from next week
    $base_date = strtotime('+1 week');
    
    for ($i = 0; $i < $max_events && $event_count < 8; $i++) {
        // Pick a random course
        $course = $courses[array_rand($courses)];
        
        // Calculate date (spread over 3 months, roughly every 2 weeks)
        $days_offset = ($i * 14) + rand(0, 7);
        $event_date = date('Y-m-d', strtotime("+{$days_offset} days", $base_date));
        
        // Skip if date is more than 3 months away
        if (strtotime($event_date) > strtotime('+3 months')) {
            continue;
        }
        
        // Pick random location and time
        $location = $locations[array_rand($locations)];
        $time_pair = $times[array_rand($times)];
        
        // Get course price or use default
        $course_price = get_field('course_price', $course->ID);
        $event_price = $course_price ?: 299;
        
        // Get course duration
        $duration = get_field('course_duration', $course->ID) ?: '1 Day';
        
        // Create event post
        $event_title = $course->post_title . ' - ' . date('j M Y', strtotime($event_date));
        
        $event_id = wp_insert_post([
            'post_title' => $event_title,
            'post_type' => 'course_event',
            'post_status' => 'publish',
            'post_content' => '',
        ]);
        
        if (is_wp_error($event_id) || !$event_id) {
            continue;
        }
        
        // Set ACF fields
        update_field('linked_course', $course->ID, $event_id);
        update_field('event_date', $event_date, $event_id);
        update_field('start_time', $time_pair[0], $event_id);
        update_field('end_time', $time_pair[1], $event_id);
        update_field('event_location', $location, $event_id);
        update_field('event_price', $event_price, $event_id);
        
        // Set spaces (random between 5-20)
        $spaces = rand(5, 20);
        update_field('spaces_available', $spaces, $event_id);
        update_field('total_spaces', $spaces + rand(5, 15), $event_id);
        
        // Copy course thumbnail if available
        if (has_post_thumbnail($course->ID)) {
            $thumbnail_id = get_post_thumbnail_id($course->ID);
            set_post_thumbnail($event_id, $thumbnail_id);
        }
        
        $event_count++;
    }
}

/**
 * Run on theme activation
 */
function cta_populate_events_on_activation() {
    cta_populate_test_discounts();
    cta_populate_test_course_events();
}
add_action('after_switch_theme', 'cta_populate_events_on_activation');

/**
 * Run on theme update (check version)
 */
function cta_populate_events_on_update() {
    $stored_version = get_option('cta_theme_version', '0.0.0');
    $current_version = CTA_THEME_VERSION;
    
    // Only run if version has changed
    if (version_compare($stored_version, $current_version, '<')) {
        cta_populate_test_discounts();
        cta_populate_test_course_events();
        update_option('cta_theme_version', $current_version);
    }
}
add_action('admin_init', 'cta_populate_events_on_update');
