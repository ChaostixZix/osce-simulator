<script setup lang="ts">
import { ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Zap, Eye } from 'lucide-vue-next';

interface ClinicalArea {
    area: string;
    key: string;
    score: number;
    max_score: number;
    justification: string;
    citations: string[];
}

interface RationalizationCard {
    id: number;
    type: string;
    status: string;
    content: any;
}

interface RationalizationEvaluation {
    id: number;
    type: string;
    score: number;
    feedback: string;
}

interface Props {
    clinicalReasoningArea?: ClinicalArea;
    rationalization?: {
        id: number;
        status: string;
        completed_at?: string;
        cards?: RationalizationCard[];
        evaluations?: RationalizationEvaluation[];
        diagnosisEntries?: any[];
    };
}

const props = defineProps<Props>();

const selectedCitation = ref<string | null>(null);
const showCitationModal = ref(false);

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
    <div id="clinical-reasoning" class="space-y-6">
        <!-- Clinical Reasoning Analysis -->
        <Card v-if="clinicalReasoningArea">
            <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                    <Zap class="h-5 w-5 text-blue-500" />
                    <span>Clinical Reasoning Analysis</span>
                    <Badge 
                        :variant="getScoreBadgeVariant(clinicalReasoningArea.score, clinicalReasoningArea.max_score)"
                        class="ml-auto"
                    >
                        {{ clinicalReasoningArea.score }}/{{ clinicalReasoningArea.max_score }}
                        ({{ Math.round((clinicalReasoningArea.score / clinicalReasoningArea.max_score) * 100) }}%)
                    </Badge>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="text-sm leading-relaxed">
                    {{ clinicalReasoningArea.justification }}
                </div>

                <!-- Citations -->
                <div v-if="clinicalReasoningArea.citations && clinicalReasoningArea.citations.length > 0" class="pt-4 border-t">
                    <h4 class="font-medium mb-2 text-sm">Evidence Citations:</h4>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="citation in clinicalReasoningArea.citations"
                            :key="citation"
                            @click="showCitation(citation)"
                            variant="outline"
                            size="sm"
                            class="h-7 text-xs"
                        >
                            <Eye class="h-3 w-3 mr-1" />
                            {{ formatCitation(citation) }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Rationalization Cards Summary -->
        <Card v-if="rationalization && rationalization.cards && rationalization.cards.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                    <span>Rationalization Summary</span>
                    <Badge variant="outline" class="ml-auto">
                        {{ rationalization.cards.length }} Cards
                    </Badge>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="grid gap-3">
                    <div 
                        v-for="card in rationalization.cards" 
                        :key="card.id"
                        class="p-3 border rounded-lg space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <Badge variant="outline" class="text-xs">
                                {{ card.type.replace('_', ' ').toUpperCase() }}
                            </Badge>
                            <Badge 
                                :variant="card.status === 'completed' ? 'default' : 'secondary'"
                                class="text-xs"
                            >
                                {{ card.status.toUpperCase() }}
                            </Badge>
                        </div>
                        
                        <div v-if="card.content && typeof card.content === 'object'" class="text-sm text-muted-foreground">
                            <div v-if="card.content.diagnosis">
                                <strong>Diagnosis:</strong> {{ card.content.diagnosis }}
                            </div>
                            <div v-if="card.content.reasoning">
                                <strong>Reasoning:</strong> {{ card.content.reasoning }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Evaluations Summary -->
                <div v-if="rationalization.evaluations && rationalization.evaluations.length > 0" class="pt-4 border-t">
                    <h4 class="font-medium mb-3 text-sm">Evaluation Summary:</h4>
                    <div class="space-y-2">
                        <div 
                            v-for="evaluation in rationalization.evaluations" 
                            :key="evaluation.id"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="font-medium">{{ evaluation.type.replace('_', ' ').toUpperCase() }}</span>
                            <div class="flex items-center space-x-2">
                                <Badge 
                                    :variant="getScoreBadgeVariant(evaluation.score, 100)"
                                    class="text-xs"
                                >
                                    {{ evaluation.score }}%
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

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