import React from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function OsceRationalization({ session }) {
  const breadcrumbs = [
    { title: 'OSCE', href: '/osce' },
    { title: 'Rationalization', href: '#' },
  ];

  const complete = () => {
    router.post(`/api/osce/sessions/${session.id}/rationalization/complete`);
  };

  return (
    <>
      <Head title="Rationalization" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-4">
          <div className="text-lg font-semibold">{session?.case?.title}</div>
          <div className="text-sm text-muted-foreground">Reflect on your decisions, then complete to unlock results.</div>
          <button className="px-4 py-2 border" onClick={complete}>Complete Rationalization</button>
        </div>
      </AppLayout>
    </>
  );
}

