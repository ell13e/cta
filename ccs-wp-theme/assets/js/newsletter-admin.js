/**
 * Modern Newsletter Admin JavaScript
 * Enhanced interactions and live preview functionality
 */

(function($) {
    'use strict';

    // ============================================
    // LIVE PREVIEW
    // ============================================
    
    let previewTimeout;
    
    function updatePreview() {
        const subject = $('#email_subject').val() || '(No subject)';
        let content = '';
        
        if (typeof tinymce !== 'undefined' && tinymce.get('email_content')) {
            content = tinymce.get('email_content').getContent() || '<p style="color: #646970; font-style: italic;">Start typing to see your content here...</p>';
        } else {
            content = $('#email_content').val() || '<p style="color: #646970; font-style: italic;">Start typing to see your content here...</p>';
        }
        
        $('.cta-preview-subject').html(escapeHtml(subject));
        $('.cta-email-preview .cta-preview-body').html(content);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Initialize preview on page load
    $(document).ready(function() {
        if ($('.cta-preview-box').length) {
            updatePreview();
            
            // Update preview on subject change
            $('#email_subject').on('input', function() {
                clearTimeout(previewTimeout);
                previewTimeout = setTimeout(updatePreview, 500);
            });
            
            // Update preview on editor change
            if (typeof tinymce !== 'undefined') {
                $(document).on('tinymce-editor-init', function(event, editor) {
                    editor.on('input change', function() {
                        clearTimeout(previewTimeout);
                        previewTimeout = setTimeout(updatePreview, 500);
                    });
                });
            }
        }
    });

    // ============================================
    // DRAG & DROP CSV IMPORT
    // ============================================
    
    $(document).ready(function() {
        const dropzone = $('.cta-import-dropzone');
        const fileInput = $('.cta-import-file-input');
        
        if (dropzone.length) {
            dropzone.on('click', function() {
                fileInput.trigger('click');
            });
            
            dropzone.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });
            
            dropzone.on('dragleave dragend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });
            
            dropzone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
                
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    fileInput[0].files = files;
                    handleFileUpload(files[0]);
                }
            });
            
            fileInput.on('change', function() {
                if (this.files.length > 0) {
                    handleFileUpload(this.files[0]);
                }
            });
        }
        
        function handleFileUpload(file) {
            if (!file.name.endsWith('.csv')) {
                alert('Please upload a CSV file');
                return;
            }
            
            $('.cta-import-text').text('Uploading: ' + file.name);
            $('.cta-import-progress').show();
            
            // Submit the form with the file
            const form = fileInput.closest('form');
            if (form.length) {
                form.submit();
            }
        }
    });

    // ============================================
    // RECIPIENT COUNTER
    // ============================================
    
    function updateRecipientCount() {
        const mode = $('input[name="recipient_mode"]:checked').val();
        
        if (mode === 'all') {
            const totalActive = parseInt($('#cta-total-active-count').text()) || 0;
            $('#cta-recipient-count').text('Recipients: ' + totalActive);
        } else {
            const selectedTags = $('.cta-recipient-tag:checked').length;
            if (selectedTags === 0) {
                $('#cta-recipient-count').text('Recipients: 0 (select tags)');
            } else {
                // AJAX call to get count
                $.post(ajaxurl, {
                    action: 'cta_get_recipient_count_by_tags',
                    tag_ids: $('.cta-recipient-tag:checked').map(function() {
                        return $(this).val();
                    }).get()
                }, function(response) {
                    if (response.success) {
                        $('#cta-recipient-count').text('Recipients: ' + response.data.count);
                    }
                });
            }
        }
    }
    
    $(document).on('change', 'input[name="recipient_mode"], .cta-recipient-tag', updateRecipientCount);

    // ============================================
    // TEMPLATE SELECTION
    // ============================================
    
    $(document).on('click', '.cta-template-card', function() {
        $('.cta-template-card').removeClass('selected');
        $(this).addClass('selected');
        
        const templateType = $(this).data('template-type');
        const templateId = $(this).data('template-id');
        
        if (templateId) {
            // Load template via AJAX
            $.post(ajaxurl, {
                action: 'cta_load_email_template',
                template_id: templateId
            }, function(response) {
                if (response.success) {
                    $('#email_subject').val(response.data.subject);
                    
                    if (typeof tinymce !== 'undefined' && tinymce.get('email_content')) {
                        tinymce.get('email_content').setContent(response.data.content);
                    } else {
                        $('#email_content').val(response.data.content);
                    }
                    
                    updatePreview();
                }
            });
        }
    });

    // ============================================
    // BULK ACTIONS
    // ============================================
    
    $(document).on('change', '.cta-subscriber-checkbox', function() {
        const checkedCount = $('.cta-subscriber-checkbox:checked').length;
        $('.cta-bulk-action-bar').toggle(checkedCount > 0);
        $('.cta-selected-count').text(checkedCount);
    });
    
    $('#cta-select-all-subscribers').on('change', function() {
        $('.cta-subscriber-checkbox').prop('checked', $(this).prop('checked')).trigger('change');
    });

    // ============================================
    // TAG MANAGEMENT
    // ============================================
    
    $('.cta-tag-checkbox').on('change', function() {
        $(this).next('.cta-tag-label').toggleClass('selected', $(this).prop('checked'));
    });

    // ============================================
    // CALENDAR INTERACTIONS
    // ============================================
    
    $('.cta-calendar-event').on('click', function() {
        const campaignId = $(this).data('campaign-id');
        if (campaignId) {
            window.location.href = ajaxurl.replace('admin-ajax.php', '') + 
                'admin.php?page=cta-newsletter-campaigns&campaign=' + campaignId;
        }
    });

    // ============================================
    // QUICK SCHEDULE FROM CALENDAR
    // ============================================
    
    $(document).on('click', '.cta-calendar-day[data-date]', function(e) {
        if (!$(e.target).closest('.cta-calendar-event').length) {
            const date = $(this).data('date');
            const url = ajaxurl.replace('admin-ajax.php', '') +
                'admin.php?page=cta-newsletter-compose&schedule_date=' + date;
            window.location.href = url;
        }
    });

    // ============================================
    // SMOOTH SCROLLING FOR ANCHOR LINKS
    // ============================================
    
    $('a[href^="#"]').on('click', function(e) {
        const target = $(this.hash);
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 32
            }, 400);
        }
    });

    // ============================================
    // CONFIRM DANGEROUS ACTIONS
    // ============================================
    
    $('.cta-action-delete, .cta-action-remove').on('click', function(e) {
        if (!confirm('Are you sure? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });

    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    
    window.ctaShowToast = function(message, type = 'success') {
        const toast = $('<div>')
            .addClass('cta-toast cta-toast-' + type)
            .text(message)
            .appendTo('body')
            .fadeIn(300);
        
        setTimeout(function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    };

    // ============================================
    // EXPORT SUBSCRIBERS
    // ============================================
    
    $('#cta-export-subscribers').on('click', function(e) {
        e.preventDefault();
        
        const statusFilter = $('[name="status_filter"]').val() || 'all';
        const searchQuery = $('[name="s"]').val() || '';
        const tagFilter = $('[name="tag_filter"]').val() || '';
        
        const params = new URLSearchParams({
            action: 'cta_export_subscribers_csv',
            status_filter: statusFilter,
            search: searchQuery,
            tag_filter: tagFilter,
            _wpnonce: $('#cta-export-nonce').val()
        });
        
        window.location.href = ajaxurl + '?' + params.toString();
    });

    // ============================================
    // DOWNLOAD SAMPLE CSV
    // ============================================
    
    $('#cta-download-sample-csv').on('click', function(e) {
        e.preventDefault();
        window.location.href = window.location.pathname + '?page=cta-newsletter-subscribers&action=download_sample_csv';
    });

    // ============================================
    // LIVE SEARCH WITH DEBOUNCE
    // ============================================
    
    let searchTimeout;
    $('input[name="s"]').on('input', function() {
        clearTimeout(searchTimeout);
        const $form = $(this).closest('form');
        
        searchTimeout = setTimeout(function() {
            $form.submit();
        }, 800);
    });

    // ============================================
    // CAMPAIGN STATS TOOLTIPS
    // ============================================
    
    $('.cta-campaign-stat').each(function() {
        const $stat = $(this);
        const value = $stat.find('.cta-campaign-stat-value').text();
        const label = $stat.find('.cta-campaign-stat-label').text();
        
        $stat.attr('title', value + ' ' + label);
    });

})(jQuery);
