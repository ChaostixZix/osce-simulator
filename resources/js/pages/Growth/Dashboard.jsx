import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@vibe-kanban/ui-kit';

export default function GrowthDashboard({
  streak,
  milestones,
  dueCards,
  refreshers,
  recentAchievements,
  stats
}) {
  const breadcrumbs = [
    { title: 'dashboard', href: route('dashboard') },
    { title: 'growth', href: route('growth.dashboard') }
  ];

  const getStreakStatus = () => {
    if (!streak) return { text: 'not started', color: 'text-neutral-400' };

    const status = streak.status;
    const colors = {
      current: 'text-emerald-400',
      pending: 'text-yellow-400',
      broken: 'text-red-400'
    };

    return { text: status, color: colors[status] || 'text-neutral-400' };
  };

  const streakStatus = getStreakStatus();

  return (
    <>
      <Head title="Growth Dashboard" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-8">
          {/* Header */}
          <div className="text-center space-y-4">
            <div className="flex items-center justify-center gap-3 mb-2">
              <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
              <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">longitudinal growth</span>
              <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400"></div>
            </div>
            <h2 className="text-2xl font-medium lowercase glow-text text-foreground">learning progress</h2>
          </div>

          {/* Stats Overview */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="cyber-border bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 p-4">
              <div className="text-2xl font-bold text-emerald-400">{stats.current_streak}</div>
              <div className="text-sm text-muted-foreground font-mono">current streak</div>
              <div className={`text-xs ${streakStatus.color} font-mono`}>{streakStatus.text}</div>
            </div>

            <div className="cyber-border bg-gradient-to-br from-blue-500/10 to-blue-600/5 border-blue-500/30 p-4">
              <div className="text-2xl font-bold text-blue-400">{stats.due_cards}</div>
              <div className="text-sm text-muted-foreground font-mono">cards due</div>
              <div className="text-xs text-blue-400 font-mono">of {stats.total_cards} total</div>
            </div>

            <div className="cyber-border bg-gradient-to-br from-purple-500/10 to-purple-600/5 border-purple-500/30 p-4">
              <div className="text-2xl font-bold text-purple-400">{stats.pending_refreshers}</div>
              <div className="text-sm text-muted-foreground font-mono">refreshers ready</div>
              <div className="text-xs text-purple-400 font-mono">{stats.completed_refreshers} completed</div>
            </div>

            <div className="cyber-border bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 border-yellow-500/30 p-4">
              <div className="text-2xl font-bold text-yellow-400">{stats.achievements_this_month}</div>
              <div className="text-sm text-muted-foreground font-mono">achievements</div>
              <div className="text-xs text-yellow-400 font-mono">this month</div>
            </div>
          </div>

          {/* Due Cards Section */}
          {dueCards.length > 0 && (
            <div className="space-y-6">
              <div className="flex items-center gap-3">
                <div className="w-1 h-6 bg-gradient-to-b from-blue-400 to-cyan-400"></div>
                <h3 className="text-lg font-medium lowercase text-foreground font-mono">spaced repetition cards</h3>
                <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                  <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                  <span>{dueCards.length} due now</span>
                </div>
              </div>

              <div className="grid md:grid-cols-2 gap-4">
                {dueCards.slice(0, 4).map((card) => (
                  <div key={card.id} className="cyber-border bg-gradient-to-br from-blue-500/10 to-blue-600/5 border-blue-500/30 p-4 group hover:scale-[1.02] transition-all duration-300">
                    <div className="space-y-3">
                      <div className="flex items-start justify-between">
                        <div className="text-sm font-medium text-foreground lowercase">{card.clinical_area}</div>
                        <div className="text-xs text-blue-400 font-mono">level {card.repetition_level}</div>
                      </div>

                      <div className="text-sm text-muted-foreground line-clamp-2">
                        {card.card_content?.question || 'Review question'}
                      </div>

                      <div className="flex items-center justify-between">
                        <div className="text-xs text-muted-foreground font-mono">
                          {card.osce_case?.title || 'General review'}
                        </div>
                        <Link
                          href={route('growth.card.review', card.id)}
                          className="cyber-button px-3 py-1 text-blue-600 dark:text-blue-300 font-mono uppercase tracking-wide text-xs"
                        >
                          review
                        </Link>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {dueCards.length > 4 && (
                <div className="text-center">
                  <Link
                    href={route('growth.cards')}
                    className="cyber-button px-6 py-2 text-blue-600 dark:text-blue-300 font-mono uppercase tracking-wider text-sm"
                  >
                    view all cards ({dueCards.length})
                  </Link>
                </div>
              )}
            </div>
          )}

          {/* Refresher Cases Section */}
          {refreshers.length > 0 && (
            <div className="space-y-6">
              <div className="flex items-center gap-3">
                <div className="w-1 h-6 bg-gradient-to-b from-purple-400 to-pink-400"></div>
                <h3 className="text-lg font-medium lowercase text-foreground font-mono">refresher cases</h3>
                <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                  <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></div>
                  <span>{refreshers.length} ready</span>
                </div>
              </div>

              <div className="space-y-3">
                {refreshers.map((refresher) => (
                  <div key={refresher.id} className="cyber-border bg-gradient-to-br from-purple-500/10 to-purple-600/5 border-purple-500/30 p-4 group hover:scale-[1.01] transition-all duration-200">
                    <div className="flex items-center justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-3 mb-2">
                          <div className="font-medium lowercase text-foreground">
                            {refresher.content_type.replace('_', ' ')}
                          </div>
                          <div className={`px-2 py-1 text-xs font-mono uppercase tracking-wider rounded-sm
                            ${refresher.difficulty === 'easy' ? 'bg-green-500/20 text-green-400' :
                              refresher.difficulty === 'medium' ? 'bg-yellow-500/20 text-yellow-400' :
                              'bg-red-500/20 text-red-400'}`}>
                            {refresher.difficulty}
                          </div>
                        </div>
                        <div className="text-sm text-muted-foreground lowercase">
                          {refresher.osce_case?.title || 'General practice'}
                        </div>
                      </div>

                      <Link
                        href={route('growth.refresher.show', refresher.id)}
                        className="cyber-button px-4 py-2 text-purple-600 dark:text-purple-300 font-mono uppercase tracking-wide text-xs"
                      >
                        start
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Recent Achievements */}
          {recentAchievements.length > 0 && (
            <div className="space-y-6">
              <div className="flex items-center gap-3">
                <div className="w-1 h-6 bg-gradient-to-b from-yellow-400 to-orange-400"></div>
                <h3 className="text-lg font-medium lowercase text-foreground font-mono">recent achievements</h3>
                <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
              </div>

              <div className="grid md:grid-cols-2 gap-4">
                {recentAchievements.map((achievement) => (
                  <div key={achievement.id} className="cyber-border bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 border-yellow-500/30 p-4">
                    <div className="flex items-start gap-3">
                      <div className="text-2xl">{achievement.badge_icon}</div>
                      <div className="flex-1">
                        <div className="font-medium text-foreground lowercase">{achievement.milestone_title}</div>
                        <div className="text-sm text-muted-foreground">{achievement.milestone_description}</div>
                        <div className="text-xs text-yellow-400 font-mono mt-1">
                          {new Date(achievement.achieved_at).toLocaleDateString()}
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              <div className="text-center">
                <Link
                  href={route('growth.milestones')}
                  className="cyber-button px-6 py-2 text-yellow-600 dark:text-yellow-300 font-mono uppercase tracking-wider text-sm"
                >
                  view all milestones
                </Link>
              </div>
            </div>
          )}

          {/* Empty State */}
          {dueCards.length === 0 && refreshers.length === 0 && recentAchievements.length === 0 && (
            <div className="cyber-border bg-card/20 p-8 text-center border-dashed border-muted-foreground/20">
              <div className="text-4xl mb-4">🌱</div>
              <div className="text-muted-foreground lowercase font-mono mb-2">your growth journey starts here</div>
              <div className="text-xs text-muted-foreground/60 mb-4">complete osce sessions to unlock spaced repetition and progress tracking</div>
              <Link
                href={route('osce')}
                className="cyber-button px-6 py-2 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm"
              >
                start learning
              </Link>
            </div>
          )}

          {/* Navigation */}
          <div className="flex items-center justify-center gap-4 pt-4 border-t border-border/50">
            <Link
              href={route('growth.analytics')}
              className="cyber-button px-4 py-2 text-cyan-600 dark:text-cyan-300 font-mono uppercase tracking-wide text-xs"
            >
              analytics
            </Link>
            <Link
              href={route('growth.milestones')}
              className="cyber-button px-4 py-2 text-yellow-600 dark:text-yellow-300 font-mono uppercase tracking-wide text-xs"
            >
              achievements
            </Link>
          </div>
        </div>
      </AppLayout>
    </>
  );
}