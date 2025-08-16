<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
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

interface Props {
    categories: Category[]
    tags: Tag[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Blog',
        href: '/blog',
    },
    {
        title: 'Create Post',
        href: '/blog/create',
    },
]

const form = useForm({
    title: '',
    excerpt: '',
    content: '',
    featured_image: '',
    category_id: '',
    status: 'draft',
    is_featured: false,
    comments_enabled: true,
    tags: [] as number[],
    meta_data: {
        meta_title: '',
        meta_description: '',
        keywords: [] as string[],
    },
})

const newKeyword = ref('')

const submit = () => {
    form.post('/blog', {
        onSuccess: () => {
            // Redirect handled by controller
        },
    })
}

const saveDraft = () => {
    form.status = 'draft'
    submit()
}

const publish = () => {
    form.status = 'published'
    submit()
}

const addKeyword = () => {
    if (newKeyword.value.trim() && !form.meta_data.keywords.includes(newKeyword.value.trim())) {
        form.meta_data.keywords.push(newKeyword.value.trim())
        newKeyword.value = ''
    }
}

const removeKeyword = (index: number) => {
    form.meta_data.keywords.splice(index, 1)
}

const toggleTag = (tagId: number) => {
    const index = form.tags.indexOf(tagId)
    if (index > -1) {
        form.tags.splice(index, 1)
    } else {
        form.tags.push(tagId)
    }
}

const isTagSelected = (tagId: number) => {
    return form.tags.includes(tagId)
}

// Auto-generate meta title and description if empty
const autoGenerateMeta = () => {
    if (!form.meta_data.meta_title && form.title) {
        form.meta_data.meta_title = form.title
    }
    if (!form.meta_data.meta_description && form.excerpt) {
        form.meta_data.meta_description = form.excerpt
    }
}
</script>

