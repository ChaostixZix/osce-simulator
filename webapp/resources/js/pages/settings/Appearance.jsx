import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Appearance() {
  const breadcrumbs = [
    { title: 'Settings', href: route('profile.edit') },
    { title: 'Appearance', href: route('appearance') },
  ];

  return (
    <>
      <Head title="Appearance" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-2">
          <div className="text-lg font-semibold">Appearance</div>
          <div className="text-sm text-muted-foreground">Theme controls coming soon.</div>
        </div>
      </AppLayout>
    </>
  );
}
