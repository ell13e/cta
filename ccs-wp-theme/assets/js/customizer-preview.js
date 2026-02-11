/**
 * Customizer Live Preview
 * 
 * Handles real-time updates in the WordPress Customizer preview.
 */
(function($) {
    'use strict';
    
    // Phone number live preview
    wp.customize('cta_contact_phone', function(value) {
        value.bind(function(newval) {
            // Update any phone displays on the page
            $('.phone-number, .contact-phone').text(newval);
        });
    });
    
    // Email live preview
    wp.customize('cta_contact_email', function(value) {
        value.bind(function(newval) {
            $('.contact-email').text(newval);
        });
    });
    
})(jQuery);

