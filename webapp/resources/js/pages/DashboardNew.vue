<script setup lang="ts">
// Fixed: Added proper null checking for props to prevent undefined errors
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { 
    Stethoscope, 
    Brain, 
    MessageSquare, 
    Users,
    ArrowRight,
    GraduationCap
} from 'lucide-vue-next';

interface Stats {
    osce_cases_active: number;
    forum_posts: number;
    users_total: number;
    mcq_available: number;
}

interface Welcome {
    title: string;
    message: string;
}

interface Props {
    stats?: Stats;
    welcome?: Welcome;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const statCards = [
    {
        title: 'OSCE Cases',
        value: props.stats?.osce_cases_active || 0,
        icon: Stethoscope,
        emoji: '🩺',
        route: '/osce',
        description: 'Active cases available'
    },
    {
        title: 'Available MCQs',
        value: props.stats?.mcq_available || 0,
        icon: Brain,
        emoji: '🧠',
        route: '/mcq',
        description: 'Practice questions'
    },
    {
        title: 'Forum Posts',
        value: props.stats?.forum_posts || 0,
        icon: MessageSquare,
        emoji: '💬',
        route: '/forum',
        description: 'Community discussions'
    },
    {
        title: 'Users',
        value: props.stats?.users_total || 0,
        icon: Users,
        emoji: '👥',
        route: '#',
        description: 'Registered members',
        clickable: false
    }
];

const navigateToRoute = (route: string, clickable: boolean = true) => {
    if (clickable && route !== '#') {
        router.visit(route);
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            
            <!-- Welcome Section -->
            <div class="text-center space-y-4">
                <h1 class="text-3xl font-bold">{{ welcome?.title || 'Welcome back 👋' }}</h1>
                <p class="text-lg text-muted-foreground">{{ welcome?.message || 'Train clinical skills, ace your MCQs, and learn together.' }}</p>
                
                <!-- App Description -->
                <div class="bg-muted/30 rounded-lg p-6 max-w-4xl mx-auto">
                    <div class="flex items-center gap-2 justify-center mb-4">
                        <GraduationCap class="w-6 h-6" />
                        <h2 class="text-xl font-semibold">What is this app?</h2>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4 text-sm">
                        <div class="text-center space-y-2">
                            <div class="text-2xl">🩺</div>
                            <div class="font-medium">OSCE Training</div>
                            <p class="text-muted-foreground">Simulated patient interactions with physical exams and test ordering</p>
                        </div>
                        <div class="text-center space-y-2">
                            <div class="text-2xl">🧠</div>
                            <div class="font-medium">MCQ Practice</div>
                            <p class="text-muted-foreground">Practice multiple-choice questions and learn from explanations</p>
                        </div>
                        <div class="text-center space-y-2">
                            <div class="text-2xl">💬</div>
                            <div class="font-medium">Community Forum</div>
                            <p class="text-muted-foreground">Post questions and discuss cases with peers</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card 
                    v-for="card in statCards" 
                    :key="card.title"
                    :class="[
                        'transition-all duration-200',
                        card.clickable !== false ? 'hover:shadow-md cursor-pointer hover:scale-[1.02]' : ''
                    ]"
                    @click="navigateToRoute(card.route, card.clickable)"
                >
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ card.emoji }} {{ card.title }}
                        </CardTitle>
                        <component :is="card.icon" class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ card.value.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ card.description }}
                        </p>
                        <div v-if="card.clickable !== false && card.route !== '#'" class="flex items-center text-xs text-primary mt-2">
                            <span>Go to {{ card.title.toLowerCase() }}</span>
                            <ArrowRight class="h-3 w-3 ml-1" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <div class="bg-muted/30 rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <Button 
                        variant="outline" 
                        class="h-auto p-4 flex flex-col gap-2 hover:bg-primary/10"
                        @click="router.visit('/osce')"
                    >
                        <Stethoscope class="h-6 w-6" />
                        <span class="font-medium">Start OSCE</span>
                        <span class="text-xs text-muted-foreground">Practice clinical skills</span>
                    </Button>
                    
                    <Button 
                        variant="outline" 
                        class="h-auto p-4 flex flex-col gap-2 hover:bg-primary/10"
                        @click="router.visit('/forum')"
                    >
                        <MessageSquare class="h-6 w-6" />
                        <span class="font-medium">Open Forum</span>
                        <span class="text-xs text-muted-foreground">Join discussions</span>
                    </Button>
                    
                    <Button 
                        variant="outline" 
                        class="h-auto p-4 flex flex-col gap-2 hover:bg-primary/10"
                        @click="router.visit('/mcq')"
                    >
                        <Brain class="h-6 w-6" />
                        <span class="font-medium">Try MCQ</span>
                        <span class="text-xs text-muted-foreground">Test your knowledge</span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
