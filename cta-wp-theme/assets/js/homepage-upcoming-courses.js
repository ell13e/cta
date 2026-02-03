/**
 * Homepage Upcoming Courses
 * Dynamically loads and displays the next 3 upcoming courses on the homepage
 */

(function() {
  'use strict';

  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  async function init() {
    const gridContainer = document.querySelector('.upcoming-courses .courses-grid');
    if (!gridContainer) {
      if (isDevelopment) console.warn('Homepage upcoming courses grid not found');
      return;
    }

    // Check if server-rendered content already exists (progressive enhancement)
    const hasServerRenderedContent = gridContainer.querySelector('.event-card') !== null;
    
    // If server-rendered content exists, only load additional events if needed
    // Otherwise, load the initial 3 events
    if (hasServerRenderedContent) {
      if (isDevelopment) console.log('Server-rendered content detected, skipping initial render');
      // Content is already there, JavaScript can enhance it later if needed
      return;
    }

    try {
      const events = await loadUpcomingEvents();
      if (events.length === 0) {
        if (isDevelopment) console.warn('No upcoming events found');
        return;
      }

      // Get the next 3 upcoming courses
      const nextThreeEvents = events.slice(0, 3);
      renderCourses(nextThreeEvents, gridContainer);
    } catch (error) {
      if (isDevelopment) console.error('Error loading homepage upcoming courses:', error);
    }
  }

  async function loadUpcomingEvents() {
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
        if (isDevelopment) console.error('Error loading scheduled courses:', error);
      }
    }

    // Merge course data and filter for upcoming courses only
    const events = scheduledCourses
      .map(mergeCourseData)
      .filter(event => event.date && isUpcoming(event.date))
      .sort((a, b) => new Date(a.date) - new Date(b.date));

    return events;
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

  function generateEventId(event) {
    // Use the date as-is from the event (should be in format like "Wed, 7 Jan 2026")
    // This must match exactly how it's stored in scheduled-courses.json
    const dateStr = event.date || '';
    if (!dateStr) {
      console.warn('Event missing date:', event);
      return `${event.title || 'course'}-unknown`.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    }
    return `${event.title}-${dateStr}`
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
  }

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

  function formatTime(timeStr) {
    if (!timeStr) return '9:30 AM - 4:00 PM';
    return timeStr;
  }

  function getCourseImage(course) {
    // Check if course has image property and it's valid
    if (course.image) {
      // Check if it's an old-style image path (just a number like 33.webp or 40.webp)
      const oldImagePattern = /\/\d+\.(webp|jpg|png)$/;
      if (!oldImagePattern.test(course.image)) {
        // Check if it's in the course thumbnails directory
        const isCourseThumbnail = course.image.includes('05_COURSE_THUMBNAILS');
        if (isCourseThumbnail) {
          return course.image;
        }
      }
    }
    
    // Map course titles directly to available stock photos (use first image from array if available)
    const titleImageMap = {
      'Emergency First Aid at Work': 'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid04.webp',
      'Emergency Paediatric First Aid': 'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid01.webp',
      'Adult Social Care Certificate': 'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp',
      'Epilepsy & Emergency Medication': 'assets/img/stock_photos/05_COURSE_THUMBNAILS/epilepsy01.webp'
    };

    if (titleImageMap[course.title]) {
      return titleImageMap[course.title];
    }

    // Fallback to a default image
    return 'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid04.webp';
  }


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

  function getCategoryClass(event) {
    // Get the first topic category or fall back to category
    const firstCategory = (event.topicCategories && event.topicCategories.length > 0)
      ? event.topicCategories[0]
      : (event.category || event.categoryName || '');
    
    if (!firstCategory) return 'default';
    
    // Convert to key for class name
    const categoryKey = window.CourseDataManager && typeof window.CourseDataManager.topicCategoryToKey === 'function'
      ? window.CourseDataManager.topicCategoryToKey(firstCategory)
      : firstCategory.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    
    // Map to CSS class names
    const classMap = {
      'core-care-skills': 'core-care',
      'emergency-first-aid': 'first-aid',
      'medication-management': 'medication',
      'safety-compliance': 'manual-handling',
      'health-conditions-specialist-care': 'specialist-care'
    };
    
    return classMap[categoryKey] || 'default';
  }

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

  function cleanEventTitle(title) {
    if (!title) return '';
    // Remove location suffixes like " in The Maidstone Studios", " in Maidstone Studios", etc.
    return title.replace(/\s+in\s+(The\s+)?Maidstone\s+Studios?/i, '').trim();
  }

  function renderCourses(events, container) {
    container.innerHTML = events.map(event => {
      const courseImage = getCourseImage(event);
      const imageSrcset = generateImageSrcset(courseImage);
      const imageSrc = getBaseImageSrc(courseImage);
      const formattedDate = formatDateForCard(event.date);
      const eventUrl = `event-detail.html?id=${generateEventId(event)}`;
      const location = (event.location || 'Maidstone, Kent').replace(/Maidstone Studios/g, 'The Maidstone Studios');
      // Clean title to remove location suffix
      const cleanTitle = cleanEventTitle(event.title);
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
        <article class="event-card event-card-${categoryClass}" onclick="window.location.href='${eventUrl}'" style="cursor: pointer;" tabindex="0" role="button" aria-label="View details for ${cleanTitle} on ${formattedDate}" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='${eventUrl}'}">
          <div class="event-card-image-wrapper">
            ${showSpacesBadge ? `<div class="event-card-spaces-badge">ONLY ${spotsLeft} SPACES REMAINING</div>` : ''}
            <img ${imageSrcset ? `srcset="${imageSrcset}"` : ''}
                 src="${imageSrc}"
                 alt="${cleanTitle}"
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
            <h3 class="event-card-title">${cleanTitle}</h3>
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
  }
})();
