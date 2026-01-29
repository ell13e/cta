# Developer Guide

**Quick reference for working with the CTA WordPress theme codebase.**

---

## How Forms Work

### Architecture Overview

Forms follow a **Controller → Service → Repository** pattern:

```
Frontend (JavaScript)
    ↓ AJAX Request
Legacy Handler (Facade) → Controller → FormValidator (Service)
    ↓                                    ↓
    └────────────────────────────────────┘
                    ↓
        FormSubmissionRepository
                    ↓
            WordPress Database
```

### Form Handler Flow

1. **Frontend** submits form via AJAX to WordPress action (e.g., `cta_contact_form`)
2. **Legacy Handler** (`inc/ajax-handlers.php`) acts as facade:
   - Checks if controller class exists
   - Instantiates controller and calls `handle()`
   - Falls back to legacy code if controller unavailable
3. **Controller** (`src/Controllers/`) processes request:
   - Validates nonce
   - Validates anti-bot (honeypot + timing)
   - Sanitizes inputs
   - Validates fields using `FormValidator` service
   - Saves submission via `FormSubmissionRepository`
   - Sends email
   - Handles newsletter subscription (if consent given)
   - Returns JSON response

### Available Form Controllers

- `ContactFormController` - General contact form
- `GroupBookingController` - Group training bookings
- `CourseBookingController` - Individual course bookings
- `NewsletterSignupController` - Newsletter subscriptions
- `CallbackRequestController` - Callback requests

### Adding a New Form Controller

1. **Create controller class:**
   ```php
   // src/Controllers/MyFormController.php
   namespace CTA\Controllers;
   
   use CTA\Services\FormValidator;
   use CTA\Repositories\FormSubmissionRepository;
   
   class MyFormController {
       private $validator;
       private $repository;
       
       public function __construct(?FormValidator $validator = null, ?FormSubmissionRepository $repository = null) {
           $this->validator = $validator ?? new FormValidator();
           $this->repository = $repository ?? new FormSubmissionRepository();
       }
       
       public function handle(): void {
           // Nonce verification
           // Anti-bot validation
           // Sanitize inputs
           // Validate using $this->validator
           // Save via $this->repository->create()
           // Send email
           // Return JSON response
       }
   }
   ```

2. **Update legacy handler:**
   ```php
   // inc/ajax-handlers.php
   function cta_handle_my_form() {
       if (class_exists('\\CTA\\Controllers\\MyFormController')) {
           $controller = new \CTA\Controllers\MyFormController();
           $controller->handle();
           return;
       }
       // Legacy fallback code
   }
   add_action('wp_ajax_cta_my_form', 'cta_handle_my_form');
   add_action('wp_ajax_nopriv_cta_my_form', 'cta_handle_my_form');
   ```

3. **Write unit test:**
   ```php
   // tests/Unit/Controllers/MyFormControllerTest.php
   class MyFormControllerTest extends TestCase {
       public function test_it_can_be_instantiated() { }
       public function test_it_has_handle_method() { }
   }
   ```

### Validation Patterns

Use `FormValidator` service for all validation:

```php
// Name validation (required)
$name_validation = $this->validator->validateName($name);
if (!$name_validation['valid']) {
    $errors['name'] = $name_validation['error'];
}

// Email validation (required)
$email_validation = $this->validator->validateEmail($email, true);
if (!$email_validation['valid']) {
    $errors['email'] = $email_validation['error'];
}

// Email validation (optional)
$email_validation = $this->validator->validateEmail($email, false);

// Phone validation
$phone_validation = $this->validator->validateUkPhone($phone);
if (!$phone_validation['valid']) {
    $errors['phone'] = $phone_validation['error'];
}

// Anti-bot validation
$bot_check = $this->validator->validateAntiBot('form-type');
if ($bot_check === false) {
    // Bot detected - silently accept
    wp_send_json_success(['message' => 'Thank you!']);
}
```

### Email Sending Patterns

```php
private function sendEmail(...): void {
    if (!defined('CTA_ENQUIRIES_EMAIL')) {
        return;
    }
    
    $to = CTA_ENQUIRIES_EMAIL;
    $subject = '...';
    $body = "...";
    
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];
    
    $sent = wp_mail($to, $subject, $body, $headers);
    
    // Update submission with email status
    if (!is_wp_error($saved) && $saved) {
        update_post_meta($saved, '_submission_email_sent', $sent ? 'yes' : 'no');
    }
}
```

---

## How to Run Tests

### Setup

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Run tests:**
   ```bash
   composer test
   # or
   vendor/bin/phpunit
   ```

### Test Structure

