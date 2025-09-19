import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@vibe-kanban/ui-kit';

export default function ReplayStudio({ session }) {
  const [replayData, setReplayData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState('timeline');
  const [selectedScenario, setSelectedScenario] = useState(null);
  const [playingVoiceover, setPlayingVoiceover] = useState(null);
  const [timelineFilter, setTimelineFilter] = useState('all');

  const breadcrumbs = [
    { title: 'OSCE', href: route('osce') },
    { title: `Session #${session?.id}`, href: route('osce.results.show', session?.id) },
    { title: 'Replay Studio', href: '#' }
  ];

  useEffect(() => {
    loadReplayData();
  }, [session?.id]);

  const loadReplayData = async () => {
    setLoading(true);
    setError('');

    try {
      const response = await fetch(route('replay.generate', session.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        }
      });

      const data = await response.json();

      if (data.success) {
        setReplayData(data.replay);
      } else {
        setError(data.error || 'Failed to generate replay');
      }
    } catch (err) {
      console.error('Failed to load replay:', err);
      setError('Network error occurred');
    } finally {
      setLoading(false);
    }
  };

  const playVoiceover = (scriptKey) => {
    if (!replayData?.voiceover_scripts?.[scriptKey]) return;

    // Simple text-to-speech implementation
    if ('speechSynthesis' in window) {
      const utterance = new SpeechSynthesisUtterance(replayData.voiceover_scripts[scriptKey]);
      utterance.rate = 0.9;
      utterance.pitch = 1;
      utterance.volume = 0.8;

      utterance.onstart = () => setPlayingVoiceover(scriptKey);
      utterance.onend = () => setPlayingVoiceover(null);
      utterance.onerror = () => setPlayingVoiceover(null);

      speechSynthesis.speak(utterance);
    }
  };

  const stopVoiceover = () => {
    if ('speechSynthesis' in window) {
      speechSynthesis.cancel();
      setPlayingVoiceover(null);
    }
  };

  const getEventIcon = (type) => {
    switch(type) {
      case 'chat': return '💬';
      case 'test_order': return '🧪';
      case 'examination': return '👨‍⚕️';
      default: return '📋';
    }
  };

  const getSignificanceColor = (significance) => {
    switch(significance) {
      case 'high': return 'border-red-500 bg-red-50 text-red-700';
      case 'medium': return 'border-orange-500 bg-orange-50 text-orange-700';
      case 'low': return 'border-blue-500 bg-blue-50 text-blue-700';
      default: return 'border-gray-500 bg-gray-50 text-gray-700';
    }
  };

  const getDifficultyColor = (change) => {
    switch(change) {
      case 'easier': return 'text-green-600 bg-green-100';
      case 'harder': return 'text-red-600 bg-red-100';
      case 'similar': return 'text-blue-600 bg-blue-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const formatTimeFromStart = (minutes) => {
    const mins = Math.floor(minutes);
    const secs = Math.round((minutes - mins) * 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const filteredEvents = replayData?.timeline?.events?.filter(event => {
    if (timelineFilter === 'all') return true;
    if (timelineFilter === 'pivotal') return event.significance === 'high';
    return event.type === timelineFilter;
  }) ?? [];

  if (loading) {
    return (
      <>
        <Head title="Replay Studio" />
        <AppLayout breadcrumbs={breadcrumbs}>
          <div className="space-y-6">
            <div className="text-center">
              <div className="text-2xl font-semibold text-foreground mb-2">Generating Replay Analysis</div>
              <div className="text-muted-foreground">This may take a moment...</div>
            </div>

            <div className="grid md:grid-cols-3 gap-4">
              {Array.from({ length: 6 }).map((_, i) => (
                <div key={i} className="clean-card p-6 animate-pulse">
                  <div className="h-4 bg-muted rounded mb-4"></div>
                  <div className="space-y-2">
                    <div className="h-3 bg-muted rounded"></div>
                    <div className="h-3 bg-muted rounded w-3/4"></div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </AppLayout>
      </>
    );
  }

  if (error) {
    return (
      <>
        <Head title="Replay Studio" />
        <AppLayout breadcrumbs={breadcrumbs}>
          <div className="clean-card p-8 text-center">
            <div className="text-4xl mb-4">⚠️</div>
            <div className="text-xl font-semibold text-foreground mb-2">Replay Generation Failed</div>
            <div className="text-muted-foreground mb-4">{error}</div>
            <Button onClick={loadReplayData}>Retry</Button>
          </div>
        </AppLayout>
      </>
    );
  }

  if (!replayData) {
    return (
      <>
        <Head title="Replay Studio" />
        <AppLayout breadcrumbs={breadcrumbs}>
          <div className="clean-card p-8 text-center">
            <div className="text-4xl mb-4">🎬</div>
            <div className="text-xl font-semibold text-foreground mb-2">No Replay Available</div>
            <div className="text-muted-foreground">Unable to generate replay for this session.</div>
          </div>
        </AppLayout>
      </>
    );
  }

  return (
    <>
      <Head title="Replay Studio" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-6">
          {/* Header */}
          <div className="text-center space-y-4">
            <div className="flex items-center justify-center gap-3 mb-2">
              <div className="w-8 h-0.5 bg-gradient-to-r from-purple-400 to-pink-400"></div>
              <span className="text-xs text-purple-500 font-mono uppercase tracking-wider">Replay Studio</span>
              <div className="w-8 h-0.5 bg-gradient-to-l from-purple-400 to-pink-400"></div>
            </div>
            <h1 className="text-2xl font-semibold text-foreground">Session Replay & Analysis</h1>
            <p className="text-muted-foreground">Review your performance and explore alternative scenarios</p>

            {replayData.voiceover_scripts?.introduction && (
              <div className="clean-card p-4 bg-gradient-to-r from-purple-500/10 to-pink-500/10">
                <div className="flex items-center justify-between">
                  <p className="text-sm text-muted-foreground">{replayData.voiceover_scripts.introduction}</p>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => playingVoiceover === 'introduction' ? stopVoiceover() : playVoiceover('introduction')}
                  >
                    {playingVoiceover === 'introduction' ? '⏸️ Stop' : '🔊 Play'}
                  </Button>
                </div>
              </div>
            )}
          </div>

          {/* Session Summary */}
          {replayData.session_summary && (
            <div className="clean-card p-6">
              <h2 className="font-medium text-foreground mb-4">Session Summary</h2>
              <div className="grid md:grid-cols-3 gap-4 text-sm">
                <div>
                  <div className="text-muted-foreground">Case</div>
                  <div className="font-medium">{replayData.session_summary.case_title}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Duration</div>
                  <div className="font-medium">{replayData.session_summary.duration_minutes} minutes</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Messages</div>
                  <div className="font-medium">{replayData.session_summary.messages_exchanged}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Tests Ordered</div>
                  <div className="font-medium">{replayData.session_summary.tests_ordered}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Examinations</div>
                  <div className="font-medium">{replayData.session_summary.examinations_performed}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Total Cost</div>
                  <div className="font-medium">${replayData.session_summary.total_cost?.toFixed(2) || '0.00'}</div>
                </div>
              </div>
            </div>
          )}

          {/* Tab Navigation */}
          <div className="flex gap-2 border-b border-border">
            {[
              { key: 'timeline', label: 'Timeline', icon: '📊' },
              { key: 'alternatives', label: 'What-If Scenarios', icon: '🔀' },
              { key: 'insights', label: 'Performance Insights', icon: '💡' }
            ].map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key)}
                className={`px-4 py-2 font-medium text-sm transition-colors ${
                  activeTab === tab.key
                    ? 'text-purple-600 border-b-2 border-purple-600 bg-purple-50'
                    : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                <span className="mr-1">{tab.icon}</span>
                {tab.label}
              </button>
            ))}
          </div>

          {/* Tab Content */}
          <div className="min-h-[400px]">
            {/* Timeline Tab */}
            {activeTab === 'timeline' && (
              <div className="space-y-6">
                <div className="flex items-center justify-between">
                  <h2 className="text-lg font-medium text-foreground">Session Timeline</h2>
                  <div className="flex gap-2">
                    <select
                      value={timelineFilter}
                      onChange={(e) => setTimelineFilter(e.target.value)}
                      className="border rounded px-3 py-1 text-sm bg-background"
                    >
                      <option value="all">All Events</option>
                      <option value="pivotal">Pivotal Moments</option>
                      <option value="chat">Chat Messages</option>
                      <option value="test_order">Test Orders</option>
                      <option value="examination">Examinations</option>
                    </select>
                    {replayData.voiceover_scripts?.timeline_overview && (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => playVoiceover('timeline_overview')}
                        disabled={playingVoiceover === 'timeline_overview'}
                      >
                        🔊
                      </Button>
                    )}
                  </div>
                </div>

                {/* Phase Breakdown */}
                {replayData.timeline?.phase_breakdown && (
                  <div className="clean-card p-4">
                    <h3 className="font-medium text-foreground mb-3">Session Phase Analysis</h3>
                    <div className="flex gap-4">
                      {Object.entries(replayData.timeline.phase_breakdown).map(([phase, percentage]) => (
                        <div key={phase} className="text-center">
                          <div className="text-2xl font-semibold text-foreground">{percentage}%</div>
                          <div className="text-sm text-muted-foreground capitalize">{phase}</div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Timeline Events */}
                <div className="space-y-3">
                  {filteredEvents.map((event, index) => (
                    <div
                      key={index}
                      className={`clean-card p-4 border-l-4 ${getSignificanceColor(event.significance)}`}
                    >
                      <div className="flex items-start justify-between">
                        <div className="flex items-start gap-3">
                          <span className="text-lg">{getEventIcon(event.type)}</span>
                          <div className="flex-1">
                            <div className="flex items-center gap-2 mb-1">
                              <span className="font-medium text-foreground capitalize">
                                {event.type.replace('_', ' ')}
                              </span>
                              <span className="text-xs px-2 py-1 rounded bg-background">
                                {formatTimeFromStart(event.minutes_from_start)}
                              </span>
                            </div>
                            <p className="text-sm text-muted-foreground">{event.content}</p>
                            {event.reasoning && (
                              <p className="text-xs text-muted-foreground mt-1">
                                <strong>Reasoning:</strong> {event.reasoning}
                              </p>
                            )}
                            {event.cost && (
                              <p className="text-xs text-muted-foreground mt-1">
                                <strong>Cost:</strong> ${event.cost}
                              </p>
                            )}
                          </div>
                        </div>
                        <div className={`text-xs px-2 py-1 rounded ${getSignificanceColor(event.significance)}`}>
                          {event.significance}
                        </div>
                      </div>
                    </div>
                  ))}

                  {filteredEvents.length === 0 && (
                    <div className="clean-card p-8 text-center">
                      <div className="text-4xl mb-3">📊</div>
                      <div className="text-muted-foreground">No events match the current filter</div>
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* Alternative Scenarios Tab */}
            {activeTab === 'alternatives' && (
              <div className="space-y-6">
                <div className="flex items-center justify-between">
                  <h2 className="text-lg font-medium text-foreground">What-If Scenarios</h2>
                  {replayData.voiceover_scripts?.alternatives_intro && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => playVoiceover('alternatives_intro')}
                      disabled={playingVoiceover === 'alternatives_intro'}
                    >
                      🔊 Introduction
                    </Button>
                  )}
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                  {replayData.alternative_scenarios?.map((scenario, index) => (
                    <div
                      key={index}
                      className={`clean-card p-6 cursor-pointer transition-all hover:shadow-sm ${
                        selectedScenario === index ? 'ring-2 ring-purple-500' : ''
                      }`}
                      onClick={() => setSelectedScenario(selectedScenario === index ? null : index)}
                    >
                      <div className="flex items-start justify-between mb-3">
                        <h3 className="font-medium text-foreground">{scenario.title}</h3>
                        <span className={`text-xs px-2 py-1 rounded ${getDifficultyColor(scenario.difficulty_change)}`}>
                          {scenario.difficulty_change}
                        </span>
                      </div>

                      <p className="text-sm text-muted-foreground mb-3">{scenario.description}</p>

                      <div className="text-xs text-muted-foreground mb-2">
                        <strong>Clinical Area:</strong> {scenario.clinical_area}
                      </div>

                      {selectedScenario === index && (
                        <div className="mt-4 pt-4 border-t border-border space-y-3">
                          <div>
                            <div className="text-xs font-medium text-foreground mb-1">Decision Point:</div>
                            <div className="text-sm text-muted-foreground">{scenario.decision_point}</div>
                          </div>

                          <div>
                            <div className="text-xs font-medium text-foreground mb-1">Alternative Action:</div>
                            <div className="text-sm text-muted-foreground">{scenario.alternative_action}</div>
                          </div>

                          <div>
                            <div className="text-xs font-medium text-foreground mb-1">Likely Outcome:</div>
                            <div className="text-sm text-muted-foreground">{scenario.likely_outcome}</div>
                          </div>

                          <div className="p-3 bg-gradient-to-r from-emerald-500/10 to-cyan-500/10 rounded">
                            <div className="text-xs font-medium text-emerald-700 mb-1">💡 Learning Point:</div>
                            <div className="text-sm text-emerald-600">{scenario.learning_point}</div>
                          </div>
                        </div>
                      )}
                    </div>
                  ))}
                </div>

                {(!replayData.alternative_scenarios || replayData.alternative_scenarios.length === 0) && (
                  <div className="clean-card p-8 text-center">
                    <div className="text-4xl mb-3">🔀</div>
                    <div className="text-muted-foreground">No alternative scenarios available</div>
                  </div>
                )}
              </div>
            )}

            {/* Performance Insights Tab */}
            {activeTab === 'insights' && (
              <div className="space-y-6">
                <div className="flex items-center justify-between">
                  <h2 className="text-lg font-medium text-foreground">Performance Insights</h2>
                  {replayData.voiceover_scripts?.performance_summary && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => playVoiceover('performance_summary')}
                      disabled={playingVoiceover === 'performance_summary'}
                    >
                      🔊 Summary
                    </Button>
                  )}
                </div>

                {replayData.performance_insights && (
                  <div className="grid md:grid-cols-2 gap-6">
                    {/* Strengths */}
                    {replayData.performance_insights.strengths?.length > 0 && (
                      <div className="clean-card p-6">
                        <h3 className="font-medium text-foreground mb-4 flex items-center gap-2">
                          <span>✅</span>
                          Strengths
                        </h3>
                        <ul className="space-y-2">
                          {replayData.performance_insights.strengths.map((strength, index) => (
                            <li key={index} className="text-sm text-muted-foreground flex items-start gap-2">
                              <span className="text-green-500">•</span>
                              {strength}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {/* Improvement Areas */}
                    {replayData.performance_insights.improvement_areas?.length > 0 && (
                      <div className="clean-card p-6">
                        <h3 className="font-medium text-foreground mb-4 flex items-center gap-2">
                          <span>📈</span>
                          Areas for Improvement
                        </h3>
                        <ul className="space-y-2">
                          {replayData.performance_insights.improvement_areas.map((area, index) => (
                            <li key={index} className="text-sm text-muted-foreground flex items-start gap-2">
                              <span className="text-orange-500">•</span>
                              {area}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {/* Efficiency Analysis */}
                    {replayData.performance_insights.efficiency_analysis?.length > 0 && (
                      <div className="clean-card p-6">
                        <h3 className="font-medium text-foreground mb-4 flex items-center gap-2">
                          <span>⏱️</span>
                          Efficiency Analysis
                        </h3>
                        <ul className="space-y-2">
                          {replayData.performance_insights.efficiency_analysis.map((analysis, index) => (
                            <li key={index} className="text-sm text-muted-foreground flex items-start gap-2">
                              <span className="text-blue-500">•</span>
                              {analysis}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {/* Resource Management */}
                    {replayData.performance_insights.resource_management?.length > 0 && (
                      <div className="clean-card p-6">
                        <h3 className="font-medium text-foreground mb-4 flex items-center gap-2">
                          <span>💰</span>
                          Resource Management
                        </h3>
                        <ul className="space-y-2">
                          {replayData.performance_insights.resource_management.map((insight, index) => (
                            <li key={index} className="text-sm text-muted-foreground flex items-start gap-2">
                              <span className="text-purple-500">•</span>
                              {insight}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Footer Actions */}
          <div className="flex items-center justify-between pt-6 border-t border-border">
            <Link
              href={route('osce.results.show', session.id)}
              className="text-muted-foreground hover:text-foreground text-sm"
            >
              ← Back to Assessment Results
            </Link>

            <div className="flex gap-2">
              <Button variant="outline" onClick={loadReplayData}>
                🔄 Regenerate Analysis
              </Button>

              {replayData.voiceover_scripts?.conclusion && (
                <Button
                  onClick={() => playVoiceover('conclusion')}
                  disabled={playingVoiceover === 'conclusion'}
                >
                  🔊 Play Conclusion
                </Button>
              )}
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}