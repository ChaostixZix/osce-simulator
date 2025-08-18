<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, computed, nextTick, watch } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select/index';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { toast } from 'vue-sonner';
import { ArrowLeft, Send, User, Bot, Clock, AlertCircle, CheckCircle, FlaskConical } from 'lucide-vue-next';
import SessionTimer from '@/components/SessionTimer.vue';

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
	ai_patient_profile?: string;
	ai_patient_vitals?: any;
	ai_patient_symptoms?: any;
}

interface OsceSession {
	id: number;
	user_id: number;
	osce_case_id: number;
	status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
	started_at: string | null;
	completed_at: string | null;
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

interface ChatMessage {
	id: number;
	osce_session_id: number;
	sender_type: 'user' | 'ai_patient' | 'system';
	message: string;
	metadata?: any;
	sent_at: string;
	created_at: string;
	updated_at: string;
}

const props = defineProps<{ 
	session: OsceSession; 
	user: any; 
	sessionData?: { 
		lab_results: any[]; 
		procedure_results: any[]; 
		examination_findings: any[]; 
	}; 
}>();

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'OSCE Station', href: '/osce' },
	{ title: 'Chat with AI Patient', href: '#' },
];

const message = ref('');
const messages = ref<ChatMessage[]>([]);
const isLoading = ref(false);
const chatContainer = ref<HTMLElement>();

// Notification system from main
type Notification = { id: number; title: string; description?: string; variant?: 'success' | 'error' | 'info' };
const notifications = ref<Notification[]>([]);
const pushNotification = (n: Omit<Notification, 'id'>) => { 
	const id = Date.now() + Math.floor(Math.random() * 1000); 
	notifications.value.push({ id, ...n }); 
	setTimeout(() => { notifications.value = notifications.value.filter(nn => nn.id !== id); }, 4000); 
};
const removeNotification = (id: number) => { notifications.value = notifications.value.filter(n => n.id !== id); };

// Clinical reasoning ordering state from main
type MedicalTest = { 
	id: number; 
	name: string; 
	category: string; 
	type: 'lab' | 'imaging' | 'procedure' | 'physical_exam'; 
	description?: string; 
	indications?: string[]; 
	contraindications?: string[]; 
	cost: number; 
	turnaround_minutes: number; 
	requires_consent: boolean; 
	risk_level: number; 
	clinicalReasoning?: string; 
	priority?: 'immediate' | 'urgent' | 'routine'; 
};
const showLabModal = ref(false);
const isSubmittingOrders = ref(false);
const testSearchQuery = ref('');
const searchResults = ref<MedicalTest[]>([]);
const selectedTests = ref<MedicalTest[]>([]);

// Results modal state
const showResultsModal = ref(false);
const selectedTestResult = ref<any>(null);
const isRefreshing = ref(false);
const isTestOrdered = (id: number) => selectedTests.value.some(t => t.id === id);
const selectTest = (test: MedicalTest) => { 
	if (!isTestOrdered(test.id)) selectedTests.value.push({ ...test, clinicalReasoning: '', priority: undefined }); 
};
const removeTest = (id: number) => { selectedTests.value = selectedTests.value.filter(t => t.id !== id); };
const clearSelection = () => { selectedTests.value.length = 0; showLabModal.value = false; };
const totalCost = computed(() => selectedTests.value.reduce((sum, t) => sum + (t.cost || 0), 0));
const maxTurnaroundTime = computed(() => selectedTests.value.reduce((max, t) => Math.max(max, t.turnaround_minutes || 0), 0));

const searchMedicalTests = async () => {
	if (testSearchQuery.value.length < 2) { 
		searchResults.value.length = 0; 
		return; 
	}
	try { 
		const resp = await fetch(`/api/medical-tests/search?q=${encodeURIComponent(testSearchQuery.value)}`);
		if (resp.ok) searchResults.value = await resp.json(); 
	} catch (e) { console.error('Search error', e); }
};

const canSubmitOrders = computed(() => selectedTests.value.length > 0 && selectedTests.value.every(t => (t.clinicalReasoning || '').length >= 20 && !!t.priority));

