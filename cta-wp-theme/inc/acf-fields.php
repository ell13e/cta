<?php
/**
 * ACF Field Definitions
 * 
 * Works with the FREE version of Advanced Custom Fields.
 * No PRO features (Repeaters, Options Pages) are used.
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Check if ACF is active
 */
function cta_acf_is_active() {
    return class_exists('ACF');
}

/**
 * Show admin notice if ACF is not installed
 */
function cta_acf_admin_notice() {
    if (!cta_acf_is_active()) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><strong>Continuity Training Academy Theme:</strong> For the best experience, install the free <a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">Advanced Custom Fields</a> plugin. The theme will work without it, but you'll have more control over course and page content with ACF installed.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'cta_acf_admin_notice');

/**
 * Register ACF field groups programmatically
 * These all work with the FREE version of ACF
 */
function cta_register_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // =========================================
    // COURSE FIELDS - Main Details
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_course_details',
        'title' => 'Course Details',
        'fields' => [
            [
                'key' => 'field_course_level',
                'label' => 'Level',
                'name' => 'course_level',
                'type' => 'text',
                'instructions' => 'e.g., Level 2, Level 3. Displays in hero meta section with icon.',
            ],
            [
                'key' => 'field_course_duration',
                'label' => 'Duration',
                'name' => 'course_duration',
                'type' => 'text',
                'instructions' => 'e.g., 1 Day, 2 Days, 3 Hours. Displays in hero meta section with clock icon.',
            ],
            [
                'key' => 'field_course_hours',
                'label' => 'Hours',
                'name' => 'course_hours',
                'type' => 'number',
                'instructions' => 'Total training hours (e.g., 6, 7.5). Displays in parentheses next to duration in hero meta section.',
                'step' => 0.5,
            ],
            [
                'key' => 'field_course_trainer',
                'label' => 'Trainer',
                'name' => 'course_trainer',
                'type' => 'text',
                'instructions' => 'Primary trainer name. Used for course organization and filtering.',
            ],
            [
                'key' => 'field_course_price',
                'label' => 'Price',
                'name' => 'course_price',
                'type' => 'number',
                'instructions' => 'Price in GBP (e.g., 150.00). Displays in hero meta section and booking sidebar.',
                'min' => 0,
                'step' => 0.01,
            ],
            [
                'key' => 'field_course_max_delegates',
                'label' => 'Maximum Delegates',
                'name' => 'course_max_delegates',
                'type' => 'number',
                'instructions' => 'Maximum number of delegates for this course (e.g., 12). Used for event capacity planning.',
                'min' => 1,
                'step' => 1,
            ],
            [
                'key' => 'field_course_is_mandatory',
                'label' => 'Mandatory Training',
                'name' => 'course_is_mandatory',
                'type' => 'true_false',
                'instructions' => 'Enable if this is mandatory CQC training. Displays a mandatory badge on course cards and detail pages.',
                'default_value' => 0,
                'ui' => 1,
            ],
            [
                'key' => 'field_course_mandatory_note',
                'label' => 'Mandatory Training Message',
                'name' => 'course_mandatory_note',
                'type' => 'textarea',
                'instructions' => 'Custom message to display in the mandatory badge (optional)',
                'rows' => 3,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_course_is_mandatory',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'left',
        'instruction_placement' => 'field',
    ]);

    // Course Content (for accordions)
    acf_add_local_field_group([
        'key' => 'group_course_content',
        'title' => 'Course Content',
        'fields' => [
            [
                'key' => 'field_course_description',
                'label' => 'Full Description',
                'name' => 'course_description',
                'type' => 'textarea',
                'instructions' => 'Course description. Displays as "Course Description" in the Course Overview section (expanded, not in accordion).',
                'rows' => 6,
                'placeholder' => 'Enter the full course description...',
            ],
            [
                'key' => 'field_generate_course_description',
                'label' => '',
                'name' => 'generate_course_description',
                'type' => 'message',
                'message' => '<button type="button" id="cta-generate-course-description" class="button button-small" style="margin-top: 6px;">✨ Generate with AI</button> <span id="cta-generate-description-status" style="margin-left: 10px; font-size: 12px;"></span>',
            ],
            [
                'key' => 'field_course_suitable_for',
                'label' => 'Who Should Attend',
                'name' => 'course_suitable_for',
                'type' => 'textarea',
                'instructions' => 'Target audience. This section is now VISIBLE on the page (not in an accordion) to help users quickly determine if the course is right for them.',
                'rows' => 4,
            ],
            [
                'key' => 'field_course_prerequisites',
                'label' => 'Requirements / Prerequisites',
                'name' => 'course_prerequisites',
                'type' => 'textarea',
                'instructions' => 'Prerequisites or requirements. This section is now VISIBLE on the page (not in an accordion). For minimal requirements (e.g., "No prerequisites required"), it will display in a highlighted summary box.',
                'rows' => 3,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course',
                ],
            ],
        ],
        'menu_order' => 3,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // Course Accreditation & Certification
    acf_add_local_field_group([
        'key' => 'group_course_accreditation',
        'title' => 'Accreditation & Certification',
        'fields' => [
            [
                'key' => 'field_course_accreditation',
                'label' => 'Accreditation',
                'name' => 'course_accreditation',
                'type' => 'text',
                'instructions' => 'e.g., "CPD Certified", "Skills for Care endorsed". Displays in hero meta section with award icon. Use "None" if not applicable.',
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_course_certificate',
                'label' => 'Certificate Info',
                'name' => 'course_certificate',
                'type' => 'text',
                'instructions' => 'e.g., "Certificate valid for 3 years". Displays in "What\'s Included" section on course detail page.',
                'wrapper' => ['width' => '50%'],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course',
                ],
            ],
        ],
        'menu_order' => 2,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // COURSE FIELDS - FAQs
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_course_faqs',
        'title' => 'Course FAQs',
        'fields' => [
            [
                'key' => 'field_generate_course_faqs',
                'label' => '',
                'name' => 'generate_course_faqs',
                'type' => 'message',
                'message' => '<button type="button" id="cta-generate-course-faqs" class="button button-small" style="margin-bottom: 12px;">✨ Generate FAQs with AI</button> <span id="cta-generate-faqs-status" style="margin-left: 10px; font-size: 12px;"></span>',
            ],
            [
                'key' => 'field_course_faqs',
                'label' => 'Frequently Asked Questions',
                'name' => 'course_faqs',
                'type' => 'repeater',
                'instructions' => 'Add course-specific FAQs. These will appear on the course detail page between the course content and related courses sections.',
                'layout' => 'block',
                'button_label' => 'Add FAQ',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_course_faq_question',
                        'label' => 'Question',
                        'name' => 'question',
                        'type' => 'text',
                        'required' => 1,
                        'placeholder' => 'e.g., What is included in this course?',
                    ],
                    [
                        'key' => 'field_course_faq_answer',
                        'label' => 'Answer',
                        'name' => 'answer',
                        'type' => 'wysiwyg',
                        'instructions' => 'Write a clear, helpful answer. Use formatting tools to add emphasis, lists, or links.',
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'delay' => 0,
                        'required' => 1,
                    ],
                ],
            ],
            [
                'key' => 'field_course_selected_reviews',
                'label' => 'Select Testimonials',
                'name' => 'course_selected_reviews',
                'type' => 'select',
                'instructions' => 'Select up to 3 testimonials to feature on this course page. Use the visual picker below to browse and select reviews.',
                'choices' => [],
                'default_value' => [],
                'allow_null' => 1,
                'multiple' => 1,
                'ui' => 1,
                'ajax' => 0,
                'return_format' => 'value',
                'wrapper' => [
                    'width' => '',
                    'class' => 'cta-review-picker-field',
                    'id' => 'cta-review-picker-wrapper',
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course',
                ],
            ],
        ],
        'menu_order' => 4,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'field',
    ]);

    // =========================================
    // COURSE FIELDS - Expanded Content
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_course_expanded_content',
        'title' => 'Expanded Course Content',
        'fields' => [
            [
                'key' => 'field_course_expanded_tabs',
                'label' => '',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
            ],
            // Introduction Tab
            [
                'key' => 'field_course_intro_tab',
                'label' => 'Introduction',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_course_intro_paragraph',
                'label' => 'Opening Paragraph',
                'name' => 'course_intro_paragraph',
                'type' => 'wysiwyg',
                'instructions' => 'Engaging opening paragraph (2-3 sentences, ~30 words). This appears at the bottom of the expanded content section, after "What\'s Included". Will be automatically truncated if longer. If left empty, will auto-populate from course description.',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0,
            ],
            [
                'key' => 'field_generate_course_intro',
                'label' => '',
                'name' => 'generate_course_intro',
                'type' => 'message',
                'message' => '<button type="button" id="cta-generate-course-intro" class="button button-small" style="margin-top: 6px;">✨ Generate with AI</button> <span id="cta-generate-intro-status" style="margin-left: 10px; font-size: 12px;"></span>',
            ],
            [
                'key' => 'field_course_why_matters',
                'label' => 'Why This Matters',
                'name' => 'course_why_matters',
                'type' => 'wysiwyg',
                'instructions' => 'Callout explaining why this training is important (150-180 words). Covers CQC requirements, legal context, importance. Displays first in the expanded content section with a highlighted callout box.',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0,
            ],
            [
                'key' => 'field_generate_course_why_matters',
                'label' => '',
                'name' => 'generate_course_why_matters',
                'type' => 'message',
                'message' => '<button type="button" id="cta-generate-course-why-matters" class="button button-small" style="margin-top: 6px;">✨ Generate with AI</button> <span id="cta-generate-why-matters-status" style="margin-left: 10px; font-size: 12px;"></span>',
            ],
            // What's Covered Tab
            [
                'key' => 'field_course_covered_tab',
                'label' => 'What\'s Covered',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_course_covered_items',
                'label' => 'Key Topics',
                'name' => 'course_covered_items',
                'type' => 'repeater',
                'instructions' => 'Add topics covered in this course. Displays as "Key Topics" in a grid layout. Only the first 6 items show initially (descriptions truncated to 20 words). Add more than 6 to show a "View full curriculum" link.',
                'layout' => 'block',
                'button_label' => 'Add Topic',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_course_covered_title',
                        'label' => 'Topic Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                        'placeholder' => 'e.g., Safeguarding Principles',
                    ],
                    [
                        'key' => 'field_course_covered_description',
                        'label' => 'Description',
                        'name' => 'description',
                        'type' => 'textarea',
                        'required' => 1,
                        'rows' => 3,
                        'placeholder' => 'Brief description of what this topic covers...',
                        'instructions' => 'Will be automatically truncated to 20 words in the display.',
                    ],
                ],
            ],
            // Course Format Tab
            [
                'key' => 'field_course_format_tab',
                'label' => 'Course Format',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_course_format_details',
                'label' => 'Format Details',
                'name' => 'course_format_details',
                'type' => 'wysiwyg',
                'instructions' => 'Additional information about course format, delivery method, etc.',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0,
            ],
            // Key Features Tab
            [
                'key' => 'field_course_features_tab',
                'label' => 'Key Features',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_course_key_features',
                'label' => 'Training Highlights',
                'name' => 'course_key_features',
                'type' => 'repeater',
                'instructions' => 'Add features that make this training unique. Displays as "Training Highlights" section. Only the first 3 features show (descriptions truncated to 25 words).',
                'layout' => 'block',
                'button_label' => 'Add Feature',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_course_feature_icon',
                        'label' => 'Icon Class',
                        'name' => 'icon',
                        'type' => 'text',
                        'instructions' => 'Font Awesome class (e.g., "fas fa-users")',
                        'default_value' => 'fas fa-check-circle',
                    ],
                    [
                        'key' => 'field_course_feature_title',
                        'label' => 'Feature Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                        'placeholder' => 'e.g., Expert Trainers',
                    ],
                    [
                        'key' => 'field_course_feature_description',
                        'label' => 'Description',
                        'name' => 'description',
                        'type' => 'textarea',
                        'required' => 1,
                        'rows' => 2,
                        'placeholder' => 'Brief description...',
                        'instructions' => 'Will be automatically truncated to 25 words in the display.',
                    ],
                ],
            ],
            // After the Course Tab
            [
                'key' => 'field_course_after_tab',
                'label' => 'After the Course',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_course_benefits',
                'label' => 'What\'s Included',
                'name' => 'course_benefits',
                'type' => 'repeater',
                'instructions' => 'List of benefits/inclusions after completing the course. Displays as "What\'s Included" section. Only the first 4 items show initially. If left empty, will auto-populate from certificate/accreditation fields.',
                'layout' => 'table',
                'button_label' => 'Add Benefit',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_course_benefit_text',
                        'label' => 'Benefit',
                        'name' => 'benefit',
                        'type' => 'text',
                        'required' => 1,
                        'placeholder' => 'e.g., Digital certificate provided',
                    ],
                ],
            ],
            [
                'key' => 'field_course_after_note',
                'label' => 'Additional Note',
                'name' => 'course_after_note',
                'type' => 'wysiwyg',
                'instructions' => 'Additional note about certificates, records, etc.',
                'tabs' => 'all',
                'toolbar' => 'basic',
                'media_upload' => 0,
                'delay' => 0,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course',
                ],
            ],
        ],
        'menu_order' => 1,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'field',
    ]);
  
      // Course Learning Outcomes - NOW A TEXTAREA (one outcome per line)
    acf_add_local_field_group([
        'key' => 'group_course_outcomes',
        'title' => 'Learning Outcomes',
        'fields' => [
            [
                'key' => 'field_course_outcomes',
                'label' => 'Course Content (Learning Outcomes)',
                'name' => 'course_outcomes',
                'type' => 'textarea',
                'instructions' => 'Enter learning outcomes, one per line. Displays as "Course Content" section (expanded, not in accordion). Only the first 8 items show initially with a "Show All X Topics" button for progressive disclosure.',
                'rows' => 8,
                'placeholder' => "Understand the principles of person-centred care\nRecognise signs and symptoms of common conditions\nApply safe moving and handling techniques\nCommunicate effectively with service users",
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course',
                ],
            ],
        ],
        'menu_order' => 2,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // Course SEO Fields - User-friendly SEO section (optional overrides)
    acf_add_local_field_group([
        'key' => 'group_course_seo',
        'title' => 'SEO & Content Settings (Optional Overrides)',
        'fields' => [
            [
                'key' => 'field_course_seo_h1',
                'label' => 'Custom H1 Title',
                'name' => 'course_seo_h1',
                'type' => 'text',
                'instructions' => '',
                'placeholder' => 'e.g., Professional Care Training Course',
                'maxlength' => 100,
            ],
            [
                'key' => 'field_course_seo_meta_title',
                'label' => 'Meta Title (SEO)',
                'name' => 'course_seo_meta_title',
                'type' => 'text',
                'instructions' => '50-60 chars (optional, defaults to title)',
                'placeholder' => 'e.g., Professional Care Training Course | CPD Accredited',
                'maxlength' => 60,
            ],
            [
                'key' => 'field_course_seo_meta_description',
                'label' => 'Meta Description (SEO)',
                'name' => 'course_seo_meta_description',
                'type' => 'textarea',
                'instructions' => 'Optional: Custom description for search engines. If left blank, uses site-wide meta description template from Customizer → SEO Settings. Recommended: 150-160 characters.',
                'rows' => 3,
                'maxlength' => 160,
                'placeholder' => 'e.g., Professional CPD-accredited care training course in Maidstone, Kent. Learn essential skills for care sector professionals.',
            ],
            [
                'key' => 'field_generate_course_meta_description',
                'label' => '',
                'name' => 'generate_course_meta_description',
                'type' => 'message',
                'message' => '<button type="button" id="cta-generate-course-meta-description" class="button button-small" style="margin-top: 6px;">✨ Generate with AI</button> <span id="cta-generate-meta-description-status" style="margin-left: 10px; font-size: 12px;"></span>',
            ],
            [
                'key' => 'field_course_seo_excerpt',
                'label' => 'Page Excerpt',
                'name' => 'course_seo_excerpt',
                'type' => 'textarea',
                'instructions' => 'Optional: Short description for course cards and search results. If left blank, WordPress excerpt will be used.',
                'rows' => 4,
                'placeholder' => 'Brief overview of the course content and who it\'s for...',
            ],
            [
                'key' => 'field_course_seo_section_heading',
                'label' => 'Section Heading Text',
                'name' => 'course_seo_section_heading',
                'type' => 'text',
                'instructions' => '',
                'placeholder' => 'e.g., Course Overview, What This Course Covers',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course',
                ],
            ],
        ],
        'menu_order' => 5,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // COURSE EVENT (SCHEDULED SESSION) FIELDS
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_course_event_details',
        'title' => 'Event Details',
        'fields' => [
            [
                'key' => 'field_linked_course',
                'label' => 'Related Course',
                'name' => 'linked_course',
                'type' => 'post_object',
                'instructions' => 'Select the course this event is for',
                'required' => 1,
                'post_type' => ['course'],
                'return_format' => 'object',
                'ui' => 1,
            ],
            [
                'key' => 'field_event_date',
                'label' => 'Event Date',
                'name' => 'event_date',
                'type' => 'date_picker',
                'instructions' => 'Date of the training event',
                'required' => 1,
                'display_format' => 'd/m/Y',
                'return_format' => 'Y-m-d',
                'first_day' => 1,
            ],
            [
                'key' => 'field_start_time',
                'label' => 'Start Time',
                'name' => 'start_time',
                'type' => 'time_picker',
                'instructions' => 'Start time of the event',
                'display_format' => 'H:i',
                'return_format' => 'H:i',
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_end_time',
                'label' => 'End Time',
                'name' => 'end_time',
                'type' => 'time_picker',
                'instructions' => 'End time of the event',
                'display_format' => 'H:i',
                'return_format' => 'H:i',
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_event_location',
                'label' => 'Venue',
                'name' => 'event_location',
                'type' => 'text',
                'instructions' => 'Training venue location',
                'default_value' => 'The Maidstone Studios',
            ],
            [
                'key' => 'field_total_spaces',
                'label' => 'Total Capacity',
                'name' => 'total_spaces',
                'type' => 'number',
                'instructions' => 'Maximum number of spaces for this event',
                'min' => 1,
                'default_value' => 12,
                'required' => 1,
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_spaces_available',
                'label' => 'Spaces Available',
                'name' => 'spaces_available',
                'type' => 'number',
                'instructions' => 'Number of spaces currently available (starts equal to Total Capacity = 0 booked)',
                'min' => 0,
                'default_value' => 12,
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_event_price',
                'label' => 'Custom Price',
                'name' => 'event_price',
                'type' => 'number',
                'instructions' => '',
                'min' => 0,
                'step' => 0.01,
            ],
            [
                'key' => 'field_event_active',
                'label' => 'Event Status',
                'name' => 'event_active',
                'type' => 'true_false',
                'instructions' => 'Uncheck to hide this event from the website',
                'default_value' => 1,
                'ui' => 1,
                'ui_on_text' => 'Active',
                'ui_off_text' => 'Hidden',
                'message' => 'Active (show on website)',
            ],
            [
                'key' => 'field_event_featured',
                'label' => 'Featured Event',
                'name' => 'event_featured',
                'type' => 'true_false',
                'instructions' => '',
                'default_value' => 0,
                'ui' => 1,
                'message' => 'Feature this event in hero section',
            ],
            [
                'key' => 'field_event_image',
                'label' => 'Event Image',
                'name' => 'event_image',
                'type' => 'image',
                'instructions' => 'Custom image for this event. If not set, will use the linked course image.',
                'return_format' => 'array',
                'preview_size' => 'thumbnail',
                'library' => 'all',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course_event',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'left',
        'instruction_placement' => 'field',
    ]);

    // Course Event SEO Fields - User-friendly SEO section (optional overrides)
    acf_add_local_field_group([
        'key' => 'group_course_event_seo',
        'title' => 'SEO & Content Settings (Optional Overrides)',
        'fields' => [
            [
                'key' => 'field_event_seo_h1',
                'label' => 'Custom H1 Title',
                'name' => 'event_seo_h1',
                'type' => 'text',
                'instructions' => '',
                'placeholder' => 'e.g., Epilepsy & Emergency Medication Training',
                'maxlength' => 100,
            ],
            [
                'key' => 'field_event_seo_meta_title',
                'label' => 'Meta Title (SEO)',
                'name' => 'event_seo_meta_title',
                'type' => 'text',
                'instructions' => '50-60 chars (optional, defaults to title)',
                'placeholder' => 'e.g., Epilepsy Training Course | Book Now | Maidstone',
                'maxlength' => 60,
            ],
            [
                'key' => 'field_event_seo_meta_description',
                'label' => 'Meta Description (SEO)',
                'name' => 'event_seo_meta_description',
                'type' => 'textarea',
                'instructions' => 'Optional: Custom description for search engines. If left blank, uses site-wide meta description template from Customizer → SEO Settings. Recommended: 150-160 characters.',
                'rows' => 3,
                'maxlength' => 160,
                'placeholder' => 'e.g., Book your place on our Epilepsy & Emergency Medication training course in Maidstone. CPD-accredited, expert-led sessions.',
            ],
            [
                'key' => 'field_event_seo_excerpt',
                'label' => 'Page Excerpt',
                'name' => 'event_seo_excerpt',
                'type' => 'textarea',
                'instructions' => 'Optional: Short description for event cards and search results. If left blank, WordPress excerpt will be used.',
                'rows' => 4,
                'placeholder' => 'Brief overview of the training session...',
            ],
            [
                'key' => 'field_event_seo_section_heading',
                'label' => 'Section Heading Text',
                'name' => 'event_seo_section_heading',
                'type' => 'text',
                'instructions' => '',
                'placeholder' => 'e.g., Event Overview, Training Details',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course_event',
                ],
            ],
        ],
        'menu_order' => 1,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // HOMEPAGE FIELDS - Comprehensive editable text areas
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_homepage_content',
        'title' => 'Homepage Content',
        'fields' => [
            // Tab: Hero Section
            [
                'key' => 'field_homepage_tab_hero',
                'label' => 'Hero Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_hero_headline',
                'label' => 'Headline',
                'name' => 'hero_headline',
                'type' => 'text',
                'default_value' => 'CQC-Compliant Care Training in Kent',
                'wrapper' => ['width' => '100%'],
            ],
            [
                'key' => 'field_hero_subheadline',
                'label' => 'Subheadline',
                'name' => 'hero_subheadline',
                'type' => 'textarea',
                'default_value' => 'Expert-led accredited training courses designed to keep your team compliant, confident, and care-focused.',
                'rows' => 2,
            ],
            [
                'key' => 'field_hero_cta_text',
                'label' => 'Primary Button Text',
                'name' => 'hero_cta_text',
                'type' => 'text',
                'default_value' => 'Find My Course',
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_hero_cta_link',
                'label' => 'Primary Button Link',
                'name' => 'hero_cta_link',
                'type' => 'page_link',
                'post_type' => ['page', 'course'],
                'wrapper' => ['width' => '50%'],
            ],
            
            // Tab: Partners Section
            [
                'key' => 'field_homepage_tab_partners',
                'label' => 'Partners Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_partners_title',
                'label' => 'Partners Section Title',
                'name' => 'partners_title',
                'type' => 'text',
                'default_value' => 'Trusted by Care Providers Across the United Kingdom',
                'instructions' => 'Use {focus} to wrap text that should be highlighted (e.g., "Trusted by Care Providers Across the {focus}United Kingdom{/focus}")',
            ],
            
            // Tab: Why Us Section
            [
                'key' => 'field_homepage_tab_why_us',
                'label' => 'Why Us Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_why_us_eyebrow',
                'label' => 'Eyebrow Text',
                'name' => 'why_us_eyebrow',
                'type' => 'text',
                'default_value' => 'Why us?',
            ],
            [
                'key' => 'field_why_us_title',
                'label' => 'Section Title',
                'name' => 'why_us_title',
                'type' => 'text',
                'default_value' => 'Experience the Difference',
            ],
            [
                'key' => 'field_why_us_subtitle',
                'label' => 'Section Subtitle',
                'name' => 'why_us_subtitle',
                'type' => 'textarea',
                'default_value' => 'Discover the benefits that make our platform the first choice for teams striving for excellence.',
                'rows' => 2,
            ],
            // Card 1
            [
                'key' => 'field_why_us_card1_title',
                'label' => 'Card 1: Title',
                'name' => 'why_us_card1_title',
                'type' => 'text',
                'default_value' => 'DBS-checked trainers',
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_why_us_card1_description',
                'label' => 'Card 1: Description',
                'name' => 'why_us_card1_description',
                'type' => 'textarea',
                'default_value' => 'Trusted professionals you can safely invite into your setting with complete peace of mind',
                'rows' => 2,
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_why_us_card1_list',
                'label' => 'Card 1: List Items',
                'name' => 'why_us_card1_list',
                'type' => 'textarea',
                'default_value' => "Enhanced DBS clearance\nProfessional indemnity insurance\nRegular background updates",
                'instructions' => 'Enter one item per line',
                'rows' => 3,
            ],
            // Card 2
            [
                'key' => 'field_why_us_card2_title',
                'label' => 'Card 2: Title',
                'name' => 'why_us_card2_title',
                'type' => 'text',
                'default_value' => 'Accredited certificates',
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_why_us_card2_description',
                'label' => 'Card 2: Description',
                'name' => 'why_us_card2_description',
                'type' => 'textarea',
                'default_value' => 'No waiting for paperwork. Stay audit-ready instantly with immediate proof of competency',
                'rows' => 2,
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_why_us_card2_list',
                'label' => 'Card 2: List Items',
                'name' => 'why_us_card2_list',
                'type' => 'textarea',
                'default_value' => "Digital & physical certificates\nEmail certificate delivery\nAutomatic renewal reminders",
                'instructions' => 'Enter one item per line',
                'rows' => 3,
            ],
            // Card 3
            [
                'key' => 'field_why_us_card3_title',
                'label' => 'Card 3: Title',
                'name' => 'why_us_card3_title',
                'type' => 'text',
                'default_value' => 'Flexible delivery',
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_why_us_card3_description',
                'label' => 'Card 3: Description',
                'name' => 'why_us_card3_description',
                'type' => 'textarea',
                'default_value' => 'Train at your location or ours, whatever works for your team\'s schedule without disrupting care',
                'rows' => 2,
                'wrapper' => ['width' => '50%'],
            ],
            [
                'key' => 'field_why_us_card3_list',
                'label' => 'Card 3: List Items',
                'name' => 'why_us_card3_list',
                'type' => 'textarea',
                'default_value' => "On-site training available\nModern classroom facilities\nFlexible scheduling options",
                'instructions' => 'Enter one item per line',
                'rows' => 3,
            ],
            
            // Tab: Course Categories Section
            [
                'key' => 'field_homepage_tab_courses',
                'label' => 'Course Categories Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_courses_title',
                'label' => 'Section Title',
                'name' => 'courses_title',
                'type' => 'text',
                'default_value' => 'Care Sector Training',
            ],
            [
                'key' => 'field_courses_subtitle',
                'label' => 'Section Subtitle',
                'name' => 'courses_subtitle',
                'type' => 'text',
                'default_value' => 'Essential to specialist care skills',
            ],
            
            // Tab: Testimonials Section
            [
                'key' => 'field_homepage_tab_testimonials',
                'label' => 'Testimonials Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_testimonials_title',
                'label' => 'Section Title',
                'name' => 'testimonials_title',
                'type' => 'text',
                'default_value' => 'What Our Learners Say',
            ],
            [
                'key' => 'field_testimonials_subtitle',
                'label' => 'Section Subtitle',
                'name' => 'testimonials_subtitle',
                'type' => 'textarea',
                'default_value' => 'Real feedback from care professionals who have completed our training courses and experienced the difference in their practice.',
                'rows' => 2,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_type',
                    'operator' => '==',
                    'value' => 'front_page',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // ABOUT PAGE FIELDS - Comprehensive content editor
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_about_page',
        'title' => 'About Page Content',
        'fields' => [
            // Tab: Hero Section
            [
                'key' => 'field_about_tab_hero',
                'label' => 'Hero Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_about_hero_title',
                'label' => 'Hero Title (H1)',
                'name' => 'hero_title',
                'type' => 'text',
                'default_value' => 'About Our Care Training in Kent',
                'placeholder' => 'e.g., About Our Care Training in Kent',
            ],
            [
                'key' => 'field_about_hero_subtitle',
                'label' => 'Hero Subtitle',
                'name' => 'hero_subtitle',
                'type' => 'textarea',
                'default_value' => 'CQC-compliant, CPD-accredited care sector training in Kent since 2020',
                'rows' => 2,
            ],
            
            // Tab: Mission Section
            [
                'key' => 'field_about_tab_mission',
                'label' => 'Mission Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_about_mission_title',
                'label' => 'Mission Title (H2)',
                'name' => 'mission_title',
                'type' => 'text',
                'default_value' => 'Our Care Training Approach',
            ],
            [
                'key' => 'field_about_mission_text',
                'label' => 'Mission Content',
                'name' => 'mission_text',
                'type' => 'repeater',
                'instructions' => 'Add paragraphs for the mission section. Each paragraph will be displayed separately.',
                'layout' => 'block',
                'button_label' => 'Add Paragraph',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_about_mission_paragraph',
                        'label' => 'Paragraph',
                        'name' => 'paragraph',
                        'type' => 'wysiwyg',
                        'tabs' => 'visual',
                        'toolbar' => 'basic',
                        'media_upload' => 0,
                    ],
                ],
            ],
            [
                'key' => 'field_about_mission_image',
                'label' => 'Mission Image',
                'name' => 'mission_image',
                'type' => 'image',
                'instructions' => 'Image displayed alongside the mission content',
                'return_format' => 'url',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
            
            // Tab: Values Section
            [
                'key' => 'field_about_tab_values',
                'label' => 'Values Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_about_values_title',
                'label' => 'Values Title (H2)',
                'name' => 'values_title',
                'type' => 'text',
                'default_value' => 'Core Care Training Values',
            ],
            [
                'key' => 'field_about_values_subtitle',
                'label' => 'Values Subtitle',
                'name' => 'values_subtitle',
                'type' => 'textarea',
                'default_value' => 'These principles guide everything we do and shape the experience we provide to our learners.',
                'rows' => 2,
            ],
            [
                'key' => 'field_about_values',
                'label' => 'Values',
                'name' => 'values',
                'type' => 'repeater',
                'instructions' => 'Add value cards displayed in the values grid',
                'layout' => 'block',
                'button_label' => 'Add Value',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_about_value_icon',
                        'label' => 'Icon Class',
                        'name' => 'icon',
                        'type' => 'text',
                        'instructions' => 'Font Awesome icon class (e.g., fas fa-hands-helping)',
                        'placeholder' => 'fas fa-hands-helping',
                    ],
                    [
                        'key' => 'field_about_value_title',
                        'label' => 'Value Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_about_value_description',
                        'label' => 'Value Description',
                        'name' => 'description',
                        'type' => 'textarea',
                        'required' => 1,
                        'rows' => 3,
                    ],
                ],
            ],
            
            // Tab: Statistics
            [
                'key' => 'field_about_tab_stats',
                'label' => 'Statistics',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_about_stats',
                'label' => 'Statistics',
                'name' => 'stats',
                'type' => 'repeater',
                'instructions' => 'Add statistics displayed on the page',
                'layout' => 'table',
                'button_label' => 'Add Statistic',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_about_stat_number',
                        'label' => 'Number',
                        'name' => 'number',
                        'type' => 'text',
                        'required' => 1,
                        'placeholder' => 'e.g., 40+, 2020, 4.6/5',
                    ],
                    [
                        'key' => 'field_about_stat_label',
                        'label' => 'Label',
                        'name' => 'label',
                        'type' => 'text',
                        'required' => 1,
                        'placeholder' => 'e.g., Courses Offered',
                    ],
                ],
            ],
            
            // Tab: Team Section
            [
                'key' => 'field_about_tab_team',
                'label' => 'Team Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_about_team_title',
                'label' => 'Team Title (H2)',
                'name' => 'team_title',
                'type' => 'text',
                'default_value' => 'Expert Care Training Team',
            ],
            [
                'key' => 'field_about_team_subtitle',
                'label' => 'Team Subtitle',
                'name' => 'team_subtitle',
                'type' => 'textarea',
                'default_value' => 'Experienced professionals dedicated to your development',
                'rows' => 2,
            ],
            [
                'key' => 'field_about_team_note',
                'label' => 'Note',
                'name' => '',
                'type' => 'message',
                'message' => 'Team members are managed separately via the Team Members post type. This section will automatically display directors from that post type.',
            ],
            
            // Tab: CTA Section
            [
                'key' => 'field_about_tab_cta',
                'label' => 'Call to Action',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_about_cta_title',
                'label' => 'CTA Title',
                'name' => 'cta_title',
                'type' => 'text',
                'default_value' => 'Start Your Care Training Today',
            ],
            [
                'key' => 'field_about_cta_text',
                'label' => 'CTA Text',
                'name' => 'cta_text',
                'type' => 'textarea',
                'default_value' => 'Join hundreds of care professionals who trust us for their training needs. Get expert CQC compliance training with CPD-accredited certificates.',
                'rows' => 3,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/page-about.php',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // NEWS/BLOG POST FIELDS - Introduction (Separate Section)
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_news_article_intro',
        'title' => '📖 Article Introduction',
        'fields' => [
            [
                'key' => 'field_news_intro',
                'label' => 'Introduction / Lead Paragraph',
                'name' => 'news_intro',
                'type' => 'wysiwyg',
                'instructions' => 'Write a compelling introduction that appears at the start of your article. This will be styled prominently as the lead paragraph, separate from the main article content below.',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 0,
                'delay' => 0,
                'placeholder' => 'Write a compelling introduction that summarizes the key points of your article and hooks the reader...',
                'required' => 0,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'hide_on_screen' => '',
    ]);

    // =========================================
    // NEWS/BLOG POST FIELDS - Main Content Fields
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_news_article_content',
        'title' => '📝 Article Content',
        'fields' => [
            [
                'key' => 'field_news_sections',
                'label' => 'Article Sections',
                'name' => 'news_sections',
                'type' => 'repeater',
                'instructions' => 'Add sections to organize your article content. Each section has a title and content. This is the main body of your article, separate from the introduction above.',
                'layout' => 'block',
                'button_label' => 'Add Section',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_news_section_title',
                        'label' => 'Section Title',
                        'name' => 'section_title',
                        'type' => 'text',
                        'instructions' => 'The heading for this section (e.g., "What You Need to Know", "Key Changes")',
                        'required' => 1,
                        'placeholder' => 'Enter section title...',
                    ],
                    [
                        'key' => 'field_news_section_content',
                        'label' => 'Section Content',
                        'name' => 'section_content',
                        'type' => 'wysiwyg',
                        'instructions' => 'The main content for this section. Use H2 for main headings, H3 for subheadings. H1 is not available (post title is already H1).',
                        'required' => 1,
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'delay' => 0,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
        'menu_order' => 1,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'hide_on_screen' => '',
    ]);

    // Article Settings (Sidebar)
    acf_add_local_field_group([
        'key' => 'group_news_article',
        'title' => '⚙️ Article Settings',
        'fields' => [
            [
                'key' => 'field_news_featured',
                'label' => 'Featured Article',
                'name' => 'news_featured',
                'type' => 'true_false',
                'instructions' => 'Mark this as a featured article to highlight it on the news page',
                'ui' => 1,
                'ui_on_text' => 'Featured',
                'ui_off_text' => 'Normal',
                'default_value' => 0,
            ],
            [
                'key' => 'field_news_subtitle',
                'label' => 'Subtitle / Standfirst',
                'name' => 'news_subtitle',
                'type' => 'textarea',
                'instructions' => 'A brief summary that appears below the title (optional - excerpt will be used if empty)',
                'rows' => 2,
                'new_lines' => '',
            ],
            [
                'key' => 'field_news_author',
                'label' => 'Author Display Name',
                'name' => 'news_author',
                'type' => 'text',
                'instructions' => 'Override the author name displayed on the article (leave blank to use WordPress author)',
                'placeholder' => 'e.g., CTA Team, Jennifer Boorman',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
        'menu_order' => 1,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // GENERAL PAGE CONTENT - For all pages (not posts, not homepage, not about)
    // Comprehensive tabbed interface matching homepage style
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_page_content',
        'title' => 'Page Content',
        'fields' => [
            // Tab: Hero Section
            [
                'key' => 'field_page_tab_hero',
                'label' => 'Hero Section',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_page_hero_title',
                'label' => 'Hero Title (H1)',
                'name' => 'hero_title',
                'type' => 'text',
                'instructions' => 'The main H1 heading for the page. Leave blank to use the page title.',
                'placeholder' => 'e.g., Professional Group Training Solutions',
                'maxlength' => 100,
                'wrapper' => ['width' => '100%'],
            ],
            [
                'key' => 'field_page_hero_subtitle',
                'label' => 'Hero Subtitle / Introduction',
                'name' => 'hero_subtitle',
                'type' => 'textarea',
                'instructions' => 'A subtitle or introduction paragraph that appears below the H1 in the hero section.',
                'rows' => 3,
                'placeholder' => 'e.g., Train your entire team together. Flexible scheduling, accredited certificates...',
            ],
            
            // Tab: Section Headings & Titles
            [
                'key' => 'field_page_tab_sections',
                'label' => 'Section Headings',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_page_section_how_it_works_title',
                'label' => 'How It Works Section - Title (H2)',
                'name' => 'section_how_it_works_title',
                'type' => 'text',
                'instructions' => 'Title for the "How It Works" section (used on Group Training page)',
                'placeholder' => 'e.g., How It Works',
            ],
            [
                'key' => 'field_page_section_testimonials_title',
                'label' => 'Testimonials Section - Title (H2)',
                'name' => 'section_testimonials_title',
                'type' => 'text',
                'instructions' => 'Title for the testimonials section',
                'placeholder' => 'e.g., Trusted by Care Teams Across Kent',
            ],
            [
                'key' => 'field_page_section_benefits_title',
                'label' => 'Benefits Section - Title (H2)',
                'name' => 'section_benefits_title',
                'type' => 'text',
                'instructions' => 'Title for the benefits section',
                'placeholder' => 'e.g., Why Choose Group Training?',
            ],
            [
                'key' => 'field_page_section_benefits_subtitle',
                'label' => 'Benefits Section - Subtitle',
                'name' => 'section_benefits_subtitle',
                'type' => 'textarea',
                'instructions' => 'Subtitle text below the benefits section title',
                'rows' => 2,
                'placeholder' => 'e.g., Maximise value while maintaining quality training standards',
            ],
            [
                'key' => 'field_page_section_form_title',
                'label' => 'Form Section - Title (H2)',
                'name' => 'section_form_title',
                'type' => 'text',
                'instructions' => 'Title for the contact/booking form section',
                'placeholder' => 'e.g., Get Your Custom Training Quote in 24 Hours',
            ],
            [
                'key' => 'field_page_section_form_description',
                'label' => 'Form Section - Description',
                'name' => 'section_form_description',
                'type' => 'textarea',
                'instructions' => 'Description text above the form',
                'rows' => 2,
                'placeholder' => 'e.g., Tell us about your team and training needs...',
            ],
            [
                'key' => 'field_page_section_faq_title',
                'label' => 'FAQ Section - Title (H2)',
                'name' => 'section_faq_title',
                'type' => 'text',
                'instructions' => 'Title for the FAQ section',
                'placeholder' => 'e.g., Frequently Asked Questions',
            ],
            
            // Tab: Content Sections
            [
                'key' => 'field_page_tab_content',
                'label' => 'Content Sections',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_page_content_sections',
                'label' => 'Page Sections',
                'name' => 'page_content_sections',
                'type' => 'repeater',
                'instructions' => 'Add content sections to your page. Each section has an H2 heading and rich text content (paragraphs, lists, etc.).',
                'layout' => 'block',
                'button_label' => 'Add Content Section',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_page_section_heading',
                        'label' => 'Section Heading (H2)',
                        'name' => 'section_heading',
                        'type' => 'text',
                        'instructions' => 'The H2 heading for this section',
                        'required' => 1,
                        'placeholder' => 'e.g., How It Works, Our Services, What We Offer',
                    ],
                    [
                        'key' => 'field_page_section_list_style',
                        'label' => 'List Style',
                        'name' => 'section_list_style',
                        'type' => 'button_group',
                        'instructions' => 'Choose how lists in this section should be displayed. "Two Column Gold" creates a two-column layout with gold arrow bullets.',
                        'choices' => [
                            'default' => 'Default',
                            'two-column-gold' => 'Two Column Gold',
                        ],
                        'default_value' => 'default',
                        'allow_null' => 0,
                        'layout' => 'horizontal',
                    ],
                    [
                        'key' => 'field_page_section_content',
                        'label' => 'Section Content',
                        'name' => 'section_content',
                        'type' => 'wysiwyg',
                        'instructions' => 'The main content for this section. Use H3 for subheadings, paragraphs, lists, etc. H1 and H2 are not available here (use H2 for the section heading above, H3 for subheadings within content). If you selected "Two Column Gold" above, all &lt;ul&gt; lists in this content will automatically use that styling.',
                        'required' => 1,
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'delay' => 0,
                    ],
                ],
            ],
            
            // Tab: Testimonials
            [
                'key' => 'field_page_tab_testimonials',
                'label' => 'Testimonials',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_page_testimonials',
                'label' => 'Testimonials',
                'name' => 'testimonials',
                'type' => 'repeater',
                'instructions' => 'Add testimonials to display on the page',
                'layout' => 'block',
                'button_label' => 'Add Testimonial',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_page_testimonial_quote',
                        'label' => 'Quote',
                        'name' => 'quote',
                        'type' => 'textarea',
                        'required' => 1,
                        'rows' => 3,
                    ],
                    [
                        'key' => 'field_page_testimonial_author',
                        'label' => 'Author',
                        'name' => 'author',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_page_testimonial_icon',
                        'label' => 'Icon Class',
                        'name' => 'icon',
                        'type' => 'text',
                        'instructions' => 'Font Awesome icon class (e.g., fas fa-building, fas fa-user)',
                        'placeholder' => 'fas fa-user',
                    ],
                ],
            ],
            
            // Tab: FAQs
            [
                'key' => 'field_page_tab_faq',
                'label' => 'FAQs',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_page_faqs',
                'label' => 'Frequently Asked Questions',
                'name' => 'faqs',
                'type' => 'repeater',
                'instructions' => 'Add FAQ items. Categories: general, pricing, scheduling, policies',
                'layout' => 'block',
                'button_label' => 'Add FAQ',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_page_faq_category',
                        'label' => 'Category',
                        'name' => 'category',
                        'type' => 'select',
                        'choices' => [
                            'general' => 'General Questions',
                            'pricing' => 'Pricing',
                            'scheduling' => 'Scheduling',
                            'policies' => 'Policies',
                        ],
                        'default_value' => 'general',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_page_faq_question',
                        'label' => 'Question',
                        'name' => 'question',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_page_faq_answer',
                        'label' => 'Answer',
                        'name' => 'answer',
                        'type' => 'wysiwyg',
                        'instructions' => 'Write a clear, helpful answer. Use formatting tools to add emphasis, lists, or links.',
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'delay' => 0,
                        'required' => 1,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
                [
                    'param' => 'page_type',
                    'operator' => '!=',
                    'value' => 'front_page',
                ],
                [
                    'param' => 'page_template',
                    'operator' => '!=',
                    'value' => 'page-templates/page-about.php',
                ],
                // Exclude resources pages that have their own tailored editor panels.
                [
                    'param' => 'page_template',
                    'operator' => '!=',
                    'value' => 'page-templates/page-news.php',
                ],
                [
                    'param' => 'page_template',
                    'operator' => '!=',
                    'value' => 'page-templates/page-faqs.php',
                ],
                [
                    'param' => 'page_template',
                    'operator' => '!=',
                    'value' => 'page-templates/page-cqc-hub.php',
                ],
                [
                    'param' => 'page_template',
                    'operator' => '!=',
                    'value' => 'page-templates/page-training-guides.php',
                ],
                [
                    'param' => 'page_template',
                    'operator' => '!=',
                    'value' => 'page-templates/page-downloadable-resources.php',
                ],
            ],
        ],
        'menu_order' => 5,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // RESOURCES: News & Articles (page template)
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_resources_news_page',
        'title' => 'News Page Settings',
        'fields' => [
            [
                'key' => 'field_resources_news_note',
                'label' => 'Note',
                'name' => '',
                'type' => 'message',
                'message' => 'This page lists your Posts. Use the fields below only to control the hero heading text.',
            ],
            [
                'key' => 'field_resources_news_hero_title',
                'label' => 'Hero Title (H1)',
                'name' => 'hero_title',
                'type' => 'text',
                'maxlength' => 100,
            ],
            [
                'key' => 'field_resources_news_hero_subtitle',
                'label' => 'Hero Subtitle',
                'name' => 'hero_subtitle',
                'type' => 'textarea',
                'rows' => 3,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/page-news.php',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // RESOURCES: FAQs (page template)
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_resources_faqs_page',
        'title' => 'FAQs Page Content',
        'fields' => [
            [
                'key' => 'field_resources_faqs_tab_hero',
                'label' => 'Hero',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_faqs_hero_title',
                'label' => 'Hero Title (H1)',
                'name' => 'hero_title',
                'type' => 'text',
                'maxlength' => 100,
            ],
            [
                'key' => 'field_resources_faqs_hero_subtitle',
                'label' => 'Hero Subtitle',
                'name' => 'hero_subtitle',
                'type' => 'textarea',
                'rows' => 3,
            ],
            [
                'key' => 'field_resources_faqs_tab_items',
                'label' => 'FAQ Items',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_faqs_items',
                'label' => 'FAQs',
                'name' => 'faqs',
                'type' => 'repeater',
                'layout' => 'block',
                'button_label' => 'Add FAQ',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_resources_faqs_item_category',
                        'label' => 'Category',
                        'name' => 'category',
                        'type' => 'select',
                        'choices' => [
                            'general' => 'General',
                            'booking' => 'Booking & Scheduling',
                            'certification' => 'Certification & Accreditation',
                            'course-specific' => 'Course-Specific',
                            'payment' => 'Payment & Funding',
                            'group-training' => 'Group Training & Employers',
                        ],
                        'default_value' => 'general',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_faqs_item_question',
                        'label' => 'Question',
                        'name' => 'question',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_faqs_item_answer',
                        'label' => 'Answer',
                        'name' => 'answer',
                        'type' => 'wysiwyg',
                        'instructions' => 'Write a clear, helpful answer. Use formatting tools to add emphasis, lists, or links. The answer will be displayed in the FAQ accordion.',
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'delay' => 0,
                        'required' => 1,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/page-faqs.php',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // RESOURCES: CQC Compliance Hub (page template)
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_resources_cqc_hub_page',
        'title' => 'CQC Hub Content',
        'fields' => [
            [
                'key' => 'field_resources_cqc_tab_hero',
                'label' => 'Hero',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_cqc_hero_title',
                'label' => 'Hero Title (H1)',
                'name' => 'hero_title',
                'type' => 'text',
                'maxlength' => 100,
            ],
            [
                'key' => 'field_resources_cqc_hero_subtitle',
                'label' => 'Hero Subtitle',
                'name' => 'hero_subtitle',
                'type' => 'textarea',
                'rows' => 3,
            ],
            [
                'key' => 'field_resources_cqc_intro_text',
                'label' => 'Intro Text',
                'name' => 'intro_text',
                'type' => 'textarea',
                'rows' => 4,
            ],
            [
                'key' => 'field_resources_cqc_tab_content_sources',
                'label' => 'Content Sources',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_cqc_post_categories',
                'label' => 'Post Categories to Show',
                'name' => 'cqc_post_categories',
                'type' => 'taxonomy',
                'taxonomy' => 'category',
                'field_type' => 'multi_select',
                'add_term' => 0,
                'save_terms' => 0,
                'load_terms' => 0,
                'return_format' => 'id',
                'instructions' => 'Optional. If empty, the hub shows the latest posts as a fallback.',
            ],
            [
                'key' => 'field_resources_cqc_tab_faqs',
                'label' => 'FAQs',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_cqc_faqs',
                'label' => 'FAQs',
                'name' => 'faqs',
                'type' => 'repeater',
                'layout' => 'block',
                'button_label' => 'Add FAQ',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_resources_cqc_faq_category',
                        'label' => 'Category',
                        'name' => 'category',
                        'type' => 'select',
                        'choices' => [
                            'general' => 'General',
                            'training' => 'Training',
                            'inspection' => 'Inspection Preparation',
                        ],
                        'default_value' => 'general',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_cqc_faq_question',
                        'label' => 'Question',
                        'name' => 'question',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_cqc_faq_answer',
                        'label' => 'Answer',
                        'name' => 'answer',
                        'type' => 'wysiwyg',
                        'instructions' => 'Write a clear, helpful answer. Use formatting tools to add emphasis, lists, or links.',
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'delay' => 0,
                        'rows' => 4,
                        'required' => 1,
                    ],
                ],
            ],
            [
                'key' => 'field_resources_cqc_tab_downloads',
                'label' => 'Downloadable Resources',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_cqc_downloads',
                'label' => 'Resources',
                'name' => 'downloadable_resources',
                'type' => 'repeater',
                'instructions' => 'Add downloadable resources that will appear on this page. Select from your existing resources.',
                'layout' => 'block',
                'button_label' => 'Add Resource',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_resources_cqc_download_resource',
                        'label' => 'Select Resource',
                        'name' => 'resource',
                        'type' => 'post_object',
                        'post_type' => ['cta_resource'],
                        'return_format' => 'object',
                        'ui' => 1,
                        'required' => 1,
                        'instructions' => 'Choose a resource from your Resources library',
                    ],
                    [
                        'key' => 'field_resources_cqc_download_icon',
                        'label' => 'Custom Icon (Optional)',
                        'name' => 'custom_icon',
                        'type' => 'text',
                        'instructions' => 'Override the resource icon. E.g., fas fa-clipboard-check',
                        'placeholder' => 'fas fa-file',
                    ],
                ],
            ],
            [
                'key' => 'field_resources_cqc_tab_mandatory_training',
                'label' => 'Mandatory Training by Setting',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_cqc_mandatory_training_title',
                'label' => 'Section Title',
                'name' => 'mandatory_training_title',
                'type' => 'text',
                'default_value' => 'Mandatory Training by Setting',
                'instructions' => 'Title for the Mandatory Training by Setting section',
            ],
            [
                'key' => 'field_resources_cqc_mandatory_training_description',
                'label' => 'Section Description',
                'name' => 'mandatory_training_description',
                'type' => 'textarea',
                'default_value' => 'Training requirements vary by care setting. Find what\'s required for your service type.',
                'rows' => 2,
                'instructions' => 'Description text below the section title',
            ],
            [
                'key' => 'field_resources_cqc_mandatory_training_settings',
                'label' => 'Care Settings',
                'name' => 'mandatory_training_settings',
                'type' => 'repeater',
                'instructions' => 'Add or edit the mandatory training requirements for each care setting. Leave empty to use default content.',
                'layout' => 'block',
                'button_label' => 'Add Care Setting',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_resources_cqc_setting_name',
                        'label' => 'Setting Name',
                        'name' => 'setting_name',
                        'type' => 'text',
                        'required' => 1,
                        'instructions' => 'e.g., Domiciliary Care, Residential Care Home, Nursing Home',
                    ],
                    [
                        'key' => 'field_resources_cqc_setting_id',
                        'label' => 'Setting ID (for anchor links)',
                        'name' => 'setting_id',
                        'type' => 'text',
                        'instructions' => 'Unique ID for this setting (e.g., domiciliary, residential, nursing). Used for anchor links.',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_cqc_setting_intro',
                        'label' => 'Introduction Text (Optional)',
                        'name' => 'setting_intro',
                        'type' => 'textarea',
                        'rows' => 2,
                        'instructions' => 'Optional intro text before the course list (e.g., "All residential care requirements plus:")',
                    ],
                    [
                        'key' => 'field_resources_cqc_setting_courses',
                        'label' => 'Required Courses',
                        'name' => 'setting_courses',
                        'type' => 'textarea',
                        'instructions' => 'List one course per line. The system will automatically link to courses if they exist. You can add notes in parentheses like "(if applicable)".',
                        'rows' => 10,
                        'required' => 1,
                    ],
                ],
            ],
            [
                'key' => 'field_resources_cqc_tab_inspection',
                'label' => 'Inspection Preparation',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_cqc_inspection_title',
                'label' => 'Section Title',
                'name' => 'inspection_title',
                'type' => 'text',
                'default_value' => 'CQC Inspection Preparation',
                'instructions' => 'Title for the Inspection Preparation section',
            ],
            [
                'key' => 'field_resources_cqc_inspection_description',
                'label' => 'Section Description',
                'name' => 'inspection_description',
                'type' => 'textarea',
                'default_value' => 'Be inspection-ready with organized training records and documentation',
                'rows' => 2,
            ],
            [
                'key' => 'field_resources_cqc_inspection_highlight_title',
                'label' => 'Highlight Box Title',
                'name' => 'inspection_highlight_title',
                'type' => 'text',
                'default_value' => 'What Inspectors Check',
            ],
            [
                'key' => 'field_resources_cqc_inspection_highlight_text',
                'label' => 'Highlight Box Text',
                'name' => 'inspection_highlight_text',
                'type' => 'wysiwyg',
                'default_value' => 'CQC inspectors verify all staff have completed mandatory training, certificates are current, and competency is documented.',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 0,
            ],
            [
                'key' => 'field_resources_cqc_inspection_accordions',
                'label' => 'Inspection Accordions',
                'name' => 'inspection_accordions',
                'type' => 'repeater',
                'instructions' => 'Add accordion sections for inspection preparation. Leave empty to use default content.',
                'layout' => 'block',
                'button_label' => 'Add Accordion',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_resources_cqc_inspection_accordion_title',
                        'label' => 'Accordion Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_cqc_inspection_accordion_icon',
                        'label' => 'Icon Class (Optional)',
                        'name' => 'icon',
                        'type' => 'text',
                        'instructions' => 'Font Awesome icon class (e.g., fas fa-search, fas fa-folder-open)',
                        'placeholder' => 'fas fa-search',
                    ],
                    [
                        'key' => 'field_resources_cqc_inspection_accordion_icon_color',
                        'label' => 'Icon Color (Optional)',
                        'name' => 'icon_color',
                        'type' => 'text',
                        'instructions' => 'Hex color code (e.g., #35938d)',
                        'placeholder' => '#35938d',
                    ],
                    [
                        'key' => 'field_resources_cqc_inspection_accordion_expanded',
                        'label' => 'Expanded by Default',
                        'name' => 'expanded',
                        'type' => 'true_false',
                        'default_value' => 0,
                        'ui' => 1,
                    ],
                    [
                        'key' => 'field_resources_cqc_inspection_accordion_warning',
                        'label' => 'Warning Style',
                        'name' => 'warning',
                        'type' => 'true_false',
                        'instructions' => 'Enable if this accordion should use warning/error styling',
                        'default_value' => 0,
                        'ui' => 1,
                    ],
                    [
                        'key' => 'field_resources_cqc_inspection_accordion_items',
                        'label' => 'Checklist Items',
                        'name' => 'items',
                        'type' => 'textarea',
                        'instructions' => 'List one item per line. Each item will appear as a checklist item with a checkmark.',
                        'rows' => 8,
                        'required' => 1,
                    ],
                ],
            ],
            [
                'key' => 'field_resources_cqc_inspection_cta_title',
                'label' => 'CTA Title',
                'name' => 'inspection_cta_title',
                'type' => 'text',
                'default_value' => 'Get Inspection Ready',
            ],
            [
                'key' => 'field_resources_cqc_inspection_cta_text',
                'label' => 'CTA Description',
                'name' => 'inspection_cta_text',
                'type' => 'textarea',
                'default_value' => 'Download our comprehensive checklist to ensure your training records meet CQC standards',
                'rows' => 2,
            ],
            [
                'key' => 'field_resources_cqc_inspection_cta_button_text',
                'label' => 'CTA Button Text',
                'name' => 'inspection_cta_button_text',
                'type' => 'text',
                'default_value' => 'Download Inspection Readiness Checklist',
            ],
            [
                'key' => 'field_resources_cqc_inspection_cta_link',
                'label' => 'CTA Button Link',
                'name' => 'inspection_cta_link',
                'type' => 'page_link',
                'post_type' => ['page', 'cta_resource'],
                'default_value' => 'downloadable-resources',
                'instructions' => 'Page or resource to link to',
            ],
            [
                'key' => 'field_resources_cqc_tab_regulatory',
                'label' => 'Regulatory Changes',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_cqc_regulatory_title',
                'label' => 'Section Title',
                'name' => 'regulatory_title',
                'type' => 'text',
                'default_value' => '2026 Regulatory Changes',
            ],
            [
                'key' => 'field_resources_cqc_regulatory_description',
                'label' => 'Section Description',
                'name' => 'regulatory_description',
                'type' => 'textarea',
                'default_value' => 'Stay ahead of upcoming CQC framework updates and new training requirements',
                'rows' => 2,
            ],
            [
                'key' => 'field_resources_cqc_regulatory_cards',
                'label' => 'Regulatory Cards',
                'name' => 'regulatory_cards',
                'type' => 'repeater',
                'instructions' => 'Add cards for regulatory changes and updates. Leave empty to use default content.',
                'layout' => 'block',
                'button_label' => 'Add Regulatory Card',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_resources_cqc_regulatory_label',
                        'label' => 'Label/Badge',
                        'name' => 'label',
                        'type' => 'text',
                        'instructions' => 'e.g., New Framework, Mandatory Training, Statutory Requirement',
                    ],
                    [
                        'key' => 'field_resources_cqc_regulatory_title',
                        'label' => 'Card Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_cqc_regulatory_content',
                        'label' => 'Content',
                        'name' => 'content',
                        'type' => 'wysiwyg',
                        'instructions' => 'Main content text. You can add links and formatting.',
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 0,
                    ],
                    [
                        'key' => 'field_resources_cqc_regulatory_list_items',
                        'label' => 'List Items (Optional)',
                        'name' => 'list_items',
                        'type' => 'textarea',
                        'instructions' => 'List one item per line. Each will appear as a bullet point. You can use HTML for links.',
                        'rows' => 6,
                    ],
                    [
                        'key' => 'field_resources_cqc_regulatory_highlight',
                        'label' => 'Highlight Card',
                        'name' => 'highlight',
                        'type' => 'true_false',
                        'instructions' => 'Enable to make this card stand out with special styling',
                        'default_value' => 0,
                        'ui' => 1,
                    ],
                    [
                        'key' => 'field_resources_cqc_regulatory_link_url',
                        'label' => 'Link URL (Optional)',
                        'name' => 'link_url',
                        'type' => 'url',
                        'instructions' => 'External or internal link URL',
                    ],
                    [
                        'key' => 'field_resources_cqc_regulatory_link_text',
                        'label' => 'Link Text (Optional)',
                        'name' => 'link_text',
                        'type' => 'text',
                        'instructions' => 'Text for the link button (e.g., "Read more", "View guidance")',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/page-cqc-hub.php',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // RESOURCES: Training Guides & Tools (page template)
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_resources_training_guides_page',
        'title' => 'Training Guides Page Content',
        'fields' => [
            [
                'key' => 'field_resources_guides_tab_hero',
                'label' => 'Hero',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_guides_hero_title',
                'label' => 'Hero Title (H1)',
                'name' => 'hero_title',
                'type' => 'text',
                'maxlength' => 100,
            ],
            [
                'key' => 'field_resources_guides_hero_subtitle',
                'label' => 'Hero Subtitle',
                'name' => 'hero_subtitle',
                'type' => 'textarea',
                'rows' => 3,
            ],
            [
                'key' => 'field_resources_guides_cta_label',
                'label' => 'Hero CTA Label',
                'name' => 'hero_cta_label',
                'type' => 'text',
                'default_value' => 'Download Training Planning Guide',
            ],
            [
                'key' => 'field_resources_guides_cta_link',
                'label' => 'Hero CTA Link',
                'name' => 'hero_cta_link',
                'type' => 'page_link',
                'post_type' => ['page'],
                'allow_null' => 1,
                'instructions' => 'Optional. Defaults to the Downloadable Resources page.',
            ],
            [
                'key' => 'field_resources_guides_tab_custom',
                'label' => 'Custom Sections (Optional)',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_guides_use_custom',
                'label' => 'Use custom sections on the frontend',
                'name' => 'use_custom_sections',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0,
            ],
            [
                'key' => 'field_resources_guides_sections',
                'label' => 'Sections',
                'name' => 'guide_sections',
                'type' => 'repeater',
                'layout' => 'block',
                'button_label' => 'Add Section',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_resources_guides_section_title',
                        'label' => 'Section Title (H2)',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_guides_section_content',
                        'label' => 'Section Content',
                        'name' => 'content',
                        'type' => 'wysiwyg',
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'required' => 1,
                    ],
                ],
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_resources_guides_use_custom',
                            'operator' => '==',
                            'value' => '1',
                        ],
                    ],
                ],
            ],
            [
                'key' => 'field_resources_guides_tab_downloads',
                'label' => 'Downloadable Resources',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_guides_downloads',
                'label' => 'Resources',
                'name' => 'downloadable_resources',
                'type' => 'repeater',
                'instructions' => 'Add downloadable resources that will appear on this page. Select from your existing resources.',
                'layout' => 'block',
                'button_label' => 'Add Resource',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_resources_guides_download_resource',
                        'label' => 'Select Resource',
                        'name' => 'resource',
                        'type' => 'post_object',
                        'post_type' => ['cta_resource'],
                        'return_format' => 'object',
                        'ui' => 1,
                        'required' => 1,
                        'instructions' => 'Choose a resource from your Resources library',
                    ],
                    [
                        'key' => 'field_resources_guides_download_icon',
                        'label' => 'Custom Icon (Optional)',
                        'name' => 'custom_icon',
                        'type' => 'text',
                        'instructions' => 'Override the resource icon. E.g., fas fa-file-pdf',
                        'placeholder' => 'fas fa-file',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/page-training-guides.php',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // =========================================
    // RESOURCES: Downloadable Resources (page template)
    // =========================================
    acf_add_local_field_group([
        'key' => 'group_resources_downloadable_page',
        'title' => 'Downloadable Resources Content',
        'fields' => [
            [
                'key' => 'field_resources_dl_tab_hero',
                'label' => 'Hero',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_dl_hero_title',
                'label' => 'Hero Title (H1)',
                'name' => 'hero_title',
                'type' => 'text',
                'maxlength' => 100,
            ],
            [
                'key' => 'field_resources_dl_hero_subtitle',
                'label' => 'Hero Subtitle',
                'name' => 'hero_subtitle',
                'type' => 'textarea',
                'rows' => 3,
            ],
            [
                'key' => 'field_resources_dl_tab_library',
                'label' => 'Resource Library',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'key' => 'field_resources_dl_library_title',
                'label' => 'Library Title (H2)',
                'name' => 'resource_library_title',
                'type' => 'text',
                'default_value' => 'Resource Library',
            ],
            [
                'key' => 'field_resources_dl_library_subtitle',
                'label' => 'Library Subtitle',
                'name' => 'resource_library_subtitle',
                'type' => 'textarea',
                'rows' => 2,
                'default_value' => 'Filter resources by category',
            ],
            [
                'key' => 'field_resources_dl_categories',
                'label' => 'Category Headings',
                'name' => 'resource_categories',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Add / Override Category',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_resources_dl_category_key',
                        'label' => 'Category Key',
                        'name' => 'key',
                        'type' => 'select',
                        'choices' => [
                            'quick-reference' => 'Quick Reference Cards',
                            'templates' => 'Templates & Tools',
                            'policies' => 'Policy Templates',
                            'infographics' => 'Infographics & Posters',
                            'checklists' => 'Checklists',
                        ],
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_dl_category_title',
                        'label' => 'Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_dl_category_subtitle',
                        'label' => 'Subtitle',
                        'name' => 'subtitle',
                        'type' => 'textarea',
                        'rows' => 2,
                    ],
                ],
            ],
            [
                'key' => 'field_resources_dl_items',
                'label' => 'Resources',
                'name' => 'resource_items',
                'type' => 'repeater',
                'layout' => 'block',
                'button_label' => 'Add Resource',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_resources_dl_item_category',
                        'label' => 'Category',
                        'name' => 'category',
                        'type' => 'select',
                        'choices' => [
                            'quick-reference' => 'Quick Reference Cards',
                            'templates' => 'Templates & Tools',
                            'policies' => 'Policy Templates',
                            'infographics' => 'Infographics & Posters',
                            'checklists' => 'Checklists',
                        ],
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_dl_item_icon',
                        'label' => 'Icon Class',
                        'name' => 'icon',
                        'type' => 'text',
                        'instructions' => 'Font Awesome class (e.g., "fas fa-file-pdf")',
                        'default_value' => 'fas fa-file',
                    ],
                    [
                        'key' => 'field_resources_dl_item_title',
                        'label' => 'Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_dl_item_description',
                        'label' => 'Description',
                        'name' => 'description',
                        'type' => 'textarea',
                        'rows' => 3,
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_resources_dl_item_file',
                        'label' => 'File',
                        'name' => 'file',
                        'type' => 'file',
                        'return_format' => 'url',
                        'instructions' => 'Upload the file to download. If you prefer, leave empty and provide a URL below.',
                    ],
                    [
                        'key' => 'field_resources_dl_item_url',
                        'label' => 'URL (optional)',
                        'name' => 'url',
                        'type' => 'url',
                    ],
                    [
                        'key' => 'field_resources_dl_item_button_label',
                        'label' => 'Button Label',
                        'name' => 'button_label',
                        'type' => 'text',
                        'default_value' => 'Download',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/page-downloadable-resources.php',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // News Article SEO Fields
    acf_add_local_field_group([
        'key' => 'group_news_seo',
        'title' => 'SEO Settings',
        'fields' => [
            [
                'key' => 'field_news_meta_title',
                'label' => 'Meta Title',
                'name' => 'news_meta_title',
                'type' => 'text',
                'instructions' => '50-60 chars (optional)',
                'maxlength' => 60,
                'placeholder' => 'Max 60 characters recommended',
            ],
            [
                'key' => 'field_news_meta_description',
                'label' => 'Meta Description',
                'name' => 'news_meta_description',
                'type' => 'textarea',
                'instructions' => 'Custom description for search engines (leave blank to use excerpt)',
                'maxlength' => 160,
                'rows' => 2,
                'placeholder' => 'Max 160 characters recommended',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
        'menu_order' => 1,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    // Page SEO Fields - For permanent pages (Home, About, Contact, etc.)
    acf_add_local_field_group([
        'key' => 'group_page_seo',
        'title' => 'SEO Settings (Permanent Pages)',
        'fields' => [
            [
                'key' => 'field_page_seo_meta_title',
                'label' => 'Meta Title (SEO)',
                'name' => 'page_seo_meta_title',
                'type' => 'text',
                'instructions' => 'Custom meta title for search engines. 50-60 characters recommended. Leave blank to use page title.',
                'placeholder' => 'e.g., Care Training in Kent | CQC-Compliant | CTA',
                'maxlength' => 60,
            ],
            [
                'key' => 'field_page_seo_meta_description',
                'label' => 'Meta Description (SEO)',
                'name' => 'page_seo_meta_description',
                'type' => 'textarea',
                'instructions' => 'Custom description for search engines. 150-160 characters recommended. Leave blank to use excerpt or auto-generate.',
                'rows' => 3,
                'maxlength' => 160,
                'placeholder' => 'e.g., Professional care sector training in Maidstone, Kent. CQC-compliant, CPD-accredited courses since 2020.',
            ],
            [
                'key' => 'field_page_schema_type',
                'label' => 'Schema Type',
                'name' => 'page_schema_type',
                'type' => 'select',
                'instructions' => 'Select the schema.org type for this page. Auto-detected for permanent pages.',
                'choices' => [
                    'WebPage' => 'WebPage (Generic)',
                    'HomePage' => 'HomePage',
                    'AboutPage' => 'AboutPage',
                    'ContactPage' => 'ContactPage',
                    'CollectionPage' => 'CollectionPage',
                    'FAQPage' => 'FAQPage',
                ],
                'default_value' => 'WebPage',
                'allow_null' => 0,
                'multiple' => 0,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
        ],
        'menu_order' => 1,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
    ]);
}
add_action('acf/init', 'cta_register_acf_fields');

/**
 * Get default values for page fields based on template or slug
 */
function cta_get_page_defaults($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'page') {
        return [];
    }
    
    $template = get_page_template_slug($post_id);
    $slug = $post->post_name;
    
    $defaults = [
        'hero_title' => $post->post_title,
        'hero_subtitle' => '',
        'section_how_it_works_title' => '',
        'section_testimonials_title' => '',
        'section_benefits_title' => '',
        'section_benefits_subtitle' => '',
        'section_form_title' => '',
        'section_form_description' => '',
        'section_faq_title' => '',
    ];
    
    // Map templates and slugs to default values
    $template_defaults = [
        'page-templates/page-group-training.php' => [
            'hero_title' => 'Group Training for Care Teams in Kent',
            'hero_subtitle' => 'Train your entire team together. Flexible scheduling, accredited certificates, and group rates that make quality training affordable.',
            'section_how_it_works_title' => 'How It Works',
            'section_testimonials_title' => 'Trusted by Care Teams Across Kent',
            'section_benefits_title' => 'Why Choose Group Training?',
            'section_benefits_subtitle' => 'Maximise value while maintaining quality training standards',
            'section_form_title' => 'Get Your Custom Training Quote in 24 Hours',
            'section_form_description' => "Tell us about your team and training needs. We'll send you a custom quote within 24 hours - no obligation, no hassle.",
            'section_faq_title' => 'Frequently Asked Questions',
        ],
        'page-templates/page-contact.php' => [
            'hero_title' => 'Contact Us for Care Training in Kent',
            'hero_subtitle' => "Whether you're booking a course, need group training, or have questions about compliance, we're here to help.",
            'section_form_title' => 'Send Us a Message',
        ],
        'page-templates/page-news.php' => [
            'hero_title' => 'News & Updates',
            'hero_subtitle' => 'Stay informed with the latest care sector news, CQC updates, and training insights.',
        ],
        'page-templates/page-about.php' => [
            'hero_title' => 'About Our Care Training in Kent',
            'hero_subtitle' => 'CQC-compliant, CPD-accredited care sector training in Kent since 2020',
        ],
        'page-templates/page-location.php' => [
            // These are refined below with location-specific defaults when possible.
            'section_testimonials_title' => 'What People Say',
            'section_faq_title' => 'Frequently Asked Questions',
        ],
    ];
    
    // Check template first
    if (!empty($template) && isset($template_defaults[$template])) {
        $defaults = array_merge($defaults, $template_defaults[$template]);
    }
    // Fallback to slug-based defaults
    elseif (!empty($slug)) {
        $slug_defaults = [
            'group-training' => [
                'hero_title' => 'Group Training for Care Teams in Kent',
                'hero_subtitle' => 'Train your entire team together. Flexible scheduling, accredited certificates, and group rates that make quality training affordable.',
                'section_how_it_works_title' => 'How It Works',
                'section_testimonials_title' => 'Trusted by Care Teams Across Kent',
                'section_benefits_title' => 'Why Choose Group Training?',
                'section_benefits_subtitle' => 'Maximise value while maintaining quality training standards',
                'section_form_title' => 'Get Your Custom Training Quote in 24 Hours',
                'section_form_description' => "Tell us about your team and training needs. We'll send you a custom quote within 24 hours - no obligation, no hassle.",
                'section_faq_title' => 'Frequently Asked Questions',
            ],
            'contact' => [
                'hero_title' => 'Contact Us for Care Training in Kent',
                'hero_subtitle' => "Whether you're booking a course, need group training, or have questions about compliance, we're here to help.",
                'section_form_title' => 'Send Us a Message',
            ],
            'news' => [
                'hero_title' => 'News & Updates',
                'hero_subtitle' => 'Stay informed with the latest care sector news, CQC updates, and training insights.',
            ],
            'about' => [
                'hero_title' => 'About Our Care Training in Kent',
                'hero_subtitle' => 'CQC-compliant, CPD-accredited care sector training in Kent since 2020',
            ],
        ];
        
        if (isset($slug_defaults[$slug])) {
            $defaults = array_merge($defaults, $slug_defaults[$slug]);
        }
    }

    // Location pages: make defaults feel page-specific (pull from location data)
    if ($template === 'page-templates/page-location.php') {
        $location_slug = function_exists('get_field') ? get_field('location_slug', $post_id) : '';
        if (empty($location_slug) && !empty($slug)) {
            $location_slug = $slug;
        }
        $location_data = function_exists('cta_get_location_data') ? cta_get_location_data((string) $location_slug) : null;
        if (is_array($location_data)) {
            // Keep hero_title as the page title by default, but make subtitle helpful.
            if (empty($defaults['hero_subtitle']) && !empty($location_data['description'])) {
                $defaults['hero_subtitle'] = (string) $location_data['description'];
            }
        }
    }
    
    return $defaults;
}

/**
 * Auto-populate hero_title from page defaults if empty
 */
function cta_populate_hero_title($value, $post_id, $field) {
    if ($value !== null && $value !== '') {
        return $value;
    }
    
    if ($field['name'] === 'hero_title') {
        $defaults = cta_get_page_defaults($post_id);
        if (!empty($defaults['hero_title'])) {
            return $defaults['hero_title'];
        }
    }
    
    return $value;
}
add_filter('acf/load_value/name=hero_title', 'cta_populate_hero_title', 10, 3);

/**
 * Auto-populate hero_subtitle from page defaults if empty
 */
function cta_populate_hero_subtitle($value, $post_id, $field) {
    if ($value !== null && $value !== '') {
        return $value;
    }
    
    if ($field['name'] === 'hero_subtitle') {
        $defaults = cta_get_page_defaults($post_id);
        if (!empty($defaults['hero_subtitle'])) {
            return $defaults['hero_subtitle'];
        }
    }
    
    return $value;
}
add_filter('acf/load_value/name=hero_subtitle', 'cta_populate_hero_subtitle', 10, 3);

/**
 * Auto-populate section heading fields from page defaults if empty
 */
function cta_populate_section_headings($value, $post_id, $field) {
    if ($value !== null && $value !== '') {
        return $value;
    }
    
    $defaults = cta_get_page_defaults($post_id);
    $field_name = $field['name'];
    
    if (isset($defaults[$field_name]) && !empty($defaults[$field_name])) {
        return $defaults[$field_name];
    }
    
    return $value;
}
add_filter('acf/load_value/name=section_how_it_works_title', 'cta_populate_section_headings', 10, 3);
add_filter('acf/load_value/name=section_testimonials_title', 'cta_populate_section_headings', 10, 3);
add_filter('acf/load_value/name=section_benefits_title', 'cta_populate_section_headings', 10, 3);
add_filter('acf/load_value/name=section_benefits_subtitle', 'cta_populate_section_headings', 10, 3);
add_filter('acf/load_value/name=section_form_title', 'cta_populate_section_headings', 10, 3);
add_filter('acf/load_value/name=section_form_description', 'cta_populate_section_headings', 10, 3);
add_filter('acf/load_value/name=section_faq_title', 'cta_populate_section_headings', 10, 3);

/**
 * Get default content sections for pages based on template
 */
function cta_get_page_content_sections($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'page') {
        return [];
    }
    
    $template = get_page_template_slug($post_id);
    $slug = $post->post_name;
    
    $sections = [];
    
    // Location page sections (SEO landing pages)
    if ($template === 'page-templates/page-location.php') {
        // Try to infer location from ACF location_slug, otherwise page slug.
        $location_slug = function_exists('get_field') ? get_field('location_slug', $post_id) : '';
        if (empty($location_slug) && !empty($slug)) {
            $location_slug = $slug;
        }
        $location_data = function_exists('cta_get_location_data') ? cta_get_location_data((string) $location_slug) : null;
        $location_name = is_array($location_data) ? ($location_data['display_name'] ?? $post->post_title) : $post->post_title;

        $sections = [
            [
                'section_heading' => 'Professional Care Training in ' . $location_name,
                'section_list_style' => 'default',
                'section_content' => '<p>We provide CQC-compliant, CPD-accredited care training for teams across ' . esc_html($location_name) . ' and the surrounding area.</p>
<p>Choose face-to-face sessions at our Maidstone Studios or book on-site training at your service to keep your team confident, compliant, and inspection-ready.</p>
<h3>On-site training for your team</h3>
<p>We can deliver training at your location and tailor examples to your policies, service user needs, and real-world scenarios.</p>',
            ],
            [
                'section_heading' => 'What training can you book?',
                'section_list_style' => 'default',
                'section_content' => '<p>Our most popular courses for care providers include:</p>
<ul>
<li>Moving &amp; Handling</li>
<li>Safeguarding Adults</li>
<li>Medication Competency</li>
<li>Emergency First Aid at Work</li>
<li>Infection Prevention &amp; Control</li>
<li>Care Certificate support</li>
</ul>
<p>Browse our full course list and upcoming dates, or contact us for a tailored training plan for your service.</p>',
            ],
        ];

        return $sections;
    }

    // Group Training page sections
    if ($template === 'page-templates/page-group-training.php' || $slug === 'group-training') {
        $sections = [
            [
                'section_heading' => 'How It Works',
                'section_content' => '<p>Our simple three-step process makes it easy to get your team trained:</p><h3>1. Request a quote</h3><p>Fill in the form or call us with your team size and training needs.</p><h3>2. Book your dates</h3><p>We\'ll send a quote within 24 hours. Choose dates that suit your team.</p><h3>3. Train your team</h3><p>We deliver the training and your team receives accredited certificates.</p>',
            ],
            [
                'section_heading' => 'Why Choose Group Training?',
                'section_content' => '<p>Maximise value while maintaining quality training standards:</p><h3>Train on your schedule</h3><p>Evenings, weekends, or during shifts - we work around your operations and staff availability.</p><h3>Consistent team training</h3><p>No knowledge gaps - everyone receives the same high-quality training to the same professional standards.</p><h3>Compliant & Accredited</h3><p>Keep your team inspection-ready with CPD accredited training that meets CQC-compliance.</p>',
            ],
        ];
    }
    // Contact page sections
    elseif ($template === 'page-templates/page-contact.php' || $slug === 'contact') {
        $sections = [
            [
                'section_heading' => 'Get in Touch',
                'section_content' => '<p>Whether you\'re booking a course, need group training, or have questions about compliance, we\'re here to help.</p><p>Fill out the form below and we\'ll get back to you as soon as possible.</p>',
            ],
        ];
    }
    // News page sections
    elseif ($template === 'page-templates/page-news.php' || $slug === 'news') {
        $sections = [
            [
                'section_heading' => 'Latest Updates',
                'section_content' => '<p>Stay informed with the latest care sector news, CQC updates, and training insights from our expert team.</p>',
            ],
        ];
    }
    
    return $sections;
}

/**
 * Get default testimonials for pages based on template
 */
function cta_get_page_testimonials($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'page') {
        return [];
    }
    
    $template = get_page_template_slug($post_id);
    $slug = $post->post_name;
    
    $testimonials = [];
    
    // Group Training page testimonials
    if ($template === 'page-templates/page-group-training.php' || $slug === 'group-training') {
        $testimonials = [
            [
                'quote' => 'Jen is a fantastic trainer and leaves you feeling confident in your new learnt abilities!',
                'author' => 'Expertise Homecare',
                'icon' => 'fas fa-building',
            ],
            [
                'quote' => "It's much easier to learn when a trainer is passionate and excited about the topics they teach.",
                'author' => 'Inga',
                'icon' => 'fas fa-user',
            ],
            [
                'quote' => "Jen's training style is very much centred around each individual and is delivered in a very personable manner.",
                'author' => 'Melvyn',
                'icon' => 'fas fa-user',
            ],
        ];
    }
    
    return $testimonials;
}

/**
 * Get default FAQs for pages based on template
 */
function cta_get_page_faqs($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'page') {
        return [];
    }
    
    $template = get_page_template_slug($post_id);
    $slug = $post->post_name;
    
    $faqs = [];
    
    // Location page FAQs (lightweight, customisable per page)
    if ($template === 'page-templates/page-location.php') {
        $location_slug = function_exists('get_field') ? get_field('location_slug', $post_id) : '';
        if (empty($location_slug) && !empty($slug)) {
            $location_slug = $slug;
        }
        $location_data = function_exists('cta_get_location_data') ? cta_get_location_data((string) $location_slug) : null;
        $location_name = is_array($location_data) ? ($location_data['display_name'] ?? $post->post_title) : $post->post_title;

        $faqs = [
            [
                'category' => 'general',
                'question' => 'Do you deliver care training in ' . $location_name . '?',
                'answer' => 'Yes. We deliver training for care teams in ' . $location_name . ' and nearby areas. You can attend scheduled sessions or book on-site training at your service.',
            ],
            [
                'category' => 'scheduling',
                'question' => 'Can you train our whole team on-site?',
                'answer' => 'Yes. On-site training is ideal for groups and can be scheduled around shifts where possible. Contact us with your team size and preferred dates.',
            ],
            [
                'category' => 'pricing',
                'question' => 'Do you offer group rates?',
                'answer' => 'Yes. Group and multi-course bookings are often more cost-effective than booking individual places. We’ll provide a quote based on your requirements.',
            ],
            [
                'category' => 'general',
                'question' => 'Are your courses CQC-compliant and accredited?',
                'answer' => 'Our training is designed for the care sector and aligns with CQC expectations. Many courses are CPD-accredited and include certificates for your records.',
            ],
        ];

        return $faqs;
    }

    // Group Training page FAQs
    if ($template === 'page-templates/page-group-training.php' || $slug === 'group-training') {
        $faqs = [
            [
                'category' => 'general',
                'question' => 'How many people can attend a group training session?',
                'answer' => 'Our standard group training sessions accommodate up to 12 staff members per session. For larger groups, we can organise multiple sessions or arrange a custom training programme to suit your needs.',
            ],
            [
                'category' => 'pricing',
                'question' => "What's included in the group training price?",
                'answer' => 'All group training sessions include an expert trainer with all necessary equipment, workbooks and handouts for each attendee, practical assessments, and digital certificates upon successful completion. For on-site training, we bring everything to you - you just need to provide a suitable training room.',
            ],
            [
                'category' => 'scheduling',
                'question' => 'How far in advance should we book group training?',
                'answer' => "We recommend booking at least 2-3 weeks in advance to secure your preferred date. However, we understand that training needs can be urgent, so we'll do our best to accommodate shorter notice bookings when possible. Contact us to discuss your timeline.",
            ],
            [
                'category' => 'general',
                'question' => 'Can we combine multiple courses into one group training session?',
                'answer' => 'Yes! Our custom package option is perfect for organisations that need multiple courses. We can create a tailored training programme that combines several courses into a streamlined schedule, delivered either on-site or at our Maidstone Studios. This is ideal for larger groups and can offer additional savings.',
            ],
            [
                'category' => 'policies',
                'question' => 'What if we need to reschedule or cancel a booking?',
                'answer' => 'We understand that plans can change. Please contact us as soon as possible if you need to reschedule. We offer flexible cancellation and rescheduling policies - typically, changes made more than 7 days in advance incur no charges. Contact us to discuss your specific situation.',
            ],
            [
                'category' => 'scheduling',
                'question' => 'Do you offer training on evenings or weekends?',
                'answer' => "Absolutely! We offer flexible scheduling to work around your operations. This includes evening sessions, weekend training, and training during shift patterns. When you request a quote, let us know your preferred dates and times, and we'll work with you to find a schedule that minimises disruption to your care services.",
            ],
        ];
    }
    
    return $faqs;
}

/**
 * Auto-populate page_content_sections from page defaults if empty
 */
function cta_populate_page_content_sections($value, $post_id, $field) {
    if ($value !== null && $value !== false && !empty($value)) {
        return $value;
    }
    
    if ($field['name'] === 'page_content_sections') {
        $default_sections = cta_get_page_content_sections($post_id);
        if (!empty($default_sections)) {
            return $default_sections;
        }
    }
    
    return $value;
}
add_filter('acf/load_value/name=page_content_sections', 'cta_populate_page_content_sections', 10, 3);

/**
 * Auto-populate testimonials from page defaults if empty
 */
function cta_populate_page_testimonials($value, $post_id, $field) {
    if ($value !== null && $value !== false && !empty($value)) {
        return $value;
    }
    
    if ($field['name'] === 'testimonials') {
        $default_testimonials = cta_get_page_testimonials($post_id);
        if (!empty($default_testimonials)) {
            return $default_testimonials;
        }
    }
    
    return $value;
}
add_filter('acf/load_value/name=testimonials', 'cta_populate_page_testimonials', 10, 3);

/**
 * Auto-populate FAQs from page defaults if empty
 */
function cta_populate_page_faqs($value, $post_id, $field) {
    if ($value !== null && $value !== false && !empty($value)) {
        return $value;
    }
    
    if ($field['name'] === 'faqs') {
        $default_faqs = cta_get_page_faqs($post_id);
        if (!empty($default_faqs)) {
            return $default_faqs;
        }
    }
    
    return $value;
}
add_filter('acf/load_value/name=faqs', 'cta_populate_page_faqs', 10, 3);

/**
 * Auto-populate course_intro_paragraph from course_description if empty
 */
function cta_populate_course_intro($value, $post_id, $field) {
    if ($value !== null && $value !== false && !empty($value)) {
        return $value;
    }
    
    if ($field['name'] === 'course_intro_paragraph') {
        $description = get_field('course_description', $post_id);
        if (!empty($description)) {
            // Take first 120 words from description
            $words = explode(' ', strip_tags($description));
            $intro = implode(' ', array_slice($words, 0, 120));
            return $intro . (count($words) > 120 ? '...' : '');
        }
    }
    
    return $value;
}
add_filter('acf/load_value/name=course_intro_paragraph', 'cta_populate_course_intro', 10, 3);

/**
 * Auto-populate course_benefits from certificate/accreditation if empty
 */
function cta_populate_course_benefits($value, $post_id, $field) {
    if ($value !== null && $value !== false && !empty($value)) {
        return $value;
    }
    
    if ($field['name'] === 'course_benefits') {
        $benefits = [];
        $certificate = get_field('course_certificate', $post_id);
        $accreditation = get_field('course_accreditation', $post_id);
        
        if ($certificate) {
            $benefits[] = ['benefit' => $certificate];
        }
        if ($accreditation && strtolower(trim($accreditation)) !== 'none') {
            $benefits[] = ['benefit' => $accreditation . ' accredited'];
        }
        $benefits[] = ['benefit' => 'Digital certificate provided'];
        $benefits[] = ['benefit' => 'Training records for CQC evidence'];
        
        return !empty($benefits) ? $benefits : $value;
    }
    
    return $value;
}
add_filter('acf/load_value/name=course_benefits', 'cta_populate_course_benefits', 10, 3);

/**
 * Populate FAQs for all relevant pages on theme activation/update
 * This ensures FAQs are saved to ACF fields, not just loaded dynamically
 */
function cta_populate_faqs_on_activation() {
    if (!function_exists('get_field') || !function_exists('update_field')) {
        return;
    }
    
    // Get all pages that might need FAQs
    $pages_to_check = get_posts([
        'post_type' => 'page',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft', 'private'],
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => '_wp_page_template',
                'value' => 'page-templates/page-faqs.php',
            ],
            [
                'key' => '_wp_page_template',
                'value' => 'page-templates/page-cqc-hub.php',
            ],
            [
                'key' => '_wp_page_template',
                'value' => 'page-templates/page-group-training.php',
            ],
            [
                'key' => '_wp_page_template',
                'value' => 'page-templates/page-location.php',
            ],
        ],
    ]);
    
    // Also check by slug for pages that might not have template set
    $slug_pages = get_posts([
        'post_type' => 'page',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft', 'private'],
        'post_name__in' => ['faqs', 'group-training', 'cqc-hub', 'cqc-compliance'],
    ]);
    
    $all_pages = array_merge($pages_to_check, $slug_pages);
    $all_pages = array_unique(array_column($all_pages, 'ID'));
    
    $faqs_populated = 0;
    
    foreach ($all_pages as $page_id) {
        $page = get_post($page_id);
        if (!$page) {
            continue;
        }
        
        // Check if FAQs field already has content
        $existing_faqs = get_field('faqs', $page_id);
        if (!empty($existing_faqs) && is_array($existing_faqs) && count($existing_faqs) > 0) {
            // Already has FAQs, skip
            continue;
        }
        
        // Get default FAQs for this page
        $default_faqs = cta_get_page_faqs($page_id);
        
        if (!empty($default_faqs) && is_array($default_faqs)) {
            // Ensure all FAQs have required fields (category, question, answer)
            $validated_faqs = [];
            foreach ($default_faqs as $faq) {
                if (isset($faq['category']) && isset($faq['question']) && isset($faq['answer'])) {
                    // Ensure answer is a string (WYSIWYG fields accept plain text)
                    $validated_faqs[] = [
                        'category' => sanitize_text_field($faq['category']),
                        'question' => sanitize_text_field($faq['question']),
                        'answer' => wp_kses_post($faq['answer']), // Sanitize for WYSIWYG field
                    ];
                }
            }
            
            if (!empty($validated_faqs)) {
                // Save FAQs to ACF field (repeater with WYSIWYG sub-field)
                update_field('faqs', $validated_faqs, $page_id);
                $faqs_populated++;
            }
        }
    }
    
    // Handle FAQs page specifically (has default FAQs in template)
    $faqs_page = get_page_by_path('faqs');
    if ($faqs_page) {
        $existing_faqs = get_field('faqs', $faqs_page->ID);
        if (empty($existing_faqs) || !is_array($existing_faqs) || count($existing_faqs) === 0) {
            // Get default FAQs from template (42 FAQs)
            $default_faqs = [
                // General Training Questions (8 FAQs)
                ['category' => 'general', 'question' => 'What training is mandatory for care workers?', 'answer' => 'Mandatory training is the essential foundation every care worker needs. At CTA, we ensure care workers complete: Health and Safety Awareness : Understanding workplace hazards and your responsibilities; Infection Prevention and Control : Current practices for safe, hygienic care; Fire Safety : Evacuating safely and protecting people in your care; Moving and Handling : Safe techniques to protect yourself and those you support; Safeguarding Adults : Recognising and reporting abuse or neglect; Learning Disability and Autism Awareness (Oliver McGowan training) : Required by law since July 2022; Safeguarding Children : Essential for roles involving contact with under:18s. For roles involving medication, Medication Competency is non:negotiable. All of these are CQC expectations, and gaps in mandatory training are red flags during inspection. CTA Reality Check: Just because you\'ve done the online module doesn\'t mean you\'re competent. Our hands:on approach ensures your team can actually do these things under pressure:not just pass a quiz.'],
                ['category' => 'general', 'question' => 'How often does training need to be renewed?', 'answer' => 'The short answer: Every three years minimum for most mandatory training, unless the content is included in a recognised adult social care qualification within that three:year window. However, some topics need refreshing sooner if: new risks are introduced (new equipment, new care tasks); legislation changes (happens regularly in care); an incident or near:miss highlights gaps; individual competency assessments show decline. Medication training is more frequent:usually annual refreshers are expected because this is high:risk, high:compliance work. CTA Advice: Don\'t wait until certificates expire. Build a renewal calendar now and book courses 2:3 months in advance:our courses fill up fast, and compliance deadlines sneak up.'],
                ['category' => 'general', 'question' => 'What\'s the difference between CPD and accredited training?', 'answer' => 'CPD (Continuing Professional Development) is ongoing learning:it can be formal or informal, and it\'s flexible. Reading a safeguarding update article, shadowing a colleague on medication rounds, attending a webinar:that\'s all CPD. Accredited training is formal, assessed learning leading to a recognised qualification or certificate. It\'s governed by awarding bodies like TQUK, NCFE CACHE, or HSE (for First Aid). Accredited courses have standards, quality checks, and carry more weight. Why it matters for you: CQC expects some accredited training, but they also recognise CPD. A good training strategy mixes both:accredited courses for core mandatory training, CPD for specialisms and ongoing development. CTA Mix: We deliver HSE:approved First Aid (accredited), CQC:compliant courses (accredited), and we support your CPD through coaching, on:site training tailored to your policies, and scenario:based learning.'],
                ['category' => 'general', 'question' => 'Do online courses meet CQC requirements?', 'answer' => 'Short answer: It depends, but CQC is increasingly sceptical about online:only training. The CQC Inspection Framework values competence. For practical, high:stakes care skills (First Aid, medication administration, moving and handling), online modules don\'t build muscle memory or real:world confidence. Staff can\'t practice CPR on a dummy via Zoom. For theory:heavy topics (e.g., GDPR, understanding dementia), online can work as part of a blended approach. But even then, interaction and assessment matter. CQC Reality: Inspectors ask, "Can your staff actually do this if a crisis happened?" Online certificates often can\'t answer that confidently. CTA Stance: We\'re in:person, hands:on, and practical. No "click and certificate" shortcuts. Real people, real scenarios, real confidence.'],
                ['category' => 'general', 'question' => 'What is the Care Certificate and who needs it?', 'answer' => 'The Care Certificate is a nationally recognised qualification covering 15 standards for adult social care workers. It\'s not legally mandatory, but it\'s a gold standard:hugely respected by employers, CQC, and Skills for Care. Who benefits most: New care workers entering the industry; Care assistants progressing toward team leader roles; Anyone without formal social care qualifications; Roles involving domiciliary or residential care. What it covers: Communication, person:centred care, duty of care, safeguarding, equality, health and safety, infection control, medication awareness, mental health, dementia, nutrition, hydration, privacy, and dignity. Reality: The Care Certificate isn\'t mandatory, but if a CQC inspector sees staff without it and without equivalent qualifications, they ask why. It\'s the industry\'s signal of competence. CTA Offer: We deliver Care Certificate:aligned training and can support your team\'s progression pathway.'],
                ['category' => 'general', 'question' => 'How long are training certificates valid?', 'answer' => 'Most certificates are valid for three years, after which refresher training is required. This applies to: Safeguarding (all levels); Health and Safety; Fire Safety; Infection Control; Learning Disability and Autism Awareness. First Aid certificates (EFAW/Paediatric): Valid for three years. After that, you need a refresher course (not a full retraining). Medication and moving/handling: Often require annual refreshers or competency reassessment depending on your policy and the risks in your setting. Specialist certificates (e.g., dementia, end:of:life care) vary by awarding body:typically 3:5 years. CQC Inspection Tip: Inspectors will check your training evidence. Expired certificates are a compliance gap. Use our Training Renewal Tracker template to stay ahead.'],
                ['category' => 'general', 'question' => 'What happens if training expires?', 'answer' => 'Short answer: Your staff are no longer deemed competent for those duties, and you\'re in breach of CQC regulation. Practically, this means: That team member can\'t be rostered for duties requiring that training; You have a compliance gap in your inspection file; If an incident occurs and training is expired, liability falls on the organisation; Insurance may not cover incidents involving untrained staff. It\'s not a small issue. CQC explicitly checks training records for expiry dates. If you have expired Fire Safety training but an evacuation was needed, that\'s a serious finding. CTA Prevention: We help you build a training calendar and send renewal reminders. Spaces fill up:booking 2:3 months early keeps compliance on track.'],
                ['category' => 'general', 'question' => 'Can training be completed during probation?', 'answer' => 'Yes:and it should be. In fact, mandatory training is part of a proper induction. Best practice: Probation periods should include: Weeks 1:2: Emergency First Aid, Fire Safety, Health & Safety basics, safeguarding intro; Weeks 2:4: Moving and handling, infection control, role:specific training; Weeks 4:8: Deeper competency building, shadow rounds, assessments. CQC Expectation: Providers should evidence that staff are trained before they work unsupervised. Waiting until probation ends to train them is risky. CTA Approach: We offer fast:track, intensive courses that fit probation timelines. New starters can complete core training within their first two weeks, building confidence and competence from day one.'],
                // Booking & Scheduling (8 FAQs)
                ['category' => 'booking', 'question' => 'How do I book training for my team?', 'answer' => 'Three easy ways to book with CTA: 1. Eventbrite (Individual or small groups) : Visit our Eventbrite page, browse upcoming courses, select date, number of delegates, and book online. Payment and confirmation immediate. Perfect for quick bookings. 2. Direct Phone : Call 01622 587 343. Speak to our team about your specific needs. Discuss group discounts, dates, on:site options. Fast:track booking for employers. 3. Bespoke Group Training (Best for care providers) : Email enquiries@continuitytrainingacademy.co.uk. Discuss your team\'s training plan for the year. We tailor dates, venues, and course content. Often the most cost:effective for larger teams. Pro tip: Larger bookings (8+ delegates) get group discounts and flexible scheduling.'],
                ['category' => 'booking', 'question' => 'What\'s your cancellation policy?', 'answer' => 'For individual bookings (Eventbrite): More than 14 days before the course: Full refund; 7:14 days before: 75% refund; Less than 7 days: No refund (we can sometimes offer place transfer). For group/bespoke training: Cancellations made 30+ days in advance: Full refund (minus admin); 14:30 days: 50% refund; Less than 14 days: No refund. Delegate swaps (much easier): Can\'t make the date? Swap your spot with another team member anytime, free of charge. Just let us know in advance. CTA Philosophy: We build relationships, not rigid policies. If something\'s genuinely difficult, talk to us:we\'ll usually find a solution.'],
                ['category' => 'booking', 'question' => 'Can you deliver training at our location?', 'answer' => 'Absolutely:this is one of our strengths. We deliver on:site training at: Care homes; Supported living services; Domiciliary care provider offices; Health services; Nurseries and childcare. Why on:site training works: No travel time for your team; Training tailored to your policies and environment; Scenarios using your equipment, your settings, your processes; More cost:effective for large teams (often cheaper than public courses); Flexible scheduling (evenings/weekends available). What we need: Appropriate room (tables, chairs, privacy); Access to your equipment (mannequins for First Aid, moving equipment for M&H, etc.); 2:3 weeks\' notice for booking. CTA Mobile Reach: We serve Maidstone, Kent, and the wider South East. No travel is too far:we come to you.'],
                ['category' => 'booking', 'question' => 'What are your group booking discounts?', 'answer' => 'The more you book, the more you save. We offer tiered discounts based on group size, with even better rates for annual contracts. If you commit to regular training (e.g., quarterly refreshers, new starter inductions), we offer bespoke packages with deeper discounts. Contact us to discuss your specific needs and we\'ll provide a tailored quote. CTA Reality: Bulk training is our sweet spot. You get better pricing, we build a long:term relationship, and your compliance is sorted.'],
                ['category' => 'booking', 'question' => 'How far in advance should I book?', 'answer' => 'Ideal timeline: 8:12 weeks before you need training. Here\'s why: 8:10 weeks: Guarantees your preferred date and trainer; 4:8 weeks: Still good availability, but less flexibility; 2:4 weeks: Possible, but dates may be limited; Less than 2 weeks: Only book if you\'re flexible on dates. Seasonal peaks: January, April, September, and November are busy (new year resolutions, inspection prep, team changes). Book early if you target these months. Emergency training: Sometimes you need urgent refreshers (inspection notice, staff absence, incident). Call us:we\'ll do our best to squeeze you in, but can\'t promise preferred dates. CTA Tip: Plan your year\'s training calendar now. Block out dates in January, April, September, and November. This keeps compliance on track and can offer better rates through advance planning:contact us to discuss.'],
                ['category' => 'booking', 'question' => 'Do you offer weekend or evening training?', 'answer' => 'Yes:we\'re flexible. Evening courses (after 16:30): Available by request for groups of 8+; Perfect for teams with shift patterns; Usually 1:2 nights depending on the course. Weekend courses: Saturday courses available (9 AM : 4 PM); Ideal for care homes with limited weekday staff availability; Popular for roles requiring EFAW/Paeds before employment starts. Book ahead: Weekend and evening slots fill quickly. Give us 4:6 weeks\' notice for these. Not every course suits evening/weekend: Some hands:on courses (e.g., advanced moving and handling) work better during regular hours. We\'ll advise what\'s possible when you call.'],
                ['category' => 'booking', 'question' => 'What happens if staff can\'t attend on the day?', 'answer' => 'If a delegate can\'t make it: 1. More than 7 days before: Free transfer to another date (no charge, no refund); 2. Less than 7 days: Same policy applies (we operate on goodwill, not penalties); 3. Last:minute emergency: Contact us ASAP. We\'ll try to reschedule or find a replacement from your team. Why we\'re flexible: We know care is unpredictable. Unplanned absences happen. Swapping delegates is often the easiest solution. What we ask: Just give us notice so we can update the register and ensure the right people are trained.'],
                ['category' => 'booking', 'question' => 'Can I change training dates after booking?', 'answer' => 'Yes, with flexibility depending on timing: 8+ weeks before: Free date change, no questions; 4:8 weeks before: Free change if we have availability; 2:4 weeks before: Possible, but limited slots:ask first; Less than 2 weeks: Difficult, but we\'ll try. Delegate swaps: Super easy. If Person A can\'t make 15 March but Person B can, just tell us. No charge. Group courses: If you\'ve booked a bespoke on:site course and need to reschedule, we\'ll work with your calendar. Usually 2:3 weeks\' notice keeps things smooth. Bottom line: We work around your needs. Life in care is busy:we get it.'],
                // Certification & Accreditation (8 FAQs)
                ['category' => 'certification', 'question' => 'Are your courses CQC:compliant?', 'answer' => 'Yes:100%. Every course we deliver aligns with: CQC Regulation 18 (Training requirements); CQC Inspection Framework (Key Lines of Enquiry for training and competence); Skills for Care standards (statutory and mandatory training guide); HSE requirements (for First Aid and Health & Safety courses). What this means practically: Our content covers what CQC inspectors expect to see; We provide evidence (certificates, attendance records, competency sign:offs); Our courses bridge the gap between "completed training" and "can actually do the job"; If inspectors ask, "Can you evidence competence?":we help you answer confidently. CTA Commitment: We don\'t just deliver courses. We help you build a training portfolio that stands up to CQC scrutiny.'],
                ['category' => 'certification', 'question' => 'What accreditations do you have?', 'answer' => 'CTA holds: Advantage Accreditation : Centre of the Year 2021; HSE Approval : For Emergency First Aid at Work (Level 3); CPD Accreditation : All our courses are CPD:registered; Skills for Care alignment : Our content matches their statutory and mandatory training standards; Ofsted compliance : For childcare:related courses (Paediatric First Aid). Quality Assurance: Trainers are industry:experienced (not just certified); Annual quality reviews and updates; Feedback:driven course design; Scenario:based, practical assessment. CTA Transparency: We\'re happy to share accreditation documents. Ask when you enquire.'],
                ['category' => 'certification', 'question' => 'Who accredits your certificates?', 'answer' => 'Depends on the course: First Aid (EFAW/Paediatric): HSE:approved via our accreditation body; Medication Competency: CQC:compliant, Skills for Care:aligned assessment; Safeguarding, Moving & Handling, etc.: CPD:accredited and Skills for Care:referenced; Care Certificate: Aligned with Skills for Care standards (if relevant to your pathway). What this means: Your certificates carry weight nationally. Employers, CQC, and other providers recognise them. Not a franchise course list? No. We deliver tailored, CQC:compliant training. Certificates are evidence of your competence, assessed by our expert trainers in real:world scenarios.'],
                ['category' => 'certification', 'question' => 'Are your certificates accepted nationally?', 'answer' => 'Yes. Our certificates are: Recognised by CQC; Accepted by employers across the UK; Valid for roles in care homes, domiciliary care, nursing, supported living, and specialist services; Transferable if staff move between employers. The only exception: Some roles (e.g., registered nurse, specific clinical roles) may require additional qualifications or registration. We\'ll advise on this during booking. Pro tip: Your training records (ours + any others) build a portfolio showing ongoing competence development. This is gold for CQC and for staff morale.'],
                ['category' => 'certification', 'question' => 'Do you provide digital certificates?', 'answer' => 'Yes:instant digital delivery after course completion. After your course ends: Digital certificate sent to your email same day (or next business day); PDF format:easy to print, share, or store; Includes attendee name, course name, date, trainer name, and validity period; Registrar\'s signature and CTA accreditation details. Physical copies: Available on request:contact us for details. Storing certificates: We recommend: Digital backup (secure shared drive); Staff personnel files; Training management system (if you use one). CQC Inspection: Have these ready. Inspectors will ask to see evidence. Digital + physical copies = fully prepared.'],
                ['category' => 'certification', 'question' => 'How quickly do we receive certificates?', 'answer' => 'Typically within 24 hours of course completion. For courses ending in the afternoon, digital certificates are sent by end of business. For courses ending mid:day, you usually have them by email within 2 hours. Urgent timescales? If you need evidence before a specific date (e.g., CQC inspection notice), let us know when booking. We can often expedite. No waiting games: This is one advantage of in:person training:you know immediately if staff are competent, and you get proof fast.'],
                ['category' => 'certification', 'question' => 'What if a certificate is lost?', 'answer' => 'No problem:we hold records. Email us with attendee name and course date; We\'ll provide a replacement digital certificate (free); Physical copy available if needed:contact us for details; Process usually takes 2:3 working days. Backup strategy: Keep digital copies of all certificates in a secure shared folder (Google Drive, OneDrive, etc.). This prevents loss and makes CQC inspections stress:free.'],
                ['category' => 'certification', 'question' => 'Do your courses meet Skills for Care standards?', 'answer' => 'Completely. All our content aligns with: Skills for Care Statutory and Mandatory Training Guide (August 2024 update); Care Certificate standards (15 standards for adult social care workers); Oliver McGowan Training on Learning Disability and Autism; Leadership and management frameworks (for manager:level courses). Why this matters: If you\'re applying for Workforce Development Fund (LDSS) grants, our courses are eligible; Staff trained with us have a recognised, national qualification; CQC sees Skills for Care alignment as best practice. CTA + Skills for Care: We stay updated on changes and refresh our content annually. You\'re always current.'],
                // Course-Specific Questions (6 FAQs)
                ['category' => 'course-specific', 'question' => 'What\'s included in the Care Certificate?', 'answer' => 'The Care Certificate covers 15 standards: 1. Understanding your role : Knowing your responsibilities and accountabilities; 2. Your health, safety and wellbeing : Protecting yourself while at work; 3. Duty of care : Understanding safeguarding and your legal obligations; 4. Equality and inclusion : Treating people fairly and respecting diversity; 5. Working in a person:centred way : Putting the individual at the centre; 6. Communication : Listening, speaking, and understanding diverse needs; 7. Privacy and dignity : Respecting confidentiality and personal space; 8. Fluids and nutrition : Supporting healthy eating and drinking; 9. Awareness of mental health, dementia and learning disabilities; 10. Safeguarding adults; 11. Safeguarding children; 12. Basic life support and First Aid; 13. Health and safety in care settings; 14. Handling information and keeping it confidential; 15. Infection prevention and control. Format: Mix of taught sessions, practice scenarios, and practical assessment. Time: Usually 8:10 days (depending on delivery method). CTA Approach: We deliver Care Certificate content in real:world scenarios using your setting. Staff leave not just "trained" but confident.'],
                ['category' => 'course-specific', 'question' => 'Which first aid course do childcare staff need?', 'answer' => 'Childcare staff require: Emergency Paediatric First Aid (Level 3), OFSTED:approved. This covers: CPR on infants and children; Choking (different techniques for kids); Common paediatric emergencies (febrile convulsions, allergic reactions, etc.); Recovery position for children; Assessment and reassurance in a crisis. Why separate from adult EFAW? Anatomy differs (tiny airways, different compression depths), and early childhood scenarios are unique. Both courses matter: Some roles (e.g., managers in nurseries) benefit from both Adult EFAW and Paediatric EFAW:for comprehensive coverage. CTA Delivery: One:day, practical course. Small groups, lots of mannequin practice. Staff leave confident they could handle a real paediatric emergency. Regulatory note: OFSTED expects evidence of Paediatric First Aid. It\'s not optional in childcare.'],
                ['category' => 'course-specific', 'question' => 'What\'s the difference between medication awareness and competency?', 'answer' => 'Medication Awareness: Understand what medications are, why people take them, side effects; Know how to store and handle medications safely; Understand why accurate records matter; Can explain but not administer. Medication Competency: Can administer medications correctly (oral, topical, injected:depending on role); Understands the "5 Rights" (right person, drug, dose, route, time); Can assess when to withhold medication; Assessed as competent by a trainer/assessor. When Awareness enough? Roles where staff handle meds but don\'t administer (e.g., care assistants, domiciliary support). When Competency needed? Direct administration:care workers giving tablets, nurses giving injections, anyone signing off medication administration. CQC Reality: Inspectors ask, "Who can administer medications?" Your answer must be specific and evidenced. Awareness ≠ Competency. CTA Approach: We assess actual competence. No guessing on the day:we verify you can do it safely.'],
                ['category' => 'course-specific', 'question' => 'Do I need moving & handling theory AND practical?', 'answer' => 'Yes:both are essential. They\'re not separate. Theory (classroom): Understanding biomechanics, spine health, loads; Legislation (Health & Safety at Work Act, MHOR); Risk assessment approach; Communication and consent. Practical (hands:on): Transferring using equipment (slide sheets, hoists, turntables); Manual handling techniques (where absolutely necessary); Adaptive methods for different conditions (stroke, arthritis, dementia); Real equipment your service uses. Why both? You can\'t safely move someone without understanding why you\'re doing it that way. Theory informs practice. Duration: Usually 1:2 days depending on role complexity. CTA Difference: We bring your actual equipment. Training happens in your care environment (if on:site), not a sterile classroom.'],
                ['category' => 'course-specific', 'question' => 'Is safeguarding training different for managers?', 'answer' => 'Yes:significantly. Safeguarding Level 1 (for all care workers): Recognising signs of abuse/neglect; Knowing who to report to; Understanding your role; Basic case scenarios. Safeguarding Level 2 (for supervisory/team roles): More detailed abuse types (including institutional, self:neglect); Recording and evidence gathering; Supporting victims and witnesses; Creating a safeguarding culture; Policy and procedure implementation. Safeguarding Level 3 (for managers/registered managers): Safeguarding strategy and policy development; Managing allegations; Multi:agency working (police, social care investigations); Creating systems and oversight; Legal responsibilities and accountability. CQC Inspection: Inspectors specifically check that managers have Level 2 or 3 evidence. If you don\'t, that\'s a compliance gap. CTA Delivery: Role:specific, scenario:based. Managers leave with confidence in handling real safeguarding issues.'],
                ['category' => 'course-specific', 'question' => 'What level of dementia training do we need?', 'answer' => 'Depends on your role and service type: Level 1 (Awareness:for all staff): What dementia is, types (Alzheimer\'s, vascular, etc.); Progression and symptoms; Communicating with someone with dementia; Reducing triggers for distress; Basic person:centred approaches. Who needs it? Everyone in care. Level 2 (Principles:for care and supervisory roles): Deeper understanding of dementia care; Understanding behaviour as communication; Environmental design for dementia support; Working with families; Managing complex behaviours. Who needs it? Care workers, team leaders, activity coordinators. Level 3 (Advanced:for managers, specialists): Dementia care strategy; Staff training and supervision; Complex presentations (advanced dementia, co:morbidities); End:of:life care in dementia. Who needs it? Registered managers, clinical leads. CQC Expectation: All staff should have at least Level 1. If your service specialises in dementia, Level 2+ is standard. CTA Reality: Dementia care isn\'t a checkbox. It\'s a way of thinking. We train for genuine understanding, not just certificate collection.'],
                // Payment & Funding (6 FAQs)
                ['category' => 'payment', 'question' => 'What payment methods do you accept?', 'answer' => 'We accept: Debit and credit cards (Visa, Mastercard, American Express); Bank transfer (BACS); Cheque (with advance notice). Eventbrite bookings: Payment taken online (card only) at booking. Large group/invoice:based bookings: Bank transfer often preferred. We\'ll invoice after confirming course details. No payment issues: CTA is transparent on pricing. No hidden fees, no surprise charges. Payment timing: Invoiced courses typically due within 30 days. Eventbrite bookings are immediate.'],
                ['category' => 'payment', 'question' => 'Do you offer payment plans?', 'answer' => 'For larger group training commitments, yes. If you\'re investing in an annual training plan (e.g., quarterly mandatory updates for a care home), we can discuss: Staged payments across the year; Deposit + final payment structure; Monthly training packages with set costs. Small individual courses: Fixed pricing, payment upfront (Eventbrite) or via invoice. How to arrange: Email enquiries@continuitytrainingacademy.co.uk with your training needs. We\'ll discuss options. CTA Philosophy: We partner with you for the long term. If payment structure is the barrier, let\'s solve it.'],
                ['category' => 'payment', 'question' => 'Can we use Workforce Development Fund?', 'answer' => 'Short answer: Yes, but it\'s now called LDSS (Learning and Development Support Scheme). What changed: The Workforce Development Fund (WDF) was replaced by the Adult Social Care Learning and Development Support Scheme (LDSS) from April 2025. How it works: 1. Check the eligible course list on gov.uk (our courses are listed); 2. Book and pay for training upfront; 3. Claim reimbursement from LDSS (up to the stated maximum per course). Eligible courses include: The Oliver McGowan Training (Tier 1 & 2); Leadership and management programmes; Specialist qualifications (dementia, autism, end:of:life care); Some diploma:level adult care qualifications. What\'s NOT covered: General awareness training, First Aid, moving & handling (unless part of a larger qualification). CTA Support: When you book, tell us you\'re using LDSS. We\'ll provide invoices and documentation to support your claim. Important: Check the eligible course list and reimbursement rates annually. LDSS changes quarterly.'],
                ['category' => 'payment', 'question' => 'Do you provide training invoices for our records?', 'answer' => 'Absolutely. We provide: Itemised invoices (course name, date, attendee list, cost); Attendance certificates for all participants; Training records showing competency sign:off; Digital copies of all documents. Invoice timing: Sent within 2 working days of course completion. For LDSS claims: We provide all documentation needed to submit your reimbursement claim. Compliance ready: Everything is formatted for audit and CQC inspection.'],
                ['category' => 'payment', 'question' => 'Are there discounts for multiple courses?', 'answer' => 'Yes:our group discount structure includes volume savings based on the number of delegates and courses booked. Multiple courses over a year? Even better. Annual packages offer significant savings compared to ad:hoc bookings. Contact us with your annual training plan and we\'ll provide a bespoke quote tailored to your needs. How to arrange: Email enquiries@continuitytrainingacademy.co.uk with your annual training plan. We\'ll quote a bespoke package.'],
                ['category' => 'payment', 'question' => 'Can we pay per delegate or per course?', 'answer' => 'Both options available, depending on structure: Per delegate (most common for groups): You pay for each person attending; Useful if team numbers fluctuate. Per course (block booking): You book a course for a specific date; One price, regardless of final headcount (within limits); Useful for annual planning. Flexible approach: If some staff might not attend, per:delegate pricing reduces financial risk. If you\'re certain of headcount, per:course is often cheaper. When booking: We\'ll ask about your preference and recommend the most cost:effective option. Contact us to discuss pricing for your specific needs.'],
                // Group Training & Employers (6 FAQs)
                ['category' => 'group-training', 'question' => 'What\'s the minimum group size for on:site training?', 'answer' => 'Minimum: 6 people for on:site courses (to make travel worthwhile for our trainer). Smaller groups (1:5 people): Can usually attend a public course instead, often at similar or better cost. Larger groups (8:25 people): Often more cost:effective on:site with group discounts. Contact us to discuss pricing for your group size and we\'ll recommend the most cost:effective option. Talk to us: If you have 4:5 people, email enquiries@continuitytrainingacademy.co.uk. We might combine them with another organisation\'s group or suggest public course dates.'],
                ['category' => 'group-training', 'question' => 'Can you tailor training to our policies?', 'answer' => 'Completely:that\'s what we do. When we deliver on:site training, we: Review your policies and procedures beforehand; Tailor scenarios to your care environment; Use your equipment (hoists, moving aids, medication charts); Address your specific risks and challenges; Train your staff in your context, not generic care theory. Examples: A domiciliary care agency: We focus on home safety, lone working, medication in non:clinical settings; A care home: We include your facilities, your resident needs, your escalation procedures; A nursing home: We address clinical protocols, medication administration, delegation. CQC Reality: Inspectors often ask, "Is your training tailored to your service?" Bespoke training shows yes. CTA Commitment: No off:the:shelf courses. Your training, your setting, your standards.'],
                ['category' => 'group-training', 'question' => 'Do you provide training matrices for your staff?', 'answer' => 'Yes:in multiple formats. What we provide: Role:based training matrices (care worker, team leader, manager, clinical staff, specialists); Mapped against CQC Key Lines of Enquiry; Showing mandatory, recommended, and specialist training; Frequency and refresher timelines; Eligibility criteria for each role. Formats: Excel (editable:you can adapt to your specific structure); PDF (for sharing with staff, managers); Printable or digital storage. How it works: You use the matrix to plan training for each team member, identify gaps, and schedule refreshers. It becomes your annual training plan. CTA Support: We help populate the matrix during your first year, then you own it.'],
                ['category' => 'group-training', 'question' => 'Can you track training for multiple sites?', 'answer' => 'For group training contracts, yes. If you operate multiple care homes, domiciliary services, or nursing facilities, we: Maintain separate attendance and competency records per site; Provide aggregate reports showing compliance across all locations; Help you identify organisation:wide training gaps; Support your quality assurance and CQC preparation. How it works: Provide us with a list of sites and roles; We schedule training across all locations; We send site:specific and consolidated reports; You have a complete training audit trail. Size: Works well for organisations with 2:5 locations. Larger networks? Discuss your reporting needs when you call. We may recommend a training management system.'],
                ['category' => 'group-training', 'question' => 'Do you offer annual training contracts?', 'answer' => 'Yes:our preferred model for care providers. An annual training contract typically includes: Quarterly mandatory refreshers for all staff; New starter induction training; Role:specific development (team leader, manager, clinical); Specialist courses (dementia, end:of:life, safeguarding advanced); On:site delivery where possible; Priority booking and reserved dates; Discounted rates compared to ad:hoc bookings; Annual training calendar and planning support. Cost varies depending on staff size and course mix:contact us for a tailored quote. Benefit: Compliance is sorted. You\'re not scrambling to book last:minute courses or facing expired certificates. How to arrange: 1. Email enquiries@continuitytrainingacademy.co.uk; 2. Describe your team size, roles, and compliance needs; 3. We\'ll build a bespoke annual plan and quote. CTA Reality: This is where we excel:long:term partnerships, strategic training planning, embedded quality.'],
                ['category' => 'group-training', 'question' => 'Can trainers visit multiple locations?', 'answer' => 'Absolutely:we\'re mobile. If you operate multiple sites across Kent and the South East: Our trainer can visit Site A, Site B, Site C on consecutive days; Cost:effective (one trainer deployment vs. three separate bookings); Consistent messaging across locations; Easier compliance tracking. What we need: 2:3 weeks\' notice for a multi:site tour; Clear list of dates, locations, and participant numbers; Appropriate training space at each location; Same course (easier to coordinate) or closely related courses. Example: A domiciliary care provider with 3 office locations books Emergency First Aid at all three on Mon/Tues/Weds. One trainer, one week, significant savings. Logistics: We handle travel. You just confirm locations and dates. CTA Advantage: Mobile, flexible, organised. Your training fits your geography, not the other way around.'],
            ];
            
            // Validate and sanitize FAQs before saving
            $validated_faqs = [];
            foreach ($default_faqs as $faq) {
                if (isset($faq['category']) && isset($faq['question']) && isset($faq['answer'])) {
                    $validated_faqs[] = [
                        'category' => sanitize_text_field($faq['category']),
                        'question' => sanitize_text_field($faq['question']),
                        'answer' => wp_kses_post($faq['answer']), // Sanitize for WYSIWYG field
                    ];
                }
            }
            
            if (!empty($validated_faqs)) {
                update_field('faqs', $validated_faqs, $faqs_page->ID);
                $faqs_populated++;
            }
        }
    }
    
    // Handle CQC Hub page specifically
    $cqc_page = get_page_by_path('cqc-hub');
    if (!$cqc_page) {
        $cqc_page = get_page_by_path('cqc-compliance');
    }
    if ($cqc_page) {
        $existing_faqs = get_field('faqs', $cqc_page->ID);
        if (empty($existing_faqs) || !is_array($existing_faqs) || count($existing_faqs) === 0) {
            $default_faqs = [
                [
                    'category' => 'general',
                    'question' => 'What training do care homes need for CQC compliance?',
                    'answer' => 'Care homes need mandatory training including: Safeguarding, Health & Safety, Fire Safety, Manual Handling, First Aid, Food Hygiene, Infection Control, and Medication Management. All staff must complete the Care Certificate within 12 weeks of starting.',
                ],
                [
                    'category' => 'general',
                    'question' => 'How do I prepare staff for a CQC inspection?',
                    'answer' => 'Ensure all staff have completed mandatory training, training records are up to date, policies and procedures are current, and staff understand their roles. Our CQC Inspection Preparation course covers everything you need to know.',
                ],
                [
                    'category' => 'training',
                    'question' => 'How often does CQC training need to be refreshed?',
                    'answer' => 'Most CQC mandatory training should be refreshed annually. First Aid certificates typically last 3 years. Safeguarding training should be refreshed every 2-3 years. Check specific course requirements for exact refresh periods.',
                ],
                [
                    'category' => 'training',
                    'question' => 'Is online training accepted by CQC?',
                    'answer' => 'CQC accepts a mix of online and face-to-face training, but some topics (like practical first aid) require hands-on training. Our face-to-face courses ensure all practical elements are covered to CQC standards.',
                ],
            ];
            
            // Validate and sanitize FAQs before saving
            $validated_faqs = [];
            foreach ($default_faqs as $faq) {
                if (isset($faq['category']) && isset($faq['question']) && isset($faq['answer'])) {
                    $validated_faqs[] = [
                        'category' => sanitize_text_field($faq['category']),
                        'question' => sanitize_text_field($faq['question']),
                        'answer' => wp_kses_post($faq['answer']), // Sanitize for WYSIWYG field
                    ];
                }
            }
            
            if (!empty($validated_faqs)) {
                update_field('faqs', $validated_faqs, $cqc_page->ID);
                $faqs_populated++;
            }
        }
    }
    
    // Store count for admin notice
    if ($faqs_populated > 0) {
        update_option('cta_faqs_populated_count', $faqs_populated);
    }
}
add_action('after_switch_theme', 'cta_populate_faqs_on_activation', 25);

/**
 * Helper function to safely get ACF field with fallback
 */
function cta_get_field($field_name, $post_id = false, $default = '') {
    if (!function_exists('get_field')) {
        return $default;
    }
    
    $value = get_field($field_name, $post_id);
    return $value !== null && $value !== '' ? $value : $default;
}

/**
 * Use custom excerpt field if available, otherwise fall back to WordPress excerpt
 */
function cta_get_custom_excerpt($post_id = false) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $post_type = get_post_type($post_id);
    
    // Check for custom excerpt fields
    if ($post_type === 'course') {
        $custom_excerpt = cta_get_field('course_seo_excerpt', $post_id, '');
        if (!empty($custom_excerpt)) {
            return $custom_excerpt;
        }
    } elseif ($post_type === 'course_event') {
        $custom_excerpt = cta_get_field('event_seo_excerpt', $post_id, '');
        if (!empty($custom_excerpt)) {
            return $custom_excerpt;
        }
    }
    
    // Fall back to WordPress excerpt
    $excerpt = get_the_excerpt($post_id);
    return !empty($excerpt) ? $excerpt : '';
}

