/**
 * Centralized error handling system for the OSCE Medical App
 * Provides consistent error handling, logging, and user-friendly error messages
 */
class ErrorHandler {
    constructor() {
        this.errorCounts = new Map();
        this.lastErrors = [];
        this.maxLastErrors = 10;
        this.logLevel = process.env.LOG_LEVEL || 'info';
    }

    /**
     * Handle and log errors with appropriate user messages
     * @param {Error} error - The error object
     * @param {string} context - Context where error occurred
     * @param {Object} metadata - Additional metadata
     * @returns {Object} Formatted error response
     */
    handleError(error, context = 'unknown', metadata = {}) {
        const errorInfo = {
            timestamp: new Date().toISOString(),
            context,
            message: error.message,
            stack: error.stack,
            metadata,
            type: this._categorizeError(error)
        };

        // Log the error
        this._logError(errorInfo);

        // Track error frequency
        this._trackError(context, error.message);

        // Store for debugging
        this._storeError(errorInfo);

        // Return user-friendly response
        return this._formatUserResponse(errorInfo);
    }

    /**
     * Handle API-specific errors with retry logic
     * @param {Error} error - API error
     * @param {string} operation - Operation that failed
     * @param {number} retryCount - Current retry attempt
     * @returns {Object} Error response with retry information
     */
    handleAPIError(error, operation = 'API call', retryCount = 0) {
        const errorInfo = {
            timestamp: new Date().toISOString(),
            context: 'api',
            operation,
            retryCount,
            message: error.message,
            status: error.response?.status,
            statusText: error.response?.statusText,
            type: 'api_error'
        };

        this._logError(errorInfo);
        this._trackError('api', `${operation}: ${error.message}`);
        this._storeError(errorInfo);

        return {
            success: false,
            error: errorInfo,
            userMessage: this._getAPIErrorMessage(error, retryCount),
            canRetry: this._canRetryAPIError(error),
            retryAfter: this._getRetryDelay(retryCount)
        };
    }

    /**
     * Handle validation errors
     * @param {Array} validationErrors - Array of validation errors
     * @param {string} context - Context of validation
     * @returns {Object} Formatted validation error response
     */
    handleValidationError(validationErrors, context = 'validation') {
        const errorInfo = {
            timestamp: new Date().toISOString(),
            context,
            type: 'validation_error',
            errors: validationErrors,
            count: validationErrors.length
        };

        this._logError(errorInfo);
        this._trackError(context, `Validation failed: ${validationErrors.length} errors`);
        this._storeError(errorInfo);

        return {
            success: false,
            error: errorInfo,
            userMessage: this._getValidationErrorMessage(validationErrors),
            details: validationErrors
        };
    }

    /**
     * Handle file system errors
     * @param {Error} error - File system error
     * @param {string} operation - File operation that failed
     * @param {string} filePath - Path of file involved
     * @returns {Object} Error response
     */
    handleFileSystemError(error, operation = 'file operation', filePath = '') {
        const errorInfo = {
            timestamp: new Date().toISOString(),
            context: 'filesystem',
            operation,
            filePath,
            message: error.message,
            code: error.code,
            type: 'filesystem_error'
        };

        this._logError(errorInfo);
        this._trackError('filesystem', `${operation}: ${error.code || error.message}`);
        this._storeError(errorInfo);

        return {
            success: false,
            error: errorInfo,
            userMessage: this._getFileSystemErrorMessage(error, operation, filePath),
            canRecover: this._canRecoverFromFileError(error)
        };
    }

    /**
     * Get error statistics for monitoring
     * @returns {Object} Error statistics
     */
    getErrorStatistics() {
        const stats = {
            totalErrors: this.lastErrors.length,
            errorsByContext: {},
            errorsByType: {},
            recentErrors: this.lastErrors.slice(-5),
            errorCounts: Object.fromEntries(this.errorCounts)
        };

        // Group by context and type
        for (const error of this.lastErrors) {
            stats.errorsByContext[error.context] = (stats.errorsByContext[error.context] || 0) + 1;
            stats.errorsByType[error.type] = (stats.errorsByType[error.type] || 0) + 1;
        }

        return stats;
    }

