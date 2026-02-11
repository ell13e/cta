<?php
/**
 * Template Name: Terms & Conditions
 *
 * This template displays the Terms & Conditions page content.
 * Content is hardcoded and not editable in WordPress admin.
 *
 * @package ccs-theme
 */

get_header();

$contact = ccs_get_contact_info();
?>

<main id="main-content">
  <section class="group-hero-section" aria-labelledby="terms-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
          </li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <span class="breadcrumb-current" aria-current="page">Terms & Conditions</span>
          </li>
        </ol>
      </nav>
      <h1 id="terms-heading" class="hero-title">Terms & Conditions</h1>
      <p class="hero-subtitle">Last updated: <?php echo get_the_modified_date('F j, Y'); ?></p>
    </div>
  </section>

  <section class="legal-content-section">
    <div class="container">
      <div class="legal-content">
        <p>These Terms of Service ("Terms") govern your use of the website <a href="https://www.continuitytrainingacademy.co.uk">https://www.continuitytrainingacademy.co.uk</a> ("Website") operated by Continuity of Care Services ("we", "us", "our").</p>
        <p>By using the Website, you agree to be bound by these Terms. If you do not agree, you should stop using the Website.</p>

        <h2>1. About Us</h2>
        <p><strong>Continuity of Care Services</strong></p>
        <p>Website: <a href="https://www.continuitytrainingacademy.co.uk">https://www.continuitytrainingacademy.co.uk</a><br>
        Email: <a href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo esc_html($contact['email']); ?></a><br>
        Phone: <?php echo esc_html($contact['phone']); ?><br>
        Address: The Maidstone Studios, New Cut Road, Maidstone, Kent, ME14 5NZ</p>
        <p>We are a UK-based training provider offering training and development services for the health and social care sector.</p>

        <h2>2. Eligibility</h2>
        <p>The Website is intended for users aged 18 or older.</p>
        <p>By using the Website, you confirm that you are at least 18 years old.</p>

        <h2>3. Use of the Website</h2>
        <p>You agree to use the Website lawfully and in a manner that does not impair its operation or interfere with the use of the Website by others.</p>
        <p>You must not:</p>
        <ul>
          <li>Attempt to hack, disrupt, or compromise the Website or related systems</li>
          <li>Upload malicious code or harmful software</li>
          <li>Use the Website to send unsolicited communications or advertising</li>
          <li>Use the Website for unlawful, fraudulent, or harmful activity</li>
        </ul>
        <p>We may suspend or restrict access to the Website at any time if we believe the Website is being misused or compromised.</p>

        <h2>4. No User Accounts</h2>
        <p>We do not offer account registration or user login functions on the Website.</p>
        <p>You do not need an account to browse the Website or submit a contact enquiry.</p>

        <h2>5. Enquiries and Bookings</h2>
        <p>The Website allows you to request information about training services through contact forms. However, you cannot complete a purchase on the Website.</p>
        <p>All bookings, payments, and service agreements are completed after contacting us, and may be subject to separate terms issued at the time of booking.</p>
        <p>We do not offer subscription plans through the Website.</p>

        <h2>6. Prices and Payment</h2>
        <p>Prices shown on the Website (if any) are for information only and may change without notice.</p>
        <p>All payments for services are handled directly with Continuity of Care Services after an enquiry.</p>
        <p>We do not accept payments or store payment information through the Website.</p>

        <h2>7. Intellectual Property</h2>
        <p>All materials on the Website, including text, graphics, images, training descriptions, and branding, are owned by Continuity of Care Services or used under licence.</p>
        <p>You may:</p>
        <ul>
          <li>View pages for personal or informational use</li>
          <li>Link to pages publicly available on the Website</li>
        </ul>
        <p>You may not:</p>
        <ul>
          <li>Copy, reproduce, or distribute Website content</li>
          <li>Modify or create derivative works</li>
          <li>Use our content commercially without written permission</li>
        </ul>

        <h2>8. Limitation of Liability</h2>
        <p>To the extent permitted by law, we shall not be liable for:</p>
        <ul>
          <li>Indirect, incidental, or consequential losses</li>
          <li>Loss of profits, business, or data</li>
          <li>Errors or interruptions in Website operation</li>
          <li>Actions taken based on Website content</li>
        </ul>
        <p>Our total liability for any claim arising from your use of the Website shall be limited to £0, as no fees are charged for Website use.</p>
        <p>Nothing in these Terms excludes liability for death, personal injury, or fraud caused by our negligence.</p>

        <h2>9. Data Protection</h2>
        <p>We process personal data in accordance with UK GDPR and the Data Protection Act 2018.</p>
        <p>For details on how we collect and use personal data, please refer to our <a href="<?php echo esc_url(ccs_page_url('privacy-policy')); ?>">Privacy Policy</a>.</p>

        <h2>10. Cookies</h2>
        <p>Our use of cookies and similar technologies is explained in our <a href="<?php echo esc_url(ccs_page_url('cookie-policy')); ?>">Cookie Policy</a>.</p>

        <h2>11. Governing Law</h2>
        <p>These Terms are governed by the laws of England and Wales.</p>
        <p>Any disputes shall be subject to the exclusive jurisdiction of the courts of England and Wales.</p>

        <h2>12. Changes to These Terms</h2>
        <p>We may update these Terms from time to time. The latest version will always be published on this page with the updated "Last updated" date shown at the top of this page.</p>
        <p>Continued use of the Website after changes are published constitutes acceptance of the updated Terms.</p>

        <h2>13. Contact Us</h2>
        <p>If you have any questions about these Terms, please contact us:</p>
        <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo esc_html($contact['email']); ?></a><br>
        <strong>Website contact form:</strong> <a href="<?php echo esc_url(ccs_page_url('contact')); ?>">Contact Us</a><br>
        <strong>Phone:</strong> <?php echo esc_html($contact['phone']); ?></p>
      </div>
    </div>
  </section>

  <section class="legal-cta-section">
    <div class="container">
      <div class="legal-cta-content">
        <h2>Questions About Our Terms?</h2>
        <p>If you have any questions about our terms and conditions, please get in touch.</p>
        <div class="legal-cta-buttons">
          <a href="<?php echo esc_url(ccs_page_url('contact')); ?>" class="btn btn-primary">Contact Us</a>
          <a href="tel:<?php echo esc_attr($contact['phone']); ?>" class="btn btn-secondary">
            <i class="fas fa-phone" aria-hidden="true"></i>
            <?php echo esc_html($contact['phone']); ?>
          </a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>
