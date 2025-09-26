import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ThemeProvider } from '@/contexts/ThemeContext';
import ThemeToggle from '@/components/react/ThemeToggle';

function MadeBy({ auth }) {
    return (
        <ThemeProvider>
            <div className="min-h-screen relative overflow-hidden bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-200 font-mono transition-colors duration-300">
                <Head title="made by bintang - osce simulator" />

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
                        <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-20 h-0.5 bg-gradient-to-r from-transparent via-purple-400 to-transparent"></div>

                        <div className="flex items-center justify-center gap-3">
                            <div className="w-8 h-0.5 bg-gradient-to-r from-purple-400 to-cyan-400"></div>
                            <span className="text-xs text-purple-500 font-mono uppercase tracking-wider">Attribution</span>
                            <div className="w-8 h-0.5 bg-gradient-to-l from-purple-400 to-cyan-400"></div>
                        </div>

                        <h1 className="text-2xl font-medium lowercase glow-text text-foreground">made by bintang</h1>
                        <p className="text-muted-foreground lowercase max-w-2xl mx-auto">
                            developer, medical student, and builder of minimal interfaces that actually work.
                        </p>
                    </div>

                    <div className="grid md:grid-cols-2 gap-8">
                        {/* Developer Info */}
                        <div className="cyber-border bg-gradient-to-br from-purple-500/10 to-purple-600/5 border-purple-500/30 p-6 hover:scale-[1.02] transition-all duration-300 group relative">
                            <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-purple-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                            <div className="flex items-center gap-3 mb-6">
                                <div className="w-1 h-6 bg-gradient-to-b from-purple-400 to-cyan-400"></div>
                                <h2 className="text-lg font-medium lowercase text-foreground font-mono">about the developer</h2>
                                <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                                <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                                    <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></div>
                                    <span>active</span>
                                </div>
                            </div>

                            <div className="space-y-4 text-muted-foreground lowercase text-sm leading-relaxed">
                                <p>
                                    bintang putra is a medical student and full-stack developer who believes
                                    in building tools that solve real problems without unnecessary complexity.
                                </p>

                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                        <span className="font-mono text-emerald-500">focus:</span>
                                        <span>medical education technology</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                        <span className="font-mono text-blue-500">expertise:</span>
                                        <span>laravel, react, minimal design</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></div>
                                        <span className="font-mono text-purple-500">philosophy:</span>
                                        <span>less is more, function over form</span>
                                    </div>
                                </div>

                                <p className="font-mono text-purple-500 text-xs uppercase tracking-wider">
                                    building the future of medical education, one line at a time.
                                </p>
                            </div>
                        </div>

                        {/* Tech Stack & Links */}
                        <div className="space-y-6">
                            {/* Tech Stack */}
                            <div className="cyber-border bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 p-6 hover:scale-[1.02] transition-all duration-300 group relative">
                                <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                                <div className="flex items-center gap-3 mb-4">
                                    <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
                                    <h3 className="text-lg font-medium lowercase text-foreground font-mono">tech stack</h3>
                                </div>

                                <div className="grid grid-cols-2 gap-3 text-sm">
                                    {[
                                        { tech: 'laravel', color: 'text-red-500' },
                                        { tech: 'react', color: 'text-blue-500' },
                                        { tech: 'inertia.js', color: 'text-purple-500' },
                                        { tech: 'tailwindcss', color: 'text-cyan-500' },
                                        { tech: 'postgresql', color: 'text-orange-500' },
                                        { tech: 'supabase', color: 'text-emerald-500' },
                                    ].map((item, idx) => (
                                        <div key={item.tech} className="flex items-center gap-2">
                                            <div className={`w-2 h-2 rounded-full animate-pulse ${item.color.replace('text-', 'bg-')}`}></div>
                                            <span className={`font-mono ${item.color}`}>{item.tech}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* External Links */}
                            <div className="cyber-border bg-gradient-to-br from-blue-500/10 to-blue-600/5 border-blue-500/30 p-6 hover:scale-[1.02] transition-all duration-300 group relative">
                                <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-blue-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                                <div className="flex items-center gap-3 mb-4">
                                    <div className="w-1 h-6 bg-gradient-to-b from-blue-400 to-cyan-400"></div>
                                    <h3 className="text-lg font-medium lowercase text-foreground font-mono">connect</h3>
                                </div>

                                <div className="space-y-3">
                                    <a
                                        href="https://bintangputra.my.id"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex items-center gap-3 group/link hover:text-emerald-500 transition-colors duration-200"
                                    >
                                        <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                        <span className="font-mono text-xs uppercase tracking-wider">personal website</span>
                                        <div className="w-4 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400 scale-x-0 group-hover/link:scale-x-100 transition-transform duration-300"></div>
                                    </a>

                                    <a
                                        href="https://github.com/ChaostixZix"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex items-center gap-3 group/link hover:text-emerald-500 transition-colors duration-200"
                                    >
                                        <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                        <span className="font-mono text-xs uppercase tracking-wider">github profile</span>
                                        <div className="w-4 h-0.5 bg-gradient-to-r from-blue-400 to-cyan-400 scale-x-0 group-hover/link:scale-x-100 transition-transform duration-300"></div>
                                    </a>

                                    <Link
                                        href={route('contact')}
                                        className="flex items-center gap-3 group/link hover:text-emerald-500 transition-colors duration-200"
                                    >
                                        <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></div>
                                        <span className="font-mono text-xs uppercase tracking-wider">send message</span>
                                        <div className="w-4 h-0.5 bg-gradient-to-r from-purple-400 to-cyan-400 scale-x-0 group-hover/link:scale-x-100 transition-transform duration-300"></div>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Project Info */}
                    <div className="cyber-border bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 p-6 hover:scale-[1.02] transition-all duration-300 group relative mt-8">
                        <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                        <div className="flex items-center gap-3 mb-4">
                            <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
                            <h3 className="text-lg font-medium lowercase text-foreground font-mono">about this project</h3>
                            <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                            <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                                <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                <span>open source</span>
                            </div>
                        </div>

                        <div className="text-muted-foreground lowercase text-sm leading-relaxed space-y-3">
                            <p>
                                the osce simulator was born from the frustration of using overcomplicated medical education tools.
                                it's designed to be fast, minimal, and focused on what matters: learning clinical skills.
                            </p>

                            <div className="grid md:grid-cols-3 gap-4 mt-4">
                                <div className="text-center">
                                    <div className="text-emerald-500 font-mono text-lg">2024</div>
                                    <div className="text-xs">first release</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-blue-500 font-mono text-lg">minimal</div>
                                    <div className="text-xs">design philosophy</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-purple-500 font-mono text-lg">secure</div>
                                    <div className="text-xs">supabase auth</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Status Footer */}
                    <div className="cyber-border bg-card/50 px-6 py-4 text-left text-sm text-neutral-600 dark:text-neutral-400 relative overflow-hidden mt-12">
                        <div className="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-purple-400 to-transparent opacity-50">
                            <div className="w-20 h-full bg-gradient-to-r from-transparent via-purple-400 to-transparent animate-pulse"></div>
                        </div>

                        <div className="flex items-center justify-between">
                            <span className="text-purple-500 font-mono">developer status:</span>
                            <div className="flex items-center gap-2 text-xs">
                                <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></div>
                                <span>actively building • medical student • full-stack dev</span>
                            </div>
                        </div>
                    </div>
                </main>

                {/* Footer */}
                <footer className="border-t border-neutral-300 dark:border-neutral-800 bg-neutral-100/80 dark:bg-neutral-950 py-10 transition-colors duration-300">
                    <div className="max-w-7xl mx-auto px-4">
                        <div className="flex flex-col items-center gap-4 text-neutral-600 dark:text-neutral-500 text-sm">
                            <div className="flex items-center gap-2">
                                <div className="w-1 h-4 bg-emerald-500"></div>
                                <span className="font-mono">crafted with care by bintang putra</span>
                                <div className="w-1 h-4 bg-emerald-500"></div>
                            </div>
                            <div className="flex gap-6">
                                <Link href={route('home')} className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider">
                                    home
                                </Link>
                                <Link href={route('contact')} className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider">
                                    contact
                                </Link>
                                <Link href={route('privacy-policy')} className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider">
                                    privacy
                                </Link>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </ThemeProvider>
    );
}

export default MadeBy;
