/**
 * CRM Inline Editing
 * Handles inline status, assignment, and notes editing for form submissions
 */
(function($) {
    'use strict';
    
    // Only run if ctaCRM object is available (localized from PHP)
    if (typeof ctaCRM === 'undefined') {
        return;
    }
    
    $(document).ready(function() {
        // Auto-submit filters on change (form submissions list view)
        // Keeps WP's built-in filter button as a no-JS fallback.
        if ($('body').hasClass('post-type-form_submission')) {
            $('body').addClass('cta-js');

            // Move the custom tab UI above WP's top tablenav so Bulk Actions sits below tabs.
            // WP renders bulk actions before firing `restrict_manage_posts`, so without this
            // the dropdown ends up visually "above" our tabs.
            (function moveTabsAboveTableNav() {
                var $tabs = $('.cta-submission-tabs').first();
                var $tablenavTop = $('.tablenav.top').first();

                if (!$tabs.length || !$tablenavTop.length) {
                    return;
                }

                // Keep within the same form; just reorder for a saner header layout.
                $tabs.insertBefore($tablenavTop);
            })();

            var autoFilterSelector = '#date_filter, #form_type_filter, #followup_filter, #marketing_consent_filter, #email_status_filter';
            $(document).on('change', autoFilterSelector, function() {
                var $form = $(this).closest('form');
                if (!$form.length) {
                    $form = $('#posts-filter');
                }

                var formEl = $form.get(0);
                if (!formEl) return;

                // Prefer requestSubmit so the submit event fires properly.
                if (typeof formEl.requestSubmit === 'function') {
                    formEl.requestSubmit();
                } else {
                    formEl.submit();
                }
            });
        }

        // Handle assigned to dropdown change
        $(document).on('change', '.cta-assigned-to-select', function() {
            var $select = $(this);
            var postId = $select.data('post-id');
            var newAssignee = $select.val();
            var oldAssignee = $select.data('original-value') || '';
            var $wrapper = $select.closest('.cta-assigned-to-wrapper');
            
            // Store original value if not set
            if (!$select.data('original-value')) {
                $select.data('original-value', oldAssignee);
            }
            
            // Only proceed if changed and not empty
            if (newAssignee !== oldAssignee && newAssignee !== '') {
                // Ask if they want to send email notification
                var sendEmail = confirm('Assign this lead to ' + newAssignee + '?\n\nDo you want to send an email notification to ' + newAssignee + ' about this lead?');
                
                // Disable select during save
                $select.prop('disabled', true);
                
                $.ajax({
                    url: ctaCRM.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cta_update_submission_assigned',
                        post_id: postId,
                        assigned_to: newAssignee,
                        send_email: sendEmail ? '1' : '0',
                        nonce: ctaCRM.nonces.assignment
                    },
                    success: function(response) {
                        if (response.success) {
                            $select.data('original-value', newAssignee);
                            // Show success feedback
                            $wrapper.append('<span class="cta-update-success" style="color: #22c55e; margin-left: 8px; font-size: 11px;">✓</span>');
                            setTimeout(function() {
                                $wrapper.find('.cta-update-success').fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 2000);
                            
                            if (sendEmail && response.data && response.data.email_sent) {
                                $wrapper.append('<span class="cta-email-sent" style="color: #22c55e; margin-left: 4px; font-size: 10px;" title="Email sent">📧</span>');
                                setTimeout(function() {
                                    $wrapper.find('.cta-email-sent').fadeOut(function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                            } else if (sendEmail && (!response.data || !response.data.email_sent)) {
                                $wrapper.append('<span class="cta-email-failed" style="color: #ef4444; margin-left: 4px; font-size: 10px;" title="Email failed">⚠️</span>');
                                setTimeout(function() {
                                    $wrapper.find('.cta-email-failed').fadeOut(function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                            }
                        } else {
                            alert('Error updating assignment: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                            $select.val(oldAssignee); // Revert
                        }
                    },
                    error: function() {
                        alert('Error updating assignment. Please try again.');
                        $select.val(oldAssignee); // Revert
                    },
                    complete: function() {
                        $select.prop('disabled', false);
                    }
                });
            } else if (newAssignee === '') {
                // Unassigning
                $select.prop('disabled', true);
                
                $.ajax({
                    url: ctaCRM.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cta_update_submission_assigned',
                        post_id: postId,
                        assigned_to: '',
                        send_email: '0',
                        nonce: ctaCRM.nonces.assignment
                    },
                    success: function(response) {
                        if (response.success) {
                            $select.data('original-value', '');
                            $wrapper.append('<span class="cta-update-success" style="color: #22c55e; margin-left: 8px; font-size: 11px;">✓</span>');
                            setTimeout(function() {
                                $wrapper.find('.cta-update-success').fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 2000);
                        }
                    },
                    complete: function() {
                        $select.prop('disabled', false);
                    }
                });
            }
        });
        
        // Store original value on focus
        $(document).on('focus', '.cta-assigned-to-select', function() {
            var $select = $(this);
            if (!$select.data('original-value')) {
                $select.data('original-value', $select.val());
            }
        });
        
        // Handle status change
        $(document).on('change', '.cta-followup-status-select', function() {
            var $select = $(this);
            var postId = $select.data('post-id');
            var newStatus = $select.val();
            var oldStatus = $select.data('original-value') || '';
            var $wrapper = $select.closest('.cta-followup-status-wrapper');
            
            // If changing to cancelled, show confirmation
            if (newStatus === 'cancelled' && oldStatus !== 'cancelled') {
                if (!confirm('Are you sure you want to cancel this lead?\n\nThis will move it to the archive. You can restore it later if needed.')) {
                    $select.val(oldStatus); // Revert selection
                    return;
                }
            }
            
            // Store original value for potential revert
            if (!$select.data('original-value')) {
                $select.data('original-value', oldStatus);
            }
            
            // Disable select during save
            $select.prop('disabled', true);
            
            $.ajax({
                url: ctaCRM.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cta_update_submission_status',
                    post_id: postId,
                    status: newStatus,
                    nonce: ctaCRM.nonces.status
                },
                success: function(response) {
                    if (response.success) {
                        // Show success feedback
                        $wrapper.append('<span class="cta-update-success" style="color: #22c55e; margin-left: 8px; font-size: 11px;">✓</span>');
                        setTimeout(function() {
                            $wrapper.find('.cta-update-success').fadeOut(function() {
                                $(this).remove();
                            });
                        }, 2000);
                        
                        // If cancelled, show archive message and optionally fade row
                        if (newStatus === 'cancelled') {
                            var $row = $select.closest('tr');
                            $row.css('opacity', '0.6');
                            var $nameCell = $row.find('td.column-title');
                            if ($nameCell.find('.cta-archived-badge').length === 0) {
                                $nameCell.append('<span class="cta-archived-badge" style="color: #999; font-size: 11px; margin-left: 8px; font-style: italic;">(Archived)</span>');
                            }
                            // Show message about archive
                            $wrapper.append('<div class="cta-archive-notice" style="color: #f59e0b; font-size: 11px; margin-top: 4px;">Moved to archive</div>');
                            setTimeout(function() {
                                $wrapper.find('.cta-archive-notice').fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 3000);
                        } else if (oldStatus === 'cancelled') {
                            // If restoring from cancelled, remove archive styling
                            var $row = $select.closest('tr');
                            $row.css('opacity', '1');
                            $row.find('.cta-archived-badge').remove();
                        }
                        
                        // Update original value
                        $select.data('original-value', newStatus);
                    } else {
                        alert('Error updating status: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                        $select.val(oldStatus); // Revert
                    }
                },
                error: function() {
                    alert('Error updating status. Please try again.');
                    $select.val(oldStatus); // Revert
                },
                complete: function() {
                    $select.prop('disabled', false);
                }
            });
        });
        
        // Handle note editing
        $(document).on('click', '.cta-notes-preview, .cta-add-note-btn', function(e) {
            e.preventDefault();
            var $trigger = $(this);
            var postId = $trigger.data('post-id') || $trigger.closest('.cta-followup-status-wrapper').find('.cta-followup-status-select').data('post-id');
            var $notesContainer = $trigger.closest('.cta-followup-notes-quick');
            var currentNotes = $trigger.text().trim();
            
            // Replace with textarea
            var $textarea = $('<textarea class="cta-notes-edit" style="width: 100%; min-width: 250px; max-width: 400px; font-size: 12px; padding: 4px; margin-top: 4px;" rows="2" placeholder="Add a note (e.g., Update this course! Booked 3 spaces)">' + (currentNotes !== '+ Add note' ? currentNotes : '') + '</textarea>');
            var $saveBtn = $('<button type="button" class="button button-small cta-save-note" style="margin-top: 4px; margin-right: 4px;" data-post-id="' + postId + '">Save</button>');
            var $cancelBtn = $('<button type="button" class="button-link cta-cancel-note" style="margin-top: 4px;">Cancel</button>');
            var $buttonGroup = $('<div class="cta-note-buttons"></div>').append($saveBtn).append($cancelBtn);
            
            $notesContainer.html($textarea).append($buttonGroup);
            $textarea.focus();
            
            // Save note
            $saveBtn.on('click', function() {
                var notes = $textarea.val().trim();
                var $saveBtn = $(this);
                
                $saveBtn.prop('disabled', true).text('Saving...');
                
                $.ajax({
                    url: ctaCRM.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cta_update_submission_notes',
                        post_id: postId,
                        notes: notes,
                        nonce: ctaCRM.nonces.notes
                    },
                    success: function(response) {
                        if (response.success) {
                            if (notes) {
                                var previewLen = 180;
                                var preview = notes.length > previewLen ? notes.substring(0, previewLen) + '…' : notes;
                                var safePreview = preview.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                                var safeTitle = notes.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                                $notesContainer.html('<div class="cta-notes-preview" title="' + safeTitle + '" data-post-id="' + postId + '">' + safePreview + '</div>');
                            } else {
                                $notesContainer.html('<button type="button" class="button-link cta-add-note-btn" data-post-id="' + postId + '">+ Add note</button>');
                            }
                        } else {
                            alert('Error saving note: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                            $saveBtn.prop('disabled', false).text('Save');
                        }
                    },
                    error: function() {
                        alert('Error saving note. Please try again.');
                        $saveBtn.prop('disabled', false).text('Save');
                    }
                });
            });
            
            // Cancel editing
            $cancelBtn.on('click', function() {
                var originalText = currentNotes !== '+ Add note' ? currentNotes : '';
                if (originalText) {
                    var previewLen = 180;
                    var preview = originalText.length > previewLen ? originalText.substring(0, previewLen) + '…' : originalText;
                    var safePreview = preview.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    var safeTitle = originalText.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    $notesContainer.html('<div class="cta-notes-preview" title="' + safeTitle + '" data-post-id="' + postId + '">' + safePreview + '</div>');
                } else {
                    $notesContainer.html('<button type="button" class="button-link cta-add-note-btn" data-post-id="' + postId + '">+ Add note</button>');
                }
            });
            
            // Save on Enter (Ctrl/Cmd + Enter)
            $textarea.on('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    $saveBtn.click();
                } else if (e.key === 'Escape') {
                    $cancelBtn.click();
                }
            });
        });
    });
    
})(jQuery);

