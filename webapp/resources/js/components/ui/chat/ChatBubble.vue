<script setup lang="ts">
import { computed, useSlots } from 'vue';
import { cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import ChatBubbleAvatar from './ChatBubbleAvatar.vue';
import ChatBubbleMessage from './ChatBubbleMessage.vue';
import ChatBubbleTimestamp from './ChatBubbleTimestamp.vue';
import ChatBubbleAction from './ChatBubbleAction.vue';
import ChatBubbleActionWrapper from './ChatBubbleActionWrapper.vue';

// ChatBubble
const chatBubbleVariant = cva(
    "flex gap-2 max-w-[60%] items-end relative group",
    {
        variants: {
            variant: {
                received: "self-start",
                sent: "self-end flex-row-reverse",
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
    },
);

interface ChatBubbleProps {
    variant?: "received" | "sent";
    layout?: "default" | "ai";
    class?: string;
}

const props = defineProps<ChatBubbleProps>();
const slots = useSlots();

const chatBubbleClass = computed(() => {
    return cn(
        chatBubbleVariant({ variant: props.variant, layout: props.layout, class: props.class }),
        "relative group",
    );
});
</script>

<template>
    <div :class="chatBubbleClass">
        <slot />
    </div>
</template>
