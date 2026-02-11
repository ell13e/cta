<?php
/**
 * Custom repeatable meta boxes (ACF-free alternative to Repeater field)
 * Phase 1: About page – Values and Statistics
 *
 * Saves to post meta in the same array format templates expect so get_field() works
 * when combined with acf/load_value filters. ACF field group keeps message-type
 * placeholders so load_value runs.
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/** Default rows for About Values (same as page-about.php fallback) */
function ccs_about_values_defaults() {
    return [
        [ 'icon' => 'fas fa-hands-helping', 'title' => 'Hands-On Care Training', 'description' => 'Practical training that builds real competence, regardless of experience or background.' ],
        [ 'icon' => 'fas fa-users', 'title' => 'Equality & Diversity in Training', 'description' => 'Anyone can reach success. The key is knowledge. We make training accessible to everyone.' ],
        [ 'icon' => 'fas fa-graduation-cap', 'title' => 'Flexible Care Training', 'description' => 'Everyone learns differently. Our training adapts. Your team walks away with skills they can actually use.' ],
        [ 'icon' => 'fas fa-handshake', 'title' => 'Partnership Care Training', 'description' => "We're your external training room. We learn your policies and procedures, then deliver training that fits how you actually work." ],
    ];
}

/** Default rows for About Stats */
function ccs_about_stats_defaults() {
    return [
        [ 'number' => '40+', 'label' => 'Courses Offered' ],
        [ 'number' => '2020', 'label' => 'Established' ],
        [ 'number' => '4.6/5', 'label' => 'Trustpilot Rating' ],
        [ 'number' => '100%', 'label' => 'CQC-Compliant' ],
    ];
}

/**
 * Enqueue admin CSS for custom repeater meta boxes.
 */
function ccs_enqueue_custom_repeaters_admin_css($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'page') {
        return;
    }
    $path = CCS_THEME_DIR . '/assets/css/admin-custom-repeaters.css';
    if (!file_exists($path)) {
        return;
    }
    wp_enqueue_style(
        'cta-admin-custom-repeaters',
        CCS_THEME_URI . '/assets/css/admin-custom-repeaters.css',
        [],
        (string) filemtime($path)
    );
}
add_action('admin_enqueue_scripts', 'ccs_enqueue_custom_repeaters_admin_css');

/**
 * Register meta boxes for About page Values and Stats (only when editing a page).
 */
