# Enhanced Page Editor with SEO Tools

## Overview

The page editor has been enhanced to provide a comprehensive editing experience similar to posts, with integrated SEO tools, visual/code editor switching, and real-time SEO scoring.

## Features

### 1. Visual/Code Editor Toggle

**What it is:**
- Pages now have the same editor interface as posts
- Switch between **Visual** (WYSIWYG) and **Text** (HTML code) tabs
- Full WordPress editor toolbar with formatting options

**How to use:**
1. Edit any page in WordPress admin
2. Above the editor, you'll see **Visual** and **Text** tabs
3. Click **Visual** for WYSIWYG editing (like Word)
4. Click **Text** to edit HTML code directly

**Location:** Main content editor (above ACF fields)

---

### 2. Comprehensive SEO Meta Box

**What it is:**
A powerful SEO panel that appears when editing pages, providing:
- Live SEO score (0-100)
- Meta title/description editors with character counters
- Schema type selector
- Search result preview
- Quick action buttons

**How to access:**
1. Edit any page
2. Look for **"🔍 SEO & Schema Settings"** meta box (below editor, above ACF fields)
3. All SEO settings are in one place

---

### 3. SEO Score Calculator

**What it measures:**
- **Title length** (20 points) - Optimal: 50-60 characters
- **Meta description** (20 points) - Optimal: 120-160 characters
- **Featured image** (15 points) - Should be set
- **Content length** (25 points) - Optimal: 300+ words
- **Schema type** (10 points) - Specific type selected
- **Indexability** (10 points) - Page is indexable (not noindex)

**Score ranges:**
- **80-100:** Excellent (green) - Well optimized
- **60-79:** Good (yellow) - Room for improvement
- **0-59:** Needs work (red) - Focus on checklist items

**Live updates:**
- Score updates automatically as you type
- Real-time feedback on what needs fixing
- Color-coded indicators

---

### 4. Meta Title & Description Editor

**Features:**
- **Character counters** - Shows current length vs. recommended
- **Live preview** - See how it will appear in search results
- **Auto-generation** - Button to generate from page content
- **Color coding** - Red if too short/long, green if optimal

**How to use:**
1. Enter meta title (or leave blank to use page title)
2. Enter meta description (or leave blank to auto-generate)
3. Watch character counters turn green when optimal
4. See live preview update in real-time

**Best practices:**
- **Title:** 50-60 characters (includes site name)
- **Description:** 120-160 characters (compelling, includes keywords)

---

### 5. Schema Type Selector

**What it is:**
Dropdown to select the Schema.org type for your page.

**Options:**
- **WebPage** (default) - Generic page
- **HomePage** - Homepage
- **AboutPage** - About us page
- **ContactPage** - Contact page
- **CollectionPage** - Resource/archive pages
- **FAQPage** - Pages with FAQs

**Why it matters:**
- Helps search engines understand page content
- Enables rich snippets in search results
- Improves SEO visibility

---

### 6. Search Result Preview

**What it shows:**
Live preview of how your page will appear in Google search results:
- **Title** (blue, clickable)
- **URL** (green, breadcrumb style)
- **Description** (gray, snippet)

**Updates in real-time** as you edit meta title/description.

---

### 7. Quick Action Buttons

**Available actions:**
- **👁️ View Page** - Opens page in new tab
- **🔍 Test Schema** - Opens Google Rich Results Test
- **📋 Copy Meta Tags** - Copies meta tags to clipboard

---

### 8. SEO Checklist

**Visual checklist showing:**
- ✅ Page has title
- ✅ Meta description set
- ✅ Featured image set
- ✅ Content length (300+ words)
- ✅ Schema type selected

**Auto-updates** based on current page state.

---

### 9. Robots Meta Controls

**Options:**
- **Noindex** - Hide page from search engines
- **Nofollow** - Don't follow links on page

**When to use:**
- **Noindex:** Only if page should NOT appear in search results (e.g., thank you pages, internal pages)
- **Nofollow:** Rarely needed (usually for user-generated content)

**⚠️ Warning:** Only use noindex if you're certain the page shouldn't be searchable.

---

## How to Use

### Editing Page Content

