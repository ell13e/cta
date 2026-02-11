<?php
/**
 * Template Name: About Us
 *
 * @package ccs-theme
 */

get_header();

$meta_title = 'About Continuity of Care Services | Care Training Providers Kent';
$meta_description = 'Professional care training in Kent since 2020. CQC-compliant, CPD-accredited courses delivered by working care professionals. 4.6★ rated on Trustpilot.';
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

$hero_title = get_field('hero_title') ?: 'About Our Care Training in Kent';
$hero_subtitle = get_field('hero_subtitle') ?: 'CQC-compliant, CPD-accredited care sector training in Kent since 2020';

$mission_title = get_field('mission_title') ?: 'Our Care Training Approach';
$mission_text_raw = get_field('mission_text');
$mission_text_default = [
  "Continuity of Care Services's ethos reflects our goal: to urge businesses to invest in their staff, and individuals to invest in themselves.",
  "We position ourselves as <strong>'the external training room'</strong> that becomes part of your organisation. We don't just deliver courses, we partner with you.",
  "When working with new care providers, we take time to understand your policies and procedures, ensuring our training complements how your organisation operates. We tailor our training to align perfectly with your needs, creating a seamless integration with your existing processes and standards."
];
// ACF free: mission_text is a single WYSIWYG (string). Legacy: repeater returned array of rows with 'paragraph' key.
if (is_array($mission_text_raw) && !empty($mission_text_raw)) {
  $mission_text = [];
  foreach ($mission_text_raw as $row) {
    $mission_text[] = is_array($row) && isset($row['paragraph']) ? $row['paragraph'] : (is_string($row) ? $row : '');
  }
  $mission_text = array_filter($mission_text) ?: $mission_text_default;
} elseif (is_string($mission_text_raw) && trim($mission_text_raw) !== '') {
  $mission_text = $mission_text_raw;
} else {
  $mission_text = $mission_text_default;
}
$mission_image = get_field('mission_image');
if (empty($mission_image)) {
  $possible_images = [
    'stock_photos/03_ABOUT_US_PAGE/about_epilepsy01.webp',
    'stock_photos/03_ABOUT_US_PAGE/about_epilepsy01-800w.webp',
    'stock_photos/03_ABOUT_US_PAGE/about_page01.webp',
  ];
  foreach ($possible_images as $img_path) {
    $img_url = ccs_image($img_path);
    $mission_image = $img_url;
    break;
  }
}

$values_title = get_field('values_title') ?: 'Core Care Training Values';
$values_subtitle = get_field('values_subtitle') ?: 'These principles guide everything we do and shape the experience we provide to our learners.';

$stats_raw = get_field('stats');
if (!is_array($stats_raw) || count($stats_raw) === 0) {
    $stats_raw = get_post_meta(get_the_ID(), 'stats', true);
}
$stats = is_array($stats_raw) && count($stats_raw) > 0 ? $stats_raw : [
  ['number' => '40+', 'label' => 'Courses Offered'],
  ['number' => '2020', 'label' => 'Established'],
  ['number' => '4.6/5', 'label' => 'Trustpilot Rating'],
  ['number' => '100%', 'label' => 'CQC-Compliant'],
];

$team_title = get_field('team_title') ?: 'Expert Care Training Team';
$team_subtitle = get_field('team_subtitle') ?: 'Experienced professionals dedicated to your development';

$ccs_title = get_field('ccs_title') ?: 'Start Your Care Training Today';
$ccs_text = get_field('ccs_text') ?: 'Join hundreds of care professionals who trust us for their training needs. Get expert CQC compliance training with CPD-accredited certificates.';
?>

