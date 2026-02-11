/**
 * Unified Site Configuration
 * Single source of truth for categories, navigation, and footer
 * 
 * Usage:
 * - Include this file before includes.js and main.js
 * - Access via SITE_CONFIG global object
 * - Use CategoryHelpers for category lookups
 */

const SITE_CONFIG = {
  /**
   * Course Categories
   * Used across: filters, pills, cards, navigation, footer
   * 
   * Fields:
   * - id: Unique identifier (kebab-case)
   * - label: Short display name (1-3 words, for pills/filters)
   * - labelFull: Full display name (for menus/headers)
   * - description: Brief description for mega menu
   * - faIcon: Font Awesome icon class
   * - color: CSS custom property for theming
   * - badge: Category badge text
   * - urlParam: URL parameter for filtering (?category=xxx)
   */
  categories: [
    {
      id: 'core-care',
      label: 'Core Care',
      labelFull: 'Core Care Skills',
      description: 'Essential skills for providing quality care',
      faIcon: 'fa-heart',
      color: 'var(--category-core-care)',
      badge: 'Essential',
      urlParam: 'core-care-skills'
    },
    {
      id: 'first-aid',
      label: 'First Aid',
      labelFull: 'Emergency & First Aid',
      description: 'Respond confidently in critical moments',
      faIcon: 'fa-first-aid',
      color: 'var(--category-first-aid)',
      badge: 'Life-Saving',
      urlParam: 'emergency-first-aid'
    },
    {
      id: 'specialist-health',
      label: 'Specialist Health',
      labelFull: 'Health Conditions & Specialist Care',
      description: 'Expert knowledge for complex conditions',
      faIcon: 'fa-stethoscope',
      color: 'var(--category-specialist-health)',
      badge: 'Advanced',
      urlParam: 'health-conditions-specialist-care'
    },
    {
      id: 'medication',
      label: 'Medication',
      labelFull: 'Medication Management',
      description: 'Safe medication administration practices',
      faIcon: 'fa-pills',
      color: 'var(--category-medication)',
      badge: 'Clinical',
      urlParam: 'medication-management'
    },
    {
      id: 'safety',
      label: 'Safety',
      labelFull: 'Safety & Compliance',
      description: 'Protect yourself and those in your care',
      faIcon: 'fa-shield-alt',
      color: 'var(--category-safety)',
      badge: 'Practical',
      urlParam: 'safety-compliance'
    },
    {
      id: 'communication',
      label: 'Communication',
      labelFull: 'Communication & Workplace Culture',
      description: 'Effective and inclusive communication',
      faIcon: 'fa-users',
      color: 'var(--category-communication)',
      badge: 'Essential',
      urlParam: 'communication-workplace-culture'
    },
    {
      id: 'data',
      label: 'Data & Records',
      labelFull: 'Information & Data Management',
      description: 'Secure handling of sensitive information',
      faIcon: 'fa-database',
      color: 'var(--category-data)',
      badge: 'Compliance',
      urlParam: 'information-data-management'
    },
    {
      id: 'leadership',
      label: 'Leadership',
      labelFull: 'Leadership & Professional Development',
      description: 'Develop leadership and management skills',
      faIcon: 'fa-user-tie',
      color: 'var(--category-leadership)',
      badge: 'Advanced',
      urlParam: 'leadership-professional-development'
    },
    {
      id: 'nutrition',
      label: 'Nutrition',
      labelFull: 'Nutrition & Hygiene',
      description: 'Food safety and hygiene practices',
      faIcon: 'fa-apple-alt',
      color: 'var(--category-nutrition)',
      badge: 'Practical',
      urlParam: 'nutrition-hygiene'
    }
  ],

  /**
   * Mega Menu Category Groupings
   * Defines how categories appear in the navigation mega menu
   */
  megaMenuGroups: [
    {
      title: 'Core Training',
      categoryIds: ['core-care', 'communication', 'nutrition']
    },
    {
      title: 'Safety & Clinical',
      categoryIds: ['first-aid', 'safety', 'medication']
    },
    {
      title: 'Specialist & Leadership',
      categoryIds: ['specialist-health', 'leadership', 'data']
    }
  ],

  /**
   * Main Navigation Links
   */
  navigation: {
    primary: [
      { label: 'Home', href: 'index.html' },
      { label: 'Courses', href: 'courses.html', hasDropdown: true },
      { label: 'Upcoming Courses', href: 'events.html' },
      { label: 'Group Training', href: 'group-training.html' },
      { label: 'About Us', href: 'about.html' },
      { label: 'News', href: 'news.html' }
    ],
    cta: { 
      label: 'Contact Us', 
      href: 'contact.html' 
    }
  },

  /**
   * Footer Configuration
   */
  footer: {
    quickLinks: [
      { label: 'Home', href: 'index.html' },
      { label: 'All Courses', href: 'courses.html' },
      { label: 'Course Calendar', href: 'events.html' },
      { label: 'Group Training', href: 'group-training.html' },
      { label: 'About Us', href: 'about.html' }
    ],
    // Top 6 categories for footer display
    trainingAreaIds: ['core-care', 'first-aid', 'safety', 'medication', 'specialist-health', 'leadership'],
    legalLinks: [
      { label: 'Privacy Policy', href: 'privacy.html' },
      { label: 'Terms of Service', href: 'terms.html' },
      { label: 'Cookie Policy', href: 'cookies.html' }
    ],
    social: [
      { 
        platform: 'facebook', 
        url: 'https://facebook.com/continuitytraining', 
        label: 'Facebook',
        icon: 'fa-facebook-f'
      },
      { 
        platform: 'instagram', 
        url: 'https://instagram.com/continuitytrainingacademy', 
        label: 'Instagram',
        icon: 'fa-instagram'
      },
      { 
        platform: 'linkedin', 
        url: 'https://www.linkedin.com/company/continuitytrainingacademy/', 
        label: 'LinkedIn',
        icon: 'fa-linkedin-in'
      }
    ],
    contact: {
      phone: '01622 587343',
      phoneHref: 'tel:+441622587343',
      email: 'enquiries@continuitytrainingacademy.co.uk',
      address: [
        'Continuity of Care Services',
        'Maidstone, Kent',
        'ME14 5NZ'
      ]
    },
    description: 'Professional care sector training in Kent. CQC-compliant courses with same-day certification since 2020.'
  },

  /**
   * Company Contact Information
   */
  contact: {
    phone: '01622 587343',
    phoneFormatted: '01622 587 343',
    phoneHref: 'tel:+441622587343',
    email: 'enquiries@continuitytrainingacademy.co.uk'
  }
};

