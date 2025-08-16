<script setup lang="ts">
import { Textarea } from '@/components/ui/textarea'; // Adjust this path
import { cn } from '@/lib/utils'; // You might need to adjust this path
import { computed } from 'vue';

interface ChatInputProps {
    class?: string;
    modelValue?: string;
}

const props = defineProps<ChatInputProps>();
const emit = defineEmits(['update:modelValue']);

const chatInputClass = computed(() => {
    return cn(
        "max-h-12 px-4 py-3 bg-background text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 w-full rounded-md flex items-center h-16 resize-none",
        props.class,
    );
});

const handleInput = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLTextAreaElement).value);
};
</script>

<template>
    <Textarea autoComplete="off" name="message" :class="chatInputClass" :value="modelValue" @input="handleInput" />
</template>
