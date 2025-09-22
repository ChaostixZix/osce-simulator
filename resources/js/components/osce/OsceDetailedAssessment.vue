<script setup lang="ts">
import { ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { CheckCircle, XCircle, AlertTriangle, Eye } from 'lucide-vue-next';

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
    output: {
        rubric_version?: string;
        criteria?: AssessmentCriterion[];
        overall_comment?: string;
        red_flags?: string[];
        detailed_assessment?: string;
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
    assessment: Assessment;
    isDetailedAreasAssessment: boolean;
    isSessionAssessment: boolean;
    isRubricAssessment: boolean;
}

const props = defineProps<Props>();

const selectedCitation = ref<string | null>(null);
const showCitationModal = ref(false);

const criteriaLabels: Record<string, string> = {
    history: 'History-taking',
    exam: 'Physical Exam',
    investigations: 'Investigations',
    diagnosis: 'Diagnosis & Reasoning',
    management: 'Management Plan',
    communication: 'Communication/Professionalism',
    safety: 'Time Use/Safety'
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

const showCitation = (citation: string) => {
    selectedCitation.value = citation;
    showCitationModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Detailed Clinical Areas Assessment -->
        <div v-if="isDetailedAreasAssessment && assessment.output.clinical_areas">
            <Card>
                <CardHeader>
                    <CardTitle>Detailed Clinical Assessment</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <div 
                            v-for="area in assessment.output.clinical_areas.filter((a: any) => a.key !== 'clinical_reasoning')" 
                            :key="area.key"
                            class="border rounded-lg p-4"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold">{{ area.area }}</h4>
                                <Badge :variant="getScoreBadgeVariant(area.score, area.max_score)">
                                    {{ area.score }}/{{ area.max_score }}
                                    ({{ Math.round((area.score / area.max_score) * 100) }}%)
                                </Badge>
                            </div>
                            
                            <div class="text-sm text-muted-foreground leading-relaxed mb-3">
                                {{ area.justification }}
                            </div>

                            <!-- Citations for this area -->
                            <div v-if="area.citations && area.citations.length > 0" class="border-t pt-3">
                                <div class="text-xs font-medium mb-2">Evidence:</div>
                                <div class="flex flex-wrap gap-1">
                                    <Button
                                        v-for="citation in area.citations"
                                        :key="citation"
                                        @click="showCitation(citation)"
                                        variant="outline"
                                        size="sm"
                                        class="h-6 text-xs"
                                    >
                                        <Eye class="h-3 w-3 mr-1" />
                                        {{ formatCitation(citation) }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Session Assessment Format -->
        <div v-else-if="isSessionAssessment">
            <Card>
                <CardHeader>
                    <CardTitle>Assessment Summary</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Strengths -->
                    <div v-if="assessment.output.strengths && assessment.output.strengths.length > 0">
                        <h4 class="font-semibold flex items-center mb-3">
                            <CheckCircle class="h-4 w-4 text-green-500 mr-2" />
                            Strengths
                        </h4>
                        <ul class="space-y-1 ml-6">
                            <li v-for="strength in assessment.output.strengths" :key="strength" class="text-sm">
                                • {{ strength }}
                            </li>
                        </ul>
                    </div>

                    <Separator v-if="assessment.output.strengths && assessment.output.areas_for_improvement" />

                    <!-- Areas for Improvement -->
                    <div v-if="assessment.output.areas_for_improvement && assessment.output.areas_for_improvement.length > 0">
                        <h4 class="font-semibold flex items-center mb-3">
                            <AlertTriangle class="h-4 w-4 text-yellow-500 mr-2" />
                            Areas for Improvement
                        </h4>
                        <ul class="space-y-1 ml-6">
                            <li v-for="area in assessment.output.areas_for_improvement" :key="area" class="text-sm">
                                • {{ area }}
                            </li>
                        </ul>
                    </div>

                    <Separator v-if="assessment.output.areas_for_improvement && assessment.output.safety_concerns" />

                    <!-- Safety Concerns -->
                    <div v-if="assessment.output.safety_concerns && assessment.output.safety_concerns.length > 0">
                        <h4 class="font-semibold flex items-center mb-3">
                            <XCircle class="h-4 w-4 text-red-500 mr-2" />
                            Safety Concerns
                        </h4>
                        <ul class="space-y-1 ml-6">
                            <li v-for="concern in assessment.output.safety_concerns" :key="concern" class="text-sm">
                                • {{ concern }}
                            </li>
                        </ul>
                    </div>

                    <Separator v-if="assessment.output.safety_concerns && assessment.output.recommendations" />

                    <!-- Recommendations -->
                    <div v-if="assessment.output.recommendations && assessment.output.recommendations.length > 0">
                        <h4 class="font-semibold mb-3">Recommendations</h4>
                        <ul class="space-y-1 ml-6">
                            <li v-for="recommendation in assessment.output.recommendations" :key="recommendation" class="text-sm">
                                • {{ recommendation }}
                            </li>
                        </ul>
                    </div>

                    <!-- Overall Feedback -->
                    <div v-if="assessment.output.overall_feedback">
                        <Separator />
                        <div>
                            <h4 class="font-semibold mb-3">Overall Feedback</h4>
                            <div class="text-sm leading-relaxed bg-muted p-4 rounded-lg">
                                {{ assessment.output.overall_feedback }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Legacy Rubric Assessment -->
        <div v-else-if="isRubricAssessment && assessment.output.criteria">
            <Card>
                <CardHeader>
                    <CardTitle>Detailed Assessment Criteria</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-1/4">Criterion</TableHead>
                                <TableHead class="w-1/6 text-center">Score</TableHead>
                                <TableHead>Justification</TableHead>
                                <TableHead class="w-1/6 text-center">Evidence</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="criterion in assessment.output.criteria" :key="criterion.key">
                                <TableCell class="font-medium">
                                    {{ criteriaLabels[criterion.key] || criterion.key }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge :variant="getScoreBadgeVariant(criterion.score, criterion.max)">
                                        {{ criterion.score }}/{{ criterion.max }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-sm">
                                    {{ criterion.justification }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <div v-if="criterion.citations && criterion.citations.length > 0" class="space-y-1">
                                        <Button
                                            v-for="citation in criterion.citations.slice(0, 2)"
                                            :key="citation"
                                            @click="showCitation(citation)"
                                            variant="outline"
                                            size="sm"
                                            class="h-6 text-xs block w-full"
                                        >
                                            <Eye class="h-3 w-3 mr-1" />
                                            {{ formatCitation(citation) }}
                                        </Button>
                                        <Button
                                            v-if="criterion.citations.length > 2"
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 text-xs"
                                        >
                                            +{{ criterion.citations.length - 2 }} more
                                        </Button>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground">No citations</span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Red Flags -->
                    <div v-if="assessment.output.red_flags && assessment.output.red_flags.length > 0" class="mt-6 pt-6 border-t">
                        <h4 class="font-semibold flex items-center mb-3">
                            <XCircle class="h-4 w-4 text-red-500 mr-2" />
                            Safety Concerns / Red Flags
                        </h4>
                        <ul class="space-y-1 ml-6">
                            <li v-for="flag in assessment.output.red_flags" :key="flag" class="text-sm text-red-600">
                                • {{ flag }}
                            </li>
                        </ul>
                    </div>

                    <!-- Overall Comment -->
                    <div v-if="assessment.output.overall_comment" class="mt-6 pt-6 border-t">
                        <h4 class="font-semibold mb-3">Overall Assessment</h4>
                        <div class="text-sm leading-relaxed bg-muted p-4 rounded-lg">
                            {{ assessment.output.overall_comment }}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Citation Modal -->
        <Dialog v-model:open="showCitationModal">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Evidence Citation</DialogTitle>
                </DialogHeader>
                <div class="space-y-4">
                    <div class="p-4 bg-muted rounded-lg">
                        <div class="font-mono text-sm">{{ selectedCitation }}</div>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        This citation references specific evidence from your session that supports the assessment.
                    </p>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>