import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function OsceResult({ session, isAssessed = true, canReassess = false, assessmentData = null, error = null }) {
  const [currentAssessmentData, setCurrentAssessmentData] = useState(assessmentData);
  const [isPolling, setIsPolling] = useState(false);
  const [statusData, setStatusData] = useState(null);
  const [isReassessing, setIsReassessing] = useState(false);

  const breadcrumbs = [
    { title: 'OSCE', href: route('osce') },
    { title: 'Result', href: '#' },
  ];

  // Poll assessment status
  useEffect(() => {
    if (!isAssessed || currentAssessmentData?.is_processing) {
      const pollStatus = async () => {
        setIsPolling(true);
        try {
          const res = await fetch(route('osce.status', session.id));
          if (res.ok) {
            const data = await res.json();
            setStatusData(data);
            
            // If assessment is complete, fetch updated results
            if (data.status === 'completed' && !currentAssessmentData) {
              const resultsRes = await fetch(route('osce.results', session.id));
              if (resultsRes.ok) {
                const resultsData = await resultsRes.json();
                setCurrentAssessmentData(resultsData);
              }
            }
          }
        } catch (e) {
          console.warn('Failed to poll status');
        } finally {
          setIsPolling(false);
        }
      };

      // Poll immediately and then every 5 seconds
      pollStatus();
      const interval = setInterval(pollStatus, 5000);
      return () => clearInterval(interval);
    }
  }, [session.id, isAssessed, currentAssessmentData]);

  const triggerReassessment = async () => {
    setIsReassessing(true);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch(route('osce.assess.trigger', session.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf ?? ''
        },
        credentials: 'same-origin'
      });

      if (res.ok) {
        // Start polling for new results
        setCurrentAssessmentData(null);
        setStatusData({ status: 'processing', progress: 0 });
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
      <Head title="OSCE Result" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-6">
          {/* Header */}
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-2xl font-bold">OSCE Results</h1>
              <p className="text-gray-600">{session?.osce_case?.title}</p>
            </div>
            {canReassess && (
              <button
                onClick={triggerReassessment}
                disabled={isReassessing || statusData?.status === 'processing'}
                className="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50"
              >
                {isReassessing ? 'Triggering...' : 'Reassess'}
              </button>
            )}
          </div>

          {/* Assessment Status */}
          {statusData?.status === 'processing' && (
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div className="flex items-center space-x-3">
                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                <div>
                  <div className="font-medium text-blue-900">Assessment in Progress</div>
                  <div className="text-sm text-blue-700">
                    {statusData.current_area ? `Analyzing ${statusData.current_area}...` : 'Processing...'}
                  </div>
                  {statusData.progress !== undefined && (
                    <div className="mt-2 bg-blue-200 rounded-full h-2">
                      <div 
                        className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        style={{ width: `${statusData.progress}%` }}
                      ></div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Error State */}
          {!isAssessed && error && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
              <div className="text-red-900">
                {error || 'This session has not been assessed yet.'}
              </div>
            </div>
          )}

          {/* Results Content */}
          {currentAssessmentData && (
            <div className="space-y-6">
              {/* Performance Overview */}
              <div className="bg-white border rounded-lg p-6">
                <h2 className="text-xl font-semibold mb-4">Performance Overview</h2>
                <div className="grid md:grid-cols-3 gap-6">
                  <div className="text-center">
                    <div className="text-3xl font-bold text-blue-600">
                      {Math.round((currentAssessmentData.overall_score / currentAssessmentData.max_score) * 100)}%
                    </div>
                    <div className="text-sm text-gray-600">Overall Score</div>
                    <div className="text-lg font-semibold mt-1">
                      {getBand((currentAssessmentData.overall_score / currentAssessmentData.max_score) * 100)}
                    </div>
                  </div>
                  <div className="text-center">
                    <div className="text-3xl font-bold text-green-600">
                      {Math.round((currentAssessmentData.clinical_reasoning_score || 0))}%
                    </div>
                    <div className="text-sm text-gray-600">Clinical Reasoning</div>
                    <div className="text-lg font-semibold mt-1">
                      {session?.clinical_reasoning_score || 0} points
                    </div>
                  </div>
                  <div className="text-center">
                    <div className="text-3xl font-bold text-orange-600">
                      {formatCurrency(session?.total_test_cost || 0)}
                    </div>
                    <div className="text-sm text-gray-600">Total Test Cost</div>
                    <div className="text-sm text-gray-500 mt-1">
                      Budget: {formatCurrency(session?.osce_case?.case_budget || 1000)}
                    </div>
                  </div>
                </div>
              </div>

              {/* Clinical Areas Assessment */}
              <div className="bg-white border rounded-lg p-6">
                <h2 className="text-xl font-semibold mb-4">Clinical Areas Assessment</h2>
                <div className="grid gap-4">
                  {(currentAssessmentData.areas || []).map((area, index) => (
                    <div key={index} className="border rounded p-4">
                      <div className="flex justify-between items-start mb-3">
                        <div>
                          <h3 className="font-semibold text-lg">{area.area}</h3>
                          <p className="text-sm text-gray-600">{area.description}</p>
                        </div>
                        <div className="text-right">
                          <div className={getScoreBadge(area.score, area.max_score)}>
                            {area.score}/{area.max_score}
                          </div>
                          <div className="text-sm text-gray-500 mt-1">
                            {area.badge_text}
                          </div>
                        </div>
                      </div>
                      
                      {area.justification && (
                        <div className="mb-3">
                          <h4 className="font-medium text-sm text-gray-700 mb-1">Assessment:</h4>
                          <p className="text-sm text-gray-600">{area.justification}</p>
                        </div>
                      )}

                      {area.strengths && area.strengths.length > 0 && (
                        <div className="mb-3">
                          <h4 className="font-medium text-sm text-green-700 mb-1">Strengths:</h4>
                          <ul className="text-sm text-gray-600 list-disc list-inside">
                            {area.strengths.map((strength, i) => (
                              <li key={i}>{strength}</li>
                            ))}
                          </ul>
                        </div>
                      )}

                      {area.areas_for_improvement && area.areas_for_improvement.length > 0 && (
                        <div className="mb-3">
                          <h4 className="font-medium text-sm text-orange-700 mb-1">Areas for Improvement:</h4>
                          <ul className="text-sm text-gray-600 list-disc list-inside">
                            {area.areas_for_improvement.map((improvement, i) => (
                              <li key={i}>{improvement}</li>
                            ))}
                          </ul>
                        </div>
                      )}

                      {area.citations && area.citations.length > 0 && (
                        <div>
                          <h4 className="font-medium text-sm text-gray-700 mb-1">References:</h4>
                          <div className="space-y-1">
                            {area.citations.map((citation, i) => (
                              <div key={i} className="text-xs text-blue-600">
                                {citation.url ? (
                                  <a href={citation.url} target="_blank" rel="noopener noreferrer" className="hover:underline">
                                    {citation.title} ({citation.source})
                                  </a>
                                ) : (
                                  <span>{citation.title} ({citation.source})</span>
                                )}
                              </div>
                            ))}
                          </div>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              {/* Session Summary */}
              <div className="bg-white border rounded-lg p-6">
                <h2 className="text-xl font-semibold mb-4">Session Summary</h2>
                <div className="grid md:grid-cols-2 gap-6">
                  <div className="space-y-3">
                    <div>
                      <span className="font-medium">Duration:</span>
                      <span className="ml-2">
                        {session?.duration_minutes} minutes 
                        {session?.time_extended && ` (+${session.time_extended} extended)`}
                      </span>
                    </div>
                    <div>
                      <span className="font-medium">Messages Exchanged:</span>
                      <span className="ml-2">{session?.messages_count || 0}</span>
                    </div>
                    <div>
                      <span className="font-medium">Physical Exams:</span>
                      <span className="ml-2">{session?.examinations_count || 0}</span>
                    </div>
                    <div>
                      <span className="font-medium">Tests Ordered:</span>
                      <span className="ml-2">{session?.ordered_tests?.length || 0}</span>
                    </div>
                  </div>
                  <div className="space-y-3">
                    <div>
                      <span className="font-medium">Session Started:</span>
                      <span className="ml-2">
                        {session?.started_at ? formatDateTime(session.started_at) : 'N/A'}
                      </span>
                    </div>
                    <div>
                      <span className="font-medium">Session Completed:</span>
                      <span className="ml-2">
                        {session?.completed_at ? formatDateTime(session.completed_at) : 'N/A'}
                      </span>
                    </div>
                    <div>
                      <span className="font-medium">Assessed At:</span>
                      <span className="ml-2">
                        {currentAssessmentData.assessed_at ? formatDateTime(currentAssessmentData.assessed_at) : 'N/A'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Test Orders Analysis */}
              {session?.ordered_tests && session.ordered_tests.length > 0 && (
                <div className="bg-white border rounded-lg p-6">
                  <h2 className="text-xl font-semibold mb-4">Test Orders Analysis</h2>
                  <div className="space-y-3">
                    {session.ordered_tests.map((test, index) => (
                      <div key={index} className="border rounded p-3">
                        <div className="flex justify-between items-start">
                          <div className="flex-1">
                            <div className="font-medium">{test.test_name}</div>
                            <div className="text-sm text-gray-600 mt-1">
                              <strong>Reasoning:</strong> {test.clinical_reasoning}
                            </div>
                            <div className="text-xs text-gray-500 mt-1">
                              Priority: {test.priority} • Cost: {formatCurrency(test.cost)}
                            </div>
                          </div>
                          <div className="ml-4 text-right">
                            <div className="text-sm font-medium">
                              {test.results?.status === 'no_data' ? (
                                <span className="text-yellow-600">Not Available</span>
                              ) : test.results ? (
                                <span className="text-green-600">Available</span>
                              ) : (
                                <span className="text-gray-500">Pending</span>
                              )}
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Feedback Summary */}
              {currentAssessmentData.feedback_summary && (
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
                  <h2 className="text-xl font-semibold mb-4 text-blue-900">Feedback Summary</h2>
                  <div className="text-blue-800">
                    {currentAssessmentData.feedback_summary}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Legacy Assessment Data Fallback */}
          {!currentAssessmentData && isAssessed && !statusData?.status === 'processing' && (
            <div className="bg-white border rounded-lg p-6">
              <h2 className="text-xl font-semibold mb-4">Session Assessment</h2>
              <div className="space-y-4">
                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <span className="font-medium">Clinical Reasoning Score:</span>
                    <span className="ml-2">{session?.clinical_reasoning_score || 0} points</span>
                  </div>
                  <div>
                    <span className="font-medium">Total Test Cost:</span>
                    <span className="ml-2">{formatCurrency(session?.total_test_cost || 0)}</span>
                  </div>
                </div>
                <div className="text-sm text-gray-600">
                  Detailed assessment results will be available after processing.
                </div>
              </div>
            </div>
          )}

          {/* Navigation */}
          <div className="flex justify-between">
            <Link href={route('osce')} className="text-blue-600 hover:text-blue-800">
              ← Back to OSCE
            </Link>
            {session?.is_rationalization_complete && (
              <Link 
                href={route('osce.rationalization.show', session.id)} 
                className="text-blue-600 hover:text-blue-800"
              >
                View Rationalization →
              </Link>
            )}
          </div>
        </div>
      </AppLayout>
    </>
  );
}