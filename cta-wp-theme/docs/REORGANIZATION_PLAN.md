# Codebase Reorganization Plan

**Date:** 2025-01-29  
**Status:** ✅ Implementation Complete

---

## Current Issues

### 1. Duplicate Data Files
- `data/courses-database.json` and `assets/data/courses-database.json` (identical)
- `data/scheduled-courses.json` and `assets/data/scheduled-courses.json` (identical)
- **Reason:** PHP uses `data/`, JavaScript needs `assets/data/` for web access
- **Impact:** Maintenance burden - must update both files

### 2. Empty Directories
- `data/markdown/` is empty (should be removed)

### 3. File Location Inconsistencies
- `site-config.js` is in `assets/data/` but is JavaScript configuration (should be in `assets/js/config/`)

### 4. Documentation Split
- `docs/` - Developer documentation (5 files)
- `wordpress-md/` - WordPress-specific documentation (6 active files + legacy)
- **Note:** This split is actually logical - keep as is

---

## Proposed Reorganization

### Option A: Symlink Approach (Recommended)
**Keep single source of truth, symlink for JavaScript access**

```
data/
├── courses-database.json          (source of truth)
├── scheduled-courses.json        (source of truth)
├── site-settings.json
├── team-members.json
├── news-articles.json
└── seo_meta_descriptions.csv

assets/
├── data/
│   ├── courses-database.json     (symlink → ../../data/courses-database.json)
│   └── scheduled-courses.json   (symlink → ../../data/scheduled-courses.json)
└── js/
    └── config/
        └── site-config.js        (moved from assets/data/)
```

**Pros:**
- Single source of truth
- No duplication
- Automatic sync

**Cons:**
- Requires symlink support (most servers support this)
- Git doesn't track symlinks well (but that's fine - we want the source)

### Option B: Build Script Approach
**Keep source in `data/`, copy to `assets/data/` on build**

```
data/
├── courses-database.json          (source)
├── scheduled-courses.json         (source)
└── ...

assets/
└── data/
    ├── courses-database.json      (generated on build)
    └── scheduled-courses.json     (generated on build)
```

**Pros:**
- Works everywhere (no symlink requirement)
- Clear separation of source vs. generated

**Cons:**
- Requires build step
- Must remember to run build script

### Option C: Keep Current Structure (Simplest)
**Accept duplication, document it clearly**

```
data/                              (PHP source)
├── courses-database.json
├── scheduled-courses.json
└── ...

assets/data/                       (JavaScript source - must stay in sync)
├── courses-database.json
├── scheduled-courses.json
└── site-config.js
```

**Pros:**
- No changes needed
- Works everywhere
- Clear separation

**Cons:**
- Manual sync required
- Risk of files getting out of sync

---

## Recommended Solution: Option A (Symlinks)

### Implementation Steps

1. **Remove empty directory:**
   ```bash
   rmdir data/markdown/
   ```

2. **Move site-config.js:**
   ```bash
   mkdir -p assets/js/config
   mv assets/data/site-config.js assets/js/config/
   ```

3. **Create symlinks:**
   ```bash
   cd assets/data
   ln -s ../../data/courses-database.json courses-database.json
   ln -s ../../data/scheduled-courses.json scheduled-courses.json
   ```

4. **Update JavaScript references:**
   - Update `site-config.js` references from `assets/data/site-config.js` to `assets/js/config/site-config.js`
   - No changes needed for JSON files (paths stay the same)

5. **Update documentation:**
   - Document that `data/` is source of truth
   - Document that `assets/data/` contains symlinks
   - Add note about symlink requirement

### Fallback: If Symlinks Don't Work

If symlinks aren't supported, use Option C but add:
- Clear documentation about keeping files in sync
- Pre-commit hook to check for differences
- Build script to copy files (optional)

---

## File Organization Summary

### Final Structure

```
cta-wp-theme/
├── data/                          # PHP-accessible data (source of truth)
│   ├── courses-database.json
│   ├── scheduled-courses.json
│   ├── site-settings.json
│   ├── team-members.json
│   ├── news-articles.json
│   └── seo_meta_descriptions.csv
│
├── assets/
│   ├── data/                      # JavaScript-accessible data (symlinks)
│   │   ├── courses-database.json  (symlink)
│   │   └── scheduled-courses.json (symlink)
│   └── js/
│       └── config/
│           └── site-config.js     (moved from assets/data/)
│
├── docs/                          # Developer documentation
│   ├── ARCHITECTURE.md
│   ├── DEVELOPER_GUIDE.md
│   ├── OPTIMIZATION_REVIEW.md
│   ├── REFACTORING-ROADMAP.md
│   └── SITEMAP-IMPLEMENTATION.md
│
└── wordpress-md/                  # WordPress-specific documentation
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
    └── legacy/                    # Historical docs
```

---

## Benefits

1. **Single Source of Truth:** Data files only exist once
2. **Clear Organization:** JavaScript config in JS folder
3. **No Duplication:** Symlinks eliminate sync issues
4. **Better Maintainability:** Update once, works everywhere
5. **Logical Structure:** Related files grouped together

---

## Migration Checklist

- [x] Remove `data/markdown/` empty directory
- [x] Move `site-config.js` to `assets/js/config/`
- [x] Create symlinks in `assets/data/`
- [x] Update JavaScript references to `site-config.js`
- [x] Test PHP data loading (should still work)
- [x] Test JavaScript data loading (should still work)
- [x] Update documentation
- [x] Add note about symlink requirement in README

**Status:** ✅ All migration steps complete (2025-01-29)

---

## ✅ Implementation Complete

**Status:** Symlinks implemented (2025-01-29)

All migration steps completed:
- ✅ Removed empty `data/markdown/` directory
- ✅ Moved `site-config.js` to `assets/js/config/`
- ✅ Created symlinks in `assets/data/` pointing to `data/` files
- ✅ Updated documentation to reflect symlink implementation

**Result:** Single source of truth established. No duplication, no manual sync required.
