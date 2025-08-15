<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button 
            @click="$inertia.visit('/')" 
            variant="ghost" 
            size="sm"
          >
            ← Back to Home
          </Button>
          <h1 class="text-2xl font-bold text-gray-900">
            🏥 OSCE Medical Training
          </h1>
        </div>
        <div class="flex items-center space-x-4">
          <div v-if="currentCase" class="text-sm text-gray-600">
            Case: {{ currentCase }}
          </div>
          <div v-if="sessionDuration" class="text-sm text-gray-500">
            {{ formatDuration(sessionDuration) }}
          </div>
        </div>
      </div>
    </header>

    <div class="container mx-auto px-6 py-8">
      <div class="grid lg:grid-cols-3 gap-8">
        <!-- Main Content Area -->
        <div class="lg:col-span-2">
          <!-- Case Selection -->
          <Card v-if="osceState.awaitingCaseSelection" class="mb-6">
            <CardHeader>
              <h2 class="text-xl font-semibold">Available Cases</h2>
              <p class="text-gray-600">Select a case to begin your OSCE training</p>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div 
                  v-for="case_ in availableCases" 
                  :key="case_.id"
                  class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-colors"
                  @click="selectCase(case_.id)"
                >
                  <div class="flex items-start justify-between">
                    <div>
                      <h3 class="font-medium text-gray-900">{{ case_.title }}</h3>
                      <p class="text-sm text-gray-600 mt-1">{{ case_.description }}</p>
                      <span class="inline-block mt-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">
                        Case ID: {{ case_.id }}
                      </span>
                    </div>
                    <Button size="sm">Select</Button>
                  </div>
                </div>
              </div>
              
              <div class="mt-6 pt-6 border-t">
                <Button @click="startOSCE" :disabled="loading" class="w-full">
                  {{ loading ? 'Loading...' : 'Refresh Cases' }}
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Chat Interface -->
          <Card v-if="!osceState.awaitingCaseSelection" class="h-96">
            <CardHeader class="border-b">
              <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Patient Interaction</h2>
                <div class="flex space-x-2">
                  <Button @click="endCase" variant="outline" size="sm">
                    End Case
                  </Button>
                  <Button @click="resetSession" variant="ghost" size="sm">
                    Reset
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent class="p-0 h-full flex flex-col">
              <!-- Messages Area -->
              <div 
                ref="messagesContainer"
                class="flex-1 overflow-y-auto p-4 space-y-4"
              >
                <div 
                  v-for="message in messages" 
                  :key="message.id"
                  :class="[
                    'flex',
                    message.type === 'user' ? 'justify-end' : 'justify-start'
                  ]"
                >
                  <div 
                    :class="[
                      'max-w-sm rounded-lg px-4 py-2',
                      message.type === 'user' 
                        ? 'bg-blue-500 text-white' 
                        : 'bg-gray-100 text-gray-900'
                    ]"
                  >
                    <div class="text-sm whitespace-pre-wrap">{{ message.content }}</div>
                    <div class="text-xs opacity-70 mt-1">
                      {{ formatTime(message.timestamp) }}
                    </div>
                  </div>
                </div>
                
                <!-- Loading indicator -->
                <div v-if="processingInput" class="flex justify-start">
                  <div class="bg-gray-100 rounded-lg px-4 py-2">
                    <div class="text-sm text-gray-600">Patient is thinking...</div>
                  </div>
                </div>
              </div>

              <!-- Input Area -->
              <div class="border-t p-4">
                <form @submit.prevent="sendMessage" class="flex space-x-2">
                  <input
                    v-model="userInput"
                    type="text"
                    placeholder="Type your message to the patient..."
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :disabled="processingInput"
                  />
                  <Button 
                    type="submit" 
                    :disabled="!userInput.trim() || processingInput"
                  >
                    Send
                  </Button>
                </form>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Current Case Info -->
          <Card v-if="selectedCaseData">
            <CardHeader>
              <h3 class="font-semibold">Case Information</h3>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div>
                  <span class="text-sm font-medium text-gray-700">Title:</span>
                  <p class="text-sm text-gray-900">{{ selectedCaseData.title }}</p>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-700">Chief Complaint:</span>
                  <p class="text-sm text-gray-900">{{ selectedCaseData.data?.chiefComplaint }}</p>
                </div>
                <div v-if="selectedCaseData.data?.patientInfo">
                  <span class="text-sm font-medium text-gray-700">Patient:</span>
                  <p class="text-sm text-gray-900">
                    {{ selectedCaseData.data.patientInfo.name }}, 
                    {{ selectedCaseData.data.patientInfo.age }} years old
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Quick Commands -->
          <Card v-if="!osceState.awaitingCaseSelection">
            <CardHeader>
              <h3 class="font-semibold">Quick Commands</h3>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <Button 
                  @click="quickMessage('Can you tell me more about your symptoms?')"
                  variant="outline" 
                  size="sm" 
                  class="w-full justify-start"
                >
                  Ask about symptoms
                </Button>
                <Button 
                  @click="quickMessage('I would like to check your vital signs')"
                  variant="outline" 
                  size="sm" 
                  class="w-full justify-start"
                >
                  Check vital signs
                </Button>
                <Button 
                  @click="quickMessage('I need to perform a physical examination')"
                  variant="outline" 
                  size="sm" 
                  class="w-full justify-start"
                >
                  Physical examination
                </Button>
                <Button 
                  @click="quickMessage('I would like to order some tests')"
                  variant="outline" 
                  size="sm" 
                  class="w-full justify-start"
                >
                  Order tests
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Session Stats -->
          <Card v-if="sessionDuration">
            <CardHeader>
              <h3 class="font-semibold">Session Stats</h3>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Duration:</span>
                  <span>{{ formatDuration(sessionDuration) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Messages:</span>
                  <span>{{ messages.length }}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardContent from '@/Components/ui/CardContent.vue'

// Reactive state
const loading = ref(false)
const processingInput = ref(false)
const userInput = ref('')
const messages = ref([])
const availableCases = ref([])
const selectedCaseData = ref(null)
const currentCase = ref(null)
const sessionDuration = ref(0)
const messagesContainer = ref(null)

const osceState = reactive({
  awaitingCaseSelection: true,
  showingResults: false,
  currentCase: null
})

// Initialize OSCE
const startOSCE = async () => {
  loading.value = true
  try {
    const response = await fetch('/osce/start', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      availableCases.value = data.cases
      Object.assign(osceState, data.state)
      
      if (data.message) {
        addSystemMessage(data.message)
      }
    } else {
      addSystemMessage(`Error: ${data.message}`)
    }
  } catch (error) {
    console.error('Failed to start OSCE:', error)
    addSystemMessage('Failed to start OSCE system. Please try again.')
  } finally {
    loading.value = false
  }
}

// Select a case
const selectCase = async (caseId) => {
  loading.value = true
  try {
    const response = await fetch('/osce/select-case', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({ caseId })
    })
    
    const data = await response.json()
    
    if (data.success) {
      selectedCaseData.value = data.case
      currentCase.value = caseId
      Object.assign(osceState, data.state)
      messages.value = []
      
      if (data.message) {
        addSystemMessage(data.message)
      }
    } else {
      addSystemMessage(`Error: ${data.message}`)
    }
  } catch (error) {
    console.error('Failed to select case:', error)
    addSystemMessage('Failed to select case. Please try again.')
  } finally {
    loading.value = false
  }
}

// Send message
const sendMessage = async () => {
  if (!userInput.value.trim() || processingInput.value) return
  
  const message = userInput.value
  userInput.value = ''
  
  // Add user message
  addUserMessage(message)
  
  processingInput.value = true
  try {
    const response = await fetch('/osce/process-input', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({ input: message })
    })
    
    const data = await response.json()
    
    if (data.success) {
      addPatientMessage(data.response)
      
      // Update session state
      if (data.state) {
        Object.assign(osceState, data.state)
        sessionDuration.value = data.state.sessionDuration
      }
    } else {
      addSystemMessage(`Error: ${data.message}`)
    }
  } catch (error) {
    console.error('Failed to process input:', error)
    addSystemMessage('Failed to send message. Please try again.')
  } finally {
    processingInput.value = false
  }
}

