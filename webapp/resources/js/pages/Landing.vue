<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
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
            <div class="grid w-full grid-cols-1 gap-6 md:grid-cols-5">
                <div class="md:col-span-3">
                    <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader>
                            <div class="flex items-center gap-2">
                                <Badge>Personal</Badge>
                                <Badge variant="outline">Project</Badge>
                            </div>
                            <CardTitle class="text-2xl">Welcome — This is a personal project</CardTitle>
                            <CardDescription>
                                Built with Laravel, Inertia, Vue 3, and ShadCN Vue. Expect small imperfections and ongoing iteration.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm leading-6 text-muted-foreground">
                            <p>
                                This landing page follows the same design language as the rest of the app using our shared UI components and
                                Tailwind theme. It keeps things simple, fast, and consistent.
                            </p>
                            <p>
                                Navigate using the buttons below or the app sidebar once you’re in the dashboard.
                            </p>
                        </CardContent>
                        <CardFooter class="flex flex-wrap gap-3">
                            <Link :href="route('dashboard')">
                                <Button size="lg">Go to Dashboard</Button>
                            </Link>
                            <Link href="/forum">
                                <Button variant="outline" size="lg">Open Forum</Button>
                            </Link>
                            <Link href="/osce">
                                <Button variant="ghost" size="lg">OSCE Module</Button>
                            </Link>
                        </CardFooter>
                    </Card>
                </div>

                <div class="md:col-span-2 flex flex-col gap-4">
                    <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader>
                            <CardTitle>What’s inside</CardTitle>
                            <CardDescription>Key parts of this project</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-2 text-sm text-muted-foreground">
                            <ul class="list-disc space-y-1 pl-5">
                                <li>Single Page App navigation with Inertia</li>
                                <li>Reusable UI with ShadCN Vue</li>
                                <li>Dark mode aware styles</li>
                                <li>Typed props and clean structure</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader>
                            <CardTitle>Notes</CardTitle>
                            <CardDescription>Development disclaimer</CardDescription>
                        </CardHeader>
                        <CardContent class="text-sm text-muted-foreground">
                            This is a personal, evolving project. Features may change, and sections might be experimental.
                        </CardContent>
                    </Card>
                </div>
            </div>
        </main>
    </div>
</template>

