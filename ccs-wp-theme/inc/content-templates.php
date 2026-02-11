<?php
/**
 * Content Templates
 * 
 * SEO-optimized content templates for common page types
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Get CQC article content template
 * 
 * @param string $topic Article topic
 * @return string Content template
 */
function ccs_get_cqc_article_template($topic = 'requirements') {
    $templates = [
        'requirements' => <<<HTML
<h2>What Training Do Care Homes Need for CQC Compliance?</h2>

<p>The Care Quality Commission (CQC) sets mandatory training requirements for all care providers in England. Understanding these requirements is essential for maintaining compliance and ensuring the safety and wellbeing of those in your care.</p>

<h3>Mandatory CQC Training Requirements</h3>

<p>All care home staff must complete the following mandatory training:</p>

<ul>
<li><strong>Care Certificate:</strong> Required for all new care workers within 12 weeks of starting</li>
<li><strong>Safeguarding Adults:</strong> Essential for protecting vulnerable adults from harm</li>
<li><strong>Health & Safety:</strong> Workplace safety and risk management</li>
<li><strong>Fire Safety:</strong> Fire prevention and evacuation procedures</li>
<li><strong>Manual Handling:</strong> Safe moving and handling techniques</li>
<li><strong>First Aid:</strong> Emergency first aid response</li>
<li><strong>Food Hygiene:</strong> Safe food handling and preparation</li>
<li><strong>Infection Control:</strong> Preventing the spread of infections</li>
<li><strong>Medication Management:</strong> Safe administration and handling of medicines</li>
</ul>

<h3>Training Refresh Requirements</h3>

<p>Most mandatory training should be refreshed annually, though some courses have different refresh periods:</p>

<ul>
<li>First Aid certificates typically last 3 years</li>
<li>Safeguarding training should be refreshed every 2-3 years</li>
<li>Fire Safety and Health & Safety should be refreshed annually</li>
</ul>

<h3>How to Ensure Compliance</h3>

<p>To maintain CQC compliance:</p>

<ol>
<li>Keep detailed training records for all staff members</li>
<li>Schedule refresher training before certificates expire</li>
<li>Ensure all new staff complete the Care Certificate within 12 weeks</li>
<li>Provide evidence of training during CQC inspections</li>
</ol>

<p>Our CQC-compliant training courses help care providers meet these requirements. <a href="/courses/">View our training courses</a> or <a href="/contact/">contact us</a> to discuss your training needs.</p>
HTML,
        
        'inspection' => <<<HTML
<h2>How to Prepare Staff for a CQC Inspection</h2>

<p>CQC inspections can be stressful, but with proper preparation, your team can demonstrate compliance and showcase the quality of care you provide. Here's how to prepare your staff for a successful inspection.</p>

<h3>Before the Inspection</h3>

<h4>1. Review Training Records</h4>
<p>Ensure all staff training records are up to date and easily accessible. CQC inspectors will review:</p>
<ul>
<li>Mandatory training completion certificates</li>
<li>Training refresh dates</li>
<li>Care Certificate completion for new staff</li>
</ul>

<h4>2. Update Policies and Procedures</h4>
<p>Review and update all policies and procedures to ensure they reflect current CQC standards and best practices.</p>

<h4>3. Staff Briefing</h4>
<p>Brief all staff on what to expect during the inspection and how to answer inspector questions confidently.</p>

<h3>During the Inspection</h3>

<h4>1. Be Open and Transparent</h4>
<p>Inspectors appreciate honesty. If there are areas for improvement, discuss them openly and show your action plan.</p>

<h4>2. Demonstrate Training</h4>
<p>Show inspectors evidence of ongoing training and professional development. Highlight any recent training initiatives.</p>

<h4>3. Showcase Best Practice</h4>
<p>Use the inspection as an opportunity to showcase examples of excellent care and staff development.</p>

<h3>After the Inspection</h3>

<p>Review the inspector's feedback and create an action plan for any areas identified for improvement. Use this as a learning opportunity to enhance your service.</p>

<p>Our <a href="/courses/">CQC compliance training courses</a> can help prepare your team for inspections and ensure ongoing compliance.</p>
HTML,
        
        'training-mandates' => <<<HTML
<h2>CQC Training Mandates: What You Need to Know</h2>

<p>The Care Quality Commission requires all care providers to ensure their staff have appropriate training and qualifications. Understanding these mandates is crucial for maintaining compliance.</p>

<h3>Core Training Mandates</h3>

<p>The CQC expects all care providers to:</p>

<ul>
<li>Ensure all staff have the skills, knowledge, and experience to provide safe, effective care</li>
<li>Provide ongoing training and professional development</li>
<li>Maintain accurate training records</li>
<li>Ensure training is refreshed as required</li>
</ul>

<h3>Specific Training Requirements</h3>

<p>While the CQC doesn't prescribe specific training providers, they do require evidence that staff have received appropriate training in:</p>

<ul>
<li>Care Certificate (for new care workers)</li>
<li>Safeguarding</li>
<li>Health & Safety</li>
<li>Infection Control</li>
<li>Medication Management (where applicable)</li>
</ul>

<h3>Training Records</h3>

<p>Maintain detailed records including:</p>
<ul>
<li>Training certificates</li>
<li>Training dates</li>
<li>Refresh dates</li>
<li>Training provider details</li>
</ul>

<p>Our <a href="/courses/">accredited training courses</a> provide the certificates and documentation you need to demonstrate CQC compliance.</p>
HTML,
    ];
    
    return $templates[$topic] ?? $templates['requirements'];
}

