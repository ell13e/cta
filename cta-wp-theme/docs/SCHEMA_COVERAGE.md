# Schema Markup Coverage

## Overview

**YES - Every page on your site has schema markup automatically generated.** The schema is created dynamically when pages are viewed, so you don't need to manually add schema when creating new courses or events in WordPress.

## Universal Schema (All Pages)

Every single page automatically gets these schemas:

1. **EducationalOrganization** - Your organization info (name, address, contact, rating)
2. **LocalBusiness** - Local SEO schema (address, geo coordinates, service area)
3. **WebSite** - Site-wide schema with search functionality
4. **BreadcrumbList** - Navigation breadcrumbs (except homepage)

These are output by `cta_schema_markup()` which runs on **every page** via `wp_head` hook.

## Page-Specific Schema

### ✅ Single Course Pages (`/courses/course-name/`)

**Automatic Schema:** `Course` schema

**Location:** `inc/seo.php` lines 1126-1239

**When it runs:** Automatically when viewing any single course page (`is_singular('course')`)

**What's included:**
- Course name, description, URL
- Provider (EducationalOrganization)
- Price (Offer schema)
- Duration (timeRequired in ISO 8601)
- Level (educationalLevel)
- Accreditation (educationalCredentialAwarded)
- Location (Place schema)
- Image (ImageObject with dimensions and alt text)
- Keywords (from course categories)

**You don't need to do anything** - just create/edit a course in WordPress and the schema is automatically generated from:
- Post title
- Post content/excerpt
- ACF fields: `course_price`, `course_duration`, `course_level`, `course_accreditation`
- Featured image
- Course categories

---

### ✅ Single Course Event Pages (`/upcoming-courses/event-name/`)

**Automatic Schema:** `Event` schema (Google's preferred format)

**Location:** `inc/event-schema.php`

**When it runs:** Automatically when viewing any single course event page (`is_singular('course_event')`)

**What's included:**
- Event name, description
- Start date/time (startDate)
- End date/time (endDate)
- Event status (Scheduled, Cancelled, etc.)
- Location (Place with full address)
- Offers (pricing, availability, validFrom)
- Organizer (your organization)
- Performer (linked course)
- Aggregate rating (if reviews exist)

**You don't need to do anything** - just create/edit a course event in WordPress and the schema is automatically generated from:
- Post title
- ACF fields: `event_date`, `event_time`, `event_location`, `linked_course`
- Event meta: `event_spaces`, `event_price`
- Linked course data

---

### ✅ Blog Posts (`/news/article-name/`)

**Automatic Schema:** `Article` schema

**Location:** `inc/seo.php` lines 1307-1342

**What's included:**
- Headline, description, URL
- Published/modified dates
- Author (Organization)
- Publisher (with logo)
- Featured image

---

### ✅ Pages with FAQs

**Automatic Schema:** `FAQPage` schema

**Location:** `inc/seo.php` lines 1344-1386 AND `inc/seo-schema.php` (for permanent pages)

**What's included:**
- FAQPage type
- mainEntity array with Question/Answer pairs

**Sources (checked in order):**
1. ACF repeater field `faqs` (question/answer sub-fields)
2. FAQ custom post type (for FAQs page)
3. HTML accordion patterns in content

---

### ✅ Permanent Pages (Home, About, Contact, etc.)

**Automatic Schema:** Specific page types

**Location:** `inc/seo-schema.php` function `cta_output_permanent_page_schema()`

**Page Types:**
- **Homepage:** WebSite + Organization + BreadcrumbList
- **About:** AboutPage schema
- **Contact:** ContactPage schema
- **Group Training:** WebPage + Service with OfferCatalog
- **FAQs:** FAQPage schema (enhanced version)
- **Resource pages:** CollectionPage schema

---

### ✅ Regular Pages (Any other WordPress page)

**Automatic Schema:** `WebPage` schema

**Location:** `inc/seo.php` lines 1387-1410 (just added)

**What's included:**
- WebPage type
- Name, description, URL
- Primary image (if featured image exists)
- Links to Organization and WebSite

**Note:** This ensures ALL pages have WebPage schema, not just permanent pages.

---

## Archive Pages

### Course Archive (`/courses/`)
- Organization schema
- WebSite schema
- BreadcrumbList schema

### Course Event Archive (`/upcoming-courses/`)
- Organization schema
- WebSite schema
- Event collection schema (from `event-schema.php`)

### Category Archives
- Organization schema
- WebSite schema
- BreadcrumbList schema

---

## How It Works

### Automatic Generation

All schema is generated **dynamically** when pages are viewed. There's no manual schema creation needed.

**For Courses:**
1. Create/edit course in WordPress admin
2. Fill in ACF fields (price, duration, level, etc.)
3. Set featured image
4. Assign categories
5. **Schema is automatically generated** when someone visits the page

**For Course Events:**
1. Create/edit course event in WordPress admin
2. Link to a course
3. Set event date, time, location
4. **Schema is automatically generated** when someone visits the page

### Schema Output

All schema is output as JSON-LD in the `<head>` section:

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Course",
  ...
}
</script>
```

### Multiple Schemas Per Page

Pages can have multiple schema blocks:
- Organization schema (always)
- LocalBusiness schema (always)
- WebSite schema (always)
- Page-specific schema (Course, Event, Article, etc.)
- BreadcrumbList schema (if not homepage)
- FAQPage schema (if page has FAQs)

This is **normal and recommended** - Google can handle multiple schema types on one page.

---

## Verification

### Check Schema on Any Page

1. **View page source** (right-click → View Page Source)
2. **Search for** `application/ld+json`
3. **Copy the JSON** and paste into [Google Rich Results Test](https://search.google.com/test/rich-results)
4. **Or use** [Schema.org Validator](https://validator.schema.org/)

### What to Look For

**Single Course Page:**
- ✅ Course schema with all fields populated
- ✅ Organization schema
- ✅ LocalBusiness schema
- ✅ BreadcrumbList schema

**Single Course Event Page:**
- ✅ Event schema with dates, location, offers
- ✅ Organization schema
- ✅ LocalBusiness schema
- ✅ BreadcrumbList schema

**Regular Page:**
- ✅ WebPage schema
- ✅ Organization schema
- ✅ LocalBusiness schema
- ✅ BreadcrumbList schema

---

## Troubleshooting

### Schema Not Appearing?

1. **Check ACF fields are filled** - Course schema needs price, duration, level
2. **Check featured image** - Image schema needs a featured image
3. **Clear cache** - If using caching plugins
4. **Check page type** - Make sure you're on the right page type (single course vs archive)

### Schema Validation Errors?

1. **Use Google Rich Results Test** to see specific errors
2. **Check required fields** - Course schema needs name, description, provider
3. **Check date formats** - Event dates must be ISO 8601 format
4. **Check URLs** - All URLs must be absolute (full URLs)

### Missing Fields?

- **Price not showing?** - Check `course_price` ACF field is set
- **Duration not showing?** - Check `course_duration` ACF field format (e.g., "1 day" or "3 hours")
- **Image not showing?** - Set featured image on the course/event
- **Location not showing?** - Check `event_location` ACF field for events

---

## Summary

✅ **Every page has schema** - Organization, LocalBusiness, WebSite on all pages

✅ **Course pages get Course schema automatically** - No manual work needed

✅ **Event pages get Event schema automatically** - No manual work needed

✅ **All schema is generated dynamically** - Created when pages are viewed

✅ **No plugins needed** - All schema is built into the theme

**You just need to:**
1. Create/edit courses and events in WordPress
2. Fill in the ACF fields
3. Set featured images
4. **That's it!** Schema is generated automatically.
