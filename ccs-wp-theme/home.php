<?php
/**
 * Home Template (Blog/News Archive)
 * 
 * Used when a page is set as the "Posts page" in Reading Settings.
 * WordPress ignores the page template in this case and uses home.php instead.
 *
 * @package ccs-theme
 */

get_header();

$contact = ccs_get_contact_info();

// Get the page that's set as Posts page to access its ACF fields
$posts_page_id = get_option('page_for_posts');
$hero_title = 'News & Updates';
$hero_subtitle = 'Stay informed with the latest care sector news, CQC updates, and training insights.';

if ($posts_page_id) {
    $hero_title = get_field('hero_title', $posts_page_id) ?: $hero_title;
    $hero_subtitle = get_field('hero_subtitle', $posts_page_id) ?: $hero_subtitle;
}

// Pagination
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
?>

<main id="main-content">
  <!-- Hero Section -->
  <section class="group-hero-section" aria-labelledby="news-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
          </li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <span class="breadcrumb-current" aria-current="page">News</span>
          </li>
        </ol>
      </nav>
      <h1 id="news-heading" class="hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
    </div>
  </section>

  <!-- Articles Section -->
  <section class="news-articles-section">
    <div class="container">
      <?php if (have_posts()) : 
        $post_index = 0;
        $has_featured = false;
        $latest_section_opened = false;
      ?>
      
      <!-- Featured Article -->
      <?php 
      // Get all categories for filter buttons
      $all_categories = [];
      $temp_query = new WP_Query(['post_type' => 'post', 'posts_per_page' => -1, 'post_status' => 'publish']);
      while ($temp_query->have_posts()) : $temp_query->the_post();
        $cats = get_the_category();
        if ($cats && !is_wp_error($cats)) {
          foreach ($cats as $cat) {
            if (!in_array($cat->name, $all_categories)) {
              $all_categories[] = $cat->name;
            }
          }
        }
      endwhile;
      wp_reset_postdata();
      sort($all_categories);
      
      // Get the first post for featured
      while (have_posts()) : the_post();
        $post_index++;
        
        // Only show first post as featured
        if ($post_index === 1) :
          $has_featured = true;
          $featured_categories = get_the_category();
          $featured_category = $featured_categories && !is_wp_error($featured_categories) ? $featured_categories[0] : null;
          $featured_read_time = get_field('read_time') ?: ccs_reading_time(get_the_content());
      ?>
      <div id="featured-article-container">
        <article class="news-featured-article">
          <div class="news-featured-image-wrapper">
            <span class="news-featured-badge-label">Featured</span>
            <?php if (has_post_thumbnail()) : ?>
            <a href="<?php the_permalink(); ?>">
              <?php the_post_thumbnail('large', ['class' => 'news-featured-image', 'fetchpriority' => 'high']); ?>
            </a>
            <?php else : ?>
            <div class="news-featured-image-placeholder" aria-hidden="true">
              <i class="fas fa-newspaper" aria-hidden="true"></i>
            </div>
            <?php endif; ?>
          </div>
          
          <div class="news-featured-content">
            <div class="news-featured-meta">
              <?php if ($featured_category) : ?>
              <span class="news-category-badge news-category-badge-featured"><?php echo esc_html($featured_category->name); ?></span>
              <?php endif; ?>
              <time datetime="<?php echo get_the_date('c'); ?>" class="news-featured-date"><?php echo get_the_date('j M Y'); ?></time>
              <?php if ($featured_read_time) : ?>
              <span class="news-read-time"><?php echo esc_html($featured_read_time); ?> min read</span>
              <?php endif; ?>
            </div>
            
            <h2 class="news-featured-title"><?php the_title(); ?></h2>
            
            <p class="news-featured-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 25, '...'); ?></p>
            
            <a href="<?php the_permalink(); ?>" class="news-featured-cta">
              Read More
            </a>
          </div>
        </article>
      </div>
      <?php
          continue;
        endif;
        
        // Open Latest Articles Section only once, after featured article
        if ($has_featured && $post_index === 2) :
          $latest_section_opened = true;
      ?>
      
      <!-- Latest Articles Section -->
      <div class="latest-articles-section">
        <div class="latest-articles-header">
          <h2 id="latest-articles-heading" class="latest-articles-title">Latest Articles</h2>
          <div class="latest-articles-filters" role="group" aria-label="Filter articles by category">
            <button type="button" class="latest-articles-filter-btn active" data-category="all" aria-pressed="true">All</button>
            <?php foreach ($all_categories as $cat_name) : ?>
            <button type="button" class="latest-articles-filter-btn" data-category="<?php echo esc_attr(strtolower($cat_name)); ?>" aria-pressed="false"><?php echo esc_html($cat_name); ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        
        <div class="news-articles-grid" id="articles-grid">
        <?php
        endif;
        
        // Regular article cards (posts 2+)
        $categories = get_the_category();
        $category = $categories && !is_wp_error($categories) ? $categories[0] : null;
        $read_time = get_field('read_time') ?: ccs_reading_time(get_the_content());
      ?>
          <article class="news-article-card" id="post-<?php the_ID(); ?>" data-category="<?php echo esc_attr($category ? strtolower($category->name) : ''); ?>">
            <div class="news-article-image-wrapper">
              <?php if (has_post_thumbnail()) : ?>
              <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('medium_large', ['class' => 'news-article-image', 'loading' => 'lazy']); ?>
              </a>
              <?php else : ?>
              <div class="news-article-image-placeholder" aria-hidden="true">
                <i class="fas fa-file-alt" aria-hidden="true"></i>
              </div>
              <?php endif; ?>
            </div>
            
            <div class="news-article-content">
              <div class="news-article-meta">
                <?php if ($category) : ?>
                <span class="news-category-badge"><?php echo esc_html($category->name); ?></span>
                <?php endif; ?>
                <span class="news-article-date"><?php echo get_the_date('j M Y'); ?></span>
                <?php if ($read_time) : ?>
                <span class="news-read-time"><?php echo esc_html($read_time); ?> min read</span>
                <?php endif; ?>
              </div>
              
              <h3 class="news-article-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
              </h3>
              
              <p class="news-article-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
              
              <a href="<?php the_permalink(); ?>" class="news-article-link">
                Read More
                <svg class="news-arrow-icon-small" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
              </a>
            </div>
          </article>
      <?php endwhile; ?>
      
      <?php if ($latest_section_opened) : ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Pagination -->
      <?php
      the_posts_pagination([
        'prev_text' => '<span class="screen-reader-text">Previous</span><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>',
        'next_text' => '<span class="screen-reader-text">Next</span><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>',
      ]);
      ?>

      <?php else : ?>
      <div class="news-empty">
        <p>No news articles found. Check back soon for updates!</p>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">Back to Home</a>
      </div>
      <?php endif; ?>
    </div>
  </section>

