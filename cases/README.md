# osce medical cases

this directory contains json files for osce (objective structured clinical examination) medical cases used in the training application.

## file structure

- `case-schema.json` - json schema definition for validating case files
- `stemi-001.json` - sample stemi (st-elevation myocardial infarction) case
- `README.md` - this documentation file

## case file format

each case file must follow the json schema defined in `case-schema.json`. the basic structure includes:

### required fields

- **id**: unique identifier for the case
- **title**: case title
- **description**: brief case description
- **chiefComplaint**: patient's chief complaint
- **patientInfo**: patient demographics (age, gender, name, occupation)
- **presentingSymptoms**: primary and associated symptoms
- **medicalHistory**: past medical history, medications, allergies, social history
- **physicalExamination**: vital signs and examination findings
- **investigations**: ecg, lab results, imaging studies
- **checklist**: structured assessment criteria with weights and scoring
- **expectedDiagnosis**: the correct diagnosis for the case
- **learningObjectives**: educational goals for the case

### checklist structure

the checklist is organized into categories (e.g., historytaking, physicalexamination, investigations, diagnosis, management). each category has:

- **weight**: percentage weight for scoring (all categories should sum to 100)
- **items**: array of checklist items with:
  - **id**: unique identifier for the item
  - **description**: what the student should do
  - **critical**: boolean indicating if this is a critical item
  - **points**: points awarded for completing this item

## validation

use the validation utilities in `utils/caseValidator.js` to:

- validate case files against the json schema
- check for required fields
- verify checklist integrity (weights sum to 100, etc.)
- validate all cases in the directory

example usage:
```javascript
const CaseValidator = require('../utils/caseValidator');
const validator = new CaseValidator();

// validate a single case file
const result = validator.validateCaseFile('./cases/stemi-001.json');
console.log(result.isValid ? 'valid' : 'invalid');

// validate all cases
const allResults = validator.validateAllCases();
```

## adding new cases

1. create a new json file following the schema structure
2. use a descriptive filename (e.g., `pneumonia-001.json`)
3. ensure the case id matches the filename convention
4. run validation to check the case is properly formatted
5. test the case in the application

## sample case: stemi-001

the included stemi case (`stemi-001.json`) demonstrates:

- complete patient presentation with realistic symptoms
- comprehensive medical history and examination findings
- appropriate investigations (ecg, cardiac enzymes, imaging)
- structured checklist covering all aspects of stemi management
- educational learning objectives

this case can serve as a template for creating additional cardiac cases or other medical scenarios.