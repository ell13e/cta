# Group Training 404 & Redirect Loop – What’s Going On

## Request flow for `/group-training/`

1. **WordPress parses the URL**  
   Uses rewrite rules (from Settings → Permalinks) to resolve the request.  
   - Page with slug `group-training` should match the `pagename` rule.  
   - Custom post types use different slugs: `courses`, `upcoming-courses`, `course-category`. None use `group-training`, so there’s no CPT slug conflict.

2. **Main query runs**  
   If the page exists and rewrite rules are correct, the page is found and `is_404()` is false.  
   If the page isn’t found (missing page or wrong/missing rules), WordPress sets `is_404()`.

3. **`template_redirect` (priority order)**  
   - **Priority 0** (runs first):  
     - `cta_create_missing_static_page_on_404` (data-importer) – only runs when `is_404()`.  
     - `cta_resource_download_template_redirect` – only runs when `cta_resource_download=1`.  
     - `cta_maybe_show_coming_soon_for_unpublished_pages` – only on 404, looks for draft/pending pages with that slug.  
   - **Priority 1**:  
     - `cta_process_redirects` (seo-links-redirects) – **checks DB table `wp_cta_redirects`**. If `source_url` matches the current path, it redirects and exits.  
     - `cta_redirect_attachments` – only for attachment pages.  
     - `cta_handle_specific_redirects` (seo) – hardcoded list (contact-us, course slugs). No group-training.  
   - **Priority 5**:  
     - `cta_enforce_canonical_redirect` – bails when `is_404()`, so it doesn’t run for 404s.

So for a **404** on `/group-training/`, the only code that can redirect is the **404 handler** in `data-importer.php`.

## Why you might get a 404

1. **Page doesn’t exist**  
   The “Group Training” page was never created (e.g. importer not run).  
   - The 404 handler will create it (via `cta_create_static_pages()`) and redirect to `/group-training/` once. After that, the page should load.

2. **Stale rewrite rules**  
   Permalink structure was changed or rules were never flushed after theme/CPT registration.  
   - The 404 handler flushes and redirects. If the next request still 404s (e.g. cache or another plugin), you can get a **redirect loop** (now mitigated with `cta_rewrite_flush`).

3. **Redirect table**  
   If `wp_cta_redirects` has a row with `source_url` = `/group-training/` or `/group-training`, that redirect runs at priority 1 **before** the 404 handler only for **non-404** requests.  
   - If that target URL redirects back to `/group-training/` (or to something that 404s and then the handler redirects to `/group-training/`), you get a loop.  
   - **Check:** `SELECT * FROM wp_cta_redirects WHERE source_url LIKE '%group-training%';`

4. **Caching**  
   A page cache (plugin, Cloudflare, etc.) might be serving a cached 404 for `/group-training/`.  
   - Flushing permalinks or creating the page won’t help until cache is cleared or bypassed (e.g. incognito, different URL).

5. **Path parsing (subdirectory installs)**  
   The 404 handler derives the slug from `REQUEST_URI` and `home_url()`.  
   - If the site lives in a subdirectory (e.g. `https://example.com/site/`), the code strips the subdirectory so the slug is still `group-training`. That’s correct.  
   - If `home_url()` or the request is wrong, the parsed slug could be wrong and the handler wouldn’t run for `group-training`.

## Why you got ERR_TOO_MANY_REDIRECTS

- Request to `/group-training/` → 404.  
- Handler sees page exists, flushes rewrite rules, redirects to `/group-training/`.  
- Second request to `/group-training/` still 404s (e.g. cache or rules still not right).  
- Handler runs again and redirects again → loop.

**Fix applied:** When the handler flushes and redirects, it now sends the user to  
`/group-training/?cta_rewrite_flush=1`.  
If that URL still 404s, the handler sees `cta_rewrite_flush` and **does not redirect again**, so the loop stops.

## What to do

1. **Confirm the page exists**  
   In WP Admin: Pages → look for “Group Training” with slug `group-training`. If it’s missing, run **Tools → Import CTA Data** and use “Create missing static pages”.

2. **Flush permalinks**  
   **Settings → Permalinks** → click **Save changes** (no need to change options). This rebuilds rewrite rules.

3. **Check redirects**  
   In the database:  
   `SELECT * FROM wp_cta_redirects WHERE source_url LIKE '%group-training%';`  
   Remove or fix any row that redirects away from or back to `/group-training/` in a loop.

4. **Bypass cache**  
   Try `/group-training/` in an incognito window and/or after clearing any page/cache layer.

5. **If you still see 404 with `?cta_rewrite_flush=1`**  
   That means the handler has already run and stopped the loop. The underlying issue is either: missing page (create it as in step 1), wrong/stale rules (step 2), or cache (step 4).

## Code references

- 404 handler: `ccs-wp-theme/inc/data-importer.php` → `cta_create_missing_static_page_on_404()` (around line 1096).  
- Static page list (includes Group Training): same file, `cta_create_static_pages()` (around line 862).  
- Redirects table: `ccs-wp-theme/inc/seo-links-redirects.php` → `cta_process_redirects()` (priority 1 on `template_redirect`).
