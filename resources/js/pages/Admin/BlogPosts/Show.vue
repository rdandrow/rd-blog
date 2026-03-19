<script setup lang="ts">
import MarkdownRender from '@/components/MarkdownRender.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface BlogPost {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    content: string;
    featured_image: string | null;
    tags: string[];
    is_featured: boolean;
    is_published: boolean;
    published_at: string | null;
    reading_time: number;
    created_at: string;
    updated_at: string;
    author: {
        id: number;
        name: string;
        email: string;
    };
}

interface PageProps {
    post: BlogPost;
}

const props = defineProps<PageProps>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Blog Posts', href: '/admin/blog-posts' },
    { title: props.post.title, href: `/admin/blog-posts/${props.post.id}` },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const statusInfo = computed(() => {
    if (props.post.is_published) {
        return {
            text: 'Published',
            class: 'bg-green-100 text-green-800',
            icon: 'M5 13l4 4L19 7',
        };
    }
    return {
        text: 'Draft',
        class: 'bg-yellow-100 text-yellow-800',
        icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    };
});
</script>

<template>
    <Head :title="post.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl">
            <!-- Header -->
            <div class="mb-8">
                <div class="mb-4 flex items-start justify-between">
                    <div class="min-w-0 flex-1">
                        <h1
                            class="mb-2 text-3xl font-bold break-words text-foreground"
                        >
                            {{ post.title }}
                        </h1>

                        <div
                            class="flex items-center gap-4 text-sm text-muted-foreground"
                        >
                            <span>By {{ post.author.name }}</span>
                            <span>{{ post.reading_time }} min read</span>
                            <span>
                                {{
                                    post.published_at
                                        ? 'Published ' +
                                          formatDate(post.published_at)
                                        : 'Created ' +
                                          formatDate(post.created_at)
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="ml-4 flex items-center gap-2">
                        <Link
                            :href="`/admin/blog-posts/${post.id}/edit`"
                            class="inline-flex items-center rounded-md bg-secondary px-4 py-2 text-secondary-foreground transition-colors hover:bg-secondary/80"
                        >
                            <svg
                                class="mr-2 h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                            Edit
                        </Link>

                        <Link
                            href="/admin/blog-posts"
                            class="inline-flex items-center rounded-md border border-border px-4 py-2 text-foreground transition-colors hover:bg-muted"
                        >
                            Back to Posts
                        </Link>
                    </div>
                </div>

                <!-- Status and Meta -->
                <div class="mb-6 flex items-center gap-4">
                    <span
                        :class="[
                            'inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium',
                            statusInfo.class,
                        ]"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="statusInfo.icon"
                            />
                        </svg>
                        {{ statusInfo.text }}
                    </span>

                    <span
                        v-if="post.is_featured"
                        class="rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800"
                    >
                        Featured
                    </span>
                </div>

                <!-- Tags -->
                <div
                    v-if="post.tags.length > 0"
                    class="mb-6 flex flex-wrap gap-2"
                >
                    <span
                        v-for="tag in post.tags"
                        :key="tag"
                        class="rounded-full bg-muted px-3 py-1 text-sm text-muted-foreground"
                    >
                        {{ tag }}
                    </span>
                </div>
            </div>

            <!-- Featured Image -->
            <div v-if="post.featured_image" class="mb-8">
                <img
                    :src="post.featured_image"
                    :alt="post.title"
                    class="h-64 w-full rounded-lg border border-border object-cover"
                />
            </div>

            <!-- Content -->
            <div
                class="overflow-hidden rounded-lg border border-border bg-card"
            >
                <!-- Excerpt -->
                <div class="border-b border-border bg-muted/50 p-6">
                    <h2 class="mb-2 text-lg font-semibold text-foreground">
                        Excerpt
                    </h2>
                    <p class="text-muted-foreground italic">
                        {{ post.excerpt }}
                    </p>
                </div>

                <!-- Main Content -->
                <div class="p-6">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">
                        Content
                    </h2>
                    <div
                        class="prose max-w-none prose-neutral dark:prose-invert"
                    >
                        <MarkdownRender :content="post.content" />
                    </div>
                </div>
            </div>

            <!-- Metadata -->
            <div class="mt-8 rounded-lg border border-border bg-muted/50 p-6">
                <h3 class="mb-4 text-lg font-semibold text-foreground">
                    Post Information
                </h3>

                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                    <div>
                        <span class="font-medium text-foreground">Slug:</span>
                        <span class="ml-2 text-muted-foreground">{{
                            post.slug
                        }}</span>
                    </div>

                    <div>
                        <span class="font-medium text-foreground">Author:</span>
                        <span class="ml-2 text-muted-foreground">{{
                            post.author.name
                        }}</span>
                    </div>

                    <div>
                        <span class="font-medium text-foreground"
                            >Created:</span
                        >
                        <span class="ml-2 text-muted-foreground">{{
                            formatDate(post.created_at)
                        }}</span>
                    </div>

                    <div v-if="post.updated_at !== post.created_at">
                        <span class="font-medium text-foreground"
                            >Updated:</span
                        >
                        <span class="ml-2 text-muted-foreground">{{
                            formatDate(post.updated_at)
                        }}</span>
                    </div>

                    <div v-if="post.published_at">
                        <span class="font-medium text-foreground"
                            >Published:</span
                        >
                        <span class="ml-2 text-muted-foreground">{{
                            formatDate(post.published_at)
                        }}</span>
                    </div>

                    <div>
                        <span class="font-medium text-foreground"
                            >Reading Time:</span
                        >
                        <span class="ml-2 text-muted-foreground"
                            >{{ post.reading_time }} minutes</span
                        >
                    </div>
                </div>
            </div>

            <!-- Preview Link -->
            <div v-if="post.is_published" class="mt-8 text-center">
                <Link
                    :href="`/blog/${post.slug}`"
                    class="inline-flex items-center rounded-lg bg-primary px-6 py-3 text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    <svg
                        class="mr-2 h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        />
                    </svg>
                    View Live Post
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.prose {
    line-height: 1.75;
}
</style>
