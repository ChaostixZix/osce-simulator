import React from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
// import { Card, CardContent, CardHeader, CardTitle, Button } from '@vibe-kanban/ui-kit';

// Placeholder icons - we'll use text for now since icons would need a different lib for React
const StethoscopeIcon = () => <span className="text-lg">🩺</span>;
const UsersIcon = () => <span className="text-lg">👥</span>;
const GraduationCapIcon = () => <span className="text-lg">🎓</span>;
const ArrowRightIcon = () => <span className="text-sm">→</span>;

export default function Dashboard({ stats, welcome }) {
    const breadcrumbs = [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
    ];

    const statCards = [
        {
            title: 'OSCE Cases',
            value: stats?.osce_cases_active || 0,
            icon: StethoscopeIcon,
            emoji: '🩺',
            route: '/osce',
            description: 'Active cases available'
        },
        {
            title: 'Users',
            value: stats?.users_total || 0,
            icon: UsersIcon,
            emoji: '👥',
            route: '#',
            description: 'Registered members',
            clickable: false
        }
    ];

    const navigateToRoute = (route, clickable = true) => {
        if (clickable && route !== '#') {
            router.visit(route);
        }
    };

    return (
        <>
            <Head title="Dashboard" />
            
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex h-full flex-1 flex-col gap-6 p-4 overflow-x-auto">
                    
                    {/* Welcome Section */}
                    <div className="text-center space-y-4">
                        <h1 className="text-3xl font-bold">{welcome?.title || 'Welcome back 👋'}</h1>
                        <p className="text-lg text-muted-foreground">{welcome?.message || 'Practice clinical skills and track your OSCE progress.'}</p>
                        
                        {/* App Description */}
                        <div className="bg-muted/30 rounded-none p-6 max-w-4xl mx-auto">
                            <div className="flex items-center gap-2 justify-center mb-4">
                                <GraduationCapIcon />
                                <h2 className="text-xl font-semibold">What is this app?</h2>
                            </div>
                            <div className="grid md:grid-cols-2 gap-4 text-sm">
                                <div className="text-center space-y-2">
                                    <div className="text-2xl">🩺</div>
                                    <div className="font-medium">OSCE Training</div>
                                    <p className="text-muted-foreground">Simulated patient interactions with physical exams and test ordering</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Stats Cards */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {statCards.map((card) => (
                            <div 
                                key={card.title}
                                className={`bg-card text-card-foreground border p-6 transition-all duration-200 ${
                                    card.clickable !== false ? 'hover:shadow-md cursor-pointer hover:scale-[1.02]' : ''
                                }`}
                                onClick={() => navigateToRoute(card.route, card.clickable)}
                            >
                                <div className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <h3 className="text-sm font-medium">
                                        {card.emoji} {card.title}
                                    </h3>
                                    <card.icon />
                                </div>
                                <div>
                                    <div className="text-2xl font-bold">{card.value.toLocaleString()}</div>
                                    <p className="text-xs text-muted-foreground">
                                        {card.description}
                                    </p>
                                    {card.clickable !== false && card.route !== '#' && (
                                        <div className="flex items-center text-xs text-primary mt-2">
                                            <span>Go to {card.title.toLowerCase()}</span>
                                            <ArrowRightIcon />
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Quick Actions */}
                    <div className="bg-muted/30 p-6">
                        <h3 className="text-lg font-semibold mb-4">Quick Actions</h3>
                        <div className="grid md:grid-cols-3 gap-4">
                            <button 
                                className="h-auto p-4 flex flex-col gap-2 hover:bg-primary/10 border border-input bg-background"
                                onClick={() => router.visit('/osce')}
                            >
                                <StethoscopeIcon />
                                <span className="font-medium">Start OSCE</span>
                                <span className="text-xs text-muted-foreground">Practice clinical skills</span>
                            </button>
                        </div>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}