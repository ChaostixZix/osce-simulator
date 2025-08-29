import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@vibe-kanban/ui-kit';

function Landing({ auth }) {
    return (
        <div className="min-h-screen relative overflow-hidden bg-neutral-950 text-neutral-200 font-mono">
            <Head title="osce simulator" />

            {/* subtle grid background */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 opacity-[0.07]"
                style={{
                    backgroundImage:
                        "repeating-linear-gradient(0deg, transparent, transparent 23px, #22c55e22 24px), repeating-linear-gradient(90deg, transparent, transparent 23px, #22c55e22 24px)",
                }}
            />

            {/* header */}
            <header className="border-b border-neutral-800/80 bg-neutral-950/60 backdrop-blur supports-[backdrop-filter]:bg-neutral-950/40">
                <div className="max-w-7xl mx-auto px-4 py-4">
                    <nav className="flex justify-between items-center">
                        <div className="text-xl tracking-tight text-emerald-400">
                            ▌ osce simulator ▐
                        </div>
                        <div className="space-x-3">
                            {auth && auth.user ? (
                                <Link href={route('dashboard')}>
                                    <Button
                                        variant="default"
                                        className="px-4 py-2 bg-neutral-900 border border-emerald-500/30 text-emerald-300 hover:bg-neutral-800 hover:border-emerald-400/60 focus:ring-emerald-500/40"
                                    >
                                        go to dashboard
                                    </Button>
                                </Link>
                            ) : (
                                <Link href={route('login')}>
                                    <Button
                                        variant="default"
                                        className="px-4 py-2 bg-neutral-900 border border-emerald-500/30 text-emerald-300 hover:bg-neutral-800 hover:border-emerald-400/60 focus:ring-emerald-500/40"
                                    >
                                        login
                                    </Button>
                                </Link>
                            )}
                        </div>
                    </nav>
                </div>
            </header>

            {/* hero */}
            <main className="max-w-7xl mx-auto px-4 py-20">
                <div className="text-center max-w-4xl mx-auto">
                    <h1 className="text-4xl md:text-5xl font-medium mb-4 leading-tight">
                        a flat, techy osce training interface
                    </h1>
                    <p className="text-base md:text-lg text-neutral-400 mb-10 leading-relaxed">
                        build clinical skill through structured, simulated sessions. fast. minimal. no noise.
                    </p>

                    {/* quick actions */}
                    <div className="flex flex-col sm:flex-row gap-3 justify-center">
                        {auth && auth.user ? (
                            <Link href={route('dashboard')}>
                                <Button className="w-full sm:w-auto px-6 py-3 bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/15">
                                    start training
                                </Button>
                            </Link>
                        ) : (
                            <Link href={route('login')}>
                                <Button className="w-full sm:w-auto px-6 py-3 bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/15">
                                    begin osce practice
                                </Button>
                            </Link>
                        )}
                    </div>

                    {/* feature grid */}
                    <div className="grid md:grid-cols-3 gap-4 mt-16 mb-8">
                        {[
                            { k: 'skills', d: 'simulated patient flow and procedures' },
                            { k: 'reasoning', d: 'clinical decision scaffolding' },
                            { k: 'tracking', d: 'progress metrics that matter' },
                        ].map((f) => (
                            <div
                                key={f.k}
                                className="bg-neutral-900/60 border border-neutral-800 p-5 text-left hover:border-emerald-600/30 transition-colors"
                            >
                                <div className="text-sm text-emerald-400 mb-1">{f.k}</div>
                                <div className="text-neutral-300">{f.d}</div>
                            </div>
                        ))}
                    </div>

                    {/* status bar */}
                    <div className="mx-auto max-w-xl mt-10 rounded border border-neutral-800 bg-neutral-900/50 px-4 py-3 text-left text-sm text-neutral-400">
                        <span className="text-emerald-400">status:</span> ready • secure • minimal interface • terminal font
                    </div>
                </div>
            </main>

            {/* footer */}
            <footer className="border-t border-neutral-800 bg-neutral-950 py-10">
                <div className="max-w-7xl mx-auto px-4">
                    <div className="flex flex-col items-center gap-3 text-neutral-500 text-sm">
                        <div>crafted by bintang putra</div>
                        <div className="flex gap-4">
                            <a
                                href="http://dev.bintangputra.my.id"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="hover:text-neutral-300"
                            >
                                personal site
                            </a>
                            <a
                                href="https://github.com/ChaostixZix"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="hover:text-neutral-300"
                            >
                                github
                            </a>
                        </div>
                        <div className="text-neutral-600">© 2024 osce simulator • laravel + react</div>
                    </div>
                </div>
            </footer>
        </div>
    );
}

export default Landing;