// Results modal functions
const openResultsModal = (test: any) => {
	selectedTestResult.value = test;
	showResultsModal.value = true;
};

const refreshTestResults = async (test: any) => {
	if (isRefreshing.value) return;
	isRefreshing.value = true;
	try {
		// Call the dedicated API endpoint to refresh test results
		const response = await fetch(`/api/osce/refresh-results/${session.value.id}`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
			}
		});

		if (response.ok) {
			const data = await response.json();
			
			// Update session with fresh data
			session.value = data.session;
			
			// Show notification
			pushNotification({ 
				title: 'Results refreshed', 
				description: 'Test results have been updated.', 
				variant: 'success' 
			});
		} else {
			const error = await response.json();
			pushNotification({ 
				title: 'Refresh failed', 
				description: error.message || 'Could not refresh test results.', 
				variant: 'error' 
			});
		}
	} catch (error) {
		pushNotification({ 
			title: 'Network error', 
			description: 'Please check your connection and try again.', 
			variant: 'error' 
		});
	} finally {
		setTimeout(() => { isRefreshing.value = false; }, 1000);
	}
};

const submitTestOrders = async () => {
	if (!canSubmitOrders.value || isSubmittingOrders.value) return;
	isSubmittingOrders.value = true;
	const payload = { 
		session_id: session.value.id, 
		orders: selectedTests.value.map(t => ({ 
			medical_test_id: t.id, 
			clinical_reasoning: t.clinicalReasoning, 
			priority: t.priority 
		})) 
	};
	try {
		const resp = await fetch('/api/osce/order-tests', { 
			method: 'POST', 
			headers: { 
				'Content-Type': 'application/json', 
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' 
			}, 
			body: JSON.stringify(payload) 
		});
		if (!resp.ok) { 
			const err = await resp.json().catch(() => ({})); 
			pushNotification({ title: 'Order failed', description: err?.error || 'Please try again', variant: 'error' }); 
			return; 
		}
		const data = await resp.json();
		pushNotification({ title: 'Tests ordered', description: `${selectedTests.value.length} test(s) have been ordered.`, variant: 'success' });
		selectedTests.value.length = 0; 
		testSearchQuery.value = ''; 
		searchResults.value.length = 0; 
		showLabModal.value = false;
		router.reload({ only: ['sessionData', 'session'] });
	} catch (error) { 
		pushNotification({ title: 'Network error', description: 'Please try again', variant: 'error' }); 
	}
	finally { isSubmittingOrders.value = false; }
};

const session = ref<OsceSession>(props.session);
const osceCase = computed(() => session.value.osce_case);

// Keep local session in sync after partial reloads
watch(() => props.session, (val) => { (session as any).value = val as any; }, { deep: true });

const page = usePage();
const errors = computed(() => page.props.errors || {});

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
		case 'in_progress': return Clock;
		case 'pending': return Clock;
		case 'cancelled': return AlertCircle;
		default: return Clock;
	}
};

const scrollToBottom = async () => { 
	await nextTick(); 
	if (chatContainer.value) chatContainer.value.scrollTop = chatContainer.value.scrollHeight; 
};

const loadChatHistory = async () => {
	try { 
		const response = await fetch(`/api/osce/chat/history/${session.value.id}?limit=100`); 
		if (response.ok) { 
			const data = await response.json(); 
			messages.value = data.messages || []; 
			await scrollToBottom(); 
		} 
	} catch (error) { 
		console.error('Error loading chat history:', error); 
	}
};

const startChat = async () => {
	try {
		isLoading.value = true;
		const response = await fetch('/api/osce/chat/start', { 
			method: 'POST', 
			headers: { 
				'Content-Type': 'application/json', 
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' 
			}, 
			body: JSON.stringify({ session_id: session.value.id }) 
		});
		if (response.ok) { 
			const data = await response.json(); 
			if (data.system_message) { 
				messages.value.push(data.system_message); 
				await scrollToBottom(); 
			} 
		}
		else { 
			const error = await response.json(); 
			pushNotification({ 
				title: 'Failed to start chat', 
				description: error.error || 'An unexpected error occurred', 
				variant: 'error' 
			}); 
		}
	} catch (error) { 
		console.error('Error starting chat:', error); 
		pushNotification({ 
			title: 'Network error', 
			description: 'Please check your connection and try again', 
			variant: 'error' 
		}); 
	}
	finally { isLoading.value = false; }
};

