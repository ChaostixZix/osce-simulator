import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import CaseValidator from '../utils/caseValidator.js';
import ErrorHandler from './ErrorHandler.js';
import Logger from './Logger.js';
import RetryManager from './RetryManager.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/**
 * CaseManager handles loading, managing, and validating OSCE medical case files
 */
class CaseManager {
    constructor(casesDirectory = path.join(__dirname, '../cases')) {
        this.casesDirectory = casesDirectory;
        this.validator = new CaseValidator();
        this.cases = new Map(); // Cache for loaded cases
        this.caseList = []; // List of available cases
        
        // Error handling and logging
        this.errorHandler = new ErrorHandler();
        this.logger = Logger;
        this.retryManager = RetryManager;
        
        // Loading statistics
        this.loadingStats = {
            totalAttempts: 0,
            successfulLoads: 0,
            failedLoads: 0,
            validationErrors: 0
        };
    }

    /**
     * Discover and load all available case files
     * @returns {Promise<Array>} Array of case metadata
     */
    async loadAvailableCases() {
        const startTime = Date.now();
        this.loadingStats.totalAttempts++;

        try {
            this.caseList = [];
            this.cases.clear();

            this.logger.info('Starting case discovery and loading', { directory: this.casesDirectory }, 'case_manager');

            // Check if cases directory exists with retry
            const directoryExists = await this.retryManager.executeFileOperation(
                () => this._checkDirectoryExists(),
                { context: 'directory_check', maxRetries: 1 }
            );

            if (!directoryExists) {
                const error = new Error(`Cases directory not found: ${this.casesDirectory}`);
                const errorResponse = this.errorHandler.handleFileSystemError(error, 'directory_access', this.casesDirectory);
                this.logger.error('Cases directory not found', { directory: this.casesDirectory }, 'case_manager');
                throw error;
            }

            // Read directory contents with error handling
            const files = await this.retryManager.executeFileOperation(
                () => this._readCaseFiles(),
                { context: 'read_directory' }
            );

            if (files.length === 0) {
                this.logger.warn('No case files found in cases directory', { directory: this.casesDirectory }, 'case_manager');
                return [];
            }

            this.logger.info(`Found ${files.length} potential case files`, { files }, 'case_manager');

            // Load and validate each case file
            const loadPromises = files.map(file => this._loadSingleCaseFile(file));
            const results = await Promise.allSettled(loadPromises);

            // Process results
            let successCount = 0;
            let errorCount = 0;

            for (let i = 0; i < results.length; i++) {
                const result = results[i];
                const filename = files[i];

                if (result.status === 'fulfilled' && result.value) {
                    const caseData = result.value;
                    this.cases.set(caseData.id, caseData);
                    this.caseList.push({
                        id: caseData.id,
                        title: caseData.title,
                        description: caseData.description,
                        filename: filename
                    });
                    successCount++;
                } else {
                    errorCount++;
                    const error = result.reason || new Error('Unknown error loading case');
                    this.logger.error(
                        `Failed to load case file: ${filename}`,
                        { error: error.message, filename },
                        'case_manager'
                    );
                }
            }

            // Update statistics
            this.loadingStats.successfulLoads += successCount;
            this.loadingStats.failedLoads += errorCount;

            const duration = Date.now() - startTime;
            this.logger.info(
                `Case loading completed: ${successCount} successful, ${errorCount} failed`,
                { 
                    duration,
                    successCount,
                    errorCount,
                    totalCases: this.caseList.length
                },
                'case_manager'
            );

            return this.caseList;

        } catch (error) {
            const duration = Date.now() - startTime;
            const errorResponse = this.errorHandler.handleError(error, 'case_loading');
            
            this.logger.error(
                'Critical error during case loading',
                { 
                    error: error.message,
                    duration,
                    directory: this.casesDirectory
                },
                'case_manager'
            );

            // Return empty array instead of throwing to allow app to continue
            return [];
        }
    }

