<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { 
    Heart, 
    MessageCircle, 
    Repeat2, 
    Share2, 
    MoreHorizontal,
    Image as ImageIcon,
    Smile,
    Calendar,
    MapPin,
    Link as LinkIcon,
    Search,
    TrendingUp,
    UserPlus,
    Hash,
    Settings
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Forum',
        href: '/forum',
    },
];

// Mock data for posts
const posts = ref([
    {
        id: 1,
        user: {
            name: 'John Doe',
            handle: '@johndoe',
            avatar: 'https://github.com/shadcn.png',
            verified: true
        },
        content: 'Just deployed my new Vue.js app! 🚀 The development experience has been amazing. What\'s your favorite frontend framework?',
        timestamp: '2h',
        likes: 42,
        retweets: 12,
        comments: 8,
        isLiked: false,
        isRetweeted: false,
        images: []
    },
    {
        id: 2,
        user: {
            name: 'Sarah Wilson',
            handle: '@sarahdev',
            avatar: 'https://github.com/shadcn.png',
            verified: false
        },
        content: 'Working on some really exciting features for our platform. Can\'t wait to share what we\'ve been building! 💻✨',
        timestamp: '4h',
        likes: 89,
        retweets: 23,
        comments: 15,
        isLiked: true,
        isRetweeted: false,
        images: []
    },
    {
        id: 3,
        user: {
            name: 'Tech News',
            handle: '@technews',
            avatar: 'https://github.com/shadcn.png',
            verified: true
        },
        content: 'Breaking: New AI models are revolutionizing how we approach software development. The future is here! 🤖',
        timestamp: '6h',
        likes: 156,
        retweets: 67,
        comments: 34,
        isLiked: false,
        isRetweeted: true,
        images: []
    }
]);

// Mock data for trending topics
const trendingTopics = ref([
    { id: 1, topic: '#VueJS', posts: '12.5K posts' },
    { id: 2, topic: '#Laravel', posts: '8.2K posts' },
    { id: 3, topic: '#WebDev', posts: '15.7K posts' },
    { id: 4, topic: '#AI', posts: '23.1K posts' },
    { id: 5, topic: '#OpenSource', posts: '6.8K posts' }
]);

// Mock data for who to follow
const whoToFollow = ref([
    {
        id: 1,
        name: 'Alex Chen',
        handle: '@alexchen',
        avatar: 'https://github.com/shadcn.png',
        verified: true,
        bio: 'Full-stack developer passionate about Vue and Laravel'
    },
    {
        id: 2,
        name: 'Maria Garcia',
        handle: '@mariagarcia',
        avatar: 'https://github.com/shadcn.png',
        verified: false,
        bio: 'UI/UX designer and frontend developer'
    },
    {
        id: 3,
        name: 'David Kim',
        handle: '@davidkim',
        avatar: 'https://github.com/shadcn.png',
        verified: true,
        bio: 'Backend engineer specializing in scalable systems'
    }
]);

const newPostContent = ref('');
const searchQuery = ref('');

const likePost = (postId: number) => {
    const post = posts.value.find(p => p.id === postId);
    if (post) {
        post.isLiked = !post.isLiked;
        post.likes += post.isLiked ? 1 : -1;
    }
};

const retweetPost = (postId: number) => {
    const post = posts.value.find(p => p.id === postId);
    if (post) {
        post.isRetweeted = !post.isRetweeted;
        post.retweets += post.isRetweeted ? 1 : -1;
    }
};

const createPost = () => {
    if (newPostContent.value.trim()) {
        const newPost = {
            id: posts.value.length + 1,
            user: {
                name: 'You',
                handle: '@you',
                avatar: 'https://github.com/shadcn.png',
                verified: false
            },
            content: newPostContent.value,
            timestamp: 'now',
            likes: 0,
            retweets: 0,
            comments: 0,
            isLiked: false,
            isRetweeted: false,
            images: []
        };
        posts.value.unshift(newPost);
        newPostContent.value = '';
    }
};

const formatNumber = (num: number) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
};

const followUser = (userId: number) => {
    // Mock follow functionality
    console.log(`Following user ${userId}`);
};
</script>

