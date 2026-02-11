/**
 * Site Includes and Dynamic Navigation
 * 
 * Features:
 * 1. HTML includes via data-include attribute
 * 2. Dynamic navigation rendering from SITE_CONFIG
 * 3. Footer rendering from SITE_CONFIG
 * 4. Active nav state management
 */

// Simple HTML includes: <div data-include="partials/header.html"></div>
// Supports both absolute (/partials/header.html) and relative (partials/header.html) paths
(async () => {
  const slots = document.querySelectorAll('[data-include]');
  for (const el of slots) {
    let file = el.getAttribute('data-include');
    // If path doesn't start with /, make it relative to current page
    if (!file.startsWith('/')) {
      const currentPath = window.location.pathname;
      const currentDir = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
      file = currentDir + file;
    }
    try {
      const res = await fetch(file, {cache:'no-cache'});
      if (!res.ok) throw new Error('HTTP '+res.status);
      const html = await res.text();
      el.outerHTML = html;
    } catch (e) {
      // Only log in development to avoid console clutter
      if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      console.warn('Include failed for', file, e);
      }
    }
  }
})();

/**
 * Navigation and Footer Rendering
 * Run after DOM is ready and SITE_CONFIG is available
 */
document.addEventListener('DOMContentLoaded', function() {
  // Only run if SITE_CONFIG is available
  if (typeof SITE_CONFIG === 'undefined') {
    setHeaderOverflowHandler();
    return;
  }

  // Render footer elements if containers exist
  renderFooterQuickLinks();
  renderFooterTrainingAreas();
  renderFooterContact();
  renderFooterSocial();
  renderFooterLegal();
  
  // Set active navigation state
  setActiveNavState();

  // Handle header overflow -> switch to hamburger when needed
  setHeaderOverflowHandler();
});

/**
 * Render footer quick links
 */
function renderFooterQuickLinks() {
  const container = document.getElementById('footer-quick-links');
  if (!container || !SITE_CONFIG.footer.quickLinks) return;

  container.innerHTML = SITE_CONFIG.footer.quickLinks.map(link => 
    `<li><a href="${link.href}" class="footer-link">${link.label}</a></li>`
  ).join('');
}

/**
 * Render footer training areas from categories
 */
function renderFooterTrainingAreas() {
  const container = document.getElementById('footer-training-areas');
  if (!container || typeof CategoryHelpers === 'undefined') return;

  const categories = CategoryHelpers.getForFooter();
  container.innerHTML = categories.map(cat =>
    `<li><a href="courses.html?category=${cat.urlParam}" class="footer-link">${cat.labelFull}</a></li>`
  ).join('');
}

/**
 * Render footer contact information
 */
function renderFooterContact() {
  const container = document.getElementById('footer-contact');
  if (!container || !SITE_CONFIG.footer.contact) return;

  const c = SITE_CONFIG.footer.contact;
  container.innerHTML = `
    <li>
      <a href="${c.phoneHref}" class="footer-contact-link" aria-label="Call us at ${c.phone}">
        <i class="fas fa-phone" aria-hidden="true"></i>
        <span lang="en-GB">${c.phone}</span>
      </a>
    </li>
    <li>
      <a href="mailto:${c.email}" class="footer-contact-link" aria-label="Email us">
        <i class="fas fa-envelope" aria-hidden="true"></i>
        <span>${c.email}</span>
      </a>
    </li>
    <li>
      <div class="footer-address">
        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
        <address class="footer-address-text">${c.address.join('<br>')}</address>
      </div>
    </li>
  `;
}

/**
 * Render footer social links
 */
function renderFooterSocial() {
  const container = document.getElementById('footer-social');
  if (!container || !SITE_CONFIG.footer.social) return;

  container.innerHTML = SITE_CONFIG.footer.social.map(s => `
    <a href="${s.url}" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Visit our ${s.label} page">
      <i class="fab ${s.icon}" aria-hidden="true"></i>
    </a>
  `).join('');
}

/**
 * Render footer legal links
 */
function renderFooterLegal() {
  const container = document.getElementById('footer-legal');
  if (!container || !SITE_CONFIG.footer.legalLinks) return;

  container.innerHTML = SITE_CONFIG.footer.legalLinks.map(link =>
    `<li><a href="${link.href}" class="footer-legal-link">${link.label}</a></li>`
  ).join('');
}

/**
 * Set active state on navigation links based on current URL
 */
