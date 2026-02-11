<?php
/**
 * Single Blog Post Template
 *
 * @package ccs-theme
 * 
 * Note: get_field() is provided by Advanced Custom Fields plugin
 * @phpstan-ignore get_field
 */

get_header();

$contact = ccs_get_contact_info();
?>

<main id="main-content">
  <?php while (have_posts()) : the_post(); ?>

  <article id="post-<?php the_ID(); ?>" <?php post_class('single-post'); ?>>
    <header class="single-post-header-modern">
      <div class="container">
        <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
            <ol class="breadcrumb-list">
              <li class="breadcrumb-item">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
              </li>
              <li class="breadcrumb-separator" aria-hidden="true">/</li>
              <li class="breadcrumb-item">
                <?php 
                $news_page_id = get_option('page_for_posts');
                $news_url = $news_page_id ? get_permalink($news_page_id) : get_post_type_archive_link('post');
                ?>
                <a href="<?php echo esc_url($news_url); ?>" class="breadcrumb-link">News</a>
              </li>
              <li class="breadcrumb-separator" aria-hidden="true">/</li>
              <li class="breadcrumb-item">
                <span class="breadcrumb-current" aria-current="page"><?php the_title(); ?></span>
              </li>
            </ol>
          </nav>
          <?php
          $categories = get_the_category();
          if ($categories) :
            $news_page_id = get_option('page_for_posts');
            $news_url = $news_page_id ? get_permalink($news_page_id) : get_post_type_archive_link('post');
            $category_slug = strtolower(trim($categories[0]->name));
            $filtered_url = add_query_arg('category', $category_slug, $news_url);
          ?>
          <div class="single-post-category-simple">
            <a href="<?php echo esc_url($filtered_url); ?>" class="category-link-simple">
              <?php echo esc_html($categories[0]->name); ?>
            </a>
          </div>
          <?php endif; ?>

          <h1 class="single-post-title-modern"><?php the_title(); ?>          </h1>

          <div class="single-post-meta-below-image">
            <?php
            $author_id = get_the_author_meta('ID');
            $author_name = get_the_author();
            $published_iso = get_the_date('c');
            $modified_iso = get_the_modified_date('c');
            $show_updated = ( $modified_iso && $modified_iso !== $published_iso );
            ?>
            <span class="single-post-author-name"><?php echo esc_html($author_name); ?></span>
            <span class="single-post-date-label">Published </span><time datetime="<?php echo esc_attr($published_iso); ?>" class="single-post-date-below"><?php echo get_the_date('M j, Y'); ?></time>
            <?php if ( $show_updated ) : ?>
              <span class="single-post-updated-label"> · Last updated </span><time datetime="<?php echo esc_attr($modified_iso); ?>" class="single-post-updated-date"><?php echo get_the_modified_date('M j, Y'); ?></time>
            <?php endif; ?>
            <span class="reading-time-below">
              <?php echo ccs_reading_time(get_the_content()); ?> min read
            </span>
          </div>

          <div class="single-post-share-below">
            <span class="single-post-share-label-below">Share</span>
            <button type="button" 
                    class="single-post-share-link share-copy" 
                    onclick="navigator.clipboard.writeText('<?php echo esc_js(get_permalink()); ?>').then(() => { const btn = event.target.closest('button'); btn.classList.add('copied'); setTimeout(() => btn.classList.remove('copied'), 2000); })"
                    aria-label="Copy link">
              <i class="fas fa-link" aria-hidden="true"></i>
            </button>
            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="single-post-share-link share-twitter"
               aria-label="Share on X (Twitter)">
              <i class="fab fa-x-twitter" aria-hidden="true"></i>
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="single-post-share-link share-facebook"
               aria-label="Share on Facebook">
              <i class="fab fa-facebook-f" aria-hidden="true"></i>
            </a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(get_permalink()); ?>&title=<?php echo urlencode(get_the_title()); ?>" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="single-post-share-link share-linkedin"
               aria-label="Share on LinkedIn">
              <i class="fab fa-linkedin-in" aria-hidden="true"></i>
            </a>
          </div>

          <?php 
          $intro = get_field('news_intro');
          if ($intro) :
          ?>
          <div class="single-post-intro">
            <?php echo wp_kses_post($intro); ?>
          </div>
          <?php 
          elseif (has_excerpt()) : 
          ?>
          <div class="single-post-intro">
            <p><?php echo get_the_excerpt(); ?></p>
          </div>
          <?php endif; ?>
      </div>
    </header>

    <div class="container">
        <div class="single-post-divider"></div>

        <?php if (has_post_thumbnail()) : ?>
        <div class="single-post-hero-section">
          <div class="single-post-hero-image-wrapper">
            <div class="single-post-featured-image-modern">
              <div class="featured-image-wrapper">
                <?php 
                $thumbnail_id = get_post_thumbnail_id();
                $alt_text = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
                if (empty($alt_text)) {
                  $alt_text = get_the_title();
                }
                the_post_thumbnail('large', [
                  'class' => 'single-post-img-modern',
                  'alt' => esc_attr($alt_text)
                ]); 
                ?>
                <?php 
                $caption = get_the_post_thumbnail_caption();
                if ($caption) :
                ?>
                <figcaption class="featured-image-caption"><?php echo esc_html($caption); ?></figcaption>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <aside class="single-post-sidebar single-post-sidebar-left">
            <nav class="single-post-toc" aria-label="Article sections">
              <h3 class="single-post-toc-title">On this page</h3>
              <ul class="single-post-toc-list" id="article-toc"></ul>
            </nav>
          </aside>
        </div>
        <?php endif; ?>

        <div class="single-post-divider"></div>

        <div class="single-post-layout">
          <div class="single-post-main" id="article-body">
            <div class="entry-content">
              <?php 
              $sections = get_field('news_sections');
              if ($sections && is_array($sections)) :
                foreach ($sections as $section) :
                  if (!empty($section['section_title']) && !empty($section['section_content'])) :
              ?>
              <section class="article-section" id="<?php echo esc_attr(sanitize_title($section['section_title'])); ?>">
                <h2 class="article-section-title"><?php echo esc_html($section['section_title']); ?></h2>
                <div class="article-section-content">
                  <?php echo wp_kses_post($section['section_content']); ?>
                </div>
              </section>
              <?php 
                  endif;
                endforeach;
              else :
                the_content();
              endif;
              ?>
            </div>
          </div>
        </div>
      </div>
  </article>

  <?php
  $related_args = [
    'post_type' => 'post',
    'posts_per_page' => 3,
    'post__not_in' => [get_the_ID()],
    'orderby' => 'rand',
  ];

  if ($categories) {
    $related_args['cat'] = $categories[0]->term_id;
  }
  
  $related_posts = new WP_Query($related_args);
  
  if ($related_posts->have_posts()) :
  ?>
  <section class="related-posts-section-modern">
    <div class="container">
      <div class="related-posts-header">
        <h2 class="related-posts-title-modern">Read Our Next Article</h2>
        <div class="related-posts-nav">
          <button type="button" class="related-nav-btn related-nav-prev" aria-label="Previous articles">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 18l-6-6 6-6"/>
            </svg>
          </button>
          <button type="button" class="related-nav-btn related-nav-next" aria-label="Next articles">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 18l6-6-6-6"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="related-posts-grid-modern">
        <?php while ($related_posts->have_posts()) : $related_posts->the_post(); 
          $related_categories = get_the_category();
          $related_category = $related_categories ? $related_categories[0]->name : '';
        ?>
        <article class="related-article-card-modern">
          <?php if (has_post_thumbnail()) : ?>
          <div class="related-article-image-wrapper">
            <a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
              <?php the_post_thumbnail('medium_large', ['class' => 'related-article-image', 'loading' => 'lazy']); ?>
            </a>
          </div>
          <?php endif; ?>
          
          <div class="related-article-content">
            <?php if ($related_category) : ?>
            <div class="related-article-category">
              <span class="related-category-badge"><?php echo esc_html($related_category); ?></span>
            </div>
            <?php endif; ?>
            
            <h3 class="related-article-title">
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            
            <time datetime="<?php echo get_the_date('c'); ?>" class="related-article-date">
              <?php echo get_the_date('F j, Y'); ?>
            </time>
          </div>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php endwhile; ?>

  <section class="news-cta-section">
    <div class="container">
      <div class="news-cta-content">
        <h2>Ready to Upskill Your Team?</h2>
        <p>Explore our range of CPD-accredited care training courses.</p>
        <div class="news-cta-buttons">
          <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-primary">View Courses</a>
          <a href="<?php echo esc_url(ccs_page_url('contact')); ?>" class="btn btn-secondary">Contact Us</a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>