<template>
    <Head title="Create Blog Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <div class="max-w-4xl mx-auto w-full">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-foreground">Create New Post</h1>
                        <p class="text-muted-foreground">
                            Write and publish your blog post
                        </p>
                    </div>
                    
                    <div class="flex gap-2">
                        <Button @click="saveDraft" variant="outline" :disabled="form.processing">
                            Save Draft
                        </Button>
                        <Button @click="publish" :disabled="form.processing">
                            Publish
                        </Button>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <h3 class="text-lg font-semibold">Basic Information</h3>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Title -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-foreground mb-2">
                                    Title *
                                </label>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter post title..."
                                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.title }"
                                />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <!-- Excerpt -->
                            <div>
                                <label for="excerpt" class="block text-sm font-medium text-foreground mb-2">
                                    Excerpt
                                </label>
                                <textarea
                                    id="excerpt"
                                    v-model="form.excerpt"
                                    rows="3"
                                    placeholder="Brief description of your post..."
                                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.excerpt }"
                                ></textarea>
                                <p v-if="form.errors.excerpt" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.excerpt }}
                                </p>
                            </div>

                            <!-- Content -->
                            <div>
                                <label for="content" class="block text-sm font-medium text-foreground mb-2">
                                    Content *
                                </label>
                                <textarea
                                    id="content"
                                    v-model="form.content"
                                    rows="15"
                                    placeholder="Write your post content here..."
                                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.content }"
                                ></textarea>
                                <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.content }}
                                </p>
                            </div>

                            <!-- Featured Image -->
                            <div>
                                <label for="featured_image" class="block text-sm font-medium text-foreground mb-2">
                                    Featured Image URL
                                </label>
                                <input
                                    id="featured_image"
                                    v-model="form.featured_image"
                                    type="url"
                                    placeholder="https://example.com/image.jpg"
                                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.featured_image }"
                                />
                                <p v-if="form.errors.featured_image" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.featured_image }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Category & Tags -->
                    <Card>
                        <CardHeader>
                            <h3 class="text-lg font-semibold">Category & Tags</h3>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Category -->
                            <div>
                                <label for="category" class="block text-sm font-medium text-foreground mb-2">
                                    Category *
                                </label>
                                <select
                                    id="category"
                                    v-model="form.category_id"
                                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    :class="{ 'border-red-500': form.errors.category_id }"
                                >
                                    <option value="">Select a category</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.category_id }}
                                </p>
                            </div>

                            <!-- Tags -->
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">
                                    Tags
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="tag in tags"
                                        :key="tag.id"
                                        type="button"
                                        @click="toggleTag(tag.id)"
                                        :class="[
                                            'px-3 py-1 text-sm rounded-full border transition-colors',
                                            isTagSelected(tag.id)
                                                ? 'text-white border-transparent'
                                                : 'text-foreground border-border hover:border-primary'
                                        ]"
                                        :style="isTagSelected(tag.id) ? { backgroundColor: tag.color } : {}"
                                    >
                                        {{ tag.name }}
                                    </button>
                                </div>
                                <p v-if="form.errors.tags" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.tags }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Post Settings -->
                    <Card>
                        <CardHeader>
                            <h3 class="text-lg font-semibold">Post Settings</h3>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-foreground mb-2">
                                    Status
                                </label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                >
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>

                            <!-- Settings Checkboxes -->
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input
                                        id="is_featured"
                                        v-model="form.is_featured"
                                        type="checkbox"
                                        class="rounded border-border text-primary focus:ring-primary"
                                    />
                                    <label for="is_featured" class="ml-2 text-sm text-foreground">
                                        Featured Post
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        id="comments_enabled"
                                        v-model="form.comments_enabled"
                                        type="checkbox"
                                        class="rounded border-border text-primary focus:ring-primary"
                                    />
                                    <label for="comments_enabled" class="ml-2 text-sm text-foreground">
                                        Enable Comments
                                    </label>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- SEO Settings -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">SEO Settings</h3>
                                <Button type="button" @click="autoGenerateMeta" variant="outline" size="sm">
                                    Auto Generate
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Meta Title -->
                            <div>
                                <label for="meta_title" class="block text-sm font-medium text-foreground mb-2">
                                    Meta Title
                                    <span class="text-xs text-muted-foreground">({{ form.meta_data.meta_title.length }}/60)</span>
                                </label>
                                <input
                                    id="meta_title"
                                    v-model="form.meta_data.meta_title"
                                    type="text"
                                    placeholder="SEO optimized title..."
                                    maxlength="60"
                                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>

                            <!-- Meta Description -->
                            <div>
                                <label for="meta_description" class="block text-sm font-medium text-foreground mb-2">
                                    Meta Description
                                    <span class="text-xs text-muted-foreground">({{ form.meta_data.meta_description.length }}/160)</span>
                                </label>
                                <textarea
                                    id="meta_description"
                                    v-model="form.meta_data.meta_description"
                                    rows="3"
                                    placeholder="SEO description for search engines..."
                                    maxlength="160"
                                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                ></textarea>
                            </div>

                            <!-- Keywords -->
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">
                                    Keywords
                                </label>
                                <div class="flex gap-2 mb-3">
                                    <input
                                        v-model="newKeyword"
                                        type="text"
                                        placeholder="Add keyword..."
                                        class="flex-1 rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                        @keyup.enter="addKeyword"
                                    />
                                    <Button type="button" @click="addKeyword" variant="outline">
                                        Add
                                    </Button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(keyword, index) in form.meta_data.keywords"
                                        :key="index"
                                        class="px-2 py-1 bg-muted text-foreground text-sm rounded-md flex items-center gap-1"
                                    >
                                        {{ keyword }}
                                        <button
                                            type="button"
                                            @click="removeKeyword(index)"
                                            class="text-muted-foreground hover:text-red-500 ml-1"
                                        >
                                            ×
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Submit Buttons -->
                    <div class="flex gap-4 justify-end">
                        <Button type="button" @click="$inertia.visit('/blog')" variant="outline">
                            Cancel
                        </Button>
                        <Button type="button" @click="saveDraft" variant="outline" :disabled="form.processing">
                            Save as Draft
                        </Button>
                        <Button type="button" @click="publish" :disabled="form.processing">
                            {{ form.processing ? 'Publishing...' : 'Publish Post' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>