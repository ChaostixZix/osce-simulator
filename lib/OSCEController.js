import CaseManager from './CaseManager.js';
import PatientSimulator from './PatientSimulator.js';
import PerformanceTracker from './PerformanceTracker.js';
import ScoringEngine from './ScoringEngine.js';
import ErrorHandler from './ErrorHandler.js';
import Logger from './Logger.js';

/**
 * OSCEController coordinates all OSCE components and manages the application flow
 * Integrates case management, patient simulation, performance tracking, and scoring
 */
class OSCEController {
    constructor(apiConfig = {}) {
        // Initialize components
        this.caseManager = new CaseManager();
        this.patientSimulator = new PatientSimulator(apiConfig);
        this.performanceTracker = new PerformanceTracker();
        this.scoringEngine = new ScoringEngine();
        
        // Error handling and logging
        this.errorHandler = new ErrorHandler();
        this.logger = Logger;
        
        // Application state
        this.currentCase = null;
        this.isActive = false;
        this.sessionStartTime = null;
        this.availableCases = [];
        
        // User interface state
        this.awaitingCaseSelection = false;
        this.showingResults = false;
        
        // Error recovery state
        this.lastError = null;
        this.errorCount = 0;
        this.maxConsecutiveErrors = 5;
    }

    /**
     * Initialize OSCE application and display case selection
     * @returns {Promise<string>} Welcome message with case options
     */
    async startOSCE() {
        const startTime = Date.now();
        
        try {
            this.logger.info('Initializing OSCE Medical Training System', {}, 'osce_controller');
            
            // Reset error state
            this.lastError = null;
            this.errorCount = 0;
            
            // Load available cases with error handling
            this.availableCases = await this.caseManager.loadAvailableCases();
            
            if (this.availableCases.length === 0) {
                const message = 'No OSCE cases are currently available. Please check the cases directory and ensure case files are properly formatted.';
                this.logger.warn('No cases available during startup', { casesDirectory: this.caseManager.casesDirectory }, 'osce_controller');
                return message;
            }

            this.awaitingCaseSelection = true;
            this.isActive = true;

            const duration = Date.now() - startTime;
            this.logger.info(
                `OSCE system initialized successfully`,
                { 
                    availableCases: this.availableCases.length,
                    duration,
                    caseIds: this.availableCases.map(c => c.id)
                },
                'osce_controller'
            );

            return this._formatCaseSelectionMessage();
            
        } catch (error) {
            const duration = Date.now() - startTime;
            const errorResponse = this.errorHandler.handleError(error, 'osce_startup');
            
            this.logger.error(
                'Failed to start OSCE system',
                { 
                    error: error.message,
                    duration,
                    stack: error.stack
                },
                'osce_controller'
            );

            this.lastError = error;
            this.errorCount++;

            return `Failed to start OSCE system: ${errorResponse.userMessage}`;
        }
    }

    /**
     * Select and load a specific case
     * @param {string} caseId - The case ID to load
     * @returns {Promise<string>} Case initialization message
     */
    async selectCase(caseId) {
        try {
            if (!this.isActive) {
                return 'OSCE system not initialized. Please start the system first.';
            }

            if (!caseId) {
                return 'Please specify a case ID. ' + this._formatCaseSelectionMessage();
            }

            // Load case data
            const caseData = this.caseManager.getCaseById(caseId);
            if (!caseData) {
                return `Case "${caseId}" not found. Available cases:\n${this._formatCaseList()}`;
            }

            // Initialize all components with case data
            this.currentCase = caseData;
            this.sessionStartTime = new Date();
            this.awaitingCaseSelection = false;
            this.showingResults = false;

            // Initialize patient simulation
            this.patientSimulator.initializePatient(caseData);
            
            // Initialize performance tracking
            this.performanceTracker.initializeChecklist(caseData);

            console.log(`Case "${caseId}" loaded successfully`);
            
            return this._formatCaseStartMessage(caseData);
            
        } catch (error) {
            console.error('Error selecting case:', error.message);
            return `Failed to load case: ${error.message}`;
        }
    }

