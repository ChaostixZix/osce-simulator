import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@vibe-kanban/ui-kit';
import { ThemeProvider } from '@/contexts/ThemeContext';
import ThemeToggle from '@/components/react/ThemeToggle';
import { motion } from 'framer-motion';

function Landing({ auth }) {
    // Animation variants
    const containerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: {
                duration: 0.6,
                staggerChildren: 0.1
            }
        }
    };

    const itemVariants = {
        hidden: { opacity: 0, y: 20 },
        visible: {
            opacity: 1,
            y: 0,
            transition: {
                duration: 0.6,
                ease: [0.25, 0.46, 0.45, 0.94]
            }
        }
    };

    const heroVariants = {
        hidden: { opacity: 0, y: 30 },
        visible: {
            opacity: 1,
            y: 0,
            transition: {
                duration: 0.8,
                ease: [0.25, 0.46, 0.45, 0.94]
            }
        }
    };

    const cardVariants = {
        hidden: { opacity: 0, y: 40, scale: 0.95 },
        visible: {
            opacity: 1,
            y: 0,
            scale: 1,
            transition: {
                duration: 0.6,
                ease: [0.25, 0.46, 0.45, 0.94]
            }
        },
        hover: {
            scale: 1.02,
            y: -5,
            transition: {
                duration: 0.3,
                ease: [0.25, 0.46, 0.45, 0.94]
            }
        }
    };

    const buttonVariants = {
        hover: {
            scale: 1.05,
            transition: {
                duration: 0.2,
                ease: [0.25, 0.46, 0.45, 0.94]
            }
        },
        tap: {
            scale: 0.95,
            transition: {
                duration: 0.1
            }
        }
    };

    return (
        <ThemeProvider>
            <motion.div 
                className="min-h-screen relative overflow-hidden bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-200 font-mono transition-colors duration-300"
                variants={containerVariants}
                initial="hidden"
                animate="visible"
            >
                <Head title="osce simulator" />

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

                {/* Enhanced header */}
                <motion.header 
                    className="border-b border-neutral-300 dark:border-neutral-800/80 bg-neutral-100/80 dark:bg-neutral-950/60 backdrop-blur supports-[backdrop-filter]:bg-neutral-100/40 supports-[backdrop-filter]:dark:bg-neutral-950/40 transition-colors duration-300"
                    variants={itemVariants}
                >
                    <div className="max-w-7xl mx-auto px-4 py-4">
                        <nav className="flex justify-between items-center">
                            <motion.div 
                                className="flex items-center gap-4"
                                variants={itemVariants}
                            >
                                {/* Enhanced logo */}
                                <motion.div 
                                    className="text-xl tracking-tight text-emerald-600 dark:text-emerald-400 font-bold relative"
                                    whileHover={{ scale: 1.05 }}
                                    transition={{ duration: 0.2 }}
                                >
                                    <span className="relative z-10">▌ osce simulator ▐</span>
                                    <motion.div 
                                        className="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-emerald-500 to-cyan-400 opacity-60"
                                        style={{
                                            clipPath: 'polygon(0 0, 95% 0, 100% 100%, 5% 100%)'
                                        }}
                                        animate={{ 
                                            opacity: [0.6, 1, 0.6],
                                        }}
                                        transition={{ 
                                            duration: 2,
                                            repeat: Infinity,
                                            ease: "easeInOut"
                                        }}
                                    />
                                </motion.div>
                                
                                {/* Status indicator */}
                                <motion.div 
                                    className="flex items-center gap-1"
                                    variants={itemVariants}
                                >
                                    <motion.div 
                                        className="w-2 h-2 bg-emerald-500 rounded-full"
                                        animate={{ 
                                            scale: [1, 1.2, 1],
                                            opacity: [0.7, 1, 0.7]
                                        }}
                                        transition={{ 
                                            duration: 1.5,
                                            repeat: Infinity,
                                            ease: "easeInOut"
                                        }}
                                    />
                                    <span className="text-xs text-neutral-600 dark:text-neutral-400 uppercase tracking-wide">Live</span>
                                </motion.div>
                            </motion.div>
                            <motion.div 
                                className="flex items-center gap-3"
                                variants={itemVariants}
                            >
                                <ThemeToggle />
                                {auth && auth.user ? (
                                    <Link href={route('dashboard')}>
                                        <motion.button 
                                            className="cyber-button px-4 py-2 text-sm font-mono text-emerald-600 dark:text-emerald-300 uppercase tracking-wide"
                                            variants={buttonVariants}
                                            whileHover="hover"
                                            whileTap="tap"
                                        >
                                            go to dashboard
                                        </motion.button>
                                    </Link>
                                ) : (
                                    <Link href={route('login')}>
                                        <motion.button 
                                            className="cyber-button px-4 py-2 text-sm font-mono text-emerald-600 dark:text-emerald-300 uppercase tracking-wide"
                                            variants={buttonVariants}
                                            whileHover="hover"
                                            whileTap="tap"
                                        >
                                            login
                                        </motion.button>
                                    </Link>
                                )}
                            </motion.div>
                        </nav>
                    </div>
                </motion.header>

            {/* Enhanced hero */}
            <motion.main 
                className="max-w-7xl mx-auto px-4 py-20 relative"
                variants={heroVariants}
            >
                <div className="text-center max-w-4xl mx-auto">
                    {/* Animated title */}
                    <motion.h1 
                        className="text-4xl md:text-5xl font-medium mb-4 leading-tight glow-text"
                        variants={heroVariants}
                        whileHover={{ scale: 1.02 }}
                        transition={{ duration: 0.3 }}
                    >
                        a flat, techy osce training interface
                    </motion.h1>
                    <motion.p 
                        className="text-base md:text-lg text-neutral-600 dark:text-neutral-400 mb-10 leading-relaxed"
                        variants={heroVariants}
                    >
                        build clinical skill through structured, simulated sessions. fast. minimal. no noise.
                    </motion.p>

                    {/* Enhanced CTA */}
                    <motion.div 
                        className="flex flex-col sm:flex-row gap-3 justify-center mb-16"
                        variants={heroVariants}
                    >
                        {auth && auth.user ? (
                            <Link href={route('dashboard')}>
                                <motion.button 
                                    className="cyber-button px-8 py-3 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm"
                                    variants={buttonVariants}
                                    whileHover="hover"
                                    whileTap="tap"
                                >
                                    start training
                                </motion.button>
                            </Link>
                        ) : (
                            <Link href={route('login')}>
                                <motion.button 
                                    className="cyber-button px-8 py-3 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wider text-sm"
                                    variants={buttonVariants}
                                    whileHover="hover"
                                    whileTap="tap"
                                >
                                    start training
                                </motion.button>
                            </Link>
                        )}
                    </motion.div>

                    {/* Enhanced feature grid */}
                    <motion.div 
                        className="grid md:grid-cols-3 gap-6 mt-16 mb-8"
                        variants={containerVariants}
                    >
                        {[
                            { 
                                k: 'skills', 
                                d: 'simulated patient flow and procedures',
                                color: 'border-emerald-500/20 bg-emerald-500/5',
                                accent: 'text-emerald-500'
                            },
                            { 
                                k: 'reasoning', 
                                d: 'clinical decision scaffolding',
                                color: 'border-blue-500/20 bg-blue-500/5',
                                accent: 'text-blue-500'
                            },
                            { 
                                k: 'tracking', 
                                d: 'progress metrics that matter',
                                color: 'border-purple-500/20 bg-purple-500/5',
                                accent: 'text-purple-500'
                            },
                        ].map((f, idx) => (
                            <motion.div
                                key={f.k}
                                className={`cyber-border p-6 text-left ${f.color} relative group`}
                                variants={cardVariants}
                                whileHover="hover"
                                custom={idx}
                            >
                                {/* Animated corner accent */}
                                <motion.div 
                                    className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60"
                                    animate={{ 
                                        opacity: [0.6, 1, 0.6],
                                        scale: [1, 1.1, 1]
                                    }}
                                    transition={{ 
                                        duration: 2,
                                        repeat: Infinity,
                                        delay: idx * 0.3,
                                        ease: "easeInOut"
                                    }}
                                />
                                
                                <motion.div 
                                    className={`text-sm ${f.accent} mb-2 font-mono uppercase tracking-wider`}
                                    variants={itemVariants}
                                >
                                    {f.k}
                                </motion.div>
                                <motion.div 
                                    className="text-neutral-700 dark:text-neutral-300 text-sm leading-relaxed"
                                    variants={itemVariants}
                                >
                                    {f.d}
                                </motion.div>
                                
                                {/* Animated hover indicator */}
                                <motion.div 
                                    className="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"
                                    initial={{ scaleX: 0 }}
                                    whileHover={{ scaleX: 1 }}
                                    transition={{ duration: 0.3, ease: [0.25, 0.46, 0.45, 0.94] }}
                                    style={{ originX: 0 }}
                                />
                            </motion.div>
                        ))}
                    </motion.div>

                    {/* Enhanced status bar */}
                    <motion.div 
                        className="mx-auto max-w-xl mt-10 cyber-border bg-card/50 px-6 py-4 text-left text-sm text-neutral-600 dark:text-neutral-400 relative overflow-hidden"
                        variants={itemVariants}
                    >
                        {/* Animated scan line */}
                        <motion.div 
                            className="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent opacity-50"
                            animate={{ 
                                opacity: [0.3, 0.8, 0.3]
                            }}
                            transition={{ 
                                duration: 2,
                                repeat: Infinity,
                                ease: "easeInOut"
                            }}
                        >
                            <motion.div 
                                className="w-20 h-full bg-gradient-to-r from-transparent via-emerald-400 to-transparent"
                                animate={{ 
                                    x: ['-100%', '100%']
                                }}
                                transition={{ 
                                    duration: 3,
                                    repeat: Infinity,
                                    ease: "linear"
                                }}
                            />
                        </motion.div>
                        
                        <motion.div 
                            className="flex items-center justify-between"
                            variants={itemVariants}
                        >
                            <span className="text-emerald-500 font-mono">status:</span>
                            <div className="flex items-center gap-2 text-xs">
                                <motion.div 
                                    className="w-2 h-2 bg-emerald-500 rounded-full"
                                    animate={{ 
                                        scale: [1, 1.2, 1],
                                        opacity: [0.7, 1, 0.7]
                                    }}
                                    transition={{ 
                                        duration: 1.5,
                                        repeat: Infinity,
                                        ease: "easeInOut"
                                    }}
                                />
                                <span>ready • secure • minimal interface • terminal font</span>
                            </div>
                        </motion.div>
                    </motion.div>
                </div>
            </motion.main>

            {/* Comprehensive Explanation Section */}
            <motion.section 
                className="max-w-7xl mx-auto px-4 py-20 relative"
                variants={containerVariants}
                initial="hidden"
                whileInView="visible"
                viewport={{ once: true, margin: "-100px" }}
            >
                <div className="text-center max-w-4xl mx-auto mb-16">
                    <motion.div 
                        className="flex items-center justify-center gap-3 mb-4"
                        variants={itemVariants}
                    >
                        <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
                        <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">comprehensive guide</span>
                        <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400"></div>
                    </motion.div>
                    
                    <motion.h2 
                        className="text-3xl md:text-4xl font-medium mb-6 leading-tight glow-text"
                        variants={heroVariants}
                    >
                        everything you need to know
                    </motion.h2>
                    
                    <motion.p 
                        className="text-lg text-neutral-600 dark:text-neutral-400 leading-relaxed"
                        variants={heroVariants}
                    >
                        a complete breakdown of our osce training platform, features, and methodology
                    </motion.p>
                </div>

                {/* Detailed Feature Explanations */}
                <div className="grid lg:grid-cols-2 gap-8 mb-16">
                    {/* What is OSCE */}
                    <motion.div
                        className="cyber-border p-8 bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 relative group"
                        variants={cardVariants}
                        whileHover="hover"
                    >
                        <motion.div 
                            className="absolute top-3 right-3 w-3 h-3 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"
                            animate={{ 
                                opacity: [0.6, 1, 0.6],
                                scale: [1, 1.1, 1]
                            }}
                            transition={{ 
                                duration: 2,
                                repeat: Infinity,
                                ease: "easeInOut"
                            }}
                        />
                        
                        <motion.div 
                            className="text-sm text-emerald-500 mb-4 font-mono uppercase tracking-wider"
                            variants={itemVariants}
                        >
                            what is osce?
                        </motion.div>
                        
                        <motion.h3 
                            className="text-xl font-medium mb-4 text-foreground"
                            variants={itemVariants}
                        >
                            objective structured clinical examination
                        </motion.h3>
                        
                        <motion.div 
                            className="text-neutral-700 dark:text-neutral-300 leading-relaxed space-y-3"
                            variants={itemVariants}
                        >
                            <p>
                                OSCE is a modern approach to clinical skills assessment that breaks down complex medical scenarios into structured, measurable components. Unlike traditional exams, OSCE evaluates both knowledge and practical application in realistic clinical settings.
                            </p>
                            <p>
                                Our platform simulates real patient interactions, allowing you to practice history-taking, physical examinations, clinical reasoning, and communication skills in a controlled, feedback-rich environment.
                            </p>
                        </motion.div>
                    </motion.div>

                    {/* How It Works */}
                    <motion.div
                        className="cyber-border p-8 bg-gradient-to-br from-blue-500/10 to-blue-600/5 border-blue-500/30 relative group"
                        variants={cardVariants}
                        whileHover="hover"
                    >
                        <motion.div 
                            className="absolute top-3 right-3 w-3 h-3 bg-gradient-to-br from-blue-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"
                            animate={{ 
                                opacity: [0.6, 1, 0.6],
                                scale: [1, 1.1, 1]
                            }}
                            transition={{ 
                                duration: 2,
                                repeat: Infinity,
                                ease: "easeInOut",
                                delay: 0.3
                            }}
                        />
                        
                        <motion.div 
                            className="text-sm text-blue-500 mb-4 font-mono uppercase tracking-wider"
                            variants={itemVariants}
                        >
                            how it works
                        </motion.div>
                        
                        <motion.h3 
                            className="text-xl font-medium mb-4 text-foreground"
                            variants={itemVariants}
                        >
                            structured learning workflow
                        </motion.h3>
                        
                        <motion.div 
                            className="text-neutral-700 dark:text-neutral-300 leading-relaxed space-y-3"
                            variants={itemVariants}
                        >
                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span className="font-medium">Case Selection</span>
                                </div>
                                <p className="text-sm ml-4">Choose from diverse clinical scenarios covering various medical specialties and difficulty levels.</p>
                            </div>
                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span className="font-medium">Timed Sessions</span>
                                </div>
                                <p className="text-sm ml-4">Practice under realistic time constraints with built-in timers and progress tracking.</p>
                            </div>
                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span className="font-medium">AI Assessment</span>
                                </div>
                                <p className="text-sm ml-4">Receive detailed feedback and scoring based on clinical reasoning and communication skills.</p>
                            </div>
                        </motion.div>
                    </motion.div>
                </div>

                {/* Key Features Deep Dive */}
                <motion.div 
                    className="mb-16"
                    variants={containerVariants}
                >
                    <motion.div 
                        className="flex items-center gap-3 mb-8"
                        variants={itemVariants}
                    >
                        <div className="w-1 h-8 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
                        <h3 className="text-2xl font-medium lowercase text-foreground font-mono">key features</h3>
                        <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                        <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                            <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                            <span>comprehensive</span>
                        </div>
                    </motion.div>

                    <div className="grid md:grid-cols-3 gap-6">
                        {[
                            {
                                title: "Clinical Reasoning Engine",
                                description: "Advanced AI-powered system that evaluates your diagnostic thinking, differential diagnosis formation, and clinical decision-making process.",
                                details: [
                                    "Real-time assessment of clinical reasoning patterns",
                                    "Identification of knowledge gaps and biases",
                                    "Personalized feedback on diagnostic accuracy",
                                    "Evidence-based recommendations for improvement"
                                ],
                                color: "border-emerald-500/20 bg-emerald-500/5",
                                accent: "text-emerald-500"
                            },
                            {
                                title: "Interactive Patient Simulation",
                                description: "Immersive patient encounters with dynamic responses, realistic symptoms, and adaptive scenarios based on your clinical approach.",
                                details: [
                                    "Dynamic patient responses to your questions",
                                    "Realistic symptom presentation and progression",
                                    "Adaptive scenarios based on your clinical approach",
                                    "Multiple patient types and demographics"
                                ],
                                color: "border-blue-500/20 bg-blue-500/5",
                                accent: "text-blue-500"
                            },
                            {
                                title: "Comprehensive Assessment",
                                description: "Multi-dimensional evaluation covering clinical skills, communication, professionalism, and evidence-based practice.",
                                details: [
                                    "Communication skills assessment",
                                    "Professionalism and ethics evaluation",
                                    "Evidence-based practice scoring",
                                    "Detailed performance analytics"
                                ],
                                color: "border-purple-500/20 bg-purple-500/5",
                                accent: "text-purple-500"
                            }
                        ].map((feature, idx) => (
                            <motion.div
                                key={feature.title}
                                className={`cyber-border p-6 ${feature.color} relative group`}
                                variants={cardVariants}
                                whileHover="hover"
                                custom={idx}
                            >
                                <motion.div 
                                    className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"
                                    animate={{ 
                                        opacity: [0.6, 1, 0.6],
                                        scale: [1, 1.1, 1]
                                    }}
                                    transition={{ 
                                        duration: 2,
                                        repeat: Infinity,
                                        ease: "easeInOut",
                                        delay: idx * 0.2
                                    }}
                                />
                                
                                <motion.h4 
                                    className={`text-lg font-medium mb-3 ${feature.accent}`}
                                    variants={itemVariants}
                                >
                                    {feature.title}
                                </motion.h4>
                                
                                <motion.p 
                                    className="text-neutral-700 dark:text-neutral-300 text-sm mb-4 leading-relaxed"
                                    variants={itemVariants}
                                >
                                    {feature.description}
                                </motion.p>
                                
                                <motion.div 
                                    className="space-y-2"
                                    variants={itemVariants}
                                >
                                    {feature.details.map((detail, detailIdx) => (
                                        <motion.div 
                                            key={detailIdx}
                                            className="flex items-start gap-2 text-xs text-neutral-600 dark:text-neutral-400"
                                            variants={itemVariants}
                                        >
                                            <div className="w-1 h-1 bg-emerald-500 rounded-full mt-2 flex-shrink-0"></div>
                                            <span>{detail}</span>
                                        </motion.div>
                                    ))}
                                </motion.div>
                            </motion.div>
                        ))}
                    </div>
                </motion.div>

                {/* Technology Stack */}
                <motion.div 
                    className="mb-16"
                    variants={containerVariants}
                >
                    <motion.div 
                        className="flex items-center gap-3 mb-8"
                        variants={itemVariants}
                    >
                        <div className="w-1 h-8 bg-gradient-to-b from-blue-400 to-cyan-400"></div>
                        <h3 className="text-2xl font-medium lowercase text-foreground font-mono">technology stack</h3>
                        <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                        <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                            <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                            <span>modern</span>
                        </div>
                    </motion.div>

                    <div className="grid md:grid-cols-2 gap-8">
                        <motion.div
                            className="cyber-border p-6 bg-gradient-to-br from-blue-500/10 to-blue-600/5 border-blue-500/30"
                            variants={cardVariants}
                        >
                            <motion.h4 
                                className="text-lg font-medium mb-4 text-blue-500"
                                variants={itemVariants}
                            >
                                Backend Infrastructure
                            </motion.h4>
                            <motion.div 
                                className="space-y-3 text-sm text-neutral-700 dark:text-neutral-300"
                                variants={itemVariants}
                            >
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span><strong>Laravel 12:</strong> Robust PHP framework with advanced features</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span><strong>PostgreSQL:</strong> Enterprise-grade database for reliability</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span><strong>Laravel Reverb:</strong> Real-time WebSocket communication</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span><strong>WorkOS:</strong> Secure authentication and session management</span>
                                </div>
                            </motion.div>
                        </motion.div>

                        <motion.div
                            className="cyber-border p-6 bg-gradient-to-br from-purple-500/10 to-purple-600/5 border-purple-500/30"
                            variants={cardVariants}
                        >
                            <motion.h4 
                                className="text-lg font-medium mb-4 text-purple-500"
                                variants={itemVariants}
                            >
                                Frontend Experience
                            </motion.h4>
                            <motion.div 
                                className="space-y-3 text-sm text-neutral-700 dark:text-neutral-300"
                                variants={itemVariants}
                            >
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-purple-500 rounded-full"></div>
                                    <span><strong>React 19:</strong> Latest React with concurrent features</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-purple-500 rounded-full"></div>
                                    <span><strong>Inertia.js:</strong> SPA experience without API complexity</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-purple-500 rounded-full"></div>
                                    <span><strong>Framer Motion:</strong> Smooth, professional animations</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-2 h-2 bg-purple-500 rounded-full"></div>
                                    <span><strong>Tailwind CSS:</strong> Utility-first styling with custom design system</span>
                                </div>
                            </motion.div>
                        </motion.div>
                    </div>
                </motion.div>

                {/* Getting Started Guide */}
                <motion.div 
                    className="cyber-border p-8 bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30"
                    variants={cardVariants}
                >
                    <motion.div 
                        className="flex items-center gap-3 mb-6"
                        variants={itemVariants}
                    >
                        <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
                        <h3 className="text-xl font-medium lowercase text-foreground font-mono">getting started</h3>
                        <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                        <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                            <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                            <span>quick start</span>
                        </div>
                    </motion.div>

                    <div className="grid md:grid-cols-3 gap-6">
                        {[
                            {
                                step: "01",
                                title: "Create Account",
                                description: "Sign up using WorkOS authentication for secure, enterprise-grade access management.",
                                details: "Quick registration with email verification and secure session handling."
                            },
                            {
                                step: "02", 
                                title: "Choose Your Case",
                                description: "Select from our curated library of OSCE cases covering various medical specialties and difficulty levels.",
                                details: "Cases range from basic clinical skills to complex diagnostic scenarios."
                            },
                            {
                                step: "03",
                                title: "Start Training",
                                description: "Begin your timed session with real-time AI assessment and comprehensive feedback.",
                                details: "Track your progress and identify areas for improvement with detailed analytics."
                            }
                        ].map((item, idx) => (
                            <motion.div 
                                key={item.step}
                                className="text-center"
                                variants={itemVariants}
                            >
                                <motion.div 
                                    className="text-2xl font-mono text-emerald-500 mb-3"
                                    variants={itemVariants}
                                >
                                    {item.step}
                                </motion.div>
                                <motion.h4 
                                    className="text-lg font-medium mb-2 text-foreground"
                                    variants={itemVariants}
                                >
                                    {item.title}
                                </motion.h4>
                                <motion.p 
                                    className="text-sm text-neutral-700 dark:text-neutral-300 mb-2"
                                    variants={itemVariants}
                                >
                                    {item.description}
                                </motion.p>
                                <motion.p 
                                    className="text-xs text-neutral-600 dark:text-neutral-400"
                                    variants={itemVariants}
                                >
                                    {item.details}
                                </motion.p>
                            </motion.div>
                        ))}
                    </div>
                </motion.div>
            </motion.section>

            {/* Enhanced footer */}
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
        </ThemeProvider>
    );
}

export default Landing;
