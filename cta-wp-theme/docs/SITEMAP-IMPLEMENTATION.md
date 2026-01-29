# Smart Sitemap Implementation

## Overview
The WordPress theme now has a fully automated, intelligent sitemap system that dynamically updates whenever content is added or modified.

## What's Included in the Sitemap

### ✅ Automatically Included
- **All Pages** (published) - Priority: 0.7-0.9
- **All Blog Posts** (published) - Priority: 0.6
- **All Courses** (published) - Priority: 0.8
- **All Upcoming Events** (published, active, future dates) - Priority: 0.85
- **Course Categories** (taxonomy) - Dynamic priority

### ❌ Excluded
- Draft/private content
- Inactive course events
- Past course events
- Pages marked as noindex
- Author archives (noindexed per SEO strategy)
- Tags (noindexed per SEO strategy)
- User profiles

## High Priority Pages (0.9 Priority)
These pages are marked as most important for SEO:
- CQC Compliance Hub
- Training Guides
- Downloadable Resources
- FAQs
- Group Training
- Contact
- About
- Safeguarding Training
- Manual Handling Training

## Automatic Updates

### When Sitemap Updates Automatically
The sitemap cache is cleared and search engines are notified when:
1. ✅ A page is published or updated
2. ✅ A blog post is published or updated
3. ✅ A course is created or modified
4. ✅ A scheduled course/event is added or changed
5. ✅ A course category is added, edited, or deleted

