<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
    Award, 
    User, 
    Calendar, 
    RefreshCw, 
    CheckCircle, 
    XCircle, 
    AlertTriangle,
    Zap 
} from 'lucide-vue-next';

interface Assessment {
    id: number;
    session_id: number;
    status: string;
    percentage: number;
    score: number;
    max_score: number;
    completed_at: string;
    output: {
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
        model_info: {
            name: string;
            temperature: number;
            status?: string;
            assessment_approach?: string;
        };
    };
}

interface Session {
    id: number;
    status: string;
    completed_at?: string;
    duration_minutes: number;
    time_extended?: number;
    clinical_reasoning_score?: number;
    total_test_cost?: number;
    user: {
        id: number;
        name: string;
    };
}

interface Props {
    session: Session;
    assessment?: Assessment;
    isAssessed: boolean;
    canReassess: boolean;
    isReassessing?: boolean;
}

interface Emits {
    (event: 'reassess'): void;
    (event: 'scroll-to-clinical-reasoning'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
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

const getScoreBadgeVariant = (score: number, max: number) => {
    const percentage = (score / max) * 100;
    if (percentage >= 80) return 'default';
    if (percentage >= 60) return 'secondary';
    return 'destructive';
};

const handleReassess = () => {
    emit('reassess');
};

const handleScrollToClinicalReasoning = () => {
    emit('scroll-to-clinical-reasoning');
};
</script>

<template>
    <!-- Performance Overview Card -->
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <Award class="h-5 w-5" />
                    <span>Performance Overview</span>
                </div>
                <div class="flex items-center space-x-2">
                    <Button 
                        v-if="canReassess" 
                        @click="handleReassess"
                        :disabled="isReassessing" 
                        size="sm" 
                        variant="outline"
                    >
                        <RefreshCw :class="{ 'animate-spin': isReassessing }" class="h-4 w-4 mr-1" />
                        {{ isReassessing ? 'Processing...' : 'Reassess' }}
                    </Button>
                </div>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Student Info -->
                <div class="text-center">
                    <div class="flex justify-center mb-2">
                        <User class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <div class="text-sm font-medium">Student</div>
                    <div class="text-xs text-muted-foreground">{{ session.user.name }}</div>
                </div>

                <!-- Completion Date -->
                <div class="text-center">
                    <div class="flex justify-center mb-2">
                        <Calendar class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <div class="text-sm font-medium">Completed</div>
                    <div class="text-xs text-muted-foreground">
                        {{ session.completed_at ? formatDateTime(session.completed_at) : 'N/A' }}
                    </div>
                </div>

                <!-- Duration -->
                <div class="text-center">
                    <div class="flex justify-center mb-2">
                        <CheckCircle class="h-6 w-6 text-green-500" />
                    </div>
                    <div class="text-sm font-medium">Duration</div>
                    <div class="text-xs text-muted-foreground">
                        {{ session.duration_minutes }} minutes
                        <span v-if="session.time_extended" class="text-orange-600">
                            (+{{ session.time_extended }}min)
                        </span>
                    </div>
                </div>

                <!-- Assessment Status -->
                <div class="text-center">
                    <div class="flex justify-center mb-2">
                        <CheckCircle v-if="isAssessed" class="h-6 w-6 text-green-500" />
                        <XCircle v-else-if="session.status === 'completed'" class="h-6 w-6 text-orange-500" />
                        <AlertTriangle v-else class="h-6 w-6 text-gray-400" />
                    </div>
                    <div class="text-sm font-medium">Assessment</div>
                    <div class="text-xs">
                        <Badge v-if="isAssessed" variant="default">Complete</Badge>
                        <Badge v-else-if="session.status === 'completed'" variant="secondary">Pending</Badge>
                        <Badge v-else variant="outline">Not Available</Badge>
                    </div>
                </div>
            </div>

            <!-- Assessment Score Section -->
            <div v-if="isAssessed && assessment" class="mt-6 pt-6 border-t">
                <div class="text-center space-y-4">
                    <!-- Overall Score -->
                    <div>
                        <div class="text-3xl font-bold" :class="performanceLevelColor">
                            {{ Math.round(assessment.percentage) }}%
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ assessment.score }}/{{ assessment.max_score }} points
                        </div>
                        <Badge 
                            :variant="getScoreBadgeVariant(assessment.score, assessment.max_score)"
                            class="mt-2"
                        >
                            {{ performanceLevel }}
                        </Badge>
                    </div>

                    <!-- Clinical Reasoning Score (if available) -->
                    <div v-if="session.clinical_reasoning_score !== null && session.clinical_reasoning_score !== undefined" class="pt-4 border-t">
                        <div class="flex items-center justify-center space-x-2 mb-2">
                            <Zap class="h-4 w-4 text-blue-500" />
                            <span class="font-medium">Clinical Reasoning</span>
                        </div>
                        <div class="text-xl font-semibold text-blue-600">
                            {{ Math.round((session.clinical_reasoning_score || 0) * 100) }}%
                        </div>
                        <Button 
                            @click="handleScrollToClinicalReasoning"
                            variant="link" 
                            size="sm" 
                            class="mt-1"
                        >
                            View Details
                        </Button>
                    </div>

                    <!-- Test Cost Summary -->
                    <div v-if="session.total_test_cost !== null && session.total_test_cost !== undefined" class="pt-4 border-t">
                        <div class="flex items-center justify-center space-x-2 mb-2">
                            <span class="font-medium">Total Test Cost</span>
                        </div>
                        <div class="text-lg font-semibold text-gray-700">
                            ${{ Math.round(session.total_test_cost || 0) }}
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>