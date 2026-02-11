# CCS Theme Transformation - Technical Implementation Roadmap

## QUICK START CHECKLIST FOR CURSOR

This document is your step-by-step technical guide. Use this WITH the other two documents (ccs-cursor-prompt.md and ccs-visual-ux-specs.md).

---

## PHASE 1: FOUNDATION SETUP (Days 1-3)

### 1.1 Repository & Git Setup

```bash
# Clone theme
git clone https://github.com/ell13e/FINALCTAIHOPE.git ccs-theme
cd ccs-theme

# Remove old remote, add new
git remote remove origin
git remote add origin [your-new-ccs-repo-url]

# Create development branch
git checkout -b develop
git branch -b feature/cta-to-ccs-transformation
```

### 1.2 Global Text Domain Replacement

**DO THIS CAREFULLY - Use VS Code Find & Replace with Regex**

| Find | Replace | Regex | Files |
|------|---------|-------|-------|
| `cta-theme` | `ccs-theme` | No | `style.css`, `functions.php`, all PHP files |
| `cta_` | `ccs_` | Yes: `cta_(?!\s)` | All `*.php` files |
| `.cta-` | `.ccs-` | Yes: `\.cta-` | All `*.css`, `*.scss` files |
| `CTA_THEME_` | `CCS_THEME_` | No | `functions.php`, `/inc/` files |
| `namespace CTA` | `namespace CCS` | No | All `/inc/` PHP files |
| `use CTA` | `use CCS` | No | All PHP files with namespaces |
| `ctaData` | `ccsData` | No | `functions.php` (script localization) |
| `window.CTA` | `window.CCS` | No | All `*.js` files |
| `cta-` (file prefix) | `ccs-` | No | Rename actual files |

**Verification Steps**:
```bash
# Search for remaining CTA references
grep -r "cta_" --include="*.php" .
grep -r "\.cta-" --include="*.css" .
grep -r "cta-theme" --include="*.php" .

# Should return: ZERO results
```

### 1.3 File Structure Organization

**Create directory for old/archived code** (keep for reference):
```
/ccs-theme/
  /cta-legacy/  ← Archive old course-related files here
    archive-course.php
    single-course.php
    archive-course_event.php
    single-course_event.php
    inc/eventbrite-integration.php
    assets/js/courses.js
    assets/js/course-data-manager.js
    assets/js/data/courses-data.js
```

### 1.4 Update Theme Metadata (style.css)

```css
/*
Theme Name: Continuity of Care Services
Theme URI: https://www.continuitycareservices.co.uk
Author: Continuity of Care Services Limited
Author URI: https://www.continuitycareservices.co.uk
Description: Professional domiciliary care provider theme. CQC-regulated, local Kent expertise, 24/7 support for complex, disability, and respite care. Integrated with Continuity of Care Services.
Version: 1.0.0
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ccs-theme
Domain Path: /languages

Continuity of Care Services: "Your Team, Your Time, Your Life"
Maidstone-based, Kent-wide home care provider for families requiring domiciliary, complex, disability, respite, and palliative care support. CQC Good-rated.
*/
```

### 1.5 Update functions.php Constants