### Search Engine Notifications ✅ IMPLEMENTED
- ✅ Automatically pings Google and Bing when content changes
- ✅ Throttled to once per hour to prevent spam
- ✅ Uses non-blocking HTTP requests (doesn't slow down publishing)
- ✅ Daily cleanup cron job (runs at 3 AM WordPress local time)
- ✅ Timezone-aware scheduling (respects WordPress timezone settings)

## Sitemap URLs

### Main Sitemap
- **URL**: `https://yoursite.com/wp-sitemap.xml`
- **Format**: XML Sitemap Index (links to sub-sitemaps)

### Sub-Sitemaps (Auto-Generated)
- `wp-sitemap-posts-page-1.xml` - All pages
- `wp-sitemap-posts-post-1.xml` - Blog posts
- `wp-sitemap-posts-course-1.xml` - Courses
- `wp-sitemap-posts-course_event-1.xml` - Events
- `wp-sitemap-taxonomies-course_category-1.xml` - Course categories

## Admin Interface

### Access the Sitemap Monitor
**Location**: WordPress Admin → Tools → Sitemap Monitor

### Features
1. **Overview Dashboard**
   - See total URLs in sitemap
   - View counts by content type
   - Check priority and change frequency settings

2. **Manual Refresh**
   - Clear sitemap cache manually
   - Ping search engines on demand
   - See last ping timestamp

3. **Submit to Search Engines**
   - Direct links to Google Search Console
   - Direct links to Bing Webmaster Tools
   - Copy-ready sitemap URL

## Technical Implementation

### Files Modified
- **`inc/seo.php`** - Core sitemap logic (lines ~900-1300)

### WordPress Hooks Used
```php
// Post type inclusion
add_filter('wp_sitemaps_post_types', 'cta_sitemap_post_types');

// Taxonomy inclusion
add_filter('wp_sitemaps_taxonomies', 'cta_sitemap_taxonomies');

// Entry customization (priority, changefreq, lastmod)
add_filter('wp_sitemaps_posts_entry', 'cta_sitemap_entry', 10, 3);

// Query filtering (exclude noindex, inactive)
add_filter('wp_sitemaps_posts_query_args', 'cta_sitemap_exclude_noindex', 10, 2);

// Max URLs per sitemap
add_filter('wp_sitemaps_max_urls', 'cta_sitemap_max_urls');

// User exclusion
add_filter('wp_sitemaps_add_provider', 'cta_sitemap_remove_users', 10, 2);

// Cache clearing
add_action('save_post', 'cta_flush_sitemap_cache', 10, 2);
add_action('delete_post', 'cta_flush_sitemap_cache', 10, 2);
add_action('create_term', 'cta_flush_sitemap_taxonomy_cache', 10, 3);
add_action('edit_term', 'cta_flush_sitemap_taxonomy_cache', 10, 3);
add_action('delete_term', 'cta_flush_sitemap_taxonomy_cache', 10, 3);
```

## SEO Benefits

### Improved Crawling
- **lastmod dates**: Search engines know exactly when content was updated
- **Priority signals**: Important pages are marked as such
- **Change frequency**: Tells crawlers how often to check each page type

### Better Indexing
- Courses indexed with 0.8 priority (high value content)
- Upcoming events marked as 0.85 priority with daily change frequency
- Stale/inactive content automatically excluded
- No wasted crawl budget on noindex pages

### Automatic Discovery
- Search engines notified immediately when new content is published
- No manual sitemap submission needed after initial setup
- robots.txt includes sitemap URL for auto-discovery

## Robots.txt Integration

The sitemap is automatically referenced in robots.txt:
```
# robots.txt for Continuity Training Academy
User-agent: *
Allow: /

Sitemap: https://yoursite.com/wp-sitemap.xml
```

**Location**: `https://yoursite.com/robots.txt`

## Initial Setup Required

### 1. Submit to Google Search Console
1. Go to: https://search.google.com/search-console
2. Add your property if not already added
3. Navigate to "Sitemaps" in the left menu
4. Enter: `wp-sitemap.xml`
5. Click "Submit"

### 2. Submit to Bing Webmaster Tools
1. Go to: https://www.bing.com/webmasters
2. Add your site if not already added
3. Navigate to "Sitemaps"
4. Enter: `https://yoursite.com/wp-sitemap.xml`
5. Click "Submit"

### 3. Verify Sitemap is Working
1. Visit: `https://yoursite.com/wp-sitemap.xml`
2. Should see XML with links to sub-sitemaps
3. Check one sub-sitemap to verify content is listed

## Monitoring & Maintenance

### Regular Checks (Recommended)
- **Weekly**: Check Sitemap Monitor dashboard for stats
- **Monthly**: Review Google Search Console coverage reports
- **After major content updates**: Manually refresh sitemap from admin

### Troubleshooting

#### Sitemap returns 404
**Fix**: Flush permalinks
1. Go to Settings → Permalinks
2. Click "Save Changes" (no changes needed)
3. This regenerates rewrite rules

#### Old content still in sitemap
**Fix**: Clear cache manually
1. Go to Tools → Sitemap Monitor
2. Click "Refresh Sitemap & Ping Search Engines"

#### Events not appearing
**Check**:
- Event must be published (not draft)
- Event must be active (check meta field)
- Event date must be in the future

#### Search engines not getting notified
**Fix**: Check ping throttle
- Pings are limited to once per hour
- Check "Last pinged" timestamp in admin
- Wait 1 hour between pings to avoid being flagged as spam

## Performance Notes

### Caching
- Sitemaps are cached by WordPress (transients)
- Cache automatically cleared when content changes
- Manual clearing available in admin
- Typical cache time: until next content update

### Load Impact
- Minimal overhead on page publishing (non-blocking pings)
- Sitemap generation is lazy (only when requested)
- Sub-sitemaps reduce individual file size
- Max 2000 URLs per sub-sitemap (can handle very large sites)

## Future Enhancements (Optional)

### Potential Additions
- [ ] Image sitemap for course thumbnails
- [ ] Video sitemap if video content added
- [ ] News sitemap for blog posts
- [ ] Automatic submission to additional search engines
- [ ] Sitemap analytics dashboard
- [ ] HTML sitemap page for users

## Support & Questions

For sitemap issues, check:
1. WordPress admin: Tools → Sitemap Monitor
2. WordPress admin: Tools → Sitemap Diagnostic (existing tool)
3. Server error logs for PHP errors
4. Google Search Console for indexing issues

## References

- [WordPress Core Sitemaps Documentation](https://make.wordpress.org/core/2020/07/22/new-xml-sitemaps-functionality-in-wordpress-5-5/)
- [Google Sitemap Guidelines](https://developers.google.com/search/docs/advanced/sitemaps/overview)
- [Bing Sitemap Guidelines](https://www.bing.com/webmasters/help/sitemaps-3b5cf6ed)
