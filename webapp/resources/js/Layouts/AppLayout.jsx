import React from 'react';
import Breadcrumbs from '@/components/react/Breadcrumbs';
// import { Card, CardContent, CardHeader, CardTitle } from '@vibe-kanban/ui-kit';

export default function AppLayout({ children, breadcrumbs = [] }) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            {/* Simple header */}
            <header className="border-b border-border bg-card">
                <div className="max-w-7xl mx-auto px-4 py-4">
                    <h1 className="text-xl font-semibold">OSCE Application</h1>
                </div>
            </header>

            {/* Breadcrumbs if provided */}
            {breadcrumbs.length > 0 && (
                <div className="max-w-7xl mx-auto px-4 py-2">
                    <Breadcrumbs items={breadcrumbs} />
                </div>
            )}

            {/* Main content */}
            <main className="max-w-7xl mx-auto px-4 py-6">
                <div className="bg-card text-card-foreground border p-6"> {/* Square corners */}
                    {children}
                </div>
            </main>
        </div>
    );
}
