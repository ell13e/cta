<?php
/**
 * Template Name: Locations Index
 * Template Post Type: page
 * 
 * Main locations landing page showing all training locations
 *
 * @package CTA_Theme
 */

get_header();

// SEO Meta Tags
$meta_title = 'Health & Social Care Training Locations | UK-Wide Training | CTA';
$meta_description = 'CQC-compliant care training at our Maidstone centre or on-site anywhere in the UK. Serving Kent providers with flexible training options for care teams.';
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

// Define all locations
$locations = [
    [
        'name' => 'Maidstone',
        'slug' => 'maidstone',
        'description' => 'Our primary training centre at The Maidstone Studios. Central location for Mid Kent care providers.',
        'areas' => 'Maidstone, Aylesford, Bearsted, Loose, East Farleigh',
        'travel_time' => 'On-site',
        'page_url' => get_permalink(get_page_by_path('locations/maidstone')),
    ],
    [
        'name' => 'Medway',
        'slug' => 'medway',
        'description' => '20 minutes from Chatham, Gillingham, Rochester and Strood via Medway Valley Line.',
        'areas' => 'Chatham, Gillingham, Rochester, Strood, Rainham',
        'travel_time' => '20 mins by train',
        'page_url' => get_permalink(get_page_by_path('locations/medway')),
    ],
    [
        'name' => 'Canterbury & East Kent',
        'slug' => 'canterbury',
        'description' => '35-40 minutes from Canterbury via A2/M2. Serving all East Kent care providers.',
        'areas' => 'Canterbury, Whitstable, Herne Bay, Margate, Deal',
        'travel_time' => '35-40 mins by car',
        'page_url' => get_permalink(get_page_by_path('locations/canterbury')),
    ],
    [
        'name' => 'Ashford & South Kent',
        'slug' => 'ashford',
        'description' => '25 minutes from Ashford via M20. On-site training across Romney Marsh and rural areas.',
        'areas' => 'Ashford, Tenterden, New Romney, Hythe, Folkestone',
        'travel_time' => '25 mins via M20',
        'page_url' => get_permalink(get_page_by_path('locations/ashford')),
    ],
    [
        'name' => 'Tunbridge Wells & West Kent',
        'slug' => 'tunbridge-wells',
        'description' => 'Accessible via Arriva Route 7 or A21/M20. Serving West Kent care providers.',
        'areas' => 'Tunbridge Wells, Southborough, Tonbridge, Sevenoaks',
        'travel_time' => '30 mins by car',
        'page_url' => get_permalink(get_page_by_path('locations/tunbridge-wells')),
    ],
];

// Enhanced Schema
$site_url = home_url();
$page_url = get_permalink();