    /**
     * Process user input during active case
     * @param {string} input - User's input/question
     * @returns {Promise<string>} Response from patient or system
     */
    async processUserInput(input) {
        const startTime = Date.now();
        
        try {
            // Validate input
            if (!input || typeof input !== 'string' || input.trim().length === 0) {
                return 'Please enter a valid question or command.';
            }

            const trimmedInput = input.trim();
            
            this.logger.logUserAction('user_input', null, { 
                input: trimmedInput.substring(0, 100),
                inputLength: trimmedInput.length,
                isActive: this.isActive,
                awaitingCaseSelection: this.awaitingCaseSelection,
                currentCase: this.currentCase?.id
            });

            if (!this.isActive) {
                return 'OSCE system not active. Type "start osce" to begin.';
            }

            if (this.awaitingCaseSelection) {
                return await this._handleCaseSelection(trimmedInput);
            }

            if (this.showingResults) {
                return this._handleResultsMode(trimmedInput);
            }

            if (!this.currentCase) {
                return 'No case is currently active. Please select a case first.';
            }

            // Handle special commands
            const specialResponse = await this._handleSpecialCommands(trimmedInput);
            if (specialResponse) {
                return specialResponse;
            }

            // Determine request type
            const requestType = this._categorizeInput(trimmedInput);
            
            // Track the action with error handling
            let triggeredItems = [];
            try {
                triggeredItems = this.performanceTracker.trackAction(trimmedInput, requestType);
            } catch (trackingError) {
                this.logger.error(
                    'Error tracking user action',
                    { error: trackingError.message, input: trimmedInput.substring(0, 50) },
                    'osce_controller'
                );
                // Continue without tracking - don't fail the entire interaction
            }
            
            // Generate patient response with comprehensive error handling
            let patientResponse;
            try {
                patientResponse = await this.patientSimulator.respondAsPatient(trimmedInput, requestType);
                
                // Reset error count on successful response
                this.errorCount = 0;
                
            } catch (simulatorError) {
                this.errorCount++;
                
                const errorResponse = this.errorHandler.handleError(simulatorError, 'patient_simulation');
                
                this.logger.error(
                    'Patient simulator error',
                    { 
                        error: simulatorError.message,
                        input: trimmedInput.substring(0, 100),
                        requestType,
                        errorCount: this.errorCount
                    },
                    'osce_controller'
                );

                // Check if we've had too many consecutive errors
                if (this.errorCount >= this.maxConsecutiveErrors) {
                    return this._handleCriticalError();
                }

                patientResponse = errorResponse.userMessage;
            }
            
            // Add tracking feedback if items were completed
            let trackingFeedback = '';
            if (triggeredItems.length > 0) {
                trackingFeedback = `\n\n[Checklist items completed: ${triggeredItems.length}]`;
            }

            const duration = Date.now() - startTime;
            this.logger.logPerformance('process_user_input', duration, {
                requestType,
                triggeredItems: triggeredItems.length,
                responseLength: patientResponse.length
            });

            return patientResponse + trackingFeedback;
            
        } catch (error) {
            const duration = Date.now() - startTime;
            this.errorCount++;
            
            const errorResponse = this.errorHandler.handleError(error, 'user_input_processing');
            
            this.logger.error(
                'Critical error processing user input',
                { 
                    error: error.message,
                    input: input?.substring(0, 100),
                    duration,
                    errorCount: this.errorCount,
                    stack: error.stack
                },
                'osce_controller'
            );

            this.lastError = error;

            // Check if we've had too many consecutive errors
            if (this.errorCount >= this.maxConsecutiveErrors) {
                return this._handleCriticalError();
            }

            return `Sorry, I encountered an error processing your request: ${errorResponse.userMessage}`;
        }
    }

