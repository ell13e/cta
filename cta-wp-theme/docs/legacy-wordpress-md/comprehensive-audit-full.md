# Comprehensive HTML, CSS, JS & JSON Audit
**CTA Website - Full Stack Analysis**
**Date: December 17, 2025**

---

## Executive Summary

**Total Files Analyzed:** 50+ files across HTML partials, JS scripts, build tools, and JSON configs

**Major Findings:**
- ⚠️ **Footer inconsistency** - partial uses `site-footer`, some pages have inline `site-footer-modern` - consolidate
- ❌ **2 duplicate newsletter modal files** - consolidate
- ❌ **15 build/test scripts** - most can be deleted after use
- ❌ **Massive CSS coverage reports** - archive or delete
- ⚠️ **theme.json for WordPress** - not needed for static site
- ✅ **JS already audited separately** (see js-audit-report.md)

---

## PART 1: HTML PARTIALS ANALYSIS

### 1.1 FOOTER INCONSISTENCY ⚠️ CONSOLIDATE

**Current Situation:**
- **One footer partial:** `partials/footer.html` uses `class="site-footer"`
- **Inline footer HTML:** Some pages (contact.html, cqc-changes-2025-2026-what-care-providers-need-to-know.html) have inline footer with `class="site-footer-modern"`

**Problem:**
- Two different footer implementations (partial vs inline)
- Inconsistent class names (`site-footer` vs `site-footer-modern`)
- Duplicate footer HTML in multiple files

**Recommendation:**
```bash
# Option A: Standardize on partial (recommended)
# 1. Update footer.html to use site-footer-modern class
# 2. Replace inline footer HTML with: <div data-include="partials/footer.html"></div>
# 3. Remove inline footer HTML from contact.html and other pages

# Option B: Extract inline footer to partial
# 1. Create footer-modern.html partial from inline HTML
# 2. Update all pages to use the partial
```

**Impact:** Better maintainability, single source of truth for footer

---

### 1.2 DUPLICATE NEWSLETTER MODALS ❌ CONSOLIDATE

You have **TWO newsletter modal files**:

**newsletter-modal.html** (53 lines, 2.5 KB)
```html
<!-- Just HTML structure for modal -->
<div class="newsletter-modal">
  <form>...</form>
</div>
```

**newsletter-modal-js.html** (75 lines, 2.3 KB)
```html
<!-- HTML + embedded JavaScript -->
<div class="newsletter-modal">
  <form>...</form>
</div>
<script>
  // Modal logic inline
</script>
```

**Problem:**
- Same functionality, different approaches
- One has inline JS, one doesn't
- Both exist but likely only one is used

**Recommendation:**
```bash
# Keep: newsletter-modal.html (clean HTML)
# Put JS in: js/newsletter-modal.js (separate file)
# Delete: newsletter-modal-js.html

rm partials/newsletter-modal-js.html
```

**Why?**
- Separation of concerns (HTML vs JS)
- Better caching
- Follows your existing pattern

**Impact:** -2.3 KB, cleaner architecture

---

### 1.3 HEAD.HTML - OPTIMIZED ✅

**head.html** (33 lines, 5.8 KB)
- ✅ Loads critical CSS inline (good!)
- ✅ Async loads main CSS (good!)
- ✅ Uses preconnect for fonts (good!)
- ⚠️ Comment says icons missing:
  ```html
  <!-- Icon files not found - using favicon.ico fallback -->
  <!-- To fix: Add icon-light-32x32.png, icon-dark-32x32.png, and icon.svg -->
  ```

**Action:** Create proper PWA icons (covered in js-audit-report.md)

---

### 1.4 HEADER.HTML - MASSIVE ⚠️

**header.html** (345 lines, 15.9 KB)

**Contents:**
```
Lines   1-50:  Header structure
Lines  50-100: Desktop navigation
Lines 100-200: Mobile menu HTML
Lines 200-300: Search modal HTML
Lines 300-345: Skip links, accessibility
```

**Problem:** This is HUGE for a partial!