/**
 * Filter get_the_excerpt to use custom excerpt fields when available
 */
function cta_filter_excerpt($excerpt, $post = null) {
    if (!$post) {
        $post = get_post();
    }
    
    if (!$post) {
        return $excerpt;
    }
    
    $post_type = get_post_type($post->ID);
    
    // Check for custom excerpt fields
    if ($post_type === 'course') {
        $custom_excerpt = cta_get_field('course_seo_excerpt', $post->ID, '');
        if (!empty($custom_excerpt)) {
            return $custom_excerpt;
        }
    } elseif ($post_type === 'course_event') {
        $custom_excerpt = cta_get_field('event_seo_excerpt', $post->ID, '');
        if (!empty($custom_excerpt)) {
            return $custom_excerpt;
        }
    }
    
    return $excerpt;
}
add_filter('get_the_excerpt', 'cta_filter_excerpt', 10, 2);

/**
 * Helper function to get learning outcomes as array
 * Parses the textarea (one outcome per line) into an array
 */
function cta_get_outcomes($post_id = false) {
    $outcomes_text = cta_get_field('course_outcomes', $post_id, '');
    
    if (empty($outcomes_text)) {
        return [];
    }
    
    // Split by newlines and filter empty lines
    $lines = preg_split('/\r\n|\r|\n/', $outcomes_text);
    $outcomes = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $outcomes[] = ['outcome_text' => $line];
        }
    }
    
    return $outcomes;
}

