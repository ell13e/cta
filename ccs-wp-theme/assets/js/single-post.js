/**
 * Single Post Page Functionality
 * Handles table of contents and article interactions
 */

(function() {
  'use strict';

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    initTableOfContents();
  }

  /**
   * Initialize sticky table of contents
   * Works with both ACF sections and standard WordPress content
   */
  function initTableOfContents() {
    const articleBody = document.getElementById('article-body');
    if (!articleBody) return;
    
    // Find headings - check for article-section-title first (ACF sections), then h2 in entry-content
    let headings = articleBody.querySelectorAll('.article-section-title');
    
    // Fallback to standard h2 headings if no ACF sections
    if (headings.length === 0) {
      headings = articleBody.querySelectorAll('.entry-content h2');
    }
    
    if (headings.length < 2) {
      // Hide TOC if less than 2 headings
      const tocNav = document.querySelector('.single-post-toc');
      if (tocNav) {
        tocNav.style.display = 'none';
      }
      return;
    }
    
    // Find existing TOC list in sidebar
    const tocList = document.getElementById('article-toc');
    if (!tocList) return;
    
    // Clear any existing content
    tocList.innerHTML = '';
    
    headings.forEach((heading, index) => {
      // Generate ID for heading if it doesn't have one
      let headingId = heading.id;
      if (!headingId) {
        // Check if parent section has ID (for ACF sections)
        const section = heading.closest('.article-section');
        if (section && section.id) {
          headingId = section.id;
        } else {
          // Create slug from heading text
          headingId = heading.textContent
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
          // Ensure unique ID
          if (document.getElementById(headingId)) {
            headingId = `${headingId}-${index}`;
          }
          heading.id = headingId;
        }
      }
      
      const tocItem = document.createElement('li');
      const tocLink = document.createElement('a');
      tocLink.href = `#${headingId}`;
      tocLink.textContent = heading.textContent.trim();
      tocLink.classList.add('single-post-toc-link');
      
      // Smooth scroll
      tocLink.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.getElementById(headingId) || heading;
        if (target) {
          const offset = 120; // Account for sticky header
          const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });
          
          // Update active state
          tocList.querySelectorAll('a').forEach(link => {
            link.classList.remove('active');
          });
          tocLink.classList.add('active');
          
          // Update URL hash without scrolling again
          if (history.pushState) {
            history.pushState(null, null, `#${headingId}`);
          }
        }
      });
      
      tocItem.appendChild(tocLink);
      tocList.appendChild(tocItem);
    });
    
    // Update active link on scroll with throttling
    let ticking = false;
    const updateActiveTOCLink = () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          const scrollPosition = window.scrollY + 200;
          
          let activeHeading = null;
          headings.forEach((heading) => {
            const headingTop = heading.getBoundingClientRect().top + window.pageYOffset;
            const headingBottom = headingTop + heading.offsetHeight;
            
            if (scrollPosition >= headingTop - 50 && scrollPosition < headingBottom) {
              activeHeading = heading;
            }
          });
          
          // If we're past the last heading, keep it active
          if (!activeHeading && headings.length > 0) {
            const lastHeading = headings[headings.length - 1];
            const lastHeadingBottom = lastHeading.getBoundingClientRect().top + window.pageYOffset + lastHeading.offsetHeight;
            if (scrollPosition >= lastHeadingBottom - 100) {
              activeHeading = lastHeading;
            }
          }
          
          // Update active state
          tocList.querySelectorAll('a').forEach(link => {
            link.classList.remove('active');
          });
          
          if (activeHeading) {
            const activeId = activeHeading.id || activeHeading.closest('.article-section')?.id;
            if (activeId) {
              const activeLink = tocList.querySelector(`a[href="#${activeId}"]`);
              if (activeLink) {
                activeLink.classList.add('active');
              }
            }
          }
          
          ticking = false;
        });
        ticking = true;
      }
    };
    
    window.addEventListener('scroll', updateActiveTOCLink, { passive: true });
    updateActiveTOCLink(); // Initial check
    
    // Handle hash on page load
    if (window.location.hash) {
      setTimeout(() => {
        const target = document.querySelector(window.location.hash);
        if (target) {
          const offset = 120;
          const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });
        }
      }, 100);
    }
  }
})();

