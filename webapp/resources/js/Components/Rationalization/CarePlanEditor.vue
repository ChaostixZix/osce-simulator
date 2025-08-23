<template>
  <div class="bg-white rounded-lg border p-6">
    <!-- Header -->
    <div class="mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">
        Structured Care Plan
      </h3>
      <p class="text-sm text-gray-600">
        Provide a comprehensive care plan using the structured template below. Include specific actions for each section.
      </p>
    </div>

    <!-- Completion Status -->
    <div v-if="hasExistingPlan" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
      <div class="flex items-center space-x-2">
        <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
          <span class="text-white text-xs">✓</span>
        </div>
        <span class="text-sm font-medium text-green-800">
          Care plan submitted successfully
        </span>
      </div>
      <p class="text-sm text-green-700 mt-2">
        You can edit your care plan below if needed.
      </p>
    </div>

    <!-- Auto-save Status -->
    <div class="flex justify-between items-center mb-4">
      <div class="text-sm text-gray-600">
        <span class="font-medium">Care Plan Editor</span>
        <span v-if="autoSaveStatus" class="ml-2" :class="autoSaveStatus.type === 'saving' ? 'text-blue-600' : 'text-green-600'">
          {{ autoSaveStatus.message }}
        </span>
      </div>
      <div class="text-xs text-gray-500">
        {{ content.length }}/{{ minLength }} characters (minimum required)
      </div>
    </div>

    <!-- Template Helper -->
    <div class="mb-4">
      <button
        type="button"
        @click="insertTemplate"
        v-if="!hasContent"
        class="px-4 py-2 bg-blue-100 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-200 transition-colors duration-200"
      >
        📋 Insert Care Plan Template
      </button>
      
      <div v-if="hasContent" class="text-sm text-gray-600">
        <details>
          <summary class="cursor-pointer hover:text-gray-800">Show section guidelines</summary>
          <div class="mt-2 pl-4 space-y-1 text-xs">
            <div><strong>Immediate Actions:</strong> Urgent interventions, monitoring, vital signs</div>
            <div><strong>Diagnostics:</strong> Additional tests, imaging, lab work</div>
            <div><strong>Therapeutics:</strong> Medications, procedures, treatments</div>
            <div><strong>Monitoring:</strong> What to watch for, frequency, parameters</div>
            <div><strong>Disposition:</strong> Admission, discharge, follow-up timing</div>
            <div><strong>Counseling:</strong> Patient/family education, lifestyle advice</div>
            <div><strong>Safety Netting:</strong> Warning signs, when to return, contact info</div>
          </div>
        </details>
      </div>
    </div>

    <!-- Rich Text Editor -->
    <div class="border border-gray-300 rounded-lg overflow-hidden">
      <TiptapEditor
        v-model="content"
        :placeholder="editorPlaceholder"
        :disabled="processing"
        :min-height="'300px'"
        :toolbar="['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'undo', 'redo']"
        @blur="handleBlur"
      />
    </div>

    <!-- Content Validation -->
    <div class="mt-4 flex justify-between items-center">
      <div class="text-sm">
        <span v-if="content.length < minLength" class="text-red-600">
          ⚠️ Minimum {{ minLength }} characters required
        </span>
        <span v-else class="text-green-600">
          ✅ Content length requirement met
        </span>
      </div>
      
      <div class="text-xs text-gray-500">
        Last edited: {{ lastEditedText }}
      </div>
    </div>

    <!-- Form Actions -->
    <div class="pt-6 border-t mt-6">
      <div class="flex justify-between items-center">
        <div class="text-sm text-gray-600">
          <span class="font-medium">Auto-save:</span> 
          Every 10 seconds when editing
        </div>
        
        <button
          @click="submitCarePlan"
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
            {{ hasExistingPlan ? 'Update Care Plan' : 'Submit Care Plan' }}
          </span>
        </button>
      </div>
    </div>

    <!-- Validation Errors -->
    <div v-if="errors.length > 0" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
      <h4 class="text-sm font-medium text-red-800 mb-2">Please fix the following errors:</h4>
      <ul class="text-sm text-red-700 space-y-1">
        <li v-for="error in errors" :key="error">• {{ error }}</li>
      </ul>
    </div>
  </div>
</template>

<script lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import TiptapEditor from '@/Components/TiptapEditor.vue'

