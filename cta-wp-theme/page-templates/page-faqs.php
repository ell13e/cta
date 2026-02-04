<?php
/**
 * Template Name: FAQs
 *
 * @package CTA_Theme
 */

get_header();

$contact = cta_get_contact_info();

/**
 * Format FAQ answer text, converting semicolon-separated lists to HTML lists
 * 
 * @param string $text The FAQ answer text
 * @return string Formatted HTML
 */
function cta_format_faq_answer($text) {
    if (empty($text)) {
        return '';
    }
    
    // Look for patterns like: "intro text: Item : Description; Item : Description; closing text"
    // Improved pattern to catch more variations
    // Match: "Title : Description;" where Title starts with capital letter
    $pattern = '/([A-Z][A-Za-z\s&\-\'()]{3,}?)\s*:\s*([^;]{10,}?)(?:;\s*(?=[A-Z])|;\s*$|$)/';
    
    $matches = [];
    if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        // Need at least 2 consecutive items to format as list
        if (count($matches) >= 2) {
            $first_pos = $matches[0][0][1];
            $last_end = $matches[count($matches) - 1][0][1] + strlen($matches[count($matches) - 1][0][0]);
            $list_span = $last_end - $first_pos;
            $text_length = strlen($text);
            
            // If list spans at least 20% of text, format as list
            if ($list_span >= $text_length * 0.2) {
                $before_text = trim(substr($text, 0, $first_pos));
                $after_text = trim(substr($text, $last_end));
                
                $list_items = [];
                foreach ($matches as $match) {
                    $title = trim($match[1][0]);
                    $description = trim($match[2][0]);
                    $description = rtrim($description, '; ');
                    
                    // Validate item - more lenient
                    if (!empty($title) && !empty($description) && 
                        strlen($title) >= 3 && strlen($description) >= 10) {
                        $list_items[] = ['title' => $title, 'description' => $description];
                    }
                }
                
                // Format as list if we have 2+ valid items
                if (count($list_items) >= 2) {
                    $output = '';
                    
                    if (!empty($before_text)) {
                        // Remove trailing colon/space
                        $before_text = preg_replace('/:\s*$/', '', trim($before_text));
                        $output .= '<p class="faq-intro">' . wp_kses_post($before_text) . '</p>';
                    }
                    
                    $output .= '<ul class="faq-answer-list">';
                    foreach ($list_items as $item) {
                        $output .= '<li><strong class="faq-list-title">' . esc_html($item['title']) . ':</strong> <span class="faq-list-description">' . esc_html($item['description']) . '</span></li>';
                    }
                    $output .= '</ul>';
                    
                    if (!empty($after_text)) {
                        $output .= '<p class="faq-outro">' . wp_kses_post($after_text) . '</p>';
                    }
                    
                    return $output;
                }
            }
        }
    }
    
    // Default: standard formatting with improved paragraph spacing
    $formatted = wpautop(wp_kses_post($text));
    
    // Add classes to paragraphs for better styling
    $formatted = preg_replace('/<p>/', '<p class="faq-paragraph">', $formatted);
    
    return $formatted;
}

// ACF fields
$hero_title = function_exists('get_field') ? get_field('hero_title') : '';
$hero_subtitle = function_exists('get_field') ? get_field('hero_subtitle') : '';

// Defaults
if (empty($hero_title)) {
    $hero_title = 'Frequently Asked Questions';
}
if (empty($hero_subtitle)) {
    $hero_subtitle = 'Quick answers to common questions about our training courses, booking, and certification.';
}

// Get FAQs from custom post type
$faqs = [];
$faq_posts = get_posts([
    'post_type' => 'faq',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'menu_order',
    'order' => 'ASC',
]);

foreach ($faq_posts as $faq_post) {
    $category_terms = get_the_terms($faq_post->ID, 'faq_category');
    $category = 'general'; // Default category
    if ($category_terms && !is_wp_error($category_terms)) {
        $category_term = reset($category_terms);
        $category_slug = $category_term->slug;
        // Map taxonomy slugs to template category slugs (handle colons)
        $template_slug = str_replace('-', ':', $category_slug);
        if ($template_slug === 'course-specific') {
            $template_slug = 'course:specific';
        } elseif ($template_slug === 'group-training') {
            $template_slug = 'group:training';
        }
        $category = $template_slug;
    }
    
    $answer = get_field('faq_answer', $faq_post->ID);
    if (empty($answer)) {
        $answer = $faq_post->post_content; // Fallback to post content
    }
    
    $faqs[] = [
        'category' => $category,
        'question' => $faq_post->post_title,
        'answer' => $answer,
    ];
}

// Fallback to ACF repeater if no FAQ posts exist
if (empty($faqs)) {
    $faqs = function_exists('get_field') ? get_field('faqs') : [];
}

