import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Landing({ auth }) {
    const portfolioItems = [
        {
            title: 'Portfolio Item One',
            description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            image: 'https://via.placeholder.com/400x250',
        },
        {
            title: 'Portfolio Item Two',
            description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            image: 'https://via.placeholder.com/400x250',
        },
        {
            title: 'Portfolio Item Three',
            description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            image: 'https://via.placeholder.com/400x250',
        },
    ];

    const [currentPortfolio, setCurrentPortfolio] = useState(0);

    const nextPortfolio = () => {
        setCurrentPortfolio((current) => (current + 1) % portfolioItems.length);
    };

    const prevPortfolio = () => {
        setCurrentPortfolio((current) => (current - 1 + portfolioItems.length) % portfolioItems.length);
    };

    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-sidebar-border/70">
                    <div className="mx-auto flex h-14 w-full items-center justify-end px-4 md:max-w-6xl">
                        {auth.user ? (
                            <Link href="/dashboard">
                                <button className="border border-input bg-background px-4 py-2 text-sm hover:bg-accent hover:text-accent-foreground">
                                    Dashboard
                                </button>
                            </Link>
                        ) : (
                            <Link href="/login">
                                <button className="border border-input bg-background px-4 py-2 text-sm hover:bg-accent hover:text-accent-foreground">
                                    Log in
                                </button>
                            </Link>
                        )}
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-6xl flex-1 items-center px-4 py-10">
                    <div className="flex w-full flex-col gap-6">
                        {/* About the Creator Card - Full Width */}
                        <div className="bg-card text-card-foreground border border-sidebar-border/70 p-6">
                            <div className="space-y-4">
                                <div className="flex items-center gap-2">
                                    <span className="bg-primary text-primary-foreground px-2 py-1 text-xs">Personal</span>
                                    <span className="border border-input bg-background px-2 py-1 text-xs">Project</span>
                                </div>
                                <h2 className="text-2xl font-bold">About the Creator — Bintang Putra</h2>
                                <p className="text-muted-foreground">
                                    Medical student with a passion for technology and programming, specializing in PHP and web development.
                                </p>
                            </div>
                            <div className="space-y-4 text-sm leading-6 text-muted-foreground mt-4">
                                <p>
                                    Hi! I'm currently a medical student immersed in clinical rotations, but I also have a deep passion for technology and
                                    programming. This project represents the intersection of my medical background and my technical skills, creating tools
                                    that can help fellow medical students in their learning journey.
                                </p>
                                <div>
                                    <a
                                        href="https://github.com/ChaostixZix"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1 text-primary hover:underline"
                                    >
                                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.30.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                                        </svg>
                                        Check my GitHub profile for more projects
                                    </a>
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-3 mt-6">
                                <Link href="/dashboard">
                                    <button className="bg-primary text-primary-foreground px-6 py-3 text-sm hover:bg-primary/90">
                                        Go to Dashboard
                                    </button>
                                </Link>
                                <Link href="/osce">
                                    <button className="text-foreground hover:bg-accent hover:text-accent-foreground px-6 py-3 text-sm">
                                        Try OSCE Training
                                    </button>
                                </Link>
                            </div>
                        </div>

                        {/* Portfolio Carousel */}
                        <div className="bg-card text-card-foreground border border-sidebar-border/70 p-6">
                            <div className="space-y-4">
                                <h2 className="text-xl font-bold">Portfolio</h2>
                                <p className="text-muted-foreground">A glimpse of my work (placeholders)</p>
                            </div>
                            <div className="flex items-center gap-4 mt-4">
                                <button 
                                    className="border border-input bg-background p-2 hover:bg-accent hover:text-accent-foreground"
                                    onClick={prevPortfolio}
                                >
                                    <span className="sr-only">Previous</span>
                                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="m15 18-6-6 6-6" />
                                    </svg>
                                </button>
                                <div className="flex w-full flex-col items-center">
                                    <img src={portfolioItems[currentPortfolio].image} alt="" className="mb-4 w-full max-w-sm" />
                                    <h3 className="text-lg font-medium">
                                        {portfolioItems[currentPortfolio].title}
                                    </h3>
                                    <p className="mt-2 text-center text-sm text-muted-foreground">
                                        {portfolioItems[currentPortfolio].description}
                                    </p>
                                </div>
                                <button 
                                    className="border border-input bg-background p-2 hover:bg-accent hover:text-accent-foreground"
                                    onClick={nextPortfolio}
                                >
                                    <span className="sr-only">Next</span>
                                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {/* Academic History */}
                        <div className="bg-card text-card-foreground border border-sidebar-border/70 p-6">
                            <div className="space-y-4">
                                <h2 className="text-xl font-bold">Academic History</h2>
                                <p className="text-muted-foreground">Where I've studied and contributed</p>
                            </div>
                            <div className="space-y-2 text-sm leading-6 text-muted-foreground mt-4">
                                <ul className="list-disc space-y-1 pl-5">
                                    <li><strong>High School:</strong> Lorem Ipsum High School (2015-2018)</li>
                                    <li><strong>Organization:</strong> Student Council Member</li>
                                    <li><strong>Organization:</strong> Science Club Treasurer</li>
                                </ul>
                            </div>
                        </div>

                        {/* Project Features - Two Column Layout */}
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="bg-card text-card-foreground border border-sidebar-border/70 p-6">
                                <div className="space-y-4">
                                    <h2 className="text-xl font-bold">OSCE Training Module</h2>
                                    <p className="text-muted-foreground">Interactive medical examination practice</p>
                                </div>
                                <div className="space-y-3 text-sm text-muted-foreground mt-4">
                                    <p>
                                        The OSCE (Objective Structured Clinical Examination) module provides realistic patient scenarios for medical students
                                        to practice their clinical skills and examination techniques.
                                    </p>
                                    <ul className="list-disc space-y-1 pl-5">
                                        <li>AI-powered patient interactions</li>
                                        <li>Structured clinical scenarios</li>
                                        <li>Real-time feedback and scoring</li>
                                        <li>Progress tracking and analytics</li>
                                    </ul>
                                </div>
                            </div>

                            <div className="bg-card text-card-foreground border border-sidebar-border/70 p-6">
                                <div className="space-y-4">
                                    <h2 className="text-xl font-bold">Technical Features</h2>
                                    <p className="text-muted-foreground">Built with modern web technologies</p>
                                </div>
                                <div className="space-y-3 text-sm text-muted-foreground mt-4">
                                    <p>
                                        This application showcases modern web development practices, combining Laravel's robust backend with React.js for a
                                        seamless user experience.
                                    </p>
                                    <ul className="list-disc space-y-1 pl-5">
                                        <li>Laravel 12 with Inertia.js for SPA navigation</li>
                                        <li>React with modern hooks support</li>
                                        <li>Vibe Kanban UI Kit for consistent components</li>
                                        <li>Tailwind CSS v4 for responsive design</li>
                                        <li>Dark mode support throughout</li>
                                    </ul>
                                    <p className="mt-3 text-xs">This is a personal, evolving project. Features may change, and sections might be experimental.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}