/**
 * Privacy Policy Page - Navigation and Scroll Spy
 * Handles smooth scrolling, active link highlighting, and sticky nav
 */

(function() {
  'use strict';

  const sectionNodes = document.querySelectorAll('[data-privacy-section]');
  const navLinks = document.querySelectorAll('.cta-nav-link');
  const pillLinks = document.querySelectorAll('.cta-privacy-pill');
  const mobileNav = document.querySelector('.cta-privacy-mobile-nav');
  const header = document.querySelector('.site-header');

  if (!sectionNodes.length) return;

  // Get header height from CSS custom property or measure it
  function getHeaderHeight() {
    const cssVar = getComputedStyle(document.documentElement).getPropertyValue('--header-height-mobile');
    if (cssVar) return parseInt(cssVar, 10);
    return header ? header.offsetHeight : 72;
  }

  // Set scroll offset CSS custom property for CSS scroll-margin-top
  function updateScrollOffset() {
    const headerHeight = getHeaderHeight();
    const mobileNavHeight = mobileNav ? mobileNav.offsetHeight : 0;
    const offset = headerHeight + mobileNavHeight + 16;
    document.documentElement.style.setProperty('--privacy-scroll-offset', offset + 'px');
  }

  // Initialize scroll offset
  updateScrollOffset();
  window.addEventListener('resize', updateScrollOffset);

  // Smooth scroll for sidebar links
  navLinks.forEach(function(link) {
    link.addEventListener('click', function(e) {
      const targetId = link.getAttribute('href');
      if (!targetId || !targetId.startsWith('#')) return;

      e.preventDefault();
      const target = document.querySelector(targetId);
      if (!target) return;

      // Set aria-current immediately on click (before scroll animation)
      navLinks.forEach(function(l) {
        l.classList.remove('active');
        l.removeAttribute('aria-current');
      });
      link.classList.add('active');
      link.setAttribute('aria-current', 'true');

      const headerHeight = getHeaderHeight();
      const offset = headerHeight + 12;
      const rect = target.getBoundingClientRect();
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

      window.scrollTo({
        top: rect.top + scrollTop - offset,
        behavior: 'smooth'
      });

      // Update URL hash without jumping
      history.pushState(null, '', targetId);

      // Move focus to the section for accessibility
      target.setAttribute('tabindex', '-1');
      target.focus({ preventScroll: true });
    });
  });

  // Smooth scroll for mobile pill links
  pillLinks.forEach(function(pill) {
    pill.addEventListener('click', function(e) {
      const targetId = pill.getAttribute('href');
      if (!targetId || !targetId.startsWith('#')) return;

      e.preventDefault();
      const target = document.querySelector(targetId);
      if (!target) return;

      // Set aria-current immediately on click (before scroll animation)
      pillLinks.forEach(function(p) {
        p.classList.remove('active');
        p.removeAttribute('aria-current');
      });
      pill.classList.add('active');
      pill.setAttribute('aria-current', 'true');

      // Account for sticky header and mobile nav height
      const headerHeight = getHeaderHeight();
      const mobileNavHeight = mobileNav ? mobileNav.offsetHeight : 0;
      const offset = headerHeight + mobileNavHeight + 16;
      const rect = target.getBoundingClientRect();
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

      window.scrollTo({
        top: rect.top + scrollTop - offset,
        behavior: 'smooth'
      });

      history.pushState(null, '', targetId);
      target.setAttribute('tabindex', '-1');
      target.focus({ preventScroll: true });
    });
  });

  // Scroll spy: highlight current section in both navs
  function updateActiveLink() {
    let currentId = null;
    const headerHeight = getHeaderHeight();
    const mobileNavHeight = mobileNav ? mobileNav.offsetHeight : 0;
    const scrollPosition = window.scrollY + headerHeight + mobileNavHeight + 50;

    sectionNodes.forEach(function(section) {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;

      if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
        currentId = section.id;
      }
    });

    // If we're near the bottom, activate the last section
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100) {
      currentId = sectionNodes[sectionNodes.length - 1].id;
    }

    // If we're at the top, activate the first section
    if (window.scrollY < 300) {
      currentId = sectionNodes[0].id;
    }

    if (!currentId) return;

    // Update sidebar nav links
    navLinks.forEach(function(link) {
      const href = link.getAttribute('href');
      if (href === '#' + currentId) {
        link.classList.add('active');
        link.setAttribute('aria-current', 'true');
      } else {
        link.classList.remove('active');
        link.removeAttribute('aria-current');
      }
    });

    // Update mobile pill links
    pillLinks.forEach(function(pill) {
      const href = pill.getAttribute('href');
      if (href === '#' + currentId) {
        pill.classList.add('active');
        pill.setAttribute('aria-current', 'true');
      } else {
        pill.classList.remove('active');
        pill.removeAttribute('aria-current');
      }
    });
  }

  // Handle sticky state shadow
  function updateStickyState() {
    if (!mobileNav) return;
    
    // Get the original position of the mobile nav
    const navRect = mobileNav.getBoundingClientRect();
    const headerHeight = getHeaderHeight();
    
    // Add shadow when stuck (when top is at or below header height)
    if (navRect.top <= headerHeight + 1) {
      mobileNav.classList.add('is-stuck');
    } else {
      mobileNav.classList.remove('is-stuck');
    }
  }

  // Throttle scroll events for performance
  let ticking = false;
  function onScroll() {
    if (!ticking) {
      window.requestAnimationFrame(function() {
        updateActiveLink();
        updateStickyState();
        ticking = false;
      });
      ticking = true;
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('load', function() {
    updateActiveLink();
    updateStickyState();
  });

  // Handle direct navigation to hash on page load
  if (window.location.hash) {
    const target = document.querySelector(window.location.hash);
    if (target) {
      setTimeout(function() {
        const headerHeight = getHeaderHeight();
        const mobileNavHeight = mobileNav ? mobileNav.offsetHeight : 0;
        const offset = window.innerWidth < 1024 ? headerHeight + mobileNavHeight + 16 : headerHeight + 12;
        const rect = target.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        window.scrollTo({
          top: rect.top + scrollTop - offset,
          behavior: 'smooth'
        });
      }, 100);
    }
  }
})();

