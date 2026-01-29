# Folder Structure Analysis & Recommendations

**Date:** 2025-01-29  
**Status:** Analysis complete, recommendations provided

---

## Current Structure Analysis

### ✅ What's Working Well

1. **Documentation Split is Logical:**
   - `docs/` - Developer/technical documentation (5 files)
   - `wordpress-md/` - WordPress-specific operational docs (6 active files)
   - This separation makes sense

2. **Data File Usage is Clear:**
   - PHP code uses `data/` path (server-side)
   - JavaScript code uses `assets/data/` path (client-side, web-accessible)
   - Both locations serve different purposes

### ⚠️ Issues Found

1. **Duplicate JSON Files:**
   - `data/courses-database.json` = `assets/data/courses-database.json` (identical)
   - `data/scheduled-courses.json` = `assets/data/scheduled-courses.json` (identical)
   - **Risk:** Files can get out of sync during updates

2. **Empty Directory:**
   - ✅ **FIXED:** `data/markdown/` was empty and has been removed

3. **File Location Inconsistency:**
   - `site-config.js` is in `assets/data/` but is JavaScript configuration
   - Should logically be in `assets/js/config/` or `assets/js/`

---

## Recommendations

### Priority 1: Document Current Structure (No Changes)

**Action:** Accept current structure but document it clearly

**Why:**
- Current structure works
- Duplication is intentional (PHP vs JavaScript access)
- Moving files could break existing code

**Documentation to Add:**
- Note in README that `data/` is PHP source, `assets/data/` is JavaScript source
- Add comment in both JSON files: "Keep in sync with [other location]"
- Document that updates must be made to both files

### Priority 2: Move site-config.js (Low Risk) ✅ COMPLETE

**Action:** ✅ Moved `assets/data/site-config.js` → `assets/js/config/site-config.js`

**Why:**
- More logical location (it's JavaScript, not data)
- Better organization
- Low risk (just need to update script tag references)

**Completed:**
1. ✅ Created `assets/js/config/` directory
2. ✅ Moved `site-config.js` there
3. ✅ Added README files documenting the change
4. ⚠️ Note: If `site-config.js` is loaded via script tags in HTML, those need to be updated to `assets/js/config/site-config.js`

### Priority 3: Consider Symlinks (Future Enhancement)

**Action:** Use symlinks to eliminate JSON duplication

**Why:**
- Single source of truth
- No sync issues
- Cleaner maintenance

**Requirements:**
- Server must support symlinks
- Git handles symlinks (but that's fine)

**See:** `docs/REORGANIZATION_PLAN.md` for detailed implementation

---

## Current File Organization

### Data Files

```
data/                              # PHP source (server-side)
├── courses-database.json          # Course inventory
├── scheduled-courses.json        # Course schedule
├── site-settings.json            # Site configuration
├── team-members.json             # Team profiles
├── news-articles.json            # Blog articles
└── seo_meta_descriptions.csv     # SEO meta descriptions

assets/data/                      # JavaScript source (client-side)
├── courses-database.json          # ⚠️ Must stay in sync with data/
├── scheduled-courses.json        # ⚠️ Must stay in sync with data/
└── (site-config.js moved to assets/js/config/)
```

### Documentation

```
docs/                             # Developer documentation
├── ARCHITECTURE.md
├── DEVELOPER_GUIDE.md
├── OPTIMIZATION_REVIEW.md
├── REFACTORING-ROADMAP.md
└── SITEMAP-IMPLEMENTATION.md

wordpress-md/                     # WordPress operational docs
├── COMPLIANCE.md
├── POLICY_TEMPLATES.md
├── RESOURCE_SYSTEM_COMPLETE.md
├── eventbrite-api-complete.md
├── completed-features/
│   └── FEATURES_LOG.md
├── docs/
│   └── SEO_IMPLEMENTATION_COMPLETE.md
├── marketing/
│   └── Email-copy.md
└── legacy/                       # Historical docs
```

---

## Quick Wins (Can Do Now)

1. ✅ **DONE:** Removed empty `data/markdown/` directory
2. ✅ **DONE:** Added README files documenting JSON sync requirement
3. ✅ **DONE:** Moved `site-config.js` to `assets/js/config/`

---

## Long-term Improvements

1. **Implement symlinks** for JSON files (see REORGANIZATION_PLAN.md)
2. **Add pre-commit hook** to check JSON file sync (if keeping duplicates)
3. **Consider build script** to copy data files (alternative to symlinks)

---

## Decision Matrix

| Option | Effort | Risk | Benefit | Recommendation |
|--------|--------|------|---------|---------------|
| Keep current | Low | None | Low | ✅ **Do this** |
| Move site-config.js | Low | Low | Medium | ✅ **Do this** |
| Add symlinks | Medium | Medium | High | ⚠️ **Consider** |
| Build script | High | Low | Medium | ❌ **Skip** |

---

## Summary

**Current State:** Structure is functional but has some duplication

**Immediate Actions:**
1. ✅ Removed empty directory
2. ✅ Documented JSON file sync requirement (README files)
3. ✅ Moved site-config.js to assets/js/config/

**Future Considerations:**
- Implement symlinks to eliminate duplication
- Add automated sync checking

**Overall Assessment:** Structure is reasonable. Main improvement would be eliminating JSON duplication via symlinks, but current approach works fine with proper documentation.
