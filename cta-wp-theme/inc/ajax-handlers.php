<?php
/**
 * AJAX Form Handlers
 *
 * Handles all form submissions via WordPress AJAX.
 * Security: Nonce verification, honeypot, input sanitization.
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Enquiries email address - all form submissions go here
 */
define('CTA_ENQUIRIES_EMAIL', 'enquiries@continuitytrainingacademy.co.uk');

/**
 * =========================================
 * CONTACT FORM HANDLER (Refactored)
 * =========================================
 * 
 * NOTE: This function now delegates to ContactFormController class
 * for cleaner, testable code while maintaining backward compatibility.
 * The AJAX action names remain unchanged, so frontend JS continues to work.
 */
function cta_handle_contact_form() {
    // Use new controller if autoloader is available
    if (class_exists('\\CTA\\Controllers\\ContactFormController')) {
        $validator = new \CTA\Services\FormValidator();
        $controller = new \CTA\Controllers\ContactFormController($validator);
        $controller->handle();
        return;
    }
    
    // Fallback to legacy code if composer autoloader not available
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cta_nonce')) {
            wp_send_json_error([
                'message' => 'Security verification failed. Please refresh the page and try again.',
                'code' => 'nonce_failed'
            ], 403);
        }

        // Comprehensive anti-bot validation
        $bot_check = cta_validate_anti_bot('contact');
        if ($bot_check === false) {
            // Bot detected - silently accept but don't process
            wp_send_json_success([
                'message' => 'Thank you for your message. We will be in touch soon.',
            ]);
        }

        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $enquiry_type = sanitize_text_field($_POST['enquiryType'] ?? 'general');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $discount_code = sanitize_text_field($_POST['discount_code'] ?? '');
        $consent = isset($_POST['consent']) && $_POST['consent'] === 'true';
        $marketing_consent = isset($_POST['marketingConsent']) && $_POST['marketingConsent'] === 'true';

        $errors = [];
        
        // Validate discount code if provided (only for course-related enquiries)
        $discount_validation = ['valid' => false, 'message' => '', 'discount' => 0];
        $is_course_enquiry = ($enquiry_type === 'book-course' || $enquiry_type === 'group-training');
        if ($is_course_enquiry && !empty($discount_code)) {
            if (function_exists('cta_validate_discount_code')) {
                $discount_validation = cta_validate_discount_code($discount_code);
                if (!$discount_validation['valid']) {
                    $errors['discount_code'] = $discount_validation['message'] ?: 'This discount code is not valid.';
                }
            } else {
                $errors['discount_code'] = 'Unable to validate discount code. Please try again.';
            }
        }

        // Validate name
        $name_validation = cta_validate_name($name);
        if (!$name_validation['valid']) {
            $errors['name'] = $name_validation['error'];
        }

        // Validate email
        $email_validation = cta_validate_email($email, true);
        if (!$email_validation['valid']) {
            $errors['email'] = $email_validation['error'];
        }

        // Validate phone
        $phone_validation = cta_validate_uk_phone($phone);
        if (!$phone_validation['valid']) {
            $errors['phone'] = $phone_validation['error'];
        }

        // Validate message
        if (empty($message)) {
            $errors['message'] = 'Please tell us about your enquiry';
        } elseif (strlen($message) > 1000) {
            $errors['message'] = 'Your message is too long. Please keep it under 1000 characters.';
        } elseif (strlen(trim($message)) < 10) {
            $errors['message'] = 'Please provide more details about your enquiry (at least 10 characters)';
        }

        if (!$consent) {
            $errors['consent'] = 'You must agree to be contacted to submit this form';
        }

        if (!empty($errors)) {
            wp_send_json_error([
                'message' => 'Please correct the errors below.',
                'errors' => $errors,
                'code' => 'validation_failed'
            ], 400);
        }

        $enquiry_type_labels = [
            'training-consultation' => 'Book a Free Training Consultation',
            'group-training' => 'Group Training',
            'book-course' => 'Book a Course',
            'cqc-training' => 'CQC Training Enquiry',
            'support' => 'Support/FAQ',
            'general' => 'General Enquiry',
        ];
        $enquiry_label = $enquiry_type_labels[$enquiry_type] ?? ucfirst($enquiry_type);

        // Check if email constant is defined
        if (!defined('CTA_ENQUIRIES_EMAIL') || empty(CTA_ENQUIRIES_EMAIL)) {
            error_log('CTA Contact Form: CTA_ENQUIRIES_EMAIL constant not defined');
            wp_send_json_error([
                'message' => 'Email configuration error. Please contact us directly.',
                'code' => 'email_config_error'
            ], 500);
            return;
        }

        // Save submission to database first (so we can include admin link in email)
        $submission_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'consent' => $consent ? 'yes' : 'no',
            'marketing_consent' => $marketing_consent ? 'yes' : 'no',
            'enquiry_type' => $enquiry_type,
            'ip' => cta_get_client_ip(),
            'page_url' => $_POST['page_url'] ?? home_url('/contact/'),
        ];
        
        // Add discount code if provided (for course-related enquiries)
        if ($is_course_enquiry && !empty($discount_code)) {
            $submission_data['discount_code'] = $discount_code;
            $submission_data['discount_valid'] = $discount_validation['valid'] ? 'yes' : 'no';
            $submission_data['discount_message'] = $discount_validation['message'];
            $submission_data['discount_percent'] = $discount_validation['discount'];
        }
        
        // Add selected courses to submission data
        $selected_courses = [];
        if (isset($_POST['selectedCourses']) && is_array($_POST['selectedCourses'])) {
            $selected_courses = array_map('absint', $_POST['selectedCourses']);
            $selected_courses = array_filter($selected_courses); // Remove empty values
            if (!empty($selected_courses)) {
                $submission_data['selected_courses'] = $selected_courses;
            }
        }
        
        // Save submission (temporarily mark email as not sent, will update after)
        $saved = cta_save_form_submission($submission_data, $enquiry_type, false, '');
        
        // Build email with admin link
        $to = CTA_ENQUIRIES_EMAIL;
        $subject = sprintf('[CTA Contact] %s from %s', $enquiry_label, $name);
        
        $body = "New contact form submission:\n\n";
        $body .= "Name: {$name}\n";
        $body .= "Email: {$email}\n";
        $body .= "Phone: {$phone}\n";
        $body .= "Enquiry Type: {$enquiry_label}\n";
        
        // Add selected courses to email body with dates if they're upcoming course events
        if (!empty($selected_courses)) {
            $course_titles = [];
            $course_dates = [];
            foreach ($selected_courses as $course_id) {
                $course_title = get_the_title($course_id);
                if ($course_title && $course_title !== 'Auto Draft') {
                    $course_titles[] = $course_title;
                    
                    // Check if this is a course_event and get its date
                    $post_type = get_post_type($course_id);
                    if ($post_type === 'course_event') {
                        $course_event_date = function_exists('get_field') ? get_field('event_date', $course_id) : null;
                        if (!$course_event_date) {
                            $course_event_date = get_post_meta($course_id, 'event_date', true);
                        }
                        
                        // Only include if it's an upcoming course (date >= today)
                        if ($course_event_date) {
                            $event_date_obj = is_string($course_event_date) ? strtotime($course_event_date) : $course_event_date;
                            $today = strtotime(date('Y-m-d'));
                            if ($event_date_obj && $event_date_obj >= $today) {
                                $formatted_date = is_string($course_event_date) ? date('j F Y', strtotime($course_event_date)) : date('j F Y', $event_date_obj);
                                $course_dates[] = $course_title . ' - ' . $formatted_date;
                            } else {
                                $course_dates[] = $course_title;
                            }
                        } else {
                            $course_dates[] = $course_title;
                        }
                    } else {
                        $course_dates[] = $course_title;
                    }
                }
            }
            if (!empty($course_titles)) {
                $body .= "Selected Course(s): " . implode(', ', $course_dates) . "\n";
            }
        }
        
        // Add discount code info if provided
        if ($is_course_enquiry && !empty($discount_code)) {
            $body .= "\nDiscount Code: {$discount_code}\n";
            $body .= "Discount Status: {$discount_validation['message']}\n";
            if ($discount_validation['valid']) {
                $body .= "Discount Applied: {$discount_validation['discount']}% Off\n";
            }
        }
        
        $body .= "\nMessage:\n{$message}\n\n";
        $body .= "---\n";
        $body .= "Submitted: " . current_time('mysql') . "\n";
        $body .= "IP: " . (function_exists('cta_get_client_ip') ? cta_get_client_ip() : '') . "\n";
        $body .= "Page: " . esc_url($_POST['page_url'] ?? home_url('/contact/')) . "\n\n";
        
        // Add admin link to view submission
        if (!is_wp_error($saved) && $saved) {
            $admin_link = admin_url('post.php?post=' . $saved . '&action=edit');
            $body .= "View in WordPress Admin:\n";
            $body .= esc_url($admin_link) . "\n\n";
        }

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $name . ' <' . $email . '>',
        ];

        $sent = wp_mail($to, $subject, $body, $headers);
        $email_error = '';

        if (!$sent) {
            $email_error = 'Email sending failed';
            $last_error = error_get_last();
            error_log('CTA Contact Form: Failed to send email');
            if (is_array($last_error) && isset($last_error['type']) && $last_error['type'] === E_ERROR) {
                error_log('CTA Contact Form: PHP Error - ' . (isset($last_error['message']) ? $last_error['message'] : 'Unknown error'));
            }
            
            // Check if wp_mail function exists
            if (!function_exists('wp_mail')) {
                error_log('CTA Contact Form: wp_mail function does not exist');
            }
        }
        
        // Update submission with email status
        if (!is_wp_error($saved) && $saved) {
            update_post_meta($saved, '_submission_email_sent', $sent ? 'yes' : 'no');
            if ($email_error) {
                update_post_meta($saved, '_submission_email_error', $email_error);
            }
        }
        
        // If marketing consent given, add to newsletter (only if not already subscribed)
        if ($marketing_consent && !empty($email)) {
            $name_parts = explode(' ', trim($name), 2);
            $first_name = $name_parts[0] ?? '';
            $last_name = $name_parts[1] ?? '';
            cta_add_newsletter_subscriber($email, cta_get_client_ip(), $first_name, $last_name);
        }

        // If submission was saved successfully, show success even if email failed
        if (!is_wp_error($saved)) {
            // Log successful saves (ID only for privacy)
            error_log('CTA Contact Form: Submission saved (ID: ' . $saved . ') - Type: ' . $enquiry_type);
            
        if (defined('WP_DEBUG') && WP_DEBUG) {
                if ($sent) {
                    error_log('CTA Contact Form: Email successfully sent');
                } else {
                    error_log('CTA Contact Form: Email failed but submission saved (ID: ' . $saved . ')');
                }
            }
        } else {
            // Log save failures (error message only, no personal data)
            error_log('CTA Contact Form: FAILED to save submission - ' . $saved->get_error_message());
        }

        $response_messages = [
            'training-consultation' => 'Thank you! We will be in touch to arrange your free training consultation.',
            'group-training' => 'Thank you! Our group training team will be in touch.',
            'book-course' => 'Thank you! We will confirm your booking enquiry.',
            'cqc-training' => 'Thank you! Our CQC training specialist will contact you.',
            'support' => 'Thank you! Our support team will respond.',
            'general' => 'Thank you for your message. We will be in touch.',
        ];

        wp_send_json_success([
            'message' => $response_messages[$enquiry_type] ?? $response_messages['general'],
            'enquiry_type' => $enquiry_type,
        ]);
    } catch (Exception $e) {
        error_log('CTA Contact Form Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'An error occurred while processing your request. Please try again or contact us directly.',
            'code' => 'server_error'
        ], 500);
    }
}
add_action('wp_ajax_cta_contact_form', 'cta_handle_contact_form');
add_action('wp_ajax_nopriv_cta_contact_form', 'cta_handle_contact_form');


