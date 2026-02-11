<?php
/**
 * Newsletter Automation Flows
 * 
 * Mailchimp-style automation flows with triggers, rules, and actions
 * Visual flow builder for creating automated email sequences
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Create automation flow tables
 */
function ccs_create_automation_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Automation flows table
    $flows_table = $wpdb->prefix . 'ccs_automation_flows';
    $flows_sql = "CREATE TABLE IF NOT EXISTS $flows_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        status varchar(20) NOT NULL DEFAULT 'draft',
        trigger_type varchar(50) NOT NULL,
        trigger_config longtext,
        flow_data longtext,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        activated_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY status (status),
        KEY trigger_type (trigger_type)
    ) $charset_collate;";
    
    // Flow steps table (for tracking individual steps in flows)
    $steps_table = $wpdb->prefix . 'ccs_automation_steps';
    $steps_sql = "CREATE TABLE IF NOT EXISTS $steps_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        flow_id bigint(20) unsigned NOT NULL,
        step_type varchar(50) NOT NULL,
        step_order int(11) NOT NULL,
        step_config longtext,
        parent_step_id bigint(20) unsigned DEFAULT NULL,
        branch_path varchar(20) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY flow_id (flow_id),
        KEY parent_step_id (parent_step_id)
    ) $charset_collate;";
    
    // Flow contacts table (tracks which contacts are in which flows)
    $contacts_table = $wpdb->prefix . 'ccs_automation_contacts';
    $contacts_sql = "CREATE TABLE IF NOT EXISTS $contacts_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        flow_id bigint(20) unsigned NOT NULL,
        subscriber_id bigint(20) unsigned NOT NULL,
        current_step_id bigint(20) unsigned DEFAULT NULL,
        entered_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completed_at datetime DEFAULT NULL,
        exited_at datetime DEFAULT NULL,
        exit_reason varchar(100) DEFAULT NULL,
        metadata longtext,
        PRIMARY KEY (id),
        UNIQUE KEY flow_subscriber (flow_id, subscriber_id),
        KEY flow_id (flow_id),
        KEY subscriber_id (subscriber_id),
        KEY current_step_id (current_step_id)
    ) $charset_collate;";
    
    // Email templates table
    $templates_table = $wpdb->prefix . 'ccs_email_templates';
    $templates_sql = "CREATE TABLE IF NOT EXISTS $templates_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        subject varchar(500) NOT NULL,
        content longtext NOT NULL,
        template_type varchar(50) DEFAULT 'custom',
        category varchar(100) DEFAULT NULL,
        is_system tinyint(1) DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        created_by bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (id),
        KEY template_type (template_type),
        KEY category (category)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($flows_sql);
    dbDelta($steps_sql);
    dbDelta($contacts_sql);
    dbDelta($templates_sql);
    
    // Create default templates if they don't exist
    ccs_create_default_email_templates();
    
    // Create default automations if they don't exist
    ccs_create_default_automations();
}
add_action('after_switch_theme', 'ccs_create_automation_tables');
add_action('after_switch_theme', 'ccs_create_default_email_templates', 20);
add_action('after_switch_theme', 'ccs_create_default_automations', 25);
// Also run on admin init to ensure templates and automations exist (for existing installations)
add_action('admin_init', function() {
    global $wpdb;
    $templates_table = $wpdb->prefix . 'ccs_email_templates';
    $flows_table = $wpdb->prefix . 'ccs_automation_flows';
    
    // Check if tables exist
    $templates_exist = $wpdb->get_var("SHOW TABLES LIKE '$templates_table'") === $templates_table;
    $flows_exist = $wpdb->get_var("SHOW TABLES LIKE '$flows_table'") === $flows_table;
    
    if ($templates_exist && $flows_exist) {
        // Only check once per day to avoid performance issues
        $last_check = get_transient('ccs_defaults_check');
        if (!$last_check) {
            ccs_create_default_email_templates();
            ccs_create_default_automations();
            set_transient('ccs_defaults_check', true, DAY_IN_SECONDS);
        }
    } else if (!$templates_exist || !$flows_exist) {
        // Tables don't exist, create them
        ccs_create_automation_tables();
    }
});

/**
 * Create default email templates from existing templates
 */
