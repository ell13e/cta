<?php
/**
 * Enhanced Page Editor with Visual/Code Switching and SEO Tools
 * 
 * Provides:
 * - Visual/Code editor toggle (like posts)
 * - Comprehensive SEO meta box with live preview
 * - SEO score calculator
 * - Schema type selector
 * - Character counters
 * - Meta description generator
 * 
 * @package CTA_Theme
 */

defined('ABSPATH') || exit;

/**
 * Enable visual/code editor for pages (if Gutenberg is disabled)
 * Ensures pages have the classic editor with visual/code tabs
 */
function cta_enable_page_editor() {
    // Ensure pages can use the editor
    add_post_type_support('page', 'editor');
    
    // If Classic Editor plugin is active, ensure it's enabled for pages
    if (class_exists('Classic_Editor')) {
        // Classic Editor handles this automatically
        return;
    }
    
    // If Gutenberg is disabled globally, ensure classic editor works
    add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
        if ($post_type === 'page') {
            // Allow classic editor for pages
            return false;
        }
        return $use_block_editor;
    }, 10, 2);
}
add_action('init', 'cta_enable_page_editor');

/**
 * Add comprehensive SEO meta box for pages
 */
function cta_add_seo_meta_box() {
    add_meta_box(
        'cta_page_seo',
        '🔍 SEO & Schema Settings',
        'cta_page_seo_meta_box_callback',
        'page',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'cta_add_seo_meta_box');

/**
 * SEO meta box callback with live preview and scoring
 */
function cta_page_seo_meta_box_callback($post) {
    wp_nonce_field('cta_page_seo_nonce', 'cta_page_seo_nonce');
    
    // Get current values
    $meta_title = cta_safe_get_field('page_seo_meta_title', $post->ID, '');
    $meta_description = cta_safe_get_field('page_seo_meta_description', $post->ID, '');
    $schema_type = cta_safe_get_field('page_schema_type', $post->ID, 'WebPage');
    $primary_keyword = cta_safe_get_field('page_seo_primary_keyword', $post->ID, '');
    $secondary_keywords = cta_safe_get_field('page_seo_secondary_keywords', $post->ID, '');
    $noindex = get_post_meta($post->ID, '_cta_noindex', true);
    $nofollow = get_post_meta($post->ID, '_cta_nofollow', true);
    $nosnippet = get_post_meta($post->ID, '_cta_nosnippet', true);
    $max_snippet_length = get_post_meta($post->ID, '_cta_max_snippet_length', true);
    
    // Calculate SEO score
    $seo_score = cta_calculate_page_seo_score($post);
    
    // Get auto-generated meta description preview
    $auto_description = cta_get_meta_description($post);
    
    ?>
    <div class="cta-seo-meta-box">
        <!-- Simplified SEO Score -->
        <div class="cta-seo-score-card" style="background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="font-size: 14px; color: #1d2327;">SEO Score: <span id="cta-seo-score-value" style="color: <?php echo $seo_score['color']; ?>; font-size: 20px; font-weight: 600;"><?php echo $seo_score['score']; ?>/100</span></strong>
                    <p style="margin: 4px 0 0 0; font-size: 13px; color: #646970;"><?php echo esc_html($seo_score['message']); ?></p>
                </div>
                <button type="button" id="cta-toggle-seo-details" class="button button-small" style="margin-left: 16px;">
                    <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span>
                </button>
            </div>
            <div id="cta-seo-score-details" style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid #dcdcde; font-size: 13px; color: #646970;">
                <?php foreach ($seo_score['checks'] as $check) : ?>
                    <div style="margin: 4px 0;">
                        <?php echo $check['passed'] ? '✅' : ($check['score'] > 0 ? '⚠️' : '❌'); ?> 
                        <?php echo esc_html($check['label']); ?>
                        <?php if (isset($check['score'])): ?>
                            <span style="color: #8c8f94;">(<?php echo $check['score']; ?>pts)</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Main SEO Fields -->
        <div class="cta-seo-main-fields" style="margin-bottom: 20px;">
            <!-- Meta Title -->
            <div style="margin-bottom: 20px;">
                <label for="cta_meta_title" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">
                    Meta Title
                    <span id="cta-title-length" style="color: #646970; font-weight: normal; margin-left: 8px; font-size: 12px;">0/60</span>
                </label>
                <input 
                    type="text" 
                    id="cta_meta_title" 
                    name="page_seo_meta_title" 
                    value="<?php echo esc_attr($meta_title); ?>"
                    placeholder="<?php echo esc_attr(get_the_title($post->ID)); ?>"
                    maxlength="60"
                    style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px;"
                />
                <p class="description" style="margin: 6px 0 0 0; font-size: 12px; color: #646970;">
                    Leave blank to use page title. 50-60 characters recommended.
                </p>
            </div>
            
            <!-- Meta Description -->
            <div style="margin-bottom: 20px;">
                <label for="cta_meta_description" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">
                    Meta Description
                    <span id="cta-desc-length" style="color: #646970; font-weight: normal; margin-left: 8px; font-size: 12px;">0/160</span>
                </label>
                <textarea 
                    id="cta_meta_description" 
                    name="page_seo_meta_description" 
                    rows="3"
                    maxlength="160"
                    placeholder="<?php echo esc_attr($auto_description); ?>"
                    style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-family: inherit; font-size: 14px; resize: vertical;"
                ><?php echo esc_textarea($meta_description); ?></textarea>
                <div style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
                    <button 
                        type="button" 
                        id="cta-generate-meta-desc" 
                        class="button button-small"
                    >
                        Generate from content
                    </button>
                    <span class="description" style="margin: 0; font-size: 12px; color: #646970;">
                        Leave blank to auto-generate. 150-160 characters recommended.
                    </span>
                </div>
            </div>
            
            <!-- Search Result Preview -->
            <div style="background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; padding: 16px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 600; color: #1d2327;">Search Result Preview</h4>
                <div id="cta-search-preview" style="max-width: 600px;">
                    <div style="color: #1a0dab; font-size: 18px; line-height: 1.3; margin-bottom: 3px;">
                        <span id="preview-title"><?php echo esc_html($meta_title ?: get_the_title($post->ID)); ?></span>
                    </div>
                    <div style="color: #006621; font-size: 14px; line-height: 1.3; margin-bottom: 8px;">
                        <?php echo esc_html(home_url()); ?> › <span id="preview-url"><?php echo esc_html(str_replace(home_url(), '', get_permalink($post->ID))); ?></span>
                    </div>
                    <div style="color: #545454; font-size: 14px; line-height: 1.4;">
                        <span id="preview-description"><?php echo esc_html($meta_description ?: $auto_description); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Advanced Options (Collapsible) -->
        <div class="cta-seo-advanced" style="border-top: 1px solid #dcdcde; padding-top: 20px;">
            <button type="button" id="cta-toggle-advanced-seo" class="button button-link" style="padding: 0; margin-bottom: 16px; font-size: 13px; font-weight: 600;">
                <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span>
                Advanced SEO Settings
            </button>
            
            <div id="cta-advanced-seo-content" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Left: Keywords -->
                    <div>
                        <div style="margin-bottom: 16px;">
                            <label for="cta_primary_keyword" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">
                                Primary Focus Keyword
                            </label>
                            <input 
                                type="text" 
                                id="cta_primary_keyword" 
                                name="page_seo_primary_keyword" 
                                value="<?php echo esc_attr($primary_keyword); ?>"
                                placeholder="e.g., care training kent"
                                style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px;"
                            />
                            <p class="description" style="margin: 6px 0 0 0; font-size: 12px; color: #646970;">
                                Main keyword you want to rank for.
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label for="cta_secondary_keywords" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">
                                Secondary Keywords
                            </label>
                            <input 
                                type="text" 
                                id="cta_secondary_keywords" 
                                name="page_seo_secondary_keywords" 
                                value="<?php echo esc_attr($secondary_keywords); ?>"
                                placeholder="e.g., cqc training, care courses"
                                style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px;"
                            />
                            <p class="description" style="margin: 6px 0 0 0; font-size: 12px; color: #646970;">
                                Comma-separated related keywords.
                            </p>
                            <div id="cta-secondary-keyword-tests" style="margin-top: 8px; padding: 12px; background: #f6f7f7; border-radius: 4px; font-size: 12px; display: none;">
                                <strong>Keyword Tests:</strong>
                                <div id="cta-secondary-keyword-results" style="margin-top: 8px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right: Schema & Indexing -->
                    <div>
                        <div style="margin-bottom: 16px;">
                            <label for="cta_schema_type" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;">
                                Schema.org Type
                            </label>
                            <select 
                                id="cta_schema_type" 
                                name="page_schema_type"
                                style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px;"
                            >
                                <option value="WebPage" <?php selected($schema_type, 'WebPage'); ?>>WebPage (Generic)</option>
                                <option value="HomePage" <?php selected($schema_type, 'HomePage'); ?>>HomePage</option>
                                <option value="AboutPage" <?php selected($schema_type, 'AboutPage'); ?>>AboutPage</option>
                                <option value="ContactPage" <?php selected($schema_type, 'ContactPage'); ?>>ContactPage</option>
                                <option value="CollectionPage" <?php selected($schema_type, 'CollectionPage'); ?>>CollectionPage</option>
                                <option value="FAQPage" <?php selected($schema_type, 'FAQPage'); ?>>FAQPage</option>
                            </select>
                            <p class="description" style="margin: 6px 0 0 0; font-size: 12px; color: #646970;">
                                Helps search engines understand your page content.
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px;">Search Engine Indexing</label>
                            <label style="display: flex; align-items: center; margin-bottom: 8px;">
                                <input 
                                    type="checkbox" 
                                    name="cta_noindex" 
                                    value="1" 
                                    <?php checked($noindex, '1'); ?>
                                    style="margin-right: 8px;"
                                />
                                <span style="font-size: 13px;">Noindex (hide from search engines)</span>
                            </label>
                            <label style="display: flex; align-items: center;">
                                <input 
                                    type="checkbox" 
                                    name="cta_nofollow" 
                                    value="1" 
                                    <?php checked($nofollow, '1'); ?>
                                    style="margin-right: 8px;"
                                />
                                <span style="font-size: 13px;">Nofollow (don't follow links)</span>
                            </label>
                            <p class="description" style="margin: 8px 0 0 0; font-size: 12px; color: #d63638;">
                                ⚠️ Only use noindex if this page should NOT appear in search results.
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px;">Snippet control</label>
                            <label style="display: flex; align-items: center; margin-bottom: 8px;">
                                <input 
                                    type="checkbox" 
                                    name="cta_nosnippet" 
                                    value="1" 
                                    <?php checked($nosnippet, '1'); ?>
                                    style="margin-right: 8px;"
                                />
                                <span style="font-size: 13px;">No snippet (exclude from search result snippets)</span>
                            </label>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <label for="cta_max_snippet_length" style="font-size: 13px;">Max snippet length:</label>
                                <input 
                                    type="number" 
                                    id="cta_max_snippet_length" 
                                    name="cta_max_snippet_length" 
                                    value="<?php echo esc_attr($max_snippet_length); ?>"
                                    min="1" 
                                    max="320" 
                                    step="1"
                                    placeholder="160"
                                    style="width: 80px; padding: 6px 8px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;"
                                />
                                <span style="font-size: 12px; color: #646970;">characters (leave blank for default)</span>
                            </div>
                            <p class="description" style="margin: 4px 0 0 0; font-size: 12px; color: #646970;">
                                To exclude only part of the page from snippets, wrap that section in <code>&lt;div data-nosnippet&gt;…&lt;/div&gt;</code> in the content.
                            </p>
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px;">Quick Actions</label>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <a 
                                    href="<?php echo esc_url(get_permalink($post->ID)); ?>" 
                                    target="_blank" 
                                    class="button button-small"
                                    style="text-align: center;"
                                >
                                    View Page
                                </a>
                                <a 
                                    href="https://search.google.com/test/rich-results?url=<?php echo urlencode(get_permalink($post->ID)); ?>" 
                                    target="_blank" 
                                    class="button button-small"
                                    style="text-align: center;"
                                >
                                    Test Schema
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Toggle SEO details
        $('#cta-toggle-seo-details').on('click', function() {
            var $details = $('#cta-seo-score-details');
            var $icon = $(this).find('.dashicons');
            if ($details.is(':visible')) {
                $details.slideUp();
                $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
            } else {
                $details.slideDown();
                $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
            }
        });
        
        // Toggle advanced SEO settings
        $('#cta-toggle-advanced-seo').on('click', function() {
            var $content = $('#cta-advanced-seo-content');
            var $icon = $(this).find('.dashicons');
            if ($content.is(':visible')) {
                $content.slideUp();
                $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
            } else {
                $content.slideDown();
                $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
            }
        });
        
        // Character counters
        function updateCounters() {
            var title = $('#cta_meta_title').val() || '<?php echo esc_js(get_the_title($post->ID)); ?>';
            var desc = $('#cta_meta_description').val() || '<?php echo esc_js($auto_description); ?>';
            
            var titleLen = title.length;
            var descLen = desc.length;
            
            $('#cta-title-length').text(titleLen + '/60').css('color', titleLen > 60 ? '#d63638' : (titleLen < 50 ? '#d63638' : '#646970'));
            $('#cta-desc-length').text(descLen + '/160').css('color', descLen > 160 ? '#d63638' : (descLen < 120 ? '#d63638' : '#646970'));
            
            // Update preview
            $('#preview-title').text(title);
            $('#preview-description').text(desc);
            
            // Update SEO score
            updateSEOScore();
        }
        
        // Update on input
        $('#cta_meta_title, #cta_meta_description, #cta_primary_keyword, #cta_secondary_keywords').on('input', updateCounters);
        
        // Generate meta description button
        $('#cta-generate-meta-desc').on('click', function() {
            var content = $('#content').val() || '';
            var title = '<?php echo esc_js(get_the_title($post->ID)); ?>';
            var primaryKeyword = $('#cta_primary_keyword').val();
            
            // Simple extraction: first 150 chars of content, or title + default
            var generated = content.substring(0, 150).trim();
            if (generated.length < 50) {
                if (primaryKeyword) {
                    generated = title + '. ' + primaryKeyword + ' training in Maidstone, Kent. CQC-compliant, CPD-accredited courses.';
                } else {
                    generated = title + '. Professional care sector training in Maidstone, Kent. CQC-compliant, CPD-accredited courses.';
                }
            }
            generated = generated.substring(0, 160);
            
            $('#cta_meta_description').val(generated);
            updateCounters();
        });
        
        // Copy meta tags
        $('#cta-copy-meta-tags').on('click', function() {
            var title = $('#cta_meta_title').val() || '<?php echo esc_js(get_the_title($post->ID)); ?>';
            var desc = $('#cta_meta_description').val() || '<?php echo esc_js($auto_description); ?>';
            
            var metaTags = '<meta name="title" content="' + title + '">\n';
            metaTags += '<meta name="description" content="' + desc + '">';
            
            // Copy to clipboard
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(metaTags).select();
            document.execCommand('copy');
            $temp.remove();
            
            $(this).text('✅ Copied!').css('background', '#00a32a');
            setTimeout(function() {
                $('#cta-copy-meta-tags').text('📋 Copy Meta Tags').css('background', '');
            }, 2000);
        });
        
        // Update SEO score with enhanced 2026 methodology
        function updateSEOScore() {
            var title = $('#cta_meta_title').val() || '<?php echo esc_js(get_the_title($post->ID)); ?>';
            var desc = $('#cta_meta_description').val() || '<?php echo esc_js($auto_description); ?>';
            var primaryKeyword = $('#cta_primary_keyword').val().toLowerCase().trim();
            var secondaryKeywords = $('#cta_secondary_keywords').val().toLowerCase().split(',').map(function(k) { return k.trim(); }).filter(function(k) { return k.length > 0; });
            var hasImage = <?php echo has_post_thumbnail($post->ID) ? 'true' : 'false'; ?>;
            var contentText = $('#content').val() || '';
            var contentLength = contentText.length;
            var wordCount = contentText.trim().split(/\s+/).filter(function(w) { return w.length > 0; }).length;
            
            var score = 0;
            var maxScore = 100;
            var checks = [];
            
            // 1. Title Optimization (15 points) - Rank Math style
            var titleScore = 0;
            if (title.length >= 50 && title.length <= 60) {
                titleScore = 15;
                checks.push({label: 'Title length optimal (50-60 chars)', passed: true, score: 15});
            } else if (title.length >= 40 && title.length < 50) {
                titleScore = 10;
                checks.push({label: 'Title length good (40-49 chars)', passed: true, score: 10});
            } else if (title.length > 60 && title.length <= 70) {
                titleScore = 8;
                checks.push({label: 'Title slightly long (61-70 chars)', passed: false, score: 8});
            } else if (title.length > 0) {
                titleScore = 5;
                checks.push({label: 'Title length needs adjustment', passed: false, score: 5});
            } else {
                checks.push({label: 'Title missing', passed: false, score: 0});
            }
            
            // Primary keyword in title (5 points)
            if (primaryKeyword && title.toLowerCase().indexOf(primaryKeyword) !== -1) {
                titleScore += 5;
                checks.push({label: 'Primary keyword in title', passed: true, score: 5});
            } else if (primaryKeyword) {
                checks.push({label: 'Primary keyword not in title', passed: false, score: 0});
            }
            score += titleScore;
            
            // 2. Meta Description (15 points)
            var descScore = 0;
            if (desc.length >= 120 && desc.length <= 160) {
                descScore = 15;
                checks.push({label: 'Description length optimal (120-160 chars)', passed: true, score: 15});
            } else if (desc.length >= 100 && desc.length < 120) {
                descScore = 10;
                checks.push({label: 'Description length good (100-119 chars)', passed: true, score: 10});
            } else if (desc.length > 160 && desc.length <= 180) {
                descScore = 8;
                checks.push({label: 'Description slightly long (161-180 chars)', passed: false, score: 8});
            } else if (desc.length > 0) {
                descScore = 5;
                checks.push({label: 'Description length needs adjustment', passed: false, score: 5});
            } else {
                checks.push({label: 'Description missing', passed: false, score: 0});
            }
            
            // Primary keyword in description (5 points)
            if (primaryKeyword && desc.toLowerCase().indexOf(primaryKeyword) !== -1) {
                descScore += 5;
                checks.push({label: 'Primary keyword in description', passed: true, score: 5});
            } else if (primaryKeyword) {
                checks.push({label: 'Primary keyword not in description', passed: false, score: 0});
            }
            score += descScore;
            
            // 3. Content Length - Graduated scoring (Rank Math style) (20 points)
            var contentScore = 0;
            if (wordCount >= 2500) {
                contentScore = 20;
                checks.push({label: 'Content length excellent (2500+ words)', passed: true, score: 20});
            } else if (wordCount >= 2000) {
                contentScore = 14; // 70% of 20
                checks.push({label: 'Content length very good (2000-2499 words)', passed: true, score: 14});
            } else if (wordCount >= 1500) {
                contentScore = 12; // 60% of 20
                checks.push({label: 'Content length good (1500-1999 words)', passed: true, score: 12});
            } else if (wordCount >= 1000) {
                contentScore = 8; // 40% of 20
                checks.push({label: 'Content length moderate (1000-1499 words)', passed: false, score: 8});
            } else if (wordCount >= 600) {
                contentScore = 4; // 20% of 20
                checks.push({label: 'Content length acceptable (600-999 words)', passed: false, score: 4});
            } else if (wordCount >= 300) {
                contentScore = 2;
                checks.push({label: 'Content length minimal (300-599 words)', passed: false, score: 2});
            } else {
                checks.push({label: 'Content too short (<300 words)', passed: false, score: 0});
            }
            score += contentScore;
            
            // 4. Keyword Analysis (15 points)
            var keywordScore = 0;
            if (primaryKeyword) {
                var keywordLower = primaryKeyword.toLowerCase();
                var contentLower = contentText.toLowerCase();
                var keywordCount = (contentLower.match(new RegExp(keywordLower.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi')) || []).length;
                var totalWords = wordCount || 1;
                var keywordDensity = (keywordCount / totalWords) * 100;
                
                // Keyword in first 10% of content (5 points)
                var first10Percent = Math.max(300, Math.floor(contentLength * 0.1));
                var firstContent = contentText.substring(0, first10Percent).toLowerCase();
                if (firstContent.indexOf(keywordLower) !== -1) {
                    keywordScore += 5;
                    checks.push({label: 'Primary keyword in first 10% of content', passed: true, score: 5});
                } else {
                    checks.push({label: 'Primary keyword not in first 10%', passed: false, score: 0});
                }
                
                // Keyword density (5 points)
                if (keywordDensity >= 1 && keywordDensity <= 1.5) {
                    keywordScore += 5;
                    checks.push({label: 'Keyword density optimal (1-1.5%)', passed: true, score: 5});
                } else if (keywordDensity > 0 && keywordDensity < 1) {
                    keywordScore += 3;
                    checks.push({label: 'Keyword density low (<1%)', passed: false, score: 3});
                } else if (keywordDensity > 1.5 && keywordDensity <= 2.5) {
                    keywordScore += 2;
                    checks.push({label: 'Keyword density high (1.5-2.5%)', passed: false, score: 2});
                } else if (keywordDensity > 2.5) {
                    checks.push({label: 'Keyword over-optimization (>2.5%)', passed: false, score: 0});
                } else {
                    checks.push({label: 'Keyword not found in content', passed: false, score: 0});
                }
                
                // Keyword in URL slug (5 points)
                var urlSlug = '<?php echo esc_js($post->post_name); ?>';
                if (urlSlug.toLowerCase().indexOf(keywordLower.replace(/\s+/g, '-')) !== -1) {
                    keywordScore += 5;
                    checks.push({label: 'Primary keyword in URL slug', passed: true, score: 5});
                } else {
                    checks.push({label: 'Primary keyword not in URL slug', passed: false, score: 0});
                }
            } else {
                checks.push({label: 'No primary keyword set', passed: false, score: 0});
            }
            score += keywordScore;
            
            // 5. Featured Image (10 points)
            if (hasImage) {
                score += 10;
                checks.push({label: 'Featured image set', passed: true, score: 10});
            } else {
                checks.push({label: 'Featured image missing', passed: false, score: 0});
            }
            
            // 6. Schema Type (8 points)
            var schemaType = $('#cta_schema_type').val();
            if (schemaType && schemaType !== 'WebPage') {
                score += 8;
                checks.push({label: 'Specific schema type selected', passed: true, score: 8});
            } else {
                score += 4;
                checks.push({label: 'Using default schema', passed: false, score: 4});
            }
            
            // 7. Indexability (7 points)
            if (!$('input[name="cta_noindex"]').is(':checked')) {
                score += 7;
                checks.push({label: 'Page is indexable', passed: true, score: 7});
            } else {
                checks.push({label: 'Page is noindex (not searchable)', passed: false, score: 0});
            }
            
            // 8. Subheading Structure (5 points)
            var h2Count = (contentText.match(/<h2[^>]*>/gi) || []).length;
            var h3Count = (contentText.match(/<h3[^>]*>/gi) || []).length;
            if (h2Count >= 2 || (h2Count >= 1 && h3Count >= 2)) {
                score += 5;
                checks.push({label: 'Good subheading structure (H2/H3)', passed: true, score: 5});
            } else if (h2Count >= 1 || h3Count >= 1) {
                score += 3;
                checks.push({label: 'Some subheadings present', passed: false, score: 3});
            } else {
                checks.push({label: 'No subheadings (H2/H3) found', passed: false, score: 0});
            }
            
            // Primary keyword in subheadings (5 points)
            if (primaryKeyword && h2Count > 0) {
                var h2Matches = contentText.match(/<h2[^>]*>([^<]+)<\/h2>/gi) || [];
                var keywordInH2 = h2Matches.some(function(h2) {
                    return h2.toLowerCase().indexOf(primaryKeyword.toLowerCase()) !== -1;
                });
                if (keywordInH2) {
                    score += 5;
                    checks.push({label: 'Primary keyword in subheadings', passed: true, score: 5});
                } else {
                    checks.push({label: 'Primary keyword not in subheadings', passed: false, score: 0});
                }
            }
            
            // Secondary keywords in content and subheadings (5 points total)
            if (secondaryKeywords.length > 0) {
                var secondaryScore = 0;
                var secondaryResults = [];
                var contentLower = contentText.toLowerCase();
                var h2Text = (contentText.match(/<h[2-3][^>]*>([^<]+)<\/h[2-3]>/gi) || []).join(' ').toLowerCase();
                
                secondaryKeywords.forEach(function(keyword) {
                    var keywordLower = keyword.toLowerCase();
                    var inContent = contentLower.indexOf(keywordLower) !== -1;
                    var inHeadings = h2Text.indexOf(keywordLower) !== -1;
                    
                    secondaryResults.push({
                        keyword: keyword,
                        inContent: inContent,
                        inHeadings: inHeadings,
                        passed: inContent && inHeadings
                    });
                    
                    if (inContent && inHeadings) {
                        secondaryScore += 1;
                    }
                });
                
                // Max 5 points for secondary keywords (1 point per keyword found in both places)
                var secondaryPoints = Math.min(secondaryScore, 5);
                if (secondaryPoints > 0) {
                    score += secondaryPoints;
                    checks.push({label: secondaryPoints + ' secondary keyword(s) in content and subheadings', passed: true, score: secondaryPoints});
                } else {
                    checks.push({label: 'Secondary keywords not found in content/subheadings', passed: false, score: 0});
                }
                
                // Show secondary keyword test results
                if (secondaryResults.length > 0) {
                    var resultsHtml = secondaryResults.map(function(result) {
                        var icon = result.passed ? '✅' : '⚠️';
                        return '<div style="margin: 3px 0;">' + icon + ' "' + result.keyword + '" - ' +
                               (result.inContent ? 'In content' : 'Not in content') + ', ' +
                               (result.inHeadings ? 'In headings' : 'Not in headings') +
                               '</div>';
                    }).join('');
                    $('#cta-secondary-keyword-results').html(resultsHtml);
                    $('#cta-secondary-keyword-tests').show();
                } else {
                    $('#cta-secondary-keyword-tests').hide();
                }
            } else {
                $('#cta-secondary-keyword-tests').hide();
            }
            
            // Determine color and message (Rank Math style)
            var color, message;
            if (score >= 81) {
                color = '#00a32a';
                message = 'Fully optimized and ready to publish!';
            } else if (score >= 51) {
                color = '#dba617';
                message = 'Partially optimized with room for improvement.';
            } else {
                color = '#d63638';
                message = 'Poorly optimized, requires significant work.';
            }
            
            // Update display
            $('#cta-seo-score-value').text(score + '/100').css('color', color);
            $('#cta-seo-score-details').html(
                checks.map(function(check) {
                    var icon = check.passed ? '✅' : (check.score > 0 ? '⚠️' : '❌');
                    return '<div style="margin: 4px 0; font-size: 13px;">' + 
                           icon + ' ' + check.label + 
                           (check.score !== undefined ? ' <span style="color: #8c8f94;">(' + check.score + 'pts)</span>' : '') +
                           '</div>';
                }).join('')
            );
            
            // Update parent message
            $('#cta-seo-score-value').parent().find('p').text(message);
        }
        
        // Initial update
        updateCounters();
        
        // Update on schema/keyword change
        $('#cta_schema_type, #cta_primary_keyword, #cta_secondary_keywords, input[name="cta_noindex"]').on('change', updateSEOScore);
    });
    </script>
    
    <style>
    .cta-seo-meta-box {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    .cta-seo-meta-box .description {
        font-size: 12px;
        color: #646970;
        line-height: 1.5;
    }
    .cta-seo-meta-box input[type="text"],
    .cta-seo-meta-box textarea,
    .cta-seo-meta-box select {
        transition: border-color 0.15s ease-in-out;
    }
    .cta-seo-meta-box input[type="text"]:focus,
    .cta-seo-meta-box textarea:focus,
    .cta-seo-meta-box select:focus {
        border-color: #2271b1;
        box-shadow: 0 0 0 1px #2271b1;
        outline: none;
    }
    #cta-search-preview {
        transition: opacity 0.2s;
    }
    #cta-toggle-seo-details,
    #cta-toggle-advanced-seo {
        color: #2271b1;
    }
    #cta-toggle-seo-details:hover,
    #cta-toggle-advanced-seo:hover {
        color: #135e96;
    }
    </style>
    <?php
}

/**
 * Calculate SEO score for a page (2026 Enhanced Methodology)
 * Based on Rank Math, Yoast, and SEOPress best practices
 */
function cta_calculate_page_seo_score($post) {
    $score = 0;
    $checks = [];
    
    // Get values
    $meta_title = cta_safe_get_field('page_seo_meta_title', $post->ID, '');
    $title = $meta_title ?: get_the_title($post->ID);
    $meta_description = cta_safe_get_field('page_seo_meta_description', $post->ID, '');
    $auto_description = cta_get_meta_description($post);
    $description = $meta_description ?: $auto_description;
    $primary_keyword = strtolower(trim(cta_safe_get_field('page_seo_primary_keyword', $post->ID, '')));
    $has_image = has_post_thumbnail($post->ID);
    $content = strip_tags($post->post_content);
    $content_length = strlen($content);
    $word_count = str_word_count($content);
    $schema_type = cta_safe_get_field('page_schema_type', $post->ID, 'WebPage');
    $noindex = get_post_meta($post->ID, '_cta_noindex', true);
    
    // 1. Title Optimization (15 points + 5 for keyword = 20 max)
    $title_score = 0;
    $title_len = strlen($title);
    if ($title_len >= 50 && $title_len <= 60) {
        $title_score = 15;
        $checks[] = ['label' => 'Title length optimal (50-60 chars)', 'passed' => true, 'score' => 15];
    } elseif ($title_len >= 40 && $title_len < 50) {
        $title_score = 10;
        $checks[] = ['label' => 'Title length good (40-49 chars)', 'passed' => true, 'score' => 10];
    } elseif ($title_len > 60 && $title_len <= 70) {
        $title_score = 8;
        $checks[] = ['label' => 'Title slightly long (61-70 chars)', 'passed' => false, 'score' => 8];
    } elseif ($title_len > 0) {
        $title_score = 5;
        $checks[] = ['label' => 'Title length needs adjustment', 'passed' => false, 'score' => 5];
    } else {
        $checks[] = ['label' => 'Title missing', 'passed' => false, 'score' => 0];
    }
    
    // Primary keyword in title (5 points)
    if (!empty($primary_keyword) && stripos($title, $primary_keyword) !== false) {
        $title_score += 5;
        $checks[] = ['label' => 'Primary keyword in title', 'passed' => true, 'score' => 5];
    } elseif (!empty($primary_keyword)) {
        $checks[] = ['label' => 'Primary keyword not in title', 'passed' => false, 'score' => 0];
    }
    $score += $title_score;
    
    // 2. Meta Description (15 points + 5 for keyword = 20 max)
    $desc_score = 0;
    $desc_len = strlen($description);
    if ($desc_len >= 120 && $desc_len <= 160) {
        $desc_score = 15;
        $checks[] = ['label' => 'Description length optimal (120-160 chars)', 'passed' => true, 'score' => 15];
    } elseif ($desc_len >= 100 && $desc_len < 120) {
        $desc_score = 10;
        $checks[] = ['label' => 'Description length good (100-119 chars)', 'passed' => true, 'score' => 10];
    } elseif ($desc_len > 160 && $desc_len <= 180) {
        $desc_score = 8;
        $checks[] = ['label' => 'Description slightly long (161-180 chars)', 'passed' => false, 'score' => 8];
    } elseif ($desc_len > 0) {
        $desc_score = 5;
        $checks[] = ['label' => 'Description length needs adjustment', 'passed' => false, 'score' => 5];
    } else {
        $checks[] = ['label' => 'Description missing', 'passed' => false, 'score' => 0];
    }
    
    // Primary keyword in description (5 points)
    if (!empty($primary_keyword) && stripos($description, $primary_keyword) !== false) {
        $desc_score += 5;
        $checks[] = ['label' => 'Primary keyword in description', 'passed' => true, 'score' => 5];
    } elseif (!empty($primary_keyword)) {
        $checks[] = ['label' => 'Primary keyword not in description', 'passed' => false, 'score' => 0];
    }
    $score += $desc_score;
    
    // 3. Content Length - Graduated scoring (Rank Math style) (20 points)
    $content_score = 0;
    if ($word_count >= 2500) {
        $content_score = 20;
        $checks[] = ['label' => 'Content length excellent (2500+ words)', 'passed' => true, 'score' => 20];
    } elseif ($word_count >= 2000) {
        $content_score = 14; // 70% of 20
        $checks[] = ['label' => 'Content length very good (2000-2499 words)', 'passed' => true, 'score' => 14];
    } elseif ($word_count >= 1500) {
        $content_score = 12; // 60% of 20
        $checks[] = ['label' => 'Content length good (1500-1999 words)', 'passed' => true, 'score' => 12];
    } elseif ($word_count >= 1000) {
        $content_score = 8; // 40% of 20
        $checks[] = ['label' => 'Content length moderate (1000-1499 words)', 'passed' => false, 'score' => 8];
    } elseif ($word_count >= 600) {
        $content_score = 4; // 20% of 20
        $checks[] = ['label' => 'Content length acceptable (600-999 words)', 'passed' => false, 'score' => 4];
    } elseif ($word_count >= 300) {
        $content_score = 2;
        $checks[] = ['label' => 'Content length minimal (300-599 words)', 'passed' => false, 'score' => 2];
    } else {
        $checks[] = ['label' => 'Content too short (<300 words)', 'passed' => false, 'score' => 0];
    }
    $score += $content_score;
    
    // 4. Keyword Analysis (15 points)
    $keyword_score = 0;
    if (!empty($primary_keyword)) {
        $content_lower = strtolower($content);
        $keyword_lower = strtolower($primary_keyword);
        $keyword_count = substr_count($content_lower, $keyword_lower);
        $total_words = max($word_count, 1);
        $keyword_density = ($keyword_count / $total_words) * 100;
        
        // Keyword in first 10% of content (5 points)
        $first_10_percent = max(300, floor($content_length * 0.1));
        $first_content = strtolower(substr($content, 0, $first_10_percent));
        if (stripos($first_content, $keyword_lower) !== false) {
            $keyword_score += 5;
            $checks[] = ['label' => 'Primary keyword in first 10% of content', 'passed' => true, 'score' => 5];
        } else {
            $checks[] = ['label' => 'Primary keyword not in first 10%', 'passed' => false, 'score' => 0];
        }
        
        // Keyword density (5 points)
        if ($keyword_density >= 1 && $keyword_density <= 1.5) {
            $keyword_score += 5;
            $checks[] = ['label' => 'Keyword density optimal (1-1.5%)', 'passed' => true, 'score' => 5];
        } elseif ($keyword_density > 0 && $keyword_density < 1) {
            $keyword_score += 3;
            $checks[] = ['label' => 'Keyword density low (<1%)', 'passed' => false, 'score' => 3];
        } elseif ($keyword_density > 1.5 && $keyword_density <= 2.5) {
            $keyword_score += 2;
            $checks[] = ['label' => 'Keyword density high (1.5-2.5%)', 'passed' => false, 'score' => 2];
        } elseif ($keyword_density > 2.5) {
            $checks[] = ['label' => 'Keyword over-optimization (>2.5%)', 'passed' => false, 'score' => 0];
        } else {
            $checks[] = ['label' => 'Keyword not found in content', 'passed' => false, 'score' => 0];
        }
        
        // Keyword in URL slug (5 points)
        $url_slug = $post->post_name;
        $keyword_slug = str_replace(' ', '-', $keyword_lower);
        if (stripos($url_slug, $keyword_slug) !== false) {
            $keyword_score += 5;
            $checks[] = ['label' => 'Primary keyword in URL slug', 'passed' => true, 'score' => 5];
        } else {
            $checks[] = ['label' => 'Primary keyword not in URL slug', 'passed' => false, 'score' => 0];
        }
    } else {
        $checks[] = ['label' => 'No primary keyword set', 'passed' => false, 'score' => 0];
    }
    $score += $keyword_score;
    
    // 5. Featured Image (10 points)
    if ($has_image) {
        $score += 10;
        $checks[] = ['label' => 'Featured image set', 'passed' => true, 'score' => 10];
    } else {
        $checks[] = ['label' => 'Featured image missing', 'passed' => false, 'score' => 0];
    }
    
    // 6. Schema Type (8 points)
    if ($schema_type && $schema_type !== 'WebPage') {
        $score += 8;
        $checks[] = ['label' => 'Specific schema type selected', 'passed' => true, 'score' => 8];
    } else {
        $score += 4;
        $checks[] = ['label' => 'Using default schema', 'passed' => false, 'score' => 4];
    }
    
    // 7. Indexability (7 points)
    if ($noindex !== '1') {
        $score += 7;
        $checks[] = ['label' => 'Page is indexable', 'passed' => true, 'score' => 7];
    } else {
        $checks[] = ['label' => 'Page is noindex (not searchable)', 'passed' => false, 'score' => 0];
    }
    
    // 8. Subheading Structure (5 points)
    $h2_count = preg_match_all('/<h2[^>]*>/i', $post->post_content, $matches);
    $h3_count = preg_match_all('/<h3[^>]*>/i', $post->post_content, $matches);
    if ($h2_count >= 2 || ($h2_count >= 1 && $h3_count >= 2)) {
        $score += 5;
        $checks[] = ['label' => 'Good subheading structure (H2/H3)', 'passed' => true, 'score' => 5];
    } elseif ($h2_count >= 1 || $h3_count >= 1) {
        $score += 3;
        $checks[] = ['label' => 'Some subheadings present', 'passed' => false, 'score' => 3];
    } else {
        $checks[] = ['label' => 'No subheadings (H2/H3) found', 'passed' => false, 'score' => 0];
    }
    
    // Primary keyword in subheadings (5 points)
    if (!empty($primary_keyword) && $h2_count > 0) {
        preg_match_all('/<h2[^>]*>([^<]+)<\/h2>/i', $post->post_content, $h2_matches);
        $keyword_in_h2 = false;
        if (!empty($h2_matches[1])) {
            foreach ($h2_matches[1] as $h2_text) {
                if (stripos($h2_text, $primary_keyword) !== false) {
                    $keyword_in_h2 = true;
                    break;
                }
            }
        }
        if ($keyword_in_h2) {
            $score += 5;
            $checks[] = ['label' => 'Primary keyword in subheadings', 'passed' => true, 'score' => 5];
        } else {
            $checks[] = ['label' => 'Primary keyword not in subheadings', 'passed' => false, 'score' => 0];
        }
    }
    
    // Secondary keywords in content and subheadings (5 points total)
    $secondary_keywords_field = cta_safe_get_field('page_seo_secondary_keywords', $post->ID, '');
    $secondary_keywords_array = !empty($secondary_keywords_field) ? array_map('trim', explode(',', $secondary_keywords_field)) : [];
    $secondary_keywords_array = array_filter($secondary_keywords_array);
    
    if (!empty($secondary_keywords_array)) {
        $secondary_score = 0;
        $content_lower = strtolower($content);
        preg_match_all('/<h[2-3][^>]*>([^<]+)<\/h[2-3]>/i', $post->post_content, $all_headings);
        $headings_text = implode(' ', $all_headings[1] ?? []);
        $headings_lower = strtolower($headings_text);
        
        foreach ($secondary_keywords_array as $secondary_keyword) {
            $secondary_keyword_lower = strtolower(trim($secondary_keyword));
            if (empty($secondary_keyword_lower)) {
                continue;
            }
            
            $in_content = stripos($content_lower, $secondary_keyword_lower) !== false;
            $in_headings = stripos($headings_lower, $secondary_keyword_lower) !== false;
            
            if ($in_content && $in_headings) {
                $secondary_score += 1;
            }
        }
        
        // Max 5 points for secondary keywords
        $secondary_points = min($secondary_score, 5);
        if ($secondary_points > 0) {
            $score += $secondary_points;
            $checks[] = ['label' => $secondary_points . ' secondary keyword(s) in content and subheadings', 'passed' => true, 'score' => $secondary_points];
        } else {
            $checks[] = ['label' => 'Secondary keywords not found in content/subheadings', 'passed' => false, 'score' => 0];
        }
    }
    
    // Determine color and message (Rank Math style)
    if ($score >= 81) {
        $color = '#00a32a';
        $message = 'Fully optimized and ready to publish!';
    } elseif ($score >= 51) {
        $color = '#dba617';
        $message = 'Partially optimized with room for improvement.';
    } else {
        $color = '#d63638';
        $message = 'Poorly optimized, requires significant work.';
    }
    
    return [
        'score' => $score,
        'color' => $color,
        'message' => $message,
        'checks' => $checks,
    ];
}

/**
 * Save SEO meta box data
 */
function cta_save_seo_meta_box($post_id) {
    // Check nonce
    if (!isset($_POST['cta_page_seo_nonce']) || !wp_verify_nonce($_POST['cta_page_seo_nonce'], 'cta_page_seo_nonce')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save meta title
    if (isset($_POST['page_seo_meta_title'])) {
        cta_safe_update_field('page_seo_meta_title', $post_id, sanitize_text_field($_POST['page_seo_meta_title']));
    }
    
    // Save meta description
    if (isset($_POST['page_seo_meta_description'])) {
        cta_safe_update_field('page_seo_meta_description', $post_id, sanitize_textarea_field($_POST['page_seo_meta_description']));
    }
    
    // Save schema type
    if (isset($_POST['page_schema_type'])) {
        cta_safe_update_field('page_schema_type', $post_id, sanitize_text_field($_POST['page_schema_type']));
    }
    
    // Save keywords
    if (isset($_POST['page_seo_primary_keyword'])) {
        cta_safe_update_field('page_seo_primary_keyword', $post_id, sanitize_text_field($_POST['page_seo_primary_keyword']));
    }
    if (isset($_POST['page_seo_secondary_keywords'])) {
        cta_safe_update_field('page_seo_secondary_keywords', $post_id, sanitize_text_field($_POST['page_seo_secondary_keywords']));
    }
    
    // Save noindex/nofollow
    $noindex = isset($_POST['cta_noindex']) ? '1' : '0';
    $nofollow = isset($_POST['cta_nofollow']) ? '1' : '0';
    update_post_meta($post_id, '_cta_noindex', $noindex);
    update_post_meta($post_id, '_cta_nofollow', $nofollow);
    
    // Save snippet control
    $nosnippet = isset($_POST['cta_nosnippet']) ? '1' : '0';
    update_post_meta($post_id, '_cta_nosnippet', $nosnippet);
    $max_snippet = isset($_POST['cta_max_snippet_length']) ? sanitize_text_field($_POST['cta_max_snippet_length']) : '';
    if ($max_snippet !== '' && is_numeric($max_snippet)) {
        $max_snippet = (string) max(1, min(320, (int) $max_snippet));
    }
    update_post_meta($post_id, '_cta_max_snippet_length', $max_snippet);
    
    // Clear sitemap cache when SEO settings change
    cta_flush_sitemap_cache($post_id, get_post($post_id));
}
add_action('save_post', 'cta_save_seo_meta_box');

/**
 * Add editor toggle button helper text
 */
function cta_add_editor_toggle_help() {
    global $post_type;
    
    if ($post_type !== 'page') {
        return;
    }
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Add helpful text near editor tabs
        if ($('#postdivrich').length) {
            var helpText = '<div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 10px 15px; margin: 10px 0; border-radius: 4px; font-size: 13px;">' +
                '<strong>💡 Editor Tips:</strong> Use the <strong>Visual</strong> tab for WYSIWYG editing, or <strong>Text</strong> tab for HTML code. ' +
                'The main editor content appears above ACF fields. You can also use the "Page Sections" field below to add structured content sections.' +
                '</div>';
            $('#postdivrich').before(helpText);
        }
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'cta_add_editor_toggle_help');
add_action('admin_footer-post-new.php', 'cta_add_editor_toggle_help');

/**
 * Ensure pages support editor
 */
function cta_ensure_page_editor_support() {
    add_post_type_support('page', 'editor');
    add_post_type_support('page', 'excerpt');
    add_post_type_support('page', 'thumbnail');
}
add_action('init', 'cta_ensure_page_editor_support');
