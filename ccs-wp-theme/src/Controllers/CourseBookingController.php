<?php
/**
 * Course Booking Controller
 *
 * Handles course booking form submissions with validation and email delivery
 *
 * @package CTA\Controllers
 */

namespace CCS\Controllers;

use CCS\Services\FormValidator;
use CCS\Repositories\FormSubmissionRepository;

class CourseBookingController {
    
    /** @var FormValidator */
    private $validator;
    
    /** @var FormSubmissionRepository */
    private $repository;
    
    public function __construct(?FormValidator $validator = null, ?FormSubmissionRepository $repository = null) {
        $this->validator = $validator ?? new FormValidator();
        $this->repository = $repository ?? new FormSubmissionRepository();
    }
    
    /**
     * Handle course booking form submission
     *
     * @return void Sends JSON response and exits
     */
    public function handle(): void {
        try {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccs_nonce')) {
                wp_send_json_error([
                    'message' => 'Security verification failed. Please refresh the page and try again.',
                    'code' => 'nonce_failed'
                ], 403);
            }

            // Anti-bot validation
            $bot_check = $this->validator->validateAntiBot('course-booking');
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
                if (function_exists('ccs_validate_discount_code')) {
                    $discount_validation = ccs_validate_discount_code($discount_code);
                    if (!$discount_validation['valid']) {
                        $errors['discount_code'] = $discount_validation['message'] ?: 'This discount code is not valid.';
                    }
                } else {
                    $errors['discount_code'] = 'Unable to validate discount code. Please try again.';
                }
            }

            // Validate name
            $name_validation = $this->validator->validateName($name);
            if (!$name_validation['valid']) {
                $errors['name'] = $name_validation['error'];
            }

            // Validate email
            $email_validation = $this->validator->validateEmail($email, true);
            if (!$email_validation['valid']) {
                $errors['email'] = $email_validation['error'];
            }

            // Validate phone
            $phone_validation = $this->validator->validateUkPhone($phone);
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
                'ip' => $bot_check['ip'] ?? '',
                'page_url' => $_POST['page_url'] ?? ($course_url ?: home_url('/')),
            ];
            $saved = $this->repository->create($submission_data, 'course-booking', false, '');
            
            // Track WordPress booking for Eventbrite sync
            if (!is_wp_error($saved) && $course_id > 0) {
                $post_type = get_post_type($course_id);
                if ($post_type === 'course_event' && function_exists('ccs_track_wordpress_booking')) {
                    ccs_track_wordpress_booking($course_id, $delegates);
                }
            }
            
            // Trigger Facebook Conversions API tracking
            if (!is_wp_error($saved)) {
                do_action('ccs_course_booking_saved', $saved, $submission_data);
            }

            // Send email
            $this->sendEmail($name, $email, $phone, $delegates, $course_title, $event_date, $course_url, $message, $discount_code, $discount_validation, $saved);

            // If marketing consent given, add to newsletter (only if not already subscribed)
            if ($marketing_consent && !empty($email)) {
                $name_parts = explode(' ', trim($name), 2);
                $first_name = $name_parts[0] ?? '';
                $last_name = $name_parts[1] ?? '';
                $ip = $bot_check['ip'] ?? '';
                if (function_exists('ccs_add_newsletter_subscriber')) {
                    ccs_add_newsletter_subscriber($email, $ip, $first_name, $last_name);
                }
            }

            // If submission was saved successfully, show success even if email failed
            if (!is_wp_error($saved)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    // Email status logged in sendEmail method
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

        } catch (\Exception $e) {
            error_log('CTA Course Booking: Exception - ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'An error occurred while processing your request. Please try again or contact us directly.',
                'code' => 'server_error'
            ], 500);
        }
    }
    
    /**
     * Send course booking email
     *
     * @param string $name Contact name
     * @param string $email Contact email
     * @param string $phone Contact phone
     * @param int $delegates Number of delegates
     * @param string $course_title Course title
     * @param string $event_date Event date if provided
     * @param string $course_url Course URL if available
     * @param string $message Additional message
     * @param string $discount_code Discount code if provided
     * @param array $discount_validation Discount validation result
     * @param int|\WP_Error $saved Submission post ID
     * @return void
     */
    private function sendEmail(string $name, string $email, string $phone, int $delegates, string $course_title, string $event_date, string $course_url, string $message, string $discount_code, array $discount_validation, $saved): void {
        if (!defined('CCS_EMAIL_OFFICE')) {
            return;
        }

        $to = CCS_EMAIL_OFFICE;
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
        
        // Get IP from helper function if available
        $ip = '';
        if (function_exists('ccs_get_client_ip')) {
            $ip = ccs_get_client_ip();
        }
        if ($ip) {
            $body .= "IP: {$ip}\n\n";
        }
        
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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($sent) {
                error_log('CTA Course Booking: Successfully sent email');
            } else {
                error_log('CTA Course Booking: Email failed but submission saved (ID: ' . $saved . ')');
            }
        }
    }
}
