import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@vibe-kanban/ui-kit';
import { ThemeProvider } from '@/contexts/ThemeContext';
import ThemeToggle from '@/components/react/ThemeToggle';

function Landing({ auth }) {
    return (
        <ThemeProvider>
            <div className="min-h-screen relative overflow-hidden bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-200 font-mono transition-colors duration-300">
                <Head title="osce simulator" />

                {/* Enhanced grid background */}
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-0 opacity-[0.03] dark:opacity-[0.07]"
                    style={{
                        backgroundImage:
                            "repeating-linear-gradient(0deg, transparent, transparent 23px, #22c55e44 24px), repeating-linear-gradient(90deg, transparent, transparent 23px, #22c55e44 24px)",
                    }}
                />

                {/* Animated scan lines */}
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-0 opacity-[0.02] dark:opacity-[0.05]"
                    style={{
                        backgroundImage: "repeating-linear-gradient(0deg, transparent, transparent 1px, #00ff0022 2px)"
                    }}
                />

                {/* Enhanced header */}
                <header className="border-b border-neutral-300 dark:border-neutral-800/80 bg-neutral-100/80 dark:bg-neutral-950/60 backdrop-blur supports-[backdrop-filter]:bg-neutral-100/40 supports-[backdrop-filter]:dark:bg-neutral-950/40 transition-colors duration-300">
                    <div className="max-w-7xl mx-auto px-4 py-4">
                        <nav className="flex justify-between items-center">
                            <div className="flex items-center gap-4">
                                {/* Enhanced logo */}
                                <div className="text-xl tracking-tight text-emerald-600 dark:text-emerald-400 font-bold relative">
                                    <span className="relative z-10">▌ osce simulator ▐</span>
                                    <div 
                                        className="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-emerald-500 to-cyan-400 opacity-60"
                                        style={{
                                            clipPath: 'polygon(0 0, 95% 0, 100% 100%, 5% 100%)'
                                        }}
                                    />
                                </div>
                                
                                {/* Status indicator */}
                                <div className="flex items-center gap-1">
                                    <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" />
                                    <span className="text-xs text-neutral-600 dark:text-neutral-400 uppercase tracking-wide">Live</span>
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <ThemeToggle />
                                {auth && auth.user ? (
                                    <Link href={route('dashboard')}>
                                        <button className="cyber-button px-4 py-2 text-sm font-mono text-emerald-600 dark:text-emerald-300 uppercase tracking-wide">
                                            go to dashboard
                                        </button>
                                    </Link>
                                ) : (
                                    <Link href={route('login')}>
                                        <button className="cyber-button px-4 py-2 text-sm font-mono text-emerald-600 dark:text-emerald-300 uppercase tracking-wide">
                                            login
                                        </button>
                                    </Link>
                                )}
                            </div>
                        </nav>
                    </div>
                </header>

            {/* Enhanced hero */}
            <main className="max-w-7xl mx-auto px-4 py-20 relative">
                <div className="text-center max-w-4xl mx-auto">
                    {/* Glitch effect on title */}
                    <h1 className="text-4xl md:text-5xl font-medium mb-4 leading-tight glow-text">
                        a flat, techy osce training interface
                    </h1>
                    <p className="text-base md:text-lg text-neutral-600 dark:text-neutral-400 mb-10 leading-relaxed">
                        build clinical skill through structured, simulated sessions. fast. minimal. no noise.
                    </p>

                    {/* Enhanced CTA */}
                    <div className="flex flex-col sm:flex-row gap-3 justify-center mb-16">
                        {auth && auth.user ? (
                            <Link href={route('dashboard')}>
                                <button className="cyber-button px-8 py-3 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm">
                                    start training
                                </button>
                            </Link>
                        ) : (
                            <Link href={route('login')}>
                                <button className="cyber-button px-8 py-3 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm">
                                    start training
                                </button>
                            </Link>
                        )}
                    </div>

                    {/* Enhanced feature grid */}
                    <div className="grid md:grid-cols-3 gap-6 mt-16 mb-8">
                        {[
                            { 
                                k: 'skills', 
                                d: 'simulated patient flow and procedures',
                                color: 'border-emerald-500/20 bg-emerald-500/5',
                                accent: 'text-emerald-500'
                            },
                            { 
                                k: 'reasoning', 
                                d: 'clinical decision scaffolding',
                                color: 'border-blue-500/20 bg-blue-500/5',
                                accent: 'text-blue-500'
                            },
                            { 
                                k: 'tracking', 
                                d: 'progress metrics that matter',
                                color: 'border-purple-500/20 bg-purple-500/5',
                                accent: 'text-purple-500'
                            },
                        ].map((f, idx) => (
                            <div
                                key={f.k}
                                className={`cyber-border p-6 text-left ${f.color} hover:scale-[1.02] transition-all duration-300 relative group`}
                                style={{
                                    animationDelay: `${idx * 200}ms`
                                }}
                            >
                                {/* Corner accent */}
                                <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>
                                
                                <div className={`text-sm ${f.accent} mb-2 font-mono uppercase tracking-wider`}>{f.k}</div>
                                <div className="text-neutral-700 dark:text-neutral-300 text-sm leading-relaxed">{f.d}</div>
                                
                                {/* Hover indicator */}
                                <div className="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                            </div>
                        ))}
                    </div>

                    {/* Enhanced status bar */}
                    <div 
                        className="mx-auto max-w-xl mt-10 cyber-border bg-card/50 px-6 py-4 text-left text-sm text-neutral-600 dark:text-neutral-400 relative overflow-hidden"
                    >
                        {/* Animated scan line */}
                        <div className="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent opacity-50">
                            <div className="w-20 h-full bg-gradient-to-r from-transparent via-emerald-400 to-transparent animate-pulse"></div>
                        </div>
                        
                        <div className="flex items-center justify-between">
                            <span className="text-emerald-500 font-mono">status:</span>
                            <div className="flex items-center gap-2 text-xs">
                                <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                <span>ready • secure • minimal interface • terminal font</span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            {/* Enhanced footer */}
            <footer className="border-t border-neutral-300 dark:border-neutral-800 bg-neutral-100/80 dark:bg-neutral-950 py-10 transition-colors duration-300">
                <div className="max-w-7xl mx-auto px-4">
                    <div className="flex flex-col items-center gap-4 text-neutral-600 dark:text-neutral-500 text-sm">
                        <div className="flex items-center gap-2">
                            <div className="w-1 h-4 bg-emerald-500"></div>
                            <span className="font-mono">crafted by bintang putra</span>
                            <div className="w-1 h-4 bg-emerald-500"></div>
                        </div>
                        <div className="flex gap-6">
                            <Link href={route('contact')} className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider">
                                contact
                            </Link>
                            <Link href={route('privacy-policy')} className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider">
                                privacy
                            </Link>
                            <Link href={route('made-by')} className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider">
                                made by
                            </Link>
                            <a
                                href="https://github.com/ChaostixZix"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider"
                            >
                                github
                            </a>
                        </div>
                        <div className="text-neutral-500 dark:text-neutral-600 text-xs font-mono">
                            © 2024 osce simulator • laravel + react • 
                            <span className="text-emerald-500 ml-1">v2.1</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        </ThemeProvider>
    );
}

export default Landing;
