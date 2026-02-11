# Continuity Care Services (CCS) → WordPress Theme Transformation

## PROJECT CONTEXT

You are transforming the FINALCTAIHOPE WordPress theme (currently configured for Continuity of Care Services) into a professional domiciliary care provider website for **Continuity of Care Services**.

**DO NOT FABRICATE ANYTHING.** Use only the verified information below.

---

## VERIFIED BUSINESS INFORMATION

### Company Details
- **Legal Name**: Continuity of Care Services Limited
- **Company Number**: 09440482
- **CQC Location ID**: 1-2624556588
- **CQC Rating**: GOOD (as of 06/12/2023 - all categories rated Good)
- **Registered Manager**: Mrs Victoria Louise Walker (also Nominated Individual)
- **Company Status**: Active since 2015 (10 years)

### Physical Address
- **Office Location**: The Maidstone Studios, New Cut Road, Vinters Park, Maidstone, Kent, ME14 5NZ
- **Phone**: 01622 809881
- **Geographic Coverage**: Maidstone, Kent, Medway (across Kent)

### Digital Contact Information
- **Website**: https://www.continuitycareservices.co.uk (currently live - extract existing branding from this)
- **LinkedIn**: https://uk.linkedin.com/company/continuitycareservices
- **Facebook**: [Not found in search - may not have separate CCS Facebook]
- **Eventbrite**: [Link via Continuity of Care Services]

### Related Organization
- **Sister Company**: Continuity of Care Services (CTA)
- **CTA Phone**: +44 1622 587343 | +44 7535 351307
- **CTA Email**: enquiries@continuitytrainingacademy.co.uk
- **CTA Website**: continuitytrainingacademy.co.uk
- **CTA Facebook**: https://www.facebook.com/continuitytraining/
- **CTA LinkedIn**: https://uk.linkedin.com/company/continuity-training-academy-cta
- **CTA Location**: The Maidstone Studios, New Cut Road, Maidstone, United Kingdom

---

## CURRENT WEBSITE CONTENT (to preserve)

### Brand Voice (from https://www.continuitycareservices.co.uk)
- **Tagline**: "Your Team, Your Time, Your Life"
- **Brand Statement**: "It's not just what we do, it's how we do it."
- **Core Message**: "Compassionately supporting children and adults with domiciliary, disability, respite, complex, and palliative care, day or night, 24/7."

### Care Services Offered (from current site + CQC registration)
**Verified Services**:
- Personal care (daily living assistance)
- Complex care (PEG feeding, epilepsy, tracheostomy, ventilation, neurological conditions like muscular dystrophy)
- Domiciliary support
- Respite care (few hours to few days)
- Palliative/end-of-life care
- Dementia care
- Learning disabilities support
- Physical disabilities support
- Mental health support
- Children's services (pediatric disability care)
- Sensory impairments support
- Companionship care
- Treatment of disease, disorder or injury

**Age Groups**: Adults 65+, Adults under 65, Children (0-18 years)

### Current Website Messaging (Extract & Preserve)
```
"Our caring, local team is dedicated to supporting families across Kent. We don't rush or rotate staff every other week. Instead, we take the time to get to know each person, not just their care plan.

Our staff commit to discovering the quirks of every client, from how they like their toast to what puts them at ease on a tough day. We believe that the best care doesn't stop when the to-do list is ticked; it continues through our staff showing up in a way that feels friendly, familiar, and person-centred."
```

### Current Services Copy Structure
- **Personal Care**: "Getting dressed. Making breakfast. Remembering the right meds at the right time. Our carers provide gentle assistance with everyday tasks."
- **Respite Care**: "Whether it's for a few hours or a few days, our team are here to step in and provide gentle, reliable respite support. Take some time to rest, you can't pour from an empty cup."
- **Complex Care**: "From epilepsy care to PEG and mobility support, we provide complex care in the comfort of your own home. We work closely with families, nurses and healthcare teams to ensure we get it right, every time."

### Verified Testimonial
```
"I am delighted to express my gratitude for the outstanding care CCS delivered to my paraplegic father, requiring complex care. The skilled and compassionate team went above and beyond, addressing his unique needs with unwavering dedication. Their expertise and empathy transformed what could have been a challenging situation into a positive and reassuring experience for him and his family."
— Home Care Maidstone Client - Family Member Review
```

### Current Website Sections
- Hero section with tagline
- Services overview (personal care, respite, complex care)
- About approach/philosophy
- Care approach/philosophy section
- Partnerships section
- FAQs
- Careers section
- Contact section