**Breakdown of what's in header.html:**
```html
<!-- Navigation structure (50 lines) -->
<header class="site-header">
  <div class="header-container">
    <nav class="nav-desktop">...</nav>
    <button class="mobile-menu-btn">...</button>
  </div>
</header>

<!-- Mobile menu (100 lines) -->
<div id="mobile-menu" class="mobile-menu">
  <!-- Entire mobile navigation structure -->
</div>

<!-- Search modal (100 lines) -->
<div id="search-modal" class="search-modal">
  <!-- Search interface -->
</div>

<!-- Skip navigation (20 lines) -->
<a href="#main-content" class="skip-link">Skip to content</a>
```

**Recommendation: Split into modular partials**
```
partials/
  ├─ header-nav.html (70 lines)          // Just the header bar
  ├─ mobile-menu.html (100 lines)        // Mobile menu
  ├─ search-modal.html (100 lines)       // Search modal
  └─ skip-links.html (20 lines)          // Accessibility links
```

Then in your pages:
```html
<div data-include="partials/header-nav.html"></div>
<div data-include="partials/mobile-menu.html"></div>
<div data-include="partials/search-modal.html"></div>
<div data-include="partials/skip-links.html"></div>
```

**Why split it?**
- Easier maintenance
- Can reuse search modal elsewhere
- Better organization
- Smaller files = faster to edit

**Impact:** No size reduction, but MUCH better maintainability

---

## PART 2: SCRIPTS FOLDER ANALYSIS

### 2.1 Build Scripts ⚠️ ARCHIVE AFTER USE

**These are one-time or occasional-use tools - NOT production code:**

#### CSS Coverage Tools (8 files, ~83 KB)
```
collect-css-coverage-simple.js   (267 lines)
collect-css-coverage-v2.js       (644 lines)  ← Latest version
collect-css-coverage.js          (218 lines)
find-orphaned-rules-direct.js    (281 lines)
find-orphaned-rules.js           (294 lines)
create-deletion-batch.js         (366 lines)
execute-deletion-batch.js        (176 lines)
generate-removal-plan.js         (262 lines)
```

**Status:** 
- You already ran these and have coverage results
- `collect-css-coverage-v2.js` is the latest version
- The other 7 versions are obsolete

**Action:**
```bash
# Keep ONLY the latest version
mkdir -p archive/css-tools
mv scripts/collect-css-coverage-simple.js archive/css-tools/
mv scripts/collect-css-coverage.js archive/css-tools/
mv scripts/find-orphaned-rules-direct.js archive/css-tools/
mv scripts/find-orphaned-rules.js archive/css-tools/
mv scripts/create-deletion-batch.js archive/css-tools/
mv scripts/execute-deletion-batch.js archive/css-tools/
mv scripts/generate-removal-plan.js archive/css-tools/

# Keep ONLY this one:
# scripts/collect-css-coverage-v2.js
```

**Impact:** -7 files, cleaner scripts folder

---

#### Testing Tools (4 files, ~30 KB)
```
check-contrast.js                (200 lines)
verify-accessibility.js          (214 lines)
verify-keyboard-navigation.js    (220 lines)
phase7-testing.js                (305 lines)
```

**Status:**
- These run accessibility/WCAG tests
- Useful for ongoing testing
- **BUT:** phase7-testing.js suggests you're past phase 7

**Action:**
```bash
# Keep for ongoing testing:
# - verify-accessibility.js
# - verify-keyboard-navigation.js
# - check-contrast.js

# Archive or delete:
mv scripts/phase7-testing.js archive/old-testing/
```

**Impact:** -1 file

---

#### Performance Tools (2 files, ~16 KB)
```
measure-baseline.js              (230 lines)
pagespeed-insights.js            (231 lines)
```

**Status:**
- measure-baseline.js: Records initial performance (done)
- pagespeed-insights.js: Runs PageSpeed API tests

**Action:**
```bash
# Archive baseline (one-time use):
mv scripts/measure-baseline.js archive/

# Keep PageSpeed for ongoing monitoring:
# scripts/pagespeed-insights.js ✅
```

**Impact:** -1 file

---

#### Image Tools (1 file, ~5 KB)
```
generate-responsive-images.js    (196 lines)
```

**Status:**
- Generates responsive image sizes
- Run when you add new images
- Keep it

**Action:** ✅ Keep

---

### 2.2 Scripts Folder Cleanup Summary

**Current: 15 files, ~134 KB**
**After cleanup: 5 files, ~45 KB**

