<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'

const props = defineProps<{
  osceCase: {
    id: number
    title: string
  }
}>()

const form = useForm({
  id: null,
  subjective: '',
  objective: '',
  assessment: '',
  plan: '',
})

const saving = ref(false)
let saveTimeout = null

const save = () => {
  saving.value = true
  if (saveTimeout) {
    clearTimeout(saveTimeout)
  }

  const routePath = route('soap.store', props.osceCase.id)
  form.post(routePath, {
    preserveScroll: true,
    onSuccess: (page) => {
        if (page.props.jetstream.flash?.note) {
            form.id = page.props.jetstream.flash.note.id;
        }
    },
    onFinish: () => {
      saving.value = false
    },
  })
}

const debouncedSave = () => {
  if (saveTimeout) {
    clearTimeout(saveTimeout)
  }
  saveTimeout = setTimeout(save, 10000)
}

watch(() => form.data(), debouncedSave, { deep: true })

const finalize = () => {
  if (form.id) {
    const routePath = route('soap.finalize', form.id)
    form.put(routePath, {
      preserveScroll: true,
    })
  }
}

// TODO: Fetch and display timeline of SOAP notes.
</script>

<template>
  <Dialog>
    <DialogTrigger as-child>
      <Button variant="outline">
        SOAP Notes
      </Button>
    </DialogTrigger>
    <DialogContent class="sm:max-w-[80vw]">
      <DialogHeader>
        <DialogTitle>SOAP Notes for {{ osceCase.title }}</DialogTitle>
        <DialogDescription>
          Subjective, Objective, Assessment, and Plan.
        </DialogDescription>
      </DialogHeader>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <form @submit.prevent="save">
            <div class="grid gap-4 py-4">
              <div class="grid grid-cols-4 items-center gap-4">
                <Label for="subjective" class="text-right">
                  Subjective
                </Label>
                <Textarea id="subjective" v-model="form.subjective" class="col-span-3" @blur="save" />
              </div>
              <div class="grid grid-cols-4 items-center gap-4">
                <Label for="objective" class="text-right">
                  Objective
                </Label>
                <Textarea id="objective" v-model="form.objective" class="col-span-3" @blur="save" />
              </div>
              <div class="grid grid-cols-4 items-center gap-4">
                <Label for="assessment" class="text-right">
                  Assessment
                </Label>
                <Textarea id="assessment" v-model="form.assessment" class="col-span-3" @blur="save" />
              </div>
              <div class="grid grid-cols-4 items-center gap-4">
                <Label for="plan" class="text-right">
                  Plan
                </Label>
                <Textarea id="plan" v-model="form.plan" class="col-span-3" @blur="save" />
              </div>
            </div>
            <DialogFooter>
              <div>
                <span v-if="saving">Saving...</span>
                <span v-else-if="form.isDirty">Unsaved changes</span>
                <span v-else>Saved</span>
              </div>
              <Button type="submit" @click="save">
                Save Draft
              </Button>
              <Button variant="destructive" @click="finalize" :disabled="!form.id">
                Finalize
              </Button>
            </DialogFooter>
          </form>
        </div>
        <div>
          <!-- Timeline will go here -->
          <h3 class="text-lg font-medium">
            History
          </h3>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
