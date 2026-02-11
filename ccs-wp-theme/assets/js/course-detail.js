// ============================================
// Course Detail Page - Dynamic Course Loading
// ============================================
// This loads general course information (not event-specific)

(function() {
  'use strict';

  // Development mode flag
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  // Parse URL - support both old format (?id=) and new format (/courses/[slug])
  function getCourseSlugFromUrl() {
    // Check for new format: /courses/[course-title][course-level] or /courses/[slug].html
    const pathname = window.location.pathname;
    const coursesMatch = pathname.match(/\/courses\/([^\/]+)(?:\.html)?$/);
    if (coursesMatch) {
      const slug = coursesMatch[1];
      // Remove .html extension if present
      return slug.replace(/\.html$/, '');
    }
    
    // Also check if we're in a courses subdirectory
    const coursesSubdirMatch = pathname.match(/\/static-site\/courses\/([^\/]+)(?:\.html)?$/);
    if (coursesSubdirMatch) {
      return coursesSubdirMatch[1].replace(/\.html$/, '');
    }
    
    // Fallback to old format: ?id= or query parameter
  const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id') || urlParams.get('slug');
  }

  // Generate course slug from title and level
  function generateCourseSlug(course) {
    if (!course) return null;
    
    const title = course.title || '';
    const level = course.level || '';
    
    // Combine title and level, make URL-friendly
    let slug = title.toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
    
    if (level) {
      const levelSlug = level.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
      slug = `${slug}-${levelSlug}`;
    }
    
    return slug;
  }

  const courseSlug = getCourseSlugFromUrl();

  // Check if we're on WordPress (has wp-content in path or WordPress-specific elements)
  const isWordPress = window.location.pathname.includes('/wp-content/') || 
                      document.body.classList.contains('wp-admin') ||
                      (typeof wp !== 'undefined' && wp.data);

  // If no course slug, redirect to courses page
  if (!courseSlug) {
    if (isDevelopment) console.error('No course slug provided');
    // Use WordPress permalink if on WordPress, otherwise use static site path
    if (isWordPress) {
      // Get courses archive URL from WordPress
      const coursesUrl = window.location.origin + '/courses/';
      window.location.href = coursesUrl;
    } else {
      // Static site fallback
      const isInCoursesDir = window.location.pathname.includes('/courses/');
      window.location.href = isInCoursesDir ? '../courses.html' : 'courses.html';
    }
    return;
  }

  // If on WordPress, the page is already server-rendered, so don't run client-side logic
  if (isWordPress) {
    if (isDevelopment) console.log('WordPress detected - skipping client-side course loading');
    return;
  }

  // Helper function to get courses URL (WordPress or static)
  function getCoursesUrl() {
    const isWordPress = window.location.pathname.includes('/wp-content/') || 
                        (typeof wp !== 'undefined' && wp.data);
    if (isWordPress) {
      return window.location.origin + '/courses/';
    }
    const isInCoursesDir = window.location.pathname.includes('/courses/');
    return isInCoursesDir ? '../courses.html' : 'courses.html';
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  async function init() {
    // Wait for CourseDataManager and CourseData to be available
    let attempts = 0;
    const maxAttempts = 50; // 5 seconds max wait
    
    function checkDependencies() {
      if (!window.CourseDataManager) {
        if (isDevelopment) console.log('Waiting for CourseDataManager...', attempts);
        attempts++;
        if (attempts < maxAttempts) {
          setTimeout(checkDependencies, 100);
        } else {
          if (isDevelopment) console.error('CourseDataManager not loaded after', maxAttempts, 'attempts');
          showErrorState('Unable to load course data. Redirecting...');
          setTimeout(() => {
            // Use WordPress permalink if on WordPress
            const isWordPress = window.location.pathname.includes('/wp-content/') || 
                                (typeof wp !== 'undefined' && wp.data);
            if (isWordPress) {
              window.location.href = window.location.origin + '/courses/';
            } else {
              window.location.href = getCoursesUrl();
            }
          }, 2000);
        }
        return;
      }
      
      if (!window.CourseData || !window.CourseData.courses) {
        if (isDevelopment) console.log('Waiting for CourseData...', attempts);
        attempts++;
        if (attempts < maxAttempts) {
          setTimeout(checkDependencies, 100);
        } else {
          if (isDevelopment) console.error('CourseData not loaded after', maxAttempts, 'attempts');
          showErrorState('Unable to load course data. Redirecting...');
          setTimeout(() => {
            // Use WordPress permalink if on WordPress
            const isWordPress = window.location.pathname.includes('/wp-content/') || 
                                (typeof wp !== 'undefined' && wp.data);
            if (isWordPress) {
              window.location.href = window.location.origin + '/courses/';
            } else {
              window.location.href = getCoursesUrl();
            }
          }, 2000);
        }
        return;
      }
      
      // Dependencies are ready, proceed
      loadCourse();
    }
    
    async function loadCourse() {
      try {
        // Decode the slug (in case it's URL-encoded)
        const decodedSlug = decodeURIComponent(courseSlug);
        
        // Get all courses to search by slug
        const courses = window.CourseDataManager.getCourses();
        
        // Try to find course by matching generated slug
        let course = courses.find(c => {
          const generatedSlug = generateCourseSlug(c);
          return generatedSlug === decodedSlug || 
                 generatedSlug === courseSlug ||
                 decodedSlug === generateCourseSlug(c);
        });
        
        // Fallback: try by ID (for backward compatibility)
        if (!course) {
          course = window.CourseDataManager.getCourseById(decodedSlug) ||
                   window.CourseDataManager.getCourseById(courseSlug);
        }
        
        // Fallback: try by title
        if (!course) {
          const decodedTitle = decodedSlug.replace(/-level-\d+$/, '').replace(/-/g, ' ');
          course = window.CourseDataManager.getCourseByTitle(decodedTitle);
        }
        
        // Fallback: try partial matching
        if (!course) {
          course = courses.find(c => {
            const courseSlugGenerated = generateCourseSlug(c);
            return courseSlugGenerated.includes(decodedSlug) || 
                   decodedSlug.includes(courseSlugGenerated);
          });
        }
        
        if (isDevelopment) {
          console.log('Looking for course slug:', courseSlug, 'decoded:', decodedSlug);
          if (!course) {
            console.log('Total courses available:', courses.length);
            console.log('Sample generated slugs:', courses.slice(0, 5).map(c => ({
              title: c.title,
              level: c.level,
              slug: generateCourseSlug(c)
            })));
          } else {
            console.log('Course found:', course.title, 'ID:', course.id, 'slug:', generateCourseSlug(course));
          }
        }

        if (!course) {
          if (isDevelopment) {
            console.error('Course not found with slug:', courseSlug);
            const courses = window.CourseDataManager.getCourses();
            console.log('Available courses (first 10):', courses.map(c => ({ 
              id: c.id, 
              title: c.title,
              level: c.level,
              slug: generateCourseSlug(c)
            })).slice(0, 10));
          }
          showErrorState('Course not found. Redirecting to courses page...');
        setTimeout(() => {
            window.location.href = getCoursesUrl();
        }, 2000);
        return;
      }

        if (isDevelopment) {
          console.log('Course found:', course.title, course);
        }

        // Merge with raw course data to get all fields (requirements, accessibilityInfo, etc.)
        const rawCourse = window.CourseDataManager.getRawCourses().find(c => c.id === course.id) ||
                         window.CourseData?.courses?.find(c => c.id === course.id);
        
        if (rawCourse) {
          // Merge additional fields from raw course that aren't in transformed data
          course.requirements = rawCourse.requirements || course.requirements;
          course.accessibilityInfo = rawCourse.accessibilityInfo || course.accessibilityInfo;
          course.mappedTo = rawCourse.mappedTo || course.mappedTo;
          // Keep transformed accreditation but also have mappedTo available
          if (rawCourse.mappedTo && !course.mappedTo) {
            course.mappedTo = rawCourse.mappedTo;
          }
          if (rawCourse.accreditation && !course.accreditation) {
            course.accreditation = rawCourse.accreditation;
          }
          course.validFor = rawCourse.validFor || course.validFor || 'Permanent';
          course.image = rawCourse.image || course.image;
        }

      // Populate page with course data
        populatePage(course);
      
      // Initialize accordions
      initAccordions();
      
      // Load related courses (async)
        loadRelatedCourses(course).catch(err => {
        if (isDevelopment) console.error('Error loading related courses:', err);
      });
      
      // Announce to screen readers
        announceToScreenReader(`Course details loaded for ${course.title}`);
      
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
          window.location.href = 'courses.html';
      }, 2000);
    }
  }

    // Start checking for dependencies
    checkDependencies();
  }

  function showErrorState(message) {
    const hero = document.querySelector('.group-hero-section');
    if (hero) {
      const errorDiv = document.createElement('div');
      errorDiv.className = 'course-detail-error';
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
    setTimeout(() => document.body.removeChild(announcement), 1000);
  }

  // Accordion functionality is now handled by unified accordion.js

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

  function populatePage(course) {
    // Update page title
    const pageTitle = `${course.title} | Continuity of Care Services`;
    document.title = pageTitle;
    
    // Update meta description
    const metaDescription = document.querySelector('meta[name="description"]');
    const descriptionText = `Learn about ${course.title} at Continuity of Care Services. CQC-compliant, CPD-accredited training in Kent.`;
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
    const breadcrumbCurrent = document.getElementById('breadcrumb-course-name');
    if (breadcrumbCurrent) {
      breadcrumbCurrent.textContent = course.title;
    }

    // Update title
    const title = document.getElementById('course-detail-heading');
    if (title) {
      title.textContent = course.title;
    }

    // Update meta info in hero (duration and location only - no date/spots)
    const heroMetaItems = document.querySelectorAll('.course-detail-hero-meta-item span');
    if (heroMetaItems.length >= 2) {
      // First item is duration
      heroMetaItems[0].textContent = course.duration || '1 Day';
      // Second item is location
      let heroLocation = course.location || 'Maidstone, Kent';
      heroLocation = heroLocation.replace(/Maidstone Studios/g, 'The Maidstone Studios');
      heroLocation = heroLocation.replace(/Maidstone, Kent/g, 'Maidstone, Kent');
      heroMetaItems[1].textContent = heroLocation;
    } else {
      // Fallback: update by ID if structure is different
      const durationEl = document.getElementById('course-duration');
      if (durationEl) {
        durationEl.textContent = course.duration || '1 Day';
      }
    }

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
    const sidebarPrice = document.getElementById('sidebar-price');
    if (sidebarPrice) {
      // Strip existing £ symbol before adding new one
      const cleanPrice = String(course.price || '62').replace(/£/g, '');
      sidebarPrice.textContent = `From £${cleanPrice}`;
    }

    const sidebarDurationEl = document.getElementById('sidebar-duration');
    if (sidebarDurationEl) {
      sidebarDurationEl.textContent = course.duration || '1 Day';
    }
    
    const skillLevelEl = document.getElementById('sidebar-level');
    if (skillLevelEl && course.level) {
      skillLevelEl.textContent = course.level;
    }
    
    const locationEl = document.getElementById('sidebar-location');
    if (locationEl) {
      locationEl.textContent = 'The Maidstone Studios';
    }
    
    const categoryEl = document.getElementById('sidebar-category');
    if (categoryEl && window.CourseDataManager) {
      // Get category name from CourseDataManager
      const categoryConfig = window.CourseDataManager.getCategoryConfig();
      const categoryKey = course.topicCategories && course.topicCategories[0];
      if (categoryKey) {
        const category = categoryConfig.find(cat => cat.key === categoryKey);
        if (category) {
          categoryEl.textContent = category.labelFull || category.label;
        }
      }
    }

    // Hero section - no background image (matches event detail page styling)
    
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

  function getCategoryClass(course) {
    let categoryName = '';
    
    // Get category name from CourseDataManager if available
    if (window.CourseDataManager) {
      const categoryConfig = window.CourseDataManager.getCategoryConfig();
      const categoryKey = course.topicCategories && course.topicCategories[0];
      if (categoryKey) {
        const category = categoryConfig.find(cat => cat.key === categoryKey);
        if (category) {
          categoryName = category.labelFull || category.label;
    }
  }
    }
    
    // Fallback to direct category properties
    if (!categoryName) {
      categoryName = course.categoryName || course.category || (course.topicCategories && course.topicCategories[0]) || '';
    }
    
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
    
    // Check category mapping - use CourseDataManager if available
    let categoryName = '';
    if (window.CourseDataManager) {
      const categoryConfig = window.CourseDataManager.getCategoryConfig();
      const categoryKey = course.topicCategories && course.topicCategories[0];
      if (categoryKey) {
        const category = categoryConfig.find(cat => cat.key === categoryKey);
        if (category) {
          categoryName = category.labelFull || category.label;
        }
      }
    }
    
    // Fallback to direct category properties
    if (!categoryName) {
      categoryName = course.categoryName || course.category || (course.topicCategories && course.topicCategories[0]);
    }
    
    if (categoryName) {
      let images = categoryImageMap[categoryName];
      if (!images) {
        const normalizedCategory = normalize(categoryName);
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
      // Get all courses from CourseDataManager
      if (!window.CourseDataManager) {
        return;
          }

      const allCourses = window.CourseDataManager.getCourses();
      
      // Get current course's category
      const currentCategory = currentCourse.topicCategories && currentCourse.topicCategories[0];
      
      // Filter courses by same category, excluding current course
      let relatedCourses = allCourses.filter(course => {
        if (course.id === currentCourse.id) return false;
        const courseCategory = course.topicCategories && course.topicCategories[0];
        return courseCategory === currentCategory;
      });
      
      // If no courses in same category, fall back to other categories
      if (relatedCourses.length === 0) {
        relatedCourses = allCourses.filter(course => course.id !== currentCourse.id);
      }
      
      // Limit to 3 courses
      if (relatedCourses.length > 3) {
        relatedCourses = relatedCourses.slice(0, 3);
      }

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
              <a href="${getCoursesUrl()}" class="related-courses-view-all-link primary-cta-button">
                View All Courses
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
              </a>
            </div>
          </div>
        `;
        return;
      }

      // Helper function to generate image srcset
      function generateImageSrcset(basePath) {
        if (!basePath || !basePath.includes('05_COURSE_THUMBNAILS')) return '';
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

      // Helper function to get base src from image path
      function getBaseImageSrc(imagePath) {
        if (!imagePath || !imagePath.includes('05_COURSE_THUMBNAILS')) return imagePath || '';
        const pathParts = imagePath.split('/');
        const filename = pathParts.pop();
        const directory = pathParts.join('/');
        const nameMatch = filename.match(/^(.+?)\.(webp|jpg|png)$/);
        if (!nameMatch) return imagePath;
        return `${directory}/${nameMatch[1]}-400w.${nameMatch[2]}`;
      }

      // Generate course cards HTML
      const courseCardsHtml = relatedCourses.map(course => {
        const categoryConfig = window.CourseDataManager.getCategoryConfig();
        const categoryKey = course.topicCategories && course.topicCategories[0];
        const category = categoryConfig.find(cat => cat.key === categoryKey);
        const categoryName = category ? (category.labelFull || category.label) : 'Course';
        const categoryClass = getCategoryClass(course);
        const courseImage = getCourseImage(course);
        const imageSrcset = generateImageSrcset(courseImage);
        const imageSrc = getBaseImageSrc(courseImage);
        
        // Generate course URL slug
        const relatedTitle = course.title || '';
        const relatedLevel = course.level || '';
        let relatedSlug = relatedTitle.toLowerCase()
                          .replace(/[^a-z0-9]+/g, '-')
                          .replace(/^-|-$/g, '');
        if (relatedLevel) {
          const relatedLevelSlug = relatedLevel.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
          relatedSlug = `${relatedSlug}-${relatedLevelSlug}`;
        }
        const relatedUrl = `courses/${relatedSlug}.html`;

        return `
          <article class="course-card" onclick="window.location.href='${relatedUrl}'" style="cursor: pointer;">
            ${courseImage ? `
            <img srcset="${imageSrcset}"
                   src="${imageSrc}"
                   alt="${course.title}"
                 class="course-image"
                   loading="lazy"
                 width="400"
                 height="225"
                 sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw">
            ` : ''}
            <div class="course-card-header">
              ${categoryName ? `<span class="course-card-badge">${categoryName}</span>` : ''}
              <h3 class="course-card-title">${course.title}</h3>
              <p class="course-card-description">${course.description || ''}</p>
              </div>
            <div class="course-card-content">
              <div class="course-card-meta">
                <div class="course-card-meta-item">
                  <i class="fas fa-clock" aria-hidden="true"></i>
                  <span>${course.duration || '1 Day'}</span>
            </div>
                <div class="course-card-meta-item">
                  <i class="fas fa-trophy" aria-hidden="true"></i>
                  <span>${course.accreditation || 'CPD'}</span>
                </div>
                <div class="course-card-meta-item">
                  <i class="fas fa-chart-line" aria-hidden="true"></i>
                  <span>${course.level || 'Basic'}</span>
                </div>
              </div>
                </div>
            <div class="course-card-footer">
              <div class="course-card-price">
                <p class="course-card-price-amount">From £${String(course.price || '62').replace(/£/g, '')}</p>
                <p class="course-card-price-label">per person</p>
              </div>
            </div>
          </article>
        `;
      }).join('');

      // Render simple grid structure
      relatedEventsContainer.innerHTML = `
        <div class="related-courses-content">
          <div class="related-courses-grid">
            ${courseCardsHtml}
          </div>
          <div class="related-courses-divider"></div>
          <h3 class="related-courses-title">Looking for a Different Course?</h3>
          <div class="related-courses-view-all-box">
            <a href="courses.html" class="related-courses-view-all-link primary-cta-button">
              View All Courses
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
