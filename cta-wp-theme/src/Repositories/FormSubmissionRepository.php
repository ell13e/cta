<?php
/**
 * Form Submission Repository
 *
 * Handles database operations for form submissions
 *
 * @package CTA\Repositories
 */

namespace CTA\Repositories;

class FormSubmissionRepository {
    
    /**
     * Create a new form submission
     *
     * @param array $data Submission data
     * @param string $form_type Form type slug
     * @param bool $email_sent Whether email was sent
     * @param string $email_error Email error message if any
     * @return int|\WP_Error Post ID on success, WP_Error on failure
     */
    public function create(array $data, string $form_type, bool $email_sent = false, string $email_error = ''): int {
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
            if (function_exists('cta_add_newsletter_subscriber')) {
                $ip = $data['ip'] ?? '';
                if (empty($ip) && function_exists('cta_get_client_ip')) {
                    $ip = cta_get_client_ip();
                }
                $newsletter_result = cta_add_newsletter_subscriber($email, $ip, $first_name, $last_name);
                
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
        if (function_exists('cta_track_email_failures')) {
            cta_track_email_failures($email_sent);
        }
        
        // Save additional metadata
        if (isset($data['ip'])) {
            update_post_meta($post_id, '_submission_ip', sanitize_text_field($data['ip']));
        } else {
            if (function_exists('cta_get_client_ip')) {
                update_post_meta($post_id, '_submission_ip', cta_get_client_ip());
            }
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
        
        // Save delegates count if provided
        if (isset($data['delegates']) && is_numeric($data['delegates'])) {
            update_post_meta($post_id, '_submission_delegates', absint($data['delegates']));
        }
        
        // Save Meta Lead ID if provided (for Facebook Conversion Leads Integration)
        if (isset($data['meta_lead_id']) && preg_match('/^\d{15,17}$/', $data['meta_lead_id'])) {
            update_post_meta($post_id, '_submission_meta_lead_id', sanitize_text_field($data['meta_lead_id']));
        }
        
        // Save any additional form data (exclude fields that are stored separately)
        $form_data = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, ['name', 'email', 'phone', 'message', 'consent', 'nonce', 'action', 'ip', 'user_agent', 'page_url', 'course_id', 'course_name', 'event_date', 'delegates', 'meta_lead_id'])) {
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
     * Find submission by ID
     *
     * @param int $id Post ID
     * @return array|null Submission data or null if not found
     */
    public function findById(int $id): ?array {
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'form_submission') {
            return null;
        }
        
        // Get form type taxonomy
        $terms = get_the_terms($id, 'form_type');
        $form_type = '';
        if ($terms && !is_wp_error($terms) && !empty($terms)) {
            $form_type = $terms[0]->slug;
        }
        
        // Get all meta fields
        $meta = get_post_meta($id);
        
        // Build structured array
        $submission = [
            'id' => $id,
            'title' => $post->post_title,
            'form_type' => $form_type,
            'name' => $meta['_submission_name'][0] ?? '',
            'email' => $meta['_submission_email'][0] ?? '',
            'phone' => $meta['_submission_phone'][0] ?? '',
            'message' => $meta['_submission_message'][0] ?? '',
            'consent' => $meta['_submission_consent'][0] ?? 'no',
            'marketing_consent' => $meta['_submission_marketing_consent'][0] ?? 'no',
            'email_sent' => $meta['_submission_email_sent'][0] ?? 'no',
            'email_error' => $meta['_submission_email_error'][0] ?? '',
            'ip' => $meta['_submission_ip'][0] ?? '',
            'user_agent' => $meta['_submission_user_agent'][0] ?? '',
            'page_url' => $meta['_submission_page_url'][0] ?? '',
            'course_id' => $meta['_submission_course_id'][0] ?? null,
            'course_name' => $meta['_submission_course_name'][0] ?? '',
            'event_date' => $meta['_submission_event_date'][0] ?? '',
            'form_data' => $meta['_submission_form_data'][0] ?? [],
            'followup_status' => $meta['_submission_followup_status'][0] ?? 'new',
            'newsletter_status' => $meta['_submission_newsletter_status'][0] ?? '',
            'created_at' => $post->post_date,
        ];
        
        // Unserialize form_data if it's serialized
        if (is_string($submission['form_data']) && is_serialized($submission['form_data'])) {
            $submission['form_data'] = unserialize($submission['form_data']);
        }
        
        return $submission;
    }
    
    /**
     * Find submissions by email
     *
     * @param string $email Email address
     * @param int $limit Maximum number of results
     * @return array Array of submission post IDs
     */
    public function findByEmail(string $email, int $limit = 10): array {
        $args = [
            'post_type' => 'form_submission',
            'posts_per_page' => $limit,
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => '_submission_email',
                    'value' => $email,
                    'compare' => '=',
                ],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ];
        
        $query = new \WP_Query($args);
        
        return $query->posts ?? [];
    }
}
