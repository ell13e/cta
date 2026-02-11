<?php
/**
 * Serve a “Coming soon” page for unpublished pages (draft/pending/future) to logged-out visitors.
 *
 * @package ccs-theme
 */
defined('ABSPATH') || exit;

function ccs_maybe_show_coming_soon_for_unpublished_pages() {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }
    if (is_user_logged_in()) {
        return;
    }
    if (!is_404()) {
        return;
    }

    global $wp;
    $request = isset($wp->request) ? (string) $wp->request : '';
    $slug = trim($request, '/');
    if ($slug === '') {
        return;
    }

    $candidates = get_posts([
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => ['draft', 'pending', 'future'],
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);

    if (empty($candidates)) {
        return;
    }

    // Serve “coming soon” with 503 to avoid indexing unfinished pages.
    status_header(503);
    header('Retry-After: 86400'); // 1 day
    nocache_headers();

    // Ensure WordPress doesn't keep thinking this is a 404.
    global $wp_query;
    if ($wp_query) {
        $wp_query->is_404 = false;
    }

    include get_template_directory() . '/coming-soon.php';
    exit;
}
add_action('template_redirect', 'ccs_maybe_show_coming_soon_for_unpublished_pages', 0);

