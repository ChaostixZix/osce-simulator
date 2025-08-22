<script setup lang="ts">
import { ref, onBeforeUnmount, watch, onMounted } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Bold from '@tiptap/extension-bold';
import Italic from '@tiptap/extension-italic';
import Underline from '@tiptap/extension-underline';
import BulletList from '@tiptap/extension-bullet-list';
import OrderedList from '@tiptap/extension-ordered-list';
import ListItem from '@tiptap/extension-list-item';
import History from '@tiptap/extension-history';
import { Button } from '@/components/ui/button';
import { Bold as BoldIcon, Italic as ItalicIcon, Underline as UnderlineIcon, List, ListOrdered, Undo, Redo } from 'lucide-vue-next';

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

const editor = ref<Editor | null>(null);

onMounted(() => {
  editor.value = new Editor({
    content: props.modelValue,
    editable: !props.disabled,
    extensions: [
      StarterKit.configure({
        history: false, // We'll use our own history extension
      }),
      Bold,
      Italic,
      Underline,
      BulletList,
      OrderedList,
      ListItem,
      History,
    ],
    onUpdate: ({ editor }) => {
      emit('update:modelValue', editor.getHTML());
    },
    onBlur: () => {
      emit('blur');
    },
    editorProps: {
      attributes: {
        class: `prose prose-sm sm:prose lg:prose-lg xl:prose-2xl mx-auto focus:outline-none min-h-[${props.minHeight}] p-3 border border-input rounded-md`,
        placeholder: props.placeholder,
      },
    },
  });
});

onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy();
  }
});

watch(() => props.modelValue, (value) => {
  if (editor.value && editor.value.getHTML() !== value) {
    editor.value.commands.setContent(value, false);
  }
});

watch(() => props.disabled, (disabled) => {
  if (editor.value) {
    editor.value.setEditable(!disabled);
  }
});

const toggleBold = () => {
  if (editor.value) {
    editor.value.chain().focus().toggleBold().run();
  }
};

const toggleItalic = () => {
  if (editor.value) {
    editor.value.chain().focus().toggleItalic().run();
  }
};

const toggleUnderline = () => {
  if (editor.value) {
    editor.value.chain().focus().toggleUnderline().run();
  }
};

const toggleBulletList = () => {
  if (editor.value) {
    editor.value.chain().focus().toggleBulletList().run();
  }
};

const toggleOrderedList = () => {
  if (editor.value) {
    editor.value.chain().focus().toggleOrderedList().run();
  }
};

const undo = () => {
  if (editor.value) {
    editor.value.chain().focus().undo().run();
  }
};

const redo = () => {
  if (editor.value) {
    editor.value.chain().focus().redo().run();
  }
};

const isActive = (name: string, attributes?: Record<string, any>) => {
  return editor.value?.isActive(name, attributes) || false;
};

const canUndo = () => {
  return editor.value?.can().undo() || false;
};

const canRedo = () => {
  return editor.value?.can().redo() || false;
};
</script>

<template>
  <div class="border border-input rounded-md">
    <!-- Toolbar -->
    <div v-if="toolbar.length > 0" class="border-b border-input p-2 flex flex-wrap gap-1">
      <Button
        v-if="toolbar.includes('bold')"
        variant="ghost"
        size="sm"
        :class="{ 'bg-accent': isActive('bold') }"
        @click="toggleBold"
        :disabled="disabled"
        type="button"
      >
        <BoldIcon class="h-4 w-4" />
      </Button>
      
      <Button
        v-if="toolbar.includes('italic')"
        variant="ghost"
        size="sm"
        :class="{ 'bg-accent': isActive('italic') }"
        @click="toggleItalic"
        :disabled="disabled"
        type="button"
      >
        <ItalicIcon class="h-4 w-4" />
      </Button>
      
      <Button
        v-if="toolbar.includes('underline')"
        variant="ghost"
        size="sm"
        :class="{ 'bg-accent': isActive('underline') }"
        @click="toggleUnderline"
        :disabled="disabled"
        type="button"
      >
        <UnderlineIcon class="h-4 w-4" />
      </Button>

      <div v-if="toolbar.some(t => ['bulletList', 'orderedList'].includes(t))" class="border-l border-input mx-1"></div>
      
      <Button
        v-if="toolbar.includes('bulletList')"
        variant="ghost"
        size="sm"
        :class="{ 'bg-accent': isActive('bulletList') }"
        @click="toggleBulletList"
        :disabled="disabled"
        type="button"
      >
        <List class="h-4 w-4" />
      </Button>
      
      <Button
        v-if="toolbar.includes('orderedList')"
        variant="ghost"
        size="sm"
        :class="{ 'bg-accent': isActive('orderedList') }"
        @click="toggleOrderedList"
        :disabled="disabled"
        type="button"
      >
        <ListOrdered class="h-4 w-4" />
      </Button>

      <div v-if="toolbar.some(t => ['undo', 'redo'].includes(t))" class="border-l border-input mx-1"></div>
      
      <Button
        v-if="toolbar.includes('undo')"
        variant="ghost"
        size="sm"
        @click="undo"
        :disabled="disabled || !canUndo()"
        type="button"
      >
        <Undo class="h-4 w-4" />
      </Button>
      
      <Button
        v-if="toolbar.includes('redo')"
        variant="ghost"
        size="sm"
        @click="redo"
        :disabled="disabled || !canRedo()"
        type="button"
      >
        <Redo class="h-4 w-4" />
      </Button>
    </div>

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