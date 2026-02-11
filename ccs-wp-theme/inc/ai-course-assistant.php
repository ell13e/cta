<?php
/**
 * AI Course Assistant
 * 
 * Provides AI-powered content generation for course and event fields
 * with context-aware prompts and character limit handling
 *
 * @package ccs-theme
 */

defined('ABSPATH') || exit;

/**
 * Get AI context for a field based on post type and field name
 */
function ccs_get_ai_context_for_field($field_name, $post_id) {
    $context = [];
    $post_type = get_post_type($post_id);
    
    if ($post_type === 'course') {
        $context = [
            'title' => get_the_title($post_id),
            'level' => get_field('course_level', $post_id),
            'duration' => get_field('course_duration', $post_id),
            'hours' => get_field('course_hours', $post_id),
            'accreditation' => get_field('course_accreditation', $post_id),
            'price' => get_field('course_price', $post_id),
            'outcomes' => function_exists('ccs_get_outcomes') ? ccs_get_outcomes($post_id) : get_field('course_outcomes', $post_id),
            'suitable_for' => get_field('course_suitable_for', $post_id),
            'prerequisites' => get_field('course_prerequisites', $post_id),
            'category' => '',
        ];
        
        // Get category
        $terms = get_the_terms($post_id, 'course_category');
        if ($terms && !is_wp_error($terms)) {
            $context['category'] = $terms[0]->name;
        }
    } elseif ($post_type === 'course_event') {
        $course = get_field('linked_course', $post_id);
        $context = [
            'event_title' => get_the_title($post_id),
            'event_date' => get_field('event_date', $post_id),
            'start_time' => get_field('start_time', $post_id),
            'end_time' => get_field('end_time', $post_id),
            'location' => get_field('event_location', $post_id),
            'price' => get_field('event_price', $post_id),
            'course' => $course ? [
                'title' => $course->post_title,
                'level' => get_field('course_level', $course->ID),
                'duration' => get_field('course_duration', $course->ID),
                'accreditation' => get_field('course_accreditation', $course->ID),
            ] : null,
        ];
    }
    
    return $context;
}

/**
 * Get authoritative sources for a course based on title and category
 * Uses the complete 40-course SEO guide
 */
function ccs_get_authoritative_sources_for_course($course_title, $category = '') {
    $title_lower = strtolower($course_title);
    $category_lower = strtolower($category);
    
    $sources = [];
    
    // Universal primary sources (always include)
    $sources[] = 'Care Quality Commission (CQC) - https://www.cqc.org.uk - For inspection requirements, compliance standards, and what CQC looks for';
    $sources[] = 'Skills for Health - https://www.skillsforhealth.org.uk - UK Core Skills Training Framework, learning outcomes';
    $sources[] = 'Skills for Care - https://www.skillsforcare.org.uk - Care Certificate standards, workforce data';
    $sources[] = 'Department of Health and Social Care - https://www.gov.uk/government/organisations/department-of-health-and-social-care - Care Act 2014, legislation';
    
    // Course-specific sources (expanded from guide)
    if (stripos($title_lower, 'safeguarding') !== false) {
        $sources[] = 'Care Act 2014 Statutory Guidance (Chapter 14 - Safeguarding)';
        $sources[] = 'Mental Capacity Act 2005 Code of Practice';
        $sources[] = 'Social Care Institute for Excellence (SCIE) - https://www.scie.org.uk - Safeguarding best practice';
    }
    
    if (stripos($title_lower, 'medication') !== false) {
        $sources[] = 'NICE Guideline NG5 (Medicines optimisation) - https://www.nice.org.uk';
        $sources[] = 'Human Medicines Regulations 2012';
        $sources[] = 'Skills for Health Core Skills Training Framework (Level 2 & 3)';
        if (stripos($title_lower, 'competency') !== false || stripos($title_lower, 'level 3') !== false) {
            $sources[] = 'CQC guidance on medicines management';
        }
    }
    
    if (stripos($title_lower, 'first aid') !== false || stripos($title_lower, 'efaw') !== false || stripos($title_lower, 'faw') !== false || stripos($title_lower, 'bls') !== false) {
        $sources[] = 'HSE First Aid at Work regulations (L74 guidance) - https://www.hse.gov.uk';
        $sources[] = 'Resuscitation Council UK guidelines - https://www.resus.org.uk - Latest CPR protocols';
        $sources[] = 'Health and Safety (First Aid) Regulations 1981';
        if (stripos($title_lower, 'paediatric') !== false || stripos($title_lower, 'child') !== false) {
            $sources[] = 'Ofsted requirements (for childcare settings)';
        }
    }
    
    if (stripos($title_lower, 'moving') !== false || stripos($title_lower, 'handling') !== false || stripos($title_lower, 'hoist') !== false || stripos($title_lower, 'positioning') !== false) {
        $sources[] = 'Manual Handling Operations Regulations 1992 (as amended)';
        $sources[] = 'HSE guidance on manual handling (L23) - https://www.hse.gov.uk';
        $sources[] = 'Lifting Operations and Lifting Equipment Regulations 1998 (LOLER)';
        if (stripos($title_lower, 'hoist') !== false) {
            $sources[] = 'Provision and Use of Work Equipment Regulations 1998 (PUWER)';
        }
    }
    
    if (stripos($title_lower, 'fire safety') !== false || stripos($title_lower, 'fire') !== false) {
        $sources[] = 'Regulatory Reform (Fire Safety) Order 2005';
        $sources[] = 'CQC guidance on fire safety';
        $sources[] = 'Fire safety risk assessment guidance for care homes';
    }
    
    if (stripos($title_lower, 'infection') !== false || stripos($title_lower, 'ipc') !== false) {
        $sources[] = 'Health and Social Care Act 2008 (Code of Practice on IPC)';
        $sources[] = 'NICE guideline NG15 (antimicrobial stewardship)';
        $sources[] = 'Public Health England IPC guidelines';
    }
    
    if (stripos($title_lower, 'dementia') !== false) {
        $sources[] = 'NICE guideline NG97 (dementia: assessment, management and support)';
        $sources[] = 'Alzheimer\'s Society resources';
        $sources[] = 'Social Care Institute for Excellence (SCIE) dementia resources';
    }
    
    if (stripos($title_lower, 'diabetes') !== false || stripos($title_lower, 'insulin') !== false) {
        $sources[] = 'NICE guidelines on diabetes (NG17, NG18, NG19, NG28)';
        $sources[] = 'Diabetes UK resources';
        $sources[] = 'NHS diabetes information';
    }
    
    if (stripos($title_lower, 'mental capacity') !== false || stripos($title_lower, 'mca') !== false || stripos($title_lower, 'dols') !== false) {
        $sources[] = 'Mental Capacity Act 2005';
        $sources[] = 'Mental Capacity Act Code of Practice';
        $sources[] = 'Deprivation of Liberty Safeguards (DoLS)';
        $sources[] = 'Liberty Protection Safeguards (LPS) - new system replacing DoLS';
    }
    
    if (stripos($title_lower, 'gdpr') !== false || stripos($title_lower, 'data protection') !== false) {
        $sources[] = 'UK GDPR (General Data Protection Regulation)';
        $sources[] = 'Data Protection Act 2018';
        $sources[] = 'Information Commissioner\'s Office (ICO) guidance';
    }
    
    if (stripos($title_lower, 'nutrition') !== false || stripos($title_lower, 'hydration') !== false) {
        $sources[] = 'NICE guideline NG57 (nutrition support in adults)';
        $sources[] = 'Malnutrition Universal Screening Tool (MUST)';
        $sources[] = 'British Dietetic Association resources';
    }
    
    if (stripos($title_lower, 'food safety') !== false || stripos($title_lower, 'food hygiene') !== false) {
        $sources[] = 'Food Safety Act 1990';
        $sources[] = 'Food Hygiene Regulations 2013';
        $sources[] = 'Food Standards Agency guidance';
    }
    
    if (stripos($title_lower, 'care certificate') !== false || stripos($title_lower, 'social care certificate') !== false) {
        $sources[] = 'Skills for Care - The Care Certificate (15 standards)';
        $sources[] = 'CQC guidance on induction and training';
        $sources[] = 'Care Act 2014 statutory guidance';
    }
    
    if (stripos($title_lower, 'equality') !== false || stripos($title_lower, 'diversity') !== false) {
        $sources[] = 'Equality Act 2010';
        $sources[] = 'Human Rights Act 1998';
        $sources[] = 'CQC guidance on equality and human rights';
    }
    
    if (stripos($title_lower, 'positive behaviour') !== false || stripos($title_lower, 'challenging behaviour') !== false) {
        $sources[] = 'NICE guideline NG11 (challenging behaviour and learning disabilities)';
        $sources[] = 'Positive Behavioural Support framework';
        $sources[] = 'CQC guidance on restraint and restrictive practices';
    }
    
    if (stripos($title_lower, 'end of life') !== false || stripos($title_lower, 'palliative') !== false) {
        $sources[] = 'NICE guideline NG142 (end of life care for adults)';
        $sources[] = 'Gold Standards Framework';
        $sources[] = 'Ambitions for Palliative and End of Life Care';
    }
    
    if (stripos($title_lower, 'epilepsy') !== false) {
        $sources[] = 'NICE guideline CG137 (epilepsies: diagnosis and management)';
        $sources[] = 'Epilepsy Action resources';
        $sources[] = 'MHRA guidance on buccal midazolam';
    }
    
    if (stripos($title_lower, 'tissue viability') !== false || stripos($title_lower, 'pressure ulcer') !== false) {
        $sources[] = 'NICE guideline NG89 (pressure ulcers)';
        $sources[] = 'NHS Tissue Viability guidance';
        $sources[] = 'National Wound Care Strategy';
    }
    
    if (stripos($title_lower, 'oral health') !== false || stripos($title_lower, 'mouth care') !== false) {
        $sources[] = 'NICE guideline NG48 (oral health in care homes)';
        $sources[] = 'Public Health England oral health guidance';
        $sources[] = 'British Dental Association resources';
    }
    
    if (stripos($title_lower, 'oxygen') !== false) {
        $sources[] = 'British Thoracic Society oxygen guidelines';
        $sources[] = 'Medicines and Healthcare products Regulatory Agency (MHRA) guidance';
        $sources[] = 'Fire safety considerations (oxygen is an accelerant)';
    }
    
    if (stripos($title_lower, 'learning disabilit') !== false || stripos($title_lower, 'autism') !== false) {
        $sources[] = 'NICE guideline NG11 (challenging behaviour and learning disabilities)';
        $sources[] = 'Autism Act 2009';
        $sources[] = 'National Autistic Society resources';
    }
    
    if (stripos($title_lower, 'management') !== false || stripos($title_lower, 'leadership') !== false) {
        $sources[] = 'Skills for Care leadership frameworks';
        $sources[] = 'CQC guidance on well-led key question';
        if (stripos($title_lower, 'level 3') !== false) {
            $sources[] = 'Care Quality Commission registered manager requirements';
        }
    }
    
    return array_unique($sources);
}

