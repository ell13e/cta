# Eventbrite Embedded Checkout Implementation Plan

## Overview

This document outlines the implementation of Eventbrite's embedded checkout system to enable direct booking and payment on course event pages, while preventing cross-promotion of competitor events.

## Problem Statement

- Users currently see an enquiry form when clicking "Book Now"
- Redirecting to Eventbrite exposes users to competitor events and cheaper alternatives
- Need seamless booking experience that keeps users on our site
- Must maintain payment security and ticket management capabilities

## Solution: Embedded Checkout

Eventbrite's embedded checkout allows us to:
- Keep users on our WordPress site throughout the booking process
- Process payments securely through Eventbrite's infrastructure
- Prevent exposure to other events on Eventbrite's platform
- Maintain all ticket management and capacity tracking features

---

## Phase 1: Core Implementation (Current)

### 1.1 Privacy Settings Configuration

**File:** `inc/eventbrite-integration.php`

**Changes:**
- Set `listed: false` - Events not publicly searchable on Eventbrite
- Set `shareable: false` - Disable social sharing buttons
- Prevents events from appearing in Eventbrite's discovery features

**Location:** `cta_prepare_eventbrite_event_data()` function, around line 636

```php
$event_payload = [
    'event' => [
        // ... existing fields ...
        'listed' => false,      // Not publicly searchable
        'shareable' => false,   // No social sharing
    ]
];
```

**Impact:**
- New events created will be unlisted by default
- Existing events need manual update via Eventbrite API or admin interface
- Reduces but doesn't eliminate cross-promotion (Eventbrite may still show some recommendations)

### 1.2 Single Course Event Page Integration

**File:** `single-course_event.php`

**Changes:**
1. Check for Eventbrite ID on page load
2. Conditionally render Book Now button:
   - If Eventbrite ID exists → Embedded checkout button
   - If no Eventbrite ID → Fallback to enquiry form

**Implementation:**
```php
<?php
$eventbrite_id = get_post_meta(get_the_ID(), 'eventbrite_id', true);
$has_eventbrite = !empty($eventbrite_id);
?>

<?php if ($has_eventbrite) : ?>
  <button onclick="openEventbriteCheckout('<?php echo esc_js($eventbrite_id); ?>')">
    Book Now
  </button>
<?php else : ?>
  <button onclick="openBookingModal(...)">
    Book Now
  </button>
<?php endif; ?>
```

### 1.3 Embedded Checkout Modal

**Components:**
- Modal HTML structure (accessible, keyboard navigable)
- JavaScript functions for open/close
- iframe integration with Eventbrite checkout URL
- Body scroll lock/unlock for modal experience

**Modal Features:**
- Full-screen overlay with backdrop
- Responsive design (mobile-friendly)
- Close button (X) and Escape key support
- Focus management for accessibility
- iframe loading with Eventbrite checkout URL

**URL Format:**
```
https://www.eventbrite.co.uk/checkout-external?eid={EVENTBRITE_ID}
```

### 1.4 Styling

**File:** `assets/css/main.css` (or separate file)

**Requirements:**
- Modal overlay with backdrop
- Responsive container (max-width: 900px)
- Mobile full-screen on small devices
- Accessible focus states
- Smooth transitions

---

## Phase 2: Expansion to Other Pages

### 2.1 Course Archive Pages

**Files:**
- `archive-course_event.php`
- `archive-course.php`

**Implementation:**
- Add Eventbrite ID check to course card rendering
- Update "Book Now" links to use embedded checkout
- Maintain fallback to enquiry form for events without Eventbrite

**Considerations:**
- Performance: Multiple Eventbrite API calls may be needed
- Caching: Cache Eventbrite IDs to reduce queries
- User experience: Consistent booking flow across site

### 2.2 Homepage Course Cards

**Files:**
- `front-page.php`
- `assets/js/homepage-upcoming-courses.js`

**Implementation:**
- Pass Eventbrite ID as data attribute on course cards
- Update JavaScript to check for Eventbrite ID before opening modal
- Add embedded checkout option to course modal

**Example:**
```javascript
const eventbriteId = courseCard.getAttribute('data-eventbrite-id');
if (eventbriteId) {
  openEventbriteCheckout(eventbriteId);
} else {
  openBookingModal(...);
}
```

