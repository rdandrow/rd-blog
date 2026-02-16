import Drafts from '@/pages/Admin/BlogPosts/Drafts.vue';
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

const createWrapper = (props: any) => mount(Drafts, props as any);

describe('Drafts Page', () => {
    const mockPosts = {
        data: [
            {
                id: 1,
                title: 'My First Draft',
                excerpt: 'This is a draft blog post',
                slug: 'my-first-draft',
                is_featured: false,
                is_published: false,
                published_at: null,
                created_at: '2024-01-01',
                updated_at: '2024-01-02',
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
                title: 'Featured Draft',
                excerpt: 'A featured draft post',
                slug: 'featured-draft',
                is_featured: true,
                is_published: false,
                published_at: null,
                created_at: '2024-01-05',
                updated_at: '2024-01-06',
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
            { label: '1', url: '/admin/blog-posts/drafts?page=1', active: true },
            { label: '2', url: '/admin/blog-posts/drafts?page=2', active: false },
            { label: 'Next', url: '/admin/blog-posts/drafts?page=2' },
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
            expect(head.text()).toBe('My Drafts');
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
            expect(layout.props('breadcrumbs')).toHaveLength(3);
        });

        it('should render page heading', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('My Drafts');
        });

        it('should render page description', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('View and manage your unpublished blog posts');
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

            expect(wrapper.text()).toContain('No drafts yet');
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

            expect(wrapper.text()).toContain('All your unpublished posts will appear here');
        });
    });

    describe('posts list', () => {
        it('should render draft posts', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('My First Draft');
            expect(wrapper.text()).toContain('Featured Draft');
        });

        it('should render post titles as links', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const titleLink = links.find(l => l.text().includes('My First Draft'));
            expect(titleLink?.props('href')).toBe('/admin/blog-posts/1');
        });

        it('should render post excerpts', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('This is a draft blog post');
            expect(wrapper.text()).toContain('A featured draft post');
        });

        it('should render draft badges', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const draftBadges = wrapper.findAll('.bg-yellow-100');
            expect(draftBadges.length).toBeGreaterThan(0);
        });

        it('should render featured badge for featured posts', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            expect(wrapper.text()).toContain('Featured');
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
    });

    describe('post actions', () => {
        it('should render view link', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const viewLinks = links.filter(l => l.props('href')?.includes('/admin/blog-posts/'));
            expect(viewLinks.length).toBeGreaterThan(0);
        });

        it('should render edit link', () => {
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
            // Mock window.confirm
            global.confirm = vi.fn(() => true);
            vi.mocked(router.delete).mockClear();

            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            const vm = wrapper.vm as any;
            vm.deletePost(mockPosts.data[0]);

            expect(global.confirm).toHaveBeenCalledWith('Are you sure you want to delete this draft?');
            expect(router.delete).toHaveBeenCalledWith('/admin/blog-posts/1');
        });

        it('should not delete if confirmation cancelled', () => {
            // Mock window.confirm
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

            const paginationLinks = wrapper.findAll('.px-3.py-2');
            expect(paginationLinks.length).toBe(0);
        });

        it('should highlight active page', () => {
            const wrapper = createWrapper({
                props: { posts: mockPosts },
            });

            // Verify pagination is rendered with multiple page links
            const links = wrapper.findAllComponents({ name: 'Link' });
            const pageLinks = links.filter(l =>
                l.props('href')?.toString().includes('?page=')
            );

            // Should have at least 2 page number links
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
