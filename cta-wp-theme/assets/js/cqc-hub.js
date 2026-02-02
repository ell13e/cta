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
  // Subscription Bar
  // ============================================
  
  const subscriptionBar = document.getElementById('cqc-subscription-bar');
  const closeBtn = subscriptionBar?.querySelector('.cqc-subscription-close');
  const subscriptionForm = subscriptionBar?.querySelector('.cqc-subscription-form');
  
  if (subscriptionBar) {
    // Check if user has already dismissed or subscribed
    const hasDismissed = localStorage.getItem('cqc_subscription_dismissed');
    const hasSubscribed = localStorage.getItem('cqc_subscription_subscribed');
    
    // Show bar if user hasn't dismissed or subscribed
    function checkShowBar() {
      if (hasDismissed || hasSubscribed) {
        return;
      }
      
      const heroSection = document.querySelector('.cqc-hero-section');
      const heroHeight = heroSection?.offsetHeight || 0;
      const scrollY = window.scrollY;
      
      // Show after scrolling past hero + 200px
      if (scrollY > heroHeight + 200) {
        subscriptionBar.classList.add('is-visible');
      }
    }
    
    // Check on scroll
    let scrollTimeout;
    window.addEventListener('scroll', function() {
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(checkShowBar, 100);
    });
    
    // Initial check after page load
    setTimeout(checkShowBar, 2000);
    
    // Close button
    if (closeBtn) {
      closeBtn.addEventListener('click', function() {
        subscriptionBar.classList.remove('is-visible');
        localStorage.setItem('cqc_subscription_dismissed', 'true');
      });
    }
    
    // Handle form submission
    if (subscriptionForm) {
      subscriptionForm.addEventListener('submit', function(e) {
        // Note: Actual form submission would be handled server-side
        // This is just for localStorage tracking
        const email = this.querySelector('#cqc-email')?.value;
        if (email) {
          localStorage.setItem('cqc_subscription_subscribed', 'true');
          // Don't prevent default - let form submit normally
        }
      });
    }
  }
})();