### 2.3 Course Detail Pages (single-course.php)

**File:** `single-course.php`

**Implementation:**
- Check Eventbrite ID for each upcoming event date
- Update date selection buttons to use embedded checkout
- Maintain enquiry form for courses without scheduled events

**Complexity:**
- Multiple events per course page
- Need to pass correct Eventbrite ID for selected date
- Handle events with/without Eventbrite integration

---

## Phase 3: Enhanced Features

### 3.1 Discount Code Integration

**Current State:**
- Discount codes exist in WordPress
- Eventbrite discount codes can be synced

**Enhancement:**
- Pre-populate discount code in embedded checkout
- Pass discount code as URL parameter to Eventbrite
- Validate discount codes before opening checkout

**Eventbrite URL with Discount:**
```
https://www.eventbrite.co.uk/checkout-external?eid={EVENTBRITE_ID}&discount={CODE}
```

**Implementation:**
```php
$discount_code = cta_get_active_discount_code($event_id);
$checkout_url = 'https://www.eventbrite.co.uk/checkout-external?eid=' . $eventbrite_id;
if ($discount_code) {
    $checkout_url .= '&discount=' . urlencode($discount_code);
}
```

### 3.2 Quantity Selection

**Enhancement:**
- Allow users to select number of tickets before opening checkout
- Pass quantity to Eventbrite checkout URL
- Pre-fill ticket quantity in embedded checkout

**URL Parameter:**
```
&qty={QUANTITY}
```

### 3.3 Analytics & Tracking

**Implementation:**
- Track embedded checkout opens
- Monitor conversion rates (enquiry form vs. direct booking)
- Track which events have Eventbrite integration
- Measure booking completion rates

**Tracking Points:**
- `openEventbriteCheckout()` function call
- iframe load success/failure
- Modal close events
- Comparison with enquiry form submissions

**Tools:**
- Google Analytics events
- Facebook Pixel events
- Custom WordPress tracking

### 3.4 Error Handling

**Scenarios:**
- Eventbrite event not found
- Event sold out
- Payment processing errors
- Network failures

**Implementation:**
- iframe load error detection
- Fallback to enquiry form on error
- User-friendly error messages
- Logging for debugging

**Example:**
```javascript
iframe.addEventListener('error', function() {
  console.error('Eventbrite checkout failed to load');
  closeEventbriteCheckout();
  // Fallback to enquiry form
  openBookingModal(...);
});
```

### 3.5 Post-Booking Actions

**Enhancement:**
- Detect successful booking completion
- Show thank you message
- Redirect to confirmation page
- Trigger email notifications
- Update WordPress post meta

**Eventbrite Webhooks:**
- Order placed
- Order updated
- Order refunded

**Implementation:**
- Set up Eventbrite webhook endpoint
- Process webhook events
- Update local database
- Send confirmation emails

---

## Phase 4: Advanced Features

### 4.1 Multi-Event Booking

**Use Case:**
- User wants to book multiple course dates
- Add to cart functionality
- Bulk discount application

**Considerations:**
- Eventbrite doesn't natively support multi-event cart
- May need custom solution or redirect to Eventbrite cart
- Alternative: Sequential booking flow

### 4.2 Group Booking Workflow

**Enhancement:**
- Pre-fill number of delegates
- Group discount codes
- Bulk booking confirmation

**Implementation:**
- Pass delegate count to Eventbrite
- Apply group discount automatically
- Custom confirmation for group bookings

### 4.3 Custom Checkout Fields

**Eventbrite Limitations:**
- Limited customization of checkout form
- Can add custom questions via Eventbrite settings

**Workaround:**
- Collect additional info before checkout
- Store in WordPress
- Pass to Eventbrite via order notes or custom fields

### 4.4 Payment Method Preferences

**Enhancement:**
- Allow users to choose payment method
- Invoice option for B2B bookings
- Payment plan options

**Considerations:**
- Eventbrite supports multiple payment methods
- May need Eventbrite Plus/Pro features
- Custom payment gateway integration if needed

### 4.5 Booking Confirmation Customization

