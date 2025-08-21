<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { MessageSquare, Heart, Share, Send, Loader2 } from 'lucide-vue-next';
import { ref, computed, onMounted, nextTick } from 'vue';

interface User {
    id: number;
    name: string;
    avatar?: string;
}

interface Post {
    id: number;
    title: string;
    content: string;
    user: User;
    comments_count: number;
    created_at: string;
    formatted_created_at: string;
}

interface Comment {
    id: number;
    content: string;
    user: User;
    created_at: string;
    formatted_created_at: string;
}

interface Props {
    posts: {
        data: Post[];
        meta?: any;
        links?: any;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Forum',
        href: '/forum',
    },
];

// Timeline state
const posts = ref<Post[]>(props.posts.data);
const newPostContent = ref('');
const isPosting = ref(false);
const loadingMore = ref(false);
const nextPageUrl = ref(props.posts.links?.next);

// Comments state
const showComments = ref<Record<number, boolean>>({});
const comments = ref<Record<number, Comment[]>>({});
const commentInput = ref<Record<number, string>>({});
const loadingComments = ref<Record<number, boolean>>({});
const postingComment = ref<Record<number, boolean>>({});

// Character limit for posts (Twitter-like)
const CHARACTER_LIMIT = 280;

const characterCount = computed(() => newPostContent.value.length);
const canPost = computed(() => 
    newPostContent.value.trim().length > 0 && 
    newPostContent.value.length <= CHARACTER_LIMIT && 
    !isPosting.value
);

const submitPost = async () => {
    if (!canPost.value) return;
    
    isPosting.value = true;
    try {
        const response = await fetch('/api/forum/posts', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ content: newPostContent.value })
        });
        
        if (response.ok) {
            const newPost = await response.json();
            posts.value.unshift(newPost.data);
            newPostContent.value = '';
        }
    } catch (error) {
        console.error('Failed to post:', error);
    } finally {
        isPosting.value = false;
    }
};

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        submitPost();
    }
};

const toggleComments = async (postId: number) => {
    showComments.value[postId] = !showComments.value[postId];
    
    // Lazy load comments on first expand
    if (showComments.value[postId] && !comments.value[postId]) {
        await loadComments(postId);
    }
};

const loadComments = async (postId: number) => {
    loadingComments.value[postId] = true;
    try {
        const response = await fetch(`/api/forum/posts/${postId}/comments`);
        if (response.ok) {
            const data = await response.json();
            comments.value[postId] = data.data;
        }
    } catch (error) {
        console.error('Failed to load comments:', error);
    } finally {
        loadingComments.value[postId] = false;
    }
};