// Final fallback to hardcoded FAQs if still empty
if (empty($faqs)) {
    $faqs = [
        // General Training Questions (8 FAQs)
        ['category' => 'general', 'question' => 'What training is mandatory for care workers?', 'answer' => 'Mandatory training is the essential foundation every care worker needs. At CTA, we ensure care workers complete: Health and Safety Awareness : Understanding workplace hazards and your responsibilities; Infection Prevention and Control : Current practices for safe, hygienic care; Fire Safety : Evacuating safely and protecting people in your care; Moving and Handling : Safe techniques to protect yourself and those you support; Safeguarding Adults : Recognising and reporting abuse or neglect; Learning Disability and Autism Awareness (Oliver McGowan training) : Required by law since July 2022; Safeguarding Children : Essential for roles involving contact with under:18s. For roles involving medication, Medication Competency is non:negotiable. All of these are CQC expectations, and gaps in mandatory training are red flags during inspection. CTA Reality Check: Just because you\'ve done the online module doesn\'t mean you\'re competent. Our hands:on approach ensures your team can actually do these things under pressure:not just pass a quiz.'],
        ['category' => 'general', 'question' => 'How often does training need to be renewed?', 'answer' => 'The short answer: Every three years minimum for most mandatory training, unless the content is included in a recognised adult social care qualification within that three:year window. However, some topics need refreshing sooner if: new risks are introduced (new equipment, new care tasks); legislation changes (happens regularly in care); an incident or near:miss highlights gaps; individual competency assessments show decline. Medication training is more frequent:usually annual refreshers are expected because this is high:risk, high:compliance work. CTA Advice: Don\'t wait until certificates expire. Build a renewal calendar now and book courses 2:3 months in advance:our courses fill up fast, and compliance deadlines sneak up.'],
        ['category' => 'general', 'question' => 'What\'s the difference between CPD and accredited training?', 'answer' => 'CPD (Continuing Professional Development) is ongoing learning:it can be formal or informal, and it\'s flexible. Reading a safeguarding update article, shadowing a colleague on medication rounds, attending a webinar:that\'s all CPD. Accredited training is formal, assessed learning leading to a recognised qualification or certificate. It\'s governed by awarding bodies like TQUK, NCFE CACHE, or HSE (for First Aid). Accredited courses have standards, quality checks, and carry more weight. Why it matters for you: CQC expects some accredited training, but they also recognise CPD. A good training strategy mixes both:accredited courses for core mandatory training, CPD for specialisms and ongoing development. CTA Mix: We deliver HSE:approved First Aid (accredited), CQC:compliant courses (accredited), and we support your CPD through coaching, on:site training tailored to your policies, and scenario:based learning.'],
        ['category' => 'general', 'question' => 'Do online courses meet CQC requirements?', 'answer' => 'Short answer: It depends, but CQC is increasingly sceptical about online:only training. The CQC Inspection Framework values competence. For practical, high:stakes care skills (First Aid, medication administration, moving and handling), online modules don\'t build muscle memory or real:world confidence. Staff can\'t practice CPR on a dummy via Zoom. For theory:heavy topics (e.g., GDPR, understanding dementia), online can work as part of a blended approach. But even then, interaction and assessment matter. CQC Reality: Inspectors ask, "Can your staff actually do this if a crisis happened?" Online certificates often can\'t answer that confidently. CTA Stance: We\'re in:person, hands:on, and practical. No "click and certificate" shortcuts. Real people, real scenarios, real confidence.'],
        ['category' => 'general', 'question' => 'What is the Care Certificate and who needs it?', 'answer' => 'The Care Certificate is a nationally recognised qualification covering 15 standards for adult social care workers. It\'s not legally mandatory, but it\'s a gold standard:hugely respected by employers, CQC, and Skills for Care. Who benefits most: New care workers entering the industry; Care assistants progressing toward team leader roles; Anyone without formal social care qualifications; Roles involving domiciliary or residential care. What it covers: Communication, person:centred care, duty of care, safeguarding, equality, health and safety, infection control, medication awareness, mental health, dementia, nutrition, hydration, privacy, and dignity. Reality: The Care Certificate isn\'t mandatory, but if a CQC inspector sees staff without it and without equivalent qualifications, they ask why. It\'s the industry\'s signal of competence. CTA Offer: We deliver Care Certificate:aligned training and can support your team\'s progression pathway.'],
        ['category' => 'general', 'question' => 'How long are training certificates valid?', 'answer' => 'Most certificates are valid for three years, after which refresher training is required. This applies to: Safeguarding (all levels); Health and Safety; Fire Safety; Infection Control; Learning Disability and Autism Awareness. First Aid certificates (EFAW/Paediatric): Valid for three years. After that, you need a refresher course (not a full retraining). Medication and moving/handling: Often require annual refreshers or competency reassessment depending on your policy and the risks in your setting. Specialist certificates (e.g., dementia, end:of:life care) vary by awarding body:typically 3:5 years. CQC Inspection Tip: Inspectors will check your training evidence. Expired certificates are a compliance gap. Use our Training Renewal Tracker template to stay ahead.'],
        ['category' => 'general', 'question' => 'What happens if training expires?', 'answer' => 'Short answer: Your staff are no longer deemed competent for those duties, and you\'re in breach of CQC regulation. Practically, this means: That team member can\'t be rostered for duties requiring that training; You have a compliance gap in your inspection file; If an incident occurs and training is expired, liability falls on the organisation; Insurance may not cover incidents involving untrained staff. It\'s not a small issue. CQC explicitly checks training records for expiry dates. If you have expired Fire Safety training but an evacuation was needed, that\'s a serious finding. CTA Prevention: We help you build a training calendar and send renewal reminders. Spaces fill up:booking 2:3 months early keeps compliance on track.'],
        ['category' => 'general', 'question' => 'Can training be completed during probation?', 'answer' => 'Yes:and it should be. In fact, mandatory training is part of a proper induction. Best practice: Probation periods should include: Weeks 1:2: Emergency First Aid, Fire Safety, Health & Safety basics, safeguarding intro; Weeks 2:4: Moving and handling, infection control, role:specific training; Weeks 4:8: Deeper competency building, shadow rounds, assessments. CQC Expectation: Providers should evidence that staff are trained before they work unsupervised. Waiting until probation ends to train them is risky. CTA Approach: We offer fast:track, intensive courses that fit probation timelines. New starters can complete core training within their first two weeks, building confidence and competence from day one.'],
        
        // Booking & Scheduling (8 FAQs)
        ['category' => 'booking', 'question' => 'How do I book training for my team?', 'answer' => 'Three easy ways to book with CTA: 1. Eventbrite (Individual or small groups) : Visit our Eventbrite page, browse upcoming courses, select date, number of delegates, and book online. Payment and confirmation immediate. Perfect for quick bookings. 2. Direct Phone : Call 01622 587 343. Speak to our team about your specific needs. Discuss group discounts, dates, on:site options. Fast:track booking for employers. 3. Bespoke Group Training (Best for care providers) : Email enquiries@continuitytrainingacademy.co.uk. Discuss your team\'s training plan for the year. We tailor dates, venues, and course content. Often the most cost:effective for larger teams. Pro tip: Larger bookings (8+ delegates) get group discounts and flexible scheduling.'],
        ['category' => 'booking', 'question' => 'What\'s your cancellation policy?', 'answer' => 'For individual bookings (Eventbrite): More than 14 days before the course: Full refund; 7:14 days before: 75% refund; Less than 7 days: No refund (we can sometimes offer place transfer). For group/bespoke training: Cancellations made 30+ days in advance: Full refund (minus admin); 14:30 days: 50% refund; Less than 14 days: No refund. Delegate swaps (much easier): Can\'t make the date? Swap your spot with another team member anytime, free of charge. Just let us know in advance. CTA Philosophy: We build relationships, not rigid policies. If something\'s genuinely difficult, talk to us:we\'ll usually find a solution.'],
        ['category' => 'booking', 'question' => 'Can you deliver training at our location?', 'answer' => 'Absolutely:this is one of our strengths. We deliver on:site training at: Care homes; Supported living services; Domiciliary care provider offices; Health services; Nurseries and childcare. Why on:site training works: No travel time for your team; Training tailored to your policies and environment; Scenarios using your equipment, your settings, your processes; More cost:effective for large teams (often cheaper than public courses); Flexible scheduling (evenings/weekends available). What we need: Appropriate room (tables, chairs, privacy); Access to your equipment (mannequins for First Aid, moving equipment for M&H, etc.); 2:3 weeks\' notice for booking. CTA Mobile Reach: We serve Maidstone, Kent, and the wider South East. No travel is too far:we come to you.'],
        ['category' => 'booking', 'question' => 'What are your group booking discounts?', 'answer' => 'The more you book, the more you save. We offer tiered discounts based on group size, with even better rates for annual contracts. If you commit to regular training (e.g., quarterly refreshers, new starter inductions), we offer bespoke packages with deeper discounts. Contact us to discuss your specific needs and we\'ll provide a tailored quote. CTA Reality: Bulk training is our sweet spot. You get better pricing, we build a long:term relationship, and your compliance is sorted.'],
        ['category' => 'booking', 'question' => 'How far in advance should I book?', 'answer' => 'Ideal timeline: 8:12 weeks before you need training. Here\'s why: 8:10 weeks: Guarantees your preferred date and trainer; 4:8 weeks: Still good availability, but less flexibility; 2:4 weeks: Possible, but dates may be limited; Less than 2 weeks: Only book if you\'re flexible on dates. Seasonal peaks: January, April, September, and November are busy (new year resolutions, inspection prep, team changes). Book early if you target these months. Emergency training: Sometimes you need urgent refreshers (inspection notice, staff absence, incident). Call us:we\'ll do our best to squeeze you in, but can\'t promise preferred dates. CTA Tip: Plan your year\'s training calendar now. Block out dates in January, April, September, and November. This keeps compliance on track and can offer better rates through advance planning:contact us to discuss.'],
        ['category' => 'booking', 'question' => 'Do you offer weekend or evening training?', 'answer' => 'Yes:we\'re flexible. Evening courses (after 16:30): Available by request for groups of 8+; Perfect for teams with shift patterns; Usually 1:2 nights depending on the course. Weekend courses: Saturday courses available (9 AM : 4 PM); Ideal for care homes with limited weekday staff availability; Popular for roles requiring EFAW/Paeds before employment starts. Book ahead: Weekend and evening slots fill quickly. Give us 4:6 weeks\' notice for these. Not every course suits evening/weekend: Some hands:on courses (e.g., advanced moving and handling) work better during regular hours. We\'ll advise what\'s possible when you call.'],
        ['category' => 'booking', 'question' => 'What happens if staff can\'t attend on the day?', 'answer' => 'If a delegate can\'t make it: 1. More than 7 days before: Free transfer to another date (no charge, no refund); 2. Less than 7 days: Same policy applies (we operate on goodwill, not penalties); 3. Last:minute emergency: Contact us ASAP. We\'ll try to reschedule or find a replacement from your team. Why we\'re flexible: We know care is unpredictable. Unplanned absences happen. Swapping delegates is often the easiest solution. What we ask: Just give us notice so we can update the register and ensure the right people are trained.'],
        ['category' => 'booking', 'question' => 'Can I change training dates after booking?', 'answer' => 'Yes, with flexibility depending on timing: 8+ weeks before: Free date change, no questions; 4:8 weeks before: Free change if we have availability; 2:4 weeks before: Possible, but limited slots:ask first; Less than 2 weeks: Difficult, but we\'ll try. Delegate swaps: Super easy. If Person A can\'t make 15 March but Person B can, just tell us. No charge. Group courses: If you\'ve booked a bespoke on:site course and need to reschedule, we\'ll work with your calendar. Usually 2:3 weeks\' notice keeps things smooth. Bottom line: We work around your needs. Life in care is busy:we get it.'],
        
        // Certification & Accreditation (8 FAQs)
        ['category' => 'certification', 'question' => 'Are your courses CQC:compliant?', 'answer' => 'Yes:100%. Every course we deliver aligns with: CQC Regulation 18 (Training requirements); CQC Inspection Framework (Key Lines of Enquiry for training and competence); Skills for Care standards (statutory and mandatory training guide); HSE requirements (for First Aid and Health & Safety courses). What this means practically: Our content covers what CQC inspectors expect to see; We provide evidence (certificates, attendance records, competency sign:offs); Our courses bridge the gap between "completed training" and "can actually do the job"; If inspectors ask, "Can you evidence competence?":we help you answer confidently. CTA Commitment: We don\'t just deliver courses. We help you build a training portfolio that stands up to CQC scrutiny.'],
        ['category' => 'certification', 'question' => 'What accreditations do you have?', 'answer' => 'CTA holds: Advantage Accreditation : Centre of the Year 2021; HSE Approval : For Emergency First Aid at Work (Level 3); CPD Accreditation : All our courses are CPD:registered; Skills for Care alignment : Our content matches their statutory and mandatory training standards; Ofsted compliance : For childcare:related courses (Paediatric First Aid). Quality Assurance: Trainers are industry:experienced (not just certified); Annual quality reviews and updates; Feedback:driven course design; Scenario:based, practical assessment. CTA Transparency: We\'re happy to share accreditation documents. Ask when you enquire.'],
        ['category' => 'certification', 'question' => 'Who accredits your certificates?', 'answer' => 'Depends on the course: First Aid (EFAW/Paediatric): HSE:approved via our accreditation body; Medication Competency: CQC:compliant, Skills for Care:aligned assessment; Safeguarding, Moving & Handling, etc.: CPD:accredited and Skills for Care:referenced; Care Certificate: Aligned with Skills for Care standards (if relevant to your pathway). What this means: Your certificates carry weight nationally. Employers, CQC, and other providers recognise them. Not a franchise course list? No. We deliver tailored, CQC:compliant training. Certificates are evidence of your competence, assessed by our expert trainers in real:world scenarios.'],
        ['category' => 'certification', 'question' => 'Are your certificates accepted nationally?', 'answer' => 'Yes. Our certificates are: Recognised by CQC; Accepted by employers across the UK; Valid for roles in care homes, domiciliary care, nursing, supported living, and specialist services; Transferable if staff move between employers. The only exception: Some roles (e.g., registered nurse, specific clinical roles) may require additional qualifications or registration. We\'ll advise on this during booking. Pro tip: Your training records (ours + any others) build a portfolio showing ongoing competence development. This is gold for CQC and for staff morale.'],
        ['category' => 'certification', 'question' => 'Do you provide digital certificates?', 'answer' => 'Yes:instant digital delivery after course completion. After your course ends: Digital certificate sent to your email same day (or next business day); PDF format:easy to print, share, or store; Includes attendee name, course name, date, trainer name, and validity period; Registrar\'s signature and CTA accreditation details. Physical copies: Available on request:contact us for details. Storing certificates: We recommend: Digital backup (secure shared drive); Staff personnel files; Training management system (if you use one). CQC Inspection: Have these ready. Inspectors will ask to see evidence. Digital + physical copies = fully prepared.'],
        ['category' => 'certification', 'question' => 'How quickly do we receive certificates?', 'answer' => 'Typically within 24 hours of course completion. For courses ending in the afternoon, digital certificates are sent by end of business. For courses ending mid:day, you usually have them by email within 2 hours. Urgent timescales? If you need evidence before a specific date (e.g., CQC inspection notice), let us know when booking. We can often expedite. No waiting games: This is one advantage of in:person training:you know immediately if staff are competent, and you get proof fast.'],
        ['category' => 'certification', 'question' => 'What if a certificate is lost?', 'answer' => 'No problem:we hold records. Email us with attendee name and course date; We\'ll provide a replacement digital certificate (free); Physical copy available if needed:contact us for details; Process usually takes 2:3 working days. Backup strategy: Keep digital copies of all certificates in a secure shared folder (Google Drive, OneDrive, etc.). This prevents loss and makes CQC inspections stress:free.'],
        ['category' => 'certification', 'question' => 'Do your courses meet Skills for Care standards?', 'answer' => 'Completely. All our content aligns with: Skills for Care Statutory and Mandatory Training Guide (August 2024 update); Care Certificate standards (15 standards for adult social care workers); Oliver McGowan Training on Learning Disability and Autism; Leadership and management frameworks (for manager:level courses). Why this matters: If you\'re applying for Workforce Development Fund (LDSS) grants, our courses are eligible; Staff trained with us have a recognised, national qualification; CQC sees Skills for Care alignment as best practice. CTA + Skills for Care: We stay updated on changes and refresh our content annually. You\'re always current.'],
        
        // Course:Specific Questions (6 FAQs)
        ['category' => 'course:specific', 'question' => 'What\'s included in the Care Certificate?', 'answer' => 'The Care Certificate covers 15 standards: 1. Understanding your role : Knowing your responsibilities and accountabilities; 2. Your health, safety and wellbeing : Protecting yourself while at work; 3. Duty of care : Understanding safeguarding and your legal obligations; 4. Equality and inclusion : Treating people fairly and respecting diversity; 5. Working in a person:centred way : Putting the individual at the centre; 6. Communication : Listening, speaking, and understanding diverse needs; 7. Privacy and dignity : Respecting confidentiality and personal space; 8. Fluids and nutrition : Supporting healthy eating and drinking; 9. Awareness of mental health, dementia and learning disabilities; 10. Safeguarding adults; 11. Safeguarding children; 12. Basic life support and First Aid; 13. Health and safety in care settings; 14. Handling information and keeping it confidential; 15. Infection prevention and control. Format: Mix of taught sessions, practice scenarios, and practical assessment. Time: Usually 8:10 days (depending on delivery method). CTA Approach: We deliver Care Certificate content in real:world scenarios using your setting. Staff leave not just "trained" but confident.'],
        ['category' => 'course:specific', 'question' => 'Which first aid course do childcare staff need?', 'answer' => 'Childcare staff require: Emergency Paediatric First Aid (Level 3), OFSTED:approved. This covers: CPR on infants and children; Choking (different techniques for kids); Common paediatric emergencies (febrile convulsions, allergic reactions, etc.); Recovery position for children; Assessment and reassurance in a crisis. Why separate from adult EFAW? Anatomy differs (tiny airways, different compression depths), and early childhood scenarios are unique. Both courses matter: Some roles (e.g., managers in nurseries) benefit from both Adult EFAW and Paediatric EFAW:for comprehensive coverage. CTA Delivery: One:day, practical course. Small groups, lots of mannequin practice. Staff leave confident they could handle a real paediatric emergency. Regulatory note: OFSTED expects evidence of Paediatric First Aid. It\'s not optional in childcare.'],
        ['category' => 'course:specific', 'question' => 'What\'s the difference between medication awareness and competency?', 'answer' => 'Medication Awareness: Understand what medications are, why people take them, side effects; Know how to store and handle medications safely; Understand why accurate records matter; Can explain but not administer. Medication Competency: Can administer medications correctly (oral, topical, injected:depending on role); Understands the "5 Rights" (right person, drug, dose, route, time); Can assess when to withhold medication; Assessed as competent by a trainer/assessor. When Awareness enough? Roles where staff handle meds but don\'t administer (e.g., care assistants, domiciliary support). When Competency needed? Direct administration:care workers giving tablets, nurses giving injections, anyone signing off medication administration. CQC Reality: Inspectors ask, "Who can administer medications?" Your answer must be specific and evidenced. Awareness ≠ Competency. CTA Approach: We assess actual competence. No guessing on the day:we verify you can do it safely.'],
        ['category' => 'course:specific', 'question' => 'Do I need moving & handling theory AND practical?', 'answer' => 'Yes:both are essential. They\'re not separate. Theory (classroom): Understanding biomechanics, spine health, loads; Legislation (Health & Safety at Work Act, MHOR); Risk assessment approach; Communication and consent. Practical (hands:on): Transferring using equipment (slide sheets, hoists, turntables); Manual handling techniques (where absolutely necessary); Adaptive methods for different conditions (stroke, arthritis, dementia); Real equipment your service uses. Why both? You can\'t safely move someone without understanding why you\'re doing it that way. Theory informs practice. Duration: Usually 1:2 days depending on role complexity. CTA Difference: We bring your actual equipment. Training happens in your care environment (if on:site), not a sterile classroom.'],
        ['category' => 'course:specific', 'question' => 'Is safeguarding training different for managers?', 'answer' => 'Yes:significantly. Safeguarding Level 1 (for all care workers): Recognising signs of abuse/neglect; Knowing who to report to; Understanding your role; Basic case scenarios. Safeguarding Level 2 (for supervisory/team roles): More detailed abuse types (including institutional, self:neglect); Recording and evidence gathering; Supporting victims and witnesses; Creating a safeguarding culture; Policy and procedure implementation. Safeguarding Level 3 (for managers/registered managers): Safeguarding strategy and policy development; Managing allegations; Multi:agency working (police, social care investigations); Creating systems and oversight; Legal responsibilities and accountability. CQC Inspection: Inspectors specifically check that managers have Level 2 or 3 evidence. If you don\'t, that\'s a compliance gap. CTA Delivery: Role:specific, scenario:based. Managers leave with confidence in handling real safeguarding issues.'],
        ['category' => 'course:specific', 'question' => 'What level of dementia training do we need?', 'answer' => 'Depends on your role and service type: Level 1 (Awareness:for all staff): What dementia is, types (Alzheimer\'s, vascular, etc.); Progression and symptoms; Communicating with someone with dementia; Reducing triggers for distress; Basic person:centred approaches. Who needs it? Everyone in care. Level 2 (Principles:for care and supervisory roles): Deeper understanding of dementia care; Understanding behaviour as communication; Environmental design for dementia support; Working with families; Managing complex behaviours. Who needs it? Care workers, team leaders, activity coordinators. Level 3 (Advanced:for managers, specialists): Dementia care strategy; Staff training and supervision; Complex presentations (advanced dementia, co:morbidities); End:of:life care in dementia. Who needs it? Registered managers, clinical leads. CQC Expectation: All staff should have at least Level 1. If your service specialises in dementia, Level 2+ is standard. CTA Reality: Dementia care isn\'t a checkbox. It\'s a way of thinking. We train for genuine understanding, not just certificate collection.'],
        
        // Payment & Funding (6 FAQs)
        ['category' => 'payment', 'question' => 'What payment methods do you accept?', 'answer' => 'We accept: Debit and credit cards (Visa, Mastercard, American Express); Bank transfer (BACS); Cheque (with advance notice). Eventbrite bookings: Payment taken online (card only) at booking. Large group/invoice:based bookings: Bank transfer often preferred. We\'ll invoice after confirming course details. No payment issues: CTA is transparent on pricing. No hidden fees, no surprise charges. Payment timing: Invoiced courses typically due within 30 days. Eventbrite bookings are immediate.'],
        ['category' => 'payment', 'question' => 'Do you offer payment plans?', 'answer' => 'For larger group training commitments, yes. If you\'re investing in an annual training plan (e.g., quarterly mandatory updates for a care home), we can discuss: Staged payments across the year; Deposit + final payment structure; Monthly training packages with set costs. Small individual courses: Fixed pricing, payment upfront (Eventbrite) or via invoice. How to arrange: Email enquiries@continuitytrainingacademy.co.uk with your training needs. We\'ll discuss options. CTA Philosophy: We partner with you for the long term. If payment structure is the barrier, let\'s solve it.'],
        ['category' => 'payment', 'question' => 'Can we use Workforce Development Fund?', 'answer' => 'Short answer: Yes, but it\'s now called LDSS (Learning and Development Support Scheme). What changed: The Workforce Development Fund (WDF) was replaced by the Adult Social Care Learning and Development Support Scheme (LDSS) from April 2025. How it works: 1. Check the eligible course list on gov.uk (our courses are listed); 2. Book and pay for training upfront; 3. Claim reimbursement from LDSS (up to the stated maximum per course). Eligible courses include: The Oliver McGowan Training (Tier 1 & 2); Leadership and management programmes; Specialist qualifications (dementia, autism, end:of:life care); Some diploma:level adult care qualifications. What\'s NOT covered: General awareness training, First Aid, moving & handling (unless part of a larger qualification). CTA Support: When you book, tell us you\'re using LDSS. We\'ll provide invoices and documentation to support your claim. Important: Check the eligible course list and reimbursement rates annually. LDSS changes quarterly.'],
        ['category' => 'payment', 'question' => 'Do you provide training invoices for our records?', 'answer' => 'Absolutely. We provide: Itemised invoices (course name, date, attendee list, cost); Attendance certificates for all participants; Training records showing competency sign:off; Digital copies of all documents. Invoice timing: Sent within 2 working days of course completion. For LDSS claims: We provide all documentation needed to submit your reimbursement claim. Compliance ready: Everything is formatted for audit and CQC inspection.'],
        ['category' => 'payment', 'question' => 'Are there discounts for multiple courses?', 'answer' => 'Yes:our group discount structure includes volume savings based on the number of delegates and courses booked. Multiple courses over a year? Even better. Annual packages offer significant savings compared to ad:hoc bookings. Contact us with your annual training plan and we\'ll provide a bespoke quote tailored to your needs. How to arrange: Email enquiries@continuitytrainingacademy.co.uk with your annual training plan. We\'ll quote a bespoke package.'],
        ['category' => 'payment', 'question' => 'Can we pay per delegate or per course?', 'answer' => 'Both options available, depending on structure: Per delegate (most common for groups): You pay for each person attending; Useful if team numbers fluctuate. Per course (block booking): You book a course for a specific date; One price, regardless of final headcount (within limits); Useful for annual planning. Flexible approach: If some staff might not attend, per:delegate pricing reduces financial risk. If you\'re certain of headcount, per:course is often cheaper. When booking: We\'ll ask about your preference and recommend the most cost:effective option. Contact us to discuss pricing for your specific needs.'],
        
        // Group Training & Employers (6 FAQs)
        ['category' => 'group:training', 'question' => 'What\'s the minimum group size for on:site training?', 'answer' => 'Minimum: 6 people for on:site courses (to make travel worthwhile for our trainer). Smaller groups (1:5 people): Can usually attend a public course instead, often at similar or better cost. Larger groups (8:25 people): Often more cost:effective on:site with group discounts. Contact us to discuss pricing for your group size and we\'ll recommend the most cost:effective option. Talk to us: If you have 4:5 people, email enquiries@continuitytrainingacademy.co.uk. We might combine them with another organisation\'s group or suggest public course dates.'],
        ['category' => 'group:training', 'question' => 'Can you tailor training to our policies?', 'answer' => 'Completely:that\'s what we do. When we deliver on:site training, we: Review your policies and procedures beforehand; Tailor scenarios to your care environment; Use your equipment (hoists, moving aids, medication charts); Address your specific risks and challenges; Train your staff in your context, not generic care theory. Examples: A domiciliary care agency: We focus on home safety, lone working, medication in non:clinical settings; A care home: We include your facilities, your resident needs, your escalation procedures; A nursing home: We address clinical protocols, medication administration, delegation. CQC Reality: Inspectors often ask, "Is your training tailored to your service?" Bespoke training shows yes. CTA Commitment: No off:the:shelf courses. Your training, your setting, your standards.'],
        ['category' => 'group:training', 'question' => 'Do you provide training matrices for your staff?', 'answer' => 'Yes:in multiple formats. What we provide: Role:based training matrices (care worker, team leader, manager, clinical staff, specialists); Mapped against CQC Key Lines of Enquiry; Showing mandatory, recommended, and specialist training; Frequency and refresher timelines; Eligibility criteria for each role. Formats: Excel (editable:you can adapt to your specific structure); PDF (for sharing with staff, managers); Printable or digital storage. How it works: You use the matrix to plan training for each team member, identify gaps, and schedule refreshers. It becomes your annual training plan. CTA Support: We help populate the matrix during your first year, then you own it.'],
        ['category' => 'group:training', 'question' => 'Can you track training for multiple sites?', 'answer' => 'For group training contracts, yes. If you operate multiple care homes, domiciliary services, or nursing facilities, we: Maintain separate attendance and competency records per site; Provide aggregate reports showing compliance across all locations; Help you identify organisation:wide training gaps; Support your quality assurance and CQC preparation. How it works: Provide us with a list of sites and roles; We schedule training across all locations; We send site:specific and consolidated reports; You have a complete training audit trail. Size: Works well for organisations with 2:5 locations. Larger networks? Discuss your reporting needs when you call. We may recommend a training management system.'],
        ['category' => 'group:training', 'question' => 'Do you offer annual training contracts?', 'answer' => 'Yes:our preferred model for care providers. An annual training contract typically includes: Quarterly mandatory refreshers for all staff; New starter induction training; Role:specific development (team leader, manager, clinical); Specialist courses (dementia, end:of:life, safeguarding advanced); On:site delivery where possible; Priority booking and reserved dates; Discounted rates compared to ad:hoc bookings; Annual training calendar and planning support. Cost varies depending on staff size and course mix:contact us for a tailored quote. Benefit: Compliance is sorted. You\'re not scrambling to book last:minute courses or facing expired certificates. How to arrange: 1. Email enquiries@continuitytrainingacademy.co.uk; 2. Describe your team size, roles, and compliance needs; 3. We\'ll build a bespoke annual plan and quote. CTA Reality: This is where we excel:long:term partnerships, strategic training planning, embedded quality.'],
        ['category' => 'group:training', 'question' => 'Can trainers visit multiple locations?', 'answer' => 'Absolutely:we\'re mobile. If you operate multiple sites across Kent and the South East: Our trainer can visit Site A, Site B, Site C on consecutive days; Cost:effective (one trainer deployment vs. three separate bookings); Consistent messaging across locations; Easier compliance tracking. What we need: 2:3 weeks\' notice for a multi:site tour; Clear list of dates, locations, and participant numbers; Appropriate training space at each location; Same course (easier to coordinate) or closely related courses. Example: A domiciliary care provider with 3 office locations books Emergency First Aid at all three on Mon/Tues/Weds. One trainer, one week, significant savings. Logistics: We handle travel. You just confirm locations and dates. CTA Advantage: Mobile, flexible, organised. Your training fits your geography, not the other way around.'],
    ];
}

