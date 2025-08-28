<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

// Import modular components
import OsceCaseInfo from '@/components/osce/OsceCaseInfo.vue';
import OscePerformanceOverview from '@/components/osce/OscePerformanceOverview.vue';
import OsceClinicalReasoning from '@/components/osce/OsceClinicalReasoning.vue';
import OsceDetailedAssessment from '@/components/osce/OsceDetailedAssessment.vue';

// Type definitions
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
    user: {
        id: number;
        name: string;
    };
    rationalization?: {
        id: number;
        status: string;
        completed_at?: string;
        cards?: any[];
        evaluations?: any[];
        diagnosisEntries?: any[];
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
}

interface Assessment {
    id: number;
    session_id: number;
    status: string;
    percentage: number;
    score: number;
    max_score: number;
    completed_at: string;
    assessment_type?: string;
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

// Reactive state
const isReassessing = ref(false);

// Computed properties for assessment types
const isDetailedAreasAssessment = computed(() => {
    return props.assessment?.output?.assessment_type === 'detailed_clinical_areas_assessment' ||
        (props.assessment?.output?.clinical_areas && Array.isArray(props.assessment.output.clinical_areas));
});

const isSessionAssessment = computed(() => {
    return props.assessment?.output?.assessment_type === 'holistic_clinical_assessment' ||
        props.assessment?.assessment_type === 'session_assessment';
});

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

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'OSCE', href: '/osce' },
    { title: 'Assessment Results', href: '' },
];

// Event handlers
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

const scrollToClinicalReasoning = () => {
    const element = document.getElementById('clinical-reasoning');
    element?.scrollIntoView({ behavior: 'smooth' });
};
</script>

<template>
    <Head title="OSCE Assessment Results" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <!-- Error Display -->
            <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <strong>Error:</strong> {{ error }}
            </div>

            <!-- OSCE Case Scenario - Top Section -->
            <div class="space-y-6">
                <OsceCaseInfo :osce-case="session.case" />

                <!-- Performance Overview -->
                <OscePerformanceOverview 
                    :session="session"
                    :assessment="assessment"
                    :is-assessed="isAssessed"
                    :can-reassess="canReassess"
                    :is-reassessing="isReassessing"
                    @reassess="handleReassess"
                    @scroll-to-clinical-reasoning="scrollToClinicalReasoning"
                />

                <!-- Assessment Results Section -->
                <div v-if="isAssessed && assessment">
                    <!-- Clinical Reasoning Analysis -->
                    <OsceClinicalReasoning 
                        :clinical-reasoning-area="clinicalReasoningArea"
                        :rationalization="session.rationalization"
                    />

                    <!-- Detailed Assessment -->
                    <OsceDetailedAssessment 
                        :assessment="assessment"
                        :is-detailed-areas-assessment="isDetailedAreasAssessment"
                        :is-session-assessment="isSessionAssessment"
                        :is-rubric-assessment="isRubricAssessment"
                    />
                </div>

                <!-- No Assessment Message -->
                <div v-else-if="!isAssessed && session.status === 'completed'" class="text-center py-8">
                    <div class="text-muted-foreground mb-4">
                        Assessment is being processed. This may take a few minutes.
                    </div>
                    <Button @click="router.reload()" variant="outline">
                        Check Again
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>