/**
 * Get target keywords for a course based on title
 */
function ccs_get_target_keywords_for_course($course_title, $category = '') {
    $title_lower = strtolower($course_title);
    
    // Extract course name (remove "Level X", "Training", etc.)
    $course_name = preg_replace('/\b(level\s*\d+|training|course|l\d+|efaw|faw|bls)\b/i', '', $course_title);
    $course_name = trim($course_name);
    
    // Build keywords
    $primary = strtolower($course_name) . ' training kent';
    $secondary = strtolower($course_name) . ' course maidstone';
    $long_tail = strtolower($course_name) . ' for care workers kent';
    
    // Compliance keyword
    if (stripos($title_lower, 'safeguarding') !== false) {
        $compliance = 'cqc safeguarding training';
    } elseif (stripos($title_lower, 'first aid') !== false) {
        $compliance = 'hse approved first aid';
    } elseif (stripos($title_lower, 'medication') !== false) {
        $compliance = 'cqc medication training';
    } elseif (stripos($title_lower, 'fire') !== false) {
        $compliance = 'fire safety training care homes cqc';
    } else {
        $compliance = 'cqc ' . strtolower($course_name) . ' training';
    }
    
    return [
        'primary' => $primary,
        'secondary' => $secondary,
        'long_tail' => $long_tail,
        'compliance' => $compliance,
    ];
}

/**
 * Get course-specific prompt enhancements based on course type
 * From Part 3 of the guide
 */
function ccs_get_course_specific_prompt_enhancements($course_title, $context) {
    $title_lower = strtolower($course_title);
    $enhancements = '';
    
    // Safeguarding-specific
    if (stripos($title_lower, 'safeguarding') !== false) {
        $enhancements = "
**Safeguarding-Specific Requirements:**
- Focus on legal duty under Care Act 2014
- Cover six types of abuse (physical, emotional, sexual, neglect, financial, discriminatory)
- Explain CQC inspection requirements
- Cover reporting procedures (local authority, manager, CQC)
- Link to Mental Capacity Act
- Explain whistleblowing protection
- Emphasize refresher training every 1-2 years
- Tone: Serious but supportive (protecting vulnerable people, not scaremongering)";
    }
    
    // Medication-specific
    if (stripos($title_lower, 'medication') !== false) {
        if (stripos($title_lower, 'awareness') !== false || stripos($title_lower, 'level 2') !== false) {
            $enhancements = "
**Medication Awareness (Level 2) - CRITICAL:**
- Clearly state this is AWARENESS only, NOT administration
- Does NOT qualify you to administer medications
- Does NOT cover MAR charts
- For administration, need Level 3 Competency
- Focus on: understanding medications, safe storage, supporting self-administration
- Tone: Very clear about limitations";
        } else {
            $enhancements = "
**Medication Competency (Level 3) - CRITICAL:**
- This is for people who ADMINISTER medications
- Clearly differentiate from Level 2 Awareness
- Cover the 6 Rs of medication administration
- MAR chart completion training
- Medication error prevention
- CQC inspection focus on medication
- NICE guideline NG5 reference
- Tone: Practical and confidence-building (acknowledge errors happen, focus on prevention)";
        }
    }
    
    // First Aid-specific
    if (stripos($title_lower, 'first aid') !== false || stripos($title_lower, 'efaw') !== false || stripos($title_lower, 'faw') !== false) {
        if (stripos($title_lower, 'emergency') !== false && stripos($title_lower, 'paediatric') === false) {
            $enhancements = "
**Emergency First Aid at Work (EFAW) - Specific:**
- HSE legal requirement for workplace first aiders
- Difference between EFAW (1 day) and FAW (3 days)
- Lower-risk workplaces need EFAW
- Certificate valid 3 years
- HSE-approved qualification
- No written exam (continuous assessment)
- Tone: Empowering - 'you could save a life' not 'you might get sued'";
        } elseif (stripos($title_lower, 'paediatric') !== false || stripos($title_lower, 'child') !== false) {
            $enhancements = "
**Paediatric First Aid - Specific:**
- Different from adult first aid (infant/child CPR differences)
- Ofsted approved (for childcare settings)
- Child development and injury risk
- Common childhood emergencies
- Parents can attend
- Tone: Child-safety focused";
        }
    }
    
    // Moving & Handling-specific
    if (stripos($title_lower, 'moving') !== false || stripos($title_lower, 'handling') !== false) {
        if (stripos($title_lower, 'hoist') !== false || stripos($title_lower, 'practical') !== false) {
            $enhancements = "
**Moving & Handling with Hoist - Specific:**
- Practical hands-on training (not theory only)
- Back injury statistics (BackCare data)
- LOLER regulations for equipment
- Types of hoists and slings
- Safety checks and risk assessment
- Equipment provided in training
- Tone: Safety-focused but confidence-building";
        } else {
            $enhancements = "
**Moving & Handling Theory - Specific:**
- Theory only (no practical)
- Manual Handling Operations Regulations 1992
- When you need practical training too
- Risk assessment principles
- Tone: Clear about limitations (theory only)";
        }
    }
    
    // Care Certificate-specific
    if (stripos($title_lower, 'care certificate') !== false || stripos($title_lower, 'social care certificate') !== false) {
        $enhancements = "
**Adult Social Care Certificate - Specific:**
- Gateway to care sector employment
- All 15 Care Certificate standards
- Difference from NVQ/QCF
- Career entry point
- 4-day intensive format
- Employer requirements
- Tone: Encouraging for career-starters";
    }
    
    // Mental Capacity Act-specific
    if (stripos($title_lower, 'mental capacity') !== false || stripos($title_lower, 'mca') !== false) {
        $enhancements = "
**Mental Capacity Act & DoLS - Specific:**
- Five principles of MCA
- Assessing mental capacity
- Best interests decision-making
- DoLS vs Liberty Protection Safeguards (LPS)
- Complex legislation simplified
- Real scenarios
- Tone: Accessible explanation of complex law";
    }
    
    return $enhancements;
}

