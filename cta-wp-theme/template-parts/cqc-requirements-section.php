<?php
/**
 * CQC Training Requirements Section
 * 
 * Displays the 5 fundamental CQC standards with associated training courses
 * 
 * @package CTA_Theme
 */

// Helper function to find course link (if not already defined)
if (!function_exists('cta_find_course_link')) {
  function cta_find_course_link($keywords) {
    static $courses_cache = null;
    
    if ($courses_cache === null) {
      $courses_cache = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'publish',
      ]);
    }
    
    $keywords_lower = strtolower(trim($keywords));
    
    // Exact title matches first
    foreach ($courses_cache as $course) {
      $title_lower = strtolower($course->post_title);
      if ($title_lower === $keywords_lower || strpos($title_lower, $keywords_lower) !== false) {
        return get_permalink($course->ID);
      }
    }
    
    // Keyword mapping for common variations
    $keyword_map = [
      'safeguarding' => ['safeguarding', 'safeguarding adults', 'safeguarding children'],
      'first aid' => ['first aid', 'first aid at work', 'faw'],
      'emergency first aid' => ['emergency first aid', 'efaw', 'efa', 'emergency first aid at work'],
      'moving' => ['moving', 'handling', 'manual handling', 'moving & handling', 'moving and handling'],
      'medication' => ['medication', 'medicines', 'medication management', 'medication competency'],
      'fire safety' => ['fire safety', 'fire'],
      'health & safety' => ['health', 'safety', 'health & safety', 'health and safety'],
      'infection' => ['infection', 'ipc', 'infection control', 'infection prevention'],
      'care certificate' => ['care certificate'],
      'dementia' => ['dementia', 'dementia care'],
      'mca' => ['mental capacity', 'dols', 'mca', 'mental capacity act'],
      'food hygiene' => ['food hygiene', 'food safety'],
    ];
    
    foreach ($keyword_map as $key => $terms) {
      if (in_array($keywords_lower, $terms) || strpos($keywords_lower, $key) !== false) {
        foreach ($courses_cache as $course) {
          $title_lower = strtolower($course->post_title);
          foreach ($terms as $term) {
            if (strpos($title_lower, $term) !== false) {
              return get_permalink($course->ID);
            }
          }
        }
      }
    }
    
    return null;
  }
}

// Helper to create a course link or plain text
if (!function_exists('cta_course_link')) {
  function cta_course_link($course_name, $display_text = null) {
    $link = cta_find_course_link($course_name);
    $text = $display_text ?: $course_name;
    return $link ? '<a href="' . esc_url($link) . '">' . esc_html($text) . '</a>' : esc_html($text);
  }
}

