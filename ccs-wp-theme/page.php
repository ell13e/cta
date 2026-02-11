<?php
/**
 * Default Page Template
 *
 * @package ccs-theme
 */

get_header();
?>

<main id="main-content">
  <article id="post-<?php the_ID(); ?>" <?php post_class('page-content'); ?>>
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- Page Header -->
    <section class="page-header">
      <div class="container">
        <h1 class="page-title"><?php the_title(); ?></h1>
      </div>
    </section>

    <!-- Page Content -->
    <section class="page-body">
      <div class="container">
        <div class="entry-content">
          <?php the_content(); ?>
        </div>
      </div>
    </section>

    <?php endwhile; endif; ?>
  </article>
</main>

<?php get_footer(); ?>

