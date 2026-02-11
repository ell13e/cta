# Refactoring Roadmap

## Philosophy

This roadmap prioritizes **pragmatic improvements** over perfect architecture. Focus on:
- **Critical paths** (forms, bookings, revenue-generating features)
- **Code that changes frequently** (extract when you touch it)
- **Real problems** (not theoretical ones)

Skip the ceremony. Build what matters.

---

## Current Status: Phase 1-3 Complete ✅

### Phase 1 Completed
- [x] Created `composer.json` with PSR-4 autoloading
- [x] Created `package.json` with Vite build tooling
- [x] Set up PHPUnit testing framework
- [x] Created directory structure (`src/`, `tests/`)
- [x] Built `FormValidator` service
- [x] Extracted `ContactFormController` from monolithic handlers
- [x] Updated `functions.php` to load Composer autoloader
- [x] Maintained backward compatibility (all AJAX actions still work)
- [x] Wrote unit tests for `FormValidator`
- [x] Documented architecture decisions

### Phase 2 Completed
- [x] Extracted `GroupBookingController` with validation, discount codes, and email handling
- [x] Extracted `CourseBookingController` with course/event integration and Eventbrite sync
- [x] Extracted `NewsletterSignupController` for footer newsletter subscriptions
- [x] Updated legacy handlers to act as facades (backward compatible)
- [x] Wrote unit tests for all controllers
- [x] All revenue-generating forms now use controller classes

### Phase 3 Completed
- [x] Created `FormSubmissionRepository` with `create()`, `findById()`, and `findByEmail()` methods
- [x] Updated all controllers (`ContactFormController`, `GroupBookingController`, `CourseBookingController`, `NewsletterSignupController`) to use repository
- [x] Updated legacy `cta_save_form_submission()` function to delegate to repository
- [x] Wrote integration tests for repository
- [x] Centralized all form submission database logic

---

## Phase 2: Extract Critical Form Controllers ✅

**Priority: High** | **Status: Complete**

Focus on the forms that matter most: revenue-generating and high-traffic.

### Completed
- [x] Extracted `GroupBookingController`
  - Complex validation + discount codes
  - Revenue-generating feature
  - Reuses `FormValidator` service
  - Maintains `wp_ajax_cta_group_booking` hook
  
- [x] Extracted `CourseBookingController`
  - Course event integration
  - Revenue-generating feature
  - Eventbrite sync hooks
  - Maintains `wp_ajax_cta_course_booking` hook

- [x] Extracted `NewsletterSignupController`
  - Footer newsletter subscriptions
  - Optional fields (phone, date of birth)
  - Links submissions to subscriber records

- [x] Wrote unit tests for all controllers

### Remaining (Optional)
- [ ] Extract `CallbackRequestController` (when you need to change callback logic)

**Success Criteria:**
- ✅ Revenue-generating forms use controller classes
- ✅ Legacy `inc/ajax-handlers.php` contains compatibility wrappers
- ✅ Zero frontend changes required
- ✅ Tests cover validation logic

---

## Phase 3: Essential Repositories ✅

**Priority: Medium** | **Status: Complete**

Only extract what you actually need. Don't build a full DDD layer.

### Completed
- [x] Created `FormSubmissionRepository`
  - Extracted `cta_save_form_submission()` logic
  - Methods: `create()`, `findById()`, `findByEmail()`
  - Updated all controllers to use repository via dependency injection
  - Centralized database logic for all form submissions

**Benefits Achieved:**
- ✅ Centralized database logic for form submissions
- ✅ Easier to test (mock repositories)
- ✅ Consistent error handling
- ✅ All controllers use repository pattern

### Remaining (Optional)
- [ ] Create minimal `NewsletterRepository` (only if actively changing newsletter features)
  - Methods: `addSubscriber()`, `findByEmail()`
  - Don't extract everything - just what you need

**Skip:** Full repository layer for newsletter system unless you're actively building new features.

---

## Phase 4: Newsletter System Refactor

**Priority: Optional** | **Estimated: On-Demand**

`inc/newsletter-subscribers.php` is 8,171 lines. **Don't refactor it all at once.**

### Strategy: Extract When You Touch It

Each time you need to change newsletter code:
1. Extract just that slice into a service/repository
2. Add tests around the extracted code
3. Leave the rest alone

### When to Extract
- [ ] Need to change tag management? → Extract `TagManager` service
- [ ] Need to change campaign logic? → Extract `CampaignService`
- [ ] Need to change email queue? → Extract `EmailQueueService`
- [ ] Need to change admin UI? → Extract that view file

**Don't:** Try to refactor all 8K lines in one go. That's how bugs happen.

**Success Criteria:**
- Code you touch is extracted and tested
- Rest of file remains functional
- Over time, the big file becomes a thin shell

---

## Phase 5: Email Service

**Priority: Optional** | **Estimated: On-Demand**

