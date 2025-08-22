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
const editorContent = ref('');

// Initialize content from props
onMounted(async () => {
  await nextTick();
  isReady.value = true;
  editorContent.value = getContentAsString(props.modelValue);
  
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

// Watch for external model value changes
watch(() => props.modelValue, (newValue) => {
  const newContent = getContentAsString(newValue);
  if (newContent !== editorContent.value) {
    editorContent.value = newContent;
  }
}, { deep: true });

// Helper to convert any content to string for editor
const getContentAsString = (content: any): string => {
  if (!content) return '';
  if (typeof content === 'string') return content;
  if (typeof content === 'object') {
    // If it's TipTap JSON, we'll let the editor handle it
    try {
      return JSON.stringify(content);
    } catch {
      return '';
    }
  }
  return String(content);
};

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

// Handle content changes - always output HTML string
const handleUpdate = (editor: any) => {
  let htmlContent = '';
  
  if (typeof editor === 'string') {
    htmlContent = editor;
  } else if (editor && typeof editor.getHTML === 'function') {
    htmlContent = editor.getHTML();
  } else if (editor && editor.content) {
    // If it's a TipTap JSON object, try to convert to HTML
    if (typeof editor.content === 'object') {
      // For now, emit empty HTML if we get JSON
      htmlContent = '';
    } else {
      htmlContent = String(editor.content);
    }
  }
  
  // Update local content state
  editorContent.value = htmlContent;
  
  // Always emit HTML string, never JSON
  emit('update:modelValue', htmlContent);
};

// Handle blur
const handleBlur = () => {
  emit('blur');
};

// Convert model value for Novel editor input - must return TipTap JSON format
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
    
    // If it's HTML string (contains < and >), let Novel Vue parse it by returning the HTML directly
    // Novel Vue will automatically convert HTML strings to TipTap JSON internally
    if (props.modelValue.includes('<') && props.modelValue.includes('>')) {
      return props.modelValue; // Novel Vue will convert HTML to TipTap JSON
    } else {
      // Plain text - wrap in paragraph
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
        :content="editorContent"
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