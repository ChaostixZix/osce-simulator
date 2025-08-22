<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue';
import { generateJSON, generateHTML } from '@tiptap/html';
import { Editor } from 'novel-vue';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
import axios from 'axios';

interface Props {
  modelValue: any; // TipTap JSON object or HTML string
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

const uploading = ref(false);
const initialContent = ref('');

// TipTap extensions configuration for JSON conversion
const extensions = [
  StarterKit,
  Underline,
  Image.configure({
    HTMLAttributes: {
      class: 'rounded-lg border border-gray-300 max-w-full h-auto',
    },
  }),
  Link.configure({
    openOnClick: false,
    HTMLAttributes: {
      class: 'text-blue-600 underline cursor-pointer',
    },
  }),
];

// Convert TipTap JSON to HTML for Novel editor
const convertJsonToHtml = (json: any): string => {
  if (!json) return '';
  if (typeof json === 'string') {
    // If it's already HTML, return as-is
    return json;
  }
  try {
    return generateHTML(json, extensions);
  } catch (error) {
    console.warn('Failed to convert JSON to HTML:', error);
    return '';
  }
};

// Convert HTML back to TipTap JSON
const convertHtmlToJson = (html: string): any => {
  if (!html || html.trim() === '' || html === '<p></p>') return null;
  try {
    return generateJSON(html, extensions);
  } catch (error) {
    console.warn('Failed to convert HTML to JSON:', error);
    return null;
  }
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

// Initialize content from props
onMounted(() => {
  initialContent.value = convertJsonToHtml(props.modelValue);
});

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  const newHtml = convertJsonToHtml(newValue);
  if (newHtml !== initialContent.value) {
    initialContent.value = newHtml;
  }
});

// Handle content changes from Novel editor
const handleUpdate = (content: any) => {
  // Novel editor might pass different content format
  const html = typeof content === 'string' ? content : content.getHTML?.() || '';
  const json = convertHtmlToJson(html);
  emit('update:modelValue', json);
};

// Handle blur events
const handleBlur = () => {
  emit('blur');
};

// Computed style for min height
const editorStyle = computed(() => ({
  minHeight: props.minHeight,
}));
</script>

<template>
  <div class="relative border border-input rounded-md">
    <Editor
      :initial-content="initialContent"
      :placeholder="placeholder"
      :editable="!disabled"
      :style="editorStyle"
      :onUpdate="handleUpdate"
      :onBlur="handleBlur"
      :handleImageUpload="props.noteId ? handleImageUpload : undefined"
      class="prose prose-sm max-w-none"
    />
    
    <!-- Upload indicator -->
    <div v-if="uploading" class="absolute inset-0 bg-white/80 flex items-center justify-center rounded-md">
      <div class="flex items-center space-x-2">
        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-900"></div>
        <span class="text-sm text-gray-600">Uploading image...</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom styles for the SOAP Novel editor */
:deep(.ProseMirror) {
  padding: 1rem;
  outline: none;
  min-height: v-bind(minHeight);
}

:deep(.ProseMirror img) {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
  margin: 0.5rem 0;
}

:deep(.ProseMirror a) {
  color: #2563eb;
  text-decoration: underline;
}

:deep(.ProseMirror strong) {
  font-weight: 600;
}

:deep(.ProseMirror em) {
  font-style: italic;
}

:deep(.ProseMirror u) {
  text-decoration: underline;
}

/* Placeholder styling */
:deep(.ProseMirror p.is-editor-empty:first-child::before) {
  color: #9ca3af;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

/* List styling */
:deep(.ProseMirror ul),
:deep(.ProseMirror ol) {
  padding-left: 1.5rem;
  margin: 0.5rem 0;
}

:deep(.ProseMirror li) {
  margin: 0.25rem 0;
}

/* Heading styling */
:deep(.ProseMirror h1) {
  font-size: 2rem;
  font-weight: 700;
  margin: 1rem 0 0.5rem 0;
  line-height: 1.2;
}

:deep(.ProseMirror h2) {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0.875rem 0 0.5rem 0;
  line-height: 1.3;
}

:deep(.ProseMirror h3) {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0.75rem 0 0.5rem 0;
  line-height: 1.4;
}

/* Blockquote styling */
:deep(.ProseMirror blockquote) {
  border-left: 4px solid #e5e7eb;
  padding-left: 1rem;
  margin: 1rem 0;
  font-style: italic;
  color: #6b7280;
}

/* Code styling */
:deep(.ProseMirror code) {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
  font-size: 0.875em;
}

:deep(.ProseMirror pre) {
  background-color: #f3f4f6;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 1rem 0;
}

:deep(.ProseMirror pre code) {
  background: none;
  padding: 0;
  font-size: 1em;
}

/* Focus styles */
:deep(.ProseMirror:focus) {
  outline: none;
}

/* Disabled state */
.editor-disabled {
  opacity: 0.6;
  pointer-events: none;
}
</style>