import React, { useEffect, useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

const tabs = [
    {
        id: 'overview',
        label: 'Overview',
        description: 'Mission control for your OSCE preparation.',
    },
    {
        id: 'cases',
        label: 'Cases',
        description: 'Browse, filter, and plan the scenarios you want to run.',
    },
    {
        id: 'progress',
        label: 'Progress',
        description: 'Track trends, milestones, and skill development.',
    },
    {
        id: 'history',
        label: 'History',
        description: 'Review session outcomes and export your performance.',
    },
];

const statusStyles = {
    available: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    in_progress: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    completed: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
    locked: 'bg-border/60 text-muted-foreground',
};

const difficultyStyles = {
    Beginner: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    Intermediate: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
    Advanced: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
};

const formatStatusLabel = (status) => (status ? status.replace(/_/g, ' ') : 'unknown');

function formatDate(iso) {
    if (!iso) return '—';
    const date = new Date(iso);
    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatRelative(iso) {
    if (!iso) return '—';
    const formatter = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });
    const now = new Date();
    const date = new Date(iso);
    const diff = date.getTime() - now.getTime();
    const diffDays = Math.round(diff / (1000 * 60 * 60 * 24));

    if (Math.abs(diffDays) >= 1) {
        return formatter.format(diffDays, 'day');
    }

    const diffHours = Math.round(diff / (1000 * 60 * 60));
    if (Math.abs(diffHours) >= 1) {
        return formatter.format(diffHours, 'hour');
    }

    const diffMinutes = Math.round(diff / (1000 * 60));
    return formatter.format(diffMinutes, 'minute');
}

function formatDuration(minutes) {
    if (!minutes) return '—';
    if (minutes < 60) {
        return `${minutes} min`;
    }

    const hours = Math.floor(minutes / 60);
    const remaining = minutes % 60;

    return remaining === 0 ? `${hours} hr` : `${hours} hr ${remaining} min`;
}

