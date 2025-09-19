import React, { useState, useEffect } from 'react';
import { Button } from '@vibe-kanban/ui-kit';

export default function CasePrimer({ caseId, userLevel = 'intermediate', mode = 'quick' }) {
  const [primer, setPrimer] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [expandedSections, setExpandedSections] = useState({});

  useEffect(() => {
    loadPrimer();
  }, [caseId, userLevel, mode]);

  const loadPrimer = async () => {
    setLoading(true);
    setError('');

    try {
      const endpoint = mode === 'quick'
        ? route('case-primer.quick', caseId)
        : route('case-primer.show', caseId);

      const params = mode === 'full' ? { user_level: userLevel } : {};

      const response = await fetch(endpoint + (Object.keys(params).length ? '?' + new URLSearchParams(params) : ''), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        }
      });

      const data = await response.json();

      if (data.success) {
        setPrimer(data);
      } else {
        setError('Failed to load case primer');
      }
    } catch (err) {
      console.error('Failed to load primer:', err);
      setError('Network error occurred');
    } finally {
      setLoading(false);
    }
  };

  const toggleSection = (section) => {
    setExpandedSections(prev => ({
      ...prev,
      [section]: !prev[section]
    }));
  };

  const getComplexityColor = (complexity) => {
    switch(complexity) {
      case 'beginner': return 'text-green-600 bg-green-100';
      case 'intermediate': return 'text-blue-600 bg-blue-100';
      case 'advanced': return 'text-orange-600 bg-orange-100';
      case 'expert': return 'text-red-600 bg-red-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  if (loading) {
    return (
      <div className="clean-card p-6 animate-pulse">
        <div className="h-4 bg-muted rounded mb-4"></div>
        <div className="space-y-2">
          <div className="h-3 bg-muted rounded"></div>
          <div className="h-3 bg-muted rounded w-3/4"></div>
          <div className="h-3 bg-muted rounded w-1/2"></div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="clean-card p-4 bg-gradient-to-br from-red-500/10 to-red-600/5 border-red-500/20">
        <div className="text-red-600 text-sm">{error}</div>
        <Button
          variant="outline"
          size="sm"
          onClick={loadPrimer}
          className="mt-2"
        >
          Retry
        </Button>
      </div>
    );
  }

  if (!primer) {
    return null;
  }

  // Quick mode - just show key points
  if (mode === 'quick' && primer.quick_primer) {
    return (
      <div className="clean-card p-6 bg-gradient-to-br from-emerald-500/10 to-cyan-500/10">
        <div className="flex items-center gap-2 mb-4">
          <span className="text-lg">🎯</span>
          <h3 className="font-medium text-foreground">Case Primer</h3>
        </div>
        <div className="space-y-3">
          {primer.quick_primer.map((point, index) => (
            <div key={index} className="flex items-start gap-3">
              <span className="flex-shrink-0 w-6 h-6 bg-emerald-500 text-white text-xs rounded-full flex items-center justify-center">
                {index + 1}
              </span>
              <p className="text-sm text-muted-foreground">{point}</p>
            </div>
          ))}
        </div>
      </div>
    );
  }

  // Full mode - comprehensive primer
  const primerData = primer.primer;
  if (!primerData) return null;

  return (
    <div className="space-y-4">
      {/* Header with complexity */}
      <div className="clean-card p-4">
        <div className="flex items-center justify-between mb-2">
          <h2 className="font-medium text-foreground">Case Primer</h2>
          <span className={`px-2 py-1 rounded text-xs font-medium ${getComplexityColor(primerData.complexity_rating)}`}>
            {(primerData.complexity_rating || 'intermediate').toUpperCase()}
          </span>
        </div>
        <div className="text-sm text-muted-foreground">
          {primer.case?.title} • {primer.case?.chief_complaint}
        </div>
        {primerData.cached && (
          <div className="text-xs text-muted-foreground mt-1">
            💾 Cached primer • Generated for {userLevel} level
          </div>
        )}
      </div>

      {/* Clinical Overview */}
      {primerData.clinical_overview && (
        <div className="clean-card">
          <button
            onClick={() => toggleSection('overview')}
            className="w-full p-4 text-left flex items-center justify-between hover:bg-muted/50 transition-colors"
          >
            <div className="flex items-center gap-2">
              <span className="text-lg">🔍</span>
              <span className="font-medium text-foreground">Clinical Overview</span>
            </div>
            <span className="text-muted-foreground">
              {expandedSections.overview ? '▼' : '▶'}
            </span>
          </button>

          {expandedSections.overview && (
            <div className="px-4 pb-4 space-y-4">
              {primerData.clinical_overview.likely_diagnoses && (
                <div>
                  <h4 className="font-medium text-foreground text-sm mb-2">Likely Diagnoses</h4>
                  <ul className="space-y-1">
                    {primerData.clinical_overview.likely_diagnoses.map((diagnosis, index) => (
                      <li key={index} className="text-sm text-muted-foreground flex items-start gap-2">
                        <span className="text-emerald-500">•</span>
                        {diagnosis}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {primerData.clinical_overview.red_flags && (
                <div>
                  <h4 className="font-medium text-foreground text-sm mb-2">🚨 Red Flags</h4>
                  <ul className="space-y-1">
                    {primerData.clinical_overview.red_flags.map((flag, index) => (
                      <li key={index} className="text-sm text-red-600 flex items-start gap-2">
                        <span className="text-red-500">⚠</span>
                        {flag}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {primerData.clinical_overview.key_history_points && (
                <div>
                  <h4 className="font-medium text-foreground text-sm mb-2">Key History Points</h4>
                  <ul className="space-y-1">
                    {primerData.clinical_overview.key_history_points.map((point, index) => (
                      <li key={index} className="text-sm text-muted-foreground flex items-start gap-2">
                        <span className="text-blue-500">?</span>
                        {point}
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          )}
        </div>
      )}

      {/* Investigation Strategy */}
      {primerData.investigation_strategy && (
        <div className="clean-card">
          <button
            onClick={() => toggleSection('investigations')}
            className="w-full p-4 text-left flex items-center justify-between hover:bg-muted/50 transition-colors"
          >
            <div className="flex items-center gap-2">
              <span className="text-lg">🧪</span>
              <span className="font-medium text-foreground">Investigation Strategy</span>
            </div>
            <span className="text-muted-foreground">
              {expandedSections.investigations ? '▼' : '▶'}
            </span>
          </button>

          {expandedSections.investigations && (
            <div className="px-4 pb-4 space-y-4">
              {primerData.investigation_strategy.first_line_tests && (
                <div>
                  <h4 className="font-medium text-foreground text-sm mb-2">First-Line Tests</h4>
                  <ul className="space-y-1">
                    {primerData.investigation_strategy.first_line_tests.map((test, index) => (
                      <li key={index} className="text-sm text-muted-foreground flex items-start gap-2">
                        <span className="text-green-500">1</span>
                        {test}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {primerData.investigation_strategy.physical_exam_priorities && (
                <div>
                  <h4 className="font-medium text-foreground text-sm mb-2">Physical Exam Priorities</h4>
                  <ul className="space-y-1">
                    {primerData.investigation_strategy.physical_exam_priorities.map((exam, index) => (
                      <li key={index} className="text-sm text-muted-foreground flex items-start gap-2">
                        <span className="text-purple-500">👨‍⚕️</span>
                        {exam}
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          )}
        </div>
      )}

      {/* Common Pitfalls */}
      {primerData.common_pitfalls && primerData.common_pitfalls.length > 0 && (
        <div className="clean-card">
          <button
            onClick={() => toggleSection('pitfalls')}
            className="w-full p-4 text-left flex items-center justify-between hover:bg-muted/50 transition-colors"
          >
            <div className="flex items-center gap-2">
              <span className="text-lg">⚠️</span>
              <span className="font-medium text-foreground">Common Pitfalls</span>
            </div>
            <span className="text-muted-foreground">
              {expandedSections.pitfalls ? '▼' : '▶'}
            </span>
          </button>

          {expandedSections.pitfalls && (
            <div className="px-4 pb-4 space-y-3">
              {primerData.common_pitfalls.map((pitfall, index) => (
                <div key={index} className="border-l-4 border-orange-500 pl-3">
                  <div className="text-sm font-medium text-orange-700">
                    {pitfall.pitfall}
                  </div>
                  <div className="text-sm text-muted-foreground mt-1">
                    💡 {pitfall.avoidance_tip}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Success Indicators */}
      {primerData.success_indicators && (
        <div className="clean-card p-4 bg-gradient-to-br from-emerald-500/10 to-cyan-500/10">
          <div className="flex items-center gap-2 mb-3">
            <span className="text-lg">✅</span>
            <h4 className="font-medium text-foreground">Success Indicators</h4>
          </div>
          <ul className="space-y-1">
            {primerData.success_indicators.map((indicator, index) => (
              <li key={index} className="text-sm text-muted-foreground flex items-start gap-2">
                <span className="text-emerald-500">✓</span>
                {indicator}
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}