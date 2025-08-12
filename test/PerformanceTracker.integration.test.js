import { describe, it, expect, beforeEach } from 'vitest';
import PerformanceTracker from '../lib/PerformanceTracker.js';
import CaseManager from '../lib/CaseManager.js';

describe('PerformanceTracker Integration Tests', () => {
    let tracker;
    let caseManager;
    let stemiCase;

    beforeEach(async () => {
        tracker = new PerformanceTracker();
        caseManager = new CaseManager();
        
        // Load the actual STEMI case
        await caseManager.loadAvailableCases();
        stemiCase = caseManager.getCaseById('stemi-001');
        
        if (stemiCase) {
            tracker.initializeChecklist(stemiCase);
        }
    });

    describe('Real STEMI Case Integration', () => {
        it('should work with actual STEMI case data', () => {
            expect(stemiCase).toBeDefined();
            expect(stemiCase.id).toBe('stemi-001');
            
            const status = tracker.getCompletionStatus();
            expect(status.caseId).toBe('stemi-001');
            expect(status.totalItems).toBeGreaterThan(0);
        });

        it('should track realistic clinical interactions', () => {
            // Simulate a realistic clinical interaction sequence
            const interactions = [
                { input: 'Tell me about your chest pain', type: 'history' },
                { input: 'When did the pain start?', type: 'history' },
                { input: 'Can you describe the character of the pain?', type: 'history' },
                { input: 'Do you have any shortness of breath or nausea?', type: 'history' },
                { input: 'Let me check your vital signs', type: 'examination' },
                { input: 'I need to listen to your heart', type: 'examination' },
                { input: 'I want to order an ECG', type: 'investigation' },
                { input: 'Let\'s get some cardiac enzymes', type: 'investigation' },
                { input: 'I think you might be having a heart attack', type: 'diagnosis' }
            ];

            let totalTriggered = 0;
            for (const step of interactions) {
                const triggered = tracker.trackAction(step.input, step.type);
                totalTriggered += triggered.length;
            }

            expect(totalTriggered).toBeGreaterThan(0);
            
            const finalStatus = tracker.getCompletionStatus();
            expect(finalStatus.completedItems).toBeGreaterThan(0);
            expect(finalStatus.overallCompletionRate).toBeGreaterThan(0);
        });

        it('should provide meaningful performance data', () => {
            // Simulate some interactions
            tracker.trackAction('When did your chest pain start?', 'history');
            tracker.trackAction('Let me check your blood pressure', 'examination');
            tracker.trackAction('I need to order an ECG', 'investigation');
            
            const performanceData = tracker.getPerformanceData();
            
            expect(performanceData.caseId).toBe('stemi-001');
            expect(performanceData.checklist).toBeDefined();
            expect(performanceData.completionStatus).toBeDefined();
            expect(performanceData.actionLog).toBeDefined();
            expect(performanceData.actionLog.totalActions).toBe(3);
        });

        it('should handle complete clinical workflow', () => {
            // Simulate a complete clinical assessment
            const clinicalWorkflow = [
                // History taking
                { input: 'Tell me about your chest pain', type: 'history' },
                { input: 'When did the pain start?', type: 'history' },
                { input: 'What does the pain feel like?', type: 'history' },
                { input: 'Do you have any medical history?', type: 'history' },
                { input: 'What medications are you taking?', type: 'history' },
                
                // Physical examination
                { input: 'Let me check your vital signs', type: 'examination' },
                { input: 'I need to listen to your heart', type: 'examination' },
                { input: 'Let me listen to your lungs', type: 'examination' },
                
                // Investigations
                { input: 'I need to order an ECG', type: 'investigation' },
                { input: 'Let\'s get cardiac enzymes', type: 'investigation' },
                { input: 'I want to order some basic blood work', type: 'investigation' },
                
                // Diagnosis and management
                { input: 'I think you\'re having a STEMI', type: 'diagnosis' },
                { input: 'We need to start emergency treatment', type: 'management' }
            ];

            for (const step of clinicalWorkflow) {
                tracker.trackAction(step.input, step.type);
            }

            const finalStatus = tracker.getCompletionStatus();
            
            // Should have completed multiple categories
            expect(Object.keys(finalStatus.categories)).toContain('historyTaking');
            expect(Object.keys(finalStatus.categories)).toContain('physicalExamination');
            expect(Object.keys(finalStatus.categories)).toContain('investigations');
            
            // Should have reasonable completion rates
            expect(finalStatus.overallCompletionRate).toBeGreaterThan(30);
            
            // Should have tracked all actions
            const actionLog = tracker.getDetailedLog();
            expect(actionLog.totalActions).toBe(clinicalWorkflow.length);
        });
    });

    describe('Error Handling with Real Data', () => {
        it('should handle missing case gracefully', () => {
            const nonExistentCase = caseManager.getCaseById('non-existent');
            expect(nonExistentCase).toBeNull();
        });

        it('should validate case data integrity', () => {
            if (stemiCase) {
                const validation = caseManager.validateCaseData(stemiCase);
                expect(validation.isValid).toBe(true);
            }
        });
    });
});