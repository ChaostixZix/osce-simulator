<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue';
import { Editor } from 'novel-vue';
import axios from 'axios';
import { toTiptapJSON, emptyDoc } from '@/utils/richtext';

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

const editorRef = ref(null);
const uploading = ref(false);
const isReady = ref(false);
// Keep source of truth minimal; we normalize to JSON for the editor and emit JSON on updates
const initialValue = ref<any>(emptyDoc());

// Initialize content from props
onMounted(async () => {
  await nextTick();
  isReady.value = true;
  initialValue.value = toTiptapJSON(props.modelValue) || emptyDoc();
  // Clear any localStorage that might contain demo content
  if (typeof window !== 'undefined') {
    try {
      Object.keys(localStorage).forEach(key => {
        if (key.includes('novel') || key.includes('editor')) {
          localStorage.removeItem(key);
        }
      });
    } catch {
      // ignore
    }
  }
});

// Watch for external model value changes and re-normalize to JSON
watch(() => props.modelValue, (newValue) => {
  initialValue.value = toTiptapJSON(newValue) || emptyDoc();
}, { deep: true });

// Removed HTML-centric storage; we normalize to JSON only

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

// Handle content changes - always emit TipTap JSON
const handleUpdate = (editor: any) => {
  try {
    if (editor && typeof editor.getJSON === 'function') {
      emit('update:modelValue', editor.getJSON());
      return;
    }
    if (editor && typeof editor.getHTML === 'function') {
      // Fallback: convert editor HTML to TipTap JSON via Novel internals
      // Novel will pass the new state again, but we still guard
      emit('update:modelValue', toTiptapJSON(editor.getHTML()));
      return;
    }
    if (typeof editor === 'string') {
      emit('update:modelValue', toTiptapJSON(editor));
      return;
    }
    if (editor && editor.content) {
      // If raw content provided
      emit('update:modelValue', toTiptapJSON(editor.content));
      return;
    }
  } catch (error) {
    console.warn('Error handling editor update, defaulting to empty doc:', error);
    emit('update:modelValue', emptyDoc());
  }
};

// Handle blur
const handleBlur = () => {
  emit('blur');
};

// Convert model value for Novel editor input - return TipTap JSON format
const getEditorValue = () => {
  return initialValue.value || emptyDoc();
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
    
    <div v-if="isReady">
      <Editor
        ref="editorRef"
        :key="`editor-${noteId || 'new'}`"
        :defaultValue="getEditorValue()"
        completionApi=""
        className="prose prose-sm max-w-none"
        :placeholder="placeholder"
        :editable="!disabled"
        :disableLocalStorage="true"
        @update="handleUpdate"
        @blur="handleBlur"
        :onImageUpload="props.noteId ? handleImageUpload : undefined"
        :style="{ minHeight: minHeight }"
      />
    </div>
    
    <div v-else class="p-4 text-gray-500">
      Loading editor...
    </div>
  </div>
</template>

<style scoped>
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
