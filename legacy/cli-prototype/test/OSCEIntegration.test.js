import { describe, it, expect, beforeEach } from 'vitest';
import OSCEController from '../lib/OSCEController.js';

describe('OSCE Integration Workflow', () => {
    let controller;

    beforeEach(() => {
        controller = new OSCEController({
            apiUrl: 'http://test-api.com',
            apiKey: 'test-key',
            model: 'test-model'
        });
    });

    it('should complete full OSCE workflow', async () => {
        // Step 1: Start OSCE
        const startResponse = await controller.startOSCE();
        expect(startResponse).toContain('OSCE Case Selection');
        expect(controller.isActive).toBe(true);
        expect(controller.awaitingCaseSelection).toBe(true);

        // Step 2: Select case
        const selectResponse = await controller.selectCase('stemi-001');
        expect(selectResponse).toContain('Case Started');
        expect(controller.currentCase).toBeDefined();
        expect(controller.currentCase.id).toBe('stemi-001');
        expect(controller.awaitingCaseSelection).toBe(false);

        // Step 3: Get progress
        const progressResponse = controller.getCurrentProgress();
        expect(progressResponse).toContain('Progress Report');
        expect(progressResponse).toContain('Overall Progress');

        // Step 4: Get help
        const helpResponse = controller.getHelp();
        expect(helpResponse).toContain('Active Case Help');

        // Step 5: Check state
        const state = controller.getState();
        expect(state.isActive).toBe(true);
        expect(state.currentCase).toBe('stemi-001');
        expect(state.awaitingCaseSelection).toBe(false);

        // Step 6: End case
        const endResponse = await controller.endCase();
        expect(endResponse).toContain('CASE COMPLETED');
        expect(endResponse).toContain('FINAL SCORE');
        expect(controller.showingResults).toBe(true);
    });

    it('should handle case selection errors gracefully', async () => {
        await controller.startOSCE();
        
        // Try to select non-existent case
        const response = await controller.selectCase('non-existent-case');
        expect(response).toContain('not found');
    });

    it('should handle input processing without active case', async () => {
        const response = await controller.processUserInput('test input');
        expect(response).toContain('not active');
    });

    it('should handle special commands', async () => {
        await controller.startOSCE();
        await controller.selectCase('stemi-001');
        
        // Test score command
        const scoreResponse = await controller.processUserInput('score');
        expect(scoreResponse).toContain('Progress Report');
        
        // Test help command
        const helpResponse = await controller.processUserInput('help');
        expect(helpResponse).toContain('Active Case Help');
        
        // Test case info command
        const infoResponse = await controller.processUserInput('case info');
        expect(infoResponse).toContain('Case Information');
    });
});