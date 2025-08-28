<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FileText, Award, User, CheckCircle, AlertTriangle, Eye } from 'lucide-vue-next';

interface OsceCase {
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
}

interface Props {
    osceCase: OsceCase;
}

defineProps<Props>();

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
</script>

<template>
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
                        :class="`ml-2 px-2 py-1 rounded-full text-xs ${getDifficultyColor(osceCase.difficulty)}`">
                        {{ osceCase.difficulty || 'Not specified' }}
                    </span>
                </div>
                <div>
                    <span class="font-medium">Duration:</span>
                    <span class="ml-2">{{ osceCase.duration_minutes || 30 }} minutes</span>
                </div>
                <div>
                    <span class="font-medium">Budget:</span>
                    <span class="ml-2">${{ osceCase.budget || 1000 }}</span>
                </div>
                <div>
                    <span class="font-medium">Case ID:</span>
                    <span class="ml-2">#{{ osceCase.id }}</span>
                </div>
            </div>

            <!-- Description -->
            <div v-if="osceCase.description">
                <h4 class="font-semibold mb-2">Description</h4>
                <p class="text-sm text-muted-foreground">{{ osceCase.description }}</p>
            </div>

            <!-- Scenario -->
            <div v-if="osceCase.scenario">
                <h4 class="font-semibold mb-2">Clinical Scenario</h4>
                <div class="bg-muted p-3 rounded-md text-sm">{{ osceCase.scenario }}</div>
            </div>
        </CardContent>
    </Card>

    <!-- Learning Objectives and Requirements Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Learning Objectives -->
        <Card v-if="osceCase.learning_objectives && osceCase.learning_objectives.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center space-x-2 text-base">
                    <Award class="h-4 w-4" />
                    <span>Learning Objectives</span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="space-y-2">
                    <li v-for="objective in osceCase.learning_objectives" :key="objective"
                        class="flex items-start space-x-2">
                        <CheckCircle class="h-3 w-3 text-blue-500 mt-0.5 flex-shrink-0" />
                        <span class="text-sm">{{ objective }}</span>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <!-- History Taking Requirements -->
        <Card v-if="osceCase.key_history_points && osceCase.key_history_points.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center space-x-2 text-base">
                    <User class="h-4 w-4" />
                    <span>Key History Points</span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="space-y-2">
                    <li v-for="point in osceCase.key_history_points" :key="point"
                        class="flex items-start space-x-2">
                        <CheckCircle class="h-3 w-3 text-green-500 mt-0.5 flex-shrink-0" />
                        <span class="text-sm">{{ point }}</span>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <!-- Critical Examinations -->
        <Card v-if="osceCase.critical_examinations && osceCase.critical_examinations.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center space-x-2 text-base">
                    <Eye class="h-4 w-4" />
                    <span>Critical Examinations</span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="space-y-2">
                    <li v-for="exam in osceCase.critical_examinations" :key="exam"
                        class="flex items-start space-x-2">
                        <AlertTriangle class="h-3 w-3 text-orange-500 mt-0.5 flex-shrink-0" />
                        <span class="text-sm">{{ exam }}</span>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>