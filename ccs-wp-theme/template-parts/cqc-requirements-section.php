<?php
/**
 * CQC Training Requirements Section
 * 
 * Displays the 5 fundamental CQC standards with associated training courses
 * 
 * @package ccs-theme
 */

if (!function_exists('ccs_find_course_link')) {
  function ccs_find_course_link($keywords) {
    static $courses_cache = null;
    
    if ($courses_cache === null) {
      $courses_cache = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'publish',
      ]);
    }
    
    $keywords_lower = strtolower(trim($keywords));

    foreach ($courses_cache as $course) {
      $title_lower = strtolower($course->post_title);
      if ($title_lower === $keywords_lower || strpos($title_lower, $keywords_lower) !== false) {
        return get_permalink($course->ID);
      }
    }

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

if (!function_exists('ccs_course_link')) {
  function ccs_course_link($course_name, $display_text = null) {
    $link = ccs_find_course_link($course_name);
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
        'explanation' => 'Emergency response training for medical emergencies.'
      ],
      [
        'name' => 'Moving & Handling',
        'explanation' => 'Prevents injuries during transfers and lifts.'
      ],
      [
        'name' => 'Safeguarding',
        'explanation' => 'Protects vulnerable people from abuse and neglect.'
      ],
      [
        'name' => 'Health & Safety',
        'explanation' => 'Safe working practices and risk management.'
      ],
      [
        'name' => 'Fire Safety',
        'explanation' => 'Life safety and fire prevention compliance.'
      ],
      [
        'name' => 'Infection Control',
        'explanation' => 'Prevents infection spread in care settings.'
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
        'explanation' => 'Mandatory induction training for new care staff.'
      ],
      [
        'name' => 'Competency Assessments',
        'explanation' => 'Verifies staff can perform their roles effectively.'
      ],
      [
        'name' => 'Medication Management',
        'explanation' => 'Safe administration and management of medicines.'
      ],
      [
        'name' => 'Clinical Skills',
        'explanation' => 'Evidence-based clinical knowledge and practice.'
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
        'explanation' => 'Maintaining dignity and treating people with respect.'
      ],
      [
        'name' => 'Communication Skills',
        'explanation' => 'Effective, compassionate communication with service users.'
      ],
      [
        'name' => 'Person-Centred Care',
        'explanation' => 'Care focused on individual needs and preferences.'
      ],
      [
        'name' => 'Equality & Diversity',
        'explanation' => 'Fair, non-discriminatory care for all.'
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
        'explanation' => 'Specialized support for people with dementia.'
      ],
      [
        'name' => 'Learning Disabilities',
        'explanation' => 'Understanding and responding to unique needs.'
      ],
      [
        'name' => 'MCA & DoLS',
        'explanation' => 'Mental capacity and least restrictive practices.'
      ],
      [
        'name' => 'End of Life Care',
        'explanation' => 'Compassionate care at end of life.'
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
        'explanation' => 'Effective leadership for quality service delivery.'
      ],
      [
        'name' => 'Supervision Skills',
        'explanation' => 'Supporting and developing staff effectively.'
      ],
      [
        'name' => 'Information Handling',
        'explanation' => 'Data management and confidentiality compliance.'
      ],
      [
        'name' => 'Governance',
        'explanation' => 'Quality assurance and compliance frameworks.'
      ]
    ]
  ]
];
?>

<section class="cqc-requirements-section" aria-labelledby="cqc-requirements-heading">
  <div class="container">
    <div class="cqc-requirements-header">
      <h2 id="cqc-requirements-heading" class="cqc-requirements-title">
        CQC Training Requirements
      </h2>
      <p class="cqc-requirements-description">
        CQC sets standards for health and social care services in England. Our CQC-compliant training courses help care providers meet these requirements and prepare for inspections.
        <a href="https://www.cqc.org.uk/about-us/fundamental-standards" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline; text-decoration-color: rgba(155, 133, 96, 0.4);">Learn more about CQC fundamental standards</a>.
      </p>
    </div>

    <div class="cqc-standards-grid">
      <?php foreach ($cqc_standards as $standard) : ?>
      <article class="cqc-standard-card cqc-standard-<?php echo esc_attr($standard['color']); ?>">
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
            <span class="cqc-course-name"><?php echo ccs_course_link($course_name); ?></span>
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