**Keep:**
```
scripts/
  ├─ collect-css-coverage-v2.js          // Latest CSS tool
  ├─ verify-accessibility.js             // A11y testing
  ├─ verify-keyboard-navigation.js       // Keyboard testing
  ├─ check-contrast.js                   // Contrast testing
  ├─ pagespeed-insights.js               // Performance monitoring
  └─ generate-responsive-images.js       // Image generation
```

**Archive or Delete:**
```
archive/
  ├─ css-tools/
  │   ├─ collect-css-coverage-simple.js
  │   ├─ collect-css-coverage.js
  │   ├─ find-orphaned-rules-direct.js
  │   ├─ find-orphaned-rules.js
  │   ├─ create-deletion-batch.js
  │   ├─ execute-deletion-batch.js
  │   └─ generate-removal-plan.js
  └─ old-testing/
      ├─ phase7-testing.js
      └─ measure-baseline.js
```

---

## PART 3: TOOLS FOLDER ANALYSIS

### 3.1 Course Data Generation Tools (5 files)

```
csv-to-courses.js           (247 lines)  ← Main tool
excel-to-courses.js         (131 lines)  ← Alternative format
generate-course-modules.js  (82 lines)   ← Module generator
generate-course-pages.js    (35 lines)   ← Page generator
safe_split_static.js        (76 lines)   ← Unknown purpose?
```

**Analysis:**

**csv-to-courses.js** (8.8 KB)
```javascript
/**
 * Course data generated from courses_complete.csv
 * DO NOT EDIT MANUALLY - Run: node tools/csv-to-courses.js
 */
```
- Converts CSV → JSON course data
- ✅ **Keep** - needed when updating courses

**excel-to-courses.js** (4 KB)
```javascript
// Reads .xlsx files and converts to course JSON
```
- Alternative to CSV version
- ❓ **Question:** Do you use Excel OR CSV? Pick one.
  ```bash
  # If you use CSV only:
  mv tools/excel-to-courses.js archive/
  
  # If you use Excel only:
  mv tools/csv-to-courses.js archive/
  ```

**generate-course-modules.js** (3 KB)
- Generates course module structure
- ✅ **Keep** if you actively use it
- ❓ Delete if courses are now static

**generate-course-pages.js** (1.4 KB)
- Generates individual course HTML pages
- ✅ **Keep** if you have 100+ courses
- ❓ Delete if you generate pages manually

**safe_split_static.js** (3.1 KB)
```bash
# Check what this does:
head -20 tools/safe_split_static.js
```
- ❓ **Unknown** - check if still used

**Recommendation:**
```bash
# Minimal setup (keep these):
tools/
  ├─ csv-to-courses.js              // OR excel-to-courses.js (pick one)
  └─ generate-course-modules.js     // If actively used

# Archive the rest unless actively using them
```

---

## PART 4: JSON FILES ANALYSIS

### 4.1 Configuration Files

**manifest.json** (20 lines)
```json
{
  "name": "Continuity Training Academy",
  "icons": [{ "src": "favicon.ico" }]  ← Missing proper PWA icons
}
```
- ⚠️ **Fix:** Add 192x192 and 512x512 PNG icons
- ✅ **Keep**

**package.json** (18 lines)
```json
{
  "scripts": {
    "lighthouse": "...",
    "pagespeed": "node scripts/pagespeed-insights.js"
  }
}
```
- ✅ **Keep** - needed for npm scripts

**theme.json** (422 lines, large!)
```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 2,
  "settings": { ... }
}
```
- ❌ **DELETE** - This is for WordPress themes
- You're running a **static HTML site**, NOT WordPress
- This file does nothing

```bash
rm theme.json  # You don't need this
```

---

### 4.2 Coverage Result Files

**css-coverage-results.json** (unknown size)
**css-coverage-results_v2.json** (unknown size)
**css-unused-analysis.json** (unknown size)
**css-deletion-batch.json** (unknown size)

**Problem:**
- These are GENERATED files from your CSS analysis
- They're massive (coverage data for every selector)
- They're not used in production

**Action:**
```bash
# Archive them
mkdir -p archive/css-coverage-reports
mv css-coverage-results.json archive/css-coverage-reports/
mv css-coverage-results_v2.json archive/css-coverage-reports/
mv css-unused-analysis.json archive/css-coverage-reports/
mv css-deletion-batch.json archive/css-coverage-reports/

# Or just delete them if you've already cleaned CSS
rm css-coverage-*.json css-unused-analysis.json css-deletion-batch.json
```

