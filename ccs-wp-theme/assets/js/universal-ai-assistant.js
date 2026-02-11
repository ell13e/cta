/**
 * Universal AI Assistant
 * Automatically detects textareas and adds AI generate buttons
 * 
 * NOTE: This script ONLY targets textareas and WYSIWYG editors.
 * It explicitly EXCLUDES select/dropdown fields - those don't need AI generation.
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        buttonText: '✨ Generate with AI',
        buttonClass: 'cta-universal-ai-button',
        statusClass: 'cta-universal-ai-status',
        loadingText: 'Generating...',
        errorText: 'Error: ',
        successText: 'Generated!'
    };

    /**
     * Detect all textareas on the page
     */
    function detectTextareas() {
        const textareas = [];
        
        // Standard textareas only - explicitly exclude select/dropdown fields
        $('textarea:not(.cta-ai-processed)').each(function() {
            const $textarea = $(this);
            const id = $textarea.attr('id') || '';
            const name = $textarea.attr('name') || '';
            
            // Skip if already has AI button
            if ($textarea.siblings('.' + config.buttonClass).length > 0) {
                return;
            }
            
            // Skip hidden textareas
            if (!$textarea.is(':visible')) {
                return;
            }
            
            // Skip if inside a select field wrapper (shouldn't happen, but safety check)
            if ($textarea.closest('select, .select2-container, .acf-select').length > 0) {
                return;
            }
            
            // Skip ACF fields that already have AI (they're handled separately)
            if ($textarea.closest('.acf-field').find('.cta-generate-course-description, .cta-generate-course-meta-description').length > 0) {
                return;
            }
            
            // Skip if near a select field (don't add AI buttons to dropdown areas)
            const $parent = $textarea.closest('.form-field, .acf-field, .inside, td, th');
            if ($parent.length && $parent.find('select').length > 0) {
                // Only skip if the select is the main field (not just any select in the container)
                const $select = $parent.find('select').first();
                const selectName = $select.attr('name') || '';
                // If there's a select field with a similar name, skip adding AI to this textarea
                if (selectName && name && selectName.toLowerCase() === name.toLowerCase()) {
                    return;
                }
            }
            
            textareas.push({
                element: $textarea,
                id: id,
                name: name,
                type: 'textarea'
            });
        });
        
        // WordPress editor (TinyMCE)
        if (typeof tinymce !== 'undefined') {
            tinymce.on('AddEditor', function(e) {
                const editor = e.editor;
                const editorId = editor.id;
                const $editorContainer = $('#' + editorId).closest('.wp-editor-wrap');
                
                // Skip if already processed
                if ($editorContainer.find('.' + config.buttonClass).length > 0) {
                    return;
                }
                
                // Add button to editor toolbar area
                const $toolbar = $editorContainer.find('.wp-editor-tools');
                if ($toolbar.length > 0 && !$toolbar.find('.' + config.buttonClass).length) {
                    const $button = createAIButton(editorId, 'wysiwyg');
                    $button.css({
                        'margin-left': '10px',
                        'vertical-align': 'middle'
                    });
                    $toolbar.append($button);
                }
            });
        }
        
        return textareas;
    }

    /**
     * Create AI generate button
     */
    function createAIButton(fieldId, fieldType) {
        const $button = $('<button>')
            .attr('type', 'button')
            .addClass('button button-small ' + config.buttonClass)
            .text(config.buttonText)
            .data('field-id', fieldId)
            .data('field-type', fieldType);
        
        const $status = $('<span>')
            .addClass(config.statusClass)
            .css({
                'margin-left': '10px',
                'font-size': '12px'
            });
        
        $button.on('click', function() {
            handleAIGenerate($(this), fieldId, fieldType);
        });
        
        return $button.add($status);
    }

    /**
     * Handle AI generation
     */
    function handleAIGenerate($button, fieldId, fieldType) {
        const $status = $button.siblings('.' + config.statusClass);
        const $textarea = $('#' + fieldId);
        
        // Get field context
        const context = getFieldContext(fieldId, fieldType);
        
        // Show loading state
        $button.prop('disabled', true);
        $status.text(config.loadingText).css('color', '#646970');
        
        // Make AJAX request
        $.ajax({
            url: ctaUniversalAI.ajaxurl || ajaxurl,
            type: 'POST',
            data: {
                action: 'cta_generate_field_content',
                nonce: ctaUniversalAI.nonce,
                field_id: fieldId,
                field_type: fieldType,
                field_name: context.name,
                field_label: context.label,
                page_context: context.page,
                current_value: getFieldValue(fieldId, fieldType)
            },
            success: function(response) {
                if (response.success && response.data && response.data.content) {
                    setFieldValue(fieldId, fieldType, response.data.content);
                    $status.text(config.successText).css('color', '#00a32a');
                    setTimeout(function() {
                        $status.text('');
                    }, 3000);
                } else {
                    $status.text(config.errorText + (response.data && response.data.message ? response.data.message : 'Unknown error')).css('color', '#d63638');
                }
            },
            error: function(xhr, status, error) {
                $status.text(config.errorText + error).css('color', '#d63638');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Get field context for better prompts
     */
    function getFieldContext(fieldId, fieldType) {
        const context = {
            name: '',
            label: '',
            page: ''
        };
        
        // Try to find label
        const $label = $('label[for="' + fieldId + '"]');
        if ($label.length) {
            context.label = $label.text().trim();
        }
        
        // Try to find field name
        const $field = $('#' + fieldId);
        if ($field.length) {
            context.name = $field.attr('name') || fieldId;
        }
        
        // Detect page context
        const $body = $('body');
        if ($body.hasClass('post-php')) {
            context.page = 'post-edit';
        } else if ($body.hasClass('settings-page')) {
            context.page = 'settings';
        } else if (window.location.href.indexOf('seo') !== -1) {
            context.page = 'seo-settings';
            }
            
            return context;
    }

    /**
     * Get field value
     */
    function getFieldValue(fieldId, fieldType) {
        if (fieldType === 'wysiwyg' && typeof tinymce !== 'undefined') {
            const editor = tinymce.get(fieldId);
            if (editor) {
                return editor.getContent();
            }
        }
        
        const $field = $('#' + fieldId);
        return $field.val() || '';
    }

    /**
     * Set field value
     */
    function setFieldValue(fieldId, fieldType, value) {
        if (fieldType === 'wysiwyg' && typeof tinymce !== 'undefined') {
            const editor = tinymce.get(fieldId);
            if (editor) {
                editor.setContent(value);
                return;
            }
        }
        
        const $field = $('#' + fieldId);
        $field.val(value).trigger('change');
    }

    /**
     * Initialize on page load
     */
    function init() {
        // Wait for DOM to be ready
        $(document).ready(function() {
            // Small delay to ensure all fields are rendered
            setTimeout(function() {
                const textareas = detectTextareas();
                
                textareas.forEach(function(item) {
                    const $textarea = item.element;
                    const $wrapper = $textarea.closest('.form-field, .acf-field, .inside, td, th');
                    
                    // Explicitly skip if wrapper contains select/dropdown fields
                    if ($wrapper.length && $wrapper.find('select').length > 0) {
                        // Don't add AI buttons to areas with dropdowns
                        $textarea.addClass('cta-ai-processed');
                        return;
                    }
                    
                    if ($wrapper.length) {
                        // Add button after label or before textarea
                        const $label = $wrapper.find('label');
                        if ($label.length) {
                            $label.after(createAIButton(item.id, item.type));
                        } else {
                            $textarea.before(createAIButton(item.id, item.type));
                        }
                    } else {
                        // Fallback: add after textarea (only if no select fields nearby)
                        if ($textarea.siblings('select').length === 0 && $textarea.parent().find('select').length === 0) {
                            $textarea.after(createAIButton(item.id, item.type));
                        }
                    }
                    
                    // Mark as processed
                    $textarea.addClass('cta-ai-processed');
                });
            }, 500);
        });
    }

    // Initialize
    init();
    
    // Re-initialize on ACF field additions (for repeaters)
    if (typeof acf !== 'undefined') {
        acf.addAction('append_field', function() {
            setTimeout(function() {
                const textareas = detectTextareas();
                textareas.forEach(function(item) {
                    const $textarea = item.element;
                    const $wrapper = $textarea.closest('.acf-field');
                    
                    // Skip if this is a select field wrapper
                    if ($wrapper.length && $wrapper.find('select').length > 0) {
                        return;
                    }
                    
                    if ($wrapper.length) {
                        const $label = $wrapper.find('label');
                        if ($label.length) {
                            $label.after(createAIButton(item.id, item.type));
                        }
                        $textarea.addClass('cta-ai-processed');
                    }
                });
            }, 100);
        });
    }
    
})(jQuery);