function ccs_create_default_email_templates() {
    global $wpdb;
    $templates_table = $wpdb->prefix . 'ccs_email_templates';
    
    $default_templates = [
        [
            'name' => 'New Course Announcement',
            'description' => 'Announce a new training course to subscribers',
            'subject' => 'New Course: [Course Name] - Pass Your Next CQC Inspection',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Your next CQC inspection is coming. Will your team be ready?</strong></p>
<p>We\'ve just launched a new course that gives care professionals exactly what they need to pass inspections with confidence, not anxiety.</p>
<div style="background: #f9f9f9; border-left: 4px solid #3ba59b; padding: 20px; margin: 24px 0; border-radius: 4px;">
<p style="margin: 0 0 12px 0;"><strong style="font-size: 18px; color: #2b1b0e;">[Course details will be inserted here]</strong></p>
<p style="margin: 0 0 8px 0; color: #646970; line-height: 1.6;">This course will help you:</p>
<ul style="margin: 8px 0 0 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>Meet CQC requirements</strong> with confidence, with no more last-minute panic</li>
<li><strong>Build team competence</strong> so everyone knows exactly what inspectors look for</li>
<li><strong>Deliver better care outcomes</strong> that show in your inspection reports</li>
</ul>
</div>
<p><strong>Imagine walking into your next inspection knowing your team is prepared.</strong> No scrambling. No stress. Just confidence.</p>
<p>This CPD-accredited course is trusted by care providers across Kent and the UK. Many book 2-3 months before their inspection dates to ensure their team is ready.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="#" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Reserve Your Team\'s Place →</a></p>
<p><strong>Spaces fill up quickly</strong>, especially in the months leading up to inspection periods. Secure your spot now to avoid disappointment.</p>
<p>Questions? Just reply to this email. We\'re here to help.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Early booking means you get the dates that work for your team\'s schedule. Don\'t wait until the last minute.</p>',
            'template_type' => 'new_course',
            'category' => 'new_course',
            'is_system' => 1,
        ],
        [
            'name' => 'New Course: Last-Minute Spaces',
            'description' => 'Promote a course with limited spaces remaining (scarcity, urgency)',
            'subject' => 'Last few spaces: [Course Name] – book today',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Quick heads-up: we have just a few spaces left on <em>[Course Name]</em>.</strong></p>
<p>If you’ve been meaning to get this booked for your team (or you’ve got an inspection window coming up), this is the easiest way to lock in the date.</p>
<div style="background: #f9f9f9; border: 1px solid #e0e0e0; padding: 16px; margin: 20px 0; border-radius: 4px;">
<p style="margin: 0 0 8px 0;"><strong>[Course details will be inserted here]</strong></p>
<ul style="margin: 0; padding-left: 20px; color: #646970; line-height: 1.7;">
<li>Practical, care-sector focused</li>
<li>Supports compliance and confidence in practice</li>
<li>Designed for busy teams (clear, usable takeaways)</li>
</ul>
</div>
<p style="margin: 24px 0; text-align: center;"><a href="#" style="display: inline-block; background: #2271b1; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 600;">Book the remaining spaces →</a></p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 12px;">{unsubscribe_link}</p>',
            'template_type' => 'new_course_last_minute',
            'category' => 'new_course',
            'is_system' => 1,
        ],
        [
            'name' => 'New Course: For Managers (ROI + compliance)',
            'description' => 'Position a new course for managers (evidence, risk reduction, staff confidence)',
            'subject' => 'New course: reduce compliance risk on [topic]',
            'content' => '<p>Hi {first_name},</p>
<p><strong>If you’re responsible for compliance, you know the pain: training gaps get found at the worst possible moment.</strong></p>
<p>We’ve launched a new course that helps you turn training into evidence you can actually use—so you’re not scrambling when you need it.</p>
<div style="background: #f6f7f7; border: 1px solid #dcdcde; padding: 16px; margin: 20px 0; border-radius: 4px;">
<p style="margin: 0 0 8px 0;"><strong>[Course details will be inserted here]</strong></p>
<p style="margin: 0; color: #646970;">What you get:</p>
<ul style="margin: 8px 0 0 0; padding-left: 20px; color: #646970; line-height: 1.7;">
<li>Clear expectations for staff competence</li>
<li>Practical examples your team can apply immediately</li>
<li>Confidence that training stands up to scrutiny</li>
</ul>
</div>
<p style="margin: 24px 0; text-align: center;"><a href="#" style="display: inline-block; background: #2271b1; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 600;">View dates / book →</a></p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 12px;">{unsubscribe_link}</p>',
            'template_type' => 'new_course_manager_roi',
            'category' => 'new_course',
            'is_system' => 1,
        ],
        [
            'name' => 'New Article Published',
            'description' => 'Promote a new blog article or guide',
            'subject' => 'How to [Solve Problem]: 5-Minute Guide for Care Professionals',
            'content' => '<p>Hi {first_name},</p>
<p><strong>[Common challenge] is costing you time and stress. Here\'s how to fix it.</strong></p>
<p>We\'ve just published a practical guide that shows you exactly how to solve this problem, with steps you can implement this week.</p>
<div style="background: #f9f9f9; border-left: 4px solid #2271b1; padding: 20px; margin: 24px 0; border-radius: 4px;">
<p style="margin: 0 0 12px 0;"><strong style="font-size: 18px; color: #2b1b0e;">[Article title and summary will be inserted here]</strong></p>
<p style="margin: 0; color: #646970; line-height: 1.6;">You\'ll discover:</p>
<ul style="margin: 8px 0 0 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li>Practical strategies you can use immediately</li>
<li>Common mistakes to avoid</li>
<li>How this improves your CQC compliance</li>
</ul>
</div>
<p><strong>This isn\'t theory. It\'s what works.</strong> Based on latest CQC guidance and used by care providers who consistently pass their inspections.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="#" style="display: inline-block; background: linear-gradient(135deg, #2271b1 0%, #1e5a8f 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3);">Read the Full Guide →</a></p>
<p><strong>This 5-minute read could save you hours of work</strong> and help you avoid common compliance pitfalls.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Bookmark this for your next inspection prep. It\'s the kind of resource that makes the difference between a good inspection and a great one.</p>',
            'template_type' => 'new_article',
            'category' => 'new_article',
            'is_system' => 1,
        ],
        [
            'name' => 'New Article: Quick Checklist',
            'description' => 'Promote a short checklist-style article (scannable, practical)',
            'subject' => 'A quick checklist for your next audit (5 mins)',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Here’s a quick checklist you can use this week.</strong> It’s designed to be practical — not a long read.</p>
<div style="background: #f6f7f7; border: 1px solid #dcdcde; padding: 16px; margin: 20px 0; border-radius: 4px;">
<p style="margin: 0 0 8px 0;"><strong>[Article title and summary will be inserted here]</strong></p>
<p style="margin: 0; color: #646970;">Ideal for:</p>
<ul style="margin: 8px 0 0 0; padding-left: 20px; color: #646970; line-height: 1.7;">
<li>Managers doing quick spot-checks</li>
<li>Team leaders keeping standards consistent</li>
<li>Anyone prepping evidence ahead of inspection</li>
</ul>
</div>
<p style="margin: 24px 0; text-align: center;"><a href="#" style="display: inline-block; background: #2271b1; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 600;">Read the checklist →</a></p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 12px;">{unsubscribe_link}</p>',
            'template_type' => 'new_article_checklist',
            'category' => 'new_article',
            'is_system' => 1,
        ],
        [
            'name' => 'New Article: Compliance Update',
            'description' => 'Promote an article that explains regulatory/compliance changes',
            'subject' => 'Compliance update: what’s changed (and what to do)',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Things change quickly in care. Here’s what you need to know — and what to do next.</strong></p>
<div style="background: #fff; border: 1px solid #dcdcde; padding: 16px; margin: 20px 0; border-radius: 4px;">
<p style="margin: 0 0 8px 0;"><strong>[Article title and summary will be inserted here]</strong></p>
<p style="margin: 0; color: #646970;">Inside the guide:</p>
<ul style="margin: 8px 0 0 0; padding-left: 20px; color: #646970; line-height: 1.7;">
<li>What’s changed (plain English)</li>
<li>What inspectors tend to ask</li>
<li>The simplest next steps for your service</li>
</ul>
</div>
<p style="margin: 24px 0; text-align: center;"><a href="#" style="display: inline-block; background: #2271b1; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 600;">Read the update →</a></p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 12px;">{unsubscribe_link}</p>',
            'template_type' => 'new_article_compliance_update',
            'category' => 'new_article',
            'is_system' => 1,
        ],
        [
            'name' => 'Upcoming Courses Reminder',
            'description' => 'Remind subscribers about upcoming training courses',
            'subject' => 'Limited Spaces: Upcoming Training Courses This Month',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Your next CQC inspection is coming. Is your team ready?</strong></p>
<p>Here are our upcoming courses with spaces still available:</p>
<div style="background: #f9f9f9; padding: 20px; margin: 24px 0; border-radius: 4px; border: 1px solid #e0e0e0;">
<p style="margin: 0;"><strong>[List of upcoming courses]</strong></p>
</div>
<p><strong>Why book now instead of waiting?</strong></p>
<ul style="margin: 16px 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>You choose the dates</strong> that work for your team, not whatever\'s left</li>
<li><strong>Your staff are prepared</strong> well before inspection dates, reducing stress</li>
<li><strong>You avoid last-minute panic</strong> when courses are fully booked</li>
</ul>
<p>Many care providers book 2-3 months in advance. The courses that fill up fastest? The ones closest to common inspection periods.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/upcoming-courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">View All Upcoming Courses →</a></p>
<p>Secure your team\'s training dates now. Spaces are limited.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Need help choosing the right course for your team? Reply to this email and we\'ll recommend the best options for your care setting.</p>',
            'template_type' => 'upcoming_courses',
            'category' => 'upcoming_courses',
            'is_system' => 1,
        ],
        [
            'name' => 'Upcoming Courses: Monthly Roundup',
            'description' => 'Monthly roundup of upcoming courses (general reminder)',
            'subject' => 'Upcoming training dates: what’s available this month',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Here’s what’s coming up this month.</strong> If you’re planning training dates for your team, this is the easiest way to get it booked in.</p>
<div style="background: #f6f7f7; border: 1px solid #dcdcde; padding: 16px; margin: 20px 0; border-radius: 4px;">
{upcoming_courses_list}
</div>
<p style="margin: 24px 0; text-align: center;"><a href="' . esc_url(home_url('/upcoming-courses/')) . '" style="display: inline-block; background: #2271b1; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 600;">See all upcoming courses →</a></p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 12px;">{unsubscribe_link}</p>',
            'template_type' => 'upcoming_courses_monthly_roundup',
            'category' => 'upcoming_courses',
            'is_system' => 1,
        ],
        [
            'name' => 'Upcoming Courses: Compliance Focus',
            'description' => 'Highlight compliance-related courses (manager-focused)',
            'subject' => 'Training dates to support compliance (next few weeks)',
            'content' => '<p>Hi {first_name},</p>
<p><strong>If compliance is on your radar, these upcoming dates may help.</strong></p>
<p>Here are the next sessions with availability:</p>
<div style="background: #fff; border: 1px solid #dcdcde; padding: 16px; margin: 20px 0; border-radius: 4px;">
{upcoming_courses_list}
</div>
<p style="margin: 24px 0; text-align: center;"><a href="' . esc_url(home_url('/upcoming-courses/')) . '" style="display: inline-block; background: #2271b1; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 600;">View dates / book →</a></p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 12px;">{unsubscribe_link}</p>',
            'template_type' => 'upcoming_courses_compliance_focus',
            'category' => 'upcoming_courses',
            'is_system' => 1,
        ],
        [
            'name' => 'Quarterly Update',
            'description' => 'Send a quarterly newsletter update to subscribers',
            'subject' => 'Your Quarterly Update: What We\'ve Accomplished Together',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Thank you for being part of our community.</strong> Your commitment to professional development helps deliver better care across Kent and the UK.</p>
<h3 style="color: #2b1b0e; font-size: 20px; margin: 32px 0 16px 0;">What We\'ve Accomplished This Quarter</h3>
<div style="background: #f9f9f9; padding: 20px; margin: 24px 0; border-radius: 4px;">
<p style="margin: 0;"><strong>[Quarterly summary will be inserted here]</strong></p>
</div>
<p>Together, we\'re raising standards in care. <strong>Your dedication to continuous learning makes a real difference for your team, your residents, and the sector.</strong></p>
<p><strong>What\'s coming next quarter:</strong></p>
<ul style="margin: 16px 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>New courses</strong> designed around latest CQC guidance</li>
<li><strong>Practical resources</strong> to help you pass inspections with confidence</li>
<li><strong>Expert insights</strong> from industry leaders</li>
</ul>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Browse Our Courses →</a></p>
<p>We look forward to supporting your professional development in the months ahead.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Is there a specific topic or challenge you\'d like us to cover? Reply to this email. We read every response and use your feedback to shape what we create next.</p>',
            'template_type' => 'quarterly',
            'category' => 'quarterly',
            'is_system' => 1,
        ],
        [
            'name' => 'Quarterly: Training Planner (next quarter)',
            'description' => 'Quarterly planning email (what to book next quarter)',
            'subject' => 'Plan next quarter’s training dates (quick guide)',
            'content' => '<p>Hi {first_name},</p>
<p><strong>If you’re planning training dates for the next quarter, here’s a simple way to approach it.</strong></p>
<ol style="margin: 16px 0; padding-left: 20px; color: #646970; line-height: 1.7;">
<li>Confirm your priority roles and high-risk areas</li>
<li>Book core compliance training early (dates go first)</li>
<li>Add practical refreshers so learning sticks</li>
</ol>
<p style="margin: 24px 0; text-align: center;"><a href="' . esc_url(home_url('/upcoming-courses/')) . '" style="display: inline-block; background: #2271b1; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 600;">Browse upcoming dates →</a></p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 12px;">{unsubscribe_link}</p>',
            'template_type' => 'quarterly_training_planner',
            'category' => 'quarterly',
            'is_system' => 1,
        ],
        [
            'name' => 'Happy Birthday',
            'description' => 'Send a birthday message to subscribers',
            'subject' => '🎂 Happy Birthday, {first_name}!',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Happy Birthday! 🎉</strong></p>
<p>We wanted to take a moment to celebrate you today. Your dedication to professional development and continuous learning in the care sector makes a real difference—not just for your career, but for the people you care for every day.</p>
<div style="background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%); border-left: 4px solid #3ba59b; padding: 24px; margin: 24px 0; border-radius: 4px; text-align: center;">
<p style="margin: 0; font-size: 48px; line-height: 1;">🎂</p>
<p style="margin: 16px 0 0 0; font-size: 20px; color: #2b1b0e; font-weight: 600;">Wishing you a wonderful year ahead!</p>
</div>
<p>As a special birthday gift, we\'d like to offer you <strong>20% off any course</strong> booked in the next 30 days. Use code <strong>BIRTHDAY20</strong> at checkout.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Browse Our Courses →</a></p>
<p>Thank you for being part of our community. Here\'s to another year of growth, learning, and making a positive impact in care.</p>
<p>Best wishes,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> This offer is valid for 30 days from your birthday. Don\'t miss out!</p>',
            'template_type' => 'birthday',
            'category' => 'birthday',
            'is_system' => 1,
        ],
        [
            'name' => 'Welcome Email',
            'description' => 'Welcome new subscribers to your newsletter',
            'subject' => 'Welcome to Continuity of Care Services!',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Welcome to Continuity of Care Services!</strong></p>
<p>Thank you for subscribing to our newsletter. We\'re thrilled to have you join our community of care professionals who are committed to continuous learning and excellence in care delivery.</p>
<div style="background: linear-gradient(135deg, #f9f9f9 0%, #f0f0f0 100%); border-left: 4px solid #3ba59b; padding: 24px; margin: 24px 0; border-radius: 4px;">
<p style="margin: 0 0 16px 0; font-weight: 600; color: #2b1b0e; font-size: 18px;">What you can expect from us:</p>
<ul style="margin: 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>Latest CQC guidance</strong> and compliance updates</li>
<li><strong>Practical training courses</strong> to help you and your team excel</li>
<li><strong>Expert insights</strong> from industry leaders</li>
<li><strong>Resources and tools</strong> to make your job easier</li>
</ul>
</div>
<p>We know how busy you are, so we promise to only send you content that\'s genuinely valuable—no fluff, just practical insights that help you deliver better care and pass inspections with confidence.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Browse Our Courses →</a></p>
<p>If you have any questions or topics you\'d like us to cover, just reply to this email. We read every message and use your feedback to shape what we create.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> Follow us on social media for daily tips and updates. We\'re here to support your professional development journey.</p>',
            'template_type' => 'welcome',
            'category' => 'welcome',
            'is_system' => 1,
        ],
        [
            'name' => 'Welcome: What to expect (set cadence + value)',
            'description' => 'Alternate welcome email focused on expectations and trust-building',
            'subject' => 'Welcome — what you’ll get from us (and what you won’t)',
            'content' => '<p>Hi {first_name},</p>
<p><strong>Thanks for joining the CTA newsletter.</strong></p>
<p>We know inboxes are busy. Here’s what you can expect from us:</p>
<ul style="margin: 16px 0; padding-left: 20px; color: #646970; line-height: 1.7;">
<li>Practical training updates and upcoming dates</li>
<li>Short resources you can actually use at work</li>
<li>Clear explanations when guidance changes</li>
</ul>
<p>And what you won’t get: fluff, spam, or daily emails.</p>
<p style="margin: 24px 0; text-align: center;"><a href="' . esc_url(home_url('/courses/')) . '" style="display: inline-block; background: #2271b1; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: 600;">Browse courses →</a></p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 12px;">{unsubscribe_link}</p>',
            'template_type' => 'welcome_expectations',
            'category' => 'welcome',
            'is_system' => 1,
        ],
        [
            'name' => 'Special Offer / Sale',
            'description' => 'Promote a special discount or sale to subscribers',
            'subject' => '🎁 Special Offer: [Discount]% Off Training Courses',
            'content' => '<p>Hi {first_name},</p>
<p><strong>We have a special offer just for you!</strong></p>
<p>As a valued member of our community, we\'re offering you <strong>[Discount]% off</strong> all training courses for a limited time.</p>
<div style="background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%); border: 2px solid #ffc107; padding: 24px; margin: 24px 0; border-radius: 4px; text-align: center;">
<p style="margin: 0 0 8px 0; font-size: 32px; font-weight: 700; color: #856404;">[Discount]% OFF</p>
<p style="margin: 0; font-size: 16px; color: #856404; font-weight: 600;">Use code: <strong style="font-size: 18px; letter-spacing: 1px;">[DISCOUNT_CODE]</strong></p>
<p style="margin: 16px 0 0 0; font-size: 14px; color: #856404;">Valid until [End Date]</p>
</div>
<p><strong>Why book now?</strong></p>
<ul style="margin: 16px 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li><strong>Save money</strong> on essential CQC compliance training</li>
<li><strong>Secure your dates</strong> before spaces fill up</li>
<li><strong>Invest in your team</strong> at a reduced rate</li>
<li><strong>Limited time offer</strong>—don\'t miss out</li>
</ul>
<p>This offer applies to all our training courses, including our most popular CQC inspection preparation courses. Perfect timing to get your team ready for their next inspection.</p>
<p style="margin: 32px 0 24px 0; text-align: center;"><a href="' . esc_url(home_url('/courses/')) . '" style="display: inline-block; background: linear-gradient(135deg, #3ba59b 0%, #2d8b82 100%); color: #fff; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 165, 155, 0.3);">Browse Courses & Claim Offer →</a></p>
<p><strong>How to use your discount:</strong></p>
<ol style="margin: 16px 0; padding-left: 20px; color: #646970; line-height: 1.8;">
<li>Browse our courses and select the training you need</li>
<li>Enter code <strong>[DISCOUNT_CODE]</strong> at checkout</li>
<li>Your discount will be applied automatically</li>
</ol>
<p>Questions? Just reply to this email or call us. We\'re here to help.</p>
<p>Best regards,<br><strong>The CCS Team</strong></p>
<p style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0e0e0; color: #646970; font-size: 13px;"><strong>P.S.</strong> This offer is exclusive to our newsletter subscribers. Thank you for being part of our community!</p>',
            'template_type' => 'special_offer',
            'category' => 'special_offer',
            'is_system' => 1,
        ],
    ];
    
    foreach ($default_templates as $template) {
        // Check if template already exists
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $templates_table WHERE name = %s AND is_system = 1",
            $template['name']
        ));
        
        if (!$existing_id) {
            // Create new template
            $wpdb->insert($templates_table, [
                'name' => $template['name'],
                'description' => $template['description'],
                'subject' => $template['subject'],
                'content' => $template['content'],
                'template_type' => $template['template_type'],
                'category' => $template['category'],
                'is_system' => $template['is_system'],
                'created_by' => get_current_user_id() ?: 1,
            ], ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d']);
        } else {
            // Update existing template category if it's wrong (for templates created with old category)
            $wpdb->update(
                $templates_table,
                ['category' => $template['category']],
                ['id' => $existing_id],
                ['%s'],
                ['%d']
            );
        }
    }
}

