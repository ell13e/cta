<?php
/**
 * Theme Setup
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Theme setup
 */
function cta_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    add_theme_support('custom-logo', [
        'height' => 50,
        'width' => 200,
        'flex-height' => true,
        'flex-width' => true,
    ]);
    add_theme_support('editor-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');
    
    // Add editor styles so content looks the same in editor as on front-end
    add_editor_style('assets/css/editor-style.css');

    // Register navigation menus
    register_nav_menus([
        'primary' => __('Primary Navigation', 'cta-theme'),
        'resources' => __('Resources Dropdown', 'cta-theme'),
        'footer-company' => __('Footer Company Links', 'cta-theme'),
        'footer-help' => __('Footer Help Links', 'cta-theme'),
    ]);

    // Set content width
    $GLOBALS['content_width'] = 1280;
}
add_action('after_setup_theme', 'cta_theme_setup');

/**
 * Add custom image sizes
 */
function cta_add_image_sizes() {
    add_image_size('course-thumbnail', 400, 300, true);
    add_image_size('course-card', 600, 400, true);
    add_image_size('hero-image', 1200, 600, true);
    add_image_size('team-member', 400, 400, true);
}
add_action('after_setup_theme', 'cta_add_image_sizes');

/**
 * Disable Gutenberg for specific post types (keep admin simple)
 */
function cta_disable_gutenberg($use_block_editor, $post_type) {
    // Use classic editor for posts, courses and events (ACF handles the content)
    if (in_array($post_type, ['post', 'course', 'course_event'])) {
        return false;
    }
    return $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'cta_disable_gutenberg', 10, 2);

// Head cleanup is handled in inc/seo.php - cta_cleanup_head()

/**
 * Add body classes
 */
function cta_body_classes($classes) {
    // Add page-specific classes
    if (is_front_page()) {
        $classes[] = 'page-home';
    }
    
    if (is_singular('course')) {
        $classes[] = 'page-course-detail';
    }
    
    if (is_post_type_archive('course')) {
        $classes[] = 'page-courses';
    }

    return $classes;
}
add_filter('body_class', 'cta_body_classes');

/**
 * Customize excerpt length
 */
function cta_excerpt_length($length) {
    return 25;
}
add_filter('excerpt_length', 'cta_excerpt_length');

/**
 * Customize excerpt more text
 */
function cta_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'cta_excerpt_more');

/**
 * Optimize TinyMCE editor for better UX
 * Remove H1 (post title is already H1), improve toolbar, add helpful guidance
 */
function cta_customize_tinymce($init) {
    global $post_type;
    
    // Remove H1 from format dropdown (post title is already H1)
    // Keep H2-H6 for content structure
    // Apply to all editors (main content and ACF fields)
    $init['block_formats'] = 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Preformatted=pre';
    
    // Better paste handling
    $init['paste_as_text'] = true;
    $init['paste_auto_cleanup_on_paste'] = true;
    $init['paste_remove_styles'] = true;
    $init['paste_remove_spans'] = true;
    $init['paste_strip_class_attributes'] = 'all';
    
    // Better word wrapping
    $init['wordpress_adv_hidden'] = false;
    $init['wpautop'] = true;
    $init['indent'] = true;
    
    return $init;
}
add_filter('tiny_mce_before_init', 'cta_customize_tinymce');

/**
 * Add helpful guidance above the editor
 */
function cta_add_editor_help_text($post) {
    if (!$post) {
        return;
    }
    
    // Show different help text for articles vs FAQs
    if ($post->post_type === 'post') {
        ?>
        <div class="cta-editor-help" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px 16px; margin: 10px 0 15px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 13px; color: #1d2327; line-height: 1.6;">
                <strong>💡 Writing Tips:</strong> Use <strong>H2</strong> for main sections, <strong>H3</strong> for subsections. 
                The post title above is already your H1, so H1 is not available in the editor. 
                Keep paragraphs short (2-3 sentences) for better readability.
            </p>
        </div>
        <?php
    } elseif ($post->post_type === 'faq') {
        ?>
        <div class="cta-editor-help" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px 16px; margin: 10px 0 15px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 13px; color: #1d2327; line-height: 1.6;">
                <strong>💡 FAQ Writing Tips:</strong> The title above is your question. Use the <strong>Answer</strong> field below to write a clear, helpful response. 
                Keep answers concise and focused. Use formatting tools to add emphasis, lists, or links when helpful.
            </p>
        </div>
        <?php
    }
}
add_action('edit_form_after_title', 'cta_add_editor_help_text');

/**
 * Remove unnecessary meta boxes to reduce clutter
 */
function cta_remove_unnecessary_meta_boxes() {
    // Remove comments meta box for posts (if not using comments)
    remove_meta_box('commentstatusdiv', 'post', 'normal');
    remove_meta_box('commentsdiv', 'post', 'normal');
    remove_meta_box('trackbacksdiv', 'post', 'normal');
    remove_meta_box('slugdiv', 'post', 'normal');
    
    // Remove unnecessary meta boxes for FAQs (same as articles)
    remove_meta_box('commentstatusdiv', 'faq', 'normal');
    remove_meta_box('commentsdiv', 'faq', 'normal');
    remove_meta_box('trackbacksdiv', 'faq', 'normal');
    remove_meta_box('slugdiv', 'faq', 'normal');
}
add_action('admin_menu', 'cta_remove_unnecessary_meta_boxes');

