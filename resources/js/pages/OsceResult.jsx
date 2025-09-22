import React, { useState, useEffect, useCallback } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import useAssessmentStatus from '@/hooks/useAssessmentStatus';
import QueueIndicator from '@/components/QueueIndicator';
import FinalizeSessionModal from '@/components/react/FinalizeSessionModal';

export default function OsceResult({ session, isAssessed = true, canReassess = false, assessment: assessmentData = null, error = null }) {
  const [currentAssessmentData, setCurrentAssessmentData] = useState(assessmentData);
  const [isPolling, setIsPolling] = useState(false);
  const [statusData, setStatusData] = useState(null);
  const [isReassessing, setIsReassessing] = useState(false);
  const [transcript, setTranscript] = useState([]);
  const [loadingTranscript, setLoadingTranscript] = useState(false);
  const [showFinalizeModal, setShowFinalizeModal] = useState(false);

  // Stable fetcher for assessment results
  const fetchAssessmentResults = useCallback(async () => {
    try {
      const resultsRes = await fetch(route('osce.results', session.id));
      if (resultsRes.ok) {
        const resultsData = await resultsRes.json();
        setCurrentAssessmentData((prev) => ({
          ...(prev || {}),
          ...resultsData,
          output: resultsData.assessor_output ?? prev?.output,
          area_results: resultsData.area_results ?? prev?.area_results ?? [],
          areas: resultsData.areas ?? prev?.areas ?? [],
        }));
      }
    } catch (e) {
      console.warn('Failed to fetch assessment results');
    }
  }, [session?.id]);

  // Stable status change handler to avoid re-subscribing SSE/polling
  const handleStatusChange = useCallback((newStatus, prevStatus) => {
    setStatusData(newStatus);

    // Merge progressive area results so UI can show 1/5, 2/5, ... with details
    if (Array.isArray(newStatus?.area_results) && newStatus.area_results.length > 0) {
      setCurrentAssessmentData(prev => {
        const prevAreas = prev?.area_results || [];
        const byKey = Object.fromEntries(prevAreas.map(a => [a.key, a]));
        newStatus.area_results.forEach(a => { byKey[a.key] = a; });
        return {
          ...(prev || {}),
          area_results: Object.values(byKey),
        };
      });
    }

    // Fetch full results when assessment completes
    if (newStatus.status === 'completed' && (!prevStatus || prevStatus.status !== 'completed')) {
      fetchAssessmentResults();
    }
  }, [fetchAssessmentResults]);

  // Use real-time assessment status hook
  const enableRealtime = !currentAssessmentData || isReassessing;
  const { status: queueStatus, isConnected, error: connectionError, disconnect } = useAssessmentStatus(
    session?.id,
    {
      enableSSE: enableRealtime,
      onStatusChange: handleStatusChange,
    }
  );

  const breadcrumbs = [
    { title: 'osce', href: route('osce') },
    { title: 'results', href: '#' },
  ];

  // Check if session needs finalization
  const needsFinalization = session?.status === 'completed' && !session?.finalized_at;

  // Auto-show finalize modal if needed
  useEffect(() => {
    if (needsFinalization) {
      setShowFinalizeModal(true);
    }
  }, [needsFinalization]);

  // Disconnect real-time updates once completed to stop further polling
  useEffect(() => {
    if (queueStatus?.status === 'completed' || statusData?.status === 'completed') {
      disconnect?.();
    }
  }, [queueStatus?.status, statusData?.status, disconnect]);

  // Legacy polling fallback (only if SSE is not working)
  useEffect(() => {
    if (!isAssessed && !queueStatus && !connectionError) {
      const pollStatus = async () => {
        setIsPolling(true);
        try {
          const res = await fetch(route('osce.status', session.id));
          if (res.ok) {
            const data = await res.json();
            setStatusData(data);
            
            // If assessment is complete, fetch updated results
            if (data.status === 'completed') {
              fetchAssessmentResults();
            }
          }
        } catch (e) {
          console.warn('Failed to poll status');
        } finally {
          setIsPolling(false);
        }
      };

      // Only poll if real-time connection is not working
      if (!isConnected) {
        pollStatus();
        const interval = setInterval(pollStatus, 5000);
        return () => clearInterval(interval);
      }
    }
  }, [session.id, isAssessed, currentAssessmentData, queueStatus, isConnected, connectionError]);

  // Fetch transcript for anchors (first 100 messages)
  useEffect(() => {
    let ignore = false;
    const fetchTranscript = async () => {
      try {
        setLoadingTranscript(true);
        const res = await fetch(route('osce.chat.history', session.id) + '?limit=100');
        if (res.ok) {
          const data = await res.json();
          if (!ignore) setTranscript(data?.messages || []);
        }
      } catch (e) {
        // ignore
      } finally {
        setLoadingTranscript(false);
      }
    };
    if (session?.id) fetchTranscript();
    return () => { ignore = true; };
  }, [session?.id]);

  const triggerReassessment = async () => {
    setIsReassessing(true);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      // Prefer JSON API to get immediate queue status
      const res = await fetch(route('osce.assess', session.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf ?? ''
        },
        credentials: 'same-origin',
        body: JSON.stringify({ force: true })
      });

      if (res.ok) {
        const data = await res.json().catch(() => null);
        if (data && data.status) {
          setStatusData(data);
        } else {
          setStatusData({ status: 'queued', progress_percentage: 0 });
        }
      }
    } catch (e) {
      console.error('Failed to trigger reassessment', e);
    } finally {
      setIsReassessing(false);
    }
  };

  const getScoreBadge = (score, maxScore) => {
    const percentage = (score / maxScore) * 100;
    let className = 'px-3 py-1 rounded-full text-sm font-medium ';
    
    if (percentage >= 80) {
      className += 'bg-green-100 text-green-800';
    } else if (percentage >= 60) {
      className += 'bg-yellow-100 text-yellow-800';
    } else {
      className += 'bg-red-100 text-red-800';
    }
    
    return className;
  };

  const getBand = (percentage) => {
    if (percentage >= 90) return 'Exceptional';
    if (percentage >= 80) return 'Excellent';
    if (percentage >= 70) return 'Good';
    if (percentage >= 60) return 'Satisfactory';
    if (percentage >= 50) return 'Needs Improvement';
    return 'Unsatisfactory';
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
  };

  const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('id-ID', {
      timeZone: 'Asia/Jakarta',
      hour12: false
    });
  };

  return (
    <>
      <Head title="osce results" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-8">
          {/* Header */}
          <div className="text-center space-y-4 relative">
            <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-20 h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent" />
            <div className="flex items-center justify-center gap-3 mb-2">
              <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400" />
              <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">assessment results</span>
              <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400" />
            </div>
            <h1 className="text-2xl font-medium lowercase glow-text text-foreground">{session?.case?.title || 'osce results'}</h1>
            {canReassess && (
              <div className="flex items-center justify-center">
                <button
                  onClick={triggerReassessment}
                  disabled={
                    isReassessing || 
                    queueStatus?.status === 'queued' || 
                    queueStatus?.status === 'in_progress' ||
                    statusData?.status === 'processing'
                  }
                  className="cyber-button px-4 py-2 text-blue-600 dark:text-blue-300 font-mono uppercase tracking-wide text-xs disabled:opacity-50"
                >
                  {isReassessing ? 'triggering…' : 
                   queueStatus?.status === 'queued' ? 'queued…' :
                   queueStatus?.status === 'in_progress' ? 'processing…' :
                   'reassess'}
                </button>
              </div>
            )}
          </div>

          {/* Real-time Queue Status */}
          {(queueStatus || statusData) && (
            <QueueIndicator 
              status={queueStatus || statusData} 
              className="mb-6"
            />
          )}

          {/* In-progress Warning */}
          {(queueStatus?.status === 'queued' || queueStatus?.status === 'in_progress' || statusData?.status === 'queued' || statusData?.status === 'in_progress') && (
            <div className="cyber-border bg-yellow-50/10 border-yellow-500/30 p-3">
              <div className="flex items-center gap-2">
                <span className="text-yellow-500">⚠️</span>
                <span className="text-xs text-muted-foreground lowercase">assessment in progress — please check back soon.</span>
              </div>
            </div>
          )}

          {/* Connection Status */}
          {connectionError && (
            <div className="cyber-border bg-yellow-50/10 border-yellow-500/30 p-3">
              <div className="flex items-center gap-2">
                <span className="text-yellow-500">⚠️</span>
                <span className="text-xs text-muted-foreground lowercase">real-time unavailable. using fallback polling. ({connectionError})</span>
              </div>
            </div>
          )}

          {/* Error State */}
          {!isAssessed && error && (
            <div className="cyber-border bg-red-50/10 border-red-500/30 p-4">
              <div className="text-red-400 text-sm lowercase font-mono">
                {error || 'this session has not been assessed yet.'}
              </div>
            </div>
          )}

          {/* Results Content */}
          {currentAssessmentData && (
            <div className="space-y-6">
              {/* Finalization callout (if not finalized) */}
              {needsFinalization && (
                <div className="cyber-border bg-gradient-to-br from-amber-500/10 to-amber-600/5 border-amber-500/30 p-4 relative">
                  <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-amber-400 to-orange-400 opacity-60" />
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="flex items-center gap-3 mb-2">
                        <div className="w-1 h-4 bg-gradient-to-b from-amber-400 to-orange-400" />
                        <span className="text-xs text-amber-400 font-mono uppercase tracking-wider">action required</span>
                      </div>
                      <div className="text-sm text-foreground lowercase">session must be finalized to complete the workflow</div>
                      <div className="text-xs text-muted-foreground mt-1">provide diagnosis, differential, and management plan</div>
                    </div>
                    <button
                      onClick={() => setShowFinalizeModal(true)}
                      className="cyber-button px-4 py-2 text-amber-600 dark:text-amber-300 font-mono uppercase tracking-wide text-xs"
                    >
                      finalize now
                    </button>
                  </div>
                </div>
              )}
              {/* Reassessment in Progress Banner */}
              {(queueStatus?.status === 'queued' || queueStatus?.status === 'in_progress' || statusData?.status === 'processing') && (
                <div className="cyber-border bg-blue-50/10 border-blue-500/30 p-4">
                  <div className="flex items-center gap-3">
                    <div className="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                    <div className="text-sm text-blue-400 lowercase font-mono">
                      reassessment in progress — showing previous results below
                    </div>
                  </div>
                </div>
              )}

              {/* Performance Overview */}
              <div className="cyber-border bg-card/30 p-6">
                <div className="flex items-center gap-3 mb-4">
                  <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400" />
                  <h2 className="text-lg font-medium lowercase text-foreground font-mono">performance overview</h2>
                  <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent" />
                </div>
                <div className="grid md:grid-cols-3 gap-6">
                  <div className="text-center">
                    <div className="text-3xl font-bold text-blue-500">
                      {(() => {
                        const score = Number(currentAssessmentData?.score ?? 0);
                        const max = Number(currentAssessmentData?.max_score ?? 0);
                        if (!max || max <= 0) return 0 + '%';
                        return Math.round((score / max) * 100) + '%';
                      })()}
                    </div>
                    <div className="text-xs text-muted-foreground lowercase">overall score</div>
                    <div className="text-lg font-semibold mt-1">
                      {(() => {
                        const score = Number(currentAssessmentData?.score ?? 0);
                        const max = Number(currentAssessmentData?.max_score ?? 0);
                        const pct = max > 0 ? (score / max) * 100 : 0;
                        return getBand(pct);
                      })()}
                    </div>
                  </div>
                  <div className="text-center">
                    <div className="text-3xl font-bold text-emerald-500">
                      {Math.round((session?.clinical_reasoning_score || 0))}%
                    </div>
                    <div className="text-xs text-muted-foreground lowercase">clinical reasoning</div>
                    <div className="text-lg font-semibold mt-1">
                      {session?.clinical_reasoning_score || 0} points
                    </div>
                  </div>
                  <div className="text-center">
                    <div className="text-3xl font-bold text-orange-500">
                      {formatCurrency(session?.total_test_cost || 0)}
                    </div>
                    <div className="text-xs text-muted-foreground lowercase">total test cost</div>
                    <div className="text-xs text-muted-foreground mt-1 lowercase font-mono">
                      budget: {formatCurrency(session?.case?.budget || 1000)}
                    </div>
                  </div>
                </div>
              </div>

          {/* Clinical Areas Assessment */}
          <div className="cyber-border bg-card/30 p-6">
                <div className="flex items-center gap-3 mb-4">
                  <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400" />
                  <h2 className="text-lg font-medium lowercase text-foreground font-mono">clinical areas assessment</h2>
                  <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent" />
                </div>
                <div className="grid gap-4">
                  {(currentAssessmentData?.output?.clinical_areas || currentAssessmentData.area_results || currentAssessmentData.areas || []).map((area, index) => (
                    <div key={index} className="cyber-border p-4 bg-card/20">
                      <div className="flex justify-between items-start mb-3">
                        <div>
                          <h3 className="font-semibold text-base lowercase text-foreground">{area.area}</h3>
                          <p className="text-xs text-muted-foreground lowercase">{area.description}</p>
                        </div>
                        <div className="text-right">
                          <div className={getScoreBadge(area.score, area.max_score)}>
                            {area.score}/{area.max_score}
                          </div>
                          <div className="text-xs text-muted-foreground mt-1 lowercase font-mono">
                            {area.badge_text}
                          </div>
                        </div>
                      </div>
                      
                      {area.justification && (
                        <div className="mb-3">
                          <h4 className="font-medium text-xs text-muted-foreground mb-1 lowercase">assessment</h4>
                          <p className="text-sm text-foreground/90">{area.justification}</p>
                        </div>
                      )}

                      {Array.isArray(area.outline) && area.outline.length > 0 && (
                        <div className="mb-3">
                          <h4 className="font-medium text-xs text-muted-foreground mb-1 lowercase">outline</h4>
                          <ul className="text-sm text-foreground/90 list-disc list-inside">
                            {area.outline.map((item, i) => (
                              <li key={i}>{item}</li>
                            ))}
                          </ul>
                        </div>
                      )}

                      {area.strengths && area.strengths.length > 0 && (
                        <div className="mb-3">
                          <h4 className="font-medium text-xs text-emerald-500 mb-1 lowercase">strengths</h4>
                          <ul className="text-sm text-foreground/90 list-disc list-inside">
                            {area.strengths.map((strength, i) => (
                              <li key={i}>{strength}</li>
                            ))}
                          </ul>
                        </div>
                      )}

                      {area.areas_for_improvement && area.areas_for_improvement.length > 0 && (
                        <div className="mb-3">
                          <h4 className="font-medium text-xs text-orange-500 mb-1 lowercase">areas for improvement</h4>
                          <ul className="text-sm text-foreground/90 list-disc list-inside">
                            {area.areas_for_improvement.map((improvement, i) => (
                              <li key={i}>{improvement}</li>
                            ))}
                          </ul>
                        </div>
                      )}

                      {area.citations && area.citations.length > 0 && (
                        <div>
                          <h4 className="font-medium text-xs text-muted-foreground mb-1 lowercase">references</h4>
                          <div className="space-y-1">
                            {area.citations.map((citation, i) => {
                              const c = typeof citation === 'string' ? { title: citation, source: 'session', url: null } : citation;
                              return (
                                <div key={i} className="text-xs text-blue-600">
                                  {c.url ? (
                                    <a href={c.url} className="hover:underline">
                                      {c.title} ({c.source})
                                    </a>
                                  ) : (
                                    <span>{c.title} ({c.source})</span>
                                  )}
                                </div>
                              );
                            })}
                          </div>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              {/* Debugging — AI fallback and errors */}
              {(() => {
                const areas = currentAssessmentData?.output?.clinical_areas || currentAssessmentData.area_results || [];
                const hasFallbacks = (currentAssessmentData?.has_fallbacks) || areas.some((a) => a.status === 'fallback');
                if (!hasFallbacks) return null;
                return (
                  <div className="cyber-border bg-yellow-50/10 border-yellow-500/30 p-4">
                    <div className="flex items-center gap-2 mb-2">
                      <span className="text-yellow-500">⚠️</span>
                      <span className="text-xs text-muted-foreground lowercase">ai issues detected — some areas used rubric fallback</span>
                    </div>
                    <div className="space-y-1">
                      {areas.map((a, i) => (
                        <div key={i} className="text-xs text-muted-foreground">
                          <span className="font-mono">{a.key}</span>: status=<span className="font-mono">{a.status}</span>, attempts={a.attempts ?? 0}{' '}
                          {a.error_message ? (
                            <>
                              • last_error: <span className="text-foreground/90">{String(a.error_message).slice(0, 200)}</span>
                            </>
                          ) : null}
                        </div>
                      ))}
                    </div>
                  </div>
                );
              })()}

              {/* Session Summary */}
              <div className="cyber-border bg-card/30 p-6">
                <div className="flex items-center gap-3 mb-4">
                  <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400" />
                  <h2 className="text-lg font-medium lowercase text-foreground font-mono">session summary</h2>
                  <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent" />
                </div>
                <div className="grid md:grid-cols-2 gap-6">
                  <div className="space-y-3">
                    <div>
                      <span className="font-medium lowercase">duration:</span>
                      <span className="ml-2">
                        {session?.duration_minutes} minutes 
                        {session?.time_extended && ` (+${session.time_extended} extended)`}
                      </span>
                    </div>
                    <div>
                      <span className="font-medium lowercase">messages exchanged:</span>
                      <span className="ml-2">{session?.messages_count || 0}</span>
                    </div>
                    <div>
                      <span className="font-medium lowercase">physical exams:</span>
                      <span className="ml-2">{session?.examinations_count || 0}</span>
                    </div>
                    <div>
                      <span className="font-medium lowercase">tests ordered:</span>
                      <span className="ml-2">{session?.ordered_tests?.length || 0}</span>
                    </div>
                  </div>
                  <div className="space-y-3">
                    <div>
                      <span className="font-medium lowercase">session started:</span>
                      <span className="ml-2">
                        {session?.started_at ? formatDateTime(session.started_at) : 'N/A'}
                      </span>
                    </div>
                    <div>
                      <span className="font-medium lowercase">session completed:</span>
                      <span className="ml-2">
                        {session?.completed_at ? formatDateTime(session.completed_at) : 'N/A'}
                      </span>
                    </div>
                    <div>
                      <span className="font-medium lowercase">assessed at:</span>
                      <span className="ml-2">
                        {currentAssessmentData.assessed_at ? formatDateTime(currentAssessmentData.assessed_at) : 'N/A'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Test Orders Analysis */}
              {session?.ordered_tests && session.ordered_tests.length > 0 && (
                <div className="cyber-border bg-card/30 p-6">
                  <div className="flex items-center gap-3 mb-4">
                    <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400" />
                    <h2 className="text-lg font-medium lowercase text-foreground font-mono">test orders analysis</h2>
                    <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent" />
                  </div>
                  <div className="space-y-3">
                    {session.ordered_tests.map((test, index) => {
                      const slug = String(test.test_name || '')
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/(^-|-$)/g, '');
                      return (
                        <div key={index} id={`test-${slug}`} className="cyber-border p-3 bg-card/20">
                        <div className="flex justify-between items-start">
                          <div className="flex-1">
                            <div className="font-medium">{test.test_name}</div>
                            <div className="text-xs text-muted-foreground mt-1 lowercase">
                              <span className="font-mono text-foreground/80">reasoning:</span> {test.clinical_reasoning}
                            </div>
                            <div className="text-xs text-muted-foreground mt-1 lowercase font-mono">
                              priority: {test.priority} • cost: {formatCurrency(test.cost)}
                            </div>
                          </div>
                          <div className="ml-4 text-right">
                            <div className="text-sm font-medium">
                              {test.results?.status === 'no_data' ? (
                                <span className="text-yellow-500">not available</span>
                              ) : test.results ? (
                                <span className="text-emerald-500">available</span>
                              ) : (
                                <span className="text-muted-foreground">pending</span>
                              )}
                            </div>
                          </div>
                        </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Feedback Summary */}
              {(currentAssessmentData.overall_feedback || currentAssessmentData?.output?.overall_feedback) && (
                <div className="cyber-border bg-blue-50/10 border-blue-500/30 p-6">
                  <div className="flex items-center gap-3 mb-2">
                    <div className="w-1 h-6 bg-gradient-to-b from-blue-400 to-cyan-400" />
                    <h2 className="text-lg font-medium lowercase text-foreground font-mono">feedback summary</h2>
                  </div>
                  <div className="text-sm text-foreground/90">
                    {currentAssessmentData.overall_feedback || currentAssessmentData?.output?.overall_feedback}
                  </div>
                </div>
              )}
              </div>
            )}

            {/* Examinations Summary with anchors */}
            {Array.isArray(session?.examinations) && session.examinations.length > 0 && (
              <div className="cyber-border bg-card/30 p-6">
                <div className="flex items-center gap-3 mb-4">
                  <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400" />
                  <h2 className="text-lg font-medium lowercase text-foreground font-mono">examinations summary</h2>
                  <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent" />
                </div>
                <div className="space-y-2">
                  {session.examinations.map((exam, idx) => {
                    const slug = String(exam.examination_type || '')
                      .toLowerCase()
                      .replace(/[^a-z0-9]+/g, '-')
                      .replace(/(^-|-$)/g, '');
                    return (
                      <div key={idx} id={`exam-${slug}`} className="text-sm">
                        <span className="font-medium">{exam.examination_type}</span>
                        {exam.examination_category ? (
                          <span className="text-muted-foreground"> — {exam.examination_category}</span>
                        ) : null}
                        {exam.findings && (
                          <span className="text-muted-foreground"> • findings: {exam.findings}</span>
                        )}
                      </div>
                    );
                  })}
                </div>
              </div>
            )}

            {/* Transcript (first 100) with anchors */}
            {Array.isArray(transcript) && transcript.length > 0 && (
              <div className="cyber-border bg-card/30 p-6">
                <div className="flex items-center gap-3 mb-4">
                  <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400" />
                  <h2 className="text-lg font-medium lowercase text-foreground font-mono">transcript (first 100)</h2>
                  <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent" />
                </div>
                <div className="space-y-1">
                  {transcript.map((m, i) => (
                    <div key={m.id || i} id={`msg-${i + 1}`} className="text-xs">
                      <span className="font-mono text-muted-foreground">#{i + 1}</span>{' '}
                      <span className="font-medium">{m.sender_type}</span>:{' '}
                      <span className="text-foreground/90">{m.message}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}

          {/* Legacy Assessment Data Fallback */}
          {!currentAssessmentData && isAssessed && !statusData?.status === 'processing' && (
            <div className="cyber-border bg-card/30 p-6">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400" />
                <h2 className="text-lg font-medium lowercase text-foreground font-mono">session assessment</h2>
                <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent" />
              </div>
              <div className="space-y-4">
                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <span className="font-medium lowercase">clinical reasoning score:</span>
                    <span className="ml-2">{session?.clinical_reasoning_score || 0} points</span>
                  </div>
                  <div>
                    <span className="font-medium lowercase">total test cost:</span>
                    <span className="ml-2">{formatCurrency(session?.total_test_cost || 0)}</span>
                  </div>
                </div>
                <div className="text-xs text-muted-foreground lowercase">
                  detailed assessment results will be available after processing.
                </div>
              </div>
            </div>
          )}

          {/* Navigation */}
          <div className="flex justify-between">
            <Link href={route('osce')} className="cyber-button px-3 py-2 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wide text-xs">
              ← back to osce
            </Link>
            {session?.is_rationalization_complete && (
              <Link 
                href={route('osce.rationalization.show', session.id)} 
                className="cyber-button px-3 py-2 text-blue-600 dark:text-blue-300 font-mono uppercase tracking-wide text-xs"
              >
                view rationalization →
              </Link>
            )}
          </div>
        </div>
      </AppLayout>
      {/* Finalize Modal */}
      <FinalizeSessionModal 
        open={showFinalizeModal} 
        onClose={() => setShowFinalizeModal(false)}
        session={session}
      />
    </>
  );
}
