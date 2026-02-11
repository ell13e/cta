// ============================================
// Continuity of Care Services - JavaScript
// Translated from React version to vanilla JS
// ============================================

(function() {
  'use strict';

  // ============================================
  // Suppress Browser Extension Errors
  // ============================================
  // These errors are caused by browser extensions (ad blockers, password managers, etc.)
  // They are harmless and don't affect functionality, but clutter the console
  // Suppress them to keep the console clean during development
  // MUST be at the top to catch errors early

  const handleError = function(event) {
    const errorMessage = event.message || event.error?.message || event.error?.toString() || '';
    const errorStack = event.error?.stack || event.filename || '';
    const fullErrorText = (errorMessage + ' ' + errorStack).toLowerCase();
    
    const isExtensionError =
      fullErrorText.includes('message channel closed') ||
      fullErrorText.includes('asynchronous response') ||
      fullErrorText.includes('listener indicated an asynchronous response') ||
      fullErrorText.includes('extension context invalidated') ||
      fullErrorText.includes('receiving end does not exist') ||
      fullErrorText.includes('chrome-extension://') ||
      fullErrorText.includes('moz-extension://') ||
      fullErrorText.includes('safari-extension://');

    if (isExtensionError) {
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();
      return false;
    }
  };

  const handleUnhandledRejection = function(event) {
    const errorMessage = event.reason?.message || event.reason?.toString() || String(event.reason || '');
    const errorStack = event.reason?.stack || '';
    const fullErrorText = (errorMessage + ' ' + errorStack).toLowerCase();
    
    const isExtensionError =
      fullErrorText.includes('message channel closed') ||
      fullErrorText.includes('asynchronous response') ||
      fullErrorText.includes('listener indicated an asynchronous response') ||
      fullErrorText.includes('extension context invalidated') ||
      fullErrorText.includes('receiving end does not exist') ||
      fullErrorText.includes('chrome-extension://') ||
      fullErrorText.includes('moz-extension://') ||
      fullErrorText.includes('safari-extension://');

    if (isExtensionError) {
      event.preventDefault();
      return false;
    }
  };

  // Add error listeners with capture phase to catch errors early
  // Use capture phase and passive:false to ensure we can prevent default
  window.addEventListener('error', handleError, { capture: true, passive: false });
  window.addEventListener('unhandledrejection', handleUnhandledRejection, { capture: true, passive: false });

  // ============================================
  // Suppress Console Warnings from WordPress Core
  // ============================================
  // WordPress core (admin-bar.min.js) uses non-passive touchstart listeners
  // This is a performance warning, not an error, and can't be fixed in the theme
  // Suppress it to keep the console clean

  if (typeof console !== 'undefined' && console.warn) {
    const originalWarn = console.warn;
    console.warn = function(...args) {
      const message = args.join(' ').toLowerCase();
      
      // Suppress WordPress admin bar passive listener warnings
      const isWordPressWarning =
        message.includes('[violation]') &&
        (message.includes('non-passive event listener') ||
         message.includes('scroll-blocking') ||
         message.includes('touchstart') ||
         message.includes('admin-bar.min.js'));
      
      if (!isWordPressWarning) {
        originalWarn.apply(console, args);
      }
    };
  }

  // Development mode flag - set to false in production
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
  
  // Create placeholder functions immediately so onclick handlers don't fail
  // These will be replaced with actual implementations below
  // Placeholders that actually work by finding and toggling the menu
  window.toggleMobileMenu = function() {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    if (!menu || !button) return;
    
    const isOpen = menu.classList.contains('active');
    if (isOpen) {
      menu.classList.remove('active');
      button.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    } else {
      menu.classList.add('active');
      button.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    }
  };
  window.closeMobileMenu = function() {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    if (menu) {
      menu.classList.remove('active');
      document.body.style.overflow = '';
    }
    if (button) {
      button.setAttribute('aria-expanded', 'false');
    }
  };
  window.openMobileMenu = function() {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    if (menu) {
      menu.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    if (button) {
      button.setAttribute('aria-expanded', 'true');
    }
  };

  // ============================================
  // State Management
  // ============================================

  // Search modal state - controls visibility of full-screen search overlay
  // Desktop dropdown states - separate state for each expandable menu
  // These are controlled by both hover and keyboard interactions
  // Mobile menu states - controls slide-in navigation drawer
  // These refs store timeout IDs to create a 150ms delay before closing dropdowns
  // This prevents dropdowns from closing immediately when mouse moves between trigger and menu
  const state = {
    isSearchOpen: false,
    isCoursesOpen: false,
    isGroupTrainingOpen: false,
    isResourcesOpen: false,
    isMobileMenuOpen: false,
    isMobileCoursesOpen: false,
    isMobileGroupTrainingOpen: false,
    isMobileResourcesOpen: false,
    coursesTimeout: null,
    groupTrainingTimeout: null,
    resourcesTimeout: null,
    focusableElements: [],
    lastFocusedElement: null
  };

  // ============================================
  // DOM References
  // ============================================

  const searchButton = document.getElementById('search-button');
  const searchModal = document.getElementById('search-modal');
  const searchModalContainer = searchModal?.querySelector('.search-modal-container');
  const searchInputRef = document.getElementById('search-modal-input');
  const searchModalClose = document.getElementById('search-modal-close');
  const searchAutocomplete = document.getElementById('search-autocomplete');
  let autocompleteTimeout = null;

  const coursesButtonRef = document.getElementById('courses-link');
  const coursesDropdownRef = document.getElementById('courses-dropdown');
  const coursesCloseBtn = coursesDropdownRef?.querySelector('.mega-menu-close');
  const coursesDropdownWrapper = coursesButtonRef?.closest('.nav-item-dropdown');
  const groupTrainingButtonRef = document.getElementById('group-training-link');
  const groupTrainingDropdownRef = document.getElementById('group-training-dropdown');
  const groupTrainingDropdownWrapper = groupTrainingButtonRef?.closest('.nav-item-dropdown');
  const resourcesButtonRef = document.getElementById('resources-link');
  const resourcesDropdownRef = document.getElementById('resources-dropdown');
  const resourcesDropdownWrapper = resourcesButtonRef?.closest('.nav-item-dropdown');
  const mainContent = document.querySelector('main') || document.body;

  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  const mobileMenuRef = document.getElementById('mobile-menu-content');
  const mobileCoursesAccordion = document.getElementById('mobile-courses-accordion');
  const mobileCoursesContent = document.getElementById('mobile-courses-content');
  const mobileGroupTrainingAccordion = document.getElementById('mobile-group-training-accordion');
  const mobileGroupTrainingContent = document.getElementById('mobile-group-training-content');
  const mobileResourcesAccordion = document.getElementById('mobile-resources-accordion');
  const mobileResourcesContent = document.getElementById('mobile-resources-content');

  // ============================================
  // Search Modal Functions
  // ============================================

  // Modal animates in from top with fade-in for smooth appearance (duration: 200-300ms)
  // Click on backdrop closes modal, clicking inside modal content doesn't propagate
  // ESC key closes modal via event listener
  function openSearchModal() {
    state.isSearchOpen = true;
    if (searchModal) {
      searchModal.classList.add('active');
      searchModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      
      // Auto-focuses search input when modal opens for immediate interaction
      setTimeout(() => {
        if (searchInputRef) {
          searchInputRef.focus();
        }
      }, 100);
    }
  }

  function closeSearchModal() {
    state.isSearchOpen = false;
    
    // Blur search input first to dismiss mobile keyboard
    if (searchInputRef) {
      searchInputRef.blur();
      // Clear search input after blur to ensure keyboard dismisses
      searchInputRef.value = '';
    }
    
    if (searchModal) {
      searchModal.classList.remove('active');
      searchModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }
    
    // Hide autocomplete
    if (searchAutocomplete) {
      searchAutocomplete.style.display = 'none';
      searchAutocomplete.innerHTML = '';
    }
    
    // Clear autocomplete timeout
    if (autocompleteTimeout) {
      clearTimeout(autocompleteTimeout);
      autocompleteTimeout = null;
    }
    
    // Return focus to search button (desktop only to prevent mobile keyboard from reopening)
    // Only focus on desktop - on mobile/tablet, blurring the input is sufficient
    if (searchButton && window.innerWidth >= 768) {
      searchButton.focus();
    }
  }

  // Handle search modal submission
  function handleSearchModalSubmit() {
    if (!searchInputRef) return;
    
    const query = searchInputRef.value.trim();
    if (!query) return;
    
    // Check if we're on the courses archive page
    const isCoursesPage = window.location.pathname.includes('/course') || 
                          document.body.classList.contains('post-type-archive-course');
    
    if (isCoursesPage) {
      // If on courses page, use the page's search input if available
      const coursesSearchInput = document.getElementById('search-courses');
      if (coursesSearchInput) {
        coursesSearchInput.value = query;
        coursesSearchInput.dispatchEvent(new Event('input', { bubbles: true }));
        closeSearchModal();
        return;
      }
    }
    
    // For WordPress: Navigate to courses archive with search parameter
    // Use the WordPress site URL from ccsData if available, otherwise use relative path
    const baseUrl = window.ccsData?.homeUrl || '/';
    const coursesUrl = baseUrl + (baseUrl.endsWith('/') ? '' : '/') + '?post_type=course&s=' + encodeURIComponent(query);
    window.location.href = coursesUrl;
  }

  // Get course suggestions for autocomplete via WordPress AJAX
  async function getCourseSuggestionsAsync(query) {
    if (!query || query.trim() === '' || query.length < 2) {
      return [];
    }
    
    // Check if WordPress AJAX is available
    const ajaxUrl = window.ccsData?.ajaxUrl;
    if (!ajaxUrl) {
      // Fallback: try CourseDataManager if available (for static site compatibility)
      if (window.CourseDataManager) {
        return getCourseSuggestionsFromManager(query);
      }
      return [];
    }
    
    try {
      const response = await fetch(`${ajaxUrl}?action=cta_course_search&q=${encodeURIComponent(query)}`);
      const data = await response.json();
      
      if (data.success && data.data?.courses) {
        return data.data.courses;
      }
    } catch (error) {
      if (isDevelopment) console.error('Search autocomplete error:', error);
    }
    
    return [];
  }
  
  // Fallback: Get suggestions from CourseDataManager (static site)
  function getCourseSuggestionsFromManager(query) {
    if (!window.CourseDataManager) return [];
    
    const courses = window.CourseDataManager.getCourses();
    const lowerQuery = query.toLowerCase().trim();
    const maxSuggestions = 8;
    
    const scoredMatches = courses.map(course => {
      let score = 0;
      const courseTitleLower = course.title.toLowerCase();
      const courseDescLower = (course.description || '').toLowerCase();
      const courseCategory = (course.category || '').toLowerCase();
      
      if (courseTitleLower === lowerQuery) score += 100;
      else if (courseTitleLower.startsWith(lowerQuery)) score += 80;
      else if (courseTitleLower.includes(lowerQuery)) score += 60;
      if (courseCategory && courseCategory.includes(lowerQuery)) score += 70;
      if (courseDescLower.includes(lowerQuery)) score += 30;
      
      return { course, score };
    })
    .filter(item => item.score > 0)
    .sort((a, b) => b.score - a.score)
    .slice(0, maxSuggestions)
    .map(item => item.course);
    
    return scoredMatches;
  }

  // Highlight matching text in suggestion
  function highlightMatch(text, query) {
    if (!query || query.trim() === '') return text;
    
    const lowerText = text.toLowerCase();
    const lowerQuery = query.toLowerCase().trim();
    const index = lowerText.indexOf(lowerQuery);
    
    if (index === -1) return text;
    
    const before = text.substring(0, index);
    const match = text.substring(index, index + query.length);
    const after = text.substring(index + query.length);
    
    return `${before}<mark class="search-autocomplete-highlight">${match}</mark>${after}`;
  }

  // Render autocomplete suggestions (async for WordPress AJAX)
  async function renderAutocompleteSuggestions(query) {
    if (!searchAutocomplete) return;
    
    if (!query || query.trim() === '' || query.length < 2) {
      searchAutocomplete.style.display = 'none';
      searchAutocomplete.innerHTML = '';
      return;
    }
    
    // Show loading state
    searchAutocomplete.innerHTML = '<div class="search-autocomplete-loading">Searching courses...</div>';
    searchAutocomplete.style.display = 'block';
    
    const suggestions = await getCourseSuggestionsAsync(query);
    
    if (suggestions.length === 0) {
      searchAutocomplete.innerHTML = `
        <div class="search-autocomplete-empty">
          <div class="search-autocomplete-empty-icon">
            <i class="fas fa-search" aria-hidden="true"></i>
          </div>
          <p class="search-autocomplete-empty-text">No courses found for "${query}"</p>
          <p class="search-autocomplete-empty-hint">Try a different search term or browse all courses</p>
        </div>
      `;
      searchAutocomplete.style.display = 'block';
      return;
    }
    
    searchAutocomplete.innerHTML = suggestions.map((course, index) => {
      const highlightedTitle = highlightMatch(course.title, query);
      const categoryDisplay = course.category ? 
        `<span class="search-autocomplete-category">${course.category}</span>` : '';
      const durationDisplay = course.duration ? 
        `<span class="search-autocomplete-duration">${course.duration}</span>` : '';
      const courseUrl = course.url || '#';
      
      return `
        <a
          href="${courseUrl}"
          class="search-autocomplete-item"
          role="option"
          aria-selected="false"
          data-course-id="${course.id}"
          data-course-title="${course.title}"
        >
          <span class="search-autocomplete-icon" aria-hidden="true">
            <i class="fas fa-graduation-cap"></i>
          </span>
          <span class="search-autocomplete-text">
            <span class="search-autocomplete-title">${highlightedTitle}</span>
            <span class="search-autocomplete-meta">
              ${categoryDisplay}
              ${durationDisplay}
            </span>
          </span>
        </a>
      `;
    }).join('');
    
    searchAutocomplete.style.display = 'block';
    
    // Add click handlers to close modal on selection
    const suggestionItems = searchAutocomplete.querySelectorAll('.search-autocomplete-item');
    suggestionItems.forEach(item => {
      item.addEventListener('click', () => {
        closeSearchModal();
      });
    });
  }

  // Handle autocomplete input with debouncing
  function handleAutocompleteInput() {
    if (!searchInputRef) return;
    
    // Clear existing timeout
    if (autocompleteTimeout) {
      clearTimeout(autocompleteTimeout);
    }
    
    const query = searchInputRef.value;
    
    // Debounce autocomplete (300ms)
    autocompleteTimeout = setTimeout(() => {
      renderAutocompleteSuggestions(query);
    }, 300);
  }

  // This ensures keyboard users can't tab outside the modal, meeting WCAG 2.1 Level AA
  // When user presses Tab at last element, focus returns to first element (and vice versa with Shift+Tab)
  // Focus trap for search modal
  function trapFocusInModal(e) {
    if (!state.isSearchOpen || !searchModalContainer) return;
    if (e.key !== 'Tab') return;

    const focusableElements = searchModalContainer.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (e.shiftKey) {
      if (document.activeElement === firstElement) {
        e.preventDefault();
        lastElement?.focus();
      }
    } else {
      if (document.activeElement === lastElement) {
        e.preventDefault();
        firstElement?.focus();
      }
    }
  }

  // ============================================
  // Desktop Dropdown Functions
  // ============================================

  // Helper function to position mega menu below header (centered with header container)
  function updateMegaMenuPosition() {
    if (!coursesDropdownRef) return;
    const header = document.querySelector('.site-header');
    if (header) {
      const headerRect = header.getBoundingClientRect();
      const headerHeight = headerRect.height;
      coursesDropdownRef.style.top = `${headerHeight + 8}px`;
      // Set CSS custom property for future use
      document.documentElement.style.setProperty('--header-height', `${headerHeight}px`);
    }
  }

  // Helper function to position resources dropdown below header (aligned with resources button)
  function updateResourcesDropdownPosition() {
    if (!resourcesDropdownRef || !resourcesButtonRef) return;
    const header = document.querySelector('.site-header');
    if (header) {
      const headerRect = header.getBoundingClientRect();
      const headerHeight = headerRect.height;
      const buttonRect = resourcesButtonRef.getBoundingClientRect();
      
      // Position dropdown 8px below header, aligned with the left edge of the button
      resourcesDropdownRef.style.top = `${headerHeight + 8}px`;
      resourcesDropdownRef.style.left = `${buttonRect.left}px`;
    }
  }

  // These functions create a better UX by preventing accidental dropdown closures
  // When mouse enters: clear any pending close timeout and open immediately
  // When mouse leaves: wait 150ms before closing (gives user time to move mouse to dropdown)
  function handleCoursesEnter() {
    if (state.coursesTimeout) {
      clearTimeout(state.coursesTimeout);
      state.coursesTimeout = null;
    }
    state.isCoursesOpen = true;
    state.lastFocusedElement = document.activeElement;
    
    if (coursesButtonRef) {
      coursesButtonRef.setAttribute('aria-expanded', 'true');
    }
    if (coursesDropdownRef) {
      coursesDropdownRef.classList.add('active');
      coursesDropdownRef.setAttribute('aria-hidden', 'false');
      
      // Position mega menu below header (centered with header container)
      updateMegaMenuPosition();
      
      // Set up focus trap
      setupFocusTrap();
      // Make menu items focusable
      makeMenuItemsFocusable(true);
    }
  }
  
  function setupFocusTrap() {
    if (!coursesDropdownRef) return;
    state.focusableElements = Array.from(
      coursesDropdownRef.querySelectorAll(
        'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
      )
    );
    // Add focus trap and navigation listeners
    document.addEventListener('keydown', trapFocus, true);
    document.addEventListener('keydown', handleMegaMenuNavigation, true);
  }
  
  // Make menu items focusable or not based on menu state
  function makeMenuItemsFocusable(focusable) {
    if (!coursesDropdownRef) return;
    const menuItems = coursesDropdownRef.querySelectorAll('[role="menuitem"]');
    menuItems.forEach(item => {
      if (focusable) {
        item.setAttribute('tabindex', '0');
      } else {
        item.setAttribute('tabindex', '-1');
      }
    });
    // Also handle close button
    if (coursesCloseBtn) {
      coursesCloseBtn.setAttribute('tabindex', focusable ? '0' : '-1');
    }
  }

  function handleCoursesLeave() {
    // 150ms delay before closing prevents accidental closure (reduced from 300ms)
    state.coursesTimeout = setTimeout(() => {
      closeCoursesMenu();
    }, 150);
  }
  
  function closeCoursesMenu() {
    state.isCoursesOpen = false;
    if (coursesButtonRef) {
      coursesButtonRef.setAttribute('aria-expanded', 'false');
    }
    if (coursesDropdownRef) {
      coursesDropdownRef.classList.remove('active');
      coursesDropdownRef.setAttribute('aria-hidden', 'true');
    }
    // Remove focus trap and navigation listeners
    document.removeEventListener('keydown', trapFocus, true);
    document.removeEventListener('keydown', handleMegaMenuNavigation, true);
    state.focusableElements = [];
    // Make menu items non-focusable
    makeMenuItemsFocusable(false);
    // Return focus to trigger
    if (state.lastFocusedElement && typeof state.lastFocusedElement.focus === 'function') {
      state.lastFocusedElement.focus();
    }
  }

  function handleGroupTrainingEnter() {
    if (state.groupTrainingTimeout) {
      clearTimeout(state.groupTrainingTimeout);
      state.groupTrainingTimeout = null;
    }
    state.isGroupTrainingOpen = true;
    if (groupTrainingDropdownRef) {
      groupTrainingDropdownRef.classList.add('active');
    }
  }

  function handleGroupTrainingLeave() {
    // 150ms delay before closing prevents accidental closure (matches courses dropdown)
    state.groupTrainingTimeout = setTimeout(() => {
      state.isGroupTrainingOpen = false;
      if (groupTrainingDropdownRef) {
        groupTrainingDropdownRef.classList.remove('active');
      }
    }, 150); // Matches courses dropdown timeout
  }

  // Keyboard navigation for dropdowns
  // Dropdown behavior:
  // - 150ms delay before closing (coursesTimeout/groupTrainingTimeout) prevents accidental closure
  // - ChevronDownIcon rotates 180deg when open
  // - ArrowDown key moves focus to first link in dropdown
  // - Escape key closes dropdown and returns focus to trigger button
  // Handle focus management for mega menu (allow tabbing out)
  // For dropdown menus, users should be able to tab out to continue navigation
  function trapFocus(e) {
    if (e.key !== 'Tab' || !state.isCoursesOpen || !coursesDropdownRef) return;
    
    // Rebuild focusable elements list in case DOM changed
    state.focusableElements = Array.from(
      coursesDropdownRef.querySelectorAll(
        'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
      )
    );
    
    if (state.focusableElements.length === 0) return;
    
    const firstFocusable = state.focusableElements[0];
    const lastFocusable = state.focusableElements[state.focusableElements.length - 1];
    const activeElement = document.activeElement;
    
    // Check if focus is inside the menu
    const isFocusInside = coursesDropdownRef.contains(activeElement);
    const isOnTriggerButton = activeElement === coursesButtonRef;
    
    // If focus is not inside the menu and not on trigger, allow normal tab behavior
    if (!isFocusInside && !isOnTriggerButton) {
      // Focus has moved outside - close the menu
      closeCoursesMenu();
      return;
    }
    
    // Allow tabbing out of the menu:
    // - Tab on last element: allow focus to move to next element on page (close menu)
    // - Shift+Tab on first element: allow focus to move back to trigger button (close menu)
    if (e.shiftKey) {
      // Shift+Tab - moving backwards
      if (activeElement === firstFocusable) {
        // On first element, allow tabbing back to trigger button or previous element
        // Don't prevent default - let focus move naturally
        // Close menu after focus moves
        setTimeout(() => {
          const newActiveElement = document.activeElement;
          // If focus moved outside menu (including to trigger button), close menu
          if (!coursesDropdownRef.contains(newActiveElement)) {
            closeCoursesMenu();
          }
        }, 0);
      }
    } else {
      // Tab - moving forwards
      if (activeElement === lastFocusable) {
        // On last element, allow tabbing to next element on page
        // Don't prevent default - let focus move naturally
        // Close menu after focus moves
        setTimeout(() => {
          const newActiveElement = document.activeElement;
          // If focus moved outside menu, close menu
          if (!coursesDropdownRef.contains(newActiveElement)) {
            closeCoursesMenu();
          }
        }, 0);
      }
    }
  }
  
  // Arrow key navigation within mega menu
  function handleMegaMenuNavigation(e) {
    if (!state.isCoursesOpen || !coursesDropdownRef) return;
    
    const currentElement = document.activeElement;
    const allItems = state.focusableElements;
    const currentIndex = allItems.indexOf(currentElement);
    
    if (currentIndex === -1) return;
    
    let nextIndex = currentIndex;
    
    switch(e.key) {
      case 'ArrowDown':
        e.preventDefault();
        nextIndex = (currentIndex + 1) % allItems.length;
        break;
      case 'ArrowUp':
        e.preventDefault();
        nextIndex = (currentIndex - 1 + allItems.length) % allItems.length;
        break;
      case 'Home':
        e.preventDefault();
        nextIndex = 0;
        break;
      case 'End':
        e.preventDefault();
        nextIndex = allItems.length - 1;
        break;
      case 'Escape':
        e.preventDefault();
        closeCoursesMenu(true); // Return focus to trigger button
        return;
      default:
        return;
    }
    
    allItems[nextIndex]?.focus();
  }

  function handleCoursesKeyDown(e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      if (state.isCoursesOpen) {
        closeCoursesMenu();
      } else {
        handleCoursesEnter();
        // Focus first item after menu opens
        setTimeout(() => {
          const firstItem = coursesDropdownRef?.querySelector('[role="menuitem"]');
          if (firstItem) {
            firstItem.focus();
          }
        }, 50);
      }
    } else if (e.key === 'Escape') {
      if (state.isCoursesOpen) {
        e.preventDefault();
        closeCoursesMenu();
      }
    } else if (e.key === 'ArrowDown' && !state.isCoursesOpen) {
      e.preventDefault();
      handleCoursesEnter();
      // Focus first item after menu opens
      setTimeout(() => {
        const firstItem = coursesDropdownRef?.querySelector('[role="menuitem"]');
        if (firstItem) {
          firstItem.focus();
        }
      }, 50);
    }
  }

  function handleGroupTrainingKeyDown(e) {
    if (e.key === 'ArrowDown' && state.isGroupTrainingOpen) {
      e.preventDefault();
      const firstLink = groupTrainingDropdownRef?.querySelector('a');
      firstLink?.focus();
    } else if (e.key === 'Escape') {
      state.isGroupTrainingOpen = false;
      if (groupTrainingButtonRef) {
        groupTrainingButtonRef.focus();
      }
      if (groupTrainingDropdownRef) {
        groupTrainingDropdownRef.classList.remove('active');
      }
    }
  }

  // Resources Dropdown Handlers
  function openResources() {
    if (state.resourcesTimeout) {
      clearTimeout(state.resourcesTimeout);
      state.resourcesTimeout = null;
    }
    state.isResourcesOpen = true;
    if (resourcesButtonRef) {
      resourcesButtonRef.setAttribute('aria-expanded', 'true');
    }
    if (resourcesDropdownRef) {
      resourcesDropdownRef.classList.add('active');
      resourcesDropdownRef.setAttribute('aria-hidden', 'false');
      
      // Position dropdown below header (aligned with resources button)
      updateResourcesDropdownPosition();
    }
  }

  function closeResources() {
    if (state.resourcesTimeout) {
      clearTimeout(state.resourcesTimeout);
      state.resourcesTimeout = null;
    }
    state.isResourcesOpen = false;
    if (resourcesButtonRef) {
      resourcesButtonRef.setAttribute('aria-expanded', 'false');
    }
    if (resourcesDropdownRef) {
      resourcesDropdownRef.classList.remove('active');
      resourcesDropdownRef.setAttribute('aria-hidden', 'true');
    }
  }

  function handleResourcesEnter() {
    openResources();
  }

  function handleResourcesLeave() {
    state.resourcesTimeout = setTimeout(() => {
      closeResources();
    }, 150);
  }

  function handleResourcesKeyDown(e) {
    if (e.key === 'ArrowDown' && state.isResourcesOpen) {
      e.preventDefault();
      const firstLink = resourcesDropdownRef?.querySelector('a');
      firstLink?.focus();
    } else if (e.key === 'Escape') {
      closeResources();
      resourcesButtonRef?.focus();
    }
  }

  function handleResourcesFocus() {
    openResources();
  }

  function handleResourcesBlur(e) {
    if (resourcesDropdownWrapper && !resourcesDropdownWrapper.contains(e.relatedTarget)) {
      setTimeout(() => {
        closeResources();
      }, 150);
    }
  }

  // Focus management for dropdowns - returns focus to trigger on close
  function handleCoursesFocus() {
    // Only open on focus if triggered by keyboard (not mouse)
    // Check if focus came from a keyboard event
    if (state.isCoursesOpen) return;
    
    state.isCoursesOpen = true;
    state.lastFocusedElement = document.activeElement;
    
    if (coursesButtonRef) {
      coursesButtonRef.setAttribute('aria-expanded', 'true');
    }
    if (coursesDropdownRef) {
      coursesDropdownRef.classList.add('active');
      coursesDropdownRef.setAttribute('aria-hidden', 'false');
      
      // Position mega menu below header (centered with header container)
      updateMegaMenuPosition();
      
      setupFocusTrap();
      makeMenuItemsFocusable(true);
      // Focus first item when opened via keyboard
      setTimeout(() => {
        const firstItem = coursesDropdownRef.querySelector('[role="menuitem"]');
        if (firstItem) {
          firstItem.focus();
        }
      }, 50);
    }
  }

  function handleCoursesBlur(e) {
    // Only close if focus moves outside dropdown entirely
    if (coursesDropdownWrapper && !coursesDropdownWrapper.contains(e.relatedTarget)) {
      setTimeout(() => {
        state.isCoursesOpen = false;
        if (coursesButtonRef) {
          coursesButtonRef.setAttribute('aria-expanded', 'false');
        }
        if (coursesDropdownRef) {
          coursesDropdownRef.classList.remove('active');
        }
      }, 150);
    }
  }

  function handleGroupTrainingFocus() {
    state.isGroupTrainingOpen = true;
    if (groupTrainingDropdownRef) {
      groupTrainingDropdownRef.classList.add('active');
    }
  }

  function handleGroupTrainingBlur(e) {
    if (groupTrainingDropdownWrapper && !groupTrainingDropdownWrapper.contains(e.relatedTarget)) {
      setTimeout(() => {
        state.isGroupTrainingOpen = false;
        if (groupTrainingDropdownRef) {
          groupTrainingDropdownRef.classList.remove('active');
        }
      }, 150); // Matches courses dropdown timeout
    }
  }

  // ============================================
  // Mobile Menu Functions
  // ============================================

  // Mobile menu with improved scrollability and accessibility:
  // - max-h-[calc(100vh-80px)] ensures menu fits viewport minus header
  // - overflow-y-auto makes content scrollable when exceeds viewport
  // - Bottom CTA button always accessible via scroll
  // This ensures the background page doesn't scroll while menu is visible
  // Critical for mobile UX - prevents confusing double-scroll behavior
  function openMobileMenu() {
    // Re-query elements to ensure they exist (handles cases where script loads before DOM)
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    const menuContent = document.getElementById('mobile-menu-content');
    
    if (!menu) {
      console.error('Mobile menu element #mobile-menu not found!');
      return;
    }
    
    state.isMobileMenuOpen = true;
    // Use class-based approach - CSS handles display logic
    menu.classList.add('active');
    document.body.style.overflow = 'hidden';
    if (button) {
      button.setAttribute('aria-expanded', 'true');
      button.setAttribute('aria-label', 'Close menu');
      // Update icon to X - handle both SVG and Font Awesome icons
      const icon = button.querySelector('#mobile-menu-icon');
      if (icon) {
        if (icon.tagName === 'SVG') {
          // For SVG, update paths to show X icon (two crossing lines)
          const paths = icon.querySelectorAll('path');
          if (paths.length >= 3) {
            // Change from hamburger (3 horizontal lines) to X (2 crossing lines)
            paths[0].setAttribute('d', 'M6 18L18 6');
            paths[1].setAttribute('d', 'M6 6l12 12');
            // Hide the third line
            if (paths[2]) {
              paths[2].style.display = 'none';
            }
          }
        } else {
          // If it's Font Awesome, toggle classes
          icon.classList.remove('fa-bars');
          icon.classList.add('fa-times');
        }
      }
    }
    
    // Focus management - focus first link in menu
    setTimeout(() => {
      if (menuContent) {
        const firstLink = menuContent.querySelector('button, a');
        firstLink?.focus();
      }
    }, 100);
  }

  function closeMobileMenu() {
    // Re-query elements to ensure they exist (handles cases where script loads before DOM)
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    
    state.isMobileMenuOpen = false;
    if (menu) {
      menu.classList.remove('active');
      // Use class-based approach - CSS handles display logic
      document.body.style.overflow = '';
    }
    if (button) {
      button.setAttribute('aria-expanded', 'false');
      button.setAttribute('aria-label', 'Open menu');
      // Update icon back to hamburger - handle both SVG and Font Awesome icons
      const icon = button.querySelector('#mobile-menu-icon');
      if (icon) {
        if (icon.tagName === 'SVG') {
          // For SVG, restore hamburger icon (3 horizontal lines)
          const paths = icon.querySelectorAll('path');
          if (paths.length >= 3) {
            paths[0].setAttribute('d', 'M4 6h16');
            paths[1].setAttribute('d', 'M4 12h16');
            paths[2].setAttribute('d', 'M4 18h16');
            // Show the third line
            if (paths[2]) {
              paths[2].style.display = '';
            }
          }
        } else {
          // If it's Font Awesome, toggle classes
          icon.classList.remove('fa-times');
          icon.classList.add('fa-bars');
        }
      }
      button.focus();
    }
  }

  function toggleMobileMenu() {
    try {
      if (state.isMobileMenuOpen) {
        closeMobileMenu();
      } else {
        openMobileMenu();
      }
    } catch (error) {
      console.error('Error in toggleMobileMenu:', error);
    }
  }
  
  // Replace placeholder functions with actual implementations
  window.toggleMobileMenu = toggleMobileMenu;
  window.closeMobileMenu = closeMobileMenu;
  window.openMobileMenu = openMobileMenu;

  function toggleMobileCourses() {
    state.isMobileCoursesOpen = !state.isMobileCoursesOpen;
    if (mobileCoursesAccordion) {
      mobileCoursesAccordion.setAttribute('aria-expanded', state.isMobileCoursesOpen.toString());
    }
    if (mobileCoursesContent) {
      if (state.isMobileCoursesOpen) {
        mobileCoursesContent.classList.add('active');
      } else {
        mobileCoursesContent.classList.remove('active');
      }
    }
  }

  function toggleMobileGroupTraining() {
    state.isMobileGroupTrainingOpen = !state.isMobileGroupTrainingOpen;
    if (mobileGroupTrainingAccordion) {
      mobileGroupTrainingAccordion.setAttribute('aria-expanded', state.isMobileGroupTrainingOpen.toString());
    }
    if (mobileGroupTrainingContent) {
      if (state.isMobileGroupTrainingOpen) {
        mobileGroupTrainingContent.classList.add('active');
      } else {
        mobileGroupTrainingContent.classList.remove('active');
      }
    }
  }

  function toggleMobileResources() {
    state.isMobileResourcesOpen = !state.isMobileResourcesOpen;
    if (mobileResourcesAccordion) {
      mobileResourcesAccordion.setAttribute('aria-expanded', state.isMobileResourcesOpen.toString());
    }
    if (mobileResourcesContent) {
      if (state.isMobileResourcesOpen) {
        mobileResourcesContent.classList.add('active');
      } else {
        mobileResourcesContent.classList.remove('active');
      }
    }
  }

  // ============================================
  // Event Listeners
  // ============================================

  // Search Modal
  if (searchButton) {
    searchButton.addEventListener('click', () => {
      openSearchModal();
    });
  }

  if (searchModalClose) {
    searchModalClose.addEventListener('click', () => {
      closeSearchModal();
    });
  }

  // Search submit button
  const searchModalSubmit = document.getElementById('search-modal-submit');
  if (searchModalSubmit) {
    searchModalSubmit.addEventListener('click', handleSearchModalSubmit);
  }

  // Enter key triggers search in search modal
  if (searchInputRef) {
    searchInputRef.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        handleSearchModalSubmit();
      }
    });
    
    // Autocomplete input handler
    searchInputRef.addEventListener('input', handleAutocompleteInput);
  }

  // Close search modal on backdrop click
  const searchModalBackdrop = searchModal?.querySelector('.search-modal-backdrop');
  if (searchModalBackdrop) {
    searchModalBackdrop.addEventListener('click', closeSearchModal);
  }
  
  // Prevent search modal container clicks from closing modal
  if (searchModalContainer) {
    searchModalContainer.addEventListener('click', (e) => {
      e.stopPropagation();
    });
  }

  // ESC key closes search modal, mobile menu, or mega menu (whichever is open)
  // This is a common UX pattern that users expect
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (state.isCoursesOpen) {
        e.preventDefault();
        closeCoursesMenu();
      } else if (state.isSearchOpen) {
        e.preventDefault();
        // Blur input immediately to dismiss mobile keyboard
        if (searchInputRef) {
          searchInputRef.blur();
        }
        closeSearchModal();
      } else if (state.isMobileMenuOpen) {
        closeMobileMenu();
      }
    }
  });

  // Focus trap for search modal
  document.addEventListener('keydown', trapFocusInModal);

  // Desktop Dropdowns - Courses
  if (coursesButtonRef && coursesDropdownRef && coursesDropdownWrapper) {
    coursesDropdownWrapper.addEventListener('mouseenter', handleCoursesEnter);
    coursesDropdownWrapper.addEventListener('mouseleave', handleCoursesLeave);
    coursesButtonRef.addEventListener('focus', handleCoursesFocus);
    coursesButtonRef.addEventListener('blur', handleCoursesBlur);
    coursesButtonRef.addEventListener('keydown', handleCoursesKeyDown);
    
    // Also handle mouse events on the mega menu itself (since it's position: fixed)
    // This prevents the menu from closing when moving mouse from button to menu
    coursesDropdownRef.addEventListener('mouseenter', () => {
      // Clear any pending close timeout when entering the menu
      if (state.coursesTimeout) {
        clearTimeout(state.coursesTimeout);
        state.coursesTimeout = null;
      }
      // Ensure menu is open
      if (!state.isCoursesOpen) {
        handleCoursesEnter();
      }
    });
    
    coursesDropdownRef.addEventListener('mouseleave', handleCoursesLeave);
    
    // Handle Escape key in dropdown links (handled by handleMegaMenuNavigation now)
    coursesDropdownRef.querySelectorAll('a').forEach(link => {
      link.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          closeCoursesMenu();
        }
      });
    });
  }
  
  // Close button event listener
  if (coursesCloseBtn) {
    coursesCloseBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeCoursesMenu();
    });
  }
  
  // Outside click handler for mega menu
  document.addEventListener('click', (e) => {
    if (state.isCoursesOpen && coursesDropdownRef && coursesDropdownWrapper) {
      const isClickInside = coursesDropdownWrapper.contains(e.target) || 
                            coursesDropdownRef.contains(e.target);
      if (!isClickInside) {
        closeCoursesMenu();
      }
    }
  });

  // Desktop Dropdowns - Group Training
  if (groupTrainingButtonRef && groupTrainingDropdownRef && groupTrainingDropdownWrapper) {
    groupTrainingDropdownWrapper.addEventListener('mouseenter', handleGroupTrainingEnter);
    groupTrainingDropdownWrapper.addEventListener('mouseleave', handleGroupTrainingLeave);
    groupTrainingButtonRef.addEventListener('focus', handleGroupTrainingFocus);
    groupTrainingButtonRef.addEventListener('blur', handleGroupTrainingBlur);
    groupTrainingButtonRef.addEventListener('keydown', handleGroupTrainingKeyDown);
    
    // Handle Escape key in dropdown links
    groupTrainingDropdownRef.querySelectorAll('a').forEach(link => {
      link.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          state.isGroupTrainingOpen = false;
          if (groupTrainingButtonRef) {
            groupTrainingButtonRef.focus();
          }
          if (groupTrainingDropdownRef) {
            groupTrainingDropdownRef.classList.remove('active');
          }
        }
      });
    });
  }

  // Desktop Dropdowns - Resources
  if (resourcesButtonRef && resourcesDropdownRef && resourcesDropdownWrapper) {
    // Ensure consistent initial accessibility state
    resourcesDropdownRef.setAttribute('aria-hidden', 'true');

    resourcesDropdownWrapper.addEventListener('mouseenter', handleResourcesEnter);
    resourcesDropdownWrapper.addEventListener('mouseleave', handleResourcesLeave);
    resourcesButtonRef.addEventListener('focus', handleResourcesFocus);
    resourcesButtonRef.addEventListener('blur', handleResourcesBlur);
    resourcesButtonRef.addEventListener('keydown', handleResourcesKeyDown);

    // CRITICAL: Add event listeners to the dropdown itself (like Courses mega menu)
    // This prevents the menu from closing when moving mouse from button to dropdown
    resourcesDropdownRef.addEventListener('mouseenter', () => {
      // Clear any pending close timeout when entering the menu
      if (state.resourcesTimeout) {
        clearTimeout(state.resourcesTimeout);
        state.resourcesTimeout = null;
      }
      // Ensure menu is open
      if (!state.isResourcesOpen) {
        openResources();
      }
    });

    resourcesDropdownRef.addEventListener('mouseleave', handleResourcesLeave);

    // Click-to-toggle (more discoverable than hover-only)
    resourcesButtonRef.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (state.isResourcesOpen) {
        closeResources();
      } else {
        openResources();
      }
    });
    
    // Handle Escape key in dropdown links
    resourcesDropdownRef.querySelectorAll('a').forEach(link => {
      link.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          closeResources();
          resourcesButtonRef?.focus();
        }
      });
    });

    // Outside click closes the dropdown (parity with Courses mega menu)
    document.addEventListener('click', (e) => {
      if (!state.isResourcesOpen) return;
      const target = e.target;
      if (!target) return;
      if (!resourcesDropdownWrapper.contains(target)) {
        closeResources();
      }
    });
  }

  // Mobile Menu - Set up event listeners
  // Function is already exposed to global scope above
  
  function attachMobileMenuHandler() {
    const btn = document.getElementById('mobile-menu-button');
    if (btn && !btn.dataset.handlerAttached) {
      btn.dataset.handlerAttached = 'true';
      
      // Remove onclick attribute and use event listener instead for better control
      btn.removeAttribute('onclick');
      
      // Add click event listener - always use window.toggleMobileMenu
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        // Direct call to ensure it works
        window.toggleMobileMenu();
      });
    }
  }
  
  // Try to set up immediately
  attachMobileMenuHandler();
  
  // Also try when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachMobileMenuHandler);
  } else {
    // DOM already ready, try again
    setTimeout(attachMobileMenuHandler, 0);
  }
  
  // Use MutationObserver to catch dynamically added buttons
  const observer = new MutationObserver(function(mutations) {
    attachMobileMenuHandler();
  });
  
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
  
  // Also try after a delay to catch any late-loading content
  setTimeout(attachMobileMenuHandler, 500);

  // Mobile search button (opens search and closes menu)
  const mobileSearchButton = document.getElementById('mobile-search-button');
  if (mobileSearchButton) {
    mobileSearchButton.addEventListener('click', () => {
      openSearchModal();
      closeMobileMenu();
    });
  }

  // Close mobile menu when clicking on links
  if (mobileMenuRef) {
    mobileMenuRef.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', closeMobileMenu);
    });
  }

  // Mobile Accordions
  if (mobileCoursesAccordion) {
    mobileCoursesAccordion.addEventListener('click', toggleMobileCourses);
  }

  if (mobileGroupTrainingAccordion) {
    mobileGroupTrainingAccordion.addEventListener('click', toggleMobileGroupTraining);
  }

  if (mobileResourcesAccordion) {
    mobileResourcesAccordion.addEventListener('click', toggleMobileResources);
  }

  // ============================================
  // Initialize
  // ============================================

  // Set initial ARIA states
  if (coursesButtonRef) {
    coursesButtonRef.setAttribute('aria-expanded', 'false');
  }
  if (coursesDropdownRef) {
    coursesDropdownRef.setAttribute('aria-hidden', 'true');
    // Make menu items non-focusable initially
    makeMenuItemsFocusable(false);
  }
  // Group Training is now a link, no aria-expanded needed
  if (mobileMenuButton) {
    mobileMenuButton.setAttribute('aria-expanded', 'false');
  }
  if (searchModal) {
    searchModal.setAttribute('aria-hidden', 'true');
  }
  if (mobileCoursesAccordion) {
    mobileCoursesAccordion.setAttribute('aria-expanded', 'false');
  }
  if (mobileGroupTrainingAccordion) {
    mobileGroupTrainingAccordion.setAttribute('aria-expanded', 'false');
  }

  // Close mobile menu on window resize if desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 768 && state.isMobileMenuOpen) {
      closeMobileMenu();
    }
    // Update mega menu position if it's open (header height might change)
    if (state.isCoursesOpen && coursesDropdownRef) {
      updateMegaMenuPosition();
    }
    // Update resources dropdown position if it's open (header height might change)
    if (state.isResourcesOpen && resourcesDropdownRef) {
      updateResourcesDropdownPosition();
    }
  });

  // Prevent body scroll when mobile menu is open
  // This is handled in openMobileMenu/closeMobileMenu functions

  // ============================================
  // Global Functions (for onclick handlers)
  // ============================================

  // Make functions globally available for onclick handlers in HTML
  // Note: toggleMobileMenu and closeMobileMenu are already exposed earlier
  window.openSearchModal = openSearchModal;
  window.closeSearchModal = closeSearchModal;
  window.handleSearchModalSubmit = handleSearchModalSubmit;
  window.toggleMobileCourses = toggleMobileCourses;
  window.toggleMobileGroupTraining = toggleMobileGroupTraining;
  window.toggleMobileResources = toggleMobileResources;

  // ============================================
  // About CTA Modern Section - Micro-interactions
  // ============================================
  
  // Parallax effect for image on scroll (desktop only)
  // Note: Image removed in redesign, but keeping code structure for future use
  if (window.innerWidth >= 1024) {
    const aboutSection = document.querySelector('#main-content > section.about-cta-modern');
    const aboutImage = aboutSection?.querySelector('.about-cta-modern-image');
    
    if (aboutSection && aboutImage) {
      let ticking = false;
      let lastScrollY = window.scrollY;
      
      const handleScroll = function() {
        if (!ticking) {
          window.requestAnimationFrame(function() {
            const currentScrollY = window.scrollY;
            const rect = aboutSection.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            // Only apply parallax when section is in viewport
            if (rect.top < windowHeight && rect.bottom > 0) {
              const scrollDelta = currentScrollY - lastScrollY;
              const currentTransform = aboutImage.style.transform || 'translateY(0px)';
              const currentY = parseFloat(currentTransform.match(/translateY\((-?\d+\.?\d*)px\)/)?.[1] || 0);
              
              // Apply subtle parallax (max 4px movement, smooth)
              const newY = Math.max(-4, Math.min(0, currentY - scrollDelta * 0.1));
              aboutImage.style.transform = `translateY(${newY}px)`;
            } else {
              // Reset when out of viewport
              aboutImage.style.transform = '';
            }
            
            lastScrollY = currentScrollY;
            ticking = false;
          });
          
          ticking = true;
        }
      };
      
      window.addEventListener('scroll', handleScroll, { passive: true });
      
      // Clean up on resize
      window.addEventListener('resize', function() {
        if (window.innerWidth < 1024 && aboutImage) {
          aboutImage.style.transform = '';
        }
      });
    }
  }
  
  // Intersection Observer for stat cards fade-in on scroll
  const statCards = document.querySelectorAll('.about-cta-modern-stat-card');
  
  if (statCards.length > 0 && 'IntersectionObserver' in window) {
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.style.animationPlayState = 'running';
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);
    
    statCards.forEach(function(card) {
      card.style.animationPlayState = 'paused';
      observer.observe(card);
    });
  }

  // Intersection Observer for process cards fade-in on scroll
  const processCards = document.querySelectorAll('.process-card');
  
  if (processCards.length > 0 && 'IntersectionObserver' in window) {
    const processObserverOptions = {
      threshold: 0.15,
      rootMargin: '0px 0px -80px 0px'
    };
    
    const processObserver = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('process-visible');
          processObserver.unobserve(entry.target);
        }
      });
    }, processObserverOptions);
    
    processCards.forEach(function(card) {
      processObserver.observe(card);
    });
  }

  // Position connector lines between process cards
  function positionProcessConnectors() {
    const card1 = document.querySelector('.process-card-1');
    const card2 = document.querySelector('.process-card-2');
    const card3 = document.querySelector('.process-card-3');
    const connector12 = document.querySelector('.process-connector-1-2');
    const connector23 = document.querySelector('.process-connector-2-3');
    const grid = document.querySelector('.process-grid');
    
    if (!card1 || !card2 || !card3 || !connector12 || !connector23 || !grid) return;
    
    // Get positions relative to grid
    const gridRect = grid.getBoundingClientRect();
    const card1Rect = card1.getBoundingClientRect();
    const card2Rect = card2.getBoundingClientRect();
    const card3Rect = card3.getBoundingClientRect();
    
    // Get number circle positions
    const circle1 = card1.querySelector('.process-number-circle');
    const circle2 = card2.querySelector('.process-number-circle');
    const circle3 = card3.querySelector('.process-number-circle');
    
    if (!circle1 || !circle2 || !circle3) return;
    
    const circle1Rect = circle1.getBoundingClientRect();
    const circle2Rect = circle2.getBoundingClientRect();
    const circle3Rect = circle3.getBoundingClientRect();
    
    // Position connector 1-2: from bottom of circle1 to top of circle2
    const start1 = circle1Rect.bottom - gridRect.top;
    const end1 = circle2Rect.top - gridRect.top;
    const left1 = circle1Rect.left + circle1Rect.width / 2 - gridRect.left;
    
    connector12.style.left = left1 + 'px';
    connector12.style.top = start1 + 'px';
    connector12.style.height = (end1 - start1) + 'px';
    
    // Position connector 2-3: from bottom of circle2 to top of circle3
    const start2 = circle2Rect.bottom - gridRect.top;
    const end2 = circle3Rect.top - gridRect.top;
    const left2 = circle2Rect.left + circle2Rect.width / 2 - gridRect.left;
    
    connector23.style.left = left2 + 'px';
    connector23.style.top = start2 + 'px';
    connector23.style.height = (end2 - start2) + 'px';
  }
  
  // Position connectors on load and resize
  if (document.querySelector('.process-grid')) {
    positionProcessConnectors();
    window.addEventListener('resize', positionProcessConnectors);
    // Also position after cards are visible (in case of animations)
    setTimeout(positionProcessConnectors, 1000);
  }

  // Booking Enquiry Form Handler
  const bookingForm = document.getElementById('booking-enquiry-form');
  
  if (bookingForm) {
    bookingForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Check consent checkbox
      const consentCheckbox = document.getElementById('booking-consent');
      if (!consentCheckbox || !consentCheckbox.checked) {
        alert('Please provide consent to be contacted before submitting your enquiry.');
        consentCheckbox.focus();
        return;
      }
      
      const formData = new FormData(bookingForm);
      const name = formData.get('name');
      const email = formData.get('email');
      const phone = formData.get('phone') || 'Not provided';
      const interest = formData.get('interest');
      
      // Create mailto link with form data
      const subject = encodeURIComponent('Training Booking Enquiry from ' + name);
      const body = encodeURIComponent(
        'New booking enquiry:\n\n' +
        'Name: ' + name + '\n' +
        'Email: ' + email + '\n' +
        'Phone: ' + phone + '\n' +
        'Interested in: ' + interest + '\n' +
        'Consent to contact: Yes\n\n' +
        'Please contact them to discuss their training needs.'
      );
      
      // Open email client (you can replace this with your form submission endpoint)
      window.location.href = 'mailto:enquiries@continuitytrainingacademy.co.uk?subject=' + subject + '&body=' + body;
      
      // Generate custom thank you message for booking enquiry
      const thankYouMessage = "Thank you! Your booking enquiry has been sent.";
      const nextStepsMessage = "We will be in touch regarding your training booking enquiry. Please keep an eye out for an email from us.";
      
      // Show thank you popup with custom message
      if (window.showThankYouModal) {
        window.showThankYouModal(thankYouMessage, {
          nextSteps: nextStepsMessage
        });
      }
      
      // Reset form
      bookingForm.reset();
      
      // Reset consent checkbox (reuse the variable already declared above)
      if (consentCheckbox) {
        consentCheckbox.checked = false;
      }
    });
  }

  // ============================================
  // Category Cards Progressive Disclosure (Load More)
  // ============================================
  // Reduces cognitive overload on mobile by showing 6 cards initially
  // with a "Load More" button to reveal the remaining 3 cards
  
  function initCategoryLoadMore() {
    const categoriesLoadMoreBtn = document.getElementById('categories-load-more');
    const hiddenCategoryCards = document.querySelectorAll('.category-card-hidden');
    
    if (categoriesLoadMoreBtn && hiddenCategoryCards.length > 0) {
      // Add ARIA label for accessibility
      if (!categoriesLoadMoreBtn.getAttribute('aria-label')) {
        categoriesLoadMoreBtn.setAttribute('aria-label', 'Show all training categories');
      }
      
      categoriesLoadMoreBtn.addEventListener('click', function() {
        const isExpanded = categoriesLoadMoreBtn.getAttribute('aria-expanded') === 'true';
        const loadMoreText = categoriesLoadMoreBtn.querySelector('.load-more-text');
        
        if (isExpanded) {
          // Hide cards
          hiddenCategoryCards.forEach(card => {
            card.classList.remove('category-card-visible');
          });
          categoriesLoadMoreBtn.setAttribute('aria-expanded', 'false');
          categoriesLoadMoreBtn.setAttribute('aria-label', 'Show all training categories');
          if (loadMoreText) {
            loadMoreText.textContent = 'View All Categories';
          }
        } else {
          // Show cards
          hiddenCategoryCards.forEach(card => {
            card.classList.add('category-card-visible');
          });
          categoriesLoadMoreBtn.setAttribute('aria-expanded', 'true');
          categoriesLoadMoreBtn.setAttribute('aria-label', 'Hide additional training categories');
          if (loadMoreText) {
            loadMoreText.textContent = 'Show Less Categories';
          }
          
          // Smooth scroll to first newly revealed card
          if (hiddenCategoryCards[0]) {
            hiddenCategoryCards[0].scrollIntoView({ 
              behavior: 'smooth', 
              block: 'nearest' 
            });
          }
        }
      });
    }
  }
  
  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCategoryLoadMore);
  } else {
    initCategoryLoadMore(); // DOM already loaded
  }

  // Homepage Sticky CTA Button
  function initHomepageStickyCTA() {
    try {
      const stickyCTA = document.getElementById('homepage-sticky-cta');
      const ctaSection = document.getElementById('cta-centered-section');
      
      if (!stickyCTA) return;
      
      let lastScrollTop = 0;
      let ticking = false;
      
      // Debounced scroll handler
      function handleScroll() {
        if (!ticking) {
          window.requestAnimationFrame(() => {
            try {
              const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
              const isMobile = window.innerWidth < 768;
              // Support both footer variants used across the static site.
              // `.site-footer` is the shared partial/footer component, while many pages currently use `.site-footer-modern`.
              const footer = document.querySelector('.site-footer, .site-footer-modern');
              
              // Show after 200px scroll
              if (scrollTop > 200) {
                // On mobile, check if form is visible or if footer is near
                if (isMobile) {
                  // Check if form is visible
                  if (ctaSection) {
                    const formRect = ctaSection.getBoundingClientRect();
                    const formVisible = formRect.top < window.innerHeight && formRect.bottom > 0;
                    
                    if (formVisible) {
                      stickyCTA.classList.remove('visible');
                      stickyCTA.classList.add('hidden-mobile');
                    } else {
                      stickyCTA.classList.remove('hidden-mobile');
                    }
                  }
                  
                  // Check if footer is near viewport (within 300px) - hide button to prevent overlap
                  if (footer) {
                    const footerRect = footer.getBoundingClientRect();
                    const footerNear = footerRect.top < window.innerHeight + 300; // 300px threshold (increased from 200px)
                    
                    if (footerNear) {
                      // Aggressively hide button when footer is near
                      stickyCTA.classList.remove('visible');
                      stickyCTA.classList.add('hidden-near-footer');
                    } else {
                      stickyCTA.classList.remove('hidden-near-footer');
                      // Only show if form is not visible
                      if (ctaSection) {
                        const formRect = ctaSection.getBoundingClientRect();
                        const formVisible = formRect.top < window.innerHeight && formRect.bottom > 0;
                        if (!formVisible) {
                          stickyCTA.classList.add('visible');
                        }
                      } else {
                        stickyCTA.classList.add('visible');
                      }
                    }
                  } else {
                    // No footer check, just check form visibility
                    if (ctaSection) {
                      const formRect = ctaSection.getBoundingClientRect();
                      const formVisible = formRect.top < window.innerHeight && formRect.bottom > 0;
                      if (!formVisible) {
                        stickyCTA.classList.remove('hidden-mobile');
                        stickyCTA.classList.add('visible');
                      }
                    } else {
                      stickyCTA.classList.add('visible');
                    }
                  }
                } else {
                  // Desktop: always show after 200px scroll
                  stickyCTA.classList.add('visible');
                }
              } else {
                stickyCTA.classList.remove('visible');
                stickyCTA.classList.remove('hidden-mobile');
                stickyCTA.classList.remove('hidden-near-footer');
              }
              
              lastScrollTop = scrollTop;
            } catch (e) {
              if (isDevelopment) console.error('Error in sticky CTA scroll handler:', e);
            } finally {
              ticking = false;
            }
          });
          
          ticking = true;
        }
      }
      
      // Smooth scroll to form on click only
      stickyCTA.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        try {
          if (ctaSection) {
            ctaSection.scrollIntoView({ 
              behavior: 'smooth', 
              block: 'start' 
            });
            
            // Focus first form input after scroll
            setTimeout(() => {
              try {
                const firstInput = ctaSection.querySelector('input[type="text"], input[type="tel"], input[type="email"]');
                if (firstInput) {
                  firstInput.focus();
                }
              } catch (e) {
                if (isDevelopment) console.warn('Could not focus form input:', e);
              }
            }, 500);
          } else {
            // Fallback: scroll to contact page
            window.location.href = 'contact.html';
          }
        } catch (e) {
          if (isDevelopment) console.error('Error in sticky CTA click handler:', e);
        }
      });
      
      // Initial check
      handleScroll();
      
      // Listen to scroll events
      window.addEventListener('scroll', handleScroll, { passive: true });
      
      // Listen to resize events (for mobile/desktop switching)
      window.addEventListener('resize', handleScroll, { passive: true });
    } catch (e) {
      if (isDevelopment) console.error('Error initializing sticky CTA:', e);
    }
  }
  
  // Initialize sticky CTA on homepage
  if (document.getElementById('homepage-sticky-cta')) {
    initHomepageStickyCTA();
  }

  // Exit-Intent Popup - REMOVED (disabled per user request)

  // ============================================
  // Mobile Menu Auto-Selection Prevention
  // ============================================
  // CRITICAL: Prevent any scroll spy or auto-selection of menu items on mobile
  // This is a UX blocker - menu items should only highlight on manual click/tap, not on scroll
  // If any scroll spy functionality is added in the future, it MUST be disabled on mobile (< 768px)
  
  function preventMobileMenuAutoSelection() {
    const isMobile = window.innerWidth < 768;
    if (isMobile) {
      const mobileMenuLinks = document.querySelectorAll('#mobile-menu-content .mobile-menu-link');
      mobileMenuLinks.forEach(link => {
        // Aggressively remove all active states and attributes
        link.classList.remove('active');
        link.removeAttribute('data-active');
        link.removeAttribute('aria-current');
      });
      
      // Also check for any intersection observers that might be affecting menu items
      // Disable any scroll-based highlighting
      const mobileMenuCheck = document.getElementById('mobile-menu-content');
      if (mobileMenuCheck) {
        // Remove any scroll event listeners that might affect menu items
        // This is a safeguard against future scroll spy implementations
      }
    }
  }
  
  // Run immediately
  preventMobileMenuAutoSelection();
  
  // Run on resize
  window.addEventListener('resize', preventMobileMenuAutoSelection, { passive: true });
  
  // Run continuously on scroll to prevent any auto-selection
  window.addEventListener('scroll', () => {
    if (window.innerWidth < 768) {
      preventMobileMenuAutoSelection();
    }
  }, { passive: true });
  
  // Also prevent on mobile menu click - ensure only manual selection works
  const mobileMenuContent = document.getElementById('mobile-menu-content');
  if (mobileMenuContent) {
    mobileMenuContent.addEventListener('click', (e) => {
      if (e.target.classList.contains('mobile-menu-link')) {
        // Only allow manual highlighting
        const mobileMenuLinks = document.querySelectorAll('#mobile-menu-content .mobile-menu-link');
        mobileMenuLinks.forEach(link => {
          if (link !== e.target) {
            link.classList.remove('active');
            link.removeAttribute('data-active');
            link.removeAttribute('aria-current');
          }
        });
      }
    }, { passive: true });
  }

  // JavaScript initialized - console.log removed for production
  // ============================================
  // Back to Top Button
  // ============================================
  
  (function initBackToTop() {
    const backToTopButton = document.getElementById('back-to-top');
    
    if (!backToTopButton) {
      return; // Button not present on this page
    }
    
    // Show/hide button based on scroll position
    function handleScroll() {
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      const showThreshold = 300; // Show button after scrolling 300px
      
      if (scrollTop > showThreshold) {
        backToTopButton.classList.add('visible');
        backToTopButton.setAttribute('aria-hidden', 'false');
      } else {
        backToTopButton.classList.remove('visible');
        backToTopButton.setAttribute('aria-hidden', 'true');
      }
    }
    
    // Smooth scroll to top
    function scrollToTop(e) {
      e.preventDefault();
      
      // Focus management: move focus to top of page
      const mainContent = document.getElementById('main-content');
      if (mainContent) {
        mainContent.setAttribute('tabindex', '-1');
        mainContent.focus();
        
        // Remove tabindex after focus (for accessibility)
        setTimeout(() => {
          mainContent.removeAttribute('tabindex');
        }, 100);
      }
      
      // Smooth scroll
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    }
    
    // Throttle scroll handler for performance
    let ticking = false;
    function throttledScroll() {
      if (!ticking) {
        window.requestAnimationFrame(function() {
          handleScroll();
          ticking = false;
        });
        ticking = true;
      }
    }
    
    // Event listeners
    backToTopButton.addEventListener('click', scrollToTop);
    window.addEventListener('scroll', throttledScroll, { passive: true });
    
    // Initial check on page load
    handleScroll();
  })();

  // ============================================
  // Footer Newsletter Form
  // ============================================
  function initFooterNewsletterForm() {
    const form = document.getElementById('footer-newsletter-form');
    if (!form) return;
    if (form.dataset?.ctaBound === 'true') return;
    form.dataset.ctaBound = 'true';

    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const emailInput = form.querySelector('input[type="email"]');
      const consentCheckbox = form.querySelector('input[name="consent"]');
      const submitBtn = form.querySelector('button[type="submit"]');

      if (consentCheckbox && !consentCheckbox.checked) {
        consentCheckbox.focus();
        return;
      }

      const email = emailInput?.value?.trim() ?? '';
      if (!email) {
        emailInput?.focus();
        return;
      }

      // Disable submit button during submission
      if (submitBtn) {
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = 'Subscribing...';
      }

      // Check if WordPress AJAX is available
      if (typeof ccsData !== 'undefined' && ccsData.ajaxUrl) {
        const formData = new FormData();
        formData.append('action', 'cta_newsletter_signup');
        formData.append('nonce', ccsData.nonce);
        formData.append('email', email);
        formData.append('consent', 'true');
        
        fetch(ccsData.ajaxUrl, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          // Re-enable button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }

          if (data.success) {
            const status = data.data?.status;
            const message = data.data?.message || 'Thank you for subscribing!';
            
            // If already subscribed, show a simple alert instead of the full modal
            if (status === 'exists') {
              alert(message);
            } else if (typeof window.showThankYouModal === 'function') {
              // For new subscriptions or reactivations, show the full modal
              window.showThankYouModal(
                message,
                { nextSteps: "Check your email for a confirmation message. We'll send you updates about new courses, CQC changes, and training opportunities." }
              );
            } else {
              alert(message);
            }
            form.reset();
          } else {
            alert(data.data?.message || 'Unable to process subscription. Please try again.');
          }
        })
        .catch(error => {
          console.error('Newsletter signup error:', error);
          // Re-enable button
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
          
          // Show thank you modal even on error (graceful degradation)
          if (typeof window.showThankYouModal === 'function') {
            window.showThankYouModal(
              'Thank you for subscribing!',
              { nextSteps: "Check your email for a confirmation message. You can unsubscribe at any time." }
            );
          } else {
            alert('Thank you for subscribing!');
          }
          form.reset();
        });
      } else {
        // Fallback for static site or when AJAX not available
        if (submitBtn) {
          submitBtn.disabled = false;
        }
        
        if (typeof window.showThankYouModal === 'function') {
          window.showThankYouModal(
            "Thank you for subscribing! We'll keep you updated with training insights and CQC updates.",
            { nextSteps: "Check your email for a confirmation message. You can unsubscribe at any time." }
          );
        } else {
          alert('Thank you for subscribing!');
        }
        form.reset();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFooterNewsletterForm);
  } else {
    initFooterNewsletterForm();
  }

  // ============================================
  // Team Member Modal - About Page
  // ============================================
  
  function initTeamModal() {
    const modal = document.getElementById('team-modal');
    const modalContent = document.getElementById('team-modal-content');
    const closeBtn = modal?.querySelector('.team-modal-close');
    const backdrop = modal?.querySelector('.team-modal-backdrop');
    const readMoreBtns = document.querySelectorAll('.team-read-more-btn');
    
    if (!modal || !modalContent || readMoreBtns.length === 0) return;
    
    // Open modal when clicking "Read More"
    readMoreBtns.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const card = this.closest('.team-card-new');
        if (!card) return;
        
        // Get team member data - matching static site approach
        const preview = card.querySelector('.team-card-preview');
        if (!preview) return;
        
        const photoEl = preview.querySelector('.team-photo-new');
        const nameEl = preview.querySelector('.team-name-new');
        const roleEl = preview.querySelector('.team-role-new');
        const experienceEl = preview.querySelector('.team-experience-new');
        
        if (!photoEl || !nameEl || !roleEl) return;
        
        const photo = photoEl.src;
        const name = nameEl.textContent.trim();
        const role = roleEl.textContent.trim();
        const experience = experienceEl ? experienceEl.innerHTML : '';
        const fullContent = card.querySelector('.team-full-content');
        
        // Build modal content - using correct class names that match CSS
        let modalHTML = `
          <div class="team-modal-header">
            <div class="team-modal-photo">
              <img src="${photo}" alt="${name}">
            </div>
            <h2 id="team-modal-title" class="team-modal-name">${name}</h2>
            <p class="team-modal-role">${role}</p>
            ${experience ? `<div class="team-modal-experience">${experience}</div>` : ''}
          </div>
        `;
        
        if (fullContent) {
          const bio = fullContent.querySelector('.team-full-bio');
          if (bio) {
            modalHTML += `<div class="team-modal-bio">${bio.innerHTML}</div>`;
          }
          
          const specialisations = fullContent.querySelector('.team-specialisations-new');
          if (specialisations) {
            modalHTML += specialisations.outerHTML;
          }
        }
        
        modalContent.innerHTML = modalHTML;
        
        // Show modal
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        // Focus close button
        setTimeout(() => closeBtn?.focus(), 100);
      });
    });
    
    // Close modal function
    function closeModal() {
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }
    
    // Close on button click
    closeBtn?.addEventListener('click', closeModal);
    
    // Close on backdrop click
    backdrop?.addEventListener('click', closeModal);
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
        closeModal();
      }
    });
  }
  
  // Initialize team modal on About page
  if (document.getElementById('team-modal')) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initTeamModal);
    } else {
      initTeamModal();
    }
  }

  // ============================================


  // ============================================


  // ============================================
  // FAQs Page - Search (filters visible items)
  // ============================================

  function initFaqSearch() {
    const input = document.getElementById('faq-search');
    if (!input) return;

    const items = Array.from(document.querySelectorAll('.faq-item'));
    if (items.length === 0) return;

    input.addEventListener('input', function () {
      const q = (this.value || '').toLowerCase().trim();

      items.forEach((item) => {
        const questionEl = item.querySelector('.group-faq-question span');
        const answerEl = item.querySelector('.group-faq-answer p');

        const question = (questionEl ? questionEl.textContent : '').toLowerCase();
        const answer = (answerEl ? answerEl.textContent : '').toLowerCase();

        const matches = !q || question.includes(q) || answer.includes(q);
        item.style.display = matches ? '' : 'none';
      });
    });
  }

  if (document.getElementById('faq-search')) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initFaqSearch);
    } else {
      initFaqSearch();
    }
  }

  // Downloadable Resources - Category Filtering
  // ============================================

  function initResourceFilters() {
    const container = document.querySelector('.resources-filter-group');
    if (!container) return;

    const buttons = Array.from(container.querySelectorAll('.resources-filter-btn'));
    const sections = Array.from(document.querySelectorAll('.content-section[data-category]'));

    if (buttons.length === 0 || sections.length === 0) return;

    function setActive(button) {
      buttons.forEach((btn) => {
        const isActive = btn === button;
        btn.classList.toggle('active', isActive);
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    }

    function applyFilter(filter) {
      const key = (filter || 'all').toString();
      if (key === 'all') {
        sections.forEach((s) => {
          s.style.display = 'block';
        });
        return;
      }
      sections.forEach((s) => {
        const cat = s.getAttribute('data-category');
        s.style.display = (cat === key) ? 'block' : 'none';
      });
    }

    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const filter = btn.getAttribute('data-filter') || 'all';
        setActive(btn);
        applyFilter(filter);
      });
    });

    // Default state
    const initial = buttons.find((b) => b.classList.contains('active')) || buttons[0];
    setActive(initial);
    applyFilter(initial.getAttribute('data-filter') || 'all');
  }

  if (document.querySelector('.resources-filter-group')) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initResourceFilters);
    } else {
      initResourceFilters();
    }
  }

  // Accordion functionality is now handled by unified accordion.js

})();

  /**
   * Training Pathway Card Toggles
   * Handles the expand/collapse functionality for training pathway cards
   */
  function initTrainingPathwayToggles() {
    const toggleButtons = document.querySelectorAll('.training-pathway-toggle');
    if (toggleButtons.length === 0) return;

    toggleButtons.forEach((button) => {
      // Prevent double-binding
      if (button.dataset.pathwayInit === 'true') return;
      button.dataset.pathwayInit = 'true';

      button.addEventListener('click', function () {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        const contentId = this.getAttribute('aria-controls');
        if (!contentId) return;

        const content = document.getElementById(contentId);
        if (!content) return;

        // Toggle states
        this.setAttribute('aria-expanded', (!isExpanded).toString());
        content.setAttribute('aria-hidden', isExpanded ? 'true' : 'false');
      });

      // Keyboard accessibility
      button.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.click();
        }
      });
    });
  }

  // Initialize training pathway toggles
  if (document.querySelector('.training-pathway-toggle')) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initTrainingPathwayToggles);
    } else {
      initTrainingPathwayToggles();
    }
  }
