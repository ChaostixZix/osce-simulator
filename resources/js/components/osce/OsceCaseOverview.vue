<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

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

interface Props {
    osceCase: OsceCase;
}

defineProps<Props>();
</script>

<template>
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
            <div v-if="osceCase?.ai_patient_vitals">
                <div class="font-semibold">Vital Signs</div>
                <div class="grid grid-cols-2 gap-2">
                    <div v-for="(v, k) in osceCase.ai_patient_vitals" :key="k" class="flex justify-between">
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
</template>