```
tests/
├── Unit/
│   ├── Controllers/        # Controller structure tests
│   ├── Services/           # Service logic tests
│   └── Repositories/       # Repository tests
└── Integration/            # Full integration tests (optional)
```

### Writing Tests

**Example: Service Test**
```php
namespace CTA\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use CTA\Services\FormValidator;

class FormValidatorTest extends TestCase {
    private FormValidator $validator;
    
    protected function setUp(): void {
        $this->validator = new FormValidator();
    }
    
    /** @test */
    public function it_validates_correct_uk_phone(): void {
        $result = $this->validator->validateUkPhone('07123 456789');
        $this->assertTrue($result['valid']);
    }
}
```

**Example: Controller Test**
```php
namespace CTA\Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use CTA\Controllers\ContactFormController;

class ContactFormControllerTest extends TestCase {
    /** @test */
    public function it_can_be_instantiated(): void {
        $controller = new ContactFormController();
        $this->assertInstanceOf(ContactFormController::class, $controller);
    }
}
```

**Note:** Full integration tests require WordPress test environment setup. Current tests focus on structure and dependency injection.

---

## Code Organization

### Directory Structure

```
cta-wp-theme/
├── src/                    # PSR-4 autoloaded classes
│   ├── Controllers/        # Form handlers
│   ├── Services/           # Business logic
│   └── Repositories/      # Database operations
├── inc/                    # Legacy procedural code
│   ├── ajax-handlers.php  # Facades for controllers
│   └── ...                # Other legacy files
├── tests/                  # PHPUnit tests
├── assets/                 # Frontend assets
├── docs/                   # Documentation
└── functions.php           # Bootstrap (loads autoloader)
```

### PSR-4 Autoloading

Classes in `src/` are automatically loaded via Composer:

```php
// src/Services/FormValidator.php
namespace CTA\Services;
class FormValidator { }

// Usage (no require needed)
use CTA\Services\FormValidator;
$validator = new FormValidator();
```

**Namespace mapping:**
- `CTA\` → `src/`
- `CTA\Controllers\` → `src/Controllers/`
- `CTA\Services\` → `src/Services/`
- `CTA\Repositories\` → `src/Repositories/`

### Naming Conventions

- **Controllers:** `*Controller.php` (e.g., `ContactFormController.php`)
- **Services:** `*Service.php` or descriptive name (e.g., `FormValidator.php`)
- **Repositories:** `*Repository.php` (e.g., `FormSubmissionRepository.php`)
- **Tests:** `*Test.php` (e.g., `FormValidatorTest.php`)

### Where to Put New Code

- **New form handler?** → `src/Controllers/`
- **New validation logic?** → `src/Services/` (or extend `FormValidator`)
- **New database operations?** → `src/Repositories/`
- **New helper function?** → `inc/` (or create service if reusable)
- **New test?** → `tests/Unit/` (mirror `src/` structure)

---

## Common Tasks

### Adding a New Form Controller

See "Adding a New Form Controller" section above.

### Adding a New Service

1. **Create service class:**
   ```php
   // src/Services/MyService.php
   namespace CTA\Services;
   
   class MyService {
       public function doSomething(): void {
           // Business logic
       }
   }
   ```

2. **Use in controller:**
   ```php
   use CTA\Services\MyService;
   
   class MyController {
       private $service;
       
       public function __construct(?MyService $service = null) {
           $this->service = $service ?? new MyService();
       }
   }
   ```

3. **Write test:**
   ```php
   // tests/Unit/Services/MyServiceTest.php
   class MyServiceTest extends TestCase {
       public function test_it_does_something() { }
   }
   ```

### Adding a New Repository

1. **Create repository class:**
   ```php
   // src/Repositories/MyRepository.php
   namespace CTA\Repositories;
   
   class MyRepository {
       public function create(array $data): int {
           // Database operations
       }
       
       public function findById(int $id): ?array {
           // Retrieve from database
       }
   }
   ```

2. **Use in controller:**
   ```php
   use CTA\Repositories\MyRepository;
   
   class MyController {
       private $repository;
       
       public function __construct(?MyRepository $repository = null) {
           $this->repository = $repository ?? new MyRepository();
       }
   }
   ```

### Debugging Form Submissions

1. **Check WordPress admin:**
   - Go to Form Submissions post type
   - View submission details
   - Check email status meta fields

2. **Check error logs:**
   ```bash
   # WordPress debug log
   tail -f wp-content/debug.log
   ```

3. **Enable WP_DEBUG:**
   ```php
   // wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

4. **Check AJAX response:**
   - Open browser DevTools → Network tab
   - Submit form
   - Inspect AJAX response for errors

### Running Composer Commands