**Top of functions.php**:
```php
<?php
/**
 * Continuity of Care Services Theme
 * 
 * @package ccs-theme
 * @version 1.0.0
 */

// Define constants first (before anything else)
if ( ! defined( 'CCS_THEME_VERSION' ) ) {
    define( 'CCS_THEME_VERSION', '1.0.0' );
}

if ( ! defined( 'CCS_THEME_DIR' ) ) {
    define( 'CCS_THEME_DIR', get_template_directory() );
}

if ( ! defined( 'CCS_THEME_URI' ) ) {
    define( 'CCS_THEME_URI', get_template_directory_uri() );
}

// Contact & Business Information
define( 'CCS_PHONE', '01622 809881' );
define( 'CCS_PHONE_LINK', 'tel:01622809881' );
define( 'CCS_ADDRESS_LINE1', 'The Maidstone Studios' );
define( 'CCS_ADDRESS_LINE2', 'New Cut Road, Vinters Park' );
define( 'CCS_ADDRESS_CITY', 'Maidstone' );
define( 'CCS_ADDRESS_COUNTY', 'Kent' );
define( 'CCS_ADDRESS_POSTCODE', 'ME14 5NZ' );
define( 'CCS_FULL_ADDRESS', CCS_ADDRESS_LINE1 . ', ' . CCS_ADDRESS_LINE2 . ', ' . CCS_ADDRESS_CITY . ', ' . CCS_ADDRESS_COUNTY . ', ' . CCS_ADDRESS_POSTCODE );

// Business Hours
define( 'CCS_OFFICE_HOURS_START', '9am' );
define( 'CCS_OFFICE_HOURS_END', '5pm' );
define( 'CCS_OFFICE_HOURS_DAYS', 'Monday-Friday' );
define( 'CCS_ONCALL_STATUS', '24/7 emergency support available' );

// Email
define( 'CCS_EMAIL_OFFICE', 'office@continuitycareservices.co.uk' );
define( 'CCS_EMAIL_RECRUITMENT', 'recruitment@continuitycareservices.co.uk' );

// CQC Information
define( 'CCS_CQC_LOCATION_ID', '1-2624556588' );
define( 'CCS_CQC_RATING', 'Good' );
define( 'CCS_CQC_REPORT_URL', 'https://www.cqc.org.uk/location/1-2624556588' );
define( 'CCS_CQC_REPORT_PDF_URL', 'https://www.cqc.org.uk/location/1-2624556588' ); // Update with actual PDF URL

// Company Information
define( 'CCS_COMPANY_NUMBER', '09440482' );
define( 'CCS_REGISTERED_MANAGER', 'Mrs Victoria Louise Walker' );
define( 'CCS_REGISTERED_SINCE', '2015' );

// Geographic Coverage
define( 'CCS_PRIMARY_AREA', 'Maidstone' );
define( 'CCS_COVERAGE_AREAS', 'Maidstone, Medway, Kent' );

// Social Media (UPDATE AFTER VERIFYING)
define( 'CCS_SOCIAL_LINKEDIN', 'https://uk.linkedin.com/company/continuitycareservices' );
define( 'CCS_SOCIAL_FACEBOOK', '[TO BE CONFIRMED]' );
define( 'CCS_SOCIAL_INSTAGRAM', '[TO BE CONFIRMED]' );

// Related Organization: Continuity of Care Services
define( 'CTA_WEBSITE', 'https://www.continuitytrainingacademy.co.uk' );
define( 'CTA_PHONE', '+44 1622 587343' );
define( 'CTA_EMAIL', 'enquiries@continuitytrainingacademy.co.uk' );

// Homecare Reviews
define( 'CCS_HOMECARE_RATING', '4.9' );
define( 'CCS_HOMECARE_URL', '[FIND AND UPDATE WITH ACTUAL URL]' );

// Registrations
define( 'CCS_UKHCA_MEMBER', true );
define( 'CCS_REGISTERED_PROVIDER', true );

// Rest of your functions.php continues below...
```

### 1.6 Verify PHP Composer/Namespaces

```bash
# Regenerate autoloader after namespace changes
composer dump-autoload

# Test theme activation
# 1. Activate theme in WordPress admin
# 2. Check console for PHP errors: wp debug.log should be empty
# 3. Frontend should load without fatal errors
```

---

## PHASE 2: POST TYPE & TAXONOMY CREATION (Days 3-4)

### 2.1 Create/Update Post Types (inc/post-types.php)

