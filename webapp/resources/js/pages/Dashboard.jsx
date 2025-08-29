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
                <div className="flex h-full flex-1 flex-col gap-6 rounded-none p-2 md:p-4 overflow-x-auto text-neutral-300">
                    
                    {/* Welcome Section */}
                    <div className="text-center space-y-3">
                        <h1 className="text-3xl font-medium lowercase">{welcome?.title || 'welcome back'}</h1>
                        <p className="text-base text-neutral-400 lowercase">{welcome?.message || 'practice clinical skills and track your osce progress.'}</p>
                        
                        {/* minimal description */}
                        <div className="border border-neutral-800 bg-neutral-900/50 p-5 max-w-3xl mx-auto text-sm lowercase text-neutral-400">
                            build confidence with structured practice. fast. minimal. focused.
                        </div>
                    </div>

                    {/* Stats Cards */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                        {statCards.map((card) => (
                            <div 
                                key={card.title}
                                className={`bg-neutral-900/60 text-neutral-300 border border-neutral-800 p-5 transition-colors ${
                                    card.clickable !== false ? 'hover:border-emerald-600/30 cursor-pointer' : ''
                                }`}
                                onClick={() => navigateToRoute(card.route, card.clickable)}
                            >
                                <div className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <div className="text-sm font-medium lowercase text-emerald-400">
                                        {card.title.toLowerCase()}
                                    </div>
                                    <div className="h-4 w-4 text-neutral-600">
                                        {/* Icon placeholder */}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-2xl font-medium">{card.value.toLocaleString()}</div>
                                    <p className="text-xs text-neutral-500 lowercase">
                                        {card.description.toLowerCase()}
                                    </p>
                                    {card.clickable !== false && card.route !== '#' && (
                                        <div className="flex items-center text-xs text-emerald-400 mt-2 lowercase">
                                            <span>open {card.title.toLowerCase()}</span>
                                            <span className="ml-1">→</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Quick Actions */}
                    <div className="border border-neutral-800 bg-neutral-900/50 p-5">
                        <h3 className="text-lg font-medium mb-3 lowercase">quick actions</h3>
                        <div className="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
                            <button 
                                className="h-auto p-4 flex flex-col gap-1 border border-neutral-800 bg-neutral-950/40 text-left hover:border-emerald-600/30"
                                onClick={() => router.visit(route('osce'))}
                            >
                                <span className="text-sm text-emerald-400 lowercase">start osce</span>
                                <span className="text-xs text-neutral-500 lowercase">practice clinical skills</span>
                            </button>
                        </div>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
