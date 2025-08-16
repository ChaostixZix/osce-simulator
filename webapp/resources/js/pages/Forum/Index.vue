<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Plus, MessageSquare, User } from 'lucide-vue-next';
import { ref } from 'vue';

interface Post {
    id: number;
    title: string;
    content: string;
    user: {
        id: number;
        name: string;
        avatar?: string;
    };
    comments_count: number;
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

const showNewPostForm = ref(false);

const form = useForm({
    title: '',
    content: '',
});

const submitPost = () => {
    form.post('/forum', {
        onSuccess: () => {
            form.reset();
            showNewPostForm.value = false;
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
    <Head title="Forum" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Header with New Post Button -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold">Forum</h1>
                    <p class="text-muted-foreground">Share and discuss with the community</p>
                </div>
                <Button @click="showNewPostForm = !showNewPostForm" class="gap-2">
                    <Plus class="w-4 h-4" />
                    New Post
                </Button>
            </div>

            <!-- New Post Form -->
            <Card v-if="showNewPostForm" class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardHeader>
                    <CardTitle>Create New Post</CardTitle>
                    <CardDescription>Share something with the community</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitPost" class="space-y-4">
                        <div>
                            <Input
                                v-model="form.title"
                                placeholder="Post title..."
                                required
                                :disabled="form.processing"
                            />
                        </div>
                        <div>
                            <Textarea
                                v-model="form.content"
                                placeholder="What's on your mind?"
                                rows="4"
                                required
                                :disabled="form.processing"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Publishing...' : 'Publish Post' }}
                            </Button>
                            <Button 
                                type="button" 
                                variant="outline" 
                                @click="showNewPostForm = false"
                                :disabled="form.processing"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Posts List -->
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <div class="p-6">
                    <div v-if="posts.data.length === 0" class="text-center py-12">
                        <MessageSquare class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium">No posts yet</h3>
                        <p class="text-muted-foreground mb-4">Be the first to start a conversation!</p>
                        <Button @click="showNewPostForm = true" class="gap-2">
                            <Plus class="w-4 h-4" />
                            Create First Post
                        </Button>
                    </div>

                    <div v-else class="space-y-4">
                        <Card 
                            v-for="post in posts.data" 
                            :key="post.id"
                            class="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 transition-colors cursor-pointer"
                            @click="router.visit(`/forum/${post.id}`)"
                        >
                            <CardContent class="p-6">
                                <div class="flex gap-4">
                                    <Avatar class="h-10 w-10 flex-shrink-0">
                                        <AvatarImage :src="post.user.avatar" />
                                        <AvatarFallback>
                                            {{ getInitials(post.user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="font-medium">{{ post.user.name }}</span>
                                            <span class="text-muted-foreground text-sm">
                                                {{ post.formatted_created_at }}
                                            </span>
                                        </div>
                                        
                                        <h3 class="text-lg font-semibold mb-2 hover:text-primary transition-colors">
                                            {{ post.title }}
                                        </h3>
                                        
                                        <p class="text-muted-foreground line-clamp-3 mb-3">
                                            {{ post.content }}
                                        </p>
                                        
                                        <div class="flex items-center gap-2">
                                            <Badge variant="secondary" class="gap-1">
                                                <MessageSquare class="w-3 h-3" />
                                                {{ post.comments_count }} 
                                                {{ post.comments_count === 1 ? 'comment' : 'comments' }}
                                            </Badge>
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

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>