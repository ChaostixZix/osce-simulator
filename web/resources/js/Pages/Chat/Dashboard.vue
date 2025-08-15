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
            💬 AI Medical Chat
          </h1>
        </div>
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-500">
            Chat Mode
          </span>
        </div>
      </div>
    </header>

    <div class="container mx-auto px-6 py-8">
      <div class="max-w-4xl mx-auto">
        <Card class="h-96">
          <CardHeader class="border-b">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-semibold">AI Medical Assistant</h2>
              <Button @click="clearChat" variant="ghost" size="sm">
                Clear Chat
              </Button>
            </div>
          </CardHeader>
          <CardContent class="p-0 h-full flex flex-col">
            <!-- Messages Area -->
            <div 
              ref="messagesContainer"
              class="flex-1 overflow-y-auto p-4 space-y-4"
            >
              <div v-if="messages.length === 0" class="text-center text-gray-500 mt-8">
                <p>👋 Hello! I'm your AI medical assistant.</p>
                <p class="text-sm mt-2">Ask me any medical questions or discuss clinical scenarios.</p>
              </div>
              
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
                    'max-w-lg rounded-lg px-4 py-2',
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
              <div v-if="processing" class="flex justify-start">
                <div class="bg-gray-100 rounded-lg px-4 py-2">
                  <div class="text-sm text-gray-600">AI is thinking...</div>
                </div>
              </div>
            </div>

            <!-- Input Area -->
            <div class="border-t p-4">
              <form @submit.prevent="sendMessage" class="flex space-x-2">
                <input
                  v-model="userInput"
                  type="text"
                  placeholder="Ask me about medical topics, diagnoses, treatments..."
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :disabled="processing"
                />
                <Button 
                  type="submit" 
                  :disabled="!userInput.trim() || processing"
                >
                  Send
                </Button>
              </form>
            </div>
          </CardContent>
        </Card>

        <!-- Quick Suggestions -->
        <div class="mt-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Questions</h3>
          <div class="grid md:grid-cols-2 gap-4">
            <Button
              v-for="suggestion in suggestions"
              :key="suggestion"
              @click="quickMessage(suggestion)"
              variant="outline"
              class="justify-start h-auto p-4 text-left"
            >
              {{ suggestion }}
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardContent from '@/Components/ui/CardContent.vue'

const processing = ref(false)
const userInput = ref('')
const messages = ref([])
const messagesContainer = ref(null)

const suggestions = [
  "What are the symptoms of myocardial infarction?",
  "Explain the pathophysiology of diabetes mellitus",
  "How do you diagnose pneumonia?",
  "What are the side effects of ACE inhibitors?",
  "Describe the treatment protocol for sepsis",
  "What tests would you order for chest pain?"
]

// Note: This is a simplified chat implementation
// In a real application, you would integrate with the same OpenRouter API
const sendMessage = async () => {
  if (!userInput.value.trim() || processing.value) return
  
  const message = userInput.value
  userInput.value = ''
  
  // Add user message
  addUserMessage(message)
  
  processing.value = true
  
  // Simulate AI response (replace with actual API call)
  setTimeout(() => {
    const responses = [
      "That's a great medical question. Let me explain the key concepts...",
      "Based on current medical guidelines, the recommended approach is...",
      "The symptoms you're asking about typically include...",
      "From a clinical perspective, the differential diagnosis would consider...",
      "The pathophysiology involves several key mechanisms..."
    ]
    
    const randomResponse = responses[Math.floor(Math.random() * responses.length)]
    addAIMessage(randomResponse + "\n\nThis is a demo response. In the full implementation, this would connect to the OpenRouter API for actual medical AI responses.")
    processing.value = false
  }, 1500)
}

const quickMessage = (message) => {
  userInput.value = message
  sendMessage()
}

const clearChat = () => {
  messages.value = []
}

const addUserMessage = (content) => {
  messages.value.push({
    id: Date.now(),
    type: 'user',
    content,
    timestamp: new Date()
  })
  scrollToBottom()
}

const addAIMessage = (content) => {
  messages.value.push({
    id: Date.now(),
    type: 'ai',
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

const formatTime = (date) => {
  return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>