    /**
     * End current case and display results
     * @returns {Promise<string>} Final results and scoring
     */
    async endCase() {
        try {
            if (!this.currentCase) {
                return 'No active case to end.';
            }

            // Get performance data
            const performanceData = this.performanceTracker.getPerformanceData();
            
            // Calculate score
            const scoreResult = this.scoringEngine.calculateScore(
                performanceData, 
                this.currentCase.checklist
            );
            
            // Generate feedback
            const feedback = this.scoringEngine.generateFeedback(
                performanceData, 
                this.currentCase.checklist
            );

            // Update state
            this.showingResults = true;
            
            return this._formatFinalResults(scoreResult, feedback);
            
        } catch (error) {
            console.error('Error ending case:', error.message);
            return `Error generating results: ${error.message}`;
        }
    }

    /**
     * Get current progress without ending the case
     * @returns {string} Current progress summary
     */
    getCurrentProgress() {
        if (!this.currentCase) {
            return 'No active case.';
        }

        const completionStatus = this.performanceTracker.getCompletionStatus();
        return this._formatProgressSummary(completionStatus);
    }

    /**
     * Get help information
     * @returns {string} Help text
     */
    getHelp() {
        if (!this.isActive) {
            return `
╔══════════════════════════════════════════════════════════════╗
║                    OSCE System Help                         ║
╚══════════════════════════════════════════════════════════════╝

🚀 **Getting Started:**
• Type "start osce" to enter OSCE training mode
• Select a case by typing its ID (e.g., "stemi-001")
• Begin interacting with the AI patient

📚 **Available Commands:**
• "list" or "cases" - Show available cases
• "system status" - Check system health
• "health check" - Run diagnostics
• "exit osce" - Return to chat mode

💡 **Tips:**
• Start with the STEMI case for cardiac emergency practice
• Each case takes 15-30 minutes to complete
• Focus on systematic clinical approach
• Review feedback carefully to improve

📖 **Documentation:**
• Check README.md for complete setup guide
• See docs/USER_GUIDE.md for detailed instructions
• Visit docs/QUICK_START.md for 5-minute tutorial`;
        }

        if (this.awaitingCaseSelection) {
            return `
╔══════════════════════════════════════════════════════════════╗
║                    Case Selection Help                      ║
╚══════════════════════════════════════════════════════════════╝

🎯 **Selecting a Case:**
• Type the case ID exactly as shown (e.g., "stemi-001")
• Case IDs are case-sensitive

📋 **Available Commands:**
• "list" - Show all available cases again
• "case info [id]" - Get details about a specific case
• "exit osce" - Return to chat mode

💡 **Case Recommendations:**
• **stemi-001**: Great for emergency cardiology practice
• **New to OSCE?**: Start with stemi-001 for comprehensive training
• **Time Management**: Most cases take 15-30 minutes

🎓 **Learning Approach:**
• Read the chief complaint carefully
• Plan your approach before starting
• Focus on systematic history and examination
• Don't rush - thoroughness is rewarded`;
        }

        if (this.currentCase) {
            const caseId = this.currentCase.id || 'current case';
            const duration = this.sessionStartTime ? 
                Math.round((Date.now() - this.sessionStartTime) / 1000 / 60) : 0;
            
            return `
╔══════════════════════════════════════════════════════════════╗
║                    Active Case Help                         ║
║  Case: ${caseId.padEnd(48)} │
║  Duration: ${duration} minutes${' '.repeat(40 - duration.toString().length)} │
╚══════════════════════════════════════════════════════════════╝

🗣️ **Patient Interaction:**
• Ask open-ended questions: "Can you tell me about your pain?"
• Be specific: "When did the pain start?" "What makes it worse?"
• Show empathy: "I understand this must be concerning"

🔍 **Physical Examination:**
• Request examinations: "I'd like to check your vital signs"
• Be systematic: "Let me examine your heart and lungs"
• Ask for specific findings: "What are the heart sounds?"

🧪 **Investigations:**
• Order tests clearly: "I need an ECG" or "Please get cardiac enzymes"
• Prioritize by urgency: ECG first in chest pain cases
• Ask for interpretation: "What does the ECG show?"

📊 **Progress Tracking:**
• "score" or "progress" - See current performance
• "case info" - Review case objectives and details
• Track critical items - these carry the most points

⚡ **Emergency Cases:**
• Time is critical in emergency presentations
• Focus on life-threatening conditions first
• Don't forget basic interventions (oxygen, IV access)

🎯 **Scoring Tips:**
• Complete all critical checklist items
• Be systematic in your approach
• Provide clear diagnostic reasoning
• Suggest appropriate management

📋 **Available Commands:**
• "score" - Current progress and performance
• "case info" - Case details and learning objectives
• "end case" - Complete case and get final results
• "new case" - Start a different case
• "exit osce" - Return to chat mode

💡 **Common Mistakes to Avoid:**
• Don't skip vital signs in emergency cases
• Don't forget to order ECG for chest pain
• Don't provide vague diagnoses
• Don't rush through the history`;
        }

        return `
╔══════════════════════════════════════════════════════════════╗
║                      General Help                           ║
╚══════════════════════════════════════════════════════════════╝

Type "start osce" to begin medical case training, or use these commands:
• "help" - Context-sensitive help
• "system status" - Check system health
• "stats" - View session statistics
• "exit" - Quit application`;
    }