// Get all FAQ categories with their icons
$faq_categories = get_terms([
    'taxonomy' => 'faq_category',
    'hide_empty' => false,
    'orderby' => 'term_order',
    'order' => 'ASC',
]);

// Build category map with icons and organize FAQs
$faqs_by_category = [];
$category_info = [];

foreach ($faq_categories as $term) {
    $category_slug = $term->slug;
    // Map taxonomy slug to template category slug (handle colons)
    $template_slug = str_replace('-', ':', $category_slug);
    if ($template_slug === 'course-specific') {
        $template_slug = 'course:specific';
    } elseif ($template_slug === 'group-training') {
        $template_slug = 'group:training';
    }
    
    $icon = get_field('faq_category_icon', 'faq_category_' . $term->term_id);
    if (empty($icon)) {
        // Fallback to Font Awesome icons for default categories
        $default_icons = [
            'general' => '<i class="fas fa-graduation-cap" aria-hidden="true"></i>',
            'booking' => '<i class="fas fa-calendar-alt" aria-hidden="true"></i>',
            'certification' => '<i class="fas fa-certificate" aria-hidden="true"></i>',
            'course-specific' => '<i class="fas fa-book-open" aria-hidden="true"></i>',
            'course:specific' => '<i class="fas fa-book-open" aria-hidden="true"></i>',
            'payment' => '<i class="fas fa-pound-sign" aria-hidden="true"></i>',
            'group-training' => '<i class="fas fa-users" aria-hidden="true"></i>',
            'group:training' => '<i class="fas fa-users" aria-hidden="true"></i>',
        ];
        $icon = isset($default_icons[$category_slug]) ? $default_icons[$category_slug] : (isset($default_icons[$template_slug]) ? $default_icons[$template_slug] : '<i class="fas fa-question-circle" aria-hidden="true"></i>');
    }
    
    $category_info[$template_slug] = [
        'name' => $term->name,
        'slug' => $category_slug,
        'template_slug' => $template_slug,
        'icon' => $icon,
    ];
    $faqs_by_category[$template_slug] = [];
}

