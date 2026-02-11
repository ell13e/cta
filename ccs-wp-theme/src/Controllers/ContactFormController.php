<?php
/**
 * Contact Form Controller
 *
 * Handles contact form submissions with validation and email delivery
 *
 * @package CTA\Controllers
 */

namespace CCS\Controllers;

use CCS\Services\FormValidator;
use CCS\Repositories\FormSubmissionRepository;

class ContactFormController {
    
    /** @var FormValidator */
    private $validator;
    
    /** @var FormSubmissionRepository */
    private $repository;
    
    public function __construct(?FormValidator $validator = null, ?FormSubmissionRepository $repository = null) {
        $this->validator = $validator ?? new FormValidator();
        $this->repository = $repository ?? new FormSubmissionRepository();
    }
    
    /**
     * Handle contact form submission
     *
     * @return void Sends JSON response and exits
     */
    public function handle(): void {
        try {
            // Nonce verification
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccs_nonce')) {
                wp_send_json_error([
                    'message' => 'Security verification failed. Please refresh the page and try again.',
                    'code' => 'nonce_failed'
                ], 403);
            }

            // Anti-bot validation
            $bot_check = $this->validator->validateAntiBot('contact');
            if ($bot_check === false) {
                // Bot detected - silently accept
                wp_send_json_success([
                    'message' => 'Thank you for your message. We will be in touch soon.',
                ]);
            }

            // Sanitize inputs
            $name = sanitize_text_field($_POST['name'] ?? '');
            $email = sanitize_email($_POST['email'] ?? '');
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $enquiry_type = sanitize_text_field($_POST['enquiryType'] ?? 'general');
            $message = sanitize_textarea_field($_POST['message'] ?? '');
            $discount_code = sanitize_text_field($_POST['discount_code'] ?? '');
            $consent = isset($_POST['consent']) && $_POST['consent'] === 'true';
            $marketing_consent = isset($_POST['marketingConsent']) && $_POST['marketingConsent'] === 'true';

            // Validation
            $errors = [];

            // Validate discount code if provided (for course enquiries)
            $discount_validation = ['valid' => false, 'message' => '', 'discount' => 0];
            $is_course_enquiry = in_array($enquiry_type, ['book-course', 'cqc-training']);
            
            if ($is_course_enquiry && !empty($discount_code)) {
                if (function_exists('ccs_validate_discount_code')) {
                    $discount_validation = ccs_validate_discount_code($discount_code);
                    if (!$discount_validation['valid']) {
                        $errors['discount_code'] = $discount_validation['message'] ?: 'This discount code is not valid.';
                    }
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

            // Validate message
            if (empty(trim($message))) {
                $errors['message'] = 'Please enter your message';
            } elseif (strlen(trim($message)) < 10) {
                $errors['message'] = 'Please provide more details (at least 10 characters)';
            }

            // Validate enquiry type
            $valid_types = ['training-consultation', 'group-training', 'book-course', 'cqc-training', 'support', 'general'];
            if (!in_array($enquiry_type, $valid_types)) {
                $enquiry_type = 'general';
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

            // Save submission
            $submission_data = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'consent' => $consent ? 'yes' : 'no',
                'marketing_consent' => $marketing_consent ? 'yes' : 'no',
                'enquiry_type' => $enquiry_type,
                'ip' => $bot_check['ip'],
                'page_url' => $_POST['page_url'] ?? home_url('/contact/'),
            ];
            
            if ($is_course_enquiry && !empty($discount_code)) {
                $submission_data['discount_code'] = $discount_code;
                $submission_data['discount_valid'] = $discount_validation['valid'] ? 'yes' : 'no';
                $submission_data['discount_message'] = $discount_validation['message'];
                $submission_data['discount_percent'] = $discount_validation['discount'];
            }
            
            // Add selected courses if provided
            if (isset($_POST['selectedCourses']) && is_array($_POST['selectedCourses'])) {
                $selected_courses = array_filter(array_map('absint', $_POST['selectedCourses']));
                if (!empty($selected_courses)) {
                    $submission_data['selected_courses'] = $selected_courses;
                }
            }
            
            $saved = $this->repository->create($submission_data, $enquiry_type, false, '');

            // Send email
            $this->sendEmail($name, $email, $phone, $enquiry_type, $message, $discount_code, $discount_validation, $saved, $submission_data);

            // Add to newsletter if consent given
            if ($marketing_consent && !empty($email)) {
                $name_parts = explode(' ', trim($name), 2);
                ccs_add_newsletter_subscriber($email, $bot_check['ip'], $name_parts[0] ?? '', $name_parts[1] ?? '');
            }

            // Success response
            $enquiry_labels = [
                'training-consultation' => 'Thank you! We will be in touch to arrange your free training consultation.',
                'group-training' => 'Thank you! Our group training team will be in touch.',
                'book-course' => 'Thank you! We will confirm your booking enquiry.',
                'cqc-training' => 'Thank you! Our CQC training specialist will contact you.',
                'support' => 'Thank you! Our support team will respond.',
                'general' => 'Thank you for your message. We will be in touch.',
            ];

            wp_send_json_success([
                'message' => $enquiry_labels[$enquiry_type] ?? $enquiry_labels['general'],
                'enquiry_type' => $enquiry_type,
            ]);

        } catch (\Exception $e) {
            error_log('CTA Contact Form Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'An error occurred while processing your request. Please try again or contact us directly.',
                'code' => 'server_error'
            ], 500);
        }
    }
    
