<script setup lang="ts">
import { MCQ } from '@/components/ui/mcq'
import { ref } from 'vue'

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

const handleAnswerSelect = (selectedAnswer: string, isCorrect: boolean) => {
  if (isCorrect) {
    score.value++
  }
  totalAnswered.value++
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
}
</script>

<template>
  <div class="min-h-screen bg-background p-6">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-foreground mb-2">MCQ Component Demo</h1>
        <p class="text-muted-foreground">
          A reusable Multiple Choice Question component built with Vue 3 and Tailwind CSS
        </p>
      </div>

      <!-- Score Display -->
      <div class="mb-6 p-4 bg-muted rounded-lg">
        <div class="flex justify-between items-center">
          <div>
            <span class="font-medium">Question:</span>
            {{ currentQuestionIndex + 1 }} / {{ sampleMCQs.length }}
          </div>
          <div>
            <span class="font-medium">Score:</span>
            {{ score }} / {{ totalAnswered }}
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="flex justify-between mb-6">
        <button
          @click="previousQuestion"
          :disabled="currentQuestionIndex === 0"
          class="px-4 py-2 rounded-md bg-secondary text-secondary-foreground hover:bg-secondary/80 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          ← Previous
        </button>
        <button
          @click="nextQuestion"
          :disabled="currentQuestionIndex === sampleMCQs.length - 1"
          class="px-4 py-2 rounded-md bg-secondary text-secondary-foreground hover:bg-secondary/80 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          Next →
        </button>
      </div>

      <!-- MCQ Component -->
      <div class="mb-8">
        <MCQ
          :data="sampleMCQs[currentQuestionIndex]"
          variant="card"
          size="lg"
          :show-explanation="true"
          :show-correct-answer="true"
          @answer-select="handleAnswerSelect"
        />
      </div>

      <!-- Reset Button -->
      <div class="text-center">
        <button
          @click="resetQuiz"
          class="px-6 py-3 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors"
        >
          Reset Quiz
        </button>
      </div>

      <!-- Usage Examples -->
      <div class="mt-16">
        <h2 class="text-2xl font-bold text-foreground mb-6">Usage Examples</h2>
        
        <div class="grid gap-6 md:grid-cols-2">
          <!-- Basic Usage -->
          <div class="p-6 border border-border rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Basic Usage</h3>
            <pre class="bg-muted p-4 rounded text-sm overflow-x-auto"><code>&lt;MCQ :data="mcqData" /&gt;</code></pre>
          </div>

          <!-- With Variants -->
          <div class="p-6 border border-border rounded-lg">
            <h3 class="text-lg font-semibold mb-4">With Variants</h3>
            <pre class="bg-muted p-4 rounded text-sm overflow-x-auto"><code>&lt;MCQ 
  :data="mcqData"
  variant="card"
  size="lg"
  :show-explanation="true"
/&gt;</code></pre>
          </div>

          <!-- Data Structure -->
          <div class="p-6 border border-border rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Data Structure</h3>
            <pre class="bg-muted p-4 rounded text-sm overflow-x-auto"><code>const mcqData = {
  question: "Your question here?",
  options: [
    { id: 'a', text: 'Option A', value: 'a' },
    { id: 'b', text: 'Option B', value: 'b' },
    { id: 'c', text: 'Option C', value: 'c' },
    { id: 'd', text: 'Option D', value: 'd' }
  ],
  correctAnswer: 'a',
  explanation: 'Optional explanation'
}</code></pre>
          </div>

          <!-- Event Handling -->
          <div class="p-6 border border-border rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Event Handling</h3>
            <pre class="bg-muted p-4 rounded text-sm overflow-x-auto"><code>&lt;MCQ 
  :data="mcqData"
  @answer-select="handleAnswer"
/&gt;</code></pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>