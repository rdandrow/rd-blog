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
    const start = new Date();
    start.setHours(0, 0, 0, 0);
    start.setDate(start.getDate() - 29);

    return Array.from({ length: 30 }, (_, index) => {
        const day = new Date(start);
        day.setDate(start.getDate() + index);
        const key = day.toISOString().slice(0, 10);

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
        { id: 1, title: 'My Post', slug: 'my-post', comments_count: 2 },
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
        active_authors_30d: 1,
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
    },
});

const createGlobalMetrics = () => ({
    total_blog_post_views: null,
    average_views_per_blog_post: null,
    total_followers: 10,
    comments_per_blog_post: [
        { id: 2, title: 'Global Post', slug: 'global-post', comments_count: 5 },
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
                },
            },
        });

        expect(wrapper.text()).toContain('Total Blog Post Views');
        expect(wrapper.text()).toContain('Average Views per Blog Post');
        expect(wrapper.text()).toContain('Total Number of Followers');
        expect(wrapper.text()).toContain('Not available yet');
        expect(wrapper.text()).toContain('3');
        expect(wrapper.text()).toContain('High-Value Metrics');
        expect(wrapper.text()).toContain('Published Posts');
        expect(wrapper.text()).toContain('Draft Posts');
        expect(wrapper.text()).toContain('No activity in last 30 days');
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
                },
            },
        });

        expect(wrapper.text()).toContain('Personal');
        expect(wrapper.text()).toContain('Global');
        expect(wrapper.text()).toContain('My Post');
        expect(wrapper.text()).toContain('3');

        const globalButton = wrapper.findAll('button').find((b) => b.text() === 'Global');
        expect(globalButton).toBeTruthy();
        await globalButton!.trigger('click');

        expect(wrapper.text()).toContain('Global Post');
        expect(wrapper.text()).toContain('10');
        expect(wrapper.text()).toContain('90%');
        expect(wrapper.text()).toContain('Pending: 1 · Accepted: 4 · Expired: 2');
        expect(wrapper.text()).toContain('1 day(s) with activity');
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
                },
            },
        });

        expect(wrapper.text()).toContain('No published posts available yet.');
    });
});
