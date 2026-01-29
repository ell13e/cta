<?php
/**
 * Phase 1 Blog Posts Creation Helper
 * 
 * Creates WordPress posts from Phase 1 blog articles
 * Can be run via WP-CLI or admin page
 *
 * @package CTA_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create or get WordPress category
 */
function cta_get_or_create_category($name, $slug = '') {
    if (empty($slug)) {
        $slug = sanitize_title($name);
    }
    
    $category = get_category_by_slug($slug);
    
    if (!$category) {
        $category_data = wp_insert_category([
            'cat_name' => $name,
            'category_nicename' => $slug,
        ]);
        
        if (is_wp_error($category_data)) {
            return false;
        }
        
        $category = get_category($category_data);
    }
    
    return $category ? $category->term_id : false;
}

/**
 * Convert markdown-style content to WordPress-friendly HTML
 */
function cta_convert_markdown_to_html($content) {
    // Convert markdown headings
    $content = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $content);
    $content = preg_replace('/^#### (.*)$/m', '<h4>$1</h4>', $content);
    
    // Convert markdown bold
    $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
    
    // Convert markdown italic
    $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
    
    // Convert markdown lists (basic)
    $content = preg_replace('/^- (.*)$/m', '<li>$1</li>', $content);
    $content = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $content);
    
    // Convert markdown links
    $content = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2">$1</a>', $content);
    
    // Convert horizontal rules
    $content = str_replace('---', '<hr>', $content);
    
    // Split into paragraphs (double line breaks)
    $paragraphs = preg_split('/\n\s*\n/', $content);
    $html = '';
    
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if (empty($para)) {
            continue;
        }
        
        // Skip if already an HTML tag
        if (preg_match('/^<[h|u|o|l|d]|^<hr/', $para)) {
            $html .= $para . "\n\n";
        } else {
            $html .= '<p>' . $para . '</p>' . "\n\n";
        }
    }
    
    return trim($html);
}

/**
 * Extract article content from markdown file
 */
function cta_extract_article_from_markdown($markdown_file, $article_number) {
    if (!file_exists($markdown_file)) {
        return '';
    }
    
    $content = file_get_contents($markdown_file);
    
    // Find article start marker
    $article_start = '## Article ' . $article_number . ':';
    $start_pos = strpos($content, $article_start);
    
    if ($start_pos === false) {
        return '';
    }
    
    // Extract from article start
    $article_content = substr($content, $start_pos);
    
    // Find next article or end of file
    $next_article_pattern = '/^## Article \d+:/m';
    if (preg_match($next_article_pattern, substr($article_content, strlen($article_start)), $matches, PREG_OFFSET_CAPTURE)) {
        $article_content = substr($article_content, 0, strlen($article_start) + $matches[0][1]);
    }
    
    // Remove the article header line
    $article_content = preg_replace('/^## Article \d+:.*?\n/s', '', $article_content, 1);
    
    // Remove meta information lines
    $article_content = preg_replace('/^\*\*Meta Title:\*\*.*?\n/s', '', $article_content);
    $article_content = preg_replace('/^\*\*Meta Description:\*\*.*?\n/s', '', $article_content);
    $article_content = preg_replace('/^\*\*Keywords:\*\*.*?\n/s', '', $article_content);
    $article_content = preg_replace('/^\*\*Word Count:\*\*.*?\n/s', '', $article_content);
    
    // Remove trailing separators
    $article_content = preg_replace('/^---\s*$/m', '', $article_content);
    $article_content = trim($article_content);
    
    return $article_content;
}

/**
 * Create Phase 1 blog posts
 */
