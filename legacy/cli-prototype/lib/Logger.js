import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/**
 * Logging system for the OSCE Medical App
 * Provides structured logging with different levels and file output
 */
class Logger {
    constructor(options = {}) {
        this.logLevel = options.logLevel || process.env.LOG_LEVEL || 'info';
        this.logToFile = options.logToFile !== false; // Default to true
        this.logToConsole = options.logToConsole !== false; // Default to true
        this.logDirectory = options.logDirectory || path.join(__dirname, '../logs');
        this.maxLogFiles = options.maxLogFiles || 5;
        this.maxLogSize = options.maxLogSize || 10 * 1024 * 1024; // 10MB
        
        this.levels = {
            error: 0,
            warn: 1,
            info: 2,
            debug: 3
        };

        this.currentLevel = this.levels[this.logLevel] || this.levels.info;
        
        // Ensure log directory exists
        if (this.logToFile) {
            this._ensureLogDirectory();
        }
    }

    /**
     * Log an error message
     * @param {string} message - Log message
     * @param {Object} metadata - Additional metadata
     * @param {string} context - Context/module name
     */
    error(message, metadata = {}, context = 'app') {
        this._log('error', message, metadata, context);
    }

    /**
     * Log a warning message
     * @param {string} message - Log message
     * @param {Object} metadata - Additional metadata
     * @param {string} context - Context/module name
     */
    warn(message, metadata = {}, context = 'app') {
        this._log('warn', message, metadata, context);
    }

    /**
     * Log an info message
     * @param {string} message - Log message
     * @param {Object} metadata - Additional metadata
     * @param {string} context - Context/module name
     */
    info(message, metadata = {}, context = 'app') {
        this._log('info', message, metadata, context);
    }

    /**
     * Log a debug message
     * @param {string} message - Log message
     * @param {Object} metadata - Additional metadata
     * @param {string} context - Context/module name
     */
    debug(message, metadata = {}, context = 'app') {
        this._log('debug', message, metadata, context);
    }

    /**
     * Log API requests and responses
     * @param {string} method - HTTP method
     * @param {string} url - Request URL
     * @param {number} status - Response status
     * @param {number} duration - Request duration in ms
     * @param {Object} metadata - Additional metadata
     */
    logAPICall(method, url, status, duration, metadata = {}) {
        const level = status >= 400 ? 'error' : 'info';
        const message = `API ${method} ${url} - ${status} (${duration}ms)`;
        
        this._log(level, message, {
            type: 'api_call',
            method,
            url,
            status,
            duration,
            ...metadata
        }, 'api');
    }

    /**
     * Log user actions for analytics
     * @param {string} action - User action
     * @param {string} userId - User identifier (optional)
     * @param {Object} metadata - Additional metadata
     */
    logUserAction(action, userId = null, metadata = {}) {
        this._log('info', `User action: ${action}`, {
            type: 'user_action',
            action,
            userId,
            ...metadata
        }, 'user');
    }

    /**
     * Log performance metrics
     * @param {string} operation - Operation name
     * @param {number} duration - Duration in ms
     * @param {Object} metadata - Additional metadata
     */
    logPerformance(operation, duration, metadata = {}) {
        const level = duration > 5000 ? 'warn' : 'info'; // Warn if over 5 seconds
        const message = `Performance: ${operation} took ${duration}ms`;
        
        this._log(level, message, {
            type: 'performance',
            operation,
            duration,
            ...metadata
        }, 'performance');
    }

    /**
     * Log case-related events
     * @param {string} event - Event type
     * @param {string} caseId - Case identifier
     * @param {Object} metadata - Additional metadata
     */
    logCaseEvent(event, caseId, metadata = {}) {
        this._log('info', `Case event: ${event} for case ${caseId}`, {
            type: 'case_event',
            event,
            caseId,
            ...metadata
        }, 'case');
    }

    /**
     * Get recent log entries
     * @param {number} count - Number of entries to return
     * @param {string} level - Minimum log level
     * @returns {Array} Recent log entries
     */
    getRecentLogs(count = 50, level = 'info') {
        // This is a simplified implementation
        // In production, you might want to read from log files
        return this.recentLogs ? this.recentLogs.slice(-count) : [];
    }

    /**
     * Rotate log files if they exceed size limit
     */
    rotateLogs() {
        if (!this.logToFile) return;

        try {
            const logFile = this._getCurrentLogFile();
            if (fs.existsSync(logFile)) {
                const stats = fs.statSync(logFile);
                if (stats.size > this.maxLogSize) {
                    this._rotateLogFile(logFile);
                }
            }
        } catch (error) {
            console.error('Error rotating logs:', error.message);
        }
    }

