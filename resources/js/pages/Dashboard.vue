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
    likes_count: number;
}

interface AuthorMetric {
    id: number;
    name: string;
    published_posts_count: number;
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
                top_authors_by_published_posts_30d: AuthorMetric[];
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
                top_authors_by_published_posts_30d: AuthorMetric[];
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
        generated_at: string;
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

const contentActivity30d = computed(() => {
    const high = activeMetrics.value.high_value_metrics;

    return high.posts_published_30d.map((postsPoint, index) => ({
        day: postsPoint.day,
        posts: postsPoint.count,
        comments: high.comments_created_30d[index]?.count ?? 0,
        likes: high.likes_created_30d[index]?.count ?? 0,
    }));
});

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
                    <p class="text-sm text-muted-foreground">Published Posts</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ activeMetrics.high_value_metrics.published_posts }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Draft Posts</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ activeMetrics.high_value_metrics.draft_posts }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Total Number of Followers</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ activeMetrics.total_followers }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Total Comments</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ activeMetrics.high_value_metrics.total_comments_on_published_posts }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Total Likes</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ activeMetrics.high_value_metrics.total_likes_on_published_posts }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">2FA Adoption Rate</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{
                            activeMetrics.high_value_metrics.two_factor_adoption_rate === null
                                ? 'Not available in personal scope'
                                : `${activeMetrics.high_value_metrics.two_factor_adoption_rate}%`
                        }}
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-4">
                <h2 class="text-base font-semibold text-foreground">
                    Charts Row
                </h2>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-border p-3">
                        <h3 class="text-sm font-medium text-foreground">30-Day Content Activity</h3>
                        <p class="mt-1 text-xs text-muted-foreground">Posts / Comments / Likes</p>
                        <div class="mt-3 max-h-64 overflow-y-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="border-b border-border text-left">
                                        <th class="px-2 py-1 font-medium">Day</th>
                                        <th class="px-2 py-1 font-medium">Posts</th>
                                        <th class="px-2 py-1 font-medium">Comments</th>
                                        <th class="px-2 py-1 font-medium">Likes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="point in contentActivity30d"
                                        :key="point.day"
                                        class="border-b border-border/60"
                                    >
                                        <td class="px-2 py-1">{{ point.day }}</td>
                                        <td class="px-2 py-1">{{ point.posts }}</td>
                                        <td class="px-2 py-1">{{ point.comments }}</td>
                                        <td class="px-2 py-1">{{ point.likes }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <h3 class="text-sm font-medium text-foreground">30-Day Follower Growth</h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ activeDayCount(activeMetrics.high_value_metrics.follower_growth_30d) > 0
                                ? `${activeDayCount(activeMetrics.high_value_metrics.follower_growth_30d)} day(s) with activity`
                                : 'No activity in last 30 days' }}
                        </p>
                        <div class="mt-3 max-h-64 overflow-y-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="border-b border-border text-left">
                                        <th class="px-2 py-1 font-medium">Day</th>
                                        <th class="px-2 py-1 font-medium">Followers</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="point in activeMetrics.high_value_metrics.follower_growth_30d"
                                        :key="point.day"
                                        class="border-b border-border/60"
                                    >
                                        <td class="px-2 py-1">{{ point.day }}</td>
                                        <td class="px-2 py-1">{{ point.count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-4">
                <h2 class="text-base font-semibold text-foreground">
                    Tables Row
                </h2>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-border p-3">
                        <h3 class="text-sm font-medium text-foreground">Top 10 Posts by Comments (with likes)</h3>

                        <div
                            v-if="activeMetrics.comments_per_blog_post.length === 0"
                            class="mt-3 text-xs text-muted-foreground"
                        >
                            No published posts available yet.
                        </div>

                        <div v-else class="mt-3 overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="border-b border-border text-left">
                                        <th class="px-2 py-1 font-medium">Post</th>
                                        <th class="px-2 py-1 font-medium">Comments</th>
                                        <th class="px-2 py-1 font-medium">Likes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="post in activeMetrics.comments_per_blog_post"
                                        :key="post.id"
                                        class="border-b border-border/60"
                                    >
                                        <td class="px-2 py-1">{{ post.title }}</td>
                                        <td class="px-2 py-1">{{ post.comments_count }}</td>
                                        <td class="px-2 py-1">{{ post.likes_count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <h3 class="text-sm font-medium text-foreground">Top 10 Authors by Published Posts (last 30d)</h3>

                        <div
                            v-if="activeMetrics.high_value_metrics.top_authors_by_published_posts_30d.length === 0"
                            class="mt-3 text-xs text-muted-foreground"
                        >
                            No authors with published posts in the last 30 days.
                        </div>

                        <div v-else class="mt-3 overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="border-b border-border text-left">
                                        <th class="px-2 py-1 font-medium">Author</th>
                                        <th class="px-2 py-1 font-medium">Published Posts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="author in activeMetrics.high_value_metrics.top_authors_by_published_posts_30d"
                                        :key="author.id"
                                        class="border-b border-border/60"
                                    >
                                        <td class="px-2 py-1">{{ author.name }}</td>
                                        <td class="px-2 py-1">{{ author.published_posts_count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-4">
                <h2 class="text-base font-semibold text-foreground">
                    Requested View Metrics (Pending Tracking)
                </h2>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-sm text-muted-foreground">Total Blog Post Views</p>
                        <p class="mt-1 text-xl font-semibold text-foreground">
                            {{
                                activeMetrics.total_blog_post_views === null
                                    ? 'Not available yet'
                                    : activeMetrics.total_blog_post_views
                            }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-sm text-muted-foreground">Average Views per Blog Post</p>
                        <p class="mt-1 text-xl font-semibold text-foreground">
                            {{
                                activeMetrics.average_views_per_blog_post === null
                                    ? 'Not available yet'
                                    : activeMetrics.average_views_per_blog_post
                            }}
                        </p>
                    </div>
                </div>

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
