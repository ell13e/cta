# Assets Data Directory

**Purpose:** JavaScript-accessible data files (client-side, web-accessible)

## Files

- `courses-database.json` - Course inventory (symlink to `data/courses-database.json`)
- `scheduled-courses.json` - Course schedule (symlink to `data/scheduled-courses.json`)

## ⚠️ Important: Symlinks

**These files are symlinks to the source files in `/data/`:**

- `courses-database.json` → `../../data/courses-database.json`
- `scheduled-courses.json` → `../../data/scheduled-courses.json`

**Why:** PHP code reads from `data/`, JavaScript code reads from `assets/data/` (web-accessible). Symlinks ensure a single source of truth with automatic synchronization.

**When updating:** Only update the source files in `/data/`. The symlinks automatically reflect changes - no manual sync required!

## Note

The `site-config.js` file has been moved to `assets/js/config/site-config.js` for better organization.
