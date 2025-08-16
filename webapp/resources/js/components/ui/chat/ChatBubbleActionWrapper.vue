<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface ChatBubbleActionWrapperProps {
    variant?: "sent" | "received";
    class?: string;
}

const props = defineProps<ChatBubbleActionWrapperProps>();

const wrapperClass = computed(() => {
    return cn(
        "absolute top-1/2 -translate-y-1/2 flex opacity-0 group-hover:opacity-100 transition-opacity duration-200",
        props.variant === "sent"
            ? "-left-1 -translate-x-full flex-row-reverse"
            : "-right-1 translate-x-full",
        props.class,
    );
});
</script>

<template>
    <div :class="wrapperClass">
        <slot />
    </div>
</template>
