/**
 * Admin Media Library Integration
 * Allows changing course and course event images from admin list views
 * 
 * @package CTA_Theme
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Get post type from URL or from page context
        var postType = getUrlParameter('post_type');
        
        // If on edit page, get post type from body class or meta box
        if (!postType) {
            var $body = $('body');
            if ($body.hasClass('post-type-course')) {
                postType = 'course';
            } else if ($body.hasClass('post-type-course_event')) {
                postType = 'course_event';
            }
        }
        
        if (postType !== 'course' && postType !== 'course_event') {
            return;
        }

        // Initialize media library integration
        initMediaLibraryIntegration();
        
        // Also enhance featured image meta box on edit pages
        enhanceFeaturedImageMetaBox(postType);
    });

    /**
     * Initialize media library integration
     */
    function initMediaLibraryIntegration() {
        // Make thumbnails and missing badges clickable
        $(document).on('click', '.cta-change-image', function(e) {
            e.preventDefault();
            
            var $element = $(this);
            var postId = $element.data('post-id');
            var postType = $element.data('post-type');
            var currentImageId = $element.data('image-id') || null;

            if (!postId || !postType) {
                return;
            }

            openMediaLibrary(postId, postType, currentImageId, $element);
        });
    }

    /**
     * Open WordPress media library
     */
    function openMediaLibrary(postId, postType, currentImageId, $element) {
        // Check if media frame already exists
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('Media library is not available. Please refresh the page.');
            return;
        }

        // Create media frame
        var frame = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        // Pre-select current image if exists
        if (currentImageId) {
            frame.on('open', function() {
                var selection = frame.state().get('selection');
                var attachment = wp.media.attachment(currentImageId);
                attachment.fetch();
                selection.add(attachment ? [attachment] : []);
            });
        }

        // Handle image selection
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            
            if (!attachment || !attachment.id) {
                return;
            }

            // Save image via AJAX
            saveImage(postId, postType, attachment.id, attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url, $element);
        });

        // Open media library
        frame.open();
    }

    /**
     * Save image via AJAX
     */
    function saveImage(postId, postType, imageId, imageUrl, $element) {
        // Show loading state
        var $originalContent = $element.clone();
        $element.css('opacity', '0.5');
        
        if ($element.is('img')) {
            $element.attr('src', imageUrl);
        } else {
            // Replace missing badge with loading indicator
            $element.html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');
        }

        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cta_save_image',
                post_id: postId,
                post_type: postType,
                image_id: imageId,
                nonce: ctaAdminMediaLibrary.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update element
                    if ($element.is('img')) {
                        $element.attr('src', imageUrl);
                        $element.data('image-id', imageId);
                        $element.css('opacity', '1');
                        
                        // If on edit page, update featured image meta box
                        if ((postType === 'course' || postType === 'course_event') && $('#postimagediv').length) {
                            // Update the thumbnail ID input (this is what WordPress saves)
                            $('#_thumbnail_id').val(imageId);
                            
                            // Trigger WordPress's built-in update mechanism
                            // This ensures the meta box displays correctly
                            var $featuredBox = $('#postimagediv');
                            var $inside = $featuredBox.find('.inside');
                            var $currentImg = $inside.find('img');
                            
                            if ($currentImg.length) {
                                // Update existing image src
                                $currentImg.attr('src', imageUrl);
                            } else {
                                // No image yet - create the preview structure WordPress expects
                                var $setThumbnail = $inside.find('#set-post-thumbnail');
                                if ($setThumbnail.length) {
                                    // Hide "Set featured image" link
                                    $setThumbnail.hide();
                                    
                                    // Create image preview
                                    var $imgWrap = $('<div class="wp-post-thumbnail"></div>');
                                    var $img = $('<img>', {
                                        src: imageUrl,
                                        style: 'max-width: 100%; height: auto; display: block;'
                                    });
                                    $imgWrap.append($img);
                                    $inside.prepend($imgWrap);
                                }
                            }
                            
                            // Show remove link if it exists and is hidden
                            var $removeLink = $('#remove-post-thumbnail');
                            if ($removeLink.length && $removeLink.is(':hidden')) {
                                $removeLink.show();
                            }
                            
                            // Trigger change event on thumbnail input to notify WordPress
                            $('#_thumbnail_id').trigger('change');
                        }
                    } else if ($element.closest('.acf-field-image').length || $element.hasClass('acf-image-uploader')) {
                        // If it's an ACF image field, trigger ACF update
                        if (typeof acf !== 'undefined') {
                            var $acfField = $element.closest('.acf-field-image');
                            if ($acfField.length) {
                                var fieldKey = $acfField.data('key');
                                var field = acf.getField(fieldKey);
                                if (field) {
                                    // Set the image ID
                                    field.val(imageId);
                                    // Trigger change event to update preview
                                    field.$el.find('input[type="hidden"]').val(imageId).trigger('change');
                                }
                            }
                        }
                        // Small delay then reload to show updated image
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    } else {
                        // Replace badge with image
                        var $newImg = $('<img>', {
                            src: imageUrl,
                            class: 'cta-admin-thumbnail cta-change-image',
                            'data-post-id': postId,
                            'data-post-type': postType,
                            'data-image-id': imageId,
                            title: 'Click to change image',
                            alt: 'Thumbnail',
                            style: 'cursor: pointer;'
                        });
                        $element.replaceWith($newImg);
                    }
                    
                    // Show success message
                    showNotice('Image updated successfully.', 'success');
                } else {
                    // Restore original content
                    if ($element.is('img')) {
                        $element.css('opacity', '1');
                    } else {
                        $element.replaceWith($originalContent);
                    }
                    
                    showNotice(response.data && response.data.message ? response.data.message : 'Failed to update image.', 'error');
                }
            },
            error: function() {
                // Restore original content
                if ($element.is('img')) {
                    $element.css('opacity', '1');
                } else {
                    $element.replaceWith($originalContent);
                }
                
                showNotice('An error occurred while updating the image.', 'error');
            }
        });
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        type = type || 'info';
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        
        var $notice = $('<div>', {
            class: 'notice ' + noticeClass + ' is-dismissible',
            style: 'margin: 5px 0 15px 0; padding: 8px 12px;',
            html: '<p>' + message + '</p>'
        });

        // Insert notice at top of page
        var $target = $('.wrap h1').first();
        if ($target.length) {
            $target.after($notice);
        } else {
            $('.wrap').first().prepend($notice);
        }

        // Auto-dismiss after 3 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Enhance featured image meta box on edit pages
     */
    function enhanceFeaturedImageMetaBox(postType) {
        // Only on edit pages (post.php, post-new.php)
        if (typeof pagenow === 'undefined' || (pagenow !== 'post' && pagenow !== 'post-new')) {
            return;
        }
        
        // For courses and course events, make the featured image meta box more interactive
        if (postType === 'course' || postType === 'course_event') {
            var $featuredImageBox = $('#postimagediv');
            if ($featuredImageBox.length) {
                var postId = $('#post_ID').val();
                
                // Ensure _thumbnail_id field exists and is properly set
                var $thumbnailInput = $('#_thumbnail_id');
                if (!$thumbnailInput.length) {
                    // Create the hidden input if it doesn't exist
                    $thumbnailInput = $('<input>', {
                        type: 'hidden',
                        id: '_thumbnail_id',
                        name: '_thumbnail_id',
                        value: ''
                    });
                    $featuredImageBox.append($thumbnailInput);
                }
                
                // Add a "Change Image" button if image exists
                var $currentImage = $featuredImageBox.find('img');
                if ($currentImage.length) {
                    var imageId = $thumbnailInput.val() || $featuredImageBox.find('input[name="_thumbnail_id"]').val();
                    
                    // Make the image clickable
                    $currentImage.css('cursor', 'pointer').attr('title', 'Click to change image');
                    $currentImage.on('click', function() {
                        openMediaLibrary(postId, postType, imageId, $(this));
                    });
                    
                    // Also add a "Change" link next to "Remove featured image"
                    var $removeLink = $featuredImageBox.find('a#remove-post-thumbnail');
                    if ($removeLink.length) {
                        var $changeLink = $('<a href="#" class="change-featured-image" style="margin-left: 10px;">Change Image</a>');
                        $changeLink.on('click', function(e) {
                            e.preventDefault();
                            openMediaLibrary(postId, postType, imageId, $currentImage);
                        });
                        $removeLink.after($changeLink);
                    }
                }
                
                // Ensure the thumbnail ID is preserved when form is submitted
                // WordPress handles this automatically, but we'll ensure it's in the form
                var $form = $('#post');
                if ($form.length) {
                    $form.on('submit', function() {
                        // Ensure _thumbnail_id is in the form before submission
                        var $thumbInput = $('#_thumbnail_id');
                        if ($thumbInput.length && !$thumbInput.closest('form').length) {
                            // If somehow not in form, add it
                            $form.append($thumbInput.clone());
                        }
                    });
                }
            }
        }
        
        // For course events, enhance the ACF event_image field
        if (postType === 'course_event') {
            // Wait for ACF to load
            if (typeof acf !== 'undefined') {
                acf.addAction('ready_field/type=image', function(field) {
                    if (field.get('name') === 'event_image') {
                        var postId = $('#post_ID').val();
                        var $field = field.$el;
                        var $imagePreview = $field.find('.acf-image-uploader img, .acf-image-uploader .acf-image-wrap img');
                        
                        // Make existing image preview clickable
                        if ($imagePreview.length) {
                            $imagePreview.css('cursor', 'pointer').attr('title', 'Click to change image');
                            $imagePreview.on('click', function() {
                                var currentImageId = field.val();
                                openMediaLibrary(postId, postType, currentImageId, $imagePreview);
                            });
                        }
                        
                        // Add a quick change button after the field buttons
                        setTimeout(function() {
                            var $button = $('<button type="button" class="button button-small cta-quick-change-image" style="margin-top: 5px;">Quick Change Image</button>');
                            $button.on('click', function(e) {
                                e.preventDefault();
                                var currentImageId = field.val();
                                openMediaLibrary(postId, postType, currentImageId, $field);
                            });
                            
                            // Insert after ACF buttons or image preview
                            var $target = $field.find('.acf-button-group, .acf-image-uploader').first();
                            if ($target.length) {
                                $target.after($button);
                            } else {
                                $field.append($button);
                            }
                        }, 500);
                    }
                });
            }
        }
    }

    /**
     * Get URL parameter
     */
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

})(jQuery);

