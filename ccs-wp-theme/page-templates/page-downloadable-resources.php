<?php
/**
 * Template Name: Downloadable Resources
 *
 * @package ccs-theme
 */

get_header();

$meta_title = 'Downloadable Care Training Resources | Free Templates & Guides';
$meta_description = 'Free downloadable resources for care providers. Training templates, compliance checklists, policy documents, and care sector guides.';
?>
<meta name="description" content="<?php echo esc_attr($meta_description); ?>">
<meta property="og:title" content="<?php echo esc_attr($meta_title); ?>">
<meta property="og:description" content="<?php echo esc_attr($meta_description); ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo esc_attr($meta_title); ?>">
<meta name="twitter:description" content="<?php echo esc_attr($meta_description); ?>">
<?php

$contact = ccs_get_contact_info();

$hero_title = function_exists('get_field') ? get_field('hero_title') : '';
$hero_subtitle = function_exists('get_field') ? get_field('hero_subtitle') : '';
$resource_library_title = function_exists('get_field') ? (get_field('resource_library_title') ?: '') : '';
$resource_library_subtitle = function_exists('get_field') ? (get_field('resource_library_subtitle') ?: '') : '';
$resource_categories = function_exists('get_field') ? (get_field('resource_categories') ?: []) : [];

if (empty($hero_title)) {
    $hero_title = 'Free Training Resources for Care Professionals';
}
if (empty($hero_subtitle)) {
    $hero_subtitle = 'Templates, checklists, quick reference guides, and tools to support excellent care.';
}
if (empty($resource_library_title)) {
    $resource_library_title = 'Resource Library';
}
if (empty($resource_library_subtitle)) {
    $resource_library_subtitle = 'Filter resources by category';
}

$category_defaults = [
    'quick-reference' => ['title' => 'Quick Reference Cards', 'subtitle' => 'Laminated reference cards and pocket guides for quick access to essential information'],
    'templates' => ['title' => 'Templates & Tools', 'subtitle' => 'Excel and Word templates to streamline your training administration'],
    'policies' => ['title' => 'Policy Templates', 'subtitle' => 'Comprehensive policy templates aligned with CQC requirements'],
    'infographics' => ['title' => 'Infographics & Posters', 'subtitle' => 'Visual resources for noticeboards, staff rooms, and quick reminders'],
    'checklists' => ['title' => 'Checklists', 'subtitle' => 'Step-by-step checklists to support audits and day-to-day compliance'],
];
$category_map = $category_defaults;
if (is_array($resource_categories)) {
    foreach ($resource_categories as $row) {
        if (!is_array($row) || empty($row['key'])) continue;
        $key = (string) $row['key'];
        if (!isset($category_map[$key])) continue;
        if (!empty($row['title'])) {
            $category_map[$key]['title'] = (string) $row['title'];
        }
        if (isset($row['subtitle']) && $row['subtitle'] !== '') {
            $category_map[$key]['subtitle'] = (string) $row['subtitle'];
        }
    }
}

$resources_by_category = [];
$resource_posts = get_posts([
    'post_type' => 'ccs_resource',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'menu_order title',
    'order' => 'ASC',
]);

foreach ($resource_posts as $resource_post) {
    if (!is_object($resource_post)) {
        continue;
    }
    $resource_id = (int) $resource_post->ID;
    if ($resource_id <= 0) {
        continue;
    }

    $title = get_the_title($resource_id);
    $content = isset($resource_post->post_content) ? (string) $resource_post->post_content : '';
    $desc = has_excerpt($resource_id) ? get_the_excerpt($resource_id) : wp_trim_words(wp_strip_all_tags($content), 22);
    $icon = (string) get_post_meta($resource_id, '_ccs_resource_icon', true);
    if ($icon === '') $icon = 'fas fa-file';

    $terms = get_the_terms($resource_id, 'ccs_resource_category');
    $cat_slug = 'templates';
    if (is_array($terms) && !empty($terms) && !is_wp_error($terms)) {
        $cat_slug = (string) $terms[0]->slug;
    }
    if (!isset($resources_by_category[$cat_slug])) $resources_by_category[$cat_slug] = [];
    $resources_by_category[$cat_slug][] = [
        'id' => $resource_id,
        'title' => $title,
        'description' => $desc,
        'icon' => $icon,
        'button_label' => 'Get via email',
    ];
}

