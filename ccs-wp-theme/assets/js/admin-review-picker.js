/**
 * Visual Review Picker for Course Testimonials
 * 
 * Creates a visual carousel interface for selecting reviews
 * that matches the frontend testimonial-card styling
 */

(function($) {
    'use strict';

    function initReviewPicker() {
        const $wrapper = $('#cta-review-picker-wrapper');
        if (!$wrapper.length) {
            return;
        }

        const $field = $wrapper.find('select[name*="course_selected_reviews"]');
        if (!$field.length) {
            return;
        }

        // Get all reviews from localized data or fetch via AJAX
        const reviews = window.ccsAllReviews || {};
        if (Object.keys(reviews).length === 0) {
            return;
        }

        // Get current course title for keyword matching
        const courseTitle = $('#title').val() || '';
        const courseTitleLower = courseTitle.toLowerCase();

        // Separate related and all reviews
        const relatedReviews = [];
        const allReviews = [];

        Object.keys(reviews).forEach(function(reviewId) {
            const review = reviews[reviewId];
            const isRelated = review.keywords && review.keywords.some(function(keyword) {
                return courseTitleLower.indexOf(keyword.toLowerCase()) !== -1;
            }) || review.course_matches && review.course_matches.length > 0;

            if (isRelated) {
                relatedReviews.push({ id: reviewId, ...review });
            } else {
                allReviews.push({ id: reviewId, ...review });
            }
        });

        // Get currently selected reviews
        const selectedIds = $field.val() || [];

        // Create visual picker HTML
        let html = '<div class="cta-review-picker-container">';
        
        // Selection counter
        html += '<div class="cta-review-picker-counter">';
        html += '<span class="cta-selected-count">' + selectedIds.length + '</span> of 3 selected';
        html += '</div>';

        // Related Reviews section
        if (relatedReviews.length > 0) {
            html += '<div class="cta-review-picker-section cta-review-picker-related">';
            html += '<h4>Related Reviews</h4>';
            html += '<div class="cta-review-picker-carousel">';
            relatedReviews.forEach(function(review) {
                html += createReviewCard(review, selectedIds.indexOf(review.id) !== -1);
            });
            html += '</div>';
            html += '</div>';
        }

        // All Reviews section
        html += '<div class="cta-review-picker-section cta-review-picker-all">';
        html += '<h4>All Reviews</h4>';
        html += '<div class="cta-review-picker-carousel">';
        allReviews.forEach(function(review) {
            html += createReviewCard(review, selectedIds.indexOf(review.id) !== -1);
        });
        html += '</div>';
        html += '</div>';

        html += '</div>';

        // Hide the original select field
        $field.closest('.acf-field').hide();

        // Insert visual picker after the field wrapper
        $wrapper.after(html);

        // Handle checkbox clicks
        $('.cta-review-card input[type="checkbox"]').on('change', function() {
            const checkbox = $(this);
            const reviewId = checkbox.data('review-id');
            const isChecked = checkbox.is(':checked');
            const $card = checkbox.closest('.cta-review-card');

            // Get current selections
            let currentSelections = $field.val() || [];
            if (!Array.isArray(currentSelections)) {
                currentSelections = [currentSelections];
            }

            if (isChecked) {
                // Check if we've reached the limit
                if (currentSelections.length >= 3) {
                    checkbox.prop('checked', false);
                    alert('You can only select up to 3 reviews.');
                    return;
                }
                // Add to selections
                if (currentSelections.indexOf(reviewId) === -1) {
                    currentSelections.push(reviewId);
                }
                $card.addClass('selected');
            } else {
                // Remove from selections
                currentSelections = currentSelections.filter(function(id) {
                    return id !== reviewId;
                });
                $card.removeClass('selected');
            }

            // Update the hidden select field
            $field.val(currentSelections).trigger('change');

            // Update counter
            $('.cta-selected-count').text(currentSelections.length);
        });
    }

    function createReviewCard(review, isSelected) {
        let html = '<div class="cta-review-card' + (isSelected ? ' selected' : '') + '">';
        html += '<label class="cta-review-card-label">';
        html += '<input type="checkbox" data-review-id="' + review.id + '"' + (isSelected ? ' checked' : '') + '>';
        html += '<div class="cta-review-card-content">';
        html += '<div class="cta-review-quote-wrapper">';
        html += '<p class="cta-review-quote">' + escapeHtml(review.quote) + '</p>';
        html += '</div>';
        html += '<div class="cta-review-author">';
        html += '<div class="cta-review-avatar">';
        html += '<i class="fas fa-user" aria-hidden="true"></i>';
        html += '</div>';
        html += '<div class="cta-review-info">';
        html += '<div class="cta-review-name">' + escapeHtml(review.author) + '</div>';
        html += '<div class="cta-review-company">' + escapeHtml(review.date || '') + '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        html += '</label>';
        html += '</div>';
        return html;
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
    }

    // Initialize on page load
    $(document).ready(function() {
        // Wait for ACF to initialize
        if (typeof acf !== 'undefined') {
            acf.add_action('ready', initReviewPicker);
        } else {
            initReviewPicker();
        }
    });

    // Re-initialize when ACF fields are added/updated
    $(document).on('acf/sync', initReviewPicker);

})(jQuery);
