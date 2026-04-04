<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DashboardBarChart from '@/components/charts/DashboardBarChart.vue';
import DashboardLineChart from '@/components/charts/DashboardLineChart.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';
import { buildBarChartData, buildBarDataset, buildBarOptions, buildChartData, buildLineDataset, buildTrendOptions } from '@/lib/chart';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Tooltip, Legend, Filler);

interface TrendPoint {
    day: string;
    count: number;
}

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

interface HighValueMetrics {
    published_posts: number;
    draft_posts: number;
    featured_posts: number;
    total_comments_on_published_posts: number;
    total_likes_on_published_posts: number;
    avg_comments_per_published_post: number;
    avg_likes_per_published_post: number;
    active_authors_30d: number | null;
    top_authors_by_published_posts_30d: AuthorMetric[];
    follower_growth_30d: TrendPoint[];
    posts_published_30d: TrendPoint[];
    comments_created_30d: TrendPoint[];
    likes_created_30d: TrendPoint[];
    two_factor_adoption_rate: number | null;
    invitation_funnel: {
        pending: number | null;
        accepted: number | null;
        expired: number | null;
    };
    user_trends: {
        total_users: number;
        daily_active_users: number;
        weekly_active_users: number;
        monthly_active_users: number;
        total_inactive_users: number;
    } | null;
}

interface ScopeMetrics {
    total_blog_post_views: number | null;
    average_views_per_blog_post: number | null;
    total_followers: number;
    comments_per_blog_post: CommentMetric[];
    views_30d: TrendPoint[];
    high_value_metrics: HighValueMetrics;
}

interface Props {
    metricsByScope: {
        personal: ScopeMetrics;
        global?: ScopeMetrics;
    };
    meta: {
        views_tracking_enabled: boolean;
        available_scopes: string[];
        default_scope: 'personal' | 'global';
        generated_at: string;
    };
}

const props = defineProps<Props>();

const CHART_LABEL_MAX_LENGTH = 22;

const selectedScope = ref<'personal' | 'global'>(props.meta.default_scope);
const chartThemeVersion = ref(0);
let themeObserver: MutationObserver | null = null;

onMounted(() => {
    if (typeof MutationObserver === 'undefined') {
        return;
    }

    themeObserver = new MutationObserver(() => {
        chartThemeVersion.value += 1;
    });

    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class', 'style', 'data-theme'],
    });
});

onBeforeUnmount(() => {
    themeObserver?.disconnect();
    themeObserver = null;
});

const activeMetrics = computed(() =>
    selectedScope.value === 'global' && props.metricsByScope.global
        ? props.metricsByScope.global
        : props.metricsByScope.personal,
);

const showUserTrends = computed(
    () =>
        selectedScope.value === 'global' &&
        !!props.metricsByScope.global?.high_value_metrics.user_trends,
);

const showActiveAuthorsMetric = computed(() => selectedScope.value === 'global');

const totalFollowersLabel = computed(() =>
    selectedScope.value === 'global' ? 'Total Follow Relationships' : 'Total Followers',
);

const followerGrowthHeading = computed(() =>
    selectedScope.value === 'global'
        ? '30-Day Follower Growth (Platform)'
        : '30-Day Follower Growth (You)',
);

const topAuthorsHeading = computed(() =>
    selectedScope.value === 'global'
        ? 'Top Authors (Last 30 Days)'
        : 'Top Authors (Platform, Last 30 Days)',
);

const showInvitationFunnel = computed(() => {
    const funnel = activeMetrics.value.high_value_metrics.invitation_funnel;

    return funnel.pending !== null || funnel.accepted !== null || funnel.expired !== null;
});

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

const contentActivityChartData = computed(() =>
    buildChartData(
        contentActivity30d.value.map((point) => formatDashboardDate(point.day)),
        [
            buildLineDataset(
                'Posts',
                contentActivity30d.value.map((point) => point.posts),
                '--chart-1',
            ),
            buildLineDataset(
                'Comments',
                contentActivity30d.value.map((point) => point.comments),
                '--chart-2',
            ),
            buildLineDataset(
                'Likes',
                contentActivity30d.value.map((point) => point.likes),
                '--chart-3',
            ),
        ],
        chartThemeVersion.value,
    ),
);

const followerGrowthChartData = computed(() =>
    buildChartData(
        activeMetrics.value.high_value_metrics.follower_growth_30d.map((point) =>
            formatDashboardDate(point.day),
        ),
        [
            buildLineDataset(
                'Followers',
                activeMetrics.value.high_value_metrics.follower_growth_30d.map((point) => point.count),
                '--chart-4',
                true,
            ),
        ],
        chartThemeVersion.value,
    ),
);