    /**
     * Clear error history (useful for testing)
     */
    clearErrorHistory() {
        this.lastErrors = [];
        this.errorCounts.clear();
    }

    /**
     * Set log level
     * @param {string} level - Log level (error, warn, info, debug)
     */
    setLogLevel(level) {
        this.logLevel = level;
    }

    // Private methods

    /**
     * Categorize error type
     * @param {Error} error - Error object
     * @returns {string} Error category
     * @private
     */
    _categorizeError(error) {
        if (error.code === 'ENOENT') return 'file_not_found';
        if (error.code === 'EACCES') return 'permission_denied';
        if (error.code === 'ECONNREFUSED') return 'connection_refused';
        if (error.code === 'ETIMEDOUT') return 'timeout';
        if (error.name === 'ValidationError') return 'validation_error';
        if (error.name === 'SyntaxError') return 'syntax_error';
        if (error.response) return 'api_error';
        return 'unknown_error';
    }

    /**
     * Log error based on log level
     * @param {Object} errorInfo - Error information
     * @private
     */
    _logError(errorInfo) {
        const logMessage = `[${errorInfo.timestamp}] ${errorInfo.context.toUpperCase()}: ${errorInfo.message}`;
        
        switch (this.logLevel) {
            case 'debug':
                console.error(logMessage);
                console.error('Stack:', errorInfo.stack);
                console.error('Metadata:', errorInfo.metadata);
                break;
            case 'info':
                console.error(logMessage);
                if (errorInfo.type === 'api_error' || errorInfo.type === 'filesystem_error') {
                    console.error('Details:', errorInfo.metadata || errorInfo);
                }
                break;
            case 'warn':
                if (errorInfo.type !== 'validation_error') {
                    console.error(logMessage);
                }
                break;
            case 'error':
                if (['api_error', 'filesystem_error', 'unknown_error'].includes(errorInfo.type)) {
                    console.error(logMessage);
                }
                break;
        }
    }

    /**
     * Track error frequency
     * @param {string} context - Error context
     * @param {string} message - Error message
     * @private
     */
    _trackError(context, message) {
        const key = `${context}:${message}`;
        this.errorCounts.set(key, (this.errorCounts.get(key) || 0) + 1);
    }

    /**
     * Store error for debugging
     * @param {Object} errorInfo - Error information
     * @private
     */
    _storeError(errorInfo) {
        this.lastErrors.push(errorInfo);
        if (this.lastErrors.length > this.maxLastErrors) {
            this.lastErrors.shift();
        }
    }

    /**
     * Format user-friendly error response
     * @param {Object} errorInfo - Error information
     * @returns {Object} User response
     * @private
     */
    _formatUserResponse(errorInfo) {
        return {
            success: false,
            error: errorInfo,
            userMessage: this._getUserFriendlyMessage(errorInfo),
            canRetry: this._canRetryError(errorInfo),
            timestamp: errorInfo.timestamp
        };
    }

    /**
     * Get user-friendly error message
     * @param {Object} errorInfo - Error information
     * @returns {string} User message
     * @private
     */
    _getUserFriendlyMessage(errorInfo) {
        switch (errorInfo.type) {
            case 'file_not_found':
                return 'A required file could not be found. Please check that all case files are properly installed.';
            case 'permission_denied':
                return 'Permission denied accessing a required file. Please check file permissions.';
            case 'connection_refused':
                return 'Unable to connect to the AI service. Please check your internet connection and try again.';
            case 'timeout':
                return 'The request timed out. Please try again in a moment.';
            case 'validation_error':
                return 'There was a problem with the data format. Please contact support if this persists.';
            case 'syntax_error':
                return 'There was a problem parsing data. Please contact support if this persists.';
            case 'api_error':
                return 'There was a problem communicating with the AI service. Please try again.';
            default:
                return 'An unexpected error occurred. Please try again, and contact support if the problem persists.';
        }
    }

