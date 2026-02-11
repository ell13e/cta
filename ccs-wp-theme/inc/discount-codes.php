<?php
/**
 * Discount Codes Management
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Add Discount Codes admin menu
 */
function ccs_discount_codes_menu() {
    $cap = function_exists('ccs_newsletter_required_capability') ? ccs_newsletter_required_capability() : 'edit_others_posts';
    add_submenu_page(
        'edit.php?post_type=course',
        'Discount Codes',
        'Discount Codes',
        $cap,
        'cta-discount-codes',
        'ccs_discount_codes_page'
    );
}
add_action('admin_menu', 'ccs_discount_codes_menu', 26);

/**
 * Get all discount codes
 */
function ccs_get_discount_codes() {
    $codes = get_option('ccs_discount_codes', []);
    return is_array($codes) ? $codes : [];
}

/**
 * Save discount codes
 */
function ccs_save_discount_codes($codes) {
    update_option('ccs_discount_codes', $codes);
}

/**
 * Validate discount code
 * 
 * @param string $code Discount code to validate
 * @return array ['valid' => bool, 'message' => string, 'discount' => float, 'error_type' => string|null]
 */
function ccs_validate_discount_code($code) {
    if (empty($code)) {
        return [
            'valid' => false,
            'message' => '',
            'discount' => 0,
            'error_type' => 'empty',
        ];
    }
    
    $codes = ccs_get_discount_codes();
    $code_upper = strtoupper(trim($code));
    $code_found = false;
    
    foreach ($codes as $discount_code) {
        $stored_code = strtoupper(trim($discount_code['code'] ?? ''));
        
        if ($stored_code === $code_upper) {
            $code_found = true;

            // Check if expired
            if (!empty($discount_code['expiry_date'])) {
                $expiry = strtotime($discount_code['expiry_date']);
                $now = current_time('timestamp');
                
                if ($expiry && $now > $expiry) {
                    $expiry_formatted = date('j F Y', $expiry);
                    return [
                        'valid' => false,
                        'message' => 'This discount code expired on ' . $expiry_formatted,
                        'discount' => 0,
                        'error_type' => 'expired',
                        'expiry_date' => $expiry_formatted,
                    ];
                }
            }
            
            // Check if active
            if (isset($discount_code['active']) && !$discount_code['active']) {
                return [
                    'valid' => false,
                    'message' => 'This discount code is no longer active',
                    'discount' => 0,
                    'error_type' => 'inactive',
                ];
            }
            
            // Valid code - return 20% discount
            return [
                'valid' => true,
                'message' => '20% Off',
                'discount' => 20,
                'code' => $discount_code['code'],
                'error_type' => null,
            ];
        }
    }
    
    if (!$code_found) {
        return [
            'valid' => false,
            'message' => 'This discount code is not valid. Please check and try again.',
            'discount' => 0,
            'error_type' => 'not_found',
        ];
    }

    // Fallback (should not be hit)
    return [
        'valid' => false,
        'message' => 'Unable to validate this discount code. Please try again.',
        'discount' => 0,
        'error_type' => 'unknown',
    ];
}

/**
 * Get site-wide discount settings
 */
function ccs_get_site_wide_discount() {
    return [
        'active' => get_option('ccs_site_wide_discount_active', false),
        'percentage' => floatval(get_option('ccs_site_wide_discount_percentage', 0)),
        'label' => get_option('ccs_site_wide_discount_label', 'Site-Wide Sale'),
        'expiry_date' => get_option('ccs_site_wide_discount_expiry', ''),
    ];
}

/**
 * Check if site-wide discount is active
 */
