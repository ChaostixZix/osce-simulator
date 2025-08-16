<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { ref, computed } from 'vue'
import { cn } from '@/lib/utils'
import { Primitive, type PrimitiveProps } from 'reka-ui'
import { type MCQVariants, mcqVariants } from '.'

interface MCQOption {
  id: string
  text: string
  value: string
}

interface MCQData {
  question: string
  options: MCQOption[]
  correctAnswer?: string
  explanation?: string
}

interface Props extends PrimitiveProps {
  data: MCQData
  variant?: MCQVariants['variant']
  size?: MCQVariants['size']
  showExplanation?: boolean
  showCorrectAnswer?: boolean
  class?: HTMLAttributes['class']
  onAnswerSelect?: (selectedAnswer: string, isCorrect: boolean) => void
}

const props = withDefaults(defineProps<Props>(), {
  as: 'div',
  variant: 'default',
  size: 'default',
  showExplanation: false,
  showCorrectAnswer: false,
})

const emit = defineEmits<{
  answerSelect: [selectedAnswer: string, isCorrect: boolean]
}>()

const selectedAnswer = ref<string | null>(null)
const isAnswered = ref(false)

const isCorrect = computed(() => {
  if (!selectedAnswer.value || !props.data.correctAnswer) return false
  return selectedAnswer.value === props.data.correctAnswer
})

const handleOptionSelect = (optionValue: string) => {
  if (isAnswered.value) return
  
  selectedAnswer.value = optionValue
  isAnswered.value = true
  
  const correct = optionValue === props.data.correctAnswer
  emit('answerSelect', optionValue, correct)
  
  if (props.onAnswerSelect) {
    props.onAnswerSelect(optionValue, correct)
  }
}

const resetQuiz = () => {
  selectedAnswer.value = null
  isAnswered.value = false
}
</script>

<template>
  <Primitive
    :as="as"
    :as-child="asChild"
    :class="cn(mcqVariants({ variant, size }), props.class)"
  >
    <div class="w-full max-w-2xl mx-auto">
      <!-- Question -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold text-foreground mb-2">
          {{ data.question }}
        </h3>
      </div>

      <!-- Options -->
      <div class="space-y-3 mb-6">
        <div
          v-for="option in data.options"
          :key="option.id"
          @click="handleOptionSelect(option.value)"
          :class="cn(
            'p-4 rounded-lg border-2 cursor-pointer transition-all duration-200',
            'hover:border-primary/50 hover:bg-accent/50',
            {
              'border-primary bg-primary/10 text-primary': 
                selectedAnswer === option.value && isCorrect,
              'border-destructive bg-destructive/10 text-destructive': 
                selectedAnswer === option.value && !isCorrect && isAnswered,
              'border-input bg-background': 
                selectedAnswer !== option.value || !isAnswered,
              'pointer-events-none': isAnswered,
            }
          )"
        >
          <div class="flex items-center justify-between">
            <span class="font-medium">{{ option.text }}</span>
            <div v-if="isAnswered" class="flex items-center gap-2">
              <span
                v-if="selectedAnswer === option.value && isCorrect"
                class="text-green-600 dark:text-green-400"
              >
                ✓
              </span>
              <span
                v-else-if="selectedAnswer === option.value && !isCorrect"
                class="text-red-600 dark:text-red-400"
              >
                ✗
              </span>
              <span
                v-else-if="option.value === data.correctAnswer && showCorrectAnswer"
                class="text-green-600 dark:text-green-400"
              >
                ✓
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Explanation -->
      <div
        v-if="showExplanation && data.explanation && isAnswered"
        class="mb-6 p-4 rounded-lg bg-muted/50 border border-border"
      >
        <h4 class="font-medium text-foreground mb-2">Explanation:</h4>
        <p class="text-muted-foreground">{{ data.explanation }}</p>
      </div>

      <!-- Result Message -->
      <div
        v-if="isAnswered"
        class="mb-6 p-4 rounded-lg text-center"
        :class="cn(
          'border-2',
          {
            'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200': isCorrect,
            'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200': !isCorrect,
          }
        )"
      >
        <p class="font-medium">
          {{ isCorrect ? 'Correct!' : 'Incorrect. Try again!' }}
        </p>
      </div>

      <!-- Reset Button -->
      <div
        v-if="isAnswered"
        class="flex justify-center"
      >
        <button
          @click="resetQuiz"
          class="px-4 py-2 rounded-md bg-secondary text-secondary-foreground hover:bg-secondary/80 transition-colors"
        >
          Try Another Question
        </button>
      </div>
    </div>
  </Primitive>
</template>