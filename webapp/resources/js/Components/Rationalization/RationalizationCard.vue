<template>
  <div class="bg-white rounded-lg border shadow-sm">
    <!-- Card Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            {{ card.prompt_text }}
          </h3>
          <div class="text-sm text-gray-600 bg-gray-50 rounded px-3 py-2">
            <strong>{{ getCardTypeLabel() }}:</strong> "{{ card.question_text }}"
          </div>
        </div>
        
        <!-- Status indicator -->
        <div class="ml-4 flex-shrink-0">
          <div 
            v-if="card.is_answered"
            class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium"
          >
            ✓ Completed
          </div>
          <div 
            v-else
            class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium"
          >
            Pending
          </div>
        </div>
      </div>
    </div>

    <!-- Card Body -->
    <div class="px-6 py-6">
      <div v-if="!card.is_answered && !readonly" class="space-y-4">
        <!-- Negative anamnesis "Forgot" option -->
        <div v-if="allowForgot" class="mb-4">
          <button
            @click="markAsForgot"
            :disabled="processing"
            class="px-4 py-2 bg-orange-100 text-orange-800 rounded-lg text-sm font-medium hover:bg-orange-200 transition-colors duration-200 disabled:opacity-50"
          >
            I forgot to ask this question
          </button>
          <p class="text-sm text-gray-600 mt-2">
            Or provide your rationale for why you chose not to ask this question:
          </p>
        </div>

        <!-- Rationale input -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Your rationale:
          </label>
          <textarea
            v-model="rationale"
            rows="4"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :placeholder="getRationalePlaceholder()"
            :disabled="processing"
          ></textarea>
          <div class="flex justify-between items-center mt-2">
            <p class="text-xs text-gray-500">
              Minimum {{ getMinLength() }} characters. Current: {{ rationale.length }}
            </p>
            <button
              @click="submitRationale"
              :disabled="!canSubmit || processing"
              class="px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
              :class="canSubmit && !processing
                ? 'bg-blue-600 hover:bg-blue-700' 
                : 'bg-gray-400 cursor-not-allowed'"
            >
              <div v-if="processing" class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                <span>Submitting...</span>
              </div>
              <span v-else>Submit Rationale</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Already answered display -->
      <div v-else-if="card.is_answered" class="space-y-4">
        <div v-if="card.marked_as_forgot" class="p-4 bg-orange-50 border border-orange-200 rounded-lg">
          <div class="flex items-center space-x-2">
            <div class="w-5 h-5 bg-orange-400 rounded-full flex items-center justify-center">
              <span class="text-white text-xs">!</span>
            </div>
            <span class="text-sm font-medium text-orange-800">
              Marked as forgotten
            </span>
          </div>
          <p class="text-sm text-orange-700 mt-2">
            This question was not asked during your session and you indicated you forgot to ask it.
          </p>
        </div>
        
        <div v-else>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Your rationale:
          </label>
          <div class="bg-gray-50 border border-gray-200 rounded-md px-3 py-3 text-sm">
            {{ card.user_rationale }}
          </div>
        </div>

        <!-- Evaluation results (if available) -->
        <div v-if="card.evaluation_summary" class="pt-4 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Evaluation Results</h4>
          
          <!-- Verdict badge -->
          <div class="flex items-center space-x-3 mb-3">
            <div 
              class="px-3 py-1 rounded-full text-sm font-medium"
              :class="getVerdictClasses(card.verdict)"
            >
              {{ getVerdictLabel(card.verdict) }}
            </div>
            <div class="text-sm text-gray-600">
              Score: {{ card.score || 0 }}/10
            </div>
          </div>

          <!-- Summary and feedback -->
          <div class="space-y-3">
            <div>
              <p class="text-sm font-medium text-gray-700">Summary:</p>
              <p class="text-sm text-gray-600">{{ card.evaluation_summary }}</p>
            </div>
            
            <div v-if="card.feedback_why">
              <p class="text-sm font-medium text-gray-700">Feedback:</p>
              <p class="text-sm text-gray-600">{{ card.feedback_why }}</p>
            </div>

            <!-- Citations -->
            <div v-if="card.citations && card.citations.length > 0" class="pt-2">
              <p class="text-sm font-medium text-gray-700 mb-2">Sources:</p>
              <div class="space-y-1">
                <div 
                  v-for="(citation, index) in card.citations" 
                  :key="index"
                  class="text-xs text-blue-600"
                >
                  <a 
                    v-if="citation.url" 
                    :href="citation.url" 
                    target="_blank" 
                    class="hover:underline"
                  >
                    {{ citation.title }} ({{ citation.source }})
                  </a>
                  <span v-else>
                    {{ citation.title }} ({{ citation.source }})
                  </span>
                </div>
              </div>
            </div>

            <!-- Score breakdown -->
            <div v-if="hasScoreBreakdown()" class="pt-2">
              <details class="text-sm">
                <summary class="cursor-pointer font-medium text-gray-700 hover:text-gray-900">
                  Score Breakdown
                </summary>
                <div class="mt-2 space-y-1 text-xs text-gray-600 pl-4">
                  <div>Relevance: {{ card.relevance_score || 0 }}/2</div>
                  <div>Evidence Accuracy: {{ card.evidence_accuracy_score || 0 }}/3</div>
                  <div>Completeness: {{ card.completeness_score || 0 }}/2</div>
                  <div>Safety: {{ card.safety_score || 0 }}/2</div>
                  <div>Prioritization: {{ card.prioritization_score || 0 }}/1</div>
                </div>
              </details>
            </div>
          </div>
        </div>

        <!-- Edit button (if not readonly) -->
        <div v-if="!readonly && !card.marked_as_forgot" class="pt-4">
          <button
            @click="enableEdit"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
          >
            Edit rationale
          </button>
        </div>
      </div>

      <!-- Readonly display -->
      <div v-else-if="readonly" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Rationale provided during session:
          </label>
          <div class="bg-gray-50 border border-gray-200 rounded-md px-3 py-3 text-sm">
            {{ card.user_rationale }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export default {
  name: 'RationalizationCard',
  props: {
    card: {
      type: Object,
      required: true
    },
    allowForgot: {
      type: Boolean,
      default: false
    },
    readonly: {
      type: Boolean,
      default: false
    }
  },
  emits: ['answered'],
  setup(props, { emit }) {
    const processing = ref(false)
    const rationale = ref(props.card.user_rationale || '')

    const canSubmit = computed(() => {
      const minLength = getMinLength()
      return rationale.value.length >= minLength
    })

    const getCardTypeLabel = () => {
      switch (props.card.card_type) {
        case 'asked_question':
          return 'Question Asked'
        case 'negative_anamnesis':
          return 'Expected Question'
        case 'investigation':
          return 'Investigation'
        default:
          return 'Question'
      }
    }

    const getRationalePlaceholder = () => {
      switch (props.card.card_type) {
        case 'asked_question':
          return 'Explain your clinical reasoning for asking this question. Consider: pathophysiology, risk assessment, differential diagnosis, or clinical decision rules...'
        case 'negative_anamnesis':
          return 'Explain why you chose not to ask this question. Was it not relevant to your differential? Did you obtain the information another way?...'
        case 'investigation':
          return 'Explain your rationale for ordering this investigation...'
        default:
          return 'Provide your clinical reasoning...'
      }
    }

    const getMinLength = () => {
      return props.card.card_type === 'negative_anamnesis' ? 20 : 30
    }

    const submitRationale = async () => {
      if (!canSubmit.value || processing.value) return

      processing.value = true

      try {
        const response = await fetch(route('rationalization.answer-card', props.card.id), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            rationale: rationale.value,
            marked_as_forgot: false
          })
        })

        const data = await response.json()

        if (response.ok) {
          emit('answered', data.card)
        } else {
          alert(data.error || 'Failed to submit rationale')
        }
      } catch (error) {
        console.error('Failed to submit rationale:', error)
        alert('An error occurred. Please try again.')
      } finally {
        processing.value = false
      }
    }

    const markAsForgot = async () => {
      if (processing.value) return

      processing.value = true

      try {
        const response = await fetch(route('rationalization.answer-card', props.card.id), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            rationale: null,
            marked_as_forgot: true
          })
        })

        const data = await response.json()

        if (response.ok) {
          emit('answered', data.card)
        } else {
          alert(data.error || 'Failed to mark as forgot')
        }
      } catch (error) {
        console.error('Failed to mark as forgot:', error)
        alert('An error occurred. Please try again.')
      } finally {
        processing.value = false
      }
    }

    const enableEdit = () => {
      // For now, just scroll to top of card
      // Could implement inline editing in the future
    }

    const getVerdictClasses = (verdict) => {
      switch (verdict) {
        case 'correct':
          return 'bg-green-100 text-green-800'
        case 'partially_correct':
          return 'bg-yellow-100 text-yellow-800'
        case 'incorrect':
          return 'bg-red-100 text-red-800'
        default:
          return 'bg-gray-100 text-gray-800'
      }
    }

    const getVerdictLabel = (verdict) => {
      switch (verdict) {
        case 'correct':
          return 'Correct'
        case 'partially_correct':
          return 'Partially Correct'
        case 'incorrect':
          return 'Incorrect'
        default:
          return 'Not Evaluated'
      }
    }

    const hasScoreBreakdown = () => {
      return props.card.relevance_score !== undefined ||
             props.card.evidence_accuracy_score !== undefined ||
             props.card.completeness_score !== undefined ||
             props.card.safety_score !== undefined ||
             props.card.prioritization_score !== undefined
    }

    return {
      processing,
      rationale,
      canSubmit,
      getCardTypeLabel,
      getRationalePlaceholder,
      getMinLength,
      submitRationale,
      markAsForgot,
      enableEdit,
      getVerdictClasses,
      getVerdictLabel,
      hasScoreBreakdown
    }
  }
}
</script>