const sendMessage = async () => {
	if (message.value.trim() === '' || isLoading.value) return;
	const userMessage = message.value; 
	message.value = ''; 
	isLoading.value = true;
	try {
		const response = await fetch('/api/osce/chat/message', { 
			method: 'POST', 
			headers: { 
				'Content-Type': 'application/json', 
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' 
			}, 
			body: JSON.stringify({ session_id: session.value.id, message: userMessage }) 
		});
		if (response.ok) { 
			const data = await response.json(); 
			if (data.user_message) messages.value.push(data.user_message); 
			if (data.ai_response) messages.value.push(data.ai_response); 
			await scrollToBottom(); 
		}
		else { 
			const error = await response.json(); 
			pushNotification({ 
				title: 'Failed to send message', 
				description: error.error || 'An unexpected error occurred', 
				variant: 'error' 
			}); 
			message.value = userMessage; 
		}
	} catch (error) { 
		console.error('Error sending message:', error); 
		pushNotification({ 
			title: 'Network error', 
			description: 'Please check your connection and try again', 
			variant: 'error' 
		}); 
		message.value = userMessage; 
	}
	finally { isLoading.value = false; }
};

watch(errors, (newErrors) => { 
	if (newErrors && (newErrors as any).error) { 
		pushNotification({ 
			title: 'Error', 
			description: (newErrors as any).error, 
			variant: 'error' 
		}); 
	} 
}, { deep: true });

// Ordered tests + countdowns
const nowTs = ref<number>(Date.now());
let nowTicker: number | undefined;
const orderedTests = computed<any[]>(() => {
	// Handle both camelCase and snake_case from different data sources
	return (session.value as any)?.ordered_tests || (session.value as any)?.orderedTests || [];
});
const hasPendingTests = computed(() => orderedTests.value.some(t => getTestSecondsRemaining(t) > 0 && !isTestCompleted(t)));

function getTestSecondsRemaining(t: any): number {
	const availableAt = t?.results_available_at ? new Date(t.results_available_at).getTime() : 0;
	const remainingMs = Math.max(0, availableAt - nowTs.value);
	return Math.floor(remainingMs / 1000);
}

function isTestCompleted(t: any): boolean {
	if (t?.completed_at) return true;
	const hasReadyResults = t?.results && (t.results.status === 'ready' || t.results.status === 'no_data' || Object.keys(t.results || {}).length > 0);
	const timeReady = getTestSecondsRemaining(t) === 0 && !!t?.results_available_at;
	return Boolean(hasReadyResults || timeReady);
}

function formatSeconds(sec: number): string {
	const s = Math.max(0, Math.floor(sec));
	const mm = Math.floor(s / 60).toString().padStart(2, '0');
	const ss = (s % 60).toString().padStart(2, '0');
	return `${mm}:${ss}`;
}

onMounted(async () => { 
	await loadChatHistory(); 
	if (messages.value.length === 0) { 
		await startChat(); 
	} 
	// Tick for test countdowns
	nowTicker = window.setInterval(() => { nowTs.value = Date.now(); }, 1000);
	// Light polling to refresh when pending tests exist
	const pollId = window.setInterval(() => {
		if (hasPendingTests.value) {
			router.reload({ only: ['session', 'sessionData'] });
		}
	}, 15000);
	(Object.assign(window as any, { __osce_tests_poll_id: pollId }));
});

function handleSessionExpired() { 
	toast.error('Session expired', { description: 'Your OSCE session time has ended.' }); 
}

function handleSessionCompleted() { 
	toast.success('Session completed', { description: 'Session has been marked as completed.' }); 
}

onBeforeUnmount(() => {
	if (nowTicker) window.clearInterval(nowTicker);
	const pollId = (window as any).__osce_tests_poll_id;
	if (pollId) window.clearInterval(pollId);
});