    /**
     * Reset the controller for a new session
     */
    reset() {
        this.currentCase = null;
        this.isActive = false;
        this.sessionStartTime = null;
        this.awaitingCaseSelection = false;
        this.showingResults = false;
        this.performanceTracker.reset();
        this.availableCases = [];
    }

    /**
     * Get current application state
     * @returns {Object} Current state information
     */
    getState() {
        return {
            isActive: this.isActive,
            currentCase: this.currentCase ? this.currentCase.id : null,
            awaitingCaseSelection: this.awaitingCaseSelection,
            showingResults: this.showingResults,
            availableCases: this.availableCases.length,
            sessionDuration: this.sessionStartTime ? new Date() - this.sessionStartTime : 0
        };
    }

    /**
     * Get case information for display
     * @returns {string} Formatted case information
     */
    getCaseInfo() {
        return this._formatCaseInfo();
    }

    /**
     * List available cases
     * @returns {string} Formatted case list
     */
    listCases() {
        if (this.availableCases.length === 0) {
            return 'No cases are currently available.';
        }
        return this._formatCaseList();
    }

    // Private helper methods

    /**
     * Format case selection message
     * @returns {string} Formatted message
     * @private
     */
    _formatCaseSelectionMessage() {
        let message = `\n╔══════════════════════════════════════════════════════════════╗\n`;
        message += `║                    OSCE Case Selection                      ║\n`;
        message += `╚══════════════════════════════════════════════════════════════╝\n`;
        message += `\n🎯 Welcome to OSCE Medical Training! Please select a case to begin.\n\n`;
        message += `📋 Available Cases:\n`;
        message += this._formatCaseList();
        message += `\n📝 Instructions:\n`;
        message += `   • Type the Case ID (e.g., "stemi-001") to select a case\n`;
        message += `   • Type "list" to see this list again\n`;
        message += `   • Type "help" for more information\n`;
        
        return message;
    }

    /**
     * Format case list
     * @returns {string} Formatted case list
     * @private
     */
    _formatCaseList() {
        return this.availableCases.map((caseInfo, index) => {
            const number = (index + 1).toString().padStart(2, ' ');
            return `   ${number}. 🏥 ${caseInfo.id}\n      📋 ${caseInfo.title}\n      📝 ${caseInfo.description}`;
        }).join('\n\n');
    }

