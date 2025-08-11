import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import OSCEController from '../lib/OSCEController.js';
import fs from 'fs';
import path from 'path';

/**
 * STEMI Case End-to-End Test Scenarios
 * Tests complete STEMI case workflows with realistic clinical scenarios
 */
describe('STEMI Case End-to-End Scenarios', () => {
    let controller;
    let sampleCaseData;
    let mockApiConfig;

    beforeEach(async () => {
        mockApiConfig = {
            apiUrl: 'http://test-api.com',
            apiKey: 'test-key',
            model: 'test-model'
        };

        controller = new OSCEController(mockApiConfig);

        // Load STEMI case data
        const casePath = path.join(process.cwd(), 'cases', 'stemi-001.json');
        if (fs.existsSync(casePath)) {
            sampleCaseData = JSON.parse(fs.readFileSync(casePath, 'utf8'));
        }

        // Mock realistic STEMI patient responses
        vi.spyOn(controller.patientSimulator, '_callOpenRouterAPI').mockImplementation(async (messages) => {
            const lastMessage = messages[messages.length - 1];
            const userInput = lastMessage.content.toLowerCase();
            
            return getSTEMIPatientResponse(userInput);
        });

        // Initialize OSCE and select STEMI case
        await controller.startOSCE();
        await controller.selectCase('stemi-001');
    });

    afterEach(() => {
        vi.restoreAllMocks();
        controller.reset();
    });

    /**
     * Generate realistic STEMI patient responses based on input
     */
    function getSTEMIPatientResponse(userInput) {
        // History taking responses
        if (userInput.includes('chest pain') || userInput.includes('pain')) {
            return "Doctor, this pain is terrible! It feels like someone is crushing my chest with a heavy weight. It started about 2 hours ago when I was lifting something at work. It's the worst pain I've ever felt - I'd say it's a 9 out of 10.";
        }
        
        if (userInput.includes('radiat') || userInput.includes('spread') || userInput.includes('go')) {
            return "Yes, the pain goes down my left arm all the way to my fingers, and it's also going up to my jaw. It's really scary.";
        }
        
        if (userInput.includes('shortness') || userInput.includes('breath') || userInput.includes('breathing')) {
            return "Yes, I'm having trouble catching my breath. I also feel nauseous and I've been sweating a lot even though I'm not hot.";
        }
        
        if (userInput.includes('when') || userInput.includes('start') || userInput.includes('began')) {
            return "It started suddenly about 2 hours ago. I was lifting a heavy beam at the construction site and suddenly this crushing pain hit my chest. It hasn't gotten any better.";
        }
        
        if (userInput.includes('medical history') || userInput.includes('conditions') || userInput.includes('problems')) {
            return "I have high blood pressure and diabetes. I've had them for about 10 years now. I take pills for both of them.";
        }
        
        if (userInput.includes('medications') || userInput.includes('medicine') || userInput.includes('pills')) {
            return "I take Lisinopril for my blood pressure and Metformin for my diabetes. I take them every morning.";
        }
        
        if (userInput.includes('smoke') || userInput.includes('tobacco') || userInput.includes('cigarettes')) {
            return "I used to smoke a pack a day for about 20 years, but I quit 5 years ago when my doctor told me I had to. My father died of a heart attack when he was 62.";
        }
        
        if (userInput.includes('family') || userInput.includes('father') || userInput.includes('relatives')) {
            return "My father died of a heart attack when he was 62. My mother had diabetes too. Heart problems seem to run in my family.";
        }
        
        // Physical examination responses
        if (userInput.includes('examine') || userInput.includes('check') || userInput.includes('listen')) {
            return "Please go ahead, doctor. I'm really worried. The pain is still very bad and I'm scared something serious is wrong with me.";
        }
        
        if (userInput.includes('vital signs') || userInput.includes('blood pressure') || userInput.includes('pulse')) {
            return "Please check whatever you need to check. I just want to know what's wrong with me.";
        }
        
        // Investigation responses
        if (userInput.includes('ecg') || userInput.includes('ekg') || userInput.includes('heart test')) {
            return "Yes, please do whatever tests you think I need. I'm really scared this might be a heart attack.";
        }
        
        if (userInput.includes('blood') || userInput.includes('lab') || userInput.includes('test')) {
            return "Of course, take whatever blood you need. Will these tests tell us what's wrong?";
        }
        
        if (userInput.includes('x-ray') || userInput.includes('chest') && userInput.includes('picture')) {
            return "Yes, I'll do whatever tests you recommend. How long will it take to get the results?";
        }
        
        // Diagnosis and treatment responses
        if (userInput.includes('heart attack') || userInput.includes('stemi') || userInput.includes('myocardial')) {
            return "Oh my God, is that what this is? Am I going to be okay? What do we need to do?";
        }
        
        if (userInput.includes('treatment') || userInput.includes('medicine') || userInput.includes('help')) {
            return "Please do whatever you need to do to help me. I'm really scared. Will I be okay?";
        }
        
        if (userInput.includes('cardiology') || userInput.includes('specialist') || userInput.includes('catheter')) {
            return "Yes, I'll see whoever you think I should see. I just want to get better. Thank you for helping me.";
        }
        
        // Default responses
        if (userInput.includes('how') && userInput.includes('feel')) {
            return "I feel terrible, doctor. This pain is unbearable and I'm really scared. I've never felt anything like this before.";
        }
        
        return "I'm in so much pain, doctor. Please help me. I'm really scared something bad is happening.";
    }

    describe('Optimal STEMI Management Workflow', () => {
        it('should handle expert-level STEMI assessment and management', async () => {
            const expertWorkflow = [
                // Rapid initial assessment
                {
                    input: 'Tell me about your chest pain - when did it start and what does it feel like?',
                    expectedResponse: /crushing|chest|2 hours|9.*10|terrible/i,
                    category: 'history'
                },
                
                // Key history elements
                {
                    input: 'Does the pain radiate anywhere - to your arm, jaw, or back?',
                    expectedResponse: /left arm|jaw|fingers|scary/i,
                    category: 'history'
                },
                
                {
                    input: 'Any shortness of breath, nausea, sweating, or other symptoms?',
                    expectedResponse: /breath|nauseous|sweating/i,
                    category: 'history'
                },
                
                // Risk factors
                {
                    input: 'Do you have diabetes, high blood pressure, or other medical conditions?',
                    expectedResponse: /blood pressure|diabetes|10 years/i,
                    category: 'history'
                },
                
                {
                    input: 'Do you smoke or have any family history of heart disease?',
                    expectedResponse: /smoke|20 years|quit|father.*heart attack|62/i,
                    category: 'history'
                },
                
                // Immediate assessment
                {
                    input: 'I need to check your vital signs immediately',
                    expectedResponse: /check|worried|scared/i,
                    category: 'examination'
                },
                
                {
                    input: 'Let me quickly examine your heart and lungs',
                    expectedResponse: /ahead|worried|serious/i,
                    category: 'examination'
                },
                
                // Critical investigations
                {
                    input: 'I need a 12-lead ECG right now - this is urgent',
                    expectedResponse: /tests|scared|heart attack/i,
                    category: 'investigation'
                },
                
                {
                    input: 'I\'m ordering cardiac enzymes including troponin levels immediately',
                    expectedResponse: /blood|tests|results/i,
                    category: 'investigation'
                },
                
                // Diagnosis
                {
                    input: 'Based on your symptoms and tests, you\'re having a STEMI - a serious heart attack',
                    expectedResponse: /heart attack|god|okay|scared/i,
                    category: 'diagnosis'
                },
                
                // Emergency management
                {
                    input: 'We need to start emergency treatment immediately and get cardiology involved',
                    expectedResponse: /treatment|help|scared|okay/i,
                    category: 'management'
                }
            ];

            const responses = [];
            for (const step of expertWorkflow) {
                const response = await controller.processUserInput(step.input);
                responses.push({
                    input: step.input,
                    response: response,
                    category: step.category
                });

                // Validate response quality and medical accuracy
                expect(response).toBeDefined();
                expect(response.length).toBeGreaterThan(10);
                expect(response).toMatch(step.expectedResponse);
            }

            // Check final performance
            const endResult = await controller.endCase();
            expect(endResult).toContain('CASE COMPLETED');
            expect(endResult).toContain('FINAL SCORE');

            // Should achieve excellent score
            const performanceData = controller.performanceTracker.getPerformanceData();
            const scoreResult = controller.scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);
            
            expect(scoreResult.percentageScore).toBeGreaterThan(80);
            expect(scoreResult.grade).toMatch(/A|B/);
            expect(scoreResult.passed).toBe(true);
            expect(scoreResult.criticalPercentage).toBeGreaterThan(75);
        });

        it('should validate time-critical STEMI decision making', async () => {
            const startTime = Date.now();

            // Simulate urgent STEMI scenario
            const urgentActions = [
                'This patient has severe chest pain - I need vital signs and ECG immediately',
                'The ECG shows ST elevation - this is a STEMI',
                'Start aspirin, clopidogrel, and heparin now',
                'Call the cath lab for emergency PCI - door to balloon time is critical',
                'This patient needs immediate reperfusion therapy'
            ];

            for (const action of urgentActions) {
                const response = await controller.processUserInput(action);
                expect(response).toBeDefined();
                expect(response.length).toBeGreaterThan(5);
            }

            const endTime = Date.now();
            const duration = endTime - startTime;

            // Should handle urgent scenario efficiently
            expect(duration).toBeLessThan(3000);

            const performanceData = controller.performanceTracker.getPerformanceData();
            const scoreResult = controller.scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Should score well for rapid, appropriate management
            expect(scoreResult.percentageScore).toBeGreaterThan(70);
            expect(scoreResult.criticalPercentage).toBeGreaterThan(60);
        });
    });

    describe('Common STEMI Management Scenarios', () => {
        it('should handle systematic STEMI workup', async () => {
            const systematicWorkflow = [
                // Systematic history
                'Tell me about your chest pain',
                'When exactly did it start?',
                'Can you describe the quality of the pain?',
                'Does it radiate anywhere?',
                'Any associated symptoms?',
                'Rate the pain from 1 to 10',
                
                // Past medical history
                'Do you have any medical conditions?',
                'What medications do you take?',
                'Any allergies?',
                'Do you smoke?',
                'Any family history of heart disease?',
                
                // Physical examination
                'I need to check your vital signs',
                'Let me examine your heart',
                'I\'ll listen to your lungs',
                
                // Investigations
                'I need a 12-lead ECG',
                'I want cardiac enzymes',
                'Let me get a chest X-ray',
                'I need basic labs including CBC and BMP',
                
                // Assessment and plan
                'This appears to be an inferior STEMI',
                'We need to start dual antiplatelet therapy',
                'I\'m calling cardiology for urgent catheterization'
            ];

            let completedActions = 0;
            for (const action of systematicWorkflow) {
                const response = await controller.processUserInput(action);
                expect(response).toBeDefined();
                completedActions++;
            }

            expect(completedActions).toBe(systematicWorkflow.length);

            const endResult = await controller.endCase();
            const performanceData = controller.performanceTracker.getPerformanceData();
            const scoreResult = controller.scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Systematic approach should score very well
            expect(scoreResult.percentageScore).toBeGreaterThan(85);
            expect(scoreResult.categoryScores.historyTaking.percentage).toBeGreaterThan(80);
            expect(scoreResult.categoryScores.physicalExamination.percentage).toBeGreaterThan(70);
            expect(scoreResult.categoryScores.investigations.percentage).toBeGreaterThan(80);
        });

        it('should handle focused STEMI assessment', async () => {
            // Focused, efficient approach
            const focusedWorkflow = [
                'Tell me about your chest pain - onset, character, radiation, and severity',
                'Any shortness of breath, nausea, or diaphoresis?',
                'Medical history - diabetes, hypertension, smoking, family history?',
                'I need vital signs, heart and lung exam',
                'Get me a 12-lead ECG and cardiac enzymes stat',
                'This is a STEMI - start emergency protocol and call cardiology'
            ];

            for (const action of focusedWorkflow) {
                const response = await controller.processUserInput(action);
                expect(response).toBeDefined();
                expect(response.length).toBeGreaterThan(10);
            }

            const performanceData = controller.performanceTracker.getPerformanceData();
            const scoreResult = controller.scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Focused approach should still score well
            expect(scoreResult.percentageScore).toBeGreaterThan(70);
            expect(scoreResult.criticalPercentage).toBeGreaterThan(80);
        });
    });

    describe('Suboptimal STEMI Management Scenarios', () => {
        it('should handle delayed recognition scenario', async () => {
            // Simulate delayed or missed recognition
            const delayedWorkflow = [
                'What medications are you taking?',
                'Any allergies?',
                'Let me check your temperature',
                'I want to do some routine blood work',
                'Maybe we should get a chest X-ray',
                'This might be acid reflux or muscle strain'
            ];

            for (const action of delayedWorkflow) {
                const response = await controller.processUserInput(action);
                expect(response).toBeDefined();
            }

            const performanceData = controller.performanceTracker.getPerformanceData();
            const scoreResult = controller.scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Should score poorly due to missing critical elements
            expect(scoreResult.percentageScore).toBeLessThan(40);
            expect(scoreResult.passed).toBe(false);
            expect(scoreResult.criticalPercentage).toBeLessThan(30);

            // Should have many missed critical items
            expect(scoreResult.missedItems.length).toBeGreaterThan(8);
            
            const feedback = controller.scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);
            expect(feedback.areasForImprovement.length).toBeGreaterThan(5);
        });

        it('should handle incomplete assessment scenario', async () => {
            // Partial assessment missing key elements
            const incompleteWorkflow = [
                'Tell me about your chest pain',
                'I need to check your pulse',
                'Let me get some blood tests',
                'You might have chest pain'
            ];

            for (const action of incompleteWorkflow) {
                await controller.processUserInput(action);
            }

            const performanceData = controller.performanceTracker.getPerformanceData();
            const scoreResult = controller.scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Should score poorly
            expect(scoreResult.percentageScore).toBeLessThan(50);
            expect(scoreResult.grade).toMatch(/D|F/);

            // Should miss most critical items
            const criticalItems = [];
            for (const category of Object.values(sampleCaseData.checklist)) {
                for (const item of category.items) {
                    if (item.critical) criticalItems.push(item);
                }
            }

            expect(scoreResult.missedItems.length).toBeGreaterThan(criticalItems.length * 0.7);
        });
    });

    describe('STEMI-Specific Medical Accuracy', () => {
        it('should validate STEMI symptom recognition', async () => {
            const symptomQuestions = [
                'Describe your chest pain in detail',
                'When did this pain start?',
                'Does the pain go anywhere else?',
                'Any other symptoms with the chest pain?'
            ];

            const responses = [];
            for (const question of symptomQuestions) {
                const response = await controller.processUserInput(question);
                responses.push(response.toLowerCase());
            }

            const combinedResponses = responses.join(' ');

            // Should contain classic STEMI presentation elements
            expect(combinedResponses).toMatch(/crushing|elephant|weight|chest/);
            expect(combinedResponses).toMatch(/2 hours|sudden/);
            expect(combinedResponses).toMatch(/left arm|jaw|radiat/);
            expect(combinedResponses).toMatch(/breath|nausea|sweat/);
            expect(combinedResponses).toMatch(/9.*10|worst.*pain/);
        });

        it('should validate STEMI risk factor assessment', async () => {
            const riskFactorQuestions = [
                'Do you have diabetes or high blood pressure?',
                'Do you smoke or have you ever smoked?',
                'Any family history of heart problems?',
                'What is your occupation?'
            ];

            const responses = [];
            for (const question of riskFactorQuestions) {
                const response = await controller.processUserInput(question);
                responses.push(response.toLowerCase());
            }

            const combinedResponses = responses.join(' ');

            // Should identify STEMI risk factors
            expect(combinedResponses).toMatch(/diabetes|blood pressure|hypertension/);
            expect(combinedResponses).toMatch(/smoke|20 years|quit.*5 years/);
            expect(combinedResponses).toMatch(/father.*heart attack|62/);
            expect(combinedResponses).toMatch(/construction/);
        });

        it('should validate STEMI diagnostic test interpretation', async () => {
            const diagnosticRequests = [
                'I need a 12-lead ECG immediately',
                'Order cardiac enzymes including troponin',
                'Get me a chest X-ray'
            ];

            const responses = [];
            for (const request of diagnosticRequests) {
                const response = await controller.processUserInput(request);
                responses.push(response);
            }

            // Should include appropriate test results
            const combinedResponses = responses.join(' ').toLowerCase();
            expect(combinedResponses).toMatch(/st elevation|ii|iii|avf|inferior|stemi/);
            expect(combinedResponses).toMatch(/troponin|15\.2|elevated|ng\/ml/);
            expect(combinedResponses).toMatch(/pulmonary edema|heart size/);
        });

        it('should validate STEMI emergency management', async () => {
            const managementActions = [
                'This is a STEMI - we need emergency treatment',
                'Start aspirin and clopidogrel immediately',
                'Call cardiology for urgent catheterization',
                'We need to get to the cath lab quickly'
            ];

            const responses = [];
            for (const action of managementActions) {
                const response = await controller.processUserInput(action);
                responses.push(response.toLowerCase());
            }

            const combinedResponses = responses.join(' ');

            // Patient should show appropriate concern and compliance
            expect(combinedResponses).toMatch(/scared|worried|okay|help|treatment/);
            expect(combinedResponses).toMatch(/cardiology|specialist|catheter/);
        });
    });

    describe('Learning Objectives Validation', () => {
        it('should validate "Recognize classic presentation of STEMI"', async () => {
            const recognitionActions = [
                'Tell me about your chest pain',
                'Describe the character and severity',
                'Any radiation of the pain?',
                'Associated symptoms?'
            ];

            for (const action of recognitionActions) {
                await controller.processUserInput(action);
            }

            const performanceData = controller.performanceTracker.getPerformanceData();
            const completionStatus = controller.performanceTracker.getCompletionStatus();

            // Should complete key history items
            expect(completionStatus.categories.historyTaking.completionRate).toBeGreaterThan(50);
            
            const feedback = controller.scoringEngine.generateFeedback(performanceData, sampleCaseData.checklist);
            expect(feedback.summary).toMatch(/history|symptoms|presentation/i);
        });

        it('should validate "Perform systematic cardiovascular assessment"', async () => {
            const assessmentActions = [
                'I need to check your vital signs',
                'Let me examine your heart',
                'I want to listen to your lungs',
                'Check your pulse and blood pressure'
            ];

            for (const action of assessmentActions) {
                await controller.processUserInput(action);
            }

            const completionStatus = controller.performanceTracker.getCompletionStatus();
            expect(completionStatus.categories.physicalExamination.completionRate).toBeGreaterThan(70);
        });

        it('should validate "Order appropriate diagnostic tests"', async () => {
            const diagnosticActions = [
                'I need a 12-lead ECG stat',
                'Order cardiac enzymes and troponin',
                'Get me a chest X-ray',
                'I want basic labs including CBC'
            ];

            for (const action of diagnosticActions) {
                await controller.processUserInput(action);
            }

            const completionStatus = controller.performanceTracker.getCompletionStatus();
            expect(completionStatus.categories.investigations.completionRate).toBeGreaterThan(75);
        });

        it('should validate "Initiate time-sensitive emergency treatment"', async () => {
            const treatmentActions = [
                'This is a STEMI requiring immediate treatment',
                'Start emergency cardiac protocols',
                'Call cardiology for urgent intervention',
                'We need rapid reperfusion therapy'
            ];

            for (const action of treatmentActions) {
                await controller.processUserInput(action);
            }

            const completionStatus = controller.performanceTracker.getCompletionStatus();
            expect(completionStatus.categories.diagnosis.completionRate).toBeGreaterThan(50);
            expect(completionStatus.categories.management.completionRate).toBeGreaterThan(50);
        });
    });

    describe('Performance Benchmarking', () => {
        it('should benchmark against expected STEMI performance standards', async () => {
            // Simulate different performance levels
            const performanceScenarios = [
                {
                    name: 'Expert',
                    actions: [
                        'Tell me about your chest pain - onset, character, radiation, severity',
                        'Any dyspnea, nausea, diaphoresis, or other associated symptoms?',
                        'Medical history including diabetes, hypertension, smoking, family history',
                        'Vital signs and focused cardiovascular examination',
                        '12-lead ECG and cardiac biomarkers immediately',
                        'This is an inferior STEMI - emergency reperfusion needed',
                        'Dual antiplatelet therapy and urgent cardiology consultation'
                    ],
                    expectedScore: 85
                },
                {
                    name: 'Competent',
                    actions: [
                        'Tell me about your chest pain',
                        'When did it start and how severe is it?',
                        'Any other symptoms?',
                        'Do you have medical conditions?',
                        'I need to check your vital signs',
                        'Get an ECG and cardiac enzymes',
                        'This looks like a heart attack'
                    ],
                    expectedScore: 70
                },
                {
                    name: 'Novice',
                    actions: [
                        'What brings you here today?',
                        'Tell me about the pain',
                        'I should check your vitals',
                        'Maybe we need some tests',
                        'This could be heart-related'
                    ],
                    expectedScore: 45
                }
            ];

            for (const scenario of performanceScenarios) {
                // Reset for each scenario
                controller.reset();
                await controller.startOSCE();
                await controller.selectCase('stemi-001');

                for (const action of scenario.actions) {
                    await controller.processUserInput(action);
                }

                const performanceData = controller.performanceTracker.getPerformanceData();
                const scoreResult = controller.scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

                // Validate against expected performance
                expect(scoreResult.percentageScore).toBeGreaterThanOrEqual(scenario.expectedScore - 10);
                expect(scoreResult.percentageScore).toBeLessThanOrEqual(scenario.expectedScore + 10);

                if (scenario.name === 'Expert') {
                    expect(scoreResult.grade).toMatch(/A|B/);
                    expect(scoreResult.passed).toBe(true);
                } else if (scenario.name === 'Competent') {
                    expect(scoreResult.grade).toMatch(/B|C/);
                    expect(scoreResult.passed).toBe(true);
                } else if (scenario.name === 'Novice') {
                    expect(scoreResult.grade).toMatch(/D|F/);
                    expect(scoreResult.passed).toBe(false);
                }
            }
        });

        it('should validate critical item completion rates', async () => {
            // Complete critical items for STEMI
            const criticalActions = [
                'Tell me about your chest pain - when did it start?',
                'Describe the character of your chest pain',
                'I need to check your vital signs immediately',
                'Let me examine your heart',
                'I need a 12-lead ECG right now',
                'Order cardiac enzymes including troponin',
                'This is a STEMI - ST elevation myocardial infarction'
            ];

            for (const action of criticalActions) {
                await controller.processUserInput(action);
            }

            const performanceData = controller.performanceTracker.getPerformanceData();
            const scoreResult = controller.scoringEngine.calculateScore(performanceData, sampleCaseData.checklist);

            // Should achieve high critical item completion
            expect(scoreResult.criticalPercentage).toBeGreaterThan(80);
            expect(scoreResult.passed).toBe(true);

            // Validate specific critical items
            const completedCriticalItems = scoreResult.completedItems.filter(item => item.critical);
            expect(completedCriticalItems.length).toBeGreaterThan(5);
        });
    });
});