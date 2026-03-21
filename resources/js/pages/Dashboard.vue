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
            high_value_metrics: {
                published_posts: number;
                draft_posts: number;
                featured_posts: number;
                total_comments_on_published_posts: number;
                total_likes_on_published_posts: number;
                avg_comments_per_published_post: number;
                avg_likes_per_published_post: number;
                active_authors_30d: number;
                follower_growth_30d: Array<{ day: string; count: number }>;
                posts_published_30d: Array<{ day: string; count: number }>;
                comments_created_30d: Array<{ day: string; count: number }>;
                likes_created_30d: Array<{ day: string; count: number }>;
                two_factor_adoption_rate: number | null;
                invitation_funnel: {
                    pending: number | null;
                    accepted: number | null;
                    expired: number | null;
                };
            };
        };
        global?: {
            total_blog_post_views: number | null;
            average_views_per_blog_post: number | null;
            total_followers: number;
            comments_per_blog_post: CommentMetric[];
            views_30d: Array<{ day: string; count: number }>;
            high_value_metrics: {
                published_posts: number;
                draft_posts: number;
                featured_posts: number;
                total_comments_on_published_posts: number;
                total_likes_on_published_posts: number;
                avg_comments_per_published_post: number;
                avg_likes_per_published_post: number;
                active_authors_30d: number;
                follower_growth_30d: Array<{ day: string; count: number }>;
                posts_published_30d: Array<{ day: string; count: number }>;
                comments_created_30d: Array<{ day: string; count: number }>;
                likes_created_30d: Array<{ day: string; count: number }>;
                two_factor_adoption_rate: number | null;
                invitation_funnel: {
                    pending: number | null;
                    accepted: number | null;
                    expired: number | null;
                };
            };
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

const activeDayCount = (trend: Array<{ day: string; count: number }>) =>
    trend.filter((point) => point.count > 0).length;

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
                    High-Value Metrics
                </h2>

                <div class="mt-4 grid gap-4 md:grid-cols-4">
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">Published Posts</p>
                        <p class="mt-1 text-xl font-semibold">{{ activeMetrics.high_value_metrics.published_posts }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">Draft Posts</p>
                        <p class="mt-1 text-xl font-semibold">{{ activeMetrics.high_value_metrics.draft_posts }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">Featured Posts</p>
                        <p class="mt-1 text-xl font-semibold">{{ activeMetrics.high_value_metrics.featured_posts }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">Active Authors (30d)</p>
                        <p class="mt-1 text-xl font-semibold">{{ activeMetrics.high_value_metrics.active_authors_30d }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">Total Comments (Published)</p>
                        <p class="mt-1 text-xl font-semibold">{{ activeMetrics.high_value_metrics.total_comments_on_published_posts }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">Total Likes (Published)</p>
                        <p class="mt-1 text-xl font-semibold">{{ activeMetrics.high_value_metrics.total_likes_on_published_posts }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">Avg Comments / Published Post</p>
                        <p class="mt-1 text-xl font-semibold">{{ activeMetrics.high_value_metrics.avg_comments_per_published_post }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">Avg Likes / Published Post</p>
                        <p class="mt-1 text-xl font-semibold">{{ activeMetrics.high_value_metrics.avg_likes_per_published_post }}</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">2FA Adoption Rate</p>
                        <p class="mt-1 text-lg font-semibold">
                            {{
                                activeMetrics.high_value_metrics.two_factor_adoption_rate === null
                                    ? 'Not available in personal scope'
                                    : `${activeMetrics.high_value_metrics.two_factor_adoption_rate}%`
                            }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">Invitation Funnel</p>
                        <p class="mt-1 text-sm font-medium">
                            {{
                                activeMetrics.high_value_metrics.invitation_funnel.pending === null
                                    ? 'Not available in personal scope'
                                    : `Pending: ${activeMetrics.high_value_metrics.invitation_funnel.pending} · Accepted: ${activeMetrics.high_value_metrics.invitation_funnel.accepted} · Expired: ${activeMetrics.high_value_metrics.invitation_funnel.expired}`
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-4">
                <h2 class="text-base font-semibold text-foreground">
                    30-Day Trends (Captured Metrics)
                </h2>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-sm font-medium">Posts Published</p>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ activeDayCount(activeMetrics.high_value_metrics.posts_published_30d) > 0
                                ? `${activeDayCount(activeMetrics.high_value_metrics.posts_published_30d)} day(s) with activity`
                                : 'No activity in last 30 days' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-sm font-medium">Comments Created</p>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ activeDayCount(activeMetrics.high_value_metrics.comments_created_30d) > 0
                                ? `${activeDayCount(activeMetrics.high_value_metrics.comments_created_30d)} day(s) with activity`
                                : 'No activity in last 30 days' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-sm font-medium">Likes Created</p>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ activeDayCount(activeMetrics.high_value_metrics.likes_created_30d) > 0
                                ? `${activeDayCount(activeMetrics.high_value_metrics.likes_created_30d)} day(s) with activity`
                                : 'No activity in last 30 days' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-sm font-medium">Follower Growth</p>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ activeDayCount(activeMetrics.high_value_metrics.follower_growth_30d) > 0
                                ? `${activeDayCount(activeMetrics.high_value_metrics.follower_growth_30d)} day(s) with activity`
                                : 'No activity in last 30 days' }}
                        </p>
                    </div>
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
