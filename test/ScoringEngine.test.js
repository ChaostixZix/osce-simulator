import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import ScoringEngine from '../lib/ScoringEngine.js';
import PerformanceTracker from '../lib/PerformanceTracker.js';
import fs from 'fs';
import path from 'path';

describe('ScoringEngine', () => {
    let scoringEngine;
    let performanceTracker;
    let sampleCaseData;

    beforeEach(() => {
        scoringEngine = new ScoringEngine();
        performanceTracker = new PerformanceTracker();

        // Load sample case data
        const casePath = path.join(process.cwd(), 'cases', 'stemi-001.json');
        sampleCaseData = JSON.parse(fs.readFileSync(casePath, 'utf8'));
    });

    describe('Constructor', () => {
        it('should initialize with educational feedback', () => {
            expect(scoringEngine.educationalFeedback).toBeDefined();
            expect(scoringEngine.educationalFeedback.size).toBeGreaterThan(0);
        });

        it('should have educational feedback for key checklist items', () => {
            expect(scoringEngine.educationalFeedback.has('onset_timing')).toBe(true);
            expect(scoringEngine.educationalFeedback.has('ecg')).toBe(true);
            expect(scoringEngine.educationalFeedback.has('primary_diagnosis')).toBe(true);
        });
    });

    describe('calculateScore', () => {
        it('should throw error with invalid inputs', () => {
            expect(() => scoringEngine.calculateScore(null, {})).toThrow('Performance data and checklist are required');
            expect(() => scoringEngine.calculateScore({}, null)).toThrow('Performance data and checklist are required');
        });

        it('should calculate score for perfect performance', () => {
            // Initialize performance tracker with all items completed
            performanceTracker.initializeChecklist(sampleCaseData);

            // Mark all items as completed
            const allItems = [];
            for (const category of Object.values(sampleCaseData.checklist)) {
                for (const item of category.items) {
                    performanceTracker.markChecklistItem(item.id, `Test input for ${item.id}`);
                    allItems.push(item.id);
                }
            }

            const performanceData = performanceTracker.getPerformanceData();
            const result = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            expect(result.totalScore).toBeGreaterThan(0);
            expect(result.percentageScore).toBe(100);
            expect(result.passed).toBe(true);
            expect(result.grade).toBe('A');
            expect(result.completedItems.length).toBe(allItems.length);
            expect(result.missedItems.length).toBe(0);
        });

        it('should calculate score for partial performance', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete only some critical items
            performanceTracker.markChecklistItem('onset_timing', 'When did the pain start?');
            performanceTracker.markChecklistItem('ecg', 'I need an ECG');
            performanceTracker.markChecklistItem('vital_signs', 'Check vital signs');

            const performanceData = performanceTracker.getPerformanceData();
            const result = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            expect(result.totalScore).toBeGreaterThan(0);
            expect(result.percentageScore).toBeLessThan(100);
            expect(result.completedItems.length).toBe(3);
            expect(result.missedItems.length).toBeGreaterThan(0);
        });

        it('should calculate score for poor performance', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete only one non-critical item
            performanceTracker.markChecklistItem('medications', 'What medications are you taking?');

            const performanceData = performanceTracker.getPerformanceData();
            const result = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            expect(result.percentageScore).toBeLessThan(50);
            expect(result.passed).toBe(false);
            expect(result.grade).toBe('F');
            expect(result.criticalPercentage).toBeLessThan(80);
        });

        it('should calculate category scores correctly', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete all history taking items
            performanceTracker.markChecklistItem('onset_timing', 'When did this start?');
            performanceTracker.markChecklistItem('pain_character', 'Describe the pain');
            performanceTracker.markChecklistItem('associated_symptoms', 'Any other symptoms?');
            performanceTracker.markChecklistItem('past_medical_history', 'Any medical history?');
            performanceTracker.markChecklistItem('medications', 'Current medications?');
            performanceTracker.markChecklistItem('risk_factors', 'Do you smoke?');

            const performanceData = performanceTracker.getPerformanceData();
            const result = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            expect(result.categoryScores.historyTaking).toBeDefined();
            expect(result.categoryScores.historyTaking.percentage).toBe(100);
            expect(result.categoryScores.historyTaking.weightedScore).toBe(30); // Full weight
        });

        it('should apply efficiency bonuses correctly', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete several items efficiently (few actions)
            performanceTracker.trackAction('Tell me about your chest pain - when did it start, what does it feel like?', 'history');
            performanceTracker.markChecklistItem('onset_timing');
            performanceTracker.markChecklistItem('pain_character');

            performanceTracker.trackAction('I need to check your vital signs and do an ECG', 'examination');
            performanceTracker.markChecklistItem('vital_signs');
            performanceTracker.markChecklistItem('ecg');

            const performanceData = performanceTracker.getPerformanceData();
            const result = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            expect(result.efficiencyBonus).toBeGreaterThanOrEqual(0);
        });

        it('should handle critical items scoring', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete all critical items
            const criticalItems = [];
            for (const category of Object.values(sampleCaseData.checklist)) {
                for (const item of category.items) {
                    if (item.critical) {
                        performanceTracker.markChecklistItem(item.id);
                        criticalItems.push(item.id);
                    }
                }
            }

            const performanceData = performanceTracker.getPerformanceData();
            const result = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            expect(result.criticalPercentage).toBe(100);
            expect(result.criticalItemsScore).toBeGreaterThan(0);
        });
    });

    describe('generateFeedback', () => {
        it('should throw error with invalid inputs', () => {
            expect(() => scoringEngine.generateFeedback(null, {})).toThrow('Performance data and checklist are required');
            expect(() => scoringEngine.generateFeedback({}, null)).toThrow('Performance data and checklist are required');
        });

        it('should generate comprehensive feedback', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Partial completion
            performanceTracker.markChecklistItem('onset_timing', 'When did the pain start?');
            performanceTracker.markChecklistItem('ecg', 'I need an ECG');

            const performanceData = performanceTracker.getPerformanceData();
            const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

            expect(feedback.caseId).toBe(sampleCaseData.id);
            expect(feedback.summary).toBeDefined();
            expect(feedback.strengths).toBeDefined();
            expect(feedback.areasForImprovement).toBeDefined();
            expect(feedback.educationalFeedback).toBeDefined();
            expect(feedback.categoryFeedback).toBeDefined();
            expect(feedback.learningRecommendations).toBeDefined();
            expect(feedback.nextSteps).toBeDefined();
        });

        it('should identify strengths correctly', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete all history taking items
            for (const item of sampleCaseData.checklist.historyTaking.items) {
                performanceTracker.markChecklistItem(item.id);
            }

            const performanceData = performanceTracker.getPerformanceData();
            const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

            expect(feedback.strengths.length).toBeGreaterThan(0);
            expect(feedback.strengths.some(s => s.includes('historyTaking'))).toBe(true);
        });

        it('should identify areas for improvement', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete only one item, leaving many missed
            performanceTracker.markChecklistItem('medications', 'What medications?');

            const performanceData = performanceTracker.getPerformanceData();
            const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

            expect(feedback.areasForImprovement.length).toBeGreaterThan(0);
            expect(feedback.areasForImprovement.some(a => a.includes('CRITICAL'))).toBe(true);
        });

        it('should provide educational feedback for missed items', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Don't complete ECG (critical item with educational feedback)
            performanceTracker.markChecklistItem('medications', 'What medications?');

            const performanceData = performanceTracker.getPerformanceData();
            const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

            const ecgFeedback = feedback.educationalFeedback.find(f => f.itemId === 'ecg');
            expect(ecgFeedback).toBeDefined();
            expect(ecgFeedback.learningPoint).toBeDefined();
            expect(ecgFeedback.clinicalRationale).toBeDefined();
            expect(ecgFeedback.practicalTips).toBeDefined();
        });

        it('should generate category-specific feedback', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete some items in different categories
            performanceTracker.markChecklistItem('onset_timing');
            performanceTracker.markChecklistItem('vital_signs');

            const performanceData = performanceTracker.getPerformanceData();
            const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

            expect(feedback.categoryFeedback.historyTaking).toBeDefined();
            expect(feedback.categoryFeedback.physicalExamination).toBeDefined();
            expect(feedback.categoryFeedback.historyTaking.feedback).toBeDefined();
        });

        it('should provide appropriate learning recommendations', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Poor performance scenario
            performanceTracker.markChecklistItem('medications');

            const performanceData = performanceTracker.getPerformanceData();
            const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

            expect(feedback.learningRecommendations.length).toBeGreaterThan(0);
            expect(feedback.learningRecommendations.some(r => r.includes('fundamental'))).toBe(true);
        });

        it('should provide next steps', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            const performanceData = performanceTracker.getPerformanceData();
            const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

            expect(feedback.nextSteps.length).toBeGreaterThan(0);
            expect(feedback.nextSteps.some(s => s.includes('Review'))).toBe(true);
        });
    });

    describe('identifyMissedItems', () => {
        it('should categorize missed items by importance', () => {
            const missedItems = [
                { id: 'ecg', description: 'ECG', category: 'investigations', critical: true, points: 8 },
                { id: 'medications', description: 'Medications', category: 'history', critical: false, points: 2 },
                { id: 'chest_xray', description: 'Chest X-ray', category: 'investigations', critical: false, points: 2 }
            ];

            const result = scoringEngine.identifyMissedItems(sampleCaseData.checklist, missedItems);

            expect(result.critical.length).toBe(1);
            expect(result.critical[0].id).toBe('ecg');
            expect(result.optional.length).toBe(2);
            expect(result.totalMissed).toBe(3);
        });

        it('should include educational feedback for missed items', () => {
            const missedItems = [
                { id: 'ecg', description: 'ECG', category: 'investigations', critical: true, points: 8 }
            ];

            const result = scoringEngine.identifyMissedItems(sampleCaseData.checklist, missedItems);

            expect(result.critical[0].educationalFeedback).toBeDefined();
            expect(result.critical[0].educationalFeedback.learningPoint).toBeDefined();
        });
    });

    describe('provideLearningPoints', () => {
        it('should provide learning points for missed items', () => {
            const missedItems = [
                { id: 'ecg', description: 'ECG', category: 'investigations', critical: true },
                { id: 'onset_timing', description: 'Onset timing', category: 'history', critical: true }
            ];

            const learningPoints = scoringEngine.provideLearningPoints(missedItems);

            expect(learningPoints.length).toBe(2);
            expect(learningPoints[0].learningPoint).toBeDefined();
            expect(learningPoints[0].clinicalRationale).toBeDefined();
            expect(learningPoints[0].practicalTips).toBeDefined();
        });

        it('should handle items without educational feedback', () => {
            const missedItems = [
                { id: 'nonexistent_item', description: 'Non-existent', category: 'test', critical: false }
            ];

            const learningPoints = scoringEngine.provideLearningPoints(missedItems);

            expect(learningPoints.length).toBe(0);
        });
    });

    describe('Grade determination', () => {
        it('should assign correct grades based on performance', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Test A grade (90%+ with critical items)
            const allItems = [];
            for (const category of Object.values(sampleCaseData.checklist)) {
                for (const item of category.items) {
                    performanceTracker.markChecklistItem(item.id);
                    allItems.push(item.id);
                }
            }

            let performanceData = performanceTracker.getPerformanceData();
            let result = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);
            expect(result.grade).toBe('A');
            expect(result.passed).toBe(true);

            // Test F grade (poor critical performance)
            performanceTracker.reset();
            performanceTracker.initializeChecklist(sampleCaseData);
            performanceTracker.markChecklistItem('medications'); // Non-critical only

            performanceData = performanceTracker.getPerformanceData();
            result = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);
            expect(result.grade).toBe('F');
            expect(result.passed).toBe(false);
        });
    });

    describe('Educational feedback database', () => {
        it('should have comprehensive educational content', () => {
            const criticalItems = ['onset_timing', 'pain_character', 'ecg', 'cardiac_enzymes', 'primary_diagnosis'];

            for (const itemId of criticalItems) {
                const feedback = scoringEngine.educationalFeedback.get(itemId);
                expect(feedback).toBeDefined();
                expect(feedback.learningPoint).toBeDefined();
                expect(feedback.clinicalRationale).toBeDefined();
                expect(feedback.practicalTips).toBeDefined();
            }
        });

        it('should provide relevant clinical content', () => {
            const ecgFeedback = scoringEngine.educationalFeedback.get('ecg');
            expect(ecgFeedback.learningPoint).toContain('ECG');
            expect(ecgFeedback.clinicalRationale).toContain('ST elevation');
            expect(ecgFeedback.practicalTips).toContain('10 minutes');
        });
    });

    describe('Integration with PerformanceTracker', () => {
        it('should work seamlessly with PerformanceTracker data', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Simulate realistic student interaction
            performanceTracker.trackAction('Tell me about your chest pain', 'history');
            performanceTracker.trackAction('I need to check your vital signs', 'examination');
            performanceTracker.trackAction('Let me get an ECG', 'investigation');

            const performanceData = performanceTracker.getPerformanceData();

            expect(() => {
                const score = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);
                const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);
            }).not.toThrow();
        });
    });

    describe('Error handling', () => {
        it('should handle malformed performance data gracefully', () => {
            const malformedData = {
                caseId: 'test',
                completionStatus: {
                    completedItemsList: [],
                    missingItemsList: [],
                    sessionDuration: 1000
                },
                actionLog: { totalActions: 0 }
            };

            expect(() => {
                scoringEngine.calculateScore(malformedData, sampleCaseData.checklist);
            }).not.toThrow();
        });

        it('should handle empty checklist gracefully', () => {
            performanceTracker.initializeChecklist(sampleCaseData);
            const performanceData = performanceTracker.getPerformanceData();

            const emptyChecklist = {};

            const result = scoringEngine.calculateScore(performanceData, emptyChecklist);
            expect(result.totalScore).toBe(0);
            expect(result.maxPossibleScore).toBe(0);
        });
    });
});