/**
 * Category Helper Functions
 * Utility methods for working with categories
 */
const CategoryHelpers = {
  /**
   * Get category by ID
   * @param {string} id - Category ID
   * @returns {Object|undefined} Category object
   */
  getById(id) {
    return SITE_CONFIG.categories.find(cat => cat.id === id);
  },

  /**
   * Get category by URL parameter
   * @param {string} param - URL parameter value
   * @returns {Object|undefined} Category object
   */
  getByUrlParam(param) {
    return SITE_CONFIG.categories.find(cat => cat.urlParam === param);
  },

  /**
   * Get Font Awesome icon HTML for a category
   * @param {string} categoryId - Category ID
   * @param {string} size - Size class (sm, md, lg)
   * @returns {string} HTML string
   */
  getIconHtml(categoryId, size = 'md') {
    const category = this.getById(categoryId);
    if (!category) return '';
    const sizeClass = size ? `category-icon-${size}` : '';
    return `<i class="fas ${category.faIcon} category-icon ${sizeClass}" aria-hidden="true"></i>`;
  },

  /**
   * Get all categories for filter display
   * @returns {Array} Array of filter-ready category objects
   */
  getForFilters() {
    return SITE_CONFIG.categories.map(cat => ({
      id: cat.id,
      label: cat.label,
      labelFull: cat.labelFull,
      icon: cat.faIcon,
      urlParam: cat.urlParam
    }));
  },

  /**
   * Get categories for footer display (top 6)
   * @returns {Array} Array of category objects
   */
  getForFooter() {
    return SITE_CONFIG.footer.trainingAreaIds
      .map(id => this.getById(id))
      .filter(Boolean);
  },

  /**
   * Get categories grouped for mega menu
   * @returns {Array} Array of group objects with categories
   */
  getForMegaMenu() {
    return SITE_CONFIG.megaMenuGroups.map(group => ({
      title: group.title,
      categories: group.categoryIds
        .map(id => this.getById(id))
        .filter(Boolean)
    }));
  },

  /**
   * Render a category pill HTML
   * @param {string} categoryId - Category ID
   * @param {Object} options - Rendering options
   * @returns {string} HTML string
   */
  renderPill(categoryId, options = {}) {
    const cat = this.getById(categoryId);
    if (!cat) return '';
    
    const { linkable = false, size = '' } = options;
    const tag = linkable ? 'a' : 'span';
    const href = linkable ? `href="courses.html?category=${cat.urlParam}"` : '';
    const sizeClass = size ? `category-pill-${size}` : '';
    
    return `
      <${tag} ${href} class="category-pill ${sizeClass}" data-category="${cat.id}">
        <i class="fas ${cat.faIcon} category-pill-icon" aria-hidden="true"></i>
        ${cat.label}
      </${tag}>
    `.trim();
  }
};

// Freeze config to prevent accidental modifications
Object.freeze(SITE_CONFIG);
Object.freeze(SITE_CONFIG.categories);
Object.freeze(SITE_CONFIG.navigation);
Object.freeze(SITE_CONFIG.footer);

// Export for module systems (if used)
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { SITE_CONFIG, CategoryHelpers };
}