<main id="main-content">
  <section class="group-hero-section" aria-labelledby="about-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
          </li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <span class="breadcrumb-current" aria-current="page">About Us</span>
          </li>
        </ol>
      </nav>
      <h1 id="about-heading" class="hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
    </div>
  </section>

  <section class="about-mission-new">
    <div class="container">
      <div class="about-mission-grid-new">
        <div class="about-mission-text-new">
          <h2><?php echo esc_html($mission_title); ?></h2>
          <?php
          if (is_array($mission_text)) {
            foreach ($mission_text as $paragraph) {
              $paragraph = stripslashes((string) $paragraph);
              if ($paragraph === '') {
                continue;
              }
              echo wp_kses_post(strpos($paragraph, '<') !== false ? $paragraph : '<p>' . $paragraph . '</p>');
            }
          } else {
            echo wp_kses_post(stripslashes((string) $mission_text));
          }
          ?>
        </div>
        <div class="about-mission-image-new">
          <img src="<?php echo esc_url($mission_image); ?>" 
               alt="Continuity of Care Services - Our Ethos"
               width="1600"
               height="1200"
               loading="eager">
        </div>
      </div>
    </div>
  </section>

  <section class="about-values-new">
    <div class="container">
      <div class="about-values-header">
        <h2><?php echo esc_html($values_title); ?></h2>
        <p><?php echo esc_html($values_subtitle); ?></p>
      </div>
      
      <div class="about-values-grid-new" id="valuesGrid">
        <?php
$values_raw = get_field('values');
if (!is_array($values_raw) || count($values_raw) === 0) {
    $values_raw = get_post_meta(get_the_ID(), 'values', true);
}
$values = is_array($values_raw) && count($values_raw) > 0 ? $values_raw : [
  [
    'icon' => 'fas fa-hands-helping',
            'title' => 'Hands-On Care Training',
            'description' => 'Practical training that builds real competence, regardless of experience or background.'
          ],
          [
            'icon' => 'fas fa-users',
            'title' => 'Equality & Diversity in Training',
            'description' => 'Anyone can reach success. The key is knowledge. We make training accessible to everyone.'
          ],
          [
            'icon' => 'fas fa-graduation-cap',
            'title' => 'Flexible Care Training',
            'description' => 'Everyone learns differently. Our training adapts. Your team walks away with skills they can actually use.'
          ],
          [
            'icon' => 'fas fa-handshake',
            'title' => 'Partnership Care Training',
            'description' => 'We\'re your external training room. We learn your policies and procedures, then deliver training that fits how you actually work.'
          ]
        ];

