import { describe, it, expect } from 'vitest';
import ScoringEngine from '../lib/ScoringEngine.js';
import PerformanceTracker from '../lib/PerformanceTracker.js';
import fs from 'fs';
import path from 'path';

describe('ScoringEngine efficiency bonus', () => {
    it('does not award efficiency bonus when no checklist items are completed', () => {
        const casePath = path.join(process.cwd(), 'cases', 'stemi-001.json');
        const caseData = JSON.parse(fs.readFileSync(casePath, 'utf8'));
        const tracker = new PerformanceTracker();
        tracker.initializeChecklist(caseData);
        const performanceData = tracker.getPerformanceData();
        const engine = new ScoringEngine();
        const result = engine.calculateScore(performanceData, caseData.checklist);
        expect(result.efficiencyBonus).toBe(0);
        expect(result.totalScore).toBe(0);
    });
});
