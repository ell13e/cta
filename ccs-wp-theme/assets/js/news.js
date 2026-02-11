/**
 * News Page JavaScript
 * Handles article rendering and newsletter subscription
 */

// Generate article slug from title
function generateArticleSlug(title) {
  if (!title) return '';
  return title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');
}

// News articles data
const newsArticles = [
  {
    id: 1,
    title: "CQC Changes 2025-2026: What Care Providers Need to Know",
    slug: "cqc-changes-2025-2026-what-care-providers-need-to-know",
    excerpt:
      "Essential guide to CQC inspection updates, Quality Statements, and the new assessment framework. Stay compliant and prepared for what's ahead.",
    category: "Compliance",
    date: "2025-12-15",
    readTime: "5 min read",
    featured: true,
    image: "assets/img/stock_photos/06_BLOG_POSTS/blog_post01.webp",
  },
  {
    id: 2,
    title: "New Medication Administration Course Launched",
    slug: "new-medication-administration-course-launched",
    excerpt:
      "We're excited to announce our Level 3 Medication Administration course, designed for senior care staff requiring advanced medication management skills.",
    category: "Courses",
    date: "2025-01-10",
    readTime: "3 min read",
    image: "assets/img/stock_photos/06_BLOG_POSTS/blog_post03.webp",
  },
  {
    id: 3,
    title: "Success Story: Care Home Achieves Outstanding Rating",
    slug: "success-story-care-home-achieves-outstanding-rating",
    excerpt: "How Riverside Care Home used our comprehensive training programme to achieve their Outstanding CQC rating.",
    category: "Case Study",
    date: "2025-01-05",
    readTime: "4 min read",
    image: "assets/img/stock_photos/06_BLOG_POSTS/blog_post04.webp",
  },
  {
    id: 4,
    title: "Understanding the Updated BLS Guidelines",
    slug: "understanding-the-updated-bls-guidelines",
    excerpt:
      "The UK Resuscitation Council has released new Basic Life Support protocols. Learn what's changed and how it affects your certification.",
    category: "First Aid",
    date: "2024-12-20",
    readTime: "6 min read",
    image: "assets/img/stock_photos/05_COURSE_THUMBNAILS/emergency_first_aid06.webp",
  },
  {
    id: 5,
    title: "Mental Health First Aid in Care Settings",
    slug: "mental-health-first-aid-in-care-settings",
    excerpt: "Why every care organisation should have Mental Health First Aiders on staff and how to get certified.",
    category: "Mental Health",
    date: "2024-12-15",
    readTime: "4 min read",
    image: "assets/img/stock_photos/06_BLOG_POSTS/blog_post02.webp",
  },
];

// Newsletter form state
const newsletterState = {
  isSubscribing: false,
  subscribeSuccess: false,
  subscribeEmail: "",
};

// Format date for display
function formatDate(dateString, format = "long") {
  const date = new Date(dateString);
  const options =
    format === "long"
      ? { day: "numeric", month: "long", year: "numeric" }
      : { day: "numeric", month: "short", year: "numeric" };
  return date.toLocaleDateString("en-GB", options);
}

// Calendar icon SVG
function getCalendarIcon(className = "news-calendar-icon") {
  return `<svg class="${className}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
    />
  </svg>`;
}

// Arrow right icon SVG
function getArrowRightIcon(className = "news-arrow-icon") {
  return `<svg class="${className}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
  </svg>`;
}

// Render featured article
function renderFeaturedArticle(article) {
  const featuredContainer = document.getElementById("featured-article-container");
  if (!featuredContainer || !article) return;

  featuredContainer.innerHTML = `
    <article class="news-featured-article">
      <div class="news-featured-image-wrapper">
        <span class="news-featured-badge-label">Featured</span>
        <img
          src="${article.image}"
          alt="${article.title}"
          class="news-featured-image"
        />
      </div>

      <div class="news-featured-content">
        <div class="news-featured-meta">
          <span class="news-category-badge news-category-badge-featured">${article.category}</span>
          <time datetime="${article.date}" class="news-featured-date">${formatDate(article.date, "short")}</time>
          <span class="news-read-time">${article.readTime}</span>
        </div>

        <h2 class="news-featured-title">${article.title}</h2>
        <p class="news-featured-excerpt">${article.excerpt}</p>

        <a href="${article.slug ? article.slug + '.html' : 'news-article-' + article.id + '.html'}" class="news-featured-cta">
          Read More
        </a>
      </div>
    </article>
  `;
}

// Render regular article card
function renderArticleCard(article) {
  return `
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
          <span class="news-article-date">${formatDate(article.date, "short")}</span>
          <span class="news-read-time">${article.readTime}</span>
        </div>

        <h3 class="news-article-title">${article.title}</h3>
        <p class="news-article-excerpt">${article.excerpt}</p>

        <a href="${article.slug ? article.slug + '.html' : 'news-article-' + article.id + '.html'}" class="news-article-link">
          Read More
          ${getArrowRightIcon("news-arrow-icon-small")}
        </a>
      </div>
    </article>
  `;
}

// Get unique categories from articles
function getUniqueCategories(articles) {
  const categories = new Set();
  articles.forEach((article) => {
    if (article.category) {
      categories.add(article.category);
    }
  });
  return Array.from(categories).sort();
}

