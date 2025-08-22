<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

interface McqTest {
  id: number
  title: string
  description: string | null
  created_at: string
  updated_at: string
}

interface Props {
  tests: McqTest[]
}

defineProps<Props>()
</script>

<template>
  <Head title="MCQ Tests" />

  <AppLayout>
    <div class="p-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
          Multiple Choice Questions
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
          Choose a test category to begin practicing.
        </p>
      </div>

      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <Card 
          v-for="test in tests" 
          :key="test.id"
          class="hover:shadow-lg transition-shadow cursor-pointer"
        >
          <Link :href="route('mcq.show', test.id)">
            <CardHeader>
              <CardTitle class="text-xl">{{ test.title }}</CardTitle>
              <CardDescription v-if="test.description">
                {{ test.description }}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Click to start test
              </p>
            </CardContent>
          </Link>
        </Card>
      </div>

      <div v-if="tests.length === 0" class="text-center py-12">
        <p class="text-gray-500 dark:text-gray-400 text-lg">
          No MCQ tests available at the moment.
        </p>
      </div>
    </div>
  </AppLayout>
</template>