<template>
    <Head title="Forum" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col lg:flex-row gap-6">
            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Header -->
                <div class="sticky top-0 z-10 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b border-border">
                    <div class="px-4 py-3">
                        <h1 class="text-xl font-bold">Forum</h1>
                        <p class="text-sm text-muted-foreground">Share your thoughts with the community</p>
                    </div>
                </div>

                <!-- Create Post Section -->
                <div class="border-b border-border p-4">
                    <Card>
                        <CardContent class="p-4">
                            <div class="flex gap-3">
                                <Avatar class="h-10 w-10">
                                    <AvatarImage src="https://github.com/shadcn.png" alt="Your avatar" />
                                    <AvatarFallback>You</AvatarFallback>
                                </Avatar>
                                <div class="flex-1">
                                    <Input
                                        v-model="newPostContent"
                                        placeholder="What's happening?"
                                        class="border-0 text-lg focus-visible:ring-0 focus-visible:ring-offset-0"
                                        @keyup.enter="createPost"
                                    />
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="flex items-center gap-2 text-primary">
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                <ImageIcon class="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                <Smile class="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                <Calendar class="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                <MapPin class="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                <LinkIcon class="h-4 w-4" />
                                            </Button>
                                        </div>
                                        <Button 
                                            @click="createPost"
                                            :disabled="!newPostContent.trim()"
                                            class="rounded-full px-6"
                                        >
                                            Post
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Posts Feed -->
                <div class="flex-1">
                    <div v-for="post in posts" :key="post.id" class="border-b border-border hover:bg-muted/50 transition-colors">
                        <div class="p-4">
                            <div class="flex gap-3">
                                <!-- User Avatar -->
                                <Avatar class="h-10 w-10 flex-shrink-0">
                                    <AvatarImage :src="post.user.avatar" :alt="post.user.name" />
                                    <AvatarFallback>{{ post.user.name.charAt(0) }}</AvatarFallback>
                                </Avatar>

                                <!-- Post Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold text-foreground">{{ post.user.name }}</span>
                                        <span v-if="post.user.verified" class="text-blue-500">✓</span>
                                        <span class="text-muted-foreground">{{ post.user.handle }}</span>
                                        <span class="text-muted-foreground">·</span>
                                        <span class="text-muted-foreground">{{ post.timestamp }}</span>
                                        <Button variant="ghost" size="sm" class="h-6 w-6 p-0 ml-auto">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <p class="text-foreground mb-3 leading-relaxed">{{ post.content }}</p>

                                    <!-- Post Actions -->
                                    <div class="flex items-center justify-between max-w-md">
                                        <Button 
                                            variant="ghost" 
                                            size="sm" 
                                            class="h-8 px-3 text-muted-foreground hover:text-blue-500 hover:bg-blue-500/10"
                                            @click="() => {}"
                                        >
                                            <MessageCircle class="h-4 w-4 mr-2" />
                                            <span class="text-sm">{{ formatNumber(post.comments) }}</span>
                                        </Button>

                                        <Button 
                                            variant="ghost" 
                                            size="sm" 
                                            class="h-8 px-3 text-muted-foreground hover:text-green-500 hover:bg-green-500/10"
                                            :class="{ 'text-green-500 bg-green-500/10': post.isRetweeted }"
                                            @click="retweetPost(post.id)"
                                        >
                                            <Repeat2 class="h-4 w-4 mr-2" />
                                            <span class="text-sm">{{ formatNumber(post.retweets) }}</span>
                                        </Button>

                                        <Button 
                                            variant="ghost" 
                                            size="sm" 
                                            class="h-8 px-3 text-muted-foreground hover:text-red-500 hover:bg-red-500/10"
                                            :class="{ 'text-red-500 bg-red-500/10': post.isLiked }"
                                            @click="likePost(post.id)"
                                        >
                                            <Heart class="h-4 w-4 mr-2" />
                                            <span class="text-sm">{{ formatNumber(post.likes) }}</span>
                                        </Button>

                                        <Button 
                                            variant="ghost" 
                                            size="sm" 
                                            class="h-8 px-3 text-muted-foreground hover:text-blue-500 hover:bg-blue-500/10"
                                        >
                                            <Share2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="posts.length === 0" class="flex-1 flex items-center justify-center">
                    <div class="text-center text-muted-foreground">
                        <MessageCircle class="h-12 w-12 mx-auto mb-4 opacity-50" />
                        <h3 class="text-lg font-semibold mb-2">No posts yet</h3>
                        <p>Be the first to share something with the community!</p>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="w-80 flex-shrink-0 space-y-4 lg:block">
                <!-- Search -->
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Search Forum"
                        class="pl-10"
                    />
                </div>

                <!-- Trending Topics -->
                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <TrendingUp class="h-4 w-4" />
                            <h3 class="font-semibold">Trending</h3>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="topic in trendingTopics" :key="topic.id" class="flex items-center justify-between hover:bg-muted/50 p-2 rounded-lg cursor-pointer transition-colors">
                            <div class="flex items-center gap-2">
                                <Hash class="h-4 w-4 text-muted-foreground" />
                                <span class="font-medium">{{ topic.topic }}</span>
                            </div>
                            <span class="text-sm text-muted-foreground">{{ topic.posts }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Who to Follow -->
                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <UserPlus class="h-4 w-4" />
                            <h3 class="font-semibold">Who to follow</h3>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="user in whoToFollow" :key="user.id" class="flex items-center gap-3 hover:bg-muted/50 p-2 rounded-lg transition-colors">
                            <Avatar class="h-8 w-8">
                                <AvatarImage :src="user.avatar" :alt="user.name" />
                                <AvatarFallback>{{ user.name.charAt(0) }}</AvatarFallback>
                            </Avatar>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium text-sm">{{ user.name }}</span>
                                    <span v-if="user.verified" class="text-blue-500 text-xs">✓</span>
                                </div>
                                <p class="text-xs text-muted-foreground truncate">{{ user.handle }}</p>
                                <p class="text-xs text-muted-foreground truncate">{{ user.bio }}</p>
                            </div>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                class="text-xs px-3"
                                @click="followUser(user.id)"
                            >
                                Follow
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Footer Links -->
                <div class="text-xs text-muted-foreground space-y-2">
                    <div class="flex flex-wrap gap-2">
                        <a href="#" class="hover:underline">Terms of Service</a>
                        <a href="#" class="hover:underline">Privacy Policy</a>
                        <a href="#" class="hover:underline">Cookie Policy</a>
                        <a href="#" class="hover:underline">Accessibility</a>
                    </div>
                    <p>© 2024 Forum App. All rights reserved.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom scrollbar for the feed */
.flex-1::-webkit-scrollbar {
    width: 6px;
}

.flex-1::-webkit-scrollbar-track {
    background: transparent;
}

.flex-1::-webkit-scrollbar-thumb {
    background: hsl(var(--muted-foreground) / 0.3);
    border-radius: 3px;
}

.flex-1::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--muted-foreground) / 0.5);
}

/* Responsive design */
@media (max-width: 1024px) {
    .lg\:flex-row {
        flex-direction: column;
    }
    
    .w-80 {
        width: 100%;
    }
}
</style>