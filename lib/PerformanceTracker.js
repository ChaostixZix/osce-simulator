/**
 * PerformanceTracker monitors student actions and maps them to checklist items
 * Provides comprehensive tracking of clinical performance during OSCE cases
 */
class PerformanceTracker {
    constructor() {
        this.caseId = null;
        this.checklist = null;
        this.checklistStatus = new Map(); // itemId -> { completed, timestamp, userInput }
        this.actionLog = []; // Comprehensive log of all student actions
        this.sessionStartTime = null;
        this.keywordMappings = new Map(); // Maps keywords to checklist item IDs
        this.itemCategories = new Map(); // itemId -> checklist category
    }

    /**
     * Initialize tracking for a specific case
     * @param {Object} caseData - Complete case data including checklist
     */
    initializeChecklist(caseData) {
        if (!caseData || !caseData.id) {
            throw new Error('Valid case data with ID is required');
        }

        if (!caseData.checklist) {
            throw new Error('Case data must include a checklist');
        }

        this.caseId = caseData.id;
        this.checklist = caseData.checklist;
        this.checklistStatus.clear();
        this.actionLog = [];
        this.sessionStartTime = new Date();
        this.keywordMappings.clear();
        this.itemCategories.clear();

        // Initialize all checklist items as not completed
        this._initializeChecklistItems();

        // Build keyword mappings for intelligent action tracking
        this._buildKeywordMappings();

        // Build item category map for filtering by action type
        for (const [categoryName, category] of Object.entries(this.checklist)) {
            if (category.items) {
                for (const item of category.items) {
                    this.itemCategories.set(item.id, categoryName);
                }
            }
        }

        console.log(`Performance tracking initialized for case: ${this.caseId}`);
    }

    /**
     * Track a student action and attempt to map it to checklist items
     * @param {string} userInput - The student's input/question
     * @param {string} actionType - Type of action (history, examination, investigation, etc.)
     * @returns {Array} Array of checklist items that were triggered by this action
     */
    trackAction(userInput, actionType = 'general') {
        if (!this.caseId) {
            throw new Error('Performance tracking not initialized. Call initializeChecklist first.');
        }

        const timestamp = new Date();
        const triggeredItems = [];

        // Log the action
        const actionEntry = {
            timestamp,
            input: userInput,
            actionType,
            triggeredItems: []
        };

        // Analyze input and map to checklist items
        const mappedItems = this._analyzeInputForChecklistItems(userInput, actionType);
        
        for (const itemId of mappedItems) {
            if (!this.checklistStatus.get(itemId)?.completed) {
                this.markChecklistItem(itemId, userInput, timestamp);
                triggeredItems.push(itemId);
            }
        }

        actionEntry.triggeredItems = triggeredItems;
        this.actionLog.push(actionEntry);

        return triggeredItems;
    }

    /**
     * Mark a specific checklist item as completed
     * @param {string} itemId - The checklist item ID
     * @param {string} userInput - The input that triggered this item (optional)
     * @param {Date} timestamp - When the item was completed (optional)
     */
    markChecklistItem(itemId, userInput = null, timestamp = null) {
        if (!this.caseId) {
            throw new Error('Performance tracking not initialized');
        }

        if (!this._checklistItemExists(itemId)) {
            console.warn(`Checklist item not found: ${itemId}`);
            return;
        }

        const completionTime = timestamp || new Date();
        
        this.checklistStatus.set(itemId, {
            completed: true,
            timestamp: completionTime,
            userInput: userInput,
            timeFromStart: completionTime - this.sessionStartTime
        });

        console.log(`Checklist item completed: ${itemId}`);
    }