1. **Go to Pages → Edit**
2. **Use main editor** (Visual/Text tabs) for main content
3. **Use ACF "Page Sections"** field for structured sections
4. **Both work together** - editor for freeform content, ACF for structured sections

### Setting SEO

1. **Scroll to "🔍 SEO & Schema Settings"** meta box
2. **Enter meta title** (or leave blank for auto)
3. **Enter meta description** (or click "Generate from content")
4. **Select schema type** from dropdown
5. **Check SEO score** - aim for 80+
6. **Review search preview** - see how it will look
7. **Save page**

### Improving SEO Score

1. **Check current score** at top of SEO meta box
2. **Review checklist** - see what's missing
3. **Fix items** one by one:
   - Add featured image
   - Optimize title length (50-60 chars)
   - Optimize description length (120-160 chars)
   - Add more content (300+ words)
   - Select specific schema type
4. **Watch score improve** in real-time

---

## Technical Details

### File Location
- **Main file:** `inc/page-editor-enhancements.php`
- **Included in:** `functions.php` (line ~90)

### Hooks Used
- `add_meta_boxes` - Adds SEO meta box
- `save_post` - Saves SEO settings
- `wp_head` - Outputs robots meta (via existing `cta_robots_meta()`)
- `admin_footer` - Adds editor help text

### Data Storage
- **Meta title:** ACF field `page_seo_meta_title`
- **Meta description:** ACF field `page_seo_meta_description`
- **Schema type:** ACF field `page_schema_type`
- **Noindex:** Post meta `_cta_noindex`
- **Nofollow:** Post meta `_cta_nofollow`

### Integration
- **Works with existing SEO system** - Uses `cta_get_meta_description()`, `cta_robots_meta()`
- **Respects ACF fields** - Uses `cta_safe_get_field()` / `cta_safe_update_field()`
- **Sitemap integration** - Noindex pages excluded from sitemap automatically

---

## Benefits

### For Content Editors
- ✅ **Easy editing** - Visual editor like posts
- ✅ **One-stop SEO** - All SEO settings in one place
- ✅ **Real-time feedback** - See score improve as you work
- ✅ **No guessing** - Clear indicators of what's good/bad

### For SEO
- ✅ **Consistent optimization** - Every page gets proper meta tags
- ✅ **Schema markup** - Proper structured data
- ✅ **Search visibility** - Better rankings potential
- ✅ **Rich snippets** - Enhanced search results

### For Developers
- ✅ **Centralized** - All SEO logic in one place
- ✅ **Extensible** - Easy to add more checks
- ✅ **Maintainable** - Clean, documented code
- ✅ **Integrated** - Works with existing systems

---

## Troubleshooting

### Editor tabs not showing?
- **Check:** Is Gutenberg enabled for pages?
- **Fix:** The system ensures classic editor is available
- **Note:** If using Classic Editor plugin, it should work automatically

### SEO score not updating?
- **Check:** JavaScript enabled in browser?
- **Fix:** Refresh page, check browser console for errors
- **Note:** Score updates on input, not on save

### Meta tags not appearing?
- **Check:** Are fields saved? (Click "Update" button)
- **Check:** Is page published? (Draft pages may not show)
- **Fix:** Clear cache if using caching plugin

### Noindex not working?
- **Check:** Is `_cta_noindex` meta saved? (Check post meta in database)
- **Check:** Is `cta_robots_meta()` filter active?
- **Fix:** Verify meta box save function is running

---

## Future Enhancements

Potential additions:
- [ ] Keyword density checker
- [ ] Internal link suggestions
- [ ] Content readability score
- [ ] Image alt text checker
- [ ] H1/H2 structure validator
- [ ] Social media preview (Facebook/Twitter)
- [ ] Competitor analysis integration
- [ ] SEO history tracking

---

## Summary

The enhanced page editor provides:
1. **Visual/code editor** - Like posts, easy to use
2. **SEO meta box** - All SEO settings in one place
3. **Live SEO score** - Real-time feedback
4. **Search preview** - See how it will look
5. **Quick actions** - Test schema, view page, copy tags

**Result:** Easier content editing + better SEO = Better search rankings.
