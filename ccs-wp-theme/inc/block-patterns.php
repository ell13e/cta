<?php
/**
 * Block Patterns
 * Pre-built content patterns users can insert with one click
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Register block pattern category
 */
function ccs_register_pattern_category() {
    register_block_pattern_category('cta-patterns', [
        'label' => __('CTA Patterns', 'ccs-theme'),
        'description' => __('Pre-designed content blocks for Continuity of Care Services', 'ccs-theme'),
    ]);
}
add_action('init', 'ccs_register_pattern_category');

/**
 * Register block patterns
 */
function ccs_register_block_patterns() {
    
    // Call-to-Action Box
    $courses_url = esc_url(get_post_type_archive_link('course') ?: home_url('/courses/'));
    
    register_block_pattern('ccs-theme/cta-box', [
        'title' => __('Call to Action Box', 'ccs-theme'),
        'description' => __('A highlighted box with a call to action', 'ccs-theme'),
        'categories' => ['cta-patterns'],
        'keywords' => ['cta', 'call to action', 'button', 'highlight'],
        'content' => '<!-- wp:group {"className":"article-cta-section","style":{"spacing":{"padding":{"top":"2rem","bottom":"2rem","left":"2rem","right":"2rem"}},"border":{"width":"2px"}},"borderColor":"luminous-vivid-amber","backgroundColor":"pale-cyan-blue"} -->
<div class="wp-block-group article-cta-section has-border-color has-luminous-vivid-amber-border-color has-pale-cyan-blue-background-color has-background" style="border-width:2px;padding-top:2rem;padding-right:2rem;padding-bottom:2rem;padding-left:2rem">

<!-- wp:heading {"level":3,"className":"article-cta-title"} -->
<h3 class="wp-block-heading article-cta-title">Ready to Get Started?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"article-cta-intro"} -->
<p class="article-cta-intro">Discover how our training courses can help your team deliver outstanding care.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"left"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"className":"article-cta-btn"} -->
<div class="wp-block-button article-cta-btn"><a class="wp-block-button__link wp-element-button" href="' . $courses_url . '">View Our Courses</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

</div>
<!-- /wp:group -->',
    ]);

    // Checklist with ticks
    register_block_pattern('ccs-theme/checklist', [
        'title' => __('Checklist', 'ccs-theme'),
        'description' => __('A styled list with checkmarks', 'ccs-theme'),
        'categories' => ['cta-patterns'],
        'keywords' => ['list', 'checklist', 'ticks', 'benefits'],
        'content' => '<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">What You\'ll Learn</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list">
<li>✓ Understanding CQC requirements and compliance</li>
<li>✓ Practical skills for real-world care situations</li>
<li>✓ Evidence-based best practices</li>
<li>✓ CPD-accredited certification on completion</li>
</ul>
<!-- /wp:list -->',
    ]);

    // Key Points Box
    register_block_pattern('ccs-theme/key-points', [
        'title' => __('Key Points Box', 'ccs-theme'),
        'description' => __('Highlight key takeaways from your article', 'ccs-theme'),
        'categories' => ['cta-patterns'],
        'keywords' => ['key points', 'summary', 'takeaways', 'highlight'],
        'content' => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}},"border":{"left":{"width":"4px"}}},"backgroundColor":"cyan-bluish-gray"} -->
<div class="wp-block-group has-cyan-bluish-gray-background-color has-background" style="border-left-width:4px;padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">

<!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">💡 Key Points</h4>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list">
<li>First key point from this section</li>
<li>Second key point to remember</li>
<li>Third important takeaway</li>
</ul>
<!-- /wp:list -->

