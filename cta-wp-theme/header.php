<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link rel="dns-prefetch" href="https://fonts.googleapis.com">
  <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
  
  <meta name="msapplication-TileColor" content="#B29456">
  <meta name="theme-color" content="#B29456" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#2B1B0E" media="(prefers-color-scheme: dark)">
  
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

  <a href="#main-content" class="skip-link">Skip to main content</a>

<div id="search-modal" class="search-modal" role="dialog" aria-modal="true" aria-labelledby="search-title" aria-hidden="true">
  <div class="search-modal-backdrop" onclick="closeSearchModal()" aria-hidden="true"></div>
  <div class="search-modal-container" onclick="event.stopPropagation()">
    <h2 id="search-title" class="sr-only">Search for courses</h2>
    <div class="search-modal-content">
      <div class="search-input-wrapper">
        <svg
          class="search-icon"
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
        <input
          id="search-modal-input"
          type="text"
          placeholder="Search for courses..."
          class="search-input"
          aria-label="Search courses"
        />
        <button
          type="button"
          id="search-modal-close"
          class="search-close-btn"
          aria-label="Close search"
          onclick="closeSearchModal()"
        >
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      <div id="search-autocomplete" class="search-autocomplete" role="listbox" aria-label="Search suggestions" style="display: none;">
      </div>
      <p class="search-modal-hint search-modal-hint-desktop">
        Press <kbd class="kbd">ESC</kbd> to close
      </p>
      <p class="search-modal-hint search-modal-hint-mobile">Tap anywhere to close</p>
    </div>
  </div>
</div>

<!-- 
  Header Navigation
  
  NOTE FOR DEVELOPERS: The navigation below is hardcoded rather than using wp_nav_menu().
  This is intentional due to the complex mega menu structure with icons, descriptions,
  and multi-column layouts that would require a complex custom Walker class.
  
  To modify navigation:
  1. Main nav links: Edit the <nav class="nav-desktop"> section below
  2. Mega menu items: Edit the mega-menu-section divs
  3. Mobile menu: Edit the <div id="mobile-menu"> section
  
  If you need to add/remove pages, update BOTH desktop and mobile navigation.
  
  Menu locations registered (for future use or simpler menus):
  - 'primary' - Primary Navigation
  - 'footer-quick-links' - Footer Quick Links
  - 'footer-legal' - Footer Legal Links
