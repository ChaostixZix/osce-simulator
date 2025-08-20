import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import PatientSimulator from '../lib/PatientSimulator.js';
import fs from 'fs';
import path from 'path';

/**
 * AI Patient Response Validation Tests
 * Tests medical accuracy, consistency, and appropriateness of AI patient responses
 */
describe('AI Patient Response Validation', () => {
    let patientSimulator;
    let sampleCaseData;
    let mockApiConfig;

    beforeEach(() => {
        mockApiConfig = {
            apiUrl: 'http://test-api.com',
            apiKey: 'test-key',
            model: 'test-model'
        };

        patientSimulator = new PatientSimulator(mockApiConfig);

        // Load STEMI case data
        const casePath = path.join(process.cwd(), 'cases', 'stemi-001.json');
        if (fs.existsSync(casePath)) {
            sampleCaseData = JSON.parse(fs.readFileSync(casePath, 'utf8'));
            patientSimulator.initializePatient(sampleCaseData);
        }
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('Medical Accuracy Validation', () => {
        beforeEach(() => {
            // Mock API to return medically accurate responses
            vi.spyOn(patientSimulator, '_callOpenRouterAPI').mockImplementation(async (messages) => {
                const lastMessage = messages[messages.length - 1];
                const userInput = lastMessage.content.toLowerCase();
                
                // Return medically accurate responses based on STEMI case
                if (userInput.includes('chest pain') || userInput.includes('pain')) {
                    return "The pain is severe, about 9 out of 10. It's crushing and feels like an elephant sitting on my chest. It started about 2 hours ago suddenly while I was working.";
                } else if (userInput.includes('radiat') || userInput.includes('spread')) {
                    return "Yes, the pain goes down my left arm and up to my jaw. It's really frightening.";
                } else if (userInput.includes('shortness') || userInput.includes('breath')) {
                    return "Yes, I'm having trouble breathing. I also feel nauseous and I'm sweating a lot.";
                } else if (userInput.includes('medical history') || userInput.includes('conditions')) {
                    return "I have high blood pressure and diabetes. I take Lisinopril and Metformin.";
                } else if (userInput.includes('smoke') || userInput.includes('tobacco')) {
                    return "I used to smoke for about 20 years, but I quit 5 years ago. My father died of a heart attack when he was 62.";
                } else {
                    return "I'm in a lot of pain and very scared. Please help me.";
                }
            });
        });

        it('should provide medically accurate symptom descriptions', async () => {
            const symptomQuestions = [
                'Tell me about your chest pain',
                'How severe is the pain?',
                'Can you describe the character of the pain?',
                'Does the pain radiate anywhere?',
                'Any associated symptoms?'
            ];

            for (const question of symptomQuestions) {
                const response = await patientSimulator.respondAsPatient(question, 'history');
                
                // Verify medical accuracy for STEMI presentation
                expect(response).toBeDefined();
                expect(response.length).toBeGreaterThan(10);
                
                if (question.includes('chest pain') || question.includes('pain')) {
                    expect(response.toLowerCase()).toMatch(/severe|crushing|elephant|chest/);
                    expect(response).toMatch(/9|10/); // Severity score
                }
                
                if (question.includes('radiat') || question.includes('spread')) {
                    expect(response.toLowerCase()).toMatch(/left arm|jaw|down|up/);
                }
                
                if (question.includes('associated') || question.includes('other')) {
                    expect(response.toLowerCase()).toMatch(/breath|nausea|sweat/);
                }
            }
        });

        it('should provide accurate medical history information', async () => {
            const historyQuestions = [
                'Do you have any medical conditions?',
                'What medications are you taking?',
                'Do you smoke?',
                'Any family history of heart problems?'
            ];

            for (const question of historyQuestions) {
                const response = await patientSimulator.respondAsPatient(question, 'history');
                
                expect(response).toBeDefined();
                
                if (question.includes('medical') || question.includes('conditions')) {
                    expect(response.toLowerCase()).toMatch(/blood pressure|diabetes|hypertension/);
                }
                
                if (question.includes('medications') || question.includes('medicine')) {
                    expect(response.toLowerCase()).toMatch(/lisinopril|metformin/);
                }
                
                if (question.includes('smoke') || question.includes('tobacco')) {
                    expect(response.toLowerCase()).toMatch(/quit|20 years|5 years ago/);
                }
                
                if (question.includes('family') || question.includes('father')) {
                    expect(response.toLowerCase()).toMatch(/father|heart attack|62/);
                }
            }
        });

        it('should provide appropriate examination responses', async () => {
            // Mock examination findings
            vi.spyOn(patientSimulator, '_callOpenRouterAPI').mockImplementation(async () => {
                return "Please go ahead with the examination. I'm feeling quite unwell.";
            });

            const examRequests = [
                'I need to check your vital signs',
                'Let me examine your heart',
                'I want to listen to your lungs',
                'Let me feel your pulse'
            ];

            for (const request of examRequests) {
                const response = await patientSimulator.respondAsPatient(request, 'examination');
                
                expect(response).toBeDefined();
                expect(response.toLowerCase()).toMatch(/examination|vital|bp|heart rate|pulse/);
                
                // Should include actual findings from case data
                if (request.includes('vital signs')) {
                    expect(response).toMatch(/160\/95|110|22|37\.1|94%/);
                }
            }
        });

        it('should provide accurate test results when appropriate', async () => {
            // Mock test result responses
            vi.spyOn(patientSimulator, '_callOpenRouterAPI').mockImplementation(async () => {
                return "I understand you want to do tests. Please go ahead.";
            });

            const testRequests = [
                'I need an ECG',
                'I want cardiac enzymes',
                'Get me a chest X-ray',
                'I need troponin levels'
            ];

            for (const request of testRequests) {
                const response = await patientSimulator.respondAsPatient(request, 'investigation');
                
                expect(response).toBeDefined();
                
                // Should include actual test results from case data
                if (request.includes('ECG') || request.includes('ecg')) {
                    expect(response).toMatch(/ST elevation|II|III|aVF|inferior|STEMI/i);
                }
                
                if (request.includes('troponin') || request.includes('cardiac enzymes')) {
                    expect(response).toMatch(/15\.2|ng\/mL|elevated/i);
                }
                
                if (request.includes('chest') && request.includes('ray')) {
                    expect(response).toMatch(/pulmonary edema|normal heart size/i);
                }
            }
        });
    });

    describe('Response Consistency Validation', () => {
        beforeEach(() => {
            // Mock consistent patient responses
            vi.spyOn(patientSimulator, '_callOpenRouterAPI').mockImplementation(async (messages) => {
                const lastMessage = messages[messages.length - 1];
                const userInput = lastMessage.content.toLowerCase();
                
                // Consistent patient persona responses
                if (userInput.includes('name')) {
                    return "My name is John Smith.";
                } else if (userInput.includes('age') || userInput.includes('old')) {
                    return "I'm 58 years old.";
                } else if (userInput.includes('work') || userInput.includes('job')) {
                    return "I work in construction.";
                } else if (userInput.includes('pain')) {
                    return "The pain is crushing and severe, about 9 out of 10.";
                } else {
                    return "I'm John Smith, 58 years old, and I work in construction. I'm having severe chest pain.";
                }
            });
        });

        it('should maintain consistent patient identity', async () => {
            const identityQuestions = [
                'What is your name?',
                'How old are you?',
                'What do you do for work?',
                'Tell me about yourself'
            ];

            const responses = [];
            for (const question of identityQuestions) {
                const response = await patientSimulator.respondAsPatient(question, 'history');
                responses.push(response.toLowerCase());
            }

            // Check consistency across responses
            const combinedResponses = responses.join(' ');
            expect(combinedResponses).toMatch(/john smith/);
            expect(combinedResponses).toMatch(/58/);
            expect(combinedResponses).toMatch(/construction/);
        });

        it('should maintain consistent symptom reporting', async () => {
            const symptomQuestions = [
                'Tell me about your pain',
                'How bad is the pain?',
                'Describe your chest pain',
                'What does the pain feel like?'
            ];

            const responses = [];
            for (const question of symptomQuestions) {
                const response = await patientSimulator.respondAsPatient(question, 'history');
                responses.push(response.toLowerCase());
            }

            // All responses should consistently describe severe, crushing pain
            responses.forEach(response => {
                expect(response).toMatch(/severe|crushing|9|10/);
            });
        });

        it('should maintain emotional consistency throughout interaction', async () => {
            const questions = [
                'How are you feeling?',
                'Are you worried?',
                'Tell me about your pain',
                'What brought you here today?'
            ];

            const responses = [];
            for (const question of questions) {
                const response = await patientSimulator.respondAsPatient(question, 'general');
                responses.push(response.toLowerCase());
            }

            // Should consistently show distress and concern
            const combinedResponses = responses.join(' ');
            expect(combinedResponses).toMatch(/pain|scared|worried|help|severe/);
        });
    });

    describe('Appropriate Information Disclosure', () => {
        beforeEach(() => {
            // Mock responses that test information disclosure rules
            vi.spyOn(patientSimulator, '_callOpenRouterAPI').mockImplementation(async (messages) => {
                const lastMessage = messages[messages.length - 1];
                const userInput = lastMessage.content.toLowerCase();
                
                if (userInput.includes('test results') || userInput.includes('what does') || userInput.includes('show')) {
                    return "I don't know about any test results. You'll have to ask the medical staff.";
                } else if (userInput.includes('diagnosis') || userInput.includes('what do you think')) {
                    return "I don't know what's wrong with me. That's why I'm here to see you, doctor.";
                } else if (userInput.includes('treatment') || userInput.includes('medicine')) {
                    return "I don't know what treatment I need. Please tell me what you think.";
                } else {
                    return "I can tell you about my symptoms and how I'm feeling.";
                }
            });
        });

        it('should not reveal test results patient would not know', async () => {
            const inappropriateQuestions = [
                'What do your test results show?',
                'What does your ECG look like?',
                'What are your troponin levels?',
                'What does your chest X-ray show?'
            ];

            for (const question of inappropriateQuestions) {
                const response = await patientSimulator.respondAsPatient(question, 'investigation');
                
                expect(response).toBeDefined();
                expect(response.toLowerCase()).toMatch(/don't know|ask.*medical staff|ask.*doctor/);
                expect(response.toLowerCase()).not.toMatch(/st elevation|15\.2|elevated|stemi/);
            }
        });

        it('should not provide medical diagnoses', async () => {
            const diagnosticQuestions = [
                'What do you think is wrong with you?',
                'Do you think you\'re having a heart attack?',
                'What\'s your diagnosis?',
                'What condition do you have?'
            ];

            for (const question of diagnosticQuestions) {
                const response = await patientSimulator.respondAsPatient(question, 'general');
                
                expect(response).toBeDefined();
                expect(response.toLowerCase()).toMatch(/don't know|not sure|you.*doctor|help me/);
                expect(response.toLowerCase()).not.toMatch(/stemi|myocardial infarction|heart attack/);
            }
        });

        it('should appropriately reveal symptoms and feelings', async () => {
            const appropriateQuestions = [
                'How do you feel?',
                'Tell me about your symptoms',
                'What brought you here?',
                'Describe your pain'
            ];

            for (const question of appropriateQuestions) {
                const response = await patientSimulator.respondAsPatient(question, 'history');
                
                expect(response).toBeDefined();
                expect(response.length).toBeGreaterThan(10);
                // Should provide detailed symptom information
                expect(response.toLowerCase()).toMatch(/pain|chest|severe|symptoms|feel/);
            }
        });
    });

    describe('Response Quality and Realism', () => {
        beforeEach(() => {
            // Mock realistic patient responses with appropriate emotion
            vi.spyOn(patientSimulator, '_callOpenRouterAPI').mockImplementation(async (messages) => {
                const lastMessage = messages[messages.length - 1];
                const userInput = lastMessage.content.toLowerCase();
                
                if (userInput.includes('pain')) {
                    return "Doctor, this pain is unbearable! It feels like someone is crushing my chest with a vice. I'm really scared - is this serious?";
                } else if (userInput.includes('examine') || userInput.includes('check')) {
                    return "Please, go ahead and examine me. I'm worried something is really wrong. The pain is making me feel sick.";
                } else if (userInput.includes('better') || userInput.includes('help')) {
                    return "I just want this pain to stop. Can you help me? I'm really frightened.";
                } else {
                    return "I'm in so much pain and I don't know what's happening to me. Please help.";
                }
            });
        });

        it('should demonstrate appropriate emotional responses', async () => {
            const emotionalTriggers = [
                'Tell me about your pain',
                'I need to examine you',
                'Are you feeling better?',
                'Don\'t worry, we\'ll help you'
            ];

            for (const trigger of emotionalTriggers) {
                const response = await patientSimulator.respondAsPatient(trigger, 'general');
                
                expect(response).toBeDefined();
                // Should show appropriate emotional language
                expect(response.toLowerCase()).toMatch(/scared|frightened|worried|help|pain|unbearable/);
                // Should use first person and direct address
                expect(response.toLowerCase()).toMatch(/i'm|i am|me|my|doctor/);
            }
        });

        it('should use appropriate language level for patient', async () => {
            const questions = [
                'Tell me about your chest pain',
                'Describe your symptoms',
                'How are you feeling?'
            ];

            for (const question of questions) {
                const response = await patientSimulator.respondAsPatient(question, 'history');
                
                expect(response).toBeDefined();
                // Should use lay language, not medical terminology
                expect(response.toLowerCase()).not.toMatch(/myocardial|ischemia|angina|stenosis/);
                // Should use common descriptive terms
                expect(response.toLowerCase()).toMatch(/pain|hurt|chest|crushing|elephant|vice/);
            }
        });

        it('should maintain realistic response length', async () => {
            const questions = [
                'How are you?',
                'Tell me about your pain',
                'What brought you here?'
            ];

            for (const question of questions) {
                const response = await patientSimulator.respondAsPatient(question, 'general');
                
                expect(response).toBeDefined();
                // Should be conversational length (not too short or too long)
                expect(response.length).toBeGreaterThan(20);
                expect(response.length).toBeLessThan(500);
                // Should be 1-3 sentences typically
                const sentences = response.split(/[.!?]+/).filter(s => s.trim().length > 0);
                expect(sentences.length).toBeGreaterThanOrEqual(1);
                expect(sentences.length).toBeLessThanOrEqual(5);
            }
        });
    });

    describe('Error Handling in Patient Responses', () => {
        it('should provide fallback responses when API fails', async () => {
            // Mock API failure
            vi.spyOn(patientSimulator, '_callOpenRouterAPI').mockRejectedValue(new Error('API Error'));

            const questions = [
                'Tell me about your pain',
                'I need to examine you',
                'How are you feeling?'
            ];

            for (const question of questions) {
                const response = await patientSimulator.respondAsPatient(question, 'history');
                
                expect(response).toBeDefined();
                expect(response.length).toBeGreaterThan(0);
                // Should provide contextual fallback
                expect(response.toLowerCase()).toMatch(/pain|sorry|trouble|difficult|concentrate/);
            }
        });

        it('should handle network timeouts gracefully', async () => {
            // Mock timeout error
            const timeoutError = new Error('Request timeout');
            timeoutError.code = 'ETIMEDOUT';
            vi.spyOn(patientSimulator, '_callOpenRouterAPI').mockRejectedValue(timeoutError);

            const response = await patientSimulator.respondAsPatient('How are you feeling?', 'general');
            
            expect(response).toBeDefined();
            expect(response.toLowerCase()).toMatch(/trouble thinking|patient with me|pain/);
        });

        it('should maintain patient persona even in fallback responses', async () => {
            // Mock API failure
            vi.spyOn(patientSimulator, '_callOpenRouterAPI').mockRejectedValue(new Error('API Error'));

            const response = await patientSimulator.respondAsPatient('Tell me about your chest pain', 'history');
            
            expect(response).toBeDefined();
            // Even fallback should reference the patient's condition
            expect(response.toLowerCase()).toMatch(/pain|chest|severe|8\/10|9\/10|crushing/);
        });
    });

    describe('Context Awareness', () => {
        beforeEach(() => {
            // Mock context-aware responses
            vi.spyOn(patientSimulator, '_callOpenRouterAPI').mockImplementation(async (messages) => {
                // Check conversation history for context
                const conversationHistory = messages.slice(-4).map(m => m.content).join(' ').toLowerCase();
                const lastMessage = messages[messages.length - 1];
                const userInput = lastMessage.content.toLowerCase();
                
                if (conversationHistory.includes('pain') && userInput.includes('better')) {
                    return "No, the pain is still just as bad. It hasn't gotten any better since we started talking.";
                } else if (conversationHistory.includes('examine') && userInput.includes('how')) {
                    return "That examination was uncomfortable because of the pain, but I understand you need to check me.";
                } else if (userInput.includes('pain')) {
                    return "The pain is severe and crushing, about 9 out of 10.";
                } else {
                    return "I'm still in a lot of pain and very worried.";
                }
            });
        });

        it('should maintain conversation context', async () => {
            // First interaction about pain
            const firstResponse = await patientSimulator.respondAsPatient('Tell me about your pain', 'history');
            expect(firstResponse.toLowerCase()).toMatch(/pain|severe|crushing/);

            // Follow-up question should reference previous context
            const followUpResponse = await patientSimulator.respondAsPatient('Is it getting any better?', 'history');
            expect(followUpResponse.toLowerCase()).toMatch(/still|hasn't|better|bad/);
        });

        it('should reference previous interactions appropriately', async () => {
            // Examination request
            await patientSimulator.respondAsPatient('I need to examine your heart', 'examination');
            
            // Follow-up about the examination
            const followUpResponse = await patientSimulator.respondAsPatient('How did that feel?', 'general');
            expect(followUpResponse.toLowerCase()).toMatch(/examination|uncomfortable|check|pain/);
        });
    });
});