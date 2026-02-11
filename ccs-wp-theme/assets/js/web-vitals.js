/**
 * Core Web Vitals Tracking
 * 
 * This script tracks Core Web Vitals and sends them to Google Analytics 4.
 * Make sure GA4 is loaded before this script.
 * 
 * Usage: Add this script after GA4 initialization in the <head>
 */

(function() {
  'use strict';

  // Development mode flag
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  // Check if gtag is available
  if (typeof gtag === 'undefined') {
    if (isDevelopment) console.warn('Google Analytics (gtag) not found. Core Web Vitals tracking disabled.');
    return;
  }

  // Import Web Vitals library
  function sendToAnalytics(metric) {
    // Send to Google Analytics 4
    gtag('event', metric.name, {
      value: Math.round(metric.name === 'CLS' ? metric.value * 1000 : metric.value),
      event_category: 'Web Vitals',
      event_label: metric.id,
      non_interaction: true,
    });

    // Log to console in development
    if (isDevelopment) {
      // Log Web Vitals in development only
    }
  }

  // Load Web Vitals library and track metrics
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      loadWebVitals();
    });
  } else {
    loadWebVitals();
  }

  function loadWebVitals() {
    // Use dynamic import for Web Vitals
    import('https://unpkg.com/web-vitals@3?module').then(function(webVitals) {
      webVitals.getCLS(sendToAnalytics);
      webVitals.getFID(sendToAnalytics);
      webVitals.getFCP(sendToAnalytics);
      webVitals.getLCP(sendToAnalytics);
      webVitals.getTTFB(sendToAnalytics);
    }).catch(function(error) {
      if (isDevelopment) console.warn('Failed to load Web Vitals library:', error);
    });
  }

  // Track page load performance
  window.addEventListener('load', function() {
    if (typeof performance === 'undefined' || !performance.timing) {
      return;
    }

    const perfData = performance.timing;
    const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
    const domReadyTime = perfData.domContentLoadedEventEnd - perfData.navigationStart;
    const connectTime = perfData.responseEnd - perfData.requestStart;

    // Send to analytics
    gtag('event', 'page_load_time', {
      value: Math.round(pageLoadTime),
      event_category: 'Performance',
      non_interaction: true,
    });

    gtag('event', 'dom_ready_time', {
      value: Math.round(domReadyTime),
      event_category: 'Performance',
      non_interaction: true,
    });

    gtag('event', 'connect_time', {
      value: Math.round(connectTime),
      event_category: 'Performance',
      non_interaction: true,
    });
  });

  // Track form submission performance
  const form = document.getElementById('contact-form');
  if (form) {
    let formSubmissionStartTime = null;

    form.addEventListener('submit', function() {
      formSubmissionStartTime = performance.now();
    });

    // Track when form submission completes
    // This should be called from the form submission handler
    window.trackFormSubmission = function() {
      if (formSubmissionStartTime) {
        const endTime = performance.now();
        const duration = endTime - formSubmissionStartTime;

        gtag('event', 'form_submission_time', {
          value: Math.round(duration),
          event_category: 'Form Performance',
          non_interaction: true,
        });

        formSubmissionStartTime = null;
      }
    };
  }
})();

