# Multi-Prompt Assessment - Sample Prompts

This document shows examples of the actual prompts used for each aspect assessment.

## History-Taking - Systematic Approach

```
You are an expert medical examiner evaluating a specific aspect of history performance in an OSCE session.

ASSESSMENT FOCUS: systematic_approach
EVALUATION CRITERIA: Evaluate the systematic approach to history-taking. Assess if the student followed a logical sequence (e.g., presenting complaint, history of present illness, past medical history, medications, allergies, family history, social history).

SESSION DATA:
{
  "session": {
    "id": 123,
    "duration_minutes": 15,
    "total_test_cost": 250
  },
  "detailed_analysis": {
    "communication": {
      "conversation_flow": [
        {"sender": "user", "message": "Hello, I'm Dr. Smith. What brings you in today?"},
        {"sender": "patient", "message": "I have this really bad pain in my chest, doctor. It started about 2 hours ago suddenly."},
        {"sender": "user", "message": "Can you tell me more about the pain? Where exactly is it and does it go anywhere else?"},
        // ... more messages
      ]
    }
  }
}

SCORING GUIDELINES:
- Acceptable Performance (60-79% of 7 points): Covers at least 4 major history domains in some order
- Good Performance (80-100% of 7 points): Follows logical sequence covering all major domains

ASSESSMENT INSTRUCTIONS:
1. Focus ONLY on the systematic_approach aspect of history
2. Review relevant session data (chat messages, tests, examinations)
3. Provide specific evidence from the session
4. Score based on the criteria above
5. Must return structured JSON response

REQUIRED JSON RESPONSE FORMAT:
{
  "aspect": "systematic_approach",
  "clinical_area": "history",
  "score": <integer 0-7>,
  "max_score": 7,
  "performance_level": "acceptable"|"good"|"needs_improvement",
  "feedback": "<specific feedback with evidence citations, max 800 chars>",
  "citations": ["msg#15", "test:CBC", "exam:cardiac"],
  "acceptable_evidence": ["evidence of meeting acceptable criteria"],
  "good_evidence": ["evidence of meeting good criteria"],
  "missing_elements": ["any missing elements for good performance"]
}

Be specific and cite exact evidence from the session data.
```

## Physical Examination - Technique

```
You are an expert medical examiner evaluating a specific aspect of exam performance in an OSCE session.

ASSESSMENT FOCUS: technique
EVALUATION CRITERIA: Evaluate physical examination technique. Assess proper positioning, exposure, and examination methods.

SESSION DATA:
{
  "session": {
    "id": 123,
    "duration_minutes": 15
  },
  "detailed_analysis": {
    "examinations": {
      "examination_timeline": [
        {
          "category": "Cardiovascular",
          "type": "General Inspection",
          "findings": "Patient appears distressed, diaphoretic",
          "performed_at": "2024-01-15T10:25:00Z"
        },
        {
          "category": "Cardiovascular",
          "type": "Pulse",
          "findings": "Radial pulse 110 bpm, regular",
          "performed_at": "2024-01-15T10:26:00Z"
        }
        // ... more examinations
      ]
    }
  }
}

SCORING GUIDELINES:
- Acceptable Performance (60-79% of 5 points): Basic technique, some awkwardness but adequate
- Good Performance (80-100% of 5 points): Proper technique, smooth and confident

[... rest of prompt similar to above ...]
```

## Investigations - Appropriateness

```
You are an expert medical examiner evaluating a specific aspect of investigations performance in an OSCE session.

ASSESSMENT FOCUS: appropriateness
EVALUATION CRITERIA: Evaluate appropriateness of investigations ordered. Were tests indicated based on clinical presentation?

SESSION DATA:
{
  "session": {
    "id": 123,
    "total_test_cost": 250
  },
  "case": {
    "chief_complaint": "Chest pain",
    "required_tests": ["ECG", "Cardiac enzymes"]
  },
  "detailed_analysis": {
    "tests": {
      "test_ordering_timeline": [
        {
          "test_name": "ECG",
          "cost": 50,
          "ordered_at": "2024-01-15T10:28:00Z"
        },
        {
          "test_name": "Cardiac Troponin",
          "cost": 100,
          "ordered_at": "2024-01-15T10:29:00Z"
        }
      ],
      "appropriate_tests_status": {
        "appropriate": ["ECG", "Cardiac Troponin", "Chest X-ray"],
        "inappropriate": []
      }
    }
  }
}

SCORING GUIDELINES:
- Acceptable Performance (60-79% of 7 points): 60% of tests clinically indicated
- Good Performance (80-100% of 7 points): 80%+ of tests highly appropriate

[... rest of prompt similar to above ...]
```

## Sample AI Response for History - Systematic Approach

```json
{
  "aspect": "systematic_approach",
  "clinical_area": "history",
  "score": 6,
  "max_score": 7,
  "performance_level": "good",
  "feedback": "Excellent systematic approach following a logical sequence from presenting complaint to relevant history. Student started with open-ended question about chest pain, then systematically explored location, radiation, associated symptoms, and risk factors.",
  "citations": ["msg#2", "msg#4", "msg#6", "msg#8"],
  "acceptable_evidence": [
    "Asked about presenting complaint first",
    "Explored pain characteristics",
    "Inquired about associated symptoms",
    "Asked about medical history"
  ],
  "good_evidence": [
    "Perfect logical flow: complaint → characteristics → associated symptoms → risk factors",
    "Used open-ended questions appropriately",
    "Covered all major domains systematically"
  ],
  "missing_elements": [
    "Family history not explicitly asked",
    "Social history limited to smoking"
  ]
}
```

## Key Features of the Multi-Prompt Approach

1. **Focused Assessment**: Each prompt targets only one specific aspect
2. **Clear Criteria**: Explicit definitions for acceptable vs good performance
3. **Evidence Required**: AI must cite specific evidence from session data
4. **Structured Output**: JSON format ensures consistent data structure
5. **Performance Levels**: Clear categorization (needs improvement/acceptable/good)
6. **Detailed Feedback**: Specific feedback with strengths and areas for improvement

## Benefits Over Single-Prompt Approach

### Before (Single Prompt)
- One massive prompt covering all aspects
- Risk of hitting token limits
- AI might focus on some aspects over others
- Difficult to debug which aspect caused issues
- Generic feedback covering multiple areas

### After (Multi-Prompt)
- Individual prompts for each aspect
- No token limit issues
- Equal focus on all aspects
- Easy to identify problematic aspects
- Specific, actionable feedback per aspect
- Better reliability and debugging capability