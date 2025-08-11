import axios from 'axios';
import ErrorHandler from './ErrorHandler.js';
import Logger from './Logger.js';
import RetryManager from './RetryManager.js';

/**
 * PatientSimulator class handles AI-powered patient simulation for OSCE training
 * Generates contextual patient responses based on case data and user interactions
 */
class PatientSimulator {
    constructor(apiConfig = {}) {
        this.apiUrl = apiConfig.apiUrl || process.env.API_URL;
        this.apiKey = apiConfig.apiKey || process.env.API_KEY;
        this.model = apiConfig.model || process.env.API_MODEL;
        
        this.caseData = null;
        this.patientPersona = null;
        this.conversationHistory = [];
        
        // Error handling and logging
        this.errorHandler = new ErrorHandler();
        this.logger = Logger;
        this.retryManager = RetryManager;
        
        // Fallback responses for different scenarios
        this.fallbackResponses = this._initializeFallbackResponses();
        
        // API call statistics
        this.apiStats = {
            totalCalls: 0,
            successfulCalls: 0,
            failedCalls: 0,
            averageResponseTime: 0
        };
    }

    /**
     * Initialize patient simulation with case data
     * @param {Object} caseData - Complete case data from JSON
     */
    initializePatient(caseData) {
        this.caseData = caseData;
        this.patientPersona = this._buildPatientPersona(caseData);
        this.conversationHistory = [];
    }

    /**
     * Generate patient response based on user input and case context
     * @param {string} userInput - Student's question or request
     * @param {string} requestType - Type of request (history, examination, investigation)
     * @returns {Promise<string>} Patient's response
     */
    async respondAsPatient(userInput, requestType = 'general') {
        if (!this.caseData) {
            const error = new Error('Patient not initialized. Call initializePatient() first.');
            const errorResponse = this.errorHandler.handleError(error, 'patient_simulator');
            this.logger.error('Patient simulator not initialized', { userInput, requestType }, 'patient_simulator');
            return errorResponse.userMessage;
        }

        const startTime = Date.now();
        this.apiStats.totalCalls++;

        try {
            const shouldReveal = this.shouldRevealInformation(requestType, userInput);
            const systemPrompt = this._buildSystemPrompt(requestType, shouldReveal);
            const contextualPrompt = this._buildContextualPrompt(userInput, requestType);

            const messages = [
                { role: "system", content: systemPrompt },
                { role: "system", content: this.patientPersona },
                ...this.conversationHistory.slice(-6), // Keep last 6 exchanges for context
                { role: "user", content: contextualPrompt }
            ];

            // Use retry manager for API call
            const response = await this.retryManager.executeAPICall(
                () => this._callOpenRouterAPI(messages),
                {
                    context: 'patient_response',
                    maxRetries: 2,
                    onRetry: (error, attempt, delay) => {
                        this.logger.warn(
                            `Retrying patient response generation (attempt ${attempt})`,
                            { error: error.message, delay, userInput: userInput.substring(0, 50) },
                            'patient_simulator'
                        );
                    }
                }
            );

            const formattedResponse = this.formatMedicalResponse(response, requestType);
            
            // Store conversation for context
            this.conversationHistory.push(
                { role: "user", content: userInput },
                { role: "assistant", content: formattedResponse }
            );

            // Update statistics
            const duration = Date.now() - startTime;
            this.apiStats.successfulCalls++;
            this._updateAverageResponseTime(duration);

            this.logger.info(
                'Patient response generated successfully',
                { requestType, duration, responseLength: formattedResponse.length },
                'patient_simulator'
            );

            return formattedResponse;

        } catch (error) {
            const duration = Date.now() - startTime;
            this.apiStats.failedCalls++;

            // Handle the error with comprehensive error handling
            const errorResponse = this.errorHandler.handleAPIError(error, 'patient_response_generation');
            
            this.logger.error(
                'Failed to generate patient response',
                { 
                    error: error.message,
                    requestType,
                    duration,
                    userInput: userInput.substring(0, 100),
                    apiStats: this.apiStats
                },
                'patient_simulator'
            );

            // Return contextual fallback response
            return this._getFallbackResponse(requestType, userInput, error);
        }
    }

