<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface CommentMetric {
    id: number;
    title: string;
    slug: string;
    comments_count: number;
}

interface Props {
    metricsByScope: {
        personal: {
            total_blog_post_views: number | null;
            average_views_per_blog_post: number | null;
            total_followers: number;
            comments_per_blog_post: CommentMetric[];
            views_30d: Array<{ day: string; count: number }>;
        };
        global?: {
            total_blog_post_views: number | null;
            average_views_per_blog_post: number | null;
            total_followers: number;
            comments_per_blog_post: CommentMetric[];
            views_30d: Array<{ day: string; count: number }>;
        };
    };
    meta: {
        views_tracking_enabled: boolean;
        available_scopes: string[];
        default_scope: 'personal' | 'global';
    };
}

const props = defineProps<Props>();

const selectedScope = ref<'personal' | 'global'>(props.meta.default_scope);

const activeMetrics = computed(() =>
    selectedScope.value === 'global' && props.metricsByScope.global
        ? props.metricsByScope.global
        : props.metricsByScope.personal,
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div
                v-if="meta.available_scopes.length > 1"
                class="flex items-center gap-2"
            >
                <button
                    type="button"
                    class="rounded-md border border-border px-3 py-1.5 text-sm"
                    :class="
                        selectedScope === 'personal'
                            ? 'bg-muted text-foreground'
                            : 'text-muted-foreground'
                    "
                    @click="selectedScope = 'personal'"
                >
                    Personal
                </button>
                <button
                    type="button"
                    class="rounded-md border border-border px-3 py-1.5 text-sm"
                    :class="
                        selectedScope === 'global'
                            ? 'bg-muted text-foreground'
                            : 'text-muted-foreground'
                    "
                    @click="selectedScope = 'global'"
                >
                    Global
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">
                        Total Blog Post Views
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{
                            activeMetrics.total_blog_post_views === null
                                ? 'Not available yet'
                                : activeMetrics.total_blog_post_views
                        }}
                    </p>
                </div>

                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">
                        Average Views per Blog Post
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{
                            activeMetrics.average_views_per_blog_post === null
                                ? 'Not available yet'
                                : activeMetrics.average_views_per_blog_post
                        }}
                    </p>
                </div>

                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">
                        Total Number of Followers
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ activeMetrics.total_followers }}
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-4">
                <h2 class="text-base font-semibold text-foreground">
                    Comments per Blog Post
                </h2>

                <div
                    v-if="activeMetrics.comments_per_blog_post.length === 0"
                    class="mt-4 text-sm text-muted-foreground"
                >
                    No published posts available yet.
                </div>

                <div v-else class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left">
                                <th class="px-3 py-2 font-medium">Post</th>
                                <th class="px-3 py-2 font-medium">Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="post in activeMetrics.comments_per_blog_post"
                                :key="post.id"
                                class="border-b border-border/60"
                            >
                                <td class="px-3 py-2">{{ post.title }}</td>
                                <td class="px-3 py-2">
                                    {{ post.comments_count }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-4">
                <h2 class="text-base font-semibold text-foreground">
                    30-Day View Graph for Posted Articles
                </h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{
                        meta.views_tracking_enabled
                            ? 'View trend data available.'
                            : 'View tracking is not enabled yet. This chart will populate once blog post view tracking is implemented.'
                    }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
