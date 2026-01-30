/**
 * Unified Accordion System
 * 
 * A single, accessible accordion implementation that can be configured
 * via data attributes for different behaviors across the site.
 * 
 * Usage:
 * <div class="accordion" data-accordion-group="faq">
 *   <button class="accordion-trigger" aria-expanded="false" aria-controls="content-1">
 *     Question
 *   </button>
 *   <div class="accordion-content" id="content-1" aria-hidden="true">
 *     Answer
 *   </div>
 * </div>
 * 
 * Data attributes:
 * - data-accordion-group: Groups accordions together (only one open per group)
 * - data-accordion-allow-multiple: Allow multiple accordions open in same group
 * - data-accordion-animation: "height" (default) or "fade" or "none"
 */

(function() {
  'use strict';

  /**
   * Initialize all accordions on the page
   */
  function initAccordions() {
    const triggers = document.querySelectorAll('.accordion-trigger');
    
    if (triggers.length === 0) return;

    triggers.forEach(trigger => {
      // Prevent double-binding
      if (trigger.dataset.accordionInit === 'true') return;
      trigger.dataset.accordionInit = 'true';

      // Get accordion container and configuration
      const accordion = trigger.closest('.accordion');
      const group = accordion?.dataset.accordionGroup || 'default';
      const allowMultiple = accordion?.dataset.accordionAllowMultiple === 'true';
      const animation = accordion?.dataset.accordionAnimation || 'height';

      // Get content panel
      const contentId = trigger.getAttribute('aria-controls');
      const content = contentId ? document.getElementById(contentId) : null;
      
      if (!content) {
        console.warn('Accordion trigger missing aria-controls or content not found:', trigger);
        return;
      }

      // Click handler
      trigger.addEventListener('click', function(e) {
        e.preventDefault();
        toggleAccordion(trigger, content, group, allowMultiple, animation);
      });

      // Keyboard navigation
      trigger.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          trigger.click();
        }
      });
    });
  }

  /**
   * Toggle accordion open/closed
   */
  function toggleAccordion(trigger, content, group, allowMultiple, animation) {
    const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
    const willExpand = !isExpanded;

    // Close other accordions in the same group (unless multiple allowed)
    if (willExpand && !allowMultiple) {
      closeOtherAccordionsInGroup(group, trigger, animation);
    }

    // Toggle current accordion
    if (willExpand) {
      openAccordion(trigger, content, animation);
    } else {
      closeAccordion(trigger, content, animation);
    }
  }

  /**
   * Open an accordion
   */
  function openAccordion(trigger, content, animation) {
    trigger.setAttribute('aria-expanded', 'true');
    content.setAttribute('aria-hidden', 'false');

    if (animation === 'height') {
      // Calculate actual height for smooth transition
      const originalMaxHeight = content.style.maxHeight;
      const originalPadding = window.getComputedStyle(content).padding;
      
      // Temporarily set to auto to measure
      content.style.maxHeight = 'none';
      content.style.paddingTop = '';
      content.style.paddingBottom = '';
      const height = content.scrollHeight;
      
      // Reset to collapsed state
      content.style.maxHeight = '0';
      content.style.paddingTop = '0';
      content.style.paddingBottom = '0';
      
      // Force reflow
      void content.offsetHeight;
      
      // Animate to full height
      requestAnimationFrame(() => {
        content.style.maxHeight = height + 'px';
        content.style.paddingTop = '';
        content.style.paddingBottom = '';
        
        // Clean up after transition completes
        const transitionDuration = parseFloat(window.getComputedStyle(content).transitionDuration) * 1000 || 300;
        setTimeout(() => {
          if (content.getAttribute('aria-hidden') === 'false') {
            content.style.maxHeight = '';
          }
        }, transitionDuration);
      });
    } else if (animation === 'fade') {
      content.style.opacity = '0';
      content.style.display = 'block';
      void content.offsetHeight; // Force reflow
      content.style.opacity = '1';
    } else {
      // No animation
      content.style.display = 'block';
    }
  }

  /**
   * Close an accordion
   */
  function closeAccordion(trigger, content, animation) {
    trigger.setAttribute('aria-expanded', 'false');
    content.setAttribute('aria-hidden', 'true');

    if (animation === 'height') {
      // Get current height before collapsing
      const height = content.scrollHeight;
      content.style.maxHeight = height + 'px';
      
      // Force reflow
      void content.offsetHeight;
      
      // Collapse
      requestAnimationFrame(() => {
        content.style.maxHeight = '0';
        content.style.paddingTop = '0';
        content.style.paddingBottom = '0';
      });
    } else if (animation === 'fade') {
      content.style.opacity = '0';
      setTimeout(() => {
        if (content.getAttribute('aria-hidden') === 'true') {
          content.style.display = 'none';
        }
      }, 300);
    } else {
      // No animation
      content.style.display = 'none';
    }
  }

  /**
   * Close all other accordions in the same group
   */
  function closeOtherAccordionsInGroup(group, currentTrigger, animation) {
    const allTriggers = document.querySelectorAll(`.accordion[data-accordion-group="${group}"] .accordion-trigger`);
    
    allTriggers.forEach(trigger => {
      if (trigger === currentTrigger) return;
      
      const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
      if (!isExpanded) return;

      const contentId = trigger.getAttribute('aria-controls');
      const content = contentId ? document.getElementById(contentId) : null;
      
      if (content) {
        closeAccordion(trigger, content, animation);
      }
    });
  }

  // Initialize on page load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAccordions);
  } else {
    initAccordions();
  }

  // Re-initialize for dynamically added content
  if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver(function(mutations) {
      const hasNewAccordions = Array.from(mutations).some(mutation => {
        return Array.from(mutation.addedNodes).some(node => {
          return node.nodeType === 1 && (
            node.classList?.contains('accordion') ||
            node.querySelector?.('.accordion-trigger')
          );
        });
      });
      
      if (hasNewAccordions) {
        initAccordions();
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  }

  // Export for manual initialization if needed
  window.CTAAccordion = {
    init: initAccordions,
    open: openAccordion,
    close: closeAccordion
  };

})();
