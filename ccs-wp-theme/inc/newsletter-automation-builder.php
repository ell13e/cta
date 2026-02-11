<?php
/**
 * Flow Builder Functions
 * Visual flow builder UI and helper functions
 */

defined('ABSPATH') || exit;

/**
 * Get built-in flow templates.
 *
 * These are used by the "Flow Templates" page and for pre-filling the builder
 * when `admin.php?page=cta-automation&action=new&template=...` is present.
 */
function ccs_get_flow_templates() {
    return [
        [
            'name' => 'Welcome Series',
            'description' => 'Send a series of welcome emails to new subscribers',
            'trigger' => 'subscribes',
            // Step types (builder will expand these into flow_data entries)
            'steps' => ['send_email', 'delay', 'send_email', 'delay', 'send_email']
        ],
        [
            'name' => 'Re-engagement Campaign',
            'description' => 'Re-engage inactive subscribers with a special offer',
            'trigger' => 'inactive',
            'steps' => ['send_email', 'delay', 'conditional_split', 'send_email']
        ],
        [
            'name' => 'Birthday Email',
            'description' => 'Send a birthday message to subscribers',
            'trigger' => 'date_based',
            'steps' => ['send_email']
        ],
    ];
}

/**
 * Render flow builder interface
 */
function ccs_render_flow_builder($flow = null) {
    global $wpdb;
    $flow_id = $flow ? $flow->id : 0;
    $flow_name = $flow ? $flow->name : '';
    $flow_description = $flow ? $flow->description : '';
    $flow_data = $flow ? json_decode($flow->flow_data, true) : [];
    // Handle double-encoded flow_data from older saves
    if (is_string($flow_data)) {
        $flow_data = json_decode($flow_data, true);
    }
    $flow_data = is_array($flow_data) ? $flow_data : [];
    $trigger_type = $flow ? $flow->trigger_type : '';
    $trigger_config = $flow ? json_decode($flow->trigger_config, true) : [];

    // If creating a new flow from a template (`action=new&template=...`), pre-fill the form.
    $loaded_flow_template = null;
    $template_not_found = false;
    if (!$flow && isset($_GET['template'])) {
        $requested_name = sanitize_text_field(wp_unslash($_GET['template']));
        $templates = ccs_get_flow_templates();
        foreach ($templates as $t) {
            if (!empty($t['name']) && $t['name'] === $requested_name) {
                $loaded_flow_template = $t;
                break;
            }
        }

        if ($loaded_flow_template) {
            $flow_name = (string) ($loaded_flow_template['name'] ?? '');
            $flow_description = (string) ($loaded_flow_template['description'] ?? '');
            $trigger_type = (string) ($loaded_flow_template['trigger'] ?? '');

            $flow_data = [];
            $step_types_from_template = $loaded_flow_template['steps'] ?? [];
            if (is_array($step_types_from_template)) {
                foreach ($step_types_from_template as $step_type) {
                    $step_type = sanitize_text_field((string) $step_type);
                    if ($step_type === '') {
                        continue;
                    }
                    $flow_data[] = [
                        'type' => $step_type,
                        'config' => [],
                    ];
                }
            }
        } else {
            $template_not_found = true;
        }
    }
    
    // Get available email templates for Send Email steps
    $templates_table = $wpdb->prefix . 'ccs_email_templates';
    $email_templates = $wpdb->get_results("SELECT id, name, subject FROM $templates_table ORDER BY is_system DESC, name ASC");
    
    // Available triggers
    $triggers = [
        'subscribes' => 'Subscribes to Newsletter',
        'date_based' => 'Date-Based (Birthday, Anniversary)',
        'inactive' => 'Inactive Subscriber (No opens/clicks)',
        'tag_added' => 'Tag Added',
        'course_booked' => 'Course Booked',
        'form_submitted' => 'Form Submitted',
    ];
    
    // Available steps
    $step_types = [
        'send_email' => ['label' => 'Send Email', 'icon' => 'email-alt', 'color' => '#2271b1'],
        'delay' => ['label' => 'Wait/Delay', 'icon' => 'clock', 'color' => '#646970'],
        'conditional_split' => ['label' => 'Conditional Split', 'icon' => 'arrow-right-alt', 'color' => '#00a32a'],
        'tag' => ['label' => 'Add Tag', 'icon' => 'tag', 'color' => '#d63638'],
        'untag' => ['label' => 'Remove Tag', 'icon' => 'dismiss', 'color' => '#d63638'],
        'unsubscribe' => ['label' => 'Unsubscribe', 'icon' => 'no-alt', 'color' => '#d63638'],
    ];
    
    wp_enqueue_script('jquery-ui-sortable');
    ?>
    <div class="wrap" id="cta-flow-builder">
        <h1><?php echo $flow ? 'Edit Flow' : 'Create New Automation Flow'; ?></h1>
        <?php if ($loaded_flow_template) : ?>
            <div class="notice notice-success">
                <p><strong>Template loaded:</strong> <?php echo esc_html($loaded_flow_template['name']); ?>. Review the trigger and steps, then click “Save Flow”.</p>
            </div>
        <?php elseif ($template_not_found) : ?>
            <div class="notice notice-error">
                <p>That flow template couldn’t be found. Please go back to <a href="<?php echo esc_url(admin_url('admin.php?page=cta-automation&action=templates')); ?>">Flow Templates</a> and try again.</p>
            </div>
        <?php endif; ?>
        
        <form method="post" id="flow-builder-form">
            <?php wp_nonce_field('save_automation_flow'); ?>
            <input type="hidden" name="save_flow" value="1">
            <input type="hidden" name="flow_id" value="<?php echo esc_attr($flow_id); ?>">
            
            <!-- Flow Settings -->
            <div style="background: #fff; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="margin-top: 0;">Flow Settings</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="flow_name">Flow Name</label></th>
                        <td><input type="text" id="flow_name" name="flow_name" value="<?php echo esc_attr($flow_name); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="flow_description">Description</label></th>
                        <td><textarea id="flow_description" name="flow_description" rows="3" class="large-text"><?php echo esc_textarea($flow_description); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="trigger_type">Trigger</label></th>
                        <td>
                            <select id="trigger_type" name="trigger_type" required>
                                <option value="">Select a trigger...</option>
                                <?php foreach ($triggers as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($trigger_type, $key); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">What starts this automation flow?</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Flow Builder Canvas -->
            <div style="background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; min-height: 400px;">
                <div style="display: flex; gap: 20px;">
                    <!-- Sidebar: Available Steps -->
                    <div style="width: 250px; background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e0e0e0;">
                        <h3 style="margin-top: 0; font-size: 14px; text-transform: uppercase; color: #646970;">Add Step</h3>
                        <div id="available-steps" style="display: flex; flex-direction: column; gap: 8px;">
                            <?php foreach ($step_types as $key => $step) : ?>
                                <button type="button" class="cta-add-step-btn" data-step-type="<?php echo esc_attr($key); ?>" 
                                        style="display: flex; align-items: center; gap: 10px; padding: 12px; border: 1px solid #e0e0e0; border-radius: 6px; background: #fff; cursor: pointer; text-align: left; width: 100%;">
                                    <span class="dashicons dashicons-<?php echo esc_attr($step['icon']); ?>" style="color: <?php echo esc_attr($step['color']); ?>;"></span>
                                    <span><?php echo esc_html($step['label']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Canvas: Flow Map -->
                    <div style="flex: 1; background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; min-height: 400px;">
                        <h3 style="margin-top: 0;">Flow Map</h3>
                        <div id="flow-canvas" style="position: relative; min-height: 300px;">
                            <!-- Trigger Node -->
                            <div class="flow-node flow-trigger" style="background: #2271b1; color: #fff; padding: 16px; border-radius: 8px; margin-bottom: 20px; text-align: center; max-width: 300px; margin-left: auto; margin-right: auto;">
                                <div style="font-weight: 600;">Trigger</div>
                                <div id="trigger-display" style="font-size: 13px; margin-top: 8px;">
                                    <?php echo $trigger_type ? esc_html($triggers[$trigger_type] ?? $trigger_type) : 'Select a trigger above'; ?>
                                </div>
                            </div>
                            
                            <!-- Flow Steps Container -->
                            <div id="flow-steps-container" style="display: flex; flex-direction: column; gap: 20px; align-items: center;">
                                <!-- Steps will be added here via JavaScript -->
                            </div>
                            
                            <!-- Empty State -->
                            <div id="flow-empty-state" style="text-align: center; padding: 40px; color: #646970; <?php echo !empty($flow_data) ? 'display: none;' : ''; ?>">
                                <p>Click a step type on the left to add it to your flow</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hidden field for flow data -->
            <input type="hidden" name="flow_data" id="flow-data-input" value='<?php echo esc_attr(wp_json_encode($flow_data)); ?>'>
            <input type="hidden" name="flow_status" value="<?php echo $flow ? esc_attr($flow->status) : 'draft'; ?>">
            
            <!-- Actions -->
            <div style="margin-top: 20px;">
                <button type="submit" class="button button-primary button-large">Save Flow</button>
                <a href="<?php echo admin_url('admin.php?page=cta-automation'); ?>" class="button button-large">Cancel</a>
            </div>
        </form>
    </div>
    
    <style>
        .flow-node {
            position: relative;
            padding: 16px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            background: #fff;
            min-width: 200px;
            text-align: center;
            cursor: move;
        }
        .flow-node::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 20px;
            background: #e0e0e0;
        }
        .flow-node:last-child::after {
            display: none;
        }
        .flow-node:hover {
            border-color: #2271b1;
            box-shadow: 0 2px 8px rgba(34, 113, 177, 0.2);
        }
        .cta-add-step-btn:hover {
            background: #f0f6fc;
            border-color: #2271b1;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        var stepCounter = 0;
        var flowSteps = <?php echo wp_json_encode($flow_data); ?>;
        
        // Initialize existing steps
        if (flowSteps && flowSteps.length > 0) {
            flowSteps.forEach(function(step) {
                addStepToCanvas(step.type, step.config || {});
            });
        }
        
        // Restore template selections for existing Send Email steps
        setTimeout(function() {
            if (flowSteps && flowSteps.length > 0) {
                flowSteps.forEach(function(step, index) {
                    if (step.type === 'send_email' && step.config && step.config.template_id) {
                        var $stepNode = $('#flow-steps-container .flow-node').eq(index);
                        if ($stepNode.length) {
                            $stepNode.find('.step-template-select').val(step.config.template_id);
                        }
                    }
                });
            }
        }, 100);
        
        // Add step button click
        $('.cta-add-step-btn').on('click', function() {
            var stepType = $(this).data('step-type');
            addStepToCanvas(stepType, {});
        });
        
        // Update trigger display
        $('#trigger_type').on('change', function() {
            var selected = $(this).find('option:selected').text();
            $('#trigger-display').text(selected || 'Select a trigger above');
        });
        
        function addStepToCanvas(stepType, config) {
            stepCounter++;
            var stepId = 'step-' + stepCounter;
            var stepTypes = <?php echo wp_json_encode($step_types); ?>;
            var step = stepTypes[stepType] || {};
            
            var stepHtml = '<div class="flow-node" data-step-id="' + stepId + '" data-step-type="' + stepType + '">' +
                '<span class="dashicons dashicons-' + (step.icon || 'admin-generic') + '" style="color: ' + (step.color || '#646970') + '; vertical-align: middle; margin-right: 8px;"></span>' +
                '<strong>' + (step.label || stepType) + '</strong>';
            
            // Add template selector for Send Email steps
            if (stepType === 'send_email') {
                stepHtml += '<div class="step-config" style="margin-top: 12px;">' +
                    '<select class="step-template-select" style="width: 100%; padding: 6px; border: 1px solid #dcdcde; border-radius: 4px;">' +
                    '<option value="">Select email template...</option>';
                
                var templates = <?php echo wp_json_encode(array_map(function($t) {
                    return ['id' => $t->id, 'name' => $t->name, 'subject' => $t->subject];
                }, $email_templates)); ?>;
                
                templates.forEach(function(template) {
                    stepHtml += '<option value="' + template.id + '">' + template.name + ' - ' + template.subject + '</option>';
                });
                
                stepHtml += '</select>' +
                    '<p class="description" style="margin: 8px 0 0 0; font-size: 12px; color: #646970;">Choose a template or leave blank to create custom email</p>' +
                    '</div>';
            } else {
                stepHtml += '<div class="step-config" style="margin-top: 12px; display: none;">' +
                    '<input type="text" class="step-config-input" placeholder="Configure step...">' +
                    '</div>';
            }
            
            stepHtml += '<button type="button" class="button-link delete-step" style="position: absolute; top: 8px; right: 8px; color: #d63638;">×</button>' +
                '</div>';
            
            $('#flow-empty-state').hide();
            $('#flow-steps-container').append(stepHtml);
            
            // Make sortable
            $('#flow-steps-container').sortable({
                axis: 'y',
                handle: '.flow-node',
                update: updateFlowData
            });
            
            // Delete step
            $('.flow-node[data-step-id="' + stepId + '"] .delete-step').on('click', function() {
                $(this).closest('.flow-node').remove();
                updateFlowData();
                if ($('#flow-steps-container .flow-node').length === 0) {
                    $('#flow-empty-state').show();
                }
            });
            
            updateFlowData();
        }
        
        function updateFlowData() {
            var steps = [];
            $('#flow-steps-container .flow-node').each(function() {
                var stepType = $(this).data('step-type');
                var config = {};
                
                // Get template ID for Send Email steps
                if (stepType === 'send_email') {
                    var templateId = $(this).find('.step-template-select').val();
                    if (templateId) {
                        config.template_id = parseInt(templateId);
                    }
                }
                
                steps.push({
                    type: stepType,
                    config: config
                });
            });
            
            $('#flow-data-input').val(JSON.stringify(steps));
        }
        
        // Update flow data when template is selected
        $(document).on('change', '.step-template-select', function() {
            updateFlowData();
        });
        
        // Form submission
        $('#flow-builder-form').on('submit', function(e) {
            if (!$('#trigger_type').val()) {
                e.preventDefault();
                alert('Please select a trigger for this flow.');
                return false;
            }
        });
    });
    </script>
    <?php
}