// Organize FAQs by category
foreach ($faqs as $faq) {
    if (is_array($faq) && isset($faq['category']) && isset($faq['question']) && isset($faq['answer'])) {
        $cat = $faq['category'];
        if (isset($faqs_by_category[$cat])) {
            $faqs_by_category[$cat][] = $faq;
        } else {
            // If category doesn't exist in our map, add it
            if (!isset($faqs_by_category[$cat])) {
                $faqs_by_category[$cat] = [];
                // Try to find the term
                $term = get_term_by('slug', str_replace(':', '-', $cat), 'faq_category');
                if ($term) {
                    $icon = get_field('faq_category_icon', 'faq_category_' . $term->term_id);
                    if (empty($icon)) {
                        $icon = '<i class="fas fa-question-circle" aria-hidden="true"></i>';
                    }
                    $category_info[$cat] = [
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'template_slug' => $cat,
                        'icon' => $icon,
                    ];
                } else {
                    // Fallback for categories that don't have terms yet
                    $category_info[$cat] = [
                        'name' => ucwords(str_replace([':', '-'], ' ', $cat)),
                        'slug' => str_replace(':', '-', $cat),
                        'template_slug' => $cat,
                        'icon' => '<i class="fas fa-question-circle" aria-hidden="true"></i>',
                    ];
                }
            }
            $faqs_by_category[$cat][] = $faq;
        }
    }
}

