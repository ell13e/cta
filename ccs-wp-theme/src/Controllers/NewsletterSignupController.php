<?php
/**
 * Newsletter Signup Controller
 *
 * Handles newsletter subscription form submissions from footer and other locations
 *
 * @package CTA\Controllers
 */

namespace CCS\Controllers;

use CCS\Services\FormValidator;
use CCS\Repositories\FormSubmissionRepository;

class NewsletterSignupController {
    
    /** @var FormValidator */
    private $validator;
    
    /** @var FormSubmissionRepository */
    private $repository;
    
    public function __construct(?FormValidator $validator = null, ?FormSubmissionRepository $repository = null) {
        $this->validator = $validator ?? new FormValidator();
        $this->repository = $repository ?? new FormSubmissionRepository();
    }
    
    /**
     * Handle newsletter signup form submission
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
            $bot_check = $this->validator->validateAntiBot('newsletter');
            if ($bot_check === false) {
                // Bot detected - silently accept but don't process
                wp_send_json_success([
                    'message' => 'Thank you for subscribing!',
                ]);
            }

            // Sanitize inputs
            $email = sanitize_email($_POST['email'] ?? '');
            $first_name = sanitize_text_field($_POST['first_name'] ?? '');
            $last_name = sanitize_text_field($_POST['last_name'] ?? '');
            $phone = sanitize_text_field($_POST['phone'] ?? '');
            $date_of_birth = sanitize_text_field($_POST['date_of_birth'] ?? '');
            $consent = isset($_POST['consent']) && $_POST['consent'] === 'true';

            // Validation
            $errors = [];

            // Validate email
            $email_validation = $this->validator->validateEmail($email, true);
            if (!$email_validation['valid']) {
                $errors['email'] = $email_validation['error'];
            }

            if (!$consent) {
                $errors['consent'] = 'Please confirm your consent to receiving updates.';
            }

            // Optional phone validation (only if provided)
            if (!empty($phone)) {
                $phone_validation = $this->validator->validateUkPhone($phone);
                if (!$phone_validation['valid']) {
                    $errors['phone'] = $phone_validation['error'];
                }
            }
            
            // Validate date of birth format if provided
            if (!empty($date_of_birth)) {
                $dob_timestamp = strtotime($date_of_birth);
                if ($dob_timestamp === false || $dob_timestamp > time()) {
                    $errors['date_of_birth'] = 'Please enter a valid date of birth.';
                }
            }

            if (!empty($errors)) {
                wp_send_json_error([
                    'message' => 'Please correct the errors below.',
                    'errors' => $errors,
                    'code' => 'validation_failed'
                ], 400);
            }

            // Add subscriber to newsletter
            $ip = $bot_check['ip'] ?? '';
            if (empty($ip) && function_exists('ccs_get_client_ip')) {
                $ip = ccs_get_client_ip();
            }
            
            if (!function_exists('ccs_add_newsletter_subscriber')) {
                wp_send_json_error([
                    'message' => 'Newsletter service is currently unavailable. Please try again later.',
                    'code' => 'service_unavailable'
                ], 500);
                return;
            }

            $result = ccs_add_newsletter_subscriber($email, $ip, $first_name, $last_name, $date_of_birth);
            
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
                'ip' => $ip,
                'page_url' => $_POST['page_url'] ?? home_url('/'),
                'first_name' => $first_name,
                'last_name' => $last_name,
                'date_of_birth' => $date_of_birth,
            ];
            
            $submission_id = $this->repository->create($submission_data, 'newsletter', false, '');
            
            // Link submission to subscriber record
            if (!is_wp_error($submission_id) && $submission_id) {
                // Get subscriber ID from database
                global $wpdb;
                $subscriber_table = $wpdb->prefix . 'ccs_newsletter_subscribers';
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

        } catch (\Exception $e) {
            error_log('CTA Newsletter Signup: Exception - ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'An error occurred while processing your subscription. Please try again or contact us directly.',
                'code' => 'server_error'
            ], 500);
        }
    }
}
