# Unified Accordion System

## Overview

A single, accessible accordion implementation that replaces all duplicate accordion code across the site. This provides:

- **Consistency**: Same behavior and accessibility across all accordions
- **Maintainability**: One place to fix bugs and add features
- **Flexibility**: Configurable via data attributes for different use cases
- **Accessibility**: Built-in ARIA attributes and keyboard navigation

## Files

- `assets/js/accordion.js` - Unified JavaScript implementation
- `assets/css/accordion.css` - Base styles with variant modifiers

## Basic Usage

```html
<div class="accordion" data-accordion-group="faq">
  <button 
    class="accordion-trigger" 
    aria-expanded="false" 
    aria-controls="content-1"
  >
    Question
    <svg class="accordion-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="6 9 12 15 18 9"></polyline>
    </svg>
  </button>
  <div 
    class="accordion-content" 
    id="content-1" 
    aria-hidden="true"
  >
    Answer content goes here
  </div>
</div>
```

## Configuration Options

### Data Attributes

**On the `.accordion` container:**

- `data-accordion-group="name"` - Groups accordions together (only one open per group by default)
- `data-accordion-allow-multiple="true"` - Allow multiple accordions open in same group
- `data-accordion-animation="height|fade|none"` - Animation type (default: "height")

**Required on trigger:**
- `aria-expanded="false"` (or "true" if open by default)
- `aria-controls="content-id"` - Must match the content panel's `id`

**Required on content:**
- `id` - Must match trigger's `aria-controls`
- `aria-hidden="true"` (or "false" if open by default)

## Variant Styles

Add modifier classes to `.accordion` for different visual styles:

### Card Style
```html
<div class="accordion accordion--card" data-accordion-group="course-details">
```
Used for: Course details, event details

### FAQ Style
```html
<div class="accordion accordion--faq" data-accordion-group="faq">
```
Used for: FAQs, group training FAQs

### Mobile Navigation Style
```html
<div class="accordion accordion--mobile" data-accordion-group="mobile-nav">
```
Used for: Mobile menu accordions

### CQC Standards Style
```html
<div class="accordion accordion--cqc" data-accordion-group="cqc">
```
Used for: CQC requirements section

## Migration Examples

### Example 1: Course Detail Accordions

**Before:**
```html
<div class="course-detail-accordion">
  <button class="course-detail-accordion-trigger" aria-expanded="false" aria-controls="learn-content">
    <h3 class="course-detail-accordion-title">What You'll Learn</h3>
    <svg class="course-detail-accordion-icon">...</svg>
  </button>
  <div class="course-detail-accordion-content" id="learn-content">...</div>
</div>
```

**After:**
```html
<div class="accordion accordion--card" data-accordion-group="course-details">
  <button class="accordion-trigger" aria-expanded="false" aria-controls="learn-content">
    <h3 class="accordion-title">What You'll Learn</h3>
    <svg class="accordion-icon">...</svg>
  </button>
  <div class="accordion-content" id="learn-content" aria-hidden="true">...</div>
</div>
```

### Example 2: FAQ Accordions

**Before:**
```html
<div class="group-faq-item">
  <button class="group-faq-question" aria-expanded="false" aria-controls="faq-1">
    <span>Question</span>
    <span class="group-faq-icon"></span>
  </button>
  <div class="group-faq-answer" id="faq-1" aria-hidden="true">Answer</div>
</div>
```

**After:**
```html
<div class="accordion accordion--faq" data-accordion-group="faq">
  <button class="accordion-trigger" aria-expanded="false" aria-controls="faq-1">
    <span>Question</span>
    <svg class="accordion-icon">...</svg>
  </button>
  <div class="accordion-content" id="faq-1" aria-hidden="true">Answer</div>
</div>
```

### Example 3: Mobile Navigation

**Before:**
```html
<div class="mobile-accordion">
  <button class="mobile-accordion-trigger" aria-expanded="false">
    Courses
    <svg class="chevron-icon">...</svg>
  </button>
  <div class="mobile-accordion-content">...</div>
</div>
```

**After:**
```html
<div class="accordion accordion--mobile" data-accordion-group="mobile-nav">
  <button class="accordion-trigger" aria-expanded="false" aria-controls="mobile-courses">
    Courses
    <svg class="accordion-icon">...</svg>
  </button>
  <div class="accordion-content" id="mobile-courses" aria-hidden="true">...</div>
</div>
```

## JavaScript API

The accordion system is automatically initialized, but you can also use it programmatically:

```javascript
// Re-initialize (useful for dynamically added content)
CCSAccordion.init();

// Manually open an accordion
const trigger = document.querySelector('.accordion-trigger');
const content = document.getElementById('content-1');
CCSAccordion.open(trigger, content, 'height');

// Manually close an accordion
CCSAccordion.close(trigger, content, 'height');
```

## Benefits

1. **Single Source of Truth**: All accordion logic in one place
2. **Consistent Accessibility**: Same ARIA patterns everywhere
3. **Easier Maintenance**: Fix bugs once, works everywhere
4. **Flexible Configuration**: Data attributes for different behaviors
5. **Performance**: One script instead of multiple duplicate implementations
6. **Future-Proof**: Easy to add new features (e.g., animations, analytics)

## Migration Checklist

- [ ] Enqueue `accordion.js` and `accordion.css` in `functions.php`
- [ ] Replace `.course-detail-accordion` with `.accordion.accordion--card`
- [ ] Replace `.group-faq-question` with `.accordion-trigger` in `.accordion.accordion--faq`
- [ ] Replace `.mobile-accordion-trigger` with `.accordion-trigger` in `.accordion.accordion--mobile`
- [ ] Replace `.cqc-standard-header` with `.accordion-trigger` in `.accordion.accordion--cqc`
- [ ] Remove duplicate accordion JavaScript from:
  - [ ] `main.js` (initAccordions, initCourseDetailAccordions)
  - [ ] `group-booking.js` (initFAQAccordions)
  - [ ] `course-detail.js` (initAccordions)
  - [ ] `event-detail.js` (initAccordions)
  - [ ] `cqc-requirements-section.php` (inline script)
  - [ ] `page-training-guides.php` (inline script)
- [ ] Update CSS to use new class names or remove duplicate styles
- [ ] Test all accordion functionality across the site

## Notes

- The system automatically handles keyboard navigation (Enter/Space)
- Supports reduced motion preferences
- Works with dynamically added content (MutationObserver)
- All accordions are accessible by default (ARIA attributes required)
