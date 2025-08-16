<script setup lang="ts">
import { MCQ } from '@/components/ui/mcq'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'

// Sample MCQ data
const sampleMCQs = [
    {
        question: "What is the capital of France?",
        options: [
            { id: 'a', text: 'London', value: 'a' },
            { id: 'b', text: 'Berlin', value: 'b' },
            { id: 'c', text: 'Paris', value: 'c' },
            { id: 'd', text: 'Madrid', value: 'd' }
        ],
        correctAnswer: 'c',
        explanation: 'Paris is the capital and largest city of France.'
    },
    {
        question: "Which programming language is this component built with?",
        options: [
            { id: 'a', text: 'JavaScript', value: 'a' },
            { id: 'b', text: 'Python', value: 'b' },
            { id: 'c', text: 'Vue.js', value: 'c' },
            { id: 'd', text: 'React', value: 'd' }
        ],
        correctAnswer: 'c',
        explanation: 'This MCQ component is built with Vue.js 3 and TypeScript.'
    },
    {
        question: "What is 2 + 2?",
        options: [
            { id: 'a', text: '3', value: 'a' },
            { id: 'b', text: '4', value: 'b' },
            { id: 'c', text: '5', value: 'c' },
            { id: 'd', text: '6', value: 'd' }
        ],
        correctAnswer: 'b',
        explanation: 'Basic arithmetic: 2 + 2 = 4'
    }
]

const currentQuestionIndex = ref(0)
const score = ref(0)
const totalAnswered = ref(0)
const selectedAnswers = ref<(string | null)[]>(new Array(sampleMCQs.length).fill(null))

const handleAnswerSelect = (selectedAnswer: string, isCorrect: boolean) => {
    // Only count score if this question wasn't already answered
    if (selectedAnswers.value[currentQuestionIndex.value] === null) {
        if (isCorrect) {
            score.value++
        }
        totalAnswered.value++
    } else if (selectedAnswers.value[currentQuestionIndex.value] !== selectedAnswer) {
        // If changing answer, adjust score
        const previousAnswer = selectedAnswers.value[currentQuestionIndex.value]
        const previousCorrect = previousAnswer === sampleMCQs[currentQuestionIndex.value].correctAnswer

        if (previousCorrect && !isCorrect) {
            score.value--
        } else if (!previousCorrect && isCorrect) {
            score.value++
        }
    }

    selectedAnswers.value[currentQuestionIndex.value] = selectedAnswer === '' ? null : selectedAnswer
}

const nextQuestion = () => {
    if (currentQuestionIndex.value < sampleMCQs.length - 1) {
        currentQuestionIndex.value++
    }
}

const previousQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--
    }
}

const resetQuiz = () => {
    currentQuestionIndex.value = 0
    score.value = 0
    totalAnswered.value = 0
    selectedAnswers.value = new Array(sampleMCQs.length).fill(null)
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'MCQ Demo',
        href: '/mcq-demo',
    },
]
</script>

<template>

    <Head title="MCQ Demo" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-foreground mb-2">MCQ Component Demo</h1>
                <p class="text-muted-foreground">
                    A reusable Multiple Choice Question component built with Vue 3 and Tailwind CSS
                </p>
            </div>
            <!-- Navigation -->
            <div class="flex justify-between mb-6">
                <button @click="previousQuestion" :disabled="currentQuestionIndex === 0"
                    class="px-4 py-2 rounded-md bg-secondary text-secondary-foreground hover:bg-secondary/80 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    ← Previous
                </button>
                <button @click="nextQuestion" :disabled="currentQuestionIndex === sampleMCQs.length - 1"
                    class="px-4 py-2 rounded-md bg-secondary text-secondary-foreground hover:bg-secondary/80 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    Next →
                </button>
            </div>

            <!-- MCQ Component -->
            <div class="mb-8">
                <MCQ :data="sampleMCQs[currentQuestionIndex]" :selected-answer="selectedAnswers[currentQuestionIndex]"
                    variant="card" size="lg" :show-explanation="true" :show-correct-answer="true"
                    @answer-select="handleAnswerSelect" />
            </div>
        </div>
    </AppLayout>
</template>
