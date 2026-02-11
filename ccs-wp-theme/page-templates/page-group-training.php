<?php
/**
 * Template Name: Group Training
 *
 * @package ccs-theme
 */

get_header();

$contact = ccs_get_contact_info();

$hero_title = get_field('hero_title') ?: 'Group Training for Care Teams in Kent';
$hero_subtitle = get_field('hero_subtitle') ?: 'Train your entire team together. Flexible scheduling, accredited certificates, and group rates that make quality training affordable.';
?>

<main id="main-content" class="site-main">
  <section class="group-hero-section" aria-labelledby="group-training-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><span class="breadcrumb-current" aria-current="page">Group Training</span></li>
        </ol>
      </nav>
      <h1 id="group-training-heading" class="hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
      <div class="group-hero-cta">
        <a href="#group-booking-form" class="btn btn-primary group-hero-btn-primary">Get Your Free Quote</a>
        <a href="#comparison" class="btn btn-secondary group-hero-btn-secondary">See Training Options</a>
      </div>
    </div>
  </section>

  <section class="group-how-it-works-section" id="how-it-works" aria-labelledby="how-it-works-heading">
    <div class="container">
      <div class="group-how-it-works-header">
        <p class="section-eyebrow">Simple Process</p>
        <h2 id="how-it-works-heading" class="section-title">How It Works</h2>
      </div>
      
      <div class="group-steps-grid">
        <div class="group-step-card">
          <span class="group-step-number">1</span>
          <h3 class="group-step-title">Request a quote</h3>
          <p class="group-step-description">Fill in the form or call us with your team size and training needs.</p>
        </div>
        <div class="group-step-card">
          <span class="group-step-number">2</span>
          <h3 class="group-step-title">Book your dates</h3>
          <p class="group-step-description">We'll send a quote within 24 hours. Choose dates that suit your team.</p>
        </div>
        <div class="group-step-card">
          <span class="group-step-number">3</span>
          <h3 class="group-step-title">Train your team</h3>
          <p class="group-step-description">We deliver the training and your team receives accredited certificates.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="group-training-options-section" id="comparison" aria-labelledby="comparison-heading">
    <div class="container">
      <div class="group-training-options-header">
        <p class="section-eyebrow">Training Options</p>
        <h2 id="comparison-heading" class="section-title">Choose Your Training Format</h2>
        <p class="group-training-options-subtitle">Compare our three delivery options</p>
      </div>

      <div class="group-training-cards-grid" id="comparison-cards-view">
        <div class="group-training-card group-training-card-popular" id="onsite" data-card-type="onsite">
          <span class="group-training-card-badge" aria-label="Most Popular option">Most Popular</span>
          <div class="group-training-card-icon">
            <i class="fas fa-home" aria-hidden="true"></i>
          </div>
          <h3 class="group-training-card-title">On-Site Training</h3>
          <ul class="group-training-card-features">
            <li><i class="fas fa-check" aria-hidden="true"></i><span>We come to your workplace</span></li>
            <li><i class="fas fa-check" aria-hidden="true"></i><span>Train during shift handovers</span></li>
            <li><i class="fas fa-check" aria-hidden="true"></i><span>Up to 12 staff per session</span></li>
            <li><i class="fas fa-check" aria-hidden="true"></i><span>CPD-accredited certificates</span></li>
          </ul>
          <div class="group-training-card-pricing">
            <span>From £45 per person</span>
          </div>
          <div class="group-training-card-actions">
            <a href="#group-booking-form" class="btn btn-primary quote-btn" data-training-type="onsite">Get Quote</a>
          </div>
        </div>

        <div class="group-training-card" id="classroom" data-card-type="classroom">
          <div class="group-training-card-icon">
            <i class="fas fa-building" aria-hidden="true"></i>
          </div>
          <h3 class="group-training-card-title">Classroom Training</h3>
          <ul class="group-training-card-features">
            <li><i class="fas fa-check" aria-hidden="true"></i><span>Purpose-built training studio</span></li>
            <li><i class="fas fa-check" aria-hidden="true"></i><span>Distraction-free environment</span></li>
            <li><i class="fas fa-check" aria-hidden="true"></i><span>Up to 12 staff per session</span></li>
            <li><i class="fas fa-check" aria-hidden="true"></i><span>CPD-accredited certificates</span></li>
          </ul>
          <div class="group-training-card-pricing">
            <span>From £45 per person</span>
            <span class="group-training-card-location"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Maidstone</span>
          </div>
          <div class="group-training-card-actions">
            <a href="#group-booking-form" class="btn btn-primary quote-btn" data-training-type="classroom">Get Quote</a>
          </div>
        </div>

        <div class="group-training-card" id="custom" data-card-type="custom">
          <div class="group-training-card-icon">
            <i class="fas fa-sliders-h" aria-hidden="true"></i>
          </div>
          <h3 class="group-training-card-title">Custom Package</h3>
          <ul class="group-training-card-features">
            <li><i class="fas fa-check" aria-hidden="true"></i><span>Bundle multiple courses</span></li>
            <li><i class="fas fa-check" aria-hidden="true"></i><span>Mix on-site and classroom</span></li>
            <li><i class="fas fa-check" aria-hidden="true"></i><span>Ideal for 20+ staff</span></li>
            <li><i class="fas fa-check" aria-hidden="true"></i><span>Dedicated account manager</span></li>
          </ul>
          <div class="group-training-card-pricing">
            <span>Custom pricing</span>
          </div>
          <div class="group-training-card-actions">
            <a href="#group-booking-form" class="btn btn-primary quote-btn" data-training-type="custom">Get Quote</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="group-testimonials-section" id="testimonials" aria-labelledby="testimonials-heading">
    <div class="container">
      <div class="group-testimonials-header">
        <p class="group-testimonials-eyebrow">What People Say</p>
        <h2 id="testimonials-heading" class="section-title">Trusted by Care Teams Across Kent</h2>
      </div>

      <div class="group-testimonials-grid">
        <?php
        $testimonials = get_field('testimonials') ?: [
          ['quote' => 'Jen is a fantastic trainer and leaves you feeling confident in your new learnt abilities!', 'author' => 'Expertise Homecare', 'icon' => 'fas fa-building'],
          ['quote' => "It's much easier to learn when a trainer is passionate and excited about the topics they teach.", 'author' => 'Inga', 'icon' => 'fas fa-user'],
          ['quote' => "Jen's training style is very much centred around each individual and is delivered in a very personable manner.", 'author' => 'Melvyn', 'icon' => 'fas fa-user'],
        ];
        
        foreach ($testimonials as $testimonial) :
        ?>
        <blockquote class="group-testimonial-card">
          <span class="group-testimonial-quote-mark" aria-hidden="true">"</span>
          <p class="group-testimonial-quote"><?php echo esc_html($testimonial['quote']); ?></p>
          <footer class="group-testimonial-footer">
            <div class="group-testimonial-avatar">
              <i class="<?php echo esc_attr($testimonial['icon']); ?>" aria-hidden="true"></i>
            </div>
            <cite class="group-testimonial-author"><?php echo esc_html($testimonial['author']); ?></cite>
          </footer>
        </blockquote>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="group-benefits-section" id="benefits" aria-labelledby="benefits-heading">
    <div class="container">
      <div class="group-benefits-header">
        <p class="section-eyebrow">Benefits</p>
        <h2 id="benefits-heading" class="section-title">Why Choose Group Training?</h2>
        <p class="section-subtitle">Maximise value while maintaining quality training standards</p>
      </div>

      <div class="group-benefits-grid">
        <div class="group-benefit-card">
          <div class="group-benefit-icon">
            <i class="fas fa-clock" aria-hidden="true"></i>
          </div>
          <h3 class="group-benefit-title">Train on your schedule</h3>
          <p class="group-benefit-description">Evenings, weekends, or during shifts - we work around your operations and staff availability</p>
        </div>
        <div class="group-benefit-card">
          <div class="group-benefit-icon">
            <i class="fas fa-users" aria-hidden="true"></i>
          </div>
          <h3 class="group-benefit-title">Consistent team training</h3>
          <p class="group-benefit-description">No knowledge gaps - everyone receives the same high-quality training to the same professional standards</p>
        </div>
        <div class="group-benefit-card">
          <div class="group-benefit-icon">
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
          </div>
          <h3 class="group-benefit-title">Compliant & Accredited</h3>
          <p class="group-benefit-description">Keep your team inspection-ready with CPD accredited training that meets CQC-compliance.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="contact-section-main" id="group-booking-form" aria-labelledby="group-booking-heading">
    <div class="container">
      <div class="contact-departments-header">
        <p class="section-eyebrow">Get Started</p>
        <h2 id="group-booking-heading" class="section-title">Get Your Custom Training Quote in 24 Hours</h2>
        <p class="contact-departments-description">Tell us about your team and training needs. We'll send you a custom quote within 24 hours - no obligation, no hassle.</p>
      </div>

      <div class="contact-form-card group-booking-form-wrapper">
        <div id="group-booking-success" class="contact-form-success" style="display: none;">
          <div class="contact-form-success-content">
            <i class="fas fa-check-circle contact-form-success-icon" aria-hidden="true"></i>
            <h3 class="contact-form-success-title">Request Sent!</h3>
            <p class="contact-form-success-text">We'll get back to you with a custom quote.</p>
          </div>
        </div>

        <form id="group-booking-form-element" class="contact-form" novalidate>
          <input type="text" name="website" id="group-booking-website" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
          <input type="text" name="url" id="group-booking-url" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
          <input type="text" name="homepage" id="group-booking-homepage" class="honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
          <input type="hidden" name="form_load_time" id="group-booking-form-load-time" value="">
          <input type="hidden" name="submission_time" id="group-booking-submission-time" value="">

          <div id="group-booking-error-summary" class="contact-form-error-summary" role="alert" aria-live="assertive" style="display: none">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <div>
              <strong>Please correct the following errors:</strong>
              <ul id="group-booking-error-list"></ul>
            </div>
          </div>

          <div class="contact-form-grid">
            <div class="contact-form-field">
              <label for="group-booking-name" class="contact-form-label">
                Full Name <span class="contact-form-required">*</span>
              </label>
              <input type="text" id="group-booking-name" name="name" required class="contact-form-input" placeholder="John Smith" autocomplete="name" aria-describedby="group-booking-name-error" aria-invalid="false" />
              <p id="group-booking-name-error" class="contact-form-error" role="alert" aria-live="polite" style="display: none"></p>
            </div>
            <div class="contact-form-field">
              <label for="group-booking-email" class="contact-form-label">
                Email Address <span class="contact-form-required">*</span>
              </label>
              <input type="email" id="group-booking-email" name="email" required class="contact-form-input" placeholder="john@organisation.com" autocomplete="email" aria-describedby="group-booking-email-error" aria-invalid="false" />
              <p id="group-booking-email-error" class="contact-form-error" role="alert" aria-live="polite" style="display: none"></p>
            </div>
          </div>

          <div class="contact-form-grid">
            <div class="contact-form-field">
              <label for="group-booking-phone" class="contact-form-label">
                Phone Number <span class="contact-form-required">*</span>
              </label>
              <input type="tel" id="group-booking-phone" name="phone" required class="contact-form-input" placeholder="01622 123 456" autocomplete="tel" aria-describedby="group-booking-phone-error" aria-invalid="false" />
              <p id="group-booking-phone-error" class="contact-form-error" role="alert" aria-live="polite" style="display: none"></p>
            </div>
            <div class="contact-form-field">
              <label for="group-booking-organisation" class="contact-form-label">
                Organisation Name <span class="contact-form-required">*</span>
              </label>
              <input type="text" id="group-booking-organisation" name="organisation" required class="contact-form-input" placeholder="Your Care Home Ltd" autocomplete="organization" aria-describedby="group-booking-organisation-error" aria-invalid="false" />
              <p id="group-booking-organisation-error" class="contact-form-error" role="alert" aria-live="polite" style="display: none"></p>
            </div>
          </div>

          <div class="contact-form-grid">
            <div class="contact-form-field contact-form-field-staff">
              <label for="group-booking-numberOfStaff" class="contact-form-label">
                Number of Staff <span class="contact-form-required">*</span>
              </label>
              <input type="number" id="group-booking-numberOfStaff" name="numberOfStaff" required min="1" max="100" class="contact-form-input" placeholder="10" aria-describedby="group-booking-numberOfStaff-error" aria-invalid="false" />
              <div id="staff-number-hint" class="staff-number-hint" role="alert" aria-live="polite" style="display: none;">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span>Our sessions are limited to 12 people, but we can organise multiple sessions for larger groups.</span>
              </div>
              <p id="group-booking-numberOfStaff-error" class="contact-form-error" role="alert" aria-live="polite" style="display: none"></p>
            </div>
            <div class="contact-form-field">
              <label for="group-booking-trainingType" class="contact-form-label">
                Training Type <span class="contact-form-required">*</span>
              </label>
              <select id="group-booking-trainingType" name="trainingType" required class="contact-form-select" aria-describedby="group-booking-trainingType-error" aria-invalid="false">
                <option value="onsite">On-Site Training</option>
                <option value="classroom">Classroom Sessions</option>
                <option value="custom">Custom Package</option>
              </select>
              <p id="group-booking-trainingType-error" class="contact-form-error" role="alert" aria-live="polite" style="display: none"></p>
            </div>
          </div>

          <div class="contact-form-field">
            <label for="group-booking-details" class="contact-form-label">
              Training Details (Preferred dates, courses, or any requirements)
            </label>
            <textarea id="group-booking-details" name="details" rows="3" class="contact-form-textarea" placeholder="Preferred dates, courses, or specific requirements..."></textarea>
          </div>

          <div class="contact-form-field">
            <label for="group-booking-discount-code" class="contact-form-label">
              Discount Code (Optional)
            </label>
            <div class="contact-form-input-wrapper">
              <input
                type="text"
                id="group-booking-discount-code"
                name="discount_code"
                class="contact-form-input"
                placeholder="Enter code"
                autocomplete="off"
                aria-describedby="group-booking-discount-code-error"
                aria-invalid="false"
                style="text-transform: uppercase;"
              />
              <i class="fas fa-check-circle contact-form-success-icon" aria-hidden="true"></i>
              <i class="fas fa-exclamation-circle contact-form-error-icon" aria-hidden="true"></i>
            </div>
            <p
              id="group-booking-discount-code-error"
              class="contact-form-error"
              role="alert"
              aria-live="polite"
              style="display: none"
            ></p>
          </div>

          <div class="contact-form-consent">
            <div class="contact-form-consent-checkbox-wrapper">
              <input type="checkbox" id="group-booking-consent" name="consent" required class="contact-form-consent-checkbox" aria-describedby="group-booking-consent-error" aria-invalid="false" />
              <label for="group-booking-consent" class="contact-form-consent-label">
                I would like to be contacted about training services.<span class="contact-form-required">*</span>
              </label>
            </div>
            <div class="contact-form-consent-checkbox-wrapper" style="margin-top: 12px;">
              <input type="checkbox" id="group-marketing-consent" name="marketingConsent" checked class="contact-form-consent-checkbox" />
              <label for="group-marketing-consent" class="contact-form-consent-label">
                I would like to receive updates, offers, and training news from Continuity of Care Services.
              </label>
            </div>
            <p id="group-booking-consent-error" class="contact-form-error" role="alert" aria-live="polite" style="display: none"></p>
          </div>
          <input type="hidden" name="g-recaptcha-response" id="group-booking-recaptcha-response" value="">
          <p class="contact-form-privacy">
            By submitting this form, you agree to our <a href="<?php echo esc_url(ccs_page_url('privacy')); ?>" class="contact-form-privacy-link">Privacy Policy</a>
          </p>

          <button type="submit" id="group-booking-submit" class="contact-form-submit">Get My Free Quote</button>
        </form>
      </div>

      <div class="group-booking-contact">
        <p class="group-booking-contact-text">Prefer to speak directly?</p>
        <div class="group-booking-contact-links">
          <a href="<?php echo esc_url($contact['phone_link']); ?>" class="group-booking-contact-link group-booking-contact-link-primary">
            <i class="fas fa-phone" aria-hidden="true"></i>
            Call: <?php echo esc_html($contact['phone']); ?>
          </a>
          <a href="mailto:<?php echo esc_attr($contact['email']); ?>" class="group-booking-contact-link">
            <i class="fas fa-envelope" aria-hidden="true"></i>
            Email Us
          </a>
          <a href="<?php echo esc_url(ccs_page_url('contact') . '?type=schedule-call'); ?>" class="group-booking-contact-link">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            Schedule a Call
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="group-faq-section" id="faq" aria-labelledby="faq-heading">
    <div class="container">
      <div class="group-faq-header">
        <h2 id="faq-heading" class="group-faq-title">Frequently Asked Questions</h2>
      </div>

      <div class="faq-two-column">
        <aside class="faq-sidebar" aria-label="FAQ categories">
          <nav class="faq-sidebar-nav">
            <button type="button" class="faq-sidebar-btn active" data-category="all">All</button>
            <button type="button" class="faq-sidebar-btn" data-category="general">General Questions</button>
            <button type="button" class="faq-sidebar-btn" data-category="pricing">Pricing</button>
            <button type="button" class="faq-sidebar-btn" data-category="scheduling">Scheduling</button>
            <button type="button" class="faq-sidebar-btn" data-category="policies">Policies</button>
          </nav>
        </aside>

        <div class="faq-content-panel">
          <div class="group-faq-list">
            <?php
            $faqs = [
              ['category' => 'general', 'question' => 'How many people can attend a group training session?', 'answer' => 'Our standard group training sessions accommodate up to 12 staff members per session. For larger groups, we can organise multiple sessions or arrange a custom training programme to suit your needs.'],
              ['category' => 'pricing', 'question' => "What's included in the group training price?", 'answer' => 'All group training sessions include an expert trainer with all necessary equipment, workbooks and handouts for each attendee, practical assessments, and digital certificates upon successful completion. For on-site training, we bring everything to you - you just need to provide a suitable training room.'],
              ['category' => 'scheduling', 'question' => 'How far in advance should we book group training?', 'answer' => "We recommend booking at least 2-3 weeks in advance to secure your preferred date. However, we understand that training needs can be urgent, so we'll do our best to accommodate shorter notice bookings when possible. Contact us to discuss your timeline."],
              ['category' => 'general', 'question' => 'Can we combine multiple courses into one group training session?', 'answer' => 'Yes! Our custom package option is perfect for organisations that need multiple courses. We can create a tailored training programme that combines several courses into a streamlined schedule, delivered either on-site or at our Maidstone Studios. This is ideal for larger groups and can offer additional savings.'],
              ['category' => 'policies', 'question' => 'What if we need to reschedule or cancel a booking?', 'answer' => 'We understand that plans can change. Please contact us as soon as possible if you need to reschedule. We offer flexible cancellation and rescheduling policies - typically, changes made more than 7 days in advance incur no charges. Contact us to discuss your specific situation.'],
              ['category' => 'scheduling', 'question' => 'Do you offer training on evenings or weekends?', 'answer' => "Absolutely! We offer flexible scheduling to work around your operations. This includes evening sessions, weekend training, and training during shift patterns. When you request a quote, let us know your preferred dates and times, and we'll work with you to find a schedule that minimises disruption to your care services."],
            ];
            
            foreach ($faqs as $index => $faq) :
              $is_first = $index === 0;
            ?>
            <div class="accordion" data-accordion-group="group-training-faq" data-category="<?php echo esc_attr($faq['category']); ?>">
              <button type="button" class="accordion-trigger" aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>" aria-controls="faq-answer-<?php echo $index + 1; ?>">
                <span><?php echo esc_html($faq['question']); ?></span>
                <span class="accordion-icon" aria-hidden="true">
                  <i class="fas fa-plus" aria-hidden="true"></i>
                  <i class="fas fa-minus" aria-hidden="true"></i>
                </span>
              </button>
              <div id="faq-answer-<?php echo $index + 1; ?>" class="accordion-content" role="region" aria-hidden="<?php echo $is_first ? 'false' : 'true'; ?>">
                <?php echo wpautop(wp_kses_post($faq['answer'])); ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <div class="group-faq-cta">
        <div class="group-faq-cta-content">
          <h3 class="group-faq-cta-title">Still have questions?</h3>
          <p class="group-faq-cta-description">Our team is here to help you find the right training solution.</p>
          <a href="<?php echo esc_url(ccs_page_url('contact')); ?>" class="btn btn-primary group-faq-cta-button">Contact Us</a>
        </div>
      </div>
    </div>
  </section>
