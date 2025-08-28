<script setup lang="ts">
import { ref, computed, nextTick, watch } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Send, User, Bot } from 'lucide-vue-next';

interface ChatMessage {
    id: number;
    message: string;
    sender: 'user' | 'ai';
    timestamp: string;
    metadata?: any;
}

interface Props {
    messages: ChatMessage[];
    isSessionActive: boolean;
    isLoading?: boolean;
}

interface Emits {
    (event: 'send-message', message: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const newMessage = ref('');
const messagesContainer = ref<HTMLElement | null>(null);

const canSendMessage = computed(() => 
    newMessage.value.trim().length > 0 && 
    props.isSessionActive && 
    !props.isLoading
);

const sendMessage = async () => {
    if (!canSendMessage.value) return;
    
    const message = newMessage.value.trim();
    newMessage.value = '';
    
    emit('send-message', message);
    
    // Scroll to bottom after message is added
    await nextTick();
    scrollToBottom();
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const handleKeyPress = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
};

const formatTime = (timestamp: string) => {
    return new Date(timestamp).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Auto-scroll to bottom when new messages arrive
watch(() => props.messages.length, () => {
    nextTick(() => scrollToBottom());
});
</script>

<template>
    <Card class="flex flex-col h-full">
        <CardHeader>
            <CardTitle class="text-lg">Patient Interaction</CardTitle>
        </CardHeader>
        <CardContent class="flex flex-col flex-1 space-y-4">
            <!-- Messages Container -->
            <div 
                ref="messagesContainer"
                class="flex-1 overflow-y-auto space-y-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg min-h-0"
            >
                <div v-if="messages.length === 0" class="text-center text-gray-500 py-8">
                    Start the conversation with your patient...
                </div>
                
                <div 
                    v-for="message in messages" 
                    :key="message.id"
                    :class="[
                        'flex items-start space-x-3',
                        message.sender === 'user' ? 'justify-end' : 'justify-start'
                    ]"
                >
                    <!-- Avatar -->
                    <div 
                        v-if="message.sender === 'ai'" 
                        class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center"
                    >
                        <Bot class="h-4 w-4 text-white" />
                    </div>
                    
                    <!-- Message Bubble -->
                    <div 
                        :class="[
                            'max-w-xs lg:max-w-md px-4 py-2 rounded-lg',
                            message.sender === 'user' 
                                ? 'bg-blue-500 text-white' 
                                : 'bg-white dark:bg-gray-800 border'
                        ]"
                    >
                        <div class="text-sm">{{ message.message }}</div>
                        <div 
                            :class="[
                                'text-xs mt-1',
                                message.sender === 'user' 
                                    ? 'text-blue-100' 
                                    : 'text-gray-500'
                            ]"
                        >
                            {{ formatTime(message.timestamp) }}
                        </div>
                    </div>
                    
                    <!-- User Avatar -->
                    <div 
                        v-if="message.sender === 'user'" 
                        class="flex-shrink-0 w-8 h-8 rounded-full bg-green-500 flex items-center justify-center"
                    >
                        <User class="h-4 w-4 text-white" />
                    </div>
                </div>
                
                <!-- Loading Indicator -->
                <div v-if="isLoading" class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center">
                        <Bot class="h-4 w-4 text-white" />
                    </div>
                    <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg bg-white dark:bg-gray-800 border">
                        <div class="flex space-x-2">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Message Input -->
            <div class="flex space-x-2">
                <Input
                    v-model="newMessage"
                    placeholder="Type your message to the patient..."
                    class="flex-1"
                    :disabled="!isSessionActive"
                    @keypress="handleKeyPress"
                />
                <Button 
                    @click="sendMessage" 
                    :disabled="!canSendMessage"
                    class="flex-shrink-0"
                >
                    <Send class="h-4 w-4" />
                </Button>
            </div>
            
            <!-- Session Status -->
            <div v-if="!isSessionActive" class="text-center text-sm text-gray-500 py-2">
                Session is not active. You cannot send messages.
            </div>
        </CardContent>
    </Card>
</template>