// ============================================
// Event Detail Page - Dynamic Course Loading
// ============================================

(function() {
  'use strict';

  // Development mode flag
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  // Parse URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const courseId = urlParams.get('id');

  // If no course ID, redirect to calendar
  if (!courseId) {
    if (isDevelopment) console.error('No course ID provided');
    window.location.href = 'upcoming-courses.html';
    return;
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Helper function to update or create meta tags
  function updateMetaTag(attr, value, content) {
    const selector = attr === 'property' ? `meta[property="${value}"]` : `meta[name="${value}"]`;
    let metaTag = document.querySelector(selector);
    
    if (!metaTag) {
      metaTag = document.createElement('meta');
      if (attr === 'property') {
        metaTag.setAttribute('property', value);
      } else {
        metaTag.setAttribute('name', value);
      }
      document.head.appendChild(metaTag);
    }
    
    metaTag.setAttribute('content', content);
  }

  async function init() {
    // Show loading state
    showLoadingState();
    
    try {
      // Load course data
      const courseData = await loadCourseData(courseId);
      
      if (!courseData) {
        showErrorState('Course not found. Redirecting to upcoming courses...');
        setTimeout(() => {
          window.location.href = 'upcoming-courses.html';
        }, 2000);
        return;
      }

      // Populate page with course data
      populatePage(courseData);
      
      // Initialize accordions
      initAccordions();
      
      // Load related courses (async)
      loadRelatedCourses(courseData).catch(err => {
        if (isDevelopment) console.error('Error loading related courses:', err);
      });
      
      // Hide loading state
      hideLoadingState();
      
      // Announce to screen readers
      announceToScreenReader(`Course details loaded for ${courseData.title}`);
      
      // Focus management - move focus to main content for screen readers
      const mainContent = document.getElementById('main-content');
      if (mainContent) {
        mainContent.setAttribute('tabindex', '-1');
        mainContent.focus();
        // Remove tabindex after focus
        setTimeout(() => mainContent.removeAttribute('tabindex'), 100);
      }
      
    } catch (error) {
      if (isDevelopment) console.error('Error loading course:', error);
      showErrorState('Unable to load course details. Redirecting...');
      setTimeout(() => {
        window.location.href = 'upcoming-courses.html';
      }, 2000);
    }
  }

  function showLoadingState() {
    const hero = document.querySelector('.event-detail-hero-content');
    const content = document.querySelector('.event-detail-content');
    
    if (hero) {
      hero.classList.add('loading');
      hero.setAttribute('aria-busy', 'true');
    }
    
    if (content) {
      content.classList.add('loading');
    }
  }

  function hideLoadingState() {
    const hero = document.querySelector('.event-detail-hero-content');
    const content = document.querySelector('.event-detail-content');
    
    if (hero) {
      hero.classList.remove('loading');
      hero.removeAttribute('aria-busy');
    }
    
    if (content) {
      content.classList.remove('loading');
    }
  }

  function showErrorState(message) {
    hideLoadingState();
    
    const hero = document.querySelector('.event-detail-hero-content');
    if (hero) {
      const errorDiv = document.createElement('div');
      errorDiv.className = 'event-detail-error';
      errorDiv.setAttribute('role', 'alert');
      errorDiv.innerHTML = `
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <p>${message}</p>
      `;
      hero.appendChild(errorDiv);
    }
  }

  function announceToScreenReader(message) {
    const announcement = document.createElement('div');
    announcement.setAttribute('role', 'status');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    document.body.appendChild(announcement);
    setTimeout(() => {
      // Check if element still exists before removing
      if (announcement.parentNode) {
        document.body.removeChild(announcement);
      }
    }, 1000);
  }

  // Accordion functionality is now handled by unified accordion.js

  function populateCurriculum(course) {
    const curriculumContainer = document.getElementById('course-curriculum');
    if (!curriculumContainer) return;

    const curriculum = course.curriculum || [];
    if (curriculum.length === 0) return;

    // Calculate time per module
    const totalMinutes = parseDuration(course.duration || course.hours);
    const timePerModule = Math.round(totalMinutes / curriculum.length / 15) * 15;

    // Build HTML
    const html = curriculum.map((item, index) => `
      <div class="course-detail-curriculum-item">
        <div class="course-detail-curriculum-number">${index + 1}</div>
        <div class="course-detail-curriculum-content">
          <div class="course-detail-curriculum-header">
            <h4 class="course-detail-curriculum-title">${item.title || item}</h4>
            <span class="course-detail-curriculum-time">${timePerModule} min</span>
          </div>
          ${item.description ? `<p class="course-detail-curriculum-description">${item.description}</p>` : ''}
        </div>
      </div>
    `).join('');

    curriculumContainer.innerHTML = html;
  }

  function parseDuration(duration) {
    if (!duration) return 180; // Default 3 hours
    
    // Parse "3 hours" or "24 hours" or "½ Day" to minutes
    const hourMatch = duration.match(/(\d+\.?\d*)\s*(hour|hr)/i);
    if (hourMatch) {
      return parseFloat(hourMatch[1]) * 60;
    }
    
    const dayMatch = duration.match(/(\d+\.?\d*|½|¼|¾)\s*day/i);
    if (dayMatch) {
      const dayValue = dayMatch[1];
      if (dayValue === '½') return 180; // Half day = 3 hours
      if (dayValue === '¼') return 90;  // Quarter day = 1.5 hours
      if (dayValue === '¾') return 270; // Three-quarter day = 4.5 hours
      return parseFloat(dayValue) * 360; // Full day = 6 hours
    }
    
    return 180; // Default 3 hours
  }

  function populateCourseModules(course) {
    const modulesContainer = document.getElementById('course-modules');
    if (!modulesContainer) return;

    const outcomes = course.learningOutcomes || [];
    if (outcomes.length === 0) return;

    // Group outcomes into modules (4 outcomes per module)
    const itemsPerModule = Math.ceil(outcomes.length / 4);
    const modules = [];
    
    for (let i = 0; i < outcomes.length; i += itemsPerModule) {
      modules.push(outcomes.slice(i, i + itemsPerModule));
    }

    // Module names based on common patterns
    const moduleNames = [
      'Foundation & Responsibilities',
      'Equality & Communication',
      'Health & Wellbeing',
      'Safety & Protection'
    ];

    // Build HTML for nested accordions
    const html = modules.map((moduleItems, index) => {
      const moduleName = moduleNames[index] || `Module ${index + 1}`;
      const moduleId = `module-${index}`;
      
      return `
        <div class="course-detail-module">
          <button 
            type="button"
            class="course-detail-module-header"
            aria-expanded="false"
            aria-controls="${moduleId}"
            onclick="toggleModule(this)"
          >
            <div>
              <div class="course-detail-module-title">Module ${index + 1}: ${moduleName}</div>
              <div class="course-detail-module-meta">${moduleItems.length} lessons</div>
            </div>
            <svg class="course-detail-module-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div class="course-detail-module-content" id="${moduleId}">
            ${moduleItems.map(item => `
              <div class="course-detail-lesson-item">
                <span>${item}</span>
              </div>
            `).join('')}
          </div>
        </div>
      `;
    }).join('');

    modulesContainer.innerHTML = html;
  }

  // Module toggle function (called from HTML onclick)
  window.toggleModule = function(button) {
    const isExpanded = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', !isExpanded);
  };

  async function loadCourseData(courseId) {
    try {
      // First, try to load scheduled courses
      let scheduledCourses = [];
      
      // Try JavaScript module first
      if (window.ScheduledCourses && Array.isArray(window.ScheduledCourses)) {
        scheduledCourses = window.ScheduledCourses;
      } else {
        // Fallback to JSON
        const response = await fetch('assets/data/scheduled-courses.json');
        if (response.ok) {
          scheduledCourses = await response.json();
        }
      }

      // Find the scheduled course by ID
      const scheduled = scheduledCourses.find(course => {
        const generatedId = `${course.title}-${course.date}`
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-|-$/g, '');
        return generatedId === courseId;
      });

      if (!scheduled) {
        if (isDevelopment) {
          console.error('Course not found for ID:', courseId);
          console.log('Looking for course with ID:', courseId);
          console.log('Available course IDs:', scheduledCourses.map(c => {
            const id = `${c.title}-${c.date}`.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            return `${c.title} (${c.date}) -> ${id}`;
          }));
        }
        return null;
      }

      // Merge with course database details if available
      if (window.CourseDataManager) {
        return window.CourseDataManager.mergeCourseDataForCalendar(scheduled);
      }

      return scheduled;
      
    } catch (error) {
      if (isDevelopment) console.error('Error loading course data:', error);
      return null;
    }
  }

  function populatePage(course) {
    // Update page title
    const pageTitle = `${course.title} - ${formatDate(course.date)} | Continuity Training Academy`;
    document.title = pageTitle;
    
    // Update meta description
    const metaDescription = document.querySelector('meta[name="description"]');
    const descriptionText = `Book your spot for ${course.title} on ${formatDate(course.date)} at Continuity Training Academy. CQC-compliant, CPD-accredited training in Kent.`;
    if (metaDescription) {
      metaDescription.content = descriptionText;
    }

    // Update Open Graph tags
    updateMetaTag('property', 'og:title', pageTitle);
    updateMetaTag('property', 'og:description', descriptionText);
    updateMetaTag('property', 'og:url', window.location.href);
    
    // Update Twitter Card tags
    updateMetaTag('name', 'twitter:title', pageTitle);
    updateMetaTag('name', 'twitter:description', descriptionText);
    
    // Update canonical URL
    const canonical = document.querySelector('link[rel="canonical"]');
    if (canonical) {
      canonical.href = window.location.href;
    }

    // Update breadcrumbs
    const breadcrumbCurrent = document.querySelector('.breadcrumb-current');
    if (breadcrumbCurrent) {
      breadcrumbCurrent.textContent = course.title;
    }

    // Update title
    const title = document.querySelector('#event-detail-heading');
    if (title) {
      title.textContent = course.title;
    }

    // Update meta info in hero
    const metaItems = document.querySelectorAll('.event-detail-hero-meta-item span');
    if (metaItems.length >= 4) {
      metaItems[0].textContent = formatDate(course.date);
      metaItems[1].textContent = course.duration || '1 Day';
      let heroLocation = course.location || 'Maidstone, Kent';
      heroLocation = heroLocation.replace(/Maidstone Studios/g, 'The Maidstone Studios');
      metaItems[2].textContent = heroLocation;
      metaItems[3].textContent = `${course.spotsLeft || '8'} spots left`;
    }

    // Update CTA buttons - Note: Buttons will be updated in hero section separately
    // This section kept for backward compatibility but buttons are now in hero

    // Update course description (in Description accordion)
    const descriptionEl = document.getElementById('course-description');
    if (descriptionEl && course.description) {
      descriptionEl.innerHTML = `<p>${course.description}</p>`;
    }

    // Update learning outcomes (What You'll Learn - accordion with grid only)
    const learningIntro = document.getElementById('learn-intro');
    if (learningIntro) {
      // Hide the intro paragraph
      learningIntro.style.display = 'none';
    }
    
    const learningList = document.getElementById('learn-list');
    if (learningList && course.learningOutcomes && course.learningOutcomes.length > 0) {
      learningList.innerHTML = course.learningOutcomes.map(outcome => `
        <div class="course-detail-learn-item">${outcome}</div>
      `).join('');
    }

    // Update requirements
    const requirementsList = document.getElementById('course-requirements');
    if (requirementsList) {
      if (course.requirements && course.requirements !== 'null' && course.requirements !== null) {
        // Handle both string and array formats
        if (Array.isArray(course.requirements)) {
          requirementsList.innerHTML = course.requirements.map(req => `<li>${req}</li>`).join('');
        } else {
          requirementsList.innerHTML = `<p>${course.requirements}</p>`;
        }
      } else {
        requirementsList.innerHTML = `<p>No specific prerequisites required. This course is suitable for beginners and those seeking to refresh their knowledge.</p>`;
      }
    }

    // Update who should attend (in accordion)
    const audienceEl = document.getElementById('course-audience');
    if (audienceEl && course.whoShouldAttend) {
      audienceEl.innerHTML = `<p>${course.whoShouldAttend}</p>`;
    }

    // Update certification (in accordion)
    const certificationEl = document.getElementById('course-certification');
    if (certificationEl) {
      let certHtml = '';
      
      if (course.accreditation || course.mappedTo) {
        certHtml += '<p style="margin-bottom: 1rem;"><strong>This course is mapped to:</strong></p>';
        certHtml += '<div class="course-detail-certification-box">';
        
        const mappings = course.mappedTo ? course.mappedTo.split('|') : [course.accreditation];
        certHtml += mappings.map(mapping => `<p>✓ ${mapping.trim()}</p>`).join('');
        
        certHtml += '</div>';
      }
      
      certHtml += '<p style="margin-top: 1rem;">';
      if (course.validFor && course.validFor !== 'Permanent') {
        certHtml += `Upon successful completion, you will be awarded a certificate valid for ${course.validFor}.`;
      } else {
        certHtml += 'Upon successful completion, you will be awarded the course certificate.';
      }
      certHtml += '</p>';
      
      certificationEl.innerHTML = certHtml;
    }

    // Update accessibility support (in accordion)
    const accessibilityEl = document.getElementById('course-accessibility');
    if (accessibilityEl) {
      if (course.accessibilityInfo && course.accessibilityInfo !== 'null' && course.accessibilityInfo !== null) {
        accessibilityEl.innerHTML = `<p>${course.accessibilityInfo}</p>`;
      } else {
        accessibilityEl.innerHTML = `<p>We are committed to making our courses accessible to everyone. Please contact us to discuss any specific accessibility requirements you may have.</p>`;
      }
    }

    // Update sidebar booking card
    const sidebarPrice = document.querySelector('#sidebar-price');
    if (sidebarPrice) {
      // Strip existing £ symbol before adding new one
      const cleanPrice = String(course.price || '62').replace(/£/g, '');
      sidebarPrice.textContent = `From £${cleanPrice}`;
    }

    // Update course details in sidebar
    const startDateEl = document.getElementById('sidebar-start-date');
    if (startDateEl) {
      startDateEl.textContent = formatDate(course.date);
    }
    
    const enrolledEl = document.getElementById('sidebar-enrolled');
    if (enrolledEl) {
      const spotsLeft = course.spotsLeft || 8;
      enrolledEl.textContent = `${spotsLeft} spots left`;
    }
    
    const durationEl = document.getElementById('sidebar-duration');
    if (durationEl) {
      durationEl.textContent = course.duration || '1 Day';
    }
    
    const skillLevelEl = document.getElementById('sidebar-level');
    if (skillLevelEl && course.level) {
      skillLevelEl.innerHTML = `<span class="course-detail-badge">${course.level}</span>`;
    }
    
    const locationEl = document.getElementById('sidebar-location');
    if (locationEl && course.location) {
      // Update "Maidstone Studios" to "The Maidstone Studios"
      let locationText = course.location;
      locationText = locationText.replace(/Maidstone Studios/g, 'The Maidstone Studios');
      locationEl.textContent = locationText;
    }
    
    const categoryEl = document.getElementById('sidebar-category');
    if (categoryEl) {
      // Get category - prefer categoryName, then category, then first topicCategory
      const categoryName = course.categoryName || 
                           course.category || 
                           (course.topicCategories && course.topicCategories[0]) || 
                           'General';
      categoryEl.textContent = categoryName;
    }

    // Update level badge if exists
    const levelBadge = document.querySelector('.event-detail-level');
    if (levelBadge && course.level) {
      levelBadge.textContent = course.level;
    }

    // Update booking modal category field
    const bookingCourseField = document.getElementById('booking-course');
    if (bookingCourseField) {
      const categoryName = course.categoryName || 
                           course.category || 
                           (course.topicCategories && course.topicCategories[0]) || 
                           'Course';
      bookingCourseField.value = categoryName;
    }

    // Update booking modal date field
    const bookingDateField = document.getElementById('booking-date');
    if (bookingDateField) {
      bookingDateField.value = formatDate(course.date);
    }

    // Update hero background image - get course image (optional, subtle effect)
    const courseImage = getCourseImage(course);
    if (courseImage) {
      const heroSection = document.querySelector('.event-detail-hero');
      if (heroSection) {
        // Preload image for better UX
        const img = new Image();
        img.onload = () => {
          // Apply background image after it's loaded
          heroSection.style.backgroundImage = `url(${courseImage})`;
          heroSection.style.backgroundSize = 'cover';
          heroSection.style.backgroundPosition = 'center';
          heroSection.style.backgroundAttachment = 'scroll';
          heroSection.classList.add('bg-loaded');
        };
        img.onerror = () => {
          // If image fails to load, keep default background
          if (isDevelopment) console.warn('Background image failed to load:', courseImage);
        };
        img.src = courseImage;
      }
    }
    
    // Add smooth scroll behavior for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href.length > 1) {
          const target = document.querySelector(href);
          if (target) {
            e.preventDefault();
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
            // Update focus for accessibility
            target.setAttribute('tabindex', '-1');
            target.focus();
            setTimeout(() => target.removeAttribute('tabindex'), 1000);
          }
        }
      });
    });
  }

  function formatDate(dateStr) {
    try {
      const date = new Date(dateStr);
      return date.toLocaleDateString('en-GB', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      });
    } catch (error) {
      return dateStr;
    }
  }

  function getCategoryClass(course) {
    const categoryName = course.category || (course.topicCategories && course.topicCategories[0]) || '';
    
    if (!categoryName) return 'default';
    
    const categoryClassMap = {
      'Core Care Skills': 'core-care',
      'Emergency & First Aid': 'first-aid',
      'Medication Management': 'medication',
      'Safety & Compliance': 'safety',
      'Health Conditions & Specialist Care': 'specialist-care',
      'Communication & Workplace Culture': 'communication',
      'Information & Data Management': 'data',
      'Nutrition & Hygiene': 'nutrition',
      'Leadership & Professional Development': 'leadership'
    };
    
    if (categoryClassMap[categoryName]) {
      return categoryClassMap[categoryName];
    }
    
    return categoryName.toLowerCase().replace(/\s+/g, '-').replace(/&/g, '').replace(/[^a-z0-9-]/g, '');
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

  async function loadRelatedCourses(currentCourse) {
    const relatedEventsContainer = document.getElementById('related-courses');
    if (!relatedEventsContainer) return;

    try {
      // Get all scheduled courses - try multiple sources
      let allCourses = [];
      
      // Try window.scheduledCourses (lowercase)
      if (window.scheduledCourses && Array.isArray(window.scheduledCourses)) {
        allCourses = window.scheduledCourses;
      }
      // Try window.ScheduledCourses (uppercase)
      else if (window.ScheduledCourses && Array.isArray(window.ScheduledCourses)) {
        allCourses = window.ScheduledCourses;
      }
      // Try to fetch from JSON if not available
      else {
        try {
          const response = await fetch('assets/data/scheduled-courses.json');
          if (response.ok) {
            allCourses = await response.json();
            // Set it for future use
            window.scheduledCourses = allCourses;
          }
        } catch (fetchError) {
          if (isDevelopment) console.warn('Could not load scheduled courses:', fetchError);
        }
      }
      
      // Merge with course database to get category information
      if (window.CourseDataManager && allCourses.length > 0) {
        allCourses = allCourses.map(course => {
          return window.CourseDataManager.mergeCourseDataForCalendar(course);
        });
      }
      
      // Get current course's category
      const currentCategory = currentCourse.category || 
                              currentCourse.categoryName || 
                              (currentCourse.topicCategories && currentCourse.topicCategories[0]) || 
                              null;
      
      // Generate current course ID for comparison (same format as loadCourseData - always generate from title and date)
      const currentCourseId = `${currentCourse.title}-${currentCourse.date}`
                                .toLowerCase()
                                .replace(/[^a-z0-9]+/g, '-')
                                .replace(/^-|-$/g, '');
      
      // Helper function to parse date and check if it's in the future
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
      
      // Filter courses by same category, excluding current course, only upcoming
      let relatedCourses = allCourses.filter(course => {
        // Generate course ID for comparison (same format as loadCourseData - always generate from title and date)
        const courseId = `${course.title}-${course.date}`
                          .toLowerCase()
                          .replace(/[^a-z0-9]+/g, '-')
                          .replace(/^-|-$/g, '');
        
        if (courseId === currentCourseId) return false;
        
        // Only include upcoming courses
        if (!isUpcoming(course.date)) return false;
        
        const courseCategory = course.category || 
                              course.categoryName || 
                              (course.topicCategories && course.topicCategories[0]) || 
                              null;
        return courseCategory === currentCategory;
      });
      
      // If no courses in same category, fall back to other categories (still upcoming)
      if (relatedCourses.length === 0) {
        relatedCourses = allCourses.filter(course => {
          // Generate course ID for comparison (same format as loadCourseData - always generate from title and date)
          const courseId = `${course.title}-${course.date}`
                            .toLowerCase()
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-|-$/g, '');
          if (courseId === currentCourseId) return false;
          return isUpcoming(course.date);
        });
      }
      
      // Sort by date (earliest first) and limit to 3
      relatedCourses.sort((a, b) => {
        try {
          return new Date(a.date) - new Date(b.date);
        } catch (e) {
          return 0;
        }
      });
      
      // Limit to 3 courses
      if (relatedCourses.length > 3) {
        relatedCourses = relatedCourses.slice(0, 3);
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

      // Function to render a course card
      const renderCourseCard = (course) => {
        const courseDate = formatDate(course.date);
        const courseImage = getCourseImage(course);
        const imageSrcset = generateImageSrcset(courseImage);
        const imageSrc = getBaseImageSrc(courseImage);
        const categoryName = course.categoryName || course.category || (course.topicCategories && course.topicCategories[0]) || 'Course';
        const categoryClass = getCategoryClass(course);
        // Generate course ID for URL (same format as loadCourseData - always generate from title and date)
        const courseId = `${course.title}-${course.date}`
                          .toLowerCase()
                          .replace(/[^a-z0-9]+/g, '-')
                          .replace(/^-|-$/g, '');
        const courseUrl = `event-detail.html?id=${courseId}`;

        return `
          <article class="event-card event-card-${categoryClass}" onclick="window.location.href='${courseUrl}'" style="cursor: pointer;">
            <div class="event-card-image-wrapper">
              <img ${imageSrcset ? `srcset="${imageSrcset}"` : ''}
                   src="${imageSrc}"
                   alt="${course.title}"
                   class="event-card-image"
                   loading="lazy"
                   width="400"
                   height="225"
                   ${imageSrcset ? 'sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"' : ''}>
              <div class="event-card-pills">
                <span class="event-card-pill event-card-pill-category">${categoryName}</span>
                <span class="event-card-pill event-card-pill-duration">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                  ${course.duration || '1 Day'}
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
                  <time datetime="${course.date}">${courseDate}</time>
                </div>
                <div class="event-card-meta-item">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <span>${(course.location || 'Maidstone, Kent').replace(/Maidstone Studios/g, 'The Maidstone Studios')}</span>
                </div>
              </div>
              <h3 class="event-card-title">${course.title}</h3>
              <div class="event-card-footer">
                <div class="event-card-price">
                  ${course.hasDiscount ? `
                    <div style="margin-bottom: 4px;">
                      <span class="badge badge-discount">Save ${course.discountPercent}%</span>
                      ${course.siteWideDiscount && course.siteWideDiscount.active ? `
                        <span class="badge badge-discount-subtle">${course.siteWideDiscount.label || 'Site-Wide Sale'}</span>
                      ` : ''}
                    </div>
                    <div style="display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;">
                      <span style="text-decoration: line-through; color: #8c8f94; font-size: 14px; font-weight: 400;">
                        £${Math.round(course.originalPrice)}
                      </span>
                      <span class="event-card-price-amount" style="color: #dc3232; font-weight: 700;">
                        From £${String(course.price || '62').replace(/£/g, '')}
                      </span>
                    </div>
                  ` : `
                    <span class="event-card-price-amount">From £${String(course.price || '62').replace(/£/g, '')}</span>
                  `}
                  <span class="event-card-price-label">per person</span>
                </div>
              </div>
            </div>
          </article>
        `;
      };

      if (relatedCourses.length === 0) {
        // Show compelling CTA instead of empty message
        relatedEventsContainer.innerHTML = `
          <div class="related-courses-content">
            <div class="related-courses-divider"></div>
            <h3 class="related-courses-title">Looking for a Different Course?</h3>
            <p class="related-courses-description">
              Explore our full range of CQC-compliant, CPD-accredited courses designed to keep your team compliant, confident, and care-focused.
            </p>
            <div class="related-courses-view-all-box">
              <a href="upcoming-courses.html" class="related-courses-view-all-link primary-cta-button">
                View All Upcoming Courses
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
              </a>
            </div>
          </div>
        `;
        return;
      }

      // Generate course cards HTML
      const courseCardsHtml = relatedCourses.map(renderCourseCard).join('');

      // Render simple grid structure
      relatedEventsContainer.innerHTML = `
        <div class="related-courses-content">
          <div class="related-courses-grid">
            ${courseCardsHtml}
          </div>
          <div class="related-courses-divider"></div>
          <h3 class="related-courses-title">Looking for a Different Course?</h3>
          <div class="related-courses-view-all-box">
            <a href="upcoming-courses.html" class="related-courses-view-all-link primary-cta-button">
              View All Upcoming Courses
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M12 5l7 7-7 7"></path>
              </svg>
            </a>
          </div>
        </div>
      `;
    } catch (error) {
      if (isDevelopment) console.error('Error loading related courses:', error);
      relatedEventsContainer.innerHTML = '<p style="text-align: center; color: var(--brown-medium); padding: 40px;">Unable to load related courses.</p>';
    }
  }

})();

