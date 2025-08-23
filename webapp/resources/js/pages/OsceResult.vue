<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { 
    CheckCircle, 
    XCircle, 
    AlertTriangle, 
    Clock, 
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
    case: {
        id: number;
        title: string;
        chief_complaint: string;
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

interface Assessment {
    score: number;
    max_score: number;
    percentage: number;
    assessed_at: string;
    assessor_model?: string;
    rubric_version?: string;
    output: {
        rubric_version: string;
        criteria: AssessmentCriterion[];
        overall_comment: string;
        red_flags: string[];
        model_info: {
            name: string;
            temperature: number;
            status?: string;
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

const handleReassess = async () => {
    isReassessing.value = true;
    try {
        await router.post(`/api/osce/sessions/${props.session.id}/assess`, {
            force: true
        });
        // Refresh the page to show updated results
        router.reload();
    } catch (error) {
        console.error('Failed to reassess session:', error);
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
        <div class="space-y-6">
            <!-- Header Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-2xl">Assessment Results</CardTitle>
                            <p class="text-muted-foreground mt-1">
                                {{ session.case.title }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Badge variant="outline" class="flex items-center space-x-1">
                                <User class="h-3 w-3" />
                                <span>{{ session.user.name }}</span>
                            </Badge>
                            <Badge variant="outline" class="flex items-center space-x-1">
                                <Calendar class="h-3 w-3" />
                                <span>{{ session.completed_at ? formatDateTime(session.completed_at) : 'Not completed' }}</span>
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
                        <Button @click="handleReassess" :disabled="isReassessing" class="flex items-center space-x-2">
                            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isReassessing }" />
                            <span>{{ isReassessing ? 'Assessing...' : 'Start Assessment' }}</span>
                        </Button>
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
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold" :class="getScoreColor(assessment.score, assessment.max_score)">
                                    {{ assessment.score }}/{{ assessment.max_score }}
                                </div>
                                <p class="text-sm text-muted-foreground">Total Score</p>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold" :class="getScoreColor(assessment.score, assessment.max_score)">
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
                                <Badge :variant="getScoreBadgeVariant(assessment.score, assessment.max_score)" class="text-sm px-3 py-1">
                                    {{ assessment.output.rubric_version }}
                                </Badge>
                                <p class="text-sm text-muted-foreground mt-1">Rubric Version</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Detailed Rubric Breakdown -->
                <Card>
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
                                    <TableCell class="max-w-md">
                                        <p class="text-sm">{{ criterion.justification }}</p>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-wrap gap-1">
                                            <Button
                                                v-for="citation in criterion.citations"
                                                :key="citation"
                                                variant="outline"
                                                size="sm"
                                                @click="showCitation(citation)"
                                                class="text-xs px-2 py-1"
                                            >
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
                            <div>
                                <h4 class="font-semibold mb-2">Overall Comment</h4>
                                <p class="text-sm bg-muted p-3 rounded-md">
                                    {{ assessment.output.overall_comment }}
                                </p>
                            </div>

                            <div v-if="assessment.output.red_flags.length > 0">
                                <h4 class="font-semibold mb-2 text-red-600 flex items-center space-x-2">
                                    <AlertTriangle class="h-4 w-4" />
                                    <span>Red Flags</span>
                                </h4>
                                <div class="space-y-2">
                                    <Badge
                                        v-for="flag in assessment.output.red_flags"
                                        :key="flag"
                                        variant="destructive"
                                        class="mr-2 mb-2"
                                    >
                                        {{ flag }}
                                    </Badge>
                                </div>
                            </div>

                            <Separator />

                            <div class="flex items-center justify-between text-sm text-muted-foreground">
                                <div class="flex items-center space-x-4">
                                    <span>Assessed: {{ formatDateTime(assessment.assessed_at) }}</span>
                                    <span>Model: {{ assessment.output.model_info.name }}</span>
                                    <span v-if="assessment.output.model_info.status === 'ai_unavailable'" class="text-amber-600">
                                        (AI Unavailable - Rubric Only)
                                    </span>
                                </div>
                                <Button
                                    v-if="canReassess"
                                    @click="handleReassess"
                                    :disabled="isReassessing"
                                    variant="outline"
                                    size="sm"
                                    class="flex items-center space-x-2"
                                >
                                    <RefreshCw class="h-3 w-3" :class="{ 'animate-spin': isReassessing }" />
                                    <span>{{ isReassessing ? 'Reassessing...' : 'Reassess' }}</span>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
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