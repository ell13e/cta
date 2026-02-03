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
  
  <!-- Hiring Stats Section -->
  <section class="about-mission-new bg-light-cream" aria-labelledby="hiring-stats-heading">
    <div class="container">
      <div class="about-mission-grid-new">
        <div class="about-mission-text-new">
          <h2 id="hiring-stats-heading">The Retention Challenge</h2>
          <p>
            Hiring's down 56% year-on-year. One in three providers is considering leaving the sector. You can't fix the funding crisis. But you can keep the team you've got.
          </p>
          <p>
            Trained staff feel competent. Competent staff stay longer. That's not marketing speak: it's the only retention strategy that's working right now.
          </p>
        </div>
        <div class="about-mission-image-new">
          <img src="<?php echo esc_url(cta_image('stock_photos/03_ABOUT_US_PAGE/about_page01.webp')); ?>" 
               alt="Care training team working together"
               width="1600"
               height="1200"
               loading="lazy">
        </div>
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
          <p class="locations-delivery-address">Maidstone, Kent</p>
          
          <ul class="locations-delivery-features">
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Free parking on site</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Less than 10 minutes from Maidstone West station</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Modern training space</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> All equipment provided</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Quiet environment, no distractions</li>
            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Refreshments included</li>
          </ul>
          
          <div class="locations-delivery-highlight">
            <strong>Works best for:</strong>
            <p>Small to medium teams who can send staff for a day. Ideal if you're within easy reach of Maidstone and want a dedicated training environment away from your workplace.</p>
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
            <p>Larger teams or when staff can't leave the premises. Minimises disruption and ensures everyone gets trained together, tailored to your specific policies and procedures.</p>
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
      
      <div class="categories-grid">
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
          <article class="locations-delivery-card">
            <?php if ($is_primary) : ?>
              <div class="badge badge-essential" style="position: absolute; top: 16px; right: 16px;">Our Training Centre</div>
            <?php endif; ?>
            
            <div class="locations-delivery-icon">
              <i class="fas <?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
            </div>
            
            <h3 class="locations-delivery-title"><?php echo esc_html($location['name']); ?></h3>
            <p class="locations-delivery-address"><?php echo esc_html($location['description']); ?></p>
            
            <ul class="locations-delivery-features">
              <li><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <strong>Areas covered:</strong> <?php echo esc_html($location['areas']); ?></li>
              <li><i class="fas fa-clock" aria-hidden="true"></i> <strong>Travel time:</strong> <?php echo esc_html($location['travel_time']); ?></li>
            </ul>
            
            <?php if ($location['page_url']) : ?>
              <div class="locations-delivery-actions">
                <a href="<?php echo esc_url($location['page_url']); ?>" class="btn <?php echo $is_primary ? 'btn-primary' : 'btn-secondary'; ?>">
                  <?php echo $is_primary ? 'View Our Centre' : 'View Location Details'; ?>
                </a>
              </div>
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
        <h2 id="uk-locations-heading" class="section-title">UK-Wide Mobile Training Delivery</h2>
        <p class="section-subtitle">We travel to your venue anywhere in the UK. Our training centre is in Maidstone, Kent—we bring the training to you.</p>
      </div>
      
      <div class="categories-grid">
        <?php
        $uk_locations = [
          [
            'name' => 'Greater Manchester & North West',
            'url' => home_url('/locations/greater-manchester/'),
            'description' => 'We travel to your venue across Greater Manchester and the North West. Hospital discharge delays are pushing complex care packages your way—your team needs current training delivered at your location.',
            'areas' => 'We deliver on-site in: Manchester, Stockport, Salford, Bolton, Bury, Oldham, Rochdale, Wigan',
            'hook' => 'Mobile training at your venue. No travel time for your team.'
          ],
          [
            'name' => 'London & Greater London',
            'url' => home_url('/locations/london/'),
            'description' => 'We travel to your care facility anywhere in London. CQC scrutiny is tightest here—we bring current training to your venue to meet the highest standards.',
            'areas' => 'We deliver on-site in: All London boroughs, Greater London, Home Counties',
            'hook' => 'Mobile delivery to your venue. Zero travel time for staff.'
          ],
          [
            'name' => 'West Yorkshire & Yorkshire',
            'url' => home_url('/locations/west-yorkshire/'),
            'description' => 'We come to your venue across West Yorkshire. Bed occupancy above 91% means sicker patients, more medications, more risk—we deliver training at your location.',
            'areas' => 'We deliver on-site in: Leeds, Sheffield, Bradford, Wakefield, Huddersfield, York',
            'hook' => 'On-site training at your facility. We bring everything to you.'
          ],
          [
            'name' => 'Midlands (Birmingham & Coventry)',
            'url' => home_url('/locations/midlands/'),
            'description' => 'We travel to your venue across the Midlands. When discharge happens, it\'s complex—we deliver training at your location to keep your team current.',
            'areas' => 'We deliver on-site in: Birmingham, Coventry, Solihull, Wolverhampton, Dudley, Walsall',
            'hook' => 'Mobile training delivery. No disruption to your rota.'
          ],
          [
            'name' => 'South West (Bristol, Devon & Cornwall)',
            'url' => home_url('/locations/south-west/'),
            'description' => 'We travel to your venue across the South West. Rural and coastal areas need flexible training—we come to you, bringing all equipment and materials.',
            'areas' => 'We deliver on-site in: Bristol, Plymouth, Exeter, Truro, Taunton, Bath',
            'hook' => 'Mobile delivery anywhere. We bring everything to your venue.'
          ],
          [
            'name' => 'Lancashire & South Cumbria',
            'url' => home_url('/locations/lancashire/'),
            'description' => 'We travel to your venue across Lancashire and South Cumbria. Budget pressures are real—we deliver cost-effective training at your location.',
            'areas' => 'We deliver on-site in: Preston, Blackburn, Lancaster, Burnley, Barrow-in-Furness, Carlisle',
            'hook' => 'On-site training at your facility. Training tailored to your policies.'
          ],
          [
            'name' => 'East of England (Norfolk, Suffolk, Cambs)',
            'url' => home_url('/locations/east-england/'),
            'description' => 'We travel to your venue across the East of England. Rural and coastal care providers need accessible training—we deliver on-site at your location.',
            'areas' => 'We deliver on-site in: Norwich, Ipswich, Cambridge, Peterborough, Great Yarmouth, Lowestoft',
            'hook' => 'Mobile delivery. We work around your rota and come to you.'
          ],
          [
            'name' => 'Merseyside & Cheshire',
            'url' => home_url('/locations/merseyside/'),
            'description' => 'We travel to your venue across Merseyside and Cheshire. Urban care providers managing high-acuity cases—we deliver current training at your location.',
            'areas' => 'We deliver on-site in: Liverpool, Wirral, Chester, Warrington, Birkenhead, Ellesmere Port',
            'hook' => 'On-site training at your facility. Complex care needs current skills.'
          ],
          [
            'name' => 'Scotland',
            'url' => home_url('/locations/scotland/'),
            'description' => 'We travel to your venue anywhere in Scotland. Scottish care standards require current training—we deliver on-site from the Borders to the Highlands.',
            'areas' => 'We deliver on-site in: Glasgow, Edinburgh, Aberdeen, Dundee, Inverness, Perth',
            'hook' => 'UK-wide mobile delivery. We come to your venue anywhere.'
          ],
          [
            'name' => 'Wales',
            'url' => home_url('/locations/wales/'),
            'description' => 'We travel to your venue anywhere in Wales. Welsh care providers need CQC-equivalent training—we deliver on-site from Cardiff to rural areas.',
            'areas' => 'We deliver on-site in: Cardiff, Swansea, Newport, Wrexham, Bangor, Carmarthen',
            'hook' => 'Mobile delivery to your venue. We come to you anywhere in Wales.'
          ]
        ];
        
        foreach ($uk_locations as $location) :
        ?>
        <article class="locations-delivery-card">
          <div class="locations-delivery-icon">
            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
          </div>
          
          <h3 class="locations-delivery-title"><?php echo esc_html($location['name']); ?></h3>
          <p class="locations-delivery-address"><?php echo esc_html($location['description']); ?></p>
          
          <ul class="locations-delivery-features">
            <li><i class="fas fa-truck" aria-hidden="true"></i> <strong>Service areas:</strong> <?php echo esc_html($location['areas']); ?></li>
            <li><i class="fas fa-info-circle" aria-hidden="true"></i> <strong>Why it matters:</strong> <?php echo esc_html($location['hook']); ?></li>
          </ul>
          
          <div class="locations-delivery-actions">
            <a href="<?php echo esc_url($location['url']); ?>" class="btn btn-primary">View Location Details</a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      
      <div class="locations-contact-prompt">
        <p class="locations-contact-text"><strong>Elsewhere in the UK?</strong> We deliver on-site anywhere. <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>">Give us your postcode</a> and we'll sort it.</p>
      </div>
    </div>
  </section>

  <!-- Why Choose Continuity Training Academy -->
  <section class="why-us-blended" aria-labelledby="why-choose-heading">
    <div class="container">
      <div class="why-us-blended-header">
        <h2 id="why-choose-heading" class="section-title">What Makes Us Different</h2>
        <p class="section-subtitle">We get it. You're stretched. Here's why providers book us anyway.</p>
      </div>
      
      <div class="why-us-blended-grid">
        <div class="why-us-blended-card">
          <div class="why-us-blended-card-header">
            <div class="why-us-blended-icon-wrapper why-us-blended-icon-1">
              <i class="fas fa-clock why-us-blended-icon" aria-hidden="true"></i>
            </div>
          </div>
          <h3 class="why-us-blended-title">Flexible Scheduling</h3>
          <p class="why-us-blended-description">Morning, afternoon, evening, weekend sessions. We work around your rota. Night shift finishing at 8am? Book them on a morning course. Need emergency training before CQC visits? We'll find space.</p>
        </div>
        
        <div class="why-us-blended-card">
          <div class="why-us-blended-card-header">
            <div class="why-us-blended-icon-wrapper why-us-blended-icon-2">
              <i class="fas fa-user-md why-us-blended-icon" aria-hidden="true"></i>
            </div>
          </div>
          <h3 class="why-us-blended-title">Trainers Who Work in Care</h3>
          <p class="why-us-blended-description">Not academics. Working care professionals. They know what "discharge-ready" means when someone rocks up at 9pm with a carrier bag of meds and no notes. DBS-checked, clinically current, professionally insured.</p>
        </div>
        
        <div class="why-us-blended-card">
          <div class="why-us-blended-card-header">
            <div class="why-us-blended-icon-wrapper why-us-blended-icon-3">
              <i class="fas fa-certificate why-us-blended-icon" aria-hidden="true"></i>
            </div>
          </div>
          <h3 class="why-us-blended-title">Instant Certificates</h3>
          <p class="why-us-blended-description">Digital certificates hit inboxes before your team leaves the building. Physical copies posted same day. Automatic renewal reminders. CQC audit tomorrow? You're covered.</p>
        </div>
        
        <div class="why-us-blended-card">
          <div class="why-us-blended-card-header">
            <div class="why-us-blended-icon-wrapper why-us-blended-icon-4">
              <i class="fas fa-clipboard-check why-us-blended-icon" aria-hidden="true"></i>
            </div>
          </div>
          <h3 class="why-us-blended-title">No Scrambling for Records</h3>
          <p class="why-us-blended-description">Every course documented. Every attendee tracked. Every certificate automatic. When CQC inspects, your training compliance is ready. No scrambling through files. No gaps.</p>
        </div>
        
        <div class="why-us-blended-card">
          <div class="why-us-blended-card-header">
            <div class="why-us-blended-icon-wrapper why-us-blended-icon-5">
              <i class="fas fa-hands-helping why-us-blended-icon" aria-hidden="true"></i>
            </div>
          </div>
          <h3 class="why-us-blended-title">Actual Practical Training</h3>
          <p class="why-us-blended-description">Not death by PowerPoint. Your team learns by doing. Real scenarios. Real equipment. Real situations they'll face on shift.</p>
        </div>
        
        <div class="why-us-blended-card">
          <div class="why-us-blended-card-header">
            <div class="why-us-blended-icon-wrapper why-us-blended-icon-6">
              <i class="fas fa-users why-us-blended-icon" aria-hidden="true"></i>
            </div>
          </div>
          <h3 class="why-us-blended-title">200+ Providers Trust Us</h3>
          <p class="why-us-blended-description">4.6 on Trustpilot. All 5-star reviews. 100% CQC-compliant courses. CPD-accredited. On-site delivery anywhere in the UK.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Most Booked Courses -->
  <section class="courses-listing-section" aria-labelledby="courses-heading">
    <div class="container">
      <div class="courses-listing-header">
        <h2 id="courses-heading" class="section-title">What Providers Are Booking</h2>
        <p class="section-subtitle">Most popular courses right now</p>
      </div>
      
      <?php
      // Category mapping for badges and icons (same as archive-course.php)
      $category_icons = [
        'core-care-skills' => 'fa-heart',
        'emergency-first-aid' => 'fa-first-aid',
        'health-conditions-specialist-care' => 'fa-stethoscope',
        'medication-management' => 'fa-pills',
        'safety-compliance' => 'fa-shield-alt',
        'communication-workplace-culture' => 'fa-users',
        'information-data-management' => 'fa-database',
        'nutrition-hygiene' => 'fa-apple-alt',
        'leadership-professional-development' => 'fa-user-tie',
      ];

      $category_badge_colors = [
        'core-care-skills' => 'course-badge-blue',
        'emergency-first-aid' => 'course-badge-red',
        'health-conditions-specialist-care' => 'course-badge-green',
        'medication-management' => 'course-badge-purple',
        'safety-compliance' => 'course-badge-amber',
        'communication-workplace-culture' => 'course-badge-teal',
        'information-data-management' => 'course-badge-indigo',
        'nutrition-hygiene' => 'course-badge-orange',
        'leadership-professional-development' => 'course-badge-pink',
      ];

      $category_short_names = [
        'core-care-skills' => 'Core Care',
        'emergency-first-aid' => 'First Aid',
        'health-conditions-specialist-care' => 'Specialist',
        'medication-management' => 'Medication',
        'safety-compliance' => 'Safety',
        'communication-workplace-culture' => 'Communication',
        'information-data-management' => 'Data',
        'nutrition-hygiene' => 'Nutrition',
        'leadership-professional-development' => 'Leadership',
      ];

      // Get most popular courses (limit to 6)
      $popular_courses = get_posts([
        'post_type' => 'course',
        'post_status' => 'publish',
        'posts_per_page' => 6,
        'orderby' => 'title',
        'order' => 'ASC',
      ]);
      ?>
      
      <div class="courses-grid">
        <?php if (!empty($popular_courses)) : 
          foreach ($popular_courses as $course_post) :
            setup_postdata($course_post);
            $duration = get_field('course_duration', $course_post->ID);
            $price = get_field('course_price', $course_post->ID);
            $accreditation = get_field('course_accreditation', $course_post->ID);
            $level = get_field('course_level', $course_post->ID);
            // Use limiting function to get max 2 categories
            $terms = function_exists('cta_get_course_category_terms') ? cta_get_course_category_terms($course_post->ID) : get_the_terms($course_post->ID, 'course_category');
            $primary_term = $terms && !is_wp_error($terms) && !empty($terms) ? $terms[0] : null;
            $secondary_term = $terms && !is_wp_error($terms) && count($terms) >= 2 ? $terms[1] : null;
            $category_slug = $primary_term ? $primary_term->slug : '';
            $badge_color = isset($category_badge_colors[$category_slug]) ? $category_badge_colors[$category_slug] : 'course-badge-blue';
            $short_name = isset($category_short_names[$category_slug]) ? $category_short_names[$category_slug] : ($primary_term ? $primary_term->name : '');
            $primary_icon = isset($category_icons[$category_slug]) ? $category_icons[$category_slug] : 'fa-book';
            $secondary_icon = $secondary_term && isset($category_icons[$secondary_term->slug]) ? $category_icons[$secondary_term->slug] : 'fa-book';
            $secondary_badge_color = $secondary_term && isset($category_badge_colors[$secondary_term->slug]) ? $category_badge_colors[$secondary_term->slug] : 'course-badge-blue';
            $secondary_short_name = $secondary_term && isset($category_short_names[$secondary_term->slug]) ? $category_short_names[$secondary_term->slug] : ($secondary_term ? $secondary_term->name : '');
        ?>
        <article class="course-card" data-category="<?php echo esc_attr($category_slug); ?>" data-title="<?php echo esc_attr(strtolower(get_the_title($course_post->ID))); ?>" data-course-id="<?php echo $course_post->ID; ?>" data-course-url="<?php echo esc_url(get_permalink($course_post->ID)); ?>">
          <?php if (has_post_thumbnail($course_post->ID)) : 
            $thumbnail_id = get_post_thumbnail_id($course_post->ID);
            $image_src = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
            $image_srcset = wp_get_attachment_image_srcset($thumbnail_id, 'medium_large');
          ?>
          <div class="course-image-wrapper">
            <img srcset="<?php echo esc_attr($image_srcset); ?>"
                 src="<?php echo esc_url($image_src[0]); ?>"
                 alt="<?php echo esc_attr(get_the_title($course_post->ID)); ?>"
                 class="course-image"
                 loading="lazy"
                 width="400"
                 height="225"
                 sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw">
          </div>
          <?php endif; ?>
          
          <div class="course-card-header">
            <?php if ($primary_term || $secondary_term) : ?>
            <div class="course-card-badge-wrapper">
              <?php if ($primary_term) : ?>
              <span class="course-card-badge <?php echo esc_attr($badge_color); ?>">
                <i class="fas <?php echo esc_attr($primary_icon); ?> course-card-badge-icon" aria-hidden="true"></i>
                <?php echo esc_html($short_name); ?>
              </span>
              <?php endif; ?>
              <?php if ($secondary_term) : ?>
              <span class="course-card-badge <?php echo esc_attr($secondary_badge_color); ?>">
                <i class="fas <?php echo esc_attr($secondary_icon); ?> course-card-badge-icon" aria-hidden="true"></i>
                <?php echo esc_html($secondary_short_name); ?>
              </span>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <h3 class="course-card-title"><?php echo esc_html(get_the_title($course_post->ID)); ?></h3>
            
            <?php 
            $excerpt = get_the_excerpt($course_post->ID);
            if ($excerpt) : 
            ?>
            <p class="course-card-description"><?php echo esc_html($excerpt); ?></p>
            <?php endif; ?>
          </div>
          
          <div class="course-card-content">
            <div class="course-card-meta">
              <?php if ($duration) : ?>
              <div class="course-card-meta-item">
                <i class="fas fa-clock course-card-meta-icon" aria-hidden="true"></i>
                <span><?php echo esc_html($duration); ?></span>
              </div>
              <?php endif; ?>
              
              <?php if ($accreditation) : ?>
              <div class="course-card-meta-item">
                <i class="fas fa-trophy course-card-meta-icon" aria-hidden="true"></i>
                <span><?php echo esc_html($accreditation); ?></span>
              </div>
              <?php endif; ?>
              
              <?php if ($level) : ?>
              <div class="course-card-meta-item">
                <i class="fas fa-chart-line course-card-meta-icon" aria-hidden="true"></i>
                <span><?php echo esc_html($level); ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="course-card-footer">
            <?php if ($price) : ?>
            <div class="course-card-price">
              <p class="course-card-price-amount">From £<?php echo esc_html(number_format($price, 0)); ?></p>
              <p class="course-card-price-label">per person</p>
            </div>
            <?php endif; ?>
            <a href="<?php echo esc_url(get_permalink($course_post->ID)); ?>" class="course-read-more-btn" aria-label="Read more about <?php echo esc_attr(get_the_title($course_post->ID)); ?>">
              Read More
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M12 5l7 7-7 7"></path>
              </svg>
            </a>
          </div>
        </article>
        <?php 
          endforeach;
          wp_reset_postdata();
        else : 
        ?>
        <p class="no-courses">No courses available at the moment. Please check back soon.</p>
        <?php endif; ?>
      </div>
      
      <!-- Specialist Courses Section -->
      <div class="specialist-courses-section">
        <div class="section-header-center">
          <h3 class="section-title">Plus Specialist Courses</h3>
          <p class="section-subtitle">Additional training for complex care needs and regulatory compliance</p>
        </div>
        
        <div class="badge-container">
          <span class="badge badge-essential">Catheter Care</span>
          <span class="badge badge-advanced">Sepsis Awareness</span>
          <span class="badge badge-clinical">End-of-Life Care</span>
          <span class="badge badge-practical">Learning Disabilities</span>
          <span class="badge badge-essential">Mental Health</span>
          <span class="badge badge-advanced">Epilepsy Management</span>
          <span class="badge badge-clinical">Insulin Administration</span>
          <span class="badge badge-practical">Nutrition & Hydration</span>
          <span class="badge badge-essential">Infection Control</span>
          <span class="badge badge-advanced">PEG Feeding</span>
          <span class="badge badge-clinical">Stoma Care</span>
          <span class="badge badge-practical">Oxygen Therapy</span>
        </div>
        
        <div class="cta-center">
          <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="btn btn-primary btn-large">View All Course Dates</a>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('courses'))); ?>" class="btn btn-secondary btn-large">Browse Full Course List</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Final CTA -->
  <section class="cta-section" aria-labelledby="cta-heading">
    <div class="container">
      <div class="cta-content">
        <h2 id="cta-heading" class="cta-title">Book Your Team In</h2>
        <p class="cta-description">Tell us what training you need and where you are. We'll quote you (centre-based or on-site) with available dates. Your team gets trained, certificates hit inboxes, you're CQC-ready.</p>
        <div class="cta-buttons">
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" class="btn btn-primary">Get in Touch</a>
          <a href="<?php echo esc_url(get_post_type_archive_link('course_event')); ?>" class="btn btn-secondary">See Course Dates</a>
        </div>
        <p style="margin-top: 32px; font-size: 1.125rem; color: var(--brown-medium);">
          <i class="fas fa-phone" aria-hidden="true"></i> 
          <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $contact['phone'])); ?>" style="color: var(--brown-dark); font-weight: 600; text-decoration: underline; text-decoration-thickness: 1px; text-underline-offset: 3px;"><?php echo esc_html($contact['phone']); ?></a>
        </p>
      </div>
    </div>
  </section>
</main>

<?php
get_footer();