/**
 * Get "What to Cover" content requirements for a course
 * Extracted from Part 1 of the guide
 */
function ccs_get_what_to_cover_for_course($course_title, $context) {
    $title_lower = strtolower($course_title);
    $what_to_cover = [];
    
    // Safeguarding
    if (stripos($title_lower, 'safeguarding') !== false) {
        $what_to_cover = [
            'Six types of abuse (physical, emotional, sexual, neglect, financial, discriminatory)',
            'Recognizing signs and indicators of abuse',
            'Legal duty under Care Act 2014',
            'Reporting procedures (local authority, manager, CQC)',
            'Mental Capacity Act links',
            'Whistleblowing protection',
            'CQC inspection requirements',
            'Refresher training requirements (every 1-2 years)',
        ];
    }
    
    // Medication Awareness (Level 2)
    elseif (stripos($title_lower, 'medication') !== false && (stripos($title_lower, 'awareness') !== false || stripos($title_lower, 'level 2') !== false)) {
        $what_to_cover = [
            'What medications are and why people take them',
            'Different types of medicines',
            'Safe storage and handling',
            'Basic recording requirements',
            'When to report concerns',
            'Supporting self-administration',
            '**CRITICAL: This does NOT qualify you to administer medications**',
        ];
    }
    
    // Medication Competency (Level 3)
    elseif (stripos($title_lower, 'medication') !== false && (stripos($title_lower, 'competency') !== false || stripos($title_lower, 'level 3') !== false)) {
        $what_to_cover = [
            'The 6 Rs of medication administration',
            'MAR chart completion and management',
            'Medication error prevention',
            'Safe administration techniques',
            'Storage and handling',
            'CQC inspection focus on medication',
            'NICE guideline NG5 compliance',
        ];
    }
    
    // First Aid - EFAW
    elseif ((stripos($title_lower, 'emergency first aid') !== false || stripos($title_lower, 'efaw') !== false) && stripos($title_lower, 'paediatric') === false) {
        $what_to_cover = [
            'Adult CPR (hands-only and with rescue breaths)',
            'Recovery position',
            'Choking management (back blows, abdominal thrusts)',
            'When to call 999',
            'Use of AED (defibrillator)',
            'Basic wound care',
            'Shock management',
        ];
    }
    
    // First Aid - FAW (3-day)
    elseif (stripos($title_lower, 'first aid at work') !== false && stripos($title_lower, 'emergency') === false) {
        $what_to_cover = [
            'Everything in EFAW plus:',
            'Secondary survey',
            'Spinal injury management',
            'Major vs minor injuries',
            'Detailed wound care',
            'Fractures and dislocations',
            'Medical emergencies (stroke, heart attack, anaphylaxis)',
            'Environmental injuries',
        ];
    }
    
    // Paediatric First Aid
    elseif (stripos($title_lower, 'paediatric') !== false || stripos($title_lower, 'child first aid') !== false) {
        $what_to_cover = [
            'Infant CPR (under 1 year)',
            'Child CPR (1-8 years)',
            'Choking in babies and children',
            'Common childhood injuries',
            'Febrile convulsions',
            'Meningitis recognition',
            'Anaphylaxis in children',
            'Asthma management',
        ];
    }
    
    // Basic Life Support
    elseif (stripos($title_lower, 'basic life support') !== false || stripos($title_lower, 'bls') !== false) {
        $what_to_cover = [
            'Adult CPR (hands-only and rescue breaths)',
            'Child and infant CPR differences',
            'Choking management',
            'Recovery position',
            'When to call 999',
            'Use of AED (defibrillator)',
        ];
    }
    
    // Moving & Handling Theory
    elseif ((stripos($title_lower, 'moving') !== false || stripos($title_lower, 'handling') !== false) && stripos($title_lower, 'hoist') === false && stripos($title_lower, 'practical') === false) {
        $what_to_cover = [
            'Legislation and employer duties',
            'Principles of safe moving',
            'Risk assessment',
            'When to use equipment vs manual handling',
            'Individual capabilities assessment',
            'Anatomy of back injuries',
            '**Theory only - does not include practical training**',
        ];
    }
    
    // Moving & Handling with Hoist
    elseif (stripos($title_lower, 'moving') !== false || stripos($title_lower, 'handling') !== false) {
        $what_to_cover = [
            'Practical moving techniques',
            'Safe use of hoists (different types)',
            'Sling selection and fitting',
            'Equipment safety checks',
            'Risk assessment in practice',
            'Individual moving plans',
            'LOLER regulations',
        ];
    }
    
    // Fire Safety
    elseif (stripos($title_lower, 'fire safety') !== false || stripos($title_lower, 'fire') !== false) {
        $what_to_cover = [
            'Fire triangle (fuel, oxygen, heat)',
            'Fire prevention measures',
            'Evacuation procedures (PEEP - Personal Emergency Evacuation Plans)',
            'Fire extinguisher types and use',
            'Fire drills and testing',
            'CQC fire safety checks',
            'Regulatory Reform (Fire Safety) Order 2005',
        ];
    }
    
    // Infection Prevention and Control
    elseif (stripos($title_lower, 'infection') !== false || stripos($title_lower, 'ipc') !== false) {
        $what_to_cover = [
            'Chain of infection',
            'Standard infection control precautions',
            'Hand hygiene (7 steps)',
            'PPE use (donning and doffing)',
            'Cleaning and decontamination',
            'Outbreak management',
            'COVID-19 lessons and updates',
        ];
    }
    
    // Dementia Awareness
    elseif (stripos($title_lower, 'dementia') !== false) {
        $what_to_cover = [
            'What dementia is (not a normal part of aging)',
            'Different types of dementia (Alzheimer\'s, vascular, Lewy body, frontotemporal)',
            'Symptoms and progression',
            'Communication strategies',
            'Person-centred dementia care',
            'Managing challenging behaviors',
            'Supporting families',
            'Dementia-friendly environments',
        ];
    }
    
    // Diabetes Awareness
    elseif (stripos($title_lower, 'diabetes') !== false && stripos($title_lower, 'insulin') === false) {
        $what_to_cover = [
            'Type 1 vs Type 2 diabetes',
            'Symptoms and diagnosis',
            'Blood sugar monitoring',
            'Hypoglycemia (low blood sugar) - recognition and treatment',
            'Hyperglycemia (high blood sugar)',
            'Diabetic emergencies',
            'Supporting people with diabetes',
            'Diet and lifestyle management',
            'Foot care importance',
        ];
    }
    
    // Mental Capacity Act & DoLS
    elseif (stripos($title_lower, 'mental capacity') !== false || stripos($title_lower, 'mca') !== false || stripos($title_lower, 'dols') !== false) {
        $what_to_cover = [
            'Five principles of MCA',
            'Assessing mental capacity',
            'Best interests decision-making',
            'Advance decisions and LPAs',
            'What is a deprivation of liberty',
            'DoLS vs Liberty Protection Safeguards (LPS)',
            'Application process',
            'MCA and safeguarding links',
        ];
    }
    
    // Care Certificate
    elseif (stripos($title_lower, 'care certificate') !== false || stripos($title_lower, 'social care certificate') !== false) {
        $what_to_cover = [
            'All 15 Care Certificate standards',
            'Difference between Care Certificate and NVQ',
            'Career entry point/progression',
            'Employer requirements',
            'Time commitment (4 days intensive)',
            'Assessment process',
        ];
    }
    
    // Dignity Privacy & Respect
    elseif (stripos($title_lower, 'dignity') !== false || (stripos($title_lower, 'privacy') !== false && stripos($title_lower, 'respect') !== false)) {
        $what_to_cover = [
            'What dignity means in practice',
            'Privacy during personal care',
            'Maintaining respect in difficult situations',
            'Link to person-centred care',
            'CQC inspection questions about dignity',
            'Care Act 2014 wellbeing principle',
        ];
    }
    
    // Person-Centred Care
    elseif (stripos($title_lower, 'person') !== false && stripos($title_lower, 'centred') !== false) {
        $what_to_cover = [
            'What person-centred means',
            'Putting the person at the heart of their care',
            'Choice, control, independence',
            'Care planning around individual needs',
            'Contrast with task-focused care',
            'Care Act 2014 wellbeing principle',
        ];
    }
    
    // Duty of Care
    elseif (stripos($title_lower, 'duty of care') !== false) {
        $what_to_cover = [
            'Legal duty of care definition',
            'Professional boundaries',
            'When duty of care conflicts with service user wishes',
            'Duty of care vs safeguarding',
            'Reporting poor practice',
            'Whistleblowing protection',
        ];
    }
    
    // End of Life Care
    elseif (stripos($title_lower, 'end of life') !== false || stripos($title_lower, 'palliative') !== false) {
        $what_to_cover = [
            'Recognizing end of life',
            'Person-centred end of life planning',
            'Symptom management (pain, breathlessness)',
            'Emotional and spiritual support',
            'Supporting families',
            'After death care',
            'Bereavement support',
        ];
    }
    
    // Epilepsy
    elseif (stripos($title_lower, 'epilepsy') !== false) {
        $what_to_cover = [
            'What epilepsy is',
            'Types of seizures',
            'Seizure triggers',
            'What to do during a seizure',
            'When to call 999',
            'Buccal midazolam (emergency medication)',
            'Recovery and support after seizure',
        ];
    }
    
    // Tissue Viability
    elseif (stripos($title_lower, 'tissue viability') !== false || stripos($title_lower, 'pressure ulcer') !== false) {
        $what_to_cover = [
            'What tissue viability means',
            'Pressure ulcer development',
            'Risk factors (immobility, malnutrition, moisture)',
            'Prevention strategies (repositioning, nutrition, skin care)',
            'Pressure ulcer grading',
            'When to escalate concerns',
            'Wound care basics',
        ];
    }
    
    // Oral Health
    elseif (stripos($title_lower, 'oral health') !== false || stripos($title_lower, 'mouth care') !== false) {
        $what_to_cover = [
            'Importance of oral health',
            'Tooth brushing technique',
            'Denture cleaning and care',
            'Mouth care for dependent adults',
            'Recognizing oral health problems',
            'When to refer to dentist',
            'Special considerations (dementia, stroke, palliative care)',
        ];
    }
    
    // Nutrition and Hydration
    elseif (stripos($title_lower, 'nutrition') !== false || stripos($title_lower, 'hydration') !== false) {
        $what_to_cover = [
            'Importance of nutrition and hydration',
            'Nutritional needs across lifespan',
            'Recognizing malnutrition and dehydration',
            'Special diets (diabetes, dysphagia, cultural/religious)',
            'Supporting people to eat and drink',
            'Monitoring and recording intake',
            'When to escalate concerns',
            'MUST tool (Malnutrition Universal Screening Tool)',
        ];
    }
    
    // Food Safety
    elseif (stripos($title_lower, 'food safety') !== false || stripos($title_lower, 'food hygiene') !== false) {
        $what_to_cover = [
            'Food safety legislation',
            'Hazards (biological, chemical, physical)',
            'Personal hygiene',
            'Cross-contamination prevention',
            'Safe food storage and temperatures',
            'Food allergies and intolerances (Natasha\'s Law)',
            'Cleaning and disinfection',
            'HACCP principles',
        ];
    }
    
    // GDPR
    elseif (stripos($title_lower, 'gdpr') !== false || stripos($title_lower, 'data protection') !== false) {
        $what_to_cover = [
            'What GDPR is and why it matters',
            'Six lawful bases for processing data',
            'Data protection principles',
            'Rights of data subjects',
            'Data breaches - what to do',
            'Consent and confidentiality',
            'Record keeping in care settings',
            'ICO reporting',
        ];
    }
    
    // Equality, Diversity, Inclusion
    elseif (stripos($title_lower, 'equality') !== false || stripos($title_lower, 'diversity') !== false) {
        $what_to_cover = [
            'Equality Act 2010 protected characteristics',
            'Direct and indirect discrimination',
            'Human rights in care settings',
            'Promoting equality and inclusion',
            'Challenging discrimination',
            'Cultural competence',
            'Reasonable adjustments',
        ];
    }
    
    // Positive Behaviour Support
    elseif (stripos($title_lower, 'positive behaviour') !== false || stripos($title_lower, 'challenging behaviour') !== false) {
        $what_to_cover = [
            'Understanding behavior as communication',
            'Triggers and antecedents',
            'Functional behavior assessment',
            'De-escalation techniques',
            'Positive behavior support plans',
            'Avoiding restrictive practices',
            'Person-centred approaches',
        ];
    }
    
    // Management & Leadership
    elseif (stripos($title_lower, 'management') !== false || stripos($title_lower, 'leadership') !== false) {
        if (stripos($title_lower, 'level 3') !== false) {
            $what_to_cover = [
                'Strategic leadership',
                'Managing resources (budgets, staffing)',
                'Quality improvement',
                'Leading change',
                'Performance management',
                'Developing others',
                'CQC registered manager requirements',
                'Business planning',
            ];
        } else {
            $what_to_cover = [
                'Leadership vs management',
                'Leadership styles',
                'Team motivation',
                'Delegation skills',
                'Supervising staff',
                'Managing performance',
                'Communication as a leader',
            ];
        }
    }
    
    // If no specific match, return general structure
    if (empty($what_to_cover)) {
        $what_to_cover = [
            'Core concepts and principles',
            'Legal and regulatory requirements',
            'Practical application in care settings',
            'CQC compliance requirements',
            'Best practice guidelines',
        ];
    }
    
    return $what_to_cover;
}

