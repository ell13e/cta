/**
 * Centralized Course Data Manager
 * 
 * This is the SINGLE SOURCE OF LOGIC for all course data operations.
 * All course data transformations, mappings, and utilities are centralized here.
 * 
 * Source: Company_Information/courses_complete.csv
 * Generated: DO NOT EDIT MANUALLY - Run: node tools/csv-to-courses.js && node tools/generate-course-modules.js
 */

(function() {
  'use strict';

  // Development mode flag
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  // ============================================
  // Category Mapping Logic
  // ============================================

  /**
   * Convert topic category name to URL-friendly key
   */
  function topicCategoryToKey(topicCategory) {
    return topicCategory
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
  }

  /**
   * Get the primary topic category for a course (first one in the list)
   */
  function getPrimaryTopicCategory(topicCategories) {
    if (!topicCategories || topicCategories.length === 0) {
      return 'other';
    }
    return topicCategoryToKey(topicCategories[0]);
  }

  /**
   * Map URL category parameter to topic category key
   * Supports both old URL parameters and new topic category keys
   */
  function mapUrlCategoryToKey(urlCategory) {
    // Map old URL parameters to topic category keys
    const legacyUrlMap = {
      'core-health-social-care': 'core-care-skills',
      'first-aid-emergency-response': 'emergency-first-aid',
      'specialist-health-awareness': 'health-conditions-specialist-care',
      'medication-clinical-skills': 'medication-management',
      'manual-handling-workplace-safety': 'safety-compliance',
      'safeguarding-protection': 'safety-compliance',
      // Old internal keys (for backward compatibility)
      'care': 'core-care-skills',
      'emergency-first-aid': 'emergency-first-aid',
      'specialist-care': 'health-conditions-specialist-care',
      'medication': 'medication-management',
      'health-safety': 'safety-compliance',
      'safeguarding': 'safety-compliance',
      'person-centred-care': 'communication-workplace-culture',
      'mental-health': 'health-conditions-specialist-care'
    };
    
    // If it's a legacy URL, map it
    if (legacyUrlMap[urlCategory]) {
      return legacyUrlMap[urlCategory];
    }
    
    // Otherwise, use as-is (should be a topic category key)
    return urlCategory || 'all';
  }

  // ============================================
  // Accreditation Simplification Logic
  // ============================================

  /**
   * Simplify accreditation string - extract the most relevant accreditation
   */
  function simplifyAccreditation(accreditation) {
    if (!accreditation) return '';
    
    // If it contains pipe separators, split and find the best one
    if (accreditation.includes('|')) {
      const parts = accreditation.split('|').map(p => p.trim()).filter(p => p);
      
      // Priority order: Care Certificate > Skills for Care > Skills for Health > Others
      const careCert = parts.find(p => p.includes('Care Certificate'));
      if (careCert) return careCert;
      
      const skillsForCare = parts.find(p => p.includes('Skills for Care'));
      if (skillsForCare) return skillsForCare;
      
      const skillsForHealth = parts.find(p => p.includes('Skills for Health'));
      if (skillsForHealth) {
        // Simplify Skills for Health to just "Skills for Health"
        return 'Skills for Health';
      }
      
      // If none of the above, take the first one (usually the most important)
      return parts[0];
    }
    
    // If it's a long Skills for Health string, simplify it
    if (accreditation.includes('Skills for Health UK Core Skills Training Framework')) {
      return 'Skills for Health';
    }
    
    return accreditation;
  }

  // ============================================
  // Course Data Transformation
  // ============================================

  /**
   * Transform raw course data from CSV to standardized format for courses.js
   * Uses topic categories directly
   */
  function transformCourseForDisplay(course) {
    const topicCategories = course.topicCategories || [];
    const primaryCategory = getPrimaryTopicCategory(topicCategories);
    
    return {
      id: course.id,
      title: course.title,
      category: primaryCategory, // Use topic category key directly
      categoryName: topicCategories[0] || 'Other', // Display name of primary category
      topicCategories: topicCategories, // All topic categories
      description: course.description,
      duration: course.duration + (course.hours ? ` (${course.hours})` : ''),
      price: course.price,
      level: course.level,
      accreditation: simplifyAccreditation(course.accreditation || course.mappedTo || ''),
      validFor: course.validFor || 'Permanent',
      location: course.location,
      trainers: course.trainers || [],
      featured: course.featured || false,
      learningOutcomes: course.learningOutcomes || [],
      whoShouldAttend: course.whoShouldAttend || course.whoCanJoin || '',
      topics: topicCategories, // Alias for backward compatibility
      industrySectors: course.industrySectors || []
    };
  }

  /**
   * Get all courses in standardized format
   */
  function getCourses() {
    if (!window.CourseData || !window.CourseData.courses || window.CourseData.courses.length === 0) {
      if (isDevelopment) console.warn('CourseData not available. Make sure courses-data.js is loaded before this script.');
      return [];
    }
    
    return window.CourseData.courses.map(transformCourseForDisplay);
  }

  /**
   * Get course by ID
   */
  function getCourseById(id) {
    const courses = getCourses();
    return courses.find(c => c.id === id) || null;
  }

  // Cache for loaded JSON database
  let jsonDatabaseLoaded = false;
  let jsonDatabaseLoading = false;

  /**
   * Get the theme base URL for loading JSON files
   * Constructs path from script location or uses wp_localize_script data
   */
  function getThemeDataUrl(filename) {
    // Check if theme URL was passed via wp_localize_script
    if (window.ccsThemeData && window.ccsThemeData.themeUri) {
      return window.ccsThemeData.themeUri + '/assets/data/' + filename;
    }
    
    // Fallback: construct from script location
    // Find the script tag that loaded this file
    const scripts = document.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
      const src = scripts[i].src;
      if (src && src.includes('course-data-manager.js')) {
        // Extract base path: /wp-content/themes/ccs-wp-theme/assets/js/data/course-data-manager.js
        // Need: /wp-content/themes/ccs-wp-theme/assets/data/courses-database.json
        const basePath = src.substring(0, src.lastIndexOf('/assets/js/data/'));
        return basePath + '/assets/data/' + filename;
      }
    }
    
    // Last resort: relative path (may not work in all contexts)
    return 'assets/data/' + filename;
  }

  /**
   * Load course database from JSON file and merge with existing CourseData
   * JSON file takes precedence (it's the source of truth)
   */
  async function ensureCourseDatabaseLoaded() {
    // If already loaded or loading, return
    if (jsonDatabaseLoaded || jsonDatabaseLoading) {
      return;
    }
    
    jsonDatabaseLoading = true;
    
    // Try loading from JSON file
    try {
      const jsonUrl = getThemeDataUrl('courses-database.json');
      const response = await fetch(jsonUrl);
      if (response.ok) {
        const database = await response.json();
        // Ensure CourseData exists
        if (!window.CourseData) window.CourseData = {};
        
        // Merge: JSON takes precedence (it's the updated source)
        if (window.CourseData.courseDatabase) {
          // Deep merge: JSON fields override existing ones
          Object.keys(database).forEach(title => {
            if (window.CourseData.courseDatabase[title]) {
              // Merge individual course objects, JSON takes precedence
              window.CourseData.courseDatabase[title] = {
                ...window.CourseData.courseDatabase[title],
                ...database[title]
              };
            } else {
              // New course from JSON
              window.CourseData.courseDatabase[title] = database[title];
            }
          });
        } else {
          // No existing database, use JSON directly
          window.CourseData.courseDatabase = database;
        }
        
        jsonDatabaseLoaded = true;
      }
    } catch (error) {
      if (isDevelopment) console.warn('Could not load courses-database.json:', error);
    } finally {
      jsonDatabaseLoading = false;
    }
  }
  
  // Load database on initialization (non-blocking)
  if (typeof window !== 'undefined') {
    ensureCourseDatabaseLoaded();
  }

  /**
   * Get course by title (for calendar matching)
   */
  function getCourseByTitle(title) {
    // Ensure database exists (async load happens in background)
    if (!window.CourseData || !window.CourseData.courseDatabase) {
      // Trigger async load but return null for now
      ensureCourseDatabaseLoaded();
      return null;
    }
    const result = window.CourseData.courseDatabase[title] || null;
    return result;
  }

  /**
   * Get courses by category
   */
  function getCoursesByCategory(categoryKey) {
    const courses = getCourses();
    if (categoryKey === 'all') {
      return courses;
    }
    return courses.filter(c => c.category === categoryKey);
  }

  /**
   * Search courses by query
   */
  function searchCourses(query) {
    const courses = getCourses();
    if (!query || query.trim() === '') {
      return courses;
    }
    
    const lowerQuery = query.toLowerCase();
    return courses.filter(course => 
      course.title.toLowerCase().includes(lowerQuery) ||
      course.description.toLowerCase().includes(lowerQuery) ||
      course.topics.some(topic => topic.toLowerCase().includes(lowerQuery))
    );
  }

  // ============================================
  // Category Configuration
  // ============================================

  /**
   * Get category configuration for courses page
   * Uses topic categories directly from CSV
   */
  function getCategoryConfig() {
    // Get unique topic categories from course data
    const courses = window.CourseData?.courses || [];
    const topicCategorySet = new Set();
    
    courses.forEach(course => {
      if (course.topicCategories && Array.isArray(course.topicCategories)) {
        course.topicCategories.forEach(cat => topicCategorySet.add(cat));
      }
    });
    
    // Define badge colors and short names for each topic category
    const categoryMetadata = {
      'Core Care Skills': { 
        badgeColor: 'course-badge-blue',
        shortName: 'Core Care'
      },
      'Emergency & First Aid': { 
        badgeColor: 'course-badge-red',
        shortName: 'First Aid'
      },
      'Medication Management': { 
        badgeColor: 'course-badge-purple',
        shortName: 'Medication'
      },
      'Safety & Compliance': { 
        badgeColor: 'course-badge-orange',
        shortName: 'Safety'
      },
      'Health Conditions & Specialist Care': { 
        badgeColor: 'course-badge-teal',
        shortName: 'Specialist Health'
      },
      'Communication & Workplace Culture': { 
        badgeColor: 'course-badge-pink',
        shortName: 'Communication'
      },
      'Information & Data Management': { 
        badgeColor: 'course-badge-amber',
        shortName: 'Data & Records'
      },
      'Nutrition & Hygiene': { 
        badgeColor: 'course-badge-green',
        shortName: 'Nutrition'
      },
      'Leadership & Professional Development': { 
        badgeColor: 'course-badge-indigo',
        shortName: 'Leadership'
      }
    };
    
    // Convert to array and sort
    const categories = Array.from(topicCategorySet).sort().map(categoryName => {
      const metadata = categoryMetadata[categoryName] || { badgeColor: 'course-badge-amber', shortName: categoryName };
      return {
      key: topicCategoryToKey(categoryName),
      title: categoryName,
        shortName: metadata.shortName,
        badgeColor: metadata.badgeColor
      };
    });
    
    // Add "All Courses" at the beginning
    return [
      { key: 'all', title: 'All Courses', shortName: 'All', badgeColor: 'course-badge-amber' },
      ...categories
    ];
  }

  /**
   * Get Font Awesome icon map for topic categories
   * Returns Font Awesome class names for consistent icon usage across the site
   */
  function getCategoryIconMap() {
    return {
      'core-care-skills': 'fa-heart',
      'emergency-first-aid': 'fa-first-aid',
      'medication-management': 'fa-pills',
      'safety-compliance': 'fa-shield-alt',
      'health-conditions-specialist-care': 'fa-stethoscope',
      'communication-workplace-culture': 'fa-users',
      'information-data-management': 'fa-database',
      'nutrition-hygiene': 'fa-apple-alt',
      'leadership-professional-development': 'fa-user-tie',
      'all': 'fa-th-large'
    };
  }

  /**
   * Get icon HTML for a category
   * @param {string} categoryKey - The category key (e.g., 'core-care-skills')
   * @param {string} extraClasses - Additional CSS classes to add
   * @returns {string} HTML string for the icon
   */
  function getCategoryIconHtml(categoryKey, extraClasses = '') {
    const iconMap = getCategoryIconMap();
    const iconClass = iconMap[categoryKey] || 'fa-book';
    const classes = `fas ${iconClass}${extraClasses ? ' ' + extraClasses : ''}`;
    return `<i class="${classes}" aria-hidden="true"></i>`;
  }

  /**
   * Get display categories (for calendar and other uses)
   * Returns topic categories directly
   */
  function getDisplayCategories() {
    // Get unique topic categories from course data
    const courses = window.CourseData?.courses || [];
    const topicCategorySet = new Set();
    
    courses.forEach(course => {
      if (course.topicCategories && Array.isArray(course.topicCategories)) {
        course.topicCategories.forEach(cat => topicCategorySet.add(cat));
      }
    });
    
    // Convert to sorted array
    const categories = Array.from(topicCategorySet).sort();
    
    // Add "All Courses" at the beginning
    return ["All Courses", ...categories];
  }

  // ============================================
  // Calendar Integration
  // ============================================

  /**
   * Merge scheduled course with database course details (for calendar)
   */
  function mergeCourseDataForCalendar(scheduledCourse) {
    // Ensure database is loaded
    ensureCourseDatabaseLoaded();
    
    const dbCourse = getCourseByTitle(scheduledCourse.title);
    
    // Also look up course from CourseData.courses to get topicCategories
    // courseDatabase doesn't have topicCategories, but CourseData.courses does
    let courseFromCourses = null;
    if (window.CourseData && window.CourseData.courses && Array.isArray(window.CourseData.courses)) {
      courseFromCourses = window.CourseData.courses.find(c => c.title === scheduledCourse.title);
    }
    
    if (dbCourse) {
      // Price logic: Use custom event price if set, otherwise use course price
      // Store both original and custom prices for discount display
      const originalPrice = dbCourse.price || 0;
      let customPrice = null;
      let formattedPrice = `£${originalPrice}`;
      
      // Check if scheduled course has a custom price (from event_price field)
      if (scheduledCourse.price) {
        const scheduledPrice = typeof scheduledCourse.price === 'string' 
          ? parseFloat(scheduledCourse.price.replace(/[£$,\s]/g, '')) 
          : scheduledCourse.price;
        const originalPriceNum = parseFloat(originalPrice);
        
        // If scheduled price is different (and lower), it's a custom/discounted price
        if (scheduledPrice && scheduledPrice !== originalPriceNum && scheduledPrice < originalPriceNum) {
          customPrice = scheduledPrice;
          formattedPrice = typeof scheduledCourse.price === 'string' ? scheduledCourse.price : `£${scheduledPrice}`;
        } else if (scheduledPrice && scheduledPrice !== originalPriceNum) {
          // Custom price that's higher (use it anyway)
          customPrice = scheduledPrice;
          formattedPrice = typeof scheduledCourse.price === 'string' ? scheduledCourse.price : `£${scheduledPrice}`;
        } else {
          // Use scheduled price as-is (might be same as original)
          formattedPrice = typeof scheduledCourse.price === 'string' ? scheduledCourse.price : `£${scheduledPrice || originalPrice}`;
        }
      }
      
      // Get topicCategories from CourseData.courses (has full topicCategories array)
      // Fallback to dbCourse.category converted to array, then scheduledCourse.topicCategories
      let topicCategories = null;
      if (courseFromCourses && courseFromCourses.topicCategories && Array.isArray(courseFromCourses.topicCategories)) {
        topicCategories = courseFromCourses.topicCategories;
      } else if (dbCourse.category) {
        // Convert single category to array
        topicCategories = [dbCourse.category];
      } else if (scheduledCourse.topicCategories && Array.isArray(scheduledCourse.topicCategories)) {
        topicCategories = scheduledCourse.topicCategories;
      } else {
        topicCategories = [];
      }
      
      // Apply site-wide discount if active
      let finalPrice = customPrice !== null ? customPrice : originalPrice;
      let finalHasDiscount = customPrice !== null && customPrice < originalPrice;
      let finalDiscountPercent = finalHasDiscount ? Math.round((1 - (customPrice / originalPrice)) * 100) : 0;
      
      // Check for site-wide discount
      const siteWideDiscount = window.ccsData && window.ccsData.siteWideDiscount ? window.ccsData.siteWideDiscount : null;
      if (siteWideDiscount && siteWideDiscount.active && siteWideDiscount.percentage > 0) {
        const siteWidePrice = originalPrice * (1 - (siteWideDiscount.percentage / 100));
        // Use site-wide discount if it's better than custom discount
        if (!finalHasDiscount || siteWidePrice < finalPrice) {
          finalPrice = siteWidePrice;
          finalHasDiscount = true;
          finalDiscountPercent = siteWideDiscount.percentage;
        }
      }
      
      const merged = {
        ...scheduledCourse,
        description: dbCourse.description,
        accreditation: simplifyAccreditation(dbCourse.accreditation || dbCourse.mappedTo || ''),
        trainers: dbCourse.trainers,
        learningOutcomes: dbCourse.learningOutcomes,
        image: dbCourse.image || scheduledCourse.image,
        price: `£${Math.round(finalPrice)}`,
        originalPrice: originalPrice, // Store original price for discount calculation
        customPrice: customPrice, // Store custom price if different
        hasDiscount: finalHasDiscount, // Flag for discount display
        discountPercent: finalDiscountPercent,
        siteWideDiscount: siteWideDiscount && siteWideDiscount.active ? siteWideDiscount : null,
        duration: dbCourse.duration || scheduledCourse.duration,
        level: dbCourse.level || scheduledCourse.level,
        category: dbCourse.category || scheduledCourse.category,
        categoryName: dbCourse.category || (topicCategories && topicCategories[0]) || scheduledCourse.category,
        topicCategories: topicCategories,
        location: "Maidstone Studios", // All upcoming courses are hosted at Maidstone Studios
        requirements: dbCourse.requirements,
        accessibilityInfo: dbCourse.accessibilityInfo,
        whoShouldAttend: dbCourse.whoShouldAttend || scheduledCourse.whoShouldAttend,
        spotsLeft: scheduledCourse.spotsLeft !== undefined ? scheduledCourse.spotsLeft : 12, // Preserve spotsLeft from scheduled course
        // Explicitly preserve original date format for ID generation (must match scheduled-courses.json format exactly)
        date: scheduledCourse.date, // Ensure date format matches scheduled course exactly
        originalDate: scheduledCourse.date // Also preserve as originalDate for backwards compatibility
      };
      return merged;
    }
    
    // If no database match, try to get topicCategories from CourseData.courses
    let topicCategories = scheduledCourse.topicCategories || [];
    if (!topicCategories || topicCategories.length === 0) {
      if (courseFromCourses && courseFromCourses.topicCategories && Array.isArray(courseFromCourses.topicCategories)) {
        topicCategories = courseFromCourses.topicCategories;
      } else if (scheduledCourse.category) {
        topicCategories = [scheduledCourse.category];
      }
    }
    
    const fallback = {
      ...scheduledCourse,
      location: scheduledCourse.location || "Maidstone Studios",
      topicCategories: topicCategories
    };
    return fallback;
  }

  // ============================================
  // Public API
  // ============================================

  window.CourseDataManager = {
    // Data access
    getCourses,
    getCourseById,
    getCourseByTitle,
    getCoursesByCategory,
    searchCourses,
    
    // Category utilities
    topicCategoryToKey,
    getPrimaryTopicCategory,
    mapUrlCategoryToKey,
    getCategoryConfig,
    getCategoryIconMap,
    getCategoryIconHtml,
    getDisplayCategories,
    
    // Transformation utilities
    transformCourseForDisplay,
    simplifyAccreditation,
    
    // Calendar integration
    mergeCourseDataForCalendar,
    
    // Direct access to raw data (if needed)
    getRawCourses: function() {
      return window.CourseData?.courses || [];
    },
    getRawCourseDatabase: function() {
      return window.CourseData?.courseDatabase || {};
    }
  };

})();