/**
 * Get FAQ content template
 * 
 * @param string $category FAQ category
 * @return array FAQ items
 */
function ccs_get_faq_template($category = 'general') {
    $templates = [
        'general' => [
            [
                'question' => 'What training do care workers need?',
                'answer' => 'Care workers need mandatory training including the Care Certificate, Safeguarding, Health & Safety, Fire Safety, Manual Handling, First Aid, Food Hygiene, Infection Control, and Medication Management (where applicable).',
            ],
            [
                'question' => 'How often does care training need to be refreshed?',
                'answer' => 'Most mandatory training should be refreshed annually. First Aid certificates typically last 3 years, and Safeguarding should be refreshed every 2-3 years. Check specific course requirements for exact refresh periods.',
            ],
            [
                'question' => 'Is online training accepted by CQC?',
                'answer' => 'CQC accepts a mix of online and face-to-face training, but some topics (like practical first aid) require hands-on training. Our face-to-face courses ensure all practical elements are covered to CQC standards.',
            ],
        ],
        'pricing' => [
            [
                'question' => 'How much does care training cost?',
                'answer' => 'Training costs vary by course. Our courses start from £45 per person for group training. Individual course prices are listed on each course page. Contact us for group discounts and custom training packages.',
            ],
            [
                'question' => 'Do you offer group discounts?',
                'answer' => 'Yes, we offer discounts for group bookings. The more staff you train together, the better the rate. Contact us for a custom quote based on your team size and training needs.',
            ],
        ],
        'scheduling' => [
            [
                'question' => 'How far in advance should I book training?',
                'answer' => 'We recommend booking at least 2-3 weeks in advance to secure your preferred date. However, we understand training needs can be urgent, so we\'ll do our best to accommodate shorter notice bookings when possible.',
            ],
            [
                'question' => 'Do you offer training on evenings or weekends?',
                'answer' => 'Yes, we offer flexible scheduling including evening sessions, weekend training, and training during shift patterns. When you request a quote, let us know your preferred dates and times.',
            ],
        ],
    ];
    
    return $templates[$category] ?? $templates['general'];
}

/**
 * Get course comparison template
 * 
 * @param array $courses Array of course data to compare
 * @return string Comparison content
 */
function ccs_get_course_comparison_template($courses = []) {
    if (empty($courses)) {
        return '';
    }
    
    $output = '<h2>Course Comparison</h2>' . "\n";
    $output .= '<p>Compare our training courses to find the right option for your needs:</p>' . "\n\n";
    $output .= '<table class="course-comparison-table">' . "\n";
    $output .= '<thead>' . "\n";
    $output .= '<tr>' . "\n";
    $output .= '<th>Course</th>' . "\n";
    $output .= '<th>Duration</th>' . "\n";
    $output .= '<th>Price</th>' . "\n";
    $output .= '<th>Accreditation</th>' . "\n";
    $output .= '</tr>' . "\n";
    $output .= '</thead>' . "\n";
    $output .= '<tbody>' . "\n";
    
    foreach ($courses as $course) {
        $output .= '<tr>' . "\n";
        $output .= '<td><strong>' . esc_html($course['name'] ?? '') . '</strong></td>' . "\n";
        $output .= '<td>' . esc_html($course['duration'] ?? 'N/A') . '</td>' . "\n";
        $output .= '<td>' . ($course['price'] ? '£' . number_format($course['price'], 0) : 'Contact us') . '</td>' . "\n";
        $output .= '<td>' . esc_html($course['accreditation'] ?? 'CPD') . '</td>' . "\n";
        $output .= '</tr>' . "\n";
    }
    
    $output .= '</tbody>' . "\n";
    $output .= '</table>' . "\n";
    
    return $output;
}

/**
 * Get SEO-optimized page introduction template
 * 
 * @param string $page_type Type of page
 * @param array $context Additional context data
 * @return string Introduction content
 */
function ccs_get_page_intro_template($page_type, $context = []) {
    $templates = [
        'location' => function($ctx) {
            $location = $ctx['location'] ?? 'Kent';
            return "Professional care training courses in {$location}. CQC-compliant, CPD-accredited training delivered by experienced trainers. Face-to-face and on-site training options available.";
        },
        'cqc' => "Everything you need to know about CQC requirements, inspections, and mandatory training for care providers. Stay compliant with our CQC-compliant training courses.",
        'course' => function($ctx) {
            $course = $ctx['course'] ?? 'this course';
            return "Learn about {$course} - a CQC-compliant, CPD-accredited training course designed for care sector professionals.";
        },
    ];
    
    $template = $templates[$page_type] ?? '';
    
    if (is_callable($template)) {
        return $template($context);
    }
    
    return $template;
}

/**
 * Get call-to-action template
 * 
 * @param string $type CTA type
 * @return string CTA content
 */
function ccs_get_ccs_template($type = 'default') {
    $templates = [
        'default' => '<p><a href="/contact/" class="btn btn-primary">Contact Us</a> <a href="/upcoming-courses/" class="btn btn-secondary">View Courses</a></p>',
        'booking' => '<p><a href="/upcoming-courses/" class="btn btn-primary">Book Your Training</a> or <a href="/contact/">contact us</a> to discuss your needs.</p>',
        'quote' => '<p><a href="/group-training/" class="btn btn-primary">Get a Free Quote</a> for group training or <a href="/contact/">contact us</a> to discuss your requirements.</p>',
    ];
    
    return $templates[$type] ?? $templates['default'];
}
