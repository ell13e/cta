<?php
/**
 * PHPUnit Bootstrap
 *
 * Loads WordPress test environment
 *
 * @package ccs-theme
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// WordPress tests bootstrap (if using WordPress test suite)
// Uncomment when WordPress test environment is set up
// $_tests_dir = getenv('WP_TESTS_DIR');
// if (!$_tests_dir) {
//     $_tests_dir = '/tmp/wordpress-tests-lib';
// }
// require_once $_tests_dir . '/includes/functions.php';
// require_once $_tests_dir . '/includes/bootstrap.php';
