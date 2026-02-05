/**
 * "Generate with AI" for attachment details: Title, Caption, Alt text, Description.
 * Works on Media Library (upload.php) and in the media modal.
 * See: W3C alt decision tree (accessibility), WordPress media field roles.
 */
(function($) {
    'use strict';

    if (typeof ctaAiAltText === 'undefined') {
        return;
    }

    var nonce = ctaAiAltText.nonce;
    var ajaxurl = ctaAiAltText.ajaxurl;
    var label = ctaAiAltText.label || 'Generate with AI';
    var loadingLabel = ctaAiAltText.loading || 'Generating…';
    var errorLabel = ctaAiAltText.error || 'Could not generate.';

    function getAttachmentIdFromContainer($container) {
        var $input = $container.find('input[name*="attachments["], textarea[name*="attachments["]').first();
        var name = $input.attr('name') || '';
        var match = name.match(/attachments\[(\d+)\]/);
        if (match) return parseInt(match[1], 10);
        var urlMatch = window.location.search.match(/[?&]item=(\d+)/);
        if (urlMatch) return parseInt(urlMatch[1], 10);
        return 0;
    }

    function findFieldsInContainer($container) {
        var fields = { title: null, caption: null, alt: null, description: null };
        $container.find('.setting, .field, [class*="attachment-details"]').each(function() {
            var $row = $(this);
            var labelText = ($row.find('label').first().text() || '').toLowerCase();
            var $input = $row.find('input[type="text"], textarea').first();
            if (!$input.length) $input = $row.find('input, textarea').first();
            if (!$input.length) return;
            var name = ($input.attr('name') || '').toLowerCase();
            if (labelText.indexOf('title') !== -1 && name.indexOf('title') !== -1 || name.indexOf('[title]') !== -1) {
                fields.title = $input;
            } else if (labelText.indexOf('caption') !== -1 || name.indexOf('[caption]') !== -1) {
                fields.caption = $input;
            } else if (labelText.indexOf('alternative') !== -1 || labelText.indexOf('alt') !== -1 && labelText.indexOf('text') !== -1 || name.indexOf('[alt]') !== -1) {
                fields.alt = $input;
            } else if (labelText.indexOf('description') !== -1 || name.indexOf('[description]') !== -1) {
                fields.description = $input;
            }
        });
        if (!fields.alt) {
            var $alt = $container.find('input[name*="[alt]"], textarea[name*="[alt]"]').first();
            if ($alt.length) fields.alt = $alt;
        }
        if (!fields.title) {
            var $title = $container.find('input[name*="[title]"]').first();
            if ($title.length) fields.title = $title;
        }
        if (!fields.caption) {
            var $cap = $container.find('textarea[name*="[caption]"], input[name*="[caption]"]').first();
            if ($cap.length) fields.caption = $cap;
        }
        if (!fields.description) {
            var $desc = $container.find('textarea[name*="[description]"], input[name*="[description]"]').first();
            if ($desc.length) fields.description = $desc;
        }
        return fields;
    }

    function injectButton() {
        var $label = $('label').filter(function() {
            var t = $(this).text().toLowerCase();
            return t.indexOf('alternative') !== -1 || (t.indexOf('alt') !== -1 && t.indexOf('text') !== -1);
        }).first();

        if (!$label.length) return;

        var $container = $label.closest('.attachment-details, .media-frame-content .attachment-info, [class*="attachment-details"], .media-sidebar');
        if (!$container.length) $container = $label.closest('.settings, form').first();
        if (!$container.length) $container = $label.parent().parent();

        if ($container.find('.cta-ai-alt-generate').length) return;

        var $toolbar = $container.find('.actions, .attachment-details .setting').first();
        var $insertTarget = $toolbar.length ? $toolbar : $container.children().first();
        var $wrap = $('<div class="cta-ai-alt-toolbar" style="margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #ddd;"></div>');
        var $btn = $('<button type="button" class="button button-primary cta-ai-alt-generate">' + label + '</button>');
        $wrap.append($btn);
        $insertTarget.before($wrap);
    }

    function runInject() {
        injectButton();
    }

    $(function() {
        runInject();
        var observer = typeof MutationObserver !== 'undefined' ? new MutationObserver(function() {
            runInject();
        }) : null;
        if (observer) {
            var target = document.querySelector('#wpbody') || document.body;
            if (target) observer.observe(target, { childList: true, subtree: true });
        }
        setInterval(runInject, 1500);
    });

    $(document).on('click', '.cta-ai-alt-generate', function() {
        var $btn = $(this);
        var $container = $btn.closest('.attachment-details, .media-frame-content, .media-sidebar, [class*="attachment-details"]');
        if (!$container.length) $container = $btn.closest('form').length ? $btn.closest('form') : $(document.body);

        var attachmentId = getAttachmentIdFromContainer($container);
        if (!attachmentId) {
            alert(errorLabel);
            return;
        }

        var fields = findFieldsInContainer($container);
        if (!fields.alt && !fields.title) {
            alert(errorLabel);
            return;
        }

        $btn.prop('disabled', true).text(loadingLabel);

        $.post(ajaxurl, {
            action: 'cta_generate_ai_alt_text',
            nonce: nonce,
            attachment_id: attachmentId
        })
            .done(function(res) {
                if (res.success && res.data) {
                    var d = res.data;
                    if (fields.title && d.title !== undefined) fields.title.val(d.title || '').trigger('change');
                    if (fields.caption && d.caption !== undefined) fields.caption.val(d.caption || '').trigger('change');
                    if (fields.alt && d.alt !== undefined) fields.alt.val(d.alt || '').trigger('change');
                    if (fields.description && d.description !== undefined) fields.description.val(d.description || '').trigger('change');
                } else {
                    alert(res.data && res.data.message ? res.data.message : errorLabel);
                }
            })
            .fail(function(xhr) {
                var msg = errorLabel;
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                alert(msg);
            })
            .always(function() {
                $btn.prop('disabled', false).text(label);
            });
    });
})(jQuery);
