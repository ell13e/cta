# Eventbrite Fields AI Context Assessment

**Date:** January 2026  
**Purpose:** Assess the AI context provided for Eventbrite field generation  
**Updated:** Integration of 2026 Eventbrite SEO Best Practices

---

## Executive Summary

The Eventbrite integration has three AI-generated fields with **inconsistent context gathering**. The description field has comprehensive context, while summary and custom name fields use minimal context. There's also a **centralized context function** (`cta_get_ai_context_for_field`) that exists but is **not used** by Eventbrite functions, leading to code duplication.

**New Insight (2026 Best Practices):** Eventbrite SEO optimization requires specific strategies including title structure (Event Type + Descriptor + Location, under 75 chars), description length (150-200 words optimal), FAQ sections (+8% search visibility), and location-specific keyword integration. Current AI prompts should be enhanced to incorporate these documented best practices.

---

## Eventbrite Fields Overview

### 1. `eventbrite_description` (WYSIWYG/HTML)
- **Type:** HTML-formatted description
- **Character Limit:** None (targets 500-700 words)
- **Purpose:** Full event description for Eventbrite listing
- **AI Generation:** `cta_generate_eventbrite_description()`
- **Auto-generate:** Yes (on save if empty)

### 2. `eventbrite_summary` (Text)
- **Type:** Plain text
- **Character Limit:** 140 characters
- **Purpose:** Short summary for Eventbrite search/discovery
- **AI Generation:** `cta_generate_eventbrite_summary_ajax()`
- **Auto-generate:** Yes (on save if empty, or via button)

### 3. `eventbrite_custom_name` (Text)
- **Type:** Plain text
- **Character Limit:** 100 characters
- **Purpose:** Override auto-generated event name
- **AI Generation:** `cta_generate_eventbrite_custom_name_ajax()`
- **Auto-generate:** No (manual button only)

---

## Current AI Context Analysis

### ✅ `eventbrite_description` - **EXCELLENT CONTEXT**

**Location:** `wordpress-theme/inc/eventbrite-integration.php:850-1107`

**Context Gathered:**
- ✅ Course title
- ✅ Course description
- ✅ Learning outcomes (formatted as text list)
- ✅ Accreditation
- ✅ Suitable for
- ✅ Prerequisites
- ✅ Event date (with full formatting: "Wednesday, 15 January 2026")
- ✅ Day of week
- ✅ Month name
- ✅ Season (spring/summer/autumn/winter)
- ✅ Start time
- ✅ End time
- ✅ Time context (morning/afternoon/evening)
- ✅ Location
- ✅ Location keywords (Maidstone, Kent extraction)
- ✅ Duration
- ✅ Price
- ✅ Course keywords array

**Prompt Quality:**
- ✅ Comprehensive SEO-focused system prompt
- ✅ Detailed requirements (10 sections)
- ✅ Eventbrite SEO best practices
- ✅ Local SEO optimization
- ✅ E-E-A-T principles
- ✅ Seasonal/contextual relevance
- ✅ HTML structure requirements

**Assessment:** **Excellent** - This is the gold standard. Comprehensive context with rich prompt engineering.

---

### ⚠️ `eventbrite_summary` - **MINIMAL CONTEXT**

**Location:** `wordpress-theme/inc/eventbrite-integration.php:2365-2439`

**Context Gathered:**
- ✅ Course title
- ✅ Duration
- ✅ Event date (formatted as "j M Y")
- ✅ Location (with fallback)
- ✅ Accreditation (if not "none")

**Missing Context:**
- ❌ Learning outcomes
- ❌ Suitable for
- ❌ Prerequisites
- ❌ Course description
- ❌ Price
- ❌ Time context (morning/afternoon/evening)
- ❌ Season/day of week context
- ❌ Location keywords extraction

**Prompt Quality:**
- ✅ Clear system prompt
- ✅ Basic requirements
- ⚠️ No SEO optimization guidance
- ⚠️ No local SEO focus
- ⚠️ No seasonal/contextual relevance

**Assessment:** **Needs Improvement** - While 140 characters is limiting, the context could still include more relevant details (outcomes, suitable for, price) to generate better summaries.

---