### Current Testimonials/Reviews
- Homecare.co.uk rating: 4.9/5 (mentioned on website)
- Google/Trustpilot integration referenced but not confirmed

---

## THEME TO TRANSFORM

### Source Theme
- **Theme Name**: FINALCTAIHOPE
- **Tech Stack**: WordPress, PSR-4 architecture, custom post types (courses, course_events)
- **Current Configuration**: Training academy-focused
- **Location**: https://github.com/ell13e/FINALCTAIHOPE.git

### Post Types to REMOVE
- `course` (custom post type)
- `course_event` (custom post type)
- Associated taxonomies (course_level, instructor, etc.)
- Associated metaboxes for course data

### Post Types to KEEP/ADAPT
- Standard `post` (blog/news)
- `page` (static content)

### Post Types to CREATE
- `care_service` (replaces course CPT)
- Taxonomies:
  - `service_category` (Personal Care, Complex Care, etc.)
  - `care_condition` (Epilepsy, PEG feeding, etc.)
  - `coverage_area` (Maidstone, Medway, Kent areas)

### Forms to REMOVE
- Course enrollment form
- Course event registration form
- Course inquiry form

### Forms to CREATE
- Care assessment request form
- Commissioning/LA inquiry form
- 24-hour care emergency inquiry
- Respite care inquiry form
- General service inquiry
- Career application form
- Existing client support request
- Callback request
- Newsletter signup

### Assets to Migrate
- Logos (all formats)
- Brand colors (CSS variables)
- Typography system
- Navigation structure
- Header/footer components
- Button styles
- Card components
- Form styling

### Assets to VERIFY/EXTRACT from Current Site
- Actual brand colors used (primary, secondary, accent)
- Font families and weights
- All photography (real vs stock)
- All existing copy/testimonials
- Contact information formatting
- Social media links (LinkedIn confirmed, others TBD)

---

## CONTENT ARCHITECTURE

### Critical Pages (MUST CREATE/REPURPOSE)
1. **Homepage** (front-page.php)
   - Hero with "Your Team, Your Time, Your Life" tagline
   - CQC badge (GOOD rating, verified)
   - Primary CTA: "Book Free Care Assessment" or "Speak to Our Team"
   - Trust signals: CQC rating, phone number (01622 809881), Homecare.co.uk 4.9/5
   - Services overview
   - About approach section
   - Testimonial carousel (include verified review)
   - FAQs
   - Careers teaser
   - Contact CTA

2. **Services Hub** (archive-care_service.php)
   - All care service categories as cards
   - Filter by: Service Type, Care Condition, Coverage Area
   - Search functionality

3. **Individual Service Pages** (single-care_service.php)
   - Service name & description
   - Who it's for (specific scenarios, ages)
   - What's included (specific details)
   - How it works (process)
   - Real example or case study
   - Related services
   - CTA: "Request Assessment"

4. **About Us** (page-about.php)
   - Company story (from YouTube video: company started to serve real families)
   - Our approach & philosophy (preserve current messaging)
   - Registered Manager intro with photo
   - Team section (named individuals with photos when available)
   - Continuity of Care Services integration
   - CQC registration details with link to report

5. **Careers** (page-careers.php)
   - Career opportunities
   - Link to CTA training credentials
   - Career pathways (support worker → senior carer, etc.)
   - Team culture/values
   - Application form
   - Contact: recruitment@continuitycareservices.co.uk

6. **For Professionals/Commissioners** (page-commissioning.php) [MARKET GAP]
   - Dedicated section for Local Authorities & NHS commissioners
   - Complex care/CHC funding expertise showcase
   - Case management capabilities
   - Capacity information
   - Contracting process
   - Contact: office@continuitycareservices.co.uk
   - Link to CQC registration & report

7. **FAQs** (page-faqs.php)
   - Searchable FAQ section
   - Organized by category
   - Downloadable resources:
     - Service guides
     - CHC funding guide
     - Care assessment checklist

8. **Blog/Resources** (archive.php)
   - Care guidance content (CHC funding explainers, assessment guides, complex care info)
   - Position as thought leadership

9. **Contact** (page-contact.php)
   - Multiple contact methods:
     - Phone: 01622 809881
     - Email: [Extract primary email from current site]
     - Care/commissioning/general → office@; careers → recruitment@continuitycareservices.co.uk
     - Contact form (multiple types)
     - WhatsApp [if available]
   - Operating hours clearly stated
   - Geographic map (Maidstone office, Kent coverage)
   - 24/7 on-call information