/**
 * Get FAQ template questions based on course type
 * From Part 2, Section 5 of the guide
 */
function ccs_get_faq_template_for_course($course_title, $context) {
    $title_lower = strtolower($course_title);
    $faq_template = [];
    
    // Mandatory Training Courses
    if (stripos($title_lower, 'safeguarding') !== false || 
        stripos($title_lower, 'fire safety') !== false || 
        stripos($title_lower, 'infection') !== false ||
        stripos($title_lower, 'health and safety') !== false) {
        $faq_template = [
            'How often do I need ' . strtolower($course_title) . ' training?',
            'Is this course CQC compliant?',
            'What happens if I don\'t complete this training?',
            'Who should attend this course?',
            'Can this be delivered onsite for our team?',
            'What certificate will I receive?',
        ];
    }
    // Qualification Courses
    elseif (stripos($title_lower, 'first aid') !== false || 
            (stripos($title_lower, 'medication') !== false && stripos($title_lower, 'competency') !== false)) {
        $faq_template = [
            'How long is the certificate valid?',
            'What\'s the difference between Level 2 and Level 3?',
            'Do I need to pass an exam?',
            'Is this HSE/CQC approved?',
            'Can I administer [medications/first aid] after this course?',
            'What happens if I fail the assessment?',
        ];
    }
    // Awareness Courses
    else {
        $faq_template = [
            'What will I learn on this course?',
            'Is this suitable for care workers?',
            'Do I need any prior knowledge?',
            'How does this help with CQC inspections?',
            'Can this be delivered onsite?',
            'What certificate will I receive?',
        ];
    }
    
    return $faq_template;
}

