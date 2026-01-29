# Outstanding Plans & Future Work

**Last Updated:** 2025-01-29  
**Status:** All critical work complete ✅

---

## 🎉 What's Done

- ✅ **All critical refactoring** (Phases 1-3 complete)
- ✅ **All SEO technical infrastructure** (meta tags, sitemap, schema, robots.txt)
- ✅ **All file links verified** (no broken links)
- ✅ **Documentation consolidated** (single `docs/` folder)
- ✅ **Folder structure optimized** (site-config.js moved, READMEs added)

---

## 📋 Outstanding Plans

### 1. Manual Content Work (Not Code)

**Priority:** Medium | **Type:** Content creation

**7 Course Pages Need Manual Content:**
- Safeguarding L2
- Medication Competency L3
- Emergency First Aid at Work
- Adult Social Care Certificate L2
- Emergency Paediatric First Aid
- Medication Awareness L2
- Moving & Positioning with Hoist L3

**Note:** Technical infrastructure is complete - meta descriptions, titles, and schema will auto-generate. This is just content writing.

**Detailed Implementation Guide:** See `/Users/elliesmith/Documents/7_course_pages_optimization.md` for:
- Complete content templates (800-1000+ words per course)
- FAQ sections (6-8 questions each)
- 4-week implementation timeline
- Revenue impact estimates (£8,500-12,750/month Month 1, growing to £18,000-28,000/month by Month 3)
- Internal linking strategy
- Technical fixes (duplicate URLs, image optimization)

**Reference:** `docs/DEVELOPER_GUIDE.md:498-510`

---

### 2. ✅ JSON File Symlinks - COMPLETE

**Priority:** Low | **Type:** Infrastructure improvement | **Status:** ✅ Implemented

**Implementation:**
- ✅ Created symlinks in `assets/data/` pointing to `data/` files
- ✅ Eliminated duplication
- ✅ Single source of truth established

**Result:**
- `assets/data/courses-database.json` → symlink to `data/courses-database.json`
- `assets/data/scheduled-courses.json` → symlink to `data/scheduled-courses.json`
- No more manual sync required
- Changes to source files automatically reflected

**Reference:** `docs/REORGANIZATION_PLAN.md:110-140`

---

### 3. On-Demand Refactoring (Only When Needed)

**Priority:** On-demand | **Type:** Code improvement

**Phase 4: Newsletter System** (8,171 lines)
- **Strategy:** Extract when you touch it
- Don't refactor all at once
- Extract slices as you need to change them

**Phase 5: Email Service**
- Only if email deliverability issues arise
- Only if you need to swap mail providers
- Skip if email works fine (it does)

**Phase 6: Testing & Quality**
- Write tests for new code as you build it
- Run PHPStan when touching code
- Fix errors in code you're actively changing

**Phase 7: Frontend Build Pipeline**
- Vite already configured
- Use when building new JS features
- Skip migrating existing JS (it works)

**Phase 8: Cleanup & Documentation**
- Delete truly dead code (verify unused first)
- Archive `inc.backup/` directory
- ~1 day when convenient

**Reference:** `docs/REFACTORING-ROADMAP.md:110-240`

---

## 📊 Summary

| Category | Status | Priority |
|----------|--------|----------|
| Critical Refactoring | ✅ Complete | - |
| SEO Infrastructure | ✅ Complete | - |
| Link Verification | ✅ Complete | - |
| Documentation | ✅ Complete | - |
| JSON Symlinks | ✅ Complete | - |
| Manual Content Work | ⏳ Pending | Medium |

---

## 💡 Philosophy

**From REFACTORING-ROADMAP.md:**
> "This roadmap prioritizes **pragmatic improvements** over perfect architecture. Focus on:
> - **Critical paths** (forms, bookings, revenue-generating features)
> - **Code that changes frequently** (extract when you touch it)
> - **Real problems** (not theoretical ones)
>
> Skip the ceremony. Build what matters."

**Translation:** You're done with the critical stuff. Everything else is optional and should only be done when you actually need it.

---

**Status:** 🎉 **All critical work complete!** Everything else is optional/on-demand.
