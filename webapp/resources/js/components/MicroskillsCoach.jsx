import React, { useState, useEffect } from 'react';
import { Button } from '@vibe-kanban/ui-kit';

export default function MicroskillsCoach({ sessionId, onInterventionDisplayed }) {
  const [activeTab, setActiveTab] = useState('insights');
  const [currentIntervention, setCurrentIntervention] = useState(null);
  const [interventionHistory, setInterventionHistory] = useState([]);
  const [currentQuiz, setCurrentQuiz] = useState(null);
  const [quizAnswer, setQuizAnswer] = useState(null);
  const [quizSubmitted, setQuizSubmitted] = useState(false);
  const [loading, setLoading] = useState(false);
  const [stats, setStats] = useState({});
  const [preferences, setPreferences] = useState({
    coaching_enabled: true,
    auto_display: true
  });

  useEffect(() => {
    if (sessionId) {
      checkForInterventions();
      loadHistory();
      loadPreferences();

      // Set up polling for real-time coaching
      const interval = setInterval(checkForInterventions, 30000); // Every 30 seconds
      return () => clearInterval(interval);
    }
  }, [sessionId]);

  const checkForInterventions = async () => {
    if (!preferences.coaching_enabled) return;

    try {
      const response = await fetch(route('microskills.status', sessionId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      const data = await response.json();

      if (data.success && data.has_pending_intervention) {
        setCurrentIntervention(data.pending_intervention);
        setStats(data.session_stats);

        if (preferences.auto_display && onInterventionDisplayed) {
          onInterventionDisplayed(data.pending_intervention);
        }
      }
    } catch (error) {
      console.error('Failed to check for interventions:', error);
    }
  };

  const loadHistory = async () => {
    try {
      const response = await fetch(route('microskills.history', sessionId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      const data = await response.json();

      if (data.success) {
        setInterventionHistory(data.interventions.data || []);
        setStats(data.stats);
      }
    } catch (error) {
      console.error('Failed to load coaching history:', error);
    }
  };

  const loadPreferences = async () => {
    try {
      const response = await fetch(route('microskills.preferences'), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      const data = await response.json();

      if (data.success) {
        setPreferences(data.preferences);
      }
    } catch (error) {
      console.error('Failed to load preferences:', error);
    }
  };

  const markInterventionDisplayed = async (interventionId) => {
    try {
      await fetch(route('microskills.mark-displayed', [sessionId, interventionId]), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        }
      });
    } catch (error) {
      console.error('Failed to mark intervention as displayed:', error);
    }
  };

  const loadQuiz = async () => {
    setLoading(true);
    try {
      const response = await fetch(route('microskills.quiz', sessionId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      const data = await response.json();

      if (data.success && data.has_quiz) {
        setCurrentQuiz(data.quiz);
        setQuizAnswer(null);
        setQuizSubmitted(false);
      }
    } catch (error) {
      console.error('Failed to load quiz:', error);
    } finally {
      setLoading(false);
    }
  };

  const submitQuizAnswer = async () => {
    if (quizAnswer === null || !currentQuiz) return;

    try {
      const response = await fetch(route('microskills.submit-quiz', sessionId), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          question_id: Date.now().toString(), // Simple ID for tracking
          selected_answer: quizAnswer,
          correct_answer: currentQuiz.correct_answer
        })
      });

      const data = await response.json();

      if (data.success) {
        setQuizSubmitted(true);
      }
    } catch (error) {
      console.error('Failed to submit quiz answer:', error);
    }
  };

  const getPriorityColor = (priority) => {
    switch(priority) {
      case 'high': return 'text-red-600 bg-red-100 border-red-200';
      case 'medium': return 'text-orange-600 bg-orange-100 border-orange-200';
      case 'low': return 'text-blue-600 bg-blue-100 border-blue-200';
      default: return 'text-gray-600 bg-gray-100 border-gray-200';
    }
  };

  const getTypeIcon = (type) => {
    switch(type) {
      case 'decision_support': return '🤔';
      case 'resource_management': return '💰';
      case 'time_management': return '⏱️';
      case 'communication': return '💬';
      case 'knowledge_check': return '🧠';
      default: return '💡';
    }
  };

  const formatTimeAgo = (timestamp) => {
    const diff = Date.now() - new Date(timestamp).getTime();
    const minutes = Math.floor(diff / 60000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;

    const hours = Math.floor(minutes / 60);
    return `${hours}h ago`;
  };

  return (
    <div className="h-full flex flex-col">
      {/* Tab Navigation */}
      <div className="flex border-b border-border">
        {[
          { key: 'insights', label: 'AI Insights', icon: '🎯' },
          { key: 'quiz', label: 'Quick Quiz', icon: '🧠' },
          { key: 'history', label: 'History', icon: '📋' }
        ].map((tab) => (
          <button
            key={tab.key}
            onClick={() => setActiveTab(tab.key)}
            className={`flex-1 px-3 py-2 text-sm font-medium transition-colors ${
              activeTab === tab.key
                ? 'text-emerald-600 border-b-2 border-emerald-600 bg-emerald-50'
                : 'text-muted-foreground hover:text-foreground'
            }`}
          >
            <span className="mr-1">{tab.icon}</span>
            {tab.label}
          </button>
        ))}
      </div>

      {/* Tab Content */}
      <div className="flex-1 overflow-y-auto p-4">
        {/* AI Insights Tab */}
        {activeTab === 'insights' && (
          <div className="space-y-4">
            {/* Current Intervention */}
            {currentIntervention ? (
              <div className={`clean-card p-4 border-l-4 ${
                currentIntervention.priority === 'high' ? 'border-red-500 bg-red-50' :
                currentIntervention.priority === 'medium' ? 'border-orange-500 bg-orange-50' :
                'border-blue-500 bg-blue-50'
              }`}>
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center gap-2">
                    <span className="text-lg">{getTypeIcon(currentIntervention.type)}</span>
                    <div>
                      <div className="font-medium text-foreground text-sm capitalize">
                        {currentIntervention.type.replace('_', ' ')}
                      </div>
                      <div className={`text-xs px-2 py-1 rounded ${getPriorityColor(currentIntervention.priority)}`}>
                        {currentIntervention.priority.toUpperCase()} PRIORITY
                      </div>
                    </div>
                  </div>
                  <button
                    onClick={() => {
                      setCurrentIntervention(null);
                      if (currentIntervention.id) {
                        markInterventionDisplayed(currentIntervention.id);
                      }
                    }}
                    className="text-muted-foreground hover:text-foreground text-sm"
                  >
                    ✕
                  </button>
                </div>

                <p className="text-sm text-foreground mb-3">{currentIntervention.content}</p>

                <div className="text-xs text-muted-foreground">
                  Triggered by: {currentIntervention.trigger?.replace('_', ' ')} •
                  Session progress: {Math.round(currentIntervention.session_progress || 0)}%
                </div>
              </div>
            ) : (
              <div className="clean-card p-6 text-center">
                <div className="text-4xl mb-3">🎯</div>
                <div className="font-medium text-foreground mb-2">No Active Coaching</div>
                <div className="text-sm text-muted-foreground">
                  Continue with your session. The AI coach will provide insights when helpful.
                </div>
              </div>
            )}

            {/* Session Stats */}
            {stats && Object.keys(stats).length > 0 && (
              <div className="clean-card p-4">
                <h3 className="font-medium text-foreground mb-3">Session Coaching Stats</h3>
                <div className="grid grid-cols-2 gap-3 text-sm">
                  <div>
                    <div className="text-muted-foreground">Total Interventions</div>
                    <div className="font-medium">{stats.total_interventions || 0}</div>
                  </div>
                  <div>
                    <div className="text-muted-foreground">Displayed</div>
                    <div className="font-medium">{stats.displayed_interventions || 0}</div>
                  </div>
                </div>
              </div>
            )}

            {/* Manual Analysis Trigger */}
            <Button
              onClick={checkForInterventions}
              variant="outline"
              className="w-full"
              disabled={loading}
            >
              🔄 Request Coaching Analysis
            </Button>
          </div>
        )}

        {/* Quiz Tab */}
        {activeTab === 'quiz' && (
          <div className="space-y-4">
            {currentQuiz ? (
              <div className="clean-card p-4">
                <div className="flex items-center gap-2 mb-4">
                  <span className="text-lg">🧠</span>
                  <div>
                    <div className="font-medium text-foreground">Knowledge Check</div>
                    <div className="text-xs text-muted-foreground">Topic: {currentQuiz.topic}</div>
                  </div>
                </div>

                <div className="mb-4">
                  <p className="text-sm text-foreground mb-3">{currentQuiz.question}</p>

                  <div className="space-y-2">
                    {currentQuiz.options.map((option, index) => (
                      <label
                        key={index}
                        className={`block p-3 border rounded cursor-pointer transition-colors ${
                          quizAnswer === index
                            ? 'border-emerald-500 bg-emerald-50'
                            : 'border-border hover:bg-muted/50'
                        } ${
                          quizSubmitted && index === currentQuiz.correct_answer
                            ? 'border-green-500 bg-green-50'
                            : quizSubmitted && quizAnswer === index && index !== currentQuiz.correct_answer
                              ? 'border-red-500 bg-red-50'
                              : ''
                        }`}
                      >
                        <input
                          type="radio"
                          name="quiz-answer"
                          value={index}
                          checked={quizAnswer === index}
                          onChange={() => setQuizAnswer(index)}
                          disabled={quizSubmitted}
                          className="sr-only"
                        />
                        <div className="flex items-center gap-2">
                          <span className="text-sm font-medium">
                            {String.fromCharCode(65 + index)}.
                          </span>
                          <span className="text-sm">{option}</span>
                        </div>
                      </label>
                    ))}
                  </div>
                </div>

                {!quizSubmitted ? (
                  <Button
                    onClick={submitQuizAnswer}
                    disabled={quizAnswer === null}
                    className="w-full"
                  >
                    Submit Answer
                  </Button>
                ) : (
                  <div className="space-y-3">
                    <div className={`p-3 rounded text-sm ${
                      quizAnswer === currentQuiz.correct_answer
                        ? 'bg-green-100 text-green-800'
                        : 'bg-red-100 text-red-800'
                    }`}>
                      {quizAnswer === currentQuiz.correct_answer ? '✅ Correct!' : '❌ Incorrect'}
                    </div>

                    <div className="p-3 bg-blue-50 rounded">
                      <div className="font-medium text-blue-800 mb-1">Explanation:</div>
                      <div className="text-sm text-blue-700">{currentQuiz.explanation}</div>
                    </div>

                    <Button
                      onClick={loadQuiz}
                      variant="outline"
                      className="w-full"
                    >
                      🔄 Load New Quiz
                    </Button>
                  </div>
                )}
              </div>
            ) : (
              <div className="clean-card p-6 text-center">
                <div className="text-4xl mb-3">🧠</div>
                <div className="font-medium text-foreground mb-2">Ready for a Knowledge Check?</div>
                <div className="text-sm text-muted-foreground mb-4">
                  Test your understanding with a quick question related to your current case.
                </div>
                <Button
                  onClick={loadQuiz}
                  disabled={loading}
                  className="w-full"
                >
                  {loading ? 'Loading Quiz...' : '🚀 Start Quiz'}
                </Button>
              </div>
            )}
          </div>
        )}

        {/* History Tab */}
        {activeTab === 'history' && (
          <div className="space-y-3">
            {interventionHistory.length > 0 ? (
              interventionHistory.map((intervention, index) => (
                <div key={intervention.id || index} className="clean-card p-3">
                  <div className="flex items-start justify-between mb-2">
                    <div className="flex items-center gap-2">
                      <span>{getTypeIcon(intervention.intervention_type)}</span>
                      <div>
                        <div className="text-sm font-medium text-foreground capitalize">
                          {intervention.intervention_type?.replace('_', ' ')}
                        </div>
                        <div className={`text-xs px-2 py-1 rounded ${getPriorityColor(intervention.priority)}`}>
                          {intervention.priority?.toUpperCase()}
                        </div>
                      </div>
                    </div>
                    <div className="text-xs text-muted-foreground">
                      {formatTimeAgo(intervention.created_at)}
                    </div>
                  </div>

                  <p className="text-sm text-muted-foreground">{intervention.content}</p>

                  {intervention.user_response && (
                    <div className="mt-2 p-2 bg-muted rounded text-xs">
                      <strong>Your response:</strong> {intervention.user_response}
                    </div>
                  )}
                </div>
              ))
            ) : (
              <div className="clean-card p-6 text-center">
                <div className="text-4xl mb-3">📋</div>
                <div className="font-medium text-foreground mb-2">No Coaching History</div>
                <div className="text-sm text-muted-foreground">
                  Coaching insights will appear here as you progress through the session.
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}