    /**
     * Format case start message
     * @param {Object} caseData - Case data
     * @returns {string} Formatted start message
     * @private
     */
    _formatCaseStartMessage(caseData) {
        let message = `\n╔══════════════════════════════════════════════════════════════╗\n`;
        message += `║                      Case Started                           ║\n`;
        message += `╚══════════════════════════════════════════════════════════════╝\n`;
        message += `\n🏥 ${caseData.title}\n`;
        message += `\n👤 Patient Information:\n`;
        message += `   • Name: ${caseData.patientInfo.name}\n`;
        message += `   • Age: ${caseData.patientInfo.age} years old\n`;
        message += `   • Gender: ${caseData.patientInfo.gender}\n`;
        if (caseData.patientInfo.occupation) {
            message += `   • Occupation: ${caseData.patientInfo.occupation}\n`;
        }
        message += `\n🗣️  Chief Complaint: "${caseData.chiefComplaint}"\n`;
        message += `\n┌─ Patient Interaction Started ────────────────────────────────┐\n`;
        message += `│ The patient is ready to speak with you. You may begin taking │\n`;
        message += `│ their history, performing examinations, and ordering tests.  │\n`;
        message += `└───────────────────────────────────────────────────────────────┘\n`;
        message += `\n⚡ Quick Commands:\n`;
        message += `   • "score" or "progress" - View current progress\n`;
        message += `   • "case info" - Review case details\n`;
        message += `   • "end case" - Complete case and get results\n`;
        message += `   • "help" - Show all available commands\n`;
        
        return message;
    }

    /**
     * Handle case selection input
     * @param {string} input - User input
     * @returns {Promise<string>} Response
     * @private
     */
    async _handleCaseSelection(input) {
        const inputLower = input.toLowerCase().trim();
        
        if (inputLower === 'list') {
            return this._formatCaseSelectionMessage();
        }
        
        if (inputLower === 'help') {
            return this.getHelp();
        }
        
        // Try to select the case
        return await this.selectCase(input.trim());
    }

    /**
     * Handle special commands during active case
     * @param {string} input - User input
     * @returns {string|null} Response or null if not a special command
     * @private
     */
    _handleSpecialCommands(input) {
        const inputLower = input.toLowerCase().trim();
        
        if (inputLower === 'score' || inputLower === 'progress') {
            return this.getCurrentProgress();
        }
        
        if (inputLower === 'help') {
            return this.getHelp();
        }
        
        if (inputLower === 'case info') {
            return this._formatCaseInfo();
        }
        
        if (inputLower === 'end case' || inputLower === 'finish') {
            return this.endCase();
        }
        
        return null;
    }

    /**
     * Handle input when showing results
     * @param {string} input - User input
     * @returns {string} Response
     * @private
     */
    _handleResultsMode(input) {
        const inputLower = input.toLowerCase().trim();
        
        if (inputLower === 'new case' || inputLower === 'restart') {
            this.currentCase = null;
            this.showingResults = false;
            this.awaitingCaseSelection = true;
            return this._formatCaseSelectionMessage();
        }
        
        if (inputLower === 'help') {
            return `Results Mode Help:
- Type "new case" to select another case
- Type "restart" to return to case selection
- Review your performance above`;
        }
        
        return 'Case completed. Type "new case" to select another case or "help" for options.';
    }

    /**
     * Categorize user input to determine request type
     * @param {string} input - User input
     * @returns {string} Request type
     * @private
     */
    _categorizeInput(input) {
        const inputLower = input.toLowerCase();
        
        // History taking patterns
        if (inputLower.includes('tell me') || inputLower.includes('describe') || 
            inputLower.includes('when') || inputLower.includes('how') ||
            inputLower.includes('pain') || inputLower.includes('symptom')) {
            return 'history';
        }
        
        // Physical examination patterns
        if (inputLower.includes('examine') || inputLower.includes('check') || 
            inputLower.includes('listen') || inputLower.includes('feel') ||
            inputLower.includes('palpate') || inputLower.includes('auscult')) {
            return 'examination';
        }
        
        // Investigation patterns
        if (inputLower.includes('test') || inputLower.includes('lab') || 
            inputLower.includes('blood') || inputLower.includes('ecg') ||
            inputLower.includes('x-ray') || inputLower.includes('scan')) {
            return 'investigation';
        }
        
        return 'general';
    }

