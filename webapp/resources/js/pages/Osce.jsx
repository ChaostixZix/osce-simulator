import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
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

  const breadcrumbs = [{ title: 'OSCE', href: route('osce') }];

  return (
    <>
      <Head title="OSCE" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-8">
          {/* Welcome header */}
          <div className="clean-card bg-card p-8 text-center space-y-5 hover:shadow-sm transition-all duration-300 relative overflow-hidden">
            <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" aria-hidden="true"></div>
            <div className="flex justify-center">
              <span className="rounded-full bg-primary/10 text-primary px-3 py-1 text-xs font-medium uppercase tracking-wide">
                immersive osce workspace
              </span>
            </div>
            <div className="space-y-3">
              <h1 className="text-2xl font-semibold text-foreground">
                Welcome back, {user?.name || 'clinician'}
              </h1>
              <p className="text-muted-foreground max-w-2xl mx-auto">
                Launch a simulated encounter, refine your clinical instincts, and continue your progression through the futuristic OSCE program.
              </p>
            </div>
            <div className="flex flex-wrap items-center justify-center gap-3">
              <Link href={route('dashboard')} className="clean-button px-4 py-2 text-sm">
                Back to dashboard
              </Link>
              <div className="clean-card bg-background px-4 py-2 text-sm text-muted-foreground flex items-center gap-2">
                <span className="h-2 w-2 rounded-full bg-primary animate-pulse" aria-hidden="true"></span>
                <span>{cases.length} active cases • {userSessions.length} recent sessions</span>
              </div>
            </div>
          </div>

          {/* Available cases */}
          <div className="space-y-6">
            <div className="border-b border-border pb-3 mb-3">
              <h2 className="text-lg font-medium text-foreground">Available cases</h2>
              <p className="text-sm text-muted-foreground">
                Choose a scenario to enter the AI-driven medical simulator. Each case adapts to your pace and decision-making style.
              </p>
            </div>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              {cases.map((c) => (
                <div
                  key={c.id}
                  className="clean-card bg-card p-6 border border-border/60 hover:shadow-sm transition-all duration-200 space-y-4"
                >
                  <div className="flex items-start justify-between">
                    <div className="space-y-1">
                      <p className="text-xs uppercase tracking-wide text-muted-foreground">Case #{c.id}</p>
                      <h3 className="text-lg font-medium text-foreground">{c.title}</h3>
                    </div>
                    <div className="text-right text-xs text-muted-foreground space-y-1">
                      {c.duration_minutes != null && <p>~{c.duration_minutes} min</p>}
                      {c.difficulty && <p className="capitalize">{c.difficulty}</p>}
                    </div>
                  </div>

                  <p className="text-sm text-muted-foreground leading-relaxed">
                    {c.chief_complaint}
                  </p>

                  <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span className="capitalize">{c.clinical_setting || 'adaptive setting'}</span>
                    <span>{c.urgency_level ? `Urgency: ${c.urgency_level}` : 'Dynamic triage enabled'}</span>
                  </div>

                  <button
                    onClick={() => startSession(c.id)}
                    disabled={startingId === c.id}
                    className="clean-button primary w-full px-4 py-2 text-sm flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    {startingId === c.id ? (
                      <>
                        <span className="h-4 w-4 rounded-full border-2 border-primary/60 border-t-transparent animate-spin" aria-hidden="true"></span>
                        <span>Preparing session…</span>
                      </>
                    ) : (
                      <>
                        <span>Enter simulation</span>
                      </>
                    )}
                  </button>
                </div>
              ))}

              {cases.length === 0 && (
                <div className="clean-card bg-card p-8 text-center border border-dashed border-border/60 col-span-full">
                  <div className="text-3xl mb-3" aria-hidden="true">
                    ⏳
                  </div>
                  <p className="text-muted-foreground">
                    No active cases available right now. Administrators will deploy new simulations soon.
                  </p>
                </div>
              )}
            </div>
          </div>

          {/* Recent sessions */}
          <div className="space-y-6">
            <div className="border-b border-border pb-3 mb-3">
              <h2 className="text-lg font-medium text-foreground">Recent sessions</h2>
              <p className="text-sm text-muted-foreground">
                Rejoin an in-progress encounter, rationalize your findings, or review completed assessments.
              </p>
            </div>

            <div className="space-y-4">
              {userSessions.map((s) => (
                <div
                  key={s.id}
                  className="clean-card bg-card p-5 border border-border/60 hover:shadow-sm transition-all duration-200"
                >
                  <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div className="space-y-2">
                      <div className="flex flex-wrap items-center gap-3">
                        <span className="text-base font-medium text-foreground">{s.osce_case?.title}</span>
                        <SessionStatusBadge session={s} />
                        <span className="text-xs uppercase tracking-wide text-muted-foreground">
                          Session #{s.id}
                        </span>
                      </div>
                      <p className="text-sm text-muted-foreground">
                        Created {new Date(s.created_at).toLocaleDateString()} •{' '}
                        {s.status === 'active' ? 'Live simulation' : `Status: ${s.status.replace(/_/g, ' ')}`}
                      </p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                      {s.status !== 'completed' ? (
                        <Link className="clean-button primary px-4 py-2 text-sm" href={route('osce.chat', s.id)}>
                          Resume session
                        </Link>
                      ) : s.canRationalize ? (
                        <Link className="clean-button px-4 py-2 text-sm" href={route('osce.rationalization.show', s.id)}>
                          Rationalize findings
                        </Link>
                      ) : s.canViewResults ? (
                        <Link className="clean-button px-4 py-2 text-sm" href={route('osce.results.show', s.id)}>
                          View results
                        </Link>
                      ) : null}
                    </div>
                  </div>
                </div>
              ))}

              {userSessions.length === 0 && (
                <div className="clean-card bg-card p-6 text-center border border-dashed border-border/60">
                  <div className="text-3xl mb-2" aria-hidden="true">
                    📝
                  </div>
                  <p className="text-muted-foreground">
                    You have no recorded sessions yet. Start a case to generate your first activity timeline.
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}
