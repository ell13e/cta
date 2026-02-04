<?php
/**
 * Auto-Populate Articles from Government Resources
 * 
 * Creates blog articles automatically from government guidance and resources
 * that are relevant to care providers and CQC compliance.
 *
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Get government resources that should be converted to articles
 */
function cta_get_government_resources_for_articles() {
    return [
        [
            'title' => 'Infection Prevention and Control: Understanding the Code of Practice',
            'category' => 'cqc-compliance',
            'intro' => 'The Health and Social Care Act 2008 code of practice on the prevention and control of infections is a fundamental standard that all registered care providers must meet. CQC inspectors check compliance with this code during inspections, making it essential for care providers to understand and implement its requirements.',
            'sections' => [
                [
                    'title' => 'What is the Code of Practice?',
                    'content' => '<p>The code of practice sets out requirements for registered health and social care providers to prevent and control infections. It covers areas including:</p>
<ul>
<li>Infection prevention and control policies and procedures</li>
<li>Staff training and competency</li>
<li>Environmental cleanliness and hygiene</li>
<li>Waste management</li>
<li>Personal protective equipment (PPE) use</li>
<li>Outbreak management</li>
</ul>
<p>This is a fundamental standard under the Health and Social Care Act 2008, meaning CQC can take enforcement action if providers fail to meet these requirements.</p>'
                ],
                [
                    'title' => 'Key Requirements for Care Providers',
                    'content' => '<p>To comply with the code of practice, care providers must:</p>
<ul>
<li>Have robust infection prevention and control policies in place</li>
<li>Ensure all staff receive appropriate training in infection control</li>
<li>Maintain clean and hygienic environments</li>
<li>Follow proper hand hygiene protocols</li>
<li>Use PPE correctly and appropriately</li>
<li>Have systems for identifying and managing outbreaks</li>
<li>Keep accurate records of training and incidents</li>
</ul>
<p>Regular training updates are essential, as infection control practices evolve and new guidance is issued.</p>'
                ],
                [
                    'title' => 'How CQC Inspects Infection Control',
                    'content' => '<p>During inspections, CQC will check:</p>
<ul>
<li>Whether infection control policies are in place and being followed</li>
<li>Staff knowledge and understanding of infection control procedures</li>
<li>The cleanliness and hygiene of the environment</li>
<li>Training records for infection control</li>
<li>Evidence of ongoing compliance and improvement</li>
</ul>
<p>Providers should be able to demonstrate that infection control is embedded in their service culture, not just a tick-box exercise.</p>'
                ],
                [
                    'title' => 'Training Requirements',
                    'content' => '<p>All staff who provide care must receive infection prevention and control training. This should include:</p>
<ul>
<li>Basic infection control principles</li>
<li>Hand hygiene techniques</li>
<li>PPE use and disposal</li>
<li>Cleaning and disinfection procedures</li>
<li>Waste management</li>
<li>Outbreak recognition and response</li>
</ul>
<p>Training should be refreshed regularly, typically annually, and updated when new guidance is issued or after incidents.</p>'
                ]
            ],
            'external_link' => 'https://www.gov.uk/government/publications/the-health-and-social-care-act-2008-code-of-practice-on-the-prevention-and-control-of-infections-and-related-guidance/health-and-social-care-act-2008-code-of-practice-on-the-prevention-and-control-of-infections-and-related-guidance',
            'link_text' => 'Read the full code of practice',
            'tags' => ['infection control', 'CQC compliance', 'fundamental standards', 'health and safety']
        ],
        [
            'title' => 'Reducing Restraint and Restrictive Intervention in Care Settings',
            'category' => 'cqc-compliance',
            'intro' => 'The fundamental standards require that people must not suffer unnecessary or disproportionate restraint. The Department of Health guidance on reducing the need for restraint and restrictive intervention provides essential best practice for care providers. This is a key safeguarding requirement that CQC inspectors assess during inspections.',
            'sections' => [
                [
                    'title' => 'Understanding Restraint and Restrictive Intervention',
                    'content' => '<p>Restraint and restrictive intervention refers to any action that restricts a person\'s movement, liberty, or freedom to act independently. This includes:</p>
<ul>
<li>Physical restraint</li>
<li>Chemical restraint (medication used to control behaviour)</li>
<li>Environmental restraint (locked doors, barriers)</li>
<li>Seclusion or isolation</li>
</ul>
<p>The fundamental standards are clear: restraint should only be used when absolutely necessary to prevent harm, and must be proportionate and in the person\'s best interests.</p>'
                ],
                [
                    'title' => 'Legal and Regulatory Framework',
                    'content' => '<p>Care providers must comply with:</p>
<ul>
<li><strong>Health and Social Care Act 2008 (Regulated Activities) Regulations 2014:</strong> Requires that people are protected from abuse and improper treatment, including unnecessary restraint</li>
<li><strong>Mental Capacity Act 2005:</strong> Sets out when and how decisions can be made for people who lack capacity</li>
<li><strong>Human Rights Act 1998:</strong> Protects the right to liberty and freedom from degrading treatment</li>
</ul>
<p>CQC will take enforcement action if providers fail to meet these requirements or use restraint inappropriately.</p>'
                ],
                [
                    'title' => 'Best Practice: Prevention First',
                    'content' => '<p>The guidance emphasises prevention over intervention. Best practice includes:</p>
<ul>
<li>Understanding the person\'s needs, preferences, and triggers</li>
<li>Creating supportive environments that reduce the need for restraint</li>
<li>Training staff in de-escalation techniques</li>
<li>Using positive behaviour support approaches</li>
<li>Involving the person and their family in care planning</li>
<li>Regular review of care plans and interventions</li>
</ul>
<p>When restraint is necessary, it must be the least restrictive option, used for the shortest time possible, and properly documented.</p>'
                ],
                [
                    'title' => 'Training and Competency',
                    'content' => '<p>Staff must be trained in:</p>
<ul>
<li>Understanding when restraint may be necessary and when it is not</li>
<li>De-escalation techniques</li>
<li>Positive behaviour support</li>
<li>Legal and ethical considerations</li>
<li>Documentation and reporting requirements</li>
<li>Physical intervention techniques (if required, by qualified trainers only)</li>
</ul>
<p>Training should be refreshed regularly, and staff competency should be assessed. All incidents involving restraint must be documented, reviewed, and reported appropriately.</p>'
                ]
            ],
            'external_link' => 'https://assets.publishing.service.gov.uk/media/5d1387e240f0b6350e1ab567/reducing-the-need-for-restraint-and-restrictive-intervention.pdf',
            'link_text' => 'Read the full guidance document',
            'tags' => ['restraint', 'safeguarding', 'CQC compliance', 'fundamental standards', 'positive behaviour support']
        ],
        [
            'title' => 'Oliver McGowan Mandatory Training: What Care Providers Need to Know',
            'category' => 'cqc-compliance',
            'intro' => 'The Oliver McGowan Mandatory Training on Learning Disability and Autism will become a legal requirement for all health and social care staff in Q2 2026. The code of practice became final on 6 September 2025, making this training essential for all care providers. Understanding the requirements now will help you prepare for compliance.',
            'sections' => [
                [
                    'title' => 'What is Oliver McGowan Training?',
                    'content' => '<p>The Oliver McGowan Mandatory Training on Learning Disability and Autism is the government\'s preferred and recommended training for health and social care staff. Named after Oliver McGowan, a young autistic teenager with a mild learning disability who died in 2016, this training ensures staff have the right skills and knowledge to provide safe, compassionate care.</p>
<p>The training is co-delivered by experts by experience (people with learning disabilities or autistic people), making it unique in its approach to understanding the needs of people with learning disabilities and autism.</p>'
                ],
                [
                    'title' => 'Training Structure: Tier 1 and Tier 2',
                    'content' => '<p>The training is delivered in two tiers:</p>
<ul>
<li><strong>Tier 1 (General Awareness):</strong> For staff who require general awareness of support needs. Includes elearning (1.5 hours) plus a 1-hour online interactive session co-delivered by experts by experience.</li>
<li><strong>Tier 2 (Direct Care):</strong> For staff who provide care and support. Includes the same elearning plus a 1-day face-to-face training session co-delivered by experts by experience.</li>
</ul>
<p>Everyone must complete the elearning regardless of tier. Tier 2 training includes Tier 1 material, so staff only need to complete one tier based on their role.</p>'
                ],
                [
                    'title' => 'Who Needs This Training?',
                    'content' => '<p>All registered health and social care providers must ensure their staff receive appropriate training:</p>
<ul>
<li><strong>Tier 1:</strong> Staff who need general awareness (e.g., finance staff, administrators without patient contact)</li>
<li><strong>Tier 2:</strong> Staff who provide direct care or support (e.g., care workers, nurses, GPs, reception staff with patient contact, managers making service decisions)</li>
</ul>
<p>Employers must assess which tier each staff member needs based on their role and level of contact with people with learning disabilities or autistic people.</p>'
                ],
                [
                    'title' => 'Compliance Requirements',
                    'content' => '<p>The code of practice sets out standards for training and guidance for meeting those standards. CQC will assess whether registered providers are meeting this requirement during inspections.</p>
<p>Key requirements:</p>
<ul>
<li>Training must be standardised and meet the core capabilities frameworks</li>
<li>Training must be co-delivered by experts by experience</li>
<li>Providers must maintain records of staff completion</li>
<li>CQC can take enforcement action if providers fail to meet the requirement</li>
</ul>
<p>The training is available via e-Learning for Healthcare, and providers should start planning now to ensure all staff are trained before the Q2 2026 deadline.</p>'
                ]
            ],
            'external_link' => 'https://www.gov.uk/government/publications/oliver-mcgowan-code-of-practice/the-oliver-mcgowan-draft-code-of-practice-on-statutory-learning-disability-and-autism-training',
            'link_text' => 'Read the code of practice',
            'tags' => ['Oliver McGowan', 'learning disability', 'autism', 'mandatory training', 'CQC compliance']
        ],
        [
            'title' => 'Down Syndrome Act 2022: Guidance for Care Providers',
            'category' => 'cqc-compliance',
            'intro' => 'The Down Syndrome Act 2022 requires local authorities and NHS bodies to have regard to guidance when providing services to people with Down syndrome. While the Act applies directly to statutory bodies, care providers should understand its principles to ensure they provide appropriate support.',
            'sections' => [
                [
                    'title' => 'What is the Down Syndrome Act 2022?',
                    'content' => '<p>The Down Syndrome Act 2022 is legislation that requires local authorities and NHS bodies in England to have regard to guidance when providing services to people with Down syndrome. The Act aims to improve outcomes for people with Down syndrome by ensuring their specific needs are considered in service planning and delivery.</p>
<p>While the Act applies directly to statutory bodies, care providers should be aware of its principles and ensure their services are appropriate for people with Down syndrome.</p>'
                ],
                [
                    'title' => 'Key Principles for Care Providers',
                    'content' => '<p>Care providers should consider:</p>
<ul>
<li>Understanding the specific health and support needs of people with Down syndrome</li>
<li>Providing person-centred care that recognises individual strengths and needs</li>
<li>Ensuring staff have appropriate training and understanding</li>
<li>Working in partnership with families and other professionals</li>
<li>Supporting people with Down syndrome to live independently and participate in their communities</li>
</ul>
<p>This aligns with CQC\'s fundamental standards around person-centred care and meeting people\'s needs.</p>'
                ],
                [
                    'title' => 'Training and Competency',
                    'content' => '<p>Staff supporting people with Down syndrome should:</p>
<ul>
<li>Understand the specific health conditions associated with Down syndrome</li>
<li>Recognise the importance of early intervention and support</li>
<li>Be trained in communication techniques appropriate for people with Down syndrome</li>
<li>Understand the importance of supporting independence and choice</li>
<li>Work collaboratively with families and other professionals</li>
</ul>
<p>Training should be ongoing and updated as understanding of best practice evolves.</p>'
                ],
                [
                    'title' => 'CQC Compliance',
                    'content' => '<p>While the Down Syndrome Act applies to statutory bodies, care providers should ensure their services meet CQC\'s fundamental standards, including:</p>
<ul>
<li>Person-centred care</li>
<li>Dignity and respect</li>
<li>Consent and mental capacity</li>
<li>Safeguarding from abuse</li>
<li>Meeting nutritional and hydration needs</li>
</ul>
<p>Providers should be able to demonstrate that they understand and meet the specific needs of people with Down syndrome in their care.</p>'
                ]
            ],
            'external_link' => 'https://www.gov.uk/government/consultations/down-syndrome-act-2022-draft-statutory-guidance-easy-read',
            'link_text' => 'Read the draft statutory guidance',
            'tags' => ['Down syndrome', 'person-centred care', 'CQC compliance', 'learning disability']
        ]
    ];
}

