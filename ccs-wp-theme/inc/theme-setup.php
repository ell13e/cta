<?php
/**
 * Theme Setup
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Theme setup
 */
function ccs_theme_setup() {
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

    // Register navigation menus.
    // Only Resources is exposed in Appearance > Menus; header main nav and footer are hardcoded/fallback.
    register_nav_menus([
        'resources' => __('Resources Dropdown', 'ccs-theme'),
    ]);

    // Set content width
    $GLOBALS['content_width'] = 1280;
}
add_action('after_setup_theme', 'ccs_theme_setup');

/**
 * Add custom image sizes
 */
function ccs_add_image_sizes() {
    add_image_size('course-thumbnail', 400, 300, true);
    add_image_size('course-card', 600, 400, true);
    add_image_size('hero-image', 1200, 600, true);
    add_image_size('team-member', 400, 400, true);
}
add_action('after_setup_theme', 'ccs_add_image_sizes');

/**
 * Disable Gutenberg for specific post types (keep admin simple)
 */
function ccs_disable_gutenberg($use_block_editor, $post_type) {
    // Use classic editor for posts, courses, events, and FAQs
    if (in_array($post_type, ['post', 'course', 'course_event', 'care_service', 'faq'])) {
        return false;
    }
    return $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'ccs_disable_gutenberg', 10, 2);

// Head cleanup is handled in inc/seo.php - ccs_cleanup_head()

/**
 * Add body classes
 */
function ccs_body_classes($classes) {
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
add_filter('body_class', 'ccs_body_classes');

/**
 * Customize excerpt length
 */
function ccs_excerpt_length($length) {
    return 25;
}
add_filter('excerpt_length', 'ccs_excerpt_length');

/**
 * Customize excerpt more text
 */
function ccs_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'ccs_excerpt_more');

/**
 * Optimize TinyMCE editor for better UX
 * Remove H1 (post title is already H1), improve toolbar, add helpful guidance
 */
function ccs_customize_tinymce($init) {
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
add_filter('tiny_mce_before_init', 'ccs_customize_tinymce');

/**
 * Add helpful guidance above the editor
 */
function ccs_add_editor_help_text($post) {
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
                <strong>💡 FAQ:</strong> The <strong>title</strong> above is the question. Use the <strong>editor below</strong> (Visual or Text) for the answer. Keep answers concise; use the toolbar for lists, links, or emphasis.
            </p>
        </div>
        <?php
    }
}
add_action('edit_form_after_title', 'ccs_add_editor_help_text');

/**
 * Build the About page content from ACF so the main editor can show "the page" in one place.
 * Used to pre-fill the main editor when editing the About Us page (only when editor is empty).
 */
