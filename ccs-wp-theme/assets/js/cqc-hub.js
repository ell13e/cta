/**
 * CQC Hub JavaScript
 * Handles training selector and subscription bar functionality
 */

(function() {
  'use strict';
  
  // ============================================
  // Training Selector
  // ============================================
  
  const settingButtons = document.querySelectorAll('.cqc-setting-btn');
  const resultsContainer = document.getElementById('training-results');
  const resultsList = document.getElementById('results-list');
  const resultsTitle = document.getElementById('results-title');
  const resultsFooter = document.getElementById('results-footer');
  const totalCountSpan = document.getElementById('total-count');
  const collapseBtn = document.querySelector('.cqc-results-collapse');
  const dataSource = document.getElementById('training-data-source');
  
  const MAX_DISPLAY = 10;
  
  // Extract course data from hidden data source
  function getCoursesForSetting(setting) {
    const settingData = dataSource?.querySelector(`[data-setting="${setting}"]`);
    if (!settingData) return [];
    
    const listItems = settingData.querySelectorAll('li');
    const courses = [];
    
    listItems.forEach(li => {
      const text = li.textContent.trim();
      // Skip header text like "All residential care requirements plus:"
      if (!text.includes(':') && text.length > 0) {
        const link = li.querySelector('a');
        if (link) {
          courses.push({
            name: text,
            url: link.href
          });
        } else {
          courses.push({
            name: text,
            url: null
          });
        }
      }
    });
    
    return courses;
  }
  
  // Handle setting button clicks
  if (settingButtons.length > 0 && resultsContainer) {
    settingButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const setting = this.dataset.setting;
        const courses = getCoursesForSetting(setting);
        const totalCount = courses.length;
        const settingTitle = dataSource?.querySelector(`[data-setting="${setting}"]`)?.dataset.title || this.textContent.trim();
        
        // Update title
        resultsTitle.textContent = `Required Training: ${settingTitle}`;
        
        // Clear previous results
        resultsList.innerHTML = '';
        
        // Display courses (max 10)
        const displayCourses = courses.slice(0, MAX_DISPLAY);
        displayCourses.forEach(course => {
          const li = document.createElement('li');
          if (course.url) {
            li.innerHTML = `<a href="${course.url}">${course.name}</a>`;
          } else {
            li.textContent = course.name;
          }
          resultsList.appendChild(li);
        });
        
        // Show footer if more than MAX_DISPLAY
        if (totalCount > MAX_DISPLAY) {
          totalCountSpan.textContent = totalCount;
          resultsFooter.style.display = 'block';
        } else {
          resultsFooter.style.display = 'none';
        }
        
        // Update button states
        settingButtons.forEach(b => b.classList.remove('is-active'));
        this.classList.add('is-active');
        
        // Show results
        resultsContainer.style.display = 'block';
        
        // Scroll to results smoothly
        setTimeout(() => {
          resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
      });
    });
    
    // Collapse results
    if (collapseBtn) {
      collapseBtn.addEventListener('click', function() {
        resultsContainer.style.display = 'none';
        // Reset button states
        settingButtons.forEach(btn => btn.classList.remove('is-active'));
      });
    }
  }
  
  // ============================================
  // Jump to Navigation - Smooth Scroll
  // ============================================
  
  const jumpNavLinks = document.querySelectorAll('.cqc-jump-nav-link');
  
  if (jumpNavLinks.length > 0) {
    jumpNavLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href && href.startsWith('#')) {
          e.preventDefault();
          const targetId = href.substring(1);
          const targetElement = document.getElementById(targetId);
          
          if (targetElement) {
            // Calculate offset for sticky header and jump nav
            const headerOffset = 120; // Account for sticky header
            const jumpNavOffset = 60; // Account for sticky jump nav
            const totalOffset = headerOffset + jumpNavOffset;
            
            const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - totalOffset;
            
            window.scrollTo({
              top: targetPosition,
              behavior: 'smooth'
            });
            
            // Update active state
            jumpNavLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Update URL hash without scrolling again
            if (history.pushState) {
              history.pushState(null, null, href);
            }
          }
        }
      });
    });
    
    // Update active link on scroll
    let ticking = false;
    const updateActiveJumpLink = () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          const scrollPosition = window.scrollY + 180; // Offset for header + jump nav
          
          // Find which section is currently in view
          const sections = [
            'cqc-requirements-heading',
            'mandatory-training-heading',
            'inspection-prep-heading',
            'regulatory-changes-heading',
            'oliver-mcgowan-heading',
            'cqc-resources-heading',
            'government-guidance-heading',
            'cqc-articles-heading',
            'cqc-courses-heading',
            'cqc-faq-heading'
          ];
          
          let activeSection = null;
          
          sections.forEach(sectionId => {
            const section = document.getElementById(sectionId);
            if (section) {
              const sectionTop = section.getBoundingClientRect().top + window.pageYOffset;
              const sectionBottom = sectionTop + section.offsetHeight;
              
              if (scrollPosition >= sectionTop - 100 && scrollPosition < sectionBottom - 100) {
                activeSection = sectionId;
              }
            }
          });
          
          // Update active state
          jumpNavLinks.forEach(link => {
            link.classList.remove('active');
            if (activeSection && link.getAttribute('href') === `#${activeSection}`) {
              link.classList.add('active');
            }
          });
          
          ticking = false;
        });
        ticking = true;
      }
    };
    
    window.addEventListener('scroll', updateActiveJumpLink, { passive: true });
    updateActiveJumpLink(); // Initial check
  }
  
})();