/**
 * Create articles from government resources
 */
function cta_create_articles_from_resources() {
    if (!current_user_can('edit_posts')) {
        wp_die('You do not have permission to perform this action.');
    }

    $resources = cta_get_government_resources_for_articles();
    $created = 0;
    $skipped = 0;
    $errors = [];

    foreach ($resources as $resource) {
        // Check if article already exists
        $existing = get_page_by_title($resource['title'], OBJECT, 'post');
        
        if ($existing) {
            $skipped++;
            continue;
        }

        // Get or create category
        $category_id = null;
        if (!empty($resource['category'])) {
            $category = get_term_by('slug', $resource['category'], 'category');
            if (!$category) {
                $term_result = wp_insert_term(
                    ucwords(str_replace('-', ' ', $resource['category'])),
                    'category',
                    ['slug' => $resource['category']]
                );
                if (!is_wp_error($term_result)) {
                    $category_id = $term_result['term_id'];
                }
            } else {
                $category_id = $category->term_id;
            }
        }

        // Create the post
        $post_data = [
            'post_title' => $resource['title'],
            'post_content' => '', // Content will be in ACF fields
            'post_excerpt' => wp_trim_words(strip_tags($resource['intro']), 30, '...'),
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => get_current_user_id() ?: 1,
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            $errors[] = [
                'title' => $resource['title'],
                'error' => $post_id->get_error_message()
            ];
            continue;
        }

        // Set category
        if ($category_id) {
            wp_set_object_terms($post_id, [$category_id], 'category');
        }

        // Set ACF fields
        if (function_exists('update_field')) {
            // Set introduction
            if (!empty($resource['intro'])) {
                update_field('news_intro', $resource['intro'], $post_id);
            }

            // Set sections
            if (!empty($resource['sections']) && is_array($resource['sections'])) {
                $sections = [];
                foreach ($resource['sections'] as $section) {
                    $sections[] = [
                        'section_title' => $section['title'],
                        'section_content' => $section['content']
                    ];
                }
                update_field('news_sections', $sections, $post_id);
            }

            // Add external link section if provided
            if (!empty($resource['external_link'])) {
                $link_section = [
                    'section_title' => 'Official Resources',
                    'section_content' => '<p>For the full official guidance and detailed information, please refer to the government resources:</p>
<p><a href="' . esc_url($resource['external_link']) . '" target="_blank" rel="noopener noreferrer" class="btn btn-primary">' . esc_html($resource['link_text'] ?: 'Read the official guidance') . ' <i class="fas fa-external-link-alt" aria-hidden="true"></i></a></p>'
                ];
                
                $existing_sections = get_field('news_sections', $post_id) ?: [];
                $existing_sections[] = $link_section;
                update_field('news_sections', $existing_sections, $post_id);
            }
        }

        // Set tags if provided
        if (!empty($resource['tags']) && is_array($resource['tags'])) {
            wp_set_object_terms($post_id, $resource['tags'], 'post_tag');
        }

        $created++;
    }

    return [
        'created' => $created,
        'skipped' => $skipped,
        'errors' => $errors
    ];
}