foreach (array_keys($resources_by_category) as $slug) {
    if (isset($category_map[$slug])) {
        continue;
    }
    $term = get_term_by('slug', $slug, 'ccs_resource_category');
    if ($term && !is_wp_error($term)) {
        $category_map[$slug] = [
            'title' => (string) $term->name,
            'subtitle' => (string) ($term->description ?: ''),
        ];
    }
}
?>

<main id="main-content" class="site-main">
  <section class="group-hero-section" aria-labelledby="resources-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><span class="breadcrumb-current" aria-current="page">Downloadable Resources</span></li>
        </ol>
      </nav>
      <h1 id="resources-heading" class="hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
      <div class="group-hero-cta">
        <a href="#resource-library" class="btn btn-primary group-hero-btn-primary">Browse All Resources</a>
      </div>
    </div>
  </section>

  <section class="content-section" id="resource-library" aria-labelledby="filter-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="filter-heading" class="section-title"><?php echo esc_html($resource_library_title); ?></h2>
        <p class="section-description"><?php echo esc_html($resource_library_subtitle); ?></p>
      </div>
      
      <div class="resources-filter-group">
        <button type="button" class="resources-filter-btn active" data-filter="all" aria-pressed="true">All Resources</button>
        <?php
        $filter_keys = !empty($resources_by_category) ? array_keys($resources_by_category) : array_keys($category_map);
        foreach ($filter_keys as $key) :
            if (!isset($category_map[$key])) continue;
        ?>
            <button type="button" class="resources-filter-btn" data-filter="<?php echo esc_attr($key); ?>" aria-pressed="false"><?php echo esc_html($category_map[$key]['title']); ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if (!empty($resources_by_category)) : ?>
    <?php foreach ($resources_by_category as $cat_key => $items) :
      if (empty($items) || !isset($category_map[$cat_key])) continue;
      $cat_title = $category_map[$cat_key]['title'];
      $cat_subtitle = $category_map[$cat_key]['subtitle'];
    ?>
      <section class="content-section bg-light-cream" aria-labelledby="<?php echo esc_attr($cat_key); ?>-heading" data-category="<?php echo esc_attr($cat_key); ?>" style="display: block;">
        <div class="container">
          <div class="section-header-center">
            <h2 id="<?php echo esc_attr($cat_key); ?>-heading" class="section-title"><?php echo esc_html($cat_title); ?></h2>
            <?php if (!empty($cat_subtitle)) : ?>
              <p class="section-description"><?php echo esc_html($cat_subtitle); ?></p>
            <?php endif; ?>
          </div>

          <div class="downloadable-resources-grid">
            <?php foreach ($items as $item) :
              $rid = (int) ($item['id'] ?? 0);
              $title = (string) ($item['title'] ?? '');
              $desc = (string) ($item['description'] ?? '');
              $icon = (string) ($item['icon'] ?? 'fas fa-file');
              $button_label = (string) ($item['button_label'] ?? 'Get via email');
              if ($rid <= 0 || $title === '' || $desc === '') continue;
            ?>
              <div class="downloadable-resource-card">
                <div class="downloadable-resource-icon">
                  <i class="<?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                </div>
                <div class="downloadable-resource-content">
                  <h3 class="downloadable-resource-title"><?php echo esc_html($title); ?></h3>
                  <p class="downloadable-resource-description"><?php echo esc_html($desc); ?></p>
                  <button
                    type="button"
                    class="downloadable-resource-btn resource-download-btn"
                    data-resource-id="<?php echo esc_attr($rid); ?>"
                    data-resource-name="<?php echo esc_attr($title); ?>"
                  >
                    <?php echo esc_html($button_label); ?>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endforeach; ?>
  <?php else : ?>
    <section class="content-section bg-light-cream">
      <div class="container">
        <div class="resources-coming-soon">
          <div class="resources-coming-soon-icon">
            <i class="fas fa-box-open" aria-hidden="true"></i>
          </div>
          <h2>Resources Coming Soon</h2>
          <p>We're updating our resource library. Please check back soon for templates, checklists, and training tools.</p>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="content-section">
    <div class="container">
      <div class="resources-newsletter-box">
        <div class="resources-newsletter-icon">
          <i class="fas fa-envelope" aria-hidden="true"></i>
        </div>
        <h2>Want New Resources Delivered to Your Inbox?</h2>
        <p>Subscribe to receive updates when we add new templates, checklists, and training resources.</p>
        <button type="button" onclick="openNewsletterModal()" class="btn btn-primary btn-large">Subscribe to Training Resources Updates</button>
      </div>
    </div>
  </section>

  <section class="cqc-cta-section">
    <div class="container">
      <div class="cqc-cta-content">
        <h2 class="cqc-cta-title">Looking for Training to Go With These Resources?</h2>
        <p class="cqc-cta-description">Our courses provide the knowledge and skills that complement these downloadable resources.</p>
        <div class="cqc-cta-buttons">
          <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-primary btn-large">Browse Our Courses</a>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('group-training'))); ?>" class="btn btn-secondary btn-large">Book Group Training</a>
        </div>
      </div>
    </div>
  </section>
