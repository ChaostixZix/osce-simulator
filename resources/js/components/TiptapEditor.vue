<script setup lang="ts">
import { EditorContent } from '@tiptap/vue-3';
import { useTiptapEditor } from '@/composables/useTiptapEditor';
import TiptapToolbar from '@/components/editor/TiptapToolbar.vue';

interface Props {
  modelValue: string;
  placeholder?: string;
  disabled?: boolean;
  minHeight?: string;
  toolbar?: string[];
}

interface Emits {
  (event: 'update:modelValue', value: string): void;
  (event: 'blur'): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  disabled: false,
  minHeight: '200px',
  toolbar: () => ['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'undo', 'redo'],
});

const emit = defineEmits<Emits>();

const { editor } = useTiptapEditor({
  initialContent: props.modelValue,
  placeholder: props.placeholder,
  disabled: props.disabled,
  minHeight: props.minHeight,
  onUpdate: (html: string) => {
    emit('update:modelValue', html);
  },
  onBlur: () => {
    emit('blur');
  },
});
</script>

<template>
  <div class="border border-input rounded-md">
    <!-- Toolbar -->
    <TiptapToolbar 
      :editor="editor" 
      :toolbar="toolbar" 
      :disabled="disabled" 
    />

    <!-- Editor Content -->
    <div class="relative">
      <EditorContent 
        :editor="editor" 
        :style="{ minHeight: props.minHeight }"
        class="prose prose-sm max-w-none"
      />
    </div>
  </div>
</template>

<style>
/* Custom styles for the editor */
.ProseMirror {
  outline: none;
  padding: 12px;
  min-height: 200px;
}

.ProseMirror p.is-editor-empty:first-child::before {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

.ProseMirror ul, .ProseMirror ol {
  padding-left: 1.5rem;
}

.ProseMirror li {
  margin: 0.25rem 0;
}

.ProseMirror strong {
  font-weight: bold;
}

.ProseMirror em {
  font-style: italic;
}

.ProseMirror u {
  text-decoration: underline;
}

.ProseMirror p {
  margin: 0.75rem 0;
}

.ProseMirror p:first-child {
  margin-top: 0;
}

.ProseMirror p:last-child {
  margin-bottom: 0;
}

/* Focus styles */
.ProseMirror:focus {
  outline: none;
}

/* Placeholder styles */
.ProseMirror[data-placeholder]:empty::before {
  content: attr(data-placeholder);
  color: #9ca3af;
  pointer-events: none;
  opacity: 0.6;
}
</style>