<?php
/**
 * Form Validator Service
 *
 * Centralized validation logic for all forms
 *
 * @package CTA\Services
 */

namespace CTA\Services;

class FormValidator {
    
    /**
     * Validate UK phone number
     *
     * @param string $phone Phone number to validate
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateUkPhone(string $phone): array {
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
        
        // Pattern matching for UK numbers (using digits_only to ensure clean validation)
        // Mobile: 07xxx xxxxxx (11 digits starting with 07)
        // Landline: 01xxx xxxxxx (10 digits) or 02x xxxx xxxx (10-11 digits)
        // Non-geographic: 03xx, 05xx, 08xx, 09xx (10-11 digits)
        $pattern = '/^0[1-9]\d{8,9}$/';
        
        if (!preg_match($pattern, $digits_only)) {
            return ['valid' => false, 'error' => 'Please enter a valid UK phone number format'];
        }
        
        // Check for repeating digits (e.g., 0000000000, 1111111111)
        if (preg_match('/^0(\d)\1{8,9}$/', $digits_only)) {
            return ['valid' => false, 'error' => 'Please enter a valid phone number'];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Validate name field
     *
     * @param string $name Name to validate
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateName(string $name): array {
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
        
        // All numbers
        if (preg_match('/^\d+$/', $trimmed)) {
            return ['valid' => false, 'error' => 'Please enter a valid name'];
        }
        
        // Too many special characters
        $special_char_count = preg_match_all('/[^a-zA-Z0-9\s\-\']/', $trimmed);
        if ($special_char_count > strlen($trimmed) * 0.3) {
            return ['valid' => false, 'error' => 'Please enter a valid name'];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Validate email address
     *
     * @param string $email Email to validate
     * @param bool $required Whether email is required
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateEmail(string $email, bool $required = true): array {
        if (empty($email)) {
            if ($required) {
                return ['valid' => false, 'error' => 'Email address is required'];
            }
            return ['valid' => true, 'error' => null];
        }
        
        if (!is_email($email)) {
            return ['valid' => false, 'error' => 'Please enter a valid email address'];
        }
        
        if (strlen($email) > 254) {
            return ['valid' => false, 'error' => 'Email address is too long'];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Validate anti-bot fields (honeypot + timing)
     *
     * @param string $formType Form identifier
     * @return array|false Bot check result or false if bot detected
     */
    public function validateAntiBot(string $formType) {
        // Honeypot check
        $honeypot_field = $_POST['website'] ?? '';
        if (!empty($honeypot_field)) {
            return false;
        }
        
        // Form timing check
        $form_loaded_at = absint($_POST['form_loaded_at'] ?? 0);
        $submission_time = time();
        $form_load_time = $form_loaded_at;
        
        if ($form_load_time > 0) {
            $time_taken = $submission_time - $form_load_time;
            
            // Too fast (< 3 seconds)
            if ($time_taken < 3) {
                return false;
            }
            
            // Suspiciously long (> 1 hour)
            if ($time_taken > 3600) {
                return false;
            }
        }
        
        // Get metadata
        $ip = $this->getClientIp();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        
        return [
            'ip' => $ip,
            'user_agent' => $user_agent,
            'referrer' => $referrer,
            'time_taken' => $form_load_time > 0 ? ($submission_time - $form_load_time) : 0,
        ];
    }
    
    /**
     * Get client IP address
     *
     * @return string Client IP
     */
    private function getClientIp(): string {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ip_list[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '0.0.0.0';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
