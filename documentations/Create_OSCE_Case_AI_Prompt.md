# AI Prompt: How to Create a New OSCE Case

Your task is to generate the data for a new Objective Structured Clinical Examination (OSCE) case for our medical education platform. Follow the structure and format outlined below precisely.

---

## 1. Anatomy of an OSCE Case

An OSCE case is a single record in the `osce_cases` database table. It is composed of several fields that define the clinical scenario, the AI patient's characteristics, and the expected outcomes for any diagnostic tests ordered.

| Field Name                   | Data Type      | Description                                                                                             |
| ---------------------------- | -------------- | ------------------------------------------------------------------------------------------------------- |
| `title`                      | `string`       | A concise, descriptive title for the case.                                                              |
| `description`                | `text`         | A brief summary of the clinical scenario.                                                               |
| `difficulty`                 | `string`       | The difficulty level (e.g., "Easy", "Medium", "Hard").                                                  |
| `duration_minutes`           | `integer`      | The total time allocated for the case in minutes.                                                       |
| `scenario`                   | `text`         | The detailed clinical situation presented to the user at the start.                                     |
| `objectives`                 | `text` (JSON)  | A JSON array of strings listing the learning goals.                                                     |
| `checklist`                  | `text` (JSON)  | A JSON array of objects for the evaluation checklist.                                                   |
| `ai_patient_profile`         | `text` (JSON)  | A JSON object describing the patient's background, history, etc.                                        |
| `ai_patient_vitals`          | `text` (JSON)  | A JSON object containing the patient's initial vital signs.                                             |
| `ai_patient_symptoms`        | `text` (JSON)  | A JSON array of strings describing the patient's primary symptoms.                                      |
| `ai_patient_instructions`    | `text`         | Instructions for the AI's behavior and personality during the simulation.                               |
| `clinical_setting`           | `string`       | The physical location of the scenario (e.g., "Emergency Room", "Outpatient Clinic").                    |
| `case_budget`                | `numeric`      | The maximum simulated budget for ordering tests.                                                        |
| `test_results_templates`     | `text` (JSON)  | **Crucial Field**: A JSON object containing the pre-determined results for all relevant medical tests.  |

---

## 2. Detailed Elaboration & Formatting Rules

### Standard Fields
-   **`title`, `description`, `difficulty`, `duration_minutes`, `scenario`, `clinical_setting`, `case_budget`**: These should be filled with straightforward, descriptive content appropriate for the clinical case you are creating.

### JSON Formatted Fields
-   **`objectives`**: Must be a JSON array of strings.
    -   *Example*: `["Recognize the signs of anaphylaxis.", "Administer epinephrine correctly."]`
-   **`ai_patient_profile`**: Must be a JSON object.
    -   *Example*: `{"name": "Jane Doe", "age": 34, "gender": "Female", "medical_history": "Asthma, Peanut Allergy"}`
-   **`ai_patient_vitals`**: Must be a JSON object.
    -   *Example*: `{"heart_rate": "120 bpm", "blood_pressure": "90/60 mmHg", "respiratory_rate": "28 breaths/min"}`
-   **`ai_patient_symptoms`**: Must be a JSON array of strings.
    -   *Example*: `["Difficulty breathing", "Swelling of the lips and tongue", "Hives on chest and neck"]`

### The `test_results_templates` Field (CRITICAL)
This is the most complex and important field. It defines the results for any test a user might order.

-   **Structure**: It is a JSON object.
    -   The **key** must be a string representing the `id` of the test from the `medical_tests` table.
    -   The **value** is another JSON object containing the complete, detailed result for that test *in the context of this specific case*.
-   **Example `test_results_templates` JSON**:
    ```json
    {
      "1": {
        "status": "completed",
        "values": {
          "troponin_i_level": "0.01 ng/mL",
          "reference_range": "< 0.04 ng/mL",
          "abnormal_flag": "NORMAL"
        },
        "interpretation": "Within normal limits. Myocardial infarction is highly unlikely.",
        "recommended_action": "Consider non-cardiac causes of chest pain."
      },
      "4": {
        "status": "completed",
        "values": {
          "wbc": "8.5 x10³/µL",
          "hemoglobin": "13.5 g/dL",
          "platelets": "250 x10³/µL"
        },
        "interpretation": "All values are within normal ranges.",
        "clinical_significance": "No evidence of infection or anemia."
      }
    }
    ```
    *(In this example, the case creator has defined results for the tests with IDs `1` (Troponin I) and `4` (Complete Blood Count).)*

---

## 3. Example Case: "Anaphylactic Shock"

Use the following table as a template to structure your output.

| Field Name                   | Value                                                                                                                                                                                                                                     |
| ---------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `title`                      | `Anaphylactic Shock after Bee Sting`                                                                                                                                                                                                      |
| `description`                | `A 45-year-old male develops severe respiratory distress and hypotension minutes after being stung by a bee.`                                                                                                                            |
| `difficulty`                 | `Hard`                                                                                                                                                                                                                                    |
| `duration_minutes`           | `15`                                                                                                                                                                                                                                      |
| `scenario`                   | `You are called to a park where a 45-year-old male, who was just stung by a bee, is complaining of a tight throat and dizziness. His friend tells you he has a known allergy. He appears anxious and is breathing rapidly.`                   |
| `objectives`                 | `["Identify the key features of anaphylaxis.", "Initiate immediate life-saving treatment with epinephrine.", "Manage airway and circulation appropriately."]`                                                                                 |
| `checklist`                  | `[{"task": "Assess ABCs (Airway, Breathing, Circulation)", "is_critical": true}, {"task": "Administer intramuscular epinephrine", "is_critical": true}, {"task": "Establish IV access", "is_critical": false}]`                         |
| `ai_patient_profile`         | `{"name": "John Smith", "age": 45, "gender": "Male", "allergies": "Known severe allergy to bee stings", "medications": "Carries an EpiPen but did not use it."}`                                                                           |
| `ai_patient_vitals`          | `{"heart_rate": "130 bpm", "blood_pressure": "80/50 mmHg", "respiratory_rate": "30 breaths/min", "oxygen_saturation": "88% on room air"}`                                                                                                 |
| `ai_patient_symptoms`        | `["I can't breathe", "My throat feels like it's closing", "I feel dizzy", "I'm itching all over"]`                                                                                                                                       |
| `ai_patient_instructions`    | `The patient should sound panicked and breathless. His condition should stabilize after epinephrine is administered.`                                                                                                                     |
| `clinical_setting`           | `Pre-hospital / Park`                                                                                                                                                                                                                     |
| `case_budget`                | `500.00`                                                                                                                                                                                                                                  |
| `test_results_templates`     | `{"2": {"status": "completed", "findings": {"rhythm": "Sinus tachycardia", "rate": "130 bpm"}, "interpretation": "Sinus tachycardia, likely secondary to anaphylaxis-induced hypotension and hypoxia. No ST changes noted."}, "4": {"status": "completed", "values": {"wbc": "11.2 x10³/µL"}, "interpretation": "Mild leukocytosis, consistent with an acute allergic/inflammatory reaction."}}` |

Your final output should be a well-structured markdown table containing all the necessary data to create a new, complete OSCE case.
