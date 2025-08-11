import Logger from './Logger.js';

/**
 * Retry manager for handling failed operations with exponential backoff
 * Provides intelligent retry logic for API calls and other operations
 */
class RetryManager {
    constructor(options = {}) {
        this.maxRetries = options.maxRetries || 3;
        this.baseDelay = options.baseDelay || 1000; // 1 second
        this.maxDelay = options.maxDelay || 30000; // 30 seconds
        this.backoffMultiplier = options.backoffMultiplier || 2;
        this.jitter = options.jitter !== false; // Add random jitter by default
        this.logger = Logger;
    }

    /**
     * Execute an operation with retry logic
     * @param {Function} operation - Async operation to retry
     * @param {Object} options - Retry options
     * @returns {Promise} Operation result
     */
    async executeWithRetry(operation, options = {}) {
        const config = {
            maxRetries: options.maxRetries || this.maxRetries,
            baseDelay: options.baseDelay || this.baseDelay,
            maxDelay: options.maxDelay || this.maxDelay,
            backoffMultiplier: options.backoffMultiplier || this.backoffMultiplier,
            jitter: options.jitter !== false,
            retryCondition: options.retryCondition || this._defaultRetryCondition,
            onRetry: options.onRetry || null,
            context: options.context || 'operation'
        };

        let lastError;
        let attempt = 0;

        while (attempt <= config.maxRetries) {
            try {
                const startTime = Date.now();
                const result = await operation();
                
                if (attempt > 0) {
                    this.logger.info(
                        `Operation succeeded after ${attempt} retries`,
                        { 
                            context: config.context,
                            attempts: attempt + 1,
                            duration: Date.now() - startTime
                        },
                        'retry'
                    );
                }

                return result;
            } catch (error) {
                lastError = error;
                attempt++;

                // Check if we should retry
                if (attempt > config.maxRetries || !config.retryCondition(error, attempt)) {
                    this.logger.error(
                        `Operation failed after ${attempt} attempts`,
                        {
                            context: config.context,
                            error: error.message,
                            attempts: attempt,
                            finalError: true
                        },
                        'retry'
                    );
                    throw error;
                }

                // Calculate delay
                const delay = this._calculateDelay(attempt - 1, config);
                
                this.logger.warn(
                    `Operation failed, retrying in ${delay}ms (attempt ${attempt}/${config.maxRetries + 1})`,
                    {
                        context: config.context,
                        error: error.message,
                        attempt,
                        delay
                    },
                    'retry'
                );

                // Call retry callback if provided
                if (config.onRetry) {
                    try {
                        await config.onRetry(error, attempt, delay);
                    } catch (callbackError) {
                        this.logger.error(
                            'Retry callback failed',
                            { error: callbackError.message },
                            'retry'
                        );
                    }
                }

                // Wait before retrying
                await this._delay(delay);
            }
        }

        throw lastError;
    }

    /**
     * Execute API call with retry logic
     * @param {Function} apiCall - API call function
     * @param {Object} options - Retry options
     * @returns {Promise} API response
     */
    async executeAPICall(apiCall, options = {}) {
        return this.executeWithRetry(apiCall, {
            ...options,
            context: options.context || 'api_call',
            retryCondition: options.retryCondition || this._apiRetryCondition,
            onRetry: options.onRetry || this._defaultAPIRetryCallback
        });
    }

    /**
     * Execute file operation with retry logic
     * @param {Function} fileOperation - File operation function
     * @param {Object} options - Retry options
     * @returns {Promise} Operation result
     */
    async executeFileOperation(fileOperation, options = {}) {
        return this.executeWithRetry(fileOperation, {
            ...options,
            context: options.context || 'file_operation',
            maxRetries: options.maxRetries || 2, // Fewer retries for file ops
            retryCondition: options.retryCondition || this._fileRetryCondition
        });
    }

