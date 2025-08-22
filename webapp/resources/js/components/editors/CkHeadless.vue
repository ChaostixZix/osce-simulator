<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { Bold as BoldIcon, Italic as ItalicIcon, Underline as UnderlineIcon, List as ListIcon, ListOrdered, Undo as UndoIcon, Redo as RedoIcon } from 'lucide-vue-next';
import { 
  ClassicEditor,
  Bold,
  Italic,
  Underline,
  List,
  Undo,
  Essentials,
  Paragraph
} from 'ckeditor5';

interface Props {
  modelValue: string;
  placeholder?: string;
  disabled?: boolean;
  minHeight?: string;
  toolbar?: string[];
  autofocus?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  disabled: false,
  minHeight: '160px',
  toolbar: () => ['bold', 'italic', 'underline', 'bulletedList', 'numberedList', 'undo', 'redo'],
  autofocus: false
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
  'blur': [];
}>();

const editorContainer = ref<HTMLElement>();
let editor: ClassicEditor | null = null;
const isEditorReady = ref(false);
const isFocused = ref(false);

// Initialize CKEditor
onMounted(async () => {
  if (!editorContainer.value) return;

  try {
    editor = await ClassicEditor.create(editorContainer.value, {
      toolbar: {
        items: []
      },
      placeholder: props.placeholder,
      plugins: [
        Essentials,
        Bold,
        Italic,
        Underline,
        List,
        Undo,
        Paragraph
      ]
    });

    // Set initial content
    if (props.modelValue) {
      editor.setData(props.modelValue);
    }

    // Listen for changes
    editor.model.document.on('change:data', () => {
      const data = editor!.getData();
      emit('update:modelValue', data);
    });

    // Listen for focus/blur events
    editor.editing.view.document.on('focus', () => {
      isFocused.value = true;
    });

    editor.editing.view.document.on('blur', () => {
      isFocused.value = false;
      emit('blur');
    });

    // Set disabled state
    editor.isReadOnly = props.disabled;

    // Handle autofocus
    if (props.autofocus) {
      await nextTick();
      editor.editing.view.focus();
    }

    isEditorReady.value = true;
  } catch (error) {
    console.error('Error initializing CKEditor:', error);
  }
});

// Watch for prop changes
watch(() => props.modelValue, (newValue) => {
  if (editor && editor.getData() !== newValue) {
    editor.setData(newValue || '');
  }
});

watch(() => props.disabled, (newDisabled) => {
  if (editor) {
    editor.isReadOnly = newDisabled;
  }
});

// Cleanup
onUnmounted(() => {
  if (editor) {
    editor.destroy();
  }
});

// Toolbar actions
const executeCommand = (command: string) => {
  if (!editor || !isEditorReady.value) return;

  editor.execute(command);
  editor.editing.view.focus();
};

const canExecuteCommand = (command: string): boolean => {
  if (!editor || !isEditorReady.value) return false;
  return editor.commands.get(command)?.isEnabled || false;
};

const isCommandActive = (command: string): boolean => {
  if (!editor || !isEditorReady.value) return false;
  return editor.commands.get(command)?.value || false;
};
</script>

<template>
  <div class="ck-headless-wrapper">
    <!-- Custom Toolbar -->
    <div v-if="!disabled" class="ck-toolbar border border-b-0 bg-gray-50 px-2 py-1 flex items-center gap-1 rounded-t-md">
      <Button
        v-if="toolbar.includes('bold')"
        variant="ghost"
        size="sm"
        type="button"
        :class="{ 'bg-gray-200': isCommandActive('bold') }"
        @click="executeCommand('bold')"
        :disabled="!canExecuteCommand('bold')"
      >
        <BoldIcon class="h-4 w-4" />
      </Button>

      <Button
        v-if="toolbar.includes('italic')"
        variant="ghost"
        size="sm"
        type="button"
        :class="{ 'bg-gray-200': isCommandActive('italic') }"
        @click="executeCommand('italic')"
        :disabled="!canExecuteCommand('italic')"
      >
        <ItalicIcon class="h-4 w-4" />
      </Button>

      <Button
        v-if="toolbar.includes('underline')"
        variant="ghost"
        size="sm"
        type="button"
        :class="{ 'bg-gray-200': isCommandActive('underline') }"
        @click="executeCommand('underline')"
        :disabled="!canExecuteCommand('underline')"
      >
        <UnderlineIcon class="h-4 w-4" />
      </Button>

      <div class="w-px h-6 bg-gray-300 mx-1" />

      <Button
        v-if="toolbar.includes('bulletedList')"
        variant="ghost"
        size="sm"
        type="button"
        :class="{ 'bg-gray-200': isCommandActive('bulletedList') }"
        @click="executeCommand('bulletedList')"
        :disabled="!canExecuteCommand('bulletedList')"
      >
        <ListIcon class="h-4 w-4" />
      </Button>

      <Button
        v-if="toolbar.includes('numberedList')"
        variant="ghost"
        size="sm"
        type="button"
        :class="{ 'bg-gray-200': isCommandActive('numberedList') }"
        @click="executeCommand('numberedList')"
        :disabled="!canExecuteCommand('numberedList')"
      >
        <ListOrdered class="h-4 w-4" />
      </Button>

      <div class="w-px h-6 bg-gray-300 mx-1" />

      <Button
        v-if="toolbar.includes('undo')"
        variant="ghost"
        size="sm"
        type="button"
        @click="executeCommand('undo')"
        :disabled="!canExecuteCommand('undo')"
      >
        <UndoIcon class="h-4 w-4" />
      </Button>

      <Button
        v-if="toolbar.includes('redo')"
        variant="ghost"
        size="sm"
        type="button"
        @click="executeCommand('redo')"
        :disabled="!canExecuteCommand('redo')"
      >
        <RedoIcon class="h-4 w-4" />
      </Button>
    </div>

    <!-- Editor Container -->
    <div
      ref="editorContainer"
      class="ck-content border"
      :class="{
        'rounded-b-md': !disabled,
        'rounded-md': disabled,
        'border-t-0': !disabled,
        'opacity-60': disabled,
        'ring-2 ring-blue-500': isFocused && !disabled
      }"
      :style="{ minHeight: minHeight }"
    />
  </div>
</template>

<style>
/* CKEditor 5 Content Styles */
.ck-content {
  padding: 12px;
  background: white;
}

.ck-content:focus {
  outline: none;
}

.ck-content p {
  margin-bottom: 8px;
}

.ck-content p:last-child {
  margin-bottom: 0;
}

.ck-content ul,
.ck-content ol {
  margin: 8px 0;
  padding-left: 24px;
}

.ck-content li {
  margin-bottom: 4px;
}

.ck-content strong {
  font-weight: bold;
}

.ck-content em {
  font-style: italic;
}

.ck-content u {
  text-decoration: underline;
}

/* Hide CKEditor's default toolbar and UI elements */
.ck.ck-toolbar {
  display: none !important;
}

.ck.ck-balloon-panel {
  display: none !important;
}

/* Ensure proper height behavior */
.ck-headless-wrapper .ck-content {
  overflow-y: auto;
  resize: vertical;
}
</style>