/**
 * Get word count target based on competition level
 * From Part 2, Section 2 of the guide
 */
function ccs_get_word_count_target($course_title) {
    $title_lower = strtolower($course_title);
    
    // High-competition courses
    if (stripos($title_lower, 'safeguarding') !== false || 
        stripos($title_lower, 'first aid') !== false || 
        stripos($title_lower, 'medication') !== false) {
        return '1200-1500 words';
    }
    
    // Medium-competition
    if (stripos($title_lower, 'dementia') !== false || 
        stripos($title_lower, 'mental capacity') !== false ||
        stripos($title_lower, 'fire safety') !== false) {
        return '900-1200 words';
    }
    
    // Low-competition/specialist
    return '700-900 words';
}

/**
 * Build SEO prompt block with all requirements
 * From Part 2 of the guide
 */
function ccs_build_seo_prompt($course_title, $context, $content_type, $word_count = null) {
    $keywords = ccs_get_target_keywords_for_course($course_title, $context['category'] ?? '');
    $sources = ccs_get_authoritative_sources_for_course($course_title, $context['category'] ?? '');
    $course_specific = ccs_get_course_specific_prompt_enhancements($course_title, $context);
    
    $seo_block = "
**SEO REQUIREMENTS (CRITICAL):**

**Target Keywords:**
- Primary: {$keywords['primary']}
- Secondary: {$keywords['secondary']}
- Long-tail: {$keywords['long_tail']}
- Compliance: {$keywords['compliance']}

**First 100 Words Structure (REQUIRED):**
- Include target keyword '{$keywords['primary']}' in FIRST SENTENCE
- Mention course level and duration in first paragraph
- Include location (Kent/Maidstone) in first paragraph
- Mention CQC/compliance (where relevant) in first 100 words
- State main benefit/outcome
- Use British English only
- Write for care workers, not HR managers
- Avoid corporate jargon and passive voice
- Use practical examples

**Authoritative Sources to Reference (UK ONLY):**
" . implode("\n", array_map(function($s) { return "- " . $s; }, $sources)) . "

**Brand Voice Guidelines:**
✓ DO: Direct and conversational, 'You' and 'your' frequently, Clear benefit statements, Real-world scenarios, 'We' not 'the organization', Active voice, Short punchy sentences
✗ DON'T: Corporate speak, Overly formal language, Third person ('the learner will...'), Passive constructions, Marketing fluff, Unnecessarily complex words, Long winding sentences

**Tone Examples:**
✓ GOOD: 'This course ensures you know what to do when you're worried about someone. CQC inspectors will ask if all your staff have had safeguarding training - this course gives you the answer they're looking for.'
✗ BAD: 'This course facilitates the acquisition of competencies in safeguarding protocols and regulatory compliance frameworks. Participants will be enabled to demonstrate adherence to statutory requirements.'

**Quality Checks:**
- Paragraphs under 4 lines
- Subheading every 200-300 words
- Bullet points or tables for scanability
- No jargon without explanation
- Sounds natural when read aloud
- Keyword density 1.5-3%
- Location mentions (Kent/Maidstone) 3-4 times
{$course_specific}";
    
    if ($word_count) {
        $seo_block .= "\n**Word Count Target:** {$word_count}";
    }
    
    return $seo_block;
}

/**
 * Call AI API for field generation with character limit handling
 */
function ccs_call_ai_api_for_field($api_key, $provider, $system_prompt, $user_prompt, $field_type, $max_chars = null) {
    // Ignore the single-provider selection at callsites and always try Groq first,
    // then fall back to preferred provider, then remaining configured providers.
    $preferred_provider = get_option('ccs_ai_provider', 'groq');
    $result = ccs_ai_try_providers($preferred_provider, function($p, $key) use ($system_prompt, $user_prompt) {
        if ($p === 'groq' && function_exists('ccs_call_groq_api')) {
            return ccs_call_groq_api($key, $system_prompt, $user_prompt);
        }
        if ($p === 'anthropic' && function_exists('ccs_call_anthropic_api')) {
            return ccs_call_anthropic_api($key, $system_prompt, $user_prompt);
        }
        if ($p === 'openai' && function_exists('ccs_call_openai_api')) {
            return ccs_call_openai_api($key, $system_prompt, $user_prompt);
        }
        return new WP_Error('api_error', 'Provider not available');
    });
    
    if (is_wp_error($result)) {
        return $result;
    }
    
    if (!$result) {
        return new WP_Error('api_error', 'No response from AI API');
    }
    
    // Clean result
    $content = trim(strip_tags($result));
    $content = preg_replace('/^["\']|["\']$/', '', $content); // Remove quotes if AI added them
    
    // Apply character limit if specified
    if ($max_chars && strlen($content) > $max_chars) {
        $content = substr($content, 0, $max_chars - 3) . '...';
    }
    
    return $content;
}

/**
 * AJAX handler for generating course description
 */
function ccs_generate_course_description_ajax() {
    check_ajax_referer('ccs_ai_course', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'course') {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    // Check if AI is configured (Groq first, then fallbacks if keys are set)
    $provider = get_option('ccs_ai_provider', 'groq');
    if (empty(ccs_ai_get_attemptable_providers($provider))) {
        wp_send_json_error(['message' => 'AI API keys not configured. Go to Settings → AI Assistant and add your keys.']);
    }
    
    // Get context
    $context = ccs_get_ai_context_for_field('course_description', $post_id);
    
    // Build context-aware prompt
    $system_prompt = "You are an expert content writer for Continuity of Care Services, a care sector training provider in Kent, UK. Write comprehensive, engaging course descriptions that help UK care sector professionals understand what they will learn. Use British English only.";
    
    $prompt = "Write a comprehensive course description for: {$context['title']}
" . ($context['level'] ? "Level: {$context['level']}\n" : "") . 
($context['duration'] ? "Duration: {$context['duration']}\n" : "") . 
($context['accreditation'] && strtolower(trim($context['accreditation'])) !== 'none' ? "Accreditation: {$context['accreditation']}\n" : "") . 
($context['category'] ? "Category: {$context['category']}\n" : "") . 
($context['suitable_for'] ? "Target Audience: {$context['suitable_for']}\n" : "") . 
($context['outcomes'] && is_array($context['outcomes']) ? "Learning Outcomes:\n" . implode("\n", array_map(function($o) { return is_array($o) ? ($o['outcome_text'] ?? '') : $o; }, $context['outcomes'])) . "\n" : "") . "

Requirements:
- Comprehensive description (no character limit, but aim for 200-400 words)
- Include: what the course covers, who it's for, learning outcomes, accreditation details
- Target: UK care sector professionals
- Tone: professional and informative
- Location: Mention Maidstone, Kent if relevant
- Use British English only
- Format: Well-structured paragraphs, easy to read

Write only the description text, nothing else.";
    
    $description = ccs_call_ai_api_for_field('', $provider, $system_prompt, $prompt, 'textarea');
    
    if (is_wp_error($description)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $description->get_error_message()]);
    }
    
    wp_send_json_success(['description' => $description]);
}
add_action('wp_ajax_ccs_generate_course_description', 'ccs_generate_course_description_ajax');

/**
 * AJAX handler for generating course SEO meta description
 */