// Build comprehensive schema graph
$schema_data = [
    '@context' => 'https://schema.org',
    '@graph' => [
        // Organization
        [
            '@type' => 'EducationalOrganization',
            '@id' => $site_url . '/#organization',
            'name' => 'Continuity Training Academy',
            'url' => $site_url . '/',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => get_template_directory_uri() . '/assets/img/logo/long_logo-400w.webp',
                'width' => 400,
                'height' => 100,
            ],
            'description' => 'Professional care sector training in Kent and across the UK. CQC-compliant, CPD-accredited courses since 2020.',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'The Maidstone Studios, New Cut Road',
                'addressLocality' => 'Maidstone',
                'addressRegion' => 'Kent',
                'postalCode' => 'ME14 5NZ',
                'addressCountry' => 'GB',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => '51.264494',
                'longitude' => '0.545844',
            ],
            'telephone' => '+441622587343',
            'email' => 'info@continuitytrainingacademy.co.uk',
            'priceRange' => '££',
            'sameAs' => [
                'https://www.trustpilot.com/review/continuitytrainingacademy.co.uk',
                'https://www.facebook.com/continuitytraining',
                'https://www.linkedin.com/company/continuity-training-academy-cta',
                'https://www.instagram.com/continuitytrainingacademy',
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.6',
                'reviewCount' => '20',
                'bestRating' => '5',
                'worstRating' => '5',
            ],
            'areaServed' => [
                ['@type' => 'Country', 'name' => 'United Kingdom'],
                ['@type' => 'State', 'name' => 'Kent'],
                ['@type' => 'City', 'name' => 'Maidstone'],
                ['@type' => 'City', 'name' => 'Medway'],
                ['@type' => 'City', 'name' => 'Canterbury'],
                ['@type' => 'City', 'name' => 'Ashford'],
                ['@type' => 'City', 'name' => 'Tunbridge Wells'],
            ],
        ],
        // Service
        [
            '@type' => 'Service',
            '@id' => $page_url . '#service',
            'serviceType' => 'Health and Social Care Training',
            'name' => 'UK-Wide Care Training Services',
            'description' => 'CQC-compliant health and social care training delivered at our Maidstone training centre or on-site at care facilities across the United Kingdom.',
            'provider' => [
                '@id' => $site_url . '/#organization',
            ],
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'United Kingdom',
            ],
            'audience' => [
                '@type' => 'Audience',
                'audienceType' => 'Care Providers, Healthcare Workers, Domiciliary Care Staff, Care Home Staff',
            ],
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => 'Care Training Courses',
                'itemListElement' => [
                    [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'Course',
                            'name' => 'Emergency First Aid at Work',
                            'provider' => ['@id' => $site_url . '/#organization'],
                        ],
                    ],
                    [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'Course',
                            'name' => 'Medication Competency & Management',
                            'provider' => ['@id' => $site_url . '/#organization'],
                        ],
                    ],
                    [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'Course',
                            'name' => 'Moving & Handling',
                            'provider' => ['@id' => $site_url . '/#organization'],
                        ],
                    ],
                    [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'Course',
                            'name' => 'Care Certificate',
                            'provider' => ['@id' => $site_url . '/#organization'],
                        ],
                    ],
                ],
            ],
        ],
        // LocalBusiness (Main Training Centre)
        [
            '@type' => 'LocalBusiness',
            '@id' => $site_url . '/locations/#maidstone-centre',
            'name' => 'Continuity Training Academy - Maidstone Training Centre',
            'image' => get_template_directory_uri() . '/assets/img/logo/long_logo-400w.webp',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'The Maidstone Studios, New Cut Road',
                'addressLocality' => 'Maidstone',
                'addressRegion' => 'Kent',
                'postalCode' => 'ME14 5NZ',
                'addressCountry' => 'GB',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => '51.264494',
                'longitude' => '0.545844',
            ],
            'telephone' => '+441622587343',
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    'opens' => '09:00',
                    'closes' => '17:00',
                ],
            ],
        ],
        // BreadcrumbList
        [
            '@type' => 'BreadcrumbList',
            '@id' => $page_url . '#breadcrumb',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => $site_url . '/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Training Locations',
                    'item' => $page_url,
                ],
            ],
        ],
        // WebPage
        [
            '@type' => 'WebPage',
            '@id' => $page_url . '#webpage',
            'url' => $page_url,
            'name' => 'Health & Social Care Training Locations | UK-Wide Training',
            'description' => 'CQC-compliant care training at our Maidstone centre or on-site anywhere in the UK. Serving Kent providers with flexible training options.',
            'isPartOf' => [
                '@id' => $site_url . '/#website',
            ],
            'about' => [
                '@id' => $site_url . '/#organization',
            ],
            'breadcrumb' => [
                '@id' => $page_url . '#breadcrumb',
            ],
        ],
    ],
];

echo '<script type="application/ld+json">' . wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
?>