    /**
     * Load and validate a single case file
     * @param {string} filePath - Path to the case JSON file
     * @returns {Promise<Object|null>} Case data or null if invalid
     */
    async loadCaseFile(filePath) {
        try {
            this.logger.debug(`Loading case file: ${filePath}`, {}, 'case_manager');

            // Read file with retry mechanism
            const fileContent = await this.retryManager.executeFileOperation(
                () => fs.readFileSync(filePath, 'utf8'),
                { context: 'read_case_file', maxRetries: 2 }
            );

            // Parse JSON with error handling
            let caseData;
            try {
                caseData = JSON.parse(fileContent);
            } catch (parseError) {
                const error = new Error(`Invalid JSON format in ${filePath}: ${parseError.message}`);
                this.errorHandler.handleFileSystemError(error, 'json_parse', filePath);
                throw error;
            }

            // Validate case data
            const validationResult = this.validator.validateCase(caseData);
            
            if (!validationResult.isValid) {
                this.loadingStats.validationErrors++;
                
                this.logger.error(
                    `Case validation failed for ${filePath}`,
                    { 
                        errors: validationResult.errors,
                        errorCount: validationResult.errors.length
                    },
                    'case_manager'
                );

                // Log each validation error
                validationResult.errors.forEach(error => {
                    this.logger.error(
                        `Validation error: ${error.field}: ${error.message}`,
                        { field: error.field, value: error.value, filePath },
                        'case_manager'
                    );
                });

                return null;
            }

            // Additional integrity checks
            const integrityResult = this.validator.validateChecklistIntegrity(caseData.checklist);
            if (!integrityResult.isValid) {
                this.logger.warn(
                    `Case integrity warnings for ${filePath}`,
                    { warnings: integrityResult.warnings },
                    'case_manager'
                );

                integrityResult.warnings.forEach(warning => {
                    this.logger.warn(`Integrity warning: ${warning}`, { filePath }, 'case_manager');
                });
            }

            this.logger.debug(
                `Successfully loaded and validated case: ${caseData.id}`,
                { 
                    caseId: caseData.id,
                    title: caseData.title,
                    filePath
                },
                'case_manager'
            );

            return caseData;

        } catch (error) {
            // Handle different types of file system errors
            if (error.code === 'ENOENT') {
                const fileError = new Error(`Case file not found: ${filePath}`);
                this.errorHandler.handleFileSystemError(fileError, 'file_not_found', filePath);
                throw fileError;
            } else if (error.code === 'EACCES') {
                const permError = new Error(`Permission denied reading case file: ${filePath}`);
                this.errorHandler.handleFileSystemError(permError, 'permission_denied', filePath);
                throw permError;
            } else if (error instanceof SyntaxError) {
                // JSON parsing error - already handled above
                throw error;
            } else {
                const genericError = new Error(`Failed to load case file ${filePath}: ${error.message}`);
                this.errorHandler.handleFileSystemError(genericError, 'file_load', filePath);
                throw genericError;
            }
        }
    }

    /**
     * Get a specific case by ID
     * @param {string} caseId - The case ID to retrieve
     * @returns {Object|null} Case data or null if not found
     */
    getCaseById(caseId) {
        if (!caseId) {
            throw new Error('Case ID is required');
        }

        const caseData = this.cases.get(caseId);
        if (!caseData) {
            console.warn(`Case not found: ${caseId}`);
            return null;
        }

        return caseData;
    }

    /**
     * Get list of available cases with basic metadata
     * @returns {Array} Array of case metadata objects
     */
    getCaseList() {
        return [...this.caseList]; // Return a copy to prevent external modification
    }

    /**
     * Validate case data against schema and integrity rules
     * @param {Object} caseData - Case data to validate
     * @returns {Object} Validation result with detailed feedback
     */
    validateCaseData(caseData) {
        if (!caseData) {
            return {
                isValid: false,
                errors: [{ field: 'caseData', message: 'Case data is required', value: null }],
                warnings: []
            };
        }

        // Schema validation
        const schemaResult = this.validator.validateCase(caseData);
        
        // Integrity validation
        const integrityResult = caseData.checklist ? 
            this.validator.validateChecklistIntegrity(caseData.checklist) : 
            { isValid: false, warnings: ['No checklist found'] };

        // Required fields check
        const fieldsResult = this.validator.checkRequiredFields(caseData);

        return {
            isValid: schemaResult.isValid && fieldsResult.isComplete,
            errors: [
                ...schemaResult.errors,
                ...fieldsResult.missingFields.map(field => ({
                    field,
                    message: 'Required field is missing',
                    value: null
                }))
            ],
            warnings: integrityResult.warnings || []
        };
    }