```bash
# Install dependencies
composer install

# Run tests
composer test

# Update autoloader (after adding new classes)
composer dump-autoload
```

---

## Key Principles

1. **Backward Compatibility:** All AJAX action names remain unchanged
2. **Dependency Injection:** Controllers accept services via constructor (testable)
3. **Legacy Fallback:** Legacy code remains as fallback in facades
4. **Progressive Enhancement:** New code uses modern patterns, legacy code works alongside
5. **Test What Matters:** Focus tests on business logic, not WordPress internals

---

## Quick Reference

**Form Controllers:**
- `ContactFormController`
- `GroupBookingController`
- `CourseBookingController`
- `NewsletterSignupController`
- `CallbackRequestController`

**Services:**
- `FormValidator` - Validation logic

**Repositories:**
- `FormSubmissionRepository` - Form submission database operations

**Legacy Functions (still used for fallback):**
- `cta_handle_*()` - Form handler facades
- `cta_save_form_submission()` - Delegates to repository
- `cta_validate_*()` - Legacy validation (used in fallback code)

---

## Recent Work Summary

**Last Updated:** 2025-01-27  
**Status:** Technical SEO Foundation Complete

### ✅ Completed: Technical SEO Infrastructure

**Meta Descriptions:**
- Auto-generation system with smart fallback hierarchy
- CSV bulk import tool (Tools → SEO Tools → Meta Description Import)
- Context-aware templates for courses, events, posts, pages
- Character limit enforcement (120-160 chars)
- ACF field integration (reads/writes use ACF properly)

**Title Tags:**
- Context-aware title generation
- Auto-truncation to 60 characters
- Category slug to name mapping for filtered archives
- Event date auto-appending for course events
- Homepage, courses, events, filtered archives all optimized

**Sitemap System:**
- Automatic inclusion/exclusion logic
- Past events automatically excluded
- Inactive events excluded
- Noindex pages excluded
- Dynamic priority and changefreq based on post type
- Search engine auto-pinging (Google/Bing)
- Daily cleanup cron job (3 AM local time)
- Timezone-aware scheduling
- Admin monitoring dashboard (Tools → Sitemap Monitor)

**Robots.txt:**
- AI crawler controls implemented
- Blocks training bots (GPTBot, CCBot, Google-Extended, anthropic-ai, ClaudeBot, Omgilibot, FacebookBot, Diffbot)
- Allows search bots (PerplexityBot, YouBot, ChatGPT-User)
- Sitemap URL included

**Schema Markup:**
- Course schema with validation
- Article schema with validation
- Price format validation (numeric, no £ symbol)
- Date format validation (ISO 8601)
- Required field validation (prevents invalid schema output)
- Fallback values for missing data

**Code Quality Fixes:**
- Fixed `add_query_arg()` query parameter preservation
- Fixed timezone handling in cron scheduling
- Fixed ACF field read/write consistency
- Fixed resource download modal loading (CQC Hub, Training Guides)
- UK phone validation improvements (all forms)

**Performance Optimizations:**
- Enhanced `.htaccess` caching (1-year cache for static assets)
- Cache-Control headers for better browser caching
- Gzip compression expanded (includes SVG, XML)
- Conditional script/CSS loading
- Defer attributes for non-critical scripts

### ⏳ Pending: Manual Content Work

**Course Pages (7 Priority Pages):**
Technical infrastructure complete - All automation in place. Manual content work needed for:
- Safeguarding L2
- Medication Competency L3
- Emergency First Aid at Work
- Adult Social Care Certificate L2
- Emergency Paediatric First Aid
- Medication Awareness L2
- Moving & Positioning with Hoist L3

**Note:** Meta descriptions and title tags will auto-generate if not manually set. Schema markup is auto-generated. Sitemap includes all pages automatically.

### 📊 Implementation Status by Document

- **OPTIMIZATION_REVIEW.md:** Phases 1-4 Complete, Phase 5 Optional
- **SITEMAP-IMPLEMENTATION.md:** All core features implemented
- **REFACTORING-ROADMAP.md:** Phases 1-3 Complete, Phases 4-8 On-demand
- **ARCHITECTURE.md:** Current architecture documented

---

## Project Status

**Last Updated:** January 17, 2026  
**Status:** ✅ Production Ready

### Executive Summary

The Continuity Training Academy WordPress theme is fully functional and production-ready. All core systems are implemented and documented. The theme includes complete course management, event handling, resource downloads, newsletter automation, and comprehensive compliance documentation.

### Current State

