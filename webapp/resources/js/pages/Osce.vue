<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import ExpandableChat from '@/components/ui/chat/ExpandableChat.vue';
import ExpandableChatHeader from '@/components/ui/chat/ExpandableChatHeader.vue';
import ExpandableChatBody from '@/components/ui/chat/ExpandableChatBody.vue';
import ExpandableChatFooter from '@/components/ui/chat/ExpandableChatFooter.vue';
import ChatMessageList from '@/components/ui/chat/ChatMessageList.vue';
import ChatInput from '@/components/ui/chat/ChatInput.vue';
import ChatBubble from '@/components/ui/chat/ChatBubble.vue';
import ChatBubbleAvatar from '@/components/ui/chat/ChatBubbleAvatar.vue';
import ChatBubbleMessage from '@/components/ui/chat/ChatBubbleMessage.vue';
import ChatBubbleTimestamp from '@/components/ui/chat/ChatBubbleTimestamp.vue';
import ChatBubbleAction from '@/components/ui/chat/ChatBubbleAction.vue';
import ChatBubbleActionWrapper from '@/components/ui/chat/ChatBubbleActionWrapper.vue';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { Clock, Play, BookOpen, CheckCircle, XCircle, AlertCircle } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface OsceCase {
    id: number;
    title: string;
    description: string;
    difficulty: 'easy' | 'medium' | 'hard';
    duration_minutes: number;
    stations: string[];
    scenario: string;
    objectives: string;
    checklist: string[];
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface OsceSession {
    id: number;
    user_id: number;
    osce_case_id: number;
    status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    started_at: string | null;
    completed_at: string | null;
    rationalization_completed_at?: string | null;
    score: number | null;
    max_score: number | null;
    responses: any;
    feedback: any;
    created_at: string;
    updated_at: string;
    osce_case?: OsceCase;
    remaining_seconds?: number;
    duration_minutes?: number;
    time_status?: 'active' | 'expired' | 'completed';
}

const props = defineProps<{
    cases: OsceCase[];
    userSessions: OsceSession[];
    user: any;
}>();

// Create reactive refs for data that can change
const userSessions = ref<OsceSession[]>(props.userSessions);
let timerRefreshInterval: number | undefined;

// Store per-session `setInterval` handles so each row can tick down locally
// between server polls. The key is the session id, the value the interval id.

const sessionCountdowns: Record<number, number> = {};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'OSCE Station',
        href: '/osce',
    },
];

const message = ref('');
const messages = ref<{
    id: number;
    text: string;
    sender: 'user' | 'ai';
    timestamp: string;
}[]>([]);

// Poll the server for authoritative time remaining on each active session.
// This keeps the dashboard in sync even if a user refreshed elsewhere or the
// browser was paused.
async function refreshActiveSessionTimers() {
    const activeSessions = userSessions.value.filter(s => s.status === 'in_progress');
    
    for (const session of activeSessions) {
        try {
            const response = await fetch(`/api/osce/sessions/${session.id}/timer`, {
                headers: { 'Accept': 'application/json' }
            });
            
            if (response.ok) {
                const timerData = await response.json();
                
                // Update session with latest timer data
                const sessionIndex = userSessions.value.findIndex(s => s.id === session.id);
                if (sessionIndex !== -1) {
                    userSessions.value[sessionIndex] = {
                        ...userSessions.value[sessionIndex],
                        remaining_seconds: timerData.remaining_seconds,
                        time_status: timerData.time_status
                    };

                    if (timerData.time_status === 'expired') {
                        userSessions.value[sessionIndex].status = 'completed';
                        clearInterval(sessionCountdowns[session.id]);
                        delete sessionCountdowns[session.id];
                        toast.info(`OSCE session "${session.osce_case?.title}" has expired and been completed.`);
                    } else {
                        startSessionCountdown(session.id, timerData.remaining_seconds);
                    }
                }
            }
        } catch (error) {
            console.warn(`Failed to refresh timer for session ${session.id}:`, error);
        }
    }
}

// Begin polling for timer updates and kick off the initial fetch.
function startTimerRefresh() {
    if (timerRefreshInterval) {
        clearInterval(timerRefreshInterval);
    }
    
    // Refresh timers every 10 seconds for active sessions
    timerRefreshInterval = setInterval(refreshActiveSessionTimers, 10000);
    
    // Initial refresh
    refreshActiveSessionTimers();
}

// Stop polling and clear all local countdowns.
function stopTimerRefresh() {
    if (timerRefreshInterval) {
        clearInterval(timerRefreshInterval);
        timerRefreshInterval = undefined;
    }
    Object.values(sessionCountdowns).forEach(clearInterval);
}
// Maintain a lightweight one‑second countdown for a session row so the user
// sees time tick down in real time between refreshes.

