# Data Directory

**Purpose:** PHP-accessible data files (server-side, source of truth)

## Files

- `courses-database.json` - Course inventory (source of truth)
- `scheduled-courses.json` - Course schedule (source of truth)
- `site-settings.json` - Site configuration
- `team-members.json` - Team member profiles
- `news-articles.json` - Blog articles
- `seo_meta_descriptions.csv` - SEO meta descriptions

## ⚠️ Important: Single Source of Truth

**`courses-database.json` and `scheduled-courses.json` are the source files.**

**Symlinks:** These files are automatically accessible to JavaScript via symlinks in `assets/data/`:
- `assets/data/courses-database.json` → symlink to this file
- `assets/data/scheduled-courses.json` → symlink to this file

**Why:** PHP code reads from `data/`, JavaScript code reads from `assets/data/` (web-accessible). The symlinks ensure automatic synchronization - no manual sync required!

**When updating:** Update these source files in `/data/`. The symlinks in `/assets/data/` automatically reflect changes.

Refer to `docs/REORGANIZATION_PLAN.md` for details on the symlink implementation.
