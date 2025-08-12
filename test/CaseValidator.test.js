import { describe, it, expect, beforeEach, beforeAll, afterAll, vi } from 'vitest';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import CaseValidator from '../utils/caseValidator.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

describe('CaseValidator', () => {
    let validator;
    let validCaseData;
    let invalidCaseData;
    let tempTestDir;

    beforeAll(() => {
        // Create temporary test directory
        tempTestDir = path.join(__dirname, 'temp-test-cases');
        if (!fs.existsSync(tempTestDir)) {
            fs.mkdirSync(tempTestDir);
        }
    });

    beforeEach(() => {
        validator = new CaseValidator();
        
        // Valid case data based on the schema
        validCaseData = {
            id: "test-001",
            title: "Test Case",
            description: "A test case for validation",
            chiefComplaint: "Test complaint",
            patientInfo: {
                age: 45,
                gender: "male",
                name: "John Doe",
                occupation: "Teacher"
            },
            presentingSymptoms: {
                primary: "Test symptom",
                associated: ["Associated symptom 1", "Associated symptom 2"],
                onset: "2 hours ago",
                character: "Sharp",
                radiation: "To arm",
                severity: "7/10"
            },
            medicalHistory: {
                pastMedical: ["Hypertension"],
                medications: ["Lisinopril"],
                allergies: ["NKDA"],
                socialHistory: {
                    smoking: "Never",
                    alcohol: "Occasional",
                    familyHistory: "No significant family history"
                }
            },
            physicalExamination: {
                vitalSigns: {
                    bp: "120/80",
                    hr: "75",
                    rr: "16",
                    temp: "36.5",
                    o2sat: "98%"
                },
                general: "Alert and oriented",
                cardiovascular: "Regular rate and rhythm",
                respiratory: "Clear to auscultation",
                other: "Unremarkable"
            },
            investigations: {
                ecg: {
                    findings: "Normal sinus rhythm",
                    interpretation: "Normal ECG"
                },
                labs: {
                    troponin: "Normal"
                },
                imaging: {
                    chest_xray: "Normal"
                }
            },
            checklist: {
                history: {
                    weight: 30,
                    items: [
                        {
                            id: "chief_complaint",
                            description: "Elicit chief complaint",
                            critical: true,
                            points: 10
                        },
                        {
                            id: "pain_history",
                            description: "Detailed pain history",
                            critical: false,
                            points: 5
                        }
                    ]
                },
                examination: {
                    weight: 40,
                    items: [
                        {
                            id: "vital_signs",
                            description: "Check vital signs",
                            critical: true,
                            points: 15
                        }
                    ]
                },
                management: {
                    weight: 30,
                    items: [
                        {
                            id: "appropriate_tests",
                            description: "Order appropriate investigations",
                            critical: false,
                            points: 10
                        }
                    ]
                }
            },
            expectedDiagnosis: "Test diagnosis",
            learningObjectives: [
                "Demonstrate history taking skills",
                "Perform physical examination",
                "Interpret findings"
            ]
        };

        // Invalid case data for testing error cases
        invalidCaseData = {
            id: "invalid-001",
            title: "Invalid Case",
            // Missing required fields intentionally
            patientInfo: {
                age: "invalid", // Should be integer
                gender: "invalid", // Should be from enum
                // Missing name field
            }
        };
    });

    afterAll(() => {
        // Clean up temporary test directory
        if (fs.existsSync(tempTestDir)) {
            const files = fs.readdirSync(tempTestDir);
            for (const file of files) {
                fs.unlinkSync(path.join(tempTestDir, file));
            }
            fs.rmdirSync(tempTestDir);
        }
    });

    describe('Constructor and Schema Loading', () => {
        it('should initialize validator and load schema successfully', () => {
            expect(validator).toBeInstanceOf(CaseValidator);
            expect(validator.schema).toBeDefined();
            expect(validator.validate).toBeDefined();
        });

        it('should handle schema loading errors gracefully', () => {
            // Mock fs.readFileSync to throw an error
            const originalReadFileSync = fs.readFileSync;
            vi.spyOn(fs, 'readFileSync').mockImplementation(() => {
                throw new Error('Schema file not found');
            });

            expect(() => new CaseValidator()).toThrow('Failed to load case validation schema');

            // Restore original function
            fs.readFileSync = originalReadFileSync;
        });
    });

    describe('validateCase', () => {
        it('should validate a correct case object successfully', () => {
            const result = validator.validateCase(validCaseData);

            expect(result.isValid).toBe(true);
            expect(result.errors).toEqual([]);
        });

        it('should identify validation errors in invalid case data', () => {
            const result = validator.validateCase(invalidCaseData);

            expect(result.isValid).toBe(false);
            expect(result.errors).toBeInstanceOf(Array);
            expect(result.errors.length).toBeGreaterThan(0);
            
            // Check that errors have the expected structure
            result.errors.forEach(error => {
                expect(error).toHaveProperty('field');
                expect(error).toHaveProperty('message');
                expect(error).toHaveProperty('value');
            });
        });

        it('should throw error when schema is not loaded', () => {
            validator.validate = null;
            
            expect(() => validator.validateCase(validCaseData)).toThrow('Schema not loaded');
        });

        it('should validate specific field requirements', () => {
            const invalidAge = { ...validCaseData };
            invalidAge.patientInfo.age = 150; // Invalid age > 120

            const result = validator.validateCase(invalidAge);
            expect(result.isValid).toBe(false);
            // Check that there's an error related to age
            expect(result.errors.some(error => 
                error.message.includes('maximum') || error.field.includes('age')
            )).toBe(true);
        });

        it('should validate gender enum constraints', () => {
            const invalidGender = { ...validCaseData };
            invalidGender.patientInfo.gender = "invalid";

            const result = validator.validateCase(invalidGender);
            expect(result.isValid).toBe(false);
            // Check that there's an error related to gender
            expect(result.errors.some(error => 
                error.message.includes('enum') || error.field.includes('gender')
            )).toBe(true);
        });

        it('should validate checklist item structure', () => {
            const invalidChecklist = { ...validCaseData };
            invalidChecklist.checklist.history.items[0] = {
                id: "test",
                description: "test",
                // missing critical and points fields
            };

            const result = validator.validateCase(invalidChecklist);
            expect(result.isValid).toBe(false);
        });
    });

    describe('validateCaseFile', () => {
        it('should validate a valid case file successfully', () => {
            // Create a temporary valid case file
            const tempFilePath = path.join(tempTestDir, 'valid-case.json');
            fs.writeFileSync(tempFilePath, JSON.stringify(validCaseData, null, 2));

            const result = validator.validateCaseFile(tempFilePath);

            expect(result.isValid).toBe(true);
            expect(result.errors).toEqual([]);
        });

        it('should handle invalid JSON files', () => {
            // Create a file with invalid JSON
            const tempFilePath = path.join(tempTestDir, 'invalid-json.json');
            fs.writeFileSync(tempFilePath, '{ invalid json }');

            const result = validator.validateCaseFile(tempFilePath);

            expect(result.isValid).toBe(false);
            expect(result.errors).toHaveLength(1);
            expect(result.errors[0].field).toBe('file');
            expect(result.errors[0].message).toContain('Failed to read or parse JSON file');
            expect(result.errors[0].value).toBe(tempFilePath);
        });

        it('should handle non-existent files', () => {
            const nonExistentPath = path.join(tempTestDir, 'non-existent.json');

            const result = validator.validateCaseFile(nonExistentPath);

            expect(result.isValid).toBe(false);
            expect(result.errors).toHaveLength(1);
            expect(result.errors[0].field).toBe('file');
            expect(result.errors[0].message).toContain('Failed to read or parse JSON file');
        });

        it('should validate case content when file is readable', () => {
            // Create a file with invalid case data
            const tempFilePath = path.join(tempTestDir, 'invalid-case.json');
            fs.writeFileSync(tempFilePath, JSON.stringify(invalidCaseData, null, 2));

            const result = validator.validateCaseFile(tempFilePath);

            expect(result.isValid).toBe(false);
            expect(result.errors.length).toBeGreaterThan(0);
        });
    });

    describe('validateAllCases', () => {
        beforeEach(() => {
            // Create test files for bulk validation
            const validFilePath = path.join(tempTestDir, 'valid-test.json');
            const invalidFilePath = path.join(tempTestDir, 'invalid-test.json');
            const nonJsonFilePath = path.join(tempTestDir, 'readme.txt');
            const schemaFilePath = path.join(tempTestDir, 'case-schema.json');

            fs.writeFileSync(validFilePath, JSON.stringify(validCaseData, null, 2));
            fs.writeFileSync(invalidFilePath, JSON.stringify(invalidCaseData, null, 2));
            fs.writeFileSync(nonJsonFilePath, 'This is not a JSON file');
            fs.writeFileSync(schemaFilePath, '{}'); // Should be ignored
        });

        it('should validate all JSON case files in directory', () => {
            const results = validator.validateAllCases(tempTestDir);

            expect(results).toHaveProperty('valid-test.json');
            expect(results).toHaveProperty('invalid-test.json');
            expect(results).not.toHaveProperty('readme.txt'); // Non-JSON files ignored
            expect(results).not.toHaveProperty('case-schema.json'); // Schema file ignored

            expect(results['valid-test.json'].isValid).toBe(true);
            expect(results['invalid-test.json'].isValid).toBe(false);
        });

        it('should handle non-existent directory gracefully', () => {
            const results = validator.validateAllCases('/non/existent/directory');

            expect(results).toEqual({});
        });

        it('should return empty object for directory without JSON files', () => {
            const emptyDir = path.join(tempTestDir, 'empty');
            fs.mkdirSync(emptyDir);

            const results = validator.validateAllCases(emptyDir);

            expect(results).toEqual({});

            fs.rmdirSync(emptyDir);
        });
    });

    describe('checkRequiredFields', () => {
        it('should pass for case data with all required fields', () => {
            const result = validator.checkRequiredFields(validCaseData);

            expect(result.isComplete).toBe(true);
            expect(result.missingFields).toEqual([]);
        });

        it('should identify missing top-level required fields', () => {
            const incompleteCase = { ...validCaseData };
            delete incompleteCase.title;
            delete incompleteCase.chiefComplaint;

            const result = validator.checkRequiredFields(incompleteCase);

            expect(result.isComplete).toBe(false);
            expect(result.missingFields).toContain('title');
            expect(result.missingFields).toContain('chiefComplaint');
        });

        it('should identify missing patient info fields', () => {
            const incompleteCase = { ...validCaseData };
            delete incompleteCase.patientInfo.name;
            delete incompleteCase.patientInfo.age;

            const result = validator.checkRequiredFields(incompleteCase);

            expect(result.isComplete).toBe(false);
            expect(result.missingFields).toContain('patientInfo.name');
            expect(result.missingFields).toContain('patientInfo.age');
        });

        it('should identify missing entire patientInfo object', () => {
            const incompleteCase = { ...validCaseData };
            delete incompleteCase.patientInfo;

            const result = validator.checkRequiredFields(incompleteCase);

            expect(result.isComplete).toBe(false);
            expect(result.missingFields).toContain('patientInfo');
        });

        it('should identify checklist structure issues', () => {
            const incompleteCase = { ...validCaseData };
            delete incompleteCase.checklist.history.weight;
            delete incompleteCase.checklist.examination.items;

            const result = validator.checkRequiredFields(incompleteCase);

            expect(result.isComplete).toBe(false);
            expect(result.missingFields).toContain('checklist.history.weight or items');
            expect(result.missingFields).toContain('checklist.examination.weight or items');
        });
    });

    describe('validateChecklistIntegrity', () => {
        it('should pass for valid checklist with weights summing to 100', () => {
            const result = validator.validateChecklistIntegrity(validCaseData.checklist);

            expect(result.isValid).toBe(true);
            expect(result.warnings).toEqual([]);
        });

        it('should identify when weights do not sum to 100', () => {
            const invalidChecklist = {
                history: { weight: 50, items: [{ id: "1", description: "test", critical: true, points: 10 }] },
                examination: { weight: 30, items: [{ id: "2", description: "test", critical: false, points: 5 }] }
                // Total: 80, should be 100
            };

            const result = validator.validateChecklistIntegrity(invalidChecklist);

            expect(result.isValid).toBe(false);
            expect(result.warnings).toContain('Checklist weights sum to 80, should be 100');
        });

        it('should identify categories with no checklist items', () => {
            const invalidChecklist = {
                history: { weight: 50, items: [] },
                examination: { weight: 50, items: undefined }
            };

            const result = validator.validateChecklistIntegrity(invalidChecklist);

            expect(result.isValid).toBe(false);
            expect(result.warnings).toContain("Category 'history' has no checklist items");
            expect(result.warnings).toContain("Category 'examination' has no checklist items");
        });

        it('should identify invalid item structures', () => {
            const invalidChecklist = {
                history: {
                    weight: 100,
                    items: [
                        { id: "1", description: "test", critical: true, points: 10 }, // Valid
                        { description: "test", critical: true, points: 10 }, // Missing id
                        { id: "3", critical: true, points: 10 }, // Missing description
                        { id: "4", description: "test", points: 10 }, // Missing critical
                        { id: "5", description: "test", critical: true } // Missing points
                    ]
                }
            };

            const result = validator.validateChecklistIntegrity(invalidChecklist);

            expect(result.isValid).toBe(false);
            expect(result.warnings.filter(w => w.includes('Invalid item structure')).length).toBe(4);
        });

        it('should handle edge cases with zero weights', () => {
            const edgeCaseChecklist = {
                history: { weight: 0, items: [{ id: "1", description: "test", critical: true, points: 10 }] },
                examination: { weight: 100, items: [{ id: "2", description: "test", critical: false, points: 5 }] }
            };

            const result = validator.validateChecklistIntegrity(edgeCaseChecklist);

            expect(result.isValid).toBe(true);
            expect(result.warnings).toEqual([]);
        });

        it('should handle weights summing to more than 100', () => {
            const invalidChecklist = {
                history: { weight: 60, items: [{ id: "1", description: "test", critical: true, points: 10 }] },
                examination: { weight: 60, items: [{ id: "2", description: "test", critical: false, points: 5 }] }
                // Total: 120, should be 100
            };

            const result = validator.validateChecklistIntegrity(invalidChecklist);

            expect(result.isValid).toBe(false);
            expect(result.warnings).toContain('Checklist weights sum to 120, should be 100');
        });
    });

    describe('Integration Tests', () => {
        it('should handle a complete validation workflow', () => {
            // Create a comprehensive case file
            const comprehensiveCase = { ...validCaseData };
            const tempFilePath = path.join(tempTestDir, 'comprehensive-case.json');
            fs.writeFileSync(tempFilePath, JSON.stringify(comprehensiveCase, null, 2));

            // Test all validation methods
            const schemaValidation = validator.validateCase(comprehensiveCase);
            const fileValidation = validator.validateCaseFile(tempFilePath);
            const fieldsCheck = validator.checkRequiredFields(comprehensiveCase);
            const checklistValidation = validator.validateChecklistIntegrity(comprehensiveCase.checklist);

            expect(schemaValidation.isValid).toBe(true);
            expect(fileValidation.isValid).toBe(true);
            expect(fieldsCheck.isComplete).toBe(true);
            expect(checklistValidation.isValid).toBe(true);
        });

        it('should provide comprehensive validation results for invalid cases', () => {
            const problematicCase = {
                id: "problematic-001",
                title: "Problematic Case",
                // Missing many required fields
                patientInfo: {
                    age: 999, // Invalid age
                    gender: "invalid", // Invalid enum
                    // Missing name
                },
                checklist: {
                    history: {
                        weight: 30,
                        items: [{ id: "incomplete" }] // Missing required fields
                    },
                    examination: {
                        weight: 30, // Will sum to 60, not 100
                        items: []
                    }
                }
            };

            const schemaValidation = validator.validateCase(problematicCase);
            const fieldsCheck = validator.checkRequiredFields(problematicCase);
            const checklistValidation = validator.validateChecklistIntegrity(problematicCase.checklist);

            expect(schemaValidation.isValid).toBe(false);
            expect(fieldsCheck.isComplete).toBe(false);
            expect(checklistValidation.isValid).toBe(false);

            // Should have multiple types of errors
            expect(schemaValidation.errors.length).toBeGreaterThan(0);
            expect(fieldsCheck.missingFields.length).toBeGreaterThan(0);
            expect(checklistValidation.warnings.length).toBeGreaterThan(0);
        });
    });

    describe('Error Resilience', () => {
        it('should handle corrupted checklist data gracefully', () => {
            const corruptedCase = { ...validCaseData };
            corruptedCase.checklist = null;

            expect(() => validator.checkRequiredFields(corruptedCase)).not.toThrow();
            expect(() => validator.validateChecklistIntegrity({})).not.toThrow();
        });

        it('should handle missing directories in validateAllCases', () => {
            // Mock console.error to avoid cluttering test output
            const originalConsoleError = console.error;
            console.error = vi.fn();

            const result = validator.validateAllCases('/definitely/does/not/exist');
            expect(result).toEqual({});

            console.error = originalConsoleError;
        });

        it('should handle file system permissions errors', () => {
            // This test might not work on all systems, so we'll mock the error
            const originalReadFileSync = fs.readFileSync;
            vi.spyOn(fs, 'readFileSync').mockImplementation(() => {
                throw new Error('EACCES: permission denied');
            });

            const result = validator.validateCaseFile('/some/path');
            expect(result.isValid).toBe(false);
            expect(result.errors[0].message).toContain('Failed to read or parse JSON file');

            fs.readFileSync = originalReadFileSync;
        });
    });
});