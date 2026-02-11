/**
 * News Article Page JavaScript
 * Handles article loading, related articles, and newsletter subscription
 * 
 * NOTE: This script is for static site only. WordPress renders posts server-side.
 * If this script is loaded on WordPress, it will exit early to prevent conflicts.
 */

// Generate article slug from title
function generateArticleSlug(title) {
  if (!title) return '';
  return title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');
}

// Get article slug or ID from URL
function getArticleSlugFromUrl() {
  const path = window.location.pathname;
  
  // Check for slug format: /[slug].html
  const slugMatch = path.match(/\/([^\/]+)\.html$/);
  if (slugMatch) {
    const slug = slugMatch[1];
    // Exclude known non-article pages
    if (!['news', 'index', 'courses', 'about', 'contact'].includes(slug)) {
      return slug;
    }
  }
  
  // Fallback to old format: news-article-[id].html
  const idMatch = path.match(/news-article-(\d+)\.html/);
  if (idMatch) {
    return parseInt(idMatch[1], 10);
  }
  
  return null;
}

// Load article data by slug or ID
function loadArticle(slugOrId) {
  // Find article in newsArticles array (from news.js)
  if (typeof newsArticles === 'undefined') {
    console.error('newsArticles not found');
    return null;
  }

  // Try to find by slug first
  let article = newsArticles.find(a => a.slug === slugOrId);
  
  // If not found by slug, try by ID (for backward compatibility)
  if (!article && typeof slugOrId === 'number') {
    article = newsArticles.find(a => a.id === slugOrId);
  }
  
  // If still not found and slugOrId is a string, try generating slug from title
  if (!article && typeof slugOrId === 'string') {
    article = newsArticles.find(a => {
      const generatedSlug = generateArticleSlug(a.title);
      return generatedSlug === slugOrId || a.slug === slugOrId;
    });
  }
  
  return article || null;
}

// Render article content
function renderArticle(article) {
  if (!article) return;

  // Update meta tags
  const titleMeta = document.getElementById('article-title-meta');
  if (titleMeta) {
    titleMeta.textContent = `${article.title} - Continuity of Care Services`;
  }

  // Update breadcrumb
  const categoryBreadcrumb = document.getElementById('article-category-breadcrumb');
  if (categoryBreadcrumb) {
    categoryBreadcrumb.textContent = article.category;
  }

  // Update category badge
  const categoryBadge = document.getElementById('article-category');
  if (categoryBadge) {
    categoryBadge.textContent = article.category;
  }

  // Update title
  const title = document.getElementById('article-title');
  if (title) {
    title.textContent = article.title;
  }

  // Update excerpt
  const excerpt = document.getElementById('article-excerpt');
  if (excerpt) {
    excerpt.textContent = article.excerpt;
  }

  // Update date
  const date = document.getElementById('article-date');
  if (date) {
    date.textContent = formatDate(article.date, 'short');
    date.setAttribute('datetime', article.date);
  }

  // Update read time
  const readTime = document.getElementById('article-read-time');
  if (readTime) {
    readTime.textContent = article.readTime;
  }

  // Update featured image
  const featuredImage = document.getElementById('article-featured-image');
  if (featuredImage) {
    featuredImage.src = article.image;
    featuredImage.alt = article.title;
  }

  // Update Open Graph and Twitter meta tags
  updateMetaTags(article);
}

// Update meta tags for social sharing
function updateMetaTags(article) {
  // Open Graph
  const ogTitle = document.querySelector('meta[property="og:title"]');
  if (ogTitle) ogTitle.setAttribute('content', `${article.title} - Continuity of Care Services`);

  const ogDescription = document.querySelector('meta[property="og:description"]');
  if (ogDescription) ogDescription.setAttribute('content', article.excerpt);

  const ogImage = document.querySelector('meta[property="og:image"]');
  if (ogImage) ogImage.setAttribute('content', `https://continuitytrainingacademy.co.uk/${article.image}`);

  const ogUrl = document.querySelector('meta[property="og:url"]');
  if (ogUrl) ogUrl.setAttribute('content', window.location.href);

  // Twitter
  const twitterTitle = document.querySelector('meta[name="twitter:title"]');
  if (twitterTitle) twitterTitle.setAttribute('content', article.title);

  const twitterDescription = document.querySelector('meta[name="twitter:description"]');
  if (twitterDescription) twitterDescription.setAttribute('content', article.excerpt);

  const twitterImage = document.querySelector('meta[name="twitter:image"]');
  if (twitterImage) twitterImage.setAttribute('content', `https://continuitytrainingacademy.co.uk/${article.image}`);
}