</main>

<script>
(function() {
  const filterButtons = document.querySelectorAll('.latest-articles-filter-btn');
  const articleCards = document.querySelectorAll('.news-article-card');
  const articlesGrid = document.getElementById('articles-grid');
  
  // Create or get no results message element
  function getNoResultsMessage() {
    let noResults = document.getElementById('no-filter-results');
    if (!noResults && articlesGrid) {
      noResults = document.createElement('div');
      noResults.id = 'no-filter-results';
      noResults.className = 'no-filter-results';
      noResults.innerHTML = '<p>No articles found in this category.</p>';
      noResults.style.display = 'none';
      articlesGrid.parentNode.insertBefore(noResults, articlesGrid.nextSibling);
    }
    return noResults;
  }
  
  // Function to filter articles by category with smooth animation
  function filterByCategory(category) {
    let visibleCount = 0;
    
    // Update active state
    filterButtons.forEach(function(b) {
      const isActive = b.getAttribute('data-category') === category;
      b.classList.toggle('active', isActive);
      b.setAttribute('aria-pressed', isActive.toString());
    });
    
    // Filter articles with fade animation
    articleCards.forEach(function(card) {
      const cardCategory = card.getAttribute('data-category') || '';
      const shouldShow = category === 'all' || cardCategory === category;
      
      if (shouldShow) {
        visibleCount++;
        card.style.display = '';
        // Trigger reflow for animation
        card.offsetHeight;
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      } else {
        card.style.opacity = '0';
        card.style.transform = 'translateY(-10px)';
        setTimeout(function() {
          card.style.display = 'none';
        }, 200);
      }
    });
    
    // Show/hide no results message
    const noResults = getNoResultsMessage();
    if (noResults) {
      if (visibleCount === 0) {
        noResults.style.display = 'block';
        if (articlesGrid) articlesGrid.style.display = 'none';
      } else {
        noResults.style.display = 'none';
        if (articlesGrid) articlesGrid.style.display = '';
      }
    }
    
    // Announce to screen readers
    const announcement = document.createElement('div');
    announcement.setAttribute('role', 'status');
    announcement.setAttribute('aria-live', 'polite');
    announcement.className = 'sr-only';
    announcement.textContent = category === 'all' 
      ? 'Showing all articles' 
      : 'Showing ' + visibleCount + ' article' + (visibleCount !== 1 ? 's' : '') + ' in ' + category;
    document.body.appendChild(announcement);
    setTimeout(function() {
      document.body.removeChild(announcement);
    }, 1000);
  }
  
  // Set up initial styles for animation
  articleCards.forEach(function(card) {
    card.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
    card.style.opacity = '1';
    card.style.transform = 'translateY(0)';
  });
  
  // Check for category parameter in URL on page load
  const urlParams = new URLSearchParams(window.location.search);
  const categoryParam = urlParams.get('category');
  if (categoryParam) {
    // Decode and normalize the category (should already be lowercase, but ensure it is)
    const category = decodeURIComponent(categoryParam).toLowerCase().trim();
    // Filter by the category from URL
    setTimeout(function() {
      filterByCategory(category);
    }, 100); // Small delay to ensure DOM is ready
  }
  
  // Add click handlers to filter buttons
  filterButtons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      const category = this.getAttribute('data-category');
      filterByCategory(category);
      
      // Update URL without reload
      const url = new URL(window.location);
      if (category === 'all') {
        url.searchParams.delete('category');
      } else {
        url.searchParams.set('category', category);
      }
      window.history.pushState({}, '', url);
      
      // Scroll to top of articles section smoothly
      const articlesSection = document.querySelector('.latest-articles-section');
      if (articlesSection) {
        articlesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
})();
</script>

<?php get_footer(); ?>

