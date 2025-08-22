<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Editor } from 'novel-vue';
import axios from 'axios';

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
const uploading = ref(false);

// Initialize content from props
onMounted(() => {
  editorContent.value = String(props.modelValue || '');
});

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  const newContent = String(newValue || '');
  if (newContent !== editorContent.value) {
    editorContent.value = newContent;
  }
});

// Handle image uploads
const handleImageUpload = async (file: File): Promise<string> => {
  if (!props.noteId) {
    throw new Error('Note ID is required for image uploads');
  }

  uploading.value = true;
  try {
    const formData = new FormData();
    formData.append('image', file);

    const response = await axios.post(
      route('soap.upload-image', props.noteId),
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );

    return response.data.url;
  } catch (error) {
    console.error('Image upload failed:', error);
    throw error;
  } finally {
    uploading.value = false;
  }
};

// Handle content changes - try different event patterns
const handleUpdate = (editor: any) => {
  let content = '';
  if (typeof editor === 'string') {
    content = editor;
  } else if (editor && typeof editor.getHTML === 'function') {
    content = editor.getHTML();
  } else if (editor && editor.content) {
    content = editor.content;
  }
  
  editorContent.value = content;
  emit('update:modelValue', content);
};

// Handle blur
const handleBlur = () => {
  emit('blur');
};
</script>

<template>
  <div class="relative border border-input rounded-md">
    <!-- Upload indicator -->
    <div v-if="uploading" class="absolute inset-0 bg-white/80 flex items-center justify-center rounded-md z-10">
      <div class="flex items-center space-x-2">
        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-900"></div>
        <span class="text-sm text-gray-600">Uploading image...</span>
      </div>
    </div>
    
    <Editor
      :defaultValue="editorContent"
      :placeholder="placeholder"
      :editable="!disabled"
      @update:editor="handleUpdate"
      @editor-update="handleUpdate"
      @update="handleUpdate"
      @change="handleUpdate"
      @blur="handleBlur"
      :onImageUpload="props.noteId ? handleImageUpload : undefined"
      :handleImageUpload="props.noteId ? handleImageUpload : undefined"
      :style="{ minHeight: minHeight }"
      class="prose prose-sm max-w-none [&_.ProseMirror]:p-4 [&_.ProseMirror]:outline-none"
    />
  </div>
</template>

<style scoped>
/* Additional Novel-specific styles */
:deep(.ProseMirror) {
  padding: 1rem !important;
  outline: none !important;
  min-height: v-bind(minHeight);
}

:deep(.ProseMirror img) {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
  margin: 0.5rem 0;
}

:deep(.ProseMirror p.is-editor-empty:first-child::before) {
  color: #9ca3af;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

/* Disable state when disabled */
:deep(.ProseMirror[contenteditable="false"]) {
  opacity: 0.6;
  background-color: #f9fafb;
  cursor: not-allowed;
}
</style>