Only introduce this when you actually need it.

### When to Build
- [ ] Fighting email deliverability issues
- [ ] Need consistent email logging across all forms
- [ ] Want to swap mail provider (SMTP, SendGrid, etc.)
- [ ] Building new email features

### What to Build (When Needed)
- [ ] Create `EmailService` class
- [ ] Centralize `wp_mail()` calls
- [ ] Add email logging
- [ ] Template system (if you need it)

**Skip:** Building this "just in case". Email works fine right now.

---

## Phase 6: Testing & Quality

**Priority: Ongoing** | **Estimated: As You Go**

Realistic testing strategy: test what you change.

### Must Do
- [ ] Write tests for new controllers/services as you build them
- [ ] Run PHPStan when you touch code (don't need level 6+ everywhere)
- [ ] Fix PHPStan errors in code you're actively changing

### Nice to Have
- [ ] Add type hints to new code (not retrofitting everything)
- [ ] Configure PHP_CodeSniffer for new code
- [ ] Integration tests for critical paths (forms, bookings)

**Skip:**
- 80%+ coverage requirement (unrealistic for a working site)
- Full WordPress test environment setup (unless you're building complex features)
- Pre-commit hooks (nice but not essential)

**Philosophy:** Test what matters. Don't test for the sake of testing.

---

## Phase 7: Frontend Build Pipeline

**Priority: Optional** | **Estimated: On-Demand**

Vite is already configured. Use it when you need it.

### When to Use
- [ ] Building new JavaScript features
- [ ] Need code splitting for new pages
- [ ] Want TypeScript for new code
- [ ] Need hot module replacement for development

### What to Do (When Needed)
- [ ] Create `assets/js/src/` directory
- [ ] Put new JS in `src/`, configure Vite to compile
- [ ] Leave existing JS files alone (they work)

**Skip:** Migrating all existing JS to Vite. That's busywork.

---

## Phase 8: Cleanup & Documentation

**Priority: Low** | **Estimated: 1 day**

Do the essentials. Skip the ceremony.

### Must Do
- [ ] Delete truly dead code from `inc/` (verify it's unused first)
- [ ] Archive `inc.backup/` directory (move to `.gitignore` or delete)
- [ ] Create basic developer onboarding doc (one page: "How forms work", "How to run tests")

### Nice to Have
- [ ] Update inline documentation as you touch code
- [ ] Document database schema (if you're actively changing it)

**Skip:**
- phpDocumentor API docs (overkill for a single-site theme)
- Video walkthrough (documentation goes stale)
- Comprehensive documentation for code you're not touching

---

## Revised Timeline

| Phase | Duration | Status |
|-------|----------|--------|
| Phase 1 | ✅ Complete | Done |
| Phase 2 | ✅ Complete | Done |
| Phase 3 | ✅ Complete | Done |
| Phase 4 | On-demand | Only when touching newsletter code |
| Phase 5 | On-demand | Only when email issues arise |
| Phase 6 | Ongoing | As you build new code |
| Phase 7 | On-demand | Only when building new JS features |
| Phase 8 | 1 day | When you have time |

**Completed:** Core refactoring (Phases 1-3) completed. All revenue-generating forms now use controller classes with repository pattern.

---

## Risk Mitigation

### Frontend Breakage
**Risk:** Low (backward compatibility maintained)  
**Mitigation:**
- All AJAX action names unchanged
- Legacy functions remain as wrappers
- Test frontend forms after changes

### Database Issues
**Risk:** Low (only extracting what you need)  
**Mitigation:**
- Extract incrementally, not all at once
- Test each extraction before moving on
- Backup database before major changes

### Email Delivery
**Risk:** Low (only touching email when you need to)  
**Mitigation:**
- Don't refactor email unless you have a reason
- Keep existing email logic until you need to change it

---

## Success Metrics

- **Code Quality:** New code has tests and type hints
- **File Sizes:** New files stay under 500 lines (don't worry about legacy files)
- **Performance:** No increase in page load time (already optimized)
- **Reliability:** Zero increase in error rates
- **Maintainability:** New features are easier to build

---

## Next Steps

1. ✅ **Phase 2 Complete:** All critical form controllers extracted
2. ✅ **Phase 3 Complete:** FormSubmissionRepository created and integrated
3. **Next:** Only refactor other code when you actually need to change it
   - Phase 4: Newsletter system (extract incrementally when touching it)
   - Phase 5: Email service (only if email issues arise)
   - Phase 8: Cleanup dead code when convenient

**Status:** Core refactoring complete. All form handlers now follow consistent controller/repository pattern.

---

## Notes

- This roadmap is **pragmatic, not perfect**
- Focus on **what matters** (revenue, critical paths)
- Extract code **when you touch it**, not "just because"
- Don't refactor working code unless you have a reason
- Test new code, not everything

The goal is a maintainable codebase, not a perfect architecture.