function ccs_generate_course_meta_description_ajax() {
    check_ajax_referer('ccs_ai_course', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'course') {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    // Check if AI is configured (Groq first, then fallbacks if keys are set)
    $provider = get_option('ccs_ai_provider', 'groq');
    if (empty(ccs_ai_get_attemptable_providers($provider))) {
        wp_send_json_error(['message' => 'AI API keys not configured. Go to Settings → AI Assistant and add your keys.']);
    }
    
    // Get context
    $context = ccs_get_ai_context_for_field('course_seo_meta_description', $post_id);
    
    // Build context-aware prompt
    $system_prompt = "You are an expert SEO copywriter for Continuity of Care Services, a care sector training provider in Kent, UK. Write compelling, keyword-rich meta descriptions optimized for search engines. Use British English only.";
    
    $prompt = "Write a 150-160 character SEO meta description for: {$context['title']}
" . ($context['level'] ? "Level: {$context['level']}\n" : "") . 
($context['duration'] ? "Duration: {$context['duration']}\n" : "") . 
($context['accreditation'] && strtolower(trim($context['accreditation'])) !== 'none' ? "Accreditation: {$context['accreditation']}\n" : "") . 
($context['category'] ? "Category: {$context['category']}\n" : "") . "

Requirements:
- Exactly 150-160 characters
- Include: course name, location (Maidstone, Kent), key benefit, accreditation if relevant
- Target: UK care sector professionals searching for training
- Format: Compelling, keyword-rich, action-oriented
- Use British English only
- No quotes, no prefixes, just the meta description text

Write only the meta description, nothing else.";
    
    $meta_description = ccs_call_ai_api_for_field('', $provider, $system_prompt, $prompt, 'textarea', 160);
    
    if (is_wp_error($meta_description)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $meta_description->get_error_message()]);
    }
    
    wp_send_json_success(['meta_description' => $meta_description]);
}
add_action('wp_ajax_ccs_generate_course_meta_description', 'ccs_generate_course_meta_description_ajax');

/**
 * AJAX handler for generating event SEO meta description
 */
function ccs_generate_event_meta_description_ajax() {
    check_ajax_referer('ccs_ai_course', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'course_event') {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    // Check if AI is configured (Groq first, then fallbacks if keys are set)
    $provider = get_option('ccs_ai_provider', 'groq');
    if (empty(ccs_ai_get_attemptable_providers($provider))) {
        wp_send_json_error(['message' => 'AI API keys not configured. Go to Settings → AI Assistant and add your keys.']);
    }
    
    // Get context
    $context = ccs_get_ai_context_for_field('event_seo_meta_description', $post_id);
    
    $event_title = $context['event_title'];
    $course_title = $context['course'] ? $context['course']['title'] : $event_title;
    $formatted_date = $context['event_date'] ? date('j M Y', strtotime($context['event_date'])) : '';
    $location = $context['location'] ?: 'Maidstone, Kent';
    $duration = $context['course'] ? $context['course']['duration'] : '';
    
    // Build context-aware prompt
    $system_prompt = "You are an expert SEO copywriter for Continuity of Care Services, a care sector training provider in Kent, UK. Write compelling, date-specific meta descriptions optimized for search engines. Use British English only.";
    
    $prompt = "Write a 150-160 character SEO meta description for an event.

Event: {$event_title}
Course: {$course_title}
" . ($formatted_date ? "Date: {$formatted_date}\n" : "") . 
"Location: {$location}
" . ($duration ? "Duration: {$duration}\n" : "") . 
($context['price'] ? "Price: £" . number_format($context['price'], 0) . "\n" : "") . "

Requirements:
- Exactly 150-160 characters
- Include: course name, date, location (Maidstone, Kent), booking CTA
- Target: UK care sector professionals searching for training courses
- Format: Compelling, date-specific, action-oriented
- Use British English only
- No quotes, no prefixes, just the meta description text

Write only the meta description, nothing else.";
    
    $meta_description = ccs_call_ai_api_for_field('', $provider, $system_prompt, $prompt, 'textarea', 160);
    
    if (is_wp_error($meta_description)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $meta_description->get_error_message()]);
    }
    
    wp_send_json_success(['meta_description' => $meta_description]);
}
add_action('wp_ajax_ccs_generate_event_meta_description', 'ccs_generate_event_meta_description_ajax');

/**
 * AJAX handler for generating course intro paragraph
 */
function ccs_generate_course_intro_ajax() {
    check_ajax_referer('ccs_ai_course', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'course') {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    $provider = get_option('ccs_ai_provider', 'groq');
    if (empty(ccs_ai_get_attemptable_providers($provider))) {
        wp_send_json_error(['message' => 'AI API keys not configured. Go to Settings → AI Assistant and add your keys.']);
    }
    
    $context = ccs_get_ai_context_for_field('course_intro_paragraph', $post_id);
    $course_title = $context['title'];
    
    $system_prompt = "You are an expert SEO content writer for Continuity of Care Services, a care sector training provider in Kent, UK. You write engaging, SEO-optimized content that ranks well in search engines while being genuinely helpful to care workers. You ONLY reference UK authoritative sources (CQC, HSE, NICE, Department of Health, Skills for Care, SCIE). Use British English only. Never make up statistics or claim compliance without verification. Write in a direct, conversational tone (not corporate).";
    
    $seo_prompt = ccs_build_seo_prompt($course_title, $context, 'intro', '100-120 words');
    $what_to_cover = ccs_get_what_to_cover_for_course($course_title, $context);
    
    $what_to_cover_text = !empty($what_to_cover) ? "\n**What This Course Covers (must be mentioned):**\n" . implode("\n", array_map(function($item) { return "- " . $item; }, $what_to_cover)) . "\n" : '';
    
    $prompt = "Write an engaging opening paragraph (100-120 words) for: {$course_title}
" . ($context['level'] ? "Level: {$context['level']}\n" : "") . 
($context['duration'] ? "Duration: {$context['duration']}\n" : "") . 
($context['accreditation'] && strtolower(trim($context['accreditation'])) !== 'none' ? "Accreditation: {$context['accreditation']}\n" : "") . 
($context['category'] ? "Category: {$context['category']}\n" : "") . 
($context['outcomes'] && is_array($context['outcomes']) ? "Key Learning Points:\n" . implode("\n", array_slice(array_map(function($o) { return is_array($o) ? ($o['outcome_text'] ?? '') : $o; }, $context['outcomes']), 0, 3)) . "\n" : "") . "

{$seo_prompt}
{$what_to_cover_text}

Write only the paragraph text, nothing else. Do not include quotes or prefixes.";
    
    $intro = ccs_call_ai_api_for_field('', $provider, $system_prompt, $prompt, 'wysiwyg');
    
    if (is_wp_error($intro)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $intro->get_error_message()]);
    }
    
    wp_send_json_success(['intro' => $intro]);
}
add_action('wp_ajax_ccs_generate_course_intro', 'ccs_generate_course_intro_ajax');

/**
 * AJAX handler for generating "Why This Course Matters" content
 */
function ccs_generate_course_why_matters_ajax() {
    check_ajax_referer('ccs_ai_course', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'course') {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    $provider = get_option('ccs_ai_provider', 'groq');
    if (empty(ccs_ai_get_attemptable_providers($provider))) {
        wp_send_json_error(['message' => 'AI API keys not configured. Go to Settings → AI Assistant and add your keys.']);
    }
    
    $context = ccs_get_ai_context_for_field('course_why_matters', $post_id);
    $course_title = $context['title'];
    
    $system_prompt = "You are an expert SEO content writer for Continuity of Care Services, a care sector training provider in Kent, UK. You write compelling content explaining why training is important, focusing on CQC compliance, legal requirements, and professional development. You ONLY reference UK authoritative sources (CQC, HSE, NICE, Department of Health, Skills for Care, SCIE). Use British English only. Never make up statistics or claim compliance without verification. Write in a direct, conversational tone (not corporate).";
    
    $seo_prompt = ccs_build_seo_prompt($course_title, $context, 'why_matters', '150-180 words');
    $what_to_cover = ccs_get_what_to_cover_for_course($course_title, $context);
    
    $what_to_cover_text = !empty($what_to_cover) ? "\n**What This Course Covers (context for why it matters):**\n" . implode("\n", array_map(function($item) { return "- " . $item; }, array_slice($what_to_cover, 0, 5))) . "\n" : '';
    
    $prompt = "Write a compelling 'Why This Course Matters' section (150-180 words) for: {$course_title}
" . ($context['level'] ? "Level: {$context['level']}\n" : "") . 
($context['category'] ? "Category: {$context['category']}\n" : "") . 
($context['accreditation'] && strtolower(trim($context['accreditation'])) !== 'none' ? "Accreditation: {$context['accreditation']}\n" : "") . "

{$seo_prompt}
{$what_to_cover_text}

**Content Requirements:**
- Explain CQC compliance requirements and legal context
- Highlight why this training is essential for care professionals
- Mention consequences of not having proper training
- Emphasize professional development and career benefits
- Reference the key topics covered (from list above)
- Tone: Authoritative but supportive (not scaremongering)
- Format: Well-structured paragraphs

Write only the content, nothing else.";
    
    $content = ccs_call_ai_api_for_field('', $provider, $system_prompt, $prompt, 'wysiwyg');
    
    if (is_wp_error($content)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $content->get_error_message()]);
    }
    
    wp_send_json_success(['content' => $content]);
}
add_action('wp_ajax_ccs_generate_course_why_matters', 'ccs_generate_course_why_matters_ajax');