/**
 * =========================================
 * NEWSLETTER SIGNUP HANDLER
 * =========================================
 */
function cta_handle_newsletter_signup() {
    // Use controller if available, otherwise fall back to legacy implementation
    if (class_exists('\\CTA\\Controllers\\NewsletterSignupController')) {
        $controller = new \CTA\Controllers\NewsletterSignupController();
        $controller->handle();
        return;
    }
    
    // Legacy fallback implementation (keep for backward compatibility)
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cta_nonce')) {
        wp_send_json_error([
            'message' => 'Security verification failed. Please refresh the page and try again.',
            'code' => 'nonce_failed'
        ], 403);
    }

    // Comprehensive anti-bot validation
    $bot_check = cta_validate_anti_bot('newsletter');
    if ($bot_check === false) {
        // Bot detected - silently accept but don't process
        wp_send_json_success([
            'message' => 'Thank you for subscribing!',
        ]);
    }

    $email = sanitize_email($_POST['email'] ?? '');
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $date_of_birth = sanitize_text_field($_POST['date_of_birth'] ?? '');
    $consent = isset($_POST['consent']) && $_POST['consent'] === 'true';

    if (empty($email)) {
        wp_send_json_error([
            'message' => 'Email address is required.',
            'code' => 'validation_failed'
        ], 400);
    }

    if (!is_email($email)) {
        wp_send_json_error([
            'message' => 'Please enter a valid email address.',
            'code' => 'validation_failed'
        ], 400);
    }

    if (!$consent) {
        wp_send_json_error([
            'message' => 'Please confirm your consent to receiving updates.',
            'code' => 'validation_failed'
        ], 400);
    }

    // Optional phone validation (only if provided)
    if (!empty($phone)) {
        $phone_validation = function_exists('cta_validate_uk_phone') ? cta_validate_uk_phone($phone) : ['valid' => true];
        if (isset($phone_validation['valid']) && !$phone_validation['valid']) {
            wp_send_json_error([
                'message' => $phone_validation['error'] ?? 'Please enter a valid phone number.',
                'code' => 'validation_failed',
                'errors' => ['phone' => $phone_validation['error'] ?? 'Please enter a valid phone number.'],
            ], 400);
        }
    }
    
    // Validate date of birth format if provided
    if (!empty($date_of_birth)) {
        $dob_timestamp = strtotime($date_of_birth);
        if ($dob_timestamp === false || $dob_timestamp > time()) {
            wp_send_json_error([
                'message' => 'Please enter a valid date of birth.',
                'code' => 'validation_failed'
            ], 400);
        }
    }

    $result = cta_add_newsletter_subscriber($email, cta_get_client_ip(), $first_name, $last_name, $date_of_birth);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('CTA Newsletter: ' . $result);
    }
    
    // Create form submission post for admin tracking
    $full_name = trim(($first_name ?? '') . ' ' . ($last_name ?? ''));
    $submission_data = [
        'name' => $full_name ?: 'Newsletter Subscriber',
        'email' => $email,
        'phone' => $phone,
        'message' => '',
        'consent' => $consent ? 'yes' : 'no',
        'marketing_consent' => $consent ? 'yes' : 'no', // Newsletter consent IS marketing consent
        'ip' => cta_get_client_ip(),
        'page_url' => $_POST['page_url'] ?? home_url('/'),
        'first_name' => $first_name,
        'last_name' => $last_name,
        'date_of_birth' => $date_of_birth,
    ];
    
    $submission_id = cta_save_form_submission($submission_data, 'newsletter', false, '');
    
    // Link submission to subscriber record
    if (!is_wp_error($submission_id) && $submission_id) {
        // Get subscriber ID from database
        global $wpdb;
        $subscriber_table = $wpdb->prefix . 'cta_newsletter_subscribers';
        $subscriber = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status, subscribed_at FROM $subscriber_table WHERE email = %s",
            $email
        ));
        
        if ($subscriber) {
            update_post_meta($submission_id, '_submission_newsletter_subscriber_id', $subscriber->id);
            update_post_meta($submission_id, '_submission_newsletter_subscriber_status', $subscriber->status);
            update_post_meta($submission_id, '_submission_newsletter_status', $result);
            
            // Store additional newsletter-specific data
            if (!empty($first_name)) {
                update_post_meta($submission_id, '_submission_first_name', $first_name);
            }
            if (!empty($last_name)) {
                update_post_meta($submission_id, '_submission_last_name', $last_name);
            }
            if (!empty($date_of_birth)) {
                update_post_meta($submission_id, '_submission_date_of_birth', $date_of_birth);
            }
            if (!empty($phone)) {
                update_post_meta($submission_id, '_submission_phone', $phone);
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CTA Newsletter: Submission created (ID: ' . $submission_id . ')');
        }
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CTA Newsletter: Failed to create submission');
        }
    }
    
    $messages = [
        'added' => 'Thank you for subscribing! We\'ll keep you updated with the latest training insights and CQC updates.',
        'reactivated' => 'Welcome back! Your subscription has been reactivated.',
        'exists' => 'You\'re already signed up to our mailing list! You\'ll continue to receive our updates.',
    ];

    wp_send_json_success([
        'message' => $messages[$result] ?? $messages['added'],
        'status' => $result,
    ]);
}
add_action('wp_ajax_cta_newsletter_signup', 'cta_handle_newsletter_signup');
add_action('wp_ajax_nopriv_cta_newsletter_signup', 'cta_handle_newsletter_signup');