const trendChartOptions = computed(() => buildTrendOptions(true, chartThemeVersion.value));

const topPostsChartData = computed(() =>
    buildBarChartData(
        activeMetrics.value.comments_per_blog_post.map((p) =>
            p.title.length > CHART_LABEL_MAX_LENGTH
                ? `${p.title.slice(0, CHART_LABEL_MAX_LENGTH - 1)}\u2026`
                : p.title,
        ),
        [
            buildBarDataset(
                'Comments',
                activeMetrics.value.comments_per_blog_post.map((p) => p.comments_count),
                '--chart-1',
            ),
            buildBarDataset(
                'Likes',
                activeMetrics.value.comments_per_blog_post.map((p) => p.likes_count),
                '--chart-2',
            ),
        ],
        chartThemeVersion.value,
    ),
);

const topPostsChartOptions = computed(() => buildBarOptions(true, chartThemeVersion.value));

const topAuthorsChartData = computed(() =>
    buildBarChartData(
        activeMetrics.value.high_value_metrics.top_authors_by_published_posts_30d.map(
            (a) => a.name,
        ),
        [
            buildBarDataset(
                'Published Posts',
                activeMetrics.value.high_value_metrics.top_authors_by_published_posts_30d.map(
                    (a) => a.published_posts_count,
                ),
                '--chart-4',
            ),
        ],
        chartThemeVersion.value,
    ),
);

const topAuthorsChartOptions = computed(() => buildBarOptions(false, chartThemeVersion.value));

const viewTrendChartData = computed(() =>
    buildChartData(
        activeMetrics.value.views_30d.map((point) => formatDashboardDate(point.day)),
        [
            buildLineDataset(
                'Views',
                activeMetrics.value.views_30d.map((point) => point.count),
                '--chart-5',
                true,
            ),
        ],
        chartThemeVersion.value,
    ),
);

const viewTrendChartOptions = computed(() => buildTrendOptions(false, chartThemeVersion.value));

const showViewMetrics = computed(() => props.meta.views_tracking_enabled);

const viewsTrendSummary = computed(() => {
    const points = activeMetrics.value.views_30d;

    if (points.length === 0) {
        return {
            total: 0,
            peakCount: 0,
            peakDay: '',
        };
    }

    const total = points.reduce((sum, point) => sum + point.count, 0);
    const peakPoint = points.reduce((peak, point) =>
        point.count > peak.count ? point : peak,
    );

    return {
        total,
        peakCount: peakPoint.count,
        peakDay: peakPoint.day,
    };
});

const personalGlobalBenchmarks = computed(() => {
    if (selectedScope.value !== 'personal' || !props.metricsByScope.global) {
        return [] as Array<{ label: string; delta: string }>;
    }

    const personal = props.metricsByScope.personal;
    const global = props.metricsByScope.global;
    const chips: Array<{ label: string; delta: string }> = [];

    const formatDelta = (personalValue: number, globalValue: number) => {
        if (globalValue === 0) {
            return 'n/a';
        }

        const delta = ((personalValue - globalValue) / globalValue) * 100;
        const sign = delta > 0 ? '+' : '';

        return `${sign}${delta.toFixed(1)}%`;
    };

    chips.push({
        label: 'Comments/Post vs Global',
        delta: formatDelta(
            personal.high_value_metrics.avg_comments_per_published_post,
            global.high_value_metrics.avg_comments_per_published_post,
        ),
    });

    chips.push({
        label: 'Likes/Post vs Global',
        delta: formatDelta(
            personal.high_value_metrics.avg_likes_per_published_post,
            global.high_value_metrics.avg_likes_per_published_post,
        ),
    });

    if (
        personal.average_views_per_blog_post !== null &&
        global.average_views_per_blog_post !== null
    ) {
        chips.push({
            label: 'Views/Post vs Global',
            delta: formatDelta(personal.average_views_per_blog_post, global.average_views_per_blog_post),
        });
    }

    return chips;
});

const formatDashboardDate = (day: string) => {
    const dayToken = day.slice(0, 10);
    const parsedDate = new Date(`${dayToken}T00:00:00Z`);

    if (Number.isNaN(parsedDate.getTime())) {
        return day;
    }

    return parsedDate.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        timeZone: 'UTC',
    });
};

const formatNumber = (n: number) => n.toLocaleString();

