# Implementation Plan

- [ ] 1. Update case data structure with Indonesian language and non-revealing IDs
  - Modify existing STEMI case to use ID "chest-pain-1" instead of "stemi-001"
  - Translate all case content to Indonesian (patient info, symptoms, examination findings)
  - Update case title to "Nyeri Dada Akut" and patient name to Indonesian name
  - Ensure all medical terminology is properly translated to Indonesian
  - _Requirements: 6.1, 6.2, 8.1_

- [x] 2. Implement Case Manager module
  - Create CaseManager class to handle loading and managing case files
  - Implement methods for case discovery, loading, and validation
  - Add error handling for missing or malformed case files
  - Write unit tests for case loading and validation functionality
  - _Requirements: 5.1, 5.2, 5.5_

- [ ] 3. Update Performance Tracker for accurate scoring based on specific requests
  - Modify checklist tracking to only award points when students make specific requests
  - Add validation methods to ensure information was specifically requested before scoring
  - Implement tracking for finish attempts and final diagnosis requests
  - Update scoring logic to prevent points for unrequested information
  - Write unit tests for specific request validation and accurate scoring
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 8.6_

- [ ] 2. Implement controlled information disclosure in AI Patient Simulator
  - Add methods to ask for specific examination requests before providing findings
  - Implement Indonesian prompts: "Pemeriksaan fisik apa yang ingin Anda lakukan?"
  - Add validation to ensure student makes specific requests before revealing information
  - Create Indonesian language templates for all patient responses
  - Modify response logic to never volunteer unrequested information
  - Write unit tests for controlled information disclosure
  - _Requirements: 2.3, 2.4, 2.5, 2.6, 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 5. Update Scoring Engine with Indonesian feedback and final diagnosis evaluation
  - Translate all feedback messages and educational content to Indonesian
  - Add final diagnosis evaluation and scoring
  - Implement detailed feedback generation in Indonesian
  - Create educational feedback for missed items in Indonesian
  - Write unit tests for Indonesian feedback generation and diagnosis scoring
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 7.2, 7.3, 7.4_

- [ ] 4. Add finish mechanism and Indonesian language support to OSCE Controller
  - Implement "selesai" and "finish" command handling to end cases
  - Add final diagnosis request before scoring when case is finished
  - Translate all user interface elements to Indonesian
  - Create Indonesian prompts for case selection and user interactions
  - Add validation to prevent premature case finishing
  - Write unit tests for finish mechanism and Indonesian interface
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

- [ ] 6. Create comprehensive integration tests for Indonesian language and controlled disclosure
  - Write integration tests for complete OSCE workflow with Indonesian language
  - Test controlled information disclosure - ensure AI asks for specific requests
  - Validate that scoring only occurs when students make specific requests
  - Test finish mechanism with "selesai" command and final diagnosis
  - Create end-to-end test scenarios for the chest-pain-1 case in Indonesian
  - Test that case IDs don't reveal diagnosis information
  - _Requirements: 2.3, 2.4, 2.5, 6.3, 6.4, 6.5, 6.6, 7.1, 7.2, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_