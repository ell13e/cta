# Policy Content Reference

This document contains the complete policy content that should be copied into the WordPress page editor for each policy page. The content aligns with [COMPLIANCE.md](./COMPLIANCE.md) and reflects what the website actually uses.

**IMPORTANT:** Before using this content:
1. Check which tracking services are enabled: WordPress Admin → Settings → API Keys
2. Only include services that are actually configured (Google Analytics, Facebook Pixel, reCAPTCHA)
3. Update contact information if it differs from what's shown here
4. Replace `[contact-url]` placeholders with actual WordPress page URLs using `cta_page_url()` function or relative paths

---

## Privacy Policy Content

Copy this into the WordPress page editor for the `privacy-policy` page:

```html
<h2>1. Data Controller</h2>
<p>The Data Controller responsible for your personal information is:</p>
<p><strong>Continuity of Care Services</strong><br>
Website: <a href="https://www.continuitytrainingacademy.co.uk">https://www.continuitytrainingacademy.co.uk</a><br>
Email: <a href="mailto:enquiries@continuitytrainingacademy.co.uk">enquiries@continuitytrainingacademy.co.uk</a><br>
Phone: 01622 587343<br>
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
<p>If you choose to opt in, we collect your email address and marketing consent preference to send you updates about new courses, CQC changes, and training opportunities.</p>

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

<p><strong>We do not collect payment details online.</strong> Payment for our services is made after contacting us.</p>

<h2>3. Legal Basis for Processing</h2>
<p>We process personal data under the following lawful bases under UK GDPR:</p>
<ul>
<li><strong>Consent (Article 6(1)(a))</strong><br>
When you opt in to receive marketing emails, you give us consent to use your contact details for this purpose.</li>
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
<li>Improve the performance and usability of our website</li>
<li>Maintain records for business administration</li>
</ul>
<p>We do <strong>not</strong> sell or share your personal information with third parties for their own marketing.</p>

<h2>5. Sharing Your Information</h2>
<p>We may share your information with trusted service providers who support our operations, including:</p>
<ul>
<li><strong>Mailchimp</strong> (email marketing platform - if used for newsletters)</li>
<li><strong>Google</strong> (Google Analytics - if enabled)</li>
<li><strong>Service providers</strong> (hosting, security, website maintenance)</li>
</ul>
<p>These providers act as <strong>data processors</strong> and only process data according to our instructions.</p>
<p>If required by law or regulatory authorities, we may disclose personal data where necessary.</p>

<h2>6. Data Retention</h2>
<p>We only keep personal data for as long as necessary to fulfil the purposes described in this Privacy Policy, or as long as required by law.</p>
<ul>
<li><strong>Enquiries:</strong> up to 12 months</li>
<li><strong>Marketing data (opt-in):</strong> until you unsubscribe</li>
<li><strong>Course bookings:</strong> 7 years (for tax and legal compliance)</li>
<li><strong>Analytics data:</strong> stored according to Google Analytics retention policy (typically 26 months)</li>
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
<p>Our website uses cookies and similar tracking technologies to improve user experience and analyse website traffic. For detailed information about the cookies we use, please see our <a href="/cookie-policy/">Cookie Policy</a>.</p>

<h2>9. Data Transfers Outside the UK</h2>
<p>Mailchimp and Google may store personal data on servers outside the UK/EEA (for example, in the United States). When this occurs, we ensure that adequate safeguards are in place, including <strong>Standard Contractual Clauses (SCCs)</strong> approved under UK GDPR.</p>

<h2>10. Contact Us</h2>
<p>If you have any questions about this Privacy Policy or our data practices, you can contact us by:</p>
<p><strong>Email:</strong> <a href="mailto:enquiries@continuitytrainingacademy.co.uk">enquiries@continuitytrainingacademy.co.uk</a><br>
<strong>Website contact form:</strong> <a href="/contact/">Contact Us</a><br>
<strong>Phone:</strong> 01622 587343</p>

<h2>11. Changes to This Policy</h2>
<p>We may update this Privacy Policy from time to time. The most recent version will always be published on our website with the "Last updated" date shown at the top of this page.</p>
```

---

## Cookie Policy Content

Copy this into the WordPress page editor for the `cookie-policy` page:

**IMPORTANT:** Only include sections for tracking services that are actually enabled. Check WordPress Admin → Settings → API Keys.

