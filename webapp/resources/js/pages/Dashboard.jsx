import React from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Dashboard({ stats, welcome }) {
    const breadcrumbs = [
        {
            title: 'dashboard',
            href: route('dashboard'),
        },
    ];

    const primaryStats = [
        {
            label: 'Active OSCE Cases',
            value: stats?.osce_cases_active || 0,
            description: 'Realistic clinical scenarios ready to run',
            accent: 'text-emerald-600 dark:text-emerald-400',
            badge: 'Live'
        },
        {
            label: 'Learners Registered',
            value: stats?.users_total || 0,
            description: 'Participants collaborating across cohorts',
            accent: 'text-blue-600 dark:text-blue-400',
            badge: 'Community'
        }
    ];

    const focusTracks = [
        {
            title: 'Clinical Reasoning Sprint',
            description: 'Walk through a full patient journey to sharpen diagnostics.',
            icon: '🧠',
            action: () => router.visit(route('osce')),
            cta: 'Resume training',
            status: 'In progress'
        },
        {
            title: 'Communication Warm-Up',
            description: 'Practice difficult conversations with simulated actors.',
            icon: '💬',
            action: null,
            cta: 'Opens soon',
            status: 'Coming soon'
        }
    ];

    const navigationShortcuts = [
        {
            title: 'Start an OSCE Session',
            description: 'Launch an interactive simulation tailored to your level.',
            href: route('osce'),
            intent: 'primary',
            icon: '🚀'
        },
        {
            title: 'Review Past Sessions',
            description: 'Reflection timeline coming soon with personalized insights.',
            href: null,
            intent: 'secondary',
            icon: '🗂️',
            disabled: true
        },
        {
            title: 'Explore Case Library',
            description: 'Plan practice time with specialty-aligned scenarios.',
            href: route('osce'),
            intent: 'secondary',
            icon: '📚'
        }
    ];

    const engagementHighlights = [
        {
            title: 'Confidence Trend',
            caption: 'You logged consistent improvements this week.',
            metric: '+12%',
            chart: [52, 60, 64, 58, 66, 70],
            accent: 'emerald'
        },
        {
            title: 'Case Variety',
            caption: 'Balanced exposure across cardiology, endocrine, and trauma.',
            metric: '6 specialties',
            chart: [30, 48, 42, 56, 40, 62],
            accent: 'blue'
        }
    ];

    const systemStatus = [
        { label: 'API', value: 'Operational', tone: 'text-emerald-600 dark:text-emerald-400' },
        { label: 'Latency', value: '< 50ms', tone: 'text-emerald-600 dark:text-emerald-400' },
        { label: 'Uptime', value: '99.9%', tone: 'text-emerald-600 dark:text-emerald-400' }
    ];

    const goTo = (href) => {
        if (!href) return;
        router.visit(href);
    };

    return (
        <>
            <Head title="dashboard" />

            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex h-full flex-1 flex-col gap-10">
                    <section className="clean-card bg-card/95 p-8 transition-all duration-300">
                        <div className="grid gap-8 lg:grid-cols-[1.2fr_1fr] items-center">
                            <div className="space-y-4">
                                <div className="inline-flex items-center gap-2 rounded-full bg-emerald-50 dark:bg-emerald-950/30 px-3 py-1 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                    <span className="w-2 h-2 rounded-full bg-emerald-500" />
                                    <span>Training Mode Active</span>
                                </div>
                                <div className="space-y-3">
                                    <h1 className="text-2xl font-semibold text-foreground">
                                        {welcome?.title || 'Welcome back'}
                                    </h1>
                                    <p className="text-muted-foreground text-lg">
                                        {welcome?.message || 'Practice clinical skills and track your OSCE progress.'}
                                    </p>
                                </div>
                                <div className="flex flex-wrap items-center gap-3">
                                    <button
                                        className="clean-button primary px-5 py-2.5 text-base"
                                        onClick={() => goTo(route('osce'))}
                                    >
                                        Start new simulation
                                    </button>
                                    <button
                                        className="clean-button px-5 py-2.5 text-base"
                                        onClick={() => goTo(route('osce.results.index'))}
                                    >
                                        View performance history
                                    </button>
                                </div>
                            </div>
                            <div className="clean-card bg-background/95 p-6 transition-all duration-300">
                                <div className="space-y-5">
                                    {primaryStats.map((item) => (
                                        <div key={item.label} className="flex items-start justify-between gap-4">
                                            <div className="space-y-1">
                                                <p className={`text-sm font-medium ${item.accent}`}>{item.badge}</p>
                                                <p className="text-lg font-medium text-foreground">{item.label}</p>
                                                <p className="text-sm text-muted-foreground">{item.description}</p>
                                            </div>
                                            <span className="text-3xl font-semibold text-foreground">{item.value.toLocaleString()}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="grid gap-6 lg:grid-cols-[1fr_0.65fr] xl:grid-cols-[1.1fr_0.6fr]">
                        <div className="space-y-6">
                            <div className="clean-card bg-card/95 p-6 transition-all duration-300">
                                <div className="border-b border-border pb-4 mb-4">
                                    <h2 className="text-lg font-medium text-foreground">Today&apos;s Focus Tracks</h2>
                                    <p className="text-sm text-muted-foreground">Stay aligned with your learning plan and pick up where you left off.</p>
                                </div>
                                <div className="space-y-5">
                                    {focusTracks.map((track) => (
                                        <div key={track.title} className="clean-card bg-background/85 p-4 transition-all duration-300">
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="flex items-center gap-3">
                                                    <span className="text-2xl" aria-hidden>{track.icon}</span>
                                                    <div>
                                                        <p className="text-base font-medium text-foreground">{track.title}</p>
                                                        <p className="text-sm text-muted-foreground">{track.description}</p>
                                                    </div>
                                                </div>
                                                <span className="inline-flex items-center gap-1 rounded-full border border-border/50 bg-card/75 px-3 py-1 text-xs font-medium text-muted-foreground">
                                                    {track.status}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <button
                                                    className={`clean-button ${track.action ? 'primary' : ''} px-4 py-2 text-sm`}
                                                    disabled={!track.action}
                                                    onClick={() => track.action?.()}
                                                >
                                                    {track.cta}
                                                </button>
                                                {!track.action && (
                                                    <span className="text-xs text-muted-foreground">We&apos;ll notify you when it unlocks.</span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="clean-card bg-card/95 p-6 transition-all duration-300">
                                <div className="border-b border-border pb-4 mb-4">
                                    <h2 className="text-lg font-medium text-foreground">Highlights & Momentum</h2>
                                    <p className="text-sm text-muted-foreground">Track your progression at a glance and celebrate recent wins.</p>
                                </div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    {engagementHighlights.map((highlight) => (
                                        <div key={highlight.title} className="clean-card bg-background/85 p-5">
                                            <div className="flex items-center justify-between">
                                                <p className="text-sm font-medium text-foreground">{highlight.title}</p>
                                                <span className={`text-base font-semibold ${highlight.accent === 'emerald' ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400'}`}>
                                                    {highlight.metric}
                                                </span>
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">{highlight.caption}</p>
                                            <div className="mt-4 flex h-16 items-end gap-1">
                                                {highlight.chart.map((value, idx) => (
                                                    <span
                                                        // Approximate spark bars based on relative value
                                                        key={`${highlight.title}-${idx}`}
                                                        className={`flex-1 rounded-t-sm ${highlight.accent === 'emerald' ? 'bg-emerald-500/70 dark:bg-emerald-500/40' : 'bg-blue-500/70 dark:bg-blue-500/40'} transition-opacity duration-200 hover:opacity-90`}
                                                        style={{ height: `${Math.max(30, value)}%` }}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="space-y-6">
                            <div className="clean-card bg-card/95 p-6 transition-all duration-300">
                                <div className="border-b border-border pb-4 mb-4">
                                    <h2 className="text-lg font-medium text-foreground">Jump Back In</h2>
                                    <p className="text-sm text-muted-foreground">Browse the areas that keep momentum strong.</p>
                                </div>
                                <div className="space-y-4">
                                    {navigationShortcuts.map((shortcut) => (
                                        <button
                                            key={shortcut.title}
                                            className={`clean-button ${shortcut.intent === 'primary' ? 'primary' : ''} w-full px-4 py-3 text-left ${shortcut.disabled ? 'opacity-60 cursor-not-allowed' : ''}`}
                                            disabled={shortcut.disabled}
                                            onClick={() => !shortcut.disabled && goTo(shortcut.href)}
                                        >
                                            <div className="flex items-center gap-3">
                                                <span className="text-xl" aria-hidden>{shortcut.icon}</span>
                                                <div className="flex flex-col">
                                                    <span className="text-base font-medium text-foreground">{shortcut.title}</span>
                                                    <span className="text-sm text-muted-foreground">{shortcut.description}</span>
                                                </div>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="clean-card bg-card/95 p-6 transition-all duration-300">
                                <div className="border-b border-border pb-4 mb-4">
                                    <h2 className="text-lg font-medium text-foreground">Platform Health</h2>
                                    <p className="text-sm text-muted-foreground">Everything you need for a smooth training session.</p>
                                </div>
                                <div className="space-y-4 text-sm text-muted-foreground">
                                    {systemStatus.map((item) => (
                                        <div key={item.label} className="clean-card bg-background/85 px-4 py-3 flex items-center justify-between">
                                            <span className="text-muted-foreground">{item.label}</span>
                                            <span className={`font-medium ${item.tone}`}>{item.value}</span>
                                        </div>
                                    ))}
                                    <div className="clean-card bg-background/85 px-4 py-3 flex items-center gap-2">
                                        <div className="h-2 w-2 rounded-full bg-emerald-500" />
                                        <span className="text-sm text-muted-foreground">System Operational — enjoy uninterrupted sessions.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </AppLayout>
        </>
    );
}