/**
 * =========================================
 * GROUP BOOKING FORM HANDLER
 * =========================================
 */
function cta_handle_group_booking() {
    // Use controller if available, otherwise fall back to legacy implementation
    if (class_exists('\\CTA\\Controllers\\GroupBookingController')) {
        $controller = new \CTA\Controllers\GroupBookingController();
        $controller->handle();
        return;
    }
    
    // Legacy fallback implementation (keep for backward compatibility)
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cta_nonce')) {
            wp_send_json_error([
                'message' => 'Security verification failed. Please refresh the page and try again.',
                'code' => 'nonce_failed'
            ], 403);
        }

        // Comprehensive anti-bot validation
        $bot_check = cta_validate_anti_bot('group-booking');
        if ($bot_check === false) {
            // Bot detected - silently accept but don't process
            wp_send_json_success([
                'message' => 'Thank you for your enquiry. We will be in touch soon.',
            ]);
        }

    // Sanitize inputs
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $organisation = sanitize_text_field($_POST['organisation'] ?? '');
    $number_of_staff = absint($_POST['numberOfStaff'] ?? 0);
    $training_type = sanitize_text_field($_POST['trainingType'] ?? '');
    $details = sanitize_textarea_field($_POST['details'] ?? '');
    $discount_code = sanitize_text_field($_POST['discount_code'] ?? '');
    $consent = isset($_POST['consent']) && $_POST['consent'] === 'true';
    $marketing_consent = isset($_POST['marketingConsent']) && $_POST['marketingConsent'] === 'true';

    $errors = [];

    // Validate discount code if provided
    $discount_validation = ['valid' => false, 'message' => '', 'discount' => 0];
    if (!empty($discount_code)) {
        if (function_exists('cta_validate_discount_code')) {
            $discount_validation = cta_validate_discount_code($discount_code);
            if (!$discount_validation['valid']) {
                $errors['discount_code'] = $discount_validation['message'] ?: 'This discount code is not valid.';
            }
        } else {
            $errors['discount_code'] = 'Unable to validate discount code. Please try again.';
        }
    }

    // Validate name
    $name_validation = cta_validate_name($name);
    if (!$name_validation['valid']) {
        $errors['name'] = $name_validation['error'];
    }

    // Validate email
    $email_validation = cta_validate_email($email, true);
    if (!$email_validation['valid']) {
        $errors['email'] = $email_validation['error'];
    }

    // Validate phone
    $phone_validation = cta_validate_uk_phone($phone);
    if (!$phone_validation['valid']) {
        $errors['phone'] = $phone_validation['error'];
    }

    // Validate organisation
    if (empty($organisation)) {
        $errors['organisation'] = 'Organisation name is required';
    } elseif (strlen(trim($organisation)) < 2) {
        $errors['organisation'] = 'Please enter a valid organisation name';
    }

    // Validate number of staff
    if ($number_of_staff < 1) {
        $errors['numberOfStaff'] = 'Please enter the number of staff (minimum 1)';
    } elseif ($number_of_staff > 1000) {
        $errors['numberOfStaff'] = 'Please enter a realistic number of staff (maximum 1000)';
    }

    if (empty($training_type)) {
        $errors['trainingType'] = 'Please select a training type';
    }

    if (!$consent) {
        $errors['consent'] = 'You must agree to be contacted';
    }

    if (!empty($errors)) {
        wp_send_json_error([
            'message' => 'Please correct the errors below.',
            'errors' => $errors,
            'code' => 'validation_failed'
            ], 400);
        }

        $training_type_labels = [
        'onsite' => 'On-Site Training',
        'classroom' => 'Classroom Sessions',
        'custom' => 'Custom Package',
    ];
        $training_label = $training_type_labels[$training_type] ?? ucfirst(str_replace('-', ' ', $training_type));

    // Check if email constant is defined BEFORE using it
    if (!defined('CTA_ENQUIRIES_EMAIL') || empty(CTA_ENQUIRIES_EMAIL)) {
        error_log('CTA Group Booking: CTA_ENQUIRIES_EMAIL constant not defined');
        wp_send_json_error([
            'message' => 'Email configuration error. Please contact us directly.',
            'code' => 'email_config_error'
        ], 500);
        return;
    }

    // Save submission to database first (so we can include admin link in email)
    $submission_data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $details,
        'consent' => $consent ? 'yes' : 'no',
        'marketing_consent' => $marketing_consent ? 'yes' : 'no',
        'organisation' => $organisation,
        'number_of_staff' => $number_of_staff,
        'training_type' => $training_type,
        'discount_code' => $discount_code,
        'discount_valid' => $discount_validation['valid'] ? 'yes' : 'no',
        'discount_message' => $discount_validation['message'],
        'discount_percent' => $discount_validation['discount'],
        'ip' => cta_get_client_ip(),
        'page_url' => $_POST['page_url'] ?? home_url('/group-training/'),
    ];
    $saved = cta_save_form_submission($submission_data, 'group-booking', false, '');

        $to = CTA_ENQUIRIES_EMAIL;
    $subject = sprintf('[CTA Group Training] %s - %d staff', $organisation, $number_of_staff);
    
    $body = "New group training enquiry:\n\n";
    $body .= "Contact: {$name}\n";
    $body .= "Email: {$email}\n";
    $body .= "Phone: {$phone}\n";
    $body .= "Organisation: {$organisation}\n";
    $body .= "Number of Staff: {$number_of_staff}\n";
    $body .= "Training Type: {$training_label}\n";
    
    if ($discount_code) {
        $body .= "\nDiscount Code: {$discount_code}\n";
        $body .= "Discount Status: {$discount_validation['message']}\n";
        if ($discount_validation['valid']) {
            $body .= "Discount Applied: {$discount_validation['discount']}% Off\n";
        }
    }
    
    if ($details) {
        $body .= "\nAdditional Details:\n{$details}\n\n";
    }
    
    $body .= "---\n";
    $body .= "Submitted: " . current_time('mysql') . "\n";
    $body .= "IP: " . cta_get_client_ip() . "\n\n";
    
    // Add admin link to view submission
    if (!is_wp_error($saved) && $saved) {
        $admin_link = admin_url('post.php?post=' . $saved . '&action=edit');
        $body .= "View in WordPress Admin:\n";
        $body .= esc_url($admin_link) . "\n\n";
    }

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];

    $sent = wp_mail($to, $subject, $body, $headers);
    $email_error = '';

    if (!$sent) {
        $email_error = 'Email sending failed';
        $last_error = error_get_last();
        error_log('CTA Group Booking: Failed to send email');
        if (is_array($last_error) && isset($last_error['type']) && $last_error['type'] === E_ERROR) {
            error_log('CTA Group Booking: PHP Error - ' . (isset($last_error['message']) ? $last_error['message'] : 'Unknown error'));
        }
        
        // Check if wp_mail function exists
        if (!function_exists('wp_mail')) {
            error_log('CTA Group Booking: wp_mail function does not exist');
        }
    }
    
    // Update submission with email status
    if (!is_wp_error($saved) && $saved) {
        update_post_meta($saved, '_submission_email_sent', $sent ? 'yes' : 'no');
        if ($email_error) {
            update_post_meta($saved, '_submission_email_error', $email_error);
        }
    }
    
    // If marketing consent given, add to newsletter (only if not already subscribed)
    if ($marketing_consent && !empty($email)) {
        $name_parts = explode(' ', trim($name), 2);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? '';
        cta_add_newsletter_subscriber($email, cta_get_client_ip(), $first_name, $last_name);
    }

    // If submission was saved successfully, show success even if email failed
    if (!is_wp_error($saved)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($sent) {
        error_log('CTA Group Booking: Successfully sent email');
            } else {
                error_log('CTA Group Booking: Email failed but submission saved (ID: ' . $saved . ')');
            }
    }

        wp_send_json_success([
            'message' => 'Thank you! Your group training enquiry has been received.',
            'training_type' => $training_label,
        ]);
        } else {
            // Only show error if submission couldn't be saved
            wp_send_json_error([
                'message' => 'Unable to process your request. Please try again or call us directly.',
                'code' => 'save_failed'
            ], 500);
        }
    } catch (Exception $e) {
        error_log('CTA Group Booking: Exception - ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'An error occurred while processing your request. Please try again or contact us directly.',
            'code' => 'server_error'
        ], 500);
    }
}
add_action('wp_ajax_cta_group_booking', 'cta_handle_group_booking');
add_action('wp_ajax_nopriv_cta_group_booking', 'cta_handle_group_booking');