### ⚠️ `eventbrite_custom_name` - **MINIMAL CONTEXT**

**Location:** `wordpress-theme/inc/eventbrite-integration.php:2444-2510`

**Context Gathered:**
- ✅ Course title
- ✅ Duration
- ✅ Event date (formatted as "j M Y")
- ✅ Location (with fallback)

**Missing Context:**
- ❌ Accreditation
- ❌ Learning outcomes (for keyword extraction)
- ❌ Course description
- ❌ Price
- ❌ Time context
- ❌ Location keywords extraction

**Prompt Quality:**
- ✅ SEO-focused system prompt
- ✅ Clear format requirements
- ⚠️ Limited SEO optimization guidance
- ⚠️ No local SEO depth

**Assessment:** **Needs Improvement** - For SEO-optimized names, more context (especially accreditation and key outcomes) would help generate better search-optimized titles.

---

## Code Architecture Issues

### 🔴 **Critical: Context Function Not Used**

**Issue:** There's a centralized context function `cta_get_ai_context_for_field()` in `wordpress-theme/inc/ai-course-assistant.php` that provides structured context for `course_event` post types, but **Eventbrite functions don't use it**.

**Current State:**
- `cta_get_ai_context_for_field()` exists and provides:
  - Event title, date, times, location, price
  - Linked course: title, level, duration, accreditation
- Eventbrite functions manually gather context, duplicating logic

**Impact:**
- Code duplication
- Inconsistent context gathering
- Harder to maintain
- Missing fields in some functions

**Recommendation:** Refactor Eventbrite functions to use `cta_get_ai_context_for_field()` as the base, then extend with Eventbrite-specific context (season, time context, location keywords, outcomes formatting).

---

## Context Comparison Matrix

| Context Field | Description | Summary | Custom Name | `cta_get_ai_context_for_field()` |
|---------------|-------------|---------|-------------|----------------------------------|
| Course title | ✅ | ✅ | ✅ | ✅ |
| Course description | ✅ | ❌ | ❌ | ❌ |
| Learning outcomes | ✅ | ❌ | ❌ | ❌ |
| Accreditation | ✅ | ✅ | ❌ | ✅ |
| Suitable for | ✅ | ❌ | ❌ | ❌ |
| Prerequisites | ✅ | ❌ | ❌ | ❌ |
| Event date | ✅ (full format) | ✅ (short) | ✅ (short) | ✅ |
| Day of week | ✅ | ❌ | ❌ | ❌ |
| Month name | ✅ | ❌ | ❌ | ❌ |
| Season | ✅ | ❌ | ❌ | ❌ |
| Start time | ✅ | ❌ | ❌ | ✅ |
| End time | ✅ | ❌ | ❌ | ✅ |
| Time context | ✅ | ❌ | ❌ | ❌ |
| Location | ✅ | ✅ | ✅ | ✅ |
| Location keywords | ✅ | ❌ | ❌ | ❌ |
| Duration | ✅ | ✅ | ✅ | ✅ |
| Price | ✅ | ❌ | ❌ | ✅ |
| Course level | ❌ | ❌ | ❌ | ✅ |

---

## Recommendations

### 1. **Refactor to Use Centralized Context Function** (High Priority)

**Action:** Update Eventbrite functions to use `cta_get_ai_context_for_field()` as the foundation.

**Benefits:**
- Eliminates code duplication
- Ensures consistent context across all AI functions
- Easier to maintain and extend

**Implementation:**
```php
// In eventbrite functions, start with:
$base_context = cta_get_ai_context_for_field('eventbrite_description', $post_id);

// Then extend with Eventbrite-specific context:
$extended_context = [
    'course_description' => get_field('course_description', $course->ID),
    'outcomes' => cta_get_outcomes($course->ID),
    'season' => cta_get_season_from_date($event_date),
    'time_context' => cta_get_time_context($start_time),
    'location_keywords' => cta_extract_location_keywords($location),
    // ... etc
];
```

---

### 2. **Enhance Summary Context** (Medium Priority)

**Action:** Add more relevant context to `eventbrite_summary` generation.

**Add:**
- Learning outcomes (first 2-3 key points)
- Accreditation (if relevant)
- Price (if relevant for search)
- Suitable for (for targeting)