function cta_create_phase1_blog_posts() {
    // Ensure categories exist
    $categories = [
        'regulatory-updates' => 'Regulatory Updates',
        'training-tips' => 'Training Tips & Best Practices',
        'cqc-compliance' => 'CQC Compliance',
        'case-studies' => 'Case Studies & Success Stories',
        'course-announcements' => 'Course Announcements',
    ];
    
    $category_ids = [];
    foreach ($categories as $slug => $name) {
        $cat_id = cta_get_or_create_category($name, $slug);
        if ($cat_id) {
            $category_ids[$slug] = $cat_id;
        }
    }
    
    // Markdown source file (can live in the theme during deployment, or in a repo/docs folder during development).
    $theme_dir = get_stylesheet_directory();
    $markdown_candidates = [
        // Theme-packaged location (included on theme upload)
        trailingslashit($theme_dir) . 'docs/legacy-wordpress-md/CTA-Phase1-Blog-Articles.md',
        // Original data/markdown location (restored for backward compatibility)
        trailingslashit($theme_dir) . 'data/markdown/CTA-Phase1-Blog-Articles.md',
        // Legacy/in-theme location
        trailingslashit($theme_dir) . 'CTA-Phase1-Blog-Articles.md',
        // Legacy wordpress-md location (for backward compatibility)
        trailingslashit($theme_dir) . 'wordpress-md/CTA-Phase1-Blog-Articles.md',
        // Sensible WordPress content location (e.g. wp-content/wordpress-md/)
        trailingslashit(WP_CONTENT_DIR) . 'wordpress-md/CTA-Phase1-Blog-Articles.md',
        // Repo root layout (this project has /wordpress-theme and /wordpress-md side-by-side)
        trailingslashit(dirname($theme_dir)) . 'wordpress-md/CTA-Phase1-Blog-Articles.md',
    ];

    $markdown_file = '';
    foreach ($markdown_candidates as $candidate) {
        if (is_readable($candidate)) {
            $markdown_file = $candidate;
            break;
        }
    }

    if (empty($markdown_file)) {
        return [
            'created' => [],
            'skipped' => [],
            'errors' => [[
                'title' => 'CTA-Phase1-Blog-Articles.md',
                'error' => 'Markdown source file not found. Place it in `docs/legacy-wordpress-md/`, theme root, or `wp-content/wordpress-md/`.',
            ]],
        ];
    }
    
    // Blog articles data
    $articles = [
        // Complete Articles (4)
        [
            'title' => 'What CQC Inspectors Look for in Training Records',
            'meta_title' => 'What CQC Inspectors Look for in Training Records | CTA',
            'meta_description' => 'CQC training compliance checklist. Learn what inspectors examine during care quality inspections.',
            'keywords' => 'CQC training records, CQC inspection, mandatory training, care compliance',
            'category' => 'cqc-compliance',
            'status' => 'publish',
            'article_number' => 1,
        ],
        [
            'title' => 'Care Certificate Classroom vs Online: Which is Better?',
            'meta_title' => 'Care Certificate Classroom vs Online: Which Format is Best? | CTA',
            'meta_description' => 'Compare classroom and online Care Certificate training. Which delivers better competence?',
            'keywords' => 'Care Certificate, classroom training, online training, care worker qualification',
            'category' => 'training-tips',
            'status' => 'publish',
            'article_number' => 2,
        ],
        [
            'title' => 'Understanding the Workforce Development Fund (WDF) and New LDSS',
            'meta_title' => 'WDF vs LDSS: Funding Your Care Training in 2025–26 | CTA',
            'meta_description' => 'Understand Workforce Development Fund changes. How to access LDSS grants for care staff training.',
            'keywords' => 'Workforce Development Fund, LDSS, care training funding, Skills for Care',
            'category' => 'regulatory-updates',
            'status' => 'publish',
            'article_number' => 3,
        ],
        [
            'title' => 'First Aid Certification: What You Need to Know (2026 Update)',
            'meta_title' => 'First Aid Certification for Care Workers 2026 | CTA',
            'meta_description' => 'Complete guide to First Aid training for care sector. EFAW vs Paediatric, level requirements, validity.',
            'keywords' => 'First Aid training, EFAW, Paediatric First Aid, care workers, HSE approved',
            'category' => 'training-tips',
            'status' => 'publish',
            'article_number' => 4,
        ],
        
        // Outlined Articles (8) - will be created as drafts
        [
            'title' => '5 Ways to Make Training Stick With Your Team',
            'meta_title' => '5 Ways to Make Training Stick With Your Team | CTA',
            'meta_description' => 'Practical strategies to ensure training knowledge is retained and applied in the workplace.',
            'keywords' => 'training retention, staff development, learning culture, care training',
            'category' => 'training-tips',
            'status' => 'draft',
            'article_number' => 5,
        ],
        [
            'title' => 'Medication Management: New 2026 Requirements',
            'meta_title' => 'Medication Management: New 2026 Requirements | CTA',
            'meta_description' => 'Understanding the latest medication training requirements and competency standards for 2026.',
            'keywords' => 'medication training, medication competency, CQC medication requirements, 2026',
            'category' => 'regulatory-updates',
            'status' => 'draft',
            'article_number' => 6,
        ],
        [
            'title' => 'How to Prepare Your Team for CQC Inspection',
            'meta_title' => 'How to Prepare Your Team for CQC Inspection | CTA',
            'meta_description' => 'Step-by-step guide to preparing your care service for CQC inspection, including training evidence preparation.',
            'keywords' => 'CQC inspection preparation, CQC inspection guide, care home inspection',
            'category' => 'cqc-compliance',
            'status' => 'draft',
            'article_number' => 7,
        ],
        [
            'title' => 'Training Requirements by Role: Complete Guide',
            'meta_title' => 'Training Requirements by Role: Complete Guide | CTA',
            'meta_description' => 'Comprehensive guide to mandatory and recommended training for care workers, team leaders, and managers.',
            'keywords' => 'care worker training, training requirements, role-based training, mandatory training',
            'category' => 'training-tips',
            'status' => 'draft',
            'article_number' => 8,
        ],
        [
            'title' => 'Common CQC Training Compliance Mistakes (And How to Avoid Them)',
            'meta_title' => 'Common CQC Training Compliance Mistakes | CTA',
            'meta_description' => 'Learn about the most common training compliance mistakes care providers make and how to avoid them.',
            'keywords' => 'CQC compliance mistakes, training compliance, care training errors',
            'category' => 'cqc-compliance',
            'status' => 'draft',
            'article_number' => 9,
        ],
        [
            'title' => 'Moving and Handling: Reducing Workplace Injuries (The Real Impact)',
            'meta_title' => 'Moving and Handling: Reducing Workplace Injuries | CTA',
            'meta_description' => 'How proper moving and handling training reduces workplace injuries and saves money for care providers.',
            'keywords' => 'moving and handling, workplace injuries, care worker safety, manual handling',
            'category' => 'training-tips',
            'status' => 'draft',
            'article_number' => 10,
        ],
        [
            'title' => 'Case Study – How a Care Home Prepared for CQC Inspection Through Strategic Training',
            'meta_title' => 'Case Study: CQC Inspection Success Through Training | CTA',
            'meta_description' => 'Real-world case study showing how strategic training helped a care home improve their CQC rating.',
            'keywords' => 'CQC case study, care home training, CQC rating improvement, training success',
            'category' => 'case-studies',
            'status' => 'draft',
            'article_number' => 12,
        ],
    ];
    
    $results = [
        'created' => [],
        'skipped' => [],
        'errors' => [],
    ];
    
    foreach ($articles as $article) {
        // Check if post already exists
        $existing = get_page_by_path(sanitize_title($article['title']), OBJECT, 'post');
        if ($existing) {
            $results['skipped'][] = $article['title'];
            continue;
        }
        
        // Extract content from markdown file for complete articles
        $content = '';
        if ($article['status'] === 'publish') {
            $content = cta_extract_article_from_markdown($markdown_file, $article['article_number']);
        }
        
        // For outlined articles, create placeholder content
        if (empty($content) && $article['status'] === 'draft') {
            $content = '<p><strong>This article is currently being expanded. Check back soon for the full content.</strong></p>';
            
            // Add outline sections based on article number
            $outlines = [
                5 => ['The problem: Staff forget training immediately after certification', 'Scenario-based learning keeps knowledge accessible', 'Peer learning and shadowing reinforce skills', 'Spaced repetition (refreshers aren\'t just compliance, they\'re embedding)', 'Linking training to real incidents/near-misses in your service', 'Creating a learning culture where staff ask questions and apply knowledge'],
                6 => ['Oliver McGowan Tier 2 now funds medication training', 'Competency vs. Awareness—which your staff need', 'Administration errors and how training prevents them', 'Annual refresher expectations (not just three-yearly)', 'CQC\'s focus on medication as high-risk, high-compliance area', 'Real scenarios: what inspectors ask about medication competency'],
                7 => ['Timeline: 2 weeks vs. 8 weeks notice (different prep strategies)', 'Training as a quick wins area (inspectors always check this)', 'Mock inspection scenarios', 'Evidence gathering (certificates, supervision records, competency assessments)', 'Staff confidence-building (training staff as confidence-builders before inspection)', 'Post-inspection: using inspection feedback to refine training'],
                8 => ['Care Assistant mandatory courses (clear list)', 'Senior Care Worker / Team Leader additional training', 'Registered Manager qualification and ongoing CPD', 'Nursing and clinical staff specialisms', 'Specialist roles (palliative care, learning disability champions, dementia leads)', 'Career pathways through training'],
                9 => ['Expired certificates still in service', 'No competency assessment beyond the certificate', 'Induction incomplete before independent practice', 'No refresh plan (discovering gaps at inspection)', 'Using untrained or unqualified trainers', 'No link between training and incident prevention', 'Waiting for inspection notice to audit training', 'Not tailoring training to your specific service'],
                10 => ['Why moving & handling training saves money (fewer staff injuries, reduced sick leave)', 'Most common moving & handling injuries in care settings', 'The difference between theory and practical competency', 'Using your actual equipment in training (not generic models)', 'Ongoing supervision and peer checking (not one-off training)', 'CQC inspection focus on moving & handling compliance'],
                12 => ['Real (anonymised) example: 40-bed care home with known training gaps', 'Timeline of training interventions (12 weeks before inspection notice)', 'Courses booked, staff trained, evidence gathered', 'Inspection result: moved from "Requires Improvement" to "Good"', 'Cost of training vs. cost of inspection findings', 'Lessons learned and ongoing training culture'],
            ];
            
            if (isset($outlines[$article['article_number']])) {
                $content .= '<h2>Planned Sections:</h2><ul>';
                foreach ($outlines[$article['article_number']] as $section) {
                    $content .= '<li>' . esc_html($section) . '</li>';
                }
                $content .= '</ul>';
            }
        }
        
        // Convert markdown to HTML
        $html_content = cta_convert_markdown_to_html($content);
        
        // Prepare post data
        $post_data = [
            'post_title' => $article['title'],
            'post_content' => $html_content,
            'post_status' => $article['status'],
            'post_type' => 'post',
            'post_author' => 1, // Default to admin user
            'post_name' => sanitize_title($article['title']),
        ];
        
        // Set category
        $post_category = isset($category_ids[$article['category']]) ? [$category_ids[$article['category']]] : [];
        
        // Create post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            $results['errors'][] = [
                'title' => $article['title'],
                'error' => $post_id->get_error_message(),
            ];
            continue;
        }
        
        // Set category
        if (!empty($post_category)) {
            wp_set_post_categories($post_id, $post_category);
        }
        
        // Set meta fields (SEO)
        if (function_exists('update_post_meta')) {
            update_post_meta($post_id, '_yoast_wpseo_title', $article['meta_title']);
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $article['meta_description']);
            
            // Also set for Rank Math if installed
            update_post_meta($post_id, 'rank_math_title', $article['meta_title']);
            update_post_meta($post_id, 'rank_math_description', $article['meta_description']);
            
            // Store keywords as post meta
            update_post_meta($post_id, '_cta_article_keywords', $article['keywords']);
        }
        
        $results['created'][] = [
            'id' => $post_id,
            'title' => $article['title'],
            'status' => $article['status'],
        ];
    }
    
    return $results;
}

