<?php
/**
 * Cache Helper Functions
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Get or set cache value.
 *
 * Provides a consistent caching interface across the theme.
 *
 * @param string          $key        Cache key.
 * @param callable|mixed  $callback   Callback to generate value (or static value).
 * @param int             $expiration Expiration in seconds.
 * @param string          $group      Cache group.
 *
 * @return mixed
 */
function ccs_cache_get_or_set($key, $callback, $expiration = 3600, $group = '')
{
    $value = wp_cache_get($key, $group);

    if (false === $value) {
        $value = is_callable($callback) ? call_user_func($callback) : $callback;
        wp_cache_set($key, $value, $group, $expiration);
    }

    return $value;
}