/**
 * =========================================
 * COURSE BOOKING MODAL HANDLER
 * =========================================
 */
function cta_handle_course_booking() {
    // Use controller if available, otherwise fall back to legacy implementation
    if (class_exists('\\CTA\\Controllers\\CourseBookingController')) {
        $controller = new \CTA\Controllers\CourseBookingController();
        $controller->handle();
        return;
    }
    
    // Legacy fallback implementation (keep for backward compatibility)
    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cta_nonce')) {
            wp_send_json_error([
                'message' => 'Security verification failed. Please refresh the page and try again.',
                'code' => 'nonce_failed'
            ], 403);
        }

        // Comprehensive anti-bot validation
        $bot_check = cta_validate_anti_bot('course-booking');
        if ($bot_check === false) {
            // Bot detected - silently accept but don't process
            wp_send_json_success([
                'message' => 'Thank you for your booking enquiry.',
            ]);
        }

    // Sanitize inputs
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $delegates = absint($_POST['delegates'] ?? 1);
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    $course_name = sanitize_text_field($_POST['course_name'] ?? '');
    $course_id = absint($_POST['course_id'] ?? 0);
    $event_date = sanitize_text_field($_POST['event_date'] ?? '');
    $discount_code = sanitize_text_field($_POST['discount_code'] ?? '');
    $consent = isset($_POST['consent']) && $_POST['consent'] === 'true';
    $marketing_consent = isset($_POST['marketingConsent']) && $_POST['marketingConsent'] === 'true';

    // Validation
    $errors = [];
    
    // Validate discount code if provided
    $discount_validation = ['valid' => false, 'message' => '', 'discount' => 0];
    if (!empty($discount_code)) {
        if (function_exists('cta_validate_discount_code')) {
            $discount_validation = cta_validate_discount_code($discount_code);
            if (!$discount_validation['valid']) {
                $errors['discount_code'] = $discount_validation['message'] ?: 'This discount code is not valid.';
            }
        } else {
            $errors['discount_code'] = 'Unable to validate discount code. Please try again.';
        }
    }

    // Validate name
    $name_validation = cta_validate_name($name);
    if (!$name_validation['valid']) {
        $errors['name'] = $name_validation['error'];
    }

    // Validate email
    $email_validation = cta_validate_email($email, true);
    if (!$email_validation['valid']) {
        $errors['email'] = $email_validation['error'];
    }

    // Validate phone
    $phone_validation = cta_validate_uk_phone($phone);
    if (!$phone_validation['valid']) {
        $errors['phone'] = $phone_validation['error'];
    }

    // Validate delegates
    if ($delegates < 1) {
        $errors['delegates'] = 'Please enter the number of delegates (minimum 1)';
    } elseif ($delegates > 100) {
        $errors['delegates'] = 'Please enter a realistic number of delegates (maximum 100)';
    }

    if (!$consent) {
        $errors['consent'] = 'You must agree to be contacted';
    }

    if (!empty($errors)) {
        wp_send_json_error([
            'message' => 'Please correct the errors below.',
            'errors' => $errors,
            'code' => 'validation_failed'
        ], 400);
    }

    // Get course details if ID provided
    $course_title = $course_name;
    $course_url = '';
    if ($course_id) {
        $course = get_post($course_id);
        if ($course) {
            $course_title = $course->post_title;
            $course_url = get_permalink($course_id);
            
            // If this is a course_event and no event_date was provided, get it from the event
            $post_type = get_post_type($course_id);
            if ($post_type === 'course_event' && empty($event_date)) {
                // Try ACF field first, then post meta
                $course_event_date = function_exists('get_field') ? get_field('event_date', $course_id) : null;
                if (!$course_event_date) {
                    $course_event_date = get_post_meta($course_id, 'event_date', true);
                }
                
                // Only use if it's an upcoming course (date >= today)
                if ($course_event_date) {
                    $event_date_obj = is_string($course_event_date) ? strtotime($course_event_date) : $course_event_date;
                    $today = strtotime(date('Y-m-d'));
                    if ($event_date_obj && $event_date_obj >= $today) {
                        // Format date consistently
                        $event_date = is_string($course_event_date) ? $course_event_date : date('Y-m-d', $event_date_obj);
                    }
                }
            }
        }
    }

    // Save submission to database first (so we can include admin link in email)
    $submission_data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
        'consent' => $consent ? 'yes' : 'no',
        'marketing_consent' => $marketing_consent ? 'yes' : 'no',
        'course_id' => $course_id,
        'course_name' => $course_title,
        'event_date' => $event_date,
        'delegates' => $delegates,
        'discount_code' => $discount_code,
        'discount_valid' => $discount_validation['valid'] ? 'yes' : 'no',
        'discount_message' => $discount_validation['message'],
        'discount_percent' => $discount_validation['discount'],
        'ip' => cta_get_client_ip(),
        'page_url' => $_POST['page_url'] ?? ($course_url ?: home_url('/')),
    ];
    $saved = cta_save_form_submission($submission_data, 'course-booking', false, '');
    
    // Track WordPress booking for Eventbrite sync
    if (!is_wp_error($saved) && $course_id > 0) {
        $post_type = get_post_type($course_id);
        if ($post_type === 'course_event' && function_exists('cta_track_wordpress_booking')) {
            cta_track_wordpress_booking($course_id, $delegates);
        }
    }

    // Build email
    $to = CTA_ENQUIRIES_EMAIL;
    $subject = sprintf('[CTA Booking] %s - %s', $course_title ?: 'Course Enquiry', $name);
    
    $body = "New course booking enquiry:\n\n";
    $body .= "Contact: {$name}\n";
    $body .= "Email: {$email}\n";
    $body .= "Phone: {$phone}\n";
    $body .= "Number of Delegates: {$delegates}\n\n";
    $body .= "Course: {$course_title}\n";
    
    if ($event_date) {
        $body .= "Event Date: {$event_date}\n";
    }
    
    if ($course_url) {
        $body .= "Course URL: {$course_url}\n";
    }
    
    if ($discount_code) {
        $body .= "\nDiscount Code: {$discount_code}\n";
        $body .= "Discount Status: {$discount_validation['message']}\n";
        if ($discount_validation['valid']) {
            $body .= "Discount Applied: {$discount_validation['discount']}% Off\n";
        }
    }
    
    if ($message) {
        $body .= "\nAdditional Information:\n{$message}\n";
    }
    
    $body .= "\n---\n";
    $body .= "Submitted: " . current_time('mysql') . "\n";
    $body .= "IP: " . cta_get_client_ip() . "\n\n";
    
    // Add admin link to view submission
    if (!is_wp_error($saved) && $saved) {
        $admin_link = admin_url('post.php?post=' . $saved . '&action=edit');
        $body .= "View in WordPress Admin:\n";
        $body .= esc_url($admin_link) . "\n\n";
    }

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];

    $sent = wp_mail($to, $subject, $body, $headers);
    $email_error = '';

    if (!$sent) {
        $email_error = 'Email sending failed';
        error_log('CTA Course Booking: Failed to send email');
    }
    
    // Update submission with email status
    if (!is_wp_error($saved) && $saved) {
        update_post_meta($saved, '_submission_email_sent', $sent ? 'yes' : 'no');
        if ($email_error) {
            update_post_meta($saved, '_submission_email_error', $email_error);
        }
    }
    
    // If marketing consent given, add to newsletter (only if not already subscribed)
    if ($marketing_consent && !empty($email)) {
        $name_parts = explode(' ', trim($name), 2);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? '';
        cta_add_newsletter_subscriber($email, cta_get_client_ip(), $first_name, $last_name);
    }

    // If submission was saved successfully, show success even if email failed
    if (!is_wp_error($saved)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($sent) {
        error_log('CTA Course Booking: Successfully sent email');
            } else {
                error_log('CTA Course Booking: Email failed but submission saved (ID: ' . $saved . ')');
            }
    }

    wp_send_json_success([
        'message' => 'Thank you! Your booking enquiry for ' . esc_html($course_title) . ' has been received.',
        'course_name' => $course_title,
    ]);
    } else {
        // Only show error if submission couldn't be saved
        wp_send_json_error([
            'message' => 'Unable to process your request. Please try again or call us directly.',
            'code' => 'save_failed'
        ], 500);
    }
    } catch (Exception $e) {
        error_log('CTA Course Booking: Exception - ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'An error occurred while processing your request. Please try again or contact us directly.',
            'code' => 'server_error'
        ], 500);
    }
}
add_action('wp_ajax_cta_course_booking', 'cta_handle_course_booking');
add_action('wp_ajax_nopriv_cta_course_booking', 'cta_handle_course_booking');