**Enhancement:**
- Custom confirmation page on WordPress
- Branded confirmation emails
- Additional resources/links post-booking

**Implementation:**
- Detect booking completion via webhook
- Redirect to custom thank you page
- Include course materials, venue info, etc.

---

## Phase 5: Admin & Management

### 5.1 Eventbrite Sync Status Dashboard

**Features:**
- Show which events have Eventbrite integration
- Display sync status (synced, pending, error)
- Quick actions (sync, unlink, view on Eventbrite)

**Location:**
- New admin page or existing event management UI
- Add columns to event list table

### 5.2 Bulk Operations

**Features:**
- Sync multiple events to Eventbrite
- Update privacy settings in bulk
- Unlink events from Eventbrite

**Implementation:**
- Admin interface with checkboxes
- AJAX bulk operations
- Progress indicators for large batches

### 5.3 Booking Analytics Dashboard

**Metrics:**
- Bookings via embedded checkout vs. enquiry form
- Conversion rates by event
- Revenue tracking
- Popular booking times

**Tools:**
- Custom WordPress dashboard widget
- Integration with existing analytics
- Export capabilities

### 5.4 Automated Event Creation

**Enhancement:**
- Auto-create Eventbrite events when WordPress events are published
- Auto-update Eventbrite when WordPress events are modified
- Auto-unpublish Eventbrite when WordPress events are cancelled

**Current State:**
- Partial implementation exists
- Needs refinement and error handling

**Improvements:**
- Better error messages
- Retry logic for failed syncs
- Notification system for admins

---

## Technical Considerations

### Performance

**Optimizations:**
- Cache Eventbrite IDs in post meta (already implemented)
- Lazy load checkout modal (only load when needed)
- Minimize API calls to Eventbrite
- Use WordPress transients for rate-limited data

**Caching Strategy:**
```php
$eventbrite_id = get_transient('eventbrite_id_' . $post_id);
if (false === $eventbrite_id) {
    $eventbrite_id = get_post_meta($post_id, 'eventbrite_id', true);
    set_transient('eventbrite_id_' . $post_id, $eventbrite_id, HOUR_IN_SECONDS);
}
```

### Security

**Requirements:**
- Sanitize all user inputs
- Validate Eventbrite IDs before use
- Use nonces for admin actions
- Secure iframe communication (CSP headers)

**Best Practices:**
- Escape output in templates
- Validate Eventbrite API responses
- Rate limit API requests
- Log security events

### Accessibility

**Requirements:**
- Keyboard navigation support
- Screen reader compatibility
- Focus management
- ARIA labels and roles

**Implementation:**
- Modal with proper ARIA attributes
- Focus trap within modal
- Escape key to close
- Focus return to trigger element

### Browser Compatibility

**Testing Required:**
- Chrome/Edge (Chromium)
- Firefox
- Safari (iOS and macOS)
- Mobile browsers

**Known Issues:**
- iframe sandbox attributes may be needed
- Some browsers block third-party cookies
- Mobile viewport handling

### Error Handling

**Scenarios:**
1. Eventbrite event deleted
2. Event sold out
3. Network timeout
4. Invalid Eventbrite ID
5. Payment processing failure

**Strategy:**
- Graceful degradation to enquiry form
- User-friendly error messages
- Admin notifications for critical errors
- Retry mechanisms where appropriate

---

## Migration Strategy

### Existing Events

**Options:**
1. **Manual Update:** Update each event's privacy settings in Eventbrite dashboard
2. **Bulk API Update:** Script to update all existing events
3. **Gradual Rollout:** Update as events are edited

**Recommended:**
- Create admin tool to bulk update existing events
- Set `listed: false` and `shareable: false` for all events
- Test on staging first

### New Events

**Process:**
- All new events automatically get privacy settings
- Embedded checkout available immediately if Eventbrite ID exists
- Fallback to enquiry form if no Eventbrite integration

---

## Testing Checklist

### Functional Testing
- [ ] Book Now button appears when Eventbrite ID exists
- [ ] Enquiry form appears when no Eventbrite ID
- [ ] Modal opens and closes correctly
- [ ] iframe loads Eventbrite checkout
- [ ] Payment processing works
- [ ] Booking confirmation received
- [ ] Discount codes apply correctly
- [ ] Mobile experience works