-->
<header class="site-header" id="header">
  <div class="header-container">
    <div class="header-inner-wrapper">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="logo-link" aria-label="<?php bloginfo('name'); ?> Home">
        <img srcset="<?php echo esc_url(cta_image('logo/long_logo-400w.webp')); ?> 400w,
                     <?php echo esc_url(cta_image('logo/long_logo-800w.webp')); ?> 800w,
                     <?php echo esc_url(cta_image('logo/long_logo-1200w.webp')); ?> 1200w,
                     <?php echo esc_url(cta_image('logo/long_logo-1600w.webp')); ?> 1600w"
             src="<?php echo esc_url(cta_image('logo/long_logo-400w.webp')); ?>"
             alt="<?php bloginfo('name'); ?> - Care Sector Training in Kent"
             class="logo-img"
             width="200"
             height="50"
             sizes="(max-width: 640px) 180px, 200px">
      </a>

      <button
        type="button"
        id="mobile-menu-button"
        class="mobile-menu-btn"
        aria-label="Open menu"
        aria-expanded="false"
        aria-controls="mobile-navigation"
        onclick="toggleMobileMenu()"
      >
        <svg
          id="mobile-menu-icon"
          class="mobile-menu-icon"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>

      <nav class="nav-desktop" aria-label="Primary navigation">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-link<?php echo is_front_page() ? ' nav-link-active' : ''; ?>">Home</a>

        <div class="nav-item-dropdown">
          <button
            type="button"
            id="courses-link"
            class="nav-link nav-link-dropdown"
            aria-expanded="false"
            aria-haspopup="true"
            aria-controls="courses-dropdown"
          >
            <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="nav-link-dropdown-text">Courses</a>
            <svg
              class="chevron-icon"
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div id="courses-dropdown" class="mega-menu" role="menu" aria-labelledby="courses-link" aria-hidden="true">
            <button type="button" class="mega-menu-close" aria-label="Close courses menu">
              <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <path d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
            <div class="mega-menu-content">
              <div class="mega-menu-sections">
                <!-- Core Training Section -->
                <section class="mega-menu-section" aria-labelledby="section-core-training">
                  <h3 class="mega-menu-section-title" id="section-core-training">Core Training</h3>
                  <div class="mega-menu-items" role="group" aria-labelledby="section-core-training">
                    <a href="<?php echo esc_url(add_query_arg('category', 'core-care-skills', get_post_type_archive_link('course'))); ?>" class="mega-menu-item" role="menuitem" tabindex="-1">
                      <div class="mega-menu-item-icon">
                        <i class="fas fa-heart" aria-hidden="true"></i>
                      </div>
                      <div class="mega-menu-item-content">
                        <h4 class="mega-menu-item-title">Core Care Skills</h4>
                        <p class="mega-menu-item-description">Essential induction and Care Certificate training.</p>
                      </div>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('category', 'communication-workplace-culture', get_post_type_archive_link('course'))); ?>" class="mega-menu-item" role="menuitem" tabindex="-1">
                      <div class="mega-menu-item-icon">
                        <i class="fas fa-users" aria-hidden="true"></i>
                      </div>
                      <div class="mega-menu-item-content">
                        <h4 class="mega-menu-item-title">Communication & Workplace Culture</h4>
                        <p class="mega-menu-item-description">Dignity, equality, communication and care planning.</p>
                      </div>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('category', 'nutrition-hygiene', get_post_type_archive_link('course'))); ?>" class="mega-menu-item" role="menuitem" tabindex="-1">
                      <div class="mega-menu-item-icon">
                        <i class="fas fa-apple-alt" aria-hidden="true"></i>
                      </div>
                      <div class="mega-menu-item-content">
                        <h4 class="mega-menu-item-title">Nutrition & Hygiene</h4>
                        <p class="mega-menu-item-description">Food safety, nutrition and hygiene practices.</p>
                      </div>
                    </a>
                  </div>
                </section>

                <section class="mega-menu-section" aria-labelledby="section-safety-clinical">
                  <h3 class="mega-menu-section-title" id="section-safety-clinical">Safety & Clinical</h3>
                  <div class="mega-menu-items" role="group" aria-labelledby="section-safety-clinical">
                    <a href="<?php echo esc_url(add_query_arg('category', 'emergency-first-aid', get_post_type_archive_link('course'))); ?>" class="mega-menu-item" role="menuitem" tabindex="-1">
                      <div class="mega-menu-item-icon">
                        <i class="fas fa-first-aid" aria-hidden="true"></i>
                      </div>
                      <div class="mega-menu-item-content">
                        <h4 class="mega-menu-item-title">Emergency & First Aid</h4>
                        <p class="mega-menu-item-description">Workplace, paediatric and basic life support.</p>
                      </div>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('category', 'safety-compliance', get_post_type_archive_link('course'))); ?>" class="mega-menu-item" role="menuitem" tabindex="-1">
                      <div class="mega-menu-item-icon">
                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                      </div>
                      <div class="mega-menu-item-content">
                        <h4 class="mega-menu-item-title">Safety & Compliance</h4>
                        <p class="mega-menu-item-description">Workplace safety, safeguarding and moving & handling.</p>
                      </div>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('category', 'medication-management', get_post_type_archive_link('course'))); ?>" class="mega-menu-item" role="menuitem" tabindex="-1">
                      <div class="mega-menu-item-icon">
                        <i class="fas fa-pills" aria-hidden="true"></i>
                      </div>
                      <div class="mega-menu-item-content">
                        <h4 class="mega-menu-item-title">Medication Management</h4>
                        <p class="mega-menu-item-description">Medicines management, competency and insulin awareness.</p>
                      </div>
                    </a>
                  </div>
                </section>

                <section class="mega-menu-section" aria-labelledby="section-specialist">
                  <h3 class="mega-menu-section-title" id="section-specialist">Specialist & Leadership</h3>
                  <div class="mega-menu-items" role="group" aria-labelledby="section-specialist">
                    <a href="<?php echo esc_url(add_query_arg('category', 'health-conditions-specialist-care', get_post_type_archive_link('course'))); ?>" class="mega-menu-item" role="menuitem" tabindex="-1">
                      <div class="mega-menu-item-icon">
                        <i class="fas fa-stethoscope" aria-hidden="true"></i>
                      </div>
                      <div class="mega-menu-item-content">
                        <h4 class="mega-menu-item-title">Health Conditions & Specialist Care</h4>
                        <p class="mega-menu-item-description">Dementia, diabetes, epilepsy and specialist health conditions.</p>
                      </div>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('category', 'leadership-professional-development', get_post_type_archive_link('course'))); ?>" class="mega-menu-item" role="menuitem" tabindex="-1">
                      <div class="mega-menu-item-icon">
                        <i class="fas fa-user-tie" aria-hidden="true"></i>
                      </div>
                      <div class="mega-menu-item-content">
                        <h4 class="mega-menu-item-title">Leadership & Professional Development</h4>
                        <p class="mega-menu-item-description">Management, supervision and professional skills.</p>
                      </div>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('category', 'information-data-management', get_post_type_archive_link('course'))); ?>" class="mega-menu-item" role="menuitem" tabindex="-1">
                      <div class="mega-menu-item-icon">
                        <i class="fas fa-database" aria-hidden="true"></i>
                      </div>
                      <div class="mega-menu-item-content">
                        <h4 class="mega-menu-item-title">Information & Data Management</h4>
                        <p class="mega-menu-item-description">Data protection, record keeping and information governance.</p>
                      </div>
                    </a>
                  </div>
                </section>
              </div>
              <div class="mega-menu-footer">
                <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="mega-menu-view-all" role="menuitem" tabindex="-1">
                  View All 40+ Courses
                  <span aria-hidden="true">→</span>
                </a>
              </div>
            </div>
          </div>
        </div>

        <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="nav-link<?php echo is_post_type_archive('course_event') ? ' nav-link-active' : ''; ?>">Upcoming Courses</a>

        <div class="nav-item-dropdown">
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('group-training'))); ?>" class="nav-link">Group Training</a>
        </div>

        <a href="<?php echo esc_url(get_permalink(get_page_by_path('about'))); ?>" class="nav-link<?php echo is_page('about') ? ' nav-link-active' : ''; ?>">About Us</a>
        
        <div class="nav-item-dropdown">
          <button
            type="button"
            id="resources-link"
            class="nav-link nav-link-dropdown<?php echo (is_page('news') || is_page('cqc-compliance-hub') || is_page('training-guides-tools') || is_page('downloadable-resources') || is_page('faqs') || is_singular('post')) ? ' nav-link-active' : ''; ?>"
            aria-expanded="false"
            aria-haspopup="true"
            aria-controls="resources-dropdown"
          >
            <span class="nav-link-dropdown-text">Resources</span>
            <svg
              class="chevron-icon"
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div id="resources-dropdown" class="dropdown-menu" role="menu" aria-labelledby="resources-link">
            <?php
            wp_nav_menu([
                'theme_location' => 'resources',
                'container' => false,
                'menu_class' => '',
                'fallback_cb' => 'cta_resources_fallback_menu',
                'walker' => new CTA_Resources_Walker(),
                'depth' => 1,
            ]);
            ?>
          </div>
        </div>
      </nav>

      <?php $contact = cta_get_contact_info(); ?>
      <div class="header-actions">
        <button
          type="button"
          id="search-button"
          class="search-btn"
          aria-label="Open search"
          onclick="openSearchModal()">
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
        </button>
        <div class="header-divider" aria-hidden="true"></div>
        <a href="<?php echo esc_url($contact['phone_link']); ?>" class="phone-link">
          <i class="fas fa-phone" aria-hidden="true"></i>
          <span><?php echo esc_html($contact['phone']); ?></span>
        </a>
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact')) . '?type=schedule-call'); ?>" class="btn btn-primary btn-header-cta">Contact Us</a>
      </div>
    </div>

    <div id="mobile-menu" class="mobile-menu" role="dialog" aria-modal="true" aria-label="Mobile navigation">
      <nav class="mobile-menu-content" id="mobile-menu-content" aria-label="Mobile navigation">
        <button
          type="button"
          class="mobile-search-btn"
          id="mobile-search-button"
          onclick="openSearchModal(); closeMobileMenu();">
          <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
          Search Courses
        </button>

        <a href="<?php echo esc_url(home_url('/')); ?>" class="mobile-menu-link">Home</a>

        <div class="accordion" data-accordion-group="mobile-nav">
          <button
            type="button"
            id="mobile-courses-accordion"
            class="accordion-trigger"
            aria-expanded="false"
            aria-controls="mobile-courses-content"
          >
            Courses
            <svg
              class="accordion-icon"
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div id="mobile-courses-content" class="accordion-content" aria-hidden="true">
            <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="mobile-menu-link mobile-menu-link-sub">View All Courses</a>
            <?php foreach (cta_get_course_categories() as $category) : ?>
            <a href="<?php echo esc_url(add_query_arg('category', $category['slug'], get_post_type_archive_link('course'))); ?>" class="mobile-menu-link mobile-menu-link-sub"><?php echo esc_html($category['name']); ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="mobile-menu-link">Upcoming Courses</a>
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('group-training'))); ?>" class="mobile-menu-link">Group Training</a>
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('about'))); ?>" class="mobile-menu-link">About Us</a>
        
        <div class="accordion" data-accordion-group="mobile-nav">
          <button
            type="button"
            id="mobile-resources-accordion"
            class="accordion-trigger"
            aria-expanded="false"
            aria-controls="mobile-resources-content"
          >
            Resources
            <svg
              class="accordion-icon"
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div id="mobile-resources-content" class="accordion-content" aria-hidden="true">
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('cqc-compliance-hub'))); ?>" class="mobile-menu-link mobile-menu-link-sub">CQC Compliance Hub</a>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('training-guides-tools'))); ?>" class="mobile-menu-link mobile-menu-link-sub">Training Guides & Tools</a>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="mobile-menu-link mobile-menu-link-sub">Downloadable Resources</a>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('news'))); ?>" class="mobile-menu-link mobile-menu-link-sub">News & Articles</a>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('faqs'))); ?>" class="mobile-menu-link mobile-menu-link-sub">FAQs</a>
          </div>
        </div>
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact')) . '?type=schedule-call'); ?>" class="mobile-menu-link">Contact</a>
        <a href="<?php echo esc_url($contact['phone_link']); ?>" class="mobile-menu-link mobile-menu-link-phone">
          <i class="fas fa-phone" aria-hidden="true"></i>
          Call <span><?php echo esc_html($contact['phone']); ?></span>
        </a>
      </nav>
    </div>
  </div>
</header>