export default {
  name: 'CarePlanEditor',
  components: {
    TiptapEditor
  },
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
    const content = ref('')
    const lastEdited = ref(new Date())
    const autoSaveStatus = ref(null)
    const minLength = 100

    let autoSaveInterval
    let lastAutoSave = 0

    const hasExistingPlan = computed(() => {
      return !!props.rationalization.care_plan
    })

    const hasContent = computed(() => {
      return content.value.trim().length > 0
    })

    const canSubmit = computed(() => {
      const textContent = stripHtml(content.value).trim()
      return textContent.length >= minLength && !processing.value
    })

    const lastEditedText = computed(() => {
      return lastEdited.value.toLocaleTimeString()
    })

    const editorPlaceholder = computed(() => {
      return hasContent.value 
        ? "Continue editing your care plan..."
        : "Start typing your care plan, or click 'Insert Care Plan Template' above to get started with a structured format."
    })

    const stripHtml = (html) => {
      const tmp = document.createElement('div')
      tmp.innerHTML = html
      return tmp.textContent || tmp.innerText || ''
    }

    const insertTemplate = () => {
      const template = `
<h3><strong>Immediate Actions</strong></h3>
<ul>
  <li>Vital signs monitoring q15 minutes</li>
  <li>IV access and baseline labs</li>
  <li>[Add specific immediate interventions]</li>
</ul>

<h3><strong>Diagnostics</strong></h3>
<ul>
  <li>Additional laboratory tests: [specify]</li>
  <li>Imaging studies: [specify]</li>
  <li>Specialist consultations: [specify]</li>
</ul>

<h3><strong>Therapeutics</strong></h3>
<ul>
  <li>Medications: [name, dose, route, frequency]</li>
  <li>Procedures: [specify if indicated]</li>
  <li>Supportive care: [specify]</li>
</ul>

<h3><strong>Monitoring</strong></h3>
<ul>
  <li>Clinical parameters to monitor: [specify]</li>
  <li>Frequency of assessments: [specify]</li>
  <li>Response to treatment indicators: [specify]</li>
</ul>

<h3><strong>Disposition</strong></h3>
<ul>
  <li>Admission vs discharge plan: [specify]</li>
  <li>Level of care required: [specify]</li>
  <li>Follow-up arrangements: [specify timeline]</li>
</ul>

<h3><strong>Counseling</strong></h3>
<ul>
  <li>Patient education topics: [specify]</li>
  <li>Family counseling needs: [specify]</li>
  <li>Lifestyle modifications: [specify]</li>
</ul>

<h3><strong>Safety Netting</strong></h3>
<ul>
  <li>Warning signs to watch for: [specify]</li>
  <li>When to return for care: [specify criteria]</li>
  <li>Emergency contact information: [provide]</li>
</ul>
      `.trim()

      content.value = template
      lastEdited.value = new Date()
    }

    const handleBlur = () => {
      lastEdited.value = new Date()
      performAutoSave()
    }

    const performAutoSave = async () => {
      const now = Date.now()
      
      // Avoid too frequent auto-saves
      if (now - lastAutoSave < 5000) return
      
      if (canSubmit.value && hasContent.value) {
        lastAutoSave = now
        
        autoSaveStatus.value = { type: 'saving', message: 'Saving...' }
        
        try {
          await submitCarePlan(true) // Silent auto-save
          autoSaveStatus.value = { type: 'saved', message: 'Saved' }
          
          // Clear status after 3 seconds
          setTimeout(() => {
            autoSaveStatus.value = null
          }, 3000)
        } catch (error) {
          autoSaveStatus.value = { type: 'error', message: 'Save failed' }
          console.warn('Auto-save failed:', error)
        }
      }
    }

    const submitCarePlan = async (isAutoSave = false) => {
      if (!canSubmit.value && !isAutoSave) return

      if (!isAutoSave) {
        processing.value = true
        errors.value = []
      }

      const textContent = stripHtml(content.value).trim()
      
      if (textContent.length < minLength) {
        if (!isAutoSave) {
          errors.value = [`Care plan must be at least ${minLength} characters long`]
          processing.value = false
        }
        return
      }

      try {
        const response = await fetch(route('rationalization.submit-care-plan', props.rationalization.id), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            care_plan: content.value
          })
        })

        const data = await response.json()

        if (response.ok) {
          if (!isAutoSave) {
            emit('submitted', data)
            console.log('Care plan submitted successfully!')
          }
          
          // Clear draft from localStorage
          localStorage.removeItem(`care-plan-draft-${props.rationalization.id}`)
        } else {
          if (!isAutoSave) {
            if (data.errors) {
              errors.value = Object.values(data.errors).flat()
            } else {
              errors.value = [data.message || 'Failed to submit care plan']
            }
          }
        }
      } catch (error) {
        if (!isAutoSave) {
          console.error('Failed to submit care plan:', error)
          errors.value = ['An error occurred. Please try again.']
        }
        throw error // Re-throw for auto-save error handling
      } finally {
        if (!isAutoSave) {
          processing.value = false
        }
      }
    }

    const startAutoSave = () => {
      // Auto-save every 10 seconds
      autoSaveInterval = setInterval(() => {
        if (!processing.value) {
          performAutoSave()
        }
      }, 10000)
    }

    const saveDraft = () => {
      if (hasContent.value) {
        localStorage.setItem(`care-plan-draft-${props.rationalization.id}`, content.value)
      }
    }

    const loadDraft = () => {
      const draft = localStorage.getItem(`care-plan-draft-${props.rationalization.id}`)
      if (draft && !hasExistingPlan.value) {
        content.value = draft
      }
    }

    const loadExistingPlan = () => {
      if (props.rationalization.care_plan) {
        content.value = props.rationalization.care_plan
      }
    }

    // Watch for content changes and save draft
    watch(content, () => {
      lastEdited.value = new Date()
      saveDraft()
      
      // Clear errors when user starts typing
      if (errors.value.length > 0) {
        errors.value = []
      }
    }, { flush: 'post' })

    onMounted(() => {
      loadExistingPlan()
      loadDraft()
      startAutoSave()
    })

    onUnmounted(() => {
      if (autoSaveInterval) {
        clearInterval(autoSaveInterval)
      }
      saveDraft()
    })

    return {
      processing,
      errors,
      content,
      lastEdited,
      autoSaveStatus,
      minLength,
      hasExistingPlan,
      hasContent,
      canSubmit,
      lastEditedText,
      editorPlaceholder,
      insertTemplate,
      handleBlur,
      submitCarePlan
    }
  }
}
</script>

<style scoped>
/* Custom styles for the care plan editor */
:deep(.tiptap) {
  min-height: 300px;
}

:deep(.tiptap h3) {
  @apply text-lg font-semibold text-gray-900 mt-6 mb-3 first:mt-0;
}

:deep(.tiptap ul) {
  @apply list-disc list-inside space-y-2 ml-4;
}

:deep(.tiptap ol) {
  @apply list-decimal list-inside space-y-2 ml-4;
}

:deep(.tiptap li) {
  @apply text-gray-700;
}

:deep(.tiptap strong) {
  @apply font-semibold text-gray-900;
}
</style>