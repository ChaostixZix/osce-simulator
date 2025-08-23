<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              OSCE Rationalization Review
            </h1>
            <p class="mt-1 text-sm text-gray-600">
              Case: {{ session.osce_case.title }}
            </p>
          </div>
          
          <div class="flex items-center space-x-4">
            <!-- Progress indicator -->
            <div class="text-right">
              <div class="text-sm font-medium text-gray-900">
                {{ progress.cards_completed }}/{{ progress.cards_total }} cards completed
              </div>
              <div class="w-32 bg-gray-200 rounded-full h-2 mt-1">
                <div 
                  class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: progress.cards_percentage + '%' }"
                ></div>
              </div>
            </div>

            <!-- Results unlock status -->
            <div class="flex items-center space-x-2">
              <div 
                v-if="canUnlockResults"
                class="px-3 py-2 bg-green-100 text-green-800 rounded-lg text-sm font-medium"
              >
                ✓ Ready to view results
              </div>
              <div 
                v-else
                class="px-3 py-2 bg-yellow-100 text-yellow-800 rounded-lg text-sm font-medium"
              >
                Complete all sections to unlock results
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Main content -->
        <div class="lg:col-span-3 space-y-8">
          
          <!-- Instructions -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-blue-900 mb-2">
              Clinical Reasoning Assessment
            </h2>
            <p class="text-blue-800 mb-4">
              As a strict, objective hospital consultant, I will evaluate your clinical reasoning with evidence-based standards. 
              Complete all sections below to unlock your session results.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
              <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                <span>Answer all rationalization cards</span>
              </div>
              <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                <span>Submit primary & differential diagnoses</span>
              </div>
              <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                <span>Provide structured care plan</span>
              </div>
            </div>
          </div>

          <!-- Anamnesis Rationalization Cards -->
          <div class="space-y-6">
            <h2 class="text-2xl font-bold text-gray-900">
              1. Anamnesis Rationalization
            </h2>
            
            <!-- Asked Questions -->
            <div v-if="askedQuestionCards.length > 0">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">
                Questions You Asked
              </h3>
              <div class="space-y-4">
                <RationalizationCard
                  v-for="card in askedQuestionCards"
                  :key="card.id"
                  :card="card"
                  @answered="handleCardAnswered"
                />
              </div>
            </div>

            <!-- Negative Anamnesis -->
            <div v-if="negativeAnamnesisCards.length > 0" class="pt-6">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">
                Expected Questions Not Asked
              </h3>
              <div class="space-y-4">
                <RationalizationCard
                  v-for="card in negativeAnamnesisCards"
                  :key="card.id"
                  :card="card"
                  :allow-forgot="true"
                  @answered="handleCardAnswered"
                />
              </div>
            </div>

            <!-- Investigation Cards -->
            <div v-if="investigationCards.length > 0" class="pt-6">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">
                Investigation Rationales
              </h3>
              <div class="space-y-4">
                <RationalizationCard
                  v-for="card in investigationCards"
                  :key="card.id"
                  :card="card"
                  :readonly="true"
                  @answered="handleCardAnswered"
                />
              </div>
            </div>
          </div>

          <!-- Diagnosis & Plan Section -->
          <div class="space-y-6">
            <h2 class="text-2xl font-bold text-gray-900">
              2. Diagnosis & Care Plan
            </h2>
            
            <DiagnosisModal 
              :rationalization="rationalization"
              @submitted="handleDiagnosesSubmitted"
            />
            
            <CarePlanEditor
              :rationalization="rationalization" 
              @submitted="handleCarePlanSubmitted"
            />
          </div>

          <!-- Complete Button -->
          <div class="pt-8">
            <button
              @click="completeRationalization"
              :disabled="!canComplete || processing"
              class="w-full py-4 px-6 text-white font-semibold rounded-lg transition-colors duration-200"
              :class="canComplete && !processing
                ? 'bg-green-600 hover:bg-green-700' 
                : 'bg-gray-400 cursor-not-allowed'
              "
            >
              <div v-if="processing" class="flex items-center justify-center space-x-2">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                <span>Processing...</span>
              </div>
              <span v-else>
                {{ canComplete ? 'Complete Rationalization & View Results' : 'Complete All Sections Above' }}
              </span>
            </button>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg border p-6 sticky top-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              Progress Overview
            </h3>
            
            <!-- Completion Checklist -->
            <div class="space-y-3">
              <div class="flex items-center space-x-3">
                <div 
                  class="w-6 h-6 rounded-full flex items-center justify-center text-sm font-medium"
                  :class="progress.cards_completed === progress.cards_total 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-600'"
                >
                  {{ progress.cards_completed === progress.cards_total ? '✓' : progress.cards_completed }}
                </div>
                <span class="text-sm">
                  Rationalization cards ({{ progress.cards_completed }}/{{ progress.cards_total }})
                </span>
              </div>

              <div class="flex items-center space-x-3">
                <div 
                  class="w-6 h-6 rounded-full flex items-center justify-center text-sm font-medium"
                  :class="progress.has_primary_diagnosis 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-600'"
                >
                  {{ progress.has_primary_diagnosis ? '✓' : '○' }}
                </div>
                <span class="text-sm">Primary diagnosis</span>
              </div>

              <div class="flex items-center space-x-3">
                <div 
                  class="w-6 h-6 rounded-full flex items-center justify-center text-sm font-medium"
                  :class="progress.differential_count > 0 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-600'"
                >
                  {{ progress.differential_count > 0 ? '✓' : '○' }}
                </div>
                <span class="text-sm">
                  Differential diagnoses ({{ progress.differential_count }})
                </span>
              </div>

              <div class="flex items-center space-x-3">
                <div 
                  class="w-6 h-6 rounded-full flex items-center justify-center text-sm font-medium"
                  :class="progress.has_care_plan 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-600'"
                >
                  {{ progress.has_care_plan ? '✓' : '○' }}
                </div>
                <span class="text-sm">Care plan</span>
              </div>
            </div>

            <!-- Session Info -->
            <div class="pt-6 mt-6 border-t">
              <h4 class="text-sm font-semibold text-gray-900 mb-2">Session Details</h4>
              <div class="text-sm text-gray-600 space-y-1">
                <div>Duration: {{ session.duration_minutes }} minutes</div>
                <div>Completed: {{ formatDate(session.completed_at) }}</div>
                <div v-if="session.clinical_reasoning_score">
                  Reasoning Score: {{ session.clinical_reasoning_score }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import RationalizationCard from '@/Components/Rationalization/RationalizationCard.vue'
import DiagnosisModal from '@/Components/Rationalization/DiagnosisModal.vue'
import CarePlanEditor from '@/Components/Rationalization/CarePlanEditor.vue'

export default {
  name: 'RationalizationShow',
  components: {
    RationalizationCard,
    DiagnosisModal,
    CarePlanEditor
  },
  props: {
    session: {
      type: Object,
      required: true
    },
    rationalization: {
      type: Object,
      required: true
    },
    progress: {
      type: Object,
      required: true
    },
    canUnlockResults: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const processing = ref(false)
    const localProgress = ref(props.progress)
    const localRationalization = ref(props.rationalization)

    const askedQuestionCards = computed(() => {
      return props.rationalization.cards?.filter(card => card.card_type === 'asked_question') || []
    })

    const negativeAnamnesisCards = computed(() => {
      return props.rationalization.cards?.filter(card => card.card_type === 'negative_anamnesis') || []
    })

    const investigationCards = computed(() => {
      return props.rationalization.cards?.filter(card => card.card_type === 'investigation') || []
    })

    const canComplete = computed(() => {
      return localProgress.value.can_unlock
    })

    const handleCardAnswered = async (card) => {
      // Update local state
      const cardIndex = props.rationalization.cards.findIndex(c => c.id === card.id)
      if (cardIndex !== -1) {
        props.rationalization.cards[cardIndex] = card
      }
      
      // Refresh progress
      await refreshProgress()
    }

    const handleDiagnosesSubmitted = async () => {
      await refreshProgress()
    }

    const handleCarePlanSubmitted = async () => {
      await refreshProgress()
    }

    const refreshProgress = async () => {
      try {
        const response = await fetch(route('rationalization.progress', props.rationalization.id))
        const data = await response.json()
        localProgress.value = data.progress
      } catch (error) {
        console.error('Failed to refresh progress:', error)
      }
    }

    const completeRationalization = async () => {
      if (!canComplete.value || processing.value) return

      processing.value = true
      
      try {
        const response = await fetch(route('rationalization.complete', props.rationalization.id), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })

        const data = await response.json()
        
        if (response.ok) {
          // Redirect to results page
          router.visit(route('osce.results.show', props.session.id))
        } else {
          alert(data.error || 'Failed to complete rationalization')
        }
      } catch (error) {
        console.error('Failed to complete rationalization:', error)
        alert('An error occurred. Please try again.')
      } finally {
        processing.value = false
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleString()
    }

    onMounted(() => {
      // Auto-refresh progress every 30 seconds
      const interval = setInterval(refreshProgress, 30000)
      
      // Clean up interval on unmount
      return () => clearInterval(interval)
    })

    return {
      processing,
      localProgress,
      askedQuestionCards,
      negativeAnamnesisCards,
      investigationCards,
      canComplete,
      handleCardAnswered,
      handleDiagnosesSubmitted,
      handleCarePlanSubmitted,
      completeRationalization,
      formatDate
    }
  }
}
</script>