<script setup lang="ts">
import { Avatar, AvatarImage, AvatarFallback } from '../avatar';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface ChatBubbleAvatarProps {
    src?: string;
    fallback?: string;
    class?: string;
}

const props = defineProps<ChatBubbleAvatarProps>();

const avatarClass = computed(() => {
    return cn(
        props.class,
    );
});
</script>

<template>
    <Avatar :class="avatarClass">
        <AvatarImage :src="props.src" alt="Avatar" />
        <AvatarFallback>{{ props.fallback || '' }}</AvatarFallback>
    </Avatar>
</template>
