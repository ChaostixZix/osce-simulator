import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import Breadcrumbs from '@/components/react/Breadcrumbs';
import ThemeToggle from '@/components/react/ThemeToggle';

export default function AppLayout({ children, breadcrumbs = [] }) {
    const { props } = usePage();
    const user = props?.auth?.user;

    return (
        <div className="relative min-h-screen bg-background text-foreground flex flex-col transition-colors duration-300 antialiased">
            {/* Ambient background */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 opacity-[0.04] dark:opacity-[0.06]"
                style={{
                    backgroundImage: `radial-gradient(120% 100% at 0% 0%, hsl(var(--primary) / 0.08), transparent 65%),
                        repeating-linear-gradient(0deg, transparent, transparent 26px, hsl(var(--border) / 0.18) 27px),
                        repeating-linear-gradient(90deg, transparent, transparent 26px, hsl(var(--border) / 0.18) 27px)`,
                }}
            />

            {/* Header - Mobile optimized */}
            <header className="relative border-b border-border/60 bg-card/80 backdrop-blur supports-[backdrop-filter]:bg-card/55 shadow-sm transition-colors duration-300">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
                    <div className="flex items-center justify-between gap-3 sm:gap-6">
                        <div className="flex items-center gap-2 sm:gap-4 min-w-0 flex-1">
                            {/* Identity */}
                            <h1 className="text-lg sm:text-2xl font-semibold text-primary truncate">
                                OSCE Interface
                            </h1>

                            {/* Status indicator */}
                            <div className="hidden sm:flex items-center gap-2">
                                <div className="w-2 h-2 bg-emerald-500 dark:bg-emerald-400 rounded-full" />
                                <span className="text-sm text-muted-foreground">Online</span>
                            </div>
                        </div>

                        <div className="flex items-center gap-2 sm:gap-3">
                            {user?.is_admin && (
                                <nav className="hidden lg:flex items-center gap-2">
                                    <Link
                                        href={route('admin.osce-cases.index')}
                                        className="clean-button px-2 sm:px-3 py-1 text-xs sm:text-sm"
                                    >
                                        OSCE Cases
                                    </Link>
                                    <Link
                                        href={route('admin.osce-cases.create')}
                                        className="clean-button primary px-2 sm:px-3 py-1 text-xs sm:text-sm"
                                    >
                                        New Case
                                    </Link>
                                    <Link
                                        href={route('admin.users.index')}
                                        className="clean-button px-2 sm:px-3 py-1 text-xs sm:text-sm"
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

            {/* Main content with clean styling - Mobile optimized */}
            <main className="relative max-w-7xl mx-auto px-4 sm:px-6 py-4 sm:py-6 lg:py-8 flex-1 flex min-h-0">
                <div className="clean-card bg-card p-4 sm:p-6 flex-1 flex flex-col min-h-0">

                    {breadcrumbs.length > 0 && (
                        <div className="mb-4 sm:mb-6">
                            <Breadcrumbs items={breadcrumbs} />
                        </div>
                    )}

                    {/* Content area */}
                    <div className="flex-1 min-h-0">{children}</div>
                </div>
            </main>

            {/* Footer with system info - Mobile optimized */}
            <footer className="relative border-t border-border/60 bg-card/80 backdrop-blur supports-[backdrop-filter]:bg-card/55 transition-colors duration-300">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 py-3">
                    <div className="flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-2 sm:gap-0 text-xs sm:text-sm text-muted-foreground">
                        <div className="flex items-center gap-2 sm:gap-4">
                            <span>OSCE v2.1</span>
                            <span className="text-emerald-600 dark:text-emerald-400">●</span>
                            <span>Ready</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="opacity-70">Powered by Laravel + Inertia</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
