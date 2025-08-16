<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
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
    featured_image: string | null
    published_at: string
    formatted_published_date: string
    reading_time: number
    views_count: number
    is_featured: boolean
    category: Category
    author: Author
    tags: Tag[]
}

interface Props {
    posts: {
        data: Post[]
        links: any[]
        meta: any
    }
    categories: Category[]
    tags: Tag[]
    filters: {
        category?: string
        tag?: string
        search?: string
    }
}

const props = defineProps<Props>()

const searchQuery = ref(props.filters.search || '')
const selectedCategory = ref(props.filters.category || '')
const selectedTag = ref(props.filters.tag || '')

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Blog',
        href: '/blog',
    },
]

const applyFilters = () => {
    const params: any = {}
    
    if (searchQuery.value) params.search = searchQuery.value
    if (selectedCategory.value) params.category = selectedCategory.value
    if (selectedTag.value) params.tag = selectedTag.value
    
    router.get('/blog', params, {
        preserveState: true,
        replace: true,
    })
}

const clearFilters = () => {
    searchQuery.value = ''
    selectedCategory.value = ''
    selectedTag.value = ''
    router.get('/blog', {}, { preserveState: true, replace: true })
}

const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedCategory.value || selectedTag.value
})
</script>

<template>
    <Head title="Blog" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-foreground">Blog</h1>
                    <p class="text-muted-foreground">
                        Discover insights, tutorials, and tips from our team
                    </p>
                </div>
                <Link href="/blog/create">
                    <Button>
                        Write New Post
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <h3 class="text-lg font-semibold">Filters</h3>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <!-- Search -->
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-foreground mb-2">
                                Search
                            </label>
                            <input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search posts..."
                                class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <!-- Category Filter -->
                        <div class="flex-1">
                            <label for="category" class="block text-sm font-medium text-foreground mb-2">
                                Category
                            </label>
                            <select
                                id="category"
                                v-model="selectedCategory"
                                class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                                <option value="">All Categories</option>
                                <option v-for="category in categories" :key="category.id" :value="category.slug">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Tag Filter -->
                        <div class="flex-1">
                            <label for="tag" class="block text-sm font-medium text-foreground mb-2">
                                Tag
                            </label>
                            <select
                                id="tag"
                                v-model="selectedTag"
                                class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                                <option value="">All Tags</option>
                                <option v-for="tag in tags" :key="tag.id" :value="tag.slug">
                                    {{ tag.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <Button @click="applyFilters" variant="default">
                                Apply Filters
                            </Button>
                            <Button 
                                v-if="hasActiveFilters" 
                                @click="clearFilters" 
                                variant="outline"
                            >
                                Clear
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Featured Posts -->
            <div v-if="posts.data.some(post => post.is_featured)" class="space-y-4">
                <h2 class="text-2xl font-bold text-foreground">Featured Posts</h2>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <template v-for="post in posts.data.filter(p => p.is_featured)" :key="post.id">
                        <Card class="group hover:shadow-lg transition-shadow duration-200">
                            <div v-if="post.featured_image" class="aspect-video overflow-hidden rounded-t-lg">
                                <img 
                                    :src="post.featured_image" 
                                    :alt="post.title"
                                    class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-200"
                                />
                            </div>
                            <CardContent class="p-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <span 
                                        class="px-2 py-1 text-xs font-medium rounded-full text-white"
                                        :style="{ backgroundColor: post.category.color }"
                                    >
                                        {{ post.category.name }}
                                    </span>
                                    <span class="text-sm text-muted-foreground">
                                        {{ post.formatted_published_date }}
                                    </span>
                                </div>
                                <Link :href="`/blog/${post.slug}`" class="group">
                                    <h3 class="text-xl font-semibold text-foreground group-hover:text-primary transition-colors mb-3">
                                        {{ post.title }}
                                    </h3>
                                </Link>
                                <p class="text-muted-foreground mb-4 line-clamp-3">
                                    {{ post.excerpt }}
                                </p>
                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <span>By {{ post.author.name }}</span>
                                    <span>{{ post.reading_time }} min read</span>
                                </div>
                                <div class="flex flex-wrap gap-1 mt-3">
                                    <span 
                                        v-for="tag in post.tags" 
                                        :key="tag.id"
                                        class="px-2 py-1 text-xs rounded-full text-white"
                                        :style="{ backgroundColor: tag.color }"
                                    >
                                        {{ tag.name }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </template>
                </div>
            </div>

            <!-- All Posts -->
            <div class="space-y-4">
                <h2 class="text-2xl font-bold text-foreground">
                    {{ posts.data.some(post => post.is_featured) ? 'Latest Posts' : 'All Posts' }}
                </h2>
                
                <div v-if="posts.data.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <template v-for="post in posts.data.filter(p => !p.is_featured)" :key="post.id">
                        <Card class="group hover:shadow-lg transition-shadow duration-200">
                            <div v-if="post.featured_image" class="aspect-video overflow-hidden rounded-t-lg">
                                <img 
                                    :src="post.featured_image" 
                                    :alt="post.title"
                                    class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-200"
                                />
                            </div>
                            <CardContent class="p-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <span 
                                        class="px-2 py-1 text-xs font-medium rounded-full text-white"
                                        :style="{ backgroundColor: post.category.color }"
                                    >
                                        {{ post.category.name }}
                                    </span>
                                    <span class="text-sm text-muted-foreground">
                                        {{ post.formatted_published_date }}
                                    </span>
                                </div>
                                <Link :href="`/blog/${post.slug}`" class="group">
                                    <h3 class="text-xl font-semibold text-foreground group-hover:text-primary transition-colors mb-3">
                                        {{ post.title }}
                                    </h3>
                                </Link>
                                <p class="text-muted-foreground mb-4 line-clamp-3">
                                    {{ post.excerpt }}
                                </p>
                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <span>By {{ post.author.name }}</span>
                                    <span>{{ post.reading_time }} min read</span>
                                </div>
                                <div class="flex flex-wrap gap-1 mt-3">
                                    <span 
                                        v-for="tag in post.tags" 
                                        :key="tag.id"
                                        class="px-2 py-1 text-xs rounded-full text-white"
                                        :style="{ backgroundColor: tag.color }"
                                    >
                                        {{ tag.name }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </template>
                </div>

                <div v-else class="text-center py-12">
                    <h3 class="text-lg font-semibold text-foreground mb-2">No posts found</h3>
                    <p class="text-muted-foreground mb-4">
                        Try adjusting your filters or search terms.
                    </p>
                    <Button @click="clearFilters" variant="outline">
                        Clear Filters
                    </Button>
                </div>

                <!-- Pagination -->
                <div v-if="posts.links.length > 3" class="flex justify-center">
                    <nav class="flex items-center gap-2">
                        <template v-for="(link, index) in posts.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-2 text-sm rounded-md transition-colors',
                                    link.active
                                        ? 'bg-primary text-primary-foreground font-medium'
                                        : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                :class="[
                                    'px-3 py-2 text-sm rounded-md',
                                    'text-muted-foreground/50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>