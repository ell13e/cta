<?php
/**
 * Template Part: Course Card
 *
 * @package ccs-theme
 * 
 * Usage: get_template_part('template-parts/course-card');
 * 
 * Expected to be called within a loop or with a course post object set up.
 */

$course_id = get_the_ID();
$course_title = get_the_title();
$course_price = get_field('course_price', $course_id);
$course_duration = get_field('course_duration', $course_id);

$terms = get_the_terms($course_id, 'course_category');
$category_name = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
?>

<article class="course-card" id="post-<?php echo esc_attr($course_id); ?>">
  <div class="course-card-image">
    <?php if (has_post_thumbnail()) : ?>
      <a href="<?php the_permalink(); ?>">
        <?php the_post_thumbnail('medium_large', ['class' => 'course-card-img', 'loading' => 'lazy']); ?>
      </a>
    <?php else : ?>
      <a href="<?php the_permalink(); ?>">
        <div class="course-card-placeholder" aria-hidden="true">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
          </svg>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($category_name) : ?>
    <span class="course-card-badge"><?php echo esc_html($category_name); ?></span>
    <?php endif; ?>
  </div>
  
  <div class="course-card-content">
    <h3 class="course-card-title">
      <a href="<?php the_permalink(); ?>"><?php echo esc_html($course_title); ?></a>
    </h3>
    
    <p class="course-card-excerpt">
      <?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?>
    </p>
    
    <div class="course-card-meta">
      <?php if ($course_duration) : ?>
      <span class="course-card-duration">
        <i class="fas fa-clock" aria-hidden="true"></i>
        <?php echo esc_html($course_duration); ?>
      </span>
      <?php endif; ?>
      
      <?php if ($course_price) : ?>
      <span class="course-card-price">
        From £<?php echo esc_html(number_format($course_price, 0)); ?>
      </span>
      <?php endif; ?>
    </div>
  </div>
  
  <div class="course-card-footer">
    <a href="<?php the_permalink(); ?>" class="course-card-link">
      View Course
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M5 12h14M12 5l7 7-7 7"></path>
      </svg>
    </a>
  </div>
</article>