// Render related articles
function renderRelatedArticles(currentArticleId) {
  if (typeof newsArticles === 'undefined') {
    return;
  }

  const currentArticle = newsArticles.find(a => a.id === currentArticleId);
  if (!currentArticle) return;

  // Get related articles (same category, excluding current article)
  let relatedArticles = newsArticles
    .filter(a => a.id !== currentArticleId && a.category === currentArticle.category)
    .slice(0, 4);

  // Only show articles if we have at least one in the same category
  // Don't fill with unrelated articles - better to show fewer but relevant ones
  if (relatedArticles.length === 0) {
    const grid = document.getElementById('related-articles-grid');
    if (grid) {
      grid.innerHTML = '<p>No related articles found.</p>';
    }
    return;
  }

  const grid = document.getElementById('related-articles-grid');
  if (!grid) return;

  grid.innerHTML = relatedArticles.map(article => `
    <article class="news-article-card">
      <div class="news-article-image-wrapper">
        <img
          src="${article.image}"
          alt="${article.title}"
          class="news-article-image"
        />
      </div>
      <div class="news-article-content">
        <div class="news-article-meta">
          <span class="news-category-badge">${article.category}</span>
          <span class="news-article-date">${formatDate(article.date, 'short')}</span>
          <span class="news-read-time">${article.readTime}</span>
        </div>
        <h3 class="news-article-title">${article.title}</h3>
        <p class="news-article-excerpt">${article.excerpt}</p>
        <a href="${article.slug ? article.slug + '.html' : 'news-article-' + article.id + '.html'}" class="news-article-link">
          Read More
          ${getArrowRightIcon('news-arrow-icon-small')}
        </a>
      </div>
    </article>
  `).join('');
}

// Handle related articles navigation (simple scroll for now)
function setupRelatedArticlesNav() {
  const prevBtn = document.getElementById('related-prev');
  const nextBtn = document.getElementById('related-next');
  const grid = document.getElementById('related-articles-grid');

  if (!prevBtn || !nextBtn || !grid) return;

  // Simple scroll implementation
  prevBtn.addEventListener('click', () => {
    grid.scrollBy({ left: -300, behavior: 'smooth' });
  });

  nextBtn.addEventListener('click', () => {
    grid.scrollBy({ left: 300, behavior: 'smooth' });
  });
}

