# OSCE Medical Cases

This directory contains JSON files for OSCE (Objective Structured Clinical Examination) medical cases used in the training application.

## File Structure

- `case-schema.json` - JSON schema definition for validating case files
- `stemi-001.json` - Sample STEMI (ST-elevation myocardial infarction) case
- `README.md` - This documentation file

## Case File Format

Each case file must follow the JSON schema defined in `case-schema.json`. The basic structure includes:

### Required Fields

- **id**: Unique identifier for the case
- **title**: Case title
- **description**: Brief case description
- **chiefComplaint**: Patient's chief complaint
- **patientInfo**: Patient demographics (age, gender, name, occupation)
- **presentingSymptoms**: Primary and associated symptoms
- **medicalHistory**: Past medical history, medications, allergies, social history
- **physicalExamination**: Vital signs and examination findings
- **investigations**: ECG, lab results, imaging studies
- **checklist**: Structured assessment criteria with weights and scoring
- **expectedDiagnosis**: The correct diagnosis for the case
- **learningObjectives**: Educational goals for the case

### Checklist Structure

The checklist is organized into categories (e.g., historyTaking, physicalExamination, investigations, diagnosis, management). Each category has:

- **weight**: Percentage weight for scoring (all categories should sum to 100)
- **items**: Array of checklist items with:
  - **id**: Unique identifier for the item
  - **description**: What the student should do
  - **critical**: Boolean indicating if this is a critical item
  - **points**: Points awarded for completing this item

## Validation

Use the validation utilities in `utils/caseValidator.js` to:

- Validate case files against the JSON schema
- Check for required fields
- Verify checklist integrity (weights sum to 100, etc.)
- Validate all cases in the directory

Example usage:
```javascript
const CaseValidator = require('../utils/caseValidator');
const validator = new CaseValidator();

// Validate a single case file
const result = validator.validateCaseFile('./cases/stemi-001.json');
console.log(result.isValid ? 'Valid' : 'Invalid');

// Validate all cases
const allResults = validator.validateAllCases();
```

## Adding New Cases

1. Create a new JSON file following the schema structure
2. Use a descriptive filename (e.g., `pneumonia-001.json`)
3. Ensure the case ID matches the filename convention
4. Run validation to check the case is properly formatted
5. Test the case in the application

## Sample Case: STEMI-001

The included STEMI case (`stemi-001.json`) demonstrates:

- Complete patient presentation with realistic symptoms
- Comprehensive medical history and examination findings
- Appropriate investigations (ECG, cardiac enzymes, imaging)
- Structured checklist covering all aspects of STEMI management
- Educational learning objectives

This case can serve as a template for creating additional cardiac cases or other medical scenarios.