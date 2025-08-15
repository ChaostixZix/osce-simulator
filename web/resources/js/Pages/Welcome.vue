<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="container mx-auto px-4 py-16">
      <!-- Header -->
      <div class="text-center mb-16">
        <h1 class="text-5xl font-bold text-gray-900 mb-4">
          🏥 Medical Training System
        </h1>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          AI-powered medical education platform with structured OSCE training and intelligent patient simulation
        </p>
      </div>

      <!-- Feature Cards -->
      <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto mb-16">
        <!-- OSCE Mode Card -->
        <Card class="p-8 hover:shadow-lg transition-shadow">
          <CardHeader>
            <div class="flex items-center space-x-3 mb-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <span class="text-2xl">🏥</span>
              </div>
              <div>
                <h3 class="text-2xl font-semibold text-gray-900">OSCE Mode</h3>
                <p class="text-gray-600">Structured Clinical Examination</p>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <p class="text-gray-700 mb-6">
              Practice with AI-powered patient simulations, get real-time feedback, and improve your clinical skills through structured scenarios.
            </p>
            <ul class="space-y-2 mb-6">
              <li class="flex items-center text-sm text-gray-600">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Interactive patient simulation
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Automated performance tracking
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Multiple medical cases
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Detailed scoring and feedback
              </li>
            </ul>
            <Button 
              @click="$inertia.visit('/osce')" 
              class="w-full"
            >
              Start OSCE Training
            </Button>
          </CardContent>
        </Card>

        <!-- Chat Mode Card -->
        <Card class="p-8 hover:shadow-lg transition-shadow">
          <CardHeader>
            <div class="flex items-center space-x-3 mb-4">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <span class="text-2xl">💬</span>
              </div>
              <div>
                <h3 class="text-2xl font-semibold text-gray-900">Chat Mode</h3>
                <p class="text-gray-600">AI Medical Consultation</p>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <p class="text-gray-700 mb-6">
              Have conversations with advanced AI models about medical topics, get explanations, and explore different medical scenarios.
            </p>
            <ul class="space-y-2 mb-6">
              <li class="flex items-center text-sm text-gray-600">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Natural language interaction
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Medical knowledge base
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Conversation memory
              </li>
              <li class="flex items-center text-sm text-gray-600">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Learning assistance
              </li>
            </ul>
            <Button 
              @click="$inertia.visit('/chat')" 
              variant="secondary"
              class="w-full"
            >
              Start Chat Session
            </Button>
          </CardContent>
        </Card>
      </div>

      <!-- System Info -->
      <div class="text-center text-sm text-gray-500">
        <p>Laravel v{{ laravelVersion }} • PHP v{{ phpVersion }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import Button from '@/Components/ui/Button.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardContent from '@/Components/ui/CardContent.vue'

defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
  laravelVersion: String,
  phpVersion: String,
})
</script>
