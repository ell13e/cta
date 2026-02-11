<?php
/**
 * Smart Internal Linker
 * Automatically suggests and adds internal links to content
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Add Internal Links meta box to post editor
 */
function ccs_linker_add_metabox() {
    add_meta_box(
        'ccs_internal_linker',
        'Smart Internal Links',
        'ccs_linker_metabox_callback',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ccs_linker_add_metabox');

/**
 * Internal Linker meta box content
 */
function ccs_linker_metabox_callback($post) {
    wp_nonce_field('ccs_linker_nonce', 'ccs_linker_nonce_field');
    ?>
    <style>
        .cta-linker-container {
            padding: 16px 0;
        }
        .cta-linker-scan-btn {
            background: linear-gradient(135deg, #00a32a 0%, #007017 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .cta-linker-scan-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 163, 42, 0.3);
        }
        .cta-linker-scan-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        .cta-linker-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: cta-linker-spin 0.8s linear infinite;
        }
        @keyframes cta-linker-spin {
            to { transform: rotate(360deg); }
        }
        .cta-linker-results {
            margin-top: 20px;
        }
        .cta-linker-empty {
            padding: 20px;
            background: #f0f6fc;
            border-radius: 8px;
            text-align: center;
            color: #1d2327;
        }
        .cta-linker-suggestion {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s;
        }
        .cta-linker-suggestion:hover {
            border-color: #2271b1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .cta-linker-suggestion.added {
            background: #edfaef;
            border-color: #00a32a;
        }
        .cta-linker-suggestion-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }
        .cta-linker-match {
            flex: 1;
        }
        .cta-linker-match-text {
            font-size: 14px;
            color: #1d2327;
            background: #fff8e5;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }
        .cta-linker-match-context {
            font-size: 13px;
            color: #646970;
            margin-top: 4px;
            line-height: 1.5;
        }
        .cta-linker-target {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f6f7f7;
            border-radius: 6px;
            font-size: 12px;
        }
        .cta-linker-target-icon {
            width: 24px;
            height: 24px;
            background: #2271b1;
            color: white;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }
        .cta-linker-target-info {
            flex: 1;
        }
        .cta-linker-target-title {
            font-weight: 600;
            color: #1d2327;
        }
        .cta-linker-target-type {
            color: #646970;
            font-size: 11px;
        }
        .cta-linker-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .cta-linker-add-btn {
            background: #2271b1;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .cta-linker-add-btn:hover {
            background: #135e96;
        }
        .cta-linker-add-btn.added {
            background: #00a32a;
            cursor: default;
        }
        .cta-linker-skip-btn {
            background: transparent;
            color: #646970;
            border: 1px solid #c3c4c7;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .cta-linker-skip-btn:hover {
            background: #f6f7f7;
        }
        .cta-linker-stats {
            display: flex;
            gap: 16px;
            padding: 12px 16px;
            background: #f6f7f7;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .cta-linker-stat {
            text-align: center;
        }
        .cta-linker-stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1d2327;
        }
        .cta-linker-stat-label {
            font-size: 11px;
            color: #646970;
            text-transform: uppercase;
        }
        .cta-linker-add-all {
            background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            margin-left: auto;
        }
        .cta-linker-add-all:hover {
            box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3);
        }
        .cta-linker-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
    </style>
    
    <div class="cta-linker-container">
        <p style="color: #646970; margin: 0 0 16px 0;">
            Scan your content to find opportunities for internal links to your courses and other articles.
        </p>
        
        <button type="button" id="cta-linker-scan" class="cta-linker-scan-btn">
            <span class="btn-text">🔍 Scan for Link Opportunities</span>
            <span class="btn-loading" style="display:none;"><span class="cta-linker-spinner"></span> Analysing...</span>
        </button>
        
        <div id="cta-linker-results" class="cta-linker-results"></div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var suggestions = [];
        
        $('#cta-linker-scan').on('click', function() {
            var btn = $(this);
            var content = '';
            
            // Get content from editor
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
                content = tinymce.activeEditor.getContent({format: 'text'});
            } else {
                content = $('#content').val();
            }
            
            if (!content || content.length < 50) {
                alert('Please add some content first before scanning for link opportunities.');
                return;
            }
            
            btn.prop('disabled', true);
            btn.find('.btn-text').hide();
            btn.find('.btn-loading').show();
            $('#cta-linker-results').html('<div class="cta-linker-empty"><span class="cta-linker-spinner" style="border-color: rgba(0,0,0,0.1); border-top-color: #2271b1; display: inline-block; margin-right: 8px;"></span> Analysing content...</div>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_linker_scan',
                    nonce: $('#ccs_linker_nonce_field').val(),
                    content: content,
                    post_id: <?php echo $post->ID; ?>
                },
                success: function(response) {
                    if (response.success) {
                        suggestions = response.data.suggestions;
                        renderSuggestions(suggestions);
                    } else {
                        $('#cta-linker-results').html('<div class="cta-linker-empty">❌ ' + response.data + '</div>');
                    }
                },
                error: function() {
                    $('#cta-linker-results').html('<div class="cta-linker-empty">❌ Connection error. Please try again.</div>');
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.find('.btn-text').show();
                    btn.find('.btn-loading').hide();
                }
            });
        });
        
        function renderSuggestions(suggestions) {
            if (!suggestions || suggestions.length === 0) {
                $('#cta-linker-results').html('<div class="cta-linker-empty">✅ No additional link opportunities found. Your content looks good!</div>');
                return;
            }
            
            var html = '<div class="cta-linker-header">';
            html += '<div class="cta-linker-stats">';
            html += '<div class="cta-linker-stat"><div class="cta-linker-stat-number">' + suggestions.length + '</div><div class="cta-linker-stat-label">Opportunities</div></div>';
            html += '</div>';
            if (suggestions.length > 1) {
                html += '<button type="button" class="cta-linker-add-all">✨ Add All Links</button>';
            }
            html += '</div>';
            
            suggestions.forEach(function(s, index) {
                var typeIcon = s.type === 'course' ? '📚' : '📰';
                var typeLabel = s.type === 'course' ? 'Course' : 'Article';
                
                html += '<div class="cta-linker-suggestion" data-index="' + index + '">';
                html += '<div class="cta-linker-suggestion-header">';
                html += '<div class="cta-linker-match">';
                html += '<span class="cta-linker-match-text">' + escapeHtml(s.match_text) + '</span>';
                html += '<div class="cta-linker-match-context">..."' + escapeHtml(s.context) + '"...</div>';
                html += '</div>';
                html += '<div class="cta-linker-target">';
                html += '<div class="cta-linker-target-icon">' + typeIcon + '</div>';
                html += '<div class="cta-linker-target-info">';
                html += '<div class="cta-linker-target-title">' + escapeHtml(s.target_title) + '</div>';
                html += '<div class="cta-linker-target-type">' + typeLabel + '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                html += '<div class="cta-linker-actions">';
                html += '<button type="button" class="cta-linker-add-btn" data-index="' + index + '">✅ Yes, Add Link!</button>';
                html += '<button type="button" class="cta-linker-skip-btn" data-index="' + index + '">Skip</button>';
                html += '</div>';
                html += '</div>';
            });
            
            $('#cta-linker-results').html(html);
        }
        
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Add single link
        $(document).on('click', '.cta-linker-add-btn:not(.added)', function() {
            var btn = $(this);
            var index = btn.data('index');
            var suggestion = suggestions[index];
            
            addLinkToContent(suggestion, function(success) {
                if (success) {
                    btn.addClass('added').html('✓ Added!');
                    btn.closest('.cta-linker-suggestion').addClass('added');
                }
            });
        });
        
        // Skip suggestion
        $(document).on('click', '.cta-linker-skip-btn', function() {
            $(this).closest('.cta-linker-suggestion').slideUp(200, function() {
                $(this).remove();
                updateStats();
            });
        });
        
        // Add all links
        $(document).on('click', '.cta-linker-add-all', function() {
            var btn = $(this);
            btn.prop('disabled', true).text('Adding...');
            
            var pending = $('.cta-linker-add-btn:not(.added)').length;
            var completed = 0;
            
            $('.cta-linker-add-btn:not(.added)').each(function() {
                var addBtn = $(this);
                var index = addBtn.data('index');
                var suggestion = suggestions[index];
                
                addLinkToContent(suggestion, function(success) {
                    completed++;
                    if (success) {
                        addBtn.addClass('added').html('✓ Added!');
                        addBtn.closest('.cta-linker-suggestion').addClass('added');
                    }
                    
                    if (completed >= pending) {
                        btn.text('✓ All Done!');
                    }
                });
            });
        });
        
        function addLinkToContent(suggestion, callback) {
            var content = '';
            var editor = null;
            
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
                editor = tinymce.activeEditor;
                content = editor.getContent();
            } else {
                content = $('#content').val();
            }
            
            // Create the linked version
            var linkedText = '<a href="' + suggestion.target_url + '">' + suggestion.match_text + '</a>';
            
            // Replace first occurrence only (case-insensitive)
            var regex = new RegExp('(?<!<a[^>]*>)(' + escapeRegex(suggestion.match_text) + ')(?![^<]*</a>)', 'i');
            var newContent = content.replace(regex, linkedText);
            
            if (newContent !== content) {
                if (editor) {
                    editor.setContent(newContent);
                } else {
                    $('#content').val(newContent);
                }
                callback(true);
            } else {
                callback(false);
            }
        }
        
        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        function updateStats() {
            var remaining = $('.cta-linker-suggestion:visible').length;
            if (remaining === 0) {
                $('#cta-linker-results').html('<div class="cta-linker-empty">✅ All done! Your content is well-linked.</div>');
            } else {
                $('.cta-linker-stat-number').first().text(remaining);
            }
        }
    });
    </script>
    <?php
}

