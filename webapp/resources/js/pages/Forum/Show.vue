<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, MessageSquare, Reply } from 'lucide-vue-next';
import { ref, computed } from 'vue';

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
    post_id: number;
    created_at: string;
    formatted_created_at: string;
}

interface Props {
    post: Post;
    comments: Comment[];
}

const props = defineProps<Props>();
const page = usePage();
const auth = computed(() => page.props.auth as any);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Forum',
        href: '/forum',
    },
    {
        title: props.post.title,
        href: `/forum/${props.post.id}`,
    },
];

const showCommentForm = ref(false);

const commentForm = useForm({
    content: '',
});

const submitComment = () => {
    commentForm.post(`/forum/${props.post.id}/comments`, {
        onSuccess: () => {
            commentForm.reset();
            showCommentForm.value = false;
        },
    });
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

    <Head :title="post.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Back Button -->
            <div class="flex items-center gap-2">
                <Button variant="ghost" @click="router.visit('/forum')" class="gap-2">
                    <ArrowLeft class="w-4 h-4" />
                    Back to Forum
                </Button>
            </div>

            <!-- Post Card -->
            <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardContent class="p-6">
                    <div class="flex gap-4">
                        <Avatar class="h-12 w-12 flex-shrink-0">
                            <AvatarImage :src="post.user?.avatar" />
                            <AvatarFallback>
                                {{ post.user?.name ? getInitials(post.user.name) : '?' }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="font-medium text-lg">{{ post.user?.name || 'Unknown User' }}</span>
                                <span class="text-muted-foreground">
                                    {{ post.formatted_created_at }}
                                </span>
                            </div>

                            <h1 class="text-2xl font-bold mb-4">{{ post.title }}</h1>

                            <div class="prose prose-neutral dark:prose-invert max-w-none">
                                <p class="whitespace-pre-wrap">{{ post.content }}</p>
                            </div>

                            <div class="flex items-center gap-4 mt-6 pt-4 border-t">
                                <Badge variant="secondary" class="gap-1">
                                    <MessageSquare class="w-3 h-3" />
                                    {{ comments.length }}
                                    {{ comments.length === 1 ? 'comment' : 'comments' }}
                                </Badge>

                                <Button variant="outline" @click="showCommentForm = !showCommentForm" class="gap-2">
                                    <Reply class="w-4 h-4" />
                                    Reply
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Comment Form -->
            <Card v-if="showCommentForm" class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardHeader>
                    <CardTitle>Add a Comment</CardTitle>
                    <CardDescription>Share your thoughts on this post</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitComment" class="space-y-4">
                        <div>
                            <Textarea v-model="commentForm.content" placeholder="Write your comment..." rows="4"
                                required :disabled="commentForm.processing" />
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit" :disabled="commentForm.processing">
                                {{ commentForm.processing ? 'Posting...' : 'Post Comment' }}
                            </Button>
                            <Button type="button" variant="outline" @click="showCommentForm = false"
                                :disabled="commentForm.processing">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Comments Section -->
            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        Comments ({{ comments.length }})
                    </h2>

                    <div v-if="comments.length === 0" class="text-center py-12">
                        <MessageSquare class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium">No comments yet</h3>
                        <p class="text-muted-foreground mb-4">Be the first to share your thoughts!</p>
                        <Button @click="showCommentForm = true" class="gap-2">
                            <Reply class="w-4 h-4" />
                            Add Comment
                        </Button>
                    </div>

                    <div v-else class="space-y-4">
                        <Card v-for="comment in comments" :key="comment.id"
                            class="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardContent class="p-4">
                                <div class="flex gap-3">
                                    <Avatar class="h-8 w-8 flex-shrink-0">
                                        <AvatarImage :src="comment.user?.avatar" />
                                        <AvatarFallback>
                                            {{ comment.user?.name ? getInitials(comment.user.name) : '?' }}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="font-medium">{{ comment.user?.name || 'Unknown User' }}</span>
                                            <span class="text-muted-foreground text-sm">
                                                {{ comment.formatted_created_at }}
                                            </span>
                                        </div>

                                        <div class="prose prose-sm prose-neutral dark:prose-invert max-w-none">
                                            <p class="whitespace-pre-wrap">{{ comment.content }}</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
