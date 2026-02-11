/**
 * Import CTA Data admin page: course filter and small UX tweaks
 */
(function () {
  'use strict';

  var courseSelect = document.getElementById('cta-populate-selected-courses');
  var courseSearch = document.getElementById('cta-populate-course-search');

  if (courseSelect && courseSearch) {
    var options = Array.from(courseSelect.querySelectorAll('option'));
    var allOption = options.find(function (opt) { return opt.value === 'all'; });
    var courseOptions = options.filter(function (opt) { return opt.value !== 'all'; });

    function filterCourses() {
      var q = (courseSearch.value || '').trim().toLowerCase();
      courseOptions.forEach(function (opt) {
        var show = !q || opt.textContent.toLowerCase().indexOf(q) !== -1;
        opt.style.display = show ? '' : 'none';
        opt.disabled = show ? false : true;
      });
      if (allOption) {
        var anyVisible = courseOptions.some(function (opt) { return opt.style.display !== 'none'; });
        allOption.style.display = anyVisible ? '' : 'none';
        allOption.disabled = !anyVisible;
      }
    }

    courseSearch.addEventListener('input', filterCourses);
    courseSearch.addEventListener('change', filterCourses);
  }
})();