function startSessionCountdown(sessionId: number, seconds: number) {
    if (sessionCountdowns[sessionId]) {
        clearInterval(sessionCountdowns[sessionId]);
    }
    const idx = userSessions.value.findIndex(s => s.id === sessionId);
    if (idx === -1) return;
    userSessions.value[idx].remaining_seconds = seconds;
    if (seconds <= 0) return;
    sessionCountdowns[sessionId] = setInterval(() => {
        const i = userSessions.value.findIndex(s => s.id === sessionId);
        if (i === -1) return;
        const current = userSessions.value[i].remaining_seconds || 0;
        if (current > 0) {
            userSessions.value[i].remaining_seconds = current - 1;
        } else {
            clearInterval(sessionCountdowns[sessionId]);
            delete sessionCountdowns[sessionId];
        }
    }, 1000);
}

const getDifficultyColor = (difficulty: string) => {
    switch (difficulty) {
        case 'easy': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'medium': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'hard': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'in_progress': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'pending': return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
        case 'cancelled': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'completed': return CheckCircle;
        case 'in_progress': return AlertCircle;
        case 'pending': return Clock;
        case 'cancelled': return XCircle;
        default: return Clock;
    }
};

// Guard: can this session be continued from the dashboard?
const canContinue = (s: OsceSession) => s.status === 'in_progress' && s.time_status !== 'expired';

// Derive server-truth flags with safe fallbacks for live updates
const canViewResults = (s: OsceSession) => {
    // Prefer server-provided boolean if present
    const provided = (s as any).canViewResults as boolean | undefined;
    if (typeof provided === 'boolean') return provided;
    // Fallback: allow when rationalization completed
    return !!s.rationalization_completed_at;
};

const canProceedToScoring = (s: OsceSession) => {
    const provided = (s as any).canProceedToScoring as boolean | undefined;
    if (typeof provided === 'boolean') return provided;
    return canViewResults(s);
};

const canRationalize = (s: OsceSession) => {
    const provided = (s as any).canRationalize as boolean | undefined;
    if (typeof provided === 'boolean') return provided;
    return s.status === 'completed' && !canViewResults(s);
};

const isStartingSession = ref<number | null>(null);

const startCase = async (caseId: number) => {
    isStartingSession.value = caseId;

    try {
        const response = await fetch('/api/osce/sessions/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ osce_case_id: caseId })
        });

        if (response.ok) {
            const result = await response.json();
            // Add the new session to the reactive userSessions array
            userSessions.value.unshift(result.session);
            // Show success notification
            toast.success('Session started successfully!', {
                description: `You can now begin working on: ${result.session.osce_case.title}`
            });
            // Optionally, also reload data from server to ensure consistency
            router.reload({ only: ['userSessions'], preserveState: true });
        } else {
            const error = await response.json();
            toast.error('Failed to start session', {
                description: error.message || 'An unexpected error occurred'
            });
        }
    } catch (error) {
        console.error('Error starting session:', error);
        toast.error('Network error', {
            description: 'Please check your connection and try again'
        });
    } finally {
        isStartingSession.value = null;
    }
};

const sendMessage = () => {
    if (message.value.trim() === '') return;

    messages.value.push({
        id: messages.value.length + 1,
        text: message.value,
        sender: 'user',
        timestamp: new Date().toLocaleTimeString(),
    });
    message.value = '';

    // Simulate AI response
    setTimeout(() => {
        messages.value.push({
            id: messages.value.length + 1,
            text: 'Ini adalah respons dari AI.',
            sender: 'ai',
            timestamp: new Date().toLocaleTimeString(),
        });
    }, 1000);
};

onMounted(() => {
    startTimerRefresh();
});

onBeforeUnmount(() => {
    stopTimerRefresh();
});

</script>

