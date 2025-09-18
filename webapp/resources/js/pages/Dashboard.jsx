import React from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@vibe-kanban/ui-kit';

export default function Dashboard({ stats, welcome }) {
    const breadcrumbs = [
        {
            title: 'dashboard',
            href: route('dashboard'),
        },
    ];

    const statCards = [
        {
            title: 'OSCE Cases',
            value: stats?.osce_cases_active || 0,
            emoji: '🩺',
            route: route('osce'),
            description: 'Active cases available',
            clickable: true
        },
        {
            title: 'Users',
            value: stats?.users_total || 0,
            emoji: '👥',
            route: '#',
            description: 'Registered members',
            clickable: false
        }
    ];

    const navigateToRoute = (href, clickable = true) => {
        if (clickable && href !== '#') {
            router.visit(href);
        }
    };

    return (
        <>
            <Head title="dashboard" />

            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                    
                    {/* Welcome Section */}
                    <div className="text-center space-y-6">
                        <div className="space-y-3">
                            <h1 className="text-3xl font-semibold text-foreground">{welcome?.title || 'Welcome Back'} 👋</h1>
                            <p className="text-xl text-muted-foreground">{welcome?.message || 'Practice clinical skills and track your OSCE progress.'}</p>
                        </div>
                        
                        {/* Clean description card */}
                        <div className="clean-card bg-card p-6 max-w-3xl mx-auto">
                            <div className="flex items-center justify-center gap-2 mb-3">
                                <div className="w-2 h-2 bg-emerald-500 rounded-full"></div>
                                <span className="text-base text-emerald-600 dark:text-emerald-400 font-medium">Training Mode Active</span>
                            </div>
                            <p className="text-lg text-muted-foreground">Build confidence with structured practice. Fast, minimal, and focused.</p>
                        </div>
                    </div>

                    {/* Stats Cards */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                        {statCards.map((card, idx) => {
                            const colors = [
                                { bg: 'bg-emerald-50 dark:bg-emerald-950/20', border: 'border-emerald-200 dark:border-emerald-800', accent: 'text-emerald-600 dark:text-emerald-400' },
                                { bg: 'bg-blue-50 dark:bg-blue-950/20', border: 'border-blue-200 dark:border-blue-800', accent: 'text-blue-600 dark:text-blue-400' }
                            ];
                            const cardColor = colors[idx % colors.length];
                            
                            return (
                                <div 
                                    key={card.title}
                                    className={`clean-card ${cardColor.bg} ${cardColor.border} p-6 transition-all duration-200 ${
                                        card.clickable !== false ? 'hover:shadow-md cursor-pointer' : ''
                                    }`}
                                    onClick={() => navigateToRoute(card.route, card.clickable)}
                                >
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <h3 className={`text-base font-medium ${cardColor.accent}`}>
                                                {card.title}
                                            </h3>
                                            <div className="text-2xl">{card.emoji}</div>
                                        </div>
                                        <div className="space-y-2">
                                            <div className="text-3xl font-bold text-foreground">{card.value.toLocaleString()}</div>
                                            <p className="text-base text-muted-foreground">
                                                {card.description}
                                            </p>
                                            {card.clickable !== false && card.route !== '#' && (
                                                <div className={`flex items-center text-base ${cardColor.accent} font-medium transition-all duration-200 group-hover:gap-2`}>
                                                    <span>Open {card.title}</span>
                                                    <span className="ml-1 group-hover:ml-2 transition-all duration-200">→</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Quick Actions */}
                    <div className="clean-card bg-card p-6">
                        <div className="space-y-6">
                            <h3 className="text-xl font-medium text-foreground">Quick Actions</h3>
                            
                            <div className="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <button 
                                    className="clean-button h-auto p-5 flex flex-col gap-3 text-left group bg-emerald-50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800"
                                    onClick={() => router.visit(route('osce'))}
                                >
                                    <div className="flex items-center gap-2">
                                        <div className="w-2 h-2 bg-emerald-500 rounded-full"></div>
                                        <span className="text-base text-emerald-600 dark:text-emerald-400 font-semibold">Start OSCE</span>
                                    </div>
                                    <span className="text-base text-muted-foreground">Practice clinical skills</span>
                                    
                                    <div className="flex items-center gap-1 text-base text-emerald-600 dark:text-emerald-400 font-medium group-hover:gap-2 transition-all">
                                        <span>Launch Training</span>
                                        <span className="group-hover:translate-x-1 transition-transform">→</span>
                                    </div>
                                </button>
                                
                                {/* Additional placeholder actions */}
                                <div className="clean-card p-5 bg-muted/20 border-dashed border-muted-foreground/20">
                                    <div className="flex items-center gap-2 mb-2">
                                        <div className="w-2 h-2 bg-muted-foreground/40 rounded-full"></div>
                                        <span className="text-base text-muted-foreground font-medium">Coming Soon</span>
                                    </div>
                                    <span className="text-base text-muted-foreground/60">More features</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* System info */}
                    <div className="flex items-center justify-between text-base text-muted-foreground">
                        <div className="flex items-center gap-2">
                            <div className="w-2 h-2 bg-emerald-500 rounded-full"></div>
                            <span>System Operational</span>
                        </div>
                        <div className="flex items-center gap-4">
                            <span>Uptime: 99.9%</span>
                            <span>Latency: &lt;50ms</span>
                        </div>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