/**
 * =========================================
 * COURSE SEARCH AJAX HANDLER
 * =========================================
 */
function cta_handle_course_search() {
    $query = sanitize_text_field($_GET['q'] ?? $_POST['q'] ?? '');
    
    if (empty($query) || strlen($query) < 2) {
        wp_send_json_success(['courses' => []]);
    }
    
    // Search courses
    $args = [
        'post_type' => 'course',
        'posts_per_page' => 8,
        's' => $query,
        'post_status' => 'publish',
    ];
    
    $search_query = new WP_Query($args);
    $courses = [];
    
    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            
            $terms = get_the_terms(get_the_ID(), 'course_category');
            $category = $terms && !is_wp_error($terms) ? $terms[0]->name : '';
            
            $courses[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'url' => get_permalink(),
                'category' => $category,
                'duration' => get_field('course_duration') ?: '',
            ];
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success(['courses' => $courses]);
}
add_action('wp_ajax_cta_course_search', 'cta_handle_course_search');
add_action('wp_ajax_nopriv_cta_course_search', 'cta_handle_course_search');

/**
 * Get all courses for dropdown selection
 */
function cta_handle_get_courses() {
    $args = [
        'post_type' => 'course',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish',
    ];
    
    $courses_query = new WP_Query($args);
    $courses = [];
    
    if ($courses_query->have_posts()) {
        while ($courses_query->have_posts()) {
            $courses_query->the_post();
            
            $courses[] = [
                'id' => get_the_ID(),
                'title' => html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'url' => get_permalink(),
            ];
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success(['courses' => $courses]);
}
add_action('wp_ajax_cta_get_courses', 'cta_handle_get_courses');
add_action('wp_ajax_nopriv_cta_get_courses', 'cta_handle_get_courses');


/**
 * =========================================
 * CALLBACK REQUEST FORM HANDLER
 * =========================================
 */
function cta_handle_callback_request() {
    // Use controller if available, otherwise fall back to legacy implementation
    if (class_exists('\\CTA\\Controllers\\CallbackRequestController')) {
        $controller = new \CTA\Controllers\CallbackRequestController();
        $controller->handle();
        return;
    }
    
    // Legacy fallback implementation (keep for backward compatibility)
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cta_nonce')) {
            wp_send_json_error([
                'message' => 'Security verification failed. Please refresh the page and try again.',
                'code' => 'nonce_failed'
            ], 403);
        }

        // Comprehensive anti-bot validation
        $bot_check = cta_validate_anti_bot('callback-request');
        if ($bot_check === false) {
            // Bot detected - silently accept but don't process
            wp_send_json_success([
                'message' => 'Thank you! We will call you back.',
            ]);
        }

        $name = sanitize_text_field($_POST['name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $consent = isset($_POST['consent']) && $_POST['consent'] === 'on';
        $marketing_consent = isset($_POST['marketingConsent']) && $_POST['marketingConsent'] === 'on';

        $errors = [];

        // Validate name
        $name_validation = cta_validate_name($name);
        if (!$name_validation['valid']) {
            $errors['name'] = $name_validation['error'];
        }

        // Validate phone (required for callback)
        $phone_validation = cta_validate_uk_phone($phone);
        if (!$phone_validation['valid']) {
            $errors['phone'] = $phone_validation['error'];
        }

        // Validate email (optional for callback)
        if (!empty($email)) {
            $email_validation = cta_validate_email($email, false);
            if (!$email_validation['valid']) {
                $errors['email'] = $email_validation['error'];
            }
        }

        if (!$consent) {
            $errors['consent'] = 'You must agree to be contacted';
        }

        if (!empty($errors)) {
            wp_send_json_error([
                'message' => 'Please correct the errors below.',
                'errors' => $errors,
                'code' => 'validation_failed'
            ], 400);
        }

        // Save submission to database first (so we can include admin link in email)
        $submission_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'consent' => $consent ? 'yes' : 'no',
            'marketing_consent' => $marketing_consent ? 'yes' : 'no',
            'ip' => cta_get_client_ip(),
            'page_url' => $_POST['page_url'] ?? home_url('/'),
        ];
        $saved = cta_save_form_submission($submission_data, 'callback-request', false, '');

        // Build email
        $to = CTA_ENQUIRIES_EMAIL;
        $subject = sprintf('[CTA Callback Request] From %s', $name);
        
        $body = "New callback request:\n\n";
        $body .= "Name: {$name}\n";
        $body .= "Phone: {$phone}\n";
        if (!empty($email)) {
            $body .= "Email: {$email}\n";
        }
        $body .= "\n---\n";
        $body .= "Submitted: " . current_time('mysql') . "\n";
        $body .= "IP: " . (function_exists('cta_get_client_ip') ? cta_get_client_ip() : '') . "\n";
        $body .= "Page: " . esc_url($_POST['page_url'] ?? home_url('/')) . "\n\n";
        
        // Add admin link to view submission
        if (!is_wp_error($saved) && $saved) {
            $admin_link = admin_url('post.php?post=' . $saved . '&action=edit');
            $body .= "View in WordPress Admin:\n";
            $body .= esc_url($admin_link) . "\n\n";
        }

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
        ];
        if (!empty($email)) {
            $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
        }

        $sent = wp_mail($to, $subject, $body, $headers);
        $email_error = '';

        if (!$sent) {
            $email_error = 'Email sending failed';
            error_log('CTA Callback Request: Failed to send email');
        }
        
        // Update submission with email status
        if (!is_wp_error($saved) && $saved) {
            update_post_meta($saved, '_submission_email_sent', $sent ? 'yes' : 'no');
            if ($email_error) {
                update_post_meta($saved, '_submission_email_error', $email_error);
            }
        }
        
        // If marketing consent given, add to newsletter (only if not already subscribed)
        if ($marketing_consent && !empty($email)) {
            $name_parts = explode(' ', trim($name), 2);
            $first_name = $name_parts[0] ?? '';
            $last_name = $name_parts[1] ?? '';
            cta_add_newsletter_subscriber($email, cta_get_client_ip(), $first_name, $last_name);
        }

        // If submission was saved successfully, show success even if email failed
        if (!is_wp_error($saved)) {
            // Log successful saves (ID only for privacy)
            error_log('CTA Callback Request: Submission saved (ID: ' . $saved . ')');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                if ($sent) {
                    error_log('CTA Callback Request: Email successfully sent');
                } else {
                    error_log('CTA Callback Request: Email failed but submission saved (ID: ' . $saved . ')');
                }
        }

        wp_send_json_success([
            'message' => "Thank you! We'll call you back.",
        ]);
        } else {
            // Log save failures (error message only, no personal data)
            error_log('CTA Callback Request: FAILED to save submission - ' . $saved->get_error_message());
            // Only show error if submission couldn't be saved
            wp_send_json_error([
                'message' => 'Unable to process your request. Please try again or call us directly.',
                'code' => 'save_failed'
            ], 500);
        }
    } catch (Exception $e) {
        error_log('CTA Callback Request Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'An error occurred while processing your request. Please try again or contact us directly.',
            'code' => 'server_error'
        ], 500);
    }
}
add_action('wp_ajax_cta_callback_request', 'cta_handle_callback_request');
add_action('wp_ajax_nopriv_cta_callback_request', 'cta_handle_callback_request');

/**
 * =========================================
 * REAL-TIME DISCOUNT CODE VALIDATION
 * =========================================
 */