```php
<?php
/**
 * Custom Post Type Definitions for CCS
 * 
 * @package ccs-theme
 */

namespace CCS;

class PostTypeManager {
    public function __construct() {
        add_action( 'init', [ $this, 'register_post_types' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
    }

    public function register_post_types() {
        // Care Services Post Type (replaces courses)
        register_post_type( 'care_service', [
            'labels' => [
                'name'               => 'Care Services',
                'singular_name'      => 'Care Service',
                'add_new'            => 'Add New Care Service',
                'add_new_item'       => 'Add New Care Service',
                'edit_item'          => 'Edit Care Service',
                'new_item'           => 'New Care Service',
                'view_item'          => 'View Care Service',
                'view_items'         => 'View Care Services',
                'search_items'       => 'Search Care Services',
                'not_found'          => 'No care services found',
                'not_found_in_trash' => 'No care services found in trash',
            ],
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_rest'          => true,
            'has_archive'           => true,
            'rewrite'               => [ 'slug' => 'services' ],
            'menu_icon'             => 'dashicons-heart',
            'supports'              => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'custom-fields',
            ],
            'menu_position'         => 5,
            'description'           => 'Care services offered by CCS',
        ] );

        // Keep standard post type (for blog/news)
        // Already built-in, just ensure it's enabled
    }

    public function register_taxonomies() {
        // Service Categories
        register_taxonomy( 'service_category', 'care_service', [
            'labels' => [
                'name'              => 'Service Categories',
                'singular_name'     => 'Service Category',
                'search_items'      => 'Search Service Categories',
                'all_items'         => 'All Service Categories',
                'parent_item'       => 'Parent Service Category',
                'parent_item_colon' => 'Parent Service Category:',
                'edit_item'         => 'Edit Service Category',
                'update_item'       => 'Update Service Category',
                'add_new_item'      => 'Add New Service Category',
                'new_item_name'     => 'New Service Category Name',
                'menu_name'         => 'Service Categories',
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'service-category' ],
            'show_admin_column' => true,
        ] );

        // Care Conditions (for complex care, dementia, etc.)
        register_taxonomy( 'care_condition', 'care_service', [
            'labels' => [
                'name'              => 'Care Conditions',
                'singular_name'     => 'Care Condition',
                'search_items'      => 'Search Care Conditions',
                'all_items'         => 'All Care Conditions',
                'edit_item'         => 'Edit Care Condition',
                'update_item'       => 'Update Care Condition',
                'add_new_item'      => 'Add New Care Condition',
                'new_item_name'     => 'New Care Condition Name',
                'menu_name'         => 'Care Conditions',
            ],
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'condition' ],
            'show_admin_column' => true,
        ] );

        // Coverage Areas (Maidstone, Medway, Kent regions)
        register_taxonomy( 'coverage_area', 'care_service', [
            'labels' => [
                'name'              => 'Coverage Areas',
                'singular_name'     => 'Coverage Area',
                'search_items'      => 'Search Coverage Areas',
                'all_items'         => 'All Coverage Areas',
                'parent_item'       => 'Parent Coverage Area',
                'edit_item'         => 'Edit Coverage Area',
                'update_item'       => 'Update Coverage Area',
                'add_new_item'      => 'Add New Coverage Area',
                'new_item_name'     => 'New Coverage Area Name',
                'menu_name'         => 'Coverage Areas',
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'area' ],
            'show_admin_column' => true,
        ] );
    }
}

// Instantiate
new PostTypeManager();
```

### 2.2 Create Service Data Seeding Function

```php
/**
 * Seed initial care service categories
 * Run once via WordPress CLI or admin page
 */
function ccs_seed_initial_categories() {
    $categories = [
        [
            'name'        => 'Personal Care',
            'slug'        => 'personal-care',
            'description' => 'Assistance with daily living activities, independence support',
        ],
        [
            'name'        => 'Complex Care',
            'slug'        => 'complex-care',
            'description' => 'Specialist clinical care for complex medical needs',
        ],
        [
            'name'        => 'Respite Care',
            'slug'        => 'respite-care',
            'description' => 'Short-term relief care for families',
        ],
        [
            'name'        => 'Dementia Care',
            'slug'        => 'dementia-care',
            'description' => 'Specialized dementia support and memory care',
        ],
        [
            'name'        => 'Physical Disabilities',
            'slug'        => 'physical-disabilities',
            'description' => 'Support for physical disability and mobility needs',
        ],
        [
            'name'        => 'Learning Disabilities',
            'slug'        => 'learning-disabilities',
            'description' => 'Specialized support for learning disabilities',
        ],
        [
            'name'        => 'Mental Health Support',
            'slug'        => 'mental-health',
            'description' => 'Mental health crisis and ongoing support',
        ],
        [
            'name'        => 'Palliative Care',
            'slug'        => 'palliative-care',
            'description' => 'End-of-life comfort and dignity care',
        ],
        [
            'name'        => 'Children\'s Services',
            'slug'        => 'childrens-services',
            'description' => 'Specialist pediatric disability and complex needs care',
        ],
    ];

    foreach ( $categories as $cat ) {
        if ( ! term_exists( $cat['slug'], 'service_category' ) ) {
            wp_insert_term(
                $cat['name'],
                'service_category',
                [
                    'slug'        => $cat['slug'],
                    'description' => $cat['description'],
                ]
            );
        }
    }
}

// Run via: wp eval 'ccs_seed_initial_categories();'
```

---

## PHASE 3: FORM SYSTEM OVERHAUL (Days 4-5)

### 3.1 Update Form Submission Types (inc/form-submissions-admin.php)