    /**
     * Format case information
     * @returns {string} Formatted case info
     * @private
     */
    _formatCaseInfo() {
        if (!this.currentCase) {
            return '❌ No active case.';
        }
        
        let info = `\n╔══════════════════════════════════════════════════════════════╗\n`;
        info += `║                      Case Information                       ║\n`;
        info += `╚══════════════════════════════════════════════════════════════╝\n`;
        info += `\n🏥 Title: ${this.currentCase.title}\n`;
        info += `📝 Description: ${this.currentCase.description}\n`;
        info += `🗣️  Chief Complaint: "${this.currentCase.chiefComplaint}"\n`;
        
        if (this.currentCase.learningObjectives && this.currentCase.learningObjectives.length > 0) {
            info += `\n🎯 Learning Objectives:\n`;
            this.currentCase.learningObjectives.forEach((obj, index) => {
                info += `   ${index + 1}. ${obj}\n`;
            });
        }
        
        // Add session info if available
        if (this.sessionStartTime) {
            const duration = Math.round((new Date() - this.sessionStartTime) / (1000 * 60));
            info += `\n⏱️  Session Duration: ${duration} minutes\n`;
        }
        
        return info;
    }

    /**
     * Format progress summary
     * @param {Object} completionStatus - Completion status from tracker
     * @returns {string} Formatted progress
     * @private
     */
    _formatProgressSummary(completionStatus) {
        const overallPercent = Math.round(completionStatus.overallCompletionRate);
        const criticalPercent = Math.round(completionStatus.criticalCompletionRate);
        const sessionMinutes = Math.round(completionStatus.sessionDuration / (1000 * 60));
        
        let summary = `\n╔══════════════════════════════════════════════════════════════╗\n`;
        summary += `║                     Progress Report                         ║\n`;
        summary += `╚══════════════════════════════════════════════════════════════╝\n`;
        
        // Progress bars
        const overallBar = this._createProgressBar(overallPercent);
        const criticalBar = this._createProgressBar(criticalPercent);
        
        summary += `\n📊 Overall Progress: ${overallPercent}% ${overallBar}\n`;
        summary += `   Completed: ${completionStatus.completedItems}/${completionStatus.totalItems} items\n`;
        summary += `\n🔥 Critical Items: ${criticalPercent}% ${criticalBar}\n`;
        summary += `   Completed: ${completionStatus.completedCriticalItems}/${completionStatus.criticalItems} critical items\n`;
        
        summary += `\n📋 Category Breakdown:\n`;
        for (const [categoryName, category] of Object.entries(completionStatus.categories)) {
            const categoryPercent = Math.round(category.completionRate);
            const categoryBar = this._createProgressBar(categoryPercent, 20);
            summary += `   • ${categoryName}: ${categoryPercent}% ${categoryBar}\n`;
            summary += `     (${category.completedItems}/${category.totalItems} items)\n`;
        }
        
        summary += `\n⏱️  Session Duration: ${sessionMinutes} minutes\n`;
        
        return summary;
    }

    /**
     * Create a visual progress bar
     * @param {number} percentage - Percentage complete (0-100)
     * @param {number} width - Width of progress bar (default 30)
     * @returns {string} Progress bar string
     * @private
     */
    _createProgressBar(percentage, width = 30) {
        const filled = Math.round((percentage / 100) * width);
        const empty = width - filled;
        const bar = '█'.repeat(filled) + '░'.repeat(empty);
        return `[${bar}]`;
    }

