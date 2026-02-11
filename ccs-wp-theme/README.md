# CCS WordPress Theme Refactoring

## Quick Start

This theme is being modernized from procedural to object-oriented architecture. Here's how to get started:

### Prerequisites
- PHP 7.4+
- Composer
- Node.js 18+
- WordPress 6.1+

### Installation

```bash
# Navigate to theme directory
cd wp-content/themes/ccs-wp-theme/

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Run tests
composer test
```

### What's New?

We're refactoring from this:
```php
// OLD: 40+ manual requires in functions.php
require_once CTA_THEME_DIR . '/inc/ajax-handlers.php';
require_once CTA_THEME_DIR . '/inc/newsletter-subscribers.php';
// ... 38 more files

// OLD: 1,909-line monolithic ajax-handlers.php
function cta_handle_contact_form() {
    // 300 lines of code...
}
```

To this:
```php
// NEW: PSR-4 autoloaded classes
use CTA\Controllers\ContactFormController;
use CTA\Services\FormValidator;

$validator = new FormValidator();
$controller = new ContactFormController($validator);
$controller->handle();
```

**Don't worry:** The old code still works! All WordPress hooks and AJAX endpoints are unchanged.

### Key Files

| File | Purpose |
|------|---------|
| [`composer.json`](composer.json) | PHP dependencies & autoloading |
| [`package.json`](package.json) | JavaScript build tooling |
| [`phpunit.xml`](phpunit.xml) | Test configuration |
| [`src/`](src/) | New PSR-4 classes |
| [`tests/`](tests/) | PHPUnit tests |
| [`inc/`](inc/) | Legacy code (being refactored) |
| [`.env.example`](.env.example) | Environment variables template |

### Development Workflow

```bash
# Run tests
composer test

# Check code quality
composer phpstan

# Fix code style
composer cs-fix

# Build JavaScript (when ready)
npm run dev      # Development
npm run build    # Production
```

### Directory Structure

```
ccs-wp-theme/
├── src/
│   ├── Controllers/         # AJAX request handlers
│   ├── Services/            # Business logic (testable)
│   └── Repositories/        # Database operations (planned)
├── tests/
│   ├── Unit/                # Fast, isolated tests
│   └── Integration/         # WordPress integration tests
├── inc/                     # Legacy code (backward compatibility)
├── docs/
│   ├── ARCHITECTURE.md      # System design
│   └── REFACTORING-ROADMAP.md  # Migration plan
└── vendor/                  # Composer dependencies (gitignored)
```

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/Unit/FormValidatorTest.php

# Run with coverage (requires xdebug)
./vendor/bin/phpunit --coverage-html coverage/
```

### Making Changes

#### Adding a New Feature
1. Create class in `src/`
2. Write tests in `tests/Unit/`
3. Use dependency injection
4. Run tests: `composer test`
5. Commit

#### Refactoring Existing Code
1. Extract logic to new class
2. Keep old function as wrapper
3. Maintain WordPress hooks
4. Test frontend still works
5. Write tests for new class
6. Commit

### Example: Contact Form Flow

```
Frontend JS
    ↓
wp_ajax_cta_contact_form (hook)
    ↓
cta_handle_contact_form() (wrapper function)
    ↓
ContactFormController::handle() (new class)
    ├── FormValidator::validateName()
    ├── FormValidator::validateEmail()
    ├── FormValidator::validateUkPhone()
    ├── cta_save_form_submission() (legacy)
    └── sendEmail() (method)
```

**Result:** Frontend unchanged, backend modernized.

### FAQ

**Q: Will this break the website?**  
A: No. We maintain backward compatibility. Old and new code coexist.

**Q: Do I need to run `composer install`?**  
A: Yes, if you're developing. The new classes won't load otherwise. But WordPress will fallback to legacy code if composer isn't installed.

**Q: Can I still edit `inc/` files?**  
A: Yes, they still work. But prefer creating new classes in `src/` for new features.

**Q: How do I know if autoloader is working?**  
A: Check if `/vendor/autoload.php` exists. Or check PHP error logs.

**Q: What about the JavaScript files?**  
A: Many only exist as `.min.js` files. We'll recreate sources in Phase 7. For now, leave them as-is.

### Troubleshooting

#### "Class not found" errors
```bash
# Regenerate autoloader
composer dump-autoload
```

#### Tests failing
```bash
# Clear PHPUnit cache
rm -rf .phpunit.cache/

# Run with verbose output
./vendor/bin/phpunit --verbose
```

#### WordPress functions undefined in tests
Tests mock WordPress functions. See `tests/bootstrap.php`.

### Contributing

1. Read [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
2. Check [`docs/REFACTORING-ROADMAP.md`](docs/REFACTORING-ROADMAP.md)
3. Write tests for new code
4. Follow WordPress coding standards
5. Run `composer test` before committing

### Resources

- [PHP Namespaces](https://www.php.net/manual/en/language.namespaces.php)
- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Composer Documentation](https://getcomposer.org/doc/)

### Current Status

**Phase 1:** ✅ Complete  
- Infrastructure set up
- First controller extracted
- Tests written
- Documentation complete

**Next:** Phase 2 - Extract remaining form controllers

See [`docs/REFACTORING-ROADMAP.md`](docs/REFACTORING-ROADMAP.md) for full plan.
