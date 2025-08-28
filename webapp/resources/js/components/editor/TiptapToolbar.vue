<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Bold as BoldIcon, Italic as ItalicIcon, Underline as UnderlineIcon, List, ListOrdered, Undo, Redo } from 'lucide-vue-next';
import type { Editor } from '@tiptap/vue-3';

interface Props {
    editor: Editor | null;
    toolbar: string[];
    disabled?: boolean;
}

const props = defineProps<Props>();

const toggleBold = () => {
    if (props.editor) {
        props.editor.chain().focus().toggleBold().run();
    }
};

const toggleItalic = () => {
    if (props.editor) {
        props.editor.chain().focus().toggleItalic().run();
    }
};

const toggleUnderline = () => {
    if (props.editor) {
        props.editor.chain().focus().toggleUnderline().run();
    }
};

const toggleBulletList = () => {
    if (props.editor) {
        props.editor.chain().focus().toggleBulletList().run();
    }
};

const toggleOrderedList = () => {
    if (props.editor) {
        props.editor.chain().focus().toggleOrderedList().run();
    }
};

const undo = () => {
    if (props.editor) {
        props.editor.chain().focus().undo().run();
    }
};

const redo = () => {
    if (props.editor) {
        props.editor.chain().focus().redo().run();
    }
};

const isActive = (name: string, attributes?: Record<string, any>) => {
    return props.editor?.isActive(name, attributes) || false;
};

const canUndo = () => {
    return props.editor?.can().undo() || false;
};

const canRedo = () => {
    return props.editor?.can().redo() || false;
};
</script>

<template>
    <div v-if="toolbar.length > 0" class="border-b border-input p-2 flex flex-wrap gap-1">
        <!-- Bold Button -->
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
        
        <!-- Italic Button -->
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
        
        <!-- Underline Button -->
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

        <!-- Separator for List Items -->
        <div v-if="toolbar.some(t => ['bulletList', 'orderedList'].includes(t))" class="border-l border-input mx-1"></div>
        
        <!-- Bullet List Button -->
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
        
        <!-- Ordered List Button -->
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

        <!-- Separator for Undo/Redo -->
        <div v-if="toolbar.some(t => ['undo', 'redo'].includes(t))" class="border-l border-input mx-1"></div>
        
        <!-- Undo Button -->
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
        
        <!-- Redo Button -->
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
</template>