    /**
     * Send contact form email
     *
     * @param string $name Sender name
     * @param string $email Sender email
     * @param string $phone Sender phone
     * @param string $enquiry_type Type of enquiry
     * @param string $message Message content
     * @param string $discount_code Discount code if provided
     * @param array $discount_validation Discount validation result
     * @param int|\WP_Error $saved Submission post ID
     * @param array $submission_data Full submission data
     * @return void
     */
    private function sendEmail(string $name, string $email, string $phone, string $enquiry_type, string $message, string $discount_code, array $discount_validation, $saved, array $submission_data): void {
        if (!defined('CCS_EMAIL_OFFICE')) {
            error_log('CCS Contact Form: CCS_EMAIL_OFFICE not defined');
            return;
        }

        $enquiry_labels = [
            'training-consultation' => 'Training Consultation',
            'group-training' => 'Group Training',
            'book-course' => 'Course Booking',
            'cqc-training' => 'CQC Training',
            'support' => 'Support',
            'general' => 'General Enquiry',
        ];
        $enquiry_label = $enquiry_labels[$enquiry_type] ?? 'General Enquiry';

        $to = CCS_EMAIL_OFFICE;
        $subject = sprintf('[CCS Contact] %s from %s', $enquiry_label, $name);
        
        $body = "New contact form submission:\n\n";
        $body .= "Name: {$name}\n";
        $body .= "Email: {$email}\n";
        $body .= "Phone: {$phone}\n";
        $body .= "Enquiry Type: {$enquiry_label}\n";
        
        // Add selected courses if any
        if (!empty($submission_data['selected_courses'])) {
            $course_titles = [];
            foreach ($submission_data['selected_courses'] as $course_id) {
                $course_title = get_the_title($course_id);
                if ($course_title && $course_title !== 'Auto Draft') {
                    $course_titles[] = $course_title;
                }
            }
            if (!empty($course_titles)) {
                $body .= "Selected Course(s): " . implode(', ', $course_titles) . "\n";
            }
        }
        
        if (!empty($discount_code)) {
            $body .= "\nDiscount Code: {$discount_code}\n";
            $body .= "Discount Status: {$discount_validation['message']}\n";
            if ($discount_validation['valid']) {
                $body .= "Discount Applied: {$discount_validation['discount']}% Off\n";
            }
        }
        
        $body .= "\nMessage:\n{$message}\n\n";
        $body .= "---\n";
        $body .= "Submitted: " . current_time('mysql') . "\n";
        $body .= "IP: " . ($submission_data['ip'] ?? '') . "\n";
        $body .= "Page: " . esc_url($_POST['page_url'] ?? home_url('/contact/')) . "\n\n";
        
        if (!is_wp_error($saved) && $saved) {
            $admin_link = admin_url('post.php?post=' . $saved . '&action=edit');
            $body .= "View in WordPress Admin:\n" . esc_url($admin_link) . "\n\n";
        }

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $name . ' <' . $email . '>',
        ];

        $sent = wp_mail($to, $subject, $body, $headers);
        
        // Update submission with email status
        if (!is_wp_error($saved) && $saved) {
            update_post_meta($saved, '_submission_email_sent', $sent ? 'yes' : 'no');
            if (!$sent) {
                update_post_meta($saved, '_submission_email_error', 'Email sending failed');
            }
        }
        
        if (!$sent) {
            error_log('CTA Contact Form: Failed to send email');
        }
    }
}