/**
 * Helper function to get stats as array
 * Parses the textarea (format: "Number | Label" per line) into an array
 */
function cta_get_stats($post_id = false) {
    $stats_text = cta_get_field('about_stats', $post_id, '');
    
    if (empty($stats_text)) {
        return [];
    }
    
    // Split by newlines and filter empty lines
    $lines = preg_split('/\r\n|\r|\n/', $stats_text);
    $stats = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && strpos($line, '|') !== false) {
            $parts = explode('|', $line, 2);
            $stats[] = [
                'number' => trim($parts[0]),
                'label' => trim($parts[1] ?? ''),
            ];
        }
    }
    
    return $stats;
}

/**
 * Legacy compatibility: cta_get_repeater now works with textarea format
 */
function cta_get_repeater($field_name, $post_id = false) {
    // Handle outcomes field
    if ($field_name === 'course_outcomes') {
        return cta_get_outcomes($post_id);
    }
    
    // Handle stats field
    if ($field_name === 'about_stats') {
        return cta_get_stats($post_id);
    }
    
    // For any other field, try to get as array
    if (!function_exists('get_field')) {
        return [];
    }
    
    $value = get_field($field_name, $post_id);
    return is_array($value) ? $value : [];
}

/**
 * Add Eventbrite-specific fields to course_event ACF
 */
