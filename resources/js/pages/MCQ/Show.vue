<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

interface McqOption {
  id: number
  mcq_question_id: number
  option_text: string
  is_correct: boolean
  order: number
}

interface McqQuestion {
  id: number
  mcq_test_id: number
  question: string
  order: number
  options: McqOption[]
}

interface McqTest {
  id: number
  title: string
  description: string | null
  questions: McqQuestion[]
  created_at: string
  updated_at: string
}

interface Props {
  test: McqTest
}

defineProps<Props>()

const selectedAnswers = ref<Record<number, number>>({})

const selectAnswer = (questionId: number, optionId: number) => {
  selectedAnswers.value[questionId] = optionId
}

const isSelected = (questionId: number, optionId: number) => {
  return selectedAnswers.value[questionId] === optionId
}

const getOptionLabel = (index: number) => {
  return String.fromCharCode(65 + index) // A, B, C, D...
}
</script>

<template>
  <Head :title="test.title" />

  <AppLayout>
    <div class="p-6">
      <div class="mb-6">
        <Link :href="route('mcq.index')" class="text-sm text-blue-600 hover:text-blue-800 mb-2 inline-block">
          ← Back to MCQ Tests
        </Link>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
          {{ test.title }}
        </h1>
        <p v-if="test.description" class="text-gray-600 dark:text-gray-400 mt-2">
          {{ test.description }}
        </p>
        <Badge class="mt-2">
          {{ test.questions.length }} Question{{ test.questions.length !== 1 ? 's' : '' }}
        </Badge>
      </div>

      <div v-if="test.questions.length === 0" class="text-center py-12">
        <p class="text-gray-500 dark:text-gray-400 text-lg">
          No questions available for this test.
        </p>
      </div>

      <div v-else class="space-y-8">
        <Card v-for="(question, questionIndex) in test.questions" :key="question.id">
          <CardHeader>
            <CardTitle class="flex items-start gap-3">
              <span class="text-sm bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">
                Question {{ questionIndex + 1 }}
              </span>
              <span class="flex-1">{{ question.question }}</span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <button
                v-for="(option, optionIndex) in question.options"
                :key="option.id"
                @click="selectAnswer(question.id, option.id)"
                class="w-full text-left p-4 rounded-lg border transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                :class="{
                  'border-blue-500 bg-blue-50 dark:bg-blue-950': isSelected(question.id, option.id),
                  'border-gray-200 dark:border-gray-700': !isSelected(question.id, option.id)
                }"
              >
                <div class="flex items-start gap-3">
                  <span class="text-sm font-medium text-gray-500 dark:text-gray-400 min-w-[24px]">
                    {{ getOptionLabel(optionIndex) }}.
                  </span>
                  <span class="flex-1">{{ option.option_text }}</span>
                  <div 
                    class="w-5 h-5 rounded-full border-2 transition-colors"
                    :class="{
                      'border-blue-500 bg-blue-500': isSelected(question.id, option.id),
                      'border-gray-300 dark:border-gray-600': !isSelected(question.id, option.id)
                    }"
                  >
                    <div 
                      v-if="isSelected(question.id, option.id)"
                      class="w-full h-full rounded-full bg-white scale-50"
                    ></div>
                  </div>
                </div>
              </button>
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-between items-center py-6">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ Object.keys(selectedAnswers).length }} of {{ test.questions.length }} questions answered
          </p>
          <Button 
            disabled 
            class="opacity-50 cursor-not-allowed"
          >
            Submit Test (Coming Soon)
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>