import Index from '@/pages/Admin/BlogPosts/Index.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div data-testid="head">{{ title }}</div>', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a :href="href" :class="classValue"><slot /></a>',
        props: ['href', 'class'],
        computed: {
            classValue(): any {
                return (this as any).class;
            },
        },
    },
    router: {
        delete: vi.fn(),
    },
}));

// Mock AppLayout
vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div data-testid="app-layout"><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

const createWrapper = (props: any) => mount(Index, props as any);

describe('Index Page', () => {
    const mockPosts = {
        data: [
            {
                id: 1,
                title: 'First Published Post',
                excerpt: 'This is a published post excerpt',
                slug: 'first-published-post',
                is_featured: false,
                is_published: true,
                published_at: '2024-01-10',
                created_at: '2024-01-01',
                reading_time: 5,
                tags: ['javascript', 'vue'],
                author: {
                    id: 1,
                    name: 'John Doe',
                    email: 'john@example.com',
                },
            },
            {
                id: 2,
                title: 'Featured Draft Post',
                excerpt: 'A featured draft post excerpt',
                slug: 'featured-draft-post',
                is_featured: true,
                is_published: false,
                published_at: null,
                created_at: '2024-01-05',
                reading_time: 8,
                tags: ['typescript'],
                author: {
                    id: 1,
                    name: 'John Doe',
                    email: 'john@example.com',
                },
            },
        ],
        links: [
            { label: 'Previous', url: null },
            { label: '1', url: '/admin/blog-posts?page=1', active: true },
            { label: '2', url: '/admin/blog-posts?page=2', active: false },
            { label: 'Next', url: '/admin/blog-posts?page=2' },
        ],
        meta: {},
    };

    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.text()).toBe('Blog Posts');
        });

        it('should render within AppLayout', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const layout = wrapper.findComponent({ name: 'AppLayout' });
            expect(layout.exists()).toBe(true);
        });

        it('should pass breadcrumbs to layout', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const layout = wrapper.findComponent({ name: 'AppLayout' });
            expect(layout.props('breadcrumbs')).toBeDefined();
            expect(layout.props('breadcrumbs')).toHaveLength(2);
        });

        it('should render page heading', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('Blog Posts');
        });

        it('should render page description', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('Manage your blog posts and articles');
        });

        it('should render new post button', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const newPostLink = links.find(l => l.text().includes('New Post'));
            expect(newPostLink).toBeTruthy();
            expect(newPostLink?.props('href')).toBe('/admin/blog-posts/create');
        });
    });

    describe('loading state', () => {
        it('should show loading state when posts is undefined', () => {
            const wrapper = createWrapper({
                props: { posts: undefined },
            });

            expect(wrapper.text()).toContain('Loading...');
        });

        it('should show spinner in loading state', () => {
            const wrapper = createWrapper({
                props: { posts: undefined },
            });

            const spinner = wrapper.find('.animate-spin');
            expect(spinner.exists()).toBe(true);
        });
    });

    describe('empty state', () => {
        it('should show empty state when no posts', () => {
            const wrapper = createWrapper({
                props: { posts: { data: [], links: [], meta: {} } },
            });

            expect(wrapper.text()).toContain('No blog posts yet');
        });

        it('should show create post link in empty state', () => {
            const wrapper = createWrapper({
                props: { posts: { data: [], links: [], meta: {} } },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const createLink = links.find(l => l.text().includes('Create Your First Post'));
            expect(createLink).toBeTruthy();
        });

        it('should show empty state message', () => {
            const wrapper = createWrapper({
                props: { posts: { data: [], links: [], meta: {} } },
            });

            expect(wrapper.text()).toContain('Get started by creating your first blog post');
        });
    });

    describe('posts list', () => {
        it('should render all posts', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('First Published Post');
            expect(wrapper.text()).toContain('Featured Draft Post');
        });

        it('should render post titles as links', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const titleLink = links.find(l => l.text().includes('First Published Post'));
            expect(titleLink?.props('href')).toBe('/admin/blog-posts/1');
        });

        it('should render post excerpts', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('This is a published post excerpt');
            expect(wrapper.text()).toContain('A featured draft post excerpt');
        });

        it('should render published status badge', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('Published');
            const publishedBadges = wrapper.findAll('.bg-green-100');
            expect(publishedBadges.length).toBeGreaterThan(0);
        });

        it('should render draft status badge', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('Draft');
            const draftBadges = wrapper.findAll('.bg-yellow-100');
            expect(draftBadges.length).toBeGreaterThan(0);
        });

        it('should render featured badge for featured posts', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('Featured');
            const featuredBadges = wrapper.findAll('.bg-blue-100');
            expect(featuredBadges.length).toBeGreaterThan(0);
        });

        it('should render author name', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('By John Doe');
        });

        it('should render reading time', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('5 min read');
            expect(wrapper.text()).toContain('8 min read');
        });

        it('should render tags', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('javascript');
            expect(wrapper.text()).toContain('vue');
            expect(wrapper.text()).toContain('typescript');
        });

        it('should render published date for published posts', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('Published');
        });

        it('should render created date for draft posts', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('Created');
        });
    });

    describe('post actions', () => {
        it('should render view button', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const viewLinks = links.filter(l => l.props('href')?.includes('/admin/blog-posts/'));
            expect(viewLinks.length).toBeGreaterThan(0);
        });

        it('should render edit button', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const editLink = links.find(l => l.props('href')?.includes('/edit'));
            expect(editLink).toBeTruthy();
        });

        it('should render delete button', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const deleteButtons = wrapper.findAll('button');
            expect(deleteButtons.length).toBeGreaterThan(0);
        });
    });

    describe('status badge helper', () => {
        it('should return published status for published posts', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const vm = wrapper.vm as any;
            const status = vm.getStatusBadge(mockPosts.data[0]);
            expect(status.text).toBe('Published');
            expect(status.class).toContain('bg-green-100');
        });

        it('should return draft status for draft posts', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const vm = wrapper.vm as any;
            const status = vm.getStatusBadge(mockPosts.data[1]);
            expect(status.text).toBe('Draft');
            expect(status.class).toContain('bg-yellow-100');
        });
    });

    describe('date formatting', () => {
        it('should format dates correctly', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const vm = wrapper.vm as any;
            const formatted = vm.formatDate('2024-01-15');
            expect(formatted).toContain('Jan');
            expect(formatted).toContain('2024');
        });

        it('should handle null dates', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const vm = wrapper.vm as any;
            const formatted = vm.formatDate(null);
            expect(formatted).toBe('No date');
        });
    });

    describe('delete functionality', () => {
        it('should call deletePost with confirmation', () => {
            global.confirm = vi.fn(() => true);
            vi.mocked(router.delete).mockClear();

            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const vm = wrapper.vm as any;
            vm.deletePost(mockPosts.data[0]);

            expect(global.confirm).toHaveBeenCalledWith('Are you sure you want to delete this post?');
            expect(router.delete).toHaveBeenCalledWith('/admin/blog-posts/1');
        });

        it('should not delete if confirmation cancelled', () => {
            global.confirm = vi.fn(() => false);
            vi.mocked(router.delete).mockClear();

            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const vm = wrapper.vm as any;
            vm.deletePost(mockPosts.data[0]);

            expect(global.confirm).toHaveBeenCalled();
            expect(router.delete).not.toHaveBeenCalled();
        });
    });

    describe('pagination', () => {
        it('should render pagination links when available', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const paginationLinks = links.filter(l =>
                l.props('href')?.toString().includes('?page=')
            );
            expect(paginationLinks.length).toBeGreaterThan(0);
        });

        it('should not render pagination if less than 4 links', () => {
            const wrapper = createWrapper({
                props: {
                    posts: {
                        data: mockPosts.data,
                        links: [
                            { label: 'Previous', url: null },
                            { label: 'Next', url: null },
                        ],
                        meta: {},
                    },
                },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const paginationLinks = links.filter(l =>
                l.props('href')?.toString().includes('?page=')
            );
            expect(paginationLinks.length).toBe(0);
        });

        it('should render multiple page links', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const pageLinks = links.filter(l =>
                l.props('href')?.toString().includes('?page=')
            );

            expect(pageLinks.length).toBeGreaterThanOrEqual(2);
        });
    });

    describe('props', () => {
        it('should accept posts prop', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.props('posts')).toEqual(mockPosts);
        });

        it('should handle undefined posts prop', () => {
            const wrapper = createWrapper({
                props: { posts: undefined },
            });

            expect(wrapper.props('posts')).toBeUndefined();
        });
    });
});
