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
import { ArrowLeft, Send, User, Bot, Clock, AlertCircle, CheckCircle, FlaskConical, Stethoscope, FileText } from 'lucide-vue-next';
import SessionTimer from '@/components/SessionTimer.vue';
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
		available_labs: string[];
		available_procedures: string[];
		available_examinations: any;
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

// New refs for examination system
const selectedLab = ref('');
const selectedProcedure = ref('');
const selectedExaminations = ref<Array<{category: string, type: string}>>([]);
const showLabModal = ref(false);
const showProcedureModal = ref(false);
const showExamModal = ref(false);

const session = ref<OsceSession>(props.session);
const osceCase = computed(() => session.value.osce_case);

// Get page errors for Inertia error handling
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
			// Re-add the user message back to input
			message.value = userMessage;
		}
	} catch (error) {
		console.error('Error sending message:', error);
		toast.error('Network error', {
			description: 'Please check your connection and try again'
		});
		// Re-add the user message back to input
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

// Available examination options
const examinationOptions = [
	{ category: 'cardiovascular', type: 'auscultation', label: 'Cardiovascular Auscultation' },
	{ category: 'cardiovascular', type: 'palpation', label: 'Cardiovascular Palpation' },
	{ category: 'cardiovascular', type: 'inspection', label: 'Cardiovascular Inspection' },
	{ category: 'respiratory', type: 'auscultation', label: 'Respiratory Auscultation' },
	{ category: 'respiratory', type: 'percussion', label: 'Respiratory Percussion' },
	{ category: 'respiratory', type: 'palpation', label: 'Respiratory Palpation' },
	{ category: 'respiratory', type: 'inspection', label: 'Respiratory Inspection' },
	{ category: 'abdominal', type: 'inspection', label: 'Abdominal Inspection' },
	{ category: 'abdominal', type: 'auscultation', label: 'Abdominal Auscultation' },
	{ category: 'abdominal', type: 'palpation', label: 'Abdominal Palpation' },
	{ category: 'abdominal', type: 'percussion', label: 'Abdominal Percussion' },
	{ category: 'neurological', type: 'reflexes', label: 'Neurological Reflexes' },
	{ category: 'neurological', type: 'sensation', label: 'Neurological Sensation' },
	{ category: 'neurological', type: 'motor', label: 'Neurological Motor' },
	{ category: 'neurological', type: 'cranial_nerves', label: 'Cranial Nerves' },
	{ category: 'musculoskeletal', type: 'range_of_motion', label: 'Range of Motion' },
	{ category: 'musculoskeletal', type: 'strength', label: 'Muscle Strength' },
	{ category: 'musculoskeletal', type: 'inspection', label: 'Musculoskeletal Inspection' }
];

// Order lab test using Inertia
const orderLab = () => {
	if (!selectedLab.value) return;

	router.post('/osce/order-lab', {
		session_id: session.value.id,
		test_name: selectedLab.value
	}, {
		preserveState: true,
		preserveScroll: true,
		onSuccess: (page) => {
			toast.success('Lab Test Ordered', {
				description: `Lab test '${selectedLab.value}' has been ordered.`
			});
			showLabModal.value = false;
			selectedLab.value = '';
		},
		onError: (errors) => {
			toast.error('Failed to order lab test', {
				description: errors.error || 'An unexpected error occurred'
			});
		}
	});
};

// Order procedure using Inertia
const orderProcedure = () => {
	if (!selectedProcedure.value) return;

	router.post('/osce/order-procedure', {
		session_id: session.value.id,
		procedure_name: selectedProcedure.value
	}, {
		preserveState: true,
		preserveScroll: true,
		onSuccess: (page) => {
			toast.success('Procedure Ordered', {
				description: `Procedure '${selectedProcedure.value}' has been ordered.`
			});
			showProcedureModal.value = false;
			selectedProcedure.value = '';
		},
		onError: (errors) => {
			toast.error('Failed to order procedure', {
				description: errors.error || 'An unexpected error occurred'
			});
		}
	});
};

// Perform physical examination using Inertia
const performExamination = () => {
	if (selectedExaminations.value.length === 0) return;

	router.post('/osce/perform-examination', {
		session_id: session.value.id,
		examinations: selectedExaminations.value
	}, {
		preserveState: true,
		preserveScroll: true,
		onSuccess: (page) => {
			toast.success('Examination Completed', {
				description: `${selectedExaminations.value.length} examination(s) completed.`
			});
			showExamModal.value = false;
			selectedExaminations.value = [];
		},
		onError: (errors) => {
			toast.error('Failed to perform examination', {
				description: errors.error || 'An unexpected error occurred'
			});
		}
	});
};

// Handle examination selection
const toggleExamination = (exam: {category: string, type: string}) => {
	const index = selectedExaminations.value.findIndex(
		e => e.category === exam.category && e.type === exam.type
	);
	
	if (index > -1) {
		selectedExaminations.value.splice(index, 1);
	} else {
		selectedExaminations.value.push(exam);
	}
};

const isExaminationSelected = (exam: {category: string, type: string}) => {
	return selectedExaminations.value.some(
		e => e.category === exam.category && e.type === exam.type
	);
};

// Watch for Inertia errors and display toast
watch(errors, (newErrors) => {
	if (newErrors && newErrors.error) {
		toast.error('Error', {
			description: newErrors.error
		});
	}
}, { deep: true });

onMounted(async () => {
	await loadChatHistory();
	if (messages.value.length === 0) {
		await startChat();
	}
});

function handleSessionExpired() {
	toast.error('Session expired', { description: 'Your OSCE session time has ended.' });
}

function handleSessionCompleted() {
	toast.success('Session completed', { description: 'Session has been marked as completed.' });
}
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
						<h1 class="text-2xl font-bold text-gray-900 dark:text-white">
							OSCE Chat Session
						</h1>
						<p class="text-sm text-gray-600 dark:text-gray-400">
							Chat with AI Patient for Case: {{ osceCase?.title }}
						</p>
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
				<!-- Enhanced Left Sidebar -->
				<div class="lg:col-span-1 space-y-4 max-h-screen overflow-y-auto">
					<!-- Action Buttons -->
					<Card>
						<CardHeader>
							<CardTitle class="text-lg">Medical Actions</CardTitle>
						</CardHeader>
						<CardContent class="space-y-3">
							<Dialog v-model:open="showLabModal">
								<DialogTrigger asChild>
									<Button variant="outline" class="w-full flex items-center gap-2">
										<FlaskConical class="h-4 w-4" />
										Order Labs
									</Button>
								</DialogTrigger>
								<DialogContent>
									<DialogHeader>
										<DialogTitle>Order Laboratory Tests</DialogTitle>
										<DialogDescription>
											Select a laboratory test to order for this patient
										</DialogDescription>
									</DialogHeader>
									<div class="space-y-4">
										<Select v-model="selectedLab">
											<SelectTrigger>
												<SelectValue placeholder="Select a lab test..." />
											</SelectTrigger>
											<SelectContent>
												<SelectItem 
													v-for="lab in props.sessionData?.available_labs || []" 
													:key="lab" 
													:value="lab"
												>
													{{ lab }}
												</SelectItem>
											</SelectContent>
										</Select>
										<div class="flex gap-2">
											<Button @click="orderLab" :disabled="!selectedLab" class="flex-1">
												Order Test
											</Button>
											<Button variant="outline" @click="showLabModal = false" class="flex-1">
												Cancel
											</Button>
										</div>
									</div>
								</DialogContent>
							</Dialog>

							<Dialog v-model:open="showProcedureModal">
								<DialogTrigger asChild>
									<Button variant="outline" class="w-full flex items-center gap-2">
										<FileText class="h-4 w-4" />
										Order Procedure
									</Button>
								</DialogTrigger>
								<DialogContent>
									<DialogHeader>
										<DialogTitle>Order Medical Procedure</DialogTitle>
										<DialogDescription>
											Select a medical procedure to order for this patient
										</DialogDescription>
									</DialogHeader>
									<div class="space-y-4">
										<Select v-model="selectedProcedure">
											<SelectTrigger>
												<SelectValue placeholder="Select a procedure..." />
											</SelectTrigger>
											<SelectContent>
												<SelectItem 
													v-for="procedure in props.sessionData?.available_procedures || []" 
													:key="procedure" 
													:value="procedure"
												>
													{{ procedure }}
												</SelectItem>
											</SelectContent>
										</Select>
										<div class="flex gap-2">
											<Button @click="orderProcedure" :disabled="!selectedProcedure" class="flex-1">
												Order Procedure
											</Button>
											<Button variant="outline" @click="showProcedureModal = false" class="flex-1">
												Cancel
											</Button>
										</div>
									</div>
								</DialogContent>
							</Dialog>

							<Dialog v-model:open="showExamModal">
								<DialogTrigger asChild>
									<Button variant="outline" class="w-full flex items-center gap-2">
										<Stethoscope class="h-4 w-4" />
										Physical Exam
									</Button>
								</DialogTrigger>
								<DialogContent class="max-w-2xl max-h-96 overflow-y-auto">
									<DialogHeader>
										<DialogTitle>Physical Examination</DialogTitle>
										<DialogDescription>
											Select multiple examinations to perform on the patient
										</DialogDescription>
									</DialogHeader>
									<div class="space-y-4">
										<div class="grid grid-cols-1 gap-2 max-h-60 overflow-y-auto">
											<div 
												v-for="exam in examinationOptions" 
												:key="`${exam.category}-${exam.type}`"
												class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
												:class="isExaminationSelected(exam) ? 'bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700' : ''"
												@click="toggleExamination(exam)"
											>
												<input 
													type="checkbox" 
													:checked="isExaminationSelected(exam)"
													class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
													readonly
												/>
												<label class="text-sm cursor-pointer">{{ exam.label }}</label>
											</div>
										</div>
										<div class="flex gap-2">
											<Button @click="performExamination" :disabled="selectedExaminations.length === 0" class="flex-1">
												Perform Examination(s)
											</Button>
											<Button variant="outline" @click="showExamModal = false" class="flex-1">
												Cancel
											</Button>
										</div>
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
								<p class="text-sm text-gray-600 dark:text-gray-400">
									{{ osceCase?.scenario || 'No scenario provided' }}
								</p>
							</div>
							
							<div>
								<h4 class="font-medium text-gray-900 dark:text-white mb-2">Objectives</h4>
								<p class="text-sm text-gray-600 dark:text-gray-400">
									{{ osceCase?.objectives || 'No objectives provided' }}
								</p>
							</div>

							<div v-if="osceCase?.ai_patient_profile">
								<h4 class="font-medium text-gray-900 dark:text-white mb-2">Patient Profile</h4>
								<p class="text-sm text-gray-600 dark:text-gray-400">
									{{ osceCase.ai_patient_profile }}
								</p>
							</div>

							<div v-if="osceCase?.ai_patient_symptoms">
								<h4 class="font-medium text-gray-900 dark:text-white mb-2">Patient Symptoms</h4>
								<div class="flex flex-wrap gap-1">
									<Badge v-for="symptom in osceCase.ai_patient_symptoms" :key="symptom" variant="outline" class="text-xs">
										{{ symptom }}
									</Badge>
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
							<CardTitle class="text-lg flex items-center gap-2">
								<FlaskConical class="h-5 w-5 text-blue-600" />
								Lab Results
							</CardTitle>
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
							<CardTitle class="text-lg flex items-center gap-2">
								<FileText class="h-5 w-5 text-green-600" />
								Procedure Results
							</CardTitle>
						</CardHeader>
						<CardContent class="space-y-3">
							<div v-for="procedure in props.sessionData?.procedure_results" :key="procedure.id" class="border-l-4 border-green-500 pl-3">
								<div class="font-medium text-sm">{{ procedure.test_name }}</div>
								<div class="text-xs text-gray-500 mb-2">{{ new Date(procedure.ordered_at).toLocaleString() }}</div>
								<div class="text-sm text-gray-700 dark:text-gray-300">
									{{ procedure.results.description || JSON.stringify(procedure.results) }}
								</div>
							</div>
						</CardContent>
					</Card>

					<!-- Physical Examination Findings -->
					<Card v-if="props.sessionData?.examination_findings && props.sessionData.examination_findings.length > 0">
						<CardHeader>
							<CardTitle class="text-lg flex items-center gap-2">
								<Stethoscope class="h-5 w-5 text-purple-600" />
								Examination Findings
							</CardTitle>
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
							<CardDescription>
								Ask questions and interact with the AI patient to practice your clinical skills
							</CardDescription>
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
											<div class="bg-blue-600 text-white rounded-lg px-3 py-2 text-sm">
												{{ msg.message }}
											</div>
											<User class="h-6 w-6 text-blue-600" />
										</div>
									</div>
									
									<div v-else-if="msg.sender_type === 'ai_patient'" class="flex-1 flex justify-start">
										<div class="flex items-end gap-2 max-w-xs lg:max-w-md">
											<Bot class="h-6 w-6 text-green-600" />
											<div class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg px-3 py-2 text-sm">
												{{ msg.message }}
											</div>
										</div>
									</div>
									
									<div v-else-if="msg.sender_type === 'system'" class="flex-1 flex justify-center">
										<div class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-lg px-3 py-2 text-xs text-center max-w-md">
											{{ msg.message }}
										</div>
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
										<Textarea
											v-model="message"
											placeholder="Type your question or message to the AI patient..."
											class="flex-1 resize-none"
											:rows="2"
											@keydown="handleKeyPress"
											:disabled="isLoading"
										/>
										<Button 
											@click="sendMessage" 
											:disabled="isLoading || !message.trim()"
											class="px-4"
										>
											<Send class="h-4 w-4" />
										</Button>
									</div>
									<p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
										Press Enter to send, Shift+Enter for new line
									</p>
								</div>
						</CardContent>
					</Card>
				</div>
			</div>
		</div>
	</AppLayout>
</template>