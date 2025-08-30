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
            <Button variant="outline" class="w-full flex items-center gap-2 font-mono text-sm tracking-wide hover:border-blue-400 transition-all duration-200" :disabled="!isSessionActive">
                <FlaskConical class="h-4 w-4" />
                ORDER TESTS
            </Button>
        </DialogTrigger>
        <DialogContent class="max-w-4xl bg-white dark:bg-slate-950 border border-slate-200/20 dark:border-slate-800/50 shadow-2xl backdrop-blur-sm"
            style="clip-path: polygon(0 0, calc(100% - 20px) 0, 100% 20px, 100% 100%, 20px 100%, 0 calc(100% - 20px))">
            <!-- Tech grid overlay -->
            <div class="absolute inset-0 opacity-5 dark:opacity-10 pointer-events-none">
                <div class="w-full h-full" 
                    style="background-image: linear-gradient(rgba(148, 163, 184, 0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, 0.3) 1px, transparent 1px); background-size: 20px 20px;">
                </div>
            </div>
            <DialogHeader class="relative border-b border-slate-200/30 dark:border-slate-700/50 bg-gradient-to-r from-slate-50/50 to-transparent dark:from-slate-900/50 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 bg-blue-400 rounded-full shadow-lg shadow-blue-400/50 animate-pulse"></div>
                    <DialogTitle class="font-mono font-semibold text-slate-900 dark:text-slate-100 tracking-wide uppercase text-sm">Order Medical Tests</DialogTitle>
                </div>
                <DialogDescription class="font-mono text-xs text-slate-600 dark:text-slate-400 uppercase tracking-widest ml-5">Search → Select → Configure → Order</DialogDescription>
                <div class="absolute top-0 right-0 w-20 h-full bg-gradient-to-l from-blue-500/10 to-transparent dark:from-blue-400/10"></div>
            </DialogHeader>
            <div class="space-y-6 relative">
                <!-- Search Section -->
                <div>
                    <div class="relative">
                        <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                            <div class="w-2 h-2 bg-blue-400 rounded-full shadow-lg shadow-blue-400/50"></div>
                        </div>
                        <Input 
                            v-model="testSearchQuery" 
                            placeholder="SEARCH TESTS // e.g. 'troponin', 'ecg', 'chest x-ray'" 
                            @input="searchMedicalTests" 
                            :disabled="!isSessionActive"
                            class="w-full bg-slate-50 dark:bg-slate-900/50 border-2 border-slate-200 dark:border-slate-700 focus:border-blue-400 dark:focus:border-blue-400 pl-10 pr-4 py-3 font-mono text-sm text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400 transition-all duration-200"
                            style="clip-path: polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px))"
                        />
                    </div>
                    <div v-if="searchResults.length > 0" class="mt-4 space-y-2 max-h-48 overflow-y-auto">
                        <div 
                            v-for="test in searchResults" 
                            :key="test.id" 
                            class="group relative p-4 bg-slate-50/50 hover:bg-slate-100/50 dark:bg-slate-900/30 dark:hover:bg-slate-800/50 border-l-2 border-slate-300 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-400 transition-all duration-200"
                            style="clip-path: polygon(0 0, calc(100% - 6px) 0, 100% 6px, 100% 100%, 0 100%)"
                        >
                            <div class="flex items-center justify-between">
                                <div class="space-y-1">
                                    <div class="font-mono font-semibold text-slate-900 dark:text-slate-100 text-sm uppercase tracking-wide">{{ test.name }}</div>
                                    <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 font-mono">
                                        <span class="px-2 py-0.5 bg-slate-200 dark:bg-slate-700 rounded text-xs">{{ test.category }}</span>
                                        <span>•</span>
                                        <span>{{ test.type }}</span>
                                    </div>
                                    <div v-if="test.cost" class="text-xs font-mono text-blue-600 dark:text-blue-400 font-semibold">${{ test.cost }}</div>
                                </div>
                                <Button 
                                    size="sm" 
                                    variant="outline" 
                                    :disabled="isTestOrdered(test.id) || !isSessionActive" 
                                    @click="selectTest(test)"
                                    :class="[
                                        'font-mono text-xs tracking-wide transition-all duration-200',
                                        isTestOrdered(test.id)
                                            ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-l-2 border-emerald-400'
                                            : 'bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-800/50 text-blue-700 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 border-l-2 border-blue-400 hover:border-blue-500'
                                    ]"
                                    style="clip-path: polygon(0 0, calc(100% - 4px) 0, 100% 4px, 100% 100%, 0 100%)"
                                >
                                    {{ isTestOrdered(test.id) ? 'SELECTED' : 'SELECT' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Tests Section -->
                <div v-if="selectedTests.length > 0" class="space-y-4">
                    <div class="flex items-center gap-3 pb-2 border-b border-slate-200/30 dark:border-slate-700/50">
                        <div class="w-1 h-4 bg-gradient-to-b from-blue-400 to-purple-400"></div>
                        <h4 class="font-mono font-semibold text-slate-900 dark:text-slate-100 uppercase tracking-widest text-sm">
                            Selected Tests [{{ selectedTests.length }}]
                        </h4>
                    </div>
                    <div class="space-y-4 max-h-72 overflow-y-auto">
                        <div 
                            v-for="test in selectedTests" 
                            :key="test.id" 
                            class="relative p-4 bg-gradient-to-r from-slate-50/80 to-blue-50/30 dark:from-slate-900/50 dark:to-blue-950/30 border-l-2 border-blue-400 shadow-md shadow-blue-500/10"
                            style="clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 0 100%)"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <div class="font-mono font-semibold text-slate-900 dark:text-slate-100 uppercase tracking-wide text-sm">{{ test.name }}</div>
                                <Button size="sm" variant="outline" @click="removeTest(test.id)"
                                    class="px-3 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-800/50 text-red-700 dark:text-red-400 font-mono text-xs tracking-wide transition-all duration-200 border-l-2 border-red-400"
                                    style="clip-path: polygon(0 0, calc(100% - 4px) 0, 100% 4px, 100% 100%, 0 100%)">
                                    REMOVE
                                </Button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-xs font-mono font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-widest">
                                        Clinical Reasoning
                                    </label>
                                    <Textarea 
                                        v-model="test.clinicalReasoning" 
                                        placeholder="Clinical reasoning (min 20 chars)" 
                                        :rows="3" 
                                        :disabled="!isSessionActive"
                                        class="w-full bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 focus:border-blue-400 dark:focus:border-blue-400 px-3 py-2 font-mono text-sm text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400 resize-none transition-all duration-200"
                                        style="clip-path: polygon(0 0, calc(100% - 6px) 0, 100% 6px, 100% 100%, 6px 100%, 0 calc(100% - 6px))"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-xs font-mono font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-widest">
                                        Priority Level
                                    </label>
                                    <Select v-model="test.priority">
                                        <SelectTrigger class="w-full bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 focus:border-blue-400 dark:focus:border-blue-400 px-3 py-2 font-mono text-sm text-slate-900 dark:text-slate-100 transition-all duration-200"
                                            style="clip-path: polygon(0 0, calc(100% - 6px) 0, 100% 6px, 100% 100%, 6px 100%, 0 calc(100% - 6px))">
                                            <SelectValue placeholder="SELECT PRIORITY" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="immediate">IMMEDIATE</SelectItem>
                                            <SelectItem value="urgent">URGENT</SelectItem>
                                            <SelectItem value="routine">ROUTINE</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div class="flex justify-between mt-3 pt-2 border-t border-slate-200/30 dark:border-slate-700/50">
                                <span class="text-xs font-mono text-slate-600 dark:text-slate-400">COST: <span class="text-blue-600 dark:text-blue-400 font-semibold">${{ test.cost }}</span></span>
                                <span class="text-xs font-mono text-slate-600 dark:text-slate-400">ETA: <span class="text-purple-600 dark:text-purple-400 font-semibold">{{ test.turnaround_minutes }}m</span></span>
                            </div>
                            <div class="absolute top-4 right-16">
                                <div class="w-2 h-2 bg-blue-400 rounded-full shadow-lg shadow-blue-400/50 animate-pulse"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="relative p-4 bg-gradient-to-r from-slate-900 to-blue-900 dark:from-slate-950 dark:to-blue-950 border-2 border-blue-400/30"
                        style="clip-path: polygon(0 0, calc(100% - 16px) 0, 100% 16px, 100% 100%, 16px 100%, 0 calc(100% - 16px))">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex justify-between">
                                <span class="font-mono text-slate-300 uppercase tracking-widest">Total Cost:</span>
                                <span class="font-mono font-bold text-blue-400">${{ totalCost }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-mono text-slate-300 uppercase tracking-widest">Max ETA:</span>
                                <span class="font-mono font-bold text-purple-400">{{ maxTurnaroundTime }}m</span>
                            </div>
                        </div>
                        <div class="absolute top-0 right-0 w-20 h-full bg-gradient-to-l from-blue-500/20 to-transparent"></div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between gap-4">
                        <Button variant="outline" @click="clearSelection"
                            class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono text-sm tracking-wide transition-all duration-200 border-l-4 border-slate-400 dark:border-slate-500">
                            CANCEL
                        </Button>
                        <Button 
                            @click="handleSubmitOrders" 
                            :disabled="!canSubmitOrders || isSubmitting || !isSessionActive"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600 disabled:from-slate-400 disabled:to-slate-500 text-white font-mono text-sm tracking-wide transition-all duration-200 border-l-4 border-blue-400 disabled:border-slate-400 shadow-lg shadow-blue-500/25 disabled:shadow-none">
                            {{ isSubmitting ? 'ORDERING...' : `ORDER [${selectedTests.length}] TEST${selectedTests.length > 1 ? 'S' : ''}` }}
                        </Button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>