// Generate FAQ schema
$faq_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [],
];

foreach ($faqs as $faq) {
    if (is_array($faq) && isset($faq['question']) && isset($faq['answer'])) {
        $faq_schema['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer'],
            ],
        ];
    }
}
?>

<?php if (!empty($faq_schema['mainEntity'])) : ?>
<script type="application/ld+json">
<?php echo json_encode($faq_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>
<?php endif; ?>

<main id="main-content" class="site-main">
  <!-- Hero Section -->
  <section class="group-hero-section" aria-labelledby="faqs-heading">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>#resources" class="breadcrumb-link">Resources</a></li>
          <li class="breadcrumb-separator" aria-hidden="true">/</li>
          <li class="breadcrumb-item"><span class="breadcrumb-current" aria-current="page">FAQs</span></li>
        </ol>
      </nav>
      <h1 id="faqs-heading" class="hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
      
      <!-- Search Bar -->
      <div class="faqs-search-container">
        <div class="faqs-search-wrapper">
          <i class="fas fa-search faqs-search-icon" aria-hidden="true"></i>
          <input 
            type="search" 
            id="faq-search" 
            class="faqs-search-input"
            placeholder="Search FAQs..." 
            aria-label="Search frequently asked questions"
            autocomplete="off"
            aria-autocomplete="list"
            aria-expanded="false"
            aria-controls="faq-suggestions"
          />
          <div id="faq-suggestions" class="faq-suggestions" role="listbox" aria-label="FAQ search suggestions"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ Categories -->
  <section class="content-section" aria-labelledby="faq-categories-heading">
    <div class="container">
      <div class="section-header-center">
        <h2 id="faq-categories-heading" class="section-title">Browse FAQs by Category</h2>
      </div>
      
      <!-- Category Filters -->
      <div class="faqs-filter-group">
        <button type="button" class="faqs-filter-btn active" data-category="all" aria-pressed="true">
          <i class="fas fa-th" aria-hidden="true"></i> All Categories
        </button>
        <?php foreach ($category_info as $cat_slug => $cat_data) : 
          // Only show filter if category has FAQs
          if (!empty($faqs_by_category[$cat_slug])) :
            $filter_slug = str_replace(':', '-', $cat_slug);
        ?>
        <button type="button" class="faqs-filter-btn" data-category="<?php echo esc_attr($filter_slug); ?>" aria-pressed="false">
          <span class="faq-filter-icon" aria-hidden="true"><?php echo wp_kses($cat_data['icon'], ['svg' => ['width' => true, 'height' => true, 'viewbox' => true, 'fill' => true, 'class' => true, 'aria-hidden' => true], 'path' => ['d' => true, 'fill' => true], 'i' => ['class' => true, 'aria-hidden' => true]]); ?></span> <?php echo esc_html($cat_data['name']); ?>
        </button>
        <?php endif; endforeach; ?>
      </div>
      
      <div class="faqs-content-wrapper">
        <?php foreach ($category_info as $cat_slug => $cat_data) : 
          if (!empty($faqs_by_category[$cat_slug])) :
            $filter_slug = str_replace(':', '-', $cat_slug);
            $category_id = sanitize_title($cat_slug);
        ?>
        <div class="faqs-category-section" data-category="<?php echo esc_attr($filter_slug); ?>">
          <h3 class="faqs-category-title">
            <span class="faqs-category-icon" aria-hidden="true"><?php echo wp_kses($cat_data['icon'], ['svg' => ['width' => true, 'height' => true, 'viewbox' => true, 'fill' => true, 'class' => true, 'aria-hidden' => true, 'xmlns' => true], 'path' => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true], 'i' => ['class' => true, 'aria-hidden' => true]]); ?></span>
            <?php echo esc_html($cat_data['name']); ?>
          </h3>
          <div class="group-faq-list">
          <?php foreach ($faqs_by_category[$cat_slug] as $index => $faq) : 
            $faq_id = $category_id . '-' . (int) $index;
          ?>
          <div class="accordion faq-item" data-accordion-group="faqs" data-faq-category="<?php echo esc_attr($filter_slug); ?>">
            <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="faq-<?php echo esc_attr($faq_id); ?>">
              <span><?php echo esc_html($faq['question']); ?></span>
              <span class="accordion-icon" aria-hidden="true">
                <i class="fas fa-plus" aria-hidden="true"></i>
                <i class="fas fa-minus" aria-hidden="true"></i>
              </span>
            </button>
            <div id="faq-<?php echo esc_attr($faq_id); ?>" class="accordion-content" role="region" aria-hidden="true">
              <?php echo cta_format_faq_answer($faq['answer']); ?>
            </div>
          </div>
          <?php endforeach; ?>
          </div>
        </div>
        <?php endif; endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Still Have Questions Section -->
  <section class="content-section bg-light-cream">
    <div class="container">
      <div class="faqs-contact-box">
        <div class="faqs-contact-icon">
          <i class="fas fa-question-circle" aria-hidden="true"></i>
        </div>
        <h2>Can't Find What You're Looking For?</h2>
        <p>Call us, send an enquiry, or request a callback.</p>
        <div class="faqs-contact-buttons">
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" class="btn btn-primary">Contact Us</a>
          <a href="<?php echo esc_url($contact['phone_link']); ?>" class="btn btn-secondary">Call <?php echo esc_html($contact['phone']); ?></a>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact')) . '?type=schedule-call'); ?>" class="btn btn-secondary">Request Callback</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Related Resources Section -->
  <section class="content-section">
    <div class="container">
      <div class="section-header-center">
        <h2 class="section-title">Related Resources</h2>
        <p class="section-description">Explore more helpful resources and information</p>
      </div>
      
      <div class="cqc-resources-grid">
        <div class="cqc-resource-card">
          <div class="cqc-resource-icon">
            <i class="fas fa-book" aria-hidden="true"></i>
          </div>
          <h3 class="cqc-resource-title">View All Courses</h3>
          <p class="cqc-resource-description">Browse our full range of CQC-compliant training courses.</p>
          <a href="<?php echo esc_url(get_post_type_archive_link('course')); ?>" class="cqc-resource-link">
            View Courses
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
        
        <div class="cqc-resource-card">
          <div class="cqc-resource-icon">
            <i class="fas fa-download" aria-hidden="true"></i>
          </div>
          <h3 class="cqc-resource-title">Training Planning Guide</h3>
          <p class="cqc-resource-description">Free guide to help you plan your team's training and development.</p>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('downloadable-resources'))); ?>" class="cqc-resource-link">
            Download Guide
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
        
        <div class="cqc-resource-card">
          <div class="cqc-resource-icon">
            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
          </div>
          <h3 class="cqc-resource-title">CQC Compliance Hub</h3>
          <p class="cqc-resource-description">Comprehensive guide to CQC training requirements and compliance.</p>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('cqc-compliance-hub'))); ?>" class="cqc-resource-link">
            Read Hub
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
        
        <div class="cqc-resource-card">
          <div class="cqc-resource-icon">
            <i class="fas fa-calendar-check" aria-hidden="true"></i>
          </div>
          <h3 class="cqc-resource-title">Free Consultation</h3>
          <p class="cqc-resource-description">Speak with our training advisors about your specific needs.</p>
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('contact'))); ?>" class="cqc-resource-link">
            Book Consultation
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
  </section>
