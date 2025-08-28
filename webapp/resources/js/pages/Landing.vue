<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const currentPortfolio = ref(0);

const nextPortfolio = () => {
    currentPortfolio.value = (currentPortfolio.value + 1) % portfolioItems.length;
};

const prevPortfolio = () => {
    currentPortfolio.value = (currentPortfolio.value - 1 + portfolioItems.length) % portfolioItems.length;
};
</script>

<template>
    <Head title="Welcome" />
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="w-full border-b border-sidebar-border/70">
            <div class="mx-auto flex h-14 w-full items-center justify-end px-4 md:max-w-6xl">
                <Link v-if="$page.props.auth.user" :href="route('dashboard')">
                    <Button size="sm" variant="outline">Dashboard</Button>
                </Link>
                <template v-else>
                    <Link :href="route('login')">
                        <Button size="sm" variant="outline">Log in</Button>
                    </Link>
                </template>
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-6xl flex-1 items-center px-4 py-10">
            <div class="flex w-full flex-col gap-6">
                <!-- About the Creator Card - Full Width -->
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <Badge>Personal</Badge>
                            <Badge variant="outline">Project</Badge>
                        </div>
                        <CardTitle class="text-2xl">About the Creator — Bintang Putra</CardTitle>
                        <CardDescription>
                            Medical student with a passion for technology and programming, specializing in PHP and web development.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm leading-6 text-muted-foreground">
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
                                class="inline-flex items-center gap-1 text-primary hover:underline"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.30.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
                                    />
                                </svg>
                                Check my GitHub profile for more projects
                            </a>
                        </div>
                    </CardContent>
                    <CardFooter class="flex flex-wrap gap-3">
                        <Link :href="route('dashboard')">
                            <Button size="lg">Go to Dashboard</Button>
                        </Link>
                        
                        <Link href="/osce">
                            <Button variant="ghost" size="lg">Try OSCE Training</Button>
                        </Link>
                    </CardFooter>
                </Card>

                <!-- Portfolio Carousel -->
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader>
                        <CardTitle>Portfolio</CardTitle>
                        <CardDescription>A glimpse of my work (placeholders)</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-4">
                            <Button variant="outline" size="icon" class="h-8 w-8 shrink-0" @click="prevPortfolio">
                                <span class="sr-only">Previous</span>
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                            </Button>
                            <div class="flex w-full flex-col items-center">
                                <img :src="portfolioItems[currentPortfolio].image" alt="" class="mb-4 w-full max-w-sm rounded" />
                                <h3 class="text-lg font-medium">
                                    {{ portfolioItems[currentPortfolio].title }}
                                </h3>
                                <p class="mt-2 text-center text-sm text-muted-foreground">
                                    {{ portfolioItems[currentPortfolio].description }}
                                </p>
                            </div>
                            <Button variant="outline" size="icon" class="h-8 w-8 shrink-0" @click="nextPortfolio">
                                <span class="sr-only">Next</span>
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Academic History -->
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader>
                        <CardTitle>Academic History</CardTitle>
                        <CardDescription>Where I've studied and contributed</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm leading-6 text-muted-foreground">
                        <ul class="list-disc space-y-1 pl-5">
                            <li><strong>High School:</strong> Lorem Ipsum High School (2015-2018)</li>
                            <li><strong>Organization:</strong> Student Council Member</li>
                            <li><strong>Organization:</strong> Science Club Treasurer</li>
                        </ul>
                    </CardContent>
                </Card>

                <!-- Project Features - Two Column Layout -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader>
                            <CardTitle>OSCE Training Module</CardTitle>
                            <CardDescription>Interactive medical examination practice</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm text-muted-foreground">
                            <p>
                                The OSCE (Objective Structured Clinical Examination) module provides realistic patient scenarios for medical students
                                to practice their clinical skills and examination techniques.
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li>AI-powered patient interactions</li>
                                <li>Structured clinical scenarios</li>
                                <li>Real-time feedback and scoring</li>
                                <li>Progress tracking and analytics</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader>
                            <CardTitle>Technical Features</CardTitle>
                            <CardDescription>Built with modern web technologies</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm text-muted-foreground">
                            <p>
                                This application showcases modern web development practices, combining Laravel's robust backend with Vue.js for a
                                seamless user experience.
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li>Laravel 12 with Inertia.js for SPA navigation</li>
                                <li>Vue 3 with TypeScript support</li>
                                <li>ShadCN Vue for consistent UI components</li>
                                <li>Tailwind CSS v4 for responsive design</li>
                                <li>Dark mode support throughout</li>
                            </ul>
                            <p class="mt-3 text-xs">This is a personal, evolving project. Features may change, and sections might be experimental.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </main>
    </div>
</template>