function ccs_is_site_wide_discount_active() {
    $discount = ccs_get_site_wide_discount();
    
    if (!$discount['active'] || $discount['percentage'] <= 0) {
        return false;
    }
    
    // Check expiry date
    if (!empty($discount['expiry_date'])) {
        $expiry = strtotime($discount['expiry_date']);
        $now = current_time('timestamp');
        if ($expiry && $now > $expiry) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get site-wide discount percentage
 */
function ccs_get_site_wide_discount_percentage() {
    if (!ccs_is_site_wide_discount_active()) {
        return 0;
    }
    $discount = ccs_get_site_wide_discount();
    return $discount['percentage'];
}

/**
 * Calculate discounted price
 */
function ccs_apply_site_wide_discount($original_price) {
    $discount_percent = ccs_get_site_wide_discount_percentage();
    if ($discount_percent <= 0) {
        return $original_price;
    }
    return $original_price * (1 - ($discount_percent / 100));
}

/**
 * Get course-specific discount settings
 */
function ccs_get_course_discount($course_id) {
    return [
        'active' => get_post_meta($course_id, '_course_discount_active', true) === '1',
        'percentage' => floatval(get_post_meta($course_id, '_course_discount_percentage', true)),
        'label' => get_post_meta($course_id, '_course_discount_label', true) ?: 'Special Offer',
        'requires_code' => get_post_meta($course_id, '_course_discount_requires_code', true) === '1',
        'discount_code' => get_post_meta($course_id, '_course_discount_code', true),
        'expiry_date' => get_post_meta($course_id, '_course_discount_expiry', true),
    ];
}

/**
 * Check if course-specific discount is active
 */
function ccs_is_course_discount_active($course_id) {
    $discount = ccs_get_course_discount($course_id);
    
    if (!$discount['active'] || $discount['percentage'] <= 0) {
        return false;
    }
    
    // Check expiry date
    if (!empty($discount['expiry_date'])) {
        $expiry = strtotime($discount['expiry_date']);
        $now = current_time('timestamp');
        if ($expiry && $now > $expiry) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get course-specific discount percentage
 */
function ccs_get_course_discount_percentage($course_id) {
    if (!ccs_is_course_discount_active($course_id)) {
        return 0;
    }
    $discount = ccs_get_course_discount($course_id);
    return $discount['percentage'];
}

/**
 * Calculate discounted price for a course
 */
function ccs_apply_course_discount($course_id, $original_price) {
    $discount_percent = ccs_get_course_discount_percentage($course_id);
    if ($discount_percent <= 0) {
        return $original_price;
    }
    return $original_price * (1 - ($discount_percent / 100));
}

/**
 * AJAX handler to get course discount data
 */
function ccs_get_course_discount_ajax() {
    check_ajax_referer('ccs_discount_codes_nonce', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }
    
    $cap = function_exists('ccs_newsletter_required_capability') ? ccs_newsletter_required_capability() : 'edit_others_posts';
    if (!current_user_can($cap)) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    $course_id = absint($_POST['course_id'] ?? 0);
    
    if ($course_id <= 0) {
        wp_send_json_error(['message' => 'Invalid course ID']);
    }
    
    $course = get_post($course_id);
    if (!$course || $course->post_type !== 'course') {
        wp_send_json_error(['message' => 'Course not found']);
    }
    
    $discount = ccs_get_course_discount($course_id);
    
    wp_send_json_success([
        'course_id' => $course_id,
        'course_title' => $course->post_title,
        'active' => $discount['active'],
        'percentage' => $discount['percentage'],
        'label' => $discount['label'],
        'requires_code' => $discount['requires_code'],
        'discount_code' => $discount['discount_code'],
        'expiry_date' => $discount['expiry_date'],
    ]);
}
add_action('wp_ajax_ccs_get_course_discount', 'ccs_get_course_discount_ajax');

/**
 * Discount Codes admin page
 */
function ccs_discount_codes_page() {
    $cap = function_exists('ccs_newsletter_required_capability') ? ccs_newsletter_required_capability() : 'edit_others_posts';
    if (!current_user_can($cap)) {
        wp_die('You do not have permission to access this page.');
    }
    
    // Handle site-wide discount form submission
    if (isset($_POST['ccs_save_site_wide_discount']) && check_admin_referer('ccs_site_wide_discount_action')) {
        $active = isset($_POST['active']) ? 1 : 0;
        $percentage = floatval($_POST['percentage'] ?? 0);
        $label = sanitize_text_field($_POST['label'] ?? 'Site-Wide Sale');
        $expiry_date = sanitize_text_field($_POST['expiry_date'] ?? '');
        
        // Validate percentage
        if ($percentage < 0 || $percentage > 100) {
            echo '<div class="notice notice-error is-dismissible"><p>Discount percentage must be between 0 and 100.</p></div>';
        } else {
            update_option('ccs_site_wide_discount_active', $active);
            update_option('ccs_site_wide_discount_percentage', $percentage);
            update_option('ccs_site_wide_discount_label', $label);
            update_option('ccs_site_wide_discount_expiry', $expiry_date);
            
            if ($active && $percentage > 0) {
                echo '<div class="notice notice-success is-dismissible"><p>Site-wide discount saved and activated!</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>Site-wide discount settings saved.</p></div>';
            }
        }
    }
    
    // Handle course-specific discount form submission
    if (isset($_POST['ccs_save_course_discount']) && check_admin_referer('ccs_course_discount_action')) {
        $course_ids = isset($_POST['course_ids']) && is_array($_POST['course_ids']) ? array_map('absint', $_POST['course_ids']) : [];
        $active = isset($_POST['course_discount_active']) ? 1 : 0;
        $percentage = floatval($_POST['course_discount_percentage'] ?? 0);
        $label = sanitize_text_field($_POST['course_discount_label'] ?? 'Special Offer');
        $requires_code = isset($_POST['course_discount_requires_code']) ? 1 : 0;
        $discount_code = $requires_code ? strtoupper(sanitize_text_field($_POST['course_discount_code'] ?? '')) : '';
        $expiry_date = sanitize_text_field($_POST['course_discount_expiry'] ?? '');
        
        if (!empty($course_ids)) {
            // Validate percentage
            if ($percentage < 0 || $percentage > 100) {
                echo '<div class="notice notice-error is-dismissible"><p>Discount percentage must be between 0 and 100.</p></div>';
            } elseif ($requires_code && empty($discount_code)) {
                echo '<div class="notice notice-error is-dismissible"><p>Discount code is required when "Requires Discount Code" is checked.</p></div>';
            } else {
                // Validate discount code if required
                $code_valid = true;
                if ($requires_code && !empty($discount_code)) {
                    $codes = ccs_get_discount_codes();
                    $code_exists = false;
                    $code_upper = strtoupper(trim($discount_code));
                    
                    foreach ($codes as $code_data) {
                        $stored_code = strtoupper(trim($code_data['code'] ?? ''));
                        if ($stored_code === $code_upper) {
                            $code_exists = true;
                            // Check if code is active and not expired
                            if (isset($code_data['active']) && !$code_data['active']) {
                                echo '<div class="notice notice-warning is-dismissible"><p><strong>Warning:</strong> The discount code "' . esc_html($discount_code) . '" exists but is inactive. The discount will not work until the code is activated.</p></div>';
                            } elseif (!empty($code_data['expiry_date'])) {
                                $expiry = strtotime($code_data['expiry_date']);
                                $now = current_time('timestamp');
                                if ($expiry && $now > $expiry) {
                                    echo '<div class="notice notice-warning is-dismissible"><p><strong>Warning:</strong> The discount code "' . esc_html($discount_code) . '" exists but has expired. The discount will not work.</p></div>';
                                }
                            }
                            break;
                        }
                    }
                    
                    if (!$code_exists) {
                        echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> The discount code "' . esc_html($discount_code) . '" does not exist in your discount codes list. Please create it first or use an existing code.</p></div>';
                        $code_valid = false;
                    }
                }
                
                if ($code_valid) {
                    // Save discount for all selected courses
                    $saved_courses = [];
                    $failed_courses = [];
                    
                    foreach ($course_ids as $course_id) {
                        $course = get_post($course_id);
                        if ($course && $course->post_type === 'course') {
                            update_post_meta($course_id, '_course_discount_active', $active);
                            update_post_meta($course_id, '_course_discount_percentage', $percentage);
                            update_post_meta($course_id, '_course_discount_label', $label);
                            update_post_meta($course_id, '_course_discount_requires_code', $requires_code);
                            update_post_meta($course_id, '_course_discount_code', $discount_code);
                            update_post_meta($course_id, '_course_discount_expiry', $expiry_date);
                            $saved_courses[] = $course->post_title;
                        } else {
                            $failed_courses[] = $course_id;
                        }
                    }
                    
                    if (!empty($saved_courses)) {
                        $course_count = count($saved_courses);
                        $course_list = $course_count <= 3 ? implode(', ', $saved_courses) : implode(', ', array_slice($saved_courses, 0, 3)) . ' and ' . ($course_count - 3) . ' more';
                        
                        if ($active && $percentage > 0) {
                            echo '<div class="notice notice-success is-dismissible"><p>Course discount saved for ' . $course_count . ' course' . ($course_count !== 1 ? 's' : '') . ': ' . esc_html($course_list) . '</p></div>';
                        } else {
                            echo '<div class="notice notice-success is-dismissible"><p>Course discount settings saved for ' . $course_count . ' course' . ($course_count !== 1 ? 's' : '') . ': ' . esc_html($course_list) . '</p></div>';
                        }
                    }
                    
                    if (!empty($failed_courses)) {
                        echo '<div class="notice notice-warning is-dismissible"><p>Some courses could not be updated: ' . esc_html(implode(', ', $failed_courses)) . '</p></div>';
                    }
                }
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Please select at least one course.</p></div>';
        }
    }
    
    // Handle form submissions
    if (isset($_POST['ccs_add_discount_code']) && check_admin_referer('ccs_discount_codes_action')) {
        $code = strtoupper(sanitize_text_field($_POST['code'] ?? ''));
        $expiry_date = sanitize_text_field($_POST['expiry_date'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $sync_to_eventbrite = isset($_POST['sync_to_eventbrite']) ? 1 : 0;
        
        if (!empty($code)) {
            $codes = ccs_get_discount_codes();
            
            // Check if code already exists
            $exists = false;
            foreach ($codes as $existing) {
                if (strtoupper($existing['code']) === $code) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $new_code = [
                    'code' => $code,
                    'expiry_date' => $expiry_date,
                    'active' => $active,
                    'sync_to_eventbrite' => $sync_to_eventbrite,
                    'created' => current_time('mysql')
                ];
                
                // Sync to Eventbrite if enabled (with rate limiting - best practice)
                if ($sync_to_eventbrite && $active && function_exists('ccs_sync_discount_code_to_eventbrite')) {
                    // Add small delay to avoid rate limits (best practice)
                    usleep(200000); // 0.2 seconds delay
                    $new_code = ccs_sync_discount_code_to_eventbrite($new_code, count($codes));
                }
                
                $codes[] = $new_code;
                ccs_save_discount_codes($codes);
                
                $sync_msg = $sync_to_eventbrite ? ' and synced to Eventbrite' : '';
                echo '<div class="notice notice-success is-dismissible"><p>Discount code added successfully' . $sync_msg . '!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>This discount code already exists.</p></div>';
            }
        }
    }
    
    // Handle delete
    if (isset($_GET['delete']) && check_admin_referer('ccs_delete_discount_code_' . $_GET['delete'])) {
        $delete_index = absint($_GET['delete']);
        $codes = ccs_get_discount_codes();
        
        if (isset($codes[$delete_index])) {
            $code_to_delete = $codes[$delete_index];
            $code_name = $code_to_delete['code'] ?? 'Unknown';
            
            // Delete from Eventbrite if synced
            $eventbrite_message = '';
            if (!empty($code_to_delete['eventbrite_discount_id']) && function_exists('ccs_delete_eventbrite_discount')) {
                $delete_result = ccs_delete_eventbrite_discount($code_to_delete['eventbrite_discount_id']);
                
                if (is_wp_error($delete_result)) {
                    $delete_error_message = (is_object($delete_result) && method_exists($delete_result, 'get_error_message'))
                        ? (string) $delete_result->get_error_message()
                        : 'Unknown error';
                    error_log('Eventbrite Discount Delete Error: ' . $delete_error_message);
                    $eventbrite_message = ' <strong>Note:</strong> Could not remove from Eventbrite: ' . esc_html($delete_error_message);
                } elseif (is_array($delete_result)) {
                    if ($delete_result['method'] === 'expired') {
                        $eventbrite_message = ' <strong>Note:</strong> This discount has been used on Eventbrite and cannot be deleted. It has been expired instead (set expiry to yesterday).';
                    } elseif ($delete_result['method'] === 'deleted') {
                        $eventbrite_message = ' Removed from Eventbrite.';
                    }
                }
            }
            
            unset($codes[$delete_index]);
            $codes = array_values($codes); // Re-index array
            ccs_save_discount_codes($codes);
            
            echo '<div class="notice notice-success is-dismissible"><p>Discount code "' . esc_html($code_name) . '" deleted successfully from WordPress.' . $eventbrite_message . '</p></div>';
        }
    }
    
    // Handle toggle active
    if (isset($_GET['toggle']) && check_admin_referer('ccs_toggle_discount_code_' . $_GET['toggle'])) {
        $toggle_index = absint($_GET['toggle']);
        $codes = ccs_get_discount_codes();
        
        if (isset($codes[$toggle_index])) {
            $codes[$toggle_index]['active'] = !($codes[$toggle_index]['active'] ?? true);
            
            // Sync to Eventbrite if enabled (with rate limiting - best practice)
            if (!empty($codes[$toggle_index]['sync_to_eventbrite']) && function_exists('ccs_sync_discount_code_to_eventbrite')) {
                // Add small delay to avoid rate limits (best practice)
                usleep(200000); // 0.2 seconds delay
                $codes[$toggle_index] = ccs_sync_discount_code_to_eventbrite($codes[$toggle_index], $toggle_index);
            }
            
            ccs_save_discount_codes($codes);
            echo '<div class="notice notice-success is-dismissible"><p>Discount code status updated!</p></div>';
        }
    }
    
    // Handle toggle Eventbrite sync
    if (isset($_GET['toggle_eventbrite']) && check_admin_referer('ccs_toggle_eventbrite_sync_' . $_GET['toggle_eventbrite'])) {
        $toggle_index = absint($_GET['toggle_eventbrite']);
        $codes = ccs_get_discount_codes();
        
        if (isset($codes[$toggle_index])) {
            $codes[$toggle_index]['sync_to_eventbrite'] = !($codes[$toggle_index]['sync_to_eventbrite'] ?? false);
            
            // Sync to Eventbrite if enabled
            if (!empty($codes[$toggle_index]['sync_to_eventbrite']) && function_exists('ccs_sync_discount_code_to_eventbrite')) {
                $codes[$toggle_index] = ccs_sync_discount_code_to_eventbrite($codes[$toggle_index], $toggle_index);
            } elseif (empty($codes[$toggle_index]['sync_to_eventbrite']) && !empty($codes[$toggle_index]['eventbrite_discount_id']) && function_exists('ccs_delete_eventbrite_discount')) {
                // Add small delay to avoid rate limits (best practice)
                usleep(200000); // 0.2 seconds delay
                
                // Try to delete from Eventbrite if sync disabled
                $delete_result = ccs_delete_eventbrite_discount($codes[$toggle_index]['eventbrite_discount_id']);
                if (!is_wp_error($delete_result) && is_array($delete_result) && $delete_result['success']) {
                    // Only remove the ID if successfully deleted (not just expired)
                    if ($delete_result['method'] === 'deleted') {
                        unset($codes[$toggle_index]['eventbrite_discount_id']);
                    }
                    // If expired, keep the ID but note it's been expired
                    if ($delete_result['method'] === 'expired') {
                        $codes[$toggle_index]['eventbrite_expired'] = true;
                    }
                }
            }
            
            ccs_save_discount_codes($codes);
            echo '<div class="notice notice-success is-dismissible"><p>Eventbrite sync status updated!</p></div>';
        }
    }
    
    // Handle course discount delete
    if (isset($_GET['delete_course_discount']) && check_admin_referer('ccs_delete_course_discount_' . $_GET['delete_course_discount'])) {
        $course_id = absint($_GET['delete_course_discount']);
        $course = get_post($course_id);
        
        if ($course && $course->post_type === 'course') {
            // Delete all discount meta
            delete_post_meta($course_id, '_course_discount_active');
            delete_post_meta($course_id, '_course_discount_percentage');
            delete_post_meta($course_id, '_course_discount_label');
            delete_post_meta($course_id, '_course_discount_requires_code');
            delete_post_meta($course_id, '_course_discount_code');
            delete_post_meta($course_id, '_course_discount_expiry');
            
            echo '<div class="notice notice-success is-dismissible"><p>Course discount deleted for "' . esc_html($course->post_title) . '"!</p></div>';
        }
    }
    
    // Handle course discount disable
    if (isset($_GET['disable_course_discount']) && check_admin_referer('ccs_disable_course_discount_' . $_GET['disable_course_discount'])) {
        $course_id = absint($_GET['disable_course_discount']);
        $course = get_post($course_id);
        
        if ($course && $course->post_type === 'course') {
            update_post_meta($course_id, '_course_discount_active', 0);
            echo '<div class="notice notice-success is-dismissible"><p>Course discount disabled for "' . esc_html($course->post_title) . '"!</p></div>';
        }
    }
    
    $codes = ccs_get_discount_codes();
    $site_wide_discount = ccs_get_site_wide_discount();
    $is_active = ccs_is_site_wide_discount_active();
    
    // Count courses with discounts
    $all_courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    $courses_with_discounts_count = 0;
    foreach ($all_courses as $course) {
        if (ccs_is_course_discount_active($course->ID)) {
            $courses_with_discounts_count++;
        }
    }
    
    // Count active vs expired codes
    $active_codes_count = 0;
    $expired_codes_count = 0;
    foreach ($codes as $code_data) {
        if (!empty($code_data['expiry_date'])) {
            $expiry = strtotime($code_data['expiry_date']);
            $now = current_time('timestamp');
            if ($expiry && $now > $expiry) {
                $expired_codes_count++;
                continue;
            }
        }
        if ($code_data['active'] ?? true) {
            $active_codes_count++;
        }
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Discount Codes & Site-Wide Discounts</h1>
        <a href="#add-code" class="page-title-action">Add New Code</a>
        <hr class="wp-header-end">
        
        <!-- Summary Dashboard -->
        <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 16px; margin-top: 20px; margin-bottom: 20px; border-radius: 4px;">
            <h3 style="margin-top: 0;">Discount Overview</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 30px;">
                <div>
                    <strong>Site-Wide Discount:</strong> 
                    <?php if ($is_active) : ?>
                        <span style="color: #00a32a;"><?php echo esc_html($site_wide_discount['percentage']); ?>% active</span>
                    <?php else : ?>
                        <span style="color: #8c8f94;">Inactive</span>
                    <?php endif; ?>
                </div>
                <div>
                    <strong>Course Discounts:</strong> 
                    <?php if ($courses_with_discounts_count === 0) : ?>
                        <span style="color: #8c8f94;">None active</span>
                    <?php else : ?>
                        <span style="color: #00a32a;"><?php echo esc_html($courses_with_discounts_count); ?> active</span>
                    <?php endif; ?>
                </div>
                <div>
                    <strong>Discount Codes:</strong> 
                    <span style="color: #2271b1;"><?php echo esc_html(count($codes)); ?> total</span>
                    (<?php echo $active_codes_count === 0 ? '<span style="color: #8c8f94;">none active</span>' : esc_html($active_codes_count) . ' active'; ?>, <?php echo esc_html($expired_codes_count); ?> expired)
                </div>
            </div>
        </div>
        
        <!-- Tabbed Interface -->
        <div class="nav-tab-wrapper" style="margin-top: 20px;">
            <a href="#site-wide-tab" class="nav-tab nav-tab-active" data-tab="site-wide">Site-Wide Discount</a>
            <a href="#course-specific-tab" class="nav-tab" data-tab="course-specific">Course-Specific Discounts</a>
            <a href="#discount-codes-tab" class="nav-tab" data-tab="discount-codes">Discount Codes</a>
        </div>
        
        <!-- Site-Wide Discount Tab -->
        <div id="site-wide-tab" class="tab-content" style="display: block;">
        <!-- Site-Wide Discount Section -->
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-top: 20px; border-left: 4px solid #2271b1;">
            <h2 style="margin-top: 0;">🎁 Site-Wide Discount</h2>
            <p style="color: #646970; margin-bottom: 20px;">Apply a discount to all courses and events across the entire site. This discount will be automatically applied to all prices displayed on the frontend.</p>
            
            <form method="post" id="site-wide-discount">
                <?php wp_nonce_field('ccs_site_wide_discount_action'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="site_wide_active">Enable Site-Wide Discount</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="site_wide_active" name="active" value="1" <?php checked($site_wide_discount['active']); ?>>
                                Active
                            </label>
                            <p class="description">When enabled, the discount will be automatically applied to all course and event prices.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="site_wide_percentage">Discount Percentage</label></th>
                        <td>
                            <input type="number" id="site_wide_percentage" name="percentage" value="<?php echo esc_attr($site_wide_discount['percentage']); ?>" min="0" max="100" step="0.1" class="small-text" required>
                            <span>%</span>
                            <p class="description">Enter the discount percentage (e.g., 20 for 20% off).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="site_wide_label">Discount Label</label></th>
                        <td>
                            <input type="text" id="site_wide_label" name="label" value="<?php echo esc_attr($site_wide_discount['label']); ?>" class="regular-text">
                            <p class="description">Label to display on the frontend (e.g., "Site-Wide Sale", "Summer Sale").</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="site_wide_expiry">Expiry Date (Optional)</label></th>
                        <td>
                            <input type="date" id="site_wide_expiry" name="expiry_date" value="<?php echo esc_attr($site_wide_discount['expiry_date']); ?>" class="regular-text">
                            <p class="description">The discount will automatically expire on this date. Leave empty for no expiry.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="ccs_save_site_wide_discount" class="button button-primary" value="Save Site-Wide Discount">
                </p>
            </form>
            
            <?php if ($is_active) : ?>
                <div style="background: #00a32a; color: #fff; padding: 12px; border-radius: 4px; margin-top: 20px;">
                    <strong>✓ Site-Wide Discount Active:</strong> <?php echo esc_html($site_wide_discount['percentage']); ?>% off all courses and events
                    <?php if (!empty($site_wide_discount['expiry_date'])) : ?>
                        (expires <?php echo esc_html(date('d M Y', strtotime($site_wide_discount['expiry_date']))); ?>)
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div style="background: #f0f0f1; color: #646970; padding: 12px; border-radius: 4px; margin-top: 20px;">
                    <strong>Site-Wide Discount:</strong> Currently inactive
                </div>
            <?php endif; ?>
        </div>
        </div>
        
        <!-- Course-Specific Discounts Tab -->
        <div id="course-specific-tab" class="tab-content" style="display: none;">
        <!-- Course-Specific Discounts Section -->
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-top: 20px; border-left: 4px solid #00a32a;">
            <h2 style="margin-top: 0;">🎯 Course-Specific Discounts</h2>
            <p style="color: #646970; margin-bottom: 20px;">Apply discounts to specific courses. You can set whether the discount is automatic or requires a discount code.</p>
            
            <form method="post" id="course-discount">
                <?php wp_nonce_field('ccs_course_discount_action'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="course_discount_courses">Select Courses <span style="color: #d63638;">*</span></label></th>
                        <td>
                            <?php
                            // Get all courses
                            $courses = get_posts([
                                'post_type' => 'course',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ]);
                            
                            // Get all categories
                            $categories = get_terms([
                                'taxonomy' => 'course_category',
                                'hide_empty' => false,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ]);
                            
                            // Group courses by category
                            $courses_by_category = [];
                            $uncategorized = [];
                            
                            foreach ($courses as $course) {
                                $course_cats = wp_get_post_terms($course->ID, 'course_category');
                                if (!empty($course_cats) && !is_wp_error($course_cats)) {
                                    foreach ($course_cats as $cat) {
                                        if (!isset($courses_by_category[$cat->term_id])) {
                                            $courses_by_category[$cat->term_id] = [
                                                'name' => $cat->name,
                                                'slug' => $cat->slug,
                                                'courses' => []
                                            ];
                                        }
                                        $courses_by_category[$cat->term_id]['courses'][] = $course;
                                    }
                                } else {
                                    $uncategorized[] = $course;
                                }
                            }
                            ?>
                            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #dcdcde; border-radius: 4px; padding: 12px; background: #fff;">
                                <?php foreach ($courses_by_category as $cat_id => $category_data) : ?>
                                    <div class="course-category-group" style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1;">
                                        <label style="display: block; padding: 8px; margin-bottom: 8px; background: #f0f6fc; border-radius: 4px; cursor: pointer; font-weight: 600;">
                                            <input type="checkbox" class="category-checkbox" data-category="<?php echo esc_attr($cat_id); ?>" style="margin-right: 8px;">
                                            <span style="color: #2271b1;">
                                                <?php echo esc_html($category_data['name']); ?>
                                                <span style="font-weight: normal; color: #646970; font-size: 12px;">(<?php echo count($category_data['courses']); ?> courses)</span>
                                            </span>
                                        </label>
                                        <div style="margin-left: 24px;">
                                            <?php foreach ($category_data['courses'] as $course) : 
                                                $course_discount = ccs_get_course_discount($course->ID);
                                                $is_active = ccs_is_course_discount_active($course->ID);
                                            ?>
                                                <label style="display: block; padding: 6px 8px; margin-bottom: 2px; border-radius: 4px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#f0f6fc';" onmouseout="this.style.background='transparent';">
                                                    <input type="checkbox" name="course_ids[]" value="<?php echo esc_attr($course->ID); ?>" class="course-discount-checkbox category-<?php echo esc_attr($cat_id); ?>" data-course-id="<?php echo esc_attr($course->ID); ?>" data-category="<?php echo esc_attr($cat_id); ?>" style="margin-right: 8px;">
                                                    <span style="font-size: 14px;">
                                                        <?php echo esc_html($course->post_title); ?>
                                                        <?php if ($is_active) : ?>
                                                            <span style="color: #00a32a; font-size: 11px;">(<?php echo esc_html($course_discount['percentage']); ?>% off)</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (!empty($uncategorized)) : ?>
                                    <div class="course-category-group" style="margin-bottom: 16px;">
                                        <label style="display: block; padding: 8px; margin-bottom: 8px; background: #f0f0f1; border-radius: 4px; cursor: pointer; font-weight: 600;">
                                            <input type="checkbox" class="category-checkbox" data-category="uncategorized" style="margin-right: 8px;">
                                            <span style="color: #646970;">
                                                Uncategorized
                                                <span style="font-weight: normal; font-size: 12px;">(<?php echo count($uncategorized); ?> courses)</span>
                                            </span>
                                        </label>
                                        <div style="margin-left: 24px;">
                                            <?php foreach ($uncategorized as $course) : 
                                                $course_discount = ccs_get_course_discount($course->ID);
                                                $is_active = ccs_is_course_discount_active($course->ID);
                                            ?>
                                                <label style="display: block; padding: 6px 8px; margin-bottom: 2px; border-radius: 4px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#f0f6fc';" onmouseout="this.style.background='transparent';">
                                                    <input type="checkbox" name="course_ids[]" value="<?php echo esc_attr($course->ID); ?>" class="course-discount-checkbox category-uncategorized" data-course-id="<?php echo esc_attr($course->ID); ?>" data-category="uncategorized" style="margin-right: 8px;">
                                                    <span style="font-size: 14px;">
                                                        <?php echo esc_html($course->post_title); ?>
                                                        <?php if ($is_active) : ?>
                                                            <span style="color: #00a32a; font-size: 11px;">(<?php echo esc_html($course_discount['percentage']); ?>% off)</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="description">Click a category name to select all courses in that category, or select individual courses.</p>
                            <div style="margin-top: 8px;">
                                <button type="button" id="select-all-courses" class="button button-small" style="margin-right: 8px;">Select All</button>
                                <button type="button" id="deselect-all-courses" class="button button-small">Deselect All</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="course_discount_active">Enable Discount</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="course_discount_active" name="course_discount_active" value="1">
                                Active
                            </label>
                            <p class="description">When enabled, the discount will be applied to this course.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="course_discount_percentage">Discount Percentage</label></th>
                        <td>
                            <input type="number" id="course_discount_percentage" name="course_discount_percentage" value="" min="0" max="100" step="0.1" class="small-text" required>
                            <span>%</span>
                            <p class="description">Enter the discount percentage (e.g., 15 for 15% off).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="course_discount_label">Discount Label</label></th>
                        <td>
                            <input type="text" id="course_discount_label" name="course_discount_label" value="" class="regular-text" placeholder="e.g., Early Bird, Limited Time">
                            <p class="description">Label to display on the course page (e.g., "Early Bird", "Limited Time Offer").</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="course_discount_requires_code">Requires Discount Code</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="course_discount_requires_code" name="course_discount_requires_code" value="1">
                                Yes, require a discount code
                            </label>
                            <p class="description">If checked, customers must enter a discount code to get this discount. If unchecked, the discount applies automatically.</p>
                        </td>
                    </tr>
                    <tr id="course_discount_code_row" style="display: none;">
                        <th scope="row"><label for="course_discount_code">Discount Code</label></th>
                        <td>
                            <input type="text" id="course_discount_code" name="course_discount_code" value="" class="regular-text" style="text-transform: uppercase;" placeholder="e.g., EARLYBIRD">
                            <p class="description">
                                The discount code customers must enter. This should match one of your discount codes.
                                <?php if (!empty($codes)) : ?>
                                    <br><strong>Available codes:</strong> 
                                    <?php 
                                    $code_list = [];
                                    foreach ($codes as $code_data) {
                                        if ($code_data['active'] ?? true) {
                                            $code_list[] = '<code>' . esc_html($code_data['code']) . '</code>';
                                        }
                                    }
                                    echo !empty($code_list) ? implode(', ', $code_list) : 'None active';
                                    ?>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="course_discount_expiry">Expiry Date (Optional)</label></th>
                        <td>
                            <input type="date" id="course_discount_expiry" name="course_discount_expiry" value="" class="regular-text">
                            <p class="description">The discount will automatically expire on this date. Leave empty for no expiry.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="ccs_save_course_discount" class="button button-primary" value="Save Course Discount" id="save-course-discount-btn">
                    <button type="button" id="course-discount-clear-form" class="button" style="margin-left: 10px;">Clear Form</button>
                </p>
            </form>
            
            <div id="course-discount-status" style="display: none; margin-bottom: 15px; padding: 10px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
                <strong>Selected courses:</strong> <span id="course-discount-course-name"></span>
                <button type="button" id="course-discount-clear" class="button button-small" style="margin-left: 10px;">Clear Selection</button>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Handle requires code checkbox
                $('#course_discount_requires_code').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#course_discount_code_row').show();
                        $('#course_discount_code').prop('required', true);
                    } else {
                        $('#course_discount_code_row').hide();
                        $('#course_discount_code').prop('required', false);
                    }
                });
                
                // Category checkbox - select/deselect all courses in category
                $('.category-checkbox').on('change', function() {
                    var category = $(this).data('category');
                    var isChecked = $(this).is(':checked');
                    $('.category-' + category).prop('checked', isChecked).trigger('change');
                });
                
                // Update category checkbox state when individual courses change
                $('.course-discount-checkbox').on('change', function() {
                    var category = $(this).data('category');
                    var totalInCategory = $('.category-' + category).length;
                    var checkedInCategory = $('.category-' + category + ':checked').length;
                    
                    var categoryCheckbox = $('.category-checkbox[data-category="' + category + '"]');
                    if (checkedInCategory === 0) {
                        categoryCheckbox.prop('checked', false).prop('indeterminate', false);
                    } else if (checkedInCategory === totalInCategory) {
                        categoryCheckbox.prop('checked', true).prop('indeterminate', false);
                    } else {
                        categoryCheckbox.prop('checked', false).prop('indeterminate', true);
                    }
                });
                
                // Select all courses
                $('#select-all-courses').on('click', function() {
                    $('.course-discount-checkbox').prop('checked', true).trigger('change');
                    $('.category-checkbox').prop('checked', true).prop('indeterminate', false);
                });
                
                // Deselect all courses
                $('#deselect-all-courses').on('click', function() {
                    $('.course-discount-checkbox').prop('checked', false).trigger('change');
                    $('.category-checkbox').prop('checked', false).prop('indeterminate', false);
                });
                
                // Update status when courses are selected
                function updateSelectedCoursesStatus() {
                    var selectedCount = $('.course-discount-checkbox:checked').length;
                    var $statusDiv = $('#course-discount-status');
                    var $courseName = $('#course-discount-course-name');
                    
                    if (selectedCount > 0) {
                        var selectedNames = [];
                        $('.course-discount-checkbox:checked').each(function() {
                            var courseName = $(this).closest('label').find('span').first().text().trim();
                            // Remove the "(Currently X% off)" part if present
                            courseName = courseName.replace(/\s*\(Currently\s+\d+%\s+off\)\s*$/, '');
                            selectedNames.push(courseName);
                        });
                        
                        if (selectedCount <= 3) {
                            $courseName.text(selectedNames.join(', '));
                        } else {
                            $courseName.text(selectedNames.slice(0, 3).join(', ') + ' and ' + (selectedCount - 3) + ' more');
                        }
                        $statusDiv.show();
                    } else {
                        $statusDiv.hide();
                    }
                }
                
                $('.course-discount-checkbox').on('change', updateSelectedCoursesStatus);
                
                // Form validation
                $('#course-discount').on('submit', function(e) {
                    var selectedCount = $('.course-discount-checkbox:checked').length;
                    if (selectedCount === 0) {
                        e.preventDefault();
                        alert('Please select at least one course to apply the discount to.');
                        return false;
                    }
                });
                
                // Clear form buttons
                $('#course-discount-clear, #course-discount-clear-form').on('click', function() {
                    $('.course-discount-checkbox').prop('checked', false);
                    clearForm();
                    $('#course-discount-status').hide();
                });
                
                // Handle URL parameter for course_id (when coming from course list)
                var urlParams = new URLSearchParams(window.location.search);
                var courseIdParam = urlParams.get('course_id');
                if (courseIdParam) {
                    $('.course-discount-checkbox[value="' + courseIdParam + '"]').prop('checked', true).trigger('change');
                    // Switch to course-specific tab
                    $('.nav-tab[data-tab="course-specific"]').trigger('click');
                }
                
                // Clear form function
                function clearForm() {
                    $('#course_discount_active').prop('checked', false);
                    $('#course_discount_percentage').val('');
                    $('#course_discount_label').val('');
                    $('#course_discount_requires_code').prop('checked', false);
                    $('#course_discount_code').val('');
                    $('#course_discount_expiry').val('');
                    $('#course_discount_code_row').hide();
                    $('#course_discount_code').prop('required', false);
                }
            });
            </script>
        </div>
        
        <!-- Courses with Discounts Overview Table -->
        <?php
        $all_courses = get_posts([
            'post_type' => 'course',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $courses_with_discounts = [];
        foreach ($all_courses as $course) {
            if (ccs_is_course_discount_active($course->ID)) {
                $discount = ccs_get_course_discount($course->ID);
                $courses_with_discounts[] = [
                    'course' => $course,
                    'discount' => $discount
                ];
            }
        }
        ?>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-top: 20px;">
            <h2 style="margin-top: 0;">Courses with Active Discounts</h2>
            <?php if (empty($courses_with_discounts)) : ?>
                <p style="color: #646970;">No courses currently have active discounts.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 200px;">Course Name</th>
                            <th style="width: 100px;">Discount %</th>
                            <th style="width: 150px;">Label</th>
                            <th style="width: 120px;">Type</th>
                            <th style="width: 120px;">Code</th>
                            <th style="width: 120px;">Expiry Date</th>
                            <th style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses_with_discounts as $item) : 
                            $course = $item['course'];
                            $discount = $item['discount'];
                            $is_expired = false;
                            if (!empty($discount['expiry_date'])) {
                                $expiry = strtotime($discount['expiry_date']);
                                $now = current_time('timestamp');
                                $is_expired = $expiry && $now > $expiry;
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($course->post_title); ?></strong></td>
                            <td><span style="color: #00a32a; font-weight: 600;"><?php echo esc_html($discount['percentage']); ?>%</span></td>
                            <td><?php echo esc_html($discount['label'] ?: '-'); ?></td>
                            <td>
                                <?php if ($discount['requires_code']) : ?>
                                    <span style="color: #856404;">Code Required</span>
                                <?php else : ?>
                                    <span style="color: #00a32a;">Automatic</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($discount['requires_code'] && !empty($discount['discount_code'])) : ?>
                                    <code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($discount['discount_code']); ?></code>
                                <?php else : ?>
                                    <span style="color: #8c8f94;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($discount['expiry_date'])) : ?>
                                    <?php if ($is_expired) : ?>
                                        <span style="color: #d63638;">Expired</span><br>
                                        <small style="color: #8c8f94;"><?php echo esc_html(date('d M Y', strtotime($discount['expiry_date']))); ?></small>
                                    <?php else : ?>
                                        <?php echo esc_html(date('d M Y', strtotime($discount['expiry_date']))); ?>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span style="color: #8c8f94;">No expiry</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small course-discount-edit" data-course-id="<?php echo esc_attr($course->ID); ?>" style="margin-right: 5px;">
                                    Edit
                                </button>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['disable_course_discount' => $course->ID], admin_url('edit.php?post_type=course&page=cta-discount-codes')), 'ccs_disable_course_discount_' . $course->ID)); ?>" class="button button-small">
                                    Disable
                                </a>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['delete_course_discount' => $course->ID], admin_url('edit.php?post_type=course&page=cta-discount-codes')), 'ccs_delete_course_discount_' . $course->ID)); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete the discount for <?php echo esc_js($course->post_title); ?>?');" style="color: #b32d2e; margin-left: 5px;">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <script>
                jQuery(document).ready(function($) {
                    $('.course-discount-edit').on('click', function() {
                        var courseId = $(this).data('course-id');
                        // Uncheck all first
                        $('.course-discount-checkbox').prop('checked', false);
                        // Check the selected course
                        $('.course-discount-checkbox[value="' + courseId + '"]').prop('checked', true).trigger('change');
                        // Scroll to form
                        $('html, body').animate({
                            scrollTop: $('#course-discount').offset().top - 100
                        }, 500);
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        
        <!-- All course discounts -->
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-top: 20px;">
            <h2 style="margin-top: 0;">All course discounts</h2>
            <p style="color: #646970; margin-bottom: 16px;">Every course and its discount status. Use this view to see inactive, expired, or courses with no discount.</p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 200px;">Course Name</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 100px;">Discount %</th>
                        <th style="width: 150px;">Label</th>
                        <th style="width: 120px;">Type</th>
                        <th style="width: 120px;">Code</th>
                        <th style="width: 120px;">Expiry Date</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $now_ts = current_time('timestamp');
                    foreach ($all_courses as $course) :
                        $discount = ccs_get_course_discount($course->ID);
                        $is_active = ccs_is_course_discount_active($course->ID);
                        $is_expired = false;
                        if (!empty($discount['expiry_date'])) {
                            $expiry = strtotime($discount['expiry_date']);
                            $is_expired = $expiry && $now_ts > $expiry;
                        }
                        $has_discount_config = ($discount['active'] && $discount['percentage'] > 0) || !empty($discount['expiry_date']) || !empty($discount['discount_code']) || !empty($discount['label']);
                        if ($is_active) {
                            $status = 'Active';
                            $status_style = 'color: #00a32a; font-weight: 600;';
                        } elseif ($is_expired) {
                            $status = 'Expired';
                            $status_style = 'color: #d63638;';
                        } elseif ($discount['active'] && $discount['percentage'] > 0) {
                            $status = 'Inactive';
                            $status_style = 'color: #856404;';
                        } else {
                            $status = 'None';
                            $status_style = 'color: #8c8f94;';
                        }
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($course->post_title); ?></strong></td>
                        <td><span style="<?php echo esc_attr($status_style); ?>"><?php echo esc_html($status); ?></span></td>
                        <td><?php echo $has_discount_config && $discount['percentage'] > 0 ? esc_html($discount['percentage']) . '%' : '<span style="color: #8c8f94;">-</span>'; ?></td>
                        <td><?php echo esc_html($discount['label'] ?: '-'); ?></td>
                        <td>
                            <?php if ($has_discount_config && $discount['requires_code']) : ?>
                                <span style="color: #856404;">Code Required</span>
                            <?php elseif ($has_discount_config) : ?>
                                <span style="color: #00a32a;">Automatic</span>
                            <?php else : ?>
                                <span style="color: #8c8f94;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($discount['requires_code'] && !empty($discount['discount_code'])) : ?>
                                <code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($discount['discount_code']); ?></code>
                            <?php else : ?>
                                <span style="color: #8c8f94;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($discount['expiry_date'])) : ?>
                                <?php if ($is_expired) : ?>
                                    <span style="color: #d63638;">Expired</span><br>
                                    <small style="color: #8c8f94;"><?php echo esc_html(date('d M Y', strtotime($discount['expiry_date']))); ?></small>
                                <?php else : ?>
                                    <?php echo esc_html(date('d M Y', strtotime($discount['expiry_date']))); ?>
                                <?php endif; ?>
                            <?php else : ?>
                                <span style="color: #8c8f94;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($has_discount_config) : ?>
                                <button type="button" class="button button-small course-discount-edit" data-course-id="<?php echo esc_attr($course->ID); ?>" style="margin-right: 5px;">
                                    Edit
                                </button>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['disable_course_discount' => $course->ID], admin_url('edit.php?post_type=course&page=cta-discount-codes')), 'ccs_disable_course_discount_' . $course->ID)); ?>" class="button button-small">
                                    Disable
                                </a>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['delete_course_discount' => $course->ID], admin_url('edit.php?post_type=course&page=cta-discount-codes')), 'ccs_delete_course_discount_' . $course->ID)); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete the discount for <?php echo esc_js($course->post_title); ?>?');" style="color: #b32d2e; margin-left: 5px;">
                                    Delete
                                </a>
                            <?php else : ?>
                                <span style="color: #8c8f94;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
        
        <!-- Discount Codes Tab -->
        <div id="discount-codes-tab" class="tab-content" style="display: none;">
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-top: 20px;">
            <h2>Add New Discount Code</h2>
            <form method="post" id="add-code">
                <?php wp_nonce_field('ccs_discount_codes_action'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="code">Discount Code</label></th>
                        <td>
                            <input type="text" id="code" name="code" class="regular-text" required style="text-transform: uppercase;">
                            <p class="description">Enter the discount code (will be converted to uppercase). All valid codes apply 20% discount.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="expiry_date">Expiry Date (Optional)</label></th>
                        <td>
                            <input type="date" id="expiry_date" name="expiry_date" class="regular-text">
                            <p class="description">Leave empty for no expiry date.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="active">Status</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="active" name="active" value="1" checked>
                                Active
                            </label>
                            <p class="description">Inactive codes will be rejected as "Invalid or Expired".</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sync_to_eventbrite">Sync to Eventbrite</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="sync_to_eventbrite" name="sync_to_eventbrite" value="1">
                                Sync this code to Eventbrite
                            </label>
                            <p class="description">When enabled, this discount code will be created on Eventbrite and can be used during checkout. Requires Eventbrite API credentials to be configured.</p>
                            <?php
                            $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
                            $org_id = get_option('ccs_eventbrite_organization_id', '');
                            if (empty($oauth_token) || empty($org_id)) :
                            ?>
                                <p class="description" style="color: #d63638;">
                                    <strong>⚠️ Eventbrite credentials not configured.</strong> Configure them in <a href="<?php echo admin_url('admin.php?page=cta-api-keys'); ?>">API Keys settings</a> to enable syncing.
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="ccs_add_discount_code" class="button button-primary" value="Add Discount Code">
                </p>
            </form>
        </div>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-top: 20px;">
            <h2>Existing Discount Codes</h2>
            <?php if (empty($codes)) : ?>
                <p>No discount codes have been created yet.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Code</th>
                            <th>Status</th>
                            <th>Eventbrite Sync</th>
                            <th>Expiry Date</th>
                            <th>Created</th>
                            <th style="width: 250px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($codes as $index => $code_data) : 
                            $is_expired = false;
                            if (!empty($code_data['expiry_date'])) {
                                $expiry = strtotime($code_data['expiry_date']);
                                $now = current_time('timestamp');
                                $is_expired = $expiry && $now > $expiry;
                            }
                            $is_active = $code_data['active'] ?? true;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($code_data['code']); ?></strong></td>
                            <td>
                                <?php if ($is_expired) : ?>
                                    <span style="color: #d63638;">Expired</span>
                                <?php elseif (!$is_active) : ?>
                                    <span style="color: #d63638;">Inactive</span>
                                <?php else : ?>
                                    <span style="color: #00a32a;">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $sync_enabled = $code_data['sync_to_eventbrite'] ?? false;
                                $eventbrite_id = $code_data['eventbrite_discount_id'] ?? '';
                                $eventbrite_expired = $code_data['eventbrite_expired'] ?? false;
                                
                                if ($sync_enabled) : 
                                    if ($eventbrite_id) :
                                        if ($eventbrite_expired || !$is_active) :
                                ?>
                                    <span style="color: #d63638;">⚠ Expired on EB</span>
                                    <br><small style="color: #646970;">ID: <?php echo esc_html(substr($eventbrite_id, 0, 12)); ?>...</small>
                                <?php else : ?>
                                    <span style="color: #00a32a;">✓ Synced</span>
                                    <br><small style="color: #646970;">ID: <?php echo esc_html(substr($eventbrite_id, 0, 12)); ?>...</small>
                                <?php 
                                        endif;
                                    else : 
                                ?>
                                    <span style="color: #d63638;">⚠ Sync Failed</span>
                                <?php 
                                    endif;
                                else : 
                                ?>
                                    <span style="color: #8c8f94;">Not synced</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $code_data['expiry_date'] ? esc_html(date('d M Y', strtotime($code_data['expiry_date']))) : 'No expiry'; ?>
                            </td>
                            <td>
                                <?php echo $code_data['created'] ? esc_html(date('d M Y', strtotime($code_data['created']))) : '-'; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['toggle' => $index], admin_url('edit.php?post_type=course&page=cta-discount-codes')), 'ccs_toggle_discount_code_' . $index)); ?>" class="button button-small">
                                    <?php echo $is_active ? 'Deactivate' : 'Activate'; ?>
                                </a>
                                <?php
                                $oauth_token = get_option('ccs_eventbrite_oauth_token', '');
                                $org_id = get_option('ccs_eventbrite_organization_id', '');
                                if (!empty($oauth_token) && !empty($org_id)) :
                                ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['toggle_eventbrite' => $index], admin_url('edit.php?post_type=course&page=cta-discount-codes')), 'ccs_toggle_eventbrite_sync_' . $index)); ?>" class="button button-small" style="margin-left: 5px;">
                                        <?php echo $sync_enabled ? 'Unsync' : 'Sync to EB'; ?>
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['delete' => $index], admin_url('edit.php?post_type=course&page=cta-discount-codes')), 'ccs_delete_discount_code_' . $index)); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete this discount code?');" style="color: #b32d2e; margin-left: 5px;">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var targetTab = $(this).data('tab');
                
                // Update active tab
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Show/hide tab content
                $('.tab-content').hide();
                $('#' + targetTab + '-tab').show();
                
                // Update URL hash without reload
                if (history.pushState) {
                    history.pushState(null, null, '#' + targetTab + '-tab');
                }
            });
            
            // Handle hash on page load
            if (window.location.hash) {
                var hash = window.location.hash.replace('#', '');
                var $tab = $('.nav-tab[data-tab="' + hash.replace('-tab', '') + '"]');
                if ($tab.length) {
                    $tab.trigger('click');
                }
            }
        });
        </script>
    </div>
    <?php
}
