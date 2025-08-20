# Medical Training System - User Guide

This comprehensive guide will help you get the most out of the Medical Training System, whether you're a medical student practicing clinical skills or an educator supervising training.

## 📋 Table of Contents

1. [Getting Started](#getting-started)
2. [Chat Mode Guide](#chat-mode-guide)
3. [OSCE Mode Guide](#osce-mode-guide)
4. [Case Walkthroughs](#case-walkthroughs)
5. [Performance Optimization](#performance-optimization)
6. [Troubleshooting](#troubleshooting)

## 🚀 Getting Started

### First Launch

When you start the application, you'll see the welcome screen:

```
╔══════════════════════════════════════════════════════════════╗
║                    Medical Training System                   ║
║                         v1.0.0                              ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  🗣️  Chat Mode: General conversation with AI                 ║
║  🏥 OSCE Mode: Structured clinical examination training      ║
║                                                              ║
║  Quick Start:                                                ║
║  • Type "start osce" to begin medical case training         ║
║  • Type any message to chat with AI                         ║
║  • Type "help" for detailed command reference               ║
╚══════════════════════════════════════════════════════════════╝
```

### Choosing Your Mode

**For General Learning**: Start with chat mode by typing any medical question
**For Structured Practice**: Type `start osce` to begin case-based training

## 💬 Chat Mode Guide

### Basic Usage

Chat mode provides free-form conversation with AI for medical education:

```
[Chat] What are the signs and symptoms of myocardial infarction?

🤖 Myocardial infarction (heart attack) presents with several key signs and symptoms:

**Classic Symptoms:**
• Chest pain or discomfort (often described as crushing, squeezing, or pressure)
• Pain radiating to left arm, jaw, neck, or back
• Shortness of breath
• Nausea and vomiting
• Diaphoresis (sweating)
• Fatigue or weakness

**Physical Signs:**
• Tachycardia or bradycardia
• Hypotension or hypertension
• S3 or S4 gallop sounds
• Pulmonary crackles (if heart failure develops)
...
```

### Advanced Chat Features

**Conversation Memory**: The system maintains context across messages
```
[Chat] What about atypical presentations?

🤖 Great follow-up question! Atypical MI presentations are especially important to recognize:

**Common in:**
• Elderly patients (>75 years)
• Diabetic patients
• Women
• Patients with chronic kidney disease
...
```

**Session Statistics**: Track your learning progress
**Real-time Command Suggestions**

- Press `/` at any time to get a quick list of available commands for the current mode.
- Suggestions appear inline at the cursor and disappear on the next keypress (autocomplete-like).
- Works in both Chat mode and OSCE mode with context-appropriate commands.

```
[Chat] stats

╔══════════════════════════════════════════════════════════════╗
║                      Session Statistics                     ║
╚══════════════════════════════════════════════════════════════╝
⏱️  Session Duration: 15 minutes
💬 Chat Messages Sent: 8
🏥 OSCE Cases Completed: 0
```

## 🏥 OSCE Mode Guide

### Starting OSCE Mode

```
[Chat] start osce

🏥 Initializing OSCE Medical Training System...

╔══════════════════════════════════════════════════════════════╗
║                    Available OSCE Cases                     ║
╚══════════════════════════════════════════════════════════════╝

📋 Case ID: stemi-001
   Title: Acute Coronary Syndrome - STEMI
   Description: 58-year-old male with acute chest pain
   Difficulty: Intermediate
   Duration: ~20-30 minutes
   Focus: Emergency Cardiology

💡 To select a case, type the Case ID (e.g., "stemi-001")
💡 Type "help" for OSCE commands
```

### Case Selection and Initialization

```
[OSCE] stemi-001

🏥 Loading case: Acute Coronary Syndrome - STEMI...

╔══════════════════════════════════════════════════════════════╗
║                        Case Briefing                        ║
╚══════════════════════════════════════════════════════════════╝

👤 Patient: John Smith, 58-year-old male
🏗️  Occupation: Construction worker
📍 Setting: Emergency Department
⏰ Time: 2:30 AM

Chief Complaint: "I've been having severe chest pain for the past 2 hours"

🎯 Your task: Conduct a focused history and examination, order appropriate 
   investigations, and provide a diagnosis and management plan.

💡 The patient is waiting. Begin by introducing yourself and asking questions.
```

### Patient Interaction Examples

**History Taking:**
```
[OSCE - stemi-001] Hello Mr. Smith, I'm Dr. [Name]. Can you tell me more about your chest pain?

👤 *Patient appears diaphoretic and anxious*

"Doctor, it started about 2 hours ago while I was sleeping. It woke me up - it's like someone is crushing my chest with a vice. It's the worst pain I've ever felt, maybe 9 out of 10. The pain goes down my left arm and up to my jaw too."

*Patient clutches chest and appears uncomfortable*

✅ Tracked: Pain onset, character, severity, radiation
```

**Physical Examination:**
```
[OSCE - stemi-001] I'd like to check your vital signs and examine your heart and lungs.

👤 *Patient nods consent*

**Vital Signs:**
• Blood Pressure: 160/95 mmHg
• Heart Rate: 110 bpm (regular)
• Respiratory Rate: 22/min
• Temperature: 37.1°C
• Oxygen Saturation: 94% on room air

**Cardiovascular Examination:**
• Heart sounds: S1, S2 present, no murmurs audible
• Rhythm: Regular tachycardia
• No peripheral edema noted

**Respiratory Examination:**
• Mild bibasilar crackles
• No wheeze or stridor

✅ Tracked: Vital signs assessment, cardiovascular examination
```

**Investigations:**
```
[OSCE - stemi-001] I'd like to order an ECG immediately, please.

👤 *Nurse brings ECG machine*

**12-Lead ECG Results:**
• ST elevation in leads II, III, aVF (2-4mm)
• Reciprocal ST depression in leads I, aVL
• Q waves beginning to form in inferior leads
• Rate: 110 bpm, regular rhythm

**Interpretation:** Acute inferior STEMI

✅ Tracked: ECG ordered - CRITICAL ITEM COMPLETED
💡 This is a time-sensitive emergency requiring immediate intervention!
```

### Real-Time Progress Tracking

```
[OSCE - stemi-001] score

┌─ Current Progress ─────────────────────────────────────────────┐
│ Case: stemi-001                    Duration: 12 minutes        │
└────────────────────────────────────────────────────────────────┘

📊 **Current Score: 68/100**

✅ **Completed Items:**
• History Taking (18/22 points)
  ✓ Pain onset and timing (5/5)
  ✓ Pain character and radiation (5/5)
  ✓ Associated symptoms (3/3)
  ✓ Cardiovascular risk factors (4/4)
  ✗ Past medical history (0/3) - Not assessed
  ✗ Current medications (0/2) - Not assessed

• Physical Examination (13/15 points)
  ✓ Vital signs (5/5)
  ✓ Cardiovascular exam (5/5)
  ✓ Respiratory exam (3/3)

• Investigations (8/25 points)
  ✓ ECG ordered (8/8) - CRITICAL
  ✗ Cardiac enzymes (0/6) - Not ordered
  ✗ Basic labs (0/3) - Not ordered

⚠️ **Missing Critical Items:**
• Cardiac enzymes/troponin (6 points)
• Primary diagnosis (10 points)

💡 **Suggestions:**
• Order cardiac enzymes to confirm diagnosis
• Consider basic metabolic panel and CBC
• Provide your diagnostic impression
```

### Case Completion

```
[OSCE - stemi-001] Based on the presentation and ECG findings, I believe this is an acute inferior STEMI. I would start dual antiplatelet therapy, anticoagulation, and arrange urgent cardiac catheterization.

👤 *Patient nods* "Thank you doctor, I feel a bit better knowing what's wrong."

🎉 **Case Completed!**

╔══════════════════════════════════════════════════════════════╗
║                        Final Results                        ║
╚══════════════════════════════════════════════════════════════╝

📊 **Final Score: 85/100** (Grade: B+)
⏱️ **Completion Time:** 18 minutes
🎯 **Efficiency Rating:** Excellent

**Performance Breakdown:**
• History Taking: 20/22 (91%) ✅
• Physical Examination: 15/15 (100%) ✅
• Investigations: 20/25 (80%) ⚠️
• Diagnosis: 15/15 (100%) ✅
• Management: 8/10 (80%) ⚠️

**What You Did Well:**
✅ Excellent history taking with focus on cardiac symptoms
✅ Systematic physical examination approach
✅ Immediate ECG recognition of STEMI
✅ Correct diagnosis and understanding of urgency
✅ Appropriate emergency management initiation

**Areas for Improvement:**
⚠️ Consider ordering cardiac enzymes earlier for confirmation
⚠️ Basic labs (CBC, BMP) helpful for baseline assessment
⚠️ Mention specific antiplatelet agents (aspirin, clopidogrel)

**Learning Points:**
💡 STEMI is a time-critical emergency - "time is muscle"
💡 Door-to-balloon time goal is <90 minutes
💡 Consider contraindications to anticoagulation
💡 Monitor for complications (arrhythmias, cardiogenic shock)

🎉 Congratulations! You successfully managed this cardiac emergency.
```

## 📚 Case Walkthroughs

### STEMI Case Strategy

**Phase 1: Initial Assessment (5-7 minutes)**
1. Introduce yourself professionally
2. Obtain focused history of chest pain (OPQRST)
3. Ask about associated symptoms
4. Quick cardiovascular risk factor assessment

**Phase 2: Physical Examination (3-5 minutes)**
1. Vital signs (always first in emergency)
2. Cardiovascular examination
3. Respiratory examination
4. Brief neurological check if indicated

**Phase 3: Investigations (5-8 minutes)**
1. **Immediate**: 12-lead ECG (most critical)
2. **Urgent**: Cardiac enzymes/troponin
3. **Supportive**: Basic labs, chest X-ray

**Phase 4: Diagnosis and Management (3-5 minutes)**
1. Interpret ECG findings
2. Provide clear diagnosis
3. Outline emergency management
4. Discuss next steps (cardiac catheterization)

### Common Pitfalls to Avoid

❌ **Don't**: Spend too much time on detailed past history in emergency cases
✅ **Do**: Focus on immediate life-threatening conditions first

❌ **Don't**: Forget to order ECG in chest pain cases
✅ **Do**: Make ECG your first investigation in cardiac presentations

❌ **Don't**: Provide vague diagnoses like "chest pain"
✅ **Do**: Be specific - "acute inferior STEMI"

## 📈 Performance Optimization

### Maximizing Your OSCE Score

**Time Management:**
- Spend 30% of time on history
- Spend 20% of time on examination
- Spend 25% of time on investigations
- Spend 25% of time on diagnosis/management

**Critical Item Focus:**
- Always complete items marked as "critical"
- These carry the highest point values
- Missing critical items significantly impacts score

**Systematic Approach:**
- Follow the same structure for each case
- Develop consistent examination routines
- Practice common investigation ordering

### Learning from Feedback

**Review Your Performance:**
```
[Chat] stats

📈 Performance Trend: +12.5%
🎯 Recent Cases:
   • stemi-001: 85% (18min) - Excellent diagnosis
   • pneumonia-002: 72% (25min) - Missed key examination
   • diabetes-003: 90% (15min) - Perfect efficiency
```

**Focus Areas:**
- Identify patterns in missed items
- Practice weak areas in chat mode
- Time yourself on similar cases

## 🔧 Troubleshooting

### Common Issues and Solutions

**Case Won't Load:**
```
❌ Error: Could not load case file

💡 Solutions:
• Check that case files exist in cases/ directory
• Verify JSON formatting with case validator
• Try "system status" to check file system health
```

**AI Patient Not Responding:**
```
❌ Error: API request failed

💡 Solutions:
• Check internet connection
• Verify API key in .env file
• Try "health check" for detailed diagnostics
• Wait a moment and try again (may be rate limited)
```

**Score Calculation Issues:**
```
❌ Error: Unable to calculate performance score

💡 Solutions:
• Complete at least one checklist item
• Ensure case has valid scoring criteria
• Try "case info" to verify case integrity
```

### Getting Help

**In-App Commands:**
- `help` - Context-sensitive help
- `system status` - Technical diagnostics
- `health check` - Comprehensive system check
- `stats` - Session and performance data

**Emergency Recovery:**
- `exit osce` - Return to chat mode
- `new case` - Start fresh case
- `restart` - Reset current case

## 🎓 Advanced Tips

### For Medical Students

**Study Strategy:**
1. Start with chat mode to review concepts
2. Practice cases multiple times for consistency
3. Focus on time management and efficiency
4. Review feedback carefully after each case

**Skill Development:**
- Practice history taking in different specialties
- Develop systematic examination routines
- Learn to prioritize investigations by urgency
- Master common diagnostic patterns

### For Educators

**Monitoring Student Progress:**
- Review session statistics for engagement metrics
- Analyze performance trends across multiple cases
- Identify common areas where students struggle
- Use case completion times to assess efficiency

**Curriculum Integration:**
- Assign specific cases for different learning objectives
- Use performance data to guide additional instruction
- Encourage repeated practice for skill reinforcement
- Supplement with real patient encounters

---

**Need More Help?**

- Type `help` in the application for immediate assistance
- Check `system status` for technical issues
- Review the main README.md for setup instructions
- Use `stats` to monitor your learning progress

**Happy Learning! 🏥📚**