</main>

<script>
(function() {
  'use strict';
  
  // FAQ Filter and Search (accordion functionality handled by unified accordion.js)
  const filterButtons = document.querySelectorAll('.faqs-filter-btn');
  const searchInput = document.getElementById('faq-search');
  const faqSections = document.querySelectorAll('.faqs-category-section');
  const faqItems = document.querySelectorAll('.faq-item');
  
  let currentCategory = 'all';
  let currentSearch = '';
  
  // Filter by category
  function filterByCategory(category) {
    currentCategory = category;
    
    // Update active button
    filterButtons.forEach(btn => {
      const isActive = btn.getAttribute('data-category') === category;
      btn.classList.toggle('active', isActive);
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
    
    // Apply filters
    applyFilters();
  }
  
  // Search FAQs
  function searchFAQs(query) {
    currentSearch = query.toLowerCase().trim();
    applyFilters();
  }
  
  // Apply combined filters (category + search)
  function applyFilters() {
    faqSections.forEach(section => {
      const sectionCategory = section.getAttribute('data-category');
      const sectionItems = section.querySelectorAll('.faq-item');
      let visibleItems = 0;
      
      sectionItems.forEach(item => {
        const itemCategory = item.getAttribute('data-faq-category');
        const question = item.querySelector('.accordion-trigger span')?.textContent.toLowerCase() || '';
        const answer = item.querySelector('.accordion-content p')?.textContent.toLowerCase() || '';
        
        // Check category match
        const categoryMatch = currentCategory === 'all' || itemCategory === currentCategory;
        
        // Check search match (search in both question and answer)
        const searchMatch = !currentSearch || 
                           question.includes(currentSearch) || 
                           answer.includes(currentSearch);
        
        // Show/hide item
        if (categoryMatch && searchMatch) {
          item.style.display = 'block';
          visibleItems++;
        } else {
          item.style.display = 'none';
        }
      });
      
      // Show/hide entire section based on visibility of items
      section.style.display = visibleItems > 0 ? 'block' : 'none';
    });
  }
  
  // Check URL parameter for initial category filter
  const urlParams = new URLSearchParams(window.location.search);
  const categoryParam = urlParams.get('category');
  if (categoryParam) {
    // Map URL parameter to filter category
    const categoryMap = {
      'general': 'general',
      'booking': 'booking',
      'certification': 'certification',
      'payment': 'payment',
      'group-training': 'group-training',
    };
    const mappedCategory = categoryMap[categoryParam] || categoryParam;
    if (mappedCategory && document.querySelector(`.faqs-filter-btn[data-category="${mappedCategory}"]`)) {
      filterByCategory(mappedCategory);
      // Scroll to FAQs section if needed
      setTimeout(() => {
        const faqsSection = document.querySelector('.faqs-content-wrapper');
        if (faqsSection) {
          faqsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 100);
    }
  }
  
  // Event listeners
  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const category = btn.getAttribute('data-category');
      filterByCategory(category);
      // Update URL without reload
      const url = new URL(window.location);
      if (category === 'all') {
        url.searchParams.delete('category');
      } else {
        url.searchParams.set('category', category);
      }
      window.history.pushState({}, '', url);
    });
  });
  
  // FAQ Search Suggestions
  const suggestionsContainer = document.getElementById('faq-suggestions');
  let allFAQQuestions = [];
  let selectedSuggestionIndex = -1;
  
  // Build FAQ questions list from DOM
  function buildFAQQuestionsList() {
    allFAQQuestions = [];
    faqItems.forEach((item, index) => {
      const questionEl = item.querySelector('.accordion-trigger span');
      if (questionEl) {
        const question = questionEl.textContent.trim();
        const category = item.getAttribute('data-faq-category') || 'general';
        allFAQQuestions.push({
          question: question,
          category: category,
          element: item
        });
      }
    });
  }
  
  // Get matching suggestions
  function getSuggestions(query) {
    if (!query || query.length < 2) {
      return [];
    }
    
    const queryLower = query.toLowerCase().trim();
    const matches = allFAQQuestions
      .filter(faq => {
        const questionLower = faq.question.toLowerCase();
        return questionLower.includes(queryLower);
      })
      .slice(0, 5); // Limit to 5 suggestions
    
    return matches;
  }
  
  // Render suggestions
  function renderSuggestions(suggestions) {
    if (!suggestionsContainer) return;
    
    if (suggestions.length === 0) {
      suggestionsContainer.innerHTML = '';
      suggestionsContainer.style.display = 'none';
      searchInput.setAttribute('aria-expanded', 'false');
      return;
    }
    
    suggestionsContainer.innerHTML = suggestions.map((faq, index) => {
      // Highlight matching text
      const questionLower = faq.question.toLowerCase();
      const queryLower = searchInput.value.toLowerCase().trim();
      const matchIndex = questionLower.indexOf(queryLower);
      
      let highlightedQuestion = faq.question;
      if (matchIndex !== -1) {
        const before = faq.question.substring(0, matchIndex);
        const match = faq.question.substring(matchIndex, matchIndex + queryLower.length);
        const after = faq.question.substring(matchIndex + queryLower.length);
        highlightedQuestion = `${before}<mark>${match}</mark>${after}`;
      }
      
      return `
        <button 
          type="button"
          class="faq-suggestion-item ${index === selectedSuggestionIndex ? 'selected' : ''}"
          role="option"
          data-index="${index}"
          aria-selected="${index === selectedSuggestionIndex}"
        >
          <span class="faq-suggestion-text">${highlightedQuestion}</span>
        </button>
      `;
    }).join('');
    
    suggestionsContainer.style.display = 'block';
    searchInput.setAttribute('aria-expanded', 'true');
    
    // Add click handlers
    suggestionsContainer.querySelectorAll('.faq-suggestion-item').forEach((btn, index) => {
      btn.addEventListener('click', () => {
        selectSuggestion(suggestions[index]);
      });
    });
  }
  
  // Select a suggestion
  function selectSuggestion(faq) {
    searchInput.value = faq.question;
    searchInput.setAttribute('aria-expanded', 'false');
    suggestionsContainer.style.display = 'none';
    selectedSuggestionIndex = -1;
    
    // Trigger search
    searchFAQs(faq.question);
    
    // Scroll to FAQ item
    setTimeout(() => {
      if (faq.element) {
        faq.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Open accordion if closed
        const trigger = faq.element.querySelector('.accordion-trigger');
        if (trigger && trigger.getAttribute('aria-expanded') === 'false') {
          trigger.click();
        }
      }
    }, 100);
  }
  
  // Initialize FAQ questions list
  buildFAQQuestionsList();
  
  if (searchInput) {
    let searchTimeout;
    let suggestionsTimeout;
    
    // Handle input
    searchInput.addEventListener('input', (e) => {
      const query = e.target.value;
      
      // Clear previous timeouts
      clearTimeout(searchTimeout);
      clearTimeout(suggestionsTimeout);
      
      // Show suggestions immediately
      suggestionsTimeout = setTimeout(() => {
        const suggestions = getSuggestions(query);
        renderSuggestions(suggestions);
        selectedSuggestionIndex = -1;
      }, 150);
      
      // Debounce search
      searchTimeout = setTimeout(() => {
        searchFAQs(query);
      }, 300);
    });
    
    // Handle keyboard navigation
    searchInput.addEventListener('keydown', (e) => {
      const suggestions = Array.from(suggestionsContainer.querySelectorAll('.faq-suggestion-item'));
      
      if (suggestions.length === 0) return;
      
      switch(e.key) {
        case 'ArrowDown':
          e.preventDefault();
          selectedSuggestionIndex = Math.min(selectedSuggestionIndex + 1, suggestions.length - 1);
          updateSuggestionSelection(suggestions);
          break;
          
        case 'ArrowUp':
          e.preventDefault();
          selectedSuggestionIndex = Math.max(selectedSuggestionIndex - 1, -1);
          updateSuggestionSelection(suggestions);
          break;
          
        case 'Enter':
          e.preventDefault();
          if (selectedSuggestionIndex >= 0 && selectedSuggestionIndex < suggestions.length) {
            const currentSuggestions = getSuggestions(searchInput.value);
            if (currentSuggestions[selectedSuggestionIndex]) {
              selectSuggestion(currentSuggestions[selectedSuggestionIndex]);
            }
          } else {
            // Just trigger search with current value
            searchFAQs(searchInput.value);
            suggestionsContainer.style.display = 'none';
            searchInput.setAttribute('aria-expanded', 'false');
          }
          break;
          
        case 'Escape':
          suggestionsContainer.style.display = 'none';
          searchInput.setAttribute('aria-expanded', 'false');
          selectedSuggestionIndex = -1;
          break;
      }
    });
    
    // Close suggestions when clicking outside
    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
        suggestionsContainer.style.display = 'none';
        searchInput.setAttribute('aria-expanded', 'false');
        selectedSuggestionIndex = -1;
      }
    });
  }
  
  // Update suggestion selection highlighting
  function updateSuggestionSelection(suggestions) {
    suggestions.forEach((btn, index) => {
      const isSelected = index === selectedSuggestionIndex;
      btn.classList.toggle('selected', isSelected);
      btn.setAttribute('aria-selected', isSelected);
      
      if (isSelected) {
        btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    });
  }
})();
</script>

