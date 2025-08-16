<script setup lang="ts">
import { ref, computed, useSlots } from 'vue';
import { ArrowDown } from 'lucide-vue-next'; // You might need to install lucide-vue-next
import { Button } from '@/components/ui/button'; // Adjust this path
import { useAutoScroll } from './hooks/useAutoScroll'; // Adjust this path
import { cn } from '@/lib/utils'; // You might need to adjust this path

interface ChatMessageListProps {
    smooth?: boolean;
    class?: string;
}

const props = defineProps<ChatMessageListProps>();
const slots = useSlots();

const { scrollRef, isAtBottom, scrollToBottom, disableAutoScroll } = useAutoScroll({
    smooth: props.smooth,
    content: slots.default ? computed(() => slots.default()) : undefined, // Pass reactive content
});

const messageListClass = computed(() => {
    return cn(
        `flex flex-col w-full h-full p-4 overflow-y-auto`,
        props.class,
    );
});
</script>

<template>
    <div class="relative w-full h-full">
        <div :class="messageListClass" ref="scrollRef" @wheel="disableAutoScroll" @touchmove="disableAutoScroll">
            <div class="flex flex-col gap-6">
                <slot />
            </div>
        </div>

        <Transition name="fade">
            <Button v-if="!isAtBottom" @click="scrollToBottom()" size="icon" variant="outline"
                class="absolute bottom-2 left-1/2 transform -translate-x-1/2 inline-flex rounded-full shadow-md"
                aria-label="Scroll to bottom">
                <ArrowDown class="h-4 w-4" />
            </Button>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
