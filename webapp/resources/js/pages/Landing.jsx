import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@vibe-kanban/ui-kit';

function Landing({ auth }) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title="Welcome to OSCE Simulator" />
            
            {/* Header */}
            <header className="border-b border-border bg-card">
                <div className="max-w-7xl mx-auto px-4 py-4">
                    <nav className="flex justify-between items-center">
                        <div className="text-2xl font-bold text-primary">
                            🩺 OSCE Simulator
                        </div>
                    <div className="space-x-4">
                        {auth && auth.user ? (
                            <Link href={route('dashboard')}>
                                <Button variant="default">Go to Dashboard</Button>
                            </Link>
                        ) : (
                            <Link href={route('login')}>
                                <Button variant="default">Login</Button>
                            </Link>
                        )}
                        </div>
                    </nav>
                </div>
            </header>

            {/* Hero Section */}
            <main className="max-w-7xl mx-auto px-4 py-16">
                <div className="text-center max-w-4xl mx-auto">
                    <h1 className="text-5xl font-bold mb-6">
                        Welcome to <span className="text-primary">OSCE Simulator</span>
                    </h1>
                    
                    <p className="text-xl text-muted-foreground mb-8 leading-relaxed">
                        A comprehensive medical training platform designed for OSCE (Objective Structured Clinical Examination) practice. 
                        Develop clinical skills through simulated patient interactions, physical examinations, and diagnostic reasoning.
                    </p>

                    {/* Key Features */}
                    <div className="grid md:grid-cols-3 gap-8 mt-16 mb-16">
                        <div className="bg-card border p-6">
                            <div className="text-3xl mb-4">🩺</div>
                            <h3 className="text-lg font-semibold mb-2">Clinical Skills Training</h3>
                            <p className="text-muted-foreground">Practice patient interactions, physical examinations, and medical procedures in a safe simulated environment.</p>
                        </div>
                        
                        <div className="bg-card border p-6">
                            <div className="text-3xl mb-4">🧠</div>
                            <h3 className="text-lg font-semibold mb-2">Diagnostic Reasoning</h3>
                            <p className="text-muted-foreground">Develop clinical reasoning skills through case-based scenarios with real-time feedback and assessment.</p>
                        </div>
                        
                        <div className="bg-card border p-6">
                            <div className="text-3xl mb-4">📈</div>
                            <h3 className="text-lg font-semibold mb-2">Performance Tracking</h3>
                            <p className="text-muted-foreground">Monitor your progress across different clinical skills and identify areas for improvement through detailed analytics.</p>
                        </div>
                    </div>

                    {/* CTA Section */}
                    <div className="bg-card border p-8">
                        <h2 className="text-2xl font-bold mb-4">
                            Ready to enhance your clinical skills?
                        </h2>
                        <p className="text-muted-foreground mb-6">
                            Join medical students and healthcare professionals using OSCE Simulator to improve their clinical examination and diagnostic skills.
                        </p>
                        {auth && auth.user ? (
                            <Link href={route('dashboard')}>
                                <Button variant="default" className="text-lg px-8 py-3">
                                    Start Training
                                </Button>
                            </Link>
                        ) : (
                            <Link href={route('login')}>
                                <Button variant="default" className="text-lg px-8 py-3">
                                    Begin OSCE Practice
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>
            </main>

            {/* Footer */}
            <footer className="border-t border-border bg-muted py-12">
                <div className="max-w-7xl mx-auto px-4">
                    <div className="text-center">
                        <h3 className="text-lg font-semibold mb-4">Created by Bintang Putra</h3>
                        <div className="space-x-6">
                            <a 
                                href="http://dev.bintangputra.my.id" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                className="text-primary hover:text-primary/80 transition-colors"
                            >
                                🌐 Personal Website
                            </a>
                            <a 
                                href="https://github.com/ChaostixZix" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                className="text-primary hover:text-primary/80 transition-colors"
                            >
                                🐙 GitHub Profile
                            </a>
                        </div>
                        <div className="mt-8 pt-8 border-t border-border text-muted-foreground">
                            <p>&copy; 2024 OSCE Simulator. Built with Laravel & React.</p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}

export default Landing;
