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
  selectedAnswer?: string | null
  onAnswerSelect?: (selectedAnswer: string, isCorrect: boolean) => void
}

const props = withDefaults(defineProps<Props>(), {
  as: 'div',
  variant: 'default',
  size: 'default',
  showExplanation: false,
  showCorrectAnswer: false,
  selectedAnswer: null,
})

const emit = defineEmits<{
  answerSelect: [selectedAnswer: string, isCorrect: boolean]
}>()

const isAnswered = computed(() => {
  return props.selectedAnswer !== null && props.selectedAnswer !== undefined
})

const isCorrect = computed(() => {
  if (!props.selectedAnswer || !props.data.correctAnswer) return false
  return props.selectedAnswer === props.data.correctAnswer
})

const handleOptionSelect = (optionValue: string) => {
  if (isAnswered.value) return

  const correct = optionValue === props.data.correctAnswer
  emit('answerSelect', optionValue, correct)

  if (props.onAnswerSelect) {
    props.onAnswerSelect(optionValue, correct)
  }
}

const resetQuiz = () => {
  emit('answerSelect', '', false) // Emit empty string to reset
}
</script>

<template>
  <Primitive :as="as" :as-child="asChild" :class="cn(mcqVariants({ variant, size }), props.class)">
    <div class="w-full">
      <!-- Question -->
      <div class="mb-4">
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ data.question }}
        </h3>
      </div>

      <!-- Options -->
      <div class="space-y-2 mb-4">
        <div v-for="option in data.options" :key="option.id" @click="handleOptionSelect(option.value)" :class="cn(
          'p-3 rounded-3xl border-2 cursor-pointer transition-all duration-200',
          'hover:border-primary/50 hover:bg-accent/50',
          {
            'border-primary bg-primary/10 text-primary':
              props.selectedAnswer === option.value && isCorrect,
            'border-destructive bg-destructive/10 text-destructive':
              props.selectedAnswer === option.value && !isCorrect && isAnswered,
            'border-input bg-background':
              props.selectedAnswer !== option.value || !isAnswered,
            'pointer-events-none': isAnswered,
          }
        )">
          <div class="flex items-center justify-between">
            <span class="font-medium">{{ option.text }}</span>
            <div v-if="isAnswered" class="flex items-center gap-2">
              <span v-if="props.selectedAnswer === option.value && isCorrect"
                class="text-green-600 dark:text-green-400">
                ✓
              </span>
              <span v-else-if="props.selectedAnswer === option.value && !isCorrect"
                class="text-red-600 dark:text-red-400">
                ✗
              </span>
              <span v-else-if="option.value === data.correctAnswer && showCorrectAnswer"
                class="text-green-600 dark:text-green-400">
                ✓
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Explanation -->
      <div v-if="showExplanation && data.explanation && isAnswered"
        class="mb-4 p-3 rounded-3xl bg-muted/50 border border-border">
        <h4 class="font-medium text-foreground mb-2">Explanation:</h4>
        <p class="text-muted-foreground text-sm">{{ data.explanation }}</p>
      </div>

      <!-- Result Message -->
      <div v-if="isAnswered" class="mb-4 p-3 rounded-3xl text-center" :class="cn(
        'border-2',
        {
          'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200': isCorrect,
          'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200': !isCorrect,
        }
      )">
        <p class="font-medium text-sm">
          {{ isCorrect ? 'Correct!' : 'Incorrect. Try again!' }}
        </p>
      </div>

      <!-- Reset Button -->
      <div v-if="isAnswered" class="flex justify-center">
        <button @click="resetQuiz"
          class="px-4 py-2 rounded-3xl bg-secondary text-secondary-foreground hover:bg-secondary/80 transition-colors text-sm">
          Try Another Question
        </button>
      </div>
    </div>
  </Primitive>
</template>
