<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed, nextTick, watch } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { toast } from 'vue-sonner';
import { ArrowLeft, Send, User, Bot, Clock, AlertCircle, CheckCircle, FlaskConical } from 'lucide-vue-next';

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
	{
		title: 'OSCE Station',
		href: '/osce',
	},
	{
		title: 'Chat with AI Patient',
		href: '#',
	},
];

const message = ref('');
const messages = ref<ChatMessage[]>([]);
const isLoading = ref(false);
const chatContainer = ref<HTMLElement>();

// UI state
const showLabModal = ref(false);

// Clinical reasoning-based ordering UI state
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

const session = ref<OsceSession>(props.session);
const osceCase = computed(() => session.value.osce_case);

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
	if (chatContainer.value) {
		chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
	}
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
		} else {
			const error = await response.json();
			toast.error('Failed to start chat', {
				description: error.error || 'An unexpected error occurred'
			});
		}
	} catch (error) {
		console.error('Error starting chat:', error);
		toast.error('Network error', {
			description: 'Please check your connection and try again'
		});
	} finally {
		isLoading.value = false;
	}
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
			body: JSON.stringify({
				session_id: session.value.id,
				message: userMessage
			})
		});

		if (response.ok) {
			const data = await response.json();
			if (data.user_message) messages.value.push(data.user_message);
			if (data.ai_response) messages.value.push(data.ai_response);
			await scrollToBottom();
		} else {
			const error = await response.json();
			toast.error('Failed to send message', {
				description: error.error || 'An unexpected error occurred'
			});
			message.value = userMessage;
		}
	} catch (error) {
		console.error('Error sending message:', error);
		toast.error('Network error', {
			description: 'Please check your connection and try again'
		});
		message.value = userMessage;
	} finally {
		isLoading.value = false;
	}
};

const handleKeyPress = (event: KeyboardEvent) => {
	if (event.key === 'Enter' && !event.shiftKey) {
		event.preventDefault();
		sendMessage();
	}
};

// Clinical reasoning ordering state and actions
const testSearchQuery = ref('');
const searchResults = ref<MedicalTest[]>([]);
const selectedTests = ref<MedicalTest[]>([]);

const isTestOrdered = (id: number) => selectedTests.value.some(t => t.id === id);
const selectTest = (test: MedicalTest) => {
	if (!isTestOrdered(test.id)) selectedTests.value.push({ ...test, clinicalReasoning: '', priority: undefined });
};
const removeTest = (id: number) => { selectedTests.value = selectedTests.value.filter(t => t.id !== id); };
const totalCost = computed(() => selectedTests.value.reduce((sum, t) => sum + (t.cost || 0), 0));
const maxTurnaroundTime = computed(() => selectedTests.value.reduce((max, t) => Math.max(max, t.turnaround_minutes || 0), 0));

const searchMedicalTests = async () => {
	if (testSearchQuery.value.length < 2) { searchResults.value = []; return; }
	try {
		const resp = await fetch(`/api/medical-tests/search?q=${encodeURIComponent(testSearchQuery.value)}`);
		if (resp.ok) searchResults.value = await resp.json();
	} catch (e) { console.error('Search error', e); }
};

const canSubmitOrders = computed(() => selectedTests.value.length > 0 && selectedTests.value.every(t => (t.clinicalReasoning || '').length >= 20 && !!t.priority));
const submitTestOrders = async () => {
	if (!canSubmitOrders.value) return;
	const orders = selectedTests.value.map(t => ({ medical_test_id: t.id, clinical_reasoning: t.clinicalReasoning, priority: t.priority }));
	router.post('/api/osce/order-tests', { session_id: session.value.id, orders }, {
		preserveState: true,
		preserveScroll: true,
		onSuccess: () => {
			toast.success('Tests Ordered Successfully', { description: `${selectedTests.value.length} tests have been ordered.` });
			selectedTests.value = [];
			testSearchQuery.value = '';
			searchResults.value = [];
		},
		onError: (errors: any) => { toast.error('Failed to order tests', { description: errors?.error || 'Please try again' }); }
	});
};

watch(errors, (newErrors) => {
	if (newErrors && (newErrors as any).error) {
		toast.error('Error', { description: (newErrors as any).error });
	}
}, { deep: true });

onMounted(async () => {
	await loadChatHistory();
	if (messages.value.length === 0) {
		await startChat();
	}
});
</script>