// Physical examination modal state
type ExamSelection = { category: string; type: string };
const showExamModal = ref(false);
const availableExamMap = computed<Record<string, string[]>>(() => {
	const m = (osceCase.value as any)?.physical_exam_findings || {};
	const result: Record<string, string[]> = {};
	Object.keys(m || {}).forEach((cat) => {
		const types = Object.keys(m[cat] || {});
		if (types.length) result[cat] = types as string[];
	});
	return result;
});
const selectedExams = ref<ExamSelection[]>([]);
function toggleExam(sel: ExamSelection) {
	const idx = selectedExams.value.findIndex(e => e.category === sel.category && e.type === sel.type);
	if (idx >= 0) selectedExams.value.splice(idx, 1); else selectedExams.value.push(sel);
}
const isExamSelected = (sel: ExamSelection) => selectedExams.value.some(e => e.category === sel.category && e.type === sel.type);
const isSubmittingExams = ref(false);
async function submitExams() {
	if (selectedExams.value.length === 0 || isSubmittingExams.value) return;
	isSubmittingExams.value = true;
	try {
		await router.post('/osce/perform-examination', {
			session_id: session.value.id,
			examinations: selectedExams.value
		}, { preserveScroll: true, onError: (e: any) => {
			pushNotification({ title: 'Failed to perform examination', description: (e && (e.error || e.message)) || 'Please try again', variant: 'error' });
		}, onSuccess: () => {
			pushNotification({ title: 'Examination(s) performed', description: `${selectedExams.value.length} selection(s) recorded.`, variant: 'success' });
			selectedExams.value = [];
			showExamModal.value = false;
			router.reload({ only: ['sessionData'] });
		}});
	} finally {
		isSubmittingExams.value = false;
	}
}

// Time configuration controls
const showTimeConfig = ref(false);
const newCaseDuration = ref<number>(osceCase.value?.duration_minutes || 0);
const extendMinutes = ref<number>(5);
const isTimeSaving = ref(false);
async function updateCaseDuration() {
	if (!osceCase.value?.id || newCaseDuration.value <= 0) return;
	isTimeSaving.value = true;
	try {
		const res = await fetch(`/api/osce/cases/${osceCase.value.id}/duration`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '' },
			body: JSON.stringify({ duration_minutes: newCaseDuration.value })
		});
		if (!res.ok) throw new Error('Failed');
		pushNotification({ title: 'Case duration updated', variant: 'success' });
		router.reload({ only: ['session'] });
	} catch (e) {
		pushNotification({ title: 'Failed to update case duration', variant: 'error' });
	} finally { isTimeSaving.value = false; }
}
async function extendSessionTime() {
	if (extendMinutes.value <= 0) return;
	isTimeSaving.value = true;
	try {
		const res = await fetch(`/api/osce/sessions/${session.value.id}/extend`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '' },
			body: JSON.stringify({ minutes: extendMinutes.value })
		});
		if (!res.ok) throw new Error('Failed');
		pushNotification({ title: 'Session time extended', description: `+${extendMinutes.value} minute(s)`, variant: 'success' });
		router.reload({ only: ['session'] });
	} catch (e) {
		pushNotification({ title: 'Failed to extend session', variant: 'error' });
	} finally { isTimeSaving.value = false; }
}
</script>

