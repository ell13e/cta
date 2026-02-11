<?php
/**
 * Group Booking Controller
 *
 * Handles group training booking form submissions with validation and email delivery
 *
 * @package CTA\Controllers
 */

namespace CCS\Controllers;

use CCS\Services\FormValidator;
use CCS\Repositories\FormSubmissionRepository;

class GroupBookingController {
    
    /** @var FormValidator */
    private $validator;
    
    /** @var FormSubmissionRepository */
    private $repository;
    
    public function __construct(?FormValidator $validator = null, ?FormSubmissionRepository $repository = null) {
        $this->validator = $validator ?? new FormValidator();
        $this->repository = $repository ?? new FormSubmissionRepository();
    }
    
    /**
     * Handle group booking form submission
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
            $bot_check = $this->validator->validateAntiBot('group-booking');
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

            // Check if email constant is defined
            if (!defined('CCS_EMAIL_OFFICE') || empty(CCS_EMAIL_OFFICE)) {
                error_log('CTA Group Booking: CCS_EMAIL_OFFICE constant not defined');
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
                'ip' => $bot_check['ip'] ?? '',
                'page_url' => $_POST['page_url'] ?? home_url('/group-training/'),
            ];
            $saved = $this->repository->create($submission_data, 'group-booking', false, '');

            // Send email
            $this->sendEmail($name, $email, $phone, $organisation, $number_of_staff, $training_label, $details, $discount_code, $discount_validation, $saved);

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

        } catch (\Exception $e) {
            error_log('CTA Group Booking: Exception - ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'An error occurred while processing your request. Please try again or contact us directly.',
                'code' => 'server_error'
            ], 500);
        }
    }
    
    /**
     * Send group booking email
     *
     * @param string $name Contact name
     * @param string $email Contact email
     * @param string $phone Contact phone
     * @param string $organisation Organisation name
     * @param int $number_of_staff Number of staff
     * @param string $training_label Training type label
     * @param string $details Additional details
     * @param string $discount_code Discount code if provided
     * @param array $discount_validation Discount validation result
     * @param int|\WP_Error $saved Submission post ID
     * @return void
     */
    private function sendEmail(string $name, string $email, string $phone, string $organisation, int $number_of_staff, string $training_label, string $details, string $discount_code, array $discount_validation, $saved): void {
        if (!defined('CCS_EMAIL_OFFICE')) {
            return;
        }

        $to = CCS_EMAIL_OFFICE;
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
        
        // Get IP from helper function if available, otherwise use empty string
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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($sent) {
                error_log('CTA Group Booking: Successfully sent email');
            } else {
                error_log('CTA Group Booking: Email failed but submission saved (ID: ' . $saved . ')');
            }
        }
    }
}