function ccs_register_about_repeater_meta_boxes() {
    add_meta_box(
        'ccs_about_values',
        'About – Value cards',
        'ccs_about_values_meta_box_callback',
        'page',
        'normal',
        'default'
    );
    add_meta_box(
        'ccs_about_stats',
        'About – Statistics',
        'ccs_about_stats_meta_box_callback',
        'page',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'ccs_register_about_repeater_meta_boxes');

/**
 * Values meta box: repeatable rows (icon, title, description).
 */
function ccs_about_values_meta_box_callback($post) {
    if (get_page_template_slug($post->ID) !== 'page-templates/page-about.php') {
        echo '<p class="description">Set the page template to <strong>About Us</strong> to edit value cards here.</p>';
        return;
    }
    wp_nonce_field('ccs_about_values_nonce', 'ccs_about_values_nonce');
    $values = get_post_meta($post->ID, 'values', true);
    if (!is_array($values) || count($values) === 0) {
        $values = ccs_about_values_defaults();
    }
    ?>
    <p class="description" style="margin-bottom: 8px;">Value cards on the About page. Add or remove rows.</p>
    <div class="cta-repeater-table-wrap">
        <table class="cta-repeater-table" id="cta-about-values-table">
            <thead>
                <tr>
                    <th class="cta-repeater-col-icon">Icon</th>
                    <th class="cta-repeater-col-title">Title</th>
                    <th>Description</th>
                    <th class="cta-repeater-col-actions" aria-label="Remove"></th>
                </tr>
            </thead>
            <tbody id="cta-about-values-rows">
                <?php foreach ($values as $i => $row) : ?>
                <tr class="cta-repeater-row" data-index="<?php echo (int) $i; ?>">
                    <td class="cta-repeater-col-icon"><input type="text" name="ccs_values[<?php echo (int) $i; ?>][icon]" value="<?php echo esc_attr($row['icon'] ?? ''); ?>" placeholder="fas fa-icon" /></td>
                    <td class="cta-repeater-col-title"><input type="text" name="ccs_values[<?php echo (int) $i; ?>][title]" value="<?php echo esc_attr($row['title'] ?? ''); ?>" placeholder="Title" /></td>
                    <td><textarea name="ccs_values[<?php echo (int) $i; ?>][description]" rows="2" placeholder="Short description"><?php echo esc_textarea($row['description'] ?? ''); ?></textarea></td>
                    <td class="cta-repeater-col-actions"><button type="button" class="cta-repeater-remove-btn cta-repeater-remove-row" aria-label="Remove row">×</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="cta-repeater-add-wrap"><button type="button" class="button" id="cta-about-values-add">Add value card</button></p>
    <template id="cta-about-values-row-tpl">
        <tr class="cta-repeater-row" data-index="{{INDEX}}">
            <td class="cta-repeater-col-icon"><input type="text" name="ccs_values[{{INDEX}}][icon]" value="" placeholder="fas fa-icon" /></td>
            <td class="cta-repeater-col-title"><input type="text" name="ccs_values[{{INDEX}}][title]" value="" placeholder="Title" /></td>
            <td><textarea name="ccs_values[{{INDEX}}][description]" rows="2" placeholder="Short description"></textarea></td>
            <td class="cta-repeater-col-actions"><button type="button" class="cta-repeater-remove-btn cta-repeater-remove-row" aria-label="Remove row">×</button></td>
        </tr>
    </template>
    <script>
    (function() {
        var tbody = document.getElementById('cta-about-values-rows');
        if (!tbody) return;
        var addBtn = document.getElementById('cta-about-values-add');
        var tpl = document.getElementById('cta-about-values-row-tpl');
        if (!addBtn || !tpl) return;
        var nextIndex = tbody.querySelectorAll('.cta-repeater-row').length;
        addBtn.addEventListener('click', function() {
            var html = tpl.innerHTML.replace(/\{\{INDEX\}\}/g, nextIndex);
            var wrap = document.createElement('tbody');
            wrap.innerHTML = html;
            tbody.appendChild(wrap.firstElementChild);
            nextIndex++;
        });
        tbody.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('cta-repeater-remove-row')) {
                e.target.closest('tr.cta-repeater-row').remove();
            }
        });
    })();
    </script>
    <?php
}

/**
 * Stats meta box: repeatable rows (number, label).
 */
function ccs_about_stats_meta_box_callback($post) {
    if (get_page_template_slug($post->ID) !== 'page-templates/page-about.php') {
        echo '<p class="description">Set the page template to <strong>About Us</strong> to edit statistics here.</p>';
        return;
    }
    wp_nonce_field('ccs_about_stats_nonce', 'ccs_about_stats_nonce');
    $stats = get_post_meta($post->ID, 'stats', true);
    if (!is_array($stats) || count($stats) === 0) {
        $stats = ccs_about_stats_defaults();
    }
    ?>
    <p class="description" style="margin-bottom: 8px;">Statistics shown on the About page (e.g. 40+ Courses).</p>
    <div class="cta-repeater-table-wrap">
        <table class="cta-repeater-table" id="cta-about-stats-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Number</th>
                    <th>Label</th>
                    <th class="cta-repeater-col-actions" aria-label="Remove"></th>
                </tr>
            </thead>
            <tbody id="cta-about-stats-rows">
                <?php foreach ($stats as $i => $row) : ?>
                <tr class="cta-repeater-row" data-index="<?php echo (int) $i; ?>">
                    <td><input type="text" name="ccs_stats[<?php echo (int) $i; ?>][number]" value="<?php echo esc_attr($row['number'] ?? ''); ?>" placeholder="40+" /></td>
                    <td><input type="text" name="ccs_stats[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr($row['label'] ?? ''); ?>" placeholder="Courses Offered" /></td>
                    <td class="cta-repeater-col-actions"><button type="button" class="cta-repeater-remove-btn cta-repeater-remove-row" aria-label="Remove row">×</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="cta-repeater-add-wrap"><button type="button" class="button" id="cta-about-stats-add">Add statistic</button></p>
    <template id="cta-about-stats-row-tpl">
        <tr class="cta-repeater-row" data-index="{{INDEX}}">
            <td><input type="text" name="ccs_stats[{{INDEX}}][number]" value="" placeholder="40+" /></td>
            <td><input type="text" name="ccs_stats[{{INDEX}}][label]" value="" placeholder="Label" /></td>
            <td class="cta-repeater-col-actions"><button type="button" class="cta-repeater-remove-btn cta-repeater-remove-row" aria-label="Remove row">×</button></td>
        </tr>
    </template>
    <script>
    (function() {
        var tbody = document.getElementById('cta-about-stats-rows');
        if (!tbody) return;
        var addBtn = document.getElementById('cta-about-stats-add');
        var tpl = document.getElementById('cta-about-stats-row-tpl');
        if (!addBtn || !tpl) return;
        var nextIndex = tbody.querySelectorAll('.cta-repeater-row').length;
        addBtn.addEventListener('click', function() {
            var html = tpl.innerHTML.replace(/\{\{INDEX\}\}/g, nextIndex);
            var wrap = document.createElement('tbody');
            wrap.innerHTML = html;
            tbody.appendChild(wrap.firstElementChild);
            nextIndex++;
        });
        tbody.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('cta-repeater-remove-row')) {
                e.target.closest('tr.cta-repeater-row').remove();
            }
        });
    })();
    </script>
    <?php
}

