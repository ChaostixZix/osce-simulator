import React from 'react';
import { Head, Link } from '@inertiajs/react';
import Button from '../lib/ui-kit/primitives/Button';

function Landing({ auth }) {
    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
            <Head title="Welcome to Vibe Kanban" />
            
            {/* Header */}
            <header className="container mx-auto px-6 py-4">
                <nav className="flex justify-between items-center">
                    <div className="text-2xl font-bold text-indigo-600">
                        Vibe Kanban
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
            </header>

            {/* Hero Section */}
            <main className="container mx-auto px-6 py-16">
                <div className="text-center max-w-4xl mx-auto">
                    <h1 className="text-5xl font-bold text-gray-900 mb-6">
                        Welcome to <span className="text-indigo-600">Vibe Kanban</span>
                    </h1>
                    
                    <p className="text-xl text-gray-600 mb-8 leading-relaxed">
                        A modern task and project management platform designed to streamline your workflow. 
                        Organize, track, and collaborate on your projects with an intuitive Kanban-style interface.
                    </p>

                    {/* Key Features */}
                    <div className="grid md:grid-cols-3 gap-8 mt-16 mb-16">
                        <div className="bg-white rounded-lg p-6 shadow-md">
                            <div className="text-3xl mb-4">📋</div>
                            <h3 className="text-lg font-semibold mb-2">Task Management</h3>
                            <p className="text-gray-600">Create, organize, and track tasks with our intuitive Kanban board interface.</p>
                        </div>
                        
                        <div className="bg-white rounded-lg p-6 shadow-md">
                            <div className="text-3xl mb-4">👥</div>
                            <h3 className="text-lg font-semibold mb-2">Team Collaboration</h3>
                            <p className="text-gray-600">Work together seamlessly with team members and track project progress in real-time.</p>
                        </div>
                        
                        <div className="bg-white rounded-lg p-6 shadow-md">
                            <div className="text-3xl mb-4">📊</div>
                            <h3 className="text-lg font-semibold mb-2">Project Insights</h3>
                            <p className="text-gray-600">Get valuable insights and analytics to improve your team's productivity and workflow.</p>
                        </div>
                    </div>

                    {/* CTA Section */}
                    <div className="bg-white rounded-lg p-8 shadow-lg">
                        <h2 className="text-2xl font-bold text-gray-900 mb-4">
                            Ready to get started?
                        </h2>
                        <p className="text-gray-600 mb-6">
                            Join thousands of teams already using Vibe Kanban to manage their projects more effectively.
                        </p>
                        {auth && auth.user ? (
                            <Link href={route('dashboard')}>
                                <Button variant="default" className="text-lg px-8 py-3">
                                    Go to Dashboard
                                </Button>
                            </Link>
                        ) : (
                            <Link href={route('login')}>
                                <Button variant="default" className="text-lg px-8 py-3">
                                    Get Started
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>
            </main>

            {/* Footer */}
            <footer className="bg-gray-900 text-white py-12">
                <div className="container mx-auto px-6">
                    <div className="text-center">
                        <h3 className="text-lg font-semibold mb-4">Created by Bintang Putra</h3>
                        <div className="space-x-6">
                            <a 
                                href="http://dev.bintangputra.my.id" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                className="text-indigo-400 hover:text-indigo-300 transition-colors"
                            >
                                🌐 Personal Website
                            </a>
                            <a 
                                href="https://github.com/ChaostixZix" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                className="text-indigo-400 hover:text-indigo-300 transition-colors"
                            >
                                🐙 GitHub Profile
                            </a>
                        </div>
                        <div className="mt-8 pt-8 border-t border-gray-700 text-gray-400">
                            <p>&copy; 2024 Vibe Kanban. Built with Laravel & React.</p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}

export default Landing;
