/**
 * Latest News Loader
 * Dynamically loads and displays the latest 4 blog articles
 * Always shows the most recent articles based on date
 */

(function() {
  'use strict';

  // Development mode flag
  const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

  // Format date to readable format (e.g., "FEB 9, 2025")
  function formatDate(dateString) {
    const date = new Date(dateString);
    const months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    const month = months[date.getMonth()];
    const day = date.getDate();
    const year = date.getFullYear();
    return `${month} ${day}, ${year}`;
  }

  // Load and display latest articles
  async function loadLatestNews() {
    try {
      const response = await fetch('assets/data/news-articles.json');
      if (!response.ok) {
        throw new Error('Failed to load blog articles');
      }

      const articles = await response.json();

      // Sort articles by date (newest first) and get latest 4
      const sortedArticles = articles
        .sort((a, b) => new Date(b.date) - new Date(a.date))
        .slice(0, 4);

      if (sortedArticles.length === 0) {
        if (isDevelopment) console.warn('No articles found');
        return;
      }

      // First article is featured (left side)
      const featuredArticle = sortedArticles[0];
      const categoryArticles = sortedArticles.slice(1, 4); // Next 3 for right side

      // Update featured article
      updateFeaturedArticle(featuredArticle);

      // Update category articles
      updateCategoryArticles(categoryArticles);

    } catch (error) {
      if (isDevelopment) console.error('Error loading latest news:', error);
      // Keep the static content as fallback
    }
  }

  // Update the featured article (left side)
  function updateFeaturedArticle(article) {
    const featuredSection = document.querySelector('.latest-news-featured');
    if (!featuredSection) return;

    // Update image
    const img = featuredSection.querySelector('.latest-news-featured-image');
    if (img) {
      img.src = article.image;
      img.alt = article.title;
    }

    // Update meta
    const meta = featuredSection.querySelector('.latest-news-featured-meta');
    if (meta) {
      meta.textContent = `${article.category} / ${formatDate(article.date)}`;
    }

    // Update title
    const title = featuredSection.querySelector('.latest-news-featured-title');
    if (title) {
      title.textContent = article.title;
    }

    // Update excerpt
    const excerpt = featuredSection.querySelector('.latest-news-featured-excerpt');
    if (excerpt) {
      excerpt.textContent = article.excerpt;
    }

    // Update link
    const link = featuredSection.querySelector('.latest-news-featured-link');
    if (link) {
      link.href = article.url;
      link.setAttribute('aria-label', `Read full article: ${article.title}`);
    }
  }

  // Update category articles (right side)
  function updateCategoryArticles(articles) {
    const categoryCards = document.querySelectorAll('.latest-news-category-card');
    
    articles.forEach((article, index) => {
      if (index >= categoryCards.length) return;

      const card = categoryCards[index];

      // Update image
      const img = card.querySelector('.latest-news-category-image');
      if (img) {
        img.src = article.image;
        img.alt = article.title;
      }

      // Update title
      const title = card.querySelector('.latest-news-category-title');
      if (title) {
        title.textContent = article.categoryName || article.title;
      }

      // Update excerpt
      const excerpt = card.querySelector('.latest-news-category-excerpt');
      if (excerpt) {
        excerpt.textContent = article.excerpt;
      }

      // Update link
      const link = card.querySelector('.latest-news-category-link');
      if (link) {
        link.href = article.url;
        link.setAttribute('aria-label', `Read more: ${article.title}`);
      }
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadLatestNews);
  } else {
    loadLatestNews();
  }
})();