/**
 * Add admin page for auto-populating articles
 * NOTE: Automatic menu removed - use manual trigger in Import CTA Data page
 * Function kept for manual use only
 */
function cta_add_auto_populate_articles_page() {
    // Automatic menu disabled - function kept for manual use only
    return;
}

/**
 * Admin page for auto-populating articles
 */
function cta_auto_populate_articles_page() {
    if (isset($_POST['cta_create_articles']) && check_admin_referer('cta_create_articles')) {
        $result = cta_create_articles_from_resources();
        
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo sprintf(
            'Created %d article(s), skipped %d existing article(s).',
            $result['created'],
            $result['skipped']
        );
        echo '</p></div>';

        if (!empty($result['errors'])) {
            echo '<div class="notice notice-error"><p><strong>Errors:</strong></p><ul>';
            foreach ($result['errors'] as $error) {
                echo '<li>' . esc_html($error['title']) . ': ' . esc_html($error['error']) . '</li>';
            }
            echo '</ul></div>';
        }
    }

    $resources = cta_get_government_resources_for_articles();
    $existing_count = 0;
    foreach ($resources as $resource) {
        if (get_page_by_title($resource['title'], OBJECT, 'post')) {
            $existing_count++;
        }
    }
    ?>
    <div class="wrap">
        <h1>Auto-Populate Articles from Government Resources</h1>
        <p>This tool will create blog articles from government guidance and resources relevant to care providers and CQC compliance.</p>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>Available Resources</h2>
            <p>Found <strong><?php echo count($resources); ?></strong> government resources ready to be converted to articles.</p>
            <p><?php echo $existing_count; ?> article(s) already exist and will be skipped.</p>
            
            <ul style="list-style: disc; margin-left: 20px; margin-top: 15px;">
                <?php foreach ($resources as $resource) : 
                    $exists = get_page_by_title($resource['title'], OBJECT, 'post');
                ?>
                <li>
                    <strong><?php echo esc_html($resource['title']); ?></strong>
                    <?php if ($exists) : ?>
                        <span style="color: #666;">(Already exists)</span>
                    <?php endif; ?>
                    <br>
                    <small style="color: #666;">Category: <?php echo esc_html(ucwords(str_replace('-', ' ', $resource['category']))); ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <form method="post" style="margin-top: 30px;">
            <?php wp_nonce_field('cta_create_articles'); ?>
            <p>
                <button type="submit" name="cta_create_articles" class="button button-primary button-large">
                    Create Articles
                </button>
            </p>
            <p class="description">
                This will create new blog articles from the government resources listed above. 
                Articles that already exist will be skipped to prevent duplicates.
            </p>
        </form>
    </div>
    <?php
}
