import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ThemeToggle from '@/components/react/ThemeToggle';
import { AnimatePresence, motion, useScroll, useTransform } from 'framer-motion';

function Landing({ auth }) {
    const [mousePosition, setMousePosition] = useState({ x: 0, y: 0 });
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const { scrollYProgress } = useScroll();
    const backgroundY = useTransform(scrollYProgress, [0, 1], ['0%', '50%']);
    const heroOpacity = useTransform(scrollYProgress, [0, 0.3, 0.5], [1, 0.5, 0]);

    useEffect(() => {
        const updateMousePosition = (e) => {
            setMousePosition({ x: e.clientX, y: e.clientY });
        };
        window.addEventListener('mousemove', updateMousePosition);
        return () => window.removeEventListener('mousemove', updateMousePosition);
    }, []);

    useEffect(() => {
        const mediaQuery = window.matchMedia('(min-width: 768px)');
        const handleChange = (event) => {
            if (event.matches) {
                setIsMenuOpen(false);
            }
        };

        mediaQuery.addEventListener('change', handleChange);
        return () => mediaQuery.removeEventListener('change', handleChange);
    }, []);

    const isAuthenticated = Boolean(auth?.user);

    // Advanced Animation Variants
    const containerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: {
                duration: 0.8,
                staggerChildren: 0.15,
                delayChildren: 0.2
            }
        }
    };

    const heroVariants = {
        hidden: { opacity: 0, y: 60, scale: 0.95 },
        visible: {
            opacity: 1,
            y: 0,
            scale: 1,
            transition: {
                duration: 1.2,
                ease: [0.23, 1, 0.32, 1]
            }
        }
    };

    const itemVariants = {
        hidden: { opacity: 0, y: 40 },
        visible: {
            opacity: 1,
            y: 0,
            transition: {
                duration: 0.8,
                ease: [0.23, 1, 0.32, 1]
            }
        }
    };

    const cardVariants = {
        hidden: { opacity: 0, y: 60, rotateX: 15 },
        visible: {
            opacity: 1,
            y: 0,
            rotateX: 0,
            transition: {
                duration: 0.8,
                ease: [0.23, 1, 0.32, 1]
            }
        },
        hover: {
            scale: 1.03,
            y: -8,
            rotateX: 5,
            transition: {
                duration: 0.4,
                ease: [0.23, 1, 0.32, 1]
            }
        }
    };

    const buttonVariants = {
        rest: { scale: 1 },
        hover: {
            scale: 1.05,
            transition: {
                duration: 0.3,
                ease: [0.23, 1, 0.32, 1]
            }
        },
        tap: {
            scale: 0.95,
            transition: {
                duration: 0.1
            }
        }
    };

    const staggerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: {
                staggerChildren: 0.1,
                delayChildren: 0.2
            }
        }
    };

    const slideInVariants = {
        hidden: { opacity: 0, x: -60 },
        visible: {
            opacity: 1,
            x: 0,
            transition: {
                duration: 0.8,
                ease: [0.23, 1, 0.32, 1]
            }
        }
    };

    const fadeInVariants = {
        hidden: { opacity: 0, scale: 0.9 },
        visible: {
            opacity: 1,
            scale: 1,
            transition: {
                duration: 0.6,
                ease: [0.23, 1, 0.32, 1]
            }
        }
    };

    // Simplified content - only key features and testimonials
    const keyFeatures = [
        {
            icon: "🤖",
            title: "AI-Powered Training",
            description: "Advanced AI provides real-time feedback on your clinical reasoning and decision-making process."
        },
        {
            icon: "📊",
            title: "Progress Analytics",
            description: "Track your improvement with comprehensive analytics and personalized study recommendations."
        },
        {
            icon: "⚡",
            title: "Instant Feedback",
            description: "Get immediate, detailed feedback to accelerate your learning and identify knowledge gaps."
        }
    ];

    const testimonials = [
        {
            content: "This platform revolutionized my OSCE preparation. The AI feedback helped me identify blind spots I never knew I had.",
            name: "Sarah Chen",
            role: "Medical Student, Johns Hopkins"
        },
        {
            content: "The realistic simulations made me feel confident and prepared for my actual OSCE exams. Absolutely game-changing.",
            name: "Marcus Rodriguez",
            role: "Resident, Mayo Clinic"
        },
        {
            content: "None come close to the depth and quality of training provided here. It's like having a personal clinical mentor.",
            name: "Dr. Aisha Patel",
            role: "Clinical Educator, Harvard Medical"
        }
    ];

    return (
        <motion.div
            className="relative bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-200"
            variants={containerVariants}
            initial="hidden"
            animate="visible"
        >
                <Head title="OSCE Simulator - Revolutionary Medical Training Platform" />

                {/* Advanced Background Effects */}
                <motion.div
                    className="fixed inset-0 pointer-events-none"
                    style={{ y: backgroundY }}
                >
                    {/* Dynamic gradient mesh */}
                    <div className="absolute inset-0 opacity-30">
                        <motion.div
                            className="absolute inset-0"
                            animate={{
                                background: [
                                    "radial-gradient(circle at 20% 50%, rgba(34, 197, 94, 0.1) 0%, transparent 50%), radial-gradient(circle at 80% 30%, rgba(59, 130, 246, 0.1) 0%, transparent 50%)",
                                    "radial-gradient(circle at 80% 50%, rgba(34, 197, 94, 0.1) 0%, transparent 50%), radial-gradient(circle at 20% 70%, rgba(168, 85, 247, 0.1) 0%, transparent 50%)",
                                    "radial-gradient(circle at 20% 50%, rgba(34, 197, 94, 0.1) 0%, transparent 50%), radial-gradient(circle at 80% 30%, rgba(59, 130, 246, 0.1) 0%, transparent 50%)"
                                ]
                            }}
                            transition={{ duration: 8, repeat: Infinity, ease: "linear" }}
                        />
                    </div>

                    {/* Floating particles */}
                    <div className="absolute inset-0">
                        {[...Array(30)].map((_, i) => (
                            <motion.div
                                key={i}
                                className="absolute w-1 h-1 bg-gradient-to-r from-emerald-400/60 to-cyan-400/60 rounded-full"
                                style={{
                                    left: `${Math.random() * 100}%`,
                                    top: `${Math.random() * 100}%`,
                                }}
                                animate={{
                                    y: [0, -30, 0],
                                    opacity: [0, 1, 0],
                                    scale: [0, 1.5, 0],
                                    rotate: [0, 180, 360]
                                }}
                                transition={{
                                    duration: 3 + Math.random() * 2,
                                    repeat: Infinity,
                                    delay: Math.random() * 2,
                                    ease: "easeInOut"
                                }}
                            />
                        ))}
                    </div>

                    {/* Interactive cursor effect */}
                    <motion.div
                        className="absolute w-80 h-80 rounded-full opacity-20"
                        style={{
                            background: "radial-gradient(circle, rgba(34, 197, 94, 0.1) 0%, transparent 70%)",
                            x: mousePosition.x - 160,
                            y: mousePosition.y - 160,
                        }}
                        transition={{ type: "spring", damping: 30, stiffness: 200 }}
                    />

                    {/* Neural network pattern */}
                    <div className="absolute inset-0 opacity-[0.02] dark:opacity-[0.05]">
                        <svg width="100%" height="100%" className="absolute inset-0">
                            <defs>
                                <pattern id="neuralPattern" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
                                    <circle cx="25" cy="25" r="1" fill="currentColor" />
                                    <circle cx="75" cy="75" r="1" fill="currentColor" />
                                    <line x1="25" y1="25" x2="75" y2="75" stroke="currentColor" strokeWidth="0.5" opacity="0.3" />
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#neuralPattern)" />
                        </svg>
                    </div>
                </motion.div>

                {/* Modern Floating Header */}
                <motion.header
                    className="fixed top-4 left-1/2 z-50 w-full max-w-5xl -translate-x-1/2 px-4"
                    variants={itemVariants}
                    initial={{ y: -100, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    transition={{ duration: 0.8, delay: 0.2 }}
                >
                    <div className="relative">
                        <motion.nav
                            className="clean-card bg-card px-4 py-3 flex items-center justify-between gap-4 transition-all duration-200"
                            variants={itemVariants}
                        >
                            <motion.div
                                className="flex items-center gap-3 text-emerald-600 dark:text-emerald-400 font-semibold"
                                variants={itemVariants}
                                whileHover={{ scale: 1.02 }}
                                transition={{ duration: 0.2 }}
                            >
                                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500 text-white text-sm font-bold">
                                    O
                                </div>
                                <span className="text-lg tracking-tight text-foreground">osce.ai</span>
                            </motion.div>

                            <div className="hidden md:flex items-center gap-3">
                                <ThemeToggle />
                                {isAuthenticated ? (
                                    <Link href={route('dashboard')}>
                                        <motion.button
                                            className="clean-button primary px-4 py-2 text-sm font-semibold"
                                            variants={buttonVariants}
                                            initial="rest"
                                            whileHover="hover"
                                            whileTap="tap"
                                        >
                                            Dashboard
                                        </motion.button>
                                    </Link>
                                ) : (
                                    <>
                                        <Link href={route('login')}>
                                            <motion.button
                                                className="clean-button px-4 py-2 text-sm font-medium"
                                                variants={buttonVariants}
                                                initial="rest"
                                                whileHover="hover"
                                                whileTap="tap"
                                            >
                                                Sign In
                                            </motion.button>
                                        </Link>
                                        <Link href={route('login')}>
                                            <motion.button
                                                className="clean-button primary px-4 py-2 text-sm font-semibold"
                                                variants={buttonVariants}
                                                initial="rest"
                                                whileHover="hover"
                                                whileTap="tap"
                                            >
                                                Get Started
                                            </motion.button>
                                        </Link>
                                    </>
                                )}
                            </div>

                            <div className="flex items-center gap-2 md:hidden">
                                <button
                                    type="button"
                                    className="clean-button px-2 py-2"
                                    aria-expanded={isMenuOpen}
                                    aria-controls="landing-nav-menu"
                                    onClick={() => setIsMenuOpen((prev) => !prev)}
                                >
                                    <span className="sr-only">Toggle navigation</span>
                                    {isMenuOpen ? (
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            className="h-5 w-5 text-foreground"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth="1.5"
                                        >
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    ) : (
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            className="h-5 w-5 text-foreground"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth="1.5"
                                        >
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                        </svg>
                                    )}
                                </button>
                            </div>
                        </motion.nav>

                        <AnimatePresence>
                            {isMenuOpen && (
                                <motion.div
                                    id="landing-nav-menu"
                                    initial={{ opacity: 0, y: -8 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    exit={{ opacity: 0, y: -8 }}
                                    transition={{ duration: 0.2 }}
                                    className="absolute left-0 right-0 mt-3 md:hidden"
                                >
                                    <div className="clean-card bg-card p-4 space-y-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium text-muted-foreground">Appearance</span>
                                            <ThemeToggle />
                                        </div>
                                        <div className="border-t border-border pt-3 space-y-3">
                                            {isAuthenticated ? (
                                                <Link
                                                    href={route('dashboard')}
                                                    className="block"
                                                    onClick={() => setIsMenuOpen(false)}
                                                >
                                                    <span className="clean-button primary flex w-full justify-center px-4 py-2 text-sm font-semibold">
                                                        Dashboard
                                                    </span>
                                                </Link>
                                            ) : (
                                                <>
                                                    <Link
                                                        href={route('login')}
                                                        className="block"
                                                        onClick={() => setIsMenuOpen(false)}
                                                    >
                                                        <span className="clean-button flex w-full justify-center px-4 py-2 text-sm font-medium">
                                                            Sign In
                                                        </span>
                                                    </Link>
                                                    <Link
                                                        href={route('login')}
                                                        className="block"
                                                        onClick={() => setIsMenuOpen(false)}
                                                    >
                                                        <span className="clean-button primary flex w-full justify-center px-4 py-2 text-sm font-semibold">
                                                            Get Started
                                                        </span>
                                                    </Link>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </motion.div>
                            )}
                        </AnimatePresence>
                    </div>
                </motion.header>

                {/* Revolutionary Hero Section */}
                <motion.section
                    className="relative flex items-center justify-center py-20 min-h-[80vh]"
                    variants={heroVariants}
                    style={{ opacity: heroOpacity }}
                >
                    <div className="max-w-6xl mx-auto px-4 text-center relative z-10">
                        {/* Hero Badge */}
                        <motion.div
                            className="inline-flex items-center gap-2 cyber-border px-4 py-2 mb-8 bg-gradient-to-r from-emerald-500/10 to-cyan-500/10"
                            variants={itemVariants}
                        >
                            <motion.div
                                className="w-2 h-2 bg-emerald-500 rounded-full"
                                animate={{ scale: [1, 1.2, 1], opacity: [0.7, 1, 0.7] }}
                                transition={{ duration: 2, repeat: Infinity }}
                            />
                            <span className="text-sm font-mono text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                Revolutionary AI-Powered Training
                            </span>
                        </motion.div>

                        {/* Hero Title with Advanced Typography */}
                        <motion.h1
                            className="text-4xl md:text-6xl lg:text-7xl font-bold mb-8 leading-[1.1]"
                            variants={heroVariants}
                        >
                            <motion.span
                                className="block text-foreground"
                                animate={{
                                    backgroundPosition: ["0% 50%", "100% 50%", "0% 50%"],
                                }}
                                style={{
                                    background: "linear-gradient(90deg, currentColor 0%, #10b981 25%, #06b6d4 50%, #8b5cf6 75%, currentColor 100%)",
                                    backgroundSize: "200% 100%",
                                    WebkitBackgroundClip: "text",
                                    WebkitTextFillColor: "transparent",
                                    backgroundClip: "text",
                                }}
                                transition={{ duration: 3, repeat: Infinity, ease: "linear" }}
                            >
                                Master Clinical
                            </motion.span>
                            <motion.span
                                className="block glow-text text-emerald-500"
                                initial={{ opacity: 0, scale: 0.8 }}
                                animate={{ opacity: 1, scale: 1 }}
                                transition={{ delay: 0.5, duration: 0.8 }}
                            >
                                Excellence
                            </motion.span>
                            <motion.span
                                className="block text-2xl md:text-3xl lg:text-4xl text-muted-foreground font-normal mt-4"
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.8, duration: 0.8 }}
                            >
                                with AI-Powered OSCE Simulation
                            </motion.span>
                        </motion.h1>

                        {/* Hero Description */}
                        <motion.p
                            className="text-xl md:text-2xl text-muted-foreground mb-12 max-w-4xl mx-auto leading-relaxed"
                            variants={itemVariants}
                        >
                            Master clinical skills with AI-powered OSCE simulations. Get instant feedback, track progress, and prepare for real exams with confidence.
                        </motion.p>

                        {/* Enhanced CTA Section */}
                        <motion.div
                            className="flex flex-col sm:flex-row gap-4 justify-center items-center mb-16"
                            variants={staggerVariants}
                        >
                            {auth && auth.user ? (
                                <Link href={route('dashboard')}>
                                    <motion.button
                                        className="cyber-button px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg relative overflow-hidden group"
                                        variants={buttonVariants}
                                        initial="rest"
                                        whileHover="hover"
                                        whileTap="tap"
                                    >
                                        <motion.div
                                            className="absolute inset-0 bg-gradient-to-r from-cyan-500 to-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                            transition={{ duration: 0.3 }}
                                        />
                                        <span className="relative z-10">Continue Training</span>
                                    </motion.button>
                                </Link>
                            ) : (
                                <>
                                    <Link href={route('login')}>
                                        <motion.button
                                            className="cyber-button px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-emerald-500 to-cyan-500 shadow-lg relative overflow-hidden group"
                                            variants={buttonVariants}
                                            initial="rest"
                                            whileHover="hover"
                                            whileTap="tap"
                                        >
                                            <motion.div
                                                className="absolute inset-0 bg-gradient-to-r from-cyan-500 to-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                                transition={{ duration: 0.3 }}
                                            />
                                            <span className="relative z-10">Start Free Trial</span>
                                        </motion.button>
                                    </Link>
                                    <motion.button
                                        className="cyber-button px-8 py-4 text-lg font-semibold text-foreground bg-transparent border-2 border-emerald-500/30"
                                        variants={buttonVariants}
                                        initial="rest"
                                        whileHover="hover"
                                        whileTap="tap"
                                    >
                                        Watch Demo
                                    </motion.button>
                                </>
                            )}
                        </motion.div>

                        {/* Social Proof Stats */}
                        <motion.div
                            className="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto"
                            variants={staggerVariants}
                        >
                            {[
                                { number: "50K+", label: "Medical Students" },
                                { number: "94%", label: "Pass Rate Improvement" },
                                { number: "500+", label: "OSCE Cases" },
                                { number: "24/7", label: "AI Support" }
                            ].map((stat, idx) => (
                                <motion.div
                                    key={idx}
                                    className="text-center"
                                    variants={fadeInVariants}
                                >
                                    <motion.div
                                        className="text-2xl md:text-3xl font-bold text-emerald-500 mb-2"
                                        initial={{ scale: 0.5, opacity: 0 }}
                                        animate={{ scale: 1, opacity: 1 }}
                                        transition={{ delay: 1 + idx * 0.1, duration: 0.5, type: "spring" }}
                                    >
                                        {stat.number}
                                    </motion.div>
                                    <div className="text-sm text-muted-foreground font-mono uppercase tracking-wider">
                                        {stat.label}
                                    </div>
                                </motion.div>
                            ))}
                        </motion.div>
                    </div>

                    {/* Floating Hero Elements */}
                    <motion.div
                        className="absolute top-1/4 left-10 w-20 h-20 cyber-border bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 hidden lg:block"
                        animate={{
                            y: [0, -20, 0],
                            rotate: [0, 5, 0]
                        }}
                        transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
                    >
                        <div className="w-full h-full flex items-center justify-center text-2xl">🩺</div>
                    </motion.div>

                    <motion.div
                        className="absolute top-1/3 right-10 w-16 h-16 cyber-border bg-gradient-to-br from-blue-500/20 to-purple-500/20 hidden lg:block"
                        animate={{
                            y: [0, 20, 0],
                            rotate: [0, -5, 0]
                        }}
                        transition={{ duration: 3, repeat: Infinity, ease: "easeInOut", delay: 1 }}
                    >
                        <div className="w-full h-full flex items-center justify-center text-xl">🧠</div>
                    </motion.div>

                    <motion.div
                        className="absolute bottom-1/4 left-1/4 w-12 h-12 cyber-border bg-gradient-to-br from-purple-500/20 to-pink-500/20 hidden lg:block"
                        animate={{
                            y: [0, -15, 0],
                            rotate: [0, 10, 0]
                        }}
                        transition={{ duration: 5, repeat: Infinity, ease: "easeInOut", delay: 2 }}
                    >
                        <div className="w-full h-full flex items-center justify-center text-lg">📊</div>
                    </motion.div>
                </motion.section>

                {/* Key Features Section - Compact */}
                <motion.section
                    className="py-16 relative"
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, margin: "-50px" }}
                >
                    <div className="max-w-6xl mx-auto px-4">
                        <motion.div className="text-center mb-12" variants={staggerVariants}>
                            <motion.h2
                                className="text-3xl md:text-4xl font-bold mb-4 glow-text"
                                variants={heroVariants}
                            >
                                Everything You Need to Excel
                            </motion.h2>
                            <motion.p
                                className="text-lg text-muted-foreground max-w-2xl mx-auto"
                                variants={itemVariants}
                            >
                                AI-powered training platform designed for medical students preparing for OSCE exams
                            </motion.p>
                        </motion.div>

                        <motion.div
                            className="grid md:grid-cols-3 gap-8"
                            variants={staggerVariants}
                        >
                            {keyFeatures.map((feature, idx) => (
                                <motion.div
                                    key={idx}
                                    className="cyber-border p-6 bg-gradient-to-br from-emerald-500/5 to-blue-500/5 relative group text-center"
                                    variants={cardVariants}
                                    whileHover="hover"
                                >
                                    <motion.div className="text-4xl mb-4" variants={itemVariants}>
                                        {feature.icon}
                                    </motion.div>

                                    <motion.h3
                                        className="text-xl font-semibold mb-3 text-foreground"
                                        variants={itemVariants}
                                    >
                                        {feature.title}
                                    </motion.h3>

                                    <motion.p
                                        className="text-muted-foreground leading-relaxed"
                                        variants={itemVariants}
                                    >
                                        {feature.description}
                                    </motion.p>
                                </motion.div>
                            ))}
                        </motion.div>
                    </div>
                </motion.section>

                {/* Testimonials Section - Compact */}
                <motion.section
                    className="py-16 relative"
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, margin: "-50px" }}
                >
                    <div className="max-w-6xl mx-auto px-4">
                        <motion.div className="text-center mb-12" variants={staggerVariants}>
                            <motion.h2
                                className="text-3xl md:text-4xl font-bold mb-4 glow-text"
                                variants={heroVariants}
                            >
                                Loved by Medical Students
                            </motion.h2>
                        </motion.div>

                        <motion.div
                            className="grid md:grid-cols-3 gap-6"
                            variants={staggerVariants}
                        >
                            {testimonials.map((testimonial, idx) => (
                                <motion.div
                                    key={idx}
                                    className="cyber-border p-6 bg-gradient-to-br from-muted/20 to-card/30 relative group"
                                    variants={cardVariants}
                                    whileHover="hover"
                                >
                                    <div className="text-emerald-400 text-2xl mb-3 font-serif">"</div>
                                    <motion.p
                                        className="text-muted-foreground mb-4 leading-relaxed text-sm"
                                        variants={itemVariants}
                                    >
                                        {testimonial.content}
                                    </motion.p>
                                    <motion.div className="flex items-center gap-3" variants={itemVariants}>
                                        <div className="w-10 h-10 bg-gradient-to-br from-emerald-400 to-cyan-400 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            {testimonial.name.split(' ').map(n => n[0]).join('')}
                                        </div>
                                        <div>
                                            <div className="font-semibold text-foreground text-sm">{testimonial.name}</div>
                                            <div className="text-xs text-muted-foreground">{testimonial.role}</div>
                                        </div>
                                    </motion.div>
                                </motion.div>
                            ))}
                        </motion.div>
                    </div>
                </motion.section>

                {/* Final CTA Section - Compact */}
                <motion.section
                    className="py-16 relative"
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, margin: "-50px" }}
                >
                    <div className="max-w-4xl mx-auto px-4 text-center">
                        <motion.div
                            className="cyber-border p-10 bg-gradient-to-br from-emerald-500/10 via-blue-500/10 to-purple-500/10"
                            variants={cardVariants}
                        >
                            <motion.h2
                                className="text-3xl md:text-4xl font-bold mb-4 glow-text"
                                variants={heroVariants}
                            >
                                Ready to Excel in OSCE?
                            </motion.h2>

                            <motion.p
                                className="text-lg text-muted-foreground mb-8 max-w-2xl mx-auto"
                                variants={itemVariants}
                            >
                                Join thousands of medical students preparing with AI-powered simulations
                            </motion.p>

                            <motion.div
                                className="flex flex-col sm:flex-row gap-4 justify-center mb-6"
                                variants={staggerVariants}
                            >
                                {auth && auth.user ? (
                                    <Link href={route('dashboard')}>
                                        <motion.button
                                            className="cyber-button px-8 py-3 text-lg font-semibold text-white bg-gradient-to-r from-emerald-500 to-cyan-500"
                                            variants={buttonVariants}
                                            initial="rest"
                                            whileHover="hover"
                                            whileTap="tap"
                                        >
                                            Continue Learning
                                        </motion.button>
                                    </Link>
                                ) : (
                                    <>
                                        <Link href={route('login')}>
                                            <motion.button
                                                className="cyber-button px-8 py-3 text-lg font-semibold text-white bg-gradient-to-r from-emerald-500 to-cyan-500"
                                                variants={buttonVariants}
                                                initial="rest"
                                                whileHover="hover"
                                                whileTap="tap"
                                            >
                                                Start Free Trial
                                            </motion.button>
                                        </Link>
                                        <motion.button
                                            className="cyber-button px-8 py-3 text-lg font-semibold text-foreground bg-transparent border-2 border-emerald-500/30"
                                            variants={buttonVariants}
                                            initial="rest"
                                            whileHover="hover"
                                            whileTap="tap"
                                        >
                                            View Demo
                                        </motion.button>
                                    </>
                                )}
                            </motion.div>

                            <motion.div
                                className="flex items-center justify-center gap-6 text-sm text-muted-foreground"
                                variants={itemVariants}
                            >
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-emerald-500 rounded-full" />
                                    <span>Free trial</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-blue-500 rounded-full" />
                                    <span>No credit card</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-purple-500 rounded-full" />
                                    <span>Cancel anytime</span>
                                </div>
                            </motion.div>
                        </motion.div>
                    </div>
                </motion.section>

                {/* Modern Footer */}
                <motion.footer
                    className="border-t border-emerald-500/20 bg-gradient-to-b from-neutral-50/50 to-neutral-100/50 dark:from-neutral-950/50 dark:to-neutral-900/50 py-20 relative overflow-hidden"
                    variants={itemVariants}
                >
                    {/* Footer background effects */}
                    <div className="absolute inset-0 opacity-10">
                        <div className="absolute top-0 left-1/4 w-64 h-64 bg-gradient-to-br from-emerald-500 to-cyan-500 rounded-full blur-3xl"></div>
                        <div className="absolute bottom-0 right-1/4 w-64 h-64 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full blur-3xl"></div>
                    </div>

                    <div className="max-w-7xl mx-auto px-4 relative z-10">
                        <motion.div
                            className="grid md:grid-cols-4 gap-8 mb-12"
                            variants={staggerVariants}
                        >
                            {/* Company Info */}
                            <motion.div className="col-span-2" variants={itemVariants}>
                                <motion.div className="flex items-center gap-3 mb-6">
                                    <motion.div
                                        className="w-10 h-10 bg-gradient-to-br from-emerald-500 to-cyan-500 rounded-full flex items-center justify-center"
                                        animate={{ rotate: [0, 360] }}
                                        transition={{ duration: 20, repeat: Infinity, ease: "linear" }}
                                    >
                                        <span className="text-white text-lg font-bold">O</span>
                                    </motion.div>
                                    <span className="text-2xl font-mono font-bold text-foreground">osce.ai</span>
                                </motion.div>
                                <p className="text-muted-foreground mb-6 leading-relaxed max-w-md">
                                    Revolutionizing medical education through AI-powered OSCE simulations.
                                    Empowering the next generation of healthcare professionals with cutting-edge training technology.
                                </p>
                                <div className="flex items-center gap-4">
                                    <motion.div
                                        className="flex items-center gap-2 text-sm text-emerald-500"
                                        animate={{ opacity: [0.7, 1, 0.7] }}
                                        transition={{ duration: 2, repeat: Infinity }}
                                    >
                                        <div className="w-2 h-2 bg-emerald-500 rounded-full"></div>
                                        <span className="font-mono">System Online</span>
                                    </motion.div>
                                    <div className="text-sm text-muted-foreground">•</div>
                                    <div className="text-sm text-muted-foreground font-mono">99.9% Uptime</div>
                                </div>
                            </motion.div>

                            {/* Quick Links */}
                            <motion.div variants={itemVariants}>
                                <h4 className="font-semibold text-foreground mb-4 text-sm uppercase tracking-wider">Platform</h4>
                                <ul className="space-y-3 text-sm text-muted-foreground">
                                    <li><a href="#features" className="hover:text-emerald-400 transition-colors">Features</a></li>
                                    <li><a href="#pricing" className="hover:text-emerald-400 transition-colors">Pricing</a></li>
                                    <li><a href="#demo" className="hover:text-emerald-400 transition-colors">Live Demo</a></li>
                                    <li><a href="#api" className="hover:text-emerald-400 transition-colors">API Docs</a></li>
                                </ul>
                            </motion.div>

                            {/* Support */}
                            <motion.div variants={itemVariants}>
                                <h4 className="font-semibold text-foreground mb-4 text-sm uppercase tracking-wider">Support</h4>
                                <ul className="space-y-3 text-sm text-muted-foreground">
                                    <li><Link href={route('contact')} className="hover:text-emerald-400 transition-colors">Contact Us</Link></li>
                                    <li><a href="#help" className="hover:text-emerald-400 transition-colors">Help Center</a></li>
                                    <li><Link href={route('privacy-policy')} className="hover:text-emerald-400 transition-colors">Privacy Policy</Link></li>
                                    <li><a href="#terms" className="hover:text-emerald-400 transition-colors">Terms of Service</a></li>
                                </ul>
                            </motion.div>
                        </motion.div>

                        {/* Footer Bottom */}
                        <motion.div
                            className="border-t border-emerald-500/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4"
                            variants={itemVariants}
                        >
                            <motion.div
                                className="flex items-center gap-4 text-sm text-muted-foreground"
                                variants={itemVariants}
                            >
                                <motion.div
                                    className="w-1 h-6 bg-emerald-500"
                                    animate={{
                                        scaleY: [1, 1.2, 1],
                                        opacity: [0.7, 1, 0.7]
                                    }}
                                    transition={{
                                        duration: 2,
                                        repeat: Infinity,
                                        ease: "easeInOut"
                                    }}
                                />
                                <span className="font-mono">Built by</span>
                                <Link
                                    href={route('made-by')}
                                    className="text-emerald-400 hover:text-emerald-300 transition-colors font-medium"
                                >
                                    Bintang Putra
                                </Link>
                            </motion.div>

                            <motion.div
                                className="flex items-center gap-6 text-sm text-muted-foreground"
                                variants={itemVariants}
                            >
                                <motion.a
                                    href="http://dev.bintangputra.my.id"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="font-mono uppercase tracking-wider hover:text-emerald-400 transition-colors"
                                    whileHover={{ scale: 1.05 }}
                                    transition={{ duration: 0.2 }}
                                >
                                    Portfolio
                                </motion.a>
                                <motion.a
                                    href="https://github.com/ChaostixZix"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="font-mono uppercase tracking-wider hover:text-emerald-400 transition-colors"
                                    whileHover={{ scale: 1.05 }}
                                    transition={{ duration: 0.2 }}
                                >
                                    GitHub
                                </motion.a>
                                <div className="font-mono text-xs">
                                    © 2024 OSCE.AI •
                                    <motion.span
                                        className="text-emerald-500 ml-1"
                                        animate={{
                                            opacity: [0.7, 1, 0.7]
                                        }}
                                        transition={{
                                            duration: 2,
                                            repeat: Infinity,
                                            ease: "easeInOut"
                                        }}
                                    >
                                        v3.0
                                    </motion.span>
                                </div>
                            </motion.div>
                        </motion.div>
                    </div>

                    {/* Floating footer elements */}
                    <motion.div
                        className="absolute bottom-10 left-10 w-6 h-6 cyber-border bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 hidden lg:block"
                        animate={{
                            y: [0, -10, 0],
                            rotate: [0, 5, 0]
                        }}
                        transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
                    >
                        <div className="w-full h-full flex items-center justify-center text-xs">✨</div>
                    </motion.div>

                    <motion.div
                        className="absolute bottom-16 right-10 w-8 h-8 cyber-border bg-gradient-to-br from-blue-500/20 to-purple-500/20 hidden lg:block"
                        animate={{
                            y: [0, 15, 0],
                            rotate: [0, -5, 0]
                        }}
                        transition={{ duration: 3, repeat: Infinity, ease: "easeInOut", delay: 1 }}
                    >
                        <div className="w-full h-full flex items-center justify-center text-sm">🚀</div>
                    </motion.div>
                </motion.footer>
            <motion.footer 
                className="border-t border-neutral-300 dark:border-neutral-800 bg-neutral-100/80 dark:bg-neutral-950 py-10 transition-colors duration-300"
                variants={itemVariants}
            >
                <div className="max-w-7xl mx-auto px-4">
                    <motion.div 
                        className="flex flex-col items-center gap-4 text-neutral-600 dark:text-neutral-500 text-sm"
                        variants={containerVariants}
                    >
                        <motion.div 
                            className="flex items-center gap-2"
                            variants={itemVariants}
                        >
                            <motion.div 
                                className="w-1 h-4 bg-emerald-500"
                                animate={{ 
                                    scaleY: [1, 1.2, 1],
                                    opacity: [0.7, 1, 0.7]
                                }}
                                transition={{ 
                                    duration: 2,
                                    repeat: Infinity,
                                    ease: "easeInOut"
                                }}
                            />
                            <span className="font-mono">crafted by bintang putra</span>
                            <motion.div 
                                className="w-1 h-4 bg-emerald-500"
                                animate={{ 
                                    scaleY: [1, 1.2, 1],
                                    opacity: [0.7, 1, 0.7]
                                }}
                                transition={{ 
                                    duration: 2,
                                    repeat: Infinity,
                                    ease: "easeInOut",
                                    delay: 0.5
                                }}
                            />
                        </motion.div>
                        <motion.div 
                            className="flex gap-6"
                            variants={itemVariants}
                        >
                            <motion.a
                                href="http://dev.bintangputra.my.id"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="font-mono text-xs uppercase tracking-wider"
                                whileHover={{ 
                                    color: "#10b981",
                                    scale: 1.05
                                }}
                                transition={{ duration: 0.2 }}
                            >
                                personal site
                            </motion.a>
                            <motion.a
                                href="https://github.com/ChaostixZix"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="font-mono text-xs uppercase tracking-wider"
                                whileHover={{ 
                                    color: "#10b981",
                                    scale: 1.05
                                }}
                                transition={{ duration: 0.2 }}
                            >
                                github
                            </motion.a>
                        </motion.div>
                        <motion.div 
                            className="text-neutral-500 dark:text-neutral-600 text-xs font-mono"
                            variants={itemVariants}
                        >
                            © 2024 osce simulator • laravel + react • 
                            <motion.span 
                                className="text-emerald-500 ml-1"
                                animate={{ 
                                    opacity: [0.7, 1, 0.7]
                                }}
                                transition={{ 
                                    duration: 2,
                                    repeat: Infinity,
                                    ease: "easeInOut"
                                }}
                            >
                                v2.1
                            </motion.span>
                        </motion.div>
                    </motion.div>
                </div>
            </motion.footer>
        </motion.div>
    );
}

export default Landing;