```php
function ccs_get_form_types() {
    return [
        'care-assessment-request'  => 'Care Assessment Request',
        'commissioning-enquiry'    => 'Commissioning Enquiry (LA/Professional)',
        '24hour-care-enquiry'      => '24-Hour Care Enquiry',
        'respite-care-enquiry'     => 'Respite Care Enquiry',
        'service-enquiry'          => 'General Service Enquiry',
        'career-application'       => 'Career Application',
        'existing-client-support'  => 'Existing Client Support',
        'callback-request'         => 'Callback Request',
        'newsletter'               => 'Newsletter Signup',
    ];
}

// CPT for form submissions (storage)
function ccs_register_form_submission_post_type() {
    register_post_type( 'form_submission', [
        'labels' => [
            'name'          => 'Form Submissions',
            'singular_name' => 'Form Submission',
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'supports'     => [ 'title', 'editor', 'custom-fields' ],
        'menu_icon'    => 'dashicons-email',
        'menu_position' => 50,
    ] );

    register_taxonomy( 'submission_type', 'form_submission', [
        'labels'       => [
            'name'  => 'Submission Types',
            'singular_name' => 'Submission Type',
        ],
        'show_ui'      => true,
        'hierarchical' => true,
    ] );
}
add_action( 'init', 'ccs_register_form_submission_post_type' );
```

### 3.2 Create Form Handler Functions (inc/ajax-handlers.php)

**Remove course-related handlers, add care-related handlers:**

