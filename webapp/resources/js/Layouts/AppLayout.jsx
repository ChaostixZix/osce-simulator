import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import Breadcrumbs from '@/components/react/Breadcrumbs';
import ThemeToggle from '@/components/react/ThemeToggle';

export default function AppLayout({ children, breadcrumbs = [] }) {
    const { props } = usePage();
    const user = props?.auth?.user;

    return (
        <div className="relative min-h-screen bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-200 flex flex-col transition-colors duration-300">
            {/* Simplified background */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 opacity-[0.01] dark:opacity-[0.02]"
                style={{
                    backgroundImage:
                        "repeating-linear-gradient(0deg, transparent, transparent 23px, #22c55e22 24px), repeating-linear-gradient(90deg, transparent, transparent 23px, #22c55e22 24px)",
                }}
            />

            {/* Header with enhanced gaming aesthetic */}
            <header className="relative border-b border-neutral-300 dark:border-neutral-800/80 bg-neutral-100/80 dark:bg-neutral-950/60 backdrop-blur supports-[backdrop-filter]:bg-neutral-100/40 supports-[backdrop-filter]:dark:bg-neutral-950/40 transition-colors duration-300">
                <div className="max-w-7xl mx-auto px-4 py-4">
                    <div className="flex items-center justify-between gap-6">
                        <div className="flex items-center gap-4">
                            {/* Simplified logo */}
                            <h1 className="text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
                                OSCE Interface
                            </h1>
                            
                            {/* Status indicator */}
                            <div className="flex items-center gap-2">
                                <div className="w-2 h-2 bg-emerald-500 rounded-full" />
                                <span className="text-base text-neutral-600 dark:text-neutral-400">Online</span>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            {user?.is_admin && (
                                <nav className="flex items-center gap-2">
                                    <Link
                                        href={route('admin.osce-cases.index')}
                                        className="clean-button px-3 py-1 text-sm"
                                    >
                                        OSCE Cases
                                    </Link>
                                    <Link
                                        href={route('admin.osce-cases.create')}
                                        className="clean-button primary px-3 py-1 text-sm"
                                    >
                                        New Case
                                    </Link>
                                    <Link
                                        href={route('admin.users.index')}
                                        className="clean-button px-3 py-1 text-sm"
                                    >
                                        Users
                                    </Link>
                                </nav>
                            )}

                            {/* Theme toggle */}
                            <ThemeToggle />
                        </div>
                    </div>
                </div>
            </header>

            {/* Main content with clean styling */}
            <main className="relative max-w-7xl mx-auto px-4 py-6 flex-1 flex min-h-0">
                <div className="clean-card bg-card p-6 flex-1 flex flex-col min-h-0">
                    
                    {breadcrumbs.length > 0 && (
                        <div className="mb-6">
                            <Breadcrumbs items={breadcrumbs} />
                        </div>
                    )}
                    
                    {/* Content area */}
                    <div className="flex-1 min-h-0">{children}</div>
                </div>
            </main>

            {/* Footer with system info */}
            <footer className="relative border-t border-neutral-300 dark:border-neutral-800/80 bg-neutral-100/80 dark:bg-neutral-950/60 backdrop-blur transition-colors duration-300">
                <div className="max-w-7xl mx-auto px-4 py-3">
                    <div className="flex items-center justify-between text-base text-neutral-600 dark:text-neutral-400">
                        <div className="flex items-center gap-4">
                            <span>OSCE v2.1</span>
                            <span className="text-emerald-600 dark:text-emerald-400">●</span>
                            <span>Ready</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-sm opacity-60">Powered by Laravel + Inertia</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
