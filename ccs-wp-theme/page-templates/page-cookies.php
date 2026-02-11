<?php
/**
 * Template Name: Cookie Policy
 *
 * This template displays the Cookie Policy page content.
 * Content is hardcoded and not editable in WordPress admin.
 *
 * @package ccs-theme
 */

get_header();

$contact = ccs_get_contact_info();
$ga_enabled = !empty(get_option('ccs_google_analytics_id', ''));
$fb_pixel_enabled = !empty(get_option('ccs_facebook_pixel_id', ''));
$recaptcha_enabled = !empty(function_exists('ccs_get_recaptcha_site_key') ? ccs_get_recaptcha_site_key() : get_theme_mod('ccs_recaptcha_site_key', ''));
?>

<main id="main-content">
  <section class="group-hero-section" aria-labelledby="cookies-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
          </li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item">
            <span class="breadcrumb-current" aria-current="page">Cookie Policy</span>
          </li>
        </ol>
      </nav>
      <h1 id="cookies-heading" class="hero-title">Cookie Policy</h1>
      <p class="hero-subtitle">Last updated: <?php echo get_the_modified_date('F j, Y'); ?></p>
    </div>
  </section>

  <section class="legal-content-section">
    <div class="container">
      <div class="legal-content">
        <p>This Cookie Policy explains how Continuity of Care Services ("we", "us", "our") uses cookies and similar tracking technologies on our website <a href="https://www.continuitytrainingacademy.co.uk">https://www.continuitytrainingacademy.co.uk</a> ("Website").</p>
        <p>It should be read together with our <a href="<?php echo esc_url(ccs_page_url('privacy-policy')); ?>">Privacy Policy</a>, which explains how we collect and use personal information.</p>
        <p>By continuing to use our Website, you consent to the use of cookies described in this policy, unless you have disabled them in your browser or cookie settings.</p>

        <h2>1. What are Cookies?</h2>
        <p>Cookies are small text files placed on your device when you visit a website. Cookies allow the website to recognise your device, remember preferences, and improve functionality and performance.</p>
        <p>Cookies can be:</p>
        <ul>
          <li><strong>Session cookies:</strong> deleted when you close your browser</li>
          <li><strong>Persistent cookies:</strong> remain on your device for a set period</li>
          <li><strong>First-party cookies:</strong> set by our Website</li>
          <li><strong>Third-party cookies:</strong> set by external services we use</li>
        </ul>
        <p>Cookies may contain anonymous identifiers, but do not generally store personal information unless you provide it to us voluntarily.</p>

        <h2>2. Types of Cookies We Use</h2>
        <p>We use a combination of the following cookie types:</p>

        <h3>Strictly Necessary Cookies</h3>
        <p>These cookies are essential for using the Website and cannot be switched off. They allow for basic functions such as navigating pages, submitting forms, and accessing secure areas.</p>
        <p>Without these cookies, the Website may not function correctly.</p>
        <ul>
          <li><strong>Cookie Consent Preference:</strong> Remembers your cookie preferences (stored for 1 year)</li>
          <li><strong>WordPress Session:</strong> Maintains your session while using the website</li>
          <?php if ($recaptcha_enabled) : ?>
          <li><strong>Google reCAPTCHA:</strong> Used to protect our forms from spam (session cookies)</li>
          <?php endif; ?>
        </ul>

        <?php if ($ga_enabled) : ?>
        <h3>Analytics and Performance Cookies</h3>
        <p>We use analytics tools to understand how visitors use our Website. These cookies help us measure performance, improve page experience, and identify issues.</p>
        <p>We use <strong>Google Analytics 4 (GA4)</strong> for website analytics and performance tracking.</p>
        <p>These cookies collect usage information such as:</p>
        <ul>
          <li>Pages visited</li>
          <li>Time spent on pages</li>
          <li>Browser type and device</li>
          <li>General geographic location (not precise address)</li>
          <li>Referring websites</li>
        </ul>
        <p>Cookies used: <code>_ga</code>, <code>_ga_*</code>, <code>_gid</code>, <code>_gat</code></p>
        <p>Data collected is aggregated and does not directly identify individual users. Analytics only loads if you accept cookies via our consent banner.</p>
        <p>You can learn how Google uses data here: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">https://policies.google.com/privacy</a></p>
        <?php endif; ?>

        <?php if ($fb_pixel_enabled) : ?>
        <h3>Marketing Cookies</h3>
        <p>We use <strong>Facebook Pixel</strong> for conversion tracking and remarketing. This only works if you accept marketing cookies.</p>
        <p>Cookies used: <code>_fbp</code>, <code>fr</code></p>
        <p>These cookies track interactions with our Website and may be used to show relevant ads on Facebook.</p>
        <p>You can learn more about Facebook's use of data here: <a href="https://www.facebook.com/privacy/explanation" target="_blank" rel="noopener">https://www.facebook.com/privacy/explanation</a></p>
        <?php endif; ?>

        <h2>3. Third-Party Cookies</h2>
        <p>Some cookies are set by external providers to support analytics, advertising, or security features. These third parties may be located outside the UK/EEA.</p>
        <p>Where this happens, safeguards such as Standard Contractual Clauses (SCCs) may apply.</p>
        <p>Third-party services we use include:</p>
        <ul>
          <?php if ($ga_enabled) : ?>
          <li>Google Analytics</li>
          <?php endif; ?>
          <?php if ($fb_pixel_enabled) : ?>
          <li>Facebook Pixel</li>
          <?php endif; ?>
          <?php if ($recaptcha_enabled) : ?>
          <li>Google reCAPTCHA</li>
          <?php endif; ?>
        </ul>

        <h2>4. How to Manage Cookies</h2>

        <h3>Cookie Banner</h3>
        <p>When you first visit our Website, you will see a Cookie Notice that allows you to control cookie settings. You can choose to:</p>
        <ul>
          <li><strong>Accept All:</strong> Allows all cookies including analytics</li>
          <li><strong>Essential Only:</strong> Only allows essential cookies needed for the website to work</li>
        </ul>
        <p>You can change your preferences at any time by clearing your browser cookies or contacting us.</p>

        <h3>Browser Settings</h3>
        <p>Most web browsers allow control over cookies through settings. You can:</p>
        <ul>
          <li>Block cookies altogether</li>
          <li>Delete existing cookies</li>
          <li>Set notifications for new cookies</li>
        </ul>
        <p>However, blocking certain cookies may affect Website performance.</p>
        <p>To manage cookies in your browser, see:</p>
        <ul>
          <li><strong>Chrome:</strong> <a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">https://support.google.com/chrome/answer/95647</a></li>
          <li><strong>Firefox:</strong> <a href="https://support.mozilla.org/en-US/kb/enhanced-tracking-protection-firefox-desktop" target="_blank" rel="noopener">https://support.mozilla.org/en-US/kb/enhanced-tracking-protection-firefox-desktop</a></li>
          <li><strong>Safari:</strong> <a href="https://support.apple.com/en-gb/guide/safari/sfri11471/mac" target="_blank" rel="noopener">https://support.apple.com/en-gb/guide/safari/sfri11471/mac</a></li>
          <li><strong>Edge:</strong> <a href="https://support.microsoft.com/en-gb/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener">https://support.microsoft.com/en-gb/microsoft-edge/delete-cookies-in-microsoft-edge</a></li>
        </ul>

        <h2>5. How Long Cookies Are Stored</h2>
        <ul>
          <li><strong>Session cookies:</strong> Deleted when you close your browser</li>
          <li><strong>Cookie consent preference:</strong> Stored for 1 year</li>
          <?php if ($ga_enabled) : ?>
          <li><strong>Analytics cookies:</strong> Typically stored for 2 years (Google Analytics)</li>
          <?php endif; ?>
        </ul>

        <h2>6. Your Rights</h2>
        <p>Under GDPR, you have the right to:</p>
        <ul>
          <li>Know what cookies we use and why</li>
          <li>Refuse non-essential cookies</li>
          <li>Delete cookies from your browser at any time</li>
          <li>Contact us with questions about our cookie usage</li>
        </ul>

        <h2>7. Contact Us</h2>
        <p>If you have any questions about this Cookie Policy or how we use cookies, please contact us:</p>
        <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo esc_html($contact['email']); ?></a><br>
        <strong>Website contact form:</strong> <a href="<?php echo esc_url(ccs_page_url('contact')); ?>">Contact Us</a><br>
        <strong>Phone:</strong> <?php echo esc_html($contact['phone']); ?></p>

        <h2>8. Updates to This Cookie Policy</h2>
        <p>We may update this Cookie Policy from time to time. Any changes will be published on this page with an updated "Last updated" date shown at the top of this page.</p>
        <p>If we introduce new types of cookies, we may request renewed consent.</p>
      </div>
    </div>
  </section>

  <section class="legal-cta-section">
    <div class="container">
      <div class="legal-cta-content">
        <h2>Questions About Cookies?</h2>
        <p>If you have any questions about how we use cookies, please get in touch.</p>
        <div class="legal-cta-buttons">
          <a href="<?php echo esc_url(ccs_page_url('contact')); ?>" class="btn btn-primary">Contact Us</a>
          <a href="<?php echo esc_url(ccs_page_url('privacy-policy')); ?>" class="btn btn-secondary">Privacy Policy</a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>