</main>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Free Training Resources for Care Professionals",
  "description": "Free training resources for care professionals. Templates, checklists, quick reference guides, and tools to support excellent care.",
  "url": "<?php echo esc_url(get_permalink()); ?>",
  "about": {
    "@type": "Thing",
    "name": "Care Training Resources",
    "description": "Downloadable templates, checklists, and guides for health and social care professionals"
  },
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "<?php echo esc_url(home_url('/')); ?>"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "Downloadable Resources",
        "item": "<?php echo esc_url(get_permalink()); ?>"
      }
    ]
  },
  "hasPart": [
    {
      "@type": "CreativeWork",
      "name": "Training Templates",
      "description": "Customizable templates for training records, competency assessments, and compliance documentation"
    },
    {
      "@type": "CreativeWork",
      "name": "Checklists",
      "description": "Quick reference checklists for care procedures, safety protocols, and inspection preparation"
    },
    {
      "@type": "CreativeWork",
      "name": "Quick Reference Guides",
      "description": "Essential information guides for common care scenarios and regulatory requirements"
    },
    {
      "@type": "CreativeWork",
      "name": "Planning Tools",
      "description": "Tools for training planning, staff development, and compliance tracking"
    }
  ],
  "publisher": {
    "@type": "EducationalOrganization",
    "name": "Continuity of Care Services",
    "url": "<?php echo esc_url(home_url('/')); ?>"
  }
}
</script>

<script>
(function() {
  'use strict';
  
  const filterButtons = document.querySelectorAll('.resources-filter-btn');
  const categorySections = document.querySelectorAll('section[data-category]');
  
  filterButtons.forEach(button => {
    button.addEventListener('click', function() {
      const filterValue = this.getAttribute('data-filter');

      filterButtons.forEach(btn => {
        btn.classList.remove('active');
        btn.setAttribute('aria-pressed', 'false');
      });
      this.classList.add('active');
      this.setAttribute('aria-pressed', 'true');

      if (filterValue === 'all') {
        categorySections.forEach(section => {
          section.style.display = 'block';
        });
      } else {
        categorySections.forEach(section => {
          const sectionCategory = section.getAttribute('data-category');
          if (sectionCategory === filterValue) {
            section.style.display = 'block';
          } else {
            section.style.display = 'none';
          }
        });
      }

      const firstVisible = document.querySelector('section[data-category][style*="display: block"]');
      if (firstVisible && filterValue !== 'all') {
        firstVisible.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
})();
</script>

<?php
get_footer();
?>