/**
 * AJAX handler for generating course FAQs
 */
function ccs_generate_course_faqs_ajax() {
    check_ajax_referer('ccs_ai_course', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'course') {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    $provider = get_option('ccs_ai_provider', 'groq');
    if (empty(ccs_ai_get_attemptable_providers($provider))) {
        wp_send_json_error(['message' => 'AI API keys not configured. Go to Settings → AI Assistant and add your keys.']);
    }
    
    $context = ccs_get_ai_context_for_field('course_faqs', $post_id);
    $course_title = $context['title'];
    
    $system_prompt = "You are an expert SEO content writer for Continuity of Care Services, a care sector training provider in Kent, UK. Write helpful, SEO-optimized FAQs that answer common questions about training courses. You ONLY reference UK authoritative sources (CQC, HSE, NICE, Department of Health, Skills for Care, SCIE). Use British English only. Never make up statistics or claim compliance without verification. Write in a direct, conversational tone (not corporate).";
    
    $keywords = ccs_get_target_keywords_for_course($course_title, $context['category'] ?? '');
    $sources = ccs_get_authoritative_sources_for_course($course_title, $context['category'] ?? '');
    $faq_template = ccs_get_faq_template_for_course($course_title, $context);
    $course_specific = ccs_get_course_specific_prompt_enhancements($course_title, $context);
    $what_to_cover = ccs_get_what_to_cover_for_course($course_title, $context);
    
    $what_to_cover_text = !empty($what_to_cover) ? "\n**What This Course Covers (use to inform FAQ answers):**\n" . implode("\n", array_map(function($item) { return "- " . $item; }, $what_to_cover)) . "\n" : '';
    
    $prompt = "Generate 6-8 SEO-optimized FAQs for: {$course_title}
" . ($context['level'] ? "Level: {$context['level']}\n" : "") . 
($context['duration'] ? "Duration: {$context['duration']}\n" : "") . 
($context['accreditation'] && strtolower(trim($context['accreditation'])) !== 'none' ? "Accreditation: {$context['accreditation']}\n" : "") . 
($context['category'] ? "Category: {$context['category']}\n" : "") . 
($context['price'] ? "Price: £" . number_format($context['price'], 0) . "\n" : "") . 
($context['suitable_for'] ? "Target Audience: {$context['suitable_for']}\n" : "") . "

**Target Keywords to Include Naturally:**
- Primary: {$keywords['primary']}
- Secondary: {$keywords['secondary']}
- Long-tail: {$keywords['long_tail']}

**Authoritative Sources to Reference (UK ONLY):**
" . implode("\n", array_map(function($s) { return "- " . $s; }, $sources)) . "

**FAQ Template Questions (Use as inspiration, adapt naturally):**
" . implode("\n", array_map(function($q) { return "- " . $q; }, $faq_template)) . "
{$what_to_cover_text}
{$course_specific}

**Answer Quality Requirements:**
- Answers should be comprehensive (100-200 words each)
- Include specific details (certificate validity, CQC requirements, etc.)
- Reference authoritative sources where relevant
- Use natural language (not FAQ spam)
- Include location mentions (Kent/Maidstone) where relevant
- Tone: Helpful and informative, not salesy

**Output Format (JSON only, no other text):**
[
  {\"question\": \"Question text here\", \"answer\": \"Answer text here\"},
  {\"question\": \"Question text here\", \"answer\": \"Answer text here\"}
]";
    
    $result = ccs_call_ai_api_for_field('', $provider, $system_prompt, $prompt, 'textarea');
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $result->get_error_message()]);
    }
    
    // Parse JSON response
    $faqs = json_decode($result, true);
    if (!is_array($faqs)) {
        // Try to extract JSON from response if wrapped in text
        preg_match('/\[.*\]/s', $result, $matches);
        if (!empty($matches[0])) {
            $faqs = json_decode($matches[0], true);
        }
    }
    
    if (!is_array($faqs) || empty($faqs)) {
        wp_send_json_error(['message' => 'Failed to parse FAQ data. Please try again.']);
    }
    
    // Clean and validate FAQs
    $valid_faqs = [];
    foreach ($faqs as $faq) {
        if (isset($faq['question']) && isset($faq['answer']) && !empty($faq['question']) && !empty($faq['answer'])) {
            $valid_faqs[] = [
                'question' => sanitize_text_field($faq['question']),
                'answer' => wp_kses_post($faq['answer']),
            ];
        }
    }
    
    if (empty($valid_faqs)) {
        wp_send_json_error(['message' => 'No valid FAQs generated. Please try again.']);
    }
    
    wp_send_json_success(['faqs' => $valid_faqs]);
}
add_action('wp_ajax_ccs_generate_course_faqs', 'ccs_generate_course_faqs_ajax');

/**
 * Enqueue universal AI assistant script
 */
function ccs_enqueue_universal_ai_assistant($hook) {
    // Only load in admin
    if (!is_admin()) {
        return;
    }
    
    // Enqueue script
    wp_enqueue_script(
        'cta-universal-ai-assistant',
        get_template_directory_uri() . '/assets/js/universal-ai-assistant.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/universal-ai-assistant.js'),
        true
    );
    
    // Localize script
    wp_localize_script('cta-universal-ai-assistant', 'ctaUniversalAI', [
        'nonce' => wp_create_nonce('ccs_universal_ai'),
        'ajaxurl' => admin_url('admin-ajax.php')
    ]);
    
    // Ensure ajaxurl is available globally (WordPress doesn't always provide it in admin)
    wp_add_inline_script('cta-universal-ai-assistant', 'var ajaxurl = ajaxurl || "' . admin_url('admin-ajax.php') . '";', 'before');
}
add_action('admin_enqueue_scripts', 'ccs_enqueue_universal_ai_assistant');

/**
 * Generic AJAX handler for field content generation
 */
function ccs_generate_field_content_ajax() {
    check_ajax_referer('ccs_universal_ai', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $field_id = sanitize_text_field($_POST['field_id'] ?? '');
    $field_type = sanitize_text_field($_POST['field_type'] ?? 'textarea');
    $field_name = sanitize_text_field($_POST['field_name'] ?? '');
    $field_label = sanitize_text_field($_POST['field_label'] ?? '');
    $page_context = sanitize_text_field($_POST['page_context'] ?? '');
    $current_value = wp_kses_post($_POST['current_value'] ?? '');
    
    // Check if AI is configured
    $provider = get_option('ccs_ai_provider', 'groq');
    if (empty(ccs_ai_get_attemptable_providers($provider))) {
        wp_send_json_error(['message' => 'AI API keys not configured. Go to Settings → AI Assistant and add your keys.']);
    }
    
    // Build context-aware prompt based on field context
    $context = ccs_build_universal_ai_context($field_id, $field_name, $field_label, $page_context, $current_value);
    
    // Call AI API
    $result = ccs_call_ai_api_for_field('', $provider, $context['system_prompt'], $context['user_prompt'], $field_type);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'AI generation failed: ' . $result->get_error_message()]);
    }
    
    wp_send_json_success(['content' => $result]);
}
add_action('wp_ajax_ccs_generate_field_content', 'ccs_generate_field_content_ajax');

/**
 * Build context for universal AI generation
 */
