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
                <div className="flex h-full flex-1 flex-col gap-6 rounded-none p-2 md:p-4 overflow-x-auto">
                    
                    {/* Enhanced Welcome Section */}
                    <div className="text-center space-y-4 relative">
                        {/* Decorative elements */}
                        <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-16 h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent"></div>
                        
                        <div className="flex items-center justify-center gap-3 mb-2">
                            <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
                            <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">system status</span>
                            <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400"></div>
                        </div>
                        
                        <h1 className="text-3xl font-medium lowercase glow-text text-foreground">{welcome?.title || 'welcome back'} 👋</h1>
                        <p className="text-base text-muted-foreground lowercase">{welcome?.message || 'practice clinical skills and track your osce progress.'}</p>
                        
                        {/* Enhanced description with gaming elements */}
                        <div className="cyber-border bg-card/50 p-6 max-w-3xl mx-auto text-sm lowercase text-muted-foreground relative overflow-hidden">
                            {/* Animated corner accent */}
                            <div className="absolute top-2 right-2 w-3 h-3 border-t-2 border-r-2 border-emerald-400 opacity-60 animate-pulse"></div>
                            <div className="absolute bottom-2 left-2 w-3 h-3 border-b-2 border-l-2 border-cyan-400 opacity-60 animate-pulse"></div>
                            
                            <div className="flex items-center justify-center gap-2 mb-2">
                                <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                <span className="text-emerald-500 font-mono text-xs">TRAINING MODE ACTIVE</span>
                                <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                            </div>
                            <p>build confidence with structured practice. fast. minimal. focused.</p>
                        </div>
                    </div>

                    {/* Enhanced Stats Cards with colors */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {statCards.map((card, idx) => {
                            const colors = [
                                { bg: 'bg-gradient-to-br from-emerald-500/10 to-emerald-600/5', border: 'border-emerald-500/30', accent: 'text-emerald-400', glow: 'shadow-emerald-500/10' },
                                { bg: 'bg-gradient-to-br from-blue-500/10 to-blue-600/5', border: 'border-blue-500/30', accent: 'text-blue-400', glow: 'shadow-blue-500/10' }
                            ];
                            const cardColor = colors[idx % colors.length];
                            
                            return (
                                <div 
                                    key={card.title}
                                    className={`cyber-border ${cardColor.bg} ${cardColor.border} p-5 transition-all duration-300 group relative overflow-hidden ${
                                        card.clickable !== false ? `hover:scale-[1.02] hover:${cardColor.glow} hover:shadow-lg cursor-pointer` : ''
                                    }`}
                                    onClick={() => navigateToRoute(card.route, card.clickable)}
                                >
                                    {/* Animated background pattern */}
                                    <div className="absolute inset-0 opacity-0 group-hover:opacity-5 transition-opacity duration-300">
                                        <div className="w-full h-full bg-gradient-to-br from-transparent via-white/20 to-transparent"></div>
                                    </div>
                                    
                                    {/* Corner indicators */}
                                    <div className="absolute top-2 right-2 w-1.5 h-1.5 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>
                                    
                                    <div className="relative z-10">
                                        <div className="flex flex-row items-center justify-between space-y-0 pb-3">
                                            <div className={`text-sm font-medium lowercase ${cardColor.accent} font-mono tracking-wide`}>
                                                {card.title.toLowerCase()}
                                            </div>
                                            <div className="text-2xl">{card.emoji}</div>
                                        </div>
                                        <div>
                                            <div className="text-3xl font-bold text-foreground mb-1">{card.value.toLocaleString()}</div>
                                            <p className="text-xs text-muted-foreground lowercase mb-2">
                                                {card.description.toLowerCase()}
                                            </p>
                                            {card.clickable !== false && card.route !== '#' && (
                                                <div className={`flex items-center text-xs ${cardColor.accent} font-mono lowercase transition-all duration-200 group-hover:gap-2`}>
                                                    <span>open {card.title.toLowerCase()}</span>
                                                    <span className="ml-1 group-hover:ml-2 transition-all duration-200">→</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    
                                    {/* Hover effect line */}
                                    <div className="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Enhanced Quick Actions */}
                    <div className="cyber-border bg-card/30 p-6 relative overflow-hidden">
                        {/* Background decoration */}
                        <div className="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-emerald-400/50 to-transparent"></div>
                        
                        <div className="flex items-center gap-3 mb-4">
                            <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
                            <h3 className="text-lg font-medium lowercase text-foreground font-mono">quick actions</h3>
                            <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                        </div>
                        
                        <div className="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
                            <button 
                                className="cyber-button h-auto p-5 flex flex-col gap-2 text-left group relative overflow-hidden bg-gradient-to-br from-emerald-500/5 to-emerald-600/10 border-emerald-500/20"
                                onClick={() => router.visit(route('osce'))}
                            >
                                {/* Button decorations */}
                                <div className="absolute top-2 right-2 w-2 h-2 bg-emerald-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>
                                
                                <div className="flex items-center gap-2 mb-1">
                                    <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                    <span className="text-sm text-emerald-500 lowercase font-mono font-semibold">start osce</span>
                                </div>
                                <span className="text-xs text-muted-foreground lowercase">practice clinical skills</span>
                                
                                {/* Action indicator */}
                                <div className="flex items-center gap-1 mt-2 text-xs text-emerald-500 font-mono group-hover:gap-2 transition-all">
                                    <span>launch training</span>
                                    <span className="group-hover:translate-x-1 transition-transform">▸</span>
                                </div>
                            </button>
                            
                            {/* Additional placeholder actions */}
                            <div className="cyber-border p-5 bg-muted/20 border-dashed border-muted-foreground/20">
                                <div className="flex items-center gap-2 mb-1">
                                    <div className="w-2 h-2 bg-muted-foreground/40 rounded-full"></div>
                                    <span className="text-sm text-muted-foreground lowercase font-mono">coming soon</span>
                                </div>
                                <span className="text-xs text-muted-foreground/60 lowercase">more features</span>
                            </div>
                        </div>
                    </div>

                    {/* System info */}
                    <div className="flex items-center justify-between text-xs text-muted-foreground font-mono">
                        <div className="flex items-center gap-2">
                            <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                            <span>system operational</span>
                        </div>
                        <div className="flex items-center gap-4">
                            <span>uptime: 99.9%</span>
                            <span>latency: &lt;50ms</span>
                        </div>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