```html
<p>This Cookie Policy explains how Continuity of Care Services ("we", "us", "our") uses cookies and similar tracking technologies on our website <a href="https://www.continuitytrainingacademy.co.uk">https://www.continuitytrainingacademy.co.uk</a> ("Website").</p>
<p>It should be read together with our <a href="/privacy-policy/">Privacy Policy</a>, which explains how we collect and use personal information.</p>
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
<!-- Only include if reCAPTCHA is enabled -->
<li><strong>Google reCAPTCHA:</strong> Used to protect our forms from spam (session cookies)</li>
</ul>

<h3>Analytics and Performance Cookies</h3>
<!-- Only include this section if Google Analytics is enabled -->
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
<!-- End Google Analytics section -->

<h3>Marketing Cookies</h3>
<!-- Only include this section if Facebook Pixel is enabled -->
<p>We use <strong>Facebook Pixel</strong> for conversion tracking and remarketing. This only works if you accept marketing cookies.</p>
<p>Cookies used: <code>_fbp</code>, <code>fr</code></p>
<p>These cookies track interactions with our Website and may be used to show relevant ads on Facebook.</p>
<p>You can learn more about Facebook's use of data here: <a href="https://www.facebook.com/privacy/explanation" target="_blank" rel="noopener">https://www.facebook.com/privacy/explanation</a></p>
<!-- End Facebook Pixel section -->

<h2>3. Third-Party Cookies</h2>
<p>Some cookies are set by external providers to support analytics, advertising, or security features. These third parties may be located outside the UK/EEA.</p>
<p>Where this happens, safeguards such as Standard Contractual Clauses (SCCs) may apply.</p>
<p>Third-party services we use include:</p>
<ul>
<!-- Only list services that are actually enabled -->
<li>Google Analytics (if enabled)</li>
<li>Facebook Pixel (if enabled)</li>
<li>Google reCAPTCHA (if enabled)</li>
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
<li><strong>Analytics cookies:</strong> Typically stored for 2 years (Google Analytics)</li>
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
<p><strong>Email:</strong> <a href="mailto:enquiries@continuitytrainingacademy.co.uk">enquiries@continuitytrainingacademy.co.uk</a><br>
<strong>Website contact form:</strong> <a href="/contact/">Contact Us</a><br>
<strong>Phone:</strong> 01622 587343</p>

<h2>8. Updates to This Cookie Policy</h2>
<p>We may update this Cookie Policy from time to time. Any changes will be published on this page with an updated "Last updated" date shown at the top of this page.</p>
<p>If we introduce new types of cookies, we may request renewed consent.</p>
```

---

## Terms & Conditions Content

Copy this into the WordPress page editor for the `terms-conditions` page:

```html
<p>These Terms of Service ("Terms") govern your use of the website <a href="https://www.continuitytrainingacademy.co.uk">https://www.continuitytrainingacademy.co.uk</a> ("Website") operated by Continuity of Care Services ("we", "us", "our").</p>
<p>By using the Website, you agree to be bound by these Terms. If you do not agree, you should stop using the Website.</p>

<h2>1. About Us</h2>
<p><strong>Continuity of Care Services</strong></p>
<p>Website: <a href="https://www.continuitytrainingacademy.co.uk">https://www.continuitytrainingacademy.co.uk</a><br>
Email: <a href="mailto:enquiries@continuitytrainingacademy.co.uk">enquiries@continuitytrainingacademy.co.uk</a><br>
Phone: 01622 587343<br>
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
<p>For details on how we collect and use personal data, please refer to our <a href="/privacy-policy/">Privacy Policy</a>.</p>

<h2>10. Cookies</h2>
<p>Our use of cookies and similar technologies is explained in our <a href="/cookie-policy/">Cookie Policy</a>.</p>

<h2>11. Governing Law</h2>
<p>These Terms are governed by the laws of England and Wales.</p>
<p>Any disputes shall be subject to the exclusive jurisdiction of the courts of England and Wales.</p>

<h2>12. Changes to These Terms</h2>
<p>We may update these Terms from time to time. The latest version will always be published on this page with the updated "Last updated" date shown at the top of this page.</p>
<p>Continued use of the Website after changes are published constitutes acceptance of the updated Terms.</p>

<h2>13. Contact Us</h2>
<p>If you have any questions about these Terms, please contact us:</p>
<p><strong>Email:</strong> <a href="mailto:enquiries@continuitytrainingacademy.co.uk">enquiries@continuitytrainingacademy.co.uk</a><br>
<strong>Website contact form:</strong> <a href="/contact/">Contact Us</a><br>
<strong>Phone:</strong> 01622 587343</p>
```

---

## Usage Instructions

1. **Copy the relevant content** from above into the WordPress page editor
2. **Remove conditional sections** (marked with comments like `<!-- Only include if... -->`) for services that are NOT enabled
3. **Update URLs** - Replace `/privacy-policy/`, `/cookie-policy/`, `/contact/` with actual WordPress page URLs or use relative paths
4. **Verify contact information** matches your actual details
5. **Check tracking services** - Only mention Google Analytics, Facebook Pixel, or reCAPTCHA if they're actually configured in Settings → API Keys
6. **Save and preview** the pages to ensure formatting looks correct

---

## Quick Checklist Before Publishing

- [ ] All required sections from [COMPLIANCE.md](./COMPLIANCE.md) are included
- [ ] Only enabled tracking services are mentioned
- [ ] Contact information is accurate
- [ ] Links to other policy pages work correctly
- [ ] "Last updated" date is shown (handled automatically by template)
- [ ] Content matches what the website actually does/collects

