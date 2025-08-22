<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
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
    Settings,
    Bell,
    Bookmark,
    Eye,
    EyeOff,
    X,
    Plus
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
        isBookmarked: false,
        images: [],
        views: 1240
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
        isBookmarked: true,
        images: [],
        views: 2890
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
        isBookmarked: false,
        images: [],
        views: 5670
    }
]);

// Mock data for trending topics
const trendingTopics = ref([
    { id: 1, topic: '#VueJS', posts: '12.5K posts', trending: 'up' },
    { id: 2, topic: '#Laravel', posts: '8.2K posts', trending: 'up' },
    { id: 3, topic: '#WebDev', posts: '15.7K posts', trending: 'stable' },
    { id: 4, topic: '#AI', posts: '23.1K posts', trending: 'up' },
    { id: 5, topic: '#OpenSource', posts: '6.8K posts', trending: 'down' }
]);

// Mock data for who to follow
const whoToFollow = ref([
    {
        id: 1,
        name: 'Alex Chen',
        handle: '@alexchen',
        avatar: 'https://github.com/shadcn.png',
        verified: true,
        bio: 'Full-stack developer passionate about Vue and Laravel',
        followers: '2.4K'
    },
    {
        id: 2,
        name: 'Maria Garcia',
        handle: '@mariagarcia',
        avatar: 'https://github.com/shadcn.png',
        verified: false,
        bio: 'UI/UX designer and frontend developer',
        followers: '1.8K'
    },
    {
        id: 3,
        name: 'David Kim',
        handle: '@davidkim',
        avatar: 'https://github.com/shadcn.png',
        verified: true,
        bio: 'Backend engineer specializing in scalable systems',
        followers: '3.2K'
    }
]);

// Mock notifications
const notifications = ref([
    { id: 1, type: 'like', user: 'John Doe', action: 'liked your post', time: '5m', read: false },
    { id: 2, type: 'retweet', user: 'Sarah Wilson', action: 'retweeted your post', time: '12m', read: false },
    { id: 3, type: 'follow', user: 'Alex Chen', action: 'started following you', time: '1h', read: true }
]);

const newPostContent = ref('');
const searchQuery = ref('');
const selectedImages = ref<File[]>([]);
const showNotifications = ref(false);
const showBookmarks = ref(false);
const activeTab = ref('for-you');
const maxPostLength = 280;

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

const bookmarkPost = (postId: number) => {
    const post = posts.value.find(p => p.id === postId);
    if (post) {
        post.isBookmarked = !post.isBookmarked;
    }
};

const createPost = () => {
    if (newPostContent.value.trim() && newPostContent.value.length <= maxPostLength) {
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
            isBookmarked: false,
            images: selectedImages.value,
            views: 0
        };
        posts.value.unshift(newPost);
        newPostContent.value = '';
        selectedImages.value = [];
    }
};

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const files = Array.from(target.files);
        selectedImages.value = [...selectedImages.value, ...files];
    }
};

const removeImage = (index: number) => {
    selectedImages.value.splice(index, 1);
    if (selectedImages.value.length === 0) {
        showImageUpload.value = false;
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

const sharePost = (postId: number) => {
    const post = posts.value.find(p => p.id === postId);
    if (post) {
        // Mock share functionality - in a real app, this would open a share dialog
        if (navigator.share) {
            navigator.share({
                title: `${post.user.name} on Forum`,
                text: post.content,
                url: `${window.location.origin}/forum/post/${post.id}`
            });
        } else {
            // Fallback to copying to clipboard
            navigator.clipboard.writeText(`${post.content}\n\n- ${post.user.name} on Forum`);
            alert('Post content copied to clipboard!');
        }
    }
};

const markNotificationAsRead = (notificationId: number) => {
    const notification = notifications.value.find(n => n.id === notificationId);
    if (notification) {
        notification.read = true;
    }
};

const incrementPostViews = (postId: number) => {
    const post = posts.value.find(p => p.id === postId);
    if (post) {
        post.views += 1;
    }
};

const handlePostClick = (postId: number) => {
    incrementPostViews(postId);
    // In a real app, this would navigate to the post detail page
    console.log(`Post ${postId} clicked`);
};

const getTrendingIcon = (trending: string) => {
    switch (trending) {
        case 'up': return '📈';
        case 'down': return '📉';
        default: return '➡️';
    }
};

const handleTopicClick = (topic: string) => {
    // In a real app, this would filter posts by topic or navigate to topic page
    searchQuery.value = topic;
    console.log(`Topic ${topic} clicked`);
};

const handleUserClick = (handle: string) => {
    // In a real app, this would navigate to user profile
    console.log(`User ${handle} clicked`);
};

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        // In a real app, this would perform search and filter posts
        console.log(`Searching for: ${searchQuery.value}`);
        // You could filter posts here or navigate to search results
    }
};

