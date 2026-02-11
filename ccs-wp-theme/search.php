<?php
/**
 * Search Results Template
 *
 * @package ccs-theme
 */

get_header();
?>

<main id="main-content">
  <!-- Search Header -->
  <section class="page-hero-section page-hero-section-simple">
    <div class="container">
      <h1 class="hero-title">
        <?php
        printf(
          esc_html__('Search Results for: %s', 'ccs-theme'),
          '<span>' . get_search_query() . '</span>'
        );
        ?>
      </h1>
      <p class="hero-subtitle">
        <?php
        global $wp_query;
        printf(
          esc_html(_n('%d result found', '%d results found', $wp_query->found_posts, 'ccs-theme')),
          $wp_query->found_posts
        );
        ?>
      </p>
      
      <!-- Search Form -->
      <div class="search-page-form">
        <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
          <label for="search-input" class="sr-only">Search for:</label>
          <div class="search-input-wrapper">
            <input type="search" 
                   id="search-input" 
                   class="search-field" 
                   placeholder="Search courses, articles..." 
                   value="<?php echo get_search_query(); ?>" 
                   name="s" />
            <button type="submit" class="search-submit btn btn-primary">
              Search
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- Search Results -->
  <section class="search-results-section">
    <div class="container">
      <?php if (have_posts()) : ?>
      
      <div class="search-results-grid">
        <?php while (have_posts()) : the_post(); ?>
        <article class="search-result-card" id="post-<?php the_ID(); ?>">
          <?php if (has_post_thumbnail()) : ?>
          <div class="search-result-image">
            <a href="<?php the_permalink(); ?>">
              <?php the_post_thumbnail('medium', ['class' => 'search-result-img', 'loading' => 'lazy']); ?>
            </a>
          </div>
          <?php endif; ?>
          
          <div class="search-result-content">
            <div class="search-result-meta">
              <span class="search-result-type">
                <?php
                $post_type = get_post_type();
                $post_type_obj = get_post_type_object($post_type);
                echo esc_html($post_type_obj->labels->singular_name);
                ?>
              </span>
              <?php if ($post_type === 'post') : ?>
              <time datetime="<?php echo get_the_date('c'); ?>" class="search-result-date">
                <?php echo get_the_date('j F Y'); ?>
              </time>
              <?php endif; ?>
            </div>
            
            <h2 class="search-result-title">
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>
            
            <p class="search-result-excerpt">
              <?php echo wp_trim_words(get_the_excerpt(), 25, '...'); ?>
            </p>
            
            <a href="<?php the_permalink(); ?>" class="search-result-link">
              <?php echo $post_type === 'course' ? 'View Course' : 'Read More'; ?>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M12 5l7 7-7 7"></path>
              </svg>
            </a>
          </div>
        </article>
        <?php endwhile; ?>
      </div>

      <!-- Pagination -->
      <nav class="search-pagination" aria-label="Search results pagination">
        <?php
        echo paginate_links([
          'prev_text' => '<span class="screen-reader-text">Previous</span><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>',
          'next_text' => '<span class="screen-reader-text">Next</span><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>',
          'type' => 'list',
        ]);
        ?>
      </nav>

      <?php else : ?>
      
      <div class="search-no-results">
        <div class="search-no-results-icon">
          <i class="fas fa-search" aria-hidden="true"></i>
        </div>
        <h2>No results found</h2>
        <p>Sorry, we couldn't find anything matching "<strong><?php echo get_search_query(); ?></strong>".</p>
        <p>Try different keywords or browse our courses below.</p>
        
        <div class="search-no-results-actions">
          <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-primary">Browse Courses</a>
          <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-secondary">Back to Home</a>
        </div>
      </div>
      
      <?php endif; ?>
    </div>
  </section>
</main>

<?php get_footer(); ?>