function cta_add_eventbrite_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    acf_add_local_field_group([
        'key' => 'group_eventbrite_fields',
        'title' => 'Eventbrite Settings',
        'fields' => [
            [
                'key' => 'field_eventbrite_description',
                'label' => 'Eventbrite Description',
                'name' => 'eventbrite_description',
                'type' => 'wysiwyg',
                'instructions' => 'HTML-formatted description shown on Eventbrite. Leave empty to auto-generate with AI. Click "Regenerate" to create a new AI description.',
                'tabs' => 'visual',
                'toolbar' => 'full',
                'media_upload' => 0,
                'delay' => 0,
                'default_value' => '',
            ],
            [
                'key' => 'field_eventbrite_summary',
                'label' => 'Eventbrite Summary (140 chars)',
                'name' => 'eventbrite_summary',
                'type' => 'text',
                'instructions' => 'Short summary for Eventbrite search/discovery. Auto-generated if empty.',
                'maxlength' => 140,
            ],
            [
                'key' => 'field_generate_eventbrite_summary',
                'label' => '',
                'name' => 'generate_eventbrite_summary',
                'type' => 'message',
                'message' => '<button type="button" id="cta-generate-eventbrite-summary" class="button button-small" style="margin-top: 6px;">✨ Generate with AI</button> <span id="cta-generate-summary-status" style="margin-left: 10px; font-size: 12px;"></span>',
            ],
            [
                'key' => 'field_eventbrite_custom_name',
                'label' => 'Custom Eventbrite Event Name (75 chars max)',
                'name' => 'eventbrite_custom_name',
                'type' => 'text',
                'instructions' => 'Optional: Override auto-generated event name for Eventbrite. Maximum 75 characters (2026 best practice). Format: [Event Type] + [Descriptor] + [Location].',
                'placeholder' => 'Leave empty to use auto-generated name',
                'maxlength' => 75,
            ],
            [
                'key' => 'field_generate_eventbrite_custom_name',
                'label' => '',
                'name' => 'generate_eventbrite_custom_name',
                'type' => 'message',
                'message' => '<button type="button" id="cta-generate-eventbrite-name" class="button button-small" style="margin-top: 6px;">✨ Generate with AI</button> <span id="cta-generate-name-status" style="margin-left: 10px; font-size: 12px;"></span>',
            ],
            [
                'key' => 'field_regenerate_eventbrite_description',
                'label' => 'Regenerate Description',
                'name' => 'regenerate_eventbrite_description',
                'type' => 'message',
                'message' => '<button type="button" id="cta-regenerate-eventbrite-desc" class="button button-secondary">🔄 Regenerate AI Description</button> <span id="cta-regenerate-status" style="margin-left: 10px;"></span>',
            ],
            [
                'key' => 'field_eventbrite_faqs',
                'label' => 'Eventbrite FAQ Suggestions',
                'name' => 'eventbrite_faqs',
                'type' => 'repeater',
                'instructions' => 'FAQ suggestions for Eventbrite listing (+8% search visibility boost). Click "Generate with AI" to auto-generate.',
                'layout' => 'block',
                'button_label' => 'Add FAQ',
                'min' => 0,
                'sub_fields' => [
                    [
                        'key' => 'field_eventbrite_faq_question',
                        'label' => 'Question',
                        'name' => 'question',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_eventbrite_faq_answer',
                        'label' => 'Answer',
                        'name' => 'answer',
                        'type' => 'wysiwyg',
                        'instructions' => 'Write a clear, concise answer for Eventbrite listing.',
                        'tabs' => 'visual',
                        'toolbar' => 'basic',
                        'media_upload' => 0,
                        'delay' => 0,
                        'required' => 1,
                    ],
                ],
            ],
            [
                'key' => 'field_generate_eventbrite_faqs',
                'label' => '',
                'name' => 'generate_eventbrite_faqs',
                'type' => 'message',
                'message' => '<button type="button" id="cta-generate-eventbrite-faqs" class="button button-small" style="margin-top: 6px;">✨ Generate FAQs with AI</button> <span id="cta-generate-faqs-status" style="margin-left: 10px; font-size: 12px;"></span>',
            ],
            [
                'key' => 'field_eventbrite_tag_suggestions',
                'label' => 'Eventbrite Tag Suggestions',
                'name' => 'eventbrite_tag_suggestions',
                'type' => 'textarea',
                'instructions' => 'AI-generated tag suggestions for Eventbrite. Copy these into Eventbrite\'s tag field when creating/editing the event.',
                'rows' => 4,
                'readonly' => 0,
            ],
            [
                'key' => 'field_generate_eventbrite_tags',
                'label' => '',
                'name' => 'generate_eventbrite_tags',
                'type' => 'message',
                'message' => '<button type="button" id="cta-generate-eventbrite-tags" class="button button-small" style="margin-top: 6px;">✨ Generate Tags with AI</button> <span id="cta-generate-tags-status" style="margin-left: 10px; font-size: 12px;"></span>',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'course_event',
                ],
            ],
        ],
        'menu_order' => 2,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);
}
add_action('acf/init', 'cta_add_eventbrite_acf_fields');