$cqc_standards = [
  [
    'id' => 'safe',
    'title' => 'Safe',
    'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
    'color' => 'blue',
    'description' => 'People are protected from abuse and avoidable harm',
    'courses' => [
      [
        'name' => 'First Aid',
        'explanation' => 'Ensures staff can respond immediately to medical emergencies, preventing avoidable harm and meeting CQC\'s requirement for emergency response capabilities.'
      ],
      [
        'name' => 'Moving & Handling',
        'explanation' => 'Prevents injuries to both staff and service users during transfers, directly addressing CQC\'s focus on reducing avoidable harm.'
      ],
      [
        'name' => 'Safeguarding',
        'explanation' => 'Protects vulnerable adults and children from abuse, neglect, and exploitation—a core CQC requirement for all care settings.'
      ],
      [
        'name' => 'Health & Safety',
        'explanation' => 'Ensures safe working environments and practices, meeting CQC standards for risk management and accident prevention.'
      ],
      [
        'name' => 'Fire Safety',
        'explanation' => 'Critical for protecting lives and property, demonstrating compliance with fire safety regulations that CQC inspectors verify.'
      ],
      [
        'name' => 'Infection Control',
        'explanation' => 'Prevents the spread of infections, essential for protecting vulnerable service users and meeting CQC infection prevention standards.'
      ]
    ]
  ],
  [
    'id' => 'effective',
    'title' => 'Effective',
    'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
    'color' => 'green',
    'description' => 'Care, treatment and support achieves good outcomes and promotes quality of life',
    'courses' => [
      [
        'name' => 'Care Certificate',
        'explanation' => 'Mandatory induction training ensuring all new staff meet minimum competency standards, directly required by CQC for effective care delivery.'
      ],
      [
        'name' => 'Competency Assessments',
        'explanation' => 'Demonstrates staff can perform their roles effectively, providing evidence CQC inspectors require to verify competency.'
      ],
      [
        'name' => 'Medication Management',
        'explanation' => 'Ensures safe and effective administration of medicines, meeting CQC standards for medication competency and reducing medication errors.'
      ],
      [
        'name' => 'Clinical Skills',
        'explanation' => 'Provides evidence-based clinical knowledge and skills, ensuring care delivery meets professional standards CQC expects.'
      ]
    ]
  ],
  [
    'id' => 'caring',
    'title' => 'Caring',
    'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
    'color' => 'pink',
    'description' => 'Staff involve and treat people with compassion, kindness, dignity and respect',
    'courses' => [
      [
        'name' => 'Dignity & Respect',
        'explanation' => 'Ensures staff understand how to maintain service users\' dignity and treat them with respect, a fundamental CQC expectation.'
      ],
      [
        'name' => 'Communication Skills',
        'explanation' => 'Enables effective, compassionate communication with service users and families, demonstrating CQC\'s caring standard in practice.'
      ],
      [
        'name' => 'Person-Centred Care',
        'explanation' => 'Focuses care on individual needs and preferences, aligning with CQC\'s requirement for personalized, compassionate care delivery.'
      ],
      [
        'name' => 'Equality & Diversity',
        'explanation' => 'Ensures fair, non-discriminatory care that respects individual differences, meeting CQC standards for inclusive service provision.'
      ]
    ]
  ],
  [
    'id' => 'responsive',
    'title' => 'Responsive',
    'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    'color' => 'amber',
    'description' => 'Services are organised so they meet people\'s needs',
    'courses' => [
      [
        'name' => 'Dementia Care',
        'explanation' => 'Provides specialized knowledge for supporting people with dementia, ensuring services are responsive to their specific needs and preferences.'
      ],
      [
        'name' => 'Learning Disabilities',
        'explanation' => 'Equips staff to understand and respond to the unique needs of people with learning disabilities, meeting CQC expectations for responsive care.'
      ],
      [
        'name' => 'MCA & DoLS',
        'explanation' => 'Ensures services respect mental capacity and use least restrictive practices, demonstrating responsiveness to individual rights and legal requirements.'
      ],
      [
        'name' => 'End of Life Care',
        'explanation' => 'Provides compassionate, responsive care for people at end of life, meeting CQC standards for supporting people through this sensitive time.'
      ]
    ]
  ],
  [
    'id' => 'well-led',
    'title' => 'Well-Led',
    'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>',
    'color' => 'purple',
    'description' => 'Leadership, management and governance assure high-quality care',
    'courses' => [
      [
        'name' => 'Leadership & Management',
        'explanation' => 'Develops effective leadership skills that ensure high-quality service delivery and governance, directly supporting CQC\'s well-led standard.'
      ],
      [
        'name' => 'Supervision Skills',
        'explanation' => 'Ensures managers can effectively support and develop staff, demonstrating strong leadership and governance that CQC expects.'
      ],
      [
        'name' => 'Information Handling',
        'explanation' => 'Ensures proper data management and confidentiality, meeting CQC requirements for information governance and record-keeping.'
      ],
      [
        'name' => 'Governance',
        'explanation' => 'Provides frameworks for quality assurance and compliance, directly supporting CQC\'s well-led standard through effective governance systems.'
      ]
    ]
  ]
];
?>

<section class="cqc-requirements-section" aria-labelledby="cqc-requirements-heading">
  <div class="container">
    
    <!-- Section Header -->
    <div class="cqc-requirements-header">
      <h2 id="cqc-requirements-heading" class="cqc-requirements-title">
        Understanding CQC Training Requirements
      </h2>
      <p class="cqc-requirements-description">
        The Care Quality Commission (CQC) sets standards for health and social care services in England. 
        Our CQC-compliant training courses help care providers meet these requirements and prepare for inspections.
      </p>
    </div>

    <!-- Standards Grid -->
    <div class="cqc-standards-grid">
      <?php foreach ($cqc_standards as $standard) : ?>
      <article class="cqc-standard-card cqc-standard-<?php echo esc_attr($standard['color']); ?>">
        <!-- Card Header -->
        <div class="cqc-standard-header">
          <div class="cqc-standard-icon" aria-hidden="true">
            <?php echo $standard['icon']; ?>
          </div>
          <div class="cqc-standard-title-wrapper">
            <h3 class="cqc-standard-title"><?php echo esc_html($standard['title']); ?></h3>
            <?php if (!empty($standard['description'])) : ?>
            <p class="cqc-standard-description"><?php echo esc_html($standard['description']); ?></p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Course List -->
        <ul class="cqc-course-list">
        <?php foreach ($standard['courses'] as $course) : 
          $course_name = is_array($course) ? $course['name'] : $course;
          $course_explanation = is_array($course) && isset($course['explanation']) ? $course['explanation'] : '';
        ?>
        <li class="cqc-course-item">
          <svg class="cqc-course-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <div class="cqc-course-content">
            <span class="cqc-course-name"><?php echo cta_course_link($course_name); ?></span>
            <?php if ($course_explanation) : ?>
            <p class="cqc-course-explanation"><?php echo esc_html($course_explanation); ?></p>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        </ul>
      </article>
      <?php endforeach; ?>
    </div>

  </div>
</section>