    /**
     * Determine if information should be revealed based on request type
     * @param {string} requestType - Type of medical request
     * @param {string} userInput - Student's input
     * @returns {boolean} Whether information should be disclosed
     */
    shouldRevealInformation(requestType, userInput) {
        const input = userInput.toLowerCase();
        
        // Always reveal basic history and symptoms when asked
        if (requestType === 'history' || input.includes('tell me') || input.includes('describe')) {
            return true;
        }

        // Physical examination findings only when specifically requested
        if (requestType === 'examination') {
            const examKeywords = ['examine', 'check', 'listen', 'palpate', 'auscult'];
            const feelKeywords = ['feel your', 'feel the', 'feel for']; // More specific "feel" patterns
            
            return examKeywords.some(keyword => input.includes(keyword)) ||
                   feelKeywords.some(keyword => input.includes(keyword));
        }

        // Laboratory results only when specifically ordered
        if (requestType === 'investigation') {
            const labKeywords = ['test', 'lab', 'blood', 'urine', 'ecg', 'x-ray', 'scan'];
            return labKeywords.some(keyword => input.includes(keyword));
        }

        // Default to revealing information for general conversation
        return true;
    }

    /**
     * Format medical response based on request type
     * @param {string} response - Raw AI response
     * @param {string} requestType - Type of medical request
     * @returns {string} Formatted response
     */
    formatMedicalResponse(response, requestType) {
        switch (requestType) {
            case 'examination':
                return this._formatExaminationResponse(response);
            case 'investigation':
                return this._formatInvestigationResponse(response);
            case 'history':
                return this._formatHistoryResponse(response);
            default:
                return response;
        }
    }

    /**
     * Build patient persona from case data
     * @param {Object} caseData - Case data
     * @returns {string} Patient persona description
     * @private
     */
    _buildPatientPersona(caseData) {
        const { patientInfo, presentingSymptoms, medicalHistory } = caseData;
        
        return `You are ${patientInfo.name}, a ${patientInfo.age}-year-old ${patientInfo.gender} ${patientInfo.occupation || 'patient'}.

CURRENT PRESENTATION:
- Chief complaint: ${caseData.chiefComplaint}
- Primary symptom: ${presentingSymptoms.primary}
- Associated symptoms: ${presentingSymptoms.associated.join(', ')}
- Onset: ${presentingSymptoms.onset}
- Character: ${presentingSymptoms.character}
- Radiation: ${presentingSymptoms.radiation}
- Severity: ${presentingSymptoms.severity}

MEDICAL HISTORY:
- Past medical history: ${medicalHistory.pastMedical.join(', ')}
- Current medications: ${medicalHistory.medications.join(', ')}
- Allergies: ${medicalHistory.allergies.join(', ')}
- Social history: Smoking - ${medicalHistory.socialHistory.smoking}, Alcohol - ${medicalHistory.socialHistory.alcohol}
- Family history: ${medicalHistory.socialHistory.familyHistory}

You should respond as this patient would, showing appropriate concern and symptoms. Be realistic about your pain level and distress. Answer questions honestly but only provide information that a patient would naturally know or observe about themselves.`;
    }

