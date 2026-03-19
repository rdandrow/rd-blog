<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

interface BlogPost {
    id: number;
    title: string;
    excerpt: string;
    slug: string;
    is_featured: boolean;
    is_published: boolean;
    published_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    reading_time: number;
    tags: string[] | null;
    author: {
        id: number;
        name: string;
        email: string;
    } | null;
}

type PageProps = AppPageProps<{
    posts?: {
        data: BlogPost[];
        links: any[];
        meta: any;
    };
}>;

const props = defineProps<PageProps>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Blog Posts', href: '/admin/blog-posts' },
    { title: 'My Drafts', href: '/admin/blog-posts/drafts' },
];

const formatDate = (dateString: string | null) => {
    if (!dateString) return 'No date';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const deletePost = (post: BlogPost) => {
    if (confirm('Are you sure you want to delete this draft?')) {
        router.delete(`/admin/blog-posts/${post.id}`);
    }
};
</script>

<template>
    <Head title="My Drafts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Loading state for when posts data is not available -->
        <div
            v-if="!props.posts"
            class="flex min-h-64 items-center justify-center"
        >
            <div class="text-center">
                <div
                    class="mx-auto mb-2 h-8 w-8 animate-spin rounded-full border-b-2 border-primary"
                ></div>
                <p class="text-muted-foreground">Loading...</p>
            </div>
        </div>

        <div v-else class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        My Drafts
                    </h1>
                    <p class="text-muted-foreground">
                        View and manage your unpublished blog posts
                    </p>
                </div>

                <Link
                    href="/admin/blog-posts/create"
                    class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-primary-foreground transition-colors hover:bg-primary/90"
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
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                        />
                    </svg>
                    New Post
                </Link>
            </div>

            <!-- Drafts List -->
            <div
                class="overflow-hidden rounded-lg border border-border bg-card"
            >
                <div
                    v-if="!props.posts?.data || props.posts.data.length === 0"
                    class="p-8 text-center"
                >
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted"
                    >
                        <svg
                            class="h-8 w-8 text-muted-foreground"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-foreground">
                        No drafts yet
                    </h3>
                    <p class="mb-4 text-muted-foreground">
                        All your unpublished posts will appear here.
                    </p>
                    <Link
                        href="/admin/blog-posts/create"
                        class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Create Your First Post
                    </Link>
                </div>

                <div
                    v-else-if="props.posts?.data"
                    class="divide-y divide-border"
                >
                    <div
                        v-for="post in props.posts.data"
                        :key="post.id"
                        class="p-6 transition-colors hover:bg-muted/50"
                    >
                        <div class="flex items-start justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="mb-2 flex items-center gap-3">
                                    <Link
                                        :href="`/admin/blog-posts/${post.id}`"
                                        class="truncate text-lg font-semibold text-foreground transition-colors hover:text-primary"
                                    >
                                        {{ post.title }}
                                    </Link>

                                    <!-- Draft Badge -->
                                    <span
                                        class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400"
                                    >
                                        Draft
                                    </span>

                                    <!-- Featured Badge -->
                                    <span
                                        v-if="post.is_featured"
                                        class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
                                    >
                                        Featured
                                    </span>
                                </div>

                                <p
                                    class="mb-3 line-clamp-2 text-muted-foreground"
                                >
                                    {{ post.excerpt }}
                                </p>

                                <div
                                    class="flex items-center gap-4 text-sm text-muted-foreground"
                                >
                                    <span
                                        >By
                                        {{
                                            post.author?.name ||
                                            'Unknown Author'
                                        }}</span
                                    >
                                    <span
                                        >{{ post.reading_time }} min read</span
                                    >
                                    <span
                                        >Last updated
                                        {{ formatDate(post.updated_at) }}</span
                                    >
                                </div>

                                <!-- Tags -->
                                <div
                                    v-if="post.tags && post.tags.length > 0"
                                    class="mt-3 flex flex-wrap gap-1"
                                >
                                    <span
                                        v-for="tag in post.tags"
                                        :key="tag"
                                        class="rounded bg-muted px-2 py-1 text-xs text-muted-foreground"
                                    >
                                        {{ tag }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="ml-4 flex items-center gap-2">
                                <Link
                                    :href="`/admin/blog-posts/${post.id}`"
                                    class="rounded p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    title="View"
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
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                </Link>

                                <Link
                                    :href="`/admin/blog-posts/${post.id}/edit`"
                                    class="rounded p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    title="Edit"
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
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </Link>

                                <button
                                    @click="deletePost(post)"
                                    class="rounded p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-destructive"
                                    title="Delete"
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
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="props.posts?.links && props.posts.links.length > 3"
                class="flex items-center justify-center space-x-2"
            >
                <template
                    v-for="link in props.posts.links"
                    :key="link.label || 'unknown'"
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'rounded-md px-3 py-2 text-sm transition-colors',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                    >
                        <span v-html="link.label || ''" />
                    </Link>
                    <span
                        v-else
                        :class="[
                            'rounded-md px-3 py-2 text-sm transition-colors',
                            'cursor-not-allowed text-muted-foreground opacity-50',
                        ]"
                    >
                        <span v-html="link.label || ''" />
                    </span>
                </template>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