/**
 * Save Values and Stats from our meta boxes. Only when template is About.
 */
function ccs_save_about_repeaters($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (get_post_type($post_id) !== 'page') {
        return;
    }
    if (get_page_template_slug($post_id) !== 'page-templates/page-about.php') {
        return;
    }

    if (isset($_POST['ccs_about_values_nonce']) && wp_verify_nonce($_POST['ccs_about_values_nonce'], 'ccs_about_values_nonce')) {
        $values = [];
        if (!empty($_POST['ccs_values']) && is_array($_POST['ccs_values'])) {
            foreach ($_POST['ccs_values'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $values[] = [
                    'icon'        => isset($row['icon']) ? sanitize_text_field($row['icon']) : '',
                    'title'       => isset($row['title']) ? sanitize_text_field($row['title']) : '',
                    'description' => isset($row['description']) ? sanitize_textarea_field($row['description']) : '',
                ];
            }
        }
        update_post_meta($post_id, 'values', $values);
    }

    if (isset($_POST['ccs_about_stats_nonce']) && wp_verify_nonce($_POST['ccs_about_stats_nonce'], 'ccs_about_stats_nonce')) {
        $stats = [];
        if (!empty($_POST['ccs_stats']) && is_array($_POST['ccs_stats'])) {
            foreach ($_POST['ccs_stats'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $stats[] = [
                    'number' => isset($row['number']) ? sanitize_text_field($row['number']) : '',
                    'label'  => isset($row['label']) ? sanitize_text_field($row['label']) : '',
                ];
            }
        }
        update_post_meta($post_id, 'stats', $stats);
    }
}
add_action('save_post_page', 'ccs_save_about_repeaters', 10, 1);

/**
 * Make get_field('values') and get_field('stats') return our meta so templates don't need to change.
 * Only when ACF would be loading this field (field still exists as message type in field group).
 */
function ccs_load_about_values_from_meta($value, $post_id, $field) {
    if (empty($post_id)) {
        return $value;
    }
    $name = is_array($field) && isset($field['name']) ? $field['name'] : '';
    if ($name !== 'values') {
        return $value;
    }
    $meta = get_post_meta($post_id, 'values', true);
    return is_array($meta) ? $meta : $value;
}
function ccs_load_about_stats_from_meta($value, $post_id, $field) {
    if (empty($post_id)) {
        return $value;
    }
    $name = is_array($field) && isset($field['name']) ? $field['name'] : '';
    if ($name !== 'stats') {
        return $value;
    }
    $meta = get_post_meta($post_id, 'stats', true);
    return is_array($meta) ? $meta : $value;
}
add_filter('acf/load_value/name=values', 'ccs_load_about_values_from_meta', 10, 3);
add_filter('acf/load_value/name=stats', 'ccs_load_about_stats_from_meta', 10, 3);
