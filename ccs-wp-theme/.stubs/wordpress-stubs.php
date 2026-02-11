<?php
/**
 * WordPress Constants and Superglobals Stubs for Intelephense
 * This file provides definitions for WordPress constants and PHP superglobals
 * 
 * @package CTA_Theme
 */

// Prevent direct access (but don't exit in stub files)
defined('ABSPATH') || define('ABSPATH', __DIR__);

// PHP Superglobals (prevent Intelephense warnings)
/** @var array<string, mixed> */
$_POST = [];

/** @var array<string, mixed> */
$_GET = [];

/** @var array<string, mixed> */
$_SERVER = [];

/** @var array<string, mixed> */
$_REQUEST = [];

/** @var array<string, mixed> */
$_COOKIE = [];

/** @var array<string, mixed> */
$_FILES = [];

/** @var array<string, mixed> */
$_SESSION = [];

/** @var array<string, mixed> */
$GLOBALS = [];

/**
 * WordPress CLI constant
 * Set to true when running WP-CLI commands
 * @var bool
 */
define('WP_CLI', false);

/**
 * AJAX request constant
 * Set to true during AJAX requests
 * @var bool
 */
define('DOING_AJAX', false);

/**
 * Cron request constant
 * Set to true during cron requests
 * @var bool
 */
define('DOING_CRON', false);

/**
 * Admin area constant
 * Set to true in admin area
 * @var bool
 */
define('WP_ADMIN', false);