/**
 * AJAX handler for link scanning
 */
function ccs_linker_scan_ajax() {
    check_ajax_referer('ccs_linker_nonce', 'nonce');
    
    // SECURITY: Ensure we're in admin context
    if (!is_admin()) {
        wp_send_json_error('Invalid request', 403);
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }
    
    $content = sanitize_textarea_field($_POST['content']);
    $post_id = intval($_POST['post_id']);
    
    if (empty($content)) {
        wp_send_json_error('No content to analyse');
    }
    
    $suggestions = [];
    
    // Get all courses
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    // Get all other posts (excluding current)
    $posts = get_posts([
        'post_type' => 'post',
        'posts_per_page' => 50,
        'post_status' => 'publish',
        'exclude' => [$post_id]
    ]);
    
    // Get all pages (excluding current)
    $pages = get_posts([
        'post_type' => 'page',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'exclude' => [$post_id]
    ]);
    
    // Keywords to look for (course-related)
    $link_targets = [];
    
    // Add courses with their keywords
    foreach ($courses as $course) {
        $keywords = ccs_extract_keywords($course->post_title);
        foreach ($keywords as $keyword) {
            $link_targets[] = [
                'keyword' => $keyword,
                'title' => $course->post_title,
                'url' => get_permalink($course->ID),
                'type' => 'course',
                'priority' => 10
            ];
        }
    }
    
    // Add posts with their keywords
    foreach ($posts as $p) {
        $keywords = ccs_extract_keywords($p->post_title);
        foreach ($keywords as $keyword) {
            $link_targets[] = [
                'keyword' => $keyword,
                'title' => $p->post_title,
                'url' => get_permalink($p->ID),
                'type' => 'post',
                'priority' => 5
            ];
        }
    }
    
    // Add pages with their keywords
    foreach ($pages as $page) {
        $keywords = ccs_extract_keywords($page->post_title);
        foreach ($keywords as $keyword) {
            $link_targets[] = [
                'keyword' => $keyword,
                'title' => $page->post_title,
                'url' => get_permalink($page->ID),
                'type' => 'page',
                'priority' => 7
            ];
        }
    }
    
    // Add common care sector terms that should link to courses
    $common_terms = [
        'first aid' => 'Emergency & First Aid',
        'manual handling' => 'Moving & Handling',
        'moving and handling' => 'Moving & Handling',
        'safeguarding' => 'Safeguarding',
        'medication' => 'Medication Management',
        'food hygiene' => 'Food Hygiene',
        'infection control' => 'Infection Control',
        'dementia' => 'Dementia Awareness',
        'diabetes' => 'Diabetes Awareness',
        'epilepsy' => 'Epilepsy Awareness',
        'cqc' => 'CQC Compliance',
        'care certificate' => 'Care Certificate',
        'bls' => 'Basic Life Support',
        'cpr' => 'Basic Life Support',
    ];
    
    foreach ($common_terms as $term => $course_name) {
        // Find matching course
        foreach ($courses as $course) {
            if (stripos($course->post_title, $course_name) !== false || 
                stripos($course->post_title, $term) !== false) {
                $link_targets[] = [
                    'keyword' => $term,
                    'title' => $course->post_title,
                    'url' => get_permalink($course->ID),
                    'type' => 'course',
                    'priority' => 15
                ];
                break;
            }
        }
    }
    
    // Sort by priority (highest first) and keyword length (longest first for better matches)
    usort($link_targets, function($a, $b) {
        if ($a['priority'] !== $b['priority']) {
            return $b['priority'] - $a['priority'];
        }
        return strlen($b['keyword']) - strlen($a['keyword']);
    });
    
    // Find matches in content
    $content_lower = strtolower($content);
    $found_positions = []; // Track positions to avoid overlapping suggestions
    
    foreach ($link_targets as $target) {
        $keyword = strtolower($target['keyword']);
        
        // Skip if keyword is too short
        if (strlen($keyword) < 4) continue;
        
        // Find keyword in content (not already linked)
        $pos = stripos($content_lower, $keyword);
        
        if ($pos !== false) {
            // Check if this position overlaps with existing suggestion
            $overlaps = false;
            foreach ($found_positions as $fp) {
                if ($pos >= $fp['start'] && $pos <= $fp['end']) {
                    $overlaps = true;
                    break;
                }
            }
            
            if (!$overlaps) {
                // Check if already linked (simple check)
                $before = substr($content, max(0, $pos - 50), 50);
                if (stripos($before, '<a ') !== false && stripos($before, '</a>') === false) {
                    continue; // Likely inside a link
                }
                
                // Get context
                $context_start = max(0, $pos - 30);
                $context_end = min(strlen($content), $pos + strlen($target['keyword']) + 30);
                $context = substr($content, $context_start, $context_end - $context_start);
                
                // Get the exact matched text (preserving case)
                $match_text = substr($content, $pos, strlen($target['keyword']));
                
                $suggestions[] = [
                    'match_text' => $match_text,
                    'context' => trim($context),
                    'target_title' => $target['title'],
                    'target_url' => $target['url'],
                    'type' => $target['type'],
                    'position' => $pos
                ];
                
                $found_positions[] = [
                    'start' => $pos,
                    'end' => $pos + strlen($keyword)
                ];
                
                // Limit suggestions
                if (count($suggestions) >= 10) break;
            }
        }
    }
    
    // Sort by position in content
    usort($suggestions, function($a, $b) {
        return $a['position'] - $b['position'];
    });
    
    wp_send_json_success([
        'suggestions' => $suggestions,
        'total_targets' => count($link_targets)
    ]);
}
add_action('wp_ajax_ccs_linker_scan', 'ccs_linker_scan_ajax');