    /**
     * Get API-specific error message
     * @param {Error} error - API error
     * @param {number} retryCount - Retry count
     * @returns {string} User message
     * @private
     */
    _getAPIErrorMessage(error, retryCount) {
        const status = error.response?.status;
        
        if (status === 401) {
            return 'Authentication failed. Please check your API credentials.';
        } else if (status === 403) {
            return 'Access forbidden. Please check your API permissions.';
        } else if (status === 429) {
            return 'Rate limit exceeded. Please wait a moment before trying again.';
        } else if (status >= 500) {
            return 'The AI service is temporarily unavailable. Please try again in a few minutes.';
        } else if (error.code === 'ECONNREFUSED') {
            return 'Unable to connect to the AI service. Please check your internet connection.';
        } else if (error.code === 'ETIMEDOUT') {
            return 'The request timed out. Please try again.';
        } else if (retryCount > 0) {
            return `AI service error (attempt ${retryCount + 1}). Retrying...`;
        } else {
            return 'There was a problem with the AI service. Please try again.';
        }
    }

    /**
     * Get validation error message
     * @param {Array} validationErrors - Validation errors
     * @returns {string} User message
     * @private
     */
    _getValidationErrorMessage(validationErrors) {
        if (validationErrors.length === 1) {
            return `Data validation error: ${validationErrors[0].message}`;
        } else {
            return `Multiple data validation errors found (${validationErrors.length} issues). Please check the data format.`;
        }
    }

    /**
     * Get file system error message
     * @param {Error} error - File system error
     * @param {string} operation - Operation
     * @param {string} filePath - File path
     * @returns {string} User message
     * @private
     */
    _getFileSystemErrorMessage(error, operation, filePath) {
        switch (error.code) {
            case 'ENOENT':
                return `File not found: ${filePath}. Please ensure all required files are installed.`;
            case 'EACCES':
                return `Permission denied accessing: ${filePath}. Please check file permissions.`;
            case 'EISDIR':
                return `Expected a file but found a directory: ${filePath}`;
            case 'EMFILE':
                return 'Too many files open. Please close some applications and try again.';
            case 'ENOSPC':
                return 'Not enough disk space available.';
            default:
                return `File system error during ${operation}: ${error.message}`;
        }
    }

    /**
     * Check if error can be retried
     * @param {Object} errorInfo - Error information
     * @returns {boolean} Can retry
     * @private
     */
    _canRetryError(errorInfo) {
        const retryableTypes = ['timeout', 'connection_refused', 'api_error'];
        return retryableTypes.includes(errorInfo.type);
    }

    /**
     * Check if API error can be retried
     * @param {Error} error - API error
     * @returns {boolean} Can retry
     * @private
     */
    _canRetryAPIError(error) {
        const status = error.response?.status;
        
        // Don't retry client errors (4xx) except rate limiting
        if (status >= 400 && status < 500 && status !== 429) {
            return false;
        }
        
        // Retry server errors (5xx) and network errors
        return status >= 500 || error.code === 'ETIMEDOUT' || error.code === 'ECONNREFUSED' || status === 429;
    }

    /**
     * Check if file system error can be recovered
     * @param {Error} error - File system error
     * @returns {boolean} Can recover
     * @private
     */
    _canRecoverFromFileError(error) {
        // Can't recover from missing files or permission issues
        const unrecoverableCodes = ['ENOENT', 'EACCES', 'EISDIR'];
        return !unrecoverableCodes.includes(error.code);
    }

    /**
     * Get retry delay based on attempt count
     * @param {number} retryCount - Current retry count
     * @returns {number} Delay in milliseconds
     * @private
     */
    _getRetryDelay(retryCount) {
        // Exponential backoff: 1s, 2s, 4s, 8s, max 30s
        return Math.min(1000 * Math.pow(2, retryCount), 30000);
    }
}

export default ErrorHandler;