    /**
     * Build system prompt based on request type
     * @param {string} requestType - Type of request
     * @param {boolean} shouldReveal - Whether to reveal information
     * @returns {string} System prompt
     * @private
     */
    _buildSystemPrompt(requestType, shouldReveal) {
        const basePrompt = `You are simulating a patient in a medical training scenario. Stay in character as the patient described. Be realistic and respond as a real patient would.

IMPORTANT GUIDELINES:
- Only provide information that a patient would naturally know about themselves
- Show appropriate emotional responses (anxiety, pain, concern)
- Don't use medical terminology unless the patient would know it
- Be consistent with the case presentation
- If asked about test results or examination findings, say you don't know unless told by medical staff`;

        if (!shouldReveal) {
            return basePrompt + `\n\nFor this interaction, be more reserved with information. Only provide basic responses unless specifically pressed for details.`;
        }

        switch (requestType) {
            case 'examination':
                return basePrompt + `\n\nThe student is performing a physical examination. You cannot see or know the examination findings - only describe how you feel during the examination (comfortable, uncomfortable, painful, etc.).`;
            
            case 'investigation':
                return basePrompt + `\n\nThe student is ordering tests or asking about results. As a patient, you don't know test results unless a healthcare provider has told you. Respond appropriately.`;
            
            case 'history':
                return basePrompt + `\n\nThe student is taking your medical history. Provide detailed, honest answers about your symptoms, medical history, and how you're feeling.`;
            
            default:
                return basePrompt;
        }
    }

    /**
     * Build contextual prompt for specific user input
     * @param {string} userInput - Student's input
     * @param {string} requestType - Type of request
     * @returns {string} Contextual prompt
     * @private
     */
    _buildContextualPrompt(userInput, requestType) {
        return `Student says: "${userInput}"

Respond as the patient. Keep your response natural and conversational, showing appropriate emotion for your condition. Limit your response to 2-3 sentences unless more detail is specifically requested.`;
    }

    /**
     * Format examination response
     * @param {string} response - Raw response
     * @returns {string} Formatted response
     * @private
     */
    _formatExaminationResponse(response) {
        // For examination, we need to provide the actual findings from case data
        // This is a simplified version - in practice, you'd map specific examination requests to findings
        return response + this._getExaminationFindings();
    }

    /**
     * Format investigation response
     * @param {string} response - Raw response
     * @returns {string} Formatted response
     * @private
     */
    _formatInvestigationResponse(response) {
        // For investigations, provide actual test results when appropriate
        return response + this._getInvestigationResults();
    }

    /**
     * Format history response
     * @param {string} response - Raw response
     * @returns {string} Formatted response
     * @private
     */
    _formatHistoryResponse(response) {
        return response; // History responses are typically just patient responses
    }

    /**
     * Get examination findings from case data
     * @returns {string} Examination findings
     * @private
     */
    _getExaminationFindings() {
        if (!this.caseData.physicalExamination) return '';
        
        const exam = this.caseData.physicalExamination;
        return `\n\n[EXAMINATION FINDINGS]
Vital Signs: BP ${exam.vitalSigns.bp}, HR ${exam.vitalSigns.hr}, RR ${exam.vitalSigns.rr}, Temp ${exam.vitalSigns.temp}, O2 Sat ${exam.vitalSigns.o2sat}
General: ${exam.general}
Cardiovascular: ${exam.cardiovascular}
Respiratory: ${exam.respiratory}`;
    }

    /**
     * Get investigation results from case data
     * @returns {string} Investigation results
     * @private
     */
    _getInvestigationResults() {
        if (!this.caseData.investigations) return '';
        
        const inv = this.caseData.investigations;
        let results = '\n\n[TEST RESULTS]';
        
        if (inv.ecg) {
            results += `\nECG: ${inv.ecg.findings} - ${inv.ecg.interpretation}`;
        }
        
        if (inv.labs) {
            results += '\nLaboratory Results:';
            Object.entries(inv.labs).forEach(([test, result]) => {
                results += `\n- ${test.toUpperCase()}: ${result}`;
            });
        }
        
        if (inv.imaging) {
            results += '\nImaging:';
            Object.entries(inv.imaging).forEach(([study, result]) => {
                results += `\n- ${study}: ${result}`;
            });
        }
        
        return results;
    }