// Quick message helper
const quickMessage = (message) => {
  userInput.value = message
  sendMessage()
}

// End case
const endCase = async () => {
  try {
    const response = await fetch('/osce/end-case', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      addSystemMessage(data.message)
      Object.assign(osceState, data.state)
    } else {
      addSystemMessage(`Error: ${data.message}`)
    }
  } catch (error) {
    console.error('Failed to end case:', error)
  }
}

// Reset session
const resetSession = async () => {
  try {
    const response = await fetch('/osce/reset', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      messages.value = []
      selectedCaseData.value = null
      currentCase.value = null
      sessionDuration.value = 0
      Object.assign(osceState, data.state)
      
      // Restart OSCE
      startOSCE()
    }
  } catch (error) {
    console.error('Failed to reset session:', error)
  }
}

// Message helpers
const addUserMessage = (content) => {
  messages.value.push({
    id: Date.now(),
    type: 'user',
    content,
    timestamp: new Date()
  })
  scrollToBottom()
}

const addPatientMessage = (content) => {
  messages.value.push({
    id: Date.now(),
    type: 'patient',
    content,
    timestamp: new Date()
  })
  scrollToBottom()
}

const addSystemMessage = (content) => {
  messages.value.push({
    id: Date.now(),
    type: 'system',
    content,
    timestamp: new Date()
  })
  scrollToBottom()
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

// Utility functions
const formatDuration = (ms) => {
  const minutes = Math.floor(ms / 60000)
  const seconds = Math.floor((ms % 60000) / 1000)
  return `${minutes}:${seconds.toString().padStart(2, '0')}`
}

const formatTime = (date) => {
  return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

// Initialize on mount
onMounted(() => {
  startOSCE()
})
</script>