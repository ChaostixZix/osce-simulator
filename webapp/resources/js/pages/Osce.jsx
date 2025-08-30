import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@vibe-kanban/ui-kit';
import SessionStatusBadge from '@/components/SessionStatusBadge';

export default function Osce({ cases = [], userSessions = [], user }) {
  const [startingId, setStartingId] = useState(null);

  const startSession = (osce_case_id) => {
    setStartingId(osce_case_id);
    // Use Ziggy-named route to avoid hard-coded paths
    router.post(route('osce.sessions.start'), { osce_case_id }, {
      preserveScroll: true,
      onFinish: () => setStartingId(null),
    });
  };

  const breadcrumbs = [{ title: 'osce', href: route('osce') }];

  return (
    <>
      <Head title="osce" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-6 text-neutral-300">
          <div className="flex items-center justify-between">
            <h2 className="text-xl font-medium lowercase">welcome, {user?.name || 'student'}</h2>
            <Link href={route('dashboard')} className="text-sm text-neutral-500 hover:text-neutral-300 lowercase">back to dashboard</Link>
          </div>

          <div>
            <h3 className="text-lg font-medium mb-3 lowercase">available cases</h3>
            <div className="grid md:grid-cols-3 gap-3">
              {cases.map((c) => (
                <div key={c.id} className="border border-neutral-800 p-4 bg-neutral-900/60 text-neutral-300">
                  <div className="font-medium mb-1 lowercase">{c.title}</div>
                  <div className="text-sm text-neutral-500 mb-3 lowercase">{c.chief_complaint}</div>
                  <Button
                    onClick={() => startSession(c.id)}
                    disabled={startingId === c.id}
                    className="px-4 py-2 bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/15"
                  >
                    {startingId === c.id ? 'starting…' : 'start session'}
                  </Button>
                </div>
              ))}
              {cases.length === 0 && (
                <div className="text-sm text-neutral-500 lowercase">no active cases.</div>
              )}
            </div>
          </div>

          <div>
            <h3 className="text-lg font-medium mb-3 lowercase">recent sessions</h3>
            <div className="space-y-2">
              {userSessions.map((s) => (
                <div key={s.id} className="border border-neutral-800 bg-neutral-900/50 p-3 flex items-center justify-between">
                  <div>
                    <div className="flex items-center">
                      <div className="font-medium lowercase">{s.osce_case?.title}</div>
                      <SessionStatusBadge session={s} />
                    </div>
                    <div className="text-xs text-neutral-500 lowercase">status: {s.status}</div>
                  </div>
                  <div className="flex gap-2">
                    {s.status !== 'completed' ? (
                      <Link className="text-sm text-emerald-400 hover:text-emerald-300 lowercase" href={route('osce.chat', s.id)}>resume</Link>
                    ) : s.canRationalize ? (
                      <Link className="text-sm text-emerald-400 hover:text-emerald-300 lowercase" href={route('osce.rationalization.show', s.id)}>rationalize</Link>
                    ) : s.canViewResults ? (
                      <Link className="text-sm text-emerald-400 hover:text-emerald-300 lowercase" href={route('osce.results.show', s.id)}>view results</Link>
                    ) : null}
                  </div>
                </div>
              ))}
              {userSessions.length === 0 && (
                <div className="text-sm text-neutral-500 lowercase">no recent sessions.</div>
              )}
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}