foreach ($values as $value) :
        ?>
        <div class="about-value-card-new">
          <div class="about-value-icon-new">
            <i class="<?php echo esc_attr($value['icon']); ?>" aria-hidden="true"></i>
          </div>
          <h3><?php echo esc_html($value['title']); ?></h3>
          <p><?php echo esc_html($value['description']); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="about-stats-new">
    <div class="container">
      <div class="about-stats-grid-new" id="statsGrid">
        <?php foreach ($stats as $stat) : ?>
        <div class="about-stat-item-new">
          <div class="about-stat-number-new"><?php echo esc_html($stat['number']); ?></div>
          <div class="about-stat-label-new"><?php echo esc_html($stat['label']); ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="team-section-new" aria-labelledby="team-heading">
    <div class="container">
      <div class="team-section-header-new">
        <h2 id="team-heading" class="team-section-title-new"><?php echo esc_html($team_title); ?></h2>
        <p class="team-section-description-new"><?php echo esc_html($team_subtitle); ?></p>
      </div>

      <?php
      $director_posts = get_posts([
        'post_type' => 'team_member',
        'posts_per_page' => -1,
        'meta_query' => [
          ['key' => '_team_type', 'value' => 'director'],
        ],
        'orderby' => 'menu_order',
        'order' => 'ASC',
      ]);
      
      if (!empty($director_posts)) {
        $directors = [];
        foreach ($director_posts as $dp) {
          $directors[] = [
            'name' => $dp->post_title,
            'role' => get_post_meta($dp->ID, '_team_role', true) ?: 'Company Director',
            'experience' => get_post_meta($dp->ID, '_team_experience', true) ?: 'Est. 2020',
            'image' => has_post_thumbnail($dp->ID) ? get_the_post_thumbnail_url($dp->ID, 'medium') : ccs_image('instructors/' . sanitize_title($dp->post_title) . '-400w.webp'),
            'teaser' => $dp->post_excerpt,
            'bio' => $dp->post_content,
            'specialisations' => get_post_meta($dp->ID, '_team_specialisations', true) ?: [],
          ];
        }
      } else {
        $directors = get_field('directors') ?: [
        [
          'name' => 'Victoria Walker',
          'role' => 'Company Director',
          'experience' => 'Est. 2020',
          'image' => ccs_image('instructors/victoria_walker-400w.webp'),
          'teaser' => 'I have been in the Health and Social Care Sector since 2008 where I was a PA (Personal Assistant) for a disabled husband and wife. From working with them it became apparent that training that was unique to their needs was instrumental in giving a better quality of support...',
          'bio' => '<p>I have been in the Health and Social Care Sector since 2008 where I was a PA (Personal Assistant) for a disabled husband and wife. From working with them it became apparent that training that was unique to their needs was instrumental in giving a better quality of support; therefore, I do believe training is one of the most important things any care company can invest in.</p><p>I have known Jennifer Boorman since the first day of owning a care company and have always enjoyed her knowledge and enthusiasm when delivering training to our staff, with nothing ever being too much trouble to help us when we needed it or adapting our training package to suit the needs of our business.</p><p>Therefore, during the recent pandemic, while a lot of care companies looked online to give their teams training, we collaborated with Jennifer to create Continuity of Care Services.</p><p>To compliment our care training, I felt it important to also look at delivering clinical training at an affordable cost to companies as this was a big issue while I was working in the care sector. We have therefore had a Registered Nurse delivering clinical training packages.</p><p>Between the management team, we don\'t intend to stop at delivering the above. We will continue to evolve the company to be able to deliver apprenticeships starting in the care industry and venturing out to other industries.</p>',
          'specialisations' => ['Health & Social Care Training', 'Care Company Training Needs', 'Clinical Training at Affordable Costs', 'Training Package Development', 'Apprenticeship Development']
        ],
        [
          'name' => 'Chloe Roberts',
          'role' => 'Company Director',
          'experience' => 'Est. 2020',
          'image' => ccs_image('instructors/chloe_roberts-400w.webp'),
          'teaser' => 'I have been working in the Health Care Sector since 2015. From when I left school, training played a big part in all my roles. I have carried out my apprenticeships from Level 2 all the way to Level 5...',
          'bio' => '<p>I have been working in the Health Care Sector since 2015.</p><p>From when I left school, training played a big part in all my roles. I have carried out my apprenticeships from Level 2 all the way to Level 5. All of my roles have been in the Health Care Sector either as part of a GP Practice or working for a Domiciliary Care Provider.</p><p>For the last 3.5 years I have been working closely with our Lead Trainer, Jennifer to build a Training Department for an organisation and to give others the opportunity to enhance their knowledge undertaking their Health and Social Care qualifications and working then towards their Care Certificate.</p><p>All of us at Continuity of Care Services love watching individuals achieve their career goals through our training programme. Whether that be becoming specialists in certain areas of Health Care, becoming a trainer or even going into Management roles.</p><p>Having worked my way up through the apprenticeship programme, having a training organisation that you <strong>know, and trust</strong> is so important and one of our core values.</p>',
          'specialisations' => ['Health & Social Care Apprenticeships (Level 2-5)', 'Training Department Development', 'Care Certificate Training', 'Career Development & Progression', 'GP Practice & Domiciliary Care Training']
        ]
      ];
      }

      $trainer_posts = get_posts([
        'post_type' => 'team_member',
        'posts_per_page' => -1,
        'meta_query' => [
          ['key' => '_team_type', 'value' => 'trainer'],
        ],
        'orderby' => 'menu_order',
        'order' => 'ASC',
      ]);
      
      if (!empty($trainer_posts)) {
        $trainers = [];
        foreach ($trainer_posts as $tp) {
          $trainers[] = [
            'name' => $tp->post_title,
            'role' => get_post_meta($tp->ID, '_team_role', true) ?: 'Company Trainer',
            'experience' => get_post_meta($tp->ID, '_team_experience', true) ?: '',
            'image' => has_post_thumbnail($tp->ID) ? get_the_post_thumbnail_url($tp->ID, 'medium') : ccs_image('instructors/' . sanitize_title($tp->post_title) . '-400w.webp'),
            'teaser' => $tp->post_excerpt,
            'bio' => $tp->post_content,
            'specialisations' => get_post_meta($tp->ID, '_team_specialisations', true) ?: [],
          ];
        }
      } else {
        $trainers = get_field('trainers') ?: [
        [
          'name' => 'Adele Smith',
          'role' => 'Company Trainer',
          'experience' => '7+ Years Experience',
          'image' => ccs_image('instructors/adele_smith-400w.webp'),
          'teaser' => 'Hi there! My name is Adele, and I started working for Continuity Care Services in August as a company trainer...',
          'bio' => '<p>Hi there! My name is Adele, and I started working for Continuity Care Services in August as a company trainer for Continuity of Care Services. I studied for my PTLLS in 2014 and started working in the care industry as a freelance trainer in 2017, working in and around Kent empowering individuals to enhance their skills. Before CCS, I was based in a residential and nursing setting supporting staff and providing in-house training, scheduling training sessions, identifying company training needs, compliance and audits, as well as care of the elderly in the private health sector.</p><p>I continually update my own knowledge and skills through CPD and other training courses to ensure I am up to date, ensuring compliance with regulatory requirements, and I am a member of the Association of Healthcare Trainers.</p>',
          'specialisations' => ['Staff Training and Development', 'Training Needs Assessment', 'Compliance and Audits', 'Care of the Elderly', 'Regulatory Requirements']
        ],
        [
          'name' => 'Jennifer Boorman',
          'role' => 'Lead Trainer',
          'experience' => '30+ Years Experience',
          'image' => ccs_image('instructors/jen_boorman-400w.webp'),
          'teaser' => 'I am a professionally qualified healthcare professional, teacher and trainer with a comprehensive healthcare, teaching and training background...',
          'bio' => '<p>I am a professionally qualified healthcare professional, teacher and trainer with a comprehensive healthcare, teaching and training background. My experience and knowledge has been gained in demanding and challenging work environments over almost 30 years. I love and enjoy the challenges of facilitating group and individual training sessions, I believe I have the aptitude, approach and attitude to encourage learners to succeed. The more encouragement I can deliver to learners the more satisfaction learners will have in themselves and trainers have a sense of being part of their success.</p>',
          'specialisations' => ['Group and Individual Training Facilitation', 'Healthcare Training and Development', 'Training Delivery and Learner Support', 'Professional Development and Training', 'Healthcare Education']
        ],
        [
          'name' => 'Sharon Cudlip',
          'role' => 'Healthcare Educator',
          'experience' => '40+ Years Experience',
          'image' => ccs_image('instructors/sharon_cudlip-400w.webp'),
          'teaser' => 'Sharon brings over 40 years of experience in health and social care as a Registered Adult Nurse...',
          'bio' => '<p>Sharon brings over 40 years of experience in health and social care as a Registered Adult Nurse and over 28 years of teaching and mentoring. She is an intuitive and reflective educator with a strong track record of delivering expert support and development to healthcare and social care professionals and non-healthcare participants during workshops and training sessions. She lives with the gift and frustration of dyslexia.</p><p>She enjoys facilitating by embracing her dyslexia as a friend rather than a foe. Having learnt to embrace the adventures and spirit of her learning and teaching style, she is able to communicate effectively to participants during the various sessions taught and facilitated, encouraging them to ignite and engage their spirit of inquiry and professional curiosity.</p><p>As an experienced facilitator, she delivers on a variety of subject areas and is also a health and social care educator. She brings a wide range of experience to each face-to-face session.</p><p>She strives to create impactful training and learning experiences and ensures the learning environment is one built upon trust, hope, sense of worth, and competence.</p>',
          'specialisations' => ['Health & Social Care Education', 'Teaching and Mentoring', 'Workshop Facilitation', 'Professional Development', 'Inclusive Learning Environments']
        ]
      ];
      }
      ?>

      <div class="team-grid-new">
        <div class="team-directors-row">
          <div class="team-directors-cards">
            <?php foreach ($directors as $director) : ?>
            <article class="team-card-new" data-role="director">
              <div class="team-card-preview">
                <div class="team-photo-wrapper">
                  <img src="<?php echo esc_url($director['image']); ?>" 
                       alt="<?php echo esc_attr($director['name']); ?>" 
                       class="team-photo-new" 
                       width="600" 
                       height="600" 
                       loading="lazy">
                </div>
                <div class="team-info-preview">
                  <h3 class="team-name-new"><?php echo esc_html($director['name']); ?></h3>
                  <p class="team-role-new"><?php echo esc_html($director['role']); ?></p>
                  <div class="team-experience-new">
                    <i class="fas fa-award" aria-hidden="true"></i>
                    <span><?php echo esc_html($director['experience']); ?></span>
                  </div>
                  <p class="team-teaser"><?php echo esc_html($director['teaser']); ?></p>
                  <button class="team-read-more-btn" aria-label="Read more about <?php echo esc_attr($director['name']); ?>">
                    Read More
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                  </button>
                </div>
              </div>
              <div class="team-full-content" style="display: none;">
                <div class="team-full-bio">
                  <?php echo wp_kses_post($director['bio']); ?>
                </div>
                <?php if (!empty($director['specialisations'])) : ?>
                <div class="team-specialisations-new">
                  <p class="team-specialisations-label-new">Specialisations</p>
                  <ul class="team-badges-new">
                    <?php foreach ($director['specialisations'] as $spec) : ?>
                    <li><?php echo esc_html($spec); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
                <?php endif; ?>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="team-trainers-row">
          <div class="team-trainers-grid">
            <?php foreach ($trainers as $trainer) : ?>
            <article class="team-card-new">
              <div class="team-card-preview">
                <div class="team-photo-wrapper">
                  <img src="<?php echo esc_url($trainer['image']); ?>" 
                       alt="<?php echo esc_attr($trainer['name']); ?>" 
                       class="team-photo-new" 
                       width="600" 
                       height="600" 
                       loading="lazy">
                </div>
                <div class="team-info-preview">
                  <h3 class="team-name-new"><?php echo esc_html($trainer['name']); ?></h3>
                  <p class="team-role-new"><?php echo esc_html($trainer['role']); ?></p>
                  <div class="team-experience-new">
                    <i class="fas fa-award" aria-hidden="true"></i>
                    <span><?php echo esc_html($trainer['experience']); ?></span>
                  </div>
                  <p class="team-teaser"><?php echo esc_html($trainer['teaser']); ?></p>
                  <button class="team-read-more-btn" aria-label="Read more about <?php echo esc_attr($trainer['name']); ?>">
                    Read More
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                  </button>
                </div>
              </div>
              <div class="team-full-content" style="display: none;">
                <div class="team-full-bio">
                  <?php echo wp_kses_post($trainer['bio']); ?>
                </div>
                <?php if (!empty($trainer['specialisations'])) : ?>
                <div class="team-specialisations-new">
                  <p class="team-specialisations-label-new">Specialisations</p>
                  <ul class="team-badges-new">
                    <?php foreach ($trainer['specialisations'] as $spec) : ?>
                    <li><?php echo esc_html($spec); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
                <?php endif; ?>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div id="team-modal" class="team-modal" role="dialog" aria-modal="true" aria-labelledby="team-modal-title" aria-hidden="true">
    <div class="team-modal-backdrop" aria-hidden="true"></div>
    <div class="team-modal-container">
      <button class="team-modal-close" aria-label="Close modal">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M18 6L6 18M6 6l12 12"></path>
        </svg>
      </button>
      <div class="team-modal-content" id="team-modal-content"></div>
    </div>
  </div>

  <section class="about-cta-new">
    <div class="container">
      <div class="about-cta-content-new">
        <h2><?php echo esc_html($ccs_title); ?></h2>
        <p><?php echo esc_html($ccs_text); ?></p>
        <div class="about-cta-buttons-new">
          <a href="<?php echo esc_url(ccs_page_url('contact') . '?type=book-course'); ?>" class="btn btn-primary">Book Your Training</a>
          <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="btn btn-secondary">View All Courses</a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>
