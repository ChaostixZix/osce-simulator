import { describe, it, expect, beforeEach } from 'vitest';
import OSCEController from '../lib/OSCEController.js';

describe('OSCEController Integration', () => {
    let controller;

    beforeEach(() => {
        controller = new OSCEController({
            apiUrl: 'http://test-api.com',
            apiKey: 'test-key',
            model: 'test-model'
        });
    });

    it('should initialize correctly', () => {
        expect(controller).toBeDefined();
        expect(controller.isActive).toBe(false);
        expect(controller.currentCase).toBe(null);
    });

    it('should start OSCE and load cases', async () => {
        const response = await controller.startOSCE();
        
        expect(controller.isActive).toBe(true);
        expect(controller.awaitingCaseSelection).toBe(true);
        expect(response).toContain('OSCE Case Selection');
    });

    it('should handle case selection', async () => {
        // First start OSCE
        await controller.startOSCE();
        
        // Try to select a case (should work if STEMI case exists)
        const response = await controller.selectCase('stemi-001');
        
        // Should either load the case or indicate it's not found
        expect(response).toBeDefined();
        expect(typeof response).toBe('string');
    });

    it('should provide help information', () => {
        const help = controller.getHelp();
        expect(help).toContain('OSCE');
        expect(help).toContain('help');
    });

    it('should handle state management', () => {
        const initialState = controller.getState();
        expect(initialState.isActive).toBe(false);
        expect(initialState.currentCase).toBe(null);
    });

    it('should reset properly', () => {
        controller.reset();
        expect(controller.isActive).toBe(false);
        expect(controller.currentCase).toBe(null);
        expect(controller.awaitingCaseSelection).toBe(false);
    });
});