/**
 * Create default automation flows
 */
function ccs_create_default_automations() {
    global $wpdb;
    $flows_table = $wpdb->prefix . 'ccs_automation_flows';
    $steps_table = $wpdb->prefix . 'ccs_automation_steps';
    $templates_table = $wpdb->prefix . 'ccs_email_templates';
    
    // Check if automations already exist
    $existing = $wpdb->get_var("SELECT COUNT(*) FROM $flows_table WHERE name LIKE 'Default:%'");
    if ($existing > 0) {
        return; // Default automations already created
    }
    
    // Get template IDs
    $welcome_template = $wpdb->get_row("SELECT id FROM $templates_table WHERE template_type = 'welcome' AND is_system = 1 ORDER BY id ASC LIMIT 1");
    $birthday_template = $wpdb->get_row("SELECT id FROM $templates_table WHERE template_type = 'birthday' AND is_system = 1 ORDER BY id ASC LIMIT 1");
    
    $default_automations = [
        [
            'name' => 'Default: Welcome Email',
            'description' => 'Automatically send a welcome email when someone subscribes to your newsletter',
            'trigger_type' => 'subscribes',
            'trigger_config' => [],
            'steps' => [
                [
                    'type' => 'send_email',
                    'config' => $welcome_template ? ['template_id' => $welcome_template->id] : [],
                ],
            ],
        ],
        [
            'name' => 'Default: Birthday Email',
            'description' => 'Send a birthday email to subscribers on their birthday',
            'trigger_type' => 'date_based',
            'trigger_config' => [
                'date_type' => 'birthday',
                'days_before' => 0,
            ],
            'steps' => [
                [
                    'type' => 'send_email',
                    'config' => $birthday_template ? ['template_id' => $birthday_template->id] : [],
                ],
            ],
        ],
        [
            'name' => 'Default: Re-engagement Campaign',
            'description' => 'Re-engage subscribers who haven\'t opened or clicked emails in 90 days',
            'trigger_type' => 'inactive',
            'trigger_config' => [
                'days_inactive' => 90,
            ],
            'steps' => [
                [
                    'type' => 'send_email',
                    'config' => [
                        'subject' => 'We miss you! Here\'s a special offer just for you',
                        'content' => '<p>Hi {first_name},</p><p>We noticed you haven\'t been opening our emails lately. We\'d love to have you back!</p><p>As a special thank you, here\'s 20% off any training course. Use code REENGAGE20 at checkout.</p><p><a href="' . esc_url(home_url('/courses/')) . '">Browse Courses →</a></p>',
                    ],
                ],
                [
                    'type' => 'delay',
                    'config' => [
                        'delay_type' => 'days',
                        'delay_value' => 7,
                    ],
                ],
                [
                    'type' => 'conditional_split',
                    'config' => [
                        'condition' => 'opened_email',
                        'if_true' => 'continue',
                        'if_false' => 'unsubscribe',
                    ],
                ],
            ],
        ],
    ];
    
    foreach ($default_automations as $automation) {
        // Check if flow already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $flows_table WHERE name = %s",
            $automation['name']
        ));
        
        if (!$exists) {
            // Create flow
            $wpdb->insert($flows_table, [
                'name' => $automation['name'],
                'description' => $automation['description'],
                'status' => 'draft', // Draft status - must be manually activated
                'trigger_type' => $automation['trigger_type'],
                'trigger_config' => wp_json_encode($automation['trigger_config']),
                'flow_data' => wp_json_encode($automation['steps']),
            ], ['%s', '%s', '%s', '%s', '%s', '%s']);
            
            $flow_id = $wpdb->insert_id;
            
            // Create steps
            if ($flow_id && !empty($automation['steps'])) {
                $order = 0;
                foreach ($automation['steps'] as $step) {
                    $wpdb->insert($steps_table, [
                        'flow_id' => $flow_id,
                        'step_type' => $step['type'],
                        'step_order' => $order++,
                        'step_config' => wp_json_encode($step['config'] ?? []),
                    ], ['%d', '%s', '%d', '%s']);
                }
            }
        }
    }
}

