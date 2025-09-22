<template>
  <div class="bg-white rounded-lg border p-6">
    <!-- Header -->
    <div class="mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">
        Diagnosis Submission
      </h3>
      <p class="text-sm text-gray-600">
        Provide your primary diagnosis and at least one differential diagnosis with reasoning for each.
      </p>
    </div>

    <!-- Completion Status -->
    <div v-if="hasExistingDiagnoses" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
      <div class="flex items-center space-x-2">
        <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
          <span class="text-white text-xs">✓</span>
        </div>
        <span class="text-sm font-medium text-green-800">
          Diagnoses submitted successfully
        </span>
      </div>
      <p class="text-sm text-green-700 mt-2">
        You can edit your diagnoses below if needed.
      </p>
    </div>

    <!-- Form -->
    <form @submit.prevent="submitDiagnoses" class="space-y-6">
      
      <!-- Primary Diagnosis -->
      <div>
        <h4 class="text-md font-semibold text-gray-900 mb-4">Primary Diagnosis</h4>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Diagnosis Name *
            </label>
            <input
              v-model="form.primary_diagnosis"
              type="text"
              required
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="e.g., Acute myocardial infarction, Pneumonia, etc."
              :disabled="processing"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Clinical Reasoning *
            </label>
            <textarea
              v-model="form.primary_reasoning"
              rows="4"
              required
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y"
              placeholder="Explain your reasoning for this primary diagnosis. Include supporting evidence from history, examination, and investigations..."
              :disabled="processing"
            ></textarea>
            <div class="flex justify-between items-center mt-2">
              <p class="text-xs text-gray-500">
                Minimum 50 characters required
              </p>
              <p class="text-xs" :class="form.primary_reasoning.length >= 50 ? 'text-green-600' : 'text-gray-500'">
                {{ form.primary_reasoning.length }}/50
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Differential Diagnoses -->
      <div>
        <div class="flex justify-between items-center mb-4">
          <h4 class="text-md font-semibold text-gray-900">
            Differential Diagnoses
          </h4>
          <button
            type="button"
            @click="addDifferential"
            :disabled="processing"
            class="px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            + Add Differential
          </button>
        </div>

        <div v-if="form.differential_diagnoses.length === 0" class="text-sm text-gray-500 italic mb-4">
          Click "Add Differential" to add at least one differential diagnosis.
        </div>

        <div class="space-y-6">
          <div 
            v-for="(differential, index) in form.differential_diagnoses" 
            :key="index"
            class="p-4 border border-gray-200 rounded-lg"
          >
            <div class="flex justify-between items-center mb-4">
              <h5 class="text-sm font-medium text-gray-900">
                Differential #{{ index + 1 }}
              </h5>
              <button
                type="button"
                @click="removeDifferential(index)"
                :disabled="processing || form.differential_diagnoses.length === 1"
                class="text-red-600 hover:text-red-800 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Remove
              </button>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Diagnosis Name *
                </label>
                <input
                  v-model="differential.diagnosis"
                  type="text"
                  required
                  class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter differential diagnosis"
                  :disabled="processing"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Reasoning *
                </label>
                <textarea
                  v-model="differential.reasoning"
                  rows="3"
                  required
                  class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y"
                  placeholder="Explain why this is a plausible differential diagnosis and what would support or refute it..."
                  :disabled="processing"
                ></textarea>
                <div class="flex justify-between items-center mt-2">
                  <p class="text-xs text-gray-500">
                    Minimum 30 characters required
                  </p>
                  <p class="text-xs" :class="differential.reasoning.length >= 30 ? 'text-green-600' : 'text-gray-500'">
                    {{ differential.reasoning.length }}/30
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="pt-6 border-t">
        <div class="flex justify-between items-center">
          <div class="text-sm text-gray-600">
            <span class="font-medium">Required:</span> 
            Primary diagnosis + {{ form.differential_diagnoses.length }} differential{{ form.differential_diagnoses.length !== 1 ? 's' : '' }}
          </div>
          
          <button
            type="submit"
            :disabled="!canSubmit || processing"
            class="px-6 py-3 text-white font-semibold rounded-lg transition-colors duration-200"
            :class="canSubmit && !processing
              ? 'bg-green-600 hover:bg-green-700' 
              : 'bg-gray-400 cursor-not-allowed'"
          >
            <div v-if="processing" class="flex items-center space-x-2">
              <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
              <span>Submitting...</span>
            </div>
            <span v-else>
              {{ hasExistingDiagnoses ? 'Update Diagnoses' : 'Submit Diagnoses' }}
            </span>
          </button>
        </div>
      </div>

      <!-- Validation Errors -->
      <div v-if="errors.length > 0" class="p-4 bg-red-50 border border-red-200 rounded-lg">
        <h4 class="text-sm font-medium text-red-800 mb-2">Please fix the following errors:</h4>
        <ul class="text-sm text-red-700 space-y-1">
          <li v-for="error in errors" :key="error">• {{ error }}</li>
        </ul>
      </div>
    </form>
  </div>
</template>

<script lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

