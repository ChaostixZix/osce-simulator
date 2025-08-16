<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/Button.vue'
import { Card, CardContent, CardHeader } from '@/components/ui'
import { type BreadcrumbItem } from '@/types'

interface Category {
    id: number
    name: string
    slug: string
    color: string
}

interface Tag {
    id: number
    name: string
    slug: string
    color: string
}

interface Author {
    id: number
    name: string
    email: string
}

interface Post {
    id: number
    title: string
    slug: string
    excerpt: string
    content: string
    featured_image: string | null
    published_at: string
    formatted_published_date: string
    reading_time: number
    views_count: number
    is_featured: boolean
    category: Category
    author: Author
    tags: Tag[]
    meta_data?: {
        meta_title?: string
        meta_description?: string
        keywords?: string[]
    }
}

interface Props {
    post: Post
    relatedPosts: Post[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Blog',
        href: '/blog',
    },
    {
        title: props.post.title,
        href: `/blog/${props.post.slug}`,
    },
]

// Format content paragraphs
const formatContent = (content: string) => {
    return content.split('\n\n').map(paragraph => paragraph.trim()).filter(p => p.length > 0)
}

const contentParagraphs = formatContent(props.post.content)
</script>

<template>
    <Head 
        :title="post.meta_data?.meta_title || post.title"
        :description="post.meta_data?.meta_description || post.excerpt"
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <!-- Post Header -->
            <div class="max-w-4xl mx-auto w-full">
                <!-- Meta Information -->
                <div class="flex items-center gap-4 mb-6 text-sm text-muted-foreground">
                    <span 
                        class="px-3 py-1 text-sm font-medium rounded-full text-white"
                        :style="{ backgroundColor: post.category.color }"
                    >
                        {{ post.category.name }}
                    </span>
                    <span>{{ post.formatted_published_date }}</span>
                    <span>{{ post.reading_time }} min read</span>
                    <span>{{ post.views_count.toLocaleString() }} views</span>
                </div>

                <!-- Title -->
                <h1 class="text-4xl font-bold text-foreground mb-4 leading-tight">
                    {{ post.title }}
                </h1>

                <!-- Author & Actions -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-primary-foreground font-semibold">
                            {{ post.author.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <p class="font-medium text-foreground">{{ post.author.name }}</p>
                            <p class="text-sm text-muted-foreground">Author</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <Link :href="`/blog/${post.slug}/edit`">
                            <Button variant="outline" size="sm">
                                Edit Post
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Featured Image -->
                <div v-if="post.featured_image" class="mb-8">
                    <img 
                        :src="post.featured_image" 
                        :alt="post.title"
                        class="w-full h-64 md:h-96 object-cover rounded-lg shadow-lg"
                    />
                </div>

                <!-- Excerpt -->
                <div class="mb-8 p-6 bg-muted rounded-lg border-l-4 border-primary">
                    <p class="text-lg text-muted-foreground italic">
                        {{ post.excerpt }}
                    </p>
                </div>
            </div>

            <!-- Post Content -->
            <div class="max-w-4xl mx-auto w-full">
                <div class="prose prose-lg max-w-none">
                    <div 
                        v-for="(paragraph, index) in contentParagraphs" 
                        :key="index" 
                        class="mb-6 text-foreground leading-relaxed"
                    >
                        {{ paragraph }}
                    </div>
                </div>
            </div>

            <!-- Tags -->
            <div v-if="post.tags.length > 0" class="max-w-4xl mx-auto w-full">
                <div class="flex flex-wrap gap-2">
                    <span class="text-sm font-medium text-foreground mr-2">Tags:</span>
                    <Link
                        v-for="tag in post.tags"
                        :key="tag.id"
                        :href="`/blog?tag=${tag.slug}`"
                        class="px-3 py-1 text-sm rounded-full text-white hover:opacity-80 transition-opacity"
                        :style="{ backgroundColor: tag.color }"
                    >
                        {{ tag.name }}
                    </Link>
                </div>
            </div>

            <!-- Share & Navigation -->
            <div class="max-w-4xl mx-auto w-full border-t border-border pt-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-medium text-foreground">Share:</span>
                        <div class="flex gap-2">
                            <Button variant="outline" size="sm">
                                Twitter
                            </Button>
                            <Button variant="outline" size="sm">
                                LinkedIn
                            </Button>
                            <Button variant="outline" size="sm">
                                Copy Link
                            </Button>
                        </div>
                    </div>
                    
                    <Link href="/blog">
                        <Button variant="outline">
                            ← Back to Blog
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Related Posts -->
            <div v-if="relatedPosts.length > 0" class="max-w-4xl mx-auto w-full">
                <Card>
                    <CardHeader>
                        <h3 class="text-2xl font-bold text-foreground">Related Posts</h3>
                        <p class="text-muted-foreground">More articles from the {{ post.category.name }} category</p>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <div v-for="relatedPost in relatedPosts" :key="relatedPost.id" class="group">
                                <Link :href="`/blog/${relatedPost.slug}`" class="block">
                                    <div v-if="relatedPost.featured_image" class="aspect-video overflow-hidden rounded-lg mb-4">
                                        <img 
                                            :src="relatedPost.featured_image" 
                                            :alt="relatedPost.title"
                                            class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-200"
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span 
                                                class="px-2 py-1 text-xs font-medium rounded-full text-white"
                                                :style="{ backgroundColor: relatedPost.category.color }"
                                            >
                                                {{ relatedPost.category.name }}
                                            </span>
                                            <span class="text-xs text-muted-foreground">
                                                {{ relatedPost.formatted_published_date }}
                                            </span>
                                        </div>
                                        <h4 class="font-semibold text-foreground group-hover:text-primary transition-colors line-clamp-2">
                                            {{ relatedPost.title }}
                                        </h4>
                                        <p class="text-sm text-muted-foreground line-clamp-2">
                                            {{ relatedPost.excerpt }}
                                        </p>
                                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                                            <span>By {{ relatedPost.author.name }}</span>
                                            <span>{{ relatedPost.reading_time }} min read</span>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Additional prose styling */
.prose {
    @apply text-foreground;
}

.prose p {
    @apply mb-4 leading-relaxed;
}

.prose h2 {
    @apply text-2xl font-bold mt-8 mb-4;
}

.prose h3 {
    @apply text-xl font-bold mt-6 mb-3;
}

.prose ul, .prose ol {
    @apply ml-6 mb-4;
}

.prose li {
    @apply mb-2;
}

.prose blockquote {
    @apply border-l-4 border-primary pl-4 italic text-muted-foreground;
}

.prose code {
    @apply bg-muted px-1 py-0.5 rounded text-sm;
}

.prose pre {
    @apply bg-muted p-4 rounded-lg overflow-x-auto;
}

/* Line clamp utilities */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>