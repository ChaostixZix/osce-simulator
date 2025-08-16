<script setup lang="ts">
import { computed, useSlots } from 'vue';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import MessageLoading from './MessageLoading.vue';

const chatBubbleMessageVariants = cva("p-4", {
    variants: {
        variant: {
            received:
                "bg-secondary text-secondary-foreground rounded-r-lg rounded-tl-lg",
            sent: "bg-primary text-primary-foreground rounded-l-lg rounded-tr-lg",
        },
        layout: {
            default: "",
            ai: "max-w-full w-full items-center",
        },
    },
    defaultVariants: {
        variant: "received",
        layout: "default",
    },
});

interface ChatBubbleMessageProps extends /* @vue-ignore */ VariantProps<typeof chatBubbleMessageVariants> {
    isLoading?: boolean;
    class?: string;
}

const props = defineProps<ChatBubbleMessageProps>();
const slots = useSlots();

const messageClass = computed(() => {
    return cn(
        chatBubbleMessageVariants({ variant: props.variant, layout: props.layout, class: props.class }),
        "break-words max-w-full whitespace-pre-wrap",
    );
});
</script>

<template>
    <div :class="messageClass">
        <template v-if="isLoading">
            <div class="flex items-center space-x-2">
                <MessageLoading />
            </div>
        </template>
        <template v-else>
            <slot />
        </template>
    </div>
</template>