<template>
	<Head title="OSCE Chat - AI Patient" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
			<!-- Header -->
			<div class="flex items-center justify-between">
				<div class="flex items-center gap-4">
					<Button variant="outline" size="sm" @click="router.visit('/osce')">
						<ArrowLeft class="h-4 w-4 mr-2" />
						Back to OSCE
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
					<Badge :class="getDifficultyColor(osceCase?.difficulty || 'medium')">{{ osceCase?.difficulty || 'medium' }}</Badge>
				</div>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
				<!-- Left Sidebar -->
				<div class="lg:col-span-1 space-y-4 max-h-screen overflow-y-auto">
					<!-- Actions -->
					<Card>
						<CardHeader>
							<CardTitle class="text-lg">Medical Actions</CardTitle>
						</CardHeader>
						<CardContent class="space-y-3">
							<Dialog v-model:open="showLabModal">
								<DialogTrigger asChild>
									<Button variant="outline" class="w-full flex items-center gap-2">
										<FlaskConical class="h-4 w-4" />
										Order Tests
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
											<div v-if="searchResults.length" class="mt-2 max-h-48 overflow-y-auto space-y-2">
												<div v-for="test in searchResults" :key="test.id" class="p-2 border rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer" :class="{ 'opacity-60 pointer-events-none': isTestOrdered(test.id) }" @click="selectTest(test)">
													<div class="flex justify-between text-sm font-medium">
														<span>{{ test.name }}</span>
														<Badge variant="outline">{{ test.category }}</Badge>
													</div>
													<div class="text-xs text-gray-500 mt-1 flex gap-2 items-center">
														<Badge>${{ test.cost }}</Badge>
														<Badge>{{ test.turnaround_minutes }}min</Badge>
														<Badge v-if="test.requires_consent" variant="destructive">Consent</Badge>
														<Badge v-if="test.risk_level > 3" variant="destructive">High Risk</Badge>
													</div>
												</div>
											</div>
										</div>
										<div>
											<h4 class="font-medium mb-2">Selected Tests ({{ selectedTests.length }})</h4>
											<div v-if="!selectedTests.length" class="text-sm text-gray-500">No tests selected yet.</div>
											<div v-for="t in selectedTests" :key="t.id" class="mb-3 border rounded p-3">
												<div class="flex justify-between items-start mb-2">
													<div>
														<div class="text-sm font-medium">{{ t.name }}</div>
														<div class="text-xs text-gray-500">{{ t.category }} • ${{ t.cost }}</div>
													</div>
													<Button variant="ghost" size="sm" @click="removeTest(t.id)">✕</Button>
												</div>
												<div class="space-y-2">
													<label class="text-xs">Clinical Reasoning (required)</label>
													<Textarea v-model="t.clinicalReasoning" rows="3" placeholder="Explain why you're ordering this test..." />
													<label class="text-xs">Priority</label>
													<Select v-model="t.priority">
														<SelectTrigger>
															<SelectValue placeholder="Select priority" />
														</SelectTrigger>
														<SelectContent>
															<SelectItem value="immediate">STAT (Immediate)</SelectItem>
															<SelectItem value="urgent">Urgent (1 hour)</SelectItem>
															<SelectItem value="routine">Routine</SelectItem>
														</SelectContent>
													</Select>
												</div>
											</div>
										</div>
										<div class="flex items-center justify-between text-sm">
											<div class="flex gap-4">
												<div>Tests: <span class="font-medium">{{ selectedTests.length }}</span></div>
												<div>Total Cost: <span class="font-medium">${{ totalCost.toFixed(2) }}</span></div>
												<div>Max ETA: <span class="font-medium">{{ maxTurnaroundTime }} min</span></div>
											</div>
											<Button class="ml-auto" :disabled="!canSubmitOrders" @click="submitTestOrders">Submit Orders</Button>
										</div>
										<p v-if="!canSubmitOrders && selectedTests.length" class="text-xs text-red-600">Provide reasoning (min 20 chars) and priority for all tests.</p>
									</div>
								</DialogContent>
							</Dialog>
						</CardContent>
					</Card>

					<!-- Case Information -->
					<Card>
						<CardHeader>
							<CardTitle class="text-lg">Case Information</CardTitle>
						</CardHeader>
						<CardContent class="space-y-4">
							<div>
								<h4 class="font-medium text-gray-900 dark:text-white mb-2">Scenario</h4>
								<p class="text-sm text-gray-600 dark:text-gray-400">{{ osceCase?.scenario || 'No scenario provided' }}</p>
							</div>
							<div>
								<h4 class="font-medium text-gray-900 dark:text-white mb-2">Objectives</h4>
								<p class="text-sm text-gray-600 dark:text-gray-400">{{ osceCase?.objectives || 'No objectives provided' }}</p>
							</div>
							<div v-if="osceCase?.ai_patient_profile">
								<h4 class="font-medium text-gray-900 dark:text-white mb-2">Patient Profile</h4>
								<p class="text-sm text-gray-600 dark:text-gray-400">{{ osceCase.ai_patient_profile }}</p>
							</div>
							<div v-if="osceCase?.ai_patient_symptoms">
								<h4 class="font-medium text-gray-900 dark:text-white mb-2">Patient Symptoms</h4>
								<div class="flex flex-wrap gap-1">
									<Badge v-for="symptom in osceCase.ai_patient_symptoms" :key="symptom" variant="outline" class="text-xs">{{ symptom }}</Badge>
								</div>
							</div>
							<div v-if="osceCase?.ai_patient_vitals">
								<h4 class="font-medium text-gray-900 dark:text-white mb-2">Vital Signs</h4>
								<div class="grid grid-cols-2 gap-2 text-sm">
									<div v-for="(value, key) in osceCase.ai_patient_vitals" :key="key" class="flex justify-between">
										<span class="text-gray-600 dark:text-gray-400">{{ key }}:</span>
										<span class="font-medium">{{ value }}</span>
									</div>
								</div>
							</div>
						</CardContent>
					</Card>

					<!-- Lab Results -->
					<Card v-if="props.sessionData?.lab_results && props.sessionData.lab_results.length > 0">
						<CardHeader>
							<CardTitle class="text-lg flex items-center gap-2">Lab Results</CardTitle>
						</CardHeader>
						<CardContent class="space-y-3">
							<div v-for="test in props.sessionData?.lab_results" :key="test.id" class="border-l-4 border-blue-500 pl-3">
								<div class="font-medium text-sm">{{ test.test_name }}</div>
								<div class="text-xs text-gray-500 mb-2">{{ new Date(test.ordered_at).toLocaleString() }}</div>
								<div class="text-sm space-y-1">
									<div v-for="(value, key) in test.results" :key="key" class="flex justify-between">
										<span class="text-gray-600 dark:text-gray-400">{{ key }}:</span>
										<span class="font-mono text-xs">{{ value }}</span>
									</div>
								</div>
							</div>
						</CardContent>
					</Card>

					<!-- Procedure Results -->
					<Card v-if="props.sessionData?.procedure_results && props.sessionData.procedure_results.length > 0">
						<CardHeader>
							<CardTitle class="text-lg flex items-center gap-2">Procedure Results</CardTitle>
						</CardHeader>
						<CardContent class="space-y-3">
							<div v-for="procedure in props.sessionData?.procedure_results" :key="procedure.id" class="border-l-4 border-green-500 pl-3">
								<div class="font-medium text-sm">{{ procedure.test_name }}</div>
								<div class="text-xs text-gray-500 mb-2">{{ new Date(procedure.ordered_at).toLocaleString() }}</div>
								<div class="text-sm text-gray-700 dark:text-gray-300">{{ procedure.results?.description || JSON.stringify(procedure.results) }}</div>
							</div>
						</CardContent>
					</Card>

					<!-- Physical Examination Findings -->
					<Card v-if="props.sessionData?.examination_findings && props.sessionData.examination_findings.length > 0">
						<CardHeader>
							<CardTitle class="text-lg flex items-center gap-2">Examination Findings</CardTitle>
						</CardHeader>
						<CardContent class="space-y-3">
							<div v-for="exam in props.sessionData?.examination_findings" :key="exam.id" class="border-l-4 border-purple-500 pl-3">
								<div class="font-medium text-sm capitalize">{{ exam.examination_category }} - {{ exam.examination_type.replace('_', ' ') }}</div>
								<div class="text-xs text-gray-500 mb-2">{{ new Date(exam.performed_at).toLocaleString() }}</div>
								<div class="text-sm text-gray-700 dark:text-gray-300">
									<div v-for="finding in exam.findings" :key="finding" class="mb-1">• {{ finding }}</div>
								</div>
							</div>
						</CardContent>
					</Card>
				</div>

				<!-- Chat Interface -->
				<div class="lg:col-span-2 flex flex-col h-full">
					<Card class="flex-1 flex flex-col">
						<CardHeader>
							<CardTitle class="flex items-center gap-2">
								<Bot class="h-5 w-5 text-blue-600" />
								AI Patient Chat
							</CardTitle>
							<CardDescription>Ask questions and interact with the AI patient to practice your clinical skills</CardDescription>
						</CardHeader>

						<CardContent class="flex-1 flex flex-col p-0">
							<!-- Chat Messages -->
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

							<!-- Chat Input -->
							<div class="border-t p-4">
								<div class="flex gap-2">
									<Textarea v-model="message" placeholder="Type your question or message to the AI patient..." class="flex-1 resize-none" :rows="2" @keydown="handleKeyPress" :disabled="isLoading" />
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