**Rationale:** Even with 140-char limit, richer context helps AI generate more compelling, keyword-rich summaries.

---

### 3. **Enhance Custom Name Context** (Medium Priority)

**Action:** Add more context to `eventbrite_custom_name` generation.

**Add:**
- Accreditation (important for SEO)
- Key learning outcomes (for keyword extraction)
- Price (if relevant)

**Rationale:** SEO-optimized names benefit from more context, especially accreditation and key terms.

---

### 4. **Extend `cta_get_ai_context_for_field()`** (Medium Priority)

**Action:** Add missing fields to the centralized function.

**Add:**
- Course description
- Learning outcomes
- Suitable for
- Prerequisites
- Season/time context helpers

**Rationale:** Makes the centralized function more complete and useful for all AI generation needs.

---

### 5. **Create Eventbrite-Specific Context Helper** (Low Priority)

**Action:** Create `cta_get_eventbrite_ai_context($post_id)` that combines base context with Eventbrite-specific enhancements.

**Benefits:**
- Single source of truth for Eventbrite AI context
- Reusable across all Eventbrite field generators
- Easier to test and maintain

---

## Implementation Priority

1. **High:** Refactor to use centralized context function
2. **Medium:** Enhance summary and custom name context
3. **Medium:** Extend centralized context function
4. **Low:** Create Eventbrite-specific context helper

---

## Testing Recommendations

After refactoring, test that:
1. All three Eventbrite fields generate correctly
2. Context is consistent across all functions
3. No regressions in generated content quality
4. Character limits are respected
5. HTML formatting is correct for description field

---

## Files Involved

- `wordpress-theme/inc/eventbrite-integration.php` - Main Eventbrite functions
- `wordpress-theme/inc/ai-course-assistant.php` - Centralized context function
- `wordpress-theme/inc/acf-fields.php` - ACF field definitions

---

## Notes

- The description field's comprehensive context is excellent and should be the model for other fields
- The centralized context function exists but is underutilized
- Character limits (140, 100) are Eventbrite API constraints, not arbitrary
- All functions use the same AI provider fallback system (`cta_ai_try_providers`)

---

## 2026 Eventbrite SEO Best Practices Integration

### Alignment with Current Implementation

**✅ Well-Aligned:**
- Description field already targets 500-700 words (exceeds 150-200 word recommendation but includes comprehensive SEO guidance)
- Location keywords extraction is implemented
- Eventbrite-specific HTML structure requirements are in place
- SEO optimization guidance is comprehensive

**⚠️ Needs Enhancement:**

#### 1. Title Structure (eventbrite_custom_name)
**Current:** Generic "SEO-optimized event name" guidance  
**2026 Best Practice:** Structure should be `[Event Type] + [Unique Descriptor] + [Location (optional)]`, under 75 characters

**Recommendation:** Update prompt to specify:
- Event type first (e.g., "Training Course", "Workshop", "Conference")
- Unique descriptor second
- Location optional (only if space allows and location is key differentiator)
- Maximum 75 characters (not 100 as currently allowed)

#### 2. Description Length Optimization
**Current:** Targets 500-700 words  
**2026 Best Practice:** 150-200 words optimal (max 2,500 characters)

**Recommendation:** 
- Add guidance about keeping description concise (150-200 words for mobile)
- Maintain comprehensive version for longer-form needs but add "concise version" option
- Emphasize mobile optimization (short paragraphs, bullet points)

#### 3. Missing SEO Elements in Context
**2026 Best Practices Highlight:**
- FAQ section (+8% search visibility) - Not generated by AI
- Address Field 1 completion (+67% conversion) - Not part of AI context (but should be validated)
- Tag recommendations - Not generated
- Category/subcategory guidance - Not provided

**Recommendation:** Add to context gathering:
- Venue address completion status (for validation warnings)
- Suggested tags based on course content and location
- Category/subcategory recommendations

#### 4. Unique Content Requirement
**2026 Best Practice:** Eventbrite descriptions should NOT duplicate website content to avoid Google penalties

**Recommendation:** Add to system prompt:
- "Generate unique Eventbrite-specific content that differs from website course descriptions"
- "Optimize for Eventbrite's discovery algorithm and user base, not just website SEO"