### Cross-Browser Testing
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari (macOS)
- [ ] Safari (iOS)
- [ ] Mobile Chrome
- [ ] Mobile Firefox

### Accessibility Testing
- [ ] Keyboard navigation
- [ ] Screen reader compatibility
- [ ] Focus management
- [ ] ARIA attributes
- [ ] Color contrast

### Performance Testing
- [ ] Page load time
- [ ] Modal open/close speed
- [ ] iframe load time
- [ ] Mobile performance

### Security Testing
- [ ] Input sanitization
- [ ] XSS prevention
- [ ] CSRF protection
- [ ] iframe security headers

---

## Future Considerations

### Alternative Payment Providers

**Options:**
- Stripe Checkout (embedded)
- PayPal (embedded)
- WooCommerce integration
- Custom payment gateway

**When to Consider:**
- Eventbrite fees become prohibitive
- Need more customization
- Require specific payment methods
- Want full control over checkout

### Hybrid Approach

**Strategy:**
- Use Eventbrite for public events
- Use custom checkout for private/group bookings
- Use enquiry form for custom pricing

**Benefits:**
- Flexibility for different booking types
- Reduced fees for certain bookings
- Better control where needed

### API Integration Depth

**Current:** Basic sync (create, update, read)
**Future:** Full integration
- Real-time capacity updates
- Automatic refunds
- Attendee management
- Check-in integration
- Reporting and analytics

---

## Maintenance & Monitoring

### Regular Tasks
- Monitor Eventbrite API rate limits
- Check for failed syncs
- Review booking conversion rates
- Update privacy settings as needed
- Test checkout flow monthly

### Monitoring Points
- Eventbrite API response times
- Checkout modal load times
- Error rates
- Booking completion rates
- User feedback

### Documentation Updates
- Keep this document updated as features are added
- Document any customizations
- Note any Eventbrite API changes
- Track browser compatibility issues

---

## Resources

### Eventbrite Documentation
- [Eventbrite API v3 Documentation](https://www.eventbrite.com/platform/api/)
- [Embedded Checkout Guide](https://www.eventbrite.com/support/articles/en_US/How_To/how-to-add-eventbrite-s-embedded-checkout-to-your-wordpress-org-site)
- [Event Privacy Settings](https://www.eventbrite.com/support/articles/en_US/How_To/how-to-make-an-event-private-or-unlisted)

### WordPress Integration
- Current integration: `inc/eventbrite-integration.php`
- Event management: `inc/event-management-ui.php`
- Admin settings: `inc/api-keys-settings.php`

### Related Documentation
- `docs/EVENTBRITE_AI_CONTEXT_ASSESSMENT.md`
- `docs/eventbrite-api-complete.md`
- `eventbriteapiv3public.apib`

---

## Changelog

### 2024-12-XX - Initial Implementation
- Added privacy settings to event creation
- Implemented embedded checkout modal
- Updated single course event page
- Added CSS styling for modal

### Future Updates
- Track changes and improvements here

---

## Questions & Decisions Needed

1. **Existing Events:** How should we handle events already on Eventbrite?
   - [ ] Bulk update all existing events
   - [ ] Update on next edit
   - [ ] Manual process

2. **Fallback Behavior:** What happens if Eventbrite checkout fails?
   - [ ] Always fallback to enquiry form
   - [ ] Show error message
   - [ ] Redirect to Eventbrite page

3. **Analytics:** What metrics are most important?
   - [ ] Conversion rates
   - [ ] Revenue tracking
   - [ ] User behavior

4. **Mobile Experience:** Should checkout be full-screen on mobile?
   - [ ] Yes, full-screen modal
   - ] No, responsive modal
   - [ ] Redirect to Eventbrite app if available

---

## Notes

- Eventbrite's embedded checkout is the recommended solution for keeping users on-site
- Privacy settings (`listed: false`) reduce but don't completely eliminate cross-promotion
- Some Eventbrite recommendations may still appear, but significantly reduced
- Consider Eventbrite Plus/Pro for additional features if needed
- Monitor Eventbrite API changes and updates