export default {
  name: 'DiagnosisModal',
  props: {
    rationalization: {
      type: Object,
      required: true
    }
  },
  emits: ['submitted'],
  setup(props, { emit }) {
    const processing = ref(false)
    const errors = ref([])
    
    const form = ref({
      primary_diagnosis: '',
      primary_reasoning: '',
      differential_diagnoses: []
    })

    const hasExistingDiagnoses = computed(() => {
      return props.rationalization.diagnosis_entries?.length > 0 || 
             props.rationalization.primary_diagnosis
    })

    const canSubmit = computed(() => {
      if (!form.value.primary_diagnosis || !form.value.primary_reasoning) {
        return false
      }

      if (form.value.primary_reasoning.length < 50) {
        return false
      }

      if (form.value.differential_diagnoses.length === 0) {
        return false
      }

      return form.value.differential_diagnoses.every(diff => 
        diff.diagnosis && 
        diff.reasoning && 
        diff.reasoning.length >= 30
      )
    })

    const addDifferential = () => {
      form.value.differential_diagnoses.push({
        diagnosis: '',
        reasoning: ''
      })
    }

    const removeDifferential = (index) => {
      if (form.value.differential_diagnoses.length > 1) {
        form.value.differential_diagnoses.splice(index, 1)
      }
    }

    const validateForm = () => {
      errors.value = []

      if (!form.value.primary_diagnosis) {
        errors.value.push('Primary diagnosis is required')
      }

      if (!form.value.primary_reasoning) {
        errors.value.push('Primary diagnosis reasoning is required')
      } else if (form.value.primary_reasoning.length < 50) {
        errors.value.push('Primary diagnosis reasoning must be at least 50 characters')
      }

      if (form.value.differential_diagnoses.length === 0) {
        errors.value.push('At least one differential diagnosis is required')
      }

      form.value.differential_diagnoses.forEach((diff, index) => {
        if (!diff.diagnosis) {
          errors.value.push(`Differential diagnosis #${index + 1} name is required`)
        }
        if (!diff.reasoning) {
          errors.value.push(`Differential diagnosis #${index + 1} reasoning is required`)
        } else if (diff.reasoning.length < 30) {
          errors.value.push(`Differential diagnosis #${index + 1} reasoning must be at least 30 characters`)
        }
      })

      return errors.value.length === 0
    }

    const submitDiagnoses = async () => {
      if (!validateForm() || processing.value) return

      processing.value = true

      try {
        const response = await fetch(route('rationalization.submit-diagnoses', props.rationalization.id), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            primary_diagnosis: form.value.primary_diagnosis,
            primary_reasoning: form.value.primary_reasoning,
            differential_diagnoses: form.value.differential_diagnoses
          })
        })

        const data = await response.json()

        if (response.ok) {
          emit('submitted', data)
          // Show success message
          const successMessage = hasExistingDiagnoses.value ? 'Diagnoses updated successfully!' : 'Diagnoses submitted successfully!'
          console.log(successMessage)
        } else {
          if (data.errors) {
            errors.value = Object.values(data.errors).flat()
          } else {
            errors.value = [data.message || 'Failed to submit diagnoses']
          }
        }
      } catch (error) {
        console.error('Failed to submit diagnoses:', error)
        errors.value = ['An error occurred. Please try again.']
      } finally {
        processing.value = false
      }
    }

    const loadExistingDiagnoses = () => {
      // Load from rationalization data if available
      if (props.rationalization.primary_diagnosis) {
        form.value.primary_diagnosis = props.rationalization.primary_diagnosis
        form.value.primary_reasoning = props.rationalization.primary_diagnosis_reasoning || ''
      }

      // Load differential diagnoses from diagnosis entries
      const differentials = props.rationalization.diagnosis_entries?.filter(entry => 
        entry.diagnosis_type === 'differential'
      ) || []

      if (differentials.length > 0) {
        form.value.differential_diagnoses = differentials.map(entry => ({
          diagnosis: entry.diagnosis_name,
          reasoning: entry.reasoning
        }))
      } else if (form.value.differential_diagnoses.length === 0) {
        // Add at least one empty differential
        addDifferential()
      }
    }

    // Auto-save draft every 10 seconds
    let autoSaveInterval
    const startAutoSave = () => {
      autoSaveInterval = setInterval(() => {
        if (canSubmit.value && !processing.value) {
          // Could implement draft auto-save here
          localStorage.setItem(`diagnosis-draft-${props.rationalization.id}`, JSON.stringify(form.value))
        }
      }, 10000)
    }

    const loadDraft = () => {
      const draft = localStorage.getItem(`diagnosis-draft-${props.rationalization.id}`)
      if (draft && !hasExistingDiagnoses.value) {
        try {
          const parsedDraft = JSON.parse(draft)
          form.value = { ...form.value, ...parsedDraft }
        } catch (e) {
          console.warn('Failed to load diagnosis draft:', e)
        }
      }
    }

    onMounted(() => {
      loadExistingDiagnoses()
      loadDraft()
      startAutoSave()

      // Clean up interval on unmount
      return () => {
        if (autoSaveInterval) {
          clearInterval(autoSaveInterval)
        }
      }
    })

    // Watch for changes and clear errors when user starts typing
    watch(() => form.value, () => {
      if (errors.value.length > 0) {
        errors.value = []
      }
    }, { deep: true })

    return {
      processing,
      errors,
      form,
      hasExistingDiagnoses,
      canSubmit,
      addDifferential,
      removeDifferential,
      submitDiagnoses
    }
  }
}
</script>

<style scoped>
/* Add any custom styles here */
.resize-y {
  resize: vertical;
  min-height: 80px;
}
</style>