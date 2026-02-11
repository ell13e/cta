# CTA WordPress Theme - Architecture

## Overview

The Continuity of Care Services WordPress theme is transitioning from a procedural monolithic architecture to a modern, class-based PSR-4 structure with proper separation of concerns.

## Current Architecture (Hybrid)

The theme currently operates in a **hybrid mode** where:
- New code uses PSR-4 autoloaded classes (`src/` directory)
- Legacy code remains functional in `inc/` directory
- **Backward compatibility is maintained** - all AJAX actions and WordPress hooks remain unchanged

## Directory Structure

```
ccs-wp-theme/
├── functions.php           # Bootstrap file (loads autoloader + legacy includes)
├── src/                    # New PSR-4 class-based code
│   ├── Controllers/        # AJAX/Request handlers
│   ├── Services/           # Business logic services
│   ├── Repositories/       # Database operations
│   └── Views/              # Template rendering (planned)
├── inc/                    # Legacy procedural code (being refactored)
├── assets/
│   ├── js/                 # JavaScript (source + compiled)
│   └── css/                # Stylesheets
├── tests/                  # PHPUnit tests
│   ├── Unit/               # Unit tests for services
│   └── Integration/        # Integration tests
├── vendor/                 # Composer dependencies (gitignored)
├── node_modules/           # npm dependencies (gitignored)
├── composer.json           # PHP dependency management
├── package.json            # JS build tooling (Vite)
└── phpunit.xml             # PHPUnit configuration
```

## Design Patterns

### 1. Controller Pattern
Controllers handle HTTP requests (AJAX forms) and coordinate between services.

**Example:** `ContactFormController`
- Validates input using `FormValidator`
- Saves data using legacy functions (transitional)
- Sends emails
- Returns JSON responses

### 2. Service Pattern
Services contain reusable business logic.

**Example:** `FormValidator`
- Validates phone numbers, emails, names
- Anti-bot checks (honeypot + timing)
- No WordPress dependencies (easily testable)

### 3. Repository Pattern (Planned)
Repositories will handle all database operations.

**Planned:** `NewsletterRepository`, `FormSubmissionRepository`

### 4. Facade Pattern (Current)
Legacy procedural functions act as facades to new classes.

```php
// OLD: Procedural function (still works)
function cta_handle_contact_form() {
    // NEW: Delegates to class
    $controller = new \CTA\Controllers\ContactFormController();
    $controller->handle();
}

// WordPress hook (unchanged)
add_action('wp_ajax_cta_contact_form', 'cta_handle_contact_form');
add_action('wp_ajax_nopriv_cta_contact_form', 'cta_handle_contact_form');
```

**Result:** Frontend JavaScript continues to work without changes.

## Autoloading

### PSR-4 Autoloading (Composer)
```json
{
  "autoload": {
    "psr-4": {
      "CTA\\": "src/"
    }
  }
}
```

**Usage:**
```php
use CTA\Services\FormValidator;
use CTA\Controllers\ContactFormController;

$validator = new FormValidator();
$controller = new ContactFormController($validator);
```

### Legacy File Loading
`functions.php` still manually requires legacy files:
```php
require_once CTA_THEME_DIR . '/inc/ajax-handlers.php';
require_once CTA_THEME_DIR . '/inc/theme-setup.php';
// ... etc
```

## Testing Strategy

### Unit Tests
Test individual classes in isolation.

**Example:** `FormValidatorTest`
- No WordPress dependencies
- Fast execution
- Run with `composer test`

### Integration Tests (Planned)
Test WordPress integration points.
- Require WordPress test suite
- Test database operations
- Test hooks and filters

## Migration Strategy

### Phase 1: Infrastructure (Current)
✅ Create `composer.json`, `package.json`, `phpunit.xml`  
✅ Set up directory structure (`src/`, `tests/`)  
✅ Configure autoloader  
✅ Create first service (`FormValidator`)  
✅ Create first controller (`ContactFormController`)  
✅ Maintain backward compatibility layer  

### Phase 2: Extract Core Services (Next)
- Create `EmailService` (centralized email sending)
- Create `FormSubmissionRepository` (database operations)
- Extract remaining form controllers:
  - `NewsletterSignupController`
  - `GroupBookingController`
  - `CourseBookingController`
  - `CallbackRequestController`

### Phase 3: Newsletter System Refactor
- Split `inc/newsletter-subscribers.php` (8,171 lines) into:
  - `NewsletterRepository`
  - `TagManager`
  - `CampaignService`
  - `EmailQueueService`
  - View templates in `src/Views/`

### Phase 4: Cleanup
- Remove legacy procedural code
- Update all remaining `inc/` files
- Full PHPUnit test coverage
- Documentation complete

## Frontend Compatibility

### JavaScript AJAX Calls
**No changes required.** All AJAX action names remain the same:

```javascript
// This continues to work unchanged
fetch(ajaxUrl, {
    method: 'POST',
    body: formData,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
});
```

### WordPress Hooks
All `add_action()` and `add_filter()` calls remain unchanged:
- `wp_ajax_cta_contact_form`
- `wp_ajax_nopriv_cta_contact_form`
- etc.

## Build Tooling

### PHP (Composer)
```bash
composer install          # Install dependencies
composer test             # Run PHPUnit tests
composer phpstan          # Static analysis
composer cs               # Check coding standards
```

### JavaScript (Vite)
```bash
npm install               # Install dependencies
npm run dev               # Development server
npm run build             # Production build
npm run watch             # Watch mode
```

## Benefits of New Architecture

1. **Testability** - Classes can be unit tested in isolation
2. **Maintainability** - Small, focused classes vs 8K+ line files
3. **Reusability** - Services can be shared across controllers
4. **Type Safety** - PHPStan catches errors before runtime
5. **Standards** - PSR-4 autoloading, PHPUnit testing
6. **Zero Breakage** - Frontend continues working during refactor

## Contributing

When adding new features:
1. Create classes in `src/` directory
2. Write unit tests in `tests/Unit/`
3. Use dependency injection
4. Maintain backward compatibility if touching legacy code
5. Run tests before committing: `composer test`

## Further Reading

- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