function ccs_build_universal_ai_context($field_id, $field_name, $field_label, $page_context, $current_value) {
    $system_prompt = "You are an expert content writer for Continuity of Care Services, a care sector training provider in Kent, UK. Write clear, professional, and engaging content. Use British English only.";
    
    $user_prompt = '';
    
    // Detect field type from name/label
    $field_lower = strtolower($field_label . ' ' . $field_name);
    
    if (strpos($field_lower, 'meta description') !== false || strpos($field_lower, 'seo') !== false) {
        // SEO meta description
        $system_prompt = "You are an expert SEO copywriter for Continuity of Care Services, a care sector training provider in Kent, UK. Write compelling, keyword-rich meta descriptions optimized for search engines (150-160 characters). Use British English only.";
        $user_prompt = "Write a 150-160 character SEO meta description for " . ($field_label ?: 'this page') . ". Include: key information, location (Maidstone, Kent if relevant), and a call to action. Be concise and compelling.";
    } elseif (strpos($field_lower, 'description') !== false) {
        // General description
        $user_prompt = "Write a comprehensive description for " . ($field_label ?: 'this content') . ". Include: what it covers, who it's for, key benefits. Target: UK care sector professionals. Location: Mention Maidstone, Kent if relevant. Aim for 200-400 words.";
    } elseif (strpos($field_lower, 'excerpt') !== false) {
        // Excerpt
        $user_prompt = "Write a brief excerpt (2-3 sentences, ~30 words) for " . ($field_label ?: 'this content') . ". Make it engaging and informative.";
    } else {
        // Generic content
        $user_prompt = "Write content for " . ($field_label ?: 'this field') . ". Make it clear, professional, and relevant to UK care sector training. Use British English only.";
    }
    
    // Add current value as context if provided
    if (!empty($current_value)) {
        $user_prompt .= "\n\nCurrent content (for reference): " . wp_strip_all_tags($current_value);
    }
    
    return [
        'system_prompt' => $system_prompt,
        'user_prompt' => $user_prompt
    ];
}

/**
 * Add JavaScript for AI generation buttons in course and event editors
 */
function ccs_course_ai_assistant_script($hook) {
    global $post;
    
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    
    if (!$post || !in_array($post->post_type, ['course', 'course_event'])) {
        return;
    }
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Generate Course Description with AI
        $('#cta-generate-course-description').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-description-status');
            var $descriptionField = $('#acf-field_course_description');
            
            if (!$descriptionField.length) {
                $status.html('<span style="color: #d63638;">✗ Field not found</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_course_description',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_ai_course'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.description) {
                        $descriptionField.val(response.data.description);
                        $descriptionField.trigger('change');
                        $status.html('<span style="color: #00a32a;">✓ Generated</span>');
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + (response.data && response.data.message ? response.data.message : 'Generation failed') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('✨ Generate with AI');
                }
            });
        });
        
        // Generate Course Meta Description with AI
        $('#cta-generate-course-meta-description').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-meta-description-status');
            var $metaField = $('#acf-field_course_seo_meta_description');
            
            if (!$metaField.length) {
                $status.html('<span style="color: #d63638;">✗ Field not found</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_course_meta_description',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_ai_course'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.meta_description) {
                        $metaField.val(response.data.meta_description);
                        $metaField.trigger('input'); // Trigger counter update if exists
                        $status.html('<span style="color: #00a32a;">✓ Generated</span>');
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + (response.data && response.data.message ? response.data.message : 'Generation failed') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('✨ Generate with AI');
                }
            });
        });
        
        // Generate Event Meta Description with AI
        $('#cta-generate-event-meta-description').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-event-meta-description-status');
            var $metaField = $('#acf-field_event_seo_meta_description');
            
            if (!$metaField.length) {
                $status.html('<span style="color: #d63638;">✗ Field not found</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_event_meta_description',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_ai_course'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.meta_description) {
                        $metaField.val(response.data.meta_description);
                        $metaField.trigger('input'); // Trigger counter update if exists
                        $status.html('<span style="color: #00a32a;">✓ Generated</span>');
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + (response.data && response.data.message ? response.data.message : 'Generation failed') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('✨ Generate with AI');
                }
            });
        });
        
        // Generate Course Intro Paragraph with AI
        $('#cta-generate-course-intro').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-intro-status');
            var $introField = $('#acf-field_course_intro_paragraph');
            
            if (!$introField.length) {
                $status.html('<span style="color: #d63638;">✗ Field not found</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_course_intro',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_ai_course'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.intro) {
                        if (typeof acf !== 'undefined' && acf.getField) {
                            var field = acf.getField($introField);
                            if (field && field.val) {
                                field.val(response.data.intro);
                            } else {
                                $introField.find('textarea').val(response.data.intro).trigger('input');
                            }
                        } else {
                            $introField.find('textarea').val(response.data.intro).trigger('input');
                        }
                        $status.html('<span style="color: #00a32a;">✓ Generated</span>');
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + (response.data && response.data.message ? response.data.message : 'Generation failed') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('✨ Generate with AI');
                }
            });
        });
        
        // Generate "Why This Course Matters" with AI
        $('#cta-generate-course-why-matters').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-why-matters-status');
            var $whyField = $('#acf-field_course_why_matters');
            
            if (!$whyField.length) {
                $status.html('<span style="color: #d63638;">✗ Field not found</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_course_why_matters',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_ai_course'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.content) {
                        if (typeof acf !== 'undefined' && acf.getField) {
                            var field = acf.getField($whyField);
                            if (field && field.val) {
                                field.val(response.data.content);
                            } else {
                                $whyField.find('textarea').val(response.data.content).trigger('input');
                            }
                        } else {
                            $whyField.find('textarea').val(response.data.content).trigger('input');
                        }
                        $status.html('<span style="color: #00a32a;">✓ Generated</span>');
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + (response.data && response.data.message ? response.data.message : 'Generation failed') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('✨ Generate with AI');
                }
            });
        });
        
        // Generate Course FAQs with AI
        $('#cta-generate-course-faqs').on('click', function() {
            var $button = $(this);
            var $status = $('#cta-generate-faqs-status');
            var $faqsRepeater = $('#acf-field_course_faqs');
            
            if (!$faqsRepeater.length) {
                $status.html('<span style="color: #d63638;">✗ Field not found</span>');
                return;
            }
            
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccs_generate_course_faqs',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('ccs_ai_course'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.faqs && Array.isArray(response.data.faqs)) {
                        if (typeof acf !== 'undefined') {
                            var repeaterField = acf.getField($faqsRepeater);
                            if (repeaterField) {
                                // Add each FAQ
                                response.data.faqs.forEach(function(faq) {
                                    repeaterField.add();
                                    var rows = repeaterField.$el.find('.acf-row:not(.acf-clone)');
                                    var lastRow = rows.last();
                                    
                                    // Set question
                                    var questionField = acf.getField(lastRow.find('[data-name="question"]'));
                                    if (questionField) {
                                        questionField.val(faq.question);
                                    } else {
                                        lastRow.find('[data-name="question"] input').val(faq.question);
                                    }
                                    
                                    // Set answer
                                    var answerField = acf.getField(lastRow.find('[data-name="answer"]'));
                                    if (answerField) {
                                        answerField.val(faq.answer);
                                    } else {
                                        var answerTextarea = lastRow.find('[data-name="answer"] textarea');
                                        if (answerTextarea.length) {
                                            answerTextarea.val(faq.answer);
                                            // Trigger WYSIWYG update if TinyMCE
                                            if (typeof tinymce !== 'undefined') {
                                                var editorId = answerTextarea.attr('id');
                                                if (editorId && tinymce.get(editorId)) {
                                                    tinymce.get(editorId).setContent(faq.answer);
                                                }
                                            }
                                        }
                                    }
                                });
                                
                                $status.html('<span style="color: #00a32a;">✓ Generated ' + response.data.faqs.length + ' FAQs</span>');
                            } else {
                                $status.html('<span style="color: #d63638;">✗ Could not access repeater field</span>');
                            }
                        } else {
                            $status.html('<span style="color: #d63638;">✗ ACF not loaded</span>');
                        }
                    } else {
                        $status.html('<span style="color: #d63638;">✗ ' + (response.data && response.data.message ? response.data.message : 'Generation failed') + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #d63638;">✗ Generation failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('✨ Generate FAQs with AI');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'ccs_course_ai_assistant_script');