/**
 * Add ACF fields for FAQ post type
 */
function cta_add_faq_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_faq_content',
        'title' => 'FAQ Content',
        'fields' => [
            [
                'key' => 'field_faq_answer',
                'label' => 'Answer',
                'name' => 'faq_answer',
                'type' => 'wysiwyg',
                'instructions' => 'The detailed answer to this FAQ. Use formatting tools to add emphasis, lists, or links.',
                'required' => 1,
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 0,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'faq',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);
}
add_action('acf/init', 'cta_add_faq_acf_fields');

/**
 * Add ACF fields for FAQ Category taxonomy (icon)
 */
function cta_add_faq_category_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_faq_category_icon',
        'title' => 'FAQ Category Icon',
        'fields' => [
            [
                'key' => 'field_faq_category_icon',
                'label' => 'Category Icon (SVG)',
                'name' => 'faq_category_icon',
                'type' => 'textarea',
                'instructions' => 'Paste your SVG code here. This icon will appear beside the category heading and in the filter button. Example: <svg>...</svg>',
                'required' => 0,
                'rows' => 5,
                'placeholder' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path d="..."/></svg>',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'taxonomy',
                    'operator' => '==',
                    'value' => 'faq_category',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);
}
add_action('acf/init', 'cta_add_faq_category_acf_fields');