    /**
     * Format final results
     * @param {Object} scoreResult - Score calculation results
     * @param {Object} feedback - Detailed feedback
     * @returns {string} Formatted results
     * @private
     */
    _formatFinalResults(scoreResult, feedback) {
        const duration = Math.round(scoreResult.sessionDuration / (1000 * 60));
        const scoreBar = this._createProgressBar(scoreResult.percentageScore);
        const criticalBar = this._createProgressBar(scoreResult.criticalPercentage);
        
        let results = `\n╔══════════════════════════════════════════════════════════════╗\n`;
        results += `║                      CASE COMPLETED                         ║\n`;
        results += `╚══════════════════════════════════════════════════════════════╝\n`;
        results += `\n🏥 Case: ${this.currentCase.title}\n`;
        results += `⏱️  Duration: ${duration} minutes\n`;
        
        results += `\n╔══════════════════════════════════════════════════════════════╗\n`;
        results += `║                       FINAL SCORE                           ║\n`;
        results += `╚══════════════════════════════════════════════════════════════╝\n`;
        
        const statusIcon = scoreResult.passed ? '✅' : '❌';
        const statusText = scoreResult.passed ? 'PASSED' : 'FAILED';
        
        results += `\n${statusIcon} Overall Score: ${scoreResult.percentageScore}% (${scoreResult.grade})\n`;
        results += `   ${scoreBar}\n`;
        results += `\n🔥 Critical Items: ${scoreResult.criticalPercentage}%\n`;
        results += `   ${criticalBar}\n`;
        results += `\n📊 Status: ${statusText}\n`;
        
        results += `\n╔══════════════════════════════════════════════════════════════╗\n`;
        results += `║                    Category Breakdown                       ║\n`;
        results += `╚══════════════════════════════════════════════════════════════╝\n`;
        for (const [categoryName, score] of Object.entries(scoreResult.categoryScores)) {
            const categoryBar = this._createProgressBar(score.percentage, 25);
            results += `\n📋 ${categoryName}: ${score.percentage}%\n`;
            results += `   ${categoryBar}\n`;
            results += `   Completed: ${score.completedItems}/${score.totalItems} items\n`;
        }
        
        results += `\n╔══════════════════════════════════════════════════════════════╗\n`;
        results += `║                   Performance Summary                       ║\n`;
        results += `╚══════════════════════════════════════════════════════════════╝\n`;
        results += `\n${feedback.summary}\n`;
        
        if (feedback.strengths.length > 0) {
            results += `\n💪 Strengths:\n`;
            feedback.strengths.forEach(strength => {
                results += `   ✅ ${strength}\n`;
            });
        }
        
        if (feedback.areasForImprovement.length > 0) {
            results += `\n🎯 Areas for Improvement:\n`;
            feedback.areasForImprovement.forEach(area => {
                results += `   📈 ${area}\n`;
            });
        }
        
        if (feedback.learningRecommendations.length > 0) {
            results += `\n📚 Learning Recommendations:\n`;
            feedback.learningRecommendations.forEach((rec, index) => {
                results += `   ${index + 1}. ${rec}\n`;
            });
        }
        
        results += `\n🚀 Next Steps:\n`;
        feedback.nextSteps.forEach((step, index) => {
            results += `   ${index + 1}. ${step}\n`;
        });
        
        results += `\n┌─ Continue Training ───────────────────────────────────────────┐\n`;
        results += `│ • Type "new case" to try another case                        │\n`;
        results += `│ • Type "help" for available options                          │\n`;
        results += `└───────────────────────────────────────────────────────────────┘\n`;
        
        return results;
    }

    /**
     * Handle critical error situations
     * @returns {string} Critical error message
     * @private
     */
    _handleCriticalError() {
        this.logger.error(
            'Critical error threshold reached, entering safe mode',
            { 
                errorCount: this.errorCount,
                lastError: this.lastError?.message,
                currentCase: this.currentCase?.id
            },
            'osce_controller'
        );

        // Reset to safe state
        this.currentCase = null;
        this.awaitingCaseSelection = true;
        this.showingResults = false;
        this.errorCount = 0;

        return `⚠️  Multiple errors have occurred. The system has been reset to a safe state.

Please try:
1. Selecting a different case
2. Checking your internet connection
3. Restarting the application if problems persist

Type a case ID to select a case, or "help" for assistance.`;
    }