<main id="main-content" class="site-main">
  <!-- Hero Section -->
  <section class="group-hero-section" aria-labelledby="locations-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><span class="breadcrumb-current" aria-current="page">Training Locations</span></li>
        </ol>
      </nav>
      <h1 id="locations-heading" class="hero-title">Training Wherever You Are</h1>
      <p class="hero-subtitle">At our Maidstone centre or on-site at your care facility anywhere in the UK</p>
    </div>
  </section>
  
  <!-- Opening -->
  <section class="content-section">
    <div class="container">
      <div class="content-intro">
        <p class="content-lead">
          Hiring's down 56% year-on-year. One in three providers is considering leaving the sector. You can't fix the funding crisis. But you can keep the team you've got.
        </p>
        <p class="content-lead">
          Trained staff feel competent. Competent staff stay longer. That's not marketing speak: it's the only retention strategy that's working right now.
        </p>
      </div>
    </div>
  </section>

  <!-- Where We Work -->
  <section class="content-section bg-cream" aria-labelledby="where-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="where-heading" class="section-title">Two Ways to Train</h2>
        <p class="section-subtitle">At our Maidstone centre or we come to you</p>
      </div>
      
      <div class="locations-delivery-grid">
        <div class="locations-delivery-card">
          <div class="locations-delivery-icon">
            <i class="fas fa-building" aria-hidden="true"></i>
          </div>
          <h3 class="locations-delivery-title">At Our Centre</h3>
          <p class="locations-delivery-address">The Maidstone Studios<br>New Cut Road<br>Maidstone, Kent<br>ME14 5NZ</p>
          
          <ul class="locations-delivery-features">
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Free parking at the venue</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Less than 10 minutes from Maidstone West station</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Bus routes 7, 71, and 72 stop nearby</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Purpose-built training rooms</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> All equipment on-site</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Quiet environment away from care work</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Refreshments included</li>
          </ul>
          
          <div class="locations-delivery-highlight">
            <strong>Works best for:</strong>
            <p>Teams of 2-10 who can spare staff for a day. Most cost-effective option if you're in Kent or the South East.</p>
          </div>
          
          <div class="locations-delivery-actions">
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('locations/maidstone'))); ?>" class="btn btn-primary">View Maidstone Centre</a>
          </div>
        </div>
        
        <div class="locations-delivery-card">
          <div class="locations-delivery-icon">
            <i class="fas fa-home" aria-hidden="true"></i>
          </div>
          <h3 class="locations-delivery-title">We Come to You</h3>
          <p class="locations-delivery-address">On-site training anywhere in the UK</p>
          
          <ul class="locations-delivery-features">
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> No travel time for your team</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> We bring all equipment and materials</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Same trainers, same quality</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Delivered at care homes, offices, facilities</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> From Cornwall to Scotland</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Tailored to your policies and procedures</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Scheduled around your team's shifts</li>
          </ul>
          
          <div class="locations-delivery-highlight">
            <strong>Works best for:</strong>
            <p>Teams of 5+ who can't manage travel time. Cost-effective when you need everyone trained at once.</p>
          </div>
          
          <div class="locations-delivery-actions">
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact')) . '?type=onsite-training'); ?>" class="btn btn-primary">Request On-Site Training</a>
          </div>
        </div>
      </div>
      
      <div class="locations-contact-prompt">
        <p class="locations-contact-text">Not sure which works for you? Call us.</p>
        <p class="locations-contact-details">
          <i class="fas fa-phone" aria-hidden="true"></i> 
          <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $contact['phone'])); ?>"><?php echo esc_html($contact['phone']); ?></a>
          <span class="locations-contact-separator">|</span>
          <i class="fas fa-envelope" aria-hidden="true"></i> 
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>">Get in Touch</a>
        </p>
      </div>
    </div>
  </section>

  <!-- Locations Grid -->
  <section class="content-section bg-light-cream" aria-labelledby="areas-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="areas-heading" class="section-title">Training Locations Across Kent</h2>
        <p class="section-subtitle">Find location-specific information, travel times, and courses relevant to your area</p>
      </div>
      
      <div class="locations-kent-grid">
        <?php 
        $location_icons = [
          'Maidstone' => 'fa-map-marker-alt',
          'Medway' => 'fa-map-marker-alt',
          'Canterbury & East Kent' => 'fa-map-marker-alt',
          'Ashford & South Kent' => 'fa-map-marker-alt',
          'Tunbridge Wells & West Kent' => 'fa-map-marker-alt'
        ];
        
        foreach ($locations as $index => $location) : 
          $icon = $location_icons[$location['name']] ?? 'fa-map-marker-alt';
          $is_primary = ($location['name'] === 'Maidstone');
        ?>
          <article class="location-kent-card <?php echo $is_primary ? 'location-kent-card-primary' : ''; ?>">
            <?php if ($is_primary) : ?>
              <div class="location-kent-badge">Our Training Centre</div>
            <?php endif; ?>
            
            <div class="location-kent-icon">
              <i class="fas <?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
            </div>
            
            <h3 class="location-kent-title"><?php echo esc_html($location['name']); ?></h3>
            <p class="location-kent-description"><?php echo esc_html($location['description']); ?></p>
            
            <div class="location-kent-details">
              <div class="location-kent-detail-item">
                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                <div>
                  <strong>Areas covered:</strong>
                  <span><?php echo esc_html($location['areas']); ?></span>
                </div>
              </div>
              <div class="location-kent-detail-item">
                <i class="fas fa-clock" aria-hidden="true"></i>
                <div>
                  <strong>Travel time:</strong>
                  <span><?php echo esc_html($location['travel_time']); ?></span>
                </div>
              </div>
            </div>
            
            <?php if ($location['page_url']) : ?>
              <a href="<?php echo esc_url($location['page_url']); ?>" class="btn <?php echo $is_primary ? 'btn-primary' : 'btn-secondary'; ?>">
                <?php echo $is_primary ? 'View Our Centre' : 'View Location Details'; ?>
              </a>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- UK-Wide Locations -->
  <section class="content-section" aria-labelledby="uk-locations-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="uk-locations-heading" class="section-title">UK-Wide Training Locations</h2>
        <p class="section-subtitle">Regional context, hospital discharge pressures, and course priorities for your area</p>
      </div>
      
      <div class="locations-uk-grid">
        <a href="<?php echo esc_url(home_url('/locations/greater-manchester/')); ?>" class="location-uk-card">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          <h3>Greater Manchester & North West</h3>
        </a>
        
        <a href="<?php echo esc_url(home_url('/locations/london/')); ?>" class="location-uk-card">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          <h3>London & Greater London</h3>
        </a>
        
        <a href="<?php echo esc_url(home_url('/locations/west-yorkshire/')); ?>" class="location-uk-card">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          <h3>West Yorkshire & Yorkshire</h3>
        </a>
        
        <a href="<?php echo esc_url(home_url('/locations/midlands/')); ?>" class="location-uk-card">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          <h3>Midlands (Birmingham & Coventry)</h3>
        </a>
        
        <a href="<?php echo esc_url(home_url('/locations/south-west/')); ?>" class="location-uk-card">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          <h3>South West (Bristol, Devon & Cornwall)</h3>
        </a>
        
        <a href="<?php echo esc_url(home_url('/locations/lancashire/')); ?>" class="location-uk-card">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          <h3>Lancashire & South Cumbria</h3>
        </a>
        
        <a href="<?php echo esc_url(home_url('/locations/east-england/')); ?>" class="location-uk-card">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          <h3>East of England (Norfolk, Suffolk, Cambs)</h3>
        </a>
        
        <a href="<?php echo esc_url(home_url('/locations/merseyside/')); ?>" class="location-uk-card">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          <h3>Merseyside & Cheshire</h3>
        </a>
        
        <a href="<?php echo esc_url(home_url('/locations/scotland/')); ?>" class="location-uk-card">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          <h3>Scotland</h3>
        </a>
        
        <a href="<?php echo esc_url(home_url('/locations/wales/')); ?>" class="location-uk-card">
          <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          <h3>Wales</h3>
        </a>
      </div>
      
      <div style="max-width: 700px; margin: 48px auto 0; text-align: center; padding: 32px; background: white; border-radius: 8px;">
        <p style="font-size: 1.05rem; margin: 0;"><strong>Elsewhere in the UK?</strong> We deliver on-site anywhere. <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" style="font-weight: 600;">Give us your postcode</a> and we'll sort it.</p>
      </div>
    </div>
  </section>

  <!-- Why Choose Continuity Training Academy -->
  <section class="content-section bg-light-cream" aria-labelledby="why-choose-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="why-choose-heading" class="section-title">What Makes Us Different</h2>
        <p class="section-subtitle">We get it. You're stretched. Here's why providers book us anyway.</p>
      </div>
      
      <div class="locations-benefits-grid">
        <div class="locations-benefit-card">
          <div class="locations-benefit-icon locations-benefit-icon-blue">
            <i class="fas fa-clock" aria-hidden="true"></i>
          </div>
          <h3 class="locations-benefit-title">Flexible Scheduling</h3>
          <p class="locations-benefit-description">Morning, afternoon, evening, weekend sessions. We work around your rota. Night shift finishing at 8am? Book them on a morning course. Need emergency training before CQC visits? We'll find space.</p>
        </div>
        
        <div class="locations-benefit-card">
          <div class="locations-benefit-icon locations-benefit-icon-teal">
            <i class="fas fa-user-md" aria-hidden="true"></i>
          </div>
          <h3 class="locations-benefit-title">Trainers Who Work in Care</h3>
          <p class="locations-benefit-description">Not academics. Working care professionals. They know what "discharge-ready" means when someone rocks up at 9pm with a carrier bag of meds and no notes. DBS-checked, clinically current, professionally insured.</p>
        </div>
        
        <div class="locations-benefit-card">
          <div class="locations-benefit-icon locations-benefit-icon-gold">
            <i class="fas fa-certificate" aria-hidden="true"></i>
          </div>
          <h3 class="locations-benefit-title">Instant Certificates</h3>
          <p class="locations-benefit-description">Digital certificates hit inboxes before your team leaves the building. Physical copies posted same day. Automatic renewal reminders. CQC audit tomorrow? You're covered.</p>
        </div>
        
        <div class="locations-benefit-card">
          <div class="locations-benefit-icon locations-benefit-icon-green">
            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
          </div>
          <h3 class="locations-benefit-title">No Scrambling for Records</h3>
          <p class="locations-benefit-description">Every course documented. Every attendee tracked. Every certificate automatic. When CQC inspects, your training compliance is ready. No scrambling through files. No gaps.</p>
        </div>
        
        <div class="locations-benefit-card">
          <div class="locations-benefit-icon locations-benefit-icon-purple">
            <i class="fas fa-hands-helping" aria-hidden="true"></i>
          </div>
          <h3 class="locations-benefit-title">Actual Practical Training</h3>
          <p class="locations-benefit-description">Not death by PowerPoint. Your team learns by doing. Real scenarios. Real equipment. Real situations they'll face on shift.</p>
        </div>
        
        <div class="locations-benefit-card">
          <div class="locations-benefit-icon locations-benefit-icon-orange">
            <i class="fas fa-star" aria-hidden="true"></i>
          </div>
          <h3 class="locations-benefit-title">200+ Providers Trust Us</h3>
          <p class="locations-benefit-description">4.6★ on Trustpilot. All 5-star reviews. 100% CQC-compliant courses. CPD-accredited. On-site delivery anywhere in the UK.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Most Booked Courses -->
  <section class="content-section" aria-labelledby="courses-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="courses-heading" class="section-title">What Providers Are Booking</h2>
        <p class="section-subtitle">Most popular courses right now</p>
      </div>
      
      <div class="courses-grid">
        <article class="course-card">
          <div class="course-image-wrapper">
            <div class="course-image" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05)); display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-first-aid" style="font-size: 48px; color: rgba(220, 38, 38, 0.4);" aria-hidden="true"></i>
            </div>
          </div>
          <div class="course-card-header">
            <div class="course-card-badge-wrapper">
              <span class="course-card-badge course-card-badge-red">
                <i class="fas fa-book course-card-badge-icon" aria-hidden="true"></i>
                First Aid
              </span>
            </div>
            <h3 class="course-card-title">Emergency First Aid at Work</h3>
            <p class="course-card-description">Hospital discharge pressures mean your team's the safety net. They need current emergency skills.</p>
          </div>
          <div class="course-card-content">
            <div class="course-card-meta">
              <div class="course-card-meta-item">
                <i class="fas fa-clock course-card-meta-icon" aria-hidden="true"></i>
                <span>1 Day</span>
              </div>
              <div class="course-card-meta-item">
                <i class="fas fa-trophy course-card-meta-icon" aria-hidden="true"></i>
                <span>Level 3 HSE</span>
              </div>
            </div>
            <div class="course-card-actions">
              <a href="<?php echo esc_url(add_query_arg('category', 'emergency-first-aid', get_post_type_archive_link('course'))); ?>" class="btn btn-primary btn-block">View Courses</a>
            </div>
          </div>
        </article>
        
        <article class="course-card">
          <div class="course-image-wrapper">
            <div class="course-image" style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), rgba(124, 58, 237, 0.05)); display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-pills" style="font-size: 48px; color: rgba(124, 58, 237, 0.4);" aria-hidden="true"></i>
            </div>
          </div>
          <div class="course-card-header">
            <div class="course-card-badge-wrapper">
              <span class="course-card-badge course-card-badge-purple">
                <i class="fas fa-book course-card-badge-icon" aria-hidden="true"></i>
                Medication
              </span>
            </div>
            <h3 class="course-card-title">Medication Competency & Management</h3>
            <p class="course-card-description">Medication regimes are getting complex. CQC wants competency frameworks, not just awareness.</p>
          </div>
          <div class="course-card-content">
            <div class="course-card-meta">
              <div class="course-card-meta-item">
                <i class="fas fa-clock course-card-meta-icon" aria-hidden="true"></i>
                <span>1 Day</span>
              </div>
              <div class="course-card-meta-item">
                <i class="fas fa-trophy course-card-meta-icon" aria-hidden="true"></i>
                <span>Level 3 CPD</span>
              </div>
            </div>
            <div class="course-card-actions">
              <a href="<?php echo esc_url(add_query_arg('category', 'medication-administration', get_post_type_archive_link('course'))); ?>" class="btn btn-primary btn-block">View Courses</a>
            </div>
          </div>
        </article>
        
        <article class="course-card">
          <div class="course-image-wrapper">
            <div class="course-image" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05)); display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-hands" style="font-size: 48px; color: rgba(245, 158, 11, 0.4);" aria-hidden="true"></i>
            </div>
          </div>
          <div class="course-card-header">
            <div class="course-card-badge-wrapper">
              <span class="course-card-badge course-card-badge-orange">
                <i class="fas fa-book course-card-badge-icon" aria-hidden="true"></i>
                Manual Handling
              </span>
            </div>
            <h3 class="course-card-title">Moving & Handling (People)</h3>
            <p class="course-card-description">Bariatric care, specialist positioning, dynamic risk assessment. What your team actually does.</p>
          </div>
          <div class="course-card-content">
            <div class="course-card-meta">
              <div class="course-card-meta-item">
                <i class="fas fa-clock course-card-meta-icon" aria-hidden="true"></i>
                <span>1 Day</span>
              </div>
              <div class="course-card-meta-item">
                <i class="fas fa-trophy course-card-meta-icon" aria-hidden="true"></i>
                <span>Level 3 CPD</span>
              </div>
            </div>
            <div class="course-card-actions">
              <a href="<?php echo esc_url(add_query_arg('category', 'moving-handling', get_post_type_archive_link('course'))); ?>" class="btn btn-primary btn-block">View Courses</a>
            </div>
          </div>
        </article>
        
        <article class="course-card">
          <div class="course-image-wrapper">
            <div class="course-image" style="background: linear-gradient(135deg, rgba(53, 147, 141, 0.1), rgba(74, 168, 161, 0.05)); display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-certificate" style="font-size: 48px; color: rgba(53, 147, 141, 0.4);" aria-hidden="true"></i>
            </div>
          </div>
          <div class="course-card-header">
            <div class="course-card-badge-wrapper">
              <span class="course-card-badge course-card-badge-teal">
                <i class="fas fa-book course-card-badge-icon" aria-hidden="true"></i>
                Core Care
              </span>
            </div>
            <h3 class="course-card-title">Care Certificate</h3>
            <p class="course-card-description">New starters need this within 12 weeks. We deliver all 15 standards in a format that makes sense.</p>
          </div>
          <div class="course-card-content">
            <div class="course-card-meta">
              <div class="course-card-meta-item">
                <i class="fas fa-clock course-card-meta-icon" aria-hidden="true"></i>
                <span>4 Days</span>
              </div>
              <div class="course-card-meta-item">
                <i class="fas fa-trophy course-card-meta-icon" aria-hidden="true"></i>
                <span>Level 2</span>
              </div>
            </div>
            <div class="course-card-actions">
              <a href="<?php echo esc_url(add_query_arg('category', 'care-certificate', get_post_type_archive_link('course'))); ?>" class="btn btn-primary btn-block">View Courses</a>
            </div>
          </div>
        </article>
        
        <article class="course-card">
          <div class="course-image-wrapper">
            <div class="course-image" style="background: linear-gradient(135deg, rgba(5, 150, 105, 0.1), rgba(5, 150, 105, 0.05)); display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-shield-alt" style="font-size: 48px; color: rgba(5, 150, 105, 0.4);" aria-hidden="true"></i>
            </div>
          </div>
          <div class="course-card-header">
            <div class="course-card-badge-wrapper">
              <span class="course-card-badge course-card-badge-green">
                <i class="fas fa-book course-card-badge-icon" aria-hidden="true"></i>
                Safeguarding
              </span>
            </div>
            <h3 class="course-card-title">Safeguarding Adults</h3>
            <p class="course-card-description">Safeguarding protocols are tightening. Keep your team current with updated procedures.</p>
          </div>
          <div class="course-card-content">
            <div class="course-card-meta">
              <div class="course-card-meta-item">
                <i class="fas fa-clock course-card-meta-icon" aria-hidden="true"></i>
                <span>Half Day</span>
              </div>
              <div class="course-card-meta-item">
                <i class="fas fa-trophy course-card-meta-icon" aria-hidden="true"></i>
                <span>Level 2 CPD</span>
              </div>
            </div>
            <div class="course-card-actions">
              <a href="<?php echo esc_url(add_query_arg('category', 'safeguarding', get_post_type_archive_link('course'))); ?>" class="btn btn-primary btn-block">View Courses</a>
            </div>
          </div>
        </article>
        
        <article class="course-card">
          <div class="course-image-wrapper">
            <div class="course-image" style="background: linear-gradient(135deg, rgba(8, 145, 178, 0.1), rgba(8, 145, 178, 0.05)); display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-brain" style="font-size: 48px; color: rgba(8, 145, 178, 0.4);" aria-hidden="true"></i>
            </div>
          </div>
          <div class="course-card-header">
            <div class="course-card-badge-wrapper">
              <span class="course-card-badge course-card-badge-blue">
                <i class="fas fa-book course-card-badge-icon" aria-hidden="true"></i>
                Specialist
              </span>
            </div>
            <h3 class="course-card-title">Dementia Care</h3>
            <p class="course-card-description">Dementia cases are rising. Your staff need practical strategies, not just awareness training.</p>
          </div>
          <div class="course-card-content">
            <div class="course-card-meta">
              <div class="course-card-meta-item">
                <i class="fas fa-clock course-card-meta-icon" aria-hidden="true"></i>
                <span>1 Day</span>
              </div>
              <div class="course-card-meta-item">
                <i class="fas fa-trophy course-card-meta-icon" aria-hidden="true"></i>
                <span>Specialist CPD</span>
              </div>
            </div>
            <div class="course-card-actions">
              <a href="<?php echo esc_url(add_query_arg('category', 'dementia-mental-health', get_post_type_archive_link('course'))); ?>" class="btn btn-primary btn-block">View Courses</a>
            </div>
          </div>
        </article>
      </div>
      
      <!-- Specialist Courses Section -->
      <div class="locations-specialist-section">
        <div class="locations-specialist-header">
          <h3 class="locations-specialist-title">Plus Specialist Courses</h3>
          <p class="locations-specialist-description">Additional training for complex care needs and regulatory compliance</p>
        </div>
        
        <div class="locations-specialist-tags">
          <span class="locations-specialist-tag">Catheter Care</span>
          <span class="locations-specialist-tag">Sepsis Awareness</span>
          <span class="locations-specialist-tag">End-of-Life Care</span>
          <span class="locations-specialist-tag">Learning Disabilities</span>
          <span class="locations-specialist-tag">Mental Health</span>
          <span class="locations-specialist-tag">Epilepsy Management</span>
          <span class="locations-specialist-tag">Insulin Administration</span>
          <span class="locations-specialist-tag">Nutrition & Hydration</span>
          <span class="locations-specialist-tag">Infection Control</span>
          <span class="locations-specialist-tag">PEG Feeding</span>
          <span class="locations-specialist-tag">Stoma Care</span>
          <span class="locations-specialist-tag">Oxygen Therapy</span>
          </div>
        
        <div class="cta-center" style="margin-top: 32px;">
          <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="btn btn-primary btn-large">View All Course Dates</a>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('courses'))); ?>" class="btn btn-secondary btn-large">Browse Full Course List</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Final CTA -->
  <section class="about-cta-new" aria-labelledby="cta-heading">
    <div class="container">
      <div class="about-cta-content-new">
        <h2 id="cta-heading">Book Your Team In</h2>
        <p>Tell us what training you need and where you are. We'll quote you (centre-based or on-site) with available dates. Your team gets trained, certificates hit inboxes, you're CQC-ready.</p>
        <div class="about-cta-buttons-new">
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" class="btn btn-primary">Get in Touch</a>
          <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="btn btn-secondary">See Course Dates</a>
        </div>
        <p style="margin-top: 32px; font-size: 1.125rem; color: rgba(255, 255, 255, 0.95);">
          <i class="fas fa-phone" aria-hidden="true"></i> 
          <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $contact['phone'])); ?>" style="color: var(--white); font-weight: 600; text-decoration: underline; text-decoration-thickness: 1px; text-underline-offset: 3px;"><?php echo esc_html($contact['phone']); ?></a>
        </p>
      </div>
    </div>
  </section>
</main>

<?php
get_footer();
