// ============================================
// Courses Page - Filtering and Display Logic
// ============================================
// 
// Uses CourseDataManager for all course data operations
// Source: course-data-manager.js (centralized logic)

(function() {
  'use strict';

  // Development mode flag
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  // Ensure CourseDataManager is available
  if (!window.CourseDataManager) {
    if (isDevelopment) console.error('CourseDataManager not loaded. Make sure course-data-manager.js is loaded before courses.js');
    return;
  }

  // Get courses using centralized manager
  function getCourses() {
    return window.CourseDataManager.getCourses();
  }

  // Fallback data removed - all data comes from CourseDataManager
  // If CourseData is not available, CourseDataManager.getCourses() returns empty array
  // This ensures we always use the single source of truth

  // Use centralized manager for category mapping
  function mapUrlCategoryToKey(urlCategory) {
    return window.CourseDataManager.mapUrlCategoryToKey(urlCategory);
  }

  // Get category configuration from centralized manager
  const categories = window.CourseDataManager.getCategoryConfig();
  const iconMap = window.CourseDataManager.getCategoryIconMap();

  // State management
  let selectedCategories = new Set(); // Changed to support multiple categories
  let searchQuery = "";
  let searchTimeout = null;
  let isLoading = false;
  
  // Cache of course URLs from server-rendered cards (title -> URL mapping)
  let courseUrlCache = new Map();

  // DOM elements
  const coursesGrid = document.getElementById('courses-grid');
  const searchInput = document.getElementById('course-search');
  const clearSearchBtn = document.getElementById('clear-search');
  const resultsCount = document.getElementById('results-count');
  const resultsText = document.getElementById('results-text');
  const emptyState = document.getElementById('courses-empty-state');
  const ctaSection = document.getElementById('courses-cta-section');
  const activeFilters = document.getElementById('active-filters');
  const activeFiltersList = document.getElementById('active-filters-list');
  const clearAllFiltersBtn = document.getElementById('clear-all-filters');
  const clearFiltersEmptyBtn = document.getElementById('clear-filters-empty');
  const searchResultsHeading = document.getElementById('search-results-heading');
  const searchQueryDisplay = document.getElementById('search-query-display');
  const clearSearchResultsBtn = document.getElementById('clear-search-results');

  // Build URL cache from server-rendered course cards
  function buildCourseUrlCache() {
    courseUrlCache.clear();
    const existingCards = document.querySelectorAll('.course-card[data-course-url]');
    existingCards.forEach(card => {
      const cardTitle = card.querySelector('.course-card-title')?.textContent?.trim();
      const cardId = card.getAttribute('data-course-id');
      const cardUrl = card.getAttribute('data-course-url');
      
      if (cardUrl && !cardUrl.includes('courses.html') && !cardUrl.includes('courses/courses') && cardUrl !== '#') {
        // Store by title (normalized to lowercase for matching)
        if (cardTitle) {
          courseUrlCache.set(cardTitle.toLowerCase(), cardUrl);
        }
        // Also store by ID if available
        if (cardId) {
          courseUrlCache.set(`id:${cardId}`, cardUrl);
        }
      }
    });
    
    if (isDevelopment && courseUrlCache.size > 0) {
      console.log('Built course URL cache with', courseUrlCache.size, 'entries');
    }
  }

  // Initialize on page load
  function init() {
    // Build URL cache from server-rendered cards first
    buildCourseUrlCache();
    
    // Wait for CourseDataManager to be available (with timeout)
    let attempts = 0;
    const maxAttempts = 50; // 5 seconds max wait
    
    function tryInit() {
      if (window.CourseDataManager && window.CourseData && window.CourseData.courses) {
        // CourseDataManager is available, proceed with initialization
        // Get categories from URL parameter and map to internal keys
        const urlParams = new URLSearchParams(window.location.search);
        const categoryParams = urlParams.getAll('category'); // Support multiple
        if (categoryParams.length > 0) {
          selectedCategories = new Set(categoryParams.map(cat => mapUrlCategoryToKey(cat)));
          // Update active filter buttons - ensure this happens after DOM is ready
          setTimeout(() => {
          updateFilterButtons();
          }, 0);
        } else {
          // If no categories in URL, select "all" by default
          selectedCategories = new Set(['all']);
        }

        // Get search query from URL parameter
        const searchParam = urlParams.get('search');
        if (searchParam && searchInput) {
          searchQuery = searchParam.trim();
          searchInput.value = searchQuery;
          // Show clear button if search query exists
          if (clearSearchBtn) {
            clearSearchBtn.style.display = searchQuery ? 'block' : 'none';
          }
        }

        // Set up event listeners
        setupEventListeners();

        // Calculate and display category counts
        updateCategoryCounts();

        // Render initial courses (will include search if query exists)
        renderCourses();

        // Set up keyboard shortcut for search
        setupKeyboardShortcut();
      } else if (attempts < maxAttempts) {
        // CourseData not ready yet, try again
        attempts++;
        setTimeout(tryInit, 100);
      } else {
        // Timeout - proceed with fallback data
        if (isDevelopment) console.warn('CourseData not loaded after timeout, using fallback data');
        const urlParams = new URLSearchParams(window.location.search);
        const categoryParams = urlParams.getAll('category');
        if (categoryParams.length > 0) {
          selectedCategories = new Set(categoryParams.map(cat => mapUrlCategoryToKey(cat)));
          updateFilterButtons();
        } else {
          selectedCategories = new Set(['all']);
        }
        // Get search query from URL parameter
        const searchParam = urlParams.get('search');
        if (searchParam && searchInput) {
          searchQuery = searchParam.trim();
          searchInput.value = searchQuery;
          // Show clear button if search query exists
          if (clearSearchBtn) {
            clearSearchBtn.style.display = searchQuery ? 'block' : 'none';
          }
        }
        setupEventListeners();
        updateCategoryCounts();
        renderCourses();
        setupKeyboardShortcut();
      }
    }
    
    tryInit();
  }

  // Set up event listeners
  function setupEventListeners() {
    // Search input
    if (searchInput) {
      searchInput.addEventListener('input', handleSearch);
    }

    // Clear search button
    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', clearSearch);
    }

    // Clear search results button (in heading)
    if (clearSearchResultsBtn) {
      clearSearchResultsBtn.addEventListener('click', () => {
        clearSearch();
        // Remove search parameter from URL
        const url = new URL(window.location);
        url.searchParams.delete('search');
        window.history.replaceState({}, '', url);
      });
    }

    // Mobile filter toggle
    const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
    const filterButtonsContainer = document.getElementById('courses-filter-buttons');
    if (mobileFilterToggle && filterButtonsContainer) {
      mobileFilterToggle.addEventListener('click', () => {
        const isExpanded = mobileFilterToggle.getAttribute('aria-expanded') === 'true';
        mobileFilterToggle.setAttribute('aria-expanded', !isExpanded);
        filterButtonsContainer.setAttribute('aria-hidden', isExpanded);
        if (!isExpanded) {
          filterButtonsContainer.style.display = 'flex';
        } else {
          filterButtonsContainer.style.display = 'none';
        }
      });
    }

    // Filter buttons - support multi-select
    const filterButtons = document.querySelectorAll('.courses-filter-btn');
    filterButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const category = btn.getAttribute('data-category');
        toggleCategory(category);
        
        // Close mobile filter menu after selection (only on mobile where toggle is visible)
        if (mobileFilterToggle && filterButtonsContainer) {
          // Check if mobile toggle is visible (mobile breakpoint)
          const toggleComputedStyle = window.getComputedStyle(mobileFilterToggle);
          const isToggleVisible = toggleComputedStyle.display !== 'none';
          
          if (isToggleVisible) {
          mobileFilterToggle.setAttribute('aria-expanded', 'false');
          filterButtonsContainer.setAttribute('aria-hidden', 'true');
          filterButtonsContainer.style.display = 'none';
          }
        }
      });
    });

    // Clear all filters
    if (clearAllFiltersBtn) {
      clearAllFiltersBtn.addEventListener('click', clearAllFilters);
    }

    if (clearFiltersEmptyBtn) {
      clearFiltersEmptyBtn.addEventListener('click', clearAllFilters);
    }
  }

  // Handle search input with debouncing
  function handleSearch(e) {
    const inputValue = e.target.value.trim();
    
    // Show/hide clear button immediately
    if (inputValue) {
      clearSearchBtn.style.display = 'block';
    } else {
      clearSearchBtn.style.display = 'none';
    }
    
    // Clear existing timeout
    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }
    
    // Show loading state
    setLoadingState(true);
    
    // Debounce the actual filtering
    searchTimeout = setTimeout(() => {
      searchQuery = inputValue;
      renderCourses();
      updateActiveFilters();
      setLoadingState(false);
    }, 300);
  }

  // Clear search
  function clearSearch() {
    // Clear timeout if pending
    if (searchTimeout) {
      clearTimeout(searchTimeout);
      searchTimeout = null;
    }
    
    searchQuery = '';
    searchInput.value = '';
    clearSearchBtn.style.display = 'none';
    setLoadingState(false);
    
    // Reset to show all courses by selecting "all" category
    selectedCategories = new Set(['all']);
    updateFilterButtons();
    
    renderCourses();
    updateActiveFilters();
  }
  
  // Set loading state
  function setLoadingState(loading) {
    isLoading = loading;
    if (coursesGrid) {
      if (loading) {
        coursesGrid.classList.add('courses-grid-loading');
      } else {
        coursesGrid.classList.remove('courses-grid-loading');
      }
    }
  }

  // Toggle category (multi-select support)
  function toggleCategory(category) {
    if (category === 'all') {
      // "All" clears all other selections
      selectedCategories.clear();
    } else {
      // Toggle the category
      if (selectedCategories.has(category)) {
        selectedCategories.delete(category);
      } else {
        selectedCategories.add(category);
      }
      // If any specific category is selected, remove "all"
      if (selectedCategories.size > 0) {
        selectedCategories.delete('all');
      }
    }
    
    // If no categories selected, show all
    if (selectedCategories.size === 0) {
      selectedCategories.add('all');
    }
    
    updateFilterButtons();
    setLoadingState(true);
    
    // Small delay to show loading state
    setTimeout(() => {
      renderCourses();
      updateActiveFilters();
      setLoadingState(false);
      
      // Announce to screen readers (without moving focus)
      const announcement = document.createElement('div');
      announcement.setAttribute('role', 'status');
      announcement.setAttribute('aria-live', 'polite');
      announcement.className = 'sr-only';
      announcement.textContent = `Showing ${document.getElementById('results-count')?.textContent || '0'} courses`;
      document.body.appendChild(announcement);
      setTimeout(() => document.body.removeChild(announcement), 1000);
    }, 100);
    
    // Update URL without reload
    const url = new URL(window.location);
    url.searchParams.delete('category'); // Clear all
    if (!selectedCategories.has('all')) {
      selectedCategories.forEach(cat => {
        url.searchParams.append('category', cat);
      });
    }
    window.history.pushState({}, '', url);
  }
  
  // Legacy function for compatibility
  function selectCategory(category) {
    if (category === 'all') {
      selectedCategories.clear();
      selectedCategories.add('all');
    } else {
      selectedCategories.clear();
      selectedCategories.add(category);
    }
    toggleCategory(category);
  }

  // Update filter buttons
  function updateFilterButtons() {
    const filterButtons = document.querySelectorAll('.courses-filter-btn');
    filterButtons.forEach(btn => {
      const category = btn.getAttribute('data-category');
      // Check if this category is selected
      // First check direct match
      let isSelected = selectedCategories.has(category);
      
      // If not directly selected, check if any selected category maps to this button's category
      if (!isSelected && selectedCategories.size > 0) {
        isSelected = Array.from(selectedCategories).some(selectedCat => {
          // Map both to their canonical keys and compare
          const buttonKey = mapUrlCategoryToKey(category);
          const selectedKey = mapUrlCategoryToKey(selectedCat);
          return buttonKey === selectedKey;
        });
      }
      
      if (isSelected) {
        btn.classList.add('courses-filter-btn-active');
        btn.setAttribute('aria-pressed', 'true');
      } else {
        btn.classList.remove('courses-filter-btn-active');
        btn.setAttribute('aria-pressed', 'false');
      }
    });
  }

  // Clear all filters
  function clearAllFilters() {
    // Clear search timeout if pending
    if (searchTimeout) {
      clearTimeout(searchTimeout);
      searchTimeout = null;
    }
    
    selectedCategories.clear();
    selectedCategories.add('all');
    searchQuery = '';
    searchInput.value = '';
    clearSearchBtn.style.display = 'none';
    updateFilterButtons();
    setLoadingState(true);
    
    setTimeout(() => {
      renderCourses();
      updateActiveFilters();
      setLoadingState(false);
      
      // Screen reader announcement is handled by aria-live on results-count-container
    }, 100);
    
    // Update URL
    const url = new URL(window.location);
    url.searchParams.delete('category');
    window.history.pushState({}, '', url);
  }

  // Filter courses
  function filterCourses() {
    const courses = getCourses();
    return courses.filter(course => {
      // Match category: either "all" or course belongs to any of the selected topic categories
      let matchesCategory = selectedCategories.has("all");
      if (!matchesCategory && selectedCategories.size > 0) {
        // Get all topic categories for this course
        const topicCategories = course.topicCategories || [];
        
        // If no topic categories, course doesn't match any filter (except "all")
        if (topicCategories.length === 0) {
          return false;
        }
        
        // Check if course matches any of the selected categories by checking ALL topic categories
        matchesCategory = Array.from(selectedCategories).some(selectedCategory => {
          // Check if ANY of the course's topic categories match the selected category
          return topicCategories.some(topicCat => {
            const topicKey = window.CourseDataManager && typeof window.CourseDataManager.topicCategoryToKey === 'function'
              ? window.CourseDataManager.topicCategoryToKey(topicCat)
              : topicCat.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            return topicKey === selectedCategory;
          });
        });
      }
      
      const matchesSearch = searchQuery === "" ||
        course.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        course.description.toLowerCase().includes(searchQuery.toLowerCase());
      return matchesCategory && matchesSearch;
    });
  }

  // Calculate category counts (only count courses that would match if this category is selected alone)
  function updateCategoryCounts() {
    const courses = getCourses();
    const counts = { all: courses.length };
    
    categories.forEach(category => {
      if (category.key === 'all') return; // Skip "all" as it's already set
      
      // Count courses that belong to this topic category (check all topic categories, not just primary)
      counts[category.key] = courses.filter(course => {
        // Check primary category
        if (course.category === category.key) return true;
        
        // Check all topic categories
        if (course.topicCategories) {
          return course.topicCategories.some(topicCat => {
            const topicKey = window.CourseDataManager.topicCategoryToKey(topicCat);
            return topicKey === category.key;
          });
        }
        
        return false;
      }).length;
    });

    // Update count displays
    Object.keys(counts).forEach(key => {
      const countEl = document.getElementById(`count-${key}`);
      if (countEl) {
        countEl.textContent = `(${counts[key]})`;
      }
    });
  }

  // Helper function to get course image with fallback
  // Helper function to generate srcset from base image path
  function generateImageSrcset(basePath) {
    // Extract directory and filename
    const pathParts = basePath.split('/');
    const filename = pathParts.pop();
    const directory = pathParts.join('/');
    
    // Extract name and extension
    const nameMatch = filename.match(/^(.+?)\.(webp|jpg|png)$/);
    if (!nameMatch) return basePath; // Return original if pattern doesn't match
    
    const baseName = nameMatch[1];
    const extension = nameMatch[2];
    
    // Generate srcset with multiple sizes
    return `${directory}/${baseName}-400w.${extension} 400w, ` +
           `${directory}/${baseName}-800w.${extension} 800w, ` +
           `${directory}/${baseName}-1200w.${extension} 1200w, ` +
           `${directory}/${baseName}-1600w.${extension} 1600w`;
  }
  
  // Helper function to get base src from image path (for fallback)
  function getBaseImageSrc(imagePath) {
    const pathParts = imagePath.split('/');
    const filename = pathParts.pop();
    const directory = pathParts.join('/');
    const nameMatch = filename.match(/^(.+?)\.(webp|jpg|png)$/);
    if (!nameMatch) return imagePath;
    return `${directory}/${nameMatch[1]}-400w.${nameMatch[2]}`;
  }

  function getCourseImage(course) {
    // Normalize string for matching (trim, lowercase, collapse whitespace)
    function normalize(str) {
      if (!str) return '';
      return String(str).trim().toLowerCase().replace(/\s+/g, ' ');
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
      ],
      'Duty of Care': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/duty-of-care.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/duty-of-care2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/duty-of-care3.webp'
      ],
      'Effective Communication': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/effective-communication.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/effective-communication2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/effective-communication3.webp'
      ],
      'Equality, Diversity, Inclusion & Human Rights': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/equality-diversity-human-rights.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/equality-diversity-human-rights2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/equality-diversity-human-rights3.webp'
      ],
      'Information Handling': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/data-and-records.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/data-and-records2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/data-and-records3.webp'
      ],
      'GDPR': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/data-and-records.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/data-and-records2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/data-and-records3.webp'
      ],
      'Oral Health': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/oral-health2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/oral-health3.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/oral-health4.webp'
      ],
      'Nutrition and Hydration': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/nutrition.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/nutrition2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/nutrition3.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/nutrition4.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/nutrition5.webp'
      ],
      'Management & Leadership Skills (Level 2)': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/management-and-leadership.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/management-and-leadership2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/management-and-leadership3.webp'
      ],
      'Management & Leadership Skills (Level 3)': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/management-and-leadership.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/management-and-leadership2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/management-and-leadership3.webp'
      ],
      'Dignity Privacy & Respect': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate04.webp'
      ],
      'Person-Centred Care': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate04.webp'
      ],
      'The Role of a Care Worker & Personal Development': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate04.webp'
      ],
      'First Aid at Work': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid05.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid06.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid07.webp'
      ],
      'Paediatric First Aid': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/pediatric_first_aid01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/pediatric_first_aid02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_pediatric_first_aid05.webp'
      ],
      'Safeguarding': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/safeguarding_adults05.webp'
      ],
      'Medication Competency & Management': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/medication_awareness_training05.webp'
      ],
      'Moving & Positioning Theory': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling05.webp'
      ],
      'Moving & Positioning inc. Hoist': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling04.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/moving_and_handling05.webp'
      ],
      // Add all missing courses with unique images
      'Oxygen Therapy Awareness': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/oxygen_therapy_awareness.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/oxygen_therapy_awareness2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/oxygen_therapy_awareness3.webp'
      ],
      'Basic Health & Safety': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/basic_health_safety1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/basic_health_safety2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/basic_health_safety3.webp'
      ],
      'Care Planning': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/care_planning1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/care_planning2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/care_planning3.webp'
      ],
      'Customer Service Skills': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/customer_service_skills1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/customer_service_skills3.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/customer_service_skills4.webp'
      ],
      'Dementia Awareness': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/dementia_awareness1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/dementia_awareness2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/dementia_awareness3.webp'
      ],
      'Diabetes Awareness': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/diabetes_awareness2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/diabetes_awareness3.webp'
      ],
      'End of Life Care': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/end_of_life_care1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/end_of_life_care2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/end_of_life_care3.webp'
      ],
      'Equal Opportunities': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/equal_opportunities1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/equal_opportunities2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/equal_opportunities3.webp'
      ],
      'Fire Safety Awareness': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/fire_safety_awareness1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/fire_safety_awareness2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/fire_safety_awareness3.webp'
      ],
      'Food Safety Awareness': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/food-safety-awareness.webp'
      ],
      'Infection Prevention and Control': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/infection-prevention-control.webp'
      ],
      'Insulin Awareness': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/insulin_awareness1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/insulin_awareness2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/insulin_awareness3.webp'
      ],
      'Learning Disabilities & Autism': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/learning_disabilities_autism1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/learning_disabilities_autism2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/learning_disabilities_autism3.webp'
      ],
      'Mental Capacity Act & DoLS': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/mental_capacity_act_dols1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/mental_capacity_act_dols2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/mental_capacity_act_dols3.webp'
      ],
      'Positive Behaviour Support': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/positive_behaviour_support1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/positive_behaviour_support2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/positive_behaviour_support3.webp'
      ],
      'Tissue Viability': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/tissue_viability1.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/tissue_viability2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/tissue_viability3.webp'
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
        // Improved hash to ensure different courses get different images
        const hashInput = (course.id || course.title || '').toString();
        const hash = hashInput.split('').reduce((acc, char, idx) => acc + char.charCodeAt(0) * (idx + 1), hashInput.length);
        return images[hash % images.length];
      }
    }
    
    // Fallback to stock photo based on category with variety
    const categoryImageMap = {
      'Core Care Skills': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate04.webp'
      ],
      'Core Care': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate02.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate03.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate04.webp'
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
      ],
      'Communication & Workplace Culture': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/effective-communication.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/effective-communication2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/effective-communication3.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/equality-diversity-human-rights.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/equality-diversity-human-rights2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/equality-diversity-human-rights3.webp'
      ],
      'Information & Data Management': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/data-and-records.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/data-and-records2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/data-and-records3.webp'
      ],
      'Leadership & Professional Development': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/management-and-leadership.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/management-and-leadership2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/management-and-leadership3.webp'
      ],
      'Nutrition & Hygiene': [
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/nutrition.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/nutrition2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/nutrition3.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/nutrition4.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/nutrition5.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/oral-health2.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/oral-health3.webp',
        'assets/img/stock_photos/05_COURSE_THUMBNAILS/oral-health4.webp'
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
        // Improved hash to ensure different courses get different images
        const hashInput = (course.id || course.title || '').toString();
        const hash = hashInput.split('').reduce((acc, char, idx) => acc + char.charCodeAt(0) * (idx + 1), hashInput.length);
        return images[hash % images.length];
      }
    }
    
    // Final fallback
    return 'assets/img/stock_photos/05_COURSE_THUMBNAILS/adult_social_care_certificate01.webp';
  }

  // Render courses
  function renderCourses() {
    const filteredCourses = filterCourses();
    const count = filteredCourses.length;

    // Check if server-rendered content exists (progressive enhancement)
    const hasServerRenderedContent = coursesGrid && coursesGrid.querySelector('.course-card') !== null;
    const hasActiveFilters = (selectedCategories.size > 0 && !selectedCategories.has('all')) || searchQuery.trim() !== '';
    
    // Count server-rendered courses to determine if we need to replace with all courses
    const serverRenderedCount = hasServerRenderedContent 
      ? coursesGrid.querySelectorAll('.course-card').length 
      : 0;
    
    // If "all" is selected and filtered count differs from server-rendered count, we need to replace
    const needsFullRender = selectedCategories.has('all') && 
                            !hasActiveFilters && 
                            count !== serverRenderedCount;

    // Show/hide search results heading
    if (searchResultsHeading && searchQueryDisplay) {
      if (searchQuery && searchQuery.trim() !== '') {
        searchResultsHeading.style.display = 'block';
        searchQueryDisplay.textContent = `"${searchQuery}"`;
      } else {
        searchResultsHeading.style.display = 'none';
      }
    }

    // Update results count
    if (resultsCount) {
      resultsCount.textContent = count;
    }
    if (resultsText) {
      if (searchQuery && searchQuery.trim() !== '') {
        // Show search results message
        resultsText.textContent = ` result${count === 1 ? '' : 's'} for "${searchQuery}"`;
      } else {
        // Show regular count
      resultsText.textContent = count === 1 ? ' course' : ' courses';
      }
    }

    // Show/hide empty state
    if (count === 0) {
      if (emptyState) {
        emptyState.style.display = 'block';
        // Add suggestions to empty state
        const suggestionsEl = document.getElementById('courses-empty-suggestions');
        if (suggestionsEl) {
          const popularCategories = categories
            .filter(c => c.key !== 'all')
            .sort((a, b) => {
              const countA = parseInt(document.getElementById(`count-${a.key}`)?.textContent.replace(/[()]/g, '') || '0');
              const countB = parseInt(document.getElementById(`count-${b.key}`)?.textContent.replace(/[()]/g, '') || '0');
              return countB - countA;
            })
            .slice(0, 3);
          
          if (popularCategories.length > 0) {
            suggestionsEl.innerHTML = `
              <p class="courses-empty-suggestions-title">Popular Categories:</p>
              <div class="courses-empty-suggestions-list">
                ${popularCategories.map(cat => `
                  <button class="courses-empty-suggestion-btn" data-category="${cat.key}">
                    ${cat.title}
                  </button>
                `).join('')}
              </div>
            `;
            
            // Add event listeners to suggestion buttons
            suggestionsEl.querySelectorAll('.courses-empty-suggestion-btn').forEach(btn => {
              btn.addEventListener('click', () => {
                toggleCategory(btn.getAttribute('data-category'));
              });
            });
          } else {
            suggestionsEl.innerHTML = '';
          }
        }
      }
      if (coursesGrid) {
        coursesGrid.style.display = 'none';
        coursesGrid.classList.remove('courses-grid-loading');
      }
      if (ctaSection) ctaSection.style.display = 'none';
    } else {
      if (emptyState) emptyState.style.display = 'none';
      if (coursesGrid) {
        coursesGrid.style.display = 'grid';
        coursesGrid.classList.remove('courses-grid-loading');
      }
      if (ctaSection) ctaSection.style.display = 'block';
    }

    // Replace content if:
    // 1. Filters/search are active, OR
    // 2. No server-rendered content exists, OR
    // 3. "All" is selected and we need to show all courses (not just server-rendered subset)
    if (coursesGrid && (hasActiveFilters || !hasServerRenderedContent || needsFullRender)) {
      coursesGrid.innerHTML = filteredCourses.map(course => {
        // Get all topic categories for this course
        const topicCategories = course.topicCategories || [];
        
        // Build badges for all categories
        const categoryBadges = topicCategories.length > 0
          ? topicCategories.map(topicCategoryName => {
              // Convert topic category name to key
              const topicCategoryKey = window.CourseDataManager && typeof window.CourseDataManager.topicCategoryToKey === 'function'
                ? window.CourseDataManager.topicCategoryToKey(topicCategoryName)
                : topicCategoryName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
              
              // Find category config
              const category = categories.find(c => c.key === topicCategoryKey);
              const categoryIconClass = iconMap[topicCategoryKey] || "fa-book";
              const categoryIconHtml = `<i class="fas ${categoryIconClass} course-card-badge-icon" aria-hidden="true"></i>`;
              const badgeColor = category?.badgeColor || "course-badge-amber";
              // Always use short name - category config should always have shortName
              const categoryTitle = category?.shortName || category?.title || topicCategoryName;
              
              return `<span class="course-card-badge ${badgeColor}">${categoryIconHtml} ${categoryTitle}</span>`;
            }).join('')
          : (() => {
              // Fallback to primary category if no topic categories
              const category = categories.find(c => c.key === course.category);
              const categoryIconClass = iconMap[course.category] || "fa-book";
              const categoryIconHtml = `<i class="fas ${categoryIconClass} course-card-badge-icon" aria-hidden="true"></i>`;
              const badgeColor = category?.badgeColor || "course-badge-amber";
              const categoryTitle = category?.shortName || course.categoryName || category?.title || course.category;
              return `<span class="course-card-badge ${badgeColor}">${categoryIconHtml} ${categoryTitle}</span>`;
            })();

        const courseImage = getCourseImage(course);
        
        // Build learning outcomes list HTML
        const learningOutcomesHTML = course.learningOutcomes && course.learningOutcomes.length > 0
          ? `<ul class="course-modal-learning-outcomes">
              ${course.learningOutcomes.map(outcome => `<li>${outcome}</li>`).join('')}
            </ul>`
          : '';

        // Build who should attend HTML
        const whoShouldAttendHTML = course.whoShouldAttend
          ? `<div class="course-modal-who-should-attend">${course.whoShouldAttend}</div>`
          : '';

        const imageSrcset = generateImageSrcset(courseImage);
        const imageSrc = getBaseImageSrc(courseImage);
        
        // Price handling with sitewide discount support
        let price = course.price || 0;
        let originalPrice = course.originalPrice || price; // Use originalPrice if available, otherwise use price as original
        let hasDiscount = course.hasDiscount || false;
        let discountPercent = course.discountPercent || 0;
        
        // Extract numeric price value
        const priceNum = typeof price === 'string' ? parseFloat(price.replace(/[£$,\s]/g, '')) : price;
        const originalPriceNum = typeof originalPrice === 'string' ? parseFloat(originalPrice.replace(/[£$,\s]/g, '')) : originalPrice;
        
        // Check for site-wide discount
        const siteWideDiscount = window.ctaData && window.ctaData.siteWideDiscount ? window.ctaData.siteWideDiscount : null;
        if (siteWideDiscount && siteWideDiscount.active && siteWideDiscount.percentage > 0) {
          const siteWidePrice = originalPriceNum * (1 - (siteWideDiscount.percentage / 100));
          // Use site-wide discount if it's better than existing discount
          if (!hasDiscount || siteWidePrice < priceNum) {
            hasDiscount = true;
            discountPercent = siteWideDiscount.percentage;
            priceNum = siteWidePrice;
            price = Math.round(siteWidePrice);
          }
        }
        
        // Format price for display (ensure it's a number for display)
        const displayPrice = typeof price === 'number' ? price : priceNum;
        
        // Get course URL - prioritise course.url, then check cache, then try DOM lookup
        let courseUrl = course.url;
        
        // If no URL or invalid URL, check cache first (faster than DOM lookup)
        if (!courseUrl || courseUrl.includes('courses.html') || courseUrl.includes('courses/courses')) {
          // Try cache lookup by title (normalized)
          const cacheKey = course.title?.toLowerCase();
          if (cacheKey && courseUrlCache.has(cacheKey)) {
            courseUrl = courseUrlCache.get(cacheKey);
          } else if (course.id) {
            // Try cache lookup by ID
            const idCacheKey = `id:${course.id}`;
            if (courseUrlCache.has(idCacheKey)) {
              courseUrl = courseUrlCache.get(idCacheKey);
            }
          }
        }
        
        // If still no valid URL, try DOM lookup as fallback (for dynamically added cards)
        if (!courseUrl || courseUrl.includes('courses.html') || courseUrl.includes('courses/courses')) {
          const existingCards = document.querySelectorAll('.course-card[data-course-url]');
          for (const card of existingCards) {
            const cardTitle = card.querySelector('.course-card-title')?.textContent?.trim();
            const cardId = card.getAttribute('data-course-id');
            const cardUrl = card.getAttribute('data-course-url');
            
            // Match by title (most reliable) or by ID
            if ((cardTitle && cardTitle.toLowerCase() === course.title.toLowerCase()) || 
                (cardId && cardId === String(course.id))) {
              if (cardUrl && !cardUrl.includes('courses.html') && !cardUrl.includes('courses/courses')) {
                courseUrl = cardUrl;
                // Also add to cache for future use
                if (cardTitle) {
                  courseUrlCache.set(cardTitle.toLowerCase(), cardUrl);
                }
                if (cardId) {
                  courseUrlCache.set(`id:${cardId}`, cardUrl);
                }
                break;
              }
            }
          }
        }
        
        // If still no valid URL and we have a course ID that looks like a WordPress post ID
        if ((!courseUrl || courseUrl.includes('courses.html') || courseUrl.includes('courses/courses')) && course.id) {
          // Check if course.id is a numeric WordPress post ID
          const courseIdNum = parseInt(course.id, 10);
          if (!isNaN(courseIdNum) && courseIdNum > 0) {
            // Try WordPress permalink format: /courses/[slug]/
            // We'll use the post ID query format as fallback, but WordPress should rewrite it
            courseUrl = `/?p=${courseIdNum}`;
          } else {
            // If ID is not numeric, it might be a slug - try /courses/[id]/
            courseUrl = `/courses/${course.id}/`;
          }
        }
        
        // Final fallback - prevent invalid URLs
        if (!courseUrl || courseUrl.includes('courses.html') || courseUrl.includes('courses/courses')) {
          courseUrl = '#';
          if (isDevelopment) {
            console.warn('Could not determine course URL for:', course.title, 'ID:', course.id);
          }
        }
        
        return `
          <article class="course-card" data-course-id="${course.id}" data-course-url="${courseUrl}" data-booking-link="courses/${course.id}.html">
            <div class="course-image-wrapper">
            <img srcset="${imageSrcset}"
                 src="${imageSrc}"
                 alt="${course.title}"
                 class="course-image"
                 loading="lazy"
                 width="400"
                 height="225"
                 sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw">
            </div>
            <div class="course-card-header">
              <div class="course-card-badge-wrapper">
                ${categoryBadges}
              </div>
              <h3 class="course-card-title">${course.title}</h3>
              <p class="course-card-description">${course.description}</p>
            </div>
            <div class="course-card-content">
              <div class="course-card-meta">
                <div class="course-card-meta-item">
                  <i class="fas fa-clock course-card-meta-icon" aria-hidden="true"></i>
                  <span>${course.duration}</span>
                </div>
                <div class="course-card-meta-item">
                  <i class="fas fa-trophy course-card-meta-icon" aria-hidden="true"></i>
                  <span>${course.accreditation}</span>
                </div>
                <div class="course-card-meta-item">
                  <i class="fas fa-chart-line course-card-meta-icon" aria-hidden="true"></i>
                  <span>${course.level}</span>
                </div>
              </div>
            </div>
            <div class="course-card-footer">
              <div class="course-card-price">
                ${hasDiscount ? `
                  <div style="margin-bottom: 4px;">
                    <span class="badge badge-discount">Save ${discountPercent}%</span>
                    ${siteWideDiscount && siteWideDiscount.active ? `
                      <span class="badge badge-discount-subtle">${siteWideDiscount.label || 'Site-Wide Sale'}</span>
                    ` : ''}
                  </div>
                  <div style="display: flex; flex-direction: column; gap: 4px;">
                    <span style="text-decoration: line-through; color: #8c8f94; font-size: 14px; font-weight: 400;">
                      £${Math.round(originalPriceNum)}
                    </span>
                    <span class="course-card-price-amount" style="color: #dc3232; font-weight: 700;">
                      From £${displayPrice}
                    </span>
                  </div>
                ` : `
                  <p class="course-card-price-amount">From £${displayPrice}</p>
                `}
                <p class="course-card-price-label">per person</p>
              </div>
              <a href="${courseUrl}" class="course-read-more-btn" aria-label="Read more about ${course.title}">
                Read More
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
              </a>
            </div>
            <div class="course-full-content" style="display: none;" data-course-id="${course.id}" data-booking-link="courses/${course.id}.html">
              <div class="course-full-description">${course.description}</div>
              ${learningOutcomesHTML ? `<div class="course-full-learning-outcomes">${learningOutcomesHTML}</div>` : ''}
              ${whoShouldAttendHTML ? `<div class="course-full-who-should-attend">${whoShouldAttendHTML}</div>` : ''}
            </div>
          </article>
        `;
      }).join('');
    }
  }

  // Update active filters display
  function updateActiveFilters() {
    // Only show active filters if multiple categories selected (2+) or search is active
    // Hide if only one category is selected (button already shows it) - this fixes the "double text" issue
    const hasMultipleCategories = selectedCategories.size > 1 && !selectedCategories.has('all');
    const hasFilters = hasMultipleCategories || searchQuery !== '';
    
    if (hasFilters) {
      activeFilters.style.display = 'block';
      activeFiltersList.innerHTML = '';

      // Show badges for all selected categories (only when multiple are selected)
      if (hasMultipleCategories) {
        Array.from(selectedCategories).forEach(categoryKey => {
          if (categoryKey !== 'all') {
            const category = categories.find(c => c.key === categoryKey);
            const badge = document.createElement('span');
            badge.className = 'active-filter-badge active-filter-badge-category';
            badge.innerHTML = `
              <span>${category?.shortName || category?.title || categoryKey}</span>
              <button class="active-filter-remove" aria-label="Remove ${category?.shortName || category?.title || categoryKey} filter" data-filter="category" data-category="${categoryKey}">
                <span aria-hidden="true">✕</span>
              </button>
            `;
            badge.querySelector('.active-filter-remove').addEventListener('click', () => {
              toggleCategory(categoryKey);
            });
            activeFiltersList.appendChild(badge);
          }
        });
      }

      if (searchQuery !== '') {
        const badge = document.createElement('span');
        badge.className = 'active-filter-badge active-filter-badge-search';
        badge.innerHTML = `
          <span>Search: "${searchQuery.length > 30 ? searchQuery.substring(0, 30) + '...' : searchQuery}"</span>
          <button class="active-filter-remove" aria-label="Clear search filter" data-filter="search">
            <span aria-hidden="true">✕</span>
          </button>
        `;
        badge.querySelector('.active-filter-remove').addEventListener('click', clearSearch);
        activeFiltersList.appendChild(badge);
      }
      
      // Add copy URL button when filters are active
      const copyUrlBtn = document.createElement('button');
      copyUrlBtn.className = 'copy-filter-url-btn';
      copyUrlBtn.innerHTML = '<i class="fas fa-link" aria-hidden="true"></i> Copy URL';
      copyUrlBtn.setAttribute('aria-label', 'Copy filtered URL to clipboard');
      copyUrlBtn.addEventListener('click', () => {
        const url = new URL(window.location);
        url.searchParams.delete('category');
        if (!selectedCategories.has('all')) {
          selectedCategories.forEach(cat => {
            url.searchParams.append('category', cat);
          });
        }
        if (searchQuery) {
          url.searchParams.set('search', searchQuery);
        } else {
          url.searchParams.delete('search');
        }
        
        navigator.clipboard.writeText(url.toString()).then(() => {
          const originalText = copyUrlBtn.innerHTML;
          copyUrlBtn.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Copied!';
          copyUrlBtn.classList.add('copied');
          setTimeout(() => {
            copyUrlBtn.innerHTML = originalText;
            copyUrlBtn.classList.remove('copied');
          }, 2000);
        }).catch(() => {
          // Fallback for older browsers
          const textArea = document.createElement('textarea');
          textArea.value = url.toString();
          document.body.appendChild(textArea);
          textArea.select();
          document.execCommand('copy');
          document.body.removeChild(textArea);
          const originalText = copyUrlBtn.innerHTML;
          copyUrlBtn.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Copied!';
          copyUrlBtn.classList.add('copied');
          setTimeout(() => {
            copyUrlBtn.innerHTML = originalText;
            copyUrlBtn.classList.remove('copied');
          }, 2000);
        });
      });
      activeFiltersList.appendChild(copyUrlBtn);

      if (selectedCategory !== 'all' && searchQuery !== '') {
        clearAllFiltersBtn.style.display = 'inline-block';
      } else {
        clearAllFiltersBtn.style.display = 'none';
      }
    } else {
      activeFilters.style.display = 'none';
    }
  }

  // Set up keyboard shortcut for search
  function setupKeyboardShortcut() {
    document.addEventListener('keydown', (e) => {
      // Focus search input when user presses "/" key
      if (e.key === '/' && document.activeElement?.tagName !== 'INPUT' && document.activeElement?.tagName !== 'TEXTAREA') {
        e.preventDefault();
        if (searchInput) {
          searchInput.focus();
        }
      }
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

// ============================================
// Course Modal Functionality - Matching Team Modal
// ============================================
(function() {
  'use strict';

  const courseModal = document.getElementById('course-modal');
  const courseModalContent = document.getElementById('course-modal-content');
  let previousActiveElement = null;

  function openCourseModal(courseCard) {
    const courseId = courseCard.getAttribute('data-course-id');
    const fullContent = courseCard.querySelector('.course-full-content');
    
    if (!fullContent) {
      if (isDevelopment) console.warn('Course full content not found');
      return;
    }

    // Get course data from the card
    const title = courseCard.querySelector('.course-card-title')?.textContent || '';
    const category = courseCard.querySelector('.course-card-badge')?.textContent || '';
    const description = courseCard.querySelector('.course-card-description')?.textContent || '';
    const duration = courseCard.querySelector('.course-card-meta-item:first-child span')?.textContent || '';
    const accreditation = courseCard.querySelector('.course-card-meta-item:nth-child(2) span')?.textContent || '';
    const level = courseCard.querySelector('.course-card-meta-item:last-child span')?.textContent || '';
    const price = courseCard.querySelector('.course-card-price-amount')?.textContent || '';

    // Get full content sections
    const fullDescription = fullContent.querySelector('.course-full-description')?.innerHTML || description;
    const learningOutcomes = fullContent.querySelector('.course-full-learning-outcomes')?.innerHTML || '';
    const whoShouldAttend = fullContent.querySelector('.course-full-who-should-attend')?.innerHTML || '';

    // Store the element that opened the modal for focus return
    previousActiveElement = document.activeElement;

    // Build modal content
    let modalHTML = `
      <div class="course-modal-header">
        <h2 id="course-modal-title" class="course-modal-title">${title}</h2>
        <p class="course-modal-category">${category}</p>
        <div class="course-modal-meta">
          <div class="course-modal-meta-item">
            <i class="fas fa-clock" aria-hidden="true"></i>
            <span>${duration}</span>
          </div>
          <div class="course-modal-meta-item">
            <i class="fas fa-trophy" aria-hidden="true"></i>
            <span>${accreditation}</span>
          </div>
          <div class="course-modal-meta-item">
            <i class="fas fa-chart-line" aria-hidden="true"></i>
            <span>${level}</span>
          </div>
          <div class="course-modal-meta-item">
            <i class="fas fa-pound-sign" aria-hidden="true"></i>
            <span>${price} per person</span>
          </div>
        </div>
      </div>
      <div class="course-modal-description">
        <p>${fullDescription}</p>
      </div>
    `;

    // Add learning outcomes if available
    if (learningOutcomes) {
      modalHTML += `
        <div class="course-modal-section">
          <h3 class="course-modal-section-title">Learning Outcomes</h3>
          ${learningOutcomes}
        </div>
      `;
    }

    // Add who should attend if available
    if (whoShouldAttend) {
      modalHTML += `
        <div class="course-modal-section">
          <h3 class="course-modal-section-title">Who Should Attend</h3>
          ${whoShouldAttend}
        </div>
      `;
    }

    // Get booking link from course card or full content
    const bookingLink = courseCard.getAttribute('data-booking-link') || 
                       fullContent.getAttribute('data-booking-link') || 
                       `courses/${courseCard.getAttribute('data-course-id')}.html`;

    // Add action buttons
    modalHTML += `
      <div class="course-modal-actions">
        <button type="button" class="btn btn-primary course-modal-book-btn" data-course-title="${title}" data-course-id="${courseId}">
          Book Now
          <span aria-hidden="true">→</span>
        </button>
      </div>
    `;

    courseModalContent.innerHTML = modalHTML;
    courseModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    
    // Attach click handler to Book Now button to open enquiry form
    const bookNowBtn = courseModalContent.querySelector('.course-modal-book-btn');
    if (bookNowBtn) {
      bookNowBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const courseTitle = this.getAttribute('data-course-title') || title;
        openEnquiryModal(courseTitle);
        closeCourseModal(); // Close course modal when enquiry form opens
      });
    }

    // Focus trap
    const focusableElements = courseModal.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    if (focusableElements.length > 0) {
      focusableElements[0].focus();
    }

    // Close on Escape key
    const handleEscape = function(e) {
      if (e.key === 'Escape') {
        closeCourseModal();
        document.removeEventListener('keydown', handleEscape);
      }
    };
    document.addEventListener('keydown', handleEscape);
  }

  function closeCourseModal() {
    courseModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    
    // Return focus to the element that opened the modal
    if (previousActiveElement) {
      previousActiveElement.focus();
      previousActiveElement = null;
    }
  }

  // Attach click handlers to Read More buttons (using event delegation)
  // Navigate to course pages instead of opening modals
  document.addEventListener('click', function(e) {
    const readMoreBtn = e.target.closest('.course-read-more-btn');
    if (readMoreBtn) {
      // If it's an anchor tag, let it navigate normally
      if (readMoreBtn.tagName === 'A') {
        return; // Allow default link behavior
      }
      
      // For buttons, navigate to the course page
      e.preventDefault();
      const courseCard = readMoreBtn.closest('.course-card');
      if (courseCard) {
        // Get course URL from data attribute or construct from course ID
        const courseUrl = courseCard.getAttribute('data-course-url');
        const courseId = courseCard.getAttribute('data-course-id');
        
        if (courseUrl) {
          window.location.href = courseUrl;
        } else if (courseId) {
          // Construct WordPress permalink - try to find by post ID
          // WordPress will handle the rewrite to proper permalink
          window.location.href = `/?post_type=course&p=${courseId}`;
        }
      }
    }
  });

  // Close button
  const closeBtn = document.querySelector('.course-modal-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', closeCourseModal);
  }

  // Make functions globally available for onclick handlers
  window.openCourseModal = openCourseModal;
  window.closeCourseModal = closeCourseModal;
})();

