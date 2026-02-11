<?php
/**
 * Limit course categories to maximum 2 (primary and secondary)
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Limit course categories to maximum 2 terms
 * First term is primary, second is secondary
 */
function ccs_limit_course_categories($post_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
    // Only apply to course_category taxonomy on course post type
    if ($taxonomy !== 'course_category' || get_post_type($post_id) !== 'course') {
        return;
    }
    
    // Skip during autosave, revision, or bulk operations
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    // Get current terms
    $current_terms = wp_get_object_terms($post_id, 'course_category', ['fields' => 'tt_ids']);
    
    if (is_wp_error($current_terms)) {
        return;
    }
    
    // Limit to maximum 2 terms
    if (count($current_terms) > 2) {
        // Keep only the first 2 terms (primary and secondary)
        $limited_terms = array_slice($current_terms, 0, 2);
        
        // Remove action temporarily to avoid infinite loop
        remove_action('set_object_terms', 'ccs_limit_course_categories', 10, 6);
        
        // Set only the first 2 terms
        wp_set_object_terms($post_id, $limited_terms, 'course_category', false);
        
        // Re-add action
        add_action('set_object_terms', 'ccs_limit_course_categories', 10, 6);
        
        // Show admin notice if in admin
        if (is_admin()) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p>Course categories limited to 2 (primary and secondary). Only the first 2 categories were saved.</p></div>';
            });
        }
    }
}
add_action('set_object_terms', 'ccs_limit_course_categories', 10, 6);

/**
 * Get primary category for a course
 * 
 * @param int $course_id Course post ID
 * @return WP_Term|null Primary category term or null
 */
function ccs_get_primary_category($course_id) {
    $terms = get_the_terms($course_id, 'course_category');
    
    if (!$terms || is_wp_error($terms)) {
        return null;
    }
    
    // Limit to first term (primary)
    $terms = array_slice($terms, 0, 1);
    
    return !empty($terms) ? $terms[0] : null;
}

/**
 * Get secondary category for a course
 * 
 * @param int $course_id Course post ID
 * @return WP_Term|null Secondary category term or null
 */
function ccs_get_secondary_category($course_id) {
    $terms = get_the_terms($course_id, 'course_category');
    
    if (!$terms || is_wp_error($terms)) {
        return null;
    }
    
    // Get second term (secondary) if it exists
    if (count($terms) >= 2) {
        return $terms[1];
    }
    
    return null;
}

/**
 * Get all categories for a course (limited to 2)
 * 
 * @param int $course_id Course post ID
 * @return array Array of WP_Term objects (max 2)
 */
function ccs_get_course_category_terms($course_id) {
    $terms = get_the_terms($course_id, 'course_category');
    
    if (!$terms || is_wp_error($terms)) {
        return [];
    }
    
    // Return only first 2 terms
    return array_slice($terms, 0, 2);
}

/**
 * Add JavaScript to limit category selection in admin
 */
function ccs_limit_categories_admin_script() {
    global $post_type;
    
    if ($post_type !== 'course') {
        return;
    }
    
    ?>
    <script>
    (function($) {
        $(document).ready(function() {
            // Limit course category checkboxes to maximum 2
            var $categoryCheckboxes = $('#course_categorychecklist input[type="checkbox"]');
            var maxCategories = 2;
            
            $categoryCheckboxes.on('change', function() {
                var checked = $('#course_categorychecklist input[type="checkbox"]:checked').length;
                
                if (checked > maxCategories) {
                    $(this).prop('checked', false);
                    alert('Courses can have a maximum of 2 categories (primary and secondary). Please uncheck another category first.');
                    return false;
                }
            });
            
            // Also handle the category selector dropdown
            $('#course_category-adder').on('submit', function(e) {
                var checked = $('#course_categorychecklist input[type="checkbox"]:checked').length;
                var newCategory = $('#newcourse_category').val();
                
                if (newCategory && checked >= maxCategories) {
                    e.preventDefault();
                    alert('Courses can have a maximum of 2 categories (primary and secondary). Please remove a category first.');
                    return false;
                }
            });
        });
    })(jQuery);
    </script>
    <?php
}
add_action('admin_footer', 'ccs_limit_categories_admin_script');

/**
 * Clean up existing courses that have more than 2 categories
 * Keeps the first 2 categories and removes the rest
 * 
 * @return int Number of courses cleaned
 */
function ccs_cleanup_course_categories() {
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'any',
    ]);
    
    $cleaned = 0;
    
    // Temporarily remove the limit action to avoid conflicts
    remove_action('set_object_terms', 'ccs_limit_course_categories', 10, 6);
    
    foreach ($courses as $course) {
        $terms = get_the_terms($course->ID, 'course_category');
        
        if (!$terms || is_wp_error($terms)) {
            continue;
        }
        
        if (count($terms) > 2) {
            // Keep only first 2 (primary and secondary)
            $limited_terms = array_slice($terms, 0, 2);
            $term_ids = wp_list_pluck($limited_terms, 'term_id');
            
            wp_set_object_terms($course->ID, $term_ids, 'course_category', false);
            $cleaned++;
        }
    }
    
    // Re-add the limit action
    add_action('set_object_terms', 'ccs_limit_course_categories', 10, 6);
    
    return $cleaned;
}

/**
 * Add admin action to clean up course categories
 */
function ccs_add_category_cleanup_action() {
    if (isset($_GET['ccs_cleanup_categories']) && check_admin_referer('ccs_cleanup_categories')) {
        $cleaned = ccs_cleanup_course_categories();
        add_action('admin_notices', function() use ($cleaned) {
            echo '<div class="notice notice-success is-dismissible"><p>Cleaned up ' . esc_html($cleaned) . ' courses with more than 2 categories. Each course now has a maximum of 2 categories (primary and secondary).</p></div>';
        });
    }
}
add_action('admin_init', 'ccs_add_category_cleanup_action');

/**
 * Add cleanup button to course categories admin page
 */
function ccs_add_category_cleanup_button() {
    global $pagenow, $taxnow;
    
    if ($pagenow === 'edit-tags.php' && $taxnow === 'course_category') {
        ?>
        <div class="wrap" style="margin: 20px 0;">
            <h2>Course Category Management</h2>
            <p>Courses are limited to a maximum of 2 categories: a primary category and a secondary category.</p>
            <p>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('edit-tags.php?taxonomy=course_category&ccs_cleanup_categories=1'), 'ccs_cleanup_categories')); ?>" 
                   class="button button-secondary"
                   onclick="return confirm('This will remove all categories beyond the first 2 from all courses. Continue?');">
                    Clean Up Courses with More Than 2 Categories
                </a>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'ccs_add_category_cleanup_button');
