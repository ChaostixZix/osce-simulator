import { describe, it, expect, beforeEach } from 'vitest';
import ErrorHandler from '../lib/ErrorHandler.js';
import Logger from '../lib/Logger.js';
import RetryManager from '../lib/RetryManager.js';

describe('Error Handling System', () => {
    let errorHandler;
    let retryManager;

    beforeEach(() => {
        errorHandler = new ErrorHandler();
        retryManager = RetryManager; // Use singleton instance
        errorHandler.clearErrorHistory();
    });

    describe('ErrorHandler', () => {
        it('should handle basic errors correctly', () => {
            const error = new Error('Test error');
            const result = errorHandler.handleError(error, 'test_context');

            expect(result.success).toBe(false);
            expect(result.userMessage).toBeDefined();
            expect(result.error.context).toBe('test_context');
            expect(result.error.message).toBe('Test error');
        });

        it('should categorize API errors correctly', () => {
            const apiError = new Error('API Error');
            apiError.response = { status: 429, statusText: 'Too Many Requests' };
            
            const result = errorHandler.handleAPIError(apiError, 'test_api_call');

            expect(result.success).toBe(false);
            expect(result.canRetry).toBe(true);
            expect(result.error.type).toBe('api_error');
            expect(result.userMessage).toContain('Rate limit exceeded');
        });

        it('should handle file system errors correctly', () => {
            const fsError = new Error('File not found');
            fsError.code = 'ENOENT';
            
            const result = errorHandler.handleFileSystemError(fsError, 'read_file', '/test/path');

            expect(result.success).toBe(false);
            expect(result.canRecover).toBe(false);
            expect(result.userMessage).toContain('File not found');
        });

        it('should track error statistics', () => {
            const error1 = new Error('Error 1');
            const error2 = new Error('Error 2');
            
            errorHandler.handleError(error1, 'context1');
            errorHandler.handleError(error2, 'context1');
            errorHandler.handleError(error1, 'context2');

            const stats = errorHandler.getErrorStatistics();
            expect(stats.totalErrors).toBe(3);
            expect(stats.errorsByContext.context1).toBe(2);
            expect(stats.errorsByContext.context2).toBe(1);
        });
    });

    describe('RetryManager', () => {
        it('should retry operations on retryable errors', async () => {
            let attempts = 0;
            const operation = async () => {
                attempts++;
                if (attempts < 3) {
                    const error = new Error('Temporary error');
                    error.code = 'ETIMEDOUT';
                    throw error;
                }
                return 'success';
            };

            const result = await retryManager.executeWithRetry(operation, { maxRetries: 3 });
            expect(result).toBe('success');
            expect(attempts).toBe(3);
        });

        it('should not retry on non-retryable errors', async () => {
            let attempts = 0;
            const operation = async () => {
                attempts++;
                const error = new Error('Client error');
                error.response = { status: 400 };
                throw error;
            };

            try {
                await retryManager.executeAPICall(operation);
            } catch (error) {
                expect(error.message).toBe('Client error');
                expect(attempts).toBe(1);
            }
        });

        it('should calculate exponential backoff delays correctly', async () => {
            const delays = [];
            let attempts = 0;
            
            const operation = async () => {
                attempts++;
                if (attempts <= 3) {
                    const error = new Error('Retry error');
                    error.code = 'ETIMEDOUT';
                    throw error;
                }
                return 'success';
            };

            const startTime = Date.now();
            
            try {
                await retryManager.executeWithRetry(operation, {
                    maxRetries: 3,
                    baseDelay: 100,
                    jitter: false,
                    onRetry: (error, attempt, delay) => {
                        delays.push(delay);
                    }
                });
            } catch (error) {
                // Expected to fail after retries
            }

            expect(delays.length).toBe(3);
            expect(delays[0]).toBe(100); // First retry: 100ms
            expect(delays[1]).toBe(200); // Second retry: 200ms
            expect(delays[2]).toBe(400); // Third retry: 400ms
        });
    });

    describe('Logger', () => {
        it('should log messages at appropriate levels', () => {
            // This is a basic test - in a real scenario you'd mock console or file output
            expect(() => {
                Logger.info('Test info message');
                Logger.warn('Test warning message');
                Logger.error('Test error message');
                Logger.debug('Test debug message');
            }).not.toThrow();
        });

        it('should log API calls correctly', () => {
            expect(() => {
                Logger.logAPICall('POST', '/api/test', 200, 150, { test: 'data' });
                Logger.logAPICall('GET', '/api/error', 500, 300, { error: 'Server error' });
            }).not.toThrow();
        });

        it('should log performance metrics', () => {
            expect(() => {
                Logger.logPerformance('test_operation', 1500, { items: 10 });
                Logger.logPerformance('slow_operation', 6000, { warning: true });
            }).not.toThrow();
        });
    });

    describe('Integration Tests', () => {
        it('should handle complex error scenarios', async () => {
            let attempts = 0;
            const complexOperation = async () => {
                attempts++;
                
                if (attempts === 1) {
                    // First attempt: network error
                    const error = new Error('Network error');
                    error.code = 'ECONNREFUSED';
                    throw error;
                } else if (attempts === 2) {
                    // Second attempt: timeout
                    const error = new Error('Timeout');
                    error.code = 'ETIMEDOUT';
                    throw error;
                } else if (attempts === 3) {
                    // Third attempt: success
                    return 'operation completed';
                }
            };

            const result = await retryManager.executeWithRetry(complexOperation, {
                maxRetries: 3,
                baseDelay: 10, // Short delay for testing
                onRetry: (error, attempt, delay) => {
                    const errorResponse = errorHandler.handleError(error, 'complex_operation');
                    expect(errorResponse.canRetry).toBe(true);
                }
            });

            expect(result).toBe('operation completed');
            expect(attempts).toBe(3);
        });
    });
});