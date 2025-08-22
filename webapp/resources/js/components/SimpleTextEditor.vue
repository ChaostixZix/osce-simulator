<script setup lang="ts">
import { ref, watch } from 'vue';

interface Props {
  modelValue: any;
  placeholder?: string;
  disabled?: boolean;
  minHeight?: string;
  noteId?: number | null;
}

interface Emits {
  (event: 'update:modelValue', value: any): void;
  (event: 'blur'): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Start typing...',
  disabled: false,
  minHeight: '200px',
  noteId: null,
});

const emit = defineEmits<Emits>();

const textContent = ref('');

// Convert any input to string for display
const getDisplayValue = (value: any): string => {
  if (!value) return '';
  if (typeof value === 'string') return value;
  if (typeof value === 'object') {
    try {
      return JSON.stringify(value, null, 2);
    } catch {
      return String(value);
    }
  }
  return String(value);
};

// Initialize
textContent.value = getDisplayValue(props.modelValue);

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  textContent.value = getDisplayValue(newValue);
});

// Handle input
const handleInput = (event: Event) => {
  const target = event.target as HTMLTextAreaElement;
  textContent.value = target.value;
  emit('update:modelValue', target.value);
};

// Handle blur
const handleBlur = () => {
  emit('blur');
};
</script>

<template>
  <div class="relative border border-input rounded-md">
    <!-- Debug info -->
    <div class="text-xs text-gray-500 p-2 bg-gray-50 border-b">
      Debug: modelValue type={{ typeof modelValue }}, 
      content={{ modelValue ? 'has content' : 'empty' }},
      length={{ textContent.length }}
    </div>
    
    <textarea
      :value="textContent"
      :placeholder="placeholder"
      :disabled="disabled"
      :style="{ minHeight: minHeight }"
      @input="handleInput"
      @blur="handleBlur"
      class="w-full p-4 resize-none outline-none"
    />
  </div>
</template>

<style scoped>
textarea {
  font-family: inherit;
  line-height: 1.5;
}
</style>