#### 5. Mobile-First Optimization
**2026 Best Practice:** Most Eventbrite users browse on mobile; optimize for mobile reading

**Recommendation:** Enhance prompt with:
- Short sentences (under 15 words)
- Single-line sections
- Bullet points over paragraphs
- Critical info at top of description

### New AI Context Needs

Based on 2026 best practices, the following should be added to context:

1. **Suggested Tags Array**
   - Course-specific keywords
   - Location-specific tags (e.g., "Maidstone events", "Kent training")
   - Niche identifiers (e.g., "care sector", "CQC compliant")
   - Geographic qualifiers ("Southeast UK", "Kent-based")

2. **FAQ Suggestions**
   - Age restrictions/dress code
   - Parking information
   - What to bring
   - Accessibility information
   - Refund/cancellation policy

3. **Category/Subcategory Recommendations**
   - Based on course type and content
   - Most specific subcategory suggestion

4. **Venue Address Validation**
   - Check if full address is provided (not just city)
   - Flag incomplete addresses for conversion impact

### Updated Prompt Recommendations

#### Eventbrite Description Prompt Enhancement
Add to existing prompt:
```
MOBILE OPTIMIZATION (Critical - Most users browse on mobile):
- Keep paragraphs to 2-4 sentences maximum
- Use short sentences (under 15 words)
- Prioritize critical information at the top
- Use bullet points for easy scanning
- Test readability on mobile devices

UNIQUE CONTENT REQUIREMENT:
- Generate Eventbrite-specific content that differs from any website course descriptions
- Optimize for Eventbrite's discovery algorithm and Eventbrite user base
- Do not duplicate content from other sources (Google penalizes duplicate content)

OPTIMAL LENGTH:
- Target 150-200 words for optimal mobile engagement and search ranking
- Maximum 2,500 characters (current limit allows longer, but shorter is better for mobile)
```

#### Eventbrite Custom Name Prompt Enhancement
Replace current format guidance with:
```
TITLE STRUCTURE (Follow Eventbrite 2026 Best Practices):
- Format: [Event Type] + [Unique Descriptor] + [Location if space allows]
- Maximum 75 characters (optimal for Eventbrite search)
- Use title case (no all-caps, appears spammy)
- Include specific event type keywords (training, workshop, course, conference)
- Location optional but recommended if space allows

EXAMPLES:
- "Moving & Handling Training - Maidstone, Kent"
- "CQC Safeguarding Workshop for Care Staff"
- "Dementia Care Training Course - Professional Development"

AVOID:
- Vague descriptors ("Fun Night Out", "Amazing Event")
- All-caps or excessive punctuation
- Generic claims ("Best", "Can't Miss")
- Unnecessary dates (already captured in date field)
```

#### Eventbrite Summary Prompt Enhancement
Add to existing prompt:
```
TAG SUGGESTIONS (Not part of summary but for reference):
Generate 5-7 tag suggestions based on this event that would improve Eventbrite search visibility:
- Course-specific keywords
- Location-specific tags (e.g., "Maidstone training", "Kent care courses")
- Niche identifiers (e.g., "CQC compliant", "care sector")
- Geographic qualifiers

FAQ SUGGESTIONS (Generates +8% search visibility):
Suggest 3-5 FAQ questions that would improve search ranking and user experience:
- Common logistics questions (parking, accessibility, what to bring)
- Course-specific questions (prerequisites, certification details)
- Booking questions (refunds, transfers, group bookings)
```

---

## Implementation Priority (Updated)

1. **High:** Refactor to use centralized context function
2. **High:** Update title prompt to 75-char limit and proper structure
3. **High:** Add unique content warning to description prompt
4. **Medium:** Add mobile optimization guidance to description prompt
5. **Medium:** Add FAQ and tag suggestion generation (new features)
6. **Medium:** Enhance summary and custom name context
7. **Medium:** Extend centralized context function
8. **Low:** Create Eventbrite-specific context helper
9. **Low:** Add venue address validation context

---

**Assessment Complete** ✅  
**Next Steps:** Implement 2026 SEO best practices alignment