/**
 * Check if any active flow has trigger_type 'subscribes' (so welcome can be sent by flow).
 */
function ccs_automation_has_active_subscribes_flow() {
    global $wpdb;
    $flows_table = $wpdb->prefix . 'ccs_automation_flows';
    if ($wpdb->get_var("SHOW TABLES LIKE '$flows_table'") !== $flows_table) {
        return false;
    }
    return (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $flows_table WHERE status = 'active' AND trigger_type = 'subscribes'"
    ) > 0;
}

/**
 * Get first step ID for a flow (lowest step_order).
 */
function ccs_automation_get_first_step_id($flow_id) {
    global $wpdb;
    $steps_table = $wpdb->prefix . 'ccs_automation_steps';
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $steps_table WHERE flow_id = %d ORDER BY step_order ASC LIMIT 1",
        $flow_id
    ));
}

/**
 * Enter a subscriber into a flow and set current step to first step.
 */
function ccs_automation_enter_flow($flow_id, $subscriber_id) {
    global $wpdb;
    $flows_table = $wpdb->prefix . 'ccs_automation_flows';
    $contacts_table = $wpdb->prefix . 'ccs_automation_contacts';
    $steps_table = $wpdb->prefix . 'ccs_automation_steps';

    $flow = $wpdb->get_row($wpdb->prepare("SELECT id, status FROM $flows_table WHERE id = %d", $flow_id));
    if (!$flow || $flow->status !== 'active') {
        return null;
    }

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $contacts_table WHERE flow_id = %d AND subscriber_id = %d",
        $flow_id,
        $subscriber_id
    ));
    if ($existing) {
        return (int) $existing;
    }

    $first_step_id = ccs_automation_get_first_step_id($flow_id);
    $wpdb->insert($contacts_table, [
        'flow_id'         => $flow_id,
        'subscriber_id'  => $subscriber_id,
        'current_step_id' => $first_step_id ?: null,
    ], ['%d', '%d', '%d']);
    $contact_id = $wpdb->insert_id;
    return $contact_id ? (int) $contact_id : null;
}

