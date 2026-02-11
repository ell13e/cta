<?php
/**
 * Search Form Template
 *
 * Custom search form for the theme.
 * Use get_search_form() to include this template.
 *
 * @package ccs-theme
 */

$unique_id = wp_unique_id('search-form-');
$search_query = get_search_query();
?>

<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label for="<?php echo esc_attr($unique_id); ?>" class="sr-only">
        <?php esc_html_e('Search for:', 'ccs-theme'); ?>
    </label>
    <div class="search-input-wrapper">
        <i class="fas fa-search" aria-hidden="true"></i>
        <input 
            type="search" 
            id="<?php echo esc_attr($unique_id); ?>" 
            class="search-field" 
            placeholder="<?php esc_attr_e('Search courses, articles...', 'ccs-theme'); ?>" 
            value="<?php echo esc_attr($search_query); ?>" 
            name="s" 
        />
        <button type="submit" class="search-submit btn btn-primary">
            <span class="sr-only"><?php esc_html_e('Search', 'ccs-theme'); ?></span>
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </button>
    </div>
</form>

