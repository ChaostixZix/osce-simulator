<script setup lang="ts">
import { computed, watch, ref } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const props = defineProps<{
  modelValue: boolean
  title?: string
  width?: 'md' | 'lg' | 'xl'
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'close'): void
}>();

const open = computed({
  get: () => props.modelValue,
  set: (v: boolean) => emit('update:modelValue', v),
});

const sizeClass = computed(() => {
  switch (props.width) {
    case 'xl':
      return 'sm:max-w-5xl';
    case 'lg':
      return 'sm:max-w-3xl';
    case 'md':
    default:
      return 'sm:max-w-xl';
  }
});

const justClosed = ref(false);
watch(open, (v) => {
  if (!v) {
    justClosed.value = true;
    emit('close');
    setTimeout(() => (justClosed.value = false), 0);
  }
});
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent :class="sizeClass">
      <DialogHeader v-if="title">
        <DialogTitle>{{ title }}</DialogTitle>
      </DialogHeader>
      <div class="overflow-y-auto max-h-[70vh]">
        <slot />
      </div>
      <div class="mt-4 flex justify-end gap-2">
        <slot name="actions" />
      </div>
    </DialogContent>
  </Dialog>
  <!-- Emits close on overlay click/ESC via Dialog; focus trapping handled by ui/dialog (reka-ui) -->
</template>