/**
 * Execute current step for a contact and advance (or complete).
 */
function ccs_automation_process_current_step($contact_id) {
    global $wpdb;
    $contacts_table = $wpdb->prefix . 'ccs_automation_contacts';
    $steps_table = $wpdb->prefix . 'ccs_automation_steps';
    $templates_table = $wpdb->prefix . 'ccs_email_templates';
    $subscribers_table = $wpdb->prefix . 'ccs_newsletter_subscribers';

    $contact = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $contacts_table WHERE id = %d AND exited_at IS NULL",
        $contact_id
    ));
    if (!$contact || !$contact->current_step_id) {
        return;
    }

    $step = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $steps_table WHERE id = %d",
        $contact->current_step_id
    ));
    if (!$step) {
        return;
    }

    $subscriber = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $subscribers_table WHERE id = %d",
        $contact->subscriber_id
    ));
    if (!$subscriber) {
        return;
    }

    $config = json_decode($step->step_config, true);
    $config = is_array($config) ? $config : [];

    if ($step->step_type === 'send_email') {
        $template_id = isset($config['template_id']) ? (int) $config['template_id'] : 0;
        $subject = isset($config['subject']) ? $config['subject'] : '';
        $content = isset($config['content']) ? $config['content'] : '';

        if ($template_id) {
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $templates_table WHERE id = %d",
                $template_id
            ));
            if ($template) {
                $subject = $template->subject;
                $content = $template->content;
            }
        }

        if ($subject !== '' && $content !== '') {
            $first_name = !empty($subscriber->first_name) ? $subscriber->first_name : '';
            $email = $subscriber->email;
            $unsubscribe_token = function_exists('wp_hash') ? wp_hash($email . $contact->subscriber_id) : md5($email . $contact->subscriber_id);
            $unsubscribe_url = add_query_arg([
                'ccs_unsubscribe' => 1,
                'email' => urlencode($email),
                'token' => $unsubscribe_token,
            ], home_url('/unsubscribe/'));
            $unsubscribe_link = '<a href="' . esc_url($unsubscribe_url) . '">Unsubscribe</a>';
            $content = str_replace(
                ['{first_name}', '{site_name}', '{unsubscribe_link}'],
                [esc_html($first_name), esc_html(get_bloginfo('name')), $unsubscribe_link],
                $content
            );
            $from_email = defined('CCS_EMAIL_OFFICE') && CCS_EMAIL_OFFICE ? CCS_EMAIL_OFFICE : get_option('admin_email');
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . $from_email . '>',
                'Reply-To: ' . $from_email,
            ];
            wp_mail($email, $subject, $content, $headers);
        }

        $next_step = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $steps_table WHERE flow_id = %d AND step_order > %d ORDER BY step_order ASC LIMIT 1",
            $contact->flow_id,
            $step->step_order
        ));
        if ($next_step) {
            $wpdb->update(
                $contacts_table,
                ['current_step_id' => $next_step->id],
                ['id' => $contact_id],
                ['%d'],
                ['%d']
            );
        } else {
            $wpdb->update(
                $contacts_table,
                ['current_step_id' => null, 'completed_at' => current_time('mysql')],
                ['id' => $contact_id],
                ['%s', '%s'],
                ['%d']
            );
        }
    } elseif ($step->step_type === 'delay') {
        $days = isset($config['delay_value']) ? (int) $config['delay_value'] : 0;
        $run_after = gmdate('Y-m-d H:i:s', strtotime("+{$days} days"));
        $meta = json_decode($contact->metadata, true);
        $meta = is_array($meta) ? $meta : [];
        $meta['delay_run_after'] = $run_after;
        $wpdb->update(
            $contacts_table,
            ['metadata' => wp_json_encode($meta)],
            ['id' => $contact_id],
            ['%s'],
            ['%d']
        );
    }
}

/**
 * Cron: process contacts whose delay step has elapsed (advance and run next step).
 */
function ccs_automation_process_delayed_steps() {
    global $wpdb;
    $contacts_table = $wpdb->prefix . 'ccs_automation_contacts';
    $steps_table = $wpdb->prefix . 'ccs_automation_steps';

    $contacts = $wpdb->get_results(
        "SELECT id, flow_id, current_step_id, metadata FROM $contacts_table WHERE exited_at IS NULL AND current_step_id IS NOT NULL"
    );
    $now = current_time('mysql');
    foreach ($contacts as $contact) {
        $step = $wpdb->get_row($wpdb->prepare(
            "SELECT id, step_type, step_order, step_config FROM $steps_table WHERE id = %d",
            $contact->current_step_id
        ));
        if (!$step || $step->step_type !== 'delay') {
            continue;
        }
        $meta = json_decode($contact->metadata, true);
        $run_after = isset($meta['delay_run_after']) ? $meta['delay_run_after'] : '';
        if ($run_after === '' || $run_after > $now) {
            continue;
        }
        $next_step = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $steps_table WHERE flow_id = %d AND step_order > %d ORDER BY step_order ASC LIMIT 1",
            $contact->flow_id,
            $step->step_order
        ));
        unset($meta['delay_run_after']);
        $wpdb->update(
            $contacts_table,
            [
                'current_step_id' => $next_step ? $next_step->id : null,
                'metadata' => wp_json_encode($meta),
                'completed_at' => !$next_step ? $now : null,
            ],
            ['id' => $contact->id],
            ['%d', '%s', '%s'],
            ['%d']
        );
        if ($next_step) {
            ccs_automation_process_current_step($contact->id);
        }
    }
}
add_action('ccs_automation_process_delays', 'ccs_automation_process_delayed_steps');

/**
 * Schedule delay processing every 15 minutes if not already scheduled.
 */
function ccs_automation_schedule_delay_cron() {
    if (wp_next_scheduled('ccs_automation_process_delays')) {
        return;
    }
    wp_schedule_event(time(), 'every_15_minutes', 'ccs_automation_process_delays');
}
add_action('admin_init', 'ccs_automation_schedule_delay_cron');

/**
 * Register 15-minute cron interval.
 */
function ccs_automation_cron_intervals($schedules) {
    $schedules['every_15_minutes'] = [
        'interval' => 15 * 60,
        'display'  => __('Every 15 Minutes'),
    ];
    return $schedules;
}
add_filter('cron_schedules', 'ccs_automation_cron_intervals');

/**
 * Trigger: add subscriber to all active 'subscribes' flows and process first step.
 */
function ccs_automation_trigger_subscribes($subscriber_id) {
    global $wpdb;
    $flows_table = $wpdb->prefix . 'ccs_automation_flows';
    if ($wpdb->get_var("SHOW TABLES LIKE '$flows_table'") !== $flows_table) {
        return false;
    }

    $flows = $wpdb->get_results(
        "SELECT id FROM $flows_table WHERE status = 'active' AND trigger_type = 'subscribes'"
    );
    if (empty($flows)) {
        return false;
    }

    $entered = 0;
    foreach ($flows as $flow) {
        $contact_id = ccs_automation_enter_flow($flow->id, $subscriber_id);
        if ($contact_id) {
            $entered++;
            ccs_automation_process_current_step($contact_id);
        }
    }
    return $entered > 0;
}

