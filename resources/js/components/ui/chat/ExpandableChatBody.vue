<script setup lang="ts">
import { cn } from '@/lib/utils'; // Adjust this path
import { computed } from 'vue';

interface ExpandableChatBodyProps {
    class?: string;
}

const props = defineProps<ExpandableChatBodyProps>();

const bodyClass = computed(() => {
    return cn("flex-grow overflow-y-auto", props.class);
});
</script>

<template>
    <div :class="bodyClass">
        <slot />
    </div>
</template>
