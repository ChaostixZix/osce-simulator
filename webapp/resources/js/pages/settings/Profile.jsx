import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Profile({ auth, status }) {
  const { data, setData, patch, processing } = useForm({ name: auth?.user?.name || '' });

  const submit = (e) => {
    e.preventDefault();
    patch('/settings/profile');
  };

  const breadcrumbs = [
    { title: 'Settings', href: '/settings' },
    { title: 'Profile', href: '/settings/profile' },
  ];

  return (
    <>
      <Head title="Profile" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <form onSubmit={submit} className="space-y-3">
          <div>
            <label className="block text-sm mb-1">Name</label>
            <input className="border px-3 py-2 w-full" value={data.name} onChange={(e) => setData('name', e.target.value)} />
          </div>
          <button className="px-4 py-2 border" disabled={processing} type="submit">Save</button>
          {status && <div className="text-xs text-muted-foreground">{status}</div>}
        </form>
      </AppLayout>
    </>
  );
}

