<?php

/**
 * Multi-Prompt Assessment - Detailed Narratives
 * 
 * This script generates comprehensive narratives for each assessment aspect
 * based on the test case of a 65-year-old male with acute chest pain.
 */

$aspectNarratives = [
    'history' => [
        'systematic_approach' => [
            'title' => 'Systematic Approach to History-Taking',
            'narrative' => '
The student demonstrated an excellent systematic approach to history-taking, following a logical and professional sequence that mirrors clinical best practices. 

Beginning with an open-ended question ("What brings you in today?"), the student established the presenting complaint before systematically exploring the pain\'s characteristics. The questioning flow was particularly impressive:
- Location and radiation: "Where exactly is it and does it go anywhere else?"
- Associated symptoms: "Are you having any other symptoms?"
- Risk factors: Medical history and social factors

This approach shows the student understands the importance of not just collecting information, but organizing it in a clinically meaningful way. The systematic nature of the questioning suggests the student was actively forming differential diagnoses while gathering history.

Areas for minimal improvement include explicitly asking about family history of cardiac disease and exploring social history more thoroughly. However, the overall structure was excellent, demonstrating clinical maturity beyond what would be expected at this level.
            ',
            'key_evidence' => [
                'Opened with broad, open-ended question',
                'Followed logical sequence: complaint → characteristics → associated symptoms → risk factors',
                'Used appropriate follow-up questions based on patient responses',
                'Covered all major history domains'
            ],
            'clinical_relevance' => 'This systematic approach is crucial in emergency settings like chest pain, where missing key elements could lead to missed diagnoses. The student\'s method suggests they understand the importance of comprehensive history in life-threatening situations.'
        ],
        
        'question_quality' => [
            'title' => 'Question Quality and Technique',
            'narrative' => '
The student exhibited superior questioning technique, demonstrating an understanding of how to elicit maximum information while maintaining patient comfort and rapport.

The use of open-ended questions was particularly noteworthy. Starting with "What brings you in today?" allowed the patient to tell their story in their own words, often revealing information the clinician might not specifically ask about. The student then appropriately narrowed to more focused questions when specific details were needed.

The questions were clear, concise, and free of medical jargon, making them easily understandable for the patient. There was a good balance between open-ended questions ("Can you tell me more about the pain?") and specific closed questions when appropriate.

The student showed skill in avoiding leading questions that might influence the patient\'s responses. Instead, they allowed the patient to describe symptoms freely, then probed for specific details. This technique is particularly important in chest pain evaluation, where patients may not volunteer crucial information unless specifically asked.
            ',
            'key_evidence' => [
                'Effective use of open-ended questioning technique',
                'Clear, jargon-free language',
                'Appropriate transition from broad to specific questions',
                'No leading questions detected'
            ],
            'clinical_relevance' => 'Good questioning technique is fundamental to accurate diagnosis. In chest pain particularly, the way questions are asked can reveal crucial diagnostic clues that patients might not volunteer spontaneously.'
        ],
        
        'thoroughness' => [
            'title' => 'Thoroughness in History Coverage',
            'narrative' => '
The student demonstrated impressive thoroughness in history-taking, covering nearly all essential aspects of a comprehensive cardiac history. This level of detail is particularly important in a high-risk presentation like chest pain.

Key areas well-covered included:
- Pain characteristics: sudden onset, central location, radiation to left arm, squeezing quality
- Associated symptoms: dyspnea and diaphoresis - both red flags for cardiac ischemia
- Risk factors: hypertension and 30-pack-year smoking history
- Relevant negatives: no diabetes (important differential consideration)

The only significant omission was family history of cardiac disease, which could be relevant given the presentation. However, the student compensated by thoroughly exploring other risk factors and symptoms.

This thoroughness suggests the student understands that chest pain requires comprehensive evaluation, and that missing key historical elements could lead to inappropriate management. The attention to detail in symptom characterization particularly shows clinical maturity.
            ',
            'key_evidence' => [
                'Comprehensive pain characterization (onset, location, quality, radiation)',
                'Complete review of associated symptoms',
                'Thorough risk factor assessment',
                'Appropriate exploration of relevant negatives'
            ],
            'clinical_relevance' => 'In chest pain, thorough history-taking can be life-saving. The student\'s attention to detail suggests they understand the high-stakes nature of this presentation and the importance of comprehensive evaluation.'
        ]
    ],
    
    'exam' => [
        'technique' => [
            'title' => 'Physical Examination Technique',
            'narrative' => '
The student demonstrated good physical examination technique, performing all components of the cardiovascular exam in a systematic and technically sound manner. The examination sequence followed standard clinical practice, beginning with general inspection before moving to specific systems.

Notable technical strengths included:
- Proper positioning considerations (implied by systematic approach)
- Correct sequence: inspection → pulse → blood pressure → auscultation
- Appropriate examination of relevant systems (added respiratory auscultation)

The student showed understanding that technique matters not just for accuracy, but also for patient comfort and professionalism. Each examination was documented with clear findings, suggesting careful attention to what was being observed.

Areas for minor improvement include more explicit attention to patient positioning and comfort during examinations. However, the overall technical proficiency was excellent and shows the student has mastered the fundamentals of physical examination.
            ',
            'key_evidence' => [
                'Systematic examination sequence',
                'Clear documentation of findings',
                'Appropriate addition of respiratory examination',
                'Technically sound vital sign assessment'
            ],
            'clinical_relevance' => 'Proper examination technique is essential for accurate diagnosis. In cardiovascular emergencies, small technical errors can miss crucial findings. The student\'s technique suggests reliability in high-pressure situations.'
        ],
        
        'systematic_approach' => [
            'title' => 'Systematic Examination Approach',
            'narrative' => '
The student\'s systematic approach to physical examination was exemplary, demonstrating an understanding of how to organize examinations in a clinically meaningful sequence. This systematic approach is crucial in emergency settings where efficiency and thoroughness must be balanced.

The examination followed a logical progression:
1. General inspection (immediate overall assessment)
2. Vital signs (pulse and blood pressure)
3. System-specific examination (cardiovascular auscultation)
4. Relevant systems (respiratory auscultation)

This approach shows the student was thinking about differentials while examining - the addition of respiratory auscultation suggests consideration of pulmonary causes of chest pain. The systematic nature ensures no crucial elements are missed while maintaining efficiency.

The ability to be systematic yet flexible in adding relevant systems demonstrates clinical maturity beyond simple rote learning. This adaptability is particularly important in complex presentations like chest pain.
            ',
            'key_evidence' => [
                'Logical examination progression',
                'Integration of multiple relevant systems',
                'Efficient yet thorough approach',
                'Clinical reasoning evident in examination choices'
            ],
            'clinical_relevance' => 'Systematic examination prevents missed findings and builds clinical confidence. In chest pain, where multiple life-threatening conditions exist, this approach is essential for patient safety.'
        ],
        
        'critical_exams' => [
            'title' => 'Critical Examinations Performance',
            'narrative' => '
The student performed most critical examinations required for chest pain evaluation, demonstrating an understanding of what must be assessed in this potentially life-threatening presentation.

Critical examinations performed:
- General inspection (detected distress and diaphoresis)
- Pulse assessment (identified tachycardia at 110 bpm)
- Blood pressure measurement (noted hypertension at 160/100)
- Cardiac auscultation (ruled out murmurs)
- Respiratory auscultation (cleared lungs)

The only significant omission was JVP examination, which, while important, is somewhat mitigated by the other cardiovascular findings. The student showed good judgment in prioritizing the most critical elements in a time-limited scenario.

The finding of clear breath sounds was particularly important, helping to rule out pneumothorax or pulmonary edema as causes of the symptoms. This selective but comprehensive approach shows clinical efficiency.
            ',
            'key_evidence' => [
                'Performed essential vital sign assessments',
                'Completed core cardiovascular examination',
                'Added relevant respiratory examination',
                'Documented all key findings'
            ],
            'clinical_relevance' => 'In chest pain, missing critical examinations can be life-threatening. The student showed good judgment in prioritizing must-do elements while maintaining thoroughness.'
        ]
    ],
    
    'investigations' => [
        'appropriateness' => [
            'title' => 'Investigation Appropriateness',
            'narrative' => '
The student demonstrated perfect judgment in selecting appropriate investigations for chest pain, choosing exactly the tests recommended by international guidelines for this presentation. This level of appropriateness suggests excellent clinical knowledge and judgment.

The investigations selected (ECG, cardiac troponin, chest X-ray) represent the gold standard initial workup for chest pain, addressing the three most critical differential categories:
- Cardiac ischemia (ECG and troponin)
- Aortic dissection (suggested by chest X-ray)
- Pulmonary pathology (chest X-ray)

Each test was highly indicated based on the presentation:
- ECG: Essential for any chest pain with cardiac risk factors
- Troponin: Necessary to rule out myocardial infarction given the risk factors
- Chest X-ray: Appropriate for characterization and to rule out other causes

The student avoided ordering unnecessary or inappropriate tests, showing good resource utilization and clinical reasoning. This perfect score reflects an understanding of evidence-based investigation strategies.
            ',
            'key_evidence' => [
                'Selected all guideline-recommended initial tests',
                'Each test strongly indicated by presentation',
                'No inappropriate or unnecessary tests',
                'Covered all critical differential diagnoses'
            ],
            'clinical_relevance' => 'Appropriate investigation selection is crucial for timely diagnosis and resource utilization. The student\'s perfect performance suggests they understand the importance of targeted, evidence-based testing.'
        ],
        
        'cost_effectiveness' => [
            'title' => 'Cost-Effective Investigation Strategy',
            'narrative' => '
The student demonstrated outstanding cost-effectiveness in investigation planning, using only 25% of the available budget while ordering all essential tests. This reflects excellent resource stewardship and clinical judgment.

The total cost of $250 for three critical investigations represents exceptional value:
- ECG ($50) - Essential and cost-effective
- Cardiac troponin ($100) - Necessary for ruling out MI
- Chest X-ray ($100) - Appropriate for differential diagnosis

More impressive than the low cost was the complete absence of wasteful spending. Every test ordered had a clear indication and potential to change management. The student avoided the common pitfall of ordering "routine" tests or defensive medicine.

This cost-conscious approach, combined with clinical appropriateness, suggests the student understands the importance of balancing thorough investigation with responsible resource use - a crucial skill in modern healthcare.
            ',
            'key_evidence' => [
                'Total cost only 25% of available budget',
                'All tests essential and potentially outcome-changing',
                'No wasteful or defensive testing',
                'Optimal resource utilization'
            ],
            'clinical_relevance' => 'Cost-effective care is increasingly important in healthcare. The student showed that appropriate investigation doesn\'t require excessive spending - a lesson many experienced clinicians have yet to master.'
        ],
        
        'sequencing' => [
            'title' => 'Investigation Sequencing',
            'narrative' => '
The student demonstrated very good sequencing of investigations, prioritizing tests based on clinical urgency and diagnostic yield. This sequencing shows an understanding of how to efficiently work up a potentially time-sensitive condition.

The sequence was clinically appropriate:
1. ECG first - Critical for immediate cardiac ischemia detection
2. Troponin second - Essential but with longer turnaround time
3. Chest X-ray third - Important but less time-sensitive

This sequencing ensures that the most urgent, life-threatening conditions (STEMI) are addressed first, while still pursuing a comprehensive diagnostic approach. The student recognized that ECG findings could immediately change management (e.g., activating cath lab), while other tests provide important but less immediately actionable information.

The only minor improvement would be considering simultaneous ordering of all three tests, which is common in emergency settings to reduce time to diagnosis. However, the sequential approach shows good clinical reasoning.
            ',
            'key_evidence' => [
                'Prioritized time-sensitive investigations first',
                'Logical progression based on urgency',
                'Appropriate consideration of test turnaround times',
                'Balanced efficiency with thoroughness'
            ],
            'clinical_relevance' => 'Proper test sequencing can significantly impact time to diagnosis and treatment. The student\'s approach shows understanding of emergency department flow and diagnostic prioritization.'
        ]
    ],
    
    'differential_diagnosis' => [
        'breadth' => [
            'title' => 'Differential Diagnosis Breadth',
            'narrative' => '
The student demonstrated good breadth in considering differential diagnoses for chest pain, as evidenced by their history-taking, examination, and investigation choices. While not explicitly stating differentials, their clinical approach suggested consideration of multiple possibilities.

The history-taking explored features of multiple potential diagnoses:
- Cardiac: radiation to arm, diaphoresis, risk factors
- Respiratory: inquiry about dyspnea
- Musculoskeletal: pain characterization
- Vascular: sudden onset, severity

Investigation choices further supported broad differential thinking:
- ECG and troponin for cardiac causes
- Chest X-ray for respiratory, vascular, and other causes

The student showed ability to think beyond the most obvious diagnosis (cardiac ischemia) while still appropriately prioritizing life-threatening conditions. This breadth is essential in chest pain, where multiple serious conditions can present similarly.

The only area for improvement is explicit discussion of the differential diagnosis, which would demonstrate even more clinical reasoning sophistication.
            ',
            'key_evidence' => [
                'History explored multiple diagnostic possibilities',
                'Investigations covered broad differential categories',
                'Clinical reasoning evident in approach',
                'Consideration of both common and serious causes'
            ],
            'clinical_relevance' => 'Broad differential thinking prevents diagnostic anchoring and consideration of zebras. In chest pain, this breadth can be life-saving when the diagnosis isn\'t the most obvious one.'
        ],
        
        'reasoning' => [
            'title' => 'Diagnostic Reasoning',
            'narrative' => '
The student demonstrated basic but solid diagnostic reasoning, recognizing high-risk features in the presentation and responding appropriately. Their reasoning, while not explicitly stated, could be inferred from their clinical actions.

Key reasoning elements evident:
- Recognition of high-risk features (sudden onset, radiation to arm, diaphoresis)
- Appropriate concern for cardiac ischemia given risk factors
- Urgent investigation selection reflecting diagnostic urgency
- Integration of multiple data sources (history, vital signs)

The student showed the ability to prioritize life-threatening conditions while maintaining a broad diagnostic approach. Their actions suggested understanding that this presentation required urgent evaluation for potentially catastrophic conditions.

However, the reasoning could have been more explicit in discussing hypotheses and how each piece of information supported or refuted specific diagnoses. The absence of explicit diagnostic discussion represents an area for development.
            ',
            'key_evidence' => [
                'Appropriate risk stratification',
                'Urgent response to high-risk features',
                'Integration of multiple data sources',
                'Clinically appropriate prioritization'
            ],
            'clinical_relevance' => 'Sound diagnostic reasoning is the foundation of medical practice. The student showed good clinical judgment, though more explicit reasoning would demonstrate even greater sophistication.'
        ],
        
        'prioritization' => [
            'title' => 'Diagnosis Prioritization',
            'narrative' => '
The student demonstrated good diagnostic prioritization, focusing appropriately on life-threatening conditions while not completely ignoring other possibilities. This is particularly crucial in chest pain, where missing a critical diagnosis can be fatal.

The prioritization was evident in:
- Immediate focus on cardiac evaluation (most urgent)
- Rapid vital sign assessment
- Urgent ECG and troponin ordering
- Consideration of other serious causes (respiratory evaluation)

This approach shows understanding of the "worst first" principle in emergency medicine. The student appropriately prioritized ruling out myocardial infarction while still gathering information about other potential diagnoses.

The prioritization was neither too narrow (missing other serious conditions) nor too broad (wasting time on low-yield possibilities). This balanced approach represents mature clinical judgment.
            ',
            'key_evidence' => [
                'Worst-first approach to diagnosis',
                'Appropriate urgency in investigation',
                'Balanced consideration of multiple serious conditions',
                'Efficient use of limited time'
            ],
            'clinical_relevance' => 'Proper diagnostic prioritization saves lives. The student showed good judgment in focusing on immediately life-threatening conditions while maintaining diagnostic breadth.'
        ]
    ],
    
    'management' => [
        'immediate_actions' => [
            'title' => 'Immediate Management Actions',
            'narrative' => '
The student demonstrated good immediate management instincts, recognizing the urgency of the situation and taking appropriate initial actions. Their response showed understanding that chest pain with risk factors requires prompt evaluation and potential intervention.

Key immediate actions identified:
- Rapid vital sign assessment (identifying tachycardia and hypertension)
- Urgent ECG ordering (critical for STEMI detection)
- Troponin request (necessary for ruling out MI)
- Recognition of high-risk presentation requiring urgent care

The student showed good clinical judgment in prioritizing diagnostic actions while implicitly understanding the need for continuous monitoring and potential treatment. The urgency in their approach suggests they would activate appropriate emergency protocols if needed.

The only minor omission was explicit mention of aspirin administration, which is standard in suspected acute coronary syndrome. However, the overall approach showed appropriate urgency and prioritization.
            ',
            'key_evidence' => [
                'Rapid response to high-risk presentation',
                'Appropriate diagnostic prioritization',
                'Recognition of need for urgent evaluation',
                'Implicit understanding of monitoring requirements'
            ],
            'clinical_relevance' => 'In chest pain, immediate actions can be life-saving. The student showed good instincts for urgent evaluation and appropriate initial management steps.'
        ],
        
        'treatment_plan' => [
            'title' => 'Treatment Planning',
            'narrative' => '
The student demonstrated basic treatment planning, with appropriate diagnostic pathways but limited explicit treatment discussions. Their plan showed understanding of the diagnostic process but could have been more comprehensive in therapeutic interventions.

The treatment approach was inferred rather than explicit:
- Appropriate diagnostic pathway (ECG, troponin, CXR)
- Implicit understanding of need for monitoring
- Recognition of high-risk presentation requiring treatment

However, there were notable omissions in explicit treatment planning:
- No mention of aspirin (standard in suspected ACS)
- No discussion of anti-anginal medications (nitroglycerin)
- No mention of pain management
- No explicit monitoring parameters

The student showed good diagnostic reasoning but needs to develop more comprehensive treatment planning skills, particularly in emergency situations where immediate interventions may be needed while awaiting diagnostic results.
            ',
            'key_evidence' => [
                'Appropriate diagnostic pathway',
                'Limited explicit treatment discussion',
                'Good understanding of diagnostic urgency',
                'Missing key therapeutic interventions'
            ],
            'clinical_relevance' => 'Comprehensive treatment planning is essential, especially in emergencies. The student showed good diagnostic skills but needs to develop more explicit therapeutic planning.'
        ],
        
        'follow_up' => [
            'title' => 'Follow-up Planning',
            'narrative' => '
This was the weakest aspect of the student\'s performance, with significant gaps in follow-up planning. Regardless of the final diagnosis, chest pain requires careful follow-up planning, which was largely absent from the student\'s approach.

Critical follow-up elements missing:
- No discussion of disposition (admission vs discharge criteria)
- No mention of cardiac monitoring duration
- No consideration of observation period
- No discharge planning if tests negative
- No cardiac risk factor modification planning
- No discussion of follow-up timing and location

This omission is particularly significant because:
1. Even if initial workup is negative, some patients require observation
2. Risk factor modification is crucial for secondary prevention
3. Clear follow-up plans are essential for patient safety
4. Discharge planning needs to be explicit and safe

The student needs to understand that management doesn\'t end with the initial evaluation - comprehensive care includes planning for what happens next.
            ',
            'key_evidence' => [
                'No disposition planning',
                'No monitoring duration discussion',
                'No discharge criteria mentioned',
                'No follow-up arrangements planned'
            ],
            'clinical_relevance' => 'Follow-up planning is crucial for patient safety and continuity of care. This gap represents a significant area for development in the student\'s clinical skills.'
        ]
    ]
];

// Display narratives
echo "=== DETAILED ASPECT NARRATIVES ===\n\n";

foreach ($aspectNarratives as $clinicalArea => $aspects) {
    echo strtoupper(str_replace('_', ' ', $clinicalArea)) . "\n";
    echo str_repeat("=", 50) . "\n\n";
    
    foreach ($aspects as $aspect => $details) {
        echo $details['title'] . "\n";
        echo str_repeat("-", 50) . "\n";
        echo wordwrap($details['narrative'], 80) . "\n\n";
        
        echo "Key Evidence:\n";
        foreach ($details['key_evidence'] as $evidence) {
            echo "• " . $evidence . "\n";
        }
        
        echo "\nClinical Relevance:\n";
        echo wordwrap($details['clinical_relevance'], 76) . "\n";
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    }
}