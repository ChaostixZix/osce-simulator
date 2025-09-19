import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@vibe-kanban/ui-kit';
import SessionStatusBadge from '@/components/SessionStatusBadge';

export default function Osce({ cases = [], userSessions = [], user }) {
  const [startingId, setStartingId] = useState(null);

  const startSession = (osce_case_id) => {
    setStartingId(osce_case_id);
    // Redirect to onboarding first
    router.visit(route('onboarding.show', osce_case_id), {
      onFinish: () => setStartingId(null),
    });
  };

  const breadcrumbs = [{ title: 'osce', href: route('osce') }];

  return (
    <>
      <Head title="osce" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-8">
          {/* Enhanced Welcome Header */}
          <div className="text-center space-y-4 relative">
            <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-20 h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent"></div>
            
            <div className="flex items-center justify-center gap-3 mb-2">
              <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
              <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">training interface</span>
              <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400"></div>
            </div>
            
            <h2 className="text-2xl font-medium lowercase glow-text text-foreground">welcome, {user?.name || 'student'}</h2>
            
            <div className="flex items-center justify-center gap-4 text-sm">
              <Link href={route('dashboard')} className="cyber-button px-4 py-2 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wide text-xs">
                back to dashboard
              </Link>
            </div>
          </div>

          {/* Enhanced Available Cases */}
          <div className="space-y-6">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
              <h3 className="text-lg font-medium lowercase text-foreground font-mono">available cases</h3>
              <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
              <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                <span>{cases.length} active</span>
              </div>
            </div>
            
            <div className="grid md:grid-cols-3 gap-6">
              {cases.map((c, idx) => {
                const colors = [
                  { bg: 'bg-gradient-to-br from-emerald-500/10 to-emerald-600/5', border: 'border-emerald-500/30', accent: 'text-emerald-400' },
                  { bg: 'bg-gradient-to-br from-blue-500/10 to-blue-600/5', border: 'border-blue-500/30', accent: 'text-blue-400' },
                  { bg: 'bg-gradient-to-br from-purple-500/10 to-purple-600/5', border: 'border-purple-500/30', accent: 'text-purple-400' }
                ];
                const cardColor = colors[idx % colors.length];
                
                return (
                  <div key={c.id} className={`cyber-border ${cardColor.bg} ${cardColor.border} p-6 group relative overflow-hidden hover:scale-[1.02] transition-all duration-300`}>
                    {/* Decorative elements */}
                    <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>
                    <div className="absolute bottom-2 left-2 w-3 h-3 border-b-2 border-l-2 border-emerald-400/40 opacity-60"></div>
                    
                    <div className="relative z-10">
                      <div className="flex items-start justify-between mb-3">
                        <div className={`text-xs ${cardColor.accent} font-mono uppercase tracking-wider mb-1`}>case #{c.id}</div>
                        <div className="text-lg">🩺</div>
                      </div>
                      
                      <div className="font-semibold mb-2 lowercase text-foreground text-lg">{c.title}</div>
                      <div className="text-sm text-muted-foreground mb-4 lowercase leading-relaxed">{c.chief_complaint}</div>
                      
                      <button
                        onClick={() => startSession(c.id)}
                        disabled={startingId === c.id}
                        className="cyber-button w-full px-4 py-3 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm relative group/btn"
                      >
                        {startingId === c.id ? (
                          <div className="flex items-center justify-center gap-2">
                            <div className="w-4 h-4 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin"></div>
                            <span>initializing…</span>
                          </div>
                        ) : (
                          <div className="flex items-center justify-center gap-2">
                            <span>start session</span>
                            <span className="group-hover/btn:translate-x-1 transition-transform">▸</span>
                          </div>
                        )}
                      </button>
                    </div>
                    
                    {/* Hover effect line */}
                    <div className="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                  </div>
                );
              })}
              
              {cases.length === 0 && (
                <div className="col-span-full cyber-border bg-card/20 p-8 text-center border-dashed border-muted-foreground/20">
                  <div className="text-4xl mb-4">⏳</div>
                  <div className="text-muted-foreground lowercase font-mono">no active cases available</div>
                  <div className="text-xs text-muted-foreground/60 mt-2">cases will appear here when activated by administrators</div>
                </div>
              )}
            </div>
          </div>

          {/* Enhanced Recent Sessions */}
          <div className="space-y-6">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
              <h3 className="text-lg font-medium lowercase text-foreground font-mono">recent sessions</h3>
              <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
              <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                <span>{userSessions.length} sessions</span>
              </div>
            </div>
            
            <div className="space-y-3">
              {userSessions.map((s, idx) => {
                const statusColors = {
                  'active': { bg: 'from-emerald-500/10', border: 'border-emerald-500/30', text: 'text-emerald-400' },
                  'completed': { bg: 'from-blue-500/10', border: 'border-blue-500/30', text: 'text-blue-400' },
                  'expired': { bg: 'from-red-500/10', border: 'border-red-500/30', text: 'text-red-400' },
                  'default': { bg: 'from-neutral-500/10', border: 'border-neutral-500/30', text: 'text-neutral-400' }
                };
                const statusStyle = statusColors[s.status] || statusColors.default;
                
                return (
                  <div key={s.id} className={`cyber-border bg-gradient-to-br ${statusStyle.bg} to-transparent ${statusStyle.border} p-5 group hover:scale-[1.01] transition-all duration-200`}>
                    <div className="flex items-center justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-3 mb-2">
                          <div className="font-semibold lowercase text-foreground">{s.osce_case?.title}</div>
                          <SessionStatusBadge session={s} />
                          <div className="flex items-center gap-1">
                            <div className={`w-1.5 h-1.5 rounded-full ${statusStyle.text.replace('text-', 'bg-')} animate-pulse`}></div>
                            <span className={`text-xs font-mono uppercase tracking-wider ${statusStyle.text}`}>{s.status}</span>
                          </div>
                        </div>
                        <div className="text-xs text-muted-foreground lowercase font-mono">
                          session #{s.id} • created {new Date(s.created_at).toLocaleDateString()}
                        </div>
                      </div>
                      
                      <div className="flex gap-3">
                        {s.status !== 'completed' ? (
                          <Link 
                            className="cyber-button px-4 py-2 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wide text-xs" 
                            href={route('osce.chat', s.id)}
                          >
                            resume
                          </Link>
                        ) : s.canRationalize ? (
                          <Link 
                            className="cyber-button px-4 py-2 text-blue-600 dark:text-blue-300 font-mono uppercase tracking-wide text-xs" 
                            href={route('osce.rationalization.show', s.id)}
                          >
                            rationalize
                          </Link>
                        ) : s.canViewResults ? (
                          <Link 
                            className="cyber-button px-4 py-2 text-purple-600 dark:text-purple-300 font-mono uppercase tracking-wide text-xs" 
                            href={route('osce.results.show', s.id)}
                          >
                            view results
                          </Link>
                        ) : null}
                      </div>
                    </div>
                  </div>
                );
              })}
              
              {userSessions.length === 0 && (
                <div className="cyber-border bg-card/20 p-6 text-center border-dashed border-muted-foreground/20">
                  <div className="text-3xl mb-3">📝</div>
                  <div className="text-muted-foreground lowercase font-mono">no recent sessions</div>
                  <div className="text-xs text-muted-foreground/60 mt-2">your session history will appear here after starting cases</div>
                </div>
              )}
            </div>
          </div>

          {/* System Status Footer */}
          <div className="flex items-center justify-between text-xs text-muted-foreground font-mono pt-4 border-t border-border/50">
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
              <span>osce system ready</span>
            </div>
            <div className="flex items-center gap-4">
              <span>cases: {cases.length}</span>
              <span>sessions: {userSessions.length}</span>
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}