/**
 * Run when a new subscriber is added: enter them into subscribes flows and process first step.
 */
function ccs_automation_on_subscriber_added($subscriber_id, $email, $first_name) {
    ccs_automation_trigger_subscribes($subscriber_id);
}
add_action('ccs_newsletter_subscriber_added', 'ccs_automation_on_subscriber_added', 10, 3);

/**
 * Add automation menu to admin
 */
function ccs_automation_admin_menu() {
    $cap = function_exists('ccs_newsletter_required_capability') ? ccs_newsletter_required_capability() : 'edit_others_posts';
    add_submenu_page(
        'cta-newsletter',
        'Automation Flows',
        'Automation',
        $cap,
        'cta-automation',
        'ccs_automation_admin_page'
    );
    
    add_submenu_page(
        'cta-newsletter',
        'Email Templates',
        'Templates',
        $cap,
        'cta-email-templates',
        'ccs_email_templates_admin_page'
    );
}
add_action('admin_menu', 'ccs_automation_admin_menu', 11);

/**
 * Handle email template copy/redirect before any output (avoids "headers already sent").
 * Must run on admin_init so redirect happens before script-loader or admin UI outputs.
 */
function ccs_email_templates_handle_redirect_actions() {
    if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'cta-email-templates') {
        return;
    }
    $cap = function_exists('ccs_newsletter_required_capability') ? ccs_newsletter_required_capability() : 'edit_others_posts';
    if (!current_user_can($cap)) {
        return;
    }
    if (!isset($_GET['action']) || !isset($_GET['template_id'])) {
        return;
    }
    $action = sanitize_text_field($_GET['action']);
    $template_id = absint($_GET['template_id']);
    if ($template_id < 1) {
        return;
    }
    global $wpdb;
    $templates_table = $wpdb->prefix . 'ccs_email_templates';
    if ($action === 'copy' && check_admin_referer('copy_template_' . $template_id, '_wpnonce', false)) {
        $original = $wpdb->get_row($wpdb->prepare("SELECT * FROM $templates_table WHERE id = %d", $template_id));
        if ($original) {
            $wpdb->insert($templates_table, [
                'name' => $original->name . ' (Copy)',
                'description' => $original->description,
                'subject' => $original->subject,
                'content' => $original->content,
                'template_type' => $original->template_type,
                'category' => $original->category,
                'is_system' => 0,
                'created_by' => get_current_user_id(),
            ], ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d']);
            $new_id = $wpdb->insert_id;
            if ($new_id) {
                wp_redirect(admin_url('admin.php?page=cta-email-templates&action=edit&template_id=' . $new_id));
                exit;
            }
        }
    }
}
add_action('admin_init', 'ccs_email_templates_handle_redirect_actions', 5);

/**
 * Automation flows admin page
 */
function ccs_automation_admin_page() {
    global $wpdb;
    $flows_table = $wpdb->prefix . 'ccs_automation_flows';
    
    // Ensure automations table exists and default automations are created
    if ($wpdb->get_var("SHOW TABLES LIKE '$flows_table'") === $flows_table) {
        // Force check automations on this page load (only if no default automations exist)
        $automation_count = $wpdb->get_var("SELECT COUNT(*) FROM $flows_table WHERE name LIKE 'Default:%'");
        if ($automation_count == 0) {
            ccs_create_default_automations();
        }
    } else {
        // Table doesn't exist, create it
        ccs_create_automation_tables();
    }
    
    // Handle new flow creation
    if (isset($_GET['action']) && $_GET['action'] === 'new') {
        ccs_render_flow_builder();
        return;
    }
    
    // Handle flow editing
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['flow_id'])) {
        $flow_id = absint($_GET['flow_id']);
        $flow = $wpdb->get_row($wpdb->prepare("SELECT * FROM $flows_table WHERE id = %d", $flow_id));
        if ($flow) {
            ccs_render_flow_builder($flow);
            return;
        }
    }
    
    // Handle flow templates
    if (isset($_GET['action']) && $_GET['action'] === 'templates') {
        ccs_render_flow_templates();
        return;
    }
    
    // Handle flow save
    if (isset($_POST['save_flow']) && check_admin_referer('save_automation_flow')) {
        $flow_id = isset($_POST['flow_id']) ? absint($_POST['flow_id']) : 0;
        // flow_data from builder is a JSON string; avoid double-encoding
        $steps_raw = $_POST['flow_data'] ?? [];
        if (is_string($steps_raw)) {
            $steps_decoded = json_decode($steps_raw, true);
            $steps_for_db = is_array($steps_decoded) ? $steps_decoded : [];
        } else {
            $steps_for_db = is_array($steps_raw) ? $steps_raw : [];
        }
        $flow_data = [
            'name' => sanitize_text_field($_POST['flow_name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['flow_description'] ?? ''),
            'trigger_type' => sanitize_text_field($_POST['trigger_type'] ?? ''),
            'trigger_config' => wp_json_encode($_POST['trigger_config'] ?? []),
            'flow_data' => wp_json_encode($steps_for_db),
            'status' => sanitize_text_field($_POST['flow_status'] ?? 'draft'),
        ];
        
        if ($flow_id) {
            $wpdb->update($flows_table, $flow_data, ['id' => $flow_id], ['%s', '%s', '%s', '%s', '%s', '%s'], ['%d']);
            echo '<div class="notice notice-success"><p>Flow updated.</p></div>';
        } else {
            $wpdb->insert($flows_table, $flow_data, ['%s', '%s', '%s', '%s', '%s', '%s']);
            $flow_id = $wpdb->insert_id;
            echo '<div class="notice notice-success"><p>Flow created.</p></div>';
        }
        
        // Sync steps table from flow_data so execution (which uses steps_table) works
        if ($flow_id && function_exists('ccs_save_flow_steps')) {
            ccs_save_flow_steps($flow_id, $steps_for_db);
        }
    }
    
    // Handle flow actions
    if (isset($_GET['action']) && isset($_GET['flow_id'])) {
        $flow_id = absint($_GET['flow_id']);
        $action = sanitize_text_field($_GET['action']);
        
        if ($action === 'delete' && check_admin_referer('delete_flow_' . $flow_id)) {
            $wpdb->delete($flows_table, ['id' => $flow_id], ['%d']);
            echo '<div class="notice notice-success"><p>Flow deleted.</p></div>';
        } elseif ($action === 'activate' && check_admin_referer('activate_flow_' . $flow_id)) {
            $wpdb->update(
                $flows_table,
                ['status' => 'active', 'activated_at' => current_time('mysql')],
                ['id' => $flow_id],
                ['%s', '%s'],
                ['%d']
            );
            echo '<div class="notice notice-success"><p>Flow activated.</p></div>';
        } elseif ($action === 'pause' && check_admin_referer('pause_flow_' . $flow_id)) {
            $wpdb->update(
                $flows_table,
                ['status' => 'paused'],
                ['id' => $flow_id],
                ['%s'],
                ['%d']
            );
            echo '<div class="notice notice-success"><p>Flow paused.</p></div>';
        }
    }
    
    // Get all flows
    $flows = $wpdb->get_results("SELECT * FROM $flows_table ORDER BY created_at DESC");
    
    ?>
    <div class="wrap">
        <h1>Automation Flows</h1>
        <p class="description">Create automated email sequences with triggers, rules, and actions. Build customer journeys like Mailchimp.</p>
        
        <div style="margin: 20px 0;">
            <a href="<?php echo admin_url('admin.php?page=cta-automation&action=new'); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                Create New Flow
            </a>
            <a href="<?php echo admin_url('admin.php?page=cta-automation&action=templates'); ?>" class="button">
                <span class="dashicons dashicons-admin-page" style="vertical-align: middle;"></span>
                Browse Flow Templates
            </a>
        </div>
        
        <?php if (empty($flows)) : ?>
            <div style="padding: 40px; text-align: center; background: #f9f9f9; border-radius: 8px; border: 2px dashed #dcdcde; margin-top: 20px;">
                <p style="margin: 0; color: #646970; font-size: 16px;">No automation flows yet. Create your first flow to get started.</p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Flow Name</th>
                        <th>Trigger</th>
                        <th>Status</th>
                        <th>Contacts</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flows as $flow) : 
                        $trigger_config = json_decode($flow->trigger_config, true);
                        $trigger_label = ucfirst(str_replace('_', ' ', $flow->trigger_type));
                        
                        // Count contacts in this flow
                        $contacts_table = $wpdb->prefix . 'ccs_automation_contacts';
                        $contact_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $contacts_table WHERE flow_id = %d AND exited_at IS NULL",
                            $flow->id
                        ));
                        $total_entered = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $contacts_table WHERE flow_id = %d",
                            $flow->id
                        ));
                        $completed_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $contacts_table WHERE flow_id = %d AND completed_at IS NOT NULL",
                            $flow->id
                        ));
                    ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo admin_url('admin.php?page=cta-automation&action=edit&flow_id=' . $flow->id); ?>"><?php echo esc_html($flow->name); ?></a></strong>
                                <?php if ($flow->description) : ?>
                                    <br><span style="color: #646970; font-size: 13px;"><?php echo esc_html($flow->description); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($trigger_label); ?></td>
                            <td>
                                <?php if ($flow->status === 'active') : ?>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; color: #00a32a; font-weight: 500;">
                                        <span style="display: inline-block; width: 8px; height: 8px; background: #00a32a; border-radius: 50%;"></span>
                                        Active
                                    </span>
                                <?php elseif ($flow->status === 'paused') : ?>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; color: #dba617;">
                                        <span style="display: inline-block; width: 8px; height: 8px; background: #dba617; border-radius: 50%;"></span>
                                        Paused
                                    </span>
                                <?php else : ?>
                                    <span style="color: #646970;">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span style="font-weight: 600;"><?php echo number_format($contact_count); ?> active</span>
                                    <?php if ($total_entered > 0) : ?>
                                        <span style="font-size: 12px; color: #646970;">
                                            <?php echo number_format($completed_count); ?> completed
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span><?php echo date('j M Y', strtotime($flow->created_at)); ?></span>
                                    <?php if ($flow->activated_at) : ?>
                                        <span style="font-size: 12px; color: #646970;">
                                            Activated: <?php echo date('j M Y', strtotime($flow->activated_at)); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="<?php echo admin_url('admin.php?page=cta-automation&action=edit&flow_id=' . $flow->id); ?>" class="button button-small">Edit</a>
                                    <?php if ($flow->status === 'active') : ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cta-automation&action=pause&flow_id=' . $flow->id), 'pause_flow_' . $flow->id); ?>" class="button button-small">Pause</a>
                                    <?php elseif ($flow->status === 'draft' || $flow->status === 'paused') : ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cta-automation&action=activate&flow_id=' . $flow->id), 'activate_flow_' . $flow->id); ?>" class="button button-small button-primary">Activate</a>
                                    <?php endif; ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cta-automation&action=delete&flow_id=' . $flow->id), 'delete_flow_' . $flow->id); ?>" 
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('Delete this flow? This cannot be undone.');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Email templates admin page
 */
