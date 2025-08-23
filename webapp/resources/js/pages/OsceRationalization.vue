<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'

interface Props {
  session: {
    id: number
    status: string
    completed_at?: string
    rationalization_completed_at?: string
    clinical_reasoning_score?: number
    total_test_cost?: number
    evaluation_feedback?: string[]
    case: { id: number; title: string; chief_complaint: string }
  }
}

const props = defineProps<Props>()

const completeRationalization = async () => {
  await router.post(`/api/osce/sessions/${props.session.id}/rationalization/complete`, {}, {
    preserveScroll: true,
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Rasionalisasi OSCE" />

    <div class="max-w-5xl mx-auto space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Rasionalisasi OSCE — {{ props.session.case.title }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <p class="text-sm text-muted-foreground">
            Refleksi singkat terhadap tindakan dan keputusan klinis yang diambil selama sesi.
            Lengkapi rasionalisasi untuk membuka hasil penilaian.
          </p>
          <Separator />

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <div class="text-xs text-muted-foreground">Status</div>
              <div class="font-medium">{{ props.session.rationalization_completed_at ? 'Selesai' : 'Belum Selesai' }}</div>
            </div>
            <div>
              <div class="text-xs text-muted-foreground">Skor Reasoning (tindakan)</div>
              <div class="font-medium">{{ props.session.clinical_reasoning_score ?? 0 }}</div>
            </div>
            <div>
              <div class="text-xs text-muted-foreground">Total Biaya Tes</div>
              <div class="font-medium">{{ props.session.total_test_cost ?? 0 }}</div>
            </div>
          </div>

          <div v-if="props.session.evaluation_feedback?.length" class="space-y-2">
            <div class="text-sm font-medium">Umpan balik evaluasi</div>
            <ul class="list-disc pl-5 text-sm space-y-1">
              <li v-for="(f, idx) in props.session.evaluation_feedback" :key="idx">{{ f }}</li>
            </ul>
          </div>

          <div class="pt-4">
            <Button v-if="!props.session.rationalization_completed_at" @click="completeRationalization">
              Selesaikan Rasionalisasi
            </Button>
            <Button v-else variant="outline" @click="router.visit(`/osce/results/${props.session.id}`)">
              Lihat Hasil Penilaian
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

