import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import OSCEController from '../lib/OSCEController.js';
import PatientSimulator from '../lib/PatientSimulator.js';
import CaseManager from '../lib/CaseManager.js';
import PerformanceTracker from '../lib/PerformanceTracker.js';
import ScoringEngine from '../lib/ScoringEngine.js';
import fs from 'fs';
import path from 'path';

/**
 * Comprehensive Integration Tests for OSCE Medical Training System
 * Tests complete workflows, AI patient responses, scoring validation, and STEMI case scenarios
 */
describe('OSCE Complete Integration Tests', () => {
    let controller;
    let mockApiConfig;
    let sampleCaseData;

    beforeEach(async () => {
        // Mock API configuration for testing
        mockApiConfig = {
            apiUrl: 'http://test-api.com',
            apiKey: 'test-key',
            model: 'test-model'
        };

        controller = new OSCEController(mockApiConfig);

        // Load sample case data for testing
        const casePath = path.join(process.cwd(), 'cases', 'stemi-001.json');
        if (fs.existsSync(casePath)) {
            sampleCaseData = JSON.parse(fs.readFileSync(casePath, 'utf8'));
        }

        // Mock API calls to avoid external dependencies
        vi.spyOn(controller.patientSimulator, '_callOpenRouterAPI').mockImplementation(async (messages) => {
            // Return contextual mock responses based on the last user message
            const lastMessage = messages[messages.length - 1];
            const userInput = lastMessage.content.toLowerCase();
            
            if (userInput.includes('chest pain') || userInput.includes('pain')) {
                return "The pain is severe, crushing, and started about 2 hours ago. It's radiating to my left arm and jaw.";
            } else if (userInput.includes('examine') || userInput.includes('vital signs')) {
                return "Please go ahead and examine me. I'm feeling quite unwell.";
            } else if (userInput.includes('ecg') || userInput.includes('test')) {
                return "I understand you want to do some tests. Please go ahead.";
            } else if (userInput.includes('diagnosis') || userInput.includes('think')) {
                return "What do you think is wrong with me, doctor?";
            } else {
                return "I'm in a lot of pain and feeling very worried. Please help me.";
            }
        });
    });

    afterEach(() => {
        vi.restoreAllMocks();
        controller.reset();
    });

    describe('Complete OSCE Workflow Integration', () => {
        it('should execute complete workflow from start to finish', async () => {
            // Step 1: Start OSCE system
            const startResponse = await controller.startOSCE();
            expect(startResponse).toContain('OSCE Case Selection');
            expect(controller.isActive).toBe(true);
            expect(controller.awaitingCaseSelection).toBe(true);

            // Step 2: Select STEMI case
            const selectResponse = await controller.selectCase('stemi-001');
            expect(selectResponse).toContain('Case Started');
            expect(controller.currentCase).toBeDefined();
            expect(controller.currentCase.id).toBe('stemi-001');
            expect(controller.awaitingCaseSelection).toBe(false);

            // Step 3: Simulate comprehensive clinical interaction
            const interactions = [
                'Tell me about your chest pain',
                'When did the pain start?',
                'Can you describe the pain?',
                'Any other symptoms?',
                'Do you have any medical history?',
                'What medications are you taking?',
                'I need to check your vital signs',
                'Let me examine your heart',
                'I need to get an ECG',
                'I want to order cardiac enzymes',
                'I think you might be having a heart attack'
            ];

            const responses = [];
            for (const interaction of interactions) {
                const response = await controller.processUserInput(interaction);
                expect(response).toBeDefined();
                expect(typeof response).toBe('string');
                expect(response.length).toBeGreaterThan(0);
                responses.push(response);
            }

            // Step 4: Check progress during case
            const progressResponse = controller.getCurrentProgress();
            expect(progressResponse).toContain('Progress Report');
            expect(progressResponse).toContain('Overall Progress');

            // Step 5: End case and get results
            const endResponse = await controller.endCase();
            expect(endResponse).toContain('CASE COMPLETED');
            expect(endResponse).toContain('FINAL SCORE');
            expect(controller.showingResults).toBe(true);

            // Verify final state
            const finalState = controller.getState();
            expect(finalState.isActive).toBe(true);
            expect(finalState.showingResults).toBe(true);
            expect(finalState.currentCase).toBe('stemi-001');
        });

        it('should handle multiple case sessions', async () => {
            // First case session
            await controller.startOSCE();
            await controller.selectCase('stemi-001');
            await controller.processUserInput('Tell me about your symptoms');
            await controller.endCase();

            // Start new case session
            const newCaseResponse = await controller.processUserInput('new case');
            expect(newCaseResponse).toContain('OSCE Case Selection');
            expect(controller.awaitingCaseSelection).toBe(true);
            expect(controller.showingResults).toBe(false);

            // Select same case again
            const selectResponse = await controller.selectCase('stemi-001');
            expect(selectResponse).toContain('Case Started');
            expect(controller.currentCase.id).toBe('stemi-001');
        });

        it('should maintain session state throughout workflow', async () => {
            await controller.startOSCE();
            await controller.selectCase('stemi-001');

            const initialState = controller.getState();
            expect(initialState.sessionDuration).toBeGreaterThanOrEqual(0);

            // Simulate some time passing
            await new Promise(resolve => setTimeout(resolve, 100));
            await controller.processUserInput('Tell me about your pain');

            const laterState = controller.getState();
            expect(laterState.sessionDuration).toBeGreaterThan(initialState.sessionDuration);
        });
    });

    describe('AI Patient Response Validation', () => {
        beforeEach(async () => {
            await controller.startOSCE();
            await controller.selectCase('stemi-001');
        });

        it('should provide medically accurate responses for history taking', async () => {
            const historyQuestions = [
                'Tell me about your chest pain',
                'When did the pain start?',
                'Can you describe the character of the pain?',
                'Does the pain radiate anywhere?',
                'How severe is the pain on a scale of 1-10?'
            ];

            for (const question of historyQuestions) {
                const response = await controller.processUserInput(question);
                
                // Verify response quality
                expect(response).toBeDefined();
                expect(response.length).toBeGreaterThan(10);
                expect(response.length).toBeLessThan(1000); // Reasonable length
                
                // Check for medical consistency with STEMI case
                if (question.includes('chest pain') || question.includes('pain')) {
                    expect(response.toLowerCase()).toMatch(/pain|chest|severe|crushing/);
                }
                
                if (question.includes('when') || question.includes('start')) {
                    expect(response.toLowerCase()).toMatch(/hour|ago|start|began/);
                }
            }
        });

        it('should provide appropriate examination findings', async () => {
            const examRequests = [
                'I need to check your vital signs',
                'Let me examine your heart',
                'I want to listen to your lungs',
                'Let me check your pulse'
            ];

            for (const request of examRequests) {
                const response = await controller.processUserInput(request);
                
                expect(response).toBeDefined();
                expect(response.length).toBeGreaterThan(5);
                
                // Should include examination findings for STEMI case
                if (request.includes('vital signs')) {
                    expect(response).toMatch(/BP|blood pressure|heart rate|pulse|160|110/i);
                }
                
                if (request.includes('heart') || request.includes('cardiovascular')) {
                    expect(response).toMatch(/heart|cardiac|rhythm|tachycardic/i);
                }
            }
        });

        it('should provide test results when requested', async () => {
            const testRequests = [
                'I need an ECG',
                'I want to order cardiac enzymes',
                'Get me a chest X-ray',
                'I need troponin levels'
            ];

            for (const request of testRequests) {
                const response = await controller.processUserInput(request);
                
                expect(response).toBeDefined();
                
                if (request.includes('ECG') || request.includes('ecg')) {
                    expect(response).toMatch(/ECG|ST elevation|leads|II|III|aVF/i);
                }
                
                if (request.includes('troponin') || request.includes('cardiac enzymes')) {
                    expect(response).toMatch(/troponin|elevated|15\.2|ng\/mL/i);
                }
            }
        });

        it('should maintain consistent patient persona', async () => {
            const responses = [];
            
            // Ask multiple questions to test consistency
            const questions = [
                'What is your name?',
                'How old are you?',
                'What do you do for work?',
                'Tell me about your medical history'
            ];

            for (const question of questions) {
                const response = await controller.processUserInput(question);
                responses.push(response);
            }

            // Verify consistency with case data
            const combinedResponses = responses.join(' ').toLowerCase();
            
            if (sampleCaseData) {
                // Check for consistent patient information
                expect(combinedResponses).toMatch(/john|smith/i);
                expect(combinedResponses).toMatch(/58|fifty/i);
                expect(combinedResponses).toMatch(/construction|worker/i);
            }
        });

        it('should handle inappropriate requests gracefully', async () => {
            const inappropriateRequests = [
                'What are your test results?',
                'What does your ECG show?',
                'What is your diagnosis?',
                'What treatment do you need?'
            ];

            for (const request of inappropriateRequests) {
                const response = await controller.processUserInput(request);
                
                expect(response).toBeDefined();
                // Patient shouldn't know medical details they wouldn't know
                expect(response.toLowerCase()).toMatch(/don't know|not sure|ask.*doctor|medical staff/);
            }
        });
    });

    describe('Scoring System Validation', () => {
        let performanceTracker;
        let scoringEngine;

        beforeEach(async () => {
            await controller.startOSCE();
            await controller.selectCase('stemi-001');
            
            performanceTracker = controller.performanceTracker;
            scoringEngine = controller.scoringEngine;
        });

        it('should accurately track and score comprehensive clinical performance', async () => {
            // Simulate comprehensive clinical interaction
            const clinicalActions = [
                { input: 'Tell me about your chest pain', expectedItems: ['onset_timing', 'pain_character'] },
                { input: 'Any other symptoms?', expectedItems: ['associated_symptoms'] },
                { input: 'Do you have any medical history?', expectedItems: ['past_medical_history'] },
                { input: 'What medications are you taking?', expectedItems: ['medications'] },
                { input: 'Do you smoke?', expectedItems: ['risk_factors'] },
                { input: 'I need to check your vital signs', expectedItems: ['vital_signs'] },
                { input: 'Let me examine your heart', expectedItems: ['cardiovascular_exam'] },
                { input: 'I need an ECG', expectedItems: ['ecg'] },
                { input: 'I want cardiac enzymes', expectedItems: ['cardiac_enzymes'] },
                { input: 'This looks like a STEMI', expectedItems: ['primary_diagnosis'] }
            ];

            let totalExpectedItems = 0;
            for (const action of clinicalActions) {
                await controller.processUserInput(action.input);
                totalExpectedItems += action.expectedItems.length;
            }

            // Get performance data and calculate score
            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Validate scoring accuracy
            expect(scoreResult).toBeDefined();
            expect(scoreResult.totalScore).toBeGreaterThan(0);
            expect(scoreResult.percentageScore).toBeGreaterThanOrEqual(0);
            expect(scoreResult.percentageScore).toBeLessThanOrEqual(100);
            expect(scoreResult.passed).toBeDefined();
            expect(scoreResult.grade).toMatch(/[A-F]/);

            // Validate category scores
            expect(scoreResult.categoryScores).toBeDefined();
            expect(scoreResult.categoryScores.historyTaking).toBeDefined();
            expect(scoreResult.categoryScores.physicalExamination).toBeDefined();
            expect(scoreResult.categoryScores.investigations).toBeDefined();

            // Validate completed vs missed items
            expect(scoreResult.completedItems).toBeDefined();
            expect(scoreResult.missedItems).toBeDefined();
            expect(scoreResult.completedItems.length + scoreResult.missedItems.length).toBeGreaterThan(0);
        });

        it('should provide accurate feedback for different performance levels', async () => {
            // Test excellent performance
            const excellentActions = [
                'Tell me about your chest pain - when did it start and what does it feel like?',
                'Any other symptoms like shortness of breath or nausea?',
                'Do you have any medical history or take medications?',
                'Do you smoke or have family history of heart disease?',
                'I need to check your vital signs and examine your heart',
                'I need an ECG and cardiac enzymes immediately',
                'This appears to be a STEMI - we need emergency treatment'
            ];

            for (const action of excellentActions) {
                await controller.processUserInput(action);
            }

            const excellentPerformance = performanceTracker.getPerformanceData();
            const excellentFeedback = scoringEngine.generateFeedback(excellentPerformance, sampleCaseData.checklist);

            expect(excellentFeedback.summary).toBeDefined();
            expect(excellentFeedback.strengths.length).toBeGreaterThan(0);
            expect(excellentFeedback.learningRecommendations).toBeDefined();

            // Reset and test poor performance
            performanceTracker.reset();
            performanceTracker.initializeChecklist(sampleCaseData);

            await controller.processUserInput('What medications are you taking?');

            const poorPerformance = performanceTracker.getPerformanceData();
            const poorFeedback = scoringEngine.generateFeedback(poorPerformance, sampleCaseData.checklist);

            expect(poorFeedback.areasForImprovement.length).toBeGreaterThan(excellentFeedback.areasForImprovement.length);
            expect(poorFeedback.educationalFeedback.length).toBeGreaterThan(0);
        });

        it('should validate critical vs non-critical item scoring', async () => {
            // Complete only critical items
            const criticalActions = [
                'Tell me about your chest pain',
                'Describe the pain character',
                'I need to check vital signs',
                'Let me examine your heart',
                'I need an ECG',
                'I want cardiac enzymes',
                'This is a STEMI'
            ];

            for (const action of criticalActions) {
                await controller.processUserInput(action);
            }

            const performanceData = performanceTracker.getPerformanceData();
            const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Critical items should have higher impact on score
            expect(scoreResult.criticalPercentage).toBeGreaterThan(scoreResult.percentageScore - 20);
            expect(scoreResult.criticalItemsScore).toBeGreaterThan(0);
        });

        it('should validate scoring consistency across multiple runs', async () => {
            const actions = [
                'Tell me about your chest pain',
                'I need to check vital signs',
                'I need an ECG'
            ];

            const scores = [];

            // Run same scenario multiple times
            for (let i = 0; i < 3; i++) {
                performanceTracker.reset();
                performanceTracker.initializeChecklist(sampleCaseData);

                for (const action of actions) {
                    await controller.processUserInput(action);
                }

                const performanceData = performanceTracker.getPerformanceData();
                const scoreResult = scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);
                scores.push(scoreResult.percentageScore);
            }

            // Scores should be consistent
            const avgScore = scores.reduce((a, b) => a + b, 0) / scores.length;
            scores.forEach(score => {
                expect(Math.abs(score - avgScore)).toBeLessThan(5); // Within 5% variance
            });
        });
    });

    describe('STEMI Case End-to-End Scenarios', () => {
        beforeEach(async () => {
            await controller.startOSCE();
            await controller.selectCase('stemi-001');
        });

        it('should handle optimal STEMI case workflow', async () => {
            // Optimal clinical approach for STEMI
            const optimalWorkflow = [
                // History taking
                { action: 'Tell me about your chest pain - when did it start?', category: 'history' },
                { action: 'Can you describe the pain - is it crushing, sharp, or burning?', category: 'history' },
                { action: 'Does the pain go anywhere else like your arm or jaw?', category: 'history' },
                { action: 'Any shortness of breath, nausea, or sweating?', category: 'history' },
                { action: 'Do you have diabetes, high blood pressure, or heart problems?', category: 'history' },
                { action: 'Do you smoke or have family history of heart disease?', category: 'history' },
                
                // Physical examination
                { action: 'I need to check your vital signs immediately', category: 'examination' },
                { action: 'Let me listen to your heart and lungs', category: 'examination' },
                
                // Investigations
                { action: 'I need a 12-lead ECG right now', category: 'investigation' },
                { action: 'I want cardiac enzymes including troponin', category: 'investigation' },
                { action: 'Get me a chest X-ray', category: 'investigation' },
                
                // Diagnosis and management
                { action: 'This appears to be a STEMI - ST elevation myocardial infarction', category: 'diagnosis' },
                { action: 'We need to start emergency treatment immediately', category: 'management' },
                { action: 'I need to call cardiology for urgent catheterization', category: 'management' }
            ];

            const responses = [];
            for (const step of optimalWorkflow) {
                const response = await controller.processUserInput(step.action);
                responses.push({ action: step.action, response, category: step.category });
                
                // Verify appropriate response for each category
                expect(response).toBeDefined();
                expect(response.length).toBeGreaterThan(5);
            }

            // Check final performance
            const finalProgress = controller.getCurrentProgress();
            expect(finalProgress).toContain('Progress Report');

            const endResult = await controller.endCase();
            expect(endResult).toContain('CASE COMPLETED');
            expect(endResult).toContain('FINAL SCORE');

            // Should achieve high score with optimal workflow
            const performanceData = controller.performanceTracker.getPerformanceData();
            const scoreResult = controller.scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);
            expect(scoreResult.percentageScore).toBeGreaterThan(70); // Should score well
        });

        it('should handle suboptimal STEMI case workflow', async () => {
            // Suboptimal approach - missing critical steps
            const suboptimalWorkflow = [
                'What medications are you taking?', // Non-critical first
                'Let me check your temperature', // Less relevant
                'I want to do some blood tests', // Vague
                'You might have chest pain' // Poor diagnosis
            ];

            for (const action of suboptimalWorkflow) {
                await controller.processUserInput(action);
            }

            const endResult = await controller.endCase();
            expect(endResult).toContain('CASE COMPLETED');

            // Should achieve lower score
            const performanceData = controller.performanceTracker.getPerformanceData();
            const scoreResult = controller.scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);
            expect(scoreResult.percentageScore).toBeLessThan(50); // Should score poorly
            expect(scoreResult.passed).toBe(false);
        });

        it('should validate STEMI-specific medical accuracy', async () => {
            // Test STEMI-specific responses
            const stemiQuestions = [
                'Tell me about your chest pain',
                'I need an ECG',
                'What do the cardiac enzymes show?',
                'This looks like a heart attack'
            ];

            const responses = [];
            for (const question of stemiQuestions) {
                const response = await controller.processUserInput(question);
                responses.push(response);
            }

            const combinedResponses = responses.join(' ').toLowerCase();

            // Should contain STEMI-specific information
            expect(combinedResponses).toMatch(/chest|pain|crushing|severe/);
            expect(combinedResponses).toMatch(/st elevation|stemi|inferior/);
            expect(combinedResponses).toMatch(/troponin|elevated|15\.2/);
        });

        it('should handle time-sensitive STEMI scenario', async () => {
            const startTime = Date.now();

            // Simulate urgent STEMI workflow
            const urgentActions = [
                'This patient has severe chest pain - I need vital signs now',
                'Get me an ECG immediately',
                'This shows ST elevation - it\'s a STEMI',
                'Start emergency treatment and call cardiology'
            ];

            for (const action of urgentActions) {
                const response = await controller.processUserInput(action);
                expect(response).toBeDefined();
            }

            const endTime = Date.now();
            const duration = endTime - startTime;

            // Should handle urgent scenario efficiently
            expect(duration).toBeLessThan(5000); // Should complete quickly

            const performanceData = controller.performanceTracker.getPerformanceData();
            expect(performanceData.actionLog.totalActions).toBe(urgentActions.length);
        });

        it('should validate learning objectives achievement', async () => {
            // Test against STEMI learning objectives
            const learningObjectiveActions = [
                // "Recognize classic presentation of STEMI"
                'Tell me about your chest pain and when it started',
                
                // "Perform systematic cardiovascular assessment"
                'I need to check your vital signs and examine your heart',
                
                // "Order appropriate diagnostic tests"
                'I need an ECG and cardiac enzymes',
                
                // "Initiate time-sensitive emergency treatment"
                'This is a STEMI - we need emergency treatment'
            ];

            for (const action of learningObjectiveActions) {
                await controller.processUserInput(action);
            }

            const performanceData = controller.performanceTracker.getPerformanceData();
            const feedback = controller.scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);

            // Should address learning objectives
            expect(feedback.summary).toBeDefined();
            expect(feedback.categoryFeedback).toBeDefined();
            expect(feedback.learningRecommendations).toBeDefined();

            // Check that major categories were addressed
            const completionStatus = controller.performanceTracker.getCompletionStatus();
            expect(completionStatus.categories.historyTaking.completionRate).toBeGreaterThan(0);
            expect(completionStatus.categories.investigations.completionRate).toBeGreaterThan(0);
        });
    });

    describe('Error Handling and Recovery', () => {
        it('should handle API failures gracefully during patient simulation', async () => {
            // Mock API failure
            vi.spyOn(controller.patientSimulator, '_callOpenRouterAPI').mockRejectedValue(new Error('API Error'));

            await controller.startOSCE();
            await controller.selectCase('stemi-001');

            const response = await controller.processUserInput('Tell me about your pain');
            
            // Should provide fallback response
            expect(response).toBeDefined();
            expect(response.length).toBeGreaterThan(0);
            expect(response.toLowerCase()).toMatch(/sorry|trouble|pain|difficult/);
        });

        it('should handle case loading errors', async () => {
            // Mock case loading failure
            vi.spyOn(controller.caseManager, 'loadAvailableCases').mockResolvedValue([]);

            const startResponse = await controller.startOSCE();
            expect(startResponse).toContain('No OSCE cases are currently available');
        });

        it('should handle invalid user inputs', async () => {
            await controller.startOSCE();
            await controller.selectCase('stemi-001');

            const invalidInputs = ['', '   ', null, undefined];
            
            for (const input of invalidInputs) {
                const response = await controller.processUserInput(input);
                expect(response).toContain('Please enter a valid question');
            }
        });

        it('should recover from multiple consecutive errors', async () => {
            // Mock multiple API failures
            let callCount = 0;
            vi.spyOn(controller.patientSimulator, '_callOpenRouterAPI').mockImplementation(async () => {
                callCount++;
                if (callCount <= 3) {
                    throw new Error('API Error');
                }
                return 'I\'m feeling better now and can respond.';
            });

            await controller.startOSCE();
            await controller.selectCase('stemi-001');

            // First few calls should fail but provide fallbacks
            for (let i = 0; i < 3; i++) {
                const response = await controller.processUserInput('How are you feeling?');
                expect(response).toBeDefined();
            }

            // Fourth call should succeed
            const successResponse = await controller.processUserInput('How are you feeling?');
            expect(successResponse).toContain('feeling better');
        });
    });

    describe('Performance and Scalability', () => {
        it('should handle long conversation sessions efficiently', async () => {
            await controller.startOSCE();
            await controller.selectCase('stemi-001');

            const startTime = Date.now();
            
            // Simulate long conversation (20 interactions)
            for (let i = 0; i < 20; i++) {
                await controller.processUserInput(`Question ${i + 1}: Tell me about your symptoms`);
            }

            const endTime = Date.now();
            const totalDuration = endTime - startTime;
            const averageResponseTime = totalDuration / 20;

            // Should maintain reasonable performance
            expect(averageResponseTime).toBeLessThan(1000); // Less than 1 second per response
        });

        it('should manage memory usage during extended sessions', async () => {
            await controller.startOSCE();
            await controller.selectCase('stemi-001');

            // Check initial memory usage
            const initialMemory = process.memoryUsage();

            // Simulate extended session
            for (let i = 0; i < 50; i++) {
                await controller.processUserInput(`Interaction ${i}: How are you feeling?`);
            }

            const finalMemory = process.memoryUsage();
            const memoryIncrease = finalMemory.heapUsed - initialMemory.heapUsed;

            // Memory increase should be reasonable (less than 50MB)
            expect(memoryIncrease).toBeLessThan(50 * 1024 * 1024);
        });
    });
});