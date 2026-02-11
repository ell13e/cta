<?php
/**
 * Template Name: Accessibility Statement
 *
 * This template displays the Accessibility Statement page content.
 * Content is hardcoded and not editable in WordPress admin.
 *
 * @package ccs-theme
 */

get_header();

$contact = ccs_get_contact_info();
?>

<main id="main-content">
  <section class="group-hero-section" aria-labelledby="accessibility-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
          </li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <span class="breadcrumb-current" aria-current="page">Accessibility Statement</span>
          </li>
        </ol>
      </nav>
      <h1 id="accessibility-heading" class="hero-title">Accessibility Statement</h1>
      <p class="hero-subtitle">Last updated: <?php echo get_the_modified_date('F j, Y'); ?></p>
    </div>
  </section>

  <section class="legal-content-section">
    <div class="container">
      <div class="legal-content">
        <p>Continuity of Care Services is committed to ensuring digital accessibility for people with disabilities. We are continually improving the user experience for everyone and applying the relevant accessibility standards.</p>

        <h2>1. Our Commitment</h2>
        <p>We aim to conform to the Web Content Accessibility Guidelines (WCAG) 2.1 level AA standards. These guidelines explain how to make web content more accessible for people with disabilities, and user-friendly for everyone.</p>

        <h2>2. Measures to Support Accessibility</h2>
        <p>Continuity of Care Services takes the following measures to ensure accessibility:</p>
        <ul>
          <li>Include accessibility as part of our mission statement</li>
          <li>Include accessibility throughout our internal policies</li>
          <li>Assign clear accessibility targets and responsibilities</li>
          <li>Employ formal accessibility quality assurance methods</li>
          <li>Provide continual accessibility training for our staff</li>
        </ul>

        <h2>3. Conformance Status</h2>
        <p>The <a href="https://www.w3.org/WAI/WCAG21/quickref/?currentsidebar=%23col_customize&levels=aaa">Web Content Accessibility Guidelines (WCAG)</a> defines requirements for designers and developers to improve accessibility for people with disabilities. It defines three levels of conformance: Level A, Level AA, and Level AAA.</p>
        <p>This website aims to conform to WCAG 2.1 level AA. This means that the content should be accessible to people with a wide range of disabilities, including blindness and low vision, deafness and hearing loss, limited movement, speech disabilities, photosensitivity, and combinations of these, and some accommodation for learning disabilities and cognitive limitations.</p>

        <h2>4. Known Issues and Limitations</h2>
        <p>Despite our best efforts to ensure accessibility, there may be some limitations. Below is a description of known limitations, and potential solutions. Please contact us if you observe an issue not listed below.</p>
        <p><strong>Known limitations:</strong></p>
        <ul>
          <li>Some third-party content or embedded elements may not fully meet accessibility standards</li>
          <li>Older PDF documents may not be fully accessible</li>
          <li>Some interactive elements may require keyboard navigation improvements</li>
        </ul>
        <p>We are actively working to address these issues and improve the accessibility of our website.</p>

        <h2>5. Feedback</h2>
        <p>We welcome your feedback on the accessibility of Continuity of Care Services's website. Please let us know if you encounter accessibility barriers:</p>
        <ul>
          <li><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo esc_html($contact['email']); ?></a></li>
          <li><strong>Phone:</strong> <?php echo esc_html($contact['phone']); ?></li>
          <li><strong>Contact form:</strong> <a href="<?php echo esc_url(ccs_page_url('contact')); ?>">Contact Us</a></li>
        </ul>
        <p>We aim to respond to accessibility feedback within 5 business days.</p>

        <h2>6. Assessment Approach</h2>
        <p>Continuity of Care Services assessed the accessibility of this website through the following approaches:</p>
        <ul>
          <li>Self-evaluation</li>
          <li>External evaluation (where applicable)</li>
          <li>User testing with people with disabilities</li>
        </ul>

        <h2>7. Technical Specifications</h2>
        <p>Accessibility of this website relies on the following technologies to work with the particular combination of web browser and any assistive technologies or plugins installed on your computer:</p>
        <ul>
          <li>HTML</li>
          <li>WAI-ARIA</li>
          <li>CSS</li>
          <li>JavaScript</li>
        </ul>
        <p>These technologies are relied upon for conformance with the accessibility standards used.</p>

        <h2>8. Accessibility Features</h2>
        <p>Our website includes the following accessibility features:</p>
        <ul>
          <li>Semantic HTML structure for screen readers</li>
          <li>Keyboard navigation support</li>
          <li>Sufficient color contrast ratios</li>
          <li>Alternative text for images</li>
          <li>Form labels and error messages</li>
          <li>Skip navigation links</li>
          <li>Focus indicators for interactive elements</li>
        </ul>

        <h2>9. Third-Party Content</h2>
        <p>Some content on our website may be provided by third parties. We cannot guarantee the accessibility of third-party content, but we work with our partners to encourage accessible practices.</p>

        <h2>10. Updates to This Statement</h2>
        <p>We will review and update this accessibility statement regularly. The "Last updated" date at the top of this page indicates when the statement was last revised.</p>

        <h2>11. Enforcement Procedure</h2>
        <p>The Equality and Human Rights Commission (EHRC) is responsible for enforcing the Public Sector Bodies (Websites and Mobile Applications) (No. 2) Accessibility Regulations 2018 (the 'accessibility regulations'). If you're not happy with how we respond to your complaint, <a href="https://www.equalityadvisoryservice.com/" target="_blank" rel="noopener">contact the Equality Advisory and Support Service (EASS)</a>.</p>
      </div>
    </div>
  </section>

  <section class="legal-cta-section">
    <div class="container">
      <div class="legal-cta-content">
        <h2>Report an Accessibility Issue</h2>
        <p>If you encounter any accessibility barriers on our website, please let us know and we'll work to fix them.</p>
        <div class="legal-cta-buttons">
          <a href="<?php echo esc_url(ccs_page_url('contact')); ?>" class="btn btn-primary">Contact Us</a>
          <a href="mailto:<?php echo esc_attr($contact['email']); ?>" class="btn btn-secondary">Email Us</a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>