---

## CRITICAL BRAND DETAILS (TO EXTRACT FROM LIVE SITE)

**ACTION**: Pull from https://www.continuitycareservices.co.uk
- [ ] Primary brand color (hex value)
- [ ] Secondary brand color (hex value)
- [ ] Accent color (hex value)
- [ ] Logo files (.svg, .png)
- [ ] Font family & weights (inspect current site CSS)
- [ ] All current photography (team, care scenarios, office)
- [ ] Current exact contact emails (beyond 01622 809881)
- [ ] Social media links (verify if CCS has separate Facebook/Instagram or uses CTA's)
- [ ] CQC report PDF URL
- [ ] Homecare.co.uk profile link
- [ ] Google reviews link
- [ ] Trustpilot profile link

---

## TECHNICAL REQUIREMENTS

### Global Find & Replace Operations
- Text domain: `cta-theme` → `ccs-theme`
- PHP function prefix: `cta_` → `ccs_`
- CSS class prefix: `.cta-` → `.ccs-`
- Constants: `CTA_THEME_*` → `CCS_THEME_*`
- PHP namespace: `namespace CTA\` → `namespace CCS\`
- JavaScript objects: `ctaData` → `ccsData`, `window.CTA` → `window.CCS`
- File names: `cta-*.php`, `cta-*.js`, `cta-*.css` → `ccs-*.*`

### Theme Metadata Updates (style.css)
```
Theme Name: Continuity of Care Services
Theme URI: https://www.continuitycareservices.co.uk
Author: Continuity of Care Services Limited
Author URI: https://www.continuitycareservices.co.uk
Description: Professional domiciliary care provider theme for CQC-regulated home care services in Maidstone & Kent.
Version: 1.0.0
```

### WordPress Constants to Configure
```php
define('CCS_PHONE', '01622 809881');
define('CCS_PHONE_LINK', 'tel:01622809881');
define('CCS_ADDRESS', 'The Maidstone Studios, New Cut Road, Vinters Park, Maidstone, Kent, ME14 5NZ');
define('CCS_OFFICE_HOURS', 'Monday-Friday 9am-5pm');
define('CCS_ONCALL', '24/7 emergency support available');
define('CCS_CQC_LOCATION_ID', '1-2624556588');
define('CCS_CQC_RATING', 'GOOD');
define('CCS_COVERAGE_AREAS', 'Maidstone, Medway, Kent');

// Email
define('CCS_EMAIL_OFFICE', 'office@continuitycareservices.co.uk');
define('CCS_EMAIL_RECRUITMENT', 'recruitment@continuitycareservices.co.uk');

// Social
define('CCS_SOCIAL_LINKEDIN', 'https://uk.linkedin.com/company/continuitycareservices');
define('CCS_SOCIAL_FACEBOOK', '[Extract from current site if exists]');
define('CCS_SOCIAL_INSTAGRAM', '[Extract from current site if exists]');

// CTA Integration
define('CTA_WEBSITE', 'https://www.continuitytrainingacademy.co.uk');
define('CTA_PHONE', '+44 1622 587343');
define('CTA_EMAIL', 'enquiries@continuitytrainingacademy.co.uk');
```

### Form Submission Routing
**Update inc/email-templates.php**: Care assessment, commissioning, general → office@; career applications → recruitment@continuitycareservices.co.uk. Use form type in subject line.

### CQC Integration
- [ ] Create CQC profile widget (show rating, link to report)
- [ ] Embed CQC report PDF download
- [ ] CQC rating badge on homepage
- [ ] CQC location link in footer

---

## DESIGN & UX REQUIREMENTS

### Homepage Structure
1. **Hero** - CQC badge, phone, tagline, CTAs (assessment + call)
2. **Trust Signals** - CQC rating, Homecare.co.uk 4.9/5, phone, hours
3. **Services** - 9 care service cards with icons
4. **About Approach** - Text + image (preserve current copy)
5. **Meet the Team** - Photos with names (Victoria Louise Walker featured)
6. **CTA for Assessment** - Form or callback
7. **Testimonial** - Verified client review (paraplegic father)
8. **FAQ** - Top 5 questions
9. **Careers** - "Join our team" teaser
10. **Footer** - Contact, links, trust badges

### Navigation
- **Primary Nav**: Services | About | For Professionals | Careers | Blog | Contact
- **Secondary Nav**: For Families | For Commissioners | For Professionals
- **Sticky Header**: Phone number always visible

### Visual Standards
- **Real Photography**: Extract from current site (team, care, office)
- **No Stock Photos**: Only real CCS team/clients
- **Color System**: Extracted from current site (honor existing brand)
- **Typography**: Extracted from current site
- **Spacing**: Generous white space, not cramped
- **Mobile-First**: Optimize for mobile (high care-seeking demographic)

---

## CONTENT SPECIFICATIONS

### Service Pages - Required Information
Each of 9 service types needs:
- Service name
- Service description (family-friendly)
- Who it's for (age groups, conditions, scenarios)
- What's included (specific, benefit-led)
- How it works (process)
- Real example or case study (anonymized)
- Clinical details (where applicable)
- Cost/booking note: "Book assessment to discuss"
- Related services
- CTA: "Request Assessment"

### Copywriting Standards
- Direct address: "You want reassurance" not "Families want"
- Acknowledge emotions: "This decision can feel daunting"
- Benefit-led: "Stay independent at home" not "We provide personal care"
- Active voice: "Let's have a calm conversation"
- Specific not generic: "Your carer will help with medication, meals, and mobility"
- No jargon without explanation

### Forms - Standard Structure
All care inquiry forms should collect:
- Name
- Email
- Phone
- Care type needed (dropdown)
- Care needed for (self/family member)
- Care start timeline (urgent/within week/within month)
- Message
- Consent checkbox
- Auto-reply confirmation

---

## CRITICAL "DO NOT" LIST

- ❌ Do NOT use Wix, Visible, or template builder platforms
- ❌ Do NOT add fake contact information
- ❌ Do NOT create fabricated testimonials or reviews
- ❌ Do NOT hide CQC rating (show GOOD rating prominently)
- ❌ Do NOT use generic stock care imagery
- ❌ Do NOT create commissioning/LA section without proper info
- ❌ Do NOT mix training academy content into CCS (keep separate)
- ❌ Do NOT use outdated/dated design
- ❌ Do NOT create overwhelming nested navigation
- ❌ Do NOT use corporate jargon ("person-centred" without substance)
- ❌ Do NOT claim certifications we don't have
- ❌ Do NOT break user journey with external links
- ❌ Do NOT use technical errors or broken links
- ❌ Do NOT make slow load times acceptable
- ❌ Do NOT forget to link CTA training credentials prominently

---

## DELIVERABLES

### Phase 1: Technical Foundation (Week 1-2)
- [ ] Git repository setup with CCS theme
- [ ] Global find/replace operations complete
- [ ] Theme metadata updated
- [ ] WordPress constants configured
- [ ] Post types created (care_service)
- [ ] Taxonomies created (service_category, care_condition, coverage_area)
- [ ] All old course-related code removed/archived
- [ ] Composer autoloader regenerated
- [ ] Theme activates without errors

### Phase 2: Template Framework (Week 2-3)
- [ ] Homepage template updated (structure, no final copy yet)
- [ ] Services archive template created
- [ ] Single service template created
- [ ] Contact form system refactored
- [ ] Email routing configured
- [ ] Navigation restructured
- [ ] Header/footer components updated
- [ ] All styles still intact and working

### Phase 3: Content & Forms (Week 3-4)
- [ ] All 9 service pages created with content
- [ ] All forms implemented (care assessment, commissioning, careers, etc.)
- [ ] CQC integration complete
- [ ] Testimonials/reviews integrated
- [ ] FAQ system working
- [ ] Email templates updated
- [ ] Auto-replies configured

### Phase 4: Optimization & Launch (Week 4+)
- [ ] Mobile responsiveness tested
- [ ] Performance optimization
- [ ] SEO optimization
- [ ] 404 error handling
- [ ] Analytics setup
- [ ] Security audit
- [ ] Staging deployment test
- [ ] Live deployment ready

---

## NEXT STEP FOR YOU

Use this document as your foundation. When creating specific tasks:
1. **Reference verified information only** - Use this document as source of truth
2. **Extract current site data** - Pull branding from live site when ready
3. **Preserve existing messaging** - Keep the core copy that works
4. **Don't invent details** - If something isn't verified, flag it for user
5. **Note gaps** - Identify where we need user input (logo files, team photos, etc.)

**Questions to ask user when starting implementation**:
- ~~Separate email addresses~~ (office@ + recruitment@ for careers – confirmed)
- Social media profiles for CCS (separate from CTA)?
- Team member names and photos to feature?
- Specific complex care conditions to highlight in detail?
- Current logo files and brand guidelines available?
- Preferred color system from current site?
