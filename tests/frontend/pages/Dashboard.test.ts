import Dashboard from '@/pages/Dashboard.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        template: '<div data-testid="head">{{ title }}</div>',
        props: ['title'],
    },
}));

vi.mock('vue-chartjs', () => ({
    Line: {
        name: 'LineChart',
        template: '<div data-testid="line-chart"></div>',
        props: ['data', 'options'],
    },
}));

vi.mock('@/components/charts/DashboardLineChart.vue', () => ({
    default: {
        name: 'DashboardLineChart',
        template: '<div data-testid="line-chart"></div>',
        props: ['data', 'options', 'heightClass'],
    },
}));

vi.mock('@/components/charts/DashboardBarChart.vue', () => ({
    default: {
        name: 'DashboardBarChart',
        template: '<div data-testid="bar-chart"></div>',
        props: ['data', 'options', 'heightClass'],
    },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div data-testid="app-layout"><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

vi.mock('@/routes', () => ({
    dashboard: () => ({ url: '/admin/dashboard' }),
}));

const createBackfilledTrend = (activeDays: Record<string, number> = {}) => {
    const todayUTC = new Date().toISOString().slice(0, 10);

    return Array.from({ length: 30 }, (_, index) => {
        const d = new Date(`${todayUTC}T00:00:00Z`);
        d.setUTCDate(d.getUTCDate() - (29 - index));
        const key = d.toISOString().slice(0, 10);

        return {
            day: key,
            count: activeDays[key] ?? 0,
        };
    });
};

const createPersonalMetrics = () => ({
    total_blog_post_views: null,
    average_views_per_blog_post: null,
    total_followers: 3,
    comments_per_blog_post: [
        { id: 1, title: 'My Post', slug: 'my-post', comments_count: 2, likes_count: 1 },
    ],
    views_30d: [],
    high_value_metrics: {
        published_posts: 2,
        draft_posts: 1,
        featured_posts: 1,
        total_comments_on_published_posts: 5,
        total_likes_on_published_posts: 7,
        avg_comments_per_published_post: 2.5,
        avg_likes_per_published_post: 3.5,
        active_authors_30d: null,
        top_authors_by_published_posts_30d: [
            { id: 1, name: 'Admin Author', published_posts_count: 2 },
        ],
        follower_growth_30d: createBackfilledTrend(),
        posts_published_30d: createBackfilledTrend(),
        comments_created_30d: createBackfilledTrend(),
        likes_created_30d: createBackfilledTrend(),
        two_factor_adoption_rate: null,
        invitation_funnel: {
            pending: null,
            accepted: null,
            expired: null,
        },
        user_trends: null,
    },
});

const createGlobalMetrics = () => ({
    total_blog_post_views: null,
    average_views_per_blog_post: null,
    total_followers: 10,
    comments_per_blog_post: [
        { id: 2, title: 'Global Post', slug: 'global-post', comments_count: 5, likes_count: 8 },
    ],
    views_30d: [],
    high_value_metrics: {
        published_posts: 10,
        draft_posts: 4,
        featured_posts: 2,
        total_comments_on_published_posts: 28,
        total_likes_on_published_posts: 40,
        avg_comments_per_published_post: 2.8,
        avg_likes_per_published_post: 4,
        active_authors_30d: 6,
        top_authors_by_published_posts_30d: [
            { id: 2, name: 'Global Author', published_posts_count: 10 },
        ],
        follower_growth_30d: createBackfilledTrend({ [new Date().toISOString().slice(0, 10)]: 2 }),
        posts_published_30d: createBackfilledTrend({ [new Date().toISOString().slice(0, 10)]: 1 }),
        comments_created_30d: createBackfilledTrend({ [new Date().toISOString().slice(0, 10)]: 3 }),
        likes_created_30d: createBackfilledTrend({ [new Date().toISOString().slice(0, 10)]: 4 }),
        two_factor_adoption_rate: 90,
        invitation_funnel: {
            pending: 1,
            accepted: 4,
            expired: 2,
        },
        user_trends: {
            total_users: 25,
            daily_active_users: 5,
            weekly_active_users: 12,
            monthly_active_users: 18,
            total_inactive_users: 7,
        },
    },
});

describe('Dashboard Page', () => {
    it('renders requested metric cards with placeholders for unavailable views data', () => {
        const wrapper = mount(Dashboard, {
            props: {
                metricsByScope: {
                    personal: createPersonalMetrics(),
                },
                meta: {
                    views_tracking_enabled: false,
                    available_scopes: ['personal'],
                    default_scope: 'personal',
                    generated_at: new Date().toISOString(),
                },
            },
        });

        expect(wrapper.text()).toContain('Published Posts');
        expect(wrapper.text()).toContain('Featured Posts');
        expect(wrapper.text()).toContain('Total Followers');
        expect(wrapper.text()).toContain('Total Comments');
        expect(wrapper.text()).toContain('Total Likes');
        expect(wrapper.text()).toContain('Avg Comments / Post');
        expect(wrapper.text()).toContain('Avg Likes / Post');
        expect(wrapper.text()).not.toContain('Active Authors (30d)');
        expect(wrapper.text()).not.toContain('2FA Adoption Rate');
        expect(wrapper.text()).not.toContain('User Trends');
        expect(wrapper.text()).not.toContain('Invitation Funnel');
        expect(wrapper.text()).toContain('30-Day Trends');
        expect(wrapper.text()).toContain('30-Day Content Activity');
        expect(wrapper.text()).toContain('30-Day Follower Growth (You)');
        expect(wrapper.text()).toContain('Top Performers');
        expect(wrapper.text()).toContain('Top Posts by Engagement');
        expect(wrapper.text()).toContain('Top Authors (Platform, Last 30 Days)');
        expect(wrapper.text()).not.toContain('View Metrics');
        expect(wrapper.text()).not.toContain('Not available yet');
        expect(wrapper.text()).toContain('3');
    });

    it('hides scope switch when only personal scope is available', () => {
        const wrapper = mount(Dashboard, {
            props: {
                metricsByScope: {
                    personal: createPersonalMetrics(),
                },
                meta: {
                    views_tracking_enabled: false,
                    available_scopes: ['personal'],
                    default_scope: 'personal',
                    generated_at: new Date().toISOString(),
                },
            },
        });

        expect(wrapper.text()).not.toContain('Global');
    });

    it('shows scope switch and toggles between personal and global metrics', async () => {
        const wrapper = mount(Dashboard, {
            props: {
                metricsByScope: {
                    personal: createPersonalMetrics(),
                    global: createGlobalMetrics(),
                },
                meta: {
                    views_tracking_enabled: false,
                    available_scopes: ['personal', 'global'],
                    default_scope: 'personal',
                    generated_at: new Date().toISOString(),
                },
            },
        });

        expect(wrapper.text()).toContain('Personal');
        expect(wrapper.text()).toContain('Global');
        expect(wrapper.text()).toContain('3');
        expect(wrapper.text()).toContain('Comments/Post vs Global');
        expect(wrapper.text()).toContain('Likes/Post vs Global');
        expect(wrapper.text()).not.toContain('2FA Adoption Rate');
        expect(wrapper.text()).not.toContain('User Trends');
        expect(wrapper.text()).not.toContain('Invitation Funnel');

        const globalButton = wrapper.findAll('button').find((b) => b.text() === 'Global');
        expect(globalButton).toBeTruthy();
        await globalButton!.trigger('click');

        expect(wrapper.text()).toContain('10');
        expect(wrapper.text()).toContain('User Trends');
        expect(wrapper.text()).toContain('Total Users');
        expect(wrapper.text()).toContain('Daily Active Users');
        expect(wrapper.text()).toContain('Weekly Active Users');
        expect(wrapper.text()).toContain('Active Users (Last 30d)');
        expect(wrapper.text()).toContain('30-Day Follower Growth (Platform)');
        expect(wrapper.text()).toContain('2FA Adoption Rate');
        expect(wrapper.text()).toContain('90%');
        expect(wrapper.text()).toContain('25');
        expect(wrapper.text()).toContain('Active Authors (30d)');
        expect(wrapper.text()).toContain('6');
        expect(wrapper.text()).toContain('1 day(s) with activity');
        expect(wrapper.text()).toContain('Invitation Funnel');
        expect(wrapper.text()).toContain('Pending');
        expect(wrapper.text()).toContain('Accepted');
        expect(wrapper.text()).toContain('Expired');
    });

    it('renders empty-state text when active scope has no comments rows', () => {
        const wrapper = mount(Dashboard, {
            props: {
                metricsByScope: {
                    personal: {
                        ...createPersonalMetrics(),
                        comments_per_blog_post: [],
                    },
                },
                meta: {
                    views_tracking_enabled: false,
                    available_scopes: ['personal'],
                    default_scope: 'personal',
                    generated_at: new Date().toISOString(),
                },
            },
        });

        expect(wrapper.text()).toContain('No published posts available yet.');
    });

    it('renders enabled view tracking state and numeric view metrics when available', () => {
        const wrapper = mount(Dashboard, {
            props: {
                metricsByScope: {
                    personal: {
                        ...createPersonalMetrics(),
                        total_blog_post_views: 42,
                        average_views_per_blog_post: 7,
                        views_30d: createBackfilledTrend({ [new Date().toISOString().slice(0, 10)]: 5 }),
                    },
                },
                meta: {
                    views_tracking_enabled: true,
                    available_scopes: ['personal'],
                    default_scope: 'personal',
                    generated_at: new Date().toISOString(),
                },
            },
        });

        expect(wrapper.text()).toContain('Total Blog Post Views');
        expect(wrapper.text()).toContain('Average Views per Blog Post');
        expect(wrapper.text()).toContain('42');
        expect(wrapper.text()).toContain('7');
        expect(wrapper.text()).toContain('30-Day View Trend');
        expect(wrapper.text()).toContain('1 day(s) with activity');
        expect(wrapper.text()).toContain('Total 30d views:');
        expect(wrapper.text()).not.toContain('View tracking is not enabled yet.');
    });
});
