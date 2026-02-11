<?php
/**
 * Template Name: Privacy Policy
 *
 * This template displays the Privacy Policy page content.
 * Content is hardcoded and not editable in WordPress admin.
 *
 * @package ccs-theme
 */

get_header();

$contact = ccs_get_contact_info();
$ga_enabled = !empty(get_option('ccs_google_analytics_id', ''));
?>

<main id="main-content">
  <section class="group-hero-section" aria-labelledby="privacy-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
          </li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <span class="breadcrumb-current" aria-current="page">Privacy Policy</span>
          </li>
        </ol>
      </nav>
      <h1 id="privacy-heading" class="hero-title">Privacy Policy</h1>
      <p class="hero-subtitle">Last updated: <?php echo get_the_modified_date('F j, Y'); ?></p>
    </div>
  </section>

  <section class="legal-content-section">
    <div class="container">
      <div class="legal-content">
        <h2>1. Data Controller</h2>
        <p>The Data Controller responsible for your personal information is:</p>
        <p><strong>Continuity of Care Services</strong><br>
        Website: <a href="https://www.continuitytrainingacademy.co.uk">https://www.continuitytrainingacademy.co.uk</a><br>
        Email: <a href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo esc_html($contact['email']); ?></a><br>
        Phone: <?php echo esc_html($contact['phone']); ?><br>
        Address: Maidstone, Kent, ME14 5NZ</p>

        <h2>2. Information We Collect</h2>
        <p>We may collect and process the following personal information when you use our website or contact us:</p>

        <h3>Information you provide through contact forms</h3>
        <ul>
          <li>First name and last name</li>
          <li>Email address</li>
          <li>Phone number</li>
          <li>Address, County, Postal code, City</li>
          <li>Company name</li>
          <li>Any additional information provided in the message field</li>
        </ul>

        <h3>Newsletter signup</h3>
        <p>If you choose to opt in, we collect your email address, first name, last name, date of birth (optional), and marketing consent preference to send you updates about new courses, CQC changes, and training opportunities.</p>
        
        <h4>Email tracking</h4>
        <p>When you receive emails from us, we may track:</p>
        <ul>
          <li><strong>Email opens:</strong> We use a small tracking pixel (1x1 invisible image) to detect when you open our emails. This helps us understand engagement and improve our communications.</li>
          <li><strong>Link clicks:</strong> We track which links you click in our emails to understand what content interests you most.</li>
          <li><strong>Technical data:</strong> We may collect your IP address and device information (browser type) when you interact with our emails. This data is used for security purposes and to prevent abuse.</li>
        </ul>
        <p><strong>You can opt-out of email tracking</strong> by disabling images in your email client, though this may affect email display. You can also unsubscribe from our emails at any time using the unsubscribe link in every email.</p>

        <h3>Course booking forms</h3>
        <p>When you submit a course booking enquiry, we collect:</p>
        <ul>
          <li>Name</li>
          <li>Email address</li>
          <li>Phone number</li>
          <li>Number of delegates</li>
          <li>Additional information about your requirements</li>
        </ul>

        <h3>Group training enquiries</h3>
        <p>When you submit a group training enquiry, we collect:</p>
        <ul>
          <li>Name</li>
          <li>Email address</li>
          <li>Phone number</li>
          <li>Organisation name</li>
          <li>Message about your training needs</li>
        </ul>

        <h3>Callback requests</h3>
        <p>When you request a callback, we collect:</p>
        <ul>
          <li>Name</li>
          <li>Phone number</li>
          <li>Preferred callback time</li>
        </ul>

        <?php if ($ga_enabled) : ?>
        <h3>Website analytics</h3>
        <p>We use <strong>Google Analytics</strong> to understand how visitors use our website. Google Analytics collects data such as:</p>
        <ul>
          <li>Browser type and version</li>
          <li>Pages visited</li>
          <li>Time spent on pages</li>
          <li>General geographic location (not precise address)</li>
          <li>Referring pages</li>
        </ul>
        <p>This information is anonymised and does not directly identify you. Analytics only loads if you accept cookies via our consent banner.</p>
        <?php endif; ?>

        <p><strong>We do not collect payment details online.</strong> Payment for our services is made after contacting us.</p>

        <h2>3. Legal Basis for Processing</h2>
        <p>We process personal data under the following lawful bases under UK GDPR:</p>
        <ul>
          <li><strong>Consent (Article 6(1)(a))</strong><br>
          When you opt in to receive marketing emails, you give us consent to use your contact details for this purpose. By subscribing, you also consent to email tracking (opens and clicks) which helps us improve our communications.</li>
          <li><strong>Legitimate Interest (Article 6(1)(f))</strong><br>
          To respond to enquiries, provide information about our services, and operate our website (analytics and optimisation).</li>
          <li><strong>Contract (Article 6(1)(b))</strong><br>
          When processing information necessary to provide services you request (for example, course bookings or follow-up enquiries).</li>
        </ul>
        <p>You can withdraw consent at any time by using the unsubscribe link in our emails or contacting us using the details below.</p>

        <h2>4. How We Use Your Information</h2>
        <p>We use personal information to:</p>
        <ul>
          <li>Respond to queries submitted through our contact forms</li>
          <li>Provide information about training services</li>
          <li>Process bookings and follow-up enquiries</li>
          <li>Send marketing emails when you opt in</li>
          <li>Track email engagement (opens and clicks) to improve our communications</li>
          <li>Improve the performance and usability of our website</li>
          <li>Maintain records for business administration</li>
        </ul>
        <p>We do <strong>not</strong> sell or share your personal information with third parties for their own marketing.</p>

        <h2>5. Sharing Your Information</h2>
        <p>We may share your information with trusted service providers who support our operations, including:</p>
        <ul>
          <?php if ($ga_enabled) : ?>
          <li><strong>Google</strong> (Google Analytics - if enabled)</li>
          <?php endif; ?>
          <li><strong>Service providers</strong> (hosting, security, website maintenance)</li>
        </ul>
        <p>These providers act as <strong>data processors</strong> and only process data according to our instructions.</p>
        <p>If required by law or regulatory authorities, we may disclose personal data where necessary.</p>

        <h2>6. Data Retention</h2>
        <p>We only keep personal data for as long as necessary to fulfil the purposes described in this Privacy Policy, or as long as required by law.</p>
        <ul>
          <li><strong>Enquiries:</strong> up to 12 months</li>
          <li><strong>Marketing data (opt-in):</strong> until you unsubscribe or request deletion</li>
          <li><strong>Email tracking data (opens/clicks):</strong> retained for 2 years for analytics purposes, then anonymised or deleted</li>
          <li><strong>Course bookings:</strong> 7 years (for tax and legal compliance)</li>
          <?php if ($ga_enabled) : ?>
          <li><strong>Analytics data:</strong> stored according to Google Analytics retention policy (typically 26 months)</li>
          <?php endif; ?>
        </ul>
        <p>You may request deletion of your personal data at any time.</p>

        <h2>7. Your Rights Under UK GDPR</h2>
        <p>You have the following rights regarding your personal data:</p>
        <ul>
          <li><strong>Right to access:</strong> Request a copy of the personal data we hold about you</li>
          <li><strong>Right to rectification:</strong> Ask us to correct inaccurate data</li>
          <li><strong>Right to erasure:</strong> Request deletion of your personal data</li>
          <li><strong>Right to restrict processing:</strong> Ask us to limit how we use your data</li>
          <li><strong>Right to data portability:</strong> Request your data in a machine-readable format</li>
          <li><strong>Right to object:</strong> Object to processing based on legitimate interests</li>
          <li><strong>Right to withdraw consent:</strong> Withdraw consent for marketing at any time</li>
        </ul>
        <p>To exercise any of these rights, please contact us using the details below.</p>

        <h2>8. Cookies</h2>
        <p>Our website uses cookies and similar tracking technologies to improve user experience and analyse website traffic. For detailed information about the cookies we use, please see our <a href="<?php echo esc_url(ccs_page_url('cookie-policy')); ?>">Cookie Policy</a>.</p>

        <h2>9. Data Transfers Outside the UK</h2>
        <?php if ($ga_enabled) : ?>
        <p>Google may store personal data on servers outside the UK/EEA (for example, in the United States). When this occurs, we ensure that adequate safeguards are in place, including <strong>Standard Contractual Clauses (SCCs)</strong> approved under UK GDPR.</p>
        <?php else : ?>
        <p>We do not currently transfer personal data outside the UK/EEA. If this changes in the future, we will ensure that adequate safeguards are in place, including <strong>Standard Contractual Clauses (SCCs)</strong> approved under UK GDPR.</p>
        <?php endif; ?>

        <h2>10. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy or our data practices, you can contact us by:</p>
        <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo esc_html($contact['email']); ?></a><br>
        <strong>Website contact form:</strong> <a href="<?php echo esc_url(ccs_page_url('contact')); ?>">Contact Us</a><br>
        <strong>Phone:</strong> <?php echo esc_html($contact['phone']); ?></p>

        <h2>11. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. The most recent version will always be published on our website with the "Last updated" date shown at the top of this page.</p>
      </div>
    </div>
  </section>

  <section class="legal-cta-section">
    <div class="container">
      <div class="legal-cta-content">
        <h2>Questions About Your Data?</h2>
        <p>If you have any questions about how we handle your personal information, please get in touch.</p>
        <div class="legal-cta-buttons">
          <a href="<?php echo esc_url(ccs_page_url('contact')); ?>" class="btn btn-primary">Contact Us</a>
          <a href="mailto:<?php echo esc_attr($contact['email']); ?>" class="btn btn-secondary">Email Us</a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>
