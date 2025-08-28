<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ExaminationFinding {
    examination_category: string;
    examination_type: string;
    findings: string | string[];
    performed_at: string;
}

interface SessionData {
    examination_findings?: ExaminationFinding[];
}

interface Props {
    sessionData?: SessionData;
}

defineProps<Props>();

const formatFindings = (findings: string | string[]) => {
    return Array.isArray(findings) ? findings.join(', ') : findings;
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-lg">Physical Exam Results</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3 text-sm">
            <div v-if="(sessionData?.examination_findings || []).length === 0" class="text-gray-500">
                No physical examinations performed yet.
            </div>
            <div 
                v-for="exam in (sessionData?.examination_findings || [])" 
                :key="`${exam.examination_category}-${exam.examination_type}`" 
                class="border rounded p-3 space-y-1"
            >
                <div class="font-medium">
                    {{ exam.examination_category }} • {{ exam.examination_type }}
                </div>
                <div class="text-gray-600 dark:text-gray-300 text-xs">
                    {{ formatFindings(exam.findings) }}
                </div>
                <div class="text-xs text-gray-500">
                    {{ new Date(exam.performed_at).toLocaleString() }}
                </div>
            </div>
        </CardContent>
    </Card>
</template>