const formattedGeneratedAt = computed(() => {
    // PHP sends ISO 8601 with UTC offset (now()->toISOString()), so new Date() parses correctly.
    // toLocaleDateString/toLocaleTimeString display in the browser's local timezone — intentional.
    const d = new Date(props.meta.generated_at);
    const date = d.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
    const time = d.toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });

    return `${date} at ${time}`;
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
            <!-- Header: scope toggle + last updated -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div
                    v-if="meta.available_scopes.length > 1"
                    class="flex items-center gap-2"
                >
                    <span class="text-sm text-muted-foreground">View:</span>
                    <button
                        type="button"
                        class="rounded-md border border-border px-3 py-1.5 text-sm transition-colors"
                        :class="
                            selectedScope === 'personal'
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted'
                        "
                        @click="selectedScope = 'personal'"
                    >
                        Personal
                    </button>
                    <button
                        type="button"
                        class="rounded-md border border-border px-3 py-1.5 text-sm transition-colors"
                        :class="
                            selectedScope === 'global'
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted'
                        "
                        @click="selectedScope = 'global'"
                    >
                        Global
                    </button>
                </div>
                <p class="text-xs text-muted-foreground">
                    Updated: {{ formattedGeneratedAt }}
                </p>
            </div>

            <!-- Row 1: Content overview -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Published Posts</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ formatNumber(activeMetrics.high_value_metrics.published_posts) }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Featured Posts</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ formatNumber(activeMetrics.high_value_metrics.featured_posts) }}
                    </p>
                </div>
            </div>

            <!-- Row 2: Audience & engagement totals -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">{{ totalFollowersLabel }}</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ formatNumber(activeMetrics.total_followers) }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Total Comments</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ formatNumber(activeMetrics.high_value_metrics.total_comments_on_published_posts) }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Total Likes</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ formatNumber(activeMetrics.high_value_metrics.total_likes_on_published_posts) }}
                    </p>
                </div>
            </div>

            <!-- Row 3: Averages + activity (always 3 cols) -->
            <div class="grid gap-4" :class="showActiveAuthorsMetric ? 'sm:grid-cols-3' : 'sm:grid-cols-2'">
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Avg Comments / Post</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ activeMetrics.high_value_metrics.avg_comments_per_published_post }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Avg Likes / Post</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ activeMetrics.high_value_metrics.avg_likes_per_published_post }}
                    </p>
                </div>
                <div v-if="showActiveAuthorsMetric" class="rounded-xl border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Active Authors (30d)</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">
                        {{ formatNumber(activeMetrics.high_value_metrics.active_authors_30d ?? 0) }}
                    </p>
                </div>
            </div>

            <div
                v-if="personalGlobalBenchmarks.length > 0"
                class="flex flex-wrap gap-2"
            >
                <div
                    v-for="chip in personalGlobalBenchmarks"
                    :key="chip.label"
                    class="w-full rounded-md border border-border bg-card px-3 py-1 text-xs text-muted-foreground sm:w-auto sm:rounded-full"
                >
                    {{ chip.label }}: <span class="font-medium text-foreground">{{ chip.delta }}</span>
                </div>
            </div>

            <!-- User Trends (Master Admin / global scope only) -->
            <div v-if="showUserTrends">
                <h2 class="text-base font-semibold text-foreground">
                    User Trends
                </h2>
                <div class="mt-3 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Daily Active Users</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">
                            {{ formatNumber(activeMetrics.high_value_metrics.user_trends!.daily_active_users) }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Weekly Active Users</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">
                            {{ formatNumber(activeMetrics.high_value_metrics.user_trends!.weekly_active_users) }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Active Users (Last 30d)</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">
                            {{ formatNumber(activeMetrics.high_value_metrics.user_trends!.monthly_active_users) }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Total Users</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">
                            {{ formatNumber(activeMetrics.high_value_metrics.user_trends!.total_users) }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Total Inactive Users (30d)</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">
                            {{ formatNumber(activeMetrics.high_value_metrics.user_trends!.total_inactive_users) }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">2FA Adoption Rate</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">
                            {{
                                activeMetrics.high_value_metrics.two_factor_adoption_rate === null
                                    ? '—'
                                    : `${activeMetrics.high_value_metrics.two_factor_adoption_rate}%`
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Invitation Funnel (shown when data is available) -->
            <div v-if="showInvitationFunnel">
                <h2 class="text-base font-semibold text-foreground">
                    User Invitation Funnel
                </h2>
                <div class="mt-3 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Pending</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">
                            {{
                                activeMetrics.high_value_metrics.invitation_funnel.pending === null
                                    ? '—'
                                    : formatNumber(activeMetrics.high_value_metrics.invitation_funnel.pending)
                            }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Accepted</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">
                            {{
                                activeMetrics.high_value_metrics.invitation_funnel.accepted === null
                                    ? '—'
                                    : formatNumber(activeMetrics.high_value_metrics.invitation_funnel.accepted)
                            }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-4">
                        <p class="text-sm text-muted-foreground">Expired</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">
                            {{
                                activeMetrics.high_value_metrics.invitation_funnel.expired === null
                                    ? '—'
                                    : formatNumber(activeMetrics.high_value_metrics.invitation_funnel.expired)
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 30-Day Trends -->
            <div>
                <h2 class="text-base font-semibold text-foreground">
                    30-Day Trends
                </h2>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-border bg-card p-3">
                        <h3 class="text-sm font-medium text-foreground">
                            30-Day Content Activity
                        </h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Posts / Comments / Likes
                        </p>
                        <DashboardLineChart
                            class="mt-3"
                            :data="contentActivityChartData"
                            :options="trendChartOptions"
                        />
                    </div>
                    <div class="rounded-xl border border-border bg-card p-3">
                        <h3 class="text-sm font-medium text-foreground">{{ followerGrowthHeading }}</h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                activeDayCount(activeMetrics.high_value_metrics.follower_growth_30d) > 0
                                    ? `${activeDayCount(activeMetrics.high_value_metrics.follower_growth_30d)} day(s) with activity`
                                    : 'No activity in last 30 days'
                            }}
                        </p>
                        <DashboardLineChart
                            class="mt-3"
                            :data="followerGrowthChartData"
                            :options="trendChartOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- Top Performers -->
            <div>
                <h2 class="text-base font-semibold text-foreground">
                    Top Performers
                </h2>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-border bg-card p-3">
                        <h3 class="text-sm font-medium text-foreground">
                            Top Posts by Engagement
                        </h3>
                        <div
                            v-if="activeMetrics.comments_per_blog_post.length === 0"
                            class="mt-3 text-xs text-muted-foreground"
                        >
                            No published posts available yet.
                        </div>
                        <DashboardBarChart
                            v-else
                            class="mt-3"
                            :data="topPostsChartData"
                            :options="topPostsChartOptions"
                            height-class="h-80"
                        />
                    </div>
                    <div class="rounded-xl border border-border bg-card p-3">
                        <h3 class="text-sm font-medium text-foreground">{{ topAuthorsHeading }}</h3>
                        <div
                            v-if="activeMetrics.high_value_metrics.top_authors_by_published_posts_30d.length === 0"
                            class="mt-3 text-xs text-muted-foreground"
                        >
                            No authors with published posts in the last 30 days.
                        </div>
                        <DashboardBarChart
                            v-else
                            class="mt-3"
                            :data="topAuthorsChartData"
                            :options="topAuthorsChartOptions"
                            height-class="h-72"
                        />
                    </div>
                </div>
            </div>

            <!-- View Metrics -->
            <div v-if="showViewMetrics">
                <h2 class="text-base font-semibold text-foreground">
                    View Metrics
                </h2>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-border bg-card p-3">
                        <p class="text-sm text-muted-foreground">
                            Total Blog Post Views
                        </p>
                        <p class="mt-1 text-xl font-semibold text-foreground">
                            {{ formatNumber(activeMetrics.total_blog_post_views ?? 0) }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-3">
                        <p class="text-sm text-muted-foreground">
                            Average Views per Blog Post
                        </p>
                        <p class="mt-1 text-xl font-semibold text-foreground">
                            {{ activeMetrics.average_views_per_blog_post ?? 0 }}
                        </p>
                    </div>
                </div>
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-foreground">
                        30-Day View Trend
                    </h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            activeDayCount(activeMetrics.views_30d) > 0
                                ? `${activeDayCount(activeMetrics.views_30d)} day(s) with activity`
                                : 'No activity in last 30 days'
                        }}
                    </p>
                    <div class="mt-3 rounded-xl border border-border bg-card p-3">
                        <DashboardLineChart
                            :data="viewTrendChartData"
                            :options="viewTrendChartOptions"
                            height-class="h-40"
                        />
                        <div class="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-2">
                            <p>
                                Total 30d views:
                                <span class="font-medium text-foreground">{{ formatNumber(viewsTrendSummary.total) }}</span>
                            </p>
                            <p>
                                Peak day:
                                <span class="font-medium text-foreground">
                                    {{ viewsTrendSummary.peakDay ? `${formatDashboardDate(viewsTrendSummary.peakDay)} (${viewsTrendSummary.peakCount})` : '—' }}
                                </span>
                            </p>
                        </div>
                        <p v-if="viewsTrendSummary.total === 0" class="mt-2 text-xs text-muted-foreground">
                            No view counts recorded yet in this 30-day window.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