/**
 * Render Phase 1 Posts section for Import CTA Data page
 */
function cta_render_phase1_posts_section() {
    $results = null;
    
    if (isset($_POST['create_phase1_posts']) && check_admin_referer('create_phase1_posts_nonce')) {
        $results = cta_create_phase1_blog_posts();
    }
    
    ?>
    <div class="cta-import-section">
        <h2>
            <span class="dashicons dashicons-edit-page"></span>
            Phase 1 Blog Posts
        </h2>
        <p>Create WordPress posts from the Phase 1 blog articles:</p>
        <ul>
            <li><strong>4 complete articles</strong> - Will be published immediately</li>
            <li><strong>8 outlined articles</strong> - Will be created as drafts for future expansion</li>
        </ul>
        <p><strong>Note:</strong> Posts that already exist will be skipped.</p>
        
        <?php if ($results !== null) : ?>
            <?php if (!empty($results['created'])) : ?>
            <div class="notice notice-success inline">
                <p><strong>Created <?php echo count($results['created']); ?> posts:</strong></p>
                <ul>
                    <?php foreach ($results['created'] as $post) : ?>
                    <li><?php echo esc_html($post['title']); ?> (<?php echo esc_html($post['status']); ?>) - <a href="<?php echo esc_url(get_edit_post_link($post['id'])); ?>">Edit</a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($results['skipped'])) : ?>
            <div class="notice notice-warning inline">
                <p><strong>Skipped <?php echo count($results['skipped']); ?> posts (already exist):</strong></p>
                <ul>
                    <?php foreach ($results['skipped'] as $title) : ?>
                    <li><?php echo esc_html($title); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($results['errors'])) : ?>
            <div class="notice notice-error inline">
                <p><strong>Errors:</strong></p>
                <ul>
                    <?php foreach ($results['errors'] as $error) : ?>
                    <li><?php echo esc_html($error['title']); ?>: <?php echo esc_html($error['error']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <form method="post">
            <?php wp_nonce_field('create_phase1_posts_nonce'); ?>
            <p>
                <button type="submit" name="create_phase1_posts" class="button button-primary">
                    <span class="dashicons dashicons-edit-page" style="vertical-align: middle; margin-right: 5px;"></span>
                    Create Phase 1 Blog Posts
                </button>
            </p>
        </form>
    </div>
    <?php
}