<template>

    <Head title="OSCE Station" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Selamat Datang di OSCE Station
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-300">
                    Objective Structured Clinical Examination Platform
                </p>
            </div>

            <!-- Available OSCE Cases -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <BookOpen class="h-6 w-6 text-primary" />
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        Available OSCE Cases
                    </h2>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="case_ in cases" :key="case_.id" class="hover:shadow-lg transition-shadow">
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <CardTitle class="text-lg">{{ case_.title }}</CardTitle>
                                <Badge :class="getDifficultyColor(case_.difficulty)">
                                    {{ case_.difficulty }}
                                </Badge>
                            </div>
                            <CardDescription>{{ case_.description }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center gap-2">
                                    <Clock class="h-4 w-4" />
                                    <span>{{ case_.duration_minutes }} minutes</span>
                                </div>
                                <div>
                                    <p class="font-medium">Stations ({{ case_.stations.length }}):</p>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <Badge v-for="station in case_.stations" :key="station" variant="outline"
                                            class="text-xs">
                                            {{ station }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter>
                            <Button @click="startCase(case_.id)" :disabled="isStartingSession === case_.id"
                                class="w-full">
                                <template v-if="isStartingSession === case_.id">
                                    <div
                                        class="h-4 w-4 mr-2 animate-spin rounded-full border-2 border-current border-t-transparent">
                                    </div>
                                    Starting...
                                </template>
                                <template v-else>
                                    <Play class="h-4 w-4 mr-2" />
                                    Start Case
                                </template>
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </div>

            <!-- User's Recent Sessions -->
            <div class="space-y-4">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Your Recent Sessions</h2>

                <Card>
                    <CardContent class="p-0">
                        <Table v-if="userSessions.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Case</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Time</TableHead>
                                    <TableHead>Started</TableHead>
                                    <TableHead>Score</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="session in userSessions" :key="session.id">
                                    <TableCell>
                                        <div>
                                            <p class="font-medium">{{ session.osce_case?.title }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ session.osce_case?.description }}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getStatusColor(session.status)"
                                            class="flex items-center gap-1 w-fit">
                                            <component :is="getStatusIcon(session.status)" class="h-3 w-3" />
                                            {{ session.status.replace('_', ' ') }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="session.status === 'in_progress' && typeof session.remaining_seconds !== 'undefined'" class="font-mono text-xs">
                                            {{ Math.floor((session.remaining_seconds || 0) / 60).toString().padStart(2,'0') }}:{{ ((session.remaining_seconds || 0) % 60).toString().padStart(2,'0') }}
                                        </span>
                                        <span v-else-if="session.status === 'completed'" class="text-green-600 text-xs">Completed</span>
                                        <span v-else class="text-gray-500 text-xs">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="session.started_at" class="text-sm">
                                            {{ new Date(session.started_at).toLocaleString() }}
                                        </span>
                                        <span v-else class="text-sm text-gray-500">Not started</span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="session.score !== null && session.max_score !== null">
                                            {{ session.score }}/{{ session.max_score }}
                                        </span>
                                        <span v-else class="text-gray-500">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <Button
                                                    v-if="canContinue(session)"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="router.visit(`/osce/chat/${session.id}`)"
                                                >
                                                    Continue
                                                </Button>

                                                <Button
                                                    v-if="canRationalize(session)"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="router.visit(`/osce/rationalization/${session.id}`)"
                                                >
                                                    Rasionalisasi
                                                </Button>

                                                <Button 
                                                    variant="ghost" 
                                                    size="sm"
                                                    :disabled="!canViewResults(session)"
                                                    @click="router.visit(`/osce/results/${session.id}`)"
                                                >
                                                    View Results
                                                </Button>
                                            </div>
                                            <p v-if="!canViewResults(session) && session.status === 'completed'" class="text-xs text-muted-foreground">
                                                Selesaikan rasionalisasi terlebih dahulu.
                                            </p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <div v-else class="p-8 text-center">
                            <p class="text-gray-500 dark:text-gray-400">No sessions found. Start your first OSCE case
                                above!</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Chat UI -->
            <ExpandableChat position="bottom-right" size="md">
                <ExpandableChatHeader>
                    <h3 class="font-bold">Chat OSCE AI</h3>
                </ExpandableChatHeader>
                <ExpandableChatBody>
                    <ChatMessageList>
                        <ChatBubble v-for="msg in messages" :key="msg.id"
                            :variant="msg.sender === 'user' ? 'sent' : 'received'">
                            <ChatBubbleAvatar
                                :src="msg.sender === 'user' ? 'https://github.com/radix-vue.png' : 'https://github.com/google.png'"
                                :fallback="msg.sender === 'user' ? 'U' : 'A'" />
                            <ChatBubbleMessage :isLoading="msg.sender === 'ai' && msg.text === ''">
                                {{ msg.text }}
                            </ChatBubbleMessage>
                            <ChatBubbleTimestamp :timestamp="msg.timestamp" />
                            <ChatBubbleActionWrapper v-if="msg.sender === 'ai'">
                                <!-- <ChatBubbleAction :icon="RefreshCw" /> -->
                            </ChatBubbleActionWrapper>
                        </ChatBubble>
                    </ChatMessageList>
                </ExpandableChatBody>
                <ExpandableChatFooter>
                    <ChatInput v-model="message" placeholder="Ketik pesan Anda..." @keyup.enter="sendMessage" />
                </ExpandableChatFooter>
            </ExpandableChat>
        </div>
    </AppLayout>
</template>
