<?php
/**
 * Template Name: Training Guides & Tools
 *
 * @package CTA_Theme
 */

get_header();

// SEO Meta Tags
$meta_title = 'Care Training Guides & Resources | Planning Tools for Providers';
$meta_description = 'Practical training guides for care providers. Group training planning, funding options, compliance checklists, and workforce development resources.';
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

$contact = cta_get_contact_info();

// ACF fields
$hero_title = function_exists('get_field') ? get_field('hero_title') : '';
$hero_subtitle = function_exists('get_field') ? get_field('hero_subtitle') : '';
$hero_cta_label = function_exists('get_field') ? (get_field('hero_cta_label') ?: '') : '';
$hero_cta_link = function_exists('get_field') ? get_field('hero_cta_link') : null;
$use_custom_sections = function_exists('get_field') ? (get_field('use_custom_sections') ? true : false) : false;
$guide_sections = function_exists('get_field') ? (get_field('guide_sections') ?: []) : [];

// Defaults
if (empty($hero_title)) {
    $hero_title = 'Find the Right Training for Your Team';
}
if (empty($hero_subtitle)) {
    $hero_subtitle = 'Compare courses, explore training pathways, and plan your team\'s professional development.';
}
if (empty($hero_cta_label)) {
    $hero_cta_label = 'Download Training Planning Guide';
}
$hero_cta_url = '';
if (!empty($hero_cta_link)) {
    $hero_cta_url = is_string($hero_cta_link) ? $hero_cta_link : '';
}
if (empty($hero_cta_url)) {
    $dl_page = get_page_by_path('downloadable-resources');
    $hero_cta_url = $dl_page ? get_permalink($dl_page) : '';
}
?>

