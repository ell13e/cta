# Legal Compliance & Policy Pages

This document consolidates policy requirements and content for Continuity of Care Services's website.

---

## Overview

**Policy Pages Required:**
1. Privacy Policy (`privacy-policy`)
2. Cookie Policy (`cookie-policy`)
3. Terms & Conditions (`terms-conditions`)

**Storage:** All policies are stored as WordPress pages (not code files)  
**Editing:** WordPress Admin → Pages or dedicated Policy Pages menu

---

## Privacy Policy

### Purpose
Explains what personal data we collect, how we use it, and users' rights under GDPR.

### Required Sections

1. **Data Controller**
   - Company: Continuity of Care Services
   - Email: enquiries@continuitytrainingacademy.co.uk
   - Phone: 01622 587343
   - Address: Maidstone, Kent, ME14 5NZ

2. **Information We Collect**
   - Contact forms: Name, Email, Phone, Address, Company name, Message
   - Newsletter signup: Email, Marketing consent
   - Course booking forms: Name, Email, Phone, Number of delegates, Additional info
   - Group training enquiries: Name, Email, Phone, Organisation name, Message
   - Callback requests: Name, Phone, Preferred callback time
   - Technical data: IP address, browser type, device info, pages visited (via Google Analytics if enabled)

3. **Legal Basis for Processing**
   - Consent (marketing emails)
   - Legitimate Interest (responding to enquiries)
   - Contract (processing bookings)

4. **How We Use Your Information**
   - Respond to enquiries
   - Process bookings
   - Send marketing emails (with consent)
   - Improve website performance
   - Business administration

5. **Sharing Your Information**
   - Mailchimp (email marketing - if used)
   - Google Analytics (website analytics - if enabled)
   - Service providers (hosting, security)

6. **Data Retention**
   - Enquiries: up to 12 months
   - Marketing data: until unsubscribe
   - Analytics: per Google Analytics retention policy
   - Course bookings: 7 years (tax/legal compliance)

7. **Your Rights (GDPR)**
   - Access, Rectification, Erasure, Restrict processing, Data portability, Object, Withdraw consent

8. **Cookies**
   - Link to Cookie Policy

9. **Data Transfers**
   - Mailchimp/Google may store data outside UK/EEA
   - Standard Contractual Clauses (SCCs) in place

10. **Contact Information**
    - Full contact details for data protection enquiries

---

## Cookie Policy

### Purpose
Explains what cookies are used, why, and how users can manage preferences.

### Required Sections

1. **What Are Cookies?**
   - Explanation of cookies
   - Session vs persistent cookies
   - First-party vs third-party cookies

2. **Types of Cookies We Use**

   **Essential Cookies:**
   - Cookie consent preference (1 year)
   - WordPress session cookies
   - reCAPTCHA (if enabled) - for spam protection

   **Analytics Cookies (if Google Analytics enabled):**
   - Google Analytics 4 (GA4)
   - Cookies: `_ga`, `_ga_*`, `_gid`, `_gat`
   - Purpose: Website analytics and performance tracking
   - Requires consent: Yes

   **Marketing Cookies (if Facebook Pixel enabled):**
   - Facebook Pixel
   - Cookies: `_fbp`, `fr`
   - Purpose: Conversion tracking and remarketing
   - Requires consent: Yes

3. **Third-Party Cookies**
   - Google Analytics (if enabled)
   - Facebook Pixel (if enabled)
   - Google reCAPTCHA (if enabled)

4. **Managing Cookie Preferences**
   - Cookie consent banner explanation
   - Browser settings instructions
   - Links to browser-specific guides

5. **Cookie Duration**
   - Session cookies: deleted when browser closes
   - Consent preference: 1 year
   - Analytics cookies: typically 2 years (Google Analytics)

6. **Your Rights**
   - Right to know what cookies are used
   - Right to refuse non-essential cookies
   - Right to delete cookies

7. **Contact Information**
   - Full contact details for cookie-related enquiries

---

## Terms & Conditions

### Purpose
Outlines the legal terms for using the website and booking services.

### Required Sections

1. **About Us**
   - Company details and contact information

2. **Eligibility**
   - Age requirement (18+)

3. **Use of Website**
   - Acceptable use
   - Prohibited activities

4. **No User Accounts**
   - No registration/login required

5. **Enquiries and Bookings**
   - Contact forms are for enquiries only
   - Bookings completed offline
   - No online payments

6. **Prices and Payment**
   - Prices for information only
   - Payments handled offline
   - No payment storage on website

7. **Intellectual Property**
   - Content ownership
   - Permitted uses
   - Prohibited uses

8. **Limitation of Liability**
   - Standard liability limitations
   - No fees for website use

9. **Data Protection**
   - Link to Privacy Policy

10. **Cookies**
    - Link to Cookie Policy

11. **Governing Law**
    - English law
    - English courts jurisdiction

12. **Changes to Terms**
    - How updates are communicated

13. **Contact Information**
    - Full contact details

---

## Verification Checklist

### Page Setup
- [ ] Privacy Policy page exists with slug `privacy-policy`
- [ ] Cookie Policy page exists with slug `cookie-policy`
- [ ] Terms & Conditions page exists with slug `terms-conditions`
- [ ] Each page has a "Last updated: [Date]" at the top

### Content Verification
- [ ] Privacy Policy includes all 10 sections
- [ ] Cookie Policy includes all 7 sections
- [ ] Terms & Conditions includes all 13 sections
- [ ] Contact information is current and accurate

