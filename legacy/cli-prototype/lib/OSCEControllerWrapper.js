/**
 * ES6 wrapper for the OSCEController module
 * This provides a simplified interface for the main app.js
 */

import OSCEController from './OSCEController.js';

class OSCEControllerWrapper {
    constructor(apiConfig) {
        this.apiConfig = apiConfig;
        this.controller = null;
        this.initialized = false;
    }

    async initialize() {
        if (!this.initialized) {
            this.controller = new OSCEController(this.apiConfig);
            this.initialized = true;
        }
        return this.controller;
    }

    async startOSCE() {
        const controller = await this.initialize();
        return controller.startOSCE();
    }

    async selectCase(caseId) {
        const controller = await this.initialize();
        return controller.selectCase(caseId);
    }

    async processUserInput(input) {
        const controller = await this.initialize();
        return controller.processUserInput(input);
    }

    async endCase() {
        const controller = await this.initialize();
        return controller.endCase();
    }

    async getCurrentProgress() {
        const controller = await this.initialize();
        return controller.getCurrentProgress();
    }

    async getHelp() {
        const controller = await this.initialize();
        return controller.getHelp();
    }

    async reset() {
        const controller = await this.initialize();
        return controller.reset();
    }

    async getState() {
        const controller = await this.initialize();
        return controller.getState();
    }

    async getCaseInfo() {
        const controller = await this.initialize();
        return controller.getCaseInfo();
    }

    async listCases() {
        const controller = await this.initialize();
        return controller.listCases();
    }

    async getSystemStatus() {
        const controller = await this.initialize();
        return controller.getSystemStatus();
    }

    async performHealthCheck() {
        const controller = await this.initialize();
        return controller.performHealthCheck();
    }

    async getErrorRecoverySuggestions() {
        const controller = await this.initialize();
        return controller.getErrorRecoverySuggestions();
    }
}

export default OSCEControllerWrapper;