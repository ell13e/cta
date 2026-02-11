<?php
/**
 * Template Name: Search
 *
 * Static page at /search/ with search form. Search results use search.php (?s=query).
 *
 * @package ccs-theme
 */

get_header();
?>

<main id="main-content" class="site-main">
  <section class="page-hero-section page-hero-section-simple">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><span class="breadcrumb-current" aria-current="page">Search</span></li>
        </ol>
      </nav>
      <h1 id="search-heading" class="hero-title"><?php echo esc_html(get_the_title() ?: 'Search'); ?></h1>
      <p class="hero-subtitle">Search our courses, news, and resources.</p>
      <div class="search-page-form" style="max-width: 600px; margin-top: 1.5rem;">
        <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
          <label for="search-input-page" class="sr-only">Search for:</label>
          <div class="search-input-wrapper" style="display: flex; gap: 0.5rem;">
            <input type="search"
                   id="search-input-page"
                   class="search-field"
                   placeholder="Search courses, articles..."
                   name="s"
                   style="flex: 1; padding: 0.75rem; border: 1px solid #E8E3D6; border-radius: 4px;" />
            <button type="submit" class="search-submit btn btn-primary">Search</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>

<?php
get_footer();