function cta_ajax_validate_discount_code() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cta_nonce')) {
        wp_send_json_error([
            'message' => 'Security verification failed. Please refresh the page and try again.',
            'code' => 'nonce_failed',
        ], 403);
    }

    $discount_code = sanitize_text_field($_POST['discount_code'] ?? '');

    if (empty($discount_code)) {
        wp_send_json_success([
            'valid' => false,
            'message' => '',
            'discount' => 0,
            'error_type' => 'empty',
        ]);
    }

    if (!function_exists('cta_validate_discount_code')) {
        wp_send_json_error([
            'message' => 'Discount validation is not available.',
            'code' => 'validation_unavailable',
        ], 500);
    }

    $validation = cta_validate_discount_code($discount_code);
    wp_send_json_success($validation);
}
add_action('wp_ajax_validate_discount_code', 'cta_ajax_validate_discount_code');
add_action('wp_ajax_nopriv_validate_discount_code', 'cta_ajax_validate_discount_code');


/**
 * =========================================
 * HELPER FUNCTIONS
 * =========================================
 */

/**
 * Save form submission to database
 *
 * @param array $data Submission data
 * @param string $form_type Form type (contact, callback-request, group-booking, course-booking)
 * @param bool $email_sent Whether email was sent successfully
 * @param string $email_error Error message if email failed
 * @return int|WP_Error Post ID on success, WP_Error on failure
 */
function cta_save_form_submission($data, $form_type, $email_sent = false, $email_error = '') {
    // Use repository if available, otherwise fall back to legacy implementation
    if (class_exists('\\CTA\\Repositories\\FormSubmissionRepository')) {
        $repository = new \CTA\Repositories\FormSubmissionRepository();
        return $repository->create($data, $form_type, $email_sent, $email_error);
    }
    
    // Legacy fallback implementation (keep for backward compatibility)
    $name = sanitize_text_field($data['name'] ?? '');
    $email = isset($data['email']) ? sanitize_email($data['email']) : '';
    $phone = sanitize_text_field($data['phone'] ?? '');
    $message = isset($data['message']) ? sanitize_textarea_field($data['message']) : '';
    $consent = isset($data['consent']) && ($data['consent'] === 'yes' || $data['consent'] === 'on' || $data['consent'] === '1');
    $marketing_consent = isset($data['marketing_consent']) && ($data['marketing_consent'] === 'yes' || $data['marketing_consent'] === 'on' || $data['marketing_consent'] === '1');
    
    // Create post title
    $title = $name ?: 'Anonymous Submission';
    if ($form_type) {
        $title .= ' - ' . ucfirst(str_replace('-', ' ', $form_type));
    }
    
    // Insert post
    $post_data = [
        'post_title' => $title,
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => 'form_submission',
    ];
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        error_log('CTA Form Submission: Failed to save submission - ' . $post_id->get_error_message());
        error_log('CTA Form Submission: Post data: ' . print_r($post_data, true));
        return $post_id;
    }
    
    // Log successful creation (ID and type only for privacy)
    error_log('CTA Form Submission: Created submission post ID ' . $post_id . ' (Type: ' . $form_type . ')');
    
    // Set form type taxonomy
    if ($form_type) {
        $term = get_term_by('slug', $form_type, 'form_type');
        if (!$term) {
            // Create term if it doesn't exist
            $term_result = wp_insert_term(ucfirst(str_replace('-', ' ', $form_type)), 'form_type', ['slug' => $form_type]);
            if (!is_wp_error($term_result)) {
                $term_id = $term_result['term_id'];
            }
        } else {
            $term_id = $term->term_id;
        }
        
        if (isset($term_id)) {
            wp_set_object_terms($post_id, [$term_id], 'form_type');
        }
    }
    
    // Save meta fields
    update_post_meta($post_id, '_submission_name', $name);
    if ($email) {
        update_post_meta($post_id, '_submission_email', $email);
    }
    if ($phone) {
        update_post_meta($post_id, '_submission_phone', $phone);
    }
    if ($message) {
        update_post_meta($post_id, '_submission_message', $message);
    }
    update_post_meta($post_id, '_submission_consent', $consent ? 'yes' : 'no');
    update_post_meta($post_id, '_submission_marketing_consent', $marketing_consent ? 'yes' : 'no');
    update_post_meta($post_id, '_submission_email_sent', $email_sent ? 'yes' : 'no');
    if ($email_error) {
        update_post_meta($post_id, '_submission_email_error', $email_error);
    }
    
    // If marketing consent is given and email is provided, add to newsletter list
    if ($marketing_consent && !empty($email) && $form_type !== 'newsletter') {
        // Extract first and last name from name field if available
        $first_name = '';
        $last_name = '';
        if (!empty($name)) {
            $name_parts = explode(' ', trim($name), 2);
            $first_name = $name_parts[0] ?? '';
            $last_name = $name_parts[1] ?? '';
        }
        
        // Add to newsletter subscribers and capture the result
        $newsletter_result = cta_add_newsletter_subscriber($email, cta_get_client_ip(), $first_name, $last_name);
        
        // Store newsletter subscription status in submission metadata
        if ($newsletter_result) {
            update_post_meta($post_id, '_submission_newsletter_status', $newsletter_result);
            
            // Also check if email exists in newsletter to show in admin
            global $wpdb;
            $subscriber_table = $wpdb->prefix . 'cta_newsletter_subscribers';
            $subscriber = $wpdb->get_row($wpdb->prepare(
                "SELECT id, status, subscribed_at FROM $subscriber_table WHERE email = %s",
                $email
            ));
            
            if ($subscriber) {
                update_post_meta($post_id, '_submission_newsletter_subscriber_id', $subscriber->id);
                update_post_meta($post_id, '_submission_newsletter_subscriber_status', $subscriber->status);
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $status_messages = [
                'added' => 'Added',
                'reactivated' => 'Reactivated',
                'exists' => 'Already subscribed'
            ];
            $status = $status_messages[$newsletter_result] ?? $newsletter_result;
            error_log('CTA Form Submission: Newsletter subscription - ' . $status . ' from ' . $form_type . ' form');
        }
    } else {
        // Even if no consent, check if email exists in newsletter for admin visibility
        if (!empty($email)) {
            global $wpdb;
            $subscriber_table = $wpdb->prefix . 'cta_newsletter_subscribers';
            $subscriber = $wpdb->get_row($wpdb->prepare(
                "SELECT id, status, subscribed_at FROM $subscriber_table WHERE email = %s",
                $email
            ));
            
            if ($subscriber) {
                update_post_meta($post_id, '_submission_newsletter_subscriber_id', $subscriber->id);
                update_post_meta($post_id, '_submission_newsletter_subscriber_status', $subscriber->status);
                update_post_meta($post_id, '_submission_newsletter_status', 'exists_no_consent');
            }
        }
    }
    
    // Track consecutive email failures and alert if 3 in a row
    cta_track_email_failures($email_sent);
    
    // Save additional metadata
    if (isset($data['ip'])) {
        update_post_meta($post_id, '_submission_ip', sanitize_text_field($data['ip']));
    } else {
        update_post_meta($post_id, '_submission_ip', cta_get_client_ip());
    }
    
    if (isset($data['user_agent'])) {
        update_post_meta($post_id, '_submission_user_agent', sanitize_text_field($data['user_agent']));
    } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
        update_post_meta($post_id, '_submission_user_agent', sanitize_text_field($_SERVER['HTTP_USER_AGENT']));
    }
    
    if (isset($data['page_url'])) {
        update_post_meta($post_id, '_submission_page_url', esc_url_raw($data['page_url']));
    }
    
    // Save course-specific data separately for easier access
    if (isset($data['course_id']) && $data['course_id']) {
        update_post_meta($post_id, '_submission_course_id', absint($data['course_id']));
    }
    if (isset($data['course_name']) && $data['course_name']) {
        update_post_meta($post_id, '_submission_course_name', sanitize_text_field($data['course_name']));
    }
    if (isset($data['event_date']) && $data['event_date']) {
        update_post_meta($post_id, '_submission_event_date', sanitize_text_field($data['event_date']));
    }
    
    // Save Meta Lead ID if provided (for Facebook Conversion Leads Integration)
    if (isset($data['meta_lead_id']) && preg_match('/^\d{15,17}$/', $data['meta_lead_id'])) {
        update_post_meta($post_id, '_submission_meta_lead_id', sanitize_text_field($data['meta_lead_id']));
    }
    
    // Save any additional form data
    $form_data = [];
    foreach ($data as $key => $value) {
        if (!in_array($key, ['name', 'email', 'phone', 'message', 'consent', 'nonce', 'action', 'ip', 'user_agent', 'page_url'])) {
            $form_data[$key] = is_array($value) ? $value : sanitize_text_field($value);
        }
    }
    if (!empty($form_data)) {
        update_post_meta($post_id, '_submission_form_data', $form_data);
    }
    
    // Auto-assign 'new' followup status for non-newsletter submissions
    // Newsletter subscriptions are handled separately and don't need followup status
    $newsletter_form_types = ['newsletter', 'newsletter-subscription'];
    if (empty($form_type) || !in_array($form_type, $newsletter_form_types)) {
        update_post_meta($post_id, '_submission_followup_status', 'new');
    }
    
    return $post_id;
}

