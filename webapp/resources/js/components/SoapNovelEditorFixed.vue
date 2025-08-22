<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Editor } from 'novel-vue';

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

const editorContent = ref('');

// Convert modelValue to string for the editor
const getContentString = (value: any): string => {
  if (!value) return '';
  if (typeof value === 'string') return value;
  if (typeof value === 'object') {
    // For now, convert JSON to a simple string representation
    // In production, you'd want proper HTML conversion
    try {
      return JSON.stringify(value);
    } catch {
      return '';
    }
  }
  return String(value);
};

// Initialize content
onMounted(() => {
  editorContent.value = getContentString(props.modelValue);
});

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  const newContent = getContentString(newValue);
  if (newContent !== editorContent.value) {
    editorContent.value = newContent;
  }
});

// Handle content changes
const handleContentChange = (content: string) => {
  editorContent.value = content;
  // For now, emit the raw content
  // In production, you'd convert back to JSON
  emit('update:modelValue', content);
};

// Handle blur
const handleBlur = () => {
  emit('blur');
};
</script>

<template>
  <div class="relative border border-input rounded-md">
    <!-- Debug info -->
    <div class="text-xs text-gray-500 p-2 bg-gray-50">
      Debug: modelValue = {{ typeof modelValue }} ({{ modelValue ? 'has content' : 'empty' }})
    </div>
    
    <Editor
      :defaultValue="editorContent"
      :placeholder="placeholder"
      :editable="!disabled"
      @update="handleContentChange"
      @blur="handleBlur"
      :style="{ minHeight: minHeight }"
      class="prose prose-sm max-w-none"
    />
  </div>
</template>

<style scoped>
:deep(.ProseMirror) {
  padding: 1rem;
  outline: none;
}
</style>