// ============================================
// Continuity Training Academy - JavaScript
// Translated from React version to vanilla JS
// ============================================

(function() {
  'use strict';

  // Development mode flag - set to false in production
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
  
  // Create placeholder functions immediately so onclick handlers don't fail
  // These will be replaced with actual implementations below
  // Placeholders that actually work by finding and toggling the menu
  // Store original scroll position for scroll lock
  let scrollPosition = 0;
  
  function lockBodyScroll() {
    // Save current scroll position
    scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
    // Lock both html and body for better browser compatibility
    document.documentElement.style.overflow = 'hidden';
    document.documentElement.style.position = 'fixed';
    document.documentElement.style.width = '100%';
    document.documentElement.style.top = `-${scrollPosition}px`;
    document.body.style.overflow = 'hidden';
    document.body.style.position = 'fixed';
    document.body.style.width = '100%';
    document.body.style.top = `-${scrollPosition}px`;
  }
  
  function unlockBodyScroll() {
    // Restore scroll position and unlock
    document.documentElement.style.overflow = '';
    document.documentElement.style.position = '';
    document.documentElement.style.width = '';
    document.documentElement.style.top = '';
    document.body.style.overflow = '';
    document.body.style.position = '';
    document.body.style.width = '';
    document.body.style.top = '';
    // Restore scroll position
    window.scrollTo(0, scrollPosition);
  }
  
  window.toggleMobileMenu = function() {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    if (!menu || !button) return;
    
    const isOpen = menu.classList.contains('active') || menu.style.display === 'block';
    if (isOpen) {
      menu.style.display = 'none';
      menu.style.visibility = 'hidden';
      menu.classList.remove('active');
      button.setAttribute('aria-expanded', 'false');
      unlockBodyScroll();
    } else {
      menu.style.display = 'block';
      menu.style.visibility = 'visible';
      menu.classList.add('active');
      button.setAttribute('aria-expanded', 'true');
      lockBodyScroll();
    }
  };
  window.closeMobileMenu = function() {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    if (menu) {
      menu.style.display = 'none';
      menu.style.visibility = 'hidden';
      menu.classList.remove('active');
      unlockBodyScroll();
    }
    if (button) {
      button.setAttribute('aria-expanded', 'false');
    }
  };
  window.openMobileMenu = function() {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    if (menu) {
      menu.style.display = 'block';
      menu.style.visibility = 'visible';
      menu.classList.add('active');
      lockBodyScroll();
    }
    if (button) {
      button.setAttribute('aria-expanded', 'true');
    }
  };

  // ============================================
  // Suppress Browser Extension Errors
  // ============================================
  // These errors are caused by browser extensions (ad blockers, password managers, etc.)
  // They are harmless and don't affect functionality, but clutter the console
  // Suppress them to keep the console clean during development

  const handleError = function(event) {
    const errorMessage = event.message || event.error?.message || '';
    const isExtensionError =
      errorMessage.includes('message channel closed') ||
      errorMessage.includes('asynchronous response') ||
      errorMessage.includes('Extension context invalidated') ||
      errorMessage.includes('Receiving end does not exist');

    if (isExtensionError) {
      event.preventDefault();
      event.stopPropagation();
      return false;
    }
  };

  const handleUnhandledRejection = function(event) {
    const errorMessage = event.reason?.message || String(event.reason || '');
    const isExtensionError =
      errorMessage.includes('message channel closed') ||
      errorMessage.includes('asynchronous response') ||
      errorMessage.includes('Extension context invalidated') ||
      errorMessage.includes('Receiving end does not exist');

    if (isExtensionError) {
      event.preventDefault();
      return false;
    }
  };

  // Add error listeners with capture phase to catch errors early
  window.addEventListener('error', handleError, true);
  window.addEventListener('unhandledrejection', handleUnhandledRejection);

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
    isMobileMenuOpen: false,
    isMobileCoursesOpen: false,
    isMobileGroupTrainingOpen: false,
    coursesTimeout: null,
    groupTrainingTimeout: null,
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
  const mainContent = document.querySelector('main') || document.body;

  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  const mobileMenuRef = document.getElementById('mobile-menu-content');
  const mobileCoursesAccordion = document.getElementById('mobile-courses-accordion');
  const mobileCoursesContent = document.getElementById('mobile-courses-content');
  const mobileGroupTrainingAccordion = document.getElementById('mobile-group-training-accordion');
  const mobileGroupTrainingContent = document.getElementById('mobile-group-training-content');

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
      // Use robust scroll lock to prevent layout shifts on tablet
      lockBodyScroll();
      
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
    if (searchModal) {
      // Blur search input first to dismiss mobile keyboard
      if (searchInputRef) {
        searchInputRef.blur();
        searchInputRef.value = '';
      }
      
      searchModal.classList.remove('active');
      searchModal.setAttribute('aria-hidden', 'true');
      // Use robust scroll unlock to restore scroll position
      unlockBodyScroll();
      
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
      
      // Return focus to search button (only on desktop, not mobile to avoid keyboard issues)
      if (searchButton && window.innerWidth >= 768) {
        // Small delay to ensure input is blurred first
        setTimeout(() => {
          searchButton.focus();
        }, 100);
      }
    }
  }

  // Handle search modal submission
  function handleSearchModalSubmit() {
    if (!searchInputRef) return;
    
    const query = searchInputRef.value.trim();
    if (!query) return;
    
    // Check if we're on the courses page
    const isCoursesPage = window.location.pathname.includes('courses.html') || 
                          window.location.pathname.endsWith('courses.html') ||
                          window.location.pathname.endsWith('/courses');
    
    if (isCoursesPage) {
      // If on courses page, populate the courses search input and trigger search
      const coursesSearchInput = document.getElementById('course-search');
      if (coursesSearchInput) {
        coursesSearchInput.value = query;
        coursesSearchInput.dispatchEvent(new Event('input', { bubbles: true }));
        // Scroll to courses section if needed
        const coursesSection = document.getElementById('courses-filter-section');
        if (coursesSection) {
          coursesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    } else {
      // Navigate to courses page with search query
      const searchParams = new URLSearchParams();
      searchParams.set('search', query);
      window.location.href = `courses.html?${searchParams.toString()}`;
      return; // Don't close modal yet, let navigation happen
    }
    
    // Close modal after handling search
    closeSearchModal();
  }

  // Get course suggestions for autocomplete
  function getCourseSuggestions(query) {
    if (!query || query.trim() === '' || !window.CourseDataManager) {
      return [];
    }
    
    const courses = window.CourseDataManager.getCourses();
    const lowerQuery = query.toLowerCase().trim();
    const maxSuggestions = 8;
    
    // Score-based matching system for improved relevance
    const scoredMatches = courses.map(course => {
      let score = 0;
      const courseTitleLower = course.title.toLowerCase();
      const courseDescLower = (course.description || '').toLowerCase();
      const courseCategory = (course.category || '').toLowerCase();
      const courseTopics = (course.topics || []).map(t => t.toLowerCase());
      
      // Exact title match (highest priority - 100 points)
      if (courseTitleLower === lowerQuery) {
        score += 100;
      }
      // Title starts with query (high priority - 80 points)
      else if (courseTitleLower.startsWith(lowerQuery)) {
        score += 80;
      }
      // Title contains query (medium-high priority - 60 points)
      else if (courseTitleLower.includes(lowerQuery)) {
        score += 60;
      }
      
      // Category match (high priority - 70 points)
      if (courseCategory && courseCategory.includes(lowerQuery)) {
        score += 70;
      }
      
      // Topic/keyword match (medium priority - 40 points)
      if (courseTopics && courseTopics.some(topic => topic.includes(lowerQuery))) {
        score += 40;
      }
      
      // Description match (lower priority - 30 points)
      if (courseDescLower.includes(lowerQuery)) {
        score += 30;
      }
      
      return { course, score };
    })
    .filter(item => item.score > 0) // Only include matches
    .sort((a, b) => b.score - a.score) // Sort by score descending
    .slice(0, maxSuggestions) // Limit to max suggestions
    .map(item => item.course); // Extract course objects
    
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

  // Render autocomplete suggestions
  function renderAutocompleteSuggestions(query) {
    if (!searchAutocomplete) return;
    
    if (!query || query.trim() === '') {
      searchAutocomplete.style.display = 'none';
      searchAutocomplete.innerHTML = '';
      return;
    }
    
    const suggestions = getCourseSuggestions(query);
    
    if (suggestions.length === 0) {
      // Show "no results" message when there's a query but no matches
      searchAutocomplete.innerHTML = `
        <div class="search-autocomplete-empty" role="status" aria-live="polite">
          <i class="fas fa-search search-autocomplete-icon" aria-hidden="true"></i>
          <span class="search-autocomplete-text">
            No courses found for "${query}"
          </span>
        </div>
      `;
      searchAutocomplete.style.display = 'block';
      return;
    }
    
    searchAutocomplete.innerHTML = suggestions.map((course, index) => {
      const highlightedTitle = highlightMatch(course.title, query);
      const categoryDisplay = course.category ? 
        `<span class="search-autocomplete-category">${course.category}</span>` : '';
      const levelDisplay = course.level ? 
        `<span class="search-autocomplete-level">${course.level}</span>` : '';
      
      return `
        <button
          type="button"
          class="search-autocomplete-item"
          role="option"
          aria-selected="false"
          data-course-id="${course.id}"
          data-course-title="${course.title}"
          tabindex="0"
        >
          <i class="fas fa-search search-autocomplete-icon" aria-hidden="true"></i>
          <span class="search-autocomplete-text">
            ${highlightedTitle}
            <span class="search-autocomplete-meta">
              ${levelDisplay}
            ${categoryDisplay}
            </span>
          </span>
        </button>
      `;
    }).join('');
    
    searchAutocomplete.style.display = 'block';
    
    // Add click handlers to suggestions
    const suggestionItems = searchAutocomplete.querySelectorAll('.search-autocomplete-item');
    suggestionItems.forEach(item => {
      item.addEventListener('click', () => {
        const courseTitle = item.getAttribute('data-course-title');
        if (courseTitle && searchInputRef) {
          searchInputRef.value = courseTitle;
          handleSearchModalSubmit();
        }
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
    // Explicitly set display to block to ensure menu shows (inline style overrides CSS)
    menu.style.display = 'block';
    menu.style.visibility = 'visible';
    menu.classList.add('active');
    lockBodyScroll();
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
      // Explicitly hide menu to ensure it closes (inline style overrides CSS)
      menu.style.display = 'none';
      menu.style.visibility = 'hidden';
      unlockBodyScroll();
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
      
      const mailtoUrl = 'mailto:enquiries@continuitytrainingacademy.co.uk?subject=' + subject + '&body=' + body;
      
      // Generate custom thank you message for booking enquiry
      const thankYouMessage = "Thanks for your booking enquiry! We've received it and will be in touch soon.";
      const nextStepsMessage = "We'll send you booking details via email shortly.";
      
      // Show thank you popup with custom message first
      if (window.showThankYouModal) {
        window.showThankYouModal(thankYouMessage, {
          nextSteps: nextStepsMessage
        });
        
        // Open mailto link after a short delay to allow modal to render
        // This ensures the modal is visible before the email client opens
        setTimeout(function() {
          window.location.href = mailtoUrl;
        }, 300);
      } else {
        // Fallback: if modal function not available, open mailto directly
        window.location.href = mailtoUrl;
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

  // Exit-Intent Popup
  function initExitIntentPopup() {
    try {
      const exitModal = document.getElementById('exit-intent-modal');
      const exitForm = document.getElementById('exit-intent-form');
      const exitEmail = document.getElementById('exit-intent-email');
      const closeBtn = exitModal?.querySelector('.exit-intent-modal-close');
      const dismissBtn = exitModal?.querySelector('.exit-intent-dismiss');
      const backdrop = exitModal?.querySelector('.exit-intent-modal-backdrop');
      
      if (!exitModal) return;
      
      // Check if user has dismissed or submitted (localStorage)
      let exitIntentDismissed, exitIntentDate, today;
      try {
        exitIntentDismissed = localStorage.getItem('exitIntentDismissed');
        exitIntentDate = localStorage.getItem('exitIntentDate');
        today = new Date().toDateString();
      } catch (e) {
        // localStorage not available, continue without preference
        if (isDevelopment) console.warn('localStorage not available for exit-intent popup');
      }
      
      // Don't show if dismissed today
      if (exitIntentDismissed === 'true' && exitIntentDate === today) {
        return;
      }
      
      let hasTriggered = false;
      let lastMouseY = 0;
      let mouseoutTimeout = null;
      
      // Debounced exit-intent detection: mouse leaving viewport top
      function handleMouseOut(e) {
        if (hasTriggered) return;
        
        // Clear any existing timeout
        if (mouseoutTimeout) {
          clearTimeout(mouseoutTimeout);
        }
        
        // Debounce the detection
        mouseoutTimeout = setTimeout(() => {
          if (!e.relatedTarget && !e.toElement && e.clientY < 10) {
            // Mouse left viewport from top
            hasTriggered = true;
            showExitModal();
          }
        }, 100);
      }
      
      document.addEventListener('mouseout', handleMouseOut, false);
      
      // Track mouse Y position for better detection
      document.addEventListener('mousemove', (e) => {
        lastMouseY = e.clientY;
      }, { passive: true });
    
      function showExitModal() {
        try {
          exitModal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
          
          // Focus trap
          const focusableElements = exitModal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
          );
          const firstElement = focusableElements[0];
          const lastElement = focusableElements[focusableElements.length - 1];
          
          // Focus first element
          if (firstElement) {
            setTimeout(() => {
              try {
                firstElement.focus();
              } catch (e) {
                if (isDevelopment) console.warn('Could not focus exit-intent modal element:', e);
              }
            }, 100);
          }
          
          // Trap focus
          function trapFocus(e) {
            if (e.key !== 'Tab') return;
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
          
          exitModal.addEventListener('keydown', trapFocus);
          
          // Store trap function for cleanup
          exitModal._trapFocus = trapFocus;
        } catch (e) {
          if (isDevelopment) console.error('Error showing exit-intent modal:', e);
        }
      }
      
      function closeExitModal() {
        try {
          exitModal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
          
          // Remove focus trap
          if (exitModal._trapFocus) {
            exitModal.removeEventListener('keydown', exitModal._trapFocus);
            delete exitModal._trapFocus;
          }
        } catch (e) {
          if (isDevelopment) console.error('Error closing exit-intent modal:', e);
        }
      }
      
      function savePreference() {
        try {
          if (typeof localStorage !== 'undefined') {
            localStorage.setItem('exitIntentDismissed', 'true');
            localStorage.setItem('exitIntentDate', today);
          }
        } catch (e) {
          if (isDevelopment) console.warn('Could not save exit-intent preference:', e);
        }
      }
      
      // Close button
      if (closeBtn) {
        closeBtn.addEventListener('click', () => {
          savePreference();
          closeExitModal();
        });
      }
      
      // Dismiss button
      if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
          savePreference();
          closeExitModal();
        });
      }
      
      // Backdrop click
      if (backdrop) {
        backdrop.addEventListener('click', () => {
          savePreference();
          closeExitModal();
        });
      }
      
      // ESC key
      function handleEscape(e) {
        if (e.key === 'Escape' && exitModal.getAttribute('aria-hidden') === 'false') {
          closeExitModal();
        }
      }
      
      document.addEventListener('keydown', handleEscape);
      
      // Form submission
      if (exitForm) {
        exitForm.addEventListener('submit', (e) => {
          e.preventDefault();
          try {
            const email = exitEmail?.value;
            
            if (email) {
              // Store submission
              if (typeof localStorage !== 'undefined') {
                localStorage.setItem('exitIntentSubmitted', 'true');
                localStorage.setItem('exitIntentDate', today);
              }
              
              // Here you would typically send to your email service
              // For now, just close and show thank you
              closeExitModal();
              
              // Optionally show thank you message
              // You could trigger the existing thank-you-modal here
            }
          } catch (e) {
            if (isDevelopment) console.error('Error submitting exit-intent form:', e);
          }
        });
      }
    } catch (e) {
      if (isDevelopment) console.error('Error initializing exit-intent popup:', e);
    }
  }
  
  // Initialize exit-intent popup on homepage
  if (document.getElementById('exit-intent-modal')) {
    initExitIntentPopup();
  }

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
  // Footer Newsletter Form (static-site)
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

      if (consentCheckbox && !consentCheckbox.checked) {
        consentCheckbox.focus();
        return;
      }

      const email = emailInput?.value?.trim() ?? '';
      if (!email) {
        emailInput?.focus();
        return;
      }

      // No backend yet on the static site: acknowledge and reset.
      if (typeof window.showThankYouModal === 'function') {
        window.showThankYouModal(
          "You're all set! We're excited to share training insights and CQC updates with you.",
          { nextSteps: "Keep an eye on your inbox. We'll send you the latest news and training opportunities. You can unsubscribe anytime." }
        );
      } else {
        alert("You're all set! We're excited to share training insights and CQC updates with you.");
      }

      form.reset();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFooterNewsletterForm);
  } else {
    initFooterNewsletterForm();
  }

})();