function ccs_build_about_page_editor_content($post_id) {
    if (!function_exists('get_field')) {
        return '';
    }
    $hero_title   = get_field('hero_title', $post_id) ?: 'About Our Care Training in Kent';
    $hero_subtitle = get_field('hero_subtitle', $post_id) ?: 'CQC-compliant, CPD-accredited care sector training in Kent since 2020';
    $mission_title = get_field('mission_title', $post_id) ?: 'Our Care Training Approach';
    $mission_text_raw = get_field('mission_text', $post_id);
    $values_title  = get_field('values_title', $post_id) ?: 'Core Care Training Values';
    $values_subtitle = get_field('values_subtitle', $post_id) ?: 'These principles guide everything we do and shape the experience we provide to our learners.';
    $values        = get_field('values', $post_id);
    $team_title    = get_field('team_title', $post_id) ?: 'Expert Care Training Team';
    $team_subtitle = get_field('team_subtitle', $post_id) ?: 'Experienced professionals dedicated to your development.';
    $ccs_title     = get_field('ccs_title', $post_id) ?: 'Start Your Care Training Today';
    $ccs_text      = get_field('ccs_text', $post_id) ?: 'Join hundreds of care professionals who trust us for their training needs. Get expert CQC compliance training with CPD-accredited certificates.';

    // Normalise mission_text: ACF repeater returns rows with 'paragraph' key; support that and legacy array-of-strings
    $mission_paragraphs = [];
    if (!empty($mission_text_raw) && is_array($mission_text_raw)) {
        foreach ($mission_text_raw as $row) {
            $mission_paragraphs[] = is_array($row) && isset($row['paragraph']) ? $row['paragraph'] : (is_string($row) ? $row : '');
        }
        $mission_paragraphs = array_filter($mission_paragraphs);
    }

    $out = '<h1>' . esc_html($hero_title) . "</h1>\n\n";
    $out .= '<p>' . esc_html($hero_subtitle) . "</p>\n\n";
    $out .= '<h2>' . esc_html($mission_title) . "</h2>\n\n";
    if (!empty($mission_paragraphs)) {
        foreach ($mission_paragraphs as $p) {
            $p = stripslashes((string) $p);
            if ($p !== '') {
                $out .= (strpos($p, '<') !== false ? wp_kses_post($p) : '<p>' . esc_html($p) . '</p>') . "\n\n";
            }
        }
    } elseif (is_string($mission_text_raw) && trim($mission_text_raw) !== '') {
        $out .= wp_kses_post(stripslashes($mission_text_raw)) . "\n\n";
    }
    $out .= '<h2>' . esc_html($values_title) . "</h2>\n\n";
    $out .= '<p>' . esc_html($values_subtitle) . "</p>\n\n";
    if (is_array($values)) {
        foreach ($values as $v) {
            $title = isset($v['title']) ? $v['title'] : '';
            $desc  = isset($v['description']) ? $v['description'] : '';
            if ($title || $desc) {
                if ($title) {
                    $out .= '<h3>' . esc_html($title) . "</h3>\n\n";
                }
                if ($desc) {
                    $out .= '<p>' . esc_html($desc) . "</p>\n\n";
                }
            }
        }
    }
    $out .= '<h2>' . esc_html($team_title) . "</h2>\n\n";
    $out .= '<p>' . esc_html($team_subtitle) . "</p>\n\n";
    $out .= '<h2>' . esc_html($ccs_title) . "</h2>\n\n";
    $out .= '<p>' . esc_html($ccs_text) . '</p>';
    return $out;
}

/**
 * Pre-fill the main editor for the About page with content from ACF (hero, mission, values, team, CTA)
 * so the big text box shows the page content in one place. Only when the editor is currently empty.
 */
function ccs_prefill_about_page_editor() {
    global $post;
    if (!$post || $post->post_type !== 'page') {
        return;
    }
    if (get_page_template_slug($post->ID) !== 'page-templates/page-about.php') {
        return;
    }
    $current = trim((string) $post->post_content);
    if ($current !== '') {
        return;
    }
    $content = ccs_build_about_page_editor_content($post->ID);
    if ($content !== '') {
        $post->post_content = $content;
    }
}
add_action('edit_form_after_title', 'ccs_prefill_about_page_editor', 1);

/**
 * Remove unnecessary meta boxes to reduce clutter
 */
function ccs_remove_unnecessary_meta_boxes() {
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
add_action('admin_menu', 'ccs_remove_unnecessary_meta_boxes');

/**
 * Sync FAQ block editor content to ACF faq_answer on save.
 * Ensures the ACF field stays in sync when editing in the main editor.
 */
function ccs_sync_faq_answer_from_editor($post_id) {
    if (get_post_type($post_id) !== 'faq') {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    $content = get_post_field('post_content', $post_id);
    if ($content !== '' && function_exists('update_field')) {
        // Store rendered HTML so ACF fallback displays correctly
        update_field('faq_answer', apply_filters('the_content', $content), $post_id);
    }
}
add_action('save_post_faq', 'ccs_sync_faq_answer_from_editor', 20);