<template>
	<Head title="OSCE Chat - AI Patient" />
	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
			<!-- Notification Container -->
			<div class="fixed top-4 right-4 z-50 space-y-2">
				<div v-for="n in notifications" :key="n.id" class="rounded shadow px-4 py-3 text-sm"
					:class="{
						'bg-green-600 text-white': n.variant === 'success',
						'bg-red-600 text-white': n.variant === 'error',
						'bg-gray-900 text-white': !n.variant || n.variant === 'info',
					}">
					<div class="flex items-start justify-between gap-3">
						<div>
							<div class="font-medium">{{ n.title }}</div>
							<div v-if="n.description" class="text-xs opacity-90">{{ n.description }}</div>
						</div>
						<button class="text-white/80 hover:text-white" @click="removeNotification(n.id)">✕</button>
					</div>
				</div>
			</div>

			<!-- Header -->
			<div class="flex items-center justify-between">
				<div class="flex items-center gap-4">
					<Button variant="outline" size="sm" @click="router.visit('/osce')">
						<ArrowLeft class="h-4 w-4 mr-2" />Back to OSCE
					</Button>
					<div>
						<h1 class="text-2xl font-bold text-gray-900 dark:text-white">OSCE Chat Session</h1>
						<p class="text-sm text-gray-600 dark:text-gray-400">Chat with AI Patient for Case: {{ osceCase?.title }}</p>
					</div>
				</div>
				<div class="flex items-center gap-2">
					<Badge :class="getStatusColor(session.status)" class="flex items-center gap-1">
						<component :is="getStatusIcon(session.status)" class="h-3 w-3" />
						{{ session.status.replace('_', ' ') }}
					</Badge>
					<Badge :class="getDifficultyColor(osceCase?.difficulty || 'medium')">
						{{ osceCase?.difficulty || 'medium' }}
					</Badge>
				</div>
			</div>

			<!-- Timer -->
			<div>
				<SessionTimer
					:session-id="session.id"
					:initial-time-remaining="session.remaining_seconds || 0"
					:duration-minutes="session.duration_minutes || (osceCase?.duration_minutes || 0)"
					:status="(session as any).time_status || 'active'"
					@session-expired="handleSessionExpired"
					@session-completed="handleSessionCompleted"
				/>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
				<!-- Left Sidebar -->
				<div class="lg:col-span-1 space-y-4 max-h-screen overflow-y-auto">
					<!-- Case Overview -->
					<Card>
						<CardHeader>
							<CardTitle class="text-lg">Case Overview</CardTitle>
						</CardHeader>
						<CardContent class="space-y-3 text-sm">
							<div>
								<div class="font-semibold">Scenario</div>
								<div class="text-gray-600 dark:text-gray-300">{{ osceCase?.scenario || '—' }}</div>
							</div>
							<div>
								<div class="font-semibold">Objectives</div>
								<div class="text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ osceCase?.objectives || '—' }}</div>
							</div>
							<div v-if="(osceCase as any)?.ai_patient_vitals">
								<div class="font-semibold">Vital Signs</div>
								<div class="grid grid-cols-2 gap-2">
									<div v-for="(v, k) in (osceCase as any).ai_patient_vitals" :key="k" class="flex justify-between">
										<span class="text-gray-500">{{ k }}</span>
										<span class="font-medium">{{ v }}</span>
									</div>
								</div>
							</div>
							<div class="flex items-center justify-between text-xs text-gray-500">
								<span>Difficulty: {{ osceCase?.difficulty || '—' }}</span>
								<span>Duration: {{ osceCase?.duration_minutes }} min</span>
							</div>
						</CardContent>
					</Card>

					<!-- Actions -->
					<Card>
						<CardHeader>
							<CardTitle class="text-lg">Medical Actions</CardTitle>
						</CardHeader>
						<CardContent class="space-y-3">
							<Dialog v-model:open="showLabModal">
								<DialogTrigger asChild>
									<Button variant="outline" class="w-full flex items-center gap-2">
										<FlaskConical class="h-4 w-4" />Order Tests
									</Button>
								</DialogTrigger>
								<DialogContent class="max-w-3xl">
									<DialogHeader>
										<DialogTitle>Order Medical Tests</DialogTitle>
										<DialogDescription>Search and select tests. Provide reasoning and priority.</DialogDescription>
									</DialogHeader>
									<div class="space-y-4">
										<div>
											<Input v-model="testSearchQuery" placeholder="Search tests... (e.g. 'troponin', 'ecg', 'chest x-ray')" @input="searchMedicalTests" />
											<div v-if="searchResults.length > 0" class="mt-2 space-y-1">
												<div v-for="t in searchResults" :key="t.id" class="p-2 border rounded flex items-center justify-between">
													<div>
														<div class="font-medium">{{ t.name }}</div>
														<div class="text-xs text-gray-500">{{ t.category }} • {{ t.type }}</div>
													</div>
													<Button size="sm" variant="outline" :disabled="isTestOrdered(t.id)" @click="selectTest(t)">
														{{ isTestOrdered(t.id) ? 'Selected' : 'Select' }}
													</Button>
												</div>
											</div>
										</div>

										<div v-if="selectedTests.length > 0" class="space-y-3">
											<div v-for="t in selectedTests" :key="t.id" class="p-3 rounded border space-y-2">
												<div class="flex items-center justify-between">
													<div class="font-medium">{{ t.name }}</div>
													<Button size="sm" variant="outline" @click="removeTest(t.id)">Remove</Button>
												</div>
												<div class="grid grid-cols-1 md:grid-cols-2 gap-2">
													<Textarea v-model="t.clinicalReasoning" placeholder="Provide clinical reasoning (min 20 chars)" :rows="3" />
													<Select v-model="(t as any).priority">
														<SelectTrigger><SelectValue placeholder="Priority" /></SelectTrigger>
														<SelectContent>
															<SelectItem value="immediate">Immediate</SelectItem>
															<SelectItem value="urgent">Urgent</SelectItem>
															<SelectItem value="routine">Routine</SelectItem>
														</SelectContent>
													</Select>
												</div>
											</div>

											<div class="flex items-center justify-between text-sm">
												<div>Total selected: {{ selectedTests.length }}</div>
												<div>Estimated total cost: ${{ totalCost.toFixed(2) }}</div>
												<div>Max turnaround: {{ maxTurnaroundTime }} min</div>
											</div>

											<div class="flex justify-end gap-2">
												<Button variant="outline" @click="clearSelection">Cancel</Button>
												<Button :disabled="!canSubmitOrders || isSubmittingOrders" @click="submitTestOrders">
													{{ isSubmittingOrders ? 'Submitting...' : 'Submit Orders' }}
												</Button>
											</div>
										</div>
									</div>
								</DialogContent>
							</Dialog>

							<!-- Physical Examination -->
							<Dialog v-model:open="showExamModal">
								<DialogTrigger asChild>
									<Button variant="outline" class="w-full">Perform Physical Examination</Button>
								</DialogTrigger>
								<DialogContent class="max-w-2xl">
									<DialogHeader>
										<DialogTitle>Select Examinations</DialogTitle>
										<DialogDescription>Choose categories and types to perform.</DialogDescription>
									</DialogHeader>
									<div class="space-y-3 max-h-[60vh] overflow-y-auto">
										<div v-if="Object.keys(availableExamMap).length === 0" class="text-sm text-gray-500">No examination options configured for this case.</div>
										<div v-for="(types, category) in availableExamMap" :key="category" class="border rounded">
											<div class="px-3 py-2 font-medium bg-gray-50 dark:bg-gray-800">{{ category }}</div>
											<div class="p-3 grid grid-cols-2 gap-2">
												<button type="button" v-for="t in types" :key="t" @click="toggleExam({ category: String(category), type: String(t) })" class="text-xs px-2 py-1 rounded border"
													:class="isExamSelected({ category: String(category), type: String(t) }) ? 'bg-blue-600 text-white' : ''">
													{{ t }}
												</button>
											</div>
										</div>
									</div>
									<div class="flex justify-end gap-2 mt-2">
										<Button variant="outline" @click="showExamModal = false">Cancel</Button>
										<Button :disabled="selectedExams.length === 0 || isSubmittingExams" @click="submitExams">{{ isSubmittingExams ? 'Submitting...' : 'Perform' }}</Button>
									</div>
								</DialogContent>
							</Dialog>

							<!-- Results Display Modal -->
							<Dialog v-model:open="showResultsModal">
								<DialogContent class="max-w-2xl">
									<DialogHeader>
										<DialogTitle class="flex items-center gap-2">
											<FlaskConical class="h-5 w-5 text-blue-600" />
											Test Results: {{ selectedTestResult?.test_name }}
										</DialogTitle>
										<DialogDescription>
											{{ selectedTestResult?.test_type === 'lab' ? 'Laboratory' : selectedTestResult?.test_type === 'procedure' ? 'Procedure' : 'Imaging' }} results for {{ selectedTestResult?.test_name }}
										</DialogDescription>
									</DialogHeader>
									<div v-if="selectedTestResult?.results" class="space-y-4">
										<!-- Formatted Results Display -->
										<div v-if="selectedTestResult.results.values" class="space-y-3">
											<h4 class="font-medium text-gray-900 dark:text-white">Test Values:</h4>
											<div class="grid gap-2 text-sm">
												<div v-for="(value, key) in selectedTestResult.results.values" :key="key" 
													 class="flex justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
													<span class="font-medium capitalize">{{ String(key).replace(/_/g, ' ') }}:</span>
													<span>{{ value }}</span>
												</div>
											</div>
										</div>
										
										<!-- Interpretation -->
										<div v-if="selectedTestResult.results.interpretation" class="space-y-2">
											<h4 class="font-medium text-gray-900 dark:text-white">Interpretation:</h4>
											<div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-sm">
												{{ selectedTestResult.results.interpretation }}
											</div>
										</div>
										
										<!-- Reference Range -->
										<div v-if="selectedTestResult.results.reference_range" class="space-y-2">
											<h4 class="font-medium text-gray-900 dark:text-white">Reference Range:</h4>
											<div class="text-sm text-gray-600 dark:text-gray-400">
												{{ selectedTestResult.results.reference_range }}
											</div>
										</div>
										
										<!-- Status and Timing -->
										<div class="flex items-center justify-between pt-2 text-xs text-gray-500 border-t">
											<span>Status: {{ selectedTestResult.results.status || 'completed' }}</span>
											<span v-if="selectedTestResult.results.turnaround_time_minutes">
												Turnaround: {{ selectedTestResult.results.turnaround_time_minutes }} min
											</span>
										</div>
										
										<!-- Raw JSON fallback for complex results -->
										<details v-if="Object.keys(selectedTestResult.results || {}).length > 0" class="mt-4">
											<summary class="text-xs text-gray-500 cursor-pointer">View Raw Data</summary>
											<pre class="mt-2 text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded overflow-auto max-w-full max-h-48 whitespace-pre-wrap break-words">{{ JSON.stringify(selectedTestResult.results, null, 2) }}</pre>
										</details>
									</div>
									
									<div v-else class="text-center py-8 text-gray-500">
										<FlaskConical class="h-12 w-12 mx-auto mb-4 text-gray-300" />
										<p>No detailed results available for this test.</p>
									</div>
								</DialogContent>
							</Dialog>
						</CardContent>
					</Card>

					<!-- Ordered Tests & Results -->
					<Card>
						<CardHeader>
							<CardTitle class="text-lg">Ordered Tests & Results</CardTitle>
						</CardHeader>
						<CardContent class="space-y-3 text-sm">
							<div v-if="orderedTests.length === 0" class="text-gray-500">No tests ordered yet.</div>
							<div v-for="t in orderedTests" :key="t.id" class="border rounded p-3 space-y-1">
								<div class="flex items-center justify-between">
									<div class="font-medium">{{ t.test_name }} <span class="text-xs text-gray-500">({{ t.test_type }})</span></div>
									<Badge :class="isTestCompleted(t) ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'">
										{{ isTestCompleted(t) ? 'Ready' : 'Pending' }}
									</Badge>
								</div>
								<div v-if="!isTestCompleted(t)" class="flex items-center gap-2 text-xs text-gray-600">
									<div class="w-3 h-3 rounded-full border-2 border-gray-300 border-t-blue-500 animate-spin"></div>
									<span>Results in ~ {{ formatSeconds(getTestSecondsRemaining(t)) }}</span>
								</div>
								<div v-else class="space-y-2">
									<div v-if="t.results && Object.keys(t.results || {}).length">
										<Button size="sm" variant="outline" class="w-full" @click="openResultsModal(t)">
											<FlaskConical class="h-3 w-3 mr-1" />
											View Results
										</Button>
									</div>
									<div v-else>
										<Button size="sm" variant="outline" class="w-full" @click="refreshTestResults(t)" :disabled="isRefreshing">
											<FlaskConical class="h-3 w-3 mr-1" />
											{{ isRefreshing ? 'Loading...' : 'Load Results' }}
										</Button>
									</div>
								</div>
							</div>
						</CardContent>
					</Card>

					<!-- Time Configuration -->
					<Card>
						<CardHeader>
							<CardTitle class="text-lg flex items-center justify-between">Time Configuration
								<Button size="sm" variant="outline" @click="showTimeConfig = !showTimeConfig">{{ showTimeConfig ? 'Hide' : 'Edit' }}</Button>
							</CardTitle>
						</CardHeader>
						<CardContent v-if="showTimeConfig" class="space-y-3 text-sm">
							<div class="space-y-2">
								<div class="text-xs text-gray-500">Case duration (minutes)</div>
								<Input type="number" v-model.number="newCaseDuration" min="1" />
								<div class="flex justify-end">
									<Button :disabled="isTimeSaving || newCaseDuration <= 0" @click="updateCaseDuration">Save</Button>
								</div>
							</div>
							<div class="space-y-2">
								<div class="text-xs text-gray-500">Extend current session (minutes)</div>
								<Input type="number" v-model.number="extendMinutes" min="1" />
								<div class="flex justify-end">
									<Button :disabled="isTimeSaving || extendMinutes <= 0" @click="extendSessionTime">Extend</Button>
								</div>
							</div>
						</CardContent>
					</Card>

				</div>

				<!-- Chat Area -->
				<div class="lg:col-span-2 flex flex-col h-full">
					<Card class="flex-1 flex flex-col">
						<CardHeader>
							<CardTitle class="flex items-center gap-2">
								<Bot class="h-5 w-5 text-blue-600" />AI Patient Chat
							</CardTitle>
							<CardDescription>Ask questions and interact with the AI patient to practice your clinical skills</CardDescription>
						</CardHeader>
						<CardContent class="flex-1 flex flex-col p-0">
							<div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-4 max-h-96">
								<div v-if="messages.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
									<Bot class="h-12 w-12 mx-auto mb-4 text-gray-300" />
									<p>No messages yet. Start the conversation!</p>
								</div>
								<div v-for="msg in messages" :key="msg.id" class="flex gap-3">
									<div v-if="msg.sender_type === 'user'" class="flex-1 flex justify-end">
										<div class="flex items-end gap-2 max-w-xs lg:max-w-md">
											<div class="bg-blue-600 text-white rounded-lg px-3 py-2 text-sm">{{ msg.message }}</div>
											<User class="h-6 w-6 text-blue-600" />
										</div>
									</div>
									<div v-else-if="msg.sender_type === 'ai_patient'" class="flex-1 flex justify-start">
										<div class="flex items-end gap-2 max-w-xs lg:max-w-md">
											<Bot class="h-6 w-6 text-green-600" />
											<div class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg px-3 py-2 text-sm">{{ msg.message }}</div>
										</div>
									</div>
									<div v-else-if="msg.sender_type === 'system'" class="flex-1 flex justify-center">
										<div class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-lg px-3 py-2 text-xs text-center max-w-md">{{ msg.message }}</div>
									</div>
								</div>
								<div v-if="isLoading" class="flex justify-start">
									<div class="flex items-end gap-2">
										<Bot class="h-6 w-6 text-green-600" />
										<div class="bg-gray-100 dark:bg-gray-800 rounded-lg px-3 py-2">
											<div class="flex space-x-1">
												<div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
												<div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
												<div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
											</div>
										</div>
									</div>
								                                                                </div>
                                                        </div>
                                                        <div class="border-t p-4">
									<div class="flex gap-2">
										<Textarea v-model="message" placeholder="Type your question or message to the AI patient..." class="flex-1 resize-none" :rows="2" @keydown="(e: KeyboardEvent) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); } }" :disabled="isLoading" />
										<Button @click="sendMessage" :disabled="isLoading || !message.trim()" class="px-4">
											<Send class="h-4 w-4" />
										</Button>
									</div>
									<p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Press Enter to send, Shift+Enter for new line</p>
								</div>
						</CardContent>
					</Card>
				</div>
			</div>
		</div>
	</AppLayout>
</template>