// Share article functionality
function setupShareButtons(article) {
  if (!article) return;

  const currentUrl = window.location.href;
  const title = article.title;
  const description = article.excerpt;

  // Save/Bookmark button
  const saveBtn = document.getElementById('news-article-save-btn');
  if (saveBtn) {
    saveBtn.addEventListener('click', () => {
      // Check if Web Share API is available (for mobile)
      if (navigator.share) {
        navigator.share({
          title: title,
          text: description,
          url: currentUrl
        }).catch(err => {
          console.log('Error sharing:', err);
          // Fallback: copy to clipboard
          copyToClipboard(currentUrl, saveBtn);
        });
      } else {
        // Fallback: copy to clipboard
        copyToClipboard(currentUrl, saveBtn);
      }
    });
  }

  // Facebook share
  const facebookBtn = document.getElementById('news-article-facebook-btn');
  if (facebookBtn) {
    facebookBtn.addEventListener('click', () => {
      const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`;
      window.open(facebookUrl, 'facebook-share', 'width=600,height=400,menubar=no,toolbar=no,resizable=yes,scrollbars=yes');
    });
  }

  // LinkedIn share
  const linkedinBtn = document.getElementById('news-article-linkedin-btn');
  if (linkedinBtn) {
    linkedinBtn.addEventListener('click', () => {
      const linkedinUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(currentUrl)}`;
      window.open(linkedinUrl, 'linkedin-share', 'width=600,height=400,menubar=no,toolbar=no,resizable=yes,scrollbars=yes');
    });
  }

  // Twitter share
  const twitterBtn = document.getElementById('news-article-twitter-btn');
  if (twitterBtn) {
    twitterBtn.addEventListener('click', () => {
      const twitterUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(currentUrl)}&text=${encodeURIComponent(title)}`;
      window.open(twitterUrl, 'twitter-share', 'width=600,height=400,menubar=no,toolbar=no,resizable=yes,scrollbars=yes');
    });
  }
}

// Copy URL to clipboard
function copyToClipboard(text, button) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      showCopyFeedback(button);
    }).catch(err => {
      console.error('Failed to copy:', err);
      fallbackCopyToClipboard(text, button);
    });
  } else {
    fallbackCopyToClipboard(text, button);
  }
}

// Fallback copy method
function fallbackCopyToClipboard(text, button) {
  const textArea = document.createElement('textarea');
  textArea.value = text;
  textArea.style.position = 'fixed';
  textArea.style.left = '-999999px';
  textArea.style.top = '-999999px';
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();
  
  try {
    document.execCommand('copy');
    showCopyFeedback(button);
  } catch (err) {
    console.error('Fallback copy failed:', err);
    alert('Unable to copy link. Please copy manually: ' + text);
  }
  
  document.body.removeChild(textArea);
}

// Show visual feedback when URL is copied
function showCopyFeedback(button) {
  const icon = button.querySelector('i');
  const originalClass = icon.className;
  
  // Change icon to checkmark
  icon.className = 'fas fa-check';
  button.setAttribute('aria-label', 'Link copied!');
  button.classList.add('copied');
  
  // Reset after 2 seconds
  setTimeout(() => {
    icon.className = originalClass;
    button.setAttribute('aria-label', 'Save article');
    button.classList.remove('copied');
  }, 2000);
}

// Initialize article page (only for static site)
document.addEventListener('DOMContentLoaded', () => {
  // Skip if WordPress detected
  if (typeof wp !== 'undefined' || typeof ccsData !== 'undefined') {
    return;
  }
  
  // Wait for newsArticles to be loaded
  if (typeof newsArticles === 'undefined') {
    // If news.js hasn't loaded yet, wait a bit
    setTimeout(() => {
      initializeArticle();
    }, 100);
  } else {
    initializeArticle();
  }
});

function initializeArticle() {
  const slugOrId = getArticleSlugFromUrl();
  
  if (!slugOrId) {
    console.error('No article slug or ID found in URL');
    window.location.href = 'news.html';
    return;
  }
  
  const article = loadArticle(slugOrId);

  if (article) {
    renderArticle(article);
    renderRelatedArticles(article.id);
    setupShareButtons(article);
  } else {
    console.error('Article not found:', slugOrId);
    window.location.href = 'news.html';
  }

  setupRelatedArticlesNav();
  initTableOfContents();
}

// Initialize sticky table of contents
function initTableOfContents() {
  const articleBody = document.getElementById('article-body');
  if (!articleBody) return;
  
  // Find headings in the new entry-content structure (only h2, no h3 subsections)
  const headings = articleBody.querySelectorAll('.entry-content h2');
  if (headings.length < 2) return; // Only show TOC if there are at least 2 headings
  
  // Find existing TOC list in sidebar (already exists in HTML)
  const tocList = document.getElementById('article-toc');
  if (!tocList) return; // TOC sidebar not found
  
  // Clear any existing content
  tocList.innerHTML = '';
  
  headings.forEach((heading, index) => {
    // Generate ID for heading if it doesn't have one
    let headingId = heading.id;
    if (!headingId) {
      // Create slug from heading text
      headingId = heading.textContent
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
      // Ensure unique ID
      if (document.getElementById(headingId)) {
        headingId = `${headingId}-${index}`;
      }
      heading.id = headingId;
    }
    
    const tocItem = document.createElement('li');
    const tocLink = document.createElement('a');
    tocLink.href = `#${headingId}`;
    tocLink.textContent = heading.textContent.trim();
    
    // Smooth scroll
    tocLink.addEventListener('click', (e) => {
      e.preventDefault();
      const target = document.getElementById(headingId);
      if (target) {
        const offset = 120; // Account for sticky header
        const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({
          top: targetPosition,
          behavior: 'smooth'
        });
        
        // Update active state
        tocList.querySelectorAll('a').forEach(link => {
          link.classList.remove('active');
        });
        tocLink.classList.add('active');
      }
    });
    
    tocItem.appendChild(tocLink);
    tocList.appendChild(tocItem);
  });
  
  // Update active link on scroll with throttling
  let ticking = false;
  const updateActiveTOCLink = () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const scrollPosition = window.scrollY + 200;
        
        let activeHeading = null;
        headings.forEach((heading) => {
          const headingTop = heading.getBoundingClientRect().top + window.pageYOffset;
          const headingBottom = headingTop + heading.offsetHeight;
          
          if (scrollPosition >= headingTop - 50 && scrollPosition < headingBottom) {
            activeHeading = heading;
          }
        });
        
        // If we're past the last heading, keep it active
        if (!activeHeading && headings.length > 0) {
          const lastHeading = headings[headings.length - 1];
          const lastHeadingBottom = lastHeading.getBoundingClientRect().top + window.pageYOffset + lastHeading.offsetHeight;
          if (scrollPosition >= lastHeadingBottom - 100) {
            activeHeading = lastHeading;
          }
        }
        
        // Update active state
        tocList.querySelectorAll('a').forEach(link => {
          link.classList.remove('active');
        });
        
        if (activeHeading) {
          const activeLink = tocList.querySelector(`a[href="#${activeHeading.id}"]`);
          if (activeLink) {
            activeLink.classList.add('active');
          }
        }
        
        ticking = false;
      });
      ticking = true;
    }
  };
  
  window.addEventListener('scroll', updateActiveTOCLink, { passive: true });
  updateActiveTOCLink(); // Initial check
}