function setActiveNavState() {
  const currentPath = window.location.pathname;
  const currentPage = currentPath.split('/').pop() || 'index.html';
  const currentPageBase = currentPage.split('?')[0]; // Remove query params
  
  // Desktop nav links - exclude category sub-links
  const navLinks = document.querySelectorAll('.nav-link, .mobile-menu-link:not(.mobile-menu-link-sub)');
  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (href) {
      const linkPage = href.split('?')[0]; // Remove query params
      const linkPageBase = linkPage.split('#')[0]; // Remove hash
      
      // Check for exact match or index.html special case
      if (linkPageBase === currentPageBase || 
          (currentPageBase === '' && linkPageBase === 'index.html') ||
          (currentPageBase === 'index.html' && linkPageBase === 'index.html')) {
        link.classList.add('nav-link-active');
        link.setAttribute('aria-current', 'page');
      } else {
        link.classList.remove('nav-link-active');
        link.removeAttribute('aria-current');
      }
    }
  });
  
  // Explicitly remove active state from category sub-links when on courses.html with no category filter
  if (currentPageBase === 'courses.html' && !window.location.search.includes('category=')) {
    const categoryLinks = document.querySelectorAll('.mobile-menu-link-sub');
    categoryLinks.forEach(link => {
      link.classList.remove('nav-link-active');
      link.removeAttribute('aria-current');
    });
  }
  
  // Also handle parent links (e.g., if on courses.html, highlight "Courses" in nav)
  if (currentPageBase === 'courses.html' || currentPageBase.startsWith('course-')) {
    // Match courses.html specifically, but NOT upcoming-courses.html
    // Check both regular nav-link and dropdown text link
    const coursesNavLink = document.querySelector('.nav-link[href="courses.html"], .nav-link[href="/courses.html"], .nav-link-dropdown-text[href="courses.html"], .nav-link-dropdown-text[href="/courses.html"]');
    if (coursesNavLink) {
      coursesNavLink.classList.add('nav-link-active');
      coursesNavLink.setAttribute('aria-current', 'page');
      // Also mark the parent button if it's a dropdown
      const parentButton = coursesNavLink.closest('.nav-link-dropdown');
      if (parentButton) {
        parentButton.classList.add('nav-link-active');
      }
    }
  }
  
  // Handle upcoming-courses.html specifically
  if (currentPageBase === 'upcoming-courses.html') {
    const upcomingNavLink = document.querySelector('.nav-link[href="upcoming-courses.html"], .nav-link[href="/upcoming-courses.html"]');
    if (upcomingNavLink) {
      upcomingNavLink.classList.add('nav-link-active');
      upcomingNavLink.setAttribute('aria-current', 'page');
    }
    // Make sure courses.html link is NOT active
    const coursesNavLink = document.querySelector('.nav-link[href="courses.html"], .nav-link[href="/courses.html"], .nav-link-dropdown-text[href="courses.html"], .nav-link-dropdown-text[href="/courses.html"]');
    if (coursesNavLink) {
      coursesNavLink.classList.remove('nav-link-active');
      coursesNavLink.removeAttribute('aria-current');
      const parentButton = coursesNavLink.closest('.nav-link-dropdown');
      if (parentButton) {
        parentButton.classList.remove('nav-link-active');
      }
    }
  }
  
  if (currentPageBase === 'events.html' || currentPageBase === 'event-detail.html') {
    const eventsNavLink = document.querySelector('.nav-link[href*="events"]');
    if (eventsNavLink) {
      eventsNavLink.classList.add('nav-link-active');
      eventsNavLink.setAttribute('aria-current', 'page');
    }
  }
  
  if (currentPageBase === 'news.html' || currentPageBase === 'post-template.html') {
    const blogNavLink = document.querySelector('.nav-link[href*="news"]');
    if (blogNavLink) {
      blogNavLink.classList.add('nav-link-active');
      blogNavLink.setAttribute('aria-current', 'page');
    }
  }
}

/**
 * Toggle header to hamburger when the header content overflows its container.
 * Uses both resize and initial load checks to ensure correctness on all viewports.
 */
function setHeaderOverflowHandler() {
  const header = document.querySelector('.site-header');
  const inner = header?.querySelector('.header-inner-wrapper');
  const navDesktop = header?.querySelector('.nav-desktop');
  const mobileToggle = header?.querySelector('.mobile-menu-btn');
  if (!header || !inner || !navDesktop || !mobileToggle) return;

  const update = () => {
    const hasOverflow = inner.scrollWidth > inner.clientWidth;
    header.classList.toggle('header--overflow', hasOverflow);
  };

  window.addEventListener('resize', update);
  // Defer to allow fonts/layout to settle
  window.requestAnimationFrame(update);
  // Run again after load (fonts/images) and a short delay for safety
  window.addEventListener('load', update);
  setTimeout(update, 300);
}

/**
 * Render category pill HTML
 * Can be called from any page to generate category pills
 * @param {string} categoryId - Category ID
 * @param {Object} options - { linkable: boolean, size: 'sm'|'lg'|'' }
 * @returns {string} HTML string
 */
function renderCategoryPill(categoryId, options = {}) {
  if (typeof CategoryHelpers === 'undefined') return '';
  return CategoryHelpers.renderPill(categoryId, options);
}