```php
<?php
/**
 * AJAX Form Handlers for CCS
 * 
 * @package ccs-theme
 */

namespace CCS\AJAX;

class FormHandlers {
    public function __construct() {
        // Care assessment form
        add_action( 'wp_ajax_nopriv_ccs_care_assessment_request', [ $this, 'handle_care_assessment_request' ] );
        add_action( 'wp_ajax_ccs_care_assessment_request', [ $this, 'handle_care_assessment_request' ] );

        // Commissioning form
        add_action( 'wp_ajax_nopriv_ccs_commissioning_enquiry', [ $this, 'handle_commissioning_enquiry' ] );
        add_action( 'wp_ajax_ccs_commissioning_enquiry', [ $this, 'handle_commissioning_enquiry' ] );

        // Callback request form
        add_action( 'wp_ajax_nopriv_ccs_callback_request', [ $this, 'handle_callback_request' ] );
        add_action( 'wp_ajax_ccs_callback_request', [ $this, 'handle_callback_request' ] );

        // Career application
        add_action( 'wp_ajax_nopriv_ccs_career_application', [ $this, 'handle_career_application' ] );
        add_action( 'wp_ajax_ccs_career_application', [ $this, 'handle_career_application' ] );
    }

    /**
     * Handle Care Assessment Request
     */
    public function handle_care_assessment_request() {
        // Verify nonce
        check_ajax_referer( 'ccs_nonce', 'nonce' );

        // Collect form data
        $form_data = [
            'name'             => sanitize_text_field( $_POST['name'] ?? '' ),
            'email'            => sanitize_email( $_POST['email'] ?? '' ),
            'phone'            => sanitize_text_field( $_POST['phone'] ?? '' ),
            'care_type'        => sanitize_text_field( $_POST['care_type'] ?? '' ),
            'care_needed_for'  => sanitize_text_field( $_POST['care_needed_for'] ?? '' ), // self/family
            'care_start'       => sanitize_text_field( $_POST['care_start'] ?? '' ), // urgent/week/month
            'message'          => sanitize_textarea_field( $_POST['message'] ?? '' ),
            'consent'          => sanitize_text_field( $_POST['consent'] ?? '' ),
        ];

        // Validate
        if ( empty( $form_data['name'] ) || empty( $form_data['email'] ) || empty( $form_data['phone'] ) ) {
            wp_send_json_error( [
                'message' => 'Please complete all required fields.',
            ] );
        }

        // Save submission
        $submission_id = ccs_save_form_submission( $form_data, 'care-assessment-request' );

        // Send email notifications
        $this->send_care_assessment_emails( $form_data, $submission_id );

        // Auto-reply to user
        wp_send_json_success( [
            'message' => 'Thank you for your inquiry. Our care team will contact you within 24 hours.',
        ] );
    }

    /**
     * Send care assessment emails
     */
    private function send_care_assessment_emails( $form_data, $submission_id ) {
        $to = CCS_EMAIL_OFFICE;
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        // Internal notification (to care team)
        $subject = 'New Care Assessment Request - ' . $form_data['name'];
        $message = $this->get_email_template( 'care-assessment-internal', $form_data );
        wp_mail( $to, $subject, $message, $headers );

        // Auto-reply to user
        $subject_reply = 'We Received Your Care Assessment Request';
        $message_reply = $this->get_email_template( 'care-assessment-autoreply', $form_data );
        wp_mail( $form_data['email'], $subject_reply, $message_reply, $headers );
    }

    /**
     * Handle Commissioning Enquiry (LA/Professional)
     */
    public function handle_commissioning_enquiry() {
        check_ajax_referer( 'ccs_nonce', 'nonce' );

        $form_data = [
            'organization'   => sanitize_text_field( $_POST['organization'] ?? '' ),
            'contact_name'   => sanitize_text_field( $_POST['contact_name'] ?? '' ),
            'title'          => sanitize_text_field( $_POST['title'] ?? '' ),
            'email'          => sanitize_email( $_POST['email'] ?? '' ),
            'phone'          => sanitize_text_field( $_POST['phone'] ?? '' ),
            'inquiry_type'   => sanitize_text_field( $_POST['inquiry_type'] ?? '' ),
            'message'        => sanitize_textarea_field( $_POST['message'] ?? '' ),
        ];

        if ( empty( $form_data['contact_name'] ) || empty( $form_data['email'] ) ) {
            wp_send_json_error( [ 'message' => 'Please complete required fields.' ] );
        }

        $submission_id = ccs_save_form_submission( $form_data, 'commissioning-enquiry' );

        // Send to commissioning email
        $to = CCS_EMAIL_OFFICE;
        $subject = 'Professional Commissioning Inquiry - ' . $form_data['organization'];
        $message = $this->get_email_template( 'commissioning-internal', $form_data );
        wp_mail( $to, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );

        wp_send_json_success( [
            'message' => 'Thank you. Our commissioning team will contact you within 2 business days.',
        ] );
    }

    /**
     * Handle Callback Request
     */
    public function handle_callback_request() {
        check_ajax_referer( 'ccs_nonce', 'nonce' );

        $form_data = [
            'name'   => sanitize_text_field( $_POST['name'] ?? '' ),
            'phone'  => sanitize_text_field( $_POST['phone'] ?? '' ),
            'email'  => sanitize_email( $_POST['email'] ?? '' ),
            'best_time' => sanitize_text_field( $_POST['best_time'] ?? '' ),
        ];

        if ( empty( $form_data['phone'] ) ) {
            wp_send_json_error( [ 'message' => 'Phone number is required.' ] );
        }

        $submission_id = ccs_save_form_submission( $form_data, 'callback-request' );

        // Send to general care email
        wp_mail(
            CCS_EMAIL_OFFICE,
            'Callback Request - ' . $form_data['name'],
            $this->get_email_template( 'callback-internal', $form_data ),
            [ 'Content-Type: text/html; charset=UTF-8' ]
        );

        wp_send_json_success( [
            'message' => 'We\'ll call you shortly.',
        ] );
    }

    /**
     * Handle Career Application
     */
    public function handle_career_application() {
        check_ajax_referer( 'ccs_nonce', 'nonce' );

        $form_data = [
            'full_name'   => sanitize_text_field( $_POST['full_name'] ?? '' ),
            'email'       => sanitize_email( $_POST['email'] ?? '' ),
            'phone'       => sanitize_text_field( $_POST['phone'] ?? '' ),
            'position'    => sanitize_text_field( $_POST['position'] ?? '' ),
            'experience'  => sanitize_textarea_field( $_POST['experience'] ?? '' ),
        ];

        if ( empty( $form_data['full_name'] ) || empty( $form_data['email'] ) ) {
            wp_send_json_error( [ 'message' => 'Please complete all fields.' ] );
        }

        // Handle CV file upload
        if ( ! empty( $_FILES['cv'] ) ) {
            $upload = wp_handle_upload( $_FILES['cv'], [ 'test_form' => false ] );
            if ( $upload && ! isset( $upload['error'] ) ) {
                $form_data['cv_url'] = $upload['url'];
            }
        }

        $submission_id = ccs_save_form_submission( $form_data, 'career-application' );

        // Send to recruitment
        wp_mail(
            CCS_EMAIL_RECRUITMENT,
            'Career Application - ' . $form_data['position'],
            $this->get_email_template( 'career-internal', $form_data ),
            [ 'Content-Type: text/html; charset=UTF-8' ]
        );

        wp_send_json_success( [
            'message' => 'Thank you for applying! We\'ll review your application and be in touch soon.',
        ] );
    }

    /**
     * Get email template
     */
    private function get_email_template( $template_name, $data = [] ) {
        $template_file = CCS_THEME_DIR . '/template-parts/emails/' . $template_name . '.php';
        
        if ( ! file_exists( $template_file ) ) {
            return 'Thank you for contacting Continuity of Care Services.';
        }

        ob_start();
        include $template_file;
        return ob_get_clean();
    }
}

// Instantiate
new FormHandlers();

/**
 * Save form submission as CPT
 */
function ccs_save_form_submission( $data, $form_type ) {
    $post_id = wp_insert_post( [
        'post_type'   => 'form_submission',
        'post_title'  => $data['name'] ?? 'Form Submission',
        'post_content' => json_encode( $data, JSON_PRETTY_PRINT ),
        'post_status' => 'publish',
    ] );

    // Set taxonomy
    wp_set_post_terms( $post_id, $form_type, 'submission_type' );

    // Save individual fields as meta
    foreach ( $data as $key => $value ) {
        update_post_meta( $post_id, '_' . $key, $value );
    }

    return $post_id;
}
```