**WordPress Theme (Active):**
- ✅ Course Custom Post Types (CPT) fully implemented
- ✅ Course Event CPTs fully implemented
- ✅ All JavaScript properly enqueued
- ✅ Resource download system fully functional
- ✅ Newsletter automation integrated
- ✅ Form handling and validation working
- ✅ Theme setup and customization complete
- ✅ Admin customization and hardening applied

**Documentation & Compliance:**
- ✅ Privacy Policy framework documented
- ✅ Cookie Policy requirements defined
- ✅ Terms & Conditions checklist complete
- ✅ Policy templates ready for WordPress page editor
- ✅ All compliance sections verified

**Features Implemented:**
- ✅ Course management with descriptions and metadata
- ✅ Event management and scheduling
- ✅ Downloadable resources with email delivery
- ✅ Newsletter automation with subscriber management
- ✅ Contact form handling with email integration
- ✅ SEO schema markup
- ✅ Team member profiles with modals

### Asset & Configuration Status

**CSS Files:**
- ✅ `assets/css/main-consolidated-cleaned.css` (30,008 lines) - Main stylesheet
- ✅ `assets/css/team-new.css` (913 lines) - Team page styles
- ✅ All CSS properly enqueued in WordPress

**JavaScript Files:**
- ✅ 20+ JavaScript files properly enqueued
- ✅ Course management scripts loaded
- ✅ Event handling scripts functional
- ✅ Homepage animations working

**Data Files:**
- ✅ `data/courses-database.json` - Course inventory
- ✅ `data/scheduled-courses.json` - Course schedule
- ✅ `data/site-settings.json` - Configuration
- ✅ `data/team-members.json` - Team profiles
- ✅ `data/news-articles.json` - Blog content

**Configuration:**
- ✅ `functions.php` - Theme setup, hardening, and enqueues
- ✅ `theme.json` - WordPress theme settings
- ✅ `inc/` folder - 25+ feature modules
- ✅ `page-templates/` - 11+ page templates

---

## CSS Reference: Legal Pages

### CSS Classes Used by Templates

**Both Privacy and Cookie Policy Pages Use:**
- `.page-hero-section.page-hero-section-simple` - Hero section
- `.legal-content-section` - Main content wrapper
- `.legal-content` - Content container (max-width: 800px)
- `.legal-cta-section` - CTA section at bottom

### CSS Definitions

**Location:** `assets/css/main.css`

**1. Legal Content Styles (Lines 39459-39540)**
```css
.legal-content-section {
  padding: var(--section-padding-mobile) 0;
}

.legal-content {
  max-width: 800px;
  margin: 0 auto;
  font-size: var(--font-size-body);
  line-height: var(--line-height-relaxed);
  color: var(--brown-medium);
}

.legal-content h2 {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--brown-dark);
  margin-top: 48px;
  margin-bottom: 24px;
  line-height: 1.3;
}

.legal-content h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--brown-dark);
  margin-top: 32px;
  margin-bottom: 16px;
  line-height: 1.4;
}

.legal-content p {
  margin-bottom: 20px;
  max-width: var(--max-width-body); /* Line 622 - Optimal reading width */
}
```

**2. Hero Section Styles (Line 39596)**
```css
.page-hero-section-simple {
  background: linear-gradient(180deg, #fefdfb 0%, #ffffff 100%);
  padding: 48px 0 40px;
}
```

**3. Legal CTA Section (Lines 39543-39593)**
```css
.legal-cta-section {
  padding: var(--section-padding-mobile) 0;
  background: var(--cream-light);
}

.legal-cta-content {
  max-width: 600px;
  margin: 0 auto;
  text-align: center;
}
```

### Unused CSS Classes

**Note:** The `.cta-privacy-*` classes (lines 35105-39556) are NOT used by current templates but exist in CSS. They may be from an old design system and could potentially conflict if accidentally applied.

### Template Structure

Both templates use:
```html
<section class="legal-content-section">
  <div class="container">
    <div class="legal-content">
      <!-- Page content -->
    </div>
  </div>
</section>
```

The `.container` class should provide consistent width constraints.

### Troubleshooting Legal Page Styling

**If pages look different, check:**
1. **HTML structure** - Both should have identical structure
2. **Body classes** - WordPress may add different classes like `page-privacy-policy` vs `page-cookie-policy`
3. **Template assignment** - Ensure both pages use their respective templates in WordPress admin
4. **Browser cache** - Clear cache to ensure latest CSS is loaded
5. **CSS specificity** - Check for more specific selectors targeting one page

**Files to Check:**
- `page-templates/page-privacy.php` - Privacy template
- `page-templates/page-cookies.php` - Cookie template  
- `assets/css/main.css` - Main stylesheet
- `header.php` - Check body classes

---

**Need help?** Check `docs/ARCHITECTURE.md` for detailed architecture documentation.