    /**
     * Create a retry wrapper for a function
     * @param {Function} fn - Function to wrap
     * @param {Object} options - Retry options
     * @returns {Function} Wrapped function
     */
    wrapWithRetry(fn, options = {}) {
        return async (...args) => {
            return this.executeWithRetry(() => fn(...args), options);
        };
    }

    /**
     * Get retry statistics
     * @returns {Object} Retry statistics
     */
    getStatistics() {
        // This would be implemented with actual tracking in production
        return {
            totalRetries: 0,
            successfulRetries: 0,
            failedRetries: 0,
            averageRetryDelay: 0
        };
    }

    // Private methods

    /**
     * Calculate delay for retry attempt
     * @param {number} attempt - Attempt number (0-based)
     * @param {Object} config - Retry configuration
     * @returns {number} Delay in milliseconds
     * @private
     */
    _calculateDelay(attempt, config) {
        let delay = config.baseDelay * Math.pow(config.backoffMultiplier, attempt);
        
        // Apply maximum delay limit
        delay = Math.min(delay, config.maxDelay);
        
        // Add jitter to prevent thundering herd
        if (config.jitter) {
            const jitterAmount = delay * 0.1; // 10% jitter
            delay += (Math.random() - 0.5) * 2 * jitterAmount;
        }
        
        return Math.round(delay);
    }

    /**
     * Default retry condition
     * @param {Error} error - Error that occurred
     * @param {number} attempt - Current attempt number
     * @returns {boolean} Whether to retry
     * @private
     */
    _defaultRetryCondition(error, attempt) {
        // Retry on network errors and temporary failures
        const retryableCodes = ['ETIMEDOUT', 'ECONNREFUSED', 'ENOTFOUND', 'ECONNRESET'];
        return retryableCodes.includes(error.code) || error.message.includes('timeout');
    }

    /**
     * API-specific retry condition
     * @param {Error} error - Error that occurred
     * @param {number} attempt - Current attempt number
     * @returns {boolean} Whether to retry
     * @private
     */
    _apiRetryCondition(error, attempt) {
        // Don't retry client errors (4xx) except rate limiting
        if (error.response) {
            const status = error.response.status;
            if (status >= 400 && status < 500 && status !== 429) {
                return false;
            }
            // Retry server errors (5xx) and rate limiting (429)
            return status >= 500 || status === 429;
        }
        
        // Retry network errors
        const retryableCodes = ['ETIMEDOUT', 'ECONNREFUSED', 'ENOTFOUND', 'ECONNRESET'];
        return retryableCodes.includes(error.code);
    }

    /**
     * File operation retry condition
     * @param {Error} error - Error that occurred
     * @param {number} attempt - Current attempt number
     * @returns {boolean} Whether to retry
     * @private
     */
    _fileRetryCondition(error, attempt) {
        // Don't retry permission errors or file not found
        const nonRetryableCodes = ['EACCES', 'ENOENT', 'EISDIR'];
        if (nonRetryableCodes.includes(error.code)) {
            return false;
        }
        
        // Retry temporary file system errors
        const retryableCodes = ['EMFILE', 'ENFILE', 'EBUSY'];
        return retryableCodes.includes(error.code);
    }

    /**
     * Default API retry callback
     * @param {Error} error - Error that occurred
     * @param {number} attempt - Current attempt number
     * @param {number} delay - Delay before next attempt
     * @private
     */
    async _defaultAPIRetryCallback(error, attempt, delay) {
        // Could implement additional logic here, like refreshing tokens
        if (error.response?.status === 401 && attempt === 1) {
            // Could attempt to refresh authentication token
            this.logger.info('API authentication error, might need token refresh', {}, 'retry');
        }
    }

    /**
     * Delay execution
     * @param {number} ms - Milliseconds to delay
     * @returns {Promise} Promise that resolves after delay
     * @private
     */
    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Create singleton instance
const retryManager = new RetryManager();

export default retryManager;