    /**
     * Clean up old log files
     */
    cleanupOldLogs() {
        if (!this.logToFile) return;

        try {
            const files = fs.readdirSync(this.logDirectory)
                .filter(file => file.startsWith('osce-') && file.endsWith('.log'))
                .map(file => ({
                    name: file,
                    path: path.join(this.logDirectory, file),
                    mtime: fs.statSync(path.join(this.logDirectory, file)).mtime
                }))
                .sort((a, b) => b.mtime - a.mtime);

            // Keep only the most recent files
            if (files.length > this.maxLogFiles) {
                const filesToDelete = files.slice(this.maxLogFiles);
                for (const file of filesToDelete) {
                    fs.unlinkSync(file.path);
                }
            }
        } catch (error) {
            console.error('Error cleaning up old logs:', error.message);
        }
    }

    // Private methods

    /**
     * Core logging method
     * @param {string} level - Log level
     * @param {string} message - Log message
     * @param {Object} metadata - Additional metadata
     * @param {string} context - Context/module name
     * @private
     */
    _log(level, message, metadata, context) {
        if (this.levels[level] > this.currentLevel) {
            return; // Skip if below current log level
        }

        const logEntry = {
            timestamp: new Date().toISOString(),
            level: level.toUpperCase(),
            context,
            message,
            metadata: Object.keys(metadata).length > 0 ? metadata : undefined
        };

        // Log to console
        if (this.logToConsole) {
            this._logToConsole(logEntry);
        }

        // Log to file
        if (this.logToFile) {
            this._logToFile(logEntry);
        }

        // Store recent logs in memory (for debugging)
        if (!this.recentLogs) this.recentLogs = [];
        this.recentLogs.push(logEntry);
        if (this.recentLogs.length > 100) {
            this.recentLogs.shift();
        }
    }

    /**
     * Log to console with formatting
     * @param {Object} logEntry - Log entry
     * @private
     */
    _logToConsole(logEntry) {
        const timestamp = new Date(logEntry.timestamp).toLocaleTimeString();
        const levelColor = this._getLevelColor(logEntry.level);
        const contextColor = '\x1b[36m'; // Cyan
        const resetColor = '\x1b[0m';

        let output = `${levelColor}[${logEntry.level}]${resetColor} `;
        output += `${timestamp} `;
        output += `${contextColor}${logEntry.context}${resetColor}: `;
        output += logEntry.message;

        if (logEntry.metadata && this.currentLevel >= this.levels.debug) {
            output += `\n  Metadata: ${JSON.stringify(logEntry.metadata, null, 2)}`;
        }

        console.log(output);
    }

    /**
     * Log to file
     * @param {Object} logEntry - Log entry
     * @private
     */
    _logToFile(logEntry) {
        try {
            const logFile = this._getCurrentLogFile();
            const logLine = JSON.stringify(logEntry) + '\n';
            
            fs.appendFileSync(logFile, logLine);
            
            // Check if rotation is needed
            if (Math.random() < 0.01) { // Check 1% of the time to avoid constant file stats
                this.rotateLogs();
            }
        } catch (error) {
            console.error('Error writing to log file:', error.message);
        }
    }

    /**
     * Get color code for log level
     * @param {string} level - Log level
     * @returns {string} ANSI color code
     * @private
     */
    _getLevelColor(level) {
        const colors = {
            ERROR: '\x1b[31m', // Red
            WARN: '\x1b[33m',  // Yellow
            INFO: '\x1b[32m',  // Green
            DEBUG: '\x1b[37m'  // White
        };
        return colors[level] || '\x1b[37m';
    }

    /**
     * Ensure log directory exists
     * @private
     */
    _ensureLogDirectory() {
        try {
            if (!fs.existsSync(this.logDirectory)) {
                fs.mkdirSync(this.logDirectory, { recursive: true });
            }
        } catch (error) {
            console.error('Error creating log directory:', error.message);
            this.logToFile = false; // Disable file logging if directory can't be created
        }
    }

    /**
     * Get current log file path
     * @returns {string} Log file path
     * @private
     */
    _getCurrentLogFile() {
        const date = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
        return path.join(this.logDirectory, `osce-${date}.log`);
    }

    /**
     * Rotate log file
     * @param {string} logFile - Current log file path
     * @private
     */
    _rotateLogFile(logFile) {
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const rotatedFile = logFile.replace('.log', `-${timestamp}.log`);
        
        try {
            fs.renameSync(logFile, rotatedFile);
        } catch (error) {
            console.error('Error rotating log file:', error.message);
        }
    }
}

// Create singleton instance
const logger = new Logger();

export default logger;