<?php
/**
 * Media Library Folders
 * 
 * Adds folder/organization functionality to WordPress Media Library
 * using a custom taxonomy for attachments.
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Register custom taxonomy for media library folders
 */
function ccs_register_media_folders() {
    $labels = [
        'name' => 'Media Folders',
        'singular_name' => 'Media Folder',
        'menu_name' => 'Folders',
        'all_items' => 'All Folders',
        'parent_item' => 'Parent Folder',
        'parent_item_colon' => 'Parent Folder:',
        'new_item_name' => 'New Folder Name',
        'add_new_item' => 'Add New Folder',
        'edit_item' => 'Edit Folder',
        'update_item' => 'Update Folder',
        'separate_items_with_commas' => 'Separate folders with commas',
        'search_items' => 'Search Folders',
        'add_or_remove_items' => 'Add or remove folders',
        'choose_from_most_used' => 'Choose from the most used folders',
        'not_found' => 'No folders found',
    ];

    $args = [
        'labels' => $labels,
        'hierarchical' => true, // Allows nested folders
        'public' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => false,
        'show_tagcloud' => false,
        'rewrite' => false,
        'query_var' => 'media_folder',
        'capabilities' => [
            'manage_terms' => 'upload_files',
            'edit_terms' => 'upload_files',
            'delete_terms' => 'upload_files',
            'assign_terms' => 'upload_files',
        ],
    ];

    register_taxonomy('media_folder', 'attachment', $args);
}
add_action('init', 'ccs_register_media_folders');

/**
 * Add folder column to media library list view
 */
function ccs_add_media_folder_column($columns) {
    // Insert folder column after title
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['media_folder'] = 'Folder';
        }
    }
    return $new_columns;
}
add_filter('manage_media_columns', 'ccs_add_media_folder_column');

/**
 * Display folder in media library column
 */
function ccs_display_media_folder_column($column_name, $post_id) {
    if ($column_name === 'media_folder') {
        $terms = get_the_terms($post_id, 'media_folder');
        if ($terms && !is_wp_error($terms)) {
            $folder_names = [];
            foreach ($terms as $term) {
                $folder_names[] = esc_html($term->name);
            }
            echo implode(', ', $folder_names);
        } else {
            echo '<span style="color: #999;">—</span>';
        }
    }
}
add_action('manage_media_custom_column', 'ccs_display_media_folder_column', 10, 2);

/**
 * Make folder column sortable
 */
function ccs_make_media_folder_column_sortable($columns) {
    $columns['media_folder'] = 'media_folder';
    return $columns;
}
add_filter('manage_upload_sortable_columns', 'ccs_make_media_folder_column_sortable');

/**
 * Handle sorting by folder
 */
function ccs_sort_media_by_folder($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if (isset($_GET['orderby']) && $_GET['orderby'] === 'media_folder') {
        $query->set('meta_key', 'media_folder');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'ccs_sort_media_by_folder');

/**
 * Add folder filter dropdown to media library
 */
function ccs_add_media_folder_filter() {
    global $typenow;
    
    if ($typenow === 'attachment') {
        $taxonomy = 'media_folder';
        $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
        $info_taxonomy = get_taxonomy($taxonomy);
        
        wp_dropdown_categories([
            'show_option_all' => sprintf(__('All %s', 'ccs-theme'), $info_taxonomy->label),
            'taxonomy' => $taxonomy,
            'name' => $taxonomy,
            'orderby' => 'name',
            'selected' => $selected,
            'show_count' => true,
            'hide_empty' => false,
            'hierarchical' => true,
            'value_field' => 'slug',
        ]);
    }
}
add_action('restrict_manage_posts', 'ccs_add_media_folder_filter');

/**
 * Filter media library by folder
 */
function ccs_filter_media_by_folder($query) {
    global $pagenow, $typenow;
    
    if ($pagenow === 'upload.php' && $typenow === 'attachment' && isset($_GET['media_folder']) && $_GET['media_folder'] !== '') {
        $query->query_vars['tax_query'] = [
            [
                'taxonomy' => 'media_folder',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['media_folder']),
            ],
        ];
    }
}
add_action('parse_query', 'ccs_filter_media_by_folder');

/**
 * Add folder meta box to attachment edit screen
 */
function ccs_add_media_folder_meta_box() {
    add_meta_box(
        'media_folder_meta_box',
        'Media Folder',
        'ccs_media_folder_meta_box_callback',
        'attachment',
        'side',
        'default'
    );
}
add_action('add_meta_boxes_attachment', 'ccs_add_media_folder_meta_box');

/**
 * Media folder meta box callback
 */
function ccs_media_folder_meta_box_callback($post) {
    wp_nonce_field('ccs_media_folder_meta_box', 'ccs_media_folder_meta_box_nonce');
    
    $terms = get_the_terms($post->ID, 'media_folder');
    $term_ids = $terms && !is_wp_error($terms) ? wp_list_pluck($terms, 'term_id') : [];
    
    wp_dropdown_categories([
        'taxonomy' => 'media_folder',
        'name' => 'media_folder[]',
        'selected' => !empty($term_ids) ? $term_ids[0] : 0,
        'show_option_none' => '— No Folder —',
        'orderby' => 'name',
        'hierarchical' => true,
        'hide_empty' => false,
    ]);
    
    echo '<p style="margin-top: 10px;"><a href="' . admin_url('edit-tags.php?taxonomy=media_folder&post_type=attachment') . '" class="button button-small">Manage Folders</a></p>';
}

