import React from 'react';
import Breadcrumbs from '@/components/react/Breadcrumbs';
import ThemeToggle from '@/components/react/ThemeToggle';

export default function AppLayout({ children, breadcrumbs = [] }) {
    return (
        <div className="relative min-h-screen bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-200 font-mono flex flex-col transition-colors duration-300">
            {/* Animated grid background */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 opacity-[0.03] dark:opacity-[0.07]"
                style={{
                    backgroundImage:
                        "repeating-linear-gradient(0deg, transparent, transparent 23px, #22c55e44 24px), repeating-linear-gradient(90deg, transparent, transparent 23px, #22c55e44 24px)",
                }}
            />

            {/* Subtle scan lines effect */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 opacity-[0.02] dark:opacity-[0.05]"
                style={{
                    backgroundImage: "repeating-linear-gradient(0deg, transparent, transparent 1px, #00ff0022 2px)"
                }}
            />

            {/* Header with enhanced gaming aesthetic */}
            <header className="relative border-b border-neutral-300 dark:border-neutral-800/80 bg-neutral-100/80 dark:bg-neutral-950/60 backdrop-blur supports-[backdrop-filter]:bg-neutral-100/40 supports-[backdrop-filter]:dark:bg-neutral-950/40 transition-colors duration-300">
                <div className="max-w-7xl mx-auto px-4 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            {/* Logo with glitch effect */}
                            <h1 className="text-xl tracking-tight text-emerald-600 dark:text-emerald-400 lowercase font-bold relative">
                                <span className="relative z-10">▌ osce interface ▐</span>
                                {/* Glitch underline */}
                                <div 
                                    className="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-emerald-500 to-cyan-400 opacity-60"
                                    style={{
                                        clipPath: 'polygon(0 0, 95% 0, 100% 100%, 5% 100%)'
                                    }}
                                />
                            </h1>
                            
                            {/* Status indicator */}
                            <div className="flex items-center gap-1">
                                <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" />
                                <span className="text-xs text-neutral-600 dark:text-neutral-400 uppercase tracking-wide">Online</span>
                            </div>
                        </div>

                        {/* Theme toggle */}
                        <ThemeToggle />
                    </div>
                </div>
            </header>

            {/* Main content with enhanced styling */}
            <main className="relative max-w-7xl mx-auto px-4 py-6 flex-1 flex min-h-0">
                <div 
                    className="border border-neutral-300 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50 p-6 flex-1 flex flex-col min-h-0 backdrop-blur-sm transition-colors duration-300 relative overflow-hidden"
                    style={{
                        clipPath: 'polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 12px 100%, 0 calc(100% - 12px))'
                    }}
                >
                    {/* Inner glow effect */}
                    <div className="absolute inset-0 border border-emerald-500/10 dark:border-emerald-400/10 pointer-events-none"
                         style={{
                             clipPath: 'polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 12px 100%, 0 calc(100% - 12px))'
                         }} 
                    />
                    
                    {breadcrumbs.length > 0 && (
                        <div className="mb-4 relative z-10">
                            <Breadcrumbs items={breadcrumbs} />
                        </div>
                    )}
                    
                    {/* Content area */}
                    <div className="flex-1 min-h-0 relative z-10">{children}</div>
                </div>
            </main>

            {/* Footer with system info */}
            <footer className="relative border-t border-neutral-300 dark:border-neutral-800/80 bg-neutral-100/80 dark:bg-neutral-950/60 backdrop-blur transition-colors duration-300">
                <div className="max-w-7xl mx-auto px-4 py-2">
                    <div className="flex items-center justify-between text-xs text-neutral-600 dark:text-neutral-400">
                        <div className="flex items-center gap-4">
                            <span className="font-mono">SYS.OSCE.v2.1</span>
                            <span className="text-emerald-600 dark:text-emerald-400">●</span>
                            <span>READY</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-[10px] opacity-60">POWERED BY LARAVEL + INERTIA</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
