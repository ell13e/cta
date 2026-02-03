// ============================================
// Upcoming Courses Page - New Layout with Featured Hero
// ============================================

(function() {
  'use strict';

  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  let allEvents = [];
  let filteredEvents = [];
  let currentCategory = 'all';
  let currentPage = 1;
  const eventsPerPage = 9;

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  async function init() {
    try {
      await loadEvents();
      
      // Check for course filter in URL
      const urlParams = new URLSearchParams(window.location.search);
      const courseFilter = urlParams.get('course');
      if (courseFilter) {
        // Filter events by course title
        const decodedCourseTitle = decodeURIComponent(courseFilter);
        allEvents = allEvents.filter(event => {
          return event.title && event.title.toLowerCase() === decodedCourseTitle.toLowerCase();
        });
        // Set category to 'all' to show filtered results
        currentCategory = 'all';
      }
      
      renderCategoryTabs();
      renderFeaturedHero();
      renderEvents();
      setupEventListeners();
    } catch (error) {
      if (isDevelopment) console.error('Error initializing events page:', error);
    }
  }

  // Helper function to get filtered events for current category
  function getFilteredEvents() {
    if (currentCategory === 'all') {
      return allEvents;
    }
    return allEvents.filter(event => {
      const topicCategories = event.topicCategories || [];
      if (topicCategories.length === 0) {
        return false;
      }
      return topicCategories.some(topicCat => {
        const topicKey = window.CourseDataManager && typeof window.CourseDataManager.topicCategoryToKey === 'function'
          ? window.CourseDataManager.topicCategoryToKey(topicCat)
          : topicCat.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        return topicKey === currentCategory;
      });
    });
  }

  async function loadEvents() {
    // Helper to check if a date is upcoming
    const isUpcoming = (dateStr) => {
      try {
        const courseDate = new Date(dateStr);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        courseDate.setHours(0, 0, 0, 0);
        return courseDate >= today;
      } catch (e) {
        return false;
      }
    };

    let scheduledCourses = [];
    
    // Try to load from window.ScheduledCourses first
    if (window.ScheduledCourses && Array.isArray(window.ScheduledCourses)) {
      scheduledCourses = window.ScheduledCourses;
    } else {
    // Fallback to JSON
    try {
      const response = await fetch('assets/data/scheduled-courses.json');
      if (response.ok) {
          scheduledCourses = await response.json();
      }
    } catch (error) {
      if (isDevelopment) console.error('Error loading events:', error);
    }
    }

    // Merge course data and filter for upcoming courses only
    allEvents = scheduledCourses
      .map(mergeCourseData)
      .filter(event => event.date && isUpcoming(event.date))
      .sort((a, b) => new Date(a.date) - new Date(b.date));
  }

  function mergeCourseData(scheduledCourse) {
    let merged;
    if (window.CourseDataManager) {
      merged = window.CourseDataManager.mergeCourseDataForCalendar(scheduledCourse);
    } else {
      merged = {
        ...scheduledCourse,
        location: scheduledCourse.location || 'Maidstone, Kent',
        category: scheduledCourse.category || 'Course'
      };
    }
    return merged;
  }

  function renderCategoryTabs() {
    const tabsContainer = document.querySelector('.events-category-tabs');
    if (!tabsContainer) return;

    // Get ALL categories from CourseDataManager, regardless of whether events exist
    let categoriesData = [];
    if (window.CourseDataManager && typeof window.CourseDataManager.getCategoryConfig === 'function') {
      const categoryConfig = window.CourseDataManager.getCategoryConfig();
      // getCategoryConfig returns an array with 'all' first, then all categories
      categoriesData = categoryConfig;
    } else if (window.CourseData && window.CourseData.topicCategories && Array.isArray(window.CourseData.topicCategories)) {
      // Fallback: Use topicCategories from CourseData
      const categoryMetadata = {
        'Core Care Skills': { shortName: 'Core Care' },
        'Emergency & First Aid': { shortName: 'First Aid' },
        'Medication Management': { shortName: 'Medication' },
        'Safety & Compliance': { shortName: 'Safety' },
        'Health Conditions & Specialist Care': { shortName: 'Specialist Health' },
        'Communication & Workplace Culture': { shortName: 'Communication' },
        'Information & Data Management': { shortName: 'Data & Records' },
        'Nutrition & Hygiene': { shortName: 'Nutrition' },
        'Leadership & Professional Development': { shortName: 'Leadership' }
      };
      
      categoriesData = [
        { key: 'all', title: 'All Courses', shortName: 'All' },
        ...window.CourseData.topicCategories.map(cat => ({
          key: cat.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''),
          title: cat,
          shortName: (categoryMetadata[cat] && categoryMetadata[cat].shortName) || cat
        }))
      ];
    } else {
      // Final fallback: Get unique categories from events
      const uniqueCategories = ['all', ...new Set(allEvents.map(event => {
        return event.category || (event.topicCategories && event.topicCategories[0]) || 'Other';
      }).filter(Boolean))];
      
      categoriesData = uniqueCategories.map(cat => ({
        key: cat === 'all' ? 'all' : cat.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''),
        title: cat,
        shortName: cat === 'all' ? 'All' : cat
      }));
    }

    // Create tabs using short names
    // Ensure we're using the key for data-category (for filtering) but display the shortName
    const tabs = categoriesData.map(categoryData => {
      const categoryKey = categoryData.key || (categoryData.title ? categoryData.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') : 'all');
      const displayName = categoryData.shortName || categoryData.title || 'All';
      const isActive = categoryKey === currentCategory ? 'active' : '';
      return `
        <button 
          class="events-category-tab ${isActive}" 
          role="tab" 
          aria-selected="${categoryKey === currentCategory}"
          data-category="${categoryKey}"
        >
          ${displayName}
        </button>
      `;
    }).join('');

    // Replace the first tab with the generated tabs
    const firstTab = tabsContainer.querySelector('.events-category-tab');
    if (firstTab) {
      firstTab.outerHTML = tabs;
    } else {
      tabsContainer.innerHTML = tabs;
    }
  }

  function renderFeaturedHero() {
    const heroContainer = document.getElementById('featured-hero');
    if (!heroContainer || allEvents.length === 0) return;

    // Helper function to get responsive image for background
    function getResponsiveImageSrc(imagePath, size = '800w') {
      const pathParts = imagePath.split('/');
      const filename = pathParts.pop();
      const directory = pathParts.join('/');
      const nameMatch = filename.match(/^(.+?)\.(webp|jpg|png)$/);
      return nameMatch ? `${directory}/${nameMatch[1]}-${size}.${nameMatch[2]}` : imagePath;
    }

    // Get the first filtered event as featured (respects current category filter)
    const filtered = getFilteredEvents();
    if (filtered.length === 0) {
      // If no filtered events, hide hero
      heroContainer.style.display = 'none';
      return;
    }
    heroContainer.style.display = '';
    const featured = filtered[0];
    const courseImage = getCourseImage(featured);
    // For background images, use a responsive version (800w is good for hero backgrounds)
    const heroImageSrc = getResponsiveImageSrc(courseImage, '800w');
    const formattedDate = formatDate(featured.date);
    const eventUrl = `event-detail.html?id=${generateEventId(featured)}`;

    heroContainer.innerHTML = `
      <div class="events-featured-hero-bg" style="background-image: url('${heroImageSrc}');"></div>
      <div class="events-featured-hero-overlay"></div>
      <div class="events-featured-hero-content">
        <span class="events-featured-hero-badge">Upcoming Event</span>
        <h2 class="events-featured-hero-title">${featured.title}</h2>
        <div class="events-featured-hero-meta">
          <div class="events-featured-hero-meta-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span>${formattedDate}</span>
          </div>
          <div class="events-featured-hero-meta-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span>${featured.time || '9:00 AM - 2:00 PM'}</span>
          </div>
        </div>
        <div class="events-featured-hero-location">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
          <span>${featured.location || 'Maidstone, Kent'}</span>
        </div>
      </div>
    `;

    // Make hero clickable and keyboard accessible
    heroContainer.style.cursor = 'pointer';
    heroContainer.setAttribute('tabindex', '0');
    heroContainer.setAttribute('role', 'button');
    heroContainer.setAttribute('aria-label', `View details for ${featured.title} on ${formattedDate}`);
    heroContainer.onclick = () => window.location.href = eventUrl;
    heroContainer.onkeydown = (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        window.location.href = eventUrl;
      }
    };
  }

  function renderEvents() {
    const gridContainer = document.getElementById('events-grid');
    if (!gridContainer) return;

    // Check if server-rendered content exists (progressive enhancement)
    const hasServerRenderedContent = gridContainer.querySelector('.event-card') !== null;
    const hasActiveFilters = currentCategory !== 'all';

    // Filter events by category using helper function
    filteredEvents = getFilteredEvents();

    // Update featured hero to show first filtered event
    renderFeaturedHero();

    // When viewing 'all', include the featured event in the grid too
    // For specific categories, skip the first event (it's in the hero)
    const eventsToShow = (currentCategory === 'all' || filteredEvents.length <= 1) 
      ? filteredEvents 
      : filteredEvents.slice(1);

    // Pagination
    const startIndex = (currentPage - 1) * eventsPerPage;
    const endIndex = startIndex + eventsPerPage;
    const paginatedEvents = eventsToShow.slice(startIndex, endIndex);

    if (paginatedEvents.length === 0) {
      gridContainer.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
          <p style="font-size: 16px; color: var(--brown-medium);">No events found in this category.</p>
        </div>
      `;
      renderPagination(0);
      return;
    }

    // Only replace content if filters are active OR no server-rendered content exists
    // This allows progressive enhancement: server-rendered content is shown by default,
    // JavaScript only enhances when user interacts with filters
    if (!hasActiveFilters && hasServerRenderedContent) {
      // Server-rendered content exists and no filters active, just update pagination if needed
      renderPagination(eventsToShow.length);
      return;
    }

    // Helper function to check if image has responsive versions
    function hasResponsiveVersions(imagePath) {
      // Check if it's in the course thumbnails directory (these have responsive versions)
      if (imagePath.includes('05_COURSE_THUMBNAILS')) {
        return true;
      }
      // Check if it's an old-style numbered image (like 33.webp, 40.webp) - these don't have responsive versions
      const oldImagePattern = /\/\d+\.(webp|jpg|png)$/;
      if (oldImagePattern.test(imagePath)) {
        return false;
      }
      // For other paths, assume they might have responsive versions
      return true;
    }

    // Helper function to generate image srcset
    function generateImageSrcset(basePath) {
      // If image doesn't have responsive versions, return empty srcset
      if (!hasResponsiveVersions(basePath)) {
        return '';
      }
      
      const pathParts = basePath.split('/');
      const filename = pathParts.pop();
      const directory = pathParts.join('/');
      const nameMatch = filename.match(/^(.+?)\.(webp|jpg|png)$/);
      if (!nameMatch) return '';
      const baseName = nameMatch[1];
      const extension = nameMatch[2];
      return `${directory}/${baseName}-400w.${extension} 400w, ` +
             `${directory}/${baseName}-800w.${extension} 800w, ` +
             `${directory}/${baseName}-1200w.${extension} 1200w, ` +
             `${directory}/${baseName}-1600w.${extension} 1600w`;
    }

    // Helper function to get base src from image path (for fallback)
    function getBaseImageSrc(imagePath) {
      // If image doesn't have responsive versions, use the original path
      if (!hasResponsiveVersions(imagePath)) {
        return imagePath;
      }
      
      const pathParts = imagePath.split('/');
      const filename = pathParts.pop();
      const directory = pathParts.join('/');
      const nameMatch = filename.match(/^(.+?)\.(webp|jpg|png)$/);
      if (!nameMatch) return imagePath;
      // Return the 400w version as the src (this is what actually exists on disk)
      return `${directory}/${nameMatch[1]}-400w.${nameMatch[2]}`;
    }

    // Helper function to get category class
    function getCategoryClass(event) {
      const firstCategory = (event.topicCategories && event.topicCategories.length > 0)
        ? event.topicCategories[0]
        : (event.category || event.categoryName || '');
      
      if (!firstCategory) return 'default';
      
      const categoryClassMap = {
        'Core Care Skills': 'core-care',
        'Emergency & First Aid': 'first-aid',
        'Medication Management': 'medication',
        'Safety & Compliance': 'manual-handling',
        'Health Conditions & Specialist Care': 'specialist-care',
        'Communication & Workplace Culture': 'communication',
        'Information & Data Management': 'data',
        'Nutrition & Hygiene': 'nutrition',
        'Leadership & Professional Development': 'leadership'
      };
      
      if (categoryClassMap[firstCategory]) {
        return categoryClassMap[firstCategory];
      }
      
      return 'default';
    }

    // Helper function to format date for card (full format)
    function formatDateForCard(dateStr) {
      try {
        const date = new Date(dateStr);
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const dayName = days[date.getDay()];
        const day = date.getDate();
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        return `${dayName}, ${day} ${month} ${year}`;
      } catch (error) {
        return formatDate(dateStr);
      }
    }

    // Render event cards
    gridContainer.innerHTML = paginatedEvents.map(event => {
      const courseImage = getCourseImage(event);
      const imageSrcset = generateImageSrcset(courseImage);
      const imageSrc = getBaseImageSrc(courseImage);
      const formattedDate = formatDateForCard(event.date);
      const eventUrl = `event-detail.html?id=${generateEventId(event)}`;
      const location = (event.location || 'Maidstone, Kent').replace(/Maidstone Studios/g, 'The Maidstone Studios');
      // Price handling with discount support
      let price = event.price || '£62';
      let originalPrice = event.originalPrice;
      let hasDiscount = event.hasDiscount || false;
      let discountPercent = event.discountPercent || 0;
      
      // Replace dollar signs with pound signs
      price = String(price).replace(/\$/g, '£');
      // If no currency symbol, add pound sign
      if (!/^[£$]/.test(price)) {
        price = '£' + price;
      }
      
      // Extract numeric values for comparison
      const priceNum = parseFloat(price.replace(/[£$,\s]/g, ''));
      const originalPriceNum = originalPrice ? parseFloat(originalPrice) : null;
      
      // Check if we have a discount (custom price < original price)
      if (originalPriceNum && priceNum < originalPriceNum) {
        hasDiscount = true;
        discountPercent = Math.round((1 - (priceNum / originalPriceNum)) * 100);
      }
      
      // Check for site-wide discount
      const siteWideDiscount = window.ctaData && window.ctaData.siteWideDiscount ? window.ctaData.siteWideDiscount : null;
      if (siteWideDiscount && siteWideDiscount.active && siteWideDiscount.percentage > 0) {
        const siteWidePrice = originalPriceNum * (1 - (siteWideDiscount.percentage / 100));
        // Use site-wide discount if it's better than existing discount
        if (!hasDiscount || siteWidePrice < priceNum) {
          hasDiscount = true;
          discountPercent = siteWideDiscount.percentage;
          priceNum = siteWidePrice;
          price = `£${Math.round(priceNum)}`;
        }
      }
      const spotsLeft = event.spotsLeft !== undefined ? event.spotsLeft : 12;
      const showSpacesBadge = spotsLeft < 5;
      const categoryClass = getCategoryClass(event);
      
      // Get category name for pill (first topic category or fallback)
      const categoryName = (event.topicCategories && event.topicCategories.length > 0)
        ? (() => {
            const firstCategory = event.topicCategories[0];
            if (window.CourseDataManager) {
              const categoryKey = window.CourseDataManager.topicCategoryToKey(firstCategory);
              const categoryConfig = window.CourseDataManager.getCategoryConfig();
              const category = categoryConfig.find(c => c.key === categoryKey);
              return category?.shortName || category?.title || firstCategory;
            }
            return firstCategory;
          })()
        : (event.categoryName || event.category || 'Course');
      
      // Get duration
      const duration = event.duration || '1 Day';

      return `
        <article class="event-card event-card-${categoryClass}" onclick="window.location.href='${eventUrl}'" style="cursor: pointer;" tabindex="0" role="button" aria-label="View details for ${event.title} on ${formattedDate}" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='${eventUrl}'}">
          <div class="event-card-image-wrapper">
            ${showSpacesBadge ? `<div class="event-card-spaces-badge">ONLY ${spotsLeft} SPACES REMAINING</div>` : ''}
            <img ${imageSrcset ? `srcset="${imageSrcset}"` : ''}
                 src="${imageSrc}"
                 alt="${event.title}"
                 class="event-card-image"
                 loading="lazy"
                 ${imageSrcset ? 'sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"' : ''}>
            <div class="event-card-pills">
              <span class="event-card-pill event-card-pill-category">${categoryName}</span>
              <span class="event-card-pill event-card-pill-duration">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                ${duration}
              </span>
            </div>
          </div>
          <div class="event-card-content">
            <div class="event-card-meta-info">
              <div class="event-card-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <time datetime="${event.date}">${formattedDate}</time>
              </div>
              <div class="event-card-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                  <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <span>${location}</span>
              </div>
            </div>
            <h3 class="event-card-title">${event.title}</h3>
            <div class="event-card-footer">
              <div class="event-card-price">
                ${hasDiscount ? `
                  <div style="margin-bottom: 4px;">
                    <span class="badge badge-discount">Save ${discountPercent}%</span>
                    ${siteWideDiscount && siteWideDiscount.active ? `
                      <span class="badge badge-discount-subtle">${siteWideDiscount.label || 'Site-Wide Sale'}</span>
                    ` : ''}
                  </div>
                  <div style="display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;">
                    <span style="text-decoration: line-through; color: #8c8f94; font-size: 14px; font-weight: 400;">
                      £${Math.round(originalPriceNum)}
                    </span>
                    <span class="event-card-price-amount" style="color: #dc3232; font-weight: 700;">
                      From ${price.replace(/[£$]/g, '').replace(/^/, '£')}
                    </span>
                  </div>
                ` : `
                  <span class="event-card-price-amount">From ${price.replace(/[£$]/g, '').replace(/^/, '£')}</span>
                `}
                <span class="event-card-price-label">per person</span>
              </div>
              <div class="event-card-cta">
                <span>Book Now</span>
                <span aria-hidden="true">→</span>
              </div>
            </div>
          </div>
        </article>
      `;
    }).join('');

    renderPagination(eventsToShow.length);
  }

  function renderPagination(totalEvents) {
    const paginationContainer = document.getElementById('events-pagination');
    if (!paginationContainer) return;

    const totalPages = Math.ceil(totalEvents / eventsPerPage);
    
    if (totalPages <= 1) {
      paginationContainer.innerHTML = '';
      return;
    }

    const pages = [];
    
    // Previous button
    pages.push(`
      <button class="events-pagination-btn" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>
    `);

    // Page numbers
    for (let i = 1; i <= Math.min(totalPages, 5); i++) {
      pages.push(`
        <button class="events-pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">
          ${i}
        </button>
      `);
    }

    if (totalPages > 5) {
      pages.push(`<span style="color: var(--brown-light);">...</span>`);
      pages.push(`
        <button class="events-pagination-btn" data-page="${totalPages}">
          ${totalPages}
        </button>
      `);
    }

    // Next button
    pages.push(`
      <button class="events-pagination-btn" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    `);

    paginationContainer.innerHTML = pages.join('');
  }

  function setupEventListeners() {
    // Category tab clicks
    document.addEventListener('click', (e) => {
      if (e.target.classList.contains('events-category-tab')) {
        const category = e.target.dataset.category;
        if (category !== currentCategory) {
          currentCategory = category;
          currentPage = 1;
          // Update active tab
          document.querySelectorAll('.events-category-tab').forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
          });
          e.target.classList.add('active');
          e.target.setAttribute('aria-selected', 'true');
          
          renderEvents();
        }
      }

      // Pagination clicks
      if (e.target.closest('.events-pagination-btn')) {
        const btn = e.target.closest('.events-pagination-btn');
        if (btn.disabled) return;
        
        const page = parseInt(btn.dataset.page);
        if (page && page !== currentPage) {
          currentPage = page;
          renderEvents();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      }
    });
  }

  // Helper functions
  function formatDate(dateStr) {
    try {
      const date = new Date(dateStr);
      return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
      });
    } catch (error) {
      return dateStr;
    }
  }

  function generateEventId(event) {
    // Use the date as-is from the event (should be in format like "Wed, 7 Jan 2026")
    // This must match exactly how it's stored in scheduled-courses.json
    const dateStr = event.date || '';
    if (!dateStr) {
      if (isDevelopment) console.warn('Event missing date:', event);
      return `${event.title || 'course'}-unknown`.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    }
    return `${event.title}-${dateStr}`
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
  }

  function getCourseImage(course) {
    // Normalize string for matching (trim, lowercase, collapse whitespace)
    function normalize(str) {
      if (!str) return '';
      return String(str).trim().toLowerCase().replace(/\s+/g, ' ');
    }
    
    // First check if course has image property
    // But ignore old image paths that don't have responsive versions (like 33.webp, 40.webp)
    if (course.image) {
      // Check if it's an old-style image path (just a number like 33.webp or 40.webp)
      const oldImagePattern = /\/\d+\.(webp|jpg|png)$/;
      if (oldImagePattern.test(course.image)) {
        // Ignore old image paths, fall through to title/category mapping
      } else {
        // Check if it's in the course thumbnails directory and has responsive versions
        const isCourseThumbnail = course.image.includes('05_COURSE_THUMBNAILS');
        if (isCourseThumbnail) {
          return course.image;
        }
        // For other image paths, check if they might have responsive versions
        // If not, fall through to title/category mapping
      }
    }
    
    // Map course titles directly to available stock photos with variety
    const titleImageMap = {
      'Emergency First Aid at Work': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid05.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid06.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid07.webp'
      ],
      'Emergency Paediatric First Aid': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid05.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/pediatric_first_aid01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/pediatric_first_aid02.webp'
      ],
      'Adult Social Care Certificate': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate05.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate06.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate07.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate08.webp'
      ],
      'Epilepsy & Emergency Medication': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy05.webp'
      ],
      'Basic Life Support': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/basic_life_support01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/basic_life_support02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/basic_life_support03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/basic_life_support04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/basic_life_support05.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/basic_life_support06.webp'
      ],
      'Moving & Handling': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling05.webp'
      ],
      'Manual Handling': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling05.webp'
      ],
      'Safeguarding Adults': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults05.webp'
      ],
      'Medication Awareness': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training05.webp'
      ]
    };
    
    // Build normalized title lookup map for case-insensitive matching
    const normalizedTitleMap = {};
    Object.keys(titleImageMap).forEach(key => {
      normalizedTitleMap[normalize(key)] = titleImageMap[key];
    });
    
    // Check title mapping first (most reliable)
    // Try exact match first, then normalized match
    if (course.title) {
      let images = titleImageMap[course.title];
      if (!images) {
        const normalizedTitle = normalize(course.title);
        images = normalizedTitleMap[normalizedTitle];
      }
      
      if (images && images.length > 0) {
        // Use course ID or title hash to consistently select an image for the same course
        const hash = (course.id || course.title || '').toString().split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
        return images[hash % images.length];
      }
    }
    
    // Fallback to stock photo based on category with variety
    const categoryImageMap = {
      'Core Care Skills': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate05.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate06.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate07.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate08.webp'
      ],
      'Core Care': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate05.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate06.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate07.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate08.webp'
      ],
      'Emergency & First Aid': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid05.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid06.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid07.webp'
      ],
      'Emergency First Aid': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid05.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid06.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid07.webp'
      ],
      'First Aid': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid05.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid06.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid07.webp'
      ],
      'Medication Management': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training05.webp'
      ],
      'Medication': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training05.webp'
      ],
      'Safety & Compliance': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling05.webp'
      ],
      'Manual Handling': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling05.webp'
      ],
      'Health Conditions & Specialist Care': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy05.webp'
      ],
      'Specialist Care': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy05.webp'
      ],
      'Safeguarding': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults05.webp'
      ]
    };
    
    // Build normalized category lookup map for case-insensitive matching
    const normalizedCategoryMap = {};
    Object.keys(categoryImageMap).forEach(key => {
      normalizedCategoryMap[normalize(key)] = categoryImageMap[key];
    });
    
    // Check category mapping
    const category = course.categoryName || course.category || (course.topicCategories && course.topicCategories[0]);
    if (category) {
      let images = categoryImageMap[category];
      if (!images) {
        const normalizedCategory = normalize(category);
        images = normalizedCategoryMap[normalizedCategory];
      }
      
      if (images && images.length > 0) {
        // Use course ID or title hash to consistently select an image for the same course
        const hash = (course.id || course.title || '').toString().split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
        return images[hash % images.length];
      }
    }
    
    // Final fallback
    return 'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp';
  }

})();
