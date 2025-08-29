import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@vibe-kanban/ui-kit';

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

  const breadcrumbs = [{ title: 'OSCE', href: route('osce') }];

  return (
    <>
      <Head title="OSCE" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-6">
          <div className="flex items-center justify-between">
            <h2 className="text-xl font-semibold">Welcome, {user?.name || 'Student'}</h2>
            <Link href={route('dashboard')} className="text-sm text-muted-foreground">Back to Dashboard</Link>
          </div>

          <div>
            <h3 className="text-lg font-semibold mb-3">Available Cases</h3>
            <div className="grid md:grid-cols-3 gap-4">
              {cases.map((c) => (
                <div key={c.id} className="border p-4 bg-card text-card-foreground">
                  <div className="font-medium mb-1">{c.title}</div>
                  <div className="text-sm text-muted-foreground mb-2">{c.chief_complaint}</div>
                  <Button onClick={() => startSession(c.id)} disabled={startingId === c.id}>
                    {startingId === c.id ? 'Starting…' : 'Start Session'}
                  </Button>
                </div>
              ))}
              {cases.length === 0 && (
                <div className="text-sm text-muted-foreground">No active cases.</div>
              )}
            </div>
          </div>

          <div>
            <h3 className="text-lg font-semibold mb-3">Recent Sessions</h3>
            <div className="space-y-2">
              {userSessions.map((s) => (
                <div key={s.id} className="border p-3 flex items-center justify-between">
                  <div>
                    <div className="font-medium">{s.osce_case?.title}</div>
                    <div className="text-xs text-muted-foreground">Status: {s.status}</div>
                  </div>
                  <div className="flex gap-2">
                    {s.status !== 'completed' ? (
                      <Link className="text-sm text-primary" href={route('osce.chat', s.id)}>Resume</Link>
                    ) : s.canRationalize ? (
                      <Link className="text-sm text-primary" href={route('osce.rationalization.show', s.id)}>Rationalize</Link>
                    ) : s.canViewResults ? (
                      <Link className="text-sm text-primary" href={route('osce.results.show', s.id)}>View Results</Link>
                    ) : null}
                  </div>
                </div>
              ))}
              {userSessions.length === 0 && (
                <div className="text-sm text-muted-foreground">No recent sessions.</div>
              )}
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}
