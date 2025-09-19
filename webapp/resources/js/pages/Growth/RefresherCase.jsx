import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@vibe-kanban/ui-kit';

export default function RefresherCase({ refresher }) {
  const [responses, setResponses] = useState({});
  const [performanceScore, setPerformanceScore] = useState(null);
  const [completed, setCompleted] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const breadcrumbs = [
    { title: 'growth', href: route('growth.dashboard') },
    { title: 'refresher', href: route('growth.refresher.show', refresher.id) }
  ];

  const content = refresher.formatted_content || refresher.content;

  const handleQuizResponse = (questionIndex, answer) => {
    setResponses(prev => ({
      ...prev,
      [questionIndex]: answer
    }));
  };

  const calculateQuizScore = () => {
    if (content.type !== 'quiz') return null;

    const questions = content.questions || [];
    let correct = 0;

    questions.forEach((question, index) => {
      if (responses[index] === question.correct_answer) {
        correct++;
      }
    });

    return Math.round((correct / questions.length) * 100);
  };

  const submitCompletion = (score = null) => {
    const finalScore = score || performanceScore || calculateQuizScore() || 85;

    setSubmitting(true);
    router.post(route('growth.refresher.submit', refresher.id), {
      performance_score: finalScore,
      responses: responses
    }, {
      onFinish: () => setSubmitting(false)
    });
  };

  const getDifficultyColor = () => {
    switch (refresher.difficulty) {
      case 'easy': return 'text-green-400 border-green-500/30';
      case 'medium': return 'text-yellow-400 border-yellow-500/30';
      case 'hard': return 'text-red-400 border-red-500/30';
      default: return 'text-neutral-400 border-neutral-500/30';
    }
  };

  const renderQuizContent = () => {
    const questions = content.questions || [];

    return (
      <div className="space-y-6">
        {questions.map((question, index) => (
          <div key={index} className="cyber-border bg-card/50 p-6">
            <div className="text-sm text-blue-400 font-mono uppercase tracking-wider mb-3">
              question {index + 1}
            </div>
            <div className="text-foreground mb-4">{question.question}</div>

            <div className="space-y-2">
              {question.options?.map((option, optionIndex) => (
                <button
                  key={optionIndex}
                  onClick={() => handleQuizResponse(index, option)}
                  className={`w-full text-left p-3 rounded cyber-border transition-all duration-200 ${
                    responses[index] === option
                      ? 'border-blue-500/50 bg-blue-500/10'
                      : 'border-neutral-500/30 hover:border-neutral-400/50'
                  }`}
                >
                  <span className="text-sm text-foreground">{option}</span>
                </button>
              ))}
            </div>
          </div>
        ))}

        <div className="text-center">
          <button
            onClick={() => {
              const score = calculateQuizScore();
              setPerformanceScore(score);
              setCompleted(true);
            }}
            disabled={Object.keys(responses).length !== questions.length}
            className="cyber-button px-8 py-3 text-blue-600 dark:text-blue-300 font-mono uppercase tracking-wider text-sm disabled:opacity-50"
          >
            submit quiz
          </button>
        </div>

        {completed && (
          <div className="cyber-border bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 p-6 text-center">
            <div className="text-2xl font-bold text-emerald-400 mb-2">{performanceScore}%</div>
            <div className="text-sm text-muted-foreground font-mono mb-4">quiz completed</div>
            <button
              onClick={() => submitCompletion()}
              disabled={submitting}
              className="cyber-button px-8 py-3 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm"
            >
              {submitting ? 'saving...' : 'finish'}
            </button>
          </div>
        )}
      </div>
    );
  };

  const renderReviewContent = () => (
    <div className="space-y-6">
      <div className="cyber-border bg-card/50 p-6">
        <div className="text-sm text-emerald-400 font-mono uppercase tracking-wider mb-3">summary</div>
        <div className="text-foreground">{content.summary}</div>
      </div>

      {content.key_points && (
        <div className="cyber-border bg-card/50 p-6">
          <div className="text-sm text-purple-400 font-mono uppercase tracking-wider mb-3">key points</div>
          <ul className="space-y-2">
            {content.key_points.map((point, index) => (
              <li key={index} className="flex items-start gap-3">
                <div className="w-1.5 h-1.5 bg-purple-400 rounded-full mt-2 flex-shrink-0"></div>
                <span className="text-muted-foreground">{point}</span>
              </li>
            ))}
          </ul>
        </div>
      )}

      <div className="text-center">
        <div className="space-y-4">
          <div className="text-sm text-muted-foreground font-mono">rate your understanding</div>
          <div className="flex justify-center gap-2">
            {[1, 2, 3, 4, 5].map((rating) => (
              <button
                key={rating}
                onClick={() => setPerformanceScore(rating * 20)}
                className={`w-12 h-12 rounded cyber-border font-mono text-sm transition-all duration-200 ${
                  performanceScore === rating * 20
                    ? 'border-emerald-500/50 bg-emerald-500/20 text-emerald-400'
                    : 'border-neutral-500/30 hover:border-neutral-400/50 text-muted-foreground'
                }`}
              >
                {rating}
              </button>
            ))}
          </div>

          <button
            onClick={() => submitCompletion()}
            disabled={!performanceScore || submitting}
            className="cyber-button px-8 py-3 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm disabled:opacity-50"
          >
            {submitting ? 'saving...' : 'complete review'}
          </button>
        </div>
      </div>
    </div>
  );

  const renderDrillContent = () => (
    <div className="space-y-6">
      <div className="cyber-border bg-card/50 p-6">
        <div className="text-sm text-orange-400 font-mono uppercase tracking-wider mb-3">scenario</div>
        <div className="text-foreground">{content.scenario}</div>
      </div>

      {content.steps && (
        <div className="cyber-border bg-card/50 p-6">
          <div className="text-sm text-cyan-400 font-mono uppercase tracking-wider mb-3">practice steps</div>
          <div className="space-y-3">
            {content.steps.map((step, index) => (
              <div key={index} className="flex items-start gap-3">
                <div className="w-6 h-6 rounded-full bg-cyan-500/20 text-cyan-400 text-xs font-mono flex items-center justify-center flex-shrink-0 mt-0.5">
                  {index + 1}
                </div>
                <span className="text-muted-foreground">{step}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      <div className="text-center">
        <div className="space-y-4">
          <div className="text-sm text-muted-foreground font-mono">rate your performance</div>
          <div className="flex justify-center gap-2">
            {[1, 2, 3, 4, 5].map((rating) => (
              <button
                key={rating}
                onClick={() => setPerformanceScore(rating * 20)}
                className={`w-12 h-12 rounded cyber-border font-mono text-sm transition-all duration-200 ${
                  performanceScore === rating * 20
                    ? 'border-emerald-500/50 bg-emerald-500/20 text-emerald-400'
                    : 'border-neutral-500/30 hover:border-neutral-400/50 text-muted-foreground'
                }`}
              >
                {rating}
              </button>
            ))}
          </div>

          <button
            onClick={() => submitCompletion()}
            disabled={!performanceScore || submitting}
            className="cyber-button px-8 py-3 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm disabled:opacity-50"
          >
            {submitting ? 'saving...' : 'complete drill'}
          </button>
        </div>
      </div>
    </div>
  );

  return (
    <>
      <Head title="Refresher Case" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="max-w-4xl mx-auto space-y-8">
          {/* Header */}
          <div className="text-center space-y-4">
            <div className="flex items-center justify-center gap-3 mb-2">
              <div className="w-8 h-0.5 bg-gradient-to-r from-purple-400 to-pink-400"></div>
              <span className="text-xs text-purple-500 font-mono uppercase tracking-wider">refresher case</span>
              <div className="w-8 h-0.5 bg-gradient-to-l from-purple-400 to-pink-400"></div>
            </div>
            <h2 className="text-2xl font-medium lowercase glow-text text-foreground">
              {content.type || refresher.content_type.replace('_', ' ')}
            </h2>
          </div>

          {/* Case Info */}
          <div className={`cyber-border bg-gradient-to-br from-purple-500/10 to-purple-600/5 border-purple-500/30 p-6`}>
            <div className="flex items-center justify-between mb-4">
              <div className="text-sm text-purple-400 font-mono uppercase tracking-wider">
                {refresher.content_type.replace('_', ' ')}
              </div>
              <div className="flex items-center gap-4 text-xs font-mono">
                <span className={`px-2 py-1 rounded-sm ${getDifficultyColor()} bg-opacity-20`}>
                  {refresher.difficulty}
                </span>
                <span className="text-muted-foreground">
                  {content.estimated_time || '10 minutes'}
                </span>
                {refresher.osce_case && (
                  <span className="text-muted-foreground">{refresher.osce_case.title}</span>
                )}
              </div>
            </div>
          </div>

          {/* Content based on type */}
          {content.type === 'quiz' && renderQuizContent()}
          {content.type === 'review' && renderReviewContent()}
          {content.type === 'drill' && renderDrillContent()}

          {/* Info Footer */}
          <div className="cyber-border bg-card/20 p-6 border-dashed border-muted-foreground/20">
            <div className="text-center space-y-2">
              <div className="text-sm text-muted-foreground font-mono">adaptive learning</div>
              <div className="text-xs text-muted-foreground/60">
                your performance influences future refresher frequency and difficulty
              </div>
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}