import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@vibe-kanban/ui-kit';

export default function ReviewCard({ card }) {
  const [showAnswer, setShowAnswer] = useState(false);
  const [selectedQuality, setSelectedQuality] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  const breadcrumbs = [
    { title: 'growth', href: route('growth.dashboard') },
    { title: 'review card', href: route('growth.card.review', card.id) }
  ];

  const qualityOptions = [
    { value: 0, label: 'Complete blackout', description: 'No recollection' },
    { value: 1, label: 'Incorrect response', description: 'Wrong answer given' },
    { value: 2, label: 'Incorrect with hint', description: 'Correct after thinking hard' },
    { value: 3, label: 'Correct with difficulty', description: 'Remembered with effort' },
    { value: 4, label: 'Correct after hesitation', description: 'Easy to remember' },
    { value: 5, label: 'Perfect response', description: 'Immediate correct answer' }
  ];

  const submitReview = () => {
    if (selectedQuality === null) return;

    setSubmitting(true);
    router.post(route('growth.card.review.submit', card.id), {
      quality: selectedQuality
    }, {
      onFinish: () => setSubmitting(false)
    });
  };

  const getQualityColor = (quality) => {
    if (quality <= 1) return 'text-red-400 border-red-500/30';
    if (quality <= 2) return 'text-orange-400 border-orange-500/30';
    if (quality <= 3) return 'text-yellow-400 border-yellow-500/30';
    if (quality <= 4) return 'text-blue-400 border-blue-500/30';
    return 'text-emerald-400 border-emerald-500/30';
  };

  return (
    <>
      <Head title="Review Card" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="max-w-4xl mx-auto space-y-8">
          {/* Header */}
          <div className="text-center space-y-4">
            <div className="flex items-center justify-center gap-3 mb-2">
              <div className="w-8 h-0.5 bg-gradient-to-r from-blue-400 to-cyan-400"></div>
              <span className="text-xs text-blue-500 font-mono uppercase tracking-wider">spaced repetition</span>
              <div className="w-8 h-0.5 bg-gradient-to-l from-blue-400 to-cyan-400"></div>
            </div>
            <h2 className="text-2xl font-medium lowercase glow-text text-foreground">card review</h2>
          </div>

          {/* Card Info */}
          <div className="cyber-border bg-gradient-to-br from-blue-500/10 to-blue-600/5 border-blue-500/30 p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="text-sm text-blue-400 font-mono uppercase tracking-wider">
                {card.clinical_area}
              </div>
              <div className="flex items-center gap-4 text-xs text-muted-foreground font-mono">
                <span>level {card.repetition_level}</span>
                <span>reviews: {card.review_count}</span>
                {card.osce_case && <span>{card.osce_case.title}</span>}
              </div>
            </div>

            {/* Question */}
            <div className="space-y-4">
              <div className="cyber-border bg-card/50 p-6">
                <div className="text-sm text-emerald-400 font-mono uppercase tracking-wider mb-3">question</div>
                <div className="text-foreground text-lg">
                  {card.card_content?.question || 'No question available'}
                </div>
              </div>

              {/* Show Answer Button */}
              {!showAnswer && (
                <div className="text-center">
                  <button
                    onClick={() => setShowAnswer(true)}
                    className="cyber-button px-8 py-3 text-blue-600 dark:text-blue-300 font-mono uppercase tracking-wider text-sm"
                  >
                    show answer
                  </button>
                </div>
              )}

              {/* Answer */}
              {showAnswer && (
                <div className="space-y-4">
                  <div className="cyber-border bg-card/50 p-6">
                    <div className="text-sm text-emerald-400 font-mono uppercase tracking-wider mb-3">answer</div>
                    <div className="text-foreground">
                      {card.card_content?.answer || 'No answer available'}
                    </div>
                  </div>

                  {/* Explanation */}
                  {card.card_content?.explanation && (
                    <div className="cyber-border bg-card/50 p-6">
                      <div className="text-sm text-purple-400 font-mono uppercase tracking-wider mb-3">explanation</div>
                      <div className="text-muted-foreground">
                        {card.card_content.explanation}
                      </div>
                    </div>
                  )}

                  {/* Tags */}
                  {card.card_content?.tags && card.card_content.tags.length > 0 && (
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="text-xs text-muted-foreground font-mono">tags:</span>
                      {card.card_content.tags.map((tag, index) => (
                        <span
                          key={index}
                          className="px-2 py-1 text-xs bg-muted/50 text-muted-foreground rounded-sm font-mono"
                        >
                          {tag}
                        </span>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* Quality Assessment */}
          {showAnswer && (
            <div className="cyber-border bg-gradient-to-br from-neutral-500/10 to-neutral-600/5 border-neutral-500/30 p-6">
              <div className="text-sm text-emerald-400 font-mono uppercase tracking-wider mb-6">how well did you remember?</div>

              <div className="grid md:grid-cols-2 gap-3 mb-6">
                {qualityOptions.map((option) => (
                  <button
                    key={option.value}
                    onClick={() => setSelectedQuality(option.value)}
                    className={`cyber-border p-4 text-left transition-all duration-200 hover:scale-[1.02] ${
                      selectedQuality === option.value
                        ? `${getQualityColor(option.value)} bg-opacity-20`
                        : 'border-neutral-500/30 hover:border-neutral-400/50'
                    }`}
                  >
                    <div className={`font-medium ${selectedQuality === option.value ? getQualityColor(option.value).split(' ')[0] : 'text-foreground'}`}>
                      {option.label}
                    </div>
                    <div className="text-xs text-muted-foreground mt-1 font-mono">
                      {option.description}
                    </div>
                  </button>
                ))}
              </div>

              <div className="text-center">
                <button
                  onClick={submitReview}
                  disabled={selectedQuality === null || submitting}
                  className="cyber-button px-8 py-3 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {submitting ? (
                    <div className="flex items-center gap-2">
                      <div className="w-4 h-4 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin"></div>
                      <span>submitting…</span>
                    </div>
                  ) : (
                    'submit review'
                  )}
                </button>
              </div>
            </div>
          )}

          {/* Spaced Repetition Info */}
          <div className="cyber-border bg-card/20 p-6 border-dashed border-muted-foreground/20">
            <div className="text-center space-y-2">
              <div className="text-sm text-muted-foreground font-mono">spaced repetition algorithm</div>
              <div className="text-xs text-muted-foreground/60">
                your rating determines when this card appears next • higher ratings = longer intervals
              </div>
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}