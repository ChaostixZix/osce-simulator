import Ajv from 'ajv';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

class CaseValidator {
    constructor() {
        this.ajv = new Ajv({ allErrors: true });
        this.schema = null;
        this.loadSchema();
    }

    /**
     * Load the JSON schema for case validation
     */
    loadSchema() {
        try {
            const schemaPath = path.join(__dirname, '../cases/case-schema.json');
            const schemaData = fs.readFileSync(schemaPath, 'utf8');
            this.schema = JSON.parse(schemaData);
            this.validate = this.ajv.compile(this.schema);
        } catch (error) {
            console.error('Error loading case schema:', error.message);
            throw new Error('Failed to load case validation schema');
        }
    }

    /**
     * Validate a case object against the schema
     * @param {Object} caseData - The case data to validate
     * @returns {Object} - Validation result with isValid boolean and errors array
     */
    validateCase(caseData) {
        if (!this.validate) {
            throw new Error('Schema not loaded');
        }

        const isValid = this.validate(caseData);
        
        return {
            isValid,
            errors: isValid ? [] : this.validate.errors.map(error => ({
                field: error.instancePath || error.schemaPath,
                message: error.message,
                value: error.data
            }))
        };
    }

    /**
     * Validate a case file by path
     * @param {string} filePath - Path to the case JSON file
     * @returns {Object} - Validation result with isValid boolean and errors array
     */
    validateCaseFile(filePath) {
        try {
            const caseData = JSON.parse(fs.readFileSync(filePath, 'utf8'));
            return this.validateCase(caseData);
        } catch (error) {
            return {
                isValid: false,
                errors: [{
                    field: 'file',
                    message: `Failed to read or parse JSON file: ${error.message}`,
                    value: filePath
                }]
            };
        }
    }

    /**
     * Validate all case files in the cases directory
     * @param {string} casesDir - Directory containing case files
     * @returns {Object} - Results for all case files
     */
    validateAllCases(casesDir = path.join(__dirname, '../cases')) {
        const results = {};
        
        try {
            const files = fs.readdirSync(casesDir)
                .filter(file => file.endsWith('.json') && file !== 'case-schema.json');

            for (const file of files) {
                const filePath = path.join(casesDir, file);
                results[file] = this.validateCaseFile(filePath);
            }
        } catch (error) {
            console.error('Error reading cases directory:', error.message);
        }

        return results;
    }

    /**
     * Check if case data has all required fields for basic functionality
     * @param {Object} caseData - The case data to check
     * @returns {Object} - Check result with missing fields
     */
    checkRequiredFields(caseData) {
        const requiredFields = [
            'id', 'title', 'chiefComplaint', 'patientInfo',
            'checklist', 'expectedDiagnosis'
        ];

        const missing = [];
        
        for (const field of requiredFields) {
            if (!caseData[field]) {
                missing.push(field);
            }
        }

        // Check nested required fields
        if (caseData.patientInfo) {
            const requiredPatientFields = ['age', 'gender', 'name'];
            for (const field of requiredPatientFields) {
                if (!caseData.patientInfo[field]) {
                    missing.push(`patientInfo.${field}`);
                }
            }
        }

        // Check checklist structure
        if (caseData.checklist) {
            for (const [category, data] of Object.entries(caseData.checklist)) {
                if (!data.weight || !data.items) {
                    missing.push(`checklist.${category}.weight or items`);
                }
            }
        }

        return {
            isComplete: missing.length === 0,
            missingFields: missing
        };
    }

    /**
     * Validate checklist integrity (weights sum to 100, etc.)
     * @param {Object} checklist - The checklist to validate
     * @returns {Object} - Validation result with warnings
     */
    validateChecklistIntegrity(checklist) {
        const warnings = [];
        let totalWeight = 0;

        for (const [category, data] of Object.entries(checklist)) {
            totalWeight += data.weight;

            // Check if category has items
            if (!data.items || data.items.length === 0) {
                warnings.push(`Category '${category}' has no checklist items`);
            }

            // Check item structure
            if (data.items) {
                for (const item of data.items) {
                    if (!item.id || !item.description || typeof item.critical !== 'boolean' || typeof item.points !== 'number') {
                        warnings.push(`Invalid item structure in category '${category}': ${item.id || 'unnamed'}`);
                    }
                }
            }
        }

        // Check if weights sum to 100
        if (totalWeight !== 100) {
            warnings.push(`Checklist weights sum to ${totalWeight}, should be 100`);
        }

        return {
            isValid: warnings.length === 0,
            warnings
        };
    }
}

export default CaseValidator;