/**
 * Extract keywords from a title
 */
function ccs_extract_keywords($title) {
    $keywords = [];
    
    // Full title (cleaned)
    $clean_title = preg_replace('/\s*[-–—]\s*.*$/', '', $title); // Remove subtitle after dash
    $clean_title = trim($clean_title);
    
    if (strlen($clean_title) >= 4) {
        $keywords[] = $clean_title;
    }
    
    // Key phrases (2-3 words)
    $words = preg_split('/\s+/', strtolower($clean_title));
    $stop_words = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'shall', 'can', 'need', 'dare', 'ought', 'used', 'level', 'training', 'course', 'awareness'];
    
    // Filter out stop words for meaningful keywords
    $meaningful_words = array_filter($words, function($w) use ($stop_words) {
        return strlen($w) >= 3 && !in_array($w, $stop_words);
    });
    
    // Add 2-word combinations
    $meaningful_words = array_values($meaningful_words);
    for ($i = 0; $i < count($meaningful_words) - 1; $i++) {
        $phrase = $meaningful_words[$i] . ' ' . $meaningful_words[$i + 1];
        if (strlen($phrase) >= 6) {
            $keywords[] = $phrase;
        }
    }
    
    return array_unique($keywords);
}

/**
 * Auto-link content on save (optional - disabled by default)
 * Uncomment the add_action line to enable
 */
function ccs_auto_link_on_save($post_id, $post) {
    // Only for posts
    if ($post->post_type !== 'post') return;
    
    // Check if auto-linking is enabled
    if (!get_option('ccs_auto_link_enabled', false)) return;
    
    // Don't run on autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) return;
    
    // Implement auto-linking logic here if needed
}
// add_action('save_post', 'ccs_auto_link_on_save', 10, 2);

