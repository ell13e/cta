# CCS Theme Implementation Plan

Transform the CTA (training academy) WordPress theme into the CCS (domiciliary care) site. Use the three companion docs as source of truth; this file is the execution plan.

---

## 1. Reference Documents (use in this order)

| Order | Document | Use when |
|-------|----------|----------|
| 1 | **ccs-cursor-prompt.md** | Verified business info, content architecture, technical requirements, DO NOT list |
| 2 | **ccs-visual-ux-specs.md** | Design, UX, copy tone, market gaps, CQC integration, launch checklist |
| 3 | **ccs-technical-roadmap.md** | Step-by-step code: find/replace, post types, forms, templates, testing |

**Rule**: If it’s not in these docs, don’t invent it. Check the docs first.

---

## 2. Source of Truth (verified data only)

### Contact
- **Phone**: 01622 809881  
- **Address**: The Maidstone Studios, New Cut Road, Vinters Park, Maidstone, Kent, ME14 5NZ  
- **Office**: office@continuitycareservices.co.uk (care, commissioning, general)  
- **Recruitment**: recruitment@continuitycareservices.co.uk (careers only)  
- **CQC Location ID**: 1-2624556588 | **CQC Rating**: GOOD (06/12/2023)  
- **Registered Manager**: Mrs Victoria Louise Walker  
- **LinkedIn**: https://uk.linkedin.com/company/continuitycareservices  
- **Live site (extract branding)**: https://www.continuitycareservices.co.uk  

### Brand (preserve)
- **Tagline**: "Your Team, Your Time, Your Life"  
- **Statement**: "It's not just what we do, it's how we do it."  
- **Core**: "We don't rush or rotate staff. We take time to get to know each person, not just their care plan."  

### Services (CQC-verified, all 12)
Personal care · Complex care (PEG, epilepsy, tracheostomy, ventilation, neurological) · Domiciliary support · Respite · Palliative/end-of-life · Dementia · Learning disabilities · Physical disabilities · Mental health support · Children's services · Sensory impairments · Companionship care  

---

## 3. Pre-flight / Blockers (resolve before or during build)

- [ ] **Brand colours** – Extract from live site (primary, secondary, accent hex)  
- [ ] **Logo** – SVG/PNG for CCS logo  
- [ ] **Photos** – Real library (team, care, office) or confirm use of placeholders  
- [ ] **Social** – Confirm Facebook/Instagram (LinkedIn only confirmed so far)  
- [ ] **CQC PDF** – Official report URL for download link  
- [ ] **Homecare.co.uk** – Profile link (4.9/5 rating)  
- [ ] **Google Business** – Confirm profile exists  

---

## 4. Implementation Plan

### Phase 1 – COMPLETE  
Theme metadata, constants (`CCS_EMAIL_OFFICE`, `CCS_EMAIL_RECRUITMENT`, CQC, phone, address), post type `care_service` + taxonomies (`/services/`), form system (care assessment, commissioning, careers, callback → office@/recruitment@), homepage, services archive + single templates, and CTA→CCS rebrand (Continuity of Care Services) are done.

---

### Phase 2 – HIGH (2–3 days)  
*Goal: All main pages live, professional look.*

| # | Task | Done |
|---|------|------|
| 2.1 | Email system + templates (office@ / recruitment@, form type in subject) | [ ] |
| 2.2 | Contact page (phone, office@, map, hours) | [ ] |
| 2.3 | About page (team, philosophy, “we don’t rotate staff”) | [ ] |
| 2.4 | Careers page (recruitment@, CTA training link, application form) | [ ] |
| 2.5 | Commissioning / “For Professionals” page (office@, LA/NHS focus) | [ ] |
| 2.6 | CSS refinements (tokens, hierarchy from ccs-visual-ux-specs.md) | [ ] |
| 2.7 | Mobile responsiveness | [ ] |
| 2.8 | Navigation (no overloaded dropdowns) | [ ] |

**Checkpoint**: All key pages exist, forms work, layout works on mobile.

---

### Phase 3 – MEDIUM (2–3 days)  
*Goal: Production-ready.*

