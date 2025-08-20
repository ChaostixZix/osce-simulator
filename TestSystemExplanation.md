# Technical Explanation: OSCE Medical Test System

This document provides a detailed technical explanation of the architecture behind how medical tests are stored, managed, and utilized within the OSCE application. The system is designed for flexibility, allowing for a wide variety of clinical scenarios with unique diagnostic results, leveraging a relational database structure with JSON data for dynamic content.

## System Architecture: Two Core Models

The system is built on two primary Eloquent models, `App\Models\MedicalTest` and `App\Models\OsceCase`, which correspond to the `medical_tests` and `osce_cases` database tables, respectively.

### 1. The Master Test Catalog: `MedicalTest` Model (`medical_tests` table)

This table acts as a central repository for every medical test that can be ordered in the application. It defines the static properties of each test.

- **Purpose**: To provide a canonical definition for each available test, including its name, category, cost, and typical turnaround time.
- **Key Fields**:
    - `id` (Primary Key): Unique identifier for the test.
    - `name`: The official name of the test (e.g., "Troponin I").
    - `description`: A brief explanation of the test's purpose.
    - `category`: The clinical category (e.g., "Cardiac Enzymes", "Imaging").
    - `cost`: The simulated financial cost of ordering the test.
    - `turnaround_minutes`: The standard time in minutes required to receive a result.

This table **does not** contain any patient-specific results. It only defines *what* a test is.

### 2. Case-Specific Results: `OsceCase` Model (`osce_cases` table)

This table defines each unique clinical scenario. A crucial component of this model is the `test_results_templates` attribute, which is cast to an array/object from a JSON column in the database. This attribute dictates the outcome of any test ordered within that specific scenario.

- **Purpose**: To store all details of a clinical case, including the patient's profile and the pre-determined results for any medical tests relevant to that scenario.
- **Key Field**:
    - `test_results_templates` (JSON): This field stores a JSON object where each key is the `id` from the `medical_tests` table. The value is another JSON object containing the full, context-specific test result.

---

## Data Relationship and Example

The power of this system lies in the link between a static test definition and its dynamic, case-specific result.

Let's illustrate with an example: A "Troponin I" test within a "Chest Pain Assessment" case versus an "Anxiety Attack" case.

#### `medical_tests` table:
| id  | name        | category         | cost   |
| --- | ----------- | ---------------- | ------ |
| 1   | Troponin I  | Cardiac Enzymes  | 45.00  |
| 2   | ECG         | Cardiology       | 100.00 |
| ... | ...         | ...              | ...    |

#### `osce_cases` table:
| id  | title                   | test_results_templates (JSON Snippet)                                                                                                                                                                                                                                                                                                                                                                                                |
| --- | ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 5   | Chest Pain Assessment   | `{ "1": { "status": "completed", "values": { "troponin_i_level": "2.45 ng/mL", "reference_range": "< 0.04 ng/mL", "abnormal_flag": "HIGH" }, "interpretation": "CRITICALLY ELEVATED - Consistent with acute STEMI...", "recommended_action": "URGENT: Activate cardiac catheterization lab..." }, "2": { "status": "completed", "findings": { "heart_rate": "110 bpm", "rhythm": "Sinus tachycardia" }, "interpretation": "ACUTE INFERIOR STEMI..." } }` |
| 12  | Anxiety Attack Scenario | `{ "1": { "status": "completed", "values": { "troponin_i_level": "0.01 ng/mL", "reference_range": "< 0.04 ng/mL", "abnormal_flag": "NORMAL" }, "interpretation": "Within normal limits. Myocardial infarction is highly unlikely.", "recommended_action": "Consider non-cardiac causes of chest pain." }, "2": { "status": "completed", "findings": { "heart_rate": "115 bpm", "rhythm": "Sinus tachycardia" }, "interpretation": "Sinus tachycardia, likely secondary to anxiety. No acute ischemic changes." } }` |


---

## Technical Workflow: From Order to Result

1.  **User Action**: A user, engaged in the "Chest Pain Assessment" case (id: `5`), orders the "Troponin I" test.
2.  **System Logic**:
    a. The application identifies the `MedicalTest` model for "Troponin I" and retrieves its primary key (`id: 1`).
    b. It fetches the current `OsceCase` model (`id: 5`).
    c. The application accesses the `test_results_templates` attribute of the `OsceCase` model. Because this is cast from JSON, it is treated as a PHP associative array.
    d. It looks up the key `'1'` within this array.
    e. The entire object associated with this key is retrieved. This object contains the detailed, pre-scripted result for Troponin I *specifically for this heart attack scenario*.
    f. This data is then formatted and presented to the user.

## Why This Architecture?

- **Flexibility & Reusability**: The same `MedicalTest` entry can be used across hundreds of cases, each with a unique result. Notice how the `test_results_templates` for Case `5` and Case `12` provide different results for the same test (`id: 1`).
- **Control**: Case authors have precise control over the diagnostic narrative, ensuring test results guide the user along the intended educational path.
- **Scalability**: New tests can be added to the `medical_tests` table without requiring any changes to existing case data. Case creators can then incorporate these new tests into their scenarios by simply adding a new key-value pair to the `test_results_templates` JSON.

This design effectively separates the *definition* of a test from its *result*, making the system both robust and highly adaptable to new educational content.