/**
 * Track consecutive email failures and alert if threshold reached
 *
 * @param bool $email_sent Whether the email was sent successfully
 */
function cta_track_email_failures($email_sent) {
    $failure_count_key = 'cta_email_failure_count';
    $last_alert_key = 'cta_email_failure_alert_sent';
    $failure_threshold = 3;
    
    if ($email_sent) {
        // Reset counter on success
        delete_transient($failure_count_key);
        delete_transient($last_alert_key);
    } else {
        // Increment failure count
        $current_count = get_transient($failure_count_key);
        $new_count = ($current_count !== false) ? intval($current_count) + 1 : 1;
        
        set_transient($failure_count_key, $new_count, DAY_IN_SECONDS);
        
        // Check if we've hit the threshold
        if ($new_count >= $failure_threshold) {
            // Check if we've already sent an alert recently (within last hour)
            $last_alert = get_transient($last_alert_key);
            
            if ($last_alert === false) {
                // Send alert email
                $alert_sent = cta_send_email_failure_alert($new_count);
                
                if ($alert_sent) {
                    // Mark that we've sent an alert (prevent duplicates for 1 hour)
                    set_transient($last_alert_key, time(), HOUR_IN_SECONDS);
                }
            }
        }
    }
}

/**
 * Send alert email when multiple email failures occur
 *
 * @param int $failure_count Number of consecutive failures
 * @return bool Whether the alert email was sent successfully
 */
function cta_send_email_failure_alert($failure_count) {
    $to = CTA_ENQUIRIES_EMAIL;
    $subject = '[URGENT] CTA Website: Multiple Form Submission Email Failures';
    
    $body = "⚠️ ALERT: Email Delivery Issue Detected\n\n";
    $body .= "The website has experienced {$failure_count} consecutive form submission email failures.\n\n";
    $body .= "This indicates a potential issue with email delivery from the website.\n\n";
    $body .= "Details:\n";
    $body .= "- Failure Count: {$failure_count} consecutive failures\n";
    $body .= "- Time: " . current_time('mysql') . "\n";
    $body .= "- Site: " . home_url() . "\n\n";
    $body .= "Action Required:\n";
    $body .= "1. Check WordPress email configuration\n";
    $body .= "2. Verify SMTP settings if using an SMTP plugin\n";
    $body .= "3. Check server email logs\n";
    $body .= "4. Review recent form submissions in WordPress admin\n\n";
    $body .= "Recent submissions are still being saved to the database, but notification emails are not being delivered.\n\n";
    $body .= "---\n";
    $body .= "This is an automated alert from the CTA website.\n";
    
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
    ];
    
    $sent = wp_mail($to, $subject, $body, $headers);
    
    if ($sent) {
        error_log("CTA Email Alert: Successfully sent failure alert to {$to} after {$failure_count} failures");
    } else {
        error_log("CTA Email Alert: FAILED to send alert email - this is critical!");
    }
    
    return $sent;
}

/**
 * Validate UK phone number
 *
 * @param string $phone Phone number to validate
 * @return array ['valid' => bool, 'error' => string|null] Validation result with error message if invalid
 */