<main id="main-content" class="site-main">
  <!-- Hero Section -->
  <section class="group-hero-section" aria-labelledby="training-guides-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>#resources" class="breadcrumb-link">Resources</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><span class="breadcrumb-current" aria-current="page">Training Guides & Tools</span></li>
        </ol>
      </nav>
      <h1 id="training-guides-heading" class="hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
      <div class="group-hero-cta">
        <?php if (!empty($hero_cta_url)) : ?>
          <a href="<?php echo esc_url($hero_cta_url); ?>" class="btn btn-primary group-hero-btn-primary"><?php echo esc_html($hero_cta_label); ?></a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($use_custom_sections && !empty($guide_sections) && is_array($guide_sections)) : ?>
    <?php foreach ($guide_sections as $section) :
      $title = is_array($section) ? ($section['title'] ?? '') : '';
      $content = is_array($section) ? ($section['content'] ?? '') : '';
      if (empty($title) || empty($content)) {
        continue;
      }
    ?>
      <section class="content-section" aria-label="<?php echo esc_attr($title); ?>">
        <div class="container">
          <div class="section-header-center">
            <h2 class="section-title"><?php echo esc_html($title); ?></h2>
          </div>
          <div class="training-guide-content">
            <?php echo wp_kses_post($content); ?>
          </div>
        </div>
      </section>
    <?php endforeach; ?>
  <?php else : ?>
  <!-- Course Comparison Guides Section -->
  <section class="content-section" aria-labelledby="comparison-guides-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="comparison-guides-heading" class="section-title">Course Comparison Guides</h2>
        <p class="section-description">Choose the right training with clear, visual comparisons</p>
      </div>
      
      <!-- First Aid Comparison -->
      <div class="comparison-section">
        <div class="comparison-intro">
          <div class="comparison-intro-icon">
            <i class="fas fa-heartbeat" aria-hidden="true"></i>
          </div>
          <div class="comparison-intro-content">
            <h3>First Aid Training: Which Course Do You Need?</h3>
            <p>Different roles require different levels of first aid training</p>
          </div>
        </div>
        
        <div class="comparison-cards-grid">
          <div class="comparison-course-card">
            <div class="comparison-card-badge">Essential</div>
            <h4>Basic Life Support</h4>
            <div class="comparison-card-meta">
              <div class="comparison-meta-item">
                <i class="fas fa-clock" aria-hidden="true"></i>
                <span>3 hours</span>
              </div>
              <div class="comparison-meta-item">
                <i class="fas fa-certificate" aria-hidden="true"></i>
                <span>CQC recommended</span>
              </div>
            </div>
            <div class="comparison-card-for">
              <strong>Who needs it:</strong>
              <p>All care staff</p>
            </div>
          </div>
          
          <div class="comparison-course-card comparison-card-popular">
            <div class="comparison-card-badge comparison-badge-popular">Most Popular</div>
            <h4>Emergency First Aid at Work</h4>
            <div class="comparison-card-meta">
              <div class="comparison-meta-item">
                <i class="fas fa-clock" aria-hidden="true"></i>
                <span>1 day</span>
              </div>
              <div class="comparison-meta-item">
                <i class="fas fa-certificate" aria-hidden="true"></i>
                <span>HSE requirement</span>
              </div>
            </div>
            <div class="comparison-card-for">
              <strong>Who needs it:</strong>
              <p>Workplace first aiders</p>
            </div>
          </div>
          
          <div class="comparison-course-card">
            <div class="comparison-card-badge">Advanced</div>
            <h4>First Aid at Work</h4>
            <div class="comparison-card-meta">
              <div class="comparison-meta-item">
                <i class="fas fa-clock" aria-hidden="true"></i>
                <span>3 days</span>
              </div>
              <div class="comparison-meta-item">
                <i class="fas fa-certificate" aria-hidden="true"></i>
                <span>HSE requirement</span>
              </div>
            </div>
            <div class="comparison-card-for">
              <strong>Who needs it:</strong>
              <p>High-risk workplaces</p>
            </div>
          </div>
          
          <div class="comparison-course-card">
            <div class="comparison-card-badge">Specialist</div>
            <h4>Paediatric First Aid</h4>
            <div class="comparison-card-meta">
              <div class="comparison-meta-item">
                <i class="fas fa-clock" aria-hidden="true"></i>
                <span>2 days</span>
              </div>
              <div class="comparison-meta-item">
                <i class="fas fa-certificate" aria-hidden="true"></i>
                <span>EYFS requirement</span>
              </div>
            </div>
            <div class="comparison-card-for">
              <strong>Who needs it:</strong>
              <p>Childcare staff</p>
            </div>
          </div>
        </div>
        
        <div class="comparison-cta">
          <a href="<?php echo esc_url(add_query_arg('category', 'emergency-first-aid', get_post_type_archive_link('course'))); ?>" class="btn btn-primary">
            View All First Aid Courses
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>
      
      <!-- Safeguarding Comparison -->
      <div class="comparison-section">
        <div class="comparison-intro">
          <div class="comparison-intro-icon">
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
          </div>
          <div class="comparison-intro-content">
            <h3>Safeguarding Training Levels Explained</h3>
            <p>Required at different levels depending on your role and responsibilities</p>
          </div>
        </div>
        
        <div class="comparison-levels-grid">
          <div class="comparison-level-card">
            <div class="comparison-level-number">1</div>
            <h4>Level 1</h4>
            <p class="comparison-level-who">All care staff</p>
            <ul class="comparison-level-list">
              <li>Basic awareness</li>
              <li>Recognizing signs</li>
              <li>Reporting procedures</li>
            </ul>
          </div>
          
          <div class="comparison-level-card">
            <div class="comparison-level-number">2</div>
            <h4>Level 2</h4>
            <p class="comparison-level-who">Staff with direct care responsibilities</p>
            <ul class="comparison-level-list">
              <li>Deeper understanding</li>
              <li>Assessment skills</li>
              <li>Documentation</li>
            </ul>
          </div>
          
          <div class="comparison-level-card">
            <div class="comparison-level-number">3</div>
            <h4>Level 3</h4>
            <p class="comparison-level-who">Managers, supervisors, designated leads</p>
            <ul class="comparison-level-list">
              <li>Advanced skills</li>
              <li>Investigation</li>
              <li>Multi-agency working</li>
            </ul>
          </div>
        </div>
        
        <div class="comparison-cta">
          <a href="<?php echo esc_url(add_query_arg('category', 'safety-compliance', get_post_type_archive_link('course'))); ?>" class="btn btn-primary">
            View Safeguarding Courses
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>
      
      <!-- Care Certificate Comparison -->
      <div class="comparison-section">
        <div class="comparison-intro">
          <div class="comparison-intro-icon">
            <i class="fas fa-graduation-cap" aria-hidden="true"></i>
          </div>
          <div class="comparison-intro-content">
            <h3>Care Certificate vs Adult Social Care Certificate</h3>
            <p>Choose the right qualification pathway for your team</p>
          </div>
        </div>
        
        <div class="comparison-vs-grid">
          <div class="comparison-vs-card">
            <div class="comparison-vs-header">
              <i class="fas fa-certificate" aria-hidden="true"></i>
              <h4>Care Certificate</h4>
            </div>
            <ul class="comparison-vs-list">
              <li><i class="fas fa-check" aria-hidden="true"></i> 15 standards</li>
              <li><i class="fas fa-check" aria-hidden="true"></i> 12-week completion</li>
              <li><i class="fas fa-check" aria-hidden="true"></i> Induction training</li>
              <li><i class="fas fa-check" aria-hidden="true"></i> Workplace-based assessment</li>
            </ul>
          </div>
          
          <div class="comparison-vs-divider">
            <span>vs</span>
          </div>
          
          <div class="comparison-vs-card">
            <div class="comparison-vs-header">
              <i class="fas fa-award" aria-hidden="true"></i>
              <h4>Adult Social Care Certificate</h4>
            </div>
            <ul class="comparison-vs-list">
              <li><i class="fas fa-check" aria-hidden="true"></i> Level 2 qualification</li>
              <li><i class="fas fa-check" aria-hidden="true"></i> 12-18 months</li>
              <li><i class="fas fa-check" aria-hidden="true"></i> Apprenticeship route</li>
              <li><i class="fas fa-check" aria-hidden="true"></i> Formal assessment</li>
            </ul>
          </div>
        </div>
        
        <div class="comparison-cta">
          <a href="<?php echo esc_url(add_query_arg('category', 'core-care-skills', get_post_type_archive_link('course'))); ?>" class="btn btn-primary">
            View Core Care Courses
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Training Pathways by Role Section -->
  <section class="content-section bg-light-cream" aria-labelledby="training-pathways-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="training-pathways-heading" class="section-title">Training Pathways by Role</h2>
        <p class="section-description">Structured pathways to guide professional development for different roles</p>
      </div>
      
      <div class="training-pathways-grid">
        <!-- Care Worker Pathway -->
        <div class="training-pathway-card">
          <div class="training-pathway-header">
            <h3 class="training-pathway-title">Care Worker Pathway</h3>
            <button type="button" class="training-pathway-toggle" aria-expanded="false" aria-controls="pathway-care-worker-content">
              <span class="sr-only">Toggle Care Worker Pathway</span>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </button>
          </div>
          
          <div id="pathway-care-worker-content" class="training-pathway-content" aria-hidden="true">
            <div class="training-pathway-stages">
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                  </svg>
                </div>
                <h4 class="training-stage-title">Induction Requirements<br><span class="training-stage-subtitle">(First 12 Weeks)</span></h4>
                <ul class="training-stage-list">
                  <li>Care Certificate (all 15 standards)</li>
                  <li>Moving & Handling</li>
                  <li>Basic Life Support</li>
                  <li>Safeguarding Adults (Level 1)</li>
                </ul>
              </div>
              
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                  </svg>
                </div>
                <h4 class="training-stage-title">Year 1<br><span class="training-stage-subtitle">Mandatory Courses</span></h4>
                <ul class="training-stage-list">
                  <li>First Aid (Emergency or Paediatric)</li>
                  <li>Infection Prevention & Control</li>
                  <li>Fire Safety</li>
                  <li>Food Hygiene (if applicable)</li>
                </ul>
              </div>
              
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                </div>
                <h4 class="training-stage-title">Annual<br><span class="training-stage-subtitle">Refreshers</span></h4>
                <ul class="training-stage-list">
                  <li>Safeguarding (annually)</li>
                  <li>Moving & Handling (annually)</li>
                  <li>Infection Control (annually)</li>
                </ul>
              </div>
              
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                  </svg>
                </div>
                <h4 class="training-stage-title">Optional<br><span class="training-stage-subtitle">Specialist Courses</span></h4>
                <ul class="training-stage-list">
                  <li>Dementia Care</li>
                  <li>Learning Disabilities</li>
                  <li>End of Life Care</li>
                </ul>
              </div>
            </div>
            
            <div class="training-pathway-footer">
              <button type="button" class="training-pathway-download" onclick="window.location.href='<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>'">
                <span>Download Care Worker Training Matrix</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
              </button>
              <button type="button" class="training-pathway-add" aria-label="Add pathway to comparison">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Senior Care Worker / Team Leader Pathway -->
        <div class="training-pathway-card">
          <div class="training-pathway-header">
            <h3 class="training-pathway-title">Senior Care Worker / Team Leader</h3>
            <button type="button" class="training-pathway-toggle" aria-expanded="false" aria-controls="pathway-team-leader-content">
              <span class="sr-only">Toggle Team Leader Pathway</span>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </button>
          </div>
          
          <div id="pathway-team-leader-content" class="training-pathway-content" aria-hidden="true">
            <div class="training-pathway-stages">
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                  </svg>
                </div>
                <h4 class="training-stage-title">Induction Requirements<br><span class="training-stage-subtitle">(First 12 Weeks)</span></h4>
                <ul class="training-stage-list">
                  <li>All Care Worker requirements</li>
                  <li>Plus leadership foundations</li>
                </ul>
              </div>
              
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                  </svg>
                </div>
                <h4 class="training-stage-title">Year 1<br><span class="training-stage-subtitle">Mandatory Courses</span></h4>
                <ul class="training-stage-list">
                  <li>Leadership & Management</li>
                  <li>Supervision & Mentoring</li>
                  <li>Safeguarding (Level 2)</li>
                  <li>Medication Management</li>
                </ul>
              </div>
              
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                </div>
                <h4 class="training-stage-title">Annual<br><span class="training-stage-subtitle">Refreshers</span></h4>
                <ul class="training-stage-list">
                  <li>All Care Worker refreshers</li>
                  <li>Leadership updates</li>
                </ul>
              </div>
              
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                  </svg>
                </div>
                <h4 class="training-stage-title">Optional<br><span class="training-stage-subtitle">Specialist Courses</span></h4>
                <ul class="training-stage-list">
                  <li>Person-Centred Care Planning</li>
                  <li>Advanced Safeguarding</li>
                  <li>Quality Assurance</li>
                </ul>
              </div>
            </div>
            
            <div class="training-pathway-footer">
              <button type="button" class="training-pathway-download" onclick="window.location.href='<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>'">
                <span>Download Team Leader Training Matrix</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
              </button>
              <button type="button" class="training-pathway-add" aria-label="Add pathway to comparison">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Registered Manager Pathway -->
        <div class="training-pathway-card">
          <div class="training-pathway-header">
            <h3 class="training-pathway-title">Registered Manager</h3>
            <button type="button" class="training-pathway-toggle" aria-expanded="false" aria-controls="pathway-manager-content">
              <span class="sr-only">Toggle Registered Manager Pathway</span>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </button>
          </div>
          
          <div id="pathway-manager-content" class="training-pathway-content" aria-hidden="true">
            <div class="training-pathway-stages">
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                  </svg>
                </div>
                <h4 class="training-stage-title">CQC Essentials<br><span class="training-stage-subtitle">(Registration)</span></h4>
                <ul class="training-stage-list">
                  <li>Registered Manager Award (Level 4/5)</li>
                  <li>Safeguarding (Level 3)</li>
                  <li>Leadership & Management</li>
                  <li>Clinical Governance</li>
                </ul>
              </div>
              
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                  </svg>
                </div>
                <h4 class="training-stage-title">Legal<br><span class="training-stage-subtitle">Compliance Training</span></h4>
                <ul class="training-stage-list">
                  <li>Mental Capacity Act & DoLS</li>
                  <li>Health & Safety Management</li>
                  <li>Information Governance</li>
                  <li>CQC Inspection Preparation</li>
                </ul>
              </div>
              
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                </div>
                <h4 class="training-stage-title">Annual<br><span class="training-stage-subtitle">Updates</span></h4>
                <ul class="training-stage-list">
                  <li>Safeguarding updates</li>
                  <li>Legislation changes</li>
                  <li>CQC requirements</li>
                </ul>
              </div>
              
              <div class="training-stage-card">
                <div class="training-stage-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                  </svg>
                </div>
                <h4 class="training-stage-title">Optional<br><span class="training-stage-subtitle">Professional Development</span></h4>
                <ul class="training-stage-list">
                  <li>Advanced Leadership</li>
                  <li>Business Management</li>
                  <li>Strategic Planning</li>
                </ul>
              </div>
            </div>
            
            <div class="training-pathway-footer">
              <button type="button" class="training-pathway-download" onclick="window.location.href='<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>'">
                <span>Download Manager Training Matrix</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
              </button>
              <button type="button" class="training-pathway-add" aria-label="Add pathway to comparison">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
              </button>
            </div>
          </div>
        </div>
        
        <div class="group-faq-item">
          <button type="button" class="group-faq-question" aria-expanded="false" aria-controls="training-pathway-clinical-staff">
            <span>Nursing & Clinical Staff</span>
            <span class="group-faq-icon" aria-hidden="true"></span>
          </button>
          <div id="training-pathway-clinical-staff" class="group-faq-answer" role="region" aria-hidden="true">
            <p>All core care training plus clinical skills:</p>
            <ul>
              <li>Clinical Skills Updates</li>
              <li>Wound Care Management</li>
              <li>Catheter Care</li>
              <li>PEG Feeding</li>
              <li>Diabetes Management</li>
              <li>Pressure Ulcer Prevention</li>
            </ul>
            <div>
              <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="btn btn-secondary">Download Clinical Staff Matrix</a>
            </div>
          </div>
        </div>
        
        <div class="group-faq-item">
          <button type="button" class="group-faq-question" aria-expanded="false" aria-controls="training-pathway-specialist-roles">
            <span>Specialist Roles (LD, Complex Care, etc.)</span>
            <span class="group-faq-icon" aria-hidden="true"></span>
          </button>
          <div id="training-pathway-specialist-roles" class="group-faq-answer" role="region" aria-hidden="true">
            <p>Additional specialist training requirements:</p>
            <ul>
              <li>Learning Disabilities Awareness</li>
              <li>Autism Awareness</li>
              <li>Positive Behaviour Support</li>
              <li>Mental Capacity Act & DoLS (advanced)</li>
              <li>Complex Health Conditions</li>
              <li>Oliver McGowan Training (mandatory from 2026)</li>
            </ul>
            <div>
              <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="btn btn-secondary">Download Specialist Matrix</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Funding & Financial Support Section -->
  <section class="content-section bg-light-cream" aria-labelledby="funding-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="funding-heading" class="section-title">Funding Your Team's Training</h2>
        <p class="section-description">Multiple funding options available—most care providers can claim back training costs</p>
      </div>
      
      <!-- WDF Hero Card -->
      <div class="funding-hero-card">
        <div class="funding-hero-header">
          <div class="funding-hero-icon">
            <i class="fas fa-piggy-bank" aria-hidden="true"></i>
          </div>
          <div class="funding-hero-title-wrapper">
            <h3 class="funding-hero-title">Workforce Development Fund (WDF)</h3>
            <p class="funding-hero-subtitle">The main funding route for care sector training</p>
          </div>
        </div>
        
        <div class="funding-hero-content">
          <div class="funding-highlight-box">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <p><strong>Most of our CPD-accredited courses are WDF-eligible</strong>—Adult social care providers registered with Skills for Care can claim back training costs.</p>
          </div>
          
          <div class="funding-quick-facts">
            <div class="funding-fact">
              <span class="funding-fact-label">Who can apply:</span>
              <span class="funding-fact-value">CQC-registered adult social care providers</span>
            </div>
            <div class="funding-fact">
              <span class="funding-fact-label">What's covered:</span>
              <span class="funding-fact-value">Training costs for eligible courses</span>
            </div>
            <div class="funding-fact">
              <span class="funding-fact-label">When to claim:</span>
              <span class="funding-fact-value">After course completion via Skills for Care portal</span>
            </div>
          </div>
        </div>
        
        <div class="funding-hero-actions">
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="btn btn-primary btn-large">
            <i class="fas fa-download" aria-hidden="true"></i> Download WDF Application Guide
          </a>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact')) . '?type=funding-query'); ?>" class="btn btn-secondary btn-large">
            <i class="fas fa-comments" aria-hidden="true"></i> Ask About Funding
          </a>
        </div>
      </div>
      
      <!-- Other Funding Sources -->
      <div class="funding-other-section">
        <h3 class="funding-other-title">Other Ways to Fund Training</h3>
        <div class="funding-options-grid">
          
          <!-- Skills for Care Grants -->
          <div class="funding-option-card">
            <div class="funding-option-icon funding-option-icon-green">
              <i class="fas fa-hands-helping" aria-hidden="true"></i>
            </div>
            <h4 class="funding-option-title">Skills for Care Grants</h4>
            <p class="funding-option-description">Targeted funding for specific training needs and workforce development initiatives.</p>
            <ul class="funding-option-list">
              <li>Leadership & management programs</li>
              <li>Digital skills development</li>
              <li>Workforce retention initiatives</li>
            </ul>
          </div>
          
          <!-- Local Authority Support -->
          <div class="funding-option-card">
            <div class="funding-option-icon funding-option-icon-blue">
              <i class="fas fa-landmark" aria-hidden="true"></i>
            </div>
            <h4 class="funding-option-title">Local Authority Grants</h4>
            <p class="funding-option-description">Kent-specific funding opportunities for registered care providers.</p>
            <ul class="funding-option-list">
              <li>Kent County Council training support</li>
              <li>Care sector development grants</li>
              <li>Quality improvement funding</li>
            </ul>
          </div>
          
          <!-- Apprenticeship Levy -->
          <div class="funding-option-card">
            <div class="funding-option-icon funding-option-icon-purple">
              <i class="fas fa-graduation-cap" aria-hidden="true"></i>
            </div>
            <h4 class="funding-option-title">Apprenticeship Levy</h4>
            <p class="funding-option-description">Use your levy funds (or transfer from larger employers) for apprenticeship training.</p>
            <ul class="funding-option-list">
              <li>Health & Social Care diplomas</li>
              <li>Team leader qualifications</li>
              <li>Management apprenticeships</li>
            </ul>
          </div>
          
          <!-- Group Discounts -->
          <div class="funding-option-card">
            <div class="funding-option-icon funding-option-icon-orange">
              <i class="fas fa-users" aria-hidden="true"></i>
            </div>
            <h4 class="funding-option-title">Group Booking Discounts</h4>
            <p class="funding-option-description">Save on training costs by booking multiple places or combining teams.</p>
            <ul class="funding-option-list">
              <li>Volume discounts for larger teams</li>
              <li>Bespoke in-house training rates</li>
              <li>Annual training packages</li>
            </ul>
          </div>
          
        </div>
      </div>
      
      <!-- Funding FAQ Accordion -->
      <div class="funding-faq-section">
        <h3 class="funding-faq-title">Common Funding Questions</h3>
        <div class="funding-faq-accordions">
          
          <!-- How to apply for WDF -->
          <div class="accordion" data-accordion-group="funding-faq">
            <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="funding-wdf-apply">
              <span><i class="fas fa-clipboard-list" aria-hidden="true"></i> How do I apply for WDF funding?</span>
              <span class="accordion-icon" aria-hidden="true"></span>
            </button>
            <div id="funding-wdf-apply" class="accordion-content" role="region" aria-hidden="true">
              <ol class="funding-faq-steps">
                <li><strong>Register with Skills for Care:</strong> Create an account on the Skills for Care website and complete your organization's ASC-WDS return</li>
                <li><strong>Check course eligibility:</strong> Ensure the training you're booking is WDF-eligible (we'll confirm this when you book)</li>
                <li><strong>Complete the training:</strong> Staff attend and pass the course</li>
                <li><strong>Submit your claim:</strong> Log into Skills for Care portal and submit your claim with course certificates</li>
                <li><strong>Receive reimbursement:</strong> WDF will reimburse eligible costs directly to your organization</li>
              </ol>
              <p><a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="inline-link">Download our step-by-step WDF application guide</a></p>
            </div>
          </div>
          
          <!-- Which courses are eligible -->
          <div class="accordion" data-accordion-group="funding-faq">
            <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="funding-eligible-courses">
              <span><i class="fas fa-certificate" aria-hidden="true"></i> Which CTA courses are WDF-eligible?</span>
              <span class="accordion-icon" aria-hidden="true"></span>
            </button>
            <div id="funding-eligible-courses" class="accordion-content" role="region" aria-hidden="true">
              <p><strong>Most of our CPD-accredited courses qualify for WDF funding, including:</strong></p>
              <ul>
                <li>Care Certificate program</li>
                <li>Medication administration and management</li>
                <li>Moving and handling training</li>
                <li>Safeguarding adults and children</li>
                <li>Mental health and dementia care</li>
                <li>First aid and emergency response</li>
                <li>Leadership and management development</li>
              </ul>
              <p>Course pages show WDF eligibility, or <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact')) . '?type=funding-query'); ?>" class="inline-link">contact us to confirm</a> before booking.</p>
            </div>
          </div>
          
          <!-- Payment and reimbursement -->
          <div class="accordion" data-accordion-group="funding-faq">
            <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="funding-payment">
              <span><i class="fas fa-pound-sign" aria-hidden="true"></i> Do I pay upfront and get reimbursed?</span>
              <span class="accordion-icon" aria-hidden="true"></span>
            </button>
            <div id="funding-payment" class="accordion-content" role="region" aria-hidden="true">
              <p><strong>Yes, with WDF you pay for the training upfront and claim reimbursement afterwards.</strong></p>
              <p>This is standard practice for WDF funding. We accept payment by card, invoice, or bank transfer. Once your staff complete the course, you submit your claim to Skills for Care who will reimburse the training costs.</p>
              <p>Processing times vary, but claims are typically processed within 4-6 weeks of submission.</p>
            </div>
          </div>
          
          <!-- What if not eligible -->
          <div class="accordion" data-accordion-group="funding-faq">
            <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="funding-not-eligible">
              <span><i class="fas fa-question-circle" aria-hidden="true"></i> What if I'm not eligible for WDF funding?</span>
              <span class="accordion-icon" aria-hidden="true"></span>
            </button>
            <div id="funding-not-eligible" class="accordion-content" role="region" aria-hidden="true">
              <p><strong>You still have options:</strong></p>
              <ul>
                <li><strong>Group discounts:</strong> Book multiple staff at once to reduce per-person costs</li>
                <li><strong>Bespoke in-house training:</strong> Often more cost-effective for larger teams</li>
                <li><strong>Payment plans:</strong> Spread costs across multiple months</li>
                <li><strong>Alternative funding:</strong> Explore local authority grants, apprenticeship levy transfers, or Skills for Care grants</li>
              </ul>
              <p><a href="<?php echo esc_url(get_permalink(get_page_by_path('contact')) . '?type=funding-query'); ?>" class="inline-link">Get in touch</a> and we'll help you find the most cost-effective solution.</p>
            </div>
          </div>
          
        </div>
      </div>
      
    </div>
  </section>

  <!-- Helpful Tools Section -->
  <?php 
  $downloadable_resources = get_field('downloadable_resources');
  if ($downloadable_resources && is_array($downloadable_resources) && !empty($downloadable_resources)) : 
  ?>
  <section class="content-section" aria-labelledby="tools-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="tools-heading" class="section-title">Helpful Tools</h2>
        <p class="section-description">Download free templates and tools to support your training planning</p>
      </div>
      
      <div class="cqc-resources-grid">
        <?php foreach ($downloadable_resources as $item) : 
          $resource = $item['resource'] ?? null;
          if (!$resource || !is_object($resource)) continue;
          
          $resource_id = (int) $resource->ID;
          $resource_title = get_the_title($resource_id);
          $resource_excerpt = has_excerpt($resource_id) ? get_the_excerpt($resource_id) : '';
          
          // Get icon from ACF field or use resource's default icon
          $custom_icon = $item['custom_icon'] ?? '';
          if (empty($custom_icon)) {
            $custom_icon = get_post_meta($resource_id, '_cta_resource_icon', true);
          }
          if (empty($custom_icon)) {
            $custom_icon = 'fas fa-file';
          }
        ?>
        <div class="cqc-resource-card">
          <div class="cqc-resource-icon">
            <i class="<?php echo esc_attr($custom_icon); ?>" aria-hidden="true"></i>
          </div>
          <h3 class="cqc-resource-title"><?php echo esc_html($resource_title); ?></h3>
          <p class="cqc-resource-description"><?php echo esc_html($resource_excerpt); ?></p>
          <button 
            type="button"
            class="cqc-resource-link resource-download-btn" 
            data-resource-id="<?php echo esc_attr($resource_id); ?>"
            data-resource-name="<?php echo esc_attr($resource_title); ?>"
            style="border: none; background: none; cursor: pointer; padding: 0; font: inherit; color: inherit; text-align: left; width: 100%; display: inline-flex; align-items: center; gap: 8px;"
          >
            Get via Email
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Bottom CTA Section -->
  <section class="cqc-cta-section">
    <div class="container">
      <div class="cqc-cta-content">
        <h2 class="cqc-cta-title">Need Help Planning Your Team's Training?</h2>
        <p class="cqc-cta-description">Our training advisors can help you create a training plan that meets your service needs and CQC requirements.</p>
        <div class="cqc-cta-buttons">
          <a href="<?php echo esc_url(add_query_arg('type', 'training-consultation', get_permalink(get_page_by_path('contact')))); ?>" class="btn btn-primary btn-large">Speak to Our Training Advisors</a>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="btn btn-secondary btn-large">Download Planning Guide</a>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- Schema.org Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Training Guides & Tools for Care Professionals",
  "description": "Comprehensive training guides including course comparisons, training pathways by role, funding information, and group training planning.",
  "url": "<?php echo esc_url(get_permalink()); ?>",
  "mainEntity": {
    "@type": "HowTo",
    "name": "How to Choose the Right Care Training",
    "description": "Guide to selecting appropriate care training courses based on role, setting, and compliance requirements",
    "step": [
      {
        "@type": "HowToStep",
        "name": "Identify Your Role Requirements",
        "text": "Determine mandatory training based on your care role and setting"
      },
      {
        "@type": "HowToStep",
        "name": "Compare Course Options",
        "text": "Review course comparisons between classroom, online, and blended learning formats"
      },
      {
        "@type": "HowToStep",
        "name": "Check Funding Eligibility",
        "text": "Explore available funding options including apprenticeships, grants, and employer support"
      },
      {
        "@type": "HowToStep",
        "name": "Plan Group Training",
        "text": "Coordinate team training with flexible scheduling and group rates"
      }
    ]
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
        "name": "Training Guides",
        "item": "<?php echo esc_url(get_permalink()); ?>"
      }
    ]
  },
  "about": [
    {
      "@type": "Thing",
      "name": "Care Training",
      "description": "Professional development and compliance training for health and social care workers"
    },
    {
      "@type": "Thing",
      "name": "Training Pathways",
      "description": "Structured learning routes for different care roles and specializations"
    }
  ],
  "publisher": {
    "@type": "EducationalOrganization",
    "name": "Continuity Training Academy",
    "url": "<?php echo esc_url(home_url('/')); ?>"
  }
}
</script>


<?php
get_footer();
?>
