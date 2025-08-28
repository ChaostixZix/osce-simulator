import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { Editor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';

interface UseTiptapEditorOptions {
  initialContent: string;
  placeholder?: string;
  disabled?: boolean;
  minHeight?: string;
  onUpdate?: (html: string) => void;
  onBlur?: () => void;
}

export function useTiptapEditor(options: UseTiptapEditorOptions) {
  const editor = ref<Editor | null>(null);

  const initializeEditor = () => {
    editor.value = new Editor({
      content: options.initialContent,
      editable: !options.disabled,
      extensions: [
        StarterKit.configure({
          // StarterKit includes: Bold, Italic, BulletList, OrderedList, ListItem, History by default
          // We just need to add Underline
        }),
        Underline,
      ],
      onUpdate: ({ editor }) => {
        if (options.onUpdate) {
          options.onUpdate(editor.getHTML());
        }
      },
      onBlur: () => {
        if (options.onBlur) {
          options.onBlur();
        }
      },
      editorProps: {
        attributes: {
          class: `prose prose-sm sm:prose lg:prose-lg xl:prose-2xl mx-auto focus:outline-none min-h-[${options.minHeight || '200px'}] p-3 border border-input rounded-md`,
          placeholder: options.placeholder || '',
        },
      },
    });
  };

  const destroyEditor = () => {
    if (editor.value) {
      editor.value.destroy();
      editor.value = null;
    }
  };

  const updateContent = (content: string) => {
    if (editor.value && editor.value.getHTML() !== content) {
      editor.value.commands.setContent(content, false);
    }
  };

  const setEditable = (editable: boolean) => {
    if (editor.value) {
      editor.value.setEditable(editable);
    }
  };

  // Watch for content changes
  watch(() => options.initialContent, (newContent) => {
    updateContent(newContent);
  });

  // Watch for disabled changes
  watch(() => options.disabled, (disabled) => {
    setEditable(!disabled);
  });

  onMounted(() => {
    initializeEditor();
  });

  onBeforeUnmount(() => {
    destroyEditor();
  });

  return {
    editor,
    updateContent,
    setEditable,
    destroyEditor,
  };
}