**Impact:** Could be 1-5 MB freed up

---

### 4.3 Performance Results

**pagespeed-results.json**
```json
[
  {
    "url": "http://localhost:5500/",
    "metrics": {
      "performance": { "score": 57 }
    }
  }
]
```
- Historical performance data
- ⚠️ **Archive** - it's from localhost testing
- Not needed in production

```bash
mv pagespeed-results.json archive/performance/
```

---

## PART 5: NEW-ABOUT FOLDER

```
new-about/
  ├─ new-about/          (duplicate?)
  │   ├─ index.html
  │   ├─ package.json
  │   └─ src/
  └─ new-about 2/        (duplicate?)
      ├─ index.html
      ├─ package.json
      └─ src/
```

**Problem:**
- You have **TWO** "new-about" folders
- Nested structure: `new-about/new-about/`
- This looks like a test/prototype for redesigning the about page

**Status:**
- If you've integrated this into your main site → **DELETE the entire new-about folder**
- If it's still in progress → **Keep only one version**

```bash
# If about.html is updated and this is obsolete:
rm -rf new-about/

# If still prototyping:
rm -rf "new-about/new-about 2/"  # Delete duplicate
```

---

## PART 6: CSS ALREADY AUDITED

Your CSS was comprehensively audited in the previous session:
- 360 lines of unused CSS identified
- team-new.css marked for deletion
- Consolidation opportunities documented

**See:** Previous audit report for CSS details

---

## PART 7: PRIORITY CLEANUP PLAN

### IMMEDIATE WINS (30 minutes)

**1. Consolidate footer implementation**
```bash
# Standardize on footer partial:
# 1. Update footer.html to use site-footer-modern class (if that's the preferred design)
# 2. Replace inline footer HTML in contact.html and other pages with:
#    <div data-include="partials/footer.html"></div>
# 3. Remove inline footer HTML from pages

# Consolidate newsletter modals:
rm partials/newsletter-modal-js.html
# (Keep newsletter-modal.html, move JS to separate file)
```
**Impact:** Better maintainability, single source of truth

---

**2. Delete WordPress theme.json**
```bash
rm theme.json  # You're not using WordPress
```
**Impact:** -422 lines, not needed

---

**3. Archive CSS coverage reports**
```bash
mkdir -p archive/css-coverage-reports
mv css-coverage-*.json archive/css-coverage-reports/
mv css-unused-analysis.json archive/css-coverage-reports/
mv css-deletion-batch.json archive/css-coverage-reports/
```
**Impact:** 1-5 MB freed up

---

**4. Archive obsolete build scripts**
```bash
mkdir -p archive/{css-tools,old-testing,performance}

# CSS tools (keep only v2):
mv scripts/collect-css-coverage-simple.js archive/css-tools/
mv scripts/collect-css-coverage.js archive/css-tools/
mv scripts/find-orphaned-rules-direct.js archive/css-tools/
mv scripts/find-orphaned-rules.js archive/css-tools/
mv scripts/create-deletion-batch.js archive/css-tools/
mv scripts/execute-deletion-batch.js archive/css-tools/
mv scripts/generate-removal-plan.js archive/css-tools/

# Testing:
mv scripts/phase7-testing.js archive/old-testing/

# Performance:
mv scripts/measure-baseline.js archive/performance/
mv pagespeed-results.json archive/performance/
```
**Impact:** -9 files, cleaner scripts folder

---

**5. Clean up new-about folder**
```bash
# If obsolete:
rm -rf new-about/

# If still using:
rm -rf "new-about/new-about 2/"  # Delete duplicate only
```
**Impact:** Potentially huge (depends on folder size)

---

### MEDIUM PRIORITY (2-3 hours)

**6. Split header.html into modular partials**
```bash
# Create new partials:
partials/
  ├─ header-nav.html       (70 lines)
  ├─ mobile-menu.html      (100 lines)
  ├─ search-modal.html     (100 lines)
  └─ skip-links.html       (20 lines)

# Update all HTML pages to include all 4 partials
```
**Impact:** Better maintainability, no size change

---

**7. Consolidate tools/ folder**
```bash
# Pick ONE course converter:
# Either keep csv-to-courses.js OR excel-to-courses.js

# Archive unused generators:
mv tools/generate-course-pages.js archive/  # If not using
mv tools/safe_split_static.js archive/      # If unknown
```

