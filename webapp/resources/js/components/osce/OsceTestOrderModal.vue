<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { FlaskConical } from 'lucide-vue-next';

interface MedicalTest {
    id: number;
    name: string;
    category: string;
    type: 'lab' | 'imaging' | 'procedure' | 'physical_exam';
    description?: string;
    indications?: string[];
    contraindications?: string[];
    cost: number;
    turnaround_minutes: number;
    requires_consent: boolean;
    risk_level: number;
    clinicalReasoning?: string;
    priority?: 'immediate' | 'urgent' | 'routine';
}

interface Props {
    isSessionActive: boolean;
}

interface Emits {
    (event: 'submit-orders', tests: MedicalTest[]): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const showModal = ref(false);
const testSearchQuery = ref('');
const searchResults = ref<MedicalTest[]>([]);
const selectedTests = ref<MedicalTest[]>([]);
const isSubmitting = ref(false);

const searchMedicalTests = async () => {
    if (testSearchQuery.value.length < 2) { 
        searchResults.value.length = 0; 
        return; 
    }
    try { 
        const resp = await fetch(`/api/medical-tests/search?q=${encodeURIComponent(testSearchQuery.value)}`);
        if (resp.ok) searchResults.value = await resp.json(); 
    } catch (e) { 
        console.error('Search error', e); 
    }
};

const isTestOrdered = (id: number) => selectedTests.value.some(t => t.id === id);

const selectTest = (test: MedicalTest) => { 
    if (!isTestOrdered(test.id)) {
        selectedTests.value.push({ 
            ...test, 
            clinicalReasoning: '', 
            priority: undefined 
        }); 
    }
};

const removeTest = (id: number) => { 
    selectedTests.value = selectedTests.value.filter(t => t.id !== id); 
};

const clearSelection = () => { 
    selectedTests.value.length = 0; 
    showModal.value = false; 
};

const totalCost = computed(() => 
    selectedTests.value.reduce((sum, t) => sum + (t.cost || 0), 0)
);

const maxTurnaroundTime = computed(() => 
    selectedTests.value.reduce((max, t) => Math.max(max, t.turnaround_minutes || 0), 0)
);

const canSubmitOrders = computed(() => 
    selectedTests.value.length > 0 && 
    selectedTests.value.every(t => (t.clinicalReasoning || '').length >= 20 && !!t.priority)
);

const handleSubmitOrders = () => {
    if (!canSubmitOrders.value || isSubmitting.value) return;
    
    isSubmitting.value = true;
    emit('submit-orders', [...selectedTests.value]);
    
    // Reset state after submission
    selectedTests.value.length = 0;
    testSearchQuery.value = '';
    searchResults.value.length = 0;
    showModal.value = false;
    isSubmitting.value = false;
};
</script>

<template>
    <Dialog v-model:open="showModal">
        <DialogTrigger asChild>
            <Button variant="outline" class="w-full flex items-center gap-2" :disabled="!isSessionActive">
                <FlaskConical class="h-4 w-4" />
                Order Tests
            </Button>
        </DialogTrigger>
        <DialogContent class="max-w-3xl">
            <DialogHeader>
                <DialogTitle>Order Medical Tests</DialogTitle>
                <DialogDescription>Search and select tests. Provide reasoning and priority.</DialogDescription>
            </DialogHeader>
            <div class="space-y-4">
                <!-- Search Section -->
                <div>
                    <Input 
                        v-model="testSearchQuery" 
                        placeholder="Search tests... (e.g. 'troponin', 'ecg', 'chest x-ray')" 
                        @input="searchMedicalTests" 
                        :disabled="!isSessionActive" 
                    />
                    <div v-if="searchResults.length > 0" class="mt-2 space-y-1 max-h-40 overflow-y-auto">
                        <div 
                            v-for="test in searchResults" 
                            :key="test.id" 
                            class="p-2 border rounded flex items-center justify-between"
                        >
                            <div>
                                <div class="font-medium">{{ test.name }}</div>
                                <div class="text-xs text-gray-500">{{ test.category }} • {{ test.type }}</div>
                                <div v-if="test.cost" class="text-xs text-gray-500">${{ test.cost }}</div>
                            </div>
                            <Button 
                                size="sm" 
                                variant="outline" 
                                :disabled="isTestOrdered(test.id) || !isSessionActive" 
                                @click="selectTest(test)"
                            >
                                {{ isTestOrdered(test.id) ? 'Selected' : 'Select' }}
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Selected Tests Section -->
                <div v-if="selectedTests.length > 0" class="space-y-3">
                    <h4 class="font-medium">Selected Tests ({{ selectedTests.length }})</h4>
                    <div class="space-y-3 max-h-60 overflow-y-auto">
                        <div 
                            v-for="test in selectedTests" 
                            :key="test.id" 
                            class="p-3 rounded border space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <div class="font-medium">{{ test.name }}</div>
                                <Button size="sm" variant="outline" @click="removeTest(test.id)">
                                    Remove
                                </Button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <Textarea 
                                    v-model="test.clinicalReasoning" 
                                    placeholder="Provide clinical reasoning (min 20 chars)" 
                                    :rows="3" 
                                    :disabled="!isSessionActive" 
                                />
                                <Select v-model="test.priority">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Priority" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="immediate">Immediate</SelectItem>
                                        <SelectItem value="urgent">Urgent</SelectItem>
                                        <SelectItem value="routine">Routine</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Cost: ${{ test.cost }}</span>
                                <span>Turnaround: {{ test.turnaround_minutes }} min</span>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Total Cost:</span>
                            <span class="font-medium">${{ totalCost }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Max Turnaround:</span>
                            <span class="font-medium">{{ maxTurnaroundTime }} minutes</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between">
                        <Button variant="outline" @click="clearSelection">
                            Cancel
                        </Button>
                        <Button 
                            @click="handleSubmitOrders" 
                            :disabled="!canSubmitOrders || isSubmitting || !isSessionActive"
                        >
                            {{ isSubmitting ? 'Submitting...' : `Order ${selectedTests.length} Test${selectedTests.length > 1 ? 's' : ''}` }}
                        </Button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>