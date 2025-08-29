import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function OsceResults({ session, user }) {
  const breadcrumbs = [
    { title: 'OSCE', href: route('osce') },
    { title: 'Results', href: '#' },
  ];

  return (
    <>
      <Head title="OSCE Results" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-4">
          <div className="text-lg font-semibold">{session?.osceCase?.title}</div>
          <div className="text-sm text-muted-foreground">Completed at: {session?.completed_at ? new Date(session.completed_at).toLocaleString('id-ID') : '-'}</div>

          <div className="grid md:grid-cols-2 gap-3">
            <div className="border p-3">
              <div className="font-medium mb-1">Ordered Tests</div>
              <ul className="text-sm list-disc ml-4">
                {(session?.ordered_tests || session?.orderedTests || []).map((t, idx) => (
                  <li key={idx}>{t.test_name || t.testName} — {t.results?.status || 'pending'}</li>
                ))}
              </ul>
            </div>
            <div className="border p-3">
              <div className="font-medium mb-1">Examinations</div>
              <ul className="text-sm list-disc ml-4">
                {(session?.examinations || []).map((e, idx) => (
                  <li key={idx}>{e.category || e.type}: {e.finding || '-'}</li>
                ))}
              </ul>
            </div>
          </div>

          <div className="flex gap-2">
            <button
              className="px-4 py-2 border"
              onClick={() => router.post(route('osce.assess.trigger', session.id), { force: true }, { preserveScroll: true })}
            >
              Re/Assess Session
            </button>
            <button
              className="px-4 py-2 border"
              onClick={async () => {
                const res = await fetch(`/api/osce/sessions/${session.id}/status`);
                const data = await res.json();
                alert(`Status: ${data.status} (${data.progress ?? 0}%)`);
              }}
            >
              Check Status
            </button>
          </div>

          <div>
            <Link href={route('osce')} className="text-sm text-primary">Back to OSCE</Link>
          </div>
        </div>
      </AppLayout>
    </>
  );
}
