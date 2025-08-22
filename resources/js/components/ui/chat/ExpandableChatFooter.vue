<script setup lang="ts">
import { cn } from '@/lib/utils'; // Adjust this path
import { computed } from 'vue';

interface ExpandableChatFooterProps {
    class?: string;
}

const props = defineProps<ExpandableChatFooterProps>();

const footerClass = computed(() => {
    return cn("border-t p-4", props.class);
});
</script>

<template>
    <div :class="footerClass">
        <slot />
    </div>
</template>
