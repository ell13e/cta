// Set copyright year
(function() {
  'use strict';
  const year = String(new Date().getFullYear());
  // Support both ids during the transition to a standardized footer.
  const targets = ['footer-year', 'footer-year-modern'];
  for (const id of targets) {
    const el = document.getElementById(id);
    if (el) el.textContent = year;
  }
})();

