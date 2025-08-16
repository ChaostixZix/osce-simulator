# medical training system - user guide

this comprehensive guide will help you get the most out of the medical training system, whether you're a medical student practicing clinical skills or an educator supervising training.

## 📋 table of contents

1. [getting started](#getting-started)
2. [chat mode guide](#chat-mode-guide)
3. [osce mode guide](#osce-mode-guide)
4. [case walkthroughs](#case-walkthroughs)
5. [performance optimization](#performance-optimization)
6. [troubleshooting](#troubleshooting)

## 🚀 getting started

### first launch

when you start the application, you'll see the welcome screen:

```
╔══════════════════════════════════════════════════════════════╗
║                    medical training system                   ║
║                         v1.0.0                              ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  🗣️  chat mode: general conversation with ai                 ║
║  🏥 osce mode: structured clinical examination training      ║
║                                                              ║
║  quick start:                                                ║
║  • type "start osce" to begin medical case training         ║
║  • type any message to chat with ai                         ║
║  • type "help" for detailed command reference               ║
╚══════════════════════════════════════════════════════════════╝
```

### choosing your mode

**for general learning**: start with chat mode by typing any medical question
**for structured practice**: type `start osce` to begin case-based training

## 💬 chat mode guide

### basic usage

chat mode provides free-form conversation with ai for medical education:

```
[chat] what are the signs and symptoms of myocardial infarction?

🤖 myocardial infarction (heart attack) presents with several key signs and symptoms:

**classic symptoms:**
• chest pain or discomfort (often described as crushing, squeezing, or pressure)
• pain radiating to left arm, jaw, neck, or back
• shortness of breath
• nausea and vomiting
• diaphoresis (sweating)
• fatigue or weakness

**physical signs:**
• tachycardia or bradycardia
• hypotension or hypertension
• s3 or s4 gallop sounds
• pulmonary crackles (if heart failure develops)
...
```

### advanced chat features

**conversation memory**: the system maintains context across messages
```
[chat] what about atypical presentations?

🤖 great follow-up question! atypical mi presentations are especially important to recognize:

**common in:**
• elderly patients (>75 years)
• diabetic patients
• women
• patients with chronic kidney disease
...
```

**session statistics**: track your learning progress
**real-time command suggestions**

- press `/` at any time to get a quick list of available commands for the current mode.
- suggestions appear inline at the cursor and disappear on the next keypress (autocomplete-like).
- works in both chat mode and osce mode with context-appropriate commands.

```
[chat] stats

╔══════════════════════════════════════════════════════════════╗
║                      session statistics                     ║
╚══════════════════════════════════════════════════════════════╝
⏱️  session duration: 15 minutes
💬 chat messages sent: 8
🏥 osce cases completed: 0
```

## 🏥 osce mode guide

### starting osce mode

```
[chat] start osce

🏥 initializing osce medical training system...

╔══════════════════════════════════════════════════════════════╗
║                    available osce cases                     ║
╚══════════════════════════════════════════════════════════════╝

📋 case id: stemi-001
   title: acute coronary syndrome - stemi
   description: 58-year-old male with acute chest pain
   difficulty: intermediate
   duration: ~20-30 minutes
   focus: emergency cardiology

💡 to select a case, type the case id (e.g., "stemi-001")
💡 type "help" for osce commands
```

### case selection and initialization

```
[osce] stemi-001

🏥 loading case: acute coronary syndrome - stemi...

╔══════════════════════════════════════════════════════════════╗
║                        case briefing                        ║
╚══════════════════════════════════════════════════════════════╝

👤 patient: john smith, 58-year-old male
🏗️  occupation: construction worker
📍 setting: emergency department
⏰ time: 2:30 am

chief complaint: "i've been having severe chest pain for the past 2 hours"

🎯 your task: conduct a focused history and examination, order appropriate 
   investigations, and provide a diagnosis and management plan.

💡 the patient is waiting. begin by introducing yourself and asking questions.
```

### patient interaction examples

**history taking:**
```
[osce - stemi-001] hello mr. smith, i'm dr. [name]. can you tell me more about your chest pain?

👤 *patient appears diaphoretic and anxious*

"doctor, it started about 2 hours ago while i was sleeping. it woke me up - it's like someone is crushing my chest with a vice. it's the worst pain i've ever felt, maybe 9 out of 10. the pain goes down my left arm and up to my jaw too."

*patient clutches chest and appears uncomfortable*

✅ tracked: pain onset, character, severity, radiation
```

**physical examination:**
```
[osce - stemi-001] i'd like to check your vital signs and examine your heart and lungs.

👤 *patient nods consent*

**vital signs:**
• blood pressure: 160/95 mmhg
• heart rate: 110 bpm (regular)
• respiratory rate: 22/min
• temperature: 37.1°c
• oxygen saturation: 94% on room air

**cardiovascular examination:**
• heart sounds: s1, s2 present, no murmurs audible
• rhythm: regular tachycardia
• no peripheral edema noted

**respiratory examination:**
• mild bibasilar crackles
• no wheeze or stridor

✅ tracked: vital signs assessment, cardiovascular examination
```

**investigations:**
```
[osce - stemi-001] i'd like to order an ecg immediately, please.

👤 *nurse brings ecg machine*

**12-lead ecg results:**
• st elevation in leads ii, iii, avf (2-4mm)
• reciprocal st depression in leads i, avl
• q waves beginning to form in inferior leads
• rate: 110 bpm, regular rhythm

**interpretation:** acute inferior stemi

✅ tracked: ecg ordered - critical item completed
💡 this is a time-sensitive emergency requiring immediate intervention!
```

### real-time progress tracking

```
[osce - stemi-001] score

┌─ current progress ─────────────────────────────────────────────┐
│ case: stemi-001                    duration: 12 minutes        │
└────────────────────────────────────────────────────────────────┘

📊 **current score: 68/100**

✅ **completed items:**
• history taking (18/22 points)
  ✓ pain onset and timing (5/5)
  ✓ pain character and radiation (5/5)
  ✓ associated symptoms (3/3)
  ✓ cardiovascular risk factors (4/4)
  ✗ past medical history (0/3) - not assessed
  ✗ current medications (0/2) - not assessed

• physical examination (13/15 points)
  ✓ vital signs (5/5)
  ✓ cardiovascular exam (5/5)
  ✓ respiratory exam (3/3)

• investigations (8/25 points)
  ✓ ecg ordered (8/8) - critical
  ✗ cardiac enzymes (0/6) - not ordered
  ✗ basic labs (0/3) - not ordered

⚠️ **missing critical items:**
• cardiac enzymes/troponin (6 points)
• primary diagnosis (10 points)

💡 **suggestions:**
• order cardiac enzymes to confirm diagnosis
• consider basic metabolic panel and cbc
• provide your diagnostic impression
```

### case completion

```
[osce - stemi-001] based on the presentation and ecg findings, i believe this is an acute inferior stemi. i would start dual antiplatelet therapy, anticoagulation, and arrange urgent cardiac catheterization.

👤 *patient nods* "thank you doctor, i feel a bit better knowing what's wrong."

🎉 **case completed!**

╔══════════════════════════════════════════════════════════════╗
║                        final results                        ║
╚══════════════════════════════════════════════════════════════╝

📊 **final score: 85/100** (grade: b+)
⏱️ **completion time:** 18 minutes
🎯 **efficiency rating:** excellent

**performance breakdown:**
• history taking: 20/22 (91%) ✅
• physical examination: 15/15 (100%) ✅
• investigations: 20/25 (80%) ⚠️
• diagnosis: 15/15 (100%) ✅
• management: 8/10 (80%) ⚠️

**what you did well:**
✅ excellent history taking with focus on cardiac symptoms
✅ systematic physical examination approach
✅ immediate ecg recognition of stemi
✅ correct diagnosis and understanding of urgency
✅ appropriate emergency management initiation

**areas for improvement:**
⚠️ consider ordering cardiac enzymes earlier for confirmation
⚠️ basic labs (cbc, bmp) helpful for baseline assessment
⚠️ mention specific antiplatelet agents (aspirin, clopidogrel)

**learning points:**
💡 stemi is a time-critical emergency - "time is muscle"
💡 door-to-balloon time goal is <90 minutes
💡 consider contraindications to anticoagulation
💡 monitor for complications (arrhythmias, cardiogenic shock)

🎉 congratulations! you successfully managed this cardiac emergency.
```

## 📚 case walkthroughs

### stemi case strategy

**phase 1: initial assessment (5-7 minutes)**
1. introduce yourself professionally
2. obtain focused history of chest pain (opqrst)
3. ask about associated symptoms
4. quick cardiovascular risk factor assessment

**phase 2: physical examination (3-5 minutes)**
1. vital signs (always first in emergency)
2. cardiovascular examination
3. respiratory examination
4. brief neurological check if indicated

**phase 3: investigations (5-8 minutes)**
1. **immediate**: 12-lead ecg (most critical)
2. **urgent**: cardiac enzymes/troponin
3. **supportive**: basic labs, chest x-ray

**phase 4: diagnosis and management (3-5 minutes)**
1. interpret ecg findings
2. provide clear diagnosis
3. outline emergency management
4. discuss next steps (cardiac catheterization)

### common pitfalls to avoid

❌ **don't**: spend too much time on detailed past history in emergency cases
✅ **do**: focus on immediate life-threatening conditions first

❌ **don't**: forget to order ecg in chest pain cases
✅ **do**: make ecg your first investigation in cardiac presentations

❌ **don't**: provide vague diagnoses like "chest pain"
✅ **do**: be specific - "acute inferior stemi"

## 📈 performance optimization

### maximizing your osce score

**time management:**
- spend 30% of time on history
- spend 20% of time on examination
- spend 25% of time on investigations
- spend 25% of time on diagnosis/management

**critical item focus:**
- always complete items marked as "critical"
- these carry the highest point values
- missing critical items significantly impacts score

**systematic approach:**
- follow the same structure for each case
- develop consistent examination routines
- practice common investigation ordering

### learning from feedback

**review your performance:**
```
[chat] stats

📈 performance trend: +12.5%
🎯 recent cases:
   • stemi-001: 85% (18min) - excellent diagnosis
   • pneumonia-002: 72% (25min) - missed key examination
   • diabetes-003: 90% (15min) - perfect efficiency
```

**focus areas:**
- identify patterns in missed items
- practice weak areas in chat mode
- time yourself on similar cases

## 🔧 troubleshooting

### common issues and solutions

**case won't load:**
```
❌ error: could not load case file

💡 solutions:
• check that case files exist in cases/ directory
• verify json formatting with case validator
• try "system status" to check file system health
```

**ai patient not responding:**
```
❌ error: api request failed

💡 solutions:
• check internet connection
• verify api key in .env file
• try "health check" for detailed diagnostics
• wait a moment and try again (may be rate limited)
```

**score calculation issues:**
```
❌ error: unable to calculate performance score

💡 solutions:
• complete at least one checklist item
• ensure case has valid scoring criteria
• try "case info" to verify case integrity
```

### getting help

**in-app commands:**
- `help` - context-sensitive help
- `system status` - technical diagnostics
- `health check` - comprehensive system check
- `stats` - session and performance data

**emergency recovery:**
- `exit osce` - return to chat mode
- `new case` - start fresh case
- `restart` - reset current case

## 🎓 advanced tips

### for medical students

**study strategy:**
1. start with chat mode to review concepts
2. practice cases multiple times for consistency
3. focus on time management and efficiency
4. review feedback carefully after each case

**skill development:**
- practice history taking in different specialties
- develop systematic examination routines
- learn to prioritize investigations by urgency
- master common diagnostic patterns

### for educators

**monitoring student progress:**
- review session statistics for engagement metrics
- analyze performance trends across multiple cases
- identify common areas where students struggle
- use case completion times to assess efficiency

**curriculum integration:**
- assign specific cases for different learning objectives
- use performance data to guide additional instruction
- encourage repeated practice for skill reinforcement
- supplement with real patient encounters

---

**need more help?**

- type `help` in the application for immediate assistance
- check `system status` for technical issues
- review the main readme.md for setup instructions
- use `stats` to monitor your learning progress

**happy learning! 🏥📚**