    /**
     * Get current completion status of the checklist
     * @returns {Object} Detailed completion status
     */
    getCompletionStatus() {
        if (!this.caseId) {
            return { error: 'Performance tracking not initialized' };
        }

        const status = {
            caseId: this.caseId,
            sessionDuration: new Date() - this.sessionStartTime,
            totalItems: 0,
            completedItems: 0,
            criticalItems: 0,
            completedCriticalItems: 0,
            categories: {},
            completedItemsList: [],
            missingItemsList: []
        };

        // Analyze each category
        for (const [categoryName, category] of Object.entries(this.checklist)) {
            const categoryStatus = {
                weight: category.weight || 0,
                totalItems: category.items.length,
                completedItems: 0,
                criticalItems: 0,
                completedCriticalItems: 0,
                items: []
            };

            for (const item of category.items) {
                const itemStatus = this.checklistStatus.get(item.id);
                const isCompleted = itemStatus?.completed || false;
                
                status.totalItems++;
                
                if (item.critical) {
                    status.criticalItems++;
                    categoryStatus.criticalItems++;
                }

                if (isCompleted) {
                    status.completedItems++;
                    categoryStatus.completedItems++;
                    
                    if (item.critical) {
                        status.completedCriticalItems++;
                        categoryStatus.completedCriticalItems++;
                    }

                    status.completedItemsList.push({
                        id: item.id,
                        description: item.description,
                        category: categoryName,
                        critical: item.critical,
                        points: item.points,
                        completed: true,
                        completedAt: itemStatus.timestamp,
                        userInput: itemStatus.userInput
                    });
                } else {
                    status.missingItemsList.push({
                        id: item.id,
                        description: item.description,
                        category: categoryName,
                        critical: item.critical,
                        points: item.points
                    });
                }

                categoryStatus.items.push({
                    id: item.id,
                    description: item.description,
                    critical: item.critical,
                    points: item.points,
                    completed: isCompleted,
                    completedAt: itemStatus?.timestamp || null
                });
            }

            categoryStatus.completionRate = categoryStatus.totalItems > 0 ? 
                (categoryStatus.completedItems / categoryStatus.totalItems) * 100 : 0;

            status.categories[categoryName] = categoryStatus;
        }

        status.overallCompletionRate = status.totalItems > 0 ? 
            (status.completedItems / status.totalItems) * 100 : 0;
        
        status.criticalCompletionRate = status.criticalItems > 0 ? 
            (status.completedCriticalItems / status.criticalItems) * 100 : 0;

        return status;
    }

    /**
     * Get detailed action log
     * @returns {Array} Complete log of student actions
     */
    getDetailedLog() {
        return {
            caseId: this.caseId,
            sessionStartTime: this.sessionStartTime,
            sessionDuration: new Date() - this.sessionStartTime,
            totalActions: this.actionLog.length,
            actions: [...this.actionLog] // Return copy to prevent external modification
        };
    }

    /**
     * Get performance data for scoring
     * @returns {Object} Data needed for score calculation
     */
    getPerformanceData() {
        const completionStatus = this.getCompletionStatus();
        const actionLog = this.getDetailedLog();

        return {
            caseId: this.caseId,
            checklist: this.checklist,
            completionStatus,
            actionLog,
            checklistStatus: Object.fromEntries(this.checklistStatus)
        };
    }

    /**
     * Reset tracking for a new session
     */
    reset() {
        this.caseId = null;
        this.checklist = null;
        this.checklistStatus.clear();
        this.actionLog = [];
        this.sessionStartTime = null;
        this.keywordMappings.clear();
        this.itemCategories.clear();
    }

    /**
     * Initialize all checklist items as not completed
     * @private
     */
    _initializeChecklistItems() {
        for (const category of Object.values(this.checklist)) {
            if (category.items) {
                for (const item of category.items) {
                    this.checklistStatus.set(item.id, {
                        completed: false,
                        timestamp: null,
                        userInput: null,
                        timeFromStart: null
                    });
                }
            }
        }
    }