/**
 * Render flow templates page
 */
function ccs_render_flow_templates() {
    $templates = ccs_get_flow_templates();
    ?>
    <div class="wrap">
        <h1>Flow Templates</h1>
        <p class="description">Start with a pre-built automation flow template.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php foreach ($templates as $template) : ?>
                <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h3 style="margin-top: 0;"><?php echo esc_html($template['name']); ?></h3>
                    <p style="color: #646970; font-size: 14px;"><?php echo esc_html($template['description']); ?></p>
                    <div style="margin-top: 16px;">
                        <a href="<?php echo admin_url('admin.php?page=cta-automation&action=new&template=' . urlencode($template['name'])); ?>" class="button button-primary">Use Template</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Save flow steps
 */
function ccs_save_flow_steps($flow_id, $steps) {
    global $wpdb;
    $steps_table = $wpdb->prefix . 'ccs_automation_steps';
    
    // Delete existing steps
    $wpdb->delete($steps_table, ['flow_id' => $flow_id], ['%d']);
    
    // Insert new steps
    $order = 0;
    foreach ($steps as $step) {
        $wpdb->insert($steps_table, [
            'flow_id' => $flow_id,
            'step_type' => sanitize_text_field($step['type'] ?? ''),
            'step_order' => $order++,
            'step_config' => wp_json_encode($step['config'] ?? []),
        ], ['%d', '%s', '%d', '%s']);
    }
}

/**
 * Render simple template builder - matches newsletter area style
 */
function ccs_render_template_builder($template = null) {
    $template_id = $template ? $template->id : 0;
    $template_name = $template ? $template->name : '';
    $template_description = $template ? $template->description : '';
    $template_subject = $template ? $template->subject : '';
    $template_content = $template ? $template->content : '';
    $template_category = $template ? $template->category : '';
    $template_type = $template ? $template->template_type : 'custom';
    ?>
    <style>
        .cta-template-builder {
            max-width: 1200px;
            margin: 0 auto;
        }
        .cta-template-builder-header {
            margin-bottom: 32px;
        }
        .cta-template-builder-header h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 600;
            color: #1d2327;
        }
        .cta-template-builder-header p {
            margin: 0;
            color: #646970;
            font-size: 14px;
        }
        .cta-template-form {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .cta-template-form-section {
            padding: 32px;
            border-bottom: 1px solid #e0e0e0;
        }
        .cta-template-form-section:last-child {
            border-bottom: none;
        }
        .cta-template-form-section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1d2327;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cta-template-form-section-title .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
            color: #2271b1;
        }
        .cta-template-field {
            margin-bottom: 24px;
        }
        .cta-template-field:last-child {
            margin-bottom: 0;
        }
        .cta-template-field label {
            display: block;
            font-weight: 500;
            color: #1d2327;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .cta-template-field input[type="text"],
        .cta-template-field textarea,
        .cta-template-field select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .cta-template-field input[type="text"]:focus,
        .cta-template-field textarea:focus,
        .cta-template-field select:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        .cta-template-field textarea {
            resize: vertical;
            min-height: 80px;
        }
        .cta-template-field-help {
            margin-top: 6px;
            font-size: 13px;
            color: #646970;
            line-height: 1.5;
        }
        .cta-template-field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .cta-template-placeholders {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        .cta-template-placeholder {
            background: #f0f6fc;
            border: 1px solid #c3e4f7;
            color: #2271b1;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-family: monospace;
            cursor: pointer;
            transition: all 0.2s;
        }
        .cta-template-placeholder:hover {
            background: #e0f0ff;
            border-color: #2271b1;
        }
        .cta-template-actions {
            padding: 24px 32px;
            background: #f6f7f7;
            border-top: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .cta-template-actions .button-primary {
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 500;
            height: auto;
        }
        .cta-template-actions .button {
            padding: 10px 24px;
            font-size: 14px;
            height: auto;
        }
        .cta-template-actions-info {
            margin-left: auto;
            font-size: 13px;
            color: #646970;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .cta-template-actions-info .dashicons {
            font-size: 16px;
            color: #2271b1;
        }
    </style>
    
    <div class="wrap cta-template-builder">
        <div class="cta-template-builder-header">
            <h1><?php echo $template ? 'Edit Email Template' : 'Create New Email Template'; ?></h1>
            <p>Save email templates to reuse in campaigns and automation flows</p>
        </div>
        
        <form method="post" id="template-builder-form">
            <?php wp_nonce_field('save_email_template'); ?>
            <input type="hidden" name="save_template" value="1">
            <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">
            
            <div class="cta-template-form">
                <!-- Basic Information -->
                <div class="cta-template-form-section">
                    <h3 class="cta-template-form-section-title">
                        <span class="dashicons dashicons-info"></span>
                        Basic Information
                    </h3>
                    
                    <div class="cta-template-field">
                        <label for="template_name">Template Name <span style="color: #d63638;">*</span></label>
                        <input type="text" id="template_name" name="template_name" value="<?php echo esc_attr($template_name); ?>" 
                               required placeholder="e.g. Welcome Email, Course Announcement">
                        <div class="cta-template-field-help">Give your template a clear, descriptive name</div>
                    </div>
                    
                    <div class="cta-template-field">
                        <label for="template_description">Description</label>
                        <textarea id="template_description" name="template_description" rows="2" 
                                  placeholder="Brief description of when to use this template"><?php echo esc_textarea($template_description); ?></textarea>
                    </div>
                    
                    <div class="cta-template-field-row">
                        <div class="cta-template-field">
                            <label for="template_type">Template Type</label>
                            <select id="template_type" name="template_type">
                                <option value="custom" <?php selected($template_type, 'custom'); ?>>Custom</option>
                                <option value="new_course" <?php selected($template_type, 'new_course'); ?>>New Course Announcement</option>
                                <option value="new_article" <?php selected($template_type, 'new_article'); ?>>New Article Published</option>
                                <option value="upcoming_courses" <?php selected($template_type, 'upcoming_courses'); ?>>Upcoming Courses</option>
                                <option value="welcome" <?php selected($template_type, 'welcome'); ?>>Welcome Email</option>
                                <option value="quarterly" <?php selected($template_type, 'quarterly'); ?>>Quarterly Update</option>
                            </select>
                        </div>
                        
                        <div class="cta-template-field">
                            <label for="template_category">Category</label>
                            <select id="template_category" name="template_category">
                                <option value="">None</option>
                                <option value="newsletter" <?php selected($template_category, 'newsletter'); ?>>Newsletter</option>
                                <option value="promotion" <?php selected($template_category, 'promotion'); ?>>Promotion</option>
                                <option value="welcome" <?php selected($template_category, 'welcome'); ?>>Welcome</option>
                                <option value="simple" <?php selected($template_category, 'simple'); ?>>Simple</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Email Subject -->
                <div class="cta-template-form-section">
                    <h3 class="cta-template-form-section-title">
                        <span class="dashicons dashicons-edit"></span>
                        Subject Line
                    </h3>
                    
                    <div class="cta-template-field">
                        <label for="template_subject">Email Subject <span style="color: #d63638;">*</span></label>
                        <input type="text" id="template_subject" name="template_subject" value="<?php echo esc_attr($template_subject); ?>" 
                               required placeholder="e.g. New Course Available: Manual Handling Training">
                        <div class="cta-template-field-help">
                            Keep it under 60 characters for best results. Use placeholders like {first_name} for personalization.
                        </div>
                    </div>
                </div>
                
                <!-- Email Content -->
                <div class="cta-template-form-section">
                    <h3 class="cta-template-form-section-title">
                        <span class="dashicons dashicons-edit-large"></span>
                        Email Content
                    </h3>
                    
                    <div class="cta-template-field">
                        <label for="template_content">Message <span style="color: #d63638;">*</span></label>
                        <?php 
                        wp_editor($template_content, 'template_content', [
                            'textarea_rows' => 15,
                            'media_buttons' => false,
                            'teeny' => false,
                            'quicktags' => true,
                            'tinymce' => [
                                'toolbar1' => 'bold,italic,underline,|,bullist,numlist,|,link,unlink,|,alignleft,aligncenter,alignright,|,forecolor,|,undo,redo',
                                'toolbar2' => '',
                            ],
                        ]);
                        ?>
                        <div class="cta-template-field-help">
                            <strong>Available placeholders:</strong>
                        </div>
                        <div class="cta-template-placeholders">
                            <span class="cta-template-placeholder" data-placeholder="{site_name}">{site_name}</span>
                            <span class="cta-template-placeholder" data-placeholder="{unsubscribe_link}">{unsubscribe_link}</span>
                            <span class="cta-template-placeholder" data-placeholder="{first_name}">{first_name}</span>
                        </div>
                        <div class="cta-template-field-help" style="margin-top: 12px; padding: 12px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
                            <strong style="color: #2271b1;">📧 Email Formatting:</strong>
                            <p style="margin: 6px 0 0 0; color: #646970; font-size: 13px; line-height: 1.5;">
                                Your email content will be automatically wrapped in a professional HTML template with proper styling, mobile responsiveness, and Outlook compatibility. No code will appear in the final email - only clean, formatted content.
                            </p>
                            <p style="margin: 8px 0 0 0; color: #646970; font-size: 13px; line-height: 1.5;">
                                <strong>✓ Automatically included in every email footer:</strong> Unsubscribe link and Privacy Policy link (required by law).
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="cta-template-actions">
                    <button type="submit" class="button button-primary">
                        Save Template
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=cta-email-templates'); ?>" class="button">
                        Cancel
                    </a>
                    <div class="cta-template-actions-info">
                        <span class="dashicons dashicons-info"></span>
                        <span>Saved templates can be used in campaigns and automation flows</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Copy placeholder to clipboard when clicked
        $('.cta-template-placeholder').on('click', function() {
            var placeholder = $(this).data('placeholder');
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(placeholder).select();
            document.execCommand('copy');
            $temp.remove();
            
            // Show feedback
            var $this = $(this);
            var originalText = $this.text();
            $this.text('✓ Copied!').css({
                'background': '#d1f2eb',
                'border-color': '#00a32a',
                'color': '#00a32a'
            });
            setTimeout(function() {
                $this.text(originalText).css({
                    'background': '#f0f6fc',
                    'border-color': '#c3e4f7',
                    'color': '#2271b1'
                });
            }, 1500);
        });
    });
    </script>
    <?php
}
