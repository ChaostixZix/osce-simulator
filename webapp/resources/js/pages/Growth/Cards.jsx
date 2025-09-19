import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Cards({ cards, dueCount }) {
  const breadcrumbs = [
    { title: 'growth', href: route('growth.dashboard') },
    { title: 'cards', href: route('growth.cards') }
  ];

  const getStatusColor = (card) => {
    const now = new Date();
    const reviewDate = new Date(card.next_review_date);

    if (reviewDate <= now) return 'text-red-400 border-red-500/30';
    if (reviewDate <= new Date(now.getTime() + 24 * 60 * 60 * 1000)) return 'text-yellow-400 border-yellow-500/30';
    return 'text-green-400 border-green-500/30';
  };

  const getStatusText = (card) => {
    const now = new Date();
    const reviewDate = new Date(card.next_review_date);

    if (reviewDate <= now) return 'Due now';
    if (reviewDate <= new Date(now.getTime() + 24 * 60 * 60 * 1000)) return 'Due soon';
    return 'Scheduled';
  };

  return (
    <>
      <Head title="Spaced Repetition Cards" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-8">
          {/* Header */}
          <div className="text-center space-y-4">
            <div className="flex items-center justify-center gap-3 mb-2">
              <div className="w-8 h-0.5 bg-gradient-to-r from-blue-400 to-cyan-400"></div>
              <span className="text-xs text-blue-500 font-mono uppercase tracking-wider">spaced repetition</span>
              <div className="w-8 h-0.5 bg-gradient-to-l from-blue-400 to-cyan-400"></div>
            </div>
            <h2 className="text-2xl font-medium lowercase glow-text text-foreground">review cards</h2>
          </div>

          {/* Stats */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="cyber-border bg-gradient-to-br from-red-500/10 to-red-600/5 border-red-500/30 p-4">
              <div className="text-2xl font-bold text-red-400">{dueCount}</div>
              <div className="text-sm text-muted-foreground font-mono">due now</div>
            </div>

            <div className="cyber-border bg-gradient-to-br from-blue-500/10 to-blue-600/5 border-blue-500/30 p-4">
              <div className="text-2xl font-bold text-blue-400">{cards.total}</div>
              <div className="text-sm text-muted-foreground font-mono">total cards</div>
            </div>

            <div className="cyber-border bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 p-4">
              <div className="text-2xl font-bold text-emerald-400">{cards.total - dueCount}</div>
              <div className="text-sm text-muted-foreground font-mono">completed</div>
            </div>
          </div>

          {/* Cards List */}
          {cards.data.length > 0 ? (
            <div className="space-y-4">
              {cards.data.map((card) => (
                <div key={card.id} className={`cyber-border p-6 group hover:scale-[1.01] transition-all duration-200 ${getStatusColor(card)} bg-gradient-to-br from-current/10 to-current/5`}>
                  <div className="flex items-start justify-between">
                    <div className="flex-1 space-y-3">
                      <div className="flex items-center gap-3">
                        <div className="font-medium text-foreground lowercase">{card.clinical_area}</div>
                        <div className="text-xs text-muted-foreground font-mono">level {card.repetition_level}</div>
                        <div className={`text-xs font-mono px-2 py-1 rounded-sm ${getStatusColor(card)} bg-current/20`}>
                          {getStatusText(card)}
                        </div>
                      </div>

                      <div className="text-sm text-muted-foreground line-clamp-2">
                        {card.card_content?.question || 'Review question'}
                      </div>

                      <div className="flex items-center gap-4 text-xs text-muted-foreground font-mono">
                        {card.osce_case && <span>{card.osce_case.title}</span>}
                        <span>reviews: {card.review_count}</span>
                        <span>next: {new Date(card.next_review_date).toLocaleDateString()}</span>
                      </div>
                    </div>

                    <div className="flex items-center gap-3">
                      <Link
                        href={route('growth.card.review', card.id)}
                        className="cyber-button px-4 py-2 text-blue-600 dark:text-blue-300 font-mono uppercase tracking-wide text-xs"
                      >
                        review
                      </Link>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="cyber-border bg-card/20 p-8 text-center border-dashed border-muted-foreground/20">
              <div className="text-4xl mb-4">📚</div>
              <div className="text-muted-foreground lowercase font-mono mb-2">no cards available</div>
              <div className="text-xs text-muted-foreground/60 mb-4">complete osce sessions to generate spaced repetition cards</div>
              <Link
                href={route('osce')}
                className="cyber-button px-6 py-2 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm"
              >
                start learning
              </Link>
            </div>
          )}

          {/* Pagination */}
          {cards.links && cards.links.length > 3 && (
            <div className="flex justify-center">
              <nav className="flex items-center gap-2">
                {cards.links.map((link, index) => (
                  <Link
                    key={index}
                    href={link.url || '#'}
                    className={`px-3 py-2 text-xs font-mono rounded cyber-border transition-all ${
                      link.active
                        ? 'bg-blue-500/20 border-blue-500/50 text-blue-400'
                        : link.url
                          ? 'hover:bg-muted/50 text-muted-foreground'
                          : 'opacity-50 cursor-not-allowed text-muted-foreground'
                    }`}
                    preserveScroll
                  >
                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                  </Link>
                ))}
              </nav>
            </div>
          )}

          {/* Navigation */}
          <div className="flex items-center justify-center gap-4 pt-4 border-t border-border/50">
            <Link
              href={route('growth.dashboard')}
              className="cyber-button px-4 py-2 text-cyan-600 dark:text-cyan-300 font-mono uppercase tracking-wide text-xs"
            >
              back to dashboard
            </Link>
          </div>
        </div>
      </AppLayout>
    </>
  );
}