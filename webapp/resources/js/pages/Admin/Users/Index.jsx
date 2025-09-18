import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Index({ users = [] }) {
    const breadcrumbs = [
        { title: 'dashboard', href: route('dashboard') },
        { title: 'admin', href: route('admin.users.index') },
    ];

    const toggleAdmin = (user) => {
        router.put(route('admin.users.toggle-admin', user.id), {}, {
            preserveScroll: true,
        });
    };

    const toggleBan = (user) => {
        const action = user.is_banned ? 'Unban' : 'Ban';
        if (!confirm(`${action} ${user.name}?`)) {
            return;
        }

        router.put(route('admin.users.toggle-ban', user.id), {}, {
            preserveScroll: true,
        });
    };

    const formatLastActive = (value) => {
        if (!value) {
            return '—';
        }
        try {
            return new Date(value).toLocaleString();
        } catch (error) {
            return value;
        }
    };

    return (
        <>
            <Head title="Admin · Users" />

            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="space-y-6">
                    <div className="text-center space-y-2 mb-6">
                        <h1 className="text-2xl font-semibold text-foreground">Admin · Users</h1>
                        <p className="text-muted-foreground">Monitor activity, promote admins, and ban accounts when needed.</p>
                    </div>

                    <div className="clean-card bg-card p-6 space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-lg font-medium text-foreground">Active Members</h2>
                                <p className="text-sm text-muted-foreground">{users.length} registered users.</p>
                            </div>
                            <Link href={route('admin.osce-cases.index')} className="clean-button px-4 py-2">
                                Manage Cases
                            </Link>
                        </div>

                        <div className="grid gap-3">
                            {users.length === 0 && (
                                <div className="clean-card bg-background p-6 text-center text-muted-foreground">
                                    No users found.
                                </div>
                            )}

                            {users.map((user) => (
                                <div
                                    key={user.id}
                                    className="clean-card bg-background p-4 hover:shadow-sm transition-all duration-200"
                                >
                                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div className="space-y-1">
                                            <h3 className="text-lg font-medium text-foreground">{user.name}</h3>
                                            <p className="text-sm text-muted-foreground">{user.email}</p>
                                            <div className="flex flex-wrap items-center gap-3 text-sm">
                                                <span className={user.is_active ? 'text-emerald-600' : 'text-muted-foreground'}>
                                                    {user.is_active ? 'Active now' : 'Offline'}
                                                </span>
                                                <span className="text-muted-foreground">Last seen: {formatLastActive(user.last_active_at)}</span>
                                                <span className={user.is_admin ? 'text-foreground' : 'text-muted-foreground'}>
                                                    {user.is_admin ? 'Admin' : 'Standard'}
                                                </span>
                                                {user.is_banned && <span className="text-red-500">Banned</span>}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <button
                                                type="button"
                                                className="clean-button px-4 py-2 text-sm"
                                                onClick={() => toggleAdmin(user)}
                                            >
                                                {user.is_admin ? 'Remove Admin' : 'Make Admin'}
                                            </button>
                                            <button
                                                type="button"
                                                className="clean-button px-4 py-2 text-sm"
                                                onClick={() => toggleBan(user)}
                                            >
                                                {user.is_banned ? 'Unban' : 'Ban'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
