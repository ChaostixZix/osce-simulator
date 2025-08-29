import React from 'react';
import Breadcrumbs from '@/components/react/Breadcrumbs';

export default function AppLayout({ children, breadcrumbs = [] }) {
    return (
        <div className="relative min-h-screen bg-neutral-950 text-neutral-200 font-mono">
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
            <header className="relative border-b border-neutral-800/80 bg-neutral-950/60 backdrop-blur supports-[backdrop-filter]:bg-neutral-950/40">
                <div className="max-w-7xl mx-auto px-4 py-4">
                    <h1 className="text-xl tracking-tight text-emerald-400 lowercase">▌ osce interface ▐</h1>
                </div>
            </header>

            {/* breadcrumbs */}
            {breadcrumbs.length > 0 && (
                <div className="relative max-w-7xl mx-auto px-4 py-2">
                    <Breadcrumbs items={breadcrumbs} />
                </div>
            )}

            {/* main content */}
            <main className="relative max-w-7xl mx-auto px-4 py-6">
                <div className="border border-neutral-800 bg-neutral-900/50 p-6">
                    {children}
                </div>
            </main>
        </div>
    );
}
