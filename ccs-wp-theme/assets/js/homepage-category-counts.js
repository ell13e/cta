/**
 * Homepage Category Counts
 * Updates the course count numbers on category cards on the homepage
 */

(function() {
  'use strict';

  // Development mode flag
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  function updateHomepageCategoryCounts() {
    // Ensure CourseDataManager is available
    if (!window.CourseDataManager) {
      if (isDevelopment) console.warn('CourseDataManager not loaded. Category counts will not be updated.');
      return;
    }

    // Get all courses
    const courses = window.CourseDataManager.getCourses();
    if (!courses || courses.length === 0) {
      if (isDevelopment) console.warn('No courses data available.');
      return;
    }

    // Get category configuration
    const categories = window.CourseDataManager.getCategoryConfig();

    // Count courses per category
    const counts = {};
    
    categories.forEach(category => {
      if (category.key === 'all') {
        counts[category.key] = courses.length;
        return;
      }

      // Count courses that belong to this topic category
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

    // Update all category count elements on the homepage
    // The category.key matches the urlParam (both are generated from topicCategoryToKey)
    categories.forEach(category => {
      if (category.key === 'all') return;
      
      // Find elements by key (data-category attribute uses the same key format)
      const countElements = document.querySelectorAll(
        `.category-count-new[data-category="${category.key}"]`
      );
      
      const count = counts[category.key] || 0;
      countElements.forEach(element => {
        if (count === 1) {
          element.textContent = '1 Course';
        } else {
          element.textContent = `${count} Courses`;
        }
      });
    });
  }

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateHomepageCategoryCounts);
  } else {
    // DOM is already ready
    updateHomepageCategoryCounts();
  }
})();