---

### LOW PRIORITY (Nice to have)

**8. Fix manifest.json icons**
```json
{
  "icons": [
    { "src": "icons/icon-192.png", "sizes": "192x192" },
    { "src": "icons/icon-512.png", "sizes": "512x512" }
  ]
}
```

**9. Document remaining scripts**
```bash
# Add README.md to scripts/ folder explaining what each does
```

---

## SUMMARY TABLE

| Category | Files Before | Files After | Impact |
|----------|--------------|-------------|--------|
| **HTML Partials** | 1 | 1 | ⚠️ Consolidate inline footer HTML to use partial |
| **Build Scripts** | 15 | 6 | -9 obsolete |
| **Tools** | 5 | 2-3 | -2-3 unused |
| **JSON Configs** | 8 | 2 | -6 archives |
| **Test Folders** | new-about | 0 | Delete entirely |
| **Total** | **34+** | **14-15** | **56% reduction** |

---

## FILES TO KEEP (Production Essentials)

```
Essential Production Files:
========================

partials/
  ├─ head.html                               ✅
  ├─ header.html (or split into 4)           ✅
  ├─ footer.html                             ✅ (standardize - some pages have inline footer)
  └─ newsletter-modal.html                   ✅

scripts/
  ├─ collect-css-coverage-v2.js              ✅ For CSS audits
  ├─ verify-accessibility.js                 ✅ Testing
  ├─ verify-keyboard-navigation.js           ✅ Testing
  ├─ check-contrast.js                       ✅ Testing
  ├─ pagespeed-insights.js                   ✅ Monitoring
  └─ generate-responsive-images.js           ✅ Image gen

tools/
  ├─ csv-to-courses.js                       ✅ Data generation
  └─ generate-course-modules.js              ✅ If actively used

JSON:
  ├─ manifest.json                           ✅ PWA config
  └─ package.json                            ✅ npm scripts
```

---

## FILES TO DELETE/ARCHIVE

```
Delete or Archive:
==================

⚠️ Inline footer HTML in some pages (contact.html, etc.) - Consolidate to use footer.html partial
❌ partials/newsletter-modal-js.html             - Use separate JS
❌ theme.json                                     - WordPress only
❌ css-coverage-results*.json                    - Archive
❌ css-unused-analysis.json                      - Archive
❌ css-deletion-batch.json                       - Archive
❌ pagespeed-results.json                        - Archive
❌ new-about/ folder                             - Test code
❌ scripts/collect-css-coverage-simple.js        - Old version
❌ scripts/collect-css-coverage.js               - Old version
❌ scripts/find-orphaned-rules-direct.js         - Old version
❌ scripts/find-orphaned-rules.js                - Old version
❌ scripts/create-deletion-batch.js              - Old version
❌ scripts/execute-deletion-batch.js             - Old version
❌ scripts/generate-removal-plan.js              - Old version
❌ scripts/phase7-testing.js                     - Completed phase
❌ scripts/measure-baseline.js                   - One-time use
❌ tools/excel-to-courses.js (OR csv-to-courses) - Pick one
❌ tools/generate-course-pages.js                - If unused
❌ tools/safe_split_static.js                    - Unknown
```

---

## FINAL RECOMMENDATIONS

**Do This Now (30 min):**
1. Delete footer duplicate
2. Delete newsletter-modal-js.html
3. Delete theme.json
4. Archive CSS coverage JSONs
5. Delete or archive new-about/

**Do This Week (2-3 hours):**
6. Archive obsolete build scripts (keep only latest versions)
7. Consolidate tools/ folder
8. Split header.html into modular partials

**Do This Month:**
9. Fix manifest.json PWA icons
10. Document remaining scripts with README
11. Set up automated cleanup script

---

## TOTAL IMPACT

**Before Cleanup:**
- 34+ files across multiple categories
- ~5-10 MB of build artifacts and coverage reports
- Duplicate code in 4 places
- Confusing folder structure

**After Cleanup:**
- 14-15 essential files
- ~1-2 MB (production code only)
- No duplicates
- Clean, documented structure

**Result: 56% fewer files, cleaner codebase, easier maintenance**

---

**Want me to create the actual cleanup script or help with any specific deletions?**