function ccs_email_templates_admin_page() {
    global $wpdb;
    $templates_table = $wpdb->prefix . 'ccs_email_templates';
    
    // Ensure templates table exists and templates are created
    if ($wpdb->get_var("SHOW TABLES LIKE '$templates_table'") === $templates_table) {
        // Force check templates on this page load (only if no templates exist)
        $template_count = $wpdb->get_var("SELECT COUNT(*) FROM $templates_table WHERE is_system = 1");
        if ($template_count == 0) {
            ccs_create_default_email_templates();
        }
    } else {
        // Table doesn't exist, create it
        ccs_create_automation_tables();
    }
    
    // Handle new template creation
    if (isset($_GET['action']) && $_GET['action'] === 'new') {
        ccs_render_template_builder();
        return;
    }
    
    // Handle template editing
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['template_id'])) {
        $template_id = absint($_GET['template_id']);
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $templates_table WHERE id = %d", $template_id));
        if ($template) {
            ccs_render_template_builder($template);
            return;
        }
    }
    
    // Handle template save
    if (isset($_POST['save_template']) && check_admin_referer('save_email_template')) {
        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        $template_data = [
            'name' => sanitize_text_field($_POST['template_name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['template_description'] ?? ''),
            'subject' => sanitize_text_field($_POST['template_subject'] ?? ''),
            'content' => wp_kses_post($_POST['template_content'] ?? ''),
            'template_type' => sanitize_text_field($_POST['template_type'] ?? 'custom'),
            'category' => sanitize_text_field($_POST['template_category'] ?? ''),
            'created_by' => get_current_user_id(),
        ];
        
        if ($template_id) {
            $wpdb->update($templates_table, $template_data, ['id' => $template_id], ['%s', '%s', '%s', '%s', '%s', '%s', '%d'], ['%d']);
            echo '<div class="notice notice-success"><p>Template updated.</p></div>';
        } else {
            $wpdb->insert($templates_table, $template_data, ['%s', '%s', '%s', '%s', '%s', '%s', '%d']);
            echo '<div class="notice notice-success"><p>Template created.</p></div>';
        }
    }
    
    // Handle template actions
    if (isset($_GET['action']) && isset($_GET['template_id'])) {
        $template_id = absint($_GET['template_id']);
        $action = sanitize_text_field($_GET['action']);
        
        if ($action === 'delete' && check_admin_referer('delete_template_' . $template_id)) {
            // Don't allow deleting system templates
            $is_system = $wpdb->get_var($wpdb->prepare(
                "SELECT is_system FROM $templates_table WHERE id = %d",
                $template_id
            ));
            
            if ($is_system) {
                echo '<div class="notice notice-error"><p>System templates cannot be deleted. You can create a copy and edit that instead.</p></div>';
            } else {
                $wpdb->delete($templates_table, ['id' => $template_id], ['%d']);
                echo '<div class="notice notice-success"><p>Template deleted.</p></div>';
            }
        } elseif ($action === 'copy') {
            // Handled in ccs_email_templates_handle_redirect_actions() on admin_init to avoid headers already sent.
        } elseif ($action === 'duplicate' && check_admin_referer('duplicate_template_' . $template_id)) {
            // Duplicate any template
            $original = $wpdb->get_row($wpdb->prepare("SELECT * FROM $templates_table WHERE id = %d", $template_id));
            if ($original) {
                $wpdb->insert($templates_table, [
                    'name' => $original->name . ' (Copy)',
                    'description' => $original->description,
                    'subject' => $original->subject,
                    'content' => $original->content,
                    'template_type' => $original->template_type,
                    'category' => $original->category,
                    'is_system' => 0,
                    'created_by' => get_current_user_id(),
                ], ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d']);
                
                echo '<div class="notice notice-success"><p>Template duplicated successfully.</p></div>';
            }
        }
    }
    
    // Get all templates - system templates first, then custom
    // Sort templates: system templates first, then by type (welcome, new_course, new_article, upcoming_courses, quarterly, birthday, custom), then by name
    $templates = $wpdb->get_results("SELECT * FROM $templates_table ORDER BY is_system DESC, 
        CASE 
            WHEN template_type = 'welcome' THEN 1
            WHEN template_type = 'new_course' THEN 2
            WHEN template_type = 'new_article' THEN 3
            WHEN template_type = 'upcoming_courses' THEN 4
            WHEN template_type = 'quarterly' THEN 5
            WHEN template_type = 'birthday' THEN 6
            WHEN template_type = 'special_offer' THEN 7
            ELSE 8
        END, 
        name ASC");
    
    // Get usage statistics for templates
    $flows_table = $wpdb->prefix . 'ccs_automation_flows';
    $template_usage = [];
    foreach ($templates as $template) {
        // Count how many times this template is used in flows
        $usage_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $flows_table WHERE flow_data LIKE %s",
            '%"template_id":' . $template->id . '%'
        ));
        $template_usage[$template->id] = $usage_count;
    }
    
    // Template categories - tailored to our needs
    $categories = ['All Templates', 'New Course', 'New Article', 'Upcoming Courses', 'Welcome', 'Quarterly'];
    $selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : 'All Templates';
    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // Filter templates by category and search
    if ($selected_category !== 'All Templates' || $search_query) {
        $templates = array_filter($templates, function($t) use ($selected_category, $search_query) {
            $matches_category = true;
            $matches_search = true;
            
            if ($selected_category !== 'All Templates') {
                $filter_value = strtolower(str_replace(' ', '_', $selected_category));
                $matches_category = $t->category === $filter_value || 
                                   $t->template_type === $filter_value ||
                                   ($selected_category === 'New Course' && $t->template_type === 'new_course') ||
                                   ($selected_category === 'New Article' && $t->template_type === 'new_article') ||
                                   ($selected_category === 'Upcoming Courses' && $t->template_type === 'upcoming_courses');
            }
            
            if ($search_query) {
                $search_lower = strtolower($search_query);
                $matches_search = stripos(strtolower($t->name), $search_lower) !== false ||
                                 stripos(strtolower($t->description), $search_lower) !== false ||
                                 stripos(strtolower($t->subject), $search_lower) !== false;
            }
            
            return $matches_category && $matches_search;
        });
    }
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Email Templates</h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=cta-email-templates&action=new')); ?>" class="page-title-action">Add New</a>
        <p class="description">Create, manage, and reuse email templates for newsletters and automation.</p>

        <h2 class="nav-tab-wrapper wp-clearfix" style="margin-top: 12px;">
            <?php foreach ($categories as $cat) :
                $url = admin_url('admin.php?page=cta-email-templates' . ($cat !== 'All Templates' ? '&category=' . urlencode($cat) : '') . ($search_query ? '&s=' . urlencode($search_query) : ''));
                $active = ($selected_category === $cat) ? ' nav-tab-active' : '';
            ?>
                <a class="nav-tab<?php echo esc_attr($active); ?>" href="<?php echo esc_url($url); ?>">
                    <?php echo esc_html($cat); ?>
                </a>
            <?php endforeach; ?>
        </h2>

        <form method="get" style="margin: 12px 0 16px 0;">
            <input type="hidden" name="page" value="cta-email-templates" />
            <?php if ($selected_category !== 'All Templates') : ?>
                <input type="hidden" name="category" value="<?php echo esc_attr($selected_category); ?>" />
            <?php endif; ?>
            <p class="search-box">
                <label class="screen-reader-text" for="template-search-input">Search Templates:</label>
                <input type="search" id="template-search-input" name="s" value="<?php echo esc_attr($search_query); ?>" />
                <input type="submit" class="button" value="Search Templates" />
            </p>
        </form>

        <?php if (empty($templates)) : ?>
            <div class="notice notice-info"><p>No templates found.</p></div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary">Name</th>
                        <th scope="col" class="manage-column">Category</th>
                        <th scope="col" class="manage-column">Subject</th>
                        <th scope="col" class="manage-column">System</th>
                        <th scope="col" class="manage-column">Used</th>
                        <th scope="col" class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $template) :
                        $category_label = $template->category ? ucfirst(str_replace('_', ' ', (string) $template->category)) : '—';
                        $is_system = (int) $template->is_system === 1;
                        $usage = (int) ($template_usage[$template->id] ?? 0);
                        $edit_url = admin_url('admin.php?page=cta-email-templates&action=edit&template_id=' . $template->id);
                        $copy_url = wp_nonce_url(admin_url('admin.php?page=cta-email-templates&action=copy&template_id=' . $template->id), 'copy_template_' . $template->id);
                        $delete_url = wp_nonce_url(admin_url('admin.php?page=cta-email-templates&action=delete&template_id=' . $template->id), 'delete_template_' . $template->id);
                        // Take the user directly to Compose with the template applied.
                        $use_url = admin_url('admin.php?page=cta-newsletter-compose&use_template=' . $template->id);
                    ?>
                        <tr>
                            <td class="column-primary">
                                <strong>
                                    <a href="<?php echo esc_url($is_system ? $copy_url : $edit_url); ?>">
                                        <?php echo esc_html($template->name); ?>
                                    </a>
                                </strong>
                                <?php if (!empty($template->description)) : ?>
                                    <div class="row-description"><?php echo esc_html($template->description); ?></div>
                                <?php endif; ?>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                            </td>
                            <td><?php echo esc_html($category_label); ?></td>
                            <td><?php echo esc_html(wp_trim_words((string) $template->subject, 12)); ?></td>
                            <td><?php echo $is_system ? 'Yes' : 'No'; ?></td>
                            <td><?php echo esc_html((string) $usage); ?></td>
                            <td>
                                <?php if ($is_system) : ?>
                                    <a class="button button-small" href="<?php echo esc_url($copy_url); ?>">Duplicate</a>
                                <?php else : ?>
                                    <a class="button button-small" href="<?php echo esc_url($edit_url); ?>">Edit</a>
                                    <button type="button" class="button button-small preview-template" data-template-id="<?php echo esc_attr($template->id); ?>">Preview</button>
                                    <a class="button button-small button-link-delete" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Delete this template? This cannot be undone.');">Delete</a>
                                <?php endif; ?>
                                <a class="button button-small button-primary" href="<?php echo esc_url($use_url); ?>">Use</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Template Preview Modal -->
    <div id="template-preview-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto;">
        <div style="max-width: 800px; margin: 40px auto; background: #fff; border-radius: 8px; padding: 30px; position: relative;">
            <button type="button" id="close-preview" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: #646970;">×</button>
            <h2 id="preview-template-name" style="margin-top: 0;"></h2>
            <div id="preview-template-subject" style="font-weight: 600; color: #2271b1; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0;"></div>
            <div id="preview-template-content" style="line-height: 1.6; color: #2b1b0e;"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var templateData = <?php 
            $templates_for_js = [];
            foreach ($templates as $t) {
                $templates_for_js[] = [
                    'id' => $t->id,
                    'name' => $t->name,
                    'subject' => $t->subject,
                    'content' => $t->content,
                ];
            }
            echo wp_json_encode($templates_for_js);
        ?>;
        
        // Preview template
        $('.preview-template').on('click', function() {
            var templateId = $(this).data('template-id');
            var template = templateData.find(function(t) { return t.id == templateId; });
            
            if (template) {
                $('#preview-template-name').text(template.name);
                $('#preview-template-subject').text('Subject: ' + template.subject);
                $('#preview-template-content').html(template.content);
                $('#template-preview-modal').fadeIn();
            }
        });
        
        // Close preview
        $('#close-preview, #template-preview-modal').on('click', function(e) {
            if (e.target === this || $(e.target).attr('id') === 'close-preview') {
                $('#template-preview-modal').fadeOut();
            }
        });
        
        // Prevent modal close when clicking inside
        $('#template-preview-modal > div').on('click', function(e) {
            e.stopPropagation();
        });
    });
    </script>
    <?php
}