    /**
     * Build keyword mappings for intelligent action tracking
     * @private
     */
    _buildKeywordMappings() {
        // Define keyword patterns for different types of actions
        const patterns = {
            // History taking patterns
            'onset_timing': ['when', 'started', 'began', 'onset', 'timing', 'how long', 'duration'],
            'pain_character': ['pain', 'hurt', 'ache', 'describe', 'feel', 'character', 'quality', 'sharp', 'dull', 'crushing', 'pressure'],
            'associated_symptoms': ['other symptoms', 'anything else', 'associated', 'nausea', 'sweating', 'shortness', 'breath'],
            'past_medical_history': ['medical history', 'past', 'previous', 'conditions', 'diseases', 'health problems'],
            'medications': ['medications', 'medicine', 'pills', 'drugs', 'taking', 'prescribed'],
            'risk_factors': ['smoking', 'diabetes', 'hypertension', 'family history', 'risk factors'],
            
            // Physical examination patterns
            'vital_signs': ['vital signs', 'blood pressure', 'heart rate', 'temperature', 'pulse', 'bp', 'hr'],
            'cardiovascular_exam': ['heart', 'cardiac', 'cardiovascular', 'chest exam', 'listen to heart'],
            'respiratory_exam': ['lungs', 'breathing', 'respiratory', 'listen to chest', 'breath sounds'],
            
            // Investigation patterns
            'ecg': ['ecg', 'ekg', 'electrocardiogram', 'heart rhythm', 'cardiac rhythm'],
            'cardiac_enzymes': ['troponin', 'cardiac enzymes', 'heart enzymes', 'ck', 'ckmb'],
            'basic_labs': ['blood work', 'labs', 'blood test', 'cbc', 'basic metabolic'],
            'chest_xray': ['chest x-ray', 'chest xray', 'cxr', 'chest film'],
            
            // Diagnosis patterns
            'primary_diagnosis': ['diagnosis', 'think', 'suspect', 'stemi', 'heart attack', 'myocardial infarction'],
            'differential': ['differential', 'other possibilities', 'rule out', 'consider'],
            
            // Management patterns
            'emergency_treatment': ['treatment', 'manage', 'aspirin', 'nitroglycerin', 'oxygen', 'emergency'],
            'cardiology_consult': ['cardiology', 'cardiologist', 'consult', 'specialist', 'refer']
        };

        // Build reverse mapping from keywords to item IDs
        for (const [itemId, keywords] of Object.entries(patterns)) {
            for (const keyword of keywords) {
                if (!this.keywordMappings.has(keyword)) {
                    this.keywordMappings.set(keyword, []);
                }
                this.keywordMappings.get(keyword).push(itemId);
            }
        }
    }

    _actionTypeMatchesCategory(actionType, category) {
        const mapping = {
            history: 'historyTaking',
            examination: 'physicalExamination',
            investigation: 'investigations',
            diagnosis: 'diagnosis',
            management: 'management'
        };
        return mapping[actionType] === category;
    }

    /**
     * Analyze user input to identify relevant checklist items
     * @param {string} userInput - The student's input
     * @param {string} actionType - Type of action
     * @returns {Array} Array of checklist item IDs that match the input
     * @private
     */
    _analyzeInputForChecklistItems(userInput, actionType) {
        const matchedItems = new Set();
        const inputLower = userInput.toLowerCase();

        // Check against keyword mappings
        for (const [keyword, itemIds] of this.keywordMappings.entries()) {
            if (inputLower.includes(keyword)) {
                for (const itemId of itemIds) {
                    if (this._checklistItemExists(itemId)) {
                        const category = this.itemCategories.get(itemId);
                        if (this._actionTypeMatchesCategory(actionType, category)) {
                            matchedItems.add(itemId);
                        }
                    }
                }
            }
        }

        return Array.from(matchedItems);
    }

    /**
     * Check if a checklist item exists
     * @param {string} itemId - The item ID to check
     * @returns {boolean} True if item exists
     * @private
     */
    _checklistItemExists(itemId) {
        for (const category of Object.values(this.checklist)) {
            if (category.items) {
                for (const item of category.items) {
                    if (item.id === itemId) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Get statistics about tracking performance
     * @returns {Object} Tracking statistics
     */
    getTrackingStatistics() {
        if (!this.caseId) {
            return { error: 'Performance tracking not initialized' };
        }

        const completionStatus = this.getCompletionStatus();
        
        return {
            caseId: this.caseId,
            sessionDuration: new Date() - this.sessionStartTime,
            totalActions: this.actionLog.length,
            actionsPerMinute: this.actionLog.length / ((new Date() - this.sessionStartTime) / 60000),
            completionRate: completionStatus.overallCompletionRate,
            criticalCompletionRate: completionStatus.criticalCompletionRate,
            averageTimePerAction: this.actionLog.length > 0 ? 
                ((new Date() - this.sessionStartTime) / this.actionLog.length) : 0
        };
    }
}

export default PerformanceTracker;