function TrendChart({ dataset }) {
    if (!dataset || dataset.length === 0) {
        return <p className="text-sm text-muted-foreground">No assessed sessions yet.</p>;
    }

    const maxScore = dataset.reduce((acc, point) => {
        return Math.max(acc, point.max_score ?? point.score ?? 0);
    }, 0) || 100;

    return (
        <div className="flex items-end gap-3 h-40">
            {dataset.map((point) => {
                const score = point.score ?? 0;
                const height = Math.max(12, (score / maxScore) * 100);

                return (
                    <div key={`${point.label}-${score}`} className="flex-1 flex flex-col items-center gap-2">
                        <div className="w-full bg-primary/20 dark:bg-primary/30 rounded-t-md transition-all duration-200" style={{ height: `${height}%` }} />
                        <div className="text-center">
                            <p className="text-xs font-medium text-foreground">{score ?? '—'}</p>
                            <p className="text-[11px] text-muted-foreground">{point.label}</p>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

function ProgressBar({ value, label }) {
    const safeValue = Math.min(100, Math.max(0, value ?? 0));

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between text-sm text-muted-foreground">
                <span>{label}</span>
                <span className="font-medium text-foreground">{safeValue}%</span>
            </div>
            <div className="h-2 w-full rounded-full bg-border overflow-hidden">
                <div className="h-full bg-primary/70 transition-all duration-300" style={{ width: `${safeValue}%` }} />
            </div>
        </div>
    );
}

function FlowIndicator({ flow }) {
    if (!flow) return null;

    const statusIcon = {
        completed: '✅',
        current: '✨',
        upcoming: '•',
    };

    return (
        <div className="clean-card p-6">
            <div className="card-header card-header-primary">
                <div>
                    <p className="text-xs uppercase tracking-wide text-muted-foreground">Session flow</p>
                    <h3 className="text-lg font-medium text-foreground">Guide your next encounter</h3>
                </div>
                <span className="text-xs text-muted-foreground">4 steps</span>
            </div>
            <div className="mt-6 grid gap-4 md:grid-cols-4">
                {flow.map((step) => (
                    <div
                        key={step.id}
                        className={`clean-card p-4 transition-all duration-200 ${
                            step.status === 'current'
                                ? 'ring-2 ring-primary/40'
                                : step.status === 'completed'
                                ? 'opacity-90'
                                : 'opacity-75'
                        }`}
                    >
                        <div className="flex items-center gap-2">
                            <span className="text-lg" aria-hidden>
                                {statusIcon[step.status]}
                            </span>
                            <div>
                                <p className="text-sm font-medium text-foreground">{step.title}</p>
                                <p className="text-xs text-muted-foreground">{step.description}</p>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function QuickStats({ stats }) {
    if (!stats) return null;

    const accents = ['card-header-primary', 'card-header-accent', 'card-header-secondary', 'card-header-primary'];

    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {stats.map((stat, index) => (
                <div key={stat.label} className="clean-card p-6 space-y-4">
                    <div className={`card-header ${accents[index % accents.length]} items-start`}>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-muted-foreground">{stat.badge}</p>
                            <h3 className="text-lg font-medium text-foreground">{stat.label}</h3>
                        </div>
                        <span className="text-xs text-muted-foreground">Live</span>
                    </div>
                    <div className="space-y-2">
                        <p className="text-3xl font-semibold text-foreground">
                            {stat.value !== null && stat.value !== undefined ? stat.value.toLocaleString() : '—'}
                            {stat.suffix ?? ''}
                        </p>
                        <p className="text-sm text-muted-foreground">{stat.description}</p>
                    </div>
                </div>
            ))}
        </div>
    );
}

function RecentActivity({ sessions }) {
    if (!sessions || sessions.length === 0) {
        return (
            <div className="clean-card p-6">
                <div className="card-header card-header-secondary">
                    <div>
                        <p className="text-xs uppercase tracking-wide text-muted-foreground">Recent activity</p>
                        <h3 className="text-lg font-medium text-foreground">History will appear here</h3>
                    </div>
                </div>
                <p className="mt-4 text-sm text-muted-foreground">Run a session to start building your activity timeline.</p>
            </div>
        );
    }

    return (
        <div className="clean-card p-6">
            <div className="card-header card-header-secondary">
                <div>
                    <p className="text-xs uppercase tracking-wide text-muted-foreground">Recent activity</p>
                    <h3 className="text-lg font-medium text-foreground">Latest session highlights</h3>
                </div>
                <span className="text-xs text-muted-foreground">Updated {formatRelative(sessions[0]?.started_at)}</span>
            </div>
            <div className="mt-4 space-y-4">
                {sessions.map((session) => (
                    <div key={session.id} className="flex flex-col gap-2 rounded-lg border border-border/60 bg-background/90 p-4">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-medium text-foreground">{session.case_title}</p>
                                <p className="text-xs text-muted-foreground">{formatDate(session.started_at)}</p>
                            </div>
                                <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${
                                statusStyles[session.status] ?? 'bg-border/60 text-muted-foreground'
                            }`}>
                                {formatStatusLabel(session.status)}
                            </span>
                        </div>
                        <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                            <span>Started {formatRelative(session.started_at)}</span>
                            {session.completed_at && <span>Completed {formatRelative(session.completed_at)}</span>}
                            {session.score !== null && session.score !== undefined && (
                                <span className="text-foreground font-medium">Score {session.score}{session.max_score ? `/${session.max_score}` : ''}</span>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function SystemStatus({ items }) {
    if (!items) return null;

    return (
        <div className="clean-card p-6">
            <div className="card-header card-header-accent">
                <div>
                    <p className="text-xs uppercase tracking-wide text-muted-foreground">Platform health</p>
                    <h3 className="text-lg font-medium text-foreground">Everything ready for lift-off</h3>
                </div>
            </div>
            <div className="mt-4 space-y-3">
                {items.map((item) => (
                    <div key={item.label} className="flex items-center justify-between rounded-lg border border-border/60 bg-background/90 px-4 py-3">
                        <span className="text-sm text-muted-foreground">{item.label}</span>
                        <span className="text-sm font-medium text-foreground">{item.value}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

export default function Dashboard({ overview, cases, progress, history, meta, welcome }) {
    const breadcrumbs = [
        {
            title: 'dashboard',
            href: route('dashboard'),
        },
    ];

    const [activeTab, setActiveTab] = useState(meta?.active_tab ?? 'overview');
    const [search, setSearch] = useState('');
    const [selectedDifficulty, setSelectedDifficulty] = useState('all');
    const [selectedStatus, setSelectedStatus] = useState('all');
    const [selectedSetting, setSelectedSetting] = useState('all');

    useEffect(() => {
        setActiveTab(meta?.active_tab ?? 'overview');
    }, [meta?.active_tab]);

    const handleTabChange = (tabId) => {
        setActiveTab(tabId);
        router.get(route('dashboard'), { tab: tabId }, { preserveScroll: true, preserveState: true, replace: true });
    };

    const filteredCases = useMemo(() => {
        if (!cases?.items) return [];

        return cases.items.filter((item) => {
            const matchesSearch = item.title.toLowerCase().includes(search.toLowerCase()) ||
                (item.summary ?? '').toLowerCase().includes(search.toLowerCase());
            const matchesDifficulty = selectedDifficulty === 'all' || item.difficulty === selectedDifficulty;
            const matchesStatus = selectedStatus === 'all' || item.status === selectedStatus;
            const matchesSetting = selectedSetting === 'all' || item.clinical_setting === selectedSetting;

            return matchesSearch && matchesDifficulty && matchesStatus && matchesSetting;
        });
    }, [cases?.items, search, selectedDifficulty, selectedStatus, selectedSetting]);

    const overviewActions = [
        {
            label: 'Launch new case',
            description: 'Jump straight into a simulation that matches your goal.',
            intent: 'primary',
            onClick: () => router.visit(route('osce')),
        },
        {
            label: 'Browse case library',
            description: 'Filter by specialty, difficulty, and urgency to plan practice time.',
            onClick: () => handleTabChange('cases'),
        },
        {
            label: 'Review growth report',
            description: 'Open longitudinal analytics to reflect on your last sessions.',
            onClick: () => router.visit(route('growth.dashboard')),
        },
    ];

    const renderOverview = () => (
        <div className="space-y-8">
            <QuickStats stats={overview?.quick_stats} />

            <FlowIndicator flow={overview?.flow} />

            <div className="grid gap-6 xl:grid-cols-[1fr_0.6fr]">
                <RecentActivity sessions={overview?.recent_activity} />
                <SystemStatus items={overview?.system_status} />
            </div>
        </div>
    );

    const renderCases = () => (
        <div className="space-y-8">
            <div className="clean-card p-6 space-y-4">
                <div className="card-header card-header-primary">
                    <div>
                        <p className="text-xs uppercase tracking-wide text-muted-foreground">Case library</p>
                        <h3 className="text-lg font-medium text-foreground">Plan your next scenario</h3>
                    </div>
                    <span className="text-xs text-muted-foreground">{filteredCases.length} cases</span>
                </div>
                <div className="grid gap-4 md:grid-cols-4">
                    <input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search cases"
                        className="clean-card px-4 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                    />
                    <select
                        value={selectedDifficulty}
                        onChange={(event) => setSelectedDifficulty(event.target.value)}
                        className="clean-card px-4 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                    >
                        <option value="all">All difficulties</option>
                        {cases?.filters?.difficulties?.map((difficulty) => (
                            <option key={difficulty} value={difficulty}>
                                {difficulty}
                            </option>
                        ))}
                    </select>
                    <select
                        value={selectedStatus}
                        onChange={(event) => setSelectedStatus(event.target.value)}
                        className="clean-card px-4 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                    >
                        <option value="all">All statuses</option>
                        {cases?.filters?.statuses?.map((status) => (
                            <option key={status} value={status}>
                                {formatStatusLabel(status)}
                            </option>
                        ))}
                    </select>
                    <select
                        value={selectedSetting}
                        onChange={(event) => setSelectedSetting(event.target.value)}
                        className="clean-card px-4 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                    >
                        <option value="all">All settings</option>
                        {cases?.filters?.settings?.map((setting) => (
                            <option key={setting} value={setting}>
                                {setting}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="grid gap-6 xl:grid-cols-3 lg:grid-cols-2">
                {filteredCases.map((caseItem) => (
                    <div key={caseItem.id} className="clean-card overflow-hidden transition-all duration-200 hover:scale-[1.02]">
                        <div className="card-header card-header-secondary">
                            <div>
                                <p className="text-xs uppercase tracking-wide text-muted-foreground">Case #{caseItem.id}</p>
                                <h3 className="text-lg font-medium text-foreground">{caseItem.title}</h3>
                            </div>
                            <div className="flex flex-col items-end gap-2">
                                <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${statusStyles[caseItem.status] ?? 'bg-border/60 text-muted-foreground'}`}>
                                    {formatStatusLabel(caseItem.status)}
                                </span>
                                {caseItem.difficulty && (
                                    <span className={`inline-flex items-center rounded-full px-2 py-1 text-[11px] font-medium ${
                                        difficultyStyles[caseItem.difficulty] ?? 'bg-primary/10 text-primary'
                                    }`}>
                                        {caseItem.difficulty}
                                    </span>
                                )}
                            </div>
                        </div>
                        <div className="p-6 space-y-4">
                            <p className="text-sm text-muted-foreground leading-relaxed">{caseItem.summary || 'This case will describe the scenario when you launch it.'}</p>
                            <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span className="font-medium">{formatDuration(caseItem.duration_minutes)}</span>
                                {caseItem.clinical_setting && <span>• {caseItem.clinical_setting}</span>}
                                {caseItem.urgency_level && <span>• Urgency {caseItem.urgency_level}</span>}
                            </div>
                            <div className="flex items-center justify-between pt-2 border-t border-border/50">
                                <span className="text-xs text-muted-foreground">{caseItem.completed_attempts} completed / {caseItem.attempts} attempts</span>
                                <button
                                    className={`clean-button ${caseItem.is_active ? 'primary' : ''} px-4 py-2 text-xs font-medium`}
                                    disabled={!caseItem.is_active}
                                    onClick={() => router.visit(route('onboarding.show', caseItem.id))}
                                >
                                    {caseItem.is_active ? 'Start case' : 'Locked'}
                                </button>
                            </div>
                        </div>
                        </div>
                    </div>
                ))}
                {filteredCases.length === 0 && (
                    <div className="clean-card p-8 text-center space-y-3">
                        <p className="text-lg font-medium text-foreground">No cases match your filters</p>
                        <p className="text-sm text-muted-foreground">Adjust filters to explore more of the library.</p>
                    </div>
                )}
            </div>
        </div>
    );

    const renderProgress = () => (
        <div className="space-y-8">
            <div className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div className="clean-card p-6 space-y-6">
                    <div className="card-header card-header-primary">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-muted-foreground">Performance trend</p>
                            <h3 className="text-lg font-medium text-foreground">Assessment momentum</h3>
                        </div>
                        {progress?.best_score !== null && progress?.best_score !== undefined && (
                            <span className="text-xs text-muted-foreground">Best score {progress.best_score}</span>
                        )}
                    </div>
                    <TrendChart dataset={progress?.score_trend} />
                </div>

                <div className="clean-card p-6 space-y-6">
                    <div className="card-header card-header-accent">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-muted-foreground">Streak & readiness</p>
                            <h3 className="text-lg font-medium text-foreground">Daily consistency</h3>
                        </div>
                        <span className="text-xs text-muted-foreground">{progress?.streak ?? 0} day streak</span>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Keep the streak alive by running a case today. Consistency unlocks richer analytics and advanced primers.
                    </p>
                    <div className="flex flex-col gap-3">
                        {progress?.skill_breakdown?.map((item) => (
                            <ProgressBar key={item.label} value={item.value} label={item.label} />
                        ))}
                    </div>
                </div>
            </div>

            <div className="clean-card p-6 space-y-6">
                <div className="card-header card-header-secondary">
                    <div>
                        <p className="text-xs uppercase tracking-wide text-muted-foreground">Milestones</p>
                        <h3 className="text-lg font-medium text-foreground">Next achievements in sight</h3>
                    </div>
                </div>
                <div className="grid gap-4 md:grid-cols-3">
                    {progress?.milestones?.map((milestone) => {
                        const percent = Math.min(100, Math.round(((milestone.current ?? 0) / (milestone.target || 1)) * 100));
                        return (
                            <div key={milestone.title} className="clean-card p-4 space-y-3">
                                <div>
                                    <p className="text-sm font-medium text-foreground">{milestone.title}</p>
                                    <p className="text-xs text-muted-foreground">Target {milestone.target}</p>
                                </div>
                                <div className="h-2 w-full rounded-full bg-border overflow-hidden">
                                    <div className="h-full bg-primary/80 transition-all" style={{ width: `${percent}%` }} />
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {milestone.current ?? 0} / {milestone.target}
                                </p>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );

    const renderHistory = () => (
        <div className="space-y-8">
            <div className="clean-card p-6">
                <div className="card-header card-header-primary">
                    <div>
                        <p className="text-xs uppercase tracking-wide text-muted-foreground">Session history</p>
                        <h3 className="text-lg font-medium text-foreground">Detailed timeline</h3>
                    </div>
                    <button
                        className="clean-button px-4 py-2 text-xs"
                        onClick={() => {
                            if (typeof window !== 'undefined') {
                                window.print();
                            }
                        }}
                    >
                        Export summary
                    </button>
                </div>
                <div className="mt-6 overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead>
                            <tr className="text-xs uppercase tracking-wide text-muted-foreground">
                                <th className="pb-3 pr-6 font-medium">Case</th>
                                <th className="pb-3 pr-6 font-medium">Status</th>
                                <th className="pb-3 pr-6 font-medium">Score</th>
                                <th className="pb-3 pr-6 font-medium">Duration</th>
                                <th className="pb-3 pr-6 font-medium">Started</th>
                                <th className="pb-3 pr-6 font-medium">Completed</th>
                                <th className="pb-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {history?.sessions?.map((session) => (
                                <tr key={session.id} className="text-sm text-muted-foreground">
                                    <td className="py-3 pr-6 text-foreground font-medium">{session.case_title}</td>
                                    <td className="py-3 pr-6">
                                        <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${
                                            statusStyles[session.status] ?? 'bg-border/60 text-muted-foreground'
                                        }`}>
                                            {formatStatusLabel(session.status)}
                                        </span>
                                    </td>
                                    <td className="py-3 pr-6 text-foreground">
                                        {session.score !== null && session.score !== undefined
                                            ? `${session.score}${session.max_score ? `/${session.max_score}` : ''}`
                                            : '—'}
                                    </td>
                                    <td className="py-3 pr-6">{formatDuration(session.duration_minutes)}</td>
                                    <td className="py-3 pr-6">{formatDate(session.started_at)}</td>
                                    <td className="py-3 pr-6">{formatDate(session.completed_at)}</td>
                                    <td className="py-3 text-right">
                                        <div className="flex justify-end">
                                            {session.result_url ? (
                                                <button
                                                    className="clean-button primary px-3 py-2 text-xs"
                                                    onClick={() => router.visit(session.result_url)}
                                                >
                                                    View results
                                                </button>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">Pending</span>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {history?.sessions?.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="py-6 text-center text-sm text-muted-foreground">
                                        Session results will appear once you run your first case.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );

    const renderActiveTab = () => {
        switch (activeTab) {
            case 'cases':
                return renderCases();
            case 'progress':
                return renderProgress();
            case 'history':
                return renderHistory();
            case 'overview':
            default:
                return renderOverview();
        }
    };

    return (
        <>
            <Head title="Dashboard" />

            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex flex-1 flex-col gap-8">
                    {/* SaaS-style Hero Section with Animation - Always visible */}
                    <div className="clean-card p-8 lg:p-12 space-y-8 bg-gradient-to-br from-primary/5 via-background to-primary/5 relative overflow-hidden">
                        {/* Animated background elements */}
                        <div className="absolute inset-0 opacity-20">
                            <div className="absolute top-10 left-10 w-32 h-32 bg-primary/10 rounded-full blur-xl animate-pulse"></div>
                            <div className="absolute bottom-10 right-10 w-24 h-24 bg-accent/10 rounded-full blur-lg animate-pulse delay-300"></div>
                            <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-48 h-48 bg-primary/5 rounded-full blur-2xl animate-pulse delay-700"></div>
                        </div>
                        
                        <div className="relative z-10 space-y-6">
                            {/* Welcome Message */}
                            <div className="text-center space-y-4">
                                <div className="inline-flex items-center gap-2 px-4 py-2 bg-primary/10 rounded-full text-primary text-sm font-medium">
                                    <div className="w-2 h-2 bg-primary rounded-full animate-pulse"></div>
                                    {welcome?.message || "System Ready"}
                                </div>
                                <h1 className="text-3xl lg:text-4xl font-bold text-foreground bg-gradient-to-r from-foreground to-foreground/70 bg-clip-text">
                                    {welcome?.title ?? 'Welcome back 👋'}
                                </h1>
                                <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
                                    Your comprehensive OSCE simulation platform is ready. Start a new case, review your progress, or explore the case library.
                                </p>
                            </div>

                            {/* Action Cards with Hover Animations */}
                            <div className="grid gap-6 md:grid-cols-3 mt-8">
                                {overviewActions.map((action, index) => (
                                    <button
                                        key={action.label}
                                        className={`clean-card p-6 text-left transition-all duration-300 hover:scale-105 hover:shadow-lg group ${
                                            action.intent === 'primary' 
                                                ? 'bg-gradient-to-br from-primary/20 to-primary/10 border-primary/30' 
                                                : 'hover:bg-card/80'
                                        }`}
                                        onClick={action.onClick}
                                        style={{ animationDelay: `${index * 100}ms` }}
                                    >
                                        <div className="space-y-3">
                                            <div className="flex items-center justify-between">
                                                <div className={`w-12 h-12 rounded-lg flex items-center justify-center ${
                                                    action.intent === 'primary' ? 'bg-primary/20' : 'bg-primary/10'
                                                } group-hover:scale-110 transition-transform duration-300`}>
                                                    <span className="text-primary text-xl">
                                                        {index === 0 ? '🚀' : index === 1 ? '📚' : '📊'}
                                                    </span>
                                                </div>
                                                <div className="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                                                    <span className="text-primary text-sm">→</span>
                                                </div>
                                            </div>
                                            <div>
                                                <h3 className="text-lg font-semibold text-foreground mb-2">{action.label}</h3>
                                                <p className="text-sm text-muted-foreground leading-relaxed">{action.description}</p>
                                            </div>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Tab Navigation */}
                    <div className="clean-card p-6 lg:p-8 space-y-6">
                        <div className="flex flex-col gap-3">
                            <h2 className="text-2xl lg:text-3xl font-bold text-foreground">OSCE Mission Control</h2>
                            <p className="text-muted-foreground max-w-3xl">
                                Navigate seamlessly between overview, case planning, progress analytics, and detailed session history.
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-8 border-b border-border pb-4">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => handleTabChange(tab.id)}
                                    className={`pb-3 px-2 text-base font-medium transition-all duration-200 border-b-2 hover:scale-105 ${
                                        activeTab === tab.id
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground/30'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>
                        <div className="bg-primary/5 rounded-lg p-4 border border-primary/20">
                            <p className="text-sm text-foreground font-medium">
                                {tabs.find((tab) => tab.id === activeTab)?.description}
                            </p>
                        </div>
                    </div>

                    {renderActiveTab()}
                </div>
            </AppLayout>
        </>
    );
}
