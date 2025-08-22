<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface ChatBubbleTimestampProps {
    timestamp: string;
    class?: string;
}

const props = defineProps<ChatBubbleTimestampProps>();

const timestampClass = computed(() => {
    return cn("text-xs mt-2 text-right", props.class);
});
</script>

<template>
    <div :class="timestampClass">
        {{ timestamp }}
    </div>
</template>