function cta_validate_uk_phone($phone) {
    if (empty($phone)) {
        return ['valid' => false, 'error' => 'Phone number is required'];
    }
    
    $original = trim($phone);
    
    // Remove all whitespace and common formatting characters
    $cleaned = preg_replace('/[\s\-\(\)\.]/', '', $original);
    
    // Handle international format: +44 or 0044
    if (preg_match('/^(\+44|0044)/', $cleaned)) {
        // Extract digits after country code
        $digits_after_code = preg_replace('/\D/', '', substr($cleaned, preg_match('/^\+44/', $cleaned) ? 3 : 4));
        // Convert to UK format (remove leading 0 if present, then add 0)
        $digits_after_code = ltrim($digits_after_code, '0');
        $cleaned = '0' . $digits_after_code;
    }
    
    // Extract only digits for validation
    $digits_only = preg_replace('/\D/', '', $cleaned);
    
    // Must have 10-11 digits
    $digit_count = strlen($digits_only);
    if ($digit_count < 10 || $digit_count > 11) {
        return ['valid' => false, 'error' => 'Phone number must be 10-11 digits (e.g., 01622 587343 or 07123 456789)'];
    }
    
    // Must start with 0 and be followed by a non-zero digit (UK format)
    if (!preg_match('/^0[1-9]/', $digits_only)) {
        // Check if it's all digits but missing leading 0
        if (preg_match('/^[1-9]\d{9,10}$/', $digits_only)) {
            return ['valid' => false, 'error' => 'UK phone numbers should start with 0 (e.g., 01622 587343)'];
        }
        return ['valid' => false, 'error' => 'Please enter a valid UK phone number (e.g., 01622 587343 or 07123 456789)'];
    }
    
    // More specific pattern matching for UK numbers (using digits_only to ensure clean validation)
    // Mobile: 07xxx xxxxxx (11 digits starting with 07)
    // Landline: 01xxx xxxxxx (10 digits) or 02x xxxx xxxx (10-11 digits)
    // Non-geographic: 03xx, 05xx, 08xx, 09xx (10-11 digits)
    $pattern = '/^0[1-9]\d{8,9}$/';
    
    if (!preg_match($pattern, $digits_only)) {
        return ['valid' => false, 'error' => 'Please enter a valid UK phone number format'];
    }
    
    // Additional validation: Check for suspicious patterns
    // Repeating digits (e.g., 0000000000, 1111111111)
    if (preg_match('/^0(\d)\1{8,9}$/', $digits_only)) {
        return ['valid' => false, 'error' => 'Please enter a valid phone number'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Validate name field
 *
 * @param string $name Name to validate
 * @return array ['valid' => bool, 'error' => string|null] Validation result
 */
function cta_validate_name($name) {
    if (empty($name)) {
        return ['valid' => false, 'error' => 'Name is required'];
    }
    
    $trimmed = trim($name);
    
    if (strlen($trimmed) < 2) {
        return ['valid' => false, 'error' => 'Please enter your full name (at least 2 characters)'];
    }
    
    if (strlen($trimmed) > 100) {
        return ['valid' => false, 'error' => 'Name is too long (maximum 100 characters)'];
    }
    
    // Check for suspicious patterns
    // All numbers
    if (preg_match('/^\d+$/', $trimmed)) {
        return ['valid' => false, 'error' => 'Please enter a valid name'];
    }
    
    // Too many special characters (more than 30% special chars is suspicious)
    $special_char_count = preg_match_all('/[^a-zA-Z0-9\s\-\']/', $trimmed);
    if ($special_char_count > strlen($trimmed) * 0.3) {
        return ['valid' => false, 'error' => 'Please enter a valid name'];
    }
    
    // Check for excessive whitespace
    if (preg_match('/\s{3,}/', $trimmed)) {
        return ['valid' => false, 'error' => 'Please enter a valid name'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Validate email field
 *
 * @param string $email Email to validate
 * @param bool $required Whether email is required
 * @return array ['valid' => bool, 'error' => string|null] Validation result
 */
function cta_validate_email($email, $required = true) {
    if (empty($email)) {
        if ($required) {
            return ['valid' => false, 'error' => 'Email address is required'];
        }
        return ['valid' => true, 'error' => null]; // Optional email
    }
    
    $trimmed = trim($email);
    
    // Basic format check before WordPress validation
    if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $trimmed)) {
        return ['valid' => false, 'error' => 'Please enter a valid email address (e.g., name@example.com)'];
    }
    
    // Use WordPress built-in validation
    if (!is_email($trimmed)) {
        return ['valid' => false, 'error' => 'Please enter a valid email address (e.g., name@example.com)'];
    }
    
    // Additional checks for common issues
    if (strlen($trimmed) > 254) {
        return ['valid' => false, 'error' => 'Email address is too long (maximum 254 characters)'];
    }
    
    // Check for suspicious patterns
    if (preg_match('/\.{2,}/', $trimmed) || preg_match('/@{2,}/', $trimmed)) {
        return ['valid' => false, 'error' => 'Please enter a valid email address'];
    }
    
    // Check for spaces (common user error)
    if (preg_match('/\s/', $trimmed)) {
        return ['valid' => false, 'error' => 'Email address cannot contain spaces'];
    }
    
    // Check for invalid characters
    if (preg_match('/[<>"\']/', $trimmed)) {
        return ['valid' => false, 'error' => 'Email address contains invalid characters'];
    }
    
    // Check domain part is reasonable
    $parts = explode('@', $trimmed);
    if (count($parts) === 2) {
        $domain = $parts[1];
        if (strlen($domain) < 4 || !preg_match('/\./', $domain)) {
            return ['valid' => false, 'error' => 'Please enter a valid email address with a domain (e.g., name@example.com)'];
        }
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Get client IP address
 *
 * @return string IP address
 */
function cta_get_client_ip() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can contain multiple IPs - get the first one
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return sanitize_text_field($ip);
}

/**
 * Comprehensive anti-bot validation
 *
 * Checks multiple signals to detect bot submissions:
 * - Honeypot fields (multiple variations)
 * - Time-based validation (minimum time to fill form)
 * - Rate limiting per IP
 * - User agent validation
 * - Referrer validation
 *
 * @param string $form_type Form type identifier
 * @return array|false Returns false if bot detected, or array with validation results
 */
function cta_validate_anti_bot($form_type = 'general') {
    $ip = cta_get_client_ip();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
    $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
    $site_url = home_url();
    
    // 1. Honeypot checks - multiple variations
    $honeypot_fields = ['website', 'url', 'homepage', 'company_website', 'website_url'];
    foreach ($honeypot_fields as $field) {
        if (!empty($_POST[$field])) {
            // Bot detected - silently accept but don't process
        if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("CTA Anti-Bot: Honeypot field '{$field}' filled by IP {$ip}");
            }
            return false;
        }
    }
    
    // 2. reCAPTCHA validation
    $recaptcha_response = isset($_POST['g-recaptcha-response']) ? sanitize_text_field($_POST['g-recaptcha-response']) : '';
    $recaptcha_secret = function_exists('cta_get_recaptcha_secret_key') ? cta_get_recaptcha_secret_key() : get_theme_mod('cta_recaptcha_secret_key', '');
    
    if (!empty($recaptcha_secret)) {
        // Only verify if reCAPTCHA is configured
        if (empty($recaptcha_response)) {
            // No reCAPTCHA response - allow through but log (graceful degradation)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("CTA Anti-Bot: No reCAPTCHA response from IP {$ip} - allowing submission");
            }
            // Allow submission to proceed (graceful degradation)
            return true;
        }
        
        // Verify reCAPTCHA with Google
        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        $verify_data = [
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_response,
            'remoteip' => $ip,
        ];
        
        $verify_response = wp_remote_post($verify_url, [
            'body' => $verify_data,
            'timeout' => 10,
        ]);
        
        if (is_wp_error($verify_response)) {
            // Network error - log but don't block (graceful degradation)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("CTA Anti-Bot: reCAPTCHA verification network error: " . $verify_response->get_error_message());
            }
            // Allow submission to proceed on network errors (graceful degradation)
            return true;
        } else {
            $verify_body = wp_remote_retrieve_body($verify_response);
            $verify_result = json_decode($verify_body, true);
            
            if (!isset($verify_result['success']) || !$verify_result['success']) {
                // reCAPTCHA verification failed - check for specific error codes
                $error_codes = isset($verify_result['error-codes']) ? $verify_result['error-codes'] : [];
                $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown';
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $error_msg = "CTA Anti-Bot: reCAPTCHA verification failed for IP {$ip} on domain {$domain}";
                    if (!empty($error_codes)) {
                        $error_msg .= " - Error codes: " . implode(', ', $error_codes);
                        // Check for domain-related errors
                        if (in_array('invalid-input-response', $error_codes) || in_array('missing-input-response', $error_codes)) {
                            $error_msg .= " - Domain '{$domain}' may not be in allowed list. Add it in Google Cloud Console: https://console.cloud.google.com/security/recaptcha";
                        }
                    }
                    error_log($error_msg);
                }
                
                // For domain errors, allow submission but log (graceful degradation)
                if (in_array('invalid-input-response', $error_codes) || in_array('missing-input-response', $error_codes)) {
                    // Domain not configured - allow but log
                    return true;
                }
                
                // Other errors - treat as potential bot
                return false;
            }
        }
    }
    
    // 3. Time-based validation - only check for suspiciously fast submissions (less than 1 second)
    $form_load_time = isset($_POST['form_load_time']) ? floatval($_POST['form_load_time']) : 0;
    $submission_time = isset($_POST['submission_time']) ? floatval($_POST['submission_time']) : time();
    
    if ($form_load_time > 0) {
        $time_taken = $submission_time - $form_load_time;
        $minimum_time = 1; // Only block if submitted in less than 1 second (very suspicious)
        
        if ($time_taken < $minimum_time) {
            // Submitted extremely quickly - likely a bot
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("CTA Anti-Bot: Form submitted extremely quickly ({$time_taken}s) by IP {$ip}");
            }
            return false;
        }
        
        // Also check for suspiciously long times (over 1 hour) - might be a saved/stale form
        $maximum_time = 3600; // 1 hour
        if ($time_taken > $maximum_time) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("CTA Anti-Bot: Form submitted after too long ({$time_taken}s) by IP {$ip}");
            }
            return false;
        }
    }
    
    // 4. Rate limiting per IP - more lenient to avoid blocking legitimate users
    $rate_limit_key = 'cta_form_submission_' . md5($ip . $form_type);
    $rate_limit_count = get_transient($rate_limit_key);
    
    if ($rate_limit_count === false) {
        $rate_limit_count = 0;
    }
    
    // Allow 10 submissions per hour per IP per form type (more lenient)
    $rate_limit_max = 10;
    $rate_limit_window = HOUR_IN_SECONDS;
    
    if ($rate_limit_count >= $rate_limit_max) {
        // Rate limit exceeded
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("CTA Anti-Bot: Rate limit exceeded ({$rate_limit_count}/{$rate_limit_max}) by IP {$ip} for form {$form_type}");
        }
        return false;
    }
    
    // Increment rate limit counter
    set_transient($rate_limit_key, $rate_limit_count + 1, $rate_limit_window);
    
    // 5. User agent validation
    if (empty($user_agent)) {
        // No user agent - suspicious
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("CTA Anti-Bot: Missing user agent from IP {$ip}");
        }
        return false;
    }
    
    // Check for common bot user agents
    $bot_patterns = [
        'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 
        'python-requests', 'go-http-client', 'java/', 'perl'
    ];
    
    $user_agent_lower = strtolower($user_agent);
    foreach ($bot_patterns as $pattern) {
        if (strpos($user_agent_lower, $pattern) !== false) {
            // Bot user agent detected
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("CTA Anti-Bot: Bot user agent detected ({$pattern}) from IP {$ip}");
            }
            return false;
        }
    }
    
    // 6. Referrer validation (optional - can be bypassed by direct access, but helps)
    // Only check if referrer is present (some legitimate users may not have one)
    if (!empty($referrer)) {
        $referrer_host = parse_url($referrer, PHP_URL_HOST);
        $site_host = parse_url($site_url, PHP_URL_HOST);
        
        // If referrer is present, it should be from our site
        if ($referrer_host && $referrer_host !== $site_host) {
            // Referrer is from different domain - might be suspicious
            // But don't block - could be legitimate (e.g., email link)
            // Just log for monitoring
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("CTA Anti-Bot: External referrer ({$referrer_host}) from IP {$ip}");
            }
        }
    }
    
    // 7. Check for suspicious field patterns (e.g., all fields filled with same value)
    $suspicious_fields = ['name', 'email', 'phone', 'message'];
    $field_values = [];
    foreach ($suspicious_fields as $field) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified earlier
        // phpstan:ignore-next-line
        if (isset($_POST[$field]) && !empty($_POST[$field])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified earlier
            // phpstan:ignore-next-line
            $field_values[] = sanitize_text_field($_POST[$field]);
        }
    }
    
    // If multiple fields have identical values, it's suspicious
    if (count($field_values) > 1 && count(array_unique($field_values)) === 1) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("CTA Anti-Bot: Suspicious field pattern (identical values) from IP {$ip}");
        }
        return false;
    }
    
    // All checks passed
    return [
        'ip' => $ip,
        'user_agent' => $user_agent,
        'referrer' => $referrer,
        'time_taken' => $form_load_time > 0 ? ($submission_time - $form_load_time) : 0,
    ];
}

