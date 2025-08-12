import { describe, it, expect, beforeEach } from 'vitest';
import PerformanceTracker from '../lib/PerformanceTracker.js';

describe('PerformanceTracker', () => {
    let tracker;
    let mockCaseData;

    beforeEach(() => {
        tracker = new PerformanceTracker();
        
        // Mock case data based on the STEMI case structure
        mockCaseData = {
            id: 'test-case-001',
            title: 'Test Case',
            checklist: {
                historyTaking: {
                    weight: 30,
                    items: [
                        {
                            id: 'onset_timing',
                            description: 'Asked about onset and timing of chest pain',
                            critical: true,
                            points: 5
                        },
                        {
                            id: 'pain_character',
                            description: 'Characterized the chest pain',
                            critical: true,
                            points: 5
                        },
                        {
                            id: 'associated_symptoms',
                            description: 'Asked about associated symptoms',
                            critical: false,
                            points: 3
                        }
                    ]
                },
                physicalExamination: {
                    weight: 20,
                    items: [
                        {
                            id: 'vital_signs',
                            description: 'Checked vital signs',
                            critical: true,
                            points: 5
                        },
                        {
                            id: 'cardiovascular_exam',
                            description: 'Performed cardiovascular examination',
                            critical: true,
                            points: 5
                        }
                    ]
                },
                investigations: {
                    weight: 25,
                    items: [
                        {
                            id: 'ecg',
                            description: 'Ordered ECG',
                            critical: true,
                            points: 8
                        }
                    ]
                }
            }
        };
    });

    describe('Initialization', () => {
        it('should initialize with default values', () => {
            expect(tracker.caseId).toBeNull();
            expect(tracker.checklist).toBeNull();
            expect(tracker.sessionStartTime).toBeNull();
            expect(tracker.actionLog).toEqual([]);
        });

        it('should initialize checklist successfully', () => {
            tracker.initializeChecklist(mockCaseData);
            
            expect(tracker.caseId).toBe('test-case-001');
            expect(tracker.checklist).toBe(mockCaseData.checklist);
            expect(tracker.sessionStartTime).toBeInstanceOf(Date);
            expect(tracker.actionLog).toEqual([]);
        });

        it('should throw error when initializing without case data', () => {
            expect(() => tracker.initializeChecklist()).toThrow('Valid case data with ID is required');
        });

        it('should throw error when initializing without checklist', () => {
            const invalidCaseData = { id: 'test', title: 'Test' };
            expect(() => tracker.initializeChecklist(invalidCaseData)).toThrow('Case data must include a checklist');
        });

        it('should initialize all checklist items as not completed', () => {
            tracker.initializeChecklist(mockCaseData);
            
            const status = tracker.getCompletionStatus();
            expect(status.completedItems).toBe(0);
            expect(status.totalItems).toBe(6); // 3 + 2 + 1 items
        });
    });

    describe('Action Tracking', () => {
        beforeEach(() => {
            tracker.initializeChecklist(mockCaseData);
        });

        it('should track actions and log them', () => {
            const userInput = 'When did the chest pain start?';
            const triggeredItems = tracker.trackAction(userInput, 'history');
            
            expect(tracker.actionLog).toHaveLength(1);
            expect(tracker.actionLog[0].input).toBe(userInput);
            expect(tracker.actionLog[0].actionType).toBe('history');
            expect(tracker.actionLog[0].timestamp).toBeInstanceOf(Date);
        });

        it('should map user input to relevant checklist items', () => {
            const userInput = 'When did the chest pain start?';
            const triggeredItems = tracker.trackAction(userInput, 'history');
            
            expect(triggeredItems).toContain('onset_timing');
        });

        it('should handle multiple keyword matches', () => {
            const userInput = 'Tell me about your chest pain - when did it start and how does it feel?';
            const triggeredItems = tracker.trackAction(userInput, 'history');
            
            expect(triggeredItems).toContain('onset_timing');
            expect(triggeredItems).toContain('pain_character');
        });

        it('should not mark already completed items again', () => {
            tracker.markChecklistItem('onset_timing');

            const userInput = 'When did the pain start?';
            const triggeredItems = tracker.trackAction(userInput, 'history');

            expect(triggeredItems).not.toContain('onset_timing');
        });

        it('should not trigger items for mismatched action type', () => {
            const userInput = 'When did the pain start?';
            const triggeredItems = tracker.trackAction(userInput, 'examination');
            expect(triggeredItems).toHaveLength(0);
        });

        it('should throw error when tracking without initialization', () => {
            const uninitializedTracker = new PerformanceTracker();
            expect(() => uninitializedTracker.trackAction('test')).toThrow('Performance tracking not initialized');
        });
    });

    describe('Checklist Item Marking', () => {
        beforeEach(() => {
            tracker.initializeChecklist(mockCaseData);
        });

        it('should mark checklist items as completed', () => {
            tracker.markChecklistItem('onset_timing');
            
            const status = tracker.getCompletionStatus();
            expect(status.completedItems).toBe(1);
            
            const onsetItem = status.completedItemsList.find(item => item.id === 'onset_timing');
            expect(onsetItem).toBeDefined();
            expect(onsetItem.completed).toBe(true);
        });

        it('should record timestamp when marking items', () => {
            const beforeTime = new Date();
            tracker.markChecklistItem('onset_timing');
            const afterTime = new Date();
            
            const itemStatus = tracker.checklistStatus.get('onset_timing');
            expect(itemStatus.timestamp).toBeInstanceOf(Date);
            expect(itemStatus.timestamp.getTime()).toBeGreaterThanOrEqual(beforeTime.getTime());
            expect(itemStatus.timestamp.getTime()).toBeLessThanOrEqual(afterTime.getTime());
        });

        it('should record user input when provided', () => {
            const userInput = 'When did the pain start?';
            tracker.markChecklistItem('onset_timing', userInput);
            
            const itemStatus = tracker.checklistStatus.get('onset_timing');
            expect(itemStatus.userInput).toBe(userInput);
        });

        it('should handle non-existent checklist items gracefully', () => {
            // Should not throw error, just log warning
            expect(() => tracker.markChecklistItem('non_existent_item')).not.toThrow();
        });

        it('should throw error when marking without initialization', () => {
            const uninitializedTracker = new PerformanceTracker();
            expect(() => uninitializedTracker.markChecklistItem('test')).toThrow('Performance tracking not initialized');
        });
    });

    describe('Completion Status', () => {
        beforeEach(() => {
            tracker.initializeChecklist(mockCaseData);
        });

        it('should return correct completion status for empty checklist', () => {
            const status = tracker.getCompletionStatus();
            
            expect(status.caseId).toBe('test-case-001');
            expect(status.totalItems).toBe(6);
            expect(status.completedItems).toBe(0);
            expect(status.overallCompletionRate).toBe(0);
            expect(status.completedItemsList).toHaveLength(0);
            expect(status.missingItemsList).toHaveLength(6);
        });

        it('should return correct completion status with some items completed', () => {
            tracker.markChecklistItem('onset_timing');
            tracker.markChecklistItem('vital_signs');
            
            const status = tracker.getCompletionStatus();
            
            expect(status.completedItems).toBe(2);
            expect(status.overallCompletionRate).toBe(2/6 * 100);
            expect(status.completedItemsList).toHaveLength(2);
            expect(status.missingItemsList).toHaveLength(4);
        });

        it('should track critical items separately', () => {
            tracker.markChecklistItem('onset_timing'); // critical
            tracker.markChecklistItem('associated_symptoms'); // not critical
            
            const status = tracker.getCompletionStatus();
            
            expect(status.criticalItems).toBe(5); // onset_timing, pain_character, vital_signs, cardiovascular_exam, ecg
            expect(status.completedCriticalItems).toBe(1); // only onset_timing
            expect(status.criticalCompletionRate).toBe(1/5 * 100);
        });

        it('should provide category-wise breakdown', () => {
            tracker.markChecklistItem('onset_timing');
            tracker.markChecklistItem('vital_signs');
            
            const status = tracker.getCompletionStatus();
            
            expect(status.categories.historyTaking.completedItems).toBe(1);
            expect(status.categories.historyTaking.totalItems).toBe(3);
            expect(status.categories.physicalExamination.completedItems).toBe(1);
            expect(status.categories.physicalExamination.totalItems).toBe(2);
            expect(status.categories.investigations.completedItems).toBe(0);
            expect(status.categories.investigations.totalItems).toBe(1);
        });

        it('should return error when not initialized', () => {
            const uninitializedTracker = new PerformanceTracker();
            const status = uninitializedTracker.getCompletionStatus();
            
            expect(status.error).toBe('Performance tracking not initialized');
        });
    });

    describe('Action Log', () => {
        beforeEach(() => {
            tracker.initializeChecklist(mockCaseData);
        });

        it('should return detailed action log', () => {
            tracker.trackAction('When did the pain start?', 'history');
            // Add a small delay to ensure duration > 0
            const start = Date.now();
            while (Date.now() - start < 1) {} // 1ms delay
            tracker.trackAction('Can I check your blood pressure?', 'examination');
            
            const log = tracker.getDetailedLog();
            
            expect(log.caseId).toBe('test-case-001');
            expect(log.totalActions).toBe(2);
            expect(log.actions).toHaveLength(2);
            expect(log.sessionStartTime).toBeInstanceOf(Date);
            expect(log.sessionDuration).toBeGreaterThanOrEqual(0);
        });

        it('should include triggered items in action log', () => {
            tracker.trackAction('When did the chest pain start?', 'history');
            
            const log = tracker.getDetailedLog();
            const firstAction = log.actions[0];
            
            expect(firstAction.triggeredItems).toContain('onset_timing');
        });
    });

    describe('Performance Data', () => {
        beforeEach(() => {
            tracker.initializeChecklist(mockCaseData);
        });

        it('should return comprehensive performance data', () => {
            tracker.trackAction('When did the pain start?', 'history');
            tracker.markChecklistItem('vital_signs');
            
            const data = tracker.getPerformanceData();
            
            expect(data.caseId).toBe('test-case-001');
            expect(data.checklist).toBe(mockCaseData.checklist);
            expect(data.completionStatus).toBeDefined();
            expect(data.actionLog).toBeDefined();
            expect(data.checklistStatus).toBeDefined();
        });
    });

    describe('Reset Functionality', () => {
        beforeEach(() => {
            tracker.initializeChecklist(mockCaseData);
            tracker.trackAction('test input', 'history');
            tracker.markChecklistItem('onset_timing');
        });

        it('should reset all tracking data', () => {
            tracker.reset();
            
            expect(tracker.caseId).toBeNull();
            expect(tracker.checklist).toBeNull();
            expect(tracker.sessionStartTime).toBeNull();
            expect(tracker.actionLog).toHaveLength(0);
            expect(tracker.checklistStatus.size).toBe(0);
        });
    });

    describe('Keyword Mapping', () => {
        beforeEach(() => {
            tracker.initializeChecklist(mockCaseData);
        });

        it('should map history-related keywords correctly', () => {
            // Reset tracker to ensure clean state for each input
            const inputs = [
                'When did the pain start?',
                'How long have you had this pain?',
                'Tell me about the onset of your symptoms'
            ];
            
            for (let i = 0; i < inputs.length; i++) {
                if (i > 0) {
                    tracker.reset();
                    tracker.initializeChecklist(mockCaseData);
                }
                const triggeredItems = tracker.trackAction(inputs[i], 'history');
                expect(triggeredItems).toContain('onset_timing');
            }
        });

        it('should map pain character keywords correctly', () => {
            const inputs = [
                'Describe your chest pain',
                'What does the pain feel like?',
                'Is it sharp or dull pain?'
            ];
            
            for (let i = 0; i < inputs.length; i++) {
                if (i > 0) {
                    tracker.reset();
                    tracker.initializeChecklist(mockCaseData);
                }
                const triggeredItems = tracker.trackAction(inputs[i], 'history');
                expect(triggeredItems).toContain('pain_character');
            }
        });

        it('should map examination keywords correctly', () => {
            const inputs = [
                'Let me check your vital signs',
                'I need to take your blood pressure',
                'Can I listen to your heart?'
            ];
            
            const vitalSignsInput = inputs[0];
            const heartInput = inputs[2];
            
            expect(tracker.trackAction(vitalSignsInput, 'examination')).toContain('vital_signs');
            expect(tracker.trackAction(heartInput, 'examination')).toContain('cardiovascular_exam');
        });

        it('should map investigation keywords correctly', () => {
            const ecgInputs = [
                'I need to order an ECG',
                'Let\'s get an electrocardiogram',
                'We should check the heart rhythm'
            ];
            
            for (let i = 0; i < ecgInputs.length; i++) {
                if (i > 0) {
                    tracker.reset();
                    tracker.initializeChecklist(mockCaseData);
                }
                const triggeredItems = tracker.trackAction(ecgInputs[i], 'investigation');
                expect(triggeredItems).toContain('ecg');
            }
        });
    });

    describe('Statistics', () => {
        beforeEach(() => {
            tracker.initializeChecklist(mockCaseData);
        });

        it('should provide tracking statistics', async () => {
            tracker.trackAction('test input 1', 'history');
            tracker.trackAction('test input 2', 'examination');
            tracker.markChecklistItem('onset_timing');
            
            // Wait a small amount to ensure duration > 0
            await new Promise(resolve => setTimeout(resolve, 10));
            
            const stats = tracker.getTrackingStatistics();
            
            expect(stats.caseId).toBe('test-case-001');
            expect(stats.totalActions).toBe(2);
            expect(stats.sessionDuration).toBeGreaterThan(0);
            expect(stats.completionRate).toBeGreaterThan(0);
            expect(stats.actionsPerMinute).toBeGreaterThan(0);
        });

        it('should return error for statistics when not initialized', () => {
            const uninitializedTracker = new PerformanceTracker();
            const stats = uninitializedTracker.getTrackingStatistics();
            
            expect(stats.error).toBe('Performance tracking not initialized');
        });
    });

    describe('Edge Cases', () => {
        beforeEach(() => {
            tracker.initializeChecklist(mockCaseData);
        });

        it('should handle empty user input', () => {
            expect(() => tracker.trackAction('', 'history')).not.toThrow();
            expect(tracker.actionLog).toHaveLength(1);
        });

        it('should handle very long user input', () => {
            const longInput = 'a'.repeat(1000);
            expect(() => tracker.trackAction(longInput, 'history')).not.toThrow();
        });

        it('should handle special characters in user input', () => {
            const specialInput = 'What about @#$%^&*() symptoms?';
            expect(() => tracker.trackAction(specialInput, 'history')).not.toThrow();
        });

        it('should handle case-insensitive keyword matching', () => {
            const inputs = [
                'WHEN DID THE PAIN START?',
                'when did the pain start?',
                'When Did The Pain Start?'
            ];
            
            for (const input of inputs) {
                tracker.reset();
                tracker.initializeChecklist(mockCaseData);
                const triggeredItems = tracker.trackAction(input, 'history');
                expect(triggeredItems).toContain('onset_timing');
            }
        });
    });
});