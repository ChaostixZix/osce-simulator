<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue';
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

const editorRef = ref(null);
const uploading = ref(false);
const isReady = ref(false);
const editorKey = ref(0);

// Initialize empty - force clear any demo content
onMounted(async () => {
  await nextTick();
  isReady.value = true;
  
  // Clear any localStorage that might contain demo content
  if (typeof window !== 'undefined') {
    try {
      // Clear any Novel editor storage
      Object.keys(localStorage).forEach(key => {
        if (key.includes('novel') || key.includes('editor')) {
          localStorage.removeItem(key);
        }
      });
    } catch (e) {
      // Ignore localStorage errors
    }
  }
});

// Watch for external model value changes and force re-render
watch(() => props.modelValue, () => {
  editorKey.value++;
}, { deep: true });

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

// Handle content changes
const handleUpdate = (editor: any) => {
  let content = '';
  if (typeof editor === 'string') {
    content = editor;
  } else if (editor && typeof editor.getHTML === 'function') {
    content = editor.getHTML();
  } else if (editor && editor.content) {
    content = editor.content;
  }
  
  emit('update:modelValue', content);
};

// Handle blur
const handleBlur = () => {
  emit('blur');
};

// Convert model value to TipTap JSON format for Novel editor
const getEditorValue = () => {
  if (!props.modelValue) {
    return {
      type: 'doc',
      content: []
    };
  }
  
  if (typeof props.modelValue === 'string') {
    if (props.modelValue.trim() === '') {
      return {
        type: 'doc',
        content: []
      };
    }
    
    // If it's HTML string, let Novel Vue handle it
    // If it's plain text, wrap in paragraph
    if (props.modelValue.includes('<')) {
      return props.modelValue; // HTML string
    } else {
      // Plain text - convert to TipTap structure
      return {
        type: 'doc',
        content: [{
          type: 'paragraph',
          content: [{
            type: 'text',
            text: props.modelValue
          }]
        }]
      };
    }
  }
  
  // If it's already an object (TipTap JSON), use it directly
  if (typeof props.modelValue === 'object') {
    return props.modelValue;
  }
  
  return {
    type: 'doc',
    content: []
  };
};

// Convert model value to display format for debug
const getDisplayValue = () => {
  if (!props.modelValue) return '';
  return String(props.modelValue);
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
    
    <!-- Debug info -->
    <div class="text-xs text-gray-500 p-2 bg-gray-50 border-b">
      Novel Editor - modelValue: {{ typeof modelValue }} | ready: {{ isReady }} | length: {{ getDisplayValue().length }}
    </div>
    
    <div v-if="isReady">
      <Editor
        ref="editorRef"
        :key="`editor-${noteId || 'new'}-${editorKey}`"
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