/**
 * Save media folder on attachment save
 */
function ccs_save_media_folder($post_id) {
    // Check nonce
    if (!isset($_POST['ccs_media_folder_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['ccs_media_folder_meta_box_nonce'], 'ccs_media_folder_meta_box')) {
        return;
    }

    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save folder
    if (isset($_POST['media_folder']) && !empty($_POST['media_folder'])) {
        $folder_id = intval($_POST['media_folder']);
        if ($folder_id > 0) {
            wp_set_object_terms($post_id, [$folder_id], 'media_folder');
        } else {
            wp_set_object_terms($post_id, [], 'media_folder');
        }
    }
}
add_action('edit_attachment', 'ccs_save_media_folder');

/**
 * Add folder selector to media uploader
 */
function ccs_add_folder_to_media_uploader() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Add folder selector to media uploader
        var mediaFolderSelect = $('<div class="attachment-media-folder" style="padding: 10px; border-top: 1px solid #ddd;"><label><strong>Folder:</strong> </label><select name="media_folder" style="width: 100%; margin-top: 5px;"></select></div>');
        
        // Insert after attachment details
        $('.attachment-details').after(mediaFolderSelect);
        
        // Load folders via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ccs_get_media_folders',
            },
            success: function(response) {
                if (response.success && response.data) {
                    var select = $('select[name="media_folder"]');
                    select.append('<option value="">— No Folder —</option>');
                    $.each(response.data, function(id, name) {
                        select.append('<option value="' + id + '">' + name + '</option>');
                    });
                }
            }
        });
        
        // Save folder when attachment is saved
        $('button.media-button-insert, button.media-button-select').on('click', function() {
            var folderId = $('select[name="media_folder"]').val();
            if (folderId) {
                // Store folder ID to be saved when attachment is created
                $(this).data('folder-id', folderId);
            }
        });
    });
    </script>
    <?php
}
add_action('print_media_templates', 'ccs_add_folder_to_media_uploader');

/**
 * AJAX handler to get media folders
 */
function ccs_get_media_folders_ajax() {
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error('Invalid request', 403);
    }
    
    $folders = get_terms([
        'taxonomy' => 'media_folder',
        'hide_empty' => false,
        'orderby' => 'name',
        'hierarchical' => true,
    ]);
    
    $folder_list = [];
    if (!is_wp_error($folders) && !empty($folders)) {
        foreach ($folders as $folder) {
            $folder_list[$folder->term_id] = $folder->name;
        }
    }
    
    wp_send_json_success($folder_list);
}
add_action('wp_ajax_ccs_get_media_folders', 'ccs_get_media_folders_ajax');

/**
 * Create default folders on theme activation
 */
function ccs_create_default_media_folders() {
    $default_folders = [
        'Course Thumbnails',
        'Blog Posts',
        'About Us',
        'Team Members',
        'Logos',
        'Partners',
    ];
    
    foreach ($default_folders as $folder_name) {
        if (!term_exists($folder_name, 'media_folder')) {
            wp_insert_term($folder_name, 'media_folder');
        }
    }
}
add_action('after_switch_theme', 'ccs_create_default_media_folders');

/**
 * Add bulk actions for media library
 */
function ccs_add_media_bulk_actions($actions) {
    $actions['assign_folder'] = 'Assign to Folder';
    return $actions;
}
add_filter('bulk_actions-upload', 'ccs_add_media_bulk_actions');

/**
 * Handle bulk folder assignment
 */
function ccs_handle_media_bulk_actions() {
    if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'assign_folder') {
        return;
    }
    
    check_admin_referer('bulk-media');
    
    if (!isset($_REQUEST['media']) || !is_array($_REQUEST['media'])) {
        return;
    }
    
    $folder_id = isset($_REQUEST['media_folder_bulk']) ? intval($_REQUEST['media_folder_bulk']) : 0;
    
    if ($folder_id > 0) {
        foreach ($_REQUEST['media'] as $media_id) {
            wp_set_object_terms(intval($media_id), [$folder_id], 'media_folder');
        }
        
        wp_redirect(admin_url('upload.php?bulk_assigned=1'));
        exit;
    }
}
add_action('admin_init', 'ccs_handle_media_bulk_actions');

/**
 * Add bulk folder selector
 */
function ccs_add_bulk_folder_selector() {
    global $typenow;
    
    if ($typenow === 'attachment') {
        ?>
        <div class="alignleft actions">
            <select name="media_folder_bulk" id="media_folder_bulk">
                <option value="">— Select Folder —</option>
                <?php
                $folders = get_terms([
                    'taxonomy' => 'media_folder',
                    'hide_empty' => false,
                    'orderby' => 'name',
                ]);
                
                if (!is_wp_error($folders) && !empty($folders)) {
                    foreach ($folders as $folder) {
                        echo '<option value="' . esc_attr($folder->term_id) . '">' . esc_html($folder->name) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
        <?php
    }
}
add_action('restrict_manage_posts', 'ccs_add_bulk_folder_selector', 20);

