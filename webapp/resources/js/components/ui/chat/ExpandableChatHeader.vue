<script setup lang="ts">
import { cn } from '@/lib/utils'; // Adjust this path
import { computed } from 'vue';

interface ExpandableChatHeaderProps {
    class?: string;
}

const props = defineProps<ExpandableChatHeaderProps>();

const headerClass = computed(() => {
    return cn("flex items-center justify-between p-4 border-b", props.class);
});
</script>

<template>
    <div :class="headerClass">
        <slot />
    </div>
</template>
