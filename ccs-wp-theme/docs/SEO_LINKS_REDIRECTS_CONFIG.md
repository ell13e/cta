# SEO Links & Redirects Configuration

## Overview

This document details the implementation of Rank Math-style link and redirect configurations, ensuring optimal SEO performance and user experience.

---

## ✅ **IMPLEMENTED FEATURES**

### 1. Redirections System ✅

**Status:** Fully implemented

**Features:**
- ✅ **Auto-redirects on slug change** - When you change a page/post slug, a 301 redirect is automatically created
- ✅ **Manual redirects** - Admin interface to create custom redirects (Tools → Redirects)
- ✅ **Redirect tracking** - Hit count tracking for each redirect
- ✅ **301/302 support** - Permanent and temporary redirects
- ✅ **Database table** - Custom table for efficient redirect storage

**How it works:**
1. **Auto-redirects:** When you change a post/page slug, the system automatically creates a redirect from the old URL to the new URL
2. **Manual redirects:** Go to **Tools → Redirects** to add custom redirects
3. **Redirect processing:** All redirects are checked on every page load (before template rendering)

**Location:**
- `inc/seo-links-redirects.php` - Main redirects system
- Database table: `wp_cta_redirects`

**Admin Interface:**
- **Tools → Redirects** - Manage all redirects
- View hit counts
- Delete redirects
- Add new redirects

---

### 2. Strip Category Base ✅

**Status:** Fully implemented

**What it does:**
- Removes `/category/` from category URLs
- Example: `/category/training/` becomes `/training/`
- Cleaner, shorter URLs

**How it works:**
- Modifies WordPress rewrite rules to remove category base
- Fixes category links throughout the site
- Works with existing permalink structure

**Example:**
- **Before:** `yoursite.com/category/first-aid/`
- **After:** `yoursite.com/first-aid/`

---

### 3. Redirect Attachments ✅

**Status:** Fully implemented

**What it does:**
- Redirects attachment pages to their parent post
- Orphan attachments (no parent) redirect to homepage
- Prevents duplicate content issues
- Distributes SEO value to parent posts

**How it works:**
- Checks if current page is an attachment
- If parent exists → redirects to parent post (301)
- If no parent → redirects to homepage (301)

**Example:**
- Attachment URL: `yoursite.com/image.jpg/`
- Redirects to: Parent post URL or homepage

---

### 4. External Links Configuration ✅

**Status:** Fully implemented

**Settings:**
- ✅ **Open External Links in New Tab:** ON
- ✅ **NoFollow External Links:** OFF (as recommended - external links help SEO)

**What it does:**
- Automatically adds `target="_blank"` to external links
- Adds `rel="noopener noreferrer"` for security
- Keeps visitors on your site
- External links help SEO (citing sources, authority)

**How it works:**
- Filters post content and widget text
- Detects external links (different domain)
- Adds target and rel attributes automatically
- Skips links that already have target attribute

**Security:**
- `noopener` prevents new page from accessing `window.opener`
- `noreferrer` prevents referrer information from being passed

---

### 5. Image SEO - Auto Alt Text ✅

**Status:** Fully implemented

**Settings:**
- ✅ **Add Missing ALT Attributes:** ON
- ✅ **Alt Attribute Format:** `%filename%` (cleaned and formatted)
- ✅ **Add Missing Title Attributes:** OFF (as requested)
- ✅ **Add Missing Caption/Description:** OFF (as requested)

**What it does:**
- Automatically generates alt text from image filename
- Cleans up filename (removes hyphens/underscores, capitalizes)
- Saves to attachment meta for future use
- Works on upload and when images are displayed

**Format:**
- Filename: `care-training-course.jpg`
- Alt text: `Care Training Course`

**Priority:**
- Course-specific alt text (from parent post) takes precedence
- Filename-based alt text is fallback

**Bulk Tool:**
- **Media Library → "Add Alt Text to All Images"** button
- Adds alt text to all existing images without alt text
- One-click bulk operation

**How it works:**
1. **On upload:** Automatically generates alt text from filename
2. **On display:** If alt text missing, generates from filename
3. **Bulk tool:** Processes all images without alt text

---

### 6. Breadcrumbs ✅

**Status:** Already implemented and configured

**Settings:**
- ✅ **Enable Breadcrumbs Function:** ON
- ✅ **Separator Character:** `/` (standard, can be changed to `»`)
- ✅ **Show Homepage Link:** ON with label "Home"
- ✅ **Show Category(s):** ON (displays category hierarchy)

**Current Implementation:**
- Breadcrumbs appear on all pages (except homepage)
- Schema markup included (BreadcrumbList)
- Visual breadcrumbs in templates
- Category hierarchy shown for filtered content

**Location:**
- `template-parts/breadcrumb.php` - Visual breadcrumbs
- `inc/seo.php` - Breadcrumb schema markup
- `inc/seo-schema.php` - Breadcrumb schema functions