---

## PHASE 4: EMAIL TEMPLATES (Days 5-6)

### 4.1 Create Email Template System

**Directory structure**:
```
/template-parts/
  /emails/
    care-assessment-internal.php
    care-assessment-autoreply.php
    commissioning-internal.php
    callback-internal.php
    career-internal.php
    base-template.php
```

**Example: care-assessment-internal.php**:
```php
<?php
/**
 * Email template: Care Assessment Internal Notification
 * Sent to: office@continuitycareservices.co.uk
 */
?>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .header { background: [CCS_PRIMARY_COLOR]; color: white; padding: 20px; }
        .content { padding: 20px; }
        .field { margin: 15px 0; }
        .label { font-weight: bold; color: #666; font-size: 12px; }
        .value { margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>New Care Assessment Request</h1>
    </div>
    <div class="content">
        <div class="field">
            <div class="label">Name</div>
            <div class="value"><?php echo esc_html( $data['name'] ); ?></div>
        </div>
        <div class="field">
            <div class="label">Email</div>
            <div class="value"><a href="mailto:<?php echo esc_attr( $data['email'] ); ?>"><?php echo esc_html( $data['email'] ); ?></a></div>
        </div>
        <div class="field">
            <div class="label">Phone</div>
            <div class="value"><a href="tel:<?php echo esc_attr( $data['phone'] ); ?>"><?php echo esc_html( $data['phone'] ); ?></a></div>
        </div>
        <div class="field">
            <div class="label">Type of Care</div>
            <div class="value"><?php echo esc_html( $data['care_type'] ); ?></div>
        </div>
        <div class="field">
            <div class="label">Care Needed For</div>
            <div class="value"><?php echo esc_html( ucfirst( $data['care_needed_for'] ) ); ?></div>
        </div>
        <div class="field">
            <div class="label">When Care Needed</div>
            <div class="value"><?php echo esc_html( ucfirst( $data['care_start'] ) ); ?></div>
        </div>
        <div class="field">
            <div class="label">Message</div>
            <div class="value"><?php echo nl2br( esc_html( $data['message'] ) ); ?></div>
        </div>
        <hr>
        <p style="font-size: 12px; color: #999;">
            <strong>Action Required:</strong> Contact the client at <?php echo esc_html( $data['phone'] ); ?> or <?php echo esc_html( $data['email'] ); ?> to arrange assessment.
        </p>
    </div>
</body>
</html>
```

---

## PHASE 5: TEMPLATE FILES (Days 6-7)

### 5.1 Update Homepage (front-page.php)

**Replace CTA course/event sections with CCS care sections**

