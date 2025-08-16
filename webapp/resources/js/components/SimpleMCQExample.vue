<script setup lang="ts">
import { MCQ } from '@/components/ui/mcq'

// Example data in the format you requested
const mcqData = {
  question: "What is the capital of France?",
  options: [
    { id: 'a', text: 'London', value: 'a' },
    { id: 'b', text: 'Berlin', value: 'b' },
    { id: 'c', text: 'Paris', value: 'c' },
    { id: 'd', text: 'Madrid', value: 'd' }
  ],
  correctAnswer: 'c',
  explanation: 'Paris is the capital and largest city of France.'
}

const handleAnswer = (selectedAnswer: string, isCorrect: boolean) => {
  console.log(`Selected: ${selectedAnswer}, Correct: ${isCorrect}`)
}
</script>

<template>
  <div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Simple MCQ Example</h2>
    
    <!-- This is the exact usage pattern you requested -->
    <MCQ 
      :data="mcqData"
      @answer-select="handleAnswer"
    />
  </div>
</template>
