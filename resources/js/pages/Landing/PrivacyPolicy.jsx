import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ThemeProvider } from '@/contexts/ThemeContext';
import ThemeToggle from '@/components/react/ThemeToggle';

function PrivacyPolicy({ auth }) {
    return (
        <ThemeProvider>
            <div className="min-h-screen relative overflow-hidden bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-200 font-mono transition-colors duration-300">
                <Head title="privacy policy - osce simulator" />

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

                {/* Header */}
                <header className="border-b border-neutral-300 dark:border-neutral-800/80 bg-neutral-100/80 dark:bg-neutral-950/60 backdrop-blur supports-[backdrop-filter]:bg-neutral-100/40 supports-[backdrop-filter]:dark:bg-neutral-950/40 transition-colors duration-300">
                    <div className="max-w-7xl mx-auto px-4 py-4">
                        <nav className="flex justify-between items-center">
                            <div className="flex items-center gap-4">
                                <Link href={route('home')} className="text-xl tracking-tight text-emerald-600 dark:text-emerald-400 font-bold relative">
                                    <span className="relative z-10">▌ osce simulator ▐</span>
                                    <div
                                        className="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-emerald-500 to-cyan-400 opacity-60"
                                        style={{
                                            clipPath: 'polygon(0 0, 95% 0, 100% 100%, 5% 100%)'
                                        }}
                                    />
                                </Link>

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
                                            dashboard
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

                {/* Main Content */}
                <main className="max-w-4xl mx-auto px-4 py-12 relative">
                    {/* Page Welcome Header */}
                    <div className="text-center space-y-4 relative mb-12">
                        <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-20 h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent"></div>

                        <div className="flex items-center justify-center gap-3">
                            <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
                            <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">Legal Information</span>
                            <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400"></div>
                        </div>

                        <h1 className="text-2xl font-medium lowercase glow-text text-foreground">privacy policy</h1>
                    </div>

                    {/* Content Sections */}
                    <div className="space-y-8">
                        {/* Data Collection */}
                        <section className="cyber-border bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 p-6 hover:scale-[1.02] transition-all duration-300 group relative">
                            <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                            <div className="flex items-center gap-3 mb-4">
                                <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
                                <h2 className="text-lg font-medium lowercase text-foreground font-mono">data collection</h2>
                                <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                                <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                                    <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                    <span>essential only</span>
                                </div>
                            </div>

                            <div className="text-muted-foreground lowercase space-y-3 text-sm leading-relaxed">
                                <p>we collect minimal data necessary for the osce simulator to function:</p>
                                <ul className="list-disc list-inside space-y-1 ml-4">
                                    <li>authentication data through WorkOS (email, name)</li>
                                    <li>session data (osce case interactions, chat messages)</li>
                                    <li>performance metrics (assessment scores, completion times)</li>
                                    <li>contact form submissions (name, email, message)</li>
                                </ul>
                            </div>
                        </section>

                        {/* Data Usage */}
                        <section className="cyber-border bg-gradient-to-br from-blue-500/10 to-blue-600/5 border-blue-500/30 p-6 hover:scale-[1.02] transition-all duration-300 group relative">
                            <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-blue-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                            <div className="flex items-center gap-3 mb-4">
                                <div className="w-1 h-6 bg-gradient-to-b from-blue-400 to-cyan-400"></div>
                                <h2 className="text-lg font-medium lowercase text-foreground font-mono">data usage</h2>
                                <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                                <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                                    <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                    <span>purpose-limited</span>
                                </div>
                            </div>

                            <div className="text-muted-foreground lowercase space-y-3 text-sm leading-relaxed">
                                <p>your data is used exclusively for:</p>
                                <ul className="list-disc list-inside space-y-1 ml-4">
                                    <li>providing personalized osce training experiences</li>
                                    <li>tracking your learning progress and performance</li>
                                    <li>improving the simulator based on usage patterns</li>
                                    <li>responding to support requests and feedback</li>
                                </ul>
                                <p className="font-mono text-emerald-500">we never sell or share your data with third parties.</p>
                            </div>
                        </section>

                        {/* Data Security */}
                        <section className="cyber-border bg-gradient-to-br from-purple-500/10 to-purple-600/5 border-purple-500/30 p-6 hover:scale-[1.02] transition-all duration-300 group relative">
                            <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-purple-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                            <div className="flex items-center gap-3 mb-4">
                                <div className="w-1 h-6 bg-gradient-to-b from-purple-400 to-cyan-400"></div>
                                <h2 className="text-lg font-medium lowercase text-foreground font-mono">data security</h2>
                                <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                                <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                                    <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></div>
                                    <span>encrypted</span>
                                </div>
                            </div>

                            <div className="text-muted-foreground lowercase space-y-3 text-sm leading-relaxed">
                                <p>we implement industry-standard security measures:</p>
                                <ul className="list-disc list-inside space-y-1 ml-4">
                                    <li>https encryption for all data transmission</li>
                                    <li>secure database storage with regular backups</li>
                                    <li>access controls and audit logging</li>
                                    <li>regular security updates and monitoring</li>
                                </ul>
                            </div>
                        </section>

                        {/* Your Rights */}
                        <section className="cyber-border bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 p-6 hover:scale-[1.02] transition-all duration-300 group relative">
                            <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                            <div className="flex items-center gap-3 mb-4">
                                <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
                                <h2 className="text-lg font-medium lowercase text-foreground font-mono">your rights</h2>
                                <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                                <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                                    <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                    <span>user control</span>
                                </div>
                            </div>

                            <div className="text-muted-foreground lowercase space-y-3 text-sm leading-relaxed">
                                <p>you have the right to:</p>
                                <ul className="list-disc list-inside space-y-1 ml-4">
                                    <li>access your personal data</li>
                                    <li>request data correction or deletion</li>
                                    <li>export your training data</li>
                                    <li>withdraw consent at any time</li>
                                </ul>
                                <p>contact us at <Link href={route('contact')} className="text-emerald-500 hover:text-emerald-400 transition-colors">the contact page</Link> for any privacy-related requests.</p>
                            </div>
                        </section>
                    </div>

                    {/* Status Footer */}
                    <div className="cyber-border bg-card/50 px-6 py-4 text-left text-sm text-neutral-600 dark:text-neutral-400 relative overflow-hidden mt-12">
                        <div className="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent opacity-50">
                            <div className="w-20 h-full bg-gradient-to-r from-transparent via-emerald-400 to-transparent animate-pulse"></div>
                        </div>

                        <div className="flex items-center justify-between">
                            <span className="text-emerald-500 font-mono">last updated:</span>
                            <div className="flex items-center gap-2 text-xs">
                                <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                <span>september 2024 • gdpr compliant • minimal data collection</span>
                            </div>
                        </div>
                    </div>
                </main>

                {/* Footer */}
                <footer className="border-t border-neutral-300 dark:border-neutral-800 bg-neutral-100/80 dark:bg-neutral-950 py-10 transition-colors duration-300">
                    <div className="max-w-7xl mx-auto px-4">
                        <div className="flex flex-col items-center gap-4 text-neutral-600 dark:text-neutral-500 text-sm">
                            <div className="flex gap-6">
                                <Link href={route('home')} className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider">
                                    home
                                </Link>
                                <Link href={route('contact')} className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider">
                                    contact
                                </Link>
                                <Link href={route('made-by')} className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider">
                                    made by
                                </Link>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </ThemeProvider>
    );
}

export default PrivacyPolicy;