import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Index({ cases = [] }) {
    const breadcrumbs = [
        { title: 'dashboard', href: route('dashboard') },
        { title: 'admin', href: route('admin.osce-cases.index') },
    ];

    const handleDelete = (id) => {
        if (!confirm('Delete this OSCE case? This cannot be undone.')) {
            return;
        }

        router.delete(route('admin.osce-cases.destroy', id), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Admin · OSCE Cases" />

            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="space-y-6">
                    <div className="text-center space-y-2 mb-6">
                        <h1 className="text-2xl font-semibold text-foreground">Admin · OSCE Cases</h1>
                        <p className="text-muted-foreground">Review, create, and remove OSCE training cases.</p>
                    </div>

                    <div className="clean-card bg-card p-6 space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-lg font-medium text-foreground">Case Library</h2>
                                <p className="text-sm text-muted-foreground">{cases.length} cases available.</p>
                            </div>
                            <Link
                                href={route('admin.osce-cases.create')}
                                className="clean-button primary px-4 py-2"
                            >
                                Create Case
                            </Link>
                        </div>

                        <div className="space-y-3">
                            {cases.length === 0 && (
                                <div className="clean-card bg-background p-6 text-center text-muted-foreground">
                                    No OSCE cases found yet.
                                </div>
                            )}

                            {cases.map((osceCase) => (
                                <div
                                    key={osceCase.id}
                                    className="clean-card bg-background p-4 hover:shadow-sm transition-all duration-200"
                                >
                                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div className="space-y-1">
                                            <h3 className="text-lg font-medium text-foreground">{osceCase.title}</h3>
                                            <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                                <span className="uppercase tracking-wide">{osceCase.difficulty}</span>
                                                <span>Duration: {osceCase.duration_minutes} min</span>
                                                <span className={osceCase.is_active ? 'text-emerald-600' : 'text-muted-foreground'}>
                                                    {osceCase.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                                <span>Updated {new Date(osceCase.updated_at).toLocaleString()}</span>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Link
                                                href={route('admin.osce-cases.edit', osceCase.id)}
                                                className="clean-button px-4 py-2 text-sm"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                className="clean-button px-4 py-2 text-sm"
                                                onClick={() => handleDelete(osceCase.id)}
                                            >
                                                Delete
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
