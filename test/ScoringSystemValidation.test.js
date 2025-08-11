import { describe, it, expect, beforeEach } from 'vitest';
import ScoringEngine from '../lib/ScoringEngine.js';
import PerformanceTracker from '../lib/PerformanceTracker.js';
import fs from 'fs';
import path from 'path';

/**
 * Scoring System Validation Tests
 * Tests scoring accuracy, consistency, and expected outcomes validation
 */
describe('Scoring System Validation', () => {
    let scoringEngine;
    let performanceTracker;
    let sampleCaseData;

    beforeEach(() => {
        scoringEngine = new ScoringEngine();
        performanceTracker = new PerformanceTracker();

        // Load STEMI case data
        const casePath = path.join(process.cwd(), 'cases', 'stemi-001.json');
        if (fs.existsSync(casePath)) {
            sampleCaseData = JSON.parse(fs.readFileSync(casePath, 'utf8'));
        }
    });

    describe('Scoring Accuracy Validation', () => {
        it('should calculate accurate scores for perfect performance', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete all checklist items
            const allItems = [];
            for (const category of Object.values(sampleCaseData.checklist)) {
                for (const item of category.items) {
                    performanceTracker.markChecklistItem(item.id, `Test input for ${item.id}`);
                    allItems.push(item);
                }
            }

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Perfect performance validation
            expect(scoreResult.percentageScore).toBe(100);
            expect(scoreResult.passed).toBe(true);
            expect(scoreResult.grade).toBe('A');
            expect(scoreResult.completedItems.length).toBe(allItems.length);
            expect(scoreResult.missedItems.length).toBe(0);
            expect(scoreResult.criticalPercentage).toBe(100);

            // Verify total possible score calculation
            const expectedMaxScore = allItems.reduce((sum, item) => sum + item.points, 0);
            expect(scoreResult.maxPossibleScore).toBe(expectedMaxScore);
            expect(scoreResult.totalScore).toBe(expectedMaxScore);
        });

        it('should calculate accurate scores for critical items only', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete only critical items
            const criticalItems = [];
            for (const category of Object.values(sampleCaseData.checklist)) {
                for (const item of category.items) {
                    if (item.critical) {
                        performanceTracker.markChecklistItem(item.id, `Critical action: ${item.id}`);
                        criticalItems.push(item);
                    }
                }
            }

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Critical items validation
            expect(scoreResult.criticalPercentage).toBe(100);
            expect(scoreResult.completedItems.length).toBe(criticalItems.length);
            expect(scoreResult.passed).toBe(true); // Should pass with all critical items

            // Calculate expected score from critical items
            const criticalScore = criticalItems.reduce((sum, item) => sum + item.points, 0);
            expect(scoreResult.criticalItemsScore).toBe(criticalScore);
        });

        it('should calculate accurate scores for poor performance', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete only one non-critical item
            performanceTracker.markChecklistItem('medications', 'What medications are you taking?');

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Poor performance validation
            expect(scoreResult.percentageScore).toBeLessThan(30);
            expect(scoreResult.passed).toBe(false);
            expect(scoreResult.grade).toBe('F');
            expect(scoreResult.criticalPercentage).toBe(0); // No critical items completed
            expect(scoreResult.completedItems.length).toBe(1);
            expect(scoreResult.missedItems.length).toBeGreaterThan(10);
        });

        it('should validate category-specific scoring accuracy', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete all history taking items
            const historyItems = sampleCaseData.checklist.historyTaking.items;
            historyItems.forEach(item => {
                performanceTracker.markChecklistItem(item.id, `History: ${item.description}`);
            });

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // History taking category should be 100%
            expect(scoreResult.categoryScores.historyTaking.percentage).toBe(100);
            expect(scoreResult.categoryScores.historyTaking.completedItems).toBe(historyItems.length);
            expect(scoreResult.categoryScores.historyTaking.totalItems).toBe(historyItems.length);

            // Other categories should be 0%
            expect(scoreResult.categoryScores.physicalExamination.percentage).toBe(0);
            expect(scoreResult.categoryScores.investigations.percentage).toBe(0);
            expect(scoreResult.categoryScores.diagnosis.percentage).toBe(0);
            expect(scoreResult.categoryScores.management.percentage).toBe(0);
        });

        it('should apply weighted scoring correctly', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete items from different categories with different weights
            performanceTracker.markChecklistItem('onset_timing', 'When did the pain start?'); // History (weight 30)
            performanceTracker.markChecklistItem('vital_signs', 'Check vital signs'); // Examination (weight 20)
            performanceTracker.markChecklistItem('ecg', 'Get ECG'); // Investigation (weight 25)

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Verify weighted contributions
            const historyWeight = sampleCaseData.checklist.historyTaking.weight;
            const examWeight = sampleCaseData.checklist.physicalExamination.weight;
            const investigationWeight = sampleCaseData.checklist.investigations.weight;

            expect(scoreResult.categoryScores.historyTaking.weightedScore).toBeGreaterThan(0);
            expect(scoreResult.categoryScores.physicalExamination.weightedScore).toBeGreaterThan(0);
            expect(scoreResult.categoryScores.investigations.weightedScore).toBeGreaterThan(0);

            // Weighted scores should reflect category weights
            const totalWeightedScore = 
                scoreResult.categoryScores.historyTaking.weightedScore +
                scoreResult.categoryScores.physicalExamination.weightedScore +
                scoreResult.categoryScores.investigations.weightedScore +
                scoreResult.categoryScores.diagnosis.weightedScore +
                scoreResult.categoryScores.management.weightedScore;

            expect(Math.abs(scoreResult.percentageScore - totalWeightedScore)).toBeLessThan(1);
        });
    });

    describe('Expected Outcomes Validation', () => {
        it('should validate excellent performance outcomes', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Simulate excellent clinical performance
            const excellentActions = [
                { id: 'onset_timing', input: 'When did your chest pain start?' },
                { id: 'pain_character', input: 'Describe the character of your pain' },
                { id: 'associated_symptoms', input: 'Any shortness of breath or nausea?' },
                { id: 'past_medical_history', input: 'Do you have any medical conditions?' },
                { id: 'risk_factors', input: 'Do you smoke or have family history?' },
                { id: 'vital_signs', input: 'I need to check your vital signs' },
                { id: 'cardiovascular_exam', input: 'Let me examine your heart' },
                { id: 'ecg', input: 'I need a 12-lead ECG immediately' },
                { id: 'cardiac_enzymes', input: 'Order cardiac enzymes and troponin' },
                { id: 'primary_diagnosis', input: 'This appears to be a STEMI' },
                { id: 'emergency_treatment', input: 'Start emergency STEMI protocol' }
            ];

            excellentActions.forEach(action => {
                performanceTracker.markChecklistItem(action.id, action.input);
            });

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Expected outcomes for excellent performance
            expect(scoreResult.percentageScore).toBeGreaterThanOrEqual(85);
            expect(scoreResult.grade).toMatch(/A|B/);
            expect(scoreResult.passed).toBe(true);
            expect(scoreResult.criticalPercentage).toBeGreaterThanOrEqual(80);
            
            // Should complete most categories well
            expect(scoreResult.categoryScores.historyTaking.percentage).toBeGreaterThanOrEqual(80);
            expect(scoreResult.categoryScores.investigations.percentage).toBeGreaterThanOrEqual(70);
        });

        it('should validate good performance outcomes', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Simulate good but not perfect performance
            const goodActions = [
                { id: 'onset_timing', input: 'When did the pain start?' },
                { id: 'pain_character', input: 'Describe the pain' },
                { id: 'vital_signs', input: 'Check vital signs' },
                { id: 'ecg', input: 'Get an ECG' },
                { id: 'cardiac_enzymes', input: 'Order troponin' },
                { id: 'primary_diagnosis', input: 'This looks like a heart attack' }
            ];

            goodActions.forEach(action => {
                performanceTracker.markChecklistItem(action.id, action.input);
            });

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Expected outcomes for good performance
            expect(scoreResult.percentageScore).toBeGreaterThanOrEqual(60);
            expect(scoreResult.percentageScore).toBeLessThan(85);
            expect(scoreResult.grade).toMatch(/B|C/);
            expect(scoreResult.passed).toBe(true);
            expect(scoreResult.criticalPercentage).toBeGreaterThanOrEqual(60);
        });

        it('should validate failing performance outcomes', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Simulate poor performance - missing critical items
            const poorActions = [
                { id: 'medications', input: 'What medications do you take?' },
                { id: 'basic_labs', input: 'Get some blood work' }
            ];

            poorActions.forEach(action => {
                performanceTracker.markChecklistItem(action.id, action.input);
            });

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Expected outcomes for poor performance
            expect(scoreResult.percentageScore).toBeLessThan(60);
            expect(scoreResult.grade).toMatch(/D|F/);
            expect(scoreResult.passed).toBe(false);
            expect(scoreResult.criticalPercentage).toBeLessThan(50);
            expect(scoreResult.missedItems.length).toBeGreaterThan(scoreResult.completedItems.length);
        });

        it('should validate borderline performance outcomes', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Simulate borderline performance - some critical items
            const borderlineActions = [
                { id: 'onset_timing', input: 'When did this start?' },
                { id: 'pain_character', input: 'What does the pain feel like?' },
                { id: 'vital_signs', input: 'Check vitals' },
                { id: 'ecg', input: 'Get ECG' },
                { id: 'medications', input: 'Current medications?' }
            ];

            borderlineActions.forEach(action => {
                performanceTracker.markChecklistItem(action.id, action.input);
            });

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Expected outcomes for borderline performance
            expect(scoreResult.percentageScore).toBeGreaterThanOrEqual(50);
            expect(scoreResult.percentageScore).toBeLessThan(75);
            expect(scoreResult.grade).toMatch(/C|D/);
            
            // Pass/fail should depend on critical items
            if (scoreResult.criticalPercentage >= 70) {
                expect(scoreResult.passed).toBe(true);
            } else {
                expect(scoreResult.passed).toBe(false);
            }
        });
    });

    describe('Scoring Consistency Validation', () => {
        it('should produce consistent scores for identical performance', () => {
            const identicalActions = [
                { id: 'onset_timing', input: 'When did the pain start?' },
                { id: 'ecg', input: 'I need an ECG' },
                { id: 'vital_signs', input: 'Check vital signs' }
            ];

            const scores = [];

            // Run identical scenario multiple times
            for (let i = 0; i < 5; i++) {
                performanceTracker.reset();
                performanceTracker.initializeChecklist(sampleCaseData);

                identicalActions.forEach(action => {
                    performanceTracker.markChecklistItem(action.id, action.input);
                });

                const performanceData = performanceTracker.getPerformanceData();
                const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);
                scores.push(scoreResult.percentageScore);
            }

            // All scores should be identical
            const firstScore = scores[0];
            scores.forEach(score => {
                expect(score).toBe(firstScore);
            });
        });

        it('should produce consistent relative scoring', () => {
            // Test that better performance always scores higher
            const scenarios = [
                {
                    name: 'minimal',
                    actions: [{ id: 'medications', input: 'What medications?' }]
                },
                {
                    name: 'basic',
                    actions: [
                        { id: 'onset_timing', input: 'When did pain start?' },
                        { id: 'vital_signs', input: 'Check vitals' }
                    ]
                },
                {
                    name: 'good',
                    actions: [
                        { id: 'onset_timing', input: 'When did pain start?' },
                        { id: 'pain_character', input: 'Describe pain' },
                        { id: 'vital_signs', input: 'Check vitals' },
                        { id: 'ecg', input: 'Get ECG' }
                    ]
                },
                {
                    name: 'excellent',
                    actions: [
                        { id: 'onset_timing', input: 'When did pain start?' },
                        { id: 'pain_character', input: 'Describe pain' },
                        { id: 'associated_symptoms', input: 'Other symptoms?' },
                        { id: 'vital_signs', input: 'Check vitals' },
                        { id: 'cardiovascular_exam', input: 'Examine heart' },
                        { id: 'ecg', input: 'Get ECG' },
                        { id: 'cardiac_enzymes', input: 'Order troponin' },
                        { id: 'primary_diagnosis', input: 'This is STEMI' }
                    ]
                }
            ];

            const scenarioScores = [];

            scenarios.forEach(scenario => {
                performanceTracker.reset();
                performanceTracker.initializeChecklist(sampleCaseData);

                scenario.actions.forEach(action => {
                    performanceTracker.markChecklistItem(action.id, action.input);
                });

                const performanceData = performanceTracker.getPerformanceData();
                const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);
                scenarioScores.push({
                    name: scenario.name,
                    score: scoreResult.percentageScore
                });
            });

            // Scores should be in ascending order
            for (let i = 1; i < scenarioScores.length; i++) {
                expect(scenarioScores[i].score).toBeGreaterThan(scenarioScores[i - 1].score);
            }
        });

        it('should maintain scoring stability with different input phrasing', () => {
            const phrasingSets = [
                [
                    { id: 'onset_timing', input: 'When did the pain start?' },
                    { id: 'ecg', input: 'I need an ECG' }
                ],
                [
                    { id: 'onset_timing', input: 'Tell me when this pain began' },
                    { id: 'ecg', input: 'Get me a 12-lead electrocardiogram' }
                ],
                [
                    { id: 'onset_timing', input: 'What time did your chest pain start?' },
                    { id: 'ecg', input: 'Order an ECG immediately' }
                ]
            ];

            const scores = [];

            phrasingSets.forEach(phrasingSet => {
                performanceTracker.reset();
                performanceTracker.initializeChecklist(sampleCaseData);

                phrasingSet.forEach(action => {
                    performanceTracker.markChecklistItem(action.id, action.input);
                });

                const performanceData = performanceTracker.getPerformanceData();
                const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);
                scores.push(scoreResult.percentageScore);
            });

            // Scores should be identical regardless of phrasing
            const firstScore = scores[0];
            scores.forEach(score => {
                expect(score).toBe(firstScore);
            });
        });
    });

    describe('Feedback Quality Validation', () => {
        it('should provide comprehensive feedback for all performance levels', () => {
            const performanceLevels = [
                { name: 'poor', actions: [{ id: 'medications', input: 'What meds?' }] },
                { name: 'average', actions: [
                    { id: 'onset_timing', input: 'When did pain start?' },
                    { id: 'vital_signs', input: 'Check vitals' },
                    { id: 'ecg', input: 'Get ECG' }
                ]},
                { name: 'excellent', actions: [
                    { id: 'onset_timing', input: 'When did pain start?' },
                    { id: 'pain_character', input: 'Describe pain' },
                    { id: 'vital_signs', input: 'Check vitals' },
                    { id: 'cardiovascular_exam', input: 'Examine heart' },
                    { id: 'ecg', input: 'Get ECG' },
                    { id: 'cardiac_enzymes', input: 'Order troponin' },
                    { id: 'primary_diagnosis', input: 'This is STEMI' }
                ]}
            ];

            performanceLevels.forEach(level => {
                performanceTracker.reset();
                performanceTracker.initializeChecklist(sampleCaseData);

                level.actions.forEach(action => {
                    performanceTracker.markChecklistItem(action.id, action.input);
                });

                const performanceData = performanceTracker.getPerformanceData();
                const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

                // All feedback should have required components
                expect(feedback.caseId).toBe(sampleCaseData.id);
                expect(feedback.summary).toBeDefined();
                expect(feedback.summary.length).toBeGreaterThan(20);
                expect(feedback.strengths).toBeDefined();
                expect(feedback.areasForImprovement).toBeDefined();
                expect(feedback.educationalFeedback).toBeDefined();
                expect(feedback.categoryFeedback).toBeDefined();
                expect(feedback.learningRecommendations).toBeDefined();
                expect(feedback.nextSteps).toBeDefined();

                // Poor performance should have more areas for improvement
                if (level.name === 'poor') {
                    expect(feedback.areasForImprovement.length).toBeGreaterThan(5);
                    expect(feedback.educationalFeedback.length).toBeGreaterThan(3);
                }

                // Excellent performance should have more strengths
                if (level.name === 'excellent') {
                    expect(feedback.strengths.length).toBeGreaterThan(3);
                    expect(feedback.areasForImprovement.length).toBeLessThan(3);
                }
            });
        });

        it('should provide educational feedback for missed critical items', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete some items but miss critical ones
            performanceTracker.markChecklistItem('medications', 'What medications?');
            performanceTracker.markChecklistItem('basic_labs', 'Get blood work');

            const performanceData = performanceTracker.getPerformanceData();
            const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

            // Should have educational feedback for missed critical items
            const criticalEducationalFeedback = feedback.educationalFeedback.filter(ef => 
                ['onset_timing', 'pain_character', 'ecg', 'cardiac_enzymes', 'primary_diagnosis'].includes(ef.itemId)
            );

            expect(criticalEducationalFeedback.length).toBeGreaterThan(3);

            // Each educational feedback should have required components
            criticalEducationalFeedback.forEach(ef => {
                expect(ef.itemId).toBeDefined();
                expect(ef.learningPoint).toBeDefined();
                expect(ef.clinicalRationale).toBeDefined();
                expect(ef.practicalTips).toBeDefined();
                expect(ef.learningPoint.length).toBeGreaterThan(10);
                expect(ef.clinicalRationale.length).toBeGreaterThan(20);
            });
        });

        it('should provide category-specific feedback', () => {
            performanceTracker.initializeChecklist(sampleCaseData);

            // Complete items in some categories but not others
            performanceTracker.markChecklistItem('onset_timing', 'When did pain start?');
            performanceTracker.markChecklistItem('pain_character', 'Describe pain');
            performanceTracker.markChecklistItem('ecg', 'Get ECG');

            const performanceData = performanceTracker.getPerformanceData();
            const feedback = scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

            // Should have feedback for all categories
            expect(feedback.categoryFeedback.historyTaking).toBeDefined();
            expect(feedback.categoryFeedback.physicalExamination).toBeDefined();
            expect(feedback.categoryFeedback.investigations).toBeDefined();
            expect(feedback.categoryFeedback.diagnosis).toBeDefined();
            expect(feedback.categoryFeedback.management).toBeDefined();

            // Categories with completed items should have positive feedback
            expect(feedback.categoryFeedback.historyTaking.feedback).toContain('completed');
            expect(feedback.categoryFeedback.investigations.feedback).toContain('completed');

            // Categories with no completed items should have improvement suggestions
            expect(feedback.categoryFeedback.physicalExamination.feedback).toContain('missed');
            expect(feedback.categoryFeedback.diagnosis.feedback).toContain('missed');
            expect(feedback.categoryFeedback.management.feedback).toContain('missed');
        });
    });

    describe('Edge Cases and Error Handling', () => {
        it('should handle empty performance data', () => {
            performanceTracker.initializeChecklist(sampleCaseData);
            // Don't complete any items

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            expect(scoreResult.totalScore).toBe(0);
            expect(scoreResult.percentageScore).toBe(0);
            expect(scoreResult.passed).toBe(false);
            expect(scoreResult.grade).toBe('F');
            expect(scoreResult.completedItems.length).toBe(0);
        });

        it('should handle malformed checklist data', () => {
            const malformedChecklist = {
                invalidCategory: {
                    weight: 'invalid',
                    items: [
                        { id: 'test', description: 'Test', points: 'invalid' }
                    ]
                }
            };

            performanceTracker.initializeChecklist({ checklist: malformedChecklist });
            const performanceData = performanceTracker.getPerformanceData();

            expect(() => {
                scoringEngine.calculateScore(performanceData, malformedChecklist);
            }).not.toThrow();
        });

        it('should handle missing checklist categories', () => {
            const incompleteChecklist = {
                historyTaking: {
                    weight: 100,
                    items: [
                        { id: 'test', description: 'Test', critical: true, points: 10 }
                    ]
                }
            };

            performanceTracker.initializeChecklist({ checklist: incompleteChecklist });
            performanceTracker.markChecklistItem('test', 'Test input');

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, incompleteChecklist);

            expect(scoreResult.percentageScore).toBe(100);
            expect(scoreResult.categoryScores.historyTaking.percentage).toBe(100);
        });
    });
});