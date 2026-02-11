<?php
/**
 * Template Part: Breadcrumb
 *
 * @package ccs-theme
 * 
 * Usage: 
 *   set_query_var('breadcrumb_items', [
 *     ['label' => 'Courses', 'url' => get_post_type_archive_link('course')],
 *     ['label' => 'First Aid', 'url' => ''], // Empty URL = current page
 *   ]);
 *   get_template_part('template-parts/breadcrumb');
 */

$items = get_query_var('breadcrumb_items', []);
?>

<nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
  <ol class="breadcrumb-list">
    <li class="breadcrumb-item">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
    </li>
    
    <?php foreach ($items as $index => $item) : 
      $is_last = ($index === count($items) - 1);
    ?>
    <li class="breadcrumb-separator" aria-hidden="true">»</li>
    <li class="breadcrumb-item">
      <?php if (!$is_last && !empty($item['url'])) : ?>
        <a href="<?php echo esc_url($item['url']); ?>" class="breadcrumb-link"><?php echo esc_html($item['label']); ?></a>
      <?php else : ?>
        <span class="breadcrumb-current" aria-current="page"><?php echo esc_html($item['label']); ?></span>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>
</nav>