| # | Task | Done |
|---|------|------|
| 3.1 | Form validation (required fields, email, file types for CV) | [ ] |
| 3.2 | Service filters (type, condition, area) | [ ] |
| 3.3 | Blog/resources section (if in scope) | [ ] |
| 3.4 | CQC integration (badge, report link, rating visible) | [ ] |
| 3.5 | Performance (images, Lighthouse) | [ ] |

**Checkpoint**: Safe to launch from a quality and performance standpoint.

---

### Phase 4 – NICE-TO-HAVE (as time allows)

| # | Task | Done |
|---|------|------|
| 4.1 | Subtle animations | [ ] |
| 4.2 | Advanced SEO (schema, meta) | [ ] |
| 4.3 | Analytics + Search Console | [ ] |
| 4.4 | Video embeds | [ ] |
| 4.5 | Testimonial carousel (paraplegic father story prominent) | [ ] |

---

## 5. Rules

**Do**
- Use only verified data from the three docs.  
- Pull real branding from continuitycareservices.co.uk.  
- Keep existing CCS messaging and paraplegic father testimonial.  
- Add commissioning “For Professionals” section.  
- Link CTA training clearly.  
- Use real photos only; direct, authentic copy (“You want…” not “Families want…”).  

**Don’t**
- Invent contact details, emails, or testimonials.  
- Use stock elderly-care imagery.  
- Bury CQC rating.  
- Use jargon (“person-centred”, “holistic”).  
- Claim certs or services not verified.  
- Over-complicate navigation.  
- Skip mobile.  

---

## 6. Technical Gotchas

1. **Namespace** – Use `namespace CCS\` consistently; run `composer dump-autoload` after changes.  
2. **Email constants** – Define before any form/email code runs.  
3. **CQC ID** – Use 1-2624556588 only (this provider).  
4. **Coverage** – Maidstone primary; Medway and Kent-wide.  
5. **Services URL** – Slug `/services/` only.  
6. **Form routing** – Care/commissioning/general → office@; careers → recruitment@.  
7. **CQC rating** – GOOD in all categories (Dec 2023); show clearly.  

---

## 7. Differentiators to Highlight

| Advantage | Where |
|-----------|--------|
| Local Kent | “Maidstone-based, Kent-wide”, coverage map |
| Complex care | Condition detail, paraplegic testimonial, CTA training |
| Training academy | “Our carers trained at Continuity of Care Services” |
| No staff rotation | About / philosophy |
| CQC Good | Badge above the fold |
| Commissioning | Dedicated “For Professionals” section |
| 24/7 | Header / CTAs |
| Real testimonials | Paraplegic father story used strategically |

---

## 8. Lookup (where to find what)

| Need | Doc | Section |
|------|-----|---------|
| Business/contact | ccs-cursor-prompt.md | VERIFIED BUSINESS INFORMATION |
| Post types, forms | ccs-technical-roadmap.md | PHASE 2–3 |
| Brand/typography | ccs-visual-ux-specs.md | VISUAL & UX SPECIFICATIONS |
| Service pages | ccs-cursor-prompt.md | CONTENT ARCHITECTURE |
| Email templates | ccs-technical-roadmap.md | PHASE 5 |
| Copy tone | ccs-visual-ux-specs.md | COPYWRITING TONE & STYLE GUIDE |
| CQC | ccs-visual-ux-specs.md | CQC INTEGRATION SPECIFICS |
| Homepage/templates | ccs-technical-roadmap.md | PHASE 5: TEMPLATE FILES |
| Market gaps | ccs-visual-ux-specs.md | MARKET GAPS TO EXPLOIT |
| Testing | ccs-technical-roadmap.md | PHASE 7: TESTING & DEPLOYMENT |

---

## 9. When in doubt

1. **Verified or not?** → Check the three docs.  
2. **Brand voice?** → ccs-visual-ux-specs.md.  
3. **Technically correct?** → ccs-technical-roadmap.md.  
4. **Competitive angle?** → ccs-visual-ux-specs.md MARKET GAPS.  

**Bottom line**: Transform the theme using only verified info and the three documents. Don’t improvise.