const submitComment = async (postId: number) => {
    const content = commentInput.value[postId]?.trim();
    if (!content || postingComment.value[postId]) return;
    
    postingComment.value[postId] = true;
    try {
        const response = await fetch(`/api/forum/posts/${postId}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ content })
        });
        
        if (response.ok) {
            const newComment = await response.json();
            if (!comments.value[postId]) comments.value[postId] = [];
            comments.value[postId].push(newComment.data);
            commentInput.value[postId] = '';
            
            // Update comments count
            const post = posts.value.find(p => p.id === postId);
            if (post) post.comments_count++;
        }
    } catch (error) {
        console.error('Failed to post comment:', error);
    } finally {
        postingComment.value[postId] = false;
    }
};

const handleCommentKeydown = (event: KeyboardEvent, postId: number) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        submitComment(postId);
    }
};

const loadMorePosts = async () => {
    if (!nextPageUrl.value || loadingMore.value) return;
    
    loadingMore.value = true;
    try {
        const response = await fetch(nextPageUrl.value);
        if (response.ok) {
            const data = await response.json();
            posts.value.push(...data.data);
            nextPageUrl.value = data.links?.next;
        }
    } catch (error) {
        console.error('Failed to load more posts:', error);
    } finally {
        loadingMore.value = false;
    }
};

const formatRelativeTime = (timestamp: string) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const seconds = Math.floor(diff / 1000);
    
    if (seconds < 60) return `${seconds}s`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h`;
    return `${Math.floor(seconds / 86400)}d`;
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <Head title="Forum" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold">Timeline</h1>
                    <p class="text-muted-foreground">What's happening?</p>
                </div>
            </div>

            <!-- Composer -->
            <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardContent class="p-4">
                    <div class="flex gap-3">
                        <Avatar class="h-10 w-10 flex-shrink-0">
                            <AvatarFallback>
                                {{ getInitials('You') }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex-1">
                            <Textarea 
                                v-model="newPostContent"
                                placeholder="What's happening?"
                                class="resize-none border-none shadow-none p-0 text-lg placeholder:text-muted-foreground focus-visible:ring-0"
                                rows="3"
                                @keydown="handleKeydown"
                                :disabled="isPosting"
                                :maxlength="CHARACTER_LIMIT"
                            />
                            <div class="flex justify-between items-center mt-3">
                                <div class="flex items-center gap-2 text-sm">
                                    <span :class="{
                                        'text-red-500': characterCount > CHARACTER_LIMIT,
                                        'text-yellow-500': characterCount > CHARACTER_LIMIT * 0.8,
                                        'text-muted-foreground': characterCount <= CHARACTER_LIMIT * 0.8
                                    }">
                                        {{ characterCount }}/{{ CHARACTER_LIMIT }}
                                    </span>
                                </div>
                                <Button 
                                    @click="submitPost"
                                    :disabled="!canPost"
                                    size="sm"
                                    class="gap-2"
                                >
                                    <Loader2 v-if="isPosting" class="w-4 h-4 animate-spin" />
                                    <Send v-else class="w-4 h-4" />
                                    {{ isPosting ? 'Posting...' : 'Post' }}
                                </Button>
                            </div>
                            <p class="text-xs text-muted-foreground mt-2">
                                Press Enter to post, Shift+Enter for new line
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Timeline -->
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <div class="divide-y divide-border">
                    <div v-if="posts.length === 0" class="text-center py-12 px-6">
                        <MessageSquare class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium">No posts yet</h3>
                        <p class="text-muted-foreground">Be the first to start a conversation!</p>
                    </div>

                    <!-- Timeline Posts -->
                    <article 
                        v-for="post in posts" 
                        :key="post.id" 
                        class="p-4 hover:bg-muted/50 transition-colors"
                    >
                        <div class="flex gap-3">
                            <Avatar class="h-10 w-10 flex-shrink-0">
                                <AvatarImage :src="post.user?.avatar" />
                                <AvatarFallback>
                                    {{ post.user?.name ? getInitials(post.user.name) : '?' }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="flex-1 min-w-0">
                                <!-- Post Header -->
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold">{{ post.user?.name || 'Unknown User' }}</span>
                                    <span class="text-muted-foreground text-sm">·</span>
                                    <span class="text-muted-foreground text-sm" :title="post.created_at">
                                        {{ formatRelativeTime(post.created_at) }}
                                    </span>
                                </div>

                                <!-- Post Content -->
                                <div class="mb-3">
                                    <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ post.content }}</p>
                                </div>

                                <!-- Post Actions -->
                                <div class="flex items-center gap-6 text-muted-foreground">
                                    <button 
                                        @click="toggleComments(post.id)"
                                        class="flex items-center gap-1 hover:text-blue-500 transition-colors group"
                                        :class="{ 'text-blue-500': showComments[post.id] }"
                                    >
                                        <div class="p-2 rounded-full group-hover:bg-blue-500/10 transition-colors">
                                            <MessageSquare class="w-4 h-4" />
                                        </div>
                                        <span class="text-sm">{{ post.comments_count }}</span>
                                    </button>
                                    
                                    <button class="flex items-center gap-1 hover:text-red-500 transition-colors group">
                                        <div class="p-2 rounded-full group-hover:bg-red-500/10 transition-colors">
                                            <Heart class="w-4 h-4" />
                                        </div>
                                    </button>
                                    
                                    <button class="flex items-center gap-1 hover:text-green-500 transition-colors group">
                                        <div class="p-2 rounded-full group-hover:bg-green-500/10 transition-colors">
                                            <Share class="w-4 h-4" />
                                        </div>
                                    </button>
                                </div>

                                <!-- Inline Comments Thread -->
                                <div v-if="showComments[post.id]" class="mt-4 space-y-3">
                                    <!-- Reply Input -->
                                    <div class="flex gap-3">
                                        <Avatar class="h-8 w-8 flex-shrink-0">
                                            <AvatarFallback class="text-xs">
                                                {{ getInitials('You') }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="flex-1">
                                            <Textarea 
                                                v-model="commentInput[post.id]"
                                                placeholder="Post your reply"
                                                class="resize-none min-h-[80px] text-sm"
                                                rows="2"
                                                @keydown="(e) => handleCommentKeydown(e, post.id)"
                                                :disabled="postingComment[post.id]"
                                            />
                                            <div class="flex justify-between items-center mt-2">
                                                <p class="text-xs text-muted-foreground">
                                                    Press Enter to reply, Shift+Enter for new line
                                                </p>
                                                <Button 
                                                    @click="submitComment(post.id)"
                                                    size="sm"
                                                    :disabled="!commentInput[post.id]?.trim() || postingComment[post.id]"
                                                    class="gap-1"
                                                >
                                                    <Loader2 v-if="postingComment[post.id]" class="w-3 h-3 animate-spin" />
                                                    <Send v-else class="w-3 h-3" />
                                                    Reply
                                                </Button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Comments List -->
                                    <div v-if="loadingComments[post.id]" class="flex items-center justify-center py-4">
                                        <Loader2 class="w-4 h-4 animate-spin" />
                                        <span class="ml-2 text-sm text-muted-foreground">Loading comments...</span>
                                    </div>
                                    
                                    <div v-else-if="comments[post.id]" class="space-y-3">
                                        <div 
                                            v-for="comment in comments[post.id]" 
                                            :key="comment.id"
                                            class="flex gap-3 pl-0"
                                        >
                                            <Avatar class="h-8 w-8 flex-shrink-0">
                                                <AvatarImage :src="comment.user?.avatar" />
                                                <AvatarFallback class="text-xs">
                                                    {{ comment.user?.name ? getInitials(comment.user.name) : '?' }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="font-medium text-sm">{{ comment.user?.name || 'Unknown User' }}</span>
                                                    <span class="text-muted-foreground text-xs">·</span>
                                                    <span class="text-muted-foreground text-xs" :title="comment.created_at">
                                                        {{ formatRelativeTime(comment.created_at) }}
                                                    </span>
                                                </div>
                                                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ comment.content }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Load More Button -->
                <div v-if="nextPageUrl" class="p-4 border-t">
                    <Button 
                        @click="loadMorePosts"
                        :disabled="loadingMore"
                        variant="outline"
                        class="w-full gap-2"
                    >
                        <Loader2 v-if="loadingMore" class="w-4 h-4 animate-spin" />
                        {{ loadingMore ? 'Loading...' : 'Load more posts' }}
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
