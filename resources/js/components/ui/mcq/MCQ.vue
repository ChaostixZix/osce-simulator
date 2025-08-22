<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'

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

interface Props {
  data: MCQData
  showExplanation?: boolean
  showCorrectAnswer?: boolean
  selectedAnswer?: string | null
  onAnswerSelect?: (selectedAnswer: string, isCorrect: boolean) => void
}

const props = withDefaults(defineProps<Props>(), {
  showExplanation: false,
  showCorrectAnswer: false,
  selectedAnswer: null,
})

const emit = defineEmits<{
  answerSelect: [selectedAnswer: string, isCorrect: boolean]
}>()

const isAnswered = computed(() => {
  return props.selectedAnswer !== null && props.selectedAnswer !== undefined && props.selectedAnswer !== ''
})

const isCorrect = computed(() => {
  if (!props.selectedAnswer || !props.data.correctAnswer) return false
  return props.selectedAnswer === props.data.correctAnswer
})

const isOptionSelected = (optionValue: string) => props.selectedAnswer === optionValue

const getOptionVariant = (optionValue: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
  if (!isAnswered.value) return 'outline'
  if (isOptionSelected(optionValue) && isCorrect.value) return 'secondary'
  if (isOptionSelected(optionValue) && !isCorrect.value) return 'destructive'
  return 'outline'
}

const handleOptionSelect = (optionValue: string) => {
  if (isAnswered.value) return

  const correct = optionValue === props.data.correctAnswer
  emit('answerSelect', optionValue, correct)

  if (props.onAnswerSelect) {
    props.onAnswerSelect(optionValue, correct)
  }
}

const resetQuiz = () => {
  emit('answerSelect', '', false)
}
</script>

<template>
  <Card class="w-full">
    <CardHeader>
      <CardTitle>{{ data.question }}</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-2">
        <Button
          v-for="option in data.options"
          :key="option.id"
          :variant="getOptionVariant(option.value)"
          class="w-full justify-between text-left"
          :disabled="isAnswered"
          @click="handleOptionSelect(option.value)"
        >
          <span class="font-medium">{{ option.text }}</span>
          <template v-if="isAnswered">
            <Badge v-if="isOptionSelected(option.value) && isCorrect" variant="default">Correct</Badge>
            <Badge v-else-if="isOptionSelected(option.value) && !isCorrect" variant="destructive">Incorrect</Badge>
            <Badge v-else-if="option.value === data.correctAnswer && showCorrectAnswer" variant="default">Correct</Badge>
          </template>
        </Button>
      </div>

      <template v-if="showExplanation && data.explanation && isAnswered">
        <Separator class="my-4" />
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Explanation</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-muted-foreground text-sm">{{ data.explanation }}</p>
          </CardContent>
        </Card>
      </template>
    </CardContent>
    <CardFooter v-if="isAnswered" class="justify-center">
      <Button variant="secondary" @click="resetQuiz">Try Another Question</Button>
    </CardFooter>
  </Card>
</template>