### Tracking Services Check
Go to WordPress Admin → Settings → API Keys and verify:
- [ ] List which services are ENABLED
- [ ] Only mention enabled services in policies
  - [ ] Google Analytics?
  - [ ] Facebook Pixel?
  - [ ] reCAPTCHA?

### Links Verification
- [ ] Privacy Policy links to Cookie Policy
- [ ] Cookie Policy links to Privacy Policy
- [ ] Terms links to both Privacy and Cookie policies
- [ ] All footer links work correctly

### Legal Compliance
- [ ] Company contact details are accurate
- [ ] GDPR rights section is complete
- [ ] Data retention periods are documented
- [ ] Data sharing partners are listed

---

## Content Templates

Complete policy content (ready to paste into WordPress page editor) is available in [POLICY_TEMPLATES.md](./POLICY_TEMPLATES.md).

### How to Use Templates
1. Open the policy content file
2. Copy the HTML content for the relevant policy
3. Go to WordPress Admin → Pages → Edit (policy page)
4. Paste into the page editor
5. Customize contact information and tracking service references
6. Update "Last updated" date
7. Publish

---

## Tracking Services Configuration

### Google Analytics
**Location:** WordPress Admin → Settings → API Keys  
**Field:** Google Analytics Measurement ID  
**Policy Impact:** If enabled, mention in Cookie Policy and Privacy Policy

### Facebook Pixel
**Location:** WordPress Admin → Settings → API Keys  
**Field:** Facebook Pixel ID  
**Policy Impact:** If enabled, mention in Cookie Policy and Privacy Policy

### reCAPTCHA
**Location:** WordPress Admin → Settings → API Keys  
**Fields:** reCAPTCHA Site Key + Secret Key  
**Policy Impact:** If enabled, mention in Privacy Policy

---

## Key Points

✅ **Store policies as WordPress pages** - easier to manage than hardcoded HTML  
✅ **Keep content current** - update when services change or annually  
✅ **Only mention enabled services** - don't list services you don't use  
✅ **Link policies together** - provide navigation between related policies  
✅ **Use plain language** - avoid legal jargon where possible  
✅ **Document data retention** - be specific about how long you keep data  

---

## Policy Checklist

This section consolidates the policy checklist requirements for quick reference.

### Privacy Policy Checklist

**Required Sections:**
1. ✅ Data Controller (company details, contact info)
2. ✅ Information We Collect (all form types, technical data)
3. ✅ Legal Basis for Processing (consent, legitimate interest, contract)
4. ✅ How We Use Your Information (enquiries, bookings, marketing, analytics)
5. ✅ Sharing Your Information (Mailchimp, Google Analytics, service providers)
6. ✅ Data Retention (enquiries: 12 months, marketing: until unsubscribe, bookings: 7 years)
7. ✅ Your Rights (GDPR - access, rectification, erasure, etc.)
8. ✅ Cookies (link to Cookie Policy)
9. ✅ Data Transfers (SCCs for international transfers)
10. ✅ Contact Information (data protection enquiries)

### Cookie Policy Checklist

**Required Sections:**
1. ✅ What Are Cookies? (explanation, session vs persistent, first-party vs third-party)
2. ✅ Types of Cookies We Use:
   - Essential Cookies (consent preference, WordPress session, reCAPTCHA)
   - Analytics Cookies (Google Analytics 4 - if enabled)
   - Marketing Cookies (Facebook Pixel - if enabled)
3. ✅ Third-Party Cookies (list enabled services only)
4. ✅ Managing Cookie Preferences (banner explanation, browser settings)
5. ✅ Cookie Duration (session, 1 year, 2 years for analytics)
6. ✅ Your Rights (right to know, refuse, delete)
7. ✅ Contact Information (cookie-related enquiries)

### Terms & Conditions Checklist

**Required Sections:**
1. ✅ About Us (company details)
2. ✅ Eligibility (18+)
3. ✅ Use of Website (acceptable use, prohibited activities)
4. ✅ No User Accounts (no registration required)
5. ✅ Enquiries and Bookings (enquiries only, offline bookings, no online payments)
6. ✅ Prices and Payment (information only, offline payments)
7. ✅ Intellectual Property (content ownership, permitted/prohibited uses)
8. ✅ Limitation of Liability (standard limitations)
9. ✅ Data Protection (link to Privacy Policy)
10. ✅ Cookies (link to Cookie Policy)
11. ✅ Governing Law (English law, English courts)
12. ✅ Changes to Terms (how updates communicated)
13. ✅ Contact Information (full contact details)

### Quick Verification Steps

1. **Check each policy page exists:**
   - Go to WordPress Admin → Pages
   - Look for pages with slugs: `privacy-policy`, `cookie-policy`, `terms-conditions`

2. **Verify content includes:**
   - All sections listed above
   - Current contact information
   - Accurate description of data collection
   - Correct tracking services (only mention what's actually enabled)

3. **Check tracking services:**
   - WordPress Admin → Settings → API Keys
   - See which services are configured:
     - Google Analytics ID
     - Facebook Pixel ID
     - reCAPTCHA keys
   - Only mention services that are actually configured

4. **Update "Last updated" dates:**
   - Each policy should have a "Last updated: [Date]" at the top

5. **Test links:**
   - Privacy Policy should link to Cookie Policy
   - Cookie Policy should link to Privacy Policy
   - Terms should link to both Privacy and Cookie policies

---

## Related Documents

- [POLICY_TEMPLATES.md](./POLICY_TEMPLATES.md) - Complete copy-paste policy content (ready to use)