**To change separator to `»`:**
Edit `template-parts/breadcrumb.php` line 27:
```php
<li class="breadcrumb-separator" aria-hidden="true">»</li>
```

---

## 📊 **CONTENT OPTIMIZATION (Already Implemented)**

### Primary Keyword Placement ✅

**Requirements (already in enhanced SEO scoring):**
- ✅ First 10% of content (or first 300 words)
- ✅ First 50% of SEO title
- ✅ URL slug
- ✅ Meta description

**Location:** `inc/page-editor-enhancements.php` - Enhanced SEO scoring system

---

### Content Length Scoring ✅

**Graduated scoring (already implemented):**
- ✅ 2500+ words: 100% score (20 points)
- ✅ 2000-2500 words: 70% score (14 points)
- ✅ 1500-2000 words: 60% score (12 points)
- ✅ 1000-1500 words: 40% score (8 points)
- ✅ 600-1000 words: 20% score (4 points)
- ✅ Below 600 words: 0% score

**Location:** `inc/page-editor-enhancements.php` - `cta_calculate_page_seo_score()`

---

### Keyword Density ✅

**Target (already implemented):**
- ✅ Optimal: 1-1.5% (5 points)
- ✅ Low: <1% (3 points)
- ✅ High: 1.5-2.5% (2 points)
- ✅ Over-optimization: >2.5% (0 points, warning)

**Calculation:** `(keyword count ÷ total words) × 100`

**Location:** `inc/page-editor-enhancements.php` - Enhanced SEO scoring

---

## 🎯 **USAGE GUIDE**

### Managing Redirects

1. **Auto-redirects:**
   - Change a page/post slug
   - Redirect is automatically created
   - No manual action needed

2. **Manual redirects:**
   - Go to **Tools → Redirects**
   - Enter source URL (old URL)
   - Enter target URL (new URL)
   - Select redirect type (301 or 302)
   - Click "Add Redirect"

3. **View redirects:**
   - Go to **Tools → Redirects**
   - See all redirects with hit counts
   - Delete redirects as needed

### Adding Alt Text to Images

1. **Automatic:**
   - Upload image with descriptive filename
   - Alt text is auto-generated
   - Example: `care-training-kent.jpg` → "Care Training Kent"

2. **Bulk tool:**
   - Go to **Media Library**
   - Click "Add Alt Text to All Images"
   - All images without alt text get filename-based alt text

3. **Manual:**
   - Edit image in Media Library
   - Add custom alt text
   - Custom alt text takes precedence

### External Links

- **Automatic:** All external links automatically open in new tab
- **No configuration needed:** Works automatically
- **SEO benefit:** External links help SEO (citing sources)

---

## 🔧 **TECHNICAL DETAILS**

### Database Structure

**Redirects Table:**
```sql
CREATE TABLE wp_cta_redirects (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    source_url varchar(255) NOT NULL,
    target_url varchar(255) NOT NULL,
    redirect_type tinyint(3) unsigned NOT NULL DEFAULT 301,
    status varchar(20) NOT NULL DEFAULT 'active',
    hit_count bigint(20) unsigned NOT NULL DEFAULT 0,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY source_url (source_url)
);
```

### Hooks Used

- `post_updated` - Auto-redirect on slug change
- `template_redirect` - Process redirects, attachment redirects
- `category_rewrite_rules` - Strip category base
- `term_link` - Fix category links
- `the_content` - Add external link attributes
- `wp_get_attachment_image_attributes` - Auto alt text
- `add_attachment` / `edit_attachment` - Auto alt on upload

### Performance

- **Redirects:** Cached in database, fast lookup
- **External links:** Processed on content output (minimal overhead)
- **Alt text:** Generated once, saved to meta (no repeated processing)
- **Category base:** Rewrite rules cached by WordPress

---

## ✅ **VERIFICATION CHECKLIST**

- [x] Auto-redirects work when changing slugs
- [x] Manual redirects can be created in admin
- [x] Category URLs don't include /category/
- [x] Attachment pages redirect to parent/homepage
- [x] External links open in new tab
- [x] External links don't have nofollow (SEO benefit)
- [x] Images auto-generate alt text from filename
- [x] Bulk alt text tool works
- [x] Breadcrumbs show Home and categories
- [x] Content length scoring matches Rank Math
- [x] Keyword density checks implemented
- [x] Primary keyword placement checks implemented

---

## 📝 **SUMMARY**

All Rank Math-style link and redirect configurations are now implemented:

✅ **Redirections** - Auto and manual
✅ **Strip Category Base** - Cleaner URLs
✅ **Redirect Attachments** - SEO value distribution
✅ **External Links** - Open in new tab (no nofollow)
✅ **Image SEO** - Auto alt text from filename
✅ **Breadcrumbs** - Already configured correctly
✅ **Content Optimization** - Already implemented in enhanced SEO scoring

**Result:** Professional-grade SEO configuration matching Rank Math best practices!