    /**
     * Get fallback response for API failures
     * @param {string} requestType - Type of request
     * @param {string} userInput - Original user input
     * @param {Error} error - Error that occurred
     * @returns {string} Fallback response
     * @private
     */
    _getFallbackResponse(requestType, userInput = '', error = null) {
        // Try to provide contextual fallback based on user input
        const contextualResponse = this._getContextualFallback(userInput, requestType);
        if (contextualResponse) {
            return contextualResponse;
        }

        // Use predefined fallbacks
        const fallbacks = this.fallbackResponses[requestType] || this.fallbackResponses.general;
        
        // Select random fallback to avoid repetition
        const selectedFallback = fallbacks[Math.floor(Math.random() * fallbacks.length)];
        
        // Add error context if appropriate
        if (error && error.code === 'ETIMEDOUT') {
            return selectedFallback + " (I'm having trouble thinking clearly right now, please be patient with me.)";
        }
        
        return selectedFallback;
    }

    /**
     * Get contextual fallback response based on user input
     * @param {string} userInput - User's input
     * @param {string} requestType - Request type
     * @returns {string|null} Contextual response or null
     * @private
     */
    _getContextualFallback(userInput, requestType) {
        if (!this.caseData) return null;

        const input = userInput.toLowerCase();
        
        // Pain-related questions
        if (input.includes('pain') || input.includes('hurt')) {
            return `The pain is really severe, about ${this.caseData.presentingSymptoms?.severity || '8/10'}. It's ${this.caseData.presentingSymptoms?.character || 'crushing'} and started ${this.caseData.presentingSymptoms?.onset || 'suddenly'}.`;
        }
        
        // Symptom questions
        if (input.includes('symptom') || input.includes('feel')) {
            const symptoms = this.caseData.presentingSymptoms?.associated || [];
            if (symptoms.length > 0) {
                return `Besides the main problem, I'm also experiencing ${symptoms.slice(0, 2).join(' and ')}.`;
            }
        }
        
        // History questions
        if (input.includes('history') || input.includes('medical') || input.includes('condition')) {
            const history = this.caseData.medicalHistory?.pastMedical || [];
            if (history.length > 0) {
                return `I have a history of ${history.slice(0, 2).join(' and ')}.`;
            }
        }
        
        // Medication questions
        if (input.includes('medication') || input.includes('medicine') || input.includes('drug')) {
            const medications = this.caseData.medicalHistory?.medications || [];
            if (medications.length > 0) {
                return `I'm currently taking ${medications.slice(0, 2).join(' and ')}.`;
            }
        }
        
        return null;
    }

    /**
     * Initialize fallback responses for different scenarios
     * @returns {Object} Fallback responses by type
     * @private
     */
    _initializeFallbackResponses() {
        return {
            history: [
                "I'm sorry, I'm in quite a bit of pain right now. Could you repeat your question?",
                "The pain is making it hard to concentrate. What did you ask?",
                "I'm feeling quite unwell. Could you ask that again?",
                "Sorry, I'm having trouble focusing because of the pain. What was your question?"
            ],
            examination: [
                "Please go ahead with the examination.",
                "I'm ready for you to examine me.",
                "You can proceed with checking me over.",
                "Please do whatever examination you need to do."
            ],
            investigation: [
                "I'm not sure about those test results. You'll need to check with the lab.",
                "I don't know about any test results yet.",
                "You'll have to ask the medical staff about those tests.",
                "I haven't been told about any test results."
            ],
            general: [
                "I'm sorry, could you please repeat that? I'm having trouble concentrating due to the pain.",
                "I'm not feeling well right now. Could you say that again?",
                "The pain is making it hard for me to focus. What did you say?",
                "I'm sorry, I'm quite distressed. Could you repeat your question?"
            ]
        };
    }

    /**
     * Update average response time statistics
     * @param {number} duration - Response duration in ms
     * @private
     */
    _updateAverageResponseTime(duration) {
        const totalSuccessful = this.apiStats.successfulCalls;
        const currentAverage = this.apiStats.averageResponseTime;
        
        // Calculate new average
        this.apiStats.averageResponseTime = 
            ((currentAverage * (totalSuccessful - 1)) + duration) / totalSuccessful;
    }

    /**
     * Get API statistics
     * @returns {Object} API call statistics
     */
    getAPIStatistics() {
        return {
            ...this.apiStats,
            successRate: this.apiStats.totalCalls > 0 ? 
                (this.apiStats.successfulCalls / this.apiStats.totalCalls) * 100 : 0
        };
    }