</main>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Group Training for Care Teams",
  "description": "Group training for care teams in Kent. Flexible scheduling, CPD-accredited certificates, and group rates for quality training.",
  "url": "<?php echo esc_url(get_permalink()); ?>",
  "provider": {
    "@type": "EducationalOrganization",
    "name": "Continuity of Care Services",
    "url": "<?php echo esc_url(home_url('/')); ?>",
    "telephone": "01622 587343",
    "email": "hello@continuitytrainingacademy.co.uk",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Maidstone",
      "addressRegion": "Kent",
      "addressCountry": "GB"
    }
  },
  "serviceType": "Group Training",
  "areaServed": {
    "@type": "State",
    "name": "Kent"
  },
  "offers": {
    "@type": "Offer",
    "description": "Group training rates for teams of 5 or more",
    "availability": "https://schema.org/InStock"
  },
  "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "name": "Group Training Options",
    "itemListElement": [
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Course",
          "name": "On-Site Training",
          "description": "Training delivered at your care facility"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Course",
          "name": "Flexible Scheduling",
          "description": "Training scheduled around your team's availability"
        }
      },
      {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Course",
          "name": "Customized Content",
          "description": "Training tailored to your service's policies and procedures"
        }
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
        "name": "Group Training",
        "item": "<?php echo esc_url(get_permalink()); ?>"
      }
    ]
  }
}
</script>

<?php get_footer(); ?>