<!-- Schema.org Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "name": "Frequently Asked Questions : Care Training",
  "description": "Quick answers to common questions about our training courses, booking, certification, payment, and group training options.",
  "url": "<?php echo esc_url(get_permalink()); ?>",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What training is mandatory for care workers?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Mandatory training includes: Health and Safety Awareness, Infection Prevention and Control, Fire Safety, Moving and Handling, Safeguarding Adults, Learning Disability and Autism Awareness (Oliver McGowan training), and Safeguarding Children for roles involving contact with under:18s. Medication Competency is required for roles involving medication administration."
      }
    },
    {
      "@type": "Question",
      "name": "How often does training need to be renewed?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Most mandatory training needs renewal every three years minimum, unless the content is included in a recognised adult social care qualification within that three:year window. Medication training typically requires annual refreshers due to high:risk nature."
      }
    },
    {
      "@type": "Question",
      "name": "Are your courses CQC:compliant?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, all our courses are designed to meet CQC requirements and expectations. We align content with Skills for Care frameworks, CQC Key Lines of Enquiry, and current legislation including the Health and Social Care Act 2008 (Regulated Activities) Regulations 2014."
      }
    },
    {
      "@type": "Question",
      "name": "What is the Care Certificate?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "The Care Certificate is the mandatory induction standard for new care workers in England. It covers 15 standards including duty of care, communication, safeguarding, health and safety, and person:centred care. It must be completed within 12 weeks of starting employment."
      }
    },
    {
      "@type": "Question",
      "name": "How do I book a course?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "You can book online through our course pages, call us on 01622 587343, or email hello@continuitytrainingacademy.co.uk. For group bookings of 5+ people, contact us for group rates and flexible scheduling options."
      }
    },
    {
      "@type": "Question",
      "name": "Do you offer group training discounts?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, we offer group rates for teams of 5 or more. Group training includes flexible scheduling, on:site delivery options, and customized content to match your service's specific needs and policies."
      }
    },
    {
      "@type": "Question",
      "name": "What payment methods do you accept?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "We accept card payments (Visa, Mastercard, Amex), bank transfer, and purchase orders for established accounts. Payment plans are available for larger bookings, and we can invoice organizations directly."
      }
    },
    {
      "@type": "Question",
      "name": "Will I receive a certificate?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, you'll receive a CPD:accredited certificate immediately upon successful completion. Certificates are valid for three years (or one year for medication training) and include your name, course title, completion date, and CPD hours."
      }
    }
  ],
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "<?php echo esc_url(home_url('/')); ?>"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "FAQs",
        "item": "<?php echo esc_url(get_permalink()); ?>"
      }
    ]
  },
  "publisher": {
    "@type": "EducationalOrganization",
    "name": "Continuity Training Academy",
    "url": "<?php echo esc_url(home_url('/')); ?>"
  }
}
</script>

<?php
get_footer();
?>