    /**
     * Reset API statistics
     */
    resetAPIStatistics() {
        this.apiStats = {
            totalCalls: 0,
            successfulCalls: 0,
            failedCalls: 0,
            averageResponseTime: 0
        };
    }

    /**
     * Call OpenRouter API with comprehensive error handling
     * @param {Array} messages - Messages array
     * @returns {Promise<string>} API response
     * @private
     */
    async _callOpenRouterAPI(messages) {
        // Validate API configuration
        if (!this.apiUrl || !this.apiKey || !this.model) {
            throw new Error('API configuration incomplete. Please check API_URL, API_KEY, and API_MODEL environment variables.');
        }

        // Validate messages
        if (!messages || !Array.isArray(messages) || messages.length === 0) {
            throw new Error('Invalid messages array provided to API call');
        }

        const requestData = {
            model: this.model,
            messages: messages,
            temperature: 0.7, // Slightly creative but consistent
            max_tokens: 300 // Limit response length
        };

        const requestConfig = {
            headers: {
                "Authorization": `Bearer ${this.apiKey}`,
                "HTTP-Referer": "http://localhost:3000",
                "X-Title": "OSCE Medical Training App",
                "Content-Type": "application/json"
            },
            timeout: 30000 // 30 second timeout
        };

        try {
            const startTime = Date.now();
            
            this.logger.debug(
                'Making API call to OpenRouter',
                { 
                    model: this.model,
                    messageCount: messages.length,
                    maxTokens: requestData.max_tokens
                },
                'patient_simulator'
            );

            const response = await axios.post(this.apiUrl, requestData, requestConfig);
            
            const duration = Date.now() - startTime;
            
            // Validate response structure
            if (!response.data || !response.data.choices || !response.data.choices[0] || !response.data.choices[0].message) {
                throw new Error('Invalid API response structure received');
            }

            const content = response.data.choices[0].message.content;
            
            if (!content || typeof content !== 'string') {
                throw new Error('Invalid or empty content received from API');
            }

            this.logger.logAPICall(
                'POST',
                this.apiUrl,
                response.status,
                duration,
                {
                    model: this.model,
                    tokensUsed: response.data.usage?.total_tokens,
                    responseLength: content.length
                }
            );

            return content;

        } catch (error) {
            // Enhanced error handling for different types of API errors
            if (error.response) {
                // HTTP error response
                const status = error.response.status;
                const statusText = error.response.statusText;
                const errorData = error.response.data;

                this.logger.logAPICall(
                    'POST',
                    this.apiUrl,
                    status,
                    Date.now() - (error.config?.metadata?.startTime || Date.now()),
                    {
                        error: errorData?.error?.message || statusText,
                        errorType: errorData?.error?.type
                    }
                );

                // Create more specific error messages
                if (status === 401) {
                    throw new Error('API authentication failed. Please check your API key.');
                } else if (status === 403) {
                    throw new Error('API access forbidden. Please check your API permissions.');
                } else if (status === 429) {
                    throw new Error('API rate limit exceeded. Please wait before making more requests.');
                } else if (status >= 500) {
                    throw new Error(`API server error (${status}): ${statusText}. The service may be temporarily unavailable.`);
                } else {
                    throw new Error(`API request failed (${status}): ${errorData?.error?.message || statusText}`);
                }
            } else if (error.request) {
                // Network error
                if (error.code === 'ETIMEDOUT') {
                    throw new Error('API request timed out. Please check your internet connection and try again.');
                } else if (error.code === 'ECONNREFUSED') {
                    throw new Error('Unable to connect to API service. Please check your internet connection.');
                } else if (error.code === 'ENOTFOUND') {
                    throw new Error('API service not found. Please check the API URL configuration.');
                } else {
                    throw new Error(`Network error: ${error.message}`);
                }
            } else {
                // Other errors (validation, etc.)
                throw new Error(`API call failed: ${error.message}`);
            }
        }
    }
}

export default PatientSimulator;