    /**
     * Get comprehensive system status for debugging
     * @returns {Object} System status
     */
    getSystemStatus() {
        return {
            isActive: this.isActive,
            currentCase: this.currentCase?.id || null,
            awaitingCaseSelection: this.awaitingCaseSelection,
            showingResults: this.showingResults,
            availableCases: this.availableCases.length,
            errorCount: this.errorCount,
            lastError: this.lastError?.message || null,
            sessionDuration: this.sessionStartTime ? new Date() - this.sessionStartTime : 0,
            
            // Component statistics
            caseManagerStats: this.caseManager.getLoadingStatistics(),
            patientSimulatorStats: this.patientSimulator.getAPIStatistics(),
            performanceTrackerStats: this.performanceTracker.getTrackingStatistics(),
            
            // Error handler statistics
            errorStats: this.errorHandler.getErrorStatistics()
        };
    }

    /**
     * Perform system health check
     * @returns {Object} Health check results
     */
    async performHealthCheck() {
        const healthCheck = {
            timestamp: new Date().toISOString(),
            overall: 'healthy',
            components: {},
            issues: []
        };

        try {
            // Check case manager
            const caseStats = this.caseManager.getLoadingStatistics();
            healthCheck.components.caseManager = {
                status: caseStats.successRate > 50 ? 'healthy' : 'degraded',
                successRate: caseStats.successRate,
                totalCases: this.availableCases.length
            };

            if (caseStats.successRate <= 50) {
                healthCheck.issues.push('Case loading success rate is low');
            }

            // Check patient simulator
            const apiStats = this.patientSimulator.getAPIStatistics();
            healthCheck.components.patientSimulator = {
                status: apiStats.successRate > 80 ? 'healthy' : 'degraded',
                successRate: apiStats.successRate,
                averageResponseTime: apiStats.averageResponseTime
            };

            if (apiStats.successRate <= 80) {
                healthCheck.issues.push('AI patient simulator has low success rate');
            }

            if (apiStats.averageResponseTime > 10000) {
                healthCheck.issues.push('AI responses are slow (>10s average)');
            }

            // Check error rates
            if (this.errorCount > 0) {
                healthCheck.components.errorHandling = {
                    status: this.errorCount < this.maxConsecutiveErrors ? 'warning' : 'critical',
                    consecutiveErrors: this.errorCount,
                    lastError: this.lastError?.message
                };

                if (this.errorCount >= this.maxConsecutiveErrors / 2) {
                    healthCheck.issues.push('High error rate detected');
                }
            }

            // Determine overall health
            if (healthCheck.issues.length > 2) {
                healthCheck.overall = 'critical';
            } else if (healthCheck.issues.length > 0) {
                healthCheck.overall = 'degraded';
            }

        } catch (error) {
            healthCheck.overall = 'critical';
            healthCheck.issues.push(`Health check failed: ${error.message}`);
        }

        return healthCheck;
    }

    /**
     * Get error recovery suggestions
     * @returns {Array} Recovery suggestions
     */
    getErrorRecoverySuggestions() {
        const suggestions = [];
        const systemStatus = this.getSystemStatus();

        if (systemStatus.errorCount > 0) {
            suggestions.push('Try restarting the current session');
            suggestions.push('Check your internet connection');
        }

        if (systemStatus.caseManagerStats.successRate < 100) {
            suggestions.push('Verify that all case files are properly formatted JSON');
            suggestions.push('Check file permissions in the cases directory');
        }

        if (systemStatus.patientSimulatorStats.successRate < 90) {
            suggestions.push('Verify API credentials are correct');
            suggestions.push('Check if the AI service is experiencing issues');
        }

        if (suggestions.length === 0) {
            suggestions.push('System appears to be functioning normally');
        }

        return suggestions;
    }
}

export default OSCEController;