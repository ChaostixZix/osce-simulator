<script setup lang="ts">
import { ref, computed, defineComponent, h } from 'vue';
import { X, MessageCircle } from 'lucide-vue-next';
import { cn } from '@/lib/utils'; // Adjust this path
import { Button } from '@/components/ui/button'; // Adjust this path

export type ChatPosition = "bottom-right" | "bottom-left";
export type ChatSize = "sm" | "md" | "lg" | "xl" | "full";

const chatConfig = {
    dimensions: {
        sm: "sm:max-w-sm sm:max-h-[500px]",
        md: "sm:max-w-md sm:max-h-[600px]",
        lg: "sm:max-w-lg sm:max-h-[700px]",
        xl: "sm:max-w-xl sm:max-h-[800px]",
        full: "sm:w-full sm:h-full",
    },
    positions: {
        "bottom-right": "bottom-5 right-5",
        "bottom-left": "bottom-5 left-5",
    },
    chatPositions: {
        "bottom-right": "sm:bottom-[calc(100%+10px)] sm:right-0",
        "bottom-left": "sm:bottom-[calc(100%+10px)] sm:left-0",
    },
    states: {
        open: "pointer-events-auto opacity-100 visible scale-100 translate-y-0",
        closed:
            "pointer-events-none opacity-0 invisible scale-100 sm:translate-y-5",
    },
};

interface ExpandableChatProps {
    position?: ChatPosition;
    size?: ChatSize;
    icon?: any; // React.ReactNode equivalent in Vue
    class?: string;
}

const props = defineProps<ExpandableChatProps>();

const isOpen = ref(false);
const chatRef = ref<HTMLElement | null>(null);

const toggleChat = () => {
    isOpen.value = !isOpen.value;
};

const expandableChatClass = computed(() => {
    return cn(`fixed ${chatConfig.positions[props.position || "bottom-right"]} z-50`, props.class);
});

const chatContainerClass = computed(() => {
    return cn(
        "flex flex-col bg-background border sm:rounded-lg shadow-md overflow-hidden transition-all duration-250 ease-out sm:absolute sm:w-[90vw] sm:h-[80vh] fixed inset-0 w-full h-full sm:inset-auto",
        chatConfig.chatPositions[props.position || "bottom-right"],
        chatConfig.dimensions[props.size || "md"],
        isOpen.value ? chatConfig.states.open : chatConfig.states.closed,
        props.class,
    );
});

// ExpandableChatToggle
interface ExpandableChatToggleProps {
    icon?: any;
    isOpen: boolean;
    toggleChat: () => void;
    class?: string;
}

const ExpandableChatToggle = defineComponent({
    props: ['icon', 'isOpen', 'toggleChat', 'class'],
    setup(props) {
        const buttonClass = computed(() => {
            return cn(
                "w-14 h-14 rounded-full shadow-md flex items-center justify-center hover:shadow-lg hover:shadow-black/30 transition-all duration-300",
                props.class,
            );
        });
        return () => h(Button, {
            variant: "default",
            onClick: props.toggleChat,
            class: buttonClass.value,
        },
            props.isOpen
                ? h(X, { class: "h-6 w-6" })
                : props.icon || h(MessageCircle, { class: "h-6 w-6" })
        );
    }
});

</script>

<template>
    <div :class="expandableChatClass">
        <div :class="chatContainerClass" ref="chatRef">
            <slot />
            <Button variant="ghost" size="icon" class="absolute top-2 right-2 sm:hidden" @click="toggleChat">
                <X class="h-4 w-4" />
            </Button>
        </div>
        <ExpandableChatToggle :icon="icon" :isOpen="isOpen" :toggleChat="toggleChat" />
    </div>
</template>
