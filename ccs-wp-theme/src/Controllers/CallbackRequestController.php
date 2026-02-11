<?php
/**
 * Callback Request Controller
 *
 * Handles callback request form submissions with validation and email delivery
 *
 * @package CTA\Controllers
 */

namespace CCS\Controllers;

use CCS\Services\FormValidator;
use CCS\Repositories\FormSubmissionRepository;

class CallbackRequestController {
    
    /** @var FormValidator */
    private $validator;
    
    /** @var FormSubmissionRepository */
    private $repository;
    
    public function __construct(?FormValidator $validator = null, ?FormSubmissionRepository $repository = null) {
        $this->validator = $validator ?? new FormValidator();
        $this->repository = $repository ?? new FormSubmissionRepository();
    }
    
    /**
     * Handle callback request form submission
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
            $bot_check = $this->validator->validateAntiBot('callback-request');
            if ($bot_check === false) {
                // Bot detected - silently accept but don't process
                wp_send_json_success([
                    'message' => 'Thank you! We will call you back.',
                ]);
            }

            // Sanitize inputs
            $name = sanitize_text_field($_POST['name'] ?? '');
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $email = sanitize_email($_POST['email'] ?? '');
            $consent = isset($_POST['consent']) && ($_POST['consent'] === 'on' || $_POST['consent'] === 'true');
            $marketing_consent = isset($_POST['marketingConsent']) && ($_POST['marketingConsent'] === 'on' || $_POST['marketingConsent'] === 'true');

            // Validation
            $errors = [];

            // Validate name
            $name_validation = $this->validator->validateName($name);
            if (!$name_validation['valid']) {
                $errors['name'] = $name_validation['error'];
            }

            // Validate phone (required for callback)
            $phone_validation = $this->validator->validateUkPhone($phone);
            if (!$phone_validation['valid']) {
                $errors['phone'] = $phone_validation['error'];
            }

            // Validate email (optional for callback)
            if (!empty($email)) {
                $email_validation = $this->validator->validateEmail($email, false);
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
                'ip' => $bot_check['ip'] ?? '',
                'page_url' => $_POST['page_url'] ?? home_url('/'),
            ];
            $saved = $this->repository->create($submission_data, 'callback-request', false, '');

            // Send email
            $this->sendEmail($name, $phone, $email, $saved);

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
                // Log successful saves (ID only for privacy)
                error_log('CTA Callback Request: Submission saved (ID: ' . $saved . ')');
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    // Email status logged in sendEmail method
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

        } catch (\Exception $e) {
            error_log('CTA Callback Request: Exception - ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'An error occurred while processing your request. Please try again or contact us directly.',
                'code' => 'server_error'
            ], 500);
        }
    }
    
    /**
     * Send callback request email
     *
     * @param string $name Contact name
     * @param string $phone Contact phone
     * @param string $email Contact email (optional)
     * @param int|\WP_Error $saved Submission post ID
     * @return void
     */
    private function sendEmail(string $name, string $phone, string $email, $saved): void {
        if (!defined('CCS_EMAIL_OFFICE')) {
            return;
        }

        $to = CCS_EMAIL_OFFICE;
        $subject = sprintf('[CCS Callback Request] From %s', $name);
        
        $body = "New callback request:\n\n";
        $body .= "Name: {$name}\n";
        $body .= "Phone: {$phone}\n";
        if (!empty($email)) {
            $body .= "Email: {$email}\n";
        }
        $body .= "\n---\n";
        $body .= "Submitted: " . current_time('mysql') . "\n";
        
        // Get IP from helper function if available
        $ip = '';
        if (function_exists('ccs_get_client_ip')) {
            $ip = ccs_get_client_ip();
        }
        if ($ip) {
            $body .= "IP: {$ip}\n";
        }
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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($sent) {
                error_log('CTA Callback Request: Email successfully sent');
            } else {
                error_log('CTA Callback Request: Email failed but submission saved (ID: ' . $saved . ')');
            }
        }
    }
}
