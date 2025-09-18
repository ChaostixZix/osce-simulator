import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ThemeProvider } from '@/contexts/ThemeContext';
import ThemeToggle from '@/components/react/ThemeToggle';

function Contact({ auth, flash }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        subject: '',
        message: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('contact.submit'), {
            onSuccess: () => reset(),
        });
    };

    return (
        <ThemeProvider>
            <div className="min-h-screen relative overflow-hidden bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-200 font-mono transition-colors duration-300">
                <Head title="contact us - osce simulator" />

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
                        <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-20 h-0.5 bg-gradient-to-r from-transparent via-blue-400 to-transparent"></div>

                        <div className="flex items-center justify-center gap-3">
                            <div className="w-8 h-0.5 bg-gradient-to-r from-blue-400 to-cyan-400"></div>
                            <span className="text-xs text-blue-500 font-mono uppercase tracking-wider">Communication</span>
                            <div className="w-8 h-0.5 bg-gradient-to-l from-blue-400 to-cyan-400"></div>
                        </div>

                        <h1 className="text-2xl font-medium lowercase glow-text text-foreground">contact us</h1>
                        <p className="text-muted-foreground lowercase max-w-2xl mx-auto">
                            got feedback, questions, or issues? send us a message and we'll get back to you as soon as possible.
                        </p>
                    </div>

                    {/* Success Message */}
                    {flash?.success && (
                        <div className="cyber-border bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 border-emerald-500/50 p-4 mb-8 relative group">
                            <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-100"></div>
                            <div className="flex items-center gap-2">
                                <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                <span className="text-emerald-500 font-mono text-sm lowercase">{flash.success}</span>
                            </div>
                        </div>
                    )}

                    <div className="grid md:grid-cols-3 gap-8">
                        {/* Contact Form */}
                        <div className="md:col-span-2">
                            <form onSubmit={handleSubmit} className="cyber-border bg-gradient-to-br from-blue-500/10 to-blue-600/5 border-blue-500/30 p-6 hover:scale-[1.01] transition-all duration-300 group relative">
                                <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-blue-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                                <div className="flex items-center gap-3 mb-6">
                                    <div className="w-1 h-6 bg-gradient-to-b from-blue-400 to-cyan-400"></div>
                                    <h2 className="text-lg font-medium lowercase text-foreground font-mono">send message</h2>
                                    <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                                    <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                                        <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                        <span>secure form</span>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    {/* Name Field */}
                                    <div>
                                        <label htmlFor="name" className="block text-sm font-mono text-muted-foreground mb-2 lowercase">
                                            name *
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className="cyber-border bg-background border-border px-4 py-2 w-full text-foreground font-mono text-sm focus:border-blue-500/50 focus:outline-none transition-colors"
                                            placeholder="your name"
                                            required
                                        />
                                        {errors.name && <p className="text-red-500 text-xs font-mono mt-1">{errors.name}</p>}
                                    </div>

                                    {/* Email Field */}
                                    <div>
                                        <label htmlFor="email" className="block text-sm font-mono text-muted-foreground mb-2 lowercase">
                                            email *
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className="cyber-border bg-background border-border px-4 py-2 w-full text-foreground font-mono text-sm focus:border-blue-500/50 focus:outline-none transition-colors"
                                            placeholder="your.email@domain.com"
                                            required
                                        />
                                        {errors.email && <p className="text-red-500 text-xs font-mono mt-1">{errors.email}</p>}
                                    </div>

                                    {/* Subject Field */}
                                    <div>
                                        <label htmlFor="subject" className="block text-sm font-mono text-muted-foreground mb-2 lowercase">
                                            subject *
                                        </label>
                                        <input
                                            type="text"
                                            id="subject"
                                            value={data.subject}
                                            onChange={(e) => setData('subject', e.target.value)}
                                            className="cyber-border bg-background border-border px-4 py-2 w-full text-foreground font-mono text-sm focus:border-blue-500/50 focus:outline-none transition-colors"
                                            placeholder="what's this about?"
                                            required
                                        />
                                        {errors.subject && <p className="text-red-500 text-xs font-mono mt-1">{errors.subject}</p>}
                                    </div>

                                    {/* Message Field */}
                                    <div>
                                        <label htmlFor="message" className="block text-sm font-mono text-muted-foreground mb-2 lowercase">
                                            message *
                                        </label>
                                        <textarea
                                            id="message"
                                            rows="6"
                                            value={data.message}
                                            onChange={(e) => setData('message', e.target.value)}
                                            className="cyber-border bg-background border-border px-4 py-2 w-full text-foreground font-mono text-sm focus:border-blue-500/50 focus:outline-none transition-colors resize-none"
                                            placeholder="your message here..."
                                            required
                                        ></textarea>
                                        {errors.message && <p className="text-red-500 text-xs font-mono mt-1">{errors.message}</p>}
                                    </div>

                                    {/* Submit Button */}
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="cyber-button px-6 py-2 text-blue-600 dark:text-blue-300 font-mono uppercase tracking-wide disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'sending...' : 'send message'}
                                    </button>
                                </div>
                            </form>
                        </div>

                        {/* Contact Info */}
                        <div className="space-y-6">
                            {/* Response Time */}
                            <div className="cyber-border bg-gradient-to-br from-purple-500/10 to-purple-600/5 border-purple-500/30 p-6 hover:scale-[1.02] transition-all duration-300 group relative">
                                <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-purple-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                                <div className="flex items-center gap-3 mb-4">
                                    <div className="w-1 h-6 bg-gradient-to-b from-purple-400 to-cyan-400"></div>
                                    <h3 className="text-lg font-medium lowercase text-foreground font-mono">response time</h3>
                                </div>

                                <div className="text-muted-foreground lowercase text-sm space-y-2">
                                    <div className="flex items-center gap-2">
                                        <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                        <span>typically within 24 hours</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                        <span>priority support for urgent issues</span>
                                    </div>
                                </div>
                            </div>

                            {/* Alternative Contact */}
                            <div className="cyber-border bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 p-6 hover:scale-[1.02] transition-all duration-300 group relative">
                                <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                                <div className="flex items-center gap-3 mb-4">
                                    <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
                                    <h3 className="text-lg font-medium lowercase text-foreground font-mono">other ways to reach us</h3>
                                </div>

                                <div className="text-muted-foreground lowercase text-sm space-y-3">
                                    <div>
                                        <p className="font-mono text-emerald-500 mb-1">github issues</p>
                                        <p>for bug reports and feature requests</p>
                                    </div>
                                    <div>
                                        <p className="font-mono text-emerald-500 mb-1">direct email</p>
                                        <p>bintang@domain.com for urgent matters</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Status Footer */}
                    <div className="cyber-border bg-card/50 px-6 py-4 text-left text-sm text-neutral-600 dark:text-neutral-400 relative overflow-hidden mt-12">
                        <div className="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-blue-400 to-transparent opacity-50">
                            <div className="w-20 h-full bg-gradient-to-r from-transparent via-blue-400 to-transparent animate-pulse"></div>
                        </div>

                        <div className="flex items-center justify-between">
                            <span className="text-blue-500 font-mono">form status:</span>
                            <div className="flex items-center gap-2 text-xs">
                                <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                <span>secure • encrypted • gdpr compliant</span>
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
                                <Link href={route('privacy-policy')} className="hover:text-emerald-500 transition-colors duration-200 font-mono text-xs uppercase tracking-wider">
                                    privacy
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

export default Contact;