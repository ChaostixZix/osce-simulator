import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Edit({ case: osceCase }) {
    const { data, setData, put, processing, errors } = useForm({
        title: osceCase?.title ?? '',
        description: osceCase?.description ?? '',
        difficulty: osceCase?.difficulty ?? 'medium',
        duration_minutes: osceCase?.duration_minutes ?? 15,
        scenario: osceCase?.scenario ?? '',
        objectives: osceCase?.objectives ?? '',
        is_active: osceCase?.is_active ?? false,
    });

    const breadcrumbs = [
        { title: 'dashboard', href: route('dashboard') },
        { title: 'admin', href: route('admin.osce-cases.index') },
        { title: osceCase?.title ?? 'edit case', href: route('admin.osce-cases.edit', osceCase?.id) },
    ];

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.osce-cases.update', osceCase.id));
    };

    return (
        <>
            <Head title={`Edit · ${osceCase?.title ?? ''}`} />

            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="space-y-6">
                    <div className="text-center space-y-2 mb-6">
                        <h1 className="text-2xl font-semibold text-foreground">Edit OSCE Case</h1>
                        <p className="text-muted-foreground">Update details for this scenario.</p>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        <div className="clean-card bg-card p-6 space-y-5">
                            <div>
                                <h2 className="text-lg font-medium text-foreground">Case Details</h2>
                                <p className="text-sm text-muted-foreground">Keep the information clear and complete.</p>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground" htmlFor="title">
                                        Title
                                    </label>
                                    <input
                                        id="title"
                                        type="text"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        className="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                        required
                                    />
                                    {errors.title && <p className="text-sm text-red-500">{errors.title}</p>}
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground" htmlFor="difficulty">
                                        Difficulty
                                    </label>
                                    <select
                                        id="difficulty"
                                        value={data.difficulty}
                                        onChange={(e) => setData('difficulty', e.target.value)}
                                        className="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                    >
                                        <option value="easy">Easy</option>
                                        <option value="medium">Medium</option>
                                        <option value="hard">Hard</option>
                                    </select>
                                    {errors.difficulty && <p className="text-sm text-red-500">{errors.difficulty}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="description">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                    rows={3}
                                />
                                {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                            </div>
                        </div>

                        <div className="clean-card bg-card p-6 space-y-5">
                            <div>
                                <h2 className="text-lg font-medium text-foreground">Scenario Setup</h2>
                                <p className="text-sm text-muted-foreground">Adjust duration and objectives as needed.</p>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground" htmlFor="duration">
                                        Duration (minutes)
                                    </label>
                                    <input
                                        id="duration"
                                        type="number"
                                        min={1}
                                        max={480}
                                        value={data.duration_minutes}
                                        onChange={(e) => setData('duration_minutes', Number(e.target.value))}
                                        className="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                    />
                                    {errors.duration_minutes && <p className="text-sm text-red-500">{errors.duration_minutes}</p>}
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground" htmlFor="is_active">
                                        Visibility
                                    </label>
                                    <div className="clean-card bg-background p-3 flex items-center justify-between">
                                        <span className="text-sm text-muted-foreground">Make this case available to learners</span>
                                        <input
                                            id="is_active"
                                            type="checkbox"
                                            checked={!!data.is_active}
                                            onChange={(e) => setData('is_active', e.target.checked)}
                                            className="h-4 w-4"
                                        />
                                    </div>
                                    {errors.is_active && <p className="text-sm text-red-500">{errors.is_active}</p>}
                                </div>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground" htmlFor="scenario">
                                        Scenario Overview
                                    </label>
                                    <textarea
                                        id="scenario"
                                        value={data.scenario}
                                        onChange={(e) => setData('scenario', e.target.value)}
                                        className="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                        rows={4}
                                    />
                                    {errors.scenario && <p className="text-sm text-red-500">{errors.scenario}</p>}
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground" htmlFor="objectives">
                                        Learning Objectives
                                    </label>
                                    <textarea
                                        id="objectives"
                                        value={data.objectives}
                                        onChange={(e) => setData('objectives', e.target.value)}
                                        className="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                        rows={4}
                                    />
                                    {errors.objectives && <p className="text-sm text-red-500">{errors.objectives}</p>}
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center justify-between">
                            <Link href={route('admin.osce-cases.index')} className="clean-button px-4 py-2">
                                Back
                            </Link>
                            <button type="submit" className="clean-button primary px-4 py-2" disabled={processing}>
                                {processing ? 'Saving…' : 'Update Case'}
                            </button>
                        </div>
                    </form>
                </div>
            </AppLayout>
        </>
    );
}
