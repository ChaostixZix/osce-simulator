import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function OsceResult({ session, isAssessed = true, canReassess = false, assessmentData = null, error = null }) {
  const breadcrumbs = [
    { title: 'OSCE', href: '/osce' },
    { title: 'Result', href: '#' },
  ];

  return (
    <>
      <Head title="OSCE Result" />
      <AppLayout breadcrumbs={breadcrumbs}>
        {!isAssessed && (
          <div className="text-sm text-muted-foreground">
            {error || 'This session has not been assessed yet.'}
          </div>
        )}

        {isAssessed && assessmentData && (
          <div className="space-y-4">
            <div>
              <div className="text-lg font-semibold">Score</div>
              <div className="text-2xl">{assessmentData.score} / {assessmentData.max_score}</div>
              <div className="text-sm text-muted-foreground">Assessed at: {new Date(assessmentData.assessed_at).toLocaleString('id-ID')}</div>
            </div>
            <div>
              <div className="text-lg font-semibold mb-2">Areas</div>
              <div className="grid md:grid-cols-2 gap-2">
                {(assessmentData.areas || []).map((a, idx) => (
                  <div key={idx} className="border p-3">
                    <div className="font-medium">{a.area}</div>
                    <div className="text-sm text-muted-foreground">{a.badge_text} — {a.score}/{a.max_score}</div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        <div className="mt-4">
          <Link href="/osce" className="text-sm text-primary">Back to OSCE</Link>
        </div>
      </AppLayout>
    </>
  );
}

