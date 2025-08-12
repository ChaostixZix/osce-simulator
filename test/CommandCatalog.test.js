import { describe, it, expect } from 'vitest';
import { CHAT_COMMANDS, OSCE_COMMANDS } from '../lib/CommandCatalog.js';

describe('CommandCatalog', () => {
    it('Chat catalog matches PRD exactly', () => {
        const expected = [
            'help',
            'exit',
            'system status',
            'health check',
            'tutorial',
            'start osce',
            'session stats'
        ];
        // Ensure exact contents and no extras (order-insensitive)
        expect(new Set(CHAT_COMMANDS)).toEqual(new Set(expected));
        expect(CHAT_COMMANDS.length).toBe(expected.length);
    });

    it('OSCE catalog matches PRD exactly', () => {
        const expected = [
            'score', 'progress', 'status', 'help', '?',
            'case info', 'end case', 'exit osce', 'new case', 'restart', 'list', 'cases'
        ];
        expect(new Set(OSCE_COMMANDS)).toEqual(new Set(expected));
        expect(OSCE_COMMANDS.length).toBe(expected.length);
    });
});


