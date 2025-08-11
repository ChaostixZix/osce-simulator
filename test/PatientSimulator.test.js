import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import PatientSimulator from '../lib/PatientSimulator.js';
import axios from 'axios';

// Mock axios
vi.mock('axios');

describe('PatientSimulator', () => {
    let simulator;
    let mockCaseData;
    let mockApiConfig;

    beforeEach(() => {
        mockApiConfig = {
            apiUrl: 'https://test-api.com',
            apiKey: 'test-key',
            model: 'test-model'
        };

        mockCaseData = {
            id: 'test-001',
            chiefComplaint: 'Chest pain for 2 hours',
            patientInfo: {
                name: 'John Doe',
                age: 45,
                gender: 'male',
                occupation: 'Teacher'
            },
            presentingSymptoms: {
                primary: 'Severe chest pain',
                associated: ['Shortness of breath', 'Nausea'],
                onset: '2 hours ago',
                character: 'Crushing',
                radiation: 'Left arm',
                severity: '8/10'
            },
            medicalHistory: {
                pastMedical: ['Hypertension'],
                medications: ['Lisinopril'],
                allergies: ['NKDA'],
                socialHistory: {
                    smoking: '10 pack-years',
                    alcohol: 'Social',
                    familyHistory: 'Father had MI at 60'
                }
            },
            physicalExamination: {
                vitalSigns: {
                    bp: '150/90',
                    hr: '100',
                    rr: '20',
                    temp: '37.0',
                    o2sat: '95%'
                },
                general: 'Anxious, diaphoretic',
                cardiovascular: 'Regular rhythm, no murmurs',
                respiratory: 'Clear bilaterally'
            },
            investigations: {
                ecg: {
                    findings: 'ST elevation in V2-V4',
                    interpretation: 'Anterior STEMI'
                },
                labs: {
                    troponin: '12.5 ng/mL (elevated)',
                    ck: '400 U/L (elevated)'
                },
                imaging: {
                    chestXray: 'Normal heart size'
                }
            }
        };

        simulator = new PatientSimulator(mockApiConfig);
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    describe('Constructor', () => {
        it('should initialize with provided API config', () => {
            expect(simulator.apiUrl).toBe(mockApiConfig.apiUrl);
            expect(simulator.apiKey).toBe(mockApiConfig.apiKey);
            expect(simulator.model).toBe(mockApiConfig.model);
        });

        it('should use environment variables when no config provided', () => {
            process.env.API_URL = 'env-url';
            process.env.API_KEY = 'env-key';
            process.env.API_MODEL = 'env-model';

            const envSimulator = new PatientSimulator();
            expect(envSimulator.apiUrl).toBe('env-url');
            expect(envSimulator.apiKey).toBe('env-key');
            expect(envSimulator.model).toBe('env-model');
        });

        it('should initialize with null case data and empty conversation history', () => {
            expect(simulator.caseData).toBeNull();
            expect(simulator.conversationHistory).toEqual([]);
        });
    });

    describe('initializePatient', () => {
        it('should set case data and build patient persona', () => {
            simulator.initializePatient(mockCaseData);

            expect(simulator.caseData).toBe(mockCaseData);
            expect(simulator.patientPersona).toContain('John Doe');
            expect(simulator.patientPersona).toContain('45-year-old male');
            expect(simulator.patientPersona).toContain('Chest pain for 2 hours');
            expect(simulator.conversationHistory).toEqual([]);
        });

        it('should include all relevant patient information in persona', () => {
            simulator.initializePatient(mockCaseData);

            expect(simulator.patientPersona).toContain('Severe chest pain');
            expect(simulator.patientPersona).toContain('Shortness of breath, Nausea');
            expect(simulator.patientPersona).toContain('Hypertension');
            expect(simulator.patientPersona).toContain('Lisinopril');
            expect(simulator.patientPersona).toContain('10 pack-years');
        });
    });

    describe('shouldRevealInformation', () => {
        beforeEach(() => {
            simulator.initializePatient(mockCaseData);
        });

        it('should reveal information for history requests', () => {
            expect(simulator.shouldRevealInformation('history', 'Tell me about your pain')).toBe(true);
            expect(simulator.shouldRevealInformation('general', 'Tell me about your symptoms')).toBe(true);
            expect(simulator.shouldRevealInformation('general', 'Describe your pain')).toBe(true);
        });

        it('should reveal examination findings only when specifically requested', () => {
            expect(simulator.shouldRevealInformation('examination', 'I want to examine your chest')).toBe(true);
            expect(simulator.shouldRevealInformation('examination', 'Let me check your heart')).toBe(true);
            expect(simulator.shouldRevealInformation('examination', 'I will listen to your lungs')).toBe(true);
            expect(simulator.shouldRevealInformation('examination', 'How are you feeling?')).toBe(false);
        });

        it('should reveal investigation results only when tests are ordered', () => {
            expect(simulator.shouldRevealInformation('investigation', 'I need to order blood tests')).toBe(true);
            expect(simulator.shouldRevealInformation('investigation', 'Let me get an ECG')).toBe(true);
            expect(simulator.shouldRevealInformation('investigation', 'We need a chest x-ray')).toBe(true);
            expect(simulator.shouldRevealInformation('investigation', 'How are you feeling?')).toBe(false);
        });

        it('should default to revealing information for general conversation', () => {
            expect(simulator.shouldRevealInformation('general', 'How are you today?')).toBe(true);
        });
    });

    describe('formatMedicalResponse', () => {
        beforeEach(() => {
            simulator.initializePatient(mockCaseData);
        });

        it('should format examination responses with findings', () => {
            const response = 'That hurts when you press there.';
            const formatted = simulator.formatMedicalResponse(response, 'examination');

            expect(formatted).toContain(response);
            expect(formatted).toContain('[EXAMINATION FINDINGS]');
            expect(formatted).toContain('BP 150/90');
            expect(formatted).toContain('Anxious, diaphoretic');
        });

        it('should format investigation responses with test results', () => {
            const response = 'I hope the tests are normal.';
            const formatted = simulator.formatMedicalResponse(response, 'investigation');

            expect(formatted).toContain(response);
            expect(formatted).toContain('[TEST RESULTS]');
            expect(formatted).toContain('ECG: ST elevation in V2-V4');
            expect(formatted).toContain('TROPONIN: 12.5 ng/mL (elevated)');
        });

        it('should return response unchanged for history requests', () => {
            const response = 'The pain started 2 hours ago.';
            const formatted = simulator.formatMedicalResponse(response, 'history');

            expect(formatted).toBe(response);
        });

        it('should return response unchanged for general requests', () => {
            const response = 'I am feeling worried.';
            const formatted = simulator.formatMedicalResponse(response, 'general');

            expect(formatted).toBe(response);
        });
    });

    describe('respondAsPatient', () => {
        beforeEach(() => {
            simulator.initializePatient(mockCaseData);
            
            // Mock successful API response
            axios.post.mockResolvedValue({
                data: {
                    choices: [{
                        message: {
                            content: 'The pain is really severe, like someone is crushing my chest.'
                        }
                    }]
                }
            });
        });

        it('should throw error if patient not initialized', async () => {
            const uninitializedSimulator = new PatientSimulator(mockApiConfig);
            
            await expect(
                uninitializedSimulator.respondAsPatient('How are you?')
            ).rejects.toThrow('Patient not initialized');
        });

        it('should call OpenRouter API with correct parameters', async () => {
            await simulator.respondAsPatient('Tell me about your chest pain', 'history');

            expect(axios.post).toHaveBeenCalledWith(
                mockApiConfig.apiUrl,
                expect.objectContaining({
                    model: mockApiConfig.model,
                    messages: expect.arrayContaining([
                        expect.objectContaining({ role: 'system' }),
                        expect.objectContaining({ role: 'user' })
                    ]),
                    temperature: 0.7,
                    max_tokens: 300
                }),
                expect.objectContaining({
                    headers: expect.objectContaining({
                        'Authorization': `Bearer ${mockApiConfig.apiKey}`,
                        'Content-Type': 'application/json'
                    })
                })
            );
        });

        it('should store conversation history', async () => {
            const userInput = 'Tell me about your pain';
            await simulator.respondAsPatient(userInput, 'history');

            expect(simulator.conversationHistory).toHaveLength(2);
            expect(simulator.conversationHistory[0]).toEqual({
                role: 'user',
                content: userInput
            });
            expect(simulator.conversationHistory[1]).toEqual({
                role: 'assistant',
                content: expect.any(String)
            });
        });

        it('should return formatted response', async () => {
            const response = await simulator.respondAsPatient('I want to examine your chest', 'examination');

            expect(response).toContain('The pain is really severe');
            expect(response).toContain('[EXAMINATION FINDINGS]');
        });

        it('should handle API errors gracefully', async () => {
            axios.post.mockRejectedValue(new Error('API Error'));

            const response = await simulator.respondAsPatient('How are you?', 'general');

            expect(response).toBe("I'm sorry, could you please repeat that? I'm having trouble concentrating due to the pain.");
        });

        it('should limit conversation history to last 6 exchanges', async () => {
            // Add multiple exchanges
            for (let i = 0; i < 10; i++) {
                await simulator.respondAsPatient(`Question ${i}`, 'history');
            }

            // Check that API call only includes last 6 exchanges (12 messages total)
            const lastCall = axios.post.mock.calls[axios.post.mock.calls.length - 1];
            const messages = lastCall[1].messages;
            
            // Should have system prompts + max 6 conversation exchanges
            const conversationMessages = messages.filter(m => m.role === 'user' || m.role === 'assistant');
            expect(conversationMessages.length).toBeLessThanOrEqual(12); // 6 exchanges = 12 messages
        });
    });

    describe('Fallback responses', () => {
        beforeEach(() => {
            simulator.initializePatient(mockCaseData);
        });

        it('should provide appropriate fallback for history requests', () => {
            const fallback = simulator._getFallbackResponse('history');
            expect(fallback).toContain('pain');
            expect(fallback).toContain('repeat');
        });

        it('should provide appropriate fallback for examination requests', () => {
            const fallback = simulator._getFallbackResponse('examination');
            expect(fallback).toContain('examination');
        });

        it('should provide appropriate fallback for investigation requests', () => {
            const fallback = simulator._getFallbackResponse('investigation');
            expect(fallback).toContain('test results');
            expect(fallback).toContain('lab');
        });

        it('should provide general fallback for unknown request types', () => {
            const fallback = simulator._getFallbackResponse('unknown');
            expect(fallback).toContain('repeat');
            expect(fallback).toContain('pain');
        });
    });

    describe('Private helper methods', () => {
        beforeEach(() => {
            simulator.initializePatient(mockCaseData);
        });

        it('should build appropriate system prompts for different request types', () => {
            const historyPrompt = simulator._buildSystemPrompt('history', true);
            expect(historyPrompt).toContain('medical history');
            expect(historyPrompt).toContain('detailed, honest answers');

            const examPrompt = simulator._buildSystemPrompt('examination', true);
            expect(examPrompt).toContain('physical examination');
            expect(examPrompt).toContain('examination findings');

            const investigationPrompt = simulator._buildSystemPrompt('investigation', true);
            expect(investigationPrompt).toContain('tests');
            expect(investigationPrompt).toContain('test results');
        });

        it('should build contextual prompts correctly', () => {
            const userInput = 'Tell me about your chest pain';
            const prompt = simulator._buildContextualPrompt(userInput, 'history');

            expect(prompt).toContain(userInput);
            expect(prompt).toContain('Student says:');
            expect(prompt).toContain('Respond as the patient');
        });

        it('should extract examination findings from case data', () => {
            const findings = simulator._getExaminationFindings();

            expect(findings).toContain('[EXAMINATION FINDINGS]');
            expect(findings).toContain('BP 150/90');
            expect(findings).toContain('Anxious, diaphoretic');
            expect(findings).toContain('Regular rhythm');
        });

        it('should extract investigation results from case data', () => {
            const results = simulator._getInvestigationResults();

            expect(results).toContain('[TEST RESULTS]');
            expect(results).toContain('ECG: ST elevation in V2-V4');
            expect(results).toContain('TROPONIN: 12.5 ng/mL (elevated)');
            expect(results).toContain('chestXray: Normal heart size');
        });
    });
});