    /**
     * Check if a case exists by ID
     * @param {string} caseId - The case ID to check
     * @returns {boolean} True if case exists
     */
    caseExists(caseId) {
        return this.cases.has(caseId);
    }

    /**
     * Get total number of loaded cases
     * @returns {number} Number of cases
     */
    getCaseCount() {
        return this.cases.size;
    }

    /**
     * Reload all cases (useful for development/testing)
     * @returns {Promise<Array>} Updated case list
     */
    async reloadCases() {
        console.log('Reloading all cases...');
        return await this.loadAvailableCases();
    }

    /**
     * Get case statistics
     * @returns {Object} Statistics about loaded cases
     */
    getCaseStatistics() {
        const stats = {
            totalCases: this.cases.size,
            casesByType: {},
            averageChecklistItems: 0,
            totalChecklistItems: 0
        };

        let totalItems = 0;
        
        for (const caseData of this.cases.values()) {
            // Count checklist items
            let caseItems = 0;
            if (caseData.checklist) {
                for (const category of Object.values(caseData.checklist)) {
                    if (category.items) {
                        caseItems += category.items.length;
                    }
                }
            }
            totalItems += caseItems;

            // Categorize by type (extract from title or description)
            const type = this.extractCaseType(caseData);
            stats.casesByType[type] = (stats.casesByType[type] || 0) + 1;
        }

        stats.totalChecklistItems = totalItems;
        stats.averageChecklistItems = this.cases.size > 0 ? 
            Math.round(totalItems / this.cases.size) : 0;

        return stats;
    }

    /**
     * Extract case type from case data (helper method)
     * @param {Object} caseData - Case data
     * @returns {string} Case type
     */
    extractCaseType(caseData) {
        const title = caseData.title.toLowerCase();
        
        if (title.includes('stemi') || title.includes('myocardial infarction')) {
            return 'Cardiology';
        } else if (title.includes('pneumonia') || title.includes('respiratory')) {
            return 'Pulmonology';
        } else if (title.includes('stroke') || title.includes('neurological')) {
            return 'Neurology';
        } else {
            return 'General';
        }
    }

    /**
     * Get loading statistics
     * @returns {Object} Loading statistics
     */
    getLoadingStatistics() {
        return {
            ...this.loadingStats,
            successRate: this.loadingStats.totalAttempts > 0 ? 
                (this.loadingStats.successfulLoads / this.loadingStats.totalAttempts) * 100 : 0
        };
    }

    /**
     * Reset loading statistics
     */
    resetLoadingStatistics() {
        this.loadingStats = {
            totalAttempts: 0,
            successfulLoads: 0,
            failedLoads: 0,
            validationErrors: 0
        };
    }

    // Private helper methods

    /**
     * Check if cases directory exists
     * @returns {boolean} Directory exists
     * @private
     */
    _checkDirectoryExists() {
        return fs.existsSync(this.casesDirectory);
    }

    /**
     * Read case files from directory
     * @returns {Array} Array of case filenames
     * @private
     */
    _readCaseFiles() {
        return fs.readdirSync(this.casesDirectory)
            .filter(file => file.endsWith('.json') && file !== 'case-schema.json');
    }

    /**
     * Load a single case file with comprehensive error handling
     * @param {string} filename - Case filename
     * @returns {Promise<Object|null>} Case data or null
     * @private
     */
    async _loadSingleCaseFile(filename) {
        try {
            const filePath = path.join(this.casesDirectory, filename);
            return await this.loadCaseFile(filePath);
        } catch (error) {
            this.logger.error(
                `Error loading case file: ${filename}`,
                { error: error.message, filename },
                'case_manager'
            );
            return null;
        }
    }
}

export default CaseManager;