```php
<?php
/**
 * Homepage Template for CCS
 * 
 * @package ccs-theme
 */

get_header();

// Get contact info from constants
$phone = CCS_PHONE;
$phone_link = CCS_PHONE_LINK;
$address = CCS_FULL_ADDRESS;
$cqc_rating = CCS_CQC_RATING;
?>

<main id="primary" class="site-main">
    
    <!-- Hero Section -->
    <section class="ccs-hero">
        <div class="ccs-hero__content">
            <h1>Your Team, Your Time, Your Life</h1>
            <p class="ccs-hero__subtitle">
                Compassionate home care in Maidstone & Kent. CQC-regulated, locally based, always here when you need us.
            </p>
            
            <!-- Trust Signals -->
            <div class="ccs-hero__trust-signals">
                <div class="ccs-cqc-badge">
                    <img src="<?php echo esc_url( CCS_THEME_URI . '/assets/images/cqc-badge.svg' ); ?>" alt="CQC <?php echo esc_attr( $cqc_rating ); ?> Rating">
                    <span>CQC <?php echo esc_html( $cqc_rating ); ?></span>
                </div>
                <div class="ccs-accreditation">
                    <i class="fas fa-star"></i>
                    <span>4.9/5 on Homecare.co.uk</span>
                </div>
            </div>
            
            <!-- CTAs -->
            <div class="ccs-hero__cta">
                <button class="ccs-btn ccs-btn--primary ccs-btn--large" data-form="care-assessment">
                    <i class="fas fa-calendar-check"></i>
                    Book Free Assessment
                </button>
                <a href="<?php echo esc_attr( $phone_link ); ?>" class="ccs-btn ccs-btn--secondary ccs-btn--large">
                    <i class="fas fa-phone"></i>
                    <?php echo esc_html( $phone ); ?>
                </a>
            </div>
            
            <!-- Availability -->
            <p class="ccs-hero__availability">
                <i class="fas fa-clock"></i>
                Office: <?php echo esc_html( CCS_OFFICE_HOURS_DAYS ); ?> <?php echo esc_html( CCS_OFFICE_HOURS_START ); ?>-<?php echo esc_html( CCS_OFFICE_HOURS_END ); ?> | 
                <?php echo esc_html( CCS_ONCALL_STATUS ); ?>
            </p>
        </div>
        
        <!-- Hero Image -->
        <div class="ccs-hero__image">
            <img src="<?php echo esc_url( get_the_post_thumbnail_url( get_option( 'page_on_front' ), 'full' ) ); ?>" 
                 alt="Continuity Care Services carer supporting client at home">
        </div>
    </section><!-- /.ccs-hero -->
    
    <!-- Services Section -->
    <?php get_template_part( 'template-parts/homepage', 'services' ); ?>
    
    <!-- About Approach Section -->
    <?php get_template_part( 'template-parts/homepage', 'about-approach' ); ?>
    
    <!-- Testimonials Section -->
    <?php get_template_part( 'template-parts/homepage', 'testimonials' ); ?>
    
    <!-- FAQ Section -->
    <?php get_template_part( 'template-parts/homepage', 'faq' ); ?>
    
    <!-- CTA Section -->
    <?php get_template_part( 'template-parts/homepage', 'cta-assessment' ); ?>
    
</main>

<?php get_footer(); ?>
```

### 5.2 Create Service Template Files

**archive-care_service.php** (services listing page):
```php
<?php
get_header();
?>
<main id="primary" class="site-main ccs-services-archive">
    <div class="ccs-container">
        <header class="page-header">
            <h1><?php post_type_archive_title(); ?></h1>
            <p>Compassionate care tailored to your needs. Browse our full range of services.</p>
        </header>
        
        <!-- Filters -->
        <div class="ccs-service-filters">
            <!-- Service category filter -->
            <!-- Care condition filter -->
            <!-- Coverage area filter -->
        </div>
        
        <!-- Services Grid -->
        <div class="ccs-services-grid">
            <?php
            if ( have_posts() ) {
                while ( have_posts() ) {
                    the_post();
                    get_template_part( 'template-parts/content', 'care-service' );
                }
            }
            ?>
        </div>
    </div>
</main>
<?php
get_footer();
?>
```

**single-care_service.php** (individual service page):
```php
<?php
get_header();
?>
<main id="primary" class="site-main ccs-single-service">
    <article <?php post_class( 'ccs-service-article' ); ?>>
        
        <!-- Service Hero -->
        <header class="entry-header">
            <h1><?php the_title(); ?></h1>
            <?php if ( has_excerpt() ) : ?>
                <p class="service-intro"><?php the_excerpt(); ?></p>
            <?php endif; ?>
        </header>
        
        <!-- Featured Image -->
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="entry-thumbnail">
                <?php the_post_thumbnail( 'large' ); ?>
            </div>
        <?php endif; ?>
        
        <!-- Content -->
        <div class="entry-content">
            <?php the_content(); ?>
        </div>
        
        <!-- Related Services -->
        <?php
        $related_query = new WP_Query( [
            'post_type' => 'care_service',
            'exclude'   => get_the_ID(),
            'orderby'   => 'rand',
            'posts_per_page' => 3,
        ] );
        
        if ( $related_query->have_posts() ) {
            echo '<section class="related-services"><h3>Related Services</h3><div class="related-grid">';
            while ( $related_query->have_posts() ) {
                $related_query->the_post();
                get_template_part( 'template-parts/content', 'care-service-small' );
            }
            echo '</div></section>';
        }
        wp_reset_postdata();
        ?>
        
    </article>
    
    <!-- CTA Section -->
    <section class="service-cta">
        <h3>Ready to Learn More?</h3>
        <p>Book a free assessment to discuss your care needs</p>
        <button class="ccs-btn ccs-btn--primary" data-form="care-assessment">
            Book Assessment
        </button>
    </section>
</main>
<?php
get_footer();
?>
```

