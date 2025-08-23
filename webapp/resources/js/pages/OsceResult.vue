<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import {
    CheckCircle,
    XCircle,
    AlertTriangle,
    FileText,
    RefreshCw,
    Eye,
    User,
    Calendar,
    Award,
    Zap
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Session {
    id: number;
    status: string;
    completed_at?: string;
    duration_minutes: number;
    time_extended?: number;
    clinical_reasoning_score?: number;
    total_test_cost?: number;
    evaluation_feedback?: string[];
    case: {
        id: number;
        title: string;
        chief_complaint: string;
        description?: string;
        scenario?: string;
        difficulty?: string;
        duration_minutes?: number;
        budget?: number;
        learning_objectives?: string[];
        key_history_points?: string[];
        critical_examinations?: string[];
        required_tests?: string[];
        highly_appropriate_tests?: string[];
        contraindicated_tests?: string[];
        expected_diagnosis?: string;
        management_plan?: string;
        teaching_points?: string[];
    };
    osce_case?: {
        id: number;
        title: string;
        chief_complaint: string;
        description?: string;
        scenario?: string;
        difficulty?: string;
        duration_minutes?: number;
        budget?: number;
        learning_objectives?: string[];
        key_history_points?: string[];
        critical_examinations?: string[];
        required_tests?: string[];
        highly_appropriate_tests?: string[];
        contraindicated_tests?: string[];
        expected_diagnosis?: string;
        management_plan?: string;
        teaching_points?: string[];
    };
    user: {
        id: number;
        name: string;
    };
}

interface AssessmentCriterion {
    key: string;
    score: number;
    max: number;
    justification: string;
    citations: string[];
}

interface ClinicalArea {
    area: string;
    key: string;
    score: number;
    max_score: number;
    justification: string;
    citations: string[];
    strengths: string[];
    areas_for_improvement: string[];
}

interface Assessment {
    score: number;
    max_score: number;
    percentage: number;
    assessed_at: string;
    assessor_model?: string;
    assessment_type?: string;
    assessor_output?: {
        error?: string;
        message?: string;
        status?: string;
    };
    output: {
        // Legacy rubric format (still supported)
        rubric_version?: string;
        criteria?: AssessmentCriterion[];
        overall_comment?: string;
        red_flags?: string[];
        detailed_assessment?: string;

        // New session-based assessment format
        total_score?: number;
        max_possible_score?: number;
        assessment_type?: string;
        strengths?: string[];
        areas_for_improvement?: string[];
        clinical_reasoning_analysis?: string;
        safety_concerns?: string[];
        overall_feedback?: string;
        score_justification?: string;
        recommendations?: string[];

        // Detailed clinical areas format
        clinical_areas?: ClinicalArea[];

        model_info: {
            name: string;
            temperature: number;
            status?: string;
            assessment_approach?: string;
        };
    };
}

interface Props {
    session: Session;
    assessment?: Assessment;
    isAssessed: boolean;
    canReassess: boolean;
    isAdmin?: boolean;
    error?: string;
}

const props = defineProps<Props>();

const isReassessing = ref(false);
const selectedCitation = ref<string | null>(null);
const showCitationModal = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'OSCE', href: '/osce' },
    { title: 'Assessment Results', href: '' },
];

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getScoreColor = (score: number, max: number) => {
    const percentage = (score / max) * 100;
    if (percentage >= 80) return 'text-green-600';
    if (percentage >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const getScoreBadgeVariant = (score: number, max: number) => {
    const percentage = (score / max) * 100;
    if (percentage >= 80) return 'default';
    if (percentage >= 60) return 'secondary';
    return 'destructive';
};

const performanceLevel = computed(() => {
    if (!props.assessment) return '';
    const percentage = props.assessment.percentage;
    if (percentage >= 90) return 'Excellent';
    if (percentage >= 80) return 'Good';
    if (percentage >= 70) return 'Satisfactory';
    if (percentage >= 60) return 'Needs Improvement';
    return 'Unsatisfactory';
});

const performanceLevelColor = computed(() => {
    if (!props.assessment) return '';
    const percentage = props.assessment.percentage;
    if (percentage >= 80) return 'text-green-600';
    if (percentage >= 60) return 'text-yellow-600';
    return 'text-red-600';
});

// Check if this is the detailed clinical areas format
const isDetailedAreasAssessment = computed(() => {
    return props.assessment?.output?.assessment_type === 'detailed_clinical_areas_assessment' ||
        (props.assessment?.output?.clinical_areas && Array.isArray(props.assessment.output.clinical_areas));
});

// Check if this is the holistic session-based assessment format
const isSessionAssessment = computed(() => {
    return props.assessment?.output?.assessment_type === 'holistic_clinical_assessment' ||
        props.assessment?.assessment_type === 'session_assessment';
});

// Check if this is the legacy rubric format
const isRubricAssessment = computed(() => {
    return props.assessment?.output?.criteria && Array.isArray(props.assessment.output.criteria);
});

// Extract the AI's "Clinical Reasoning" area when using detailed areas
const clinicalReasoningArea = computed(() => {
    if (!isDetailedAreasAssessment.value) return null;
    const areas = props.assessment?.output?.clinical_areas || [];
    return (
        areas.find((a: any) => a.key === 'clinical_reasoning') ||
        areas.find((a: any) => a.key === 'diagnosis' || /reason/i.test(a.area))
    );
});

const handleReassess = async () => {
    isReassessing.value = true;
    try {
        const response = await fetch(`/api/osce/sessions/${props.session.id}/assess`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ force: true })
        });

        if (response.ok) {
            const result = await response.json();

            // Check if assessment was completed immediately (development) or queued (production)
            if (result.message.includes('completed')) {
                // Assessment completed immediately - reload the page
                router.reload();
            } else if (result.message.includes('queued')) {
                // Assessment queued - show message and optionally redirect back to dashboard
                alert('Assessment has been queued for processing. Please check back in a few moments.');
                router.visit('/osce');
            } else {
                // Assessment already exists
                router.reload();
            }
        } else {
            const error = await response.json();
            console.error('Assessment failed:', error);
            alert('Failed to start assessment: ' + (error.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Failed to reassess session:', error);
        alert('Network error occurred. Please try again.');
    } finally {
        isReassessing.value = false;
    }
};

const showCitation = (citation: string) => {
    selectedCitation.value = citation;
    showCitationModal.value = true;
};

const formatCitation = (citation: string) => {
    if (citation.startsWith('msg#')) {
        return `Chat Message #${citation.substring(4)}`;
    }
    if (citation.startsWith('lab:')) {
        return `Lab Test: ${citation.substring(4)}`;
    }
    if (citation.startsWith('exam:')) {
        return `Examination: ${citation.substring(5)}`;
    }
    return citation;
};

const getDifficultyColor = (difficulty?: string) => {
    switch (difficulty?.toLowerCase()) {
        case 'beginner':
        case 'easy':
            return 'bg-green-100 text-green-800';
        case 'intermediate':
        case 'medium':
            return 'bg-yellow-100 text-yellow-800';
        case 'advanced':
        case 'hard':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const criteriaLabels: Record<string, string> = {
    history: 'History-taking',
    exam: 'Physical Exam',
    investigations: 'Investigations',
    diagnosis: 'Diagnosis & Reasoning',
    management: 'Management Plan',
    communication: 'Communication/Professionalism',
    safety: 'Time Use/Safety'
};
</script>

<template>

    <Head title="OSCE Assessment Results" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <!-- OSCE Case Scenario - Top Section -->
            <div class="space-y-6">
                <!-- Case Information Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <FileText class="h-5 w-5" />
                            <span>OSCE Case Scenario</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Basic Case Info -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Difficulty:</span>
                                <span
                                    :class="`ml-2 px-2 py-1 rounded-full text-xs ${getDifficultyColor(session.case.difficulty)}`">
                                    {{ session.case.difficulty || 'Not specified' }}
                                </span>
                            </div>
                            <div>
                                <span class="font-medium">Duration:</span>
                                <span class="ml-2">{{ session.case.duration_minutes || 30 }} minutes</span>
                            </div>
                            <div>
                                <span class="font-medium">Budget:</span>
                                <span class="ml-2">${{ session.case.budget || 1000 }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Case ID:</span>
                                <span class="ml-2">#{{ session.case.id }}</span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="session.case.description">
                            <h4 class="font-semibold mb-2">Description</h4>
                            <p class="text-sm text-muted-foreground">{{ session.case.description }}</p>
                        </div>

                        <!-- Scenario -->
                        <div v-if="session.case.scenario">
                            <h4 class="font-semibold mb-2">Clinical Scenario</h4>
                            <div class="bg-muted p-3 rounded-md text-sm">{{ session.case.scenario }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Learning Objectives and Requirements Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Learning Objectives -->
                    <Card v-if="session.case.learning_objectives && session.case.learning_objectives.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2 text-base">
                                <Award class="h-4 w-4" />
                                <span>Learning Objectives</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul class="space-y-2">
                                <li v-for="objective in session.case.learning_objectives" :key="objective"
                                    class="flex items-start space-x-2">
                                    <CheckCircle class="h-3 w-3 text-blue-500 mt-0.5 flex-shrink-0" />
                                    <span class="text-sm">{{ objective }}</span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    <!-- History Taking Requirements -->
                    <Card v-if="session.case.key_history_points && session.case.key_history_points.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2 text-base">
                                <User class="h-4 w-4" />
                                <span>Key History Points</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul class="space-y-2">
                                <li v-for="point in session.case.key_history_points" :key="point"
                                    class="flex items-start space-x-2">
                                    <CheckCircle class="h-3 w-3 text-green-500 mt-0.5 flex-shrink-0" />
                                    <span class="text-sm">{{ point }}</span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    <!-- Physical Examinations -->
                    <Card v-if="session.case.critical_examinations && session.case.critical_examinations.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2 text-base">
                                <Eye class="h-4 w-4" />
                                <span>Critical Examinations</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul class="space-y-2">
                                <li v-for="exam in session.case.critical_examinations" :key="exam"
                                    class="flex items-start space-x-2">
                                    <Eye class="h-3 w-3 text-purple-500 mt-0.5 flex-shrink-0" />
                                    <span class="text-sm">{{ exam }}</span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>

                <!-- Investigation Requirements -->
                <Card v-if="(session.case.required_tests && session.case.required_tests.length > 0) ||
                    (session.case.highly_appropriate_tests && session.case.highly_appropriate_tests.length > 0) ||
                    (session.case.contraindicated_tests && session.case.contraindicated_tests.length > 0)">
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <FileText class="h-5 w-5" />
                            <span>Investigation Guidelines</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Required Tests -->
                            <div v-if="session.case.required_tests && session.case.required_tests.length > 0">
                                <h5 class="font-medium text-green-700 mb-2 flex items-center space-x-2">
                                    <CheckCircle class="h-4 w-4" />
                                    <span>Required Tests</span>
                                </h5>
                                <ul class="space-y-1">
                                    <li v-for="test in session.case.required_tests" :key="test"
                                        class="flex items-center space-x-2 text-sm">
                                        <CheckCircle class="h-3 w-3 text-green-500" />
                                        <span>{{ test }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Appropriate Tests -->
                            <div
                                v-if="session.case.highly_appropriate_tests && session.case.highly_appropriate_tests.length > 0">
                                <h5 class="font-medium text-blue-700 mb-2 flex items-center space-x-2">
                                    <CheckCircle class="h-4 w-4" />
                                    <span>Highly Appropriate Tests</span>
                                </h5>
                                <ul class="space-y-1">
                                    <li v-for="test in session.case.highly_appropriate_tests" :key="test"
                                        class="flex items-center space-x-2 text-sm">
                                        <CheckCircle class="h-3 w-3 text-blue-500" />
                                        <span>{{ test }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Contraindicated Tests -->
                            <div
                                v-if="session.case.contraindicated_tests && session.case.contraindicated_tests.length > 0">
                                <h5 class="font-medium text-red-700 mb-2 flex items-center space-x-2">
                                    <XCircle class="h-4 w-4" />
                                    <span>Contraindicated Tests</span>
                                </h5>
                                <ul class="space-y-1">
                                    <li v-for="test in session.case.contraindicated_tests" :key="test"
                                        class="flex items-center space-x-2 text-sm">
                                        <XCircle class="h-3 w-3 text-red-500" />
                                        <span>{{ test }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Expected Diagnosis & Management -->
                <div v-if="session.case.expected_diagnosis || session.case.management_plan || (session.case.teaching_points && session.case.teaching_points.length > 0)"
                    class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Expected Outcomes -->
                    <Card v-if="session.case.expected_diagnosis || session.case.management_plan">
                        <CardHeader>
                            <CardTitle class="text-lg">Expected Clinical Outcomes</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Expected Diagnosis -->
                            <div v-if="session.case.expected_diagnosis">
                                <h5 class="font-medium text-indigo-700 mb-2">Expected Diagnosis</h5>
                                <div class="bg-indigo-50 border border-indigo-200 p-3 rounded-md text-sm">
                                    {{ session.case.expected_diagnosis }}
                                </div>
                            </div>

                            <!-- Management Plan -->
                            <div v-if="session.case.management_plan">
                                <h5 class="font-medium text-orange-700 mb-2">Expected Management Plan</h5>
                                <div class="bg-orange-50 border border-orange-200 p-3 rounded-md text-sm">
                                    {{ session.case.management_plan }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Teaching Points -->
                    <Card v-if="session.case.teaching_points && session.case.teaching_points.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <Zap class="h-5 w-5" />
                                <span>Teaching Points</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul class="space-y-2">
                                <li v-for="point in session.case.teaching_points" :key="point"
                                    class="flex items-start space-x-2">
                                    <Zap class="h-4 w-4 text-yellow-500 mt-0.5 flex-shrink-0" />
                                    <span class="text-sm">{{ point }}</span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Assessment Results - Bottom Section -->
            <div class="space-y-6">
                <!-- Header Card -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-2xl">Assessment Results</CardTitle>
                                <p class="text-muted-foreground mt-1">
                                    {{ session.case?.title || session.osce_case?.title || 'Unknown Case' }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Badge variant="outline" class="flex items-center space-x-1">
                                    <User class="h-3 w-3" />
                                    <span>{{ session.user?.name || 'Unknown User' }}</span>
                                </Badge>
                                <Badge variant="outline" class="flex items-center space-x-1">
                                    <Calendar class="h-3 w-3" />
                                    <span>{{ session.completed_at ? formatDateTime(session.completed_at) : "Not completed" }}></span>
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <!-- Error State -->
                <Card v-if="!isAssessed && error">
                    <CardContent class="pt-6">
                        <div class="flex items-center space-x-3 text-amber-600">
                            <AlertTriangle class="h-5 w-5" />
                            <span>{{ error }}</span>
                        </div>
                        <div class="mt-4">
                            <Button @click="handleReassess" :disabled="isReassessing"
                                class="flex items-center space-x-2">
                                <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isReassessing }" />
                                <span>{{ isReassessing ? 'Assessing...' : 'Start Assessment' }}</span>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- AI Unavailable State -->
                <Card v-if="isAssessed && assessment && assessment.assessor_output?.error">
                    <CardContent class="pt-6">
                        <div class="text-center space-y-4">
                            <div class="flex items-center justify-center space-x-3 text-red-600">
                                <XCircle class="h-8 w-8" />
                                <h3 class="text-xl font-semibold">{{ assessment.assessor_output.error }}</h3>
                            </div>
                            <p class="text-muted-foreground max-w-md mx-auto">
                                {{ assessment.assessor_output.message }}
                            </p>
                            <div class="text-sm text-muted-foreground">
                                Status: {{ assessment.assessor_output.status }}
                            </div>
                            <div class="mt-6">
                                <Button @click="handleReassess" :disabled="isReassessing"
                                    class="flex items-center space-x-2">
                                    <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isReassessing }" />
                                    <span>{{ isReassessing ? 'Retrying...' : 'Retry Assessment' }}</span>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Assessment Results -->
                <div v-if="isAssessed && assessment" class="space-y-6">
                    <!-- Overall Score Card -->
                    <Card>
<CardHeader>
    <CardTitle class="flex items-center space-x-2">
        <Award class="h-5 w-5" />
        <span>Overall Performance</span>
    </CardTitle>
    <div class="ml-auto">
        <Button variant="outline" size="sm" @click="() => document.getElementById('clinical-reasoning')?.scrollIntoView({ behavior: 'smooth' })">
            Go to Clinical Reasoning
        </Button>
    </div>
</CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="text-center">
                                    <div class="text-3xl font-bold"
                                        :class="getScoreColor(assessment.score, assessment.max_score)">
                                        {{ assessment.score }}/{{ assessment.max_score }}
                                    </div>
                                    <p class="text-sm text-muted-foreground">Total Score</p>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold"
                                        :class="getScoreColor(assessment.score, assessment.max_score)">
                                        {{ assessment.percentage }}%
                                    </div>
                                    <p class="text-sm text-muted-foreground">Percentage</p>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-semibold" :class="performanceLevelColor">
                                        {{ performanceLevel }}
                                    </div>
                                    <p class="text-sm text-muted-foreground">Performance Level</p>
                                </div>
                                <div class="text-center">
                                    <Badge :variant="getScoreBadgeVariant(assessment.score, assessment.max_score)"
                                        class="text-sm px-3 py-1">
                                        {{ isDetailedAreasAssessment ? 'AI Clinical Areas Assessment' :
                                            isSessionAssessment ? 'AI Session Assessment' :
                                                (assessment.output.rubric_version || 'Assessment') }}
                                    </Badge>
                                    <p class="text-sm text-muted-foreground mt-1">{{ (isDetailedAreasAssessment ||
                                        isSessionAssessment) ? 'Assessment Type' : 'Rubric Version' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                <!-- Detailed Clinical Areas Assessment -->
                <Card v-if="isDetailedAreasAssessment">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <FileText class="h-5 w-5" />
                                <span>Detailed Clinical Areas Assessment</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-6">
                                <div v-for="area in assessment.output.clinical_areas" :key="area.key"
                                    class="border rounded-lg p-4">
                                    <!-- Area Header -->
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="font-semibold text-lg">{{ area.area }}</h4>
                                        <Badge :variant="getScoreBadgeVariant(area.score, area.max_score)"
                                            class="text-sm">
                                            {{ area.score }}/{{ area.max_score }}
                                        </Badge>
                                    </div>

                                    <!-- Justification -->
                                    <div class="mb-4">
                                        <h5 class="font-medium mb-2">Assessment Commentary</h5>
                                        <div
                                            class="bg-muted p-3 rounded-md text-sm whitespace-pre-line font-mono leading-relaxed">
                                            {{ area.justification }}
                                        </div>
                                    </div>

                                    <!-- Citations -->
                                    <div v-if="area.citations && area.citations.length > 0" class="mb-4">
                                        <h5 class="font-medium mb-2">Evidence Citations</h5>
                                        <div class="flex flex-wrap gap-2">
                                            <Button v-for="citation in area.citations" :key="citation" variant="outline"
                                                size="sm" @click="showCitation(citation)" class="text-xs px-2 py-1">
                                                <Eye class="h-3 w-3 mr-1" />
                                                {{ formatCitation(citation) }}
                                            </Button>
                                        </div>
                                    </div>

                                    <!-- Strengths and Improvements -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div v-if="area.strengths && area.strengths.length > 0">
                                            <h5 class="font-medium mb-2 text-green-600 flex items-center space-x-2">
                                                <CheckCircle class="h-4 w-4" />
                                                <span>Strengths in {{ area.area }}</span>
                                            </h5>
                                            <ul class="space-y-1">
                                                <li v-for="strength in area.strengths" :key="strength"
                                                    class="bg-green-50 border border-green-200 p-2 rounded-md text-sm text-green-800">
                                                    {{ strength }}
                                                </li>
                                            </ul>
                                        </div>

                                        <div v-if="area.areas_for_improvement && area.areas_for_improvement.length > 0">
                                            <h5 class="font-medium mb-2 text-amber-600 flex items-center space-x-2">
                                                <AlertTriangle class="h-4 w-4" />
                                                <span>Areas for Improvement</span>
                                            </h5>
                                            <ul class="space-y-1">
                                                <li v-for="improvement in area.areas_for_improvement" :key="improvement"
                                                    class="bg-amber-50 border border-amber-200 p-2 rounded-md text-sm text-amber-800">
                                                    {{ improvement }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                </Card>

                <!-- Clinical Reasoning (Dedicated Section) -->
                <Card v-if="isDetailedAreasAssessment && clinicalReasoningArea" id="clinical-reasoning">
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-2">
                            <Zap class="h-5 w-5" />
                            <span>Clinical Reasoning</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-muted-foreground">AI Clinical Reasoning Score</div>
                            <Badge :variant="getScoreBadgeVariant(clinicalReasoningArea.score, clinicalReasoningArea.max_score)">
                                {{ clinicalReasoningArea.score }}/{{ clinicalReasoningArea.max_score }}
                            </Badge>
                        </div>

                        <div>
                            <h4 class="font-semibold mb-2">AI Commentary</h4>
                            <div class="bg-muted p-3 rounded-md text-sm whitespace-pre-line">
                                {{ clinicalReasoningArea.justification }}
                            </div>
                        </div>

                        <div v-if="session?.clinical_reasoning_score || (session?.evaluation_feedback && session.evaluation_feedback.length)">
                            <h4 class="font-semibold mb-2">Rationalization Contributions</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ session?.clinical_reasoning_score ?? 0 }}</div>
                                    <div class="text-xs text-muted-foreground">Reasoning Score (orders)</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ session?.total_test_cost ?? 0 }}</div>
                                    <div class="text-xs text-muted-foreground">Total Test Cost</div>
                                </div>
                            </div>
                            <div v-if="session?.evaluation_feedback && session.evaluation_feedback.length > 0" class="mt-3">
                                <ul class="list-disc pl-5 text-sm space-y-1">
                                    <li v-for="(f, idx) in session.evaluation_feedback" :key="idx">{{ f }}</li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                    <!-- Session-based Assessment -->
                    <Card v-else-if="isSessionAssessment">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <FileText class="h-5 w-5" />
                                <span>Clinical Assessment Summary</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Score Justification -->
                            <div>
                                <h4 class="font-semibold mb-2">Score Justification</h4>
                                <div class="bg-muted p-3 rounded-md text-sm whitespace-pre-line">
                                    {{ assessment.output.score_justification }}
                                </div>
                            </div>

                            <!-- Clinical Reasoning Analysis -->
                            <div v-if="assessment.output.clinical_reasoning_analysis">
                                <h4 class="font-semibold mb-2">Clinical Reasoning Analysis</h4>
                                <div class="bg-muted p-3 rounded-md text-sm whitespace-pre-line">
                                    {{ assessment.output.clinical_reasoning_analysis }}
                                </div>
                            </div>

                            <!-- Strengths and Improvements -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-if="assessment.output.strengths && assessment.output.strengths.length > 0">
                                    <h4 class="font-semibold mb-2 text-green-600 flex items-center space-x-2">
                                        <CheckCircle class="h-4 w-4" />
                                        <span>Strengths</span>
                                    </h4>
                                    <ul class="space-y-2">
                                        <li v-for="strength in assessment.output.strengths" :key="strength"
                                            class="bg-green-50 border border-green-200 p-3 rounded-md">
                                            <div class="text-sm text-green-800">{{ strength }}</div>
                                        </li>
                                    </ul>
                                </div>

                                <div
                                    v-if="assessment.output.areas_for_improvement && assessment.output.areas_for_improvement.length > 0">
                                    <h4 class="font-semibold mb-2 text-amber-600 flex items-center space-x-2">
                                        <AlertTriangle class="h-4 w-4" />
                                        <span>Areas for Improvement</span>
                                    </h4>
                                    <ul class="space-y-2">
                                        <li v-for="area in assessment.output.areas_for_improvement" :key="area"
                                            class="bg-amber-50 border border-amber-200 p-3 rounded-md">
                                            <div class="text-sm text-amber-800">{{ area }}</div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Learning Recommendations -->
                            <div
                                v-if="assessment.output.recommendations && assessment.output.recommendations.length > 0">
                                <h4 class="font-semibold mb-2 text-blue-600">Learning Recommendations</h4>
                                <ul class="space-y-2">
                                    <li v-for="recommendation in assessment.output.recommendations"
                                        :key="recommendation" class="bg-blue-50 border border-blue-200 p-3 rounded-md">
                                        <div class="text-sm text-blue-800">{{ recommendation }}</div>
                                    </li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Legacy Rubric Breakdown (for backward compatibility) -->
                    <Card v-else-if="isRubricAssessment">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <FileText class="h-5 w-5" />
                                <span>Detailed Assessment</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Criterion</TableHead>
                                        <TableHead>Score</TableHead>
                                        <TableHead>Justification</TableHead>
                                        <TableHead>Citations</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="criterion in assessment.output.criteria" :key="criterion.key">
                                        <TableCell class="font-medium">
                                            {{ criteriaLabels[criterion.key] || criterion.key }}
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="getScoreBadgeVariant(criterion.score, criterion.max)">
                                                {{ criterion.score }}/{{ criterion.max }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="max-w-2xl">
                                            <div class="text-sm whitespace-pre-line font-mono leading-relaxed">{{
                                                criterion.justification }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex flex-wrap gap-1">
                                                <Button v-for="citation in criterion.citations" :key="citation"
                                                    variant="outline" size="sm" @click="showCitation(citation)"
                                                    class="text-xs px-2 py-1">
                                                    <Eye class="h-3 w-3 mr-1" />
                                                    {{ formatCitation(citation) }}
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <!-- Comprehensive Detailed Assessment -->
                    <Card v-if="assessment.output.detailed_assessment">
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <FileText class="h-5 w-5" />
                                <span>Comprehensive Session Analysis</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="bg-muted p-4 rounded-md">
                                <div class="text-sm font-mono leading-relaxed whitespace-pre-line">
                                    {{ assessment.output.detailed_assessment }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- AI Commentary -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center space-x-2">
                                <Zap class="h-5 w-5" />
                                <span>AI Assessment Commentary</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Overall Feedback (Session Assessment) or Overall Comment (Rubric Assessment) -->
                                <div>
                                    <h4 class="font-semibold mb-2">Overall Feedback</h4>
                                    <div class="text-sm bg-muted p-3 rounded-md whitespace-pre-line">
                                        {{ assessment?.output?.overall_feedback || assessment?.output?.overall_comment
                                            ||
                                            'No feedback available.' }}
                                    </div>
                                </div>

                                <!-- Safety Concerns (Session/Areas Assessment) or Red Flags (Rubric Assessment) -->
                                <div v-if="(assessment?.output?.safety_concerns && assessment.output.safety_concerns.length > 0) ||
                                    (assessment?.output?.red_flags && assessment.output.red_flags.length > 0)">
                                    <h4 class="font-semibold mb-2 text-red-600 flex items-center space-x-2">
                                        <AlertTriangle class="h-4 w-4" />
                                        <span>{{ (isDetailedAreasAssessment || isSessionAssessment) ? 'Safety Concerns'
                                            : 'Red Flags' }}</span>
                                    </h4>
                                    <div class="space-y-2">
                                        <div v-for="concern in (assessment.output.safety_concerns || assessment.output.red_flags || [])"
                                            :key="concern" class="bg-red-50 border border-red-200 p-3 rounded-md">
                                            <div class="text-sm text-red-800 whitespace-pre-line">{{ concern }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Learning Recommendations (for detailed areas assessment) -->
                                <div
                                    v-if="isDetailedAreasAssessment && assessment?.output?.recommendations && assessment.output.recommendations.length > 0">
                                    <h4 class="font-semibold mb-2 text-blue-600">Learning Recommendations</h4>
                                    <ul class="space-y-2">
                                        <li v-for="recommendation in assessment.output.recommendations"
                                            :key="recommendation"
                                            class="bg-blue-50 border border-blue-200 p-3 rounded-md">
                                            <div class="text-sm text-blue-800">{{ recommendation }}</div>
                                        </li>
                                    </ul>
                                </div>

                                <Separator />

                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <div class="flex items-center space-x-4">
                                        <span>Assessed: {{ formatDateTime(assessment.assessed_at) }}</span>
                                        <span>Model: {{ assessment?.output?.model_info?.name }}</span>
                                        <span v-if="assessment?.output?.model_info?.assessment_approach"
                                            class="text-blue-600">
                                            ({{ assessment.output.model_info.assessment_approach }})
                                        </span>
                                        <span v-if="assessment?.output?.model_info?.status === 'ai_unavailable'"
                                            class="text-amber-600">
                                            (AI Unavailable - Rubric Only)
                                        </span>
                                    </div>
                                    <Button v-if="canReassess" @click="handleReassess" :disabled="isReassessing"
                                        variant="outline" size="sm" class="flex items-center space-x-2">
                                        <RefreshCw class="h-3 w-3" :class="{ 'animate-spin': isReassessing }" />
                                        <span>{{ isReassessing ? 'Reassessing...' : 'Reassess' }}</span>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Citation Modal -->
        <Dialog v-model:open="showCitationModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ formatCitation(selectedCitation || '') }}</DialogTitle>
                </DialogHeader>
                <div class="py-4">
                    <p class="text-sm text-muted-foreground">
                        Citation details would be displayed here based on the citation type and ID.
                        This would link to specific chat messages, test results, or examination findings.
                    </p>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