const handleSearchKeyPress = (event: KeyboardEvent) => {
    if (event.key === 'Enter') {
        handleSearch();
    }
};

const handleNotificationClick = (notificationId: number) => {
    markNotificationAsRead(notificationId);
    // In a real app, this would navigate to the relevant content
    console.log(`Notification ${notificationId} clicked`);
};

const handleBookmarkClick = () => {
    showBookmarks.value = !showBookmarks.value;
    // In a real app, this would show bookmarked posts
    console.log('Bookmarks toggled');
};

const formatPostContent = (content: string) => {
    // Highlight hashtags and mentions
    return content
        .replace(/#(\w+)/g, '<span class="text-blue-500 hover:underline cursor-pointer">#$1</span>')
        .replace(/@(\w+)/g, '<span class="text-blue-500 hover:underline cursor-pointer">@$1</span>');
};

onMounted(() => {
    // Initialize any necessary data
    console.log('Forum component mounted');
});
</script>

<template>
    <Head title="Forum" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col lg:flex-row gap-6">
            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Header with Tabs -->
                <div class="sticky top-0 z-10 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b border-border">
                    <div class="px-4 py-3">
                        <h1 class="text-xl font-bold">Forum</h1>
                        <p class="text-sm text-muted-foreground">Share your thoughts with the community</p>
                    </div>
                    
                    <!-- Navigation Tabs -->
                    <div class="flex border-b border-border">
                        <button
                            @click="activeTab = 'for-you'"
                            :class="[
                                'flex-1 px-4 py-3 text-sm font-medium transition-colors',
                                activeTab === 'for-you'
                                    ? 'border-b-2 border-primary text-primary'
                                    : 'text-muted-foreground hover:text-foreground'
                            ]"
                        >
                            For you
                        </button>
                        <button
                            @click="activeTab = 'following'"
                            :class="[
                                'flex-1 px-4 py-3 text-sm font-medium transition-colors',
                                activeTab === 'following'
                                    ? 'border-b-2 border-primary text-primary'
                                    : 'text-muted-foreground hover:text-foreground'
                            ]"
                        >
                            Following
                        </button>
                        <button
                            @click="activeTab = 'trending'"
                            :class="[
                                'flex-1 px-4 py-3 text-sm font-medium transition-colors',
                                activeTab === 'trending'
                                    ? 'border-b-2 border-primary text-primary'
                                    : 'text-muted-foreground hover:text-foreground'
                            ]"
                        >
                            Trending
                        </button>
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
                                        id="newPostInput"
                                        v-model="newPostContent"
                                        placeholder="What's happening?"
                                        class="border-0 text-lg focus-visible:ring-0 focus-visible:ring-offset-0"
                                        @keyup.enter="createPost"
                                    />
                                    
                                    <!-- Image Preview -->
                                    <div v-if="selectedImages.length > 0" class="mt-3 grid grid-cols-2 gap-2">
                                        <div v-for="(image, index) in selectedImages" :key="index" class="relative">
                                            <img 
                                                :src="URL.createObjectURL(image)" 
                                                :alt="`Upload ${index + 1}`"
                                                class="w-full h-24 object-cover rounded-lg"
                                            />
                                            <Button
                                                @click="removeImage(index)"
                                                variant="ghost"
                                                size="sm"
                                                class="absolute top-1 right-1 h-6 w-6 p-0 bg-black/50 text-white hover:bg-black/70"
                                            >
                                                <X class="h-3 w-3" />
                                            </Button>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="flex items-center gap-2 text-primary">
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                class="h-8 w-8 p-0"
                                                @click="() => document.getElementById('imageInput')?.click()"
                                            >
                                                <ImageIcon class="h-4 w-4" />
                                            </Button>
                                            <input
                                                type="file"
                                                multiple
                                                accept="image/*"
                                                @change="handleImageUpload"
                                                class="hidden"
                                                id="imageInput"
                                            />
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
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                                <span :class="{ 'text-orange-500': newPostContent.length > maxPostLength * 0.8, 'text-red-500': newPostContent.length > maxPostLength }">
                                                    {{ newPostContent.length }}/{{ maxPostLength }}
                                                </span>
                                            </div>
                                            <Button 
                                                @click="createPost"
                                                :disabled="!newPostContent.trim() || newPostContent.length > maxPostLength"
                                                class="rounded-full px-6"
                                            >
                                                Post
                                            </Button>
                                        </div>
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
                                        <span 
                                            class="font-semibold text-foreground cursor-pointer hover:underline"
                                            @click="handleUserClick(post.user.handle)"
                                        >
                                            {{ post.user.name }}
                                        </span>
                                        <span v-if="post.user.verified" class="text-blue-500">✓</span>
                                        <span 
                                            class="text-muted-foreground cursor-pointer hover:underline"
                                            @click="handleUserClick(post.user.handle)"
                                        >
                                            {{ post.user.handle }}
                                        </span>
                                        <span class="text-muted-foreground">·</span>
                                        <span class="text-muted-foreground">{{ post.timestamp }}</span>
                                        <Button variant="ghost" size="sm" class="h-6 w-6 p-0 ml-auto">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <p 
                                        class="text-foreground mb-3 leading-relaxed cursor-pointer hover:bg-muted/30 p-2 rounded transition-colors" 
                                        v-html="formatPostContent(post.content)"
                                        @click="handlePostClick(post.id)"
                                    ></p>

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
                                            :class="{ 'text-blue-500 bg-blue-500/10': post.isBookmarked }"
                                            @click="bookmarkPost(post.id)"
                                        >
                                            <Bookmark class="h-4 w-4 mr-2" />
                                        </Button>

                                        <Button 
                                            variant="ghost" 
                                            size="sm" 
                                            class="h-8 px-3 text-muted-foreground hover:text-blue-500 hover:bg-blue-500/10"
                                            @click="sharePost(post.id)"
                                        >
                                            <Share2 class="h-4 w-4" />
                                        </Button>

                                        <div class="flex items-center gap-1 text-muted-foreground text-sm">
                                            <Eye class="h-4 w-4" />
                                            <span>{{ formatNumber(post.views) }}</span>
                                        </div>
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
                        @keyup="handleSearchKeyPress"
                    />
                </div>

                <!-- Quick Actions -->
                <div class="flex gap-2">
                    <Button 
                        variant="outline" 
                        size="sm" 
                        class="flex-1"
                        @click="() => showNotifications = !showNotifications"
                    >
                        <Bell class="h-4 w-4 mr-2" />
                        Notifications
                        <span v-if="notifications.filter(n => !n.read).length > 0" class="ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-1">
                            {{ notifications.filter(n => !n.read).length }}
                        </span>
                    </Button>
                    <Button 
                        variant="outline" 
                        size="sm" 
                        class="flex-1"
                        @click="handleBookmarkClick"
                    >
                        <Bookmark class="h-4 w-4 mr-2" />
                        Bookmarks
                    </Button>
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
                        <div 
                            v-for="topic in trendingTopics" 
                            :key="topic.id" 
                            class="flex items-center justify-between hover:bg-muted/50 p-2 rounded-lg cursor-pointer transition-colors"
                            @click="handleTopicClick(topic.topic)"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ getTrendingIcon(topic.trending) }}</span>
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
                                <p class="text-xs text-muted-foreground">{{ user.followers }} followers</p>
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

        <!-- Floating Action Button for Mobile -->
        <div class="fixed bottom-6 right-6 lg:hidden">
            <Button 
                size="lg" 
                class="h-14 w-14 rounded-full shadow-lg"
                @click="() => document.getElementById('newPostInput')?.focus()"
            >
                <Plus class="h-6 w-6" />
            </Button>
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

/* Smooth transitions */
.transition-colors {
    transition: all 0.2s ease-in-out;
}

/* Hover effects */
.hover\:bg-muted\/50:hover {
    background-color: hsl(var(--muted) / 0.5);
}

/* Active tab styling */
.border-b-2 {
    border-bottom-width: 2px;
}
</style>