// Render filter buttons
function renderFilterButtons(articles) {
  const filterContainer = document.querySelector(".latest-articles-filters");
  if (!filterContainer) return;

  const categories = getUniqueCategories(articles);
  
  // Keep the "All" button and add category buttons
  const allButton = filterContainer.querySelector('[data-category="all"]');
  if (!allButton) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "latest-articles-filter-btn active";
    btn.setAttribute("data-category", "all");
    btn.setAttribute("aria-pressed", "true");
    btn.textContent = "All";
    filterContainer.appendChild(btn);
  }

  // Add category buttons
  categories.forEach((category) => {
    const existingBtn = filterContainer.querySelector(`[data-category="${category}"]`);
    if (!existingBtn) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "latest-articles-filter-btn";
      btn.setAttribute("data-category", category);
      btn.setAttribute("aria-pressed", "false");
      btn.textContent = category;
      filterContainer.appendChild(btn);
    }
  });
}

// Filter articles by category
function filterArticles(category) {
  const regularArticles = newsArticles.filter((article) => !article.featured);
  const filteredArticles =
    category === "all"
      ? regularArticles
      : regularArticles.filter((article) => article.category === category);

  const articlesGrid = document.getElementById("articles-grid");
  if (articlesGrid) {
    articlesGrid.innerHTML = filteredArticles.map(renderArticleCard).join("");
  }

  // Update active filter button
  const filterButtons = document.querySelectorAll(".latest-articles-filter-btn");
  filterButtons.forEach((btn) => {
    const isActive = btn.getAttribute("data-category") === category;
    btn.classList.toggle("active", isActive);
    btn.setAttribute("aria-pressed", isActive.toString());
  });
}

// Render all articles
function renderArticles() {
  const featuredArticle = newsArticles.find((article) => article.featured);
  const regularArticles = newsArticles.filter((article) => !article.featured);

  // Check if server-rendered content exists (progressive enhancement)
  const featuredContainer = document.getElementById("featured-article-container");
  const articlesGrid = document.getElementById("articles-grid");
  const hasServerRenderedFeatured = featuredContainer && featuredContainer.querySelector('.news-featured-article') !== null;
  const hasServerRenderedArticles = articlesGrid && articlesGrid.querySelector('.news-article-card') !== null;

  // Only render if server-rendered content doesn't exist
  if (!hasServerRenderedFeatured && featuredArticle) {
    renderFeaturedArticle(featuredArticle);
  }

  // Render filter buttons (always needed for filtering)
  renderFilterButtons(regularArticles);

  // Only replace articles grid if server-rendered content doesn't exist
  // Filtering will still work via filterArticles() function
  if (articlesGrid && !hasServerRenderedArticles) {
    articlesGrid.innerHTML = regularArticles.map(renderArticleCard).join("");
  }
}

// Handle newsletter form submission
function handleNewsletterSubmit(e) {
  e.preventDefault();

  const emailInput = document.getElementById("newsletter-email");
  const consentCheckbox = document.getElementById("newsletter-consent");
  const submitBtn = document.getElementById("newsletter-submit");
  const submitText = document.getElementById("newsletter-submit-text");
  const submitLoading = document.getElementById("newsletter-submit-loading");
  const successMessage = document.getElementById("newsletter-success");
  const form = document.getElementById("newsletter-form");

  if (!emailInput || !submitBtn || !successMessage || !form) return;

  // Honeypot spam protection
  const honeypot = document.getElementById("newsletter-website");
  if (honeypot && honeypot.value !== "") {
    // Bot detected - silently reject
    return;
  }

  // Check if consent checkbox is checked
  if (!consentCheckbox || !consentCheckbox.checked) {
    consentCheckbox?.focus();
    return;
  }

  // Set subscribing state
  newsletterState.isSubscribing = true;
  newsletterState.subscribeEmail = emailInput.value;

  // Update UI
  submitBtn.disabled = true;
  submitText.classList.add("hidden");
  submitLoading.classList.remove("hidden");

  // Simulate API call
  setTimeout(() => {
    // Reset subscribing state
    newsletterState.isSubscribing = false;
    newsletterState.subscribeSuccess = true;

    // Update UI
    submitBtn.disabled = false;
    submitText.classList.remove("hidden");
    submitLoading.classList.add("hidden");

    // Clear form
    emailInput.value = "";
    if (consentCheckbox) {
      consentCheckbox.checked = false;
    }
    newsletterState.subscribeEmail = "";

    // Show thank you popup
    if (window.showThankYouModal) {
      window.showThankYouModal("🎉 Welcome aboard! Check your email to confirm your subscription and start receiving our latest updates.");
    }
  }, 1000);
}

// Initialize news page
document.addEventListener("DOMContentLoaded", () => {
  // Render articles
  renderArticles();

  // Setup filter buttons
  const filterButtons = document.querySelectorAll(".latest-articles-filter-btn");
  filterButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const category = btn.getAttribute("data-category");
      filterArticles(category);
    });
  });

  // Setup newsletter form
  const newsletterForm = document.getElementById("newsletter-form");
  if (newsletterForm) {
    newsletterForm.addEventListener("submit", handleNewsletterSubmit);
  }
});

