<?php
/**
 * ACF (Advanced Custom Fields) Stubs for Intelephense
 * This file provides type hints for ACF functions to prevent false warnings
 * 
 * @package CTA_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get a field value from ACF
 * 
 * @param string $selector Field name or key
 * @param int|string|false $post_id Optional. Post ID
 * @param bool $format_value Optional. Whether to format the value
 * @return mixed Field value
 */
function get_field($selector, $post_id = false, $format_value = true) {}

/**
 * Get a field object from ACF
 * 
 * @param string $selector Field name or key
 * @param int|string|false $post_id Optional. Post ID
 * @param bool $format_value Optional. Whether to format the value
 * @param bool $load_value Optional. Whether to load the value
 * @return array|false Field object or false
 */
function get_field_object($selector, $post_id = false, $format_value = true, $load_value = true) {}

/**
 * Update a field value in ACF
 * 
 * @param string $selector Field name or key
 * @param mixed $value The value to save
 * @param int|string $post_id Optional. Post ID
 * @return bool
 */
function update_field($selector, $value, $post_id = false) {}

/**
 * Check if a field exists
 * 
 * @param string $selector Field name or key
 * @param int|string|false $post_id Optional. Post ID
 * @return bool
 */
function have_rows($selector, $post_id = false) {}

/**
 * Loop through repeater field rows
 * 
 * @param string $selector Field name or key
 * @param int|string|false $post_id Optional. Post ID
 * @return bool
 */
function the_row($selector = '', $post_id = false) {}

/**
 * Get sub field value from repeater
 * 
 * @param string $selector Field name or key
 * @param bool $format_value Optional. Whether to format the value
 * @return mixed
 */
function get_sub_field($selector, $format_value = true) {}