</div>
<!-- /wp:group -->',
    ]);

    // Quote with Source
    register_block_pattern('ccs-theme/quote-with-source', [
        'title' => __('Quote with Source', 'ccs-theme'),
        'description' => __('A styled quote with attribution', 'ccs-theme'),
        'categories' => ['cta-patterns'],
        'keywords' => ['quote', 'citation', 'source', 'reference'],
        'content' => '<!-- wp:quote -->
<blockquote class="wp-block-quote">
<p>"Quality care starts with quality training. When staff feel confident and competent, residents receive better care."</p>
<cite>Care Quality Commission</cite>
</blockquote>
<!-- /wp:quote -->',
    ]);

    // Two Column Comparison
    register_block_pattern('ccs-theme/comparison', [
        'title' => __('Comparison Columns', 'ccs-theme'),
        'description' => __('Compare two options side by side', 'ccs-theme'),
        'categories' => ['cta-patterns'],
        'keywords' => ['comparison', 'columns', 'vs', 'before after'],
        'content' => '<!-- wp:columns -->
<div class="wp-block-columns">

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">❌ Without Training</h4>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list">
<li>Inconsistent care quality</li>
<li>Higher risk of incidents</li>
<li>Staff uncertainty</li>
<li>Compliance concerns</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">✅ With Training</h4>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list">
<li>Consistent, high-quality care</li>
<li>Reduced incidents</li>
<li>Confident, competent staff</li>
<li>CQC compliance assured</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->',
    ]);

    // Statistics/Numbers
    register_block_pattern('ccs-theme/statistics', [
        'title' => __('Statistics Row', 'ccs-theme'),
        'description' => __('Display impressive numbers or statistics', 'ccs-theme'),
        'categories' => ['cta-patterns'],
        'keywords' => ['statistics', 'numbers', 'stats', 'data'],
        'content' => '<!-- wp:columns {"style":{"spacing":{"padding":{"top":"2rem","bottom":"2rem"}}}} -->
<div class="wp-block-columns" style="padding-top:2rem;padding-bottom:2rem">

<!-- wp:column {"style":{"spacing":{"padding":{"top":"1rem","bottom":"1rem"}}}} -->
<div class="wp-block-column" style="padding-top:1rem;padding-bottom:1rem">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"2.5rem","fontStyle":"normal","fontWeight":"700"}}} -->
<p class="has-text-align-center" style="font-size:2.5rem;font-style:normal;font-weight:700">5,000+</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Care workers trained</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"1rem","bottom":"1rem"}}}} -->
<div class="wp-block-column" style="padding-top:1rem;padding-bottom:1rem">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"2.5rem","fontStyle":"normal","fontWeight":"700"}}} -->
<p class="has-text-align-center" style="font-size:2.5rem;font-style:normal;font-weight:700">98%</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Pass rate</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"1rem","bottom":"1rem"}}}} -->
<div class="wp-block-column" style="padding-top:1rem;padding-bottom:1rem">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"2.5rem","fontStyle":"normal","fontWeight":"700"}}} -->
<p class="has-text-align-center" style="font-size:2.5rem;font-style:normal;font-weight:700">50+</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Courses available</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->',
    ]);

    // Course Link Card
    register_block_pattern('ccs-theme/course-link', [
        'title' => __('Course Link Card', 'ccs-theme'),
        'description' => __('Link to a related course', 'ccs-theme'),
        'categories' => ['cta-patterns'],
        'keywords' => ['course', 'link', 'training', 'related'],
        'content' => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}},"border":{"radius":"8px"}},"backgroundColor":"cyan-bluish-gray"} -->
<div class="wp-block-group has-cyan-bluish-gray-background-color has-background" style="border-radius:8px;padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">

<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.875rem","fontStyle":"normal","fontWeight":"600"}},"textColor":"vivid-cyan-blue"} -->
<p class="has-vivid-cyan-blue-color has-text-color" style="font-size:0.875rem;font-style:normal;font-weight:600">📚 RELATED COURSE</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading"><a href="' . $courses_url . '">Course Name Here</a></h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.9rem"}}} -->
<p style="font-size:0.9rem">Brief description of the course and what attendees will learn.</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->',
    ]);

}
add_action('init', 'ccs_register_block_patterns');

