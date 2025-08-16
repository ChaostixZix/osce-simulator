<script setup lang="ts">
import { Button } from '../button';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface ChatBubbleActionProps {
    icon: any;
    onClick?: (e: MouseEvent) => void;
    class?: string;
    variant?: "default" | "destructive" | "outline" | "secondary" | "ghost" | "link";
    size?: "default" | "sm" | "lg" | "icon";
}

const props = defineProps<ChatBubbleActionProps>();

const buttonClass = computed(() => {
    return cn(
        props.class,
    );
});
</script>

<template>
    <Button :variant="props.variant" :size="props.size" :class="buttonClass" @click="props.onClick">
        <component :is="props.icon" />
    </Button>
</template>