---

## PHASE 6: CSS UPDATES (Days 7-8)

### 6.1 Update Global CSS Variables

**main.css or custom-properties.css**:
```css
:root {
    /* Extract from current CCS site CSS */
    --ccs-primary: [PRIMARY_COLOR];
    --ccs-secondary: [SECONDARY_COLOR];
    --ccs-accent: [ACCENT_COLOR];
    --ccs-gray-light: #f5f5f5;
    --ccs-gray-dark: #333333;
    --ccs-text: #1a1a1a;
    --ccs-text-light: #666666;
    --ccs-white: #ffffff;
    --ccs-success: #10b981;
    --ccs-error: #ef4444;
    --ccs-warning: #f59e0b;
    
    /* Typography */
    --ccs-font-primary: [FROM_CURRENT_SITE];
    --ccs-font-secondary: [FROM_CURRENT_SITE];
    
    /* Spacing */
    --ccs-space-xs: 0.5rem;
    --ccs-space-sm: 1rem;
    --ccs-space-md: 1.5rem;
    --ccs-space-lg: 2rem;
    --ccs-space-xl: 3rem;
}
```

### 6.2 Update CTA Classes

**Replace all `.cta-` with `.ccs-`**:
```css
/* Old */
.cta-btn { }
.cta-hero { }

/* New */
.ccs-btn { }
.ccs-hero { }
```

---

## PHASE 7: TESTING & DEPLOYMENT (Days 8-10)

### 7.1 Testing Checklist

**Functionality**:
- [ ] All forms submit correctly
- [ ] Emails route to correct addresses
- [ ] Services display in archive
- [ ] Service filtering works
- [ ] Search function works
- [ ] No 404 errors
- [ ] All links functional

**Appearance**:
- [ ] Homepage displays correctly
- [ ] Mobile responsive
- [ ] Images load properly
- [ ] Colors match brand
- [ ] Typography correct
- [ ] Spacing consistent

**Performance**:
- [ ] Lighthouse score > 90
- [ ] Page load < 3 seconds
- [ ] Images optimized
- [ ] No console errors

**SEO**:
- [ ] Meta titles set
- [ ] Meta descriptions set
- [ ] H1 tags present
- [ ] Structured data added

### 7.2 Deployment Steps

```bash
# Commit all changes
git add .
git commit -m "Complete CCS theme transformation"

# Push to repository
git push origin develop

# Create release branch
git checkout -b release/1.0.0

# Push to staging for final testing
git push origin release/1.0.0

# After approval, merge to main/master
git checkout main
git merge release/1.0.0
git push origin main
```

---

## PRIORITY ORDER IF RUSHED

If you need to prioritize:

1. **CRITICAL** (do first):
   - Theme metadata update
   - Global find/replace
   - Post types creation
   - Form submission system
   - Homepage template

2. **HIGH** (do second):
   - Service templates
   - Email system
   - Navigation updates

3. **MEDIUM** (do third):
   - CSS refinements
   - Additional templates

4. **NICE-TO-HAVE** (do if time):
   - Animations
   - Advanced filtering
   - Blog system

---

## COMMON ISSUES & FIXES

| Issue | Solution |
|-------|----------|
| "Function does not exist" | Check namespace is correct, composer dump-autoload |
| Forms not sending | Verify email constants are set correctly, check wp_mail works |
| Styles not loading | Clear WordPress cache, check CSS file names match |
| 404 on services | Re-register post types, flush rewrite rules: update_option( 'rewrite_rules', '' ) |
| Images missing | Check CCS_THEME_URI constant path is correct |

---

**Done with this document? Move to ccs-cursor-prompt.md and ccs-visual-ux-specs.md for full context.**
